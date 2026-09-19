<?php
/**
 * SMS 2 – Google Authenticator (TOTP) helpers
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/crypto.php';

function smsEnsureAuthenticatorTable(): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_authenticators (
            user_id INT UNSIGNED NOT NULL,
            secret VARCHAR(512) NOT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 0,
            pending_secret VARCHAR(512) NULL,
            confirmed_at DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            CONSTRAINT fk_ua_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    // Widen legacy VARCHAR(64) columns so encrypted secrets fit
    try {
        $pdo->exec('ALTER TABLE user_authenticators MODIFY secret VARCHAR(512) NOT NULL');
        $pdo->exec('ALTER TABLE user_authenticators MODIFY pending_secret VARCHAR(512) NULL');
    } catch (Throwable $e) {
        // Ignore if already widened / no permission
    }
}

function smsTotpBase32Encode(string $data): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split($data) as $char) {
        $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $fiveBit = str_split($binary, 5);
    $out = '';
    foreach ($fiveBit as $chunk) {
        if (strlen($chunk) < 5) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        }
        $out .= $alphabet[bindec($chunk)];
    }
    return $out;
}

function smsTotpBase32Decode(string $b32): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32) ?? '');
    $binary = '';
    for ($i = 0, $len = strlen($b32); $i < $len; $i++) {
        $val = strpos($alphabet, $b32[$i]);
        if ($val === false) {
            continue;
        }
        $binary .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
    }
    $bytes = str_split($binary, 8);
    $out = '';
    foreach ($bytes as $byte) {
        if (strlen($byte) === 8) {
            $out .= chr(bindec($byte));
        }
    }
    return $out;
}

function smsTotpGenerateSecret(int $bytes = 20): string
{
    return smsTotpBase32Encode(random_bytes($bytes));
}

function smsTotpCode(string $secret, ?int $timeSlice = null, int $digits = 6, int $period = 30): string
{
    $timeSlice = $timeSlice ?? (int) floor(time() / $period);
    $secretKey = smsTotpBase32Decode($secret);
    $time = pack('N*', 0, $timeSlice);
    $hash = hash_hmac('sha1', $time, $secretKey, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncated = (
        ((ord($hash[$offset]) & 0x7F) << 24)
        | ((ord($hash[$offset + 1]) & 0xFF) << 16)
        | ((ord($hash[$offset + 2]) & 0xFF) << 8)
        | (ord($hash[$offset + 3]) & 0xFF)
    );
    $otp = $truncated % (10 ** $digits);
    return str_pad((string) $otp, $digits, '0', STR_PAD_LEFT);
}

function smsTotpVerify(string $secret, string $code, int $window = 1, int $period = 30): bool
{
    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) !== 6) {
        return false;
    }
    $timeSlice = (int) floor(time() / $period);
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(smsTotpCode($secret, $timeSlice + $i, 6, $period), $code)) {
            return true;
        }
    }
    return false;
}

function smsTotpOtpAuthUri(string $secret, string $accountName, string $issuer = APP_SHORT_NAME): string
{
    $label = rawurlencode($issuer . ':' . $accountName);
    $query = http_build_query([
        'secret' => $secret,
        'issuer' => $issuer,
        'algorithm' => 'SHA1',
        'digits' => 6,
        'period' => 30,
    ], '', '&', PHP_QUERY_RFC3986);
    return 'otpauth://totp/' . $label . '?' . $query;
}

/**
 * Local QR markup (canvas) — loads vendor QRCode immediately (no external QR image APIs; CSP-safe).
 * Large high-contrast canvas for easy phone scanning.
 */
