<?php
/**
 * Research Director — AI Scheduling Optimizer
 * Finds conflict-free defense slots with comfortable venue capacity.
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
    $endTs   = strtotime($periodEnd);
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

    $venues = $pdo->query(
        "SELECT id, venue_name, capacity, venue_type, status
           FROM crad_research_venues
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

    $startHours = [8, 9, 10, 13, 14];
    $candidates = [];
    $evaluated  = 0;

    for ($dayTs = $startTs; $dayTs <= $endTs; $dayTs += 86400) {
        $weekday = (int) date('N', $dayTs);
        if ($weekday >= 6) {
            continue;
        }

        $date = date('Y-m-d', $dayTs);
        foreach ($startHours as $hour) {
            $startTime = sprintf('%02d:00', $hour);
            $slotStartTs = strtotime($date . ' ' . $startTime . ':00');
            $slotEndTs   = $slotStartTs + ($durationMinutes * 60);
            if ($slotEndTs === false || date('H', $slotEndTs) > 17 || (date('H', $slotEndTs) == 17 && date('i', $slotEndTs) > 0)) {
                continue;
            }
            $endTime = date('H:i', $slotEndTs);
            $startAt = date('Y-m-d H:i:s', $slotStartTs);
            $endAt   = date('Y-m-d H:i:s', $slotEndTs);

            foreach ($venues as $venue) {
                $evaluated++;
                $venueId   = (int) ($venue['id'] ?? 0);
                $capacity  = (int) ($venue['capacity'] ?? 0);
                $conflicts = rdScheduleConflictMessages($pdo, $groupId, $venueId, $startAt, $endAt, 0, $defenseType);
                if ($conflicts !== []) {
                    continue;
                }

                $headroom = $capacity - $expectedAttendees;
                $capacityScore = 100;
                if ($headroom < 5) {
                    $capacityScore = 40;
                } elseif ($headroom <= 15) {
                    $capacityScore = 75;
                } elseif ($headroom <= 40) {
                    $capacityScore = 100;
                } elseif ($headroom <= 80) {
                    $capacityScore = 85;
                } else {
                    $capacityScore = 70;
                }

                $dayLoad = rdScheduleVenueDayLoad($pdo, $venueId, $date);
                $loadScore = max(0, 100 - ($dayLoad * 25));

                $timeScore = match ($hour) {
                    9, 10 => 100,
                    8, 13 => 90,
                    default => 80,
                };

                $score = (int) round(($capacityScore * 0.45) + ($loadScore * 0.35) + ($timeScore * 0.20));

                $candidates[] = [
                    'date'        => $date,
                    'start_time'  => $startTime,
                    'end_time'    => $endTime,
                    'start_at'    => $startAt,
                    'end_at'      => $endAt,
                    'venue_id'    => $venueId,
                    'venue_name'  => (string) ($venue['venue_name'] ?? 'Venue'),
                    'capacity'    => $capacity,
                    'headroom'    => $headroom,
                    'day_load'    => $dayLoad,
                    'score'       => $score,
                    'reason'      => rdScheduleSlotReason($capacity, $expectedAttendees, $dayLoad, $hour),
                ];
            }
        }
    }

    if ($candidates === []) {
        return [
            'ok' => false,
            'message' => 'No conflict-free slots found in this period. Try a wider date range or fewer expected attendees.',
            'meta' => ['candidates_evaluated' => $evaluated],
        ];
    }

    usort($candidates, static fn(array $a, array $b): int => ($b['score'] <=> $a['score']) ?: strcmp($a['start_at'], $b['start_at']));

    $picked = rdSchedulePickDiverseSlots($candidates, $slotCount);

    return [
        'ok'      => true,
        'slots'   => $picked,
        'summary' => 'AI analyzed ' . number_format($evaluated) . ' time/venue combinations and selected '
            . count($picked) . ' optimal, conflict-free slots with comfortable venue capacity.',
        'meta'    => [
            'candidates_evaluated' => $evaluated,
            'candidates_valid'     => count($candidates),
            'expected_attendees'   => $expectedAttendees,
            'period_start'         => date('Y-m-d', $startTs),
            'period_end'           => date('Y-m-d', $endTs),
        ],
    ];
}

function rdScheduleVenueDayLoad(PDO $pdo, int $venueId, string $date): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
           FROM crad_research_defense_schedules rds
           " . rdOfficialScheduleJoinSql() . "
          WHERE rds.venue_id = ?
            AND DATE(rds.defense_datetime) = ?
            AND LOWER(rds.status) IN ('proposed', 'selected', 'scheduled', 'finalized', 'final')"
    );
    $stmt->execute([$venueId, $date]);

    return (int) $stmt->fetchColumn();
}

function rdScheduleSlotReason(int $capacity, int $expectedAttendees, int $dayLoad, int $hour): string
{
    $headroom = $capacity - $expectedAttendees;
    $parts = [];
    $parts[] = $headroom >= 10
        ? 'Comfortable venue capacity (' . $capacity . ' seats, ' . $headroom . ' spare)'
        : 'Venue fits expected attendees (' . $capacity . ' seats)';
    $parts[] = $dayLoad === 0 ? 'No other defenses in this venue that day' : 'Light venue use that day';
    $parts[] = $hour < 12 ? 'Morning slot' : 'Afternoon slot';

    return implode(' · ', $parts);
}

/**
 * @param list<array<string, mixed>> $candidates
 * @return list<array<string, mixed>>
 */
function rdSchedulePickDiverseSlots(array $candidates, int $count): array
{
    $picked = [];
    foreach ($candidates as $candidate) {
        if (count($picked) >= $count) {
            break;
        }
        $tooClose = false;
        foreach ($picked as $existing) {
            if (($existing['date'] ?? '') === ($candidate['date'] ?? '')) {
                $tooClose = true;
                break;
            }
            $dayDiff = abs(strtotime((string) $existing['date']) - strtotime((string) $candidate['date'])) / 86400;
            if ($dayDiff < 2) {
                $tooClose = true;
                break;
            }
        }
        if (!$tooClose) {
            $picked[] = $candidate;
        }
    }

    if (count($picked) < $count) {
        foreach ($candidates as $candidate) {
            if (count($picked) >= $count) {
                break;
            }
            $signature = ($candidate['date'] ?? '') . '|' . ($candidate['start_time'] ?? '') . '|' . ($candidate['venue_id'] ?? '');
            $exists = false;
            foreach ($picked as $existing) {
                $existingSig = ($existing['date'] ?? '') . '|' . ($existing['start_time'] ?? '') . '|' . ($existing['venue_id'] ?? '');
                if ($signature === $existingSig) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $picked[] = $candidate;
            }
        }
    }

    return array_slice($picked, 0, $count);
}

function rdScheduleCursorApiKey(): string
{
    if (defined('CURSOR_API_KEY') && CURSOR_API_KEY !== '') {
        return (string) CURSOR_API_KEY;
    }

    $env = function_exists('sms2_env') ? sms2_env('CURSOR_API_KEY') : getenv('CURSOR_API_KEY');

    return is_string($env) && $env !== '' ? $env : '';
}
