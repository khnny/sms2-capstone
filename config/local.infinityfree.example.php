<?php
/**
 * InfinityFree — upload as config/local.php on the server if needed.
 * Values from vPanel → MySQL Databases.
 *
 * PLACEHOLDERS ONLY — never commit real passwords.
 * Prefer a single shared database (free plan usually allows one DB).
 */

// define('SMS2_DEPLOY_TOKEN', 'YOUR_DEPLOY_TOKEN');
// For a live CAPTCHA, use keys whose hostname list includes this site domain.
// define('SMS2_TURNSTILE_SITE_KEY', 'YOUR_TURNSTILE_SITE_KEY');
// define('SMS2_TURNSTILE_SECRET_KEY', 'YOUR_TURNSTILE_SECRET_KEY');

// Optional: Fixed 32-byte or base64 encryption key for secrets (AES-256).
// define('SMS2_APP_KEY', '');

// Auto-detect URL (blank = htdocs root)
// define('BASE_URL', '');

define('DB_CONNECTION', 'mysql');
define('DB_HOST', 'YOUR_INFINITYFREE_SQL_HOST');
define('DB_PORT', '3306');
define('DB_NAME', 'YOUR_DATABASE_NAME');
define('DB_USER', 'YOUR_USERNAME');
define('DB_PASS', 'YOUR_PASSWORD');
define('DB_CHARSET', 'utf8mb4');

// Single database for all modules
define('CRAD_DB_NAME', 'YOUR_DATABASE_NAME');
define('STUDENT_PORTAL_DB_NAME', 'YOUR_DATABASE_NAME');
define('REPORTS_DB_NAME', 'YOUR_DATABASE_NAME');
define('USERMGMT_DB_NAME', 'YOUR_DATABASE_NAME');
