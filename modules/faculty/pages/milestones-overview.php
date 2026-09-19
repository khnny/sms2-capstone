<?php
/**
 * Faculty Module - Milestones Overview
 * Detailed view of all milestones for a specific research group
 */

$pageTitle = 'Milestones Overview';
$activeModule = 'faculty';
$activePage = 'milestones-overview';

$pageBannerIcon        = 'fa-tasks';
$pageBannerDescription = 'Detailed view of all milestones for the selected research group.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';

$breadcrumbs = [
    ['label' => 'Faculty',            'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'My Research Groups', 'url' => BASE_URL . '/modules/faculty/pages/my-research-groups.php'],
    ['label' => 'Milestones Overview','url' => null],
];

require_once __DIR__ . '/../../../includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);

try {
    $crad = cradDb();
    $tablesCheck = $crad->query("SHOW TABLES LIKE 'research_plans'")->fetch();
    if (!$tablesCheck) throw new Exception('Not installed.');
} catch (Throwable $e) {
    echo '<div class="alert alert-warning m-3">' . smsIcon('exclamation-triangle', ['class' => 'me-2']) . '<strong>Module Not Installed</strong></div>';
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
    rpRenderAdviserGroupSelector($groupContext['groups'], 'Select Research Group', 'Choose which assigned group you want to view milestones for.');
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

try {
    if (!empty($plan['id'])) {
        $milestones = rpGetMilestonesWithUpdateStats($crad, (int) $plan['id'], $groupId);
    } else {
        $milestones = rpGetMilestonesForPlan($crad, null, $groupId);
    }
} catch (PDOException $e) { $milestones = []; }

$overallProgress = rpMilestonesOverallProgress($milestones);
$totalMilestones = count($milestones);
$completedCount  = 0;
$inProgressCount = 0;
$pendingTotal    = 0;
foreach ($milestones as $m) {
    if (in_array($m['status'], ['Approved', 'Completed'])) $completedCount++;
    if ($m['status'] === 'In Progress') $inProgressCount++;
    $pendingTotal += (int) $m['pending_count'];
}

$statusMeta = [
    'Not Started'          => ['color' => '#94a3b8', 'bg' => '#f1f5f9', 'icon' => 'circle',                'dot' => '#94a3b8'],
    'In Progress'          => ['color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'spinner fa-spin',       'dot' => '#f59e0b'],
    'Submitted for Review' => ['color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => 'clock',                 'dot' => '#3b82f6'],
    'Revision Requested'   => ['color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'exclamation-triangle',  'dot' => '#ef4444'],
    'Approved'             => ['color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'check-circle',          'dot' => '#10b981'],
    'Completed'            => ['color' => '#059669', 'bg' => '#d1fae5', 'icon' => 'check-double',          'dot' => '#059669'],
];
?>

<div class="glass-dashboard" data-live-update-page="milestones-overview" data-group-number="<?= htmlspecialchars($groupNumber) ?>">
    <div class="glass-board">

        <!-- ── Page Header ───────────────────────────────── -->
        <!-- ── Group Hero ────────────────────────────────── -->
        <div class="rm-group-hero">
            <div class="rm-group-hero-body">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge"><?= htmlspecialchars($researchGroup['group_number']) ?></span>
                    <span class="badge"><?= htmlspecialchars($researchGroup['academic_year']) ?></span>
                </div>
                <h5 class="mt-2 mb-1"><?= htmlspecialchars($researchGroup['group_name']) ?></h5>
                <p><?= htmlspecialchars($researchGroup['research_title']) ?></p>

                <div class="rm-hero-progress">
                    <div class="rm-hero-progress-header">
                        <span class="rm-hero-progress-label">Overall Progress</span>
                        <span class="rm-hero-progress-pct" data-overall-progress-text><?= number_format($overallProgress, 1) ?>%</span>
                    </div>
                    <div class="rm-hero-progress-track">
                        <div class="rm-hero-progress-fill" style="width:<?= $overallProgress ?>%;" data-overall-progress-bar></div>
                    </div>
                </div>

                <div class="rm-group-hero-stats">
                    <div class="rm-hero-stat">
                        <span class="rm-hero-stat-value"><?= $totalMilestones ?></span>
                        <span class="rm-hero-stat-label">Total</span>
                    </div>
                    <div class="rm-hero-stat">
                        <span class="rm-hero-stat-value"><?= $completedCount ?></span>
                        <span class="rm-hero-stat-label">Completed</span>
                    </div>
                    <div class="rm-hero-stat">
                        <span class="rm-hero-stat-value"><?= $inProgressCount ?></span>
                        <span class="rm-hero-stat-label">In Progress</span>
                    </div>
                    <div class="rm-hero-stat">
                        <span class="rm-hero-stat-value"><?= $pendingTotal ?></span>
                        <span class="rm-hero-stat-label">Pending Review</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Milestone Cards ───────────────────────────── -->
        <?php if (!empty($milestones)): ?>
            <div class="row g-4" data-milestones-container>
                <?php foreach ($milestones as $ms):
                    $mp          = (float) ($ms['progress_percentage'] ?? 0);
                    $status      = (string) ($ms['status'] ?? 'Not Started');
                    $pending     = (int) ($ms['pending_count'] ?? 0);
                    $updates     = (int) ($ms['update_count'] ?? 0);
                    $startDate   = $ms['start_date'] ?? null;
                    $targetDate  = $ms['target_date'] ?? null;
                    $completedAt = $ms['completed_at'] ?? null;
                    $lastUpdate  = $ms['last_update_at'] ?? null;
                    $sc      = $statusMeta[$status] ?? $statusMeta['Not Started'];

                    // Overdue check
                    $isOverdue = false;
                    if ($targetDate && !in_array($status, ['Approved','Completed'])) {
                        $isOverdue = strtotime((string) $targetDate) < time();
                    }
                ?>
                    <div class="col-xl-4 col-lg-6" data-milestone-id="<?= htmlspecialchars((string) ($ms['id'] ?? '')) ?>">
                        <div class="glass-panel rm-milestone-card h-100">
                            <div class="glass-panel-body d-flex flex-column h-100">

                                <!-- Header row -->
                                <div class="rm-milestone-header">
                                    <div class="rm-milestone-number"><?= htmlspecialchars((string) ($ms['milestone_order'] ?? '')) ?></div>
                                    <div class="rm-milestone-header-text">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <?php if ($pending > 0): ?>
                                                <span class="badge bg-warning text-dark" style="font-size:0.7rem;font-weight:800;">
                                                    <?= smsIcon('clock', ['class' => 'me-1']) ?><?= $pending ?> Pending
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($isOverdue): ?>
                                                <span class="badge bg-danger" style="font-size:0.7rem;font-weight:800;">
                                                    <?= smsIcon('exclamation-circle', ['class' => 'me-1']) ?>Overdue
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <h6 class="rm-milestone-name"><?= htmlspecialchars((string) ($ms['milestone_name'] ?? '')) ?></h6>
                                        <?php if (!empty($ms['description'])): ?>
                                            <p class="rm-milestone-desc"><?= htmlspecialchars($ms['description']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="rm-milestone-header-pct" style="color:<?= $sc['color'] ?>;" data-milestone-progress-text>
                                        <?= number_format($mp, 0) ?>%
                                    </div>
                                </div>

                                <!-- Progress bar -->
                                <div class="rm-milestone-progress">
                                    <div class="rm-milestone-fill" style="width:<?= $mp ?>%;background:<?= $sc['color'] ?>;"></div>
                                </div>

                                <!-- Status pill -->
                                <div class="mb-3">
                                    <span class="rm-status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;" data-milestone-status>
                                        <?= smsIcon($sc['icon']) ?>
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </div>

                                <!-- Dates grid -->
                                <?php if ($startDate || $targetDate || $completedAt): ?>
                                <div class="rm-milestone-dates">
                                    <?php if ($startDate): ?>
                                        <div>
                                            <div class="rm-milestone-date-item-label"><?= smsIcon('play', ['class' => 'me-1']) ?>Start</div>
                                            <div class="rm-milestone-date-item-value"><?= date('M d, Y', strtotime((string) $startDate)) ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($targetDate): ?>
                                        <div>
                                            <div class="rm-milestone-date-item-label" style="color:<?= $isOverdue ? '#ef4444' : '' ?>">
                                                <?= smsIcon('flag-checkered', ['class' => 'me-1']) ?>Target
                                            </div>
                                            <div class="rm-milestone-date-item-value" style="color:<?= $isOverdue ? '#ef4444' : '' ?>">
                                                <?= date('M d, Y', strtotime((string) $targetDate)) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($completedAt): ?>
                                        <div class="<?= ($startDate || $targetDate) ? '' : '' ?>" style="grid-column:1/-1;">
                                            <div class="rm-milestone-date-item-label" style="color:#10b981;">
                                                <?= smsIcon('check-circle', ['class' => 'me-1']) ?>Completed
                                            </div>
                                            <div class="rm-milestone-date-item-value" style="color:#10b981;">
                                                <?= date('M d, Y', strtotime((string) $completedAt)) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <!-- Activity stats -->
                                <div class="d-flex gap-3 mb-3 px-1" style="font-size:0.8rem;">
                                    <div>
                                        <span style="font-size:1.25rem;font-weight:800;color:var(--sms-heading);"><?= $updates ?></span>
                                        <div style="color:var(--sms-text-muted);font-weight:600;">Updates</div>
                                    </div>
                                    <div>
                                        <span style="font-size:1.25rem;font-weight:800;color:var(--sms-heading);">
                                            <?= $ms['last_update_at'] ? date('M d', strtotime($ms['last_update_at'])) : '—' ?>
                                        </span>
                                        <div style="color:var(--sms-text-muted);font-weight:600;">Last Activity</div>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <?php if (!empty($ms['researcher_notes'])): ?>
                                    <div class="rm-note-block mb-2" style="background:#eff6ff;border-left:3px solid #3b82f6;color:#1e40af;">
                                        <div style="font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;color:#3b82f6;">
                                            <?= smsIcon('user', ['class' => 'me-1']) ?>Researcher Notes
                                        </div>
                                        <?= nl2br(htmlspecialchars($ms['researcher_notes'])) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ms['adviser_remarks'])): ?>
                                    <div class="rm-note-block mb-2" style="background:#f0fdf4;border-left:3px solid #10b981;color:#065f46;">
                                        <div style="font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;color:#10b981;">
                                            <?= smsIcon('user-tie', ['class' => 'me-1']) ?>Your Remarks
                                        </div>
                                        <?= nl2br(htmlspecialchars($ms['adviser_remarks'])) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Panel Remarks — shown when official Pre-Oral result is APPROVED.
                                     Wrapper always rendered so live-polling JS can toggle it. -->
                                <div data-milestone-panel-remarks
                                     <?= empty($ms['panel_remarks']) ? 'style="display:none;"' : '' ?>>
                                    <div class="rm-note-block mb-2" style="background:#ecfdf5;border-left:3px solid #059669;color:#064e3b;">
                                        <div style="font-size:0.68rem;font-weight:800;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;color:#059669;">
                                            <?= smsIcon('users', ['class' => 'me-1']) ?>Panel Remarks
                                        </div>
                                        <div data-milestone-panel-remarks-text>
                                            <?= nl2br(htmlspecialchars((string) ($ms['panel_remarks'] ?? ''))) ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- View Updates button -->
                                <div class="mt-auto pt-2">
                                    <a href="<?= BASE_URL ?>/modules/faculty/pages/submitted-updates.php?group=<?= urlencode($groupNumber) ?>&milestone_id=<?= $ms['id'] ?>"
                                       class="rm-primary-action" style="font-size:0.85rem;">
                                        <?= smsIcon('eye') ?>View Updates
                                        <?php if ($pending > 0): ?>
                                            <span class="badge bg-warning text-dark ms-auto"><?= $pending ?></span>
                                        <?php endif; ?>
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
                    <div class="rm-empty-icon"><?= smsIcon('tasks') ?></div>
                    <h6>No Milestones Found</h6>
                    <p>Milestones have not been initialized for this research group yet.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Live refresh bar -->
        <div class="rm-refresh-bar" id="rmRefreshBar">
            <?= smsIcon('sync-alt', ['class' => 'rm-refresh-icon']) ?>
            <span id="rmRefreshText">Last updated: <?= date('g:i:s A') ?></span>
        </div>

    </div>
</div>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
