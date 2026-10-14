<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once __DIR__ . '/../includes/panelist-workflow.php';

requireAuth();
if (!smsRoleAllowedForModule(['crad_officer'], 'crad')) {
    http_response_code(403);
    exit('Forbidden');
}

$pdo = cradDb();
if (!$pdo instanceof PDO) {
    http_response_code(503);
    exit('CRAD database unavailable.');
}
cradEnsurePanelistWorkflowSchema($pdo);
cradEnsurePanelistAssignmentSchema($pdo);
$panelAssignmentsTable = sms2_quote_table(crad_table('panel_pool_assignments'));
$pdo->exec(
    "UPDATE `crad_panelist_pool` p
        SET p.panel_status = 'Available', p.updated_at = CURRENT_TIMESTAMP
      WHERE p.panel_status = 'Assigned'
        AND NOT EXISTS (
            SELECT 1 FROM {$panelAssignmentsTable} pa
             WHERE pa.pool_id = p.id AND pa.assignment_status = 'Assigned'
        )
        AND NOT EXISTS (
            SELECT 1
              FROM {$panelAssignmentsTable} pa
              JOIN `crad_research_defense_schedules` rds
                ON rds.research_group_id = pa.research_group_id
               AND rds.defense_type = pa.defense_type
             WHERE pa.pool_id = p.id AND pa.assignment_status = 'Confirmed'
               AND LOWER(rds.status) IN ('scheduled', 'finalized', 'final')
               AND COALESCE(rds.defense_end_datetime, DATE_ADD(rds.defense_datetime, INTERVAL 2 HOUR)) > NOW()
        )"
);

