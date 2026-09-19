<?php
/**
 * SMS 2 - Login Page (CSRF + DB auth)
 */
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/captcha.php';

// First-time adoption: no users yet → setup Super Admin
if (smsNeedsSetup()) {
    header('Location: ' . BASE_URL . '/setup/index.php');
    exit;
}

require_once ROOT_PATH . '/includes/module-controls.php';
$systemMaintenance = smsIsSystemInMaintenance();
$adminAccess = isset($_GET['access']) && $_GET['access'] === 'admin';
// Preserve admin access flag across POST redirects during maintenance
if (!$adminAccess && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['admin_access'])) {
    $adminAccess = true;
}
$loginSelfUrl = BASE_URL . '/login/login.php' . ($adminAccess ? '?access=admin' : '');

// During global maintenance, send everyone to the lockout screen
// (admins may open login via ?access=admin)
if ($systemMaintenance && !$adminAccess && !isAuthenticated()) {
    header('Location: ' . BASE_URL . '/account/maintenance.php');
    exit;
}

// Redirect if already logged in
if (isAuthenticated()) {
    if ($systemMaintenance && !smsCanBypassSystemControls()) {
        logout();
        header('Location: ' . BASE_URL . '/account/maintenance.php');
        exit;
    }
    if (!empty($_SESSION['must_change_password'])) {
        header('Location: ' . BASE_URL . '/login/change-password.php');
        exit;
    }
    header('Location: ' . smsPostLoginRedirectUrl());
    exit;
}

$error = '';
$info = '';
$alertType = 'danger';
$showResetHint = false;
$usernameValue = '';
$loginLocked = false;
$lockUntilTs = 0;

// Persistent login lock gate (survives refresh; no auto-clear until cooldown ends)
$gate = smsLoginGateStatus();
if ($gate) {
    $loginLocked = true;
    $error = (string) $gate['message'];
    $alertType = (string) ($gate['alert'] ?? 'warning');
    $showResetHint = false;
    $lockUntilTs = (int) ($gate['until'] ?? 0);
}

// Also re-check IP throttle on every page load
$throttleNow = smsGetLoginThrottle('');
if (!$loginLocked && !empty($throttleNow['locked'])) {
    $secs = (int) ($throttleNow['lock_seconds'] ?? smsLockoutSeconds());
    $untilTs = 0;
    if (!empty($throttleNow['locked_until'])) {
        $parsed = strtotime((string) $throttleNow['locked_until']);
        if ($parsed !== false && $parsed > time()) {
            $untilTs = $parsed;
            $secs = max(1, $parsed - time());
        }
    }
    $msg = 'Login is temporarily locked after too many failed attempts. Please wait for the cooldown to finish before trying again.';
    smsLoginGateSet($throttleNow['locked_until'] ?? null, $msg, 'warning');
    $loginLocked = true;
    $error = $msg;
    $alertType = 'warning';
    $gate = smsLoginGateStatus();
    $lockUntilTs = (int) ($gate['until'] ?? ($untilTs > 0 ? $untilTs : (time() + $secs)));
}

// One-time flash messages (non-lock errors only — lock uses persistent gate)
if (!$loginLocked && !empty($_SESSION['flash_login_error'])) {
    $error = (string) $_SESSION['flash_login_error'];
    unset($_SESSION['flash_login_error']);
}
if (!empty($_SESSION['flash_login_info'])) {
    $info = (string) $_SESSION['flash_login_info'];
    unset($_SESSION['flash_login_info']);
}
if (!$loginLocked && !empty($_SESSION['flash_login_alert'])) {
    $alertType = (string) $_SESSION['flash_login_alert'];
    unset($_SESSION['flash_login_alert']);
}
if (!$loginLocked && !empty($_SESSION['flash_login_show_reset'])) {
    $showResetHint = true;
    unset($_SESSION['flash_login_show_reset']);
}
if (isset($_SESSION['flash_login_username'])) {
    $usernameValue = (string) $_SESSION['flash_login_username'];
    unset($_SESSION['flash_login_username']);
}

if ($info === '' && isset($_GET['timeout'])) {
    $info = 'Your session expired due to inactivity. Please sign in again.';
}
if ($info === '' && isset($_GET['forced'])) {
    $info = 'You were signed out by Super Admin (module force logout). Please sign in again.';
}
if ($info === '' && isset($_GET['reset'])) {
    $info = 'Password updated. You can sign in with your new password.';
}
if ($info === '' && isset($_GET['logged_out'])) {
    $info = 'You have been signed out.';
}

