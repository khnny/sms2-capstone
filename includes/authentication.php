<?php
/**
 * SMS 2 - Authentication & Authorization (Phase 2 – database-backed)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/security-workflow.php';

smsEnforceSessionTimeout();

/**
 * True when the database has zero users (first-time setup required).
 */
function smsNeedsSetup(): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }
    try {
        $count = (int) $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
        return $count === 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Map UI / matrix role keys to session role keys.
 */
function smsNormalizeRoleKey(string $roleKey): string
{
    if ($roleKey === 'admin') {
        return 'superadmin';
    }
    if ($roleKey === 'crad') {
        return 'crad_officer';
    }
    if ($roleKey === 'admissionoffice' || $roleKey === 'admission_office') {
        return 'admission';
    }
    return $roleKey;
}

/**
 * Role keys to consult in role_permissions (session key + matrix aliases).
 *
 * @return list<string>
 */
function smsRolePermissionLookupKeys(string $roleKey): array
{
    $normalized = smsNormalizeRoleKey($roleKey);
    $keys = [$normalized];
    $matrixKey = smsMatrixRoleKey($normalized);
    if ($matrixKey !== $normalized) {
        $keys[] = $matrixKey;
    }

    return array_values(array_unique($keys));
}

/**
 * Operational Admin (sms_admin) or Super Admin — not a substitute for module access.
 */
function smsIsGrantedAdminRole(string $roleKey): bool
{
    $normalized = smsNormalizeRoleKey($roleKey);

    return in_array($normalized, ['superadmin', 'sms_admin'], true);
}

function smsIsPanelDefenseRole(?string $roleKey = null): bool
{
    $roleKey = smsNormalizeRoleKey($roleKey ?? getCurrentUserRoleKey());

    return in_array($roleKey, ['panel', 'department_chair'], true);
}

function smsPanelDefenseWorkflowPaths(): array
{
    return [
        '/modules/faculty/pages/assigned-defenses.php',
        '/modules/faculty/pages/defense-details.php',
        '/modules/faculty/pages/panel-evaluation-scoring.php',
        '/modules/faculty/pages/panel-evaluation-history.php',
        '/modules/faculty/pages/panel-final-defense-evaluation.php',
        '/modules/faculty/api/panel-defense.php',
    ];
}

/**
 * DB-granted module access for Admin / Super Admin roles (replaces legacy admin checks).
 */
function smsHasGrantedModuleAdminAccess(string $moduleKey): bool
{
    if ($moduleKey === '' || $moduleKey === 'dashboard') {
        return smsIsGrantedAdminRole(getCurrentUserRoleKey());
    }

    return smsIsGrantedAdminRole(getCurrentUserRoleKey())
        && userCanAccessModule($moduleKey);
}

/**
 * True when the session role is listed or has DB-granted Admin module access.
 */
function smsRoleAllowedForModule(array $allowedRoles, string $moduleKey): bool
{
    $roleKey = getCurrentUserRoleKey();
    $normalized = smsNormalizeRoleKey($roleKey);
    if (in_array($roleKey, $allowedRoles, true) || in_array($normalized, $allowedRoles, true)) {
        return true;
    }

    return smsHasGrantedModuleAdminAccess($moduleKey);
}

/**
 * Bypass maintenance / force-logout for system operators and User Management staff.
 */
function smsCanBypassSystemControls(?string $roleKey = null): bool
{
    $roleKey = $roleKey ?? getCurrentUserRoleKey();
    if (smsIsGrantedAdminRole($roleKey)) {
        return true;
    }

    return in_array('user-management', smsAllowedModuleKeysForRole($roleKey), true);
}

/**
 * Map session role key to permission-matrix key.
 */
function smsMatrixRoleKey(string $roleKey): string
{
    return $roleKey === 'crad_officer' ? 'crad' : $roleKey;
}

function isAuthenticated(): bool
{
    return !empty($_SESSION['user_id']);
}

function requireAuth(): void
{
    if (!isAuthenticated()) {
        require_once __DIR__ . '/module-controls.php';
        if (function_exists('smsIsSystemInMaintenance') && smsIsSystemInMaintenance()) {
            header('Location: ' . BASE_URL . '/account/maintenance.php');
            exit;
        }
        header('Location: ' . BASE_URL . '/login/login.php');
        exit;
    }
    // Keep the signed-in name/role in sync with User Accounts edits.
    smsRefreshCurrentUserSession();
    // Mark online first so status stays accurate even if we redirect next
    smsTouchUserPresence();
    require_once __DIR__ . '/module-controls.php';
    smsEnforceSystemMaintenance();
    smsEnforceModuleForceLogout();
    smsEnforcePrimaryModuleMaintenance();
}

function getCurrentUserName(): string
{
    return $_SESSION['user_name'] ?? 'User';
}

function getCurrentUserRole(): string
{
    return $_SESSION['user_role'] ?? 'User';
}

function getCurrentUserRoleKey(): string
{
    return $_SESSION['user_role_key'] ?? '';
}

function getCurrentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Reload the signed-in user's name, email, and role from the users table
 * so Super Admin edits appear on the live account without re-login.
 */
function smsRefreshCurrentUserSession(): void
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return;
    }

    $pdo = db();
    if (!$pdo) {
        return;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT u.full_name, u.email, u.role_key, u.student_id, u.must_change_password,
                    r.label AS role_label
             FROM users u
             LEFT JOIN roles r ON r.role_key = u.role_key
             WHERE u.id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch() ?: null;
        if (!$user) {
            return;
        }

        $name = trim((string) ($user['full_name'] ?? ''));
        if ($name !== '') {
            $_SESSION['user_name'] = $name;
        }
        $_SESSION['user_email'] = (string) ($user['email'] ?? '');
        $roleKey = trim((string) ($user['role_key'] ?? ''));
        if ($roleKey !== '') {
            $_SESSION['user_role_key'] = $roleKey;
            $roleLabel = trim((string) ($user['role_label'] ?? ''));
            $_SESSION['user_role'] = $roleLabel !== '' ? $roleLabel : $roleKey;
        }
        $_SESSION['must_change_password'] = (int) ($user['must_change_password'] ?? 0);
        if (!empty($user['student_id'])) {
            $_SESSION['student_id'] = (string) $user['student_id'];
        } else {
            unset($_SESSION['student_id']);
        }
    } catch (Throwable $e) {
        error_log('smsRefreshCurrentUserSession: ' . $e->getMessage());
    }
}

/**
 * Default module access when DB permissions are empty (fallback).
 */
function smsDefaultModulesForRole(string $roleKey): array
{
    $roleKey = smsNormalizeRoleKey($roleKey);
    $defaults = [
        'superadmin'       => ['user-management'],
        'sms_admin'        => ['enrollment', 'registrar', 'curriculum', 'accreditation', 'payment', 'faculty', 'scheduling', 'cocurricular', 'lms', 'crad'],
        'admin'            => ['user-management'],
        'admission'        => ['enrollment'],
        'student'      => ['student_portal'],
        'registrar'    => ['registrar', 'curriculum', 'scheduling'],
        'crad_officer' => ['crad'],
        'research_coordinator' => ['crad'],
        'department_head' => ['crad'],
        'department_chair' => ['crad'],
        'research_office' => ['crad'],
        'research_grant' => ['crad_grant'],
        'review_committee' => ['crad_grant'],
        'finance'      => ['payment'],
        'hr'           => ['faculty'],
        'adviser'      => ['faculty'],
        'panel'        => ['faculty'],
        'research_director' => ['faculty'],
        'grammarian'   => ['faculty'],
        'it_office'    => ['lms'],
        'osa'          => ['cocurricular'],
        'qa'           => ['accreditation'],
        'vpaa'         => ['accreditation'],
    ];

    return $defaults[$roleKey] ?? [];
}

/**
 * Load granted modules for a role from DB (+ JSON file legacy overrides).
 */
