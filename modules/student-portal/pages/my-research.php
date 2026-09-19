<?php
/**
 * Student Portal - My Research
 * Overview of student's research group, plan, and progress
 */

$pageTitle   = 'My Research';
$activeModule = 'student_portal';
$activePage   = 'my-research';

$pageBannerIcon        = 'fa-flask';
$pageBannerDescription = 'Research progress and milestone tracking.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';

$breadcrumbs = [
    ['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
    ['label' => 'My Research',    'url' => null],
];

require_once __DIR__ . '/../../../includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';
require_once __DIR__ . '/../../../modules/crad/includes/final-phase-helpers.php';

// Check if module is properly installed
try {
    $crad = cradDb();
    
    // Verify research_progress tables exist
    $tablesCheck = $crad->query("SHOW TABLES LIKE 'research_plans'")->fetch();
    if (!$tablesCheck) {
        throw new Exception('Research Progress module not installed. Please run database installer.');
    }
} catch (Throwable $e) {
    echo '<div class="alert alert-warning">'
        . smsIcon('exclamation-triangle', ['class' => 'me-2'])
        . '<strong>Module Not Installed</strong><br>'
        . 'The Research Progress module database tables are not yet installed.'
        . ' Please contact your system administrator or run the database installer at:'
        . ' <code>/modules/crad/database/install_progress_module.php</code>'
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
finalPhaseEnsureSchema($crad);
$finalDefenseRecommendation = fpGetFinalDefenseRecommendation($crad, $groupId);

// Get milestones with Chapter 1-3 synced from document submissions
$milestones = rpGetMilestonesForPlan($crad, (int) $plan['id'], $groupId);
$plan = rpApplySyncedPlanProgress($plan, $milestones);
$academicPhase = rpGroupAcademicPhase($crad, $groupId);

// Get latest progress update
$latestUpdateStmt = $crad->prepare("
    SELECT * FROM research_progress_updates 
    WHERE research_group_id = ?
    ORDER BY submitted_at DESC
    LIMIT 1
");
$latestUpdateStmt->execute([$groupId]);
$latestUpdate = $latestUpdateStmt->fetch(PDO::FETCH_ASSOC);

// Get latest adviser feedback
$latestFeedbackStmt = $crad->prepare("
    SELECT rpf.*, rm.milestone_name
    FROM research_progress_feedback rpf
    INNER JOIN research_progress_updates rpu ON rpu.id = rpf.progress_update_id
    LEFT JOIN research_milestones rm ON rm.id = rpf.milestone_id
    WHERE rpu.research_group_id = ?
    ORDER BY rpf.created_at DESC
    LIMIT 3
");
$latestFeedbackStmt->execute([$groupId]);
$latestFeedback = $latestFeedbackStmt->fetchAll(PDO::FETCH_ASSOC);

$overallProgress = (float) $plan['overall_progress'];
?>

<div class="glass-dashboard">
    <div class="glass-board">
        
        <!-- Research Information -->
        <div class="glass-panel mb-4">
            <div class="glass-panel-body">
                <div class="glass-panel-head">
                    <div>
                        <h5 class="glass-panel-title">Research Information</h5>
                        <p class="glass-panel-sub">Your research group details</p>
                    </div>
                    <span class="glass-chip">
                        <?= smsIcon('check-circle') ?> Active
                    </span>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Group Number</label>
                            <div style="font-weight:700;color:var(--sms-heading);"><?= htmlspecialchars($researchGroup['group_number']) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Group Name</label>
                            <div style="font-weight:700;color:var(--sms-heading);"><?= htmlspecialchars($researchGroup['group_name']) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Academic Year</label>
                            <div style="font-weight:700;color:var(--sms-heading);"><?= htmlspecialchars($researchGroup['academic_year']) ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Assigned Adviser</label>
                            <div style="font-weight:700;color:var(--sms-heading);">
                                <?= smsIcon('user-tie', ['class' => 'me-2', 'style' => 'color:var(--sms-primary);']) ?>
                                <?= htmlspecialchars($researchGroup['adviser_name'] ?: $researchGroup['adviser']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-0">
                            <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Research Title</label>
                            <div style="font-weight:700;color:var(--sms-heading);font-size:1.05rem;"><?= htmlspecialchars($researchGroup['research_title']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Final Defense Recommendation -->
        <div class="glass-panel mb-4">
            <div class="glass-panel-body">
                <div class="glass-panel-head">
                    <div>
                        <h5 class="glass-panel-title">Final Defense Recommendation</h5>
                        <p class="glass-panel-sub">Your adviser's current readiness assessment</p>
                    </div>
                    <?php if (($finalDefenseRecommendation['status'] ?? '') === 'Recommended'): ?>
                        <span class="glass-chip" style="color:#047857;background:#d1fae5;">
                            <?= smsIcon('check-circle') ?> Recommended
                        </span>
                    <?php else: ?>
                        <span class="glass-chip">
                            <?= smsIcon('clock') ?> Not Yet Recommended
                        </span>
                    <?php endif; ?>
                </div>
                <?php if (($finalDefenseRecommendation['status'] ?? '') === 'Recommended'): ?>
                    <div class="small text-muted">
                        Recommended by <strong><?= htmlspecialchars((string) ($finalDefenseRecommendation['final_defense_recommended_by_name'] ?? '')) ?></strong>
                        <?php if (!empty($finalDefenseRecommendation['final_defense_recommended_at'])): ?>
                            on <?= date('M d, Y g:i A', strtotime($finalDefenseRecommendation['final_defense_recommended_at'])) ?>
                        <?php endif; ?>
                    </div>
                    <?php if (trim((string) ($finalDefenseRecommendation['final_defense_recommendation_remarks'] ?? '')) !== ''): ?>
                        <div class="mt-2" style="white-space:pre-line;color:var(--sms-text);">
                            <?= htmlspecialchars((string) $finalDefenseRecommendation['final_defense_recommendation_remarks']) ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Your adviser has not recommended the group for Final Defense yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Overall Progress -->
        <div class="glass-panel mb-4">
            <div class="glass-panel-body">
                <div class="glass-panel-head">
                    <div>
                        <h5 class="glass-panel-title">Overall Progress</h5>
                        <p class="glass-panel-sub">Calculated from all milestones</p>
                    </div>
                    <span class="glass-chip" style="font-size:1rem;font-weight:800;">
                        <span data-overall-progress-text><?= number_format($overallProgress, 1) ?>%</span>
                    </span>
                </div>
                
                <div class="progress" style="height:24px;border-radius:12px;">
                    <div class="progress-bar" role="progressbar" 
                         style="width:<?= $overallProgress ?>%;background:linear-gradient(90deg, #2563eb, #60a5fa);" 
                         aria-valuenow="<?= $overallProgress ?>" aria-valuemin="0" aria-valuemax="100"
                         data-overall-progress-bar>
                        <span style="font-weight:700;font-size:0.85rem;"><?= number_format($overallProgress, 1) ?>%</span>
                    </div>
                </div>
                
                <div class="mt-3 text-muted" style="font-size:0.85rem;">
                    <?= smsIcon('info-circle', ['class' => 'me-1']) ?>
                    Current Stage: <strong><?= htmlspecialchars($plan['current_stage']) ?></strong>
                    <span class="ms-2">Academic Phase: <strong><?= htmlspecialchars($academicPhase) ?></strong></span>
                </div>
            </div>
        </div>

        <!-- Milestones Overview -->
        <div class="glass-panel mb-4">
            <div class="glass-panel-body">
                <div class="glass-panel-head">
                    <div>
                        <h5 class="glass-panel-title">Milestones</h5>
                        <p class="glass-panel-sub">Progress breakdown by milestone</p>
                    </div>
                    <a href="<?= BASE_URL ?>/modules/student-portal/pages/milestones.php" class="btn btn-sm btn-outline-primary">
                        View All <?= smsIcon('arrow-right', ['class' => 'ms-1']) ?>
                    </a>
                </div>
                
                <div class="row g-3" data-milestones-container>
                    <?php foreach ($milestones as $milestone): ?>
                        <?php
                        $progress = (float) $milestone['progress_percentage'];
                        $status = $milestone['status'];
                        $statusIcons = [
                            'Not Started' => ['icon' => 'fa-circle', 'color' => '#94a3b8'],
                            'In Progress' => ['icon' => 'fa-clock', 'color' => '#f59e0b'],
                            'Submitted for Review' => ['icon' => 'fa-paper-plane', 'color' => '#3b82f6'],
                            'Revision Requested' => ['icon' => 'fa-redo', 'color' => '#ef4444'],
                            'Approved' => ['icon' => 'fa-check-circle', 'color' => '#10b981'],
                            'Completed' => ['icon' => 'fa-check-double', 'color' => '#059669']
                        ];
                        $statusInfo = $statusIcons[$status] ?? ['icon' => 'fa-circle', 'color' => '#64748b'];
                        ?>
                        <div class="col-md-6" data-milestone-id="<?= htmlspecialchars((string) $milestone['id']) ?>">
                            <div class="p-3" style="border-radius:12px;border:1px solid var(--sms-border-soft);background:var(--sms-surface-muted);">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div style="font-weight:700;color:var(--sms-heading);font-size:0.95rem;">
                                        <?= htmlspecialchars($milestone['milestone_name']) ?>
                                    </div>
                                    <?= smsIcon($statusInfo['icon'], ['style' => 'color:' . $statusInfo['color'] . ';font-size:1.1rem;', 'title' => $status]) ?>
                                </div>
                                <div class="progress mb-2" style="height:8px;border-radius:4px;">
                                    <div class="progress-bar" style="width:<?= $progress ?>%;background:<?= $statusInfo['color'] ?>;" 
                                         role="progressbar" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-size:0.75rem;color:var(--sms-text-muted);" data-milestone-status><?= htmlspecialchars($status) ?></span>
                                    <span style="font-size:0.8rem;font-weight:700;color:var(--sms-heading);" data-milestone-progress-text><?= number_format($progress, 0) ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="glass-panel h-100">
                    <div class="glass-panel-body">
                        <div class="glass-panel-head">
                            <div>
                                <h5 class="glass-panel-title">Latest Progress Update</h5>
                                <p class="glass-panel-sub">Most recent submission</p>
                            </div>
                        </div>
                        
                        <?php if ($latestUpdate): ?>
                            <div class="mb-2">
                                <span class="badge" style="background:rgba(37,99,235,0.15);color:#2563eb;font-weight:700;">
                                    <?= htmlspecialchars($latestUpdate['milestone_status']) ?>
                                </span>
                            </div>
                            <h6 style="font-weight:700;color:var(--sms-heading);margin-bottom:0.5rem;">
                                <?= htmlspecialchars($latestUpdate['update_title']) ?>
                            </h6>
                            <p class="text-muted mb-2" style="font-size:0.85rem;">
                                Progress: <?= number_format((float)$latestUpdate['previous_progress'], 1) ?>% → 
                                <strong><?= number_format((float)$latestUpdate['new_progress'], 1) ?>%</strong>
                            </p>
                            <p class="text-muted mb-0" style="font-size:0.75rem;">
                                <?= smsIcon('clock', ['class' => 'me-1']) ?>
                                <?= date('M d, Y g:i A', strtotime($latestUpdate['submitted_at'])) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted mb-0" style="font-size:0.9rem;">
                                <?= smsIcon('info-circle', ['class' => 'me-2']) ?>No progress updates yet
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="glass-panel h-100">
                    <div class="glass-panel-body">
                        <div class="glass-panel-head">
                            <div>
                                <h5 class="glass-panel-title">Latest Adviser Feedback</h5>
                                <p class="glass-panel-sub">Recent comments and reviews</p>
                            </div>
                        </div>
                        
                        <?php if (!empty($latestFeedback)): ?>
                            <?php foreach ($latestFeedback as $feedback): ?>
                                <div class="mb-3 pb-3" style="border-bottom:1px solid var(--sms-border-soft);">
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="badge me-2" style="background:<?= 
                                            $feedback['feedback_type'] === 'Revision Request' ? 'rgba(239,68,68,0.15)' :
                                            ($feedback['feedback_type'] === 'Progress Approved' ? 'rgba(16,185,129,0.15)' : 'rgba(59,130,246,0.15)')
                                        ?>;color:<?= 
                                            $feedback['feedback_type'] === 'Revision Request' ? '#ef4444' :
                                            ($feedback['feedback_type'] === 'Progress Approved' ? '#10b981' : '#3b82f6')
                                        ?>;font-weight:700;font-size:0.7rem;">
                                            <?= htmlspecialchars($feedback['feedback_type']) ?>
                                        </span>
                                        <?php if (!empty($feedback['milestone_name'])): ?>
                                            <span style="font-size:0.75rem;color:var(--sms-text-muted);">
                                                <?= htmlspecialchars($feedback['milestone_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-1" style="font-size:0.85rem;color:var(--sms-text);">
                                        <?= nl2br(htmlspecialchars(mb_substr($feedback['feedback_text'], 0, 100))) ?>
                                        <?php if (mb_strlen($feedback['feedback_text']) > 100): ?>...<?php endif; ?>
                                    </p>
                                    <p class="mb-0 text-muted" style="font-size:0.7rem;">
                                        <?= smsIcon('clock', ['class' => 'me-1']) ?>
                                        <?= date('M d, Y', strtotime($feedback['created_at'])) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                            <a href="<?= BASE_URL ?>/modules/student-portal/pages/adviser-feedback.php" 
                               class="btn btn-sm btn-link p-0">
                                View All Feedback <?= smsIcon('arrow-right', ['class' => 'ms-1']) ?>
                            </a>
                        <?php else: ?>
                            <p class="text-muted mb-0" style="font-size:0.9rem;">
                                <?= smsIcon('info-circle', ['class' => 'me-2']) ?>No feedback yet
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
