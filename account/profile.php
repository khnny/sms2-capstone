<?php
/**
 * SMS 2 – Super Admin Account Settings
 * Profile + Login Security (change/reset own password).
 * Not Module Security — admin personal account only.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security-workflow.php';
require_once ROOT_PATH . '/includes/authenticator-ui.php';
require_once ROOT_PATH . '/includes/passkey.php';
require_once ROOT_PATH . '/includes/security-ui.php';
requireAuth();
requireAdminAccountSettings();

smsEnsureSecurityTables();
smsEnsureAuthenticatorTable();

$userId = (int) getCurrentUserId();
$tab = (string) ($_GET['tab'] ?? 'profile');
if (!in_array($tab, ['profile', 'security'], true)) {
    $tab = 'profile';
}
$step = (string) ($_GET['step'] ?? '');
$minLen = (int) smsSetting('min_password_length', '8');
$baseUrl = BASE_URL . '/account/profile.php';

$error = '';
$success = '';
$otpDevCode = '';

if (!empty($_SESSION['flash_admin_success'])) {
    $success = (string) $_SESSION['flash_admin_success'];
    unset($_SESSION['flash_admin_success']);
}
if (!empty($_SESSION['flash_admin_error'])) {
    $error = (string) $_SESSION['flash_admin_error'];
    unset($_SESSION['flash_admin_error']);
}
if (!empty($_SESSION['flash_admin_otp'])) {
    $otpDevCode = (string) $_SESSION['flash_admin_otp'];
    unset($_SESSION['flash_admin_otp']);
}

/* ── Actions ───────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $_SESSION['flash_admin_error'] = 'Security check failed. Please refresh and try again.';
        header('Location: ' . $baseUrl . '?tab=' . urlencode($tab));
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');
    $pdo = db();

    $authActions = [
        'auth_turn_on', 'auth_request_setup', 'auth_send_setup_otp', 'auth_confirm_enable',
        'auth_cancel_setup', 'auth_disable', 'auth_turn_off_start', 'auth_disable_show_code', 'auth_disable_cancel',
    ];
    if (in_array($action, $authActions, true)) {
        $result = smsHandleAuthenticatorPost($userId, $action, $_POST, (string) ($_SESSION['user_email'] ?? ''));
        if ($result['success'] !== '') {
            $_SESSION['flash_admin_success'] = $result['success'];
        }
        if ($result['error'] !== '') {
            $_SESSION['flash_admin_error'] = $result['error'];
        }
        if ($result['otp_dev'] !== '') {
            $_SESSION['flash_admin_otp'] = $result['otp_dev'];
        }
        $extra = $result['redirect_extra'] !== '' ? '&' . $result['redirect_extra'] : '';
        header('Location: ' . $baseUrl . '?tab=security' . $extra);
        exit;
    }

    if ($action === 'save_profile' && $pdo) {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($fullName === '' || mb_strlen($fullName) < 2) {
            $_SESSION['flash_admin_error'] = 'Full name is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_admin_error'] = 'Enter a valid email address.';
        } else {
            $dup = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $dup->execute([$email, $userId]);
            if ($dup->fetch()) {
                $_SESSION['flash_admin_error'] = 'That email is already used by another account.';
            } else {
                $pdo->prepare('UPDATE users SET full_name = ?, email = ? WHERE id = ?')
                    ->execute([$fullName, $email, $userId]);
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $email;
                logActivity('update', 'Updated Super Admin profile', 'user-management', $userId);
                $_SESSION['flash_admin_success'] = 'Profile saved.';
            }
        }
        header('Location: ' . $baseUrl . '?tab=profile');
        exit;
    }

    if ($action === 'send_otp' && $pdo) {
        $current = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current, (string) $row['password_hash'])) {
            $_SESSION['flash_admin_error'] = 'Current password is incorrect.';
            header('Location: ' . $baseUrl . '?tab=security');
            exit;
        }

        $strength = smsValidatePasswordStrength($password);
        if (!$strength['ok']) {
            $_SESSION['flash_admin_error'] = $strength['message'];
            header('Location: ' . $baseUrl . '?tab=security');
            exit;
        }
        if ($password !== $confirm) {
            $_SESSION['flash_admin_error'] = 'New passwords do not match.';
            header('Location: ' . $baseUrl . '?tab=security');
            exit;
        }
        if (password_verify($password, (string) $row['password_hash'])) {
            $_SESSION['flash_admin_error'] = 'New password must be different from your current password.';
            header('Location: ' . $baseUrl . '?tab=security');
            exit;
        }

        $_SESSION['pending_admin_pw_change'] = [
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'at' => time(),
        ];
        $issued = smsIssueOtpToEmail($userId, 'password_change', 'admin-account', 10, 'password change');
        if (!empty($issued['ok'])) {
            if (!empty($issued['show_local']) && !empty($issued['code'])) {
                $_SESSION['flash_admin_otp'] = (string) $issued['code'];
            }
            if (!empty($issued['emailed'])) {
                $_SESSION['flash_admin_success'] = 'OTP sent to '
                    . (string) $issued['to']
                    . '. Enter the 6-digit code from your email to confirm.';
            } else {
                $_SESSION['flash_admin_success'] = 'OTP generated, but email could not be sent'
                    . ($issued['error'] !== '' ? ' (' . $issued['error'] . ')' : '')
                    . '. Use the on-screen code if shown, or configure SMTP in System Settings.';
            }
            logActivity(
                'password_reset_request',
                !empty($issued['emailed'])
                    ? 'OTP emailed for Super Admin password change'
                    : 'OTP generated for Super Admin password change (email failed)',
                'user-management',
                $userId
            );
            header('Location: ' . $baseUrl . '?tab=security&step=otp');
            exit;
        }
        $_SESSION['flash_admin_error'] = 'Could not generate OTP. Try again.';
        header('Location: ' . $baseUrl . '?tab=security');
        exit;
    }

    if ($action === 'confirm_otp' && $pdo) {
        $otp = trim((string) ($_POST['otp_code'] ?? ''));
        $pending = $_SESSION['pending_admin_pw_change'] ?? null;
        $pwPurpose = 'password_change';

        if (!is_array($pending) || empty($pending['hash'])) {
            $_SESSION['flash_admin_error'] = 'Password change session expired. Start again.';
            header('Location: ' . $baseUrl . '?tab=security');
            exit;
        }

        $gate = smsGetCodeGate($userId, $pwPurpose);
        if (!empty($gate['locked'])) {
            $_SESSION['flash_admin_error'] = $gate['message'];
            header('Location: ' . $baseUrl . '?tab=security&step=otp');
            exit;
        }

        $otpOk = smsVerifyOtp($userId, $pwPurpose, $otp);
        $authOk = !$otpOk && smsAuthenticatorIsEnabled($userId) && smsAuthenticatorVerifyLogin($userId, $otp);
        if (!$otpOk && !$authOk) {
            $gate = smsRegisterCodeFailure($userId, $pwPurpose);
            $_SESSION['flash_admin_error'] = smsCodeFailureMessage($gate, 'code');
            header('Location: ' . $baseUrl . '?tab=security&step=otp');
            exit;
        }

        smsClearCodeGate($userId, $pwPurpose);
        $pdo->prepare(
            'UPDATE users SET password_hash = ?, must_change_password = 0, password_changed_at = NOW(),
             failed_login_attempts = 0, locked_until = NULL WHERE id = ?'
        )->execute([$pending['hash'], $userId]);
        unset($_SESSION['pending_admin_pw_change']);
        $_SESSION['must_change_password'] = 0;
        logActivity('password_change', 'Super Admin password reset via account settings', 'user-management', $userId);
        $_SESSION['flash_admin_success'] = 'Password updated successfully.';
        header('Location: ' . $baseUrl . '?tab=security');
        exit;
    }

    if ($action === 'cancel_otp') {
        unset($_SESSION['pending_admin_pw_change']);
        $_SESSION['flash_admin_success'] = 'Password reset cancelled.';
        header('Location: ' . $baseUrl . '?tab=security');
        exit;
    }

    header('Location: ' . $baseUrl . '?tab=' . urlencode($tab));
    exit;
}

/* ── Load profile ──────────────────────────────────────────── */
$profile = [
    'full_name' => getCurrentUserName(),
    'username' => '',
    'email' => (string) ($_SESSION['user_email'] ?? ''),
    'last_login_at' => null,
    'password_changed_at' => null,
    'failed_login_attempts' => 0,
];
$pdo = db();
if ($pdo) {
    $stmt = $pdo->prepare(
        'SELECT full_name, username, email, last_login_at, password_changed_at, failed_login_attempts
         FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row) {
        $profile = $row;
    }
}

