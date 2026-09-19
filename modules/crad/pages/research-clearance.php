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

$pageTitle = 'Research Services Clearance';
$activeModule = 'crad';
$activePage = 'research-clearance';
$pageBannerIcon = 'fa-stamp';
$pageBannerDescription = 'Upload the printed clearance after Adviser, MIS, and AA have signed, then sign as CRAD.';
$breadcrumbs = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Research Services Clearance', 'url' => null],
];

$crad = rscDb();
rscEnsureSchema($crad);
$rows = rscListForCrad($crad);
$selectedId = (int) ($_GET['id'] ?? 0);
$current = $selectedId > 0 ? rscRefreshExisting($crad, rscFindById($crad, $selectedId)) : ($rows[0] ?? null);
$public = $current ? rscPublicRow($current) : null;
$rscSigPadLabel = 'CRAD Signature Pad (Draw Below)';

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/crad/assets/css/research-clearance.css?v=rsc-sig-ink-2">

<div class="glass-dashboard rsc-print-root"
     data-rsc-live
     data-rsc-role="<?= e(getCurrentUserRoleKey()) ?>"
     data-rsc-endpoint="<?= e(BASE_URL . '/modules/crad/api/research-clearance.php') ?>"
     data-rsc-csrf="<?= e(csrfToken()) ?>"
     data-rsc-id="<?= $public ? (int) $public['id'] : '' ?>">
    <div class="rsc-empty" data-rsc-empty <?= $rows ? 'hidden' : '' ?>>Waiting for an adviser-signed Research Services Clearance.</div>

    <div data-rsc-detail <?= $public ? '' : 'hidden' ?>>
        <div class="rsc-toolbar">
            <div>
                <div class="rsc-status" data-rsc-status><?= e($public['status_label'] ?? '') ?></div>
                <small class="text-muted" data-rsc-sync></small>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php if (count($rows) > 1): ?>
                    <select class="form-select form-select-sm" style="max-width:280px;" data-rsc-group>
                        <?php foreach ($rows as $item): $itemPublic = rscPublicRow($item); ?>
                            <option value="<?= (int) $itemPublic['id'] ?>"<?= $public && (int) $public['id'] === (int) $itemPublic['id'] ? ' selected' : '' ?>>
                                <?= e($itemPublic['leader_group_no'] ?: ('#' . $itemPublic['id'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <input type="file" id="rscClearanceFile" class="form-control form-control-sm" style="max-width:260px;" data-rsc-file accept=".png,.jpg,.jpeg,image/png,image/jpeg">
                <label for="rscClearanceFile" class="btn btn-outline-primary mb-0" data-rsc-accept <?= ($public && in_array($public['status'], ['adviser_signed', 'crad_received', 'clearance_done'], true)) ? '' : 'hidden' ?>><?= smsIcon('upload', ['class' => 'me-1']) ?><span data-rsc-upload-label><?= !empty($public['has_upload']) ? 'Re-upload Image' : 'Upload Image' ?></span></label>
                <button type="button" class="btn btn-outline-secondary" data-rsc-print hidden><?= smsIcon('print', ['class' => 'me-1']) ?>Print</button>
                <button type="button" class="btn btn-success" data-rsc-sign hidden><?= smsIcon('signature', ['class' => 'me-1']) ?>Sign Clearance</button>
            </div>
        </div>

        <div class="alert alert-warning" data-rsc-upload-gate>
            <?= smsIcon('upload', ['class' => 'me-2']) ?>
            Upload the printed Research Services Clearance that already has the <strong>Adviser, MIS, and AA</strong> signatures. The form appears only after a valid upload.
        </div>

        <div class="alert alert-info" data-rsc-mis-aa-note hidden>
            <?= smsIcon('info-circle', ['class' => 'me-2']) ?>
            After you upload the printed form with the Adviser, MIS, and AA signatures, you can sign as CRAD.
        </div>

        <div class="rsc-wrap" data-rsc-form hidden></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/research-clearance-sig-modal.php'; ?>
<script src="<?= BASE_URL ?>/modules/crad/assets/js/research-clearance-live.js?v=rsc-stage-2"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
