<?php
declare(strict_types=1);

require_once ROOT_PATH . '/modules/crad/config/config.php';

/**
 * Return authorized CRAD calendar projections for an inclusive date range.
 *
 * @return list<array<string, mixed>>
 */
function smsCalendarEvents(DateTimeImmutable $start, DateTimeImmutable $end): array
{
    $pdo = cradDb();
    if (!$pdo instanceof PDO) {
        throw new RuntimeException('CRAD database connection unavailable.');
    }

    if ($end < $start) {
        throw new InvalidArgumentException('Calendar end date must not precede its start date.');
    }

    $userId = (int) getCurrentUserId();
    $role = function_exists('smsNormalizeRoleKey')
        ? smsNormalizeRoleKey((string) getCurrentUserRoleKey())
        : (string) getCurrentUserRoleKey();
    $canViewAll = smsCanManageDefenseScheduling($role) || $role === 'crad_officer';
    $params = [
        ':range_start' => $start->format('Y-m-d 00:00:00'),
        ':range_end' => $end->modify('+1 day')->format('Y-m-d 00:00:00'),
    ];

    $defenseVisibility = '';
    $milestoneVisibility = '';
    if (!$canViewAll) {
        if ($role === 'student') {
            $studentId = strtoupper(trim((string) ($_SESSION['student_id'] ?? '')));
            $email = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
            if ($studentId === '' && $email === '') {
                return [];
            }
            $defenseVisibility = "AND EXISTS (
                SELECT 1
                  FROM `crad_research_group_members` member
                 WHERE member.research_group_id = rds.research_group_id
                   AND ((:defense_student_id <> '' AND UPPER(member.student_id) = :defense_student_id_match)
                     OR (:defense_email <> '' AND LOWER(member.email) = :defense_email_match))
            )";
            $milestoneVisibility = "AND EXISTS (
                SELECT 1
                  FROM `crad_research_group_members` member
                 WHERE member.research_group_id = rg.id
                   AND ((:milestone_student_id <> '' AND UPPER(member.student_id) = :milestone_student_id_match)
                     OR (:milestone_email <> '' AND LOWER(member.email) = :milestone_email_match))
            )";
            $params += [
                ':defense_student_id' => $studentId,
                ':defense_student_id_match' => $studentId,
                ':defense_email' => $email,
                ':defense_email_match' => $email,
                ':milestone_student_id' => $studentId,
                ':milestone_student_id_match' => $studentId,
                ':milestone_email' => $email,
                ':milestone_email_match' => $email,
            ];
        } elseif (in_array($role, ['panel', 'adviser', 'research_coordinator', 'department_head'], true) && $userId > 0) {
            if ($role === 'panel') {
                $defenseVisibility = "AND EXISTS (
                    SELECT 1 FROM `crad_research_panel_assignments` assignment
                     WHERE assignment.research_group_id = rds.research_group_id
                       AND assignment.panel_user_id = :defense_user_id
                       AND assignment.assignment_status IN ('Assigned', 'Confirmed')
                )";
                $milestoneVisibility = "AND EXISTS (
                    SELECT 1 FROM `crad_research_panel_assignments` assignment
                     WHERE assignment.research_group_id = rg.id
                       AND assignment.panel_user_id = :milestone_user_id
                       AND assignment.assignment_status IN ('Assigned', 'Confirmed')
                )";
            } elseif ($role === 'adviser') {
                $defenseVisibility = "AND EXISTS (
                    SELECT 1 FROM `crad_research_adviser_assignments` assignment
                     WHERE (assignment.research_group_id = rds.research_group_id
                         OR (assignment.research_group_id IS NULL AND assignment.group_number = rds.group_number))
                       AND assignment.adviser_user_id = :defense_user_id
                       AND assignment.assignment_status IN ('Assigned', 'Confirmed')
                )";
                $milestoneVisibility = "AND EXISTS (
                    SELECT 1 FROM `crad_research_adviser_assignments` assignment
                     WHERE (assignment.research_group_id = rg.id
                         OR (assignment.research_group_id IS NULL AND assignment.group_number = rg.group_number))
                       AND assignment.adviser_user_id = :milestone_user_id
                       AND assignment.assignment_status IN ('Assigned', 'Confirmed')
                )";
            } else {
                $defenseVisibility = "AND EXISTS (
                    SELECT 1 FROM `crad_research_coordinator_assignments` assignment
                     WHERE (assignment.research_group_id = rds.research_group_id
                         OR (assignment.research_group_id IS NULL AND assignment.group_number = rds.group_number))
                       AND assignment.coordinator_user_id = :defense_user_id
                       AND assignment.status = 'Active'
                )";
                $milestoneVisibility = "AND EXISTS (
                    SELECT 1 FROM `crad_research_coordinator_assignments` assignment
                     WHERE (assignment.research_group_id = rg.id
                         OR (assignment.research_group_id IS NULL AND assignment.group_number = rg.group_number))
                       AND assignment.coordinator_user_id = :milestone_user_id
                       AND assignment.status = 'Active'
                )";
            }
            $params[':defense_user_id'] = $userId;
            $params[':milestone_user_id'] = $userId;
        } else {
            return [];
        }
    }

    $events = [];
    try {
        $stmt = $pdo->prepare(
            "SELECT rds.id, rds.research_group_id, rds.defense_type, rds.defense_datetime,
                    rds.defense_end_datetime, rds.venue, rds.group_number, rds.research_title,
                    rds.status
               FROM `crad_research_defense_schedules` rds
              WHERE rds.defense_datetime >= :range_start
                AND rds.defense_datetime < :range_end
                AND LOWER(rds.status) IN ('scheduled', 'rescheduled', 'cancelled', 'canceled', 'completed', 'finalized', 'final')
                {$defenseVisibility}
              ORDER BY rds.defense_datetime ASC, rds.id ASC"
        );
        $defenseParams = [
            ':range_start' => $params[':range_start'],
            ':range_end' => $params[':range_end'],
        ];
        foreach ($params as $key => $value) {
            if (str_starts_with($key, ':defense_')) {
                $defenseParams[$key] = $value;
            }
        }
        $stmt->execute($defenseParams);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $events[] = smsCalendarNormalizeEvent([
                'source_type' => 'defense',
                'source_id' => (string) $row['id'],
                'title' => trim((string) ($row['defense_type'] ?? '')) ?: 'Defense',
                'description' => 'Official CRAD defense schedule.',
                'event_type' => 'Defense',
                'starts_at' => (string) $row['defense_datetime'],
                'ends_at' => (string) ($row['defense_end_datetime'] ?? ''),
                'all_day' => false,
                'timezone' => 'Asia/Manila',
                'venue' => trim((string) ($row['venue'] ?? '')) ?: 'Venue to be confirmed',
                'status' => trim((string) ($row['status'] ?? 'Scheduled')),
                'research_group_id' => (int) ($row['research_group_id'] ?? 0),
                'process_reference' => trim((string) ($row['group_number'] ?? '')),
                'research_group' => trim((string) ($row['research_title'] ?? '')) ?: ('Group ' . (string) ($row['group_number'] ?? '')),
                'audience' => 'Research students & panel',
                'visibility_scope' => $canViewAll ? 'crad' : 'assigned_group',
                'source_url' => '',
            ]);
        }

        $milestoneStmt = $pdo->prepare(
            "SELECT rm.id, rm.milestone_name, rm.description, rm.target_date, rm.status,
                    rp.id AS plan_id, rp.target_completion_date, rp.status AS plan_status,
                    rg.id AS research_group_id, rg.group_number, rg.research_title
               FROM `crad_research_milestones` rm
               JOIN `crad_research_plans` rp ON rp.id = rm.research_plan_id
               JOIN `crad_research_groups` rg ON rg.id = rp.research_group_id
              WHERE rm.target_date >= :milestone_start
                AND rm.target_date < :milestone_end
                AND rp.status NOT IN ('Cancelled', 'On Hold')
                {$milestoneVisibility}
              ORDER BY rm.target_date ASC, rm.id ASC"
        );
        $milestoneParams = [
            ':milestone_start' => $start->format('Y-m-d'),
            ':milestone_end' => $end->modify('+1 day')->format('Y-m-d'),
        ];
        foreach ($params as $key => $value) {
            if (str_starts_with($key, ':milestone_') && !in_array($key, [':milestone_start', ':milestone_end'], true)) {
                $milestoneParams[$key] = $value;
            }
        }
        $milestoneStmt->execute($milestoneParams);
        $milestones = $milestoneStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $plansWithMilestoneDates = [];
        foreach ($milestones as $row) {
            $planId = (int) ($row['plan_id'] ?? 0);
            $plansWithMilestoneDates[$planId][(string) $row['target_date']] = true;
            $events[] = smsCalendarNormalizeEvent([
                'source_type' => 'research_milestone',
                'source_id' => (string) $row['id'],
                'title' => trim((string) ($row['milestone_name'] ?? 'Research milestone')),
                'description' => trim((string) ($row['description'] ?? '')),
                'event_type' => 'Deadline',
                'starts_at' => (string) $row['target_date'],
                'ends_at' => '',
                'all_day' => true,
                'timezone' => 'Asia/Manila',
                'venue' => 'Research workspace',
                'status' => trim((string) ($row['status'] ?? '')),
                'research_group_id' => (int) ($row['research_group_id'] ?? 0),
                'process_reference' => 'Plan ' . $planId,
                'research_group' => 'Group ' . (string) ($row['group_number'] ?? '') . ' · ' . (string) ($row['research_title'] ?? ''),
                'audience' => 'Research students',
                'visibility_scope' => $canViewAll ? 'crad' : 'assigned_group',
                'source_url' => '',
            ]);
        }

        $planStmt = $pdo->prepare(
            "SELECT rp.id, rp.target_completion_date, rp.status,
                    rg.id AS research_group_id, rg.group_number, rg.research_title
               FROM `crad_research_plans` rp
               JOIN `crad_research_groups` rg ON rg.id = rp.research_group_id
              WHERE rp.target_completion_date >= :plan_start
                AND rp.target_completion_date < :plan_end
                AND rp.status = 'Active'
                {$milestoneVisibility}
              ORDER BY rp.target_completion_date ASC, rp.id ASC"
        );
        $planParams = [
            ':plan_start' => $start->format('Y-m-d'),
            ':plan_end' => $end->modify('+1 day')->format('Y-m-d'),
        ];
        foreach ($params as $key => $value) {
            if (str_starts_with($key, ':milestone_') && !in_array($key, [':milestone_start', ':milestone_end'], true)) {
                $planParams[$key] = $value;
            }
        }
        $planStmt->execute($planParams);
        foreach ($planStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $planId = (int) ($row['id'] ?? 0);
            $targetDate = (string) ($row['target_completion_date'] ?? '');
            if (isset($plansWithMilestoneDates[$planId][$targetDate])) {
                continue;
            }
            $events[] = smsCalendarNormalizeEvent([
                'source_type' => 'research_plan',
                'source_id' => (string) $planId,
                'title' => 'Research plan completion',
                'description' => 'Target completion date for the active research plan.',
                'event_type' => 'Deadline',
                'starts_at' => $targetDate,
                'ends_at' => '',
                'all_day' => true,
                'timezone' => 'Asia/Manila',
                'venue' => 'Research workspace',
                'status' => 'Active',
                'research_group_id' => (int) ($row['research_group_id'] ?? 0),
                'process_reference' => 'Plan ' . $planId,
                'research_group' => 'Group ' . (string) ($row['group_number'] ?? '') . ' · ' . (string) ($row['research_title'] ?? ''),
                'audience' => 'Research students',
                'visibility_scope' => $canViewAll ? 'crad' : 'assigned_group',
                'source_url' => '',
            ]);
        }
    } catch (Throwable $e) {
        error_log('Calendar event projection failed: ' . $e->getMessage());
        throw new RuntimeException('Calendar events could not be loaded.', 0, $e);
    }

    usort($events, static fn(array $left, array $right): int => strcmp($left['starts_at'], $right['starts_at']));
    return $events;
}

