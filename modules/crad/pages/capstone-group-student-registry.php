<?php
/**
 * SMS 2 - Capstone Group/Student Registry
 * Module: CRAD
 *
 * Official registry of Capstone Research Groups.
 *
 * This page is READ-ONLY. It does NOT insert, update, or delete any rows.
 * It derives every displayed record from the existing CRAD workflow tables so
 * the registry is always real-time and accurate:
 *
 *   - research_groups                       -> generated Research Group Number
 *   - title_approvals                       -> title / members when linked
 *   - research_coordinator_assignments      -> official (Active) Research Coordinator
 *   - research_adviser_assignments          -> official (Assigned) Adviser
 *   - proposal_members / title_approvals    -> group leader + members
 *
 * A group appears here as soon as CRAD generates its Research Group Number
 * (RG-YYYY-NNN). Placeholder assignment groups (STU-*) stay hidden.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';

requireAuth();

$roleKey = getCurrentUserRoleKey();
if (!smsRoleAllowedForModule(['crad_officer'], 'crad')) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$pageTitle    = 'Capstone Group/Student Registry';
$activeModule = 'crad';
$activePage   = 'capstone-group-student-registry';
$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Capstone Group/Student Registry', 'url' => null],
];
$pageBannerIcon        = 'fa-clipboard-list';
$pageBannerDescription = 'Official registry of research groups and their members. Groups appear here in real time as soon as a Research Group Number is generated.';

require_once __DIR__ . '/../../../includes/breadcrumbs.php';

$pdo = getCradDatabaseConnection();
if (!function_exists('cradEnsureAssigneeSchema')) {
    require_once __DIR__ . '/../includes/title-approval-assignees.php';
}
try {
    cradEnsureAssigneeSchema($pdo);
} catch (Throwable $e) {
    error_log('Capstone registry schema ensure skipped: ' . $e->getMessage());
}

/**
 * Returns generated research groups for the official registry.
 * A group qualifies as soon as CRAD has generated a Research Group Number.
 * Placeholder STU-* assignment rows are excluded.
 *
 * @return array<int, array<string, mixed>>
 */
function cgsrRegistryRows(PDO $pdo): array
{
    $sql = "SELECT
                g.id                AS group_id,
                g.group_number,
                g.group_name,
                g.research_title,
                g.college_dept,
                g.adviser          AS group_adviser,
                g.academic_year,
                g.leader_name,
                g.leader_id,
                g.leader_email,
                g.leader_contact,
                g.proposal_id,
                g.proposal_number,
                g.title_approval_id,
                t.proposed_title,
                t.department       AS approval_department,
                t.members_json,
                ca.id              AS coord_assignment_id,
                ca.coordinator_name,
                ca.coordinator_email,
                ca.assigned_at     AS coord_assigned_at,
                aa.id              AS adviser_assignment_id,
                aa.adviser_name,
                aa.adviser_email,
                aa.assigned_at     AS adviser_assigned_at
            FROM research_groups g
            LEFT JOIN title_approvals t ON t.id = g.title_approval_id
            LEFT JOIN research_coordinator_assignments ca ON ca.id = (
                SELECT ca2.id
                FROM research_coordinator_assignments ca2
                WHERE ca2.status = 'Active'
                  AND (
                        ca2.research_group_id = g.id
                     OR (ca2.group_number IS NOT NULL AND ca2.group_number <> '' AND ca2.group_number = g.group_number)
                     OR (g.leader_id IS NOT NULL AND g.leader_id <> '' AND ca2.student_id = g.leader_id)
                  )
                ORDER BY ca2.updated_at DESC, ca2.id DESC
                LIMIT 1
            )
            LEFT JOIN research_adviser_assignments aa ON aa.id = (
                SELECT aa2.id
                FROM research_adviser_assignments aa2
                WHERE aa2.assignment_status IN ('Assigned', 'Confirmed')
                  AND (
                        aa2.research_group_id = g.id
                     OR (aa2.group_number IS NOT NULL AND aa2.group_number <> '' AND aa2.group_number = g.group_number)
                     OR (g.leader_id IS NOT NULL AND g.leader_id <> '' AND aa2.student_id = g.leader_id)
                  )
                ORDER BY (aa2.assignment_status = 'Confirmed') DESC, (aa2.assignment_status = 'Assigned') DESC, aa2.updated_at DESC, aa2.id DESC
                LIMIT 1
            )
            WHERE g.group_number IS NOT NULL
              AND TRIM(g.group_number) <> ''
              AND g.group_number NOT LIKE 'STU-%'
            ORDER BY g.date_assigned DESC, g.id DESC";

    try {
        return $pdo->query($sql)->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Capstone registry load failed: ' . $e->getMessage());
        return [];
    }
}
/**
 * Resolve the group leader and its ordinary members from the existing member
 * records. Proposal-based groups use proposal_members (sort_order 1 = leader);
 * title-approval based groups use title_approvals.members_json (first = leader).
 * The leader is never mixed into the ordinary members list.
 */
