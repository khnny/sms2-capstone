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
if (!smsRoleAllowedForModule(['crad_officer', 'research_coordinator', 'adviser'], 'crad')) { http_response_code(403); exit('Forbidden'); }
$crad = cradDb(); finalPhaseEnsureSchema($crad); $message = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) $error = 'Security check failed. Please refresh and try again.';
    else {
        $submissionId = (int) ($_POST['submission_id'] ?? 0); $action = (string) ($_POST['review_action'] ?? '');
        $stmt = $crad->prepare("SELECT ms.*, rg.research_title, rg.group_name, rg.group_number FROM crad_manuscript_submissions ms INNER JOIN crad_research_groups rg ON rg.id = ms.research_group_id WHERE ms.id = ? LIMIT 1"); $stmt->execute([$submissionId]); $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$submission) $error = 'Manuscript submission not found.';
        elseif ($role === 'adviser' && !fpIsAssignedAdviser($crad, (int) $submission['research_group_id'], (int) ($_SESSION['user_id'] ?? 0), (string) ($_SESSION['user_email'] ?? ''))) $error = 'You are not assigned to this research group.';
        elseif (!in_array($action, ['approve','revision'], true)) $error = 'Invalid review action.';
        else {
            $scores = []; foreach (['content','methodology','results','conclusions','recommendations','references','formatting','compliance'] as $key) { $value = (float) ($_POST[$key . '_score'] ?? 0); if ($value < 0 || $value > 100) { $error = 'All scores must be between 0 and 100.'; break; } $scores[$key] = $value; }
            if ($error === '') {
                $result = $action === 'approve' ? 'APPROVED' : 'FOR REVISION'; $status = $action === 'approve' ? 'Approved' : 'For Revision'; $remarks = trim((string) ($_POST['remarks'] ?? '')); $overall = round(array_sum($scores) / 8, 2);
                $crad->beginTransaction();
                try {
                    $crad->prepare('UPDATE crad_manuscript_submissions SET status = ?, reviewed_at = NOW() WHERE id = ?')->execute([$status, $submissionId]);
                    $crad->prepare("INSERT INTO crad_manuscript_evaluations (submission_id, research_group_id, evaluator_user_id, evaluator_name, content_score, methodology_score, results_score, conclusions_score, recommendations_score, references_score, formatting_score, compliance_score, remarks, result, overall_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([$submissionId, $submission['research_group_id'], (int) $_SESSION['user_id'], (string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''), $scores['content'], $scores['methodology'], $scores['results'], $scores['conclusions'], $scores['recommendations'], $scores['references'], $scores['formatting'], $scores['compliance'], $remarks, $result, $overall]);
                    $crad->commit(); logActivity('update', ($action === 'approve' ? 'Approved' : 'Returned') . ' final manuscript submission #' . $submissionId, 'crad'); $message = 'Manuscript review saved.';
                } catch (Throwable $e) { if ($crad->inTransaction()) $crad->rollBack(); error_log('Final manuscript review failed: ' . $e->getMessage()); $error = 'Unable to save manuscript review.'; }
            }
        }
    }
}
$listSql = "SELECT ms.*, rg.group_number, rg.group_name, rg.research_title FROM crad_manuscript_submissions ms INNER JOIN crad_research_groups rg ON rg.id = ms.research_group_id INNER JOIN (SELECT research_group_id, MAX(version_number) version_number FROM crad_manuscript_submissions GROUP BY research_group_id) latest ON latest.research_group_id = ms.research_group_id AND latest.version_number = ms.version_number";
$listParams = [];
if ($role === 'adviser') {
    $listSql .= " WHERE EXISTS (SELECT 1 FROM crad_research_adviser_assignments raa WHERE raa.research_group_id = ms.research_group_id AND raa.assignment_status IN ('Assigned', 'Confirmed') AND ((raa.adviser_user_id IS NOT NULL AND raa.adviser_user_id = ?) OR (? <> '' AND LOWER(TRIM(COALESCE(raa.adviser_email, ''))) = LOWER(?))))";
    $listParams = [(int) ($_SESSION['user_id'] ?? 0), (string) ($_SESSION['user_email'] ?? ''), (string) ($_SESSION['user_email'] ?? '')];
}
$listSql .= ' ORDER BY ms.submitted_at DESC';
$stmt = $crad->prepare($listSql); $stmt->execute($listParams); $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
$breadcrumbs = [['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'], ['label' => 'Final Manuscript Review', 'url' => null]];
require_once ROOT_PATH . '/includes/layout-start.php'; renderBreadcrumbs($breadcrumbs);
?>
<div class="glass-dashboard"><div class="glass-board"><div class="glass-panel"><div class="glass-panel-body"><h5 class="glass-panel-title">Final Manuscript Review</h5><?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if (!$rows): ?><p class="text-muted">No final manuscript submissions yet.</p><?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Group</th><th>Version</th><th>File</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e((string) $row['group_number']) ?><div class="small text-muted"><?= e((string) $row['research_title']) ?></div></td><td>v<?= (int) $row['version_number'] ?></td><td><a href="<?= BASE_URL ?>/modules/crad/api/final-manuscript-document.php?id=<?= (int) $row['id'] ?>" target="_blank"><?= e((string) $row['original_name']) ?></a></td><td><?= e((string) $row['status']) ?></td><td><?php if ($row['status'] !== 'Approved'): ?><details><summary class="btn btn-sm btn-primary">Review</summary><form method="post" class="mt-3" style="min-width:320px"><?= csrfField() ?><input type="hidden" name="submission_id" value="<?= (int) $row['id'] ?>"><?php foreach (['content','methodology','results','conclusions','recommendations','references','formatting','compliance'] as $key): ?><input class="form-control form-control-sm mb-2" type="number" name="<?= $key ?>_score" min="0" max="100" step="0.01" placeholder="<?= ucfirst($key) ?> score" required><?php endforeach; ?><textarea class="form-control form-control-sm mb-2" name="remarks" placeholder="Remarks"></textarea><button class="btn btn-success btn-sm" name="review_action" value="approve">Approve</button> <button class="btn btn-warning btn-sm" name="review_action" value="revision">Request Revision</button></form></details><?php else: ?>Approved<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div></div></div>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