$defenseTypes = ['Title Defense', 'Proposal Defense', 'Final Defense'];
$notice = '';
$error = '';
$selectedGroupId = max(0, (int) ($_REQUEST['research_group_id'] ?? 0));
$selectedDefenseType = trim((string) ($_REQUEST['defense_type'] ?? 'Proposal Defense'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['pool_action'] ?? ''));
    if (!csrfVerify()) {
        $error = 'Security check failed. Refresh the page and try again.';
    } elseif ($action === 'update_status') {
        $poolId = max(0, (int) ($_POST['pool_id'] ?? 0));
        $panelStatus = trim((string) ($_POST['panel_status'] ?? ''));
        if ($poolId <= 0 || !in_array($panelStatus, ['Pending', 'Eligible', 'Available', 'Inactive'], true)) {
            $error = 'Choose a valid panelist pool status. Assignment status is controlled by the panel assignment workflow.';
        } else {
            $eligible = $pdo->prepare(
                "SELECT p.id FROM `crad_panelist_pool` p
                  JOIN `crad_panelist_applications` a ON a.id = p.application_id
                 WHERE p.id = ? AND a.status = 'Approved'
                   AND NOT EXISTS (
                       SELECT 1 FROM {$panelAssignmentsTable} pa
                        WHERE pa.pool_id = p.id AND pa.assignment_status IN ('Assigned', 'Confirmed')
                   )"
            );
            $eligible->execute([$poolId]);
            if ($eligible->fetchColumn() === false) {
                $error = 'The panelist is not approved, does not exist, or has an active assignment.';
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE `crad_panelist_pool` p
                     JOIN `crad_panelist_applications` a ON a.id = p.application_id
                        SET p.panel_status = ?, p.updated_at = CURRENT_TIMESTAMP
                      WHERE p.id = ? AND a.status = 'Approved'
                        AND NOT EXISTS (
                            SELECT 1 FROM {$panelAssignmentsTable} pa
                             WHERE pa.pool_id = p.id AND pa.assignment_status IN ('Assigned', 'Confirmed')
                        )"
                );
                $stmt->execute([$panelStatus, $poolId]);
                $notice = 'Panelist pool status updated to ' . $panelStatus . '.';
            }
        }
    } elseif ($action === 'save_panel') {
        $selectedGroupId = max(0, (int) ($_POST['research_group_id'] ?? 0));
        $selectedDefenseType = trim((string) ($_POST['defense_type'] ?? ''));
        $poolIds = array_values(array_unique(array_filter(
            array_map(static fn($id): int => is_scalar($id) ? (int) $id : 0, (array) ($_POST['panel_pool_ids'] ?? [])),
            static fn(int $id): bool => $id > 0
        )));
        $chairValue = $_POST['chair_pool_id'] ?? 0;
        $chairPoolId = is_scalar($chairValue) ? max(0, (int) $chairValue) : 0;
        if ($selectedGroupId <= 0 || !in_array($selectedDefenseType, $defenseTypes, true)
            || count($poolIds) < 2 || !in_array($chairPoolId, $poolIds, true)) {
            $error = 'Select a research group, a defense stage, at least two panelists, and exactly one chair.';
        } else {
            try {
                $pdo->beginTransaction();
                $scheduledCheck = $pdo->prepare(
                    "SELECT id FROM `crad_research_defense_schedules`
                      WHERE research_group_id = ? AND defense_type = ?
                        AND LOWER(status) IN ('scheduled', 'finalized', 'final')
                      LIMIT 1 FOR UPDATE"
                );
                $scheduledCheck->execute([$selectedGroupId, $selectedDefenseType]);
                if ($scheduledCheck->fetchColumn() !== false) {
                    throw new RuntimeException('This panel already has a confirmed defense schedule. Cancel or postpone that schedule before changing its panel.');
                }
                $groupStmt = $pdo->prepare(
                    "SELECT g.id, g.group_number, g.proposal_id, g.proposal_number,
                            COALESCE(NULLIF(g.group_name, ''), g.group_number, 'Research Group') AS research_group,
                            COALESCE(NULLIF(g.research_title, ''), p.research_title, '') AS research_title
                       FROM `crad_research_groups` g
                       JOIN `crad_research_proposals` p ON p.id = g.proposal_id
                      WHERE g.id = ? LIMIT 1 FOR UPDATE"
                );
                $groupStmt->execute([$selectedGroupId]);
                $group = $groupStmt->fetch(PDO::FETCH_ASSOC);
                if (!$group) {
                    throw new RuntimeException('The selected research group is no longer available.');
                }

                $placeholders = implode(',', array_fill(0, count($poolIds), '?'));
                $poolStmt = $pdo->prepare(
                    "SELECT p.id, p.panel_status, p.defense_preferences, a.status AS application_status
                       FROM `crad_panelist_pool` p
                       JOIN `crad_panelist_applications` a ON a.id = p.application_id
                      WHERE p.id IN ({$placeholders})"
                );
                $poolStmt->execute($poolIds);
                $qualifiedIds = [];
                foreach ($poolStmt->fetchAll(PDO::FETCH_ASSOC) as $poolRow) {
                    if ($poolRow['application_status'] === 'Approved'
                        && in_array($poolRow['panel_status'], ['Eligible', 'Available', 'Assigned'], true)
                        && cradPanelistQualifiedForDefenseType($poolRow, $selectedDefenseType)) {
                        $qualifiedIds[] = (int) $poolRow['id'];
                    }
                }
                sort($qualifiedIds);
                $expectedIds = $poolIds;
                sort($expectedIds);
                if ($qualifiedIds !== $expectedIds) {
                    throw new RuntimeException('Only approved, eligible panelists may be assigned.');
                }

                $deactivate = $pdo->prepare(
                    "UPDATE {$panelAssignmentsTable}
                        SET assignment_status = 'Removed', updated_at = CURRENT_TIMESTAMP
                      WHERE research_group_id = ? AND defense_type = ?
                        AND assignment_status IN ('Assigned', 'Confirmed')"
                );
                $deactivate->execute([$selectedGroupId, $selectedDefenseType]);

                $upsert = $pdo->prepare(
                    "INSERT INTO {$panelAssignmentsTable}
                        (pool_id, research_group_id, defense_type, panel_role, assignment_status, assigned_by)
                     VALUES (?, ?, ?, ?, 'Assigned', ?)
                     ON DUPLICATE KEY UPDATE panel_role = VALUES(panel_role),
                        assignment_status = 'Assigned', assigned_by = VALUES(assigned_by),
                        assigned_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP"
                );
                foreach ($poolIds as $poolId) {
                    $upsert->execute([
                        $poolId,
                        $selectedGroupId,
                        $selectedDefenseType,
                        $poolId === $chairPoolId ? 'Chair' : 'Member',
                        (int) getCurrentUserId(),
                    ]);
                }

                $pdo->exec(
                    "UPDATE `crad_panelist_pool`
                        SET panel_status = 'Available', updated_at = CURRENT_TIMESTAMP
                      WHERE panel_status = 'Assigned'
                        AND NOT EXISTS (
                            SELECT 1 FROM {$panelAssignmentsTable} pa
                             WHERE pa.pool_id = crad_panelist_pool.id
                               AND pa.assignment_status IN ('Assigned', 'Confirmed')
                        )"
                );
                $syncPool = $pdo->prepare(
                    "UPDATE `crad_panelist_pool`
                        SET panel_status = CASE
                            WHEN EXISTS (
                                SELECT 1 FROM {$panelAssignmentsTable} pa
                                 WHERE pa.pool_id = crad_panelist_pool.id
                                   AND pa.assignment_status IN ('Assigned', 'Confirmed')
                            ) THEN 'Assigned'
                            WHEN panel_status = 'Assigned' THEN 'Available'
                            ELSE panel_status END,
                            updated_at = CURRENT_TIMESTAMP
                      WHERE id IN ({$placeholders})"
                );
                $syncPool->execute($poolIds);

                $pdo->commit();
                $notice = 'Panel configuration saved. Assignment is recorded for the selected research group and defense stage.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('CRAD panel assignment save failed: ' . $e->getMessage());
                $error = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to save the panel assignment.';
            }
        }
    } elseif ($action === 'save_schedule') {
        $selectedGroupId = max(0, (int) ($_POST['research_group_id'] ?? 0));
        $selectedDefenseType = trim((string) ($_POST['defense_type'] ?? ''));
        $venue = trim((string) ($_POST['venue'] ?? ''));
        $startInput = trim((string) ($_POST['defense_datetime'] ?? ''));
        $endInput = trim((string) ($_POST['defense_end_datetime'] ?? ''));
        $start = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $startInput, new DateTimeZone('Asia/Manila'));
        $end = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $endInput, new DateTimeZone('Asia/Manila'));
        $dateErrors = DateTimeImmutable::getLastErrors();
        if ($selectedGroupId <= 0 || !in_array($selectedDefenseType, $defenseTypes, true)
            || $venue === '' || !$start || !$end
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $end <= $start || $end->getTimestamp() - $start->getTimestamp() > 12 * 3600
            || $end->format('Y-m-d') !== $start->format('Y-m-d')
            || $start <= new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'))) {
            $error = 'Enter a valid group, defense stage, venue, and start/end time (maximum duration 12 hours).';
        } else {
            try {
                $pdo->beginTransaction();
                $groupStmt = $pdo->prepare(
                    "SELECT g.id, g.group_number, g.proposal_id, g.proposal_number,
                            COALESCE(NULLIF(g.group_name, ''), g.group_number, 'Research Group') AS research_group,
                            COALESCE(NULLIF(g.research_title, ''), p.research_title, '') AS research_title,
                            (SELECT aa.adviser_name
                               FROM `crad_research_adviser_assignments` aa
                              WHERE aa.assignment_status IN ('Assigned', 'Confirmed')
                                AND (aa.research_group_id = g.id OR aa.group_number = g.group_number OR aa.proposal_id = g.proposal_id)
                              ORDER BY aa.assigned_at DESC, aa.updated_at DESC, aa.id DESC LIMIT 1) AS adviser_name
                       FROM `crad_research_groups` g
                       JOIN `crad_research_proposals` p ON p.id = g.proposal_id
                      WHERE g.id = ? LIMIT 1 FOR UPDATE"
                );
                $groupStmt->execute([$selectedGroupId]);
                $group = $groupStmt->fetch(PDO::FETCH_ASSOC);
                if (!$group) {
                    throw new RuntimeException('The selected research group is no longer available.');
                }

                $assignmentStmt = $pdo->prepare(
                    "SELECT p.*, pa.panel_role
                       FROM {$panelAssignmentsTable} pa
                       JOIN `crad_panelist_pool` p ON p.id = pa.pool_id
                       JOIN `crad_panelist_applications` a ON a.id = p.application_id
                      WHERE pa.research_group_id = ? AND pa.defense_type = ?
                        AND pa.assignment_status IN ('Assigned', 'Confirmed') AND a.status = 'Approved'
                      ORDER BY CASE pa.panel_role WHEN 'Chair' THEN 0 ELSE 1 END, p.applicant_name"
                );
                $assignmentStmt->execute([$selectedGroupId, $selectedDefenseType]);
                $assignedPanelists = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $chairs = array_filter($assignedPanelists, static fn(array $row): bool => $row['panel_role'] === 'Chair');
                if (count($assignedPanelists) < 2 || count($chairs) !== 1) {
                    throw new RuntimeException('Configure at least two approved panelists and exactly one panel chair before scheduling.');
                }

                foreach ($assignedPanelists as $panelist) {
                    if (!cradPanelistQualifiedForDefenseType($panelist, $selectedDefenseType)) {
                        throw new RuntimeException($panelist['applicant_name'] . ' did not select ' . $selectedDefenseType . ' as a preferred defense type.');
                    }
                    $availabilityConflicts = cradPanelAvailabilityConflicts($panelist, $start, $end);
                    if ($availabilityConflicts !== []) {
                        throw new RuntimeException(implode(' ', $availabilityConflicts));
                    }
                }

                $existing = $pdo->prepare(
                    "SELECT id, research_group_id, defense_type, group_number, venue, defense_datetime,
                            COALESCE(defense_end_datetime, DATE_ADD(defense_datetime, INTERVAL 2 HOUR)) AS defense_end_datetime
                       FROM `crad_research_defense_schedules`
                      WHERE defense_datetime IS NOT NULL
                        AND LOWER(status) IN ('scheduled', 'finalized', 'final')
                        AND defense_datetime < ? AND COALESCE(defense_end_datetime, DATE_ADD(defense_datetime, INTERVAL 2 HOUR)) > ?
                      FOR UPDATE"
                );
                $existing->execute([$end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')]);
                $overlapping = $existing->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $scheduleIdStmt = $pdo->prepare(
                    'SELECT id FROM `crad_research_defense_schedules`
                      WHERE (research_group_id = ? OR (research_group_id IS NULL AND group_number = ?))
                        AND defense_type = ? ORDER BY id DESC LIMIT 1'
                );
                $scheduleIdStmt->execute([$selectedGroupId, $group['group_number'], $selectedDefenseType]);
                $currentScheduleId = (int) ($scheduleIdStmt->fetchColumn() ?: 0);
                $poolIds = array_map(static fn(array $row): int => (int) $row['id'], $assignedPanelists);
                $scheduleById = [];
                foreach ($overlapping as $row) {
                    $id = (int) $row['id'];
                    if ($id === $currentScheduleId) {
                        continue;
                    }
                    $scheduleById[$id] = $row;
                    if (strcasecmp(trim((string) $row['venue']), $venue) === 0) {
                        throw new RuntimeException('The selected room is already reserved during this time.');
                    }
                }
                if ($scheduleById !== []) {
                    $poolPlaceholders = implode(',', array_fill(0, count($poolIds), '?'));
                    $assignmentPairs = [];
                    $pairParams = [];
                    foreach ($scheduleById as $row) {
                        $assignmentPairs[] = '(pa.research_group_id = ? AND pa.defense_type = ?)';
                        $pairParams[] = (int) $row['research_group_id'];
                        $pairParams[] = (string) $row['defense_type'];
                    }
                    $conflictStmt = $pdo->prepare(
                        "SELECT DISTINCT p.applicant_name
                           FROM {$panelAssignmentsTable} pa
                           JOIN `crad_panelist_pool` p ON p.id = pa.pool_id
                          WHERE (" . implode(' OR ', $assignmentPairs) . ")
                            AND pa.pool_id IN ({$poolPlaceholders})
                            AND pa.assignment_status IN ('Assigned', 'Confirmed')"
                    );
                    $conflictStmt->execute(array_merge($pairParams, $poolIds));
                    $conflictedNames = $conflictStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                    if ($conflictedNames !== []) {
                        throw new RuntimeException('Panelist time conflict: ' . implode(', ', $conflictedNames) . '.');
                    }
                }

                $panelNames = implode(', ', array_map(
                    static fn(array $row): string => (string) $row['applicant_name'],
                    $assignedPanelists
                ));
                $chairName = (string) array_values($chairs)[0]['applicant_name'];
                if ($currentScheduleId > 0) {
                    $save = $pdo->prepare(
                        "UPDATE `crad_research_defense_schedules`
                            SET proposal_id = ?, proposal_number = ?, group_number = ?, research_group = ?,
                                research_title = ?, adviser_name = ?, panel_members = ?, panel_chair = ?,
                                venue = ?, defense_datetime = ?, defense_end_datetime = ?,
                                status = 'Scheduled', recorded_by = ?, updated_at = CURRENT_TIMESTAMP
                          WHERE id = ?"
                    );
                    $save->execute([
                        $group['proposal_id'], $group['proposal_number'], $group['group_number'], $group['research_group'],
                        $group['research_title'], $group['adviser_name'], $panelNames, $chairName, $venue,
                        $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'),
                        (int) getCurrentUserId(), $currentScheduleId,
                    ]);
                } else {
                    $save = $pdo->prepare(
                        "INSERT INTO `crad_research_defense_schedules`
                            (research_group_id, proposal_id, proposal_number, group_number, research_group, research_title,
                             adviser_name, panel_members, panel_chair, defense_type, venue, defense_datetime,
                             defense_end_datetime, status, recorded_by)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Scheduled', ?)"
                    );
                    $save->execute([
                        $selectedGroupId, $group['proposal_id'], $group['proposal_number'], $group['group_number'],
                        $group['research_group'], $group['research_title'], $group['adviser_name'], $panelNames, $chairName,
                        $selectedDefenseType, $venue, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'),
                        (int) getCurrentUserId(),
                    ]);
                }
                $confirmAssignments = $pdo->prepare(
                    "UPDATE {$panelAssignmentsTable}
                        SET assignment_status = 'Confirmed', updated_at = CURRENT_TIMESTAMP
                      WHERE research_group_id = ? AND defense_type = ? AND assignment_status = 'Assigned'"
                );
                $confirmAssignments->execute([$selectedGroupId, $selectedDefenseType]);
                $pdo->commit();
                $notice = 'Defense schedule confirmed. The schedule is now available to the existing CRAD defense calendar.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('CRAD defense schedule save failed: ' . $e->getMessage());
                $error = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to confirm the defense schedule.';
            }
        }
    }
}

