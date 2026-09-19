<?php
/**
 * SMS 2 - Financial & Tracking · Budget & Disbursement
 * CRAD Staff records fund releases for APPROVED & FUNDED proposals (monitoring only).
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once __DIR__ . '/../includes/grant-funding-helpers.php';

requireAuth();
grantRequireFundingViewAccess();

$pageTitle            = 'Budget & Disbursement';
$activeModule         = grantActiveModuleKey();
$activePage           = 'budget-disbursement';
$hideModulePageBanner = true;
$canRelease           = grantUserCanReleaseFunds();

$breadcrumbs = [
    ['label' => grantBreadcrumbModuleLabel(), 'url' => grantBreadcrumbModuleUrl()],
    ['label' => 'Financial & Tracking', 'url' => grantBudgetDisbursementUrl()],
    ['label' => 'Budget & Disbursement', 'url' => null],
];

require_once ROOT_PATH . '/includes/breadcrumbs.php';

$crad       = cradDb();
$overview   = [];
$selectedId = (int) ($_GET['id'] ?? 0);
$detail     = null;
$dbError    = '';

if ($crad) {
    try {
        grantBackfillFundingDisbursementPlans($crad);
        $overview = grantGetFundedDisbursementOverview($crad);
        if ($selectedId <= 0 && $overview !== []) {
            $selectedId = (int) ($overview[0]['grant_application_id'] ?? 0);
        }
        if ($selectedId > 0) {
            $detail = grantGetFundingDisbursementDetail($crad, $selectedId);
        }
    } catch (Throwable $e) {
        $dbError = htmlspecialchars($e->getMessage());
        error_log('budget-disbursement: ' . $e->getMessage());
    }
} else {
    $dbError = 'CRAD database connection unavailable.';
}

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<link href="<?= BASE_URL ?>/assets/css/grant-funding-disbursement.css?v=3" rel="stylesheet">

<?php if ($dbError !== ''): ?>
<div class="gfd-alert" role="alert"><?= smsIcon('exclamation-triangle', ['class' => 'me-1']) ?><?= $dbError ?></div>
<?php endif; ?>

<div class="gfd"
     data-grant-funding-live="1"
     data-selected-id="<?= (int) $selectedId ?>"
     data-can-release="<?= $canRelease ? '1' : '0' ?>"
     data-proposal-ref="<?= htmlspecialchars((string) ($detail['application']['proposal_reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <header class="gfd-page-header">
        <div class="gfd-page-header-main">
            <h1>
                Budget &amp; Disbursement
                <span class="gfd-live-badge"><?= smsIcon('sync-alt') ?> Live</span>
            </h1>
            <p>Record fund releases for <strong>APPROVED &amp; FUNDED</strong> proposals. This system logs disbursement only — it does not transfer actual funds.</p>
        </div>
        <?php if ($canRelease): ?>
        <div class="gfd-stat-row" id="gfdStatRow">
            <span class="gfd-stat"><strong data-gfd-funded-count><?= count($overview) ?></strong> funded</span>
            <span class="gfd-stat pending"><strong data-gfd-pending-count><?= count(array_filter($overview, static fn(array $r): bool => (int) ($r['pending_count'] ?? 0) > 0)) ?></strong> pending release</span>
        </div>
        <?php endif; ?>
    </header>

    <?php if ($overview === []): ?>
        <div class="gfd-panel">
            <div class="gfd-empty">
                <?= smsIcon('wallet') ?>
                <p style="margin:0;font-weight:700;">No approved &amp; funded proposals yet.</p>
                <p style="margin:.35rem 0 0;font-size:.84rem;color:#64748b;">Proposals appear here in real time after Finance completes the final sign-off (Level 6).</p>
                <a class="gfd-btn gfd-btn-ghost" href="<?= htmlspecialchars(grantApprovedFundedUrl()) ?>" style="margin-top:1rem;display:inline-flex;">
                    <?= smsIcon('check-circle') ?> View Approved &amp; Funded
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="gfd-project-bar">
            <label for="gfdProjectSelect">Select Funded Project:</label>
            <select id="gfdProjectSelect" class="gfd-project-select" aria-label="Select funded project">
                <?php foreach ($overview as $row): ?>
                    <?php
                    $appId = (int) ($row['grant_application_id'] ?? 0);
                    $ref   = (string) ($row['proposal_reference'] ?? 'Proposal');
                    $title = (string) ($row['research_title'] ?? 'Untitled');
                    $statusLabel = (string) ($row['funding_status_label'] ?? '');
                    ?>
                    <option value="<?= $appId ?>"<?= $appId === $selectedId ? ' selected' : '' ?>>
                        <?= htmlspecialchars($ref . ': ' . $title . ' — ' . $statusLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="gfd-panel" id="gfdDetailPanel">
            <?php if ($detail === null): ?>
                <div class="gfd-empty">
                    <?= smsIcon('tasks') ?>
                    <p style="margin:0;">Select a funded project to view disbursement tranches.</p>
                </div>
            <?php else: ?>
                <?php
                $app = $detail['application'];
                $tranches = $detail['tranches'] ?? [];
                $ref = htmlspecialchars((string) ($app['proposal_reference'] ?? 'Proposal'));
                $approved = (float) ($detail['approved_budget'] ?? 0);
                ?>
                <h2 class="gfd-panel-title">Release Funds — <?= $ref ?></h2>

                <div class="gfd-summary-grid">
                    <div class="gfd-summary-card">
                        <span>Approved Budget</span>
                        <strong><?= htmlspecialchars(grantFormatPeso($approved)) ?></strong>
                    </div>
                    <div class="gfd-summary-card released">
                        <span>Total Released</span>
                        <strong data-gfd-total-released><?= htmlspecialchars(grantFormatPeso((float) ($detail['total_released'] ?? 0))) ?></strong>
                    </div>
                    <div class="gfd-summary-card pending">
                        <span>Balance Pending</span>
                        <strong data-gfd-balance-pending><?= htmlspecialchars(grantFormatPeso((float) ($detail['balance_pending'] ?? 0))) ?></strong>
                    </div>
                    <div class="gfd-summary-card">
                        <span>Funding Status</span>
                        <strong data-gfd-funding-status><?= htmlspecialchars((string) ($app['funding_status_label'] ?? '')) ?></strong>
                    </div>
                </div>

                <div class="gfd-meta">
                    <span><strong>Grant Program:</strong> <?= htmlspecialchars((string) ($app['funding_title'] ?? '—')) ?></span>
                    <span><strong>Lead Proponent:</strong> <?= htmlspecialchars((string) ($app['applicant_name'] ?? '—')) ?></span>
                </div>

                <h3 class="gfd-section-title"><?= smsIcon('layer-group', ['class' => 'me-1']) ?>Funding Tranches</h3>
                <div class="gfd-tranche-list" id="gfdTrancheList">
                    <?php foreach ($tranches as $tranche):
                        $status = (string) ($tranche['status'] ?? 'Pending');
                        $isReleased = $status === 'Released';
                        $amount = (float) ($tranche['amount_released'] ?? 0);
                        $trancheNum = (int) ($tranche['tranche_number'] ?? 0);
                        $trancheLabel = (string) ($tranche['tranche_label'] ?? ('Tranche ' . $trancheNum));
                    ?>
                    <div class="gfd-tranche-card <?= $isReleased ? 'released' : 'pending' ?>" data-tranche-id="<?= (int) ($tranche['id'] ?? 0) ?>">
                        <div class="gfd-tranche-head">
                            <div>
                                <strong><?= htmlspecialchars($trancheLabel) ?></strong>
                                <div class="gfd-tranche-amount"><?= htmlspecialchars(grantFormatPeso($amount)) ?></div>
                            </div>
                            <span class="gfd-tranche-status <?= $isReleased ? 'is-released' : 'is-pending' ?>">
                                <?= $isReleased ? 'Released' : 'Pending' ?>
                            </span>
                        </div>
                        <?php if ($isReleased): ?>
                        <div class="gfd-tranche-details">
                            <div><span>Release Date</span><strong><?= htmlspecialchars((string) ($tranche['release_date'] ?? '—')) ?></strong></div>
                            <div><span>Reference No.</span><strong><?= htmlspecialchars((string) ($tranche['reference_number'] ?? '—')) ?></strong></div>
                            <div><span>Released By</span><strong><?= htmlspecialchars((string) ($tranche['released_by_name'] ?? '—')) ?></strong></div>
                            <?php if (trim((string) ($tranche['remarks'] ?? '')) !== ''): ?>
                            <div class="gfd-tranche-remarks"><span>Remarks</span><strong><?= nl2br(htmlspecialchars((string) $tranche['remarks'])) ?></strong></div>
                            <?php endif; ?>
                        </div>
                        <?php elseif ($canRelease): ?>
                        <button type="button"
                                class="gfd-btn gfd-btn-release gfdReleaseTrancheBtn"
                                data-disbursement-id="<?= (int) ($tranche['id'] ?? 0) ?>"
                                data-tranche-label="<?= htmlspecialchars($trancheLabel) ?>"
                                data-tranche-number="<?= (int) ($tranche['tranche_number'] ?? 0) ?>"
                                data-default-amount="<?= htmlspecialchars((string) $amount) ?>">
                            <?= smsIcon('money-bill-wave') ?> Record Release
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($canRelease): ?>
<div class="gfd-dialog" id="gfdReleaseDialog" role="dialog" aria-modal="true" aria-labelledby="gfdReleaseTitle">
    <div class="gfd-dialog-box">
        <div class="gfd-dialog-header">
            <h3 id="gfdReleaseTitle"><?= smsIcon('money-bill-wave') ?> Record Fund Release</h3>
            <p class="gfd-dialog-lead" id="gfdReleaseTrancheLabel">Tranche 1</p>
        </div>
        <form id="gfdReleaseForm" class="gfd-form gfd-dialog-form" method="post" data-no-loader>
            <input type="hidden" id="gfdReleaseDisbursementId" name="disbursement_id" value="">
            <div class="gfd-dialog-body">
                <label for="gfdReleaseAmount">Amount Released (₱)</label>
                <input type="number" id="gfdReleaseAmount" name="amount_released" min="0" step="0.01" required>

                <label for="gfdReleaseDate">Release Date</label>
                <input type="date" id="gfdReleaseDate" name="release_date" required>

                <label for="gfdReleaseReference">Reference Number</label>
                <input type="text" id="gfdReleaseReference" name="reference_number" placeholder="Leave blank to auto-generate" autocomplete="off">
                <p class="gfd-field-hint" id="gfdReleaseReferenceHint">Leave blank to auto-generate: <strong id="gfdReleaseReferencePreview">—</strong></p>

                <label for="gfdReleaseRemarks">Remarks</label>
                <textarea id="gfdReleaseRemarks" name="remarks" rows="3" placeholder="Optional notes for audit trail…"></textarea>
            </div>
            <div class="gfd-dialog-actions">
                <button type="button" class="gfd-btn gfd-btn-ghost" id="gfdReleaseCancelBtn">Cancel</button>
                <button type="submit" class="gfd-btn gfd-btn-release" id="gfdReleaseConfirmBtn">
                    <?= smsIcon('check') ?> Confirm Release
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script src="<?= BASE_URL ?>/assets/js/grant-funding-live.js?v=4"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
