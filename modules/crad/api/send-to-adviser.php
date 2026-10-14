<?php
/**
 * CRAD API — Send Title Approval Form to Adviser
 *
 * POST /modules/crad/api/send-to-adviser.php
 * 1. Validates the adviser exists in sms2_users
 * 2. Returns {ok:false, no_account:true} when adviser has no account
 * 3. Inserts into `crad_title_approvals` when all is well
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once __DIR__ . '/../includes/title-approval-assignees.php';
require_once __DIR__ . '/../includes/research-group-flow.php';

header('Content-Type: application/json; charset=utf-8');

function saJson(bool $ok, string $message, array $extra = []): never
{
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
    exit;
}

/* ── auth ────────────────────────────────────────────────── */
if (!isAuthenticated()) {
    http_response_code(401);
    saJson(false, 'Not authenticated.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    saJson(false, 'Method not allowed.');
}

/* ── parse body ──────────────────────────────────────────── */
$body = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    saJson(false, 'Invalid JSON body.');
}

$studentId     = trim((string) ($body['student_id']       ?? ''));
$studentUserId = isset($body['student_user_id']) && $body['student_user_id'] !== ''
                    ? (int) $body['student_user_id'] : null;
$studentName   = trim((string) ($body['student_name']     ?? ''));
$adviserName   = '';
$adviserEmail  = '';
$coordName     = '';
$title         = trim((string) ($body['research_title']   ?? ''));
$dept          = trim((string) ($body['department']       ?? ''));
$dateStr       = trim((string) ($body['submission_date']  ?? date('Y-m-d')));
$discipline    = trim((string) ($body['discipline']       ?? ''));
$sdg           = trim((string) ($body['primary_sdg']      ?? ''));
$agenda        = trim((string) ($body['research_agenda']  ?? ''));
$justification = trim((string) ($body['justification']    ?? ''));
$membersRaw    = $body['members'] ?? '[]';
$submissionId  = (int) ($body['submission_id'] ?? 0);

if ($title === '') {
    http_response_code(422);
    saJson(false, 'Research title is required.');
}

/* ── Normalise date ──────────────────────────────────────── */
$submissionDate = date('Y-m-d', strtotime($dateStr) ?: time());

/* ── Normalise members JSON ──────────────────────────────── */
if (is_string($membersRaw)) {
    $dec = json_decode($membersRaw, true);
    $membersJson = json_encode(is_array($dec) ? $dec : [], JSON_UNESCAPED_UNICODE);
} else {
    $membersJson = json_encode(is_array($membersRaw) ? $membersRaw : [], JSON_UNESCAPED_UNICODE);
}

/* ── crad_db insert ──────────────────────────────────────── */
try {
    $pdo = getCradDatabaseConnection();
} catch (Throwable $e) {
    http_response_code(503);
    saJson(false, 'Database unavailable: ' . $e->getMessage());
}

$rgGate = cradRgFlowTitleApprovalGate($pdo, $studentId);
if (empty($rgGate['ok'])) {
    http_response_code(422);
    saJson(false, (string) ($rgGate['message'] ?? 'Complete Research Group, coordinator/adviser assignment, and confirmation before Title Approval.'));
}
// Prefer Research Group members for title approval payload (source of truth).
$rgMembersPayload = $rgGate['members'] ?? [];
if (!is_array($rgMembersPayload) || $rgMembersPayload === []) {
    try {
        $rgRow = cradRgFlowGetStudentGroup($pdo, $studentId);
        if ($rgRow && function_exists('cradRgFlowMembersAsTitleJson')) {
            $rgMembersPayload = cradRgFlowMembersAsTitleJson($pdo, (int) $rgRow['id']);
        }
    } catch (Throwable $e) {
        error_log('RG members prefer failed: ' . $e->getMessage());
    }
}
if (is_array($rgMembersPayload) && $rgMembersPayload !== []) {
    $membersJson = json_encode($rgMembersPayload, JSON_UNESCAPED_UNICODE);
}

