<?php
/**
 * Role-aware entry point for CRAD research workflows.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';

requireAuth();
requireModuleAccess('crad');

$roleKey = getCurrentUserRoleKey();
$workspaceRoles = ['department_head', 'research_coordinator', 'crad_officer'];
if (!userCanAccessModule('crad') || !smsRoleAllowedForModule($workspaceRoles, 'crad')) {
    http_response_code(403);
    exit('Forbidden');
}

$isDepartmentHead = $roleKey === 'department_head' || smsCanManageCoordinatorAssignments($roleKey);
$isCoordinator = $roleKey === 'research_coordinator';
$isCradOfficer = $roleKey === 'crad_officer' || $roleKey === 'crad';
$isModuleAdmin = smsIsGrantedAdminRole($roleKey) && userCanAccessModule('crad');

$pageTitle = 'Research Workspace';
$activeModule = 'crad';
$activePage = 'research-workspace';
$pageBannerIcon = 'fa-diagram-project';
$pageBannerDescription = 'Role-specific entry points for the existing CRAD research workflows.';
$breadcrumbs = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Research Workspace', 'url' => null],
];

require_once ROOT_PATH . '/includes/breadcrumbs.php';

$url = static fn(string $page): string => BASE_URL . '/modules/crad/pages/' . $page . '.php';
$e = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

$departmentHeadTabs = [
    [
        'id' => 'approvals',
        'label' => 'Group approvals',
        'heading' => 'Research Group Approvals',
        'description' => 'Open the live queue of submitted research groups. Department Head decisions remain on the guarded approval page.',
        'links' => [
            [
                'title' => 'Open Research Group Approvals',
                'description' => 'Review pending submissions and record an approval or rejection.',
                'page' => 'research-group-approvals',
            ],
        ],
    ],
    [
        'id' => 'assignments',
        'label' => 'Coordinator & adviser assignments',
        'heading' => 'Research Coordinator Management',
        'description' => 'Continue with groups approved for assignment. The existing management page provides the current assignment queue and assignment controls.',
        'links' => [
            [
                'title' => 'Open Research Coordinator Management',
                'description' => 'Assign a Research Coordinator and Adviser to eligible groups.',
                'page' => 'research-coordinator-management',
            ],
        ],
    ],
];

$coordinatorTabs = [
    [
        'id' => 'assignments',
        'label' => 'My assignments',
        'heading' => 'Assignment Confirmation',
        'description' => 'Review the assignments addressed to you. Confirmation and decline actions are handled on the existing role-guarded screen.',
        'links' => [
            [
                'title' => 'Open Assignment Confirmation',
                'description' => 'Review and confirm or decline your coordinator assignment.',
                'page' => 'assignment-confirmation',
            ],
        ],
    ],
    [
        'id' => 'title-screening',
        'label' => 'Title screening',
        'heading' => 'Approved Research',
        'description' => 'Open the current approved-title queue for coordinator screening and review.',
        'links' => [
            [
                'title' => 'Open Approved Research',
                'description' => 'Screen approved research titles and record coordinator review.',
                'page' => 'approved-research',
            ],
        ],
    ],
    [
        'id' => 'oversight',
        'label' => 'Research oversight',
        'heading' => 'Research Oversight',
        'description' => 'Use the existing reporting and defense scheduling screens for current progress and scheduling work.',
        'links' => [
            [
                'title' => 'Research Analytics & Reporting',
                'description' => 'View research workflow reporting from live records.',
                'page' => 'research-analytics-reporting',
            ],
            [
                'title' => 'Research Defense Scheduling',
                'description' => 'Review and manage research defense schedules.',
                'page' => 'research-defense-scheduling',
            ],
        ],
    ],
    [
        'id' => 'manuscripts',
        'label' => 'Manuscript progress',
        'heading' => 'Manuscript Review & Compliance',
        'description' => 'Continue manuscript evaluation, final approval, and revision-compliance work in their existing guarded pages.',
        'links' => [
            [
                'title' => 'Final Manuscript Review',
                'description' => 'Evaluate submitted final manuscripts.',
                'page' => 'final-manuscript-review',
            ],
            [
                'title' => 'Final Manuscript Approval',
                'description' => 'Approve eligible final manuscripts.',
                'page' => 'final-manuscript-approval',
            ],
            [
                'title' => 'Revision & Compliance',
                'description' => 'Review and update final-defense revision compliance.',
                'page' => 'revision-compliance',
            ],
        ],
    ],
    [
        'id' => 'outputs',
        'label' => 'Research outputs',
        'heading' => 'Repository & Publication',
        'description' => 'Continue approved outputs and publication record management in the existing CRAD tools.',
        'links' => [
            [
                'title' => 'Research Repository',
                'description' => 'Manage approved research outputs in the repository.',
                'page' => 'research-repository',
            ],
            [
                'title' => 'Documentation & Publication Management',
                'description' => 'Manage documentation and publication records.',
                'page' => 'documentation-publication-management',
            ],
        ],
    ],
];

$cradOfficerTabs = [
    [
        'id' => 'proposals',
        'label' => 'Proposal intake',
        'heading' => 'Proposal Registration',
        'description' => 'Review approved proposals awaiting CRAD registration in the existing live title-approval queue.',
        'links' => [
            [
                'title' => 'Open Register Proposal',
                'description' => 'Register eligible proposals and issue their official proposal number.',
                'page' => 'register-proposal',
            ],
        ],
    ],
    [
        'id' => 'registry',
        'label' => 'Group registry',
        'heading' => 'Capstone Group/Student Registry',
        'description' => 'Inspect the read-only, live registry of generated research group numbers and their members.',
        'links' => [
            [
                'title' => 'Open Capstone Group/Student Registry',
                'description' => 'View registered research groups and student membership.',
                'page' => 'capstone-group-student-registry',
            ],
        ],
    ],
    [
        'id' => 'progress',
        'label' => 'Review & progress',
        'heading' => 'Research Review & Progress',
        'description' => 'Open the live oversight queue, defense schedule, manuscript review, approval, and revision-compliance tools.',
        'links' => [
            [
                'title' => 'Research Analytics & Reporting',
                'description' => 'View research workflow reporting from live records.',
                'page' => 'research-analytics-reporting',
            ],
            [
                'title' => 'Research Defense Scheduling',
                'description' => 'Review and manage research defense schedules.',
                'page' => 'research-defense-scheduling',
            ],
            [
                'title' => 'Final Manuscript Review',
                'description' => 'Evaluate submitted final manuscripts.',
                'page' => 'final-manuscript-review',
            ],
            [
                'title' => 'Final Manuscript Approval',
                'description' => 'Approve eligible final manuscripts.',
                'page' => 'final-manuscript-approval',
            ],
            [
                'title' => 'Revision & Compliance',
                'description' => 'Review and update final-defense revision compliance.',
                'page' => 'revision-compliance',
            ],
        ],
    ],
    [
        'id' => 'outputs',
        'label' => 'Repository & publications',
        'heading' => 'Research Outputs',
        'description' => 'Continue managing approved research outputs and publication records.',
        'links' => [
            [
                'title' => 'Research Repository',
                'description' => 'Manage approved research outputs in the repository.',
                'page' => 'research-repository',
            ],
            [
                'title' => 'Documentation & Publication Management',
                'description' => 'Manage documentation and publication records.',
                'page' => 'documentation-publication-management',
            ],
        ],
    ],
];

$renderWorkspaceTabs = static function (string $prefix, array $tabs) use ($url, $e): void {
    ?>
    <div>
        <div class="nav sms-workspace-tabs" role="tablist" aria-label="<?= $e($prefix) ?> workflow sections">
            <?php foreach ($tabs as $index => $tab): ?>
                <?php $tabId = $prefix . '-' . $tab['id']; ?>
                <button
                    class="nav-link<?= $index === 0 ? ' active' : '' ?>"
                    id="<?= $e($tabId) ?>-tab"
                    type="button"
                    role="tab"
                    aria-controls="<?= $e($tabId) ?>-panel"
                    aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
                    tabindex="<?= $index === 0 ? '0' : '-1' ?>"
                    data-workspace-tab
                ><?= $e($tab['label']) ?></button>
            <?php endforeach; ?>
        </div>
        <?php foreach ($tabs as $index => $tab): ?>
            <?php $tabId = $prefix . '-' . $tab['id']; ?>
            <section
                id="<?= $e($tabId) ?>-panel"
                role="tabpanel"
                aria-labelledby="<?= $e($tabId) ?>-tab"
                tabindex="0"
                class="sms-workspace-panel"
                data-workspace-panel
                <?= $index === 0 ? '' : 'hidden' ?>
            >
                <h3><?= $e($tab['heading']) ?></h3>
                <p class="text-muted mb-0"><?= $e($tab['description']) ?></p>
                <div class="sms-workspace-link-list">
                    <?php foreach ($tab['links'] as $link): ?>
                        <a class="sms-workspace-link" href="<?= $e($url($link['page'])) ?>">
                            <span>
                                <strong><?= $e($link['title']) ?></strong>
                                <small><?= $e($link['description']) ?></small>
                            </span>
                            <span class="sms-workspace-link-icon" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
    <?php
};

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<div class="sms-role-workspace sms-crad-workspace">
    <header class="sms-role-workspace-header" aria-labelledby="workspace-heading">
        <div>
            <span class="dash-kicker"><?= $isDepartmentHead ? 'Department research' : ($isCoordinator ? 'Research coordination' : 'CRAD operations') ?></span>
            <h1 id="workspace-heading">Research Workspace</h1>
            <p>
                Signed in as <?= $e(getCurrentUserName()) ?>.
                Continue with the existing role-authorized workflow queues. Records and decisions remain on their established pages.
            </p>
        </div>
        <a class="btn btn-outline-primary flex-shrink-0" href="<?= $e(BASE_URL . '/communication/calendar.php') ?>">
            <i class="fas fa-calendar-alt me-1" aria-hidden="true"></i> Research calendar
        </a>
    </header>

    <?php if ($isDepartmentHead): ?>
        <section class="sms-role-workspace-section" aria-labelledby="department-head-workflow">
                <div class="card-body">
                    <h2 id="department-head-workflow">Department Head</h2>
                    <p>Move from research-group decisions to assignments for eligible groups.</p>
                    <?php $renderWorkspaceTabs('department-head', $departmentHeadTabs); ?>
                </div>
        </section>
    <?php endif; ?>

    <?php if ($isCoordinator): ?>
        <section class="sms-role-workspace-section" aria-labelledby="coordinator-workflow">
                <div class="card-body">
                    <h2 id="coordinator-workflow">Research Coordinator</h2>
                    <p>Work through assignment confirmation, title screening, research oversight, manuscript progress, and outputs.</p>
                    <?php $renderWorkspaceTabs('coordinator', $coordinatorTabs); ?>
                </div>
        </section>
    <?php endif; ?>

    <?php if ($isCradOfficer || $isModuleAdmin): ?>
        <section class="sms-role-workspace-section" aria-labelledby="crad-officer-workflow">
                <div class="card-body">
                    <h2 id="crad-officer-workflow">CRAD Officer</h2>
                    <p>Work from proposal intake and the official registry through review, research progress, and outputs.</p>
                    <?php $renderWorkspaceTabs('crad-officer', $cradOfficerTabs); ?>
                </div>
        </section>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('[role="tablist"]').forEach(function (tabList) {
    var tabs = Array.prototype.slice.call(tabList.querySelectorAll('[data-workspace-tab]'));
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (item) {
                var selected = item === tab;
                item.classList.toggle('active', selected);
                item.setAttribute('aria-selected', selected ? 'true' : 'false');
                item.setAttribute('tabindex', selected ? '0' : '-1');
                var panel = document.getElementById(item.getAttribute('aria-controls'));
                if (panel) {
                    panel.hidden = !selected;
                }
            });
        });
        tab.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                return;
            }
            event.preventDefault();
            var offset = event.key === 'ArrowRight' ? 1 : -1;
            var next = (tabs.indexOf(tab) + offset + tabs.length) % tabs.length;
            tabs[next].focus();
            tabs[next].click();
        });
    });
});
</script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
