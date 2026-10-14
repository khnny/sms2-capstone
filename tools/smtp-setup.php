<?php
/**
 * Localhost-only SMTP App Password setup + test send.
 * Visit: http://localhost/sms2_system/tools/smtp-setup.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/mail.php';

$remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isLocal = in_array($remote, ['127.0.0.1', '::1'], true)
    || str_starts_with($host, 'localhost')
    || str_starts_with($host, '127.0.0.1');

if (!$isLocal) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden — localhost only.";
    exit;
}

$flashOk = '';
$flashErr = '';
$pdo = db();

function smsSetupFindSuperadmin(): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }
    $stmt = $pdo->query(
        "SELECT id, username, email, full_name, role_key, status
         FROM `sms2_users`
         WHERE role_key = 'superadmin'
         ORDER BY id ASC
         LIMIT 1"
    );
    $row = $stmt ? $stmt->fetch() : false;
    return $row ?: null;
}

$super = smsSetupFindSuperadmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'save');
    $smtpUser = trim((string) ($_POST['smtp_username'] ?? ''));
    $smtpPass = preg_replace('/\s+/', '', (string) ($_POST['smtp_password'] ?? '')) ?? '';
    $superEmail = trim((string) ($_POST['superadmin_email'] ?? ''));
    $testTo = trim((string) ($_POST['test_to'] ?? ''));

    if ($action === 'save' || $action === 'save_test') {
        if ($smtpUser === '' || !filter_var($smtpUser, FILTER_VALIDATE_EMAIL)) {
            $flashErr = 'Enter a valid SMTP / Gmail address (sender account).';
        } elseif ($smtpPass === '' || strlen($smtpPass) < 8) {
            $flashErr = 'Paste your Gmail App Password (16 characters).';
        } elseif ($superEmail !== '' && !filter_var($superEmail, FILTER_VALIDATE_EMAIL)) {
            $flashErr = 'Superadmin email is invalid.';
        } else {
            smsSetSetting('smtp_host', 'smtp.gmail.com');
            smsSetSetting('smtp_port', '587');
            smsSetSetting('smtp_encryption', 'tls');
            smsSetSetting('smtp_username', $smtpUser);
            smsSetSetting('smtp_password', $smtpPass);
            smsSetSetting('mail_from_email', $smtpUser);
            smsSetSetting('mail_from_name', APP_SHORT_NAME);
            if ($testTo === '' && $superEmail !== '') {
                smsSetSetting('mail_admin_email', $superEmail);
            } elseif ($testTo !== '') {
                smsSetSetting('mail_admin_email', $testTo);
            }

            if (isset($GLOBALS['__sms_settings_cache']) && is_array($GLOBALS['__sms_settings_cache'])) {
                unset($GLOBALS['__sms_settings_cache']['smtp_password']);
            }
            if (smsSetting('smtp_password', '') !== $smtpPass) {
                $flashErr = 'Could not encrypt/store the App Password. Check storage/keys permissions.';
            } else {
                if ($super && $superEmail !== '' && strcasecmp((string) $super['email'], $superEmail) !== 0) {
                    // Keep email unique
                    $chk = $pdo->prepare('SELECT id FROM `sms2_users` WHERE LOWER(email) = LOWER(?) AND id <> ? LIMIT 1');
                    $chk->execute([$superEmail, (int) $super['id']]);
                    if ($chk->fetch()) {
                        $flashErr = 'That email is already used by another account. Pick a different Gmail for superadmin.';
                    } else {
                        $upd = $pdo->prepare('UPDATE `sms2_users` SET email = ? WHERE id = ? AND role_key = ?');
                        $upd->execute([$superEmail, (int) $super['id'], 'superadmin']);
                        $super = smsSetupFindSuperadmin();
                    }
                }

                if ($flashErr === '') {
                    $flashOk = 'SMTP settings saved.';
                    if ($action === 'save_test') {
                        $to = $testTo !== '' ? $testTo : ($superEmail !== '' ? $superEmail : $smtpUser);
                        $result = smsSendMail(
                            $to,
                            APP_SHORT_NAME . ' SMTP test',
                            '<p>Test email from <strong>SMS2 PHPMailer</strong>. If you see this, forgot-password email will work.</p>',
                            'Test email from SMS2 PHPMailer.'
                        );
                        if (!empty($result['ok'])) {
                            $flashOk .= ' Test email sent to ' . $to . '.';
                        } else {
                            $flashErr = 'Saved, but test send failed: ' . ($result['error'] ?? 'unknown');
                            $flashOk = '';
                        }
                    }
                }
            }
        }
    }
}

$smtpUser = smsSetting('smtp_username', 'j14677365@gmail.com');
$from = smsSetting('mail_from_email', $smtpUser);
$passSet = smsSmtpPassword() !== '';
$superEmail = $super['email'] ?? '';
$adminEmail = smsSetting('mail_admin_email', $superEmail);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMTP Setup — <?= htmlspecialchars(APP_SHORT_NAME) ?></title>
    <style>
        body { font-family: Segoe UI, Arial, sans-serif; max-width: 560px; margin: 40px auto; padding: 0 16px; color: #0f172a; }
        h1 { font-size: 1.25rem; margin-bottom: 0.25rem; }
        .muted { color: #64748b; font-size: 0.9rem; margin-bottom: 1.25rem; }
        label { display: block; font-weight: 600; margin: 0.75rem 0 0.35rem; font-size: 0.9rem; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; }
        .row { display: flex; gap: 8px; margin-top: 1rem; flex-wrap: wrap; }
        button { border: 0; border-radius: 8px; padding: 10px 14px; font-weight: 700; cursor: pointer; }
        .primary { background: #294ecb; color: #fff; }
        .secondary { background: #e2e8f0; color: #0f172a; }
        .ok { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; padding: 10px 12px; border-radius: 8px; margin-bottom: 1rem; }
        .err { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 12px; border-radius: 8px; margin-bottom: 1rem; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 1rem; font-size: 0.9rem; }
        code { background: #e2e8f0; padding: 1px 5px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>SMTP setup (localhost)</h1>
    <p class="muted">Paste your Gmail App Password once. Forgot-password will email the <strong>registered</strong> account address.</p>

    <?php if ($flashOk !== ''): ?><div class="ok"><?= htmlspecialchars($flashOk) ?></div><?php endif; ?>
    <?php if ($flashErr !== ''): ?><div class="err"><?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="box">
        <div><strong>PHPMailer:</strong> ready</div>
        <div><strong>SMTP password stored:</strong> <?= $passSet ? 'yes' : 'no' ?></div>
        <div><strong>Superadmin:</strong> <?= htmlspecialchars((string) ($super['username'] ?? '—')) ?>
            → <code><?= htmlspecialchars($superEmail !== '' ? $superEmail : '(none)') ?></code></div>
        <div><strong>From / SMTP user:</strong> <code><?= htmlspecialchars($from) ?></code></div>
    </div>

    <form method="post" autocomplete="off">
        <label for="smtp_username">Gmail used for sending (SMTP username)</label>
        <input id="smtp_username" name="smtp_username" type="email" required
               value="<?= htmlspecialchars($smtpUser) ?>" placeholder="you@gmail.com">

        <label for="smtp_password">Gmail App Password</label>
        <input id="smtp_password" name="smtp_password" type="password" required
               placeholder="<?= $passSet ? '•••••••• (enter again to replace)' : 'xxxx xxxx xxxx xxxx' ?>"
               autocomplete="new-password">

        <label for="superadmin_email">Superadmin registered email (receives reset links)</label>
        <input id="superadmin_email" name="superadmin_email" type="email"
               value="<?= htmlspecialchars($superEmail) ?>"
               placeholder="same Gmail you can open">

        <label for="test_to">Send test email to</label>
        <input id="test_to" name="test_to" type="email"
               value="<?= htmlspecialchars($adminEmail !== '' ? $adminEmail : $superEmail) ?>">

        <div class="row">
            <button class="secondary" type="submit" name="action" value="save">Save only</button>
            <button class="primary" type="submit" name="action" value="save_test">Save + send test</button>
        </div>
    </form>

    <p class="muted" style="margin-top:1.5rem;">
        After a successful test, open
        <a href="<?= htmlspecialchars(BASE_URL) ?>/login/forgot-password.php">Forgot password</a>
        and enter the <em>registered</em> superadmin email.
    </p>
</body>
</html>
