<?php
/**
 * Restore user password hashes from last known backup (before seed_accounts overwrite).
 * Run: C:\xampp\php\php.exe database/restore_user_passwords.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. Run from CLI only.\n";
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/config/database.php';

$pdo = getDatabaseConnection();

/** @var array<string, array{hash: string, changed_at: string}> */
$backup = [
    'superadmin' => ['hash' => '$2y$10$yeuumNmFTLjaAsv8N8hNu.QgOpg9DlrTiKrbWet2Q01jtvUasOfAW', 'changed_at' => '2026-08-31 07:53:31'],
    'registrar' => ['hash' => '$2y$10$/HmOuAP54dAuUkNOyNJo/e2GwrAszJqpF0sQmGvjofAtM/.6tcp.m', 'changed_at' => '2026-08-31 07:50:19'],
    'cradofficer' => ['hash' => '$2y$10$WZfe.MOXEjl8iS7hTaT/D.K7glWjpm3V8BmTZbbxmXPI9FectXuPq', 'changed_at' => '2026-08-31 07:50:19'],
    'finance' => ['hash' => '$2y$10$ryCgB4R4g9MkgK5wBln8WO279xeqhztZIqEgUCEOpDMyA2wXsmkyW', 'changed_at' => '2026-08-31 07:50:19'],
    'studentaffairs' => ['hash' => '$2y$10$ykS9zsSeg8ESbJDrnyaixuRg.OYKWUljfEzgDhwBWsn4MYjdRR9O2', 'changed_at' => '2026-08-31 07:50:19'],
    'itofficer' => ['hash' => '$2y$10$h1GQBrr0K5SM8whZCT2QxOmvpIN2aPKslctCSX3VMflxoiHVIdWGC', 'changed_at' => '2026-08-31 07:50:19'],
    'qualityassurance' => ['hash' => '$2y$10$cqKm0cN1jMdxpdS5l3yee.ygI3KG05tRBGw5cyagSwNFT.6YaytVq', 'changed_at' => '2026-08-31 07:50:20'],
    'dean' => ['hash' => '$2y$10$WU3FSM1vVIz3HqzXEHAWHOdKlvImfKYlOiGSd7r1XWIgF4IVJdtci', 'changed_at' => '2026-08-31 07:50:20'],
    's230000001' => ['hash' => '$2y$10$oFkiHM6jHKgiRWylpzl.7OiCMx627rzLUmUbyQhZYQQ63YgGq/gsy', 'changed_at' => '2026-08-31 07:55:09'],
    'admission' => ['hash' => '$2y$10$1M./oyAWOwzHhIjWoxGCWu5wm/6F/Jc3bzmYeF7hLt/jnhzg6KW9u', 'changed_at' => '2026-08-31 07:50:19'],
    'researchcoordinator' => ['hash' => '$2y$10$f6AGY/ZDFdykQTCiSK5YYePxBGti0SMMIqIOBuLU0OtnNY6Xwpcn2', 'changed_at' => '2026-08-31 08:04:22'],
    'rsantos' => ['hash' => '$2y$10$8K5JenMWtmwLwKeqq2086.AXlEED4PzOs/BZvilw.zVzq2Wdc0M.u', 'changed_at' => '2026-08-31 08:04:55'],
    'researchgrant' => ['hash' => '$2y$10$Kmx3XLgjIdLL3S4rP0Bs.uKL0oqNZyDRN4DpDwhtbYc249mAcYx8i', 'changed_at' => '2026-08-10 20:07:43'],
    'grammarian' => ['hash' => '$2y$10$DOubhW7dlaxRDFenQkOz2u0I.zVI3mF17NAGenmLyYU8cvYS4x9CS', 'changed_at' => '2026-08-31 08:06:20'],
    'jobert.valentino' => ['hash' => '$2y$10$AkCzL7RmKfYgXNwrujBK6.nJh7BRnDK3Lb.iwkgJ8r8SVYSJU5.Ge', 'changed_at' => '2026-08-31 08:06:08'],
    'jonathan.estrada' => ['hash' => '$2y$10$OkM9HAk8zWpIlGC.L4h/6.g9CbsjrWcZUt5PAUyUh2OF.HDGRMfyi', 'changed_at' => '2026-08-31 08:06:01'],
    'michelle.guevarra' => ['hash' => '$2y$10$Y6XHywAVrKL.JSBrpkSMbe8RAtYrHjL9D0eCtprI/p7ViGTwL2DBi', 'changed_at' => '2026-08-31 08:05:53'],
    'admin' => ['hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'changed_at' => '2026-08-18 00:38:50'],
    'reviewcommittee' => ['hash' => '$2y$10$GQbE2PiU0TKW3DhZ7bPt3uqTMc5XAOfBMutgb9KpnN6lsVS75rev6', 'changed_at' => '2026-08-31 08:04:41'],
];

$stmt = $pdo->prepare(
    'UPDATE `sms2_users`
        SET password_hash = ?,
            password_changed_at = ?,
            must_change_password = 0,
            failed_login_attempts = 0,
            locked_until = NULL
      WHERE username = ?
      LIMIT 1'
);

$restored = 0;
$skipped  = 0;

foreach ($backup as $username => $row) {
    $stmt->execute([$row['hash'], $row['changed_at'], $username]);
    if ($stmt->rowCount() > 0) {
        echo "  ✓ restored {$username}" . PHP_EOL;
        $restored++;
    } else {
        echo "  - skipped {$username} (not found)" . PHP_EOL;
        $skipped++;
    }
}

echo PHP_EOL . "DONE. Restored {$restored} account(s), skipped {$skipped}." . PHP_EOL;
echo 'New accounts (deptchair, researchoffice, vpaa) keep their seed passwords.' . PHP_EOL;
