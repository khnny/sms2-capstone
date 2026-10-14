<?php
/**
 * One HostForge repair after deploy.
 *
 * Applies the additive patches that used to be separate SQL one-liners:
 *   database/patches/fix_users_autoincrement.sql
 *   database/patches/ensure_sms2_users_account_columns.sql
 *   database/patches/fix_user_passkeys_autoincrement.sql
 *   database/patches/fix_activity_logs_autoincrement.sql
 *   database/patches/research_group_flow_schema.sql
 *
 * Does not drop tables, delete rows, or run migrate --fresh.
 * Duplicate column / table / key errors are SKIP. Anything else is FAIL.
 *
 *   php database/tools/hostforge_repair.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this from the shell: php database/tools/hostforge_repair.php\n");
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . '/config/database.php';
require_once $root . '/modules/crad/config/config.php';
require_once $root . '/database/migrate-lib.php';

$failures = 0;

function hfRepairLine(string $status, string $message): void
{
    echo str_pad($status, 4, ' ', STR_PAD_RIGHT) . ' ' . $message . PHP_EOL;
}

function hfRepairFail(string $message): void
{
    global $failures;
    $failures++;
    hfRepairLine('FAIL', $message);
}

function hfRepairShortError(Throwable $e): string
{
    $message = preg_replace('/\s+/', ' ', trim($e->getMessage())) ?? trim($e->getMessage());
    if (strlen($message) > 220) {
        $message = substr($message, 0, 217) . '...';
    }

    return $message;
}

function hfRepairErrno(Throwable $e): int
{
    if (!$e instanceof PDOException) {
        return 0;
    }
    $info = $e->errorInfo ?? null;
    if (!is_array($info) || !isset($info[1]) || !is_numeric($info[1])) {
        return 0;
    }

    return (int) $info[1];
}

function hfRepairIsDuplicate(Throwable $e): bool
{
    $errno = hfRepairErrno($e);
    if (in_array($errno, [1050, 1060, 1061, 1068, 1826], true)) {
        return true;
    }
    $message = strtolower($e->getMessage());

    return str_contains($message, 'duplicate column')
        || str_contains($message, 'duplicate key name')
        || str_contains($message, 'multiple primary key')
        || str_contains($message, 'already exists');
}

function hfRepairForbidden(string $sql): ?string
{
    if (preg_match('/^\s*(DROP|TRUNCATE|DELETE|UPDATE|INSERT|REPLACE)\b/i', ltrim($sql)) === 1) {
        return 'refusing a statement that would change or delete rows';
    }

    return null;
}

function hfRepairStripComments(string $sql): string
{
    $kept = [];
    foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $sql)) as $line) {
        $trimmed = ltrim($line);
        if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
            continue;
        }
        $kept[] = $line;
    }

    return implode("\n", $kept);
}

function hfRepairExec(PDO $pdo, string $sql): void
{
    $statement = $pdo->query($sql);
    if (!$statement instanceof PDOStatement) {
        return;
    }
    try {
        $statement->fetchAll();
    } catch (Throwable) {
        // ALTER / SET / PREPARE have no row set.
    }
    $statement->closeCursor();
}

function hfRepairLabel(string $sql): string
{
    $one = preg_replace('/\s+/', ' ', trim($sql)) ?? trim($sql);
    if (strlen($one) > 120) {
        $one = substr($one, 0, 117) . '...';
    }

    return $one;
}

function hfRepairVisibleSql(string $sql): bool
{
    return preg_match('/^\s*(ALTER|CREATE|RENAME)\b/i', ltrim($sql)) === 1;
}

/**
 * @return list<string>
 */
function hfRepairStatements(string $file): array
{
    if (!is_readable($file)) {
        throw new RuntimeException('SQL file not readable: ' . $file);
    }
    $sql = hfRepairStripComments((string) file_get_contents($file));
    $statements = [];
    foreach (sms2MigrateSplitSql($sql) as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $statements[] = $statement;
        }
    }

    return $statements;
}

