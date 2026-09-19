<?php
/**
 * SMS 2 – First-time setup
 * Creates the real Super Admin account when the users table is empty.
 * Disabled automatically after the first admin exists.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/audit.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security-ui.php';
require_once ROOT_PATH . '/includes/security-workflow.php';

if (!smsNeedsSetup()) {
    header('Location: ' . BASE_URL . '/login/login.php');
    exit;
}

$error = '';
$minLen = 8;

try {
    $minLen = (int) smsSetting('min_password_length', '8');
} catch (Throwable $e) {
    $minLen = 8;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $email    = strtolower(trim((string) ($_POST['email'] ?? '')));
        $username = strtolower(trim((string) ($_POST['username'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');

        if ($fullName === '' || $email === '' || $username === '') {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif (!preg_match('/^[a-z0-9._-]{3,80}$/', $username)) {
            $error = 'Username must be 3–80 characters (letters, numbers, . _ -).';
        } elseif (strlen($password) < $minLen) {
            $error = "Password must be at least {$minLen} characters.";
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $strength = smsValidatePasswordStrength($password);
            if (!$strength['ok']) {
                $error = $strength['message'];
            } else {
            $pdo = db();
            if (!$pdo) {
                $error = 'Database unavailable. Run database/install.php first.';
            } else {
                // Re-check empty (race-safe enough for local setup)
                $count = (int) $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
                if ($count > 0) {
                    header('Location: ' . BASE_URL . '/login/login.php');
                    exit;
                }

                try {
                    $pdo->prepare(
                        'INSERT INTO users
                            (username, email, password_hash, full_name, role_key, status, password_changed_at, must_change_password)
                         VALUES (?, ?, ?, ?, \'superadmin\', \'active\', NOW(), 0)'
                    )->execute([
                        $username,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $fullName,
                    ]);

                    $userId = (int) $pdo->lastInsertId();
                    logActivity(
                        'create',
                        'First Super Admin created via setup',
                        'System',
                        $userId,
                        $fullName,
                        'superadmin'
                    );

                    $pdo->prepare(
                        'INSERT INTO role_permissions (role_key, module_key, granted)
                         VALUES (\'superadmin\', \'user-management\', 1)
                         ON DUPLICATE KEY UPDATE granted = VALUES(granted)'
                    )->execute();

                    // Auto-login
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_name'] = $fullName;
                    $_SESSION['user_role'] = 'Super Admin';
                    $_SESSION['user_role_key'] = 'superadmin';
                    $_SESSION['user_email'] = $email;
                    $_SESSION['must_change_password'] = 0;
                    $_SESSION['last_activity'] = time();

                    header('Location: ' . BASE_URL . '/dashboard/index.php?setup=1');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Could not create account. Email or username may already exist.';
                }
            }
            }
        }
    }
}

$pageTitle = 'Initial Setup';
$bodyClass = 'login-page';
require_once ROOT_PATH . '/includes/header.php';
?>
<style>
body.login-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #051637 0%, #0f5099 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
}
.setup-card {
    width: min(520px, 100%);
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 24px 60px rgba(0,0,0,.28);
}
.setup-card h1 {
    margin: 0 0 .35rem;
    font-size: 1.65rem;
    font-weight: 800;
    color: #111827;
}
.setup-card .lead {
    color: #64748b;
    font-size: .92rem;
    margin-bottom: 1.25rem;
}
.setup-badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    margin-bottom: .85rem;
    padding: .35rem .7rem;
    border-radius: 999px;
    background: #eef5ff;
    color: #1d4ed8;
    font-size: .75rem;
    font-weight: 800;
}
</style>

<div class="setup-card sms-form-card">
    <div class="setup-badge"><?= smsIcon('shield-alt') ?> BCP Security Setup</div>
    <h1>Create Super Admin</h1>
    <p class="lead">
        Walang demo accounts. Gumawa muna ng tunay na Super Admin para ma-adopt ang security sa system mo.
        Pagkatapos nito, magdagdag ka ng staff/student accounts sa User Management.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <?= csrfField() ?>
        <div class="mb-3">
            <label class="form-label fw-semibold" for="full_name">Full name</label>
            <input type="text" class="form-control" id="full_name" name="full_name" required
                   value="<?= e($_POST['full_name'] ?? '') ?>" placeholder="e.g. Juan Dela Cruz">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold" for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" required
                   value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@bestlink.edu.ph">
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold" for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required
                   value="<?= e($_POST['username'] ?? '') ?>" placeholder="e.g. jdelacruz">
            <div class="form-text">Staff login: username@bestlink.edu.ph</div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold" for="password">Password</label>
            <?= smsPasswordInput([
                'id' => 'password',
                'name' => 'password',
                'required' => true,
                'minlength' => $minLen,
                'autocomplete' => 'new-password',
            ]) ?>
            <?= smsPasswordStrengthMarkup('password') ?>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold" for="password_confirm">Confirm password</label>
            <?= smsPasswordInput([
                'id' => 'password_confirm',
                'name' => 'password_confirm',
                'required' => true,
                'minlength' => $minLen,
                'autocomplete' => 'new-password',
            ]) ?>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
            Create Super Admin &amp; continue
        </button>
    </form>
</div>
<?php require_once ROOT_PATH . '/includes/scripts.php'; ?>
