<?php
/**
 * Generate hostforge_demo_users.sql with known demo passwords.
 */
declare(strict_types=1);

$accounts = [
    ['superadmin', 'superadmin@bestlink.edu.ph', '@Superadmin123', 'Super Admin', 'superadmin', null],
    ['admin', 'admin@bestlink.edu.ph', '@Superadmin123', 'Admin', 'sms_admin', null],
    ['registrar', 'registrar@bestlink.edu.ph', '@registrar123', 'Registrar', 'registrar', null],
    ['cradofficer', 'cradofficer@bestlink.ph', '@Cradofficer123', 'CRAD Officer', 'crad_officer', null],
    ['finance', 'finance@bestlink.edu.ph', '@finance123', 'Finance', 'finance', null],
    ['studentaffairs', 'studentaffairs@bestlink.edu.ph', '@studentaffairs123', 'Student Affairs', 'osa', null],
    ['itofficer', 'itofficer@bestlink.edu.ph', '@itofficer123', 'IT Officer', 'it_office', null],
    ['qualityassurance', 'qualityassurance@bestlink.edu.ph', '@qualityassurance123', 'Quality Assurance', 'qa', null],
    ['dean', 'dean@bestlink.edu.ph', '@Dean123', 'Dean', 'hr', null],
    ['admission', 'admission@bestlink.edu.ph', '@admission123', 'Admission', 'admission', null],
    ['researchcoordinator', 'researchcoordinator@bestlink.edu.ph', '@Coordinator123', 'Mrs. Kris Guevarra', 'research_coordinator', null],
    ['rsantos', 'rsantos@bestlink.edu.ph', '@Adviser123', 'Dr. Roberto M. Santos', 'adviser', null],
    ['grammarian', 'grammarian@bestlink.edu.ph', '@Grammarian123', 'Grammarian', 'grammarian', null],
    ['jobertvalentino', 'jobertvalentino@bestlink.edu.ph', '@Adviser123', 'Dr. Jobert Valentino', 'panel', null],
    ['jonathanestrada', 'jonathanestrada@bestlink.edu.ph', '@Adviser123', 'Dr. Jonathan Estrada', 'panel', null],
    ['michelleguevarra', 'michelleguevarra@bestlink.edu.ph', '@Adviser123', 'Dr. Michelle Guevarra', 'panel', null],
    ['reviewcommittee', 'reviewcommittee@bestlink.edu.ph', '@Committee123', 'Review Committee', 'review_committee', null],
    ['deptchair', 'deptchair@bestlink.edu.ph', '@Department123', 'Department Chair', 'department_chair', null],
    ['researchoffice', 'researchoffice@bestlink.edu.ph', '@Research123', 'Research Office', 'research_office', null],
    ['vpaa', 'vpaa@bestlink.edu.ph', '@Vpaa123', 'VPAA', 'vpaa', null],
    ['depthead', 'depthead@bestlink.edu.ph', '@Depthead123', 'Department Head', 'department_head', null],
    ['s230000001', 's230000001@bestlink.edu.ph', '@Kenneth8080', 'Student User', 'student', 'S230000001'],
];

$out = [];
$out[] = '-- HostForge Database Import UI - DEMO USERS (upload AFTER hostforge_import.sql)';
$out[] = '-- Small file: bcrypt hashes with known passwords from database/seed_accounts.php';
$out[] = '-- Skips Super Admin setup because sms_users will no longer be empty.';
$out[] = '--';
$out[] = '-- Login examples:';
$out[] = '--   superadmin / @Superadmin123';
$out[] = '--   cradofficer / @Cradofficer123';
$out[] = '--   deptchair / @Department123';
$out[] = '--   depthead / @Depthead123';
$out[] = '--   s230000001 / @Kenneth8080';
$out[] = '--';
$out[] = '';
$out[] = 'SET FOREIGN_KEY_CHECKS=0;';
$out[] = 'SET NAMES utf8mb4;';
$out[] = '';
$out[] = '-- Upsert demo users (safe if username/email already exists)';
$out[] = '';

$id = 1;
$studentUserId = null;
foreach ($accounts as $a) {
    [$username, $email, $password, $fullName, $roleKey, $studentId] = $a;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sidSql = $studentId === null ? 'NULL' : ("'" . str_replace("'", "''", $studentId) . "'");
    $fullEsc = str_replace("'", "''", $fullName);
    $userEsc = str_replace("'", "''", $username);
    $emailEsc = str_replace("'", "''", $email);
    $roleEsc = str_replace("'", "''", $roleKey);
    $hashEsc = str_replace("'", "''", $hash);

    $out[] = "INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)";
    $out[] = "VALUES ({$id}, '{$userEsc}', '{$emailEsc}', '{$hashEsc}', '{$fullEsc}', '{$roleEsc}', {$sidSql}, 'active', 0, 0, NULL, NOW(), NOW(), NOW())";
    $out[] = "ON DUPLICATE KEY UPDATE";
    $out[] = "  `email` = VALUES(`email`),";
    $out[] = "  `password_hash` = VALUES(`password_hash`),";
    $out[] = "  `full_name` = VALUES(`full_name`),";
    $out[] = "  `role_key` = VALUES(`role_key`),";
    $out[] = "  `student_id` = VALUES(`student_id`),";
    $out[] = "  `status` = 'active',";
    $out[] = "  `must_change_password` = 0,";
    $out[] = "  `failed_login_attempts` = 0,";
    $out[] = "  `locked_until` = NULL;";
    $out[] = '';

    if ($username === 's230000001') {
        $studentUserId = $id;
    }
    $id++;
}

if ($studentUserId !== null) {
    $out[] = '-- Student profile for demo student';
    $out[] = "INSERT INTO `sms_student_profiles` (`id`, `user_id`, `student_id`, `program`, `year_level`, `section`, `semester`, `school_year`, `enrollment_status`, `standing`, `mobile`, `address`, `guardian`, `guardian_contact`, `created_at`, `updated_at`)";
    $out[] = "VALUES (1, {$studentUserId}, 'S230000001', 'Bachelor of Science in Information Technology', '4th Year', 'BSIT 4B', '1st Semester', '2026-2027', 'Enrolled', 'Good Standing', '0917 000 0011', 'Fairview, Quezon City', 'Juan Dela Cruz', '0918 000 0012', NOW(), NOW())";
    $out[] = "ON DUPLICATE KEY UPDATE";
    $out[] = "  `user_id` = VALUES(`user_id`),";
    $out[] = "  `program` = VALUES(`program`),";
    $out[] = "  `year_level` = VALUES(`year_level`),";
    $out[] = "  `section` = VALUES(`section`),";
    $out[] = "  `enrollment_status` = VALUES(`enrollment_status`);";
    $out[] = '';
}

$out[] = 'SET FOREIGN_KEY_CHECKS=1;';
$out[] = '';

$target = __DIR__ . '/hostforge_demo_users.sql';
file_put_contents($target, implode("\n", $out) . "\n");
echo "Wrote {$target} (" . filesize($target) . " bytes)\n";
