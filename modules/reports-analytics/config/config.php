<?php
/**
 * Reports & Analytics Module — Database Configuration
 * Database: reports_db
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';

if (!defined('REPORTS_DB_HOST'))    { define('REPORTS_DB_HOST',    sms2_env('REPORTS_DB_HOST', sms2_env('SMS2_DB_HOST', 'localhost'))); }
if (!defined('REPORTS_DB_NAME'))    { define('REPORTS_DB_NAME',    sms2_env('REPORTS_DB_NAME', 'reports_db')); }
if (!defined('REPORTS_DB_USER'))    { define('REPORTS_DB_USER',    sms2_env('REPORTS_DB_USER', sms2_env('SMS2_DB_USER', 'root'))); }
if (!defined('REPORTS_DB_PASS'))    { define('REPORTS_DB_PASS',    sms2_env('REPORTS_DB_PASS', sms2_env('SMS2_DB_PASS', ''))); }
if (!defined('REPORTS_DB_CHARSET')) { define('REPORTS_DB_CHARSET', sms2_env('REPORTS_DB_CHARSET', sms2_env('SMS2_DB_CHARSET', 'utf8mb4'))); }

function getReportsDatabaseConnection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) { return $pdo; }
    $dsn = 'mysql:host=' . REPORTS_DB_HOST . ';dbname=' . REPORTS_DB_NAME . ';charset=' . REPORTS_DB_CHARSET;
    try {
        $pdo = new PDO($dsn, REPORTS_DB_USER, REPORTS_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('Reports DB connection failed: ' . $e->getMessage());
        throw new RuntimeException('Reports database unavailable. Run modules/reports-analytics/database/install.php');
    }
    return $pdo;
}

function reportsDb(): ?PDO
{
    try { return getReportsDatabaseConnection(); }
    catch (Throwable $e) { return null; }
}
