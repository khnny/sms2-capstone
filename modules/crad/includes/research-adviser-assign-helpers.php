<?php
/**
 * Shared Research Adviser assignment helpers for Research Coordinator Management.
 * Lightweight pool + save path (does not load the 4-page adviser wizard UI).
 */

if (!function_exists('rcmAdviserEnsureSchema')) {
    function rcmAdviserEnsureSchema(PDO $pdo): void
    {
        if (function_exists('cradEnsureAssigneeSchema')) {
            cradEnsureAssigneeSchema($pdo);
        }
    }
}

if (!function_exists('rcmAdviserExpertiseHint')) {
    function rcmAdviserExpertiseHint(string $title): string
    {
        $title = strtolower($title);
        $rules = [
            'Education / Pedagogy' => ['education', 'teaching', 'pedagogy', 'classroom', 'learning'],
            'Information Technology' => ['information technology', 'software', 'computing', 'ict', 'system', 'web', 'mobile', 'network'],
            'Business / Management' => ['business', 'management', 'marketing', 'finance', 'entrepreneur'],
            'Health Sciences' => ['health', 'nursing', 'medical', 'clinical', 'patient'],
            'Engineering' => ['engineering', 'mechanical', 'electrical', 'civil', 'construction'],
            'Social Sciences' => ['social', 'community', 'psychology', 'sociology', 'behavior'],
            'Agriculture / Environment' => ['agriculture', 'environment', 'farming', 'ecology', 'sustainability'],
        ];
        $matches = [];
        foreach ($rules as $label => $keywords) {
            foreach ($keywords as $kw) {
                if ($kw !== '' && str_contains($title, $kw)) {
                    $matches[] = $label;
                    break;
                }
            }
        }
        return $matches !== [] ? implode(', ', array_unique($matches)) : 'General Research Methods';
    }
}

if (!function_exists('rcmAdviserMatchScore')) {
    function rcmAdviserMatchScore(string $expertise, string $requiredHint): int
    {
        $expertise = strtolower($expertise);
        $needles = preg_split('/[^a-z0-9]+/i', strtolower($requiredHint)) ?: [];
        $score = 0;
        foreach ($needles as $n) {
            if (strlen($n) < 4) {
                continue;
            }
            if (str_contains($expertise, $n)) {
                $score += 2;
            }
        }
        return $score;
    }
}

/**
 * Eligible faculty advisers from User Management (active adviser-like roles).
 *
 * @return array<int, array{user_id:int,name:string,email:string,expertise:string,role_key:string}>
 */
if (!function_exists('rcmAdviserPool')) {
    function rcmAdviserPool(): array
    {
        try {
            $smsPdo = getDatabaseConnection();
            $stmt = $smsPdo->query("
                SELECT id, full_name, email, role_key
                FROM `sms2_users`
                WHERE status = 'active'
                  AND TRIM(full_name) <> ''
                  AND (
                        role_key IN ('adviser', 'research_adviser')
                     OR LOWER(REPLACE(role_key, ' ', '_')) LIKE '%adviser%'
                  )
                ORDER BY full_name ASC, id ASC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('RCM adviser pool failed: ' . $e->getMessage());
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'user_id' => (int) ($r['id'] ?? 0),
                'name' => trim((string) ($r['full_name'] ?? '')),
                'email' => trim((string) ($r['email'] ?? '')),
                'expertise' => 'General Research Methods',
                'role_key' => (string) ($r['role_key'] ?? ''),
            ];
        }
        return $out;
    }
}

/**
 * Active coordinator assignments that still need a research adviser.
 *
 * @return array<int, array<string, mixed>>
 */