/**
 * Return only actionable upcoming events from the same authorized projection.
 *
 * @param list<string> $types
 * @return list<array<string, mixed>>
 */
function smsCalendarUpcomingEvents(DateTimeImmutable $start, DateTimeImmutable $end, array $types = []): array
{
    $timezone = new DateTimeZone('Asia/Manila');
    $now = new DateTimeImmutable('now', $timezone);
    return array_values(array_filter(
        smsCalendarEvents($start, $end),
        static function (array $event) use ($types, $now, $timezone): bool {
            $status = strtolower(trim((string) ($event['status'] ?? '')));
            if (in_array($status, ['cancelled', 'canceled', 'completed', 'final', 'approved'], true)) {
                return false;
            }
            if ($types && !in_array((string) ($event['type'] ?? ''), $types, true)) {
                return false;
            }
            if (!empty($event['all_day'])) {
                return true;
            }
            try {
                return new DateTimeImmutable((string) $event['starts_at'], $timezone) >= $now;
            } catch (Throwable $e) {
                error_log('Calendar event has an invalid projected start time: ' . $e->getMessage());
                return false;
            }
        }
    ));
}

/**
 * @param array<string, mixed> $event
 * @return array<string, mixed>
 */
function smsCalendarNormalizeEvent(array $event): array
{
    $timezone = new DateTimeZone('Asia/Manila');
    $rawStart = (string) ($event['starts_at'] ?? '');
    $allDay = (bool) ($event['all_day'] ?? false);
    $start = DateTimeImmutable::createFromFormat($allDay ? '!Y-m-d' : '!Y-m-d H:i:s', $rawStart, $timezone);
    $startFormat = $allDay ? 'Y-m-d' : 'Y-m-d H:i:s';
    if (!$start || $start->format($startFormat) !== $rawStart) {
        throw new UnexpectedValueException('Calendar event has an invalid start date.');
    }
    $endRaw = (string) ($event['ends_at'] ?? '');
    $end = $endRaw !== ''
        ? DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $endRaw, $timezone)
        : null;
    if ($endRaw !== '' && (!$end || $end->format('Y-m-d H:i:s') !== $endRaw || $end < $start)) {
        throw new UnexpectedValueException('Calendar event has an invalid end date.');
    }
    $startIso = $start->format($allDay ? 'Y-m-d' : 'Y-m-d\TH:i:sP');
    $endIso = $end ? $end->format('Y-m-d\TH:i:sP') : '';
    $date = $start->format('Y-m-d');

    $normalized = $event;
    $normalized['id'] = (string) $event['source_type'] . '-' . (string) $event['source_id'];
    $normalized['date'] = $date;
    $normalized['start_time'] = $allDay ? 'All day' : $start->format('g:i A');
    $normalized['end_time'] = $end ? $end->format('g:i A') : '';
    $normalized['location'] = (string) ($event['venue'] ?? '');
    $normalized['type'] = (string) $event['event_type'];
    $normalized['starts_at'] = $startIso;
    $normalized['ends_at'] = $endIso;

    return $normalized;
}
