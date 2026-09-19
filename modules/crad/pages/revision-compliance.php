<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/audit.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/final-phase-helpers.php';
requireAuth();
if (!smsRoleAllowedForModule(['crad_officer', 'research_coordinator'], 'crad')) { http_response_code(403); exit('Forbidden'); }
$crad = cradDb(); finalPhaseEnsureSchema($crad); $message = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) $error = 'Security check failed.';
    else {
        $groupId = (int) ($_POST['group_id'] ?? 0); $status = (string) ($_POST['revision_status'] ?? '');
        $evidence = $_FILES['revision_evidence'] ?? [];
        if (is_array($evidence) && isset($evidence['error']) && (int) $evidence['error'] !== UPLOAD_ERR_NO_FILE && !fpStoreRevisionEvidence($crad, $groupId, $evidence)) {
            $error = 'Revision evidence upload failed.';
        } elseif (fpSetRevisionStatus($crad, $groupId, $status)) { logActivity('update', 'Updated final defense revision compliance for group #' . $groupId . ' to ' . $status, 'crad'); $message = 'Revision compliance status updated.'; }
        else $error = 'Revision cycle not found or invalid status.';
    }
}
$rows = $crad->query("SELECT rc.*, rg.group_number, rg.research_title,
    (SELECT rpu.update_title FROM research_progress_updates rpu WHERE rpu.research_group_id = rc.research_group_id AND rpu.submitted_at >= rc.opened_at ORDER BY rpu.submitted_at DESC, rpu.id DESC LIMIT 1) AS revision_update_title,
    (SELECT rpu.submitted_at FROM research_progress_updates rpu WHERE rpu.research_group_id = rc.research_group_id AND rpu.submitted_at >= rc.opened_at ORDER BY rpu.submitted_at DESC, rpu.id DESC LIMIT 1) AS revision_update_submitted_at
    FROM research_revision_cycles rc LEFT JOIN research_groups rg ON rg.id = rc.research_group_id ORDER BY rc.updated_at DESC, rc.id DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$breadcrumbs = [['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'], ['label' => 'Revision & Compliance', 'url' => null]];
require_once ROOT_PATH . '/includes/layout-start.php'; renderBreadcrumbs($breadcrumbs);
?>
<div class="glass-dashboard"><div class="glass-board"><div class="glass-panel"><div class="glass-panel-body"><h5 class="glass-panel-title">Final Defense Revision & Compliance</h5><?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if (!$rows): ?><p class="text-muted">No Final Defense revision cycles have been opened.</p><?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Group</th><th>Official Result</th><th>Resubmitted Progress</th><th>Status</th><th>Opened</th><th>Action</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e((string) $row['group_number']) ?><div class="small text-muted"><?= e((string) $row['research_title']) ?></div></td><td><?= e((string) $row['official_result']) ?></td><td><?= $row['revision_update_title'] ? e((string) $row['revision_update_title']) . '<div class="small text-muted">' . e((string) $row['revision_update_submitted_at']) . '</div>' : '<span class="text-muted">No resubmission yet</span>' ?><?php if (!empty($row['original_name'])): ?><div class="small text-muted">Evidence: <?= e((string) $row['original_name']) ?></div><?php endif; ?></td><td><?= e((string) $row['revision_status']) ?></td><td><?= e((string) $row['opened_at']) ?></td><td><form method="post" enctype="multipart/form-data" class="d-flex flex-column gap-2"><?= csrfField() ?><input type="hidden" name="group_id" value="<?= (int) $row['research_group_id'] ?>"><select class="form-select form-select-sm" name="revision_status"><option <?= $row['revision_status'] === 'Needs Revision' ? 'selected' : '' ?>>Needs Revision</option><option <?= $row['revision_status'] === 'Under Review' ? 'selected' : '' ?>>Under Review</option><option <?= $row['revision_status'] === 'Compliant' ? 'selected' : '' ?>>Compliant</option></select><input class="form-control form-control-sm" type="file" name="revision_evidence" accept=".pdf,.doc,.docx"><button class="btn btn-primary btn-sm">Save</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div></div></div>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
