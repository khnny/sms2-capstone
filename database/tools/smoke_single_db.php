<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
require_once $root . '/config/database.php';
require_once $root . '/modules/crad/config/config.php';

echo 'DB_NAME=' . DB_NAME . ' CRAD_DB_NAME=' . CRAD_DB_NAME . PHP_EOL;
echo 'Same DB: ' . (DB_NAME === CRAD_DB_NAME ? 'yes' : 'no') . PHP_EOL;

$pdo = getDatabaseConnection();
echo 'sms2_users count=' . $pdo->query('SELECT COUNT(*) FROM `sms2_users`')->fetchColumn() . PHP_EOL;

$crad = getCradDatabaseConnection();
echo 'crad_title_approvals=' . $crad->query('SELECT COUNT(*) FROM `crad_title_approvals`')->fetchColumn() . PHP_EOL;
echo 'crad_research_groups=' . $crad->query('SELECT COUNT(*) FROM `crad_research_groups`')->fetchColumn() . PHP_EOL;

$stmt = $pdo->query('SELECT id, username, role_key FROM `sms2_users` WHERE status = \'active\' LIMIT 3');
foreach ($stmt as $row) {
    echo 'user: ' . $row['username'] . ' (' . $row['role_key'] . ')' . PHP_EOL;
}

echo "Smoke OK\n";
