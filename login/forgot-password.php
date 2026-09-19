<?php
/**
 * SMS 2 – Forgot password (email reset link only for registered accounts)
 */
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/mail.php';
require_once ROOT_PATH . '/includes/captcha.php';
require_once ROOT_PATH . '/includes/module-controls.php';

if (smsIsSystemInMaintenance()) {
    header('Location: ' . BASE_URL . '/account/maintenance.php');
    exit;
}

if (isAuthenticated()) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$message = '';
$error = '';
$emailValue = '';

/**
 * Find user by email address only (not username / student ID).
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
         FROM users u
         LEFT JOIN roles r ON r.role_key = u.role_key
         WHERE LOWER(u.email) = ?
         LIMIT 1'
    );
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Same public reply always — never reveal if the email exists. */
function smsForgotPasswordGenericMessage(): string
{
    return 'If that email is registered in the system, a password reset link has been sent. '
        . 'Please check your inbox and spam folder. If you do not receive a message, contact your administrator.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $captcha = smsCaptchaVerifyRequest();
        if (empty($captcha['ok'])) {
            $error = $captcha['error'] !== ''
                ? $captcha['error']
                : 'Please complete the CAPTCHA before continuing.';
            $emailValue = trim((string) ($_POST['email'] ?? ''));
        } else {
            $email = trim((string) ($_POST['email'] ?? ''));
            $emailValue = $email;

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                // Always show the same success-style note (anti-enumeration).
                // Only create/send a token when the account is eligible.
                $user = smsFindUserByEmailExact($email);
                $message = smsForgotPasswordGenericMessage();

                if ($user) {
                    $status = strtolower(trim((string) ($user['status'] ?? '')));
                    $accountEmail = trim((string) ($user['email'] ?? ''));
                    $sendTo = $accountEmail !== '' ? $accountEmail : $email;

                    if (in_array($status, ['active', 'locked'], true) && $sendTo !== '') {
                        $token = smsCreatePasswordResetToken((int) $user['id']);
                        if ($token) {
                            $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
                                . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                                . BASE_URL . '/login/reset-password.php?token=' . urlencode($token);

                            $sent = smsSendPasswordResetEmail($user, $resetUrl, $sendTo);
                            logActivity(
                                'password_reset_request',
                                !empty($sent['ok'])
                                    ? 'Password reset link emailed'
                                    : 'Password reset email failed: ' . ($sent['error'] ?? 'unknown'),
                                'System',
                                (int) $user['id'],
                                (string) $user['full_name'],
                                (string) $user['role_key']
                            );
                        } else {
                            logActivity(
                                'password_reset_request',
                                'Password reset token could not be created',
                                'System',
                                (int) $user['id'],
                                (string) $user['full_name'],
                                (string) $user['role_key'],
                                false
                            );
                        }
                    } else {
                        logActivity(
                            'password_reset_request',
                            'Forgot password — account not eligible for reset (status/email)',
                            'System',
                            (int) $user['id'],
                            (string) $user['full_name'],
                            (string) $user['role_key'],
                            false
                        );
                    }
                } else {
                    logActivity(
                        'password_reset_request',
                        'Forgot password — email not matched (generic reply shown)',
                        'System',
                        null,
                        'Unknown',
                        null,
                        false
                    );
                }
            }
        }
    }
}

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
    /* Avoid Chrome white fringe from blur + radius over video */
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

.forgot-field-error {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.forgot-glass .form-control.is-invalid {
    border-color: #e11d48 !important;
    background: rgba(255, 241, 242, 0.98) !important;
    box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.14) !important;
}

.forgot-glass .form-control.is-invalid:focus {
    border-color: #e11d48 !important;
    background: #fff !important;
    box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.18) !important;
}

.forgot-glass .invalid-feedback,
.forgot-glass .valid-feedback {
    display: none !important;
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
    text-shadow: none;
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
    backdrop-filter: blur(12px) saturate(1.2);
    -webkit-backdrop-filter: blur(12px) saturate(1.2);
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
}

.forgot-setup-hint {
    margin-top: 0.75rem;
    padding: 0.85rem 0.95rem;
    border-radius: 12px;
    border: 1px solid #fdba74;
    background: rgba(255, 247, 237, 0.95);
    color: #9a3412;
    font-size: 0.86rem;
    line-height: 1.45;
}

.forgot-setup-hint ol {
    margin: 0.4rem 0 0;
    padding-left: 1.2rem;
}

.forgot-setup-hint code {
    font-size: 0.8rem;
}

.forgot-reset-box {
    margin-top: 0.65rem;
    padding: 0.75rem 0.85rem;
    border-radius: 12px;
    border: 1px solid #93c5fd;
    background: rgba(239, 246, 255, 0.95);
    color: #1e3a8a;
    font-size: 0.84rem;
    font-weight: 600;
    word-break: break-all;
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
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    box-sizing: border-box !important;
}

