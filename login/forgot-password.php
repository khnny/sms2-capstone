<?php
/**
 * SMS 2 – Forgot password (email → 6-digit OTP → new password)
 * OTP expires in 2 minutes.
 */
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security-workflow.php';
require_once ROOT_PATH . '/includes/mail.php';
require_once ROOT_PATH . '/includes/captcha.php';
require_once ROOT_PATH . '/includes/security-ui.php';
require_once ROOT_PATH . '/includes/module-controls.php';

if (smsIsSystemInMaintenance()) {
    header('Location: ' . BASE_URL . '/account/maintenance.php');
    exit;
}

if (isAuthenticated()) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

const SMS_FORGOT_OTP_PURPOSE = 'forgot_password';
const SMS_FORGOT_OTP_TTL_MIN = 2;
const SMS_FORGOT_PW_WINDOW_SEC = 300;

$message = '';
$error = '';
$emailValue = '';
$devOtpCode = '';
$minLen = (int) smsSetting('min_password_length', '8');

/**
 * Find user by email address only.
 */
function smsFindUserByEmailExact(string $email): ?array
{
    $email = strtolower(trim($email));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    $pdo = db();
    if (!$pdo) {
        return null;
    }
    $stmt = $pdo->prepare(
        'SELECT u.*, r.label AS role_label
         FROM `sms2_users` u
         LEFT JOIN `sms2_roles` r ON r.role_key = u.role_key
         WHERE LOWER(u.email) = ?
         LIMIT 1'
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function smsForgotSessionClear(): void
{
    unset($_SESSION['forgot_pw']);
}

/**
 * @return array{user_id:int,email:string,step:string,otp_expires_at:int,verified_at:int}|null
 */
function smsForgotSessionGet(): ?array
{
    $s = $_SESSION['forgot_pw'] ?? null;
    if (!is_array($s) || empty($s['user_id']) || empty($s['email']) || empty($s['step'])) {
        return null;
    }
    return [
        'user_id' => (int) $s['user_id'],
        'email' => (string) $s['email'],
        'step' => (string) $s['step'],
        'otp_expires_at' => (int) ($s['otp_expires_at'] ?? 0),
        'verified_at' => (int) ($s['verified_at'] ?? 0),
    ];
}

/**
 * @param array{user_id:int,email:string,step:string,otp_expires_at?:int,verified_at?:int} $data
 */
function smsForgotSessionSet(array $data): void
{
    $_SESSION['forgot_pw'] = [
        'user_id' => (int) $data['user_id'],
        'email' => (string) $data['email'],
        'step' => (string) $data['step'],
        'otp_expires_at' => (int) ($data['otp_expires_at'] ?? 0),
        'verified_at' => (int) ($data['verified_at'] ?? 0),
    ];
}

function smsForgotApplyNewPassword(int $userId, string $newPassword): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }
    $strength = smsValidatePasswordStrength($newPassword);
    if (!$strength['ok']) {
        return false;
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'UPDATE `sms2_users`
         SET password_hash = ?, must_change_password = 0, password_changed_at = NOW(),
             failed_login_attempts = 0, locked_until = NULL, status = IF(status = \'locked\', \'active\', status)
         WHERE id = ?'
    );
    $stmt->execute([$hash, $userId]);
    return $stmt->rowCount() >= 0;
}

/** Cancel / start over */
if (isset($_GET['cancel'])) {
    $sess = smsForgotSessionGet();
    if ($sess) {
        smsClearCodeGate($sess['user_id'], SMS_FORGOT_OTP_PURPOSE);
    }
    smsForgotSessionClear();
    header('Location: ' . BASE_URL . '/login/forgot-password.php');
    exit;
}

$step = 'email';
$session = smsForgotSessionGet();
if ($session) {
    if ($session['step'] === 'password') {
        if ($session['verified_at'] > 0 && (time() - $session['verified_at']) <= SMS_FORGOT_PW_WINDOW_SEC) {
            $step = 'password';
        } else {
            smsForgotSessionClear();
            $error = 'Password reset session expired. Request a new code.';
            $step = 'email';
        }
    } elseif ($session['step'] === 'otp') {
        $step = 'otp';
        $emailValue = $session['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $action = (string) ($_POST['forgot_action'] ?? 'send_otp');

        /* ── 1) Send OTP ─────────────────────────────────────── */
        if ($action === 'send_otp' || $action === 'resend_otp') {
            $needCaptcha = ($action === 'send_otp');
            if ($needCaptcha) {
                $captcha = smsCaptchaVerifyRequest();
                if (empty($captcha['ok'])) {
                    $error = $captcha['error'] !== ''
                        ? $captcha['error']
                        : 'Please complete the CAPTCHA before continuing.';
                    $emailValue = trim((string) ($_POST['email'] ?? ''));
                    $step = 'email';
                }
            }

            if ($error === '') {
                if ($action === 'resend_otp') {
                    $session = smsForgotSessionGet();
                    if (!$session || $session['step'] !== 'otp') {
                        $error = 'Session expired. Enter your email again.';
                        $step = 'email';
                        smsForgotSessionClear();
                    } else {
                        $email = $session['email'];
                    }
                } else {
                    $email = trim((string) ($_POST['email'] ?? ''));
                    $emailValue = $email;
                }

                if ($error === '') {
                    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $error = 'Please enter a valid email address.';
                        $step = 'email';
                    } else {
                        $user = smsFindUserByEmailExact($email);
                        if (!$user) {
                            // Anti-enumeration: same reply, stay on email step
                            $message = 'If that email is registered, a 6-digit code has been sent. Check your inbox and spam folder.';
                            $step = 'email';
                            logActivity(
                                'password_reset_request',
                                'Forgot password OTP — email not matched',
                                'System',
                                null,
                                'Unknown',
                                null,
                                false
                            );
                        } else {
                            $status = strtolower(trim((string) ($user['status'] ?? '')));
                            $userId = (int) $user['id'];
                            $accountEmail = trim((string) ($user['email'] ?? ''));

                            if (!in_array($status, ['active', 'locked'], true) || $accountEmail === '') {
                                $message = 'If that email is registered, a 6-digit code has been sent. Check your inbox and spam folder.';
                                $step = 'email';
                                logActivity(
                                    'password_reset_request',
                                    'Forgot password OTP — account not eligible',
                                    'System',
                                    $userId,
                                    (string) $user['full_name'],
                                    (string) $user['role_key'],
                                    false
                                );
                            } else {
                                $issued = smsIssueOtpToEmail(
                                    $userId,
                                    SMS_FORGOT_OTP_PURPOSE,
                                    'login-forgot',
                                    SMS_FORGOT_OTP_TTL_MIN,
                                    'password reset'
                                );

                                if (empty($issued['ok'])) {
                                    $error = (string) ($issued['error'] !== '' ? $issued['error'] : 'Could not send verification code. Try again.');
                                    $step = $action === 'resend_otp' ? 'otp' : 'email';
                                    if ($action === 'resend_otp' && $session) {
                                        $emailValue = $session['email'];
                                    }
                                } elseif (empty($issued['emailed']) && empty($issued['show_local'])) {
                                    $error = 'Could not send the code to your email'
                                        . ($issued['error'] !== '' ? ' (' . $issued['error'] . ')' : '')
                                        . '. Configure SMTP App Password in System Settings, then try again.';
                                    $step = 'email';
                                    logActivity(
                                        'password_reset_request',
                                        'Forgot password OTP email failed: ' . ($issued['error'] ?? ''),
                                        'System',
                                        $userId,
                                        (string) $user['full_name'],
                                        (string) $user['role_key'],
                                        false
                                    );
                                } else {
                                    $expiresAt = time() + (SMS_FORGOT_OTP_TTL_MIN * 60);
                                    smsForgotSessionSet([
                                        'user_id' => $userId,
                                        'email' => $accountEmail,
                                        'step' => 'otp',
                                        'otp_expires_at' => $expiresAt,
                                        'verified_at' => 0,
                                    ]);
                                    $step = 'otp';
                                    $emailValue = $accountEmail;
                                    if (!empty($issued['show_local']) && !empty($issued['code'])) {
                                        $devOtpCode = (string) $issued['code'];
                                        $message = 'Email could not be sent (' . ($issued['error'] ?? 'SMTP error') . '). '
                                            . 'Use the code below (dev fallback enabled).';
                                    } else {
                                        $message = 'We sent a 6-digit code to ' . $accountEmail . '. It expires in 2 minutes.';
                                    }
                                    logActivity(
                                        'password_reset_request',
                                        !empty($issued['emailed'])
                                            ? 'Forgot password OTP emailed'
                                            : 'Forgot password OTP generated (email failed, local shown)',
                                        'System',
                                        $userId,
                                        (string) $user['full_name'],
                                        (string) $user['role_key'],
                                        !empty($issued['emailed'])
                                    );
                                    // Fresh GET after POST
                                    if ($devOtpCode !== '') {
                                        $_SESSION['flash_forgot_otp'] = $devOtpCode;
                                    }
                                    $_SESSION['flash_forgot_msg'] = $message;
                                    header('Location: ' . BASE_URL . '/login/forgot-password.php');
                                    exit;
                                }
                            }
                        }
                    }
                }
            }
        }

        /* ── 2) Verify OTP ───────────────────────────────────── */
        if ($action === 'verify_otp') {
            $session = smsForgotSessionGet();
            if (!$session || $session['step'] !== 'otp') {
                $error = 'Session expired. Enter your email again.';
                $step = 'email';
                smsForgotSessionClear();
            } else {
                $userId = $session['user_id'];
                $emailValue = $session['email'];
                $step = 'otp';
                $otp = preg_replace('/\D+/', '', (string) ($_POST['otp_code'] ?? '')) ?? '';

                if ($session['otp_expires_at'] > 0 && time() > $session['otp_expires_at']) {
                    smsClearCodeGate($userId, SMS_FORGOT_OTP_PURPOSE);
                    smsForgotSessionClear();
                    $error = 'Code expired (2 minutes). Request a new code.';
                    $step = 'email';
                    $emailValue = $session['email'];
                } else {
                    $gate = smsGetCodeGate($userId, SMS_FORGOT_OTP_PURPOSE);
                    if (!empty($gate['locked'])) {
                        $error = $gate['message'];
                    } elseif (!preg_match('/^\d{6}$/', $otp)) {
                        $error = 'Enter the 6-digit code from your email.';
                    } elseif (!smsVerifyOtp($userId, SMS_FORGOT_OTP_PURPOSE, $otp)) {
                        $gate = smsRegisterCodeFailure($userId, SMS_FORGOT_OTP_PURPOSE);
                        $error = function_exists('smsCodeFailureMessage')
                            ? smsCodeFailureMessage($gate, 'code')
                            : 'Invalid or expired code. Try again.';
                    } else {
                        smsClearCodeGate($userId, SMS_FORGOT_OTP_PURPOSE);
                        smsForgotSessionSet([
                            'user_id' => $userId,
                            'email' => $session['email'],
                            'step' => 'password',
                            'otp_expires_at' => 0,
                            'verified_at' => time(),
                        ]);
                        $_SESSION['flash_forgot_msg'] = 'Code verified. Set your new password.';
                        header('Location: ' . BASE_URL . '/login/forgot-password.php');
                        exit;
                    }
                }
            }
        }

        /* ── 3) Set new password ─────────────────────────────── */
        if ($action === 'set_password') {
            $session = smsForgotSessionGet();
            if (
                !$session
                || $session['step'] !== 'password'
                || $session['verified_at'] <= 0
                || (time() - $session['verified_at']) > SMS_FORGOT_PW_WINDOW_SEC
            ) {
                smsForgotSessionClear();
                $error = 'Password reset session expired. Request a new code.';
                $step = 'email';
            } else {
                $password = (string) ($_POST['password'] ?? '');
                $confirm = (string) ($_POST['password_confirm'] ?? '');
                $strength = smsValidatePasswordStrength($password);
                if (!$strength['ok']) {
                    $error = $strength['message'];
                    $step = 'password';
                } elseif ($password !== $confirm) {
                    $error = 'Passwords do not match.';
                    $step = 'password';
                } elseif (!smsForgotApplyNewPassword($session['user_id'], $password)) {
                    $error = 'Could not update password. Try again.';
                    $step = 'password';
                } else {
                    $uid = $session['user_id'];
                    logActivity(
                        'password_change',
                        'Password reset via forgot-password OTP',
                        'System',
                        $uid
                    );
                    smsForgotSessionClear();
                    header('Location: ' . BASE_URL . '/login/login.php?reset=1');
                    exit;
                }
            }
        }
    }
}

// Flash after redirect
if (!empty($_SESSION['flash_forgot_msg'])) {
    $message = (string) $_SESSION['flash_forgot_msg'];
    unset($_SESSION['flash_forgot_msg']);
}
if (!empty($_SESSION['flash_forgot_otp'])) {
    $devOtpCode = (string) $_SESSION['flash_forgot_otp'];
    unset($_SESSION['flash_forgot_otp']);
}

$session = smsForgotSessionGet();
if ($session) {
    if ($session['step'] === 'password' && $step === 'password') {
        $emailValue = $session['email'];
    } elseif ($session['step'] === 'otp') {
        $step = 'otp';
        $emailValue = $session['email'];
    }
}

$otpExpiresAt = ($session && $session['step'] === 'otp') ? (int) $session['otp_expires_at'] : 0;
$otpRemaining = $otpExpiresAt > 0 ? max(0, $otpExpiresAt - time()) : 0;

$pageTitle = 'Forgot Password';
$bodyClass = 'login-page forgot-page';
require_once ROOT_PATH . '/includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/auth-transition.css?v=8">
<style>
body.login-page.forgot-page {
    --login-font: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    background: #071c48 !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    overflow-x: hidden;
    color-scheme: light !important;
    position: relative;
    font-family: var(--login-font);
    transition: none !important;
}

.forgot-video-bg {
    position: fixed;
    inset: 0;
    z-index: 0;
    overflow: hidden;
    pointer-events: none;
    background:
        radial-gradient(ellipse 90% 70% at 15% 10%, rgba(96, 165, 250, 0.22) 0%, transparent 55%),
        radial-gradient(ellipse 70% 55% at 85% 85%, rgba(83, 80, 214, 0.18) 0%, transparent 50%),
        linear-gradient(145deg, #051637 0%, #0b2a6b 42%, #1a6fc4 100%);
}

.forgot-video-bg::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 75% 60% at 50% 42%, rgba(4, 16, 42, 0.12) 0%, rgba(4, 16, 42, 0.42) 75%, rgba(2, 8, 24, 0.62) 100%),
        linear-gradient(180deg, rgba(4, 16, 42, 0.28) 0%, rgba(4, 16, 42, 0.18) 45%, rgba(2, 8, 24, 0.55) 100%);
}