function smsAllowedModuleKeysForRole(string $roleKey): array
{
    $roleKey = smsNormalizeRoleKey($roleKey);

    $allowed = [];

    $pdo = db();
    if ($pdo) {
        try {
            $lookupKeys = smsRolePermissionLookupKeys($roleKey);
            $placeholders = implode(',', array_fill(0, count($lookupKeys), '?'));
            $stmt = $pdo->prepare(
                "SELECT module_key, granted FROM role_permissions WHERE role_key IN ($placeholders)"
            );
            $stmt->execute($lookupKeys);
            $rows = $stmt->fetchAll();

            if ($rows) {
                foreach ($rows as $row) {
                    if ((int) $row['granted'] === 1) {
                        $allowed[] = (string) $row['module_key'];
                    }
                }
                $allowed = array_values(array_unique($allowed));
            } else {
                $allowed = smsDefaultModulesForRole($roleKey);
            }
        } catch (Throwable $e) {
            $allowed = smsDefaultModulesForRole($roleKey);
        }
    } else {
        $allowed = smsDefaultModulesForRole($roleKey);
    }

    // Legacy JSON overrides (migrate gradually)
    $matrixRoleKey = smsMatrixRoleKey($roleKey);
    $permFile = ROOT_PATH . '/config/perm_overrides.json';
    if (is_readable($permFile)) {
        $overrides = json_decode((string) file_get_contents($permFile), true);
        if (is_array($overrides) && !empty($overrides[$matrixRoleKey])) {
            foreach ($overrides[$matrixRoleKey] as $module => $granted) {
                if ($granted) {
                    if (!in_array($module, $allowed, true)) {
                        $allowed[] = $module;
                    }
                } else {
                    $allowed = array_values(array_filter(
                        $allowed,
                        static fn($m) => $m !== $module
                    ));
                }
            }
        }
    }

    if ($roleKey === 'student') {
        $allowed = ['student_portal'];
    } else {
        $allowed = array_values(array_filter(
            $allowed,
            static fn($moduleKey) => $moduleKey !== 'student_portal'
        ));
    }

    return $allowed;
}

/**
 * Load granted modules for the current session role.
 */
function getAllowedModuleKeys(): array
{
    return smsAllowedModuleKeysForRole(getCurrentUserRoleKey());
}

function userCanAccessModule(string $moduleKey): bool
{
    if ($moduleKey === '' || $moduleKey === 'dashboard') {
        return true;
    }

    // Student portal alias
    if ($moduleKey === 'student-portal' || $moduleKey === 'student_portal') {
        $moduleKey = 'student_portal';
        $roleKey = smsNormalizeRoleKey(getCurrentUserRoleKey());
        if ($roleKey === 'student') {
            return true;
        }
        return in_array($moduleKey, getAllowedModuleKeys(), true);
    }

    $allowedModules = getAllowedModuleKeys();
    return in_array($moduleKey, $allowedModules, true);
}

function requireSuperAdmin(): void
{
    if (!userCanAccessModule('user-management')) {
        header('Location: ' . BASE_URL . '/dashboard/index.php');
        exit;
    }
}

/**
 * Admin personal account settings (profile + login security).
 * Available to Super Admin and operational Admin (sms_admin) — not tied to user-management module.
 */
function requireAdminAccountSettings(): void
{
    requireAuth();
    if (!smsIsGrantedAdminRole(getCurrentUserRoleKey())) {
        header('Location: ' . BASE_URL . '/dashboard/index.php');
        exit;
    }
}

function smsRemoveModuleNavSlug(array $module, string $slug): array
{
    if (!empty($module['groups']) && is_array($module['groups'])) {
        foreach ($module['groups'] as $groupLabel => $slugs) {
            $filtered = array_values(array_filter(
                (array) $slugs,
                static fn($item): bool => (string) $item !== $slug
            ));
            if ($filtered === []) {
                unset($module['groups'][$groupLabel]);
            } else {
                $module['groups'][$groupLabel] = $filtered;
            }
        }
    }
    if (!empty($module['pages']) && is_array($module['pages'])) {
        $module['pages'] = array_values(array_filter(
            $module['pages'],
            static fn(array $page): bool => ($page['slug'] ?? '') !== $slug
        ));
    }

    return $module;
}

function smsMergeModuleNav(array $base, array $extra): array
{
    $baseGroups = isset($base['groups']) && is_array($base['groups']) ? $base['groups'] : [];
    $extraGroups = isset($extra['groups']) && is_array($extra['groups']) ? $extra['groups'] : [];
    $base['groups'] = $baseGroups + $extraGroups;
    foreach ($extraGroups as $label => $slugs) {
        if (isset($baseGroups[$label]) && is_array($baseGroups[$label])) {
            $base['groups'][$label] = array_values(array_unique(array_merge(
                (array) $baseGroups[$label],
                (array) $slugs
            )));
        }
    }

    $pages = isset($base['pages']) && is_array($base['pages']) ? $base['pages'] : [];
    $existing = [];
    foreach ($pages as $page) {
        $existing[(string) ($page['slug'] ?? '')] = true;
    }
    foreach ($extra['pages'] ?? [] as $page) {
        $slug = (string) ($page['slug'] ?? '');
        if ($slug === '' || isset($existing[$slug])) {
            continue;
        }
        $pages[] = $page;
        $existing[$slug] = true;
    }
    $base['pages'] = $pages;

    return $base;
}

function smsAdminCoordinatorAssignmentNav(): array
{
    return [
        'groups' => [
            'A. Adviser Assignment' => [
                'retrieve-approved-research',
                'find-contact-adviser',
                'adviser-availability',
                'assign-research-adviser',
            ],
            'B. Panel Assignment' => [
                'retrieve-defense-ready-research',
                'select-panel-members',
                'check-panel-availability',
                'assign-panel-members',
            ],
            'Coordination' => [
                'manage-assignments',
            ],
        ],
        'pages' => [
            ['slug' => 'retrieve-approved-research', 'title' => 'Retrieve Approved Research'],
            ['slug' => 'find-contact-adviser', 'title' => 'Find/Contact Adviser'],
            ['slug' => 'adviser-availability', 'title' => 'Check Adviser Availability'],
            ['slug' => 'assign-research-adviser', 'title' => 'Assign Research Adviser'],
            ['slug' => 'retrieve-defense-ready-research', 'title' => 'Retrieve Defense-Ready Research'],
            ['slug' => 'select-panel-members', 'title' => 'Select Panel Members'],
            ['slug' => 'check-panel-availability', 'title' => 'Check Panel Availability'],
            ['slug' => 'assign-panel-members', 'title' => 'Assign Panel Members'],
            ['slug' => 'manage-assignments', 'title' => 'View/Manage Assignments'],
        ],
    ];
}

function smsAdminDefenseSchedulingNav(): array
{
    return [
        'groups' => [
            'PRE-ORAL DEFENSE' => [
                'defense-scheduling-queue',
                'manual-scheduling-optimizer',
                'proposed-schedules',
                'alternative-time-slots',
                'calendar',
                'venues',
                'finalize-defense-schedule',
            ],
            'FINAL DEFENSE SCHEDULING' => [
                'final-defense-scheduling-queue',
                'final-defense-manual-scheduling',
                'final-defense-proposed-schedules',
                'final-defense-finalize-schedule',
            ],
        ],
        'pages' => [
            ['slug' => 'defense-scheduling-queue', 'title' => 'Ready for Scheduling'],
            ['slug' => 'manual-scheduling-optimizer', 'title' => 'AI Scheduling Optimizer'],
            ['slug' => 'proposed-schedules', 'title' => 'Proposed Schedules'],
            ['slug' => 'alternative-time-slots', 'title' => 'Alternative Time Slots'],
            ['slug' => 'calendar', 'title' => 'Calendar'],
            ['slug' => 'venues', 'title' => 'Venues'],
            ['slug' => 'finalize-defense-schedule', 'title' => 'Finalize Schedule'],
            ['slug' => 'final-defense-scheduling-queue', 'title' => 'Ready for Scheduling'],
            ['slug' => 'final-defense-manual-scheduling', 'title' => 'AI Scheduling Optimizer'],
            ['slug' => 'final-defense-proposed-schedules', 'title' => 'Proposed Schedules'],
            ['slug' => 'final-defense-finalize-schedule', 'title' => 'Finalize Schedule'],
        ],
    ];
}

