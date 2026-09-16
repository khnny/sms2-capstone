<?php
/**
 * CRAD Module - Database Configuration
 *
 * Default: CRAD tables (crad_*) live in the same database as SMS2 (sms_*).
 * Override CRAD_DB_* only for a rare intentional split install.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once dirname(__DIR__, 3) . '/config/database.php';

if (!defined('CRAD_DB_HOST')) {
    define('CRAD_DB_HOST', sms2_env_first(['CRAD_DB_HOST', 'SMS2_DB_HOST', 'DB_HOST', 'MYSQL_HOST', 'MARIADB_HOST'], DB_HOST));
}
if (!defined('CRAD_DB_PORT')) {
    define('CRAD_DB_PORT', sms2_env_first(['CRAD_DB_PORT', 'SMS2_DB_PORT', 'DB_PORT', 'MYSQL_PORT', 'MARIADB_PORT'], DB_PORT));
}
if (!defined('CRAD_DB_NAME')) {
    // Unified default: same database as SMS2 (DB_NAME / HostForge DB_DATABASE).
    $cradEnvName = sms2_env_first(['CRAD_DB_NAME'], null);
    if ($cradEnvName !== null && $cradEnvName !== '' && strcasecmp($cradEnvName, 'crad_db') !== 0) {
        define('CRAD_DB_NAME', $cradEnvName);
    } else {
        define('CRAD_DB_NAME', DB_NAME);
    }
}
if (!defined('CRAD_DB_USER')) {
    define('CRAD_DB_USER', sms2_env_first(['CRAD_DB_USER', 'SMS2_DB_USER', 'DB_USERNAME', 'DB_USER', 'MYSQL_USER', 'MARIADB_USER'], DB_USER));
}
if (!defined('CRAD_DB_PASS')) {
    $cradPass = sms2_env_first(['CRAD_DB_PASS', 'SMS2_DB_PASS', 'DB_PASSWORD', 'DB_PASS', 'MYSQL_PASSWORD', 'MARIADB_PASSWORD'], null);
    define('CRAD_DB_PASS', $cradPass !== null ? $cradPass : DB_PASS);
}
if (!defined('CRAD_DB_CHARSET')) {
    define('CRAD_DB_CHARSET', sms2_env_first(['CRAD_DB_CHARSET', 'SMS2_DB_CHARSET', 'DB_CHARSET'], DB_CHARSET));
}
if (!defined('CRAD_DB_CONNECTION')) {
    define('CRAD_DB_CONNECTION', strtolower((string) sms2_env_first(['CRAD_DB_CONNECTION', 'SMS2_DB_CONNECTION', 'DB_CONNECTION'], 'mysql')));
}
if (!defined('CRAD_PHASE_1ST_SEM')) {
    define('CRAD_PHASE_1ST_SEM', '1st Semester');
}
if (!defined('CRAD_PHASE_2ND_SEM')) {
    define('CRAD_PHASE_2ND_SEM', '2nd Semester');
}
if (!defined('CRAD_DEFENSE_TYPE_PRE_ORAL')) {
    define('CRAD_DEFENSE_TYPE_PRE_ORAL', 'Pre-Oral');
}
if (!defined('CRAD_DEFENSE_TYPE_FINAL')) {
    define('CRAD_DEFENSE_TYPE_FINAL', 'Final Defense');
}
if (!defined('CRAD_DEFENSE_PHASE_PRE_ORAL')) {
    define('CRAD_DEFENSE_PHASE_PRE_ORAL', 'Pre-Oral Defense');
}
if (!defined('CRAD_DEFENSE_PHASE_FINAL')) {
    define('CRAD_DEFENSE_PHASE_FINAL', 'Final Defense');
}

/**
 * Get CRAD database connection (singleton).
 *
 * @return PDO
 * @throws RuntimeException when connection fails
 */
function getCradDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // #region agent log
    $sms2AgentDebugLog = static function (string $hypothesisId, string $message, array $data = []): void {
        $payload = [
            'sessionId' => '4aceee',
            'runId' => 'pre-fix',
            'hypothesisId' => $hypothesisId,
            'location' => 'modules/crad/config/config.php:getCradDatabaseConnection',
            'message' => $message,
            'data' => $data,
            'timestamp' => (int) round(microtime(true) * 1000),
        ];
        $line = json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n";
        @file_put_contents(dirname(__DIR__, 3) . '/debug-4aceee.log', $line, FILE_APPEND | LOCK_EX);
        error_log('SMS2_DEBUG ' . $line);
    };
    // #endregion

    if (!in_array(CRAD_DB_CONNECTION, ['mysql', 'mariadb'], true)) {
        throw new RuntimeException(
            'Unsupported CRAD database connection "' . CRAD_DB_CONNECTION . '". Select MySQL/MariaDB on HostForge for SMS 2.'
        );
    }

    $nameSame = strcasecmp((string) CRAD_DB_NAME, (string) DB_NAME) === 0;
    $hostSame = strcasecmp((string) CRAD_DB_HOST, (string) DB_HOST) === 0;

    // #region agent log
    $sms2AgentDebugLog('A', 'CRAD connection decision inputs', [
        'cradHost' => (string) CRAD_DB_HOST,
        'cradName' => (string) CRAD_DB_NAME,
        'cradPort' => (string) CRAD_DB_PORT,
        'dbHost' => (string) DB_HOST,
        'dbName' => (string) DB_NAME,
        'nameSame' => $nameSame,
        'hostSame' => $hostSame,
        'willReuseMainPdo' => ($nameSame && $hostSame),
    ]);
    // #endregion

    // Same database as SMS2 → reuse the main PDO (single HostForge DB).
    if ($nameSame && $hostSame) {
        // #region agent log
        $sms2AgentDebugLog('D', 'Taking same-DB reuse path', ['path' => 'reuse_main_pdo']);
        // #endregion
        $pdo = getDatabaseConnection();
        cradEnsurePanelNotificationDeleteTrigger($pdo);
        cradCleanupPreoralEvaluationsForInvalidRegistry($pdo);
        return $pdo;
    }

    // #region agent log
    $sms2AgentDebugLog('B', 'Taking separate CRAD DSN path', [
        'path' => 'separate_dsn',
        'reason' => !$nameSame ? 'name_mismatch' : 'host_mismatch',
    ]);
    // #endregion

    $pdoOptions = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // HostForge UI often shows a published port (e.g. 33632) while app pods
    // reach MariaDB on the in-cluster port 3306 (same pattern as attached DB_*).
    $portsToTry = [CRAD_DB_PORT];
    if ((string) CRAD_DB_PORT !== '3306') {
        $portsToTry[] = '3306';
    }

    $lastException = null;
    foreach ($portsToTry as $port) {
        $dsn = 'mysql:host=' . CRAD_DB_HOST . ';port=' . $port . ';dbname=' . CRAD_DB_NAME . ';charset=' . CRAD_DB_CHARSET;
        try {
            $pdo = new PDO($dsn, CRAD_DB_USER, CRAD_DB_PASS, $pdoOptions);
            if ((string) $port !== (string) CRAD_DB_PORT) {
                error_log('CRAD DB connected via fallback port ' . $port . ' (CRAD_DB_PORT=' . CRAD_DB_PORT . ')');
            }
            cradEnsurePanelNotificationDeleteTrigger($pdo);
            cradCleanupPreoralEvaluationsForInvalidRegistry($pdo);
            return $pdo;
        } catch (PDOException $e) {
            $lastException = $e;
            // #region agent log
            $sms2AgentDebugLog('A', 'Separate CRAD DSN failed', [
                'port' => (string) $port,
                'errorClass' => get_class($e),
                'error' => $e->getMessage(),
            ]);
            // #endregion
            error_log('CRAD DB connection failed on port ' . $port . ': ' . $e->getMessage());
        }
    }

    // After a failed separate CRAD DSN, fall back to the main SMS2 database.
    // Covers legacy CRAD_DB_NAME=crad_db and stale HostForge CRAD_DB_* pointing at
    // a dead MariaDB (e.g. mariadb-edee97zl / hf_db_edee97zl) while the app uses
    // the attached unified DB_DATABASE.
    // #region agent log
    $sms2AgentDebugLog('C', 'Attempting main-DB fallback after separate DSN failed', [
        'willAttemptMainFallback' => true,
        'fallbackDbHost' => (string) DB_HOST,
        'fallbackDbName' => (string) DB_NAME,
    ]);
    // #endregion
    try {
        $pdo = getDatabaseConnection();
        cradEnsurePanelNotificationDeleteTrigger($pdo);
        cradCleanupPreoralEvaluationsForInvalidRegistry($pdo);
        // #region agent log
        $sms2AgentDebugLog('E', 'Main-DB fallback succeeded', [
            'path' => 'fallback_main_pdo',
            'dbName' => (string) DB_NAME,
        ]);
        // #endregion
        error_log(
            'CRAD fell back to main DB_HOST=' . DB_HOST . ' DB_NAME=' . DB_NAME
            . ' after separate CRAD target failed (' . CRAD_DB_HOST . '/' . CRAD_DB_NAME . ').'
        );
        return $pdo;
    } catch (Throwable $fallbackError) {
        // #region agent log
        $sms2AgentDebugLog('E', 'Main-DB fallback failed', [
            'error' => $fallbackError->getMessage(),
        ]);
        // #endregion
        error_log('CRAD main-DB fallback failed: ' . $fallbackError->getMessage());
    }

    // #region agent log
    $sms2AgentDebugLog('E', 'Throwing CRAD unavailable (no fallback taken)', [
        'cradHost' => (string) CRAD_DB_HOST,
        'cradName' => (string) CRAD_DB_NAME,
    ]);
    // #endregion

    throw new RuntimeException(
        'CRAD database unavailable (' . CRAD_DB_HOST . ':' . CRAD_DB_PORT . '/' . CRAD_DB_NAME . '). '
        . 'Import database/sms2_db.sql into DB_DATABASE and set CRAD_DB_NAME to the same value as DB_DATABASE '
        . '(or remove obsolete CRAD_DB_* env vars pointing at a deleted MariaDB).',
        0,
        $lastException
    );
}

