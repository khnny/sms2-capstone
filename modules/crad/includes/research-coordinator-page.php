<?php
/**
 * SMS 2 - Research Coordinator workflow pages.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/notifications.php';
require_once __DIR__ . '/../config/config.php';

requireAuth();

$roleKey = getCurrentUserRoleKey();
if (!in_array($roleKey, ['research_coordinator', 'superadmin'], true)) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$rcPageSlug = $rcPageSlug ?? 'approved-research';
$rcPages = [
    'approved-research' => [
        'title' => 'View Approved Research',
        'description' => 'View approved research groups and titles ready for adviser and panel coordination.',
        'metrics' => [
            ['label' => 'Approved Titles', 'value' => '18', 'icon' => 'fa-clipboard-check', 'tone' => 'green'],
            ['label' => 'Awaiting Adviser', 'value' => '6', 'icon' => 'fa-user-clock', 'tone' => 'amber'],
            ['label' => 'Awaiting Panel', 'value' => '5', 'icon' => 'fa-users', 'tone' => 'purple'],
            ['label' => 'Completed', 'value' => '22', 'icon' => 'fa-check-circle', 'tone' => 'blue'],
        ],
        'actions' => [
            ['label' => 'View Approved List', 'process' => 'view', 'icon' => 'fa-eye', 'class' => 'primary'],
            ['label' => 'Open Assignment', 'process' => 'edit', 'icon' => 'fa-tasks', 'class' => 'ghost'],
        ],
    ],
    'find-contact-adviser' => [
        'title' => 'Find/Contact Adviser',
        'description' => 'Find qualified advisers based on expertise, college, and research agenda fit.',
        'actions' => [
            ['label' => 'Find Adviser', 'process' => 'validate', 'icon' => 'fa-search', 'class' => 'primary'],
            ['label' => 'Contact Adviser', 'process' => 'submit', 'icon' => 'fa-envelope', 'class' => 'ghost'],
        ],
    ],
    'adviser-availability' => [
        'title' => 'Check Adviser Availability',
        'description' => 'Check if advisers are available or already assigned to active research groups.',
        'actions' => [
            ['label' => 'Check Availability', 'process' => 'validate', 'icon' => 'fa-calendar-check', 'class' => 'primary'],
        ],
    ],
    'assign-research-adviser' => [
        'title' => 'Assign Research Adviser',
        'description' => 'Assign the selected research adviser to an approved research group.',
        'actions' => [
            ['label' => 'Assign Adviser', 'process' => 'approve', 'icon' => 'fa-user-plus', 'class' => 'primary'],
        ],
    ],
    'find-contact-panel' => [
        'title' => 'Find/Contact Panel',
        'description' => 'Find qualified panel members based on expertise and panel load.',
        'actions' => [
            ['label' => 'Find Panel', 'process' => 'validate', 'icon' => 'fa-search', 'class' => 'primary'],
            ['label' => 'Contact Panel', 'process' => 'submit', 'icon' => 'fa-envelope', 'class' => 'ghost'],
        ],
    ],
    'panel-availability' => [
        'title' => 'Check Panel Availability',
        'description' => 'Review availability and existing assignments of panel members.',
        'actions' => [
            ['label' => 'Check Panel Availability', 'process' => 'validate', 'icon' => 'fa-calendar-alt', 'class' => 'primary'],
        ],
    ],
    'assign-panel-members' => [
        'title' => 'Assign Panel Members',
        'description' => 'Assign panel members to an approved research group.',
        'actions' => [
            ['label' => 'Assign Panel', 'process' => 'approve', 'icon' => 'fa-user-friends', 'class' => 'primary'],
        ],
    ],
    'send-notifications' => [
        'title' => 'Send Notifications',
        'description' => 'Notify student leaders and research advisers about adviser assignment updates.',
        'actions' => [
            ['label' => 'Send Notifications', 'process' => 'submit', 'icon' => 'fa-paper-plane', 'class' => 'primary'],
        ],
    ],
    'manage-assignments' => [
        'title' => 'View/Manage Assignments',
        'description' => 'View completed adviser assignments and manage follow-up updates.',
        'actions' => [
            ['label' => 'Manage Assignments', 'process' => 'edit', 'icon' => 'fa-tasks', 'class' => 'primary'],
            ['label' => 'Assignment Report', 'process' => 'report', 'icon' => 'fa-file-export', 'class' => 'ghost'],
        ],
    ],
];

if (!isset($rcPages[$rcPageSlug])) {
    $rcPageSlug = 'approved-research';
}

$pageConfig = $rcPages[$rcPageSlug];
$pageTitle = $pageConfig['title'];
$activeModule = 'crad';
$activePage = $rcPageSlug;
$breadcrumbs = [
    ['label' => 'Research Coordinator', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => $pageTitle, 'url' => null],
];

$baseRecords = [
    [
        'reference' => 'RC-2026-018',
        'title' => 'AI-Based Enrollment Prediction Model',
        'owner' => 'BSIT 4A - Cruz Group',
        'detail' => 'Approved title',
        'status' => 'Awaiting Adviser',
        'status_class' => 'pending',
        'updated' => 'Aug 8, 2026',
    ],
    [
        'reference' => 'RC-2026-017',
        'title' => 'IoT Flood Monitoring for Campus Safety',
        'owner' => 'BSIT 4B - Santos Group',
        'detail' => 'Adviser matched',
        'status' => 'Awaiting Panel',
        'status_class' => 'review',
        'updated' => 'Aug 7, 2026',
    ],
    [
        'reference' => 'RC-2026-016',
        'title' => 'Micro-Enterprise Marketing Adaptability',
        'owner' => 'BSBA 4A - Reyes Group',
        'detail' => 'Adviser and panel assigned',
        'status' => 'Completed',
        'status_class' => 'assigned',
        'updated' => 'Aug 6, 2026',
    ],
];

function rcSendNotificationRows(): array
{
    $pdo = cradDb();
    if (!$pdo) {
        return [];
    }

    try {
        smsAssignmentNotificationEnsureSentSchema($pdo);
        $rows = $pdo->query("
            SELECT
                g.id AS research_group_id,
                g.group_number,
                g.group_name,
                COALESCE(NULLIF(g.research_title, ''), p.research_title, t.proposed_title, 'Untitled research') AS research_title,
                COALESCE(NULLIF(g.leader_name, ''), p.rep_name, t.student_name, 'Research Group') AS student_lead,
                COALESCE(NULLIF(g.leader_id, ''), p.rep_id, t.student_id, '') AS student_number,
                (
                    CASE
                        WHEN g.proposal_id IS NOT NULL AND g.proposal_id > 0 THEN (
                            SELECT COUNT(*)
                            FROM proposal_members pm
                            WHERE pm.proposal_id = g.proposal_id
                        )
                        WHEN g.title_approval_id IS NOT NULL AND g.title_approval_id > 0 THEN 1
                        ELSE 0
                    END
                ) AS member_count,
                (
                    SELECT a.adviser_name
                    FROM research_adviser_assignments a
                    WHERE a.assignment_status = 'Assigned'
                      AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
                    ORDER BY a.assigned_at DESC, a.updated_at DESC, a.id DESC
                    LIMIT 1
                ) AS adviser_name,
                (
                    SELECT a.adviser_email
                    FROM research_adviser_assignments a
                    WHERE a.assignment_status = 'Assigned'
                      AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
                    ORDER BY a.assigned_at DESC, a.updated_at DESC, a.id DESC
                    LIMIT 1
                ) AS adviser_email,
                (
                    SELECT COUNT(*)
                    FROM research_adviser_assignments a
                    WHERE a.assignment_status = 'Assigned'
                      AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
                ) AS adviser_count,
                (
                    SELECT COUNT(*)
                    FROM research_adviser_assignments a
                    WHERE a.assignment_status = 'Assigned'
                      AND a.notification_sent_at IS NULL
                      AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
                ) AS adviser_unsent_count,
                COALESCE((
                    SELECT MAX(COALESCE(a.assigned_at, a.updated_at))
                    FROM research_adviser_assignments a
                    WHERE a.assignment_status = 'Assigned'
                      AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
                ), '1000-01-01 00:00:00') AS updated_at,
                COALESCE((
                    SELECT MAX(a.notification_sent_at)
                    FROM research_adviser_assignments a
                    WHERE a.assignment_status = 'Assigned'
                      AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
                ), '1000-01-01 00:00:00') AS notification_sent_at
             FROM research_groups g
             LEFT JOIN research_proposals p ON p.id = g.proposal_id
             LEFT JOIN title_approvals t ON t.id = g.title_approval_id
             WHERE (g.title_approval_id IS NULL OR g.title_approval_id = 0 OR t.id IS NOT NULL)
               AND EXISTS (
                SELECT 1
                FROM research_adviser_assignments a
                WHERE a.assignment_status = 'Assigned'
                  AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
             )
             ORDER BY updated_at DESC, g.id DESC
        ")->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Send notification assignment list failed: ' . $e->getMessage());
        return [];
    }

    return array_map(static function (array $row): array {
        $updated = strtotime((string) ($row['updated_at'] ?? '')) ?: time();
        $memberCount = max(0, (int) ($row['member_count'] ?? 0));
        $hasAdviser = ((int) ($row['adviser_count'] ?? 0)) > 0 && trim((string) ($row['adviser_name'] ?? '')) !== '';
        $sentAt = (string) ($row['notification_sent_at'] ?? '');
        $isSent = $hasAdviser
            && (int) ($row['adviser_unsent_count'] ?? 0) === 0
            && $sentAt !== ''
            && $sentAt !== '1000-01-01 00:00:00';
        if ($isSent) {
            $status = 'Notification Sent';
        } elseif ($hasAdviser) {
            $status = 'Ready to Notify';
        } else {
            $status = 'Adviser Assigned';
        }
        $canSend = $hasAdviser && !$isSent;

        return [
            'research_group_id' => (int) ($row['research_group_id'] ?? 0),
            'research_group' => (string) (($row['group_name'] ?? '') ?: ($row['group_number'] ?? 'Research Group')),
            'group_number' => (string) ($row['group_number'] ?? ''),
            'research_title' => (string) ($row['research_title'] ?? ''),
            'leader' => (string) (($row['student_lead'] ?? '') ?: 'Research Group Leader'),
            'student_number' => (string) ($row['student_number'] ?? ''),
            'adviser' => (string) (($row['adviser_name'] ?? '') ?: 'For assignment'),
            'adviser_email' => (string) ($row['adviser_email'] ?? ''),
            'status' => $status,
            'has_adviser' => $hasAdviser,
            'can_send' => $canSend,
            'is_sent' => $isSent,
            'updated' => date('M j, Y h:i A', $updated),
        ];
    }, $rows);
}

function rcSendNotificationStats(array $rows): array
{
    $readyRows = array_filter($rows, static fn(array $row): bool => !empty($row['can_send']) || !empty($row['is_sent']));
    $adviserKeys = array_unique(array_filter(array_map(static function (array $row): string {
        if (empty($row['has_adviser'])) {
            return '';
        }
        return strtolower((string) (($row['adviser_email'] ?? '') ?: ($row['adviser'] ?? '')));
    }, $rows)));

    return [
        'assignments' => count($readyRows),
        'students' => count($rows),
        'advisers' => count($adviserKeys),
    ];
}

function rcSendAssignmentNotification(string $groupNumber, ?int $userId): array
{
    $pdo = getCradDatabaseConnection();
    return smsMarkResearchAdviserAssignmentNotificationSent($pdo, $groupNumber, $userId);
}

if ($rcPageSlug === 'send-notifications' && (($_POST['ajax'] ?? '') === 'send-assignment-notification')) {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $result = rcSendAssignmentNotification(trim((string) ($_POST['group_number'] ?? '')), getCurrentUserId());
        $rows = rcSendNotificationRows();
        echo json_encode([
            'ok' => true,
            'rows' => $rows,
            'stats' => rcSendNotificationStats($rows),
            'synced_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('M j, Y h:i:s A'),
        ] + $result);
    } catch (Throwable $e) {
        error_log('Send assignment notification failed: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($rcPageSlug === 'send-notifications' && (($_GET['ajax'] ?? '') === 'assignment-notifications')) {
    header('Content-Type: application/json; charset=utf-8');
    $rows = rcSendNotificationRows();
    echo json_encode([
        'ok' => true,
        'rows' => $rows,
        'stats' => rcSendNotificationStats($rows),
        'synced_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('M j, Y h:i:s A'),
    ]);
    exit;
}

$cradProcess = [
    'kicker' => 'Research Coordinator',
    'description' => $pageConfig['description'],
    'metrics' => $pageConfig['metrics'] ?? [
        ['label' => 'Available Advisers', 'value' => '12', 'icon' => 'fa-user-tie', 'tone' => 'blue'],
        ['label' => 'Available Panelists', 'value' => '18', 'icon' => 'fa-users', 'tone' => 'purple'],
        ['label' => 'Pending Assignments', 'value' => '6', 'icon' => 'fa-user-clock', 'tone' => 'amber'],
        ['label' => 'Completed', 'value' => '22', 'icon' => 'fa-check-circle', 'tone' => 'green'],
    ],
    'steps' => [
        ['View Approved Research', 'Open approved research groups or titles ready for coordination.'],
        ['Match Adviser and Panel', 'Find faculty members based on expertise, availability, and load.'],
        ['Assign and Notify', 'Record assignments and notify students, advisers, and panel members.'],
    ],
    'columns' => ['Reference', 'Research Group / Title', 'Coordinator Detail', 'Status', 'Updated'],
    'fields' => ['reference', 'title', 'owner', 'status', 'updated'],
    'records' => $baseRecords,
    'actions' => $pageConfig['actions'],
    'form' => [
        ['label' => 'Research Reference', 'type' => 'text', 'name' => 'reference', 'placeholder' => 'RC-2026-000'],
        ['label' => 'Research Group / Title', 'type' => 'text', 'name' => 'group', 'placeholder' => 'Group or approved title'],
        ['label' => 'Adviser', 'type' => 'select', 'name' => 'adviser', 'options' => [
            'Dr. Roberto M. Santos',
            'Prof. Clara T. Reyes',
            'Dr. Ana L. Mendoza',
            'Dr. Liza M. Torres',
        ]],
        ['label' => 'Panel Member', 'type' => 'select', 'name' => 'panel', 'options' => [
            'Dr. Jose B. Tan',
            'Prof. Nina G. Cruz',
            'Dr. Art C. Lim',
            'Prof. Rhea D. Santos',
        ]],
        ['label' => 'Remarks', 'type' => 'textarea', 'name' => 'remarks', 'placeholder' => 'Assignment notes, availability, notification details...'],
    ],
    'notice' => 'Research Coordinator access is limited to approved research, adviser matching, notifications, and assignment management.',
];

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
if ($rcPageSlug === 'send-notifications') {
    $notificationRows = rcSendNotificationRows();
    $notificationStats = rcSendNotificationStats($notificationRows);
    $notificationEndpoint = BASE_URL . '/modules/crad/pages/send-notifications.php?ajax=assignment-notifications';
    ?>
    <style>
        .rcsn-wrap { display: flex; flex-direction: column; gap: 1rem; }
        .rcsn-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
        .rcsn-head h1 { color: var(--sms-heading, #0f172a); font-size: 1.25rem; font-weight: 850; margin: 0; }
        .rcsn-head p { color: var(--sms-text-muted, #64748b); margin: .25rem 0 0; }
        .rcsn-sync { color: #2454c6; font-size: .78rem; font-weight: 800; white-space: nowrap; }
        .rcsn-stats { display: grid; gap: .85rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .rcsn-stat, .rcsn-card { background: var(--sms-card-bg, #fff); border: 1px solid var(--sms-border, #dbe4f0); border-radius: 8px; box-shadow: var(--sms-shadow-xs, 0 4px 14px rgba(15,23,42,.06)); }
        .rcsn-stat { align-items: center; display: flex; gap: .75rem; padding: .9rem 1rem; }
        .rcsn-stat i { background: rgba(37,99,235,.12); border-radius: 8px; color: #2454c6; display: grid; height: 40px; place-items: center; width: 40px; }
        .rcsn-stat span { color: var(--sms-text-muted, #64748b); display: block; font-size: .72rem; font-weight: 800; text-transform: uppercase; }
        .rcsn-stat strong { color: var(--sms-heading, #0f172a); display: block; font-size: 1.35rem; font-weight: 850; line-height: 1.1; }
        .rcsn-card { overflow: hidden; }
        .rcsn-record-head { align-items: flex-start; border-bottom: 1px solid var(--sms-border, #dbe4f0); display: flex; gap: 1rem; justify-content: space-between; padding: 1rem 1.1rem; }
        .rcsn-record-title { align-items: center; color: var(--sms-text-muted, #64748b); display: flex; flex-wrap: wrap; font-size: .82rem; font-weight: 950; gap: .45rem; letter-spacing: 0; margin: 0; text-transform: uppercase; }
        .rcsn-count { align-items: center; background: #fef3c7; border-radius: 999px; color: #92400e; display: inline-flex; font-size: .74rem; font-weight: 950; justify-content: center; min-height: 24px; min-width: 28px; padding: .2rem .58rem; }
        .rcsn-record-total { color: var(--sms-text-muted, #64748b); display: block; font-size: .8rem; font-weight: 850; margin-top: .55rem; }
        .rcsn-toolbar { align-items: center; border-bottom: 1px solid var(--sms-border, #dbe4f0); display: flex; gap: .7rem; justify-content: space-between; padding: .9rem 1rem; }
        .rcsn-toolbar h2 { color: var(--sms-heading, #0f172a); font-size: 1rem; font-weight: 850; margin: 0; }
        .rcsn-search { background: var(--sms-surface-muted, #f8fafc); border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; min-height: 38px; padding: .45rem .75rem; width: min(360px, 100%); }
        .rcsn-toolbar .rcsn-search { flex: 1 1 auto; width: auto; }
        .rcsn-filter { background: var(--sms-surface-muted, #f8fafc); border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; min-height: 38px; padding: .45rem .75rem; width: min(170px, 100%); }
        .rcsn-table { margin: 0; width: 100%; }
        .rcsn-table th { background: var(--sms-surface-muted, #f8fafc); color: var(--sms-text-muted, #64748b); font-size: .73rem; font-weight: 850; padding: .8rem 1rem; text-transform: uppercase; }
        .rcsn-table td { border-top: 1px solid var(--sms-border, #e2e8f0); color: var(--sms-text, #334155); padding: .9rem 1rem; vertical-align: top; }
        .rcsn-title { color: var(--sms-heading, #0f172a); display: block; font-weight: 850; }
        .rcsn-muted { color: var(--sms-text-muted, #64748b); display: block; font-size: .8rem; margin-top: .15rem; }
        .rcsn-badge { background: #d1fae5; border-radius: 999px; color: #047857; display: inline-flex; font-size: .75rem; font-weight: 850; padding: .25rem .65rem; white-space: nowrap; }
        .rcsn-action { align-items: center; background: #2454c6; border: 1px solid #2454c6; border-radius: 8px; color: #fff; display: inline-flex; font-size: .78rem; font-weight: 850; gap: .35rem; min-height: 34px; padding: .4rem .7rem; white-space: nowrap; }
        .rcsn-action[disabled] { background: #e2e8f0; border-color: #e2e8f0; color: #64748b; cursor: not-allowed; }
        .rcsn-notice { border-radius: 8px; display: none; font-size: .84rem; font-weight: 800; margin: 0 0 1rem; padding: .75rem .9rem; }
        .rcsn-notice.show { display: block; }
        .rcsn-notice.ok { background: #d1fae5; border: 1px solid #a7f3d0; color: #047857; }
        .rcsn-notice.error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .rcsn-empty { color: var(--sms-text-muted, #64748b); padding: 2rem 1rem; text-align: center; }
        @media (max-width: 980px) { .rcsn-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .rcsn-head, .rcsn-record-head, .rcsn-toolbar { align-items: flex-start; flex-direction: column; } .rcsn-filter, .rcsn-search { width: 100%; } }
        @media (max-width: 560px) { .rcsn-stats { grid-template-columns: 1fr; } }
    </style>
    <div class="rcsn-wrap" data-rcsn-root data-endpoint="<?= htmlspecialchars($notificationEndpoint) ?>">
        <header class="rcsn-head">
            <div>
                <h1>Send Notifications</h1>
                <p>Live list of assigned research groups ready for student and adviser notification.</p>
            </div>
            <div class="rcsn-sync" data-rcsn-sync>Syncing...</div>
        </header>
        <section class="rcsn-stats">
            <div class="rcsn-stat"><?= smsIcon('check-circle') ?><div><span>Ready Assignments</span><strong data-rcsn-stat="assignments"><?= (int) $notificationStats['assignments'] ?></strong></div></div>
            <div class="rcsn-stat"><?= smsIcon('flask') ?><div><span>Research Groups</span><strong data-rcsn-stat="students"><?= (int) $notificationStats['students'] ?></strong></div></div>
            <div class="rcsn-stat"><?= smsIcon('user-tie') ?><div><span>Advisers</span><strong data-rcsn-stat="advisers"><?= (int) $notificationStats['advisers'] ?></strong></div></div>
        </section>
        <div class="rcsn-notice" data-rcsn-notice></div>
        <section class="rcsn-card">
            <div class="rcsn-record-head">
                <div>
                    <h2 class="rcsn-record-title"><?= smsIcon('paper-plane') ?>Assigned Adviser Notification List <span class="rcsn-count" data-rcsn-record-badge><?= count($notificationRows) ?></span></h2>
                    <span class="rcsn-record-total" data-rcsn-record-count><?= count($notificationRows) ?> record<?= count($notificationRows) === 1 ? '' : 's' ?></span>
                </div>
                <div class="rcsn-sync" data-rcsn-record-sync>Syncing...</div>
            </div>
            <div class="rcsn-toolbar">
                <input class="rcsn-search" type="search" data-rcsn-search placeholder="Search group, title, adviser...">
                <select class="rcsn-filter" data-rcsn-status>
                    <option value="">All Status</option>
                    <option value="ready">Ready</option>
                    <option value="sent">Sent</option>
                </select>
            </div>
            <div class="table-responsive">
                <table class="rcsn-table">
                    <thead>
                        <tr>
                            <th>Research Group / Title</th>
                            <th>Student</th>
                            <th>Research Adviser</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody data-rcsn-rows></tbody>
                </table>
            </div>
            <div class="rcsn-empty" data-rcsn-empty hidden>No assigned advisers yet.</div>
        </section>
    </div>
    <script>
    (() => {
        const root = document.querySelector('[data-rcsn-root]');
        if (!root) return;
        const endpoint = root.dataset.endpoint;
        const rowsBody = root.querySelector('[data-rcsn-rows]');
        const empty = root.querySelector('[data-rcsn-empty]');
        const search = root.querySelector('[data-rcsn-search]');
        const statusFilter = root.querySelector('[data-rcsn-status]');
        const sync = root.querySelector('[data-rcsn-sync]');
        const recordSync = root.querySelector('[data-rcsn-record-sync]');
        const recordBadge = root.querySelector('[data-rcsn-record-badge]');
        const recordCount = root.querySelector('[data-rcsn-record-count]');
        const notice = root.querySelector('[data-rcsn-notice]');
        const stats = root.querySelectorAll('[data-rcsn-stat]');
        let rows = <?= json_encode($notificationRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        let refreshing = false;
        let timer = null;
        const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[char]);
        const renderStats = (data) => stats.forEach((node) => { node.textContent = String(data?.[node.dataset.rcsnStat] ?? 0); });
        const showNotice = (message, type = 'ok') => {
            if (!notice) return;
            notice.textContent = message || '';
            notice.className = 'rcsn-notice show ' + type;
            window.setTimeout(() => {
                notice.className = 'rcsn-notice';
                notice.textContent = '';
            }, 3500);
        };
        const filteredRows = () => {
            const term = (search?.value || '').trim().toLowerCase();
            const status = (statusFilter?.value || '').trim().toLowerCase();
            return rows.filter((row) => [
                row.research_group, row.group_number, row.research_title, row.leader, row.student_number,
                row.adviser, row.adviser_email, row.status
            ].join(' ').toLowerCase().includes(term) && (!status || String(row.status || '').toLowerCase().includes(status)));
        };
        const render = () => {
            const visible = filteredRows();
            if (recordBadge) recordBadge.textContent = String(visible.length);
            if (recordCount) recordCount.textContent = `${visible.length} record${visible.length === 1 ? '' : 's'}`;
            rowsBody.innerHTML = visible.map((row) => `
                <tr>
                    <td><span class="rcsn-title">${esc(row.research_group || 'Research Group')}</span><span class="rcsn-muted">${esc(row.group_number || '')}</span><span class="rcsn-muted">${esc(row.research_title || '')}</span></td>
                    <td><span class="rcsn-title">${esc(row.leader || 'Student')}</span><span class="rcsn-muted">${esc(row.student_number || '')}</span></td>
                    <td><span class="rcsn-title">${esc(row.adviser || 'Research Adviser')}</span><span class="rcsn-muted">${esc(row.adviser_email || '')}</span></td>
                    <td><span class="rcsn-badge">${esc(row.status || 'Ready to Notify')}</span></td>
                    <td>${esc(row.updated || '')}</td>
                    <td>
                        <button type="button" class="rcsn-action" data-rcsn-send="${esc(row.group_number || '')}" ${row.can_send ? '' : 'disabled'}>
                            ${window.smsIconHtml ? window.smsIconHtml(row.is_sent ? 'check' : 'paper-plane') : ''}${row.is_sent ? 'Sent' : 'Send Notification'}
                        </button>
                    </td>
                </tr>
            `).join('');
            empty.hidden = visible.length !== 0;
            empty.textContent = rows.length === 0 ? 'No assigned advisers yet.' : 'No records match your search.';
        };
        rowsBody?.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-rcsn-send]');
            if (!button || button.disabled) return;
            button.disabled = true;
            button.innerHTML = '<?= smsIcon('spinner', ['class' => 'fa-spin']) ?>Sending';
            try {
                const form = new FormData();
                form.append('ajax', 'send-assignment-notification');
                form.append('group_number', button.dataset.rcsnSend || '');
                const res = await fetch(window.location.href, {
                    method: 'POST',
                    body: form,
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                    credentials: 'same-origin'
                });
                if (!res.ok) throw new Error('Failed to send notification.');
                const data = await res.json();
                if (!data.ok) throw new Error(data.error || 'Failed to send notification.');
                rows = Array.isArray(data.rows) ? data.rows : [];
                renderStats(data.stats || {});
                render();
                const syncText = 'Synced ' + (data.synced_at || 'just now');
                sync.textContent = syncText;
                if (recordSync) recordSync.textContent = syncText;
                showNotice(data.message || 'Notification sent.', 'ok');
            } catch (error) {
                showNotice(error.message || 'Failed to send notification.', 'error');
                render();
            }
        });
        const refresh = async () => {
            if (refreshing) return;
            refreshing = true;
            try {
                const url = new URL(endpoint, window.location.href);
                url.searchParams.set('_', Date.now().toString());
                const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }, cache: 'no-store', credentials: 'same-origin' });
                if (!res.ok) throw new Error('Sync failed');
                const data = await res.json();
                if (!data.ok) throw new Error('Sync failed');
                rows = Array.isArray(data.rows) ? data.rows : [];
                renderStats(data.stats || {});
                render();
                const syncText = 'Synced ' + (data.synced_at || 'just now');
                sync.textContent = syncText;
                if (recordSync) recordSync.textContent = syncText;
            } catch (error) {
                sync.textContent = 'Sync paused';
                if (recordSync) recordSync.textContent = 'Sync paused';
            } finally {
                refreshing = false;
            }
        };
        search?.addEventListener('input', render);
        statusFilter?.addEventListener('change', render);
        renderStats(<?= json_encode($notificationStats, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
        render();
        refresh();
        timer = window.setInterval(refresh, 5000);
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                if (timer) window.clearInterval(timer);
                timer = null;
                return;
            }
            if (timer) window.clearInterval(timer);
            refresh();
            timer = window.setInterval(refresh, 5000);
        });
    })();
    </script>
    <?php
} else {
require ROOT_PATH . '/includes/crad-module-process.php';
}
require_once ROOT_PATH . '/includes/layout-end.php';
