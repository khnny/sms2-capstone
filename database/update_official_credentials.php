<?php
/**
 * Apply official account emails and passwords to the live database.
 * Run: C:\xampp\php\php.exe database/update_official_credentials.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. Run from CLI only:\n  C:\\xampp\\php\\php.exe database/update_official_credentials.php\n";
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/config/database.php';
require_once __DIR__ . '/official_accounts.php';

$pdo = getDatabaseConnection();
$accounts = smsOfficialAccounts();

/**
 * @param list<string> $emails
 */
function smsSyncCradEmails(PDO $crad, array $emails): void
{
    $pairs = [];
    foreach ($emails as $pair) {
        if (!is_array($pair) || count($pair) !== 2) {
            continue;
        }
        [$old, $new] = $pair;
        $old = strtolower(trim($old));
        $new = strtolower(trim($new));
        if ($old === '' || $new === '' || $old === $new) {
            continue;
        }
        $pairs[] = [$old, $new];
    }
    if ($pairs === []) {
        return;
    }

    $tables = [
        ['research_panel_assignments', 'panel_email'],
        ['research_adviser_assignments', 'adviser_email'],
        ['research_coordinator_assignments', 'coordinator_email'],
        ['crad_notifications', 'recipient_email'],
    ];

    foreach ($tables as [$table, $column]) {
        try {
            $check = $crad->query("SHOW TABLES LIKE " . $crad->quote($table))->fetch();
            if (!$check) {
                continue;
            }
            $col = $crad->query("SHOW COLUMNS FROM `{$table}` LIKE " . $crad->quote($column))->fetch();
            if (!$col) {
                continue;
            }
            $stmt = $crad->prepare(
                "UPDATE `{$table}` SET `{$column}` = :new WHERE LOWER(TRIM(`{$column}`)) = :old"
            );
            foreach ($pairs as [$old, $new]) {
                $stmt->execute([':old' => $old, ':new' => $new]);
            }
        } catch (Throwable $e) {
            echo "  ! CRAD sync skipped for {$table}.{$column}: {$e->getMessage()}" . PHP_EOL;
        }
    }
}

$find = $pdo->prepare(
    'SELECT id, username, email FROM users
     WHERE username = :uname OR LOWER(email) = LOWER(:email)
     LIMIT 1'
);

$update = $pdo->prepare(
    'UPDATE users
        SET username = :username,
            email = :email,
            password_hash = :hash,
            full_name = :full_name,
            role_key = :role_key,
            student_id = :student_id,
            status = \'active\',
            password_changed_at = NOW(),
            must_change_password = 0,
            failed_login_attempts = 0,
            locked_until = NULL
      WHERE id = :id'
);

$emailPairs = [];
$updated = 0;
$created = 0;

echo 'Updating official account credentials…' . PHP_EOL;

foreach ($accounts as $account) {
    $keys = array_values(array_unique(array_filter(array_merge(
        [$account['username'], $account['email']],
        $account['lookup'] ?? []
    ))));

    $row = null;
    foreach ($keys as $key) {
        $find->execute([':uname' => $key, ':email' => $key]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            break;
        }
    }

    $hash = password_hash($account['password'], PASSWORD_DEFAULT);

    if ($row) {
        $oldEmail = strtolower(trim((string) ($row['email'] ?? '')));
        $newEmail = strtolower(trim($account['email']));
        if ($oldEmail !== '' && $oldEmail !== $newEmail) {
            $emailPairs[] = [$oldEmail, $newEmail];
        }

        $update->execute([
            ':username' => $account['username'],
            ':email' => $account['email'],
            ':hash' => $hash,
            ':full_name' => $account['full_name'],
            ':role_key' => $account['role_key'],
            ':student_id' => $account['student_id'],
            ':id' => (int) $row['id'],
        ]);
        echo "  ✓ updated {$account['username']} ({$account['email']})" . PHP_EOL;
        $updated++;
        continue;
    }

    $pdo->prepare(
        'INSERT INTO users
            (username, email, password_hash, full_name, role_key, student_id, status, password_changed_at, must_change_password, failed_login_attempts, locked_until)
         VALUES (?, ?, ?, ?, ?, ?, \'active\', NOW(), 0, 0, NULL)'
    )->execute([
        $account['username'],
        $account['email'],
        $hash,
        $account['full_name'],
        $account['role_key'],
        $account['student_id'],
    ]);
    echo "  + created {$account['username']} ({$account['email']})" . PHP_EOL;
    $created++;
}

if ($emailPairs !== []) {
    try {
        require_once ROOT_PATH . '/modules/crad/config/config.php';
        $crad = getCradDatabaseConnection();
        smsSyncCradEmails($crad, $emailPairs);
        echo '  ✓ synced CRAD email references' . PHP_EOL;
    } catch (Throwable $e) {
        echo '  ! CRAD email sync failed: ' . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "DONE. Updated {$updated}, created {$created}." . PHP_EOL;
