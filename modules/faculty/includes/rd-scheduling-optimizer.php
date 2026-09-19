<?php
/**
 * Research Director — AI Scheduling Optimizer
 * Picks varied, conflict-free defense slots from live venue / adviser / panel load.
 */
declare(strict_types=1);

/**
 * @return array{ok: bool, slots?: list<array<string, mixed>>, summary?: string, message?: string, meta?: array<string, mixed>}
 */
function rdScheduleGenerateOptimizedSlots(
    PDO $pdo,
    int $groupId,
    string $defenseType,
    string $periodStart,
    string $periodEnd,
    int $expectedAttendees = 15,
    int $slotCount = 3,
    int $durationMinutes = 120
): array {
    $expectedAttendees = max(1, $expectedAttendees);
    $slotCount = max(2, min(3, $slotCount));
    $durationMinutes = max(60, min(180, $durationMinutes));

    $startTs = strtotime($periodStart);
    $endTs = strtotime($periodEnd);
    if ($startTs === false || $endTs === false || $endTs < $startTs) {
        return ['ok' => false, 'message' => 'Select a valid defense period (start and end dates).'];
    }

    $today = strtotime(date('Y-m-d'));
    if ($startTs < $today) {
        $startTs = $today;
    }
    if ($endTs < $startTs) {
        return ['ok' => false, 'message' => 'Defense period end must be on or after the start date.'];
    }

    $maxDays = 120;
    if ((int) floor(($endTs - $startTs) / 86400) > $maxDays) {
        return ['ok' => false, 'message' => 'Defense period cannot exceed ' . $maxDays . ' days.'];
    }

    if (!function_exists('rdScheduleReadyGroup') || !rdScheduleReadyGroup($pdo, $groupId, $defenseType)) {
        return ['ok' => false, 'message' => 'Research group is not ready for scheduling.'];
    }

    $gate = rdScheduleAiReadinessGate($pdo, $groupId, $defenseType);
    if ($gate !== []) {
        return ['ok' => false, 'message' => implode(' ', $gate)];
    }

    $venues = $pdo->query(
        "SELECT id, venue_name, capacity, venue_type, status
           FROM research_venues
          WHERE LOWER(status) = 'available'
            AND capacity >= " . (int) $expectedAttendees . "
          ORDER BY capacity ASC, venue_name ASC"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($venues === []) {
        return [
            'ok' => false,
            'message' => 'No available venues meet the expected attendee count (' . $expectedAttendees . '). Lower attendees or add venues.',
        ];
    }

    $busyBlocks = rdScheduleAiBusyBlocks(
        $pdo,
        $groupId,
        date('Y-m-d', $startTs),
        date('Y-m-d', $endTs),
        $defenseType
    );

    // Varied start hours so options are not all 09:00.
    $startHours = [8, 9, 10, 11, 13, 14, 15];
    $candidates = [];
    $evaluated = 0;
    $hourLoadCache = [];
    $dayVenueLoadCache = [];

    for ($dayTs = $startTs; $dayTs <= $endTs; $dayTs += 86400) {
        $weekday = (int) date('N', $dayTs);
        if ($weekday >= 6) {
            continue;
        }

        $date = date('Y-m-d', $dayTs);
        foreach ($startHours as $hour) {
            $startTime = sprintf('%02d:00', $hour);
            $slotStartTs = strtotime($date . ' ' . $startTime . ':00');
            if ($slotStartTs === false) {
                continue;
            }
            $slotEndTs = $slotStartTs + ($durationMinutes * 60);
            $endHour = (int) date('H', $slotEndTs);
            $endMin = (int) date('i', $slotEndTs);
            if ($endHour > 17 || ($endHour === 17 && $endMin > 0)) {
                continue;
            }
            $endTime = date('H:i', $slotEndTs);
            $startAt = date('Y-m-d H:i:s', $slotStartTs);
            $endAt = date('Y-m-d H:i:s', $slotEndTs);

            $hourKey = $date . '|' . $startTime;
            if (!isset($hourLoadCache[$hourKey])) {
                $hourLoadCache[$hourKey] = rdScheduleAiHourLoad($busyBlocks, $startAt, $endAt);
            }
            $hourLoad = $hourLoadCache[$hourKey];

            foreach ($venues as $venue) {
                $evaluated++;
                $venueId = (int) ($venue['id'] ?? 0);
                $capacity = (int) ($venue['capacity'] ?? 0);

                if (rdScheduleAiHasConflict($busyBlocks, $groupId, $venueId, $startAt, $endAt)) {
                    continue;
                }

                $dayVenueKey = $date . '|' . $venueId;
                if (!isset($dayVenueLoadCache[$dayVenueKey])) {
                    $dayVenueLoadCache[$dayVenueKey] = rdScheduleAiVenueDayLoad($busyBlocks, $venueId, $date);
                }
                $dayLoad = $dayVenueLoadCache[$dayVenueKey];
                $headroom = $capacity - $expectedAttendees;

                $capacityScore = match (true) {
                    $headroom < 5 => 40,
                    $headroom <= 15 => 75,
                    $headroom <= 40 => 100,
                    $headroom <= 80 => 85,
                    default => 70,
                };
                $loadScore = max(0, 100 - ($dayLoad * 28) - ($hourLoad * 18));
                $timeScore = match ($hour) {
                    9, 10 => 95,
                    8, 13, 14 => 90,
                    11 => 82,
                    default => 78,
                };
                // Prefer less crowded hour blocks system-wide (maluwag).
                $freeScore = max(0, 100 - ($hourLoad * 35));

                $score = (int) round(
                    ($capacityScore * 0.30)
                    + ($loadScore * 0.25)
                    + ($freeScore * 0.30)
                    + ($timeScore * 0.15)
                );

                $candidates[] = [
                    'date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'venue_id' => $venueId,
                    'venue_name' => (string) ($venue['venue_name'] ?? 'Venue'),
                    'capacity' => $capacity,
                    'headroom' => $headroom,
                    'day_load' => $dayLoad,
                    'hour_load' => $hourLoad,
                    'score' => $score,
                    'reason' => rdScheduleSlotReason($capacity, $expectedAttendees, $dayLoad, $hour, $hourLoad),
                ];
            }
        }
    }

    if ($candidates === []) {
        return [
            'ok' => false,
            'message' => 'No free slots found in this period for this adviser, panel, and venue set. Try a wider date range or fewer expected attendees.',
            'meta' => ['candidates_evaluated' => $evaluated],
        ];
    }

    usort(
        $candidates,
        static fn(array $a, array $b): int => ($b['score'] <=> $a['score']) ?: strcmp((string) $a['start_at'], (string) $b['start_at'])
    );

    $picked = rdSchedulePickDiverseSlots($candidates, $slotCount);

    $venueNames = array_unique(array_map(static fn(array $s): string => (string) ($s['venue_name'] ?? ''), $picked));
    $times = array_unique(array_map(static fn(array $s): string => (string) ($s['start_time'] ?? ''), $picked));

    return [
        'ok' => true,
        'slots' => $picked,
        'summary' => 'AI scanned ' . number_format($evaluated) . ' live combinations and picked '
            . count($picked) . ' different free slots'
            . (count($venueNames) > 1 ? ' across ' . count($venueNames) . ' venues' : '')
            . (count($times) > 1 ? ' and ' . count($times) . ' start times' : '')
            . '.',
        'meta' => [
            'candidates_evaluated' => $evaluated,
            'candidates_valid' => count($candidates),
            'expected_attendees' => $expectedAttendees,
            'period_start' => date('Y-m-d', $startTs),
            'period_end' => date('Y-m-d', $endTs),
            'venues_used' => array_values($venueNames),
            'times_used' => array_values($times),
        ],
    ];
}

