<?php
/**
 * SMS 2 - Conduct Funded Research (Researcher)
 * View approved grant, budget, timeline, milestones, fund releases, and submit progress evidence.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once __DIR__ . '/../includes/grant-funded-research-helpers.php';

requireAuth();
grantRequireFundedResearchAccess();

$pageTitle            = 'Conduct Funded Research';
$activeModule         = grantActiveModuleKey();
$activePage           = 'funded-research';
$hideModulePageBanner = true;
$canSubmit            = grantUserCanConductFundedResearch();

$breadcrumbs = [
    ['label' => grantBreadcrumbModuleLabel(), 'url' => grantBreadcrumbModuleUrl()],
    ['label' => 'Funded Research', 'url' => grantFundedResearchUrl()],
    ['label' => 'Conduct Funded Research', 'url' => null],
];

require_once ROOT_PATH . '/includes/breadcrumbs.php';

$crad       = cradDb();
$overview   = [];
$selectedId = (int) ($_GET['id'] ?? 0);
$detail     = null;
$dbError    = '';

if ($crad) {
    try {
        $overview = grantGetFundedResearchOverview($crad);
        if ($selectedId <= 0 && $overview !== []) {
            $selectedId = (int) ($overview[0]['grant_application_id'] ?? 0);
        }
        if ($selectedId > 0) {
            $detail = grantGetFundedResearchDetail($crad, $selectedId);
        }
    } catch (Throwable $e) {
        $dbError = htmlspecialchars($e->getMessage());
        error_log('funded-research: ' . $e->getMessage());
    }
} else {
    $dbError = 'CRAD database connection unavailable.';
}

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<link href="<?= BASE_URL ?>/assets/css/grant-funded-research.css?v=2" rel="stylesheet">

<?php if ($dbError !== ''): ?>
<div class="gfr-alert" role="alert"><?= smsIcon('exclamation-triangle', ['class' => 'me-1']) ?><?= $dbError ?></div>
<?php endif; ?>

<div class="gfr"
     data-grant-funded-research-live="1"
     data-selected-id="<?= (int) $selectedId ?>"
     data-can-submit="<?= ($canSubmit && !empty($detail['can_submit_evidence'])) ? '1' : '0' ?>">

    <header class="gfr-page-header">
        <div class="gfr-page-header-main">
            <h1>
                Conduct Funded Research
                <span class="gfr-live-badge"><?= smsIcon('sync-alt') ?> Live</span>
            </h1>
            <p>View your <strong>APPROVED &amp; FUNDED</strong> grant — budget, project timeline, milestones, fund releases, pending requirements — and submit progress evidence.</p>
        </div>
        <?php if ($overview !== []): ?>
        <div class="gfr-stat-row">
            <span class="gfr-stat"><strong data-gfr-funded-count><?= count($overview) ?></strong> funded grant<?= count($overview) === 1 ? '' : 's' ?></span>
            <?php if ($detail !== null): ?>
            <span class="gfr-stat warn"><strong data-gfr-pending-count><?= count($detail['pending_requirements'] ?? []) ?></strong> pending items</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </header>

    <?php if ($overview === []): ?>
        <div class="gfr-panel">
            <div class="gfr-empty">
                <?= smsIcon('flask') ?>
                <p style="margin:0;font-weight:700;">No funded research projects yet.</p>
                <p style="margin:.35rem 0 0;font-size:.84rem;color:#64748b;">Your approved &amp; funded grant will appear here after Finance completes the final sign-off.</p>
                <a class="gfr-btn gfr-btn-ghost" href="<?= BASE_URL ?>/modules/crad/pages/proposals-applications.php" style="margin-top:1rem;display:inline-flex;">
                    <?= smsIcon('file-alt') ?> My Proposals
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="gfr-project-bar">
            <label for="gfrProjectSelect">Select Funded Grant:</label>
            <select id="gfrProjectSelect" class="gfr-project-select" aria-label="Select funded grant">
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

        <div id="gfrDetailPanel">
            <?php if ($detail === null): ?>
                <div class="gfr-panel">
                    <div class="gfr-empty">
                        <?= smsIcon('tasks') ?>
                        <p style="margin:0;">Select a funded grant to view details.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php
                $app = $detail['application'];
                $ref = htmlspecialchars((string) ($app['proposal_reference'] ?? 'Proposal'));
                include __DIR__ . '/partials/funded-research-detail.php';
                ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($canSubmit): ?>
    <div class="gfr-dialog" id="gfrEvidenceDialog" role="dialog" aria-modal="true" aria-labelledby="gfrEvidenceTitle">
        <div class="gfr-dialog-box">
            <div class="gfr-dialog-header">
                <h3 id="gfrEvidenceTitle"><?= smsIcon('file-upload') ?> Submit Progress Evidence</h3>
                <p class="gfr-dialog-lead">Upload supporting documents for an active milestone.</p>
            </div>
            <form id="gfrEvidenceForm" class="gfr-form gfr-dialog-form" method="post" data-no-loader enctype="multipart/form-data">
                <input type="hidden" id="gfrEvidenceApplicationId" name="grant_application_id" value="<?= (int) $selectedId ?>">
                <div class="gfr-dialog-body">
                    <label for="gfrEvidenceMilestone">Milestone</label>
                    <select id="gfrEvidenceMilestone" name="milestone_id" required>
                        <option value="">Select milestone…</option>
                    </select>

                    <label for="gfrEvidenceTitleInput">Evidence Title</label>
                    <input type="text" id="gfrEvidenceTitleInput" name="evidence_title" required placeholder="e.g. Data collection field notes — Week 2">

                    <label for="gfrEvidenceNotes">Notes</label>
                    <textarea id="gfrEvidenceNotes" name="notes" rows="3" placeholder="Brief description of what this evidence covers…"></textarea>

                    <label for="gfrEvidenceFile">Supporting File</label>
                    <input type="file" id="gfrEvidenceFile" name="supporting_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                    <p class="gfr-field-hint">Required. PDF, Word, JPG, or PNG (max 10 MB).</p>
                </div>
                <div class="gfr-dialog-actions">
                    <button type="button" class="gfr-btn gfr-btn-ghost" id="gfrEvidenceCancelBtn">Cancel</button>
                    <button type="submit" class="gfr-btn gfr-btn-primary" id="gfrEvidenceSubmitBtn">
                        <?= smsIcon('upload') ?> Submit Evidence
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="<?= BASE_URL ?>/assets/js/grant-funded-research-live.js?v=4"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
