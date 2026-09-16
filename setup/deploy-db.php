<?php
/**
 * Web import of database/sms2_db.sql into the attached HostForge database.
 *
 * 1. Set SMS2_DEPLOY_TOKEN in Environment Variables
 * 2. Open /setup/deploy-db.php?token=YOUR_TOKEN
 * 3. Import sms2_db.sql into DB_DATABASE (e.g. hf_db_xxxx)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

$expectedToken = defined('SMS2_DEPLOY_TOKEN') ? (string) SMS2_DEPLOY_TOKEN : (string) sms2_env('SMS2_DEPLOY_TOKEN', '');
$providedToken = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Deploy DB</title></head><body>';
    echo '<p>Forbidden. Set <code>SMS2_DEPLOY_TOKEN</code> in Environment Variables, then open this page with <code>?token=...</code></p>';
    echo '</body></html>';
    exit;
}

require_once ROOT_PATH . '/config/database.php';

$force = isset($_GET['force']) || isset($_POST['force']);
$skipCrad = !isset($_POST['include_crad']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['run'])) {
    $skipCrad = true;
}
$messages = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['run'])) {
    try {
        require_once dirname(__DIR__) . '/database/migrate-lib.php';
        $messages = sms2RunMigrations([
            'fresh' => false,
            'force' => $force,
            'skip_crad' => $skipCrad,
        ]);
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
    <title>SMS 2 — Import login database</title>
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
        label { display: flex; align-items: flex-start; gap: .45rem; margin: .65rem 0; }
        code { font-size: .9em; }
    </style>
</head>
<body>
    <h1>SMS 2 — Import login database</h1>
    <p>Loads <code>database/sms2_db.sql</code> into the database this app is already wired to. That is HostForge <code>DB_DATABASE</code>, not a database named <code>sms2_db</code> unless they are the same.</p>

    <div class="card">
        <p><strong>Import target (login / users)</strong></p>
        <p>
            <code><?= htmlspecialchars(DB_HOST . ':' . DB_PORT . '/' . DB_NAME) ?></code>
            as <code><?= htmlspecialchars(DB_USER) ?></code>
        </p>
        <p class="small">If this is not the database you open in HostForge → Databases, the dump will not fix login.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="err"><strong>Import failed:</strong> <?= htmlspecialchars($error) ?></div>
    <?php elseif ($messages): ?>
        <div class="ok">Import finished. Next: <a href="<?= htmlspecialchars(BASE_URL) ?>/setup/health.php?token=<?= htmlspecialchars($providedToken) ?>">check health</a> then <a href="<?= htmlspecialchars(BASE_URL) ?>/login/login.php">sign in</a>.</div>
        <pre><?= htmlspecialchars(implode("\n", $messages)) ?></pre>
    <?php endif; ?>

    <div class="card">
        <form method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($providedToken) ?>">
            <label>
                <input type="checkbox" name="force" value="1" checked>
                <span>Replace existing SMS2 tables in this database (needed to re-upload <code>sms2_db.sql</code>)</span>
            </label>
            <label>
                <input type="checkbox" name="include_crad" value="1">
                <span>Also import CRAD (<code>crad_db.sql</code>) — leave unchecked until CRAD_DB_HOST resolves</span>
            </label>
            <button type="submit">Import sms2_db.sql now</button>
        </form>
    </div>

    <p><small>After login works, you can also import the same file from HostForge → Databases → <code><?= htmlspecialchars(DB_NAME) ?></code> → Import.</small></p>
</body>
</html>
