<?php
/**
 * SMS 2 - CORE SYSTEM · Dashboard & Analytics
 * Module: CRAD
 *
 * Real-time grant management analytics for the CRAD Officer.
 * All counts and chart data come from grant_opportunities and
 * grant_applications in crad_db — no hardcoded values.
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
$activePage            = 'dashboard-analytics';
$pageBannerIcon        = 'fa-chart-pie';
$pageBannerDescription = 'Real-time grant management overview — opportunities, applications, and funding status.';

$breadcrumbs = [
    ['label' => 'CRAD',                  'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Dashboard & Analytics', 'url' => null],
];

require_once ROOT_PATH . '/includes/breadcrumbs.php';

// ── Data ──────────────────────────────────────────────────────────────────────
$crad    = cradDb();
$grantDashboardMetrics = grantDashboardMetricsDefaults();
$grantDashboardMetricsHideLink = true;
$stats   = [
    'total_opportunities' => 0, 'open' => 0, 'closed' => 0, 'expired' => 0,
    'total_applications'  => 0, 'under_review' => 0, 'approved' => 0, 'denied' => 0,
    'total_funding_cap'   => 0.0,
];
$statusBreakdown    = [];   // For donut chart:  status => count
$eligibilityBreakdown = []; // For bar chart:   eligibility => count
$recentOpportunities  = [];
$recentApplications   = [];
$dbError = '';

if ($crad) {
    try {
        grantEnsureTables($crad);
        $grantDashboardMetrics = grantGetDashboardMetrics($crad);
        $base = grantDashboardStats($crad);
        $stats = array_merge($stats, $base);

        // ── Total funding cap (sum of all open grants)
        $capStmt = $crad->query(
            "SELECT COALESCE(SUM(max_funding_cap), 0) AS total_cap
               FROM grant_opportunities
              WHERE status = 'Open for Application'"
        );
        $stats['total_funding_cap'] = (float) ($capStmt ? $capStmt->fetchColumn() : 0);

        // ── Application status breakdown for donut chart
        $sdStmt = $crad->query(
            "SELECT status, COUNT(*) AS cnt
               FROM grant_applications
              GROUP BY status
              ORDER BY cnt DESC"
        );
        $statusBreakdown = $sdStmt ? $sdStmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];

        // ── Eligibility breakdown for bar chart (grant opportunities by eligibility)
        $elStmt = $crad->query(
            "SELECT eligibility, COUNT(*) AS cnt
               FROM grant_opportunities
              GROUP BY eligibility
              ORDER BY cnt DESC"
        );
        $eligibilityBreakdown = $elStmt ? $elStmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];

        // ── Recent records for the tables
        $recentOpportunities = array_slice(grantGetOpportunities($crad), 0, 5);
        $recentApplications  = array_slice(grantGetApplications($crad), 0, 5);

    } catch (Throwable $e) {
        $dbError = htmlspecialchars($e->getMessage());
        error_log('dashboard-analytics: ' . $e->getMessage());
    }
} else {
    $dbError = 'CRAD database connection unavailable.';
}

// ── Derive current academic year label from system date ──────────────────────
// Academic year convention: SY YYYY–YYYY  (starts in June)
$nowMonth = (int) date('n');
$nowYear  = (int) date('Y');
$ayStart  = $nowMonth >= 6 ? $nowYear : $nowYear - 1;
$academicYear = 'SY ' . $ayStart . '–' . ($ayStart + 1);

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);

// ── Colour palette for charts (inline-JS safe, no external library needed) ──
$donutColors = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
$barColor    = '#2563eb';

// ── Status badge helper ───────────────────────────────────────────────────────
function daBadge(string $status): string
{
    $m = [
        'Open for Application' => 'mpl-status scheduled',
        'Closed'               => 'mpl-status cancelled',
        'Expired'              => 'mpl-status cancelled',
        'Submitted'            => 'mpl-status pending',
        'Under Review'         => 'mpl-status pending',
        'Approved'             => 'mpl-status completed',
        'Denied'               => 'mpl-status cancelled',
        'Withdrawn'            => 'mpl-status cancelled',
    ];
    return '<span class="' . htmlspecialchars($m[$status] ?? 'mpl-status processing') . '">'
         . htmlspecialchars($status) . '</span>';
}
?>
<link href="<?= BASE_URL ?>/assets/css/module-process-list.css?v=2" rel="stylesheet">

<?php if ($dbError !== ''): ?>
    <div class="mpl-alert" role="alert"
         style="background:rgba(239,68,68,0.08);color:#b91c1c;margin-bottom:1rem;">
        <?= smsIcon('exclamation-triangle', ['class' => 'me-1']) ?><?= $dbError ?>
    </div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     HERO HEADER
     ════════════════════════════════════════════════════════════════════ -->
<div class="da-hero mb-4">
    <div class="da-hero-body">
        <div class="da-hero-text">
            <div class="da-hero-kicker"><?= htmlspecialchars($academicYear) ?></div>
            <h2 class="da-hero-title">BESTLINK Research Funding Dashboard</h2>
            <p class="da-hero-sub">
                Monitor grant applications, research funding allocation, and
                related institutional research activity in real time.
            </p>
        </div>
        <div class="da-hero-actions">
            <a href="<?= BASE_URL ?>/modules/crad/pages/grant-opportunities.php"
               class="mpl-btn mpl-btn-primary">
                <?= smsIcon('plus', ['aria-hidden' => 'true']) ?>Create Grant Call
            </a>
            <a href="<?= BASE_URL ?>/modules/crad/pages/proposals-applications.php"
               class="mpl-btn mpl-btn-soft">
                <?= smsIcon('file-alt', ['aria-hidden' => 'true']) ?>View Proposals
            </a>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════
     GRANT METRICS (real-time, all 11 KPIs)
     ════════════════════════════════════════════════════════════════════ -->
<?php include ROOT_PATH . '/dashboard/partials/grant-dashboard-metrics.php'; ?>

<!-- ══════════════════════════════════════════════════════════════════════
     CHARTS ROW
     ════════════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">

    <!-- ── Grant Opportunities by Eligibility (bar chart) ─────────────── -->
    <div class="col-lg-7">
        <div class="mpl-panel">
            <div class="mpl-panel-head">
                <div>
                    <h2>Grant Calls by Eligibility</h2>
                    <p>Number of published grant opportunities grouped by target eligibility.</p>
                </div>
            </div>
            <?php if (empty($eligibilityBreakdown)): ?>
                <div style="text-align:center;padding:2.5rem 1rem;color:var(--sms-text-muted);">
                    <?= smsIcon('chart-bar', ['style' => 'font-size:2rem;display:block;margin-bottom:.75rem;opacity:.35;']) ?>
                    No grant opportunity data available yet.
                </div>
            <?php else: ?>
                <div style="padding:0.5rem 0.5rem 1rem;">
                    <canvas id="daBarChart" height="220" aria-label="Grant calls by eligibility chart"
                            role="img"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Application Status Breakdown (donut chart) ────────────────── -->
    <div class="col-lg-5">
        <div class="mpl-panel">
            <div class="mpl-panel-head">
                <div>
                    <h2>Application Status Breakdown</h2>
                    <p>Distribution of all submitted applications by current status.</p>
                </div>
            </div>
            <?php if (empty($statusBreakdown)): ?>
                <div style="text-align:center;padding:2.5rem 1rem;color:var(--sms-text-muted);">
                    <?= smsIcon('chart-pie', ['style' => 'font-size:2rem;display:block;margin-bottom:.75rem;opacity:.35;']) ?>
                    No application data available yet.
                </div>
            <?php else: ?>
                <div style="display:flex;justify-content:center;padding:0.5rem 0 1rem;">
                    <canvas id="daDonutChart" width="260" height="260"
                            aria-label="Application status breakdown chart" role="img"></canvas>
                </div>
                <!-- Legend -->
                <div id="daDonutLegend"
                     style="display:flex;flex-wrap:wrap;gap:.55rem .9rem;
                            padding:0 1rem 1rem;justify-content:center;">
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════════════════════════════
     RECENT TABLES
     ════════════════════════════════════════════════════════════════════ -->
<div class="row g-4">

    <!-- Recent Grant Opportunities -->
    <div class="col-lg-6">
        <div class="mpl-panel">
            <div class="mpl-panel-head">
                <div>
                    <h2>Recent Grant Opportunities</h2>
                    <p>5 most recently published grant calls.</p>
                </div>
                <a class="mpl-btn mpl-btn-ghost mpl-btn-sm"
                   href="<?= BASE_URL ?>/modules/crad/pages/grant-opportunities.php">
                    <?= smsIcon('arrow-right', ['aria-hidden' => 'true']) ?>View All
                </a>
            </div>
            <div class="mpl-table-wrap">
                <table class="mpl-table">
                    <thead>
                        <tr>
                            <th>Funding Title</th>
                            <th>Max Cap</th>
                            <th>Deadline</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOpportunities)): ?>
                            <tr><td colspan="4"
                                    style="text-align:center;color:var(--sms-text-muted);padding:1.5rem;">
                                No grant opportunities yet.
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($recentOpportunities as $opp): ?>
                                <tr>
                                    <td style="font-weight:600;max-width:180px;overflow:hidden;
                                               text-overflow:ellipsis;white-space:nowrap;">
                                        <?= htmlspecialchars((string) $opp['funding_title']) ?>
                                    </td>
                                    <td>₱<?= number_format((float) $opp['max_funding_cap'], 0) ?></td>
                                    <td style="white-space:nowrap;font-size:.82rem;">
                                        <?= htmlspecialchars(date('M d, Y',
                                            strtotime((string) $opp['application_deadline']))) ?>
                                    </td>
                                    <td><?= daBadge((string) $opp['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Applications -->
    <div class="col-lg-6">
        <div class="mpl-panel">
            <div class="mpl-panel-head">
                <div>
                    <h2>Recent Applications</h2>
                    <p>5 most recently submitted grant applications.</p>
                </div>
                <a class="mpl-btn mpl-btn-ghost mpl-btn-sm"
                   href="<?= BASE_URL ?>/modules/crad/pages/proposals-applications.php">
                    <?= smsIcon('arrow-right', ['aria-hidden' => 'true']) ?>View All
                </a>
            </div>
            <div class="mpl-table-wrap">
                <table class="mpl-table">
                    <thead>
                        <tr>
                            <th>Grant</th>
                            <th>Applicant</th>
                            <th>Submitted</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentApplications)): ?>
                            <tr><td colspan="4"
                                    style="text-align:center;color:var(--sms-text-muted);padding:1.5rem;">
                                No applications submitted yet.
                            </td></tr>
                        <?php else: ?>
                            <?php foreach ($recentApplications as $app): ?>
                                <tr>
                                    <td style="font-weight:600;max-width:150px;overflow:hidden;
                                               text-overflow:ellipsis;white-space:nowrap;">
                                        <?= htmlspecialchars((string) $app['funding_title']) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars((string) $app['applicant_name']) ?>
                                        <?php if (!empty($app['group_number'])): ?>
                                            <div style="font-size:.75rem;color:var(--sms-text-muted);">
                                                <?= htmlspecialchars((string) $app['group_number']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space:nowrap;font-size:.82rem;">
                                        <?= htmlspecialchars(date('M d, Y',
                                            strtotime((string) $app['submitted_at']))) ?>
                                    </td>
                                    <td><?= daBadge((string) $app['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ══════════════════════════════════════════════════════════════════════
     HERO STYLES  (scoped to this page only)
     ════════════════════════════════════════════════════════════════════ -->
<style>
.da-hero {
    border-radius: 16px;
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);
    padding: 1.75rem 2rem;
    color: #fff;
}
.da-hero-body {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.da-hero-kicker {
    font-size: .72rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .07em;
    opacity: .75;
    margin-bottom: .4rem;
}
.da-hero-title {
    margin: 0 0 .45rem;
    font-size: clamp(1.1rem, 2.5vw, 1.45rem);
    font-weight: 900;
    line-height: 1.2;
}
.da-hero-sub {
    margin: 0;
    font-size: .88rem;
    opacity: .82;
    max-width: 55ch;
    line-height: 1.55;
}
.da-hero-actions {
    display: flex;
    gap: .65rem;
    flex-wrap: wrap;
    align-items: center;
    flex-shrink: 0;
    padding-top: .25rem;
}
.da-hero .mpl-btn-primary {
    background: rgba(255,255,255,.18);
    border: 1.5px solid rgba(255,255,255,.45);
    color: #fff !important;
}
.da-hero .mpl-btn-primary:hover {
    background: rgba(255,255,255,.28);
    color: #fff !important;
}
.da-hero .mpl-btn-soft {
    background: rgba(255,255,255,.10);
    border: 1.5px solid rgba(255,255,255,.25);
    color: #fff !important;
}
.da-hero .mpl-btn-soft:hover {
    background: rgba(255,255,255,.20);
    color: #fff !important;
}
@media (max-width: 767.98px) {
    .da-hero { padding: 1.25rem 1.25rem; }
    .da-hero-body { flex-direction: column; }
    .da-hero-actions { width: 100%; }
    .da-hero-actions .mpl-btn { flex: 1; justify-content: center; }
}
</style>

<!-- ══════════════════════════════════════════════════════════════════════
     CHARTS  (vanilla Canvas — no external library dependency)
     ════════════════════════════════════════════════════════════════════ -->
<?php
// Serialize chart data to JSON safely for inline JS
$barLabels  = array_map('strval', array_keys($eligibilityBreakdown));
$barValues  = array_map('intval', array_values($eligibilityBreakdown));
$donutLabels = array_map('strval', array_keys($statusBreakdown));
$donutValues = array_map('intval', array_values($statusBreakdown));
$donutColorsSlice = array_slice($donutColors, 0, count($donutLabels));
?>
<script>
(function () {
    'use strict';

    /* ── Shared utility ─────────────────────────────────────────────── */
    function roundRect(ctx, x, y, w, h, r) {
        r = Math.min(r, w / 2, h / 2);
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }

    /* ── Bar chart ──────────────────────────────────────────────────── */
    (function () {
        var canvas = document.getElementById('daBarChart');
        if (!canvas) return;

        var labels = <?= json_encode($barLabels) ?>;
        var values = <?= json_encode($barValues) ?>;
        if (!labels.length) return;

        var barColor = '<?= $barColor ?>';
        var dpr      = window.devicePixelRatio || 1;
        var W = canvas.offsetWidth || canvas.parentElement.offsetWidth || 520;
        var H = 220;
        canvas.width  = W * dpr;
        canvas.height = H * dpr;
        canvas.style.width  = W + 'px';
        canvas.style.height = H + 'px';

        var ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);

        var padL = 42, padR = 16, padT = 20, padB = 60;
        var plotW = W - padL - padR;
        var plotH = H - padT - padB;
        var maxVal = Math.max.apply(null, values) || 1;
        var n = labels.length;
        var barW  = Math.floor(plotW / n * 0.55);
        var gap   = plotW / n;

        // Grid lines
        ctx.strokeStyle = getComputedStyle(document.documentElement)
            .getPropertyValue('--sms-border') || '#dbe3f0';
        ctx.lineWidth = 1;
        for (var i = 0; i <= 4; i++) {
            var yLine = padT + plotH - (plotH * i / 4);
            ctx.beginPath();
            ctx.moveTo(padL, yLine);
            ctx.lineTo(padL + plotW, yLine);
            ctx.stroke();
        }

        // Bars
        values.forEach(function (val, idx) {
            var barH = (val / maxVal) * plotH;
            var x = padL + idx * gap + (gap - barW) / 2;
            var y = padT + plotH - barH;

            ctx.fillStyle = barColor;
            ctx.globalAlpha = 0.85;
            roundRect(ctx, x, y, barW, barH, 4);
            ctx.fill();
            ctx.globalAlpha = 1;

            // Value label on top
            ctx.fillStyle = '#1e3a8a';
            ctx.font = 'bold 11px system-ui,sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(String(val), x + barW / 2, y - 5);

            // X-axis label (wrap long labels)
            ctx.fillStyle = '#64748b';
            ctx.font = '10px system-ui,sans-serif';
            var labelText = labels[idx];
            if (labelText.length > 14) {
                labelText = labelText.substring(0, 13) + '…';
            }
            ctx.fillText(labelText, x + barW / 2, padT + plotH + 18);
        });

        // Y-axis labels
        ctx.fillStyle = '#94a3b8';
        ctx.font = '10px system-ui,sans-serif';
        ctx.textAlign = 'right';
        for (var j = 0; j <= 4; j++) {
            var yLbl = padT + plotH - (plotH * j / 4);
            var numVal = Math.round(maxVal * j / 4);
            ctx.fillText(String(numVal), padL - 6, yLbl + 4);
        }
    })();

    /* ── Donut chart ─────────────────────────────────────────────────── */
    (function () {
        var canvas = document.getElementById('daDonutChart');
        if (!canvas) return;

        var labels = <?= json_encode($donutLabels) ?>;
        var values = <?= json_encode($donutValues) ?>;
        var colors = <?= json_encode($donutColorsSlice) ?>;
        if (!labels.length) return;

        var dpr = window.devicePixelRatio || 1;
        var SIZE = 240;
        canvas.width  = SIZE * dpr;
        canvas.height = SIZE * dpr;
        canvas.style.width  = SIZE + 'px';
        canvas.style.height = SIZE + 'px';

        var ctx  = canvas.getContext('2d');
        ctx.scale(dpr, dpr);

        var cx  = SIZE / 2, cy = SIZE / 2, r = 95, inner = 54;
        var total = values.reduce(function (a, b) { return a + b; }, 0) || 1;
        var angle = -Math.PI / 2;

        values.forEach(function (val, idx) {
            var slice = (val / total) * Math.PI * 2;
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, r, angle, angle + slice);
            ctx.closePath();
            ctx.fillStyle = colors[idx] || '#94a3b8';
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.stroke();
            angle += slice;
        });

        // Donut hole
        ctx.beginPath();
        ctx.arc(cx, cy, inner, 0, Math.PI * 2);
        ctx.fillStyle = getComputedStyle(document.documentElement)
            .getPropertyValue('--sms-card-bg') || '#ffffff';
        ctx.fill();

        // Center label
        ctx.fillStyle = '#1e3a8a';
        ctx.font = 'bold 22px system-ui,sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(String(total), cx, cy - 7);
        ctx.font = '11px system-ui,sans-serif';
        ctx.fillStyle = '#94a3b8';
        ctx.fillText('Total', cx, cy + 12);

        // Legend
        var legend = document.getElementById('daDonutLegend');
        if (legend) {
            legend.innerHTML = labels.map(function (lbl, idx) {
                var pct = total > 0 ? Math.round(values[idx] / total * 100) : 0;
                return '<div style="display:flex;align-items:center;gap:.35rem;font-size:.78rem;">'
                     + '<span style="width:10px;height:10px;border-radius:50%;flex-shrink:0;'
                     +        'background:' + (colors[idx] || '#94a3b8') + ';"></span>'
                     + '<span style="color:var(--sms-text-muted);">' + lbl + '</span>'
                     + '<strong style="color:var(--sms-heading);">' + values[idx]
                     + ' (' + pct + '%)</strong>'
                     + '</div>';
            }).join('');
        }
    })();

})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