function hfRepairApplyFile(PDO $pdo, string $step, string $file): void
{
    try {
        $statements = hfRepairStatements($file);
    } catch (Throwable $e) {
        hfRepairFail($step . ' — ' . hfRepairShortError($e));
        return;
    }

    $visible = 0;
    $skipped = 0;
    $failed = false;
    foreach ($statements as $statement) {
        $blocked = hfRepairForbidden($statement);
        if ($blocked !== null) {
            hfRepairFail($step . ' — ' . $blocked);
            $failed = true;
            continue;
        }
        $show = hfRepairVisibleSql($statement);
        try {
            hfRepairExec($pdo, $statement);
            if ($show) {
                hfRepairLine('OK', hfRepairLabel($statement));
                $visible++;
            }
        } catch (Throwable $e) {
            if (hfRepairIsDuplicate($e)) {
                if ($show) {
                    hfRepairLine('SKIP', hfRepairLabel($statement) . ' — ' . hfRepairShortError($e));
                }
                $skipped++;
                continue;
            }
            hfRepairFail(($show ? hfRepairLabel($statement) : $step) . ' — ' . hfRepairShortError($e));
            $failed = true;
        }
    }

    if ($failed) {
        return;
    }
    if ($visible === 0) {
        hfRepairLine($skipped > 0 ? 'SKIP' : 'OK', $step . ($skipped > 0 ? ' — already applied' : ''));
    }
}

function hfRepairTableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
    );
    $stmt->execute([$table]);
    $exists = (bool) $stmt->fetchColumn();
    $stmt->closeCursor();

    return $exists;
}

/**
 * @return array<string, mixed>|null
 */
function hfRepairColumn(PDO $pdo, string $table, string $column): ?array
{
    $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . "` LIKE " . $pdo->quote($column));
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
    if ($stmt) {
        $stmt->closeCursor();
    }

    return is_array($row) ? $row : null;
}

/**
 * AUTO_INCREMENT is rejected with 1075 until id is a key.
 * Adds PRIMARY KEY (id) only when the table has none.
 */
function hfRepairEnsureIdKey(PDO $pdo, string $table): bool
{
    if (preg_match('/^[a-z0-9_]+$/', $table) !== 1) {
        hfRepairFail($table . ' — refused unexpected table name');
        return false;
    }
    if (!hfRepairTableExists($pdo, $table)) {
        hfRepairFail($table . ' is missing — run php database/migrate.php first');
        return false;
    }

    $column = hfRepairColumn($pdo, $table, 'id');
    if ($column === null) {
        hfRepairFail($table . '.id is missing');
        return false;
    }

    try {
        if (strtoupper((string) ($column['Null'] ?? 'NO')) === 'YES') {
            $type = (string) ($column['Type'] ?? 'int(10) unsigned');
            if (preg_match('/^[a-z0-9(), ]+$/i', $type) !== 1) {
                $type = 'int(10) unsigned';
            }
            hfRepairExec($pdo, 'ALTER TABLE `' . $table . '` MODIFY `id` ' . $type . ' NOT NULL');
            hfRepairLine('OK', $table . '.id set NOT NULL');
        }

        $keyStmt = $pdo->query('SHOW KEYS FROM `' . $table . '`');
        $keys = $keyStmt ? $keyStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if ($keyStmt) {
            $keyStmt->closeCursor();
        }
        $hasPrimary = false;
        $idIndexed = false;
        foreach ($keys as $key) {
            if (!is_array($key)) {
                continue;
            }
            if ((string) ($key['Key_name'] ?? '') === 'PRIMARY') {
                $hasPrimary = true;
            }
            if (strtolower((string) ($key['Column_name'] ?? '')) === 'id') {
                $idIndexed = true;
            }
        }
        if (!$hasPrimary) {
            hfRepairExec($pdo, 'ALTER TABLE `' . $table . '` ADD PRIMARY KEY (`id`)');
            hfRepairLine('OK', $table . ' ADD PRIMARY KEY (id)');
        } elseif (!$idIndexed) {
            $index = 'idx_' . $table . '_id';
            try {
                hfRepairExec($pdo, 'ALTER TABLE `' . $table . '` ADD KEY `' . $index . '` (`id`)');
                hfRepairLine('OK', $table . ' ADD KEY ' . $index . ' (id)');
            } catch (Throwable $e) {
                if (!hfRepairIsDuplicate($e)) {
                    throw $e;
                }
                hfRepairLine('SKIP', $table . ' id key — ' . hfRepairShortError($e));
            }
        } else {
            hfRepairLine('SKIP', $table . '.id is already a key');
        }
    } catch (Throwable $e) {
        hfRepairFail($table . ' id key — ' . hfRepairShortError($e));
        return false;
    }

    return true;
}

