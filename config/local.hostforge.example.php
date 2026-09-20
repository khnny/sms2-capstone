<?php
/**
 * HostForge — copy to config/local.php on the server ONLY if the panel does not
 * already inject DB_* via environment variables.
 *
 * HostForge uses ONE MariaDB database for SMS + CRAD. Tables are prefixed:
 *   sms_users, crad_title_approvals, crad_chapter_submissions, ...
 *
 * Do NOT create a separate crad_db database. Do NOT set CRAD_DB_NAME to crad_db.
 *
 * Replace the placeholder values with the exact host / database / user from
 * the HostForge MySQL panel. Never commit real passwords.
 */

declare(strict_types=1);

// Leave BASE_URL unset to auto-detect from the request path.
// define('BASE_URL', '');

define('DB_HOST', 'YOUR_HOSTFORGE_MYSQL_HOST');
define('DB_PORT', '3306');
define('DB_NAME', 'YOUR_HOSTFORGE_DATABASE_NAME');
define('DB_USER', 'YOUR_HOSTFORGE_DATABASE_USER');
define('DB_PASS', 'YOUR_HOSTFORGE_DATABASE_PASSWORD');
define('DB_CHARSET', 'utf8mb4');

// Same database as DB_NAME — required on HostForge (shared / centralized DB).
define('CRAD_DB_HOST', DB_HOST);
define('CRAD_DB_PORT', DB_PORT);
define('CRAD_DB_NAME', DB_NAME);
define('CRAD_DB_USER', DB_USER);
define('CRAD_DB_PASS', DB_PASS);
define('CRAD_DB_CHARSET', DB_CHARSET);

define('STUDENT_PORTAL_DB_NAME', DB_NAME);
define('REPORTS_DB_NAME', DB_NAME);
define('USERMGMT_DB_NAME', DB_NAME);
