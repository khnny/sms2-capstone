<?php
/**
 * HostForge — copy to config/local.php on the server ONLY if you are not
 * using HostForge Environment Variables for DB_* settings.
 *
 * Prefer setting these in the HostForge panel (no password in files):
 *   DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
 *   SMS2_DEPLOY_TOKEN  (optional; for /setup/deploy-db.php)
 *   SMS2_APP_KEY (required to preserve existing encrypted DB secrets)
 *   SMS2_SMTP_PASSWORD / SMS2_SMTP_USERNAME  (Gmail App Password — survives redeploys)
 *   SMS2_TURNSTILE_SITE_KEY / SMS2_TURNSTILE_SECRET_KEY (recommended for live CAPTCHA)
 *
 * Full checklist: database/HOSTFORGE_DEPLOY.txt
 *
 * PLACEHOLDERS ONLY — never commit real HostForge credentials.
 * config/local.php is gitignored.
 */

// Optional: token for setup/deploy-db.php web migrate
// define('SMS2_DEPLOY_TOKEN', 'YOUR_DEPLOY_TOKEN');
// Set SMS2_APP_KEY in HostForge Environment Variables to the exact existing
// key from the old container, persistent key storage, or secure backup.
// Do not generate a new value: it will not decrypt existing sms2enc1 secrets.
// define('SMS2_TURNSTILE_SITE_KEY', 'YOUR_TURNSTILE_SITE_KEY');
// define('SMS2_TURNSTILE_SECRET_KEY', 'YOUR_TURNSTILE_SECRET_KEY');

// Auto-detect BASE_URL when the app is at the web root.
// define('BASE_URL', '');

define('DB_CONNECTION', 'mysql');
define('DB_HOST', 'YOUR_HOSTFORGE_INTERNAL_HOST');
define('DB_PORT', '3306');
define('DB_NAME', 'hf_db_kn5x0oao');
define('DB_USER', 'YOUR_USERNAME');
define('DB_PASS', 'YOUR_PASSWORD');
define('DB_CHARSET', 'utf8mb4');

// Single database: SMS2 + CRAD share the HostForge-provisioned schema.
define('CRAD_DB_NAME', 'hf_db_kn5x0oao');
define('STUDENT_PORTAL_DB_NAME', 'hf_db_kn5x0oao');
define('REPORTS_DB_NAME', 'hf_db_kn5x0oao');
define('USERMGMT_DB_NAME', 'hf_db_kn5x0oao');

// Optional SMTP (prefer HostForge Environment Variables instead):
// define('SMS2_SMTP_USERNAME', 'your.account@gmail.com');
// define('SMS2_SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');
