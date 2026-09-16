<?php
/**
 * SMS 2 – User Management – User Accounts (database-backed)
 * Active list by default; ?view=archive shows archived accounts in-page.
 */
require_once __DIR__ . '/../../../config/config.php';

$isArchiveView = (($_GET['view'] ?? '') === 'archive');
$pageTitle     = $isArchiveView ? 'User Archive' : 'User Accounts';
$activeModule  = 'user-management';
$activePage    = 'user-accounts';
$breadcrumbs   = [
    ['label' => 'User Management', 'url' => BASE_URL . '/modules/user-management/index.php'],
    ['label' => 'User Accounts',   'url' => $isArchiveView ? BASE_URL . '/modules/user-management/pages/user-accounts.php' : null],
];
if ($isArchiveView) {
    $breadcrumbs[] = ['label' => 'Archive', 'url' => null];
}

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
require_once ROOT_PATH . '/includes/security-ui.php';
require_once ROOT_PATH . '/includes/security-workflow.php';
requireSuperAdmin();

$minPasswordLen = (int) smsSetting('min_password_length', '8');

$users = [];
$archivedCount = 0;
$activeCount = 0;
$pdo = db();
if ($pdo) {
    try {
        $pdo->prepare(
            "INSERT IGNORE INTO roles (role_key, label, description, is_system)
             VALUES
                ('superadmin', 'Super Admin', 'Full system access', 1),
                ('admin', 'Super Admin', 'Legacy super admin access', 1),
                ('sms_admin', 'Admin', 'General administrator account', 1),
                ('research_coordinator', 'Research Coordinator', 'Research coordination access', 1),
                ('department_chair', 'Department Chair', 'Grant approval department chair sign-off', 1),
                ('research_office', 'Research Office', 'Grant approval research office sign-off', 1),
                ('vpaa', 'VPAA', 'Grant approval VPAA sign-off', 1),
                ('adviser', 'Adviser', 'Research adviser faculty account', 1),
                ('research_director', 'Research Director', 'Research defense scheduling director account', 1),
                ('grammarian', 'Grammarian', 'Research grammar and manuscript evaluation account', 1),
                ('panel', 'Panel Member', 'Research defense panel account', 1),
                ('research_grant', 'CRAD Officer', 'Research grant management access', 1),
                ('review_committee', 'Review Committee', 'Grant proposal review and rubric evaluation', 1)"
        )->execute();
        $pdo->prepare(
            "INSERT INTO role_permissions (role_key, module_key, granted)
             VALUES ('department_chair', 'crad', 1)
             ON DUPLICATE KEY UPDATE granted = VALUES(granted)"
        )->execute();
        $pdo->prepare(
            "INSERT INTO role_permissions (role_key, module_key, granted)
             VALUES ('research_office', 'crad', 1)
             ON DUPLICATE KEY UPDATE granted = VALUES(granted)"
        )->execute();
        $pdo->prepare(
            "INSERT INTO role_permissions (role_key, module_key, granted)
             VALUES ('vpaa', 'accreditation', 1)
             ON DUPLICATE KEY UPDATE granted = VALUES(granted)"
        )->execute();
        $pdo->prepare(
            "UPDATE users SET role_key = 'department_chair' WHERE username = 'deptchair' LIMIT 1"
        )->execute();
        $pdo->prepare(
            "UPDATE users SET role_key = 'research_office' WHERE username = 'researchoffice' LIMIT 1"
        )->execute();
        $pdo->prepare(
            "UPDATE users SET role_key = 'vpaa' WHERE username = 'vpaa' LIMIT 1"
        )->execute();
        $pdo->prepare(
            "UPDATE roles
             SET label = 'Super Admin', description = 'Legacy super admin access'
             WHERE role_key = 'admin'"
        )->execute();
        $pdo->prepare(
            "UPDATE users
             SET role_key = 'superadmin'
             WHERE username = 'superadmin'
               AND role_key = 'admin'"
        )->execute();
        $pdo->prepare(
            "UPDATE users
             SET full_name = 'Dean', username = 'dean', email = 'dean@bestlink.edu.ph'
             WHERE role_key = 'hr'
               AND username IN ('hr', 'faculty', 'dean')"
        )->execute();
        $adminHash = password_hash('@admin123', PASSWORD_DEFAULT);
        $pdo->prepare(
            "INSERT IGNORE INTO users
                (username, email, password_hash, full_name, role_key, student_id, status, password_changed_at, must_change_password, failed_login_attempts, locked_until)
             VALUES
                ('admin', 'admin@bestlink.edu.ph', ?, 'Admin', 'sms_admin', NULL, 'active', NOW(), 1, 0, NULL)"
        )->execute([$adminHash]);
        $insAdminPerm = $pdo->prepare(
            "INSERT INTO role_permissions (role_key, module_key, granted)
             VALUES ('sms_admin', ?, 1)
             ON DUPLICATE KEY UPDATE granted = VALUES(granted)"
        );
        foreach (['enrollment','registrar','curriculum','accreditation','payment','faculty','scheduling','cocurricular','lms','crad'] as $m) {
            $insAdminPerm->execute([$m]);
        }
        $facultyHash = password_hash('@faculty123', PASSWORD_DEFAULT);
        $seedFaculty = $pdo->prepare(
            "INSERT IGNORE INTO users
                (username, email, password_hash, full_name, role_key, student_id, status, notes, password_changed_at, must_change_password, failed_login_attempts, locked_until)
             VALUES
                (?, ?, ?, ?, ?, NULL, 'active', ?, NOW(), 1, 0, NULL)"
        );
        $seedFaculty->execute(['rsantos', 'rsantos@bestlink.edu.ph', password_hash('@Adviser123', PASSWORD_DEFAULT), 'Dr. Roberto M. Santos', 'adviser', 'Research Adviser']);
        $seedFaculty->execute(['researchdirector', 'researchdirector@bestlink.edu.ph', password_hash('@Director123', PASSWORD_DEFAULT), 'Research Director', 'research_director', 'Research Director']);
        $seedFaculty->execute(['grammarian', 'grammarian@bestlink.edu.ph', password_hash('@Grammarian123', PASSWORD_DEFAULT), 'Grammarian', 'grammarian', 'Research grammar and manuscript evaluator']);
        $seedFaculty->execute(['jobertvalentino', 'jobertvalentino@bestlink.edu.ph', password_hash('@Adviser123', PASSWORD_DEFAULT), 'Dr. Jobert Valentino', 'panel', 'Panel Member']);
        $seedFaculty->execute(['jonathanestrada', 'jonathanestrada@bestlink.edu.ph', password_hash('@Adviser123', PASSWORD_DEFAULT), 'Dr. Jonathan Estrada', 'panel', 'Panel Member']);
        $seedFaculty->execute(['michelleguevarra', 'michelleguevarra@bestlink.edu.ph', password_hash('@Adviser123', PASSWORD_DEFAULT), 'Dr. Michelle Guevarra', 'panel', 'Panel Member']);
        $insFacultyPerm = $pdo->prepare(
            "INSERT INTO role_permissions (role_key, module_key, granted)
             VALUES (?, 'faculty', 1)
             ON DUPLICATE KEY UPDATE granted = VALUES(granted)"
        );
        foreach (['adviser', 'research_director', 'grammarian', 'panel'] as $facultyRole) {
            $insFacultyPerm->execute([$facultyRole]);
        }

        // Research Grant account (CRAD Officer role)
        $rgHash = password_hash('@Grant123', PASSWORD_DEFAULT);
        $pdo->prepare(
            "INSERT IGNORE INTO users
                (username, email, password_hash, full_name, role_key, student_id, status, password_changed_at, must_change_password, failed_login_attempts, locked_until)
             VALUES
                ('researchgrant', 'researchgrant@bestlink.edu.ph', ?, 'Research Grant', 'research_grant', NULL, 'active', NOW(), 1, 0, NULL)"
        )->execute([$rgHash]);
        $pdo->prepare(
            "INSERT INTO role_permissions (role_key, module_key, granted)
             VALUES ('research_grant', 'crad_grant', 1)
             ON DUPLICATE KEY UPDATE granted = VALUES(granted)"
        )->execute();

        // Review Committee account (grant proposal evaluator)
        $rcHash = password_hash('@Committee123', PASSWORD_DEFAULT);
        $pdo->prepare(
            "INSERT IGNORE INTO users
                (username, email, password_hash, full_name, role_key, student_id, status, password_changed_at, must_change_password, failed_login_attempts, locked_until)
             VALUES
                ('reviewcommittee', 'reviewcommittee@bestlink.edu.ph', ?, 'Review Committee Member', 'review_committee', NULL, 'active', NOW(), 1, 0, NULL)"
        )->execute([$rcHash]);
        $pdo->prepare(
            "INSERT INTO role_permissions (role_key, module_key, granted)
             VALUES ('review_committee', 'crad_grant', 1)
             ON DUPLICATE KEY UPDATE granted = VALUES(granted)"
        )->execute();
    } catch (Throwable $e) {
        error_log('Default user account ensure failed: ' . $e->getMessage());
    }

    if ($isArchiveView) {
        $stmt = $pdo->query(
            'SELECT u.id, u.full_name AS name, u.username, u.email, u.role_key AS role,
                    r.label AS roleLabel, u.status,
                    DATE_FORMAT(u.created_at, "%b %e, %Y") AS created,
                    IFNULL(DATE_FORMAT(u.last_login_at, "%b %e, %Y %H:%i"), "—") AS last_login
             FROM users u
             INNER JOIN roles r ON r.role_key = u.role_key
             WHERE u.status IN (\'inactive\', \'suspended\')
             ORDER BY u.full_name ASC'
        );
        $users = $stmt->fetchAll() ?: [];
        $archivedCount = count($users);
        $activeCount = (int) $pdo->query(
            'SELECT COUNT(*) FROM users WHERE status NOT IN (\'inactive\', \'suspended\')'
        )->fetchColumn();
    } else {
        $stmt = $pdo->query(
            'SELECT u.id, u.full_name AS name, u.username, u.email, u.role_key AS role,
                    r.label AS roleLabel, u.status,
                    DATE_FORMAT(u.created_at, "%b %e, %Y") AS created,
                    IFNULL(DATE_FORMAT(u.last_login_at, "%b %e, %Y %H:%i"), "—") AS last_login
             FROM users u
             INNER JOIN roles r ON r.role_key = u.role_key
             WHERE u.status NOT IN (\'inactive\', \'suspended\')
             ORDER BY u.id ASC'
        );
        $users = $stmt->fetchAll() ?: [];
        $archivedCount = (int) $pdo->query(
            'SELECT COUNT(*) FROM users WHERE status IN (\'inactive\', \'suspended\')'
        )->fetchColumn();
    }
}

foreach ($users as &$u) {
    if ($u['role'] === 'crad_officer') {
        $u['role'] = 'crad';
    }
    if ($u['role'] === 'superadmin') {
        $u['roleLabel'] = 'Super Admin';
    }
    if ($u['role'] === 'sms_admin') {
        $u['roleLabel'] = 'Admin';
    }
    if (
        ($u['role'] === 'admin' && strtolower((string) $u['username']) !== 'superadmin')
        || $u['role'] === 'admission'
        || $u['role'] === 'admission_office'
    ) {
        $u['role'] = 'admission';
        $u['roleLabel'] = 'Admission';
        $u['name'] = 'Admission';
        $u['username'] = 'admission';
        $u['email'] = 'admission@bestlink.edu.ph';
    }
    if ($u['role'] === 'hr') {
        $u['roleLabel'] = 'Dean';
        if (in_array(trim((string) $u['name']), ['HR', 'Faculty'], true)) {
            $u['name'] = 'Dean';
        }
        if (strtolower(trim((string) $u['email'])) === 'hr@bestlink.edu.ph') {
            $u['email'] = 'dean@bestlink.edu.ph';
        }
    }
    if ($u['role'] === 'research_director') {
        $u['roleLabel'] = 'Research Director';
    }
    if ($u['role'] === 'grammarian') {
        $u['roleLabel'] = 'Grammarian';
    }
    if ($u['role'] === 'panel') {
        $u['roleLabel'] = 'Panel Member';
    }
    if ($u['role'] === 'review_committee') {
        $u['roleLabel'] = 'Review Committee';
    }
}
unset($u);

function umRoleBadgeClass(string $role, string $label = ''): string
{
    $value = strtolower(trim($role !== '' ? $role : $label));
    $value = str_replace([' ', '-'], '_', $value);

    $aliases = [
        'admin' => 'superadmin',
        'super_admin' => 'superadmin',
        'sms_admin' => 'sms_admin',
        'admissionoffice' => 'admission',
        'admission_office' => 'admission',
        'crad_officer' => 'crad',
        'research_grant' => 'research_grant',
        'review_committee' => 'review_committee',
        'research_coordinator' => 'research_coordinator',
        'department_chair' => 'department_chair',
        'research_office' => 'research_office',
        'vpaa' => 'vpaa',
        'research_director' => 'research_director',
        'grammarian' => 'grammarian',
        'panel' => 'panel',
        'qa_office' => 'qa',
    ];

    $value = $aliases[$value] ?? $value;
    return preg_replace('/[^a-z0-9_]/', '', $value) ?: 'student';
}

$avatarColors = ['a', 'b', 'c', 'd', 'e', 'f'];
$csrf = csrfToken();
$total = count($users);
$facultyAccountRoles = ['hr', 'adviser', 'grammarian', 'panel'];
$facultyUsers = array_values(array_filter($users, fn($u) => in_array($u['role'], $facultyAccountRoles, true)));
$studentUsers = array_values(array_filter($users, fn($u) => $u['role'] === 'student'));
$systemUsers = array_values(array_filter($users, fn($u) => !in_array($u['role'], array_merge($facultyAccountRoles, ['student']), true)));
$accountsUrl = BASE_URL . '/modules/user-management/pages/user-accounts.php';
$archiveUrl  = $accountsUrl . '?view=archive';
$currentUserId = (int) getCurrentUserId();
?>

<link href="<?= BASE_URL ?>/modules/user-management/assets/css/user-management.css?v=grant-role-badges-1" rel="stylesheet">
<meta name="csrf-token" content="<?= e($csrf) ?>">

<?php
$pageBannerIcon        = $isArchiveView ? 'fa-archive' : 'fa-user-cog';
$pageBannerDescription = $isArchiveView
    ? 'Archived accounts stay here inside User Accounts. Restore them, or permanently delete when needed.'
    : 'Active accounts only. Archive moves users here into the Archive view — not a separate module.';
renderBreadcrumbs($breadcrumbs);
?>

<div id="umToastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index:1100;"></div>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <?php if ($isArchiveView): ?>
            <a href="<?= e($accountsUrl) ?>" class="um-archive-btn um-archive-btn--back">
                <?= smsIcon('users') ?>
                <span>Active Accounts</span>
                <?php if ($activeCount > 0): ?>
                    <span class="um-archive-count"><?= $activeCount ?></span>
                <?php endif; ?>
            </a>
        <?php else: ?>
            <a href="<?= e($archiveUrl) ?>" class="um-archive-btn">
                <?= smsIcon('archive') ?>
                <span>User Archive</span>
                <?php if ($archivedCount > 0): ?>
                    <span class="um-archive-count"><?= $archivedCount ?></span>
                <?php endif; ?>
            </a>
            <button type="button" class="btn btn-sms-primary"
                    data-bs-toggle="modal" data-bs-target="#umUserModal"
                    data-um-action="add">
                <?= smsIcon('user-plus', ['class' => 'me-2']) ?>Add User
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isArchiveView): ?>
<!-- Stats row -->
<div class="row g-3 mb-4 dashboard-stats">
    <?php
    $active = count(array_filter($users, fn($u) => $u['status'] === 'active'));
    $locked = count(array_filter($users, fn($u) => $u['status'] === 'locked'));
    $statCards = [
        ['label' => 'Active List', 'value' => $total,         'icon' => 'users',      'type' => 'primary'],
        ['label' => 'Active',      'value' => $active,        'icon' => 'user-check', 'type' => 'success'],
        ['label' => 'Locked Out',  'value' => $locked,        'icon' => 'lock',       'type' => 'info'],
        ['label' => 'In Archive',  'value' => $archivedCount, 'icon' => 'archive',    'type' => 'warning'],
    ];
    foreach ($statCards as $sc): ?>
        <div class="col-6 col-xl-3">
            <section class="card stat-card <?= $sc['type'] ?>">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3"><?= smsIcon($sc['icon']) ?></div>
                    <div>
                        <h6 class="text-muted mb-0 small"><?= $sc['label'] ?></h6>
                        <h4 class="mb-0 fw-bold"><?= $sc['value'] ?></h4>
                    </div>
                </div>
            </section>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- User table -->
<section class="sms-table-wrap mb-3">
    <div class="sms-table-toolbar um-filter-bar">
        <div class="flex-grow-1" style="min-width:180px;max-width:320px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><?= smsIcon('search', ['style' => 'font-size:.72rem;']) ?></span>
                <input type="text" id="umSearch" class="form-control form-control-sm"
                       placeholder="<?= $isArchiveView ? 'Search archived users…' : 'Search name, username or email…' ?>"
                       style="max-width:unset;">
            </div>
        </div>
        <?php if (!$isArchiveView): ?>
        <select id="umRoleFilter" class="form-select form-select-sm">
            <option value="">All Roles</option>
            <option value="superadmin">Super Admin</option>
            <option value="sms_admin">Admin</option>
            <option value="admission">Admission</option>
            <option value="registrar">Registrar</option>
            <option value="finance">Finance</option>
            <option value="hr">Dean</option>
            <option value="adviser">Adviser</option>
            <option value="research_director">Research Director</option>
            <option value="panel">Panel Member</option>
            <option value="it_office">IT Office</option>
            <option value="osa">OSA</option>
            <option value="qa">QA Office</option>
            <option value="crad">CRAD Officer</option>
            <option value="research_coordinator">Research Coordinator</option>
            <option value="department_chair">Department Chair</option>
            <option value="research_office">Research Office</option>
            <option value="vpaa">VPAA</option>
            <option value="research_grant">Research Grant</option>
            <option value="review_committee">Review Committee</option>
            <option value="student">Student</option>
        </select>
        <select id="umStatusFilter" class="form-select form-select-sm">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="locked">Locked</option>
        </select>
        <?php endif; ?>
        <span class="ms-auto text-muted" style="font-size:.78rem;white-space:nowrap;">
            <?= $total ?> <?= $isArchiveView ? 'archived' : 'users' ?>
        </span>
    </div>
    <div class="table-responsive sms-table--responsive">
        <table class="table submodule-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="padding-left:1.2rem;">User</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Created</th>
                        <th class="text-end" style="padding-right:1.2rem;">Actions</th>
                    </tr>
                </thead>
                <tbody id="umTableBody">
                    <?php if (!$users): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <?php if ($isArchiveView): ?>
                                    <?= smsIcon('archive', ['class' => 'um-empty-icon mb-2 d-block opacity-50']) ?>
                                    Archive is empty. Archived users from User Accounts will appear here.
                                <?php else: ?>
                                    <?= smsIcon('users', ['class' => 'um-empty-icon mb-2 d-block opacity-50']) ?>
                                    No active users yet.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ([
                            ['label' => 'System Accounts', 'users' => $systemUsers],
                            ['label' => 'Faculty Accounts', 'users' => $facultyUsers],
                            ['label' => 'Students Account', 'users' => $studentUsers],
                        ] as $group): ?>
                            <?php if (empty($group['users'])) continue; ?>
                            <tr class="um-group-row" data-group-row>
                                <td colspan="7">
                                    <div class="um-group-title">
                                        <span><?= e($group['label']) ?></span>
                                        <small><?= count($group['users']) ?> account<?= count($group['users']) === 1 ? '' : 's' ?></small>
                                    </div>
                                </td>
                            </tr>
                        <?php foreach ($group['users'] as $i => $u):
                            $col = $avatarColors[$i % count($avatarColors)];
                            $statusLabel = $u['status'] === 'inactive' ? 'Archived' : ucfirst($u['status']);
                            $roleBadgeClass = umRoleBadgeClass((string) $u['role'], (string) $u['roleLabel']);
                        ?>
                        <tr class="um-user-row"
                            data-name="<?= htmlspecialchars($u['name']) ?>"
                            data-username="<?= htmlspecialchars($u['username']) ?>"
                            data-email="<?= htmlspecialchars($u['email']) ?>"
                            data-role="<?= htmlspecialchars($u['role']) ?>"
                            data-status="<?= htmlspecialchars($u['status']) ?>">
                            <td style="padding-left:1.2rem;">
                                <div class="um-user-cell">
                                    <span class="um-avatar <?= $col ?>"><?= strtoupper($u['name'][0]) ?></span>
                                    <div class="min-w-0">
                                        <span class="um-user-name"><?= htmlspecialchars($u['name']) ?></span>
                                        <span class="um-user-email"><?= htmlspecialchars($u['email']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><code style="font-size:.78rem;color:var(--sms-text-muted);"><?= htmlspecialchars($u['username']) ?></code></td>
                            <td><span class="role-badge <?= e($roleBadgeClass) ?>"><?= htmlspecialchars($u['roleLabel']) ?></span></td>
                            <td><span class="user-status <?= htmlspecialchars($u['status']) ?>"><?= e($statusLabel) ?></span></td>
                            <td class="text-muted" style="font-size:.78rem;white-space:nowrap;"><?= htmlspecialchars($u['last_login']) ?></td>
                            <td class="text-muted" style="font-size:.78rem;white-space:nowrap;"><?= htmlspecialchars($u['created']) ?></td>
                            <td class="text-end" style="padding-right:1.2rem;">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <?php if ($isArchiveView): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-success px-2 py-1 um-set-status"
                                                title="Restore to active list"
                                                data-uid="<?= (int) $u['id'] ?>"
                                                data-status="active"
                                                data-um-confirm-type="warning"
                                                data-um-confirm="Restore <?= e($u['name']) ?> back to User Accounts?">
                                            <?= smsIcon('undo', ['style' => 'font-size:.7rem;']) ?>
                                        </button>
                                        <?php if ((int) $u['id'] !== $currentUserId): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger px-2 py-1 um-delete-user"
                                                title="Permanently delete"
                                                data-uid="<?= (int) $u['id'] ?>"
                                                data-um-confirm="Permanently delete <?= e($u['name']) ?> from the archive? This cannot be undone.">
                                            <?= smsIcon('trash', ['style' => 'font-size:.7rem;']) ?>
                                        </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary px-2 py-1"
                                                title="Edit user"
                                                data-bs-toggle="modal"
                                                data-bs-target="#umUserModal"
                                                data-um-action="edit"
                                                data-uid="<?= $u['id'] ?>"
                                                data-name="<?= htmlspecialchars($u['name']) ?>"
                                                data-username="<?= htmlspecialchars($u['username']) ?>"
                                                data-email="<?= htmlspecialchars($u['email']) ?>"
                                                data-role="<?= htmlspecialchars($u['role']) ?>"
                                                data-status="<?= htmlspecialchars($u['status']) ?>">
                                            <?= smsIcon('pen', ['style' => 'font-size:.7rem;']) ?>
                                        </button>
                                        <?php if ($u['status'] === 'locked'): ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-success px-2 py-1 um-set-status"
                                                title="Unlock user"
                                                data-uid="<?= (int) $u['id'] ?>"
                                                data-status="active"
                                                data-um-confirm-type="warning"
                                                data-um-confirm="Unlock <?= e($u['name']) ?>?">
                                            <?= smsIcon('unlock', ['style' => 'font-size:.7rem;']) ?>
                                        </button>
                                        <?php endif; ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning px-2 py-1 um-set-status"
                                                title="Move to Archive"
                                                data-uid="<?= (int) $u['id'] ?>"
                                                data-status="inactive"
                                                data-um-confirm-type="warning"
                                                data-um-confirm="Move <?= e($u['name']) ?> to User Archive? They leave this list and can be restored later.">
                                            <?= smsIcon('archive', ['style' => 'font-size:.7rem;']) ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <tr id="umNoResults" style="display:none;">
                <td colspan="7" class="text-center py-5 text-muted sms-table-empty">
                    <?= smsIcon('search-minus', ['class' => 'um-empty-icon mb-2 d-block opacity-50']) ?>No users match your filters.
                </td>
            </tr>
        </div>
</section>

<?php if (!$isArchiveView): ?>
<!-- ── Add / Edit User Modal ─────────────────────────────────── -->
<div class="modal fade sms-form-modal um-user-modal" id="umUserModal" tabindex="-1" aria-labelledby="umModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="umModalTitle">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="umUserForm" action="<?= BASE_URL ?>/modules/user-management/includes/save-user.php" method="POST" novalidate>
                <input type="hidden" name="user_id">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="action" value="save">
                <div class="modal-body">
                    <div class="um-modal-avatar-row mb-3">
                        <div class="um-modal-avatar">?</div>
                        <small class="text-muted">Avatar auto-generated from name</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="full_name" placeholder="e.g. Maria Santos" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username" placeholder="e.g. msantos" required autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" placeholder="user@bestlink.edu.ph" required>
                        </div>
                        <div class="col-md-6 um-pw-row">
                            <label class="form-label fw-semibold um-pw-label">Password <span class="text-danger um-pw-required">*</span></label>
                            <?= smsPasswordInput([
                                'id' => 'um_password',
                                'name' => 'password',
                                'placeholder' => '••••••••',
                                'required' => true,
                                'minlength' => $minPasswordLen,
                                'autocomplete' => 'new-password',
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" required>
                                <option value="">Select role…</option>
                                <option value="superadmin">Super Admin</option>
                                <option value="sms_admin">Admin</option>
                                <option value="admission">Admission</option>
                                <option value="registrar">Registrar</option>
                                <option value="finance">Finance</option>
                                <option value="hr">Dean</option>
                                <option value="adviser">Adviser</option>
                                <option value="research_director">Research Director</option>
                                <option value="grammarian">Grammarian</option>
                                <option value="panel">Panel Member</option>
                                <option value="it_office">IT Office</option>
                                <option value="osa">OSA</option>
                                <option value="qa">QA Office</option>
                                <option value="crad">CRAD Officer</option>
                                <option value="research_coordinator">Research Coordinator</option>
            <option value="department_chair">Department Chair</option>
            <option value="research_office">Research Office</option>
            <option value="vpaa">VPAA</option>
                                <option value="research_grant">Research Grant (CRAD Officer)</option>
                                <option value="review_committee">Review Committee</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="col-12 um-pw-strength-row">
                            <?= smsPasswordStrengthMarkup('um_password') ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="locked">Locked</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Any notes about this account…"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sms-primary">
                        <?= smsIcon('save', ['class' => 'me-2']) ?>Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="<?= BASE_URL ?>/modules/user-management/assets/js/user-management.js?v=20260831"></script>
<script>
(function () {
    var ENDPOINT = '<?= BASE_URL ?>/modules/user-management/includes/save-user.php';
    var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var ACCOUNTS = '<?= e($accountsUrl) ?>';
    var ARCHIVE = '<?= e($archiveUrl) ?>';
    var IS_ARCHIVE = <?= $isArchiveView ? 'true' : 'false' ?>;

    function postJson(payload) {
        return fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(Object.assign({ csrf_token: CSRF }, payload))
        }).then(function (r) { return r.json(); });
    }

    function passwordMeetsPolicy(form, password) {
        if (!password) return false;
        var box = form.querySelector('.pw-strength');
        var minLen = box ? parseInt(box.getAttribute('data-pw-min') || '8', 10) : 8;
        return password.length >= minLen
            && /[A-Z]/.test(password)
            && /[a-z]/.test(password)
            && /[0-9]/.test(password)
            && /[^A-Za-z0-9]/.test(password);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('umUserForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var fd = new FormData(form);
                var userId = fd.get('user_id') || '';
                var password = fd.get('password') || '';
                if (!userId && !password) {
                    if (typeof umShowToast === 'function') umShowToast('Password is required for new users.', 'danger');
                    else alert('Password is required for new users.');
                    return;
                }
                if ((!userId || password) && password && !passwordMeetsPolicy(form, password)) {
                    if (typeof umShowToast === 'function') umShowToast('Password does not meet security requirements.', 'danger');
                    else alert('Password does not meet security requirements.');
                    return;
                }
                var payload = {
                    action: 'save',
                    user_id: fd.get('user_id') || '',
                    full_name: fd.get('full_name'),
                    username: fd.get('username'),
                    email: fd.get('email'),
                    role: fd.get('role'),
                    status: fd.get('status'),
                    password: fd.get('password') || '',
                    notes: fd.get('notes') || ''
                };
                postJson(payload).then(function (data) {
                    if (data.ok) {
                        location.href = ACCOUNTS + '?' + (data.created ? 'created=1' : 'updated=1');
                    } else if (typeof umShowToast === 'function') {
                        umShowToast(data.error || 'Save failed', 'danger');
                    } else {
                        alert(data.error || 'Save failed');
                    }
                }).catch(function () {
                    if (typeof umShowToast === 'function') umShowToast('Network error', 'danger');
                });
            });
        }

        document.querySelectorAll('.um-set-status').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (btn.hasAttribute('data-um-confirm')) return;
                var uid = btn.dataset.uid;
                var status = btn.dataset.status || 'inactive';
                postJson({ action: 'set_status', user_id: uid, status: status }).then(function (data) {
                    if (data.ok) {
                        if (status === 'inactive' || status === 'suspended') {
                            location.href = ARCHIVE + '&archived=1';
                        } else if (IS_ARCHIVE) {
                            location.href = ARCHIVE + '&restored=1';
                        } else {
                            location.href = ACCOUNTS + '?restored=1';
                        }
                    } else if (typeof umShowToast === 'function') {
                        umShowToast(data.error || 'Status update failed', 'danger');
                    }
                });
            });
        });

        document.querySelectorAll('.um-delete-user').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (btn.hasAttribute('data-um-confirm')) return;
                postJson({ action: 'delete', user_id: btn.dataset.uid }).then(function (data) {
                    if (data.ok) location.href = ARCHIVE + '&purged=1';
                    else if (typeof umShowToast === 'function') umShowToast(data.error || 'Delete failed', 'danger');
                });
            });
        });
    });
})();
</script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
