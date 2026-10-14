<?php
/**
 * SMS 2 - CRAD Officer dashboard data provider
 *
 * Live figures for the CRAD Officer workspace. Everything is queried from the
 * CRAD research tables so the board reflects the officer's own module:
 * registered research groups, title approvals, adviser/panel assignments,
 * chapter and manuscript submissions, clearances, and collage payments.
 *
 * No grant-call data and no hardcoded/mock values.
 */

declare(strict_types=1);

if (!function_exists('cradDb')) {
    require_once __DIR__ . '/../config/config.php';
}

/**
 * Count rows in a table, or 0 when the table is not deployed yet.
 */
function cradOfficerCount(PDO $crad, string $table, string $where = '', array $params = []): int
{
    try {
        $sql = "SELECT COUNT(*) FROM `" . $table . "`" . ($where !== '' ? ' WHERE ' . $where : '');
        $stmt = $crad->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('cradOfficerCount ' . $table . ': ' . $e->getMessage());
        return 0;
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function cradOfficerFetchAll(PDO $crad, string $sql, array $params = []): array
{
    try {
        $stmt = $crad->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('cradOfficerFetchAll: ' . $e->getMessage());
        return [];
    }
}

/**
 * "3 min ago" style label used by the activity feed.
 */
function cradOfficerAgoLabel(string $value): string
{
    $value = trim($value);
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return 'Just now';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return 'Just now';
    }
    $diff = time() - $ts;
    if ($diff < 0) {
        return 'Just now';
    }
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $mins = (int) floor($diff / 60);
        return $mins . ' min' . ($mins === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return $hours . ' hr' . ($hours === 1 ? '' : 's') . ' ago';
    }
    $days = (int) floor($diff / 86400);
    if ($days < 30) {
        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }
    return date('M j, Y', $ts);
}

/**
 * Clearance label shared with the CRAD clearance page.
 */
function cradOfficerClearanceStatusLabel(string $status): string
{
    return match ($status) {
        'draft' => 'Awaiting signed image',
        'sent_to_adviser' => 'Awaiting signed image',
        'adviser_signed' => 'Awaiting signed image',
        'crad_received' => 'For CRAD approval',
        'clearance_done' => 'Cleared',
        'rejected' => 'Returned to student',
        default => $status !== '' ? $status : 'Not started',
    };
}

/**
 * @return array<string, mixed>
 */
function cradOfficerDashboardData(?PDO $crad = null): array
{
    $crad = $crad instanceof PDO ? $crad : cradDb();
    if (!$crad instanceof PDO) {
        return cradOfficerEmptyData('CRAD database connection unavailable.');
    }

    // ── Headline counters ────────────────────────────────────────────────
    $groups = cradOfficerCount($crad, 'crad_research_groups');
    $approved = cradOfficerCount($crad, 'crad_research_groups', "status = 'Approved'");
    $pendingTitle = cradOfficerCount(
        $crad,
        'crad_research_groups',
        "status <> 'Approved'"
    );
    $panelAssigned = cradOfficerCount($crad, 'crad_research_panel_assignments');
    $adviserAssigned = cradOfficerCount($crad, 'crad_research_adviser_assignments');
    $chapterSubs = cradOfficerCount($crad, 'crad_chapter_submissions');
    $manuscriptSubs = cradOfficerCount($crad, 'crad_manuscript_submissions');
    $defenseSchedules = cradOfficerCount($crad, 'crad_research_defense_schedules');
    $publications = cradOfficerCount($crad, 'crad_publications');

    // ── Clearance + payment tallies (what is still waiting on CRAD) ──────
    $clearanceDone    = cradOfficerCount($crad, 'crad_research_services_clearances', "status = 'clearance_done'");
    $clearanceTotal   = cradOfficerCount($crad, 'crad_research_services_clearances');
    $clearancePending = max(0, $clearanceTotal - $clearanceDone);
    $paymentPending   = cradOfficerCount($crad, 'crad_research_clearance_payments', "status IN ('pending','rejected')");
    $paymentApproved  = cradOfficerCount($crad, 'crad_research_clearance_payments', "status = 'approved'");

    // ── Research groups by college (donut) ───────────────────────────────
    $deptRows = cradOfficerFetchAll(
        $crad,
        "SELECT COALESCE(NULLIF(TRIM(college_dept), ''), 'Unassigned') AS label, COUNT(*) AS total
           FROM `crad_research_groups`
          GROUP BY label
          ORDER BY total DESC, label ASC"
    );
    $byCollege = [];
    foreach ($deptRows as $row) {
        $byCollege[] = [
            'label' => (string) ($row['label'] ?? 'Unassigned'),
            'total' => (int) ($row['total'] ?? 0),
        ];
    }

    // ── Module stage progress (bars) ─────────────────────────────────────
    $assignedGroups = cradOfficerCount(
        $crad,
        'crad_research_groups',
        "TRIM(COALESCE(adviser, '')) <> ''"
    );
    $progressStages = [
        ['key' => 'registered', 'label' => 'Groups registered', 'done' => $groups, 'total' => max(1, $groups), 'tone' => 'blue'],
        ['key' => 'title_approved', 'label' => 'Title approved', 'done' => $approved, 'total' => max(1, $groups), 'tone' => 'green'],
        ['key' => 'adviser', 'label' => 'Adviser assigned', 'done' => $assignedGroups, 'total' => max(1, $groups), 'tone' => 'blue'],
        ['key' => 'panel', 'label' => 'Panel assigned', 'done' => $panelAssigned > 0 ? min($panelAssigned, $groups) : 0, 'total' => max(1, $groups), 'tone' => 'orange'],
        ['key' => 'clearance', 'label' => 'Clearance approved', 'done' => $clearanceDone, 'total' => max(1, $clearanceDone + $clearancePending), 'tone' => 'gradient'],
    ];
    $progress = [];
    foreach ($progressStages as $stage) {
        $pct = $stage['total'] > 0
            ? (int) round(($stage['done'] / $stage['total']) * 100)
            : 0;
        $pct = max(0, min(100, $pct));
        $progress[] = [
            'label' => $stage['label'],
            'pct' => $pct,
            'tone' => $stage['tone'],
            'done' => (int) $stage['done'],
            'total' => (int) $stage['total'],
        ];
    }

    // ── Recent research groups (table) ───────────────────────────────────
    $groupRows = cradOfficerFetchAll(
        $crad,
        "SELECT rg.id, rg.group_number, rg.research_title, rg.college_dept, rg.adviser,
                rg.leader_name, rg.status, rg.flow_status, rg.member_count, rg.created_at,
                (SELECT COUNT(*) FROM `crad_research_panel_assignments` pa
                  WHERE pa.research_group_id = rg.id) AS panel_count,
                (SELECT c.status FROM `crad_research_services_clearances` c
                  WHERE c.research_group_id = rg.id
                  ORDER BY c.id DESC LIMIT 1) AS clearance_status
           FROM `crad_research_groups` rg
          ORDER BY rg.created_at DESC, rg.id DESC
          LIMIT 8"
    );
    $recentGroups = [];
    foreach ($groupRows as $row) {
        $adviser = trim((string) ($row['adviser'] ?? ''));
        $recentGroups[] = [
            'id' => (int) ($row['id'] ?? 0),
            'name' => trim((string) ($row['group_number'] ?? '')) ?: ('Group #' . (int) ($row['id'] ?? 0)),
            'role' => trim((string) ($row['college_dept'] ?? '')) ?: 'Unassigned college',
            'adviser' => $adviser !== '' ? $adviser : 'No adviser yet',
            'panel_count' => (int) ($row['panel_count'] ?? 0),
            'members' => (int) ($row['member_count'] ?? 0),
            'status' => (string) ($row['status'] ?? ''),
            'status_label' => trim((string) ($row['status'] ?? '')) !== ''
                ? (string) $row['status']
                : cradOfficerClearanceStatusLabel((string) ($row['clearance_status'] ?? '')),
            'when' => cradOfficerAgoLabel((string) ($row['created_at'] ?? '')),
        ];
    }

    // ── Recent activity feed ──────────────────────────────────────────────
    $activity = [];
    foreach (cradOfficerFetchAll(
        $crad,
        "SELECT title, body, url, created_at
           FROM `crad_research_clearance_notifications`
          ORDER BY created_at DESC, id DESC
          LIMIT 6"
    ) as $row) {
        $activity[] = [
            'icon' => 'fa-stamp',
            'tone' => 'blue',
            'text' => trim((string) ($row['title'] ?? '')) ?: 'Clearance update',
            'when' => cradOfficerAgoLabel((string) ($row['created_at'] ?? '')),
        ];
    }
    foreach (cradOfficerFetchAll(
        $crad,
        "SELECT title, created_at
           FROM `crad_panel_assignment_notifications`
          ORDER BY created_at DESC, id DESC
          LIMIT 4"
    ) as $row) {
        $activity[] = [
            'icon' => 'fa-user-tie',
            'tone' => 'purple',
            'text' => trim((string) ($row['title'] ?? '')) ?: 'Panel assignment update',
            'when' => cradOfficerAgoLabel((string) ($row['created_at'] ?? '')),
        ];
    }
    foreach (cradOfficerFetchAll(
        $crad,
        "SELECT g.group_number, g.status, g.created_at
           FROM `crad_research_groups` g
          ORDER BY g.created_at DESC, g.id DESC
          LIMIT 4"
    ) as $row) {
        $groupNo = trim((string) ($row['group_number'] ?? '')) ?: 'Research group';
        $activity[] = [
            'icon' => 'fa-flask',
            'tone' => 'green',
            'text' => $groupNo . ' - ' . (trim((string) ($row['status'] ?? '')) ?: 'updated'),
            'when' => cradOfficerAgoLabel((string) ($row['created_at'] ?? '')),
        ];
    }
    usort($activity, static function (array $a, array $b): int {
        return strcmp((string) ($b['when'] ?? ''), (string) ($a['when'] ?? ''));
    });
    $activity = array_slice($activity, 0, 6);

    $stats = [
        'research_groups' => $groups,
        'approved_research' => $approved,
        'pending_title' => $pendingTitle,
        'adviser_assigned' => $adviserAssigned,
        'panel_assigned' => $panelAssigned,
        'chapter_submissions' => $chapterSubs,
        'manuscript_submissions' => $manuscriptSubs,
        'defense_scheduled' => $defenseSchedules,
        'publications' => $publications,
        'clearance_pending' => $clearancePending,
        'clearance_done' => $clearanceDone,
        'payment_pending' => $paymentPending,
        'payment_approved' => $paymentApproved,
    ];

    $fingerprint = md5(json_encode([
        $stats,
        array_column($byCollege, 'total', 'label'),
        array_column($progress, 'pct', 'label'),
    ]) ?: '');

    return [
        'ok' => true,
        'error' => '',
        'stats' => $stats,
        'by_college' => $byCollege,
        'progress' => $progress,
        'recent_groups' => $recentGroups,
        'activity' => $activity,
        'updated_at' => date('Y-m-d H:i:s'),
        'fingerprint' => $fingerprint,
    ];
}

function cradOfficerEmptyData(string $error = ''): array
{
    $zero = [
        'research_groups' => 0, 'approved_research' => 0, 'pending_title' => 0,
        'adviser_assigned' => 0, 'panel_assigned' => 0, 'chapter_submissions' => 0,
        'manuscript_submissions' => 0, 'defense_scheduled' => 0, 'publications' => 0,
        'clearance_pending' => 0, 'clearance_done' => 0, 'payment_pending' => 0,
        'payment_approved' => 0,
    ];
    return [
        'ok' => $error === '',
        'error' => $error,
        'stats' => $zero,
        'by_college' => [],
        'progress' => [],
        'recent_groups' => [],
        'activity' => [],
        'updated_at' => date('Y-m-d H:i:s'),
        'fingerprint' => '',
    ];
}
