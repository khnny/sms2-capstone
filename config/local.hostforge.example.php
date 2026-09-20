<?php
/**
 * Production / HostForge — copy to config/local.php on the server.
 *
 * IMPORTANT:
 * HostForge may inject environment variables such as:
 *   DB_HOST=mariadb-xxxxx.internal
 *   DB_NAME=hf_db_xxxxx
 * Those *.internal hostnames often fail DNS (getaddrinfo / Name or service not known)
 * and hf_db_* is NOT the database name this project uses for CRAD tables.
 *
 * This project keeps CRAD tables (crad_*) inside sms2_db.
 * config/local.php constants win over platform env for the main app, and CRAD
 * reuses that same connection via getDatabaseConnection().
 *
 * Fill in the SAME host/user/password that already work for sms2_db in your
 * hosting panel / phpMyAdmin. Do NOT invent values. Do NOT commit real passwords.
 */

declare(strict_types=1);

// Leave BASE_URL unset to auto-detect, or set your public app path.
// define('BASE_URL', '');

define('DB_HOST', 'PASTE_WORKING_MYSQL_HOST_FOR_sms2_db');
define('DB_PORT', '3306');
define('DB_NAME', 'sms2_db');
define('DB_USER', 'PASTE_WORKING_MYSQL_USER_FOR_sms2_db');
define('DB_PASS', 'PASTE_WORKING_MYSQL_PASSWORD');
define('DB_CHARSET', 'utf8mb4');

// CRAD must use the same database — do not set crad_db or hf_db_*.
define('CRAD_DB_HOST', DB_HOST);
define('CRAD_DB_PORT', DB_PORT);
define('CRAD_DB_NAME', DB_NAME);
define('CRAD_DB_USER', DB_USER);
define('CRAD_DB_PASS', DB_PASS);
define('CRAD_DB_CHARSET', DB_CHARSET);

define('STUDENT_PORTAL_DB_NAME', DB_NAME);
define('REPORTS_DB_NAME', DB_NAME);
define('USERMGMT_DB_NAME', DB_NAME);
