<?php
/**
 * SMS 2 - Research Coordinator Management
 * Module: CRAD
 *
 * Admin assigns a Research Coordinator from the Coordinator Roster to a
 * student first. Adviser assignment happens next. Only then do both names
 * appear on the student's Title Approval Form.
 *
 * Records live in the `research_coordinator_assignments` table. The page
 * refreshes in real time (5s polling) via the `ajax=coordinator-assignments`
 * endpoint so the CRAD Officer always sees the latest eligible groups and
 * assignments without reloading.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once __DIR__ . '/../includes/title-approval-assignees.php';

requireAuth();

$roleKey = getCurrentUserRoleKey();
if (
    !smsIsGrantedAdminRole($roleKey)
    && $roleKey !== 'department_head'
    && !smsRoleAllowedForModule(['crad_officer'], 'crad')
) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$pageTitle    = 'Research Coordinator Management';
$activeModule = 'crad';
$activePage   = 'research-coordinator-management';
$rcmNavLabel  = $roleKey === 'department_head' ? 'Research Management' : 'CRAD';
$rcmNavUrl    = $roleKey === 'department_head'
    ? BASE_URL . '/modules/crad/pages/research-coordinator-management.php'
    : BASE_URL . '/modules/crad/index.php';
$breadcrumbs  = [
    ['label' => $rcmNavLabel, 'url' => $rcmNavUrl],
    ['label' => 'Research Coordinator Management', 'url' => null],
];
$pageBannerIcon        = 'fa-user-tie';
$pageBannerDescription = 'Assign a Research Coordinator from the Coordinator Roster to each student first. After that, assign a research adviser. Names appear on the Title Approval Form only once both assignments are saved.';

require_once __DIR__ . '/../../../includes/breadcrumbs.php';

$pdo = getCradDatabaseConnection();

/**
 * Create / update the schema backing coordinator assignments.
 */
