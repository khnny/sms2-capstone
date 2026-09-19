<?php
/**
 * Shared CRAD assignment notifications.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../modules/crad/config/config.php';
require_once __DIR__ . '/../modules/crad/includes/chapter-evaluation-workflow.php';

function smsAssignmentNotificationEnsureSentSchema(PDO $crad): void
{
    foreach (['research_adviser_assignments'] as $table) {
        try {
            $sentAt = $crad->query("SHOW COLUMNS FROM {$table} LIKE 'notification_sent_at'")->fetch();
            if (!$sentAt) {
                $crad->exec("ALTER TABLE {$table} ADD notification_sent_at DATETIME DEFAULT NULL AFTER updated_at");
            }
            $sentBy = $crad->query("SHOW COLUMNS FROM {$table} LIKE 'notification_sent_by'")->fetch();
            if (!$sentBy) {
                $crad->exec("ALTER TABLE {$table} ADD notification_sent_by INT UNSIGNED DEFAULT NULL AFTER notification_sent_at");
            }
        } catch (Throwable $e) {
            error_log('Assignment notification sent schema check failed for ' . $table . ': ' . $e->getMessage());
        }
    }
}

function smsNotificationsEnsureSchema(?PDO $crad = null): void
{
    return;
}

function smsNotificationRecipientKey(array $recipient): string
{
    $userId = (int) ($recipient['id'] ?? $recipient['user_id'] ?? 0);
    if ($userId > 0) {
        return 'user:' . $userId;
    }

    $email = strtolower(trim((string) ($recipient['email'] ?? '')));
    if ($email !== '') {
        return 'email:' . $email;
    }

    return 'role:' . strtolower(trim((string) ($recipient['role_key'] ?? '')));
}

function smsNotificationAppUrl(string $path): string
{
    $baseUrl = defined('BASE_URL') ? (string) BASE_URL : '';
    if ($baseUrl === '' && (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg')) {
        $baseUrl = '/' . basename((string) ROOT_PATH);
    }

    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

function smsNotificationViewUrl(array $params): string
{
    return smsNotificationAppUrl('/notifications/view.php?' . http_build_query($params));
}

function smsNotificationUrlForRole(string $roleKey, int $notificationId): string
{
    return smsNotificationViewUrl(['id' => $notificationId]);
}

function smsCradInsertNotification(PDO $crad, array $recipient, string $batchKey, string $title, string $body): void
{
    return;
}

function smsCurrentUserNotificationWhere(): array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $role = (string) ($_SESSION['user_role_key'] ?? '');
    $email = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));

    return [
        'sql' => '(recipient_user_id = :user_id
            OR (:email_gate <> "" AND recipient_email = :email_value)
            OR (:role_gate <> "" AND recipient_user_id IS NULL AND recipient_email = "" AND recipient_role = :role_value))',
        'params' => [
            ':user_id' => $userId,
            ':email_gate' => $email,
            ':email_value' => $email,
            ':role_gate' => $role,
            ':role_value' => $role,
        ],
    ];
}

function smsDeleteStoredCradAssignmentNotifications(PDO $crad): void
{
    return;
}

function smsMarkResearchAdviserAssignmentNotificationSent(PDO $crad, string $groupNumber, ?int $sentBy = null): array
{
    $groupNumber = trim($groupNumber);
    if ($groupNumber === '') {
        throw new RuntimeException('Research group is required.');
    }

    smsAssignmentNotificationEnsureSentSchema($crad);

    $groupStmt = $crad->prepare("
        SELECT g.id, g.proposal_id, g.group_number
        FROM research_groups g
        LEFT JOIN title_approvals t ON t.id = g.title_approval_id
        WHERE g.group_number = :group_number
          AND (g.title_approval_id IS NULL OR g.title_approval_id = 0 OR t.id IS NOT NULL)
        LIMIT 1
    ");
    $groupStmt->execute([':group_number' => $groupNumber]);
    $group = $groupStmt->fetch();
    if (!$group) {
        throw new RuntimeException('Research group not found.');
    }

    $params = [
        ':research_group_id' => (int) ($group['id'] ?? 0),
        ':group_number_gate' => (string) ($group['group_number'] ?? ''),
        ':group_number_match' => (string) ($group['group_number'] ?? ''),
        ':proposal_id' => (int) ($group['proposal_id'] ?? 0),
    ];

    $adviserStmt = $crad->prepare("
        SELECT COUNT(*)
        FROM research_adviser_assignments a
        WHERE a.assignment_status = 'Assigned'
          AND (
            a.research_group_id = :research_group_id
            OR (:group_number_gate <> '' AND a.group_number = :group_number_match)
            OR a.proposal_id = :proposal_id
          )
    ");
    $adviserStmt->execute($params);
    if ((int) $adviserStmt->fetchColumn() < 1) {
        throw new RuntimeException('Assign a research adviser before sending notifications.');
    }

    $crad->prepare("
        UPDATE research_adviser_assignments a
           SET a.notification_sent_at = NOW(),
               a.notification_sent_by = :sent_by,
               a.updated_at = NOW()
         WHERE a.assignment_status = 'Assigned'
           AND a.notification_sent_at IS NULL
           AND (
            a.research_group_id = :research_group_id
            OR (:group_number_gate <> '' AND a.group_number = :group_number_match)
            OR a.proposal_id = :proposal_id
           )
    ")->execute($params + [
        ':sent_by' => $sentBy ?: null,
    ]);

    return ['message' => 'Notification sent to the student and research adviser.'];
}

function smsCurrentUserNotifications(int $limit = 8): array
{
    $crad = cradDb();
    if (!$crad) {
        return [];
    }

    try {
        $table = $crad->query("SHOW TABLES LIKE 'chapter_evaluation_notifications'")->fetchColumn();
        if (!$table) {
            return [];
        }

        $where = smsCurrentUserNotificationWhere();
        $role = (string) ($_SESSION['user_role_key'] ?? '');
        $studentChapterFilter = '';
        $evaluatorChapterFilter = '';
        $registeredGroup = null;
        if ($role === 'student') {
            $registeredGroup = chapterRegisteredStudentGroup($crad);
            if (!$registeredGroup) {
                return [];
            }
            $studentChapterFilter = "
               AND cs.id IS NOT NULL
               AND cs.research_group_id = :registered_group_id
               AND cs.id = (
                    SELECT latest_cs.id
                    FROM chapter_submissions latest_cs
                    WHERE latest_cs.research_group_id = cs.research_group_id
                      AND latest_cs.chapter_number = cs.chapter_number
                    ORDER BY latest_cs.version_number DESC, latest_cs.id DESC
                    LIMIT 1
               )
               AND (
                    (n.type = 'submitted' AND cs.status = 'Submitted')
                 OR (n.type = 'new_submission' AND cs.status = 'Submitted')
                 OR (n.type = 'under_review' AND cs.status = 'Under Review')
                 OR (n.type = 'needs_revision' AND cs.status = 'Needs Revision')
                 OR (n.type = 'accepted' AND cs.status = 'Accepted')
                 OR (n.type NOT IN ('submitted', 'new_submission', 'under_review', 'needs_revision', 'accepted'))
               )";
        }
        if ($role === 'grammarian') {
            $evaluatorChapterFilter = "
               AND (
                    n.type <> 'new_submission'
                 OR (
                        cs.id IS NOT NULL
                    AND cs.status IN ('Submitted','Under Review')
                    AND ce.id IS NULL
                    AND " . chapterCurrentLatestSubmissionSql('cs') . "
                    AND " . chapterRegistryGroupGateSql('rg') . "
                    AND NOT EXISTS (
                        SELECT 1
                        FROM chapter_evaluation_notifications newer_n
                        WHERE newer_n.submission_id = n.submission_id
                          AND newer_n.type = n.type
                          AND COALESCE(newer_n.recipient_user_id, 0) = COALESCE(n.recipient_user_id, 0)
                          AND COALESCE(newer_n.recipient_role, '') = COALESCE(n.recipient_role, '')
                          AND COALESCE(newer_n.recipient_email, '') = COALESCE(n.recipient_email, '')
                          AND newer_n.id > n.id
                    )
                 )
               )";
        }
        $stmt = $crad->prepare(
            "SELECT n.id, n.event_key, n.type, n.title, n.body, n.url, n.is_read, n.created_at
             FROM chapter_evaluation_notifications n
             LEFT JOIN chapter_submissions cs ON cs.id = n.submission_id
             LEFT JOIN research_groups rg ON rg.id = cs.research_group_id
             LEFT JOIN chapter_evaluations ce ON ce.submission_id = cs.id
             WHERE {$where['sql']}
             {$studentChapterFilter}
             {$evaluatorChapterFilter}
             ORDER BY n.created_at DESC, n.id DESC
             LIMIT :limit"
        );
        foreach ($where['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        if ($registeredGroup) {
            $stmt->bindValue(':registered_group_id', (int) $registeredGroup['id'], PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];

        $panelTable = $crad->query("SHOW TABLES LIKE 'panel_assignment_notifications'")->fetchColumn();
        if ($panelTable) {
            $panelRegistryFilter = function_exists('cradOfficialRegistryGroupWhereSql')
                ? "AND (
                    research_group_id IS NULL
                    OR EXISTS (
                        SELECT 1
                        FROM research_groups panel_rg
                        WHERE panel_rg.id = panel_assignment_notifications.research_group_id
                          AND " . cradOfficialRegistryGroupWhereSql('panel_rg') . "
                    )
                )"
                : '';
            $panelStmt = $crad->prepare(
                "SELECT id, event_key, 'panel_assignment' AS type, title, body, url, is_read, created_at
                 FROM panel_assignment_notifications
                 WHERE {$where['sql']}
                 {$panelRegistryFilter}
                 ORDER BY created_at DESC, id DESC
                 LIMIT :limit"
            );
            foreach ($where['params'] as $key => $value) {
                $panelStmt->bindValue($key, $value);
            }
            $panelStmt->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
            $panelStmt->execute();
            $rows = array_merge($rows, $panelStmt->fetchAll() ?: []);
            usort($rows, static function (array $a, array $b): int {
                return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            });
            $rows = array_slice($rows, 0, max(1, min(50, $limit)));
        }

        $clearanceTable = $crad->query("SHOW TABLES LIKE 'research_clearance_notifications'")->fetchColumn();
        if ($clearanceTable) {
            $clearanceStmt = $crad->prepare(
                "SELECT id, event_key, type, title, body, url, is_read, created_at
                 FROM research_clearance_notifications
                 WHERE {$where['sql']}
                 ORDER BY created_at DESC, id DESC
                 LIMIT :limit"
            );
            foreach ($where['params'] as $key => $value) {
                $clearanceStmt->bindValue($key, $value);
            }
            $clearanceStmt->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
            $clearanceStmt->execute();
            $rows = array_merge($rows, $clearanceStmt->fetchAll() ?: []);
            usort($rows, static function (array $a, array $b): int {
                return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            });
            $rows = array_slice($rows, 0, max(1, min(50, $limit)));
        }
    } catch (Throwable $e) {
        error_log('Chapter notification load failed: ' . $e->getMessage());
        return [];
    }

    return array_map(static function (array $row): array {
        $type = (string) ($row['type'] ?? '');
        $isPanel = $type === 'panel_assignment';
        $isClearance = in_array($type, ['sent_to_adviser', 'adviser_signed', 'mis_aa_signed', 'clearance_done', 'payment_approved'], true);
        return [
            'id' => $isPanel ? -1 * (int) $row['id'] : ($isClearance ? (800000000 + (int) $row['id']) : (int) $row['id']),
            'batch_key' => (string) ($row['event_key'] ?? ''),
            'icon' => $isClearance ? 'fa-stamp' : ($isPanel ? 'fa-user-friends' : 'fa-file-alt'),
            'status' => ((int) ($row['is_read'] ?? 0) === 1) ? 'read' : 'unread',
            'title' => (string) ($row['title'] ?? 'Notification'),
            'body' => (string) ($row['body'] ?? ''),
            'url' => (string) ($row['url'] ?? '#'),
            'created_at' => (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
        ];
    }, $rows);
}

function smsBackfillCradAssignmentNotifications(PDO $crad): void
{
    return;
}

function smsBackfillCradAssignmentNotificationForGroup(PDO $crad, array $group, array $users): void
{
    return;
}

function smsMarkCurrentUserNotificationRead(int $notificationId): void
{
    if ($notificationId < -2000000) {
        if (!function_exists('grantMarkProposalNotificationRead')) {
            require_once ROOT_PATH . '/modules/crad/includes/grant-evaluation-helpers.php';
        }
        grantMarkProposalNotificationRead($notificationId);
        return;
    }

    if ($notificationId <= 0) {
        return;
    }

    $crad = cradDb();
    if (!$crad) {
        return;
    }

    if ($notificationId >= 800000000 && $notificationId < 900000000) {
        try {
            $where = smsCurrentUserNotificationWhere();
            $stmt = $crad->prepare(
                "UPDATE research_clearance_notifications
                 SET is_read = 1
                 WHERE id = :notification_id
                   AND {$where['sql']}
                 LIMIT 1"
            );
            $stmt->bindValue(':notification_id', $notificationId - 800000000, PDO::PARAM_INT);
            foreach ($where['params'] as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('Clearance notification mark-read failed: ' . $e->getMessage());
        }
        return;
    }

    try {
        $table = $crad->query("SHOW TABLES LIKE 'chapter_evaluation_notifications'")->fetchColumn();
        if (!$table) {
            return;
        }
        $where = smsCurrentUserNotificationWhere();
        $stmt = $crad->prepare(
            "UPDATE chapter_evaluation_notifications
             SET is_read = 1
             WHERE id = :notification_id
               AND {$where['sql']}
             LIMIT 1"
        );
        $stmt->bindValue(':notification_id', $notificationId, PDO::PARAM_INT);
        foreach ($where['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
    } catch (Throwable $e) {
        error_log('Chapter notification mark-read failed: ' . $e->getMessage());
    }
}

function smsMarkCurrentUserSyntheticNotificationRead(string $batchKey): void
{
    $batchKey = strtolower(trim($batchKey));
    if ($batchKey === '') {
        return;
    }

    if (preg_match('/^research-group:(\d+)$/', $batchKey, $matches)) {
        $readIds = array_map('intval', (array) ($_SESSION['read_research_group_notifications'] ?? []));
        $readIds[] = (int) $matches[1];
        $_SESSION['read_research_group_notifications'] = array_values(array_unique(array_filter($readIds)));
        return;
    }

    if (preg_match('/^returned-title-approval:(\d+)$/', $batchKey, $matches)) {
        $readIds = array_map('intval', (array) ($_SESSION['read_returned_title_approvals'] ?? []));
        $readIds[] = (int) $matches[1];
        $_SESSION['read_returned_title_approvals'] = array_values(array_unique(array_filter($readIds)));
        return;
    }

    if (strpos($batchKey, 'returned-proposal:') === 0) {
        $readKeys = array_map(
            static fn($key): string => strtolower(trim((string) $key)),
            (array) ($_SESSION['read_returned_proposals'] ?? [])
        );
        $readKeys[] = $batchKey;
        $_SESSION['read_returned_proposals'] = array_values(array_unique(array_filter($readKeys)));
        return;
    }

    if (strpos($batchKey, 'live-assignment:') === 0) {
        $readKeys = array_map(
            static fn($key): string => strtolower(trim((string) $key)),
            (array) ($_SESSION['read_live_assignment_notifications'] ?? [])
        );
        $readKeys[] = $batchKey;
        $_SESSION['read_live_assignment_notifications'] = array_values(array_unique(array_filter($readKeys)));
        return;
    }

    if (strpos($batchKey, 'grant-proposal:') === 0) {
        $crad = cradDb();
        if (!$crad) {
            return;
        }
        try {
            if (!function_exists('grantEnsureEvaluationTables')) {
                require_once ROOT_PATH . '/modules/crad/includes/grant-evaluation-helpers.php';
            }
            grantEnsureEvaluationTables($crad);
            $where = smsCurrentUserNotificationWhere();
            $stmt = $crad->prepare(
                "UPDATE grant_proposal_notifications
                 SET is_read = 1
                 WHERE event_key = :event_key
                   AND {$where['sql']}
                 LIMIT 1"
            );
            $stmt->bindValue(':event_key', $batchKey);
            foreach ($where['params'] as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('Grant proposal notification mark-read failed: ' . $e->getMessage());
        }
        return;
    }

    if (strpos($batchKey, 'evaluator:new:') === 0 || strpos($batchKey, 'student:') === 0) {
        $crad = cradDb();
        if (!$crad) {
            return;
        }
        try {
            $where = smsCurrentUserNotificationWhere();
            $stmt = $crad->prepare(
                "UPDATE chapter_evaluation_notifications
                 SET is_read = 1
                 WHERE event_key = :event_key
                   AND {$where['sql']}
                 LIMIT 1"
            );
            $stmt->bindValue(':event_key', $batchKey);
            foreach ($where['params'] as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
        } catch (Throwable $e) {
            error_log('Chapter synthetic notification mark-read failed: ' . $e->getMessage());
        }
    }
}

function smsMarkNotificationFromRequest(): void
{
    smsMarkCurrentUserNotificationRead((int) ($_GET['assignment_notification'] ?? 0));
}

function smsCurrentUserNotificationById(int $notificationId): ?array
{
    return null;
}

function smsNotificationBodyDetails(string $body, string $title = ''): array
{
    $lines = preg_split('/\R+/', trim($body)) ?: [];
    $details = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || ($title !== '' && strcasecmp($line, $title) === 0)) {
            continue;
        }
        $parts = explode(':', $line, 2);
        $details[] = [
            'label' => trim($parts[0] ?? 'Detail'),
            'value' => trim($parts[1] ?? $line),
        ];
    }
    return $details;
}

function smsNotificationPreviewText(string $body, int $limit = 64): string
{
    $lines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $body) ?: [])));
    if (isset($lines[0]) && strcasecmp($lines[0], 'Assignment Notification') === 0) {
        array_shift($lines);
    }

    $preview = preg_replace('/\s+/', ' ', implode(' - ', $lines)) ?: '';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($preview) > $limit ? mb_substr($preview, 0, $limit - 3) . '...' : $preview;
    }

    return strlen($preview) > $limit ? substr($preview, 0, $limit - 3) . '...' : $preview;
}

function smsRenderCurrentAssignmentNotificationPanel(): void
{
    $notification = smsCurrentUserNotificationById((int) ($_GET['assignment_notification'] ?? 0));
    if (!$notification) {
        return;
    }

    $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $created = strtotime((string) ($notification['created_at'] ?? '')) ?: time();
    $details = smsNotificationBodyDetails((string) ($notification['body'] ?? ''), (string) ($notification['title'] ?? ''));
    ?>
    <style>
        .sms-assignment-notification {
            border: 1px solid #d9e4f2;
            border-left: 4px solid #2454c6;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
            margin: 0 0 1rem;
            padding: .85rem 1rem;
        }
        .sms-assignment-notification__head {
            align-items: center;
            display: flex;
            gap: .65rem;
            justify-content: space-between;
            margin-bottom: .7rem;
        }
        .sms-assignment-notification__title {
            align-items: center;
            color: #172033;
            display: flex;
            font-weight: 800;
            gap: .5rem;
            min-width: 0;
        }
        .sms-assignment-notification__time {
            color: #64748b;
            flex: 0 0 auto;
            font-size: .82rem;
            white-space: nowrap;
        }
        .sms-assignment-notification__grid {
            display: grid;
            gap: .55rem;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
        .sms-assignment-notification__item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            min-width: 0;
            padding: .55rem .65rem;
        }
        .sms-assignment-notification__label {
            color: #64748b;
            display: block;
            font-size: .74rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .sms-assignment-notification__value {
            color: #0f172a;
            display: block;
            font-weight: 700;
            overflow-wrap: anywhere;
        }
        @media (max-width: 640px) {
            .sms-assignment-notification__head {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
    <section class="sms-assignment-notification" aria-label="Assignment notification details">
        <div class="sms-assignment-notification__head">
            <div class="sms-assignment-notification__title">
                <?= smsIcon($notification['icon'] ?: 'user-check') ?>
                <span>Status Dashboard: <?= $escape($notification['title'] ?: 'Assignment Notification') ?></span>
            </div>
            <time class="sms-assignment-notification__time"><?= $escape(date('M j, Y h:i A', $created)) ?></time>
        </div>
        <div class="sms-assignment-notification__grid">
            <?php foreach ($details as $detail): ?>
                <div class="sms-assignment-notification__item">
                    <span class="sms-assignment-notification__label"><?= $escape($detail['label']) ?></span>
                    <span class="sms-assignment-notification__value"><?= $escape($detail['value']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
}

function smsCurrentUserCanReceiveAssignmentNotification(PDO $crad, array $row): bool
{
    $role = (string) ($_SESSION['user_role_key'] ?? '');
    if (in_array($role, ['superadmin', 'research_coordinator', 'crad_officer'], true)) {
        return false;
    }

    $email = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($role === 'adviser') {
        $adviserUserId = (int) ($row['adviser_user_id'] ?? 0);
        if ($adviserUserId > 0) {
            return $userId > 0 && $userId === $adviserUserId;
        }

        return $email !== '' && $email === strtolower(trim((string) ($row['adviser_email'] ?? '')));
    }

    if ($role === 'student') {
        $studentId = trim((string) ($_SESSION['student_id'] ?? ''));
        $studentName = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
        $rowStudentIds = array_filter([
            trim((string) ($row['leader_id'] ?? '')),
            trim((string) ($row['rep_id'] ?? '')),
            trim((string) ($row['title_student_id'] ?? '')),
        ]);
        $rowEmails = array_filter([
            strtolower(trim((string) ($row['leader_email'] ?? ''))),
            strtolower(trim((string) ($row['rep_email'] ?? ''))),
        ]);
        $rowNames = array_filter([
            strtolower(trim((string) ($row['leader_name'] ?? ''))),
            strtolower(trim((string) ($row['rep_name'] ?? ''))),
            strtolower(trim((string) ($row['title_student_name'] ?? ''))),
        ]);
        $rowUserIds = array_filter([
            (int) ($row['submitted_by_user'] ?? 0),
            (int) ($row['title_student_user_id'] ?? 0),
        ]);

        return ($studentId !== '' && in_array($studentId, $rowStudentIds, true))
            || ($email !== '' && in_array($email, $rowEmails, true))
            || ($studentName !== '' && in_array($studentName, $rowNames, true))
            || ($userId > 0 && in_array($userId, $rowUserIds, true));
    }

    return false;
}

function smsCurrentUserAssignmentNotifications(int $limit = 8): array
{
    $crad = cradDb();
    if (!$crad) {
        return [];
    }

    try {
        smsAssignmentNotificationEnsureSentSchema($crad);
        smsDeleteStoredCradAssignmentNotifications($crad);
        $rows = $crad->query("
            SELECT
                g.id AS research_group_id,
                g.proposal_id,
                g.proposal_number,
                g.group_number,
                g.group_name,
                COALESCE(NULLIF(g.research_title, ''), p.research_title, t.proposed_title, '') AS research_title,
                g.leader_id,
                g.leader_name,
                g.leader_email,
                p.rep_id,
                p.rep_name,
                p.rep_email,
                p.submitted_by_user,
                t.student_id AS title_student_id,
                t.student_name AS title_student_name,
                t.student_user_id AS title_student_user_id,
                a.adviser_user_id,
                a.adviser_name,
                a.adviser_email,
                COALESCE(a.notification_sent_at, '1000-01-01 00:00:00') AS completed_at
             FROM research_groups g
             LEFT JOIN research_proposals p ON p.id = g.proposal_id
             LEFT JOIN title_approvals t ON t.id = g.title_approval_id
             INNER JOIN research_adviser_assignments a
                ON a.assignment_status = 'Assigned'
               AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
             WHERE a.notification_sent_at IS NOT NULL
               AND (g.title_approval_id IS NULL OR g.title_approval_id = 0 OR t.id IS NOT NULL)
               AND NOT EXISTS (
                    SELECT 1
                    FROM research_adviser_assignments pending_a
                    WHERE pending_a.assignment_status = 'Assigned'
                      AND pending_a.notification_sent_at IS NULL
                      AND (
                            pending_a.research_group_id = g.id
                         OR pending_a.group_number = g.group_number
                         OR pending_a.proposal_id = g.proposal_id
                      )
               )
             GROUP BY
                g.id, g.proposal_id, g.proposal_number, g.group_number, g.group_name, g.research_title,
                p.research_title, t.proposed_title,
                g.leader_id, g.leader_name, g.leader_email,
                p.rep_id, p.rep_name, p.rep_email, p.submitted_by_user,
                t.student_id, t.student_name, t.student_user_id,
                a.adviser_user_id, a.adviser_name, a.adviser_email, a.notification_sent_at
             ORDER BY completed_at DESC, g.id DESC
             LIMIT 50
        ")->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Live assignment notification load failed: ' . $e->getMessage());
        return [];
    }

    $items = [];
    $readAssignmentKeys = array_map(
        static fn($key): string => strtolower(trim((string) $key)),
        (array) ($_SESSION['read_live_assignment_notifications'] ?? [])
    );
    foreach ($rows as $row) {
        if (!smsCurrentUserCanReceiveAssignmentNotification($crad, $row)) {
            continue;
        }

        $created = strtotime((string) ($row['completed_at'] ?? '')) ?: time();
        $groupLabel = (string) (($row['group_name'] ?? '') ?: ($row['group_number'] ?? 'Research Group'));
        $batchKey = 'live-assignment:' . (string) (($row['group_number'] ?? '') ?: ($row['proposal_number'] ?? $row['proposal_id'] ?? ''));
        $isUnread = !in_array(strtolower($batchKey), $readAssignmentKeys, true);
        $body = "Assignment Notification\n"
            . 'Research Group: ' . $groupLabel . "\n"
            . 'Adviser: ' . (string) ($row['adviser_name'] ?? 'Research Adviser') . "\n"
            . 'Date: ' . date('M j, Y', $created) . "\n"
            . 'Time: ' . date('g:i A', $created);

        $items[] = [
            'id' => 0,
            'batch_key' => $batchKey,
            'source_group' => (string) ($row['group_number'] ?? ''),
            'source_proposal' => (string) ($row['proposal_number'] ?? ''),
            'research_group' => $groupLabel,
            'research_title' => (string) ($row['research_title'] ?? ''),
            'adviser' => (string) ($row['adviser_name'] ?? 'Research Adviser'),
            'adviser_email' => (string) ($row['adviser_email'] ?? ''),
            'icon' => 'fa-user-check',
            'class' => 'text-primary',
            'label' => 'Assignment Notification',
            'body' => $body,
            'preview' => smsNotificationPreviewText($body),
            'status' => $isUnread ? 'unread' : 'read',
            'is_unread' => $isUnread,
            'time' => date('M j, Y h:i A', $created),
            'url' => smsNotificationViewUrl([
                'type' => 'assignment',
                'group' => (string) ($row['group_number'] ?? ''),
                'proposal' => (string) ($row['proposal_number'] ?? ''),
            ]),
        ];
    }

    return array_slice($items, 0, max(1, min(50, $limit)));
}

function smsCurrentUserAssignmentNotificationDetail(string $groupNumber = '', string $proposalNumber = ''): ?array
{
    $groupNumber = trim($groupNumber);
    $proposalNumber = trim($proposalNumber);
    $role = getCurrentUserRoleKey();
    $email = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    if (!in_array($role, ['adviser', 'student'], true)) {
        return null;
    }

    $crad = cradDb();
    if (!$crad) {
        return null;
    }

    try {
        smsAssignmentNotificationEnsureSentSchema($crad);
        $stmt = $crad->prepare("
            SELECT
                g.id AS research_group_id,
                g.group_number,
                COALESCE(t.proposal_number, g.proposal_number, p.proposal_number, p.ref_code, '') AS proposal_number,
                COALESCE(NULLIF(g.group_name, ''), g.group_number, 'Research Group') AS group_name,
                COALESCE(NULLIF(g.research_title, ''), p.research_title, t.proposed_title, '') AS research_title,
                COALESCE(NULLIF(g.leader_name, ''), p.rep_name, t.student_name, 'Student') AS student_name,
                COALESCE(NULLIF(g.leader_id, ''), p.rep_id, t.student_id, '') AS student_number,
                COALESCE(NULLIF(g.leader_email, ''), p.rep_email, '') AS student_email,
                p.submitted_by_user,
                t.student_user_id AS title_student_user_id,
                a.adviser_user_id,
                a.adviser_name,
                a.adviser_email,
                a.notification_sent_at
            FROM research_groups g
            LEFT JOIN research_proposals p ON p.id = g.proposal_id
            LEFT JOIN title_approvals t ON t.id = g.title_approval_id
            INNER JOIN research_adviser_assignments a
               ON a.assignment_status = 'Assigned'
              AND (
                    a.research_group_id = g.id
                 OR (a.group_number IS NOT NULL AND a.group_number <> '' AND a.group_number = g.group_number)
                 OR (a.proposal_id IS NOT NULL AND a.proposal_id = g.proposal_id)
              )
            WHERE a.notification_sent_at IS NOT NULL
              AND (g.title_approval_id IS NULL OR g.title_approval_id = 0 OR t.id IS NOT NULL)
              AND (
                    (:group_gate <> '' AND g.group_number = :group_number)
                 OR (:proposal_gate <> '' AND COALESCE(t.proposal_number, g.proposal_number, p.proposal_number, p.ref_code, '') = :proposal_number)
              )
            ORDER BY a.notification_sent_at DESC, a.updated_at DESC, a.id DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':group_gate' => $groupNumber,
            ':group_number' => $groupNumber,
            ':proposal_gate' => $proposalNumber,
            ':proposal_number' => $proposalNumber,
        ]);
        $row = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('Assignment notification direct detail failed: ' . $e->getMessage());
        return null;
    }

    if (!$row) {
        return null;
    }

    if ($role === 'adviser') {
        $adviserUserId = (int) ($row['adviser_user_id'] ?? 0);
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
        if ($adviserUserId > 0) {
            if ($currentUserId <= 0 || $currentUserId !== $adviserUserId) {
                return null;
            }
        } elseif ($email === '' || $email !== strtolower(trim((string) ($row['adviser_email'] ?? '')))) {
            return null;
        }
    } elseif ($role === 'student') {
        $studentId = trim((string) ($_SESSION['student_id'] ?? ''));
        $studentName = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $matchesStudent = ($studentId !== '' && $studentId === trim((string) ($row['student_number'] ?? '')))
            || ($email !== '' && $email === strtolower(trim((string) ($row['student_email'] ?? ''))))
            || ($studentName !== '' && $studentName === strtolower(trim((string) ($row['student_name'] ?? ''))))
            || ($userId > 0 && in_array($userId, [
                (int) ($row['submitted_by_user'] ?? 0),
                (int) ($row['title_student_user_id'] ?? 0),
            ], true));
        if (!$matchesStudent) {
            return null;
        }
    }

    smsMarkCurrentUserSyntheticNotificationRead(
        'live-assignment:' . (string) (($row['group_number'] ?? '') ?: ($row['proposal_number'] ?? ''))
    );

    $sent = strtotime((string) ($row['notification_sent_at'] ?? '')) ?: time();
    $researchTitle = (string) ($row['research_title'] ?? '');
    $requiredExpertise = smsNotificationRequiredExpertise($researchTitle);
    $groupLabel = (string) (($row['group_name'] ?? '') ?: ($row['group_number'] ?? 'Research Group'));
    $reference = (string) ($row['group_number'] ?? '');
    if ((string) ($row['proposal_number'] ?? '') !== '') {
        $reference .= ' / ' . (string) ($row['proposal_number'] ?? '');
    }
    if ($role === 'student') {
        $message = "Good day " . (string) (($row['student_name'] ?? '') ?: 'Student') . ",\n\n"
            . "Your research group has been sent an adviser assignment notification.\n\n"
            . "Research Title: " . ($researchTitle !== '' ? $researchTitle : 'Untitled research') . "\n"
            . "Research Adviser: " . (string) (($row['adviser_name'] ?? '') ?: 'Research Adviser') . "\n"
            . "Research Group: " . $groupLabel . "\n"
            . "Reference: " . $reference . "\n\n"
            . "Please coordinate with your research adviser for the next steps.\n\n"
            . "Thank you.";
    } else {
        $message = "Good day " . (string) (($row['adviser_name'] ?? '') ?: 'Research Adviser') . ",\n\n"
            . "You are being contacted as a possible research adviser for the approved research below.\n\n"
            . "Research Title: " . ($researchTitle !== '' ? $researchTitle : 'Untitled research') . "\n"
            . "Required Expertise: " . $requiredExpertise . "\n"
            . "Research Group: " . $groupLabel . "\n"
            . "Reference: " . $reference . "\n\n"
            . "Please review the details and confirm if you are available to advise this research.\n\n"
            . "Thank you.";
    }

    return [
        'title' => 'Assignment Notification',
        'badge' => 'Notification',
        'badge_class' => 'secondary',
        'icon' => 'fa-user-check',
        'time' => date('M j, Y h:i A', $sent),
        'time_iso' => (new DateTimeImmutable('@' . $sent))->setTimezone(new DateTimeZone('Asia/Manila'))->format(DateTimeInterface::ATOM),
        'details' => [
            ['label' => 'Research Group', 'value' => $groupLabel],
            ['label' => 'Student', 'value' => (string) (($row['student_name'] ?? '') ?: 'Student')],
            ['label' => 'Student Number', 'value' => (string) (($row['student_number'] ?? '') ?: 'N/A')],
            ['label' => 'Adviser', 'value' => (string) (($row['adviser_name'] ?? '') ?: 'Research Adviser')],
            ['label' => 'Date', 'value' => date('M j, Y', $sent)],
            ['label' => 'Time', 'value' => date('g:i A', $sent)],
        ],
        'message' => $message,
    ];
}

function smsNotificationRequiredExpertise(string $title): string
{
    $topic = strtolower($title);
    $rules = [
        'Artificial Intelligence / Machine Learning' => ['ai', 'artificial intelligence', 'machine learning', 'openai', 'chatgpt', 'neural', 'prediction model'],
        'Systems Development / Software Engineering' => ['system', 'application', 'app ', 'web', 'mobile', 'portal', 'platform', 'software'],
        'Data Analytics / Data Analysis' => ['analytics', 'analysis', 'data', 'dashboard', 'forecast', 'statistics'],
        'Educational Technology' => ['education', 'learning', 'student', 'teacher', 'classroom', 'lms'],
        'Internet of Things / Embedded Systems' => ['iot', 'sensor', 'monitoring', 'arduino', 'embedded'],
        'Research Methods / Validation' => ['assessment', 'evaluation', 'validation', 'effectiveness', 'study'],
        'Business / Entrepreneurship' => ['business', 'marketing', 'enterprise', 'entrepreneur', 'sales'],
        'Community Development / Social Research' => ['community', 'health', 'literacy', 'social', 'barangay'],
    ];

    $matches = [];
    foreach ($rules as $expertise => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($topic, $keyword) !== false) {
                $matches[] = $expertise;
                break;
            }
        }
    }

    return $matches === [] ? 'General Research Methods' : implode(' / ', array_slice(array_unique($matches), 0, 2));
}

function smsGrantProposalNotificationsPayload(int $limit = 12): array
{
    if (!in_array((string) ($_SESSION['user_role_key'] ?? ''), ['student', 'adviser'], true)) {
        return [];
    }

    if (!function_exists('grantProposalNotificationsForCurrentUser')) {
        require_once ROOT_PATH . '/modules/crad/includes/grant-evaluation-helpers.php';
    }

    $rows = grantProposalNotificationsForCurrentUser($limit);
    return array_map(static function (array $row): array {
        $created = strtotime((string) ($row['created_at'] ?? '')) ?: time();
        $status = (string) ($row['status'] ?? 'read');
        $body = (string) ($row['body'] ?? '');
        $batchKey = (string) ($row['batch_key'] ?? '');
        $notifType = (string) ($row['type'] ?? '');
        $isRevision = $notifType === 'grant_revision_required'
            || str_contains($batchKey, 'grant_revision_required')
            || str_contains($batchKey, 'grant_approval_return');
        $isFundRelease = $notifType === 'grant_fund_release' || str_contains($batchKey, 'grant_fund_release');
        $isMilestoneUpdate = $notifType === 'grant_milestone_update' || str_contains($batchKey, 'grant_milestone_update');
        $isFinalOutput = in_array($notifType, ['grant_final_output_submitted', 'grant_final_output_verified', 'grant_final_output_returned'], true)
            || str_contains($batchKey, 'final_output');
        $isApprovedFunded = $notifType === 'grant_approved_funded' || str_contains($batchKey, 'grant_approved_funded');
        $icon = (string) ($row['icon'] ?? 'fa-hand-holding-usd');
        if ($isFundRelease) {
            $icon = 'fa-money-bill-wave';
        } elseif ($isMilestoneUpdate) {
            $icon = 'fa-tasks';
        } elseif ($isFinalOutput) {
            $icon = 'fa-book-open';
        } elseif ($isApprovedFunded) {
            $icon = 'fa-check-circle';
        } elseif ($isRevision) {
            $icon = 'fa-edit';
        }
        return [
            'id' => (int) ($row['id'] ?? 0),
            'batch_key' => $batchKey,
            'icon' => $icon,
            'class' => $isRevision ? 'text-warning' : ($isFundRelease ? 'text-success' : ($isMilestoneUpdate ? 'text-info' : ($isFinalOutput ? 'text-primary' : 'text-primary'))),
            'label' => (string) ($row['title'] ?? 'Grant Proposal Update'),
            'body' => $body,
            'preview' => smsNotificationPreviewText($body),
            'status' => $status,
            'is_unread' => $status === 'unread',
            'time' => date('M j, Y h:i A', $created),
            'url' => (string) ($row['url'] ?? '#'),
        ];
    }, $rows);
}

function smsNotificationPayloadForCurrentUser(): array
{
    $rows = smsCurrentUserNotifications(50);
    $items = array_map(static function (array $row): array {
        $created = strtotime((string) ($row['created_at'] ?? '')) ?: time();
        $status = (string) ($row['status'] ?? 'read');
        $body = (string) ($row['body'] ?? '');
        return [
            'id' => (int) $row['id'],
            'batch_key' => (string) ($row['batch_key'] ?? ''),
            'icon' => (string) ($row['icon'] ?? 'fa-bell'),
            'class' => 'text-primary',
            'label' => (string) ($row['title'] ?? 'Notification'),
            'body' => $body,
            'preview' => smsNotificationPreviewText($body),
            'status' => $status,
            'is_unread' => $status === 'unread',
            'time' => date('M j, Y h:i A', $created),
            'url' => (string) ($row['url'] ?? '#'),
        ];
    }, $rows);

    return smsNotificationDedupe(array_merge(
        smsGrantProposalNotificationsPayload(12),
        smsCurrentUserAssignmentNotifications(8),
        $items,
        smsStudentResearchStatusNotifications(),
        smsStudentReturnedTitleApprovalNotifications()
    ));
}

function smsNotificationDedupe(array $items): array
{
    $seen = [];
    $deduped = [];
    foreach ($items as $item) {
        $batchKey = trim((string) ($item['batch_key'] ?? ''));
        $key = $batchKey !== ''
            ? 'batch:' . strtolower($batchKey)
            : strtolower((string) ($item['label'] ?? '') . '|' . (string) ($item['url'] ?? ''));
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $deduped[] = $item;
    }
    return $deduped;
}

function smsStudentResearchStatusNotifications(): array
{
    if (($_SESSION['user_role_key'] ?? '') !== 'student') {
        return [];
    }

    $crad = cradDb();
    if (!$crad) {
        return [];
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
                    g.title_approval_id, g.group_number, g.group_name, g.created_at, g.date_assigned
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
        $group = $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        error_log('Student research status notification load failed: ' . $e->getMessage());
        return [];
    }

    if (!$group || empty($group['group_number'])) {
        return [];
    }

    $created = !empty($group['created_at'])
        ? strtotime((string) $group['created_at'])
        : (!empty($group['date_assigned']) ? strtotime((string) $group['date_assigned']) : time());

    $groupId = (int) ($group['research_group_id'] ?? 0);
    $readIds = array_map('intval', (array) ($_SESSION['read_research_group_notifications'] ?? []));
    $isUnread = $groupId > 0 && !in_array($groupId, $readIds, true);

    $fromTitleApproval = !empty($group['title_approval_id']);
    $label = $fromTitleApproval ? 'Title Approval Status' : 'Research Approval Status';
    $body = 'Research Group Number: ' . (string) $group['group_number'] . "\n"
        . 'Group Name: ' . (string) ($group['group_name'] ?? '');

    return [[
        'id' => 0,
        'batch_key' => 'research-group:' . $groupId,
        'icon' => 'fa-users',
        'class' => 'text-primary',
        'label' => $label,
        'body' => $body,
        'preview' => 'Research Group Number: ' . (string) $group['group_number'],
        'status' => $isUnread ? 'unread' : 'read',
        'is_unread' => $isUnread,
        'time' => date('M j, Y h:i A', $created ?: time()),
        'url' => smsNotificationViewUrl(['type' => 'research_group']),
    ]];
}

function smsStudentReturnedProposalNotifications(): array
{
    return [];
}

function smsStudentReturnedTitleApprovalNotifications(): array
{
    if (($_SESSION['user_role_key'] ?? '') !== 'student') {
        return [];
    }

    $crad = cradDb();
    if (!$crad) {
        return [];
    }

    $studentId = trim((string) ($_SESSION['student_id'] ?? ''));
    $studentName = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
    $studentUserId = (int) ($_SESSION['user_id'] ?? 0);

    try {
        $stmt = $crad->prepare(
            "SELECT id, proposed_title, adviser_remarks, coordinator_remarks, reviewed_at, coordinator_reviewed_at, sent_at
             FROM title_approvals
             WHERE status = 'Returned'
               AND (
                    (:student_id_value <> '' AND student_id = :student_id_match)
                 OR (:student_name_value <> '' AND LOWER(TRIM(student_name)) = :student_name_match)
                 OR (:user_id_value > 0 AND student_user_id = :user_id_match)
               )
             ORDER BY reviewed_at DESC, id DESC
             LIMIT 5"
        );
        $stmt->execute([
            ':student_id_value' => $studentId,
            ':student_id_match' => $studentId,
            ':student_name_value' => $studentName,
            ':student_name_match' => $studentName,
            ':user_id_value' => $studentUserId,
            ':user_id_match' => $studentUserId,
        ]);
        $rows = $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Returned title approval notification load failed: ' . $e->getMessage());
        return [];
    }

    return array_map(static function (array $row): array {
        $updated = strtotime((string) (($row['coordinator_reviewed_at'] ?? '') ?: ($row['reviewed_at'] ?? '') ?: ($row['sent_at'] ?? ''))) ?: time();
        $remarks = trim((string) (($row['coordinator_remarks'] ?? '') ?: ($row['adviser_remarks'] ?? '')));
        $id = (int) ($row['id'] ?? 0);
        $readIds = array_map('intval', (array) ($_SESSION['read_returned_title_approvals'] ?? []));
        $isUnread = $id > 0 && !in_array($id, $readIds, true);
        return [
            'id' => 0,
            'batch_key' => 'returned-title-approval:' . $id,
            'icon' => 'fa-undo',
            'class' => 'text-danger',
            'label' => 'Title Approval Returned',
            'body' => (string) ($row['proposed_title'] ?? ''),
            'preview' => $remarks !== '' ? $remarks : (string) ($row['proposed_title'] ?? ''),
            'status' => $isUnread ? 'unread' : 'read',
            'is_unread' => $isUnread,
            'time' => date('M j, Y h:i A', $updated),
            'url' => smsNotificationViewUrl([
                'type' => 'returned_title_approval',
                'title_approval' => $id,
            ]),
        ];
    }, $rows);
}
