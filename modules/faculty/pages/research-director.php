<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/research-progress-helpers.php';
require_once ROOT_PATH . '/modules/faculty/includes/research-director-panel-assignment.php';

requireAuth();
if (!smsCanManageDefenseScheduling()) {
    http_response_code(403);
    exit('Forbidden');
}

$directorPages = [
    'overview' => ['title' => 'Overview', 'group' => 'Dashboard', 'icon' => 'fa-home'],
    'defense-scheduling-queue' => ['title' => 'Defense Scheduling Queue', 'group' => 'Defense Management', 'icon' => 'fa-list-alt'],
    'verify-research-defense' => ['title' => 'Verify Research for Defense', 'group' => 'Defense Management', 'icon' => 'fa-check-double'],
    'defense-schedule' => ['title' => 'Defense Schedule', 'group' => 'Defense Management', 'icon' => 'fa-calendar-check'],
    'manual-scheduling-optimizer' => ['title' => 'AI Scheduling Optimizer', 'group' => 'Manual Scheduling', 'icon' => 'fa-calendar-check'],
    'proposed-schedules' => ['title' => 'Proposed Schedules', 'group' => 'Manual Scheduling', 'icon' => 'fa-calendar-plus'],
    'alternative-time-slots' => ['title' => 'Alternative Time Slots', 'group' => 'Manual Scheduling', 'icon' => 'fa-clock'],
    'calendar' => ['title' => 'Calendar', 'group' => 'Schedule Management', 'icon' => 'fa-calendar-alt'],
    'venues' => ['title' => 'Venues', 'group' => 'Schedule Management', 'icon' => 'fa-map-marker-alt'],
    'finalize-defense-schedule' => ['title' => 'Finalize Defense Schedule', 'group' => 'Schedule Management', 'icon' => 'fa-clipboard-check'],
    'researchers' => ['title' => 'Researchers', 'group' => 'Defense Participants', 'icon' => 'fa-users'],
    'advisers' => ['title' => 'Advisers', 'group' => 'Defense Participants', 'icon' => 'fa-user-tie'],
    'panel-members' => ['title' => 'Panel Members', 'group' => 'Defense Participants', 'icon' => 'fa-users'],
    'notifications' => ['title' => 'Notifications', 'group' => 'Communication', 'icon' => 'fa-bell'],
    'defense-results' => ['title' => 'Defense Results', 'group' => 'Defense Results', 'icon' => 'fa-chart-bar'],
    'digital-scores' => ['title' => 'Digital Scores', 'group' => 'Defense Results', 'icon' => 'fa-poll'],
    'defense-history' => ['title' => 'Defense History', 'group' => 'Defense Results', 'icon' => 'fa-history'],
    'proceed-archiving' => ['title' => 'Proceed to Archiving', 'group' => 'Archiving', 'icon' => 'fa-folder-open'],
];

$view = strtolower(trim((string) ($_GET['view'] ?? 'overview')));
if ($view === '') {
    $view = 'overview';
}
if ($view === 'ai-scheduling-optimizer') {
    $view = 'manual-scheduling-optimizer';
}
if (!isset($directorPages[$view])) {
    $view = 'overview';
}

$pageInfo = $directorPages[$view];
$pageTitle = $pageInfo['title'];
$activeModule = 'faculty';
$activePage = $view === 'overview' ? '' : $view;
$breadcrumbs = [
    ['label' => 'Research Director', 'url' => BASE_URL . '/modules/faculty/pages/research-director.php'],
    ['label' => $pageTitle, 'url' => null],
];

const RD_SCHEDULE_MAX_PANEL_MEMBERS = 3;

function rdScheduleUrl(string $view, array $params = []): string
{
    $params = ['view' => $view] + $params;
    return BASE_URL . '/modules/faculty/pages/research-director.php?' . http_build_query($params);
}

function rdScheduleTypedUrl(string $view, string $defenseType, array $params = []): string
{
    if ($defenseType === CRAD_DEFENSE_TYPE_FINAL) {
        $params = ['defense_type' => $defenseType] + $params;
    }
    return rdScheduleUrl($view, $params);
}

function rdScheduleSidebarActivePage(string $view, string $defenseType): string
{
    if ($defenseType !== CRAD_DEFENSE_TYPE_FINAL) {
        return $view === 'overview' ? '' : $view;
    }

    $finalDefenseSidebarMap = [
        'defense-scheduling-queue' => 'final-defense-scheduling-queue',
        'manual-scheduling-optimizer' => 'final-defense-manual-scheduling',
        'proposed-schedules' => 'final-defense-proposed-schedules',
        'finalize-defense-schedule' => 'final-defense-finalize-schedule',
    ];

    return $finalDefenseSidebarMap[$view] ?? ($view === 'overview' ? '' : $view);
}

function rdScheduleEnsureSchema(PDO $pdo): void
{
    $columns = [
        'venue_id' => "ALTER TABLE research_defense_schedules ADD venue_id INT UNSIGNED DEFAULT NULL AFTER venue",
        'defense_end_datetime' => "ALTER TABLE research_defense_schedules ADD defense_end_datetime DATETIME DEFAULT NULL AFTER defense_datetime",
        'defense_type' => "ALTER TABLE research_defense_schedules ADD defense_type VARCHAR(40) NOT NULL DEFAULT 'Pre-Oral' AFTER defense_end_datetime",
        'finalized_by' => "ALTER TABLE research_defense_schedules ADD finalized_by INT UNSIGNED DEFAULT NULL AFTER recorded_by",
        'finalized_at' => "ALTER TABLE research_defense_schedules ADD finalized_at DATETIME DEFAULT NULL AFTER finalized_by",
    ];
    foreach ($columns as $column => $sql) {
        try {
            if (!$pdo->query("SHOW COLUMNS FROM research_defense_schedules LIKE " . $pdo->quote($column))->fetch()) {
                $pdo->exec($sql);
            }
        } catch (Throwable $e) {
            error_log('RD schedule schema column failed: ' . $column . ' ' . $e->getMessage());
        }
    }
    try {
        $legacyUnique = $pdo->query(
            "SHOW INDEX FROM research_defense_schedules
             WHERE Key_name = 'uniq_rds_group_number'
               AND Non_unique = 0"
        )->fetch();
        if ($legacyUnique) {
            $pdo->exec("ALTER TABLE research_defense_schedules DROP INDEX uniq_rds_group_number");
        }
    } catch (Throwable $e) {
        error_log('RD schedule schema legacy unique cleanup failed: ' . $e->getMessage());
    }
    foreach ([
        'idx_rds_group_number' => "ALTER TABLE research_defense_schedules ADD KEY idx_rds_group_number (group_number)",
        'idx_rds_venue_time' => "ALTER TABLE research_defense_schedules ADD KEY idx_rds_venue_time (venue_id, defense_datetime, defense_end_datetime)",
        'idx_rds_group_time' => "ALTER TABLE research_defense_schedules ADD KEY idx_rds_group_time (research_group_id, defense_datetime, defense_end_datetime)",
    ] as $index => $sql) {
        try {
            if (!$pdo->query("SHOW INDEX FROM research_defense_schedules WHERE Key_name = " . $pdo->quote($index))->fetch()) {
                $pdo->exec($sql);
            }
        } catch (Throwable $e) {
            error_log('RD schedule schema index failed: ' . $index . ' ' . $e->getMessage());
        }
    }
}

function rdScheduleDate(string $value, string $format = 'M j, Y h:i A'): string
{
    $time = strtotime($value);
    return $time ? date($format, $time) : '';
}

function rdScheduleStatusKey(string $status): string
{
    $status = strtolower(trim($status));
    if ($status === 'ready for scheduling') {
        return 'ready';
    }
    if ($status === 'needs verification') {
        return 'needs-verification';
    }
    if (in_array($status, ['completed', 'passed'], true)) {
        return 'completed';
    }
    if (in_array($status, ['proposed', 'selected', 'rejected', 'alternative'], true)) {
        return $status === 'alternative' ? 'rejected' : $status;
    }
    return 'scheduled';
}

function rdOfficialTitleApprovalClause(string $alias = 'reg_t'): string
{
    return "{$alias}.status = 'Approved'
        AND {$alias}.coordinator_status = 'Approved'
        AND {$alias}.crad_status = 'Approved'
        AND {$alias}.adviser_signature_data IS NOT NULL AND {$alias}.adviser_signature_data <> ''
        AND {$alias}.coordinator_signature_data IS NOT NULL AND {$alias}.coordinator_signature_data <> ''
        AND {$alias}.crad_signature_data IS NOT NULL AND {$alias}.crad_signature_data <> ''";
}

function rdOfficialRegistrySql(string $groupAlias = 'rg'): string
{
    return "{$groupAlias}.title_approval_id IS NOT NULL
        AND TRIM(COALESCE({$groupAlias}.research_title, '')) <> ''
        AND TRIM(COALESCE({$groupAlias}.academic_year, '')) <> ''
        AND EXISTS (
            SELECT 1
            FROM title_approvals reg_t
            WHERE reg_t.id = {$groupAlias}.title_approval_id
              AND " . rdOfficialTitleApprovalClause('reg_t') . "
              AND (TRIM(COALESCE({$groupAlias}.college_dept, '')) <> '' OR TRIM(COALESCE(reg_t.department, '')) <> '')
        )
        AND EXISTS (
            SELECT 1
            FROM research_coordinator_assignments reg_ca
            WHERE reg_ca.status = 'Active'
              AND (
                    reg_ca.research_group_id = {$groupAlias}.id
                 OR (reg_ca.research_group_id IS NULL AND reg_ca.group_number = {$groupAlias}.group_number)
              )
        )
        AND EXISTS (
            SELECT 1
            FROM research_adviser_assignments reg_aa
            WHERE (
                    reg_aa.research_group_id = {$groupAlias}.id
                 OR (reg_aa.research_group_id IS NULL AND reg_aa.group_number = {$groupAlias}.group_number)
              )
        )";
}

function rdOfficialScheduleJoinSql(): string
{
    return "INNER JOIN research_groups registry_rg
              ON registry_rg.id = rds.research_group_id
             AND " . rdOfficialRegistrySql('registry_rg');
}

function rdIsOfficialResearchGroup(PDO $pdo, int $groupId): bool
{
    if ($groupId < 1) {
        return false;
    }
    $stmt = $pdo->prepare(
        "SELECT 1
         FROM research_groups rg
         WHERE rg.id = ?
           AND " . rdOfficialRegistrySql('rg') . "
         LIMIT 1"
    );
    $stmt->execute([$groupId]);
    return (bool) $stmt->fetchColumn();
}

function rdSchedulePanelRows(PDO $pdo, int $groupId): array
{
    $stmt = $pdo->prepare(
        "SELECT rpa.panel_user_id,
                COALESCE(NULLIF(u.full_name, ''), NULLIF(rpa.panel_name, ''), 'Panel Member') AS panel_name,
                COALESCE(
                    MAX(NULLIF(pma.availability_status, '')),
                    MAX(NULLIF(rpa.availability_status, '')),
                    'Pending'
                ) AS availability_status
         FROM research_panel_assignments rpa
         LEFT JOIN sms2_db.users u ON u.id = rpa.panel_user_id
         LEFT JOIN panel_member_availability pma ON pma.panel_user_id = rpa.panel_user_id
         WHERE rpa.research_group_id = ?
           AND " . rdPanelActiveAssignmentSql('rpa') . "
         GROUP BY rpa.panel_user_id, panel_name
         ORDER BY panel_name ASC"
    );
    $stmt->execute([$groupId]);
    return $stmt->fetchAll() ?: [];
}

