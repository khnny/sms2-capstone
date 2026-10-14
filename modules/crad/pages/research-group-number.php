<?php
/**
 * SMS 2 - Research Group Number
 * Module: CRAD
 * Generates research group numbers for registered proposals.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once __DIR__ . '/../includes/title-approval-assignees.php';

requireAuth();

$pageTitle    = 'Research Group Number';
$activeModule = 'crad';
$activePage   = 'research-group-number';
$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Research Group Number', 'url' => null],
];
$pageBannerIcon = 'fa-users';
$pageBannerDescription = 'Generate research group numbers only after a proposal has been registered.';

require_once __DIR__ . '/../../../includes/breadcrumbs.php';

function rgnEnsureSchema(PDO $pdo): void
{
    $proposalColumns = [
        'proposal_number' => "ALTER TABLE `crad_research_proposals` ADD proposal_number VARCHAR(30) NULL AFTER ref_code",
        'approved_at' => "ALTER TABLE `crad_research_proposals` ADD approved_at DATETIME NULL AFTER progress",
        'registered_at' => "ALTER TABLE `crad_research_proposals` ADD registered_at DATETIME NULL AFTER approved_at",
        'registration_status' => "ALTER TABLE `crad_research_proposals` ADD registration_status ENUM('Pending','Registered') NOT NULL DEFAULT 'Pending' AFTER registered_at",
    ];

    foreach ($proposalColumns as $column => $sql) {
        $exists = $pdo->query("SHOW COLUMNS FROM `crad_research_proposals` LIKE " . $pdo->quote($column))->fetch();
        if (!$exists) {
            $pdo->exec($sql);
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `crad_research_groups` (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            proposal_id     INT UNSIGNED DEFAULT NULL,
            proposal_number VARCHAR(30) DEFAULT NULL,
            group_number    VARCHAR(40) NOT NULL,
            group_name      VARCHAR(40) NOT NULL DEFAULT '',
            research_title  VARCHAR(255) NOT NULL DEFAULT '',
            college_dept    VARCHAR(120) NOT NULL DEFAULT '',
            adviser         VARCHAR(120) NOT NULL DEFAULT '',
            academic_year   VARCHAR(20) NOT NULL DEFAULT '',
            leader_name     VARCHAR(120) NOT NULL DEFAULT '',
            leader_id       VARCHAR(40) NOT NULL DEFAULT '',
            leader_email    VARCHAR(120) NOT NULL DEFAULT '',
            leader_contact  VARCHAR(40) NOT NULL DEFAULT '',
            status          VARCHAR(40) NOT NULL DEFAULT 'Approved',
            date_assigned   DATE NOT NULL,
            created_by      INT UNSIGNED DEFAULT NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $columns = [
        'proposal_id' => "ALTER TABLE `crad_research_groups` ADD proposal_id INT UNSIGNED DEFAULT NULL AFTER id",
        'title_approval_id' => "ALTER TABLE `crad_research_groups` ADD title_approval_id INT UNSIGNED DEFAULT NULL AFTER proposal_id",
        'proposal_number' => "ALTER TABLE `crad_research_groups` ADD proposal_number VARCHAR(30) DEFAULT NULL AFTER proposal_id",
        'group_name' => "ALTER TABLE `crad_research_groups` ADD group_name VARCHAR(40) NOT NULL DEFAULT '' AFTER group_number",
    ];

    foreach ($columns as $column => $sql) {
        $exists = $pdo->query("SHOW COLUMNS FROM `crad_research_groups` LIKE " . $pdo->quote($column))->fetch();
        if (!$exists) {
            $pdo->exec($sql);
        }
    }

    $indexes = [
        'group_number' => "ALTER TABLE `crad_research_groups` ADD UNIQUE KEY group_number (group_number)",
        'proposal_id' => "ALTER TABLE `crad_research_groups` ADD UNIQUE KEY proposal_id (proposal_id)",
        'title_approval_id' => "ALTER TABLE `crad_research_groups` ADD UNIQUE KEY title_approval_id (title_approval_id)",
        'idx_rg_proposal_number' => "ALTER TABLE `crad_research_groups` ADD KEY idx_rg_proposal_number (proposal_number)",
    ];

    foreach ($indexes as $name => $sql) {
        $exists = $pdo->query("SHOW INDEX FROM `crad_research_groups` WHERE Key_name = " . $pdo->quote($name))->fetch();
        if (!$exists) {
            $pdo->exec($sql);
        }
    }

    $relationship = cradEnsureTitleApprovalResearchGroupCascade($pdo);
    if (empty($relationship['ok'])) {
        throw new RuntimeException((string) $relationship['message']);
    }
}

function rgnEnsureTitleApprovalSchema(PDO $pdo): void
{
    $columns = [
        'proposal_number' => "ALTER TABLE `crad_title_approvals` ADD COLUMN proposal_number VARCHAR(30) DEFAULT NULL AFTER coordinator_name",
        'crad_status' => "ALTER TABLE `crad_title_approvals` ADD COLUMN crad_status VARCHAR(30) NOT NULL DEFAULT 'Not Ready' AFTER coordinator_reviewed_at",
        'crad_signature_data' => "ALTER TABLE `crad_title_approvals` ADD COLUMN crad_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER crad_status",
        'crad_reviewed_at' => "ALTER TABLE `crad_title_approvals` ADD COLUMN crad_reviewed_at DATETIME NULL DEFAULT NULL AFTER crad_signature_data",
    ];

    foreach ($columns as $column => $sql) {
        try {
            if (!$pdo->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE " . $pdo->quote($column))->fetch()) {
                $pdo->exec($sql);
            }
        } catch (Throwable $e) {
            error_log('Research group title approval schema failed: ' . $e->getMessage());
        }
    }
}

function rgnBuildGroupNumber(int $sequence): string
{
    return 'RG-' . date('Y') . '-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
}

function rgnBuildGroupName(int $sequence): string
{
    return 'Group ' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
}

function rgnBuildTitleApprovalProposalNumber(int $titleApprovalId): string
{
    return 'TAP-' . date('Y') . '-' . str_pad((string) $titleApprovalId, 5, '0', STR_PAD_LEFT);
}

function rgnTitleApprovalGroupRows(PDO $pdo): array
{
    rgnEnsureSchema($pdo);
    rgnEnsureTitleApprovalSchema($pdo);
    $stmt = $pdo->query(
        "SELECT t.id, t.proposal_number, t.proposed_title, t.student_name, t.student_id,
                t.department, t.adviser_name, t.crad_reviewed_at, t.crad_status,
                g.group_number, g.group_name, g.date_assigned
         FROM `crad_title_approvals` t
         LEFT JOIN `crad_research_groups` g ON g.title_approval_id = t.id
         WHERE t.status = 'Approved'
           AND t.coordinator_status = 'Approved'
           AND t.crad_status = 'Approved'
         ORDER BY
           CASE WHEN g.id IS NULL THEN 0 ELSE 1 END,
           t.crad_reviewed_at DESC,
           t.id DESC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rgnTitleApprovalGroupPayload(): array
{
    try {
        $pdo = getCradDatabaseConnection();
        $rows = rgnTitleApprovalGroupRows($pdo);
        return [
            'ok' => true,
            'rows' => $rows,
            'pending' => count(array_filter($rows, static fn($row) => empty($row['group_number']))),
            'last_sync' => date('M j, Y g:i:s A'),
        ];
    } catch (Throwable $e) {
        error_log('Research group title approval ajax failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Failed to load CRAD-approved title approvals.', 'rows' => [], 'pending' => 0, 'last_sync' => date('M j, Y g:i:s A')];
    }
}

$formError = '';
$formSuccess = '';

try {
    $cradPdo = getCradDatabaseConnection();
    rgnEnsureSchema($cradPdo);
    rgnEnsureTitleApprovalSchema($cradPdo);
} catch (Throwable $e) {
    error_log('CRAD research group setup error: ' . $e->getMessage());
    $formError = 'Failed to prepare research group database. (' . htmlspecialchars($e->getMessage()) . ')';
}

if (($_GET['ajax'] ?? '') === 'title-approval-groups') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(rgnTitleApprovalGroupPayload());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['process'] ?? '') === 'generate-title-approval-group')) {
    if (!csrfVerify()) {
        $formError = 'Security check failed. Please try again.';
    } else {
        $titleApprovalId = (int) ($_POST['title_approval_id'] ?? 0);

        try {
            $cradPdo = getCradDatabaseConnection();
            rgnEnsureSchema($cradPdo);
            rgnEnsureTitleApprovalSchema($cradPdo);
            $cradPdo->beginTransaction();

            $stmt = $cradPdo->prepare(
                "SELECT id, student_id, student_user_id, student_name, department,
                        proposed_title, adviser_name, proposal_number, crad_status
                 FROM `crad_title_approvals`
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([':id' => $titleApprovalId]);
            $approval = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$approval) {
                throw new RuntimeException('Title approval not found.');
            }
            if (($approval['crad_status'] ?? '') !== 'Approved') {
                throw new RuntimeException('Only CRAD-approved title approvals can receive a research group number.');
            }

            $proposalNumber = trim((string) ($approval['proposal_number'] ?? ''));
            if ($proposalNumber === '') {
                $proposalNumber = rgnBuildTitleApprovalProposalNumber((int) $approval['id']);
                $setProposal = $cradPdo->prepare(
                    "UPDATE `crad_title_approvals`
                     SET proposal_number = :proposal_number
                     WHERE id = :id
                     LIMIT 1"
                );
                $setProposal->execute([
                    ':proposal_number' => $proposalNumber,
                    ':id' => (int) $approval['id'],
                ]);
            }

            $existing = $cradPdo->prepare("SELECT group_number FROM `crad_research_groups` WHERE title_approval_id = :id LIMIT 1");
            $existing->execute([':id' => (int) $approval['id']]);
            $existingGroup = $existing->fetch(PDO::FETCH_ASSOC);

            if ($existingGroup) {
                $groupNumber = (string) $existingGroup['group_number'];
            } else {
                $lastRow = $cradPdo->query("SELECT MAX(id) AS max_id FROM `crad_research_groups`")->fetch();
                $seq = (int) ($lastRow['max_id'] ?? 0) + 1;
                $groupNumber = rgnBuildGroupNumber($seq);
                $groupName = rgnBuildGroupName($seq);

                $ins = $cradPdo->prepare(
                    "INSERT INTO `crad_research_groups`
                        (proposal_id, title_approval_id, proposal_number, group_number, group_name,
                         research_title, college_dept, adviser, academic_year,
                         leader_name, leader_id, leader_email, leader_contact,
                         status, date_assigned, created_by)
                     VALUES
                        (NULL, :title_approval_id, :proposal_number, :group_number, :group_name,
                         :research_title, :college_dept, :adviser, :academic_year,
                         :leader_name, :leader_id, '', '',
                         'Approved', :date_assigned, :created_by)"
                );
                $ins->execute([
                    ':title_approval_id' => (int) $approval['id'],
                    ':proposal_number' => $proposalNumber,
                    ':group_number' => $groupNumber,
                    ':group_name' => $groupName,
                    ':research_title' => $approval['proposed_title'],
                    ':college_dept' => $approval['department'],
                    ':adviser' => $approval['adviser_name'],
                    ':academic_year' => date('Y') . '-' . ((int) date('Y') + 1),
                    ':leader_name' => $approval['student_name'],
                    ':leader_id' => $approval['student_id'],
                    ':date_assigned' => date('Y-m-d'),
                    ':created_by' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
                ]);
            }

            $cradPdo->commit();

            try {
                cradEnsureAssigneeSchema($cradPdo);
                $officialId = (int) ($cradPdo->query(
                    'SELECT id FROM `crad_research_groups` WHERE title_approval_id = ' . (int) $approval['id'] . ' LIMIT 1'
                )->fetchColumn() ?: 0);
                cradMigrateStudentAssignmentsToOfficialGroup($cradPdo, (string) ($approval['student_id'] ?? ''), [
                    'id' => $officialId,
                    'group_number' => $groupNumber,
                    'title_approval_id' => (int) $approval['id'],
                ]);
            } catch (Throwable $e) {
                error_log('Title approval group assignment migrate skipped: ' . $e->getMessage());
            }

            if (function_exists('logActivity')) {
                logActivity('create', 'Generated title approval research group number: ' . $groupNumber, 'crad');
            }

            $formSuccess = 'Research group number <strong>' . htmlspecialchars($groupNumber) . '</strong> has been generated and sent to the student notification portal.';
        } catch (Throwable $e) {
            if (isset($cradPdo) && $cradPdo instanceof PDO && $cradPdo->inTransaction()) {
                $cradPdo->rollBack();
            }
            error_log('CRAD title approval group number error: ' . $e->getMessage());
            $formError = 'Failed to generate title approval group number. ' . htmlspecialchars($e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['process'] ?? '') === 'generate-research-group')) {
    if (!csrfVerify()) {
        $formError = 'Security check failed. Please try again.';
    } else {
        $proposalId = (int) ($_POST['proposal_id'] ?? 0);

        try {
            $cradPdo = getCradDatabaseConnection();
            rgnEnsureSchema($cradPdo);
            $cradPdo->beginTransaction();

            $stmt = $cradPdo->prepare(
                "SELECT id, proposal_number, research_title, college_department,
                        research_adviser, academic_year, rep_name, rep_id,
                        rep_email, rep_contact, registration_status
                 FROM `crad_research_proposals`
                 WHERE id = :id
                 LIMIT 1
                 FOR UPDATE"
            );
            $stmt->execute([':id' => $proposalId]);
            $proposal = $stmt->fetch();

            if (!$proposal) {
                throw new RuntimeException('Proposal not found.');
            }
            if (($proposal['registration_status'] ?? '') !== 'Registered' || empty($proposal['proposal_number'])) {
                throw new RuntimeException('Only registered proposals can receive a research group number.');
            }

            $existing = $cradPdo->prepare("SELECT group_number FROM `crad_research_groups` WHERE proposal_id = :id LIMIT 1");
            $existing->execute([':id' => $proposalId]);
            $existingGroup = $existing->fetch();

            if ($existingGroup) {
                $groupNumber = $existingGroup['group_number'];
            } else {
                $lastRow = $cradPdo->query("SELECT MAX(id) AS max_id FROM `crad_research_groups`")->fetch();
                $seq = (int) ($lastRow['max_id'] ?? 0) + 1;
                $groupNumber = rgnBuildGroupNumber($seq);
                $groupName = rgnBuildGroupName($seq);

                $ins = $cradPdo->prepare(
                    "INSERT INTO `crad_research_groups`
                        (proposal_id, proposal_number, group_number, group_name,
                         research_title, college_dept, adviser, academic_year,
                         leader_name, leader_id, leader_email, leader_contact,
                         status, date_assigned, created_by)
                     VALUES
                        (:proposal_id, :proposal_number, :group_number, :group_name,
                         :research_title, :college_dept, :adviser, :academic_year,
                         :leader_name, :leader_id, :leader_email, :leader_contact,
                         'Approved', :date_assigned, :created_by)"
                );
                $ins->execute([
                    ':proposal_id'     => $proposalId,
                    ':proposal_number' => $proposal['proposal_number'],
                    ':group_number'    => $groupNumber,
                    ':group_name'      => $groupName,
                    ':research_title'  => $proposal['research_title'],
                    ':college_dept'    => $proposal['college_department'],
                    ':adviser'         => $proposal['research_adviser'],
                    ':academic_year'   => $proposal['academic_year'],
                    ':leader_name'     => $proposal['rep_name'],
                    ':leader_id'       => $proposal['rep_id'],
                    ':leader_email'    => $proposal['rep_email'],
                    ':leader_contact'  => $proposal['rep_contact'],
                    ':date_assigned'   => date('Y-m-d'),
                    ':created_by'      => (int) ($_SESSION['user_id'] ?? 0) ?: null,
                ]);
            }

            $cradPdo->commit();

            try {
                cradEnsureAssigneeSchema($cradPdo);
                $officialId = (int) ($cradPdo->query(
                    'SELECT id FROM `crad_research_groups` WHERE proposal_id = ' . (int) $proposalId . ' LIMIT 1'
                )->fetchColumn() ?: 0);
                cradMigrateStudentAssignmentsToOfficialGroup($cradPdo, (string) ($proposal['rep_id'] ?? ''), [
                    'id' => $officialId,
                    'group_number' => $groupNumber,
                    'title_approval_id' => 0,
                ]);
            } catch (Throwable $e) {
                error_log('Proposal group assignment migrate skipped: ' . $e->getMessage());
            }

            if (function_exists('logActivity')) {
                logActivity('create', 'Generated research group number: ' . $groupNumber, 'crad');
            }

            $formSuccess = 'Research group number <strong>' . htmlspecialchars($groupNumber) . '</strong> has been linked to proposal <strong>' . htmlspecialchars($proposal['proposal_number']) . '</strong>.';
        } catch (Throwable $e) {
            if (isset($cradPdo) && $cradPdo instanceof PDO && $cradPdo->inTransaction()) {
                $cradPdo->rollBack();
            }
            error_log('CRAD generate research group error: ' . $e->getMessage());
            $formError = 'Failed to generate research group number. ' . htmlspecialchars($e->getMessage());
        }
    }
}

$registeredProposals = [];
$totalLinked = 0;
$totalPending = 0;
$approvedTitleApprovals = [];
$titleApprovalLinked = 0;
$titleApprovalPending = 0;
$rgnListView = strtolower(trim((string) ($_GET['view'] ?? 'title'))) === 'approved' ? 'approved' : 'title';

try {
    $cradPdo = getCradDatabaseConnection();
    rgnEnsureSchema($cradPdo);
    rgnEnsureTitleApprovalSchema($cradPdo);

    $stmt = $cradPdo->query(
        "SELECT p.id, p.proposal_number, p.research_title, p.rep_name,
                p.college_department, p.research_adviser, p.academic_year,
                p.registered_at, g.group_number, g.group_name, g.date_assigned
         FROM `crad_research_proposals` p
         LEFT JOIN `crad_research_groups` g ON g.proposal_id = p.id
         WHERE p.registration_status = 'Registered'
           AND p.proposal_number IS NOT NULL
         ORDER BY
           CASE WHEN g.id IS NULL THEN 0 ELSE 1 END,
           p.registered_at DESC,
           p.id DESC"
    );
    $registeredProposals = $stmt->fetchAll();
    $totalLinked = count(array_filter($registeredProposals, fn($p) => !empty($p['group_number'])));
    $totalPending = count($registeredProposals) - $totalLinked;

    $approvedTitleApprovals = rgnTitleApprovalGroupRows($cradPdo);
    $titleApprovalLinked = count(array_filter($approvedTitleApprovals, fn($p) => !empty($p['group_number'])));
    $titleApprovalPending = count($approvedTitleApprovals) - $titleApprovalLinked;
} catch (Throwable $e) {
    error_log('CRAD research group list error: ' . $e->getMessage());
    if ($formError === '') {
        $formError = 'Failed to load registered proposals. (' . htmlspecialchars($e->getMessage()) . ')';
    }
}

require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if ($formError !== ''): ?>
<div class="rgn-alert rgn-alert-danger" role="alert">
    <?= smsIcon('exclamation-circle') ?>
    <span><?= $formError ?></span>
</div>
<?php endif; ?>

<?php if ($formSuccess !== ''): ?>
<div class="rgn-alert rgn-alert-success" role="alert">
    <?= smsIcon('check-circle') ?>
    <span><?= $formSuccess ?></span>
</div>
<?php endif; ?>

<style>
.rgn-wrap { display: flex; flex-direction: column; gap: 1.25rem; }
.rgn-alert {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.85rem 1.1rem; margin-bottom: 1rem;
    border-radius: 12px; font-size: 0.88rem; font-weight: 600;
}
.rgn-alert-danger { border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; }
.rgn-alert-success { border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; }
.rgn-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.42rem;
    min-height: 38px; padding: 0.48rem 0.9rem;
    border: 1px solid transparent; border-radius: 10px;
    font-size: 0.82rem; font-weight: 800; text-decoration: none;
    cursor: pointer; white-space: nowrap;
}
.rgn-btn-ghost { color: #e0e7ff; background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.25); }
.rgn-btn-primary { color: #fff; background: #4f46e5; border-color: #4f46e5; box-shadow: 0 6px 16px rgba(79,70,229,0.28); }
.rgn-btn-done { color: #047857; background: #d1fae5; border-color: #a7f3d0; cursor: default; }
.rgn-stats { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 0.85rem; }
.rgn-stat {
    display: flex; align-items: center; gap: 0.85rem;
    padding: 0.95rem 1rem;
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 14px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.rgn-stat-icon {
    width: 42px; height: 42px; display: grid; place-items: center;
    border-radius: 12px; flex: 0 0 auto;
}
.rgn-stat-icon.blue { color: #2563eb; background: rgba(37,99,235,0.12); }
.rgn-stat-icon.green { color: #059669; background: rgba(16,185,129,0.12); }
.rgn-stat-icon.amber { color: #d97706; background: rgba(245,158,11,0.14); }
.rgn-stat strong { display: block; color: var(--sms-heading); font-size: 1.3rem; font-weight: 850; }
.rgn-stat span { color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; }
.rgn-card {
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 16px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-sm);
    overflow: hidden;
}
.rgn-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
}
.rgn-card-title { min-width: 0; }
.rgn-card-head h2 {
    margin: 0; color: var(--sms-text-muted);
    font-size: 0.78rem; font-weight: 800;
    letter-spacing: 0.07em; text-transform: uppercase;
}
.rgn-card-head span { color: var(--sms-text-muted); font-size: 0.78rem; font-weight: 700; }
.rgn-card-tools {
    align-items: center;
    background: var(--sms-surface-solid, #fff);
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    display: flex;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
}
.rgn-search {
    position: relative;
    flex: 1 1 auto;
    min-width: 0;
}
.rgn-search i {
    color: var(--sms-text-muted);
    left: .9rem;
    pointer-events: none;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
}
.rgn-search input {
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
.rgn-search input:focus {
    border-color: var(--sms-primary, #2454c6);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}
.rgn-filter {
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
.rgn-filter:focus {
    border-color: var(--sms-primary, #2454c6);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}
.rgn-table-wrap { overflow-x: auto; }
.rgn-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.rgn-table th,
.rgn-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    text-align: left; vertical-align: middle;
}
.rgn-table th {
    color: var(--sms-text-muted);
    background: var(--sms-surface-muted, #f8fafc);
    font-size: 0.72rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.rgn-title { color: var(--sms-heading); font-weight: 800; line-height: 1.35; }
.rgn-meta { display: block; margin-top: 0.2rem; color: var(--sms-text-muted); font-size: 0.75rem; font-weight: 600; }
.rgn-code {
    display: inline-flex; align-items: center; gap: 0.38rem;
    padding: 0.28rem 0.62rem;
    border-radius: 999px;
    color: #4338ca; background: rgba(99,102,241,0.12);
    font-size: 0.76rem; font-weight: 900; letter-spacing: 0.03em;
}
.rgn-link-box {
    display: grid; gap: 0.45rem;
    min-width: 170px;
}
.rgn-link-box span { color: var(--sms-text-muted); font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; }
.rgn-empty {
    padding: 2rem 1.25rem;
    text-align: center;
    color: var(--sms-text-muted);
    font-size: 0.9rem;
    font-weight: 700;
}
.rgn-empty[hidden] { display: none; }
[data-theme="dark"] .rgn-card,
[data-theme="dark"] .rgn-stat { background: rgba(15,23,42,0.72); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rgn-card-head,
[data-theme="dark"] .rgn-card-tools,
[data-theme="dark"] .rgn-search input,
[data-theme="dark"] .rgn-table th,
[data-theme="dark"] .rgn-table td { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rgn-card-tools,
[data-theme="dark"] .rgn-filter { background: rgba(15,23,42,0.72); }
[data-theme="dark"] .rgn-search input { background: rgba(148,163,184,0.06); }
[data-theme="dark"] .rgn-table th { background: rgba(148,163,184,0.06); }
@media (max-width: 767.98px) {
    .rgn-stats { grid-template-columns: 1fr; }
    .rgn-card-head,
    .rgn-card-tools { align-items: stretch; flex-direction: column; }
    .rgn-search { width: 100%; }
    .rgn-filter { flex-basis: auto; width: 100%; }
    .rgn-btn { width: 100%; }
}
</style>

<div class="rgn-wrap">
    <div class="rgn-stats">
        <div class="rgn-stat">
            <div class="rgn-stat-icon blue"><?= smsIcon('file-signature') ?></div>
            <div><strong><?= count($registeredProposals) ?></strong><span>Registered Proposals</span></div>
        </div>
        <div class="rgn-stat">
            <div class="rgn-stat-icon green"><?= smsIcon('link') ?></div>
            <div><strong><?= $totalLinked ?></strong><span>Linked Groups</span></div>
        </div>
        <div class="rgn-stat">
            <div class="rgn-stat-icon amber"><?= smsIcon('clock') ?></div>
            <div><strong><?= $totalPending ?></strong><span>Waiting</span></div>
        </div>
    </div>

    <?php if ($rgnListView === 'title'): ?>
    <section class="rgn-card"
             data-rgn-card
             data-rgn-title-card
             data-endpoint="<?= htmlspecialchars(BASE_URL . '/modules/crad/pages/research-group-number.php?ajax=title-approval-groups') ?>"
             data-csrf="<?= htmlspecialchars(csrfToken()) ?>">
        <div class="rgn-card-head">
            <div class="rgn-card-title">
                <h2><?= smsIcon('file-signature') ?> CRAD-Approved Title Approvals</h2>
                <span data-rgn-count><?= count($approvedTitleApprovals) ?> record<?= count($approvedTitleApprovals) === 1 ? '' : 's' ?> • <?= (int) $titleApprovalPending ?> waiting</span>
            </div>
            <div class="rgn-meta" data-rgn-title-sync>Synced <?= htmlspecialchars(date('M j, Y g:i:s A')) ?></div>
        </div>
        <div class="rgn-card-tools">
            <label class="rgn-search">
                <?= smsIcon('search') ?>
                <input type="search" data-rgn-search placeholder="Search by title, student, or group..." aria-label="Search CRAD-approved title approvals">
            </label>
            <select class="rgn-filter" data-rgn-status aria-label="Filter title approval group status">
                <option value="">All Status</option>
                <option value="waiting">Waiting</option>
                <option value="generated">Generated</option>
            </select>
        </div>

        <?php if (empty($approvedTitleApprovals)): ?>
            <div class="rgn-empty" data-rgn-empty>
                No CRAD-approved title approvals yet. Approved CRAD statuses will appear here automatically.
            </div>
        <?php else: ?>
            <div class="rgn-table-wrap">
                <table class="rgn-table">
                    <thead>
                        <tr>
                            <th>Title Approval</th>
                            <th>Student</th>
                            <th>CRAD Approved</th>
                            <th>Research Group Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody data-rgn-title-body>
                        <?php foreach ($approvedTitleApprovals as $approval): ?>
                            <?php
                                $hasGroup = !empty($approval['group_number']);
                            ?>
                            <tr data-rgn-row
                                data-status="<?= $hasGroup ? 'generated' : 'waiting' ?>"
                                data-search="<?= htmlspecialchars(strtolower(trim(($approval['proposed_title'] ?? '') . ' ' . ($approval['group_number'] ?? '') . ' ' . ($approval['group_name'] ?? '') . ' ' . ($approval['student_name'] ?? '') . ' ' . ($approval['student_id'] ?? '') . ' ' . ($approval['department'] ?? '') . ' ' . ($approval['adviser_name'] ?? '')))) ?>">
                                <td>
                                    <div class="rgn-title"><?= htmlspecialchars($approval['proposed_title']) ?></div>
                                    <span class="rgn-meta"><?= htmlspecialchars($approval['department']) ?> · <?= htmlspecialchars($approval['adviser_name']) ?></span>
                                </td>
                                <td>
                                    <?= htmlspecialchars($approval['student_name']) ?>
                                    <span class="rgn-meta"><?= htmlspecialchars($approval['student_id']) ?></span>
                                </td>
                                <td><?= !empty($approval['crad_reviewed_at']) ? htmlspecialchars(date('M j, Y', strtotime($approval['crad_reviewed_at']))) : 'Approved' ?></td>
                                <td>
                                    <div class="rgn-link-box">
                                        <?php if ($hasGroup): ?>
                                            <span>Research Group Number</span>
                                            <strong class="rgn-code"><?= htmlspecialchars($approval['group_number']) ?></strong>
                                            <span><?= htmlspecialchars($approval['group_name']) ?></span>
                                        <?php else: ?>
                                            <span>Research Group Number</span>
                                            <strong class="rgn-meta">Will be generated</strong>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($hasGroup): ?>
                                        <span class="rgn-btn rgn-btn-done"><?= smsIcon('check') ?> Generated</span>
                                    <?php else: ?>
                                        <form method="post" action="" style="margin:0;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="process" value="generate-title-approval-group">
                                            <input type="hidden" name="title_approval_id" value="<?= (int) $approval['id'] ?>">
                                            <button type="submit" class="rgn-btn rgn-btn-primary">
                                                <?= smsIcon('hashtag') ?> Generate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="rgn-empty" data-rgn-empty hidden>
                Walang record na tumugma sa search.
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($rgnListView === 'approved'): ?>
    <section class="rgn-card" data-rgn-card>
        <div class="rgn-card-head">
            <div class="rgn-card-title">
                <h2>Approved Proposals</h2>
                <span data-rgn-count><?= count($registeredProposals) ?> record<?= count($registeredProposals) === 1 ? '' : 's' ?></span>
            </div>
        </div>
        <div class="rgn-card-tools">
            <label class="rgn-search">
                <?= smsIcon('search') ?>
                <input type="search" data-rgn-search placeholder="Search by title, researcher, proposal, or group..." aria-label="Search registered proposals">
            </label>
            <select class="rgn-filter" data-rgn-status aria-label="Filter research group status">
                <option value="">All Status</option>
                <option value="waiting">Waiting</option>
                <option value="generated">Generated</option>
            </select>
        </div>

        <?php if (empty($registeredProposals)): ?>
            <div class="rgn-empty" data-rgn-empty>
                No registered proposals yet. A proposal must be approved and registered before a research group number can be generated.
            </div>
        <?php else: ?>
            <div class="rgn-table-wrap">
                <table class="rgn-table">
                    <thead>
                        <tr>
                            <th>Proposal</th>
                            <th>Researcher</th>
                            <th>Date Registered</th>
                            <th>Generated Link</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registeredProposals as $proposal): ?>
                            <?php $hasGroup = !empty($proposal['group_number']); ?>
                            <tr data-rgn-row
                                data-status="<?= $hasGroup ? 'generated' : 'waiting' ?>"
                                data-search="<?= htmlspecialchars(strtolower(trim(($proposal['research_title'] ?? '') . ' ' . ($proposal['proposal_number'] ?? '') . ' ' . ($proposal['group_number'] ?? '') . ' ' . ($proposal['group_name'] ?? '') . ' ' . ($proposal['rep_name'] ?? '') . ' ' . ($proposal['college_department'] ?? '') . ' ' . ($proposal['research_adviser'] ?? '')))) ?>">
                                <td>
                                    <div class="rgn-title"><?= htmlspecialchars($proposal['research_title']) ?></div>
                                    <span class="rgn-meta"><?= htmlspecialchars($proposal['college_department']) ?> · <?= htmlspecialchars($proposal['research_adviser']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($proposal['rep_name']) ?></td>
                                <td><?= !empty($proposal['registered_at']) ? htmlspecialchars(date('M j, Y', strtotime($proposal['registered_at']))) : 'Registered' ?></td>
                                <td>
                                    <div class="rgn-link-box">
                                        <span>Proposal Number</span>
                                        <strong class="rgn-code"><?= htmlspecialchars($proposal['proposal_number']) ?></strong>
                                        <?php if ($hasGroup): ?>
                                            <span>Research Group Number</span>
                                            <strong class="rgn-code"><?= htmlspecialchars($proposal['group_number']) ?></strong>
                                            <span><?= htmlspecialchars($proposal['group_name']) ?></span>
                                        <?php else: ?>
                                            <span>Research Group Number</span>
                                            <strong class="rgn-meta">Will be generated</strong>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($hasGroup): ?>
                                        <span class="rgn-btn rgn-btn-done"><?= smsIcon('check') ?> Generated</span>
                                    <?php else: ?>
                                        <form method="post" action="" style="margin:0;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="process" value="generate-research-group">
                                            <input type="hidden" name="proposal_id" value="<?= (int) $proposal['id'] ?>">
                                            <button type="submit" class="rgn-btn rgn-btn-primary">
                                                <?= smsIcon('hashtag') ?> Generate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="rgn-empty" data-rgn-empty hidden>
                Walang record na tumugma sa search.
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-rgn-card]').forEach(function (card) {
        const input = card.querySelector('[data-rgn-search]');
        const status = card.querySelector('[data-rgn-status]');
        const rows = Array.from(card.querySelectorAll('[data-rgn-row]'));
        const empty = card.querySelector('[data-rgn-empty]');
        const count = card.querySelector('[data-rgn-count]');
        const update = function () {
            const term = input ? input.value.trim().toLowerCase() : '';
            const statusValue = status ? status.value.trim().toLowerCase() : '';
            let visible = 0;
            rows.forEach(function (row) {
                const matchesSearch = term === '' || String(row.dataset.search || '').indexOf(term) !== -1;
                const matchesStatus = statusValue === '' || String(row.dataset.status || '') === statusValue;
                const match = matchesSearch && matchesStatus;
                row.hidden = !match;
                if (match) visible++;
            });
            if (empty && rows.length > 0) empty.hidden = visible !== 0;
            if (count) count.textContent = visible + ' record' + (visible === 1 ? '' : 's');
        };
        if (input) input.addEventListener('input', update);
        if (status) status.addEventListener('change', update);
        update();
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const card = document.querySelector('[data-rgn-title-card]');
    if (!card) return;

    const endpoint = card.dataset.endpoint;
    const csrf = card.dataset.csrf || '';
    const body = card.querySelector('[data-rgn-title-body]');
    const input = card.querySelector('[data-rgn-search]');
    const status = card.querySelector('[data-rgn-status]');
    const empty = card.querySelector('[data-rgn-empty]');
    const count = card.querySelector('[data-rgn-count]');
    const sync = card.querySelector('[data-rgn-title-sync]');

    const esc = function (value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };
    const fmtDate = function (value) {
        if (!value) return 'Approved';
        const parsed = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'});
    };
    const searchText = function (row) {
        return [
            row.proposed_title, row.group_number, row.group_name,
            row.student_name, row.student_id, row.department, row.adviser_name
        ].join(' ').toLowerCase();
    };
    const renderRows = function (rows) {
        if (!body) return;
        body.innerHTML = rows.map(function (row) {
            const hasGroup = !!row.group_number;
            return `<tr data-rgn-row data-status="${hasGroup ? 'generated' : 'waiting'}" data-search="${esc(searchText(row))}">
                <td>
                    <div class="rgn-title">${esc(row.proposed_title || '')}</div>
                    <span class="rgn-meta">${esc(row.department || '')} · ${esc(row.adviser_name || '')}</span>
                </td>
                <td>
                    ${esc(row.student_name || '')}
                    <span class="rgn-meta">${esc(row.student_id || '')}</span>
                </td>
                <td>${esc(fmtDate(row.crad_reviewed_at || ''))}</td>
                <td>
                    <div class="rgn-link-box">
                        ${hasGroup
                            ? `<span>Research Group Number</span><strong class="rgn-code">${esc(row.group_number)}</strong><span>${esc(row.group_name || '')}</span>`
                            : `<span>Research Group Number</span><strong class="rgn-meta">Will be generated</strong>`}
                    </div>
                </td>
                <td>
                    ${hasGroup
                        ? `<span class="rgn-btn rgn-btn-done"><?= smsIcon('check') ?> Generated</span>`
                        : `<form method="post" action="" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="${esc(csrf)}">
                            <input type="hidden" name="process" value="generate-title-approval-group">
                            <input type="hidden" name="title_approval_id" value="${Number(row.id) || 0}">
                            <button type="submit" class="rgn-btn rgn-btn-primary"><?= smsIcon('hashtag') ?> Generate</button>
                        </form>`}
                </td>
            </tr>`;
        }).join('');
        applyFilter();
        const pending = rows.filter(function (row) { return !row.group_number; }).length;
        if (count) count.textContent = rows.length + ' record' + (rows.length === 1 ? '' : 's') + ' • ' + pending + ' waiting';
    };
    const applyFilter = function () {
        const rows = Array.from(card.querySelectorAll('[data-rgn-row]'));
        const term = input ? input.value.trim().toLowerCase() : '';
        const statusValue = status ? status.value.trim().toLowerCase() : '';
        let visible = 0;
        rows.forEach(function (row) {
            const match = (term === '' || String(row.dataset.search || '').indexOf(term) !== -1)
                && (statusValue === '' || String(row.dataset.status || '') === statusValue);
            row.hidden = !match;
            if (match) visible++;
        });
        if (empty) empty.hidden = rows.length > 0 && visible > 0;
    };
    const refresh = async function () {
        try {
            const res = await fetch(endpoint, {headers: {'Accept': 'application/json'}, cache: 'no-store', credentials: 'same-origin'});
            const data = await res.json();
            if (!data.ok) return;
            renderRows(data.rows || []);
            if (sync) sync.textContent = 'Synced ' + (data.last_sync || '');
        } catch (error) {
            // Keep the current rows visible if a poll fails.
        }
    };

    if (input) input.addEventListener('input', applyFilter);
    if (status) status.addEventListener('change', applyFilter);
    window.setInterval(refresh, 5000);
});
</script>

<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
