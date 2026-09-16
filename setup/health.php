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
$actionFlash = '';
$actionOk = true;
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

$postedAction = trim((string) ($_POST['health_action'] ?? ''));
if ($pdo instanceof PDO && $_SERVER['REQUEST_METHOD'] === 'POST' && $postedAction !== '') {
    try {
        if ($postedAction === 'clear_locks') {
            $pdo->exec('DELETE FROM sms_login_throttles');
            $pdo->exec(
                "UPDATE sms_users
                 SET failed_login_attempts = 0,
                     locked_until = NULL,
                     status = CASE WHEN status = 'locked' THEN 'active' ELSE status END
                 WHERE failed_login_attempts > 0
                    OR locked_until IS NOT NULL
                    OR status = 'locked'"
            );
            $actionFlash = 'Login locks cleared. Try signing in again.';
        } elseif ($postedAction === 'reset_official') {
            require_once ROOT_PATH . '/database/official_accounts.php';
            $pdo->exec('DELETE FROM sms_login_throttles');
            $result = smsApplyOfficialAccountCredentials($pdo);
            $actionFlash = 'Official account passwords reset ('
                . (int) $result['updated'] . ' updated, '
                . (int) $result['created'] . ' created) and login locks cleared.';
        } else {
            $actionOk = false;
            $actionFlash = 'Unknown health action.';
        }
    } catch (Throwable $e) {
        $actionOk = false;
        $actionFlash = 'Action failed: ' . $e->getMessage();
    }
}

$cradError = '';
$cradSameDb = strcasecmp((string) CRAD_DB_NAME, (string) DB_NAME) === 0;
try {
    $cradPdo = getCradDatabaseConnection();
    $checks[] = [
        'label' => 'CRAD database connection',
        'ok' => true,
        'detail' => $cradSameDb
            ? 'Same database as SMS2 (' . CRAD_DB_NAME . ') — unified sms_*/crad_* mode'
            : 'Connected to ' . CRAD_DB_HOST . '/' . CRAD_DB_NAME,
    ];
    if ($cradSameDb && $pdo instanceof PDO) {
        try {
            $cradTableOk = (bool) $pdo->query("SHOW TABLES LIKE 'crad_title_approvals'")->fetchColumn();
            $checks[] = [
                'label' => 'crad_title_approvals present',
                'ok' => $cradTableOk,
                'detail' => $cradTableOk
                    ? 'Found in unified database'
                    : 'Missing — re-import database/sms2_db.sql (unified dump)',
            ];
        } catch (Throwable $e) {
            $checks[] = [
                'label' => 'crad_title_approvals present',
                'ok' => false,
                'detail' => $e->getMessage(),
            ];
        }
    }
    unset($cradPdo);
} catch (Throwable $e) {
    $cradError = $e->getMessage();
    $prev = $e->getPrevious();
    if ($prev instanceof Throwable && $prev->getMessage() !== '') {
        $cradError .= ' | ' . $prev->getMessage();
    }
    if (!str_contains($cradError, 'SQLSTATE') && !str_contains($cradError, 'getaddrinfo')) {
        $cradError .= ' | trying ' . CRAD_DB_HOST . ':' . CRAD_DB_PORT . '/' . CRAD_DB_NAME
            . ' as ' . CRAD_DB_USER
            . ' (prefer CRAD_DB_NAME=' . DB_NAME . ' and import database/sms2_db.sql once)';
    }
    $checks[] = [
        'label' => 'CRAD database connection',
        'ok' => true,
        'blocking' => false,
        'detail' => 'WARN (does not block SMS2 login): ' . $cradError,
    ];
}

