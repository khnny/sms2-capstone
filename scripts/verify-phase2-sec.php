<?php
/**
 * SMS 2 — Phase 2–5 smoke checks (TEST-001).
 * CLI: C:\xampp\php\php.exe scripts/verify-phase2-sec.php
 */
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/scripts/verify-phase2-sec.php';

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/schema-ensure.php';
require_once ROOT_PATH . '/database/official_accounts.php';
require_once ROOT_PATH . '/modules/crad/includes/research-coordinator-assignment-page.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pass = 0;
$fail = 0;
$check = static function (string $id, bool $ok, string $detail = '') use (&$pass, &$fail): void {
    if ($ok) {
        $pass++;
        echo "PASS {$id}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
        return;
    }
    $fail++;
    echo "FAIL {$id}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
};

echo "=== Phase 2–5 smoke ===\n";

// DB-001: migrate-lib source contains sms_users check
$migrateLib = (string) file_get_contents(ROOT_PATH . '/database/migrate-lib.php');
$check('DB-001', str_contains($migrateLib, "in_array('sms_users'"), 'migrate-lib checks sms_users');

// DB-002: install uses migrate-lib
$install = (string) file_get_contents(ROOT_PATH . '/database/install.php');
$check('DB-002', str_contains($install, 'sms2MigrateApplySqlFile'), 'install.php uses migrate-lib');

// CFG-001: module configs default to DB_NAME
foreach ([
    'modules/user-management/config/config.php' => 'USERMGMT_DB_NAME',
    'modules/student-portal/config/config.php' => 'STUDENT_PORTAL_DB_NAME',
    'modules/reports-analytics/config/config.php' => 'REPORTS_DB_NAME',
] as $path => $const) {
    $src = (string) file_get_contents(ROOT_PATH . '/' . $path);
    $check('CFG-001:' . $const, str_contains($src, 'sms2_env_first') && str_contains($src, 'DB_NAME'), $path);
}

// WF-001: legacy path gated
$check('WF-001', function_exists('cradAllowLegacyRegisteredAssignment')
    && cradAllowLegacyRegisteredAssignment() === false, 'legacy assign off by default');

// SEC-006: dual throttle keys
$ipKey = smsLoginThrottleIpKey();
$userKey = smsLoginThrottleKey('demo@example.com');
$keys = smsLoginThrottleKeys('demo@example.com');
$check('SEC-006', $ipKey !== $userKey && count($keys) === 2, 'IP and IP+user keys differ');

// SEC-003: cloud reset blocked when cloud env simulated
putenv('DB_HOST=mariadb-fake.internal');
putenv('DB_DATABASE=hf_db_fake');
$_ENV['DB_HOST'] = 'mariadb-fake.internal';
$_ENV['DB_DATABASE'] = 'hf_db_fake';
$_SERVER['DB_HOST'] = 'mariadb-fake.internal';
$_SERVER['DB_DATABASE'] = 'hf_db_fake';
putenv('SMS2_ALLOW_OFFICIAL_RESET');
unset($_ENV['SMS2_ALLOW_OFFICIAL_RESET'], $_SERVER['SMS2_ALLOW_OFFICIAL_RESET']);
$check('SEC-003', !smsOfficialCredentialResetAllowed(), 'cloud official reset blocked');

// API-001: send-to-adviser source fails closed
$sendSrc = (string) file_get_contents(ROOT_PATH . '/modules/crad/api/send-to-adviser.php');
$check('API-001', str_contains($sendSrc, 'db_unavailable') && !str_contains($sendSrc, 'Non-fatal — proceed'), 'fail-closed adviser lookup');

// SEC-009: notifications CSRF
$notif = (string) file_get_contents(ROOT_PATH . '/api/notifications.php');
$check('SEC-009', str_contains($notif, 'smsRequireMutatingCsrf'), 'notifications CSRF');

// SEC-010: HSTS
$sec = (string) file_get_contents(ROOT_PATH . '/includes/security.php');
$check('SEC-010', str_contains($sec, 'Strict-Transport-Security'), 'HSTS header');

// QA-002: shared schema ensure + skip flag
$schema = (string) file_get_contents(ROOT_PATH . '/modules/crad/includes/schema-ensure.php');
$check('QA-002', str_contains($schema, 'SMS2_SKIP_RUNTIME_SCHEMA') && function_exists('cradEnsureTitleApprovalColumns'), 'shared schema ensure');

// PERF-001: grant helpers have LIMIT
$grants = (string) file_get_contents(ROOT_PATH . '/modules/crad/includes/grant-helpers.php');
$check('PERF-001', str_contains($grants, 'LIMIT {$limit}') || str_contains($grants, 'LIMIT \' . $limit'), 'grant lists capped');

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