if ($loginLocked && $lockUntilTs <= time()) {
    $lockUntilTs = time() + smsLockoutSeconds();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    // Block submit while locked (even if someone re-enables the form in DevTools)
    $gatePost = smsLoginGateStatus();
    $throttlePost = smsGetLoginThrottle($username);
    if ($gatePost || !empty($throttlePost['locked'])) {
        $secs = smsLockoutSeconds();
        if (!empty($gatePost['until'])) {
            $secs = max(1, (int) $gatePost['until'] - time());
        } elseif (!empty($throttlePost['locked_until'])) {
            $parsed = strtotime((string) $throttlePost['locked_until']);
            if ($parsed !== false && $parsed > time()) {
                $secs = max(1, $parsed - time());
            }
        }
        $msg = 'Login is temporarily locked after too many failed attempts. Please wait '
            . smsFormatDuration($secs)
            . ' before trying again. Sign-in is disabled until the cooldown ends.';
        smsLoginGateSet($throttlePost['locked_until'] ?? null, $msg, 'warning');
        $_SESSION['flash_login_username'] = $username;
        header('Location: ' . $loginSelfUrl);
        exit;
    }

    if (!csrfVerify()) {
        $_SESSION['flash_login_error'] = 'Security check failed. Please try again.';
        $_SESSION['flash_login_username'] = $username;
        header('Location: ' . $loginSelfUrl);
        exit;
    }

    // CAPTCHA first — bots never reach password check or 2FA
    $captcha = smsCaptchaVerifyRequest();
    if (empty($captcha['ok'])) {
        $_SESSION['flash_login_error'] = $captcha['error'] !== ''
            ? $captcha['error']
            : 'Please complete the CAPTCHA before signing in.';
        $_SESSION['flash_login_username'] = $username;
        header('Location: ' . $loginSelfUrl);
        exit;
    }

    $result = smsLoginAttempt($username, $password);
    if (!empty($result['ok'])) {
        unset(
            $_SESSION['flash_login_error'],
            $_SESSION['flash_login_username'],
            $_SESSION['flash_login_info'],
            $_SESSION['flash_login_alert'],
            $_SESSION['flash_login_show_reset']
        );
        smsLoginGateClear();

        // Global maintenance: only Super Admin may enter the system
        if (smsIsSystemInMaintenance() && !smsCanBypassSystemControls()) {
            logout();
            header('Location: ' . BASE_URL . '/account/maintenance.php');
            exit;
        }

        if (!empty($_SESSION['must_change_password'])) {
            header('Location: ' . BASE_URL . '/login/change-password.php');
            exit;
        }
        header('Location: ' . smsPostLoginRedirectUrl());
        exit;
    }

    if (!empty($result['needs_2fa']) || (($result['code'] ?? '') === 'needs_2fa')) {
        header('Location: ' . BASE_URL . '/login/verify-2fa.php');
        exit;
    }

    $_SESSION['flash_login_username'] = $username;

    if (!empty($result['locked'])) {
        smsLoginGateSet(
            $result['locked_until'] ?? null,
            (string) ($result['message'] ?? 'Login is temporarily locked.'),
            (string) ($result['alert'] ?? 'warning'),
            smsLockoutSeconds()
        );
        unset(
            $_SESSION['flash_login_error'],
            $_SESSION['flash_login_alert'],
            $_SESSION['flash_login_show_reset']
        );
    } else {
        $_SESSION['flash_login_error'] = (string) ($result['message'] ?? 'Sign in failed.');
        $_SESSION['flash_login_alert'] = (string) ($result['alert'] ?? 'danger');
        $_SESSION['flash_login_show_reset'] = !empty($result['show_reset']) ? 1 : 0;
    }

    header('Location: ' . $loginSelfUrl);
    exit;
}

$pageTitle = 'Login';
$bodyClass = 'login-page';

require_once ROOT_PATH . '/includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth-transition.css?v=8">
<style>
body.login-page {
    --bcp-navy: #0b2a6b;
    --bcp-navy-deep: #071c48;
    --bcp-blue: #1a6fc4;
    --bcp-ink: #0f172a;
    --bcp-muted: #64748b;
    --login-font: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    --login-display: Georgia, "Times New Roman", Times, serif;

    min-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    background-image:
        linear-gradient(90deg, rgba(5, 22, 55, 0.88) 0%, rgba(8, 42, 97, 0.8) 46%, rgba(15, 80, 153, 0.72) 100%),
        url("<?= e(smsLoginHeroImageUrl()) ?>") !important;
    background-size: cover !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    overflow-x: hidden;
    color-scheme: light !important;
    position: relative;
    font-family: var(--login-font);
    /* Prevent theme.css background transition flash (white/gray kidlap) */
    transition: none !important;
}

.login-stage {
    position: relative;
    z-index: 1;
    /* Wide enough so Turnstile (300px) fits sharp — no CSS scale */
    width: min(760px, calc(100% - 2.5rem));
    min-height: min(390px, calc(100vh - 8.5rem));
    flex: 0 0 auto;
    display: grid;
    grid-template-columns: minmax(320px, 1.05fr) minmax(0, 0.95fr);
    align-items: stretch;
    gap: 0;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, 0.32);
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(2, 10, 30, 0.34);
    background: rgba(8, 28, 72, 0.28);
    isolation: isolate;
    transform: translateZ(0);
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.login-glass {
    position: relative;
    width: 100%;
    max-width: none;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 1.15rem 1.1rem 1.05rem;
    border-radius: 0;
    border: 0;
    border-right: 1px solid rgba(148, 163, 184, 0.28);
    background: rgba(248, 250, 252, 0.96);
    box-shadow: none;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    transform: none;
}

.login-glass::before,
.login-glass::after {
    display: none;
}

.login-glass > * {
    position: relative;
    z-index: 1;
}

.login-side {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 1.15rem 1.35rem;
    background:
        radial-gradient(circle at 80% 18%, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0) 42%),
        linear-gradient(160deg, rgba(12, 40, 110, 0.58) 0%, rgba(8, 28, 72, 0.44) 100%);
    color: #fff;
}

.login-admit {
    margin: 0;
    text-align: left;
    max-width: none;
    width: 100%;
}

.login-admit h2 {
    margin: 0 0 0.85rem;
    color: #fff;
    font-family: var(--login-font);
    font-size: clamp(1.85rem, 3.1vw, 2.75rem);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: -0.03em;
    text-shadow: 0 4px 18px rgba(2, 8, 24, 0.35);
}

.login-admit a {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    max-width: 100%;
    padding: 0.55rem 0.9rem;
    border: 1px solid rgba(255, 255, 255, 0.45);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.14);
    color: #fff;
    font-size: clamp(0.88rem, 1.05vw, 1rem);
    font-weight: 700;
    line-height: 1.3;
    text-decoration: none;
    text-shadow: 0 2px 12px rgba(2, 8, 24, 0.3);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.22);
    transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
    cursor: pointer;
}

