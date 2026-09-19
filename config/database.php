<?php
/**
 * SMS 2 - Database Configuration (Phase 2)
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!defined('DB_HOST')) {
    define('DB_HOST', sms2_env_first(['SMS2_DB_HOST', 'DB_HOST', 'MYSQL_HOST', 'MARIADB_HOST'], 'localhost'));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', sms2_env_first(['SMS2_DB_PORT', 'DB_PORT', 'MYSQL_PORT', 'MARIADB_PORT'], '3306'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', sms2_env_first(['SMS2_DB_NAME', 'DB_DATABASE', 'DB_NAME', 'MYSQL_DATABASE', 'MARIADB_DATABASE'], 'sms2_db'));
}
if (!defined('DB_USER')) {
    define('DB_USER', sms2_env_first(['SMS2_DB_USER', 'DB_USERNAME', 'DB_USER', 'MYSQL_USER', 'MARIADB_USER'], 'root'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', sms2_env_first(['SMS2_DB_PASS', 'DB_PASSWORD', 'DB_PASS', 'MYSQL_PASSWORD', 'MARIADB_PASSWORD'], ''));
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
    } catch (PDOException $e) {
        error_log('SMS2 DB connection failed: ' . $e->getMessage());
        throw new RuntimeException(
            'Database unavailable. Run database/install.php or start MySQL in XAMPP.'
        );
    }

    return $pdo;
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
