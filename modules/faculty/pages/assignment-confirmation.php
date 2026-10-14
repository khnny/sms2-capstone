<?php
/**
 * Adviser / Research Coordinator — Approve or Decline dual assignment.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/modules/crad/includes/research-group-flow.php';

requireAuth();
$roleKey = getCurrentUserRoleKey();
if (!in_array($roleKey, ['adviser', 'research_coordinator'], true)) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$isAdviser = $roleKey === 'adviser';
$pageTitle = 'Assignment Confirmation';
$activeModule = $isAdviser ? 'faculty' : 'crad';
$activePage = 'assignment-confirmation';
$pageBannerIcon = 'fa-handshake';
$pageBannerDescription = 'Approve or Decline your Research Coordinator / Adviser assignment. Fully Assigned only after both approve.';

$breadcrumbs = [
    ['label' => $isAdviser ? 'Faculty' : 'Research Coordinator', 'url' => null],
    ['label' => 'Assignment Confirmation', 'url' => null],
];

$pdo = cradDb();
cradRgFlowEnsureSchema($pdo);
$userId = (int) ($_SESSION['user_id'] ?? 0);
$email = (string) ($_SESSION['user_email'] ?? '');
$fullName = (string) ($_SESSION['user_name'] ?? $_SESSION['full_name'] ?? '');
$flashOk = '';
$flashErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $flashErr = 'Security check failed. Please refresh and try again.';
    } else {
        $cycleId = (int) ($_POST['cycle_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $reason = trim((string) ($_POST['cancel_reason'] ?? ''));
        if ($action === 'confirm') {
            $result = cradRgFlowConfirmRole($pdo, $cycleId, $roleKey, $userId);
        } elseif ($action === 'cancel') {
            $result = cradRgFlowCancelCycle($pdo, $cycleId, $roleKey, $userId, $reason);
        } else {
            $result = ['ok' => false, 'message' => 'Unknown action.'];
        }
        if (!empty($result['ok'])) {
            $flashOk = (string) $result['message'];
            if (function_exists('logActivity')) {
                logActivity('assignment_' . $action, 'Cycle #' . $cycleId . ' - ' . $result['message']);
            }
        } else {
            $flashErr = (string) ($result['message'] ?? 'Action failed.');
        }
    }
}

$pending = cradRgFlowPendingConfirmationsForUser($pdo, $roleKey, $userId, $email, $fullName);

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<div class="glass-dashboard">
  <div class="glass-board">
    <div class="glass-panel">
      <div class="glass-panel-body">
        <div class="glass-panel-head mb-3">
          <div>
            <h5 class="glass-panel-title">Pending Assignments</h5>
            <p class="glass-panel-sub">Primary confirmation appears as a popup modal on your dashboard and portal pages. This page is a fallback list. Assigned by Department Head is not Fully Assigned until both Research Coordinator and Research Adviser accept. Decline sets Needs Reassignment for the Department Head.</p>
          </div>
        </div>
        <?php if ($flashOk): ?><div class="alert alert-success"><?= htmlspecialchars($flashOk) ?></div><?php endif; ?>
        <?php if ($flashErr): ?><div class="alert alert-danger"><?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

        <?php if ($pending === []): ?>
          <div class="alert alert-info mb-0">No pending assignments to approve.</div>
        <?php else: ?>
          <?php foreach ($pending as $row): ?>
            <?php
              $overall = function_exists('cradRgFlowOverallStatusFromCycle')
                ? cradRgFlowOverallStatusFromCycle($row)
                : (string) ($row['status'] ?? '');
              $overallLabel = function_exists('cradRgFlowOverallStatusLabel')
                ? cradRgFlowOverallStatusLabel($overall !== '' ? $overall : (string) ($row['status'] ?? ''))
                : str_replace('_', ' ', (string) ($row['status'] ?? ''));
            ?>
            <div class="border rounded p-3 mb-3">
              <div class="row g-2">
                <div class="col-md-8">
                  <strong><?= htmlspecialchars((string) (($row['research_title'] ?? '') ?: 'Pending Title')) ?></strong><br>
                  <small class="text-muted">
                    Group: <?= htmlspecialchars((string) (($row['group_name'] ?? '') ?: ($row['group_number'] ?? ''))) ?>
                    · Leader: <?= htmlspecialchars((string) ($row['leader_name'] ?? $row['student_id'] ?? '')) ?>
                    · Status: <strong><?= htmlspecialchars($overallLabel) ?></strong>
                  </small><br>
                  <small>
                    Coordinator: <?= htmlspecialchars((string) (($row['coordinator_name'] ?? '') ?: '—')) ?>
                    <?= !empty($row['coordinator_confirmed_at']) ? ' (approved)' : ' (pending)' ?>
                    · Adviser: <?= htmlspecialchars((string) (($row['adviser_name'] ?? '') ?: '—')) ?>
                    <?= !empty($row['adviser_confirmed_at']) ? ' (approved)' : ' (pending)' ?>
                  </small>
                </div>
                <div class="col-md-4">
                  <form method="post" class="d-grid gap-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="cycle_id" value="<?= (int) $row['id'] ?>">
                    <button type="submit" name="action" value="confirm" class="btn btn-success btn-sm">Approve Assignment</button>
                    <textarea class="form-control form-control-sm" name="cancel_reason" rows="2" placeholder="Decline reason (optional)"></textarea>
                    <button type="submit" name="action" value="cancel" class="btn btn-outline-danger btn-sm"
                      onclick="return confirm('Decline this assignment? Department Head must reassign.');">Decline Assignment</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
