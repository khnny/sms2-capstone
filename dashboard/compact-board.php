<?php
/**
 * SMS 2 - Compact role-aware dashboard board.
 *
 * Expects the live metric cards and visible module list from dashboard/index.php.
 */
$dashboardSummary = array_slice(smsDashSummary($roleKey), 0, 2);
$dashboardActions = array_slice($visibleModules, 0, 3, true);
$dashboardPriorityCards = array_slice($statCards, 0, $roleKey === 'crad_officer' ? 5 : 3);
$showDashboardStatusChart = $roleKey === 'crad_officer';
foreach ($statCards as $cardIndex => $card) {
    if (
        $cardIndex >= count($dashboardPriorityCards)
        && preg_match('/pending|awaiting|approval|revision|open|review/i', (string) ($card['label'] ?? ''))
    ) {
        $dashboardPriorityCards[count($dashboardPriorityCards) - 1] = $card;
        break;
    }
}
$dashboardStatusChart = [];
if ($roleKey === 'crad_officer') {
    $dashboardStatusChart = smsDashGroup(
        smsDashDb(),
        "SELECT COALESCE(NULLIF(TRIM(status),''),'Unspecified') AS label, COUNT(*) AS total
              FROM `crad_research_proposals`
          GROUP BY label
          ORDER BY total DESC
          LIMIT 5"
    );
}
$dashboardStatusChartMax = max(1, ...array_map(static fn(array $row): int => (int) ($row['total'] ?? 0), $dashboardStatusChart ?: [['total' => 1]]));
?>
<div class="dashboard-shell glass-dashboard academic-dashboard">
    <header class="page-header dashboard-page-header sms-page-header">
        <div>
            <span class="dash-kicker">Workspace</span>
            <h1>Dashboard</h1>
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
    </header>

    <div class="glass-board dashboard-compact-board role-<?= htmlspecialchars($roleKey) ?>"
         id="glassBoard"
         data-role="<?= htmlspecialchars($roleKey) ?>"
         data-period="<?= htmlspecialchars($dashboardPeriodKey) ?>"
         data-period-factor="<?= htmlspecialchars((string) $dashboardPeriodFactor) ?>">
        <section class="glass-panel dashboard-metrics-panel" aria-labelledby="dashboardMetricsTitle">
            <div class="glass-panel-body">
                <div class="dashboard-section-heading">
                    <div>
                        <h2 id="dashboardMetricsTitle" class="glass-panel-title">Workspace status</h2>
                        <p class="glass-panel-sub">Live figures for your role</p>
                    </div>
                </div>
                <div class="perf-grid">
                    <?php foreach ($dashboardPriorityCards as $i => $card): ?>
                        <?php
                        $perfColors = ['blue', 'green', 'orange'];
                        $tone = $perfColors[$i % count($perfColors)];
                        $dir = $card['deltaDir'] ?? 'neutral';
                        $arrow = $dir === 'down' ? 'fa-arrow-down' : ($dir === 'up' ? 'fa-arrow-up' : 'fa-minus');
                        ?>
                        <article class="perf-item">
                            <div class="perf-icon <?= $tone ?>"><?= smsIcon($card['icon'], ['aria-hidden' => 'true']) ?></div>
                            <div class="perf-copy">
                                <p class="perf-label"><?= htmlspecialchars($card['label']) ?></p>
                                <p class="perf-value"<?php if (!empty($card['metricKey'])): ?> data-gdm-value="<?= htmlspecialchars((string) $card['metricKey']) ?>"<?php elseif (!empty($card['liveKey'])): ?> data-cod-value="<?= htmlspecialchars((string) $card['liveKey']) ?>"<?php endif; ?>><?= htmlspecialchars($card['value']) ?></p>
                                <p class="perf-trend <?= htmlspecialchars($dir) ?>">
                                    <?= smsIcon($arrow, ['aria-hidden' => 'true']) ?>
                                    <span><?= htmlspecialchars($card['deltaLabel'] ?? 'Live') ?></span>
                                </p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div class="dashboard-compact-grid">
            <section class="glass-panel dashboard-summary-panel" aria-labelledby="dashboardSummaryTitle">
                <div class="glass-panel-body">
                    <div class="dashboard-section-heading">
                        <div>
                            <h2 id="dashboardSummaryTitle" class="glass-panel-title">Current status</h2>
                            <p class="glass-panel-sub">A quick read of your workspace</p>
                        </div>
                    </div>
                    <ul class="dashboard-summary-list">
                        <?php foreach ($dashboardSummary as $summaryLine): ?>
                            <li><?= htmlspecialchars((string) $summaryLine) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <section class="glass-panel dashboard-actions-panel" aria-labelledby="dashboardActionsTitle">
                <div class="glass-panel-body">
                    <div class="dashboard-section-heading">
                        <div>
                            <h2 id="dashboardActionsTitle" class="glass-panel-title">Next actions</h2>
                            <p class="glass-panel-sub">Open a permitted workspace to continue</p>
                        </div>
                    </div>
                    <?php if ($dashboardActions): ?>
                        <ul class="dashboard-action-list">
                            <?php foreach ($dashboardActions as $moduleKey => $module): ?>
                                <?php $moduleFolder = $moduleKey === 'student_portal' ? 'student-portal' : $moduleKey; ?>
                                <li>
                                    <a href="<?= BASE_URL ?>/modules/<?= htmlspecialchars($moduleFolder) ?>/index.php">
                                        <span><?= smsIcon($module['icon'], ['aria-hidden' => 'true']) ?></span>
                                        <strong>Open <?= htmlspecialchars($module['label']) ?></strong>
                                        <?= smsIcon('arrow-right', ['class' => 'dashboard-action-arrow', 'aria-hidden' => 'true']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="dashboard-compact-empty">No workspaces are available for this account.</p>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($showDashboardStatusChart): ?>
                <section class="glass-panel dashboard-status-chart" aria-labelledby="dashboardResearchStatusTitle">
                    <div class="glass-panel-body">
                        <div class="dashboard-section-heading">
                            <div><h2 id="dashboardResearchStatusTitle" class="glass-panel-title">Application status</h2><p class="glass-panel-sub">Live proposal totals by review stage</p></div>
                        </div>
                        <ul>
                            <?php foreach ($dashboardStatusChart as $row): ?>
                                <?php $barWidth = (int) round(((int) $row['total'] / $dashboardStatusChartMax) * 100); ?>
                                <li><div><span><?= htmlspecialchars((string) $row['label']) ?></span><strong><?= number_format((int) $row['total']) ?></strong></div><span class="dashboard-status-track"><span style="width: <?= $barWidth ?>%"></span></span></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!$dashboardStatusChart): ?><p class="dashboard-compact-empty">Application totals will appear here when proposals are submitted.</p><?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>
