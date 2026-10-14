<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/includes/research-services-clearance.php';

requireAuth();
if (!rcpCanApprove()) {
    http_response_code(403);
    exit('Forbidden');
}

$pageTitle = 'Approval Clearance Payment';
$activeModule = 'crad';
$activePage = 'approval-clearance-payment';
$pageBannerIcon = 'fa-file-invoice';
$pageBannerDescription = 'Approve the student collage payment so the O.R. number and remarks appear on the Research Services Clearance form.';
$breadcrumbs = [
    ['label' => 'Admin', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Approval Clearance Payment', 'url' => null],
];

$crad = rscDb();
rscEnsureSchema($crad);
$rows = rcpListForAdmin($crad);

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/crad/assets/css/research-clearance.css?v=rsc-date-1">
<div class="glass-dashboard"
     data-rcp-live
     data-rcp-role="admin"
     data-rcp-endpoint="<?= e(BASE_URL . '/modules/crad/api/clearance-payment.php') ?>"
     data-rcp-csrf="<?= e(csrfToken()) ?>">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="fw-bold">Collage payment approvals</div>
            <small class="text-muted" data-rcp-sync></small>
        </div>
    </div>

    <div class="table-responsive glass-panel mb-3">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Group</th>
                    <th>Clearance</th>
                    <th>Research Title</th>
                    <th>Reference / O.R. No.</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody data-rcp-list>
                <?php if (!$rows): ?>
                    <tr><td colspan="6" class="text-muted">No clearance payment has been sent to admin yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="glass-panel p-4" data-rcp-detail hidden>
        <div class="row g-3">
            <div class="col-lg-7">
                <div data-rcp-preview></div>
            </div>
            <div class="col-lg-5">
                <div class="mb-2 fw-bold" data-rcp-status></div>
                <div class="mb-3" data-rcp-receipt-student-wrap hidden>
                    <label class="form-label">Name read from receipt</label>
                    <input type="text" class="form-control" data-rcp-receipt-student readonly>
                </div>
                <div class="mb-3" data-rcp-member-references-wrap hidden>
                    <label class="form-label">Approved Researchers — Reference / O.R. Numbers</label>
                    <div class="border rounded p-2 bg-light" data-rcp-member-references-admin></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <input type="text" class="form-control" data-rcp-remarks placeholder="HMA" value="HMA">
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-success" data-rcp-approve><?= smsIcon('check', ['class' => 'me-1']) ?>Approve</button>
                    <button type="button" class="btn btn-outline-danger" data-rcp-reject>Return</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>/modules/crad/assets/js/clearance-payment-live.js?v=rcp-clearance-view-unview-1"></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
