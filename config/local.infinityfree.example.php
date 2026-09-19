<?php
/**
 * InfinityFree — i-upload bilang config/local.php sa server (htdocs/config/local.php)
 * Values mula sa vPanel → MySQL Databases
 */

define('SMS2_DEPLOY_TOKEN', 'bcp-sms2-deploy-2026');

// Auto-detect URL (blank = htdocs root)
// define('BASE_URL', '');

define('DB_HOST', 'sql211.infinityfree.com');
define('DB_PORT', '3306');
define('DB_NAME', 'if0_42794375_sms2');
define('DB_USER', 'if0_42794375');
define('DB_PASS', 'HVfvZIn3gF8RfyR');
define('DB_CHARSET', 'utf8mb4');

// Free plan: iisa lang ang database para sa lahat ng module
define('CRAD_DB_NAME', 'if0_42794375_sms2');
define('STUDENT_PORTAL_DB_NAME', 'if0_42794375_sms2');
define('REPORTS_DB_NAME', 'if0_42794375_sms2');
define('USERMGMT_DB_NAME', 'if0_42794375_sms2');