function smsAdminDefenseSchedulingHref(string $slug): ?string
{
    $map = [
        'defense-scheduling-queue' => ['view' => 'defense-scheduling-queue'],
        'manual-scheduling-optimizer' => ['view' => 'manual-scheduling-optimizer'],
        'proposed-schedules' => ['view' => 'proposed-schedules'],
        'alternative-time-slots' => ['view' => 'alternative-time-slots'],
        'calendar' => ['view' => 'calendar'],
        'venues' => ['view' => 'venues'],
        'finalize-defense-schedule' => ['view' => 'finalize-defense-schedule'],
        'final-defense-scheduling-queue' => ['view' => 'defense-scheduling-queue', 'defense_type' => 'Final Defense'],
        'final-defense-manual-scheduling' => ['view' => 'manual-scheduling-optimizer', 'defense_type' => 'Final Defense'],
        'final-defense-proposed-schedules' => ['view' => 'proposed-schedules', 'defense_type' => 'Final Defense'],
        'final-defense-finalize-schedule' => ['view' => 'finalize-defense-schedule', 'defense_type' => 'Final Defense'],
    ];
    if (!isset($map[$slug])) {
        return null;
    }

    return BASE_URL . '/modules/faculty/pages/research-director.php?' . http_build_query($map[$slug]);
}

function smsAdminCoreSystemNav(): array
{
    return [
        'groups' => [
            'Research Clearance' => [
                'approval-clearance-payment',
            ],
            'Core System' => [
                'dashboard-analytics',
                'grant-opportunities',
                'proposals-applications',
            ],
        ],
        'pages' => [
            ['slug' => 'approval-clearance-payment', 'title' => 'Approval Clearance Payment'],
            ['slug' => 'dashboard-analytics', 'title' => 'Dashboard & Analytics'],
            ['slug' => 'grant-opportunities', 'title' => 'Grant Opportunities'],
            ['slug' => 'proposals-applications', 'title' => 'Proposals & Applications'],
        ],
    ];
}

function smsAdminCoordinatorWorkflowPaths(): array
{
    return [
        '/modules/crad/pages/research-coordinator-management.php',
        '/modules/crad/pages/dashboard-analytics.php',
        '/modules/crad/pages/approval-clearance-payment.php',
        '/modules/crad/api/clearance-payment.php',
        '/modules/crad/pages/grant-opportunities.php',
        '/modules/crad/pages/proposals-applications.php',
        '/modules/crad/api/grant-management.php',
        '/modules/crad/pages/retrieve-approved-research.php',
        '/modules/crad/pages/find-contact-adviser.php',
        '/modules/crad/pages/adviser-availability.php',
        '/modules/crad/pages/assign-research-adviser.php',
        '/modules/crad/pages/retrieve-defense-ready-research.php',
        '/modules/crad/pages/select-panel-members.php',
        '/modules/crad/pages/check-panel-availability.php',
        '/modules/crad/pages/assign-panel-members.php',
        '/modules/crad/pages/manage-assignments.php',
        '/modules/faculty/pages/research-director.php',
    ];
}

function smsCanManageCoordinatorAssignments(?string $roleKey = null): bool
{
    $roleKey = $roleKey ?? getCurrentUserRoleKey();

    return smsIsGrantedAdminRole($roleKey)
        || $roleKey === 'research_coordinator'
        || $roleKey === 'department_head';
}

function smsDepartmentHeadWorkflowPaths(): array
{
    return [
        '/account/module-security.php',
        '/account/security.php',
        '/modules/crad/pages/research-coordinator-management.php',
        '/modules/crad/pages/retrieve-approved-research.php',
        '/modules/crad/pages/find-contact-adviser.php',
        '/modules/crad/pages/adviser-availability.php',
        '/modules/crad/pages/assign-research-adviser.php',
        '/modules/crad/pages/retrieve-defense-ready-research.php',
        '/modules/crad/pages/select-panel-members.php',
        '/modules/crad/pages/check-panel-availability.php',
        '/modules/crad/pages/assign-panel-members.php',
        '/modules/crad/pages/manage-assignments.php',
        '/modules/crad/pages/send-notifications.php',
    ];
}

function smsDepartmentHeadCradModule(): array
{
    return [
        'label' => 'Research Management',
        'icon'  => 'fa-flask',
        'hide_overview' => true,
        'show_ungrouped_pages' => true,
        'groups' => [
            'A. Adviser Assignment' => [
                'retrieve-approved-research',
                'find-contact-adviser',
                'adviser-availability',
                'assign-research-adviser',
                'manage-assignments',
            ],
            'B. Panel Assignment' => [
                'retrieve-defense-ready-research',
                'select-panel-members',
                'check-panel-availability',
                'assign-panel-members',
            ],
        ],
        'pages' => [
            ['slug' => 'research-coordinator-management', 'title' => 'Research Coordinator Management'],
            ['slug' => 'retrieve-approved-research', 'title' => 'Retrieve Approved Research'],
            ['slug' => 'find-contact-adviser', 'title' => 'Find/Contact Adviser'],
            ['slug' => 'adviser-availability', 'title' => 'Check Adviser Availability'],
            ['slug' => 'assign-research-adviser', 'title' => 'Assign Research Adviser'],
            ['slug' => 'manage-assignments', 'title' => 'View/Manage Adviser Assignments'],
            ['slug' => 'retrieve-defense-ready-research', 'title' => 'Retrieve Defense-Ready Research'],
            ['slug' => 'select-panel-members', 'title' => 'Select Panel Members'],
            ['slug' => 'check-panel-availability', 'title' => 'Check Panel Availability'],
            ['slug' => 'assign-panel-members', 'title' => 'Assign Panel Members'],
        ],
    ];
}

function smsCanManageDefenseScheduling(?string $roleKey = null): bool
{
    $roleKey = $roleKey ?? getCurrentUserRoleKey();

    return smsIsGrantedAdminRole($roleKey) || $roleKey === 'research_director';
}

function getVisibleModules(array $modules): array
{
    $allowedModules = getAllowedModuleKeys();
    $visible = array_intersect_key($modules, array_flip($allowedModules));

    if (getCurrentUserRoleKey() === 'crad_officer' && isset($visible['crad'])) {
        $visible['crad'] = smsRemoveModuleNavSlug($visible['crad'], 'research-coordinator-management');
        $visible['crad'] = smsRemoveModuleNavSlug($visible['crad'], 'dashboard-analytics');
        $visible['crad'] = smsRemoveModuleNavSlug($visible['crad'], 'grant-opportunities');
        $visible['crad'] = smsRemoveModuleNavSlug($visible['crad'], 'proposals-applications');
        $cradGroups = isset($visible['crad']['groups']) && is_array($visible['crad']['groups']) ? $visible['crad']['groups'] : [];
        $visible['crad']['groups'] = ['Research Clearance' => ['research-clearance']] + $cradGroups;
        $hasClearancePage = false;
        foreach ((array) ($visible['crad']['pages'] ?? []) as $cradPage) {
            if (($cradPage['slug'] ?? '') === 'research-clearance') {
                $hasClearancePage = true;
                break;
            }
        }
        if (!$hasClearancePage) {
            $visible['crad']['pages'][] = ['slug' => 'research-clearance', 'title' => 'Research Services Clearance'];
        }
    }

    if (getCurrentUserRoleKey() === 'research_coordinator' && isset($visible['crad'])) {
        $visible['crad'] = smsResearchCoordinatorCradModule();
    }

    if (getCurrentUserRoleKey() === 'department_head') {
        $visible['crad'] = smsDepartmentHeadCradModule();
    }

    if (getCurrentUserRoleKey() === 'department_chair' && isset($visible['crad'])) {
        unset($visible['crad']);
    }

    if (getCurrentUserRoleKey() === 'research_office' && isset($visible['crad'])) {
        unset($visible['crad']);
    }

    if (getCurrentUserRoleKey() === 'vpaa' && isset($visible['accreditation'])) {
        unset($visible['accreditation']);
    }

    if (getCurrentUserRoleKey() === 'review_committee' && isset($visible['crad_grant'])) {
        $visible['crad_grant'] = smsReviewCommitteeGrantModule();
    }

    if (smsIsGrantedAdminRole(getCurrentUserRoleKey())) {
        $assignmentNav = smsMergeModuleNav(
            smsAdminCoreSystemNav(),
            smsAdminDefenseSchedulingNav()
        );
        if (!isset($visible['crad'])) {
            $visible['crad'] = smsMergeModuleNav([
                'label' => 'CRAD',
                'icon'  => 'fa-flask',
                'hide_overview' => true,
            ], $assignmentNav);
        } else {
            $visible['crad'] = smsMergeModuleNav($visible['crad'], $assignmentNav);
        }
        $visible['crad'] = smsRemoveModuleNavSlug($visible['crad'], 'research-coordinator-management');
        if (isset($visible['crad']['groups']['Research Management'])) {
            unset($visible['crad']['groups']['Research Management']);
        }
    }

    if (in_array('student_portal', $allowedModules, true) && !isset($visible['student_portal'])) {
        $visible['student_portal'] = [
            'label' => 'Student Portal',
            'icon'  => 'fa-user-graduate',
            'groups' => [
                'Overview' => ['dashboard'],
                'Student Information' => ['my-profile', 'student-id'],
                'Financial' => ['account-balance', 'payment-history'],
                'Academics' => ['class-schedule', 'academic-records', 'subjects-professors', 'grades-portal'],
                'Research' => ['research-proposal-submission'],
                'Document Submission' => ['submit-chapters', 'my-submissions', 'submission-status', 'submission-history'],
                'Research Clearance' => ['college-payment', 'research-clearance'],
            ],
            'pages' => [
                ['slug' => 'dashboard', 'title' => 'Dashboard'],
                ['slug' => 'my-profile', 'title' => 'My Profile'],
                ['slug' => 'student-id', 'title' => 'Student ID'],
                ['slug' => 'account-balance', 'title' => 'Account Balance'],
                ['slug' => 'payment-history', 'title' => 'Payment History'],
                ['slug' => 'class-schedule', 'title' => 'Class Schedule'],
                ['slug' => 'academic-records', 'title' => 'Academic Records'],
                ['slug' => 'subjects-professors', 'title' => 'Subject & Professors'],
                ['slug' => 'grades-portal', 'title' => 'Grades Portal'],
                ['slug' => 'research-proposal-submission', 'title' => 'Research Proposal'],
                ['slug' => 'submit-chapters', 'title' => 'Submit Chapter 1-3'],
                ['slug' => 'college-payment', 'title' => 'Upload Collage Payment'],
                ['slug' => 'research-clearance', 'title' => 'Research Services Clearance'],
                ['slug' => 'my-submissions', 'title' => 'My Submissions'],
                ['slug' => 'submission-status', 'title' => 'Submission Status'],
                ['slug' => 'submission-history', 'title' => 'Submission History'],
            ],
        ];
    }

    if (isset($visible['reports-analytics'])) {
        require_once __DIR__ . '/reports-catalog.php';
        $roleReports = smsReportsForRole();
        $visible['reports-analytics']['pages'] = array_map(
            static fn(array $report): array => [
                'slug'  => $report['slug'],
                'title' => $report['title'],
            ],
            $roleReports
        );
    }

    return $visible;
}

