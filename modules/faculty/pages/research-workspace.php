<?php
/**
 * Adviser Research Workspace
 *
 * Central navigation for existing adviser research workflows. Mutations and
 * their authorization remain on their established workflow pages.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';

requireAuth();

if (getCurrentUserRoleKey() !== 'adviser') {
    http_response_code(403);
    exit('Forbidden');
}

$pageTitle = 'Research Workspace';
$activeModule = 'faculty';
$activePage = 'research-workspace';
$pageBannerIcon = 'fa-book-open';
$pageBannerDescription = 'A central place to continue your research assignments, reviews, and monitoring.';
$breadcrumbs = [
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Research Workspace', 'url' => null],
];

$e = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$pageUrl = static fn (string $route): string => BASE_URL . '/modules/faculty/pages/' . rawurlencode($route);
$groupUrl = static function (string $route, string $groupNumber): string {
    return BASE_URL . '/modules/faculty/pages/' . rawurlencode($route) . '?' . http_build_query(['group' => $groupNumber]);
};

// This monitoring helper reads eligible Pre-Oral revision cases and derives
// their current status; it does not create or update workflow records.
$revisionCases = [];
$revisionCasesAvailable = false;
try {
    require_once ROOT_PATH . '/modules/crad/config/config.php';
    require_once ROOT_PATH . '/modules/crad/includes/research-progress-helpers.php';
    $crad = cradDb();
    if ($crad instanceof PDO) {
        $revisionCases = rpGetRevisionMonitoringGroups(
            $crad,
            (int) ($_SESSION['user_id'] ?? 0),
            rpCurrentUserEmail()
        );
        $revisionCasesAvailable = true;
    }
} catch (Throwable $exception) {
    error_log('Research workspace revision cases could not be loaded: ' . $exception->getMessage());
}
$revisionCounts = $revisionCases !== [] && function_exists('rpRevisionMonitoringCounts')
    ? rpRevisionMonitoringCounts($revisionCases)
    : [];

require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>

<div class="glass-dashboard sms-role-workspace sms-adviser-workspace" aria-labelledby="workspace-title">
    <div class="glass-board">
        <header class="glass-panel sms-role-workspace-section mb-4 sms-role-workspace-header" aria-labelledby="workspace-title">
            <div class="glass-panel-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div>
                        <span class="dash-kicker">Adviser workspace</span>
                        <h1 id="workspace-title">Your Research Workspace</h1>
                        <p class="text-muted mb-0">
                            Choose a workflow below. Assignment confirmations, title decisions, progress actions,
                            and feedback are handled by their existing pages, which remain the source of truth.
                        </p>
                    </div>
                    <a class="btn btn-outline-primary flex-shrink-0"
                       href="<?= $e(BASE_URL . '/modules/faculty/index.php') ?>">
                        <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Faculty home
                    </a>
                </div>
            </div>
        </header>

        <nav class="glass-panel sms-role-workspace-section mb-4" aria-label="Research workspace sections">
            <div class="glass-panel-body">
                <h2 class="h5 mb-3">Workspace sections</h2>
                <ul class="nav nav-pills flex-wrap gap-2">
                    <li class="nav-item"><a class="nav-link" href="#assignments">Assignments</a></li>
                    <li class="nav-item"><a class="nav-link" href="#title-review">Title / proposal review</a></li>
                    <li class="nav-item"><a class="nav-link" href="#groups-progress">Groups / progress</a></li>
                    <li class="nav-item"><a class="nav-link" href="#revision-cases">Revision cases</a></li>
                    <li class="nav-item"><a class="nav-link" href="#feedback-history">Feedback / history</a></li>
                </ul>
            </div>
        </nav>

        <div class="d-flex flex-column gap-3">
            <section class="glass-panel sms-role-workspace-section" id="assignments" aria-labelledby="assignments-title">
                <div class="glass-panel-body">
                    <h2 class="h5" id="assignments-title">Assignment confirmation</h2>
                    <p class="text-muted">
                        Review and confirm or decline adviser assignments on the existing confirmation page.
                        That page applies the confirmation rules and displays the current queue.
                    </p>
                    <a class="btn btn-primary" href="<?= $e($pageUrl('assignment-confirmation.php')) ?>">
                        Open assignment confirmations <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </section>

            <section class="glass-panel sms-role-workspace-section" id="title-review" aria-labelledby="title-review-title">
                <div class="glass-panel-body">
                    <h2 class="h5" id="title-review-title">Title / proposal review</h2>
                    <p class="text-muted">
                        Use the live Research Status page to view submitted research and continue any available
                        adviser title-review actions. No queue count is duplicated here.
                    </p>
                    <a class="btn btn-primary" href="<?= $e($pageUrl('approved-research.php')) ?>">
                        Open Research Status <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </section>

            <section class="glass-panel sms-role-workspace-section" id="groups-progress" aria-labelledby="groups-progress-title">
                <div class="glass-panel-body">
                    <h2 class="h5" id="groups-progress-title">Assigned groups / progress</h2>
                    <p class="text-muted">
                        The assigned-group list and progress monitor provide the current adviser-scoped group
                        records and milestone details. Open a group there to see its live status and next available
                        progress action.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="<?= $e($pageUrl('my-research-groups.php')) ?>">
                            Open My Research Groups
                        </a>
                        <a class="btn btn-outline-primary" href="<?= $e($pageUrl('research-progress-monitoring.php')) ?>">
                            Open Progress Monitoring
                        </a>
                        <a class="btn btn-outline-primary" href="<?= $e($pageUrl('milestones-overview.php')) ?>">
                            Milestones Overview
                        </a>
                    </div>
                </div>
            </section>

            <section class="glass-panel sms-role-workspace-section" id="revision-cases" aria-labelledby="revision-cases-title">
                <div class="glass-panel-body">
                    <h2 class="h5" id="revision-cases-title">Revision cases</h2>
                    <p class="text-muted">
                        Pre-Oral cases below come from the existing adviser monitoring query. Its eligibility
                        requires the complete panel consensus; use the monitor for case details and actions.
                        Final Defense revisions are managed in their separate existing workflow.
                    </p>

                    <?php if ($revisionCases !== []): ?>
                        <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Current eligible Pre-Oral revision cases">
                            <span class="badge bg-primary">Active: <?= (int) ($revisionCounts['active'] ?? 0) ?></span>
                            <span class="badge bg-warning text-dark">Submitted: <?= (int) ($revisionCounts['pending'] ?? 0) ?></span>
                            <span class="badge bg-success">Completed: <?= (int) ($revisionCounts['completed'] ?? 0) ?></span>
                        </div>
                        <div class="list-group mb-3">
                            <?php foreach ($revisionCases as $case): ?>
                                <?php
                                $groupNumber = (string) ($case['group_number'] ?? '');
                                $groupLabel = (string) (($case['group_name'] ?? '') ?: $groupNumber);
                                ?>
                                <div class="list-group-item d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
                                    <div>
                                        <strong><?= $e($groupLabel !== '' ? $groupLabel : 'Research group') ?></strong>
                                        <?php if (!empty($case['research_title'])): ?>
                                            <div><?= $e($case['research_title']) ?></div>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            Pre-Oral status: <?= $e($case['revision_status'] ?? 'Not reported') ?>
                                        </small>
                                    </div>
                                    <?php if ($groupNumber !== ''): ?>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="<?= $e($groupUrl('revision-monitoring-view.php', $groupNumber)) ?>">
                                            Open case
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (!$revisionCasesAvailable): ?>
                        <p class="alert alert-warning">
                            Current case data is unavailable here. Open the existing monitor to check its live state.
                        </p>
                    <?php else: ?>
                        <p class="text-muted">
                            No eligible Pre-Oral cases were returned for this adviser by the existing query.
                            The monitor remains the authoritative source for current case availability.
                        </p>
                    <?php endif; ?>

                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="<?= $e($pageUrl('revision-monitoring.php')) ?>">
                            Open Pre-Oral Revision Monitoring
                        </a>
                        <a class="btn btn-outline-primary" href="<?= $e($pageUrl('final-defense-revision-monitoring.php')) ?>">
                            Open Final Defense Revisions
                        </a>
                    </div>
                </div>
            </section>

            <section class="glass-panel sms-role-workspace-section" id="feedback-history" aria-labelledby="feedback-history-title">
                <div class="glass-panel-body">
                    <h2 class="h5" id="feedback-history-title">Feedback / history</h2>
                    <p class="text-muted">
                        Feedback history is tied to an assigned group. Select a group in the existing page, or
                        open feedback directly from that group’s progress view.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="<?= $e($pageUrl('adviser-feedback-history.php')) ?>">
                            Open Feedback History
                        </a>
                        <a class="btn btn-outline-primary" href="<?= $e($pageUrl('submitted-updates.php')) ?>">
                            Submitted Updates
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
