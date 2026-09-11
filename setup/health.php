<?php
/**
 * HostForge / cloud deploy health check.
 *
 * 1. Set SMS2_DEPLOY_TOKEN in Environment Variables (or config/local.php on local)
 * 2. Open /setup/health.php?token=YOUR_TOKEN
 * 3. Remove SMS2_DEPLOY_TOKEN after verifying login connectivity
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$expectedToken = defined('SMS2_DEPLOY_TOKEN') ? (string) SMS2_DEPLOY_TOKEN : (string) sms2_env('SMS2_DEPLOY_TOKEN', '');
$providedToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    $reason = $expectedToken === ''
        ? 'SMS2_DEPLOY_TOKEN is not visible to PHP yet. Save it in Environment Variables, redeploy the app, then retry.'
        : 'The token in the URL does not match SMS2_DEPLOY_TOKEN. Copy the value exactly from Environment Variables.';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Health Check</title></head><body>';
    echo '<p>Forbidden. ' . htmlspecialchars($reason) . '</p>';
    echo '<p>Use: <code>/setup/health.php?token=YOUR_TOKEN</code></p>';
    echo '</body></html>';
    exit;
}

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/captcha.php';

$hasCloudDbEnv = sms2_has_cloud_db_env();

$checks = [];

$envSources = [];
foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DATABASE_URL'] as $envKey) {
    if (sms2_env_raw($envKey) !== false) {
        $envSources[] = $envKey;
    }
}

$checks[] = [
    'label' => 'Cloud DB env detected (local.php skipped)',
    'ok' => $hasCloudDbEnv,
    'detail' => $hasCloudDbEnv
        ? 'Yes — detected: ' . implode(', ', $envSources)
        : 'No — using config/local.php or defaults',
];

$checks[] = [
    'label' => 'HTTPS / reverse proxy detection',
    'ok' => sms2_request_is_https(),
    'detail' => 'sms2_request_is_https() = ' . (sms2_request_is_https() ? 'true' : 'false')
        . '; X-Forwarded-Proto=' . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '(none)'),
];

$checks[] = [
    'label' => 'PHP session active',
    'ok' => session_status() === PHP_SESSION_ACTIVE,
    'detail' => 'Session ID present: ' . (session_id() !== '' ? 'yes' : 'no'),
];

$checks[] = [
    'label' => 'SMS2 DB config resolved',
    'ok' => DB_HOST !== '' && DB_NAME !== '',
    'detail' => 'host=' . DB_HOST . '; port=' . DB_PORT . '; database=' . DB_NAME . '; user=' . DB_USER,
];

$checks[] = [
    'label' => 'CRAD DB config resolved',
    'ok' => CRAD_DB_HOST !== '' && CRAD_DB_NAME !== '',
    'detail' => 'host=' . CRAD_DB_HOST . '; port=' . CRAD_DB_PORT . '; database=' . CRAD_DB_NAME . '; user=' . CRAD_DB_USER,
];

$pdo = null;
$dbError = '';
try {
    $pdo = getDatabaseConnection();
    $checks[] = [
        'label' => 'SMS2 database connection',
        'ok' => true,
        'detail' => 'Connected to ' . DB_HOST . '/' . DB_NAME,
    ];
} catch (Throwable $e) {
    $dbError = $e->getMessage();
    $checks[] = [
        'label' => 'SMS2 database connection',
        'ok' => false,
        'detail' => $dbError,
    ];
}

$cradError = '';
try {
    $cradPdo = getCradDatabaseConnection();
    $checks[] = [
        'label' => 'CRAD database connection',
        'ok' => true,
        'detail' => 'Connected to ' . CRAD_DB_HOST . '/' . CRAD_DB_NAME,
    ];
    unset($cradPdo);
} catch (Throwable $e) {
    $cradError = $e->getMessage();
    $checks[] = [
        'label' => 'CRAD database connection',
        'ok' => false,
        'detail' => $cradError,
    ];
}

if ($pdo instanceof PDO) {
    try {
        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $checks[] = [
            'label' => 'users table row count',
            'ok' => $userCount > 0,
            'detail' => (string) $userCount . ' user(s)',
        ];
    } catch (Throwable $e) {
        $checks[] = [
            'label' => 'users table row count',
            'ok' => false,
            'detail' => $e->getMessage(),
        ];
    }

    try {
        $joinCount = (int) $pdo->query(
            'SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.role_key = u.role_key'
        )->fetchColumn();
        $checks[] = [
            'label' => 'users INNER JOIN roles (login lookup)',
            'ok' => $joinCount > 0,
            'detail' => (string) $joinCount . ' login-capable user(s)',
        ];
    } catch (Throwable $e) {
        $checks[] = [
            'label' => 'users INNER JOIN roles (login lookup)',
            'ok' => false,
            'detail' => $e->getMessage(),
        ];
    }

    try {
        $throttleCount = (int) $pdo->query('SELECT COUNT(*) FROM login_throttles')->fetchColumn();
        $checks[] = [
            'label' => 'login_throttles (failed-attempt locks)',
            'ok' => $throttleCount === 0,
            'detail' => $throttleCount === 0
                ? 'No active throttle rows'
                : $throttleCount . ' row(s) — clear with DELETE FROM login_throttles',
        ];
    } catch (Throwable $e) {
        $checks[] = [
            'label' => 'login_throttles',
            'ok' => true,
            'detail' => 'Table not present or unreadable: ' . $e->getMessage(),
        ];
    }
}

$checks[] = [
    'label' => 'Login CAPTCHA provider',
    'ok' => true,
    'detail' => smsCaptchaEnabled()
        ? smsCaptchaProvider() . (smsCaptchaProvider() === 'turnstile' ? ' (Cloudflare)' : ' (local one-click)')
        : 'disabled',
];

$allOk = true;
foreach ($checks as $check) {
    if (empty($check['ok'])) {
        $allOk = false;
        break;
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMS 2 — Deploy Health</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1.25rem; background: #f8fafc; color: #0f172a; }
        h1 { font-size: 1.35rem; color: #1e3a8a; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
        th, td { text-align: left; padding: .65rem .85rem; border-bottom: 1px solid #e2e8f0; font-size: .9rem; vertical-align: top; }
        th { background: #f1f5f9; font-weight: 600; }
        tr:last-child td { border-bottom: 0; }
        .ok { color: #065f46; font-weight: 600; }
        .fail { color: #991b1b; font-weight: 600; }
        .banner { padding: .75rem 1rem; border-radius: 8px; margin: 1rem 0; }
        .banner.ok { background: #d1fae5; color: #065f46; }
        .banner.fail { background: #fee2e2; color: #991b1b; }
        a { color: #1d4ed8; }
        code { font-size: .85em; }
    </style>
</head>
<body>
    <h1>SMS 2 — Deploy Health</h1>
    <p>Verify database connectivity before signing in on HostForge or other cloud hosts.</p>

    <div class="banner <?= $allOk ? 'ok' : 'fail' ?>">
        <?= $allOk ? 'All checks passed. Try logging in at the login page.' : 'Some checks failed. Fix the items marked FAIL before logging in.' ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Check</th>
                <th>Status</th>
                <th>Detail</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($checks as $check): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $check['label']) ?></td>
                    <td class="<?= !empty($check['ok']) ? 'ok' : 'fail' ?>"><?= !empty($check['ok']) ? 'OK' : 'FAIL' ?></td>
                    <td><?= htmlspecialchars((string) $check['detail']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top:1.25rem">
        <a href="<?= htmlspecialchars(BASE_URL) ?>/login/login.php">Go to login</a>
        &nbsp;·&nbsp;
        <a href="<?= htmlspecialchars(BASE_URL) ?>/setup/deploy-db.php?token=<?= htmlspecialchars($providedToken) ?>">Run DB migration</a>
    </p>
    <p><small>Remove <code>SMS2_DEPLOY_TOKEN</code> from Environment Variables after you finish deployment checks.</small></p>
</body>
</html>
