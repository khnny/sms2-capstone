<?php
/**
 * Student Portal Module — Database Configuration
 * Defaults to the unified SMS2 database (DB_NAME). Set STUDENT_PORTAL_DB_NAME
 * only when intentionally using a separate database.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/config/config.php';
require_once ROOT_PATH . '/config/database.php';

if (!defined('STUDENT_PORTAL_DB_HOST')) {
    define('STUDENT_PORTAL_DB_HOST', sms2_env_first(['STUDENT_PORTAL_DB_HOST', 'SMS2_DB_HOST', 'DB_HOST'], DB_HOST));
}
if (!defined('STUDENT_PORTAL_DB_NAME')) {
    define('STUDENT_PORTAL_DB_NAME', sms2_env_first(['STUDENT_PORTAL_DB_NAME'], DB_NAME));
}
if (!defined('STUDENT_PORTAL_DB_USER')) {
    define('STUDENT_PORTAL_DB_USER', sms2_env_first(['STUDENT_PORTAL_DB_USER', 'SMS2_DB_USER', 'DB_USERNAME', 'DB_USER'], DB_USER));
}
if (!defined('STUDENT_PORTAL_DB_PASS')) {
    define('STUDENT_PORTAL_DB_PASS', sms2_env_first(['STUDENT_PORTAL_DB_PASS', 'SMS2_DB_PASS', 'DB_PASSWORD', 'DB_PASS'], DB_PASS));
}
if (!defined('STUDENT_PORTAL_DB_CHARSET')) {
    define('STUDENT_PORTAL_DB_CHARSET', sms2_env_first(['STUDENT_PORTAL_DB_CHARSET', 'SMS2_DB_CHARSET', 'DB_CHARSET'], DB_CHARSET));
}

function getStudentPortalDatabaseConnection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (strcasecmp((string) STUDENT_PORTAL_DB_NAME, (string) DB_NAME) === 0
        && strcasecmp((string) STUDENT_PORTAL_DB_HOST, (string) DB_HOST) === 0
    ) {
        $pdo = getDatabaseConnection();
        return $pdo;
    }

    $dsn = 'mysql:host=' . STUDENT_PORTAL_DB_HOST . ';dbname=' . STUDENT_PORTAL_DB_NAME . ';charset=' . STUDENT_PORTAL_DB_CHARSET;
    try {
        $pdo = new PDO($dsn, STUDENT_PORTAL_DB_USER, STUDENT_PORTAL_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('Student Portal DB connection failed: ' . $e->getMessage());
        throw new RuntimeException('Student Portal database unavailable.');
    }
    return $pdo;
}

function studentPortalDb(): ?PDO
{
    try {
        return getStudentPortalDatabaseConnection();
    } catch (Throwable $e) {
        return null;
    }
}
