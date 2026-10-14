<?php
/**
 * SMS 2 - Register Proposal
 * Module: CRAD
 * Lists approved research proposals and registers them with an official number.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';

requireAuth();

$pageTitle    = 'Register Proposal';
$activeModule = 'crad';
$activePage   = 'register-proposal';
$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Register Proposal', 'url' => null],
];
$pageBannerIcon = 'fa-file-signature';
$pageBannerDescription = 'Approved proposals appear here first. Click Register to generate the official proposal number.';

require_once __DIR__ . '/../../../includes/breadcrumbs.php';

function rpEnsureTitleApprovalColumns(PDO $pdo): void
{
    $columns = [
        'adviser_signature_data' => "ALTER TABLE `crad_title_approvals` ADD COLUMN adviser_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER adviser_remarks",
        'coordinator_status' => "ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_status VARCHAR(30) NOT NULL DEFAULT 'Not Ready' AFTER adviser_signature_data",
        'coordinator_remarks' => "ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_remarks TEXT NULL DEFAULT NULL AFTER coordinator_status",
        'coordinator_screening_json' => "ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_screening_json TEXT NULL DEFAULT NULL AFTER coordinator_remarks",
        'coordinator_signature_data' => "ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER coordinator_remarks",
        'coordinator_reviewed_at' => "ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_reviewed_at DATETIME NULL DEFAULT NULL AFTER coordinator_signature_data",
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
            error_log('CRAD officer title approval schema failed: ' . $e->getMessage());
        }
    }
}

function rpTitleApprovalRows(PDO $pdo): array
{
    rpEnsureTitleApprovalColumns($pdo);
    $stmt = $pdo->query(
        "SELECT id, student_id, student_name, submission_date, department,
                proposed_title, discipline_cluster, primary_sdg, research_agenda,
                sdg_justification, members_json, adviser_name, adviser_email,
                coordinator_name, status, adviser_remarks, adviser_signature_data,
                coordinator_status, coordinator_remarks, coordinator_screening_json, coordinator_signature_data,
                coordinator_reviewed_at, crad_status, crad_signature_data,
                crad_reviewed_at, sent_at, reviewed_at
         FROM `crad_title_approvals`
         WHERE status = 'Approved'
           AND coordinator_status = 'Approved'
         ORDER BY FIELD(crad_status, 'Pending', 'Approved', 'Rejected', 'Not Ready'),
                  coordinator_reviewed_at DESC, id DESC"
    );
    return $stmt->fetchAll() ?: [];
}

function rpTitleApprovalPayload(): array
{
    try {
        $pdo = getCradDatabaseConnection();
        $rows = rpTitleApprovalRows($pdo);
        return [
            'ok' => true,
            'rows' => $rows,
            'pending' => count(array_filter($rows, static fn($row) => (string) ($row['crad_status'] ?? '') === 'Pending')),
            'last_sync' => date('M j, Y g:i:s A'),
        ];
    } catch (Throwable $e) {
        error_log('CRAD officer title approval load failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Failed to load title approvals.', 'rows' => [], 'pending' => 0, 'last_sync' => date('M j, Y g:i:s A')];
    }
}

function rpTitleApprovalApprove(int $id, string $signature): bool
{
    if ($id <= 0 || $signature === '' || !str_starts_with($signature, 'data:image/png;base64,')) {
        return false;
    }

    $pdo = getCradDatabaseConnection();
    rpEnsureTitleApprovalColumns($pdo);
    $stmt = $pdo->prepare(
        "UPDATE `crad_title_approvals`
         SET crad_status = 'Approved',
             crad_signature_data = :signature,
             crad_reviewed_at = NOW()
         WHERE id = :id
           AND status = 'Approved'
           AND coordinator_status = 'Approved'
           AND crad_status = 'Pending'
         LIMIT 1"
    );
    $stmt->execute([
        ':signature' => $signature,
        ':id' => $id,
    ]);

    return $stmt->rowCount() > 0;
}

function rpTitleApprovalReject(int $id): bool
{
    if ($id <= 0) {
        return false;
    }

    $pdo = getCradDatabaseConnection();
    rpEnsureTitleApprovalColumns($pdo);
    $stmt = $pdo->prepare(
        "UPDATE `crad_title_approvals`
         SET crad_status = 'Rejected',
             crad_signature_data = NULL,
             crad_reviewed_at = NOW()
         WHERE id = :id
           AND status = 'Approved'
           AND coordinator_status = 'Approved'
           AND crad_status = 'Pending'
         LIMIT 1"
    );
    $stmt->execute([':id' => $id]);

    return $stmt->rowCount() > 0;
}

$formError = '';
$formSuccess = '';

try {
    $cradPdo = getCradDatabaseConnection();
    rpEnsureTitleApprovalColumns($cradPdo);
} catch (Throwable $e) {
    error_log('CRAD register setup error: ' . $e->getMessage());
    $formError = 'Failed to prepare proposal registration database. Please check  (' . htmlspecialchars($e->getMessage()) . ')';
}

if (($_GET['ajax'] ?? '') === 'title-approvals') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(rpTitleApprovalPayload());
    exit;
}

if (($_GET['ajax'] ?? '') === 'title-approval-approve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $id = (int) ($body['id'] ?? 0);
        $ok = ($body['action'] ?? '') === 'reject'
            ? rpTitleApprovalReject($id)
            : rpTitleApprovalApprove($id, trim((string) ($body['crad_signature_data'] ?? '')));
        echo json_encode(['ok' => $ok]);
    } catch (Throwable $e) {
        error_log('CRAD officer title approval update failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Failed to save CRAD officer signature.']);
    }
    exit;
}

$titleApprovalPayload = rpTitleApprovalPayload();
$titleApprovalRows = $titleApprovalPayload['rows'];
$approvedProposals = [];
$totalRegistered = 0;
$totalPendingRegistration = 0;
if ($cradPdo instanceof PDO) {
    try {
        $proposalStmt = $cradPdo->query(
            "SELECT id, proposal_number, research_title, rep_name, college_department,
                    registered_at, approved_at AS approved_on, registration_status, ref_code
             FROM `crad_research_proposals`
             WHERE status = 'Approved'
             ORDER BY approved_on DESC, id DESC"
        );
        $approvedProposals = $proposalStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $totalRegistered = count(array_filter(
            $approvedProposals,
            static fn(array $proposal): bool => (string) ($proposal['registration_status'] ?? '') === 'Registered'
        ));
        $totalPendingRegistration = count($approvedProposals) - $totalRegistered;
    } catch (Throwable $e) {
        error_log('CRAD approved proposal load failed: ' . $e->getMessage());
    }
}
$rpListView = 'title';

require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if ($formError !== ''): ?>
<div class="rp-alert rp-alert-danger" role="alert">
    <?= smsIcon('exclamation-circle') ?>
    <span><?= $formError ?></span>
</div>
<?php endif; ?>

<?php if ($formSuccess !== ''): ?>
<div class="rp-alert rp-alert-success" role="alert">
    <?= smsIcon('check-circle') ?>
    <span><?= $formSuccess ?></span>
</div>
<?php endif; ?>

<style>
.rp-wrap { display: flex; flex-direction: column; gap: 1.25rem; }
.rp-alert {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.85rem 1.1rem; margin-bottom: 1rem;
    border-radius: 12px; font-size: 0.88rem; font-weight: 600;
}
.rp-alert-danger { border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; }
.rp-alert-success { border: 1px solid #bbf7d0; background: #f0fdf4; color: #166534; }
.rp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    min-height: 38px; padding: 0.48rem 0.9rem; border-radius: 10px;
    border: 1px solid transparent; font-size: 0.82rem; font-weight: 800;
    text-decoration: none; cursor: pointer; transition: all 0.15s ease; white-space: nowrap;
}
.rp-btn-ghost { color: #e0e7ff; background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.25); }
.rp-btn-ghost:hover { background: rgba(255,255,255,0.18); color: #fff; }
.rp-btn-primary { color: #fff; background: #2563eb; border-color: #2563eb; box-shadow: 0 6px 16px rgba(37,99,235,0.28); }
.rp-btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
.rp-btn-done { color: #047857; background: #d1fae5; border-color: #a7f3d0; cursor: default; }
.rp-btn-danger { color: #fff; background: #dc2626; border-color: #dc2626; }
.rp-btn-danger:hover { background: #b91c1c; border-color: #b91c1c; color: #fff; }
.rp-stats { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 0.85rem; }
.rp-stat {
    display: flex; align-items: center; gap: 0.85rem;
    padding: 0.95rem 1rem;
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 14px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.rp-stat-icon {
    width: 42px; height: 42px; display: grid; place-items: center;
    border-radius: 12px; flex: 0 0 auto;
}
.rp-stat-icon.blue { color: #2563eb; background: rgba(37,99,235,0.12); }
.rp-stat-icon.green { color: #059669; background: rgba(16,185,129,0.12); }
.rp-stat-icon.amber { color: #d97706; background: rgba(245,158,11,0.14); }
.rp-stat strong { display: block; color: var(--sms-heading); font-size: 1.3rem; font-weight: 850; }
.rp-stat span { color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; }
.rp-card {
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 16px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-sm);
    overflow: hidden;
}
.rp-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 0.9rem 1.25rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
}
.rp-card-title { min-width: 0; }
.rp-card-head h2 {
    margin: 0; font-size: 0.78rem; font-weight: 800;
    letter-spacing: 0.07em; text-transform: uppercase;
    color: var(--sms-text-muted);
}
.rp-card-head span { color: var(--sms-text-muted); font-size: 0.78rem; font-weight: 700; }
.rp-card-tools {
    align-items: center;
    background: var(--sms-surface-solid, #fff);
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    display: flex;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
}
.rp-search {
    position: relative;
    flex: 1 1 auto;
    min-width: 0;
}
.rp-search i {
    color: var(--sms-text-muted);
    left: .9rem;
    pointer-events: none;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
}
.rp-search input {
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
.rp-search input:focus {
    border-color: var(--sms-primary, #2454c6);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}
.rp-filter {
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
.rp-filter:focus {
    border-color: var(--sms-primary, #2454c6);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
}
.rp-table-wrap { overflow-x: auto; }
.rp-table { width: 100%; border-collapse: collapse; min-width: 820px; }
.rp-table th,
.rp-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    text-align: left;
    vertical-align: middle;
}
.rp-table th {
    color: var(--sms-text-muted);
    background: var(--sms-surface-muted, #f8fafc);
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.rp-title { color: var(--sms-heading); font-weight: 800; line-height: 1.35; }
.rp-meta { display: block; margin-top: 0.2rem; color: var(--sms-text-muted); font-size: 0.75rem; font-weight: 600; }
.rp-number {
    display: inline-flex; align-items: center; gap: 0.38rem;
    padding: 0.28rem 0.62rem; border-radius: 999px;
    color: #4338ca; background: rgba(99,102,241,0.12);
    font-size: 0.76rem; font-weight: 900; letter-spacing: 0.03em;
}
.rp-status {
    display: inline-flex; align-items: center;
    padding: 0.22rem 0.68rem; border-radius: 999px;
    font-size: 0.7rem; font-weight: 900;
}
.rp-status-pending { color: #b45309; background: #fef3c7; }
.rp-status-registered { color: #047857; background: #d1fae5; }
.rp-status-approved { color: #047857; background: #d1fae5; }
.rp-status-rejected { color: #b91c1c; background: #fee2e2; }
.rp-empty {
    padding: 2rem 1.25rem;
    color: var(--sms-text-muted);
    text-align: center;
    font-size: 0.9rem;
    font-weight: 700;
}
.rp-empty[hidden] { display: none; }
.rp-title-approval-sync { color: #2563eb; font-size: 0.78rem; font-weight: 800; white-space: nowrap; }
.rp-ta-modal[hidden] { display: none; }
.rp-ta-modal {
    position: fixed; inset: 0; z-index: 9999;
    display: block;
    padding: 2rem 1rem; background: rgba(15,23,42,0.65);
    overflow: auto;
}
.rp-ta-dialog {
    max-width: 780px;
    width: 100%;
    margin: 0 auto;
    display: block;
    border-radius: 12px; background: #fff; overflow: hidden;
    box-shadow: 0 24px 64px rgba(0,0,0,.38);
}
.rp-ta-dialog.signature { width: min(500px, 100%); z-index: 10000; }
.rp-ta-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 0.9rem 1.25rem; background: #1e3a70; color: #fff;
}
.rp-ta-head.green { background: #0f8a61; }
.rp-ta-head h3 { margin: 0; font-size: 1rem; font-weight: 850; }
.rp-ta-head span { display: block; margin-top: 0.15rem; color: rgba(255,255,255,0.78); font-size: 0.8rem; font-weight: 700; }
.rp-ta-close {
    min-height: 34px; border: 0; border-radius: 8px;
    color: #fff; background: #2563eb; font-weight: 850; cursor: pointer;
    padding: 0.42rem 0.75rem;
    display: inline-flex; align-items: center; gap: 0.35rem;
}
.rp-ta-body {
    padding: 1.5rem; overflow: visible; color: #111;
    font-family: Arial, Helvetica, sans-serif; font-size: 9pt;
}
.rp-ta-form-title { margin: 0 0 0.9rem; color: #1e3a70; text-align: center; font-size: 1.65rem; font-weight: 500; letter-spacing: 0; }
.rp-ta-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem; margin-bottom: 0.85rem; font-size: 0.82rem; }
.rp-ta-box { border: 1px solid #94a3b8; border-radius: 4px; margin-bottom: 0.7rem; overflow: hidden; }
.rp-ta-box h4 { margin: 0; padding: 0.32rem 0.55rem; color: #fff; background: #173b73; font-size: 0.76rem; font-weight: 850; }
.rp-ta-box-content { padding: 0.55rem 0.7rem; font-size: 0.82rem; }
.rp-ta-members { width: 100%; border-collapse: collapse; font-size: 0.78rem; }
.rp-ta-members th, .rp-ta-members td { border: 1px solid #222; padding: 0.28rem 0.45rem; text-align: left; }
.rp-ta-title-box { padding: 0.65rem; border-left: 4px solid #1d4ed8; text-align: center; font-weight: 850; }
.rp-ta-approval-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.65rem; }
.rp-ta-sign-block { text-align: center; min-width: 0; }
.rp-ta-sign-wrap { position: relative; height: 58px; margin: 0 auto 0.25rem; max-width: 180px; }
.rp-ta-sign-line { position: absolute; left: 0; right: 0; bottom: 4px; border-bottom: 1px solid #111; }
.rp-ta-sign-wrap img { position: absolute; left: 0; right: 0; bottom: 5px; width: 100%; height: 48px; object-fit: contain; }
.rp-ta-name { display: block; font-size: 0.75rem; font-weight: 850; }
.rp-ta-role { display: block; font-size: 0.68rem; color: #475569; }
.rp-ta-foot {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 0.8rem 1.25rem; border-top: 1px solid #e2e8f0; background: #f8fafc;
}
.rp-ta-sign-panel { display: grid; gap: 0.9rem; padding: 1rem 1.25rem; }
.rp-ta-sign-panel p { margin: 0; color: #475569; font-size: 0.84rem; font-weight: 700; line-height: 1.45; }
.rp-ta-pad-label {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.55rem 0.75rem; border: 1px solid #cbd5e1; border-bottom: 0;
    border-radius: 8px 8px 0 0; color: #64748b; font-size: 0.72rem; font-weight: 850;
    text-transform: uppercase; letter-spacing: 0.05em;
}
.rp-ta-clear { border: 0; background: transparent; color: #6d28d9; font-weight: 850; cursor: pointer; }
.rp-ta-canvas {
    display: block; width: 100%; height: 160px;
    border: 1px solid #cbd5e1; border-radius: 0 0 8px 8px;
    background: #fff; touch-action: none; cursor: crosshair;
}
.rp-ta-sign-actions { display: flex; justify-content: flex-end; gap: 0.6rem; padding: 0.8rem 1.25rem; border-top: 1px solid #e2e8f0; }
.rp-btn-success { color: #fff; background: #059669; border-color: #059669; }
.rp-btn-success:hover { background: #047857; color: #fff; }
.rp-btn-light { color: #64748b; background: #fff; border-color: #dbe4f0; box-shadow: none; }
.rp-btn-light:hover { color: #334155; border-color: #cbd5e1; }
[data-theme="dark"] .rp-card,
[data-theme="dark"] .rp-stat { background: rgba(15,23,42,0.72); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rp-card-head,
[data-theme="dark"] .rp-card-tools,
[data-theme="dark"] .rp-search input,
[data-theme="dark"] .rp-table th,
[data-theme="dark"] .rp-table td { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rp-card-tools,
[data-theme="dark"] .rp-filter { background: rgba(15,23,42,0.72); }
[data-theme="dark"] .rp-search input { background: rgba(148,163,184,0.06); }
[data-theme="dark"] .rp-table th { background: rgba(148,163,184,0.06); }
@media (max-width: 767.98px) {
    .rp-stats { grid-template-columns: 1fr; }
    .rp-card-head,
    .rp-card-tools { align-items: stretch; flex-direction: column; }
    .rp-btn { width: 100%; }
    .rp-search { width: 100%; }
    .rp-filter { flex-basis: auto; width: 100%; }
    .rp-ta-approval-grid,
    .rp-ta-meta { grid-template-columns: 1fr; }
}
</style>

<div class="rp-wrap">
    <div class="rp-stats">
        <div class="rp-stat">
            <div class="rp-stat-icon blue"><?= smsIcon('file-signature') ?></div>
            <div><strong><?= count($approvedProposals) ?></strong><span>Approved Proposals</span></div>
        </div>
        <div class="rp-stat">
            <div class="rp-stat-icon green"><?= smsIcon('check-circle') ?></div>
            <div><strong><?= $totalRegistered ?></strong><span>Registered Proposals</span></div>
        </div>
        <div class="rp-stat">
            <div class="rp-stat-icon amber"><?= smsIcon('clock') ?></div>
            <div><strong><?= $totalPendingRegistration ?></strong><span>Waiting</span></div>
        </div>
    </div>

    <?php if ($rpListView === 'approved'): ?>
    <section class="rp-card" data-rp-card>
        <div class="rp-card-head">
            <div class="rp-card-title">
                <h2>Approved Proposals</h2>
                <span data-rp-count><?= count($approvedProposals) ?> record<?= count($approvedProposals) === 1 ? '' : 's' ?></span>
            </div>
        </div>
        <div class="rp-card-tools">
            <label class="rp-search">
                <?= smsIcon('search') ?>
                <input type="search" data-rp-search placeholder="Search by title, researcher, or proposal number..." aria-label="Search approved proposals">
            </label>
            <select class="rp-filter" data-rp-status aria-label="Filter proposal status">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="registered">Registered</option>
            </select>
        </div>

        <?php if (empty($approvedProposals)): ?>
            <div class="rp-empty" data-rp-empty>
                No approved proposals yet. Proposals will appear here after their tracking progress is approved.
            </div>
        <?php else: ?>
            <div class="rp-table-wrap">
                <table class="rp-table">
                    <thead>
                        <tr>
                            <th>Proposal Title</th>
                            <th>Researcher</th>
                            <th>Date Approved</th>
                            <th>Proposal Number</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvedProposals as $proposal): ?>
                            <?php
                                $isRegistered = ($proposal['registration_status'] ?? 'Pending') === 'Registered';
                                $approvedDate = $proposal['approved_on'] ?: $proposal['registered_at'];
                            ?>
                            <tr data-rp-row
                                data-status="<?= $isRegistered ? 'registered' : 'pending' ?>"
                                data-search="<?= htmlspecialchars(strtolower(trim(($proposal['research_title'] ?? '') . ' ' . ($proposal['ref_code'] ?? '') . ' ' . ($proposal['proposal_number'] ?? '') . ' ' . ($proposal['rep_name'] ?? '') . ' ' . ($proposal['college_department'] ?? '') . ' ' . ($proposal['registration_status'] ?? '')))) ?>">
                                <td>
                                    <div class="rp-title"><?= htmlspecialchars($proposal['research_title']) ?></div>
                                    <span class="rp-meta"><?= htmlspecialchars($proposal['ref_code']) ?> · <?= htmlspecialchars($proposal['college_department']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($proposal['rep_name']) ?></td>
                                <td><?= $approvedDate ? htmlspecialchars(date('M j, Y', strtotime($approvedDate))) : 'For confirmation' ?></td>
                                <td>
                                    <?php if (!empty($proposal['proposal_number'])): ?>
                                        <span class="rp-number"><?= smsIcon('hashtag') ?><?= htmlspecialchars($proposal['proposal_number']) ?></span>
                                    <?php else: ?>
                                        <span class="rp-meta">Will be generated</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="rp-status <?= $isRegistered ? 'rp-status-registered' : 'rp-status-pending' ?>">
                                        <?= $isRegistered ? 'Registered' : 'Pending' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($isRegistered): ?>
                                        <span class="rp-btn rp-btn-done"><?= smsIcon('check') ?> Registered</span>
                                    <?php else: ?>
                                        <form method="post" action="" style="margin:0;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="process" value="register-approved-proposal">
                                            <input type="hidden" name="proposal_id" value="<?= (int) $proposal['id'] ?>">
                                            <button type="submit" class="rp-btn rp-btn-primary">
                                                <?= smsIcon('save') ?> Register
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="rp-empty" data-rp-empty hidden>
                Walang record na tumugma sa search.
            </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($rpListView === 'title'): ?>
    <section class="rp-card"
             data-crad-ta-card
             data-endpoint="<?= htmlspecialchars(BASE_URL . '/modules/crad/pages/register-proposal.php?ajax=title-approvals') ?>"
             data-update="<?= htmlspecialchars(BASE_URL . '/modules/crad/pages/register-proposal.php?ajax=title-approval-approve') ?>">
        <div class="rp-card-head">
            <div class="rp-card-title">
                <h2><?= smsIcon('file-signature') ?> Title Approval for CRAD Officer Review <span id="cradTaPending" class="rp-status rp-status-pending"><?= (int) $titleApprovalPayload['pending'] ?></span></h2>
                <span id="cradTaCount"><?= count($titleApprovalRows) ?> record<?= count($titleApprovalRows) === 1 ? '' : 's' ?></span>
            </div>
            <div id="cradTaSync" class="rp-title-approval-sync">Synced <?= htmlspecialchars($titleApprovalPayload['last_sync']) ?></div>
        </div>
        <div class="rp-card-tools">
            <label class="rp-search">
                <?= smsIcon('search') ?>
                <input type="search" id="cradTaSearch" placeholder="Search by title, student, or coordinator..." aria-label="Search title approvals">
            </label>
            <select class="rp-filter" id="cradTaFilter" aria-label="Filter title approval status">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div class="rp-table-wrap">
            <table class="rp-table">
                <thead>
                    <tr>
                        <th>Research Title</th>
                        <th>Student</th>
                        <th>Coordinator Approved</th>
                        <th>CRAD Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="cradTaBody"></tbody>
            </table>
        </div>
        <div id="cradTaEmpty" class="rp-empty" hidden>
            No title approval ready for CRAD Officer review yet.
        </div>
    </section>
    <?php endif; ?>
</div>

<div id="cradTaModal" class="rp-ta-modal" hidden>
    <div class="rp-ta-dialog" role="dialog" aria-modal="true" aria-labelledby="cradTaModalTitle">
        <div class="rp-ta-head">
            <div>
                <h3 id="cradTaModalTitle">Title Approval Form</h3>
                <span>CRAD Officer Review</span>
            </div>
            <button type="button" class="rp-ta-close" data-close-crad-ta><?= smsIcon('times') ?> Close</button>
        </div>
        <div id="cradTaForm" class="rp-ta-body"></div>
        <div class="rp-ta-foot">
            <div id="cradTaStatusText" class="rp-title"></div>
            <div id="cradTaActions"></div>
        </div>
    </div>
</div>

<div id="cradTaSignModal" class="rp-ta-modal" hidden>
    <div class="rp-ta-dialog signature" role="dialog" aria-modal="true" aria-labelledby="cradTaSignTitle">
        <div class="rp-ta-head green">
            <div>
                <span>TITLE APPROVAL</span>
                <h3 id="cradTaSignTitle"><?= smsIcon('signature') ?> Draw Your Signature</h3>
            </div>
            <button type="button" class="rp-ta-close" data-close-crad-ta-sign><?= smsIcon('times') ?></button>
        </div>
        <div class="rp-ta-sign-panel">
            <p>Sign in the box below. Your signature will be saved to the Title Approval Form.</p>
            <div>
                <div class="rp-ta-pad-label">
                    <span>CRAD Officer Signature Pad (Draw Below)</span>
                    <button type="button" class="rp-ta-clear" id="cradTaClearPad">Clear Pad</button>
                </div>
                <canvas id="cradTaSignatureCanvas" class="rp-ta-canvas"></canvas>
            </div>
            <div id="cradTaSignError" style="display:none;margin-top:.6rem;padding:.5rem .75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;font-size:.8rem;font-weight:700;">
                <?= smsIcon('exclamation-circle') ?> Please provide your signature before approving.
            </div>
        </div>
        <div class="rp-ta-sign-actions">
            <button type="button" class="rp-btn rp-btn-light" data-close-crad-ta-sign>Cancel</button>
            <button type="button" class="rp-btn rp-btn-success" id="cradTaConfirmSign"><?= smsIcon('check') ?> Confirm & Approve</button>
        </div>
    </div>
</div>

<div id="cradTaApproveConfirmModal" class="rp-ta-modal" hidden>
    <div class="rp-ta-dialog signature" role="dialog" aria-modal="true" aria-labelledby="cradTaApproveConfirmTitle">
        <div class="rp-ta-head">
            <div>
                <span>TITLE APPROVAL</span>
                <h3 id="cradTaApproveConfirmTitle"><?= smsIcon('check-circle') ?> Confirm Approval</h3>
            </div>
            <button type="button" class="rp-ta-close" data-close-crad-ta-approve-confirm><?= smsIcon('times') ?></button>
        </div>
        <div class="rp-ta-sign-panel">
            <p style="margin:0 0 .75rem;color:#111827;font-size:1rem;font-weight:800;">Are you sure you want to approve this Title Approval Form?</p>
            <p style="margin:0 0 .55rem;color:#64748b;font-size:.9rem;line-height:1.55;font-weight:600;">Your signature will be saved and this approval will be recorded.</p>
            <p style="margin:0;color:#64748b;font-size:.9rem;line-height:1.55;font-weight:600;">Once confirmed, the existing Title Approval process will continue.</p>
        </div>
        <div class="rp-ta-sign-actions">
            <button type="button" class="rp-btn rp-btn-light" data-close-crad-ta-approve-confirm>Cancel</button>
            <button type="button" class="rp-btn rp-btn-success" id="cradTaApproveConfirmYes"><?= smsIcon('check') ?> Yes, Approve</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const card = document.querySelector('[data-rp-card]');
    if (!card) return;
    const input = card.querySelector('[data-rp-search]');
    const status = card.querySelector('[data-rp-status]');
    const rows = Array.from(card.querySelectorAll('[data-rp-row]'));
    const empty = card.querySelector('[data-rp-empty]');
    const count = card.querySelector('[data-rp-count]');
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
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const card = document.querySelector('[data-crad-ta-card]');
    if (!card) return;

    const endpoint = card.dataset.endpoint;
    const updateUrl = card.dataset.update;
    const body = document.getElementById('cradTaBody');
    const empty = document.getElementById('cradTaEmpty');
    const countEl = document.getElementById('cradTaCount');
    const pendingEl = document.getElementById('cradTaPending');
    const syncEl = document.getElementById('cradTaSync');
    const searchEl = document.getElementById('cradTaSearch');
    const filterEl = document.getElementById('cradTaFilter');
    const modal = document.getElementById('cradTaModal');
    const formEl = document.getElementById('cradTaForm');
    const statusEl = document.getElementById('cradTaStatusText');
    const actionsEl = document.getElementById('cradTaActions');
    const signModal = document.getElementById('cradTaSignModal');
    const approveConfirmModal = document.getElementById('cradTaApproveConfirmModal');
    const approveConfirmYes = document.getElementById('cradTaApproveConfirmYes');
    const signError = document.getElementById('cradTaSignError');
    const canvas = document.getElementById('cradTaSignatureCanvas');
    const ctx = canvas ? canvas.getContext('2d') : null;
    const dialog = modal ? modal.querySelector('.rp-ta-dialog') : null;
    const signDialog = signModal ? signModal.querySelector('.rp-ta-dialog') : null;
    let rows = <?= json_encode($titleApprovalRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    let activeId = null;
    let drawing = false;
    let hasInk = false;
    let pendingSignature = '';
    let approveRequestInFlight = false;

    const esc = function (value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    };
    const fmtDate = function (value) {
        if (!value) return '';
        const parsed = new Date(String(value).replace(' ', 'T'));
        return isNaN(parsed.getTime()) ? String(value) : parsed.toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'});
    };
    const badgeClass = function (status) {
        const normalized = String(status || 'Pending');
        if (normalized === 'Approved') return 'rp-status-approved';
        if (normalized === 'Rejected') return 'rp-status-rejected';
        return 'rp-status-pending';
    };
    const parseMembers = function (raw) {
        try {
            const decoded = JSON.parse(raw || '[]');
            return Array.isArray(decoded) ? decoded : [];
        } catch (e) {
            return [];
        }
    };
    const parseScreening = function (raw) {
        try {
            const decoded = JSON.parse(raw || '{}');
            return decoded && typeof decoded === 'object' ? decoded : {};
        } catch (e) {
            return {};
        }
    };
    const screeningCriteria = [
        ['agenda_alignment', 'Title aligns with institutional research agenda'],
        ['feasible_original', 'Proposed study is feasible and original'],
        ['ethical_sdg', 'Ethical and SDG requirements are satisfied']
    ];
    const checkedCell = function (screening, key, value) {
        return String(screening[key] || '').toLowerCase() === value ? '&#10003;' : '';
    };
    const signatureImg = function (src) {
        return src ? `<img src="${src}" alt="">` : '';
    };
    const findRow = function (id) {
        return rows.find(function (row) { return Number(row.id) === Number(id); }) || null;
    };
    const searchText = function (row) {
        return [
            row.proposed_title, row.student_name, row.student_id, row.coordinator_name,
            row.department, row.discipline_cluster, row.crad_status
        ].join(' ').toLowerCase();
    };
    function applyTitleFilter() {
        const term = searchEl ? searchEl.value.trim().toLowerCase() : '';
        const status = filterEl ? filterEl.value.trim().toLowerCase() : '';
        const trs = Array.from(body ? body.querySelectorAll('tr[data-title-row]') : []);
        let visible = 0;
        trs.forEach(function (tr) {
            const match = (term === '' || String(tr.dataset.search || '').indexOf(term) !== -1)
                && (status === '' || String(tr.dataset.status || '') === status);
            tr.hidden = !match;
            if (match) visible++;
        });
        if (empty) empty.hidden = trs.length > 0 && visible > 0;
        if (countEl) countEl.textContent = visible + ' record' + (visible === 1 ? '' : 's');
    }

    function renderRows() {
        if (!body) return;
        body.innerHTML = '';
        rows.forEach(function (row) {
            const tr = document.createElement('tr');
            tr.dataset.titleRow = '1';
            tr.dataset.status = String(row.crad_status || 'Pending').toLowerCase();
            tr.dataset.search = searchText(row);
            tr.innerHTML = `
                <td>
                    <div class="rp-title">${esc(row.proposed_title)}</div>
                    <span class="rp-meta">${esc(row.discipline_cluster || '')}</span>
                </td>
                <td>
                    <div class="rp-title">${esc(row.student_name || 'Student')}</div>
                    <span class="rp-meta">${esc(row.student_id || '')}</span>
                </td>
                <td>
                    <div>${esc(row.coordinator_name || 'Research Coordinator')}</div>
                    <span class="rp-meta">${fmtDate(row.coordinator_reviewed_at)}</span>
                </td>
                <td><span class="rp-status ${badgeClass(row.crad_status)}">${esc(row.crad_status || 'Pending')}</span></td>
                <td><button type="button" class="rp-btn rp-btn-primary" data-open-crad-ta="${Number(row.id)}"><?= smsIcon('folder-open') ?> Open</button></td>
            `;
            body.appendChild(tr);
        });
        applyTitleFilter();
        if (pendingEl) pendingEl.textContent = rows.filter(function (row) { return String(row.crad_status || '') === 'Pending'; }).length;
    }

    function renderForm(row) {
        const members = parseMembers(row.members_json);
        const memberRows = [0,1,2,3,4,5].map(function (idx) {
            const item = members[idx] || {};
            const name = Array.isArray(item) ? (item[0] || '') : (item.name || '');
            const section = Array.isArray(item) ? (item[1] || '') : (item.section || '');
            return `<tr>
                <td style="text-align:center;border:0.8px solid #222;padding:2mm 1.4mm;">${idx + 1}</td>
                <td style="border:0.8px solid #222;padding:2mm 1.4mm;font-weight:700;">${esc(name)}</td>
                <td style="border:0.8px solid #222;padding:2mm 1.4mm;">${esc(section)}</td>
            </tr>`;
        }).join('');
        const adviserSig = row.adviser_signature_data
            ? `<img src="${row.adviser_signature_data}" style="position:absolute;bottom:2px;left:0;right:0;width:100%;height:50px;object-fit:contain;object-position:center bottom;" alt="">`
            : '';
        const coordSig = row.coordinator_signature_data
            ? `<img src="${row.coordinator_signature_data}" style="position:absolute;bottom:2px;left:0;right:0;width:100%;height:50px;object-fit:contain;object-position:center bottom;" alt="">`
            : '';
        const cradSig = row.crad_signature_data
            ? `<img src="${row.crad_signature_data}" style="position:absolute;bottom:2px;left:0;right:0;width:100%;height:50px;object-fit:contain;object-position:center bottom;" alt="">`
            : '';
        const screening = parseScreening(row.coordinator_screening_json);
        const screeningRows = screeningCriteria.map(function (item) {
            const key = item[0];
            const label = item[1];
            return `<tr>
                <td style="border:0.8px solid #222;padding:2mm;">${label}</td>
                <td style="border:0.8px solid #222;text-align:center;font-weight:800;">${checkedCell(screening, key, 'yes')}</td>
                <td style="border:0.8px solid #222;text-align:center;font-weight:800;">${checkedCell(screening, key, 'no')}</td>
            </tr>`;
        }).join('');
        const bar = function (label) {
            return `<div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:3px 6px;margin-bottom:2mm;">${label}</div>`;
        };
        formEl.innerHTML = `
            <div style="display:grid;grid-template-columns:15mm 1fr auto;align-items:center;gap:3mm;padding-bottom:2.5mm;border-bottom:2px solid #17366f;margin-bottom:4mm;">
                <img src="<?= BASE_URL ?>/images/bcp-crest.png?v=20260811" style="width:42px;height:42px;object-fit:contain;border-radius:0;background:transparent;" alt="">
                <div style="font-size:7pt;line-height:1.4;">
                    <strong style="display:block;font-size:10pt;color:#17366f;">BESTLINK COLLEGE OF THE PHILIPPINES</strong>
                    <span>#1071 Brgy. Kaligayahan, Quirino Highway, Novaliches, Quezon City</span><br>
                    <b>CENTER FOR RESEARCH AND DEVELOPMENT</b>
                </div>
                <div style="font-size:7pt;font-weight:700;padding:3px 8px;border:1px solid #c3cede;border-radius:4px;background:#edf4ff;color:#17366f;">CRAD Form S2 V3</div>
            </div>
            <h2 style="text-align:center;font-size:13pt;font-weight:800;color:#17366f;letter-spacing:.05em;margin:0 0 4mm;text-transform:uppercase;">TITLE APPROVAL FORM</h2>
            <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:6mm;font-size:8.5pt;margin-bottom:4mm;padding:3mm 4mm;border:1px solid #b9c7da;border-radius:4px;background:#f8fbff;">
                <div><strong style="color:#17366f;">Date:</strong> ${esc(row.submission_date || '')}</div>
                <div><strong style="color:#17366f;">I. Department:</strong> ${esc(row.department || '')}</div>
            </div>
            ${bar('II. Students Information')}
            <table style="width:100%;border-collapse:collapse;font-size:8pt;margin-bottom:4mm;">
                <thead>
                    <tr>
                        <th style="border:0.8px solid #222;background:#e4edf9;color:#17366f;padding:2mm;width:6%;">No.</th>
                        <th style="border:0.8px solid #222;background:#e4edf9;color:#17366f;padding:2mm;">Name (Last, First, M.I.)</th>
                        <th style="border:0.8px solid #222;background:#e4edf9;color:#17366f;padding:2mm;width:20%;">Section</th>
                    </tr>
                </thead>
                <tbody>${memberRows}</tbody>
            </table>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:3mm;margin-bottom:4mm;">
                <div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:7.5pt;">
                    <div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">III. Research Discipline Cluster</div>
                    <div style="background:#dceaff;color:#12366f;padding:2px 5px;border-radius:3px;font-weight:700;">&#10003; ${esc(row.discipline_cluster || '')}</div>
                </div>
                <div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:7.5pt;">
                    <div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">IV. SDG Alignment</div>
                    <div style="background:#dceaff;color:#12366f;padding:2px 5px;border-radius:3px;font-weight:700;">&#10003; ${esc(row.primary_sdg || '')}</div>
                </div>
            </div>
            <div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:7.5pt;margin-bottom:4mm;">
                <div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">V. Institutional Research Agenda Alignment</div>
                <div style="background:#dceaff;color:#12366f;padding:2px 5px;border-radius:3px;font-weight:700;">&#10003; ${esc(row.research_agenda || '')}</div>
            </div>
            ${bar('VI. Proposed Research Title')}
            <div style="padding:3mm 5mm;border:1px solid #b9c7da;border-left:3px solid #2457a7;border-radius:4px;background:#fbfdff;font-size:10pt;font-weight:800;text-align:center;color:#12294d;min-height:12mm;margin-bottom:4mm;">${esc(row.proposed_title || '')}</div>
            ${bar('VII. Sustainable Development Goal Justification')}
            <div style="padding:3mm 5mm;border:1px solid #b9c7da;border-left:3px solid #2457a7;border-radius:4px;background:#fbfdff;min-height:12mm;color:#24364f;margin-bottom:4mm;">${esc(row.sdg_justification || '')}</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:3mm;align-items:start;">
                <div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:8pt;align-self:start;height:auto;min-height:0;">
                    <div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">VIII. Research Coordinator Screening</div>
                    <table style="width:100%;border-collapse:collapse;font-size:7.5pt;">
                        <thead><tr><th style="border:0.8px solid #222;padding:2mm;background:#e4edf9;">Evaluation Criteria</th><th style="border:0.8px solid #222;padding:2mm;background:#e4edf9;width:12%;">Yes</th><th style="border:0.8px solid #222;padding:2mm;background:#e4edf9;width:12%;">No</th></tr></thead>
                        <tbody>${screeningRows}</tbody>
                    </table>
                </div>
                <div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:8pt;text-align:center;background:#fbfdff;">
                    <div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;text-align:left;">IX. Approval (Name, signature and date)</div>
                    <div style="position:relative;width:80%;margin:4mm auto 0;height:54px;"><div style="position:absolute;bottom:0;left:0;right:0;border-bottom:1px solid #111;"></div>${adviserSig}</div>
                    <strong style="display:block;font-size:8.5pt;margin-top:1mm;">${esc(row.adviser_name || '')}</strong>
                    <span style="font-size:7.5pt;color:#555;">Research Adviser</span>
                    <div style="position:relative;width:80%;margin:5mm auto 0;height:54px;"><div style="position:absolute;bottom:0;left:0;right:0;border-bottom:1px solid #111;"></div>${coordSig}</div>
                    <strong style="display:block;font-size:8.5pt;margin-top:1mm;">${esc(row.coordinator_name || '')}</strong>
                    <span style="font-size:7.5pt;color:#555;">Program Research Coordinator</span>
                    <div style="margin:5mm 0 2mm;border-top:1px dashed #7c8da5;padding-top:3mm;text-align:left;font-size:7pt;color:#475569;">Received:</div>
                    <div style="position:relative;width:80%;margin:2mm auto 0;height:42px;"><div style="position:absolute;bottom:0;left:0;right:0;border-bottom:1px solid #111;"></div>${cradSig}</div>
                    <strong style="display:block;font-size:8.5pt;margin-top:1mm;">Center for Research and Development</strong>
                    <span style="font-size:7.5pt;color:#555;">Center for Research and Development Office</span>
                </div>
            </div>
        `;
        statusEl.innerHTML = `CRAD Officer Status: <span class="rp-status ${badgeClass(row.crad_status)}">${esc(row.crad_status || 'Pending')}</span>`;
        actionsEl.innerHTML = String(row.crad_status || 'Pending') === 'Pending'
            ? `<button type="button" class="rp-btn rp-btn-success" id="cradTaApprove"><?= smsIcon('signature') ?> Approve & Sign</button><button type="button" class="rp-btn rp-btn-danger" id="cradTaReject"><?= smsIcon('x') ?> Reject</button>`
            : (String(row.crad_status || '') === 'Rejected'
                ? `<span class="rp-btn rp-btn-danger" style="pointer-events:none;"><?= smsIcon('x') ?> Rejected</span>`
                : `<span class="rp-btn rp-btn-done"><?= smsIcon('check') ?> Approved</span>`);
        const approve = document.getElementById('cradTaApprove');
        if (approve) approve.addEventListener('click', openSignModal);
        const reject = document.getElementById('cradTaReject');
        if (reject) reject.addEventListener('click', rejectActive);
    }

    function openModal(id) {
        const row = findRow(id);
        if (!row) return;
        activeId = Number(id);
        renderForm(row);
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        window.setTimeout(function () {
            modal.scrollTop = 0;
            if (dialog) dialog.scrollTop = 0;
            if (formEl) formEl.scrollTop = 0;
        }, 0);
    }
    function closeModal() {
        modal.hidden = true;
        if (signModal.hidden) {
            document.body.style.overflow = '';
        }
    }

    function resizeCanvas() {
        if (!canvas || !ctx) return;
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();
        canvas.width = Math.max(1, Math.floor(rect.width * ratio));
        canvas.height = Math.max(1, Math.floor(rect.height * ratio));
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineWidth = 2.4;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#111827';
    }
    function clearCanvas() {
        if (!canvas || !ctx) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        hasInk = false;
        pendingSignature = '';
        if (signError) signError.style.display = 'none';
    }
    function canvasPoint(event) {
        const rect = canvas.getBoundingClientRect();
        return {x: event.clientX - rect.left, y: event.clientY - rect.top};
    }
    function openSignModal() {
        resetApproveConfirmButton();
        pendingSignature = '';
        if (signError) signError.style.display = 'none';
        signModal.hidden = false;
        document.body.style.overflow = 'hidden';
        if (signDialog) signDialog.scrollTop = 0;
        window.setTimeout(function () { resizeCanvas(); clearCanvas(); }, 30);
    }
    function closeSignModal() {
        if (approveRequestInFlight) return;
        signModal.hidden = true;
        drawing = false;
        pendingSignature = '';
        if (signError) signError.style.display = 'none';
        if (modal.hidden) {
            document.body.style.overflow = '';
        }
    }

    if (canvas && ctx) {
        canvas.addEventListener('pointerdown', function (event) {
            event.preventDefault();
            drawing = true;
            hasInk = true;
            const p = canvasPoint(event);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        });
        canvas.addEventListener('pointermove', function (event) {
            if (!drawing) return;
            event.preventDefault();
            const p = canvasPoint(event);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
        });
        ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (name) {
            canvas.addEventListener(name, function () { drawing = false; });
        });
    }

    function resetApproveConfirmButton() {
        approveRequestInFlight = false;
        approveConfirmYes.disabled = false;
        approveConfirmYes.innerHTML = '<?= smsIcon('check') ?> Yes, Approve';
    }

    function closeApproveConfirmModal() {
        if (approveRequestInFlight) return;
        approveConfirmModal.hidden = true;
    }

    async function approveActive(signatureData) {
        if (!activeId || !signatureData) return;
        const res = await fetch(updateUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({id: activeId, crad_signature_data: signatureData})
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Could not save CRAD officer approval.');
        approveConfirmModal.hidden = true;
        signModal.hidden = true;
        await refreshRows();
        const row = findRow(activeId);
        if (row) renderForm(row);
    }

    async function rejectActive() {
        if (!activeId || !window.confirm('Reject this title approval? This decision will be recorded immediately.')) return;
        const res = await fetch(updateUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({id: activeId, action: 'reject'})
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Could not save CRAD officer rejection.');
        await refreshRows();
        const row = findRow(activeId);
        if (row) renderForm(row);
    }

    async function refreshRows() {
        const res = await fetch(endpoint, {headers: {'Accept': 'application/json'}, cache: 'no-store', credentials: 'same-origin'});
        const data = await res.json();
        if (!data.ok) return;
        rows = data.rows || [];
        renderRows();
        if (syncEl) syncEl.textContent = 'Synced ' + (data.last_sync || '');
    }

    body.addEventListener('click', function (event) {
        const btn = event.target.closest('[data-open-crad-ta]');
        if (btn) openModal(btn.dataset.openCradTa);
    });
    document.querySelectorAll('[data-close-crad-ta]').forEach(function (btn) { btn.addEventListener('click', closeModal); });
    document.querySelectorAll('[data-close-crad-ta-sign]').forEach(function (btn) { btn.addEventListener('click', closeSignModal); });
    document.querySelectorAll('[data-close-crad-ta-approve-confirm]').forEach(function (btn) { btn.addEventListener('click', closeApproveConfirmModal); });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
    signModal.addEventListener('click', function (event) {
        if (event.target === signModal) closeSignModal();
    });
    approveConfirmModal.addEventListener('click', function (event) {
        if (event.target === approveConfirmModal) closeApproveConfirmModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (!approveConfirmModal.hidden) {
            closeApproveConfirmModal();
        } else if (!signModal.hidden) {
            closeSignModal();
        } else if (!modal.hidden) {
            closeModal();
        }
    });
    document.getElementById('cradTaClearPad')?.addEventListener('click', clearCanvas);
    document.getElementById('cradTaConfirmSign')?.addEventListener('click', function () {
        if (!activeId || !canvas || !hasInk) {
            if (signError) signError.style.display = 'block';
            return;
        }
        if (signError) signError.style.display = 'none';
        pendingSignature = canvas.toDataURL('image/png');
        approveConfirmModal.hidden = false;
        window.setTimeout(function () { approveConfirmYes.focus(); }, 30);
    });
    approveConfirmYes?.addEventListener('click', function () {
        if (!pendingSignature || approveRequestInFlight) return;
        approveRequestInFlight = true;
        approveConfirmYes.disabled = true;
        approveConfirmYes.innerHTML = 'Processing...';
        approveActive(pendingSignature)
            .catch(function (error) {
                if (signError) {
                    signError.textContent = error.message || 'Could not save CRAD officer approval.';
                    signError.style.display = 'block';
                }
            })
            .finally(resetApproveConfirmButton);
    });
    if (searchEl) searchEl.addEventListener('input', applyTitleFilter);
    if (filterEl) filterEl.addEventListener('change', applyTitleFilter);
    window.addEventListener('resize', resizeCanvas);

    renderRows();
    window.setInterval(refreshRows, 5000);
});
</script>

<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
