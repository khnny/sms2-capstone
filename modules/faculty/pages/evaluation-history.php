<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/modules/crad/includes/chapter-evaluation-workflow.php';

$pageTitle    = 'Evaluation History';
$activeModule = 'faculty';
$activePage   = 'evaluation-history';
$pageBannerIcon = 'fa-history';
$pageBannerDescription = 'View completed chapter evaluations and previous evaluation results.';
$breadcrumbs  = [
    ['label' => 'Grammarian Portal', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Evaluation History', 'url' => null],
];

requireAuth();
if (!chapterIsEvaluator()) {
    http_response_code(403);
    exit('Forbidden');
}
$crad = chapterDb();
$rows = chapterEvaluatorQueue($crad, true);

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="glass-dashboard">
    <section class="glass-panel p-4">
        <h5 class="mb-3"><?= smsIcon('history', ['class' => 'me-2 text-primary']) ?>Evaluation History</h5>
        <?php if (!$rows): ?>
            <div class="text-center text-muted py-5">No completed evaluations available.</div>
        <?php else: ?>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Research Group</th><th>Chapter</th><th>Version</th><th>Evaluation Date</th><th>Content 20%</th><th>Methodology 20%</th><th>References 20%</th><th>Format 20%</th><th>Grammar 20%</th><th>Total 100%</th><th>Result</th><th>Action</th></tr></thead><tbody>
                <?php foreach ($rows as $row): ?><tr><td><strong><?= e($row['group_name']) ?></strong><div class="small text-muted"><?= e($row['research_title']) ?></div></td><td><?= e(chapterLabel((int) $row['chapter_number'])) ?></td><td>Version <?= (int) $row['version_number'] ?></td><td><?= e(chapterFormatDate((string) $row['evaluated_at'])) ?></td><td><?= e((string) $row['content_score']) ?></td><td><?= e((string) $row['methodology_score']) ?></td><td><?= e((string) $row['references_score']) ?></td><td><?= e((string) $row['format_score']) ?></td><td><?= e((string) ($row['grammar_score'] ?? '0')) ?></td><td><strong><?= e(number_format((float) ($row['overall_score'] ?? 0), 2)) ?></strong></td><td><span class="badge text-bg-<?= ((string) $row['result'] === 'APPROVED') ? 'success' : 'warning' ?>"><?= e(ucwords(strtolower((string) $row['result']))) ?></span></td><td><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/modules/faculty/pages/evaluation-scoring.php?id=<?= (int) $row['id'] ?>">Details</a></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