function smsResearchCoordinatorCradModule(): array
{
    return [
        'label' => 'Research Coordinator',
        'icon'  => 'fa-microscope',
        'groups' => [
            'Approved Research' => [
                'approved-research',
            ],
        ],
        'pages' => [
            ['slug' => 'approved-research', 'title' => 'View Approved Research'],
        ],
    ];
}

function smsReviewCommitteeGrantModule(): array
{
    return [
        'label' => 'Research Grant',
        'icon'  => 'fa-hand-holding-usd',
        'hide_overview' => true,
        'groups' => [
            'Review & Workflow' => [
                'reviewer-evaluation',
            ],
            'System' => [
                'security-settings',
            ],
        ],
        'pages' => [
            ['slug' => 'reviewer-evaluation', 'title' => 'Reviewer Evaluation'],
            ['slug' => 'security-settings', 'title' => 'Security Settings'],
        ],
    ];
}

function smsPostLoginRedirectUrl(): string
{
    require_once __DIR__ . '/navigation-context.php';

    return smsRoleHomeUrl(getCurrentUserRoleKey());
}

function requireModuleAccess(string $moduleKey): void
{
    // student-portal folder uses hyphen
    $key = $moduleKey === 'student-portal' ? 'student_portal' : $moduleKey;
    if ($key === '') {
        return;
    }

    require_once __DIR__ . '/module-controls.php';

    // Per-module maintenance: staff/students see maintenance page; Super Admin still allowed
    // Only the maintenance page + logout are allowed (no escape into the module)
    $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $isMaintEscape = str_ends_with($scriptPath, '/account/module-unavailable.php')
        || str_ends_with($scriptPath, '/login/logout.php');

    if (
        smsIsModuleInMaintenance($key)
        && !smsCanBypassSystemControls()
        && $key !== 'user-management'
        && !$isMaintEscape
    ) {
        header('Location: ' . BASE_URL . '/account/module-unavailable.php?module=' . rawurlencode($key));
        exit;
    }

    if ($key === 'crad' && getCurrentUserRoleKey() === 'department_head') {
        $isAllowedDepartmentHeadPage = false;
        foreach (smsDepartmentHeadWorkflowPaths() as $allowedPath) {
            if (str_ends_with($scriptPath, $allowedPath)) {
                $isAllowedDepartmentHeadPage = true;
                break;
            }
        }
        if (!$isAllowedDepartmentHeadPage) {
            header('Location: ' . BASE_URL . '/modules/crad/pages/research-coordinator-management.php');
            exit;
        }
    }

    if ($key === 'crad' && getCurrentUserRoleKey() === 'research_coordinator') {
        $allowedCoordinatorPages = [
            '/account/module-security.php',
            '/account/security.php',
            '/modules/crad/index.php',
            '/modules/crad/pages/approved-research.php',
            '/modules/crad/pages/final-manuscript-review.php',
            '/modules/crad/pages/final-manuscript-approval.php',
            '/modules/crad/pages/revision-compliance.php',
            '/modules/crad/pages/publication-create.php',
            '/modules/crad/pages/documentation-publication-management.php',
            '/modules/crad/pages/research-analytics-reporting.php',
            '/modules/crad/pages/research-repository.php',
            '/modules/crad/pages/research-collaboration-portal.php',
            '/modules/crad/pages/adviser-panel-assignment.php',
            '/modules/crad/pages/retrieve-approved-research.php',
            '/modules/crad/pages/find-contact-adviser.php',
            '/modules/crad/pages/adviser-availability.php',
            '/modules/crad/pages/assign-research-adviser.php',
            '/modules/crad/pages/retrieve-defense-ready-research.php',
            '/modules/crad/pages/select-panel-members.php',
            '/modules/crad/pages/check-panel-availability.php',
            '/modules/crad/pages/assign-panel-members.php',
            '/modules/crad/pages/send-notifications.php',
            '/modules/crad/pages/manage-assignments.php',
            '/modules/crad/pages/reviewer-evaluation.php',
            '/modules/crad/pages/approval-workflows.php',
            '/modules/crad/grant-proposal-file.php',
        ];
        $isAllowedCoordinatorPage = false;
        foreach ($allowedCoordinatorPages as $allowedPath) {
            if (str_ends_with($scriptPath, $allowedPath)) {
                $isAllowedCoordinatorPage = true;
                break;
            }
        }
        if (!$isAllowedCoordinatorPage) {
            header('Location: ' . BASE_URL . '/modules/crad/index.php');
            exit;
        }
    }

    if ($key === 'student_portal') {
        if (userCanAccessModule('student_portal')) {
            return;
        }
        header('Location: ' . BASE_URL . '/dashboard/index.php');
        exit;
    }

    if (!userCanAccessModule($key) && !userCanAccessModule($moduleKey)) {
        $grantResearcherPages = [
            '/modules/crad/pages/grant-opportunities.php',
            '/modules/crad/pages/proposals-applications.php',
            '/modules/crad/pages/revisions-requested.php',
            '/modules/crad/pages/revise-proposal.php',
            '/modules/crad/pages/approved-funded.php',
            '/modules/crad/pages/funded-research.php',
            '/modules/crad/pages/budget-disbursement.php',
            '/modules/crad/pages/project-milestones.php',
            '/modules/crad/pages/publications-ip.php',
            '/modules/crad/pages/document-repository.php',
            '/modules/crad/grant-funded-research-file.php',
            '/modules/crad/grant-final-output-file.php',
            '/modules/crad/grant-document-repository-file.php',
            '/modules/crad/grant-milestone-file.php',
        ];
        $grantReviewerPages = [
            '/modules/crad/pages/reviewer-evaluation.php',
            '/modules/faculty/pages/reviewer-evaluation.php',
            '/modules/accreditation/pages/reviewer-evaluation.php',
            '/modules/payment/pages/reviewer-evaluation.php',
            '/modules/crad/grant-proposal-file.php',
        ];
        $roleKey = getCurrentUserRoleKey();
        if (smsIsGrantedAdminRole($roleKey)) {
            foreach (smsAdminCoordinatorWorkflowPaths() as $adminPath) {
                if (str_ends_with($scriptPath, $adminPath)) {
                    return;
                }
            }
        }
        if (in_array($roleKey, ['student', 'adviser'], true)) {
            foreach ($grantResearcherPages as $allowedPath) {
                if (str_ends_with($scriptPath, $allowedPath)) {
                    return;
                }
            }
        }
        if (in_array($roleKey, ['review_committee', 'adviser'], true)) {
            foreach ($grantReviewerPages as $allowedPath) {
                if (str_ends_with($scriptPath, $allowedPath)) {
                    return;
                }
            }
        }
        if (smsIsPanelDefenseRole($roleKey)) {
            foreach (smsPanelDefenseWorkflowPaths() as $allowedPath) {
                if (str_ends_with($scriptPath, $allowedPath)) {
                    return;
                }
            }
        }
        $grantApproverRoles = ['research_coordinator', 'department_chair', 'hr', 'crad_officer', 'research_office', 'finance', 'qa', 'vpaa'];
        if (in_array($roleKey, $grantApproverRoles, true)) {
            $grantApproverPages = [
                '/modules/crad/pages/approval-workflows.php',
                '/modules/faculty/pages/approval-workflows.php',
                '/modules/accreditation/pages/approval-workflows.php',
                '/modules/payment/pages/approval-workflows.php',
            ];
            if ($roleKey === 'finance') {
                $grantApproverPages[] = '/modules/payment/pages/reviewer-evaluation.php';
                $grantApproverPages[] = '/modules/crad/pages/budget-disbursement.php';
                $grantApproverPages[] = '/modules/crad/pages/project-milestones.php';
                $grantApproverPages[] = '/modules/crad/pages/approved-funded.php';
            } elseif (!in_array($roleKey, ['crad_officer'], true)) {
                $grantApproverPages = array_merge($grantReviewerPages, $grantApproverPages);
            }
            foreach ($grantApproverPages as $allowedPath) {
                if (str_ends_with($scriptPath, $allowedPath)) {
                    return;
                }
            }
        }

        if (str_ends_with($scriptPath, '/dashboard/index.php')) {
            return;
        }
        header('Location: ' . BASE_URL . '/dashboard/index.php');
        exit;
    }
}

