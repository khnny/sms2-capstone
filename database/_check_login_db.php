<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = db();
if (!$pdo) {
    echo "DB CONNECT FAILED\n";
    print_r(sms2_db_connection_summary());
    exit(1);
}

$sum = sms2_db_connection_summary();
echo "DB OK\n";
echo 'host=' . $sum['host'] . ':' . $sum['port'] . '/' . $sum['database'] . ' user=' . $sum['user'] . "\n";
echo 'cloud_env=' . (!empty($sum['cloud_env']) ? 'yes' : 'no') . "\n";

$c = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo "users_count={$c}\n";

$join = (int) $pdo->query(
    'SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.role_key = u.role_key'
)->fetchColumn();
echo "login_capable_join={$join}\n";

$orphan = (int) $pdo->query(
    'SELECT COUNT(*) FROM users u LEFT JOIN roles r ON r.role_key = u.role_key WHERE r.role_key IS NULL'
)->fetchColumn();
echo "users_missing_role={$orphan}\n";

$rows = $pdo->query(
    "SELECT username, email, role_key, status, must_change_password, failed_login_attempts,
            CASE WHEN locked_until IS NOT NULL AND locked_until > NOW() THEN 1 ELSE 0 END AS locked
     FROM users ORDER BY id LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo json_encode($r) . "\n";
}

try {
    $t = (int) $pdo->query('SELECT COUNT(*) FROM login_throttles')->fetchColumn();
    echo "login_throttles={$t}\n";
    $cols = $pdo->query("SHOW COLUMNS FROM login_throttles LIKE 'id'")->fetch(PDO::FETCH_ASSOC);
    echo 'id_extra=' . ($cols['Extra'] ?? '(missing)') . ' default=' . var_export($cols['Default'] ?? null, true) . "\n";
    $active = (int) $pdo->query(
        'SELECT COUNT(*) FROM login_throttles WHERE locked_until IS NOT NULL AND locked_until > NOW()'
    )->fetchColumn();
    echo "active_locks={$active}\n";
} catch (Throwable $e) {
    echo 'throttle_err=' . $e->getMessage() . "\n";
}
