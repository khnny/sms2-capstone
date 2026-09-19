<?php
/**
 * Student Portal - Adviser Feedback
 * View all adviser feedback and comments
 */

$pageTitle = 'Adviser Feedback';
$activeModule = 'student_portal';
$activePage = 'adviser-feedback';

$pageBannerIcon        = 'fa-comments';
$pageBannerDescription = 'Review feedback from your research adviser.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';

$breadcrumbs = [
    ['label' => 'Student Portal',    'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
    ['label' => 'My Research',       'url' => BASE_URL . '/modules/student-portal/pages/my-research.php'],
    ['label' => 'Adviser Feedback',  'url' => null],
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

// Get feedback with related update and milestone info
try {
    $feedbackStmt = $crad->prepare("
        SELECT rpf.*, 
               rm.milestone_name,
               rpu.update_title,
               rpu.new_progress,
               rpu.submitted_at as update_submitted_at
        FROM research_progress_feedback rpf
        INNER JOIN research_progress_updates rpu ON rpu.id = rpf.progress_update_id
        LEFT JOIN research_milestones rm ON rm.id = rpf.milestone_id
        WHERE rpu.research_group_id = ?
        ORDER BY rpf.created_at DESC
    ");
    $feedbackStmt->execute([$groupId]);
    $feedbackList = $feedbackStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Feedback query error: ' . $e->getMessage());
    $feedbackList = [];
}

// Group feedback by type for filtering
$feedbackByType = [
    'all' => $feedbackList,
    'Comment' => [],
    'Revision Request' => [],
    'Approval' => [],
    'Progress Approved' => []
];

foreach ($feedbackList as $feedback) {
    $type = $feedback['feedback_type'];
    if (isset($feedbackByType[$type])) {
        $feedbackByType[$type][] = $feedback;
    }
}
?>

<div class="glass-dashboard" data-live-update-page="feedback">
    <div class="glass-board">

        <!-- Filter Tabs -->
        <div class="glass-panel mb-4">
            <div class="glass-panel-body">
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-primary filter-btn active" data-filter="all">
                        All (<?= count($feedbackByType['all']) ?>)
                    </button>
                    <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="Comment">
                        <?= smsIcon('comment', ['class' => 'me-1']) ?>
                        Comments (<?= count($feedbackByType['Comment']) ?>)
                    </button>
                    <button class="btn btn-sm btn-outline-warning filter-btn" data-filter="Revision Request">
                        <?= smsIcon('redo', ['class' => 'me-1']) ?>
                        Revisions (<?= count($feedbackByType['Revision Request']) ?>)
                    </button>
                    <button class="btn btn-sm btn-outline-success filter-btn" data-filter="Progress Approved">
                        <?= smsIcon('check-circle', ['class' => 'me-1']) ?>
                        Approved (<?= count($feedbackByType['Progress Approved']) + count($feedbackByType['Approval']) ?>)
                    </button>
                </div>
            </div>
        </div>

        <!-- Feedback Timeline -->
        <div data-feedback-container>
            <?php if (!empty($feedbackList)): ?>
                <?php foreach ($feedbackList as $feedback): ?>
                    <?php
                    $typeConfig = [
                        'Comment' => ['icon' => 'fa-comment', 'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.1)'],
                        'Revision Request' => ['icon' => 'fa-redo', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.1)'],
                        'Approval' => ['icon' => 'fa-check-circle', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)'],
                        'Progress Approved' => ['icon' => 'fa-check-circle', 'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)']
                    ];
                    $config = $typeConfig[$feedback['feedback_type']] ?? ['icon' => 'fa-comment', 'color' => '#64748b', 'bg' => 'rgba(100,116,139,0.1)'];
                    ?>
                    
                    <div class="glass-panel mb-3 feedback-item" data-feedback-type="<?= htmlspecialchars($feedback['feedback_type']) ?>">
                        <div class="glass-panel-body">
                            <div class="row">
                                <!-- Timeline Icon -->
                                <div class="col-auto">
                                    <div style="width:48px;height:48px;border-radius:12px;background:<?= $config['bg'] ?>;display:flex;align-items:center;justify-content:center;">
                                        <?= smsIcon($config['icon'], ['style' => 'color:' . $config['color'] . ';font-size:1.2rem;']) ?>
                                    </div>
                                </div>
                                
                                <!-- Feedback Content -->
                                <div class="col">
                                    <!-- Header -->
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge" style="background:<?= $config['bg'] ?>;color:<?= $config['color'] ?>;font-weight:700;">
                                                    <?= htmlspecialchars($feedback['feedback_type']) ?>
                                                </span>
                                                <?php if ($feedback['milestone_name']): ?>
                                                    <span class="badge bg-secondary">
                                                        <?= smsIcon('bookmark', ['class' => 'me-1']) ?>
                                                        <?= htmlspecialchars($feedback['milestone_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-weight:700;color:var(--sms-heading);font-size:0.95rem;">
                                                <?= smsIcon('user-tie', ['class' => 'me-2', 'style' => 'color:var(--sms-primary);']) ?>
                                                <?= htmlspecialchars($feedback['adviser_name']) ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div style="font-size:0.75rem;color:var(--sms-text-muted);">
                                                <?= smsIcon('clock', ['class' => 'me-1']) ?>
                                                <?= date('M d, Y', strtotime($feedback['created_at'])) ?>
                                            </div>
                                            <div style="font-size:0.7rem;color:var(--sms-text-muted);">
                                                <?= date('g:i A', strtotime($feedback['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Related Update -->
                                    <?php if ($feedback['update_title']): ?>
                                        <div class="p-2 mb-3" style="border-radius:6px;background:var(--sms-surface-muted);border-left:2px solid var(--sms-border);">
                                            <div style="font-size:0.7rem;font-weight:700;color:var(--sms-text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">
                                                Related to Your Update:
                                            </div>
                                            <div style="font-size:0.85rem;font-weight:700;color:var(--sms-heading);">
                                                <?= htmlspecialchars($feedback['update_title']) ?>
                                            </div>
                                            <div style="font-size:0.75rem;color:var(--sms-text-muted);">
                                                Progress: <?= number_format((float)$feedback['new_progress'], 1) ?>% 
                                                • Submitted: <?= date('M d, Y', strtotime($feedback['update_submitted_at'])) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Feedback Text -->
                                    <div class="student-feedback-message p-3 mb-2">
                                        <div style="font-size:0.95rem;line-height:1.7;white-space:pre-wrap;">
<?= htmlspecialchars($feedback['feedback_text']) ?>
                                        </div>
                                    </div>

                                    <!-- Status Change -->
                                    <?php if ($feedback['new_milestone_status']): ?>
                                        <div class="student-feedback-status alert alert-info mb-0" style="font-size:0.85rem;padding:0.5rem 1rem;">
                                            <?= smsIcon('info-circle', ['class' => 'me-2']) ?>
                                            <strong>Status Updated:</strong> <?= htmlspecialchars($feedback['new_milestone_status']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass-panel">
                    <div class="glass-panel-body text-center py-5">
                        <?= smsIcon('comments', ['style' => 'font-size:3rem;color:var(--sms-border);margin-bottom:1rem;']) ?>
                        <h6 style="font-weight:700;color:var(--sms-heading);margin-bottom:0.5rem;">No Feedback Yet</h6>
                        <p class="text-muted mb-0">Your adviser hasn't provided any feedback yet.<br>Submit progress updates to receive feedback.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Last Refresh Indicator -->
        <div class="text-center mt-4">
            <small class="text-muted" data-last-refresh>
                <?= smsIcon('sync-alt', ['class' => 'me-1']) ?>
                Last updated: <?= date('g:i:s A') ?>
            </small>
        </div>

    </div>
</div>

<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const feedbackItems = document.querySelectorAll('.feedback-item');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active state
            filterBtns.forEach(b => b.classList.remove('active', 'btn-primary'));
            filterBtns.forEach(b => b.classList.add('btn-outline-primary'));
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary', 'active');
            
            // Filter items
            feedbackItems.forEach(item => {
                const itemType = item.getAttribute('data-feedback-type');
                if (filter === 'all' || itemType === filter || 
                    (filter === 'Progress Approved' && (itemType === 'Progress Approved' || itemType === 'Approval'))) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