.forgot-glass .sms-captcha-frame--turnstile,
html[data-theme="dark"] .forgot-glass .sms-captcha-frame--turnstile {
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

.forgot-glass .sms-cf-widget {
    display: flex !important;
    align-items: center !important;
    min-height: 38px !important;
    padding: 0.45rem 0.65rem !important;
    gap: 0.5rem !important;
}

.forgot-glass .sms-captcha-frame--turnstile .cf-turnstile {
    width: 300px !important;
    max-width: 100% !important;
    transform: none !important;
}

.forgot-glass .sms-cf-widget:hover,
.forgot-glass .sms-cf-widget:focus-visible,
.forgot-glass .sms-cf-widget:active,
.forgot-glass .sms-cf-widget.is-loading {
    border-color: #5350d6 !important;
    background: #fff !important;
    color: #0f172a !important;
}

.forgot-glass .sms-cf-label,
html[data-theme="dark"] .forgot-glass .sms-cf-label {
    color: #0f172a !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
}

.forgot-glass .sms-cf-box,
html[data-theme="dark"] .forgot-glass .sms-cf-box {
    width: 1.15rem !important;
    height: 1.15rem !important;
    background: #fff !important;
    border-color: #64748b !important;
}

.forgot-glass .sms-cf-brand,
html[data-theme="dark"] .forgot-glass .sms-cf-brand {
    border: 1px solid rgba(83, 80, 214, 0.25) !important;
    background: rgba(238, 242, 255, 0.95) !important;
    color: #4338ca !important;
    font-size: 0.65rem !important;
}

.forgot-glass .sms-cf-widget.is-verified,
html[data-theme="dark"] .forgot-glass .sms-cf-widget.is-verified {
    border-color: rgba(34, 197, 94, 0.55) !important;
    background: #f0fdf4 !important;
}

@media (prefers-reduced-motion: reduce) {
    .forgot-video-bg {
        background: #071c48;
    }
}
</style>

<div class="forgot-video-bg" aria-hidden="true"></div>

<main class="forgot-stage">
    <section class="forgot-glass" aria-label="Forgot password">
        <div class="forgot-brand">
            <img src="<?= e(smsBrandLogoUrl()) ?>?v=crest3" alt="Bestlink College of the Philippines" width="82" height="82">
        </div>
        <h1>Forgot password</h1>
        <p class="forgot-lead">Enter the email linked to your BCP account. If it is registered, we will email a password reset link.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-success mb-2"><?= e($message) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-3" novalidate id="forgotForm">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label" for="email">Email <span class="login-req" style="color:#dc2626;font-weight:700">*</span></label>
                <input type="email" class="form-control" id="email" name="email" required
                       placeholder="you@gmail.com" value="<?= e($emailValue) ?>"
                       autocomplete="email" autofocus
                       aria-describedby="forgotEmailError">
                <div class="forgot-field-error" id="forgotEmailError" role="alert">Email is required.</div>
            </div>
            <?= smsCaptchaMarkup() ?>
            <button type="submit" class="btn btn-auth-primary w-100" id="forgotSubmitBtn" disabled>
                <?= smsIcon('envelope-open-text', ['class' => 'me-2']) ?>Send reset link
            </button>
            <div class="forgot-links">
                <a href="<?= BASE_URL ?>/login/login.php" data-auth-transition data-auth-direction="right"><?= smsIcon('arrow-left', ['aria-hidden' => 'true']) ?>Back to sign in</a>
            </div>
        </form>
    </section>
</main>

<footer class="forgot-footer">
    <p class="mb-0">&copy; 2026 Bestlink College of the Philippines. All rights reserved.</p>
</footer>
<script src="<?= BASE_URL ?>/assets/js/auth-transition.js?v=8"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('forgotForm');
    var email = document.getElementById('email');
    var errorEl = document.getElementById('forgotEmailError');
    var submitBtn = document.getElementById('forgotSubmitBtn');
    if (!form || !email || !submitBtn) return;

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function setEmailError(show, message) {
        if (!errorEl) return;
        if (show) {
            errorEl.textContent = message || 'Email is required.';
            errorEl.classList.add('is-visible');
            email.classList.add('is-invalid');
            email.setAttribute('aria-invalid', 'true');
        } else {
            errorEl.classList.remove('is-visible');
            email.classList.remove('is-invalid');
            email.removeAttribute('aria-invalid');
        }
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

    function validateEmail(show) {
        var value = email.value.trim();
        if (value === '') {
            if (show) setEmailError(true, 'Email is required.');
            return false;
        }
        if (!isValidEmail(value)) {
            if (show) setEmailError(true, 'Enter a valid email address.');
            return false;
        }
        setEmailError(false);
        return true;
    }

    email.addEventListener('input', function () {
        if (email.classList.contains('is-invalid') || (errorEl && errorEl.classList.contains('is-visible'))) {
            validateEmail(true);
        }
        syncSendEnabled();
    });
    // Do not show email errors on blur to random page areas — only on submit
    document.addEventListener('sms-captcha-ok', syncSendEnabled);
    var captchaPoll = 0;
    var captchaTimer = setInterval(function () {
        syncSendEnabled();
        captchaPoll += 1;
        if (captchaPoll > 40) clearInterval(captchaTimer);
    }, 500);

    form.addEventListener('submit', function (e) {
        if (!validateEmail(true) || submitBtn.disabled) {
            e.preventDefault();
            syncSendEnabled();
            return;
        }
        submitBtn.disabled = true;
    });

    syncSendEnabled();
});
</script>
<?php require_once ROOT_PATH . '/includes/scripts.php'; ?>
