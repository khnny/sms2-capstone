<?php
/**
 * Official Research Coordinator + Research Adviser names for a student's
 * Title Approval Form. Names come only from Admin assignments (Coordinator
 * Roster first, then Adviser Assignment). Until both exist, Section IX stays blank.
 */
declare(strict_types=1);

function cradStudentAssignmentGroupNumber(string $studentId): string
{
    $safe = strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', trim($studentId)) ?? '');
    if ($safe === '') {
        $safe = 'UNKNOWN';
    }

    return 'STU-' . $safe;
}

function cradEnsureAssigneeSchema(PDO $pdo): void
{
    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'crad_research_groups'")->fetch();
        if ($exists) {
            $pdo->exec("ALTER TABLE `crad_research_groups` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    } catch (Throwable $e) {
        error_log('Assignee schema group collation skipped: ' . $e->getMessage());
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
            date_assigned DATE NOT NULL,
            created_by INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `crad_research_coordinator_assignments` (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        research_group_id INT UNSIGNED NULL,
        proposal_id INT UNSIGNED NULL,
        title_approval_id INT UNSIGNED NULL,
        proposal_number VARCHAR(30) NULL,
        group_number VARCHAR(40) NULL,
        group_name VARCHAR(120) NOT NULL DEFAULT '',
        research_title VARCHAR(255) NOT NULL DEFAULT '',
        student_id VARCHAR(40) NULL,
        coordinator_user_id INT UNSIGNED NULL,
        coordinator_name VARCHAR(200) NOT NULL DEFAULT '',
        coordinator_email VARCHAR(200) NOT NULL DEFAULT '',
        status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
        assigned_by INT UNSIGNED NULL,
        assigned_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_rca_group_number (group_number),
        KEY idx_rca_group (research_group_id),
        KEY idx_rca_title_approval (title_approval_id),
        KEY idx_rca_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `crad_research_adviser_assignments` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            research_group_id INT UNSIGNED DEFAULT NULL,
            proposal_id INT UNSIGNED DEFAULT NULL,
            proposal_number VARCHAR(30) DEFAULT NULL,
            group_number VARCHAR(40) DEFAULT NULL,
            student_id VARCHAR(40) DEFAULT NULL,
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

    $addColumn = static function (PDO $pdo, string $table, string $column, string $ddl): void {
        try {
            $col = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE " . $pdo->quote($column))->fetch();
            if (!$col) {
                $pdo->exec($ddl);
            }
        } catch (Throwable $e) {
            error_log("Assignee schema column {$table}.{$column} skipped: " . $e->getMessage());
        }
    };

    $addColumn($pdo, 'crad_research_coordinator_assignments', 'student_id',
        "ALTER TABLE `crad_research_coordinator_assignments` ADD COLUMN student_id VARCHAR(40) NULL AFTER research_title, ADD KEY idx_rca_student (student_id)");
    $addColumn($pdo, 'crad_research_adviser_assignments', 'student_id',
        "ALTER TABLE `crad_research_adviser_assignments` ADD COLUMN student_id VARCHAR(40) NULL AFTER group_number, ADD KEY idx_raa_student (student_id)");

    try {
        $pdo->exec("UPDATE `crad_research_coordinator_assignments` SET student_id = NULL WHERE student_id = ''");
    } catch (Throwable $e) {
        error_log('Coordinator student_id cleanup skipped: ' . $e->getMessage());
    }

    try {
        if (!$pdo->query("SHOW INDEX FROM `crad_research_coordinator_assignments` WHERE Key_name = 'uniq_rca_student_id'")->fetch()) {
            $pdo->exec("ALTER TABLE `crad_research_coordinator_assignments` ADD UNIQUE KEY uniq_rca_student_id (student_id)");
        }
    } catch (Throwable $e) {
        error_log('Assignee unique student_id skipped: ' . $e->getMessage());
    }
}

/**
 * Placeholder research group so Admin can assign coordinator then adviser
 * before the Title Approval Form is fully signed.
 *
 * @return array<string, mixed>
 */
function cradEnsureStudentPlaceholderGroup(PDO $pdo, string $studentId, string $studentName = '', string $title = '', string $department = ''): array
{
    cradEnsureAssigneeSchema($pdo);
    $studentId = trim($studentId);
    if ($studentId === '') {
        throw new RuntimeException('Student ID is required for assignment.');
    }

    $groupNumber = cradStudentAssignmentGroupNumber($studentId);
    $stmt = $pdo->prepare(
        "SELECT * FROM `crad_research_groups`
         WHERE group_number = :group_number
            OR (leader_id = :sid AND (title_approval_id IS NULL OR title_approval_id = 0))
         ORDER BY (group_number = :group_number_order) DESC, id DESC
         LIMIT 1"
    );
    $stmt->execute([
        ':group_number' => $groupNumber,
        ':sid' => $studentId,
        ':group_number_order' => $groupNumber,
    ]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (is_array($existing)) {
        $updates = [];
        $params = [':id' => (int) $existing['id']];
        if ($studentName !== '' && trim((string) ($existing['leader_name'] ?? '')) === '') {
            $updates[] = 'leader_name = :leader_name';
            $params[':leader_name'] = $studentName;
        }
        if ($title !== '' && trim((string) ($existing['research_title'] ?? '')) === '') {
            $updates[] = 'research_title = :research_title';
            $params[':research_title'] = $title;
        }
        if ($department !== '' && trim((string) ($existing['college_dept'] ?? '')) === '') {
            $updates[] = 'college_dept = :college_dept';
            $params[':college_dept'] = $department;
        }
        if ($updates !== []) {
            $pdo->prepare('UPDATE `crad_research_groups` SET ' . implode(', ', $updates) . ' WHERE id = :id LIMIT 1')
                ->execute($params);
            $stmt->execute([
                ':group_number' => $groupNumber,
                ':sid' => $studentId,
                ':group_number_order' => $groupNumber,
            ]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: $existing;
        }
        return $existing;
    }

    $shortName = $studentName !== '' ? $studentName : $studentId;
    if (function_exists('mb_substr')) {
        $groupName = mb_substr($shortName, 0, 40);
    } else {
        $groupName = substr($shortName, 0, 40);
    }

    $ins = $pdo->prepare("
        INSERT INTO `crad_research_groups`
            (proposal_id, title_approval_id, proposal_number, group_number, group_name,
             research_title, college_dept, adviser, academic_year,
             leader_name, leader_id, leader_email, leader_contact,
             status, date_assigned, created_by)
        VALUES
            (NULL, NULL, NULL, :group_number, :group_name,
             :research_title, :college_dept, '', :academic_year,
             :leader_name, :leader_id, '', '',
             'Pending Assignment', :date_assigned, :created_by)
    ");
    $ins->execute([
        ':group_number' => $groupNumber,
        ':group_name' => $groupName,
        ':research_title' => $title !== '' ? $title : 'Pending Title Approval',
        ':college_dept' => $department,
        ':academic_year' => date('Y') . '-' . ((int) date('Y') + 1),
        ':leader_name' => $studentName,
        ':leader_id' => $studentId,
        ':date_assigned' => date('Y-m-d'),
        ':created_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
    ]);

    $id = (int) $pdo->lastInsertId();
    $row = $pdo->prepare('SELECT * FROM `crad_research_groups` WHERE id = :id LIMIT 1');
    $row->execute([':id' => $id]);
    $created = $row->fetch(PDO::FETCH_ASSOC);
    if (!is_array($created)) {
        throw new RuntimeException('Failed to create student assignment group.');
    }

    return $created;
}

/**
 * @return array{
 *   coordinator_assigned:bool,
 *   adviser_assigned:bool,
 *   ready:bool,
 *   coordinator_name:string,
 *   coordinator_email:string,
 *   adviser_name:string,
 *   adviser_email:string,
 *   group_number:string
 * }
 */
function cradStudentOfficialAssignees(PDO $pdo, string $studentId): array
{
    $empty = [
        'coordinator_assigned' => false,
        'adviser_assigned' => false,
        'ready' => false,
        'coordinator_name' => '',
        'coordinator_email' => '',
        'adviser_name' => '',
        'adviser_email' => '',
        'group_number' => '',
    ];

    $studentId = trim($studentId);
    if ($studentId === '') {
        return $empty;
    }

    try {
        cradEnsureAssigneeSchema($pdo);
    } catch (Throwable $e) {
        error_log('Assignee schema ensure failed: ' . $e->getMessage());
        return $empty;
    }

    $stuGroup = cradStudentAssignmentGroupNumber($studentId);
    $groupNumbers = [$stuGroup];
    $groupIds = [];

    try {
        $gStmt = $pdo->prepare(
            "SELECT id, group_number FROM `crad_research_groups`
             WHERE LOWER(TRIM(leader_id)) = LOWER(:sid)
                OR LOWER(TRIM(group_number)) = LOWER(:stu)
             ORDER BY id DESC"
        );
        $gStmt->execute([':sid' => $studentId, ':stu' => $stuGroup]);
        foreach ($gStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $g) {
            $gn = trim((string) ($g['group_number'] ?? ''));
            if ($gn !== '' && !in_array($gn, $groupNumbers, true)) {
                $groupNumbers[] = $gn;
            }
            $gid = (int) ($g['id'] ?? 0);
            if ($gid > 0) {
                $groupIds[] = $gid;
            }
        }
    } catch (Throwable $e) {
        error_log('Assignee group lookup failed: ' . $e->getMessage());
    }

    $coord = null;
    try {
        $sql = "SELECT coordinator_name, coordinator_email, group_number
                FROM `crad_research_coordinator_assignments`
                WHERE status = 'Active'
                  AND (
                        LOWER(TRIM(COALESCE(student_id, ''))) = LOWER(:sid)
                     OR LOWER(TRIM(COALESCE(group_number, ''))) = LOWER(:stu)";
        $params = [':sid' => $studentId, ':stu' => $stuGroup];
        if ($groupNumbers !== []) {
            $in = [];
            foreach (array_values($groupNumbers) as $i => $gn) {
                $key = ':gn' . $i;
                $in[] = $key;
                $params[$key] = $gn;
            }
            $sql .= ' OR group_number IN (' . implode(',', $in) . ')';
        }
        if ($groupIds !== []) {
            $in = [];
            foreach (array_values($groupIds) as $i => $gid) {
                $key = ':gid' . $i;
                $in[] = $key;
                $params[$key] = $gid;
            }
            $sql .= ' OR research_group_id IN (' . implode(',', $in) . ')';
        }
        $sql .= ") ORDER BY assigned_at DESC, id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $coord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        error_log('Coordinator assignment lookup failed: ' . $e->getMessage());
    }

    $adv = null;
    try {
        $sql = "SELECT adviser_name, adviser_email, group_number
                FROM `crad_research_adviser_assignments`
                WHERE assignment_status IN ('Assigned', 'Confirmed')
                  AND TRIM(adviser_name) <> ''
                  AND (
                        LOWER(TRIM(COALESCE(student_id, ''))) = LOWER(:sid)
                     OR LOWER(TRIM(COALESCE(group_number, ''))) = LOWER(:stu)";
        $params = [':sid' => $studentId, ':stu' => $stuGroup];
        if ($groupNumbers !== []) {
            $in = [];
            foreach (array_values($groupNumbers) as $i => $gn) {
                $key = ':agn' . $i;
                $in[] = $key;
                $params[$key] = $gn;
            }
            $sql .= ' OR group_number IN (' . implode(',', $in) . ')';
        }
        if ($groupIds !== []) {
            $in = [];
            foreach (array_values($groupIds) as $i => $gid) {
                $key = ':agid' . $i;
                $in[] = $key;
                $params[$key] = $gid;
            }
            $sql .= ' OR research_group_id IN (' . implode(',', $in) . ')';
        }
        $sql .= ") ORDER BY (assignment_status = 'Confirmed') DESC, (assignment_status = 'Assigned') DESC, assigned_at DESC, id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $adv = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        error_log('Adviser assignment lookup failed: ' . $e->getMessage());
    }

    $coordinatorName = trim((string) ($coord['coordinator_name'] ?? ''));
    $coordinatorEmail = trim((string) ($coord['coordinator_email'] ?? ''));
    $adviserName = trim((string) ($adv['adviser_name'] ?? ''));
    $adviserEmail = trim((string) ($adv['adviser_email'] ?? ''));
    $coordinatorAssigned = $coord !== null && $coordinatorName !== '';
    $adviserAssigned = $adv !== null && $adviserName !== '';
    $dualConfirmed = false;
    if (!function_exists('cradRgFlowAssignmentFullyConfirmed')) {
        $flowHelper = __DIR__ . '/research-group-flow.php';
        if (is_file($flowHelper)) {
            require_once $flowHelper;
        }
    }
    if (function_exists('cradRgFlowAssignmentFullyConfirmed')) {
        $dualConfirmed = cradRgFlowAssignmentFullyConfirmed($pdo, $studentId);
    }
    // Product: assignment not final until BOTH Confirm.
    $ready = $coordinatorAssigned && $adviserAssigned && $dualConfirmed;

    return [
        'coordinator_assigned' => $coordinatorAssigned,
        'adviser_assigned' => $adviserAssigned,
        'dual_confirmed' => $dualConfirmed,
        'ready' => $ready,
        'coordinator_name' => $coordinatorAssigned ? $coordinatorName : '',
        'coordinator_email' => $coordinatorAssigned ? $coordinatorEmail : '',
        'adviser_name' => $adviserAssigned ? $adviserName : '',
        'adviser_email' => $adviserAssigned ? $adviserEmail : '',
        'group_number' => (string) ($adv['group_number'] ?? $coord['group_number'] ?? $stuGroup),
    ];
}

function cradSyncTitleApprovalAssigneeNames(PDO $pdo, string $studentId): void
{
    $studentId = trim($studentId);
    if ($studentId === '') {
        return;
    }

    try {
        $names = cradStudentOfficialAssignees($pdo, $studentId);
        $stmt = $pdo->prepare(
            "UPDATE `crad_title_approvals`
                SET adviser_name = :adviser_name,
                    adviser_email = :adviser_email,
                    coordinator_name = :coordinator_name
              WHERE student_id = :sid"
        );
        $stmt->execute([
            ':adviser_name' => $names['adviser_name'],
            ':adviser_email' => $names['adviser_email'],
            ':coordinator_name' => $names['coordinator_name'],
            ':sid' => $studentId,
        ]);
    } catch (Throwable $e) {
        error_log('Title approval assignee name sync failed: ' . $e->getMessage());
    }
}

function cradStudentIdFromAssignmentGroup(string $groupNumber, array $group = []): string
{
    $leader = trim((string) ($group['leader_id'] ?? $group['student_id'] ?? ''));
    if ($leader !== '') {
        return $leader;
    }

    if (str_starts_with($groupNumber, 'STU-')) {
        return substr($groupNumber, 4);
    }

    return '';
}

function cradGroupHasActiveCoordinator(PDO $pdo, array $group): bool
{
    $groupId = (int) ($group['id'] ?? $group['research_group_id'] ?? 0);
    $groupNumber = trim((string) ($group['group_number'] ?? ''));
    $studentId = cradStudentIdFromAssignmentGroup($groupNumber, $group);

    $sql = "SELECT id FROM `crad_research_coordinator_assignments`
            WHERE status = 'Active' AND (0=1";
    $params = [];
    if ($groupId > 0) {
        $sql .= ' OR research_group_id = :gid';
        $params[':gid'] = $groupId;
    }
    if ($groupNumber !== '') {
        $sql .= ' OR group_number = :gn';
        $params[':gn'] = $groupNumber;
    }
    if ($studentId !== '') {
        $sql .= ' OR student_id = :sid';
        $params[':sid'] = $studentId;
    }
    $sql .= ') LIMIT 1';
    if ($params === []) {
        return false;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('Active coordinator check failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * After a real RG- group is created from a fully approved Title Approval,
 * copy the student's earlier STU- coordinator/adviser assignments onto it.
 */
function cradMigrateStudentAssignmentsToOfficialGroup(PDO $pdo, string $studentId, array $officialGroup): void
{
    $studentId = trim($studentId);
    $newGroupNumber = trim((string) ($officialGroup['group_number'] ?? ''));
    $newGroupId = (int) ($officialGroup['id'] ?? 0);
    $titleApprovalId = (int) ($officialGroup['title_approval_id'] ?? 0);
    if ($studentId === '' || $newGroupNumber === '' || $newGroupId <= 0) {
        return;
    }

    $stuNumber = cradStudentAssignmentGroupNumber($studentId);

    try {
        $pdo->prepare(
            "UPDATE `crad_research_coordinator_assignments`
                SET research_group_id = :gid,
                    group_number = :gn,
                    title_approval_id = COALESCE(:tid, title_approval_id),
                    student_id = :sid,
                    updated_at = NOW()
              WHERE status = 'Active'
                AND (student_id = :sid2 OR group_number = :stu)
                AND (group_number <> :gn2 OR research_group_id IS NULL OR research_group_id <> :gid2)"
        )->execute([
            ':gid' => $newGroupId,
            ':gn' => $newGroupNumber,
            ':tid' => $titleApprovalId > 0 ? $titleApprovalId : null,
            ':sid' => $studentId,
            ':sid2' => $studentId,
            ':stu' => $stuNumber,
            ':gn2' => $newGroupNumber,
            ':gid2' => $newGroupId,
        ]);
    } catch (Throwable $e) {
        error_log('Coordinator assignment migrate failed: ' . $e->getMessage());
    }

    try {
        $pdo->prepare(
            "UPDATE `crad_research_adviser_assignments`
                SET research_group_id = :gid,
                    group_number = :gn,
                    student_id = :sid,
                    updated_at = NOW()
              WHERE (student_id = :sid2 OR group_number = :stu)
                AND assignment_status IN ('Assigned', 'Confirmed', 'Pending')"
        )->execute([
            ':gid' => $newGroupId,
            ':gn' => $newGroupNumber,
            ':sid' => $studentId,
            ':sid2' => $studentId,
            ':stu' => $stuNumber,
        ]);
    } catch (Throwable $e) {
        error_log('Adviser assignment migrate failed: ' . $e->getMessage());
    }

    cradSyncTitleApprovalAssigneeNames($pdo, $studentId);
}
