<?php
/**
 * SMS 2 - Notification Viewer
 */
declare(strict_types=1);

$pageTitle = 'Notification';
$activeModule = '';
$activePage = 'notifications';
$breadcrumbs = [
    ['label' => 'Notifications', 'url' => null],
];

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/notifications.php';

$notificationType = trim((string) ($_GET['type'] ?? ''));
$viewerRole = getCurrentUserRoleKey();
if ($viewerRole === 'student') {
    $activeModule = 'student_portal';
    $activePage = 'research-proposal-submission';
    $breadcrumbs = [
        ['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
        ['label' => 'Notifications', 'url' => null],
    ];
} elseif (in_array($viewerRole, ['adviser', 'panel', 'research_director'], true)) {
    $activeModule = 'faculty';
    $activePage = 'approved-research';
} elseif (in_array($viewerRole, ['crad_officer', 'research_coordinator'], true)) {
    $activeModule = 'crad';
}

function smsNotificationBackUrl(): string
{
    $role = getCurrentUserRoleKey();
    if ($role === 'student') {
        return BASE_URL . '/modules/student-portal/pages/dashboard.php';
    }
    if (in_array($role, ['adviser', 'panel'], true)) {
        return BASE_URL . '/modules/faculty/index.php';
    }
    if (in_array($role, ['crad_officer', 'research_coordinator'], true)) {
        return BASE_URL . '/modules/crad/index.php';
    }
    return BASE_URL . '/dashboard/index.php';
}

function smsNotificationPhilippineNow(): array
{
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    return [
        'label' => $now->format('M j, Y h:i:s A'),
        'iso' => $now->format(DateTimeInterface::ATOM),
    ];
}

function smsNotificationResearchGroupDetail(): ?array
{
    if (getCurrentUserRoleKey() !== 'student') {
        return null;
    }

    $crad = cradDb();
    if (!$crad) {
        return null;
    }

    $studentId = trim((string) ($_SESSION['student_id'] ?? ''));
    $studentEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    $studentName = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
    $studentUserId = (int) ($_SESSION['user_id'] ?? 0);

    try {
        $stmt = $crad->prepare(
            "SELECT g.id AS research_group_id,
                    COALESCE(p.proposal_number, t.proposal_number, g.proposal_number) AS proposal_number,
                    COALESCE(p.research_title, t.proposed_title, g.research_title) AS research_title,
                    g.title_approval_id, g.group_number, g.group_name, g.status, g.created_at, g.date_assigned
             FROM research_groups g
             LEFT JOIN research_proposals p ON p.id = g.proposal_id
             LEFT JOIN title_approvals t ON t.id = g.title_approval_id
             WHERE g.group_number IS NOT NULL
               AND g.group_number <> ''
               AND (g.title_approval_id IS NULL OR g.title_approval_id = 0 OR t.id IS NOT NULL)
               AND (
                    (:student_id_value <> '' AND p.rep_id = :student_id_rep)
                 OR (:student_email_value <> '' AND LOWER(p.rep_email) = :student_email_rep)
                 OR (:student_name_value <> '' AND LOWER(TRIM(p.rep_name)) = :student_name_rep)
                 OR (:user_id_value > 0 AND p.submitted_by_user = :user_id_match)
                 OR (:student_id_value_t <> '' AND t.student_id = :student_id_title)
                 OR (:student_name_value_t <> '' AND LOWER(TRIM(t.student_name)) = :student_name_title)
                 OR (:user_id_value_t > 0 AND t.student_user_id = :user_id_title)
               )
             ORDER BY g.date_assigned DESC, g.id DESC
             LIMIT 1"
        );
        $stmt->execute([
            ':student_id_value' => $studentId,
            ':student_id_rep' => $studentId,
            ':student_email_value' => $studentEmail,
            ':student_email_rep' => $studentEmail,
            ':student_name_value' => $studentName,
            ':student_name_rep' => $studentName,
            ':user_id_value' => $studentUserId,
            ':user_id_match' => $studentUserId,
            ':student_id_value_t' => $studentId,
            ':student_id_title' => $studentId,
            ':student_name_value_t' => $studentName,
            ':student_name_title' => $studentName,
            ':user_id_value_t' => $studentUserId,
            ':user_id_title' => $studentUserId,
        ]);
        $group = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('Notification research group view failed: ' . $e->getMessage());
        return null;
    }

    if (!$group) {
        return null;
    }

    $readResearchGroups = array_map('intval', (array) ($_SESSION['read_research_group_notifications'] ?? []));
    $readResearchGroups[] = (int) ($group['research_group_id'] ?? 0);
    $_SESSION['read_research_group_notifications'] = array_values(array_unique(array_filter($readResearchGroups)));

    try {
        $assignmentStmt = $crad->prepare(
            "SELECT
                (
                    SELECT adviser_name
                    FROM research_adviser_assignments
                    WHERE assignment_status = 'Assigned'
                      AND (research_group_id = :adviser_group_id OR group_number = :adviser_group_number OR proposal_number = :adviser_proposal_number)
                    ORDER BY assigned_at DESC, updated_at DESC, id DESC
                    LIMIT 1
                ) AS assigned_adviser,
                '' AS assigned_panels"
        );
        $assignmentStmt->execute([
            ':adviser_group_id' => (int) ($group['research_group_id'] ?? 0),
            ':adviser_group_number' => (string) ($group['group_number'] ?? ''),
            ':adviser_proposal_number' => (string) ($group['proposal_number'] ?? ''),
        ]);
        $assignment = $assignmentStmt->fetch() ?: [];
    } catch (Throwable $e) {
        error_log('Notification research group assignment detail failed: ' . $e->getMessage());
        $assignment = [];
    }

    $status = (string) (($group['status'] ?? '') ?: 'Approved');
    $philippineNow = smsNotificationPhilippineNow();

    $fromTitleApproval = !empty($group['title_approval_id']);
    $details = [
        ['label' => 'Research Group Number', 'value' => (string) ($group['group_number'] ?? '')],
        ['label' => 'Group Name', 'value' => (string) ($group['group_name'] ?? '')],
        ['label' => 'Status', 'value' => $status],
    ];
    if (!$fromTitleApproval) {
        array_unshift($details, ['label' => 'Proposal Number', 'value' => (string) ($group['proposal_number'] ?? '')]);
        $details[] = ['label' => 'Adviser', 'value' => (string) (($assignment['assigned_adviser'] ?? '') ?: 'For assignment')];
        $details[] = ['label' => 'Panel Members', 'value' => (string) (($assignment['assigned_panels'] ?? '') ?: 'For assignment')];
    }

    return [
        'title' => $fromTitleApproval ? 'Title Approval Status' : 'Research Approval Status',
        'badge' => $status,
        'badge_class' => 'success',
        'icon' => 'fa-users',
        'time' => $philippineNow['label'],
        'time_iso' => $philippineNow['iso'],
        'details' => $details,
        'message' => 'Research Title' . "\n" . (string) ($group['research_title'] ?? ''),
    ];
}

function smsNotificationReturnedProposalDetail(): ?array
{
    return smsNotificationReturnedTitleApprovalDetail();
}

function smsNotificationReturnedTitleApprovalDetail(): ?array
{
    if (getCurrentUserRoleKey() !== 'student') {
        return null;
    }

    $crad = cradDb();
    if (!$crad) {
        return null;
    }

    $studentId = trim((string) ($_SESSION['student_id'] ?? ''));
    $studentName = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
    $studentUserId = (int) ($_SESSION['user_id'] ?? 0);
    $titleApprovalId = (int) ($_GET['title_approval'] ?? 0);

    try {
        $sql = "SELECT id, proposed_title, adviser_name, adviser_remarks, coordinator_remarks, reviewed_at, coordinator_reviewed_at, sent_at
                FROM title_approvals
                WHERE (status = 'Returned' OR (status = 'Approved' AND coordinator_status = 'Returned'))
                  AND (
                       (:student_id_value <> '' AND student_id = :student_id_match)
                    OR (:student_name_value <> '' AND LOWER(TRIM(student_name)) = :student_name_match)
                    OR (:user_id_value > 0 AND student_user_id = :user_id_match)
                  )";
        if ($titleApprovalId > 0) {
            $sql .= ' AND id = :title_approval_id';
        }
        $sql .= ' ORDER BY reviewed_at DESC, id DESC LIMIT 1';

        $stmt = $crad->prepare($sql);
        $params = [
            ':student_id_value' => $studentId,
            ':student_id_match' => $studentId,
            ':student_name_value' => $studentName,
            ':student_name_match' => $studentName,
            ':user_id_value' => $studentUserId,
            ':user_id_match' => $studentUserId,
        ];
        if ($titleApprovalId > 0) {
            $params[':title_approval_id'] = $titleApprovalId;
        }
        $stmt->execute($params);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('Notification returned title approval view failed: ' . $e->getMessage());
        return null;
    }

    if (!$row) {
        return null;
    }

    $readTitleApprovals = array_map('intval', (array) ($_SESSION['read_returned_title_approvals'] ?? []));
    $readTitleApprovals[] = (int) ($row['id'] ?? 0);
    $_SESSION['read_returned_title_approvals'] = array_values(array_unique(array_filter($readTitleApprovals)));

    $updated = strtotime((string) (($row['coordinator_reviewed_at'] ?? '') ?: ($row['reviewed_at'] ?? '') ?: ($row['sent_at'] ?? ''))) ?: time();
    $philippineNow = smsNotificationPhilippineNow();
    return [
        'title' => 'Title Approval Returned',
        'badge' => 'Returned',
        'badge_class' => 'danger',
        'icon' => 'fa-undo',
        'time' => $philippineNow['label'],
        'time_iso' => $philippineNow['iso'],
        'details' => [
            ['label' => 'Research Title', 'value' => (string) ($row['proposed_title'] ?? '')],
            ['label' => 'Adviser', 'value' => (string) (($row['adviser_name'] ?? '') ?: 'Research Adviser')],
            ['label' => 'Status', 'value' => 'Returned'],
            ['label' => 'Date Returned', 'value' => date('F j, Y', $updated)],
        ],
        'message' => (string) ((($row['coordinator_remarks'] ?? '') ?: ($row['adviser_remarks'] ?? '')) ?: 'Returned for revision. Please review and resubmit your Title Approval Form.'),
        'actions' => [
            [
                'label' => 'Resubmit Title Approval',
                'url' => BASE_URL . '/modules/student-portal/pages/research-proposal-submission.php?resubmit_title_approval=' . (int) ($row['id'] ?? 0),
                'icon' => 'fa-paper-plane',
                'class' => 'primary',
            ],
        ],
    ];
}

$notificationId = (int) ($_GET['id'] ?? 0);
$notificationViewData = null;

if ($notificationId > 0) {
    smsMarkCurrentUserNotificationRead($notificationId);
    $row = smsCurrentUserNotificationById($notificationId);
    if ($row) {
        $philippineNow = smsNotificationPhilippineNow();
        $notificationViewData = [
            'title' => (string) ($row['title'] ?? 'Notification'),
            'badge' => 'Notification',
            'badge_class' => 'secondary',
            'icon' => (string) ($row['icon'] ?? 'fa-bell'),
            'time' => $philippineNow['label'],
            'time_iso' => $philippineNow['iso'],
            'details' => smsNotificationBodyDetails((string) ($row['body'] ?? ''), (string) ($row['title'] ?? '')),
            'message' => '',
        ];
    }
} elseif ($notificationType === 'research_group') {
    $notificationViewData = smsNotificationResearchGroupDetail();
} elseif ($notificationType === 'returned_proposal') {
    $notificationViewData = smsNotificationReturnedProposalDetail();
} elseif ($notificationType === 'returned_title_approval') {
    $notificationViewData = smsNotificationReturnedTitleApprovalDetail();
} elseif ($notificationType === 'assignment') {
    $notificationViewData = smsCurrentUserAssignmentNotificationDetail(
        (string) ($_GET['group'] ?? ''),
        (string) ($_GET['proposal'] ?? '')
    );
}

require_once ROOT_PATH . '/includes/layout-start.php';
?>

<style>
    .notification-view-shell {
        align-items: center;
        display: flex;
        justify-content: center;
        min-height: calc(100vh - 190px);
        padding: 2rem 0;
    }
    .notification-view-card {
        background: var(--sms-surface-elevated, var(--sms-card-bg, #fff));
        border: 1px solid var(--sms-border, #dbe4f0);
        border-radius: 12px;
        box-shadow: 0 18px 46px rgba(15, 23, 42, .14);
        color: var(--sms-text, #334155);
        max-width: 760px;
        overflow: hidden;
        width: min(760px, 100%);
    }
    .notification-view-head {
        align-items: flex-start;
        background: linear-gradient(135deg, #f8fafc, #eef4ff);
        border-bottom: 1px solid var(--sms-border, #dbe4f0);
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 1.2rem;
    }
    .notification-view-title {
        align-items: center;
        color: var(--sms-heading, #0f172a);
        display: flex;
        gap: .75rem;
        min-width: 0;
    }
    .notification-view-icon {
        align-items: center;
        background: #2454c6;
        border-radius: 10px;
        color: #fff;
        display: inline-flex;
        flex: 0 0 42px;
        height: 42px;
        justify-content: center;
        width: 42px;
    }
    .notification-view-title h1 {
        font-size: 1.15rem;
        font-weight: 800;
        line-height: 1.25;
        margin: 0;
        overflow-wrap: anywhere;
    }
    .notification-view-title time {
        color: var(--sms-text-muted, #64748b);
        display: block;
        font-size: .82rem;
        font-weight: 700;
        margin-top: .2rem;
    }
    .notification-view-badge {
        border-radius: 999px;
        color: #fff;
        flex: 0 0 auto;
        font-size: .76rem;
        font-weight: 800;
        padding: .35rem .65rem;
        text-transform: capitalize;
    }
    .notification-view-badge.primary { background: #2454c6; }
    .notification-view-badge.secondary { background: #64748b; }
    .notification-view-badge.success { background: #16834c; }
    .notification-view-badge.danger { background: #dc2626; }
    .notification-view-body {
        background: var(--sms-surface-elevated, var(--sms-card-bg, #fff));
        padding: 1.2rem;
    }
    .notification-view-grid {
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    }
    .notification-view-item {
        background: var(--sms-surface-muted, #f8fafc);
        border: 1px solid var(--sms-border, #dbe4f0);
        border-radius: 8px;
        min-width: 0;
        padding: .8rem;
    }
    .notification-view-item span {
        color: var(--sms-text-muted, #64748b);
        display: block;
        font-size: .74rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .notification-view-item strong {
        color: var(--sms-heading, #0f172a);
        display: block;
        font-size: .96rem;
        margin-top: .2rem;
        overflow-wrap: anywhere;
    }
    .notification-view-message {
        background: var(--sms-surface-muted, #f8fafc);
        border: 1px solid var(--sms-border, #dbe4f0);
        border-radius: 8px;
        color: var(--sms-text-strong, #172033);
        font-weight: 650;
        margin-top: .9rem;
        padding: .9rem;
        white-space: pre-line;
    }
    .notification-view-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        justify-content: flex-end;
        margin-top: 1rem;
    }
    .notification-view-action {
        align-items: center;
        border: 1px solid transparent;
        border-radius: 10px;
        display: inline-flex;
        font-size: .86rem;
        font-weight: 800;
        gap: .45rem;
        min-height: 40px;
        padding: .6rem .95rem;
        text-decoration: none;
    }
    .notification-view-action.primary {
        background: #2454c6;
        color: #fff;
    }
    .notification-view-action.primary:hover {
        background: #1e40af;
        color: #fff;
    }
    .notification-view-empty {
        padding: 2rem;
        text-align: center;
    }
    [data-theme="dark"] .notification-view-card {
        background: rgba(18, 28, 52, .94);
        border-color: rgba(148, 163, 184, .22);
        box-shadow: 0 18px 46px rgba(0, 0, 0, .34);
    }
    [data-theme="dark"] .notification-view-head {
        background: linear-gradient(135deg, rgba(30, 58, 138, .34), rgba(15, 23, 42, .72));
        border-bottom-color: rgba(148, 163, 184, .2);
    }
    [data-theme="dark"] .notification-view-title h1,
    [data-theme="dark"] .notification-view-item strong,
    [data-theme="dark"] .notification-view-message {
        color: var(--sms-text-strong, #f1f5f9);
    }
    [data-theme="dark"] .notification-view-title time,
    [data-theme="dark"] .notification-view-item span {
        color: var(--sms-text-muted, #94a3b8);
    }
    [data-theme="dark"] .notification-view-body {
        background: rgba(18, 28, 52, .86);
    }
    [data-theme="dark"] .notification-view-item,
    [data-theme="dark"] .notification-view-message {
        background: rgba(15, 23, 42, .58);
        border-color: rgba(148, 163, 184, .22);
    }
    @media (max-width: 640px) {
        .notification-view-head {
            flex-direction: column;
        }
    }
</style>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="notification-view-shell">
    <section class="notification-view-card" aria-label="Notification detail">
        <?php if ($notificationViewData): ?>
            <div class="notification-view-head">
                <div class="notification-view-title">
                    <div class="notification-view-icon">
                        <?= smsIcon((string) ($notificationViewData['icon'] ?? 'bell')) ?>
                    </div>
                    <div>
                        <h1><?= htmlspecialchars((string) ($notificationViewData['title'] ?? 'Notification')) ?></h1>
                        <time data-ph-live-datetime datetime="<?= htmlspecialchars((string) ($notificationViewData['time_iso'] ?? '')) ?>">
                            <?= htmlspecialchars((string) ($notificationViewData['time'] ?? '')) ?>
                        </time>
                    </div>
                </div>
                <span class="notification-view-badge <?= htmlspecialchars((string) ($notificationViewData['badge_class'] ?? 'secondary')) ?>">
                    <?= htmlspecialchars((string) ($notificationViewData['badge'] ?? 'Notification')) ?>
                </span>
            </div>
            <div class="notification-view-body">
                <div class="notification-view-grid">
                    <?php foreach (($notificationViewData['details'] ?? []) as $detail): ?>
                        <div class="notification-view-item">
                            <span><?= htmlspecialchars((string) ($detail['label'] ?? 'Detail')) ?></span>
                            <strong><?= htmlspecialchars((string) ($detail['value'] ?? '')) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($notificationViewData['message'])): ?>
                    <div class="notification-view-message"><?= htmlspecialchars((string) $notificationViewData['message']) ?></div>
                <?php endif; ?>
                <?php if (!empty($notificationViewData['actions']) && is_array($notificationViewData['actions'])): ?>
                    <div class="notification-view-actions">
                        <?php foreach ($notificationViewData['actions'] as $action): ?>
                            <a class="notification-view-action <?= htmlspecialchars((string) ($action['class'] ?? 'primary')) ?>"
                               href="<?= htmlspecialchars((string) ($action['url'] ?? '#')) ?>">
                                <?= smsIcon((string) ($action['icon'] ?? 'arrow-right')) ?>
                                <?= htmlspecialchars((string) ($action['label'] ?? 'Open')) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="notification-view-empty">
                <div class="notification-view-icon mx-auto mb-3">
                    <?= smsIcon('bell-slash') ?>
                </div>
                <h1 class="h5 fw-bold">Notification not found</h1>
                <p class="text-muted mb-3">This notification may have been deleted or is not assigned to your account.</p>
                <a class="btn btn-sms-primary" href="<?= htmlspecialchars(smsNotificationBackUrl()) ?>">Back</a>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const liveTime = document.querySelector('[data-ph-live-datetime]');
    if (!liveTime) return;

    const serverIso = liveTime.getAttribute('datetime');
    const serverMs = Date.parse(serverIso || '');
    const offsetMs = Number.isNaN(serverMs) ? 0 : serverMs - Date.now();
    const formatter = new Intl.DateTimeFormat('en-US', {
        timeZone: 'Asia/Manila',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });

    const update = function () {
        const now = new Date(Date.now() + offsetMs);
        liveTime.textContent = formatter.format(now).replace(/, (?=\d{2}:)/, ' ');
        liveTime.setAttribute('datetime', now.toISOString());
    };

    update();
    window.setInterval(update, 1000);
});
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
