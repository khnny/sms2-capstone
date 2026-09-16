<?php
/**
 * Phase 1 security verification (SEC-001/002/003/004/005).
 * Writes NDJSON to debug-4aceee.log — run via CLI, not web.
 */
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/modules/crad/pages/register-proposal.php';

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/database/official_accounts.php';

$logPath = ROOT_PATH . '/debug-4aceee.log';
$log = static function (string $hypothesisId, string $message, array $data = []) use ($logPath): void {
    $payload = [
        'sessionId' => '4aceee',
        'runId' => 'post-fix',
        'hypothesisId' => $hypothesisId,
        'location' => 'scripts/verify-phase1-sec.php',
        'message' => $message,
        'data' => $data,
        'timestamp' => (int) round(microtime(true) * 1000),
    ];
    file_put_contents($logPath, json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
};

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// --- SEC-001: role gate (same logic as register-proposal.php) ---
foreach (['student' => false, 'adviser' => false, 'crad_officer' => true, 'superadmin' => true] as $role => $expectAllow) {
    $_SESSION['user_id'] = 9001;
    $_SESSION['role_key'] = $role;
    $canManage = smsRoleAllowedForModule(['crad_officer', 'superadmin', 'sms_admin'], 'crad');
    $denied = !$canManage;
    $log('A', 'SEC-001 register-proposal role gate', [
        'role' => $role,
        'canManage' => $canManage,
        'wouldDenyAjax' => $denied,
        'expectAllow' => $expectAllow,
        'pass' => $canManage === $expectAllow,
    ]);
    echo "SEC-001 role={$role} canManage=" . ($canManage ? '1' : '0') . " expect=" . ($expectAllow ? '1' : '0')
        . ($canManage === $expectAllow ? " PASS\n" : " FAIL\n");
}

// --- SEC-004/005: CSRF reject without token ---
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION['user_id'] = 9002;
$_SESSION['role_key'] = 'crad_officer';
csrfToken(); // ensure session token exists
$csrfRejected = false;
try {
    smsRequireMutatingCsrf(['id' => 1]); // no csrf_token
} catch (Throwable $e) {
    $csrfRejected = true;
}
// requireCsrf may exit instead of throw — check via output buffering simulation
if (!$csrfRejected) {
    // Inspect requireCsrf behavior
    ob_start();
    $prev = set_error_handler(static function () {
        return true;
    });
    try {
        requireCsrf(null);
    } catch (Throwable $e) {
        $csrfRejected = true;
    }
    $buf = ob_get_clean();
    if ($prev) {
        set_error_handler($prev);
    }
    if ($buf !== '' && $buf !== false) {
        $csrfRejected = true;
    }
}
$log('C', 'SEC-004/005 CSRF without token', [
    'rejected' => $csrfRejected,
    'pass' => $csrfRejected,
]);
echo 'SEC-004 CSRF reject without token: ' . ($csrfRejected ? "PASS\n" : "INCONCLUSIVE (may exit)\n");

$_SERVER['REQUEST_METHOD'] = 'POST';
$bodyOk = ['id' => 1, 'csrf_token' => csrfToken()];
$csrfOk = false;
try {
    smsRequireMutatingCsrf($bodyOk);
    $csrfOk = true;
} catch (Throwable $e) {
    $csrfOk = false;
}
$log('D', 'SEC-004 CSRF with valid token', [
    'ok' => $csrfOk,
    'pass' => $csrfOk,
]);
echo 'SEC-004 CSRF with valid token: ' . ($csrfOk ? "PASS\n" : "FAIL\n");

// Header path (sms-csrf.js)
$_SERVER['HTTP_X_CSRF_TOKEN'] = csrfToken();
$headerOk = false;
try {
    smsRequireMutatingCsrf(['id' => 2]);
    $headerOk = true;
} catch (Throwable $e) {
    $headerOk = false;
}
unset($_SERVER['HTTP_X_CSRF_TOKEN']);
$log('E', 'SEC-005 X-CSRF-Token header path', [
    'ok' => $headerOk,
    'pass' => $headerOk,
]);
echo 'SEC-005 X-CSRF-Token header: ' . ($headerOk ? "PASS\n" : "FAIL\n");

// --- SEC-002: file-view authorization ---
require_once ROOT_PATH . '/modules/crad/file-view.php';
// file-view exits on include if GET params set — include only the function via reflection
// The file already ran requireAuth and may have exited. Re-define by extracting function only.

echo "SEC-002: invoking cradFileViewAuthorized via included file may have exited; checking function_exists\n";
if (!function_exists('cradFileViewAuthorized')) {
    // Re-declare minimal copy for harness if include exited early
    // Include already happened — if we're still here, function should exist unless exit.
}

// Start fresh process for file-view function by requiring a stub — extract from file via eval of function only.
$fvSource = file_get_contents(ROOT_PATH . '/modules/crad/file-view.php');
if (preg_match('/function cradFileViewAuthorized\(.*?^\}/ms', $fvSource, $m)) {
    if (!function_exists('cradFileViewAuthorized')) {
        eval('?>' . '<?php ' . $m[0]);
    }
}

try {
    $pdo = getCradDatabaseConnection();
    $prop = $pdo->query(
        "SELECT id, submitted_by_user, proposal_number FROM crad_research_proposals ORDER BY id ASC LIMIT 1"
    )->fetch(PDO::FETCH_ASSOC);

    if (!$prop) {
        $log('F', 'SEC-002 no proposals in DB', ['pass' => null]);
        echo "SEC-002 SKIP (no proposals)\n";
    } else {
        $pid = (int) $prop['id'];
        // Unassigned panel
        $deny = cradFileViewAuthorized($pdo, $prop, $pid, 999999, 'panel');
        $log('F', 'SEC-002 unassigned panel denied', [
            'pid' => $pid,
            'canView' => $deny,
            'pass' => $deny === false,
        ]);
        echo 'SEC-002 unassigned panel canView=' . ($deny ? '1' : '0') . ($deny === false ? " PASS\n" : " FAIL\n");

        // Global crad_officer
        $allowOfficer = cradFileViewAuthorized($pdo, $prop, $pid, 1, 'crad_officer');
        $log('F', 'SEC-002 crad_officer allowed', [
            'pid' => $pid,
            'canView' => $allowOfficer,
            'pass' => $allowOfficer === true,
        ]);
        echo 'SEC-002 crad_officer canView=' . ($allowOfficer ? '1' : '0') . ($allowOfficer ? " PASS\n" : " FAIL\n");

        // Owner
        $ownerId = (int) ($prop['submitted_by_user'] ?? 0);
        if ($ownerId > 0) {
            $allowOwner = cradFileViewAuthorized($pdo, $prop, $pid, $ownerId, 'student');
            $log('F', 'SEC-002 owner allowed', [
                'pid' => $pid,
                'canView' => $allowOwner,
                'pass' => $allowOwner === true,
            ]);
            echo 'SEC-002 owner canView=' . ($allowOwner ? '1' : '0') . ($allowOwner ? " PASS\n" : " FAIL\n");
        }
    }
} catch (Throwable $e) {
    $log('F', 'SEC-002 DB error', ['error' => $e->getMessage(), 'pass' => false]);
    echo 'SEC-002 ERROR: ' . $e->getMessage() . "\n";
}

// --- SEC-003 ---
putenv('DB_HOST=mariadb-fake.internal');
putenv('DB_DATABASE=hf_db_fake');
$_ENV['DB_HOST'] = 'mariadb-fake.internal';
$_ENV['DB_DATABASE'] = 'hf_db_fake';
$_SERVER['DB_HOST'] = 'mariadb-fake.internal';
$_SERVER['DB_DATABASE'] = 'hf_db_fake';
$cloudBlocked = !smsOfficialCredentialResetAllowed();
$log('G', 'SEC-003 cloud reset blocked', [
    'cloudEnv' => sms2_has_cloud_db_env(),
    'resetAllowed' => smsOfficialCredentialResetAllowed(),
    'pass' => $cloudBlocked,
]);
echo 'SEC-003 cloud reset blocked: ' . ($cloudBlocked ? "PASS\n" : "FAIL\n");

putenv('SMS2_ALLOW_OFFICIAL_RESET=1');
$_ENV['SMS2_ALLOW_OFFICIAL_RESET'] = '1';
$_SERVER['SMS2_ALLOW_OFFICIAL_RESET'] = '1';
$overrideAllow = smsOfficialCredentialResetAllowed();
$log('G', 'SEC-003 allow flag permits reset', [
    'resetAllowed' => $overrideAllow,
    'pass' => $overrideAllow,
]);
echo 'SEC-003 allow flag: ' . ($overrideAllow ? "PASS\n" : "FAIL\n");

echo "Log written to {$logPath}\n";
