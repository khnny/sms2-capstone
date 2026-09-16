<?php
/**
 * CRAD Module — Database Installer (CLI only)
 *   php modules/crad/database/install.php
 *
 * Creates the crad_db database and all required tables.
 */

declare(strict_types=1);

// CLI only — never expose schema install over HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. Run from CLI:\n  php modules/crad/database/install.php\n";
    exit(1);
}

require_once __DIR__ . '/../config/config.php';

// ── Bootstrap ────────────────────────────────────────────────────────────────
$isCli = true;

function out(string $msg, bool $ok = true): void
{
    echo ($ok ? '[OK] ' : '[!!] ') . $msg . PHP_EOL;
}

// ── Settings ─────────────────────────────────────────────────────────────────
$host    = CRAD_DB_HOST;
$user    = CRAD_DB_USER;
$pass    = CRAD_DB_PASS;
$dbName  = CRAD_DB_NAME;
$charset = CRAD_DB_CHARSET;
$sqlFile = __DIR__ . '/crad_db.sql';

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
    exit(1);
}

// ── Step 3: Select database ───────────────────────────────────────────────────
try {
    $pdo->exec('USE `' . $dbName . '`');
    out('Switched to database `' . $dbName . '`');
} catch (PDOException $e) {
    out('Cannot select database: ' . $e->getMessage(), false);
    exit(1);
}

// ── Step 4: Run SQL schema ────────────────────────────────────────────────────
if (!file_exists($sqlFile)) {
    out('Schema file not found: ' . $sqlFile, false);
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
    'crad_research_proposals',
    'crad_proposal_members',
    'crad_proposal_documents',
    'crad_proposal_status_logs',
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

echo PHP_EOL . ($errors === 0 ? '[DONE] Installation complete.' : '[DONE] Finished with errors.') . PHP_EOL;
