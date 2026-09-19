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
$validTitleApprovalSql = cradValidTitleApprovalWhereSql('ta');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) $error = 'Security check failed.';
    else {
        $groupId = (int) ($_POST['research_group_id'] ?? 0);
        $stmt = $crad->prepare("SELECT rg.research_title, rg.group_name FROM research_groups rg INNER JOIN title_approvals ta ON ta.id = rg.title_approval_id AND {$validTitleApprovalSql} INNER JOIN final_manuscript_approvals fma ON fma.research_group_id = rg.id AND fma.status = 'Approved' WHERE rg.id = ? AND NOT EXISTS (SELECT 1 FROM publications p WHERE p.research_group_id = rg.id) LIMIT 1");
        $stmt->execute([$groupId]); $group = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$group) $error = 'Only approved groups without an existing publication record may be selected.';
        else {
            $insert = $crad->prepare("INSERT INTO publications (research_group_id, title, authors, status, created_by_user, created_by_name) VALUES (?, ?, ?, 'Draft', ?, ?)");
            $insert->execute([$groupId, $group['research_title'], $group['group_name'], (int) ($_SESSION['user_id'] ?? 0), (string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? '')]);
            logActivity('create', 'Created publication record for research group #' . $groupId, 'crad'); $message = 'Publication record created.';
        }
    }
}
$groups = $crad->query("SELECT rg.id, rg.group_number, rg.research_title FROM research_groups rg INNER JOIN title_approvals ta ON ta.id = rg.title_approval_id AND {$validTitleApprovalSql} INNER JOIN final_manuscript_approvals fma ON fma.research_group_id = rg.id AND fma.status = 'Approved' WHERE NOT EXISTS (SELECT 1 FROM publications p WHERE p.research_group_id = rg.id) ORDER BY rg.group_number")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$breadcrumbs = [['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'], ['label' => 'New Publication Record', 'url' => null]];
require_once ROOT_PATH . '/includes/layout-start.php'; renderBreadcrumbs($breadcrumbs);
?>
<div class="glass-dashboard"><div class="glass-board"><div class="glass-panel"><div class="glass-panel-body"><h5 class="glass-panel-title">Create Publication Record</h5><?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if (!$groups): ?><p class="text-muted">No approved final manuscripts are available for publication processing.</p><?php else: ?><form method="post"><?= csrfField() ?><label class="form-label">Approved Research Group</label><select class="form-select mb-3" name="research_group_id" required><option value="">Select group...</option><?php foreach ($groups as $group): ?><option value="<?= (int) $group['id'] ?>"><?= e((string) $group['group_number'] . ' - ' . $group['research_title']) ?></option><?php endforeach; ?></select><button class="btn btn-primary">Create Publication Record</button></form><?php endif; ?></div></div></div></div>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