/**
 * @return list<string>
 */
function rdScheduleAiReadinessGate(PDO $pdo, int $groupId, string $defenseType): array
{
    $messages = [];
    $group = function_exists('rdScheduleReadyGroup') ? rdScheduleReadyGroup($pdo, $groupId, $defenseType) : null;
    if (!$group) {
        return ['Research group is not ready for scheduling.'];
    }
    $panelRows = function_exists('rdSchedulePanelRows') ? rdSchedulePanelRows($pdo, $groupId) : [];
    $uniquePanelIds = [];
    foreach ($panelRows as $panel) {
        $panelUserId = (int) ($panel['panel_user_id'] ?? 0);
        if ($panelUserId > 0) {
            $uniquePanelIds[$panelUserId] = true;
        }
    }
    $maxPanel = defined('RD_SCHEDULE_MAX_PANEL_MEMBERS') ? (int) RD_SCHEDULE_MAX_PANEL_MEMBERS : 3;
    if (count($uniquePanelIds) !== $maxPanel) {
        $messages[] = 'Exactly ' . $maxPanel . ' Panel Members are required for a ' . $defenseType . ' schedule.';
    }
    if (strcasecmp((string) ($group['adviser_availability'] ?? 'Pending'), 'Available') !== 0) {
        $messages[] = 'Adviser must be Available before generating slots.';
    }
    foreach ($panelRows as $panel) {
        if (strcasecmp((string) ($panel['availability_status'] ?? 'Pending'), 'Available') !== 0) {
            $messages[] = (string) ($panel['panel_name'] ?? 'Panel member') . ' must be Available.';
        }
    }

    return $messages;
}

