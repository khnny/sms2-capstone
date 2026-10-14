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
    $tablesCheck = $crad->query("SHOW TABLES LIKE 'crad_research_plans'")->fetch();
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
               rpa.id AS attachment_id, rpa.file_name AS attachment_name, rpa.file_type AS attachment_type, rpa.file_size AS attachment_size,
               rpai.id AS ai_analysis_id,
               rpai.summary AS ai_summary, rpai.notes_json AS ai_notes_json, rpai.source AS ai_source, rpai.created_at AS ai_analyzed_at,
               (SELECT COUNT(*) FROM `crad_research_progress_feedback` rpf WHERE rpf.progress_update_id = rpu.id) AS feedback_count
        FROM `crad_research_progress_updates` rpu
        LEFT JOIN `crad_research_milestones` rm ON rm.id = rpu.milestone_id
        LEFT JOIN `crad_research_progress_attachments` rpa ON rpa.id = (
            SELECT rpa2.id
            FROM `crad_research_progress_attachments` rpa2
            WHERE rpa2.progress_update_id = rpu.id
            ORDER BY rpa2.id DESC
            LIMIT 1
        )
        LEFT JOIN `crad_research_progress_ai_analyses` rpai ON rpai.id = (
            SELECT rpai2.id
            FROM `crad_research_progress_ai_analyses` rpai2
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
            SELECT id, milestone_name, milestone_order FROM `crad_research_milestones`
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
                    $aiKeyPoints = [];
                    $aiSections = [];
                    $aiStyleObservations = [];
                    $aiIssues = [];
                    $aiMissingInformation = [];
                    $aiAnalysisType = 'full';
                    if (!empty($update['ai_notes_json'])) {
                        $decodedNotes = json_decode((string) $update['ai_notes_json'], true);
                        if (is_array($decodedNotes) && array_is_list($decodedNotes)) {
                            $aiNotes = $decodedNotes;
                            $aiIssues = $decodedNotes;
                        } elseif (is_array($decodedNotes)) {
                            $aiNotes = is_array($decodedNotes['notes'] ?? null) ? $decodedNotes['notes'] : [];
                            $aiKeyPoints = is_array($decodedNotes['key_points'] ?? null) ? $decodedNotes['key_points'] : [];
                            $aiSections = is_array($decodedNotes['sections'] ?? null) ? $decodedNotes['sections'] : [];
                            $aiStyleObservations = is_array($decodedNotes['style_observations'] ?? null) ? $decodedNotes['style_observations'] : [];
                            $aiIssues = is_array($decodedNotes['issues'] ?? null) ? $decodedNotes['issues'] : $aiNotes;
                            $aiMissingInformation = is_array($decodedNotes['missing_information'] ?? null) ? $decodedNotes['missing_information'] : [];
                            $aiAnalysisType = (string) ($decodedNotes['analysis_type'] ?? 'full');
                        }
                    }
                    if ($aiStyleObservations === []) {
                        $aiStyleObservations = array_values(array_filter($aiNotes, static fn(array $note): bool => ($note['category'] ?? '') === 'style'));
                    }
                    $aiObservationCount = count($aiNotes !== [] ? $aiNotes : $aiIssues);
                    $hasAiAnalysis = !empty($update['ai_analysis_id']);
                    $aiSource = (string) ($update['ai_source'] ?? '');
                    $aiSourceLabel = match ($aiSource) {
                        'grammar_engine' => 'LanguageTool · writing observations',
                        'openai_gpt_4_1' => 'OpenAI GPT-4.1',
                        'cursor' => 'Legacy Cursor AI analysis',
                        default => $hasAiAnalysis ? 'Legacy AI analysis' : 'Not analyzed',
                    };
                    $attachmentExtension = strtoupper(pathinfo((string) ($update['attachment_name'] ?? ''), PATHINFO_EXTENSION));
                    $attachmentSizeLabel = (int) ($update['attachment_size'] ?? 0) > 0
                        ? number_format((int) $update['attachment_size'] / 1048576, 1) . ' MB'
                        : 'Size unavailable';
                    $needsAiBeforeDecision = ($update['milestone_status'] === 'Submitted for Review') && !empty($update['attachment_id']);
                    $aiRevisionText = $hasAiAnalysis
                        ? rpFormatAiNotesForRevision([
                            'milestone_name' => (string) ($update['milestone_name'] ?? ''),
                            'summary' => (string) ($update['ai_summary'] ?? ''),
                            'notes' => $aiNotes !== [] ? $aiNotes : $aiIssues,
                            'missing_information' => $aiMissingInformation,
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
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">No document attached</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($update['attachment_id'])): ?>
                                <section class="rm-ai-launcher" aria-label="AI document analysis controls">
                                    <div class="rm-ai-launcher-copy">
                                        <span class="rm-ai-eyebrow">Research writing support</span>
                                        <h3><?= smsIcon('robot', ['aria-hidden' => 'true']) ?>AI Research Assistant</h3>
                                        <p>Get a concise summary and reviewable writing observations for this submission.</p>
                                    </div>
                                    <div class="rm-ai-file-meta">
                                        <span class="rm-ai-file-icon"><?= smsIcon('file-text', ['aria-hidden' => 'true']) ?></span>
                                        <span><strong><?= htmlspecialchars((string) $update['attachment_name']) ?></strong><small><?= htmlspecialchars($attachmentExtension ?: 'FILE') ?> · <?= htmlspecialchars($attachmentSizeLabel) ?></small></span>
                                        <span class="rm-ai-file-status"><?= $hasAiAnalysis ? 'Previously analyzed' : 'Ready to analyze' ?></span>
                                    </div>
                                    <div class="rm-ai-controls">
                                        <label for="aiAnalysisType<?= $updateId ?>">Analysis focus</label>
                                        <select class="form-select form-select-sm" id="aiAnalysisType<?= $updateId ?>" data-ai-analysis-type>
                                            <?php foreach (['full' => 'Full analysis', 'summary' => 'Summary', 'structure' => 'Structure analysis', 'style' => 'Writing and style'] as $analysisTypeKey => $analysisTypeLabel): ?>
                                                <option value="<?= htmlspecialchars($analysisTypeKey) ?>" <?= $aiAnalysisType === $analysisTypeKey ? 'selected' : '' ?>><?= htmlspecialchars($analysisTypeLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-sm btn-sms-primary rm-ai-generate-btn" data-ai-generate data-update-id="<?= $updateId ?>">
                                            <?= smsIcon('sparkles', ['aria-hidden' => 'true']) ?><?= $hasAiAnalysis ? 'Analyze again' : 'Analyze document' ?>
                                        </button>
                                    </div>
                                    <p class="rm-ai-format-note">Text analysis works best with DOCX or text-based PDFs. Scanned images and legacy DOC files may not contain readable text. Analysis is assistive and does not approve or grade submissions.</p>
                                    <div class="rm-ai-progress" data-ai-progress hidden role="status"><span></span><strong>Reviewing document text…</strong></div>
                                    <div class="rm-ai-live" data-ai-status role="status" aria-live="polite"></div>
                                </section>
                            <?php endif; ?>

                            <section class="rm-ai-panel<?= $hasAiAnalysis ? '' : ' d-none' ?>"
                                     data-ai-panel data-update-id="<?= $updateId ?>"
                                     data-revision-text="<?= htmlspecialchars($aiRevisionText) ?>">
                                <header class="rm-ai-panel-head">
                                    <div><span class="rm-ai-eyebrow">Analysis results</span><h3 class="rm-ai-panel-title"><?= smsIcon('sparkles', ['aria-hidden' => 'true']) ?>AI document observations</h3>
                                        <p class="rm-ai-panel-sub">Assistive feedback only. Review the complete document and make the academic decision yourself.</p></div>
                                    <span class="rm-ai-verdict" data-ai-verdict-pill><?= $hasAiAnalysis ? 'Advisory only' : '' ?></span>
                                </header>
                                <div class="rm-ai-metadata" data-ai-metadata>
                                    <span><small>Document</small><strong><?= htmlspecialchars((string) $update['attachment_name']) ?></strong></span>
                                    <span><small>Type</small><strong><?= htmlspecialchars($attachmentExtension ?: 'File') ?></strong></span>
                                    <span><small>Analyzed</small><strong data-ai-analyzed-at><?= $hasAiAnalysis && !empty($update['ai_analyzed_at']) ? htmlspecialchars(date('M j, Y · g:i A', strtotime((string) $update['ai_analyzed_at']))) : 'Not analyzed' ?></strong></span>
                                    <span><small>Observations</small><strong data-ai-observation-count><?= $aiObservationCount ?></strong></span>
                                    <span><small>Analysis method</small><strong data-ai-source><?= htmlspecialchars($aiSourceLabel) ?></strong></span>
                                </div>
                                <nav class="rm-ai-tabs" role="tablist" aria-label="Analysis result sections">
                                    <?php foreach (['overview' => 'Overview', 'summary' => 'Summary', 'structure' => 'Structure', 'style' => 'Style', 'issues' => 'Issues'] as $tabKey => $tabLabel): ?>
                                        <button type="button" role="tab" id="aiTab<?= $updateId ?><?= ucfirst($tabKey) ?>" aria-controls="aiContent<?= $updateId ?><?= ucfirst($tabKey) ?>" aria-selected="<?= $tabKey === 'overview' ? 'true' : 'false' ?>" class="<?= $tabKey === 'overview' ? 'is-active' : '' ?>" data-ai-tab="<?= htmlspecialchars($tabKey) ?>"><?= htmlspecialchars($tabLabel) ?></button>
                                    <?php endforeach; ?>
                                </nav>
                                <div class="rm-ai-tab-content">
                                    <section id="aiContent<?= $updateId ?>Overview" role="tabpanel" aria-labelledby="aiTab<?= $updateId ?>Overview" data-ai-content="overview">
                                        <h4>Overview</h4><p class="rm-ai-summary" data-ai-summary><?= $hasAiAnalysis ? nl2br(htmlspecialchars((string) $update['ai_summary'])) : '' ?></p>
                                        <div class="rm-ai-overview-stats"><span><strong data-ai-section-count><?= count($aiSections) ?></strong> detected sections</span><span><strong data-ai-point-count><?= count($aiKeyPoints) ?></strong> key points</span><span><strong data-ai-issue-count><?= count($aiIssues) ?></strong> review items</span></div>
                                    </section>
                                    <section id="aiContent<?= $updateId ?>Summary" role="tabpanel" aria-labelledby="aiTab<?= $updateId ?>Summary" data-ai-content="summary" hidden>
                                        <h4>Executive summary</h4><p class="rm-ai-summary" data-ai-summary-copy><?= $hasAiAnalysis ? nl2br(htmlspecialchars((string) $update['ai_summary'])) : '' ?></p><h5>Key points</h5><ul class="rm-ai-key-points" data-ai-key-points><?php foreach ($aiKeyPoints as $point): ?><li><?= htmlspecialchars((string) $point) ?></li><?php endforeach; ?></ul><p class="rm-ai-empty-note" data-ai-points-empty <?= $aiKeyPoints ? 'hidden' : '' ?>>Key points are not available for this analysis mode or provider.</p>
                                    </section>
                                    <section id="aiContent<?= $updateId ?>Structure" role="tabpanel" aria-labelledby="aiTab<?= $updateId ?>Structure" data-ai-content="structure" hidden>
                                        <h4>Detected structure</h4><ul class="rm-ai-section-list" data-ai-sections><?php foreach ($aiSections as $section): ?><?php $sectionName = is_array($section) ? (string) ($section['name'] ?? '') : (string) $section; ?><?php if ($sectionName !== ''): ?><li><span><?= smsIcon(!is_array($section) || !empty($section['present']) ? 'check-circle' : 'alert-circle', ['aria-hidden' => 'true']) ?></span><div><strong><?= htmlspecialchars($sectionName) ?></strong><?php if (is_array($section) && !empty($section['observation'])): ?><p><?= htmlspecialchars((string) $section['observation']) ?></p><?php endif; ?></div></li><?php endif; ?><?php endforeach; ?></ul><p class="rm-ai-empty-note" data-ai-structure-empty <?= $aiSections ? 'hidden' : '' ?>>Structure observations were not returned for this analysis. Select Structure or Full Analysis when the AI provider is available.</p>
                                    </section>
                                    <section id="aiContent<?= $updateId ?>Style" role="tabpanel" aria-labelledby="aiTab<?= $updateId ?>Style" data-ai-content="style" hidden>
                                        <h4>Writing and style</h4><ul class="rm-ai-finding-list" data-ai-style><?php foreach ($aiStyleObservations as $finding): ?><li><details><summary><?= htmlspecialchars((string) ($finding['issue'] ?? $finding['observation'] ?? 'Writing observation')) ?></summary><?php if (!empty($finding['suggestion'])): ?><p><?= htmlspecialchars((string) $finding['suggestion']) ?></p><?php endif; ?><?php if (!empty($finding['example'])): ?><blockquote><?= htmlspecialchars((string) $finding['example']) ?></blockquote><?php endif; ?></details></li><?php endforeach; ?></ul><p class="rm-ai-empty-note" data-ai-style-empty <?= $aiStyleObservations ? 'hidden' : '' ?>>No separate style observations were returned for this analysis.</p>
                                    </section>
                                    <section id="aiContent<?= $updateId ?>Issues" role="tabpanel" aria-labelledby="aiTab<?= $updateId ?>Issues" data-ai-content="issues" hidden>
                                        <h4>Areas to review</h4><ul class="rm-ai-finding-list" data-ai-issues><?php foreach ($aiIssues as $finding): ?><?php if (is_array($finding)): ?><li><details><summary><span class="rm-ai-severity <?= htmlspecialchars((string) ($finding['severity'] ?? 'review')) ?>"><?= htmlspecialchars(ucfirst((string) ($finding['severity'] ?? 'review'))) ?></span><?= htmlspecialchars((string) ($finding['issue'] ?? 'Observation')) ?></summary><?php if (!empty($finding['suggestion'])): ?><p><?= htmlspecialchars((string) $finding['suggestion']) ?></p><?php endif; ?><?php if (!empty($finding['example'])): ?><blockquote><?= htmlspecialchars((string) $finding['example']) ?></blockquote><?php endif; ?></details></li><?php endif; ?><?php endforeach; ?></ul><p class="rm-ai-empty-note" data-ai-issues-empty <?= $aiIssues ? 'hidden' : '' ?>>No specific review items were returned.</p>
                                        <h5 class="mt-4">Potentially missing information to verify</h5><ul class="rm-ai-finding-list" data-ai-missing-information><?php foreach ($aiMissingInformation as $finding): ?><?php if (is_array($finding)): ?><li><details><summary><?= htmlspecialchars((string) ($finding['item'] ?? 'Item to verify')) ?></summary><?php if (!empty($finding['why_it_matters'])): ?><p><?= htmlspecialchars((string) $finding['why_it_matters']) ?></p><?php endif; ?><?php if (!empty($finding['suggestion'])): ?><p><?= htmlspecialchars((string) $finding['suggestion']) ?></p><?php endif; ?></details></li><?php endif; ?><?php endforeach; ?></ul><p class="rm-ai-empty-note" data-ai-missing-empty <?= $aiMissingInformation ? 'hidden' : '' ?>>No potentially missing information was identified. This is not a completeness determination.</p>
                                    </section>
                                </div>
                                <?php if ($needsAiBeforeDecision): ?><button type="button" class="btn btn-sm btn-outline-secondary mt-2" data-ai-use-notes data-update-id="<?= $updateId ?>"><?= smsIcon('redo', ['class' => 'me-1']) ?>Use selected observations in revision request</button><?php endif; ?>
                            </section>

                            <!-- Action buttons -->
                            <div class="rm-action-row" data-action-controls>
                                <?php if ($needsAiBeforeDecision && !$hasAiAnalysis): ?>
                                    <div class="rm-ai-gate-note" data-ai-gate-note>
                                        <?= smsIcon('info-circle') ?>
                                        Complete the analysis to unlock the existing review controls. AI observations are advisory only; you may <strong>Request Revision</strong> at any time.
                                    </div>
                                <?php endif; ?>
                                <button type="button" class="rm-btn rm-btn-comment"
                                        data-bs-toggle="modal" data-bs-target="#feedbackModal<?= $updateId ?>">
                                    <?= smsIcon('comment') ?>Comment
                                </button>
                                <button type="button" class="rm-btn rm-btn-revision"
                                        data-bs-toggle="modal" data-bs-target="#revisionModal<?= $updateId ?>"
                                        data-decision-btn="revision">
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
                                        SELECT * FROM `crad_research_progress_feedback`
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
    const csrfToken = <?= json_encode(csrfToken()) ?>;
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
            const status = card ? card.querySelector('[data-ai-status]') : null;
            const progress = card ? card.querySelector('[data-ai-progress]') : null;
            const analysisType = card ? card.querySelector('[data-ai-analysis-type]') : null;
            this.disabled = true;
            this.innerHTML = '<?= smsIcon('spinner', ['class' => 'fa-spin me-1']) ?>Analyzing document…';
            if (status) status.textContent = '';
            if (progress) progress.hidden = false;
            if (card) card.setAttribute('aria-busy', 'true');
            try {
                const resp = await fetch('<?= BASE_URL ?>/modules/crad/api/ai-document-analysis.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'analyze',
                        update_id: parseInt(updateId, 10),
                        analysis_type: analysisType ? analysisType.value : 'full'
                    })
                });
                const result = await resp.json();
                if (!resp.ok || !result.success || !result.analysis) {
                    // Keep Request Revision available so adviser can send the student back to revise.
                    if (card) {
                        const revisionBtn = card.querySelector('[data-decision-btn="revision"]');
                        if (revisionBtn) revisionBtn.disabled = false;
                        const gate = card.querySelector('[data-ai-gate-note]');
                        if (gate) {
                            gate.innerHTML = '<?= smsIcon('info-circle') ?>The file could not be analyzed. You can still <strong>Request Revision</strong> so the student can upload a readable, text-based PDF or DOCX. Existing review controls remain subject to the current workflow.';
                        }
                        const updateIdForModal = this.getAttribute('data-update-id');
                        const revisionTextarea = document.querySelector('#revisionModal' + updateIdForModal + ' textarea[name="feedback_text"]');
                        if (revisionTextarea && !revisionTextarea.value.trim()) {
                            revisionTextarea.value = 'Please upload a readable, text-based PDF or DOCX file, then resubmit this milestone for review. Scanned images and legacy DOC files may not be readable by the analysis service.';
                        }
                    }
                    if (status) status.textContent = (result.message || 'Analysis could not be completed.') + ' You can still request revision or review the attached file manually.';
                } else {
                    renderAiAnalysis(card, result.analysis, result.revision_text || '');
                    if (status) status.textContent = result.message || 'Analysis complete. Review the observations before taking action.';
                }
            } catch (err) {
                console.error(err);
                if (card) {
                    const revisionBtn = card.querySelector('[data-decision-btn="revision"]');
                    if (revisionBtn) revisionBtn.disabled = false;
                }
                if (status) status.textContent = 'The analysis service could not be reached. You can still review the attached file or request revision.';
            } finally {
                if (progress) progress.hidden = true;
                if (card) card.removeAttribute('aria-busy');
                this.disabled = false;
                this.innerHTML = origHTML;
            }
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
        panel.classList.remove('d-none');
        panel.setAttribute('data-revision-text', revisionText || '');
        const pill = panel.querySelector('[data-ai-verdict-pill]');
        if (pill) pill.textContent = 'Advisory only';
        const summary = panel.querySelector('[data-ai-summary]');
        const summaryText = String(analysis.summary || 'No summary was returned.');
        if (summary) summary.innerHTML = escapeHtml(summaryText).replace(/\n/g, '<br>');
        const summaryCopy = panel.querySelector('[data-ai-summary-copy]');
        if (summaryCopy) summaryCopy.innerHTML = escapeHtml(summaryText).replace(/\n/g, '<br>');

        const notes = Array.isArray(analysis.notes) ? analysis.notes : [];
        const keyPoints = Array.isArray(analysis.key_points) ? analysis.key_points : [];
        const sections = Array.isArray(analysis.sections) ? analysis.sections : [];
        const styles = Array.isArray(analysis.style_observations) && analysis.style_observations.length
            ? analysis.style_observations
            : notes.filter(function (item) { return item && item.category === 'style'; });
        const issues = Array.isArray(analysis.issues) && analysis.issues.length ? analysis.issues : notes;
        const observationCount = notes.length || issues.length;
        const missingInformation = Array.isArray(analysis.missing_information) ? analysis.missing_information : [];
        const pointsList = panel.querySelector('[data-ai-key-points]');
        if (pointsList) pointsList.innerHTML = keyPoints.map(function (point) { return '<li>' + escapeHtml(String(point)) + '</li>'; }).join('');
        const sectionList = panel.querySelector('[data-ai-sections]');
        if (sectionList) {
            sectionList.innerHTML = sections.map(function (section) {
                const item = typeof section === 'string' ? { name: section, present: true } : section;
                const icon = item.present === false ? 'alert-circle' : 'check-circle';
                const explanation = item.observation ? '<p>' + escapeHtml(String(item.observation)) + '</p>' : '';
                const suggestion = item.suggestion ? '<p>' + escapeHtml(String(item.suggestion)) + '</p>' : '';
                return '<li><span><i class="ti ti-' + icon + '" aria-hidden="true"></i></span><div><strong>' + escapeHtml(String(item.name || 'Section')) + '</strong>' + explanation + suggestion + '</div></li>';
            }).join('');
        }
        function findingMarkup(finding, isIssue) {
            const item = typeof finding === 'string' ? { issue: finding } : finding;
            const title = String(item.issue || item.observation || 'Observation');
            const severity = String(item.severity || 'review');
            const badge = isIssue ? '<span class="rm-ai-severity ' + escapeHtml(severity) + '">' + escapeHtml(severity === 'attention' ? 'Attention' : 'Review') + '</span>' : '';
            const suggestion = item.suggestion ? '<p>' + escapeHtml(String(item.suggestion)) + '</p>' : '';
            const example = item.example ? '<blockquote>' + escapeHtml(String(item.example)) + '</blockquote>' : '';
            return '<li><details><summary>' + badge + escapeHtml(title) + '</summary>' + suggestion + example + '</details></li>';
        }
        const styleList = panel.querySelector('[data-ai-style]');
        if (styleList) styleList.innerHTML = styles.map(function (item) { return findingMarkup(item, false); }).join('');
        const issueList = panel.querySelector('[data-ai-issues]');
        if (issueList) issueList.innerHTML = issues.map(function (item) { return findingMarkup(item, true); }).join('');
        const missingList = panel.querySelector('[data-ai-missing-information]');
        if (missingList) {
            missingList.innerHTML = missingInformation.map(function (item) {
                const finding = typeof item === 'string' ? { item: item } : item;
                const detail = [finding.why_it_matters, finding.suggestion].filter(Boolean).map(function (text) {
                    return '<p>' + escapeHtml(String(text)) + '</p>';
                }).join('');
                return '<li><details><summary>' + escapeHtml(String(finding.item || 'Item to verify')) + '</summary>' + detail + '</details></li>';
            }).join('');
        }
        const updateEmpty = function (selector, isEmpty) {
            const element = panel.querySelector(selector);
            if (element) element.hidden = !isEmpty;
        };
        updateEmpty('[data-ai-structure-empty]', sections.length === 0);
        updateEmpty('[data-ai-points-empty]', keyPoints.length === 0);
        updateEmpty('[data-ai-style-empty]', styles.length === 0);
        updateEmpty('[data-ai-issues-empty]', issues.length === 0);
        updateEmpty('[data-ai-missing-empty]', missingInformation.length === 0);
        const counts = {
            '[data-ai-observation-count]': observationCount,
            '[data-ai-section-count]': sections.length,
            '[data-ai-point-count]': keyPoints.length,
            '[data-ai-issue-count]': issues.length
        };
        Object.keys(counts).forEach(function (selector) {
            const element = panel.querySelector(selector);
            if (element) element.textContent = String(counts[selector]);
        });
        const analyzedAt = panel.querySelector('[data-ai-analyzed-at]');
        if (analyzedAt) {
            const parsed = analysis.created_at ? new Date(String(analysis.created_at).replace(' ', 'T')) : new Date();
            analyzedAt.textContent = Number.isNaN(parsed.getTime()) ? String(analysis.created_at || 'Just now') : parsed.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
        }
        const sourceLabel = panel.querySelector('[data-ai-source]');
        if (sourceLabel) {
            sourceLabel.textContent = analysis.source === 'grammar_engine'
                ? 'LanguageTool · writing observations'
                : (analysis.source === 'openai_gpt_4_1' ? 'OpenAI GPT-4.1' : 'Legacy AI analysis');
        }
        const mode = card.querySelector('[data-ai-analysis-type]');
        if (mode && ['summary', 'structure', 'style', 'full'].includes(String(analysis.analysis_type || ''))) mode.value = analysis.analysis_type;
        const activeTab = panel.querySelector('[data-ai-tab="overview"]');
        if (activeTab) activeTab.click();
        card.querySelectorAll('[data-decision-btn]').forEach(function (decisionBtn) {
            decisionBtn.disabled = false;
        });
        const gate = card.querySelector('[data-ai-gate-note]');
        if (gate) gate.remove();
        if (!panel.querySelector('[data-ai-use-notes]')) {
            const useBtn = document.createElement('button');
            useBtn.type = 'button';
            useBtn.className = 'btn btn-sm btn-outline-secondary mt-2';
            useBtn.setAttribute('data-ai-use-notes', '1');
            useBtn.setAttribute('data-update-id', card.getAttribute('data-update-id') || '');
            useBtn.textContent = 'Use selected observations in revision request';
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

    document.querySelectorAll('.rm-ai-tabs').forEach(function (tablist) {
        const tabs = Array.from(tablist.querySelectorAll('[data-ai-tab]'));
        function activateTab(tab) {
            const panel = tablist.closest('[data-ai-panel]');
            if (!panel) return;
            tabs.forEach(function (item) {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
                const content = panel.querySelector('[data-ai-content="' + item.dataset.aiTab + '"]');
                if (content) content.hidden = !active;
            });
        }
        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () { activateTab(tab); });
            tab.addEventListener('keydown', function (event) {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const nextIndex = event.key === 'Home' ? 0 : (event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length);
                tabs[nextIndex].focus();
                activateTab(tabs[nextIndex]);
            });
        });
    });

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
                        submission_token: token,
                        csrf_token: csrfToken
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