if ($pdo instanceof PDO) {
    try {
        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM sms_users')->fetchColumn();
        $checks[] = [
            'label' => 'sms_users table row count',
            'ok' => $userCount > 0,
            'detail' => (string) $userCount . ' user(s)',
        ];
    } catch (Throwable $e) {
        $checks[] = [
            'label' => 'sms_users table row count',
            'ok' => false,
            'detail' => $e->getMessage(),
        ];
    }

    try {
        $joinCount = (int) $pdo->query(
            'SELECT COUNT(*) FROM sms_users u INNER JOIN sms_roles r ON r.role_key = u.role_key'
        )->fetchColumn();
        $checks[] = [
            'label' => 'sms_users INNER JOIN sms_roles (login lookup)',
            'ok' => $joinCount > 0,
            'detail' => (string) $joinCount . ' login-capable user(s)',
        ];
    } catch (Throwable $e) {
        $checks[] = [
            'label' => 'sms_users INNER JOIN sms_roles (login lookup)',
            'ok' => false,
            'detail' => $e->getMessage(),
        ];
    }

    try {
        $throttleCount = (int) $pdo->query('SELECT COUNT(*) FROM sms_login_throttles')->fetchColumn();
        $lockedCount = 0;
        try {
            $lockedCount = (int) $pdo->query(
                'SELECT COUNT(*) FROM sms_login_throttles
                 WHERE locked_until IS NOT NULL AND locked_until > NOW()'
            )->fetchColumn();
        } catch (Throwable) {
            $lockedCount = $throttleCount;
        }
        $usersMissing = false;
        try {
            $pdo->query('SELECT 1 FROM sms_users LIMIT 1');
        } catch (Throwable) {
            $usersMissing = true;
        }
        $checks[] = [
            'label' => 'login_throttles (failed-attempt locks)',
            'ok' => $usersMissing || $lockedCount === 0,
            'detail' => $usersMissing
                ? 'Leftover lockout table from failed logins — import sms2_db.sql into this database'
                : ($lockedCount > 0
                    ? $lockedCount . ' active lock(s) — use Clear login locks below'
                    : ($throttleCount === 0
                        ? 'No throttle rows'
                        : $throttleCount . ' leftover failed attempt(s), not locked')),
        ];
    } catch (Throwable $e) {
        $checks[] = [
            'label' => 'sms_login_throttles',
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

// #region agent log
$checks[] = [
    'label' => 'CRAD vs SMS2 target (debug)',
    'ok' => true,
    'blocking' => false,
    'detail' => 'CRAD=' . CRAD_DB_HOST . '/' . CRAD_DB_NAME
        . ' | SMS2=' . DB_HOST . '/' . DB_NAME
        . ' | nameSame=' . (strcasecmp((string) CRAD_DB_NAME, (string) DB_NAME) === 0 ? '1' : '0')
        . ' | hostSame=' . (strcasecmp((string) CRAD_DB_HOST, (string) DB_HOST) === 0 ? '1' : '0'),
];
$agentDebugLogPath = ROOT_PATH . '/debug-4aceee.log';
$agentDebugLogTail = '';
if (is_readable($agentDebugLogPath)) {
    $rawLog = (string) file_get_contents($agentDebugLogPath);
    $lines = array_values(array_filter(preg_split("/\r\n|\n|\r/", $rawLog) ?: []));
    $agentDebugLogTail = implode("\n", array_slice($lines, -12));
}
// #endregion

$allOk = true;
foreach ($checks as $check) {
    $blocking = $check['blocking'] ?? true;
    if (empty($check['ok']) && $blocking !== false) {
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
        .actions { display: flex; flex-wrap: wrap; gap: .6rem; margin: 1rem 0; }
        button { background: #1d4ed8; color: #fff; border: 0; border-radius: 8px; padding: .55rem .9rem; font-size: .9rem; cursor: pointer; }
        button.secondary { background: #0f172a; }
        button:hover { opacity: .92; }
        a { color: #1d4ed8; }
        code { font-size: .85em; }
    </style>
</head>
<body>
    <h1>SMS 2 — Deploy Health</h1>
    <p>Verify database connectivity before signing in on HostForge or other cloud hosts.</p>

    <?php if ($actionFlash !== ''): ?>
        <div class="banner <?= $actionOk ? 'ok' : 'fail' ?>"><?= htmlspecialchars($actionFlash) ?></div>
    <?php endif; ?>

    <div class="banner <?= $allOk ? 'ok' : 'fail' ?>">
        <?= $allOk ? 'All blocking checks passed. Try logging in at the login page.' : 'Some checks failed. Fix the items marked FAIL before logging in.' ?>
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

    <?php if ($agentDebugLogTail !== ''): ?>
        <!-- #region agent log -->
        <h2 style="font-size:1rem;margin-top:1.25rem">CRAD debug log (tail)</h2>
        <pre style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:8px;font-size:.75rem;overflow:auto;white-space:pre-wrap;word-break:break-word"><?= htmlspecialchars($agentDebugLogTail) ?></pre>
        <!-- #endregion -->
    <?php endif; ?>

    <?php if ($pdo instanceof PDO): ?>
        <div class="actions">
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($providedToken) ?>">
                <input type="hidden" name="health_action" value="clear_locks">
                <button type="submit">Clear login locks</button>
            </form>
            <form method="post" onsubmit="return confirm('Reset official account passwords from database/official_accounts.php and clear locks?');">
                <input type="hidden" name="token" value="<?= htmlspecialchars($providedToken) ?>">
                <input type="hidden" name="health_action" value="reset_official">
                <button type="submit" class="secondary">Reset official passwords</button>
            </form>
        </div>
    <?php endif; ?>

    <p style="margin-top:1.25rem">
        <a href="<?= htmlspecialchars(BASE_URL) ?>/login/login.php">Go to login</a>
        &nbsp;·&nbsp;
        <a href="<?= htmlspecialchars(BASE_URL) ?>/setup/deploy-db.php?token=<?= htmlspecialchars($providedToken) ?>">Run DB migration</a>
    </p>
    <p><small>Remove <code>SMS2_DEPLOY_TOKEN</code> from Environment Variables after you finish deployment checks.</small></p>
</body>
</html>