$pageTitle = 'Account Settings';
$activeModule = 'dashboard';
$activePage = $tab === 'security' ? 'security-settings' : '';
$breadcrumbs = [
    ['label' => 'Account Settings', 'url' => null],
];
$pageBannerIcon = 'fa-user-shield';
$pageBannerDescription = 'Super Admin profile and login security, separate from Module Security.';

require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>
<?php if ($otpDevCode !== ''): ?>
    <div class="alert alert-warning">
        <strong>Local OTP (email not delivered):</strong> <code><?= e($otpDevCode) ?></code>
        <span class="text-muted small ms-1">Configure SMTP in System Settings so codes go to your Gmail instead.</span>
    </div>
<?php endif; ?>

<ul class="nav nav-tabs sms-sec-tabs mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'profile' ? 'active' : '' ?>" href="<?= e($baseUrl) ?>?tab=profile">
            <?= smsIcon('user', ['class' => 'me-1']) ?>Profile
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'security' ? 'active' : '' ?>" href="<?= e($baseUrl) ?>?tab=security">
            <?= smsIcon('key', ['class' => 'me-1']) ?>Login Security
        </a>
    </li>
</ul>

<?php if ($tab === 'profile'): ?>
    <section class="card sms-sec-card mb-4">
        <div class="card-body">
            <div class="sms-sec-card-head">
                <div class="sms-sec-card-title">
                    <span class="sms-sec-icon"><?= smsIcon('user', ['aria-hidden' => 'true']) ?></span>
                    <div>
                        <h2 class="h5 fw-bold mb-0">Profile</h2>
                        <p class="sms-sec-lead mb-0 mt-1">Update how your Super Admin account appears in the system.</p>
                    </div>
                </div>
            </div>
            <div class="row g-3 sms-sec-pw-split">
                <div class="col-lg-7">
                    <div class="sms-sec-pw-box h-100">
                        <h3 class="h6 fw-bold mb-3">
                            <?= smsIcon('id-card', ['class' => 'text-sms-primary me-1', 'aria-hidden' => 'true']) ?>Account details
                        </h3>
                        <form method="POST" autocomplete="off">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="save_profile">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="full_name">Full name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                       value="<?= e((string) $profile['full_name']) ?>" required maxlength="150">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="username">Username</label>
                                <input type="text" class="form-control" id="username"
                                       value="<?= e((string) $profile['username']) ?>" disabled>
                                <div class="form-text">Username cannot be changed here.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?= e((string) $profile['email']) ?>" required maxlength="190">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Role</label>
                                <input type="text" class="form-control" value="Super Admin" disabled>
                            </div>
                            <button type="submit" class="btn btn-sms-primary">
                                <?= smsIcon('save', ['class' => 'me-1']) ?>Save profile
                            </button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="sms-sec-pw-box h-100">
                        <h3 class="h6 fw-bold mb-3">
                            <?= smsIcon('link', ['class' => 'text-sms-primary me-1', 'aria-hidden' => 'true']) ?>Quick links &amp; status
                        </h3>
                        <dl class="row small mb-3">
                            <dt class="col-5 text-muted">Last login</dt>
                            <dd class="col-7 mb-2">
                                <?= !empty($profile['last_login_at'])
                                    ? e(date('M j, Y g:i A', strtotime((string) $profile['last_login_at'])))
                                    : '—' ?>
                            </dd>
                            <dt class="col-5 text-muted">Password changed</dt>
                            <dd class="col-7 mb-2">
                                <?= !empty($profile['password_changed_at'])
                                    ? e(date('M j, Y g:i A', strtotime((string) $profile['password_changed_at'])))
                                    : '—' ?>
                            </dd>
                            <dt class="col-5 text-muted">Failed attempts</dt>
                            <dd class="col-7 mb-0"><?= (int) ($profile['failed_login_attempts'] ?? 0) ?></dd>
                        </dl>
                        <a class="btn btn-outline-primary w-100 mb-2" href="<?= e($baseUrl) ?>?tab=security">
                            <?= smsIcon('key', ['class' => 'me-2']) ?>Login Security
                        </a>
                        <a class="btn btn-outline-secondary w-100" href="<?= BASE_URL ?>/modules/user-management/pages/module-security.php?picker=1">
                            <?= smsIcon('shield-alt', ['class' => 'me-2']) ?>Module Security
                        </a>
                        <p class="small text-muted mt-3 mb-0">
                            Module Security manages staff reset requests. Your own admin password is only changed under Login Security.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php else: /* security */ ?>
    <?php
    smsRenderAuthPasskeySplit(
        $userId,
        $baseUrl . '?tab=security',
        csrfField(),
        csrfToken(),
        'Authenticator & Passkey',
        'Manage login second factors for your Super Admin account.'
    );
    ?>
    <section class="card sms-sec-card mb-4">
        <div class="card-body">
            <div class="sms-sec-card-head">
                <div class="sms-sec-card-title">
                    <span class="sms-sec-icon"><?= smsIcon('key', ['aria-hidden' => 'true']) ?></span>
                    <div>
                        <h2 class="h5 fw-bold mb-0">Password</h2>
                        <p class="sms-sec-lead mb-0 mt-1">Change your Super Admin login password with email OTP confirmation.</p>
                    </div>
                </div>
            </div>
            <div class="row g-3 sms-sec-pw-split">
                <div class="col-lg-7">
                    <div class="sms-sec-pw-box h-100">
                        <h3 class="h6 fw-bold mb-3">
                            <?= smsIcon('lock', ['class' => 'text-sms-primary me-1', 'aria-hidden' => 'true']) ?>Reset password
                        </h3>
                        <?php if ($step === 'otp' && !empty($_SESSION['pending_admin_pw_change'])): ?>
                            <p class="sms-sec-lead">Enter the 6-digit OTP to finish resetting your admin password.</p>
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="confirm_otp">
                                <div class="mb-3">
                                    <?= smsOtpInput('otp_code', ['id' => 'otp_code', 'required' => true, 'autofocus' => true, 'label' => 'Email verification code', 'hint' => 'Paste or type the code from your email.']) ?>
                                </div>
                                <button type="submit" class="btn btn-sms-primary">Confirm &amp; update password</button>
                                <button type="submit" name="action" value="cancel_otp" class="btn btn-outline-secondary ms-2" formnovalidate>Cancel</button>
                            </form>
                        <?php else: ?>
                            <p class="sms-sec-lead">Enter your current password, choose a new one, then confirm with an OTP.</p>
                            <form method="POST" autocomplete="off" id="adminResetPasswordForm">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="send_otp">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="current_password">Current password</label>
                                    <?= smsPasswordInput(['id' => 'current_password', 'name' => 'current_password', 'required' => true, 'autocomplete' => 'current-password']) ?>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="password">New password</label>
                                    <?= smsPasswordInput(['id' => 'password', 'name' => 'password', 'required' => true, 'minlength' => $minLen, 'autocomplete' => 'new-password']) ?>
                                    <?= smsPasswordStrengthMarkup('password') ?>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="password_confirm">Confirm new password</label>
                                    <?= smsPasswordInput(['id' => 'password_confirm', 'name' => 'password_confirm', 'required' => true, 'minlength' => $minLen, 'autocomplete' => 'new-password']) ?>
                                </div>
                                <button type="submit" class="btn btn-sms-primary">
                                    <?= smsIcon('mobile-alt', ['class' => 'me-1']) ?>Continue with OTP
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="sms-sec-pw-box h-100">
                        <h3 class="h6 fw-bold mb-3">
                            <?= smsIcon('info-circle', ['class' => 'text-sms-primary me-1', 'aria-hidden' => 'true']) ?>Login status
                        </h3>
                        <dl class="row small mb-3">
                            <dt class="col-5 text-muted">Email</dt>
                            <dd class="col-7 mb-2"><?= e((string) $profile['email']) ?></dd>
                            <dt class="col-5 text-muted">Last login</dt>
                            <dd class="col-7 mb-2">
                                <?= !empty($profile['last_login_at'])
                                    ? e(date('M j, Y g:i A', strtotime((string) $profile['last_login_at'])))
                                    : '—' ?>
                            </dd>
                            <dt class="col-5 text-muted">Password changed</dt>
                            <dd class="col-7 mb-2">
                                <?= !empty($profile['password_changed_at'])
                                    ? e(date('M j, Y g:i A', strtotime((string) $profile['password_changed_at'])))
                                    : '—' ?>
                            </dd>
                            <dt class="col-5 text-muted">Failed attempts</dt>
                            <dd class="col-7 mb-0"><?= (int) ($profile['failed_login_attempts'] ?? 0) ?></dd>
                        </dl>
                        <a class="btn btn-outline-secondary w-100" href="<?= BASE_URL ?>/modules/user-management/pages/module-security.php?picker=1">
                            <?= smsIcon('shield-alt', ['class' => 'me-2']) ?>Module Security
                        </a>
                        <p class="small text-muted mt-3 mb-0">
                            Staff password requests are handled in Module Security. This screen is only for your Super Admin login.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<script src="<?= BASE_URL ?>/assets/js/sms-security-ui.js?v=6"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
