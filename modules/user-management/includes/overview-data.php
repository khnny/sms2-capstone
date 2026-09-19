<?php
/**
 * SMS 2 - User Management overview live data.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';

header('Content-Type: application/json');

if (!isAuthenticated() || !userCanAccessModule('user-management')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

function umOverviewRoleBadgeClass(string $role, string $label = ''): string
{
    $value = strtolower(trim($role !== '' ? $role : $label));
    $value = str_replace([' ', '-'], '_', $value);
    $aliases = [
        'admin' => 'superadmin',
        'super_admin' => 'superadmin',
        'admissionoffice' => 'admission',
        'admission_office' => 'admission',
        'crad_officer' => 'crad',
        'research_grant' => 'research_grant',
        'review_committee' => 'review_committee',
        'research_coordinator' => 'research_coordinator',
        'department_head' => 'department_head',
        'departmenthead' => 'department_head',
        'department_chair' => 'department_chair',
        'research_office' => 'research_office',
        'vpaa' => 'vpaa',
        'grammarian' => 'grammarian',
        'qa_office' => 'qa',
    ];

    $value = $aliases[$value] ?? $value;
    return preg_replace('/[^a-z0-9_]/', '', $value) ?: 'student';
}

function umOverviewLastLogin(?string $lastLogin): string
{
    if (!$lastLogin) {
        return 'Never';
    }

    $ts = strtotime($lastLogin);
    if ($ts === false) {
        return 'Never';
    }

    $diff = max(0, time() - $ts);
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return ((int) floor($diff / 60)) . ' min ago';
    }
    if ($diff < 86400) {
        return ((int) floor($diff / 3600)) . ' hr ago';
    }
    if ($diff < 172800) {
        return 'Yesterday';
    }

    return date('M j, Y', $ts);
}

function umOverviewNormalizeUser(array $u): array
{
    $role = (string) ($u['role'] ?? '');
    $username = strtolower((string) ($u['username'] ?? ''));

    if ($role === 'crad_officer') {
        $u['role'] = 'crad';
    }
    if ($role === 'superadmin') {
        $u['roleLabel'] = 'Super Admin';
    }
    if ($role === 'sms_admin') {
        $u['roleLabel'] = 'Admin';
    }
    if (empty($u['roleLabel'])) {
        $u['roleLabel'] = $role;
    }
    if (
        ($role === 'admin' && $username !== 'superadmin')
        || $role === 'admission'
        || $role === 'admission_office'
    ) {
        $u['role'] = 'admission';
        $u['roleLabel'] = 'Admission';
    }
    if ($role === 'hr') {
        $u['roleLabel'] = 'Dean';
    }
    if (!empty($u['locked_until']) && strtotime((string) $u['locked_until']) > time()) {
        $u['status'] = 'locked';
    }

    return $u;
}

$pdo = db();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}

try {
    $stats = [
        'total' => (int) $pdo->query(
            "SELECT COUNT(*) FROM users
             WHERE role_key <> 'research_grant'
               AND username <> 'researchgrant'"
        )->fetchColumn(),
        'inactive' => (int) $pdo->query(
            "SELECT COUNT(*) FROM users
             WHERE status IN ('inactive', 'suspended')
               AND role_key <> 'research_grant'
               AND username <> 'researchgrant'"
        )->fetchColumn(),
        'locked' => (int) $pdo->query(
            "SELECT COUNT(*) FROM users
             WHERE (status = 'locked' OR (locked_until IS NOT NULL AND locked_until > NOW()))
               AND role_key <> 'research_grant'
               AND username <> 'researchgrant'"
        )->fetchColumn(),
        'active' => (int) $pdo->query(
            "SELECT COUNT(*) FROM users
             WHERE status = 'active'
               AND (locked_until IS NULL OR locked_until <= NOW())
               AND role_key <> 'research_grant'
               AND username <> 'researchgrant'"
        )->fetchColumn(),
    ];

    $stmt = $pdo->query(
        "SELECT u.id, u.full_name AS name, u.username, u.email, u.role_key AS role,
                r.label AS roleLabel, u.status, u.last_login_at, u.locked_until
         FROM users u
         LEFT JOIN roles r ON r.role_key = u.role_key
         WHERE u.role_key <> 'research_grant'
           AND u.username <> 'researchgrant'
         ORDER BY
            CASE WHEN u.status = 'active' THEN 0 WHEN u.status = 'locked' THEN 1 ELSE 2 END,
            COALESCE(u.last_login_at, u.created_at) DESC,
            u.id ASC
         LIMIT 10"
    );
    $users = array_map('umOverviewNormalizeUser', $stmt->fetchAll() ?: []);

    ob_start();
    if (!$users): ?>
        <tr>
            <td colspan="4" class="text-center py-5 text-muted">
                <?= smsIcon('users', ['class' => 'fa-2x mb-2 d-block opacity-50']) ?>
                No users found.
            </td>
        </tr>
    <?php else:
        $colors = ['a', 'b', 'c', 'd', 'e', 'f'];
        foreach ($users as $i => $u):
            $col = $colors[$i % count($colors)];
            $roleBadgeClass = umOverviewRoleBadgeClass((string) $u['role'], (string) $u['roleLabel']);
            $name = (string) ($u['name'] ?? 'User');
            $initial = strtoupper(substr($name, 0, 1));
            $status = (string) ($u['status'] ?? 'inactive');
            $statusLabel = $status === 'inactive' ? 'Archived' : ucfirst($status);
            $lastLogin = umOverviewLastLogin(isset($u['last_login_at']) ? (string) $u['last_login_at'] : null);
            ?>
            <tr>
                <td>
                    <div class="um-user-cell">
                        <span class="um-avatar <?= e($col) ?>"><?= e($initial) ?></span>
                        <div class="min-w-0">
                            <span class="um-user-name"><?= e($name) ?></span>
                            <span class="um-user-email"><?= e((string) ($u['email'] ?? '')) ?></span>
                        </div>
                    </div>
                </td>
                <td><span class="role-badge <?= e($roleBadgeClass) ?>"><?= e((string) $u['roleLabel']) ?></span></td>
                <td><span class="user-status <?= e($status) ?>"><?= e($statusLabel) ?></span></td>
                <td class="text-muted" style="font-size:.8rem;"><?= e($lastLogin) ?></td>
            </tr>
        <?php endforeach;
    endif;
    $usersHtml = trim((string) ob_get_clean());

    echo json_encode([
        'ok' => true,
        'stats' => $stats,
        'users_html' => $usersHtml,
    ]);
} catch (Throwable $e) {
    error_log('User Management overview live data failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not load overview data']);
}