function rcmEnsureSchema(PDO $pdo): void
{
    cradEnsureAssigneeSchema($pdo);

    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'research_groups'")->fetch();
        if ($exists) {
            $pdo->exec("ALTER TABLE research_groups CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    } catch (Throwable $e) {
        // ignore conversion errors
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS research_coordinator_assignments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        research_group_id INT UNSIGNED NULL,
        proposal_id INT UNSIGNED NULL,
        title_approval_id INT UNSIGNED NULL,
        proposal_number VARCHAR(30) NULL,
        group_number VARCHAR(40) NULL,
        group_name VARCHAR(120) NOT NULL DEFAULT '',
        research_title VARCHAR(255) NOT NULL DEFAULT '',
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

    try {
        $col = $pdo->query("SHOW COLUMNS FROM research_coordinator_assignments LIKE 'group_number'")->fetch();
        if ($col && strtoupper((string) ($col['Null'] ?? 'YES')) === 'NO') {
            $pdo->exec("ALTER TABLE research_coordinator_assignments MODIFY group_number VARCHAR(40) DEFAULT NULL");
        }
    } catch (Throwable $e) {
        error_log('Coordinator assignment group_number nullable check skipped: ' . $e->getMessage());
    }

    try {
        cradEnsureTitleApprovalAdviserAssignmentConsistency($pdo);
    } catch (Throwable $e) {
        error_log('Coordinator title-approval cascade ensure skipped: ' . $e->getMessage());
    }
    cradPruneDeletedTitleApprovalDependents($pdo);
}

/**
 * SQL fragment that only matches fully approved Title Approval Forms.
 * Reuses the same gate as Proposal Review / Approved Research.
 */
function rcmFullyApprovedClause(string $alias = 't'): string
{
    return "{$alias}.status = 'Approved'
        AND {$alias}.coordinator_status = 'Approved'
        AND {$alias}.crad_status = 'Approved'
        AND {$alias}.adviser_signature_data IS NOT NULL AND {$alias}.adviser_signature_data <> ''
        AND {$alias}.coordinator_signature_data IS NOT NULL AND {$alias}.coordinator_signature_data <> ''
        AND {$alias}.crad_signature_data IS NOT NULL AND {$alias}.crad_signature_data <> ''";
}

/**
 * Active students who still need a Research Coordinator from the roster.
 *
 * @return array<int, array<string, mixed>>
 */
function rcmEligibleStudents(PDO $pdo): array
{
    $main = db();
    if (!$main) {
        return [];
    }

    try {
        $students = $main->query(
            "SELECT student_id, full_name, email
             FROM users
             WHERE role_key = 'student'
               AND status = 'active'
               AND student_id IS NOT NULL
               AND TRIM(student_id) <> ''
             ORDER BY full_name ASC, student_id ASC"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Eligible student list failed: ' . $e->getMessage());
        return [];
    }

    $assigned = [];
    try {
        foreach ($pdo->query("SELECT student_id, group_number FROM research_coordinator_assignments WHERE status = 'Active'")->fetchAll() as $a) {
            $sid = trim((string) ($a['student_id'] ?? ''));
            if ($sid !== '') {
                $assigned[$sid] = true;
            }
            $gn = trim((string) ($a['group_number'] ?? ''));
            if (str_starts_with($gn, 'STU-')) {
                $assigned[substr($gn, 4)] = true;
            }
        }
    } catch (Throwable $e) {
        error_log('Active coordinator student index failed: ' . $e->getMessage());
    }

    $titles = [];
    try {
        foreach ($pdo->query("SELECT student_id, proposed_title, department FROM title_approvals ORDER BY id DESC")->fetchAll() as $row) {
            $sid = trim((string) ($row['student_id'] ?? ''));
            if ($sid !== '' && !isset($titles[$sid])) {
                $titles[$sid] = $row;
            }
        }
    } catch (Throwable $e) {
        error_log('Student title lookup failed: ' . $e->getMessage());
    }

    $out = [];
    foreach ($students as $student) {
        $sid = trim((string) ($student['student_id'] ?? ''));
        if ($sid === '' || isset($assigned[$sid])) {
            continue;
        }
        $title = trim((string) ($titles[$sid]['proposed_title'] ?? ''));
        $out[] = [
            'group_id' => 0,
            'group_number' => cradStudentAssignmentGroupNumber($sid),
            'group_name' => (string) ($student['full_name'] ?? ''),
            'research_title' => $title !== '' ? $title : 'Pending Title Approval',
            'adviser' => '',
            'proposal_number' => $sid,
            'tap_proposal_number' => '',
            'suggested_coordinator' => '',
            'student_id' => $sid,
            'student_name' => (string) ($student['full_name'] ?? ''),
            'assign_kind' => 'student',
        ];
    }

    return $out;
}

/**
 * Deduplicated coordinator pool.
 * Prefers user accounts with the Research Coordinator role, then falls back
 * to coordinator names recorded on approved Title Approval Forms.
 *
 * @return array<int, array{name:string,email:string,user_id:int,active:bool,source:string}>
 */
function rcmCoordinatorPool(PDO $pdo): array
{
    $pool = [];
    $seenNames = []; // name-based keys so a user account wins over the same name on a Title Approval

    $mainDb = db();
    $users  = [];
    if ($mainDb) {
        try {
            $users = $mainDb->query(
                "SELECT id, full_name, email, status
                 FROM users
                 WHERE role_key = 'research_coordinator'
                 ORDER BY full_name ASC"
            )->fetchAll();
        } catch (Throwable $e) {
            $users = [];
        }
    }

    foreach ($users as $u) {
        $name = trim((string) ($u['full_name'] ?? ''));
        $email = trim((string) ($u['email'] ?? ''));
        if ($name === '') {
            continue;
        }
        $nameKey = 'n:' . mb_strtolower($name);
        if (isset($seenNames[$nameKey])) {
            continue;
        }
        $seenNames[$nameKey] = true;
        $pool[] = [
            'name'    => $name,
            'email'   => $email,
            'user_id' => (int) ($u['id'] ?? 0),
            'active'  => strtolower((string) ($u['status'] ?? '')) === 'active',
            'source'  => 'user',
        ];
    }

    try {
        $approvals = $pdo->query(
            "SELECT DISTINCT coordinator_name
             FROM title_approvals
             WHERE coordinator_status = 'Approved'
               AND coordinator_name IS NOT NULL AND TRIM(coordinator_name) <> ''
             ORDER BY coordinator_name ASC"
        )->fetchAll();
    } catch (Throwable $e) {
        $approvals = [];
    }

    foreach ($approvals as $a) {
        $name = trim((string) ($a['coordinator_name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $nameKey = 'n:' . mb_strtolower($name);
        if (isset($seenNames[$nameKey])) {
            continue;
        }
        $seenNames[$nameKey] = true;
        $pool[] = [
            'name'    => $name,
            'email'   => '',
            'user_id' => 0,
            'active'  => true,
            'source'  => 'title_approval',
        ];
    }

    return $pool;
}

/**
 * Research groups that are fully approved but do not yet have an Active
 * coordinator assignment. The Title Approval coordinator is suggested.
 *
 * @return array<int, array<string, mixed>>
 */
function rcmEligibleGroups(PDO $pdo): array
{
    $sql = "SELECT g.id AS group_id, g.group_number, g.group_name, g.research_title,
                   g.adviser, g.proposal_number, g.leader_id,
                   t.proposal_number AS tap_proposal_number,
                   t.coordinator_name AS suggested_coordinator
            FROM research_groups g
            JOIN title_approvals t ON t.id = g.title_approval_id
            WHERE g.title_approval_id IS NOT NULL
              AND " . rcmFullyApprovedClause('t') . "
            ORDER BY g.group_number ASC";

    $rows = $pdo->query($sql)->fetchAll();

    $activeGroups = [];
    $assignedLeaders = [];
    foreach ($pdo->query("SELECT student_id, group_number FROM research_coordinator_assignments WHERE status = 'Active'")->fetchAll() as $a) {
        $gn = trim((string) ($a['group_number'] ?? ''));
        if ($gn !== '') {
            $activeGroups[$gn] = true;
        }
        $sid = trim((string) ($a['student_id'] ?? ''));
        if ($sid !== '') {
            $assignedLeaders[$sid] = true;
        }
        if (str_starts_with($gn, 'STU-')) {
            $assignedLeaders[substr($gn, 4)] = true;
        }
    }

    $eligible = [];
    $listedLeaders = [];
    foreach ($rows as $r) {
        if (isset($activeGroups[$r['group_number']])) {
            continue;
        }
        $leaderId = trim((string) ($r['leader_id'] ?? ''));
        if ($leaderId !== '' && isset($assignedLeaders[$leaderId])) {
            continue;
        }
        $r['assign_kind'] = 'group';
        $r['student_id'] = $leaderId;
        $eligible[] = $r;
        if ($leaderId !== '') {
            $listedLeaders[$leaderId] = true;
        }
    }

    foreach (rcmEligibleStudents($pdo) as $studentRow) {
        $sid = trim((string) ($studentRow['student_id'] ?? ''));
        if ($sid !== '' && isset($listedLeaders[$sid])) {
            continue;
        }
        $eligible[] = $studentRow;
    }

    return $eligible;
}

/**
 * Coordinator assignments joined with the current research group / title
 * approval data.
 *
 * @return array<int, array<string, mixed>>
 */
function rcmAssignments(PDO $pdo): array
{
    $rows = $pdo->query(
        "SELECT a.*, g.group_name AS current_group_name, g.research_title AS current_title,
                g.adviser AS current_adviser,
                t.coordinator_name AS approval_coordinator
         FROM research_coordinator_assignments a
         LEFT JOIN research_groups g ON g.id = a.research_group_id
         LEFT JOIN title_approvals t ON t.id = a.title_approval_id
         WHERE a.group_number IS NOT NULL AND a.group_number <> ''
         ORDER BY a.updated_at DESC, a.id DESC"
    )->fetchAll();

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'                  => (int) $r['id'],
            'research_group_id'   => $r['research_group_id'] !== null ? (int) $r['research_group_id'] : null,
            'title_approval_id'   => $r['title_approval_id'] !== null ? (int) $r['title_approval_id'] : null,
            'proposal_number'     => (string) ($r['proposal_number'] ?? ''),
            'group_number'        => (string) $r['group_number'],
            'group_name'          => (string) ($r['current_group_name'] !== null && $r['current_group_name'] !== '' ? $r['current_group_name'] : $r['group_name']),
            'research_title'      => (string) ($r['current_title'] !== null && $r['current_title'] !== '' ? $r['current_title'] : $r['research_title']),
            'adviser'             => (string) ($r['current_adviser'] ?? ''),
            'coordinator_user_id' => $r['coordinator_user_id'] !== null ? (int) $r['coordinator_user_id'] : null,
            'coordinator_name'    => (string) ($r['coordinator_name'] ?? ''),
            'coordinator_email'   => (string) ($r['coordinator_email'] ?? ''),
            'status'              => (string) ($r['status'] ?? 'Active'),
            'assigned_at'         => (string) ($r['assigned_at'] ?? ''),
            'updated_at'          => (string) ($r['updated_at'] ?? ''),
            'approval_coordinator'=> (string) ($r['approval_coordinator'] ?? ''),
        ];
    }

    return $out;
}

/**
 * Coordinator roster: one row per coordinator with account status,
 * active / inactive assignment counts and the groups they handle.
 *
 * @return array<int, array<string, mixed>>
 */
function rcmRoster(PDO $pdo): array
{
    $pool = rcmCoordinatorPool($pdo);
    $assignments = rcmAssignments($pdo);

    $byCoordinator = [];
    foreach ($pool as $c) {
        $byCoordinator[$c['name']] = [
            'name'           => $c['name'],
            'email'          => $c['email'],
            'user_id'        => $c['user_id'],
            'user_active'    => $c['active'],
            'active'         => $c['active'],
            'active_count'   => 0,
            'inactive_count' => 0,
            'groups'         => [],
            'last_assigned'  => null,
        ];
    }

    foreach ($assignments as $a) {
        $name = $a['coordinator_name'];
        if (!isset($byCoordinator[$name])) {
            $byCoordinator[$name] = [
                'name'           => $name,
                'email'          => $a['coordinator_email'],
                'user_id'        => $a['coordinator_user_id'] ?? 0,
                'user_active'    => null,
                'active'         => false,
                'active_count'   => 0,
                'inactive_count' => 0,
                'groups'         => [],
                'last_assigned'  => null,
            ];
        }

        $entry = &$byCoordinator[$name];
        if ($a['status'] === 'Active') {
            $entry['active_count']++;
            $entry['groups'][] = $a['group_number'];
        } else {
            $entry['inactive_count']++;
        }
        if ($a['assigned_at'] !== '' && ($entry['last_assigned'] === null || $a['assigned_at'] > $entry['last_assigned'])) {
            $entry['last_assigned'] = $a['assigned_at'];
        }
        unset($entry);
    }

    // Coordinators without a linked user account are Active when they handle
    // at least one active assignment, otherwise Inactive.
    foreach ($byCoordinator as $name => &$entry) {
        if ($entry['user_active'] === null) {
            $entry['active'] = $entry['active_count'] > 0;
        } else {
            $entry['active'] = $entry['user_active'];
        }
    }
    unset($entry);

    $byName = [];
    foreach ($byCoordinator as $entry) {
        $byName[] = $entry;
    }
    usort($byName, static fn(array $a, array $b): int => strcasecmp((string) $a['name'], (string) $b['name']));

    return $byName;
}

function rcmResolveGroupMembers(PDO $pdo, array $group): array
{
    $roster = [];

    if ((int) ($group['proposal_id'] ?? 0) > 0) {
        try {
            $stmt = $pdo->prepare(
                "SELECT sort_order, student_id, student_name, email, contact
                 FROM proposal_members
                 WHERE proposal_id = ?
                 ORDER BY sort_order ASC, id ASC"
            );
            $stmt->execute([(int) $group['proposal_id']]);
            foreach ($stmt->fetchAll() ?: [] as $member) {
                $name = trim((string) ($member['student_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $roster[] = [
                    'sort_order'   => (int) ($member['sort_order'] ?? 1),
                    'student_id'   => trim((string) ($member['student_id'] ?? '')),
                    'student_name' => $name,
                    'email'        => trim((string) ($member['email'] ?? '')),
                    'contact'      => trim((string) ($member['contact'] ?? '')),
                ];
            }
        } catch (Throwable $e) {
            error_log('Research coordinator management proposal member lookup failed: ' . $e->getMessage());
            $roster = [];
        }
    }

    if ($roster === []) {
        $json = trim((string) ($group['members_json'] ?? ''));
        if ($json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $order = 1;
                foreach ($decoded as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $name = trim((string) ($entry[0] ?? ($entry['name'] ?? '')));
                    if ($name === '') {
                        continue;
                    }
                    $studentId = trim((string) ($entry[2] ?? ($entry['student_id'] ?? ($entry[1] ?? ''))));
                    $roster[] = [
                        'sort_order'   => $order++,
                        'student_id'   => $studentId,
                        'student_name' => $name,
                        'email'        => trim((string) ($entry['email'] ?? '')),
                        'contact'      => trim((string) ($entry['contact'] ?? '')),
                    ];
                }
            }
        }
    }

    $leaderName = trim((string) ($group['leader_name'] ?? ''));
    $leaderId = trim((string) ($group['leader_id'] ?? ''));
    if ($leaderName === '' && $roster !== []) {
        $leaderName = (string) ($roster[0]['student_name'] ?? '');
        $leaderId = (string) ($roster[0]['student_id'] ?? '');
    }

    $leaderKey = function_exists('mb_strtolower')
        ? mb_strtolower($leaderName)
        : strtolower($leaderName);
    $members = [];
    foreach ($roster as $entry) {
        $name = trim((string) ($entry['student_name'] ?? ''));
        $nameKey = function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name);
        if ($name === '' || ($leaderName !== '' && $nameKey === $leaderKey)) {
            continue;
        }
        $members[] = $entry;
    }

    return [
        'leader' => [
            'sort_order' => 0,
            'student_id' => $leaderId,
            'student_name' => $leaderName,
            'email' => trim((string) ($group['leader_email'] ?? '')),
            'contact' => trim((string) ($group['leader_contact'] ?? '')),
        ],
        'members' => $members,
    ];
}

/**
 * Rich read-only details for the View Details modal.
 * Returns the research group record, its members from the official registry data,
 * and the full coordinator assignment history for that group.
 *
 * @return array<string, mixed>
 */
function rcmGroupDetails(PDO $pdo, string $groupNumber): array
{
    $stmt = $pdo->prepare(
        "SELECT g.*, t.coordinator_name AS approval_coordinator, t.members_json
         FROM research_groups g
         LEFT JOIN title_approvals t ON t.id = g.title_approval_id
         WHERE g.group_number = ?
         LIMIT 1"
    );
    $stmt->execute([$groupNumber]);
    $g = $stmt->fetch();
    if (!$g) {
        return ['ok' => false, 'message' => 'Research group not found.'];
    }

    $memberData = rcmResolveGroupMembers($pdo, $g);

    $as = $pdo->prepare(
        "SELECT status, coordinator_name, coordinator_email, assigned_at, updated_at
         FROM research_coordinator_assignments
         WHERE group_number = ?
         ORDER BY updated_at DESC, id DESC"
    );
    $as->execute([$groupNumber]);
    $assignments = $as->fetchAll();

    return [
        'ok' => true,
        'detail' => [
            'type'                 => 'group',
            'group_number'         => (string) $g['group_number'],
            'group_name'           => (string) $g['group_name'],
            'research_title'       => (string) $g['research_title'],
            'proposal_number'      => (string) ($g['proposal_number'] ?? ''),
            'college_dept'         => (string) ($g['college_dept'] ?? ''),
            'academic_year'        => (string) ($g['academic_year'] ?? ''),
            'adviser'              => (string) ($g['adviser'] ?? ''),
            'leader_name'          => (string) ($g['leader_name'] ?? ''),
            'leader_email'         => (string) ($g['leader_email'] ?? ''),
            'leader_contact'       => (string) ($g['leader_contact'] ?? ''),
            'date_assigned'        => (string) ($g['date_assigned'] ?? ''),
            'approval_coordinator' => (string) ($g['approval_coordinator'] ?? ''),
            'leader'               => $memberData['leader'],
            'members'              => array_map(static fn(array $m): array => [
                'sort_order'   => (int) $m['sort_order'],
                'student_id'   => (string) $m['student_id'],
                'student_name' => (string) $m['student_name'],
                'email'        => (string) $m['email'],
                'contact'      => (string) $m['contact'],
            ], $memberData['members']),
            'assignments' => array_map(static fn(array $a): array => [
                'status'             => (string) $a['status'],
                'coordinator_name'   => (string) $a['coordinator_name'],
                'coordinator_email'  => (string) $a['coordinator_email'],
                'assigned_at'        => (string) ($a['assigned_at'] ?? ''),
                'updated_at'         => (string) ($a['updated_at'] ?? ''),
            ], $assignments),
        ],
    ];
}

/**
 * Read-only coordinator profile for the View Details modal.
 */
function rcmCoordinatorDetails(PDO $pdo, string $key): array
{
    $pool = rcmCoordinatorPool($pdo);
    $match = null;
    foreach ($pool as $c) {
        if ($key !== '' && (string) $c['user_id'] === $key) {
            $match = $c;
            break;
        }
        if ($key !== '' && strcasecmp($c['name'], $key) === 0) {
            $match = $c;
            break;
        }
    }
    if ($match === null) {
        return ['ok' => false, 'message' => 'Coordinator not found.'];
    }

    $theirs = array_values(array_filter(
        rcmAssignments($pdo),
        static fn(array $a): bool => strcasecmp((string) $a['coordinator_name'], (string) $match['name']) === 0
    ));

    return [
        'ok' => true,
        'detail' => [
            'type'         => 'coordinator',
            'name'         => $match['name'],
            'email'        => $match['email'],
            'user_id'      => $match['user_id'],
            'active'       => (bool) $match['active'],
            'source'       => $match['source'],
            'assignments'  => $theirs,
        ],
    ];
}

rcmEnsureSchema($pdo);

function rcmPayload(PDO $pdo, ?string $flashMessage = null, bool $flashOk = true): array
{
    cradPruneDeletedTitleApprovalDependents($pdo);
    $eligible    = rcmEligibleGroups($pdo);
    $pool        = rcmCoordinatorPool($pdo);
    $assignments = rcmAssignments($pdo);
    $roster      = rcmRoster($pdo);

    $activeAssignments = 0;
    $activeCoordinators = 0;
    foreach ($assignments as $a) {
        if ($a['status'] === 'Active') {
            $activeAssignments++;
        }
    }
    foreach ($roster as $r) {
        if (!empty($r['active'])) {
            $activeCoordinators++;
        }
    }

    return [
        'ok'            => $flashOk,
        'message'       => $flashMessage,
        'stats'         => [
            'total_coordinators'  => count($roster),
            'active_coordinators' => $activeCoordinators,
            'assigned_groups'     => $activeAssignments,
            'pending_groups'      => count($eligible),
        ],
        'eligible'      => $eligible,
        'pool'          => $pool,
        'assignments'   => $assignments,
        'roster'        => $roster,
        'server_time'   => date('Y-m-d H:i:s'),
    ];
}

/**
 * Resolve a coordinator from the select option value. Options are the user
 * id when the coordinator is a Research Coordinator account, otherwise the
 * "name:<coordinator name>" form.
 */
function rcmResolveCoordinatorSelection(array $pool, ?string $value): ?array
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    if (str_starts_with($value, 'name:')) {
        $name = trim(substr($value, 5));
        foreach ($pool as $c) {
            if (strcasecmp($c['name'], $name) === 0) {
                return $c;
            }
        }
        return null;
    }
    if (ctype_digit($value)) {
        $userId = (int) $value;
        foreach ($pool as $c) {
            if ($c['user_id'] === $userId) {
                return $c;
            }
        }
    }
    return null;
}

// ---------------------------------------------------------------------------
// AJAX endpoints
// ---------------------------------------------------------------------------

$ajax = $_REQUEST['ajax'] ?? null;

if ($ajax === 'coordinator-assignments') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(rcmPayload($pdo));
    exit;
}

if ($ajax === 'assign') {
    header('Content-Type: application/json; charset=utf-8');
    $token = (string) ($_POST['_token'] ?? '');
    if (!csrfVerify($token)) {
        echo json_encode(['ok' => false, 'message' => 'Security token expired. Refresh the page and try again.']);
        exit;
    }

    $groupNumber = trim((string) ($_POST['group_number'] ?? ''));
    $studentId   = trim((string) ($_POST['student_id'] ?? ''));
    $selection   = rcmResolveCoordinatorSelection(rcmCoordinatorPool($pdo), (string) ($_POST['coordinator'] ?? ''));

    if ($studentId === '' && str_starts_with($groupNumber, 'STU-')) {
        $studentId = substr($groupNumber, 4);
    }

    if ($groupNumber === '' && $studentId === '') {
        echo json_encode(['ok' => false, 'message' => 'Missing research group or student.']);
        exit;
    }
    if ($selection === null) {
        echo json_encode(['ok' => false, 'message' => 'Please choose a valid Research Coordinator from the roster.']);
        exit;
    }

    $group = null;
    if ($studentId !== '' || str_starts_with($groupNumber, 'STU-')) {
        $studentName = '';
        $title = '';
        $department = '';
        $main = db();
        if ($main && $studentId !== '') {
            $u = $main->prepare("SELECT full_name FROM users WHERE student_id = :sid LIMIT 1");
            $u->execute([':sid' => $studentId]);
            $studentName = trim((string) $u->fetchColumn());
        }
        try {
            $t = $pdo->prepare("SELECT proposed_title, department FROM title_approvals WHERE student_id = :sid ORDER BY id DESC LIMIT 1");
            $t->execute([':sid' => $studentId]);
            $titleRow = $t->fetch(PDO::FETCH_ASSOC) ?: [];
            $title = trim((string) ($titleRow['proposed_title'] ?? ''));
            $department = trim((string) ($titleRow['department'] ?? ''));
        } catch (Throwable) {
        }
        $placeholder = cradEnsureStudentPlaceholderGroup($pdo, $studentId, $studentName, $title, $department);
        $group = [
            'group_id' => (int) ($placeholder['id'] ?? 0),
            'group_number' => (string) ($placeholder['group_number'] ?? $groupNumber),
            'group_name' => (string) ($placeholder['group_name'] ?? $studentName),
            'research_title' => (string) ($placeholder['research_title'] ?? $title),
            'proposal_number' => $studentId,
            'title_approval_id' => $placeholder['title_approval_id'] ?? null,
            'tap_proposal_number' => '',
            'leader_id' => $studentId,
        ];
        $groupNumber = (string) $group['group_number'];
    } else {
        $stmt = $pdo->prepare(
            "SELECT g.id AS group_id, g.group_number, g.group_name, g.research_title,
                    g.proposal_number, g.title_approval_id, g.leader_id,
                    t.proposal_number AS tap_proposal_number
             FROM research_groups g
             LEFT JOIN title_approvals t ON t.id = g.title_approval_id
             WHERE g.group_number = ?
             LIMIT 1"
        );
        $stmt->execute([$groupNumber]);
        $group = $stmt->fetch();
        if ($group && $studentId === '') {
            $studentId = trim((string) ($group['leader_id'] ?? ''));
        }
    }

    if (!$group) {
        echo json_encode(['ok' => false, 'message' => 'This student or research group is not available for coordinator assignment.']);
        exit;
    }

    $proposalNumber = (string) ($group['proposal_number'] !== null && $group['proposal_number'] !== '' ? $group['proposal_number'] : ($group['tap_proposal_number'] ?? ''));

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO research_coordinator_assignments
                (research_group_id, title_approval_id, proposal_number, group_number, group_name, research_title,
                 student_id, coordinator_user_id, coordinator_name, coordinator_email, status, assigned_by, assigned_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, NOW())
             ON DUPLICATE KEY UPDATE
                research_group_id = VALUES(research_group_id),
                title_approval_id = VALUES(title_approval_id),
                proposal_number = VALUES(proposal_number),
                group_name = VALUES(group_name),
                research_title = VALUES(research_title),
                student_id = VALUES(student_id),
                coordinator_user_id = VALUES(coordinator_user_id),
                coordinator_name = VALUES(coordinator_name),
                coordinator_email = VALUES(coordinator_email),
                status = 'Active',
                assigned_by = VALUES(assigned_by),
                assigned_at = NOW()"
        );
        $stmt->execute([
            (int) $group['group_id'],
            $group['title_approval_id'] !== null && (int) $group['title_approval_id'] > 0 ? (int) $group['title_approval_id'] : null,
            $proposalNumber !== '' ? $proposalNumber : null,
            $groupNumber,
            (string) $group['group_name'],
            (string) $group['research_title'],
            $studentId !== '' ? $studentId : null,
            $selection['user_id'] > 0 ? $selection['user_id'] : null,
            $selection['name'],
            $selection['email'],
            (int) ($_SESSION['user_id'] ?? 0) ?: null,
        ]);
        $pdo->commit();

        if ($studentId !== '') {
            cradSyncTitleApprovalAssigneeNames($pdo, $studentId);
        }

        if (function_exists('logActivity')) {
            logActivity('assign', 'Assigned coordinator "' . $selection['name'] . '" to ' . ($studentId !== '' ? 'student ' . $studentId : 'research group ' . $groupNumber), 'crad');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['ok' => false, 'message' => 'Assignment failed: ' . $e->getMessage()]);
        exit;
    }

    echo json_encode(rcmPayload($pdo, 'Coordinator assigned' . ($studentId !== '' ? ' to student ' . $studentId : ' to ' . $groupNumber) . '.'));
    exit;
}

