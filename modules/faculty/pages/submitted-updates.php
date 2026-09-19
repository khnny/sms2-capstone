<?php
/**
 * Faculty Module - Submitted Updates (Review Queue)
 * CRITICAL PAGE: Review student progress updates with Comment/Revision/Approve actions
 */

$pageTitle = 'Submitted Progress Updates';
$activeModule = 'faculty';
$activePage = 'submitted-updates';

$pageBannerIcon        = 'fa-inbox';
$pageBannerDescription = 'Review and provide feedback on student progress submissions.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';
require_once __DIR__ . '/../../../modules/crad/includes/ai-document-analysis.php';

$breadcrumbs = [
    ['label' => 'Faculty',                   'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'My Research Groups',        'url' => BASE_URL . '/modules/faculty/pages/my-research-groups.php'],
    ['label' => 'Submitted Progress Updates','url' => null],
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
$adviserName   = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''));
$groupContext = rpResolveAdviserResearchGroupContext($crad, $adviserUserId, $adviserEmail, $_GET['group'] ?? null);

if ($groupContext['status'] === 'no_groups') {
    rpRenderAdviserNoGroupsState();
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}
if ($groupContext['status'] === 'needs_selection') {
    rpRenderAdviserGroupSelector($groupContext['groups'], 'Select Research Group', 'Choose which assigned group you want to review updates for.');
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

$milestoneFilter = isset($_GET['milestone_id']) ? (int) $_GET['milestone_id'] : null;
$updateIdFilter  = isset($_GET['update_id'])    ? (int) $_GET['update_id']    : null;
$statusFilter    = $_GET['status'] ?? 'all';

$plan = rpGetResearchPlan($crad, $groupId);
rpEnsureProgressAttachmentSchema($crad);
rpEnsureAiAnalysisSchema($crad);

$whereConditions = ["rpu.research_group_id = ?"];
$params = [$groupId];
if ($milestoneFilter) { $whereConditions[] = "rpu.milestone_id = ?"; $params[] = $milestoneFilter; }
if ($updateIdFilter)  { $whereConditions[] = "rpu.id = ?";           $params[] = $updateIdFilter;  }
if ($statusFilter && $statusFilter !== 'all') { $whereConditions[] = "rpu.milestone_status = ?"; $params[] = $statusFilter; }
$whereClause = implode(' AND ', $whereConditions);

try {
    $updatesStmt = $crad->prepare("
        SELECT rpu.*,
               rm.milestone_name, rm.milestone_order, rm.status AS milestone_current_status,
               rpa.id AS attachment_id, rpa.file_name AS attachment_name,
               rpai.id AS ai_analysis_id, rpai.verdict AS ai_verdict, rpai.grammar_quality AS ai_grammar_quality,
               rpai.summary AS ai_summary, rpai.notes_json AS ai_notes_json, rpai.created_at AS ai_analyzed_at,
               (SELECT COUNT(*) FROM research_progress_feedback rpf WHERE rpf.progress_update_id = rpu.id) AS feedback_count
        FROM research_progress_updates rpu
        LEFT JOIN research_milestones rm ON rm.id = rpu.milestone_id
        LEFT JOIN research_progress_attachments rpa ON rpa.id = (
            SELECT rpa2.id
            FROM research_progress_attachments rpa2
            WHERE rpa2.progress_update_id = rpu.id
            ORDER BY rpa2.id DESC
            LIMIT 1
        )
        LEFT JOIN research_progress_ai_analyses rpai ON rpai.id = (
            SELECT rpai2.id
            FROM research_progress_ai_analyses rpai2
            WHERE rpai2.progress_update_id = rpu.id
            ORDER BY rpai2.id DESC
            LIMIT 1
        )
        WHERE {$whereClause}
        ORDER BY rpu.submitted_at DESC
    ");
    $updatesStmt->execute($params);
    $progressUpdates = $updatesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $progressUpdates = []; }

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

$pendingCount  = 0;
$approvedCount = 0;
$revisionCount = 0;
foreach ($progressUpdates as $u) {
    if ($u['milestone_status'] === 'Submitted for Review') $pendingCount++;
    if (in_array($u['milestone_status'], ['Approved', 'Completed'], true)) $approvedCount++;
    if ($u['milestone_status'] === 'Revision Requested') $revisionCount++;
}

$statusMeta = [
    'In Progress'          => ['color' => '#f59e0b', 'bg' => '#fef3c7', 'accent' => '#f59e0b'],
    'Submitted for Review' => ['color' => '#3b82f6', 'bg' => '#dbeafe', 'accent' => '#3b82f6'],
    'Revision Requested'   => ['color' => '#ef4444', 'bg' => '#fee2e2', 'accent' => '#ef4444'],
    'Approved'             => ['color' => '#10b981', 'bg' => '#d1fae5', 'accent' => '#10b981'],
    'Completed'            => ['color' => '#059669', 'bg' => '#d1fae5', 'accent' => '#059669'],
];
?>

<style>
.rm-modal .modal-dialog {
    width: min(560px, calc(100vw - 2rem));
    max-width: 560px;
    margin-left: auto;
    margin-right: auto;
}
.rm-modal .modal-content {
    border: 0;
    border-radius: 12px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
    overflow: hidden;
}
.rm-modal .modal-header {
    align-items: center;
    padding: 1rem 1.15rem;
}
.rm-modal .modal-title {
    font-size: 1rem;
    font-weight: 850;
}
.rm-modal .modal-body {
    padding: 1.15rem;
}
.rm-modal .alert {
    align-items: center;
    border-radius: 8px;
    display: block;
    line-height: 1.45;
}
.rm-modal .alert i { margin-right: 0.45rem; }
.rm-modal .alert strong { white-space: nowrap; }
.rm-modal textarea.form-control {
    min-height: 120px;
    resize: vertical;
}
.rm-modal .rm-modal-actions {
    display: flex;
    gap: 0.6rem;
    justify-content: flex-end;
}
.rm-modal .rm-modal-actions .btn {
    border-radius: 8px;
    font-weight: 800;
    min-height: 40px;
    padding-left: 1rem;
    padding-right: 1rem;
}
@media (max-width: 575.98px) {
    .rm-modal .modal-dialog {
        width: calc(100vw - 1.25rem);
    }
    .rm-modal .rm-modal-actions {
        flex-direction: column;
    }
    .rm-modal .rm-modal-actions .btn {
        width: 100%;
    }
}
</style>

<div class="glass-dashboard" data-live-update-page="submitted-updates" data-group-number="<?= htmlspecialchars($groupNumber) ?>">
    <div class="glass-board">

        <!-- ── Page Header ───────────────────────────────── -->
        <!-- ── Group Info + Stat Chips ───────────────────── -->
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
                    <div class="d-flex gap-3 text-center flex-wrap">
                        <div>
                            <div style="font-size:1.8rem;font-weight:800;color:#fff;"><?= count($progressUpdates) ?></div>
                            <div style="font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.7);text-transform:uppercase;">Total</div>
                        </div>
                        <div>
                            <div style="font-size:1.8rem;font-weight:800;color:#fbbf24;"><?= $pendingCount ?></div>
                            <div style="font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.7);text-transform:uppercase;">Pending</div>
                        </div>
                        <div>
                            <div style="font-size:1.8rem;font-weight:800;color:#86efac;"><?= $approvedCount ?></div>
                            <div style="font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.7);text-transform:uppercase;">Approved</div>
                        </div>
                        <div>
                            <div style="font-size:1.8rem;font-weight:800;color:#fca5a5;"><?= $revisionCount ?></div>
                            <div style="font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.7);text-transform:uppercase;">Revisions</div>
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
                        <?= smsIcon('bookmark', ['class' => 'me-1']) ?>Milestone
                    </label>
                    <select name="milestone_id" class="form-select form-select-sm">
                        <option value="">All Milestones</option>
                        <?php foreach ($milestones as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $milestoneFilter == $m['id'] ? 'selected' : '' ?>>
                                #<?= $m['milestone_order'] ?> — <?= htmlspecialchars($m['milestone_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight:700;font-size:0.8rem;color:var(--sms-heading);margin-bottom:0.3rem;">
                        <?= smsIcon('filter', ['class' => 'me-1']) ?>Status
                    </label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all"                    <?= $statusFilter === 'all'                    ? 'selected' : '' ?>>All Statuses</option>
                        <option value="Submitted for Review"   <?= $statusFilter === 'Submitted for Review'   ? 'selected' : '' ?>>Pending Review</option>
                        <option value="In Progress"            <?= $statusFilter === 'In Progress'            ? 'selected' : '' ?>>In Progress</option>
                        <option value="Revision Requested"     <?= $statusFilter === 'Revision Requested'     ? 'selected' : '' ?>>Revision Requested</option>
                        <option value="Approved"               <?= $statusFilter === 'Approved'               ? 'selected' : '' ?>>Approved</option>
                        <option value="Completed"              <?= $statusFilter === 'Completed'              ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <?= smsIcon('filter', ['class' => 'me-2']) ?>Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- ── Update Cards ──────────────────────────────── -->
        <?php if (!empty($progressUpdates)): ?>
            <div class="d-flex flex-column gap-4" data-updates-container>
                <?php foreach ($progressUpdates as $update):
                    $updateId      = (int) $update['id'];
                    $progressDelta = (float)$update['new_progress'] - (float)$update['previous_progress'];
                    $feedbackCount = (int) $update['feedback_count'];
                    $sc = $statusMeta[$update['milestone_status']] ?? ['color'=>'#64748b','bg'=>'#f1f5f9','accent'=>'#64748b'];
                    $aiNotes = [];
                    if (!empty($update['ai_notes_json'])) {
                        $decodedNotes = json_decode((string) $update['ai_notes_json'], true);
                        $aiNotes = is_array($decodedNotes) ? $decodedNotes : [];
                    }
                    $hasAiAnalysis = !empty($update['ai_analysis_id']);
                    $aiVerdict = (string) ($update['ai_verdict'] ?? '');
                    $needsAiBeforeDecision = ($update['milestone_status'] === 'Submitted for Review') && !empty($update['attachment_id']);
                    $aiRevisionText = $hasAiAnalysis
                        ? rpFormatAiNotesForRevision([
                            'milestone_name' => (string) ($update['milestone_name'] ?? ''),
                            'verdict' => $aiVerdict,
                            'summary' => (string) ($update['ai_summary'] ?? ''),
                            'notes' => $aiNotes,
                        ])
                        : '';
                ?>
                    <div class="glass-panel rm-update-card" style="--rm-accent:<?= $sc['accent'] ?>;" data-update-id="<?= $updateId ?>">
                        <div class="glass-panel-body">

                            <!-- Update header -->
                            <div class="rm-update-header">
                                <div class="rm-update-meta">
                                    <!-- Status + milestone badges -->
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                        <span class="rm-status-pill" data-status="<?= htmlspecialchars($update['milestone_status']) ?>" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
                                            <?= htmlspecialchars($update['milestone_status']) ?>
                                        </span>
                                        <?php if ($update['milestone_name']): ?>
                                            <span class="badge bg-secondary" style="font-size:0.72rem;font-weight:700;">
                                                <?= smsIcon('bookmark', ['class' => 'me-1']) ?><?= htmlspecialchars($update['milestone_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($feedbackCount > 0): ?>
                                            <span class="badge" style="background:rgba(99,102,241,0.12);color:#6366f1;font-size:0.72rem;font-weight:700;">
                                                <?= smsIcon('comments', ['class' => 'me-1']) ?><?= $feedbackCount ?> feedback
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="rm-update-title"><?= htmlspecialchars($update['update_title']) ?></h6>
                                    <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:0.8rem;color:var(--sms-text-muted);">
                                        <span><?= smsIcon('user', ['class' => 'me-1']) ?><strong><?= htmlspecialchars($update['submitted_by_name']) ?></strong></span>
                                        <span><?= smsIcon('clock', ['class' => 'me-1']) ?><?= date('M d, Y g:i A', strtotime($update['submitted_at'])) ?></span>
                                    </div>
                                </div>
                                <!-- Progress value -->
                                <div class="rm-update-pct-block">
                                    <div class="rm-update-pct-value"><?= number_format((float)$update['new_progress'], 0) ?>%</div>
                                    <div class="rm-update-pct-delta" style="color:<?= $progressDelta >= 0 ? '#10b981' : '#ef4444' ?>;">
                                        <?= $progressDelta >= 0 ? '+' : '' ?><?= number_format($progressDelta, 1) ?>%
                                    </div>
                                </div>
                            </div>

                            <!-- Content sections -->
                            <?php if (!empty($update['accomplishments'])): ?>
                                <div class="rm-section-block" style="background:#f0fdf4;border-left:3px solid #10b981;">
                                    <div class="rm-section-block-label" style="color:#059669;">
                                        <?= smsIcon('check-circle') ?>Accomplishments
                                    </div>
                                    <div class="rm-section-block-body" style="color:#065f46;">
                                        <?= nl2br(htmlspecialchars($update['accomplishments'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($update['problems_blockers'])): ?>
                                <div class="rm-section-block" style="background:#fef9c3;border-left:3px solid #f59e0b;">
                                    <div class="rm-section-block-label" style="color:#92400e;">
                                        <?= smsIcon('exclamation-triangle') ?>Problems / Blockers
                                    </div>
                                    <div class="rm-section-block-body" style="color:#78350f;">
                                        <?= nl2br(htmlspecialchars($update['problems_blockers'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($update['next_planned_activity'])): ?>
                                <div class="rm-section-block" style="background:#eff6ff;border-left:3px solid #3b82f6;">
                                    <div class="rm-section-block-label" style="color:#1e40af;">
                                        <?= smsIcon('arrow-right') ?>Next Planned Activity
                                    </div>
                                    <div class="rm-section-block-body" style="color:#1e40af;">
                                        <?= nl2br(htmlspecialchars($update['next_planned_activity'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="rm-section-block" style="background:#f8fafc;border-left:3px solid #64748b;">
                                <div class="rm-section-block-label" style="color:#334155;">
                                    <?= smsIcon('paperclip') ?>Attached Document
                                </div>
                                <div class="rm-section-block-body" style="color:#334155;">
                                    <?php if (!empty($update['attachment_id'])): ?>
                                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                            <span><?= htmlspecialchars((string) $update['attachment_name']) ?></span>
                                            <span class="d-flex gap-2 flex-wrap">
                                                <a class="btn btn-sm btn-outline-primary" target="_blank"
                                                   href="<?= htmlspecialchars(rpProgressAttachmentUrl((int) $update['attachment_id'])) ?>">
                                                    <?= smsIcon('eye', ['class' => 'me-1']) ?>View
                                                </a>
                                                <a class="btn btn-sm btn-outline-secondary"
                                                   href="<?= htmlspecialchars(rpProgressAttachmentUrl((int) $update['attachment_id'], true)) ?>">
                                                    <?= smsIcon('download', ['class' => 'me-1']) ?>Download
                                                </a>
                                                <button type="button"
                                                        class="btn btn-sm rm-ai-generate-btn"
                                                        data-ai-generate
                                                        data-update-id="<?= $updateId ?>">
                                                    <?= smsIcon('robot', ['class' => 'me-1']) ?>Generate to AI
                                                </button>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No document attached</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="rm-ai-panel<?= $hasAiAnalysis ? '' : ' d-none' ?>"
                                 data-ai-panel
                                 data-update-id="<?= $updateId ?>"
                                 data-verdict="<?= htmlspecialchars($aiVerdict) ?>"
                                 data-revision-text="<?= htmlspecialchars($aiRevisionText) ?>">
                                <div class="rm-ai-panel-head">
                                    <div>
                                        <div class="rm-ai-panel-title"><?= smsIcon('robot') ?>AI Grammar Review</div>
                                        <div class="rm-ai-panel-sub">Review these notes before you Approve or Request Revision.</div>
                                    </div>
                                    <span class="rm-ai-verdict" data-ai-verdict-pill>
                                        <?= $hasAiAnalysis ? htmlspecialchars($aiVerdict === 'acceptable' ? 'Acceptable grammar' : 'Needs revision') : '' ?>
                                    </span>
                                </div>
                                <div class="rm-ai-summary" data-ai-summary><?= $hasAiAnalysis ? nl2br(htmlspecialchars((string) $update['ai_summary'])) : '' ?></div>
                                <ul class="rm-ai-notes" data-ai-notes>
                                    <?php foreach ($aiNotes as $note): ?>
                                        <li>
                                            <strong><?= htmlspecialchars((string) ($note['issue'] ?? '')) ?></strong>
                                            <?php if (!empty($note['suggestion'])): ?>
                                                <div><?= htmlspecialchars((string) $note['suggestion']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($note['example'])): ?>
                                                <div class="rm-ai-example">“<?= htmlspecialchars((string) $note['example']) ?>”</div>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if ($needsAiBeforeDecision): ?>
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-ai-use-notes data-update-id="<?= $updateId ?>">
                                        <?= smsIcon('redo', ['class' => 'me-1']) ?>Use notes in Request Revision
                                    </button>
                                <?php endif; ?>
                            </div>

                            <!-- Action buttons -->
                            <div class="rm-action-row" data-action-controls>
                                <?php if ($needsAiBeforeDecision && !$hasAiAnalysis): ?>
                                    <div class="rm-ai-gate-note" data-ai-gate-note>
                                        <?= smsIcon('info-circle') ?>Run <strong>Generate to AI</strong> first. The AI will check the student’s grammar before you approve or request revision.
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="rm-btn rm-btn-comment"
                                        data-bs-toggle="modal" data-bs-target="#feedbackModal<?= $updateId ?>">
                                    <?= smsIcon('comment') ?>Comment
                                </button>
                                <button type="button" class="rm-btn rm-btn-revision"
                                        data-bs-toggle="modal" data-bs-target="#revisionModal<?= $updateId ?>"
                                        data-decision-btn="revision"
                                        <?= ($needsAiBeforeDecision && !$hasAiAnalysis) ? 'disabled' : '' ?>>
                                    <?= smsIcon('redo') ?>Request Revision
                                </button>
                                <button type="button" class="rm-btn rm-btn-approve"
                                        data-bs-toggle="modal" data-bs-target="#approveModal<?= $updateId ?>"
                                        data-decision-btn="approve"
                                        <?= ($needsAiBeforeDecision && !$hasAiAnalysis) ? 'disabled' : '' ?>>
                                    <?= smsIcon('check-circle') ?>Approve
                                </button>
                                <?php if ($feedbackCount > 0): ?>
                                    <button type="button" class="rm-btn rm-btn-history"
                                            data-bs-toggle="collapse" data-bs-target="#feedbackThread<?= $updateId ?>">
                                        <?= smsIcon('history') ?>History (<?= $feedbackCount ?>)
                                    </button>
                                <?php endif; ?>
                            </div>

                            <!-- Feedback thread (collapsible) -->
                            <?php if ($feedbackCount > 0):
                                try {
                                    $fbStmt = $crad->prepare("
                                        SELECT * FROM research_progress_feedback
                                        WHERE progress_update_id = ?
                                        ORDER BY created_at DESC
                                    ");
                                    $fbStmt->execute([$updateId]);
                                    $feedbacks = $fbStmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) { $feedbacks = []; }

                                $fbTypeMeta = [
                                    'Comment'          => ['color'=>'#3b82f6','bg'=>'#dbeafe','icon'=>'comment'],
                                    'Revision Request' => ['color'=>'#ef4444','bg'=>'#fee2e2','icon'=>'redo'],
                                    'Approval'         => ['color'=>'#10b981','bg'=>'#d1fae5','icon'=>'check-circle'],
                                    'Progress Approved'=> ['color'=>'#059669','bg'=>'#d1fae5','icon'=>'check-double'],
                                ];
                            ?>
                                <div class="collapse" id="feedbackThread<?= $updateId ?>">
                                    <div class="rm-feedback-thread mt-2">
                                        <div class="rm-feedback-thread-title">
                                            <?= smsIcon('comments') ?>Feedback History
                                        </div>
                                        <?php foreach ($feedbacks as $fb):
                                            $fbc = $fbTypeMeta[$fb['feedback_type']] ?? ['color'=>'#64748b','bg'=>'#f1f5f9','icon'=>'info'];
                                        ?>
                                            <div class="rm-feedback-item" style="background:<?= $fbc['bg'] ?>;border-left:3px solid <?= $fbc['color'] ?>;">
                                                <div class="rm-feedback-item-meta">
                                                    <span class="rm-feedback-item-type" style="background:<?= $fbc['color'] ?>;color:#fff;">
                                                        <?= smsIcon($fbc['icon']) ?><?= htmlspecialchars($fb['feedback_type']) ?>
                                                    </span>
                                                    <span class="rm-feedback-item-time">
                                                        <?= date('M d, Y g:i A', strtotime($fb['created_at'])) ?>
                                                    </span>
                                                </div>
                                                <div style="font-size:0.88rem;color:var(--sms-text);">
                                                    <?= nl2br(htmlspecialchars($fb['feedback_text'])) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- ── Modals ───────────────────────────────── -->

                    <!-- Comment Modal -->
                    <div class="modal fade rm-modal" id="feedbackModal<?= $updateId ?>" tabindex="-1" aria-labelledby="feedbackModalTitle<?= $updateId ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="feedbackModalTitle<?= $updateId ?>">
                                        <?= smsIcon('comment', ['class' => 'me-2', 'style' => 'color:#3b82f6;']) ?>Add Comment
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form class="feedback-form" data-action="comment" data-update-id="<?= $updateId ?>">
                                        <input type="hidden" name="action_token" value="<?= htmlspecialchars(rpGenerateSubmissionToken()) ?>">
                                        <div class="mb-3">
                                            <label class="form-label" style="font-weight:700;font-size:0.88rem;">Your Comment</label>
                                            <textarea name="feedback_text" class="form-control" rows="4" required
                                                      placeholder="Provide feedback, suggestions, or questions..."></textarea>
                                        </div>
                                        <div class="rm-modal-actions">
                                            <button type="submit" class="btn btn-primary">
                                                <?= smsIcon('paper-plane', ['class' => 'me-2']) ?>Submit Comment
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revision Modal -->
                    <div class="modal fade rm-modal" id="revisionModal<?= $updateId ?>" tabindex="-1" aria-labelledby="revisionModalTitle<?= $updateId ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header" style="background:#fff9c4;border-bottom:1px solid #fde68a;">
                                    <h5 class="modal-title" id="revisionModalTitle<?= $updateId ?>" style="color:#92400e;">
                                        <?= smsIcon('redo', ['class' => 'me-2']) ?>Request Revision
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-warning py-2 mb-3" style="font-size:0.85rem;">
                                        <?= smsIcon('info-circle', ['class' => 'me-2']) ?>
                                        Milestone status will change to <strong>Revision Requested</strong> and the student will be notified.
                                    </div>
                                    <form class="feedback-form" data-action="revision" data-update-id="<?= $updateId ?>">
                                        <input type="hidden" name="action_token" value="<?= htmlspecialchars(rpGenerateSubmissionToken()) ?>">
                                        <div class="mb-3">
                                            <label class="form-label" style="font-weight:700;font-size:0.88rem;">Revision Instructions</label>
                                            <textarea name="feedback_text" class="form-control" rows="5" required
                                                      placeholder="Explain what needs to be revised and why..."></textarea>
                                        </div>
                                        <div class="rm-modal-actions">
                                            <button type="submit" class="btn btn-warning">
                                                <?= smsIcon('redo', ['class' => 'me-2']) ?>Request Revision
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Approve Modal -->
                    <div class="modal fade rm-modal" id="approveModal<?= $updateId ?>" tabindex="-1" aria-labelledby="approveModalTitle<?= $updateId ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header" style="background:#dcfce7;border-bottom:1px solid #bbf7d0;">
                                    <h5 class="modal-title" id="approveModalTitle<?= $updateId ?>" style="color:#065f46;">
                                        <?= smsIcon('check-circle', ['class' => 'me-2']) ?>Approve Progress
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-success py-2 mb-3" style="font-size:0.85rem;">
                                        <?= smsIcon('info-circle', ['class' => 'me-2']) ?>
                                        Milestone status will change to <strong>Approved</strong> and the student will be notified.
                                    </div>
                                    <form class="feedback-form" data-action="approve" data-update-id="<?= $updateId ?>">
                                        <input type="hidden" name="action_token" value="<?= htmlspecialchars(rpGenerateSubmissionToken()) ?>">
                                        <div class="mb-3">
                                            <label class="form-label" style="font-weight:700;font-size:0.88rem;">Approval Message <span style="font-weight:400;color:var(--sms-text-muted);">(optional)</span></label>
                                            <textarea name="feedback_text" class="form-control" rows="3"
                                                      placeholder="Provide encouragement or additional notes..."></textarea>
                                        </div>
                                        <div class="rm-modal-actions">
                                            <button type="submit" class="btn btn-success">
                                                <?= smsIcon('check-circle', ['class' => 'me-2']) ?>Approve Progress
                                            </button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="glass-panel">
                <div class="glass-panel-body rm-empty">
                    <div class="rm-empty-icon"><?= smsIcon('inbox') ?></div>
                    <h6>No Progress Updates</h6>
                    <p>
                        <?php if ($statusFilter !== 'all' || $milestoneFilter): ?>
                            No updates match the current filters. Try adjusting them.
                        <?php else: ?>
                            No progress updates have been submitted yet.
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

<!-- AJAX feedback submission -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const initialUpdatesHash = <?= json_encode(array_map(static fn($u) => [
        'id' => (int) $u['id'],
        'status' => (string) $u['milestone_status'],
        'updated_at' => (string) ($u['updated_at'] ?? ''),
        'attachment_id' => (int) ($u['attachment_id'] ?? 0),
    ], $progressUpdates)) ?>;
    document.addEventListener('research:updates-refreshed', function (event) {
        const live = (event.detail && Array.isArray(event.detail.updates)) ? event.detail.updates : [];
        const milestoneFilter = <?= json_encode($milestoneFilter) ?>;
        const updateIdFilter = <?= json_encode($updateIdFilter) ?>;
        const statusFilter = <?= json_encode($statusFilter) ?>;
        const liveHash = live
            .filter(row => String(row.group_number || '') === <?= json_encode($groupNumber) ?>)
            .filter(row => !milestoneFilter || parseInt(row.milestone_id || 0, 10) === parseInt(milestoneFilter, 10))
            .filter(row => !updateIdFilter || parseInt(row.id || 0, 10) === parseInt(updateIdFilter, 10))
            .filter(row => statusFilter === 'all' || String(row.milestone_status || '') === statusFilter)
            .map(row => ({
                id: parseInt(row.id || 0, 10),
                status: String(row.milestone_status || ''),
                updated_at: String(row.updated_at || ''),
                attachment_id: parseInt(row.attachment_id || 0, 10)
            }));
        if (JSON.stringify(liveHash) !== JSON.stringify(initialUpdatesHash)) {
            window.location.reload();
        }
    });

    document.querySelectorAll('[data-ai-generate]').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const updateId = this.getAttribute('data-update-id');
            const card = this.closest('.rm-update-card');
            const origHTML = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<?= smsIcon('spinner', ['class' => 'fa-spin me-1']) ?>Analyzing…';
            try {
                const resp = await fetch('<?= BASE_URL ?>/modules/crad/api/ai-document-analysis.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'analyze', update_id: parseInt(updateId, 10) })
                });
                const result = await resp.json();
                if (!resp.ok || !result.success || !result.analysis) {
                    alert(result.message || 'AI analysis failed. Please try again.');
                    this.disabled = false;
                    this.innerHTML = origHTML;
                    return;
                }
                renderAiAnalysis(card, result.analysis, result.revision_text || '');
            } catch (err) {
                console.error(err);
                alert('AI analysis could not be completed. Please try again.');
                this.disabled = false;
                this.innerHTML = origHTML;
                return;
            }
            this.disabled = false;
            this.innerHTML = origHTML;
        });
    });

    document.querySelectorAll('[data-ai-use-notes]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const updateId = this.getAttribute('data-update-id');
            const panel = document.querySelector('[data-ai-panel][data-update-id="' + updateId + '"]');
            const textarea = document.querySelector('#revisionModal' + updateId + ' textarea[name="feedback_text"]');
            if (textarea && panel) {
                textarea.value = panel.getAttribute('data-revision-text') || '';
            }
            const modalEl = document.getElementById('revisionModal' + updateId);
            if (modalEl && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    });

    function renderAiAnalysis(card, analysis, revisionText) {
        if (!card || !analysis) return;
        const panel = card.querySelector('[data-ai-panel]');
        if (!panel) return;
        const verdict = String(analysis.verdict || 'needs_revision');
        panel.classList.remove('d-none');
        panel.setAttribute('data-verdict', verdict);
        panel.setAttribute('data-revision-text', revisionText || '');
        const pill = panel.querySelector('[data-ai-verdict-pill]');
        if (pill) {
            pill.textContent = verdict === 'acceptable' ? 'Acceptable grammar' : 'Needs revision';
        }
        const summary = panel.querySelector('[data-ai-summary]');
        if (summary) {
            summary.innerHTML = escapeHtml(String(analysis.summary || '')).replace(/\n/g, '<br>');
        }
        const list = panel.querySelector('[data-ai-notes]');
        if (list) {
            const notes = Array.isArray(analysis.notes) ? analysis.notes : [];
            list.innerHTML = notes.map(function (note) {
                const issue = escapeHtml(String(note.issue || ''));
                const suggestion = note.suggestion ? '<div>' + escapeHtml(String(note.suggestion)) + '</div>' : '';
                const example = note.example ? '<div class="rm-ai-example">“' + escapeHtml(String(note.example)) + '”</div>' : '';
                return '<li><strong>' + issue + '</strong>' + suggestion + example + '</li>';
            }).join('');
        }
        card.querySelectorAll('[data-decision-btn]').forEach(function (decisionBtn) {
            decisionBtn.disabled = false;
        });
        const gate = card.querySelector('[data-ai-gate-note]');
        if (gate) gate.remove();
        if (!panel.querySelector('[data-ai-use-notes]')) {
            const useBtn = document.createElement('button');
            useBtn.type = 'button';
            useBtn.className = 'btn btn-sm btn-outline-warning';
            useBtn.setAttribute('data-ai-use-notes', '1');
            useBtn.setAttribute('data-update-id', card.getAttribute('data-update-id') || '');
            useBtn.textContent = 'Use notes in Request Revision';
            useBtn.addEventListener('click', function () {
                const updateId = this.getAttribute('data-update-id');
                const textarea = document.querySelector('#revisionModal' + updateId + ' textarea[name="feedback_text"]');
                if (textarea) textarea.value = panel.getAttribute('data-revision-text') || '';
                const modalEl = document.getElementById('revisionModal' + updateId);
                if (modalEl && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                }
            });
            panel.appendChild(useBtn);
        }
    }

    function escapeHtml(value) {
        return value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    document.querySelectorAll('.feedback-form').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const action      = this.dataset.action;
            const updateId    = this.dataset.updateId;
            const token       = this.querySelector('[name="action_token"]').value;
            const feedbackText= (this.querySelector('[name="feedback_text"]').value || '').trim();
            const submitBtn   = this.querySelector('button[type="submit"]');
            const origHTML    = submitBtn.innerHTML;

            if (action !== 'approve' && !feedbackText) {
                alert('Please provide feedback text.'); return;
            }
            if (action === 'approve') {
                const card = document.querySelector('.rm-update-card[data-update-id="' + updateId + '"]');
                const panel = card ? card.querySelector('[data-ai-panel]') : null;
                if (panel && panel.getAttribute('data-verdict') === 'needs_revision') {
                    const proceed = confirm('AI found grammar issues in this research file. Approve anyway?');
                    if (!proceed) return;
                }
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<?= smsIcon('spinner', ['class' => 'fa-spin me-2']) ?>Submitting…';

            try {
                const resp = await fetch('<?= BASE_URL ?>/modules/crad/api/adviser-progress.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: action,
                        update_id: parseInt(updateId),
                        feedback_text: feedbackText || (action === 'approve' ? 'Progress approved.' : ''),
                        submission_token: token
                    })
                });
                const result = await resp.json();
                if (resp.ok && result.success) {
                    const modal = bootstrap.Modal.getInstance(this.closest('.modal'));
                    if (modal) modal.hide();
                    // Brief toast-style success then reload
                    const bar = document.getElementById('rmRefreshBar');
                    if (bar) {
                        bar.style.background = 'rgba(16,185,129,0.15)';
                        bar.style.color = '#10b981';
                        document.getElementById('rmRefreshText').textContent = result.message || 'Saved! Refreshing…';
                    }
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    alert(result.message || 'Failed to submit. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = origHTML;
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = origHTML;
            }
        });
    });
});
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
