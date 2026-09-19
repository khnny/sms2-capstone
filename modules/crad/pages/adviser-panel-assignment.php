<?php
/**
 * SMS 2 - Record Adviser Assignment
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

    try {
        $legacyUnique = $pdo->query("SHOW INDEX FROM research_defense_schedules WHERE Key_name = 'uniq_rds_group_number' AND Non_unique = 0")->fetch();
        if ($legacyUnique) {
            $pdo->exec('ALTER TABLE research_defense_schedules DROP INDEX uniq_rds_group_number');
        }
    } catch (Throwable $e) {
        error_log('CRAD schedule index cleanup failed: ' . $e->getMessage());
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

function cradRecordDefenseSchedule(PDO $pdo, string $groupNumber, string $proposalRef): ?array
{
    cradEnsureDefenseScheduleTable($pdo);
    cradPruneOrphanDefenseSchedules($pdo);

    $stmt = $pdo->prepare("
        SELECT
            g.id AS research_group_id,
            g.proposal_id,
            g.proposal_number,
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
            ) AS adviser_name,
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
    if (!$row || trim((string) ($row['group_number'] ?? '')) === '') {
        return null;
    }

    $panelMembers = trim((string) ($row['panel_members'] ?? ''));
    $panelChair = trim((string) (explode(',', $panelMembers)[0] ?? ''));
    $insert = $pdo->prepare("
        INSERT INTO research_defense_schedules
            (research_group_id, proposal_id, proposal_number, group_number, research_group, research_title,
             adviser_name, panel_members, panel_chair, defense_type, status, recorded_by, recorded_at, updated_at)
        VALUES
            (:research_group_id, :proposal_id, :proposal_number, :group_number, :research_group, :research_title,
            :adviser_name, :panel_members, :panel_chair, 'Pre-Oral', 'Ready for Scheduling', :recorded_by, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            research_group_id = VALUES(research_group_id),
            proposal_id = VALUES(proposal_id),
            proposal_number = VALUES(proposal_number),
            research_group = VALUES(research_group),
            research_title = VALUES(research_title),
            adviser_name = VALUES(adviser_name),
            panel_members = VALUES(panel_members),
            panel_chair = VALUES(panel_chair),
            defense_type = 'Pre-Oral',
            status = 'Ready for Scheduling',
            recorded_by = VALUES(recorded_by),
            updated_at = NOW()
    ");
    $insert->execute([
        ':research_group_id' => (int) ($row['research_group_id'] ?? 0) ?: null,
        ':proposal_id' => (int) ($row['proposal_id'] ?? 0) ?: null,
        ':proposal_number' => (string) ($row['proposal_number'] ?? ''),
        ':group_number' => (string) ($row['group_number'] ?? ''),
        ':research_group' => (string) ($row['research_group'] ?? 'Research Group'),
        ':research_title' => (string) ($row['research_title'] ?? ''),
        ':adviser_name' => (string) ($row['adviser_name'] ?? ''),
        ':panel_members' => $panelMembers,
        ':panel_chair' => $panelChair,
        ':recorded_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
    ]);

    return $row;
}

function cradOfficialAssignmentRecords(PDO $pdo): array
{
    try {
        cradEnsureDefenseScheduleTable($pdo);
        cradPruneOrphanDefenseSchedules($pdo);
        $stmt = $pdo->query("
            SELECT
                g.id AS research_group_id,
                g.group_number,
                g.proposal_number,
                g.proposal_id,
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
                '' AS panel_1,
                CASE WHEN rds.id IS NULL THEN 0 ELSE 1 END AS is_recorded,
                COALESCE((
                    SELECT MAX(a.updated_at)
                    FROM research_adviser_assignments a
                    WHERE a.assignment_status = 'Assigned'
                      AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
                ), '1000-01-01 00:00:00') AS updated_at
             FROM research_groups g
             JOIN research_proposals p ON p.id = g.proposal_id
             LEFT JOIN research_defense_schedules rds ON rds.group_number = g.group_number
             WHERE EXISTS (
                SELECT 1
                FROM research_adviser_assignments a
                WHERE a.assignment_status = 'Assigned'
                  AND (a.research_group_id = g.id OR a.group_number = g.group_number OR a.proposal_id = g.proposal_id)
             )
             ORDER BY updated_at DESC, g.id DESC
        ");
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Official adviser record load failed: ' . $e->getMessage());
        return [];
    }
}

function cradOfficialAssignmentStats(array $records): array
{
    $withAdviser = 0;

    foreach ($records as $record) {
        if (trim((string) ($record['adviser'] ?? '')) !== '') {
            $withAdviser++;
        }
    }

    return [
        'total' => count($records),
        'adviser_assigned' => $withAdviser,
        'panel_assigned' => 0,
        'official_records' => count($records),
    ];
}

if (($_GET['record_defense'] ?? '') === '1') {
    requireAuth();
    $pdo = cradDb();
    $groupNumber = trim((string) ($_GET['group'] ?? ''));
    $proposalRef = trim((string) ($_GET['proposal'] ?? ''));
    if ($pdo instanceof PDO) {
        cradRecordDefenseSchedule($pdo, $groupNumber, $proposalRef);
    }
    $redirect = BASE_URL . '/modules/crad/pages/research-defense-scheduling.php?' . http_build_query([
        'group' => $groupNumber,
        'proposal' => $proposalRef,
        'from' => 'assignment-record',
        'recorded' => '1',
    ]);
    header('Location: ' . $redirect);
    exit;
}

if (($_GET['ajax'] ?? '') === 'official-records') {
    requireAuth();
    header('Content-Type: application/json; charset=utf-8');
    $pdo = cradDb();
    $records = $pdo instanceof PDO ? cradOfficialAssignmentRecords($pdo) : [];
    echo json_encode([
        'ok' => true,
        'rows' => $records,
        'stats' => cradOfficialAssignmentStats($records),
        'synced_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('M j, Y h:i:s A'),
    ]);
    exit;
}

$pageTitle    = 'Record Adviser Assignment';
$activeModule = 'crad';
$activePage   = 'adviser-panel-assignment';

$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Record Adviser Assignment', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
$officialAssignmentPdo = cradDb();
$officialAssignmentRecords = $officialAssignmentPdo instanceof PDO
    ? cradOfficialAssignmentRecords($officialAssignmentPdo)
    : [];
$officialAssignmentStats = cradOfficialAssignmentStats($officialAssignmentRecords);
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<style>
    .record-assignment-page-head {
        margin: 1rem 0;
    }
    .record-assignment-page-head h1 {
        color: var(--sms-heading, #0f172a);
        font-size: 1.35rem;
        font-weight: 800;
        margin: 0;
    }
    .record-assignment-page-head p {
        color: var(--sms-text-muted, #64748b);
        margin: .35rem 0 0;
        max-width: 780px;
    }
    .record-assignment-stats {
        display: grid;
        gap: .85rem;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin: 1rem 0;
    }
    .record-assignment-stat {
        align-items: center;
        background: var(--sms-card-bg, #fff);
        border: 1px solid var(--sms-border, #e2e8f0);
        border-radius: 14px;
        box-shadow: var(--sms-shadow-xs, 0 4px 12px rgba(15, 23, 42, .06));
        display: flex;
        gap: .85rem;
        padding: .95rem 1rem;
    }
    .record-assignment-stat__icon {
        align-items: center;
        border-radius: 12px;
        display: inline-flex;
        flex: 0 0 42px;
        height: 42px;
        justify-content: center;
        width: 42px;
    }
    .record-assignment-stat__icon i {
        font-size: 1rem;
    }
    .record-assignment-stat__icon--blue {
        background: #dbeafe;
        color: #2563eb;
    }
    .record-assignment-stat__icon--amber {
        background: #ffedd5;
        color: #d97706;
    }
    .record-assignment-stat__icon--purple {
        background: #ede9fe;
        color: #7c3aed;
    }
    .record-assignment-stat__icon--green {
        background: #d1fae5;
        color: #059669;
    }
    .record-assignment-stat small {
        color: var(--sms-text-muted, #64748b);
        display: block;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }
    .record-assignment-stat strong {
        color: var(--sms-heading, #0f172a);
        display: block;
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1.1;
        margin-top: .15rem;
    }
    .record-assignment-tracking {
        background: var(--sms-card-bg, #fff);
        border: 1px solid var(--sms-border, #e2e8f0);
        border-radius: 16px;
        box-shadow: var(--sms-shadow-sm, 0 8px 20px rgba(15, 23, 42, .06));
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .record-assignment-tracking__title {
        border-bottom: 1px solid var(--sms-border, #e2e8f0);
        color: var(--sms-text-muted, #64748b);
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .08em;
        padding: 1rem 1.25rem;
        text-transform: uppercase;
    }
    .record-assignment-tracking__controls {
        align-items: center;
        background: var(--sms-surface-muted, #f8fafc);
        border-bottom: 1px solid var(--sms-border, #e2e8f0);
        display: flex;
        flex-wrap: wrap;
        gap: .65rem;
        padding: .85rem 1.25rem;
    }
    .record-assignment-search {
        align-items: center;
        background: var(--sms-input-bg, #fff);
        border: 1px solid var(--sms-border, #d7e1ef);
        border-radius: 10px;
        display: flex;
        flex: 1 1 200px;
        gap: .5rem;
        min-height: 38px;
        padding: .4rem .75rem;
    }
    .record-assignment-search i {
        color: var(--sms-text-muted, #64748b);
    }
    .record-assignment-search input {
        background: transparent;
        border: 0;
        color: var(--sms-text, #334155);
        font-size: .84rem;
        min-width: 0;
        outline: 0;
        width: 100%;
    }
    .record-assignment-filter {
        background: var(--sms-input-bg, #fff);
        border: 1px solid var(--sms-border, #d7e1ef);
        border-radius: 10px;
        color: var(--sms-text, #334155);
        font-size: .84rem;
        min-height: 38px;
        outline: none;
        padding: .4rem .75rem;
    }
    .official-assignment-record {
        background: var(--sms-card-bg, #fff);
        border: 1px solid var(--sms-border, #dbe4f0);
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .07);
        margin-top: 1rem;
        overflow: hidden;
    }
    .official-assignment-record__head {
        align-items: center;
        border-bottom: 1px solid var(--sms-border, #dbe4f0);
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 1rem 1.15rem;
    }
    .official-assignment-record__head h2 {
        color: var(--sms-heading, #0f172a);
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
    }
    .official-assignment-record__head p {
        color: var(--sms-text-muted, #64748b);
        font-size: .86rem;
        margin: .2rem 0 0;
    }
    .official-assignment-record__sync {
        color: var(--sms-text-muted, #64748b);
        flex: 0 0 auto;
        font-size: .78rem;
        font-weight: 700;
    }
    .official-assignment-record table {
        margin: 0;
    }
    .official-assignment-record th {
        color: var(--sms-text-muted, #64748b);
        font-size: .76rem;
        font-weight: 800;
        text-transform: uppercase;
    }
    .official-assignment-record td {
        color: var(--sms-text, #334155);
        font-size: .9rem;
        vertical-align: middle;
    }
    .official-assignment-record strong {
        color: var(--sms-heading, #0f172a);
        display: block;
        font-weight: 800;
    }
    .official-assignment-record small {
        color: var(--sms-text-muted, #64748b);
        display: block;
        margin-top: .15rem;
    }
    .official-assignment-record__empty {
        color: var(--sms-text-muted, #64748b);
        padding: 1.2rem;
        text-align: center;
    }
    .official-assignment-record__action {
        align-items: center;
        background: var(--sms-primary, #2454c6);
        border: 1px solid var(--sms-primary, #2454c6);
        border-radius: 9px;
        color: #fff;
        display: inline-flex;
        font-size: .82rem;
        font-weight: 800;
        gap: .4rem;
        min-height: 36px;
        padding: .45rem .75rem;
        text-decoration: none;
        white-space: nowrap;
    }
    .official-assignment-record__action:hover {
        background: #1e40af;
        border-color: #1e40af;
        color: #fff;
    }
    .official-assignment-record__action.is-recorded {
        background: #16834c;
        border-color: #16834c;
    }
    .official-assignment-record__action.is-recorded:hover {
        background: #0f6b3c;
        border-color: #0f6b3c;
    }
    @media (max-width: 1100px) {
        .record-assignment-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 720px) {
        .record-assignment-stats,
        .record-assignment-tracking__controls {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="record-assignment-page-head">
    <h1>Record Adviser Assignment</h1>
</div>

<div class="record-assignment-stats" data-official-assignment-stats>
    <section class="record-assignment-stat">
        <span class="record-assignment-stat__icon record-assignment-stat__icon--blue">
            <?= smsIcon('layer-group', ['aria-hidden' => 'true']) ?>
        </span>
        <div>
            <small>Total Records</small>
            <strong data-official-stat="total"><?= (int) $officialAssignmentStats['total'] ?></strong>
        </div>
    </section>
    <section class="record-assignment-stat">
        <span class="record-assignment-stat__icon record-assignment-stat__icon--amber">
            <?= smsIcon('user-check', ['aria-hidden' => 'true']) ?>
        </span>
        <div>
            <small>Adviser Assigned</small>
            <strong data-official-stat="adviser_assigned"><?= (int) $officialAssignmentStats['adviser_assigned'] ?></strong>
        </div>
    </section>
    <section class="record-assignment-stat">
        <span class="record-assignment-stat__icon record-assignment-stat__icon--purple">
            <?= smsIcon('users', ['aria-hidden' => 'true']) ?>
        </span>
        <div>
            <small>Panel Removed</small>
            <strong data-official-stat="panel_assigned"><?= (int) $officialAssignmentStats['panel_assigned'] ?></strong>
        </div>
    </section>
    <section class="record-assignment-stat">
        <span class="record-assignment-stat__icon record-assignment-stat__icon--green">
            <?= smsIcon('check-circle', ['aria-hidden' => 'true']) ?>
        </span>
        <div>
            <small>Official Records</small>
            <strong data-official-stat="official_records"><?= (int) $officialAssignmentStats['official_records'] ?></strong>
        </div>
    </section>
</div>

<section class="record-assignment-tracking">
    <div class="record-assignment-tracking__title">Assignment Record Tracking</div>
    <div class="record-assignment-tracking__controls">
        <label class="record-assignment-search">
            <?= smsIcon('search', ['aria-hidden' => 'true']) ?>
            <input type="search" data-official-assignment-search placeholder="Search by research group, title, or adviser...">
        </label>
        <select class="record-assignment-filter" data-official-assignment-status>
            <option value="all">All Status</option>
            <option value="official">Official Record</option>
        </select>
    </div>
</section>

<section class="official-assignment-record" data-official-assignment-records data-endpoint="<?= htmlspecialchars(BASE_URL . '/modules/crad/pages/adviser-panel-assignment.php?ajax=official-records') ?>">
    <div class="official-assignment-record__head">
        <div>
            <h2>Official Adviser Assignment Record</h2>
        </div>
        <span class="official-assignment-record__sync" data-official-assignment-sync>Syncing...</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Research Group</th>
                    <th>Adviser</th>
                    <th>Panel</th>
                    <th>Record</th>
                </tr>
            </thead>
            <tbody data-official-assignment-rows>
                <?php foreach ($officialAssignmentRecords as $record): ?>
                    <?php
                        $recordUrl = BASE_URL . '/modules/crad/pages/adviser-panel-assignment.php?' . http_build_query([
                            'record_defense' => '1',
                            'group' => (string) ($record['group_number'] ?? ''),
                            'proposal' => (string) (($record['proposal_number'] ?? '') ?: ($record['proposal_id'] ?? '')),
                        ]);
                        $isRecorded = (int) ($record['is_recorded'] ?? 0) === 1;
                    ?>
                    <tr data-group-number="<?= htmlspecialchars((string) ($record['group_number'] ?? '')) ?>"
                        data-proposal-number="<?= htmlspecialchars((string) ($record['proposal_number'] ?? '')) ?>"
                        data-proposal-id="<?= htmlspecialchars((string) ($record['proposal_id'] ?? '')) ?>"
                        data-recorded="<?= $isRecorded ? '1' : '0' ?>">
                        <td>
                            <strong><?= htmlspecialchars((string) ($record['research_group'] ?? 'Research Group')) ?></strong>
                            <?php if (!empty($record['research_title'])): ?>
                                <small><?= htmlspecialchars((string) $record['research_title']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string) ($record['adviser'] ?: 'For assignment')) ?></td>
                        <td>Not required</td>
                        <td>
                            <a class="official-assignment-record__action <?= $isRecorded ? 'is-recorded' : '' ?>" href="<?= htmlspecialchars($recordUrl) ?>">
                                <?= smsIcon($isRecorded ? 'check-circle' : 'calendar-check') ?>
                                Record
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="official-assignment-record__empty" data-official-assignment-empty <?= $officialAssignmentRecords ? 'hidden' : '' ?>>
        No completed adviser assignment record yet.
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-official-assignment-records]');
    if (!root) return;

    const endpoint = root.dataset.endpoint;
    const rowsBody = root.querySelector('[data-official-assignment-rows]');
    const empty = root.querySelector('[data-official-assignment-empty]');
    const sync = root.querySelector('[data-official-assignment-sync]');
    const search = document.querySelector('[data-official-assignment-search]');
    const status = document.querySelector('[data-official-assignment-status]');
    const statNodes = document.querySelectorAll('[data-official-stat]');
    let allRows = [];
    let isRefreshing = false;
    let refreshTimer = null;

    const esc = function (value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    };
    const recordUrl = function (row) {
        const url = new URL('<?= BASE_URL ?>/modules/crad/pages/adviser-panel-assignment.php', window.location.href);
        url.searchParams.set('record_defense', '1');
        url.searchParams.set('group', String(row.group_number || ''));
        url.searchParams.set('proposal', String(row.proposal_number || row.proposal_id || ''));
        return url.toString();
    };

    const renderStats = function (stats) {
        statNodes.forEach(function (node) {
            const key = node.dataset.officialStat;
            node.textContent = stats && Object.prototype.hasOwnProperty.call(stats, key)
                ? String(stats[key])
                : '0';
        });
    };

    const computeStats = function (rows) {
        const list = Array.isArray(rows) ? rows : [];
        return {
            total: list.length,
            adviser_assigned: list.filter(function (row) { return String(row.adviser || '').trim() !== ''; }).length,
            panel_assigned: 0,
            official_records: list.length
        };
    };

    const filteredRows = function () {
        const term = search ? search.value.trim().toLowerCase() : '';
        const selectedStatus = status ? status.value : 'all';

        return allRows.filter(function (row) {
            const rowStatus = String(row.status || 'official').toLowerCase();
            const haystack = [
                row.research_group,
                row.group_number,
                row.proposal_number,
                row.research_title,
                row.adviser,
                row.panel_1,
                rowStatus
            ].join(' ').toLowerCase();
            const matchesSearch = term === '' || haystack.indexOf(term) !== -1;
            const matchesStatus = selectedStatus === 'all' || selectedStatus === rowStatus;
            return matchesSearch && matchesStatus;
        });
    };

    const renderRows = function () {
        const list = filteredRows();
        rowsBody.innerHTML = list.map(function (row) {
            const isRecorded = Number(row.is_recorded || 0) === 1;
            return '<tr>' +
                '<td><strong>' + esc(row.research_group || 'Research Group') + '</strong>' +
                    (row.research_title ? '<small>' + esc(row.research_title) + '</small>' : '') +
                '</td>' +
                '<td>' + esc(row.adviser || 'For assignment') + '</td>' +
                '<td>Not required</td>' +
                '<td><a class="official-assignment-record__action ' + (isRecorded ? 'is-recorded' : '') + '" href="' + esc(recordUrl(row)) + '">' +
                    (window.smsIconHtml ? window.smsIconHtml(isRecorded ? 'check-circle' : 'calendar-check') : '') + ' Record' +
                '</a></td>' +
            '</tr>';
        }).join('');
        if (empty) {
            empty.hidden = list.length !== 0;
            empty.textContent = allRows.length === 0
                ? 'No completed adviser assignment record yet.'
                : 'No records match the search or filter.';
        }
    };

    const refresh = async function () {
        if (isRefreshing) return;
        isRefreshing = true;
        try {
            const url = new URL(endpoint, window.location.href);
            url.searchParams.set('_', Date.now().toString());
            const res = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            if (!res.ok) {
                throw new Error('Request failed');
            }
            const data = await res.json();
            if (!data.ok) return;
            allRows = (Array.isArray(data.rows) ? data.rows : []).map(function (row) {
                return Object.assign({ status: 'official' }, row);
            });
            renderStats(data.stats || computeStats(allRows));
            renderRows();
            if (sync) sync.textContent = 'Synced ' + (data.synced_at || 'just now');
        } catch (error) {
            if (sync) sync.textContent = 'Sync paused';
        } finally {
            isRefreshing = false;
        }
    };

    allRows = Array.from(rowsBody.querySelectorAll('tr')).map(function (row) {
        const cells = row.querySelectorAll('td');
        return {
            research_group: cells[0] ? (cells[0].querySelector('strong')?.textContent || cells[0].textContent || '').trim() : '',
            research_title: cells[0] ? (cells[0].querySelector('small')?.textContent || '').trim() : '',
            adviser: cells[1] ? cells[1].textContent.trim() : '',
            panel_1: cells[2] ? cells[2].textContent.trim() : '',
            group_number: row.dataset.groupNumber || '',
            proposal_number: row.dataset.proposalNumber || '',
            proposal_id: row.dataset.proposalId || '',
            is_recorded: row.dataset.recorded === '1' ? 1 : 0,
            status: 'official'
        };
    });

    if (search) search.addEventListener('input', renderRows);
    if (status) status.addEventListener('change', renderRows);

    refresh();
    refreshTimer = window.setInterval(refresh, 5000);
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            if (refreshTimer) window.clearInterval(refreshTimer);
            refreshTimer = null;
            return;
        }
        if (refreshTimer) window.clearInterval(refreshTimer);
        refresh();
        refreshTimer = window.setInterval(refresh, 5000);
    });
});
</script>

<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
