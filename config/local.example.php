<?php
/**
 * Optional machine-specific settings.
 *
 * Copy this file to config/local.php only on the computer that needs custom
 * values. Keep config/local.php private if it contains real passwords.
 *
 * One database only: sms2_db (sms_* + crad_* tables). Never use a separate crad_db.
 */

// Optional: Cursor API key for AI document analysis and scheduling helpers.
// Prefer storage/keys/cursor_api_key (gitignored) instead of committing a real key.
// define('CURSOR_API_KEY', '');

define('SMS2_LOCAL_BASE_URL', '/sms2_system');

// Single application database (SMS + CRAD).
define('DB_HOST', 'localhost');
define('DB_NAME', 'sms2_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// All modules share DB_NAME — do not point CRAD at crad_db.
define('CRAD_DB_NAME', DB_NAME);
define('STUDENT_PORTAL_DB_NAME', DB_NAME);
define('REPORTS_DB_NAME', DB_NAME);
define('USERMGMT_DB_NAME', DB_NAME);
