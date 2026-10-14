<?php
/**
 * SMS 2 – Glass analytics board (staff dashboard)
 * Expects: $roleKey, $statCards, $visibleModules
 */

// Self-contained bootstrap. In the normal dashboard/index.php include flow the
// variables below are already set, so the null-coalescing defaults never
// override them. This only covers direct access to this file.
if (!function_exists('getCurrentUserRoleKey')) {
    require_once __DIR__ . '/../includes/authentication.php';
}
$roleKey        = $roleKey        ?? (function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '');
$visibleModules = $visibleModules ?? [];
$statCards      = $statCards      ?? [];

$perfColors = ['blue', 'purple', 'green', 'orange'];

// Neutral fallbacks. Every value below is overwritten immediately by the
// live provider further down, so nothing mock can reach the page.
$sourceLegend = [];
$donutCenterValue = '0';
$donutCenterLabel = 'Records';
$trendBig = '0';
$trendDelta = 'Live';
$trendDeltaLabel = '';
$trendTitle = '';
$trendSub = '';
$sourceTitle = '';
$sourceSub = '';
$inflow = '0';
$outflow = '0';
$netFlow = '0%';
$tableRows = [];
$progressItems = [];
$activities = [];
$badges = [];
$tableTitle = '';
$tableSub = '';
$progressTitle = '';
$progressSub = '';
$pipelineTitle = '';
$pipelineSub = '';
$pipelineInLabel = '';
$pipelineOutLabel = '';
$pipelineGaugeLabel = '';
$pipelineInflow = 0;
$pipelineOutflow = '0';
$activityTitle = '';
$activitySub = '';
$dashboardIntro = '';
// ── Role board data ────────────────────────────────────────────────────
// Every widget below is driven by live queries through the shared provider.
// The per-role mock blocks that used to sit here have been removed.
require_once ROOT_PATH . '/includes/dashboard-metrics.php';

$codWidgets = smsDashWidgets($roleKey);

$sourceTitle      = (string) $codWidgets['source_title'];
$sourceSub        = (string) $codWidgets['source_sub'];
$sourceLegend     = (array) $codWidgets['legend'];
$donutCenterValue = (string) $codWidgets['donut_total'];
$donutCenterLabel = (string) $codWidgets['donut_label'];

$tableTitle = (string) $codWidgets['table_title'];
$tableSub   = (string) $codWidgets['table_sub'];
$tableRows  = (array) $codWidgets['table_rows'];

$progressTitle = (string) $codWidgets['progress_title'];
$progressSub   = (string) $codWidgets['progress_sub'];
$progressItems = (array) $codWidgets['progress'];

$activityTitle = (string) $codWidgets['activity_title'];
$activitySub   = (string) $codWidgets['activity_sub'];
$activities    = (array) $codWidgets['activity'];

$trendTitle = 'Awaiting action';
$trendSub   = 'Items still open in this workspace';
$trendBig   = '0';
$trendDelta = 'Live';
$trendDeltaLabel = 'open records';
$openCount = 0;
foreach ((array) $codWidgets['stats'] as $codValue) {
    if (is_numeric($codValue) && (float) $codValue > 0) {
        $openCount++;
    }
}
$trendBig = (string) $openCount;

$pipelineTitle    = 'Record coverage';
$pipelineSub      = 'Metrics currently reporting data';
$pipelineInLabel  = 'Reporting';
$pipelineOutLabel = 'No data';
$pipelineInflow   = count((array) $codWidgets['stats']);
$pipelineTotal    = max(1, count((array) $codWidgets['stats']));
$pipelineOutflow  = '0';
$codReportingPct  = (int) round(($pipelineInflow / $pipelineTotal) * 100);
$netFlow          = $codReportingPct . '%';
$pipelineGaugeLabel = 'Reporting';
$inflow           = (string) $pipelineInflow;
$outflow          = (string) $pipelineOutflow;

$badges = [];
foreach (array_slice((array) $codWidgets['stats'], 0, 5, true) as $codKey => $codValue) {
    $badges[] = [
        'icon'  => 'fa-circle',
        'class' => 'b1',
        'label' => (string) $codKey,
        'state' => is_numeric($codValue) ? number_format((float) $codValue, 0) : '—',
    ];
}

$dashboardIntro = 'Live board — every figure queried from the database.';

