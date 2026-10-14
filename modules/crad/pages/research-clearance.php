<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/includes/research-services-clearance.php';

requireAuth();
if (!rscCanManageAsCrad()) {
    http_response_code(403);
    exit('Forbidden');
}

$pageTitle = 'Approve Signed Clearance';
$activeModule = 'crad';
$activePage = 'research-clearance';
$pageBannerIcon = 'fa-stamp';
$pageBannerDescription = 'Open a clearance in the inbox to review the uploaded signed image, then Approve or Reject.';
$breadcrumbs = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Approve Signed Clearance', 'url' => null],
];

$crad = rscDb();
rscEnsureSchema($crad);
$rows = rscListForCrad($crad);
$selectedId = (int) ($_GET['id'] ?? 0);
$current = null;
if ($selectedId > 0) {
    $found = rscRefreshExisting($crad, rscFindById($crad, $selectedId));
    if ($found) {
        $current = $found;
    }
}
$public = $current ? rscPublicRow($current) : null;

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/crad/assets/css/research-clearance.css?v=rsc-flow-8">

<div class="glass-dashboard rsc-print-root"
     data-rsc-live
     data-rsc-role="<?= e(getCurrentUserRoleKey()) ?>"
     data-rsc-endpoint="<?= e(BASE_URL . '/modules/crad/api/research-clearance.php') ?>"
     data-rsc-csrf="<?= e(csrfToken()) ?>"
     data-rsc-id="<?= $public ? (int) $public['id'] : '' ?>">

    <section class="glass-panel p-4 mb-3 rsc-inbox">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="mb-0"><?= smsIcon('inbox', ['class' => 'me-2 text-primary']) ?>Clearance Inbox</h5>
            <small class="text-muted" data-rsc-sync></small>
        </div>
        <div class="table-responsive rsc-inbox-scroll">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Clearance</th>
                        <th>Title</th>
                        <th>O.R. No.</th>
                        <th>Uploaded</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody data-rsc-rows>
                    <?php if (!$rows): ?>
                        <tr><td colspan="7" class="text-muted">No signed clearances waiting for review.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $item):
                            $itemPublic = rscPublicRow($item);
                            $active = $public && (int) $public['id'] === (int) $itemPublic['id'];
                            ?>
                            <tr class="<?= $active ? 'table-active' : '' ?>" data-rsc-open="<?= (int) $itemPublic['id'] ?>">
                                <td><?= e((string) ($itemPublic['leader_group_no'] ?: '—')) ?></td>
                                <td><strong><?= e((string) ($itemPublic['stage_label'] ?? 'Research 1')) ?></strong></td>
                                <td><?= e((string) ($itemPublic['research_title'] ?: '—')) ?></td>
                                <td><?= e((string) ($itemPublic['or_number'] ?: '—')) ?></td>
                                <td><?= e((string) ($itemPublic['uploaded_at_label'] ?? '—')) ?></td>
                                <td><?= e((string) ($itemPublic['status_label'] ?? '')) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-rsc-open="<?= (int) $itemPublic['id'] ?>">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="rsc-empty" data-rsc-empty <?= $rows ? 'hidden' : '' ?>>
        Waiting for a student to upload a signed Research Services Clearance.
    </div>
    <div class="rsc-pick" data-rsc-pick <?= ($rows && !$public) ? '' : 'hidden' ?>>
        Open a row in the inbox to review the uploaded signed clearance.
    </div>

    <div data-rsc-detail <?= $public ? '' : 'hidden' ?>>
        <div class="rsc-toolbar">
            <div>
                <div class="rsc-status" data-rsc-status>
                    <?= e(($public['stage_label'] ?? '') . (!empty($public['status_label']) ? ' — ' . $public['status_label'] : '')) ?>
                </div>
                <small class="text-muted">Group <?= e((string) ($public['leader_group_no'] ?? '—')) ?></small>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-outline-secondary" data-rsc-close><?= smsIcon('arrow-left', ['class' => 'me-1']) ?>Back to Inbox</button>
                <button type="button" class="btn btn-success" data-rsc-approve <?= ($public && !empty($public['can_crad_sign'])) ? '' : 'hidden' ?>><?= smsIcon('check', ['class' => 'me-1']) ?>Approve</button>
                <button type="button" class="btn btn-outline-danger" data-rsc-reject <?= ($public && !empty($public['can_crad_sign'])) ? '' : 'hidden' ?>><?= smsIcon('times', ['class' => 'me-1']) ?>Reject</button>
            </div>
        </div>

        <div class="glass-panel p-3 mb-3" data-rsc-meta-card>
            <div class="row g-3 small">
                <div class="col-md-4">
                    <div class="text-muted">Student / Title</div>
                    <div class="fw-semibold" data-rsc-meta-title><?= e((string) ($public['research_title'] ?? '—')) ?></div>
                </div>
                <div class="col-md-2">
                    <div class="text-muted">O.R. No.</div>
                    <div class="fw-semibold" data-rsc-meta-or><?= e((string) ($public['or_number'] ?? '—')) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">Uploaded</div>
                    <div class="fw-semibold" data-rsc-meta-uploaded><?= e((string) ($public['uploaded_at_label'] ?? '—')) ?></div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted">File</div>
                    <div class="fw-semibold text-truncate" data-rsc-meta-file><?= e((string) ($public['uploaded_original'] ?: '—')) ?></div>
                </div>
            </div>
        </div>

        <div class="alert alert-info" data-rsc-mis-aa-note <?= ($public && !empty($public['has_upload']) && ($public['status'] ?? '') !== 'clearance_done') ? '' : 'hidden' ?>>
            <?= smsIcon('info-circle', ['class' => 'me-2']) ?>
            Review the uploaded signed form below. <strong>Approve</strong> if qualified, or <strong>Reject</strong> so the student can re-upload.
        </div>

        <div class="alert alert-warning" data-rsc-upload-gate <?= ($public && !empty($public['has_upload'])) ? 'hidden' : '' ?>>
            <?= smsIcon('upload', ['class' => 'me-2']) ?>
            No signed image on this clearance yet.
        </div>

        <div class="rsc-wrap" data-rsc-form <?= ($public && !empty($public['has_upload'])) ? '' : 'hidden' ?>></div>
    </div>
</div>
<script src="<?= BASE_URL ?>/modules/crad/assets/js/research-clearance-live.js?v=rsc-flow-8"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
