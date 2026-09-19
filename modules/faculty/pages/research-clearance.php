<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/includes/research-services-clearance.php';

requireAuth();
if (getCurrentUserRoleKey() !== 'adviser') {
    http_response_code(403);
    exit('Forbidden');
}

$pageTitle = 'Research Services Clearance';
$activeModule = 'faculty';
$activePage = 'research-clearance';
$pageBannerIcon = 'fa-stamp';
$pageBannerDescription = 'Review and sign student Research Services Clearance forms in real time.';
$breadcrumbs = [
    ['label' => 'Faculty', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Research Services Clearance', 'url' => null],
];

$crad = rscDb();
rscEnsureSchema($crad);
$rows = rscListForAdviser($crad);
$selectedId = (int) ($_GET['id'] ?? 0);
$current = $selectedId > 0 ? rscRefreshExisting($crad, rscFindById($crad, $selectedId)) : null;
if ($current && !rscAdviserCanAccess($current)) {
    $current = null;
}
$public = $current ? rscPublicRow($current) : null;
$rscSigPadLabel = 'Adviser Signature Pad (Draw Below)';

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/crad/assets/css/research-clearance.css?v=rsc-stage-1">

<div class="glass-dashboard rsc-print-root"
     data-rsc-live
     data-rsc-role="adviser"
     data-rsc-endpoint="<?= e(BASE_URL . '/modules/crad/api/research-clearance.php') ?>"
     data-rsc-csrf="<?= e(csrfToken()) ?>"
     data-rsc-id="<?= $public ? (int) $public['id'] : '' ?>">
    <section class="glass-panel p-4 mb-3 rsc-inbox">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><?= smsIcon('inbox', ['class' => 'me-2 text-primary']) ?>Clearance Inbox</h5>
            <small class="text-muted" data-rsc-sync></small>
        </div>
        <div class="table-responsive rsc-inbox-scroll">
            <table class="table align-middle mb-0">
                <thead><tr><th>Group</th><th>Clearance</th><th>Title</th><th>O.R. No.</th><th>Status</th><th></th></tr></thead>
                <tbody data-rsc-rows>
                    <?php if (!$rows): ?>
                        <tr><td colspan="6" class="text-muted">No clearance forms yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $item): $itemPublic = rscPublicRow($item); ?>
                            <tr<?= $public && (int) $public['id'] === (int) $itemPublic['id'] ? ' class="table-active"' : '' ?> data-rsc-open="<?= (int) $itemPublic['id'] ?>">
                                <td><?= e($itemPublic['leader_group_no']) ?></td>
                                <td><strong><?= e($itemPublic['stage_label'] ?? 'Research 1') ?></strong></td>
                                <td><?= e($itemPublic['research_title']) ?></td>
                                <td><?= e($itemPublic['or_number']) ?></td>
                                <td><?= e($itemPublic['status_label']) ?></td>
                                <td><button type="button" class="btn btn-sm btn-outline-primary" data-rsc-open="<?= (int) $itemPublic['id'] ?>">Open</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="rsc-empty" data-rsc-empty <?= $rows ? 'hidden' : '' ?>>Waiting for a student to send a Research Services Clearance form.</div>
    <div class="rsc-pick" data-rsc-pick <?= ($rows && !$public) ? '' : 'hidden' ?>>Open a row in the inbox to view that student's clearance form.</div>
    <div data-rsc-detail <?= $public ? '' : 'hidden' ?>>
        <div class="rsc-toolbar">
            <div class="rsc-status" data-rsc-status><?= e($public['status_label'] ?? '') ?></div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-outline-secondary" data-rsc-close><?= smsIcon('arrow-left', ['class' => 'me-1']) ?>Back to Inbox</button>
                <button type="button" class="btn btn-outline-secondary" data-rsc-download <?= $public ? '' : 'hidden' ?>><?= smsIcon('download', ['class' => 'me-1']) ?>Download Image</button>
                <button type="button" class="btn btn-success" data-rsc-sign <?= ($public && $public['status'] === 'sent_to_adviser') ? '' : 'hidden' ?>><?= smsIcon('signature', ['class' => 'me-1']) ?>Sign Clearance</button>
            </div>
        </div>
        <div class="rsc-wrap" data-rsc-form><?= $public['form_html'] ?? '' ?></div>
    </div>
</div>
<?php require __DIR__ . '/../../crad/includes/research-clearance-sig-modal.php'; ?>
<script src="<?= BASE_URL ?>/modules/crad/assets/js/research-clearance-live.js?v=rsc-stage-2"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
