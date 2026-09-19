<?php
/**
 * SMS 2 - Hardened Session Management
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

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
            require_once __DIR__ . '/config.php';
            header('Location: ' . BASE_URL . '/login/login.php?timeout=1');
            exit;
        }
        return;
    }

    $_SESSION['last_activity'] = $now;
}
