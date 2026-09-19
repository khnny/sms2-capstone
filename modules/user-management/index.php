<?php
/**
 * SMS 2 – User Management – Overview
 * User Management overview.
 */
$pageTitle    = 'User Management';
$activeModule = 'user-management';
$activePage   = '';
$breadcrumbs  = [
    ['label' => 'User Management', 'url' => null],
];

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../includes/layout-start.php';

/* requireSuperAdmin() is available after layout-start loads authentication.php */
requireSuperAdmin();

function umRoleBadgeClass(string $role, string $label = ''): string
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

function umFormatLastLogin(?string $lastLogin): string
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
        $mins = (int) floor($diff / 60);
        return $mins . ' min ago';
    }
    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return $hours . ' hr ago';
    }
    if ($diff < 172800) {
        return 'Yesterday';
    }

    return date('M j, Y', $ts);
}

function umNormalizeOverviewUser(array $u): array
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

$statsRaw = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'locked' => 0,
];
$overviewUsers = [];

$pdo = db();
if ($pdo) {
    try {
        $statsRaw['total'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $statsRaw['inactive'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE status IN ('inactive', 'suspended')"
        )->fetchColumn();
        $statsRaw['locked'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE status = 'locked' OR (locked_until IS NOT NULL AND locked_until > NOW())"
        )->fetchColumn();
        $statsRaw['active'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM users
             WHERE status = 'active'
               AND (locked_until IS NULL OR locked_until <= NOW())"
        )->fetchColumn();

        $stmt = $pdo->query(
            'SELECT u.id, u.full_name AS name, u.username, u.email, u.role_key AS role,
                    r.label AS roleLabel, u.status, u.last_login_at, u.locked_until
             FROM users u
             LEFT JOIN roles r ON r.role_key = u.role_key
             ORDER BY
                CASE WHEN u.status = "active" THEN 0 WHEN u.status = "locked" THEN 1 ELSE 2 END,
                COALESCE(u.last_login_at, u.created_at) DESC,
                u.id ASC
             LIMIT 10'
        );
        $overviewUsers = array_map('umNormalizeOverviewUser', $stmt->fetchAll() ?: []);
    } catch (Throwable $e) {
        error_log('User Management overview load failed: ' . $e->getMessage());
    }
}

$stats = [
    ['key' => 'total',    'label' => 'Total Users', 'value' => (string) $statsRaw['total'],    'icon' => 'users',      'type' => 'primary'],
    ['key' => 'active',   'label' => 'Active',      'value' => (string) $statsRaw['active'],   'icon' => 'user-check', 'type' => 'success'],
    ['key' => 'inactive', 'label' => 'Inactive',    'value' => (string) $statsRaw['inactive'], 'icon' => 'user-off',   'type' => 'warning'],
    ['key' => 'locked',   'label' => 'Locked Out',  'value' => (string) $statsRaw['locked'],   'icon' => 'lock',       'type' => 'info'],
];

$subpages = [
    [
        'slug'  => 'user-accounts',
        'title' => 'User Accounts',
        'icon'  => 'user-cog',
        'desc'  => 'Create and manage accounts. Use the User Archive button on that page for archived users.',
    ],
    [
        'slug'  => 'role-permissions',
        'title' => 'Role & Permissions',
        'icon'  => 'shield',
        'desc'  => 'View the role–module permission matrix and configure access per role.',
    ],
    [
        'slug'  => 'module-security',
        'title' => 'Module Security',
        'icon'  => 'lock',
        'desc'  => 'Open a module (e.g. CRAD), then use Activity Logs or Password Management (requests + reset).',
    ],
    [
        'slug'  => 'activity-logs',
        'title' => 'Activity Logs',
        'icon'  => 'history',
        'desc'  => 'Audit trail of all user logins, actions, and system events.',
    ],
    [
        'slug'  => 'system-settings',
        'title' => 'System Settings',
        'icon'  => 'adjustments-horizontal',
        'desc'  => 'Configure application name, school year, session rules, and global toggles.',
    ],
];
?>

<link href="<?= BASE_URL ?>/modules/user-management/assets/css/user-management.css?v=dept-head-badge-2" rel="stylesheet">

<?php
$pageBannerIcon        = 'user-cog';
$pageBannerDescription = 'Manage system users, roles, access permissions, activity logs, and global settings.';
renderBreadcrumbs($breadcrumbs);
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <span class="placeholder-badge"><?= smsIcon('lock', ['class' => 'me-1']) ?>User Management Access</span>
</div>

<!-- Summary stats -->
<div class="row g-3 mb-4 dashboard-stats">
    <?php foreach ($stats as $stat): ?>
        <div class="col-6 col-xl-3">
            <section class="card stat-card <?= $stat['type'] ?>">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3"><?= smsIcon($stat['icon']) ?></div>
                    <div>
                        <h6 class="text-muted mb-0 small"><?= $stat['label'] ?></h6>
                        <h4 class="mb-0 fw-bold" data-um-stat="<?= e($stat['key']) ?>"><?= $stat['value'] ?></h4>
                    </div>
                </div>
            </section>
        </div>
    <?php endforeach; ?>
</div>

<!-- Submodule cards -->
<div class="row g-3 module-button-grid mb-4">
    <?php foreach ($subpages as $page): ?>
        <?php
        $pageHref = BASE_URL . '/modules/user-management/pages/' . $page['slug'] . '.php';
        // Module Security hub: always open the All Modules picker
        if ($page['slug'] === 'module-security') {
            $pageHref .= '?picker=1';
        }
        ?>
        <div class="col-md-6">
            <a href="<?= htmlspecialchars($pageHref) ?>"
               class="text-decoration-none d-block h-100">
                <div class="card module-card hover-card h-100">
                    <div class="card-body d-flex align-items-start gap-3 p-4">
                        <div class="card-icon flex-shrink-0">
                            <?= smsIcon($page['icon'], ['aria-hidden' => 'true']) ?>
                        </div>
                        <div class="min-w-0">
                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($page['title']) ?></h6>
                            <small class="text-muted" style="font-size:.78rem;line-height:1.4;">
                                <?= htmlspecialchars($page['desc']) ?>
                            </small>
                        </div>
                        <?= smsIcon('chevron-right', ['class' => 'text-sms-primary ms-auto mt-1 flex-shrink-0', 'style' => 'font-size:.7rem;opacity:.6;']) ?>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<!-- Quick-access recent users -->
<section class="card">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <h5 class="card-title fw-semibold mb-0">
                <?= smsIcon('users', ['class' => 'text-sms-primary me-2']) ?>System Users
            </h5>
            <a href="<?= BASE_URL ?>/modules/user-management/pages/user-accounts.php"
               class="btn btn-sm btn-outline-primary">
                <?= smsIcon('arrow-right', ['class' => 'me-1']) ?>Manage All
            </a>
        </div>
        <div class="table-responsive">
            <table class="table submodule-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody id="umOverviewUsersBody">
                    <?php if (!$overviewUsers): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <?= smsIcon('users', ['class' => 'fa-2x mb-2 d-block opacity-50']) ?>
                            No users found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php
                    $colors = ['a', 'b', 'c', 'd', 'e', 'f'];
                    foreach ($overviewUsers as $i => $u):
                        $col = $colors[$i % count($colors)];
                        $roleBadgeClass = umRoleBadgeClass((string) $u['role'], (string) $u['roleLabel']);
                        $name = (string) ($u['name'] ?? 'User');
                        $initial = strtoupper(substr($name, 0, 1));
                        $status = (string) ($u['status'] ?? 'inactive');
                        $statusLabel = $status === 'inactive' ? 'Archived' : ucfirst($status);
                        $lastLogin = umFormatLastLogin(isset($u['last_login_at']) ? (string) $u['last_login_at'] : null);
                    ?>
                    <tr>
                        <td>
                            <div class="um-user-cell">
                                <span class="um-avatar <?= $col ?>"><?= e($initial) ?></span>
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
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
(function () {
    'use strict';

    var endpoint = '<?= BASE_URL ?>/modules/user-management/includes/overview-data.php';
    var body = document.getElementById('umOverviewUsersBody');
    if (!body || !window.fetch) return;

    function applyOverview(data) {
        if (!data || !data.ok) return;
        if (data.stats) {
            Object.keys(data.stats).forEach(function (key) {
                var el = document.querySelector('[data-um-stat="' + key + '"]');
                if (el) el.textContent = data.stats[key];
            });
        }
        if (typeof data.users_html === 'string') {
            body.innerHTML = data.users_html;
        }
    }

    function refreshOverview() {
        fetch(endpoint, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(applyOverview)
            .catch(function () {});
    }

    refreshOverview();
    setInterval(refreshOverview, 10000);
})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
