<?php
/**
 * SMS 2 - CORE SYSTEM · Proposals & Applications
 * Module: CRAD
 *
 * Lists all grant proposals/applications from grant_applications (crad_db).
 * Displays full BRGFAMS Form 1 fields: research title, college/dept,
 * requested budget, abstract, objectives, document attachments.
 * Status 'Submitted' is shown as 'Pending Evaluation' in this view.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once __DIR__ . '/../includes/grant-helpers.php';
require_once __DIR__ . '/../includes/grant-evaluation-helpers.php';

requireAuth();

grantRequireViewAccess();

$canManage = grantUserCanManage();
$canApply  = grantUserCanApply();

$pageTitle             = 'Proposals & Applications';
$activeModule          = grantActiveModuleKey();
$activePage            = 'proposals-applications';
$pageBannerIcon        = 'fa-file-alt';
$pageBannerDescription = $canManage
    ? 'Review all research grant proposals submitted to published grant opportunities.'
    : 'Track your submitted grant proposals and application status.';

$breadcrumbs = [
    ['label' => grantBreadcrumbModuleLabel(), 'url' => grantBreadcrumbModuleUrl()],
    ['label' => 'Proposals & Applications', 'url' => null],
];

require_once ROOT_PATH . '/includes/breadcrumbs.php';

// ── Data ──────────────────────────────────────────────────────────────────────
$crad         = cradDb();
$applications = [];
$opportunities = [];
$evaluationsByApp = [];
$dbError      = '';

if ($crad) {
    try {
        grantEnsureTables($crad);
        if ($canManage) {
            $applications  = grantGetApplications($crad);
        } else {
            $applications = grantGetMyApplications($crad);
        }
        $opportunities = grantGetOpportunities($crad);
        $feedbackAppIds = array_values(array_map(
            static fn(array $row): int => (int) ($row['id'] ?? 0),
            array_filter(
                $applications,
                static fn(array $row): bool => in_array(
                    (string) ($row['status'] ?? ''),
                    ['Rejected', 'Revision Required'],
                    true
                )
            )
        ));
        if ($feedbackAppIds !== []) {
            $evaluationsByApp = grantGetLatestEvaluationsForApplications($crad, $feedbackAppIds);
        }
    } catch (Throwable $e) {
        $dbError = htmlspecialchars($e->getMessage());
        error_log('proposals-applications: ' . $e->getMessage());
    }
} else {
    $dbError = 'CRAD database connection unavailable.';
}

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);

/**
 * Map the stored DB status to the display label shown in this view.
 * 'Submitted' → 'Pending Evaluation' (process label for the UI).
 */
function paStatusLabel(string $status): string
{
    return match ($status) {
        'Submitted'         => 'Pending Evaluation',
        'Rejected'          => 'REJECTED',
        'Revision Required' => 'REVISION REQUIRED',
        'Resubmitted'       => 'RESUBMITTED',
        'Approved & Funded' => 'APPROVED & FUNDED',
        default             => $status,
    };
}

function paStatusBadge(string $status): string
{
    $label = paStatusLabel($status);
    $map   = [
        'Pending Evaluation' => 'mpl-status pending',
        'Under Review'       => 'mpl-status pending',
        'REJECTED'           => 'mpl-status cancelled',
        'REVISION REQUIRED'  => 'mpl-status processing',
        'RESUBMITTED'        => 'mpl-status pending',
        'Approved'           => 'mpl-status completed',
        'APPROVED & FUNDED'  => 'mpl-status completed',
        'Denied'             => 'mpl-status cancelled',
        'Withdrawn'          => 'mpl-status cancelled',
    ];
    $css = $map[$label] ?? ($map[$status] ?? 'mpl-status processing');
    return '<span class="' . htmlspecialchars($css) . '">' . htmlspecialchars($label) . '</span>';
}
?>
<link href="<?= BASE_URL ?>/assets/css/module-process-list.css?v=2" rel="stylesheet">

<?php if ($dbError !== ''): ?>
    <div class="mpl-alert" role="alert" style="background:rgba(239,68,68,0.08);color:#b91c1c;">
        <?= smsIcon('exclamation-triangle', ['class' => 'me-1']) ?><?= $dbError ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['resubmitted'])): ?>
    <div class="mpl-alert" role="status" style="background:rgba(5,150,105,.08);color:#065f46;margin-bottom:1rem;">
        <?= smsIcon('check-circle', ['class' => 'me-1']) ?>Proposal resubmitted successfully. Status is now <strong>UNDER REVIEW</strong> and returned to the review committee queue.
    </div>
