<?php
/**
 * SMS 2 – Login step 2: Authenticator code or email OTP
 *
 * Mode toggle:
 *  - authenticator (default when enabled): enter app code; button → email
 *  - email: enter emailed code; button → switch back to authenticator
 *
 * Wrong codes / resends are rate-limited (temporary lock).
 */
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/totp.php';
require_once ROOT_PATH . '/includes/mail.php';
require_once ROOT_PATH . '/includes/security-workflow.php';
require_once ROOT_PATH . '/includes/security-ui.php';

if (isAuthenticated()) {
    header('Location: ' . smsPostLoginRedirectUrl());
    exit;
}

$pending = $_SESSION['pending_2fa'] ?? null;
if (!is_array($pending) || empty($pending['user_id']) || empty($pending['at']) || ((int) $pending['at'] + 600) < time()) {
    unset($_SESSION['pending_2fa']);
    $_SESSION['flash_login_error'] = 'Your sign-in session expired. Please sign in again.';
    header('Location: ' . BASE_URL . '/login/login.php');
    exit;
}

$userId = (int) $pending['user_id'];
$error = '';
$info = '';
$otpDev = '';
$purpose = 'login_2fa';

if (!empty($_SESSION['flash_2fa_error'])) {
    $error = (string) $_SESSION['flash_2fa_error'];
    unset($_SESSION['flash_2fa_error']);
}
if (!empty($_SESSION['flash_2fa_info'])) {
    $info = (string) $_SESSION['flash_2fa_info'];
    unset($_SESSION['flash_2fa_info']);
}
if (!empty($_SESSION['flash_2fa_otp'])) {
    $otpDev = (string) $_SESSION['flash_2fa_otp'];
    unset($_SESSION['flash_2fa_otp']);
}

