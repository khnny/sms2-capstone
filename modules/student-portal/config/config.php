<?php
/**
 * Student Portal Module — Database Configuration
 * Database: student_portal_db
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';

if (!defined('STUDENT_PORTAL_DB_HOST'))    { define('STUDENT_PORTAL_DB_HOST',    sms2_env('STUDENT_PORTAL_DB_HOST', sms2_env('SMS2_DB_HOST', 'localhost'))); }
if (!defined('STUDENT_PORTAL_DB_NAME'))    { define('STUDENT_PORTAL_DB_NAME',    sms2_env('STUDENT_PORTAL_DB_NAME', 'student_portal_db')); }
if (!defined('STUDENT_PORTAL_DB_USER'))    { define('STUDENT_PORTAL_DB_USER',    sms2_env('STUDENT_PORTAL_DB_USER', sms2_env('SMS2_DB_USER', 'root'))); }
if (!defined('STUDENT_PORTAL_DB_PASS'))    { define('STUDENT_PORTAL_DB_PASS',    sms2_env('STUDENT_PORTAL_DB_PASS', sms2_env('SMS2_DB_PASS', ''))); }
if (!defined('STUDENT_PORTAL_DB_CHARSET')) { define('STUDENT_PORTAL_DB_CHARSET', sms2_env('STUDENT_PORTAL_DB_CHARSET', sms2_env('SMS2_DB_CHARSET', 'utf8mb4'))); }

function getStudentPortalDatabaseConnection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) { return $pdo; }
    $dsn = 'mysql:host=' . STUDENT_PORTAL_DB_HOST . ';dbname=' . STUDENT_PORTAL_DB_NAME . ';charset=' . STUDENT_PORTAL_DB_CHARSET;
    try {
        $pdo = new PDO($dsn, STUDENT_PORTAL_DB_USER, STUDENT_PORTAL_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('Student Portal DB connection failed: ' . $e->getMessage());
        throw new RuntimeException('Student Portal database unavailable. Run modules/student-portal/database/install.php');
    }
    return $pdo;
}

function studentPortalDb(): ?PDO
{
    try { return getStudentPortalDatabaseConnection(); }
    catch (Throwable $e) { return null; }
}
