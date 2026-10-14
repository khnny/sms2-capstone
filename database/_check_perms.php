<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
try {
    $pdo = db();
    // Check if role_permissions table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'sms2_role_permissions'")->fetchAll();
    if (empty($tables)) { echo "Table `sms2_role_permissions` does not exist.\n"; exit; }

    // Show all grants for user-management
    $rows = $pdo->query("SELECT * FROM `sms2_role_permissions` WHERE module_key = 'user-management'")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "No role_permissions rows for user-management.\n";
    } else {
        foreach ($rows as $r) { echo implode(' | ', $r) . PHP_EOL; }
    }

    // Also show all roles that have ANY grants
    echo "\n--- All distinct role_key values in role_permissions ---\n";
    $roles = $pdo->query("SELECT DISTINCT role_key FROM `sms2_role_permissions`")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($roles as $r) { echo $r . PHP_EOL; }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
