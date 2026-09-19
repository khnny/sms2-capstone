<?php
/**
 * SMS 2 – Database installer (CLI only).
 * Creates schema + seed roles, permissions, settings.
 * Does NOT create demo users — use /setup/ for the first Super Admin.
 *
 * CLI:  C:\xampp\php\php.exe database/install.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// Web access is blocked — installer is CLI-only (prevents remote wipe/reinstall).
$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. Run from CLI only:\n  C:\\xampp\\php\\php.exe database/install.php\n";
    exit(1);
}

$lockFile = __DIR__ . '/.installed';

function out(string $msg): void
{
    echo $msg . PHP_EOL;
}

if (is_file($lockFile) && !in_array('--force', $argv ?? [], true)) {
    out('Installer locked (.installed exists). Use --force to reinstall (DESTROYS DATA).');
    exit(0);
}

$host = DB_HOST;
$name = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = DB_CHARSET;

$schemaFile = __DIR__ . '/sms2_db.sql';
if (!is_readable($schemaFile)) {
    out('ERROR: sms2_db.sql not found.');
    exit(1);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};charset={$charset}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    out('ERROR: Cannot connect to MySQL. Is XAMPP MySQL running?');
    out($e->getMessage());
    exit(1);
}

out('Connected to MySQL.');
out('Preparing database `' . $name . '`...');
$quotedDbName = '`' . str_replace('`', '``', $name) . '`';
$pdo->exec(
    'CREATE DATABASE IF NOT EXISTS ' . $quotedDbName .
    ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
);
$pdo->exec('USE ' . $quotedDbName);
out('Applying schema…');

$sql = file_get_contents($schemaFile);
// Split on semicolons carefully — schema uses simple statements
$pdo->exec($sql);
out('Schema applied.');

/* ── Roles ─────────────────────────────────────────────────── */
$roles = [
    ['superadmin', 'Super Admin', 'Full system access'],
    ['admission', 'Admission', 'Admission office access'],
    ['registrar', 'Registrar', 'Enrollment, records, scheduling'],
    ['finance', 'Finance', 'Payments and receivables'],
    ['hr', 'Dean', 'Dean and faculty processes'],
    ['it_office', 'IT Office', 'LMS and IT modules'],
    ['osa', 'OSA', 'Student affairs / co-curricular'],
    ['qa', 'QA Office', 'Accreditation and quality'],
    ['crad_officer', 'CRAD Officer', 'Research and development'],
    ['research_coordinator', 'Research Coordinator', 'Research coordination access'],
    ['research_director', 'Research Director', 'Research defense scheduling director account'],
    ['grammarian', 'Grammarian', 'Research grammar and manuscript evaluation account'],
    ['student', 'Student', 'Student portal only'],
];

$insRole = $pdo->prepare(
    'INSERT INTO roles (role_key, label, description) VALUES (?, ?, ?)'
);
foreach ($roles as $r) {
    $insRole->execute($r);
}
out('Roles seeded.');

/* ── Default module permissions (matrix role keys) ───────────
 * Permission matrix uses "crad" for CRAD officer.
 * Session role_key remains "crad_officer".
 */
$defaults = [
    'superadmin'   => ['user-management'],
    'admission'    => ['enrollment'],
    'registrar'    => ['registrar', 'curriculum', 'scheduling'],
    'finance'      => ['payment'],
    'hr'           => ['faculty'],
    'it_office'    => ['lms'],
    'osa'          => ['cocurricular'],
    'qa'           => ['accreditation'],
    'crad'         => ['crad'],
    'research_coordinator' => ['crad'],
    'research_director' => ['faculty'],
    'grammarian'   => ['faculty'],
    'student'      => ['student_portal'],
];

// Store under actual role_key for crad_officer as 'crad' in permissions table
// using role_key column that references roles — crad is NOT in roles table.
// So we store permissions under crad_officer and map in app, OR add a virtual key.
// Simplest: store permissions with role_key = crad_officer for CRAD modules.

$permRows = [
    'superadmin'    => ['user-management'],
    'admission'     => ['enrollment'],
    'registrar'     => ['registrar', 'curriculum', 'scheduling'],
    'finance'       => ['payment'],
    'hr'            => ['faculty'],
    'it_office'     => ['lms'],
    'osa'           => ['cocurricular'],
    'qa'            => ['accreditation'],
    'crad_officer'  => ['crad'],
    'research_coordinator' => ['crad'],
    'research_director' => ['faculty'],
    'grammarian'    => ['faculty'],
    'student'       => ['student_portal'],
];

$insPerm = $pdo->prepare(
    'INSERT INTO role_permissions (role_key, module_key, granted) VALUES (?, ?, 1)'
);
foreach ($permRows as $roleKey => $modules) {
    foreach ($modules as $mod) {
        $insPerm->execute([$roleKey, $mod]);
    }
}
out('Permissions seeded.');
out('No demo users created — create your Super Admin via /setup/ after install.');

/* ── System settings ───────────────────────────────────────── */
$settings = [
    'session_timeout_minutes' => '30',
    'max_failed_logins' => '3',
    'lockout_value' => '5',
    'lockout_unit' => 'minutes',
    'lockout_seconds' => '300',
    'lockout_minutes' => '5',
    'min_password_length' => '8',
    'password_expiry_days' => '0',
    'require_password_change_first_login' => '0',
    'csrf_enabled' => '1',
    'mail_from_email' => 'noreply@bestlink.edu.ph',
    'mail_from_name' => 'SMS 2',
    'mail_admin_email' => '',
    'crad_active_term' => '',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_encryption' => 'tls',
    'smtp_username' => '',
    'smtp_password' => '',
    'mail_show_link_on_failure' => '0',
];

$insSet = $pdo->prepare(
    'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)'
);
foreach ($settings as $k => $v) {
    $insSet->execute([$k, $v]);
}
out('Settings seeded.');

$pdo->prepare(
    'INSERT INTO activity_logs (user_id, user_name, role_key, action, module_key, detail, ip_address)
     VALUES (NULL, ?, ?, ?, ?, ?, ?)'
)->execute([
    'System',
    'admin',
    'install',
    'System',
    'Database schema installed (roles, permissions, settings). Awaiting first Super Admin setup.',
    $isCli ? 'cli' : ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
]);

out('');
out('SUCCESS: sms2_db is ready.');
out('Next step: open ' . (BASE_URL !== '' ? BASE_URL : '') . '/setup/ and create your Super Admin account.');
@file_put_contents($lockFile, date('c') . PHP_EOL);
out('Installer locked via database/.installed');
out('IMPORTANT: Prefer deleting database/install.php in production.');