/**
 * Compact a login identifier for alias matching (letters/digits only).
 */
function smsCompactLoginKey(string $input): string
{
    $input = strtolower(trim($input));
    if (str_contains($input, '@')) {
        $input = explode('@', $input, 2)[0];
    }

    return preg_replace('/[^a-z0-9]/', '', $input) ?? '';
}

/**
 * @return list<string>
 */
function smsLoginUsernameCandidates(string $input): array
{
    $raw = strtolower(trim($input));
    $local = $raw;
    if (str_contains($raw, '@')) {
        $local = explode('@', $raw, 2)[0];
    }
    $compact = smsCompactLoginKey($raw);

    $alias = [
        'reviewcommitee' => 'reviewcommittee',
        'reviewcommitte' => 'reviewcommittee',
        'reviewcommitteemember' => 'reviewcommittee',
        'reviewcommitteeember' => 'reviewcommittee',
        'reviewcommittee' => 'reviewcommittee',
    ];

    $candidates = [$raw, $local, $compact];
    if ($compact !== '' && isset($alias[$compact])) {
        $candidates[] = $alias[$compact];
    }

    return array_values(array_unique(array_filter($candidates, static fn($v) => $v !== '')));
}

/**
 * Resolve login identifier to a user row.
 */
function smsFindUserByLogin(string $input): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $input = strtolower(trim($input));
    if ($input === '') {
        return null;
    }

    $candidates = smsLoginUsernameCandidates($input);
    $emailGuesses = [];
    foreach ($candidates as $candidate) {
        if (!str_contains($candidate, '@')) {
            $emailGuesses[] = $candidate . '@bestlink.edu.ph';
        }
    }
    $lookups = array_values(array_unique(array_merge($candidates, $emailGuesses, [$input])));
    $compact = smsCompactLoginKey($input);
    if ($lookups === []) {
        return null;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($lookups), '?'));
        $sql = "SELECT u.*, r.label AS role_label
             FROM users u
             LEFT JOIN roles r ON r.role_key = u.role_key
             WHERE LOWER(TRIM(u.username)) IN ($placeholders)
                OR LOWER(TRIM(u.email)) IN ($placeholders)
                OR LOWER(TRIM(IFNULL(u.student_id, ''))) IN ($placeholders)";
        $params = array_merge($lookups, $lookups, $lookups);
        if (strlen($compact) >= 10) {
            $sql .= " OR REPLACE(LOWER(TRIM(u.full_name)), ' ', '') = ?";
            $params[] = $compact;
        }
        $sql .= ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: null;
        if ($row && ($row['role_label'] ?? '') === '') {
            $row['role_label'] = (string) ($row['role_key'] ?? '');
        }

        return $row ?: null;
    } catch (Throwable $e) {
        error_log('SMS2 find user failed: ' . $e->getMessage());
        return null;
    }
}

function smsIsAccountLocked(array $user): bool
{
    if (!empty($user['locked_until'])) {
        $until = strtotime((string) $user['locked_until']);
        if ($until !== false && $until > time()) {
            return true;
        }
        // Expired cooldown — do not keep locking forever on status alone
        return false;
    }

    // Locked with no cooldown timestamp (manual / legacy)
    return ($user['status'] ?? '') === 'locked';
}

function smsClearLockIfExpired(array $user): void
{
    if (empty($user['locked_until'])) {
        return;
    }

    $until = strtotime((string) $user['locked_until']);
    if ($until === false || $until > time()) {
        return;
    }

    $pdo = db();
    if (!$pdo) {
        return;
    }

    $pdo->prepare(
        'UPDATE users SET locked_until = NULL, failed_login_attempts = 0,
         status = CASE WHEN status = \'locked\' THEN \'active\' ELSE status END
         WHERE id = ?'
    )->execute([(int) $user['id']]);
}

function smsLockoutSeconds(): int
{
    $sec = (int) smsSetting('lockout_seconds', '0');
    if ($sec > 0) {
        return max(1, $sec);
    }
    return max(1, (int) smsSetting('lockout_minutes', '5')) * 60;
}

function smsFormatDuration(int $seconds): string
{
    $seconds = max(0, $seconds);
    if ($seconds < 60) {
        return $seconds . ' second' . ($seconds === 1 ? '' : 's');
    }
    $mins = (int) floor($seconds / 60);
    $secs = $seconds % 60;
    if ($secs === 0) {
        return $mins . ' minute' . ($mins === 1 ? '' : 's');
    }
    return $mins . ' min ' . $secs . ' sec';
}

function smsLockRemainingMinutes(array $user): int
{
    if (empty($user['locked_until'])) {
        return 0;
    }
    $until = strtotime((string) $user['locked_until']);
    if ($until === false || $until <= time()) {
        return 0;
    }
    return max(1, (int) ceil(($until - time()) / 60));
}

function smsLockRemainingSeconds(array $user): int
{
    if (empty($user['locked_until'])) {
        return 0;
    }
    $until = strtotime((string) $user['locked_until']);
    if ($until === false || $until <= time()) {
        return 0;
    }
    return max(1, $until - time());
}

function smsLoginThrottleKey(string $loginInput = ''): string
{
    $ip = smsClientIp();
    $norm = strtolower(trim($loginInput));
    // IP-wide key (anti-spam for random emails) — primary gate
    if ($norm === '') {
        return hash('sha256', 'ip|' . $ip);
    }
    return hash('sha256', 'ip|' . $ip);
}

function smsEnsureLoginThrottleTables(): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS login_throttles (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                throttle_key CHAR(64) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_login_throttle_key (throttle_key),
                KEY idx_login_throttle_ip (ip_address),
                KEY idx_login_throttle_locked (locked_until)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $e) {
        error_log('smsEnsureLoginThrottleTables: ' . $e->getMessage());
    }
}

/**
 * @return array{attempts:int,max:int,remaining:?int,locked:bool,lock_seconds:int,locked_until:?string}
 */
