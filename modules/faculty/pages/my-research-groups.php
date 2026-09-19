<?php
/**
 * Faculty Module - My Research Groups
 * Display all research groups assigned to the logged-in adviser
 */

$pageTitle = 'My Research Groups';
$activeModule = 'faculty';
$activePage = 'my-research-groups';

$pageBannerIcon        = 'fa-users';
$pageBannerDescription = 'Monitor and review progress of your assigned research groups.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';

$breadcrumbs = [
    ['label' => 'Faculty',            'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Research Monitoring','url' => null],
    ['label' => 'My Research Groups', 'url' => null],
];

require_once __DIR__ . '/../../../includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);

// Check if module is properly installed
try {
    $crad = cradDb();
    $tablesCheck = $crad->query("SHOW TABLES LIKE 'research_plans'")->fetch();
    if (!$tablesCheck) {
        throw new Exception('Research Progress module not installed.');
    }
} catch (Throwable $e) {
    echo '<div class="alert alert-warning m-3">'
        . smsIcon('exclamation-triangle', ['class' => 'me-2'])
        . '<strong>Module Not Installed</strong><br>'
        . 'The Research Progress module database tables are not yet installed.'
        . '</div>';
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

$adviserUserId = (int) ($_SESSION['user_id'] ?? 0);
$adviserEmail  = rpCurrentUserEmail();

try {
    $assignedGroups = rpGetAssignedResearchGroupsForAdviser($crad, $adviserUserId, $adviserEmail);
} catch (PDOException $e) {
    error_log('Groups query error: ' . $e->getMessage());
    $assignedGroups = [];
}

$totalPending = array_sum(array_column($assignedGroups, 'pending_reviews'));
?>

<div class="glass-dashboard" data-live-update-page="adviser-groups">
    <div class="glass-board">

        <!-- ── Live / Pending Bar ────────────────────────── -->
        <div class="d-flex align-items-center gap-2 justify-content-end mb-3">
            <div class="rm-live-badge">
                <span class="rm-live-dot"></span>Live
            </div>
            <?php if ($totalPending > 0): ?>
                <span class="badge bg-warning text-dark" style="font-size:0.82rem;padding:0.45rem 0.85rem;border-radius:999px;font-weight:800;">
                    <?= smsIcon('clock', ['class' => 'me-1']) ?><?= $totalPending ?> Pending
                </span>
            <?php endif; ?>
        </div>

        <!-- ── Summary Chips ─────────────────────────────── -->
        <?php if (!empty($assignedGroups)): ?>
        <div class="rm-stats-row">
            <div class="rm-stat-chip">
                <div class="rm-stat-chip-icon" style="background:rgba(37,99,235,0.12);color:#2563eb;">
                    <?= smsIcon('layer-group') ?>
                </div>
                <div class="rm-stat-chip-value" data-live-group-count><?= count($assignedGroups) ?></div>
                <div class="rm-stat-chip-label">Groups</div>
            </div>
            <div class="rm-stat-chip">
                <div class="rm-stat-chip-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;">
                    <?= smsIcon('clock') ?>
                </div>
                <div class="rm-stat-chip-value" style="color:#f59e0b;" data-live-pending-count><?= $totalPending ?></div>
                <div class="rm-stat-chip-label">Pending Reviews</div>
            </div>
            <div class="rm-stat-chip">
                <div class="rm-stat-chip-icon" style="background:rgba(16,185,129,0.12);color:#10b981;">
                    <?= smsIcon('check-circle') ?>
                </div>
                <?php
                $avgProg = count($assignedGroups)
                    ? round(array_sum(array_column($assignedGroups, 'overall_progress')) / count($assignedGroups), 1)
                    : 0;
                ?>
                <div class="rm-stat-chip-value" style="color:#10b981;" data-live-avg-progress><?= $avgProg ?>%</div>
                <div class="rm-stat-chip-label">Avg Progress</div>
            </div>
            <div class="rm-stat-chip">
                <div class="rm-stat-chip-icon" style="background:rgba(99,102,241,0.12);color:#6366f1;">
                    <?= smsIcon('tasks') ?>
                </div>
                <?php
                $totalMiles = array_sum(array_column($assignedGroups, 'total_milestones'));
                $doneMiles  = array_sum(array_column($assignedGroups, 'done_milestones'));
                ?>
                <div class="rm-stat-chip-value" style="color:#6366f1;"><?= $doneMiles ?>/<?= $totalMiles ?></div>
                <div class="rm-stat-chip-label">Milestones Done</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Group Cards ───────────────────────────────── -->
        <?php if (!empty($assignedGroups)): ?>
            <div class="row g-4" data-groups-container>
                <?php foreach ($assignedGroups as $group):
                    $progress       = (float) ($group['overall_progress'] ?? 0);
                    $hasPlan        = !empty($group['plan_id']);
                    $academicPhase  = $hasPlan ? rpGroupAcademicPhase($crad, (int) $group['id']) : '';
                    $pendingReviews = (int)   $group['pending_reviews'];
                    $progressColor  = $progress >= 80 ? '#10b981' : ($progress >= 40 ? '#f59e0b' : '#3b82f6');
                ?>
                    <div class="col-xl-4 col-lg-6" data-group-id="<?= $group['id'] ?>" data-group-number="<?= htmlspecialchars($group['group_number']) ?>">
                        <div class="glass-panel rm-group-card">
                            <div class="glass-panel-body d-flex flex-column h-100">

                                <!-- Badge row -->
                                <div class="rm-group-badge-row">
                                    <span class="badge bg-primary" style="font-size:0.82rem;font-weight:800;padding:0.35rem 0.8rem;">
                                        <?= htmlspecialchars($group['group_number']) ?>
                                    </span>
                                    <span class="badge bg-secondary" style="font-size:0.78rem;font-weight:700;">
                                        <?= htmlspecialchars($group['academic_year']) ?>
                                    </span>
                                    <?php if ($pendingReviews > 0): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:0.78rem;font-weight:800;">
                                            <?= smsIcon('clock', ['class' => 'me-1']) ?><?= $pendingReviews ?> to review
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Title & research title -->
                                <h6 class="rm-group-title mt-2"><?= htmlspecialchars($group['group_name']) ?></h6>
                                <p class="rm-group-subtitle"><?= htmlspecialchars($group['research_title']) ?></p>

                                <!-- Meta -->
                                <div class="rm-group-meta">
                                    <?= smsIcon('calendar-alt') ?>
                                    <?= htmlspecialchars($group['academic_year']) ?>
                                    <?php if ($hasPlan && $group['current_stage']): ?>
                                        &nbsp;·&nbsp;<?= smsIcon('map-marker-alt') ?>
                                        <?= htmlspecialchars($group['current_stage']) ?>
                                        &nbsp;·&nbsp;<?= htmlspecialchars($academicPhase) ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Progress -->
                                <div class="rm-group-progress-section">
                                    <?php if ($hasPlan): ?>
                                        <div class="rm-progress-header">
                                            <span class="rm-progress-label">Overall Progress</span>
                                            <span class="rm-progress-pct" style="color:<?= $progressColor ?>;" data-group-progress-text>
                                                <?= number_format($progress, 1) ?>%
                                            </span>
                                        </div>
                                        <div class="rm-progress-track">
                                            <div class="rm-progress-fill"
                                                 style="width:<?= $progress ?>%;background:<?= $progressColor ?>;"
                                                 data-group-progress-bar></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="rm-progress-header">
                                            <span class="rm-progress-label">Overall Progress</span>
                                            <span class="rm-progress-pct" style="color:<?= $progressColor ?>;" data-group-progress-text>
                                                0.0%
                                            </span>
                                        </div>
                                        <div class="rm-progress-track">
                                            <div class="rm-progress-fill"
                                                 style="width:0%;background:<?= $progressColor ?>;"
                                                 data-group-progress-bar></div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php $milestones = $group['milestones'] ?? []; ?>
                                <div class="mt-3" data-group-milestones>
                                    <?php foreach ($milestones as $milestone): ?>
                                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2"
                                             style="font-size:0.78rem;color:var(--sms-text);"
                                             data-milestone-id="<?= htmlspecialchars((string) ($milestone['id'] ?? '')) ?>">
                                            <span style="font-weight:700;color:var(--sms-heading);">
                                                <?= htmlspecialchars((string) $milestone['milestone_name']) ?>
                                            </span>
                                            <span class="text-nowrap" style="color:var(--sms-text-muted);" data-milestone-status>
                                                <?= htmlspecialchars((string) $milestone['status']) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Actions -->
                                <div class="rm-group-actions">
                                    <a href="<?= BASE_URL ?>/modules/faculty/pages/research-progress-monitoring.php?group=<?= urlencode($group['group_number']) ?>"
                                       class="rm-primary-action">
                                        <?= smsIcon('chart-line') ?>View Progress
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="glass-panel">
                <div class="glass-panel-body rm-empty">
                    <div class="rm-empty-icon"><?= smsIcon('users') ?></div>
                    <h6>No Research Groups Assigned</h6>
                    <p>You don't have any research groups assigned yet.<br>Please contact the Research Coordinator.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Live refresh indicator -->
        <div class="rm-refresh-bar" id="rmRefreshBar">
            <?= smsIcon('sync-alt', ['class' => 'rm-refresh-icon']) ?>
            <span id="rmRefreshText">Last updated: <?= date('g:i:s A') ?></span>
        </div>

    </div>
</div>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
