<?php
/**
 * Phase 1 security verification (SEC-001/002/003/004/005).
 * Writes NDJSON to debug-4aceee.log — CLI only.
 */
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/scripts/verify-phase1-sec.php';

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/database/official_accounts.php';

$logPath = ROOT_PATH . '/debug-4aceee.log';
@unlink($logPath);

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

echo "=== Phase 1 SEC verification ===\n";

// --- SEC-001 ---
foreach (['student' => false, 'adviser' => false, 'crad_officer' => true, 'superadmin' => true] as $role => $expectAllow) {
    $_SESSION['user_id'] = 9001;
    $_SESSION['role_key'] = $role;
    $canManage = smsRoleAllowedForModule(['crad_officer', 'superadmin', 'sms_admin'], 'crad');
    $pass = $canManage === $expectAllow;
    $log('A', 'SEC-001 register-proposal role gate', [
        'role' => $role,
        'canManage' => $canManage,
        'wouldDenyAjax' => !$canManage,
        'expectAllow' => $expectAllow,
        'pass' => $pass,
    ]);
    echo ($pass ? 'PASS' : 'FAIL') . " SEC-001 role={$role} canManage=" . ($canManage ? '1' : '0') . "\n";
}

// --- SEC-004/005 CSRF ---
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION['user_id'] = 9002;
$_SESSION['role_key'] = 'crad_officer';
$token = csrfToken();

$bodyOk = ['id' => 1, 'csrf_token' => $token];
$csrfOk = false;
try {
    smsRequireMutatingCsrf($bodyOk);
    $csrfOk = true;
} catch (Throwable $e) {
    $csrfOk = false;
}
$log('D', 'SEC-004 CSRF with valid body token', ['ok' => $csrfOk, 'pass' => $csrfOk]);
echo ($csrfOk ? 'PASS' : 'FAIL') . " SEC-004 CSRF body token\n";

$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
$headerOk = false;
try {
    smsRequireMutatingCsrf(['id' => 2]);
    $headerOk = true;
} catch (Throwable $e) {
    $headerOk = false;
}
unset($_SERVER['HTTP_X_CSRF_TOKEN']);
$log('E', 'SEC-005 X-CSRF-Token header path', ['ok' => $headerOk, 'pass' => $headerOk]);
echo ($headerOk ? 'PASS' : 'FAIL') . " SEC-005 X-CSRF-Token header\n";

// Missing token should fail closed (requireCsrf exits with 403 JSON/HTML)
$csrfMissingRejected = false;
$exitCode = null;
$php = PHP_BINARY;
$tmp = ROOT_PATH . '/scripts/_csrf_missing_probe.php';
file_put_contents($tmp, <<<'PHP'
<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost';
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . '/config/session.php';
require_once ROOT_PATH . '/includes/security.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
csrfToken();
try {
    smsRequireMutatingCsrf(['id' => 1]);
    echo "NOT_REJECTED";
} catch (Throwable $e) {
    echo "THREW";
}
PHP
);
$probe = [];
exec(escapeshellarg($php) . ' ' . escapeshellarg($tmp) . ' 2>&1', $probe, $exitCode);
@unlink($tmp);
$probeOut = implode("\n", $probe);
$csrfMissingRejected = $exitCode !== 0 || !str_contains($probeOut, 'NOT_REJECTED');
$log('C', 'SEC-004 CSRF missing token rejected', [
    'exitCode' => $exitCode,
    'output' => substr($probeOut, 0, 200),
    'pass' => $csrfMissingRejected,
]);
echo ($csrfMissingRejected ? 'PASS' : 'FAIL') . " SEC-004 CSRF missing token rejected\n";