function smsGetLoginThrottle(string $loginInput = ''): array
{
    smsEnsureLoginThrottleTables();
    $pdo = db();
    $maxFails = max(0, (int) smsSetting('max_failed_logins', '3'));
    $lockSeconds = smsLockoutSeconds();
    $empty = [
        'attempts' => 0,
        'max' => $maxFails,
        'remaining' => $maxFails > 0 ? $maxFails : null,
        'locked' => false,
        'lock_seconds' => $lockSeconds,
        'locked_until' => null,
    ];
    if (!$pdo) {
        return $empty;
    }

    $key = smsLoginThrottleKey($loginInput);
    $stmt = $pdo->prepare('SELECT attempts, locked_until FROM login_throttles WHERE throttle_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) {
        return $empty;
    }

    $lockedUntil = $row['locked_until'] ?? null;
    if ($lockedUntil) {
        $untilTs = strtotime((string) $lockedUntil);
        if ($untilTs !== false && $untilTs <= time()) {
            $pdo->prepare('UPDATE login_throttles SET attempts = 0, locked_until = NULL WHERE throttle_key = ?')
                ->execute([$key]);
            return $empty;
        }
        if ($untilTs !== false && $untilTs > time()) {
            return [
                'attempts' => (int) $row['attempts'],
                'max' => $maxFails,
                'remaining' => 0,
                'locked' => true,
                'lock_seconds' => $lockSeconds,
                'locked_until' => (string) $lockedUntil,
            ];
        }
    }

    $attempts = (int) $row['attempts'];
    return [
        'attempts' => $attempts,
        'max' => $maxFails,
        'remaining' => $maxFails > 0 ? max(0, $maxFails - $attempts) : null,
        'locked' => false,
        'lock_seconds' => $lockSeconds,
        'locked_until' => null,
    ];
}

/**
 * @return array{attempts:int,max:int,remaining:?int,locked:bool,lock_seconds:int,locked_until:?string}
 */
function smsRegisterLoginThrottleFailure(string $loginInput = ''): array
{
    smsEnsureLoginThrottleTables();
    $pdo = db();
    $maxFails = max(0, (int) smsSetting('max_failed_logins', '3'));
    $lockSeconds = smsLockoutSeconds();
    $key = smsLoginThrottleKey($loginInput);
    $ip = smsClientIp();

    $result = [
        'attempts' => 1,
        'max' => $maxFails,
        'remaining' => $maxFails > 0 ? max(0, $maxFails - 1) : null,
        'locked' => false,
        'lock_seconds' => $lockSeconds,
        'locked_until' => null,
    ];

    if (!$pdo) {
        return $result;
    }

    $current = smsGetLoginThrottle($loginInput);
    if (!empty($current['locked'])) {
        return $current;
    }

    // Atomic increment — prevents spam/race from skipping the lock threshold
    $pdo->prepare(
        'INSERT INTO login_throttles (throttle_key, ip_address, attempts, locked_until)
         VALUES (?, ?, 1, NULL)
         ON DUPLICATE KEY UPDATE
            attempts = attempts + 1,
            ip_address = VALUES(ip_address),
            locked_until = IF(locked_until IS NOT NULL AND locked_until > NOW(), locked_until, NULL)'
    )->execute([$key, $ip]);

    $stmt = $pdo->prepare('SELECT attempts, locked_until FROM login_throttles WHERE throttle_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch() ?: [];
    $attempts = max(1, (int) ($row['attempts'] ?? 1));
    $lockedUntil = null;
    $locked = false;

    if ($maxFails > 0 && $attempts >= $maxFails) {
        $locked = true;
        $pdo->prepare(
            'UPDATE login_throttles
             SET locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND)
             WHERE throttle_key = ?'
        )->execute([$lockSeconds, $key]);
        $fresh = $pdo->prepare('SELECT locked_until FROM login_throttles WHERE throttle_key = ? LIMIT 1');
        $fresh->execute([$key]);
        $lockedUntil = $fresh->fetchColumn() ?: null;
        if (is_string($lockedUntil) && $lockedUntil !== '') {
            $lockedUntil = (string) $lockedUntil;
        } else {
            $lockedUntil = null;
        }
    }

    return [
        'attempts' => $attempts,
        'max' => $maxFails,
        'remaining' => $maxFails > 0 ? max(0, $maxFails - $attempts) : null,
        'locked' => $locked,
        'lock_seconds' => $lockSeconds,
        'locked_until' => $lockedUntil,
    ];
}

function smsClearLoginThrottle(string $loginInput = ''): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }
    smsEnsureLoginThrottleTables();
    $pdo->prepare('DELETE FROM login_throttles WHERE throttle_key = ?')
        ->execute([smsLoginThrottleKey($loginInput)]);
}

/**
 * Force IP throttle into locked state (no extra attempt increment).
 */
function smsForceLoginThrottleLock(string $loginInput = '', ?int $lockSeconds = null, ?int $minAttempts = null): ?string
{
    smsEnsureLoginThrottleTables();
    $pdo = db();
    if (!$pdo) {
        return null;
    }
    $seconds = max(1, $lockSeconds ?? smsLockoutSeconds());
    $attempts = max(1, $minAttempts ?? max(1, (int) smsSetting('max_failed_logins', '3')));
    $key = smsLoginThrottleKey($loginInput);
    $pdo->prepare(
        'INSERT INTO login_throttles (throttle_key, ip_address, attempts, locked_until)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
         ON DUPLICATE KEY UPDATE
            attempts = GREATEST(attempts, VALUES(attempts)),
            locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND),
            ip_address = VALUES(ip_address)'
    )->execute([$key, smsClientIp(), $attempts, $seconds, $seconds]);

    $stmt = $pdo->prepare('SELECT locked_until FROM login_throttles WHERE throttle_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $until = $stmt->fetchColumn();
    return $until ? (string) $until : null;
}

function smsLoginGateSet(?string $lockedUntil, string $message, string $alert = 'warning', ?int $lockSeconds = null): void
{
    // Always anchor the UI gate to PHP time so countdown/lock cannot
    // disappear immediately due to MySQL/PHP timezone skew.
    $seconds = max(1, $lockSeconds ?? smsLockoutSeconds());
    $untilTs = time() + $seconds;
    if ($lockedUntil) {
        $parsed = (int) strtotime($lockedUntil);
        if ($parsed > time()) {
            $untilTs = max($untilTs, $parsed);
        }
    }
    $_SESSION['login_gate'] = [
        'until' => $untilTs,
        'message' => $message,
        'alert' => $alert,
    ];
}

function smsLoginGateClear(): void
{
    unset($_SESSION['login_gate']);
}

/**
 * @return array{active:bool,message:string,alert:string,remaining_minutes:int,until:int}|null
 */
function smsLoginGateStatus(): ?array
{
    $gate = $_SESSION['login_gate'] ?? null;
    if (!is_array($gate) || empty($gate['until'])) {
        return null;
    }
    $until = (int) $gate['until'];
    if ($until <= time()) {
        smsLoginGateClear();
        return null;
    }
    $mins = max(1, (int) ceil(($until - time()) / 60));
    $message = (string) ($gate['message'] ?? '');
    if ($message === '') {
        $message = 'Login is temporarily locked. Please wait ' . $mins . ' minute' . ($mins === 1 ? '' : 's') . ' before trying again.';
    }
    return [
        'active' => true,
        'message' => $message,
        'alert' => (string) ($gate['alert'] ?? 'warning'),
        'remaining_minutes' => $mins,
        'until' => $until,
    ];
}

function smsFailMessageFromThrottle(array $info, bool $unknownUser = false): array
{
    $lockSeconds = (int) ($info['lock_seconds'] ?? smsLockoutSeconds());
    $lockLabel = smsFormatDuration($lockSeconds);
    if (!empty($info['locked'])) {
        $msg = 'Login is temporarily locked after too many failed attempts. Please wait '
            . $lockLabel
            . ' before trying again. Sign-in is disabled until the cooldown ends.';
        return ['code' => 'locked', 'message' => $msg, 'alert' => 'warning', 'show_reset' => !$unknownUser];
    }

    $remaining = $info['remaining'] ?? null;
    $max = (int) ($info['max'] ?? 0);
    $prefix = $unknownUser
        ? 'Invalid login attempt.'
        : 'Incorrect password.';

    if ($max > 0 && $remaining === 1) {
        return [
            'code' => 'last_attempt',
            'message' => $prefix . ' Warning: this is your last attempt. One more failure will lock this login for '
                . $lockLabel . '.'
                . ($unknownUser ? '' : ' If you forgot your password, reset it now.'),
            'alert' => 'warning',
            'show_reset' => !$unknownUser,
        ];
    }

    if ($max > 0 && $remaining !== null) {
        return [
            'code' => $unknownUser ? 'unknown' : 'bad_password',
            'message' => $prefix . ' ' . $remaining . ' attempt' . ($remaining === 1 ? '' : 's') . ' remaining.'
                . ($unknownUser ? '' : ' If you forgot your password, use Forgot password to reset it.'),
            'alert' => 'danger',
            'show_reset' => !$unknownUser,
        ];
    }

    return [
        'code' => $unknownUser ? 'unknown' : 'bad_password',
        'message' => $prefix . ($unknownUser ? '' : ' If you forgot your password, use Forgot password to reset it.'),
        'alert' => 'danger',
        'show_reset' => !$unknownUser,
    ];
}