/**
 * Load live busy windows once for the period (venues + adviser + panel + other groups).
 * Own-group proposed/selected rows are ignored so Generate can re-suggest better options.
 *
 * @return list<array<string, mixed>>
 */
function rdScheduleAiBusyBlocks(PDO $pdo, int $groupId, string $periodStart, string $periodEnd, string $defenseType): array
{
    $join = function_exists('rdOfficialScheduleJoinSql') ? rdOfficialScheduleJoinSql() : '';
    $panelActive = function_exists('rdPanelActiveAssignmentSql')
        ? rdPanelActiveAssignmentSql('pa_old')
        : "pa_old.assignment_status IN ('Assigned','Confirmed')";
    $panelActiveNew = function_exists('rdPanelActiveAssignmentSql')
        ? rdPanelActiveAssignmentSql('pa_new')
        : "pa_new.assignment_status IN ('Assigned','Confirmed')";

    $sql = "SELECT rds.id,
                   rds.research_group_id,
                   rds.venue_id,
                   rds.defense_datetime AS start_at,
                   COALESCE(rds.defense_end_datetime, DATE_ADD(rds.defense_datetime, INTERVAL 2 HOUR)) AS end_at,
                   CASE
                     WHEN rds.research_group_id = :gid_flag THEN 1 ELSE 0
                   END AS is_own_group,
                   CASE
                     WHEN EXISTS (
                        SELECT 1 FROM research_adviser_assignments aa_new
                        JOIN research_adviser_assignments aa_old
                          ON aa_old.adviser_user_id = aa_new.adviser_user_id
                         AND aa_old.adviser_user_id IS NOT NULL
                         AND aa_old.research_group_id = rds.research_group_id
                         AND aa_old.assignment_status IN ('Assigned', 'Confirmed')
                        WHERE aa_new.research_group_id = :gid_adv
                          AND aa_new.assignment_status IN ('Assigned', 'Confirmed')
                     ) THEN 1 ELSE 0
                   END AS shares_adviser,
                   CASE
                     WHEN EXISTS (
                        SELECT 1 FROM research_panel_assignments pa_new
                        JOIN research_panel_assignments pa_old
                          ON pa_old.panel_user_id = pa_new.panel_user_id
                         AND pa_old.research_group_id = rds.research_group_id
                         AND {$panelActive}
                        WHERE pa_new.research_group_id = :gid_panel
                          AND {$panelActiveNew}
                     ) THEN 1 ELSE 0
                   END AS shares_panel
            FROM research_defense_schedules rds
            {$join}
            WHERE rds.defense_datetime IS NOT NULL
              AND LOWER(rds.status) IN ('proposed', 'selected', 'scheduled', 'finalized', 'final')
              AND DATE(rds.defense_datetime) BETWEEN :pstart AND :pend
              AND rds.research_group_id <> :gid_skip";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':gid_flag' => $groupId,
        ':gid_adv' => $groupId,
        ':gid_panel' => $groupId,
        ':pstart' => $periodStart,
        ':pend' => $periodEnd,
        ':gid_skip' => $groupId,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param list<array<string, mixed>> $blocks
 */
function rdScheduleAiHasConflict(array $blocks, int $groupId, int $venueId, string $startAt, string $endAt): bool
{
    $start = strtotime($startAt);
    $end = strtotime($endAt);
    if ($start === false || $end === false) {
        return true;
    }
    foreach ($blocks as $block) {
        $bStart = strtotime((string) ($block['start_at'] ?? ''));
        $bEnd = strtotime((string) ($block['end_at'] ?? ''));
        if ($bStart === false || $bEnd === false) {
            continue;
        }
        if (!($bStart < $end && $bEnd > $start)) {
            continue;
        }
        $blockVenue = (int) ($block['venue_id'] ?? 0);
        if ($blockVenue > 0 && $blockVenue === $venueId) {
            return true;
        }
        if (!empty($block['shares_adviser']) || !empty($block['shares_panel'])) {
            return true;
        }
    }

    return false;
}

/**
 * @param list<array<string, mixed>> $blocks
 */
function rdScheduleAiHourLoad(array $blocks, string $startAt, string $endAt): int
{
    $start = strtotime($startAt);
    $end = strtotime($endAt);
    if ($start === false || $end === false) {
        return 0;
    }
    $count = 0;
    foreach ($blocks as $block) {
        $bStart = strtotime((string) ($block['start_at'] ?? ''));
        $bEnd = strtotime((string) ($block['end_at'] ?? ''));
        if ($bStart === false || $bEnd === false) {
            continue;
        }
        if ($bStart < $end && $bEnd > $start) {
            $count++;
        }
    }

    return $count;
}

/**
 * @param list<array<string, mixed>> $blocks
 */
function rdScheduleAiVenueDayLoad(array $blocks, int $venueId, string $date): int
{
    $count = 0;
    foreach ($blocks as $block) {
        if ((int) ($block['venue_id'] ?? 0) !== $venueId) {
            continue;
        }
        $bDate = substr((string) ($block['start_at'] ?? ''), 0, 10);
        if ($bDate === $date) {
            $count++;
        }
    }

    return $count;
}

function rdScheduleVenueDayLoad(PDO $pdo, int $venueId, string $date): int
{
    $join = function_exists('rdOfficialScheduleJoinSql') ? rdOfficialScheduleJoinSql() : '';
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
           FROM research_defense_schedules rds
           {$join}
          WHERE rds.venue_id = ?
            AND DATE(rds.defense_datetime) = ?
            AND LOWER(rds.status) IN ('proposed', 'selected', 'scheduled', 'finalized', 'final')"
    );
    $stmt->execute([$venueId, $date]);

    return (int) $stmt->fetchColumn();
}

