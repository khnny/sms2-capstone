<?php
/**
 * SMS 2 – Forced / voluntary change password (authenticated)
 */
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security-ui.php';
require_once ROOT_PATH . '/includes/security-workflow.php';
require_once ROOT_PATH . '/includes/module-controls.php';
requireAuth();

// Password changes are disabled while the whole system is in maintenance
if (smsIsSystemInMaintenance() && !smsCanBypassSystemControls()) {
    header('Location: ' . BASE_URL . '/account/maintenance.php');
    exit;
}

// Module staff cannot self-change passwords — only Super Admin (or forced first-login change)
$error = '';
$forced = !empty($_SESSION['must_change_password']);
if (!$forced && !smsIsGrantedAdminRole(getCurrentUserRoleKey())) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$minLen = (int) smsSetting('min_password_length', '8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $current = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');
        $userId = getCurrentUserId();

        $pdo = db();
        $row = null;
        if ($pdo && $userId) {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
        }

        if (!$row || !password_verify($current, (string) $row['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($password) < $minLen) {
            $error = "New password must be at least {$minLen} characters.";
        } elseif ($password !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $strength = smsValidatePasswordStrength($password);
            if (!$strength['ok']) {
                $error = $strength['message'];
            } elseif (smsSetUserPassword((int) $userId, $password, false)) {
                $_SESSION['must_change_password'] = 0;
                logActivity('password_change', 'Password changed by user', 'System');
                header('Location: ' . smsPostLoginRedirectUrl());
                exit;
            } else {
                $error = 'Could not update password. Please try again.';
            }
        }
    }
}

$pageTitle = 'Change Password';
$bodyClass = 'login-page';
require_once ROOT_PATH . '/includes/header.php';
?>
<style>
body.login-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #051637, #0f5099);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}
</style>

<div class="sms-form-card">
    <h1>Change password</h1>
    <p class="text-muted mb-3"><?= $forced ? 'You must set a new password before continuing.' : 'Update your account password.' ?></p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <?= csrfField() ?>
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
        <button type="submit" class="btn btn-primary w-100">Save password</button>
        <?php if (!$forced): ?>
            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>/dashboard/index.php">Cancel</a>
            </div>
        <?php endif; ?>
    </form>
</div>
<?php require_once ROOT_PATH . '/includes/scripts.php'; ?>
