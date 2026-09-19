<?php
/**
 * Student Portal - Research Plan
 * Display and manage research plan details
 */

$pageTitle = 'Research Plan';
$activeModule = 'student_portal';
$activePage = 'research-plan';

$pageBannerIcon        = 'fa-project-diagram';
$pageBannerDescription = 'Manage your research plan and milestones.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';

$breadcrumbs = [
    ['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
    ['label' => 'My Research',    'url' => BASE_URL . '/modules/student-portal/pages/my-research.php'],
    ['label' => 'Research Plan',  'url' => null],
];

require_once __DIR__ . '/../../../includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);

// Check if module is properly installed
try {
    $crad = cradDb();
    
    // Verify research_progress tables exist
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

// Get milestones with Chapter 1-3 synced from document submissions
$milestones = rpGetMilestonesForPlan($crad, (int) $plan['id'], $groupId);
$plan = rpApplySyncedPlanProgress($plan, $milestones);
$academicPhase = rpGroupAcademicPhase($crad, $groupId);

$overallProgress = (float) $plan['overall_progress'];
?>

<div class="glass-dashboard">
    <div class="glass-board">
        
        <!-- Plan Details -->
        <div class="glass-panel mb-4">
            <div class="glass-panel-body">
                <div class="glass-panel-head">
                    <div>
                        <h5 class="glass-panel-title">Plan Details</h5>
                        <p class="glass-panel-sub">Research timeline and progress tracking</p>
                    </div>
                    <span class="glass-chip">
                        <?= smsIcon('project-diagram', ['class' => 'me-1']) ?> Active Plan
                    </span>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Research Title</label>
                            <div style="font-weight:700;color:var(--sms-heading);font-size:1.05rem;">
                                <?= htmlspecialchars($plan['research_title']) ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Group Number</label>
                            <div style="font-weight:700;color:var(--sms-heading);">
                                <?= htmlspecialchars($plan['group_number']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Start Date</label>
                            <div style="font-weight:700;color:var(--sms-heading);">
                                <?= smsIcon('calendar-alt', ['class' => 'me-2', 'style' => 'color:var(--sms-primary);']) ?>
                                <?= date('F d, Y', strtotime($plan['start_date'])) ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Target Completion</label>
                            <div style="font-weight:700;color:var(--sms-heading);">
                                <?= smsIcon('flag-checkered', ['class' => 'me-2', 'style' => 'color:var(--sms-primary);']) ?>
                                <?= $plan['target_completion_date'] ? date('F d, Y', strtotime($plan['target_completion_date'])) : 'Not set' ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Current Stage</label>
                            <div style="font-weight:700;color:var(--sms-heading);font-size:1.05rem;">
                                <?= smsIcon('tasks', ['class' => 'me-2', 'style' => 'color:var(--sms-primary);']) ?>
                                <?= htmlspecialchars($plan['current_stage']) ?>
                                <span class="ms-2"><?= htmlspecialchars($academicPhase) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-muted" style="font-size:0.8rem;font-weight:700;">Overall Progress</label>
                        <div class="progress" style="height:28px;border-radius:14px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width:<?= $overallProgress ?>%;background:linear-gradient(90deg, #2563eb, #60a5fa);" 
                                 aria-valuenow="<?= $overallProgress ?>" aria-valuemin="0" aria-valuemax="100">
                                <span style="font-weight:700;font-size:0.9rem;"><?= number_format($overallProgress, 1) ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Milestones Table -->
        <div class="glass-panel mb-4">
            <div class="glass-panel-body">
                <div class="glass-panel-head">
                    <div>
                        <h5 class="glass-panel-title">Research Milestones</h5>
                        <p class="glass-panel-sub">Breakdown of research phases and deliverables</p>
                    </div>
                    <span class="glass-chip">
                        <?= count($milestones) ?> Milestones
                    </span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="font-weight:700;color:var(--sms-heading);font-size:0.85rem;">#</th>
                                <th style="font-weight:700;color:var(--sms-heading);font-size:0.85rem;">Milestone</th>
                                <th style="font-weight:700;color:var(--sms-heading);font-size:0.85rem;">Status</th>
                                <th style="font-weight:700;color:var(--sms-heading);font-size:0.85rem;">Progress</th>
                                <th style="font-weight:700;color:var(--sms-heading);font-size:0.85rem;">Target Date</th>
                                <th style="font-weight:700;color:var(--sms-heading);font-size:0.85rem;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($milestones as $milestone): ?>
                                <?php
                                $progress = (float) $milestone['progress_percentage'];
                                $status = $milestone['status'];
                                $statusColors = [
                                    'Not Started' => '#94a3b8',
                                    'In Progress' => '#f59e0b',
                                    'Submitted for Review' => '#3b82f6',
                                    'Revision Requested' => '#ef4444',
                                    'Approved' => '#10b981',
                                    'Completed' => '#059669'
                                ];
                                $statusColor = $statusColors[$status] ?? '#64748b';
                                ?>
                                <tr>
                                    <td style="font-weight:700;color:var(--sms-heading);">
                                        <?= $milestone['milestone_order'] ?>
                                    </td>
                                    <td style="font-weight:700;color:var(--sms-heading);">
                                        <?= htmlspecialchars($milestone['milestone_name']) ?>
                                    </td>
                                    <td>
                                        <span class="badge" style="background:<?= $statusColor ?>15;color:<?= $statusColor ?>;font-weight:700;">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height:8px;border-radius:4px;">
                                                <div class="progress-bar" style="width:<?= $progress ?>%;background:<?= $statusColor ?>;" 
                                                     role="progressbar" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span style="font-size:0.8rem;font-weight:700;color:var(--sms-heading);min-width:40px;">
                                                <?= number_format($progress, 0) ?>%
                                            </span>
                                        </div>
                                    </td>
                                    <td style="color:var(--sms-text);font-size:0.85rem;">
                                        <?php if (!empty($milestone['target_date'])): ?>
                                            <?= smsIcon('calendar', ['class' => 'me-1']) ?>
                                            <?= date('M d, Y', strtotime($milestone['target_date'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($milestone['researcher_notes'])): ?>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewMilestoneNotes(<?= $milestone['id'] ?>, <?= htmlspecialchars(json_encode($milestone['researcher_notes']), ENT_QUOTES) ?>)">
                                                <?= smsIcon('eye') ?> View
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:0.85rem;">No notes</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="glass-panel">
            <div class="glass-panel-body">
                <div class="glass-panel-head">
                    <div>
                        <h5 class="glass-panel-title">Quick Actions</h5>
                        <p class="glass-panel-sub">Manage your research progress</p>
                    </div>
                </div>
                
                <div class="d-flex gap-3 flex-wrap">
                    <a href="<?= BASE_URL ?>/modules/student-portal/pages/milestones.php" 
                       class="btn btn-primary">
                        <?= smsIcon('tasks', ['class' => 'me-2']) ?>View Detailed Milestones
                    </a>
                    <a href="<?= BASE_URL ?>/modules/student-portal/pages/progress-updates.php" 
                       class="btn btn-success">
                        <?= smsIcon('plus-circle', ['class' => 'me-2']) ?>Submit Progress Update
                    </a>
                    <a href="<?= BASE_URL ?>/modules/student-portal/pages/adviser-feedback.php" 
                       class="btn btn-outline-primary">
                        <?= smsIcon('comments', ['class' => 'me-2']) ?>View Adviser Feedback
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Milestone Notes Modal -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Milestone Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="notesContent" style="white-space:pre-wrap;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewMilestoneNotes(milestoneId, notes) {
    document.getElementById('notesContent').textContent = notes;
    const modal = new bootstrap.Modal(document.getElementById('notesModal'));
    modal.show();
}
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
