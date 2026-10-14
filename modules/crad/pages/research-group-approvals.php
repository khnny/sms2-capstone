<?php
/**
 * Department Head - Approve/Reject every Research Group; list approved for assignment.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once __DIR__ . '/../includes/research-group-flow.php';

requireAuth();
$roleKey = getCurrentUserRoleKey();
if ($roleKey !== 'department_head' && !smsIsGrantedAdminRole($roleKey)) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$pageTitle = 'Research Group Approvals';
$activeModule = 'crad';
$activePage = 'research-group-approvals';
$pageBannerIcon = 'fa-user-check';
$pageBannerDescription = 'Approve or Reject every Research Group submission (complete or incomplete). Approved groups move to assignment.';

$breadcrumbs = [
    ['label' => 'Research Management', 'url' => BASE_URL . '/modules/crad/pages/research-coordinator-management.php'],
    ['label' => 'Research Group Approvals', 'url' => null],
];

$pdo = cradDb();
cradRgFlowEnsureSchema($pdo);
$flashOk = '';
$flashErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $flashErr = 'Security check failed. Please refresh and try again.';
    } else {
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $decision = (string) ($_POST['decision'] ?? '');
        $remarks = trim((string) ($_POST['remarks'] ?? ''));
        $result = cradRgFlowDhDecide($pdo, $groupId, $decision, (int) ($_SESSION['user_id'] ?? 0), $remarks);
        if (!empty($result['ok'])) {
            $flashOk = (string) $result['message'];
            if (function_exists('logActivity')) {
                logActivity('research_group_dh_' . $decision, 'Group #' . $groupId . ' — ' . $result['message']);
            }
        } else {
            $flashErr = (string) ($result['message'] ?? 'Decision failed.');
        }
    }
}

$queue = cradRgFlowPendingIncompleteQueue($pdo);
$ready = cradRgFlowAssignmentReadyQueue($pdo);
$coordUrl = BASE_URL . '/modules/crad/pages/research-coordinator-management.php';
$assignUrl = BASE_URL . '/modules/crad/pages/research-coordinator-management.php#rcm-adviser';

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<div class="glass-dashboard">
  <div class="glass-board">
    <?php if ($flashOk): ?><div class="alert alert-success"><?= htmlspecialchars($flashOk) ?></div><?php endif; ?>
    <?php if ($flashErr): ?><div class="alert alert-danger"><?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <div class="glass-panel mb-4">
      <div class="glass-panel-body">
        <div class="glass-panel-head mb-3">
          <div>
            <h5 class="glass-panel-title">Pending Department Head Approval</h5>
            <p class="glass-panel-sub">Every student submission waits here (complete or incomplete). Incomplete groups include a Reason. Approve moves the group to assignment; Reject requires student resubmit.</p>
          </div>
          <span class="glass-chip"><?= count($queue) ?> pending</span>
        </div>

        <?php if ($queue === []): ?>
          <div class="alert alert-info mb-0">No Research Groups awaiting Department Head approval.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Leader</th>
                  <th>Dept</th>
                  <th>Members</th>
                  <th>Reason (if incomplete)</th>
                  <th>Submitted</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($queue as $row): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars((string) ($row['leader_name'] ?? '')) ?></strong><br>
                      <small class="text-muted"><?= htmlspecialchars((string) ($row['leader_id'] ?? '')) ?> / <?= htmlspecialchars((string) ($row['group_number'] ?? '')) ?></small>
                    </td>
                    <td><?= htmlspecialchars((string) ($row['college_dept'] ?? '')) ?></td>
                    <td>
                      <?= (int) ($row['member_count'] ?? count($row['members'] ?? [])) ?> / <?= (int) (cradRgFlowMinMembers()) ?>
                      <ul class="small mb-0 mt-1">
                        <?php foreach (($row['members'] ?? []) as $m): ?>
                          <li><?= htmlspecialchars((string) ($m['full_name'] ?? '')) ?> (<?= htmlspecialchars((string) ($m['student_id'] ?? '')) ?>)</li>
                        <?php endforeach; ?>
                      </ul>
                    </td>
                    <td style="max-width:240px"><?= nl2br(htmlspecialchars((string) ($row['incomplete_reason'] ?? ''))) ?></td>
                    <td><?= htmlspecialchars((string) ($row['submitted_at'] ?? '')) ?></td>
                    <td style="min-width:260px;width:280px">
                      <form method="post" class="rg-dh-decide-form needs-validation" novalidate data-rg-dh-form>
                        <?= csrfField() ?>
                        <input type="hidden" name="group_id" value="<?= (int) $row['id'] ?>">
                        <div class="mb-2">
                          <label class="form-label form-label-sm mb-1 fw-semibold" for="rg-dh-remarks-<?= (int) $row['id'] ?>">Remarks / Reason</label>
                          <textarea
                            id="rg-dh-remarks-<?= (int) $row['id'] ?>"
                            class="form-control form-control-sm w-100"
                            name="remarks"
                            rows="3"
                            style="min-height:78px;resize:vertical"
                            placeholder="Required on Reject (e.g. incomplete members or unclear reason). Optional on Approve."
                            aria-describedby="rg-dh-remarks-help-<?= (int) $row['id'] ?>"
                          ></textarea>
                          <div id="rg-dh-remarks-help-<?= (int) $row['id'] ?>" class="form-text small mb-0">
                            Reject needs a short reason so the student can fix and resubmit.
                          </div>
                          <div class="invalid-feedback">Please enter Remarks / Reason before rejecting.</div>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                          <button class="btn btn-sm btn-success flex-fill" name="decision" value="approved" type="submit">Approve</button>
                          <button class="btn btn-sm btn-outline-danger flex-fill" name="decision" value="rejected" type="submit" data-rg-require-remarks="1">Reject</button>
                        </div>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="glass-panel">
      <div class="glass-panel-body">
        <div class="glass-panel-head mb-3">
          <div>
            <h5 class="glass-panel-title">Approved / Ready for Assignment</h5>
            <p class="glass-panel-sub">Department Head-approved Research Groups. Assign Research Coordinator, then Research Adviser.</p>
          </div>
          <span class="glass-chip"><?= count($ready) ?> group<?= count($ready) === 1 ? '' : 's' ?></span>
        </div>

        <?php if ($ready === []): ?>
          <div class="alert alert-info mb-0">No submitted Research Groups waiting for assignment yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Leader / Group</th>
                  <th>Dept</th>
                  <th>Members</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th>Next</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ready as $row): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars((string) ($row['leader_name'] ?? '')) ?></strong><br>
                      <small class="text-muted"><?= htmlspecialchars((string) ($row['leader_id'] ?? '')) ?> / <?= htmlspecialchars((string) ($row['group_number'] ?? '')) ?></small>
                    </td>
                    <td><?= htmlspecialchars((string) ($row['college_dept'] ?? '')) ?></td>
                    <td>
                      <?= (int) ($row['member_count'] ?? count($row['members'] ?? [])) ?>
                      <ul class="small mb-0 mt-1">
                        <?php foreach (($row['members'] ?? []) as $m): ?>
                          <li><?= htmlspecialchars((string) ($m['full_name'] ?? '')) ?> (<?= htmlspecialchars((string) ($m['student_id'] ?? '')) ?><?= trim((string) ($m['section'] ?? '')) !== '' ? ' · ' . htmlspecialchars((string) $m['section']) : '' ?>)</li>
                        <?php endforeach; ?>
                      </ul>
                    </td>
                    <td>
                      <span class="badge text-bg-<?= !empty($row['has_coordinator']) && !empty($row['has_adviser']) ? 'success' : 'primary' ?>">
                        <?= htmlspecialchars((string) ($row['assignment_label'] ?? 'Ready for assignment')) ?>
                      </span><br>
                      <small class="text-muted"><?= htmlspecialchars(str_replace('_', ' ', (string) ($row['flow_status'] ?? ''))) ?></small>
                    </td>
                    <td><?= htmlspecialchars((string) ($row['submitted_at'] ?? '')) ?></td>
                    <td class="d-grid gap-1" style="min-width:180px">
                      <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($coordUrl) ?>#rcm-adviser">Assign Coordinator &amp; Adviser</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<style>
  .rg-dh-decide-form .form-label-sm { font-size: 0.8rem; }
  .rg-dh-decide-form .form-control {
    border-radius: 0.5rem;
  }
  .rg-dh-decide-form .form-control.is-invalid {
    border-color: #dc3545;
  }
  .rg-dh-decide-form .invalid-feedback {
    display: none;
  }
  .rg-dh-decide-form .form-control.is-invalid ~ .invalid-feedback {
    display: block;
  }
</style>
<script>
(function () {
  document.querySelectorAll('[data-rg-dh-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var submitter = event.submitter || document.activeElement;
      var requiresRemarks = submitter && submitter.getAttribute('data-rg-require-remarks') === '1';
      var remarks = form.querySelector('textarea[name="remarks"]');
      if (!remarks) return;
      remarks.classList.remove('is-invalid');
      if (requiresRemarks && String(remarks.value || '').trim() === '') {
        event.preventDefault();
        event.stopPropagation();
        remarks.classList.add('is-invalid');
        remarks.focus();
      }
    });
  });
})();
</script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