function hfRepairPasskeyIndexes(PDO $pdo): void
{
    $table = 'sms2_user_passkeys';
    if (!hfRepairTableExists($pdo, $table)) {
        hfRepairFail($table . ' is missing — run php database/migrate.php first');
        return;
    }
    try {
        $keyStmt = $pdo->query('SHOW KEYS FROM `' . $table . '`');
        $keys = $keyStmt ? $keyStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if ($keyStmt) {
            $keyStmt->closeCursor();
        }
        $names = [];
        foreach ($keys as $key) {
            if (is_array($key)) {
                $names[strtolower((string) ($key['Key_name'] ?? ''))] = true;
            }
        }
        if (empty($names['uq_passkey_cred'])) {
            hfRepairExec($pdo, 'ALTER TABLE `' . $table . '` ADD UNIQUE KEY `uq_passkey_cred` (`credential_id`(255))');
            hfRepairLine('OK', $table . ' ADD UNIQUE KEY uq_passkey_cred');
        } else {
            hfRepairLine('SKIP', $table . ' unique key uq_passkey_cred already exists');
        }
        if (empty($names['idx_passkey_user'])) {
            hfRepairExec($pdo, 'ALTER TABLE `' . $table . '` ADD KEY `idx_passkey_user` (`user_id`)');
            hfRepairLine('OK', $table . ' ADD KEY idx_passkey_user');
        } else {
            hfRepairLine('SKIP', $table . ' key idx_passkey_user already exists');
        }
    } catch (Throwable $e) {
        if (hfRepairIsDuplicate($e)) {
            hfRepairLine('SKIP', $table . ' indexes — ' . hfRepairShortError($e));
            return;
        }
        hfRepairFail($table . ' indexes — ' . hfRepairShortError($e));
    }
}

function hfRepairSmoke(PDO $pdo): void
{
    echo PHP_EOL . '== smoke ==' . PHP_EOL;
    $same = defined('DB_NAME') && defined('CRAD_DB_NAME') && DB_NAME === CRAD_DB_NAME;
    hfRepairLine($same ? 'OK' : 'FAIL', 'DB_NAME=' . DB_NAME . ' CRAD_DB_NAME=' . CRAD_DB_NAME . ' Same DB: ' . ($same ? 'yes' : 'no'));
    if (!$same) {
        global $failures;
        $failures++;
    }

    foreach (['sms2_users', 'crad_title_approvals', 'crad_research_groups'] as $table) {
        try {
            $count = $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
            hfRepairLine('OK', $table . ' count=' . $count);
        } catch (Throwable $e) {
            hfRepairFail($table . ' — ' . hfRepairShortError($e));
        }
    }

    try {
        $stmt = $pdo->query("SELECT id, username, role_key FROM `sms2_users` WHERE status = 'active' LIMIT 3");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if ($stmt) {
            $stmt->closeCursor();
        }
        foreach ($rows as $row) {
            hfRepairLine('OK', 'user: ' . $row['username'] . ' (' . $row['role_key'] . ')');
        }
    } catch (Throwable $e) {
        hfRepairFail('active users — ' . hfRepairShortError($e));
    }
}

try {
    $pdo = getDatabaseConnection();
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
} catch (Throwable $e) {
    fwrite(STDERR, "FAIL database connection — " . hfRepairShortError($e) . PHP_EOL);
    exit(1);
}

$patchDir = dirname(__DIR__) . '/patches';
echo 'HostForge repair  DB_NAME=' . DB_NAME . PHP_EOL;
echo 'Additive only. No drops, no row deletes.' . PHP_EOL . PHP_EOL;

echo '== sms2_users id ==' . PHP_EOL;
if (hfRepairEnsureIdKey($pdo, 'sms2_users')) {
    hfRepairApplyFile($pdo, 'sms2_users.id PRIMARY KEY + AUTO_INCREMENT', $patchDir . '/fix_users_autoincrement.sql');
    hfRepairApplyFile($pdo, 'sms2_users account columns', $patchDir . '/ensure_sms2_users_account_columns.sql');
}

echo PHP_EOL . '== sms2_user_passkeys ==' . PHP_EOL;
if (hfRepairEnsureIdKey($pdo, 'sms2_user_passkeys')) {
    hfRepairApplyFile($pdo, 'sms2_user_passkeys id AUTO_INCREMENT', $patchDir . '/fix_user_passkeys_autoincrement.sql');
    hfRepairPasskeyIndexes($pdo);
}

echo PHP_EOL . '== sms2_activity_logs ==' . PHP_EOL;
if (hfRepairEnsureIdKey($pdo, 'sms2_activity_logs')) {
    hfRepairApplyFile($pdo, 'sms2_activity_logs id AUTO_INCREMENT', $patchDir . '/fix_activity_logs_autoincrement.sql');
}

echo PHP_EOL . '== research group flow ==' . PHP_EOL;
hfRepairApplyFile($pdo, 'research_group_flow_schema', $patchDir . '/research_group_flow_schema.sql');

hfRepairSmoke($pdo);

echo PHP_EOL . ($failures === 0 ? "Repair OK\n" : "Repair FAILED ($failures)\n");
exit($failures === 0 ? 0 : 1);
