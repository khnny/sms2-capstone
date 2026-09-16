<?php
/**
 * Shared SMS 2 deployment migration logic (CLI + web deploy).
 */
declare(strict_types=1);

function sms2MigrateOut(string $message, ?callable $sink = null): void
{
    if ($sink) {
        $sink($message);
        return;
    }

    echo $message . PHP_EOL;
}

function sms2MigrateQuoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function sms2MigrateConnectServer(string $host, string $port, string $user, string $pass, string $charset): PDO
{
    return new PDO(
        'mysql:host=' . $host . ';port=' . $port . ';charset=' . $charset,
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function sms2MigrateSqlCreateTables(string $sqlFile): array
{
    if (!is_readable($sqlFile)) {
        return [];
    }

    preg_match_all('/CREATE\s+TABLE\s+`([^`]+)`/i', (string) file_get_contents($sqlFile), $matches);

    return array_values(array_unique($matches[1] ?? []));
}

function sms2MigrateExistingTargetTableCount(PDO $pdo, array $tables): int
{
    if (!$tables) {
        return 0;
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME IN (' . implode(',', array_fill(0, count($tables), '?')) . ')'
    );
    $stmt->execute($tables);

    return (int) $stmt->fetchColumn();
}

function sms2MigrateEnsureDatabase(PDO $pdo, string $database, ?callable $sink = null): void
{
    $quotedDatabase = sms2MigrateQuoteIdentifier($database);

    try {
        $pdo->exec(
            'CREATE DATABASE IF NOT EXISTS ' . $quotedDatabase .
            ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        return;
    } catch (PDOException $createError) {
        try {
            $pdo->exec('USE ' . $quotedDatabase);
            sms2MigrateOut('Create database skipped by host permissions; existing database is accessible.', $sink);
            return;
        } catch (PDOException) {
            throw $createError;
        }
    }
}

function sms2MigrateSplitSql(string $sql): array
{
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);
    $delimiter = ';';
    $statement = '';
    $statements = [];

    foreach (explode("\n", $sql) as $line) {
        $trimmed = trim($line);

        if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmed, $matches)) {
            $delimiter = $matches[1];
            continue;
        }

        if ($delimiter !== ';') {
            if (str_ends_with($trimmed, $delimiter)) {
                $statement .= substr($line, 0, strrpos($line, $delimiter)) . "\n";
                $candidate = trim($statement);
                if ($candidate !== '') {
                    $statements[] = $candidate;
                }
                $statement = '';
                continue;
            }

            $statement .= $line . "\n";
            continue;
        }

        $statement .= $line . "\n";
        if (str_ends_with($trimmed, ';')) {
            $candidate = trim(substr($statement, 0, strrpos($statement, ';')));
            if ($candidate !== '') {
                $statements[] = $candidate;
            }
            $statement = '';
        }
    }

    $candidate = trim($statement);
    if ($candidate !== '') {
        $statements[] = $candidate;
    }

    return $statements;
}

function sms2MigrateApplySqlFile(PDO $pdo, string $sqlFile): int
{
    if (!is_readable($sqlFile)) {
        throw new RuntimeException('SQL file not readable: ' . $sqlFile);
    }

    $sql = (string) file_get_contents($sqlFile);
    // Always import into the connected DB_DATABASE / CRAD_DB_NAME.
    $sql = preg_replace('/^\s*CREATE\s+DATABASE\b.*$/im', '', $sql) ?? $sql;
    $sql = preg_replace('/^\s*USE\s+`?[^;`]+`?\s*;?\s*$/im', '', $sql) ?? $sql;
    $statements = sms2MigrateSplitSql($sql);
    $applied = 0;

    foreach ($statements as $statement) {
        $pdo->exec($statement);
        $applied++;
    }

    return $applied;
}

/**
 * Tables the app may create before the dump is imported (failed-login lockout).
 *
 * @return list<string>
 */
function sms2MigrateLeftoverOnlyTables(): array
{
    return ['sms_login_throttles', 'sms_schema_migrations'];
}

/**
 * @return list<string>
 */