function smsRegisterFailedLogin(array $user): array
{
    $pdo = db();
    $maxFails = max(0, (int) smsSetting('max_failed_logins', '3'));
    $lockSeconds = smsLockoutSeconds();
    $result = [
        'attempts' => ((int) $user['failed_login_attempts']) + 1,
        'max' => $maxFails,
        'remaining' => null,
        'locked' => false,
        'lock_seconds' => $lockSeconds,
        'locked_until' => null,
    ];

    if (!$pdo) {
        $attempts = (int) $result['attempts'];
        $result['remaining'] = $maxFails > 0 ? max(0, $maxFails - $attempts) : null;
        return $result;
    }

    // Atomic increment — rapid spam clicks must still hit the lock threshold
    $pdo->prepare(
        'UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?'
    )->execute([(int) $user['id']]);

    $fresh = $pdo->prepare(
        'SELECT failed_login_attempts, locked_until, status FROM users WHERE id = ? LIMIT 1'
    );
    $fresh->execute([(int) $user['id']]);
    $row = $fresh->fetch() ?: [];
    $attempts = max(1, (int) ($row['failed_login_attempts'] ?? 1));
    $result['attempts'] = $attempts;
    $result['remaining'] = $maxFails > 0 ? max(0, $maxFails - $attempts) : null;

    if ($maxFails > 0 && $attempts >= $maxFails) {
        $pdo->prepare(
            'UPDATE users
             SET locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND), status = \'locked\'
             WHERE id = ?'
        )->execute([$lockSeconds, (int) $user['id']]);

        $result['locked'] = true;
        $result['remaining'] = 0;
        $untilStmt = $pdo->prepare('SELECT locked_until FROM users WHERE id = ? LIMIT 1');
        $untilStmt->execute([(int) $user['id']]);
        $result['locked_until'] = $untilStmt->fetchColumn() ?: null;

        logActivity(
            'lockout',
            'Login locked after failed attempts',
            'System',
            (int) $user['id'],
            (string) $user['full_name'],
            (string) $user['role_key'],
            false
        );
    }

    return $result;
}

function smsRegisterSuccessfulLogin(array $user): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    $pdo->prepare(
        'UPDATE users
         SET failed_login_attempts = 0, locked_until = NULL,
             last_login_at = NOW(), last_login_ip = ?,
             status = CASE WHEN status = \'locked\' THEN \'active\' ELSE status END
         WHERE id = ?'
    )->execute([smsClientIp(), (int) $user['id']]);
}

/**
 * Database-backed login with detailed result for UI messaging.
 *
 * @return array{ok:bool,code:string,message:string,alert:string,show_reset:bool,locked:bool,locked_until:?string}
 */
function smsLoginAttempt(string $username, string $password): array
{
    $pack = static function (
        string $code,
        string $message,
        string $alert = 'danger',
        bool $showReset = false,
        bool $locked = false,
        ?string $lockedUntil = null
    ): array {
        return [
            'ok' => false,
            'code' => $code,
            'message' => $message,
            'alert' => $alert,
            'show_reset' => $showReset,
            'locked' => $locked,
            'locked_until' => $lockedUntil,
        ];
    };

    if (trim($username) === '' || trim($password) === '') {
        return $pack('empty', 'Please enter your email and password.');
    }

    // IP / login gate first (covers random spam emails too)
    $throttle = smsGetLoginThrottle($username);
    if (!empty($throttle['locked'])) {
        $secs = 0;
        if (!empty($throttle['locked_until'])) {
            $untilTs = strtotime((string) $throttle['locked_until']);
            if ($untilTs !== false && $untilTs > time()) {
                $secs = max(1, $untilTs - time());
            }
        }
        if ($secs <= 0) {
            $secs = (int) ($throttle['lock_seconds'] ?? smsLockoutSeconds());
        }
        $msg = 'Login is temporarily locked after too many failed attempts. Please wait '
            . smsFormatDuration($secs)
            . ' before trying again. Sign-in is disabled until the cooldown ends.';
        return $pack('locked', $msg, 'warning', false, true, $throttle['locked_until'] ?? null);
    }

    $user = smsFindUserByLogin($username);
    if (!$user) {
        password_verify($password, '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWX12');
        $failInfo = smsRegisterLoginThrottleFailure($username);
        logActivity(
            'login_failed',
            'Invalid login attempt (unknown credentials: ' . substr($username, 0, 80) . ')',
            'System',
            null,
            'Unknown',
            null,
            false
        );
        $msg = smsFailMessageFromThrottle($failInfo, true);
        return $pack(
            $msg['code'],
            $msg['message'],
            $msg['alert'],
            $msg['show_reset'],
            !empty($failInfo['locked']),
            $failInfo['locked_until'] ?? null
        );
    }

    smsClearLockIfExpired($user);
    $fresh = smsFindUserByLogin($username);
    if ($fresh) {
        $user = $fresh;
    }

    if (in_array($user['status'], ['inactive', 'suspended'], true)) {
        logActivity(
            'login_failed',
            'Login blocked — account status: ' . $user['status'],
            'System',
            (int) $user['id'],
            (string) $user['full_name'],
            (string) $user['role_key'],
            false
        );
        return $pack('inactive', 'This login cannot be used right now. Contact your administrator.');
    }

    if (smsIsAccountLocked($user)) {
        $secs = smsLockRemainingSeconds($user);
        if ($secs <= 0) {
            $secs = smsLockoutSeconds();
        }
        logActivity(
            'login_failed',
            'Login blocked — login locked',
            'System',
            (int) $user['id'],
            (string) $user['full_name'],
            (string) $user['role_key'],
            false
        );
        $msg = 'Login is temporarily locked after too many failed attempts. Please wait '
            . smsFormatDuration($secs)
            . ' before trying again. Sign-in is disabled until the cooldown ends.';
        $until = (string) ($user['locked_until'] ?? '');
        smsForceLoginThrottleLock($username, $secs > 0 ? $secs : null);
        return $pack('locked', $msg, 'warning', false, true, $until !== '' ? $until : null);
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        $userFail = smsRegisterFailedLogin($user);
        $ipFail = smsRegisterLoginThrottleFailure($username);
        // Use the stricter of the two
        $failInfo = $userFail;
        if (!empty($ipFail['locked']) && empty($userFail['locked'])) {
            $failInfo = $ipFail;
        } elseif (!empty($ipFail['locked']) && !empty($userFail['locked'])) {
            $failInfo = $userFail;
            if (empty($failInfo['locked_until']) && !empty($ipFail['locked_until'])) {
                $failInfo['locked_until'] = $ipFail['locked_until'];
            }
        } elseif (
            isset($ipFail['remaining'], $userFail['remaining'])
            && $ipFail['remaining'] !== null
            && $userFail['remaining'] !== null
            && $ipFail['remaining'] < $userFail['remaining']
        ) {
            $failInfo = $ipFail;
        }

        logActivity(
            'login_failed',
            'Invalid password',
            'System',
            (int) $user['id'],
            (string) $user['full_name'],
            (string) $user['role_key'],
            false
        );

        $msg = smsFailMessageFromThrottle($failInfo, false);
        $isLocked = !empty($failInfo['locked']);
        if ($isLocked) {
            $until = smsForceLoginThrottleLock(
                $username,
                (int) ($failInfo['lock_seconds'] ?? smsLockoutSeconds()),
                (int) ($failInfo['attempts'] ?? 0)
            );
            if ($until && empty($failInfo['locked_until'])) {
                $failInfo['locked_until'] = $until;
            }
        }
        return $pack(
            $msg['code'],
            $msg['message'],
            $msg['alert'],
            $msg['show_reset'],
            $isLocked,
            $failInfo['locked_until'] ?? null
        );
    }

    // Rehash if algorithm upgraded
    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        $pdo = db();
        if ($pdo) {
            $pdo->prepare('UPDATE users SET password_hash = ?, password_changed_at = password_changed_at WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
        }
    }

    require_once __DIR__ . '/totp.php';
    if (smsAuthenticatorIsEnabled((int) $user['id'])) {
        // Password OK — require Authenticator / email OTP before full session
        $_SESSION['pending_2fa'] = [
            'user_id' => (int) $user['id'],
            'username' => $username,
            'at' => time(),
            'method' => 'authenticator',
        ];
        smsClearLoginThrottle($username);
        return [
            'ok' => false,
            'code' => 'needs_2fa',
            'message' => 'Enter your Authenticator code or email OTP to finish signing in.',
            'alert' => 'info',
            'show_reset' => false,
            'locked' => false,
            'locked_until' => null,
            'needs_2fa' => true,
        ];
    }

    return smsCompleteLoginSession($user, $username);
}

