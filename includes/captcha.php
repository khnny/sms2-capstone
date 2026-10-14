<?php
/**
 * SMS 2 – Login CAPTCHA (one-click)
 *
 * 1) Cloudflare Turnstile when keys are set (real API / one click)
 * 2) Local one-click checkbox (Discord / Cloudflare-style) for XAMPP / no keys
 *
 * Checked BEFORE password auth / 2FA.
 */
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

function smsCaptchaEnabled(): bool
{
    return smsSetting('login_captcha_enabled', '1') === '1';
}

function smsCaptchaProvider(): string
{
    $site = smsCaptchaSiteKey();
    $secret = smsCaptchaSecretKey();
    if ($site !== '' && $secret !== '') {
        return 'turnstile';
    }
    return 'local';
}

function smsCaptchaSiteKey(): string
{
    // Environment values are intentionally preferred in production. On
    // container hosts, storage/keys may be recreated on deployment, making a
    // database-encrypted secret unreadable after a redeploy.
    return smsCaptchaEnvironmentValue('TURNSTILE_SITE_KEY')
        ?: trim(smsSetting('turnstile_site_key', ''));
}

function smsCaptchaSecretKey(): string
{
    return smsCaptchaEnvironmentValue('TURNSTILE_SECRET_KEY')
        ?: trim(smsSetting('turnstile_secret_key', ''));
}

/** Read a production CAPTCHA value without placing a secret in source code. */
function smsCaptchaEnvironmentValue(string $name): string
{
    $keys = ['SMS2_' . $name, $name];
    foreach ($keys as $key) {
        $value = defined($key)
            ? constant($key)
            : (function_exists('sms2_env')
                ? sms2_env($key)
                : (getenv($key) !== false ? (string) getenv($key) : null));
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }

    return '';
}

/**
 * Issue a one-click challenge token into the session.
 * @return array{token:string}
 */
function smsCaptchaLocalIssue(): array
{
    $token = bin2hex(random_bytes(16));
    $_SESSION['sms_captcha_local'] = [
        'token' => $token,
        'at' => time(),
        'verified' => 0,
    ];
    return ['token' => $token];
}

/**
 * Mark local captcha as clicked (called from tiny JSON endpoint or same-request).
 */
function smsCaptchaLocalMarkVerified(string $token): bool
{
    $stored = $_SESSION['sms_captcha_local'] ?? null;
    if (!is_array($stored) || empty($stored['token'])) {
        return false;
    }
    if (!hash_equals((string) $stored['token'], $token)) {
        return false;
    }
    if (((int) ($stored['at'] ?? 0) + 600) < time()) {
        return false;
    }
    // Require a short human pause (blocks instant scripted submits)
    if ((time() - (int) $stored['at']) < 1) {
        return false;
    }
    $_SESSION['sms_captcha_local']['verified'] = 1;
    $_SESSION['sms_captcha_local']['verified_at'] = time();
    return true;
}

/**
 * @return array{ok:bool,error:string}
 */
function smsCaptchaVerifyRequest(): array
{
    if (!smsCaptchaEnabled()) {
        return ['ok' => true, 'error' => ''];
    }

    // Honeypot — real users never fill this
    $hp = trim((string) ($_POST['captcha_hp'] ?? ''));
    if ($hp !== '') {
        return ['ok' => false, 'error' => 'CAPTCHA check failed. Please try again.'];
    }

    $provider = smsCaptchaProvider();

    if ($provider === 'turnstile') {
        $token = trim((string) ($_POST['cf-turnstile-response'] ?? ''));
        if ($token === '') {
            return ['ok' => false, 'error' => 'Please complete the CAPTCHA (one click) before signing in.'];
        }
        return smsCaptchaVerifyTurnstile($token);
    }

    // Local one-click checkbox
    $token = trim((string) ($_POST['captcha_token'] ?? ''));
    $clicked = trim((string) ($_POST['captcha_ok'] ?? ''));
    $stored = $_SESSION['sms_captcha_local'] ?? null;
    unset($_SESSION['sms_captcha_local']);

    if (!is_array($stored) || empty($stored['token'])) {
        return ['ok' => false, 'error' => 'CAPTCHA expired. Please refresh and try again.'];
    }
    if (((int) ($stored['at'] ?? 0) + 600) < time()) {
        return ['ok' => false, 'error' => 'CAPTCHA expired. Please refresh and try again.'];
    }
    if (!hash_equals((string) $stored['token'], $token)) {
        return ['ok' => false, 'error' => 'CAPTCHA check failed. Please try again.'];
    }
    if ($clicked !== '1' && empty($stored['verified'])) {
        return ['ok' => false, 'error' => 'Please click “Verify you are human” before signing in.'];
    }
    // Prefer session verified flag from click handler; also accept same-request click + delay
    if (!empty($stored['verified'])) {
        return ['ok' => true, 'error' => ''];
    }
    if ($clicked === '1' && (time() - (int) $stored['at']) >= 1) {
        return ['ok' => true, 'error' => ''];
    }

    return ['ok' => false, 'error' => 'Please click “Verify you are human” before signing in.'];
}

/**
 * Verify Turnstile token via Cloudflare siteverify API.
 * @return array{ok:bool,error:string}
 */