function sms2MigrateExistingTableNames(PDO $pdo): array
{
    $stmt = $pdo->query('SHOW TABLES');
    $names = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        if (!empty($row[0])) {
            $names[] = (string) $row[0];
        }
    }

    return $names;
}

/**
 * @param list<string> $tables
 */
function sms2MigrateDropTables(PDO $pdo, array $tables, ?callable $sink = null): void
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($tables as $table) {
        $table = trim($table);
        if ($table === '') {
            continue;
        }
        $pdo->exec('DROP TABLE IF EXISTS ' . sms2MigrateQuoteIdentifier($table));
        sms2MigrateOut('Dropped table if existed: ' . $table, $sink);
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function sms2MigrateEnsureTrackingTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `sms_schema_migrations` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration_key` varchar(120) NOT NULL,
            `source_file` varchar(255) NOT NULL,
            `source_sha256` char(64) NOT NULL,
            `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_migration_key` (`migration_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function sms2MigrateWasRecorded(PDO $pdo, string $migrationKey): bool
{
    sms2MigrateEnsureTrackingTable($pdo);

    $stmt = $pdo->prepare(
        'SELECT 1 FROM `sms_schema_migrations` WHERE `migration_key` = ? LIMIT 1'
    );
    $stmt->execute([$migrationKey]);

    return (bool) $stmt->fetchColumn();
}

function sms2MigrateRecord(PDO $pdo, string $migrationKey, string $sourceFile): void
{
    sms2MigrateEnsureTrackingTable($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO `sms_schema_migrations` (`migration_key`, `source_file`, `source_sha256`)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE
             `source_file` = VALUES(`source_file`),
             `source_sha256` = VALUES(`source_sha256`),
             `applied_at` = current_timestamp()'
    );
    $stmt->execute([
        $migrationKey,
        str_replace('\\', '/', $sourceFile),
        hash_file('sha256', $sourceFile),
    ]);
}

function sms2MigrateOneDatabase(array $target, array $options, ?callable $sink = null): void
{
    $label = $target['label'];
    $database = $target['database'];
    $quotedDatabase = sms2MigrateQuoteIdentifier($database);

    sms2MigrateOut('', $sink);
    sms2MigrateOut('== ' . $label . ' ==', $sink);
    sms2MigrateOut('SQL: ' . $target['sql_file'], $sink);
    sms2MigrateOut('DB : ' . $target['host'] . '/' . $database, $sink);

    $pdo = sms2MigrateConnectServer(
        $target['host'],
        $target['port'],
        $target['user'],
        $target['pass'],
        $target['charset']
    );

    if ($options['fresh']) {
        sms2MigrateOut('Dropping existing database because --fresh was provided...', $sink);
        $pdo->exec('DROP DATABASE IF EXISTS ' . $quotedDatabase);
    }

    sms2MigrateEnsureDatabase($pdo, $database, $sink);
    $pdo->exec('USE ' . $quotedDatabase);

    $targetTables = sms2MigrateSqlCreateTables($target['sql_file']);
    $existingNames = sms2MigrateExistingTableNames($pdo);
    // Unified schema uses sms_users; accept legacy `users` during transitional dumps.
    $usersPresent = in_array('sms_users', $existingNames, true)
        || in_array('users', $existingNames, true);

    if (!empty($options['force'])) {
        sms2MigrateOut('Force re-import: dropping dump tables in ' . $database . '...', $sink);
        sms2MigrateDropTables($pdo, array_values(array_unique(array_merge(
            $targetTables,
            sms2MigrateLeftoverOnlyTables()
        ))), $sink);
    } elseif (!$options['fresh']) {
        if (sms2MigrateWasRecorded($pdo, $target['migration_key']) && $usersPresent) {
            sms2MigrateOut('Skipped: migration was already recorded. Use --force to re-apply.', $sink);
            return;
        }

        $existingTargetTables = sms2MigrateExistingTargetTableCount($pdo, $targetTables);
        if ($targetTables && $existingTargetTables === count($targetTables) && $usersPresent) {
            sms2MigrateRecord($pdo, $target['migration_key'], $target['sql_file']);
            sms2MigrateOut('Skipped: target tables already exist; recorded this migration as applied.', $sink);
            return;
        }

        // HostForge often has only login_throttles (created by failed logins) and no users table.
        if ($existingTargetTables > 0 && !$usersPresent) {
            sms2MigrateOut('Incomplete schema (sms_users missing). Clearing leftover tables before import...', $sink);
            sms2MigrateDropTables($pdo, array_values(array_unique(array_merge(
                $targetTables,
                sms2MigrateLeftoverOnlyTables()
            ))), $sink);
        } elseif ($existingTargetTables > 0) {
            throw new RuntimeException(
                'Partial schema detected for ' . $label .
                '. Use --force to re-import ' . basename($target['sql_file']) . ' into ' . $database . '.'
            );
        }
    }

    $applied = sms2MigrateApplySqlFile($pdo, $target['sql_file']);
    sms2MigrateRecord($pdo, $target['migration_key'], $target['sql_file']);

    sms2MigrateOut('Applied ' . $applied . ' SQL statement(s) into ' . $database . '.', $sink);
}

/**
 * @return array<int, string>
 */
function sms2RunMigrations(array $options = []): array
{
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../modules/crad/config/config.php';

    $options = array_merge(['fresh' => false, 'force' => false, 'skip_crad' => false], $options);
    $lines = [];
    $sink = static function (string $message) use (&$lines): void {
        $lines[] = $message;
    };

    $targets = [
        [
            'label' => 'SMS2 unified database (sms_* + crad_*)',
            'migration_key' => '2026_09_16_sms2_unified_prefixed',
            'host' => DB_HOST,
            'port' => DB_PORT,
            'database' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'charset' => DB_CHARSET,
            'sql_file' => __DIR__ . '/sms2_db.sql',
        ],
    ];

    // Only apply legacy separate crad_db.sql when CRAD still uses a different database.
    if (strcasecmp((string) DB_NAME, (string) CRAD_DB_NAME) !== 0) {
        $targets[] = [
            'label' => 'CRAD module database (legacy separate)',
            'migration_key' => '2026_09_16_crad_db_prefixed',
            'host' => CRAD_DB_HOST,
            'port' => CRAD_DB_PORT,
            'database' => CRAD_DB_NAME,
            'user' => CRAD_DB_USER,
            'pass' => CRAD_DB_PASS,
            'charset' => CRAD_DB_CHARSET,
            'sql_file' => dirname(__DIR__) . '/modules/crad/database/crad_db.sql',
        ];
    }

    $connection = strtolower((string) sms2_env_first(['SMS2_DB_CONNECTION', 'DB_CONNECTION'], 'mysql'));
    if (!in_array($connection, ['mysql', 'mariadb'], true)) {
        throw new RuntimeException(
            'Unsupported DB_CONNECTION "' . $connection . '". Use MySQL/MariaDB for SMS 2.'
        );
    }

    sms2MigrateOut('SMS 2 deployment migration started.', $sink);
    if (strcasecmp((string) DB_NAME, (string) CRAD_DB_NAME) === 0) {
        sms2MigrateOut('CRAD tables target the main database (' . DB_NAME . ') — single-DB mode.', $sink);
    } else {
        sms2MigrateOut(
            'CRAD uses a separate database (' . CRAD_DB_NAME . '). Prefer CRAD_DB_NAME=' . DB_NAME . ' unless intentionally split.',
            $sink
        );
    }
    foreach ($targets as $target) {
        $isCrad = str_contains((string) $target['migration_key'], 'crad_db');
        if ($isCrad && !empty($options['skip_crad'])) {
            sms2MigrateOut('', $sink);
            sms2MigrateOut('Skipped CRAD database (sms2-only import).', $sink);
            continue;
        }
        try {
            sms2MigrateOneDatabase($target, $options, $sink);
        } catch (Throwable $e) {
            if ($isCrad) {
                sms2MigrateOut('CRAD import skipped: ' . $e->getMessage(), $sink);
                continue;
            }
            throw $e;
        }
    }
    sms2MigrateOut('', $sink);
    sms2MigrateOut('Migration complete.', $sink);

    return $lines;
}