$official = cradStudentOfficialAssignees($pdo, $studentId);
$adviserName = trim((string) ($official['adviser_name'] ?? ''));
$adviserEmail = trim((string) ($official['adviser_email'] ?? ''));
$coordName = trim((string) ($official['coordinator_name'] ?? ''));
if ($adviserName === '' || $coordName === '' || empty($official['ready'])) {
    http_response_code(422);
    saJson(false, 'Waiting for coordinator and adviser assignment or confirmation. Names stay blank on the Title Approval Form until both are assigned and confirmed.');
}

/* ── Check adviser has an account in sms2_db ─────────────── */
try {
    $mainPdo = db();
    if ($mainPdo) {
        $chk = $mainPdo->prepare(
            "SELECT id, full_name, email FROM `sms2_users`
             WHERE status = 'active'
               AND (
                    (email != '' AND LOWER(email) = LOWER(:email))
                 OR LOWER(full_name) = LOWER(:name)
               )
             LIMIT 1"
        );
        $chk->execute([':email' => $adviserEmail, ':name' => $adviserName]);
        $adviserUser = $chk->fetch();

        if (!$adviserUser) {
            saJson(false, 'The message cannot be sent because the adviser does not have an account.', [
                'no_account' => true,
                'adviser'    => $adviserName ?: $adviserEmail,
            ]);
        }

        $adviserName  = (string) $adviserUser['full_name'];
        $adviserEmail = (string) $adviserUser['email'];
    }
} catch (Throwable $e) {
    error_log('Adviser account check failed: ' . $e->getMessage());
}

try {
    $sigCol = $pdo->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'adviser_signature_data'")->fetch();
    if (!$sigCol) {
        $pdo->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN adviser_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER adviser_remarks");
    }
    $workflowColumns = [
        'coordinator_status' => "ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_status VARCHAR(30) NOT NULL DEFAULT 'Not Ready' AFTER adviser_signature_data",
        'coordinator_remarks' => "ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_remarks TEXT NULL DEFAULT NULL AFTER coordinator_status",
        'coordinator_screening_json' => "ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_screening_json TEXT NULL DEFAULT NULL AFTER coordinator_remarks",
        'coordinator_signature_data' => "ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER coordinator_remarks",
        'coordinator_reviewed_at' => "ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_reviewed_at DATETIME NULL DEFAULT NULL AFTER coordinator_signature_data",
        'crad_status' => "ALTER TABLE `crad_title_approvals` ADD COLUMN crad_status VARCHAR(30) NOT NULL DEFAULT 'Not Ready' AFTER coordinator_reviewed_at",
        'crad_signature_data' => "ALTER TABLE `crad_title_approvals` ADD COLUMN crad_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER crad_status",
        'crad_reviewed_at' => "ALTER TABLE `crad_title_approvals` ADD COLUMN crad_reviewed_at DATETIME NULL DEFAULT NULL AFTER crad_signature_data",
    ];
    foreach ($workflowColumns as $column => $sql) {
        if (!$pdo->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE " . $pdo->quote($column))->fetch()) {
            $pdo->exec($sql);
        }
    }
} catch (Throwable $e) {
    error_log('Title approval schema check failed: ' . $e->getMessage());
}

/* Resubmit returned/current row instead of creating a duplicate. */
$existingId = 0;
if ($submissionId > 0) {
    $byId = $pdo->prepare(
        "SELECT id, status, coordinator_status
         FROM `crad_title_approvals`
         WHERE id = :id AND student_id = :sid
         LIMIT 1"
    );
    $byId->execute([':id' => $submissionId, ':sid' => $studentId]);
    $existing = $byId->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($existing) {
        $existingId = (int) $existing['id'];
        if ((string) $existing['status'] === 'Pending') {
            saJson(true, 'Already sent.', ['already_sent' => true, 'submission_id' => $existingId]);
        }
        if ((string) $existing['status'] !== 'Returned' && (string) ($existing['coordinator_status'] ?? '') !== 'Returned') {
            saJson(true, 'Already sent.', ['already_sent' => true, 'submission_id' => $existingId]);
        }
    }
}