/**
 * Safe helper — returns null instead of throwing (for optional features).
 *
 * @return PDO|null
 */
function cradDb(): ?PDO
{
    try {
        return getCradDatabaseConnection();
    } catch (Throwable $e) {
        error_log('cradDb() unavailable: ' . $e->getMessage());
        return null;
    }
}

function cradValidTitleApprovalWhereSql(string $alias = 't'): string
{
    return "{$alias}.status = 'Approved'
        AND {$alias}.coordinator_status = 'Approved'
        AND {$alias}.crad_status = 'Approved'
        AND {$alias}.adviser_signature_data IS NOT NULL
        AND {$alias}.adviser_signature_data <> ''
        AND {$alias}.coordinator_signature_data IS NOT NULL
        AND {$alias}.coordinator_signature_data <> ''
        AND {$alias}.crad_signature_data IS NOT NULL
        AND {$alias}.crad_signature_data <> ''";
}

function cradOfficialRegistryGroupWhereSql(string $groupAlias = 'rg'): string
{
    return "{$groupAlias}.title_approval_id IS NOT NULL
        AND TRIM(COALESCE({$groupAlias}.research_title, '')) <> ''
        AND TRIM(COALESCE({$groupAlias}.academic_year, '')) <> ''
        AND EXISTS (
            SELECT 1
            FROM crad_title_approvals official_t
            WHERE official_t.id = {$groupAlias}.title_approval_id
              AND " . cradValidTitleApprovalWhereSql('official_t') . "
              AND (
                    TRIM(COALESCE({$groupAlias}.college_dept, '')) <> ''
                 OR TRIM(COALESCE(official_t.department, '')) <> ''
              )
        )
        AND EXISTS (
            SELECT 1
            FROM crad_research_coordinator_assignments official_ca
            WHERE official_ca.status = 'Active'
              AND (
                    official_ca.research_group_id = {$groupAlias}.id
                 OR (official_ca.research_group_id IS NULL AND official_ca.group_number = {$groupAlias}.group_number)
              )
        )
        AND EXISTS (
            SELECT 1
            FROM crad_research_adviser_assignments official_aa
            WHERE (
                    official_aa.research_group_id = {$groupAlias}.id
                 OR (official_aa.research_group_id IS NULL AND official_aa.group_number = {$groupAlias}.group_number)
              )
        )";
}

function cradCleanupPanelNotificationsForInvalidRegistry(PDO $pdo, ?array $groupIds = null): array
{
    if (!$pdo->query("SHOW TABLES LIKE 'crad_panel_assignment_notifications'")->fetchColumn()
        || !$pdo->query("SHOW TABLES LIKE 'crad_research_groups'")->fetchColumn()) {
        return ['ok' => true, 'deleted' => 0, 'message' => 'Panel notification cleanup skipped; required table is missing.'];
    }

    $params = [];
    $scopeSql = '';
    if (is_array($groupIds)) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $groupIds), static fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return ['ok' => true, 'deleted' => 0, 'message' => 'No research group ids selected for panel notification cleanup.'];
        }
        $scopeSql = 'AND pan.research_group_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
        $params = $ids;
    }

    $stmt = $pdo->prepare("
        DELETE pan
        FROM crad_panel_assignment_notifications pan
        WHERE pan.research_group_id IS NOT NULL
          {$scopeSql}
          AND NOT EXISTS (
                SELECT 1
                FROM crad_research_groups rg
                WHERE rg.id = pan.research_group_id
                  AND " . cradOfficialRegistryGroupWhereSql('rg') . "
          )
    ");
    $stmt->execute($params);

    return [
        'ok' => true,
        'deleted' => $stmt->rowCount(),
        'message' => 'Cleaned invalid registry-linked panel notification(s).',
    ];
}

