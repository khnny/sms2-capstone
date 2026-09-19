<?php
/**
 * SMS 2 - Research Analytics & Reporting
 * Module: CRAD
 */
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
requireAuth();
if (!smsRoleAllowedForModule(['crad_officer', 'research_coordinator', 'research_director'], 'crad')) {
    http_response_code(403);
    exit('Forbidden');
}

$crad = cradDb();
$analytics = [
    'total_groups' => 0,
    'approved_titles' => 0,
    'with_advisers' => 0,
    'pre_oral_completed' => 0,
    'final_approved' => 0,
    'for_revision' => 0,
    'active_studies' => 0,
    'approval_rate' => 0,
    'publications' => 0,
    'final_defenses' => 0,
];
$analyticsRows = [];
try {
    $validTitleApprovalSql = cradValidTitleApprovalWhereSql('pub_ta');
    $analytics['total_groups'] = (int) $crad->query("SELECT COUNT(*) FROM research_groups")->fetchColumn();
    $analytics['approved_titles'] = (int) $crad->query("SELECT COUNT(*) FROM title_approvals WHERE status = 'Approved'")->fetchColumn();
    $analytics['with_advisers'] = (int) $crad->query("SELECT COUNT(DISTINCT research_group_id) FROM research_adviser_assignments WHERE assignment_status IN ('Assigned', 'Confirmed') AND research_group_id IS NOT NULL")->fetchColumn();
    $analytics['pre_oral_completed'] = (int) $crad->query("SELECT COUNT(*) FROM research_defense_schedules WHERE defense_type = 'Pre-Oral' AND LOWER(status) IN ('scheduled', 'finalized', 'final', 'completed', 'passed')")->fetchColumn();
    $analytics['final_approved'] = (int) $crad->query("SELECT COUNT(*) FROM final_manuscript_approvals WHERE status = 'Approved'")->fetchColumn();
    $analytics['for_revision'] = (int) $crad->query("SELECT COUNT(*) FROM research_revision_cycles WHERE revision_status IN ('Needs Revision', 'Under Review')")->fetchColumn();
    $analytics['active_studies'] = (int) $crad->query("SELECT COUNT(*) FROM research_groups WHERE status IS NULL OR LOWER(status) NOT IN ('completed', 'archived', 'cancelled')")->fetchColumn();
    $analytics['approval_rate'] = (int) ($crad->query("SELECT COALESCE(ROUND(100 * AVG(result = 'APPROVED')), 0) FROM manuscript_evaluations")->fetchColumn() ?: 0);
    $analytics['publications'] = (int) $crad->query("SELECT COUNT(*) FROM publications p INNER JOIN research_groups pub_rg ON pub_rg.id = p.research_group_id INNER JOIN title_approvals pub_ta ON pub_ta.id = pub_rg.title_approval_id AND {$validTitleApprovalSql} WHERE p.status IN ('For Publication', 'Published', 'Archived')")->fetchColumn();
    $analytics['final_defenses'] = (int) $crad->query("SELECT COUNT(*) FROM research_defense_schedules WHERE defense_type = 'Final Defense' AND LOWER(status) IN ('scheduled', 'finalized', 'final', 'completed', 'passed')")->fetchColumn();
    $analyticsRows = $crad->query(
        "SELECT rg.group_number AS reference, rg.research_title AS title,
                COALESCE(rg.group_name, 'Capstone') AS owner,
                CASE
                    WHEN fma.status = 'Approved' THEN 'Completed'
                    WHEN ms.status = 'Approved' THEN 'Final Manuscript Approved'
                    WHEN rds.id IS NOT NULL THEN 'Final Defense Scheduled'
                    ELSE 'In Progress'
                END AS status,
                COALESCE(fma.updated_at, ms.updated_at, rds.updated_at, rg.updated_at) AS updated
         FROM research_groups rg
         LEFT JOIN final_manuscript_approvals fma ON fma.research_group_id = rg.id
         LEFT JOIN manuscript_submissions ms ON ms.id = (
             SELECT ms2.id FROM manuscript_submissions ms2
             WHERE ms2.research_group_id = rg.id
             ORDER BY ms2.version_number DESC, ms2.id DESC LIMIT 1
         )
         LEFT JOIN research_defense_schedules rds ON rds.id = (
             SELECT rds2.id FROM research_defense_schedules rds2
             WHERE rds2.research_group_id = rg.id AND rds2.defense_type = 'Final Defense'
             ORDER BY rds2.id DESC LIMIT 1
         )
         ORDER BY COALESCE(fma.updated_at, ms.updated_at, rds.updated_at, rg.updated_at) DESC, rg.id DESC
         LIMIT 10"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('CRAD analytics load failed: ' . $e->getMessage());
}

$pageTitle    = 'Research Analytics & Reporting';
$activeModule = 'crad';
$activePage   = 'research-analytics-reporting';
$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Research Analytics & Reporting', 'url' => null],
];

