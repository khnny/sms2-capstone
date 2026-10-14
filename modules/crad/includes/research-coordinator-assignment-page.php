<?php
/**
 * SMS 2 - Research Coordinator live assignment views.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/notifications.php';
require_once __DIR__ . '/title-approval-assignees.php';
require_once __DIR__ . '/research-group-flow.php';

requireAuth();

$roleKey = getCurrentUserRoleKey();
$requestedRcPageSlug = $rcPageSlug ?? 'find-contact-adviser';
$canViewRetrieveApprovedResearch = $requestedRcPageSlug === 'retrieve-approved-research'
    && $roleKey === 'crad_officer';
$rcAssignDeniedMessage = 'Only the Department Head may assign Research Coordinator and Adviser.';
if (!smsCanManageCoordinatorAssignments($roleKey) && !$canViewRetrieveApprovedResearch) {
    $isAjax = (($_POST['ajax'] ?? '') !== '') || (($_GET['ajax'] ?? '') !== '');
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => $rcAssignDeniedMessage, 'message' => $rcAssignDeniedMessage]);
        exit;
    }
    if ($roleKey === 'research_coordinator') {
        header('Location: ' . BASE_URL . '/modules/crad/pages/assignment-confirmation.php?notice=assign-dh-only');
        exit;
    }
    header('Location: ' . BASE_URL . '/dashboard/index.php?error=' . rawurlencode($rcAssignDeniedMessage));
    exit;
}

$rcAssignmentKind = $rcAssignmentKind ?? 'adviser';
$rcPageSlug = $rcPageSlug ?? 'find-contact-adviser';

$pageMap = [
    'retrieve-approved-research' => ['kind' => 'adviser', 'title' => 'Retrieve Approved Research', 'lead' => 'Signature Monitoring'],
    'find-contact-adviser' => ['kind' => 'adviser', 'title' => 'Find/Contact Adviser', 'lead' => 'Adviser Assignment'],
    'adviser-availability' => ['kind' => 'adviser', 'title' => 'Check Adviser Availability', 'lead' => 'Adviser Assignment'],
    'assign-research-adviser' => ['kind' => 'adviser', 'title' => 'Assign Research Adviser', 'lead' => 'Adviser Assignment'],
    'manage-assignments' => ['kind' => 'adviser', 'title' => 'View/Manage Adviser Assignments', 'lead' => 'Adviser Assignment'],
];

if (!isset($pageMap[$rcPageSlug])) {
    $rcPageSlug = 'find-contact-adviser';
}

$pageConfig = $pageMap[$rcPageSlug];
$rcAssignmentKind = $pageConfig['kind'];


function rcAssignmentStartDualConfirmCycle(PDO $pdo, string $groupNumber, ?int $userId): void
{
    try {
        if (!function_exists('cradRgFlowStartAssignmentCycle')) {
            return;
        }
        cradRgFlowEnsureSchema($pdo);
        $groupNumber = trim($groupNumber);
        if ($groupNumber === '') {
            return;
        }
        $gStmt = $pdo->prepare("SELECT id, leader_id, flow_status FROM `crad_research_groups` WHERE group_number = ? LIMIT 1");
        $gStmt->execute([$groupNumber]);
        $group = $gStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $groupId = (int) ($group['id'] ?? 0);
        $studentId = trim((string) ($group['leader_id'] ?? ''));
        if ($studentId === '' && str_starts_with($groupNumber, 'STU-')) {
            $studentId = substr($groupNumber, 4);
        }

        // Prefer groups that completed Research Group approval before assignment.
        $flow = strtolower((string) ($group['flow_status'] ?? ''));
        if (in_array($flow, ['draft', 'pending_incomplete_approval', 'rejected'], true)) {
            error_log('Dual-confirm skipped: research group flow_status=' . $flow . ' for ' . $groupNumber);
            return;
        }

        $adv = $pdo->prepare(
            "SELECT id FROM `crad_research_adviser_assignments`
             WHERE group_number = :gn OR research_group_id = :gid
             ORDER BY (assignment_status IN ('Assigned','Confirmed')) DESC, id DESC LIMIT 1"
        );
        $adv->execute([':gn' => $groupNumber, ':gid' => $groupId]);
        $adviserId = (int) ($adv->fetchColumn() ?: 0);

        $coord = $pdo->prepare(
            "SELECT id FROM `crad_research_coordinator_assignments`
             WHERE status = 'Active' AND (group_number = :gn OR research_group_id = :gid OR student_id = :sid)
             ORDER BY id DESC LIMIT 1"
        );
        $coord->execute([':gn' => $groupNumber, ':gid' => $groupId, ':sid' => $studentId]);
        $coordId = (int) ($coord->fetchColumn() ?: 0);

        if ($adviserId <= 0 || $coordId <= 0) {
            return; // wait until both exist
        }

        cradRgFlowStartAssignmentCycle(
            $pdo,
            $groupId,
            $groupNumber,
            $studentId,
            $coordId > 0 ? $coordId : null,
            $adviserId > 0 ? $adviserId : null,
            (int) ($userId ?? 0)
        );
    } catch (Throwable $e) {
        error_log('Dual-confirm cycle start failed: ' . $e->getMessage());
    }
}

function rcAssignmentEnsureSchema(PDO $pdo): void
{
    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'crad_research_groups'")->fetch();
        if ($exists) {
            $pdo->exec("ALTER TABLE `crad_research_groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    } catch (Throwable $e) {
        error_log('CRAD research group collation alignment skipped: ' . $e->getMessage());
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `crad_research_adviser_assignments` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            research_group_id INT UNSIGNED DEFAULT NULL,
            proposal_id INT UNSIGNED DEFAULT NULL,
            proposal_number VARCHAR(30) DEFAULT NULL,
            group_number VARCHAR(40) DEFAULT NULL,
            adviser_name VARCHAR(150) NOT NULL DEFAULT '',
            adviser_email VARCHAR(190) NOT NULL DEFAULT '',
            adviser_user_id INT UNSIGNED DEFAULT NULL,
            expertise VARCHAR(255) NOT NULL DEFAULT '',
            availability_status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            assignment_status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            notes TEXT DEFAULT NULL,
            assigned_by INT UNSIGNED DEFAULT NULL,
            assigned_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_raa_group (research_group_id),
            KEY idx_raa_proposal (proposal_id),
            KEY idx_raa_group_number (group_number),
            KEY idx_raa_status (assignment_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    smsAssignmentNotificationEnsureSentSchema($pdo);
    cradEnsureAssigneeSchema($pdo);
    cradEnsureTitleApprovalAdviserAssignmentConsistency($pdo);

    try {
        $col = $pdo->query("SHOW COLUMNS FROM `crad_research_adviser_assignments` LIKE 'adviser_user_id'")->fetch();
        if (!$col) {
            $pdo->exec("ALTER TABLE `crad_research_adviser_assignments` ADD COLUMN adviser_user_id INT UNSIGNED DEFAULT NULL AFTER adviser_email, ADD KEY idx_raa_user (adviser_user_id)");
        }
    } catch (Throwable $e) {
        error_log('Research adviser user link column skipped: ' . $e->getMessage());
    }

    try {
        $pdo->exec("
            DELETE a
            FROM `crad_research_adviser_assignments` a
            INNER JOIN `crad_research_adviser_assignments` keep
              ON LOWER(TRIM(a.adviser_email)) = LOWER(TRIM(keep.adviser_email))
             AND LOWER(TRIM(a.adviser_name)) = LOWER(TRIM(keep.adviser_name))
             AND IFNULL(a.group_number, '') = IFNULL(keep.group_number, '')
             AND a.id < keep.id
            WHERE TRIM(a.adviser_email) <> ''
              AND TRIM(a.adviser_name) <> ''
        ");

        if ($pdo->query("SHOW INDEX FROM `crad_research_adviser_assignments` WHERE Key_name = 'uniq_raa_adviser_identity'")->fetch()) {
            $pdo->exec("ALTER TABLE `crad_research_adviser_assignments` DROP INDEX uniq_raa_adviser_identity");
        }
    } catch (Throwable $e) {
        error_log('Research adviser duplicate guard skipped: ' . $e->getMessage());
    }
}

function rcAssignmentBuildGroupNumber(int $sequence): string
{
    return 'RG-' . date('Y') . '-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
}

function rcAssignmentBuildGroupName(int $sequence): string
{
    return 'Group ' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
}

function rcAssignmentBuildTitleProposalNumber(int $titleApprovalId): string
{
    return 'TAP-' . date('Y') . '-' . str_pad((string) $titleApprovalId, 5, '0', STR_PAD_LEFT);
}

function rcAssignmentNullableInt(mixed $value): ?int
{
    $intValue = (int) $value;
    return $intValue > 0 ? $intValue : null;
}

function rcAssignmentSyncApprovedTitleGroups(PDO $pdo): void
{
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `crad_research_groups` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                proposal_id INT UNSIGNED DEFAULT NULL,
                title_approval_id INT UNSIGNED DEFAULT NULL,
                proposal_number VARCHAR(30) DEFAULT NULL,
                group_number VARCHAR(40) NOT NULL,
                group_name VARCHAR(40) NOT NULL DEFAULT '',
                research_title VARCHAR(255) NOT NULL DEFAULT '',
                college_dept VARCHAR(120) NOT NULL DEFAULT '',
                adviser VARCHAR(120) NOT NULL DEFAULT '',
                academic_year VARCHAR(20) NOT NULL DEFAULT '',
                leader_name VARCHAR(120) NOT NULL DEFAULT '',
                leader_id VARCHAR(40) NOT NULL DEFAULT '',
                leader_email VARCHAR(120) NOT NULL DEFAULT '',
                leader_contact VARCHAR(40) NOT NULL DEFAULT '',
                status VARCHAR(40) NOT NULL DEFAULT 'Approved',
                date_assigned DATE NOT NULL,
                created_by INT UNSIGNED DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        foreach ([
            'title_approval_id' => "ALTER TABLE `crad_research_groups` ADD title_approval_id INT UNSIGNED DEFAULT NULL AFTER proposal_id",
            'proposal_number' => "ALTER TABLE `crad_research_groups` ADD proposal_number VARCHAR(30) DEFAULT NULL AFTER title_approval_id",
            'group_name' => "ALTER TABLE `crad_research_groups` ADD group_name VARCHAR(40) NOT NULL DEFAULT '' AFTER group_number",
        ] as $column => $sql) {
            if (!$pdo->query("SHOW COLUMNS FROM `crad_research_groups` LIKE " . $pdo->quote($column))->fetch()) {
                $pdo->exec($sql);
            }
        }

        foreach ([
            'title_approval_id' => "ALTER TABLE `crad_research_groups` ADD UNIQUE KEY title_approval_id (title_approval_id)",
            'group_number' => "ALTER TABLE `crad_research_groups` ADD UNIQUE KEY group_number (group_number)",
            'idx_rg_proposal_number' => "ALTER TABLE `crad_research_groups` ADD KEY idx_rg_proposal_number (proposal_number)",
        ] as $name => $sql) {
            if (!$pdo->query("SHOW INDEX FROM `crad_research_groups` WHERE Key_name = " . $pdo->quote($name))->fetch()) {
                $pdo->exec($sql);
            }
        }

        $relationship = cradEnsureTitleApprovalResearchGroupCascade($pdo);
        if (empty($relationship['ok'])) {
            throw new RuntimeException((string) $relationship['message']);
        }

        $proposalNumberCol = $pdo->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'proposal_number'")->fetch();
        if (!$proposalNumberCol) {
            $pdo->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN proposal_number VARCHAR(30) DEFAULT NULL AFTER coordinator_name");
        }

        $stmt = $pdo->query("
            SELECT t.id, t.student_id, t.student_name, t.department, t.proposed_title,
                   t.adviser_name, t.proposal_number
            FROM `crad_title_approvals` t
            LEFT JOIN `crad_research_groups` g ON g.title_approval_id = t.id
            WHERE t.status = 'Approved'
              AND t.coordinator_status = 'Approved'
              AND t.crad_status = 'Approved'
              AND t.adviser_signature_data IS NOT NULL
              AND t.adviser_signature_data <> ''
              AND t.coordinator_signature_data IS NOT NULL
              AND t.coordinator_signature_data <> ''
              AND t.crad_signature_data IS NOT NULL
              AND t.crad_signature_data <> ''
              AND g.id IS NULL
            ORDER BY t.crad_reviewed_at ASC, t.id ASC
        ");
        $approvals = $stmt->fetchAll() ?: [];

        foreach ($approvals as $approval) {
            $proposalNumber = trim((string) ($approval['proposal_number'] ?? ''));
            if ($proposalNumber === '') {
                $proposalNumber = rcAssignmentBuildTitleProposalNumber((int) $approval['id']);
                $setProposal = $pdo->prepare("UPDATE `crad_title_approvals` SET proposal_number = :proposal_number WHERE id = :id LIMIT 1");
                $setProposal->execute([
                    ':proposal_number' => $proposalNumber,
                    ':id' => (int) $approval['id'],
                ]);
            }

            $lastRow = $pdo->query("SELECT MAX(id) AS max_id FROM `crad_research_groups`")->fetch();
            $seq = (int) ($lastRow['max_id'] ?? 0) + 1;
            $newGroupNumber = rcAssignmentBuildGroupNumber($seq);
            $ins = $pdo->prepare("
                INSERT INTO `crad_research_groups`
                    (proposal_id, title_approval_id, proposal_number, group_number, group_name,
                     research_title, college_dept, adviser, academic_year,
                     leader_name, leader_id, leader_email, leader_contact,
                     status, date_assigned, created_by)
                VALUES
                    (NULL, :title_approval_id, :proposal_number, :group_number, :group_name,
                     :research_title, :college_dept, :adviser, :academic_year,
                     :leader_name, :leader_id, '', '',
                     'Approved', :date_assigned, :created_by)
            ");
            $ins->execute([
                ':title_approval_id' => (int) $approval['id'],
                ':proposal_number' => $proposalNumber,
                ':group_number' => $newGroupNumber,
                ':group_name' => rcAssignmentBuildGroupName($seq),
                ':research_title' => (string) $approval['proposed_title'],
                ':college_dept' => (string) $approval['department'],
                ':adviser' => (string) $approval['adviser_name'],
                ':academic_year' => date('Y') . '-' . ((int) date('Y') + 1),
                ':leader_name' => (string) $approval['student_name'],
                ':leader_id' => (string) $approval['student_id'],
                ':date_assigned' => date('Y-m-d'),
                ':created_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
            ]);
            $newGroupId = (int) $pdo->lastInsertId();
            if (function_exists('cradRgFlowSyncAfterTitleApproved')) {
                cradRgFlowSyncAfterTitleApproved($pdo, (int) $approval['id'], $newGroupId);
            }
            cradMigrateStudentAssignmentsToOfficialGroup($pdo, (string) $approval['student_id'], [
                'id' => $newGroupId,
                'group_number' => $newGroupNumber,
                'title_approval_id' => (int) $approval['id'],
            ]);
        }
    } catch (Throwable $e) {
        error_log('Approved title approval group auto-sync failed: ' . $e->getMessage());
    }
}

function rcAssignmentPruneOrphanAssignments(PDO $pdo): void
{
    try {
        $pdo->exec("
            UPDATE `crad_research_adviser_assignments` a
            JOIN `crad_research_proposals` p
              ON a.proposal_number IS NOT NULL
             AND a.proposal_number <> ''
             AND (p.proposal_number = a.proposal_number OR p.ref_code = a.proposal_number)
            SET a.proposal_id = p.id
            WHERE a.proposal_id IS NULL OR a.proposal_id <> p.id
        ");
    } catch (Throwable $e) {
        error_log('Assignment proposal link sync skipped for research_adviser_assignments: ' . $e->getMessage());
    }
}

function rcAssignmentResetStaleAssignments(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    rcAssignmentPruneOrphanAssignments($pdo);

    try {
        $pdo->exec("
            UPDATE `crad_research_adviser_assignments`
               SET assignment_status = 'Pending',
                   assigned_by = NULL,
                   assigned_at = NULL,
                   notification_sent_at = NULL,
                   notification_sent_by = NULL,
                   updated_at = NOW()
             WHERE assignment_status = 'Assigned'
               AND LOWER(TRIM(availability_status)) <> 'available'
        ");

        $pdo->exec("
            UPDATE `crad_research_adviser_assignments` a
               SET a.assignment_status = 'Pending',
                   a.updated_at = NOW()
             WHERE a.assignment_status = 'Assigned'
               AND NOT EXISTS (
                SELECT 1
                FROM `crad_research_groups` g
                LEFT JOIN `crad_research_proposals` p ON p.id = g.proposal_id
                WHERE g.group_number IS NOT NULL
                  AND g.group_number <> ''
                  AND (
                    (a.research_group_id IS NOT NULL AND a.research_group_id = g.id)
                    OR (a.group_number IS NOT NULL AND a.group_number <> '' AND a.group_number = g.group_number)
                    OR (a.proposal_id IS NOT NULL AND a.proposal_id = g.proposal_id)
                    OR (a.proposal_number IS NOT NULL AND a.proposal_number <> '' AND a.proposal_number = p.proposal_number)
                  )
               )
        ");
    } catch (Throwable $e) {
        error_log('Assignment stale record reset failed for research_adviser_assignments: ' . $e->getMessage());
    }

    $defenseTables = [
        'crad_research_defense_schedules',
        'research_defense_schedules',
        'research_defense_schedule',
        'research_defenses',
        'defense_schedules',
    ];
    $completedStatuses = array_map([$pdo, 'quote'], [
        'completed',
        'complete',
        'passed',
        'done',
        'finished',
        'closed',
    ]);

    foreach ($defenseTables as $defenseTable) {
        try {
            $tableExists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($defenseTable))->fetchColumn();
            if (!$tableExists) {
                continue;
            }

            $columns = $pdo->query("SHOW COLUMNS FROM `{$defenseTable}`")->fetchAll(PDO::FETCH_COLUMN) ?: [];
            $columnSet = array_fill_keys(array_map('strtolower', $columns), true);
            $statusColumn = null;
            foreach (['status', 'defense_status', 'result', 'defense_result', 'outcome'] as $candidate) {
                if (isset($columnSet[$candidate])) {
                    $statusColumn = $candidate;
                    break;
                }
            }
            if (!$statusColumn) {
                continue;
            }

            $matchParts = [];
            if (isset($columnSet['research_group_id'])) {
                $matchParts[] = '(a.research_group_id IS NOT NULL AND a.research_group_id = d.`research_group_id`)';
            }
            if (isset($columnSet['group_number'])) {
                $matchParts[] = '(a.group_number IS NOT NULL AND a.group_number <> "" AND a.group_number = d.`group_number`)';
            }
            if (isset($columnSet['proposal_id'])) {
                $matchParts[] = '(a.proposal_id IS NOT NULL AND a.proposal_id = d.`proposal_id`)';
            }
            if (isset($columnSet['proposal_number'])) {
                $matchParts[] = '(a.proposal_number IS NOT NULL AND a.proposal_number <> "" AND a.proposal_number = d.`proposal_number`)';
            }
            if (!$matchParts) {
                continue;
            }

            $matchesCompletedDefense = "
                EXISTS (
                    SELECT 1
                    FROM `{$defenseTable}` d
                    WHERE LOWER(TRIM(CAST(d.`{$statusColumn}` AS CHAR))) IN (" . implode(',', $completedStatuses) . ")
                      AND (" . implode(' OR ', $matchParts) . ")
                )
            ";

            $pdo->exec("
                UPDATE `crad_research_adviser_assignments` a
                   SET a.assignment_status = 'Pending',
                       a.assigned_by = NULL,
                       a.assigned_at = NULL,
                       a.notification_sent_at = NULL,
                       a.notification_sent_by = NULL,
                       a.updated_at = NOW()
                 WHERE a.assignment_status = 'Assigned'
                   AND {$matchesCompletedDefense}
            ");
        } catch (Throwable $e) {
            error_log('Assignment defense completion reset skipped for ' . $defenseTable . ': ' . $e->getMessage());
        }
    }
}

function rcAssignmentRows(PDO $pdo, string $kind): array
{
    $queries = [];

    if ($kind === 'adviser' || $kind === 'all') {
        $queries[] = "
            SELECT
                'adviser' AS assignment_kind,
                a.id AS assignment_id,
                g.title_approval_id,
                a.adviser_user_id AS assignee_user_id,
                a.adviser_name AS assignee_name,
                a.adviser_email AS assignee_email,
                'Research Adviser' AS assignee_role,
                a.expertise,
                a.availability_status,
                a.assignment_status,
                a.notes,
                a.assigned_at,
                a.updated_at,
                a.student_id,
                g.leader_id,
                g.group_number,
                g.group_name,
                COALESCE(NULLIF(g.research_title, ''), p.research_title, t.proposed_title) AS research_title,
                COALESCE(NULLIF(g.college_dept, ''), p.college_department, t.department) AS college_dept,
                COALESCE(p.proposal_number, t.proposal_number, g.proposal_number) AS proposal_number
             FROM `crad_research_adviser_assignments` a
             INNER JOIN `crad_research_groups` g ON g.id = a.research_group_id
                OR CONVERT(g.group_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(a.group_number USING utf8mb4) COLLATE utf8mb4_unicode_ci
             LEFT JOIN `crad_research_proposals` p ON p.id = COALESCE(a.proposal_id, g.proposal_id)
             LEFT JOIN `crad_title_approvals` t ON t.id = g.title_approval_id
             WHERE g.group_number IS NOT NULL
               AND g.group_number <> ''
               AND (
                    (p.id IS NOT NULL AND p.status = 'Approved' AND p.registration_status = 'Registered' AND p.proposal_number IS NOT NULL)
                 OR (
                    t.id IS NOT NULL
                    AND t.status = 'Approved'
                    AND t.coordinator_status = 'Approved'
                    AND t.crad_status = 'Approved'
                    AND t.adviser_signature_data IS NOT NULL
                    AND t.adviser_signature_data <> ''
                    AND t.coordinator_signature_data IS NOT NULL
                    AND t.coordinator_signature_data <> ''
                    AND t.crad_signature_data IS NOT NULL
                    AND t.crad_signature_data <> ''
                 )
                 OR EXISTS (
                    SELECT 1
                    FROM `crad_research_coordinator_assignments` ca
                    WHERE ca.status = 'Active'
                      AND (
                            ca.research_group_id = g.id
                         OR ca.group_number = g.group_number
                         OR (g.leader_id IS NOT NULL AND g.leader_id <> '' AND ca.student_id = g.leader_id)
                      )
                 )
               )
        ";
    }

    if ($queries === []) {
        return [];
    }

    $stmt = $pdo->query(implode(' UNION ALL ', $queries) . ' ORDER BY updated_at DESC, assignment_id DESC');
    return $stmt->fetchAll() ?: [];
}

function rcAssignmentRequiredExpertise(string $title): string
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

function rcAssignmentMatchScore(array $row, string $requiredExpertise): int
{
    $haystack = strtolower(implode(' ', [
        $row['research_title'] ?? '',
        $row['expertise'] ?? '',
        $row['notes'] ?? '',
        $row['college_dept'] ?? '',
    ]));
    $needles = preg_split('/[^a-z0-9]+/i', strtolower($requiredExpertise)) ?: [];
    $needles = array_values(array_unique(array_filter($needles, static fn($word) => strlen($word) > 2)));

    if ($needles === []) {
        return 40;
    }

    $hits = 0;
    foreach ($needles as $needle) {
        if (strpos($haystack, $needle) !== false) {
            $hits++;
        }
    }

    $score = (int) round(($hits / count($needles)) * 85);
    if (strcasecmp((string) ($row['availability_status'] ?? ''), 'Available') === 0) {
        $score += 10;
    }

    return max(15, min(100, $score));
}

function rcAssignmentApprovedGroups(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            g.id AS research_group_id,
            g.proposal_id,
            g.title_approval_id,
            COALESCE(p.proposal_number, t.proposal_number, g.proposal_number) AS proposal_number,
            g.group_number,
            g.group_name,
            COALESCE(NULLIF(g.research_title, ''), p.research_title, t.proposed_title) AS research_title,
            COALESCE(NULLIF(g.college_dept, ''), p.college_department, t.department) AS college_dept,
            COALESCE(NULLIF(g.adviser, ''), p.research_adviser, t.adviser_name, '') AS approved_adviser_name,
            COALESCE(t.adviser_email, '') AS approved_adviser_email,
            COALESCE(t.status, '') AS adviser_approval_status,
            COALESCE(t.coordinator_name, '') AS coordinator_approved_name,
            COALESCE(t.coordinator_status, '') AS coordinator_approval_status,
            COALESCE(t.coordinator_reviewed_at, t.updated_at, t.created_at) AS coordinator_approved_at,
            COALESCE(t.crad_status, '') AS crad_approval_status,
            COALESCE(t.crad_reviewed_at, t.updated_at, t.created_at) AS crad_approved_at,
            g.leader_name,
            g.leader_id,
            g.leader_email,
            g.leader_contact,
            g.status AS group_status,
            COALESCE(p.status, t.status) AS proposal_status,
            COALESCE(p.registration_status, 'Title Approved') AS registration_status,
            COALESCE(g.created_at, p.registered_at, p.approved_at, t.crad_reviewed_at, t.updated_at, p.created_at) AS updated_at
         FROM `crad_research_groups` g
         LEFT JOIN `crad_research_proposals` p ON p.id = g.proposal_id
         LEFT JOIN `crad_title_approvals` t ON t.id = g.title_approval_id
         WHERE (
                (p.id IS NOT NULL AND p.status = 'Approved' AND p.registration_status = 'Registered' AND p.proposal_number IS NOT NULL)
             OR (
                t.id IS NOT NULL
                AND t.status = 'Approved'
                AND t.coordinator_status = 'Approved'
                AND t.crad_status = 'Approved'
                AND t.adviser_signature_data IS NOT NULL
                AND t.adviser_signature_data <> ''
                AND t.coordinator_signature_data IS NOT NULL
                AND t.coordinator_signature_data <> ''
                AND t.crad_signature_data IS NOT NULL
                AND t.crad_signature_data <> ''
             )
           )
           AND g.group_number IS NOT NULL
           AND g.group_number <> ''
         ORDER BY updated_at DESC, g.id DESC
    ");

    $groups = $stmt->fetchAll() ?: [];
    $byNumber = [];
    foreach ($groups as $group) {
        $key = (string) ($group['group_number'] ?? '');
        if ($key !== '') {
            $byNumber[$key] = $group;
        }
    }

    try {
        $coordGroups = $pdo->query("
            SELECT
                g.id AS research_group_id,
                g.proposal_id,
                g.title_approval_id,
                COALESCE(p.proposal_number, t.proposal_number, g.proposal_number) AS proposal_number,
                g.group_number,
                g.group_name,
                COALESCE(NULLIF(g.research_title, ''), p.research_title, t.proposed_title, 'Pending Title Approval') AS research_title,
                COALESCE(NULLIF(g.college_dept, ''), p.college_department, t.department) AS college_dept,
                COALESCE(NULLIF(g.adviser, ''), p.research_adviser, t.adviser_name, '') AS approved_adviser_name,
                COALESCE(t.adviser_email, '') AS approved_adviser_email,
                COALESCE(t.status, '') AS adviser_approval_status,
                COALESCE(t.coordinator_name, '') AS coordinator_approved_name,
                COALESCE(t.coordinator_status, '') AS coordinator_approval_status,
                COALESCE(t.coordinator_reviewed_at, t.updated_at, t.created_at) AS coordinator_approved_at,
                COALESCE(t.crad_status, '') AS crad_approval_status,
                COALESCE(t.crad_reviewed_at, t.updated_at, t.created_at) AS crad_approved_at,
                g.leader_name,
                g.leader_id,
                g.leader_email,
                g.leader_contact,
                g.status AS group_status,
                COALESCE(p.status, t.status) AS proposal_status,
                COALESCE(p.registration_status, 'Title Approved') AS registration_status,
                COALESCE(g.created_at, p.registered_at, p.approved_at, t.crad_reviewed_at, t.updated_at, p.created_at) AS updated_at
             FROM `crad_research_groups` g
             INNER JOIN `crad_research_coordinator_assignments` ca
               ON ca.status = 'Active'
              AND (
                    ca.research_group_id = g.id
                 OR ca.group_number = g.group_number
                 OR (g.leader_id IS NOT NULL AND g.leader_id <> '' AND ca.student_id = g.leader_id)
              )
             LEFT JOIN `crad_research_proposals` p ON p.id = g.proposal_id
             LEFT JOIN `crad_title_approvals` t ON t.id = g.title_approval_id
             WHERE g.group_number IS NOT NULL
               AND g.group_number <> ''
             ORDER BY updated_at DESC, g.id DESC
        ")->fetchAll() ?: [];
        foreach ($coordGroups as $group) {
            $key = (string) ($group['group_number'] ?? '');
            if ($key !== '' && !isset($byNumber[$key])) {
                $byNumber[$key] = $group;
            }
        }
    } catch (Throwable $e) {
        error_log('Coordinator-ready assignment groups skipped: ' . $e->getMessage());
    }

    $groups = array_values($byNumber);
    foreach ($groups as &$group) {
        $group['required_expertise'] = rcAssignmentRequiredExpertise((string) ($group['research_title'] ?? ''));
    }
    unset($group);

    return $groups;
}

/**
 * Title approvals for the CRAD Officer's Retrieve Approved Research monitor.
 * Unlike the assignment-ready list, this intentionally includes records that
 * are still waiting for a coordinator or CRAD signature.
 */