function smsCaptchaVerifyTurnstile(string $token): array
{
    $secret = smsCaptchaSecretKey();
    $site = smsCaptchaSiteKey();
    if ($secret === '') {
        return ['ok' => false, 'error' => 'CAPTCHA is not configured.'];
    }
    if ($site !== '' && hash_equals($site, $secret)) {
        return [
            'ok' => false,
            'error' => 'CAPTCHA misconfigured: Site Key and Secret Key are the same. Paste the Secret Key from Cloudflare Turnstile (different from the Site Key).',
        ];
    }

    $payload = http_build_query([
        'secret' => $secret,
        'response' => $token,
    ]);

    $raw = '';
    $curlErr = '';
    if (function_exists('curl_init')) {
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $raw = (string) curl_exec($ch);
        $curlErr = (string) curl_error($ch);
        curl_close($ch);
        if ($raw === '' && $curlErr !== '') {
            return ['ok' => false, 'error' => 'Could not reach Cloudflare CAPTCHA service. Check internet / PHP curl SSL.'];
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 12,
            ],
        ]);
        $raw = (string) @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
        if ($raw === '') {
            return ['ok' => false, 'error' => 'Could not reach Cloudflare CAPTCHA service. Try again.'];
        }
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'CAPTCHA verification failed. Please try again.'];
    }
    if (!empty($data['success'])) {
        return ['ok' => true, 'error' => ''];
    }

    $codes = [];
    if (!empty($data['error-codes']) && is_array($data['error-codes'])) {
        foreach ($data['error-codes'] as $c) {
            $codes[] = strtolower(trim((string) $c));
        }
    }

    if (in_array('invalid-input-secret', $codes, true) || in_array('missing-input-secret', $codes, true)) {
        return [
            'ok' => false,
            'error' => 'CAPTCHA Secret Key is invalid. In System Settings, paste the Secret Key from Cloudflare Turnstile (not the Site Key), then Save.',
        ];
    }
    if (in_array('timeout-or-duplicate', $codes, true)) {
        return ['ok' => false, 'error' => 'CAPTCHA expired or already used. Click the security check again, then Sign In.'];
    }
    if (in_array('hostname-mismatch', $codes, true)) {
        return [
            'ok' => false,
            'error' => 'CAPTCHA hostname mismatch. In Cloudflare Turnstile, add hostname localhost (and 127.0.0.1).',
        ];
    }
    if (in_array('invalid-input-response', $codes, true) || in_array('missing-input-response', $codes, true)) {
        return ['ok' => false, 'error' => 'Please complete the security check, then Sign In.'];
    }

    return ['ok' => false, 'error' => 'CAPTCHA verification failed. Please complete the check again and try Sign In.'];
}

/**
 * Render one-click CAPTCHA widget HTML for login form.
 */
function smsCaptchaMarkup(): string
{
    if (!smsCaptchaEnabled()) {
        return '';
    }

    // Shared honeypot (bots often fill every field)
    $honeypot = '<label class="sms-captcha-hp" aria-hidden="true">'
        . '<span>Leave blank</span>'
        . '<input type="text" name="captcha_hp" value="" tabindex="-1" autocomplete="off">'
        . '</label>';

    if (smsCaptchaProvider() === 'turnstile') {
        $siteKey = e(smsCaptchaSiteKey());
        return '<div class="mb-3 sms-captcha-wrap">'
            . $honeypot
            . '<label class="form-label fw-medium sms-captcha-label">Security check</label>'
            . '<div class="sms-captcha-frame sms-captcha-frame--turnstile">'
            . '<div class="cf-turnstile" data-sitekey="' . $siteKey
            . '" data-theme="light" data-size="normal" data-appearance="always"'
            . ' data-callback="smsTurnstileOk" data-expired-callback="smsTurnstileReset"'
            . ' data-error-callback="smsTurnstileReset" data-timeout-callback="smsTurnstileReset"></div>'
            . '</div>'
            . '</div>'
            . '<script>'
            . 'window.smsTurnstileOk=function(){try{document.dispatchEvent(new Event("sms-captcha-ok",{bubbles:true}));}catch(e){}};'
            . 'window.smsTurnstileReset=function(){try{document.dispatchEvent(new Event("sms-captcha-reset",{bubbles:true}));}catch(e){}};'
            . '</script>'
            . '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }

    $local = smsCaptchaLocalIssue();
    $token = e($local['token']);
    $api = e(BASE_URL . '/api/captcha.php');

    return '<div class="mb-3 sms-captcha-wrap">'
        . $honeypot
        . '<label class="form-label fw-medium sms-captcha-label" for="smsCaptchaWidget">Security check</label>'
        . '<input type="hidden" name="captcha_token" id="smsCaptchaToken" value="' . $token . '">'
        . '<input type="hidden" name="captcha_ok" id="smsCaptchaOk" value="0">'
        . '<button type="button" class="sms-cf-widget" id="smsCaptchaWidget" '
        . 'data-captcha-api="' . $api . '" aria-pressed="false" aria-label="Verify you are human">'
        . '<span class="sms-cf-box" id="smsCaptchaBox" aria-hidden="true">'
        . '<span class="sms-cf-spinner" hidden></span>'
        . '<span class="sms-cf-check" hidden>' . smsIcon('check') . '</span>'
        . '</span>'
        . '<span class="sms-cf-label">Verify you are human</span>'
        . '<span class="sms-cf-brand">' . smsIcon('shield-alt', ['aria-hidden' => 'true']) . 'BCP</span>'
        . '</button>'
        . '</div>'
        . '<script src="' . e(BASE_URL . '/assets/js/sms-captcha.js?v=3') . '"></script>';
}