.forgot-stage {
    position: relative;
    z-index: 1;
    width: 100%;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1.25rem;
    box-sizing: border-box;
}

.forgot-glass {
    width: min(360px, 100%);
    padding: 1.45rem 1.3rem 1.25rem;
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.38);
    background: rgba(248, 250, 252, 0.96);
    box-shadow: 0 28px 64px rgba(2, 10, 30, 0.42);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
    isolation: isolate;
    transform: translateZ(0);
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.forgot-brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    margin-bottom: 0.55rem;
}

.forgot-brand img {
    width: 82px;
    height: 82px;
    object-fit: contain;
    filter: drop-shadow(0 8px 16px rgba(11, 42, 107, 0.18));
}

.forgot-glass h1 {
    margin: 0 0 0.4rem;
    color: #0f172a;
    font-size: 1.65rem;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.02em;
    text-align: center;
}

.forgot-lead {
    margin: 0 0 1rem;
    color: #475569;
    font-size: 0.86rem;
    font-weight: 600;
    line-height: 1.45;
    text-align: center;
}

.forgot-glass .form-label {
    margin-bottom: 0.35rem;
    color: #1e293b;
    font-size: 0.84rem;
    font-weight: 700 !important;
}

.forgot-glass .form-control {
    min-height: 44px;
    border: 1px solid #94a3b8 !important;
    border-radius: 10px !important;
    background: rgba(255, 255, 255, 0.96) !important;
    color: #0f172a !important;
    padding: 0.6rem 0.85rem !important;
    font-size: 0.92rem;
    font-weight: 600;
    box-shadow: none !important;
}

