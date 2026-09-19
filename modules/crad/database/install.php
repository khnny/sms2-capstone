<?php
/**
 * CRAD Module — Database Installer
 * Run this ONCE via browser: /sms2_system/modules/crad/database/install.php
 * or via CLI:  php modules/crad/database/install.php
 *
 * Creates the crad_db database and all required tables.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

// ── Bootstrap ────────────────────────────────────────────────────────────────
$isCli = PHP_SAPI === 'cli';

function out(string $msg, bool $ok = true): void
{
    global $isCli;
    if ($isCli) {
        echo ($ok ? '[OK] ' : '[!!] ') . $msg . PHP_EOL;
    } else {
        $cls = $ok ? 'ok' : 'err';
        echo '<p class="' . $cls . '"><b>' . ($ok ? '✔' : '✘') . '</b> ' . htmlspecialchars($msg) . '</p>';
    }
}

// ── Settings ─────────────────────────────────────────────────────────────────
$host    = CRAD_DB_HOST;
$user    = CRAD_DB_USER;
$pass    = CRAD_DB_PASS;
$dbName  = CRAD_DB_NAME;
$charset = CRAD_DB_CHARSET;
$sqlFile = __DIR__ . '/crad_db.sql';

if (!$isCli) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
    <title>CRAD DB Installer</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 680px; margin: 3rem auto; padding: 0 1.5rem; background:#f8fafc; color:#0f172a; }
        h1   { font-size: 1.4rem; margin-bottom: 1.5rem; color: #1e3a8a; }
        p    { padding: .45rem .8rem; border-radius: 8px; margin: .35rem 0; font-size: .92rem; }
        .ok  { background: #d1fae5; color: #065f46; }
        .err { background: #fee2e2; color: #991b1b; }
        .fin { background: #dbeafe; color: #1e40af; font-weight: 700; margin-top: 1.2rem; }
    </style></head><body>
    <h1>🔧 CRAD Module — Database Installer</h1>';
}

// ── Step 1: Connect without selecting a database ─────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';charset=' . $charset,
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    out('Connected to MySQL on ' . $host);
} catch (PDOException $e) {
    out('Cannot connect to MySQL: ' . $e->getMessage(), false);
    if (!$isCli) { echo '</body></html>'; }
    exit(1);
}

// ── Step 2: Create database ───────────────────────────────────────────────────
try {
    $pdo->exec(
        'CREATE DATABASE IF NOT EXISTS `' . $dbName . '`
         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    out('Database `' . $dbName . '` ready (created or already existed)');
} catch (PDOException $e) {
    out('Failed to create database: ' . $e->getMessage(), false);
    if (!$isCli) { echo '</body></html>'; }
    exit(1);
}

// ── Step 3: Select database ───────────────────────────────────────────────────
try {
    $pdo->exec('USE `' . $dbName . '`');
    out('Switched to database `' . $dbName . '`');
} catch (PDOException $e) {
    out('Cannot select database: ' . $e->getMessage(), false);
    if (!$isCli) { echo '</body></html>'; }
    exit(1);
}

// ── Step 4: Run SQL schema ────────────────────────────────────────────────────
if (!file_exists($sqlFile)) {
    out('Schema file not found: ' . $sqlFile, false);
    if (!$isCli) { echo '</body></html>'; }
    exit(1);
}

$sql = file_get_contents($sqlFile);

// Split on semicolons to run statement-by-statement
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    fn($s) => $s !== '' && !preg_match('/^--/', $s) && !preg_match('/^SET\s/i', $s)
);

$errors = 0;
foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
    } catch (PDOException $e) {
        out('SQL error: ' . $e->getMessage() . ' — [' . mb_substr($stmt, 0, 80) . '…]', false);
        $errors++;
    }
}

if ($errors === 0) {
    out('All schema tables created successfully');
} else {
    out($errors . ' statement(s) failed — check errors above', false);
}

// ── Step 5: Verify tables exist ───────────────────────────────────────────────
$expected = [
    'research_proposals',
    'proposal_members',
    'proposal_documents',
    'proposal_status_logs',
];

$found = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($expected as $table) {
    if (in_array($table, $found, true)) {
        out('Table `' . $table . '` verified');
    } else {
        out('Table `' . $table . '` MISSING', false);
        $errors++;
    }
}

// ── Done ──────────────────────────────────────────────────────────────────────
try {
    $consistency = cradEnsureTitleApprovalAdviserAssignmentConsistency($pdo, true);
    out($consistency['message'], !empty($consistency['ok']));
    if (empty($consistency['ok'])) {
        $errors++;
    }
} catch (Throwable $e) {
    out('Title approval adviser assignment consistency check failed: ' . $e->getMessage(), false);
    $errors++;
}

if (!$isCli) {
    $msg = $errors === 0
        ? 'Installation complete. You can now use the CRAD module.'
        : 'Installation finished with ' . $errors . ' error(s). Review messages above.';
    echo '<p class="fin">' . htmlspecialchars($msg) . '</p>';
    echo '</body></html>';
} else {
    echo PHP_EOL . ($errors === 0 ? '[DONE] Installation complete.' : '[DONE] Finished with errors.') . PHP_EOL;
}