function rcAssignmentRetrieveApprovalGroups(PDO $pdo): array
{
    $stmt = $pdo->query("\n        SELECT\n            t.id AS title_approval_id,\n            t.proposal_number,\n            t.proposed_title AS research_title,\n            t.department AS college_dept,\n            t.student_name AS leader_name,\n            t.student_id AS leader_id,\n            t.adviser_name AS approved_adviser_name,\n            t.status AS adviser_approval_status,\n            t.reviewed_at AS adviser_approved_at,\n            CASE WHEN t.adviser_signature_data IS NOT NULL AND t.adviser_signature_data <> '' THEN 1 ELSE 0 END AS adviser_signed,\n            t.coordinator_name AS coordinator_approved_name,\n            t.coordinator_status AS coordinator_approval_status,\n            t.coordinator_reviewed_at AS coordinator_approved_at,\n            CASE WHEN t.coordinator_signature_data IS NOT NULL AND t.coordinator_signature_data <> '' THEN 1 ELSE 0 END AS coordinator_signed,\n            t.crad_status AS crad_approval_status,\n            t.crad_reviewed_at AS crad_approved_at,\n            CASE WHEN t.crad_signature_data IS NOT NULL AND t.crad_signature_data <> '' THEN 1 ELSE 0 END AS crad_signed,\n            COALESCE(t.crad_reviewed_at, t.coordinator_reviewed_at, t.reviewed_at, t.updated_at, t.created_at) AS updated_at\n         FROM `crad_title_approvals` t\n         WHERE t.status = 'Approved'\n         ORDER BY COALESCE(t.crad_reviewed_at, t.coordinator_reviewed_at, t.reviewed_at, t.updated_at, t.created_at) DESC, t.id DESC\n    ");

    return $stmt->fetchAll() ?: [];
}

function rcAssignmentCandidateKey(string $email, string $name): string
{
    $email = strtolower(trim($email));
    if ($email !== '') {
        return 'email:' . $email;
    }

    return 'name:' . strtolower(trim($name));
}