$groups = $pdo->query(
    "SELECT g.id, g.group_number,
            COALESCE(NULLIF(g.group_name, ''), g.group_number, 'Research Group') AS research_group,
            COALESCE(NULLIF(g.research_title, ''), p.research_title, '') AS research_title
       FROM `crad_research_groups` g
       JOIN `crad_research_proposals` p ON p.id = g.proposal_id
      WHERE LOWER(g.status) NOT IN ('rejected', 'cancelled')
      ORDER BY g.group_number, g.id DESC"
)->fetchAll(PDO::FETCH_ASSOC) ?: [];
$panelists = $pdo->query(
    "SELECT p.*, a.status AS application_status
       FROM `crad_panelist_pool` p
       JOIN `crad_panelist_applications` a ON a.id = p.application_id
      WHERE a.status = 'Approved'
      ORDER BY p.applicant_name"
)->fetchAll(PDO::FETCH_ASSOC) ?: [];
$assignmentHistory = $pdo->query(
    "SELECT pa.assignment_status, pa.panel_role, pa.defense_type, pa.assigned_at,
            p.applicant_name, g.group_number,
            COALESCE(NULLIF(g.research_title, ''), pr.research_title, g.group_name, g.group_number) AS research_title
       FROM {$panelAssignmentsTable} pa
       JOIN `crad_panelist_pool` p ON p.id = pa.pool_id
       LEFT JOIN `crad_research_groups` g ON g.id = pa.research_group_id
       LEFT JOIN `crad_research_proposals` pr ON pr.id = g.proposal_id
      ORDER BY pa.assigned_at DESC, pa.id DESC
      LIMIT 100"
)->fetchAll(PDO::FETCH_ASSOC) ?: [];
$currentAssignments = [];
$currentSchedule = null;
if ($selectedGroupId > 0 && in_array($selectedDefenseType, $defenseTypes, true)) {
    $stmt = $pdo->prepare(
        "SELECT pa.pool_id, pa.panel_role, pa.assignment_status, p.applicant_name
           FROM {$panelAssignmentsTable} pa
           JOIN `crad_panelist_pool` p ON p.id = pa.pool_id
          WHERE pa.research_group_id = ? AND pa.defense_type = ?
            AND pa.assignment_status IN ('Assigned', 'Confirmed')
          ORDER BY pa.panel_role DESC, p.applicant_name"
    );
    $stmt->execute([$selectedGroupId, $selectedDefenseType]);
    $currentAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $stmt = $pdo->prepare(
        "SELECT venue, defense_datetime, defense_end_datetime, status
           FROM `crad_research_defense_schedules`
          WHERE (research_group_id = ? OR (research_group_id IS NULL AND group_number = ?))
            AND defense_type = ? ORDER BY id DESC LIMIT 1"
    );
    $selectedGroupNumber = '';
    foreach ($groups as $group) {
        if ((int) $group['id'] === $selectedGroupId) {
            $selectedGroupNumber = (string) $group['group_number'];
            break;
        }
    }
    $stmt->execute([$selectedGroupId, $selectedGroupNumber, $selectedDefenseType]);
    $currentSchedule = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
$assignedIds = array_map(static fn(array $row): int => (int) $row['pool_id'], $currentAssignments);
$chairId = 0;
foreach ($currentAssignments as $assignment) {
    if ($assignment['panel_role'] === 'Chair') {
        $chairId = (int) $assignment['pool_id'];
        break;
    }
}
$scheduleStart = $currentSchedule && !empty($currentSchedule['defense_datetime'])
    ? date('Y-m-d\TH:i', strtotime((string) $currentSchedule['defense_datetime']))
    : '';
$scheduleEnd = $currentSchedule && !empty($currentSchedule['defense_end_datetime'])
    ? date('Y-m-d\TH:i', strtotime((string) $currentSchedule['defense_end_datetime']))
    : '';

$pageTitle = 'Panel Configuration';
$activeModule = 'crad';
$activePage = 'panel-configuration';
$breadcrumbs = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Panelist Applications', 'url' => BASE_URL . '/modules/crad/pages/panelist-applications.php'],
    ['label' => $pageTitle, 'url' => null],
];
require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<main class="container-fluid py-3">
    <header class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div>
            <span class="text-primary text-uppercase fw-bold small">CRAD Officer · Panel assignment</span>
            <h1 class="h3 mb-1">Panel configuration &amp; defense scheduling</h1>
            <p class="text-muted mb-0">Select approved panelists for an existing research group, designate one chair, then confirm a conflict-checked defense schedule.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= e(BASE_URL . '/modules/crad/pages/panelist-applications.php') ?>">Back to applications</a>
    </header>

    <?php if ($notice !== ''): ?><div class="alert alert-success"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <?php if ($groups): ?>
    <form method="get" class="card border-0 shadow-sm mb-4">
        <div class="card-body row g-3 align-items-end">
            <div class="col-lg-7">
                <label class="form-label" for="research_group_id">Research group / title</label>
                <select class="form-select" id="research_group_id" name="research_group_id" required>
                    <option value="">Select a CRAD research group</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?= (int) $group['id'] ?>" <?= $selectedGroupId === (int) $group['id'] ? 'selected' : '' ?>>
                            <?= e((string) $group['group_number'] . ' · ' . ($group['research_title'] ?: $group['research_group'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label" for="defense_type">Defense stage</label>
                <select class="form-select" id="defense_type" name="defense_type">
                    <?php foreach ($defenseTypes as $type): ?><option value="<?= e($type) ?>" <?= $selectedDefenseType === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2"><button class="btn btn-outline-primary w-100" type="submit">Load workflow</button></div>
        </div>
    </form>

    <?php if ($selectedGroupId > 0): ?>
    <section class="row g-4 mb-4">
        <div class="col-xl-7">
            <form method="post" class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3"><strong>1 · Configure the panel</strong></div>
                <div class="card-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="pool_action" value="save_panel">
                    <input type="hidden" name="research_group_id" value="<?= $selectedGroupId ?>">
                    <input type="hidden" name="defense_type" value="<?= e($selectedDefenseType) ?>">
                    <p class="small text-muted">Choose at least two approved pool panelists. Expertise, qualifications, preferred defense types, and availability are shown to help the officer decide. These are recommendations, not automatic decisions.</p>
                    <?php if ($panelists): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Select</th><th>Panelist qualification profile</th><th>Chair</th></tr></thead>
                                <tbody>
                                <?php foreach ($panelists as $panelist): $pid = (int) $panelist['id']; ?>
                                    <tr>
                                        <td><input class="form-check-input" type="checkbox" name="panel_pool_ids[]" value="<?= $pid ?>" <?= in_array($pid, $assignedIds, true) ? 'checked' : '' ?> <?= !in_array($panelist['panel_status'], ['Eligible', 'Available', 'Assigned'], true) ? 'disabled' : '' ?> aria-label="Select <?= e((string) $panelist['applicant_name']) ?>"></td>
                                        <td>
                                            <strong><?= e((string) $panelist['applicant_name']) ?></strong>
                                            <span class="badge text-bg-light"><?= e((string) $panelist['panel_status']) ?></span>
                                            <div class="small text-muted"><?= e((string) $panelist['institution']) ?> · <?= e((string) $panelist['department']) ?> · <?= e((string) $panelist['position']) ?></div>
                                            <div class="small">Expertise: <?= e(trim((string) $panelist['primary_specialization'] . ' / ' . (string) $panelist['secondary_specialization'], ' /')) ?></div>
                                            <div class="small text-muted"><?= e((string) $panelist['years_research_experience']) ?> years research experience · Prefers: <?= e((string) $panelist['defense_preferences']) ?></div>
                                            <div class="small text-muted">Availability: <?= e((string) $panelist['available_days']) ?> <?= e((string) $panelist['available_time_ranges']) ?></div>
                                        </td>
                                        <td><input class="form-check-input" type="radio" name="chair_pool_id" value="<?= $pid ?>" <?= $chairId === $pid ? 'checked' : '' ?> <?= !in_array($panelist['panel_status'], ['Eligible', 'Available', 'Assigned'], true) ? 'disabled' : '' ?> aria-label="Designate <?= e((string) $panelist['applicant_name']) ?> as chair"></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No approved applicants are in the panelist pool yet.</div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-end"><button class="btn btn-primary" type="submit" <?= !$panelists ? 'disabled' : '' ?>>Save panel configuration</button></div>
            </form>
        </div>
        <div class="col-xl-5">
            <form method="post" class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3"><strong>2 · Schedule the defense</strong></div>
                <div class="card-body">
                    <?= csrfField() ?>
                    <input type="hidden" name="pool_action" value="save_schedule">
                    <input type="hidden" name="research_group_id" value="<?= $selectedGroupId ?>">
                    <input type="hidden" name="defense_type" value="<?= e($selectedDefenseType) ?>">
                    <?php if ($currentSchedule): ?>
                        <div class="alert alert-<?= strtolower((string) $currentSchedule['status']) === 'scheduled' ? 'success' : 'info' ?>">
                            Current schedule: <?= e((string) $currentSchedule['status']) ?>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3"><label class="form-label" for="venue">Room / venue</label><input class="form-control" id="venue" name="venue" maxlength="120" required value="<?= e((string) ($currentSchedule['venue'] ?? '')) ?>"></div>
                    <div class="mb-3"><label class="form-label" for="defense_datetime">Start date and time</label><input class="form-control" type="datetime-local" id="defense_datetime" name="defense_datetime" required value="<?= e($scheduleStart) ?>"></div>
                    <div class="mb-3"><label class="form-label" for="defense_end_datetime">End date and time</label><input class="form-control" type="datetime-local" id="defense_end_datetime" name="defense_end_datetime" required value="<?= e($scheduleEnd) ?>"></div>
                    <p class="small text-muted mb-0">The system checks provided panelist availability, overlapping panel assignments, room reservations, and existing CRAD schedules before confirmation. CRAD retains final approval authority.</p>
                </div>
                <div class="card-footer bg-white text-end"><button class="btn btn-success" type="submit">Check conflicts &amp; confirm schedule</button></div>
            </form>
        </div>
    </section>
    <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">No CRAD research groups are available yet. Add or approve a group before configuring its panel.</div>
    <?php endif; ?>

    <section class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><strong>Panelist pool</strong><span class="text-muted small ms-2"><?= count($panelists) ?> approved applicants</span></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Panelist</th><th>Institution / position</th><th>Research expertise</th><th>Availability</th><th>Pool status</th></tr></thead>
                <tbody>
                <?php foreach ($panelists as $panelist): ?>
                    <tr>
                        <td><strong><?= e((string) $panelist['applicant_name']) ?></strong><div class="small text-muted"><?= e((string) $panelist['applicant_email']) ?></div></td>
                        <td><?= e((string) $panelist['institution']) ?><div class="small text-muted"><?= e((string) $panelist['department'] . ' · ' . (string) $panelist['position']) ?></div></td>
                        <td><?= e((string) $panelist['primary_specialization']) ?><div class="small text-muted"><?= e((string) $panelist['research_areas']) ?></div></td>
                        <td><?= e((string) $panelist['available_days']) ?><div class="small text-muted"><?= e((string) $panelist['available_time_ranges']) ?></div></td>
                        <td>
                            <?php if (in_array((string) $panelist['panel_status'], ['Assigned'], true)): ?>
                                <span class="badge text-bg-primary">Assigned</span>
                            <?php else: ?>
                                <form method="post" class="d-flex gap-2">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="pool_action" value="update_status">
                                    <input type="hidden" name="pool_id" value="<?= (int) $panelist['id'] ?>">
                                    <select class="form-select form-select-sm" name="panel_status" aria-label="Pool status for <?= e((string) $panelist['applicant_name']) ?>">
                                        <?php foreach (['Pending', 'Eligible', 'Available', 'Inactive'] as $status): ?>
                                            <option value="<?= e($status) ?>" <?= (string) $panelist['panel_status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Update</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$panelists): ?><tr><td colspan="5" class="text-center text-muted py-4">No approved panelists are in the pool yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3"><strong>Assignment history</strong><span class="text-muted small ms-2">Most recent 100 panel assignments</span></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Panelist</th><th>Research group / title</th><th>Defense stage</th><th>Role</th><th>Status</th><th>Assigned</th></tr></thead>
                <tbody>
                <?php foreach ($assignmentHistory as $entry): ?>
                    <tr>
                        <td><?= e((string) $entry['applicant_name']) ?></td>
                        <td><?= e((string) ($entry['group_number'] ?? 'Group record unavailable')) ?><div class="small text-muted"><?= e((string) ($entry['research_title'] ?? '')) ?></div></td>
                        <td><?= e((string) $entry['defense_type']) ?></td>
                        <td><?= e((string) $entry['panel_role']) ?></td>
                        <td><span class="badge text-bg-light"><?= e((string) $entry['assignment_status']) ?></span></td>
                        <td><?= e((string) $entry['assigned_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$assignmentHistory): ?><tr><td colspan="6" class="text-center text-muted py-4">No panel assignments have been recorded yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
