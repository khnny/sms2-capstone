<?php
/**
 * SMS 2 - Financial & Tracking · Project Milestones
 * CRAD Staff tracks progress of funded grant research projects.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once __DIR__ . '/../includes/grant-milestone-helpers.php';
require_once __DIR__ . '/../includes/grant-funded-research-helpers.php';

requireAuth();
grantRequireFundedMilestoneViewAccess();

$pageTitle            = 'Project Milestones';
$activeModule         = grantActiveModuleKey();
$activePage           = 'project-milestones';
$hideModulePageBanner = true;
$canTrack             = grantUserCanTrackFundedMilestones();

$breadcrumbs = [
    ['label' => grantBreadcrumbModuleLabel(), 'url' => grantBreadcrumbModuleUrl()],
    ['label' => 'Financial & Tracking', 'url' => grantProjectMilestonesUrl()],
    ['label' => 'Project Milestones', 'url' => null],
];

require_once ROOT_PATH . '/includes/breadcrumbs.php';

$crad       = cradDb();
$overview   = [];
$selectedId = (int) ($_GET['id'] ?? 0);
$detail     = null;
$dbError    = '';

if ($crad) {
    try {
        grantBackfillFundedProjectMilestones($crad);
        $overview = grantGetFundedMilestoneOverview($crad);
        if ($selectedId <= 0 && $overview !== []) {
            $selectedId = (int) ($overview[0]['grant_application_id'] ?? 0);
        }
        if ($selectedId > 0) {
            $detail = grantGetFundedMilestoneDetail($crad, $selectedId);
        }
    } catch (Throwable $e) {
        $dbError = htmlspecialchars($e->getMessage());
        error_log('project-milestones: ' . $e->getMessage());
    }
} else {
    $dbError = 'CRAD database connection unavailable.';
}

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<link href="<?= BASE_URL ?>/assets/css/grant-project-milestones.css?v=4" rel="stylesheet">

<?php if ($dbError !== ''): ?>
<div class="gpm-alert" role="alert"><?= smsIcon('exclamation-triangle', ['class' => 'me-1']) ?><?= $dbError ?></div>
<?php endif; ?>

<div class="gpm"
     data-grant-milestones-live="1"
     data-selected-id="<?= (int) $selectedId ?>"
     data-can-track="<?= $canTrack ? '1' : '0' ?>">

    <header class="gpm-page-header">
        <div class="gpm-page-header-main">
            <h1>
                Track Progress of Funded Research
                <span class="gpm-live-badge"><?= smsIcon('sync-alt') ?> Live</span>
            </h1>
            <p><?php if ($canTrack): ?>Monitor milestone progress for <strong>APPROVED &amp; FUNDED</strong> grant projects — due dates, completion %, status, supporting documents, and remarks.<?php else: ?>View milestone progress for your <strong>APPROVED &amp; FUNDED</strong> grant. For the full research dashboard, open <a href="<?= htmlspecialchars(grantFundedResearchUrl()) ?>">Funded Research</a>.<?php endif; ?></p>
        </div>
        <?php if ($canTrack): ?>
        <div class="gpm-stat-row">
            <span class="gpm-stat"><strong data-gpm-funded-count><?= count($overview) ?></strong> funded projects</span>
            <span class="gpm-stat active"><strong data-gpm-active-count><?= count(array_filter($overview, static fn(array $r): bool => (string) ($r['progress_label'] ?? '') === 'In Progress')) ?></strong> in progress</span>
        </div>
        <?php endif; ?>
    </header>

    <?php if ($overview === []): ?>
        <div class="gpm-panel">
            <div class="gpm-empty">
                <?= smsIcon('tasks') ?>
                <p style="margin:0;font-weight:700;">No funded projects to track yet.</p>
                <p style="margin:.35rem 0 0;font-size:.84rem;color:#64748b;">Projects appear here after Finance completes approval and the proposal is marked APPROVED &amp; FUNDED.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="gpm-project-bar">
            <label for="gpmProjectSelect">Select Funded Project:</label>
            <select id="gpmProjectSelect" class="gpm-project-select" aria-label="Select funded project">
                <?php foreach ($overview as $row): ?>
                    <?php
                    $appId = (int) ($row['grant_application_id'] ?? 0);
                    $ref   = (string) ($row['proposal_reference'] ?? 'Proposal');
                    $title = (string) ($row['research_title'] ?? 'Untitled');
                    $label = (string) ($row['progress_label'] ?? '');
                    ?>
                    <option value="<?= $appId ?>"<?= $appId === $selectedId ? ' selected' : '' ?>>
                        <?= htmlspecialchars($ref . ': ' . $title . ' — ' . $label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="gpm-panel" id="gpmDetailPanel">
            <?php if ($detail === null): ?>
                <div class="gpm-empty">
                    <?= smsIcon('tasks') ?>
                    <p style="margin:0;">Select a funded project to view milestones.</p>
                </div>
            <?php else: ?>
                <?php
                $app = $detail['application'];
                $milestones = $detail['milestones'] ?? [];
                $ref = htmlspecialchars((string) ($app['proposal_reference'] ?? 'Proposal'));
                ?>
                <h2 class="gpm-panel-title">Milestones — <?= $ref ?></h2>
                <div class="gpm-meta">
                    <span><strong>Research Title:</strong> <?= htmlspecialchars((string) ($app['research_title'] ?? '—')) ?></span>
                    <span><strong>Lead Proponent:</strong> <?= htmlspecialchars((string) ($app['applicant_name'] ?? '—')) ?></span>
                    <span><strong>Overall Progress:</strong> <?= number_format((float) ($app['avg_completion_pct'] ?? 0), 1) ?>%</span>
                </div>

                <div class="gpm-table-wrap">
                    <table class="gpm-table" id="gpmMilestoneTable">
                        <thead>
                            <tr>
                                <th>Milestone</th>
                                <th>Due Date</th>
                                <th>Completion %</th>
                                <th>Status</th>
                                <th>Supporting Document</th>
                                <th>Remarks</th>
                                <?php if ($canTrack): ?><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="gpmMilestoneBody">
                            <?php foreach ($milestones as $milestone):
                                $status = (string) ($milestone['status'] ?? 'Pending');
                                $statusClass = grantMilestoneStatusClass($status);
                                $milestoneId = (int) ($milestone['id'] ?? 0);
                            ?>
                            <tr data-milestone-id="<?= $milestoneId ?>">
                                <td style="font-weight:700;"><?= htmlspecialchars((string) ($milestone['milestone_name'] ?? '')) ?></td>
                                <td><?= !empty($milestone['due_date']) ? htmlspecialchars(date('M d, Y', strtotime((string) $milestone['due_date']))) : '—' ?></td>
                                <td><strong><?= number_format((float) ($milestone['completion_pct'] ?? 0), 0) ?>%</strong></td>
                                <td><span class="gpm-status <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($status) ?></span></td>
                                <td>
                                    <?php if (!empty($milestone['has_document'])): ?>
                                        <a href="<?= htmlspecialchars((string) ($milestone['document_url'] ?? '')) ?>" target="_blank" rel="noopener">
                                            <?= smsIcon('file-alt') ?> <?= htmlspecialchars((string) ($milestone['supporting_doc_original'] ?? 'View')) ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:220px;font-size:.84rem;"><?= trim((string) ($milestone['remarks'] ?? '')) !== '' ? nl2br(htmlspecialchars((string) $milestone['remarks'])) : '—' ?></td>
                                <?php if ($canTrack): ?>
                                <td>
                                    <button type="button"
                                            class="gpm-btn gpm-btn-edit gpmEditMilestoneBtn"
                                            data-milestone-id="<?= $milestoneId ?>"
                                            data-milestone-name="<?= htmlspecialchars((string) ($milestone['milestone_name'] ?? ''), ENT_QUOTES) ?>"
                                            data-due-date="<?= htmlspecialchars((string) ($milestone['due_date'] ?? ''), ENT_QUOTES) ?>"
                                            data-completion="<?= htmlspecialchars((string) ($milestone['completion_pct'] ?? '0'), ENT_QUOTES) ?>"
                                            data-status="<?= htmlspecialchars($status, ENT_QUOTES) ?>"
                                            data-remarks="<?= htmlspecialchars((string) ($milestone['remarks'] ?? ''), ENT_QUOTES) ?>">
                                        <?= smsIcon('edit') ?> Update
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($canTrack):
                    $researchEvidence = [];
                    if ($selectedId > 0 && function_exists('grantGetFundedResearchEvidence')) {
                        $researchEvidence = grantGetFundedResearchEvidence($crad, $selectedId);
                    }
                ?>
                <div class="gpm-evidence-panel">
                    <h3 class="gpm-section-title"><?= smsIcon('folder-open', ['class' => 'me-1']) ?>Researcher Progress Evidence</h3>
                    <div class="gpm-table-wrap">
                        <table class="gpm-table">
                            <thead>
                                <tr>
                                    <th>Submitted</th>
                                    <th>Milestone</th>
                                    <th>Title</th>
                                    <th>File</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($researchEvidence === []): ?>
                                <tr><td colspan="5" style="text-align:center;padding:1.25rem;color:#64748b;">No researcher evidence submitted yet.</td></tr>
                                <?php else: ?>
                                <?php foreach ($researchEvidence as $evRow): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime((string) ($evRow['created_at'] ?? 'now')))) ?></td>
                                    <td><?= htmlspecialchars((string) ($evRow['milestone_name'] ?? '—')) ?></td>
                                    <td><?= htmlspecialchars((string) ($evRow['evidence_title'] ?? '')) ?></td>
                                    <td>
                                        <?php if (!empty($evRow['has_file'])): ?>
                                        <a href="<?= htmlspecialchars((string) ($evRow['file_url'] ?? '')) ?>" target="_blank" rel="noopener">
                                            <?= smsIcon('file-alt') ?> <?= htmlspecialchars((string) ($evRow['file_original'] ?? 'View')) ?>
                                        </a>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td><span class="gpm-status in-progress"><?= htmlspecialchars((string) ($evRow['status'] ?? 'Submitted')) ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($canTrack): ?>
    <div class="gpm-dialog" id="gpmEditDialog" role="dialog" aria-modal="true" aria-labelledby="gpmEditTitle">
        <div class="gpm-dialog-box">
            <div class="gpm-dialog-header">
                <h3 id="gpmEditTitle"><?= smsIcon('edit') ?> Update Milestone</h3>
                <p class="gpm-dialog-lead" id="gpmEditMilestoneName">Milestone</p>
            </div>
            <form id="gpmEditForm" class="gpm-form gpm-dialog-form" method="post" data-no-loader enctype="multipart/form-data">
                <input type="hidden" id="gpmEditMilestoneId" name="milestone_id" value="">
                <div class="gpm-dialog-body">
                    <label for="gpmEditDueDate">Due Date</label>
                    <input type="date" id="gpmEditDueDate" name="due_date">

                    <label for="gpmEditCompletion">Completion %</label>
                    <input type="number" id="gpmEditCompletion" name="completion_pct" min="0" max="100" step="1" required>

                    <label for="gpmEditStatus">Status</label>
                    <select id="gpmEditStatus" name="status" required>
                        <?php foreach (grantMilestoneStatusOptions() as $option): ?>
                        <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="gpmEditRemarks">Remarks</label>
                    <textarea id="gpmEditRemarks" name="remarks" rows="3" placeholder="Progress notes, issues, or follow-up actions…"></textarea>

                    <label for="gpmEditDocument">Supporting Document</label>
                    <input type="file" id="gpmEditDocument" name="supporting_doc" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <p class="gpm-field-hint">Accepted: PDF, Word, JPG, PNG. Upload saves with this milestone update.</p>
                </div>
                <div class="gpm-dialog-actions">
                    <button type="button" class="gpm-btn gpm-btn-ghost" id="gpmEditCancelBtn">Cancel</button>
                    <button type="submit" class="gpm-btn gpm-btn-primary" id="gpmEditSaveBtn">
                        <?= smsIcon('save') ?> Save Milestone
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="<?= BASE_URL ?>/assets/js/grant-milestones-live.js?v=5"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
