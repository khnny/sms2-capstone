<?php
/**
 * One-shot repair for the Review Committee login (CLI only).
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once __DIR__ . '/official_accounts.php';

$pdo = db();
if (!$pdo) {
    fwrite(STDERR, "Database unavailable\n");
    exit(1);
}

$official = null;
foreach (smsOfficialAccounts() as $account) {
    if (($account['username'] ?? '') === 'reviewcommittee') {
        $official = $account;
        break;
    }
}
if (!$official) {
    fwrite(STDERR, "Official Review Committee credentials missing\n");
    exit(1);
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$stmt->execute(['reviewcommittee']);
$id = (int) $stmt->fetchColumn();
if ($id <= 0) {
    fwrite(STDERR, "Review Committee user not found\n");
    exit(1);
}

if (!smsSetUserPassword($id, (string) $official['password'], false)) {
    fwrite(STDERR, "Password restore failed\n");
    exit(1);
}

$pdo->prepare(
    "UPDATE users
     SET role_key = ?, email = ?, full_name = ?, status = 'active',
         failed_login_attempts = 0, locked_until = NULL, must_change_password = 0
     WHERE id = ?"
)->execute([
    $official['role_key'],
    $official['email'],
    $official['full_name'],
    $id,
]);

$pdo->prepare(
    "INSERT INTO role_permissions (role_key, module_key, granted)
     VALUES ('review_committee', 'crad_grant', 1)
     ON DUPLICATE KEY UPDATE granted = VALUES(granted)"
)->execute();

$pdo->prepare(
    "INSERT IGNORE INTO roles (role_key, label, description, is_system)
     VALUES ('review_committee', 'Review Committee', 'Grant proposal review and rubric evaluation', 1)"
)->execute();

echo "Review Committee account restored.\n";
