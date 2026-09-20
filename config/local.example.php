<?php
/**
 * Optional machine-specific settings.
 *
 * Copy this file to config/local.php only on the computer that needs custom
 * values. Keep config/local.php private if it contains real passwords.
 */

// Optional: Cursor API key for AI document analysis and scheduling helpers.
// Prefer storage/keys/cursor_api_key (gitignored) instead of committing a real key.
// define('CURSOR_API_KEY', '');

define('SMS2_LOCAL_BASE_URL', '/sms2_system');

// Main SMS2 database.
define('DB_HOST', 'localhost');
define('DB_NAME', 'sms2_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Module databases — use the SAME name as DB_NAME when CRAD/SMS share one schema
// (HostForge and your current sms2_db layout with crad_* tables).
// Only set a different CRAD_DB_NAME if you intentionally keep a separate local schema.
define('CRAD_DB_NAME', 'sms2_db');
define('STUDENT_PORTAL_DB_NAME', 'sms2_db');
define('REPORTS_DB_NAME', 'sms2_db');
define('USERMGMT_DB_NAME', 'sms2_db');