if ($ajax === 'set-status') {
    header('Content-Type: application/json; charset=utf-8');
    $token = (string) ($_POST['_token'] ?? '');
    if (!csrfVerify($token)) {
        echo json_encode(['ok' => false, 'message' => 'Security token expired. Refresh the page and try again.']);
        exit;
    }

    $id     = (int) ($_POST['id'] ?? 0);
    $status = ($_POST['status'] ?? '') === 'Inactive' ? 'Inactive' : 'Active';

    if ($id <= 0) {
        echo json_encode(['ok' => false, 'message' => 'Invalid assignment.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT group_number, coordinator_name FROM research_coordinator_assignments WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        echo json_encode(['ok' => false, 'message' => 'Assignment not found.']);
        exit;
    }

    $pdo->prepare("UPDATE research_coordinator_assignments SET status = ? WHERE id = ?")->execute([$status, $id]);

    $sidStmt = $pdo->prepare("SELECT student_id, group_number FROM research_coordinator_assignments WHERE id = ?");
    $sidStmt->execute([$id]);
    $sidRow = $sidStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $studentId = trim((string) ($sidRow['student_id'] ?? ''));
    if ($studentId === '') {
        $gn = trim((string) ($sidRow['group_number'] ?? ''));
        $studentId = cradStudentIdFromAssignmentGroup($gn);
    }
    if ($studentId !== '') {
        cradSyncTitleApprovalAssigneeNames($pdo, $studentId);
    }

    if (function_exists('logActivity')) {
        logActivity($status === 'Active' ? 'activate' : 'deactivate', 'Coordinator "' . $row['coordinator_name'] . '" assignment for group ' . $row['group_number'] . ' set to ' . $status, 'crad');
    }

    $message = $status === 'Active'
        ? 'Assignment activated for group ' . $row['group_number'] . '.'
        : 'Assignment deactivated for group ' . $row['group_number'] . '.';
    echo json_encode(rcmPayload($pdo, $message));
    exit;
}

if ($ajax === 'details') {
    header('Content-Type: application/json; charset=utf-8');
    $type = (string) ($_GET['type'] ?? ($_POST['type'] ?? 'assignment'));
    $id   = trim((string) ($_GET['id'] ?? ($_POST['id'] ?? '')));

    if ($id === '') {
        echo json_encode(['ok' => false, 'message' => 'Missing record identifier.']);
        exit;
    }

    if ($type === 'group') {
        echo json_encode(rcmGroupDetails($pdo, $id));
        exit;
    }

    if ($type === 'assignment') {
        $stmt = $pdo->prepare("SELECT group_number FROM research_coordinator_assignments WHERE id = ?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch();
        if (!$row) {
            echo json_encode(['ok' => false, 'message' => 'Assignment not found.']);
            exit;
        }
        echo json_encode(rcmGroupDetails($pdo, (string) $row['group_number']));
        exit;
    }

    if ($type === 'coordinator') {
        echo json_encode(rcmCoordinatorDetails($pdo, $id));
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Invalid details request.']);
    exit;
}

// ---------------------------------------------------------------------------
// Full page render
// ---------------------------------------------------------------------------

require_once ROOT_PATH . '/includes/layout-start.php';
$payload = rcmPayload($pdo);
$stats   = $payload['stats'];
$eligible = $payload['eligible'];
$pool     = $payload['pool'];
$assignments = $payload['assignments'];
$roster   = $payload['roster'];
$csrf     = csrfToken();
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if (!empty($payload['message'])): ?>
<div class="rcm-alert rcm-alert-<?= $payload['ok'] ? 'success' : 'danger' ?>" role="alert">
    <?= smsIcon($payload['ok'] ? 'check-circle' : 'exclamation-circle') ?>
    <span><?= htmlspecialchars($payload['message']) ?></span>
</div>
<?php endif; ?>

<style>
.rcm-wrap { display: flex; flex-direction: column; gap: 1.25rem; }
.rcm-alert {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.85rem 1.1rem; margin-bottom: 1rem;
    border-radius: 12px; font-size: 0.88rem; font-weight: 600;
}
.rcm-alert-danger { border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; }
.rcm-alert-success { border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; }
.rcm-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.42rem;
    min-height: 38px; padding: 0.48rem 0.9rem;
    border: 1px solid transparent; border-radius: 10px;
    font-size: 0.82rem; font-weight: 800; text-decoration: none;
    cursor: pointer; white-space: nowrap;
}
.rcm-btn-primary { color: #fff; background: #4f46e5; border-color: #4f46e5; box-shadow: 0 6px 16px rgba(79,70,229,0.28); }
.rcm-btn-ghost { color: #e0e7ff; background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.25); }
.rcm-btn-soft-success { color: #047857; background: #d1fae5; border-color: #a7f3d0; }
.rcm-btn-soft-danger { color: #b91c1c; background: #fee2e2; border-color: #fecaca; }
.rcm-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.rcm-stats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 0.85rem; }
.rcm-stat {
    display: flex; align-items: center; gap: 0.85rem;
    padding: 0.95rem 1rem;
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 14px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.rcm-stat-icon {
    width: 42px; height: 42px; display: grid; place-items: center;
    border-radius: 12px; flex: 0 0 auto;
}
.rcm-stat-icon.indigo { color: #6366f1; background: rgba(99,102,241,0.12); }
.rcm-stat-icon.blue   { color: #2563eb; background: rgba(37,99,235,0.12); }
.rcm-stat-icon.green  { color: #059669; background: rgba(16,185,129,0.12); }
.rcm-stat-icon.amber  { color: #d97706; background: rgba(245,158,11,0.14); }
.rcm-stat strong { display: block; color: var(--sms-heading); font-size: 1.3rem; font-weight: 850; }
.rcm-stat span { color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; }
.rcm-card {
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 16px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-sm);
    overflow: hidden;
}
.rcm-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
}
.rcm-card-title { min-width: 0; }
.rcm-card-title h2 {
    margin: 0; color: var(--sms-text-muted);
    font-size: 0.78rem; font-weight: 800;
    letter-spacing: 0.07em; text-transform: uppercase;
}
.rcm-card-title span { color: var(--sms-text-muted); font-size: 0.78rem; font-weight: 700; }
.rcm-card-tools {
    align-items: center;
    background: var(--sms-surface-solid, #fff);
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    display: flex;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
}
.rcm-search { position: relative; flex: 1 1 auto; min-width: 0; }
.rcm-search i { color: var(--sms-text-muted); left: .9rem; pointer-events: none; position: absolute; top: 50%; transform: translateY(-50%); }
.rcm-search input {
    background: var(--sms-surface-muted, #f8fafc);
    border: 1px solid var(--sms-border, #dbe4f0);
    border-radius: 10px;
    color: var(--sms-text, #334155);
    font-size: .86rem;
    min-height: 40px;
    outline: none;
    padding: .5rem .75rem .5rem 2.25rem;
    width: 100%;
}
.rcm-search input:focus {
    border-color: var(--sms-primary, #2454c6);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}
.rcm-filter {
    background: var(--sms-surface-solid, #fff);
    border: 1px solid var(--sms-border, #dbe4f0);
    border-radius: 10px;
    color: var(--sms-text, #334155);
    flex: 0 0 150px;
    font-size: 0.86rem;
    min-height: 40px;
    outline: none;
    padding: 0.5rem 0.85rem;
}
.rcm-filter:focus {
    border-color: var(--sms-primary, #2454c6);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}
.rcm-table-wrap { overflow-x: auto; }
.rcm-table { width: 100%; border-collapse: collapse; min-width: 820px; }
.rcm-table th,
.rcm-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    text-align: left; vertical-align: middle;
}
.rcm-table th {
    color: var(--sms-text-muted);
    background: var(--sms-surface-muted, #f8fafc);
    font-size: 0.72rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.rcm-title { color: var(--sms-heading); font-weight: 800; line-height: 1.35; }
.rcm-meta { display: block; margin-top: 0.2rem; color: var(--sms-text-muted); font-size: 0.75rem; font-weight: 600; }
.rcm-code {
    display: inline-flex; align-items: center; gap: 0.38rem;
    padding: 0.28rem 0.62rem;
    border-radius: 999px;
    color: #4338ca; background: rgba(99,102,241,0.12);
    font-size: 0.76rem; font-weight: 900; letter-spacing: 0.03em;
}
.rcm-pill { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.28rem 0.62rem; border-radius: 999px; font-size: 0.76rem; font-weight: 800; }
.rcm-pill-active { color: #047857; background: #d1fae5; }
.rcm-pill-inactive { color: #b91c1c; background: #fee2e2; }
.rcm-select {
    background: var(--sms-surface-solid, #fff);
    border: 1px solid var(--sms-border, #dbe4f0);
    border-radius: 10px;
    color: var(--sms-text, #334155);
    font-size: 0.84rem;
    min-height: 38px;
    min-width: 240px;
    max-width: 100%;
    outline: none;
    padding: 0.42rem 0.7rem;
}
.rcm-select:focus {
    border-color: var(--sms-primary, #2454c6);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}
.rcm-empty { padding: 2rem 1.25rem; text-align: center; color: var(--sms-text-muted); font-size: 0.9rem; font-weight: 700; }
.rcm-empty[hidden] { display: none; }
.rcm-empty strong { display: block; color: var(--sms-heading); font-size: 0.95rem; font-weight: 850; margin-bottom: 0.35rem; }
.rcm-empty small { display: block; color: var(--sms-text-muted); font-size: 0.82rem; font-weight: 600; line-height: 1.5; }
.rcm-truncate { max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rcm-meta-truncate { display: block; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rcm-pager {
    align-items: center; display: flex; flex-wrap: wrap; gap: 0.9rem;
    padding: 0.85rem 1.25rem;
    border-top: 1px solid var(--sms-border, #e2e8f0);
}
.rcm-pager-info { color: var(--sms-text-muted); font-size: 0.78rem; font-weight: 700; margin-right: auto; }
.rcm-pager[hidden] { display: none; }
.rcm-pager-nav { align-items: center; display: flex; gap: 0.35rem; }
.rcm-pager-btn {
    align-items: center; background: var(--sms-surface-solid, #fff);
    border: 1px solid var(--sms-border, #dbe4f0); border-radius: 9px;
    color: var(--sms-text, #334155); cursor: pointer; display: inline-flex;
    font-size: 0.8rem; font-weight: 800; height: 34px; justify-content: center;
    min-width: 34px; padding: 0 0.55rem;
}
.rcm-pager-btn:hover:not(:disabled):not(.active) { border-color: var(--sms-primary, #2454c6); color: var(--sms-primary, #2454c6); }
.rcm-pager-btn.active { background: var(--sms-primary, #2454c6); border-color: var(--sms-primary, #2454c6); color: #fff; }
.rcm-pager-btn:disabled { cursor: not-allowed; opacity: 0.45; }
.rcm-pager-dots { color: var(--sms-text-muted); font-size: 0.8rem; font-weight: 800; padding: 0 0.15rem; }
.rcm-per-page {
    align-items: center; color: var(--sms-text-muted); display: inline-flex; gap: 0.45rem;
    font-size: 0.78rem; font-weight: 700;
}
.rcm-per-page select {
    background: var(--sms-surface-solid, #fff); border: 1px solid var(--sms-border, #dbe4f0);
    border-radius: 9px; color: var(--sms-text, #334155); font-size: 0.8rem;
    min-height: 34px; outline: none; padding: 0.25rem 0.6rem;
}
.rcm-per-page select:focus { border-color: var(--sms-primary, #2454c6); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
.rcm-menu-wrap { position: relative; display: inline-block; }
.rcm-menu-btn {
    background: var(--sms-surface-muted, #f1f5f9); border: 1px solid var(--sms-border, #dbe4f0);
    border-radius: 9px; color: var(--sms-text-muted); cursor: pointer;
    font-size: 0.8rem; height: 34px; width: 34px; padding: 0;
}
.rcm-menu-btn:hover { border-color: var(--sms-primary, #2454c6); color: var(--sms-primary, #2454c6); }
.rcm-menu {
    background: var(--sms-surface-solid, #fff); border: 1px solid var(--sms-border, #dbe4f0);
    border-radius: 12px; box-shadow: 0 12px 28px rgba(2,6,23,0.18);
    display: none; min-width: 215px; padding: 0.35rem; position: absolute; right: 0; top: calc(100% + 6px);
    z-index: 40;
}
.rcm-menu.open { display: block; }
.rcm-menu-item {
    align-items: center; background: transparent; border: 0; border-radius: 8px;
    color: var(--sms-text, #334155); cursor: pointer; display: flex; gap: 0.6rem;
    font-size: 0.82rem; font-weight: 700; padding: 0.55rem 0.7rem; text-align: left; width: 100%;
}
.rcm-menu-item:hover { background: var(--sms-surface-muted, #f1f5f9); }
.rcm-menu-item i { color: var(--sms-text-muted); width: 16px; text-align: center; }
.rcm-menu-item.danger { color: #b91c1c; }
.rcm-menu-item.danger i { color: #b91c1c; }
.rcm-modal-open { overflow: hidden; }
.rcm-modal-overlay {
    align-items: center; background: rgba(2,6,23,0.55); display: flex;
    inset: 0; justify-content: center; overflow-y: auto; padding: 2rem 1rem;
    position: fixed; z-index: 1000;
}
.rcm-modal-overlay[hidden] { display: none; }
.rcm-modal {
    background: var(--sms-surface-solid, #fff); border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 18px; box-shadow: 0 24px 60px rgba(2,6,23,0.35); display: flex;
    flex-direction: column; max-height: min(85vh, calc(100vh - 4rem));
    max-width: 720px; width: 100%;
}
.rcm-modal-head {
    align-items: center; border-bottom: 1px solid var(--sms-border, #e2e8f0);
    display: flex; flex: 0 0 auto; justify-content: space-between; padding: 1rem 1.25rem;
}
.rcm-modal-head h3 { color: var(--sms-heading); font-size: 1rem; font-weight: 850; margin: 0; }
.rcm-modal-close {
    background: var(--sms-surface-muted, #f1f5f9); border: 1px solid var(--sms-border, #dbe4f0);
    border-radius: 9px; color: var(--sms-text-muted); cursor: pointer; height: 32px; width: 32px;
}
.rcm-modal-close:hover { color: #b91c1c; border-color: #fecaca; }
.rcm-modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 1.25rem; }
.rcm-detail-head { align-items: center; display: flex; flex-wrap: wrap; gap: 0.7rem; margin-bottom: 1.1rem; }
.rcm-detail-grid { display: grid; gap: 0.85rem 1.5rem; grid-template-columns: repeat(2, minmax(0,1fr)); }
.rcm-detail-item { display: flex; flex-direction: column; gap: 0.2rem; min-width: 0; }
.rcm-detail-label {
    color: var(--sms-text-muted); font-size: 0.68rem; font-weight: 800;
    letter-spacing: 0.05em; text-transform: uppercase;
}
.rcm-detail-value { color: var(--sms-heading); font-size: 0.86rem; font-weight: 700; line-height: 1.45; overflow-wrap: anywhere; }
.rcm-detail-section { border-top: 1px solid var(--sms-border, #e2e8f0); margin-top: 1.2rem; padding-top: 1.1rem; }
.rcm-detail-section h4 { color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 800; letter-spacing: 0.07em; margin: 0 0 0.8rem; text-transform: uppercase; }
.rcm-detail-section h4 i { margin-right: 0.4rem; }
.rcm-detail-list { display: flex; flex-direction: column; gap: 0.55rem; }
.rcm-detail-list-item {
    align-items: flex-start; background: var(--sms-surface-muted, #f8fafc);
    border: 1px solid var(--sms-border, #e2e8f0); border-radius: 10px;
    display: flex; gap: 0.8rem; padding: 0.7rem 0.85rem;
}
.rcm-detail-role {
    background: rgba(99,102,241,0.12); border-radius: 999px; color: #4338ca;
    flex: 0 0 auto; font-size: 0.68rem; font-weight: 900; padding: 0.22rem 0.55rem;
    letter-spacing: 0.03em; text-transform: uppercase;
}
.rcm-detail-list-item > div:last-child { display: flex; flex-direction: column; gap: 0.35rem; min-width: 0; }
.rcm-detail-list-item .rcm-meta { margin-top: 0; }
[data-theme="dark"] .rcm-menu,
[data-theme="dark"] .rcm-modal { background: #0f172a; border-color: rgba(148,163,184,0.25); }
[data-theme="dark"] .rcm-menu-item:hover { background: rgba(148,163,184,0.12); }
[data-theme="dark"] .rcm-pager-btn,
[data-theme="dark"] .rcm-menu-btn,
[data-theme="dark"] .rcm-modal-close,
[data-theme="dark"] .rcm-per-page select { background: rgba(15,23,42,0.72); border-color: rgba(148,163,184,0.25); color: #e2e8f0; }
[data-theme="dark"] .rcm-detail-list-item { background: rgba(148,163,184,0.08); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcm-card,
[data-theme="dark"] .rcm-stat { background: rgba(15,23,42,0.72); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcm-card-head,
[data-theme="dark"] .rcm-card-tools,
[data-theme="dark"] .rcm-search input,
[data-theme="dark"] .rcm-table th,
[data-theme="dark"] .rcm-table td { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcm-card-tools,
[data-theme="dark"] .rcm-filter,
[data-theme="dark"] .rcm-select { background: rgba(15,23,42,0.72); }
[data-theme="dark"] .rcm-search input { background: rgba(148,163,184,0.06); }
[data-theme="dark"] .rcm-table th { background: rgba(148,163,184,0.06); }
@media (max-width: 1199.98px) {
    .rcm-stats { grid-template-columns: repeat(2, minmax(0,1fr)); }
}
@media (max-width: 767.98px) {
    .rcm-stats { grid-template-columns: 1fr; }
    .rcm-card-head,
    .rcm-card-tools { align-items: stretch; flex-direction: column; }
    .rcm-search { width: 100%; }
    .rcm-filter { flex-basis: auto; width: 100%; }
    .rcm-btn { width: 100%; }
    .rcm-select { width: 100%; }
    .rcm-pager { align-items: stretch; flex-direction: column; }
    .rcm-pager-info { margin-right: 0; }
    .rcm-pager-nav { justify-content: center; overflow-x: auto; padding-bottom: 0.15rem; }
    .rcm-per-page { justify-content: space-between; }
    .rcm-detail-grid { grid-template-columns: 1fr; }
    .rcm-modal-overlay { padding: 1rem 0.75rem; }
}
</style>

<div class="rcm-wrap">
    <div class="rcm-stats">
        <div class="rcm-stat">
            <div class="rcm-stat-icon indigo"><?= smsIcon('user-tie') ?></div>
            <div><strong id="rcm-stat-total"><?= (int) $stats['total_coordinators'] ?></strong><span>Total Coordinators</span></div>
        </div>
        <div class="rcm-stat">
            <div class="rcm-stat-icon green"><?= smsIcon('user-check') ?></div>
            <div><strong id="rcm-stat-active"><?= (int) $stats['active_coordinators'] ?></strong><span>Active Coordinators</span></div>
        </div>
        <div class="rcm-stat">
            <div class="rcm-stat-icon blue"><?= smsIcon('users') ?></div>
            <div><strong id="rcm-stat-assigned"><?= (int) $stats['assigned_groups'] ?></strong><span>Assigned Groups</span></div>
        </div>
        <div class="rcm-stat">
            <div class="rcm-stat-icon amber"><?= smsIcon('clock') ?></div>
            <div><strong id="rcm-stat-pending"><?= (int) $stats['pending_groups'] ?></strong><span>Pending Assignment</span></div>
        </div>
    </div>

    <section class="rcm-card" data-rcm-card="eligible">
        <div class="rcm-card-head">
            <div class="rcm-card-title">
                <h2><?= smsIcon('user-plus') ?> Assign Research Coordinator</h2>
                <span data-rcm-count><?= count($eligible) ?> group<?= count($eligible) === 1 ? '' : 's' ?> waiting • <?= count($pool) ?> coordinator(s)</span>
            </div>
            <div class="rcm-meta" data-rcm-sync>Synced <?= htmlspecialchars(date('M j, Y g:i:s A')) ?></div>
        </div>
        <div class="rcm-card-tools">
            <label class="rcm-search">
                <?= smsIcon('search') ?>
                <input type="search" data-rcm-search placeholder="Search by student, group, or title..." aria-label="Search eligible students and research groups">
            </label>
            <select class="rcm-filter" data-rcm-status aria-label="Filter coordinator status">
                <option value="">All Status</option>
                <option value="eligible">Waiting</option>
            </select>
        </div>

        <div class="rcm-table-wrap" data-rcm-table>
            <table class="rcm-table">
                <thead>
                    <tr>
                        <th>Student / Group</th>
                        <th>Title</th>
                        <th>Adviser</th>
                        <th style="min-width:240px;">Research Coordinator</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody data-rcm-tbody>
                    <?php foreach ($eligible as $g): ?>
                        <?php
                            $suggested = trim((string) ($g['suggested_coordinator'] ?? ''));
                            $matchingOption = null;
                            foreach ($pool as $c) {
                                if (strcasecmp($c['name'], $suggested) === 0) {
                                    $matchingOption = $c;
                                    break;
                                }
                            }
                            $defaultValue = $matchingOption !== null
                                ? ($matchingOption['user_id'] > 0 ? (string) $matchingOption['user_id'] : 'name:' . $matchingOption['name'])
                                : '';
                            $searchText = strtolower(trim(($g['group_number'] ?? '') . ' ' . ($g['group_name'] ?? '') . ' ' . ($g['research_title'] ?? '') . ' ' . ($g['adviser'] ?? '') . ' ' . ($g['proposal_number'] ?? '') . ' ' . ($g['student_id'] ?? '') . ' ' . $suggested));
                        ?>
                        <tr data-rcm-row data-status="eligible" data-search="<?= htmlspecialchars($searchText) ?>">
                            <td>
                                <div class="rcm-title"><?= htmlspecialchars($g['group_number']) ?></div>
                                <?php if (trim((string) ($g['group_name'] ?? '')) !== ''): ?>
                                    <span class="rcm-meta"><?= htmlspecialchars($g['group_name']) ?></span>
                                <?php endif; ?>
                                <?php if (trim((string) ($g['student_id'] ?? '')) !== ''): ?>
                                    <span class="rcm-meta"><?= htmlspecialchars((string) $g['student_id']) ?></span>
                                <?php elseif (trim((string) ($g['proposal_number'] ?? '')) !== ''): ?>
                                    <span class="rcm-meta"><?= htmlspecialchars($g['proposal_number']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><div class="rcm-title rcm-truncate" title="<?= htmlspecialchars($g['research_title']) ?>"><?= htmlspecialchars($g['research_title']) ?></div></td>
                            <td><?= htmlspecialchars((string) ($g['adviser'] ?? '')) ?></td>
                            <td>
                                <select class="rcm-select rcm-coordinator-select" data-group="<?= htmlspecialchars($g['group_number'], ENT_QUOTES) ?>" data-student="<?= htmlspecialchars((string) ($g['student_id'] ?? ''), ENT_QUOTES) ?>">
                                    <option value="">Select coordinator…</option>
                                    <?php foreach ($pool as $c): ?>
                                        <?php
                                            $optValue = $c['user_id'] > 0 ? (string) $c['user_id'] : 'name:' . $c['name'];
                                            $optLabel = $c['name'];
                                            if ($c['email'] !== '') {
                                                $optLabel .= ' (' . $c['email'] . ')';
                                            }
                                            if ($c['source'] === 'user' && !$c['active']) {
                                                $optLabel .= ' — inactive account';
                                            }
                                            $selected = ($defaultValue !== '' && $optValue === $defaultValue) ? ' selected' : '';
                                        ?>
                                        <option value="<?= htmlspecialchars($optValue, ENT_QUOTES) ?>"<?= $selected ?>><?= htmlspecialchars($optLabel) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($suggested !== '' && $matchingOption === null): ?>
                                    <span class="rcm-meta">Suggested from Title Approval: <strong><?= htmlspecialchars($suggested) ?></strong></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="rcm-btn rcm-btn-primary rcm-assign-btn" data-group="<?= htmlspecialchars($g['group_number'], ENT_QUOTES) ?>" data-student="<?= htmlspecialchars((string) ($g['student_id'] ?? ''), ENT_QUOTES) ?>">
                                    <?= smsIcon('check') ?> Assign
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="rcm-pager" data-rcm-pager>
            <span class="rcm-pager-info" data-rcm-info></span>
            <div class="rcm-pager-nav">
                <button type="button" class="rcm-pager-btn" data-rcm-prev aria-label="Previous page"><?= smsIcon('chevron-left') ?></button>
                <div class="rcm-pager-nav" data-rcm-pages></div>
                <button type="button" class="rcm-pager-btn" data-rcm-next aria-label="Next page"><?= smsIcon('chevron-right') ?></button>
            </div>
            <label class="rcm-per-page">Records
                <select data-rcm-per-page aria-label="Records per page">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                / page
            </label>
        </div>
        <div class="rcm-empty" data-rcm-empty hidden>Walang record na tumugma sa search.</div>
    </section>

    <section class="rcm-card" data-rcm-card="assignments">
        <div class="rcm-card-head">
            <div class="rcm-card-title">
                <h2><?= smsIcon('clipboard-list') ?> Assignment List</h2>
                <span data-rcm-count><?= count($assignments) ?> assignment<?= count($assignments) === 1 ? '' : 's' ?></span>
            </div>
        </div>
        <div class="rcm-card-tools">
            <label class="rcm-search">
                <?= smsIcon('search') ?>
                <input type="search" data-rcm-search placeholder="Search by group, title, or coordinator..." aria-label="Search coordinator assignments">
            </label>
            <select class="rcm-filter" data-rcm-status aria-label="Filter assignment status">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="rcm-table-wrap" data-rcm-table>
            <table class="rcm-table">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Coordinator</th>
                        <th>Status</th>
                        <th>Assigned At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody data-rcm-tbody>
                    <?php foreach ($assignments as $a): ?>
                        <?php $searchText = strtolower(trim(($a['group_number'] ?? '') . ' ' . ($a['group_name'] ?? '') . ' ' . ($a['research_title'] ?? '') . ' ' . ($a['adviser'] ?? '') . ' ' . ($a['coordinator_name'] ?? '') . ' ' . ($a['coordinator_email'] ?? '') . ' ' . ($a['proposal_number'] ?? ''))); ?>
                        <tr data-rcm-row data-status="<?= $a['status'] === 'Active' ? 'active' : 'inactive' ?>" data-search="<?= htmlspecialchars($searchText) ?>">
                            <td>
                                <div class="rcm-title"><?= htmlspecialchars($a['group_number']) ?></div>
                                <?php if (trim((string) $a['group_name']) !== ''): ?>
                                    <span class="rcm-meta"><?= htmlspecialchars($a['group_name']) ?></span>
                                <?php endif; ?>
                                <?php if (trim((string) $a['research_title']) !== ''): ?>
                                    <span class="rcm-meta rcm-meta-truncate" title="<?= htmlspecialchars($a['research_title']) ?>"><?= htmlspecialchars($a['research_title']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="rcm-title"><?= htmlspecialchars($a['coordinator_name']) ?></div>
                                <?php if (trim((string) $a['coordinator_email']) !== ''): ?>
                                    <span class="rcm-meta"><?= htmlspecialchars($a['coordinator_email']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="rcm-pill rcm-pill-<?= $a['status'] === 'Active' ? 'active' : 'inactive' ?>">
                                    <?= smsIcon($a['status'] === 'Active' ? 'check-circle' : 'minus-circle') ?>
                                    <?= htmlspecialchars($a['status']) ?>
                                </span>
                            </td>
                            <td class="rcm-meta"><?= $a['assigned_at'] !== '' ? htmlspecialchars(date('M j, Y g:i A', strtotime($a['assigned_at']))) : '—' ?></td>
                            <td>
                                <span class="rcm-menu-wrap">
                                    <button type="button" class="rcm-btn rcm-menu-btn" data-rcm-menu aria-label="Actions"><?= smsIcon('ellipsis-v') ?></button>
                                    <div class="rcm-menu" role="menu">
                                        <button type="button" class="rcm-menu-item" data-menu="view" data-type="assignment" data-id="<?= (int) $a['id'] ?>"><?= smsIcon('eye') ?> View Details</button>
                                        <button type="button" class="rcm-menu-item" data-menu="reassign" data-group="<?= htmlspecialchars($a['group_number'], ENT_QUOTES) ?>" data-coordinator="<?= htmlspecialchars($a['coordinator_name'], ENT_QUOTES) ?>"><?= smsIcon('user-cog') ?> Reassign Coordinator</button>
                                        <button type="button" class="rcm-menu-item<?= $a['status'] === 'Active' ? ' danger' : '' ?>" data-menu="toggle" data-id="<?= (int) $a['id'] ?>" data-status="<?= $a['status'] === 'Active' ? 'Inactive' : 'Active' ?>"><?= smsIcon($a['status'] === 'Active' ? 'pause' : 'play') ?> <?= $a['status'] === 'Active' ? 'Deactivate Assignment' : 'Reactivate Assignment' ?></button>
                                    </div>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="rcm-pager" data-rcm-pager>
            <span class="rcm-pager-info" data-rcm-info></span>
            <div class="rcm-pager-nav">
                <button type="button" class="rcm-pager-btn" data-rcm-prev aria-label="Previous page"><?= smsIcon('chevron-left') ?></button>
                <div class="rcm-pager-nav" data-rcm-pages></div>
                <button type="button" class="rcm-pager-btn" data-rcm-next aria-label="Next page"><?= smsIcon('chevron-right') ?></button>
            </div>
            <label class="rcm-per-page">Records
                <select data-rcm-per-page aria-label="Records per page">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                / page
            </label>
        </div>
        <div class="rcm-empty" data-rcm-empty hidden>Walang record na tumugma sa search.</div>
    </section>

    <section class="rcm-card" data-rcm-card="roster">
        <div class="rcm-card-head">
            <div class="rcm-card-title">
                <h2><?= smsIcon('user-tie') ?> Coordinator Roster</h2>
                <span data-rcm-count><?= count($roster) ?> coordinator<?= count($roster) === 1 ? '' : 's' ?></span>
            </div>
        </div>
        <div class="rcm-card-tools">
            <label class="rcm-search">
                <?= smsIcon('search') ?>
                <input type="search" data-rcm-search placeholder="Search by coordinator or email..." aria-label="Search coordinator roster">
            </label>
            <select class="rcm-filter" data-rcm-status aria-label="Filter coordinator status">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="rcm-table-wrap" data-rcm-table>
            <table class="rcm-table">
                <thead>
                    <tr>
                        <th>Coordinator</th>
                        <th>Status</th>
                        <th>Groups Handled</th>
                        <th>Last Assigned</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody data-rcm-tbody>
                    <?php foreach ($roster as $r): ?>
                        <?php $searchText = strtolower(trim(($r['name'] ?? '') . ' ' . ($r['email'] ?? '') . ' ' . implode(' ', $r['groups'] ?? []))); ?>
                        <tr data-rcm-row data-status="<?= !empty($r['active']) ? 'active' : 'inactive' ?>" data-search="<?= htmlspecialchars($searchText) ?>">
                            <td>
                                <div class="rcm-title"><?= htmlspecialchars($r['name']) ?></div>
                                <?php if (trim((string) $r['email']) !== ''): ?>
                                    <span class="rcm-meta"><?= htmlspecialchars($r['email']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="rcm-pill rcm-pill-<?= !empty($r['active']) ? 'active' : 'inactive' ?>">
                                    <?= smsIcon(!empty($r['active']) ? 'check-circle' : 'minus-circle') ?>
                                    <?= !empty($r['active']) ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= (int) $r['active_count'] ?></strong>
                                <span class="rcm-meta"><?= (int) $r['active_count'] + (int) $r['inactive_count'] ?> assignment(s)</span>
                                <?php if (!empty($r['groups'])): ?>
                                    <span class="rcm-meta"><?= htmlspecialchars(implode(', ', $r['groups'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="rcm-meta"><?= $r['last_assigned'] !== null ? htmlspecialchars(date('M j, Y', strtotime($r['last_assigned']))) : '—' ?></td>
                            <td>
                                <span class="rcm-menu-wrap">
                                    <button type="button" class="rcm-btn rcm-menu-btn" data-rcm-menu aria-label="Actions"><?= smsIcon('ellipsis-v') ?></button>
                                    <div class="rcm-menu" role="menu">
                                        <button type="button" class="rcm-menu-item" data-menu="view" data-type="coordinator" data-id="<?= (int) $r['user_id'] ?>" data-name="<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>"><?= smsIcon('eye') ?> View Details</button>
                                    </div>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="rcm-pager" data-rcm-pager>
            <span class="rcm-pager-info" data-rcm-info></span>
            <div class="rcm-pager-nav">
                <button type="button" class="rcm-pager-btn" data-rcm-prev aria-label="Previous page"><?= smsIcon('chevron-left') ?></button>
                <div class="rcm-pager-nav" data-rcm-pages></div>
                <button type="button" class="rcm-pager-btn" data-rcm-next aria-label="Next page"><?= smsIcon('chevron-right') ?></button>
            </div>
            <label class="rcm-per-page">Records
                <select data-rcm-per-page aria-label="Records per page">
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                / page
            </label>
        </div>
        <div class="rcm-empty" data-rcm-empty hidden>Walang record na tumugma sa search.</div>
    </section>
</div>

<div class="rcm-modal-overlay" data-rcm-modal hidden>
    <div class="rcm-modal" role="dialog" aria-modal="true" aria-labelledby="rcm-modal-title">
        <div class="rcm-modal-head">
            <h3 id="rcm-modal-title" data-rcm-modal-title>Details</h3>
            <button type="button" class="rcm-modal-close" data-rcm-close aria-label="Close"><?= smsIcon('times') ?></button>
        </div>
        <div class="rcm-modal-body" data-rcm-modal-body></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = <?= json_encode($csrf) ?>;
    const endpoint = 'research-coordinator-management.php';
    let pollTimer = null;
    let pendingRequest = false;
    let CURRENT = null;

    const esc = function (value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };
    const fmtDateTime = function (dt) {
        if (!dt) return '—';
        const d = new Date(String(dt).replace(' ', 'T'));
        if (isNaN(d.getTime())) return '—';
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        let h = d.getHours();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        const pad = function (n) { return (n < 10 ? '0' : '') + n; };
        return months[d.getMonth()] + ' ' + pad(d.getDate()) + ', ' + d.getFullYear() + ' ' + pad(h) + ':' + pad(d.getMinutes()) + ' ' + ampm;
    };
    const fmtDateOnly = function (dt) {
        if (!dt) return '—';
        const d = new Date(String(dt).replace(' ', 'T'));
        if (isNaN(d.getTime())) return '—';
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    };

    // ---- per-card client-side state (search / filter / pagination) ----
    const cardState = {};
    function getCardState(card) {
        const key = card.dataset.rcmCard || 'c';
        if (!cardState[key]) cardState[key] = { page: 1, perPage: 10 };
        return cardState[key];
    }
    function pageWindow(current, pages) {
        const out = [1];
        for (let i = Math.max(2, current - 1); i <= Math.min(pages - 1, current + 1); i++) out.push(i);
        if (pages > 1 && out[out.length - 1] !== pages) out.push(pages);
        return out;
    }
    function buildPageButtons(current, pages) {
        const nums = pageWindow(current, pages);
        let html = '';
        let last = 0;
        nums.forEach(function (n) {
            if (n - last > 1) html += '<span class="rcm-pager-dots">&hellip;</span>';
            html += '<button type="button" class="rcm-pager-btn' + (n === current ? ' active' : '') + '" data-rcm-page="' + n + '">' + n + '</button>';
            last = n;
        });
        return html;
    }
    function updatePager(card, total) {
        const state = getCardState(card);
        const pager = card.querySelector('[data-rcm-pager]');
        const info = card.querySelector('[data-rcm-info]');
        const pagesEl = card.querySelector('[data-rcm-pages]');
        const prev = card.querySelector('[data-rcm-prev]');
        const next = card.querySelector('[data-rcm-next]');
        if (!pager) return;
        if (total === 0) {
            pager.hidden = true;
            if (info) info.textContent = '';
            if (pagesEl) pagesEl.innerHTML = '';
            if (prev) prev.disabled = true;
            if (next) next.disabled = true;
            return;
        }
        pager.hidden = false;
        const perPage = state.perPage;
        const pages = Math.max(1, Math.ceil(total / perPage));
        const start = (state.page - 1) * perPage + 1;
        const end = Math.min(total, state.page * perPage);
        if (info) info.textContent = 'Showing ' + start + '\u2013' + end + ' of ' + total;
        if (prev) prev.disabled = state.page <= 1;
        if (next) next.disabled = state.page >= pages;
        if (pagesEl) pagesEl.innerHTML = buildPageButtons(state.page, pages);
    }
    function refreshCard(card) {
        const state = getCardState(card);
        const input = card.querySelector('[data-rcm-search]');
        const status = card.querySelector('[data-rcm-status]');
        const rows = Array.from(card.querySelectorAll('[data-rcm-row]'));
        const empty = card.querySelector('[data-rcm-empty]');
        const count = card.querySelector('[data-rcm-count]');
        const tableWrap = card.querySelector('[data-rcm-table]');
        const term = input ? input.value.trim().toLowerCase() : '';
        const statusValue = status ? status.value.trim().toLowerCase() : '';

        if (rows.length === 0) {
            if (tableWrap) tableWrap.hidden = true;
            if (empty) {
                empty.hidden = false;
                empty.innerHTML = card.dataset.emptyMessage || '<strong>No Records</strong>';
            }
            if (count) count.textContent = '0 records';
            updatePager(card, 0);
            return;
        }

        const filtered = rows.filter(function (row) {
            const matchesSearch = term === '' || String(row.dataset.search || '').indexOf(term) !== -1;
            const matchesStatus = statusValue === '' || String(row.dataset.status || '') === statusValue;
            return matchesSearch && matchesStatus;
        });
        const total = filtered.length;
        const perPage = state.perPage;
        const pages = Math.max(1, Math.ceil(total / perPage));
        if (state.page > pages) state.page = pages;
        if (state.page < 1) state.page = 1;
        const start = (state.page - 1) * perPage;
        const visible = filtered.slice(start, start + perPage);

        rows.forEach(function (row) { row.hidden = true; });
        visible.forEach(function (row) { row.hidden = false; });

        if (tableWrap) tableWrap.hidden = false;
        if (empty) {
            if (total === 0) {
                empty.hidden = false;
                empty.innerHTML = '<strong>No Match</strong><br><small>' + (term ? 'No records match your search or filter.' : (card.dataset.emptyMessage || 'No records yet.')) + '</small>';
            } else {
                empty.hidden = true;
            }
        }
        if (count) count.textContent = total + ' of ' + rows.length;
        updatePager(card, total);
    }
    function bindCardFilters() {
        document.querySelectorAll('[data-rcm-card]').forEach(function (card) {
            const input = card.querySelector('[data-rcm-search]');
            const status = card.querySelector('[data-rcm-status]');
            const apply = function () { const s = getCardState(card); s.page = 1; refreshCard(card); };
            if (input && !input.dataset.rcmBound) { input.dataset.rcmBound = '1'; input.addEventListener('input', apply); }
            if (status && !status.dataset.rcmBound) { status.dataset.rcmBound = '1'; status.addEventListener('change', apply); }
            refreshCard(card);
        });
    }
    function bindPagers() {
        document.querySelectorAll('[data-rcm-card]').forEach(function (card) {
            const state = getCardState(card);
            const prev = card.querySelector('[data-rcm-prev]');
            const next = card.querySelector('[data-rcm-next]');
            const per = card.querySelector('[data-rcm-per-page]');
            const pagesEl = card.querySelector('[data-rcm-pages]');
            if (per) {
                per.value = String(state.perPage);
                if (!per.dataset.rcmBound) {
                    per.dataset.rcmBound = '1';
                    per.addEventListener('change', function () {
                        state.perPage = parseInt(per.value, 10) || 10;
                        state.page = 1;
                        refreshCard(card);
                    });
                }
            }
            if (prev && !prev.dataset.rcmBound) {
                prev.dataset.rcmBound = '1';
                prev.addEventListener('click', function () { if (state.page > 1) { state.page--; refreshCard(card); } });
            }
            if (next && !next.dataset.rcmBound) {
                next.dataset.rcmBound = '1';
                next.addEventListener('click', function () { state.page++; refreshCard(card); });
            }
            if (pagesEl && !pagesEl.dataset.rcmBound) {
                pagesEl.dataset.rcmBound = '1';
                pagesEl.addEventListener('click', function (e) {
                    const btn = e.target.closest('[data-rcm-page]');
                    if (btn) { state.page = parseInt(btn.dataset.rcmPage, 10) || 1; refreshCard(card); }
                });
            }
        });
    }

    // ---- table renderers ----
    function renderStats(data) {
        const s = data.stats || {};
        const el = function (id, value) {
            const node = document.getElementById(id);
            if (node) node.textContent = String(value);
        };
        el('rcm-stat-total', s.total_coordinators ?? '-');
        el('rcm-stat-active', s.active_coordinators ?? '-');
        el('rcm-stat-assigned', s.assigned_groups ?? '-');
        el('rcm-stat-pending', s.pending_groups ?? '-');
    }

    function renderEligible(card, data) {
        const tbody = card.querySelector('[data-rcm-tbody]');
        const empty = card.querySelector('[data-rcm-empty]');
        if (!tbody) return;
        const pool = data.pool || [];
        const eligible = data.eligible || [];

        if (!eligible.length) {
            tbody.innerHTML = '';
            card.dataset.emptyMessage = '<strong>No Pending Assignments</strong><br><small>Assign a Research Coordinator from the roster to each student first. Students appear here until they have an active coordinator.</small>';
            if (empty) {
                empty.hidden = false;
                empty.innerHTML = card.dataset.emptyMessage;
            }
            return;
        }

        tbody.innerHTML = eligible.map(function (g) {
            const suggested = (g.suggested_coordinator || '').trim();
            let defaultValue = '';
            pool.forEach(function (c) {
                if (suggested && c.name.toLowerCase() === suggested.toLowerCase()) {
                    defaultValue = c.user_id > 0 ? String(c.user_id) : 'name:' + c.name;
                }
            });
            const searchText = [g.group_number, g.group_name, g.research_title, g.adviser, g.proposal_number, g.student_id, suggested].join(' ').toLowerCase();
            const options = ['<option value="">Select coordinator…</option>'].concat(pool.map(function (c) {
                const optValue = c.user_id > 0 ? String(c.user_id) : 'name:' + c.name;
                let label = c.name;
                if (c.email) label += ' (' + c.email + ')';
                if (c.source === 'user' && !c.active) label += ' — inactive account';
                return '<option value="' + esc(optValue) + '"' + (optValue === defaultValue ? ' selected' : '') + '>' + esc(label) + '</option>';
            })).join('');
            const hint = (suggested && !defaultValue)
                ? '<span class="rcm-meta">Suggested from Title Approval: <strong>' + esc(suggested) + '</strong></span>'
                : '';
            return '<tr data-rcm-row data-status="eligible" data-search="' + esc(searchText) + '">' +
                '<td><div class="rcm-title">' + esc(g.group_number) + '</div>' +
                    (g.group_name ? '<span class="rcm-meta">' + esc(g.group_name) + '</span>' : '') +
                    (g.student_id ? '<span class="rcm-meta">' + esc(g.student_id) + '</span>' : (g.proposal_number ? '<span class="rcm-meta">' + esc(g.proposal_number) + '</span>' : '')) + '</td>' +
                '<td><div class="rcm-title rcm-truncate" title="' + esc(g.research_title || '') + '">' + esc(g.research_title || '') + '</div></td>' +
                '<td>' + esc(g.adviser || '') + '</td>' +
                '<td><select class="rcm-select rcm-coordinator-select" data-group="' + esc(g.group_number) + '" data-student="' + esc(g.student_id || '') + '">' + options + '</select>' + hint + '</td>' +
                '<td><button type="button" class="rcm-btn rcm-btn-primary rcm-assign-btn" data-group="' + esc(g.group_number) + '" data-student="' + esc(g.student_id || '') + '"><?= smsIcon('check') ?> Assign</button></td>' +
                '</tr>';
        }).join('');
        if (empty) empty.hidden = true;
    }

    function renderAssignments(card, data) {
        const tbody = card.querySelector('[data-rcm-tbody]');
        const empty = card.querySelector('[data-rcm-empty]');
        if (!tbody) return;
        const assignments = data.assignments || [];

        if (!assignments.length) {
            tbody.innerHTML = '';
            card.dataset.emptyMessage = '<strong>No Coordinator Assignments</strong><br><small>There are currently no research coordinator assignments. Assign a coordinator to a fully approved research group above.</small>';
            if (empty) {
                empty.hidden = false;
                empty.innerHTML = card.dataset.emptyMessage;
            }
            return;
        }

        tbody.innerHTML = assignments.map(function (a) {
            const isActive = a.status === 'Active';
            const searchText = [a.group_number, a.group_name, a.research_title, a.adviser, a.coordinator_name, a.coordinator_email, a.proposal_number].join(' ').toLowerCase();
            return '<tr data-rcm-row data-status="' + (isActive ? 'active' : 'inactive') + '" data-search="' + esc(searchText) + '">' +
                '<td><div class="rcm-title">' + esc(a.group_number) + '</div>' +
                    (a.group_name ? '<span class="rcm-meta">' + esc(a.group_name) + '</span>' : '') +
                    (a.research_title ? '<span class="rcm-meta rcm-meta-truncate" title="' + esc(a.research_title) + '">' + esc(a.research_title) + '</span>' : '') + '</td>' +
                '<td><div class="rcm-title">' + esc(a.coordinator_name) + '</div>' +
                    (a.coordinator_email ? '<span class="rcm-meta">' + esc(a.coordinator_email) + '</span>' : '') + '</td>' +
                '<td><span class="rcm-pill rcm-pill-' + (isActive ? 'active' : 'inactive') + '">' + (window.smsIconHtml ? window.smsIconHtml(isActive ? 'check-circle' : 'minus-circle') : '') + ' ' + esc(a.status) + '</span></td>' +
                '<td class="rcm-meta">' + fmtDateTime(a.assigned_at) + '</td>' +
                '<td><span class="rcm-menu-wrap">' +
                    '<button type="button" class="rcm-btn rcm-menu-btn" data-rcm-menu aria-label="Actions"><?= smsIcon('ellipsis-v') ?></button>' +
                    '<div class="rcm-menu" role="menu">' +
                        '<button type="button" class="rcm-menu-item" data-menu="view" data-type="assignment" data-id="' + esc(a.id) + '"><?= smsIcon('eye') ?> View Details</button>' +
                        '<button type="button" class="rcm-menu-item" data-menu="reassign" data-group="' + esc(a.group_number) + '" data-coordinator="' + esc(a.coordinator_name) + '"><?= smsIcon('user-cog') ?> Reassign Coordinator</button>' +
                        '<button type="button" class="rcm-menu-item' + (isActive ? ' danger' : '') + '" data-menu="toggle" data-id="' + esc(a.id) + '" data-status="' + (isActive ? 'Inactive' : 'Active') + '">' + (window.smsIconHtml ? window.smsIconHtml(isActive ? 'pause' : 'play') : '') + ' ' + (isActive ? 'Deactivate Assignment' : 'Reactivate Assignment') + '</button>' +
                    '</div>' +
                '</span></td>' +
                '</tr>';
        }).join('');
        if (empty) empty.hidden = true;
    }

    function renderRoster(card, data) {
        const tbody = card.querySelector('[data-rcm-tbody]');
        const empty = card.querySelector('[data-rcm-empty]');
        if (!tbody) return;
        const roster = data.roster || [];

        if (!roster.length) {
            tbody.innerHTML = '';
            card.dataset.emptyMessage = '<strong>No Coordinators Available</strong><br><small>Research coordinator accounts and coordinators named on approved Title Approval Forms will appear here.</small>';
            if (empty) {
                empty.hidden = false;
                empty.innerHTML = card.dataset.emptyMessage;
            }
            return;
        }

        tbody.innerHTML = roster.map(function (r) {
            const isActive = Boolean(r.active);
            const searchText = [r.name, r.email, (r.groups || []).join(' ')].join(' ').toLowerCase();
            const detailKey = r.user_id > 0 ? String(r.user_id) : (r.name || '');
            return '<tr data-rcm-row data-status="' + (isActive ? 'active' : 'inactive') + '" data-search="' + esc(searchText) + '">' +
                '<td><div class="rcm-title">' + esc(r.name) + '</div>' +
                    (r.email ? '<span class="rcm-meta">' + esc(r.email) + '</span>' : '') + '</td>' +
                '<td><span class="rcm-pill rcm-pill-' + (isActive ? 'active' : 'inactive') + '">' + (window.smsIconHtml ? window.smsIconHtml(isActive ? 'check-circle' : 'minus-circle') : '') + ' ' + (isActive ? 'Active' : 'Inactive') + '</span></td>' +
                '<td><strong>' + (r.active_count || 0) + '</strong><span class="rcm-meta">' + ((r.active_count || 0) + (r.inactive_count || 0)) + ' assignment(s)</span>' +
                    (r.groups && r.groups.length ? '<span class="rcm-meta">' + esc(r.groups.join(', ')) + '</span>' : '') + '</td>' +
                '<td class="rcm-meta">' + fmtDateOnly(r.last_assigned) + '</td>' +
                '<td><span class="rcm-menu-wrap">' +
                    '<button type="button" class="rcm-btn rcm-menu-btn" data-rcm-menu aria-label="Actions"><?= smsIcon('ellipsis-v') ?></button>' +
                    '<div class="rcm-menu" role="menu">' +
                        '<button type="button" class="rcm-menu-item" data-menu="view" data-type="coordinator" data-id="' + esc(detailKey) + '" data-name="' + esc(r.name) + '"><?= smsIcon('eye') ?> View Details</button>' +
                    '</div>' +
                '</span></td>' +
                '</tr>';
        }).join('');
        if (empty) empty.hidden = true;
    }

    function render(data) {
        if (!data || !data.ok) return;
        CURRENT = data;
        renderStats(data);
        const cards = document.querySelectorAll('[data-rcm-card]');
        if (cards[0]) renderEligible(cards[0], data);
        if (cards[1]) renderAssignments(cards[1], data);
        if (cards[2]) renderRoster(cards[2], data);

        const sync = document.querySelector('[data-rcm-sync]');
        if (sync && data.server_time) {
            const d = new Date(String(data.server_time).replace(' ', 'T'));
            if (!isNaN(d.getTime())) {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                sync.textContent = 'Synced ' + months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear() + ' ' + d.toLocaleTimeString('en-US', { hour12: true });
            }
        }

        bindAll();
    }

    function pollNow() {
        if (pendingRequest) return;
        pendingRequest = true;
        fetch(endpoint + '?ajax=coordinator-assignments&t=' + Date.now(), {
            headers: { 'X-Requested-With': 'fetch' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                render(data);
                if (data && data.message) showFlash(data.message, data.ok !== false);
            })
            .catch(function () {})
            .finally(function () { pendingRequest = false; });
    }

    function showFlash(message, isSuccess) {
        const wrap = document.querySelector('.rcm-wrap');
        if (!wrap) return;
        const old = wrap.querySelector('.rcm-flash');
        if (old) old.remove();
        const div = document.createElement('div');
        div.className = 'rcm-alert rcm-alert-' + (isSuccess ? 'success' : 'danger') + ' rcm-flash';
        div.innerHTML = (window.smsIconHtml ? window.smsIconHtml(isSuccess ? 'check-circle' : 'exclamation-circle') : '') + '<span>' + message.replace(/[&<>]/g, '') + '</span>';
        wrap.insertBefore(div, wrap.firstChild);
        setTimeout(function () {
            if (div.parentNode) div.parentNode.removeChild(div);
        }, 6000);
    }

    function ensureAssignConfirmModal() {
        if (document.getElementById('rcmAssignConfirmModal')) return;
        const wrap = document.createElement('div');
        wrap.innerHTML =
            '<div class="modal fade" id="rcmAssignConfirmModal" tabindex="-1" aria-hidden="true">' +
            '<div class="modal-dialog modal-dialog-centered rcas-confirm-dialog">' +
            '<div class="modal-content rcas-confirm-modal">' +
            '<div class="modal-header rcas-confirm-modal-header">' +
            '<div class="d-flex align-items-center gap-2">' +
            '<span class="rcas-confirm-modal-icon-wrap"><?= smsIcon('user-tie') ?></span>' +
            '<h5 class="modal-title mb-0">Confirm Coordinator Assignment</h5>' +
            '</div>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
            '</div>' +
            '<div class="modal-body rcas-confirm-modal-body">' +
            '<p class="rcas-confirm-question">Are you sure you want to assign this research coordinator to the selected research group?</p>' +
            '<div class="rcas-confirm-details-card">' +
            '<div class="rcas-confirm-detail"><span class="rcas-confirm-label">Research Group</span><span class="rcas-confirm-value" id="rcmConfirmGroupNumber">—</span></div>' +
            '<div class="rcas-confirm-detail"><span class="rcas-confirm-label">Research Title</span><span class="rcas-confirm-value" id="rcmConfirmResearchTitle">—</span></div>' +
            '<div class="rcas-confirm-detail"><span class="rcas-confirm-label">Research Coordinator</span><span class="rcas-confirm-value" id="rcmConfirmCoordinatorName">—</span></div>' +
            '</div>' +
            '</div>' +
            '<div class="modal-footer rcas-confirm-modal-footer">' +
            '<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" id="rcmAssignCancel">Cancel</button>' +
            '<button type="button" class="btn btn-primary rcas-confirm-btn" id="rcmAssignConfirm"><?= smsIcon('check', ['class' => 'me-1']) ?>Confirm Assignment</button>' +
            '</div>' +
            '</div></div></div>';
        document.body.appendChild(wrap.firstChild);
    }

    let pendingAssignData = null;

    function openAssignConfirm(btn, groupNumber, coordinatorLabel, researchTitle) {
        ensureAssignConfirmModal();
        pendingAssignData = { btn: btn, groupNumber: groupNumber };

        const grpEl = document.getElementById('rcmConfirmGroupNumber');
        const titleEl = document.getElementById('rcmConfirmResearchTitle');
        const nameEl = document.getElementById('rcmConfirmCoordinatorName');
        if (grpEl) grpEl.textContent = groupNumber || '—';
        if (titleEl) titleEl.textContent = researchTitle || '—';
        if (nameEl) nameEl.textContent = coordinatorLabel || '—';

        const modalEl = document.getElementById('rcmAssignConfirmModal');
        if (modalEl && window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }

        const confirmBtn = document.getElementById('rcmAssignConfirm');
        if (confirmBtn) {
            confirmBtn.onclick = function () {
                if (modalEl && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }
                if (pendingAssignData) {
                    doAssign(pendingAssignData.btn);
                    pendingAssignData = null;
                }
            };
        }

        const cancelBtn = document.getElementById('rcmAssignCancel');
        if (cancelBtn) {
            cancelBtn.onclick = function () {
                pendingAssignData = null;
            };
        }
    }

    function doAssign(btn) {
        const tr = btn.closest('tr');
        const select = tr ? tr.querySelector('.rcm-coordinator-select') : null;
        if (!select || !select.value) {
            showFlash('Please select a Research Coordinator first.', false);
            return;
        }
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<?= smsIcon('spinner', ['class' => 'fa-spin']) ?> Saving…';

        const fd = new FormData();
        fd.append('ajax', 'assign');
        fd.append('_token', CSRF);
        fd.append('group_number', btn.dataset.group);
        fd.append('student_id', btn.dataset.student || '');
        fd.append('coordinator', select.value);

        fetch(endpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                render(data);
                if (data && data.message) showFlash(data.message, data.ok !== false);
            })
            .catch(function () {
                showFlash('Could not save the assignment. Please try again.', false);
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = original;
            });
    }

    function bindActions() {
        document.querySelectorAll('.rcm-assign-btn').forEach(function (btn) {
            btn.onclick = function () {
                const tr = btn.closest('tr');
                const select = tr ? tr.querySelector('.rcm-coordinator-select') : null;
                if (!select || !select.value) {
                    showFlash('Please select a Research Coordinator first.', false);
                    return;
                }

                // Gather display info for the confirmation modal
                const groupNumber = btn.dataset.group || '—';
                const selectedOption = select.options[select.selectedIndex];
                const coordinatorLabel = selectedOption ? selectedOption.text : '—';
                const titleCell = tr.querySelector('td:nth-child(2) .rcm-title');
                const researchTitle = titleCell ? titleCell.textContent.trim() : '—';

                openAssignConfirm(btn, groupNumber, coordinatorLabel, researchTitle);
            };
        });
    }
    function toggleAssignment(item) {
        const fd = new FormData();
        fd.append('ajax', 'set-status');
        fd.append('_token', CSRF);
        fd.append('id', item.dataset.id);
        fd.append('status', item.dataset.status);

        fetch(endpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                render(data);
                if (data && data.message) showFlash(data.message, data.ok !== false);
            })
            .catch(function () {
                showFlash('Could not update the assignment status. Please try again.', false);
            });
    }

    // ---- compact action menus (View Details / Reassign / Deactivate) ----
    function closeMenus() {
        document.querySelectorAll('.rcm-menu.open').forEach(function (m) { m.classList.remove('open'); });
    }
    function bindMenus() {
        document.querySelectorAll('[data-rcm-menu]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const menu = btn.closest('.rcm-menu-wrap').querySelector('.rcm-menu');
                if (!menu) return;
                const wasOpen = menu.classList.contains('open');
                closeMenus();
                if (!wasOpen) menu.classList.add('open');
            });
        });
        document.querySelectorAll('.rcm-menu-item').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.stopPropagation();
                closeMenus();
                const action = item.dataset.menu;
                if (action === 'view') openDetails(item);
                else if (action === 'reassign') openReassign(item);
                else if (action === 'toggle') toggleAssignment(item);
            });
        });
    }

    // ---- View Details modal ----
    function openModal(title, html) {
        const overlay = document.querySelector('[data-rcm-modal]');
        const titleEl = document.querySelector('[data-rcm-modal-title]');
        const body = document.querySelector('[data-rcm-modal-body]');
        if (!overlay || !body) return;
        if (titleEl) titleEl.textContent = title;
        body.innerHTML = html;
        overlay.hidden = false;
        document.body.classList.add('rcm-modal-open');
    }
    function closeModal() {
        const overlay = document.querySelector('[data-rcm-modal]');
        if (overlay) overlay.hidden = true;
        document.body.classList.remove('rcm-modal-open');
    }
    function detailField(label, value) {
        if (value === '' || value === null || value === undefined) return '';
        return '<div class="rcm-detail-item"><span class="rcm-detail-label">' + label + '</span><span class="rcm-detail-value">' + value + '</span></div>';
    }
    function statusPill(status) {
        const active = String(status || '').toLowerCase() === 'active';
        return '<span class="rcm-pill rcm-pill-' + (active ? 'active' : 'inactive') + '">' + (window.smsIconHtml ? window.smsIconHtml(active ? 'check-circle' : 'minus-circle') : '') + ' ' + esc(status || '') + '</span>';
    }
    function buildGroupModalHtml(d) {
        const g = d.detail;
        const current = (g.assignments && g.assignments.length) ? g.assignments[0] : null;
        const leader = g.leader && g.leader.student_name
            ? '<div class="rcm-detail-list"><div class="rcm-detail-list-item"><span class="rcm-detail-role">Leader</span>' +
                '<div><strong>' + esc(g.leader.student_name) + '</strong>' +
                '<span class="rcm-meta">' + esc(g.leader.student_id || '') + (g.leader.email ? ' &bull; ' + esc(g.leader.email) : '') + (g.leader.contact ? ' &bull; ' + esc(g.leader.contact) : '') + '</span></div></div></div>'
            : '<span class="rcm-meta">No group leader recorded.</span>';
        const members = g.members && g.members.length
            ? '<div class="rcm-detail-list">' + g.members.map(function (m, i) {
                return '<div class="rcm-detail-list-item"><span class="rcm-detail-role">Member ' + (i + 1) + '</span>' +
                    '<div><strong>' + esc(m.student_name) + '</strong>' +
                    '<span class="rcm-meta">' + esc(m.student_id) + (m.email ? ' &bull; ' + esc(m.email) : '') + (m.contact ? ' &bull; ' + esc(m.contact) : '') + '</span></div></div>';
              }).join('') + '</div>'
            : '<span class="rcm-meta">No group members recorded.</span>';
        const assignment = current
            ? '<div class="rcm-detail-list-item"><span class="rcm-detail-role">Coordinator</span>' +
                '<div><strong>' + esc(current.coordinator_name) + '</strong>' +
                (current.coordinator_email ? '<span class="rcm-meta">' + esc(current.coordinator_email) + '</span>' : '') +
                '<span class="rcm-meta">Assigned ' + fmtDateTime(current.assigned_at) +
                (current.updated_at && current.updated_at !== current.assigned_at ? ' &bull; Updated ' + fmtDateTime(current.updated_at) : '') + '</span></div></div>'
            : '<span class="rcm-meta">No coordinator has been assigned to this group yet.</span>';
        const history = g.assignments && g.assignments.length
            ? '<div class="rcm-detail-list">' + g.assignments.map(function (a) {
                return '<div class="rcm-detail-list-item"><span class="rcm-detail-role">' + esc(a.coordinator_name) + '</span>' +
                    '<div>' + statusPill(a.status) +
                    '<span class="rcm-meta">Assigned ' + fmtDateTime(a.assigned_at) + '</span></div></div>';
              }).join('') + '</div>'
            : '<span class="rcm-meta">No assignment history recorded.</span>';
        return '<div class="rcm-detail-head"><span class="rcm-code">' + esc(g.group_number) + '</span>' +
                (current ? statusPill(current.status) : '<span class="rcm-pill rcm-pill-inactive"><?= smsIcon('minus-circle') ?> Unassigned</span>') + '</div>' +
            '<div class="rcm-detail-grid">' +
                detailField('Research Title', esc(g.research_title)) +
                detailField('Group Name', esc(g.group_name)) +
                detailField('Proposal Number', esc(g.proposal_number)) +
                detailField('Academic Year', esc(g.academic_year)) +
                detailField('College / Department', esc(g.college_dept)) +
                detailField('Research Adviser', esc(g.adviser)) +
                detailField('Group Assigned Date', esc(g.date_assigned)) +
            '</div>' +
            '<div class="rcm-detail-section"><h4><?= smsIcon('user') ?> Leader</h4>' + leader + '</div>' +
            '<div class="rcm-detail-section"><h4><?= smsIcon('users') ?> Group Members</h4>' + members + '</div>' +
            '<div class="rcm-detail-section"><h4><?= smsIcon('clipboard-check') ?> Current Coordinator</h4>' + assignment + '</div>' +
            '<div class="rcm-detail-section"><h4><?= smsIcon('history') ?> Assignment History</h4>' + history + '</div>';
    }
    function buildCoordinatorModalHtml(d) {
        const c = d.detail;
        const as = c.assignments && c.assignments.length ? c.assignments : [];
        const list = as.length
            ? '<div class="rcm-detail-list">' + as.map(function (a) {
                return '<div class="rcm-detail-list-item"><span class="rcm-detail-role">' + esc(a.group_number) + '</span>' +
                    '<div><strong>' + esc(a.research_title) + '</strong>' +
                    statusPill(a.status) +
                    '<span class="rcm-meta">Assigned ' + fmtDateTime(a.assigned_at) + '</span></div></div>';
              }).join('') + '</div>'
            : '<span class="rcm-meta">No current coordinator assignments.</span>';
        return '<div class="rcm-detail-grid">' +
                detailField('Name', esc(c.name)) +
                detailField('Email', esc(c.email)) +
                detailField('Account', c.user_id > 0 ? 'Research Coordinator account' : 'Named on Title Approval') +
                detailField('Status', statusPill(c.active ? 'Active' : 'Inactive')) +
                detailField('Total Assignments', esc(String(as.length))) +
                detailField('Active Assignments', esc(String(as.filter(function (a) { return a.status === 'Active'; }).length))) +
            '</div>' +
            '<div class="rcm-detail-section"><h4><?= smsIcon('users') ?> Assignments</h4>' + list + '</div>';
    }
    function openDetails(item) {
        const type = item.dataset.type;
        const id = (item.dataset.id || '').trim();
        const key = id !== '' ? id : (item.dataset.name || '').trim();
        if (!key) { showFlash('Details are unavailable for this record.', false); return; }
        fetch(endpoint + '?ajax=details&type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(key), { headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) { showFlash((data && data.message) || 'Could not load details.', false); return; }
                const isCoord = type === 'coordinator';
                openModal(isCoord ? 'Coordinator Details' : 'Research Group Details', isCoord ? buildCoordinatorModalHtml(data) : buildGroupModalHtml(data));
            })
            .catch(function () { showFlash('Could not load details. Please try again.', false); });
    }

    // ---- Reassign Coordinator modal (reuses the existing ajax=assign upsert) ----
    function openReassign(item) {
        const group = item.dataset.group;
        const currentName = item.dataset.coordinator || '';
        const pool = (CURRENT && CURRENT.pool) || [];
        if (!pool.length) { showFlash('No coordinators are available to reassign.', false); return; }
        const options = ['<option value="">Select coordinator…</option>'].concat(pool.map(function (c) {
            const v = c.user_id > 0 ? String(c.user_id) : 'name:' + c.name;
            let label = c.name;
            if (c.email) label += ' (' + c.email + ')';
            if (c.source === 'user' && !c.active) label += ' — inactive account';
            const sel = currentName && c.name.toLowerCase() === currentName.toLowerCase() ? ' selected' : '';
            return '<option value="' + esc(v) + '"' + sel + '>' + esc(label) + '</option>';
        })).join('');
        const html = '<p class="rcm-meta" style="margin-bottom:1rem;">Reassign the coordinator for <strong>' + esc(group) + '</strong>. Current coordinator: <strong>' + esc(currentName || 'none') + '</strong>.</p>' +
            '<select class="rcm-select" data-reassign-select style="width:100%;">' + options + '</select>' +
            '<div style="display:flex;gap:.6rem;justify-content:flex-end;margin-top:1.1rem;">' +
            '<button type="button" class="rcm-btn rcm-btn-soft-danger" data-rcm-close-inline>Cancel</button>' +
            '<button type="button" class="rcm-btn rcm-btn-primary" data-reassign-save><?= smsIcon('check') ?> Save Assignment</button>' +
            '</div>';
        openModal('Reassign Coordinator', html);
        const overlay = document.querySelector('[data-rcm-modal]');
        overlay.querySelector('[data-rcm-close-inline]').addEventListener('click', closeModal);
        overlay.querySelector('[data-reassign-save]').addEventListener('click', function (e) {
            const select = overlay.querySelector('[data-reassign-select]');
            if (!select.value) { showFlash('Please select a Research Coordinator first.', false); return; }
            const btn = e.currentTarget;
            btn.disabled = true;
            const fd = new FormData();
            fd.append('ajax', 'assign');
            fd.append('_token', CSRF);
            fd.append('group_number', group);
            fd.append('coordinator', select.value);
            fetch(endpoint, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'fetch' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    closeModal();
                    render(data);
                    if (data && data.message) showFlash(data.message, data.ok !== false);
                })
                .catch(function () { showFlash('Could not save the assignment. Please try again.', false); })
                .finally(function () { btn.disabled = false; });
        });
    }

    function bindAll() {
        bindCardFilters();
        bindPagers();
        bindActions();
        bindMenus();
    }

    bindAll();
    document.addEventListener('click', closeMenus);
    document.querySelectorAll('[data-rcm-close]').forEach(function (b) { b.addEventListener('click', closeModal); });
    const modalOverlay = document.querySelector('[data-rcm-modal]');
    if (modalOverlay) modalOverlay.addEventListener('click', function (e) { if (e.target === modalOverlay) closeModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });
    pollNow();
    pollTimer = setInterval(pollNow, 2000);
});
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
