<?php
/**
 * Shared Super Admin activity-log payload (page + live poll).
 */
declare(strict_types=1);

if (!function_exists('smsIcon')) {
    require_once ROOT_PATH . '/includes/icons.php';
}

/**
 * @return array{
 *   latest_id: int,
 *   synced_at: string,
 *   stats: array{total: int, logins: int, changes: int, exports: int},
 *   actions: list<string>,
 *   modules: list<string>,
 *   logs: list<array<string, mixed>>
 * }
 */
function umActivityLogsPayload(?PDO $pdo): array
{
    $actionIcons = [
        'login'  => 'fa-sign-in-alt',
        'logout' => 'fa-sign-out-alt',
        'login_failed' => 'fa-exclamation-triangle',
        'lockout' => 'fa-user-lock',
        'create' => 'fa-plus-circle',
        'update' => 'fa-pen',
        'delete' => 'fa-trash-alt',
        'view'   => 'fa-eye',
        'export' => 'fa-file-export',
        'password_reset' => 'fa-key',
        'password_reset_request' => 'fa-envelope',
        'password_change' => 'fa-key',
        'install' => 'fa-database',
    ];

    $rows = [];
    if ($pdo) {
        $stmt = $pdo->query(
            'SELECT id,
                    IFNULL(user_name, "System") AS user,
                    IFNULL(role_key, "") AS role,
                    action,
                    detail,
                    IFNULL(module_key, "System") AS module,
                    IFNULL(ip_address, "—") AS ip,
                    DATE_FORMAT(created_at, "%b %e, %Y %H:%i:%s") AS time,
                    DATE_FORMAT(created_at, "%Y-%m-%d") AS log_date
             FROM activity_logs
             ORDER BY id DESC
             LIMIT 200'
        );
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    $logs = [];
    $actionOptions = [];
    $moduleOptions = [];
    $latestId = 0;
    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? 0);
        if ($id > $latestId) {
            $latestId = $id;
        }
        $action = (string) ($row['action'] ?? '');
        $module = (string) ($row['module'] ?? '');
        $userLabel = (string) ($row['user'] ?? 'System');
        if ($action !== '') {
            $actionOptions[$action] = true;
        }
        if ($module !== '') {
            $moduleOptions[$module] = true;
        }
        $logs[] = [
            'id' => $id,
            'user' => $userLabel,
            'role' => (string) ($row['role'] ?? ''),
            'action' => $action,
            'action_label' => ucfirst(str_replace('_', ' ', $action)),
            'icon_html' => smsIcon($actionIcons[$action] ?? 'fa-circle'),
            'detail' => (string) ($row['detail'] ?? ''),
            'module' => $module,
            'ip' => (string) ($row['ip'] ?? '—'),
            'time' => (string) ($row['time'] ?? ''),
            'log_date' => (string) ($row['log_date'] ?? ''),
        ];
    }
    ksort($actionOptions);
    ksort($moduleOptions);

    return [
        'latest_id' => $latestId,
        'synced_at' => date('M j, Y h:i:s A'),
        'stats' => [
            'total' => count($logs),
            'logins' => count(array_filter($logs, static fn(array $l): bool => $l['action'] === 'login')),
            'changes' => count(array_filter($logs, static fn(array $l): bool => in_array($l['action'], ['create', 'update', 'delete'], true))),
            'exports' => count(array_filter($logs, static fn(array $l): bool => $l['action'] === 'export')),
        ],
        'actions' => array_keys($actionOptions),
        'modules' => array_keys($moduleOptions),
        'logs' => $logs,
    ];
}