if ($existingId === 0) {
    $findExisting = $pdo->prepare(
        "SELECT id, status, coordinator_status
         FROM `crad_title_approvals`
         WHERE student_id = :sid AND proposed_title = :title
         ORDER BY id DESC
         LIMIT 1"
    );
    $findExisting->execute([':sid' => $studentId, ':title' => $title]);
    $existing = $findExisting->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($existing) {
        $existingId = (int) $existing['id'];
        if ((string) $existing['status'] === 'Pending') {
            saJson(true, 'Already sent.', ['already_sent' => true, 'submission_id' => $existingId]);
        }
        if ((string) $existing['status'] !== 'Returned' && (string) ($existing['coordinator_status'] ?? '') !== 'Returned') {
            saJson(true, 'Already sent.', ['already_sent' => true, 'submission_id' => $existingId]);
        }
    }
}

if ($existingId > 0) {
    $update = $pdo->prepare("
        UPDATE `crad_title_approvals`
        SET student_user_id = :student_user_id,
            student_name = :student_name,
            submission_date = :submission_date,
            department = :department,
            proposed_title = :proposed_title,
            discipline_cluster = :discipline_cluster,
            primary_sdg = :primary_sdg,
            research_agenda = :research_agenda,
            sdg_justification = :sdg_justification,
            members_json = :members_json,
            adviser_name = :adviser_name,
            adviser_email = :adviser_email,
            coordinator_name = :coordinator_name,
            status = 'Pending',
            adviser_remarks = NULL,
            adviser_signature_data = NULL,
            coordinator_status = 'Not Ready',
            coordinator_remarks = NULL,
            coordinator_screening_json = NULL,
            coordinator_signature_data = NULL,
            coordinator_reviewed_at = NULL,
            crad_status = 'Not Ready',
            crad_signature_data = NULL,
            crad_reviewed_at = NULL,
            reviewed_at = NULL,
            sent_at = NOW()
        WHERE id = :id
          AND student_id = :student_id
    ");
    $update->execute([
        ':student_user_id'    => $studentUserId,
        ':student_name'       => $studentName,
        ':submission_date'    => $submissionDate,
        ':department'         => $dept,
        ':proposed_title'     => $title,
        ':discipline_cluster' => $discipline,
        ':primary_sdg'        => $sdg,
        ':research_agenda'    => $agenda,
        ':sdg_justification'  => $justification,
        ':members_json'       => $membersJson,
        ':adviser_name'       => $adviserName,
        ':adviser_email'      => $adviserEmail,
        ':coordinator_name'   => $coordName,
        ':id'                 => $existingId,
        ':student_id'         => $studentId,
    ]);

    saJson(true, 'Title approval form resubmitted to adviser.', [
        'submission_id' => $existingId,
        'adviser'       => $adviserName,
        'resubmitted'   => true,
        'sent_at'       => date('Y-m-d H:i:s'),
    ]);
}

$stmt = $pdo->prepare("
    INSERT INTO `crad_title_approvals`
        (student_id, student_user_id, student_name, submission_date, department,
         proposed_title, discipline_cluster, primary_sdg, research_agenda,
         sdg_justification, members_json, adviser_name, adviser_email,
         coordinator_name, status, sent_at)
    VALUES
        (:student_id, :student_user_id, :student_name, :submission_date, :department,
         :proposed_title, :discipline_cluster, :primary_sdg, :research_agenda,
         :sdg_justification, :members_json, :adviser_name, :adviser_email,
         :coordinator_name, 'Pending', NOW())
");

$stmt->execute([
    ':student_id'         => $studentId,
    ':student_user_id'    => $studentUserId,
    ':student_name'       => $studentName,
    ':submission_date'    => $submissionDate,
    ':department'         => $dept,
    ':proposed_title'     => $title,
    ':discipline_cluster' => $discipline,
    ':primary_sdg'        => $sdg,
    ':research_agenda'    => $agenda,
    ':sdg_justification'  => $justification,
    ':members_json'       => $membersJson,
    ':adviser_name'       => $adviserName,
    ':adviser_email'      => $adviserEmail,
    ':coordinator_name'   => $coordName,
]);

saJson(true, 'Title approval form sent to adviser.', [
    'submission_id' => (int) $pdo->lastInsertId(),
    'adviser'       => $adviserName,
    'sent_at'       => date('Y-m-d H:i:s'),
]);
