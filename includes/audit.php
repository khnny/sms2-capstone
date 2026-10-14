<?php
/**
 * SMS 2 - Activity / audit logging
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

/**
 * Physical activity-log table (quoted for SQL).
 */
function smsActivityLogTableSql(): string
{
    return sms2_quote_table(sms2_table('activity_logs'));
}

/**
 * Last activity-log failure message for Super Admin diagnostics.
 */
function smsActivityLogLastError(?string $set = null): ?string
{
    static $last = null;
    if ($set !== null) {
        $last = $set === '' ? null : $set;
    }
    return $last;
}

/**
 * HostForge / partial dumps sometimes create id without AUTO_INCREMENT.
 * Repair when the app DB user has ALTER privilege.
 */
function smsActivityLogEnsureIdAutoIncrement(PDO $pdo): bool
{
    static $ensured = null;
    if ($ensured !== null) {
        return $ensured;
    }

    $tableSql = smsActivityLogTableSql();

    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . $tableSql . " LIKE 'id'");
        $col = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if (!is_array($col)) {
            smsActivityLogLastError('sms2_activity_logs.id column is missing.');
            return $ensured = false;
        }

        $extra = strtolower((string) ($col['Extra'] ?? ''));
        if (str_contains($extra, 'auto_increment')) {
            return $ensured = true;
        }

        // PRIMARY KEY is required for AUTO_INCREMENT on MariaDB/MySQL.
        $keyStmt = $pdo->query('SHOW KEYS FROM ' . $tableSql . " WHERE Key_name = 'PRIMARY'");
        $hasPk = $keyStmt && (bool) $keyStmt->fetch(PDO::FETCH_ASSOC);
        if (!$hasPk) {
            $pdo->exec('ALTER TABLE ' . $tableSql . ' ADD PRIMARY KEY (`id`)');
        }

        $pdo->exec(
            'ALTER TABLE ' . $tableSql . ' MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT'
        );
        return $ensured = true;
    } catch (Throwable $e) {
        $msg = 'sms2_activity_logs.id is not AUTO_INCREMENT and could not be repaired: ' . $e->getMessage()
            . ' Run: ALTER TABLE sms2_activity_logs MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;';
        error_log('SMS2 ' . $msg);
        smsActivityLogLastError($msg);
        return $ensured = false;
    }
}

/**
 * Make the audit trail available on installations that predate the prefixed
 * schema migration. Prefer detecting an existing table so HostForge app DB
 * users without CREATE privilege can still read/write logs.
 */
function smsActivityLogTableReady(?PDO $pdo = null): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }

    $pdo = $pdo ?: db();
    if (!$pdo) {
        smsActivityLogLastError('Database connection unavailable.');
        return $ready = false;
    }

    $tableSql = smsActivityLogTableSql();

    // 1) Existing table + SELECT privilege is enough to proceed.
    try {
        $pdo->query('SELECT 1 FROM ' . $tableSql . ' LIMIT 1');
        if (!smsActivityLogEnsureIdAutoIncrement($pdo)) {
            return $ready = false;
        }
        smsActivityLogLastError('');
        return $ready = true;
    } catch (Throwable $e) {
        smsActivityLogLastError('Table check failed: ' . $e->getMessage());
    }

    // 2) Create only when missing (needs CREATE). Migrations remain preferred.
    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . $tableSql . ' (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` int(10) UNSIGNED DEFAULT NULL,
                `user_name` varchar(150) DEFAULT NULL,
                `role_key` varchar(40) DEFAULT NULL,
                `action` varchar(40) NOT NULL,
                `module_key` varchar(60) DEFAULT NULL,
                `detail` varchar(500) NOT NULL,
                `ip_address` varchar(45) DEFAULT NULL,
                `user_agent` varchar(255) DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `idx_logs_user` (`user_id`),
                KEY `idx_logs_action` (`action`),
                KEY `idx_logs_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $pdo->query('SELECT 1 FROM ' . $tableSql . ' LIMIT 1');
        if (!smsActivityLogEnsureIdAutoIncrement($pdo)) {
            return $ready = false;
        }
        smsActivityLogLastError('');
        return $ready = true;
    } catch (Throwable $e) {
        $msg = 'SMS2 activity log table unavailable: ' . $e->getMessage();
        error_log($msg);
        smsActivityLogLastError($msg);
        return $ready = false;
    }
}

/**
 * Write an audit log entry.
 *
 * @return bool True when the row was inserted.
 */
function logActivity(
    string $action,
    string $detail,
    ?string $moduleKey = 'System',
    ?int $userId = null,
    ?string $userName = null,
    ?string $roleKey = null,
    bool $allowSessionFallback = true
): bool {
    $pdo = db();
    if (!$pdo || !smsActivityLogTableReady($pdo)) {
        if (smsActivityLogLastError() === null) {
            smsActivityLogLastError('Activity log table is not ready.');
        }
        return false;
    }

    if ($allowSessionFallback) {
        if ($userId === null && !empty($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
        }
        if ($userName === null) {
            $userName = $_SESSION['user_name'] ?? null;
        }
        if ($roleKey === null) {
            $roleKey = $_SESSION['user_role_key'] ?? null;
        }
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO ' . smsActivityLogTableSql() . '
                (user_id, user_name, role_key, action, module_key, detail, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $stmt->execute([
            $userId,
            $userName !== null ? substr($userName, 0, 150) : null,
            $roleKey !== null ? substr($roleKey, 0, 40) : null,
            substr($action, 0, 40),
            $moduleKey !== null ? substr($moduleKey, 0, 60) : null,
            substr($detail, 0, 500),
            smsClientIp(),
            $ua !== '' ? $ua : null,
        ]);
        smsActivityLogLastError('');
        return true;
    } catch (Throwable $e) {
        $msg = 'SMS2 audit log failed: ' . $e->getMessage();
        error_log($msg);
        smsActivityLogLastError($msg);
        return false;
    }
}
