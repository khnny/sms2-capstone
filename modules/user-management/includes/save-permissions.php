<?php
/**
 * SMS 2 – AJAX endpoint: save role permission overrides to database.
 *
 * POST body (JSON):
 *   { "role": "registrar", "module": "enrollment", "granted": true, "csrf_token": "..." }
 *   { "role": "__reset__", "module": "__all__", "granted": false, "csrf_token": "..." }
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';

header('Content-Type: application/json');

if (!isAuthenticated() || !userCanAccessModule('user-management')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);

if (!$data || !isset($data['role'], $data['module'], $data['granted'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid payload']);
    exit;
}

requireCsrfJson($data);

$role    = (string) $data['role'];
$module  = (string) $data['module'];
$granted = (bool) $data['granted'];

$pdo = db();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}

$validRoles   = ['superadmin', 'sms_admin', 'admission', 'registrar', 'finance', 'hr', 'adviser', 'research_director', 'grammarian', 'it_office', 'osa', 'qa', 'crad', 'crad_officer', 'research_coordinator', 'department_head', 'department_chair', 'research_office', 'vpaa', 'research_grant', 'review_committee', 'student'];
$validModules = [
    'enrollment', 'registrar', 'curriculum', 'accreditation',
    'payment', 'faculty', 'scheduling', 'cocurricular', 'lms', 'crad',
];

$defaults = [
    'superadmin'   => ['user-management'],
    'sms_admin'    => ['enrollment', 'registrar', 'curriculum', 'accreditation', 'payment', 'faculty', 'scheduling', 'cocurricular', 'lms', 'crad'],
    'admission'    => ['enrollment'],
    'registrar'    => ['registrar', 'curriculum', 'scheduling'],
    'finance'      => ['payment'],
    'hr'           => ['faculty'],
    'adviser'      => ['faculty'],
    'research_director' => ['faculty'],
    'grammarian'   => ['faculty'],
    'it_office'    => ['lms'],
    'osa'          => ['cocurricular'],
    'qa'           => ['accreditation'],
    'crad_officer' => ['crad'],
    'research_coordinator' => ['crad'],
    'department_head' => ['crad'],
    'research_grant' => ['crad_grant'],
    'review_committee' => ['crad_grant'],
];

try {
    if ($role === '__reset__' && $module === '__all__') {
        $pdo->exec('DELETE FROM role_permissions');
        $ins = $pdo->prepare(
            'INSERT INTO role_permissions (role_key, module_key, granted) VALUES (?, ?, 1)'
        );
        foreach ($defaults as $rk => $mods) {
            foreach ($mods as $m) {
                $ins->execute([$rk, $m]);
            }
        }

        $permFile = ROOT_PATH . '/config/perm_overrides.json';
        if (is_file($permFile)) {
            @unlink($permFile);
        }

        logActivity('update', 'Reset all role permissions to defaults', 'user-management');
        echo json_encode(['ok' => true, 'reset' => true]);
        exit;
    }

    if ($module === 'user-management' && $role === 'superadmin' && !$granted) {
        echo json_encode(['ok' => false, 'error' => 'This permission is locked']);
        exit;
    }

    if ($module === 'student_portal' && $role !== 'student') {
        echo json_encode(['ok' => false, 'error' => 'Student Portal is locked to Student accounts only']);
        exit;
    }

    if ($role === 'student' && ($module !== 'student_portal' || !$granted)) {
        echo json_encode(['ok' => false, 'error' => 'Student role is locked to Student Portal only']);
        exit;
    }

    if ($role === 'adviser') {
        echo json_encode(['ok' => false, 'error' => 'Adviser permissions are managed separately and are not editable here']);
        exit;
    }

    if (!in_array($role, $validRoles, true) || !in_array($module, $validModules, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Unknown role or module']);
        exit;
    }

    $dbRole = smsNormalizeRoleKey($role);

    $pdo->prepare(
        'INSERT INTO role_permissions (role_key, module_key, granted)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE granted = VALUES(granted)'
    )->execute([$dbRole, $module, $granted ? 1 : 0]);

    // Keep legacy JSON in sync for any code still reading it
    $permFile = ROOT_PATH . '/config/perm_overrides.json';
    $overrides = [];
    if (is_readable($permFile)) {
        $decoded = json_decode((string) file_get_contents($permFile), true);
        if (is_array($decoded)) {
            $overrides = $decoded;
        }
    }
    $matrixKey = smsMatrixRoleKey($dbRole);
    if (!isset($overrides[$matrixKey])) {
        $overrides[$matrixKey] = [];
    }
    $overrides[$matrixKey][$module] = $granted;
    @file_put_contents($permFile, json_encode($overrides, JSON_PRETTY_PRINT), LOCK_EX);

    logActivity(
        'update',
        sprintf('Permission %s:%s = %s', $dbRole, $module, $granted ? 'grant' : 'deny'),
        'user-management'
    );

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save permission']);
}
