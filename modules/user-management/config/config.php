<?php
/**
 * User Management Module — Database Configuration
 * Database: user_management_db
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';

if (!defined('USERMGMT_DB_HOST'))    { define('USERMGMT_DB_HOST',    sms2_env('USERMGMT_DB_HOST', sms2_env('SMS2_DB_HOST', 'localhost'))); }
if (!defined('USERMGMT_DB_NAME'))    { define('USERMGMT_DB_NAME',    sms2_env('USERMGMT_DB_NAME', 'user_management_db')); }
if (!defined('USERMGMT_DB_USER'))    { define('USERMGMT_DB_USER',    sms2_env('USERMGMT_DB_USER', sms2_env('SMS2_DB_USER', 'root'))); }
if (!defined('USERMGMT_DB_PASS'))    { define('USERMGMT_DB_PASS',    sms2_env('USERMGMT_DB_PASS', sms2_env('SMS2_DB_PASS', ''))); }
if (!defined('USERMGMT_DB_CHARSET')) { define('USERMGMT_DB_CHARSET', sms2_env('USERMGMT_DB_CHARSET', sms2_env('SMS2_DB_CHARSET', 'utf8mb4'))); }

function getUserMgmtDatabaseConnection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) { return $pdo; }
    $dsn = 'mysql:host=' . USERMGMT_DB_HOST . ';dbname=' . USERMGMT_DB_NAME . ';charset=' . USERMGMT_DB_CHARSET;
    try {
        $pdo = new PDO($dsn, USERMGMT_DB_USER, USERMGMT_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('User Management DB connection failed: ' . $e->getMessage());
        throw new RuntimeException('User Management database unavailable. Run modules/user-management/database/install.php');
    }
    return $pdo;
}

function userMgmtDb(): ?PDO
{
    try { return getUserMgmtDatabaseConnection(); }
    catch (Throwable $e) { return null; }
}