// --- SEC-002 extract cradFileViewAuthorized without running file-view side effects ---
$fvSource = (string) file_get_contents(ROOT_PATH . '/modules/crad/file-view.php');
if (!preg_match('/function cradFileViewAuthorized\b.*?^}/ms', $fvSource, $m)) {
    echo "FAIL SEC-002 could not extract cradFileViewAuthorized\n";
    $log('F', 'SEC-002 extract failed', ['pass' => false]);
} else {
    eval($m[0]);
    try {
        $pdo = getCradDatabaseConnection();
        $prop = $pdo->query(
            'SELECT id, submitted_by_user, proposal_number FROM crad_research_proposals ORDER BY id ASC LIMIT 1'
        )->fetch(PDO::FETCH_ASSOC);

        if (!$prop) {
            $log('F', 'SEC-002 no proposals', ['pass' => null]);
            echo "SKIP SEC-002 no proposals in DB\n";
        } else {
            $pid = (int) $prop['id'];

            $deny = cradFileViewAuthorized($pdo, $prop, $pid, 999999, 'panel');
            $passDeny = $deny === false;
            $log('F', 'SEC-002 unassigned panel denied', ['pid' => $pid, 'canView' => $deny, 'pass' => $passDeny]);
            echo ($passDeny ? 'PASS' : 'FAIL') . " SEC-002 unassigned panel denied\n";

            $allowOfficer = cradFileViewAuthorized($pdo, $prop, $pid, 1, 'crad_officer');
            $passOfficer = $allowOfficer === true;
            $log('F', 'SEC-002 crad_officer allowed', ['pid' => $pid, 'canView' => $allowOfficer, 'pass' => $passOfficer]);
            echo ($passOfficer ? 'PASS' : 'FAIL') . " SEC-002 crad_officer allowed\n";

            $ownerId = (int) ($prop['submitted_by_user'] ?? 0);
            if ($ownerId > 0) {
                $allowOwner = cradFileViewAuthorized($pdo, $prop, $pid, $ownerId, 'student');
                $passOwner = $allowOwner === true;
                $log('F', 'SEC-002 owner allowed', ['pid' => $pid, 'canView' => $allowOwner, 'pass' => $passOwner]);
                echo ($passOwner ? 'PASS' : 'FAIL') . " SEC-002 owner allowed\n";
            }
        }
    } catch (Throwable $e) {
        $log('F', 'SEC-002 error', ['error' => $e->getMessage(), 'pass' => false]);
        echo 'FAIL SEC-002 ' . $e->getMessage() . "\n";
    }
}

// --- SEC-003 ---
putenv('DB_HOST=mariadb-fake.internal');
putenv('DB_DATABASE=hf_db_fake');
$_ENV['DB_HOST'] = 'mariadb-fake.internal';
$_ENV['DB_DATABASE'] = 'hf_db_fake';
$_SERVER['DB_HOST'] = 'mariadb-fake.internal';
$_SERVER['DB_DATABASE'] = 'hf_db_fake';
putenv('SMS2_ALLOW_OFFICIAL_RESET');
unset($_ENV['SMS2_ALLOW_OFFICIAL_RESET'], $_SERVER['SMS2_ALLOW_OFFICIAL_RESET']);

$cloudBlocked = !smsOfficialCredentialResetAllowed();
$log('G', 'SEC-003 cloud reset blocked', [
    'cloudEnv' => sms2_has_cloud_db_env(),
    'resetAllowed' => smsOfficialCredentialResetAllowed(),
    'pass' => $cloudBlocked,
]);
echo ($cloudBlocked ? 'PASS' : 'FAIL') . " SEC-003 cloud reset blocked\n";

putenv('SMS2_ALLOW_OFFICIAL_RESET=1');
$_ENV['SMS2_ALLOW_OFFICIAL_RESET'] = '1';
$_SERVER['SMS2_ALLOW_OFFICIAL_RESET'] = '1';
$overrideAllow = smsOfficialCredentialResetAllowed();
$log('G', 'SEC-003 allow flag permits reset', [
    'resetAllowed' => $overrideAllow,
    'pass' => $overrideAllow,
]);
echo ($overrideAllow ? 'PASS' : 'FAIL') . " SEC-003 allow flag permits reset\n";

echo "Log: {$logPath}\n";