function cgsrResolveMembers(PDO $pdo, array $g): array
{
    $roster = [];

    // 1) Proposal-based groups -> proposal_members (has a real role/order field)
    if ((int) ($g['proposal_id'] ?? 0) > 0) {
        try {
            $stmt = $pdo->prepare(
                "SELECT sort_order, student_id, student_name
                 FROM proposal_members
                 WHERE proposal_id = ?
                 ORDER BY sort_order ASC, id ASC"
            );
            $stmt->execute([(int) $g['proposal_id']]);
            foreach ($stmt->fetchAll() as $m) {
                $name = trim((string) ($m['student_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $roster[] = [
                    'name'       => $name,
                    'id'         => trim((string) ($m['student_id'] ?? '')),
                    'sort_order' => (int) ($m['sort_order'] ?? 1),
                ];
            }
        } catch (Throwable $e) {
            error_log('Capstone registry member lookup failed: ' . $e->getMessage());
            $roster = [];
        }
    }

    // 2) Title-approval based groups -> members_json [ [name, section, id], ... ]
    if ($roster === []) {
        $json = trim((string) ($g['members_json'] ?? ''));
        if ($json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $order = 1;
                foreach ($decoded as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $name = trim((string) ($entry[0] ?? ''));
                    if ($name === '') {
                        $name = trim((string) ($entry['name'] ?? ''));
                    }
                    if ($name === '') {
                        continue;
                    }
                    $id = trim((string) ($entry[2] ?? ($entry[1] ?? '')));
                    if (!is_string($id)) {
                        $id = '';
                    }
                    $roster[] = ['name' => $name, 'id' => $id, 'sort_order' => $order++];
                }
            }
        }
    }

    // Leader: prefer the registered leader on the research group; else first member.
    $leaderName = trim((string) ($g['leader_name'] ?? ''));
    $leaderId   = trim((string) ($g['leader_id'] ?? ''));
    if ($leaderName === '' && $roster !== []) {
        $leaderName = $roster[0]['name'];
        $leaderId   = $roster[0]['id'];
    }

    // Ordinary members: every roster entry that is not the leader's name.
    $members = [];
    $leaderKey = mb_strtolower($leaderName);
    foreach ($roster as $entry) {
        if ($entry['name'] === '' || $entry['name'] === $leaderName || mb_strtolower($entry['name']) === $leaderKey) {
            continue;
        }
        $members[] = ['name' => $entry['name'], 'id' => $entry['id']];
    }

    return [
        'leader'  => ['name' => $leaderName, 'id' => $leaderId],
        'members' => $members,
    ];
}

/**
 * Resolve the display values for a registry row.
 */
function cgsrDisplayRow(PDO $pdo, array $g): array
{
    $members = cgsrResolveMembers($pdo, $g);

    $program = trim((string) ($g['college_dept'] ?? ''));
    if ($program === '') {
        $program = trim((string) ($g['approval_department'] ?? ''));
    }

    $title = trim((string) ($g['research_title'] ?? ''));
    if ($title === '') {
        $title = trim((string) ($g['proposed_title'] ?? ''));
    }

    $adviser = trim((string) ($g['adviser_name'] ?? ''));
    if ($adviser === '') {
        $adviser = trim((string) ($g['group_adviser'] ?? ($g['adviser'] ?? '')));
    }

    $coordinator = trim((string) ($g['coordinator_name'] ?? ''));

    return [
        'group_id'          => (int) ($g['group_id'] ?? 0),
        'group_number'      => (string) ($g['group_number'] ?? ''),
        'group_name'        => (string) ($g['group_name'] ?? ''),
        'research_title'    => $title,
        'proposal_number'   => (string) ($g['proposal_number'] ?? ''),
        'program'           => $program,
        'academic_year'     => (string) ($g['academic_year'] ?? ''),
        'leader'            => $members['leader'],
        'members'           => $members['members'],
        'adviser'           => $adviser,
        'adviser_email'     => (string) ($g['adviser_email'] ?? ''),
        'coordinator'       => $coordinator,
        'coordinator_email' => (string) ($g['coordinator_email'] ?? ''),
    ];
}
/**
 * Build the full registry payload (stats + rows) used for both server-side
 * rendering and the real-time ajax refresh endpoint.
 */
function cgsrPayload(PDO $pdo): array
{
    $raw  = cgsrRegistryRows($pdo);
    $rows = [];
    $adviserSet = [];
    $coordinatorSet = [];
    $totalStudents = 0;

    foreach ($raw as $g) {
        $row = cgsrDisplayRow($pdo, $g);
        $rows[] = $row;
        $totalStudents += 1 + count($row['members']);
        if ($row['adviser'] !== '') {
            $adviserSet[mb_strtolower($row['adviser'])] = true;
        }
        if ($row['coordinator'] !== '') {
            $coordinatorSet[mb_strtolower($row['coordinator'])] = true;
        }
    }

    return [
        'ok'        => true,
        'message'   => '',
        'stats'     => [
            'total_groups'      => count($rows),
            'total_students'    => $totalStudents,
            'total_advisers'    => count($adviserSet),
            'total_coordinators'=> count($coordinatorSet),
        ],
        'rows'      => $rows,
        'synced_at' => date('Y-m-d H:i:s'),
    ];
}

// ---------------------------------------------------------------------------
// Real-time refresh endpoint (same pattern as the other CRAD pages).
// ---------------------------------------------------------------------------
$ajaxRequest = $_REQUEST['ajax'] ?? null;
if ($ajaxRequest === 'cgsr-registry') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(cgsrPayload($pdo));
    exit;
}