function smsTotpQrMarkup(string $otpAuthUri, int $size = 280): string
{
    $size = max(200, min(360, $size));
    $id = 'smsTotpQr_' . bin2hex(random_bytes(4));
    $uriJson = json_encode($otpAuthUri, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $idJson = json_encode($id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $scriptUrl = e(BASE_URL . '/assets/js/vendor/qrcode.min.js');

    return '<div class="sms-totp-qr-wrap">'
        . '<div class="sms-totp-qr" style="width:' . $size . 'px;height:' . $size . 'px;">'
        . '<canvas id="' . e($id) . '" width="' . $size . '" height="' . $size
        . '" role="img" aria-label="Authenticator QR code — scan with Google Authenticator"></canvas>'
        . '<div class="sms-totp-qr-loading" id="' . e($id) . '_loading" aria-hidden="true">Preparing QR…</div>'
        . '</div>'
        . '<p class="sms-totp-qr-caption mb-0">Point your camera at this code</p>'
        . '</div>'
        . '<script src="' . $scriptUrl . '"></script>'
        . '<script>(function(){'
        . 'var id=' . $idJson . ',uri=' . $uriJson . ',size=' . $size . ';'
        . 'function hideLoad(){var el=document.getElementById(id+"_loading");if(el)el.style.display="none";}'
        . 'function draw(){var c=document.getElementById(id);'
        . 'if(!c)return;if(typeof QRCode==="undefined"){setTimeout(draw,50);return;}'
        . 'QRCode.toCanvas(c,uri,{width:size,margin:3,errorCorrectionLevel:"H",'
        . 'color:{dark:"#0f172a",light:"#ffffff"}},function(err){hideLoad();'
        . 'if(err){c.setAttribute("aria-label","QR failed — use the manual key");}});}'
        . 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",draw);}'
        . 'else{draw();}'
        . '})();</script>';
}

/** @deprecated Use smsTotpQrMarkup — kept for any old callers */
function smsTotpQrImageUrl(string $otpAuthUri, int $size = 200): string
{
    return 'data:image/svg+xml,' . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '">'
        . '<rect width="100%" height="100%" fill="#fff"/>'
        . '<text x="50%" y="50%" text-anchor="middle" fill="#666" font-size="12">Use manual key</text></svg>'
    );
}

/**
 * @return array{user_id:int,secret:string,enabled:bool,pending_secret:?string,confirmed_at:?string}|null
 */
function smsAuthenticatorGet(int $userId): ?array
{
    smsEnsureAuthenticatorTable();
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM user_authenticators WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $secretPlain = smsSecretDecrypt((string) $row['secret']);
    $pendingPlain = $row['pending_secret'] !== null
        ? smsSecretDecrypt((string) $row['pending_secret'])
        : null;

    // Lazily upgrade legacy plaintext secrets to encrypted storage
    try {
        $needUpgrade = false;
        $encSecret = (string) $row['secret'];
        $encPending = $row['pending_secret'] !== null ? (string) $row['pending_secret'] : null;
        if ($secretPlain !== '' && !smsCryptoIsEncrypted((string) $row['secret'])) {
            $encSecret = smsSecretEncrypt($secretPlain);
            $needUpgrade = true;
        }
        if ($pendingPlain !== null && $pendingPlain !== '' && !smsCryptoIsEncrypted((string) $row['pending_secret'])) {
            $encPending = smsSecretEncrypt($pendingPlain);
            $needUpgrade = true;
        }
        if ($needUpgrade) {
            $pdo->prepare(
                'UPDATE user_authenticators SET secret = ?, pending_secret = ?, updated_at = NOW() WHERE user_id = ?'
            )->execute([$encSecret, $encPending, $userId]);
        }
    } catch (Throwable $e) {
        // non-fatal
    }

    return [
        'user_id' => (int) $row['user_id'],
        'secret' => $secretPlain,
        'enabled' => ((int) $row['enabled']) === 1,
        'pending_secret' => $pendingPlain,
        'confirmed_at' => $row['confirmed_at'] !== null ? (string) $row['confirmed_at'] : null,
    ];
}

function smsAuthenticatorIsEnabled(int $userId): bool
{
    $row = smsAuthenticatorGet($userId);
    return $row !== null && !empty($row['enabled']) && $row['secret'] !== '';
}

/**
 * Start setup: create/replace pending secret. Returns secret for QR.
 */
function smsAuthenticatorBeginSetup(int $userId): ?string
{
    smsEnsureAuthenticatorTable();
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return null;
    }
    $secret = smsTotpGenerateSecret();
    $encSecret = smsSecretEncrypt($secret);
    $existing = smsAuthenticatorGet($userId);
    if ($existing) {
        $pdo->prepare(
            'UPDATE user_authenticators SET pending_secret = ?, updated_at = NOW() WHERE user_id = ?'
        )->execute([$encSecret, $userId]);
    } else {
        $pdo->prepare(
            'INSERT INTO user_authenticators (user_id, secret, enabled, pending_secret)
             VALUES (?, ?, 0, ?)'
        )->execute([$userId, $encSecret, $encSecret]);
    }
    return $secret;
}

/**
 * Confirm setup with a valid TOTP from the pending (or current) secret and enable.
 */
function smsAuthenticatorConfirmEnable(int $userId, string $code): bool
{
    smsEnsureAuthenticatorTable();
    $pdo = db();
    $row = smsAuthenticatorGet($userId);
    if (!$pdo || !$row) {
        return false;
    }
    $secret = $row['pending_secret'] ?: $row['secret'];
    if ($secret === '' || !smsTotpVerify($secret, $code)) {
        return false;
    }
    $pdo->prepare(
        'UPDATE user_authenticators
         SET secret = ?, pending_secret = NULL, enabled = 1, confirmed_at = NOW()
         WHERE user_id = ?'
    )->execute([smsSecretEncrypt($secret), $userId]);
    return true;
}

function smsAuthenticatorDisable(int $userId): bool
{
    smsEnsureAuthenticatorTable();
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return false;
    }
    // Invalidate previous app secret so Turn On requires a fresh QR / code.
    $pdo->prepare(
        'UPDATE user_authenticators
         SET enabled = 0, secret = ?, pending_secret = NULL, confirmed_at = NULL, updated_at = NOW()
         WHERE user_id = ?'
    )->execute([smsSecretEncrypt(smsTotpGenerateSecret()), $userId]);
    return true;
}

function smsAuthenticatorVerifyLogin(int $userId, string $code): bool
{
    $row = smsAuthenticatorGet($userId);
    if (!$row || empty($row['enabled'])) {
        return false;
    }
    return smsTotpVerify($row['secret'], $code);
}
