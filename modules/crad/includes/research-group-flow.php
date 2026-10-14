<?php
/**
 * CRAD Research Group early flow helpers.
 *
 * Product order: Research Group (members) â†’ DH incomplete approval (if needed)
 * â†’ Assign Coordinator + Adviser â†’ Dual Confirm â†’ Title Approval (auto-fill members).
 *
 * HostForge-friendly: CREATE TABLE IF NOT EXISTS + additive column/index repair.
 */
declare(strict_types=1);

/** Minimum research group members (leader counts). */
function cradRgFlowMinMembers(): int
{
    return 5;
}

function cradRgFlowMaxMembers(): int
{
    return 5;
}

/** Shared pending statuses awaiting Department Head Approve/Reject (legacy alias included). */
function cradRgFlowPendingStatuses(): array
{
    return ['pending_dh_approval', 'pending_incomplete_approval'];
}

function cradRgFlowIsPendingDh(string $flowStatus): bool
{
    return in_array(strtolower(trim($flowStatus)), cradRgFlowPendingStatuses(), true);
}

function cradRgFlowIsDhApproved(string $flowStatus): bool
{
    return in_array(strtolower(trim($flowStatus)), ['approved', 'ready_for_assignment'], true);
}


function cradRgFlowEnsureSchema(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'crad_research_groups'")->fetch();
        if ($exists) {
            $pdo->exec("ALTER TABLE `crad_research_groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    } catch (Throwable $e) {
        error_log('RG flow group collation skipped: ' . $e->getMessage());
    }

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
            flow_status VARCHAR(40) NOT NULL DEFAULT 'draft',
            incomplete_reason TEXT DEFAULT NULL,
            is_complete TINYINT(1) NOT NULL DEFAULT 0,
            member_count INT UNSIGNED NOT NULL DEFAULT 0,
            min_members_required INT UNSIGNED NOT NULL DEFAULT 5,
            submitted_by_user_id INT UNSIGNED DEFAULT NULL,
            submitted_at DATETIME DEFAULT NULL,
            dh_decision VARCHAR(20) NOT NULL DEFAULT '',
            dh_decision_by INT UNSIGNED DEFAULT NULL,
            dh_decision_at DATETIME DEFAULT NULL,
            dh_remarks TEXT DEFAULT NULL,
            date_assigned DATE NOT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY group_number (group_number),
            KEY idx_rg_flow_status (flow_status),
            KEY idx_rg_leader (leader_id),
            KEY idx_rg_title_approval (title_approval_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $addCol = static function (PDO $pdo, string $table, string $column, string $ddl): void {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote($column))->fetch();
            if (!$col) {
                $pdo->exec($ddl);
            }
        } catch (Throwable $e) {
            error_log("RG flow column {$table}.{$column} skipped: " . $e->getMessage());
        }
    };

    $addCol($pdo, 'crad_research_groups', 'flow_status',
        "ALTER TABLE `crad_research_groups` ADD COLUMN flow_status VARCHAR(40) NOT NULL DEFAULT 'draft' AFTER status, ADD KEY idx_rg_flow_status (flow_status)");
    $addCol($pdo, 'crad_research_groups', 'incomplete_reason',
        "ALTER TABLE `crad_research_groups` ADD COLUMN incomplete_reason TEXT DEFAULT NULL AFTER flow_status");
    $addCol($pdo, 'crad_research_groups', 'is_complete',
        "ALTER TABLE `crad_research_groups` ADD COLUMN is_complete TINYINT(1) NOT NULL DEFAULT 0 AFTER incomplete_reason");
    $addCol($pdo, 'crad_research_groups', 'member_count',
        "ALTER TABLE `crad_research_groups` ADD COLUMN member_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_complete");
    $addCol($pdo, 'crad_research_groups', 'min_members_required',
        "ALTER TABLE `crad_research_groups` ADD COLUMN min_members_required INT UNSIGNED NOT NULL DEFAULT 5 AFTER member_count");
    $addCol($pdo, 'crad_research_groups', 'submitted_by_user_id',
        "ALTER TABLE `crad_research_groups` ADD COLUMN submitted_by_user_id INT UNSIGNED DEFAULT NULL AFTER min_members_required");
    $addCol($pdo, 'crad_research_groups', 'submitted_at',
        "ALTER TABLE `crad_research_groups` ADD COLUMN submitted_at DATETIME DEFAULT NULL AFTER submitted_by_user_id");
    $addCol($pdo, 'crad_research_groups', 'dh_decision',
        "ALTER TABLE `crad_research_groups` ADD COLUMN dh_decision VARCHAR(20) NOT NULL DEFAULT '' AFTER submitted_at");
    $addCol($pdo, 'crad_research_groups', 'dh_decision_by',
        "ALTER TABLE `crad_research_groups` ADD COLUMN dh_decision_by INT UNSIGNED DEFAULT NULL AFTER dh_decision");
    $addCol($pdo, 'crad_research_groups', 'dh_decision_at',
        "ALTER TABLE `crad_research_groups` ADD COLUMN dh_decision_at DATETIME DEFAULT NULL AFTER dh_decision_by");
    $addCol($pdo, 'crad_research_groups', 'dh_remarks',
        "ALTER TABLE `crad_research_groups` ADD COLUMN dh_remarks TEXT DEFAULT NULL AFTER dh_decision_at");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `crad_research_group_members` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            research_group_id INT UNSIGNED NOT NULL,
            member_order TINYINT UNSIGNED NOT NULL DEFAULT 1,
            student_id VARCHAR(40) NOT NULL DEFAULT '',
            full_name VARCHAR(160) NOT NULL DEFAULT '',
            section VARCHAR(80) NOT NULL DEFAULT '',
            email VARCHAR(190) NOT NULL DEFAULT '',
            or_number VARCHAR(80) NOT NULL DEFAULT '',
            is_leader TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_rgm_group (research_group_id),
            KEY idx_rgm_student (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `crad_research_assignment_cycles` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            research_group_id INT UNSIGNED DEFAULT NULL,
            group_number VARCHAR(40) NOT NULL DEFAULT '',
            student_id VARCHAR(40) NOT NULL DEFAULT '',
            coordinator_assignment_id INT UNSIGNED DEFAULT NULL,
            adviser_assignment_id INT UNSIGNED DEFAULT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'pending_confirmation',
            coordinator_confirmed_at DATETIME DEFAULT NULL,
            coordinator_confirmed_by INT UNSIGNED DEFAULT NULL,
            adviser_confirmed_at DATETIME DEFAULT NULL,
            adviser_confirmed_by INT UNSIGNED DEFAULT NULL,
            cancelled_at DATETIME DEFAULT NULL,
            cancelled_by INT UNSIGNED DEFAULT NULL,
            cancel_role VARCHAR(40) NOT NULL DEFAULT '',
            cancel_reason TEXT DEFAULT NULL,
            assigned_by INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_rac_group (research_group_id),
            KEY idx_rac_group_number (group_number),
            KEY idx_rac_student (student_id),
            KEY idx_rac_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Dual-confirm columns on existing assignment tables (additive; HostForge-safe).
    $addCol($pdo, 'crad_research_adviser_assignments', 'confirmation_status',
        "ALTER TABLE `crad_research_adviser_assignments` ADD COLUMN confirmation_status VARCHAR(40) NOT NULL DEFAULT 'pending_confirmation' AFTER assignment_status, ADD KEY idx_raa_confirm (confirmation_status)");
    $addCol($pdo, 'crad_research_adviser_assignments', 'confirmed_at',
        "ALTER TABLE `crad_research_adviser_assignments` ADD COLUMN confirmed_at DATETIME DEFAULT NULL AFTER confirmation_status");
    $addCol($pdo, 'crad_research_adviser_assignments', 'confirmed_by',
        "ALTER TABLE `crad_research_adviser_assignments` ADD COLUMN confirmed_by INT UNSIGNED DEFAULT NULL AFTER confirmed_at");
    $addCol($pdo, 'crad_research_coordinator_assignments', 'confirmation_status',
        "ALTER TABLE `crad_research_coordinator_assignments` ADD COLUMN confirmation_status VARCHAR(40) NOT NULL DEFAULT 'pending_confirmation' AFTER status, ADD KEY idx_rca_confirm (confirmation_status)");
    $addCol($pdo, 'crad_research_coordinator_assignments', 'confirmed_at',
        "ALTER TABLE `crad_research_coordinator_assignments` ADD COLUMN confirmed_at DATETIME DEFAULT NULL AFTER confirmation_status");
    $addCol($pdo, 'crad_research_coordinator_assignments', 'confirmed_by',
        "ALTER TABLE `crad_research_coordinator_assignments` ADD COLUMN confirmed_by INT UNSIGNED DEFAULT NULL AFTER confirmed_at");
}

/**
 * @return array<string, mixed>|null
 */
function cradRgFlowGetStudentGroup(PDO $pdo, string $studentId): ?array
{
    cradRgFlowEnsureSchema($pdo);
    $studentId = trim($studentId);
    if ($studentId === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT g.* FROM `crad_research_groups` g
         WHERE LOWER(TRIM(g.leader_id)) = LOWER(:sid)
            OR EXISTS (
                SELECT 1
                FROM `crad_research_group_members` m
                WHERE m.research_group_id = g.id
                  AND LOWER(TRIM(m.student_id)) = LOWER(:sid2)
            )
         ORDER BY
            (g.flow_status IN ('approved','ready_for_assignment','pending_dh_approval','pending_incomplete_approval','draft')) DESC,
            (g.title_approval_id IS NULL OR g.title_approval_id = 0) DESC,
            g.id DESC
         LIMIT 1"
    );
    $stmt->execute([':sid' => $studentId, ':sid2' => $studentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

/**
 * @return list<array<string, mixed>>
 */
function cradRgFlowGetMembers(PDO $pdo, int $groupId): array
{
    if ($groupId <= 0) {
        return [];
    }
    cradRgFlowEnsureSchema($pdo);
    $stmt = $pdo->prepare(
        "SELECT * FROM `crad_research_group_members`
         WHERE research_group_id = :gid
         ORDER BY member_order ASC, id ASC"
    );
    $stmt->execute([':gid' => $groupId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Members as title-approval members_json shape: [[name, section, or], ...]
 *
 * @return list<array{0:string,1:string,2:string}>
 */
function cradRgFlowMembersAsTitleJson(PDO $pdo, int $groupId): array
{
    $out = [];
    foreach (cradRgFlowGetMembers($pdo, $groupId) as $m) {
        $out[] = [
            trim((string) ($m['full_name'] ?? '')),
            trim((string) ($m['section'] ?? '')),
            '', // OR not collected on Research Group; title form keeps its own OR fields
        ];
    }

    return $out;
}

function cradRgFlowIsGroupApprovedForTitle(array $group): bool
{
    $flow = strtolower(trim((string) ($group['flow_status'] ?? '')));

    return in_array($flow, ['approved', 'ready_for_assignment'], true)
        || ($flow === 'approved')
        || (strcasecmp((string) ($group['dh_decision'] ?? ''), 'approved') === 0 && (int) ($group['is_complete'] ?? 0) === 0);
}

/**
 * Gate: student may open/send Title Approval only when group is approved
 * (complete auto-approved OR incomplete-but-DH-approved).
 */
function cradRgFlowStudentHasApprovedGroup(PDO $pdo, string $studentId): bool
{
    $group = cradRgFlowGetStudentGroup($pdo, $studentId);
    if (!$group) {
        return false;
    }
    $flow = strtolower(trim((string) ($group['flow_status'] ?? '')));

    return in_array($flow, ['approved', 'ready_for_assignment'], true);
}

/**
 * Research Proposal unlock: Research Group must be Department Head-approved.
 * pending_dh_approval / draft / rejected / missing → blocked.
 */
function cradRgFlowStudentHasSubmittedGroup(PDO $pdo, string $studentId): bool
{
    // Name kept for sidebar callers; meaning is now DH-approved (not merely submitted).
    return cradRgFlowStudentHasApprovedGroup($pdo, $studentId);
}

/**
 * Whether active Coordinator + Adviser assignment rows exist for this group/student.
 *
 * @return array{has_coordinator:bool,has_adviser:bool}
 */
function cradRgFlowStudentAssignmentPresence(PDO $pdo, array $group, string $studentId = ''): array
{
    cradRgFlowEnsureSchema($pdo);
    $sid = trim($studentId !== '' ? $studentId : (string) ($group['leader_id'] ?? ''));
    $gn = trim((string) ($group['group_number'] ?? ''));
    $gid = (int) ($group['id'] ?? 0);
    $hasCoord = false;
    $hasAdv = false;
    if ($sid === '' && $gn === '' && $gid <= 0) {
        return ['has_coordinator' => false, 'has_adviser' => false];
    }
    try {
        $cStmt = $pdo->prepare(
            "SELECT id FROM `crad_research_coordinator_assignments`
             WHERE status = 'Active'
               AND (
                    (:sid <> '' AND LOWER(TRIM(COALESCE(student_id, ''))) = LOWER(:sid2))
                 OR (:gn <> '' AND group_number = :gn2)
                 OR (:gid > 0 AND research_group_id = :gid2)
               )
             LIMIT 1"
        );
        $cStmt->execute([
            ':sid' => $sid,
            ':sid2' => $sid,
            ':gn' => $gn,
            ':gn2' => $gn,
            ':gid' => $gid,
            ':gid2' => $gid,
        ]);
        $hasCoord = (bool) $cStmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('RG assignment presence coord failed: ' . $e->getMessage());
    }
    try {
        $aStmt = $pdo->prepare(
            "SELECT id FROM `crad_research_adviser_assignments`
             WHERE assignment_status IN ('Assigned','Confirmed','Pending')
               AND TRIM(COALESCE(adviser_name, '')) <> ''
               AND (
                    (:sid <> '' AND LOWER(TRIM(COALESCE(student_id, ''))) = LOWER(:sid2))
                 OR (:gn <> '' AND group_number = :gn2)
                 OR (:gid > 0 AND research_group_id = :gid2)
               )
             LIMIT 1"
        );
        $aStmt->execute([
            ':sid' => $sid,
            ':sid2' => $sid,
            ':gn' => $gn,
            ':gn2' => $gn,
            ':gid' => $gid,
            ':gid2' => $gid,
        ]);
        $hasAdv = (bool) $aStmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('RG assignment presence adviser failed: ' . $e->getMessage());
    }

    return ['has_coordinator' => $hasCoord, 'has_adviser' => $hasAdv];
}

/**
 * Latest dual-confirm cycle status for student (empty string when none).
 */
function cradRgFlowLatestAssignmentCycleStatus(PDO $pdo, string $studentId, ?array $group = null): string
{
    cradRgFlowEnsureSchema($pdo);
    $studentId = trim($studentId);
    $groupId = (int) ($group['id'] ?? 0);
    $groupNumber = trim((string) ($group['group_number'] ?? ''));
    if ($studentId === '' && $groupId <= 0 && $groupNumber === '') {
        return '';
    }
    if ($groupId > 0 || $groupNumber !== '') {
        $stmt = $pdo->prepare(
            "SELECT status FROM `crad_research_assignment_cycles`
             WHERE (:gid > 0 AND research_group_id = :gid2)
                OR (:gn <> '' AND group_number = :gn2)
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([
            ':gid' => $groupId,
            ':gid2' => $groupId,
            ':gn' => $groupNumber,
            ':gn2' => $groupNumber,
        ]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT status FROM `crad_research_assignment_cycles`
             WHERE LOWER(TRIM(student_id)) = LOWER(:sid)
                OR group_number = :stu
             ORDER BY id DESC
             LIMIT 1"
        );
        $stu = function_exists('cradStudentAssignmentGroupNumber')
            ? cradStudentAssignmentGroupNumber($studentId)
            : ('STU-' . strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $studentId) ?? 'UNKNOWN'));
        $stmt->execute([':sid' => $studentId, ':stu' => $stu]);
    }

    return strtolower(trim((string) ($stmt->fetchColumn() ?: '')));
}

/**
 * Shared Title Approval / Research Proposal unlock stage for student UI + server gates.
 *
 * Unlock only when: DH-approved Research Group + coordinator assigned + adviser assigned
 * + dual confirmation complete.
 *
 * @return array{
 *   ok:bool,
 *   stage:string,
 *   message:string,
 *   flow_status:string,
 *   group:?array,
 *   has_coordinator:bool,
 *   has_adviser:bool,
 *   cycle_status:string,
 *   members:list
 * }
 */
function cradRgFlowTitleUnlockState(PDO $pdo, string $studentId): array
{
    $base = [
        'ok' => false,
        'stage' => 'no_group',
        'message' => 'Submit your Research Group first.',
        'flow_status' => '',
        'group' => null,
        'has_coordinator' => false,
        'has_adviser' => false,
        'cycle_status' => '',
        'members' => [],
    ];

    $group = cradRgFlowGetStudentGroup($pdo, $studentId);
    if (!$group) {
        return $base;
    }

    $flow = strtolower(trim((string) ($group['flow_status'] ?? 'draft')));
    $members = cradRgFlowMembersAsTitleJson($pdo, (int) $group['id']);
    $presence = cradRgFlowStudentAssignmentPresence($pdo, $group, $studentId);
    $cycleStatus = cradRgFlowLatestAssignmentCycleStatus($pdo, $studentId, $group);
    $base['group'] = $group;
    $base['flow_status'] = $flow;
    $base['members'] = $members;
    $base['has_coordinator'] = !empty($presence['has_coordinator']);
    $base['has_adviser'] = !empty($presence['has_adviser']);
    $base['cycle_status'] = $cycleStatus;

    if (cradRgFlowIsPendingDh($flow)) {
        $base['stage'] = 'pending_dh';
        $base['message'] = 'Waiting for Department Head approval of your Research Group.';

        return $base;
    }
    if ($flow === 'rejected') {
        $base['stage'] = 'rejected';
        $base['message'] = 'Your Research Group was rejected. Update and resubmit before Research Proposal.';

        return $base;
    }
    if ($flow === 'draft' || $flow === '') {
        $base['stage'] = 'draft';
        $base['message'] = 'Submit your Research Group first.';

        return $base;
    }
    if (!in_array($flow, ['approved', 'ready_for_assignment'], true)) {
        $base['stage'] = 'blocked';
        $base['message'] = 'Research Group must be approved before Title Approval.';

        return $base;
    }

    // DH-approved: require coordinator + adviser assignment, then dual confirm.
    if (!$base['has_coordinator'] || !$base['has_adviser']) {
        $base['stage'] = 'awaiting_assignment';
        $base['message'] = 'Waiting for coordinator and adviser assignment. You cannot apply for Title Approval yet.';

        return $base;
    }

    $confirmed = ($cycleStatus === 'confirmed') || cradRgFlowAssignmentFullyConfirmed($pdo, $studentId, $group);
    if (!$confirmed) {
        $base['stage'] = 'awaiting_confirmation';
        $label = cradRgFlowOverallStatusLabel($cycleStatus !== '' ? $cycleStatus : 'pending_confirmation');
        $base['message'] = 'Assigned by Department Head (' . $label . '). Title Approval unlocks only when Fully Assigned (both approve).';

        return $base;
    }

    $base['ok'] = true;
    $base['stage'] = 'ready';
    $base['message'] = 'Ready for Title Approval.';

    return $base;
}

/**
 * Page / apply gate for Research Proposal (Title Approval).
 * Blocks until Research Group + coordinator + adviser + dual confirm are complete.
 *
 * @return array{ok:bool,message:string,flow_status:string,group:?array,stage?:string}
 */
function cradRgFlowProposalAccessGate(PDO $pdo, string $studentId): array
{
    $state = cradRgFlowTitleUnlockState($pdo, $studentId);

    return [
        'ok' => !empty($state['ok']),
        'message' => (string) ($state['message'] ?? 'Submit your Research Group first.'),
        'flow_status' => (string) ($state['flow_status'] ?? ''),
        'group' => $state['group'] ?? null,
        'stage' => (string) ($state['stage'] ?? ''),
    ];
}

/**
 * Dual-confirm ready: both coordinator and adviser confirmed on active cycle.
 */
function cradRgFlowAssignmentFullyConfirmed(PDO $pdo, string $studentId, ?array $group = null): bool
{
    return cradRgFlowLatestAssignmentCycleStatus($pdo, $studentId, $group) === 'confirmed';
}

/**
 * Title Approval may be sent only when group approved, coor+adviser assigned, AND dual-confirm done.
 *
 * @return array{ok:bool,message:string,group:?array,members:list,stage?:string}
 */
function cradRgFlowTitleApprovalGate(PDO $pdo, string $studentId): array
{
    $state = cradRgFlowTitleUnlockState($pdo, $studentId);

    return [
        'ok' => !empty($state['ok']),
        'message' => (string) ($state['message'] ?? 'Submit your Research Group first.'),
        'group' => $state['group'] ?? null,
        'members' => is_array($state['members'] ?? null) ? $state['members'] : [],
        'stage' => (string) ($state['stage'] ?? ''),
    ];
}
/**
 * @param list<array<string,mixed>> $members
 * @return array{ok:bool,message:string,group_id?:int,flow_status?:string}
 */
function cradRgFlowSaveSubmission(
    PDO $pdo,
    string $studentId,
    string $studentName,
    string $studentEmail,
    string $department,
    int $userId,
    array $members,
    string $incompleteReason = '',
    bool $submit = true
): array {
    cradRgFlowEnsureSchema($pdo);
    if (!function_exists('cradEnsureStudentPlaceholderGroup')) {
        require_once __DIR__ . '/title-approval-assignees.php';
    }

    $studentId = trim($studentId);
    if ($studentId === '') {
        return ['ok' => false, 'message' => 'Student ID is required.'];
    }

    $clean = [];
    $seenIds = [];
    foreach ($members as $idx => $raw) {
        if (!is_array($raw)) {
            continue;
        }
        $name = trim((string) ($raw['full_name'] ?? $raw['name'] ?? ''));
        $sid = trim((string) ($raw['student_id'] ?? $raw['id'] ?? ''));
        $section = trim((string) ($raw['section'] ?? ''));
        // Research Group form does not collect email/OR; keep columns empty for schema compat.
        $email = '';
        $or = '';
        if ($name === '') {
            continue;
        }
        $key = strtolower($sid !== '' ? $sid : $name);
        if (isset($seenIds[$key])) {
            continue;
        }
        $seenIds[$key] = true;
        $clean[] = [
            'student_id' => $sid,
            'full_name' => $name,
            'section' => $section,
            'email' => $email,
            'or_number' => $or,
            'is_leader' => ($sid !== '' && strcasecmp($sid, $studentId) === 0) || $idx === 0 ? 1 : 0,
        ];
    }

    if ($clean === []) {
        return ['ok' => false, 'message' => 'Add at least one group member (the leader).'];
    }

    // Ensure leader is first and marked.
    usort($clean, static function (array $a, array $b) use ($studentId): int {
        $aLead = ($a['student_id'] !== '' && strcasecmp($a['student_id'], $studentId) === 0) || (int) $a['is_leader'] === 1;
        $bLead = ($b['student_id'] !== '' && strcasecmp($b['student_id'], $studentId) === 0) || (int) $b['is_leader'] === 1;
        if ($aLead === $bLead) {
            return 0;
        }

        return $aLead ? -1 : 1;
    });
    foreach ($clean as $i => &$m) {
        $m['is_leader'] = $i === 0 ? 1 : 0;
        if ($i === 0 && $m['student_id'] === '') {
            $m['student_id'] = $studentId;
        }
        // Do not auto-fill member email; Research Group does not collect it.

    }
    unset($m);

    $count = count($clean);
    $min = cradRgFlowMinMembers();
    $max = cradRgFlowMaxMembers();
    if ($count > $max) {
        return ['ok' => false, 'message' => 'This research group has reached the maximum of 5 student members.'];
    }

    $isComplete = $count >= $min ? 1 : 0;
    $reason = trim($incompleteReason);
    if ($submit && !$isComplete && $reason === '') {
        return ['ok' => false, 'message' => 'Groups below ' . $min . ' members require a Reason for incomplete membership.'];
    }

    if ($submit) {
        // Every submission (complete or incomplete) awaits Department Head Approve/Reject.
        $flowStatus = 'pending_dh_approval';
    } else {
        $flowStatus = 'draft';
    }

    $group = cradEnsureStudentPlaceholderGroup($pdo, $studentId, $studentName, 'Pending Title Approval', $department);
    $groupId = (int) ($group['id'] ?? 0);
    if ($groupId <= 0) {
        return ['ok' => false, 'message' => 'Could not create research group record.'];
    }

    $pdo->beginTransaction();
    try {
        $groupLock = $pdo->prepare('SELECT id FROM `crad_research_groups` WHERE id = ? FOR UPDATE');
        $groupLock->execute([$groupId]);
        if (!$groupLock->fetchColumn()) {
            throw new RuntimeException('Research group no longer exists.');
        }

        $pdo->prepare('DELETE FROM `crad_research_group_members` WHERE research_group_id = ?')->execute([$groupId]);
        $ins = $pdo->prepare(
            "INSERT INTO `crad_research_group_members`
                (research_group_id, member_order, student_id, full_name, section, email, or_number, is_leader)
             VALUES
                (:gid, :ord, :sid, :name, :section, :email, :or_number, :is_leader)"
        );
        foreach ($clean as $i => $m) {
            $ins->execute([
                ':gid' => $groupId,
                ':ord' => $i + 1,
                ':sid' => $m['student_id'],
                ':name' => $m['full_name'],
                ':section' => $m['section'],
                ':email' => $m['email'],
                ':or_number' => $m['or_number'],
                ':is_leader' => (int) $m['is_leader'],
            ]);
        }

        $upd = $pdo->prepare(
            "UPDATE `crad_research_groups`
                SET flow_status = :flow_status,
                    incomplete_reason = :reason,
                    is_complete = :is_complete,
                    member_count = :member_count,
                    min_members_required = :min_req,
                    submitted_by_user_id = :uid,
                    submitted_at = CASE WHEN :submit = 1 THEN NOW() ELSE submitted_at END,
                    dh_decision = CASE WHEN :submit2 = 1 THEN '' ELSE dh_decision END,
                    dh_decision_by = NULL,
                    dh_decision_at = CASE WHEN :submit3 = 1 THEN NULL ELSE dh_decision_at END,
                    dh_remarks = NULL,
                    leader_name = :leader_name,
                    leader_email = :leader_email,
                    college_dept = CASE WHEN :dept <> '' THEN :dept2 ELSE college_dept END,
                    status = CASE
                        WHEN :flow_status2 IN ('approved','ready_for_assignment') THEN 'Pending Assignment'
                        WHEN :flow_status3 IN ('pending_dh_approval','pending_incomplete_approval') THEN 'Pending Dept Head Approval'
                        ELSE status
                    END
              WHERE id = :id
              LIMIT 1"
        );
        $upd->execute([
            ':flow_status' => $flowStatus,
            ':reason' => $isComplete ? null : ($reason !== '' ? $reason : null),
            ':is_complete' => $isComplete,
            ':member_count' => $count,
            ':min_req' => $min,
            ':uid' => $userId > 0 ? $userId : null,
            ':submit' => $submit ? 1 : 0,
            ':submit2' => $submit ? 1 : 0,
            ':submit3' => $submit ? 1 : 0,
            ':leader_name' => $studentName !== '' ? $studentName : ($clean[0]['full_name'] ?? ''),
            ':leader_email' => $studentEmail,
            ':dept' => $department,
            ':dept2' => $department,
            ':flow_status2' => $flowStatus,
            ':flow_status3' => $flowStatus,
            ':id' => $groupId,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('RG flow save failed: ' . $e->getMessage());

        return ['ok' => false, 'message' => 'Could not save Research Group: ' . $e->getMessage()];
    }

    return [
        'ok' => true,
        'message' => $submit
            ? 'Research Group submitted. Waiting for Department Head approval.'
            : 'Research Group draft saved.',
        'group_id' => $groupId,
        'flow_status' => $flowStatus,
    ];
}

/**
 * @return array{ok:bool,message:string}
 */
function cradRgFlowDhDecide(PDO $pdo, int $groupId, string $decision, int $userId, string $remarks = ''): array
{
    cradRgFlowEnsureSchema($pdo);
    $decision = strtolower(trim($decision));
    if (!in_array($decision, ['approved', 'rejected'], true)) {
        return ['ok' => false, 'message' => 'Decision must be approved or rejected.'];
    }
    if ($groupId <= 0) {
        return ['ok' => false, 'message' => 'Invalid group.'];
    }

    $stmt = $pdo->prepare('SELECT * FROM `crad_research_groups` WHERE id = ? LIMIT 1');
    $stmt->execute([$groupId]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$group) {
        return ['ok' => false, 'message' => 'Research group not found.'];
    }
    if (!cradRgFlowIsPendingDh((string) ($group['flow_status'] ?? ''))) {
        return ['ok' => false, 'message' => 'Only groups pending Department Head approval can be decided.'];
    }

    $remarks = trim($remarks);
    if ($decision === 'rejected' && $remarks === '') {
        return ['ok' => false, 'message' => 'Remarks / reason are required when rejecting a Research Group.'];
    }

    $flow = $decision === 'approved' ? 'ready_for_assignment' : 'rejected';
    $statusLabel = $decision === 'approved' ? 'Pending Assignment' : 'Rejected';

    $pdo->prepare(
        "UPDATE `crad_research_groups`
            SET flow_status = :flow,
                dh_decision = :decision,
                dh_decision_by = :uid,
                dh_decision_at = NOW(),
                dh_remarks = :remarks,
                status = :status
          WHERE id = :id
          LIMIT 1"
    )->execute([
        ':flow' => $flow,
        ':decision' => $decision,
        ':uid' => $userId > 0 ? $userId : null,
        ':remarks' => $remarks !== '' ? $remarks : null,
        ':status' => $statusLabel,
        ':id' => $groupId,
    ]);

    return [
        'ok' => true,
        'message' => $decision === 'approved'
            ? 'Research Group approved. Adviser/Coordinator assignment may proceed.'
            : 'Research Group rejected. Student must resubmit.',
    ];
}

/**
 * @return list<array<string,mixed>>
 */
function cradRgFlowPendingIncompleteQueue(PDO $pdo): array
{
    cradRgFlowEnsureSchema($pdo);
    // All submissions awaiting DH (complete + incomplete). Legacy pending_incomplete_approval included.
    $rows = $pdo->query(
        "SELECT g.*,
                (SELECT COUNT(*) FROM `crad_research_group_members` m WHERE m.research_group_id = g.id) AS live_member_count
         FROM `crad_research_groups` g
         WHERE g.flow_status IN ('pending_dh_approval', 'pending_incomplete_approval')
         ORDER BY g.submitted_at ASC, g.id ASC"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$row) {
        $row['members'] = cradRgFlowGetMembers($pdo, (int) $row['id']);
    }
    unset($row);

    return $rows;
}

/**
 * Start or refresh dual-confirm cycle after DH assigns coordinator + adviser.
 *
 * @return array{ok:bool,message:string,cycle_id?:int}
 */

/**
 * Dept Head: complete / DH-approved groups waiting for Coordinator+Adviser assignment.
 *
 * @return list<array<string,mixed>>
 */
function cradRgFlowAssignmentReadyQueue(PDO $pdo): array
{
    cradRgFlowEnsureSchema($pdo);
    $rows = $pdo->query(
        "SELECT g.*,
                (SELECT COUNT(*) FROM `crad_research_group_members` m WHERE m.research_group_id = g.id) AS live_member_count
         FROM `crad_research_groups` g
         WHERE g.flow_status IN ('approved', 'ready_for_assignment')
           AND (g.title_approval_id IS NULL OR g.title_approval_id = 0)
         ORDER BY g.submitted_at DESC, g.id DESC"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$row) {
        $row['members'] = cradRgFlowGetMembers($pdo, (int) $row['id']);
        $sid = trim((string) ($row['leader_id'] ?? ''));
        $hasCoord = false;
        $hasAdv = false;
        if ($sid !== '') {
            try {
                $cStmt = $pdo->prepare(
                    "SELECT id FROM `crad_research_coordinator_assignments`
                     WHERE status = 'Active'
                       AND (student_id = :sid OR group_number = :gn OR research_group_id = :gid)
                     LIMIT 1"
                );
                $cStmt->execute([
                    ':sid' => $sid,
                    ':gn' => (string) ($row['group_number'] ?? ''),
                    ':gid' => (int) ($row['id'] ?? 0),
                ]);
                $hasCoord = (bool) $cStmt->fetchColumn();
                $aStmt = $pdo->prepare(
                    "SELECT id FROM `crad_research_adviser_assignments`
                     WHERE assignment_status IN ('Assigned','Confirmed','Pending')
                       AND (student_id = :sid OR group_number = :gn OR research_group_id = :gid)
                     LIMIT 1"
                );
                $aStmt->execute([
                    ':sid' => $sid,
                    ':gn' => (string) ($row['group_number'] ?? ''),
                    ':gid' => (int) ($row['id'] ?? 0),
                ]);
                $hasAdv = (bool) $aStmt->fetchColumn();
            } catch (Throwable $e) {
                error_log('RG assignment-ready flags failed: ' . $e->getMessage());
            }
        }
        $row['has_coordinator'] = $hasCoord;
        $row['has_adviser'] = $hasAdv;
        $cycleRow = null;
        if (($hasCoord || $hasAdv) && $sid !== '') {
            $cycleRow = cradRgFlowLatestCycleForGroup(
                $pdo,
                (string) ($row['group_number'] ?? ''),
                $sid,
                (int) ($row['id'] ?? 0)
            );
        }
        if ($hasCoord || $hasAdv) {
            $cycleStatus = cradRgFlowOverallStatusFromCycle($cycleRow);
            if ($cycleStatus !== '') {
                $row['assignment_label'] = cradRgFlowOverallStatusLabel($cycleStatus);
                $row['overall_status'] = $cycleStatus;
            } else {
                $row['assignment_label'] = 'Assigned by Department Head';
                $row['overall_status'] = 'pending_confirmation';
            }
        } else {
            $row['assignment_label'] = 'Ready for assignment';
            $row['overall_status'] = '';
        }
    }
    unset($row);

    return $rows;
}
/**
 * Human-readable overall dual-confirm status.
 * DH Assign is never Fully Assigned — only both approvals yield Fully Assigned.
 */
function cradRgFlowOverallStatusLabel(string $status): string
{
    $status = strtolower(trim($status));
    return match ($status) {
        'pending_confirmation' => 'Pending Confirmation',
        'waiting_adviser' => 'Waiting for Adviser',
        'waiting_coordinator' => 'Waiting for Coordinator',
        'confirmed' => 'Fully Assigned',
        'needs_reassignment', 'cancelled', 'declined' => 'Needs Reassignment',
        default => $status !== '' ? ucwords(str_replace('_', ' ', $status)) : 'Unassigned',
    };
}

/**
 * Derive overall cycle status from a cycle row.
 *
 * @param array<string,mixed>|null $cycle
 */
function cradRgFlowOverallStatusFromCycle(?array $cycle): string
{
    if (!$cycle) {
        return '';
    }
    $status = strtolower(trim((string) ($cycle['status'] ?? '')));
    if (in_array($status, ['confirmed', 'needs_reassignment', 'cancelled', 'declined'], true)) {
        return ($status === 'cancelled' || $status === 'declined') ? 'needs_reassignment' : $status;
    }
    $coorOk = !empty($cycle['coordinator_confirmed_at']);
    $advOk = !empty($cycle['adviser_confirmed_at']);
    if ($coorOk && $advOk) {
        return 'confirmed';
    }
    if ($coorOk && !$advOk) {
        return 'waiting_adviser';
    }
    if (!$coorOk && $advOk) {
        return 'waiting_coordinator';
    }
    if (in_array($status, ['pending_confirmation', 'waiting_adviser', 'waiting_coordinator'], true)) {
        return $status;
    }
    return 'pending_confirmation';
}

/**
 * Latest assignment cycle for a group/student (RCM status column).
 *
 * @return array<string,mixed>|null
 */
function cradRgFlowLatestCycleForGroup(PDO $pdo, string $groupNumber = '', string $studentId = '', int $groupId = 0): ?array
{
    cradRgFlowEnsureSchema($pdo);
    $groupNumber = trim($groupNumber);
    $studentId = trim($studentId);
    if ($groupNumber === '' && $studentId === '' && $groupId <= 0) {
        return null;
    }
    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM `crad_research_assignment_cycles`
             WHERE (:gid > 0 AND research_group_id = :gid2)
                OR (:gn <> '' AND group_number = :gn2)
                OR (:sid <> '' AND LOWER(TRIM(student_id)) = LOWER(:sid2))
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([
            ':gid' => $groupId,
            ':gid2' => $groupId,
            ':gn' => $groupNumber,
            ':gn2' => $groupNumber,
            ':sid' => $studentId,
            ':sid2' => $studentId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('RG latest cycle lookup failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * True when dual-confirm cycle is Fully Assigned (both approved).
 */
function cradRgFlowIsFullyAssignedStatus(string $status): bool
{
    return strtolower(trim($status)) === 'confirmed';
}
function cradRgFlowStartAssignmentCycle(
    PDO $pdo,
    int $groupId,
    string $groupNumber,
    string $studentId,
    ?int $coordinatorAssignmentId,
    ?int $adviserAssignmentId,
    int $assignedBy
): array {
    cradRgFlowEnsureSchema($pdo);
    if ($groupId <= 0 && $groupNumber === '' && $studentId === '') {
        return ['ok' => false, 'message' => 'Group reference required.'];
    }

    $coordinatorAssignmentId = ($coordinatorAssignmentId !== null && $coordinatorAssignmentId > 0)
        ? $coordinatorAssignmentId
        : null;
    $adviserAssignmentId = ($adviserAssignmentId !== null && $adviserAssignmentId > 0)
        ? $adviserAssignmentId
        : null;
    if ($coordinatorAssignmentId === null && $adviserAssignmentId === null) {
        return ['ok' => false, 'message' => 'At least one assignment (coordinator or adviser) is required.'];
    }

    // Prefer extending an open cycle when DH assigns the other role later,
    // so an early Accept is not wiped. Supersede only on true reassignment.
    $existing = null;
    $exCoordId = 0;
    $exAdvId = 0;
    try {
        $find = $pdo->prepare(
            "SELECT * FROM `crad_research_assignment_cycles`
              WHERE status IN ('pending_confirmation','waiting_adviser','waiting_coordinator')
                AND (
                        (:gid > 0 AND research_group_id = :gid2)
                     OR (:gn <> '' AND group_number = :gn2)
                     OR (:sid <> '' AND student_id = :sid2)
                )
              ORDER BY id DESC
              LIMIT 1"
        );
        $find->execute([
            ':gid' => $groupId,
            ':gid2' => $groupId,
            ':gn' => $groupNumber,
            ':gn2' => $groupNumber,
            ':sid' => $studentId,
            ':sid2' => $studentId,
        ]);
        $existing = $find->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        error_log('RG open cycle lookup failed: ' . $e->getMessage());
    }

    $canExtend = false;
    $attachCoord = false;
    $attachAdv = false;
    if ($existing) {
        $exCoordId = (int) ($existing['coordinator_assignment_id'] ?? 0);
        $exAdvId = (int) ($existing['adviser_assignment_id'] ?? 0);
        $coordReplace = $coordinatorAssignmentId !== null && $exCoordId > 0
            && $exCoordId !== (int) $coordinatorAssignmentId;
        $advReplace = $adviserAssignmentId !== null && $exAdvId > 0
            && $exAdvId !== (int) $adviserAssignmentId;
        $canExtend = !$coordReplace && !$advReplace;
        $attachCoord = $coordinatorAssignmentId !== null && $exCoordId !== (int) $coordinatorAssignmentId;
        $attachAdv = $adviserAssignmentId !== null && $exAdvId !== (int) $adviserAssignmentId;
    }

    if ($canExtend && $existing) {
        $cycleId = (int) $existing['id'];
        $mergedCoordId = $coordinatorAssignmentId ?? ($exCoordId > 0 ? $exCoordId : null);
        $mergedAdvId = $adviserAssignmentId ?? ($exAdvId > 0 ? $exAdvId : null);

        $sets = [
            'research_group_id = COALESCE(:gid, research_group_id)',
            "group_number = CASE WHEN :gn <> '' THEN :gn2 ELSE group_number END",
            "student_id = CASE WHEN :sid <> '' THEN :sid2 ELSE student_id END",
            'coordinator_assignment_id = :cid',
            'adviser_assignment_id = :aid',
            'updated_at = NOW()',
        ];
        $params = [
            ':gid' => $groupId > 0 ? $groupId : null,
            ':gn' => $groupNumber,
            ':gn2' => $groupNumber,
            ':sid' => $studentId,
            ':sid2' => $studentId,
            ':cid' => $mergedCoordId,
            ':aid' => $mergedAdvId,
            ':id' => $cycleId,
        ];
        if ($attachCoord) {
            $sets[] = 'coordinator_confirmed_at = NULL';
            $sets[] = 'coordinator_confirmed_by = NULL';
        }
        if ($attachAdv) {
            $sets[] = 'adviser_confirmed_at = NULL';
            $sets[] = 'adviser_confirmed_by = NULL';
        }

        $pdo->prepare(
            'UPDATE `crad_research_assignment_cycles` SET ' . implode(', ', $sets) . ' WHERE id = :id'
        )->execute($params);

        // Re-derive status from remaining confirmations (never Fully Assigned until both accept).
        $reload = $pdo->prepare('SELECT * FROM `crad_research_assignment_cycles` WHERE id = ? LIMIT 1');
        $reload->execute([$cycleId]);
        $updated = $reload->fetch(PDO::FETCH_ASSOC) ?: $existing;
        $coorOk = !empty($updated['coordinator_confirmed_at']);
        $advOk = !empty($updated['adviser_confirmed_at']);
        if ($coorOk && $advOk && $mergedCoordId && $mergedAdvId) {
            $newStatus = 'confirmed';
        } elseif ($coorOk && !$advOk) {
            $newStatus = 'waiting_adviser';
        } elseif (!$coorOk && $advOk) {
            $newStatus = 'waiting_coordinator';
        } else {
            $newStatus = 'pending_confirmation';
        }
        $pdo->prepare(
            "UPDATE `crad_research_assignment_cycles` SET status = ?, updated_at = NOW() WHERE id = ?"
        )->execute([$newStatus, $cycleId]);

        if ($attachAdv && $mergedAdvId) {
            try {
                $pdo->prepare(
                    "UPDATE `crad_research_adviser_assignments`
                        SET confirmation_status = 'pending_confirmation',
                            confirmed_at = NULL,
                            confirmed_by = NULL,
                            assignment_status = CASE
                                WHEN assignment_status IN ('Cancelled','Pending') THEN 'Assigned'
                                ELSE assignment_status
                            END
                      WHERE id = ?"
                )->execute([$mergedAdvId]);
            } catch (Throwable $e) {
                error_log('Adviser confirm reset skipped: ' . $e->getMessage());
            }
        }
        if ($attachCoord && $mergedCoordId) {
            try {
                $pdo->prepare(
                    "UPDATE `crad_research_coordinator_assignments`
                        SET confirmation_status = 'pending_confirmation',
                            confirmed_at = NULL,
                            confirmed_by = NULL
                      WHERE id = ?"
                )->execute([$mergedCoordId]);
            } catch (Throwable $e) {
                error_log('Coordinator confirm reset skipped: ' . $e->getMessage());
            }
        }

        return [
            'ok' => true,
            'message' => 'Assigned by Department Head. Status: Pending Confirmation until both Approve Assignment.',
            'cycle_id' => $cycleId,
            'extended' => true,
        ];
    }

    // Cancel any open cycle for this student/group (true reassignment / fresh start).
    $pdo->prepare(
        "UPDATE `crad_research_assignment_cycles`
            SET status = 'cancelled',
                cancelled_at = NOW(),
                cancelled_by = :uid,
                cancel_role = 'system',
                cancel_reason = 'Superseded by new assignment',
                updated_at = NOW()
          WHERE status IN ('pending_confirmation','waiting_adviser','waiting_coordinator','needs_reassignment')
            AND (
                    (:gid > 0 AND research_group_id = :gid2)
                 OR (:gn <> '' AND group_number = :gn2)
                 OR (:sid <> '' AND student_id = :sid2)
            )"
    )->execute([
        ':uid' => $assignedBy > 0 ? $assignedBy : null,
        ':gid' => $groupId,
        ':gid2' => $groupId,
        ':gn' => $groupNumber,
        ':gn2' => $groupNumber,
        ':sid' => $studentId,
        ':sid2' => $studentId,
    ]);

    $ins = $pdo->prepare(
        "INSERT INTO `crad_research_assignment_cycles`
            (research_group_id, group_number, student_id,
             coordinator_assignment_id, adviser_assignment_id,
             status, assigned_by)
         VALUES
            (:gid, :gn, :sid, :cid, :aid, 'pending_confirmation', :by)"
    );
    $ins->execute([
        ':gid' => $groupId > 0 ? $groupId : null,
        ':gn' => $groupNumber,
        ':sid' => $studentId,
        ':cid' => $coordinatorAssignmentId,
        ':aid' => $adviserAssignmentId,
        ':by' => $assignedBy > 0 ? $assignedBy : null,
    ]);
    $cycleId = (int) $pdo->lastInsertId();

    if ($adviserAssignmentId) {
        try {
            $pdo->prepare(
                "UPDATE `crad_research_adviser_assignments`
                    SET confirmation_status = 'pending_confirmation',
                        confirmed_at = NULL,
                        confirmed_by = NULL,
                        assignment_status = CASE
                            WHEN assignment_status IN ('Cancelled','Pending') THEN 'Assigned'
                            ELSE assignment_status
                        END
                  WHERE id = ?"
            )->execute([$adviserAssignmentId]);
        } catch (Throwable $e) {
            error_log('Adviser confirm reset skipped: ' . $e->getMessage());
        }
    }
    if ($coordinatorAssignmentId) {
        try {
            $pdo->prepare(
                "UPDATE `crad_research_coordinator_assignments`
                    SET confirmation_status = 'pending_confirmation',
                        confirmed_at = NULL,
                        confirmed_by = NULL
                  WHERE id = ?"
            )->execute([$coordinatorAssignmentId]);
        } catch (Throwable $e) {
            error_log('Coordinator confirm reset skipped: ' . $e->getMessage());
        }
    }

    return [
        'ok' => true,
        'message' => 'Assigned by Department Head. Status: Pending Confirmation until both Approve Assignment.',
        'cycle_id' => $cycleId,
    ];
}


/**
 * @return array{ok:bool,message:string,status?:string}
 */
function cradRgFlowConfirmRole(PDO $pdo, int $cycleId, string $role, int $userId): array
{
    cradRgFlowEnsureSchema($pdo);
    $role = strtolower(trim($role));
    if (!in_array($role, ['adviser', 'coordinator', 'research_coordinator'], true)) {
        return ['ok' => false, 'message' => 'Invalid confirm role.'];
    }
    $isAdviser = $role === 'adviser';

    $stmt = $pdo->prepare('SELECT * FROM `crad_research_assignment_cycles` WHERE id = ? LIMIT 1');
    $stmt->execute([$cycleId]);
    $cycle = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cycle) {
        return ['ok' => false, 'message' => 'Assignment cycle not found.'];
    }
    $status = strtolower((string) ($cycle['status'] ?? ''));
    if (in_array($status, ['cancelled', 'confirmed', 'needs_reassignment', 'declined'], true)) {
        return ['ok' => false, 'message' => 'This assignment cycle is already ' . cradRgFlowOverallStatusLabel($status) . '.'];
    }

    if ($isAdviser) {
        $pdo->prepare(
            "UPDATE `crad_research_assignment_cycles`
                SET adviser_confirmed_at = NOW(),
                    adviser_confirmed_by = :uid,
                    status = CASE
                        WHEN coordinator_confirmed_at IS NOT NULL
                             AND coordinator_assignment_id IS NOT NULL
                             AND adviser_assignment_id IS NOT NULL THEN 'confirmed'
                        ELSE 'waiting_coordinator'
                    END,
                    updated_at = NOW()
              WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $cycleId]);
        if (!empty($cycle['adviser_assignment_id'])) {
            $pdo->prepare(
                "UPDATE `crad_research_adviser_assignments`
                    SET confirmation_status = 'confirmed',
                        confirmed_at = NOW(),
                        confirmed_by = ?,
                        assignment_status = 'Confirmed'
                  WHERE id = ?"
            )->execute([$userId, (int) $cycle['adviser_assignment_id']]);
        }
    } else {
        $pdo->prepare(
            "UPDATE `crad_research_assignment_cycles`
                SET coordinator_confirmed_at = NOW(),
                    coordinator_confirmed_by = :uid,
                    status = CASE
                        WHEN adviser_confirmed_at IS NOT NULL
                             AND adviser_assignment_id IS NOT NULL
                             AND coordinator_assignment_id IS NOT NULL THEN 'confirmed'
                        ELSE 'waiting_adviser'
                    END,
                    updated_at = NOW()
              WHERE id = :id"
        )->execute([':uid' => $userId, ':id' => $cycleId]);
        if (!empty($cycle['coordinator_assignment_id'])) {
            $pdo->prepare(
                "UPDATE `crad_research_coordinator_assignments`
                    SET confirmation_status = 'confirmed',
                        confirmed_at = NOW(),
                        confirmed_by = ?
                  WHERE id = ?"
            )->execute([$userId, (int) $cycle['coordinator_assignment_id']]);
        }
    }

    $stmt->execute([$cycleId]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC) ?: $cycle;
    $newStatus = (string) ($updated['status'] ?? '');

    return [
        'ok' => true,
        'message' => $newStatus === 'confirmed'
            ? 'Fully Assigned. Both Coordinator and Adviser approved. Title Approval may proceed.'
            : ('Approval recorded. ' . cradRgFlowOverallStatusLabel($newStatus) . '.'),
        'overall_label' => cradRgFlowOverallStatusLabel($newStatus),
        'status' => $newStatus,
    ];
}

/**
 * @return array{ok:bool,message:string}
 */
function cradRgFlowCancelCycle(PDO $pdo, int $cycleId, string $role, int $userId, string $reason = ''): array
{
    cradRgFlowEnsureSchema($pdo);
    $stmt = $pdo->prepare('SELECT * FROM `crad_research_assignment_cycles` WHERE id = ? LIMIT 1');
    $stmt->execute([$cycleId]);
    $cycle = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cycle) {
        return ['ok' => false, 'message' => 'Assignment cycle not found.'];
    }
    if (in_array(strtolower((string) ($cycle['status'] ?? '')), ['cancelled', 'needs_reassignment', 'declined'], true)) {
        return ['ok' => false, 'message' => 'Already declined. Department Head can reassign.'];
    }

    $pdo->prepare(
        "UPDATE `crad_research_assignment_cycles`
            SET status = 'needs_reassignment',
                cancelled_at = NOW(),
                cancelled_by = :uid,
                cancel_role = :role,
                cancel_reason = :reason,
                updated_at = NOW()
          WHERE id = :id"
    )->execute([
        ':uid' => $userId,
        ':role' => $role,
        ':reason' => trim($reason) !== '' ? trim($reason) : 'Assignment declined',
        ':id' => $cycleId,
    ]);

    if (!empty($cycle['adviser_assignment_id'])) {
        try {
            $pdo->prepare(
                "UPDATE `crad_research_adviser_assignments`
                    SET assignment_status = 'Cancelled',
                        confirmation_status = 'cancelled',
                        availability_status = 'Cancelled'
                  WHERE id = ?"
            )->execute([(int) $cycle['adviser_assignment_id']]);
        } catch (Throwable $e) {
            error_log('Adviser cancel update skipped: ' . $e->getMessage());
        }
    }
    if (!empty($cycle['coordinator_assignment_id'])) {
        try {
            $pdo->prepare(
                "UPDATE `crad_research_coordinator_assignments`
                    SET status = 'Inactive',
                        confirmation_status = 'cancelled'
                  WHERE id = ?"
            )->execute([(int) $cycle['coordinator_assignment_id']]);
        } catch (Throwable $e) {
            error_log('Coordinator cancel update skipped: ' . $e->getMessage());
        }
    }

    return [
        'ok' => true,
        'message' => 'Assignment Declined. Status: Needs Reassignment. Department Head can reassign Coordinator and Adviser.',
        'status' => 'needs_reassignment',
        'overall_label' => cradRgFlowOverallStatusLabel('needs_reassignment'),
    ];
}

/**
 * Pending dual-confirm rows for adviser or coordinator inbox.
 *
 * @return list<array<string,mixed>>
 */
function cradRgFlowPendingConfirmationsForUser(PDO $pdo, string $roleKey, int $userId, string $email = '', string $fullName = ''): array
{
    cradRgFlowEnsureSchema($pdo);
    $roleKey = strtolower($roleKey);
    $email = strtolower(trim($email));
    $fullName = trim($fullName);

    $sql = "SELECT c.*, g.research_title, g.leader_name, g.group_name, g.college_dept,
                   a.adviser_name, a.adviser_email, a.adviser_user_id,
                   r.coordinator_name, r.coordinator_email, r.coordinator_user_id
            FROM `crad_research_assignment_cycles` c
            LEFT JOIN `crad_research_groups` g ON g.id = c.research_group_id
            LEFT JOIN `crad_research_adviser_assignments` a ON a.id = c.adviser_assignment_id
            LEFT JOIN `crad_research_coordinator_assignments` r ON r.id = c.coordinator_assignment_id
            WHERE c.status IN ('pending_confirmation','waiting_adviser','waiting_coordinator')";

    if ($roleKey === 'adviser') {
        $sql .= " AND (
            (:uid > 0 AND a.adviser_user_id = :uid2)
            OR (:email <> '' AND LOWER(TRIM(a.adviser_email)) = :email2)
            OR (:name <> '' AND LOWER(TRIM(a.adviser_name)) = LOWER(:name2))
        ) AND c.adviser_confirmed_at IS NULL";
    } elseif (in_array($roleKey, ['research_coordinator', 'coordinator'], true)) {
        $sql .= " AND (
            (:uid > 0 AND r.coordinator_user_id = :uid2)
            OR (:email <> '' AND LOWER(TRIM(r.coordinator_email)) = :email2)
            OR (:name <> '' AND LOWER(TRIM(r.coordinator_name)) = LOWER(:name2))
        ) AND c.coordinator_confirmed_at IS NULL";
    } else {
        return [];
    }

    $sql .= ' ORDER BY c.created_at ASC, c.id ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid' => $userId,
        ':uid2' => $userId,
        ':email' => $email,
        ':email2' => $email,
        ':name' => $fullName,
        ':name2' => $fullName,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * After title fully approved: promote placeholder flow status / sync pendingâ†’active lists.
 */
function cradRgFlowSyncAfterTitleApproved(PDO $pdo, int $titleApprovalId, int $officialGroupId): void
{
    if ($titleApprovalId <= 0 || $officialGroupId <= 0) {
        return;
    }
    cradRgFlowEnsureSchema($pdo);

    try {
        // Copy members from student early group (by leader) onto official group if empty.
        $t = $pdo->prepare('SELECT student_id FROM `crad_title_approvals` WHERE id = ? LIMIT 1');
        $t->execute([$titleApprovalId]);
        $studentId = trim((string) ($t->fetchColumn() ?: ''));
        if ($studentId === '') {
            return;
        }

        $early = cradRgFlowGetStudentGroup($pdo, $studentId);
        if ($early && (int) $early['id'] !== $officialGroupId) {
            $existing = cradRgFlowGetMembers($pdo, $officialGroupId);
            if ($existing === []) {
                $earlyMembers = array_slice(
                    cradRgFlowGetMembers($pdo, (int) $early['id']),
                    0,
                    cradRgFlowMaxMembers()
                );
                foreach ($earlyMembers as $i => $m) {
                    $pdo->prepare(
                        "INSERT INTO `crad_research_group_members`
                            (research_group_id, member_order, student_id, full_name, section, email, or_number, is_leader)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    )->execute([
                        $officialGroupId,
                        $i + 1,
                        $m['student_id'],
                        $m['full_name'],
                        $m['section'],
                        $m['email'],
                        $m['or_number'],
                        (int) $m['is_leader'],
                    ]);
                }
            }
            // Mark early placeholder superseded but keep history.
            $pdo->prepare(
                "UPDATE `crad_research_groups`
                    SET flow_status = 'approved',
                        status = 'Migrated'
                  WHERE id = ? AND (title_approval_id IS NULL OR title_approval_id = 0)
                  LIMIT 1"
            )->execute([(int) $early['id']]);
        }

        $pdo->prepare(
            "UPDATE `crad_research_groups`
                SET flow_status = 'approved',
                    status = 'Approved',
                    is_complete = 1
              WHERE id = ?
              LIMIT 1"
        )->execute([$officialGroupId]);

        // pending â†’ active for assignment lists that use those labels
        $pdo->prepare(
            "UPDATE `crad_research_adviser_assignments`
                SET availability_status = CASE
                        WHEN availability_status IN ('Pending','pending') THEN 'Available'
                        ELSE availability_status
                    END,
                    assignment_status = CASE
                        WHEN confirmation_status = 'confirmed' THEN 'Confirmed'
                        WHEN assignment_status = 'Pending' THEN 'Assigned'
                        ELSE assignment_status
                    END
              WHERE research_group_id = ? OR student_id = ?"
        )->execute([$officialGroupId, $studentId]);
    } catch (Throwable $e) {
        error_log('RG flow post-title sync failed: ' . $e->getMessage());
    }
}
