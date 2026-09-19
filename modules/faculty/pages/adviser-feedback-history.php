<?php
/**
 * Faculty Module - Adviser Feedback History
 * Timeline of all feedback provided to a research group
 */

$pageTitle = 'Feedback History';
$activeModule = 'faculty';
$activePage = 'adviser-feedback-history';

$pageBannerIcon        = 'fa-comments';
$pageBannerDescription = 'Timeline of all feedback you have provided to a research group.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';

$breadcrumbs = [
    ['label' => 'Faculty',            'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'My Research Groups', 'url' => BASE_URL . '/modules/faculty/pages/my-research-groups.php'],
    ['label' => 'Feedback History',   'url' => null],
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
    rpRenderAdviserGroupSelector($groupContext['groups'], 'Select Research Group', 'Choose which assigned group you want to view feedback for.');
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

$typeFilter      = $_GET['type']         ?? 'all';
$milestoneFilter = isset($_GET['milestone_id']) ? (int) $_GET['milestone_id'] : null;

$whereConditions = ["rpu.research_group_id = ?"];
$params = [$groupId];
if ($adviserUserId > 0) {
    $whereConditions[] = "rpf.adviser_user_id = ?";
    $params[] = $adviserUserId;
}
if ($typeFilter && $typeFilter !== 'all') { $whereConditions[] = "rpf.feedback_type = ?"; $params[] = $typeFilter; }
if ($milestoneFilter) { $whereConditions[] = "rpf.milestone_id = ?"; $params[] = $milestoneFilter; }
$whereClause = implode(' AND ', $whereConditions);

try {
    $feedbackStmt = $crad->prepare("
        SELECT rpf.*, rpu.update_title, rpu.submitted_by_name,
               rm.milestone_name, rm.milestone_order
        FROM research_progress_feedback rpf
        INNER JOIN research_progress_updates rpu ON rpu.id = rpf.progress_update_id
        LEFT  JOIN research_milestones rm ON rm.id = rpf.milestone_id
        WHERE {$whereClause}
        ORDER BY rpf.created_at DESC
    ");
    $feedbackStmt->execute($params);
    $feedbackHistory = $feedbackStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $feedbackHistory = []; }

try {
    if (!empty($plan['id'])) {
        $milestonesStmt = $crad->prepare("
            SELECT id, milestone_name, milestone_order FROM research_milestones
            WHERE research_plan_id = ? ORDER BY milestone_order ASC
        ");
        $milestonesStmt->execute([(int) $plan['id']]);
        $milestones = $milestonesStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $milestones = [];
    }
} catch (PDOException $e) { $milestones = []; }

$stats = ['total' => count($feedbackHistory), 'Comment' => 0, 'Revision Request' => 0, 'approvals' => 0];
foreach ($feedbackHistory as $fb) {
    if ($fb['feedback_type'] === 'Comment')            $stats['Comment']++;
    elseif ($fb['feedback_type'] === 'Revision Request') $stats['Revision Request']++;
    elseif (in_array($fb['feedback_type'], ['Approval','Progress Approved'])) $stats['approvals']++;
}

$typeMeta = [
    'Comment'          => ['color' => '#3b82f6', 'bg' => '#dbeafe', 'icon' => 'comment',      'dot' => '#3b82f6'],
    'Revision Request' => ['color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'redo',          'dot' => '#ef4444'],
    'Approval'         => ['color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'check-circle',  'dot' => '#10b981'],
    'Progress Approved'=> ['color' => '#059669', 'bg' => '#d1fae5', 'icon' => 'check-double',  'dot' => '#059669'],
];
?>

<div class="glass-dashboard" data-live-update-page="feedback-history" data-group-number="<?= htmlspecialchars($groupNumber) ?>">
    <div class="glass-board">

        <!-- ── Page Header ───────────────────────────────── -->
        <!-- ── Group Hero ────────────────────────────────── -->
        <div class="rm-group-hero" style="padding:1.1rem 1.4rem;">
            <div class="rm-group-hero-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge"><?= htmlspecialchars($researchGroup['group_number']) ?></span>
                            <span class="badge"><?= htmlspecialchars($researchGroup['academic_year']) ?></span>
                        </div>
                        <h5 class="mt-2 mb-1" style="font-size:1.05rem;"><?= htmlspecialchars($researchGroup['group_name']) ?></h5>
                        <p style="font-size:0.85rem;"><?= htmlspecialchars($researchGroup['research_title']) ?></p>
                    </div>
                    <!-- Summary stats -->
                    <div class="d-flex gap-3 text-center flex-wrap">
                        <div>
                            <div style="font-size:1.8rem;font-weight:800;color:#fff;"><?= $stats['total'] ?></div>
                            <div style="font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.7);text-transform:uppercase;">Total</div>
                        </div>
                        <div>
                            <div style="font-size:1.8rem;font-weight:800;color:#93c5fd;"><?= $stats['Comment'] ?></div>
                            <div style="font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.7);text-transform:uppercase;">Comments</div>
                        </div>
                        <div>
                            <div style="font-size:1.8rem;font-weight:800;color:#fca5a5;"><?= $stats['Revision Request'] ?></div>
                            <div style="font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.7);text-transform:uppercase;">Revisions</div>
                        </div>
                        <div>
                            <div style="font-size:1.8rem;font-weight:800;color:#86efac;"><?= $stats['approvals'] ?></div>
                            <div style="font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.7);text-transform:uppercase;">Approvals</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Filter Bar ────────────────────────────────── -->
        <div class="rm-filter-bar">
            <form method="GET" action="" class="row g-3 align-items-end">
                <input type="hidden" name="group" value="<?= htmlspecialchars($groupNumber) ?>">
                <div class="col-md-4">
                    <label class="form-label" style="font-weight:700;font-size:0.8rem;color:var(--sms-heading);margin-bottom:0.3rem;">
                        <?= smsIcon('tag', ['class' => 'me-1']) ?>Feedback Type
                    </label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="all"              <?= $typeFilter === 'all'              ? 'selected':'' ?>>All Types</option>
                        <option value="Comment"          <?= $typeFilter === 'Comment'          ? 'selected':'' ?>>Comments</option>
                        <option value="Revision Request" <?= $typeFilter === 'Revision Request' ? 'selected':'' ?>>Revision Requests</option>
                        <option value="Approval"         <?= $typeFilter === 'Approval'         ? 'selected':'' ?>>Approvals</option>
                        <option value="Progress Approved"<?= $typeFilter === 'Progress Approved'? 'selected':'' ?>>Progress Approved</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight:700;font-size:0.8rem;color:var(--sms-heading);margin-bottom:0.3rem;">
                        <?= smsIcon('bookmark', ['class' => 'me-1']) ?>Milestone
                    </label>
                    <select name="milestone_id" class="form-select form-select-sm">
                        <option value="">All Milestones</option>
                        <?php foreach ($milestones as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $milestoneFilter == $m['id'] ? 'selected':'' ?>>
                                #<?= $m['milestone_order'] ?> — <?= htmlspecialchars($m['milestone_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <?= smsIcon('filter', ['class' => 'me-2']) ?>Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- ── Timeline ──────────────────────────────────── -->
        <?php if (!empty($feedbackHistory)): ?>
            <div class="glass-panel">
                <div class="glass-panel-body">
                    <div class="rm-timeline">
                        <?php foreach ($feedbackHistory as $fb):
                            $tc = $typeMeta[$fb['feedback_type']] ?? ['color'=>'#64748b','bg'=>'#f1f5f9','icon'=>'info','dot'=>'#64748b'];
                        ?>
                            <div class="rm-timeline-item">
                                <!-- Dot -->
                                <div class="rm-timeline-dot" style="background:<?= $tc['color'] ?>;">
                                    <?= smsIcon($tc['icon']) ?>
                                </div>
                                <!-- Card -->
                                <div class="rm-timeline-content">
                                    <div class="rm-timeline-card">
                                        <div class="rm-timeline-header">
                                            <div class="rm-timeline-header-left">
                                                <!-- Type badge + milestone -->
                                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                    <span class="rm-feedback-item-type" style="background:<?= $tc['color'] ?>;color:#fff;">
                                                        <?= smsIcon($tc['icon']) ?><?= htmlspecialchars($fb['feedback_type']) ?>
                                                    </span>
                                                    <?php if ($fb['milestone_name']): ?>
                                                        <span class="badge bg-secondary" style="font-size:0.7rem;font-weight:700;">
                                                            <?= smsIcon('bookmark', ['class' => 'me-1']) ?><?= htmlspecialchars($fb['milestone_name']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="rm-timeline-re">Re: <?= htmlspecialchars($fb['update_title']) ?></div>
                                                <div class="rm-timeline-to">
                                                    <?= smsIcon('user', ['class' => 'me-1']) ?>
                                                    To: <strong><?= htmlspecialchars($fb['submitted_by_name']) ?></strong>
                                                </div>
                                            </div>
                                            <div class="rm-timeline-header-right">
                                                <div class="rm-timeline-date"><?= date('M d, Y', strtotime($fb['created_at'])) ?></div>
                                                <div class="rm-timeline-time"><?= date('g:i A', strtotime($fb['created_at'])) ?></div>
                                            </div>
                                        </div>

                                        <!-- Feedback body -->
                                        <div class="rm-timeline-body rm-timeline-body--feedback" style="--feedback-bg:<?= $tc['bg'] ?>;--feedback-color:<?= $tc['color'] ?>;">
                                            <?= nl2br(htmlspecialchars($fb['feedback_text'])) ?>
                                        </div>

                                        <!-- Status change note -->
                                        <?php if (!empty($fb['new_milestone_status'])): ?>
                                            <div class="rm-timeline-status-change mt-2">
                                                <?= smsIcon('arrow-right') ?>
                                                Milestone status changed to:
                                                <strong><?= htmlspecialchars($fb['new_milestone_status']) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="glass-panel">
                <div class="glass-panel-body rm-empty">
                    <div class="rm-empty-icon"><?= smsIcon('comments') ?></div>
                    <h6>No Feedback History</h6>
                    <p>
                        <?php if ($typeFilter !== 'all' || $milestoneFilter): ?>
                            No feedback matches the current filters. Try adjusting them.
                        <?php else: ?>
                            You haven't provided any feedback to this research group yet.
                        <?php endif; ?>
                    </p>
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
