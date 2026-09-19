<?php
/**
 * Student Portal - Milestones
 * Detailed milestone management and progress tracking
 */

$pageTitle = 'Milestones';
$activeModule = 'student_portal';
$activePage = 'milestones';

$pageBannerIcon        = 'fa-tasks';
$pageBannerDescription = 'Track your research development milestones.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';

$breadcrumbs = [
    ['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
    ['label' => 'My Research',    'url' => BASE_URL . '/modules/student-portal/pages/my-research.php'],
    ['label' => 'Milestones',     'url' => null],
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
    echo '<div class="alert alert-warning">'
        . smsIcon('exclamation-triangle', ['class' => 'me-2'])
        . '<strong>Module Not Installed</strong><br>'
        . 'The Research Progress module database tables are not yet installed.'
        . ' Please contact your system administrator.'
        . '</div>';
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

// Get student's research group — only if it is in the Capstone Group/Student Registry
$studentId     = trim((string) ($_SESSION['student_id'] ?? ''));
$studentUserId = (int) ($_SESSION['user_id'] ?? 0);

$researchGroup = rpGetRegisteredResearchGroup($crad, $studentId, $studentUserId);

if (!$researchGroup) {
    echo '<div class="alert alert-info">'
        . smsIcon('info-circle', ['class' => 'me-2'])
        . '<strong>Research Development is not yet available.</strong><br>'
        . 'Your research group must be officially registered in the Capstone Group/Student Registry before you can access this section.'
        . ' Please ensure your title approval is fully signed and your adviser and coordinator assignments are in place.'
        . '</div>';
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

$groupId = (int) $researchGroup['id'];

// Get or create research plan (idempotent)
$plan = rpGetOrCreateResearchPlan($crad, $groupId);

// Get milestones with latest update info
try {
    $milestones = rpGetMilestonesForPlan($crad, (int) $plan['id'], $groupId);
    
    // Get latest update for each milestone separately
    foreach ($milestones as &$milestone) {
        // Initialize latest update keys to null
        $milestone['latest_update_id'] = null;
        $milestone['latest_update_title'] = null;
        $milestone['latest_update_date'] = null;
        $milestone['latest_update_by'] = null;
        
        $updateStmt = $crad->prepare("
            SELECT id, update_title, submitted_at, submitted_by_name
            FROM research_progress_updates 
            WHERE milestone_id = ? 
            ORDER BY submitted_at DESC 
            LIMIT 1
        ");
        $updateStmt->execute([$milestone['id']]);
        $latestUpdate = $updateStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($latestUpdate) {
            $milestone['latest_update_id'] = $latestUpdate['id'];
            $milestone['latest_update_title'] = $latestUpdate['update_title'];
            $milestone['latest_update_date'] = $latestUpdate['submitted_at'];
            $milestone['latest_update_by'] = $latestUpdate['submitted_by_name'];
        }
    }
    unset($milestone);
} catch (PDOException $e) {
    error_log('Milestones query error: ' . $e->getMessage());
    $milestones = [];
}

$plan = rpApplySyncedPlanProgress($plan, $milestones);
$overallProgress = (float) $plan['overall_progress'];
?>

<div class="glass-dashboard" data-live-update-page="milestones">
    <div class="glass-board">

        <!-- Action Row -->
        <div class="d-flex justify-content-end gap-2 mb-3">
            <a href="<?= BASE_URL ?>/modules/student-portal/pages/progress-updates.php" class="btn btn-primary">
                <?= smsIcon('plus-circle', ['class' => 'me-2']) ?>Submit Progress Update
            </a>
        </div>

        <!-- Overall Progress Summary -->
        <div class="glass-panel mb-4">
            <div class="glass-panel-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 style="font-weight:700;color:var(--sms-heading);margin-bottom:0.5rem;">
                            <?= htmlspecialchars($researchGroup['research_title']) ?>
                        </h6>
                        <p class="text-muted mb-0" style="font-size:0.85rem;">
                            <?= smsIcon('users', ['class' => 'me-2']) ?><?= htmlspecialchars($researchGroup['group_number']) ?> - <?= htmlspecialchars($researchGroup['group_name']) ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div style="font-size:2rem;font-weight:800;color:var(--sms-primary);" data-overall-progress-text>
                            <?= number_format($overallProgress, 1) ?>%
                        </div>
                        <div class="text-muted" style="font-size:0.85rem;">Overall Progress</div>
                    </div>
                </div>
                <div class="progress mt-3" style="height:20px;border-radius:10px;">
                    <div class="progress-bar" role="progressbar" 
                         style="width:<?= $overallProgress ?>%;background:linear-gradient(90deg, #2563eb, #60a5fa);" 
                         aria-valuenow="<?= $overallProgress ?>" aria-valuemin="0" aria-valuemax="100"
                         data-overall-progress-bar>
                    </div>
                </div>
            </div>
        </div>

        <!-- Milestones Cards -->
        <div class="row g-4" data-milestones-container>
            <?php foreach ($milestones as $milestone): ?>
                <?php
                $progress = (float) $milestone['progress_percentage'];
                $status = $milestone['status'];
                $statusConfig = [
                    'Not Started' => ['icon' => 'fa-circle', 'color' => '#94a3b8', 'bg' => 'rgba(148,163,184,0.1)'],
                    'In Progress' => ['icon' => 'fa-clock', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.1)'],
                    'Submitted for Review' => ['icon' => 'fa-paper-plane', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.1)'],
                    'Revision Requested' => ['icon' => 'fa-redo', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.1)'],
                    'Approved' => ['icon' => 'fa-check-circle', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)'],
                    'Completed' => ['icon' => 'fa-check-double', 'color' => '#059669', 'bg' => 'rgba(5,150,105,0.1)']
                ];
                $config = $statusConfig[$status] ?? ['icon' => 'fa-circle', 'color' => '#64748b', 'bg' => 'rgba(100,116,139,0.1)'];
                ?>
                
                <div class="col-md-6" data-milestone-id="<?= $milestone['id'] ?>">
                    <div class="glass-panel h-100">
                        <div class="glass-panel-body">
                            <!-- Milestone Header -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div style="flex:1;">
                                    <div class="d-flex align-items-center mb-2">
                                        <div style="width:32px;height:32px;border-radius:8px;background:<?= $config['bg'] ?>;display:flex;align-items:center;justify-content:center;margin-right:12px;">
                                            <?= smsIcon($config['icon'], ['style' => 'color:' . $config['color'] . ';font-size:1rem;']) ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0" style="font-weight:800;color:var(--sms-heading);">
                                                <?= htmlspecialchars($milestone['milestone_name']) ?>
                                            </h6>
                                        </div>
                                    </div>
                                    <?php if (!empty($milestone['description'])): ?>
                                        <p class="text-muted mb-0" style="font-size:0.8rem;line-height:1.4;">
                                            <?= htmlspecialchars($milestone['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span style="font-size:0.75rem;font-weight:700;color:var(--sms-text-muted);text-transform:uppercase;letter-spacing:0.5px;">Progress</span>
                                    <span style="font-size:0.9rem;font-weight:800;color:var(--sms-heading);" data-milestone-progress-text>
                                        <?= number_format($progress, 1) ?>%
                                    </span>
                                </div>
                                <div class="progress" style="height:12px;border-radius:6px;background:rgba(0,0,0,0.05);">
                                    <div class="progress-bar" 
                                         style="width:<?= $progress ?>%;background:<?= $config['color'] ?>;border-radius:6px;" 
                                         role="progressbar" 
                                         aria-valuenow="<?= $progress ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"
                                         data-milestone-progress-bar>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="mb-3">
                                <span class="badge" 
                                      style="background:<?= $config['bg'] ?>;color:<?= $config['color'] ?>;font-weight:700;font-size:0.75rem;padding:6px 12px;border-radius:6px;"
                                      data-milestone-status>
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </div>

                            <!-- Target Date -->
                            <?php if ($milestone['target_date']): ?>
                                <div class="mb-3" style="font-size:0.85rem;">
                                    <span class="text-muted">
                                        <?= smsIcon('calendar', ['class' => 'me-2']) ?>Target: 
                                    </span>
                                    <strong style="color:var(--sms-heading);">
                                        <?= date('M d, Y', strtotime($milestone['target_date'])) ?>
                                    </strong>
                                </div>
                            <?php endif; ?>

                            <!-- Latest Update -->
                            <?php if (!empty($milestone['latest_update_id'])): ?>
                                <div class="p-3 mb-3" style="border-radius:8px;background:var(--sms-surface-muted);border-left:3px solid <?= $config['color'] ?>;">
                                    <div style="font-size:0.75rem;font-weight:700;color:var(--sms-text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                                        Latest Update
                                    </div>
                                    <div style="font-weight:700;color:var(--sms-heading);font-size:0.9rem;margin-bottom:4px;">
                                        <?= htmlspecialchars($milestone['latest_update_title'] ?? '') ?>
                                    </div>
                                    <div style="font-size:0.75rem;color:var(--sms-text-muted);">
                                        <?= smsIcon('clock', ['class' => 'me-1']) ?>
                                        <?= date('M d, Y g:i A', strtotime($milestone['latest_update_date'])) ?>
                                        by <?= htmlspecialchars($milestone['latest_update_by'] ?? '') ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Adviser Remarks -->
                            <?php if (!empty($milestone['adviser_remarks'])): ?>
                                <div class="p-3 mb-3" style="border-radius:8px;background:rgba(59,130,246,0.05);border-left:3px solid #3b82f6;">
                                    <div style="font-size:0.75rem;font-weight:700;color:#3b82f6;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                                        <?= smsIcon('user-tie', ['class' => 'me-1']) ?> Adviser Remarks
                                    </div>
                                    <div style="font-size:0.85rem;color:var(--sms-text);line-height:1.5;">
                                        <?= nl2br(htmlspecialchars($milestone['adviser_remarks'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Panel Remarks — shown only when the official Pre-Oral
                                 panel result is APPROVED for this research group.
                                 The data-milestone-panel-remarks wrapper is always
                                 rendered so the live-polling JS can show/hide it. -->
                            <div data-milestone-panel-remarks
                                 <?= empty($milestone['panel_remarks']) ? 'style="display:none;"' : '' ?>>
                                <div class="p-3 mb-3" style="border-radius:8px;background:rgba(16,185,129,0.05);border-left:3px solid #10b981;">
                                    <div style="font-size:0.75rem;font-weight:700;color:#10b981;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                                        <?= smsIcon('users', ['class' => 'me-1']) ?> Panel Remarks
                                    </div>
                                    <div style="font-size:0.85rem;color:var(--sms-text);line-height:1.5;" data-milestone-panel-remarks-text>
                                        <?= nl2br(htmlspecialchars((string) ($milestone['panel_remarks'] ?? ''))) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Researcher Notes -->
                            <?php if (!empty($milestone['researcher_notes'])): ?>
                                <div class="p-3 mb-3" style="border-radius:8px;background:rgba(148,163,184,0.05);border-left:3px solid #94a3b8;">
                                    <div style="font-size:0.75rem;font-weight:700;color:var(--sms-text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                                        <?= smsIcon('sticky-note', ['class' => 'me-1']) ?> My Notes
                                    </div>
                                    <div style="font-size:0.85rem;color:var(--sms-text);line-height:1.5;">
                                        <?= nl2br(htmlspecialchars($milestone['researcher_notes'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Action Button -->
                            <div class="d-grid">
                                <a href="<?= BASE_URL ?>/modules/student-portal/pages/progress-updates.php?milestone_id=<?= $milestone['id'] ?>" 
                                   class="btn btn-primary">
                                    <?= smsIcon('arrow-up', ['class' => 'me-2']) ?>Submit Progress Update
                                </a>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($milestones)): ?>
            <div class="glass-panel">
                <div class="glass-panel-body text-center py-5">
                    <?= smsIcon('tasks', ['style' => 'font-size:3rem;color:var(--sms-border);margin-bottom:1rem;']) ?>
                    <p class="text-muted mb-0">No milestones found. Please contact your administrator.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Last Refresh Indicator -->
        <div class="text-center mt-4">
            <small class="text-muted" data-last-refresh>
                <?= smsIcon('sync-alt', ['class' => 'me-1']) ?>
                Last updated: <?= date('g:i:s A') ?>
            </small>
        </div>

    </div>
</div>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