require_once ROOT_PATH . '/includes/layout-start.php';
$payload = cgsrPayload($pdo);
$stats   = $payload['stats'];
$rows    = $payload['rows'];

// Distinct values for the filter dropdowns.
$academicYears = [];
$programs = [];
foreach ($rows as $r) {
    if ($r['academic_year'] !== '' && !in_array($r['academic_year'], $academicYears, true)) {
        $academicYears[] = $r['academic_year'];
    }
    if ($r['program'] !== '' && !in_array($r['program'], $programs, true)) {
        $programs[] = $r['program'];
    }
}
sort($academicYears, SORT_STRING);
sort($programs, SORT_STRING);
?>
<?php renderBreadcrumbs($breadcrumbs); ?>

<style>
.cgsr-wrap { display: flex; flex-direction: column; gap: 1.25rem; }
.cgsr-stats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 0.85rem; }
.cgsr-stat {
    display: flex; align-items: center; gap: 0.85rem;
    padding: 0.95rem 1rem;
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 14px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.cgsr-stat-icon { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px; flex: 0 0 auto; }
.cgsr-stat-icon.indigo { color: #6366f1; background: rgba(99,102,241,0.12); }
.cgsr-stat-icon.blue   { color: #2563eb; background: rgba(37,99,235,0.12); }
.cgsr-stat-icon.green  { color: #059669; background: rgba(16,185,129,0.12); }
.cgsr-stat-icon.amber  { color: #d97706; background: rgba(245,158,11,0.14); }
.cgsr-stat strong { display: block; color: var(--sms-heading); font-size: 1.3rem; font-weight: 850; }
.cgsr-stat span { color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; }

.cgsr-card {
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 16px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-sm);
    overflow: hidden;
}
.cgsr-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
}
.cgsr-card-title { min-width: 0; }
.cgsr-card-title h2 { margin: 0; color: var(--sms-text-muted); font-size: 0.78rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; }
.cgsr-card-title span { color: var(--sms-text-muted); font-size: 0.78rem; font-weight: 700; }
.cgsr-meta { color: var(--sms-text-muted); font-size: 0.78rem; font-weight: 700; white-space: nowrap; }
.cgsr-card-tools {
    align-items: center; background: var(--sms-surface-solid, #fff);
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    display: flex; flex-wrap: wrap; gap: 0.75rem; padding: 1rem 1.25rem;
}
.cgsr-search { position: relative; flex: 1 1 auto; min-width: 200px; }
.cgsr-search i { color: var(--sms-text-muted); left: .9rem; pointer-events: none; position: absolute; top: 50%; transform: translateY(-50%); }
.cgsr-search input {
    background: var(--sms-surface-muted, #f8fafc);
    border: 1px solid var(--sms-border, #dbe4f0);
    border-radius: 10px; color: var(--sms-text, #334155);
    font-size: .86rem; min-height: 40px; outline: none;
    padding: .5rem .75rem .5rem 2.25rem; width: 100%;
}
.cgsr-search input:focus { border-color: var(--sms-primary, #2454c6); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }
.cgsr-filter {
    background: var(--sms-surface-solid, #fff);
    border: 1px solid var(--sms-border, #dbe4f0);
    border-radius: 10px; color: var(--sms-text, #334155);
    flex: 0 0 170px; font-size: 0.86rem; min-height: 40px; outline: none;
    padding: 0.5rem 0.85rem;
}
.cgsr-filter:focus { border-color: var(--sms-primary, #2454c6); box-shadow: 0 0 0 3px rgba(37, 99, 235, .12); }

.cgsr-table-wrap { overflow-x: auto; }
.cgsr-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.cgsr-table th, .cgsr-table td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); text-align: left; vertical-align: middle; }
.cgsr-table th { color: var(--sms-text-muted); background: var(--sms-surface-muted, #f8fafc); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; }
.cgsr-title { color: var(--sms-heading); font-weight: 800; line-height: 1.35; }
.cgsr-meta-block { display: block; margin-top: 0.2rem; color: var(--sms-text-muted); font-size: 0.75rem; font-weight: 600; }
.cgsr-code { font-weight: 850; color: var(--sms-heading, #0f172a); }
.cgsr-badge { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.28rem 0.62rem; border-radius: 999px; font-size: 0.76rem; font-weight: 800; }
.cgsr-badge-registered { color: #047857; background: #d1fae5; }
.cgsr-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.42rem;
    min-height: 36px; padding: 0.42rem 0.85rem;
    border: 1px solid transparent; border-radius: 10px;
    font-size: 0.8rem; font-weight: 800; text-decoration: none; cursor: pointer; white-space: nowrap;
}
.cgsr-btn-primary { color: #fff; background: #2563eb; border-color: #2563eb; box-shadow: 0 6px 16px rgba(37,99,235,0.22); }
.cgsr-btn-primary:hover { background: #1d4ed8; }
.cgsr-empty { padding: 2rem 1.25rem; text-align: center; color: var(--sms-text-muted); font-size: 0.9rem; font-weight: 700; }
.cgsr-empty[hidden] { display: none; }
.cgsr-empty strong { display: block; color: var(--sms-heading); font-size: 0.95rem; font-weight: 850; margin-bottom: 0.35rem; }
.cgsr-empty small { display: block; color: var(--sms-text-muted); font-size: 0.82rem; font-weight: 600; line-height: 1.5; }
.cgsr-foot { padding: 0.85rem 1.25rem; border-top: 1px solid var(--sms-border, #e2e8f0); color: var(--sms-text-muted); font-size: 0.78rem; font-weight: 700; }
.cgsr-modal-overlay {
    align-items: center; background: rgba(2,6,23,0.55); backdrop-filter: blur(2px);
    display: flex; justify-content: center; inset: 0; position: fixed; z-index: 1050;
    overflow-y: auto; padding: 2rem 1rem;
}
.cgsr-modal-overlay[hidden] { display: none; }
.cgsr-modal {
    background: var(--sms-surface-solid, #fff); border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 18px; box-shadow: 0 24px 60px rgba(2,6,23,0.35); display: flex;
    flex-direction: column; max-height: min(88vh, calc(100vh - 4rem));
    max-width: 760px; width: 100%;
}
.cgsr-modal-head {
    align-items: center; border-bottom: 1px solid var(--sms-border, #e2e8f0);
    display: flex; flex: 0 0 auto; justify-content: space-between; padding: 1rem 1.25rem;
}
.cgsr-modal-head h3 { color: var(--sms-heading); font-size: 1rem; font-weight: 850; margin: 0; }
.cgsr-modal-close {
    background: var(--sms-surface-muted, #f1f5f9); border: 1px solid var(--sms-border, #dbe4f0);
    border-radius: 9px; color: var(--sms-text-muted); cursor: pointer; height: 32px; width: 32px;
}
.cgsr-modal-close:hover { color: #b91c1c; border-color: #fecaca; }
.cgsr-modal-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 1.25rem; }
.cgsr-detail-head { align-items: center; display: flex; flex-wrap: wrap; gap: 0.7rem; margin-bottom: 1.1rem; }
.cgsr-detail-code { font-size: 1.1rem; font-weight: 850; color: var(--sms-heading, #0f172a); }
.cgsr-detail-grid { display: grid; gap: 0.85rem 1.5rem; grid-template-columns: repeat(2, minmax(0,1fr)); }
.cgsr-detail-item { display: flex; flex-direction: column; gap: 0.2rem; min-width: 0; }
.cgsr-detail-label { color: var(--sms-text-muted); font-size: 0.68rem; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase; }
.cgsr-detail-value { color: var(--sms-heading); font-size: 0.86rem; font-weight: 700; line-height: 1.45; overflow-wrap: anywhere; }
.cgsr-detail-section { border-top: 1px solid var(--sms-border, #e2e8f0); margin-top: 1.2rem; padding-top: 1.1rem; }
.cgsr-detail-section h4 { color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 800; letter-spacing: 0.07em; margin: 0 0 0.8rem; text-transform: uppercase; }
.cgsr-detail-section h4 i { margin-right: 0.4rem; }
.cgsr-detail-list { display: flex; flex-direction: column; gap: 0.55rem; }
.cgsr-detail-list-item { align-items: flex-start; background: var(--sms-surface-muted, #f8fafc); border: 1px solid var(--sms-border, #e2e8f0); border-radius: 10px; display: flex; gap: 0.8rem; padding: 0.7rem 0.85rem; }
.cgsr-detail-role { background: rgba(99,102,241,0.12); border-radius: 999px; color: #4338ca; flex: 0 0 auto; font-size: 0.68rem; font-weight: 900; padding: 0.22rem 0.55rem; letter-spacing: 0.03em; text-transform: uppercase; }
.cgsr-detail-list-item > div:last-child { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
.cgsr-detail-list-item .cgsr-meta-block { margin-top: 0; }

[data-theme="dark"] .cgsr-modal { background: #0f172a; border-color: rgba(148,163,184,0.25); }
[data-theme="dark"] .cgsr-modal-close { background: rgba(15,23,42,0.72); border-color: rgba(148,163,184,0.25); color: #e2e8f0; }
[data-theme="dark"] .cgsr-detail-list-item { background: rgba(148,163,184,0.08); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .cgsr-stat, [data-theme="dark"] .cgsr-card { background: rgba(15,23,42,0.72); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .cgsr-table th { background: rgba(148,163,184,0.06); }

@media (max-width: 1199.98px) {
    .cgsr-stats { grid-template-columns: repeat(2, minmax(0,1fr)); }
}
@media (max-width: 767.98px) {
    .cgsr-stats { grid-template-columns: 1fr; }
    .cgsr-card-head, .cgsr-card-tools { align-items: stretch; flex-direction: column; }
    .cgsr-search { width: 100%; }
    .cgsr-filter { flex-basis: auto; width: 100%; }
    .cgsr-detail-grid { grid-template-columns: 1fr; }
    .cgsr-modal-overlay { padding: 1rem 0.75rem; }
}
</style>
<div class="cgsr-wrap">
    <div class="cgsr-stats">
        <div class="cgsr-stat">
            <div class="cgsr-stat-icon indigo"><?= smsIcon('users') ?></div>
            <div><strong id="cgsr-stat-groups"><?= (int) $stats['total_groups'] ?></strong><span>Registered Groups</span></div>
        </div>
        <div class="cgsr-stat">
            <div class="cgsr-stat-icon green"><?= smsIcon('user-graduate') ?></div>
            <div><strong id="cgsr-stat-students"><?= (int) $stats['total_students'] ?></strong><span>Total Students</span></div>
        </div>
        <div class="cgsr-stat">
            <div class="cgsr-stat-icon blue"><?= smsIcon('user-tie') ?></div>
            <div><strong id="cgsr-stat-advisers"><?= (int) $stats['total_advisers'] ?></strong><span>Total Advisers</span></div>
        </div>
        <div class="cgsr-stat">
            <div class="cgsr-stat-icon amber"><?= smsIcon('user-cog') ?></div>
            <div><strong id="cgsr-stat-coordinators"><?= (int) $stats['total_coordinators'] ?></strong><span>Total Coordinators</span></div>
        </div>
    </div>

    <section class="cgsr-card">
        <div class="cgsr-card-head">
            <div class="cgsr-card-title">
                <h2><?= smsIcon('clipboard-list') ?> Official Registered Groups</h2>
                <span data-cgsr-count><?= count($rows) ?> registered group<?= count($rows) === 1 ? '' : 's' ?></span>
            </div>
            <div class="cgsr-meta" data-cgsr-sync>Synced <?= htmlspecialchars(date('M j, Y g:i:s A')) ?></div>
        </div>
        <div class="cgsr-card-tools">
            <label class="cgsr-search">
                <?= smsIcon('search') ?>
                <input type="search" data-cgsr-search placeholder="Search by group, title, leader, adviser, or coordinator..." aria-label="Search registry">
            </label>
            <select class="cgsr-filter" data-cgsr-filter="academic_year" aria-label="Filter by Academic Year">
                <option value="">All Academic Years</option>
                <?php foreach ($academicYears as $ay): ?>
                    <option value="<?= htmlspecialchars($ay) ?>"><?= htmlspecialchars($ay) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="cgsr-filter" data-cgsr-filter="program" aria-label="Filter by Program">
                <option value="">All Programs</option>
                <?php foreach ($programs as $prog): ?>
                    <option value="<?= htmlspecialchars($prog) ?>"><?= htmlspecialchars($prog) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="cgsr-table-wrap" data-cgsr-table>
            <table class="cgsr-table">
                <thead>
                    <tr>
                        <th>Research Group</th>
                        <th>Research Title</th>
                        <th>Leader</th>
                        <th>Adviser</th>
                        <th>Coordinator</th>
                        <th>Academic Year</th>
                        <th>Status</th>
                        <th style="width:110px;">Action</th>
                    </tr>
                </thead>
                <tbody data-cgsr-tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8">
                            <div class="cgsr-empty" data-cgsr-empty>
                                <strong>No Registered Groups Yet</strong>
                                <small>Groups appear here in real time as soon as a Research Group Number is generated.</small>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <?php
                                $search = strtolower(trim(
                                    ($r['group_number'] ?? '') . ' ' . ($r['group_name'] ?? '') . ' ' .
                                    ($r['research_title'] ?? '') . ' ' . ($r['leader']['name'] ?? '') . ' ' .
                                    ($r['adviser'] ?? '') . ' ' . ($r['coordinator'] ?? '') . ' ' .
                                    ($r['program'] ?? '') . ' ' . ($r['academic_year'] ?? '')
                                ));
                            ?>
                            <tr data-cgsr-row
                                data-academic-year="<?= htmlspecialchars($r['academic_year']) ?>"
                                data-program="<?= htmlspecialchars($r['program']) ?>"
                                data-search="<?= htmlspecialchars($search) ?>">
                                <td>
                                    <div class="cgsr-code"><?= htmlspecialchars($r['group_number']) ?></div>
                                    <?php if (trim((string) $r['group_name']) !== ''): ?>
                                        <span class="cgsr-meta-block"><?= htmlspecialchars($r['group_name']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="cgsr-title"><?= htmlspecialchars($r['research_title'] !== '' ? $r['research_title'] : 'Untitled research') ?></div>
                                    <span class="cgsr-meta-block"><?= htmlspecialchars($r['program']) ?></span>
                                </td>
                                <td>
                                    <div class="cgsr-title"><?= htmlspecialchars($r['leader']['name'] !== '' ? $r['leader']['name'] : '—') ?></div>
                                    <?php if (trim((string) $r['leader']['id']) !== ''): ?>
                                        <span class="cgsr-meta-block"><?= htmlspecialchars($r['leader']['id']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="cgsr-title"><?= htmlspecialchars($r['adviser'] !== '' ? $r['adviser'] : '—') ?></div>
                                    <?php if (trim((string) $r['adviser_email']) !== ''): ?>
                                        <span class="cgsr-meta-block"><?= htmlspecialchars($r['adviser_email']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="cgsr-title"><?= htmlspecialchars($r['coordinator'] !== '' ? $r['coordinator'] : '—') ?></div>
                                    <?php if (trim((string) $r['coordinator_email']) !== ''): ?>
                                        <span class="cgsr-meta-block"><?= htmlspecialchars($r['coordinator_email']) ?></span>
                                    <?php endif; ?>
                                </td>
                                    <td><div class="cgsr-title"><?= htmlspecialchars($r['academic_year'] !== '' ? $r['academic_year'] : '—') ?></div></td>
                                <td><span class="cgsr-badge cgsr-badge-registered"><?= smsIcon('check') ?> Registered</span></td>
                                <td>
                                    <button type="button" class="cgsr-btn cgsr-btn-primary" data-cgsr-view="<?= htmlspecialchars($r['group_number']) ?>">
                                        <?= smsIcon('eye') ?> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="cgsr-foot" data-cgsr-count2></div>
    </section>
</div>

<!-- View Details Modal -->
<div class="cgsr-modal-overlay" data-cgsr-modal hidden>
    <div class="cgsr-modal" role="dialog" aria-modal="true">
        <div class="cgsr-modal-head">
            <h3><?= smsIcon('clipboard-list') ?> Registered Group Details</h3>
            <button type="button" class="cgsr-modal-close" data-cgsr-close aria-label="Close"><?= smsIcon('times') ?></button>
        </div>
        <div class="cgsr-modal-body" data-cgsr-modal-body></div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const card = document.querySelector('.cgsr-card');
    if (!card) return;

    const tbody    = card.querySelector('[data-cgsr-tbody]');
    const search   = card.querySelector('[data-cgsr-search]');
    const ayFilter = card.querySelector('[data-cgsr-filter="academic_year"]');
    const pgFilter = card.querySelector('[data-cgsr-filter="program"]');
    const count    = card.querySelector('[data-cgsr-count]');
    const count2   = card.querySelector('[data-cgsr-count2]');
    const sync     = card.querySelector('[data-cgsr-sync]');
    const modal    = document.querySelector('[data-cgsr-modal]');
    const modalBody= document.querySelector('[data-cgsr-modal-body]');

    let rows = <?= json_encode($rows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    if (!Array.isArray(rows)) rows = [];
    let refreshing = false;

    const esc = function (value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };

    const rowSearchText = function (row) {
        return [
            row.group_number, row.group_name, row.research_title,
            (row.leader && row.leader.name) || '', (row.leader && row.leader.id) || '',
            row.adviser, row.coordinator, row.program, row.academic_year
        ].join(' ').toLowerCase();
    };

    const renderTable = function () {
        if (!tbody) return;
        const term = (search ? search.value.trim().toLowerCase() : '');
        const ay = (ayFilter ? ayFilter.value.trim().toLowerCase() : '');
        const pg = (pgFilter ? pgFilter.value.trim().toLowerCase() : '');

        const filtered = rows.filter(function (r) {
            const s = rowSearchText(r);
            if (term !== '' && s.indexOf(term) === -1) return false;
            if (ay !== '' && String(r.academic_year || '').toLowerCase() !== ay) return false;
            if (pg !== '' && String(r.program || '').toLowerCase() !== pg) return false;
            return true;
        });

        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8"><div class="cgsr-empty" data-cgsr-empty>' +
                '<strong>No Registered Groups Yet</strong><small>Groups appear here in real time as soon as a Research Group Number is generated.</small></div></td></tr>';
        } else if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8"><div class="cgsr-empty" data-cgsr-empty>' +
                '<strong>No Match</strong><small>No registered groups match your search or filters.</small></div></td></tr>';
        } else {
            tbody.innerHTML = filtered.map(function (r) {
                const leader = (r.leader && r.leader.name) || '\u2014';
                const leaderId = (r.leader && r.leader.id) || '';
                const title = r.research_title || 'Untitled research';
                const adviser = r.adviser || '\u2014';
                const coordinator = r.coordinator || '\u2014';
                const year = r.academic_year || '\u2014';
                return '<tr data-cgsr-row data-group-number="' + esc(r.group_number) + '">' +
                    '<td><div class="cgsr-code">' + esc(r.group_number) + '</div>' +
                        (r.group_name ? '<span class="cgsr-meta-block">' + esc(r.group_name) + '</span>' : '') + '</td>' +
                    '<td><div class="cgsr-title">' + esc(title) + '</div>' +
                        '<span class="cgsr-meta-block">' + esc(r.program) + '</span></td>' +
                    '<td><div class="cgsr-title">' + esc(leader) + '</div>' +
                        (leaderId ? '<span class="cgsr-meta-block">' + esc(leaderId) + '</span>' : '') + '</td>' +
                    '<td><div class="cgsr-title">' + esc(adviser) + '</div>' +
                        (r.adviser_email ? '<span class="cgsr-meta-block">' + esc(r.adviser_email) + '</span>' : '') + '</td>' +
                    '<td><div class="cgsr-title">' + esc(coordinator) + '</div>' +
                        (r.coordinator_email ? '<span class="cgsr-meta-block">' + esc(r.coordinator_email) + '</span>' : '') + '</td>' +
                    '<td><div class="cgsr-title">' + esc(year) + '</div></td>' +
                    '<td><span class="cgsr-badge cgsr-badge-registered"><?= smsIcon('check') ?> Registered</span></td>' +
                    '<td><button type="button" class="cgsr-btn cgsr-btn-primary" data-cgsr-view="' + esc(r.group_number) + '"><?= smsIcon('eye') ?> View</button></td>' +
                '</tr>';
            }).join('');
            tbody.querySelectorAll('[data-cgsr-view]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const g = btn.getAttribute('data-cgsr-view');
                    const found = rows.find(function (r) { return r.group_number === g; });
                    if (found) openDetails(found);
                });
            });
        }

        if (count) count.textContent = filtered.length + ' registered group' + (filtered.length === 1 ? '' : 's');
        if (count2) count2.textContent = filtered.length + ' of ' + rows.length + ' registered group(s) shown';
    };
    const renderStats = function (data) {
        const s = (data && data.stats) || {};
        const el = function (id, v) { const n = document.getElementById(id); if (n) n.textContent = String(v); };
        el('cgsr-stat-groups', s.total_groups ?? '-');
        el('cgsr-stat-students', s.total_students ?? '-');
        el('cgsr-stat-advisers', s.total_advisers ?? '-');
        el('cgsr-stat-coordinators', s.total_coordinators ?? '-');
    };

    const badge = function (label, cls) {
        return '<span class="cgsr-badge ' + cls + '"><?= smsIcon('check') ?> ' + esc(label) + '</span>';
    };

    const openDetails = function (r) {
        if (!modal || !modalBody) return;
        const members = Array.isArray(r.members) ? r.members : [];
        const memberRows = members.length
            ? members.map(function (m, i) {
                return '<div class="cgsr-detail-list-item"><span class="cgsr-detail-role">Member ' + (i + 1) + '</span>' +
                    '<div><span class="cgsr-detail-value">' + esc(m.name) + '</span>' +
                    (m.id ? '<span class="cgsr-meta-block">' + esc(m.id) + '</span>' : '') + '</div></div>';
            }).join('')
            : '<div class="cgsr-detail-list-item"><span class="cgsr-detail-role">Members</span><div><span class="cgsr-meta-block">No additional members recorded.</span></div></div>';

        modalBody.innerHTML =
            '<div class="cgsr-detail-head">' +
                '<span class="cgsr-detail-code">' + esc(r.group_number) + '</span>' +
                (r.group_name ? '<span class="cgsr-meta-block">' + esc(r.group_name) + '</span>' : '') +
                badge('Registered', 'cgsr-badge-registered') +
            '</div>' +

            '<div class="cgsr-detail-grid">' +
                '<div class="cgsr-detail-item"><span class="cgsr-detail-label">Research Title</span>' +
                    '<span class="cgsr-detail-value">' + esc(r.research_title) + '</span></div>' +
                '<div class="cgsr-detail-item"><span class="cgsr-detail-label">Proposal Ref</span>' +
                    '<span class="cgsr-detail-value">' + esc(r.proposal_number || '\u2014') + '</span></div>' +
                '<div class="cgsr-detail-item"><span class="cgsr-detail-label">Program / Department</span>' +
                    '<span class="cgsr-detail-value">' + esc(r.program) + '</span></div>' +
                '<div class="cgsr-detail-item"><span class="cgsr-detail-label">Academic Year</span>' +
                    '<span class="cgsr-detail-value">' + esc(r.academic_year) + '</span></div>' +
                '<div class="cgsr-detail-item"><span class="cgsr-detail-label">Adviser</span>' +
                    '<span class="cgsr-detail-value">' + esc(r.adviser) + '</span></div>' +
                '<div class="cgsr-detail-item"><span class="cgsr-detail-label">Research Coordinator</span>' +
                    '<span class="cgsr-detail-value">' + esc(r.coordinator) + '</span></div>' +
            '</div>' +

            '<div class="cgsr-detail-section"><h4><?= smsIcon('user-tie') ?> Leader</h4>' +
                '<div class="cgsr-detail-list"><div class="cgsr-detail-list-item"><span class="cgsr-detail-role">Leader</span>' +
                '<div><span class="cgsr-detail-value">' + esc((r.leader && r.leader.name) || '\u2014') + '</span>' +
                ((r.leader && r.leader.id) ? '<span class="cgsr-meta-block">' + esc(r.leader.id) + '</span>' : '') +
                '</div></div></div></div>' +

            '<div class="cgsr-detail-section"><h4><?= smsIcon('users') ?> Members</h4>' +
                '<div class="cgsr-detail-list">' + memberRows + '</div></div>';

        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    };

    const closeModal = function () {
        if (!modal) return;
        modal.hidden = true;
        document.body.style.overflow = '';
    };
    const fillFilter = function (select, values, allLabel) {
        if (!select) return;
        const current = select.value;
        const unique = [];
        values.forEach(function (v) {
            const s = String(v || '').trim();
            if (s !== '' && unique.indexOf(s) === -1) unique.push(s);
        });
        unique.sort();
        select.innerHTML = '<option value="">' + esc(allLabel) + '</option>' + unique.map(function (v) {
            return '<option value="' + esc(v) + '"' + (v === current ? ' selected' : '') + '>' + esc(v) + '</option>';
        }).join('');
        if (current && unique.indexOf(current) !== -1) {
            select.value = current;
        }
    };

    const refresh = async function () {
        if (refreshing) return;
        refreshing = true;
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax', 'cgsr-registry');
            url.searchParams.set('_', Date.now().toString());
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' }, cache: 'no-store', credentials: 'same-origin' });
            if (!res.ok) throw new Error('Sync failed');
            const data = await res.json();
            if (!data.ok) throw new Error('Sync failed');
            rows = Array.isArray(data.rows) ? data.rows : [];
            renderStats(data);
            fillFilter(ayFilter, rows.map(function (r) { return r.academic_year; }), 'All Academic Years');
            fillFilter(pgFilter, rows.map(function (r) { return r.program; }), 'All Programs');
            renderTable();
            if (sync) {
                const d = new Date();
                sync.textContent = 'Synced ' + d.toLocaleTimeString('en-US', { hour12: true });
            }
        } catch (error) {
            if (sync) sync.textContent = 'Sync paused';
        } finally {
            refreshing = false;
        }
    };

    if (search) search.addEventListener('input', renderTable);
    if (ayFilter) ayFilter.addEventListener('change', renderTable);
    if (pgFilter) pgFilter.addEventListener('change', renderTable);
    if (modal) {
        modal.querySelector('[data-cgsr-close]').addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) closeModal(); });
    }
    renderTable();
    refresh();

    let timer = window.setInterval(refresh, 2000);
    document.addEventListener('visibilitychange', function () {
        if (timer) window.clearInterval(timer);
        if (document.hidden) { timer = null; return; }
        refresh();
        timer = window.setInterval(refresh, 2000);
    });
});
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