function rdScheduleSlotReason(
    int $capacity,
    int $expectedAttendees,
    int $dayLoad,
    int $hour,
    int $hourLoad = 0
): string {
    $headroom = $capacity - $expectedAttendees;
    $parts = [];
    $parts[] = $headroom >= 10
        ? 'Comfortable venue capacity (' . $capacity . ' seats, ' . $headroom . ' spare)'
        : 'Venue fits expected attendees (' . $capacity . ' seats)';
    $parts[] = $dayLoad === 0 ? 'Venue free that day' : 'Light venue use that day';
    $parts[] = $hourLoad === 0 ? 'Open time block system-wide' : 'Fewer defenses in this hour';
    $parts[] = $hour < 12 ? 'Morning slot' : 'Afternoon slot';

    return implode(' · ', $parts);
}

/**
 * Greedy pick that forces different dates, times, and venues when possible.
 *
 * @param list<array<string, mixed>> $candidates
 * @return list<array<string, mixed>>
 */
function rdSchedulePickDiverseSlots(array $candidates, int $count): array
{
    $picked = [];
    $pool = $candidates;

    while (count($picked) < $count && $pool !== []) {
        $bestIndex = null;
        $bestCombined = -INF;

        foreach ($pool as $index => $candidate) {
            $quality = (float) ($candidate['score'] ?? 0);
            $diversity = rdScheduleDiversityBonus($candidate, $picked);
            if ($diversity < 0) {
                continue;
            }
            $combined = $picked === []
                ? $quality
                : ($quality * 0.35) + ($diversity * 0.65);
            if ($combined > $bestCombined) {
                $bestCombined = $combined;
                $bestIndex = $index;
            }
        }

        if ($bestIndex === null) {
            // Relax: allow closer dates if we still need slots.
            foreach ($pool as $index => $candidate) {
                $signature = ($candidate['date'] ?? '') . '|' . ($candidate['start_time'] ?? '') . '|' . ($candidate['venue_id'] ?? '');
                $dup = false;
                foreach ($picked as $existing) {
                    $existingSig = ($existing['date'] ?? '') . '|' . ($existing['start_time'] ?? '') . '|' . ($existing['venue_id'] ?? '');
                    if ($signature === $existingSig) {
                        $dup = true;
                        break;
                    }
                }
                if ($dup) {
                    continue;
                }
                $picked[] = $candidate;
                unset($pool[$index]);
                $pool = array_values($pool);
                if (count($picked) >= $count) {
                    break 2;
                }
            }
            break;
        }

        $picked[] = $pool[$bestIndex];
        unset($pool[$bestIndex]);
        $pool = array_values($pool);
    }

    return array_slice($picked, 0, $count);
}

