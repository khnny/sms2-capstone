<?php
/**
 * SMS 2 – Passkeys (WebAuthn) helpers
 *
 * Uses browser PublicKeyCredential.getPublicKey() (Chrome/Edge/Safari recent)
 * so we can verify with OpenSSL without a full CBOR stack.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/authentication.php';

function smsPasskeyTableSql(): string
{
    return sms2_quote_table(sms2_table('user_passkeys'));
}

function smsPasskeyUsersTableSql(): string
{
    return sms2_quote_table(sms2_table('users'));
}

/**
 * HostForge / partial dumps sometimes create id without AUTO_INCREMENT (MySQL 1364).
 * Also widen credential_id when still VARCHAR(255).
 */
function smsPasskeyEnsureSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $tableSql = smsPasskeyTableSql();

    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . $tableSql . " LIKE 'id'");
        $col = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (is_array($col)) {
            $extra = strtolower((string) ($col['Extra'] ?? ''));
            if (!str_contains($extra, 'auto_increment')) {
                $keyStmt = $pdo->query('SHOW KEYS FROM ' . $tableSql . " WHERE Key_name = 'PRIMARY'");
                $hasPk = $keyStmt && (bool) $keyStmt->fetch(PDO::FETCH_ASSOC);
                if (!$hasPk) {
                    $pdo->exec('ALTER TABLE ' . $tableSql . ' ADD PRIMARY KEY (`id`)');
                }
                $pdo->exec('ALTER TABLE ' . $tableSql . ' MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT');
            }
        }
    } catch (Throwable $e) {
        error_log('SMS2 passkey id AUTO_INCREMENT repair failed: ' . $e->getMessage());
    }

    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . $tableSql . " LIKE 'credential_id'");
        $col = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (is_array($col)) {
            $type = strtolower((string) ($col['Type'] ?? ''));
            if (preg_match('/varchar\((\d+)\)/', $type, $m) && (int) $m[1] < 1024) {
                $pdo->exec('ALTER TABLE ' . $tableSql . ' MODIFY `credential_id` VARCHAR(1024) NOT NULL');
            }
        }
    } catch (Throwable $e) {
        error_log('SMS2 passkey credential_id widen failed: ' . $e->getMessage());
    }

    try {
        $keys = $pdo->query('SHOW KEYS FROM ' . $tableSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $names = [];
        foreach ($keys as $k) {
            $names[strtolower((string) ($k['Key_name'] ?? ''))] = true;
        }
        if (empty($names['uq_passkey_cred'])) {
            $pdo->exec('ALTER TABLE ' . $tableSql . ' ADD UNIQUE KEY `uq_passkey_cred` (`credential_id`(255))');
        }
        if (empty($names['idx_passkey_user'])) {
            $pdo->exec('ALTER TABLE ' . $tableSql . ' ADD KEY `idx_passkey_user` (`user_id`)');
        }
    } catch (Throwable $e) {
        error_log('SMS2 passkey index repair failed: ' . $e->getMessage());
    }
}

