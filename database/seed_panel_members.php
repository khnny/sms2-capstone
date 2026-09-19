<?php
/**
 * Seed official Panel Member accounts.
 * Run: C:\xampp\php\php.exe database/seed_panel_members.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. Run from CLI only:\n  C:\\xampp\\php\\php.exe database/seed_panel_members.php\n";
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/config/database.php';
require_once __DIR__ . '/official_accounts.php';

$pdo = getDatabaseConnection();

$pdo->prepare(
    'INSERT INTO roles (role_key, label, description) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description)'
)->execute(['panel', 'Panel Member', 'Research defense panel account']);

$pdo->prepare(
    'INSERT INTO role_permissions (role_key, module_key, granted) VALUES (?, ?, 1)
     ON DUPLICATE KEY UPDATE granted = 1'
)->execute(['panel', 'faculty']);

$upsert = $pdo->prepare(
    'INSERT INTO users
        (username, email, password_hash, full_name, role_key, student_id, status, password_changed_at, must_change_password, failed_login_attempts, locked_until)
     VALUES (?, ?, ?, ?, ?, NULL, \'active\', NOW(), 0, 0, NULL)
     ON DUPLICATE KEY UPDATE
        email = VALUES(email),
        password_hash = VALUES(password_hash),
        full_name = VALUES(full_name),
        role_key = VALUES(role_key),
        status = \'active\',
        password_changed_at = NOW(),
        must_change_password = 0,
        failed_login_attempts = 0,
        locked_until = NULL'
);

foreach (smsOfficialAccounts() as $account) {
    if ($account['role_key'] !== 'panel') {
        continue;
    }
    $upsert->execute([
        $account['username'],
        $account['email'],
        password_hash($account['password'], PASSWORD_DEFAULT),
        $account['full_name'],
        'panel',
    ]);
    echo "Seeded {$account['username']} ({$account['full_name']})" . PHP_EOL;
}

echo 'DONE. Panel accounts ready.' . PHP_EOL;
