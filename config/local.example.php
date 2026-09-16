<?php
/**
 * Optional machine-specific settings.
 *
 * Copy this file to config/local.php only on the computer that needs custom
 * values. Keep config/local.php private if it contains real passwords.
 */

// Optional: Cursor API key for future AI enhancements (do not commit real keys).
// define('CURSOR_API_KEY', '');

define('SMS2_LOCAL_BASE_URL', '/sms2_system');

// Main SMS2 database (sms_* core tables + crad_* when using unified dump).
define('DB_HOST', 'localhost');
define('DB_NAME', 'sms2_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// CRAD shares the main database by default (import database/sms2_db.sql once).
// Only set a different name if you intentionally keep a separate crad_db.
define('CRAD_DB_NAME', 'sms2_db');

// Optional module databases. These default to the main DB host/user/password.
define('STUDENT_PORTAL_DB_NAME', 'student_portal_db');
define('REPORTS_DB_NAME', 'reports_db');
define('USERMGMT_DB_NAME', 'user_management_db');
