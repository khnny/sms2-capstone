<?php
/**
 * SMS 2 - Sidebar Navigation
 * Expects: optional $activeModule (string), optional $activePage (string)
 */
if (!isset($MODULES)) {
    require_once __DIR__ . '/../config/config.php';
}
require_once __DIR__ . '/authentication.php';
require_once __DIR__ . '/module-controls.php';
require_once __DIR__ . '/nav-icons.php';
require_once __DIR__ . '/navigation-context.php';
require_once __DIR__ . '/grant-review-workflow-urls.php';

$activeModule = $activeModule ?? '';
$activePage   = $activePage ?? '';
$roleKey = getCurrentUserRoleKey();
$sidebarMode = smsSidebarMode($roleKey);
$onDashboard = str_ends_with(
    str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')),
    '/dashboard/index.php'
);
$highlightModule = smsSidebarHighlightModule((string) $activeModule, $roleKey);
$roleHomeUrl = smsRoleHomeUrl($roleKey);
$roleHomeActive = smsRoleHomeIsActive($roleKey, str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')), (string) $activePage);
$showMainDashboard = smsShowsMainDashboard($roleKey);
$currentScriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$isCommunicationCalendar = str_ends_with($currentScriptPath, '/communication/calendar.php');
$isCommunicationAnnouncements = str_ends_with($currentScriptPath, '/communication/announcements.php');
$visibleModules = getVisibleModules($MODULES);
$securitySettingsModule = '';
$securitySettingsHrefOverride = '';
if (smsIsGrantedAdminRole($roleKey)) {
    $securitySettingsModule = 'admin';
    $securitySettingsHrefOverride = BASE_URL . '/account/profile.php?tab=security';
} else {
    foreach ($visibleModules as $securityModuleKey => $_securityModule) {
        if ($securityModuleKey !== 'user-management') {
            $securitySettingsModule = (string) $securityModuleKey;
            break;
        }
    }
    if ($securitySettingsModule === '' && in_array($roleKey, ['vpaa', 'research_office', 'department_chair', 'department_head', 'research_coordinator'], true)) {
        if (!function_exists('smsPrimaryModuleForRole')) {
            require_once __DIR__ . '/security-workflow.php';
        }
        $securitySettingsModule = (string) smsPrimaryModuleForRole($roleKey);
    }
    if ($roleKey === 'research_coordinator' || $roleKey === 'department_head') {
        $securitySettingsModule = $securitySettingsModule !== '' ? $securitySettingsModule : 'crad';
        $securitySettingsHrefOverride = BASE_URL . '/account/module-security.php?module=crad';
    }
}
$moduleHasSecuritySettingsPage = false;
if ($securitySettingsModule !== '' && isset($visibleModules[$securitySettingsModule]['pages'])) {
    foreach ((array) $visibleModules[$securitySettingsModule]['pages'] as $securityPage) {
        if (($securityPage['slug'] ?? '') === 'security-settings') {
            $moduleHasSecuritySettingsPage = true;
            break;
        }
    }
}

$studentNavGroups = [
    'Student Information' => [
        ['slug' => 'my-profile',  'href' => BASE_URL . '/modules/student-portal/pages/my-profile.php',  'icon' => 'fa-user',    'label' => 'My Profile',  'locked' => false],
        ['slug' => 'student-id',  'href' => BASE_URL . '/modules/student-portal/pages/student-id.php',  'icon' => 'fa-id-card', 'label' => 'Student ID',  'locked' => false],
    ],
    'Financial' => [
        ['slug' => 'account-balance',  'href' => BASE_URL . '/modules/student-portal/pages/account-balance.php',  'icon' => 'fa-wallet',  'label' => 'Account Balance',  'locked' => false],
        ['slug' => 'payment-history',  'href' => BASE_URL . '/modules/student-portal/pages/payment-history.php',  'icon' => 'fa-receipt', 'label' => 'Payment History',  'locked' => false],
        ['slug' => 'college-payment', 'href' => BASE_URL . '/modules/student-portal/pages/college-payment.php', 'icon' => 'fa-receipt', 'label' => 'College Payment', 'locked' => false],
    ],
    'Academics' => [
        ['slug' => 'class-schedule',      'href' => BASE_URL . '/modules/student-portal/pages/class-schedule.php',      'icon' => 'fa-calendar-alt',        'label' => 'Class Schedule',       'locked' => false],
        ['slug' => 'academic-records',    'href' => BASE_URL . '/modules/student-portal/pages/academic-records.php',    'icon' => 'fa-file-alt',            'label' => 'Academic Records',     'locked' => false],
        ['slug' => 'subjects-professors', 'href' => BASE_URL . '/modules/student-portal/pages/subjects-professors.php', 'icon' => 'fa-chalkboard-teacher',  'label' => 'Subject & Professors', 'locked' => false],
        ['slug' => 'grades-portal',       'href' => BASE_URL . '/modules/student-portal/pages/grades-portal.php',       'icon' => 'fa-star-half-alt',       'label' => 'Grades Portal',        'locked' => false],
    ],
    'Research Workspace' => [
        ['slug' => 'research-workspace', 'href' => BASE_URL . '/modules/student-portal/pages/research-workspace.php', 'icon' => 'fa-flask', 'label' => 'Research Workspace', 'locked' => false],
    ],
    'Core System' => [
        ['slug' => 'grant-opportunities', 'href' => BASE_URL . '/modules/crad/pages/grant-opportunities.php', 'icon' => 'fa-hand-holding-usd', 'label' => 'Grant Opportunities', 'locked' => false],
        ['slug' => 'proposals-applications', 'href' => BASE_URL . '/modules/crad/pages/proposals-applications.php', 'icon' => 'fa-file-alt', 'label' => 'Proposals & Applications', 'locked' => false],
        ['slug' => 'revisions-requested', 'href' => BASE_URL . '/modules/crad/pages/revisions-requested.php', 'icon' => 'fa-edit', 'label' => 'Revisions Requested', 'locked' => false],
    ],
    'Funded Research' => [
        ['slug' => 'funded-research', 'href' => BASE_URL . '/modules/crad/pages/funded-research.php', 'icon' => 'fa-flask', 'label' => 'Funded Research', 'locked' => false],
        ['slug' => 'project-milestones', 'href' => BASE_URL . '/modules/crad/pages/project-milestones.php', 'icon' => 'fa-tasks', 'label' => 'Project Milestones', 'locked' => false],
    ],
    'Outputs & Records' => [
        ['slug' => 'publications-ip', 'href' => BASE_URL . '/modules/crad/pages/publications-ip.php', 'icon' => 'fa-book-open', 'label' => 'Publications & IP', 'locked' => false],
    ],
    'System' => [
        ['slug' => 'security-settings', 'href' => BASE_URL . '/account/module-security.php?module=student_portal', 'icon' => 'fa-shield-alt', 'label' => 'Security Settings', 'locked' => false],
    ],
];
$studentDashboardItem = [
    'slug' => 'dashboard',
    'href' => BASE_URL . '/modules/student-portal/pages/dashboard.php',
    'icon' => 'fa-tachometer-alt',
    'label' => 'Dashboard',
];

$studentResearchPageSlugs = [
    'research-group',
    'research-proposal-submission',
    'my-research',
    'research-plan',
    'milestones',
    'progress-updates',
    'adviser-feedback',
    'final-manuscript',
    'submit-documents',
    'submit-chapters',
    'my-submissions',
    'submission-status',
    'submission-history',
    'research-clearance',
];

$facultyAccountNavGroups = [
    'Research Status' => [
        ['slug' => 'assignment-confirmation', 'href' => BASE_URL . '/modules/faculty/pages/assignment-confirmation.php', 'icon' => 'ti-heart-handshake', 'label' => 'Assignment Confirmation'],
        ['slug' => 'approved-research', 'href' => BASE_URL . '/modules/faculty/pages/approved-research.php', 'icon' => 'ti-clipboard-check', 'label' => 'Research Status'],
    ],
    'My Research' => [
        ['slug' => 'assigned-research', 'href' => BASE_URL . '/modules/faculty/pages/assigned-research.php', 'icon' => 'fa-flask', 'label' => 'Assigned Research'],
        ['slug' => 'final-manuscript-review', 'href' => BASE_URL . '/modules/crad/pages/final-manuscript-review.php', 'icon' => 'fa-file-signature', 'label' => 'Final Manuscript Review'],
        ['slug' => 'research-details', 'href' => BASE_URL . '/modules/faculty/pages/research-details.php', 'icon' => 'fa-file-alt', 'label' => 'Research Details'],
        ['slug' => 'research-progress', 'href' => BASE_URL . '/modules/faculty/pages/research-progress.php', 'icon' => 'fa-tasks', 'label' => 'Research Progress'],
        ['slug' => 'research-documents', 'href' => BASE_URL . '/modules/faculty/pages/research-documents.php', 'icon' => 'fa-folder-open', 'label' => 'Research Documents'],
    ],
];

// ── Add Research Monitoring section (DUPLICATE PREVENTION: Check if not already present) ──
if (!isset($facultyAccountNavGroups['Research Monitoring'])) {
    $facultyAccountNavGroups['Research Monitoring'] = [
        ['slug' => 'my-research-groups', 'href' => BASE_URL . '/modules/faculty/pages/my-research-groups.php', 'icon' => 'fa-users', 'label' => 'My Research Groups'],
        ['slug' => 'final-defense-revision-monitoring', 'href' => BASE_URL . '/modules/faculty/pages/final-defense-revision-monitoring.php', 'icon' => 'fa-redo', 'label' => 'Final Defense Revisions'],
        ['slug' => 'research-progress-monitoring', 'href' => BASE_URL . '/modules/faculty/pages/research-progress-monitoring.php', 'icon' => 'fa-chart-line', 'label' => 'Research Progress'],
        ['slug' => 'milestones-overview', 'href' => BASE_URL . '/modules/faculty/pages/milestones-overview.php', 'icon' => 'fa-tasks', 'label' => 'Milestones'],
        ['slug' => 'revision-monitoring', 'href' => BASE_URL . '/modules/faculty/pages/revision-monitoring.php', 'icon' => 'fa-redo', 'label' => 'Revision Monitoring'],
        ['slug' => 'submitted-updates', 'href' => BASE_URL . '/modules/faculty/pages/submitted-updates.php', 'icon' => 'fa-inbox', 'label' => 'Submitted Updates'],
        ['slug' => 'adviser-feedback-history', 'href' => BASE_URL . '/modules/faculty/pages/adviser-feedback-history.php', 'icon' => 'fa-comments', 'label' => 'Adviser Feedback'],
    ];
}

// Continue with existing sections
$facultyAccountNavGroups += [
    'Grades Portal' => [
        ['slug' => 'grade-entry', 'href' => BASE_URL . '/modules/faculty/pages/grade-entry.php', 'icon' => 'fa-pen', 'label' => 'Grade Entry'],
        ['slug' => 'grade-records', 'href' => BASE_URL . '/modules/faculty/pages/grade-records.php', 'icon' => 'fa-list-alt', 'label' => 'Grade Records'],
        ['slug' => 'grade-summary', 'href' => BASE_URL . '/modules/faculty/pages/grade-summary.php', 'icon' => 'fa-chart-pie', 'label' => 'Grade Summary'],
    ],
    'Schedule' => [
        ['slug' => 'my-schedule', 'href' => BASE_URL . '/modules/faculty/pages/my-schedule.php', 'icon' => 'fa-calendar', 'label' => 'My Schedule'],
        ['slug' => 'defense-schedule', 'href' => BASE_URL . '/modules/faculty/pages/defense-schedule.php', 'icon' => 'fa-calendar-check', 'label' => 'Defense Schedule'],
    ],
    'Profile' => [
        ['slug' => 'my-profile', 'href' => BASE_URL . '/modules/faculty/pages/my-profile.php', 'icon' => 'fa-user', 'label' => 'My Profile'],
        ['slug' => 'expertise', 'href' => BASE_URL . '/modules/faculty/pages/expertise.php', 'icon' => 'fa-brain', 'label' => 'Expertise'],
        ['slug' => 'availability', 'href' => BASE_URL . '/modules/faculty/pages/availability.php', 'icon' => 'fa-user-check', 'label' => 'Availability'],
    ],
    'System' => [
        ['slug' => 'security-settings', 'href' => BASE_URL . '/account/module-security.php?module=faculty', 'icon' => 'fa-shield-alt', 'label' => 'Security Settings'],
    ],
];

// ── Adviser visibility-only: hide the entire "MY RESEARCH" sidebar section.
//    This affects ONLY the Adviser account. Backend pages/APIs/tables are NOT
//    deleted; their navigation entries are suppressed for the Adviser role only.
if ($roleKey === 'adviser' && isset($facultyAccountNavGroups['My Research'])) {
    unset($facultyAccountNavGroups['My Research']);
}
// ── Adviser: Research Clearance (adviser signing) removed from sidebar.
// Students upload signed forms; CRAD approves or rejects.
if ($roleKey === 'adviser' && isset($facultyAccountNavGroups['Research Clearance'])) {
    unset($facultyAccountNavGroups['Research Clearance']);
}

// ── Adviser: Core System grant pages (researchers apply to published calls) ──
if ($roleKey === 'adviser') {
    $coreSystemItems = [
        ['slug' => 'grant-opportunities', 'href' => BASE_URL . '/modules/crad/pages/grant-opportunities.php', 'icon' => 'fa-hand-holding-usd', 'label' => 'Grant Opportunities'],
        ['slug' => 'proposals-applications', 'href' => BASE_URL . '/modules/crad/pages/proposals-applications.php', 'icon' => 'fa-file-alt', 'label' => 'Proposals & Applications'],
        ['slug' => 'revisions-requested', 'href' => BASE_URL . '/modules/crad/pages/revisions-requested.php', 'icon' => 'fa-edit', 'label' => 'Revisions Requested'],
    ];
    $fundedResearchItems = [
        ['slug' => 'funded-research', 'href' => BASE_URL . '/modules/crad/pages/funded-research.php', 'icon' => 'fa-flask', 'label' => 'Funded Research'],
        ['slug' => 'project-milestones', 'href' => BASE_URL . '/modules/crad/pages/project-milestones.php', 'icon' => 'fa-tasks', 'label' => 'Project Milestones'],
    ];
    $reviewWorkflowItems = grantReviewWorkflowSidebarItems('adviser');
    $facultyInsert = [];
    foreach ($facultyAccountNavGroups as $groupKey => $groupItems) {
        if ($groupKey === 'System') {
            $facultyInsert['Core System'] = $coreSystemItems;
            $facultyInsert['Funded Research'] = $fundedResearchItems;
            $facultyInsert['Review & Workflow'] = $reviewWorkflowItems;
        }
        $facultyInsert[$groupKey] = $groupItems;
    }
    if (!isset($facultyInsert['Core System'])) {
        $facultyInsert['Core System'] = $coreSystemItems;
    }
    if (!isset($facultyInsert['Funded Research'])) {
        $facultyInsert['Funded Research'] = $fundedResearchItems;
    }
    if (!isset($facultyInsert['Review & Workflow'])) {
        $facultyInsert['Review & Workflow'] = $reviewWorkflowItems;
    }
    $facultyAccountNavGroups = $facultyInsert;
}

$deanFacultyBaseUrl = BASE_URL . '/modules/faculty/pages/';
$deanGrantNavGroups = [
    'Faculty Management' => [
        ['slug' => 'faculty-profile', 'href' => $deanFacultyBaseUrl . 'faculty-profile.php', 'icon' => 'fa-id-badge', 'label' => 'Faculty Profile'],
        ['slug' => 'subject-load-tracker', 'href' => $deanFacultyBaseUrl . 'subject-load-tracker.php', 'icon' => 'fa-tasks', 'label' => 'Subject Load Tracker'],
        ['slug' => 'schedule-assignment', 'href' => $deanFacultyBaseUrl . 'schedule-assignment.php', 'icon' => 'fa-calendar-check', 'label' => 'Schedule Assignment'],
        ['slug' => 'attendance-monitoring', 'href' => $deanFacultyBaseUrl . 'attendance-monitoring.php', 'icon' => 'fa-user-check', 'label' => 'Attendance Monitoring'],
        ['slug' => 'leave-application-approval', 'href' => $deanFacultyBaseUrl . 'leave-application-approval.php', 'icon' => 'fa-plane-departure', 'label' => 'Leave Application & Approval'],
        ['slug' => 'salary-grade-payroll-setup', 'href' => $deanFacultyBaseUrl . 'salary-grade-payroll-setup.php', 'icon' => 'fa-money-check-alt', 'label' => 'Salary Grade & Pay Set Up'],
        ['slug' => 'teaching-history', 'href' => $deanFacultyBaseUrl . 'teaching-history.php', 'icon' => 'fa-history', 'label' => 'Teaching History'],
        ['slug' => 'clearance-system', 'href' => $deanFacultyBaseUrl . 'clearance-system.php', 'icon' => 'fa-stamp', 'label' => 'Clearance System'],
        ['slug' => 'evaluation-summary', 'href' => $deanFacultyBaseUrl . 'evaluation-summary.php', 'icon' => 'fa-star', 'label' => 'Evaluation Summary'],
        ['slug' => 'faculty-directory', 'href' => $deanFacultyBaseUrl . 'faculty-directory.php', 'icon' => 'fa-address-book', 'label' => 'Faculty Directory'],
    ],
    'Review & Workflow' => grantReviewWorkflowSidebarItems('hr'),
    'System' => [
        ['slug' => 'security-settings', 'href' => BASE_URL . '/account/module-security.php?module=faculty', 'icon' => 'fa-shield-alt', 'label' => 'Security Settings'],
    ],
];

$grantApprovalSidebarRoles = ['qa', 'vpaa', 'department_chair', 'research_office'];

$grammarianNavGroups = [
    'Evaluation' => [
        ['slug' => 'for-evaluation', 'href' => BASE_URL . '/modules/faculty/pages/for-evaluation.php', 'icon' => 'fa-clipboard-check', 'label' => 'For Evaluation'],
        ['slug' => 'evaluation-scoring', 'href' => BASE_URL . '/modules/faculty/pages/evaluation-scoring.php', 'icon' => 'fa-star-half-alt', 'label' => 'Evaluation & Scoring'],
        ['slug' => 'evaluation-history', 'href' => BASE_URL . '/modules/faculty/pages/evaluation-history.php', 'icon' => 'fa-history', 'label' => 'Evaluation History'],
    ],
    'System' => [
        ['slug' => 'security-settings', 'href' => BASE_URL . '/account/module-security.php?module=faculty', 'icon' => 'fa-shield-alt', 'label' => 'Security Settings'],
    ],
];

$panelNavGroups = [
    'DEFENSE' => [
        ['slug' => 'assigned-defenses', 'href' => BASE_URL . '/modules/faculty/pages/assigned-defenses.php', 'icon' => 'fa-clipboard-list', 'label' => 'Assigned Defenses'],
        ['slug' => 'defense-details', 'href' => BASE_URL . '/modules/faculty/pages/defense-details.php', 'icon' => 'fa-file-alt', 'label' => 'Defense Details'],
        ['slug' => 'panel-evaluation-scoring', 'href' => BASE_URL . '/modules/faculty/pages/panel-evaluation-scoring.php', 'icon' => 'fa-star-half-alt', 'label' => 'Evaluation & Scoring'],
        ['slug' => 'panel-evaluation-history', 'href' => BASE_URL . '/modules/faculty/pages/panel-evaluation-history.php', 'icon' => 'fa-history', 'label' => 'Evaluation History'],
        ['slug' => 'panel-final-defense-evaluation', 'href' => BASE_URL . '/modules/faculty/pages/panel-final-defense-evaluation.php', 'icon' => 'fa-clipboard-check', 'label' => 'Final Defense Evaluation'],
    ],
    'PROFILE' => [
        ['slug' => 'my-profile', 'href' => BASE_URL . '/modules/faculty/pages/my-profile.php', 'icon' => 'fa-user', 'label' => 'My Profile'],
        ['slug' => 'availability', 'href' => BASE_URL . '/modules/faculty/pages/availability.php', 'icon' => 'fa-user-check', 'label' => 'Availability'],
    ],
    'System' => [
        ['slug' => 'security-settings', 'href' => BASE_URL . '/account/module-security.php?module=faculty', 'icon' => 'fa-shield-alt', 'label' => 'Security Settings'],
    ],
];

$researchDirectorNavGroups = [
    'SYSTEM' => [
        ['slug' => 'security-settings', 'href' => BASE_URL . '/account/module-security.php?module=faculty', 'icon' => 'fa-shield-alt', 'label' => 'Security Settings'],
    ],
];

$facultyResearchPageSlugs = [
    'assignment-confirmation',
    'approved-research',
    'assigned-research',
    'final-manuscript-review',
    'research-details',
    'research-progress',
    'research-documents',
    'my-research-groups',
    'final-defense-revision-monitoring',
    'research-progress-monitoring',
    'milestones-overview',
    'revision-monitoring',
    'submitted-updates',
    'adviser-feedback-history',
    'research-clearance',
];
$cradWorkspaceRoleKeys = ['crad_officer', 'research_coordinator', 'department_head'];
$cradResearchWorkspaceSlugs = [
    'register-proposal',
    'research-group-number',
    'retrieve-approved-research',
    'adviser-panel-assignment',
    'research-coordinator-management',
    'capstone-group-student-registry',
    'research-defense-scheduling',
    'research-clearance',
    'approval-clearance-payment',
    'final-manuscript-review',
    'revision-compliance',
    'final-manuscript-approval',
    'research-group-approvals',
    'approved-research',
    'assignment-confirmation',
    'assign-research-adviser',
];
?>
<aside class="sms-sidebar <?= smsIsGrantedAdminRole($roleKey) ? 'admin-sidebar' : '' ?> admin-sidebar-collapsible <?= $sidebarMode === 'faculty_workspace' ? 'workspace-sidebar' : '' ?> <?= ($roleKey === 'research_director' && $sidebarMode === 'faculty_workspace') ? 'research-director-sidebar' : '' ?>" id="smsSidebar" aria-label="Main navigation">
    <nav class="sidebar-nav" id="smsSidebarAccordion">
        <ul class="nav flex-column">
            <?php if ($sidebarMode === 'student'): ?>
                <li class="nav-item sidebar-home-item">
                    <a class="nav-link sidebar-home-link <?= $activeModule === 'student_portal' && $activePage === 'dashboard' ? 'active' : '' ?>"
                       href="<?= htmlspecialchars($studentDashboardItem['href']) ?>"
                       data-title="<?= htmlspecialchars($studentDashboardItem['label']) ?>"
                       title="<?= htmlspecialchars($studentDashboardItem['label']) ?>">
                        <?= smsIcon($studentDashboardItem['icon'], ['aria-hidden' => 'true']) ?>
                        <span><?= htmlspecialchars($studentDashboardItem['label']) ?></span>
                    </a>
                </li>
                <li class="nav-item sidebar-home-item">
                    <a class="nav-link sidebar-home-link <?= $isCommunicationCalendar ? 'active' : '' ?>"
                       href="<?= e(BASE_URL . '/communication/calendar.php') ?>"
                       data-title="Research Calendar"
                       title="Research Calendar">
                        <?= smsIcon('calendar-alt', ['aria-hidden' => 'true']) ?>
                        <span>Research Calendar</span>
                    </a>
                </li>
                <li class="nav-item sidebar-home-item">
                    <a class="nav-link sidebar-home-link <?= $isCommunicationAnnouncements ? 'active' : '' ?>"
                       href="<?= e(BASE_URL . '/communication/announcements.php') ?>"
                       data-title="Announcement Bulletin"
                       title="Announcement Bulletin">
                        <?= smsIcon('bullhorn', ['aria-hidden' => 'true']) ?>
                        <span>Announcement Bulletin</span>
                    </a>
                </li>
                <?php foreach ($studentNavGroups as $groupLabel => $groupItems): ?>
                    <?php
                    $groupCollapseId = 'navGrp_' . preg_replace('/[^a-z0-9_]/', '_', strtolower((string) $groupLabel));
                    $isGroupActive = false;
                    $isWorkspaceDestination = $groupLabel === 'Research Workspace';
                    $groupOverviewUrl = '';
                    foreach ($groupItems as $groupItemProbe) {
                        if (empty($groupItemProbe['locked']) && $groupOverviewUrl === '') {
                            $groupOverviewUrl = (string) ($groupItemProbe['href'] ?? '');
                        }
                        if ($activeModule === 'student_portal' && ($activePage ?? '') === ($groupItemProbe['slug'] ?? '')) {
                            $isGroupActive = true;
                        }
                    }
                    if ($isWorkspaceDestination && $activeModule === 'student_portal' && in_array($activePage, $studentResearchPageSlugs, true)) {
                        $isGroupActive = true;
                    }
                    $groupIcon = (string) ($groupItems[0]['icon'] ?? 'fa-folder');
                    ?>
                    <li class="nav-item admin-module-item">
                        <?php if ($isWorkspaceDestination): ?>
                        <a class="nav-link sidebar-home-link <?= $isGroupActive ? 'active' : '' ?>"
                           href="<?= htmlspecialchars($groupOverviewUrl) ?>"
                           data-title="Research Workspace"
                           title="Research Workspace">
                            <?= smsIcon($groupIcon, ['aria-hidden' => 'true']) ?>
                            <span>Research Workspace</span>
                        </a>
                        <?php else: ?>
                        <button type="button"
                                class="nav-link sidebar-parent admin-module-toggle <?= $isGroupActive ? 'active' : '' ?>"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= htmlspecialchars($groupCollapseId) ?>"
                                aria-expanded="<?= $isGroupActive ? 'true' : 'false' ?>"
                                aria-controls="<?= htmlspecialchars($groupCollapseId) ?>"
                                data-overview-url="<?= htmlspecialchars($groupOverviewUrl) ?>"
                                data-title="<?= htmlspecialchars((string) $groupLabel) ?>"
                                title="<?= htmlspecialchars((string) $groupLabel) ?>">
                            <?= smsIcon($groupIcon, ['aria-hidden' => 'true']) ?>
                            <span><?= htmlspecialchars((string) $groupLabel) ?></span>
                            <?= smsIcon('chevron-down', ['class' => 'sidebar-chevron ms-auto', 'aria-hidden' => 'true']) ?>
                        </button>
                        <div class="collapse admin-module-body sidebar-submenu <?= $isGroupActive ? 'show' : '' ?>"
                             id="<?= htmlspecialchars($groupCollapseId) ?>">
                            <ul class="nav flex-column">
                    <?php foreach ($groupItems as $item): ?>
                        <?php
                        $isLocked  = !empty($item['locked']);
                        $linkClass = ($activeModule === 'student_portal' && $activePage === $item['slug']) ? 'active' : '';
                        if ($isLocked) { $linkClass .= ' nav-link-locked'; }
                        ?>
                        <li class="nav-item">
                            <?php if ($isLocked): ?>
                                <span class="nav-link sidebar-sub <?= $linkClass ?>"
                                      data-title="<?= htmlspecialchars($item['label']) ?> (Locked)"
                                      title="<?= htmlspecialchars($item['label']) ?> - <?= htmlspecialchars((string) ($item['lock_reason'] ?? 'Pay Research Forum to unlock')) ?>"
                                      style="cursor:not-allowed;opacity:0.5;">
                                    <?= smsIcon('lock', ['class' => 'me-1', 'aria-hidden' => 'true', 'style' => 'font-size:0.75rem;']) ?>
                                    <?= smsIcon($item['icon'], ['aria-hidden' => 'true']) ?>
                                    <span><?= htmlspecialchars($item['label']) ?></span>
                                </span>
                            <?php else: ?>
                                <a class="nav-link sidebar-sub <?= $linkClass ?>"
                                   href="<?= htmlspecialchars($item['href']) ?>"
                                   data-title="<?= htmlspecialchars($item['label']) ?>"
                                   title="<?= htmlspecialchars($item['label']) ?>">
                                    <?= smsIcon($item['icon'], ['aria-hidden' => 'true']) ?>
                                    <span><?= htmlspecialchars($item['label']) ?></span>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>

            <?php elseif ($sidebarMode === 'faculty_workspace'): ?>
                <?php
                $accountNavGroups = $facultyAccountNavGroups;
                if ($roleKey === 'research_director') {
                    $accountNavGroups = $researchDirectorNavGroups;
                } elseif ($roleKey === 'grammarian') {
                    $accountNavGroups = $grammarianNavGroups;
                } elseif ($roleKey === 'panel') {
                    $accountNavGroups = $panelNavGroups;
                } elseif ($roleKey === 'hr') {
                    $accountNavGroups = $deanGrantNavGroups;
                }
                $facultyResearchWorkspacePages = $roleKey === 'adviser' ? $facultyResearchPageSlugs : [];
                if ($roleKey === 'adviser') {
                    $workspaceItem = [
                        'slug' => 'research-workspace',
                        'href' => BASE_URL . '/modules/faculty/pages/research-workspace.php',
                        'icon' => 'fa-flask',
                        'label' => 'Research Workspace',
                    ];
                    $filteredGroups = [];
                    foreach ($accountNavGroups as $groupLabel => $groupItems) {
                        $groupItems = array_values(array_filter(
                            $groupItems,
                            static fn(array $item): bool => !in_array((string) ($item['slug'] ?? ''), $facultyResearchPageSlugs, true)
                        ));
                        if ($groupItems !== []) {
                            $filteredGroups[$groupLabel] = $groupItems;
                        }
                    }
                    $accountNavGroups = ['Research Workspace' => [$workspaceItem]] + $filteredGroups;
                }
                $facultyWorkspaceFirstGroup = true;
                ?>
                <li class="nav-item sidebar-home-item">
                    <a class="nav-link sidebar-home-link <?= $roleHomeActive ? 'active' : '' ?>"
                       href="<?= htmlspecialchars($roleHomeUrl) ?>"
                       data-overview-url="<?= htmlspecialchars($roleHomeUrl) ?>"
                       data-title="Dashboard"
                       title="Dashboard">
                        <?= smsIcon('home', ['aria-hidden' => 'true']) ?>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php foreach ($accountNavGroups as $groupLabel => $groupItems): ?>
                    <?php
                    $groupCollapseId = 'navGrp_' . preg_replace('/[^a-z0-9_]/', '_', strtolower((string) $groupLabel));
                    $isGroupActive = false;
                    $isWorkspaceDestination = $groupLabel === 'Research Workspace';
                    $groupOverviewUrl = '';
                    foreach ($groupItems as $groupItemProbe) {
                        if ($groupOverviewUrl === '') {
                            $groupOverviewUrl = (string) ($groupItemProbe['href'] ?? '');
                        }
                        if (($activePage ?? '') === ($groupItemProbe['slug'] ?? '')) {
                            $isGroupActive = true;
                        }
                    }
                    if ($isWorkspaceDestination && in_array($activePage, $facultyResearchWorkspacePages, true)) {
                        $isGroupActive = true;
                    }
                    if ($facultyWorkspaceFirstGroup) {
                        $facultyWorkspaceFirstGroup = false;
                    }
                    $groupIcon = (string) ($groupItems[0]['icon'] ?? 'fa-folder');
                    ?>
                    <li class="nav-item admin-module-item">
                        <?php if ($isWorkspaceDestination): ?>
                        <a class="nav-link sidebar-home-link <?= $isGroupActive ? 'active' : '' ?>"
                           href="<?= htmlspecialchars($groupOverviewUrl) ?>"
                           data-title="Research Workspace"
                           title="Research Workspace">
                            <?= smsIcon($groupIcon, ['aria-hidden' => 'true']) ?>
                            <span>Research Workspace</span>
                        </a>
                        <?php else: ?>
                        <button type="button"
                                class="nav-link sidebar-parent admin-module-toggle <?= $isGroupActive ? 'active' : '' ?>"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= htmlspecialchars($groupCollapseId) ?>"
                                aria-expanded="<?= $isGroupActive ? 'true' : 'false' ?>"
                                aria-controls="<?= htmlspecialchars($groupCollapseId) ?>"
                                data-overview-url="<?= htmlspecialchars($groupOverviewUrl) ?>"
                                data-title="<?= htmlspecialchars((string) $groupLabel) ?>"
                                title="<?= htmlspecialchars((string) $groupLabel) ?>">
                            <?= smsIcon($groupIcon, ['aria-hidden' => 'true']) ?>
                            <span><?= htmlspecialchars((string) $groupLabel) ?></span>
                            <?= smsIcon('chevron-down', ['class' => 'sidebar-chevron ms-auto', 'aria-hidden' => 'true']) ?>
                        </button>
                        <div class="collapse admin-module-body sidebar-submenu <?= $isGroupActive ? 'show' : '' ?>"
                             id="<?= htmlspecialchars($groupCollapseId) ?>">
                            <ul class="nav flex-column">
                    <?php foreach ($groupItems as $item): ?>
                        <?php $linkClass = ($activePage === $item['slug']) ? 'active' : ''; ?>
                        <li class="nav-item">
                            <a class="nav-link sidebar-sub <?= $linkClass ?>"
                               href="<?= htmlspecialchars($item['href']) ?>"
                               data-title="<?= htmlspecialchars($item['label']) ?>"
                               title="<?= htmlspecialchars($item['label']) ?>">
                                <?= smsIcon($item['icon'], ['aria-hidden' => 'true']) ?>
                                <span><?= htmlspecialchars($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>

            <?php else: ?>
                <?php if ($showMainDashboard): ?>
                <li class="nav-item sidebar-home-item">
                    <a class="nav-link sidebar-home-link <?= $onDashboard ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/dashboard/index.php"
                       data-overview-url="<?= BASE_URL ?>/dashboard/index.php"
                       data-title="Dashboard"
                       title="Dashboard">
                        <?= smsIcon('layout-grid', ['aria-hidden' => 'true']) ?>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php else: ?>
                <li class="nav-item sidebar-home-item">
                    <a class="nav-link sidebar-home-link <?= $roleHomeActive ? 'active' : '' ?>"
                       href="<?= htmlspecialchars($roleHomeUrl) ?>"
                       data-overview-url="<?= htmlspecialchars($roleHomeUrl) ?>"
                       data-title="Dashboard"
                       title="Dashboard">
                        <?= smsIcon('layout-grid', ['aria-hidden' => 'true']) ?>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item sidebar-home-item">
                    <a class="nav-link sidebar-home-link <?= $isCommunicationCalendar ? 'active' : '' ?>"
                       href="<?= e(BASE_URL . '/communication/calendar.php') ?>"
                       data-title="Research Calendar"
                       title="Research Calendar">
                        <?= smsIcon('calendar-alt', ['aria-hidden' => 'true']) ?>
                        <span>Research Calendar</span>
                    </a>
                </li>
                <li class="nav-item sidebar-home-item">
                    <a class="nav-link sidebar-home-link <?= $isCommunicationAnnouncements ? 'active' : '' ?>"
                       href="<?= e(BASE_URL . '/communication/announcements.php') ?>"
                       data-title="Announcement Bulletin"
                       title="Announcement Bulletin">
                        <?= smsIcon('bullhorn', ['aria-hidden' => 'true']) ?>
                        <span>Announcement Bulletin</span>
                    </a>
                </li>

                <?php if ($roleKey === 'department_chair'): ?>
                    <?php
                    $chairDefenseItems = $panelNavGroups['DEFENSE'];
                    $chairDefenseActive = false;
                    $chairDefenseOverview = (string) ($chairDefenseItems[0]['href'] ?? '');
                    foreach ($chairDefenseItems as $chairDefenseProbe) {
                        if (($activePage ?? '') === ($chairDefenseProbe['slug'] ?? '')) {
                            $chairDefenseActive = true;
                            break;
                        }
                    }
                    $chairDefenseCollapseId = 'navGrp_dept_chair_defense';
                    ?>
                    <li class="nav-item admin-module-item">
                        <button type="button"
                                class="nav-link sidebar-parent admin-module-toggle <?= $chairDefenseActive ? 'active' : '' ?>"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= htmlspecialchars($chairDefenseCollapseId) ?>"
                                aria-expanded="<?= $chairDefenseActive ? 'true' : 'false' ?>"
                                aria-controls="<?= htmlspecialchars($chairDefenseCollapseId) ?>"
                                data-overview-url="<?= htmlspecialchars($chairDefenseOverview) ?>"
                                data-title="DEFENSE"
                                title="DEFENSE">
                            <?= smsIcon((string) ($chairDefenseItems[0]['icon'] ?? 'fa-clipboard-list'), ['aria-hidden' => 'true']) ?>
                            <span>DEFENSE</span>
                            <?= smsIcon('chevron-down', ['class' => 'sidebar-chevron ms-auto', 'aria-hidden' => 'true']) ?>
                        </button>
                        <div class="collapse admin-module-body sidebar-submenu <?= $chairDefenseActive ? 'show' : '' ?>"
                             id="<?= htmlspecialchars($chairDefenseCollapseId) ?>">
                            <ul class="nav flex-column">
                                <?php foreach ($chairDefenseItems as $chairDefenseItem): ?>
                                <li class="nav-item">
                                    <a class="nav-link sidebar-sub <?= (($activePage ?? '') === $chairDefenseItem['slug']) ? 'active' : '' ?>"
                                       href="<?= htmlspecialchars($chairDefenseItem['href']) ?>"
                                       data-title="<?= htmlspecialchars($chairDefenseItem['label']) ?>"
                                       title="<?= htmlspecialchars($chairDefenseItem['label']) ?>">
                                        <?= smsIcon($chairDefenseItem['icon'], ['aria-hidden' => 'true']) ?>
                                        <span><?= htmlspecialchars($chairDefenseItem['label']) ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>

                <?php if ($sidebarMode === 'admin_modules' && in_array($roleKey, $grantApprovalSidebarRoles, true)): ?>
                    <?php
                    $grantApprovalSidebarItems = grantReviewWorkflowSidebarItems($roleKey);
                    $gawActive = in_array(($activePage ?? ''), ['approval-workflows', 'reviewer-evaluation'], true);
                    $gawCollapseId = 'navGrp_grant_approval_workflow';
                    $gawOverviewUrl = $grantApprovalSidebarItems[0]['href'];
                    ?>
                    <li class="nav-item admin-module-item">
                        <button type="button"
                                class="nav-link sidebar-parent admin-module-toggle <?= $gawActive ? 'active' : '' ?>"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= htmlspecialchars($gawCollapseId) ?>"
                                aria-expanded="<?= $gawActive ? 'true' : 'false' ?>"
                                aria-controls="<?= htmlspecialchars($gawCollapseId) ?>"
                                data-overview-url="<?= htmlspecialchars($gawOverviewUrl) ?>"
                                data-title="Review &amp; Workflow"
                                title="Review &amp; Workflow">
                            <?= smsIcon('clipboard-check', ['aria-hidden' => 'true']) ?>
                            <span>Review &amp; Workflow</span>
                            <?= smsIcon('chevron-down', ['class' => 'sidebar-chevron ms-auto', 'aria-hidden' => 'true']) ?>
                        </button>
                        <div class="collapse admin-module-body sidebar-submenu <?= $gawActive ? 'show' : '' ?>"
                             id="<?= htmlspecialchars($gawCollapseId) ?>">
                            <ul class="nav flex-column">
                                <?php foreach ($grantApprovalSidebarItems as $gawItem): ?>
                                <?php if ($roleKey === 'finance' && ($gawItem['slug'] ?? '') === 'reviewer-evaluation') { continue; } ?>
                                <li class="nav-item">
                                    <a class="nav-link sidebar-sub <?= (($activePage ?? '') === $gawItem['slug']) ? 'active' : '' ?>"
                                       href="<?= htmlspecialchars($gawItem['href']) ?>"
                                       data-title="<?= htmlspecialchars($gawItem['label']) ?>"
                                       title="<?= htmlspecialchars($gawItem['label']) ?>">
                                        <?= smsIcon($gawItem['icon'], ['aria-hidden' => 'true']) ?>
                                        <span><?= htmlspecialchars($gawItem['label']) ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>

                <?php foreach ($visibleModules as $navModuleKey => $module): ?>
                    <?php
                    $isModuleActive = ($highlightModule === $navModuleKey);
                    if (
                        $roleKey === 'department_head'
                        && $navModuleKey === 'crad'
                        && $activePage !== 'security-settings'
                    ) {
                        $isModuleActive = true;
                    }
                    $moduleFolder = match ($navModuleKey) {
                        'student_portal' => 'student-portal',
                        'crad_grant'     => 'crad',
                        default          => $navModuleKey,
                    };
                    $overviewUrl = !empty($module['hide_overview'])
                        ? $roleHomeUrl
                        : (BASE_URL . '/modules/' . $moduleFolder . '/index.php');
                    $moduleInMaint = smsIsModuleInMaintenance((string) $navModuleKey);
                    $moduleIcon = (string) ($module['icon'] ?? 'fa-folder');
                    $moduleCollapseId = 'adminMod_' . preg_replace('/[^a-z0-9_]/', '_', (string) $navModuleKey);
                    $moduleGroups = isset($module['groups']) && is_array($module['groups']) ? $module['groups'] : [];
                    $showCradWorkspace = $navModuleKey === 'crad' && in_array($roleKey, $cradWorkspaceRoleKeys, true);
                    if ($showCradWorkspace) {
                        foreach ($moduleGroups as $groupLabel => $groupSlugs) {
                            $remainingSlugs = array_values(array_filter(
                                (array) $groupSlugs,
                                static fn(string $slug): bool => !in_array($slug, $cradResearchWorkspaceSlugs, true)
                            ));
                            if ($remainingSlugs === []) {
                                unset($moduleGroups[$groupLabel]);
                            } else {
                                $moduleGroups[$groupLabel] = $remainingSlugs;
                            }
                        }
                    }
                    $hasGroups = $moduleGroups !== [];
                    $activeGroupLabel = null;
                    if ($hasGroups && $isModuleActive && $activePage !== '') {
                        foreach ($moduleGroups as $groupLabel => $groupSlugs) {
                            if (in_array($activePage, $groupSlugs, true)) {
                                $activeGroupLabel = (string) $groupLabel;
                                break;
                            }
                        }
                    }
                    $pageTitles = [];
                    $modulePages = isset($module['pages']) && is_array($module['pages']) ? $module['pages'] : [];
                    foreach ($modulePages as $p) {
                        $pageTitles[$p['slug']] = $p['title'];
                    }
                    $showModuleOverview = empty($module['hide_overview']);
                    $onModuleOverview = ($activeModule === $navModuleKey && $activePage === '');
                    $showModuleGroups = $hasGroups;
                    $isCradWorkspaceActive = $showCradWorkspace
                        && ($activePage === 'research-workspace' || in_array($activePage, $cradResearchWorkspaceSlugs, true));
                    ?>

                    <li class="nav-item admin-module-item">
                        <button type="button"
                                class="nav-link sidebar-parent admin-module-toggle <?= $isModuleActive ? 'active' : '' ?>"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= htmlspecialchars($moduleCollapseId) ?>"
                                aria-expanded="<?= $isModuleActive ? 'true' : 'false' ?>"
                                aria-controls="<?= htmlspecialchars($moduleCollapseId) ?>"
                                data-overview-url="<?= htmlspecialchars($overviewUrl) ?>"
                                data-title="<?= htmlspecialchars((string) $module['label']) ?>"
                                title="<?= htmlspecialchars((string) $module['label']) ?>">
                            <?= smsIcon($moduleIcon, ['aria-hidden' => 'true']) ?>
                            <span><?= htmlspecialchars((string) $module['label']) ?></span>
                            <?php if ($moduleInMaint): ?>
                                <span class="badge text-bg-warning ms-1" style="font-size:0.58rem;">Maint</span>
                            <?php endif; ?>
                            <?= smsIcon('chevron-down', ['class' => 'sidebar-chevron ms-auto', 'aria-hidden' => 'true']) ?>
                        </button>
                        <div class="collapse admin-module-body sidebar-submenu <?= $isModuleActive ? 'show' : '' ?>"
                             id="<?= htmlspecialchars($moduleCollapseId) ?>">
                            <ul class="nav flex-column">
                                <?php if ($showModuleOverview): ?>
                                <li class="nav-item">
                                    <a class="nav-link sidebar-sub overview-link <?= $onModuleOverview ? 'active' : '' ?>"
                                       href="<?= htmlspecialchars($overviewUrl) ?>"
                                       data-title="Overview"
                                       title="Overview">
                                        <?= smsIcon('layout-grid', ['aria-hidden' => 'true']) ?>
                                        <span>Overview</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php if ($showCradWorkspace): ?>
                                <li class="nav-item">
                                    <a class="nav-link sidebar-sub <?= $isCradWorkspaceActive ? 'active' : '' ?>"
                                       href="<?= BASE_URL ?>/modules/crad/pages/research-workspace.php"
                                       data-title="Research Workspace"
                                       title="Research Workspace">
                                        <?= smsIcon('fa-flask', ['aria-hidden' => 'true']) ?>
                                        <span>Research Workspace</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                                <?php
                                $groupedSlugSet = [];
                                if ($showModuleGroups) {
                                    foreach ($moduleGroups as $groupSlugsForSet) {
                                        foreach ((array) $groupSlugsForSet as $groupedSlug) {
                                            $groupedSlugSet[(string) $groupedSlug] = true;
                                        }
                                    }
                                }
                                ?>
                                <?php if (!empty($module['show_ungrouped_pages'])): ?>
                                    <?php foreach ($modulePages as $page): ?>
                                        <?php
                                        $ungroupedSlug = (string) ($page['slug'] ?? '');
                                        if ($ungroupedSlug === '' || isset($groupedSlugSet[$ungroupedSlug])) {
                                            continue;
                                        }
                                        $isPageActive = ($isModuleActive && $activePage === $ungroupedSlug);
                                        $pageHref = BASE_URL . '/modules/' . $moduleFolder . '/pages/' . $ungroupedSlug . '.php';
                                        if ($ungroupedSlug === 'security-settings') {
                                            $pageHref = BASE_URL . '/account/module-security.php?module=' . urlencode((string) $navModuleKey);
                                        }
                                        ?>
                                        <li class="nav-item">
                                            <a class="nav-link sidebar-sub <?= $isPageActive ? 'active' : '' ?>"
                                               href="<?= htmlspecialchars($pageHref) ?>"
                                               data-title="<?= htmlspecialchars((string) $page['title']) ?>"
                                               title="<?= htmlspecialchars((string) $page['title']) ?>">
                                                <?= smsIcon(smsNavPageIcon($ungroupedSlug), ['aria-hidden' => 'true']) ?>
                                                <span><?= htmlspecialchars((string) $page['title']) ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if ($showModuleGroups): ?>
                                    <?php foreach ($moduleGroups as $groupLabel => $groupSlugs): ?>
                                        <?php
                                        $groupCollapseId = $moduleCollapseId . '_grp_' . preg_replace('/[^a-z0-9_]/', '_', strtolower((string) $groupLabel));
                                        $isGroupActive = ($activeGroupLabel === (string) $groupLabel);
                                        $reportSidebarItems = null;
                                        $outputsSidebarItems = null;
                                        if ($navModuleKey === 'crad' && $groupLabel === 'Reports') {
                                            $reportSidebarItems = smsCradReportSidebarItems();
                                        }
                                        if (in_array($navModuleKey, ['crad', 'crad_grant'], true) && $groupLabel === 'Outputs & Records') {
                                            $outputsSidebarItems = [
                                                ['slug' => 'publications-ip', 'label' => 'Publications & IP', 'icon' => 'fa-book-open', 'badge_key' => 'pending-verify'],
                                                ['slug' => 'document-repository', 'label' => 'Document Repository', 'icon' => 'fa-archive', 'badge_key' => 'pending-archive'],
                                            ];
                                        }
                                        $customSidebarItems = $outputsSidebarItems ?? $reportSidebarItems;
                                        ?>
                                        <li class="nav-item admin-subgroup-item">
                                            <button type="button"
                                                    class="nav-link admin-subgroup-toggle <?= $isGroupActive ? 'active' : '' ?>"
                                                    data-admin-subgroup="#<?= htmlspecialchars($groupCollapseId) ?>"
                                                    aria-expanded="<?= $isGroupActive ? 'true' : 'false' ?>"
                                                    aria-controls="<?= htmlspecialchars($groupCollapseId) ?>">
                                                <?= smsIcon(
                                                    (string) $groupLabel === 'Research Clearance'
                                                        ? 'fa-stamp'
                                                        : smsNavPageIcon((string) ($groupSlugs[0] ?? '')),
                                                    ['aria-hidden' => 'true']
                                                ) ?>
                                                <span><?= htmlspecialchars((string) $groupLabel) ?></span>
                                                <?= smsIcon('chevron-down', ['class' => 'sidebar-chevron ms-auto', 'aria-hidden' => 'true']) ?>
                                            </button>
                                            <div class="admin-subgroup-body <?= $isGroupActive ? 'show' : '' ?>"
                                                 id="<?= htmlspecialchars($groupCollapseId) ?>">
                                                <ul class="nav flex-column">
                                                    <?php foreach (($customSidebarItems ?? array_map(static fn(string $slug): array => ['slug' => $slug], $groupSlugs)) as $sidebarItem): ?>
                                                        <?php
                                                        $slug = (string) ($sidebarItem['slug'] ?? '');
                                                        if ($reportSidebarItems !== null) {
                                                            $sidebarPageTitle = (string) ($sidebarItem['label'] ?? 'Report');
                                                            $pageHref = BASE_URL . '/modules/crad/pages/research-analytics-reporting.php?report=' . rawurlencode($slug);
                                                            $pageIcon = (string) ($sidebarItem['icon'] ?? 'fa-chart-bar');
                                                            $isPageActive = $isModuleActive && $activePage === 'research-analytics-reporting' && (string) ($_GET['report'] ?? 'capstone-analytics') === $slug;
                                                        } elseif ($outputsSidebarItems !== null) {
                                                            if (!isset($pageTitles[$slug])) { continue; }
                                                            $isPageActive = ($isModuleActive && $activePage === $slug);
                                                            $pageHref = BASE_URL . '/modules/' . $moduleFolder . '/pages/' . $slug . '.php';
                                                            $sidebarPageTitle = (string) ($sidebarItem['label'] ?? $pageTitles[$slug]);
                                                            $pageIcon = (string) ($sidebarItem['icon'] ?? smsNavPageIcon($slug));
                                                        } else {
                                                            if (!isset($pageTitles[$slug])) { continue; }
                                                            if ($roleKey === 'crad_officer' && in_array($slug, smsCradOfficerHiddenNavSlugs(), true)) { continue; }
                                                            $isPageActive = ($isModuleActive && $activePage === $slug);
                                                            $pageHref = BASE_URL . '/modules/' . $moduleFolder . '/pages/' . $slug . '.php';
                                                            $sidebarPageTitle = $pageTitles[$slug];
                                                            $pageIcon = smsNavPageIcon($slug);
                                                            $defenseHref = smsAdminDefenseSchedulingHref($slug);
                                                            if ($defenseHref !== null) {
                                                                $pageHref = $defenseHref;
                                                            }
                                                            if ($slug === 'security-settings') {
                                                                $pageHref = BASE_URL . '/account/module-security.php?module=' . urlencode((string) $navModuleKey);
                                                            }
                                                        }
                                                        ?>
                                                        <li class="nav-item">
                                                            <a class="nav-link sidebar-sub <?= $isPageActive ? 'active' : '' ?>"
                                                               href="<?= htmlspecialchars($pageHref) ?>"
                                                               data-title="<?= htmlspecialchars($sidebarPageTitle) ?>"
                                                               title="<?= htmlspecialchars($sidebarPageTitle) ?>">
                                                                <?= smsIcon($pageIcon, ['aria-hidden' => 'true']) ?>
                                                                <span><?= htmlspecialchars($sidebarPageTitle) ?></span>
                                                                <?php if ($outputsSidebarItems !== null && !empty($sidebarItem['badge_key'])): ?>
                                                                <span class="sidebar-outputs-badge" data-outputs-badge="<?= htmlspecialchars((string) $sidebarItem['badge_key']) ?>" hidden></span>
                                                                <?php endif; ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($modulePages as $page): ?>
                                        <?php
                                        $isPageActive = ($isModuleActive && $activePage === $page['slug']);
                                        $pageHref = BASE_URL . '/modules/' . $moduleFolder . '/pages/' . $page['slug'] . '.php';
                                        if ($navModuleKey === 'user-management' && $page['slug'] === 'module-security') {
                                            $secFocus = (string) ($_SESSION['um_sec_focus'] ?? '');
                                            if ($secFocus !== '' && ($activePage ?? '') === 'module-security' && empty($_GET['picker'])) {
                                                $pageHref .= '?focus=' . rawurlencode($secFocus);
                                            } else {
                                                $pageHref .= '?picker=1';
                                            }
                                        }
                                        ?>
                                        <li class="nav-item">
                                            <a class="nav-link sidebar-sub <?= $isPageActive ? 'active' : '' ?>"
                                               href="<?= htmlspecialchars($pageHref) ?>"
                                               data-title="<?= htmlspecialchars($page['title']) ?>"
                                               title="<?= htmlspecialchars($page['title']) ?>">
                                                <?= smsIcon(smsNavPageIcon($page['slug']), ['aria-hidden' => 'true']) ?>
                                                <span><?= htmlspecialchars($page['title']) ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php if (smsIsGrantedAdminRole($roleKey)): ?>
                    <?php
                    $announcementsHref = BASE_URL . '/account/announcements.php';
                    $announcementsActive = ($activePage === 'announcements');
                    ?>
                    <li class="nav-item sidebar-home-item">
                        <a class="nav-link sidebar-home-link <?= $announcementsActive ? 'active' : '' ?>"
                           href="<?= htmlspecialchars($announcementsHref) ?>"
                           data-overview-url="<?= htmlspecialchars($announcementsHref) ?>"
                           data-title="Announcements"
                           title="Announcements">
                            <?= smsIcon('bullhorn', ['aria-hidden' => 'true']) ?>
                            <span>Announcements</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($securitySettingsModule !== '' && !$moduleHasSecuritySettingsPage): ?>
                    <?php $secSettingsActive = ($activePage === 'security-settings'); ?>
                    <?php $secSettingsHref = $securitySettingsHrefOverride !== ''
                        ? $securitySettingsHrefOverride
                        : BASE_URL . '/account/module-security.php?module=' . urlencode($securitySettingsModule); ?>
                    <li class="nav-item admin-module-item">
                        <button type="button"
                                class="nav-link sidebar-parent admin-module-toggle <?= $secSettingsActive ? 'active' : '' ?>"
                                data-bs-toggle="collapse"
                                data-bs-target="#navGrp_system"
                                aria-expanded="<?= $secSettingsActive ? 'true' : 'false' ?>"
                                aria-controls="navGrp_system"
                                data-overview-url="<?= htmlspecialchars($secSettingsHref) ?>"
                                data-title="System"
                                title="System">
                            <?= smsIcon('shield', ['aria-hidden' => 'true']) ?>
                            <span>System</span>
                            <?= smsIcon('chevron-down', ['class' => 'sidebar-chevron ms-auto', 'aria-hidden' => 'true']) ?>
                        </button>
                        <div class="collapse admin-module-body sidebar-submenu <?= $secSettingsActive ? 'show' : '' ?>"
                             id="navGrp_system">
                            <ul class="nav flex-column">
                                <li class="nav-item">
                                    <a class="nav-link sidebar-sub <?= $secSettingsActive ? 'active' : '' ?>"
                                       href="<?= htmlspecialchars($secSettingsHref) ?>"
                                       data-title="Security Settings"
                                       title="Security Settings">
                                        <?= smsIcon('shield', ['aria-hidden' => 'true']) ?>
                                        <span>Security Settings</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>
                <?php unset($navModuleKey, $module, $page, $isModuleActive, $overviewUrl, $pageHref, $isPageActive, $secFocus); ?>            <?php endif; ?>
        </ul>
    </nav>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<script>
/* Restore sidebar scroll position immediately to prevent visible jump */
(function () {
    try {
        var sb = document.getElementById('smsSidebar');
        var saved = sessionStorage.getItem('sidebarScrollTop');
        if (sb && saved !== null) {
            sb.scrollTop = parseInt(saved, 10) || 0;
        }
    } catch (e) {}
})();
</script>