function rcAssignmentAdviserAccountPool(): array
{
    try {
        $smsPdo = getDatabaseConnection();
        $stmt = $smsPdo->query("
            SELECT id AS assignee_user_id, full_name AS assignee_name, email AS assignee_email, role_key
            FROM `sms2_users`
            WHERE status = 'active'
              AND TRIM(full_name) <> ''
              AND (
                    role_key IN ('adviser', 'research_adviser')
                 OR LOWER(REPLACE(role_key, ' ', '_')) LIKE '%adviser%'
              )
            ORDER BY full_name ASC, id ASC
        ");
        $rows = $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Adviser account candidate sync skipped: ' . $e->getMessage());
        return [];
    }

    return array_map(static fn(array $row): array => [
        'assignee_user_id' => (int) ($row['assignee_user_id'] ?? 0),
        'assignee_name' => (string) ($row['assignee_name'] ?? ''),
        'assignee_email' => (string) ($row['assignee_email'] ?? ''),
        'assignee_role' => 'Research Adviser',
        'expertise' => 'General Research Methods',
        'availability_status' => 'Available',
        'notes' => 'Synced from adviser user account.',
        'updated_at' => date('Y-m-d H:i:s'),
    ], $rows);
}

/**
 * Live User Management advisers, attached to each assignment group so they
 * appear immediately on Find/Contact, Availability, and Assign pages.
 *
 * @param array<int, array<string, mixed>> $groups
 * @return array<int, array<string, mixed>>
 */
function rcAssignmentLiveAdviserDisplayRows(PDO $pdo, array $groups): array
{
    $accounts = rcAssignmentAdviserAccountPool();
    if ($accounts === []) {
        return [];
    }

    $groupList = $groups !== [] ? $groups : [[
        'research_group_id' => 0,
        'proposal_id' => 0,
        'title_approval_id' => 0,
        'proposal_number' => '',
        'group_number' => '',
        'group_name' => '',
        'research_title' => '',
        'college_dept' => '',
        'required_expertise' => 'General Research Methods',
    ]];

    $find = $pdo->prepare("
        SELECT id, expertise, availability_status, assignment_status, notes, assigned_at, updated_at, group_number
        FROM `crad_research_adviser_assignments`
        WHERE (
                (:uid_a > 0 AND adviser_user_id = :uid_b)
             OR (:email_a <> '' AND LOWER(TRIM(adviser_email)) = :email_b)
             OR (:name_a <> '' AND LOWER(TRIM(adviser_name)) = :name_b)
        )
          AND (
                (:gn_a = '' AND (group_number IS NULL OR group_number = ''))
             OR (:gn_b <> '' AND (
                    group_number = :gn_c
                 OR (:gid_a > 0 AND research_group_id = :gid_b)
                 OR (:sid_a <> '' AND student_id = :sid_b)
                 OR (:stu_a <> '' AND group_number = :stu_b)
             ))
          )
        ORDER BY
            (assignment_status = 'Assigned') DESC,
            (assignment_status = 'Confirmed') DESC,
            id DESC
        LIMIT 1
    ");
    $update = $pdo->prepare("
        UPDATE `crad_research_adviser_assignments`
           SET adviser_user_id = COALESCE(:adviser_user_id, adviser_user_id),
               adviser_name = :adviser_name,
               adviser_email = :adviser_email,
               availability_status = CASE
                   WHEN assignment_status = 'Assigned' THEN availability_status
                   ELSE 'Available'
               END,
               notes = COALESCE(NULLIF(notes, ''), 'Synced from adviser user account.'),
               updated_at = NOW()
         WHERE id = :id
         LIMIT 1
    ");

    $out = [];
    foreach ($groupList as $group) {
        $groupNumber = (string) ($group['group_number'] ?? '');
        foreach ($accounts as $account) {
            $email = strtolower(trim((string) ($account['assignee_email'] ?? '')));
            $name = trim((string) ($account['assignee_name'] ?? ''));
            $userId = (int) ($account['assignee_user_id'] ?? 0);
            $groupId = (int) ($group['research_group_id'] ?? $group['id'] ?? 0);
            $leaderId = trim((string) ($group['leader_id'] ?? $group['student_id'] ?? ''));
            $stuNumber = $leaderId !== '' ? cradStudentAssignmentGroupNumber($leaderId) : '';
            $find->execute([
                ':uid_a' => $userId,
                ':uid_b' => $userId,
                ':email_a' => $email,
                ':email_b' => $email,
                ':name_a' => strtolower($name),
                ':name_b' => strtolower($name),
                ':gn_a' => $groupNumber,
                ':gn_b' => $groupNumber,
                ':gn_c' => $groupNumber,
                ':gid_a' => $groupId,
                ':gid_b' => $groupId,
                ':sid_a' => $leaderId,
                ':sid_b' => $leaderId,
                ':stu_a' => $stuNumber,
                ':stu_b' => $stuNumber,
            ]);
            $db = $find->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$db) {
                $db = [
                    'id' => 0,
                    'expertise' => 'General Research Methods',
                    'availability_status' => 'Available',
                    'assignment_status' => 'Pending',
                    'notes' => 'Synced from adviser user account.',
                    'assigned_at' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            } else {
                try {
                    $update->execute([
                        ':adviser_user_id' => $userId > 0 ? $userId : null,
                        ':adviser_name' => $name,
                        ':adviser_email' => (string) ($account['assignee_email'] ?? ''),
                        ':id' => (int) ($db['id'] ?? 0),
                    ]);
                } catch (Throwable $e) {
                    error_log('Live adviser row update skipped: ' . $e->getMessage());
                }
            }

            $out[] = [
                'assignment_kind' => 'adviser',
                'assignment_id' => (int) ($db['id'] ?? 0),
                'title_approval_id' => (int) ($group['title_approval_id'] ?? 0),
                'assignee_user_id' => $userId,
                'assignee_name' => $name,
                'assignee_email' => (string) ($account['assignee_email'] ?? ''),
                'assignee_role' => 'Research Adviser',
                'expertise' => (string) (($db['expertise'] ?? '') !== '' ? $db['expertise'] : ($account['expertise'] ?? 'General Research Methods')),
                'availability_status' => (string) (($db['availability_status'] ?? '') !== '' ? $db['availability_status'] : 'Available'),
                'assignment_status' => (string) (($db['assignment_status'] ?? '') !== '' ? $db['assignment_status'] : 'Pending'),
                'notes' => (string) ($db['notes'] ?? 'Synced from adviser user account.'),
                'assigned_at' => $db['assigned_at'] ?? null,
                'updated_at' => (string) ($db['updated_at'] ?? date('Y-m-d H:i:s')),
                'group_number' => $groupNumber,
                'group_name' => (string) ($group['group_name'] ?? ''),
                'research_title' => (string) ($group['research_title'] ?? ''),
                'college_dept' => (string) ($group['college_dept'] ?? ''),
                'proposal_number' => (string) ($group['proposal_number'] ?? ''),
                'leader_id' => $leaderId,
                'student_id' => $leaderId,
                'source' => 'user_account',
            ];
        }
    }

    return $out;
}

function rcAssignmentMergeLiveAdviserRows(array $rows, array $liveRows): array
{
    $seen = [];
    $isAssigned = static function (array $row): bool {
        return strcasecmp((string) ($row['assignment_status'] ?? ''), 'Assigned') === 0
            || strcasecmp((string) ($row['assignment_status'] ?? ''), 'Confirmed') === 0;
    };

    foreach (array_merge($rows, $liveRows) as $row) {
        $key = strtolower(trim((string) ($row['group_number'] ?? ''))) . '|' . rcAssignmentCandidateKey(
            (string) ($row['assignee_email'] ?? ''),
            (string) ($row['assignee_name'] ?? '')
        );
        if ($key === '|' || $key === '|name:') {
            continue;
        }
        if (!isset($seen[$key])) {
            $seen[$key] = $row;
            continue;
        }
        $current = $seen[$key];
        if ($isAssigned($row) && !$isAssigned($current)) {
            $seen[$key] = $row;
        } elseif ((int) ($row['assignment_id'] ?? 0) > (int) ($current['assignment_id'] ?? 0) && $isAssigned($row) === $isAssigned($current)) {
            $seen[$key] = $row;
        }
    }

    return array_values($seen);
}

function rcAssignmentResolveAdviserUserId(string $email, string $name): ?int
{
    $email = strtolower(trim($email));
    $name = strtolower(trim($name));
    if ($email === '' && $name === '') {
        return null;
    }

    try {
        $smsPdo = getDatabaseConnection();
        $stmt = $smsPdo->prepare("
            SELECT id
            FROM `sms2_users`
            WHERE role_key IN ('adviser', 'research_adviser')
              AND status = 'active'
              AND (
                    (:email_gate <> '' AND LOWER(TRIM(email)) = :email_match)
                 OR (:name_gate <> '' AND LOWER(TRIM(full_name)) = :name_match)
              )
            ORDER BY
              CASE WHEN :email_gate_order <> '' AND LOWER(TRIM(email)) = :email_match_order THEN 0 ELSE 1 END,
              id ASC
            LIMIT 1
        ");
        $stmt->execute([
            ':email_gate' => $email,
            ':email_match' => $email,
            ':name_gate' => $name,
            ':name_match' => $name,
            ':email_gate_order' => $email,
            ':email_match_order' => $email,
        ]);
        $userId = (int) $stmt->fetchColumn();
        return $userId > 0 ? $userId : null;
    } catch (Throwable $e) {
        error_log('Adviser user id resolution failed: ' . $e->getMessage());
        return null;
    }
}

function rcAssignmentCandidatePool(PDO $pdo): array
{
    $rows = $pdo->query("
        SELECT
            adviser_name AS assignee_name,
            adviser_email AS assignee_email,
            adviser_user_id AS assignee_user_id,
            'Research Adviser' AS assignee_role,
            expertise,
            availability_status,
            notes,
            updated_at
        FROM `crad_research_adviser_assignments`
        WHERE adviser_name <> ''
        ORDER BY updated_at DESC, id DESC
    ")->fetchAll() ?: [];

    $pool = [];
    foreach (array_merge(rcAssignmentAdviserAccountPool(), $rows) as $row) {
        $key = rcAssignmentCandidateKey((string) ($row['assignee_email'] ?? ''), (string) ($row['assignee_name'] ?? ''));
        if ($key === 'name:' || isset($pool[$key])) {
            continue;
        }
        $pool[$key] = $row;
    }

    return array_values($pool);
}

function rcAssignmentEnsureGroupCandidateRows(PDO $pdo, array $groups): void
{
    if ($groups === []) {
        return;
    }

    $advisers = rcAssignmentCandidatePool($pdo);
    $adviserExists = $pdo->prepare("
        SELECT id, expertise, availability_status, assignment_status
        FROM `crad_research_adviser_assignments`
        WHERE (
                (:email_gate <> '' AND LOWER(TRIM(adviser_email)) = :email_match)
             OR (:name_gate <> '' AND LOWER(TRIM(adviser_name)) = :name_match)
        )
          AND (
                (:group_number_gate <> '' AND group_number = :group_number_match)
             OR (:research_group_id_gate > 0 AND research_group_id = :research_group_id_match)
             OR (:student_id_gate <> '' AND student_id = :student_id_match)
             OR (:stu_gate <> '' AND group_number = :stu_match)
          )
        ORDER BY
            (assignment_status = 'Assigned') DESC,
            (assignment_status = 'Confirmed') DESC,
            id DESC
        LIMIT 1
    ");
    $updateAdviser = $pdo->prepare("
        UPDATE `crad_research_adviser_assignments`
           SET research_group_id = COALESCE(:research_group_id, research_group_id),
               proposal_id = COALESCE(:proposal_id, proposal_id),
               proposal_number = COALESCE(NULLIF(:proposal_number, ''), proposal_number),
               group_number = COALESCE(NULLIF(:group_number, ''), group_number),
               student_id = COALESCE(NULLIF(:student_id, ''), student_id),
               adviser_user_id = COALESCE(:adviser_user_id, adviser_user_id),
               adviser_name = COALESCE(NULLIF(:adviser_name, ''), adviser_name),
               adviser_email = COALESCE(NULLIF(:adviser_email, ''), adviser_email),
               expertise = CASE
                   WHEN TRIM(expertise) = '' THEN :expertise
                   ELSE expertise
               END,
               availability_status = CASE
                   WHEN TRIM(availability_status) = '' THEN :availability_status
                   ELSE availability_status
               END,
               notes = COALESCE(NULLIF(notes, ''), :notes),
               updated_at = NOW()
         WHERE id = :id
         LIMIT 1
    ");
    $insertAdviser = $pdo->prepare("
        INSERT INTO `crad_research_adviser_assignments`
            (research_group_id, proposal_id, proposal_number, group_number, student_id, adviser_user_id, adviser_name, adviser_email, expertise, availability_status, assignment_status, notes, assigned_by, assigned_at, created_at, updated_at, notification_sent_at, notification_sent_by)
        VALUES
            (:research_group_id, :proposal_id, :proposal_number, :group_number, NULLIF(:student_id, ''), :adviser_user_id, :adviser_name, :adviser_email, :expertise, :availability_status, 'Pending', :notes, NULL, NULL, NOW(), NOW(), NULL, NULL)
    ");

    foreach ($groups as $group) {
        $groupAdviserName = trim((string) ($group['approved_adviser_name'] ?? ''));
        $groupAdviserEmail = trim((string) ($group['approved_adviser_email'] ?? ''));
        $groupAdvisers = $advisers;
        if ($groupAdviserName !== '' || $groupAdviserEmail !== '') {
            array_unshift($groupAdvisers, [
                'assignee_name' => $groupAdviserName,
                'assignee_email' => $groupAdviserEmail,
                'assignee_role' => 'Research Adviser',
                'expertise' => (string) (($group['required_expertise'] ?? '') ?: 'General Research Methods'),
                'availability_status' => 'Available',
                'notes' => 'Synced from fully approved research record.',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $groupParams = [
            ':research_group_id' => rcAssignmentNullableInt($group['research_group_id'] ?? null),
            ':proposal_id' => rcAssignmentNullableInt($group['proposal_id'] ?? null),
            ':proposal_number' => (string) ($group['proposal_number'] ?? ''),
            ':group_number' => (string) ($group['group_number'] ?? ''),
            ':student_id' => trim((string) ($group['leader_id'] ?? $group['student_id'] ?? '')),
        ];

        foreach ($groupAdvisers as $adviser) {
            $email = strtolower(trim((string) ($adviser['assignee_email'] ?? '')));
            $name = strtolower(trim((string) ($adviser['assignee_name'] ?? '')));
            $leaderId = trim((string) ($group['leader_id'] ?? $group['student_id'] ?? ''));
            $stuNumber = $leaderId !== '' ? cradStudentAssignmentGroupNumber($leaderId) : '';
            $adviserExists->execute([
                ':email_gate' => $email,
                ':email_match' => $email,
                ':name_gate' => $name,
                ':name_match' => $name,
                ':group_number_gate' => (string) ($group['group_number'] ?? ''),
                ':group_number_match' => (string) ($group['group_number'] ?? ''),
                ':research_group_id_gate' => (int) ($group['research_group_id'] ?? 0),
                ':research_group_id_match' => (int) ($group['research_group_id'] ?? 0),
                ':student_id_gate' => $leaderId,
                ':student_id_match' => $leaderId,
                ':stu_gate' => $stuNumber,
                ':stu_match' => $stuNumber,
            ]);
            $existing = $adviserExists->fetch();
            $adviserUserId = rcAssignmentNullableInt($adviser['assignee_user_id'] ?? null)
                ?? rcAssignmentResolveAdviserUserId(
                    (string) ($adviser['assignee_email'] ?? ''),
                    (string) ($adviser['assignee_name'] ?? '')
                );
            $adviserParams = [
                ':adviser_user_id' => $adviserUserId,
                ':adviser_name' => (string) ($adviser['assignee_name'] ?? ''),
                ':adviser_email' => (string) ($adviser['assignee_email'] ?? ''),
                ':expertise' => (string) (($adviser['expertise'] ?? '') ?: 'General Research Methods'),
                ':availability_status' => (string) (($adviser['availability_status'] ?? '') ?: 'Pending'),
                ':notes' => (string) ($adviser['notes'] ?? ''),
            ];
            if ($existing) {
                $updateAdviser->execute($groupParams + $adviserParams + [
                    ':id' => (int) ($existing['id'] ?? 0),
                ]);
                continue;
            }
            try {
                $insertAdviser->execute($groupParams + $adviserParams);
            } catch (Throwable $e) {
                error_log('Adviser candidate insert skipped: ' . $e->getMessage());
            }
        }
    }
}

function rcAssignmentEnrichRows(array $rows): array
{
    foreach ($rows as &$row) {
        $requiredExpertise = rcAssignmentRequiredExpertise((string) ($row['research_title'] ?? ''));
        $row['required_expertise'] = $requiredExpertise;
        $row['match_score'] = rcAssignmentMatchScore($row, $requiredExpertise);
    }
    unset($row);

    usort($rows, static function (array $a, array $b): int {
        $scoreCompare = ((int) ($b['match_score'] ?? 0)) <=> ((int) ($a['match_score'] ?? 0));
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }

        return strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? ''));
    });

    return $rows;
}

function rcAssignmentAddRecipient(array &$recipients, array $recipient): void
{
    $key = smsNotificationRecipientKey($recipient);
    if ($key === 'role:' || isset($recipients[$key])) {
        return;
    }
    $recipients[$key] = $recipient;
}

function rcAssignmentCompletionGroup(PDO $pdo, array $candidate, string $groupNumber): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            g.id AS research_group_id,
            g.proposal_id,
            COALESCE(p.proposal_number, t.proposal_number, g.proposal_number) AS proposal_number,
            g.group_number,
            g.group_name,
            COALESCE(NULLIF(g.research_title, ''), p.research_title, t.proposed_title) AS research_title,
            COALESCE(NULLIF(g.leader_name, ''), p.rep_name, t.student_name) AS leader_name,
            COALESCE(NULLIF(g.leader_id, ''), p.rep_id, t.student_id) AS leader_id,
            COALESCE(NULLIF(g.leader_email, ''), p.rep_email, '') AS leader_email,
            p.rep_name,
            p.rep_id,
            p.rep_email,
            p.submitted_by_user
         FROM `crad_research_groups` g
         LEFT JOIN `crad_research_proposals` p ON p.id = g.proposal_id
         LEFT JOIN `crad_title_approvals` t ON t.id = g.title_approval_id
         WHERE (
                (p.id IS NOT NULL AND p.status = 'Approved' AND p.registration_status = 'Registered')
             OR (
                t.id IS NOT NULL
                AND t.status = 'Approved'
                AND t.coordinator_status = 'Approved'
                AND t.crad_status = 'Approved'
                AND t.adviser_signature_data IS NOT NULL
                AND t.adviser_signature_data <> ''
                AND t.coordinator_signature_data IS NOT NULL
                AND t.coordinator_signature_data <> ''
                AND t.crad_signature_data IS NOT NULL
                AND t.crad_signature_data <> ''
             )
           )
           AND (
                (:research_group_gate > 0 AND g.id = :research_group_match)
             OR (:group_number_gate <> '' AND g.group_number = :group_number_match)
             OR (:proposal_id_gate > 0 AND g.proposal_id = :proposal_id_match)
           )
         ORDER BY g.id DESC
         LIMIT 1
    ");
    $stmt->execute([
        ':research_group_gate' => (int) ($candidate['research_group_id'] ?? 0),
        ':research_group_match' => (int) ($candidate['research_group_id'] ?? 0),
        ':group_number_gate' => $groupNumber !== '' ? $groupNumber : (string) ($candidate['group_number'] ?? ''),
        ':group_number_match' => $groupNumber !== '' ? $groupNumber : (string) ($candidate['group_number'] ?? ''),
        ':proposal_id_gate' => (int) ($candidate['proposal_id'] ?? 0),
        ':proposal_id_match' => (int) ($candidate['proposal_id'] ?? 0),
    ]);
    $group = $stmt->fetch();
    return $group ?: null;
}

function rcAssignmentGroupWhere(string $alias = 'a'): string
{
    return "(($alias.research_group_id IS NOT NULL AND $alias.research_group_id = :research_group_id)
        OR (:group_number_gate <> '' AND $alias.group_number = :group_number_match)
        OR ($alias.proposal_id IS NOT NULL AND $alias.proposal_id = :proposal_id))";
}

function rcAssignmentAssignedParties(PDO $pdo, array $group): array
{
    $params = [
        ':research_group_id' => (int) ($group['research_group_id'] ?? 0),
        ':group_number_gate' => (string) ($group['group_number'] ?? ''),
        ':group_number_match' => (string) ($group['group_number'] ?? ''),
        ':proposal_id' => (int) ($group['proposal_id'] ?? 0),
    ];

    $adviserStmt = $pdo->prepare("
        SELECT adviser_name, adviser_email
        FROM `crad_research_adviser_assignments` a
        WHERE assignment_status = 'Assigned' AND " . rcAssignmentGroupWhere('a') . "
        ORDER BY assigned_at DESC, updated_at DESC, id DESC
        LIMIT 1
    ");
    $adviserStmt->execute($params);
    $adviser = $adviserStmt->fetch() ?: null;

    return ['adviser' => $adviser, 'panels' => []];
}

function rcAssignmentResetOtherRowsForGroup(PDO $pdo, string $table, int $keepId, array $selectedGroup): void
{
    if (!in_array($table, ['research_adviser_assignments', 'crad_research_adviser_assignments'], true) || $keepId <= 0) {
        return;
    }

    $table = 'crad_research_adviser_assignments';
    $stmt = $pdo->prepare("
        UPDATE `{$table}`
           SET assignment_status = 'Pending',
               assigned_by = NULL,
               assigned_at = NULL,
               notification_sent_at = NULL,
               notification_sent_by = NULL,
               updated_at = NOW()
         WHERE id <> :keep_id
           AND assignment_status = 'Assigned'
           AND (
                research_group_id = :research_group_id
             OR group_number = :group_number
             OR proposal_id = :proposal_id
           )
    ");
    $stmt->execute([
        ':keep_id' => $keepId,
        ':research_group_id' => (int) ($selectedGroup['id'] ?? 0),
        ':group_number' => (string) ($selectedGroup['group_number'] ?? ''),
        ':proposal_id' => (int) ($selectedGroup['proposal_id'] ?? 0),
    ]);
}

function rcAssignmentCollapseDuplicatePendingRows(PDO $pdo, int $keepId, array $candidate, array $selectedGroup): void
{
    if ($keepId <= 0) {
        return;
    }

    $email = strtolower(trim((string) ($candidate['adviser_email'] ?? '')));
    $name = strtolower(trim((string) ($candidate['adviser_name'] ?? '')));
    $userId = (int) ($candidate['adviser_user_id'] ?? 0);

    $stmt = $pdo->prepare("
        DELETE FROM `crad_research_adviser_assignments`
         WHERE id <> :keep_id
           AND assignment_status <> 'Assigned'
           AND (
                (:uid_a > 0 AND adviser_user_id = :uid_b)
             OR (:email_a <> '' AND LOWER(TRIM(adviser_email)) = :email_b)
             OR (:name_a <> '' AND LOWER(TRIM(adviser_name)) = :name_b)
           )
           AND (
                research_group_id = :research_group_id
             OR group_number = :group_number
             OR proposal_id = :proposal_id
           )
    ");
    $stmt->execute([
        ':keep_id' => $keepId,
        ':uid_a' => $userId,
        ':uid_b' => $userId,
        ':email_a' => $email,
        ':email_b' => $email,
        ':name_a' => $name,
        ':name_b' => $name,
        ':research_group_id' => (int) ($selectedGroup['id'] ?? 0),
        ':group_number' => (string) ($selectedGroup['group_number'] ?? ''),
        ':proposal_id' => (int) ($selectedGroup['proposal_id'] ?? 0),
    ]);
}

function rcAssignmentFindCandidateRowForGroup(PDO $pdo, string $kind, array $candidate, array $selectedGroup): ?array
{
    if ($kind !== 'adviser') {
        return null;
    }

    $table = 'crad_research_adviser_assignments';
    $nameColumn = 'adviser_name';
    $emailColumn = 'adviser_email';
    $email = strtolower(trim((string) ($candidate[$emailColumn] ?? '')));
    $name = strtolower(trim((string) ($candidate[$nameColumn] ?? '')));

    $stmt = $pdo->prepare("
        SELECT *
        FROM `{$table}`
        WHERE (
                (:email_gate <> '' AND LOWER(TRIM({$emailColumn})) = :email_match)
             OR (:name_gate <> '' AND LOWER(TRIM({$nameColumn})) = :name_match)
        )
          AND (
                research_group_id = :research_group_id
             OR group_number = :group_number
             OR proposal_id = :proposal_id
          )
        ORDER BY
            (assignment_status = 'Assigned') DESC,
            (assignment_status = 'Confirmed') DESC,
            id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':email_gate' => $email,
        ':email_match' => $email,
        ':name_gate' => $name,
        ':name_match' => $name,
        ':research_group_id' => (int) ($selectedGroup['id'] ?? 0),
        ':group_number' => (string) ($selectedGroup['group_number'] ?? ''),
        ':proposal_id' => (int) ($selectedGroup['proposal_id'] ?? 0),
    ]);

    $row = $stmt->fetch();
    return $row ?: null;
}

function rcAssignmentProposalStudentRecipients(PDO $pdo, array $group): array
{
    $proposalId = (int) ($group['proposal_id'] ?? 0);
    if ($proposalId <= 0) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT student_id, student_name, email
            FROM `crad_proposal_members`
            WHERE proposal_id = :proposal_id
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([':proposal_id' => $proposalId]);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Assignment notification student member lookup failed: ' . $e->getMessage());
        return [];
    }
}

function rcAssignmentMaybeSendCompletionNotifications(PDO $pdo, array $candidate, string $groupNumber, ?int $userId): void
{
    $group = rcAssignmentCompletionGroup($pdo, $candidate, $groupNumber);
    if (!$group) {
        return;
    }

    $parties = rcAssignmentAssignedParties($pdo, $group);
    if (!$parties['adviser']) {
        return;
    }

    smsMarkResearchAdviserAssignmentNotificationSent($pdo, $groupNumber, $userId);
}

function rcAssignmentApplyOfficialAssignedState(PDO $pdo, array $groups): void
{
    foreach ($groups as $group) {
        $groupNumber = trim((string) ($group['group_number'] ?? ''));
        if ($groupNumber === '' || str_starts_with($groupNumber, 'STU-')) {
            continue;
        }
        $groupId = (int) ($group['research_group_id'] ?? $group['id'] ?? 0);
        $leaderId = trim((string) ($group['leader_id'] ?? $group['student_id'] ?? ''));
        $stuNumber = $leaderId !== '' ? cradStudentAssignmentGroupNumber($leaderId) : '';

        try {
            $assigned = $pdo->prepare("
                SELECT id, adviser_name, adviser_email, adviser_user_id
                FROM `crad_research_adviser_assignments`
                WHERE assignment_status IN ('Assigned', 'Confirmed')
                  AND (
                        group_number = :gn_a
                     OR (:gid_a > 0 AND research_group_id = :gid_b)
                     OR (:sid_a <> '' AND student_id = :sid_b)
                     OR (:stu_a <> '' AND group_number = :stu_b)
                  )
                ORDER BY (assignment_status = 'Confirmed') DESC, assigned_at DESC, id DESC
            ");
            $assigned->execute([
                ':gn_a' => $groupNumber,
                ':gid_a' => $groupId,
                ':gid_b' => $groupId,
                ':sid_a' => $leaderId,
                ':sid_b' => $leaderId,
                ':stu_a' => $stuNumber,
                ':stu_b' => $stuNumber,
            ]);
            $keep = $assigned->fetch(PDO::FETCH_ASSOC);
            if (!$keep) {
                continue;
            }
            $keepId = (int) ($keep['id'] ?? 0);
            if ($keepId <= 0) {
                continue;
            }

            $pdo->prepare("
                UPDATE `crad_research_adviser_assignments`
                   SET research_group_id = COALESCE(:gid, research_group_id),
                       group_number = :gn,
                       student_id = COALESCE(NULLIF(:sid, ''), student_id),
                       assignment_status = 'Assigned',
                       updated_at = NOW()
                 WHERE id = :id
                 LIMIT 1
            ")->execute([
                ':gid' => $groupId > 0 ? $groupId : null,
                ':gn' => $groupNumber,
                ':sid' => $leaderId,
                ':id' => $keepId,
            ]);

            $email = strtolower(trim((string) ($keep['adviser_email'] ?? '')));
            $name = strtolower(trim((string) ($keep['adviser_name'] ?? '')));
            $userId = (int) ($keep['adviser_user_id'] ?? 0);
            $pdo->prepare("
                DELETE FROM `crad_research_adviser_assignments`
                 WHERE id <> :keep_id
                   AND assignment_status <> 'Assigned'
                   AND (
                        (:uid_a > 0 AND adviser_user_id = :uid_b)
                     OR (:email_a <> '' AND LOWER(TRIM(adviser_email)) = :email_b)
                     OR (:name_a <> '' AND LOWER(TRIM(adviser_name)) = :name_b)
                   )
                   AND (
                        group_number = :gn
                     OR (:gid > 0 AND research_group_id = :gid2)
                     OR (:sid <> '' AND student_id = :sid2)
                     OR (:stu <> '' AND group_number = :stu2)
                   )
            ")->execute([
                ':keep_id' => $keepId,
                ':uid_a' => $userId,
                ':uid_b' => $userId,
                ':email_a' => $email,
                ':email_b' => $email,
                ':name_a' => $name,
                ':name_b' => $name,
                ':gn' => $groupNumber,
                ':gid' => $groupId,
                ':gid2' => $groupId,
                ':sid' => $leaderId,
                ':sid2' => $leaderId,
                ':stu' => $stuNumber,
                ':stu2' => $stuNumber,
            ]);
        } catch (Throwable $e) {
            error_log('Official adviser assigned-state apply skipped: ' . $e->getMessage());
        }
    }
}

function rcAssignmentPayload(string $kind): array
{
    try {
        $pdo = getCradDatabaseConnection();
        rcAssignmentEnsureSchema($pdo);
        cradPruneDeletedTitleApprovalDependents($pdo);
        rcAssignmentSyncApprovedTitleGroups($pdo);
        rcAssignmentResetStaleAssignments($pdo);
        $groups = rcAssignmentApprovedGroups($pdo);
        rcAssignmentEnsureGroupCandidateRows($pdo, $groups);
        rcAssignmentApplyOfficialAssignedState($pdo, $groups);
        $rows = rcAssignmentEnrichRows(rcAssignmentRows($pdo, $kind));
        $liveRows = rcAssignmentLiveAdviserDisplayRows($pdo, $groups);
        $rows = rcAssignmentEnrichRows(rcAssignmentMergeLiveAdviserRows($rows, $liveRows));
        global $rcPageSlug;
        if (($rcPageSlug ?? '') === 'assign-research-adviser') {
            $groups = array_values(array_filter(
                $groups,
                static fn(array $group): bool => cradGroupHasActiveCoordinator($pdo, $group)
            ));
            $officialLeaders = [];
            foreach ($groups as $group) {
                $gn = (string) ($group['group_number'] ?? '');
                $leader = trim((string) ($group['leader_id'] ?? ''));
                if ($leader !== '' && $gn !== '' && !str_starts_with($gn, 'STU-')) {
                    $officialLeaders[strtolower($leader)] = true;
                }
            }
            if ($officialLeaders !== []) {
                $groups = array_values(array_filter(
                    $groups,
                    static function (array $group) use ($officialLeaders): bool {
                        $gn = (string) ($group['group_number'] ?? '');
                        $leader = strtolower(trim((string) ($group['leader_id'] ?? '')));
                        if (str_starts_with($gn, 'STU-') && $leader !== '' && isset($officialLeaders[$leader])) {
                            return false;
                        }
                        return true;
                    }
                ));
            }
        }
        if (($rcPageSlug ?? '') === 'retrieve-approved-research') {
            $groups = array_values(array_filter($groups, static fn(array $group): bool => (int) ($group['title_approval_id'] ?? 0) > 0));
        }
        if (($rcPageSlug ?? '') === 'retrieve-approved-research') {
            $approvedAdvisers = [];
            foreach ($groups as $group) {
                $key = (string) ($group['group_number'] ?? '');
                if ($key === '') {
                    continue;
                }
                $approvedAdvisers[$key] = [
                    'email' => strtolower(trim((string) ($group['approved_adviser_email'] ?? ''))),
                    'name' => strtolower(trim((string) ($group['approved_adviser_name'] ?? ''))),
                ];
            }
            $rows = array_values(array_filter($rows, static function (array $row) use ($approvedAdvisers): bool {
                if ((int) ($row['title_approval_id'] ?? 0) <= 0) {
                    return false;
                }
                $groupNumber = (string) ($row['group_number'] ?? '');
                if (!isset($approvedAdvisers[$groupNumber])) {
                    return false;
                }
                $approved = $approvedAdvisers[$groupNumber];
                $rowEmail = strtolower(trim((string) ($row['assignee_email'] ?? '')));
                $rowName = strtolower(trim((string) ($row['assignee_name'] ?? '')));
                return ($approved['email'] !== '' && $rowEmail === $approved['email'])
                    || ($approved['name'] !== '' && $rowName === $approved['name']);
            }));
            // The monitoring screen must retain every adviser-approved title,
            // including those still awaiting coordinator or CRAD signatures.
            $groups = rcAssignmentRetrieveApprovalGroups($pdo);
            $rows = [];
        }
    } catch (Throwable $e) {
        error_log('Research Coordinator assignment load failed: ' . $e->getMessage());
        return [
            'ok' => false,
            'error' => 'Failed to load assignment records. ' . $e->getMessage(),
            'rows' => [],
            'groups' => [],
            'stats' => ['total' => 0, 'pending' => 0, 'available' => 0, 'assigned' => 0],
            'last_sync' => date('M j, Y g:i:s A'),
        ];
    }

    $statsRows = (($rcPageSlug ?? '') === 'retrieve-approved-research') ? $groups : $rows;
    return [
        'ok' => true,
        'rows' => $rows,
        'groups' => $groups,
        'stats' => [
            'total' => count($statsRows),
            'pending' => count(array_filter($statsRows, static fn($row) => strcasecmp((string) ($row['assignment_status'] ?? ''), 'Pending') === 0)),
            'available' => count(array_filter($statsRows, static fn($row) => strcasecmp((string) ($row['availability_status'] ?? ''), 'Available') === 0)),
            'assigned' => count(array_filter($statsRows, static fn($row) => strcasecmp((string) ($row['assignment_status'] ?? ''), 'Assigned') === 0)),
        ],
        'last_sync' => date('M j, Y g:i:s A'),
    ];
}

function rcAssignmentSave(PDO $pdo, string $kind, int $assignmentId, string $groupNumber, ?int $userId): array
{
    if ($assignmentId <= 0) {
        throw new RuntimeException('Invalid assignment record.');
    }
    if ($groupNumber === '') {
        throw new RuntimeException('Please select an approved research group first.');
    }

    $groupStmt = $pdo->prepare("
        SELECT g.id, g.proposal_id, COALESCE(p.proposal_number, t.proposal_number, g.proposal_number) AS proposal_number,
               g.group_number, g.leader_id, g.title_approval_id
        FROM `crad_research_groups` g
        LEFT JOIN `crad_research_proposals` p ON p.id = g.proposal_id
        LEFT JOIN `crad_title_approvals` t ON t.id = g.title_approval_id
        WHERE g.group_number = :group_number
        LIMIT 1
    ");
    $groupStmt->execute([':group_number' => $groupNumber]);
    $selectedGroup = $groupStmt->fetch();
    if (!$selectedGroup) {
        throw new RuntimeException('Selected research group is not available for assignment.');
    }
    if (!cradGroupHasActiveCoordinator($pdo, [
        'id' => (int) ($selectedGroup['id'] ?? 0),
        'research_group_id' => (int) ($selectedGroup['id'] ?? 0),
        'group_number' => (string) ($selectedGroup['group_number'] ?? ''),
        'leader_id' => (string) ($selectedGroup['leader_id'] ?? ''),
        'student_id' => (string) ($selectedGroup['leader_id'] ?? ''),
    ])) {
        throw new RuntimeException('Assign a Research Coordinator from the Coordinator Roster first.');
    }
    rcAssignmentEnsureGroupCandidateRows($pdo, [[
        'research_group_id' => rcAssignmentNullableInt($selectedGroup['id'] ?? null),
        'proposal_id' => rcAssignmentNullableInt($selectedGroup['proposal_id'] ?? null),
        'proposal_number' => (string) ($selectedGroup['proposal_number'] ?? ''),
        'group_number' => (string) ($selectedGroup['group_number'] ?? ''),
    ]]);

    $matchesSelectedGroup = static function (array $candidate) use ($selectedGroup): bool {
        return ((int) ($candidate['research_group_id'] ?? 0) > 0 && (int) $candidate['research_group_id'] === (int) $selectedGroup['id'])
            || ((string) ($candidate['group_number'] ?? '') !== '' && (string) $candidate['group_number'] === (string) $selectedGroup['group_number'])
            || ((int) ($candidate['proposal_id'] ?? 0) > 0 && (int) $candidate['proposal_id'] === (int) $selectedGroup['proposal_id']);
    };

    if ($kind !== 'adviser') {
        throw new RuntimeException('Only research adviser assignment is available.');
    }

    $candidateStmt = $pdo->prepare("SELECT * FROM `crad_research_adviser_assignments` WHERE id = :id LIMIT 1");
    $candidateStmt->execute([':id' => $assignmentId]);
    $candidate = $candidateStmt->fetch();
    if (!$candidate) {
        throw new RuntimeException('Adviser candidate not found.');
    }
    if (!$matchesSelectedGroup($candidate)) {
        $groupCandidate = rcAssignmentFindCandidateRowForGroup($pdo, 'adviser', $candidate, $selectedGroup);
        if (!$groupCandidate) {
            throw new RuntimeException('Adviser candidate was not prepared for the selected group.');
        }
        $candidate = $groupCandidate;
        $assignmentId = (int) ($candidate['id'] ?? 0);
    }
    if (!$matchesSelectedGroup($candidate)) {
        throw new RuntimeException('Adviser candidate does not match the selected research group.');
    }
    if (strcasecmp((string) ($candidate['availability_status'] ?? ''), 'Available') !== 0) {
        throw new RuntimeException('Cannot assign this research adviser because availability is not Available.');
    }
    $resolvedAdviserUserId = rcAssignmentNullableInt($candidate['adviser_user_id'] ?? null)
        ?? rcAssignmentResolveAdviserUserId(
            (string) ($candidate['adviser_email'] ?? ''),
            (string) ($candidate['adviser_name'] ?? '')
        );
    if ($matchesSelectedGroup($candidate) && strcasecmp((string) ($candidate['assignment_status'] ?? ''), 'Assigned') === 0) {
        if ($resolvedAdviserUserId !== null && (int) ($candidate['adviser_user_id'] ?? 0) <= 0) {
            $stampUser = $pdo->prepare("
                UPDATE `crad_research_adviser_assignments`
                   SET adviser_user_id = :adviser_user_id,
                       updated_at = NOW()
                 WHERE id = :id
                 LIMIT 1
            ");
            $stampUser->execute([
                ':adviser_user_id' => $resolvedAdviserUserId,
                ':id' => $assignmentId,
            ]);
        }
        rcAssignmentMaybeSendCompletionNotifications($pdo, $candidate, $groupNumber, $userId);
                rcAssignmentStartDualConfirmCycle($pdo, $groupNumber, $userId);
        return ['message' => 'Research adviser is already assigned.'];
    }

    $startedTransaction = !$pdo->inTransaction();
    if ($startedTransaction) {
        $pdo->beginTransaction();
    }

    try {
        if ($matchesSelectedGroup($candidate)) {
            rcAssignmentResetOtherRowsForGroup($pdo, 'crad_research_adviser_assignments', $assignmentId, $selectedGroup);
            $stmt = $pdo->prepare("
                UPDATE `crad_research_adviser_assignments`
                   SET assignment_status = 'Assigned',
                       adviser_user_id = COALESCE(:adviser_user_id, adviser_user_id),
                       assigned_by = :assigned_by,
                       assigned_at = NOW(),
                       notification_sent_at = NULL,
                       notification_sent_by = NULL,
                       updated_at = NOW()
                 WHERE id = :id
            ");
            $stmt->execute([
                ':adviser_user_id' => $resolvedAdviserUserId,
                ':assigned_by' => $userId,
                ':id' => $assignmentId,
            ]);
            rcAssignmentCollapseDuplicatePendingRows($pdo, $assignmentId, $candidate, $selectedGroup);
        }
        rcAssignmentMaybeSendCompletionNotifications($pdo, $candidate, $groupNumber, $userId);
                rcAssignmentStartDualConfirmCycle($pdo, $groupNumber, $userId);

        if ($startedTransaction) {
            $pdo->commit();
        }
        $studentId = cradStudentIdFromAssignmentGroup(
            (string) ($selectedGroup['group_number'] ?? ''),
            [
                'leader_id' => (string) ($selectedGroup['leader_id'] ?? ''),
                'student_id' => (string) ($selectedGroup['leader_id'] ?? ''),
            ]
        );
        if ($studentId !== '') {
            try {
                $pdo->prepare("UPDATE `crad_research_adviser_assignments` SET student_id = :sid, updated_at = NOW() WHERE id = :id LIMIT 1")
                    ->execute([':sid' => $studentId, ':id' => $assignmentId]);
            } catch (Throwable $e) {
                error_log('Adviser student_id stamp skipped: ' . $e->getMessage());
            }
            cradSyncTitleApprovalAssigneeNames($pdo, $studentId);
        }
    } catch (Throwable $e) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return ['message' => 'Research adviser assigned successfully.'];
}

if (($_POST['ajax'] ?? '') === 'assign') {
    header('Content-Type: application/json; charset=utf-8');
    if (!smsCanManageCoordinatorAssignments($roleKey)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => $rcAssignDeniedMessage, 'message' => $rcAssignDeniedMessage]);
        exit;
    }
    try {
        $pdo = getCradDatabaseConnection();
        rcAssignmentEnsureSchema($pdo);
        $result = rcAssignmentSave(
            $pdo,
            'adviser',
            (int) ($_POST['assignment_id'] ?? 0),
            trim((string) ($_POST['group_number'] ?? '')),
            getCurrentUserId()
        );
        echo json_encode(['ok' => true] + $result + rcAssignmentPayload($rcAssignmentKind));
    } catch (Throwable $e) {
        error_log('Research Coordinator assignment save failed: ' . $e->getMessage());
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if (($_GET['ajax'] ?? '') === 'assignments') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo json_encode(rcAssignmentPayload($rcAssignmentKind));
    exit;
}

function rcAssignE(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$payload = rcAssignmentPayload($rcAssignmentKind);
$rows = $payload['rows'];
$groups = $payload['groups'];
$stats = $payload['stats'];
$pageTitle = $pageConfig['title'];
$activeModule = 'crad';
$activePage = $rcPageSlug;
$rcNavLabel = getCurrentUserRoleKey() === 'department_head' ? 'Research Management' : 'Research Coordinator';
$breadcrumbs = [
    ['label' => $rcNavLabel, 'url' => getCurrentUserRoleKey() === 'department_head'
        ? BASE_URL . '/modules/crad/pages/research-coordinator-management.php'
        : BASE_URL . '/modules/crad/index.php'],
    ['label' => $pageTitle, 'url' => null],
];

$endpoint = BASE_URL . '/modules/crad/pages/' . $rcPageSlug . '.php?ajax=assignments';
$emptyText = 'No adviser assignment records yet.';
$isRetrievePage = $rcPageSlug === 'retrieve-approved-research';
$isFindContactPage = $rcPageSlug === 'find-contact-adviser';
$isAvailabilityPage = $rcPageSlug === 'adviser-availability';
$isAssignPage = $rcPageSlug === 'assign-research-adviser';
$isPanelPage = false;
$processTitle = $isAssignPage
    ? ($isPanelPage ? 'Assign Panel Members' : 'Assign Research Adviser')
    : ($isRetrievePage
    ? 'Retrieved Approved Research'
    : ($isAvailabilityPage
    ? ($isPanelPage ? 'Check Availability - Panel' : 'Check Availability - Adviser')
    : 'Contact Based on Expertise'));
$processLead = $isAssignPage
    ? 'Save the selected qualified faculty to the approved research group'
    : ($isRetrievePage
    ? 'Approved adviser, coordinator, and CRAD research is synced into adviser assignment automatically'
    : ($isAvailabilityPage
    ? 'Live availability from existing assignments and saved schedule status'
    : 'Live from approved research groups and ' . $pageConfig['lead'] . ' records'));
$processEmpty = $isAssignPage
    ? 'No available assignment candidates for this approved group yet.'
    : ($isRetrievePage
    ? 'No retrieved approved research with adviser candidates yet.'
    : ($isAvailabilityPage
    ? 'No availability records for this approved group yet.'
    : 'No matching records for this approved group yet.'));
$secondStepIcon = $isRetrievePage ? 'fa-sync-alt' : ($isAssignPage || $isAvailabilityPage ? 'fa-briefcase' : 'fa-brain');
$secondStepTitle = $isRetrievePage ? 'Auto Sync' : ($isAssignPage || $isAvailabilityPage ? 'Assignments' : 'Expertise');
$secondStepText = $isRetrievePage ? 'Create adviser assignment candidates.' : ($isAssignPage ? 'Choose qualified faculty.' : ($isAvailabilityPage ? 'Review saved workload.' : 'Detect required field.'));
$thirdStepIcon = $isRetrievePage ? 'fa-user-tie' : ($isAssignPage ? 'fa-hand-pointer' : ($isAvailabilityPage ? 'fa-calendar-check' : 'fa-envelope'));
$thirdStepTitle = $isRetrievePage ? 'Adviser Ready' : ($isAssignPage ? 'Assign' : ($isAvailabilityPage ? 'Availability' : 'Contact'));
$thirdStepText = $isRetrievePage ? 'Use these records in adviser assignment.' : ($isAssignPage ? 'Save actual coordinator action.' : ($isAvailabilityPage ? 'Check existing assignment load.' : 'Pick matching available faculty.'));
$assigneeLabel = $isPanelPage ? 'Panel Members' : 'Advisers';
$resultsTitle = $isRetrievePage ? 'Retrieved Adviser Records' : ($isAssignPage ? 'Ready to Assign' : ($isAvailabilityPage ? 'Availability Results' : 'Recommended ' . $assigneeLabel));
$recordPanelTitle = $isRetrievePage ? 'Retrieve Approved Research' : $pageTitle;
$recordPanelIcon = $isRetrievePage ? 'fa-download' : ($isFindContactPage ? 'fa-user-tie' : ($isAvailabilityPage ? 'fa-calendar-check' : ($isAssignPage ? 'fa-user-plus' : 'fa-list-check')));

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>

<style>
.rcas-wrap { display: flex; flex-direction: column; gap: 1rem; }
.rcas-header,
.rcas-stat,
.rcas-card {
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 8px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.rcas-header { display: flex; justify-content: space-between; gap: 1rem; padding: 1.1rem 1.25rem; }
.rcas-header h1 { margin: 0; color: var(--sms-heading); font-size: 1.22rem; font-weight: 850; }
.rcas-header p { margin: 0.25rem 0 0; color: var(--sms-text-muted); font-size: 0.86rem; }
.rcas-sync { color: #2563eb; font-size: 0.78rem; font-weight: 850; white-space: nowrap; }
.rcas-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.85rem; }
.rcas-stat { display: flex; align-items: center; gap: 0.75rem; padding: 0.85rem 0.95rem; }
.rcas-stat i { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 8px; color: #1d4ed8; background: rgba(37,99,235,0.12); }
.rcas-stat strong { display: block; color: var(--sms-heading); font-size: 1.25rem; line-height: 1; }
.rcas-stat span { display: block; margin-top: 0.22rem; color: var(--sms-text-muted); font-size: 0.7rem; font-weight: 850; text-transform: uppercase; }
.rcas-card { overflow: hidden; }
.rcas-card-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.9rem 1rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); }
.rcas-card-head h2 { margin: 0; color: var(--sms-heading); font-size: 0.95rem; font-weight: 850; }
.rcas-record-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); }
.rcas-record-title { display: flex; align-items: center; flex-wrap: wrap; gap: 0.45rem; margin: 0; color: var(--sms-text-muted); font-size: 0.82rem; font-weight: 950; letter-spacing: 0; text-transform: uppercase; }
.rcas-record-title i { color: #64748b; }
.rcas-record-total { display: block; margin-top: 0.55rem; color: var(--sms-text-muted); font-size: 0.8rem; font-weight: 850; }
.rcas-search { width: min(330px, 100%); min-height: 36px; border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; padding: 0.45rem 0.7rem; background: var(--sms-surface-muted, #f8fafc); color: var(--sms-heading); font-size: 0.84rem; }
.rcas-toolbar { display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); }
.rcas-toolbar .rcas-search { flex: 1 1 auto; width: auto; min-height: 40px; }
.rcas-filter { width: min(170px, 100%); min-height: 40px; border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; padding: 0.45rem 0.7rem; background: var(--sms-surface-muted, #f8fafc); color: var(--sms-heading); font-size: 0.84rem; }
.rcas-process { display: grid; grid-template-columns: minmax(320px, 0.8fr) minmax(0, 1.25fr); gap: 1rem; padding: 1rem; background: linear-gradient(180deg, rgba(37,99,235,0.035), transparent 42%); }
.rcas-context-panel,
.rcas-results-panel { min-height: 100%; border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: var(--sms-surface-solid, #fff); }
.rcas-context-panel { padding: 0.9rem; }
.rcas-results-panel { display: flex; flex-direction: column; overflow: hidden; }
.rcas-panel-title { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.8rem 0.9rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); }
.rcas-panel-title h3 { margin: 0; color: var(--sms-heading); font-size: 0.86rem; font-weight: 900; }
.rcas-count { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 26px; padding: 0 0.55rem; border-radius: 999px; color: #1d4ed8; background: rgba(37,99,235,0.12); font-size: 0.75rem; font-weight: 950; }
.rcas-count.soft { color: #92400e; background: #fef3c7; }
.rcas-field { display: flex; flex-direction: column; gap: 0.45rem; }
.rcas-field label { color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 850; text-transform: uppercase; }
.rcas-select { width: 100%; min-height: 40px; border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; padding: 0.45rem 0.7rem; background: var(--sms-surface-muted, #f8fafc); color: var(--sms-heading); font-size: 0.86rem; }
.rcas-topic { margin-top: 0.75rem; padding: 0.85rem; border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: var(--sms-surface-muted, #f8fafc); }
.rcas-topic strong { display: block; color: var(--sms-heading); font-size: 0.9rem; line-height: 1.35; }
.rcas-topic span { display: block; margin-top: 0.3rem; color: var(--sms-text-muted); font-size: 0.78rem; }
.rcas-flow { display: grid; grid-template-columns: 1fr; gap: 0.55rem; margin-top: 0.85rem; }
.rcas-flow-step { display: flex; align-items: flex-start; gap: 0.65rem; padding: 0.65rem; border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: #fff; }
.rcas-flow-step i { color: #2563eb; margin-right: 0.35rem; }
.rcas-flow-step strong { display: block; color: var(--sms-heading); font-size: 0.78rem; }
.rcas-flow-step span { display: block; margin-top: 0.2rem; color: var(--sms-text-muted); font-size: 0.7rem; line-height: 1.35; }
.rcas-match-list { display: grid; gap: 0.7rem; padding: 0.8rem; max-height: 340px; min-height: 0; overflow-y: auto; overflow-x: hidden; }
.rcas-match { position: relative; display: grid; grid-template-columns: 1fr auto; gap: 0.9rem; align-items: center; padding: 0.85rem; border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: var(--sms-surface-solid, #fff); box-shadow: 0 1px 0 rgba(15,23,42,0.03); }
.rcas-match:hover { border-color: rgba(37,99,235,0.36); box-shadow: 0 8px 20px rgba(15,23,42,0.07); }
.rcas-score { min-width: 66px; padding: 0.45rem 0.55rem; border-radius: 8px; text-align: center; color: #047857; background: #d1fae5; font-weight: 950; }
.rcas-score.busy { color: #b45309; }
.rcas-load { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.55rem; border-radius: 999px; color: #334155; background: #e2e8f0; font-size: 0.74rem; font-weight: 900; }
.rcas-score small { display: block; color: var(--sms-text-muted); font-size: 0.62rem; text-transform: uppercase; }
.rcas-actions { display: flex; flex-wrap: wrap; gap: 0.45rem; margin-top: 0.5rem; }
.rcas-action { display: inline-flex; align-items: center; gap: 0.35rem; min-height: 32px; padding: 0.35rem 0.6rem; border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; color: #1d4ed8; background: rgba(37,99,235,0.08); font-size: 0.75rem; font-weight: 850; text-decoration: none; cursor: pointer; }
.rcas-action.primary { color: #fff; border-color: #1d4ed8; background: #1d4ed8; }
.rcas-action.success { color: #fff; border-color: #047857; background: #047857; }
.rcas-action[disabled] { opacity: 0.62; cursor: not-allowed; }
.rcas-notice { display: none; margin: 0 1rem 1rem; padding: 0.7rem 0.85rem; border-radius: 8px; font-size: 0.82rem; font-weight: 800; }
.rcas-notice.show { display: block; }
.rcas-notice.ok { color: #047857; background: #d1fae5; border: 1px solid #a7f3d0; }
.rcas-notice.error { color: #991b1b; background: #fee2e2; border: 1px solid #fecaca; }
.rcas-contact-panel { position: fixed; inset: 0; z-index: 1080; display: grid; place-items: center; padding: 1rem; background: rgba(15,23,42,0.55); }
.rcas-contact-panel[hidden] { display: none; }
.rcas-contact-box { width: min(520px, 100%); border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: var(--sms-surface-solid, #fff); box-shadow: 0 22px 55px rgba(15,23,42,0.32); overflow: hidden; }
.rcas-contact-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.95rem 1rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); }
.rcas-contact-head h3 { margin: 0; color: var(--sms-heading); font-size: 1rem; font-weight: 900; }
.rcas-contact-close { width: 34px; height: 34px; border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; background: transparent; color: var(--sms-heading); cursor: pointer; }
.rcas-contact-body { display: grid; gap: 0.65rem; padding: 1rem; }
.rcas-contact-row { display: grid; gap: 0.18rem; padding: 0.7rem; border: 1px solid var(--sms-border, #e2e8f0); border-radius: 8px; background: var(--sms-surface-muted, #f8fafc); }
.rcas-contact-row span { color: var(--sms-text-muted); font-size: 0.7rem; font-weight: 850; text-transform: uppercase; }
.rcas-contact-row strong,
.rcas-contact-row p { margin: 0; color: var(--sms-heading); font-size: 0.88rem; line-height: 1.45; }
.rcas-contact-row p { white-space: pre-line; }
.rcas-contact-footer { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.5rem; padding: 0.9rem 1rem; border-top: 1px solid var(--sms-border, #e2e8f0); }
.rcas-table-wrap { overflow-x: auto; }
.rcas-table { width: 100%; min-width: 1000px; border-collapse: collapse; }
.rcas-table th,
.rcas-table td { padding: 0.82rem 0.9rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); text-align: left; vertical-align: top; }
.rcas-table th { color: var(--sms-text-muted); background: var(--sms-surface-muted, #f8fafc); font-size: 0.72rem; font-weight: 850; text-transform: uppercase; }
.rcas-title { color: var(--sms-heading); font-weight: 850; line-height: 1.35; }
.rcas-match .rcas-title { font-size: 0.95rem; }
.rcas-muted { color: var(--sms-text-muted); font-size: 0.76rem; font-weight: 650; }
.rcas-code,
.rcas-badge { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.55rem; border-radius: 999px; font-size: 0.74rem; font-weight: 900; }
.rcas-code { color: #1d4ed8; background: rgba(37,99,235,0.12); }
.rcas-badge { color: #92400e; background: #fef3c7; }
.rcas-badge.assigned,
.rcas-badge.available { color: #047857; background: #d1fae5; }
.rcas-badge.unavailable { color: #991b1b; background: #fee2e2; }
.rcas-empty { padding: 2rem 1rem; text-align: center; color: var(--sms-text-muted); font-weight: 750; }
.rcas-error { padding: 0.8rem 1rem; border: 1px solid #fecaca; border-radius: 8px; background: #fef2f2; color: #991b1b; font-weight: 750; }
.rcas-wrap--retrieve .rcas-stats,
.rcas-wrap--contact .rcas-stats,
.rcas-wrap--retrieve .rcas-process,
.rcas-wrap--retrieve .rcas-assignment-card { display: none !important; }
.rcas-retrieve-table { width: 100%; border-collapse: collapse; }
.rcas-retrieve-table th,
.rcas-retrieve-table td { padding: 0.95rem 1rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); text-align: left; vertical-align: top; }
.rcas-retrieve-table th { color: var(--sms-text-muted); background: var(--sms-surface-muted, #f8fafc); font-size: 0.72rem; font-weight: 850; text-transform: uppercase; }
.rcas-retrieve-main { display: block; color: var(--sms-heading); font-size: 0.95rem; font-weight: 900; line-height: 1.3; }
.rcas-retrieve-sub { display: block; margin-top: 0.25rem; color: var(--sms-text-muted); font-size: 0.76rem; font-weight: 750; }
.rcas-retrieve-adviser { color: #334155; font-size: 0.94rem; }
.rcas-retrieve-date { display: block; margin-top: 0.22rem; color: var(--sms-text-muted); font-size: 0.76rem; font-weight: 850; }
.rcas-retrieve-approved { display: inline-flex; align-items: center; gap: 0.35rem; min-height: 28px; margin-top: 0.45rem; padding: 0.32rem 0.7rem; border-radius: 999px; color: #047857; background: #d1fae5; font-size: 0.75rem; font-weight: 900; }
.rcas-retrieve-approved i { font-size: 0.7rem; }
.rcas-retrieve-approved.awaiting { color: #92400e; background: #fef3c7; }
.rcas-retrieve-approved.rejected { color: #991b1b; background: #fee2e2; }
[data-theme="dark"] .rcas-header,
[data-theme="dark"] .rcas-stat,
[data-theme="dark"] .rcas-card { background: rgba(15,23,42,0.74); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-card-head,
[data-theme="dark"] .rcas-record-head,
[data-theme="dark"] .rcas-toolbar,
[data-theme="dark"] .rcas-table th,
[data-theme="dark"] .rcas-table td { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-table th,
[data-theme="dark"] .rcas-search,
[data-theme="dark"] .rcas-filter,
[data-theme="dark"] .rcas-select,
[data-theme="dark"] .rcas-topic { background: rgba(148,163,184,0.07); }
[data-theme="dark"] .rcas-context-panel,
[data-theme="dark"] .rcas-results-panel { background: rgba(15,23,42,0.58); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-panel-title { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-flow-step,
[data-theme="dark"] .rcas-match,
[data-theme="dark"] .rcas-retrieve-item,
[data-theme="dark"] .rcas-contact-box { background: rgba(15,23,42,0.96); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-contact-head,
[data-theme="dark"] .rcas-contact-footer,
[data-theme="dark"] .rcas-contact-row { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcas-contact-row { background: rgba(148,163,184,0.07); }
/* Adviser assignment confirmation modal (same BCP design as sms-confirm-modal) */
.rcas-confirm-detail { display: grid; grid-template-columns: max-content 1fr; gap: 0.35rem 0.75rem; margin-top: 0.6rem; }
.rcas-confirm-label { color: var(--sms-text-muted, #64748b); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.03em; }
.rcas-confirm-value { color: var(--sms-heading, #0f172a); font-weight: 600; line-height: 1.45; word-break: break-word; }
.rcas-confirm-divider { height: 1px; background: var(--sms-border, #e2e8f0); margin: 0.75rem 0; }

@media (max-width: 767.98px) {
    .rcas-header,
    .rcas-card-head,
    .rcas-record-head,
    .rcas-toolbar { flex-direction: column; align-items: flex-start; }
    .rcas-stats { grid-template-columns: 1fr; }
    .rcas-process,
    .rcas-flow { grid-template-columns: 1fr; }
    .rcas-match { grid-template-columns: 1fr; }
    .rcas-retrieve-table { min-width: 760px; }
    .rcas-sync,
    .rcas-filter,
    .rcas-search { width: 100%; }
}
</style>

<div class="rcas-wrap <?= $isRetrievePage ? 'rcas-wrap--retrieve' : ($isFindContactPage ? 'rcas-wrap--contact' : '') ?>" data-rcas-endpoint="<?= rcAssignE($endpoint) ?>" data-rcas-mode="<?= $isRetrievePage ? 'retrieve' : ($isAssignPage ? 'assign' : ($isAvailabilityPage ? 'availability' : 'contact')) ?>">
    <?php if (!$payload['ok']): ?>
        <div class="rcas-error"><?= smsIcon('exclamation-circle', ['class' => 'me-1']) ?><?= rcAssignE((string) $payload['error']) ?></div>
    <?php endif; ?>

    <header class="rcas-header">
        <div>
            <h1><?= smsIcon('user-check', ['class' => 'me-2']) ?><?= rcAssignE($pageTitle) ?></h1>
            <p><?= rcAssignE($pageConfig['lead']) ?> records for approved research groups.</p>
        </div>
        <div class="rcas-sync" id="rcasLastSync">Synced <?= rcAssignE((string) $payload['last_sync']) ?></div>
    </header>

    <div class="rcas-stats">
        <div class="rcas-stat"><?= smsIcon('list') ?><div><strong id="rcasTotal"><?= (int) $stats['total'] ?></strong><span>Total</span></div></div>
        <div class="rcas-stat"><?= smsIcon('clock') ?><div><strong id="rcasPending"><?= (int) $stats['pending'] ?></strong><span>Pending</span></div></div>
        <div class="rcas-stat"><?= smsIcon('calendar-check') ?><div><strong id="rcasAvailable"><?= (int) $stats['available'] ?></strong><span>Available</span></div></div>
        <div class="rcas-stat"><?= smsIcon('check-circle') ?><div><strong id="rcasAssigned"><?= (int) $stats['assigned'] ?></strong><span>Assigned</span></div></div>
    </div>

    <?php if ($isRetrievePage): ?>
        <section class="rcas-card rcas-retrieve-card">
            <div class="rcas-record-head">
                <div>
                    <h2 class="rcas-record-title"><?= smsIcon(rcAssignE($recordPanelIcon)) ?><?= rcAssignE($recordPanelTitle) ?> <span class="rcas-count soft" id="rcasRecordBadge"><?= (int) $stats['total'] ?></span></h2>
                    <span class="rcas-record-total" id="rcasRecordCount"><?= (int) $stats['total'] ?> record<?= (int) $stats['total'] === 1 ? '' : 's' ?></span>
                </div>
                <div class="rcas-sync" id="rcasRecordSync">Synced <?= rcAssignE((string) $payload['last_sync']) ?></div>
            </div>
            <div class="rcas-toolbar">
                <input id="rcasSearch" class="rcas-search" type="search" placeholder="Search by title, student, or adviser...">
                <select id="rcasStatusFilter" class="rcas-filter">
                    <option value="">All Status</option>
                    <option value="approved">Approved</option>
                </select>
            </div>
            <div class="rcas-table-wrap">
                <table class="rcas-retrieve-table">
                    <thead>
                        <tr>
                            <th>Research Title</th>
                            <th>Student</th>
                            <th>Adviser Approved</th>
                            <th>Coordinator</th>
                            <th>CRAD Officer</th>
                        </tr>
                    </thead>
                    <tbody id="rcasRetrieveList"></tbody>
                </table>
            </div>
            <div class="rcas-empty" id="rcasRetrieveEmpty" hidden>No approved research retrieved yet.</div>
        </section>
    <?php endif; ?>

    <?php if (!$isRetrievePage): ?>
    <section class="rcas-card rcas-assignment-card">
        <?php if ($isFindContactPage): ?>
        <div class="rcas-record-head">
            <div>
                <h2 class="rcas-record-title"><?= smsIcon(rcAssignE($recordPanelIcon)) ?><?= rcAssignE($recordPanelTitle) ?> <span class="rcas-count soft" id="rcasRecordBadge"><?= (int) $stats['total'] ?></span></h2>
                <span class="rcas-record-total" id="rcasRecordCount"><?= (int) $stats['total'] ?> record<?= (int) $stats['total'] === 1 ? '' : 's' ?></span>
            </div>
            <div class="rcas-sync" id="rcasRecordSync">Synced <?= rcAssignE((string) $payload['last_sync']) ?></div>
        </div>
        <div class="rcas-toolbar">
            <input id="rcasSearch" class="rcas-search" type="search" placeholder="Search by title, student, or adviser...">
            <select id="rcasStatusFilter" class="rcas-filter">
                <option value="">All Status</option>
                <option value="available">Available</option>
                <option value="pending">Pending</option>
            </select>
        </div>
        <?php else: ?>
        <div class="rcas-card-head">
            <h2><?= rcAssignE($processTitle) ?></h2>
        </div>
        <?php endif; ?>
        <div class="rcas-process">
            <div class="rcas-context-panel">
                <div class="rcas-panel-title">
                    <h3><?= smsIcon('flask', ['class' => 'me-2']) ?>Selected Research</h3>
                </div>
                <div class="rcas-field">
                    <label for="rcasGroupSelect">Approved Research Group</label>
                    <select id="rcasGroupSelect" class="rcas-select"></select>
                </div>
                <div class="rcas-topic">
                    <strong id="rcasTopic">No approved group selected</strong>
                    <span id="rcasRequired">Required expertise will appear here.</span>
                </div>
                <div class="rcas-flow">
                    <div class="rcas-flow-step"><strong><?= smsIcon('flask') ?>Topic</strong><span>Read approved title.</span></div>
                    <div class="rcas-flow-step"><strong><?= smsIcon(rcAssignE($secondStepIcon)) ?><?= rcAssignE($secondStepTitle) ?></strong><span><?= rcAssignE($secondStepText) ?></span></div>
                    <div class="rcas-flow-step"><strong><?= smsIcon(rcAssignE($thirdStepIcon)) ?><?= rcAssignE($thirdStepTitle) ?></strong><span><?= rcAssignE($thirdStepText) ?></span></div>
                </div>
            </div>
            <div class="rcas-results-panel">
                <div class="rcas-panel-title">
                    <h3><?= smsIcon('user-check', ['class' => 'me-2']) ?><?= rcAssignE($resultsTitle) ?></h3>
                    <span class="rcas-count" id="rcasMatchCount">0</span>
                </div>
                <div class="rcas-notice" id="rcasAssignNotice"></div>
                <div class="rcas-match-list" id="rcasMatchList"></div>
                <div class="rcas-empty" id="rcasMatchEmpty" hidden><?= rcAssignE($processEmpty) ?></div>
            </div>
        </div>
    </section>

    <?php if (!$isFindContactPage && !$isAvailabilityPage && !$isAssignPage): ?>
    <section class="rcas-card rcas-assignment-card">
        <div class="rcas-record-head">
            <div>
                <h2 class="rcas-record-title"><?= smsIcon(rcAssignE($recordPanelIcon)) ?><?= rcAssignE($recordPanelTitle) ?> <span class="rcas-count soft" id="rcasRecordBadge"><?= (int) $stats['total'] ?></span></h2>
                <span class="rcas-record-total" id="rcasRecordCount"><?= (int) $stats['total'] ?> record<?= (int) $stats['total'] === 1 ? '' : 's' ?></span>
            </div>
            <div class="rcas-sync" id="rcasRecordSync">Synced <?= rcAssignE((string) $payload['last_sync']) ?></div>
        </div>
        <div class="rcas-toolbar">
            <input id="rcasSearch" class="rcas-search" type="search" placeholder="Search group, title, assignee, expertise...">
            <select id="rcasStatusFilter" class="rcas-filter">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="available">Available</option>
                <option value="assigned">Assigned</option>
            </select>
        </div>
        <div class="rcas-table-wrap">
            <table class="rcas-table">
                <thead>
                    <tr>
                        <th>Research Group / Title</th>
                        <th>Type</th>
                        <th>Assignee</th>
                        <th>Expertise</th>
                        <th>Availability</th>
                        <th>Assignment</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody id="rcasRows"></tbody>
            </table>
        </div>
        <div class="rcas-empty" id="rcasEmpty" hidden><?= rcAssignE($emptyText) ?></div>
    </section>
    <?php endif; ?>
    <?php endif; ?>
</div>

<div class="rcas-contact-panel" id="rcasContactPanel" hidden>
    <div class="rcas-contact-box" role="dialog" aria-modal="true" aria-labelledby="rcasContactTitle">
        <div class="rcas-contact-head">
            <h3 id="rcasContactTitle"><?= smsIcon('address-card', ['class' => 'me-2']) ?>Contact Details</h3>
            <button type="button" class="rcas-contact-close" id="rcasContactClose" aria-label="Close contact details">
                <?= smsIcon('times') ?>
            </button>
        </div>
        <div class="rcas-contact-body">
            <div class="rcas-contact-row"><span>Name</span><strong id="rcasContactName"></strong></div>
            <div class="rcas-contact-row"><span>Email</span><strong id="rcasContactEmail"></strong></div>
            <div class="rcas-contact-row"><span>Role</span><strong id="rcasContactRole"></strong></div>
            <div class="rcas-contact-row"><span>Expertise</span><p id="rcasContactExpertise"></p></div>
            <div class="rcas-contact-row"><span>Research Title</span><p id="rcasContactResearch"></p></div>
            <div class="rcas-contact-row"><span>Message Draft</span><p id="rcasContactMessage"></p></div>
        </div>
        <div class="rcas-contact-footer">
            <button type="button" class="rcas-action" id="rcasCopyEmail"><?= smsIcon('copy') ?>Copy Email</button>
            <button type="button" class="rcas-action primary" id="rcasCopyMessage"><?= smsIcon('clipboard') ?>Copy Message</button>
        </div>
    </div>
</div>

<script>
(() => {
    const root = document.querySelector('[data-rcas-endpoint]');
    if (!root) return;

    const endpoint = root.dataset.rcasEndpoint;
    const mode = root.dataset.rcasMode || 'contact';
    const isRetrieveMode = mode === 'retrieve';
    const rowsBody = document.getElementById('rcasRows');
    const empty = document.getElementById('rcasEmpty');
    const search = document.getElementById('rcasSearch');
    const statusFilter = document.getElementById('rcasStatusFilter');
    const lastSync = document.getElementById('rcasLastSync');
    const recordSync = document.getElementById('rcasRecordSync');
    const recordBadge = document.getElementById('rcasRecordBadge');
    const recordCount = document.getElementById('rcasRecordCount');
    const total = document.getElementById('rcasTotal');
    const pending = document.getElementById('rcasPending');
    const available = document.getElementById('rcasAvailable');
    const assigned = document.getElementById('rcasAssigned');
    const groupSelect = document.getElementById('rcasGroupSelect');
    const topic = document.getElementById('rcasTopic');
    const required = document.getElementById('rcasRequired');
    const matchList = document.getElementById('rcasMatchList');
    const matchEmpty = document.getElementById('rcasMatchEmpty');
    const matchCount = document.getElementById('rcasMatchCount');
    const assignNotice = document.getElementById('rcasAssignNotice');
    const contactPanel = document.getElementById('rcasContactPanel');
    const contactClose = document.getElementById('rcasContactClose');
    const contactName = document.getElementById('rcasContactName');
    const contactEmail = document.getElementById('rcasContactEmail');
    const contactRole = document.getElementById('rcasContactRole');
    const contactExpertise = document.getElementById('rcasContactExpertise');
    const contactResearch = document.getElementById('rcasContactResearch');
    const contactMessage = document.getElementById('rcasContactMessage');
    const copyEmail = document.getElementById('rcasCopyEmail');
    const copyMessage = document.getElementById('rcasCopyMessage');
    const retrieveList = document.getElementById('rcasRetrieveList');
    const retrieveEmpty = document.getElementById('rcasRetrieveEmpty');
    let rows = <?= json_encode($rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    let groups = <?= json_encode($groups, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    let selectedGroup = '';
    let activeContact = null;
    let refreshing = false;
    let refreshTimer = null;
    const refreshDelay = 1000;

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[char]);
    const attr = (value) => esc(value).replace(/`/g, '&#096;');
    const badgeClass = (value) => String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, '-');
    const buildMessage = (row, group) => [
        `Good day ${row.assignee_name || 'Research Adviser'},`,
        '',
        'You are being contacted as a possible research adviser for the approved research below.',
        '',
        `Research Title: ${group?.research_title || 'Untitled research'}`,
        `Required Expertise: ${group?.required_expertise || 'General Research Methods'}`,
        `Research Group: ${group?.group_name || group?.group_number || 'Research Group'}`,
        `Reference: ${group?.group_number || ''}${group?.proposal_number ? ' / ' + group.proposal_number : ''}`,
        '',
        'Please review the details and confirm if you are available to advise this research.',
        '',
        'Thank you.'
    ].join('\n');
    const copyText = async (value) => {
        if (!value) return;
        try {
            await navigator.clipboard.writeText(value);
        } catch (error) {
            const temp = document.createElement('textarea');
            temp.value = value;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            temp.remove();
        }
    };
    const showNotice = (message, type = 'ok') => {
        if (!assignNotice) return;
        assignNotice.textContent = message || '';
        assignNotice.className = `rcas-notice show ${type}`;
        window.setTimeout(() => {
            assignNotice.className = 'rcas-notice';
            assignNotice.textContent = '';
        }, 3500);
    };
    const openContact = (row, group) => {
        activeContact = { row, group, message: buildMessage(row, group) };
        contactName.textContent = row.assignee_name || 'For contact';
        contactEmail.textContent = row.assignee_email || 'No email saved';
        contactRole.textContent = row.assignee_role || String(row.assignment_kind || '').toUpperCase() || 'Faculty';
        contactExpertise.textContent = row.expertise || 'No expertise saved';
        contactResearch.textContent = group?.research_title || row.research_title || 'Untitled research';
        contactMessage.textContent = activeContact.message;
        contactPanel.hidden = false;
    };
    const closeContact = () => {
        contactPanel.hidden = true;
        activeContact = null;
    };
    const formatDate = (value) => {
        if (!value) return 'Not updated';
        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return value;
        return parsed.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    };
    const matches = (row, term) => !term || [
        row.assignment_kind, row.group_number, row.group_name, row.research_title,
        row.assignee_name, row.assignee_email, row.assignee_role, row.expertise,
        row.availability_status, row.assignment_status
    ].join(' ').toLowerCase().includes(term);
    const statusMatches = (row, value) => {
        if (!value) return true;
        const haystack = [
            row.availability_status,
            row.assignment_status,
            row.adviser_approval_status,
            row.coordinator_approval_status,
            row.crad_approval_status
        ].join(' ').toLowerCase();
        return haystack.includes(value);
    };
    const updateRecordMeta = (count, syncText = '') => {
        const safeCount = Number(count || 0);
        if (recordBadge) recordBadge.textContent = String(safeCount);
        if (recordCount) recordCount.textContent = `${safeCount} record${safeCount === 1 ? '' : 's'}`;
        if (recordSync && syncText) recordSync.textContent = syncText;
    };

    const scoreForGroup = (row, group) => {
        const requiredText = `${group?.required_expertise || ''} ${group?.research_title || ''}`.toLowerCase();
        const haystack = `${row.expertise || ''} ${row.notes || ''} ${row.research_title || ''}`.toLowerCase();
        const words = [...new Set(requiredText.split(/[^a-z0-9]+/).filter((word) => word.length > 2))];
        if (!words.length) return Number(row.match_score || 40);

        const hits = words.filter((word) => haystack.includes(word)).length;
        let score = Math.round((hits / words.length) * 85);
        if (String(row.availability_status || '').toLowerCase() === 'available') score += 10;
        return Math.max(15, Math.min(100, score));
    };
    const expertiseFitScore = (row, group) => {
        const required = String(group?.required_expertise || '').toLowerCase();
        const expertise = String(row.expertise || '').toLowerCase();
        if (!required || !expertise) return 0;

        const requiredParts = required
            .split('/')
            .map((part) => part.trim())
            .filter(Boolean);
        if (!requiredParts.length) return 0;

        const hits = requiredParts.filter((part) => {
            const words = part.split(/[^a-z0-9]+/).filter((word) => word.length > 3);
            if (!words.length) return false;
            return part.length > 4 && expertise.includes(part)
                || words.some((word) => expertise.includes(word));
        }).length;

        return Math.round((hits / requiredParts.length) * 100);
    };

    const selectedGroupMeta = () => groups.find((item) => {
        const value = item.group_number || item.proposal_number || String(item.research_group_id || '');
        return value === selectedGroup;
    }) || null;
    const studentGroupKeys = (group) => {
        const keys = [];
        const gn = String(group?.group_number || '');
        const pn = String(group?.proposal_number || '');
        const leader = String(group?.leader_id || group?.student_id || '').trim();
        if (gn) keys.push(gn);
        if (pn) keys.push(pn);
        if (leader) {
            keys.push(leader);
            keys.push('STU-' + leader.toUpperCase().replace(/[^A-Z0-9_-]/g, ''));
        }
        return keys;
    };
    const selectedGroupRows = () => {
        const group = selectedGroupMeta();
        const keys = new Set(studentGroupKeys(group).map((v) => v.toLowerCase()));
        if (!selectedGroup) return [];
        keys.add(String(selectedGroup).toLowerCase());
        return rows.filter((row) => {
            const values = [
                String(row.group_number || ''),
                String(row.proposal_number || ''),
                String(row.leader_id || ''),
                String(row.student_id || '')
            ].map((v) => v.toLowerCase());
            return values.some((v) => v !== '' && keys.has(v));
        });
    };
    const isRowAssigned = (row, group) => {
        const statusAssigned = ['assigned', 'confirmed'].includes(String(row.assignment_status || '').toLowerCase());
        if (!statusAssigned) return false;
        if (!selectedGroup) return true;
        const keys = new Set(studentGroupKeys(group).map((v) => v.toLowerCase()));
        keys.add(String(selectedGroup).toLowerCase());
        const values = [
            String(row.group_number || ''),
            String(row.proposal_number || ''),
            String(row.leader_id || ''),
            String(row.student_id || '')
        ].map((v) => v.toLowerCase());
        return values.some((v) => v !== '' && keys.has(v));
    };
    const assigneeLoadCount = (row) => rows.filter((item) => (
        String(item.assignment_kind || '').toLowerCase() === String(row.assignment_kind || '').toLowerCase() &&
        String(item.assignee_name || '').toLowerCase() === String(row.assignee_name || '').toLowerCase() &&
        String(item.assignment_status || '').toLowerCase() !== 'cancelled'
    )).length;
    const retrieveGroupName = (group) => {
        const approvalId = Number(group?.title_approval_id || 0);
        if (approvalId > 0) return `Group ${String(approvalId).padStart(2, '0')}`;
        return group?.group_name || group?.group_number || 'Research Group';
    };
    const retrieveApprovalBadge = (status, signed) => {
        const normalized = String(status || '').toLowerCase();
        if (normalized === 'rejected' || normalized === 'returned') {
            return `<span class="rcas-retrieve-approved rejected"><?= smsIcon('x') ?>${esc(status || 'Rejected')}</span>`;
        }
        if (Number(signed) === 1 || signed === true || String(signed) === '1') {
            return `<span class="rcas-retrieve-approved"><?= smsIcon('check') ?>Signed</span>`;
        }
        return `<span class="rcas-retrieve-approved awaiting"><?= smsIcon('clock') ?>Awaiting signature</span>`;
    };
    const renderRetrieveList = () => {
        if (!retrieveList) return;
        const term = (search?.value || '').trim().toLowerCase();
        const statusValue = (statusFilter?.value || '').trim().toLowerCase();
        const visibleGroups = groups.filter((group) => {
            const textMatch = !term || [
                group.research_title,
                group.college_dept,
                group.leader_name,
                group.leader_id,
                group.approved_adviser_name,
                group.coordinator_approved_name,
                group.crad_approval_status
            ].join(' ').toLowerCase().includes(term);
            const statusMatch = !statusValue || [
                group.adviser_approval_status,
                group.coordinator_approval_status,
                group.crad_approval_status
            ].join(' ').toLowerCase().includes(statusValue);
            return textMatch && statusMatch;
        });
        retrieveList.innerHTML = visibleGroups.map((group) => `
            <tr>
                <td>
                    <span class="rcas-retrieve-main">${esc(group.research_title || 'Untitled research')}</span>
                    <span class="rcas-retrieve-sub">${esc(group.college_dept || '')}</span>
                </td>
                <td>
                    <span class="rcas-retrieve-main">${esc(group.leader_name || 'Student')}</span>
                    <span class="rcas-retrieve-sub">${esc(group.leader_id || '')}</span>
                </td>
                <td>
                    <span class="rcas-retrieve-adviser">${esc(group.approved_adviser_name || 'For adviser')}</span>
                    <span class="rcas-retrieve-date">${esc(formatDate(group.adviser_approved_at || group.updated_at || ''))}</span>
                    ${retrieveApprovalBadge(group.adviser_approval_status, group.adviser_signed)}
                </td>
                <td>
                    <span class="rcas-retrieve-main">${esc(group.coordinator_approved_name || 'Coordinator')}</span>
                    <span class="rcas-retrieve-date">${esc(formatDate(group.coordinator_approved_at || ''))}</span>
                    ${retrieveApprovalBadge(group.coordinator_approval_status, group.coordinator_signed)}
                </td>
                <td>
                    <span class="rcas-retrieve-main">CRAD Officer</span>
                    <span class="rcas-retrieve-date">${esc(formatDate(group.crad_approved_at || ''))}</span>
                    ${retrieveApprovalBadge(group.crad_approval_status, group.crad_signed)}
                </td>
            </tr>
        `).join('');
        updateRecordMeta(visibleGroups.length);
        if (retrieveEmpty) {
            retrieveEmpty.hidden = visibleGroups.length !== 0;
            retrieveEmpty.textContent = groups.length === 0 ? 'No approved research retrieved yet.' : 'No records match your filters.';
        }
    };

    const renderGroups = () => {
        if (!groupSelect) return;
        const previous = selectedGroup || groupSelect.value;
        groupSelect.innerHTML = groups.map((group) => {
            const value = group.group_number || group.proposal_number || String(group.research_group_id || '');
            const label = `${group.group_number || group.proposal_number || 'Research Group'} - ${group.research_title || 'Untitled research'}`;
            return `<option value="${attr(value)}">${esc(label)}</option>`;
        }).join('');

        const values = Array.from(groupSelect.options).map((option) => option.value);
        selectedGroup = values.includes(previous) ? previous : (values[0] || '');
        groupSelect.value = selectedGroup;
    };

    const renderProcess = () => {
        if (!groupSelect || !topic || !required || !matchList || !matchEmpty) {
            return;
        }

        renderGroups();

        const group = selectedGroupMeta();
        const directMatches = selectedGroupRows();
        const processTerm = (search?.value || '').trim().toLowerCase();
        const processStatus = (statusFilter?.value || '').trim().toLowerCase();
        const sourceRows = (mode === 'contact' ? rows : (directMatches.length ? directMatches : rows))
            .filter((row) => matches(row, processTerm) && statusMatches(row, processStatus));
        const usingSelectedGroupRows = mode !== 'contact' && directMatches.length > 0;
        const seenAssignees = {};
        sourceRows
            .map((row) => ({
                ...row,
                expertise_fit_score: expertiseFitScore(row, group),
                match_score: scoreForGroup(row, group),
                selected_group_match: usingSelectedGroupRows
            }))
            .sort((a, b) => {
                const aAssigned = isRowAssigned(a, group) ? 1 : 0;
                const bAssigned = isRowAssigned(b, group) ? 1 : 0;
                if (bAssigned !== aAssigned) return bAssigned - aAssigned;
                return Number(b.match_score || 0) - Number(a.match_score || 0);
            })
            .forEach((row) => {
                const key = `${row.assignment_kind || ''}|${row.assignee_name || ''}|${row.assignee_email || ''}`.toLowerCase();
                const current = seenAssignees[key];
                if (!current || (isRowAssigned(row, group) && !isRowAssigned(current, group))) {
                    seenAssignees[key] = row;
                }
            });
        const matchesForGroup = Object.values(seenAssignees);

        if (!group) {
            topic.textContent = 'Adviser accounts from User Management';
            required.textContent = 'Users with the Adviser role appear here in real time.';
        } else {
            topic.textContent = group.research_title || 'Untitled research';
            required.textContent = mode === 'availability'
                ? `Availability check: ${group.group_number || group.proposal_number || 'Approved group'}`
                : `Required Expertise: ${group.required_expertise || 'General Research Methods'}`;
        }

        matchList.innerHTML = matchesForGroup.map((row) => {
            const email = String(row.assignee_email || '').trim();
            const sourceNote = directMatches.length ? '' : '<div class="rcas-muted">Recommended from assignment database</div>';
            const encoded = attr(JSON.stringify(row));
            const loadCount = assigneeLoadCount(row);
            if (mode === 'assign') {
                const isAssigned = isRowAssigned(row, group);
                const isAvailable = String(row.availability_status || '').toLowerCase() === 'available';
                const disabled = isAssigned || !isAvailable;
                const actionLabel = isAssigned ? 'Assigned' : (isAvailable ? 'Assign' : 'Not Available');
                return `
                    <article class="rcas-match">
                        <div>
                            <div class="rcas-title">${esc(row.assignee_name || 'Faculty')}</div>
                            <div class="rcas-muted">${esc(row.assignee_role || row.assignee_email || 'No email saved')}</div>
                            <div>${esc(row.expertise || 'No expertise saved')}</div>
                            ${sourceNote}
                            <div class="rcas-actions">
                                <span class="rcas-badge ${badgeClass(row.availability_status)}">${esc(row.availability_status || 'Pending')}</span>
                                <span class="rcas-load"><?= smsIcon('briefcase') ?>${esc(loadCount)} current record${loadCount === 1 ? '' : 's'}</span>
                                <button type="button" class="rcas-action success" data-assign-id="${esc(row.assignment_id || '')}" data-assign-row="${encoded}" ${disabled ? 'disabled' : ''}>
                                    <?= smsIcon('hand-pointer') ?>${esc(actionLabel)}
                                </button>
                            </div>
                        </div>
                        <div class="rcas-score">${esc(row.match_score || 0)}%<small>Fit</small></div>
                    </article>
                `;
            }
            if (mode === 'availability') {
                const availabilityText = row.availability_status || 'Pending';
                const isAvailable = String(availabilityText).toLowerCase() === 'available';
                return `
                    <article class="rcas-match">
                        <div>
                            <div class="rcas-title">${esc(row.assignee_name || 'Faculty')}</div>
                            <div class="rcas-muted">${esc(row.assignee_role || row.assignee_email || 'No email saved')}</div>
                            <div>${esc(row.expertise || 'No expertise saved')}</div>
                            ${sourceNote}
                            <div class="rcas-actions">
                                <span class="rcas-badge ${badgeClass(row.availability_status)}">${esc(row.availability_status || 'Pending')}</span>
                                <span class="rcas-load"><?= smsIcon('briefcase') ?>${esc(loadCount)} current record${loadCount === 1 ? '' : 's'}</span>
                                <span class="rcas-badge ${badgeClass(row.assignment_status)}">${esc(row.assignment_status || 'Pending')}</span>
                            </div>
                        </div>
                        <div class="rcas-score ${isAvailable ? '' : 'busy'}">${esc(availabilityText)}<small>Status</small></div>
                    </article>
                `;
            }
            return `
                <article class="rcas-match">
                    <div>
                        <div class="rcas-title">${esc(row.assignee_name || 'For contact')}</div>
                        <div class="rcas-muted">${esc(row.assignee_role || row.assignee_email || 'No email saved')}</div>
                        <div>${esc(row.expertise || 'No expertise saved')}</div>
                        ${sourceNote}
                        <div class="rcas-actions">
                            <span class="rcas-badge ${badgeClass(row.availability_status)}">${esc(row.availability_status || 'Pending')}</span>
                            <button type="button" class="rcas-action primary" data-contact-row="${encoded}"><?= smsIcon('address-card') ?>View Contact</button>
                            ${email ? `<button type="button" class="rcas-action" data-copy-email="${attr(email)}"><?= smsIcon('copy') ?>Copy Email</button>` : ''}
                        </div>
                    </div>
                    <div class="rcas-score">${esc(row.match_score || 0)}%<small>Match</small></div>
                </article>
            `;
        }).join('');
        if (matchCount) matchCount.textContent = String(matchesForGroup.length);
        matchEmpty.hidden = matchesForGroup.length !== 0;
    };

    const render = () => {
        if (isRetrieveMode) {
            renderRetrieveList();
            return;
        }
        const term = (search?.value || '').trim().toLowerCase();
        const statusValue = (statusFilter?.value || '').trim().toLowerCase();
        const visibleRows = rows.filter((row) => matches(row, term) && statusMatches(row, statusValue));
        if (rowsBody) {
            rowsBody.innerHTML = visibleRows.map((row) => `
                <tr>
                    <td>
                        <div class="rcas-title">${esc(row.group_name || row.group_number || 'Research Group')}</div>
                        <div>${esc(row.research_title || '')}</div>
                        <div class="rcas-muted">${esc(row.group_number || '')} ${row.proposal_number ? '&middot; ' + esc(row.proposal_number) : ''}</div>
                    </td>
                    <td><span class="rcas-code">${esc(String(row.assignment_kind || '').toUpperCase())}</span></td>
                    <td>
                        <div class="rcas-title">${esc(row.assignee_name || 'For contact')}</div>
                        <div class="rcas-muted">${esc(row.assignee_role || row.assignee_email || '')}</div>
                    </td>
                    <td>
                        <div>${esc(row.expertise || 'Not set')}</div>
                        <div class="rcas-muted">Required: ${esc(row.required_expertise || 'General Research Methods')}</div>
                    </td>
                    <td><span class="rcas-badge ${badgeClass(row.availability_status)}">${esc(row.availability_status || 'Pending')}</span></td>
                    <td><span class="rcas-badge ${badgeClass(row.assignment_status)}">${esc(row.assignment_status || 'Pending')}</span></td>
                    <td>${esc(formatDate(row.updated_at || row.assigned_at))}</td>
                </tr>
            `).join('');
        }
        updateRecordMeta(visibleRows.length);
        if (empty) empty.hidden = visibleRows.length !== 0;
        renderProcess();
    };

    const refresh = async () => {
        if (refreshing) return;
        refreshing = true;
        try {
            const url = new URL(endpoint, window.location.href);
            url.searchParams.set('_', Date.now().toString());
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }, cache: 'no-store', credentials: 'same-origin' });
            if (!res.ok) throw new Error('Failed to sync.');
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to sync.');
            rows = Array.isArray(data.rows) ? data.rows : [];
            groups = Array.isArray(data.groups) ? data.groups : [];
            if (total) total.textContent = data.stats?.total ?? rows.length;
            if (pending) pending.textContent = data.stats?.pending ?? 0;
            if (available) available.textContent = data.stats?.available ?? 0;
            if (assigned) assigned.textContent = data.stats?.assigned ?? 0;
            const syncText = `Synced ${data.last_sync || 'just now'}`;
            if (lastSync) lastSync.textContent = syncText;
            updateRecordMeta(isRetrieveMode ? groups.length : rows.length, syncText);
            render();
        } catch (error) {
            if (lastSync) lastSync.textContent = 'Sync paused';
        } finally {
            refreshing = false;
        }
    };

    search?.addEventListener('input', render);
    statusFilter?.addEventListener('change', render);
    groupSelect?.addEventListener('change', () => {
        selectedGroup = groupSelect.value;
        renderProcess();
    });
    matchList?.addEventListener('click', (event) => {
        const contactButton = event.target.closest('[data-contact-row]');
        const emailButton = event.target.closest('[data-copy-email]');
        if (contactButton) {
            try {
                openContact(JSON.parse(contactButton.dataset.contactRow || '{}'), groups.find((item) => {
                    const value = item.group_number || item.proposal_number || String(item.research_group_id || '');
                    return value === selectedGroup;
                }));
            } catch (error) {
                return;
            }
        }
        if (emailButton) {
            copyText(emailButton.dataset.copyEmail || '');
        }
    });
    let pendingAssign = null;

    const ensureAssignConfirmModal = function () {
        if (document.getElementById('rcasAssignConfirmModal')) return;
        const wrap = document.createElement('div');
        wrap.innerHTML =
            '<div class="modal fade" id="rcasAssignConfirmModal" tabindex="-1" aria-hidden="true">' +
            '<div class="modal-dialog modal-dialog-centered rcas-confirm-dialog">' +
            '<div class="modal-content rcas-confirm-modal">' +
            '<div class="modal-header rcas-confirm-modal-header">' +
            '<div class="d-flex align-items-center gap-2">' +
            '<span class="rcas-confirm-modal-icon-wrap"><?= smsIcon('user-tie') ?></span>' +
            '<h5 class="modal-title mb-0" id="rcasAssignConfirmTitle">Confirm Adviser Assignment</h5>' +
            '</div>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
            '</div>' +
            '<div class="modal-body rcas-confirm-modal-body">' +
            '<p class="rcas-confirm-question">Are you sure you want to assign this research adviser to the selected research group?</p>' +
            '<div class="rcas-confirm-details-card">' +
            '<div class="rcas-confirm-detail"><span class="rcas-confirm-label">Research Group</span><span class="rcas-confirm-value" id="rcasConfirmGroupNumber">—</span></div>' +
            '<div class="rcas-confirm-detail"><span class="rcas-confirm-label">Research Title</span><span class="rcas-confirm-value" id="rcasConfirmResearchTitle">—</span></div>' +
            '<div class="rcas-confirm-detail"><span class="rcas-confirm-label">Research Adviser</span><span class="rcas-confirm-value" id="rcasConfirmAdviserName">—</span></div>' +
            '<div class="rcas-confirm-detail"><span class="rcas-confirm-label">Email</span><span class="rcas-confirm-value" id="rcasConfirmAdviserEmail">—</span></div>' +
            '</div>' +
            '</div>' +
            '<div class="modal-footer rcas-confirm-modal-footer">' +
            '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="rcasAssignCancel">Cancel</button>' +
            '<button type="button" class="btn btn-primary rcas-confirm-btn" id="rcasAssignConfirm"><?= smsIcon('check', ['class' => 'me-1']) ?>Confirm Assignment</button>' +
            '</div>' +
            '</div></div></div>';
        document.body.appendChild(wrap.firstChild);
    };

    const doAssign = async function (row, group, assignButton) {
        if (assignButton) {
            assignButton.disabled = true;
            assignButton.innerHTML = '<?= smsIcon('spinner', ['class' => 'fa-spin']) ?>Saving';
        }
        try {
            const form = new FormData();
            form.append('ajax', 'assign');
            form.append('assignment_id', String(row.assignment_id || (assignButton ? (assignButton.dataset.assignId || '') : '') || ''));
            form.append('group_number', group.group_number || '');
            const res = await fetch(window.location.href, {
                method: 'POST',
                body: form,
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to assign.');
            const markAssigned = (list) => (Array.isArray(list) ? list : []).map((item) => {
                const sameAdviser = Number(item.assignment_id || 0) === Number(row.assignment_id || 0)
                    || (String(item.assignee_email || '').toLowerCase() !== '' && String(item.assignee_email || '').toLowerCase() === String(row.assignee_email || '').toLowerCase())
                    || String(item.assignee_name || '').toLowerCase() === String(row.assignee_name || '').toLowerCase();
                const keys = new Set(studentGroupKeys(group).map((v) => String(v).toLowerCase()));
                keys.add(String(selectedGroup || '').toLowerCase());
                const itemKeys = [
                    String(item.group_number || ''),
                    String(item.proposal_number || ''),
                    String(item.leader_id || ''),
                    String(item.student_id || '')
                ].map((v) => v.toLowerCase());
                const sameGroup = itemKeys.some((v) => v !== '' && keys.has(v));
                if (sameAdviser && sameGroup) {
                    return { ...item, assignment_status: 'Assigned' };
                }
                return item;
            });
            rows = markAssigned(Array.isArray(data.rows) ? data.rows : rows);
            groups = Array.isArray(data.groups) ? data.groups : groups;
            const assignedCount = rows.filter((item) => String(item.assignment_status || '').toLowerCase() === 'assigned').length;
            const pendingCount = rows.filter((item) => String(item.assignment_status || '').toLowerCase() === 'pending').length;
            if (total) total.textContent = data.stats?.total ?? rows.length;
            if (pending) pending.textContent = pendingCount;
            if (available) available.textContent = data.stats?.available ?? 0;
            if (assigned) assigned.textContent = assignedCount;
            if (lastSync) lastSync.textContent = `Synced ${data.last_sync || 'just now'}`;
            showNotice(data.message || 'Assignment saved.', 'ok');
            if (typeof window.SMSRefreshNotifications === 'function') {
                window.SMSRefreshNotifications();
            }
            render();
        } catch (error) {
            showNotice(error.message || 'Failed to assign.', 'error');
            renderProcess();
        }
    };

    const openAssignConfirm = function (row, group, assignButton) {
        ensureAssignConfirmModal();
        pendingAssign = { row: row, group: group, assignButton: assignButton };
        const grpEl = document.getElementById('rcasConfirmGroupNumber');
        const titleEl = document.getElementById('rcasConfirmResearchTitle');
        const nameEl = document.getElementById('rcasConfirmAdviserName');
        const emailEl = document.getElementById('rcasConfirmAdviserEmail');
        if (grpEl) grpEl.textContent = group.group_number || '—';
        if (titleEl) titleEl.textContent = group.research_title || group.group_name || '—';
        if (nameEl) nameEl.textContent = row.assignee_name || row.assignee_role || '—';
        if (emailEl) emailEl.textContent = row.assignee_email || '—';

        const modalEl = document.getElementById('rcasAssignConfirmModal');
        const hasBs = (typeof window !== 'undefined' && window.bootstrap && window.bootstrap.Modal);
        const modal = hasBs ? window.bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        const confirmBtn = document.getElementById('rcasAssignConfirm');
        if (confirmBtn && confirmBtn.dataset.rcasConfirmBound !== '1') {
            confirmBtn.dataset.rcasConfirmBound = '1';
            confirmBtn.addEventListener('click', function () {
                const ctx = pendingAssign;
                if (!ctx) return;
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<?= smsIcon('spinner', ['class' => 'fa-spin']) ?> Assigning...';
                if (modal) modal.hide();
                doAssign(ctx.row, ctx.group, ctx.assignButton);
            });
        }
        if (modal) modal.show();
    };

    matchList?.addEventListener('click', (event) => {
        const assignButton = event.target.closest('[data-assign-id]');
        if (!assignButton || mode !== 'assign') return;
        if (assignButton.disabled) return;
        let row = {};
        try {
            row = JSON.parse(assignButton.dataset.assignRow || '{}') || {};
        } catch (e) {
            row = {};
        }
        const group = groups.find((item) => {
            const value = item.group_number || item.proposal_number || String(item.research_group_id || '');
            return value === selectedGroup;
        });
        if (!group?.group_number) {
            showNotice('Please select an approved research group first.', 'error');
            return;
        }
        openAssignConfirm(row, group, assignButton);
    });
    contactClose?.addEventListener('click', closeContact);
    contactPanel?.addEventListener('click', (event) => {
        if (event.target === contactPanel) closeContact();
    });
    copyEmail?.addEventListener('click', () => copyText(activeContact?.row?.assignee_email || ''));
    copyMessage?.addEventListener('click', () => copyText(activeContact?.message || ''));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && contactPanel && !contactPanel.hidden) closeContact();
    });
    render();
    refreshTimer = window.setInterval(refresh, refreshDelay);
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            if (refreshTimer) window.clearInterval(refreshTimer);
            refreshTimer = null;
            return;
        }
        if (refreshTimer) window.clearInterval(refreshTimer);
        refresh();
        refreshTimer = window.setInterval(refresh, refreshDelay);
    });
})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