/**
 * Finalize login session after password (+ optional 2FA) succeeds.
 *
 * @param array<string,mixed> $user
 * @return array{ok:bool,code:string,message:string,alert:string,show_reset:bool,locked:bool,locked_until:?string}
 */
function smsCompleteLoginSession(array $user, string $username = ''): array
{
    smsRegisterSuccessfulLogin($user);
    if ($username !== '') {
        smsClearLoginThrottle($username);
    }
    smsLoginGateClear();
    unset($_SESSION['pending_2fa']);

    session_regenerate_id(true);

    $_SESSION['user_id']       = (int) $user['id'];
    $_SESSION['user_name']     = (string) $user['full_name'];
    $_SESSION['user_role']     = (string) ($user['role_label'] ?? $user['role_key']);
    $_SESSION['user_role_key'] = (string) $user['role_key'];
    $_SESSION['user_email']    = (string) $user['email'];
    $_SESSION['must_change_password'] = (int) ($user['must_change_password'] ?? 0);
    $_SESSION['last_activity'] = time();
    $_SESSION['login_at'] = time();
    unset($_SESSION['presence_touched_at']);
    smsTouchUserPresence((int) $user['id']);

    if (!empty($user['student_id'])) {
        $_SESSION['student_id'] = (string) $user['student_id'];
    } else {
        unset($_SESSION['student_id']);
    }

    $primaryModule = smsPrimaryModuleForRole((string) $user['role_key']);

    logActivity(
        'login',
        'Logged in successfully',
        $primaryModule,
        (int) $user['id'],
        (string) $user['full_name'],
        (string) $user['role_key'],
        false
    );

    return [
        'ok' => true,
        'code' => 'ok',
        'message' => '',
        'alert' => 'success',
        'show_reset' => false,
        'locked' => false,
        'locked_until' => null,
    ];
}

/**
 * Database-backed login.
 */
function attemptLogin(string $username, string $password): bool
{
    return smsLoginAttempt($username, $password)['ok'];
}

/**
 * Create a password-reset token. Returns raw token (for link) or null.
 */
function smsCreatePasswordResetToken(int $userId): ?string
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    $raw = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);

    // Invalidate previous unused tokens
    $pdo->prepare(
        'UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL'
    )->execute([$userId]);

    $pdo->prepare(
        'INSERT INTO password_resets (user_id, token_hash, expires_at, created_ip)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?)'
    )->execute([$userId, $hash, smsClientIp()]);

    return $raw;
}

/**
 * Consume reset token and set new password.
 */
function smsResetPasswordWithToken(string $rawToken, string $newPassword): bool
{
    $pdo = db();
    if (!$pdo || strlen($newPassword) < (int) smsSetting('min_password_length', '8')) {
        return false;
    }

    $hash = hash('sha256', $rawToken);
    $stmt = $pdo->prepare(
        'SELECT * FROM password_resets
         WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }

    $userId = (int) $row['user_id'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            'UPDATE users
             SET password_hash = ?, must_change_password = 0, password_changed_at = NOW(),
                 failed_login_attempts = 0, locked_until = NULL,
                 status = CASE WHEN status = \'locked\' THEN \'active\' ELSE status END
             WHERE id = ?'
        )->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
            ->execute([(int) $row['id']]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        return false;
    }

    logActivity('password_reset', 'Password reset via token', 'System', $userId);
    return true;
}

/**
 * Admin / self password update.
 */
function smsSetUserPassword(int $userId, string $newPassword, bool $forceChange = false): bool
{
    $pdo = db();
    $minLen = (int) smsSetting('min_password_length', '8');
    if (!$pdo || strlen($newPassword) < $minLen) {
        return false;
    }

    $pdo->prepare(
        'UPDATE users
         SET password_hash = ?, must_change_password = ?, password_changed_at = NOW(),
             failed_login_attempts = 0, locked_until = NULL,
             status = CASE WHEN status = \'locked\' THEN \'active\' ELSE status END
         WHERE id = ?'
    )->execute([
        password_hash($newPassword, PASSWORD_DEFAULT),
        $forceChange ? 1 : 0,
        $userId,
    ]);

    try {
        $user = $pdo->prepare('SELECT username, email FROM users WHERE id = ? LIMIT 1');
        $user->execute([$userId]);
        $row = $user->fetch() ?: [];
        if (function_exists('smsClearLoginThrottle')) {
            smsClearLoginThrottle((string) ($row['username'] ?? ''));
            smsClearLoginThrottle((string) ($row['email'] ?? ''));
            smsClearLoginThrottle('');
        }
    } catch (Throwable $e) {
        error_log('smsSetUserPassword throttle clear skipped: ' . $e->getMessage());
    }

    return true;
}

function logout(): void
{
    $uid = isAuthenticated() ? getCurrentUserId() : null;
    if (isAuthenticated()) {
        $mod = smsPrimaryModuleForRole(getCurrentUserRoleKey());
        logActivity('logout', 'Logged out', $mod);
    }

    if ($uid) {
        smsMarkUserOffline((int) $uid);
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            (bool) ($params['secure'] ?? false),
            (bool) ($params['httponly'] ?? true)
        );
    }

    session_destroy();
}

/**
 * Ensure users.last_seen_at exists for online/offline presence.
 */
function smsEnsureUserPresenceColumn(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $pdo = db();
    if (!$pdo) {
        return;
    }
    try {
        $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_seen_at'")->fetch();
        if (!$col) {
            $pdo->exec(
                'ALTER TABLE users
                 ADD COLUMN last_seen_at DATETIME NULL AFTER last_login_at,
                 ADD KEY idx_users_last_seen (last_seen_at)'
            );
        }
    } catch (Throwable $e) {
        // Column may already exist under race — ignore
    }
}

/**
 * Heartbeat: mark the signed-in user as online (throttled).
 * Uses PHP clock (not MySQL NOW) so Online status matches app timezone.
 */
function smsTouchUserPresence(?int $userId = null): void
{
    $userId = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return;
    }

    $now = time();
    $lastTouch = (int) ($_SESSION['presence_touched_at'] ?? 0);
    if ($lastTouch > 0 && ($now - $lastTouch) < 45) {
        return;
    }

    smsEnsureUserPresenceColumn();
    $pdo = db();
    if (!$pdo) {
        return;
    }
    try {
        $stmt = $pdo->prepare('UPDATE users SET last_seen_at = ? WHERE id = ? LIMIT 1');
        $stmt->execute([date('Y-m-d H:i:s', $now), $userId]);
        $_SESSION['presence_touched_at'] = $now;
    } catch (Throwable $e) {
        // ignore
    }
}

function smsMarkUserOffline(int $userId): void
{
    if ($userId <= 0) {
        return;
    }
    smsEnsureUserPresenceColumn();
    $pdo = db();
    if (!$pdo) {
        return;
    }
    try {
        $stmt = $pdo->prepare('UPDATE users SET last_seen_at = NULL WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
    } catch (Throwable $e) {
        // ignore
    }
}

/** True when last_seen_at is within the online window (default 5 minutes). */
function smsUserIsOnline(?string $lastSeenAt, int $onlineSeconds = 300): bool
{
    $lastSeenAt = trim((string) $lastSeenAt);
    if ($lastSeenAt === '' || $lastSeenAt === '0000-00-00 00:00:00') {
        return false;
    }
    $ts = strtotime($lastSeenAt);
    if ($ts === false) {
        return false;
    }
    return (time() - $ts) <= max(60, $onlineSeconds);
}
