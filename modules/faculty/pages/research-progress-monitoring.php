<?php
/**
 * Faculty Module - Research Progress Monitoring
 * Detailed progress monitoring for a specific assigned research group
 */

$pageTitle = 'Research Progress Monitoring';
$activeModule = 'faculty';
$activePage = 'research-progress-monitoring';

$pageBannerIcon        = 'fa-chart-line';
$pageBannerDescription = 'Detailed progress monitoring for your assigned research group.';
$hideModulePageBanner  = true;

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';
require_once __DIR__ . '/../../../modules/crad/includes/final-phase-helpers.php';

$breadcrumbs = [
    ['label' => 'Faculty',                      'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'My Research Groups',           'url' => BASE_URL . '/modules/faculty/pages/my-research-groups.php'],
    ['label' => 'Research Progress Monitoring', 'url' => null],
];

require_once __DIR__ . '/../../../includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);

// Module check
try {
    $crad = cradDb();
    $tablesCheck = $crad->query("SHOW TABLES LIKE 'research_plans'")->fetch();
    if (!$tablesCheck) throw new Exception('Not installed.');
} catch (Throwable $e) {
    echo '<div class="alert alert-warning m-3">' . smsIcon('exclamation-triangle', ['class' => 'me-2']) . '<strong>Module Not Installed</strong><br>The Research Progress module is not yet installed.</div>';
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

$adviserUserId = (int) ($_SESSION['user_id'] ?? 0);
$adviserEmail  = rpCurrentUserEmail();
$groupContext = rpResolveAdviserResearchGroupContext($crad, $adviserUserId, $adviserEmail, $_GET['group'] ?? null);

if ($groupContext['status'] === 'no_groups') {
    rpRenderAdviserNoGroupsState();
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}
if ($groupContext['status'] === 'needs_selection') {
    rpRenderAdviserGroupSelector($groupContext['groups'], 'Select Research Group', 'Choose which assigned group you want to monitor.');
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}
if ($groupContext['status'] !== 'ok' || empty($groupContext['group'])) {
    rpRenderAdviserGroupAccessDenied();
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

$researchGroup = $groupContext['group'];
$groupNumber = (string) $researchGroup['group_number'];
$groupId = (int) $researchGroup['id'];
$plan    = rpGetResearchPlan($crad, $groupId);
$academicPhase = rpGroupAcademicPhase($crad, $groupId);
$recommendationMessage = null;
$recommendationMessageType = 'success';
$finalDefenseRecommendationEligible = fpAreAllMilestonesApprovedForFinalDefense($crad, $groupId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fdr_action'])) {
    if (!csrfVerify()) {
        http_response_code(403);
        $recommendationMessage = 'Security validation failed. Please refresh the page and try again.';
        $recommendationMessageType = 'danger';
    } else {
        $action = (string) $_POST['fdr_action'];
        if ($action === 'recommend') {
            $remarks = trim((string) ($_POST['fdr_remarks'] ?? ''));
            $adviserName = trim((string) ($_SESSION['user_name'] ?? $_SESSION['full_name'] ?? $_SESSION['username'] ?? ''));
            if (!$finalDefenseRecommendationEligible) {
                $recommendationMessage = 'Final Defense recommendation can only be saved after all milestones are approved.';
                $recommendationMessageType = 'warning';
            } else {
                $saved = fpSaveFinalDefenseRecommendation($crad, $groupId, $groupNumber, $adviserUserId, $adviserName, $remarks);
                $recommendationMessage = $saved
                    ? 'Final Defense recommendation saved.'
                    : 'The Final Defense recommendation could not be saved.';
                $recommendationMessageType = $saved ? 'success' : 'danger';
            }
        } elseif ($action === 'revoke') {
            $saved = fpClearFinalDefenseRecommendation($crad, $groupId);
            $recommendationMessage = $saved
                ? 'Final Defense recommendation revoked.'
                : 'The Final Defense recommendation could not be revoked.';
            $recommendationMessageType = $saved ? 'success' : 'danger';
        }
    }
}

finalPhaseEnsureSchema($crad);
$finalDefenseRecommendation = fpGetFinalDefenseRecommendation($crad, $groupId);

// Milestones
try {
    if (!empty($plan['id'])) {
        $milestones = rpGetMilestonesWithUpdateStats($crad, (int) $plan['id'], $groupId);
    } else {
        $milestones = rpGetMilestonesForPlan($crad, null, $groupId);
    }
} catch (PDOException $e) { $milestones = []; }

// Recent updates (last 5)
try {
    $recentUpdatesStmt = $crad->prepare("
        SELECT rpu.*, rm.milestone_name
        FROM research_progress_updates rpu
        LEFT JOIN research_milestones rm ON rm.id = rpu.milestone_id
        WHERE rpu.research_group_id = ?
        ORDER BY rpu.submitted_at DESC
        LIMIT 5
    ");
    $recentUpdatesStmt->execute([$groupId]);
    $recentUpdates = $recentUpdatesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $recentUpdates = []; }

$overallProgress  = rpMilestonesOverallProgress($milestones);
$totalMilestones  = count($milestones);
$doneMilestones   = 0;
$pendingTotal     = 0;
foreach ($milestones as $m) {
    if (in_array($m['status'], ['Approved', 'Completed'])) $doneMilestones++;
    $pendingTotal += (int) $m['pending_count'];
}

$statusColors = [
    'Not Started'          => ['color' => '#94a3b8', 'bg' => '#f1f5f9', 'icon' => 'circle'],
    'In Progress'          => ['color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'spinner'],
    'Submitted for Review' => ['color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => 'clock'],
    'Revision Requested'   => ['color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'exclamation-triangle'],
    'Approved'             => ['color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'check-circle'],
    'Completed'            => ['color' => '#059669', 'bg' => '#d1fae5', 'icon' => 'check-double'],
];

$progressColor = $overallProgress >= 80 ? '#10b981' : ($overallProgress >= 40 ? '#f59e0b' : '#3b82f6');
?>

<div class="glass-dashboard" data-live-update-page="progress-monitoring" data-group-number="<?= htmlspecialchars($groupNumber) ?>">
    <div class="glass-board">

        <!-- ── Page Header ───────────────────────────────── -->
        <div class="rm-page-header">
            <div class="rm-page-header-left">
                <h4><?= smsIcon('chart-line', ['class' => 'me-2', 'style' => 'color:var(--sms-primary);']) ?>Research Progress</h4>
                <p>Detailed monitoring for this research group</p>
            </div>
        </div>

        <!-- ── Group Hero ────────────────────────────────── -->
        <div class="rm-group-hero">
            <div class="rm-group-hero-body">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge"><?= htmlspecialchars($researchGroup['group_number']) ?></span>
                    <span class="badge"><?= htmlspecialchars($researchGroup['academic_year']) ?></span>
                </div>
                <h5 class="mt-2 mb-1"><?= htmlspecialchars($researchGroup['group_name']) ?></h5>
                <p><?= htmlspecialchars($researchGroup['research_title']) ?></p>

                <!-- Hero progress bar -->
                <div class="rm-hero-progress">
                    <div class="rm-hero-progress-header">
                        <span class="rm-hero-progress-label">Overall Progress</span>
                        <span class="rm-hero-progress-pct" data-overall-progress-text><?= number_format($overallProgress, 1) ?>%</span>
                    </div>
                    <div class="rm-hero-progress-track">
                        <div class="rm-hero-progress-fill" style="width:<?= $overallProgress ?>%;" data-overall-progress-bar></div>
                    </div>
                </div>

                <!-- Hero stats -->
                <div class="rm-group-hero-stats">
                    <div class="rm-hero-stat">
                        <span class="rm-hero-stat-value"><?= $totalMilestones ?></span>
                        <span class="rm-hero-stat-label">Total Milestones</span>
                    </div>
                    <div class="rm-hero-stat">
                        <span class="rm-hero-stat-value" data-hero-done-total><?= $doneMilestones ?></span>
                        <span class="rm-hero-stat-label">Completed</span>
                    </div>
                    <div class="rm-hero-stat">
                        <span class="rm-hero-stat-value" data-hero-pending-total><?= $pendingTotal ?></span>
                        <span class="rm-hero-stat-label">Pending Reviews</span>
                    </div>
                    <?php if (!empty($plan['current_stage'])): ?>
                    <div class="rm-hero-stat">
                        <span class="rm-hero-stat-value" style="font-size:1rem;font-weight:700;">
                            <?= htmlspecialchars($plan['current_stage']) ?>
                        </span>
                        <span class="rm-hero-stat-label">Current Stage · <?= htmlspecialchars($academicPhase) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Main Grid ─────────────────────────────────── -->
        <div class="row g-4">

            <!-- Milestone Progress Table -->
            <div class="col-lg-8">
                <div class="glass-panel h-100">
                    <div class="glass-panel-body">
                        <div class="glass-panel-head">
                            <div>
                                <h5 class="glass-panel-title">Milestone Progress</h5>
                                <p class="glass-panel-sub">Stage-by-stage research development</p>
                            </div>
                            <a href="<?= BASE_URL ?>/modules/faculty/pages/milestones-overview.php?group=<?= urlencode($groupNumber) ?>"
                               class="rm-back-btn" style="font-size:0.8rem;">
                                <?= smsIcon('external-link-alt') ?>Full View
                            </a>
                        </div>

                        <?php if (!empty($milestones)): ?>
                        <div class="table-responsive">
                            <table class="table align-middle rm-milestone-table mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Milestone</th>
                                        <th style="min-width:130px;">Progress</th>
                                        <th>Status</th>
                                        <th>Pending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($milestones as $m):
                                        $mp   = (float) $m['progress_percentage'];
                                        $sc   = $statusColors[$m['status']] ?? $statusColors['Not Started'];
                                        $pend = (int) $m['pending_count'];
                                    ?>
                                    <tr data-milestone-id="<?= $m['id'] ?>">
                                        <td>
                                            <span style="display:inline-flex;align-items:center;justify-content:center;
                                                width:26px;height:26px;border-radius:7px;
                                                background:var(--sms-surface-muted);
                                                font-size:0.75rem;font-weight:800;color:var(--sms-text-muted);">
                                                <?= $m['milestone_order'] ?>
                                            </span>
                                        </td>
                                        <td style="font-weight:700;color:var(--sms-heading);max-width:200px;">
                                            <?= htmlspecialchars($m['milestone_name']) ?>
                                            <?php if (!empty($m['target_date'])): ?>
                                                <div style="font-size:0.72rem;color:var(--sms-text-muted);font-weight:600;margin-top:2px;">
                                                    <?= smsIcon('calendar-check', ['class' => 'me-1']) ?><?= date('M d, Y', strtotime($m['target_date'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rm-progress-track flex-grow-1" style="height:8px;max-width:90px;min-width:60px;">
                                                    <div class="rm-progress-fill" style="width:<?= $mp ?>%;background:<?= $sc['color'] ?>;"></div>
                                                </div>
                                                <span style="font-size:0.82rem;font-weight:800;color:<?= $sc['color'] ?>;min-width:38px;" data-milestone-progress-text>
                                                    <?= number_format($mp, 0) ?>%
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="rm-status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;" data-milestone-status>
                                                <?= smsIcon($sc['icon']) ?>
                                                <?= htmlspecialchars($m['status']) ?>
                                            </span>
                                        </td>
                                        <td data-pending-cell>
                                            <?php if ($pend > 0): ?>
                                                <a href="<?= BASE_URL ?>/modules/faculty/pages/submitted-updates.php?group=<?= urlencode($groupNumber) ?>&milestone_id=<?= $m['id'] ?>"
                                                   class="badge bg-warning text-dark text-decoration-none"
                                                   style="font-size:0.75rem;padding:0.3rem 0.6rem;">
                                                    <?= smsIcon('clock', ['class' => 'me-1']) ?><?= $pend ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size:0.85rem;">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="rm-empty" style="padding:2rem 1rem;">
                                <div class="rm-empty-icon"><?= smsIcon('tasks') ?></div>
                                <h6>No Milestones Yet</h6>
                                <p>Milestones have not been initialized for this group.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right column: quick stats + recent activity -->
            <div class="col-lg-4 d-flex flex-column gap-4">

                <!-- Final Defense Recommendation -->
                <div class="glass-panel">
                    <div class="glass-panel-body">
                        <div class="glass-panel-head">
                            <div>
                                <h5 class="glass-panel-title">Final Defense Recommendation</h5>
                                <p class="glass-panel-sub">Record your readiness assessment for this group.</p>
                            </div>
                        </div>

                        <?php if ($recommendationMessage): ?>
                            <div class="alert alert-<?= htmlspecialchars($recommendationMessageType) ?> py-2" role="status">
                                <?= htmlspecialchars($recommendationMessage) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (($finalDefenseRecommendation['status'] ?? '') === 'Recommended'): ?>
                            <div class="alert alert-success py-2 mb-3">
                                <strong><?= smsIcon('check-circle', ['class' => 'me-1']) ?>Recommended</strong>
                                <div class="small mt-1">
                                    <?= htmlspecialchars((string) ($finalDefenseRecommendation['final_defense_recommended_by_name'] ?? '')) ?>
                                    <?php if (!empty($finalDefenseRecommendation['final_defense_recommended_at'])): ?>
                                        <br><?= date('M d, Y g:i A', strtotime($finalDefenseRecommendation['final_defense_recommended_at'])) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (trim((string) ($finalDefenseRecommendation['final_defense_recommendation_remarks'] ?? '')) !== ''): ?>
                                <p class="small text-muted mb-3"><?= nl2br(htmlspecialchars((string) $finalDefenseRecommendation['final_defense_recommendation_remarks'])) ?></p>
                            <?php endif; ?>
                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="fdr_action" value="revoke">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <?= smsIcon('undo', ['class' => 'me-1']) ?>Revoke Recommendation
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="fdr_action" value="recommend">
                                <?php if (!$finalDefenseRecommendationEligible): ?>
                                    <div class="alert alert-warning py-2 mb-3">
                                        All milestones must be approved before recommending this group for Final Defense.
                                    </div>
                                <?php endif; ?>
                                <label class="form-label small fw-bold" for="fdr_remarks">Remarks (optional)</label>
                                <textarea class="form-control form-control-sm mb-3" id="fdr_remarks" name="fdr_remarks" rows="3" maxlength="5000" placeholder="Add readiness remarks for the research group."></textarea>
                                <button type="submit" class="btn btn-primary btn-sm" <?= $finalDefenseRecommendationEligible ? '' : 'disabled' ?>>
                                    <?= smsIcon('flag-checkered', ['class' => 'me-1']) ?>Recommend for Final Defense
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Action Cards -->
                <div class="glass-panel">
                    <div class="glass-panel-body">
                        <h6 class="glass-panel-title mb-3">Quick Actions</h6>
                        <div class="d-flex flex-column gap-2">
                            <a href="<?= BASE_URL ?>/modules/faculty/pages/submitted-updates.php?group=<?= urlencode($groupNumber) ?>"
                               class="rm-primary-action" style="justify-content:flex-start;">
                                <?= smsIcon('inbox') ?>
                                Review Updates
                                <span class="badge bg-warning text-dark ms-auto<?= $pendingTotal > 0 ? '' : ' d-none' ?>" data-review-updates-badge><?= $pendingTotal ?></span>
                            </a>
                            <a href="<?= BASE_URL ?>/modules/faculty/pages/milestones-overview.php?group=<?= urlencode($groupNumber) ?>"
                               class="rm-sec-action" style="justify-content:flex-start;padding:0.6rem 0.85rem;">
                                <?= smsIcon('tasks') ?>Milestone Details
                            </a>
                            <a href="<?= BASE_URL ?>/modules/faculty/pages/adviser-feedback-history.php?group=<?= urlencode($groupNumber) ?>"
                               class="rm-sec-action" style="justify-content:flex-start;padding:0.6rem 0.85rem;">
                                <?= smsIcon('comments') ?>Feedback History
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Updates Feed -->
                <div class="glass-panel flex-grow-1">
                    <div class="glass-panel-body">
                        <div class="glass-panel-head">
                            <div>
                                <h5 class="glass-panel-title">Recent Activity</h5>
                                <p class="glass-panel-sub">Latest submissions</p>
                            </div>
                        </div>

                        <?php if (!empty($recentUpdates)): ?>
                            <div class="d-flex flex-column gap-3" data-recent-updates-container>
                            <?php foreach ($recentUpdates as $u):
                                $uStatus = $u['milestone_status'] ?? 'In Progress';
                                $uSC = $statusColors[$uStatus] ?? $statusColors['In Progress'];
                            ?>
                                <div style="padding-bottom:0.85rem;border-bottom:1px solid var(--sms-border-soft);">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                        <div style="font-weight:700;font-size:0.88rem;color:var(--sms-heading);flex:1;min-width:0;
                                                    display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                            <?= htmlspecialchars($u['update_title']) ?>
                                        </div>
                                        <div style="font-size:1.1rem;font-weight:800;color:<?= $uSC['color'] ?>;flex-shrink:0;">
                                            <?= number_format((float)$u['new_progress'], 0) ?>%
                                        </div>
                                    </div>
                                    <?php if ($u['milestone_name']): ?>
                                        <div style="font-size:0.75rem;color:var(--sms-text-muted);font-weight:600;margin-bottom:0.3rem;">
                                            <?= smsIcon('bookmark', ['class' => 'me-1']) ?><?= htmlspecialchars($u['milestone_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div style="font-size:0.7rem;color:var(--sms-text-muted);">
                                            <?= smsIcon('clock', ['class' => 'me-1']) ?>
                                            <?= date('M d, g:i A', strtotime($u['submitted_at'])) ?>
                                        </div>
                                        <span class="rm-status-pill" style="background:<?= $uSC['bg'] ?>;color:<?= $uSC['color'] ?>;font-size:0.65rem;padding:0.18rem 0.55rem;">
                                            <?= htmlspecialchars($uStatus) ?>
                                        </span>
                                    </div>
                                    <div class="mt-2">
                                        <a href="<?= BASE_URL ?>/modules/faculty/pages/submitted-updates.php?group=<?= urlencode($groupNumber) ?>&update_id=<?= $u['id'] ?>"
                                           style="font-size:0.78rem;font-weight:700;color:var(--sms-primary);text-decoration:none;">
                                            <?= smsIcon('eye', ['class' => 'me-1']) ?>Review →
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                            <div class="mt-3">
                                <a href="<?= BASE_URL ?>/modules/faculty/pages/submitted-updates.php?group=<?= urlencode($groupNumber) ?>"
                                   class="rm-primary-action" style="font-size:0.82rem;">
                                    <?= smsIcon('inbox') ?>View All Updates
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="rm-empty" style="padding:1.5rem 0;">
                                <div class="rm-empty-icon" style="width:48px;height:48px;font-size:1.2rem;"><?= smsIcon('inbox') ?></div>
                                <p style="margin:0;font-size:0.85rem;">No updates submitted yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Live refresh bar -->
        <div class="rm-refresh-bar" id="rmRefreshBar">
            <?= smsIcon('sync-alt', ['class' => 'rm-refresh-icon']) ?>
            <span id="rmRefreshText">Last updated: <?= date('g:i:s A') ?></span>
        </div>

    </div>
</div>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