<?php endif; ?>

<div class="mpl" data-mpl data-proposals-page data-grant-live="1">

    <!-- ── Top bar ──────────────────────────────────────────────────────── -->
    <div class="mpl-top">
        <p><?= $canManage
            ? 'All research grant proposals submitted to published grant opportunities. Records are sourced directly from the database.'
            : 'Your submitted grant proposals and their current evaluation status.' ?></p>
        <?php if ($canManage): ?>
        <div class="mpl-toolbar">
            <a class="mpl-btn mpl-btn-soft"
               href="<?= BASE_URL ?>/modules/crad/pages/core-system-dashboard.php">
                <?= smsIcon('chart-pie', ['aria-hidden' => 'true']) ?>Dashboard
            </a>
            <a class="mpl-btn mpl-btn-ghost"
               href="<?= BASE_URL ?>/modules/crad/pages/grant-opportunities.php">
                <?= smsIcon('hand-holding-usd', ['aria-hidden' => 'true']) ?>Grant Opportunities
            </a>
        </div>
        <?php else: ?>
        <div class="mpl-toolbar">
            <a class="mpl-btn mpl-btn-primary"
               href="<?= BASE_URL ?>/modules/crad/pages/grant-opportunities.php">
                <?= smsIcon('hand-holding-usd', ['aria-hidden' => 'true']) ?>Browse Grant Opportunities
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($dbError === ''): ?>

    <!-- ── Stat summary ──────────────────────────────────────────────────── -->
    <?php
    $total       = count($applications);
    $pendingEval = count(array_filter($applications, fn($a) => $a['status'] === 'Submitted'));
    $underReview = count(array_filter($applications, fn($a) => $a['status'] === 'Under Review'));
    $approved    = count(array_filter($applications, fn($a) => $a['status'] === 'Approved'));
    $denied      = count(array_filter($applications, fn($a) => in_array($a['status'], ['Denied', 'Rejected'], true)));
    ?>
    <section class="mpl-stats" aria-label="Proposal summary">
        <article class="mpl-stat">
            <div class="mpl-stat-icon blue"><?= smsIcon('file-alt') ?></div>
            <div><span>Total Proposals</span><strong><?= $total ?></strong></div>
        </article>
        <article class="mpl-stat">
            <div class="mpl-stat-icon amber"><?= smsIcon('hourglass-half') ?></div>
            <div><span>Pending Evaluation</span><strong><?= $pendingEval ?></strong></div>
        </article>
        <article class="mpl-stat">
            <div class="mpl-stat-icon blue"><?= smsIcon('search') ?></div>
            <div><span>Under Review</span><strong><?= $underReview ?></strong></div>
        </article>
        <article class="mpl-stat">
            <div class="mpl-stat-icon green"><?= smsIcon('check-circle') ?></div>
            <div><span>Approved</span><strong><?= $approved ?></strong></div>
        </article>
    </section>

    <!-- ── Filters ────────────────────────────────────────────────────────── -->
    <div class="mpl-filters">
        <label class="mpl-search">
            <?= smsIcon('search') ?>
            <input type="search" id="paSearch"
                   placeholder="Search by proponent, title, college, or grant…"
                   aria-label="Search proposals">
        </label>
        <select id="paStatusFilter" aria-label="Filter by status">
            <option value="">All Status</option>
            <option value="pending evaluation">Pending Evaluation</option>
            <option value="under review">Under Review</option>
            <option value="revision required">REVISION REQUIRED</option>
            <option value="approved">Approved</option>
            <option value="rejected">REJECTED</option>
            <option value="denied">Denied</option>
            <option value="withdrawn">Withdrawn</option>
        </select>
        <?php if (!empty($opportunities)): ?>
            <select id="paOppFilter" aria-label="Filter by grant opportunity">
                <option value="">All Grant Opportunities</option>
                <?php foreach ($opportunities as $opp): ?>
                    <option value="<?= (int) $opp['id'] ?>">
                        <?= htmlspecialchars(mb_strimwidth((string) $opp['funding_title'], 0, 55, '…')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <a class="mpl-btn mpl-btn-ghost mpl-btn-sm" href="?">
            <?= smsIcon('sync-alt', ['aria-hidden' => 'true']) ?> Refresh
        </a>
    </div>

    <!-- ── Proposals table ───────────────────────────────────────────────── -->
    <section class="mpl-panel">
        <div class="mpl-panel-head">
            <div>
                <h2>Research Grant Proposals</h2>
                <p>All proposals submitted via the BRGFAMS Form 1 workflow. Click a row to expand proposal details.</p>
            </div>
        </div>
        <div class="mpl-table-wrap">
            <table class="mpl-table" id="paTable">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Grant Opportunity</th>
                        <th>Lead Proponent</th>
                        <th>Research Project Title</th>
                        <th>College / Dept</th>
                        <th>Requested Budget</th>
                        <th>Date Submitted</th>
                        <th>Status</th>
                        <th style="text-align:center;">Details</th>
                    </tr>
                </thead>
                <tbody id="paTableBody">
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="9" style="text-align:center;color:var(--sms-text-muted);padding:2.5rem;">
                                No proposals have been submitted yet.
                                <?php if (!empty($opportunities)): ?>
                                    <br><a href="<?= BASE_URL ?>/modules/crad/pages/grant-opportunities.php"
                                           style="color:var(--sms-primary);">
                                        Go to Grant Opportunities to submit a proposal.
                                    </a>
                                <?php else: ?>
                                    <br><a href="<?= BASE_URL ?>/modules/crad/pages/grant-opportunities.php"
                                           style="color:var(--sms-primary);">
                                        Publish a grant call first.
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app):
                            $statusLabel  = paStatusLabel((string) $app['status']);
                            $budgetFmt    = $app['requested_budget'] !== null
                                ? '₱' . number_format((float) $app['requested_budget'], 0)
                                : '—';
                            $submittedFmt = date('M d, Y g:i A', strtotime((string) $app['submitted_at']));
                            $hasAbstract  = !empty($app['abstract']);
                            $hasObjectives= !empty($app['objectives']);
                            $hasProposalPdf   = !empty($app['proposal_pdf']);
                            $hasSupportingDoc = !empty($app['supporting_docs']);
                            $hasEthicsDoc     = !empty($app['ethics_doc']);
                            $evaluation       = $evaluationsByApp[(int) $app['id']] ?? null;
                            $searchStr = strtolower(
                                ($app['funding_title']  ?? '') . ' ' .
                                ($app['applicant_name'] ?? '') . ' ' .
                                ($app['research_title'] ?? '') . ' ' .
                                ($app['college_dept']   ?? '') . ' ' .
                                $statusLabel
                            );
                        ?>
                        <tr class="pa-row"
                            data-search="<?= htmlspecialchars($searchStr) ?>"
                            data-status="<?= htmlspecialchars(strtolower($statusLabel)) ?>"
                            data-opp="<?= (int) $app['grant_opportunity_id'] ?>"
                            data-id="<?= (int) $app['id'] ?>">
                            <td style="font-weight:800;color:var(--sms-primary);white-space:nowrap;">
                                <?= htmlspecialchars((string) ($app['proposal_reference'] ?? ('#' . (int) $app['id']))) ?>
                                <div style="font-size:.7rem;font-weight:500;color:var(--sms-text-muted);">
                                    v<?= max(1, (int) ($app['current_version'] ?? 1)) ?>
                                </div>
                            </td>
                            <td style="font-weight:600;max-width:200px;">
                                <?= htmlspecialchars((string) $app['funding_title']) ?>
                            </td>
                            <td>
                                <div style="font-weight:600;"><?= htmlspecialchars((string) $app['applicant_name']) ?></div>
                                <?php if (!empty($app['group_number'])): ?>
                                    <div style="font-size:.75rem;color:var(--sms-text-muted);">
                                        <?= htmlspecialchars((string) $app['group_number']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.86rem;max-width:220px;">
                                <?= !empty($app['research_title'])
                                    ? htmlspecialchars((string) $app['research_title'])
                                    : '<span style="color:var(--sms-text-muted)">—</span>' ?>
                            </td>
                            <td style="font-size:.84rem;">
                                <?= !empty($app['college_dept'])
                                    ? htmlspecialchars((string) $app['college_dept'])
                                    : '<span style="color:var(--sms-text-muted)">—</span>' ?>
                            </td>
                            <td style="font-weight:700;white-space:nowrap;">
                                <?= htmlspecialchars($budgetFmt) ?>
                                <?php if (!empty($app['max_funding_cap'])): ?>
                                    <div style="font-size:.7rem;color:var(--sms-text-muted);font-weight:400;">
                                        of ₱<?= number_format((float) $app['max_funding_cap'], 0) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.83rem;white-space:nowrap;">
                                <?= htmlspecialchars($submittedFmt) ?>
                            </td>
                            <td><?= paStatusBadge((string) $app['status']) ?>
                                <?php if (!$canManage && (string) $app['status'] === 'Revision Required'): ?>
                                <div style="margin-top:.35rem;">
                                    <a href="<?= htmlspecialchars(grantReviseProposalUrl((int) $app['id'])) ?>"
                                       class="mpl-btn mpl-btn-soft mpl-btn-sm" style="font-size:.7rem;">
                                        <?= smsIcon('edit') ?> Revise
                                    </a>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm pa-detail-btn"
                                        style="font-size:.75rem;padding:.25rem .65rem;border-radius:7px;"
                                        data-app-id="<?= (int) $app['id'] ?>"
                                        title="View proposal details">
                                    <?= smsIcon('eye', ['aria-hidden' => 'true']) ?>
                                </button>
                            </td>
                        </tr>
                        <!-- Expandable detail row -->
                        <tr class="pa-detail-row" id="paDetail-<?= (int) $app['id'] ?>"
                            style="display:none;">
                            <td colspan="9" style="padding:0;">
                                <div style="background:var(--sms-surface-muted,#f8fafc);
                                            border-top:2px solid var(--sms-primary-xlight,#dbeafe);
                                            padding:1.1rem 1.4rem;font-size:.85rem;">
                                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.9rem 1.5rem;">

                                        <?php if ($hasAbstract): ?>
                                        <div style="grid-column:1/-1;">
                                            <div style="font-size:.63rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--sms-text-muted);margin-bottom:.3rem;">Executive Abstract</div>
                                            <div style="color:var(--sms-text);line-height:1.6;white-space:pre-wrap;"><?= htmlspecialchars((string) $app['abstract']) ?></div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($hasObjectives): ?>
                                        <div style="grid-column:1/-1;">
                                            <div style="font-size:.63rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--sms-text-muted);margin-bottom:.3rem;">Objectives</div>
                                            <div style="color:var(--sms-text);line-height:1.6;white-space:pre-wrap;"><?= htmlspecialchars((string) $app['objectives']) ?></div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($hasProposalPdf || $hasSupportingDoc || $hasEthicsDoc): ?>
                                        <div style="grid-column:1/-1;">
                                            <div style="font-size:.63rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--sms-text-muted);margin-bottom:.45rem;">Attached Documents</div>
                                            <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                                                <?php if ($hasProposalPdf): ?>
                                                    <span style="display:inline-flex;align-items:center;gap:.3rem;
                                                                 background:rgba(30,64,175,.08);color:#1e40af;
                                                                 border:1px solid rgba(30,64,175,.18);border-radius:8px;
                                                                 padding:.3rem .7rem;font-size:.75rem;font-weight:600;">
                                                        <?= smsIcon('file-pdf', ['aria-hidden' => 'true']) ?>
                                                        Proposal: <?= htmlspecialchars((string) $app['proposal_pdf_original']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($hasSupportingDoc): ?>
                                                    <span style="display:inline-flex;align-items:center;gap:.3rem;
                                                                 background:rgba(5,150,105,.08);color:#065f46;
                                                                 border:1px solid rgba(5,150,105,.2);border-radius:8px;
                                                                 padding:.3rem .7rem;font-size:.75rem;font-weight:600;">
                                                        <?= smsIcon('paperclip', ['aria-hidden' => 'true']) ?>
                                                        Supporting: <?= htmlspecialchars((string) $app['supporting_docs_original']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($hasEthicsDoc): ?>
                                                    <span style="display:inline-flex;align-items:center;gap:.3rem;
                                                                 background:rgba(109,40,217,.08);color:#6d28d9;
                                                                 border:1px solid rgba(109,40,217,.18);border-radius:8px;
                                                                 padding:.3rem .7rem;font-size:.75rem;font-weight:600;">
                                                        <?= smsIcon('shield-alt', ['aria-hidden' => 'true']) ?>
                                                        Ethics: <?= htmlspecialchars((string) $app['ethics_doc_original']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (!empty($app['application_notes'])): ?>
                                        <div>
                                            <div style="font-size:.63rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--sms-text-muted);margin-bottom:.2rem;">Application Notes</div>
                                            <div style="color:var(--sms-text);line-height:1.5;"><?= htmlspecialchars((string) $app['application_notes']) ?></div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($evaluation && in_array((string) $app['status'], ['Rejected', 'Revision Required'], true)): ?>
                                        <div style="grid-column:1/-1;border-top:1px solid var(--sms-border);padding-top:.85rem;">
                                            <div style="font-size:.63rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--sms-text-muted);margin-bottom:.45rem;">Committee Review Feedback</div>
                                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem 1rem;">
                                                <div>
                                                    <div style="font-size:.72rem;color:var(--sms-text-muted);">Reviewer</div>
                                                    <div style="font-weight:700;"><?= htmlspecialchars((string) ($evaluation['evaluator_name'] ?? 'Review Committee')) ?></div>
                                                </div>
                                                <div>
                                                    <div style="font-size:.72rem;color:var(--sms-text-muted);">Score</div>
                                                    <div style="font-weight:700;"><?= number_format((float) ($evaluation['total_score'] ?? 0), 1) ?> / 100</div>
                                                </div>
                                                <div>
                                                    <div style="font-size:.72rem;color:var(--sms-text-muted);">Decision</div>
                                                    <div style="font-weight:700;"><?= htmlspecialchars(grantRecommendationLabel((string) ($evaluation['recommendation'] ?? ''))) ?></div>
                                                </div>
                                                <div>
                                                    <div style="font-size:.72rem;color:var(--sms-text-muted);">Reviewed On</div>
                                                    <div style="font-weight:700;"><?= htmlspecialchars(date('M d, Y g:i A', strtotime((string) ($evaluation['submitted_at'] ?? 'now')))) ?></div>
                                                </div>
                                            </div>
                                            <?php if (!empty($evaluation['comments'])): ?>
                                            <div style="margin-top:.75rem;">
                                                <div style="font-size:.72rem;color:var(--sms-text-muted);margin-bottom:.2rem;">Comments</div>
                                                <div style="line-height:1.55;white-space:pre-wrap;"><?= htmlspecialchars((string) $evaluation['comments']) ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($evaluation['revision_reason'])): ?>
                                            <div style="margin-top:.75rem;">
                                                <div style="font-size:.72rem;color:var(--sms-text-muted);margin-bottom:.2rem;">Revision Reason</div>
                                                <div style="line-height:1.55;white-space:pre-wrap;"><?= htmlspecialchars((string) $evaluation['revision_reason']) ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($evaluation['required_corrections'])): ?>
                                            <div style="margin-top:.75rem;">
                                                <div style="font-size:.72rem;color:var(--sms-text-muted);margin-bottom:.2rem;">Required Corrections</div>
                                                <div style="line-height:1.55;white-space:pre-wrap;"><?= htmlspecialchars((string) $evaluation['required_corrections']) ?></div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php endif; // $dbError === '' ?>

</div>

<script>
(function () {
    'use strict';

    /* ── Expand / collapse proposal detail rows ───────────────────────── */
    document.querySelectorAll('.pa-detail-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var appId    = this.dataset.appId;
            var detailRow = document.getElementById('paDetail-' + appId);
            if (!detailRow) return;
            var isOpen = detailRow.style.display !== 'none';
            detailRow.style.display = isOpen ? 'none' : '';
            this.innerHTML = isOpen
                ? '<?= smsIcon('eye', ['aria-hidden' => 'true']) ?>'
                : '<?= smsIcon('eye-slash', ['aria-hidden' => 'true']) ?>';
        });
    });

    /* ── Filter / search ──────────────────────────────────────────────── */
    var searchInput  = document.getElementById('paSearch');
    var statusFilter = document.getElementById('paStatusFilter');
    var oppFilter    = document.getElementById('paOppFilter');
    var tableBody    = document.getElementById('paTableBody');

    function filterTable() {
        var term   = searchInput  ? searchInput.value.toLowerCase().trim()  : '';
        var status = statusFilter ? statusFilter.value.toLowerCase()         : '';
        var opp    = oppFilter    ? oppFilter.value                          : '';

        /* Only filter the main data rows (not detail expansion rows) */
        var dataRows = tableBody.querySelectorAll('tr.pa-row');
        dataRows.forEach(function (row) {
            var matchTerm   = !term   || (row.dataset.search||'').includes(term);
            var matchStatus = !status || (row.dataset.status||'') === status;
            var matchOpp    = !opp    || row.dataset.opp === opp;
            var visible     = matchTerm && matchStatus && matchOpp;
            row.style.display = visible ? '' : 'none';

            /* Also hide the sibling detail row when the data row is hidden */
            var detailRow = document.getElementById('paDetail-' + (row.dataset.id||''));
            if (detailRow && !visible) detailRow.style.display = 'none';
        });
    }

    if (searchInput)  searchInput.addEventListener('input',  filterTable);
    if (statusFilter) statusFilter.addEventListener('change', filterTable);
    if (oppFilter)    oppFilter.addEventListener('change',    filterTable);
})();
</script>
<script src="<?= BASE_URL ?>/assets/js/grant-opportunities-live.js?v=1"></script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