.login-admit a::after {
    content: "→";
    font-family: inherit;
    font-size: 0.95em;
    font-weight: 700;
    transition: transform 0.15s ease;
}

.login-admit a:hover,
.login-admit a:focus-visible {
    color: #fff;
    background: rgba(255, 255, 255, 0.28);
    border-color: rgba(255, 255, 255, 0.75);
    box-shadow: 0 6px 18px rgba(2, 8, 24, 0.28);
    outline: none;
    transform: translateY(-1px);
}

.login-admit a:hover::after,
.login-admit a:focus-visible::after {
    transform: translateX(3px);
}

.login-admit a:active {
    background: rgba(255, 255, 255, 0.34);
    transform: translateY(0);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
}

.login-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: 0.55rem;
}

.login-brand img {
    width: 82px;
    height: 82px;
    object-fit: contain;
    filter: drop-shadow(0 8px 16px rgba(11, 42, 107, 0.18));
}

.login-heading {
    margin-bottom: 0.7rem;
    text-align: center;
}

.login-heading h1 {
    margin: 0;
    color: #0f172a;
    font-family: var(--login-font);
    font-size: 1.4rem;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.02em;
    text-shadow: none;
}

.login-glass .form-label {
    margin-bottom: 0.28rem;
    color: #1e293b;
    font-size: 0.78rem;
    font-weight: 700 !important;
    text-shadow: none;
}

.login-req {
    color: #dc2626;
    font-weight: 700;
}

.login-glass .input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.login-glass .input-group-text {
    display: none !important;
}

.login-glass .form-control {
    width: 100%;
    min-height: 38px;
    border: 1px solid #94a3b8 !important;
    border-radius: 10px !important;
    background: rgba(255, 255, 255, 0.96) !important;
    color: #0f172a !important;
    padding: 0.5rem 0.75rem !important;
    font-size: 0.86rem;
    font-weight: 600;
    box-shadow: none !important;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.login-glass .form-control::placeholder {
    color: #94a3b8;
    font-weight: 500;
    font-size: 0.84rem;
}

.login-glass .form-control:focus {
    border-color: #5350d6 !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(83, 80, 214, 0.18) !important;
    color: #0f172a !important;
}

.login-glass .password-group .form-control {
    padding-right: 3rem !important;
}

.password-toggle {
    position: absolute;
    top: 50%;
    right: 0.75rem;
    z-index: 5;
    width: 32px;
    height: 32px;
    padding: 0;
    border: 0;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    font-size: 0.95rem;
    transform: translateY(-50%);
    border-radius: 8px;
    transition: color 0.18s ease, background 0.18s ease;
}

.password-toggle:hover {
    color: #4338ca;
    background: #eef2ff;
}

.password-toggle:focus-visible {
    outline: 3px solid rgba(83, 80, 214, 0.18);
    outline-offset: 1px;
}

.login-glass .mb-3 {
    margin-bottom: 0.65rem !important;
}

.login-glass .mb-4 {
    margin-bottom: 0.75rem !important;
}

.login-glass .btn-sms-primary {
    width: 100%;
    min-height: 38px;
    border: 0 !important;
    border-radius: 10px !important;
    background: #5350d6 !important;
    color: #fff !important;
    padding: 0.55rem 0.9rem !important;
    font-size: 0.86rem;
    font-weight: 800;
    box-shadow: 0 8px 18px rgba(83, 80, 214, 0.25) !important;
    transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
}

.login-submit-idle i,
.login-passkey-btn i {
    width: 1.15rem;
    height: 1.15rem;
    display: inline-grid;
    place-items: center;
    border-radius: 7px;
    font-size: 0.78rem;
}

.login-submit-idle i {
    color: rgba(255,255,255,0.92);
    background: rgba(255,255,255,0.14);
}

.login-passkey-btn i {
    color: #5350d6;
    background: #eef2ff;
}

.login-glass .btn-sms-primary:hover:not(:disabled):not(.disabled),
.login-glass .btn-sms-primary:focus:not(:disabled):not(.disabled) {
    background: #4542c4 !important;
    box-shadow: 0 10px 22px rgba(83, 80, 214, 0.32) !important;
    transform: translateY(-1px);
}

.login-glass .btn-sms-primary:disabled,
.login-glass .btn-sms-primary.disabled {
    background: #94a3b8 !important;
    box-shadow: none !important;
    cursor: not-allowed;
    opacity: 1;
}

.login-glass .btn-sms-primary.is-loading,
.login-glass .btn-sms-primary.is-loading:disabled {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: #5350d6 !important;
    box-shadow: 0 8px 18px rgba(83, 80, 214, 0.22) !important;
    cursor: wait;
}

.login-loading-spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid rgba(255, 255, 255, 0.45);
    border-top-color: #fff;
    border-radius: 50%;
    animation: loginSpin 0.75s linear infinite;
    flex: 0 0 auto;
}

.login-loading-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(7, 28, 72, 0.58);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.login-loading-overlay.is-visible {
    display: flex;
}

.login-loading-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.8rem;
    min-width: 180px;
    padding: 1.25rem 1.4rem;
    border: 1px solid rgba(255, 255, 255, 0.28);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.92);
    color: #0f172a;
    font-weight: 800;
    box-shadow: 0 22px 50px rgba(2, 10, 30, 0.35);
}

.login-loading-circle {
    width: 2.75rem;
    height: 2.75rem;
    border: 4px solid rgba(83, 80, 214, 0.18);
    border-top-color: #5350d6;
    border-radius: 50%;
    animation: loginSpin 0.75s linear infinite;
}

@keyframes loginSpin {
    to { transform: rotate(360deg); }
}

.login-links {
    margin-top: 1rem;
    text-align: center;
}

