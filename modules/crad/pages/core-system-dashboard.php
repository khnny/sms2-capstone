<?php
/**
 * SMS 2 - CORE SYSTEM · Dashboard & Analytics
 * Module: CRAD
 *
 * Displays real-time summary statistics for the grant management workflow.
 * All counts are queried live from grant_opportunities and grant_applications
 * in crad_db. No mock data is used.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once __DIR__ . '/../includes/grant-helpers.php';

requireAuth();

grantRequireManageAccess();

$pageTitle             = 'Dashboard & Analytics';
$activeModule          = grantActiveModuleKey();
$activePage            = 'core-system-dashboard';
$pageBannerIcon        = 'fa-chart-pie';
$pageBannerDescription = 'Real-time grant management overview — opportunities, applications, and funding status.';

$breadcrumbs = [
    ['label' => 'CRAD',                 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Dashboard & Analytics','url' => null],
];

require_once ROOT_PATH . '/includes/breadcrumbs.php';

// ── Data ──────────────────────────────────────────────────────────────────────
$crad  = cradDb();
$stats = ['total_opportunities' => 0, 'open' => 0, 'closed' => 0, 'expired' => 0,
          'total_applications'  => 0, 'under_review' => 0, 'approved' => 0, 'denied' => 0];
$recentOpportunities = [];
$recentApplications  = [];
$dbError = '';

if ($crad) {
    try {
        grantEnsureTables($crad);
        $stats               = grantDashboardStats($crad);
        $recentOpportunities = array_slice(grantGetOpportunities($crad), 0, 5);
        $recentApplications  = array_slice(grantGetApplications($crad), 0, 5);
    } catch (Throwable $e) {
        $dbError = 'Could not load grant data: ' . htmlspecialchars($e->getMessage());
        error_log('core-system-dashboard: ' . $e->getMessage());
    }
} else {
    $dbError = 'CRAD database connection unavailable.';
}

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);

// ── Status badge helper ───────────────────────────────────────────────────────
function csdStatusBadge(string $status): string
{
    $map = [
        'Open for Application' => 'mpl-status scheduled',
        'Closed'               => 'mpl-status cancelled',
        'Expired'              => 'mpl-status cancelled',
        'Submitted'            => 'mpl-status pending',
        'Under Review'         => 'mpl-status pending',
        'Approved'             => 'mpl-status completed',
        'Denied'               => 'mpl-status cancelled',
        'Withdrawn'            => 'mpl-status cancelled',
    ];
    $cls = $map[$status] ?? 'mpl-status processing';
    return '<span class="' . htmlspecialchars($cls) . '">' . htmlspecialchars($status) . '</span>';
}
?>
<link href="<?= BASE_URL ?>/assets/css/module-process-list.css?v=2" rel="stylesheet">

<?php if ($dbError !== ''): ?>
    <div class="mpl-alert" role="alert" style="background:rgba(239,68,68,0.08);color:#b91c1c;">
        <?= smsIcon('exclamation-triangle', ['class' => 'me-1']) ?><?= $dbError ?>
    </div>
<?php endif; ?>

<div class="mpl" data-mpl data-grant-dashboard>

    <div class="mpl-top">
        <p>Real-time overview of the grant management workflow. All numbers are queried live from the database.</p>
        <div class="mpl-toolbar">
            <a class="mpl-btn mpl-btn-primary"
               href="<?= BASE_URL ?>/modules/crad/pages/grant-opportunities.php">
                <?= smsIcon('hand-holding-usd', ['aria-hidden' => 'true']) ?>Grant Opportunities
            </a>
            <a class="mpl-btn mpl-btn-soft"
               href="<?= BASE_URL ?>/modules/crad/pages/proposals-applications.php">
                <?= smsIcon('file-alt', ['aria-hidden' => 'true']) ?>Proposals &amp; Applications
            </a>
        </div>
    </div>

    <!-- ── Stat cards ──────────────────────────────────────────────────── -->
    <section class="mpl-stats" aria-label="Grant management summary"
             data-grant-stats>
        <article class="mpl-stat" data-stat="total_opportunities">
            <div class="mpl-stat-icon blue"><?= smsIcon('hand-holding-usd') ?></div>
            <div>
                <span>Total Grant Opportunities</span>
                <strong><?= $stats['total_opportunities'] ?></strong>
            </div>
        </article>
        <article class="mpl-stat" data-stat="open">
            <div class="mpl-stat-icon green"><?= smsIcon('door-open') ?></div>
            <div>
                <span>Open for Application</span>
                <strong><?= $stats['open'] ?></strong>
            </div>
        </article>
        <article class="mpl-stat" data-stat="total_applications">
            <div class="mpl-stat-icon amber"><?= smsIcon('file-alt') ?></div>
            <div>
                <span>Total Applications</span>
                <strong><?= $stats['total_applications'] ?></strong>
            </div>
        </article>
        <article class="mpl-stat" data-stat="approved">
            <div class="mpl-stat-icon purple"><?= smsIcon('check-circle') ?></div>
            <div>
                <span>Approved Applications</span>
                <strong><?= $stats['approved'] ?></strong>
            </div>
        </article>
    </section>

    <!-- ── Secondary stat row ──────────────────────────────────────────── -->
    <section class="mpl-stats" aria-label="Secondary grant stats" data-grant-stats-secondary
             style="margin-top:0.75rem;">
        <article class="mpl-stat" data-stat="closed">
            <div class="mpl-stat-icon blue"><?= smsIcon('lock') ?></div>
            <div><span>Closed</span><strong><?= $stats['closed'] ?></strong></div>
        </article>
        <article class="mpl-stat" data-stat="expired">
            <div class="mpl-stat-icon amber"><?= smsIcon('calendar-times') ?></div>
            <div><span>Expired</span><strong><?= $stats['expired'] ?></strong></div>
        </article>
        <article class="mpl-stat" data-stat="under_review">
            <div class="mpl-stat-icon blue"><?= smsIcon('search') ?></div>
            <div><span>Under Review</span><strong><?= $stats['under_review'] ?></strong></div>
        </article>
        <article class="mpl-stat" data-stat="denied">
            <div class="mpl-stat-icon cancelled"><?= smsIcon('ban') ?></div>
            <div><span>Denied</span><strong><?= $stats['denied'] ?></strong></div>
        </article>
    </section>

    <!-- ── Recent Grant Opportunities ─────────────────────────────────── -->
    <section class="mpl-panel" style="margin-top:1.5rem;">
        <div class="mpl-panel-head">
            <div>
                <h2>Recent Grant Opportunities</h2>
                <p>The 5 most recently published grant calls.</p>
            </div>
            <a class="mpl-btn mpl-btn-ghost mpl-btn-sm"
               href="<?= BASE_URL ?>/modules/crad/pages/grant-opportunities.php">
                <?= smsIcon('arrow-right', ['aria-hidden' => 'true']) ?> View All
            </a>
        </div>
        <div class="mpl-table-wrap">
            <table class="mpl-table">
                <thead>
                    <tr>
                        <th>Funding Title</th>
                        <th>Max Funding</th>
                        <th>Eligibility</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Applications</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOpportunities)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--sms-text-muted);padding:1.5rem;">
                                No grant opportunities yet.
                                <a href="<?= BASE_URL ?>/modules/crad/pages/grant-opportunities.php">Publish the first one.</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOpportunities as $opp): ?>
                            <tr>
                                <td style="font-weight:600;">
                                    <?= htmlspecialchars((string) $opp['funding_title']) ?>
                                </td>
                                <td>₱<?= number_format((float) $opp['max_funding_cap'], 2) ?></td>
                                <td><?= htmlspecialchars((string) $opp['eligibility']) ?></td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime((string) $opp['application_deadline']))) ?></td>
                                <td><?= csdStatusBadge((string) $opp['status']) ?></td>
                                <td style="text-align:center;font-weight:700;"><?= (int) $opp['application_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ── Recent Applications ─────────────────────────────────────────── -->
    <section class="mpl-panel" style="margin-top:1.5rem;">
        <div class="mpl-panel-head">
            <div>
                <h2>Recent Applications</h2>
                <p>The 5 most recently submitted grant applications.</p>
            </div>
            <a class="mpl-btn mpl-btn-ghost mpl-btn-sm"
               href="<?= BASE_URL ?>/modules/crad/pages/proposals-applications.php">
                <?= smsIcon('arrow-right', ['aria-hidden' => 'true']) ?> View All
            </a>
        </div>
        <div class="mpl-table-wrap">
            <table class="mpl-table">
                <thead>
                    <tr>
                        <th>Grant Opportunity</th>
                        <th>Applicant / Group</th>
                        <th>Research Title</th>
                        <th>Submitted</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentApplications)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;color:var(--sms-text-muted);padding:1.5rem;">
                                No applications submitted yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentApplications as $app): ?>
                            <tr>
                                <td style="font-weight:600;"><?= htmlspecialchars((string) $app['funding_title']) ?></td>
                                <td><?= htmlspecialchars((string) $app['applicant_name']) ?>
                                    <?php if (!empty($app['group_number'])): ?>
                                        <div style="font-size:0.78rem;color:var(--sms-text-muted);">
                                            <?= htmlspecialchars((string) $app['group_number']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= $app['research_title'] ? htmlspecialchars((string) $app['research_title']) : '<span style="color:var(--sms-text-muted)">—</span>' ?></td>
                                <td><?= htmlspecialchars(date('M d, Y', strtotime((string) $app['submitted_at']))) ?></td>
                                <td><?= csdStatusBadge((string) $app['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>

<script>
/* Live-refresh stats every 30 s without a full page reload */
(function () {
    'use strict';
    var apiBase = '<?= BASE_URL ?>/modules/crad/api/grant-management.php';

    function refreshStats() {
        fetch(apiBase + '?action=get_dashboard_stats', {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) {
            if (!data || !data.success) return;
            var s = data.stats;
            document.querySelectorAll('[data-stat]').forEach(function (el) {
                var key = el.getAttribute('data-stat');
                if (key && s[key] !== undefined) {
                    var strong = el.querySelector('strong');
                    if (strong) strong.textContent = String(s[key]);
                }
            });
        })
        .catch(function () {});
    }

    window.setInterval(refreshStats, 30000);
})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