if (!function_exists('rcmGroupsNeedingAdviser')) {
    function rcmGroupsNeedingAdviser(PDO $pdo): array
    {
        rcmAdviserEnsureSchema($pdo);
        try {
            if (function_exists('cradRgFlowEnsureSchema')) {
                cradRgFlowEnsureSchema($pdo);
            }
        } catch (Throwable $e) {
            error_log('RCM adviser flow schema skipped: ' . $e->getMessage());
        }

        try {
            $rows = $pdo->query(
                "SELECT a.id AS coordinator_assignment_id,
                        a.research_group_id AS group_id,
                        a.group_number,
                        a.group_name,
                        a.research_title,
                        a.student_id,
                        a.coordinator_name,
                        a.coordinator_email,
                        a.assigned_at,
                        g.adviser AS group_adviser,
                        g.flow_status,
                        g.leader_name,
                        g.leader_id
                 FROM `crad_research_coordinator_assignments` a
                 LEFT JOIN `crad_research_groups` g ON g.id = a.research_group_id
                 WHERE a.status = 'Active'
                   AND a.group_number IS NOT NULL
                   AND TRIM(a.group_number) <> ''
                 ORDER BY a.assigned_at DESC, a.id DESC"
            )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('RCM groups needing adviser failed: ' . $e->getMessage());
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            $flow = strtolower(trim((string) ($r['flow_status'] ?? '')));
            if ($flow !== '' && !in_array($flow, ['approved', 'ready_for_assignment'], true)) {
                continue;
            }

            $groupNumber = trim((string) ($r['group_number'] ?? ''));
            $studentId = trim((string) ($r['student_id'] ?? ''));
            if ($studentId === '') {
                $studentId = trim((string) ($r['leader_id'] ?? ''));
            }
            if ($studentId === '' && str_starts_with($groupNumber, 'STU-')) {
                $studentId = substr($groupNumber, 4);
            }
            $groupId = (int) ($r['group_id'] ?? 0);

            $hasAdviser = false;
            try {
                $chk = $pdo->prepare(
                    "SELECT adviser_name
                     FROM `crad_research_adviser_assignments`
                     WHERE (group_number = :gn OR (:sid <> '' AND student_id = :sid2) OR (:gid > 0 AND research_group_id = :gid2))
                       AND assignment_status IN ('Assigned', 'Confirmed')
                     ORDER BY (assignment_status = 'Confirmed') DESC, (assignment_status = 'Assigned') DESC, id DESC
                     LIMIT 1"
                );
                $chk->execute([
                    ':gn' => $groupNumber,
                    ':sid' => $studentId,
                    ':sid2' => $studentId,
                    ':gid' => $groupId,
                    ':gid2' => $groupId,
                ]);
                $hasAdviser = (bool) $chk->fetchColumn();
            } catch (Throwable $e) {
                error_log('RCM adviser presence check skipped: ' . $e->getMessage());
            }

            if ($hasAdviser) {
                continue;
            }

            $title = trim((string) ($r['research_title'] ?? ''));
            $out[] = [
                'coordinator_assignment_id' => (int) ($r['coordinator_assignment_id'] ?? 0),
                'group_id' => $groupId,
                'group_number' => $groupNumber,
                'group_name' => (string) ($r['group_name'] ?? ''),
                'research_title' => $title,
                'student_id' => $studentId,
                'student_name' => (string) ($r['leader_name'] ?? ''),
                'coordinator_name' => (string) ($r['coordinator_name'] ?? ''),
                'coordinator_email' => (string) ($r['coordinator_email'] ?? ''),
                'group_adviser' => (string) ($r['group_adviser'] ?? ''),
                'required_expertise' => rcmAdviserExpertiseHint($title),
                'assigned_at' => (string) ($r['assigned_at'] ?? ''),
            ];
        }

        return $out;
    }
}

/**
 * @param array<int, array{user_id:int,name:string,email:string,expertise:string}> $pool
 * @return array{user_id:int,name:string,email:string,expertise:string}|null
 */
if (!function_exists('rcmResolveAdviserSelection')) {
    function rcmResolveAdviserSelection(array $pool, ?string $value): ?array
    {
        $value = trim((string) $value);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }
        $uid = (int) $value;
        foreach ($pool as $row) {
            if ((int) ($row['user_id'] ?? 0) === $uid) {
                return $row;
            }
        }
        return null;
    }
}

/**
 * Save adviser assignment for a group that already has an Active coordinator.
 * Starts dual-confirm when both parties exist.
 *
 * @return array{ok:bool,message:string}
 */