/**
 * @param array<string, mixed> $candidate
 * @param list<array<string, mixed>> $picked
 */
function rdScheduleDiversityBonus(array $candidate, array $picked): float
{
    if ($picked === []) {
        return 100.0;
    }

    $cDate = (string) ($candidate['date'] ?? '');
    $cTime = (string) ($candidate['start_time'] ?? '');
    $cVenue = (int) ($candidate['venue_id'] ?? 0);
    $cHour = (int) substr($cTime, 0, 2);
    $cAmpm = $cHour < 12 ? 'am' : 'pm';

    $minDayDiff = 999.0;
    $sameTime = 0;
    $sameVenue = 0;
    $sameAmpm = 0;

    foreach ($picked as $existing) {
        $eDate = (string) ($existing['date'] ?? '');
        if ($eDate === $cDate) {
            return -100.0; // never two slots on the same day
        }
        $dayDiff = abs((strtotime($eDate) ?: 0) - (strtotime($cDate) ?: 0)) / 86400;
        $minDayDiff = min($minDayDiff, $dayDiff);

        if ((string) ($existing['start_time'] ?? '') === $cTime) {
            $sameTime++;
        }
        if ((int) ($existing['venue_id'] ?? 0) === $cVenue) {
            $sameVenue++;
        }
        $eHour = (int) substr((string) ($existing['start_time'] ?? '00'), 0, 2);
        if (($eHour < 12 ? 'am' : 'pm') === $cAmpm) {
            $sameAmpm++;
        }
    }

    $score = 0.0;
    // Prefer at least 2 days apart, but still allow 1-day gap if needed later.
    if ($minDayDiff < 1) {
        return -100.0;
    }
    $score += min(35.0, $minDayDiff * 12.0);
    if ($sameTime === 0) {
        $score += 35.0;
    } elseif ($sameTime === 1) {
        $score += 8.0;
    }
    if ($sameVenue === 0) {
        $score += 30.0;
    } elseif ($sameVenue === 1) {
        $score += 6.0;
    }
    if ($sameAmpm < count($picked)) {
        $score += 25.0; // mix morning + afternoon
    }

    return $score;
}

function rdScheduleCursorApiKey(): string
{
    if (defined('CURSOR_API_KEY') && CURSOR_API_KEY !== '') {
        return (string) CURSOR_API_KEY;
    }

    $env = function_exists('sms2_env') ? sms2_env('CURSOR_API_KEY') : getenv('CURSOR_API_KEY');
    if (is_string($env) && $env !== '') {
        return $env;
    }
    $file = defined('ROOT_PATH') ? ROOT_PATH . '/storage/keys/cursor_api_key' : '';
    if ($file !== '' && is_readable($file)) {
        $raw = trim((string) file_get_contents($file));
        if ($raw !== '') {
            return $raw;
        }
    }

    return '';
}
