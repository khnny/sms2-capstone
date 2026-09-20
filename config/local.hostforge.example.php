<?php
/**
 * HostForge — copy to config/local.php on the server.
 *
 * One physical database only (often named hf_db_* in the panel). That database
 * holds both sms_* and crad_* tables from database/sms2_db.sql.
 * Do NOT create crad_db. Do NOT use unreachable mariadb-*.internal hosts unless
 * they actually resolve inside your HostForge runtime.
 *
 * Paste the host/user/password/database name from the HostForge MySQL panel.
 * Never commit real passwords.
 */

declare(strict_types=1);

// define('BASE_URL', '');

define('DB_HOST', 'PASTE_WORKING_MYSQL_HOST');
define('DB_PORT', '3306');
define('DB_NAME', 'PASTE_HOSTFORGE_DATABASE_NAME'); // e.g. hf_db_xxxxx — one DB for SMS+CRAD
define('DB_USER', 'PASTE_WORKING_MYSQL_USER');
define('DB_PASS', 'PASTE_WORKING_MYSQL_PASSWORD');
define('DB_CHARSET', 'utf8mb4');

define('CRAD_DB_HOST', DB_HOST);
define('CRAD_DB_PORT', DB_PORT);
define('CRAD_DB_NAME', DB_NAME);
define('CRAD_DB_USER', DB_USER);
define('CRAD_DB_PASS', DB_PASS);
define('CRAD_DB_CHARSET', DB_CHARSET);

define('STUDENT_PORTAL_DB_NAME', DB_NAME);
define('REPORTS_DB_NAME', DB_NAME);
define('USERMGMT_DB_NAME', DB_NAME);
