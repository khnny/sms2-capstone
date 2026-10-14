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

    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`/i', (string) file_get_contents($sqlFile), $matches);

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
    $statements = sms2MigrateSplitSql($sql);
    $applied = 0;

    foreach ($statements as $statement) {
        $pdo->exec($statement);
        $applied++;
    }

    return $applied;
}

function sms2MigrateEnsureTrackingTable(PDO $pdo): void
{
    // Prefer prefixed name; keep creating legacy name only if migrating old installs mid-cutover.
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS `schema_migrations` (
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
        'SELECT 1 FROM `schema_migrations` WHERE `migration_key` = ? LIMIT 1'
    );
    $stmt->execute([$migrationKey]);

    return (bool) $stmt->fetchColumn();
}

function sms2MigrateRecord(PDO $pdo, string $migrationKey, string $sourceFile): void
{
    sms2MigrateEnsureTrackingTable($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO `schema_migrations` (`migration_key`, `source_file`, `source_sha256`)
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

/** A failed dump can leave every table present but its indexes unfinished. */
function sms2MigrateValidateExistingSchema(PDO $pdo, string $sourceFile): void
{
    $sql = (string) file_get_contents($sourceFile);
    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`([^`]+)`\s*\((.*?)\)\s*ENGINE/is', $sql, $tables, PREG_SET_ORDER);
    foreach ($tables as $table) {
        $name = $table[1];
        $ddl = $table[2];
        preg_match_all('/ALTER\s+TABLE\s+`' . preg_quote($name, '/') . '`\s+(.*?);/is', $sql, $alters);
        $definitions = $ddl . "\n" . implode("\n", $alters[1]);
        $columns = $pdo->prepare('SELECT COLUMN_NAME, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $columns->execute([$name]);
        $existing = $columns->fetchAll(PDO::FETCH_KEY_PAIR);
        preg_match_all('/^\s*`([^`]+)`\s+\w+/m', $ddl, $expected);
        foreach ($expected[1] as $column) {
            if (!array_key_exists($column, $existing)) {
                throw new RuntimeException('Incomplete schema: ' . $name . '.' . $column . ' is missing. Repair the schema before recording the dump; do not use --fresh on live data.');
            }
        }
        if (preg_match('/PRIMARY\s+KEY/i', $definitions)) {
            $keys = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = 'PRIMARY'");
            $keys->execute([$name]);
            if (!(int) $keys->fetchColumn()) {
                throw new RuntimeException('Incomplete schema: ' . $name . ' has no primary key. Finish or repair the import before recording the dump.');
            }
        }
        preg_match_all('/(?:^|\n)\s*(?:MODIFY\s+)?`([^`]+)`[^\n,]*AUTO_INCREMENT/i', $definitions, $auto);
        foreach (array_unique($auto[1]) as $column) {
            if (!str_contains(strtolower($existing[$column] ?? ''), 'auto_increment')) {
                throw new RuntimeException('Incomplete schema: ' . $name . '.' . $column . ' is missing AUTO_INCREMENT. Run the additive schema repair before retrying.');
            }
        }
    }
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

    if ($options['force'] && sms2MigrateExistingTargetTableCount($pdo, sms2MigrateSqlCreateTables($target['sql_file'])) > 0) {
        throw new RuntimeException('Cannot replay a full dump over existing tables. Use additive repairs for upgrades; --fresh is only for disposable databases or an intentional rebuild.');
    }

    sms2MigrateEnsureV3Columns($pdo, $sink);

    if (!$options['fresh'] && !$options['force']) {
        if (sms2MigrateWasRecorded($pdo, $target['migration_key'])) {
            $recorded = $pdo->prepare('SELECT source_sha256 FROM schema_migrations WHERE migration_key = ?');
            $recorded->execute([$target['migration_key']]);
            if ($recorded->fetchColumn() !== hash_file('sha256', $target['sql_file'])) {
                sms2MigrateOut('Warning: the recorded dump differs from this release. Existing data is preserved; run the documented additive repairs. Replaying a full dump is not an upgrade.', $sink);
            }
            sms2MigrateOut('Skipped: migration was already recorded.', $sink);
            return;
        }

        $targetTables = sms2MigrateSqlCreateTables($target['sql_file']);
        $existingTargetTables = sms2MigrateExistingTargetTableCount($pdo, $targetTables);
        if ($targetTables && $existingTargetTables === count($targetTables)) {
            sms2MigrateValidateExistingSchema($pdo, $target['sql_file']);
            sms2MigrateRecord($pdo, $target['migration_key'], $target['sql_file']);
            sms2MigrateOut('Skipped: target tables already exist; recorded this migration as applied.', $sink);
            return;
        }

        if ($existingTargetTables > 0) {
            throw new RuntimeException(
                'Partial schema detected for ' . $label .
                '. Use --fresh to rebuild, or fix the existing tables before migrating.'
            );
        }
    }

    $applied = sms2MigrateApplySqlFile($pdo, $target['sql_file']);
    sms2MigrateRecord($pdo, $target['migration_key'], $target['sql_file']);

    sms2MigrateOut('Applied ' . $applied . ' SQL statement(s).', $sink);
}

/** Keep clearance/payment images in HostForge's managed database across redeploys. */
function sms2MigrateEnsureDurableUploadColumns(PDO $pdo, ?callable $sink = null): void
{
    $tables = [
        'crad_research_clearance_payments',
        'crad_research_services_clearances',
    ];
    $columns = [
        'uploaded_blob' => 'MEDIUMBLOB NULL',
        'uploaded_mime' => "VARCHAR(100) NOT NULL DEFAULT ''",
        'uploaded_size' => 'INT UNSIGNED NOT NULL DEFAULT 0',
    ];
    foreach ($tables as $table) {
        // MariaDB does not accept native PDO placeholders in SHOW statements.
        // Query information_schema instead so this works on HostForge.
        $exists = $pdo->prepare(
            'SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1'
        );
        $exists->execute([$table]);
        if (!$exists->fetchColumn()) continue;
        foreach ($columns as $column => $definition) {
            $check = $pdo->prepare(
                'SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
            );
            $check->execute([$table, $column]);
            if ($check->fetchColumn()) continue;
            $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
            sms2MigrateOut('Added durable upload column ' . $table . '.' . $column, $sink);
        }
    }
}

/** Keep legacy member receipts separate for Research 1 and Research 2. */
function sms2MigrateEnsureMemberStages(PDO $pdo, ?callable $sink = null): void
{
    $table = 'crad_research_group_members';
    $exists = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $exists->execute([$table]);
    if (!$exists->fetchColumn()) {
        return;
    }
    $column = $pdo->prepare("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'research_stage'");
    $column->execute([$table]);
    if (!$column->fetchColumn()) {
        $pdo->exec("ALTER TABLE `crad_research_group_members` ADD COLUMN `research_stage` VARCHAR(20) NOT NULL DEFAULT 'research_1' AFTER `research_group_id`");
        sms2MigrateOut('Added research-stage separation for member receipt references.', $sink);
    }
    $index = $pdo->prepare("SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = 'idx_rgm_group_stage'");
    $index->execute([$table]);
    if (!$index->fetchColumn()) {
        $pdo->exec('ALTER TABLE `crad_research_group_members` ADD KEY `idx_rgm_group_stage` (`research_group_id`, `research_stage`)');
    }
}

/** Verified additive changes from the original capstone schema to v3. */
function sms2MigrateEnsureV3Columns(PDO $pdo, ?callable $sink = null): void
{
    $changes = [
        'sms2_admin_announcements' => [
            'category' => "VARCHAR(40) NOT NULL DEFAULT 'General'",
            'scheduled_for' => 'DATE NULL',
            'expires_at' => 'DATE NULL',
        ],
        'crad_panel_member_availability' => [
            'availability_windows_json' => 'LONGTEXT NULL',
        ],
        'crad_research_services_clearances' => [
            'crad_remarks' => "VARCHAR(500) NOT NULL DEFAULT ''",
        ],
    ];
    foreach ($changes as $table => $columns) {
        $exists = $pdo->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $exists->execute([$table]);
        if (!$exists->fetchColumn()) {
            continue;
        }
        foreach ($columns as $column => $definition) {
            $check = $pdo->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
            $check->execute([$table, $column]);
            if (!$check->fetchColumn()) {
                $pdo->exec('ALTER TABLE ' . sms2MigrateQuoteIdentifier($table) . ' ADD COLUMN ' . sms2MigrateQuoteIdentifier($column) . ' ' . $definition);
                sms2MigrateOut('Added v3 column ' . $table . '.' . $column, $sink);
            }
        }
    }
    sms2MigrateEnsureDurableUploadColumns($pdo, $sink);
    sms2MigrateEnsureMemberStages($pdo, $sink);
}

/**
 * @return array<int, string>
 */
function sms2RunMigrations(array $options = []): array
{
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../modules/crad/config/config.php';

    $options = array_merge(['fresh' => false, 'force' => false], $options);
    if (CRAD_DB_NAME !== DB_NAME || CRAD_DB_HOST !== DB_HOST || CRAD_DB_PORT !== DB_PORT) {
        throw new RuntimeException('Single-database mode requires CRAD_DB_HOST, CRAD_DB_PORT and CRAD_DB_NAME to match the main database. Omit the CRAD overrides or update them before migrating.');
    }
    $lines = [];
    $sink = static function (string $message) use (&$lines): void {
        $lines[] = $message;
    };

    // Single-database architecture: both dumps apply into DB_NAME (sms2_* + crad_*).
    $targets = [
        [
            'label' => 'SMS2 schema (sms2_*)',
            'migration_key' => '2026_09_21_sms2_prefixed_dump',
            'host' => DB_HOST,
            'port' => DB_PORT,
            'database' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'charset' => DB_CHARSET,
            'sql_file' => __DIR__ . '/sms2_db.sql',
        ],
        [
            'label' => 'CRAD schema (crad_*)',
            'migration_key' => '2026_09_21_crad_prefixed_dump',
            'host' => DB_HOST,
            'port' => DB_PORT,
            'database' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'charset' => DB_CHARSET,
            'sql_file' => dirname(__DIR__) . '/modules/crad/database/crad_db.sql',
        ],
    ];

    sms2MigrateOut('Single-database mode: applying SMS2 + CRAD into ' . DB_NAME, $sink);

    $connection = strtolower((string) sms2_env_first(['SMS2_DB_CONNECTION', 'DB_CONNECTION'], 'mysql'));
    if (!in_array($connection, ['mysql', 'mariadb'], true)) {
        throw new RuntimeException(
            'Unsupported DB_CONNECTION "' . $connection . '". Use MySQL/MariaDB for SMS 2.'
        );
    }

    sms2MigrateOut('SMS 2 deployment migration started.', $sink);
    if (!$options['fresh']) {
        $server = sms2MigrateConnectServer(DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_CHARSET);
        $legacy = $server->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN ('users', 'sms_users')");
        $legacy->execute([DB_NAME]);
        if ((int) $legacy->fetchColumn() > 0) {
            throw new RuntimeException('Legacy users tables detected. Back up the database, run database/migrate_to_single_prefixed.php --dry-run, then the prefix migration and additive repairs. Initial-install dumps must not replace legacy accounts.');
        }
    }
    // Both schemas share a physical database. Rebuild it once, before either dump.
    if ($options['fresh']) {
        $pdo = sms2MigrateConnectServer(DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_CHARSET);
        sms2MigrateOut('Dropping the shared database because --fresh was provided...', $sink);
        $pdo->exec('DROP DATABASE IF EXISTS ' . sms2MigrateQuoteIdentifier(DB_NAME));
        $options['fresh'] = false;
    }
    foreach ($targets as $target) {
        sms2MigrateOneDatabase($target, $options, $sink);
    }
    $pdo = sms2MigrateConnectServer(DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_CHARSET);
    $pdo->exec('USE ' . sms2MigrateQuoteIdentifier(DB_NAME));
    sms2MigrateEnsureDurableUploadColumns($pdo, $sink);
    sms2MigrateEnsureMemberStages($pdo, $sink);
    sms2MigrateOut('', $sink);
    sms2MigrateOut('Migration complete.', $sink);

    return $lines;
}
