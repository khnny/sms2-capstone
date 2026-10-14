<?php
/**
 * SMS 2 - Hardened Session Management
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    // Detect HTTPS even behind reverse proxies (Cloudflare, Nginx, load
    // balancers) that terminate SSL at the edge and forward plain HTTP.
    $secure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (!empty($_SERVER['HTTP_CF_VISITOR'])
            && strpos($_SERVER['HTTP_CF_VISITOR'], '"https"') !== false)
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
    );

    session_name('SMS2SESSID');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_trans_sid', '0');

    session_start();
}

/** Idle timeout in seconds (default 30 minutes). */
if (!defined('SMS_SESSION_IDLE_SECONDS')) {
    define('SMS_SESSION_IDLE_SECONDS', 30 * 60);
}

/**
 * Enforce idle timeout for authenticated sessions.
 */
function smsEnforceSessionTimeout(): void
{
    if (empty($_SESSION['user_id'])) {
        return;
    }

    $now = time();
    $last = (int) ($_SESSION['last_activity'] ?? $now);

    $idleMinutes = 30;
    if (function_exists('smsSetting')) {
        $idleMinutes = max(1, (int) smsSetting('session_timeout_minutes', '30'));
    } elseif (defined('SMS_SESSION_IDLE_SECONDS')) {
        $idleMinutes = (int) max(1, SMS_SESSION_IDLE_SECONDS / 60);
    }
    $idleSeconds = $idleMinutes * 60;

    if (($now - $last) > $idleSeconds) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();

        if (!headers_sent()) {
            // Return JSON for AJAX/fetch/API requests instead of HTML redirect.
            $isApiRequest = (
                (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (!empty($_SERVER['HTTP_ACCEPT'])
                    && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                || (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST'
                    && !empty($_SERVER['CONTENT_TYPE'])
                    && (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false
                        || strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false))
            );

            if ($isApiRequest) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error'   => 'session_expired',
                    'message' => 'Your session has expired. Please log in again.',
                ]);
                exit;
            }

            require_once __DIR__ . '/config.php';
            header('Location: ' . BASE_URL . '/login/login.php?timeout=1');
            exit;
        }
        return;
    }

    $_SESSION['last_activity'] = $now;
}
