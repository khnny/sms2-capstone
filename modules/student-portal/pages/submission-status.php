<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/modules/crad/includes/chapter-evaluation-workflow.php';
requireAuth();
$pageTitle = 'Submission Status';
$activeModule = 'student_portal';
$activePage = 'submission-status';
$pageBannerIcon = 'fa-chart-line';
$pageBannerDescription = 'Track the current review status of your submitted research chapters.';
$breadcrumbs = [['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'], ['label' => 'Submission Status', 'url' => null]];
$crad = chapterDb();
$group = chapterRegisteredStudentGroup($crad);
$rows = $group ? chapterLatestSubmissionsForGroup($crad, (int) $group['id']) : [];
$byChapter = [];
foreach ($rows as $row) { $byChapter[(int) $row['chapter_number']] = $row; }
require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<div class="glass-dashboard" data-chapter-live="student" data-live-endpoint="<?= BASE_URL ?>/modules/crad/api/chapter-live.php?mode=student" data-document-base="<?= BASE_URL ?>/modules/crad/api/chapter-document.php?id=" data-latest-update="<?= e($rows ? max(array_map(static fn($r) => (string) ($r['updated_at'] ?? ''), $rows)) : '') ?>" data-registry-available="<?= $group ? '1' : '0' ?>">
    <?php if (!$group): ?>
        <?php chapterRenderUnavailableNotice(); ?>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach (chapterAllowedChapters() as $num => $label): $row = $byChapter[$num] ?? null; ?>
            <div class="col-md-4"><section class="glass-panel p-4 h-100"><h5><?= e($label) ?></h5>
                <?php if (!$row): ?><p class="text-muted mb-0">No submission yet.</p>
                <?php else: ?>
                    <div class="display-6 fw-bold mb-2">V<?= (int) $row['version_number'] ?></div>
                    <span class="badge text-bg-<?= e(chapterStatusClass((string) $row['status'])) ?> mb-3"><?= e($row['status']) ?></span>
                    <div class="small text-muted">Updated <?= e(chapterFormatDate((string) $row['updated_at'])) ?></div>
                    <?php if (!empty($row['evaluation_id'])): ?>
                        <hr><div class="small"><strong>Evaluation Result:</strong> <?= e(ucwords(strtolower((string) $row['result']))) ?></div>
                        <div class="small"><strong>Evaluator:</strong> <?= e($row['evaluator_name']) ?></div>
                        <div class="small"><strong>Date:</strong> <?= e(chapterFormatDate((string) $row['evaluated_at'])) ?></div>
                        <div class="small mt-2">Content (20%): <?= e((string) $row['content_score']) ?> · Methodology (20%): <?= e((string) $row['methodology_score']) ?> · References (20%): <?= e((string) $row['references_score']) ?> · Format (20%): <?= e((string) $row['format_score']) ?> · Grammar (20%): <?= e((string) ($row['grammar_score'] ?? '0')) ?></div>
                        <div class="small fw-bold mt-1">Total: <?= e(number_format((float) ($row['overall_score'] ?? 0), 2)) ?> / 100%</div>
                        <?php if ((string) ($row['overall_feedback'] ?? '') !== ''): ?><div class="alert alert-light mt-3 mb-0"><?= nl2br(e((string) $row['overall_feedback'])) ?></div><?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </section></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script src="<?= BASE_URL ?>/assets/js/chapter-evaluation-live.js"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