function smsEnsurePasskeyTable(): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    $tableSql = smsPasskeyTableSql();
    $usersSql = smsPasskeyUsersTableSql();

    try {
        $pdo->query('SELECT 1 FROM ' . $tableSql . ' LIMIT 1');
        smsPasskeyEnsureSchema($pdo);
        return;
    } catch (Throwable $e) {
        // create below
    }

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . $tableSql . ' (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `credential_id` VARCHAR(1024) NOT NULL,
                `public_key` TEXT NOT NULL,
                `sign_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `device_name` VARCHAR(120) NOT NULL DEFAULT \'Passkey\',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_used_at` DATETIME NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_passkey_cred` (`credential_id`(255)),
                KEY `idx_passkey_user` (`user_id`),
                CONSTRAINT `fk_passkey_user` FOREIGN KEY (`user_id`) REFERENCES ' . $usersSql . ' (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        smsPasskeyEnsureSchema($pdo);
    } catch (Throwable $e) {
        error_log('SMS2 passkey table create failed: ' . $e->getMessage());
    }
}
function smsPasskeyRpId(): string
{
    // Optional override (HostForge / custom domain). Must match the browser address-bar host.
    if (function_exists('sms2_env')) {
        $override = trim((string) (sms2_env('SMS2_WEBAUTHN_RP_ID') ?? ''));
        if ($override !== '') {
            $override = preg_replace('/:\d+$/', '', strtolower($override)) ?? '';
            if ($override !== '') {
                return $override;
            }
        }
    }

    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (isset($_SERVER['HTTP_X_FORWARDED_HOST']) && is_string($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $fwd = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
        if ($fwd !== '') {
            $host = $fwd;
        }
    }
    $host = preg_replace('/:\d+$/', '', $host) ?? 'localhost';
    $host = strtolower(trim($host));
    // rpId must match the address bar host exactly (do not remap 127.0.0.1 <-> localhost)
    return $host !== '' ? $host : 'localhost';
}
function smsPasskeyRpName(): string
{
    return defined('APP_SHORT_NAME') ? (string) APP_SHORT_NAME : 'SMS2';
}

function smsPasskeyOrigin(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    // Keep origin host as the browser sent it (including 127.0.0.1 if used)
    return $scheme . '://' . $host;
}

/**
 * Accept browser origin if it matches this request (scheme + host), for verify step.
 */
function smsPasskeyOriginAllowed(string $clientOrigin): bool
{
    $expected = smsPasskeyOrigin();
    if (hash_equals($expected, $clientOrigin)) {
        return true;
    }
    // Allow localhost <-> 127.0.0.1 equivalence on same scheme
    $a = parse_url($expected);
    $b = parse_url($clientOrigin);
    if (!$a || !$b) {
        return false;
    }
    $schemeA = strtolower((string) ($a['scheme'] ?? ''));
    $schemeB = strtolower((string) ($b['scheme'] ?? ''));
    if ($schemeA !== $schemeB) {
        return false;
    }
    $hostA = strtolower((string) ($a['host'] ?? ''));
    $hostB = strtolower((string) ($b['host'] ?? ''));
    $loopback = ['localhost', '127.0.0.1', '::1'];
    if (in_array($hostA, $loopback, true) && in_array($hostB, $loopback, true)) {
        return true;
    }
    return false;
}

function smsB64UrlEncode(string $bin): string
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

function smsB64UrlDecode(string $b64): string
{
    $b64 = strtr($b64, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad > 0) {
        $b64 .= str_repeat('=', 4 - $pad);
    }
    $raw = base64_decode($b64, true);
    return $raw === false ? '' : $raw;
}

/**
 * @return list<array<string,mixed>>
 */
/**
 * Normalize WebAuthn credential id to unpadded base64url.
 */
function smsPasskeyNormalizeCredentialId(string $credId): string
{
    $credId = trim($credId);
    if ($credId === '') {
        return '';
    }
    if (preg_match('#^[A-Za-z0-9_\-+/=]+$#', $credId)) {
        $raw = smsB64UrlDecode($credId);
        if ($raw !== '') {
            return smsB64UrlEncode($raw);
        }
        return rtrim(strtr($credId, '+/', '-_'), '=');
    }
    return smsB64UrlEncode($credId);
}

/**
 * @return list<string>
 */
function smsPasskeyCredentialIdLookupKeys(string $credId): array
{
    $norm = smsPasskeyNormalizeCredentialId($credId);
    $keys = [];
    foreach ([$norm, $credId, rtrim($credId, '='), strtr($credId, '-_', '+/'), strtr($norm, '-_', '+/')] as $k) {
        $k = trim((string) $k);
        if ($k !== '' && !in_array($k, $keys, true)) {
            $keys[] = $k;
        }
        $pad = strlen($k) % 4;
        if ($pad > 0) {
            $padded = $k . str_repeat('=', 4 - $pad);
            if (!in_array($padded, $keys, true)) {
                $keys[] = $padded;
            }
        }
    }
    return $keys;
}

function smsPasskeyStepUpClear(): void
{
    unset($_SESSION['passkey_stepup_until'], $_SESSION['passkey_stepup_user']);
}

function smsPasskeyStepUpGrant(int $userId, int $ttlSeconds = 300): void
{
    $_SESSION['passkey_stepup_user'] = $userId;
    $_SESSION['passkey_stepup_until'] = time() + max(60, $ttlSeconds);
}

function smsPasskeyStepUpOk(int $userId): bool
{
    $until = (int) ($_SESSION['passkey_stepup_until'] ?? 0);
    $uid = (int) ($_SESSION['passkey_stepup_user'] ?? 0);
    return $userId > 0 && $uid === $userId && $until >= time();
}

/**
 * @return array{method:string,label:string,email:string,email_masked:string}
 */
function smsPasskeyStepUpMethod(int $userId): array
{
    return smsPasskeyRemoveMethod($userId);
}

/**
 * @return array{ok:bool,error:string}
 */
function smsPasskeyVerifyStepUpProof(int $userId, string $method, array $body, string $otpPurpose = 'passkey_add'): array
{
    require_once __DIR__ . '/totp.php';
    require_once __DIR__ . '/security-workflow.php';

    $expected = smsPasskeyStepUpMethod($userId);
    if ($method !== (string) ($expected['method'] ?? '')) {
        return ['ok' => false, 'error' => 'Verification method changed. Refresh and try again.'];
    }

    $gatePurpose = 'passkey_stepup_' . $otpPurpose;
    $gate = smsGetCodeGate($userId, $gatePurpose);
    if (!empty($gate['locked'])) {
        return ['ok' => false, 'error' => smsCodeFailureMessage($gate, $method === 'password' ? 'password' : 'code')];
    }

    if ($method === 'authenticator') {
        $code = trim((string) ($body['totp_code'] ?? $body['code'] ?? ''));
        if ($code === '' || !smsAuthenticatorVerifyLogin($userId, $code)) {
            $gate = smsRegisterCodeFailure($userId, $gatePurpose);
            return ['ok' => false, 'error' => smsCodeFailureMessage($gate, 'code') ?: 'Invalid Authenticator code.'];
        }
        smsClearCodeGate($userId, $gatePurpose);
        return ['ok' => true, 'error' => ''];
    }

    if ($method === 'email') {
        $code = trim((string) ($body['otp_code'] ?? $body['code'] ?? ''));
        if ($code === '' || !smsVerifyOtp($userId, $otpPurpose, $code)) {
            $gate = smsRegisterCodeFailure($userId, $gatePurpose);
            return ['ok' => false, 'error' => smsCodeFailureMessage($gate, 'code') ?: 'Invalid or expired email code.'];
        }
        smsClearCodeGate($userId, $gatePurpose);
        return ['ok' => true, 'error' => ''];
    }

    $password = (string) ($body['password'] ?? '');
    $pdo = db();
    if (!$pdo || $userId <= 0 || $password === '') {
        return ['ok' => false, 'error' => 'Enter your password to continue.'];
    }
    $stmt = $pdo->prepare('SELECT password_hash FROM ' . smsPasskeyUsersTableSql() . ' WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $hash = (string) ($stmt->fetchColumn() ?: '');
    if ($hash === '' || !password_verify($password, $hash)) {
        $gate = smsRegisterCodeFailure($userId, $gatePurpose);
        return ['ok' => false, 'error' => smsCodeFailureMessage($gate, 'password') ?: 'Incorrect password.'];
    }
    smsClearCodeGate($userId, $gatePurpose);
    return ['ok' => true, 'error' => ''];
}
function smsPasskeysForUser(int $userId): array
{
    smsEnsurePasskeyTable();
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return [];
    }
    $stmt = $pdo->prepare(
        'SELECT id, credential_id, device_name, sign_count, created_at, last_used_at
         FROM ' . smsPasskeyTableSql() . ' WHERE user_id = ? ORDER BY id DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll() ?: [];
}

function smsPasskeyCount(int $userId): int
{
    smsEnsurePasskeyTable();
    $pdo = db();
    if (!$pdo || $userId <= 0) {
        return 0;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM ' . smsPasskeyTableSql() . ' WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function smsPasskeyDelete(int $userId, int $passkeyId): bool
{
    smsEnsurePasskeyTable();
    $pdo = db();
    if (!$pdo || $userId <= 0 || $passkeyId <= 0) {
        return false;
    }
    // Always delete exactly one row by id + owner — never wipe all passkeys.
    $stmt = $pdo->prepare('DELETE FROM ' . smsPasskeyTableSql() . ' WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$passkeyId, $userId]);
    return $stmt->rowCount() > 0;
}

/**
 * @return array<string,mixed>
 */
function smsPasskeyRegisterOptions(int $userId): array
{
    smsEnsurePasskeyTable();
    if (!smsPasskeyStepUpOk($userId)) {
        throw new RuntimeException('Verify your identity before adding a passkey.');
    }
    $pdo = db();
    $user = null;
    if ($pdo) {
        $st = $pdo->prepare('SELECT id, email, full_name, username FROM ' . smsPasskeyUsersTableSql() . ' WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $user = $st->fetch() ?: null;
    }
    if (!$user) {
        throw new RuntimeException('Account not found.');
    }

    $challenge = random_bytes(32);
    $_SESSION['passkey_reg_challenge'] = smsB64UrlEncode($challenge);
    $_SESSION['passkey_reg_user'] = $userId;
    $_SESSION['passkey_reg_at'] = time();

    $exclude = [];
    foreach (smsPasskeysForUser($userId) as $pk) {
        $cid = smsPasskeyNormalizeCredentialId((string) ($pk['credential_id'] ?? ''));
        if ($cid === '') {
            continue;
        }
        $exclude[] = [
            'type' => 'public-key',
            'id' => $cid,
        ];
    }

    $userHandle = pack('N', (int) $user['id']);
    $display = (string) ($user['email'] ?: $user['username'] ?: $user['full_name']);

    return [
        'challenge' => smsB64UrlEncode($challenge),
        'rp' => [
            'name' => smsPasskeyRpName(),
            'id' => smsPasskeyRpId(),
        ],
        'user' => [
            'id' => smsB64UrlEncode($userHandle),
            'name' => $display,
            'displayName' => (string) ($user['full_name'] ?: $display),
        ],
        'pubKeyCredParams' => [
            ['type' => 'public-key', 'alg' => -7],   // ES256
            ['type' => 'public-key', 'alg' => -257], // RS256
        ],
        'timeout' => 180000,
        'attestation' => 'none',
        'excludeCredentials' => $exclude,
        'authenticatorSelection' => [
            // Discoverable when the device supports it (login without typing email)
            'residentKey' => 'preferred',
            'userVerification' => 'preferred',
        ],
    ];
}

/**
 * @param array<string,mixed> $cred
 */
function smsPasskeyRegisterVerify(int $userId, array $cred, string $deviceName = 'Passkey'): array
{
    smsEnsurePasskeyTable();
    if (!smsPasskeyStepUpOk($userId)) {
        return ['ok' => false, 'error' => 'Verify your identity before adding a passkey.'];
    }
    if (
        (int) ($_SESSION['passkey_reg_user'] ?? 0) !== $userId
        || empty($_SESSION['passkey_reg_challenge'])
        || empty($_SESSION['passkey_reg_at'])
        || ((int) $_SESSION['passkey_reg_at'] + 300) < time()
    ) {
        return ['ok' => false, 'error' => 'Passkey setup expired. Try again.'];
    }

    $challenge = (string) $_SESSION['passkey_reg_challenge'];
    $credId = smsPasskeyNormalizeCredentialId((string) ($cred['id'] ?? ''));
    $clientDataB64 = (string) ($cred['clientDataJSON'] ?? '');
    $publicKeyB64 = (string) ($cred['publicKey'] ?? '');

    if ($credId === '' || $clientDataB64 === '' || $publicKeyB64 === '') {
        return ['ok' => false, 'error' => 'Incomplete passkey response. Use Chrome, Edge, or Safari.'];
    }

    $clientDataRaw = smsB64UrlDecode($clientDataB64);
    $clientData = json_decode($clientDataRaw, true);
    if (!is_array($clientData)) {
        return ['ok' => false, 'error' => 'Invalid client data.'];
    }
    if (($clientData['type'] ?? '') !== 'webauthn.create') {
        return ['ok' => false, 'error' => 'Unexpected ceremony type.'];
    }
    if (($clientData['challenge'] ?? '') !== $challenge) {
        return ['ok' => false, 'error' => 'Challenge mismatch.'];
    }
    if (($clientData['origin'] ?? '') === '' || !smsPasskeyOriginAllowed((string) $clientData['origin'])) {
        return ['ok' => false, 'error' => 'Origin mismatch. Open the site at the same address… (same address you used to add the passkey).'];
    }

    $pubDer = smsB64UrlDecode($publicKeyB64);
    if ($pubDer === '') {
        return ['ok' => false, 'error' => 'Missing public key.'];
    }
    $pem = "-----BEGIN PUBLIC KEY-----\n"
        . chunk_split(base64_encode($pubDer), 64, "\n")
        . "-----END PUBLIC KEY-----";
    $res = openssl_pkey_get_public($pem);
    if ($res === false) {
        return ['ok' => false, 'error' => 'Could not parse passkey public key.'];
    }

    $pdo = db();
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database unavailable.'];
    }

    $name = trim($deviceName) !== '' ? substr(trim($deviceName), 0, 120) : 'Passkey';
    try {
        $pdo->prepare(
            'INSERT INTO ' . smsPasskeyTableSql() . ' (user_id, credential_id, public_key, sign_count, device_name)
             VALUES (?, ?, ?, 0, ?)'
        )->execute([$userId, $credId, $pem, $name]);
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        error_log('SMS2 passkey register insert failed: ' . $msg);
        if (stripos($msg, 'Duplicate') !== false || (string) $e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'This passkey is already registered.'];
        }
        if (stripos($msg, "doesn't have a default value") !== false || stripos($msg, '1364') !== false) {
            smsPasskeyEnsureSchema($pdo);
            try {
                $pdo->prepare(
                    'INSERT INTO ' . smsPasskeyTableSql() . ' (user_id, credential_id, public_key, sign_count, device_name)
                     VALUES (?, ?, ?, 0, ?)'
                )->execute([$userId, $credId, $pem, $name]);
            } catch (Throwable $e2) {
                error_log('SMS2 passkey register retry failed: ' . $e2->getMessage());
                return ['ok' => false, 'error' => 'Could not save passkey (database id column). Ask an admin to run the passkey AUTO_INCREMENT patch.'];
            }
        } else {
            return ['ok' => false, 'error' => 'Could not save passkey. Please try again.'];
        }
    }

    unset($_SESSION['passkey_reg_challenge'], $_SESSION['passkey_reg_user'], $_SESSION['passkey_reg_at']);
    smsPasskeyStepUpClear();
    logActivity('update', 'Added passkey: ' . $name, 'System', $userId);
    return ['ok' => true];
}

/**
 * @return array<string,mixed>
 */
function smsPasskeyLoginOptions(?string $username = null): array
{
    smsEnsurePasskeyTable();
    $challenge = random_bytes(32);
    $_SESSION['passkey_login_challenge'] = smsB64UrlEncode($challenge);
    $_SESSION['passkey_login_at'] = time();
    $_SESSION['passkey_login_user_hint'] = $username !== null ? trim($username) : '';
    unset($_SESSION['passkey_login_user_id']);

    $opts = [
        'challenge' => smsB64UrlEncode($challenge),
        'timeout' => 180000,
        'rpId' => smsPasskeyRpId(),
        'userVerification' => 'preferred',
    ];

    $username = trim((string) $username);
    if ($username !== '') {
        $user = smsFindUserByLogin($username);
        if ($user) {
            $_SESSION['passkey_login_user_id'] = (int) $user['id'];
            $allow = [];
            foreach (smsPasskeysForUser((int) $user['id']) as $pk) {
                $cid = smsPasskeyNormalizeCredentialId((string) ($pk['credential_id'] ?? ''));
                if ($cid === '') {
                    continue;
                }
                $allow[] = [
                    'type' => 'public-key',
                    'id' => $cid,
                    'transports' => ['internal', 'hybrid', 'usb', 'nfc', 'ble'],
                ];
            }
            if ($allow !== []) {
                $opts['allowCredentials'] = $allow;
            }
        }
    }

    // No email / no allowCredentials → browser shows saved passkeys for this site (discoverable).
    return $opts;
}

/**
 * @param array<string,mixed> $cred
 * @return array{ok:bool,error?:string,user?:array<string,mixed>}
 */
function smsPasskeyLoginVerify(array $cred): array
{
    smsEnsurePasskeyTable();
    if (
        empty($_SESSION['passkey_login_challenge'])
        || empty($_SESSION['passkey_login_at'])
        || ((int) $_SESSION['passkey_login_at'] + 300) < time()
    ) {
        return ['ok' => false, 'error' => 'Passkey login expired. Try again.'];
    }

    $challenge = (string) $_SESSION['passkey_login_challenge'];
    $credId = smsPasskeyNormalizeCredentialId((string) ($cred['id'] ?? ''));
    $clientDataB64 = (string) ($cred['clientDataJSON'] ?? '');
    $authDataB64 = (string) ($cred['authenticatorData'] ?? '');
    $sigB64 = (string) ($cred['signature'] ?? '');

    if ($credId === '' || $clientDataB64 === '' || $authDataB64 === '' || $sigB64 === '') {
        return ['ok' => false, 'error' => 'Incomplete passkey assertion.'];
    }

    $pdo = db();
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database unavailable.'];
    }
    $pk = null;
    $keys = smsPasskeyCredentialIdLookupKeys($credId);
    if ($keys !== []) {
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $pdo->prepare('SELECT * FROM ' . smsPasskeyTableSql() . ' WHERE credential_id IN (' . $placeholders . ') LIMIT 1');
        $stmt->execute($keys);
        $pk = $stmt->fetch() ?: null;
    }
    if (!$pk) {
        return ['ok' => false, 'error' => 'Unknown passkey.'];
    }
    $storedId = (string) ($pk['credential_id'] ?? '');
    if ($storedId !== '' && $storedId !== $credId) {
        try {
            $pdo->prepare('UPDATE ' . smsPasskeyTableSql() . ' SET credential_id = ? WHERE id = ? LIMIT 1')->execute([$credId, (int) $pk['id']]);
            $pk['credential_id'] = $credId;
        } catch (Throwable $e) {
            error_log('SMS2 passkey credential_id normalize skipped: ' . $e->getMessage());
        }
    }

    $clientDataRaw = smsB64UrlDecode($clientDataB64);
    $clientData = json_decode($clientDataRaw, true);
    if (!is_array($clientData)) {
        return ['ok' => false, 'error' => 'Invalid client data.'];
    }
    if (($clientData['type'] ?? '') !== 'webauthn.get') {
        return ['ok' => false, 'error' => 'Unexpected ceremony type.'];
    }
    if (($clientData['challenge'] ?? '') !== $challenge) {
        return ['ok' => false, 'error' => 'Challenge mismatch.'];
    }
    if (($clientData['origin'] ?? '') === '' || !smsPasskeyOriginAllowed((string) $clientData['origin'])) {
        return ['ok' => false, 'error' => 'Origin mismatch. Open the site at the same address… (same address you used to add the passkey).'];
    }

    $authData = smsB64UrlDecode($authDataB64);
    $signature = smsB64UrlDecode($sigB64);
    if ($authData === '' || $signature === '') {
        return ['ok' => false, 'error' => 'Invalid authenticator data.'];
    }

    // rpIdHash (first 32 bytes) must match SHA-256 of rpId used at create time
    $rpHash = substr($authData, 0, 32);
    $rpId = smsPasskeyRpId();
    $rpOk = hash_equals(hash('sha256', $rpId, true), $rpHash);
    if (!$rpOk) {
        // Allow loopback alias only if hash matches the other name
        foreach (['localhost', '127.0.0.1'] as $alt) {
            if (hash_equals(hash('sha256', $alt, true), $rpHash)) {
                $rpOk = true;
                break;
            }
        }
    }
    if (!$rpOk) {
        return ['ok' => false, 'error' => 'Relying party mismatch. Use the same site address (localhost vs 127.0.0.1) as when you added the passkey.'];
    }
    $flags = ord($authData[32] ?? "\0");
    if (($flags & 0x01) !== 0x01) { // user present
        return ['ok' => false, 'error' => 'User presence required.'];
    }

    $clientHash = hash('sha256', $clientDataRaw, true);
    $signed = $authData . $clientHash;
    $pem = (string) $pk['public_key'];

    $ok = openssl_verify($signed, $signature, $pem, OPENSSL_ALGO_SHA256);
    if ($ok !== 1) {
        // Some authenticators return IEEE P1363 ECDSA; try converting to DER
        $derSig = smsEcdsaP1363ToDer($signature);
        if ($derSig !== null) {
            $ok = openssl_verify($signed, $derSig, $pem, OPENSSL_ALGO_SHA256);
        }
    }
    if ($ok !== 1) {
        return ['ok' => false, 'error' => 'Passkey signature verification failed.'];
    }

    // signCount is bytes 33..36 (big-endian) after flags
    $countBin = substr($authData, 33, 4);
    $newCount = $countBin !== false && strlen($countBin) === 4 ? unpack('N', $countBin)[1] : 0;
    $oldCount = (int) $pk['sign_count'];
    if ($newCount > 0 && $oldCount > 0 && $newCount <= $oldCount) {
        return ['ok' => false, 'error' => 'Passkey may have been cloned. Contact admin.'];
    }

    $pdo->prepare(
        'UPDATE ' . smsPasskeyTableSql() . ' SET sign_count = ?, last_used_at = NOW() WHERE id = ?'
    )->execute([max($newCount, $oldCount), (int) $pk['id']]);

    $ust = $pdo->prepare(
        'SELECT u.*, r.label AS role_label
         FROM ' . smsPasskeyUsersTableSql() . ' u
         LEFT JOIN `sms2_roles` r ON r.role_key = u.role_key
         WHERE u.id = ? LIMIT 1'
    );
    $ust->execute([(int) $pk['user_id']]);
    $user = $ust->fetch() ?: null;
    if ($user && ($user['role_label'] ?? '') === '') {
        $user['role_label'] = (string) ($user['role_key'] ?? '');
    }
    if (!$user || (string) ($user['status'] ?? '') !== 'active') {
        return ['ok' => false, 'error' => 'Account is not available.'];
    }

    unset($_SESSION['passkey_login_challenge'], $_SESSION['passkey_login_at'], $_SESSION['passkey_login_user_hint']);
    return ['ok' => true, 'user' => $user];
}

/**
 * Convert IEEE P1363 (r||s) ECDSA signature to DER for OpenSSL.
 */
function smsEcdsaP1363ToDer(string $sig): ?string
{
    $len = strlen($sig);
    if ($len === 0 || $len % 2 !== 0) {
        return null;
    }
    $half = (int) ($len / 2);
    $r = ltrim(substr($sig, 0, $half), "\0");
    $s = ltrim(substr($sig, $half), "\0");
    if ($r === '') {
        $r = "\0";
    }
    if ($s === '') {
        $s = "\0";
    }
    if ((ord($r[0]) & 0x80) !== 0) {
        $r = "\0" . $r;
    }
    if ((ord($s[0]) & 0x80) !== 0) {
        $s = "\0" . $s;
    }
    $encodeInt = static function (string $x): string {
        return "\x02" . chr(strlen($x)) . $x;
    };
    $seq = $encodeInt($r) . $encodeInt($s);
    return "\x30" . chr(strlen($seq)) . $seq;
}

/**
 * Preferred step-up method before removing a passkey.
 * Priority: Authenticator (if on) → email OTP (if usable Gmail/email) → password.
 *
 * @return array{method:string,label:string,email:string,email_masked:string}
 */
function smsPasskeyRemoveMethod(int $userId): array
{
    require_once __DIR__ . '/totp.php';

    $auth = smsAuthenticatorGet($userId);
    if ($auth && !empty($auth['enabled'])) {
        return [
            'method' => 'authenticator',
            'label' => 'Authenticator',
            'email' => '',
            'email_masked' => '',
        ];
    }

    $pdo = db();
    $email = '';
    if ($pdo && $userId > 0) {
        $stmt = $pdo->prepare('SELECT email FROM ' . smsPasskeyUsersTableSql() . ' WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $email = trim((string) ($stmt->fetchColumn() ?: ''));
    }

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $masked = smsPasskeyMaskEmail($email);
        return [
            'method' => 'email',
            'label' => 'Email code',
            'email' => $email,
            'email_masked' => $masked,
        ];
    }

    return [
        'method' => 'password',
        'label' => 'Password',
        'email' => '',
        'email_masked' => '',
    ];
}

function smsPasskeyMaskEmail(string $email): string
{
    $parts = explode('@', $email, 2);
    if (count($parts) !== 2) {
        return '***';
    }
    $local = $parts[0];
    $domain = $parts[1];
    $keep = max(1, min(2, (int) floor(strlen($local) / 3)));
    $stars = max(3, strlen($local) - $keep);
    return substr($local, 0, $keep) . str_repeat('*', $stars) . '@' . $domain;
}

/**
 * Verify proof for passkey removal.
 *
 * @return array{ok:bool,error:string}
 */
function smsPasskeyVerifyRemoveProof(int $userId, string $method, array $body): array
{
    require_once __DIR__ . '/totp.php';
    require_once __DIR__ . '/security-workflow.php';

    $expected = smsPasskeyRemoveMethod($userId);
    if ($method !== $expected['method']) {
        return ['ok' => false, 'error' => 'Verification method changed. Refresh and try again.'];
    }

    if ($method === 'authenticator') {
        $code = trim((string) ($body['totp_code'] ?? $body['code'] ?? ''));
        if ($code === '' || !smsAuthenticatorVerifyLogin($userId, $code)) {
            return ['ok' => false, 'error' => 'Invalid Authenticator code.'];
        }
        return ['ok' => true, 'error' => ''];
    }

    if ($method === 'email') {
        $code = trim((string) ($body['otp_code'] ?? $body['code'] ?? ''));
        if ($code === '' || !smsVerifyOtp($userId, 'passkey_remove', $code)) {
            return ['ok' => false, 'error' => 'Invalid or expired email code.'];
        }
        return ['ok' => true, 'error' => ''];
    }

    // password
    $password = (string) ($body['password'] ?? '');
    $pdo = db();
    if (!$pdo || $userId <= 0 || $password === '') {
        return ['ok' => false, 'error' => 'Enter your password to continue.'];
    }
    $stmt = $pdo->prepare('SELECT password_hash FROM ' . smsPasskeyUsersTableSql() . ' WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $hash = (string) ($stmt->fetchColumn() ?: '');
    if ($hash === '' || !password_verify($password, $hash)) {
        return ['ok' => false, 'error' => 'Incorrect password.'];
    }

    return ['ok' => true, 'error' => ''];
}

function smsRenderPasskeyCard(int $userId, string $csrfToken, bool $asBox = false): void
{
    require_once __DIR__ . '/security-ui.php';
    $keys = smsPasskeysForUser($userId);
    $api = BASE_URL . '/api/passkey.php';
    $methodInfo = smsPasskeyRemoveMethod($userId);
    $badge = '<span class="badge ' . ($keys ? 'text-bg-success' : 'text-bg-secondary') . '">'
        . ($keys ? count($keys) . ' saved' : 'None') . '</span>';
    $stepUp = smsPasskeyStepUpMethod($userId);
    echo '<div id="smsPasskeyCard" class="' . ($asBox ? 'h-100' : '') . '" data-passkey-api="' . e($api) . '" data-csrf="' . e($csrfToken) . '"'
        . ' data-remove-method="' . e($methodInfo['method']) . '"'
        . ' data-add-method="' . e($stepUp['method']) . '">';
    echo $asBox
        ? smsSecBoxStart('Passkey', 'fa-fingerprint', $badge)
        : smsSecCardStart('Passkey', 'fa-fingerprint', $badge);
    ?>
            <p class="sms-sec-lead">
                Sign in faster with Windows Hello, Face ID, fingerprint, or a phone passkey — no password needed.
                Use the same site address for register and sign-in (localhost locally, or this portal host in production). Needs a current Chrome, Edge, or Safari.
            </p>
            <p class="small text-muted mb-3">
                Adding or removing a passkey requires a security check:
                <?php if ($stepUp['method'] === 'authenticator'): ?>
                    <strong>Authenticator code</strong> (Authenticator is on).
                <?php elseif ($stepUp['method'] === 'email'): ?>
                    <strong>email code</strong> to <?= e($stepUp['email_masked']) ?>.
                <?php else: ?>
                    <strong>your password</strong> (no Authenticator or email on this account).
                <?php endif; ?>
            </p>
            <div id="smsPasskeyMsg" class="small mb-2" hidden></div>
            <button type="button" class="sms-sec-btn sms-sec-btn-primary" id="smsPasskeyAdd">
                <?= smsIcon('plus', ['aria-hidden' => 'true']) ?>Add passkey
            </button>
            <?php if ($keys): ?>
                <div class="sms-sec-list table-responsive mt-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Added</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($keys as $pk): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e((string) $pk['device_name']) ?></td>
                                    <td class="small text-muted"><?= e(date('M j, Y', strtotime((string) $pk['created_at']))) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="sms-sec-btn sms-sec-btn-danger sms-passkey-remove"
                                                data-id="<?= (int) $pk['id'] ?>"
                                                data-name="<?= e((string) $pk['device_name']) ?>">
                                            <?= smsIcon('trash-alt', ['aria-hidden' => 'true']) ?>Remove
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
    <?= $asBox ? smsSecBoxEnd() : smsSecCardEnd() ?>

    
    <div class="modal fade sms-confirm-modal" id="smsPasskeyAddModal" tabindex="-1" aria-labelledby="smsPasskeyAddTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered sms-confirm-dialog sms-confirm-dialog--wide">
            <div class="modal-content sms-confirm-content">
                <div class="sms-confirm-header">
                    <div class="sms-confirm-header-text">
                        <span class="sms-confirm-kicker">Passkey</span>
                        <h5 class="sms-confirm-title" id="smsPasskeyAddTitle">Verify to add a passkey</h5>
                    </div>
                    <button type="button" class="sms-confirm-close" data-bs-dismiss="modal" aria-label="Close"><span class="sms-confirm-close__glyph" aria-hidden="true">&times;</span></button>
                </div>
                <div class="sms-confirm-body sms-confirm-body--form">
                    <div class="sms-confirm-icon sms-confirm-icon--info" aria-hidden="true">
                        <?= smsIcon('shield-check') ?>
                    </div>
                    <p class="sms-confirm-msg mb-0" id="smsPasskeyAddLead">Confirm it is you before creating a new passkey.</p>
                    <div id="smsPasskeyAddErr" class="sms-confirm-notice sms-confirm-notice--danger w-100" hidden></div>
                    <div id="smsPasskeyAddInfo" class="sms-confirm-notice sms-confirm-notice--info w-100" hidden></div>
                    <div class="sms-confirm-form w-100">
                    <div id="smsPkAddVerifyAuthenticator" class="sms-pk-verify w-100" hidden>
                        <label class="sms-confirm-label" for="smsPkAddTotp"><?= smsIcon('shield-lock') ?>Authenticator code</label>
                        <input type="text" class="form-control sms-confirm-input" id="smsPkAddTotp" inputmode="numeric" maxlength="6" pattern="\d{6}" autocomplete="one-time-code" placeholder="000000">
                    </div>
                    <div id="smsPkAddVerifyEmail" class="sms-pk-verify w-100" hidden>
                        <label class="sms-confirm-label" for="smsPkAddOtp"><?= smsIcon('mail') ?>Email code</label>
                        <input type="text" class="form-control sms-confirm-input" id="smsPkAddOtp" inputmode="numeric" maxlength="6" pattern="\d{6}" autocomplete="one-time-code" placeholder="000000">
                        <button type="button" class="btn btn-link btn-sm px-0 mt-2 sms-confirm-link" id="smsPkAddResendEmail"><?= smsIcon('refresh', ['class' => 'me-1']) ?>Resend email code</button>
                    </div>
                    <div id="smsPkAddVerifyPassword" class="sms-pk-verify w-100" hidden>
                        <label class="sms-confirm-label" for="smsPkAddPassword"><?= smsIcon('lock') ?>Password</label>
                        <div class="sms-pw-group password-group">
                            <input type="password" class="form-control sms-confirm-input" id="smsPkAddPassword" autocomplete="new-password" placeholder="Enter your password">
                            <button class="password-toggle sms-pw-toggle" type="button" data-pw-target="smsPkAddPassword" aria-label="Show password" title="Show password" aria-pressed="false">
                                <?= smsIcon('eye', ['aria-hidden' => 'true']) ?>
                            </button>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="sms-confirm-footer">
                    <button type="button" class="btn btn-outline-secondary sms-confirm-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn sms-confirm-ok sms-confirm-ok--primary" id="smsPasskeyAddConfirm">
                        <?= smsIcon('check', ['class' => 'me-1', 'aria-hidden' => 'true']) ?>Continue
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade sms-confirm-modal" id="smsPasskeyRemoveModal" tabindex="-1" aria-labelledby="smsPasskeyRemoveTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered sms-confirm-dialog sms-confirm-dialog--wide">
            <div class="modal-content sms-confirm-content">
                <div class="sms-confirm-header">
                    <div class="sms-confirm-header-text">
                        <span class="sms-confirm-kicker">Passkey</span>
                        <h5 class="sms-confirm-title" id="smsPasskeyRemoveTitle">Remove passkey?</h5>
                    </div>
                    <button type="button" class="sms-confirm-close" data-bs-dismiss="modal" aria-label="Close"><span class="sms-confirm-close__glyph" aria-hidden="true">×</span></button>
                </div>
                <div class="sms-confirm-body sms-confirm-body--form">
                    <div class="sms-confirm-icon sms-confirm-icon--danger" aria-hidden="true">
                        <?= smsIcon('trash') ?>
                    </div>
                    <p class="sms-confirm-msg mb-0" id="smsPasskeyRemoveLead">Verify your identity to remove this passkey.</p>
                    <div id="smsPasskeyRemoveErr" class="sms-confirm-notice sms-confirm-notice--danger w-100" hidden></div>
                    <div id="smsPasskeyRemoveInfo" class="sms-confirm-notice sms-confirm-notice--info w-100" hidden></div>

                    <div class="sms-confirm-form w-100">
                    <div id="smsPkVerifyAuthenticator" class="sms-pk-verify w-100" hidden>
                        <label class="sms-confirm-label" for="smsPkTotp"><?= smsIcon('shield-lock') ?>Authenticator code</label>
                        <input type="text" class="form-control sms-confirm-input" id="smsPkTotp" inputmode="numeric" maxlength="6"
                               pattern="\d{6}" autocomplete="one-time-code" placeholder="000000">
                    </div>
                    <div id="smsPkVerifyEmail" class="sms-pk-verify w-100" hidden>
                        <label class="sms-confirm-label" for="smsPkOtp"><?= smsIcon('mail') ?>Email code</label>
                        <input type="text" class="form-control sms-confirm-input" id="smsPkOtp" inputmode="numeric" maxlength="6"
                               pattern="\d{6}" autocomplete="one-time-code" placeholder="000000">
                        <button type="button" class="btn btn-link btn-sm px-0 mt-2 sms-confirm-link" id="smsPkResendEmail"><?= smsIcon('refresh', ['class' => 'me-1']) ?>Resend email code</button>
                    </div>
                    <div id="smsPkVerifyPassword" class="sms-pk-verify w-100" hidden>
                        <label class="sms-confirm-label" for="smsPkPassword"><?= smsIcon('lock') ?>Password</label>
                        <div class="sms-pw-group password-group">
                            <input type="password" class="form-control sms-confirm-input" id="smsPkPassword" autocomplete="new-password"
                                   placeholder="Enter your password">
                            <button class="password-toggle sms-pw-toggle" type="button" data-pw-target="smsPkPassword"
                                    aria-label="Show password" title="Show password" aria-pressed="false">
                                <?= smsIcon('eye', ['aria-hidden' => 'true']) ?>
                            </button>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="sms-confirm-footer">
                    <button type="button" class="btn btn-outline-secondary sms-confirm-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn sms-confirm-ok sms-confirm-ok--danger" id="smsPasskeyRemoveConfirm">
                        <?= smsIcon('trash', ['class' => 'me-1', 'aria-hidden' => 'true']) ?>Yes, remove
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/passkey.js?v=12"></script>
    <?php
}