if (!function_exists('rcmSaveAdviserAssignment')) {
    function rcmSaveAdviserAssignment(
        PDO $pdo,
        string $groupNumber,
        string $studentId,
        array $adviser,
        ?int $assignedBy
    ): array {
        if (function_exists('smsCanManageCoordinatorAssignments') && !smsCanManageCoordinatorAssignments()) {
            return ['ok' => false, 'message' => 'Only the Department Head may assign Research Coordinator and Adviser.'];
        }
        rcmAdviserEnsureSchema($pdo);
        $groupNumber = trim($groupNumber);
        $studentId = trim($studentId);
        if ($groupNumber === '' && $studentId === '') {
            return ['ok' => false, 'message' => 'Missing research group or student.'];
        }
        if ($studentId === '' && str_starts_with($groupNumber, 'STU-')) {
            $studentId = substr($groupNumber, 4);
        }

        $groupStmt = $pdo->prepare(
            "SELECT g.id, g.group_number, g.group_name, g.research_title, g.proposal_number,
                    g.leader_id, g.title_approval_id, g.flow_status, g.proposal_id
             FROM `crad_research_groups` g
             WHERE g.group_number = :gn
                OR (:sid <> '' AND g.leader_id = :sid2)
             ORDER BY (g.group_number = :gn2) DESC, g.id DESC
             LIMIT 1"
        );
        $groupStmt->execute([
            ':gn' => $groupNumber,
            ':sid' => $studentId,
            ':sid2' => $studentId,
            ':gn2' => $groupNumber,
        ]);
        $group = $groupStmt->fetch(PDO::FETCH_ASSOC);
        if (!$group) {
            return ['ok' => false, 'message' => 'Research group not found.'];
        }
        $groupNumber = trim((string) ($group['group_number'] ?? $groupNumber));
        if ($studentId === '') {
            $studentId = trim((string) ($group['leader_id'] ?? ''));
        }
        $flow = strtolower(trim((string) ($group['flow_status'] ?? '')));
        if ($flow !== '' && !in_array($flow, ['approved', 'ready_for_assignment'], true)) {
            return ['ok' => false, 'message' => 'Adviser can only be assigned after the Research Group is approved by the Department Head.'];
        }

        if (!cradGroupHasActiveCoordinator($pdo, [
            'id' => (int) ($group['id'] ?? 0),
            'research_group_id' => (int) ($group['id'] ?? 0),
            'group_number' => $groupNumber,
            'leader_id' => $studentId,
            'student_id' => $studentId,
        ])) {
            return ['ok' => false, 'message' => 'Assign a Research Coordinator first, then assign the research adviser.'];
        }

        $name = trim((string) ($adviser['name'] ?? ''));
        $email = trim((string) ($adviser['email'] ?? ''));
        $userId = (int) ($adviser['user_id'] ?? 0);
        $expertise = trim((string) ($adviser['expertise'] ?? ''));
        if ($expertise === '') {
            $expertise = rcmAdviserExpertiseHint((string) ($group['research_title'] ?? ''));
        }
        if ($name === '') {
            return ['ok' => false, 'message' => 'Please choose a valid research adviser.'];
        }

        $groupId = (int) ($group['id'] ?? 0);
        $proposalId = (int) ($group['proposal_id'] ?? 0);
        $proposalNumber = trim((string) ($group['proposal_number'] ?? ''));
        $adviserAssignmentId = 0;

        try {
            $pdo->beginTransaction();

            $pdo->prepare(
                "UPDATE `crad_research_adviser_assignments`
                    SET assignment_status = 'Pending',
                        updated_at = NOW()
                  WHERE assignment_status IN ('Assigned', 'Confirmed')
                    AND (
                            group_number = :gn
                         OR (:sid <> '' AND student_id = :sid2)
                         OR (:gid > 0 AND research_group_id = :gid2)
                    )"
            )->execute([
                ':gn' => $groupNumber,
                ':sid' => $studentId,
                ':sid2' => $studentId,
                ':gid' => $groupId,
                ':gid2' => $groupId,
            ]);

            $find = $pdo->prepare(
                "SELECT id FROM `crad_research_adviser_assignments`
                 WHERE (
                        (:uid > 0 AND adviser_user_id = :uid2)
                     OR (:email <> '' AND LOWER(TRIM(adviser_email)) = :email2)
                     OR (:name <> '' AND LOWER(TRIM(adviser_name)) = :name2)
                 )
                   AND (
                        group_number = :gn
                     OR (:sid <> '' AND student_id = :sid2)
                     OR (:gid > 0 AND research_group_id = :gid2)
                   )
                 ORDER BY id DESC
                 LIMIT 1"
            );
            $find->execute([
                ':uid' => $userId,
                ':uid2' => $userId,
                ':email' => strtolower($email),
                ':email2' => strtolower($email),
                ':name' => strtolower($name),
                ':name2' => strtolower($name),
                ':gn' => $groupNumber,
                ':sid' => $studentId,
                ':sid2' => $studentId,
                ':gid' => $groupId,
                ':gid2' => $groupId,
            ]);
            $existingId = (int) ($find->fetchColumn() ?: 0);

            if ($existingId > 0) {
                $pdo->prepare(
                    "UPDATE `crad_research_adviser_assignments`
                        SET research_group_id = COALESCE(:gid, research_group_id),
                            proposal_id = COALESCE(:pid, proposal_id),
                            proposal_number = COALESCE(NULLIF(:pnum, ''), proposal_number),
                            group_number = :gn,
                            student_id = NULLIF(:sid, ''),
                            adviser_user_id = COALESCE(:uid, adviser_user_id),
                            adviser_name = :aname,
                            adviser_email = :aemail,
                            expertise = COALESCE(NULLIF(:expertise, ''), expertise),
                            availability_status = 'Available',
                            assignment_status = 'Assigned',
                            assigned_by = :by,
                            assigned_at = NOW(),
                            updated_at = NOW()
                      WHERE id = :id
                      LIMIT 1"
                )->execute([
                    ':gid' => $groupId > 0 ? $groupId : null,
                    ':pid' => $proposalId > 0 ? $proposalId : null,
                    ':pnum' => $proposalNumber,
                    ':gn' => $groupNumber,
                    ':sid' => $studentId,
                    ':uid' => $userId > 0 ? $userId : null,
                    ':aname' => $name,
                    ':aemail' => $email,
                    ':expertise' => $expertise,
                    ':by' => $assignedBy,
                    ':id' => $existingId,
                ]);
                $adviserAssignmentId = $existingId;
            } else {
                $pdo->prepare(
                    "INSERT INTO `crad_research_adviser_assignments`
                        (research_group_id, proposal_id, proposal_number, group_number, student_id,
                         adviser_user_id, adviser_name, adviser_email, expertise,
                         availability_status, assignment_status, notes, assigned_by, assigned_at)
                     VALUES
                        (:gid, :pid, NULLIF(:pnum, ''), :gn, NULLIF(:sid, ''),
                         :uid, :aname, :aemail, :expertise,
                         'Available', 'Assigned', 'Assigned from Research Coordinator Management', :by, NOW())"
                )->execute([
                    ':gid' => $groupId > 0 ? $groupId : null,
                    ':pid' => $proposalId > 0 ? $proposalId : null,
                    ':pnum' => $proposalNumber,
                    ':gn' => $groupNumber,
                    ':sid' => $studentId,
                    ':uid' => $userId > 0 ? $userId : null,
                    ':aname' => $name,
                    ':aemail' => $email,
                    ':expertise' => $expertise,
                    ':by' => $assignedBy,
                ]);
                $adviserAssignmentId = (int) $pdo->lastInsertId();
            }

            if ($groupId > 0) {
                try {
                    $pdo->prepare(
                        "UPDATE `crad_research_groups`
                            SET adviser = :aname, updated_at = NOW()
                          WHERE id = :id
                          LIMIT 1"
                    )->execute([':aname' => $name, ':id' => $groupId]);
                } catch (Throwable $e) {
                    error_log('RCM group adviser stamp skipped: ' . $e->getMessage());
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('RCM adviser assign failed: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Adviser assignment failed: ' . $e->getMessage()];
        }

        if ($studentId !== '' && function_exists('cradSyncTitleApprovalAssigneeNames')) {
            cradSyncTitleApprovalAssigneeNames($pdo, $studentId);
        }

        try {
            if (function_exists('cradRgFlowEnsureSchema') && function_exists('cradRgFlowStartAssignmentCycle')) {
                cradRgFlowEnsureSchema($pdo);
                $coordIdStmt = $pdo->prepare(
                    "SELECT id FROM `crad_research_coordinator_assignments`
                     WHERE status = 'Active' AND (group_number = :gn OR student_id = :sid)
                     ORDER BY id DESC LIMIT 1"
                );
                $coordIdStmt->execute([':gn' => $groupNumber, ':sid' => $studentId]);
                $coordId = (int) ($coordIdStmt->fetchColumn() ?: 0);
                // Start/extend pending confirmation as soon as adviser is assigned
                // (even before coordinator). Coordinator attach later extends the same cycle.
                if ($adviserAssignmentId > 0) {
                    cradRgFlowStartAssignmentCycle(
                        $pdo,
                        $groupId,
                        $groupNumber,
                        $studentId,
                        $coordId > 0 ? $coordId : null,
                        $adviserAssignmentId,
                        (int) ($assignedBy ?? 0)
                    );
                }
            }
        } catch (Throwable $e) {
            error_log('RCM adviser dual-confirm start skipped: ' . $e->getMessage());
        }

        if (function_exists('logActivity')) {
            logActivity('assign', 'Assigned research adviser "' . $name . '" to research group ' . $groupNumber, 'crad');
        }

        return [
            'ok' => true,
            'message' => 'Research adviser assigned to ' . $groupNumber . '. Status: Pending Confirmation (Assigned by Department Head). Fully Assigned only after both Approve Assignment.',
        ];
    }
}

if (!function_exists('rcmCurrentAdviserLabel')) {
    function rcmCurrentAdviserLabel(PDO $pdo, string $groupNumber, string $studentId = '', int $groupId = 0): string
    {
        try {
            $stmt = $pdo->prepare(
                "SELECT adviser_name
                 FROM `crad_research_adviser_assignments`
                 WHERE (group_number = :gn OR (:sid <> '' AND student_id = :sid2) OR (:gid > 0 AND research_group_id = :gid2))
                   AND assignment_status IN ('Assigned', 'Confirmed')
                 ORDER BY (assignment_status = 'Confirmed') DESC, id DESC
                 LIMIT 1"
            );
            $stmt->execute([
                ':gn' => $groupNumber,
                ':sid' => $studentId,
                ':sid2' => $studentId,
                ':gid' => $groupId,
                ':gid2' => $groupId,
            ]);
            return trim((string) ($stmt->fetchColumn() ?: ''));
        } catch (Throwable $e) {
            return '';
        }
    }
}