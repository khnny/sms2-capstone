<?php
/**
 * SMS 2 - Research Defense Scheduling
 * Module: CRAD
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/modules/crad/includes/chapter-evaluation-workflow.php';

requireAuth();
$roleKey = getCurrentUserRoleKey();
if (!smsRoleAllowedForModule(['crad_officer', 'research_coordinator', 'research_director'], 'crad')) {
    http_response_code(403);
    exit('Forbidden');
}

function cradEnsureDefenseScheduleTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS research_defense_schedules (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            research_group_id INT UNSIGNED DEFAULT NULL,
            proposal_id INT UNSIGNED DEFAULT NULL,
            proposal_number VARCHAR(30) DEFAULT NULL,
            group_number VARCHAR(40) NOT NULL,
            research_group VARCHAR(120) NOT NULL,
            research_title VARCHAR(255) NOT NULL,
            adviser_name VARCHAR(160) DEFAULT NULL,
            panel_members TEXT DEFAULT NULL,
            panel_chair VARCHAR(160) DEFAULT NULL,
            defense_type VARCHAR(40) NOT NULL DEFAULT 'Pre-Oral',
            venue VARCHAR(120) DEFAULT NULL,
            defense_datetime DATETIME DEFAULT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'Ready for Scheduling',
            recorded_by INT UNSIGNED DEFAULT NULL,
            recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_rds_group_number (group_number),
            KEY idx_rds_proposal_id (proposal_id),
            KEY idx_rds_proposal_number (proposal_number),
            KEY idx_rds_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $columns = [
        'research_group_id' => "ALTER TABLE research_defense_schedules ADD research_group_id INT UNSIGNED DEFAULT NULL AFTER id",
        'proposal_id' => "ALTER TABLE research_defense_schedules ADD proposal_id INT UNSIGNED DEFAULT NULL AFTER research_group_id",
        'proposal_number' => "ALTER TABLE research_defense_schedules ADD proposal_number VARCHAR(30) DEFAULT NULL AFTER proposal_id",
        'group_number' => "ALTER TABLE research_defense_schedules ADD group_number VARCHAR(40) NOT NULL DEFAULT '' AFTER proposal_number",
        'research_group' => "ALTER TABLE research_defense_schedules ADD research_group VARCHAR(120) NOT NULL DEFAULT '' AFTER group_number",
        'research_title' => "ALTER TABLE research_defense_schedules ADD research_title VARCHAR(255) NOT NULL DEFAULT '' AFTER research_group",
        'adviser_name' => "ALTER TABLE research_defense_schedules ADD adviser_name VARCHAR(160) DEFAULT NULL AFTER research_title",
        'panel_members' => "ALTER TABLE research_defense_schedules ADD panel_members TEXT DEFAULT NULL AFTER adviser_name",
        'panel_chair' => "ALTER TABLE research_defense_schedules ADD panel_chair VARCHAR(160) DEFAULT NULL AFTER panel_members",
        'defense_type' => "ALTER TABLE research_defense_schedules ADD defense_type VARCHAR(40) NOT NULL DEFAULT 'Pre-Oral' AFTER panel_chair",
        'venue' => "ALTER TABLE research_defense_schedules ADD venue VARCHAR(120) DEFAULT NULL AFTER panel_chair",
        'defense_datetime' => "ALTER TABLE research_defense_schedules ADD defense_datetime DATETIME DEFAULT NULL AFTER venue",
        'status' => "ALTER TABLE research_defense_schedules ADD status VARCHAR(40) NOT NULL DEFAULT 'Ready for Scheduling' AFTER defense_datetime",
        'recorded_by' => "ALTER TABLE research_defense_schedules ADD recorded_by INT UNSIGNED DEFAULT NULL AFTER status",
        'recorded_at' => "ALTER TABLE research_defense_schedules ADD recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER recorded_by",
        'updated_at' => "ALTER TABLE research_defense_schedules ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER recorded_at",
    ];

    foreach ($columns as $column => $sql) {
        if (!$pdo->query("SHOW COLUMNS FROM research_defense_schedules LIKE " . $pdo->quote($column))->fetch()) {
            $pdo->exec($sql);
        }
    }

    foreach ([
        'idx_rds_proposal_id' => "ALTER TABLE research_defense_schedules ADD KEY idx_rds_proposal_id (proposal_id)",
        'idx_rds_proposal_number' => "ALTER TABLE research_defense_schedules ADD KEY idx_rds_proposal_number (proposal_number)",
        'idx_rds_status' => "ALTER TABLE research_defense_schedules ADD KEY idx_rds_status (status)",
    ] as $index => $sql) {
        if (!$pdo->query("SHOW INDEX FROM research_defense_schedules WHERE Key_name = " . $pdo->quote($index))->fetch()) {
            $pdo->exec($sql);
        }
    }
}

function cradPruneOrphanDefenseSchedules(PDO $pdo): void
{
    cradEnsureDefenseScheduleTable($pdo);
    $pdo->exec("
        DELETE FROM research_defense_schedules
        WHERE NOT EXISTS (
            SELECT 1
            FROM research_proposals p
            WHERE (research_defense_schedules.proposal_id IS NOT NULL AND p.id = research_defense_schedules.proposal_id)
               OR (
                    research_defense_schedules.proposal_number IS NOT NULL
                    AND research_defense_schedules.proposal_number <> ''
                    AND (p.proposal_number = research_defense_schedules.proposal_number OR p.ref_code = research_defense_schedules.proposal_number)
               )
        )
    ");
}

function cradDefenseScheduleRows(PDO $pdo): array
{
    try {
        cradEnsureDefenseScheduleTable($pdo);
        cradPruneOrphanDefenseSchedules($pdo);
        $stmt = $pdo->query("
            SELECT
                rds.research_group_id,
                rds.group_number AS reference,
                rds.research_title,
                rds.research_group,
                rds.adviser_name,
                rds.panel_chair,
                rds.panel_members,
                rds.defense_type,
                COALESCE(NULLIF(rds.venue, ''), 'Ready for venue') AS venue,
                rds.defense_datetime,
                rds.status,
                rds.updated_at
            FROM research_defense_schedules rds
            JOIN research_proposals p
              ON (rds.proposal_id IS NOT NULL AND p.id = rds.proposal_id)
              OR (
                    rds.proposal_number IS NOT NULL
                    AND rds.proposal_number <> ''
                    AND (p.proposal_number = rds.proposal_number OR p.ref_code = rds.proposal_number)
                 )
            ORDER BY rds.updated_at DESC, rds.id DESC
        ");
        $rows = [];
        foreach (($stmt->fetchAll() ?: []) as $row) {
            if (
                strcasecmp(trim((string) ($row['defense_type'] ?? CRAD_DEFENSE_TYPE_PRE_ORAL)), CRAD_DEFENSE_TYPE_PRE_ORAL) === 0
                && (
                    trim((string) ($row['adviser_name'] ?? '')) === ''
                    || !chapterIsReadyForPreOral($pdo, (int) ($row['research_group_id'] ?? 0))
                )
            ) {
                continue;
            }
            $updated = strtotime((string) ($row['updated_at'] ?? '')) ?: time();
            $defenseTime = !empty($row['defense_datetime'])
                ? strtotime((string) $row['defense_datetime'])
                : false;
            $rows[] = [
                'reference' => (string) ($row['reference'] ?? ''),
                'title' => (string) (($row['research_title'] ?? '') ?: ($row['research_group'] ?? 'Research Group')),
                'owner' => (string) (($row['panel_chair'] ?? '') ?: 'For panel chair'),
                'detail' => (string) ($row['venue'] ?? 'Ready for venue'),
                'status' => (string) (($row['status'] ?? '') ?: 'Ready for Scheduling'),
                'status_class' => 'scheduled',
                'updated' => $defenseTime ? date('M j, Y h:i A', $defenseTime) : date('M j, Y h:i A', $updated),
                'defense_datetime_raw' => (string) ($row['defense_datetime'] ?? ''),
                'type' => 'Defense Scheduling',
                'defense_type' => (string) ($row['defense_type'] ?? CRAD_DEFENSE_TYPE_PRE_ORAL),
                'subtitle' => (string) ($row['research_group'] ?? ''),
            ];
        }
        return $rows;
    } catch (Throwable $e) {
        error_log('Defense schedule rows load failed: ' . $e->getMessage());
        return [];
    }
}

function cradDefenseScheduleStats(array $rows): array
{
    $today = 0;
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
    $todayKey = $now->format('Y-m-d');

    foreach ($rows as $row) {
        $rawDate = trim((string) ($row['defense_datetime_raw'] ?? ''));
        if ($rawDate === '') {
            continue;
        }

        try {
            $defenseDate = new DateTimeImmutable($rawDate, new DateTimeZone('Asia/Manila'));
            if ($defenseDate->format('Y-m-d') === $todayKey) {
                $today++;
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    return [
        'scheduled' => count($rows),
        'today' => $today,
        'completed' => 0,
        'postponed' => 0,
    ];
}

function cradDefenseSelectedAssignment(PDO $pdo, string $groupNumber, string $proposalRef): ?array
{
    if ($groupNumber === '' && $proposalRef === '') {
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                g.id,
                g.group_number,
                COALESCE(NULLIF(g.group_name, ''), g.group_number, 'Research Group') AS research_group,
                COALESCE(NULLIF(g.research_title, ''), p.research_title, '') AS research_title,
                (
                    SELECT a.adviser_name
                    FROM research_adviser_assignments a
                    WHERE a.assignment_status = 'Assigned'
                      AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
                    ORDER BY a.assigned_at DESC, a.updated_at DESC, a.id DESC
                    LIMIT 1
                ) AS adviser,
                '' AS panel_members
             FROM research_groups g
             JOIN research_proposals p ON p.id = g.proposal_id
             WHERE (:group_gate <> '' AND g.group_number = :group_match)
                OR (
                    :proposal_gate <> ''
                    AND (
                           g.proposal_number = :proposal_group_match
                        OR p.proposal_number = :proposal_number_match
                        OR p.ref_code = :proposal_ref_match
                        OR CAST(g.proposal_id AS CHAR) = :proposal_id_match
                    )
                )
             ORDER BY g.id DESC
             LIMIT 1
        ");
        $stmt->execute([
            ':group_gate' => $groupNumber,
            ':group_match' => $groupNumber,
            ':proposal_gate' => $proposalRef,
            ':proposal_group_match' => $proposalRef,
            ':proposal_number_match' => $proposalRef,
            ':proposal_ref_match' => $proposalRef,
            ':proposal_id_match' => $proposalRef,
        ]);
        $row = $stmt->fetch();
        if ($row && (
            trim((string) ($row['adviser'] ?? '')) === ''
            || !chapterIsReadyForPreOral($pdo, (int) ($row['id'] ?? 0))
        )) {
            return null;
        }
        return $row ?: null;
    } catch (Throwable $e) {
        error_log('Defense selected assignment load failed: ' . $e->getMessage());
        return null;
    }
}

function cradDefenseRowFromAssignment(?array $assignment): array
{
    if (!$assignment) {
        return [];
    }

    $group = trim((string) ($assignment['research_group'] ?? $assignment['group_number'] ?? 'Research Group'));
    $title = trim((string) ($assignment['research_title'] ?? ''));
    $panels = trim((string) ($assignment['panel_members'] ?? ''));
    $panelChair = trim((string) (explode(',', $panels)[0] ?? ''));
    $reference = trim((string) ($assignment['group_number'] ?? $group));

    return [
        'reference' => $reference !== '' ? $reference : 'READY-FOR-DEFENSE',
        'title' => $title !== '' ? $title : $group,
        'owner' => $panelChair !== '' ? $panelChair : 'For panel chair',
        'detail' => 'Ready for venue',
        'status' => 'Ready for Scheduling',
        'status_class' => 'scheduled',
        'updated' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('M j, Y h:i A'),
        'type' => 'Defense Scheduling',
        'subtitle' => $group,
    ];
}

$selectedDefenseAssignment = null;
$selectedGroupNumber = trim((string) ($_GET['group'] ?? ''));
$selectedProposalRef = trim((string) ($_GET['proposal'] ?? ''));
$cameFromAssignmentRecord = (($_GET['from'] ?? '') === 'assignment-record');
if ($cameFromAssignmentRecord || (($_GET['ajax'] ?? '') === 'selected-assignment')) {
    $selectedDefensePdo = cradDb();
    if ($selectedDefensePdo instanceof PDO) {
        $selectedDefenseAssignment = cradDefenseSelectedAssignment($selectedDefensePdo, $selectedGroupNumber, $selectedProposalRef);
    }
}

if (($_GET['ajax'] ?? '') === 'selected-assignment') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'assignment' => $selectedDefenseAssignment,
        'synced_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('M j, Y h:i:s A'),
    ]);
    exit;
}

if (($_GET['ajax'] ?? '') === 'defense-schedules') {
    header('Content-Type: application/json; charset=utf-8');
    $pdo = cradDb();
    $rows = $pdo instanceof PDO ? cradDefenseScheduleRows($pdo) : [];
    echo json_encode([
        'ok' => true,
        'rows' => $rows,
        'stats' => cradDefenseScheduleStats($rows),
        'synced_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('M j, Y h:i:s A'),
    ]);
    exit;
}

$pageTitle    = 'Research Defense Scheduling';
$activeModule = 'crad';
$activePage   = 'research-defense-scheduling';
$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Research Defense Scheduling', 'url' => null],
];

$readyDefensePdo = cradDb();
$readyDefenseRecords = $readyDefensePdo instanceof PDO ? cradDefenseScheduleRows($readyDefensePdo) : [];
if ($cameFromAssignmentRecord && $selectedDefenseAssignment) {
    $selectedReadyRow = cradDefenseRowFromAssignment($selectedDefenseAssignment);
    $alreadyListed = false;
    foreach ($readyDefenseRecords as $record) {
        if ((string) ($record['reference'] ?? '') === (string) ($selectedReadyRow['reference'] ?? '')) {
            $alreadyListed = true;
            break;
        }
    }
    if (!$alreadyListed) {
        array_unshift($readyDefenseRecords, $selectedReadyRow);
    }
}
$readyDefenseStats = cradDefenseScheduleStats($readyDefenseRecords);

$cradProcess = [
    'kicker' => 'CRAD Officer · Defense Workflow',
    'description' => 'Schedule research defense hearings, assign panel members, manage room availability, and track defense results.',
    'metrics' => [
        ['label' => 'Scheduled Defenses', 'value' => (string) $readyDefenseStats['scheduled'], 'icon' => 'fa-calendar-check', 'tone' => 'blue'],
        ['label' => 'Today', 'value' => (string) $readyDefenseStats['today'], 'icon' => 'fa-clock', 'tone' => 'amber'],
        ['label' => 'Completed', 'value' => (string) $readyDefenseStats['completed'], 'icon' => 'fa-check-circle', 'tone' => 'green'],
        ['label' => 'Postponed', 'value' => (string) $readyDefenseStats['postponed'], 'icon' => 'fa-calendar-times', 'tone' => 'purple'],
    ],
    'steps' => [
        ['Schedule Defense Date', 'Select proposal or final defense date and time slot with the research group and panel.'],
        ['Assign Panel & Venue', 'Confirm panel members and reserve an available room or venue for the defense.'],
        ['Send Notifications', 'Notify the research group, adviser, and panel of the confirmed schedule.'],
        ['Record Defense Result', 'Capture passed, revise, or failed outcome and update research progression.'],
    ],
    'columns' => ['Reference', 'Research Title / Group', 'Panel Chair', 'Status', 'Updated'],
    'fields' => ['reference', 'title', 'owner', 'status', 'updated'],
    'records' => $readyDefenseRecords,
    'show_add_button' => false,
    'show_pager' => false,
    'actions' => [
        ['label' => 'New Defense Schedule', 'process' => 'new', 'icon' => 'fa-plus', 'class' => 'primary'],
        ['label' => 'Check Room Availability', 'process' => 'validate', 'icon' => 'fa-door-open', 'class' => 'ghost'],
        ['label' => 'Confirm Defense', 'process' => 'approve', 'icon' => 'fa-check', 'class' => 'ghost'],
        ['label' => 'Defense Report', 'process' => 'report', 'icon' => 'fa-file-export', 'class' => 'ghost'],
    ],
    'form' => [
        ['label' => 'Research Reference', 'type' => 'text', 'name' => 'reference', 'placeholder' => 'RES-2026-00X'],
        ['label' => 'Research Title / Group', 'type' => 'text', 'name' => 'title', 'placeholder' => 'Research group title'],
        ['label' => 'Defense Type', 'type' => 'select', 'name' => 'defense_type', 'options' => [
            'Proposal Defense',
            'Final Defense',
        ]],
        ['label' => 'Panel Chair', 'type' => 'select', 'name' => 'panel_chair', 'options' => [
            'Dr. Roberto M. Santos',
            'Dr. Liza M. Torres',
            'Dr. Jose B. Tan',
            'Dr. Ana L. Mendoza',
        ]],
        ['label' => 'Venue / Room', 'type' => 'select', 'name' => 'venue', 'options' => [
            'CRAD Hall A',
            'CRAD Hall B',
            'Conference Room 2',
            'COE AVR',
        ]],
        ['label' => 'Date & Time', 'type' => 'text', 'name' => 'datetime', 'placeholder' => 'YYYY-MM-DD HH:MM'],
        ['label' => 'Defense Remarks', 'type' => 'textarea', 'name' => 'remarks', 'placeholder' => 'Schedule notes, panel availability, follow-up...'],
    ],
    'notice' => 'Defenses may only be scheduled after adviser assignment is confirmed. Room changes must be announced to all parties at least 24 hours in advance.',
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>
<?php
$defenseOriginalActivePage = $activePage;
$activePage = 'research-defense-scheduling-record';
require_once ROOT_PATH . '/includes/crad-module-process.php';
$activePage = $defenseOriginalActivePage;
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rowsBody = document.getElementById('mplRows');
    if (!rowsBody) return;
    const statNodes = Array.from(document.querySelectorAll('.mpl-stat strong'));
    let refreshing = false;
    let timer = null;
    const esc = function (value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    };
    const initials = function (value) {
        const parts = String(value || '').trim().split(/\s+/).filter(Boolean);
        return (parts[0]?.charAt(0) || 'D').toUpperCase() + (parts[1]?.charAt(0) || '').toUpperCase();
    };
    const statusClass = function (status) {
        const text = String(status || '').toLowerCase();
        if (text.includes('completed') || text.includes('passed')) return 'completed';
        if (text.includes('postponed') || text.includes('cancelled')) return 'cancelled';
        return 'scheduled';
    };
    const renderRows = function (rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            rowsBody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--sms-text-muted);padding:1.5rem;">No records found.</td></tr>';
            const meta = document.querySelector('.mpl-foot .meta');
            if (meta) meta.textContent = 'Showing 0-0 of 0 records';
            return;
        }
        rowsBody.innerHTML = rows.map(function (row) {
            const subject = row.title || row.subject || '';
            const subtitle = row.subtitle || '';
            const status = row.status || 'Ready for Scheduling';
            const search = [row.reference, subject, subtitle, row.owner, row.detail, row.updated, status, row.type].join(' ').toLowerCase();
            return '<tr data-search="' + esc(search) + '" data-status="' + esc(status.toLowerCase()) + '" data-type="' + esc(String(row.type || 'Defense Scheduling').toLowerCase()) + '">' +
                '<td><input type="checkbox" aria-label="Select ' + esc(row.reference || '') + '"></td>' +
                '<td class="ref">' + esc(row.reference || '') + '</td>' +
                '<td><div class="mpl-person"><span class="mpl-avatar">' + esc(initials(subject || subtitle)) + '</span><div><strong>' + esc(subject) + '</strong>' + (subtitle ? '<small>' + esc(subtitle) + '</small>' : '') + '</div></div></td>' +
                '<td>' + esc(row.owner || 'For panel chair') + '</td>' +
                '<td>' + esc(row.detail || 'Ready for venue') + '</td>' +
                '<td>' + esc(row.updated || '') + '</td>' +
                '<td><span class="mpl-status ' + esc(statusClass(status)) + '">' + esc(status) + '</span></td>' +
                '<td><div class="mpl-actions"><a href="?process=view&amp;ref=' + encodeURIComponent(row.reference || '') + '" title="View" aria-label="View"><?= smsIcon('eye') ?></a></div></td>' +
            '</tr>';
        }).join('');
        const meta = document.querySelector('.mpl-foot .meta');
        if (meta) meta.textContent = 'Showing 1-' + rows.length + ' of ' + rows.length + ' records';
    };
    const renderStats = function (stats) {
        if (statNodes[0]) statNodes[0].textContent = String(stats?.scheduled ?? 0);
        if (statNodes[1]) statNodes[1].textContent = String(stats?.today ?? 0);
        if (statNodes[2]) statNodes[2].textContent = String(stats?.completed ?? 0);
        if (statNodes[3]) statNodes[3].textContent = String(stats?.postponed ?? 0);
    };
    const refreshSchedules = async function () {
        if (refreshing) return;
        refreshing = true;
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax', 'defense-schedules');
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
        } catch (error) {
        } finally {
            refreshing = false;
        }
    };
    refreshSchedules();
    timer = window.setInterval(refreshSchedules, 5000);
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (timer) window.clearInterval(timer);
            timer = null;
            return;
        }
        if (timer) window.clearInterval(timer);
        refreshSchedules();
        timer = window.setInterval(refreshSchedules, 5000);
    });
});
</script>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
