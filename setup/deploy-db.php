<?php
/**
 * Web database migration for hosts without CLI (e.g. InfinityFree).
 *
 * 1. Set SMS2_DEPLOY_TOKEN in config/local.php
 * 2. Open /setup/deploy-db.php?token=YOUR_TOKEN
 * 3. Remove SMS2_DEPLOY_TOKEN after success
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$expectedToken = defined('SMS2_DEPLOY_TOKEN') ? (string) SMS2_DEPLOY_TOKEN : '';
$providedToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Deploy DB</title></head><body>';
    echo '<p>Forbidden. Set <code>SMS2_DEPLOY_TOKEN</code> in <code>config/local.php</code>, then open this page with <code>?token=...</code></p>';
    echo '</body></html>';
    exit;
}

$force = isset($_GET['force']) || isset($_POST['force']);
$messages = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['run'])) {
    try {
        require_once dirname(__DIR__) . '/database/migrate-lib.php';
        $messages = sms2RunMigrations(['fresh' => false, 'force' => $force]);
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMS 2 — Deploy Database</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1.25rem; background: #f8fafc; color: #0f172a; }
        h1 { font-size: 1.35rem; color: #1e3a8a; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.1rem; margin: 1rem 0; }
        pre { white-space: pre-wrap; word-break: break-word; background: #0f172a; color: #e2e8f0; padding: 1rem; border-radius: 8px; font-size: .85rem; }
        .err { background: #fee2e2; color: #991b1b; padding: .75rem 1rem; border-radius: 8px; }
        .ok { background: #d1fae5; color: #065f46; padding: .75rem 1rem; border-radius: 8px; }
        button { background: #1d4ed8; color: #fff; border: 0; border-radius: 8px; padding: .6rem 1rem; font-size: .95rem; cursor: pointer; }
        button:hover { background: #1e40af; }
        a { color: #1d4ed8; }
        label { display: inline-flex; align-items: center; gap: .35rem; margin: .75rem 0; }
    </style>
</head>
<body>
    <h1>SMS 2 — Deploy Database</h1>
    <p>For InfinityFree and other hosts without SSH/CLI. Applies <code>sms2_db.sql</code> and <code>crad_db.sql</code>.</p>

    <?php if ($error !== ''): ?>
        <div class="err"><strong>Migration failed:</strong> <?= htmlspecialchars($error) ?></div>
    <?php elseif ($messages): ?>
        <div class="ok">Migration finished. Next: <a href="<?= htmlspecialchars(BASE_URL) ?>/setup/">create Super Admin</a></div>
        <pre><?= htmlspecialchars(implode("\n", $messages)) ?></pre>
    <?php endif; ?>

    <div class="card">
        <form method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($providedToken) ?>">
            <label><input type="checkbox" name="force" value="1"> Force re-apply (only if you know tables are incomplete)</label><br>
            <button type="submit">Run database migration</button>
        </form>
    </div>

    <p><small>After success, remove <code>SMS2_DEPLOY_TOKEN</code> from <code>config/local.php</code>.</small></p>
</body>
</html>
