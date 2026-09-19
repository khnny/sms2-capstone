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
$role = getCurrentUserRoleKey();
if (!smsRoleAllowedForModule(['crad_officer', 'research_coordinator'], 'crad')) { http_response_code(403); exit('Forbidden'); }
$crad = cradDb(); finalPhaseEnsureSchema($crad); $message = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) { $error = 'Security check failed. Please refresh and try again.'; }
    else {
        $groupId = (int) ($_POST['group_id'] ?? 0); $remarks = trim((string) ($_POST['remarks'] ?? ''));
        $submission = fpGetLatestManuscriptSubmission($crad, $groupId);
        if (!$submission || !fpIsManuscriptApproved($crad, $groupId)) $error = 'The latest manuscript must be evaluated as approved first.';
        elseif (!fpIsEligibleForFinalApproval($crad, $groupId)) $error = 'Final Defense evaluation or revision compliance is not complete.';
        else {
            $stmt = $crad->prepare("INSERT INTO final_manuscript_approvals (research_group_id, defense_schedule_id, approved_by_user, approved_by_name, status, remarks, approved_at) VALUES (?, ?, ?, ?, 'Approved', ?, NOW()) ON DUPLICATE KEY UPDATE defense_schedule_id = VALUES(defense_schedule_id), approved_by_user = VALUES(approved_by_user), approved_by_name = VALUES(approved_by_name), status = 'Approved', remarks = VALUES(remarks), approved_at = NOW()");
            $stmt->execute([$groupId, (int) (fpGetFinalDefenseSchedule($crad, $groupId)['id'] ?? 0) ?: null, (int) ($_SESSION['user_id'] ?? 0), (string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''), $remarks]);
            fpNotifyFinalManuscriptApproval(
                $crad,
                $submission,
                'Final Manuscript Approved',
                'Your latest final manuscript for ' . ((string) ($submission['research_title'] ?? 'your research group')) . ' has been approved for the next CRAD stage.'
            );
            logActivity('update', 'Approved final manuscript for research group #' . $groupId, 'crad'); $message = 'Final manuscript approval saved.';
        }
    }
}
$rows = $crad->query("SELECT ms.*, rg.group_number, rg.group_name, rg.research_title, fma.status AS approval_status FROM manuscript_submissions ms INNER JOIN research_groups rg ON rg.id = ms.research_group_id INNER JOIN (SELECT research_group_id, MAX(version_number) version_number FROM manuscript_submissions GROUP BY research_group_id) latest ON latest.research_group_id = ms.research_group_id AND latest.version_number = ms.version_number LEFT JOIN final_manuscript_approvals fma ON fma.research_group_id = ms.research_group_id ORDER BY ms.updated_at DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($rows as &$row) {
    $row['manuscript_approved'] = (string) ($row['status'] ?? '') === 'Approved' && fpIsManuscriptApproved($crad, (int) $row['research_group_id']);
    $row['final_approval_eligible'] = $row['manuscript_approved'] && fpIsEligibleForFinalApproval($crad, (int) $row['research_group_id']);
}
unset($row);
$breadcrumbs = [['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'], ['label' => 'Final Manuscript Approval', 'url' => null]];
require_once ROOT_PATH . '/includes/layout-start.php'; renderBreadcrumbs($breadcrumbs);
?>
<div class="glass-dashboard"><div class="glass-board"><div class="glass-panel"><div class="glass-panel-body"><h5 class="glass-panel-title">Final Manuscript Approval</h5><?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if (!$rows): ?><p class="text-muted">No manuscript submissions found.</p><?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Group</th><th>Version</th><th>Manuscript Status</th><th>Approval</th><th>Action</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e((string) $row['group_number']) ?><div class="small text-muted"><?= e((string) $row['research_title']) ?></div></td><td>v<?= (int) $row['version_number'] ?></td><td><?= e((string) $row['status']) ?></td><td><?= e((string) ($row['approval_status'] ?? 'Pending')) ?></td><td><?php if (($row['approval_status'] ?? '') === 'Approved'): ?>Approved<?php elseif (!empty($row['final_approval_eligible'])): ?><form method="post"><?= csrfField() ?><input type="hidden" name="group_id" value="<?= (int) $row['research_group_id'] ?>"><input class="form-control form-control-sm mb-2" name="remarks" placeholder="Approval remarks"><button class="btn btn-success btn-sm">Approve Final Manuscript</button></form><?php elseif (($row['status'] ?? '') !== 'Approved'): ?>Waiting for manuscript evaluation<?php else: ?>Waiting for Final Defense approval or revision compliance<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div></div></div>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