/**
 * Delete preoral_defense_evaluations rows whose research_group_id no longer
 * belongs to a group that qualifies for the official Capstone Registry.
 *
 * Called automatically on every getCradDatabaseConnection() so the cleanup is
 * real-time: the moment a group loses its registry eligibility (e.g. title
 * approval revoked, coordinator/adviser removed) all related pre-oral evaluation
 * records are purged from the database and disappear from the Evaluation History
 * page on the next poll cycle (≤ 10 seconds).
 *
 * @param PDO         $pdo      CRAD database connection.
 * @param int[]|null  $groupIds Optional: limit the sweep to specific group ids.
 * @return array{ok: bool, deleted: int, message: string}
 */
function cradCleanupPreoralEvaluationsForInvalidRegistry(PDO $pdo, ?array $groupIds = null): array
{
    static $checked = false;
    // Only run the full global sweep once per request (subsequent calls with
    // specific $groupIds will still execute the scoped delete).
    if ($checked && $groupIds === null) {
        return ['ok' => true, 'deleted' => 0, 'message' => 'Pre-oral evaluation cleanup already ran this request.'];
    }
    if ($groupIds === null) {
        $checked = true;
    }

    foreach (['crad_preoral_defense_evaluations', 'crad_research_groups'] as $tbl) {
        if (!$pdo->query("SHOW TABLES LIKE " . $pdo->quote($tbl))->fetchColumn()) {
            return ['ok' => true, 'deleted' => 0, 'message' => 'Pre-oral evaluation cleanup skipped; table ' . $tbl . ' is missing.'];
        }
    }

    $params   = [];
    $scopeSql = '';
    if (is_array($groupIds)) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $groupIds), static fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return ['ok' => true, 'deleted' => 0, 'message' => 'No research group ids selected for pre-oral evaluation cleanup.'];
        }
        $scopeSql = 'AND ev.research_group_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
        $params   = $ids;
    }

    try {
        $stmt = $pdo->prepare("
            DELETE ev
            FROM crad_preoral_defense_evaluations ev
            WHERE ev.research_group_id IS NOT NULL
              {$scopeSql}
              AND NOT EXISTS (
                    SELECT 1
                    FROM crad_research_groups rg
                    WHERE rg.id = ev.research_group_id
                      AND " . cradOfficialRegistryGroupWhereSql('rg') . "
              )
        ");
        $stmt->execute($params);

        return [
            'ok'      => true,
            'deleted' => $stmt->rowCount(),
            'message' => 'Removed ' . $stmt->rowCount() . ' pre-oral evaluation(s) for groups no longer in the official registry.',
        ];
    } catch (Throwable $e) {
        error_log('Pre-oral evaluation registry cleanup failed: ' . $e->getMessage());
        return ['ok' => false, 'deleted' => 0, 'message' => 'Pre-oral evaluation cleanup error: ' . $e->getMessage()];
    }
}