$user = null;
$pdo = db();
if ($pdo) {
    $stmt = $pdo->prepare(
        'SELECT u.*, r.label AS role_label
         FROM `sms2_users` u LEFT JOIN `sms2_roles` r ON r.role_key = u.role_key
         WHERE u.id = ? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch() ?: null;
    if ($user && ($user['role_label'] ?? '') === '') {
        $user['role_label'] = (string) ($user['role_key'] ?? '');
    }
}
if (!$user) {
    unset($_SESSION['pending_2fa']);
    header('Location: ' . BASE_URL . '/login/login.php');
    exit;
}

$authEnabled = smsAuthenticatorIsEnabled($userId);

// Default method: authenticator when available, otherwise email
$method = strtolower(trim((string) ($_SESSION['pending_2fa']['method'] ?? '')));
if ($method !== 'authenticator' && $method !== 'email') {
    $method = $authEnabled ? 'authenticator' : 'email';
    $_SESSION['pending_2fa']['method'] = $method;
}
if ($method === 'authenticator' && !$authEnabled) {
    $method = 'email';
    $_SESSION['pending_2fa']['method'] = 'email';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $_SESSION['flash_2fa_error'] = 'Security check failed. Please try again.';
        header('Location: ' . BASE_URL . '/login/verify-2fa.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? 'verify');

    if ($action === 'use_email') {
        $_SESSION['pending_2fa']['method'] = 'email';
        $issued = smsIssueOtpToEmail($userId, $purpose, 'System', 10, 'login verification');
        if (!empty($issued['ok'])) {
            if (!empty($issued['show_local']) && !empty($issued['code'])) {
                $_SESSION['flash_2fa_otp'] = (string) $issued['code'];
            }
            $_SESSION['flash_2fa_info'] = !empty($issued['emailed'])
                ? 'A login code was emailed to ' . $issued['to'] . '. Enter that email code below.'
                : 'Could not email OTP' . ($issued['error'] !== '' ? ': ' . $issued['error'] : '')
                    . '. Use the on-screen code if shown.';
        } else {
            $_SESSION['flash_2fa_error'] = (string) ($issued['error'] !== '' ? $issued['error'] : 'Could not send email OTP.');
        }
        header('Location: ' . BASE_URL . '/login/verify-2fa.php');
        exit;
    }

    if ($action === 'use_authenticator') {
        if (!$authEnabled) {
            $_SESSION['flash_2fa_error'] = 'Authenticator is not set up on this account.';
            header('Location: ' . BASE_URL . '/login/verify-2fa.php');
            exit;
        }
        $_SESSION['pending_2fa']['method'] = 'authenticator';
        $_SESSION['flash_2fa_info'] = 'Enter the 6-digit code from your Authenticator app.';
        header('Location: ' . BASE_URL . '/login/verify-2fa.php');
        exit;
    }

    if ($action === 'cancel') {
        unset($_SESSION['pending_2fa']);
        header('Location: ' . BASE_URL . '/login/login.php');
        exit;
    }

    $codeGate = smsGetCodeGate($userId, $purpose);
    if (!empty($codeGate['locked'])) {
        $_SESSION['flash_2fa_error'] = $codeGate['message'];
        header('Location: ' . BASE_URL . '/login/verify-2fa.php');
        exit;
    }

    $code = trim((string) ($_POST['code'] ?? ''));
    $activeMethod = strtolower(trim((string) ($_SESSION['pending_2fa']['method'] ?? $method)));
    $ok = false;

    if ($activeMethod === 'email') {
        $ok = smsVerifyOtp($userId, $purpose, $code);
        if (!$ok) {
            $gate = smsRegisterCodeFailure($userId, $purpose);
            $_SESSION['flash_2fa_error'] = smsCodeFailureMessage($gate, 'email');
            header('Location: ' . BASE_URL . '/login/verify-2fa.php');
            exit;
        }
    } else {
        $ok = smsAuthenticatorVerifyLogin($userId, $code);
        if (!$ok) {
            $gate = smsRegisterCodeFailure($userId, $purpose);
            $_SESSION['flash_2fa_error'] = smsCodeFailureMessage($gate, 'authenticator');
            header('Location: ' . BASE_URL . '/login/verify-2fa.php');
            exit;
        }
    }

    smsClearCodeGate($userId, $purpose);

    $username = (string) ($pending['username'] ?? '');
    $result = smsCompleteLoginSession($user, $username);
    if (!empty($result['ok'])) {
        require_once ROOT_PATH . '/includes/module-controls.php';
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

    $_SESSION['flash_2fa_error'] = 'Could not complete sign-in.';
    header('Location: ' . BASE_URL . '/login/verify-2fa.php');
    exit;
}

$codeGate = smsGetCodeGate($userId, $purpose);
$isLocked = !empty($codeGate['locked']);
$isEmailMode = $method === 'email';
$pageTitle = 'Verify Sign In';
$bodyClass = 'login-page';
require_once ROOT_PATH . '/includes/header.php';
?>
<link href="<?= BASE_URL ?>/assets/css/auth-pages.css" rel="stylesheet">
<style>
body.login-page {
    background: #071c48 !important;
    background-image: none !important;
    position: relative;
}

.verify-video-bg {
    position: fixed;
    inset: 0;
    z-index: 0;
    overflow: hidden;
    pointer-events: none;
    background:
        radial-gradient(ellipse 90% 70% at 15% 10%, rgba(96, 165, 250, 0.22) 0%, transparent 55%),
        linear-gradient(145deg, #051637 0%, #0b2a6b 42%, #1a6fc4 100%);
}

.verify-video-bg::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 75% 60% at 50% 42%, rgba(4, 16, 42, 0.12) 0%, rgba(4, 16, 42, 0.42) 75%, rgba(2, 8, 24, 0.62) 100%),
        linear-gradient(180deg, rgba(4, 16, 42, 0.28) 0%, rgba(4, 16, 42, 0.18) 45%, rgba(2, 8, 24, 0.55) 100%);
}

.auth-stage {
    position: relative;
    z-index: 1;
}

.auth-footer {
    position: relative;
    z-index: 1;
}

@media (prefers-reduced-motion: reduce) {
    .verify-video-bg {
        background: #071c48;
    }
}
</style>

<div class="verify-video-bg" aria-hidden="true"></div>

<main class="auth-stage">
    <section class="auth-card" aria-label="Two-factor verification">
        <div class="auth-badge">
            <?php if ($isEmailMode): ?>
                <?= smsIcon('envelope') ?>
                <span>Email</span>
            <?php else: ?>
                <?= smsIcon('shield-alt') ?>
                <span>Authenticator</span>
            <?php endif; ?>
        </div>
        <h1>Verify it’s you</h1>
        <?php if ($isEmailMode): ?>
            <p class="auth-lead">
                Enter the 6-digit code we sent to your <strong>email</strong>.
                This is an email code — not your Authenticator app code.
            </p>
        <?php else: ?>
            <p class="auth-lead">
                Enter the 6-digit code from your <strong>Authenticator</strong> app
                (Google Authenticator / similar).
            </p>
        <?php endif; ?>

        <?php if ($info): ?>
            <div class="alert alert-info"><?= e($info) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php elseif ($isLocked): ?>
            <div class="alert alert-warning"><?= e($codeGate['message']) ?></div>
        <?php endif; ?>
        <?php if ($otpDev !== '' && !$isLocked): ?>
            <div class="alert alert-warning"><strong>Local OTP:</strong> <code><?= e($otpDev) ?></code></div>
        <?php endif; ?>

        <?php if ($isLocked): ?>
            <p class="text-muted small mb-3">
                Verification is temporarily locked after too many wrong codes.
                Please wait, then try again.
            </p>
            <fieldset disabled>
                <div class="mb-3">
                    <?= smsOtpInput('code', [
                        'id' => 'code',
                        'required' => false,
                        'label' => $isEmailMode ? 'Email code' : 'Authenticator code',
                        'hint' => 'Locked — try again later.',
                    ]) ?>
                </div>
                <button type="button" class="btn btn-secondary w-100 mb-2" disabled>
                    <?= smsIcon('lock', ['class' => 'me-2']) ?>Please try again later
                </button>
            </fieldset>
        <?php else: ?>
        <form method="POST" class="mt-3" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="action" value="verify">
            <div class="mb-3">
                <?= smsOtpInput('code', [
                    'id' => 'code',
                    'required' => true,
                    'autofocus' => true,
                    'label' => $isEmailMode ? 'Email code' : 'Authenticator code',
                    'hint' => $isEmailMode
                        ? 'Use the 6-digit code from your email inbox.'
                        : 'Use the 6-digit code from your Authenticator app.',
                ]) ?>
            </div>
            <button type="submit" class="btn btn-auth-primary w-100 mb-2">
                <?= smsIcon('check', ['class' => 'me-2']) ?>Verify &amp; sign in
            </button>
        </form>
        <?php endif; ?>

        <?php if (!$isLocked && $isEmailMode): ?>
            <?php if ($authEnabled): ?>
            <form method="POST" class="mb-2">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="use_authenticator">
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <?= smsIcon('mobile-alt', ['class' => 'me-2']) ?>Use Authenticator instead
                </button>
            </form>
            <?php endif; ?>
            <form method="POST" class="mb-2">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="use_email">
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <?= smsIcon('paper-plane', ['class' => 'me-2']) ?>Resend email code
                </button>
            </form>
        <?php elseif (!$isLocked): ?>
            <form method="POST" class="mb-2">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="use_email">
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <?= smsIcon('envelope', ['class' => 'me-2']) ?>Send code to my email instead
                </button>
            </form>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="btn btn-link w-100">Back to sign in</button>
        </form>
    </section>
</main>

<footer class="auth-footer">
    <p class="mb-0">&copy; 2026 Bestlink College of the Philippines. All rights reserved.</p>
</footer>
<?php require_once ROOT_PATH . '/includes/scripts.php'; ?>
