<?php
/**
 * SMS 2 – Create official role accounts (no demo fluff).
 * Run: C:\xampp\php\php.exe database/seed_accounts.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. Run from CLI only:\n  C:\\xampp\\php\\php.exe database/seed_accounts.php\n";
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/config/database.php';

$pdo = getDatabaseConnection();

echo "Ensuring roles..." . PHP_EOL;

$roles = [
    ['superadmin', 'Super Admin', 'Full system access'],
    ['admin', 'Super Admin', 'Legacy super admin access'],
    ['admission', 'Admission', 'Admission office access'],
    ['registrar', 'Registrar', 'Enrollment, records, scheduling'],
    ['finance', 'Finance', 'Payments and receivables'],
    ['hr', 'Dean', 'Dean and faculty processes'],
    ['it_office', 'IT Office', 'LMS and IT modules'],
    ['osa', 'OSA', 'Student affairs / co-curricular'],
    ['qa', 'QA Office', 'Accreditation and quality'],
    ['crad_officer', 'CRAD Officer', 'Research and development'],
    ['research_coordinator', 'Research Coordinator', 'Research coordination access'],
    ['department_head', 'Department Head', 'Adviser and panel assignment'],
    ['department_chair', 'Department Chair', 'Grant approval — department chair sign-off'],
    ['research_office', 'Research Office', 'Grant approval — research office sign-off'],
    ['vpaa', 'VPAA', 'Grant approval — VPAA sign-off'],
    ['research_director', 'Research Director', 'Research defense scheduling director account'],
    ['grammarian', 'Grammarian', 'Research grammar and manuscript evaluation account'],
    ['review_committee', 'Review Committee', 'Grant proposal review and rubric evaluation'],
    ['panel', 'Panel Member', 'Research defense panel account'],
    ['student', 'Student', 'Student portal only'],
];

$insRole = $pdo->prepare(
    'INSERT INTO roles (role_key, label, description) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description)'
);
foreach ($roles as $role) {
    $insRole->execute($role);
}

echo "Updating role permissions…" . PHP_EOL;

$pdo->exec('DELETE FROM role_permissions');

$perms = [
    'superadmin'   => ['user-management'],
    'admission'    => ['enrollment'],
    'registrar'    => ['registrar', 'curriculum', 'scheduling'],
    'crad_officer' => ['crad'],
    'research_coordinator' => ['crad'],
    'department_head' => ['crad'],
    'department_chair' => ['crad'],
    'research_office' => ['crad'],
    'research_director' => ['faculty'],
    'grammarian'   => ['faculty'],
    'review_committee' => ['crad_grant'],
    'panel'        => ['faculty'],
    'finance'      => ['payment'],
    'osa'          => ['cocurricular'],
    'it_office'    => ['lms'],
    'qa'           => ['accreditation'],
    'vpaa'         => ['accreditation'],
    'hr'           => ['faculty'],
    'student'      => ['student_portal'],
];

$insPerm = $pdo->prepare(
    'INSERT INTO role_permissions (role_key, module_key, granted) VALUES (?, ?, 1)'
);
foreach ($perms as $role => $modules) {
    foreach ($modules as $mod) {
        $insPerm->execute([$role, $mod]);
        echo "  + {$role} → {$mod}" . PHP_EOL;
    }
}

echo "Creating / updating accounts…" . PHP_EOL;

$accounts = [
    [
        'username' => 'depthead',
        'email' => 'depthead@bestlink.edu.ph',
        'password' => '@Depthead123',
        'full_name' => 'Department Head',
        'role_key' => 'department_head',
        'student_id' => null,
    ],
    [
        'username' => 'deptchair',
        'email' => 'deptchair@bestlink.edu.ph',
        'password' => '@Department123',
        'full_name' => 'Department Chair',
        'role_key' => 'department_chair',
        'student_id' => null,
    ],
    [
        'username' => 'researchoffice',
        'email' => 'researchoffice@bestlink.edu.ph',
        'password' => '@Research123',
        'full_name' => 'Research Office',
        'role_key' => 'research_office',
        'student_id' => null,
    ],
    [
        'username' => 'vpaa',
        'email' => 'vpaa@bestlink.edu.ph',
        'password' => '@Vpaa123',
        'full_name' => 'VPAA',
        'role_key' => 'vpaa',
        'student_id' => null,
    ],
    [
        'username' => 'superadmin',
        'email' => 'superadmin@bestlink.edu.ph',
        'password' => '@Superadmin123',
        'full_name' => 'Super Admin',
        'role_key' => 'superadmin',
        'student_id' => null,
    ],
    [
        'username' => 'admission',
        'email' => 'admission@bestlink.edu.ph',
        'password' => '@admission123',
        'full_name' => 'Admission',
        'role_key' => 'admission',
        'student_id' => null,
    ],
    [
        'username' => 'registrar',
        'email' => 'registrar@bestlink.edu.ph',
        'password' => '@registrar123',
        'full_name' => 'Registrar',
        'role_key' => 'registrar',
        'student_id' => null,
    ],
    [
        'username' => 'cradofficer',
        'email' => 'cradofficer@bestlink.ph',
        'password' => '@Cradofficer123',
        'full_name' => 'CRAD Officer',
        'role_key' => 'crad_officer',
        'student_id' => null,
    ],
    [
        'username' => 'researchcoordinator',
        'email' => 'researchcoordinator@bestlink.edu.ph',
        'password' => '@Coordinator123',
        'full_name' => 'Mrs. Kris Guevarra',
        'role_key' => 'research_coordinator',
        'student_id' => null,
    ],
    [
        'username' => 'grammarian',
        'email' => 'grammarian@bestlink.edu.ph',
        'password' => '@Grammarian123',
        'full_name' => 'Grammarian',
        'role_key' => 'grammarian',
        'student_id' => null,
    ],
    [
        'username' => 'reviewcommittee',
        'email' => 'reviewcommittee@bestlink.edu.ph',
        'password' => '@Committee123',
        'full_name' => 'Review Committee Member',
        'role_key' => 'review_committee',
        'student_id' => null,
    ],
    [
        'username' => 'rsantos',
        'email' => 'rsantos@bestlink.edu.ph',
        'password' => '@Adviser123',
        'full_name' => 'Dr. Roberto M. Santos',
        'role_key' => 'adviser',
        'student_id' => null,
    ],
    [
        'username' => 'jobertvalentino',
        'email' => 'jobertvalentino@bestlink.edu.ph',
        'password' => '@Adviser123',
        'full_name' => 'Dr. Jobert Valentino',
        'role_key' => 'panel',
        'student_id' => null,
    ],
    [
        'username' => 'jonathanestrada',
        'email' => 'jonathanestrada@bestlink.edu.ph',
        'password' => '@Adviser123',
        'full_name' => 'Dr. Jonathan Estrada',
        'role_key' => 'panel',
        'student_id' => null,
    ],
    [
        'username' => 'michelleguevarra',
        'email' => 'michelleguevarra@bestlink.edu.ph',
        'password' => '@Adviser123',
        'full_name' => 'Dr. Michelle Guevarra',
        'role_key' => 'panel',
        'student_id' => null,
    ],
    [
        'username' => 'finance',
        'email' => 'finance@bestlink.edu.ph',
        'password' => '@finance123',
        'full_name' => 'Finance',
        'role_key' => 'finance',
        'student_id' => null,
    ],
    [
        'username' => 'studentaffairs',
        'email' => 'studentaffairs@bestlink.edu.ph',
        'password' => '@studentaffairs123',
        'full_name' => 'Student Affairs',
        'role_key' => 'osa',
        'student_id' => null,
    ],
    [
        'username' => 'itofficer',
        'email' => 'itofficer@bestlink.edu.ph',
        'password' => '@itofficer123',
        'full_name' => 'IT Officer',
        'role_key' => 'it_office',
        'student_id' => null,
    ],
    [
        'username' => 'qualityassurance',
        'email' => 'qualityassurance@bestlink.edu.ph',
        'password' => '@qualityassurance123',
        'full_name' => 'Quality Assurance',
        'role_key' => 'qa',
        'student_id' => null,
    ],
    [
        'username' => 'dean',
        'email' => 'dean@bestlink.edu.ph',
        'password' => '@Dean123',
        'full_name' => 'Dean',
        'role_key' => 'hr',
        'student_id' => null,
    ],
    [
        'username' => 's230000001',
        'email' => 's230000001@bestlink.edu.ph',
        'password' => '@Kenneth8080',
        'full_name' => 'Student User',
        'role_key' => 'student',
        'student_id' => 'S230000001',
    ],
];

$upsert = $pdo->prepare(
    'INSERT INTO users
        (username, email, password_hash, full_name, role_key, student_id, status, password_changed_at, must_change_password, failed_login_attempts, locked_until)
     VALUES (?, ?, ?, ?, ?, ?, \'active\', NOW(), 0, 0, NULL)
     ON DUPLICATE KEY UPDATE
        email = VALUES(email),
        full_name = VALUES(full_name),
        role_key = VALUES(role_key),
        student_id = VALUES(student_id),
        status = \'active\',
        must_change_password = 0,
        failed_login_attempts = 0,
        locked_until = NULL'
);

foreach ($accounts as $a) {
    $upsert->execute([
        $a['username'],
        $a['email'],
        password_hash($a['password'], PASSWORD_DEFAULT),
        $a['full_name'],
        $a['role_key'],
        $a['student_id'],
    ]);
    echo "  ✓ {$a['username']} ({$a['role_key']})" . PHP_EOL;
}

// Clear legacy JSON overrides so DB is source of truth
$permFile = ROOT_PATH . '/config/perm_overrides.json';
if (is_file($permFile)) {
    @unlink($permFile);
    echo "Removed perm_overrides.json" . PHP_EOL;
}

$pdo->prepare(
    'INSERT INTO activity_logs (user_id, user_name, role_key, action, module_key, detail, ip_address)
     VALUES (NULL, ?, ?, ?, ?, ?, ?)'
)->execute(['System', 'admin', 'seed', 'System', 'Official role accounts seeded', 'cli']);

echo PHP_EOL . 'DONE. Accounts ready.' . PHP_EOL;