function cradEnsurePanelNotificationDeleteTrigger(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        if (!$pdo->query("SHOW TABLES LIKE 'crad_research_groups'")->fetchColumn()
            || !$pdo->query("SHOW TABLES LIKE 'crad_panel_assignment_notifications'")->fetchColumn()) {
            return;
        }

        $stmt = $pdo->prepare("
            SELECT TRIGGER_NAME
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
              AND TRIGGER_NAME = 'trg_research_groups_panel_notifications_after_delete'
            LIMIT 1
        ");
        $stmt->execute();
        if ($stmt->fetchColumn()) {
            return;
        }

        $pdo->exec("
            CREATE TRIGGER trg_research_groups_panel_notifications_after_delete
            AFTER DELETE ON crad_research_groups
            FOR EACH ROW
            BEGIN
                DELETE FROM crad_panel_assignment_notifications
                WHERE research_group_id = OLD.id;
            END
        ");
    } catch (Throwable $e) {
        error_log('Panel notification delete trigger ensure failed: ' . $e->getMessage());
    }
}

function cradFindForeignKey(PDO $pdo, string $table, string $column, string $referencedTable, string $referencedColumn): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            kcu.CONSTRAINT_NAME,
            rc.DELETE_RULE,
            rc.UPDATE_RULE
        FROM information_schema.KEY_COLUMN_USAGE kcu
        INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
            ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
           AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
           AND rc.TABLE_NAME = kcu.TABLE_NAME
        WHERE kcu.TABLE_SCHEMA = DATABASE()
          AND kcu.TABLE_NAME = :table_name
          AND kcu.COLUMN_NAME = :column_name
          AND kcu.REFERENCED_TABLE_NAME = :referenced_table
          AND kcu.REFERENCED_COLUMN_NAME = :referenced_column
        LIMIT 1
    ");
    $stmt->execute([
        ':table_name' => $table,
        ':column_name' => $column,
        ':referenced_table' => $referencedTable,
        ':referenced_column' => $referencedColumn,
    ]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function cradDropForeignKey(PDO $pdo, string $table, string $constraintName): void
{
    $pdo->exec('ALTER TABLE `' . str_replace('`', '``', $table) . '` DROP FOREIGN KEY `' . str_replace('`', '``', $constraintName) . '`');
}

function cradEnsureResearchGroupHistoryProtection(PDO $pdo): array
{
    $result = [
        'ok' => true,
        'changed' => false,
        'message' => 'Research group child history protection is ready.',
    ];

    foreach (['crad_research_groups', 'crad_research_plans'] as $table) {
        if (!$pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn()) {
            return [
                'ok' => false,
                'changed' => false,
                'message' => 'Missing required table: ' . $table,
            ];
        }
    }

    $column = $pdo->query("SHOW COLUMNS FROM crad_research_plans LIKE 'research_group_id'")->fetch();
    if (!$column) {
        return [
            'ok' => false,
            'changed' => false,
            'message' => 'research_plans.research_group_id is missing.',
        ];
    }

    $fk = cradFindForeignKey($pdo, 'crad_research_plans', 'research_group_id', 'crad_research_groups', 'id');
    $needsNullable = strtoupper((string) ($column['Null'] ?? '')) !== 'YES';
    $needsSetNull = !$fk || strtoupper((string) ($fk['DELETE_RULE'] ?? '')) !== 'SET NULL';

    if (!$needsNullable && !$needsSetNull) {
        return $result;
    }

    if ($fk) {
        cradDropForeignKey($pdo, 'crad_research_plans', (string) $fk['CONSTRAINT_NAME']);
        $result['changed'] = true;
    }

    if ($needsNullable) {
        $pdo->exec('ALTER TABLE crad_research_plans MODIFY research_group_id INT UNSIGNED NULL COMMENT ' . $pdo->quote('FK to research_groups; nullable to preserve history if group is removed'));
        $result['changed'] = true;
    }

    $pdo->exec("
        ALTER TABLE crad_research_plans
        ADD CONSTRAINT fk_rp_research_group
        FOREIGN KEY (research_group_id)
        REFERENCES crad_research_groups(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
    ");
    $result['changed'] = true;
    $result['message'] = 'Updated research_plans FK to preserve history when a research group is removed.';
    return $result;
}

function cradCountOrphanResearchGroups(PDO $pdo): int
{
    if (!$pdo->query("SHOW TABLES LIKE 'crad_title_approvals'")->fetchColumn()
        || !$pdo->query("SHOW TABLES LIKE 'crad_research_groups'")->fetchColumn()) {
        return 0;
    }

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM crad_research_groups rg
        LEFT JOIN crad_title_approvals t ON t.id = rg.title_approval_id
        WHERE rg.title_approval_id IS NOT NULL
          AND t.id IS NULL
    ");
    return (int) $stmt->fetchColumn();
}

function cradReconcileOrphanResearchGroups(PDO $pdo): array
{
    $protection = cradEnsureResearchGroupHistoryProtection($pdo);
    if (empty($protection['ok'])) {
        return [
            'ok' => false,
            'changed' => false,
            'deleted' => 0,
            'message' => $protection['message'],
        ];
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->query("
            SELECT rg.id
            FROM crad_research_groups rg
            LEFT JOIN crad_title_approvals t ON t.id = rg.title_approval_id
            WHERE rg.title_approval_id IS NOT NULL
              AND t.id IS NULL
            ORDER BY rg.id
            FOR UPDATE
        ");
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if (!$ids) {
            $pdo->commit();
            return [
                'ok' => true,
                'changed' => !empty($protection['changed']),
                'deleted' => 0,
                'message' => 'No orphaned research groups found.',
            ];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $delete = $pdo->prepare("DELETE FROM crad_research_groups WHERE id IN ({$placeholders})");
        $delete->execute($ids);
        $deleted = $delete->rowCount();
        $notificationCleanup = cradCleanupPanelNotificationsForInvalidRegistry($pdo, $ids);
        $pdo->commit();

        return [
            'ok' => true,
            'changed' => true,
            'deleted' => $deleted,
            'message' => 'Removed ' . $deleted . ' orphaned research group(s) by title_approval_id. Cleaned ' . (int) ($notificationCleanup['deleted'] ?? 0) . ' panel notification(s).',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function cradEnsureTitleApprovalResearchGroupCascade(PDO $pdo, bool $reconcileExisting = false): array
{
    $result = [
        'ok' => true,
        'changed' => false,
        'reconciled' => 0,
        'message' => 'Title approval research group FK is ready.',
    ];

    $tables = $pdo->query("
        SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME IN ('crad_title_approvals', 'crad_research_groups')
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach (['crad_title_approvals', 'crad_research_groups'] as $requiredTable) {
        if (!in_array($requiredTable, $tables, true)) {
            return [
                'ok' => false,
                'changed' => false,
                'message' => 'Missing required table: ' . $requiredTable,
            ];
        }
    }

    if (!$pdo->query("SHOW COLUMNS FROM crad_research_groups LIKE 'title_approval_id'")->fetch()) {
        $result['message'] = 'research_groups.title_approval_id is not present; no title approval FK to add.';
        return $result;
    }

    $protection = cradEnsureResearchGroupHistoryProtection($pdo);
    if (empty($protection['ok'])) {
        return [
            'ok' => false,
            'changed' => $result['changed'],
            'reconciled' => 0,
            'message' => $protection['message'],
        ];
    }
    if (!empty($protection['changed'])) {
        $result['changed'] = true;
    }

    if ($reconcileExisting) {
        $cleanup = cradReconcileOrphanResearchGroups($pdo);
        if (empty($cleanup['ok'])) {
            return [
                'ok' => false,
                'changed' => $result['changed'],
                'reconciled' => 0,
                'message' => $cleanup['message'],
            ];
        }
        $result['reconciled'] = (int) ($cleanup['deleted'] ?? 0);
        if (!empty($cleanup['changed'])) {
            $result['changed'] = true;
        }
    }

    $orphans = cradCountOrphanResearchGroups($pdo);
    if ($orphans > 0) {
        return [
            'ok' => false,
            'changed' => $result['changed'],
            'reconciled' => $result['reconciled'],
            'message' => 'Cannot add title approval FK while ' . $orphans . ' orphaned research group(s) exist. Run reconciliation first.',
        ];
    }

    $existing = cradFindForeignKey($pdo, 'crad_research_groups', 'title_approval_id', 'crad_title_approvals', 'id');

    if ($existing) {
        $deleteRule = strtoupper((string) ($existing['DELETE_RULE'] ?? ''));
        $updateRule = strtoupper((string) ($existing['UPDATE_RULE'] ?? ''));
        if ($deleteRule === 'CASCADE' && $updateRule === 'CASCADE') {
            $result['message'] = 'Title approval research group FK already exists.';
            return $result;
        }

        cradDropForeignKey($pdo, 'crad_research_groups', (string) $existing['CONSTRAINT_NAME']);
        $result['changed'] = true;
    }

    $pdo->exec("
        ALTER TABLE crad_research_groups
        ADD CONSTRAINT fk_rg_title_approval
        FOREIGN KEY (title_approval_id)
        REFERENCES crad_title_approvals(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
    ");
    $result['changed'] = true;
    $result['message'] = 'Installed title_approvals.id -> research_groups.title_approval_id ON DELETE CASCADE FK.';
    return $result;
}

function cradCountOrphanResearchCoordinatorAssignments(PDO $pdo): int
{
    if (!$pdo->query("SHOW TABLES LIKE 'crad_title_approvals'")->fetchColumn()
        || !$pdo->query("SHOW TABLES LIKE 'crad_research_coordinator_assignments'")->fetchColumn()) {
        return 0;
    }

    if (!$pdo->query("SHOW COLUMNS FROM crad_research_coordinator_assignments LIKE 'title_approval_id'")->fetch()) {
        return 0;
    }

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM crad_research_coordinator_assignments a
        LEFT JOIN crad_title_approvals t ON t.id = a.title_approval_id
        WHERE a.title_approval_id IS NOT NULL
          AND t.id IS NULL
    ");
    return (int) $stmt->fetchColumn();
}

function cradReconcileOrphanResearchCoordinatorAssignments(PDO $pdo): array
{
    $result = [
        'ok' => true,
        'changed' => false,
        'deleted' => 0,
        'message' => 'No orphaned research coordinator assignments found.',
    ];

    foreach (['crad_title_approvals', 'crad_research_coordinator_assignments'] as $table) {
        if (!$pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn()) {
            return [
                'ok' => false,
                'changed' => false,
                'deleted' => 0,
                'message' => 'Missing required table: ' . $table,
            ];
        }
    }

    if (!$pdo->query("SHOW COLUMNS FROM crad_research_coordinator_assignments LIKE 'title_approval_id'")->fetch()) {
        return [
            'ok' => false,
            'changed' => false,
            'deleted' => 0,
            'message' => 'research_coordinator_assignments.title_approval_id is missing.',
        ];
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->query("
            SELECT a.id
            FROM crad_research_coordinator_assignments a
            LEFT JOIN crad_title_approvals t ON t.id = a.title_approval_id
            WHERE a.title_approval_id IS NOT NULL
              AND t.id IS NULL
            ORDER BY a.id
            FOR UPDATE
        ");
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if (!$ids) {
            $pdo->commit();
            return $result;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $delete = $pdo->prepare("DELETE FROM crad_research_coordinator_assignments WHERE id IN ({$placeholders})");
        $delete->execute($ids);
        $deleted = $delete->rowCount();
        $pdo->commit();

        return [
            'ok' => true,
            'changed' => true,
            'deleted' => $deleted,
            'message' => 'Removed ' . $deleted . ' orphaned research coordinator assignment(s) by title_approval_id.',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function cradEnsureTitleApprovalResearchCoordinatorCascade(PDO $pdo, bool $reconcileExisting = false): array
{
    $result = [
        'ok' => true,
        'changed' => false,
        'reconciled' => 0,
        'message' => 'Title approval research coordinator assignment FK is ready.',
    ];

    foreach (['crad_title_approvals', 'crad_research_coordinator_assignments'] as $table) {
        if (!$pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn()) {
            return [
                'ok' => false,
                'changed' => false,
                'reconciled' => 0,
                'message' => 'Missing required table: ' . $table,
            ];
        }
    }

    $childColumn = $pdo->query("SHOW COLUMNS FROM crad_research_coordinator_assignments LIKE 'title_approval_id'")->fetch();
    $parentColumn = $pdo->query("SHOW COLUMNS FROM crad_title_approvals LIKE 'id'")->fetch();
    if (!$childColumn || !$parentColumn) {
        return [
            'ok' => false,
            'changed' => false,
            'reconciled' => 0,
            'message' => 'Required title approval relationship columns are missing.',
        ];
    }

    $childType = strtolower((string) ($childColumn['Type'] ?? ''));
    $parentType = strtolower((string) ($parentColumn['Type'] ?? ''));
    if ($childType !== $parentType) {
        return [
            'ok' => false,
            'changed' => false,
            'reconciled' => 0,
            'message' => 'Cannot add coordinator assignment FK because title_approval_id type (' . $childType . ') does not match title_approvals.id (' . $parentType . ').',
        ];
    }

    if (!$pdo->query("SHOW INDEX FROM crad_research_coordinator_assignments WHERE Key_name = 'idx_rca_title_approval'")->fetch()) {
        $pdo->exec('ALTER TABLE crad_research_coordinator_assignments ADD KEY idx_rca_title_approval (title_approval_id)');
        $result['changed'] = true;
    }

    if ($reconcileExisting) {
        $cleanup = cradReconcileOrphanResearchCoordinatorAssignments($pdo);
        if (empty($cleanup['ok'])) {
            return [
                'ok' => false,
                'changed' => $result['changed'],
                'reconciled' => 0,
                'message' => $cleanup['message'],
            ];
        }
        $result['reconciled'] = (int) ($cleanup['deleted'] ?? 0);
        if (!empty($cleanup['changed'])) {
            $result['changed'] = true;
        }
    }

    $orphans = cradCountOrphanResearchCoordinatorAssignments($pdo);
    if ($orphans > 0) {
        return [
            'ok' => false,
            'changed' => $result['changed'],
            'reconciled' => $result['reconciled'],
            'message' => 'Cannot add coordinator assignment FK while ' . $orphans . ' orphaned assignment(s) exist. Run reconciliation first.',
        ];
    }

    $existing = cradFindForeignKey($pdo, 'crad_research_coordinator_assignments', 'title_approval_id', 'crad_title_approvals', 'id');
    if ($existing) {
        $deleteRule = strtoupper((string) ($existing['DELETE_RULE'] ?? ''));
        $updateRule = strtoupper((string) ($existing['UPDATE_RULE'] ?? ''));
        if ($deleteRule === 'CASCADE' && $updateRule === 'CASCADE') {
            $suffix = $result['reconciled'] > 0
                ? ' Reconciled ' . $result['reconciled'] . ' existing coordinator assignment(s).'
                : '';
            $result['message'] = 'Title approval research coordinator assignment FK already exists.' . $suffix;
            return $result;
        }

        cradDropForeignKey($pdo, 'crad_research_coordinator_assignments', (string) $existing['CONSTRAINT_NAME']);
        $result['changed'] = true;
    }

    $pdo->exec("
        ALTER TABLE crad_research_coordinator_assignments
        ADD CONSTRAINT fk_rca_title_approval
        FOREIGN KEY (title_approval_id)
        REFERENCES crad_title_approvals(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
    ");
    $result['changed'] = true;

    $suffix = $result['reconciled'] > 0
        ? ' Reconciled ' . $result['reconciled'] . ' existing coordinator assignment(s).'
        : '';
    $result['message'] = 'Installed title_approvals.id -> research_coordinator_assignments.title_approval_id ON DELETE CASCADE FK.' . $suffix;
    return $result;
}

function cradEnsureTitleApprovalAdviserAssignmentConsistency(PDO $pdo, bool $reconcileExisting = false): array
{
    $result = [
        'ok' => true,
        'changed' => false,
        'reconciled' => 0,
        'message' => 'Title approval adviser assignment consistency is ready.',
    ];

    foreach (['crad_title_approvals', 'crad_research_adviser_assignments', 'crad_research_groups'] as $table) {
        $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn();
        if (!$exists) {
            return [
                'ok' => false,
                'changed' => false,
                'reconciled' => 0,
                'message' => 'Missing required table: ' . $table,
            ];
        }
    }

    $relationship = cradEnsureTitleApprovalResearchGroupCascade($pdo, $reconcileExisting);
    if (!empty($relationship['changed'])) {
        $result['changed'] = true;
    }

    $coordinatorRelationship = cradEnsureTitleApprovalResearchCoordinatorCascade($pdo, $reconcileExisting);
    if (empty($coordinatorRelationship['ok'])) {
        return [
            'ok' => false,
            'changed' => $result['changed'],
            'reconciled' => 0,
            'message' => $coordinatorRelationship['message'],
        ];
    }
    if (!empty($coordinatorRelationship['changed'])) {
        $result['changed'] = true;
    }

    $pdo->exec('DROP TRIGGER IF EXISTS trg_title_approvals_after_delete');
    $pdo->exec("
        CREATE TRIGGER trg_title_approvals_after_delete
        AFTER DELETE ON crad_title_approvals
        FOR EACH ROW
        BEGIN
            UPDATE crad_research_adviser_assignments a
               SET a.assignment_status = 'Pending'
             WHERE a.assignment_status = 'Assigned'
               AND (
                    (OLD.proposal_number IS NOT NULL
                     AND OLD.proposal_number <> ''
                     AND a.proposal_number = OLD.proposal_number)
                 OR (a.research_group_id IS NOT NULL
                     AND a.research_group_id IN (
                        SELECT g.id
                        FROM crad_research_groups g
                        WHERE g.title_approval_id = OLD.id
                     ))
                 OR (a.group_number IS NOT NULL
                     AND a.group_number <> ''
                     AND a.group_number IN (
                        SELECT g2.group_number
                        FROM crad_research_groups g2
                        WHERE g2.title_approval_id = OLD.id
                     ))
               );
        END
    ");
    $result['changed'] = true;

    if ($reconcileExisting) {
        $validTitle = cradValidTitleApprovalWhereSql('t');
        $stmt = $pdo->prepare("
            UPDATE crad_research_adviser_assignments a
            LEFT JOIN crad_research_groups g
              ON (a.research_group_id IS NOT NULL AND a.research_group_id = g.id)
              OR (a.group_number IS NOT NULL AND a.group_number <> '' AND a.group_number = g.group_number)
               SET a.assignment_status = 'Pending'
             WHERE a.assignment_status = 'Assigned'
               AND (
                    (
                        g.title_approval_id IS NOT NULL
                        AND NOT EXISTS (
                            SELECT 1
                            FROM crad_title_approvals t
                            WHERE t.id = g.title_approval_id
                              AND {$validTitle}
                        )
                    )
                 OR (
                        a.proposal_number IS NOT NULL
                        AND a.proposal_number <> ''
                        AND a.proposal_number LIKE 'TAP-%'
                        AND NOT EXISTS (
                            SELECT 1
                            FROM crad_title_approvals t
                            WHERE t.proposal_number = a.proposal_number
                              AND {$validTitle}
                        )
                    )
                 OR (
                        g.proposal_number IS NOT NULL
                        AND g.proposal_number <> ''
                        AND g.proposal_number LIKE 'TAP-%'
                        AND NOT EXISTS (
                            SELECT 1
                            FROM crad_title_approvals t
                            WHERE t.proposal_number = g.proposal_number
                              AND {$validTitle}
                        )
                    )
               )
        ");
        $stmt->execute();
        $result['reconciled'] = $stmt->rowCount();
    }

    $suffix = $result['reconciled'] > 0
        ? ' Reconciled ' . $result['reconciled'] . ' existing assignment(s).'
        : '';
    $result['message'] = 'Installed non-destructive title approval delete sync trigger.' . $suffix;
    return $result;
}
