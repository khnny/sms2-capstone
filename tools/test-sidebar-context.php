<?php
/**
 * CLI smoke test for sidebar navigation context.
 * Run: C:\xampp\php\php.exe tools/test-sidebar-context.php
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/navigation-context.php';

$roles = [
    'student' => ['mode' => 'student', 'module' => 'student_portal', 'dashboard' => false, 'home' => '/modules/student-portal/pages/dashboard.php'],
    'registrar' => ['mode' => 'admin_modules', 'module' => 'registrar', 'dashboard' => true, 'home' => '/dashboard/index.php'],
    'finance' => ['mode' => 'admin_modules', 'module' => 'payment', 'dashboard' => true, 'home' => '/dashboard/index.php'],
    'crad_officer' => ['mode' => 'admin_modules', 'module' => 'crad', 'dashboard' => true, 'home' => '/dashboard/index.php'],
    'research_coordinator' => ['mode' => 'admin_modules', 'module' => 'crad', 'dashboard' => false, 'home' => '/modules/crad/index.php'],
    'department_head' => ['mode' => 'admin_modules', 'module' => 'crad', 'dashboard' => false, 'home' => '/modules/crad/pages/research-coordinator-management.php'],
    'panel' => ['mode' => 'faculty_workspace', 'module' => 'faculty', 'dashboard' => false, 'home' => '/modules/faculty/pages/assigned-defenses.php'],
    'grammarian' => ['mode' => 'faculty_workspace', 'module' => 'faculty', 'dashboard' => false, 'home' => '/modules/faculty/pages/for-evaluation.php'],
    'research_director' => ['mode' => 'faculty_workspace', 'module' => 'faculty', 'dashboard' => false, 'home' => '/modules/faculty/pages/research-director.php'],
    'hr' => ['mode' => 'admin_modules', 'module' => 'faculty', 'dashboard' => true, 'home' => '/dashboard/index.php'],
    'superadmin' => ['mode' => 'admin_modules', 'module' => 'user-management', 'dashboard' => true, 'home' => '/dashboard/index.php'],
];

$_SERVER['SCRIPT_NAME'] = '/sms2_system/dashboard/index.php';

$failed = 0;
foreach ($roles as $roleKey => $expected) {
    $mode = smsSidebarMode($roleKey);
    $module = smsEffectiveActiveModule('dashboard', $roleKey);
    $highlight = smsSidebarHighlightModule('dashboard', $roleKey);
    $home = smsRoleHomeUrl($roleKey);
    $showsDashboard = smsShowsMainDashboard($roleKey);

    $ok = $mode === $expected['mode']
        && $module === $expected['module']
        && $highlight === $expected['module']
        && $showsDashboard === $expected['dashboard']
        && str_contains($home, $expected['home']);

    if (!$ok) {
        $failed++;
        echo "FAIL {$roleKey}: mode={$mode} module={$module} highlight={$highlight} dashboard=" . ($showsDashboard ? 'yes' : 'no') . " home={$home}\n";
        echo "     expected mode={$expected['mode']} module={$expected['module']} dashboard=" . ($expected['dashboard'] ? 'yes' : 'no') . " home contains {$expected['home']}\n";
    } else {
        echo "OK   {$roleKey}: {$mode} / home=" . basename(parse_url($home, PHP_URL_PATH) ?: '') . "\n";
    }
}

$_SERVER['SCRIPT_NAME'] = '/sms2_system/modules/registrar/pages/student-information-system.php';
$registrarModule = smsEffectiveActiveModule('dashboard', 'registrar');
if ($registrarModule !== 'registrar') {
    $failed++;
    echo "FAIL URL resolver: expected registrar, got {$registrarModule}\n";
} else {
    echo "OK   URL resolver: registrar page => {$registrarModule}\n";
}

exit($failed > 0 ? 1 : 0);