// Workspace summary — real, human-readable counts.
$codSummary = smsDashSummary($roleKey);

require_once __DIR__ . '/period-filter.php';
$dashboardPeriodKey    = $dashboardPeriodKey ?? smsDashboardCurrentPeriod();
$dashboardPeriods      = $dashboardPeriods ?? smsDashboardPeriods();
$dashboardPeriodMeta   = $dashboardPeriodMeta ?? $dashboardPeriods[$dashboardPeriodKey];
$dashboardPeriodFactor = $dashboardPeriodFactor ?? $dashboardPeriodMeta['factor'];
// No period scaling: every figure is a real count from the database.
?>

<div class="dashboard-shell glass-dashboard academic-dashboard">
    <div class="page-header dashboard-page-header sms-page-header">
        <div>
            <span class="dash-kicker">Dashboard</span>
            <h1>Overview</h1>
            <p>Welcome back, <?= htmlspecialchars(getCurrentUserName()) ?>. <?= htmlspecialchars($dashboardIntro) ?></p>
        </div>
        <div class="dash-period glass-period-filter dropdown">
            <?= smsIcon('calendar-alt', ['aria-hidden' => 'true']) ?>
            <button type="button"
                    class="glass-period-btn dropdown-toggle"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Select reporting period">
                <span><?= htmlspecialchars($dashboardPeriodMeta['sy'] . ' · ' . $dashboardPeriodMeta['label']) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end glass-period-menu">
                <?php foreach ($dashboardPeriods as $periodKey => $periodMeta): ?>
                    <li>
                        <a class="dropdown-item<?= $periodKey === $dashboardPeriodKey ? ' active' : '' ?>"
                           href="<?= htmlspecialchars(smsDashboardPeriodUrl($periodKey)) ?>">
                            <?= htmlspecialchars($periodMeta['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <section class="academic-notices-panel" aria-labelledby="academicNoticesTitle">
        <div class="academic-notices-icon" aria-hidden="true"><?= smsIcon('bullhorn') ?></div>
        <div>
            <span class="ai-insight-kicker">Academic notices</span>
            <h2 class="ai-insight-title" id="academicNoticesTitle">Workspace summary</h2>
            <p class="ai-insight-copy">Important items for <?= htmlspecialchars(getCurrentUserName()) ?> this reporting period.</p>
            <ul class="ai-insight-list">
                <?php foreach ($codSummary ?: ['No live metrics are available for this workspace yet.'] as $summaryLine): ?>
                    <li><?= htmlspecialchars((string) $summaryLine) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <div class="glass-board"
         id="glassBoard"
         data-role="<?= htmlspecialchars($roleKey) ?>"
         data-period="<?= htmlspecialchars($dashboardPeriodKey) ?>"
         data-period-factor="<?= htmlspecialchars((string) $dashboardPeriodFactor) ?>"
         data-crad-donut="<?= htmlspecialchars(json_encode(
             array_map(
                 static fn(array $row): array => [
                     'label' => (string) ($row['label'] ?? ''),
                     'total' => (int) ($row['total'] ?? 0),
                 ],
                 (array) $codWidgets['donut_rows']
             ),
             JSON_THROW_ON_ERROR
         ), ENT_QUOTES, 'UTF-8') ?>">

        <section class="glass-panel">
            <div class="glass-panel-body">
                <div class="glass-panel-head">
                    <div>
                        <h2 class="glass-panel-title">Key metrics</h2>
                        <p class="glass-panel-sub">Summary for your workspace</p>
                    </div>
                    <div class="glass-period-filter dropdown">
                        <button type="button"
                                class="glass-chip glass-chip-btn dropdown-toggle"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="Filter metrics by period">
                            <?= smsIcon('filter', ['aria-hidden' => 'true']) ?>
                            <span><?= htmlspecialchars($dashboardPeriodMeta['label']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end glass-period-menu">
                            <?php foreach ($dashboardPeriods as $periodKey => $periodMeta): ?>
                                <li>
                                    <a class="dropdown-item<?= $periodKey === $dashboardPeriodKey ? ' active' : '' ?>"
                                       href="<?= htmlspecialchars(smsDashboardPeriodUrl($periodKey)) ?>">
                                        <?= htmlspecialchars($periodMeta['label']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div class="perf-grid">
                    <?php foreach ($statCards as $i => $card): ?>
                        <?php
                        $tone = $perfColors[$i % count($perfColors)];
                        $dir = $card['deltaDir'] ?? 'neutral';
                        $arrow = $dir === 'down' ? 'fa-arrow-down' : ($dir === 'up' ? 'fa-arrow-up' : 'fa-minus');
                        ?>
                        <article class="perf-item">
                            <div class="perf-icon <?= $tone ?>"><?= smsIcon($card['icon'], ['aria-hidden' => 'true']) ?></div>
                            <p class="perf-label"><?= htmlspecialchars($card['label']) ?></p>
                            <p class="perf-value"<?php if (!empty($card['metricKey'])): ?> data-gdm-value="<?= htmlspecialchars((string) $card['metricKey']) ?>"<?php elseif (!empty($card['liveKey'])): ?> data-cod-value="<?= htmlspecialchars((string) $card['liveKey']) ?>"<?php endif; ?>><?= htmlspecialchars($card['value']) ?></p>
                            <p class="perf-trend <?= htmlspecialchars($dir) ?>">
                                <?= smsIcon($arrow, ['aria-hidden' => 'true']) ?>
                                <?= htmlspecialchars($card['delta'] ?? '') ?>
                                <span class="perf-delta-label"><?= htmlspecialchars($card['deltaLabel'] ?? '') ?></span>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <?php if (!empty($visibleModules)): ?>
        <section class="glass-panel glass-quick-actions">
            <div class="glass-panel-body">
                <div class="glass-panel-head">
                    <div>
                        <h2 class="glass-panel-title">Quick actions</h2>
                        <p class="glass-panel-sub">Open a module workspace</p>
                    </div>
                </div>
                <div class="row g-3 module-grid">
                    <?php foreach ($visibleModules as $moduleKey => $module): ?>
                        <?php $moduleFolder = $moduleKey === 'student_portal' ? 'student-portal' : $moduleKey; ?>
                        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                            <a href="<?= BASE_URL ?>/modules/<?= htmlspecialchars($moduleFolder) ?>/index.php" class="quick-module">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <?= smsIcon($module['icon'], ['aria-hidden' => 'true']) ?>
                                        <p class="small mb-0 fw-medium"><?= htmlspecialchars($module['label']) ?></p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <div class="glass-row glass-row-bot">
            <section class="glass-panel">
                <div class="glass-panel-body">
                    <div class="glass-panel-head">
                        <div>
                            <h2 class="glass-panel-title"><?= htmlspecialchars($activityTitle) ?></h2>
                            <p class="glass-panel-sub"><?= htmlspecialchars($activitySub) ?></p>
                        </div>
                    </div>
                    <ul class="glass-activity">
                        <?php foreach ($activities as $act): ?>
                            <li>
                                <span class="act-icon <?= htmlspecialchars($act['tone']) ?>"><?= smsIcon($act['icon'], ['aria-hidden' => 'true']) ?></span>
                                <div class="act-body"><strong><?= htmlspecialchars($act['text']) ?></strong></div>
                                <span class="act-time"><?= htmlspecialchars($act['when']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <section class="glass-panel">
                <div class="glass-panel-body">
                    <div class="glass-panel-head">
                        <div>
                            <h2 class="glass-panel-title"><?= htmlspecialchars($sourceTitle) ?></h2>
                            <p class="glass-panel-sub"><?= htmlspecialchars($sourceSub) ?></p>
                        </div>
                    </div>
                    <div class="donut-layout">
                        <div class="donut-wrap">
                            <canvas id="glassDonut" aria-label="Distribution chart"></canvas>
                            <div class="donut-center">
                                <strong><?= htmlspecialchars($donutCenterValue) ?></strong>
                                <span><?= htmlspecialchars($donutCenterLabel) ?></span>
                            </div>
                        </div>
                        <ul class="glass-legend">
                            <?php foreach ($sourceLegend as $item): ?>
                                <li>
                                    <span class="leg-left">
                                        <span class="dot" style="background:<?= htmlspecialchars($item['color']) ?>"></span>
                                        <span class="name"><?= htmlspecialchars($item['label']) ?></span>
                                    </span>
                                    <span class="pct"><?= htmlspecialchars($item['pct']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
