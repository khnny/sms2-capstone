<?php
/**
 * InfinityFree — copy to config/local.php on the server and fill real values.
 * NEVER commit real passwords or deploy tokens.
 */

// define('SMS2_DEPLOY_TOKEN', 'change-me-to-a-long-random-secret');

// Auto-detect URL (blank = htdocs root)
// define('BASE_URL', '');

define('DB_HOST', 'sqlXXXX.infinityfree.com');
define('DB_PORT', '3306');
define('DB_NAME', 'if0_XXXX_sms2');
define('DB_USER', 'if0_XXXX');
define('DB_PASS', 'REPLACE_WITH_PANEL_PASSWORD');
define('DB_CHARSET', 'utf8mb4');

// Free plan: often one shared database for all modules
define('CRAD_DB_NAME', 'if0_XXXX_sms2');
define('STUDENT_PORTAL_DB_NAME', 'if0_XXXX_sms2');
define('REPORTS_DB_NAME', 'if0_XXXX_sms2');
define('USERMGMT_DB_NAME', 'if0_XXXX_sms2');