.forgot-glass .form-control:focus {
    border-color: #5350d6 !important;
    box-shadow: 0 0 0 3px rgba(83, 80, 214, 0.18) !important;
}

.forgot-glass .form-control.is-invalid {
    border-color: #e11d48 !important;
    background: rgba(255, 241, 242, 0.98) !important;
}

.forgot-otp-boxes {
    display: flex;
    justify-content: space-between;
    gap: 0.4rem;
    margin: 0.15rem 0 0.15rem;
}

.forgot-otp-digit {
    width: 100%;
    max-width: 48px;
    min-height: 52px !important;
    padding: 0.35rem 0 !important;
    text-align: center !important;
    font-size: 1.35rem !important;
    font-weight: 800 !important;
    font-family: Consolas, "Courier New", monospace !important;
    letter-spacing: 0 !important;
    border: 1.5px solid #a5b4fc !important;
    border-radius: 12px !important;
    background: linear-gradient(180deg, #ffffff 0%, #eef2ff 100%) !important;
    color: #0f172a !important;
    box-shadow: none !important;
    caret-color: #5350d6;
}

.forgot-otp-digit:focus {
    border-color: #5350d6 !important;
    box-shadow: 0 0 0 3px rgba(83, 80, 214, 0.2) !important;
    outline: none !important;
    background: #fff !important;
}

.forgot-otp-digit.is-filled {
    border-color: #6366f1 !important;
    background: #eef2ff !important;
}

.forgot-timer {
    margin: 0.55rem 0 0.85rem;
    text-align: center;
    font-size: 0.9rem;
    font-weight: 700;
    color: #4338ca;
    padding: 0.45rem 0.65rem;
    border-radius: 10px;
    background: rgba(238, 242, 255, 0.95);
    border: 1px solid #c7d2fe;
}

.forgot-timer.is-expired {
    color: #be123c;
    background: rgba(255, 241, 242, 0.95);
    border-color: #fecdd3;
}

.forgot-otp-box {
    margin-top: 0.65rem;
    padding: 0.85rem 0.95rem;
    border-radius: 14px;
    border: 1px solid #c7d2fe;
    background: linear-gradient(180deg, #eef2ff 0%, #e0e7ff 100%);
    color: #1e3a8a;
    font-size: 0.9rem;
    font-weight: 700;
    text-align: center;
    box-shadow: 0 10px 24px rgba(83, 80, 214, 0.12);
}

.forgot-otp-box code {
    display: inline-block;
    margin-top: 0.25rem;
    font-size: 1.45rem;
    letter-spacing: 0.28em;
    font-weight: 800;
    font-family: Consolas, "Courier New", monospace;
    color: #0f172a;
}

.forgot-step-pills {
    display: flex;
    justify-content: center;
    gap: 0.4rem;
    margin: 0 0 0.85rem;
}

.forgot-step-pills span {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #cbd5e1;
}

.forgot-step-pills span.is-active {
    width: 22px;
    background: #5350d6;
}

.forgot-glass .btn-auth-primary {
    width: 100%;
    min-height: 44px;
    border: 0 !important;
    border-radius: 999px !important;
    background: #5350d6 !important;
    color: #fff !important;
    padding: 0.65rem 1rem !important;
    font-size: 0.92rem;
    font-weight: 800;
    box-shadow: 0 8px 18px rgba(83, 80, 214, 0.25) !important;
}

.forgot-glass .btn-auth-primary:hover,
.forgot-glass .btn-auth-primary:focus {
    background: #4542c4 !important;
}

.forgot-glass .btn-auth-secondary {
    width: 100%;
    min-height: 40px;
    margin-top: 0.5rem;
    border: 1px solid #c7d2fe !important;
    border-radius: 999px !important;
    background: #eef2ff !important;
    color: #3730a3 !important;
    font-size: 0.88rem;
    font-weight: 700;
}

.forgot-glass .btn-auth-secondary:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.forgot-links {
    margin-top: 1rem;
    text-align: center;
}

.forgot-links a {
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
    border-radius: 8px;
}

.forgot-links a:hover,
.forgot-links a:focus-visible {
    color: #312e81;
    text-decoration: underline;
    background: rgba(67, 56, 202, 0.08);
    outline: none;
}

.forgot-glass .alert {
    border-radius: 12px;
    font-size: 0.88rem;
    font-weight: 600;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
}

.forgot-footer {
    position: relative;
    z-index: 1;
    padding: 0 1rem 1.15rem;
    color: rgba(255, 255, 255, 0.92);
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    text-shadow: 0 2px 12px rgba(0, 0, 0, 0.45);
}

.forgot-glass .sms-captcha-wrap {
    margin-bottom: 0.75rem !important;
    width: 100%;
    max-width: 100%;
}

.forgot-glass .sms-captcha-label {
    color: #0f172a !important;
    font-size: 0.78rem !important;
    font-weight: 800 !important;
    margin-bottom: 0.3rem !important;
}

.forgot-glass .sms-cf-widget,
.forgot-glass .sms-captcha-frame:not(.sms-captcha-frame--turnstile),
html[data-theme="dark"] .forgot-glass .sms-cf-widget,
html[data-theme="dark"] .forgot-glass .sms-captcha-frame:not(.sms-captcha-frame--turnstile) {
    width: 100% !important;
    max-width: 100% !important;
    min-height: 0 !important;
    border: 1px solid #94a3b8 !important;
    border-radius: 9px !important;
    background: #fff !important;
    color: #0f172a !important;
    box-shadow: none !important;
    box-sizing: border-box !important;
}

.forgot-glass .sms-captcha-frame--turnstile,
html[data-theme="dark"] .forgot-glass .sms-captcha-frame--turnstile {
    width: 100% !important;
    max-width: 100% !important;
    min-height: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
}

.forgot-glass .sms-cf-widget {
    display: flex !important;
    align-items: center !important;
    min-height: 38px !important;
    padding: 0.45rem 0.65rem !important;
    gap: 0.5rem !important;
}

.forgot-glass .sms-cf-label,
html[data-theme="dark"] .forgot-glass .sms-cf-label {
    color: #0f172a !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
}

.forgot-glass .sms-cf-widget.is-verified,
html[data-theme="dark"] .forgot-glass .sms-cf-widget.is-verified {
    border-color: rgba(34, 197, 94, 0.55) !important;
    background: #f0fdf4 !important;
}

@media (prefers-reduced-motion: reduce) {
    .forgot-video-bg { background: #071c48; }
}
</style>

<div class="forgot-video-bg" aria-hidden="true"></div>

<main class="forgot-stage">
    <section class="forgot-glass" aria-label="Forgot password">
        <div class="forgot-brand">
            <img src="<?= e(smsBrandLogoUrl()) ?>?v=crest3" alt="Bestlink College of the Philippines" width="82" height="82">
        </div>
        <div class="forgot-step-pills" aria-hidden="true">
            <span class="<?= $step === 'email' ? 'is-active' : '' ?>"></span>
            <span class="<?= $step === 'otp' ? 'is-active' : '' ?>"></span>
            <span class="<?= $step === 'password' ? 'is-active' : '' ?>"></span>
        </div>

        <?php if ($step === 'email'): ?>
            <h1>Forgot password</h1>
            <p class="forgot-lead">Enter the email linked to your BCP account. We will send a 6-digit code (valid for 2 minutes).</p>
        <?php elseif ($step === 'otp'): ?>
            <h1>Enter code</h1>
            <p class="forgot-lead">We sent a code to <strong><?= e($emailValue) ?></strong>. Enter it below before it expires.</p>
        <?php else: ?>
            <h1>New password</h1>
            <p class="forgot-lead">Code verified for <strong><?= e($emailValue) ?></strong>. Choose a strong new password.</p>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success mb-2"><?= e($message) ?></div>
        <?php endif; ?>
        <?php if ($devOtpCode !== '' && $step === 'otp'): ?>
            <div class="forgot-otp-box" role="status">
                <div style="margin-bottom:.25rem;">Your OTP code</div>
                <code><?= e($devOtpCode) ?></code>
            </div>
        <?php endif; ?>

        <?php if ($step === 'email'): ?>
            <form method="POST" class="mt-3" novalidate id="forgotForm">
                <?= csrfField() ?>
                <input type="hidden" name="forgot_action" value="send_otp">
                <div class="mb-3">
                    <label class="form-label" for="email">Email <span style="color:#dc2626;font-weight:700">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required
                           placeholder="you@gmail.com" value="<?= e($emailValue) ?>"
                           autocomplete="email" autofocus>
                </div>
                <?= smsCaptchaMarkup() ?>
                <button type="submit" class="btn btn-auth-primary w-100" id="forgotSubmitBtn" disabled>
                    <?= smsIcon('envelope-open-text', ['class' => 'me-2']) ?>Send code
                </button>
                <div class="forgot-links">
                    <a href="<?= BASE_URL ?>/login/login.php" data-auth-transition data-auth-direction="right"><?= smsIcon('arrow-left', ['aria-hidden' => 'true']) ?>Back to sign in</a>
                </div>
            </form>

        <?php elseif ($step === 'otp'): ?>
            <form method="POST" class="mt-3" novalidate id="otpForm">
                <?= csrfField() ?>
                <input type="hidden" name="forgot_action" value="verify_otp">
                <input type="hidden" name="otp_code" id="otp_code" value="">
                <div class="mb-2">
                    <label class="form-label" for="otp_d0">6-digit code</label>
                    <div class="forgot-otp-boxes" role="group" aria-label="6-digit verification code">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <input type="text"
                                   class="form-control forgot-otp-digit"
                                   id="otp_d<?= $i ?>"
                                   inputmode="numeric"
                                   maxlength="1"
                                   autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
                                   aria-label="Digit <?= $i + 1 ?>"
                                   <?= $otpRemaining <= 0 ? 'disabled' : '' ?>
                                   <?= $i === 0 ? 'autofocus' : '' ?>>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="forgot-timer<?= $otpRemaining <= 0 ? ' is-expired' : '' ?>" id="otpTimer"
                     data-expires="<?= (int) $otpExpiresAt ?>">
                    <?php if ($otpRemaining > 0): ?>
                        Code expires in <span id="otpCountdown"><?= sprintf('%d:%02d', intdiv($otpRemaining, 60), $otpRemaining % 60) ?></span>
                    <?php else: ?>
                        Code expired — request a new one
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-auth-primary w-100" id="otpSubmitBtn" <?= $otpRemaining <= 0 ? 'disabled' : '' ?>>
                    <?= smsIcon('shield-alt', ['class' => 'me-2']) ?>Verify code
                </button>
            </form>
            <form method="POST" class="mt-1" id="resendForm">
                <?= csrfField() ?>
                <input type="hidden" name="forgot_action" value="resend_otp">
                <button type="submit" class="btn btn-auth-secondary" id="resendBtn" <?= $otpRemaining > 0 ? 'disabled' : '' ?>>
                    Resend code
                </button>
            </form>
            <div class="forgot-links">
                <a href="<?= BASE_URL ?>/login/forgot-password.php?cancel=1">Use a different email</a>
            </div>

        <?php else: ?>
            <form method="POST" class="mt-3" novalidate>
                <?= csrfField() ?>
                <input type="hidden" name="forgot_action" value="set_password">
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <?= smsPasswordInput(['id' => 'password', 'name' => 'password', 'required' => true, 'minlength' => $minLen, 'autocomplete' => 'new-password']) ?>
                    <?= smsPasswordStrengthMarkup('password') ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirm">Confirm password</label>
                    <?= smsPasswordInput(['id' => 'password_confirm', 'name' => 'password_confirm', 'required' => true, 'minlength' => $minLen, 'autocomplete' => 'new-password']) ?>
                </div>
                <button type="submit" class="btn btn-auth-primary w-100">
                    <?= smsIcon('check', ['class' => 'me-2']) ?>Update password
                </button>
                <div class="forgot-links">
                    <a href="<?= BASE_URL ?>/login/forgot-password.php?cancel=1">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>

<footer class="forgot-footer">
    <p class="mb-0">&copy; 2026 Bestlink College of the Philippines. All rights reserved.</p>
</footer>
<script src="<?= BASE_URL ?>/assets/js/auth-transition.js?v=8"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var emailForm = document.getElementById('forgotForm');
    var email = document.getElementById('email');
    var submitBtn = document.getElementById('forgotSubmitBtn');
    if (emailForm && email && submitBtn) {
        function isValidEmail(value) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        }
        function syncSendEnabled() {
            var value = email.value.trim();
            var captchaOk = document.getElementById('smsCaptchaOk');
            var captchaWidget = document.getElementById('smsCaptchaWidget');
            var captchaReady = true;
            if (captchaWidget && captchaOk) {
                captchaReady = captchaOk.value === '1';
            }
            submitBtn.disabled = !(isValidEmail(value) && captchaReady);
        }
        email.addEventListener('input', syncSendEnabled);
        document.addEventListener('sms-captcha-ok', syncSendEnabled);
        var captchaPoll = 0;
        var captchaTimer = setInterval(function () {
            syncSendEnabled();
            captchaPoll += 1;
            if (captchaPoll > 40) clearInterval(captchaTimer);
        }, 500);
        emailForm.addEventListener('submit', function (e) {
            if (submitBtn.disabled) {
                e.preventDefault();
                return;
            }
            submitBtn.disabled = true;
        });
        syncSendEnabled();
    }

    var otpHidden = document.getElementById('otp_code');
    var otpDigits = Array.prototype.slice.call(document.querySelectorAll('.forgot-otp-digit'));
    var otpForm = document.getElementById('otpForm');
    var otpSubmitBtn = document.getElementById('otpSubmitBtn');
    var resendBtn = document.getElementById('resendBtn');

    function syncOtpHidden() {
        if (!otpHidden) return '';
        var code = otpDigits.map(function (el) { return (el.value || '').replace(/\D/g, '').slice(0, 1); }).join('');
        otpHidden.value = code;
        otpDigits.forEach(function (el) {
            if ((el.value || '').trim() !== '') el.classList.add('is-filled');
            else el.classList.remove('is-filled');
        });
        return code;
    }

    function fillOtpFromString(raw) {
        var digits = String(raw || '').replace(/\D/g, '').slice(0, 6).split('');
        otpDigits.forEach(function (el, i) {
            el.value = digits[i] || '';
        });
        syncOtpHidden();
        var nextIdx = Math.min(digits.length, otpDigits.length - 1);
        if (otpDigits[nextIdx]) otpDigits[nextIdx].focus();
    }

    if (otpDigits.length === 6) {
        otpDigits.forEach(function (el, idx) {
            el.addEventListener('input', function () {
                var v = (el.value || '').replace(/\D/g, '');
                if (v.length > 1) {
                    // Paste into one box or autofill dump
                    fillOtpFromString(v);
                    return;
                }
                el.value = v.slice(0, 1);
                syncOtpHidden();
                if (el.value && idx < otpDigits.length - 1) {
                    otpDigits[idx + 1].focus();
                }
            });
            el.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !el.value && idx > 0) {
                    otpDigits[idx - 1].focus();
                    otpDigits[idx - 1].value = '';
                    syncOtpHidden();
                    e.preventDefault();
                } else if (e.key === 'ArrowLeft' && idx > 0) {
                    otpDigits[idx - 1].focus();
                    e.preventDefault();
                } else if (e.key === 'ArrowRight' && idx < otpDigits.length - 1) {
                    otpDigits[idx + 1].focus();
                    e.preventDefault();
                }
            });
            el.addEventListener('paste', function (e) {
                e.preventDefault();
                var text = (e.clipboardData || window.clipboardData).getData('text') || '';
                fillOtpFromString(text);
            });
            el.addEventListener('focus', function () {
                el.select();
            });
        });
        syncOtpHidden();
    }

    if (otpForm) {
        otpForm.addEventListener('submit', function (e) {
            var code = syncOtpHidden();
            if (code.length !== 6) {
                e.preventDefault();
                if (otpDigits[0]) otpDigits[0].focus();
                return;
            }
            if (otpSubmitBtn) otpSubmitBtn.disabled = true;
        });
    }

    var timerEl = document.getElementById('otpTimer');
    var countdownEl = document.getElementById('otpCountdown');
    if (timerEl) {
        var expires = parseInt(timerEl.getAttribute('data-expires') || '0', 10);
        var expiredDone = false;
        function setDigitsDisabled(disabled) {
            otpDigits.forEach(function (el) { el.disabled = disabled; });
        }
        function tick() {
            var left = Math.max(0, expires - Math.floor(Date.now() / 1000));
            if (left <= 0) {
                if (!expiredDone) {
                    expiredDone = true;
                    timerEl.classList.add('is-expired');
                    timerEl.textContent = 'Code expired — request a new one';
                    if (otpSubmitBtn) otpSubmitBtn.disabled = true;
                    if (resendBtn) resendBtn.disabled = false;
                    setDigitsDisabled(true);
                }
                return;
            }
            var m = Math.floor(left / 60);
            var s = left % 60;
            if (countdownEl) {
                countdownEl.textContent = m + ':' + String(s).padStart(2, '0');
            }
            if (resendBtn) resendBtn.disabled = true;
            setTimeout(tick, 250);
        }
        tick();
    }
});
</script>
<?php require_once ROOT_PATH . '/includes/scripts.php'; ?>