function rdSchedulePanelNames(PDO $pdo, int $groupId, string $fallback = ''): array
{
    $names = [];
    $seen = [];
    if ($groupId > 0) {
        foreach (rdSchedulePanelRows($pdo, $groupId) as $panel) {
            $name = trim((string) ($panel['panel_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $panelUserId = (int) ($panel['panel_user_id'] ?? 0);
            $key = $panelUserId > 0 ? 'id:' . $panelUserId : 'name:' . strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $names[] = $name;
        }
    }

    return $names ?: rdPanelNamesFromString($fallback);
}

function rdScheduleReadyGroup(PDO $pdo, int $groupId, string $defenseType = CRAD_DEFENSE_TYPE_PRE_ORAL): ?array
{
    foreach (rdScheduleReadyRows($pdo, $defenseType === CRAD_DEFENSE_TYPE_FINAL, $defenseType) as $row) {
        if ((int) ($row['research_group_id'] ?? 0) === $groupId) {
            return $row;
        }
    }
    return null;
}

function rdScheduleAutoDefenseType(PDO $pdo, int $groupId, string $requestedType): string
{
    if ($requestedType === CRAD_DEFENSE_TYPE_FINAL || $groupId <= 0) {
        return $requestedType;
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT 1
             FROM manuscript_submissions ms
             INNER JOIN manuscript_evaluations me ON me.submission_id = ms.id
             WHERE ms.research_group_id = ?
               AND ms.status = 'Approved'
               AND me.result = 'APPROVED'
             LIMIT 1"
        );
        $stmt->execute([$groupId]);
        if ($stmt->fetchColumn()) {
            return CRAD_DEFENSE_TYPE_FINAL;
        }
    } catch (Throwable $e) {
        error_log('RD automatic defense type lookup failed: ' . $e->getMessage());
    }

    return CRAD_DEFENSE_TYPE_PRE_ORAL;
}

function rdScheduleReadyRows(PDO $pdo, bool $includeScheduled = false, string $defenseType = CRAD_DEFENSE_TYPE_PRE_ORAL): array
{
        // Never run DDL (schema ensure) while a transaction is open — MySQL implicitly
        // commits on ALTER/CREATE and PDO then throws "There is no active transaction".
        if ($defenseType === CRAD_DEFENSE_TYPE_FINAL
            && function_exists('rscEnsureSchema')
            && !$pdo->inTransaction()
        ) {
            rscEnsureSchema($pdo);
        }
        $finalDefenseJoins = '';
        $finalDefenseWhere = '';
        if ($defenseType === CRAD_DEFENSE_TYPE_FINAL) {
                $finalDefenseJoins = "
                 INNER JOIN final_defense_recommendations fdr
                     ON fdr.research_group_id = rg.id
                    AND fdr.status = 'Recommended'
                 INNER JOIN research_services_clearances rsc2
                     ON rsc2.research_group_id = rg.id
                    AND rsc2.research_stage = 'research_2'
                    AND rsc2.status = 'clearance_done'
                    AND TRIM(COALESCE(rsc2.adviser_signature, '')) <> ''
                    AND TRIM(COALESCE(rsc2.crad_signature, '')) <> ''
                 INNER JOIN manuscript_submissions fms
                     ON fms.id = (
                                SELECT ms.id
                                FROM manuscript_submissions ms
                                WHERE ms.research_group_id = rg.id
                                ORDER BY ms.version_number DESC, ms.id DESC
                                LIMIT 1
                     )
                 INNER JOIN manuscript_evaluations fme
                     ON fme.id = (
                                SELECT me.id
                                FROM manuscript_evaluations me
                                WHERE me.submission_id = fms.id
                                ORDER BY me.id DESC
                                LIMIT 1
                     )";
                $finalDefenseWhere = "
                     AND fms.status = 'Approved'
                     AND fme.result = 'APPROVED'";
        }

    $stmt = $pdo->prepare(
        "SELECT
            rg.id AS research_group_id,
            rg.proposal_id,
            rg.title_approval_id,
            rg.proposal_number,
            rg.group_number,
            COALESCE(NULLIF(rg.group_name, ''), rg.group_number, CONCAT('Group ', LPAD(rg.id, 2, '0'))) AS group_name,
            COALESCE(NULLIF(rg.research_title, ''), 'Research title pending') AS research_title,
            rg.academic_year,
            raa.adviser_user_id,
            raa.adviser_name,
            raa.availability_status AS adviser_availability,
            COUNT(DISTINCT rpa.panel_user_id) AS panel_count,
            GROUP_CONCAT(DISTINCT COALESCE(NULLIF(u.full_name, ''), NULLIF(rpa.panel_name, ''), 'Panel Member') ORDER BY COALESCE(NULLIF(u.full_name, ''), NULLIF(rpa.panel_name, ''), 'Panel Member') SEPARATOR '\n') AS panel_members,
            MAX(rpa.updated_at) AS panel_updated_at,
            finalized.id AS finalized_schedule_id,
            GREATEST(
                COALESCE(ch1.updated_at, '1000-01-01 00:00:00'),
                COALESCE(ch2.updated_at, '1000-01-01 00:00:00'),
                COALESCE(ch3.updated_at, '1000-01-01 00:00:00'),
                COALESCE(MAX(rpa.updated_at), '1000-01-01 00:00:00'),
                COALESCE(raa.updated_at, '1000-01-01 00:00:00')
            ) AS updated_at
         FROM research_groups rg
         {$finalDefenseJoins}
         INNER JOIN chapter_submissions ch1 ON ch1.id = (
            SELECT cs1.id FROM chapter_submissions cs1
            WHERE cs1.research_group_id = rg.id AND cs1.chapter_number = 1
            ORDER BY cs1.version_number DESC, cs1.id DESC LIMIT 1
         )
         INNER JOIN chapter_evaluations ce1 ON ce1.submission_id = ch1.id
         INNER JOIN chapter_submissions ch2 ON ch2.id = (
            SELECT cs2.id FROM chapter_submissions cs2
            WHERE cs2.research_group_id = rg.id AND cs2.chapter_number = 2
            ORDER BY cs2.version_number DESC, cs2.id DESC LIMIT 1
         )
         INNER JOIN chapter_evaluations ce2 ON ce2.submission_id = ch2.id
         INNER JOIN chapter_submissions ch3 ON ch3.id = (
            SELECT cs3.id FROM chapter_submissions cs3
            WHERE cs3.research_group_id = rg.id AND cs3.chapter_number = 3
            ORDER BY cs3.version_number DESC, cs3.id DESC LIMIT 1
         )
         INNER JOIN chapter_evaluations ce3 ON ce3.submission_id = ch3.id
         INNER JOIN research_adviser_assignments raa ON raa.id = (
            SELECT raa2.id
            FROM research_adviser_assignments raa2
            WHERE raa2.assignment_status IN ('Assigned', 'Confirmed')
              AND ((raa2.research_group_id IS NOT NULL AND raa2.research_group_id = rg.id)
                OR (raa2.group_number IS NOT NULL AND raa2.group_number <> '' AND raa2.group_number = rg.group_number))
            ORDER BY (raa2.assignment_status = 'Confirmed') DESC,
                     (raa2.assignment_status = 'Assigned') DESC,
                     raa2.updated_at DESC,
                     raa2.id DESC
            LIMIT 1
         )
         INNER JOIN research_panel_assignments rpa
           ON rpa.research_group_id = rg.id
          AND " . rdPanelActiveAssignmentSql('rpa') . "
         LEFT JOIN sms2_db.users u ON u.id = rpa.panel_user_id
         LEFT JOIN research_defense_schedules finalized
           ON finalized.id = (
                SELECT rds.id
                FROM research_defense_schedules rds
                WHERE rds.research_group_id = rg.id
                                    AND rds.defense_type = :ready_defense_type
                  AND LOWER(rds.status) IN ('scheduled', 'finalized', 'final')
                ORDER BY rds.updated_at DESC, rds.id DESC
                LIMIT 1
           )
         WHERE ch1.status = 'Accepted'
           AND ch2.status = 'Accepted'
           AND ch3.status = 'Accepted'
           AND " . rdOfficialRegistrySql('rg') . "
           AND UPPER(REPLACE(ce1.result, ' ', '_')) IN ('APPROVED', 'APPROVED_WITH_REVISION')
           AND UPPER(REPLACE(ce2.result, ' ', '_')) IN ('APPROVED', 'APPROVED_WITH_REVISION')
           AND UPPER(REPLACE(ce3.result, ' ', '_')) IN ('APPROVED', 'APPROVED_WITH_REVISION')
           {$finalDefenseWhere}
         GROUP BY rg.id, rg.proposal_id, rg.title_approval_id, rg.proposal_number, rg.group_number,
                  group_name, research_title, rg.academic_year, raa.adviser_user_id, raa.adviser_name,
                  raa.availability_status, raa.updated_at, finalized.id,
                  ch1.updated_at, ch2.updated_at, ch3.updated_at
         ORDER BY updated_at DESC, rg.id DESC"
    );

    $stmt->execute([':ready_defense_type' => $defenseType]);
    $rows = [];
    foreach (($stmt->fetchAll() ?: []) as $row) {
        if ((int) ($row['panel_count'] ?? 0) !== RD_SCHEDULE_MAX_PANEL_MEMBERS) {
            continue;
        }
        if (!$includeScheduled && !empty($row['finalized_schedule_id'])) {
            continue;
        }
        $rows[] = $row;
    }
    return $rows;
}

function rdScheduleRows(PDO $pdo, array $statuses = [], string $defenseType = CRAD_DEFENSE_TYPE_PRE_ORAL): array
{
    $whereParts = ['rds.defense_type = ?'];
    $params = [];
    $params[] = $defenseType;
    if ($statuses) {
        $whereParts[] = "LOWER(rds.status) IN (" . implode(',', array_fill(0, count($statuses), '?')) . ")";
        $params = array_merge($params, array_map('strtolower', $statuses));
    }
    $where = "WHERE " . implode(' AND ', $whereParts);
    $stmt = $pdo->prepare(
        "SELECT rds.*, rv.venue_name
         FROM research_defense_schedules rds
         " . rdOfficialScheduleJoinSql() . "
         LEFT JOIN research_venues rv ON rv.id = rds.venue_id
         {$where}
         ORDER BY COALESCE(rds.defense_datetime, rds.updated_at) DESC, rds.id DESC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

function rdScheduleOne(PDO $pdo, int $scheduleId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT rds.*, rv.venue_name
         FROM research_defense_schedules rds
         " . rdOfficialScheduleJoinSql() . "
         LEFT JOIN research_venues rv ON rv.id = rds.venue_id
         WHERE rds.id = ?
         LIMIT 1"
    );
    $stmt->execute([$scheduleId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function rdScheduleOverlapWhere(): string
{
    return "rds.id <> :ignore_id
        AND rds.defense_datetime IS NOT NULL
        AND LOWER(rds.status) IN ('proposed', 'selected', 'scheduled', 'finalized', 'final')
        AND rds.defense_datetime < :end_at
        AND COALESCE(rds.defense_end_datetime, DATE_ADD(rds.defense_datetime, INTERVAL 2 HOUR)) > :start_at";
}

function rdScheduleAvailabilityReport(PDO $pdo, array $slot, int $ignoreId = 0): array
{
    $groupId = (int) ($slot['research_group_id'] ?? 0);
    $venueId = (int) ($slot['venue_id'] ?? 0);
    $defenseType = (string) ($slot['defense_type'] ?? CRAD_DEFENSE_TYPE_PRE_ORAL);
    $start = (string) ($slot['defense_datetime'] ?? '');
    $end = (string) ($slot['defense_end_datetime'] ?? '');
    $group = $groupId > 0 ? rdScheduleReadyGroup($pdo, $groupId, $defenseType) : null;
    $panels = $groupId > 0 ? rdSchedulePanelRows($pdo, $groupId) : [];
    $items = [];
    $hasConflict = false;
    $validTime = strtotime($start) !== false && strtotime($end) !== false && strtotime($end) > strtotime($start);

    $addItem = static function (string $label, bool $ok, string $message) use (&$items, &$hasConflict): void {
        $items[] = ['label' => $label, 'ok' => $ok, 'message' => $message];
        if (!$ok) {
            $hasConflict = true;
        }
    };

    if (!$group || !$validTime) {
        $addItem('Researcher', false, !$group ? 'Research group is not ready for scheduling.' : 'Invalid proposed date/time.');
        return ['items' => $items, 'has_conflict' => true, 'panels' => $panels];
    }

    $params = [
        ':ignore_id' => $ignoreId,
        ':start_at' => $start,
        ':end_at' => $end,
    ];
    $overlapSql = rdScheduleOverlapWhere();

    $researcher = $pdo->prepare("SELECT rds.id FROM research_defense_schedules rds " . rdOfficialScheduleJoinSql() . " WHERE {$overlapSql} AND rds.research_group_id = :group_id LIMIT 1");
    $researcher->execute($params + [':group_id' => $groupId]);
    $researcherConflict = (bool) $researcher->fetchColumn();
    $addItem('Researcher', !$researcherConflict, $researcherConflict ? 'Schedule Conflict' : 'Available');

    $adviserOk = strcasecmp((string) ($group['adviser_availability'] ?? 'Pending'), 'Available') === 0;
    if ($adviserOk && !empty($group['adviser_user_id'])) {
        $adviser = $pdo->prepare(
             "SELECT rds.id
             FROM research_defense_schedules rds
             " . rdOfficialScheduleJoinSql() . "
             WHERE {$overlapSql}
               AND EXISTS (
                    SELECT 1 FROM research_adviser_assignments aa_old
                    WHERE aa_old.research_group_id = rds.research_group_id
                      AND aa_old.adviser_user_id = :adviser_user_id
                      AND aa_old.assignment_status IN ('Assigned', 'Confirmed')
               )
             LIMIT 1"
        );
        $adviser->execute($params + [':adviser_user_id' => (int) $group['adviser_user_id']]);
        $adviserOk = !$adviser->fetchColumn();
    }
    $addItem('Adviser', $adviserOk, $adviserOk ? 'Available' : 'Schedule Conflict');

    foreach ($panels as $index => $panel) {
        $panelName = (string) ($panel['panel_name'] ?? ('Panel ' . ($index + 1)));
        $panelOk = strcasecmp((string) ($panel['availability_status'] ?? 'Pending'), 'Available') === 0;
        $panelUserId = (int) ($panel['panel_user_id'] ?? 0);
        if ($panelOk && $panelUserId > 0) {
            $panelConflict = $pdo->prepare(
                "SELECT rds.id
                 FROM research_defense_schedules rds
                 " . rdOfficialScheduleJoinSql() . "
                 WHERE {$overlapSql}
                   AND EXISTS (
                        SELECT 1 FROM research_panel_assignments pa_old
                        WHERE pa_old.research_group_id = rds.research_group_id
                          AND pa_old.panel_user_id = :panel_user_id
                          AND " . rdPanelActiveAssignmentSql('pa_old') . "
                   )
                 LIMIT 1"
            );
            $panelConflict->execute($params + [':panel_user_id' => $panelUserId]);
            $panelOk = !$panelConflict->fetchColumn();
        }
        $addItem($panelName, $panelOk, $panelOk ? 'Available' : 'Schedule Conflict');
    }

    $venueStmt = $pdo->prepare("SELECT id, venue_name, status FROM research_venues WHERE id = ?");
    $venueStmt->execute([$venueId]);
    $venue = $venueStmt->fetch();
    $venueOk = $venue && strcasecmp((string) ($venue['status'] ?? ''), 'Available') === 0;
    if ($venueOk) {
        $venueConflict = $pdo->prepare("SELECT rds.id FROM research_defense_schedules rds " . rdOfficialScheduleJoinSql() . " WHERE {$overlapSql} AND rds.venue_id = :venue_id LIMIT 1");
        $venueConflict->execute($params + [':venue_id' => $venueId]);
        $venueOk = !$venueConflict->fetchColumn();
    }
    $addItem((string) (($venue['venue_name'] ?? '') ?: ($slot['venue'] ?? 'Venue')), $venueOk, $venueOk ? 'Available' : 'Venue Schedule Conflict');

    if (count($panels) !== RD_SCHEDULE_MAX_PANEL_MEMBERS) {
        $addItem('Panel Assignment', false, 'Exactly 3 Panel Members are required.');
    }

    return ['items' => $items, 'has_conflict' => $hasConflict, 'panels' => $panels];
}

function rdScheduleReviewPayload(PDO $pdo, int $scheduleId): ?array
{
    $slot = rdScheduleOne($pdo, $scheduleId);
    if (!$slot || !in_array(strtolower((string) ($slot['status'] ?? '')), ['proposed', 'selected'], true)) {
        return null;
    }
    $availability = rdScheduleAvailabilityReport($pdo, $slot, $scheduleId);
    $startTs = strtotime((string) ($slot['defense_datetime'] ?? ''));
    $endTs = strtotime((string) ($slot['defense_end_datetime'] ?? ''));
    return [
        'id' => (int) ($slot['id'] ?? 0),
        'research_group_id' => (int) ($slot['research_group_id'] ?? 0),
        'group' => (string) (($slot['research_group'] ?? '') ?: ($slot['group_number'] ?? 'Research Group')),
        'group_number' => (string) ($slot['group_number'] ?? ''),
        'title' => (string) ($slot['research_title'] ?? ''),
        'date' => $startTs ? date('F j, Y', $startTs) : 'Invalid date',
        'time' => ($startTs && $endTs) ? date('g:i A', $startTs) . ' - ' . date('g:i A', $endTs) : 'Invalid time',
        'venue' => (string) (($slot['venue_name'] ?? '') ?: ($slot['venue'] ?? '')),
        'adviser' => (string) ($slot['adviser_name'] ?? ''),
        'panels' => array_map(static fn (array $panel): string => (string) ($panel['panel_name'] ?? 'Panel Member'), $availability['panels']),
        'availability' => $availability['items'],
        'has_conflict' => (bool) $availability['has_conflict'],
        'alternative_url' => rdScheduleTypedUrl('alternative-time-slots', (string) ($slot['defense_type'] ?? CRAD_DEFENSE_TYPE_PRE_ORAL), [
            'group_id' => (int) ($slot['research_group_id'] ?? 0),
            'schedule_id' => (int) ($slot['id'] ?? 0),
        ]),
    ];
}

function rdScheduleConflictMessages(PDO $pdo, int $groupId, int $venueId, string $start, string $end, int $ignoreId = 0, string $defenseType = CRAD_DEFENSE_TYPE_PRE_ORAL): array
{
    $messages = [];
    $group = rdScheduleReadyGroup($pdo, $groupId, $defenseType);
    if (!$group) {
        return ['Research group is not ready for scheduling.'];
    }
    $panelRows = rdSchedulePanelRows($pdo, $groupId);
    $uniquePanelIds = [];
    foreach ($panelRows as $panel) {
        $panelUserId = (int) ($panel['panel_user_id'] ?? 0);
        if ($panelUserId > 0) {
            $uniquePanelIds[$panelUserId] = true;
        }
    }
    if (count($uniquePanelIds) !== RD_SCHEDULE_MAX_PANEL_MEMBERS) {
        $messages[] = 'Exactly 3 Panel Members are required for a ' . $defenseType . ' schedule.';
    }
    $venueStmt = $pdo->prepare("SELECT id, venue_name, status FROM research_venues WHERE id = ?");
    $venueStmt->execute([$venueId]);
    $venue = $venueStmt->fetch();
    if (!$venue) {
        $messages[] = 'Selected venue does not exist.';
    } elseif (strcasecmp((string) ($venue['status'] ?? ''), 'Available') !== 0) {
        $messages[] = 'Venue is not Available.';
    }
    if (strcasecmp((string) ($group['adviser_availability'] ?? 'Pending'), 'Available') !== 0) {
        $messages[] = 'Adviser availability is ' . (string) (($group['adviser_availability'] ?? '') ?: 'Pending') . '.';
    }
    foreach ($panelRows as $panel) {
        if (strcasecmp((string) ($panel['availability_status'] ?? 'Pending'), 'Available') !== 0) {
            $messages[] = (string) $panel['panel_name'] . ' availability is ' . (string) (($panel['availability_status'] ?? '') ?: 'Pending') . '.';
        }
    }

    $conflict = $pdo->prepare(
        "SELECT rds.id, rds.research_group_id, rds.venue_id, rds.group_number, rds.research_title, rds.venue, rds.status
         FROM research_defense_schedules rds
         " . rdOfficialScheduleJoinSql() . "
         WHERE rds.id <> :ignore_id
           AND rds.defense_datetime IS NOT NULL
           AND LOWER(rds.status) IN ('proposed', 'selected', 'scheduled', 'finalized', 'final')
           AND rds.defense_datetime < :end_at
           AND COALESCE(rds.defense_end_datetime, DATE_ADD(rds.defense_datetime, INTERVAL 2 HOUR)) > :start_at
           AND (
                rds.research_group_id = :group_id
             OR rds.venue_id = :venue_id
             OR EXISTS (
                    SELECT 1 FROM research_adviser_assignments aa_new
                    JOIN research_adviser_assignments aa_old
                      ON aa_old.adviser_user_id = aa_new.adviser_user_id
                     AND aa_old.adviser_user_id IS NOT NULL
                     AND aa_old.research_group_id = rds.research_group_id
                     AND aa_old.assignment_status IN ('Assigned', 'Confirmed')
                    WHERE aa_new.research_group_id = :group_id_adviser
                      AND aa_new.assignment_status IN ('Assigned', 'Confirmed')
                )
             OR EXISTS (
                    SELECT 1 FROM research_panel_assignments pa_new
                    JOIN research_panel_assignments pa_old
                      ON pa_old.panel_user_id = pa_new.panel_user_id
                     AND pa_old.research_group_id = rds.research_group_id
                     AND " . rdPanelActiveAssignmentSql('pa_old') . "
                    WHERE pa_new.research_group_id = :group_id_panel
                      AND " . rdPanelActiveAssignmentSql('pa_new') . "
                )
           )"
    );
    $conflict->execute([
        ':ignore_id' => $ignoreId,
        ':end_at' => $end,
        ':start_at' => $start,
        ':group_id' => $groupId,
        ':venue_id' => $venueId,
        ':group_id_adviser' => $groupId,
        ':group_id_panel' => $groupId,
    ]);
    foreach (($conflict->fetchAll() ?: []) as $row) {
        if ((int) ($row['research_group_id'] ?? 0) === $groupId) {
            $messages[] = 'Research group already has a schedule in this time range.';
        } elseif ($venue && (int) ($row['venue_id'] ?? 0) === $venueId) {
            $messages[] = 'Venue is occupied by ' . (string) ($row['group_number'] ?? 'another defense') . '.';
        } else {
            $messages[] = 'The adviser or an assigned panel member has another defense in this time range.';
        }
    }
    return array_values(array_unique($messages));
}

require_once __DIR__ . '/../includes/rd-scheduling-optimizer.php';

if (defined('RD_AI_OPTIMIZER_TEST') && RD_AI_OPTIMIZER_TEST) {
    return;
}

$requestedDefenseType = trim((string) ($_GET['defense_type'] ?? CRAD_DEFENSE_TYPE_PRE_ORAL));
if (!in_array($requestedDefenseType, [CRAD_DEFENSE_TYPE_PRE_ORAL, CRAD_DEFENSE_TYPE_FINAL], true)) {
    $requestedDefenseType = CRAD_DEFENSE_TYPE_PRE_ORAL;
}
$readyRows = [];
$scheduledRows = [];
$venueRows = [];
$allVenueRows = [];
$officialScheduledCount = 0;
$completedScheduleCount = 0;
$venueMessage = null;
$crad = cradDb();
if ($crad) {
    try {
        $crad->exec(
            "CREATE TABLE IF NOT EXISTS research_defense_schedules (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                research_group_id INT UNSIGNED DEFAULT NULL,
                proposal_id INT UNSIGNED DEFAULT NULL,
                proposal_number VARCHAR(30) DEFAULT NULL,
                group_number VARCHAR(40) NOT NULL DEFAULT '',
                research_group VARCHAR(120) NOT NULL DEFAULT '',
                research_title VARCHAR(255) NOT NULL DEFAULT '',
                adviser_name VARCHAR(160) DEFAULT NULL,
                panel_members TEXT DEFAULT NULL,
                panel_chair VARCHAR(160) DEFAULT NULL,
                venue VARCHAR(120) DEFAULT NULL,
                defense_datetime DATETIME DEFAULT NULL,
                status VARCHAR(40) NOT NULL DEFAULT 'Ready for Scheduling',
                recorded_by INT UNSIGNED DEFAULT NULL,
                recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_rds_group (research_group_id),
                KEY idx_rds_group_number (group_number),
                KEY idx_rds_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $crad->exec(
            "CREATE TABLE IF NOT EXISTS research_venues (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                venue_name VARCHAR(160) NOT NULL,
                capacity INT UNSIGNED NOT NULL DEFAULT 0,
                venue_type VARCHAR(80) NOT NULL DEFAULT '',
                status VARCHAR(40) NOT NULL DEFAULT 'Available',
                created_by INT UNSIGNED DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_research_venue_name (venue_name),
                KEY idx_research_venues_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $seedVenue = $crad->prepare(
            "INSERT IGNORE INTO research_venues
                (venue_name, capacity, venue_type, status, created_at, updated_at)
             VALUES
                (?, ?, ?, 'Available', NOW(), NOW())"
        );
        foreach ([
            ['CRAD Conference Room', 30, 'Conference Room'],
            ['Research Room 1', 25, 'Research Room'],
            ['Research Room 2', 25, 'Research Room'],
            ['AVR Room', 100, 'Auditorium'],
            ['Computer Laboratory 1', 40, 'Laboratory'],
        ] as $venueSeed) {
            $seedVenue->execute($venueSeed);
        }
        rdScheduleEnsureSchema($crad);
    } catch (Throwable $e) {
        error_log('Research director venue table setup failed: ' . $e->getMessage());
    }

    if ($view === 'venues' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['venue_action'] ?? '') === 'update_status') {
        header('Content-Type: application/json; charset=utf-8');
        if (!csrfVerify()) {
            echo json_encode(['ok' => false, 'message' => 'Security token expired.']);
            exit;
        }
        $venueId     = (int) ($_POST['venue_id'] ?? 0);
        $newStatus   = trim((string) ($_POST['status'] ?? ''));
        $allowedSt   = ['Available', 'Reserved', 'Unavailable'];
        if ($venueId < 1 || !in_array($newStatus, $allowedSt, true)) {
            echo json_encode(['ok' => false, 'message' => 'Invalid venue or status.']);
            exit;
        }
        try {
            $upd = $crad->prepare(
                "UPDATE research_venues SET status = :status, updated_at = NOW() WHERE id = :id"
            );
            $upd->execute([':status' => $newStatus, ':id' => $venueId]);
            echo json_encode(['ok' => true, 'status' => $newStatus]);
        } catch (Throwable $e) {
            error_log('Research director update venue status failed: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'message' => 'Database error.']);
        }
        exit;
    }

    if ($view === 'venues' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['venue_action'] ?? '') === 'update_capacity') {
        header('Content-Type: application/json; charset=utf-8');
        if (!csrfVerify()) {
            echo json_encode(['ok' => false, 'message' => 'Security token expired.']);
            exit;
        }
        $venueId  = (int) ($_POST['venue_id'] ?? 0);
        $capacity = (int) ($_POST['capacity'] ?? 0);
        if ($venueId < 1 || $capacity < 1) {
            echo json_encode(['ok' => false, 'message' => 'Capacity must be at least 1.']);
            exit;
        }
        try {
            $upd = $crad->prepare(
                "UPDATE research_venues SET capacity = :capacity, updated_at = NOW() WHERE id = :id"
            );
            $upd->execute([':capacity' => $capacity, ':id' => $venueId]);
            echo json_encode(['ok' => true, 'capacity' => $capacity]);
        } catch (Throwable $e) {
            error_log('Research director update venue capacity failed: ' . $e->getMessage());
            echo json_encode(['ok' => false, 'message' => 'Database error.']);
        }
        exit;
    }

    if ($view === 'venues' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['venue_action'] ?? '') === 'add') {
        if (!csrfVerify()) {
            $venueMessage = ['type' => 'danger', 'text' => 'Security token expired. Refresh the page and try again.'];
        } else {
            $venueName = trim((string) ($_POST['venue_name'] ?? ''));
            $venueType = trim((string) ($_POST['venue_type'] ?? ''));
            $capacity = max(0, (int) ($_POST['capacity'] ?? 0));
            $venueStatus = trim((string) ($_POST['status'] ?? 'Available'));
            $allowedStatuses = ['Available', 'Unavailable', 'Reserved'];

            if ($venueName === '' || $venueType === '' || $capacity < 1 || !in_array($venueStatus, $allowedStatuses, true)) {
                $venueMessage = ['type' => 'danger', 'text' => 'Complete the venue name, capacity, type, and valid status.'];
            } else {
                try {
                    $addVenue = $crad->prepare(
                        "INSERT INTO research_venues
                            (venue_name, capacity, venue_type, status, created_by, created_at, updated_at)
                         VALUES
                            (:venue_name, :capacity, :venue_type, :status, :created_by, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE
                            capacity = VALUES(capacity),
                            venue_type = VALUES(venue_type),
                            status = VALUES(status),
                            updated_at = NOW()"
                    );
                    $addVenue->execute([
                        ':venue_name' => $venueName,
                        ':capacity' => $capacity,
                        ':venue_type' => $venueType,
                        ':status' => $venueStatus,
                        ':created_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
                    ]);
                    $venueMessage = ['type' => 'success', 'text' => 'Venue saved successfully.'];
                } catch (Throwable $e) {
                    error_log('Research director add venue failed: ' . $e->getMessage());
                    $venueMessage = ['type' => 'danger', 'text' => 'Unable to save venue. Please try again.'];
                }
            }
        }
    }

    if ($view === 'manual-scheduling-optimizer'
        && $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['schedule_action'] ?? '') === 'ai_generate_slots') {
        header('Content-Type: application/json; charset=utf-8');
        if (!csrfVerify()) {
            echo json_encode(['ok' => false, 'message' => 'Security token expired.']);
            exit;
        }

        $groupId = (int) ($_POST['research_group_id'] ?? ($_GET['group_id'] ?? 0));
        $defenseType = trim((string) ($_POST['defense_type'] ?? $requestedDefenseType));
        if (!in_array($defenseType, [CRAD_DEFENSE_TYPE_PRE_ORAL, CRAD_DEFENSE_TYPE_FINAL], true)) {
            $defenseType = CRAD_DEFENSE_TYPE_PRE_ORAL;
        }

        $periodStart = trim((string) ($_POST['period_start'] ?? ''));
        $periodEnd   = trim((string) ($_POST['period_end'] ?? ''));
        $expectedAttendees = max(1, (int) ($_POST['expected_attendees'] ?? 15));

        $result = rdScheduleGenerateOptimizedSlots(
            $crad,
            $groupId,
            $defenseType,
            $periodStart,
            $periodEnd,
            $expectedAttendees
        );

        echo json_encode($result);
        exit;
    }

    if (in_array($view, ['manual-scheduling-optimizer', 'alternative-time-slots'], true)
        && $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['schedule_action'] ?? '') === 'save_proposed') {
        if (!csrfVerify()) {
            $venueMessage = ['type' => 'danger', 'text' => 'Security token expired. Refresh the page and try again.'];
        } else {
            $requestGroupId = (int) ($_GET['group_id'] ?? 0);
            $groupId = (int) ($_POST['research_group_id'] ?? 0);
            $postedDefenseType = trim((string) ($_POST['defense_type'] ?? ''));
            $defenseType = in_array($postedDefenseType, [CRAD_DEFENSE_TYPE_PRE_ORAL, CRAD_DEFENSE_TYPE_FINAL], true)
                ? $postedDefenseType
                : rdScheduleAutoDefenseType($crad, $groupId, CRAD_DEFENSE_TYPE_PRE_ORAL);
            $group = $groupId > 0 ? rdScheduleReadyGroup($crad, $groupId, $defenseType) : null;
            $panelRowsForSchedule = $groupId > 0 ? rdSchedulePanelRows($crad, $groupId) : [];
            $uniquePanelIds = [];
            foreach ($panelRowsForSchedule as $panelRowForSchedule) {
                $panelUserId = (int) ($panelRowForSchedule['panel_user_id'] ?? 0);
                if ($panelUserId > 0) {
                    $uniquePanelIds[$panelUserId] = true;
                }
            }
            $panelCountForSchedule = count($uniquePanelIds);
            $errors = [];
            if ($requestGroupId < 1 || $requestGroupId !== $groupId) {
                $errors[] = 'Select a defense-ready research from Ready for Scheduling before saving slots.';
            }
            if (!$group) {
                $errors[] = 'Select a defense-ready research group.';
            }
            if ($panelCountForSchedule > RD_SCHEDULE_MAX_PANEL_MEMBERS) {
                $errors[] = 'Maximum of 3 Panel Members is allowed for a ' . $defenseType . ' schedule. Please update the panel assignment before continuing.';
            } elseif ($panelCountForSchedule !== RD_SCHEDULE_MAX_PANEL_MEMBERS) {
                $errors[] = 'Exactly 3 Panel Members are required before creating a ' . $defenseType . ' schedule.';
            }

            $dates = is_array($_POST['defense_date'] ?? null) ? $_POST['defense_date'] : [$_POST['defense_date'] ?? ''];
            $starts = is_array($_POST['start_time'] ?? null) ? $_POST['start_time'] : [$_POST['start_time'] ?? ''];
            $ends = is_array($_POST['end_time'] ?? null) ? $_POST['end_time'] : [$_POST['end_time'] ?? ''];
            $venueIds = is_array($_POST['venue_id'] ?? null) ? $_POST['venue_id'] : [$_POST['venue_id'] ?? 0];
            $slots = [];
            $venueStmt = $crad->prepare("SELECT id, venue_name FROM research_venues WHERE id = ?");
            $slotLimit = min(3, max(count($dates), count($starts), count($ends), count($venueIds)));
            for ($i = 0; $i < $slotLimit; $i++) {
                $date = trim((string) ($dates[$i] ?? ''));
                $startTime = trim((string) ($starts[$i] ?? ''));
                $endTime = trim((string) ($ends[$i] ?? ''));
                $venueId = (int) ($venueIds[$i] ?? 0);
                if ($date === '' && $startTime === '' && $endTime === '' && $venueId < 1) {
                    continue;
                }
                $startAt = trim($date . ' ' . $startTime . ':00');
                $endAt = trim($date . ' ' . $endTime . ':00');
                $venueStmt->execute([$venueId]);
                $venue = $venueStmt->fetch() ?: [];
                if (!$venue) {
                    $errors[] = 'Slot ' . ($i + 1) . ': select a valid venue.';
                    continue;
                }
                $startTs = strtotime($startAt);
                $endTs = strtotime($endAt);
                if ($startTs === false || $endTs === false || $endTs <= $startTs) {
                    $errors[] = 'Slot ' . ($i + 1) . ': select a valid date, start time, and end time.';
                    continue;
                }
                if ($date < date('Y-m-d')) {
                    $errors[] = 'Slot ' . ($i + 1) . ': select a current or future date.';
                    continue;
                }
                $slots[] = [
                    'venue_id' => $venueId,
                    'venue' => $venue,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'start_ts' => $startTs,
                    'end_ts' => $endTs,
                    'signature' => $venueId . '|' . $startAt . '|' . $endAt,
                ];
            }
            if (!$slots) {
                $errors[] = 'Add at least one proposed slot.';
            } elseif ($view === 'manual-scheduling-optimizer' && count($slots) < 2) {
                $errors[] = 'Add at least two proposed slots for the initial scheduling set.';
            }
            if (!$errors) {
                $signatures = [];
                foreach ($slots as $slotIndex => $slot) {
                    if (isset($signatures[$slot['signature']])) {
                        $errors[] = 'Slot ' . ($slotIndex + 1) . ': duplicate proposed slot.';
                    }
                    $signatures[$slot['signature']] = true;
                    foreach ($slots as $otherIndex => $other) {
                        if ($otherIndex >= $slotIndex) {
                            continue;
                        }
                        if ($slot['start_ts'] < $other['end_ts'] && $slot['end_ts'] > $other['start_ts']) {
                            $errors[] = 'Slot ' . ($slotIndex + 1) . ': overlaps another submitted slot for this research group.';
                        }
                    }
                    $slotErrors = rdScheduleConflictMessages($crad, $groupId, (int) $slot['venue_id'], (string) $slot['start_at'], (string) $slot['end_at'], 0, $defenseType);
                    foreach ($slotErrors as $slotError) {
                        $errors[] = 'Slot ' . ($slotIndex + 1) . ': ' . $slotError;
                    }
                }
            }
            if (!$errors) {
                $official = $crad->prepare(
                    "SELECT id FROM research_defense_schedules
                     WHERE research_group_id = ?
                                             AND defense_type = ?
                       AND LOWER(status) IN ('scheduled', 'finalized', 'final')
                     LIMIT 1"
                );
                                $official->execute([$groupId, $defenseType]);
                if ($official->fetchColumn()) {
                                        $errors[] = 'This research group already has an official ' . $defenseType . ' schedule.';
                }
            }
            if ($errors) {
                $venueMessage = ['type' => 'danger', 'text' => implode(' ', $errors)];
            } else {
                $slotSignatures = array_map(static fn (array $slot): string => (string) $slot['signature'], $slots);
                sort($slotSignatures);
                $scheduleLockName = 'rd_' . strtolower(str_replace(' ', '_', $defenseType)) . '_' . $groupId . '_' . sha1(implode('|', $slotSignatures));
                $lockAcquired = false;
                try {
                    $lockStmt = $crad->prepare("SELECT GET_LOCK(?, 5)");
                    $lockStmt->execute([$scheduleLockName]);
                    $lockAcquired = (int) $lockStmt->fetchColumn() === 1;
                    if (!$lockAcquired) {
                        throw new RuntimeException('Schedule is being saved. Please wait and try again.');
                    }
                    $crad->beginTransaction();
                    $exists = $crad->prepare(
                        "SELECT id FROM research_defense_schedules
                         WHERE research_group_id = ?
                           AND venue_id = ?
                           AND defense_datetime = ?
                           AND defense_end_datetime = ?
                                                     AND defense_type = ?
                           AND LOWER(status) IN ('proposed', 'selected', 'scheduled', 'finalized', 'final')
                         LIMIT 1"
                    );
                    $panelNamesForSchedule = array_map(
                        static fn (array $panelRow): string => (string) ($panelRow['panel_name'] ?? 'Panel Member'),
                        $panelRowsForSchedule
                    );
                    $panels = rdPanelFormatNames($panelNamesForSchedule);
                    $panelChair = (string) ((rdPanelNamesFromString($panels)[0] ?? '') ?: 'For panel chair');
                    $insert = $crad->prepare(
                        "INSERT INTO research_defense_schedules
                            (research_group_id, proposal_id, proposal_number, group_number, research_group,
                             research_title, adviser_name, panel_members, panel_chair, venue, venue_id,
                             defense_datetime, defense_end_datetime, defense_type, status, recorded_by, recorded_at, updated_at)
                         VALUES
                            (:research_group_id, :proposal_id, :proposal_number, :group_number, :research_group,
                             :research_title, :adviser_name, :panel_members, :panel_chair, :venue, :venue_id,
                             :defense_datetime, :defense_end_datetime, :defense_type, 'Proposed', :recorded_by, NOW(), NOW())"
                    );
                    foreach ($slots as $slot) {
                        $exists->execute([$groupId, (int) $slot['venue_id'], (string) $slot['start_at'], (string) $slot['end_at'], $defenseType]);
                        if ($exists->fetchColumn()) {
                            throw new RuntimeException('One of the proposed slots already exists.');
                        }
                        $insert->execute([
                            ':research_group_id' => $groupId,
                            ':proposal_id' => (int) ($group['proposal_id'] ?? 0) ?: null,
                            ':proposal_number' => (string) ($group['proposal_number'] ?? ''),
                            ':group_number' => (string) ($group['group_number'] ?? ''),
                            ':research_group' => (string) ($group['group_name'] ?? ''),
                            ':research_title' => (string) ($group['research_title'] ?? ''),
                            ':adviser_name' => (string) ($group['adviser_name'] ?? ''),
                            ':panel_members' => $panels,
                            ':panel_chair' => $panelChair,
                            ':venue' => (string) ($slot['venue']['venue_name'] ?? ''),
                            ':venue_id' => (int) $slot['venue_id'],
                            ':defense_datetime' => (string) $slot['start_at'],
                            ':defense_end_datetime' => (string) $slot['end_at'],
                            ':defense_type' => $defenseType,
                            ':recorded_by' => (int) getCurrentUserId(),
                        ]);
                    }
                    $crad->commit();
                    $venueMessage = ['type' => 'success', 'text' => count($slots) === 1 ? 'Proposed slot saved.' : count($slots) . ' proposed slot(s) saved.'];
                } catch (Throwable $e) {
                    if ($crad->inTransaction()) {
                        $crad->rollBack();
                    }
                    $venueMessage = ['type' => 'danger', 'text' => $e instanceof RuntimeException ? $e->getMessage() : 'Unable to save proposed slot.'];
                    error_log('RD proposed slot save failed: ' . $e->getMessage());
                } finally {
                    if ($lockAcquired) {
                        try {
                            $releaseStmt = $crad->prepare("SELECT RELEASE_LOCK(?)");
                            $releaseStmt->execute([$scheduleLockName]);
                        } catch (Throwable $e) {
                            error_log('RD schedule lock release failed: ' . $e->getMessage());
                        }
                    }
                }
            }
        }
    }

    if ($view === 'finalize-defense-schedule'
        && $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['schedule_action'] ?? '') === 'finalize') {
        header('Content-Type: application/json; charset=utf-8');
        if (!csrfVerify()) {
            echo json_encode(['ok' => false, 'message' => 'Security token expired.']);
            exit;
        }
        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        try {
            $stmt = $crad->prepare("SELECT * FROM research_defense_schedules WHERE id = ? AND LOWER(status) IN ('proposed', 'selected')");
            $stmt->execute([$scheduleId]);
            $slot = $stmt->fetch();
            if (!$slot) {
                throw new RuntimeException('Proposed schedule was not found.');
            }
            $groupId = (int) ($slot['research_group_id'] ?? 0);
            if (!rdIsOfficialResearchGroup($crad, $groupId)) {
                throw new RuntimeException('Research group is no longer available in the official Capstone Group/Student Registry.');
            }
            $venueId = (int) ($slot['venue_id'] ?? 0);
            $conflicts = rdScheduleConflictMessages(
                $crad,
                $groupId,
                $venueId,
                (string) $slot['defense_datetime'],
                (string) $slot['defense_end_datetime'],
                $scheduleId,
                (string) ($slot['defense_type'] ?? CRAD_DEFENSE_TYPE_PRE_ORAL)
            );
            $official = $crad->prepare(
                "SELECT id FROM research_defense_schedules
                 WHERE id <> ?
                   AND research_group_id = ?
                   AND defense_type = ?
                   AND LOWER(status) IN ('scheduled', 'finalized', 'final')
                 LIMIT 1"
            );
            $official->execute([$scheduleId, $groupId, (string) ($slot['defense_type'] ?? CRAD_DEFENSE_TYPE_PRE_ORAL)]);
            if ($official->fetchColumn()) {
                $conflicts[] = 'This research group already has an official ' . (string) ($slot['defense_type'] ?? CRAD_DEFENSE_TYPE_PRE_ORAL) . ' schedule.';
            }
            if ($conflicts) {
                throw new RuntimeException(implode(' ', $conflicts));
            }
            $panelStmt = $crad->prepare(
                "SELECT rpa.id, rpa.panel_user_id, rpa.panel_email,
                        COALESCE(NULLIF(u.full_name, ''), NULLIF(rpa.panel_name, ''), 'Panel Member') AS panel_name
                 FROM research_panel_assignments rpa
                 LEFT JOIN sms2_db.users u ON u.id = rpa.panel_user_id
                 WHERE rpa.research_group_id = ?
                   AND " . rdPanelActiveAssignmentSql('rpa') . "
                 GROUP BY rpa.id, rpa.panel_user_id, rpa.panel_email, panel_name
                 ORDER BY panel_name ASC"
            );
            $panelStmt->execute([$groupId]);
            $assignedPanels = $panelStmt->fetchAll() ?: [];
            if (!$assignedPanels) {
                throw new RuntimeException('No active panel members are assigned to this research group.');
            }
            $scheduleDefenseType = (string) ($slot['defense_type'] ?? CRAD_DEFENSE_TYPE_PRE_ORAL);

            $crad->beginTransaction();
            try {
                $lockStmt = $crad->prepare("SELECT * FROM research_defense_schedules WHERE id = ? AND LOWER(status) IN ('proposed', 'selected') FOR UPDATE");
                $lockStmt->execute([$scheduleId]);
                if (!$lockStmt->fetch()) {
                    throw new RuntimeException('Proposed schedule was not found.');
                }
                if ($scheduleDefenseType === CRAD_DEFENSE_TYPE_FINAL) {
                    $crad->prepare(
                        "INSERT INTO research_panel_assignments
                            (research_group_id, defense_schedule_id, proposal_id, title_approval_id, proposal_number,
                             group_number, research_title, panel_user_id, panel_name, panel_email, expertise,
                             availability_status, assignment_status, defense_phase, assigned_by, assigned_at, created_at, updated_at)
                         SELECT research_group_id, ?, proposal_id, title_approval_id, proposal_number,
                                group_number, research_title, panel_user_id, panel_name, panel_email, expertise,
                                availability_status, 'Assigned', ?, assigned_by, NOW(), NOW(), NOW()
                         FROM research_panel_assignments
                         WHERE research_group_id = ?
                           AND defense_phase = ?
                           AND assignment_status = 'Assigned'
                         ON DUPLICATE KEY UPDATE defense_schedule_id = VALUES(defense_schedule_id), assignment_status = 'Assigned', updated_at = NOW()"
                    )->execute([$scheduleId, CRAD_DEFENSE_PHASE_FINAL, $groupId, CRAD_DEFENSE_PHASE_PRE_ORAL]);
                }
                $crad->prepare("UPDATE research_defense_schedules SET status = 'Rejected', updated_at = NOW() WHERE research_group_id = ? AND id <> ? AND defense_type = ? AND LOWER(status) IN ('proposed', 'selected')")
                    ->execute([$groupId, $scheduleId, $scheduleDefenseType]);
                $crad->prepare("UPDATE research_defense_schedules SET status = 'Finalized', finalized_by = ?, finalized_at = NOW(), updated_at = NOW() WHERE id = ?")
                    ->execute([(int) getCurrentUserId(), $scheduleId]);
                $crad->prepare(
                    "UPDATE research_panel_assignments
                     SET defense_schedule_id = ?, updated_at = NOW()
                     WHERE research_group_id = ?
                       AND defense_phase = ?
                       AND assignment_status = 'Assigned'"
                )->execute([$scheduleId, $groupId, $scheduleDefenseType === CRAD_DEFENSE_TYPE_FINAL ? CRAD_DEFENSE_PHASE_FINAL : CRAD_DEFENSE_PHASE_PRE_ORAL]);
                $planStmt = $crad->prepare("SELECT id FROM research_plans WHERE research_group_id = ? LIMIT 1");
                $planStmt->execute([$groupId]);
                $planId = (int) ($planStmt->fetchColumn() ?: 0);
                if ($planId > 0 && $scheduleDefenseType === CRAD_DEFENSE_TYPE_PRE_ORAL) {
                    rpSetCurrentStageIfFirstSemesterComplete($crad, $planId, $groupId);
                }
                $notify = $crad->prepare(
                    "INSERT IGNORE INTO panel_assignment_notifications
                        (event_key, recipient_user_id, recipient_role, recipient_email, panel_assignment_id,
                         research_group_id, title, body, url, is_read, created_at)
                     VALUES
                        (:event_key, :recipient_user_id, 'panel', :recipient_email, :panel_assignment_id,
                         :research_group_id, :title, :body, :url, 0, NOW())"
                );
                $startLabel = rdScheduleDate((string) ($slot['defense_datetime'] ?? ''), 'M j, Y h:i A');
                $endLabel = rdScheduleDate((string) ($slot['defense_end_datetime'] ?? ''), 'h:i A');
                $timeLabel = trim($startLabel . ($endLabel !== '' ? ' - ' . $endLabel : ''));
                $venueLabel = (string) (($slot['venue'] ?? '') ?: 'TBA');
                $groupLabel = (string) (($slot['group_number'] ?? '') ?: ($slot['research_group'] ?? 'Research Group'));
                $notificationBody = $groupLabel . "\n"
                    . (string) ($slot['research_title'] ?? '') . "\n"
                    . 'Date/Time: ' . $timeLabel . "\n"
                    . 'Venue: ' . $venueLabel;
                foreach ($assignedPanels as $panel) {
                    $panelUserId = (int) ($panel['panel_user_id'] ?? 0);
                    if ($panelUserId <= 0) {
                        continue;
                    }
                    $notify->execute([
                        ':event_key' => strtolower(str_replace(' ', '-', $scheduleDefenseType)) . '-finalized:s' . $scheduleId . ':u' . $panelUserId,
                        ':recipient_user_id' => $panelUserId,
                        ':recipient_email' => (string) ($panel['panel_email'] ?? ''),
                        ':panel_assignment_id' => (int) ($panel['id'] ?? 0) ?: null,
                        ':research_group_id' => $groupId,
                        ':title' => $scheduleDefenseType . ' Scheduled',
                        ':body' => $notificationBody,
                        ':url' => BASE_URL . '/modules/faculty/pages/defense-details.php?id=' . $scheduleId,
                    ]);
                }
                if ($crad->inTransaction()) {
                    $crad->commit();
                }
            } catch (Throwable $inner) {
                if ($crad->inTransaction()) {
                    $crad->rollBack();
                }
                throw $inner;
            }
            echo json_encode(['ok' => true, 'message' => $scheduleDefenseType . ' schedule confirmed.']);
        } catch (Throwable $e) {
            if ($crad->inTransaction()) {
                $crad->rollBack();
            }
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($view === 'proposed-schedules'
        && ($_GET['ajax'] ?? '') === 'review-schedule') {
        header('Content-Type: application/json; charset=utf-8');
        $scheduleId = (int) ($_GET['schedule_id'] ?? 0);
        try {
            $payload = $scheduleId > 0 ? rdScheduleReviewPayload($crad, $scheduleId) : null;
            if (!$payload) {
                throw new RuntimeException('Proposed schedule was not found.');
            }
            echo json_encode(['ok' => true, 'schedule' => $payload]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($view === 'proposed-schedules'
        && $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['schedule_action'] ?? '') === 'choose_schedule') {
        header('Content-Type: application/json; charset=utf-8');
        if (!csrfVerify()) {
            echo json_encode(['ok' => false, 'message' => 'Security token expired.']);
            exit;
        }
        $scheduleId = (int) ($_POST['schedule_id'] ?? 0);
        $lockName = 'rd_choose_schedule_' . $scheduleId;
        $lockAcquired = false;
        try {
            $lockStmt = $crad->prepare('SELECT GET_LOCK(?, 5)');
            $lockStmt->execute([$lockName]);
            $lockAcquired = (int) $lockStmt->fetchColumn() === 1;
            if (!$lockAcquired) {
                throw new RuntimeException('Schedule is being selected. Please wait and try again.');
            }

            // Validate outside the transaction so readiness/schema checks cannot
            // implicitly commit and break PDO::commit().
            $slot = rdScheduleOne($crad, $scheduleId);
            if (!$slot || !in_array(strtolower((string) ($slot['status'] ?? '')), ['proposed', 'selected'], true)) {
                throw new RuntimeException('Proposed schedule was not found.');
            }
            $availability = rdScheduleAvailabilityReport($crad, $slot, $scheduleId);
            if (!empty($availability['has_conflict'])) {
                throw new RuntimeException('This proposed schedule has a current conflict. Please find an alternative slot.');
            }
            $groupId = (int) ($slot['research_group_id'] ?? 0);
            $defenseType = (string) ($slot['defense_type'] ?? CRAD_DEFENSE_TYPE_PRE_ORAL);
            $official = $crad->prepare(
                "SELECT id FROM research_defense_schedules
                 WHERE research_group_id = ?
                   AND defense_type = ?
                   AND LOWER(status) IN ('scheduled', 'finalized', 'final')
                 LIMIT 1"
            );
            $official->execute([$groupId, $defenseType]);
            if ($official->fetchColumn()) {
                throw new RuntimeException('This research group already has an official finalized schedule.');
            }

            $crad->beginTransaction();
            try {
                $crad->prepare(
                    "UPDATE research_defense_schedules
                     SET status = 'Proposed', updated_at = NOW()
                     WHERE research_group_id = ?
                       AND id <> ?
                       AND defense_type = ?
                       AND LOWER(status) = 'selected'"
                )->execute([$groupId, $scheduleId, $defenseType]);
                $crad->prepare(
                    "UPDATE research_defense_schedules
                     SET status = 'Selected', updated_at = NOW()
                     WHERE id = ?
                       AND LOWER(status) IN ('proposed', 'selected')"
                )->execute([$scheduleId]);
                if ($crad->inTransaction()) {
                    $crad->commit();
                }
            } catch (Throwable $inner) {
                if ($crad->inTransaction()) {
                    $crad->rollBack();
                }
                throw $inner;
            }

            echo json_encode([
                'ok' => true,
                'message' => 'Proposed schedule selected for final review.',
                'redirect' => rdScheduleTypedUrl('finalize-defense-schedule', $defenseType, ['schedule_id' => $scheduleId]),
            ]);
        } catch (Throwable $e) {
            if ($crad->inTransaction()) {
                $crad->rollBack();
            }
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        } finally {
            if ($lockAcquired) {
                try {
                    $releaseStmt = $crad->prepare('SELECT RELEASE_LOCK(?)');
                    $releaseStmt->execute([$lockName]);
                } catch (Throwable $e) {
                    error_log('RD choose schedule lock release failed: ' . $e->getMessage());
                }
            }
        }
        exit;
    }

    try {
        $readyRows = rdScheduleReadyRows($crad, $requestedDefenseType === CRAD_DEFENSE_TYPE_FINAL, $requestedDefenseType);
    } catch (Throwable $e) {
        error_log('Research director ready list failed: ' . $e->getMessage());
    }

    try {
        if ($view === 'finalize-defense-schedule') {
            $scheduleStatusFilter = ['Selected'];
        } elseif (in_array($view, ['proposed-schedules', 'alternative-time-slots'], true)) {
            $scheduleStatusFilter = ['Proposed', 'Selected'];
        } elseif ($view === 'calendar') {
            $scheduleStatusFilter = ['Proposed', 'Selected', 'Scheduled', 'Finalized', 'Final'];
        } elseif (in_array($view, ['defense-schedule', 'calendar'], true)) {
            $scheduleStatusFilter = ['Scheduled', 'Finalized', 'Final'];
        } else {
            $scheduleStatusFilter = [];
        }
        foreach (rdScheduleRows($crad, $scheduleStatusFilter, $requestedDefenseType) as $row) {
            $panelNames = rdSchedulePanelNames(
                $crad,
                (int) ($row['research_group_id'] ?? 0),
                (string) ($row['panel_members'] ?? '')
            );
            $panelDetail = $panelNames ? rdPanelFormatNames($panelNames) : 'Panel verification required';
            $updated = strtotime((string) ($row['updated_at'] ?? '')) ?: time();
            $defenseTime = !empty($row['defense_datetime'])
                ? strtotime((string) $row['defense_datetime'])
                : false;
            $endTime = !empty($row['defense_end_datetime']) ? strtotime((string) $row['defense_end_datetime']) : false;
            $timeLabel = $defenseTime
                ? date('M j, Y h:i A', $defenseTime) . ($endTime ? ' - ' . date('h:i A', $endTime) : '')
                : date('M j, Y h:i A', $updated);
            $scheduledRows[] = [
                'id' => (int) ($row['id'] ?? 0),
                'research_group_id' => (int) ($row['research_group_id'] ?? 0),
                'reference' => (string) ($row['group_number'] ?? ''),
                'title' => (string) (($row['research_title'] ?? '') ?: ($row['research_group'] ?? 'Research Group')),
                'subtitle' => (string) ($row['research_group'] ?? ''),
                'owner' => $panelDetail,
                'owner_lines' => $panelNames ?: [$panelDetail],
                'detail' => trim((string) (($row['venue_name'] ?? '') ?: ($row['venue'] ?? 'Ready for venue')) . ($defenseTime ? ' | ' . $timeLabel : '')),
                'adviser' => (string) ($row['adviser_name'] ?? ''),
                'panel_members' => $panelDetail,
                'status' => (string) (($row['status'] ?? '') ?: 'Ready for Scheduling'),
                'proposal_status' => '',
                'proposal_progress' => 100,
                'updated' => $timeLabel,
                'updated_raw' => (string) ($row['updated_at'] ?? ''),
                'defense_datetime_raw' => (string) ($row['defense_datetime'] ?? ''),
                'action_url' => rdScheduleTypedUrl('finalize-defense-schedule', $requestedDefenseType, ['schedule_id' => (int) ($row['id'] ?? 0)]),
                'action_label' => in_array(strtolower((string) ($row['status'] ?? '')), ['proposed', 'selected'], true) ? 'Review' : '',
            ];
        }
    } catch (Throwable $e) {
        error_log('Research director schedule list failed: ' . $e->getMessage());
    }

    try {
        $allVenueRows = $crad->query(
            "SELECT id, venue_name, capacity, venue_type, status, updated_at
             FROM research_venues
             ORDER BY FIELD(status, 'Available', 'Reserved', 'Unavailable'), venue_name ASC"
        )->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Research director venue options failed: ' . $e->getMessage());
    }

    try {
        $officialScheduledStmt = $crad->prepare(
            "SELECT COUNT(*)
             FROM research_defense_schedules rds
             " . rdOfficialScheduleJoinSql() . "
             WHERE rds.defense_datetime IS NOT NULL
               AND rds.defense_type = ?
               AND LOWER(rds.status) IN ('scheduled', 'finalized', 'final')"
        );
        $officialScheduledStmt->execute([$requestedDefenseType]);
        $officialScheduledCount = (int) $officialScheduledStmt->fetchColumn();
        $completedScheduleStmt = $crad->prepare(
            "SELECT COUNT(*)
             FROM research_defense_schedules rds
             " . rdOfficialScheduleJoinSql() . "
             WHERE rds.defense_datetime IS NOT NULL
               AND rds.defense_type = ?
               AND LOWER(rds.status) IN ('completed', 'passed')"
        );
        $completedScheduleStmt->execute([$requestedDefenseType]);
        $completedScheduleCount = (int) $completedScheduleStmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('Research director schedule counters failed: ' . $e->getMessage());
    }

    if ($view === 'venues') {
        try {
            $venueStmt = $crad->query(
                "SELECT rv.id, rv.venue_name, rv.capacity, rv.venue_type, rv.status, rv.updated_at,
                        (
                            SELECT CONCAT(rds.group_number, ' | ', DATE_FORMAT(rds.defense_datetime, '%b %e, %Y %h:%i %p'))
                            FROM research_defense_schedules rds
                            " . rdOfficialScheduleJoinSql() . "
                            WHERE rds.venue_id = rv.id
                              AND rds.defense_datetime IS NOT NULL
                              AND LOWER(rds.status) IN ('proposed', 'selected', 'scheduled', 'finalized', 'final')
                            ORDER BY rds.defense_datetime ASC
                            LIMIT 1
                        ) AS current_schedule
                 FROM research_venues rv
                 ORDER BY FIELD(rv.status, 'Available', 'Reserved', 'Unavailable'), rv.venue_name ASC"
            );
            $venueRows = $venueStmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log('Research director venue list failed: ' . $e->getMessage());
        }
    }
}

$schedulerViews = ['manual-scheduling-optimizer', 'alternative-time-slots'];
$isSchedulerView = in_array($view, $schedulerViews, true);
$hasExplicitGroupSelection = isset($_GET['group_id']) && (int) $_GET['group_id'] > 0;
$selectedGroupId = (int) ($_GET['group_id'] ?? 0);
$requestedDefenseType = rdScheduleAutoDefenseType($crad, $selectedGroupId, $requestedDefenseType);
$defenseTypeLabel = $requestedDefenseType === CRAD_DEFENSE_TYPE_FINAL ? CRAD_DEFENSE_TYPE_FINAL : 'Pre-Oral Defense';
$activePage = rdScheduleSidebarActivePage($view, $requestedDefenseType);
$selectedReadyGroup = ($crad && $selectedGroupId > 0) ? rdScheduleReadyGroup($crad, $selectedGroupId, $requestedDefenseType) : null;
$selectedPanelRows = ($crad && $selectedGroupId > 0) ? rdSchedulePanelRows($crad, $selectedGroupId) : [];
$selectedGroupIsOfficial = ($crad && $selectedGroupId > 0) ? rdIsOfficialResearchGroup($crad, $selectedGroupId) : false;
$selectedGroupHasOfficial = false;
if ($crad && $selectedGroupId > 0) {
    try {
        $officialStmt = $crad->prepare(
            "SELECT id FROM research_defense_schedules
             WHERE research_group_id = ?
                             AND defense_type = ?
               AND LOWER(status) IN ('scheduled', 'finalized', 'final')
             LIMIT 1"
        );
                $officialStmt->execute([$selectedGroupId, $requestedDefenseType]);
        $selectedGroupHasOfficial = (bool) $officialStmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('RD selected official lookup failed: ' . $e->getMessage());
    }
}
$finalizeScheduleId = (int) ($_GET['schedule_id'] ?? 0);

$directorUsesScheduleRows = in_array($view, ['defense-schedule', 'calendar', 'proposed-schedules', 'alternative-time-slots', 'finalize-defense-schedule'], true);
$directorUsesVerificationRows = $view === 'verify-research-defense';

// Normalize venue rows for JS compatibility (add `updated` field)
$normalizedVenueRows = array_map(static function (array $row): array {
    $row['updated'] = date('M j, Y h:i A', strtotime((string) ($row['updated_at'] ?? 'now')));
    return $row;
}, $venueRows);

$displayRows = $view === 'venues' ? $normalizedVenueRows : ($directorUsesScheduleRows ? $scheduledRows : array_map(static function (array $row) use ($requestedDefenseType): array {
    $panelCount = (int) ($row['panel_count'] ?? 0);
    $panelNames = rdPanelNamesFromString((string) ($row['panel_members'] ?? ''));
    $savedStatus = trim((string) ($row['schedule_status'] ?? ''));
    $savedStatusKey = strtolower($savedStatus);
    $status = in_array($savedStatusKey, ['scheduled', 'completed', 'passed', 'failed', 'finalized', 'final'], true)
        ? $savedStatus
        : (rdPanelAssignmentComplete($panelCount) ? 'Ready for Scheduling' : 'Needs Verification');
    $panelDetail = $panelNames ? rdPanelFormatNames($panelNames) : 'Not yet assigned';

    return [
        'reference' => (string) (($row['group_number'] ?? '') ?: ($row['group_name'] ?? 'Research Group')),
        'title' => (string) ($row['research_title'] ?? 'Research title pending'),
        'subtitle' => (string) ($row['group_name'] ?? ''),
        'owner' => (string) ($row['adviser_name'] ?? 'For adviser'),
        'detail' => $panelDetail,
        'detail_lines' => $panelNames ?: [$panelDetail],
        'status' => $status,
        'panel_count' => $panelCount,
        'panel_assignment_complete' => rdPanelAssignmentComplete($panelCount),
        'updated' => date('M j, Y h:i A', strtotime((string) ($row['updated_at'] ?? 'now'))),
        'action_url' => rdScheduleTypedUrl('manual-scheduling-optimizer', $requestedDefenseType, ['group_id' => (int) ($row['research_group_id'] ?? 0)]),
        'action_label' => 'Start Scheduling',
    ];
}, $readyRows));

if ($directorUsesScheduleRows) {
    $displayRows = array_map(static function (array $row) use ($requestedDefenseType): array {
        $proposalStatus = trim((string) ($row['proposal_status'] ?? ''));
        $progress = (int) ($row['proposal_progress'] ?? 0);
        $adviser = trim((string) ($row['adviser'] ?? ''));
        $panel = trim((string) ($row['panel_members'] ?? ''));
        $panelChair = trim((string) ($row['owner'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));
        $group = trim((string) ($row['subtitle'] ?? ''));
        $status = trim((string) ($row['status'] ?? ''));

        $isApproved = strcasecmp($proposalStatus, 'Approved') === 0;
        $hasPanel = $panel !== '' || ($panelChair !== '' && strcasecmp($panelChair, 'For panel chair') !== 0);
        $hasRequiredInfo = $title !== '' && $group !== '' && $adviser !== '' && $hasPanel;

        $row['verification'] = [
            'proposal_complete' => $isApproved || $progress >= 100,
            'approval_approved' => $isApproved,
            'required_info_complete' => $hasRequiredInfo,
            'adviser_assigned' => $adviser !== '',
            'panel_assigned' => $hasPanel,
            'ready_for_defense' => $hasRequiredInfo && in_array(strtolower($status), ['ready for scheduling', 'scheduled', 'completed', 'passed'], true),
        ];
        $row['verification_status'] = in_array(false, $row['verification'], true) ? 'Needs Verification' : 'Verified';
        $row['proceed_url'] = rdScheduleTypedUrl('proposed-schedules', $requestedDefenseType, [
            'group' => (string) ($row['reference'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
        ]);
        if (in_array(strtolower($status), ['proposed', 'selected'], true)) {
            $row['action_url'] = rdScheduleTypedUrl('finalize-defense-schedule', $requestedDefenseType, ['schedule_id' => (int) ($row['id'] ?? 0)]);
            $row['action_label'] = 'Review';
        }
        return $row;
    }, $displayRows);
}

if ($directorUsesVerificationRows) {
    $displayRows = array_map(static function (array $row): array {
        $adviser = trim((string) ($row['owner'] ?? ''));
        $panel = trim((string) ($row['detail'] ?? ''));
        $hasAdviser = $adviser !== '' && strcasecmp($adviser, 'For adviser verification') !== 0;
        $hasPanel = !empty($row['panel_assignment_complete'])
            || ($panel !== '' && strcasecmp($panel, 'Not yet assigned') !== 0 && strcasecmp($panel, 'Panel verification required') !== 0);

        $row['verification'] = [
            'proposal_complete' => true,
            'approval_approved' => true,
            'required_info_complete' => $hasAdviser && $hasPanel,
            'adviser_assigned' => $hasAdviser,
            'panel_assigned' => $hasPanel,
            'ready_for_defense' => $hasAdviser && $hasPanel,
        ];
        $row['verification_status'] = in_array(false, $row['verification'], true) ? 'Needs Verification' : 'Verified';
        $row['proceed_url'] = rdScheduleTypedUrl('manual-scheduling-optimizer', CRAD_DEFENSE_TYPE_PRE_ORAL, [
            'group' => (string) ($row['reference'] ?? ''),
            'title' => (string) ($row['title'] ?? ''),
        ]);
        return $row;
    }, $displayRows);
}

$readyCount = count($readyRows);
$scheduledCount = $officialScheduledCount;
$needsVerification = $directorUsesScheduleRows ? 0 : count(array_filter($displayRows, static function (array $row): bool {
    return strcasecmp((string) ($row['status'] ?? ''), 'Needs Verification') === 0;
}));
$completedCount = $completedScheduleCount;

if ($view === 'venues') {
    $readyCount = count($venueRows);
    $needsVerification = count(array_filter($venueRows, static fn (array $row): bool => strcasecmp((string) ($row['status'] ?? ''), 'Reserved') === 0));
    $scheduledCount = count(array_filter($venueRows, static fn (array $row): bool => strcasecmp((string) ($row['status'] ?? ''), 'Available') === 0));
    $completedCount = count(array_filter($venueRows, static fn (array $row): bool => strcasecmp((string) ($row['status'] ?? ''), 'Unavailable') === 0));
}

if ($directorUsesVerificationRows) {
    $needsVerification = count(array_filter($displayRows, static function (array $row): bool {
        return strcasecmp((string) ($row['verification_status'] ?? ''), 'Verified') !== 0;
    }));
}

if (($_GET['ajax'] ?? '') === 'director-schedules') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'rows' => $displayRows,
        'stats' => [
            'ready' => $readyCount,
            'needs_verification' => $needsVerification,
            'scheduled' => $scheduledCount,
            'completed' => $completedCount,
        ],
        'synced_at' => date('M j, Y h:i:s A'),
    ]);
    exit;
}

if (smsIsGrantedAdminRole(getCurrentUserRoleKey())) {
    $activeModule = 'crad';
    $breadcrumbs = [
        ['label' => 'CRAD', 'url' => BASE_URL . '/modules/faculty/pages/research-director.php?view=defense-scheduling-queue'],
        ['label' => $pageTitle, 'url' => null],
    ];
}

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>

<style>
    /* ── Director page — fully theme-aware ─────────────────────────────── */
    .director-stats {
        display: grid;
        gap: .85rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin: 1rem 0;
    }
    .director-stat {
        align-items: center;
        background: var(--sms-surface);
        border: 1px solid var(--sms-border);
        border-radius: 14px;
        box-shadow: var(--sms-shadow-xs);
        display: flex;
        gap: .85rem;
        min-height: 76px;
        padding: .95rem 1rem;
        transition: background .2s, border-color .2s;
    }
    .director-stat__icon {
        align-items: center;
        border-radius: 12px;
        display: inline-flex;
        flex: 0 0 42px;
        height: 42px;
        justify-content: center;
        width: 42px;
    }
    .director-stat__icon--blue  { background: var(--sms-primary-xlight); color: var(--sms-primary); }
    .director-stat__icon--amber { background: rgba(217,119,6,.15);        color: var(--sms-warning); }
    .director-stat__icon--cyan  { background: rgba(2,132,199,.15);         color: var(--sms-info); }
    .director-stat__icon--green { background: rgba(22,163,74,.15);         color: var(--sms-success); }
    .director-stat small {
        color: var(--sms-text-muted);
        display: block;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .director-stat strong {
        color: var(--sms-heading);
        display: block;
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1.1;
        margin-top: .15rem;
    }
    .director-tracking {
        background: var(--sms-surface);
        border: 1px solid var(--sms-border);
        border-radius: 16px;
        box-shadow: var(--sms-shadow-sm);
        margin-bottom: 1rem;
        overflow: hidden;
        transition: background .2s, border-color .2s;
    }
    .director-tracking__title {
        border-bottom: 1px solid var(--sms-border);
        color: var(--sms-text-muted);
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .08em;
        padding: 1rem 1.25rem;
        text-transform: uppercase;
    }
    .director-tracking__controls {
        align-items: center;
        background: var(--sms-surface-muted);
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        padding: .85rem 1.25rem;
    }
    .director-search {
        align-items: center;
        background: var(--sms-input-bg);
        border: 1px solid var(--sms-input-border);
        border-radius: 10px;
        display: flex;
        flex: 1 1 260px;
        gap: .5rem;
        min-height: 38px;
        padding: .4rem .75rem;
        transition: border-color .15s;
    }
    .director-search:focus-within {
        border-color: var(--sms-primary-light);
        box-shadow: 0 0 0 3px var(--sms-input-focus);
    }
    .director-search i { color: var(--sms-text-muted); }
    .director-search input {
        background: transparent;
        border: 0;
        color: var(--sms-text);
        font-size: .84rem;
        min-width: 0;
        outline: 0;
        width: 100%;
    }
    .director-search input::placeholder { color: var(--sms-text-faint); }
    .director-filter {
        background: var(--sms-input-bg);
        border: 1px solid var(--sms-input-border);
        border-radius: 10px;
        color: var(--sms-text);
        font-size: .84rem;
        min-height: 38px;
        outline: 0;
        padding: .4rem .75rem;
        transition: border-color .15s;
    }
    .director-filter:focus {
        border-color: var(--sms-primary-light);
        box-shadow: 0 0 0 3px var(--sms-input-focus);
    }
    .director-record {
        background: var(--sms-surface);
        border: 1px solid var(--sms-border);
        border-radius: 14px;
        box-shadow: var(--sms-shadow-sm);
        overflow: hidden;
        transition: background .2s, border-color .2s;
    }
    .director-record__head {
        align-items: center;
        border-bottom: 1px solid var(--sms-border);
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 1rem 1.15rem;
    }
    .director-record__head h2 {
        color: var(--sms-heading);
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
    }
    .director-record__head p,
    .director-record__sync,
    .director-record small {
        color: var(--sms-text-muted);
    }
    .director-record__head p {
        font-size: .86rem;
        margin: .2rem 0 0;
    }
    .director-record__sync {
        flex: 0 0 auto;
        font-size: .78rem;
        font-weight: 700;
    }
    .director-record table { margin: 0; }
    .director-record th {
        background: var(--sms-table-head-bg);
        color: var(--sms-text-muted);
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
        border-bottom: 1px solid var(--sms-table-border) !important;
    }
    .director-record td {
        color: var(--sms-text);
        font-size: .9rem;
        vertical-align: middle;
        border-color: var(--sms-table-border);
    }
    .director-record strong {
        color: var(--sms-heading);
        display: block;
        font-weight: 800;
    }
    .director-record small { display: block; margin-top: .15rem; }
    .director-record__empty { color: var(--sms-text-muted); padding: 1.2rem; text-align: center; }
    .director-panel-list { display: grid; gap: .18rem; line-height: 1.35; }
    .director-panel-list span { overflow-wrap: anywhere; }
    .director-status {
        background: var(--sms-primary-xlight);
        border-radius: 999px;
        color: var(--sms-primary);
        display: inline-flex;
        font-size: .75rem;
        font-weight: 800;
        padding: .35rem .65rem;
        white-space: nowrap;
    }
    .director-status.is-verified {
        background: rgba(22,163,74,.14);
        color: var(--sms-success);
    }
    .director-status.is-needs-verification {
        background: rgba(217,119,6,.14);
        color: var(--sms-warning);
    }
    .director-verify-list {
        display: grid;
        gap: .85rem;
        padding: 1rem;
    }
    .director-verify-card {
        background: var(--sms-surface-muted);
        border: 1px solid var(--sms-border);
        border-radius: 12px;
        padding: .95rem 1rem;
    }
    .director-verify-card__top {
        align-items: flex-start;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        margin-bottom: .75rem;
    }
    .director-verify-card__title strong {
        color: var(--sms-heading);
        font-size: 1rem;
    }
    .director-verify-grid {
        display: grid;
        gap: .55rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .director-verify-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: .8rem;
    }
    .director-proceed-btn {
        align-items: center;
        background: var(--sms-primary);
        border: 0;
        border-radius: 10px;
        box-shadow: 0 8px 18px rgba(29,78,216,.18);
        color: #fff;
        display: inline-flex;
        font-size: .82rem;
        font-weight: 800;
        gap: .45rem;
        min-height: 40px;
        padding: .62rem .9rem;
        text-decoration: none;
        text-transform: uppercase;
        transition: background .15s, box-shadow .15s;
    }
    .director-proceed-btn:hover {
        background: var(--sms-primary-dark);
        color: #fff;
        text-decoration: none;
        box-shadow: 0 10px 24px rgba(29,78,216,.28);
    }
    .director-check {
        align-items: center;
        background: var(--sms-surface);
        border: 1px solid var(--sms-border);
        border-radius: 10px;
        color: var(--sms-text);
        display: flex;
        font-size: .86rem;
        font-weight: 700;
        gap: .55rem;
        min-height: 42px;
        padding: .55rem .7rem;
    }
    .director-check i {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        flex: 0 0 22px;
        height: 22px;
        justify-content: center;
        width: 22px;
    }
    .director-check.is-ok i      { background: rgba(22,163,74,.16);  color: var(--sms-success); }
    .director-check.is-missing i { background: rgba(220,38,38,.14);  color: var(--sms-danger); }
    /* Venue form */
    .director-venue-form {
        margin-bottom: 0;
    }
    .director-venue-grid {
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(4, minmax(0, 1fr)) auto;
        align-items: end;
        padding: 1rem 1.15rem;
    }
    .director-venue-grid label {
        display: flex;
        flex-direction: column;
        gap: .35rem;
    }
    .director-venue-grid label span {
        color: var(--sms-text-muted);
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .director-venue-grid input,
    .director-venue-grid select {
        background: var(--sms-input-bg);
        border: 1px solid var(--sms-input-border);
        border-radius: 9px;
        color: var(--sms-text);
        font-size: .88rem;
        min-height: 40px;
        outline: 0;
        padding: .4rem .7rem;
        transition: border-color .15s, box-shadow .15s;
        width: 100%;
    }
    .director-venue-grid input:focus,
    .director-venue-grid select:focus {
        border-color: var(--sms-primary-light);
        box-shadow: 0 0 0 3px var(--sms-input-focus);
    }
    .director-venue-grid input::placeholder { color: var(--sms-text-faint); }
    .director-scheduler {
        background: var(--sms-surface);
        border: 1px solid var(--sms-border);
        border-radius: 14px;
        box-shadow: var(--sms-shadow-sm);
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .director-scheduler__head {
        border-bottom: 1px solid var(--sms-border);
        padding: 1rem 1.15rem;
    }
    .director-scheduler__head h2 {
        color: var(--sms-heading);
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
    }
    .director-scheduler__head p {
        color: var(--sms-text-muted);
        font-size: .84rem;
        margin: .25rem 0 0;
    }
    .director-scheduler-grid {
        display: grid;
        gap: .9rem;
        grid-template-columns: 1.1fr .9fr;
        padding: 1rem 1.15rem;
    }
    .director-scheduler-box {
        border: 1px solid var(--sms-border);
        border-radius: 10px;
        padding: .9rem;
    }
    .director-scheduler-box h3 {
        color: var(--sms-heading);
        font-size: .85rem;
        font-weight: 800;
        margin: 0 0 .65rem;
    }
    .director-scheduler-box p,
    .director-scheduler-box small {
        color: var(--sms-text-muted);
        display: block;
        margin: .15rem 0;
    }
    .director-scheduler-form {
        display: grid;
        gap: .7rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .director-scheduler-form label span {
        color: var(--sms-text-muted);
        display: block;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .04em;
        margin-bottom: .25rem;
        text-transform: uppercase;
    }
    .director-scheduler-form input,
    .director-scheduler-form select {
        background: var(--sms-input-bg);
        border: 1px solid var(--sms-input-border);
        border-radius: 10px;
        color: var(--sms-text);
        min-height: 40px;
        outline: 0;
        padding: .45rem .65rem;
        width: 100%;
    }
    .director-scheduler-form .is-wide { grid-column: 1 / -1; }
    .director-ai-scheduler {
        grid-column: 1 / -1;
        padding: 1rem 1.1rem;
        border: 1px solid #c7d2fe;
        border-radius: 12px;
        background: linear-gradient(180deg, rgba(99,102,241,.06), rgba(59,130,246,.04));
        display: grid;
        gap: .75rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .director-ai-scheduler h3 {
        grid-column: 1 / -1;
        margin: 0;
        font-size: .95rem;
        font-weight: 800;
        color: var(--sms-primary);
    }
    .director-ai-scheduler p {
        grid-column: 1 / -1;
        margin: 0;
        font-size: .82rem;
        color: var(--sms-text-muted);
    }
    .director-ai-scheduler label span {
        color: var(--sms-text-muted);
        display: block;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .04em;
        margin-bottom: .25rem;
        text-transform: uppercase;
    }
    .director-ai-scheduler input {
        background: var(--sms-input-bg);
        border: 1px solid var(--sms-input-border);
        border-radius: 10px;
        color: var(--sms-text);
        min-height: 40px;
        outline: 0;
        padding: .45rem .65rem;
        width: 100%;
    }
    .director-ai-generate-btn {
        align-self: end;
        background: linear-gradient(135deg, #4f46e5, #2563eb);
        border: 0;
        border-radius: 10px;
        color: #fff;
        cursor: pointer;
        font-size: .8rem;
        font-weight: 800;
        min-height: 40px;
        padding: .5rem .85rem;
    }
    .director-ai-generate-btn:hover { filter: brightness(1.05); }
    .director-ai-generate-btn:disabled { opacity: .65; cursor: wait; }
    .director-ai-summary {
        grid-column: 1 / -1;
        display: none;
        padding: .75rem .85rem;
        border-radius: 10px;
        background: rgba(16,185,129,.08);
        border: 1px solid rgba(16,185,129,.25);
        color: #047857;
        font-size: .82rem;
    }
    .director-ai-summary.is-error {
        background: rgba(239,68,68,.08);
        border-color: rgba(239,68,68,.25);
        color: #b91c1c;
    }
    .director-ai-slot-hints {
        grid-column: 1 / -1;
        display: grid;
        gap: .5rem;
    }
    .director-ai-slot-hint {
        padding: .55rem .7rem;
        border-radius: 8px;
        background: var(--sms-surface-muted, #f8fafc);
        border: 1px solid var(--sms-border);
        font-size: .78rem;
        color: var(--sms-text-muted);
    }
    .director-ai-slot-hint strong { color: var(--sms-text); }
    .director-action-btn,
    .director-finalize-btn {
        align-items: center;
        background: var(--sms-primary);
        border: 0;
        border-radius: 10px;
        color: #fff;
        display: inline-flex;
        font-size: .78rem;
        font-weight: 800;
        gap: .45rem;
        min-height: 34px;
        padding: .45rem .75rem;
        text-decoration: none;
    }
    .director-action-btn:hover,
    .director-finalize-btn:hover { background: var(--sms-primary-dark); color: #fff; text-decoration: none; }
    .director-confirm {
        align-items: center;
        background: rgba(15,23,42,.45);
        display: none;
        inset: 0;
        justify-content: center;
        padding: 1rem;
        position: fixed;
        z-index: 1050;
    }
    .director-confirm.is-open { display: flex; }
    .director-confirm__box {
        background: var(--sms-surface);
        border: 1px solid var(--sms-border);
        border-radius: 14px;
        box-shadow: var(--sms-shadow-lg);
        max-width: 420px;
        padding: 1.1rem;
        width: 100%;
    }
    .director-confirm__actions {
        display: flex;
        gap: .55rem;
        justify-content: flex-end;
        margin-top: 1rem;
    }
    .director-review-modal .director-confirm__box {
        background: var(--sms-surface);
        display: flex;
        flex-direction: column;
        max-height: min(720px, calc(100vh - 2rem));
        max-width: 720px;
        overflow: hidden;
        padding: 1.15rem;
    }
    .director-review-modal__title {
        color: var(--sms-heading);
        flex: 0 0 auto;
        font-size: 1rem;
        font-weight: 800;
        margin: 0 0 .85rem;
    }
    .director-review-modal__body {
        color: var(--sms-text);
        flex: 1 1 auto;
        font-size: .9rem;
        min-height: 0;
        overflow-y: auto;
        padding: .05rem .35rem .05rem 0;
    }
    .director-review-modal .director-confirm__actions {
        border-top: 1px solid var(--sms-border);
        flex: 0 0 auto;
        flex-wrap: wrap;
        padding-top: .85rem;
    }
    .director-review-detail {
        display: grid;
        gap: .7rem;
    }
    .director-review-main {
        display: grid;
        gap: .55rem .85rem;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .director-review-detail__title strong {
        color: var(--sms-heading);
        font-size: .98rem;
    }
    .director-review-detail__title span,
    .director-review-detail small {
        color: var(--sms-text-muted);
    }
    .director-review-section {
        background: var(--sms-surface-muted);
        border: 1px solid var(--sms-border);
        border-radius: 10px;
        padding: .65rem .75rem;
    }
    .director-review-section:first-child {
        background: transparent;
        border: 0;
        padding: 0;
    }
    .director-review-section.is-wide {
        grid-column: 1 / -1;
    }
    .director-review-line {
        display: block;
        line-height: 1.45;
        margin-top: .25rem;
        overflow-wrap: anywhere;
    }
    .director-review-line.is-ok { color: var(--sms-text); }
    .director-review-line.is-conflict { color: var(--sms-danger); }
    .director-review-summary {
        font-weight: 800;
        margin-top: .45rem;
    }
    .director-review-summary.is-ok { color: var(--sms-text); }
    .director-review-summary.is-conflict { color: var(--sms-danger); }
    @media (max-width: 720px) {
        .director-review-main {
            grid-template-columns: 1fr;
        }
        .director-review-modal .director-confirm__actions {
            justify-content: stretch;
        }
        .director-review-modal .director-confirm__actions > * {
            justify-content: center;
            width: 100%;
        }
    }
    /* Add / Save venue buttons */
    .director-add-venue-btn {
        align-items: center;
        background: var(--sms-primary);
        border: 0;
        border-radius: 10px;
        box-shadow: 0 6px 16px rgba(29,78,216,.2);
        color: #fff;
        cursor: pointer;
        display: inline-flex;
        font-size: .82rem;
        font-weight: 800;
        gap: .45rem;
        min-height: 40px;
        padding: .5rem 1rem;
        text-decoration: none;
        transition: background .15s, box-shadow .15s;
        white-space: nowrap;
    }
    .director-add-venue-btn:hover,
    .director-add-venue-btn:focus {
        background: var(--sms-primary-dark);
        box-shadow: 0 8px 22px rgba(29,78,216,.3);
        color: #fff;
        outline: 0;
        text-decoration: none;
    }
    .director-add-venue-btn.is-cancel       { background: var(--sms-text-muted); box-shadow: none; }
    .director-add-venue-btn.is-cancel:hover { background: var(--sms-text); color: #fff; }
    /* Inline venue status dropdown */
    .venue-status-select {
        -webkit-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23555'/%3E%3C/svg%3E");
        background-position: right .5rem center;
        background-repeat: no-repeat;
        background-size: 8px;
        border: 2px solid transparent;
        border-radius: 999px;
        cursor: pointer;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .02em;
        min-height: 30px;
        outline: 0;
        padding: .25rem 1.5rem .25rem .6rem;
        transition: box-shadow .15s, border-color .15s, opacity .15s;
    }
    .venue-status-select:focus         { border-color: var(--sms-primary-light); box-shadow: 0 0 0 3px var(--sms-input-focus); }
    .venue-status-select:disabled      { opacity: .5; cursor: not-allowed; }
    .venue-status-select.is-saving     { opacity: .6; cursor: wait; }
    .venue-status-select--available    { background-color: #d1fae5; color: #047857; border-color: #6ee7b7; }
    .venue-status-select--reserved     { background-color: #ede9fe; color: #6d28d9; border-color: #c4b5fd; }
    .venue-status-select--unavailable  { background-color: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
    [data-theme="dark"] .venue-status-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23aaa'/%3E%3C/svg%3E");
    }
    [data-theme="dark"] .venue-status-select--available   { background-color: rgba(52,211,153,.18);  color: #6ee7b7;  border-color: rgba(52,211,153,.35); }
    [data-theme="dark"] .venue-status-select--reserved    { background-color: rgba(139,92,246,.20);  color: #c4b5fd;  border-color: rgba(139,92,246,.38); }
    [data-theme="dark"] .venue-status-select--unavailable { background-color: rgba(248,113,113,.18); color: #fca5a5;  border-color: rgba(248,113,113,.35); }
    .venue-capacity-input {
        width: 5.5rem;
        padding: .35rem .55rem;
        border: 1px solid var(--sms-border, #dbe4f0);
        border-radius: 8px;
        font-size: .88rem;
        font-weight: 700;
        color: var(--sms-text, #0f172a);
        background: var(--sms-surface, #fff);
        text-align: center;
    }
    .venue-capacity-input:focus {
        border-color: var(--sms-primary-light);
        box-shadow: 0 0 0 3px var(--sms-input-focus);
        outline: none;
    }
    .venue-capacity-input:disabled { opacity: .55; cursor: wait; }
    .venue-capacity-input.is-saving { opacity: .65; }
    [data-theme="dark"] .venue-capacity-input {
        background: var(--sms-surface-muted, #1e293b);
        color: var(--sms-text, #e2e8f0);
        border-color: rgba(148,163,184,.28);
    }
    /* ── Responsive ───────────────────────────────────────────────────── */
    @media (max-width: 1100px) {
        .director-stats            { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .director-venue-grid       { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .director-scheduler-grid   { grid-template-columns: 1fr; }
        .director-venue-grid > div { grid-column: span 2; }
    }
    @media (max-width: 720px) {
        .director-stats            { grid-template-columns: 1fr; }
        .director-record__head     { align-items: flex-start; flex-direction: column; }
        .director-filter           { width: 100%; }
        .director-verify-card__top { flex-direction: column; }
        .director-verify-grid      { grid-template-columns: 1fr; }
        .director-venue-grid       { grid-template-columns: 1fr; }
        .director-scheduler-form   { grid-template-columns: 1fr; }
        .director-ai-scheduler     { grid-template-columns: 1fr; }
        .director-venue-grid > div { grid-column: 1; }
    }
</style>

<div class="director-stats">
    <section class="director-stat">
        <span class="director-stat__icon director-stat__icon--blue"><?= smsIcon('list-alt', ['aria-hidden' => 'true']) ?></span>
        <div>
            <small><?= $view === 'venues' ? 'Total Venues' : 'Ready for Scheduling' ?></small>
            <strong data-director-stat="ready"><?= (int) $readyCount ?></strong>
        </div>
    </section>
    <section class="director-stat">
        <span class="director-stat__icon director-stat__icon--amber"><?= smsIcon('check-double', ['aria-hidden' => 'true']) ?></span>
        <div>
            <small><?= $view === 'venues' ? 'Reserved' : 'Needs Verification' ?></small>
            <strong data-director-stat="needs_verification"><?= (int) $needsVerification ?></strong>
        </div>
    </section>
    <section class="director-stat">
        <span class="director-stat__icon director-stat__icon--cyan"><?= smsIcon('calendar-check', ['aria-hidden' => 'true']) ?></span>
        <div>
            <small><?= $view === 'venues' ? 'Available' : 'Scheduled Defenses' ?></small>
            <strong data-director-stat="scheduled"><?= (int) $scheduledCount ?></strong>
        </div>
    </section>
    <section class="director-stat">
        <span class="director-stat__icon director-stat__icon--green"><?= smsIcon('check-circle', ['aria-hidden' => 'true']) ?></span>
        <div>
            <small><?= $view === 'venues' ? 'Unavailable' : 'Completed' ?></small>
            <strong data-director-stat="completed"><?= (int) $completedCount ?></strong>
        </div>
    </section>
</div>

<section class="director-tracking">
    <div class="director-tracking__title"><?= htmlspecialchars($pageTitle) ?> Tracking</div>
    <div class="director-tracking__controls">
        <label class="director-search">
            <?= smsIcon('search', ['aria-hidden' => 'true']) ?>
            <input type="search" data-director-search placeholder="<?= $view === 'venues' ? 'Search by venue, capacity, type, or status...' : 'Search by group, title, adviser, or panel...' ?>">
        </label>
        <select class="director-filter" data-director-status>
            <option value="all">All Status</option>
            <?php if ($view === 'venues'): ?>
                <option value="available">Available</option>
                <option value="reserved">Reserved</option>
                <option value="unavailable">Unavailable</option>
            <?php elseif ($directorUsesVerificationRows): ?>
                <option value="verified">Verified</option>
                <option value="needs-verification">Needs Verification</option>
            <?php else: ?>
                <option value="ready">Ready for Scheduling</option>
                <option value="needs-verification">Needs Verification</option>
                <option value="proposed">Proposed</option>
                <option value="scheduled">Scheduled</option>
                <option value="completed">Completed</option>
            <?php endif; ?>
        </select>
    </div>
</section>

<?php if ($view === 'venues'): ?>
    <?php if ($venueMessage): ?>
        <div class="alert alert-<?= htmlspecialchars($venueMessage['type']) ?> mb-3">
            <?= htmlspecialchars($venueMessage['text']) ?>
        </div>
    <?php endif; ?>
<?php elseif ($venueMessage): ?>
    <div class="alert alert-<?= htmlspecialchars($venueMessage['type']) ?> mb-3">
        <?= htmlspecialchars($venueMessage['text']) ?>
    </div>
<?php endif; ?>

<?php if ($isSchedulerView): ?>
    <section class="director-scheduler">
        <div class="director-scheduler__head">
            <h2><?= $view === 'alternative-time-slots' ? 'Add Alternative Time Slot' : ($requestedDefenseType === CRAD_DEFENSE_TYPE_FINAL ? 'Final Defense AI Scheduling Optimizer' : 'AI Scheduling Optimizer') ?></h2>
            <p><?= $selectedReadyGroup ? 'Create proposed ' . htmlspecialchars($defenseTypeLabel) . ' slots from current database records.' : 'Select a defense-ready research before creating a ' . htmlspecialchars($defenseTypeLabel) . ' schedule.' ?></p>
        </div>
        <?php if (!$hasExplicitGroupSelection): ?>
            <div class="director-scheduler-grid">
                <div class="director-scheduler-box">
                    <h3>No Research Selected</h3>
                    <p>Please select a defense-ready research before creating a <?= htmlspecialchars($defenseTypeLabel) ?> schedule.</p>
                    <a class="director-action-btn" href="<?= htmlspecialchars(rdScheduleTypedUrl('defense-scheduling-queue', $requestedDefenseType)) ?>">
                        <?= smsIcon('list-alt', ['aria-hidden' => 'true']) ?>
                        View Ready for Scheduling
                    </a>
                </div>
            </div>
        <?php elseif (!$selectedReadyGroup): ?>
            <div class="director-scheduler-grid">
                <div class="director-scheduler-box">
                    <h3><?= !$selectedGroupIsOfficial ? 'Research group is no longer available in the official Capstone Group/Student Registry.' : 'Scheduling is currently unavailable for this research.' ?></h3>
                    <p><?= !$selectedGroupIsOfficial ? 'This group can no longer be used in ' . htmlspecialchars($defenseTypeLabel) . ' scheduling.' : 'One or more required records are no longer complete.' ?></p>
                    <a class="director-action-btn" href="<?= htmlspecialchars(rdScheduleTypedUrl('defense-scheduling-queue', $requestedDefenseType)) ?>">
                        <?= smsIcon('arrow-left', ['aria-hidden' => 'true']) ?>
                        Back to Ready for Scheduling
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="director-scheduler-grid">
                <div class="director-scheduler-box">
                    <h3>Selected Research</h3>
                    <strong><?= htmlspecialchars((string) ($selectedReadyGroup['group_name'] ?? 'Research Group')) ?></strong>
                    <p><?= htmlspecialchars((string) ($selectedReadyGroup['research_title'] ?? 'Research title pending')) ?></p>
                    <small>Group Number: <?= htmlspecialchars((string) (($selectedReadyGroup['group_number'] ?? '') ?: 'N/A')) ?></small>
                    <small>Academic Year: <?= htmlspecialchars((string) (($selectedReadyGroup['academic_year'] ?? '') ?: 'N/A')) ?></small>
                    <small>Adviser: <?= htmlspecialchars((string) ($selectedReadyGroup['adviser_name'] ?? 'For adviser')) ?> (<?= htmlspecialchars((string) (($selectedReadyGroup['adviser_availability'] ?? '') ?: 'Pending')) ?>)</small>
                    <small>Research Group: <?= $selectedGroupHasOfficial ? 'Already Scheduled' : 'Ready / No Official Schedule' ?></small>
                    <div class="director-panel-list" style="margin-top:.55rem;">
                        <?php foreach ($selectedPanelRows as $panelRow): ?>
                            <span><?= htmlspecialchars((string) ($panelRow['panel_name'] ?? 'Panel Member')) ?> (<?= htmlspecialchars((string) (($panelRow['availability_status'] ?? '') ?: 'Pending')) ?>)</span>
                        <?php endforeach; ?>
                    </div>
                    <small><?= count($selectedPanelRows) ?> of <?= RD_SCHEDULE_MAX_PANEL_MEMBERS ?> Panel Members assigned</small>
                </div>
                <form method="post" class="director-scheduler-box director-scheduler-form" id="directorSchedulerForm" data-group-id="<?= (int) $selectedGroupId ?>" data-defense-type="<?= htmlspecialchars($requestedDefenseType) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="schedule_action" value="save_proposed">
                    <input type="hidden" name="research_group_id" value="<?= (int) $selectedGroupId ?>">
                    <input type="hidden" name="defense_type" value="<?= htmlspecialchars($requestedDefenseType) ?>">
                    <?php if ($view === 'manual-scheduling-optimizer'): ?>
                    <div class="director-ai-scheduler" id="directorAiScheduler">
                        <h3><?= smsIcon('magic', ['class' => 'me-1']) ?> AI Scheduling Optimizer</h3>
                        <p>Set your defense period. AI checks live free schedules and picks different dates, times, and venues where the adviser, panel, and rooms are free.</p>
                        <label>
                            <span>Period Start</span>
                            <input type="date" id="aiPeriodStart" min="<?= htmlspecialchars(date('Y-m-d')) ?>" value="<?= htmlspecialchars(date('Y-m-d')) ?>">
                        </label>
                        <label>
                            <span>Period End</span>
                            <input type="date" id="aiPeriodEnd" min="<?= htmlspecialchars(date('Y-m-d')) ?>" value="<?= htmlspecialchars(date('Y-m-d', strtotime('+30 days'))) ?>">
                        </label>
                        <label>
                            <span>Expected Attendees</span>
                            <input type="number" id="aiExpectedAttendees" min="1" max="500" value="15" inputmode="numeric">
                        </label>
                        <button type="button" class="director-ai-generate-btn" id="aiGenerateSlotsBtn">
                            <?= smsIcon('robot', ['class' => 'me-1']) ?> Generate Optimal Slots
                        </button>
                        <div class="director-ai-summary" id="aiScheduleSummary" role="status"></div>
                        <div class="director-ai-slot-hints" id="aiSlotHints"></div>
                    </div>
                    <?php endif; ?>
                    <label class="is-wide">
                        <span>Research Group</span>
                        <input type="text" value="<?= htmlspecialchars((string) ($selectedReadyGroup['group_name'] ?? 'Research Group')) ?>" readonly>
                    </label>
                    <?php $visibleSlotCount = 3; ?>
                    <?php for ($slotNumber = 1; $slotNumber <= $visibleSlotCount; $slotNumber++): ?>
                        <label>
                            <span>Slot <?= $slotNumber ?> Date</span>
                            <input type="date" name="defense_date[]" class="js-defense-date" min="<?= htmlspecialchars(date('Y-m-d')) ?>" <?= $slotNumber <= 2 && $view === 'manual-scheduling-optimizer' ? 'required' : '' ?>>
                        </label>
                        <label>
                            <span>Slot <?= $slotNumber ?> Venue</span>
                            <select name="venue_id[]" class="js-venue-id" <?= $slotNumber <= 2 && $view === 'manual-scheduling-optimizer' ? 'required' : '' ?>>
                                <option value="">Select venue</option>
                                <?php foreach ($allVenueRows as $venueRow): ?>
                                    <option value="<?= (int) ($venueRow['id'] ?? 0) ?>">
                                        <?= htmlspecialchars((string) ($venueRow['venue_name'] ?? 'Venue')) ?> - <?= htmlspecialchars((string) ($venueRow['status'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>
                            <span>Slot <?= $slotNumber ?> Start</span>
                            <input type="time" name="start_time[]" class="js-start-time" <?= $slotNumber <= 2 && $view === 'manual-scheduling-optimizer' ? 'required' : '' ?>>
                        </label>
                        <label>
                            <span>Slot <?= $slotNumber ?> End</span>
                            <input type="time" name="end_time[]" class="js-end-time" <?= $slotNumber <= 2 && $view === 'manual-scheduling-optimizer' ? 'required' : '' ?>>
                        </label>
                    <?php endfor; ?>
                    <div class="is-wide">
                        <button type="submit" class="director-action-btn">
                            <?= smsIcon('save', ['aria-hidden' => 'true']) ?>
                            Save Proposed Slots
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if (!$isSchedulerView): ?>
<section class="director-record">
    <div class="director-record__head">
        <div>
            <h2><?= $view === 'defense-scheduling-queue' ? 'Research Defense Scheduling Queue' : ($view === 'venues' ? 'Venue List' : ($view === 'verify-research-defense' ? 'Verify Proposal Complete & Approved' : htmlspecialchars($pageTitle))) ?></h2>
            <p><?= $view === 'venues' ? 'Manage defense venues, capacity, type, and availability status.' : ($view === 'verify-research-defense' ? 'Check approved, complete, assigned, and ready-for-defense records from Research Defense Scheduling.' : ($view === 'defense-scheduling-queue' ? 'Realtime records from accepted Chapter 1-3 evaluations.' : 'Realtime records for this module.')) ?></p>
        </div>
        <?php if ($view === 'venues'): ?>
            <button type="button" class="director-add-venue-btn" data-director-add-venue>
                <?= smsIcon('plus', ['aria-hidden' => 'true']) ?>
                Add Venue
            </button>
        <?php endif; ?>
        <span class="director-record__sync" data-director-sync>Synced <?= htmlspecialchars(date('M j, Y h:i:s A')) ?></span>
    </div>
    <?php if ($view === 'venues'): ?>
        <div class="director-venue-form" data-director-venue-form style="display:none; border-top: 1px solid var(--sms-border, #dbe4f0);">
            <form method="post" class="director-venue-grid">
                <?= csrfField() ?>
                <input type="hidden" name="venue_action" value="add">
                <label>
                    <span>Venue Name</span>
                    <input type="text" name="venue_name" required placeholder="e.g. CRAD Conference Room">
                </label>
                <label>
                    <span>Capacity</span>
                    <input type="number" name="capacity" min="1" required placeholder="30">
                </label>
                <label>
                    <span>Type</span>
                    <input type="text" name="venue_type" required placeholder="e.g. Conference Room">
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" required>
                        <option value="Available">Available</option>
                        <option value="Reserved">Reserved</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </label>
                <div style="display:flex; gap:.5rem; align-items:flex-end;">
                    <button type="submit" class="director-add-venue-btn">
                        <?= smsIcon('save', ['aria-hidden' => 'true']) ?>
                        Save Venue
                    </button>
                    <button type="button" class="director-add-venue-btn is-cancel" data-director-cancel-venue>
                        <?= smsIcon('times', ['aria-hidden' => 'true']) ?>
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Venue</th>
                        <th>Capacity</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Current Schedule</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody data-director-rows>
                    <?php foreach ($venueRows as $row): ?>
                        <?php
                            $vid          = (int) ($row['id'] ?? 0);
                            $vStatusVal   = (string) ($row['status'] ?? 'Available');
                            $vStatusKey   = strtolower($vStatusVal);
                        ?>
                        <tr data-director-row data-status="<?= htmlspecialchars($vStatusKey) ?>" data-venue-id="<?= $vid ?>">
                            <td><strong><?= htmlspecialchars((string) ($row['venue_name'] ?? 'Venue')) ?></strong></td>
                            <td>
                                <input type="number"
                                       class="venue-capacity-input"
                                       data-venue-capacity-input
                                       data-venue-id="<?= $vid ?>"
                                       value="<?= (int) ($row['capacity'] ?? 0) ?>"
                                       min="1"
                                       step="1"
                                       inputmode="numeric"
                                       aria-label="Capacity for <?= htmlspecialchars((string) ($row['venue_name'] ?? 'venue')) ?>">
                            </td>
                            <td><?= htmlspecialchars((string) ($row['venue_type'] ?? '')) ?></td>
                            <td>
                                <select class="venue-status-select venue-status-select--<?= htmlspecialchars($vStatusKey) ?>"
                                        data-venue-status-select
                                        data-venue-id="<?= $vid ?>"
                                        aria-label="Status for <?= htmlspecialchars((string) ($row['venue_name'] ?? 'venue')) ?>">
                                    <option value="Available"   <?= $vStatusVal === 'Available'   ? 'selected' : '' ?>>Available</option>
                                    <option value="Reserved"    <?= $vStatusVal === 'Reserved'    ? 'selected' : '' ?>>Reserved</option>
                                    <option value="Unavailable" <?= $vStatusVal === 'Unavailable' ? 'selected' : '' ?>>Unavailable</option>
                                </select>
                            </td>
                            <td><?= htmlspecialchars((string) (($row['current_schedule'] ?? '') ?: 'None')) ?></td>
                            <td class="venue-updated-cell"><?= htmlspecialchars(date('M j, Y h:i A', strtotime((string) ($row['updated_at'] ?? 'now')))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($view === 'verify-research-defense'): ?>
        <div class="director-verify-list" data-director-rows>
            <?php foreach ($displayRows as $row): ?>
                <?php
                    $verification = is_array($row['verification'] ?? null) ? $row['verification'] : [];
                    $verificationStatus = (string) ($row['verification_status'] ?? 'Needs Verification');
                    $statusKey = strcasecmp($verificationStatus, 'Verified') === 0 ? 'verified' : 'needs-verification';
                    $checks = [
                        'Proposal Complete' => (bool) ($verification['proposal_complete'] ?? false),
                        'Approval Approved' => (bool) ($verification['approval_approved'] ?? false),
                        'Required Info Complete' => (bool) ($verification['required_info_complete'] ?? false),
                        'Adviser Assigned' => (bool) ($verification['adviser_assigned'] ?? false),
                        'Panel Assigned' => (bool) ($verification['panel_assigned'] ?? false),
                        'Ready for Defense' => (bool) ($verification['ready_for_defense'] ?? false),
                    ];
                ?>
                <article class="director-verify-card" data-director-row data-status="<?= htmlspecialchars($statusKey) ?>">
                    <div class="director-verify-card__top">
                        <div class="director-verify-card__title">
                            <strong><?= htmlspecialchars((string) ($row['subtitle'] ?: $row['reference'] ?: 'Research Group')) ?></strong>
                            <small><?= htmlspecialchars((string) ($row['title'] ?? 'Research title pending')) ?></small>
                        </div>
                        <span class="director-status <?= $statusKey === 'verified' ? 'is-verified' : 'is-needs-verification' ?>">
                            <?= htmlspecialchars($verificationStatus) ?>
                        </span>
                    </div>
                    <div class="director-verify-grid">
                        <?php foreach ($checks as $label => $isOk): ?>
                            <span class="director-check <?= $isOk ? 'is-ok' : 'is-missing' ?>">
                                <?= smsIcon($isOk ? 'check' : 'times', ['aria-hidden' => 'true']) ?>
                                <?= htmlspecialchars($label) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($statusKey === 'verified'): ?>
                        <div class="director-verify-actions">
                            <a class="director-proceed-btn" href="<?= htmlspecialchars((string) ($row['proceed_url'] ?? '#')) ?>">
                                <?= smsIcon('arrow-right', ['aria-hidden' => 'true']) ?>
                                VERIFY &amp; PROCEED
                            </a>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Reference No.</th>
                    <th>Research Title / Group</th>
                    <th><?= $directorUsesScheduleRows ? 'Panel Members' : 'Adviser' ?></th>
                    <th><?= $directorUsesScheduleRows ? 'Office / Detail' : 'Panel Members' ?></th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody data-director-rows>
                <?php foreach ($displayRows as $row): ?>
                    <?php
                        $statusValue = trim((string) ($row['status'] ?? 'Ready for Scheduling'));
                        $lowerStatus = strtolower($statusValue);
                        $statusKey = 'scheduled';
                        if ($lowerStatus === 'ready for scheduling') {
                            $statusKey = 'ready';
                        } elseif ($lowerStatus === 'needs verification') {
                            $statusKey = 'needs-verification';
                        } elseif (in_array($lowerStatus, ['proposed', 'selected'], true)) {
                            $statusKey = 'proposed';
                        } elseif (in_array($lowerStatus, ['completed', 'passed'], true)) {
                            $statusKey = 'completed';
                        }
                    ?>
                    <tr data-director-row data-status="<?= htmlspecialchars($statusKey) ?>">
                        <td class="fw-semibold"><?= htmlspecialchars((string) ($row['reference'] ?? 'For Scheduling')) ?></td>
                        <td>
                            <strong><?= htmlspecialchars((string) ($row['title'] ?? 'Research title pending')) ?></strong>
                            <?php if (!empty($row['subtitle'])): ?>
                                <small><?= htmlspecialchars((string) $row['subtitle']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($directorUsesScheduleRows && !empty($row['owner_lines']) && is_array($row['owner_lines'])): ?>
                                <div class="director-panel-list">
                                    <?php foreach ($row['owner_lines'] as $panelLine): ?>
                                        <span><?= htmlspecialchars((string) $panelLine) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <?= htmlspecialchars((string) ($row['owner'] ?? 'For panel chair')) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$directorUsesScheduleRows && !empty($row['detail_lines']) && is_array($row['detail_lines'])): ?>
                                <div class="director-panel-list">
                                    <?php foreach ($row['detail_lines'] as $panelLine): ?>
                                        <span><?= htmlspecialchars((string) $panelLine) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <?= htmlspecialchars((string) ($row['detail'] ?? 'Ready for venue')) ?>
                            <?php endif; ?>
                        </td>
                        <td><span class="director-status"><?= htmlspecialchars($statusValue) ?></span></td>
                        <td><?= htmlspecialchars((string) ($row['updated'] ?? '')) ?></td>
                        <td>
                            <?php if ($view === 'proposed-schedules' && in_array($lowerStatus, ['proposed', 'selected'], true)): ?>
                                <button type="button"
                                        class="director-action-btn"
                                        data-review-schedule
                                        data-schedule-id="<?= (int) ($row['id'] ?? 0) ?>">
                                    <?= smsIcon('eye', ['aria-hidden' => 'true']) ?>
                                    Review
                                </button>
                            <?php elseif ($view === 'finalize-defense-schedule' && $lowerStatus === 'selected'): ?>
                                <button type="button"
                                        class="director-finalize-btn"
                                        data-finalize-schedule
                                        data-schedule-id="<?= (int) ($row['id'] ?? 0) ?>">
                                    <?= smsIcon('clipboard-check', ['aria-hidden' => 'true']) ?>
                                    Finalize
                                </button>
                            <?php elseif (!empty($row['action_url']) && !empty($row['action_label'])): ?>
                                <a class="director-action-btn" href="<?= htmlspecialchars((string) $row['action_url']) ?>">
                                    <?= smsIcon('arrow-right', ['aria-hidden' => 'true']) ?>
                                    <?= htmlspecialchars((string) $row['action_label']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    <div class="director-record__empty" data-director-empty <?= $displayRows ? 'hidden' : '' ?>>
        No records found.
    </div>
</section>
<?php endif; ?>

<?php if ($view === 'finalize-defense-schedule'): ?>
    <div class="director-confirm" data-finalize-modal aria-hidden="true">
        <div class="director-confirm__box" role="dialog" aria-modal="true" aria-labelledby="finalize-title">
            <h2 id="finalize-title" style="font-size:1rem; font-weight:800; margin:0 0 .45rem;">Finalize Pre-Oral Defense Schedule</h2>
            <p style="color:var(--sms-text-muted); margin:0;">Confirm this proposed slot as the official schedule. Other proposed slots for the same group will be marked rejected.</p>
            <div class="director-confirm__actions">
                <button type="button" class="director-add-venue-btn is-cancel" data-finalize-cancel>Cancel</button>
                <button type="button" class="director-finalize-btn" data-finalize-confirm>
                    <?= smsIcon('check', ['aria-hidden' => 'true']) ?>
                    Confirm
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($view === 'proposed-schedules'): ?>
    <div class="director-confirm director-review-modal" data-review-modal aria-hidden="true">
        <div class="director-confirm__box" role="dialog" aria-modal="true" aria-labelledby="review-title">
            <h2 id="review-title" class="director-review-modal__title">Proposed Schedule Details</h2>
            <div class="director-review-modal__body" data-review-body>Loading...</div>
            <div class="director-confirm__actions">
                <a class="director-add-venue-btn is-cancel" data-review-alternative href="#">
                    <?= smsIcon('clock', ['aria-hidden' => 'true']) ?>
                    Find Alternative Slots
                </a>
                <button type="button" class="director-finalize-btn" data-review-choose>
                    <?= smsIcon('check', ['aria-hidden' => 'true']) ?>
                    Choose This Schedule
                </button>
                <button type="button" class="director-add-venue-btn is-cancel" data-review-close>Close</button>
            </div>
        </div>
    </div>
    <div class="director-confirm" data-choose-modal aria-hidden="true">
        <div class="director-confirm__box" role="dialog" aria-modal="true" aria-labelledby="choose-title">
            <h2 id="choose-title" style="font-size:1rem; font-weight:800; margin:0 0 .45rem;">Choose This Proposed Schedule?</h2>
            <div data-choose-body style="color:var(--sms-text-muted); margin:0;">This schedule will be selected for final review. It will not become official until it is finalized.</div>
            <div class="director-confirm__actions">
                <button type="button" class="director-add-venue-btn is-cancel" data-choose-cancel>Cancel</button>
                <button type="button" class="director-finalize-btn" data-choose-confirm>
                    <?= smsIcon('check', ['aria-hidden' => 'true']) ?>
                    Choose Schedule
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const isVerifyView = <?= $view === 'verify-research-defense' ? 'true' : 'false' ?>;
    const isVenueView  = <?= $view === 'venues' ? 'true' : 'false' ?>;
    const isFinalizeView = <?= $view === 'finalize-defense-schedule' ? 'true' : 'false' ?>;
    const currentDefenseType = <?= json_encode($requestedDefenseType) ?>;
    const isProposedView = <?= $view === 'proposed-schedules' ? 'true' : 'false' ?>;
    const isManualOptimizerView = <?= $view === 'manual-scheduling-optimizer' ? 'true' : 'false' ?>;
    const csrfToken    = <?= json_encode(csrfToken()) ?>;
    const pageUrl      = window.location.pathname + '?view=venues';
    const search = document.querySelector('[data-director-search]');
    const status = document.querySelector('[data-director-status]');
    const rowsBody = document.querySelector('[data-director-rows]');
    const empty = document.querySelector('[data-director-empty]');
    const sync = document.querySelector('[data-director-sync]');
    const statNodes = document.querySelectorAll('[data-director-stat]');
    let rows = Array.from(document.querySelectorAll('[data-director-row]'));
    let refreshing = false;
    let timer = null;

    const esc = function (value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    };

    const statusKey = function (value) {
        const text = String(value || '').toLowerCase();
        if (text === 'ready for scheduling') return 'ready';
        if (text === 'needs verification') return 'needs-verification';
        if (text === 'completed' || text === 'passed') return 'completed';
        if (text === 'proposed' || text === 'selected') return 'proposed';
        return 'scheduled';
    };

    const verificationStatus = function (row) {
        const checks = row.verification || {};
        const keys = ['proposal_complete', 'approval_approved', 'required_info_complete', 'adviser_assigned', 'panel_assigned', 'ready_for_defense'];
        const verified = keys.every(function (key) { return checks[key] === true; });
        return verified ? 'Verified' : 'Needs Verification';
    };

    const renderVerificationChecks = function (checks) {
        const labels = [
            ['proposal_complete', 'Proposal Complete'],
            ['approval_approved', 'Approval Approved'],
            ['required_info_complete', 'Required Info Complete'],
            ['adviser_assigned', 'Adviser Assigned'],
            ['panel_assigned', 'Panel Assigned'],
            ['ready_for_defense', 'Ready for Defense']
        ];
        return labels.map(function (item) {
            const ok = checks && checks[item[0]] === true;
            return '<span class="director-check ' + (ok ? 'is-ok' : 'is-missing') + '">' +
                (window.smsIconHtml ? window.smsIconHtml(ok ? 'check' : 'times', '', {'aria-hidden': 'true'}) : '') +
                esc(item[1]) +
            '</span>';
        }).join('');
    };

    const renderStats = function (stats) {
        statNodes.forEach(function (node) {
            const key = node.dataset.directorStat;
            node.textContent = stats && Object.prototype.hasOwnProperty.call(stats, key)
                ? String(stats[key])
                : '0';
        });
    };

    const renderRows = function (list) {
        if (!rowsBody) return;
        if (!Array.isArray(list) || list.length === 0) {
            rowsBody.innerHTML = '';
            rows = [];
            applyFilters();
            return;
        }

        if (isVenueView) {
            rowsBody.innerHTML = list.map(function (row) {
                const s    = String(row.status || 'Available').toLowerCase();
                const vid  = parseInt(row.id, 10) || 0;
                const opts = ['Available', 'Reserved', 'Unavailable'].map(function (opt) {
                    const sel = opt.toLowerCase() === s ? ' selected' : '';
                    return '<option value="' + opt + '"' + sel + '>' + opt + '</option>';
                }).join('');
                return '<tr data-director-row data-status="' + esc(s) + '" data-venue-id="' + vid + '">' +
                    '<td><strong>' + esc(row.venue_name || 'Venue') + '</strong></td>' +
                    '<td><input type="number" class="venue-capacity-input" data-venue-capacity-input ' +
                        'data-venue-id="' + vid + '" value="' + (parseInt(row.capacity, 10) || 0) + '" ' +
                        'min="1" step="1" inputmode="numeric" ' +
                        'aria-label="Capacity for ' + esc(row.venue_name || 'venue') + '"></td>' +
                    '<td>' + esc(row.venue_type || '') + '</td>' +
                    '<td><select class="venue-status-select venue-status-select--' + esc(s) + '" ' +
                        'data-venue-status-select data-venue-id="' + vid + '" ' +
                        'aria-label="Status for ' + esc(row.venue_name || 'venue') + '">' +
                        opts + '</select></td>' +
                    '<td>' + esc(row.current_schedule || 'None') + '</td>' +
                    '<td class="venue-updated-cell">' + esc(row.updated || '') + '</td>' +
                '</tr>';
            }).join('');
            rows = Array.from(document.querySelectorAll('[data-director-row]'));
            bindStatusSelects();
            bindCapacityInputs();
            applyFilters();
            return;
        }

        if (isVerifyView) {
            rowsBody.innerHTML = list.map(function (row) {
                const verifyStatus = row.verification_status || verificationStatus(row);
                const key = verifyStatus.toLowerCase().replace(/\s+/g, '-');
                return '<article class="director-verify-card" data-director-row data-status="' + esc(key) + '">' +
                    '<div class="director-verify-card__top">' +
                        '<div class="director-verify-card__title">' +
                            '<strong>' + esc(row.subtitle || row.reference || 'Research Group') + '</strong>' +
                            '<small>' + esc(row.title || 'Research title pending') + '</small>' +
                        '</div>' +
                        '<span class="director-status ' + (key === 'verified' ? 'is-verified' : 'is-needs-verification') + '">' + esc(verifyStatus) + '</span>' +
                    '</div>' +
                    '<div class="director-verify-grid">' + renderVerificationChecks(row.verification || {}) + '</div>' +
                    (key === 'verified'
                        ? '<div class="director-verify-actions"><a class="director-proceed-btn" href="' + esc(row.proceed_url || '#') + '"><?= smsIcon('arrow-right', ['aria-hidden' => 'true']) ?>VERIFY &amp; PROCEED</a></div>'
                        : '') +
                '</article>';
            }).join('');
            rows = Array.from(document.querySelectorAll('[data-director-row]'));
            applyFilters();
            return;
        }

        rowsBody.innerHTML = list.map(function (row) {
            const key = statusKey(row.status);
            const owner = Array.isArray(row.owner_lines) && row.owner_lines.length
                ? '<div class="director-panel-list">' + row.owner_lines.map(function (line) {
                    return '<span>' + esc(line) + '</span>';
                }).join('') + '</div>'
                : esc(row.owner || 'For panel chair');
            const detail = Array.isArray(row.detail_lines) && row.detail_lines.length
                ? '<div class="director-panel-list">' + row.detail_lines.map(function (line) {
                    return '<span>' + esc(line) + '</span>';
                }).join('') + '</div>'
                : esc(row.detail || 'Ready for venue');
            const action = isProposedView && (String(row.status || '').toLowerCase() === 'proposed' || String(row.status || '').toLowerCase() === 'selected')
                ? '<button type="button" class="director-action-btn" data-review-schedule data-schedule-id="' + (parseInt(row.id, 10) || 0) + '"><?= smsIcon('eye', ['aria-hidden' => 'true']) ?>Review</button>'
                : (isFinalizeView && String(row.status || '').toLowerCase() === 'selected'
                ? '<button type="button" class="director-finalize-btn" data-finalize-schedule data-schedule-id="' + (parseInt(row.id, 10) || 0) + '"><?= smsIcon('clipboard-check', ['aria-hidden' => 'true']) ?>Finalize</button>'
                : (row.action_url && row.action_label
                    ? '<a class="director-action-btn" href="' + esc(row.action_url) + '"><?= smsIcon('arrow-right', ['aria-hidden' => 'true']) ?>' + esc(row.action_label) + '</a>'
                    : '<span class="text-muted">-</span>'));
            return '<tr data-director-row data-status="' + esc(key) + '">' +
                '<td class="fw-semibold">' + esc(row.reference || 'For Scheduling') + '</td>' +
                '<td><strong>' + esc(row.title || 'Research title pending') + '</strong>' +
                    (row.subtitle ? '<small>' + esc(row.subtitle) + '</small>' : '') +
                '</td>' +
                '<td>' + owner + '</td>' +
                '<td>' + detail + '</td>' +
                '<td><span class="director-status">' + esc(row.status || 'Ready for Scheduling') + '</span></td>' +
                '<td>' + esc(row.updated || '') + '</td>' +
                '<td>' + action + '</td>' +
            '</tr>';
        }).join('');
        rows = Array.from(document.querySelectorAll('[data-director-row]'));
        bindFinalizeButtons();
        bindReviewButtons();
        applyFilters();
    };

    /* ── Venue status dropdown handler ─────────────────────────────────── */
    const bindStatusSelects = function () {
        document.querySelectorAll('[data-venue-status-select]').forEach(function (sel) {
            if (sel.dataset.bound === '1') return;
            sel.dataset.bound = '1';
            sel.addEventListener('change', async function () {
                const venueId   = parseInt(sel.dataset.venueId, 10);
                const newStatus = sel.value;
                const row       = sel.closest('[data-director-row]');
                const updCell   = row ? row.querySelector('.venue-updated-cell') : null;
                sel.disabled = true;
                sel.classList.add('is-saving');
                try {
                    const body = new URLSearchParams();
                    body.set('venue_action', 'update_status');
                    body.set('venue_id', venueId);
                    body.set('status', newStatus);
                    body.set('csrf_token', csrfToken);
                    const res = await fetch(pageUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: body.toString()
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Save failed');
                    const s = newStatus.toLowerCase();
                    if (row) row.dataset.status = s;
                    sel.className = 'venue-status-select venue-status-select--' + s;
                    // keep bound=1 so duplicate listeners aren't added
                    if (updCell) {
                        const now = new Date();
                        updCell.textContent = now.toLocaleString('en-US', {
                            month: 'short', day: 'numeric', year: 'numeric',
                            hour: 'numeric', minute: '2-digit', hour12: true
                        });
                    }
                } catch (err) {
                    alert('Could not save status. Please try again.');
                }
                sel.disabled = false;
                sel.classList.remove('is-saving');
            });
        });
    };

    const bindCapacityInputs = function () {
        document.querySelectorAll('[data-venue-capacity-input]').forEach(function (inp) {
            if (inp.dataset.bound === '1') return;
            inp.dataset.bound = '1';
            inp.dataset.lastSaved = String(parseInt(inp.value, 10) || 0);

            const saveCapacity = async function () {
                const venueId = parseInt(inp.dataset.venueId, 10);
                const capacity = parseInt(inp.value, 10);
                const lastSaved = parseInt(inp.dataset.lastSaved, 10) || 0;
                if (!capacity || capacity < 1) {
                    inp.value = String(lastSaved || 1);
                    return;
                }
                if (capacity === lastSaved) return;

                const row = inp.closest('[data-director-row]');
                const updCell = row ? row.querySelector('.venue-updated-cell') : null;
                inp.disabled = true;
                inp.classList.add('is-saving');
                try {
                    const body = new URLSearchParams();
                    body.set('venue_action', 'update_capacity');
                    body.set('venue_id', venueId);
                    body.set('capacity', capacity);
                    body.set('csrf_token', csrfToken);
                    const res = await fetch(pageUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: body.toString()
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Save failed');
                    inp.dataset.lastSaved = String(capacity);
                    if (updCell) {
                        const now = new Date();
                        updCell.textContent = now.toLocaleString('en-US', {
                            month: 'short', day: 'numeric', year: 'numeric',
                            hour: 'numeric', minute: '2-digit', hour12: true
                        });
                    }
                } catch (err) {
                    inp.value = String(lastSaved || capacity);
                    alert('Could not save capacity. Please try again.');
                }
                inp.disabled = false;
                inp.classList.remove('is-saving');
            };

            inp.addEventListener('blur', saveCapacity);
            inp.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    inp.blur();
                }
            });
        });
    };

    const bindFinalizeButtons = function () {
        const modal = document.querySelector('[data-finalize-modal]');
        const confirmBtn = document.querySelector('[data-finalize-confirm]');
        const cancelBtn = document.querySelector('[data-finalize-cancel]');
        if (!modal || !confirmBtn) return;

        document.querySelectorAll('[data-finalize-schedule]').forEach(function (btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                modal.dataset.scheduleId = String(parseInt(btn.dataset.scheduleId, 10) || 0);
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
            });
        });

        if (cancelBtn && cancelBtn.dataset.bound !== '1') {
            cancelBtn.dataset.bound = '1';
            cancelBtn.addEventListener('click', function () {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            });
        }

        if (confirmBtn.dataset.bound !== '1') {
            confirmBtn.dataset.bound = '1';
            confirmBtn.addEventListener('click', async function () {
                const activeId = parseInt(modal.dataset.scheduleId || '0', 10) || 0;
                if (!activeId) return;
                confirmBtn.disabled = true;
                try {
                    const body = new URLSearchParams();
                    body.set('schedule_action', 'finalize');
                    body.set('schedule_id', String(activeId));
                    body.set('csrf_token', csrfToken);
                    const url = new URL(window.location.href);
                    url.searchParams.set('view', 'finalize-defense-schedule');
                    if (currentDefenseType === 'Final Defense') {
                        url.searchParams.set('defense_type', currentDefenseType);
                    } else {
                        url.searchParams.delete('defense_type');
                    }
                    const res = await fetch(url.toString(), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: body.toString()
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Finalize failed');
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                    await refreshRows();
                } catch (err) {
                    alert(err.message || 'Could not finalize schedule.');
                }
                confirmBtn.disabled = false;
            });
        }
    };

    let activeReview = null;
    const renderReviewDetails = function (schedule) {
        const availability = Array.isArray(schedule.availability) ? schedule.availability : [];
        const panels = Array.isArray(schedule.panels) ? schedule.panels : [];
        const availabilityHtml = availability.map(function (item) {
            return '<span class="director-review-line ' + (item.ok ? 'is-ok' : 'is-conflict') + '">' +
                '<strong>' + esc(item.label || 'Resource') + ':</strong> ' + esc(item.message || '') +
            '</span>';
        }).join('');
        const panelHtml = panels.length
            ? panels.map(function (name) { return '<span class="director-review-line is-ok">' + esc(name) + '</span>'; }).join('')
            : '<span class="director-review-line is-conflict">Panel assignment incomplete.</span>';
        return '<div class="director-review-detail">' +
            '<section class="director-review-section director-review-detail__title"><strong>' + esc(schedule.group || schedule.group_number || 'Research Group') + '</strong><br><span>' + esc(schedule.title || '') + '</span></section>' +
            '<div class="director-review-main">' +
                '<section class="director-review-section"><small>DATE</small><span class="director-review-line">' + esc(schedule.date || '') + '</span></section>' +
                '<section class="director-review-section"><small>TIME</small><span class="director-review-line">' + esc(schedule.time || '') + '</span></section>' +
                '<section class="director-review-section"><small>VENUE</small><span class="director-review-line">' + esc(schedule.venue || '') + '</span></section>' +
                '<section class="director-review-section is-wide"><small>ADVISER</small><span class="director-review-line is-ok">' + esc(schedule.adviser || '') + '</span></section>' +
                '<section class="director-review-section is-wide"><small>PANEL MEMBERS</small>' + panelHtml + '</section>' +
                '<section class="director-review-section is-wide"><small>AVAILABILITY CHECK</small>' + availabilityHtml +
                    '<div class="director-review-summary ' + (schedule.has_conflict ? 'is-conflict' : 'is-ok') + '">' +
                        (schedule.has_conflict ? 'Conflict detected' : 'No conflicts detected') +
                    '</div>' +
                '</section>' +
            '</div>' +
        '</div>';
    };

    const bindReviewButtons = function () {
        const modal = document.querySelector('[data-review-modal]');
        const body = document.querySelector('[data-review-body]');
        const chooseBtn = document.querySelector('[data-review-choose]');
        const altLink = document.querySelector('[data-review-alternative]');
        const closeBtn = document.querySelector('[data-review-close]');
        const chooseModal = document.querySelector('[data-choose-modal]');
        const chooseBody = document.querySelector('[data-choose-body]');
        const chooseCancel = document.querySelector('[data-choose-cancel]');
        const chooseConfirm = document.querySelector('[data-choose-confirm]');
        if (!modal || !body || !chooseBtn || !altLink) return;

        document.querySelectorAll('[data-review-schedule]').forEach(function (btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', async function () {
                const scheduleId = parseInt(btn.dataset.scheduleId, 10) || 0;
                activeReview = null;
                body.textContent = 'Loading...';
                chooseBtn.disabled = true;
                altLink.href = '#';
                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('ajax', 'review-schedule');
                    url.searchParams.set('schedule_id', String(scheduleId));
                    url.searchParams.set('_', Date.now().toString());
                    const res = await fetch(url.toString(), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        cache: 'no-store'
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Could not load review.');
                    activeReview = data.schedule;
                    body.innerHTML = renderReviewDetails(activeReview);
                    chooseBtn.disabled = !!activeReview.has_conflict;
                    altLink.href = activeReview.alternative_url || '#';
                } catch (err) {
                    body.textContent = err.message || 'Could not load review.';
                }
            });
        });

        if (closeBtn && closeBtn.dataset.bound !== '1') {
            closeBtn.dataset.bound = '1';
            closeBtn.addEventListener('click', function () {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            });
        }

        if (chooseBtn.dataset.bound !== '1') {
            chooseBtn.dataset.bound = '1';
            chooseBtn.addEventListener('click', function () {
                if (!activeReview || activeReview.has_conflict || !chooseModal) return;
                if (chooseBody) {
                    chooseBody.innerHTML = '<strong>' + esc(activeReview.group || activeReview.group_number || 'Research Group') + '</strong><br>' +
                        esc(activeReview.date || '') + '<br>' +
                        esc(activeReview.time || '') + '<br>' +
                        esc(activeReview.venue || '') +
                        '<p style="margin:.65rem 0 0;">This schedule will be selected for final review. It will not become official until it is finalized.</p>';
                }
                chooseModal.classList.add('is-open');
                chooseModal.setAttribute('aria-hidden', 'false');
            });
        }

        if (chooseCancel && chooseCancel.dataset.bound !== '1') {
            chooseCancel.dataset.bound = '1';
            chooseCancel.addEventListener('click', function () {
                chooseModal.classList.remove('is-open');
                chooseModal.setAttribute('aria-hidden', 'true');
            });
        }

        if (chooseConfirm && chooseConfirm.dataset.bound !== '1') {
            chooseConfirm.dataset.bound = '1';
            chooseConfirm.addEventListener('click', async function () {
                if (!activeReview) return;
                chooseConfirm.disabled = true;
                try {
                    const form = new URLSearchParams();
                    form.set('schedule_action', 'choose_schedule');
                    form.set('schedule_id', String(activeReview.id || 0));
                    form.set('csrf_token', csrfToken);
                    const url = new URL(window.location.href);
                    url.searchParams.set('view', 'proposed-schedules');
                    if (currentDefenseType === 'Final Defense') {
                        url.searchParams.set('defense_type', currentDefenseType);
                    } else {
                        url.searchParams.delete('defense_type');
                    }
                    const res = await fetch(url.toString(), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json'
                        },
                        credentials: 'same-origin',
                        body: form.toString()
                    });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message || 'Could not choose schedule.');
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        const redirectUrl = new URL(window.location.href);
                        redirectUrl.searchParams.set('view', 'finalize-defense-schedule');
                        redirectUrl.searchParams.set('schedule_id', String(activeReview.id || 0));
                        if (currentDefenseType === 'Final Defense') {
                            redirectUrl.searchParams.set('defense_type', currentDefenseType);
                        } else {
                            redirectUrl.searchParams.delete('defense_type');
                        }
                        window.location.href = redirectUrl.toString();
                    }
                } catch (err) {
                    alert(err.message || 'Could not choose schedule.');
                    chooseConfirm.disabled = false;
                }
            });
        }
    };

    const applyFilters = function () {
        const term     = search ? search.value.trim().toLowerCase() : '';
        const selected = status ? status.value : 'all';
        let visibleCount = 0;

        rows.forEach(function (row) {
            const matchesTerm = !term || row.textContent.toLowerCase().includes(term);
            const matchesStatus = selected === 'all' || row.dataset.status === selected;
            const show = matchesTerm && matchesStatus;
            row.hidden = !show;
            if (show) visibleCount++;
        });

        if (empty) {
            empty.hidden = visibleCount > 0;
            empty.textContent = rows.length ? 'No records match the search or filter.' : 'No records found.';
        }
    };

    const refreshRows = async function () {
        if (refreshing) return;
        refreshing = true;
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax', 'director-schedules');
            url.searchParams.set('_', Date.now().toString());
            const res = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error('Sync failed');
            const data = await res.json();
            if (!data.ok) throw new Error('Sync failed');
            renderRows(data.rows || []);
            renderStats(data.stats || {});
            if (sync) sync.textContent = 'Synced ' + (data.synced_at || '');
        } catch (error) {
        } finally {
            refreshing = false;
        }
    };

    if (search) search.addEventListener('input', applyFilters);
    if (status) status.addEventListener('change', applyFilters);

    // Add Venue toggle
    const addVenueBtn = document.querySelector('[data-director-add-venue]');
    const venueForm = document.querySelector('[data-director-venue-form]');
    const cancelVenueBtn = document.querySelector('[data-director-cancel-venue]');

    if (addVenueBtn && venueForm) {
        addVenueBtn.addEventListener('click', function () {
            const isVisible = venueForm.style.display !== 'none';
            if (isVisible) {
                venueForm.style.display = 'none';
                addVenueBtn.innerHTML = '<?= smsIcon('plus', ['aria-hidden' => 'true']) ?> Add Venue';
                addVenueBtn.classList.remove('is-cancel');
            } else {
                venueForm.style.display = '';
                addVenueBtn.innerHTML = '<?= smsIcon('chevron-up', ['aria-hidden' => 'true']) ?> Hide Form';
                addVenueBtn.classList.add('is-cancel');
                venueForm.querySelector('input[name="venue_name"]')?.focus();
            }
        });
    }

    if (cancelVenueBtn && venueForm && addVenueBtn) {
        cancelVenueBtn.addEventListener('click', function () {
            venueForm.style.display = 'none';
            addVenueBtn.innerHTML = '<?= smsIcon('plus', ['aria-hidden' => 'true']) ?> Add Venue';
            addVenueBtn.classList.remove('is-cancel');
        });
    }

    // Auto-open form if there's a message (after submit attempt)
    <?php if ($view === 'venues' && $venueMessage): ?>
    if (venueForm && addVenueBtn) {
        venueForm.style.display = '';
        addVenueBtn.innerHTML = '<?= smsIcon('chevron-up', ['aria-hidden' => 'true']) ?> Hide Form';
        addVenueBtn.classList.add('is-cancel');
    }
    <?php endif; ?>

    const bindAiScheduler = function () {
        const schedulerForm = document.getElementById('directorSchedulerForm');
        const generateBtn = document.getElementById('aiGenerateSlotsBtn');
        const summaryEl = document.getElementById('aiScheduleSummary');
        const hintsEl = document.getElementById('aiSlotHints');
        const periodStart = document.getElementById('aiPeriodStart');
        const periodEnd = document.getElementById('aiPeriodEnd');
        const expectedAttendees = document.getElementById('aiExpectedAttendees');
        if (!schedulerForm || !generateBtn || !summaryEl) return;

        const normalizeTime = function (value) {
            const raw = String(value || '').trim();
            if (!raw) return '';
            const match = raw.match(/^(\d{1,2}):(\d{2})/);
            if (!match) return raw;
            return String(match[1]).padStart(2, '0') + ':' + match[2];
        };

        generateBtn.addEventListener('click', async function () {
            const groupId = parseInt(schedulerForm.getAttribute('data-group-id') || '0', 10);
            if (groupId < 1) {
                summaryEl.style.display = '';
                summaryEl.classList.add('is-error');
                summaryEl.textContent = 'Select a defense-ready research group first.';
                return;
            }
            if (periodStart && periodEnd && periodStart.value && periodEnd.value && periodEnd.value < periodStart.value) {
                summaryEl.style.display = '';
                summaryEl.classList.add('is-error');
                summaryEl.textContent = 'Period End must be on or after Period Start.';
                return;
            }

            const originalLabel = generateBtn.innerHTML;
            generateBtn.disabled = true;
            generateBtn.innerHTML = 'Scanning free schedules…';
            summaryEl.style.display = '';
            summaryEl.classList.remove('is-error');
            summaryEl.textContent = 'Checking live venue, adviser, and panel availability…';
            if (hintsEl) hintsEl.innerHTML = '';

            const dates = schedulerForm.querySelectorAll('.js-defense-date');
            const venues = schedulerForm.querySelectorAll('.js-venue-id');
            const starts = schedulerForm.querySelectorAll('.js-start-time');
            const ends = schedulerForm.querySelectorAll('.js-end-time');
            dates.forEach(function (el) { el.value = ''; });
            venues.forEach(function (el) { el.value = ''; });
            starts.forEach(function (el) { el.value = ''; });
            ends.forEach(function (el) { el.value = ''; });

            const body = new URLSearchParams();
            body.set('schedule_action', 'ai_generate_slots');
            body.set('research_group_id', String(groupId));
            body.set('defense_type', schedulerForm.getAttribute('data-defense-type') || '');
            body.set('period_start', periodStart ? periodStart.value : '');
            body.set('period_end', periodEnd ? periodEnd.value : '');
            body.set('expected_attendees', expectedAttendees ? expectedAttendees.value : '15');
            body.set('csrf_token', csrfToken);

            try {
                const res = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'Cache-Control': 'no-store'
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    body: body.toString()
                });
                const text = await res.text();
                let data = null;
                try { data = JSON.parse(text); } catch (e) { data = null; }
                if (!data || !data.ok) {
                    throw new Error((data && data.message) || 'AI scheduling failed.');
                }

                (data.slots || []).forEach(function (slot, index) {
                    if (dates[index]) dates[index].value = slot.date || '';
                    if (venues[index]) venues[index].value = String(slot.venue_id || '');
                    if (starts[index]) starts[index].value = normalizeTime(slot.start_time);
                    if (ends[index]) ends[index].value = normalizeTime(slot.end_time);
                });

                summaryEl.style.display = '';
                summaryEl.textContent = data.summary || 'AI generated varied free slots. Review and save when ready.';

                if (hintsEl && Array.isArray(data.slots)) {
                    hintsEl.innerHTML = data.slots.map(function (slot, index) {
                        return '<div class="director-ai-slot-hint"><strong>Slot ' + (index + 1) + ':</strong> '
                            + esc(slot.date) + ' · ' + esc(normalizeTime(slot.start_time)) + '–' + esc(normalizeTime(slot.end_time))
                            + ' · ' + esc(slot.venue_name) + ' (' + esc(slot.capacity) + ' cap) — '
                            + esc(slot.reason || '') + '</div>';
                    }).join('');
                }
            } catch (err) {
                summaryEl.style.display = '';
                summaryEl.classList.add('is-error');
                summaryEl.textContent = err.message || 'Could not generate slots. Try a wider period.';
            }

            generateBtn.disabled = false;
            generateBtn.innerHTML = originalLabel;
        });
    };

    applyFilters();
    if (isVenueView) {
        bindStatusSelects();
        bindCapacityInputs();
    }
    if (isManualOptimizerView) bindAiScheduler();
    if (isFinalizeView) bindFinalizeButtons();
    if (isProposedView) bindReviewButtons();
    refreshRows();
    timer = window.setInterval(refreshRows, 5000);
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (timer) window.clearInterval(timer);
            timer = null;
            return;
        }
        if (timer) window.clearInterval(timer);
        refreshRows();
        timer = window.setInterval(refreshRows, 5000);
    });
});
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
