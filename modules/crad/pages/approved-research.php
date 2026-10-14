<?php
/**
 * SMS 2 - Research Coordinator: Research Status
 * Shows registered approved research titles that already have research group numbers.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';

requireAuth();

$roleKey = getCurrentUserRoleKey();
if (!in_array($roleKey, ['research_coordinator', 'superadmin'], true)) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

function rcTitleApprovalEnsureSchema(PDO $pdo): void
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
    foreach ($columns as $name => $sql) {
        try {
            if (!$pdo->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE " . $pdo->quote($name))->fetch()) {
                $pdo->exec($sql);
            }
        } catch (Throwable $e) {
            error_log('Coordinator title approval schema failed: ' . $e->getMessage());
        }
    }
}

function rcTitleApprovalRows(PDO $pdo): array
{
    rcTitleApprovalEnsureSchema($pdo);
    $stmt = $pdo->query(
        "SELECT id, student_id, student_name, submission_date, department,
                proposed_title, discipline_cluster, primary_sdg, research_agenda,
                sdg_justification, members_json, adviser_name, adviser_email,
                coordinator_name, status, adviser_remarks, adviser_signature_data,
                coordinator_status, coordinator_remarks, coordinator_screening_json, coordinator_signature_data,
                sent_at, reviewed_at, coordinator_reviewed_at
         FROM `crad_title_approvals`
         WHERE status = 'Approved'
         ORDER BY FIELD(coordinator_status, 'Pending', 'Returned', 'Rejected', 'Approved', 'Not Ready'),
                  reviewed_at DESC, id DESC"
    );
    return $stmt->fetchAll() ?: [];
}

function rcTitleApprovalPayload(): array
{
    try {
        $pdo = getCradDatabaseConnection();
        $rows = rcTitleApprovalRows($pdo);
        return [
            'ok' => true,
            'rows' => $rows,
            'pending' => count(array_filter($rows, static fn($row) => (string) ($row['coordinator_status'] ?? '') === 'Pending')),
            'last_sync' => date('M j, Y g:i:s A'),
        ];
    } catch (Throwable $e) {
        error_log('Coordinator title approval load failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Failed to load title approvals.', 'rows' => [], 'pending' => 0, 'last_sync' => date('M j, Y g:i:s A')];
    }
}

function rcTitleApprovalUpdate(int $id, string $status, string $remarks, string $signature, array $screening = []): bool
{
    if (!in_array($status, ['Approved', 'Returned', 'Rejected', 'Screening'], true)) {
        return false;
    }
    $pdo = getCradDatabaseConnection();
    rcTitleApprovalEnsureSchema($pdo);
    if ($signature !== '' && !str_starts_with($signature, 'data:image/png;base64,')) {
        $signature = '';
    }
    $allowedScreening = [];
    foreach (['agenda_alignment', 'feasible_original', 'ethical_sdg'] as $key) {
        $value = strtolower(trim((string) ($screening[$key] ?? '')));
        if (in_array($value, ['yes', 'no'], true)) {
            $allowedScreening[$key] = $value;
        }
    }
    $screeningJson = $allowedScreening ? json_encode($allowedScreening, JSON_UNESCAPED_UNICODE) : null;
    if ($status === 'Screening') {
        $stmt = $pdo->prepare(
            "UPDATE `crad_title_approvals`
             SET coordinator_screening_json = :screening_json
             WHERE id = :id
               AND status = 'Approved'"
        );
        $stmt->execute([
            ':screening_json' => $screeningJson,
            ':id' => $id,
        ]);
        if ($stmt->rowCount() > 0) {
            return true;
        }
        $check = $pdo->prepare("SELECT id FROM `crad_title_approvals` WHERE id = :id AND status = 'Approved' LIMIT 1");
        $check->execute([':id' => $id]);
        return (bool) $check->fetch();
    }
    $stmt = $pdo->prepare(
        "UPDATE `crad_title_approvals`
         SET coordinator_status = :status,
             coordinator_remarks = :remarks,
             coordinator_screening_json = :screening_json,
             coordinator_signature_data = CASE WHEN :status_sig = 'Approved' AND :sig_gate <> '' THEN :sig_value ELSE NULL END,
             crad_status = CASE WHEN :status_crad = 'Approved' THEN 'Pending' ELSE 'Not Ready' END,
             crad_signature_data = NULL,
             crad_reviewed_at = NULL,
             coordinator_reviewed_at = NOW()
         WHERE id = :id
           AND status = 'Approved'"
    );
    $stmt->execute([
        ':status' => $status,
        ':status_sig' => $status,
        ':status_crad' => $status,
        ':remarks' => $remarks !== '' ? $remarks : null,
        ':screening_json' => $screeningJson,
        ':sig_gate' => $signature,
        ':sig_value' => $signature,
        ':id' => $id,
    ]);
    return $stmt->rowCount() > 0;
}

function rcApprovedResearchFetch(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT
            g.id AS group_id,
            g.proposal_id,
            g.proposal_number,
            g.group_number,
            g.group_name,
            COALESCE(NULLIF(g.research_title, ''), p.research_title) AS research_title,
            COALESCE(NULLIF(g.college_dept, ''), p.college_department) AS college_dept,
            COALESCE(NULLIF(g.adviser, ''), p.research_adviser) AS adviser,
            COALESCE(NULLIF(g.academic_year, ''), p.academic_year) AS academic_year,
            COALESCE(NULLIF(g.leader_name, ''), p.rep_name) AS leader_name,
            COALESCE(NULLIF(g.leader_id, ''), p.rep_id) AS leader_id,
            COALESCE(NULLIF(g.leader_email, ''), p.rep_email) AS leader_email,
            COALESCE(NULLIF(g.leader_contact, ''), p.rep_contact) AS leader_contact,
            g.status AS group_status,
            g.date_assigned,
            g.created_at AS group_created_at,
            p.status AS proposal_status,
            p.registration_status,
            'Approved' AS display_status,
            p.approved_at,
            p.registered_at
         FROM `crad_research_groups` g
         INNER JOIN `crad_research_proposals` p ON p.id = g.proposal_id
         WHERE p.status = 'Approved'
           AND p.registration_status = 'Registered'
           AND p.proposal_number IS NOT NULL
           AND g.group_number IS NOT NULL
           AND g.group_number <> ''
         ORDER BY g.date_assigned DESC, g.id DESC"
    );

    return $stmt->fetchAll() ?: [];
}

function rcApprovedResearchPayload(): array
{
    try {
        $pdo = getCradDatabaseConnection();
        $rows = rcApprovedResearchFetch($pdo);
    } catch (Throwable $e) {
        error_log('Research Coordinator approved research load failed: ' . $e->getMessage());
        return [
            'ok' => false,
            'error' => 'Failed to load approved research records.',
            'rows' => [],
            'stats' => ['total' => 0, 'approved' => 0, 'with_adviser' => 0],
            'last_sync' => date('M j, Y g:i:s A'),
        ];
    }

    $withAdviser = 0;
    foreach ($rows as $row) {
        if (trim((string) ($row['adviser'] ?? '')) !== '') {
            $withAdviser++;
        }
    }

    return [
        'ok' => true,
        'rows' => $rows,
        'stats' => [
            'total' => count($rows),
            'approved' => count(array_filter($rows, static fn($row) => (string) ($row['proposal_status'] ?? '') === 'Approved')),
            'with_adviser' => $withAdviser,
        ],
        'last_sync' => date('M j, Y g:i:s A'),
    ];
}

if (($_GET['ajax'] ?? '') === 'approved-research') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(rcApprovedResearchPayload());
    exit;
}

if (($_GET['ajax'] ?? '') === 'title-approvals') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(rcTitleApprovalPayload());
    exit;
}

if (($_GET['ajax'] ?? '') === 'title-approval-status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
    echo json_encode([
        'ok' => rcTitleApprovalUpdate(
            (int) ($body['id'] ?? 0),
            (string) ($body['status'] ?? ''),
            trim((string) ($body['remarks'] ?? '')),
            trim((string) ($body['coordinator_signature_data'] ?? '')),
            is_array($body['coordinator_screening'] ?? null) ? $body['coordinator_screening'] : []
        ),
    ]);
    exit;
}

function rcE(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$payload = rcApprovedResearchPayload();
$approvedRows = $payload['rows'];
$stats = $payload['stats'];
$titleApprovalPayload = rcTitleApprovalPayload();
$titleApprovalRows = $titleApprovalPayload['rows'];
$pageTitle = 'Research Status';
$activeModule = 'crad';
$activePage = 'approved-research';
$breadcrumbs = [
    ['label' => 'Research Coordinator', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Research Status', 'url' => null],
];

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>

<style>
.rcar-wrap { display: flex; flex-direction: column; gap: 1rem; }
.rcar-header {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1.1rem 1.25rem;
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 8px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.rcar-header h1 { margin: 0; font-size: 1.25rem; font-weight: 850; color: var(--sms-heading); }
.rcar-header p { margin: 0.25rem 0 0; color: var(--sms-text-muted); font-size: 0.86rem; }
.rcar-sync { color: #2563eb; font-size: 0.78rem; font-weight: 800; white-space: nowrap; }
.rcar-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.85rem; }
.rcar-stat {
    display: flex; align-items: center; gap: 0.8rem;
    padding: 0.9rem 1rem;
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 8px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-xs);
}
.rcar-stat i {
    width: 40px; height: 40px; display: grid; place-items: center;
    border-radius: 8px; color: #1d4ed8; background: rgba(37,99,235,0.12);
}
.rcar-stat strong { display: block; color: var(--sms-heading); font-size: 1.35rem; line-height: 1; }
.rcar-stat span { display: block; margin-top: 0.25rem; color: var(--sms-text-muted); font-size: 0.72rem; font-weight: 800; text-transform: uppercase; }
.rcar-card {
    border: 1px solid var(--sms-border, #e2e8f0);
    border-radius: 8px;
    background: var(--sms-surface-solid, #fff);
    box-shadow: var(--sms-shadow-sm);
    overflow: hidden;
}
.rcar-card-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 0.9rem 1rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
}
.rcar-card-head h2 { margin: 0; color: var(--sms-heading); font-size: 0.95rem; font-weight: 850; }
.rcar-record-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
}
.rcar-record-title {
    display: flex; align-items: center; flex-wrap: wrap; gap: .45rem;
    margin: 0;
    color: var(--sms-text-muted);
    font-size: .82rem;
    font-weight: 950;
    letter-spacing: 0;
    text-transform: uppercase;
}
.rcar-record-title i { color: #64748b; }
.rcar-record-total { display: block; margin-top: .55rem; color: var(--sms-text-muted); font-size: .8rem; font-weight: 850; }
.rcar-search {
    width: min(320px, 100%);
    min-height: 36px;
    border: 1px solid var(--sms-border, #d8e2ef);
    border-radius: 8px;
    padding: 0.45rem 0.7rem;
    background: var(--sms-surface-muted, #f8fafc);
    color: var(--sms-heading);
    font-size: 0.84rem;
}
.rcar-toolbar { display: flex; align-items: center; gap: .75rem; padding: 1rem; border-bottom: 1px solid var(--sms-border, #e2e8f0); }
.rcar-toolbar .rcar-search { flex: 1 1 auto; width: auto; min-height: 40px; }
.rcar-filter { width: min(170px, 100%); min-height: 40px; border: 1px solid var(--sms-border, #d8e2ef); border-radius: 8px; padding: .45rem .7rem; background: var(--sms-surface-muted, #f8fafc); color: var(--sms-heading); font-size: .84rem; }
.rcar-table-wrap { overflow-x: auto; }
.rcar-table { width: 100%; min-width: 980px; border-collapse: collapse; }
.rcar-table th,
.rcar-table td {
    padding: 0.82rem 0.9rem;
    border-bottom: 1px solid var(--sms-border, #e2e8f0);
    text-align: left; vertical-align: top;
}
.rcar-table th {
    color: var(--sms-text-muted);
    background: var(--sms-surface-muted, #f8fafc);
    font-size: 0.72rem;
    font-weight: 850;
    text-transform: uppercase;
}
.rcar-title { color: var(--sms-heading); font-weight: 850; line-height: 1.35; }
.rcar-muted { color: var(--sms-text-muted); font-size: 0.76rem; font-weight: 650; }
.rcar-code {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    color: #1d4ed8;
    background: rgba(37,99,235,0.12);
    font-size: 0.74rem;
    font-weight: 900;
}
.rcar-status {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.26rem 0.58rem;
    border-radius: 999px;
    color: #047857;
    background: #d1fae5;
    font-size: 0.74rem;
    font-weight: 850;
}
.rcar-empty {
    padding: 2rem 1rem;
    text-align: center;
    color: var(--sms-text-muted);
    font-weight: 750;
}
.rcta-actions { display:flex; gap:.45rem; flex-wrap:wrap; }
.rcta-btn { border:0; border-radius:8px; color:#fff; font-size:.78rem; font-weight:850; min-height:34px; padding:.42rem .7rem; }
.rcta-btn.primary { background:#2563eb; }
.rcta-btn.success { background:#059669; }
.rcta-btn.danger { background:#dc2626; }
.rcta-badge { border-radius:999px; display:inline-flex; font-size:.74rem; font-weight:850; padding:.25rem .6rem; }
.rcta-badge.pending { background:#fef3c7; color:#92400e; }
.rcta-badge.approved { background:#d1fae5; color:#047857; }
.rcta-badge.returned { background:#fee2e2; color:#991b1b; }
.rcta-badge.rejected { background:#fee2e2; color:#991b1b; }
.rcta-inline-alert {
    display: none;
    margin-top: .75rem;
    padding: .7rem .85rem;
    border: 1px solid #fecaca;
    border-radius: 8px;
    background: #fef2f2;
    color: #991b1b;
    font-size: .82rem;
    font-weight: 800;
}
.rcta-inline-alert.is-visible {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
}
.rcta-inline-alert i { margin-top: .12rem; }
.rcar-error {
    margin: 0 0 1rem;
    padding: 0.8rem 1rem;
    border: 1px solid #fecaca;
    border-radius: 8px;
    background: #fef2f2;
    color: #991b1b;
    font-weight: 750;
}
[data-theme="dark"] .rcar-header,
[data-theme="dark"] .rcar-stat,
[data-theme="dark"] .rcar-card { background: rgba(15,23,42,0.74); border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcar-card-head,
[data-theme="dark"] .rcar-record-head,
[data-theme="dark"] .rcar-toolbar,
[data-theme="dark"] .rcar-table th,
[data-theme="dark"] .rcar-table td { border-color: rgba(148,163,184,0.2); }
[data-theme="dark"] .rcar-table th,
[data-theme="dark"] .rcar-search,
[data-theme="dark"] .rcar-filter { background: rgba(148,163,184,0.07); }
@media (max-width: 767.98px) {
    .rcar-header,
    .rcar-card-head,
    .rcar-record-head,
    .rcar-toolbar { align-items: flex-start; flex-direction: column; }
    .rcar-stats { grid-template-columns: 1fr; }
    .rcar-sync,
    .rcar-filter,
    .rcar-search { width: 100%; }
}
</style>

<div class="rcar-wrap" data-rcar-endpoint="<?= rcE(BASE_URL . '/modules/crad/pages/approved-research.php?ajax=approved-research') ?>">
    <?php if (!$payload['ok']): ?>
        <div class="rcar-error">
            <?= smsIcon('exclamation-circle', ['class' => 'me-1']) ?><?= rcE((string) $payload['error']) ?>
        </div>
    <?php endif; ?>

    <header class="rcar-header">
        <div>
            <h1><?= smsIcon('clipboard-check', ['class' => 'me-2']) ?>Research Status</h1>
            <p>Approved research groups with official proposal and research group numbers.</p>
        </div>
        <div class="rcar-sync" id="rcarLastSync">Synced <?= rcE((string) $payload['last_sync']) ?></div>
    </header>

    <section class="rcar-card"
        data-rcta-endpoint="<?= rcE(BASE_URL . '/modules/crad/pages/approved-research.php?ajax=title-approvals') ?>"
        data-rcta-update="<?= rcE(BASE_URL . '/modules/crad/pages/approved-research.php?ajax=title-approval-status') ?>">
        <div class="rcar-record-head">
            <div>
                <h2 class="rcar-record-title"><?= smsIcon('file-signature') ?>Title Approval for Coordinator Review <span id="rctaPending" class="rcta-badge pending"><?= (int) $titleApprovalPayload['pending'] ?></span></h2>
                <span class="rcar-record-total" id="rctaRecordCount"><?= count($titleApprovalRows) ?> record<?= count($titleApprovalRows) === 1 ? '' : 's' ?></span>
            </div>
            <div class="rcar-sync" id="rctaLastSync">Synced <?= rcE((string) $titleApprovalPayload['last_sync']) ?></div>
        </div>
        <div class="rcar-toolbar">
            <input type="search" id="rctaSearch" class="rcar-search" placeholder="Search by title, student, or coordinator...">
            <select id="rctaStatusFilter" class="rcar-filter">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="returned">Returned</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div class="rcar-table-wrap">
            <table class="rcar-table">
                <thead>
                    <tr>
                        <th>Research Title</th>
                        <th>Student</th>
                        <th>Adviser Approved</th>
                        <th>Coordinator Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="rctaRows"></tbody>
            </table>
        </div>
        <div class="rcar-empty" id="rctaEmpty" hidden>No adviser-approved title approvals yet.</div>
    </section>
</div>

<div id="rctaModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.65);overflow:auto;padding:2rem 1rem;">
    <div style="max-width:780px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.38);">
        <div style="background:#17366f;color:#fff;padding:1rem 1.25rem;display:flex;justify-content:space-between;gap:1rem;align-items:center;">
            <div><strong>Title Approval Form</strong><div style="font-size:.78rem;color:#dbeafe;">Coordinator Review</div></div>
            <button id="rctaClose" class="rcta-btn primary" type="button"><?= smsIcon('times') ?> Close</button>
        </div>
        <div id="rctaBody" style="padding:1.5rem;color:#111;font-family:Arial,Helvetica,sans-serif;font-size:9pt;"></div>
        <div style="border-top:1px solid #e2e8f0;background:#f8fafc;padding:.9rem 1.25rem;">
            <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:center;">
                <div id="rctaStatus" style="font-weight:800;color:#475569;"></div>
                <div class="rcta-actions" id="rctaModalActions"></div>
            </div>
            <div id="rctaInlineAlert" class="rcta-inline-alert" role="alert">
                <?= smsIcon('exclamation-circle') ?>
                <span id="rctaInlineAlertText">Please complete the Research Coordinator Screening Yes/No checks first.</span>
            </div>
        </div>
    </div>
</div>

<div id="rctaSigModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.68);overflow:auto;padding:2rem 1rem;">
    <div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#065f46 0%,#047857 55%,#059669 100%);padding:1rem 1.4rem;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="color:rgba(209,250,229,.8);font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem;">Title Approval</div>
                <h3 style="margin:0;color:#fff;font-size:1.05rem;font-weight:800;"><?= smsIcon('signature', ['class' => 'me-2']) ?>Draw Your Signature</h3>
            </div>
            <button id="rctaSigClose" type="button" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:8px;padding:.35rem .8rem;cursor:pointer;font-weight:700;font-size:.82rem;">
                <?= smsIcon('times') ?>
            </button>
        </div>
        <div style="padding:1.25rem;">
            <p style="margin:0 0 .75rem;font-size:.82rem;color:#374151;font-weight:600;">
                Sign in the box below. Your signature will be saved to the Title Approval Form.
            </p>
            <div style="border:2px solid #d1d5db;border-radius:8px;background:#f9fafb;position:relative;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:.4rem .75rem;background:#f3f4f6;border-bottom:1px solid #e5e7eb;">
                    <span style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Coordinator Signature Pad (Draw Below)</span>
                    <button id="rctaSigClear" type="button" style="background:none;border:none;color:#7c3aed;font-size:.75rem;font-weight:800;cursor:pointer;padding:0;">Clear Pad</button>
                </div>
                <canvas id="rctaSigCanvas" style="display:block;width:100%;height:160px;background:#fff;touch-action:none;cursor:crosshair;"></canvas>
            </div>
            <div id="rctaSigError" style="display:none;margin-top:.6rem;padding:.5rem .75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;font-size:.8rem;font-weight:700;">
                <?= smsIcon('exclamation-circle', ['class' => 'me-1']) ?>Please provide your signature before approving.
            </div>
        </div>
        <div style="padding:.85rem 1.25rem;border-top:1px solid #e5e7eb;display:flex;align-items:center;justify-content:flex-end;gap:.65rem;background:#f9fafb;">
            <button id="rctaSigCancel" class="btn btn-outline-secondary btn-sm" type="button" style="font-size:.82rem;">Cancel</button>
            <button id="rctaSigApprove" class="btn btn-success btn-sm" type="button">Confirm & Approve</button>
        </div>
    </div>
</div>

<div id="rctaApproveConfirmModal" style="display:none;position:fixed;inset:0;z-index:10001;background:rgba(15,23,42,.72);overflow:auto;padding:2rem 1rem;">
    <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.38);">
        <div style="background:#17366f;color:#fff;padding:1rem 1.25rem;display:flex;justify-content:space-between;gap:1rem;align-items:center;">
            <div>
                <div style="color:rgba(219,234,254,.9);font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem;">TITLE APPROVAL</div>
                <h3 style="margin:0;font-size:1.05rem;font-weight:800;"><?= smsIcon('check-circle', ['class' => 'me-2']) ?>Confirm Approval</h3>
            </div>
            <button id="rctaApproveConfirmClose" type="button" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:8px;padding:.35rem .8rem;cursor:pointer;font-weight:700;font-size:.82rem;">
                <?= smsIcon('times') ?>
            </button>
        </div>
        <div style="padding:1.2rem;">
            <p style="margin:0 0 .75rem;color:#111827;font-size:1rem;font-weight:800;">Are you sure you want to approve this Title Approval Form?</p>
            <p style="margin:0 0 .55rem;color:#64748b;font-size:.9rem;line-height:1.55;font-weight:600;">Your signature will be saved and this approval will be recorded.</p>
            <p style="margin:0;color:#64748b;font-size:.9rem;line-height:1.55;font-weight:600;">Once confirmed, the existing Title Approval process will continue.</p>
        </div>
        <div style="padding:.9rem 1.2rem;border-top:1px solid #e5e7eb;background:#f8fafc;display:flex;justify-content:flex-end;gap:.65rem;">
            <button id="rctaApproveConfirmCancel" type="button" class="btn btn-outline-secondary btn-sm" style="font-size:.82rem;">Cancel</button>
            <button id="rctaApproveConfirmYes" type="button" class="btn btn-success btn-sm" style="font-size:.82rem;"><?= smsIcon('check', ['class' => 'me-1']) ?>Yes, Approve</button>
        </div>
    </div>
</div>

<script>
(() => {
    const root = document.querySelector('[data-rcar-endpoint]');
    if (!root) return;

    const endpoint = root.dataset.rcarEndpoint;
    const rowsBody = document.getElementById('rcarRows');
    const empty = document.getElementById('rcarEmpty');
    const search = document.getElementById('rcarSearch');
    const lastSync = document.getElementById('rcarLastSync');
    const total = document.getElementById('rcarTotal');
    const approved = document.getElementById('rcarApproved');
    const withAdviser = document.getElementById('rcarWithAdviser');
    let rows = <?= json_encode($approvedRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    if (!rowsBody || !empty || !lastSync || !total || !approved || !withAdviser) return;

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[char]);

    const formatDate = (value) => {
        if (!value) return 'For coordination';
        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return value;
        return parsed.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    };

    const rowMatches = (row, term) => {
        if (!term) return true;
        return [
            row.research_title,
            row.group_name,
            row.group_number,
            row.proposal_number,
            row.leader_name,
            row.leader_id,
            row.college_dept,
            row.adviser
        ].join(' ').toLowerCase().includes(term);
    };

    const render = () => {
        const term = (search?.value || '').trim().toLowerCase();
        const visibleRows = rows.filter((row) => rowMatches(row, term));
        rowsBody.innerHTML = visibleRows.map((row) => `
            <tr>
                <td>
                    <div class="rcar-title">${esc(row.group_name || 'Research Group')}</div>
                    <div>${esc(row.research_title || '')}</div>
                    <div class="rcar-muted">${esc(row.college_dept || '')} ${row.academic_year ? '&middot; ' + esc(row.academic_year) : ''}</div>
                </td>
                <td><span class="rcar-code"><?= smsIcon('file-signature') ?>${esc(row.proposal_number || '')}</span></td>
                <td><span class="rcar-code"><?= smsIcon('hashtag') ?>${esc(row.group_number || '')}</span></td>
                <td>
                    <div class="rcar-title">${esc(row.leader_name || '')}</div>
                    <div class="rcar-muted">${esc(row.leader_id || '')}</div>
                    <div class="rcar-muted">${esc(row.leader_email || '')}</div>
                </td>
                <td>${esc(row.adviser || 'For assignment')}</td>
                <td>${esc(formatDate(row.date_assigned || row.group_created_at))}</td>
                <td><span class="rcar-status"><?= smsIcon('check-circle') ?>${esc(row.display_status || row.proposal_status || 'Approved')}</span></td>
            </tr>
        `).join('');
        empty.hidden = visibleRows.length !== 0;
    };

    const refresh = async () => {
        try {
            const res = await fetch(endpoint, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.error || 'Failed to sync.');
            rows = Array.isArray(data.rows) ? data.rows : [];
            total.textContent = data.stats?.total ?? rows.length;
            approved.textContent = data.stats?.approved ?? 0;
            withAdviser.textContent = data.stats?.with_adviser ?? 0;
            lastSync.textContent = `Synced ${data.last_sync || 'just now'}`;
            render();
        } catch (error) {
            lastSync.textContent = 'Sync paused';
        }
    };

    search?.addEventListener('input', render);
    render();
    window.setInterval(refresh, 5000);
})();
</script>

<script>
(() => {
    const root = document.querySelector('[data-rcta-endpoint]');
    if (!root) return;
    const endpoint = root.dataset.rctaEndpoint;
    const updateUrl = root.dataset.rctaUpdate;
    const rowsEl = document.getElementById('rctaRows');
    const empty = document.getElementById('rctaEmpty');
    const pendingEl = document.getElementById('rctaPending');
    const recordCountEl = document.getElementById('rctaRecordCount');
    const searchEl = document.getElementById('rctaSearch');
    const statusFilterEl = document.getElementById('rctaStatusFilter');
    const lastSync = document.getElementById('rctaLastSync');
    const modal = document.getElementById('rctaModal');
    const bodyEl = document.getElementById('rctaBody');
    const statusEl = document.getElementById('rctaStatus');
    const actionsEl = document.getElementById('rctaModalActions');
    const inlineAlert = document.getElementById('rctaInlineAlert');
    const inlineAlertText = document.getElementById('rctaInlineAlertText');
    let rows = <?= json_encode($titleApprovalRows, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    let current = null;
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c]);
    const fmt = (v) => { const d = new Date(String(v || '').replace(' ', 'T')); return Number.isNaN(d.getTime()) ? (v || '') : d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); };
    const badgeClass = (s) => String(s || 'Pending').toLowerCase();
    const parseMembers = (v) => { try { return JSON.parse(v || '[]') || []; } catch(e) { return []; } };
    const parseScreening = (v) => { try { const out = JSON.parse(v || '{}') || {}; return typeof out === 'object' ? out : {}; } catch(e) { return {}; } };
    const screeningCriteria = [
        ['agenda_alignment', 'Title aligns with institutional research agenda'],
        ['feasible_original', 'Proposed study is feasible and original'],
        ['ethical_sdg', 'Ethical and SDG requirements are satisfied']
    ];
    const checkMark = (screening, key, value) => String(screening[key] || '').toLowerCase() === value ? '&#10003;' : '';
    const screeningComplete = (screening) => screeningCriteria.every(([key]) => ['yes', 'no'].includes(String(screening[key] || '').toLowerCase()));

    function rowHtml(r) {
        return `<tr>
            <td><div class="rcar-title">${esc(r.proposed_title)}</div><div class="rcar-muted">${esc(r.discipline_cluster)}</div></td>
            <td><div class="rcar-title">${esc(r.student_name)}</div><div class="rcar-muted">${esc(r.student_id)}</div></td>
            <td><div>${esc(r.adviser_name)}</div><div class="rcar-muted">${esc(fmt(r.reviewed_at))}</div></td>
            <td><span class="rcta-badge ${badgeClass(r.coordinator_status)}">${esc(r.coordinator_status || 'Pending')}</span></td>
            <td><button class="rcta-btn primary" data-open="${esc(r.id)}"><?= smsIcon('folder-open') ?> Open</button></td>
        </tr>`;
    }
    function visibleRows() {
        const term = (searchEl?.value || '').trim().toLowerCase();
        const status = (statusFilterEl?.value || '').trim().toLowerCase();
        return rows.filter((row) => {
            const textMatch = !term || [
                row.proposed_title,
                row.discipline_cluster,
                row.student_name,
                row.student_id,
                row.adviser_name,
                row.coordinator_name,
                row.coordinator_status
            ].join(' ').toLowerCase().includes(term);
            const statusMatch = !status || String(row.coordinator_status || '').toLowerCase() === status;
            return textMatch && statusMatch;
        });
    }
    function render() {
        const visible = visibleRows();
        rowsEl.innerHTML = visible.map(rowHtml).join('');
        if (recordCountEl) recordCountEl.textContent = `${visible.length} record${visible.length === 1 ? '' : 's'}`;
        empty.hidden = visible.length !== 0;
        empty.textContent = rows.length === 0 ? 'No adviser-approved title approvals yet.' : 'No records match your filters.';
    }
    function formHtml(r) {
        const members = parseMembers(r.members_json);
        const memberRows = Array.from({length:6}, (_, i) => {
            const m = members[i] || [];
            return `<tr>
                <td style="text-align:center;border:0.8px solid #222;padding:2mm 1.4mm;">${i+1}</td>
                <td style="border:0.8px solid #222;padding:2mm 1.4mm;font-weight:700;">${esc(m[0]||'')}</td>
                <td style="border:0.8px solid #222;padding:2mm 1.4mm;">${esc(m[1]||'')}</td>
            </tr>`;
        }).join('');
        const adviserSig = r.adviser_signature_data ? `<img src="${r.adviser_signature_data}" style="position:absolute;bottom:2px;left:0;right:0;width:100%;height:50px;object-fit:contain;object-position:center bottom;">` : '';
        const coordSig = r.coordinator_signature_data ? `<img src="${r.coordinator_signature_data}" style="position:absolute;bottom:2px;left:0;right:0;width:100%;height:50px;object-fit:contain;object-position:center bottom;">` : '';
        const screening = parseScreening(r.coordinator_screening_json);
        const canEditScreening = true;
        const screeningRows = screeningCriteria.map(([key, label]) => canEditScreening
            ? `<tr>
                <td style="border:0.8px solid #222;padding:2mm;">${label}</td>
                <td style="border:0.8px solid #222;text-align:center;"><input type="radio" name="rcta_${key}" value="yes" ${String(screening[key] || '').toLowerCase() === 'yes' ? 'checked' : ''} style="accent-color:#17366f;"></td>
                <td style="border:0.8px solid #222;text-align:center;"><input type="radio" name="rcta_${key}" value="no" ${String(screening[key] || '').toLowerCase() === 'no' ? 'checked' : ''} style="accent-color:#17366f;"></td>
            </tr>`
            : `<tr>
                <td style="border:0.8px solid #222;padding:2mm;">${label}</td>
                <td style="border:0.8px solid #222;text-align:center;font-weight:800;">${checkMark(screening, key, 'yes')}</td>
                <td style="border:0.8px solid #222;text-align:center;font-weight:800;">${checkMark(screening, key, 'no')}</td>
            </tr>`).join('');
        const bar = (label) => `<div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:3px 6px;margin-bottom:2mm;">${label}</div>`;
        return `<div style="font-family:Arial,Helvetica,sans-serif;color:#111;font-size:9pt;line-height:1.45;">
            <div style="display:grid;grid-template-columns:15mm 1fr auto;align-items:center;gap:3mm;padding-bottom:2.5mm;border-bottom:2px solid #17366f;margin-bottom:4mm;">
                <img src="<?= BASE_URL ?>/images/bcp-crest.png?v=20260811" style="width:42px;height:42px;object-fit:contain;border-radius:0;background:transparent;">
                <div style="font-size:7pt;line-height:1.4;"><strong style="display:block;font-size:10pt;color:#17366f;">BESTLINK COLLEGE OF THE PHILIPPINES</strong><span>#1071 Brgy. Kaligayahan, Quirino Highway, Novaliches, Quezon City</span><br><b>CENTER FOR RESEARCH AND DEVELOPMENT</b></div>
                <div style="font-size:7pt;font-weight:700;padding:3px 8px;border:1px solid #c3cede;border-radius:4px;background:#edf4ff;color:#17366f;">CRAD Form S2 V3</div>
            </div>
            <h2 style="text-align:center;font-size:13pt;font-weight:800;color:#17366f;letter-spacing:.05em;margin:0 0 4mm;text-transform:uppercase;">TITLE APPROVAL FORM</h2>
            <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:6mm;font-size:8.5pt;margin-bottom:4mm;padding:3mm 4mm;border:1px solid #b9c7da;border-radius:4px;background:#f8fbff;">
                <div><strong style="color:#17366f;">Date:</strong> ${esc(r.submission_date)}</div>
                <div><strong style="color:#17366f;">I. Department:</strong> ${esc(r.department)}</div>
            </div>
            ${bar('II. Students Information')}
            <table style="width:100%;border-collapse:collapse;font-size:8pt;margin-bottom:4mm;">
                <thead><tr><th style="border:0.8px solid #222;background:#e4edf9;color:#17366f;padding:2mm;width:6%;">No.</th><th style="border:0.8px solid #222;background:#e4edf9;color:#17366f;padding:2mm;">Name (Last, First, M.I.)</th><th style="border:0.8px solid #222;background:#e4edf9;color:#17366f;padding:2mm;width:20%;">Section</th></tr></thead>
                <tbody>${memberRows}</tbody>
            </table>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:3mm;margin-bottom:4mm;">
                <div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:7.5pt;"><div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">III. Research Discipline Cluster</div><div style="background:#dceaff;color:#12366f;padding:2px 5px;border-radius:3px;font-weight:700;">✓ ${esc(r.discipline_cluster)}</div></div>
                <div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:7.5pt;"><div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">IV. SDG Alignment</div><div style="background:#dceaff;color:#12366f;padding:2px 5px;border-radius:3px;font-weight:700;">✓ ${esc(r.primary_sdg)}</div></div>
            </div>
            <div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:7.5pt;margin-bottom:4mm;"><div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">V. Institutional Research Agenda Alignment</div><div style="background:#dceaff;color:#12366f;padding:2px 5px;border-radius:3px;font-weight:700;">✓ ${esc(r.research_agenda)}</div></div>
            ${bar('VI. Proposed Research Title')}
            <div style="padding:3mm 5mm;border:1px solid #b9c7da;border-left:3px solid #2457a7;border-radius:4px;background:#fbfdff;font-size:10pt;font-weight:800;text-align:center;color:#12294d;min-height:12mm;margin-bottom:4mm;">${esc(r.proposed_title)}</div>
            ${bar('VII. Sustainable Development Goal Justification')}
            <div style="padding:3mm 5mm;border:1px solid #b9c7da;border-left:3px solid #2457a7;border-radius:4px;background:#fbfdff;min-height:12mm;color:#24364f;margin-bottom:4mm;">${esc(r.sdg_justification)}</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:3mm;align-items:start;">
                <div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:8pt;align-self:start;height:auto;min-height:0;">
                    <div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">VIII. Research Coordinator Screening</div>
                    <table style="width:100%;border-collapse:collapse;font-size:7.5pt;"><thead><tr><th style="border:0.8px solid #222;padding:2mm;background:#e4edf9;">Evaluation Criteria</th><th style="border:0.8px solid #222;padding:2mm;background:#e4edf9;width:12%;">Yes</th><th style="border:0.8px solid #222;padding:2mm;background:#e4edf9;width:12%;">No</th></tr></thead><tbody>${screeningRows}</tbody></table>
                </div>
                <div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:8pt;text-align:center;background:#fbfdff;">
                    <div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;text-align:left;">IX. Approval (Name, signature and date)</div>
                    <div style="position:relative;width:80%;margin:4mm auto 0;height:54px;"><div style="position:absolute;bottom:0;left:0;right:0;border-bottom:1px solid #111;"></div>${adviserSig}</div><strong style="display:block;font-size:8.5pt;margin-top:1mm;">${esc(r.adviser_name)}</strong><span style="font-size:7.5pt;color:#555;">Research Adviser</span>
                    <div style="position:relative;width:80%;margin:5mm auto 0;height:54px;"><div style="position:absolute;bottom:0;left:0;right:0;border-bottom:1px solid #111;"></div>${coordSig}</div><strong style="display:block;font-size:8.5pt;margin-top:1mm;">${esc(r.coordinator_name || '')}</strong><span style="font-size:7.5pt;color:#555;">Program Research Coordinator</span>
                    <div style="margin:5mm 0 2mm;border-top:1px dashed #7c8da5;padding-top:3mm;text-align:left;font-size:7pt;color:#475569;">Received:</div><div style="border-bottom:1px solid #111;width:80%;margin:2mm auto;"></div><strong style="display:block;font-size:8.5pt;">Center for Research and Development</strong><span style="font-size:7.5pt;color:#555;">Center for Research and Development Office</span>
                </div>
            </div>
            ${r.coordinator_remarks ? `<div style="margin-top:3mm;padding:3mm 4mm;background:#fef9ec;border:1px solid #fbbf24;border-radius:4px;font-size:8pt;"><strong>Coordinator Remarks:</strong> ${esc(r.coordinator_remarks)}</div>` : ''}
        </div>`;
    }
    function openModal(r) {
        current = r;
        hideInlineAlert();
        bodyEl.innerHTML = formHtml(r);
        const screening = parseScreening(r.coordinator_screening_json);
        statusEl.innerHTML = `Coordinator Status: <span class="rcta-badge ${badgeClass(r.coordinator_status)}">${esc(r.coordinator_status || 'Pending')}</span>`;
        if ((r.coordinator_status || 'Pending') === 'Pending') {
            actionsEl.innerHTML = `<button id="rctaApprove" class="rcta-btn success"><?= smsIcon('signature') ?> Approve & Sign</button><button id="rctaReject" class="rcta-btn danger"><?= smsIcon('x') ?> Reject</button>`;
            document.getElementById('rctaApprove').onclick = () => {
                if (Object.keys(collectScreening()).length < screeningCriteria.length) {
                    showInlineAlert('Please complete the Research Coordinator Screening Yes/No checks first.');
                    return;
                }
                openSig();
            };
            document.getElementById('rctaReject').onclick = async () => {
                if (!window.confirm('Reject this title approval? This decision will be recorded immediately.')) return;
                await updateStatus(r.id, 'Rejected', '', '');
            };
        } else if ((r.coordinator_status || '') === 'Rejected') {
            actionsEl.innerHTML = `<button class="rcta-btn danger" type="button" disabled><?= smsIcon('x') ?> Rejected</button>`;
        } else {
            if (screeningComplete(screening)) {
                actionsEl.innerHTML = `<button class="rcta-btn success" type="button" disabled><?= smsIcon('check') ?> Done Save</button>`;
            } else {
                actionsEl.innerHTML = `<button id="rctaSaveScreening" class="rcta-btn primary"><?= smsIcon('save') ?> Save Screening</button>`;
                document.getElementById('rctaSaveScreening').onclick = (event) => {
                    const button = event.currentTarget;
                    screeningCriteria.forEach(([key]) => {
                        const yes = bodyEl.querySelector(`input[name="rcta_${key}"][value="yes"]`);
                        if (yes) yes.checked = true;
                    });
                    button.disabled = true;
                    button.className = 'rcta-btn success';
                    button.innerHTML = '<?= smsIcon('check') ?> Done Save';
                    updateStatus(r.id, 'Screening', '', '');
                };
            }
        }
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    function showInlineAlert(message) {
        if (!inlineAlert || !inlineAlertText) return;
        inlineAlertText.textContent = message;
        inlineAlert.classList.add('is-visible');
    }
    function hideInlineAlert() {
        if (inlineAlert) inlineAlert.classList.remove('is-visible');
    }
    function closeModal(){ modal.style.display='none'; document.body.style.overflow=''; current=null; hideInlineAlert(); }
    function collectScreening() {
        const out = {};
        screeningCriteria.forEach(([key]) => {
            const checked = bodyEl.querySelector(`input[name="rcta_${key}"]:checked`);
            if (checked) out[key] = checked.value;
        });
        if (Object.keys(out).length >= screeningCriteria.length) hideInlineAlert();
        return out;
    }
    async function updateStatus(id, status, remarks, sig) {
        const res = await fetch(updateUrl, {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},credentials:'same-origin',body:JSON.stringify({id,status,remarks,coordinator_signature_data:sig || '',coordinator_screening:collectScreening()})});
        const data = await res.json();
        if (!data.ok) { showInlineAlert('Could not update coordinator status. Please try again.'); return; }
        await refresh();
        const updated = rows.find(r => String(r.id) === String(id));
        if (updated) openModal(updated); else closeModal();
    }
    searchEl?.addEventListener('input', render);
    statusFilterEl?.addEventListener('change', render);
    rowsEl.addEventListener('click', e => {
        const btn = e.target.closest('[data-open]');
        if (!btn) return;
        const row = rows.find(r => String(r.id) === String(btn.dataset.open));
        if (row) openModal(row);
    });
    document.getElementById('rctaClose').onclick = closeModal;
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    async function refresh() {
        try {
            const res = await fetch(endpoint, {headers:{'Accept':'application/json'},cache:'no-store',credentials:'same-origin'});
            const data = await res.json();
            if (!data.ok) return;
            rows = Array.isArray(data.rows) ? data.rows : [];
            pendingEl.textContent = data.pending || 0;
            lastSync.textContent = 'Synced ' + (data.last_sync || 'just now');
            render();
        } catch(e) { lastSync.textContent = 'Sync paused'; }
    }

    const sigModal = document.getElementById('rctaSigModal');
    const canvas = document.getElementById('rctaSigCanvas');
    const ctx = canvas.getContext('2d');
    const sigError = document.getElementById('rctaSigError');
    const approveConfirmModal = document.getElementById('rctaApproveConfirmModal');
    const approveConfirmYes = document.getElementById('rctaApproveConfirmYes');
    let drawing = false;
    let pendingSignature = '';
    let approveRequestInFlight = false;
    function resize(){ const ratio=Math.max(devicePixelRatio||1,1); const rect=canvas.getBoundingClientRect(); canvas.width=rect.width*ratio; canvas.height=160*ratio; ctx.setTransform(ratio,0,0,ratio,0,0); ctx.strokeStyle='#0f172a'; ctx.lineWidth=2; ctx.lineCap='round'; }
    function pos(e){ const r=canvas.getBoundingClientRect(); const p=e.touches?e.touches[0]:e; return {x:p.clientX-r.left,y:p.clientY-r.top}; }
    function hasDraw(){ const px=ctx.getImageData(0,0,canvas.width,canvas.height).data; for(let i=3;i<px.length;i+=4){ if(px[i]>0) return true; } return false; }
    function openSig(){ pendingSignature=''; resetApproveConfirmButton(); if(sigError) sigError.style.display='none'; sigModal.style.display='block'; setTimeout(()=>{resize(); ctx.clearRect(0,0,canvas.width,canvas.height);},30); }
    function closeSig(){ if(approveRequestInFlight) return; sigModal.style.display='none'; if(sigError) sigError.style.display='none'; pendingSignature=''; }
    ['mousedown','touchstart'].forEach(ev => canvas.addEventListener(ev, e => { e.preventDefault(); drawing=true; const p=pos(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); }, {passive:false}));
    ['mousemove','touchmove'].forEach(ev => canvas.addEventListener(ev, e => { e.preventDefault(); if(!drawing)return; const p=pos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); }, {passive:false}));
    ['mouseup','mouseleave','touchend'].forEach(ev => canvas.addEventListener(ev, () => drawing=false));
    document.getElementById('rctaSigClose').onclick = closeSig;
    document.getElementById('rctaSigCancel').onclick = closeSig;
    document.getElementById('rctaSigClear').onclick = () => { ctx.clearRect(0,0,canvas.width,canvas.height); if(sigError) sigError.style.display='none'; pendingSignature=''; };
    function resetApproveConfirmButton(){
        approveRequestInFlight = false;
        approveConfirmYes.disabled = false;
        approveConfirmYes.innerHTML = '<?= smsIcon('check', ['class' => 'me-1']) ?>Yes, Approve';
    }
    function closeApproveConfirm(){
        if(approveRequestInFlight) return;
        approveConfirmModal.style.display = 'none';
    }
    document.getElementById('rctaApproveConfirmClose').onclick = closeApproveConfirm;
    document.getElementById('rctaApproveConfirmCancel').onclick = closeApproveConfirm;
    approveConfirmModal.addEventListener('click', e => { if(e.target === approveConfirmModal) closeApproveConfirm(); });
    approveConfirmYes.onclick = async () => {
        if(!current || !pendingSignature || approveRequestInFlight) return;
        approveRequestInFlight = true;
        approveConfirmYes.disabled = true;
        approveConfirmYes.innerHTML = 'Processing...';
        try {
            await updateStatus(current.id, 'Approved', '', pendingSignature);
            approveConfirmModal.style.display = 'none';
            sigModal.style.display = 'none';
        } catch(e) {
            showInlineAlert('Could not update coordinator status. Please try again.');
        } finally {
            resetApproveConfirmButton();
        }
    };
    document.getElementById('rctaSigApprove').onclick = () => {
        if (!hasDraw()) { if(sigError) sigError.style.display='block'; return; }
        if(sigError) sigError.style.display='none';
        const out = document.createElement('canvas'); out.width=400; out.height=100; const o=out.getContext('2d'); o.fillStyle='#fff'; o.fillRect(0,0,400,100); o.drawImage(canvas,0,0,canvas.width,canvas.height,0,0,400,100);
        pendingSignature = out.toDataURL('image/png');
        approveConfirmModal.style.display = 'block';
        setTimeout(() => approveConfirmYes.focus(), 30);
    };
    render();
    window.setInterval(refresh, 5000);
})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