.login-links a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    min-height: 2.25rem;
    padding: 0.35rem 0.75rem;
    color: #4338ca;
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1.3;
    text-decoration: none;
    text-shadow: none;
    border-radius: 8px;
}

.login-links a:hover,
.login-links a:focus-visible {
    color: #312e81;
    text-decoration: underline;
    background: rgba(67, 56, 202, 0.08);
    outline: none;
}

.login-or {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    margin: 0.75rem 0 0.55rem;
    color: #64748b;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.login-or::before,
.login-or::after {
    content: "";
    flex: 1;
    height: 1px;
    background: #cbd5e1;
}

.login-glass .login-passkey-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    width: 100%;
    min-height: 36px;
    border: 1px solid #cbd5e1 !important;
    border-radius: 10px !important;
    background: rgba(255, 255, 255, 0.9) !important;
    color: #1e3a8a !important;
    font-size: 0.84rem;
    font-weight: 700;
    box-shadow: none;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
}

.login-glass .login-passkey-btn:hover,
.login-glass .login-passkey-btn:focus {
    border-color: #818cf8 !important;
    background: #eef2ff !important;
    box-shadow: 0 6px 16px rgba(83, 80, 214, 0.12);
    transform: translateY(-1px);
}

.login-glass .login-passkey-btn i {
    color: #5350d6;
}

.login-glass .sms-captcha-wrap {
    margin-bottom: 0.65rem !important;
}

.login-glass .sms-captcha-wrap {
    margin-bottom: 0.7rem !important;
    width: 100%;
    max-width: 100%;
}

.login-glass .sms-captcha-label {
    color: #0f172a !important;
    font-size: 0.78rem !important;
    font-weight: 800 !important;
    margin-bottom: 0.3rem !important;
    text-shadow: none;
}

.login-glass .sms-cf-widget,
.login-glass .sms-captcha-frame:not(.sms-captcha-frame--turnstile),
body.login-page[data-theme="dark"] .login-glass .sms-cf-widget,
body.login-page[data-theme="dark"] .login-glass .sms-captcha-frame:not(.sms-captcha-frame--turnstile),
html[data-theme="dark"] .login-glass .sms-cf-widget,
html[data-theme="dark"] .login-glass .sms-captcha-frame:not(.sms-captcha-frame--turnstile) {
    width: 100% !important;
    max-width: 100% !important;
    min-height: 0 !important;
    border: 1px solid #94a3b8 !important;
    border-radius: 9px !important;
    background: #fff !important;
    color: #0f172a !important;
    box-shadow: none !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    box-sizing: border-box !important;
}

.login-glass .sms-captcha-frame--turnstile,
body.login-page[data-theme="dark"] .login-glass .sms-captcha-frame--turnstile,
html[data-theme="dark"] .login-glass .sms-captcha-frame--turnstile {
    width: 100% !important;
    max-width: 100% !important;
    min-height: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    overflow: visible !important;
}

.login-glass .sms-cf-widget {
    display: flex !important;
    align-items: center !important;
    padding: 0.45rem 0.65rem !important;
    gap: 0.5rem !important;
    min-height: 38px !important;
}

.login-glass .sms-captcha-frame--turnstile .cf-turnstile {
    width: 300px !important;
    max-width: 100% !important;
    transform: none !important;
}

.login-glass .sms-cf-widget:hover,
.login-glass .sms-cf-widget:focus-visible {
    border-color: #5350d6 !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(83, 80, 214, 0.14) !important;
    outline: none !important;
}

.login-glass .sms-cf-widget:active,
.login-glass .sms-cf-widget.is-loading {
    border-color: #5350d6 !important;
    background: #f8faff !important;
    color: #0f172a !important;
}

.login-glass .sms-cf-box,
html[data-theme="dark"] .login-glass .sms-cf-box {
    width: 1.15rem !important;
    height: 1.15rem !important;
    border: 2px solid #64748b !important;
    border-radius: 4px !important;
    background: #fff !important;
    flex-shrink: 0 !important;
}

.login-glass .sms-cf-label,
html[data-theme="dark"] .login-glass .sms-cf-label {
    flex: 1;
    min-width: 0;
    color: #0f172a !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
}

.login-glass .sms-cf-brand,
html[data-theme="dark"] .login-glass .sms-cf-brand {
    border: 1px solid rgba(83, 80, 214, 0.25) !important;
    border-radius: 999px !important;
    background: rgba(238, 242, 255, 0.95) !important;
    color: #4338ca !important;
    font-size: 0.65rem !important;
    font-weight: 800 !important;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    opacity: 1 !important;
    padding: 0.15rem 0.45rem !important;
    white-space: nowrap;
}

.login-glass .sms-cf-widget.is-verified,
html[data-theme="dark"] .login-glass .sms-cf-widget.is-verified {
    border-color: rgba(34, 197, 94, 0.55) !important;
    background: #f0fdf4 !important;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.12) !important;
}

.login-glass .sms-cf-widget.is-verified .sms-cf-label,
.login-glass .sms-cf-widget.is-verified .sms-cf-check {
    color: #15803d !important;
}

.login-glass .sms-cf-widget.is-verified .sms-cf-box {
    border-color: #22c55e !important;
    background: #ecfdf5 !important;
}

.login-glass .sms-cf-widget.is-verified .sms-cf-brand {
    border-color: rgba(34, 197, 94, 0.35) !important;
    background: rgba(220, 252, 231, 0.95) !important;
    color: #15803d !important;
}

.login-field-error {
    display: none;
    margin: 0.28rem 0 0;
    padding: 0;
    color: #be123c;
    font-size: 0.75rem;
    font-weight: 700;
    line-height: 1.3;
}

.login-field-error.is-visible {
    display: block;
}

