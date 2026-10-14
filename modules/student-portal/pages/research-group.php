<?php
/**
 * Student Portal - Research Group (members) submission.
 * Must be completed before Title Approval.
 */
declare(strict_types=1);

$pageTitle = 'Research Group';
$activeModule = 'student_portal';
$activePage = 'research-group';
$pageBannerIcon = 'fa-users';
$pageBannerDescription = 'Submit your research group members before Title Approval.';

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/research-group-flow.php';

requireAuth();
if (getCurrentUserRoleKey() !== 'student') {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$breadcrumbs = [
    ['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
    ['label' => 'Research Group', 'url' => null],
];

$crad = cradDb();
$studentId = trim((string) ($_SESSION['student_id'] ?? ''));
$studentName = trim((string) ($_SESSION['user_name'] ?? $_SESSION['full_name'] ?? ''));
$studentEmail = trim((string) ($_SESSION['user_email'] ?? ''));
$userId = (int) ($_SESSION['user_id'] ?? 0);
$department = trim((string) ($_SESSION['department'] ?? $_SESSION['college_dept'] ?? ''));
$minMembers = cradRgFlowMinMembers();
$maxMembers = cradRgFlowMaxMembers();

$flashOk = '';
$flashErr = '';
$group = null;
$members = [];

if ($crad && $studentId !== '') {
    cradRgFlowEnsureSchema($crad);
    $group = cradRgFlowGetStudentGroup($crad, $studentId);
    if ($group) {
        $members = cradRgFlowGetMembers($crad, (int) $group['id']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $crad) {
    if (!csrfVerify()) {
        $flashErr = 'Security check failed. Please refresh and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? 'submit');
        $reason = trim((string) ($_POST['incomplete_reason'] ?? ''));
        $names = (array) ($_POST['member_name'] ?? []);
        $ids = (array) ($_POST['member_student_id'] ?? []);
        $sections = (array) ($_POST['member_section'] ?? []);
        $payload = [];
        $n = max(count($names), count($ids), count($sections));
        for ($i = 0; $i < $n; $i++) {
            $payload[] = [
                'full_name' => (string) ($names[$i] ?? ''),
                'student_id' => (string) ($ids[$i] ?? ''),
                'section' => (string) ($sections[$i] ?? ''),
                'email' => '',
                'or_number' => '',
            ];
        }
        $result = cradRgFlowSaveSubmission(
            $crad,
            $studentId,
            $studentName,
            $studentEmail,
            $department,
            $userId,
            $payload,
            $reason,
            $action !== 'draft'
        );
        if (!empty($result['ok'])) {
            $flashOk = (string) $result['message'];
            if (function_exists('logActivity')) {
                logActivity('research_group_' . ($action === 'draft' ? 'draft' : 'submit'), (string) $result['message']);
            }
            $group = cradRgFlowGetStudentGroup($crad, $studentId);
            $members = $group ? cradRgFlowGetMembers($crad, (int) $group['id']) : [];
        } else {
            $flashErr = (string) ($result['message'] ?? 'Save failed.');
            $members = [];
            foreach ($payload as $p) {
                $members[] = [
                    'full_name' => $p['full_name'],
                    'student_id' => $p['student_id'],
                    'section' => $p['section'],
                    'email' => $p['email'],
                    'or_number' => $p['or_number'],
                    'is_leader' => 0,
                ];
            }
        }
    }
}

if ($members === []) {
    $members = [[
        'full_name' => $studentName,
        'student_id' => $studentId,
        'section' => '',
        'email' => '',
        'or_number' => '',
        'is_leader' => 1,
    ]];
}

$flowStatus = strtolower((string) ($group['flow_status'] ?? 'draft'));
$locked = in_array($flowStatus, ['approved', 'ready_for_assignment', 'pending_dh_approval', 'pending_incomplete_approval'], true);
$canEdit = !$locked || $flowStatus === 'rejected' || $flowStatus === 'draft';

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<div class="glass-dashboard">
  <div class="glass-board">
    <div class="glass-panel mb-4">
      <div class="glass-panel-body">
        <div class="glass-panel-head">
          <div>
            <h5 class="glass-panel-title">Research Group</h5>
            <p class="glass-panel-sub">Submit members before Title Approval. Minimum <?= (int) $minMembers ?> members (maximum <?= (int) $maxMembers ?>). Every submission waits for Department Head Approve/Reject. Below minimum also requires a Reason.</p>
          </div>
          <span class="glass-chip"><?= htmlspecialchars(str_replace('_', ' ', $flowStatus ?: 'draft')) ?></span>
        </div>

        <?php if ($flashOk): ?>
          <div class="alert alert-success"><?= htmlspecialchars($flashOk) ?></div>
        <?php endif; ?>
        <?php if ($flashErr): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($flashErr) ?></div>
        <?php endif; ?>

        <?php if (in_array($flowStatus, ['pending_dh_approval', 'pending_incomplete_approval'], true)): ?>
          <div class="alert alert-warning">Waiting for Department Head approval. Research Proposal stays locked until your Research Group is approved.</div>
        <?php elseif ($flowStatus === 'approved' || $flowStatus === 'ready_for_assignment'): ?>
          <div class="alert alert-success">Department Head approved your Research Group. Next: wait for coordinator and adviser assignment, then both must confirm before you can apply for Title Approval.</div>
        <?php elseif ($flowStatus === 'rejected'): ?>
          <?php $rejectReason = trim((string) ($group['dh_remarks'] ?? '')); ?>
          <div class="alert alert-danger alert-sms py-2 px-3 mb-2" style="display:inline-block;max-width:22rem;line-height:1.35;">
            <div class="fw-semibold">Group Submission rejected</div>
            <div>reason: <?= $rejectReason !== '' ? htmlspecialchars($rejectReason) : '' ?></div>
            <div>Update and submit.</div>
          </div>
        <?php endif; ?>

        <form method="post" id="rgForm" class="mt-3">
          <?= csrfField() ?>
          <input type="hidden" name="action" id="rgAction" value="submit">
          <div id="rgMemberList">
            <?php foreach ($members as $i => $m): ?>
              <div class="border rounded p-3 mb-3 rg-member-card">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong>Member <?= (int) ($i + 1) ?><?= !empty($m['is_leader']) || $i === 0 ? ' (Leader)' : '' ?></strong>
                  <?php if ($canEdit && $i > 0): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger rg-remove">Remove</button>
                  <?php endif; ?>
                </div>
                <div class="row g-2">
                  <div class="col-md-4">
                    <label class="form-label">Full Name</label>
                    <input class="form-control" name="member_name[]" value="<?= htmlspecialchars((string) ($m['full_name'] ?? '')) ?>" <?= $canEdit ? 'required' : 'readonly' ?>>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Student ID</label>
                    <input class="form-control" name="member_student_id[]" value="<?= htmlspecialchars((string) ($m['student_id'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label">Section</label>
                    <input class="form-control" name="member_section[]" value="<?= htmlspecialchars((string) ($m['section'] ?? '')) ?>" <?= $canEdit ? '' : 'readonly' ?>>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if ($canEdit): ?>
            <button type="button" class="btn btn-outline-primary mb-3" id="rgAddMember">+ Add Member</button>
            <div class="alert alert-warning py-2 mb-3" id="rgMaxMembersNotice" role="status" hidden>
              This research group has reached the maximum of 5 student members.
            </div>
          <?php endif; ?>

          <div class="mb-3" id="rgReasonWrap">
            <label class="form-label">Reason (required only if below <?= (int) $minMembers ?> members)</label>
            <textarea class="form-control" name="incomplete_reason" rows="3" <?= $canEdit ? '' : 'readonly' ?>><?= htmlspecialchars((string) ($group['incomplete_reason'] ?? '')) ?></textarea>
          </div>

          <?php if ($canEdit): ?>
            <div class="d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary" onclick="document.getElementById('rgAction').value='submit'">Submit Research Group</button>
              <button type="submit" class="btn btn-outline-secondary" onclick="document.getElementById('rgAction').value='draft'">Save Draft</button>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  const list = document.getElementById('rgMemberList');
  const addBtn = document.getElementById('rgAddMember');
  const maxMembers = <?= (int) $maxMembers ?>;
  if (!list || !addBtn) return;
  const maxNotice = document.getElementById('rgMaxMembersNotice');
  function updateAddButton() {
    const atLimit = list.querySelectorAll('.rg-member-card').length >= maxMembers;
    addBtn.disabled = atLimit;
    addBtn.setAttribute('aria-disabled', atLimit ? 'true' : 'false');
    if (maxNotice) maxNotice.hidden = !atLimit;
  }
  updateAddButton();
  addBtn.addEventListener('click', function () {
    if (list.querySelectorAll('.rg-member-card').length >= maxMembers) {
      updateAddButton();
      return;
    }
    const card = document.createElement('div');
    card.className = 'border rounded p-3 mb-3 rg-member-card';
    card.innerHTML = '<div class="d-flex justify-content-between align-items-center mb-2"><strong>Member</strong><button type="button" class="btn btn-sm btn-outline-danger rg-remove">Remove</button></div>'
      + '<div class="row g-2">'
      + '<div class="col-md-4"><label class="form-label">Full Name</label><input class="form-control" name="member_name[]" required></div>'
      + '<div class="col-md-3"><label class="form-label">Student ID</label><input class="form-control" name="member_student_id[]"></div>'
      + '<div class="col-md-4"><label class="form-label">Section</label><input class="form-control" name="member_section[]"></div>'
      + '</div>';
    list.appendChild(card);
    updateAddButton();
  });
  list.addEventListener('click', function (e) {
    const btn = e.target.closest('.rg-remove');
    if (!btn) return;
    const card = btn.closest('.rg-member-card');
    if (card && list.querySelectorAll('.rg-member-card').length > 1) {
      card.remove();
      updateAddButton();
    }
  });
})();
</script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
