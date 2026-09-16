<?php
/**
 * SMS 2 - Security helpers (CSRF, headers, escaping, client IP)
 */

declare(strict_types=1);

/**
 * Send common security headers (call early on HTML/API responses).
 */
function smsSendSecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Cross-Origin-Opener-Policy: same-origin');

    // Baseline CSP — local assets + Cloudflare Turnstile CAPTCHA + HostForge/CF Insights beacon
    header(
        "Content-Security-Policy: default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' https://challenges.cloudflare.com https://static.cloudflareinsights.com; " .
        "style-src 'self' 'unsafe-inline'; " .
        "font-src 'self' data:; " .
        "img-src 'self' data: blob:; " .
        "connect-src 'self' https://challenges.cloudflare.com https://cloudflareinsights.com; " .
        "frame-src 'self' https://challenges.cloudflare.com https://bcp-admissions.elearningcommons.com; " .
        "child-src 'self' https://challenges.cloudflare.com https://bcp-admissions.elearningcommons.com; " .
        "frame-ancestors 'self'; " .
        "base-uri 'self'; " .
        "form-action 'self'"
    );
}

/**
 * HTML escape helper.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Best-effort client IP.
 *
 * HostForge / Cloudflare sit in front of PHP, so REMOTE_ADDR is often a
 * private container address shared by every visitor. Prefer the edge headers
 * so failed logins lock one client instead of the whole app.
 */
function smsClientIp(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_TRUE_CLIENT_IP', 'HTTP_X_REAL_IP'] as $key) {
        $raw = $_SERVER[$key] ?? '';
        if (!is_string($raw) || $raw === '') {
            continue;
        }
        $ip = trim($raw);
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return substr($ip, 0, 45);
        }
    }

    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if (is_string($forwarded) && $forwarded !== '') {
        foreach (explode(',', $forwarded) as $part) {
            $ip = trim($part);
            if ($ip === '') {
                continue;
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return substr($ip, 0, 45);
            }
        }
        $first = trim(explode(',', $forwarded)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return substr($first, 0, 45);
        }
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return is_string($ip) ? substr($ip, 0, 45) : '0.0.0.0';
}

/**
 * Get or create CSRF token for the session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Hidden input for HTML forms.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Validate CSRF token from POST (or custom array).
 */
function csrfVerify(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($token) || $token === '' || !is_string($sessionToken) || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Require valid CSRF or abort with 403.
 */
function requireCsrf(?string $token = null): void
{
    if (!csrfVerify($token)) {
        http_response_code(403);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
        } else {
            echo 'Forbidden: invalid CSRF token.';
        }
        exit;
    }
}

/**
 * Validate CSRF from JSON body field csrf_token.
 */
function requireCsrfJson(array $data): void
{
    requireCsrf(isset($data['csrf_token']) ? (string) $data['csrf_token'] : null);
}

/**
 * Require CSRF on mutating HTTP methods (POST/PUT/PATCH/DELETE).
 * Accepts csrf_token from JSON body, form POST, or X-CSRF-Token header.
 */
function smsRequireMutatingCsrf(?array $jsonBody = null): void
{
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        return;
    }

    $token = '';
    if (is_array($jsonBody) && isset($jsonBody['csrf_token'])) {
        $token = (string) $jsonBody['csrf_token'];
    } elseif (isset($_POST['csrf_token'])) {
        $token = (string) $_POST['csrf_token'];
    } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    requireCsrf($token !== '' ? $token : null);
}

/**
 * Read a system setting with default.
 * Sensitive keys (SMTP / Turnstile secret) are decrypted transparently.
 */
function smsSetting(string $key, string $default = ''): string
{
    if (!isset($GLOBALS['__sms_settings_cache']) || !is_array($GLOBALS['__sms_settings_cache'])) {
        $GLOBALS['__sms_settings_cache'] = [];
    }
    $cache =& $GLOBALS['__sms_settings_cache'];

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    require_once __DIR__ . '/../config/database.php';
    $pdo = db();
    if (!$pdo) {
        return $default;
    }

    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM sms_system_settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $raw = $row ? (string) $row['setting_value'] : $default;
        if ($row) {
            require_once __DIR__ . '/crypto.php';
            if (smsIsEncryptedSettingKey($key) && $raw !== '') {
                if (!smsCryptoIsEncrypted($raw)) {
                    // Upgrade legacy plaintext secret on first read (direct write — avoid recursion)
                    try {
                        $enc = smsSecretEncrypt($raw);
                        $upd = $pdo->prepare(
                            'UPDATE sms_system_settings SET setting_value = ? WHERE setting_key = ?'
                        );
                        $upd->execute([$enc, $key]);
                    } catch (Throwable $e) {
                        // keep serving plaintext this request
                    }
                } else {
                    $raw = smsSecretDecrypt($raw);
                }
            }
        }
        $cache[$key] = $raw;
    } catch (Throwable $e) {
        $cache[$key] = $default;
    }

    return $cache[$key];
}

/**
 * Upsert a system setting and refresh the in-request cache.
 * Sensitive keys are encrypted at rest.
 */
function smsSetSetting(string $key, string $value): bool
{
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/crypto.php';
    $pdo = db();
    if (!$pdo || $key === '') {
        return false;
    }

    $storeValue = $value;
    if (smsIsEncryptedSettingKey($key) && $value !== '') {
        $storeValue = smsSecretEncrypt($value);
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO sms_system_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $storeValue]);
        if (!isset($GLOBALS['__sms_settings_cache']) || !is_array($GLOBALS['__sms_settings_cache'])) {
            $GLOBALS['__sms_settings_cache'] = [];
        }
        // Cache plaintext for this request
        $GLOBALS['__sms_settings_cache'][$key] = $value;
        return true;
    } catch (Throwable $e) {
        error_log('SMS2 smsSetSetting failed: ' . $e->getMessage());
        return false;
    }
}
