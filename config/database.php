<?php
/**
 * SMS 2 - Database Configuration (Phase 2)
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!function_exists('sms2_database_url_config')) {
    /**
     * @return array{host:string,port:string,name:string,user:string,pass:string}|null
     */
    function sms2_database_url_config(): ?array
    {
        $url = sms2_env_first(['DATABASE_URL', 'SMS2_DATABASE_URL']);
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['mysql', 'mariadb'], true)) {
            return null;
        }

        $database = ltrim((string) ($parts['path'] ?? ''), '/');
        if ($database === '') {
            return null;
        }

        return [
            'host' => (string) ($parts['host'] ?? 'localhost'),
            'port' => (string) ($parts['port'] ?? '3306'),
            'name' => $database,
            'user' => rawurldecode((string) ($parts['user'] ?? '')),
            'pass' => rawurldecode((string) ($parts['pass'] ?? '')),
        ];
    }
}

$dbUrl = sms2_database_url_config();

if (!defined('DB_HOST')) {
    define('DB_HOST', sms2_env_first(['SMS2_DB_HOST', 'DB_HOST', 'MYSQL_HOST', 'MARIADB_HOST'], $dbUrl['host'] ?? 'localhost'));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', sms2_env_first(['SMS2_DB_PORT', 'DB_PORT', 'MYSQL_PORT', 'MARIADB_PORT'], $dbUrl['port'] ?? '3306'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', sms2_env_first(['SMS2_DB_NAME', 'DB_DATABASE', 'DB_NAME', 'MYSQL_DATABASE', 'MARIADB_DATABASE'], $dbUrl['name'] ?? 'sms2_db'));
}
if (!defined('DB_USER')) {
    define('DB_USER', sms2_env_first(['SMS2_DB_USER', 'DB_USERNAME', 'DB_USER', 'MYSQL_USER', 'MARIADB_USER'], $dbUrl['user'] ?? 'root'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', sms2_env_first(['SMS2_DB_PASS', 'DB_PASSWORD', 'DB_PASS', 'MYSQL_PASSWORD', 'MARIADB_PASSWORD'], $dbUrl['pass'] ?? ''));
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', sms2_env_first(['SMS2_DB_CHARSET', 'DB_CHARSET'], 'utf8mb4'));
}
if (!defined('DB_CONNECTION')) {
    define('DB_CONNECTION', strtolower((string) sms2_env_first(['SMS2_DB_CONNECTION', 'DB_CONNECTION'], 'mysql')));
}

/**
 * Shared PDO connection (singleton).
 *
 * @throws RuntimeException when connection fails
 */
function getDatabaseConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!in_array(DB_CONNECTION, ['mysql', 'mariadb'], true)) {
        throw new RuntimeException(
            'Unsupported database connection "' . DB_CONNECTION . '". Select MySQL/MariaDB on HostForge for SMS 2.'
        );
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $GLOBALS['sms2_db_connect_error'] = '';
    } catch (PDOException $e) {
        $GLOBALS['sms2_db_connect_error'] = $e->getMessage();
        error_log('SMS2 DB connection failed: ' . $e->getMessage());
        throw new RuntimeException(
            'Database unavailable (' . DB_HOST . ':' . DB_PORT . '/' . DB_NAME . '). '
            . 'Check HostForge Environment Variables or open /setup/health.php.'
        );
    }

    return $pdo;
}

function sms2_db_last_connect_error(): string
{
    return (string) ($GLOBALS['sms2_db_connect_error'] ?? '');
}

/**
 * Safe summary for deploy troubleshooting (no password).
 *
 * @return array{cloud_env:bool,host:string,port:string,database:string,user:string,connection:string,error:string}
 */
function sms2_db_connection_summary(): array
{
    return [
        'cloud_env' => function_exists('sms2_has_cloud_db_env') && sms2_has_cloud_db_env(),
        'host' => defined('DB_HOST') ? (string) DB_HOST : '',
        'port' => defined('DB_PORT') ? (string) DB_PORT : '',
        'database' => defined('DB_NAME') ? (string) DB_NAME : '',
        'user' => defined('DB_USER') ? (string) DB_USER : '',
        'connection' => defined('DB_CONNECTION') ? (string) DB_CONNECTION : '',
        'error' => sms2_db_last_connect_error(),
    ];
}

/**
 * Safe helper — returns null instead of throwing (for optional features).
 */
function db(): ?PDO
{
    try {
        return getDatabaseConnection();
    } catch (Throwable $e) {
        return null;
    }
}

function smsUsersTableExists(?PDO $pdo): bool
{
    if (!$pdo instanceof PDO) {
        return false;
    }

    try {
        return (bool) $pdo->query("SHOW TABLES LIKE 'sms_users'")->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Match child user_id columns to users.id so MariaDB foreign keys can be created.
 */
function smsUsersIdSqlType(?PDO $pdo): string
{
    if (!$pdo instanceof PDO) {
        return 'INT UNSIGNED';
    }

    try {
        $row = $pdo->query("SHOW COLUMNS FROM sms_users LIKE 'id'")->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row) || empty($row['Type'])) {
            return 'INT UNSIGNED';
        }

        $type = strtolower((string) $row['Type']);
        $unsigned = str_contains($type, 'unsigned') ? ' UNSIGNED' : '';
        $map = [
            'bigint' => 'BIGINT',
            'mediumint' => 'MEDIUMINT',
            'smallint' => 'SMALLINT',
            'tinyint' => 'TINYINT',
            'int' => 'INT',
        ];
        foreach ($map as $prefix => $sql) {
            if (str_starts_with($type, $prefix)) {
                return $sql . $unsigned;
            }
        }
    } catch (Throwable $e) {
        // users table missing on a fresh HostForge database
    }

    return 'INT UNSIGNED';
}

/**
 * Create a table, retrying without FOREIGN KEY clauses if MariaDB rejects them (errno 150).
 */
function smsCreateTableIfNeeded(PDO $pdo, string $sql): void
{
    try {
        $pdo->exec($sql);
        return;
    } catch (PDOException $first) {
        $message = $first->getMessage();
        $isFkError = str_contains($message, 'errno: 150')
            || str_contains($message, 'Foreign key constraint is incorrectly formed')
            || str_contains($message, 'Cannot add foreign key constraint');
        if (!$isFkError) {
            throw $first;
        }

        $stripped = preg_replace(
            '/,\s*CONSTRAINT\s+`?[a-zA-Z0-9_]+`?\s+FOREIGN KEY\s*\([^)]+\)\s*REFERENCES\s+`?[a-zA-Z0-9_]+`?\s*\([^)]+\)(?:\s+ON\s+DELETE\s+(?:CASCADE|SET NULL|RESTRICT|NO ACTION))?(?:\s+ON\s+UPDATE\s+(?:CASCADE|SET NULL|RESTRICT|NO ACTION))?/i',
            '',
            $sql
        );
        if (!is_string($stripped) || $stripped === $sql) {
            throw $first;
        }

        $pdo->exec($stripped);
    }
}
