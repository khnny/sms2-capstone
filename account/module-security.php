<?php
/**
 * SMS 2 – Per-module Security Settings (sidebar only)
 *
 * Staff / student:
 *   - Activity logs for this module
 *   - Change password (current + new + confirm) with Authenticator / email OTP
 *   - OR request password reset from Super Admin
 *
 * Super Admin viewing a module:
 *   - Activity logs for this module
 *   - Pending password reset requests for this module
 *   - Reset a user’s password (admin action)
 *   - No personal “change my password” form here
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/module-security-catalog.php';
require_once ROOT_PATH . '/includes/security-workflow.php';
require_once ROOT_PATH . '/includes/authenticator-ui.php';
require_once ROOT_PATH . '/includes/passkey.php';
require_once ROOT_PATH . '/includes/security-ui.php';
requireAuth();

smsEnsureSecurityTables();
smsEnsureAuthenticatorTable();

// Super Admin: security lives only under User Management → Module Security
if (smsIsGrantedAdminRole(getCurrentUserRoleKey())) {
    $mod = (string) ($_GET['focus'] ?? $_GET['sec_mod'] ?? $_GET['mod'] ?? $_GET['module'] ?? $_GET['m'] ?? '');
    if ($mod === 'student-portal') {
        $mod = 'student_portal';
    }
    if (in_array(strtolower($mod), ['crud', 'crowd'], true)) {
        $mod = 'crad';
    }
    $hub = BASE_URL . '/modules/user-management/pages/module-security.php';
    if ($mod !== '') {
        header('Location: ' . $hub . '?focus=' . rawurlencode($mod));
    } else {
        header('Location: ' . $hub . '?picker=1');
    }
    exit;
}

$moduleKey = (string) ($_GET['module'] ?? '');
if ($moduleKey === 'student-portal') {
    $moduleKey = 'student_portal';
}

$info = smsModuleSecurityInfo($moduleKey);
if ($info === null && !isset($MODULES[$moduleKey]) && $moduleKey !== 'student_portal') {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

if ($moduleKey === 'user-management' || !userCanAccessModule($moduleKey)) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$isAdmin = false; // staff/student page only
$moduleLabel = is_array($info) ? (string) ($info['label'] ?? smsModuleLabel($moduleKey)) : smsModuleLabel($moduleKey);
$moduleIconFallback = 'fa-shield-alt';
if (is_array($info) && !empty($info['icon'])) {
    $moduleIconFallback = (string) $info['icon'];
}
$securityRoleKey = getCurrentUserRoleKey();
$accountContext = [
    'student' => ['module' => 'student_portal', 'label' => 'Student Portal', 'icon' => 'fa-user-graduate'],
    'admission' => ['module' => 'enrollment', 'label' => 'Admission Account', 'icon' => 'fa-user-plus'],
    'adviser' => ['module' => 'faculty', 'label' => 'Adviser Account', 'icon' => 'fa-user-tie'],
    'panel' => ['module' => 'faculty', 'label' => 'Panel Account', 'icon' => 'fa-users'],
    'research_director' => ['module' => 'faculty', 'label' => 'Research Director Account', 'icon' => 'fa-user-shield'],
    'hr' => ['module' => 'faculty', 'label' => 'HR Account', 'icon' => 'fa-chalkboard-teacher'],
    'research_coordinator' => ['module' => 'crad', 'label' => 'Research Coordinator', 'icon' => 'fa-microscope'],
    'department_head' => ['module' => 'crad', 'label' => 'Department Head', 'icon' => 'fa-user-tie'],
    'department_chair' => ['module' => 'crad', 'label' => 'Department Chair', 'icon' => 'fa-user-tie'],
    'research_office' => ['module' => 'crad', 'label' => 'Research Office', 'icon' => 'fa-flask'],
    'crad_officer' => ['module' => 'crad', 'label' => 'CRAD Officer', 'icon' => 'fa-flask'],
    'research_grant' => ['module' => 'crad_grant', 'label' => 'Research Grant Account', 'icon' => 'fa-hand-holding-usd'],
    'review_committee' => ['module' => 'crad_grant', 'label' => 'Review Committee Account', 'icon' => 'fa-clipboard-check'],
    'finance' => ['module' => 'payment', 'label' => 'Finance Account', 'icon' => 'fa-credit-card'],
    'registrar' => ['module' => $moduleKey, 'label' => 'Registrar Account', 'icon' => 'fa-folder-open'],
    'osa' => ['module' => 'cocurricular', 'label' => 'OSA Account', 'icon' => 'fa-users'],
    'it_office' => ['module' => 'lms', 'label' => 'IT Office Account', 'icon' => 'fa-laptop'],
    'qa' => ['module' => 'accreditation', 'label' => 'QA Account', 'icon' => 'fa-award'],
    'vpaa' => ['module' => 'accreditation', 'label' => 'VPAA Account', 'icon' => 'fa-award'],
];
if (isset($accountContext[$securityRoleKey])) {
    $context = $accountContext[$securityRoleKey];
    if (($context['module'] ?? $moduleKey) === $moduleKey || $securityRoleKey === 'registrar') {
        $moduleLabel = (string) $context['label'];
        $moduleIconFallback = (string) $context['icon'];
    }
}
$tab = (string) ($_GET['tab'] ?? 'logs');
// Legacy tabs → combined Account Security
if (in_array($tab, ['password', 'request'], true)) {
    $tab = 'account';
}
$allowedTabs = ['logs', 'account', 'authenticator'];
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'logs';
}

$error = '';
$success = '';
$otpDevCode = '';

if (!empty($_SESSION['flash_sec_success'])) {
    $success = (string) $_SESSION['flash_sec_success'];
    unset($_SESSION['flash_sec_success']);
}
if (!empty($_SESSION['flash_sec_error'])) {
    $error = (string) $_SESSION['flash_sec_error'];
    unset($_SESSION['flash_sec_error']);
}
if (!empty($_SESSION['flash_sec_otp'])) {
    $otpDevCode = (string) $_SESSION['flash_sec_otp'];
    unset($_SESSION['flash_sec_otp']);
}

$userId = (int) getCurrentUserId();
$minLen = (int) smsSetting('min_password_length', '8');

/* ── POST handlers ─────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $_SESSION['flash_sec_error'] = 'Security check failed. Please try again.';
        header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=' . urlencode($tab));
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');

    $authActions = [
        'auth_turn_on', 'auth_request_setup', 'auth_send_setup_otp', 'auth_confirm_enable',
        'auth_cancel_setup', 'auth_disable', 'auth_turn_off_start', 'auth_disable_show_code', 'auth_disable_cancel',
    ];
    if (in_array($action, $authActions, true)) {
        $result = smsHandleAuthenticatorPost($userId, $action, $_POST, (string) ($_SESSION['user_email'] ?? ''));
        if ($result['success'] !== '') {
            $_SESSION['flash_sec_success'] = $result['success'];
        }
        if ($result['error'] !== '') {
            $_SESSION['flash_sec_error'] = $result['error'];
        }
        if ($result['otp_dev'] !== '') {
            $_SESSION['flash_sec_otp'] = $result['otp_dev'];
        }
        $extra = $result['redirect_extra'] !== '' ? '&' . $result['redirect_extra'] : '';
        header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=authenticator' . $extra);
        exit;
    }

    // Staff: send OTP / Authenticator step for password change
    if ($action === 'send_otp' && !$isAdmin) {
        $current = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        $pdo = db();
        $row = null;
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
        }

        if (!$row || !password_verify($current, (string) $row['password_hash'])) {
            $_SESSION['flash_sec_error'] = 'Current password is incorrect.';
        } else {
            $strength = smsValidatePasswordStrength($password);
            if (!$strength['ok']) {
                $_SESSION['flash_sec_error'] = $strength['message'];
            } elseif ($password !== $confirm) {
                $_SESSION['flash_sec_error'] = 'New passwords do not match.';
            } else {
                $_SESSION['pending_pw_change'] = [
                    'hash' => password_hash($password, PASSWORD_DEFAULT),
                    'module' => $moduleKey,
                    'at' => time(),
                ];
                $authOn = smsAuthenticatorIsEnabled($userId);
                $issued = smsIssueOtpToEmail($userId, 'password_change', $moduleKey, 10, 'password change');
                if (!empty($issued['ok']) || $authOn) {
                    if (!empty($issued['ok']) && !empty($issued['show_local']) && !empty($issued['code'])) {
                        $_SESSION['flash_sec_otp'] = (string) $issued['code'];
                    }
                    if ($authOn && empty($issued['ok'])) {
                        $_SESSION['flash_sec_success'] = 'Enter the 6-digit code from your Authenticator app to confirm the password change.';
                    } elseif ($authOn && !empty($issued['emailed'])) {
                        $_SESSION['flash_sec_success'] = 'Enter your Authenticator code, or the 6-digit email code sent to '
                            . (string) $issued['to']
                            . '.';
                    } elseif (!empty($issued['emailed'])) {
                        $_SESSION['flash_sec_success'] = 'OTP sent to '
                            . (string) $issued['to']
                            . '. Enter the 6-digit code from your email to confirm.';
                    } elseif (!empty($issued['ok'])) {
                        $_SESSION['flash_sec_success'] = 'OTP generated, but email could not be sent'
                            . (!empty($issued['error']) ? ' (' . $issued['error'] . ')' : '')
                            . '. Use the on-screen code if shown, or your Authenticator app.';
                    } else {
                        $_SESSION['flash_sec_success'] = 'Enter the 6-digit code from your Authenticator app to confirm.';
                    }
                    logActivity(
                        'password_reset_request',
                        $authOn
                            ? 'Password change awaiting Authenticator / email OTP'
                            : (!empty($issued['emailed'])
                                ? 'OTP emailed for password change'
                                : 'OTP generated for password change (email failed)'),
                        $moduleKey
                    );
                    header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=account&step=otp');
                    exit;
                }
                unset($_SESSION['pending_pw_change']);
                $_SESSION['flash_sec_error'] = !empty($issued['error'])
                    ? (string) $issued['error']
                    : 'Could not send a verification code. Set up Authenticator or configure email in System Settings.';
            }
        }
        header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=account');
        exit;
    }

    // Staff: confirm OTP / Authenticator + apply new password
    if ($action === 'confirm_otp' && !$isAdmin) {
        $otp = trim((string) ($_POST['otp_code'] ?? ''));
        $pending = $_SESSION['pending_pw_change'] ?? null;
        $pwPurpose = 'password_change';

        if (!is_array($pending) || empty($pending['hash']) || (($pending['module'] ?? '') !== $moduleKey)) {
            $_SESSION['flash_sec_error'] = 'Password change session expired. Start again.';
        } else {
            $gate = smsGetCodeGate($userId, $pwPurpose);
            if (!empty($gate['locked'])) {
                $_SESSION['flash_sec_error'] = $gate['message'];
            } else {
                $otpOk = smsVerifyOtp($userId, $pwPurpose, $otp);
                $authOk = !$otpOk && smsAuthenticatorIsEnabled($userId) && smsAuthenticatorVerifyLogin($userId, $otp);
                if (!$otpOk && !$authOk) {
                    $gate = smsRegisterCodeFailure($userId, $pwPurpose);
                    $_SESSION['flash_sec_error'] = smsCodeFailureMessage($gate, 'code');
                } else {
                    smsClearCodeGate($userId, $pwPurpose);
                    $pdo = db();
                    if ($pdo) {
                        $pdo->prepare(
                            'UPDATE users SET password_hash = ?, must_change_password = 0, password_changed_at = NOW(),
                             failed_login_attempts = 0, locked_until = NULL WHERE id = ?'
                        )->execute([$pending['hash'], $userId]);
                        unset($_SESSION['pending_pw_change']);
                        logActivity('password_change', 'Password changed with OTP verification', $moduleKey);
                        $_SESSION['flash_sec_success'] = 'Password updated successfully.';
                        header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=account');
                        exit;
                    }
                    $_SESSION['flash_sec_error'] = 'Database error while saving password.';
                }
            }
        }
        header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=account&step=otp');
        exit;
    }

    // Staff: request admin reset (reason + chosen password required)
    if ($action === 'request_admin_reset' && !$isAdmin) {
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $newPassword = (string) ($_POST['requested_password'] ?? '');
        $confirm = (string) ($_POST['requested_password_confirm'] ?? '');

        if ($reason === '') {
            $_SESSION['flash_sec_error'] = 'Reason is required.';
        } elseif ($newPassword !== $confirm) {
            $_SESSION['flash_sec_error'] = 'Requested passwords do not match.';
        } else {
            $strength = smsValidatePasswordStrength($newPassword);
            if (!$strength['ok']) {
                $_SESSION['flash_sec_error'] = $strength['message'];
            } else {
                $result = smsCreatePasswordResetRequest($userId, $moduleKey, $reason, $newPassword);
                if ($result['ok']) {
                    $_SESSION['flash_sec_success'] = 'Request sent to Super Admin with your chosen password. Wait for approval.';
                } else {
                    $_SESSION['flash_sec_error'] = $result['error'] ?? 'Could not submit request.';
                }
            }
        }
        header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=account');
        exit;
    }

    // Admin: approve request (applies user's chosen password — admin does not type a password)
    if ($action === 'approve_request' && $isAdmin) {
        $reqId = (int) ($_POST['request_id'] ?? 0);
        $result = smsApprovePasswordRequest($reqId, $userId);
        if ($result['ok']) {
            $_SESSION['flash_sec_success'] = 'Approved. The user’s chosen password is now active.';
        } else {
            $_SESSION['flash_sec_error'] = $result['error'] ?? 'Approve failed.';
        }
        header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=requests');
        exit;
    }

    // Admin: reject request
    if ($action === 'reject_request' && $isAdmin) {
        $reqId = (int) ($_POST['request_id'] ?? 0);
        if (smsRejectPasswordRequest($reqId, $userId, trim((string) ($_POST['admin_note'] ?? '')))) {
            $_SESSION['flash_sec_success'] = 'Request rejected.';
        } else {
            $_SESSION['flash_sec_error'] = 'Could not reject request.';
        }
        header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=requests');
        exit;
    }

    // Admin: direct reset password for a user (auto-generated temp only)
    if ($action === 'admin_reset_user' && $isAdmin) {
        $targetId = (int) ($_POST['target_user_id'] ?? 0);
        $temp = 'Temp@' . random_int(100000, 999999);
        if ($targetId <= 0) {
            $_SESSION['flash_sec_error'] = 'Select a user.';
        } elseif ($targetId === $userId) {
            $_SESSION['flash_sec_error'] = 'You cannot reset your own password on this screen.';
        } elseif (!smsSetUserPassword($targetId, $temp, true)) {
            $_SESSION['flash_sec_error'] = 'Could not reset password.';
        } else {
            logActivity('password_reset', 'Admin reset password for user #' . $targetId, $moduleKey);
            $_SESSION['flash_sec_success'] = 'Password reset. Temporary password: ' . $temp . ' (user must change on next login).';
        }
        header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=reset');
        exit;
    }

    header('Location: ' . BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey) . '&tab=' . urlencode($tab));
    exit;
}

$step = (string) ($_GET['step'] ?? '');

// Record that this security page was opened (so Activity Logs always has module traffic)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SESSION['sec_view_' . $moduleKey])) {
    logActivity('view', 'Opened Security Settings', $moduleKey);
    $_SESSION['sec_view_' . $moduleKey] = time();
}

$logs = smsModuleActivityLogs($moduleKey, 200, $userId);
$pendingRequests = $isAdmin ? smsPendingPasswordRequests($moduleKey) : [];

// Users for admin reset (active accounts)
$resetUsers = [];
if ($isAdmin) {
    $pdo = db();
    if ($pdo) {
        $resetUsers = $pdo->query(
            'SELECT id, full_name, username, email, role_key, status
             FROM users WHERE status IN (\'active\',\'locked\') AND role_key <> \'admin\'
             ORDER BY full_name ASC'
        )->fetchAll() ?: [];
    }
}

// Staff pending / latest rejected request (so they can see admin note)
$myPending = null;
$myRejected = null;
if (!$isAdmin) {
    $pdo = db();
    if ($pdo) {
        $stmt = $pdo->prepare(
            'SELECT * FROM password_reset_requests
             WHERE user_id = ? AND status = \'pending\' ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $myPending = $stmt->fetch() ?: null;

        if (!$myPending) {
            $stmt = $pdo->prepare(
                'SELECT * FROM password_reset_requests
                 WHERE user_id = ? AND module_key = ? AND status = \'rejected\'
                 ORDER BY resolved_at DESC, id DESC LIMIT 1'
            );
            $stmt->execute([$userId, $moduleKey]);
            $myRejected = $stmt->fetch() ?: null;
        }
    }
}

$pageTitle    = $moduleLabel . ' – Security';
$activeModule = $moduleKey === 'student_portal' ? 'student_portal' : $moduleKey;
$activePage   = 'security-settings';
$breadcrumbs  = [
    [
        'label' => $moduleLabel,
        'url'   => $moduleKey === 'student_portal'
            ? BASE_URL . '/modules/student-portal/pages/my-profile.php'
            : BASE_URL . '/modules/' . $moduleKey . '/index.php',
    ],
    ['label' => 'Security Settings', 'url' => null],
];
$pageBannerIcon = $moduleIconFallback;
$pageBannerDescription = 'Activity logs, password options, and Authenticator / passkeys for ' . $moduleLabel . '.';

require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';

// Navbar/sidebar foreach must not overwrite this page's module key.
$moduleKey = (string) ($_GET['module'] ?? $moduleKey ?? '');
if ($moduleKey === 'student-portal') {
    $moduleKey = 'student_portal';
}

$baseUrl = BASE_URL . '/account/module-security.php?module=' . urlencode($moduleKey);
$moduleIcon = $moduleIconFallback;
$initialPanel = 'logs';
if (in_array($tab, ['account', 'password', 'request', 'passwords'], true) || $step === 'otp') {
    $initialPanel = 'passwords';
} elseif ($tab === 'authenticator') {
    $initialPanel = 'authenticator';
}

$logActions = [];
foreach ($logs as $log) {
    $a = (string) ($log['action'] ?? '');
    if ($a !== '') {
        $logActions[$a] = true;
    }
}
ksort($logActions);
?>
<?php renderBreadcrumbs($breadcrumbs); ?>

<div id="secModuleRoot" class="sms-sec-root" data-initial-panel="<?= e($initialPanel) ?>" data-url-mode="staff" data-module="<?= e($moduleKey) ?>">
    <?php if ($success): ?>
        <div class="alert alert-success"><?= smsIcon('check-circle', ['class' => 'me-2']) ?><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= smsIcon('exclamation-circle', ['class' => 'me-2']) ?><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($otpDevCode !== ''): ?>
        <div class="alert alert-warning">
            <strong>Local OTP (email not delivered):</strong>
            <code class="fs-5 ms-1"><?= e($otpDevCode) ?></code>
            <span class="d-block small mt-1">Configure SMTP in System Settings so the code is emailed to your Gmail instead.</span>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs sms-sec-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link <?= $initialPanel === 'logs' ? 'active' : '' ?>" data-sec-tab="logs" data-sec-tab-card="logs" role="tab" aria-controls="panel-logs">
                <?= smsIcon('history', ['class' => 'me-1']) ?>Activity Logs
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link <?= $initialPanel === 'passwords' ? 'active' : '' ?>" data-sec-tab="passwords" data-sec-tab-card="passwords" role="tab" aria-controls="panel-passwords">
                <?= smsIcon('key', ['class' => 'me-1']) ?>Password Management
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link <?= $initialPanel === 'authenticator' ? 'active' : '' ?>" data-sec-tab="authenticator" data-sec-tab-card="authenticator" role="tab" aria-controls="panel-authenticator">
                <?= smsIcon('fingerprint', ['class' => 'me-1']) ?>Authenticator &amp; Passkey
            </button>
        </li>
    </ul>

    <div id="panel-logs" class="sec-panel sms-sec-panel" role="tabpanel" data-sec-panel="logs" <?= $initialPanel === 'logs' ? '' : 'hidden' ?>>
        <section class="card sms-sec-card">
            <div class="card-body">
                <div class="sms-sec-card-head">
                    <div class="sms-sec-card-title">
                        <span class="sms-sec-icon"><?= smsIcon('history', ['aria-hidden' => 'true']) ?></span>
                        <div>
                            <h2 class="h5 fw-bold mb-0"><?= e($moduleLabel) ?> — Activity Logs</h2>
                            <p class="sms-sec-lead mb-0 mt-1">Filter by user, action, or date. Scroll to browse.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="small text-muted" id="modLogCount"><?= count($logs) ?> shown</span>
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                data-sms-export-csv="#modLogTable"
                                data-sms-export-rows="tbody tr.mod-log-row"
                                data-sms-export-filename="<?= e($moduleKey) ?>-activity-logs.csv">
                            <?= smsIcon('file-export', ['class' => 'me-1']) ?>Export CSV
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="modLogClear">Clear filters</button>
                    </div>
                </div>
                <div class="row g-2 g-md-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label small mb-1" for="modLogUser">User</label>
                        <input type="text" id="modLogUser" class="form-control form-control-sm" placeholder="Name…">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1" for="modLogAction">Action type</label>
                        <select id="modLogAction" class="form-select form-select-sm">
                            <option value="">All actions</option>
                            <?php foreach (array_keys($logActions) as $actionName): ?>
                                <option value="<?= e($actionName) ?>"><?= e(ucfirst(str_replace('_', ' ', $actionName))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1" for="modLogDateFrom">Date from</label>
                        <input type="date" id="modLogDateFrom" class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1" for="modLogDateTo">Date to</label>
                        <input type="date" id="modLogDateTo" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="mod-log-scroll table-responsive border rounded-3">
                    <table class="table table-hover align-middle mb-0" id="modLogTable">
                        <thead class="mod-log-thead">
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Detail</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$logs): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No activity logs for this module yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="mod-log-row"
                                        data-action="<?= e((string) ($log['action'] ?? '')) ?>"
                                        data-user="<?= e(strtolower((string) ($log['user_name'] ?? ''))) ?>"
                                        data-date="<?= e((string) ($log['log_date'] ?? '')) ?>">
                                        <td class="small text-nowrap"><?= e($log['time']) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= e($log['user_name'] ?? 'System') ?></div>
                                            <div class="small text-muted"><?= e($log['role_key'] ?? '') ?></div>
                                        </td>
                                        <td><span class="badge text-bg-light"><?= e($log['action']) ?></span></td>
                                        <td class="small"><?= e($log['detail']) ?></td>
                                        <td class="small text-muted"><?= e($log['ip_address'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="mod-log-empty-filter" hidden>
                                    <td colspan="5" class="text-center text-muted py-4">No logs match the selected filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <div id="panel-passwords" class="sec-panel sms-sec-panel" role="tabpanel" data-sec-panel="passwords" <?= $initialPanel === 'passwords' ? '' : 'hidden' ?>>
        <section class="card sms-sec-card mb-4">
            <div class="card-body">
                <div class="sms-sec-card-head">
                    <div class="sms-sec-card-title">
                        <span class="sms-sec-icon"><?= smsIcon('key', ['aria-hidden' => 'true']) ?></span>
                        <div>
                            <h2 class="h5 fw-bold mb-0"><?= e($moduleLabel) ?> — Password Management</h2>
                            <p class="sms-sec-lead mb-0 mt-1">
                                Change your password yourself (current password + verification code), or request a new one from Super Admin.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row g-3 sms-sec-pw-split">
                    <div class="col-lg-6">
                        <div class="sms-sec-pw-box h-100">
                            <h3 class="h6 fw-bold mb-2">
                                <?= smsIcon('lock', ['class' => 'text-sms-primary me-1', 'aria-hidden' => 'true']) ?>Change password
                            </h3>
                            <?php if ($step === 'otp' && !empty($_SESSION['pending_pw_change'])): ?>
                                <p class="sms-sec-lead">
                                    Enter the 6-digit code from your
                                    <?= smsAuthenticatorIsEnabled($userId) ? 'Authenticator app or email' : 'email' ?>
                                    to finish updating your password.
                                </p>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="confirm_otp">
                                    <div class="mb-3">
                                        <?= smsOtpInput('otp_code', [
                                            'id' => 'otp_code',
                                            'required' => true,
                                            'autofocus' => true,
                                            'label' => 'Verification code',
                                            'hint' => 'Paste the full code — boxes fill automatically.',
                                        ]) ?>
                                    </div>
                                    <button type="submit" class="btn btn-sms-primary">Confirm &amp; update password</button>
                                    <a class="btn btn-outline-secondary ms-2" href="<?= e($baseUrl) ?>&tab=account">Cancel</a>
                                </form>
                            <?php else: ?>
                                <p class="sms-sec-lead">
                                    Enter your current password and a new one. You’ll confirm with
                                    <?= smsAuthenticatorIsEnabled($userId) ? 'Authenticator or email OTP' : 'an email OTP' ?>.
                                </p>
                                <form method="POST" autocomplete="off">
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
                                        <?= smsIcon('mobile-alt', ['class' => 'me-1']) ?>Continue with verification
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="sms-sec-pw-box h-100">
                            <h3 class="h6 fw-bold mb-2">
                                <?= smsIcon('paper-plane', ['class' => 'text-sms-primary me-1', 'aria-hidden' => 'true']) ?>Request from Super Admin
                            </h3>
                            <?php if ($myPending): ?>
                                <div class="alert alert-info mb-0">
                                    You already have a <strong>pending</strong> request
                                    (<?= e(date('M j, Y g:i A', strtotime((string) $myPending['created_at']))) ?>).
                                    Reason: <?= e($myPending['reason'] ?: '—') ?>.
                                </div>
                            <?php else: ?>
                                <?php if ($myRejected): ?>
                                    <div class="alert alert-warning">
                                        <strong>Your last request was rejected</strong>
                                        (<?= e(date('M j, Y g:i A', strtotime((string) ($myRejected['resolved_at'] ?? $myRejected['created_at'])))) ?>).
                                        <?php if (!empty($myRejected['admin_note'])): ?>
                                            <div class="mt-2 mb-0">
                                                <span class="fw-semibold">Note from Super Admin:</span>
                                                <?= e((string) $myRejected['admin_note']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <p class="sms-sec-lead">Forgot your current password? Ask Super Admin to approve a new one.</p>
                                <form method="POST" class="mt-1" autocomplete="off">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="request_admin_reset">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="reason">Reason <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="reason" name="reason" rows="3" maxlength="500" required
                                                  placeholder="e.g. Forgot password / need to update credentials"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="requested_password">New password you want <span class="text-danger">*</span></label>
                                        <?= smsPasswordInput(['id' => 'requested_password', 'name' => 'requested_password', 'required' => true, 'minlength' => $minLen, 'autocomplete' => 'new-password']) ?>
                                        <?= smsPasswordStrengthMarkup('requested_password') ?>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold" for="requested_password_confirm">Confirm new password <span class="text-danger">*</span></label>
                                        <?= smsPasswordInput(['id' => 'requested_password_confirm', 'name' => 'requested_password_confirm', 'required' => true, 'minlength' => $minLen, 'autocomplete' => 'new-password']) ?>
                                    </div>
                                    <button type="submit" class="btn btn-sms-primary">
                                        <?= smsIcon('paper-plane', ['class' => 'me-1']) ?>Send request to Super Admin
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div id="panel-authenticator" class="sec-panel sms-sec-panel" role="tabpanel" data-sec-panel="authenticator" <?= $initialPanel === 'authenticator' ? '' : 'hidden' ?>>
        <?php
        smsRenderAuthPasskeySplit(
            $userId,
            $baseUrl . '&tab=authenticator',
            csrfField(),
            csrfToken(),
            $moduleLabel . ' — Authenticator & Passkey',
            'Manage Authenticator and passkeys for your ' . $moduleLabel . ' account.'
        );
        ?>
    </div>
</div>

<script src="<?= BASE_URL ?>/modules/user-management/assets/js/module-security.js?v=20260723e"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
