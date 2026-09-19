<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/includes/research-services-clearance.php';

requireAuth();
if (getCurrentUserRoleKey() !== 'student') {
    http_response_code(403);
    exit('Forbidden');
}

$pageTitle = 'Research Services Clearance';
$activeModule = 'student_portal';
$activePage = 'research-clearance';
$pageBannerIcon = 'fa-stamp';
$pageBannerDescription = 'Research 1 is Pre-Oral clearance. Research 2 opens after Research 1 is done and its collage payment is approved.';
$breadcrumbs = [
    ['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
    ['label' => 'Research Services Clearance', 'url' => null],
];

$crad = rscDb();
rscEnsureSchema($crad);
$group = chapterRegisteredStudentGroup($crad);
$inbox = $group ? rscStudentInbox($crad, (int) $group['id']) : [];
$selectedId = (int) ($_GET['id'] ?? 0);
$selectedStage = rscNormalizeStage((string) ($_GET['stage'] ?? ''));
$public = null;
foreach ($inbox as $item) {
    if ($selectedId > 0 && (int) ($item['id'] ?? 0) === $selectedId) {
        $public = $item['ready'] ? $item : $item;
        break;
    }
}
if (!$public && $selectedStage !== '') {
    foreach ($inbox as $item) {
        if (($item['research_stage'] ?? '') === $selectedStage) {
            $public = $item;
            break;
        }
    }
}

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/crad/assets/css/research-clearance.css?v=rsc-stage-1">

<div class="glass-dashboard rsc-print-root"
     data-rsc-live
     data-rsc-role="student"
     data-rsc-endpoint="<?= e(BASE_URL . '/modules/crad/api/research-clearance.php') ?>"
     data-rsc-csrf="<?= e(csrfToken()) ?>"
     data-rsc-id="<?= $public && !empty($public['id']) ? (int) $public['id'] : '' ?>"
     data-rsc-stage="<?= e((string) ($public['research_stage'] ?? $selectedStage ?: 'research_1')) ?>">
    <div class="rsc-toolbar">
        <div>
            <div class="rsc-status" data-rsc-status><?= e(($public['stage_label'] ?? 'Research Clearance') . ' — ' . ($public['status_label'] ?? 'Not available yet')) ?></div>
            <small class="text-muted" data-rsc-sync></small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary" data-rsc-print <?= ($public && !empty($public['form_html'])) ? '' : 'hidden' ?>><?= smsIcon('print', ['class' => 'me-1']) ?>Print</button>
            <button type="button" class="btn btn-sms-primary" data-rsc-send <?= ($public && ($public['status'] ?? '') === 'draft') ? '' : 'hidden' ?>><?= smsIcon('paper-plane', ['class' => 'me-1']) ?>Send to Adviser</button>
        </div>
    </div>

    <section class="glass-panel p-4 mb-3">
        <h5 class="mb-3"><?= smsIcon('inbox', ['class' => 'me-2 text-primary']) ?>Clearance Inbox</h5>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Clearance</th>
                        <th>O.R. No.</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody data-rsc-rows>
                    <?php if (!$inbox): ?>
                        <tr><td colspan="4" class="text-muted">No clearance records yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inbox as $item): ?>
                            <tr class="<?= (!empty($public['id']) && (int) $public['id'] === (int) ($item['id'] ?? 0)) || (($public['research_stage'] ?? '') === ($item['research_stage'] ?? '') && empty($public['id'])) ? 'table-active' : '' ?>"
                                data-rsc-open="<?= (int) ($item['id'] ?? 0) ?>"
                                data-rsc-stage="<?= e((string) ($item['research_stage'] ?? 'research_1')) ?>">
                                <td><strong><?= e((string) $item['stage_label']) ?></strong></td>
                                <td><?= e((string) ($item['or_number'] ?: '—')) ?></td>
                                <td><?= e((string) ($item['status_label'] ?: 'Not available yet')) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-rsc-open="<?= (int) ($item['id'] ?? 0) ?>"
                                            data-rsc-stage="<?= e((string) ($item['research_stage'] ?? 'research_1')) ?>">Open</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="alert alert-info" data-rsc-empty <?= ($public && !empty($public['form_html'])) ? 'hidden' : '' ?>>
        <?= smsIcon('info-circle', ['class' => 'me-2']) ?>
        <span data-rsc-empty-text>
            <?php if (!empty($public['locked_reason'])): ?>
                <?= e((string) $public['locked_reason']) ?>
                <?php if (str_contains((string) ($public['locked_reason'] ?? ''), 'payment')): ?>
                    <a href="<?= e(BASE_URL . '/modules/student-portal/pages/college-payment.php?stage=' . urlencode((string) ($public['research_stage'] ?? 'research_1'))) ?>">Upload Collage Payment</a>
                <?php endif; ?>
            <?php else: ?>
                Open a clearance in the inbox. Research 1 is for Pre-Oral. Research 2 opens after Research 1 is finished and its payment is approved.
            <?php endif; ?>
        </span>
    </div>

    <div class="rsc-wrap" data-rsc-form><?= $public['form_html'] ?? '' ?></div>
</div>

<script src="<?= BASE_URL ?>/modules/crad/assets/js/research-clearance-live.js?v=rsc-stage-2"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