$cradProcess = [
    'kicker' => 'CRAD Officer · Analytics & Reporting Workflow',
    'description' => 'Monitor research productivity, proposal acceptance rates, grants disbursed, defense outcomes, and publication counts across all colleges.',
    'metrics' => [
        ['label' => 'Active Studies', 'value' => (string) $analytics['active_studies'], 'icon' => 'fa-flask', 'tone' => 'blue'],
        ['label' => 'Approval Rate', 'value' => $analytics['approval_rate'] . '%', 'icon' => 'fa-percent', 'tone' => 'green'],
        ['label' => 'Final Defenses', 'value' => (string) $analytics['final_defenses'], 'icon' => 'fa-gavel', 'tone' => 'amber'],
        ['label' => 'Publications', 'value' => (string) $analytics['publications'], 'icon' => 'fa-book-open', 'tone' => 'purple'],
    ],
    'steps' => [
        ['Define Report Scope', 'Select the time period, college, and research metric for the report.'],
        ['Gather Research Data', 'Pull proposal, adviser, grant, defense, and publication records from each workflow.'],
        ['Analyze Performance', 'Compute acceptance rates, pass rates, grant utilization, and productivity trends.'],
        ['Generate & Export Report', 'Produce the institutional research analytics report and export for stakeholders.'],
    ],
    'columns' => ['Reference', 'Research Metric', 'College / Office', 'Status', 'Updated'],
    'fields' => ['reference', 'title', 'owner', 'status', 'updated'],
    'records' => array_map(static function (array $row): array {
        return [
            'reference' => (string) ($row['reference'] ?? ''),
            'title' => (string) ($row['title'] ?? 'Research Group'),
            'owner' => (string) ($row['owner'] ?? 'Capstone'),
            'detail' => 'Live CRAD workflow record',
            'status' => (string) ($row['status'] ?? 'In Progress'),
            'status_class' => strtolower((string) ($row['status'] ?? 'pending')),
            'updated' => (string) ($row['updated'] ?? ''),
        ];
    }, $analyticsRows),
    'actions' => [
        ['label' => 'Generate Report', 'process' => 'report', 'icon' => 'fa-file-export', 'class' => 'primary'],
        ['label' => 'Filter by College', 'process' => 'validate', 'icon' => 'fa-filter', 'class' => 'ghost'],
        ['label' => 'Export Dashboard', 'process' => 'approve', 'icon' => 'fa-download', 'class' => 'ghost'],
        ['label' => 'View Historical Trends', 'process' => 'view', 'icon' => 'fa-chart-line', 'class' => 'ghost'],
    ],
    'form' => [
        ['label' => 'Report Reference', 'type' => 'text', 'name' => 'reference', 'placeholder' => 'RAN-2026-00X'],
        ['label' => 'Report Title', 'type' => 'text', 'name' => 'title', 'placeholder' => 'Research performance report'],
        ['label' => 'College / Office', 'type' => 'select', 'name' => 'college', 'options' => [
            'All Colleges',
            'College of Computer Studies',
            'College of Business Administration',
            'College of Criminology',
            'College of Education',
            'College of Hospitality & Tourism Management',
        ]],
        ['label' => 'Time Period', 'type' => 'select', 'name' => 'period', 'options' => [
            'This Term',
            'This Academic Year',
            'Last 12 Months',
            'Custom Range',
        ]],
        ['label' => 'Report Type', 'type' => 'select', 'name' => 'report_type', 'options' => [
            'Proposal Analytics',
            'Adviser & Panel Report',
            'Grants & Funding Report',
            'Defense Outcomes Report',
            'Publication & Repository Report',
        ]],
        ['label' => 'Report Notes', 'type' => 'textarea', 'name' => 'notes', 'placeholder' => 'Trend highlights, anomalies, recommendations...'],
    ],
    'notice' => 'Research analytics reports are generated from live workflow data. Reports should be reviewed by the CRAD Director before release to college deans or external partners.',
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>
<link href="<?= BASE_URL ?>/assets/css/reports-analytics.css" rel="stylesheet">
<?php
$step20Dashboard = [
    'donut' => [
        'labels' => ['Approved Titles', 'With Advisers', 'Pre-Oral Completed', 'Final Defense Completed', 'Final Approved', 'For Revision', 'Published'],
        'values' => [$analytics['approved_titles'], $analytics['with_advisers'], $analytics['pre_oral_completed'], $analytics['final_defenses'], $analytics['final_approved'], $analytics['for_revision'], $analytics['publications']],
        'colors' => ['#22c55e', '#3b82f6', '#f59e0b', '#0ea5e9', '#14b8a6', '#ef4444', '#8b5cf6'],
    ],
    'bar' => [
        'labels' => ['Groups', 'Titles', 'Advisers', 'Pre-Oral', 'Final Defense', 'Final Approved', 'Published'],
        'values' => [$analytics['total_groups'], $analytics['approved_titles'], $analytics['with_advisers'], $analytics['pre_oral_completed'], $analytics['final_defenses'], $analytics['final_approved'], $analytics['publications']],
    ],
];
?>
<section class="ra-dash mb-4" id="cradStep20Charts" data-dashboard="<?= htmlspecialchars(json_encode($step20Dashboard), ENT_QUOTES, 'UTF-8') ?>">
    <header class="ra-dash-head"><div><h2>Capstone Analytics</h2><p>Live summary of groups, approvals, defenses, completion, revisions, and publications.</p></div></header>
    <div class="ra-chart-grid">
        <article class="ra-card"><h2>Capstone Status Distribution</h2><div class="ra-donut-wrap"><div class="ra-chart-box"><canvas id="cradStep20Donut" aria-label="Capstone status distribution chart"></canvas></div><ul class="ra-legend">
            <?php foreach ($step20Dashboard['donut']['labels'] as $index => $label): ?><li><span class="label"><span class="dot" style="background:<?= e($step20Dashboard['donut']['colors'][$index]) ?>"></span><?= e($label) ?></span><strong><?= (int) $step20Dashboard['donut']['values'][$index] ?></strong></li><?php endforeach; ?>
        </ul></div></article>
        <article class="ra-card"><h2>Capstone Process Counts</h2><div class="ra-chart-box"><canvas id="cradStep20Bar" aria-label="Capstone process counts chart"></canvas></div></article>
    </div>
</section>
<script src="<?= BASE_URL ?>/assets/vendor/chartjs/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('cradStep20Charts');
    if (!root || typeof Chart === 'undefined') return;
    var data = JSON.parse(root.getAttribute('data-dashboard') || '{}');
    var options = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
    new Chart(document.getElementById('cradStep20Donut'), { type: 'doughnut', data: { labels: data.donut.labels, datasets: [{ data: data.donut.values, backgroundColor: data.donut.colors, borderWidth: 0 }] }, options: Object.assign({}, options, { cutout: '68%' }) });
    new Chart(document.getElementById('cradStep20Bar'), { type: 'bar', data: { labels: data.bar.labels, datasets: [{ data: data.bar.values, backgroundColor: '#2563eb', borderRadius: 7, borderSkipped: false, maxBarThickness: 38 }] }, options: Object.assign({}, options, { scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } }) });
});
</script>
<?php require_once ROOT_PATH . '/includes/crad-module-process.php'; ?>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
