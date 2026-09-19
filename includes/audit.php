<?php
/**
 * SMS 2 - Activity / audit logging
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

/**
 * Write an audit log entry.
 */
function logActivity(
    string $action,
    string $detail,
    ?string $moduleKey = 'System',
    ?int $userId = null,
    ?string $userName = null,
    ?string $roleKey = null,
    bool $allowSessionFallback = true
): void {
    $pdo = db();
    if (!$pdo) {
        return;
    }

    if ($allowSessionFallback) {
        if ($userId === null && !empty($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
        }
        if ($userName === null) {
            $userName = $_SESSION['user_name'] ?? null;
        }
        if ($roleKey === null) {
            $roleKey = $_SESSION['user_role_key'] ?? null;
        }
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs
                (user_id, user_name, role_key, action, module_key, detail, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $stmt->execute([
            $userId,
            $userName !== null ? substr($userName, 0, 150) : null,
            $roleKey !== null ? substr($roleKey, 0, 40) : null,
            substr($action, 0, 40),
            $moduleKey !== null ? substr($moduleKey, 0, 60) : null,
            substr($detail, 0, 500),
            smsClientIp(),
            $ua !== '' ? $ua : null,
        ]);
    } catch (Throwable $e) {
        error_log('SMS2 audit log failed: ' . $e->getMessage());
    }
}