.login-glass .form-control.is-invalid {
    border-color: #e11d48 !important;
    background: rgba(255, 241, 242, 0.98) !important;
    box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.14) !important;
}

.login-glass .form-control.is-invalid:focus {
    border-color: #e11d48 !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.18) !important;
}

.login-glass .invalid-feedback,
.login-glass .valid-feedback {
    display: none !important;
}

.login-alert {
    border-radius: 12px !important;
    border-width: 1px !important;
    font-size: 0.84rem;
    font-weight: 600;
    line-height: 1.4;
    padding: 0.75rem 0.85rem !important;
    backdrop-filter: blur(12px) saturate(1.2);
    -webkit-backdrop-filter: blur(12px) saturate(1.2);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
}

.login-alert.alert-danger {
    background: rgba(254, 242, 242, 0.82) !important;
    border-color: rgba(252, 165, 165, 0.85) !important;
    color: #991b1b !important;
}

.login-alert.alert-warning {
    background: rgba(255, 247, 237, 0.82) !important;
    border-color: rgba(253, 186, 116, 0.85) !important;
    color: #9a3412 !important;
}

.login-alert.alert-info {
    background: rgba(239, 246, 255, 0.82) !important;
    border-color: rgba(147, 197, 253, 0.85) !important;
    color: #1e3a8a !important;
}

.login-alert .alert-link {
    color: #1d4ed8 !important;
    font-weight: 800;
    text-decoration: underline;
}

.login-lock-box {
    margin: 0 0 1rem;
    padding: 0.8rem 0.9rem;
    border-radius: 12px;
    border: 1px solid #fdba74;
    background: rgba(255, 247, 237, 0.95);
}

.login-lock-box .lock-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.login-lock-box .lock-copy {
    margin: 0;
    color: #9a3412;
    font-size: 0.8rem;
    font-weight: 600;
    line-height: 1.35;
    flex: 1 1 140px;
    min-width: 0;
}

.login-countdown {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.6rem;
    border-radius: 999px;
    background: #111827;
    color: #fff;
    white-space: nowrap;
}

.login-countdown .count-label {
    font-size: 0.62rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #fbbf24;
}

.login-countdown .count-value {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.85rem;
    font-weight: 700;
    line-height: 1;
    color: #fff;
}

.login-locked-fields {
    opacity: 0.55;
    margin: 0;
    padding: 0;
    border: 0;
    min-width: 0;
    pointer-events: none;
}

.login-footer {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1;
    margin: 0;
    padding: 0 1rem 1.15rem;
    color: rgba(255, 255, 255, 0.92);
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    text-shadow: 0 2px 12px rgba(0, 0, 0, 0.45);
    pointer-events: none;
}

/* Cookie notice — glassy pill, bottom-right */
.cookie-notice {
    position: fixed;
    left: auto;
    right: 1.1rem;
    bottom: 1.25rem;
    z-index: 40;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.6rem;
    max-width: min(340px, calc(100vw - 2rem));
}

.cookie-notice-btn {
    width: auto;
    height: auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.65rem 0.9rem;
    border: 1px solid rgba(255, 255, 255, 0.32);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    color: #fff;
    font-family: var(--login-font);
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow:
        0 10px 24px rgba(2, 10, 30, 0.28),
        inset 0 1px 0 rgba(255, 255, 255, 0.28);
    backdrop-filter: blur(14px) saturate(1.2);
    -webkit-backdrop-filter: blur(14px) saturate(1.2);
}

.cookie-notice-btn:hover,
.cookie-notice-btn:focus-visible {
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
    outline: none;
}

.cookie-notice-btn i {
    display: none;
}

.cookie-toast {
    display: none !important;
}

.cookie-notice-btn .cookie-notice-label {
    position: static;
    width: auto;
    height: auto;
    overflow: visible;
    clip: auto;
    white-space: nowrap;
    color: #fff;
    font-size: 0.8rem;
    font-weight: 700;
}

.cookie-notice-panel {
    display: none;
    width: 100%;
    padding: 1rem 1.05rem;
    border: 1px solid rgba(255, 255, 255, 0.38);
    border-radius: 14px;
    background: rgba(15, 23, 42, 0.72);
    color: #fff;
    box-shadow: 0 14px 32px rgba(2, 10, 30, 0.35);
    backdrop-filter: blur(16px) saturate(1.2);
    -webkit-backdrop-filter: blur(16px) saturate(1.2);
}

.cookie-notice-panel.is-open {
    display: block;
}

.cookie-notice-panel h2 {
    margin: 0 0 0.45rem;
    color: #fff;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.01em;
}

.cookie-notice-panel p {
    margin: 0 0 0.85rem;
    color: rgba(255, 255, 255, 0.95);
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.4;
}

.cookie-notice-panel ul {
    display: none;
}

.cookie-notice-panel .cookie-ack {
    width: 100%;
    min-height: 40px;
    border: 1px solid rgba(255, 255, 255, 0.28);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.22);
    color: #fff;
    font-family: var(--login-font);
    font-size: 0.9rem;
    font-weight: 800;
    cursor: pointer;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.cookie-toast {
    position: fixed;
    left: auto;
    right: 1.1rem;
    bottom: 5rem;
    z-index: 50;
    display: none;
    max-width: min(320px, calc(100vw - 2rem));
    padding: 0.8rem 0.9rem;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    box-shadow: 0 14px 32px rgba(2, 10, 30, 0.32);
    backdrop-filter: blur(16px) saturate(1.2);
    -webkit-backdrop-filter: blur(16px) saturate(1.2);
}

.cookie-toast.is-visible {
    display: block;
}

.cookie-toast strong {
    display: block;
    margin-bottom: 0.2rem;
    color: #fde68a;
    font-size: 0.82rem;
}

