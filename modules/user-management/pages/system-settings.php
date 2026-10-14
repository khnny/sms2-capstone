<?php
/**
 * SMS 2 – User Management – System Settings
 */
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';

$pageTitle    = 'System Settings';
$activeModule = 'user-management';
$activePage   = 'system-settings';
$breadcrumbs  = [
    ['label' => 'User Management', 'url' => BASE_URL . '/modules/user-management/index.php'],
    ['label' => 'System Settings', 'url' => null],
];

requireAuth();
requireSuperAdmin();

/* ── Save Login / Security settings ─────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['settings_section'] ?? '') === 'security')) {
    if (!csrfVerify()) {
        $_SESSION['flash_settings_error'] = 'Security check failed. Please try again.';
    } else {
        $sessionTimeout = (int) ($_POST['session_timeout_minutes'] ?? 2);
        $maxFails = (int) ($_POST['max_failed_logins'] ?? 3);
        $lockValue = (int) ($_POST['lockout_value'] ?? 5);
        $lockUnit = strtolower(trim((string) ($_POST['lockout_unit'] ?? 'minutes')));
        $minLen = (int) ($_POST['min_password_length'] ?? 8);
        $expiry = (int) ($_POST['password_expiry_days'] ?? 0);
        $requireFirst = !empty($_POST['require_password_change_first_login']) ? '1' : '0';

        $allowedUnits = ['seconds', 'minutes', 'hours', 'days'];
        if (!in_array($lockUnit, $allowedUnits, true)) {
            $lockUnit = 'minutes';
        }

        $multipliers = [
            'seconds' => 1,
            'minutes' => 60,
            'hours'   => 3600,
            'days'    => 86400,
        ];

        // Free-edit values with soft safety bounds
        $sessionTimeout = max(1, min(1440, $sessionTimeout));   // 1 min … 24 hours
        $maxFails = max(0, min(100, $maxFails));                // 0 = unlimited
        $lockValue = max(1, min(999999, $lockValue));
        $lockSeconds = (int) ($lockValue * $multipliers[$lockUnit]);
        $lockSeconds = max(1, min(604800, $lockSeconds));       // 1 sec … 7 days
        $minLen = max(6, min(64, $minLen));
        $expiry = max(0, min(3650, $expiry));                   // 0 = never

        smsSetSetting('session_timeout_minutes', (string) $sessionTimeout);
        smsSetSetting('max_failed_logins', (string) $maxFails);
        smsSetSetting('lockout_value', (string) $lockValue);
        smsSetSetting('lockout_unit', $lockUnit);
        smsSetSetting('lockout_seconds', (string) $lockSeconds);
        smsSetSetting('lockout_minutes', (string) max(1, (int) ceil($lockSeconds / 60)));
        smsSetSetting('min_password_length', (string) $minLen);
        smsSetSetting('password_expiry_days', (string) $expiry);
        smsSetSetting('require_password_change_first_login', $requireFirst);

        $captchaEnabled = !empty($_POST['login_captcha_enabled']) ? '1' : '0';
        $turnstileSite = trim((string) ($_POST['turnstile_site_key'] ?? ''));
        $turnstileSecret = trim((string) ($_POST['turnstile_secret_key'] ?? ''));
        smsSetSetting('login_captcha_enabled', $captchaEnabled);
        smsSetSetting('turnstile_site_key', $turnstileSite);
        // Keep existing secret if the field is left blank (placeholder save pattern)
        if ($turnstileSecret !== '') {
            if ($turnstileSite !== '' && hash_equals($turnstileSite, $turnstileSecret)) {
                $_SESSION['flash_settings_error'] = 'Turnstile Site Key and Secret Key must be different. Copy the Secret Key from Cloudflare (not the Site Key).';
                header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php?saved=security');
                exit;
            }
            smsSetSetting('turnstile_secret_key', $turnstileSecret);
        }
        if (!empty($_POST['turnstile_secret_clear'])) {
            smsSetSetting('turnstile_secret_key', '');
        }

        logActivity('update', 'Updated login security settings', 'user-management');
        $_SESSION['flash_settings_success'] = 'Login security settings saved.';
    }
    header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php?saved=security');
    exit;
}

/* ── Save Notification / SMTP settings ──────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['settings_section'] ?? '') === 'notifications')) {
    if (!csrfVerify()) {
        $_SESSION['flash_settings_error'] = 'Security check failed. Please try again.';
    } else {
        $fromEmail = trim((string) ($_POST['mail_from_email'] ?? ''));
        $fromName = trim((string) ($_POST['mail_from_name'] ?? ''));
        $adminEmail = trim((string) ($_POST['mail_admin_email'] ?? ''));
        $smtpHost = trim((string) ($_POST['smtp_host'] ?? ''));
        $smtpPort = (int) ($_POST['smtp_port'] ?? 587);
        $smtpEnc = strtolower(trim((string) ($_POST['smtp_encryption'] ?? 'tls')));
        $smtpUser = trim((string) ($_POST['smtp_username'] ?? ''));
        $smtpPass = (string) ($_POST['smtp_password'] ?? '');
        $showLink = !empty($_POST['mail_show_link_on_failure']) ? '1' : '0';

        if ($fromEmail !== '' && !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_settings_error'] = 'System From email is invalid.';
        } elseif ($adminEmail !== '' && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_settings_error'] = 'Admin alert email is invalid.';
        } else {
            if (!in_array($smtpEnc, ['none', 'tls', 'ssl'], true)) {
                $smtpEnc = 'tls';
            }
            $smtpPort = max(1, min(65535, $smtpPort));

            smsSetSetting('mail_from_email', $fromEmail !== '' ? $fromEmail : 'noreply@bestlink.edu.ph');
            smsSetSetting('mail_from_name', $fromName !== '' ? $fromName : APP_SHORT_NAME);
            smsSetSetting('mail_admin_email', $adminEmail);
            smsSetSetting('smtp_host', $smtpHost);
            smsSetSetting('smtp_port', (string) $smtpPort);
            smsSetSetting('smtp_encryption', $smtpEnc);
            smsSetSetting('smtp_username', $smtpUser);
            // Keep previous password if blank (so Save doesn't wipe it)
            require_once ROOT_PATH . '/includes/mail.php';
            $existingPass = smsSmtpPassword();
            if ($smtpPass !== '') {
                $smtpPass = preg_replace('/\s+/', '', $smtpPass) ?? $smtpPass;
                smsSetSetting('smtp_password', $smtpPass);
                // Prove encrypt/decrypt round-trip with current app.key
                if (isset($GLOBALS['__sms_settings_cache']) && is_array($GLOBALS['__sms_settings_cache'])) {
                    unset($GLOBALS['__sms_settings_cache']['smtp_password']);
                }
                if (smsSetting('smtp_password', '') !== $smtpPass) {
                    $_SESSION['flash_settings_error'] = 'SMTP password could not be saved securely (encryption key issue). Check that storage/keys/ is writable, then try again.';
                    header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php?saved=notifications');
                    exit;
                }
            } elseif ($smtpUser !== '' && $existingPass === '') {
                $_SESSION['flash_settings_error'] = 'SMTP username is set but no App Password is stored. Enter your Gmail App Password and Save.';
                header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php?saved=notifications');
                exit;
            }
            smsSetSetting('mail_show_link_on_failure', $showLink);

            logActivity('update', 'Updated notification / SMTP settings', 'user-management');
            $_SESSION['flash_settings_success'] = 'Notification settings saved. Use “Test” below to confirm email delivery.';
        }
    }
    header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php?saved=notifications');
    exit;
}

/* ── Test SMTP email ────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['settings_section'] ?? '') === 'notifications_test')) {
    require_once ROOT_PATH . '/includes/mail.php';
    if (!csrfVerify()) {
        $_SESSION['flash_settings_error'] = 'Security check failed. Please try again.';
    } else {
        $to = trim((string) ($_POST['test_email'] ?? ''));
        if ($to === '') {
            $to = trim(smsSetting('mail_admin_email', ''));
        }
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_settings_error'] = 'Enter a valid test email address.';
        } else {
            $result = smsSendMail(
                $to,
                APP_SHORT_NAME . ' test email',
                '<p>This is a test email from <strong>' . htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') . '</strong>.</p><p>SMTP is working.</p>',
                "This is a test email from " . APP_NAME . ".\n\nSMTP is working.\n"
            );
            if (!empty($result['ok'])) {
                $_SESSION['flash_settings_success'] = 'Test email sent to ' . $to . '.';
            } else {
                $_SESSION['flash_settings_error'] = 'Test email failed: ' . ($result['error'] ?? 'unknown error');
            }
        }
    }
    header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php?saved=notifications');
    exit;
}

/* ── Save / toggle global maintenance mode ──────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['settings_section'] ?? '') === 'maintenance')) {
    require_once ROOT_PATH . '/includes/module-controls.php';
    if (!csrfVerify()) {
        $_SESSION['flash_settings_error'] = 'Security check failed. Please try again.';
    } else {
        $enabled = !empty($_POST['system_maintenance']);
        $message = trim((string) ($_POST['system_maintenance_msg'] ?? ''));
        smsSetSystemMaintenance($enabled, $message);
        logActivity(
            'update',
            $enabled ? 'Enabled global system maintenance mode' : 'Disabled global system maintenance mode',
            'user-management'
        );
        $_SESSION['flash_settings_success'] = $enabled
            ? 'Maintenance mode is on. Non-admin users are locked out immediately.'
            : 'Maintenance mode is off. Users can sign in again.';
    }
    header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php?saved=maintenance');
    exit;
}

/* ── Encrypted database backup (download) ───────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['settings_section'] ?? '') === 'encrypted_backup')) {
    require_once ROOT_PATH . '/includes/backup.php';
    if (!csrfVerify()) {
        $_SESSION['flash_settings_error'] = 'Security check failed. Please try again.';
        header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php');
        exit;
    }
    $pass = (string) ($_POST['backup_password'] ?? '');
    $confirm = (string) ($_POST['backup_password_confirm'] ?? '');
    if (strlen($pass) < 8) {
        $_SESSION['flash_settings_error'] = 'Backup password must be at least 8 characters.';
        header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php');
        exit;
    }
    if (!hash_equals($pass, $confirm)) {
        $_SESSION['flash_settings_error'] = 'Backup passwords do not match.';
        header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php');
        exit;
    }
    $result = smsCreateEncryptedBackup($pass);
    if (empty($result['ok']) || empty($result['path']) || !is_readable((string) $result['path'])) {
        $_SESSION['flash_settings_error'] = 'Could not create encrypted backup'
            . (!empty($result['error']) ? ': ' . $result['error'] : '.');
        header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php');
        exit;
    }
    $path = (string) $result['path'];
    $name = basename($path);
    logActivity('export', 'Downloaded encrypted database backup', 'user-management');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: no-store');
    readfile($path);
    exit;
}

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';

$saved = $_GET['saved'] ?? '';
$flashSuccess = '';
$flashError = '';
if (!empty($_SESSION['flash_settings_success'])) {
    $flashSuccess = (string) $_SESSION['flash_settings_success'];
    unset($_SESSION['flash_settings_success']);
}
if (!empty($_SESSION['flash_settings_error'])) {
    $flashError = (string) $_SESSION['flash_settings_error'];
    unset($_SESSION['flash_settings_error']);
}

$sessionTimeout = (int) smsSetting('session_timeout_minutes', '2');
$maxFails = (int) smsSetting('max_failed_logins', '3');
$lockSeconds = (int) smsSetting('lockout_seconds', '0');
if ($lockSeconds <= 0) {
    $lockSeconds = max(1, (int) smsSetting('lockout_minutes', '5')) * 60;
}
$lockValue = (int) smsSetting('lockout_value', '0');
$lockUnit = strtolower(trim((string) smsSetting('lockout_unit', '')));
$allowedUnits = ['seconds', 'minutes', 'hours', 'days'];
if ($lockValue <= 0 || !in_array($lockUnit, $allowedUnits, true)) {
    // Derive a readable value + unit from stored seconds
    if ($lockSeconds % 86400 === 0) {
        $lockValue = (int) ($lockSeconds / 86400);
        $lockUnit = 'days';
    } elseif ($lockSeconds % 3600 === 0) {
        $lockValue = (int) ($lockSeconds / 3600);
        $lockUnit = 'hours';
    } elseif ($lockSeconds % 60 === 0) {
        $lockValue = (int) ($lockSeconds / 60);
        $lockUnit = 'minutes';
    } else {
        $lockValue = $lockSeconds;
        $lockUnit = 'seconds';
    }
}
$minLen = (int) smsSetting('min_password_length', '8');
$expiry = (int) smsSetting('password_expiry_days', '0');
$requireFirst = smsSetting('require_password_change_first_login', '0') === '1';

$mailFromEmail = smsSetting('mail_from_email', 'noreply@bestlink.edu.ph');
$mailFromName = smsSetting('mail_from_name', APP_SHORT_NAME);
$mailAdminEmail = smsSetting('mail_admin_email', '');
$smtpHost = smsSetting('smtp_host', '');
$smtpPort = (int) smsSetting('smtp_port', '587');
$smtpEnc = strtolower(smsSetting('smtp_encryption', 'tls'));
$smtpUser = smsSetting('smtp_username', '');
require_once ROOT_PATH . '/includes/mail.php';
$smtpPassSet = smsSmtpPassword() !== '';
$mailShowLink = smsSetting('mail_show_link_on_failure', '0') === '1';
$smtpNeedsPassword = $smtpUser !== '' && !$smtpPassSet;
$captchaEnabled = smsSetting('login_captcha_enabled', '1') === '1';
$turnstileSite = smsSetting('turnstile_site_key', '');
$turnstileSecretSet = smsSetting('turnstile_secret_key', '') !== '';

require_once ROOT_PATH . '/includes/module-controls.php';
$systemMaintenance = smsIsSystemInMaintenance();
$systemMaintenanceMsg = trim(smsSetting('system_maintenance_msg', ''));
?>

<link href="<?= BASE_URL ?>/modules/user-management/assets/css/user-management.css" rel="stylesheet">
<!-- Toast container -->
<div id="umToastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index:1100;"></div>

<?php
$pageBannerIcon        = 'fa-sliders-h';
$pageBannerDescription = 'Configure application-wide settings, school year, security rules, and global feature toggles.';
renderBreadcrumbs($breadcrumbs);
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div></div>
    <span class="placeholder-badge"><?= smsIcon('lock', ['class' => 'me-1']) ?>Superadmin Only</span>
</div>

<?php if ($flashSuccess || $saved === 'security' || $saved === 'notifications'): ?>
    <div class="alert alert-success"><?= e($flashSuccess !== '' ? $flashSuccess : ($saved === 'notifications' ? 'Notification settings saved.' : 'Login security settings saved.')) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="row g-4">

    <!-- Left column: App + Academic + Security -->
    <div class="col-lg-7">

        <!-- Application Identity -->
        <section class="card mb-4 settings-form">
            <div class="card-body">
                <div class="settings-section-head">
                    <div class="settings-icon"><?= smsIcon('university') ?></div>
                    <div>
                        <h6>Application Identity</h6>
                        <p>System name, institution, and branding settings.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Application Name</label>
                        <input type="text" class="form-control" value="Student Management System 2">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Short Name</label>
                        <input type="text" class="form-control" value="BCP">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Institution</label>
                        <input type="text" class="form-control" value="Bestlink College of the Philippines">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">App Version</label>
                        <input type="text" class="form-control" value="1.0.0" readonly>
                        <div class="form-text">Managed via deployment — read-only.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Base URL</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars(BASE_URL !== '' ? BASE_URL : '/sms2_system') ?>">
                        <div class="form-text">Change if deployed under a different folder.</div>
                    </div>
                </div>
            </div>
            <div class="settings-save-bar">
                <button type="button" class="btn btn-outline-secondary btn-sm">Reset</button>
                <button type="button" class="btn btn-sms-primary btn-sm"
                        onclick="window.umShowToast('Application identity saved.','success')">
                    <?= smsIcon('save', ['class' => 'me-2']) ?>Save Changes
                </button>
            </div>
        </section>

        <!-- Academic Calendar -->
        <section class="card mb-4 settings-form">
            <div class="card-body">
                <div class="settings-section-head">
                    <div class="settings-icon" style="background:linear-gradient(145deg,#059669,#34d399);">
                        <?= smsIcon('calendar-alt') ?>
                    </div>
                    <div>
                        <h6>Academic Calendar</h6>
                        <p>Active school year, semester, and enrollment period.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Active School Year</label>
                        <select class="form-select">
                            <option selected>2025–2026</option>
                            <option>2026–2027</option>
                            <option>2024–2025</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Current Semester</label>
                        <select class="form-select">
                            <option>1st Semester</option>
                            <option selected>2nd Semester</option>
                            <option>Summer</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Enrollment Start</label>
                        <input type="date" class="form-control" value="2026-06-01">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Enrollment End</label>
                        <input type="date" class="form-control" value="2026-07-31">
                    </div>
                </div>
            </div>
            <div class="settings-save-bar">
                <button type="button" class="btn btn-outline-secondary btn-sm">Reset</button>
                <button type="button" class="btn btn-sms-primary btn-sm"
                        onclick="window.umShowToast('Academic calendar saved.','success')">
                    <?= smsIcon('save', ['class' => 'me-2']) ?>Save Changes
                </button>
            </div>
        </section>

        <!-- Security & Sessions -->
        <section class="card mb-4 settings-form">
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="settings_section" value="security">
                <div class="card-body">
                    <div class="settings-section-head">
                        <div class="settings-icon" style="background:linear-gradient(145deg,#dc2626,#f87171);">
                            <?= smsIcon('lock') ?>
                        </div>
                        <div>
                            <h6>Security &amp; Sessions</h6>
                            <p>Login attempts, lockout cooldown, session timeout, and password policy. Applies system-wide.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="session_timeout_minutes">Session Timeout (minutes)</label>
                            <input type="number" class="form-control" id="session_timeout_minutes" name="session_timeout_minutes"
                                   value="<?= (int) $sessionTimeout ?>" min="1" max="1440" step="1" required>
                            <div class="form-text">Idle time before auto sign-out (no mouse/keyboard). Default is 2 minutes.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="max_failed_logins">Max Failed Login Attempts</label>
                            <input type="number" class="form-control" id="max_failed_logins" name="max_failed_logins"
                                   value="<?= (int) $maxFails ?>" min="0" max="100" step="1" required>
                            <div class="form-text">Type any number. Use <strong>0</strong> for unlimited.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="lockout_value">Lockout Cooldown</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="lockout_value" name="lockout_value"
                                       value="<?= (int) $lockValue ?>" min="1" max="999999" step="1" required
                                       placeholder="e.g. 3">
                                <select class="form-select" id="lockout_unit" name="lockout_unit" style="max-width:8.5rem;">
                                    <option value="seconds" <?= $lockUnit === 'seconds' ? 'selected' : '' ?>>Seconds</option>
                                    <option value="minutes" <?= $lockUnit === 'minutes' ? 'selected' : '' ?>>Minutes</option>
                                    <option value="hours" <?= $lockUnit === 'hours' ? 'selected' : '' ?>>Hours</option>
                                    <option value="days" <?= $lockUnit === 'days' ? 'selected' : '' ?>>Days</option>
                                </select>
                            </div>
                            <div class="form-text">
                                Type any number, then choose the unit. Example: <strong>3</strong> + Minutes, or <strong>100</strong> + Seconds.
                                Currently equals <strong><?= (int) $lockSeconds ?></strong> seconds.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="min_password_length">Minimum Password Length</label>
                            <input type="number" class="form-control" id="min_password_length" name="min_password_length"
                                   value="<?= (int) $minLen ?>" min="6" max="64" step="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="password_expiry_days">Password Expiry (days)</label>
                            <input type="number" class="form-control" id="password_expiry_days" name="password_expiry_days"
                                   value="<?= (int) $expiry ?>" min="0" max="3650" step="1" required>
                            <div class="form-text">Type any number of days. Use <strong>0</strong> for never.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold d-flex align-items-center gap-2 mb-1">
                                <label class="um-toggle mb-0">
                                    <input type="checkbox" name="require_password_change_first_login" value="1"
                                           <?= $requireFirst ? 'checked' : '' ?>>
                                    <span class="um-toggle-track"></span>
                                </label>
                                Require password change on first login
                            </label>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-12">
                            <h6 class="fw-bold mb-1"><?= smsIcon('robot', ['class' => 'text-sms-primary me-1']) ?>Login CAPTCHA</h6>
                            <p class="small text-muted mb-2">
                                One-click check (Cloudflare / Discord style) before password and 2FA.
                                With Turnstile keys, Cloudflare’s widget is used.
                                Without keys, BCP shows a local one-click “Verify you are human” box.
                            </p>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold d-flex align-items-center gap-2 mb-1">
                                <label class="um-toggle mb-0">
                                    <input type="checkbox" name="login_captcha_enabled" value="1"
                                           <?= $captchaEnabled ? 'checked' : '' ?>>
                                    <span class="um-toggle-track"></span>
                                </label>
                                Require CAPTCHA on login
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="turnstile_site_key">Cloudflare Turnstile Site Key</label>
                            <input type="text" class="form-control" id="turnstile_site_key" name="turnstile_site_key"
                                   value="<?= e($turnstileSite) ?>" autocomplete="off" placeholder="0x4AAAA…">
                            <div class="form-text">
                                Free keys: <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener">Cloudflare Turnstile</a>
                                (add your domain or <code>localhost</code>).
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="turnstile_secret_key">Cloudflare Turnstile Secret Key</label>
                            <input type="password" class="form-control" id="turnstile_secret_key" name="turnstile_secret_key"
                                   value="" autocomplete="new-password"
                                   placeholder="<?= $turnstileSecretSet ? '•••••••• (leave blank to keep)' : 'Enter secret key' ?>">
                            <div class="form-text">
                                Paste the <strong>Secret Key</strong> (not the Site Key). They look similar but must be different.
                            </div>
                            <?php if ($turnstileSecretSet): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="turnstile_secret_clear" name="turnstile_secret_clear">
                                    <label class="form-check-label small" for="turnstile_secret_clear">Clear saved secret (use local one-click CAPTCHA)</label>
                                </div>
                            <?php else: ?>
                                <div class="form-text">No secret saved yet — local one-click CAPTCHA is active.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="settings-save-bar">
                    <a href="<?= BASE_URL ?>/modules/user-management/pages/system-settings.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                    <button type="submit" class="btn btn-sms-primary btn-sm">
                        <?= smsIcon('save', ['class' => 'me-2']) ?>Save Changes
                    </button>
                </div>
            </form>
        </section>

    </div><!-- /left col -->

    <!-- Right column: Features + Notifications + Maintenance -->
    <div class="col-lg-5">

        <!-- Feature Toggles -->
        <section class="card mb-4 settings-form">
            <div class="card-body">
                <div class="settings-section-head">
                    <div class="settings-icon" style="background:linear-gradient(145deg,#7c3aed,#a78bfa);">
                        <?= smsIcon('toggle-on') ?>
                    </div>
                    <div>
                        <h6>Feature Toggles</h6>
                        <p>Enable or disable system-wide features.</p>
                    </div>
                </div>
                <?php
                $toggles = [
                    ['label'=>'Online Pre-registration',  'checked'=>true],
                    ['label'=>'Online Payment Gateway',   'checked'=>false],
                    ['label'=>'LMS Module',               'checked'=>true],
                    ['label'=>'Student Portal',           'checked'=>true],
                    ['label'=>'Activity Logging',         'checked'=>true],
                    ['label'=>'Email Notifications',      'checked'=>false],
                ];
                foreach ($toggles as $toggle): ?>
                <div class="d-flex align-items-center justify-content-between py-2"
                     style="border-bottom:1px solid var(--sms-border-soft);">
                    <span style="font-size:.85rem;font-weight:600;color:var(--sms-text);">
                        <?= htmlspecialchars($toggle['label']) ?>
                    </span>
                    <label class="um-toggle mb-0">
                        <input type="checkbox" <?= $toggle['checked'] ? 'checked' : '' ?>>
                        <span class="um-toggle-track"></span>
                    </label>
                </div>
                <?php endforeach; ?>
                <form method="POST" class="pt-2" autocomplete="off">
                    <?= csrfField() ?>
                    <input type="hidden" name="settings_section" value="maintenance">
                    <input type="hidden" name="system_maintenance" value="0">
                    <div class="d-flex align-items-center justify-content-between py-2"
                         style="border-bottom:1px solid var(--sms-border-soft);">
                        <div>
                            <span style="font-size:.85rem;font-weight:600;color:var(--sms-text);display:block;">
                                Maintenance Mode
                            </span>
                            <span class="text-muted" style="font-size:.72rem;">
                                Locks out all non-admin users immediately.
                            </span>
                        </div>
                        <label class="um-toggle mb-0">
                            <input type="checkbox" name="system_maintenance" value="1"
                                   <?= $systemMaintenance ? 'checked' : '' ?>
                                   onchange="this.form.requestSubmit()">
                            <span class="um-toggle-track"></span>
                        </label>
                    </div>
                    <div class="mt-2">
                        <label class="form-label fw-semibold" for="system_maintenance_msg" style="font-size:.8rem;">
                            Maintenance message (optional)
                        </label>
                        <textarea class="form-control form-control-sm" id="system_maintenance_msg"
                                  name="system_maintenance_msg" rows="2"
                                  maxlength="500"
                                  placeholder="The system is temporarily unavailable for maintenance. Please try again later."><?= e($systemMaintenanceMsg) ?></textarea>
                    </div>
                    <div class="settings-save-bar mt-2 px-0">
                        <button type="submit" class="btn btn-sms-primary btn-sm w-100">
                            <?= smsIcon('save', ['class' => 'me-2']) ?>Save Maintenance Settings
                        </button>
                    </div>
                </form>
            </div>
            <div class="settings-save-bar">
                <button type="button" class="btn btn-outline-secondary btn-sm w-100"
                        onclick="window.umShowToast('Other feature toggles are not wired yet.','info')">
                    <?= smsIcon('save', ['class' => 'me-2']) ?>Save Other Toggles
                </button>
            </div>
        </section>

        <!-- Notification Settings -->
        <section class="card mb-4 settings-form">
            <div class="card-body">
                <div class="settings-section-head">
                    <div class="settings-icon" style="background:linear-gradient(145deg,#d97706,#fbbf24);">
                        <?= smsIcon('bell') ?>
                    </div>
                    <div>
                        <h6>Notifications / Email</h6>
                        <p>SMTP settings used to email password-reset links to each user’s Gmail/email.</p>
                    </div>
                </div>
                <?php if ($smtpNeedsPassword): ?>
                    <div class="alert alert-warning py-2 small mb-3">
                        SMTP username <strong><?= e($smtpUser) ?></strong> is set, but the App Password is missing
                        (previous value could not be decrypted after <code>app.key</code> changed).
                        Paste your Gmail App Password below, Save, then click <strong>Test</strong>.
                    </div>
                <?php endif; ?>
                <form method="POST" autocomplete="off">
                    <?= csrfField() ?>
                    <input type="hidden" name="settings_section" value="notifications">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="mail_from_email">System Email (From)</label>
                            <input type="email" class="form-control" id="mail_from_email" name="mail_from_email"
                                   value="<?= e($mailFromEmail) ?>" placeholder="noreply@bestlink.edu.ph">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="mail_from_name">From Name</label>
                            <input type="text" class="form-control" id="mail_from_name" name="mail_from_name"
                                   value="<?= e($mailFromName) ?>" placeholder="<?= e(APP_SHORT_NAME) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="mail_admin_email">Admin Alert / Test Email</label>
                            <input type="email" class="form-control" id="mail_admin_email" name="mail_admin_email"
                                   value="<?= e($mailAdminEmail) ?>" placeholder="admin@gmail.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="smtp_host">SMTP Server</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                   value="<?= e($smtpHost) ?>" placeholder="smtp.gmail.com">
                            <div class="form-text">For Gmail use <code>smtp.gmail.com</code>, port <code>587</code>, TLS, and an App Password.</div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="smtp_port">SMTP Port</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                   value="<?= (int) ($smtpPort ?: 587) ?>" min="1" max="65535">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold" for="smtp_encryption">Encryption</label>
                            <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                <option value="none" <?= $smtpEnc === 'none' ? 'selected' : '' ?>>None</option>
                                <option value="tls" <?= $smtpEnc === 'tls' || $smtpEnc === '' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= $smtpEnc === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="smtp_username">SMTP Username</label>
                            <input type="text" class="form-control" id="smtp_username" name="smtp_username"
                                   value="<?= e($smtpUser) ?>" placeholder="your.account@gmail.com" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="smtp_password">SMTP Password / App Password</label>
                            <input type="password" class="form-control <?= $smtpNeedsPassword ? 'is-invalid' : '' ?>" id="smtp_password" name="smtp_password"
                                   value="" placeholder="<?= $smtpPassSet ? '•••••••• (saved — leave blank to keep)' : '16-char Gmail App Password' ?>"
                                   autocomplete="new-password" <?= $smtpNeedsPassword ? 'required' : '' ?>>
                            <?php if ($smtpNeedsPassword): ?>
                                <div class="invalid-feedback d-block">
                                    Required: previous password could not be decrypted (app.key changed). Paste your Gmail App Password and Save, then click Test.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mail_show_link_on_failure"
                                       name="mail_show_link_on_failure" value="1" <?= $mailShowLink ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mail_show_link_on_failure">
                                    Dev only: if email fails, show OTP on-screen for authenticated setup flows (never on public login / forgot password)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="settings-save-bar mt-3">
                        <button type="submit" class="btn btn-sms-primary btn-sm">
                            <?= smsIcon('save', ['class' => 'me-2']) ?>Save Email Settings
                        </button>
                    </div>
                </form>
                <hr class="my-3">
                <form method="POST" class="row g-2 align-items-end">
                    <?= csrfField() ?>
                    <input type="hidden" name="settings_section" value="notifications_test">
                    <div class="col">
                        <label class="form-label fw-semibold" for="test_email">Send test email to</label>
                        <input type="email" class="form-control" id="test_email" name="test_email"
                               value="<?= e($mailAdminEmail) ?>" placeholder="you@gmail.com">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                            <?= smsIcon('paper-plane', ['class' => 'me-1']) ?>Test
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Encrypted backup -->
        <section class="card mb-4">
            <div class="card-body">
                <div class="settings-section-head">
                    <div class="settings-icon" style="background:linear-gradient(145deg,#0f766e,#2dd4bf);">
                        <?= smsIcon('shield-alt') ?>
                    </div>
                    <div>
                        <h6>Encrypted database backup</h6>
                        <p>Download a password-protected backup (AES-256). Store the password separately from the file.</p>
                    </div>
                </div>
                <form method="POST" class="row g-3" autocomplete="off">
                    <?= csrfField() ?>
                    <input type="hidden" name="settings_section" value="encrypted_backup">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="backup_password">Backup password</label>
                        <input type="password" class="form-control" id="backup_password" name="backup_password"
                               minlength="8" required autocomplete="new-password"
                               placeholder="At least 8 characters">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="backup_password_confirm">Confirm password</label>
                        <input type="password" class="form-control" id="backup_password_confirm" name="backup_password_confirm"
                               minlength="8" required autocomplete="new-password">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-sms-primary btn-sm">
                            <?= smsIcon('download', ['class' => 'me-2']) ?>Create &amp; download encrypted backup
                        </button>
                        <p class="text-muted small mt-2 mb-0">
                            CLI alternative:
                            <code>php database/backup-encrypted.php "YourPassword"</code>
                        </p>
                    </div>
                </form>
            </div>
        </section>

        <!-- Danger zone -->
        <section class="card border" style="border-color:rgba(220,38,38,0.30) !important;">
            <div class="card-body">
                <div class="settings-section-head">
                    <div class="settings-icon" style="background:linear-gradient(145deg,#dc2626,#f87171);">
                        <?= smsIcon('exclamation-triangle') ?>
                    </div>
                    <div>
                        <h6 style="color:var(--sms-danger);">Danger Zone</h6>
                        <p>Irreversible system actions — proceed with caution.</p>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-warning btn-sm text-start"
                            data-um-confirm-type="warning"
                            data-um-confirm="Archive old activity logs? Prefer exporting first. This is a retention action, not a routine delete.">
                        <?= smsIcon('archive', ['class' => 'me-2']) ?>Archive Old Activity Logs
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm text-start"
                            data-um-confirm="Reset all user passwords to default? This cannot be undone.">
                        <?= smsIcon('key', ['class' => 'me-2']) ?>Reset All User Passwords
                    </button>
                    <?php if ($systemMaintenance): ?>
                    <form method="POST" class="m-0">
                        <?= csrfField() ?>
                        <input type="hidden" name="settings_section" value="maintenance">
                        <input type="hidden" name="system_maintenance" value="0">
                        <input type="hidden" name="system_maintenance_msg" value="<?= e($systemMaintenanceMsg) ?>">
                        <button type="submit" class="btn btn-outline-success btn-sm text-start w-100"
                                data-um-confirm-type="warning"
                                data-um-confirm="Turn off maintenance mode? Users will be able to sign in again.">
                            <?= smsIcon('unlock', ['class' => 'me-2']) ?>Disable Maintenance Mode
                        </button>
                    </form>
                    <?php else: ?>
                    <form method="POST" class="m-0">
                        <?= csrfField() ?>
                        <input type="hidden" name="settings_section" value="maintenance">
                        <input type="hidden" name="system_maintenance" value="1">
                        <input type="hidden" name="system_maintenance_msg" value="<?= e($systemMaintenanceMsg) ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm text-start w-100"
                                data-um-confirm="Enable maintenance mode? All non-admin users will be locked out immediately.">
                            <?= smsIcon('tools', ['class' => 'me-2']) ?>Enable Maintenance Mode
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>

    </div><!-- /right col -->
</div><!-- /row -->

<script src="<?= BASE_URL ?>/modules/user-management/assets/js/user-management.js?v=20260831"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