.cookie-toast span {
    display: block;
    color: rgba(255, 255, 255, 0.88);
    font-size: 0.74rem;
    font-weight: 600;
    line-height: 1.4;
}

@media (max-width: 860px) {
    .login-stage {
        width: min(440px, calc(100% - 1.5rem));
        min-height: 0;
        grid-template-columns: 1fr;
        margin: 1rem auto;
    }

    .login-glass {
        border-right: 0;
        border-bottom: 1px solid rgba(148, 163, 184, 0.35);
        padding: 1.45rem 1.2rem 1.25rem;
    }

    .login-side {
        padding: 1.35rem 1.2rem 1.5rem;
        min-height: 140px;
    }

    .login-admit {
        max-width: none;
        text-align: center;
    }

    .login-admit h2 {
        font-size: 1.85rem;
        margin-bottom: 0.75rem;
    }

    .login-admit a {
        font-size: 0.92rem;
        justify-content: center;
    }

    .login-heading h1 {
        font-size: 1.35rem;
    }
}

@media (max-width: 575.98px) {
    .login-stage {
        width: calc(100% - 1rem);
        border-radius: 14px;
    }

    .login-brand img {
        width: 72px;
        height: 72px;
    }

    .login-heading h1 {
        font-size: 1.3rem;
    }

    .login-admit h2 {
        font-size: 1.7rem;
    }

    .cookie-notice {
        left: auto;
        right: 0.75rem;
        bottom: 0.85rem;
    }

    .cookie-toast {
        left: auto;
        right: 0.75rem;
        bottom: 4.5rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .login-video-bg {
        background: #071c48;
    }
}
</style>

<main class="login-stage">
    <div class="login-glass" aria-label="Student Management System login">
        <div class="login-brand">
            <img src="<?= e(smsBrandLogoUrl()) ?>?v=crest3" alt="Bestlink College of the Philippines" width="82" height="82">
        </div>
        <div class="login-heading">
            <h1><?= $systemMaintenance && $adminAccess ? 'Administrator sign-in' : 'Sign in' ?></h1>
        </div>

        <?php if ($systemMaintenance && $adminAccess): ?>
            <div class="alert alert-warning login-alert" role="status">
                <?= smsIcon('exclamation-triangle', ['class' => 'me-2']) ?>System maintenance is on. Only Super Admin can enter.
            </div>
        <?php endif; ?>

        <?php if ($info): ?>
            <div class="alert alert-info login-alert" role="alert">
                <?= smsIcon('info-circle', ['class' => 'me-2']) ?><?= e($info) ?>
            </div>
        <?php endif; ?>

        <?php if ($error && !$loginLocked): ?>
            <div class="alert alert-<?= $alertType === 'warning' ? 'warning' : 'danger' ?> login-alert" role="alert">
                <?= smsIcon($alertType === 'warning' ? 'alert-triangle' : 'alert-circle', ['class' => 'me-2']) ?><?= e($error) ?>
                <?php if ($showResetHint): ?>
                    <div class="mt-2">
                        <a href="<?= BASE_URL ?>/login/forgot-password.php" class="alert-link" data-auth-transition data-auth-direction="left">Forgot password? Reset it here</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($loginLocked): ?>
            <div class="login-lock-box" id="loginLockBox"
                 data-lock-until="<?= (int) $lockUntilTs ?>"
                 data-reload-url="<?= e(BASE_URL . '/login/login.php') ?>">
                <div class="lock-row">
                    <p class="lock-copy"><?= smsIcon('lock', ['class' => 'me-1']) ?>Login locked. Try again when the timer ends.</p>
                    <div class="login-countdown" aria-live="polite">
                        <span class="count-label">Left</span>
                        <span class="count-value" id="loginCountdownValue">--:--</span>
                    </div>
                </div>
            </div>
            <fieldset disabled class="login-locked-fields">
                <div class="mb-3">
                    <label for="username" class="form-label">Email or username <span class="login-req">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Email or username" value="<?= e($usernameValue) ?>" autocomplete="username">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Password <span class="login-req">*</span></label>
                    <div class="input-group password-group">
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Enter your password" autocomplete="current-password">
                    </div>
                </div>
                <button type="button" class="btn btn-secondary w-100" disabled>Sign In unavailable</button>
            </fieldset>
            <div class="login-links">
                <a href="<?= BASE_URL ?>/login/forgot-password.php" data-auth-transition data-auth-direction="left">Forgot password?</a>
            </div>
        <?php else: ?>
        <form method="POST" action="" novalidate id="loginForm">
            <?= csrfField() ?>
            <?php if ($adminAccess): ?>
            <input type="hidden" name="admin_access" value="1">
            <?php endif; ?>
            <div class="mb-3">
                <label for="username" class="form-label">Email or username <span class="login-req">*</span></label>
                <div class="input-group">
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="Email or username" required autofocus
                           value="<?= e($usernameValue) ?>" autocomplete="username"
                           aria-describedby="usernameError">
                </div>
                <div class="login-field-error" id="usernameError" role="alert">Email or username is required.</div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password <span class="login-req">*</span></label>
                <div class="input-group password-group">
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter your password" required autocomplete="current-password"
                           aria-describedby="passwordError">
                    <button class="password-toggle" type="button" aria-label="Show password" title="Show password" data-pw-target="password" aria-pressed="false">
                        <?= smsIcon('eye') ?>
                    </button>
                </div>
                <div class="login-field-error" id="passwordError" role="alert">Password is required.</div>
            </div>
            <?= smsCaptchaMarkup() ?>
            <div class="login-field-error" id="captchaError" role="alert">Please complete the security check.</div>
            <button type="submit" class="btn btn-sms-primary disabled" id="loginSubmitBtn" aria-disabled="true"
                    title="Fill in email or username and password first">
                <span class="login-submit-idle">Sign In</span>
                <span class="login-submit-loading d-none" aria-live="polite">
                    <span class="login-loading-spinner" aria-hidden="true"></span>
                    Signing in...
                </span>
            </button>
            <div class="login-or" aria-hidden="true">or</div>
            <div id="smsPasskeyLoginMsg" class="small mb-2 text-center" hidden></div>
            <button type="button" class="btn login-passkey-btn" id="smsPasskeyLoginBtn"
                    data-passkey-api="<?= e(BASE_URL . '/api/passkey.php') ?>">
                <?= smsIcon('key', ['aria-hidden' => 'true']) ?>Sign in with Passkey
            </button>
            <div class="login-links">
                <a href="<?= BASE_URL ?>/login/forgot-password.php" data-auth-transition data-auth-direction="left">Forgot password?</a>
            </div>
        </form>
        <script src="<?= BASE_URL ?>/assets/js/passkey.js?v=10"></script>
        <?php endif; ?>
    </div>
    <aside class="login-side" aria-label="Student admission">
        <div class="login-admit">
            <h2>Student Management System</h2>
            <a href="<?= BASE_URL ?>/login/student-admission.php"
               title="Student admission click here">
                Student admission click here
            </a>
        </div>
    </aside>
</main>

<footer class="login-footer">
    <p class="mb-0">&copy; 2026 Bestlink College of the Philippines. All rights reserved.</p>
</footer>

<aside class="cookie-notice" aria-label="Cookie notice">
    <div class="cookie-notice-panel" id="cookieNoticePanel" role="dialog" aria-labelledby="cookieNoticeTitle" hidden>
        <h2 id="cookieNoticeTitle">Cookie notice</h2>
        <p>Enable cookies in your browser. This site uses 2 cookies for secure sign-in.</p>
        <button type="button" class="cookie-ack" id="cookieAckBtn">Got it</button>
    </div>
    <button type="button" class="cookie-notice-btn" id="cookieNoticeBtn" aria-expanded="false" aria-controls="cookieNoticePanel" title="Cookie notice">
        <span class="cookie-notice-label">Cookie notice</span>
    </button>
</aside>

<div class="login-loading-overlay" id="loginLoadingOverlay" role="status" aria-live="polite" aria-hidden="true">
    <div class="login-loading-box">
        <span class="login-loading-circle" aria-hidden="true"></span>
        <span>Signing in...</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('password');
    const username = document.getElementById('username');
    const submitBtn = document.getElementById('loginSubmitBtn');
    const form = document.getElementById('loginForm');
    const loadingOverlay = document.getElementById('loginLoadingOverlay');

    const cookieBtn = document.getElementById('cookieNoticeBtn');
    const cookiePanel = document.getElementById('cookieNoticePanel');
    const cookieAck = document.getElementById('cookieAckBtn');

    function readCookie(name) {
        const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : '';
    }

    function writeCookie(name, value, days) {
        const maxAge = Math.max(1, Math.floor(days * 86400));
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = name + '=' + encodeURIComponent(value)
            + '; Path=/; Max-Age=' + maxAge + '; SameSite=Lax' + secure;
    }

    function openCookiePanel(open) {
        if (!cookiePanel || !cookieBtn) return;
        cookiePanel.hidden = !open;
        cookiePanel.classList.toggle('is-open', open);
        cookieBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (cookieBtn && cookiePanel) {
        cookieBtn.addEventListener('click', function () {
            openCookiePanel(cookiePanel.hidden);
        });
    }

    if (cookieAck) {
        cookieAck.addEventListener('click', function () {
            writeCookie('sms_cookie_ok', '1', 365);
            openCookiePanel(false);
        });
    }

    // One soft open if cookies are disabled (single panel only — no second toast)
    try {
        if (!navigator.cookieEnabled && !readCookie('sms_cookie_ok')) {
            openCookiePanel(true);
        }
    } catch (err) {
        // ignore
    }

    function isCaptchaReady() {
        const captchaOk = document.getElementById('smsCaptchaOk');
        const captchaWidget = document.getElementById('smsCaptchaWidget');
        const turnstile = document.querySelector('.cf-turnstile');
        if (captchaWidget && captchaOk) {
            return captchaOk.value === '1';
        }
        if (turnstile) {
            const ts = document.querySelector('[name="cf-turnstile-response"]');
            return !!(ts && ts.value && ts.value.length > 10);
        }
        return true;
    }

    function setCaptchaError(show) {
        const captchaError = document.getElementById('captchaError');
        if (!captchaError) return;
        if (show) {
            captchaError.textContent = 'Please complete the security check.';
            captchaError.classList.add('is-visible');
        } else {
            captchaError.classList.remove('is-visible');
        }
    }

    function syncSignInEnabled() {
        if (!submitBtn || !username || !password) return;
        const captchaReady = isCaptchaReady();
        if (captchaReady) {
            setCaptchaError(false);
        }
        const hasEmail = username.value.trim() !== '';
        const hasPassword = password.value !== '';
        const ready = hasEmail && hasPassword && captchaReady;
        submitBtn.classList.toggle('disabled', !ready);
        submitBtn.setAttribute('aria-disabled', ready ? 'false' : 'true');

        // Short hover tip while incomplete (tooltip) — field errors still show on submit
        if (ready) {
            submitBtn.removeAttribute('title');
        } else if (!hasEmail && !hasPassword) {
            submitBtn.title = 'Fill in email or username and password first';
        } else if (!hasEmail) {
            submitBtn.title = 'Enter your email or username first';
        } else if (!hasPassword) {
            submitBtn.title = 'Enter your password first';
        } else {
            submitBtn.title = 'Complete the security check first';
        }
    }

    const usernameError = document.getElementById('usernameError');
    const passwordError = document.getElementById('passwordError');

    function setFieldRequiredError(input, errorEl, message, show) {
        if (!input || !errorEl) return;
        if (show) {
            errorEl.textContent = message;
            errorEl.classList.add('is-visible');
            input.classList.add('is-invalid');
            input.setAttribute('aria-invalid', 'true');
        } else {
            errorEl.classList.remove('is-visible');
            input.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
        }
    }

    function validateUsername(showWhenEmpty) {
        if (!username) return true;
        const empty = username.value.trim() === '';
        setFieldRequiredError(username, usernameError, 'Email or username is required.', showWhenEmpty && empty);
        return !empty;
    }

    function validatePassword(showWhenEmpty) {
        if (!password) return true;
        const empty = password.value === '';
        setFieldRequiredError(password, passwordError, 'Password is required.', showWhenEmpty && empty);
        return !empty;
    }

    function isEmailField(el) {
        return !!(el && username && (el === username || el.id === 'username'));
    }

    function isPasswordField(el) {
        if (!el || !password) return false;
        if (el === password || el.id === 'password') return true;
        // Password show/hide button sits in the same group
        return !!(el.closest && el.closest('.password-group, .input-group') && password.closest('.password-group, .input-group') === el.closest('.password-group, .input-group') && el !== username);
    }

    let emailTouched = false;
    let passwordTouched = false;

    if (username) {
        username.addEventListener('focus', function () {
            emailTouched = true;
            // Password was opened then left empty → moving here shows password required
            if (passwordTouched && password && password.value === '') {
                validatePassword(true);
            }
        });
        username.addEventListener('input', function () {
            validateUsername(username.classList.contains('is-invalid') || (usernameError && usernameError.classList.contains('is-visible')));
            syncSignInEnabled();
        });
        username.addEventListener('change', syncSignInEnabled);
        username.addEventListener('blur', function (e) {
            // Only after email was focused, then user moves to password (not random clicks)
            if (!emailTouched) return;
            if (isPasswordField(e.relatedTarget)) {
                validateUsername(true);
            }
        });
    }
    if (password) {
        password.addEventListener('focus', function () {
            passwordTouched = true;
            // Email was opened then left empty → moving here shows email required
            if (emailTouched && username && username.value.trim() === '') {
                validateUsername(true);
            }
        });
        password.addEventListener('input', function () {
            validatePassword(password.classList.contains('is-invalid') || (passwordError && passwordError.classList.contains('is-visible')));
            syncSignInEnabled();
        });
        password.addEventListener('change', syncSignInEnabled);
        password.addEventListener('blur', function (e) {
            // Only after password was focused, then user moves back to email
            if (!passwordTouched) return;
            if (isEmailField(e.relatedTarget)) {
                validatePassword(true);
            }
        });
    }
    document.addEventListener('sms-captcha-ok', syncSignInEnabled);
    document.addEventListener('sms-captcha-reset', syncSignInEnabled);
    // Poll briefly for Turnstile token
    let captchaPoll = 0;
    const captchaTimer = setInterval(function () {
        syncSignInEnabled();
        captchaPoll += 1;
        if (captchaPoll > 40) clearInterval(captchaTimer);
    }, 500);
    syncSignInEnabled();

    if (form && submitBtn) {
        form.addEventListener('submit', function (e) {
            if (submitBtn.classList.contains('is-loading')) {
                e.preventDefault();
                return;
            }
            const userOk = validateUsername(true);
            const passOk = validatePassword(true);
            const captchaOk = isCaptchaReady();
            setCaptchaError(!captchaOk);
            const ready = userOk && passOk && captchaOk;
            if (!ready) {
                e.preventDefault();
                syncSignInEnabled();
                if (!userOk && username) {
                    username.focus();
                } else if (!passOk && password) {
                    password.focus();
                }
                return;
            }
            const idleLabel = submitBtn.querySelector('.login-submit-idle');
            const loadingLabel = submitBtn.querySelector('.login-submit-loading');
            if (idleLabel && loadingLabel) {
                idleLabel.classList.add('d-none');
                loadingLabel.classList.remove('d-none');
            } else {
                submitBtn.textContent = 'Signing in...';
            }
            if (loadingOverlay) {
                loadingOverlay.classList.add('is-visible');
                loadingOverlay.setAttribute('aria-hidden', 'false');
            }
            submitBtn.classList.add('is-loading');
            submitBtn.classList.add('disabled');
            submitBtn.setAttribute('aria-disabled', 'true');
            submitBtn.disabled = true;
        });
    }

    // Show/hide password is handled globally by sms-security-ui.js (do not double-bind here)

    const box = document.getElementById('loginLockBox');
    const valueEl = document.getElementById('loginCountdownValue');
    if (!box || !valueEl) return;

    const until = parseInt(box.getAttribute('data-lock-until') || '0', 10);
    const reloadUrl = box.getAttribute('data-reload-url') || window.location.href;

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function formatClock(totalSeconds) {
        const s = Math.max(0, totalSeconds);
        const hours = Math.floor(s / 3600);
        const minutes = Math.floor((s % 3600) / 60);
        const seconds = s % 60;
        if (hours > 0) {
            return pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
        }
        return pad(minutes) + ':' + pad(seconds);
    }

    function tick() {
        const remaining = until - Math.floor(Date.now() / 1000);
        if (remaining <= 0) {
            valueEl.textContent = '00:00';
            window.setTimeout(function () {
                window.location.href = reloadUrl;
            }, 500);
            return;
        }
        valueEl.textContent = formatClock(remaining);
        window.setTimeout(tick, 250);
    }

    tick();
});
</script>

<?php require_once ROOT_PATH . '/includes/scripts.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/auth-transition.js?v=8"></script>
