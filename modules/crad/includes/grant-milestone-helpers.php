<?php
/**
 * CRAD Grant — Funded project milestone tracking helpers.
 */
declare(strict_types=1);

require_once __DIR__ . '/grant-helpers.php';
require_once __DIR__ . '/grant-approval-helpers.php';

function grantProjectMilestonesUrl(): string
{
    return BASE_URL . '/modules/crad/pages/project-milestones.php';
}

function grantMilestoneStatusOptions(): array
{
    return ['Pending', 'In Progress', 'Completed'];
}

/**
 * @return list<array{order: int, name: string, status: string, completion_pct: float}>
 */
function grantDefaultFundedMilestoneDefinitions(): array
{
    return [
        ['order' => 1, 'name' => 'Project Start',     'status' => 'Completed',   'completion_pct' => 100.0],
        ['order' => 2, 'name' => 'Data Gathering',    'status' => 'In Progress', 'completion_pct' => 0.0],
        ['order' => 3, 'name' => 'Analysis',          'status' => 'Pending',   'completion_pct' => 0.0],
        ['order' => 4, 'name' => 'Final Report',      'status' => 'Pending',   'completion_pct' => 0.0],
        ['order' => 5, 'name' => 'Publication',       'status' => 'Pending',   'completion_pct' => 0.0],
    ];
}

function grantUserCanTrackFundedMilestones(): bool
{
    if (grantUserCanManage()) {
        return true;
    }

    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    return in_array($roleKey, ['finance', 'superadmin'], true);
}

function grantUserCanViewFundedMilestones(): bool
{
    return grantUserCanTrackFundedMilestones() || grantUserCanApply();
}

function grantRequireFundedMilestoneViewAccess(): void
{
    if (grantUserCanViewFundedMilestones()) {
        return;
    }

    grantRedirectUnauthorized();
}

function grantRequireFundedMilestoneTrackAccess(): void
{
    if (grantUserCanTrackFundedMilestones()) {
        return;
    }

    grantRedirectUnauthorized();
}

function grantEnsureMilestoneTables(PDO $crad): void
{
    grantEnsureTables($crad);

    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_funded_project_milestones (
            id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            grant_application_id  INT UNSIGNED NOT NULL,
            milestone_order       TINYINT UNSIGNED NOT NULL DEFAULT 1,
            milestone_name        VARCHAR(120) NOT NULL,
            due_date              DATE NULL DEFAULT NULL,
            completion_pct        DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            status                ENUM('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
            supporting_doc        VARCHAR(255) NULL DEFAULT NULL,
            supporting_doc_original VARCHAR(255) NULL DEFAULT NULL,
            remarks               TEXT NULL,
            updated_by_user_id    INT UNSIGNED NULL DEFAULT NULL,
            updated_by_name       VARCHAR(120) NULL DEFAULT NULL,
            created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_gfpm_app_order (grant_application_id, milestone_order),
            KEY idx_gfpm_application (grant_application_id),
            KEY idx_gfpm_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * @return array{ok: bool, error?: string}
 */
function grantInitializeFundedProjectMilestones(PDO $crad, int $applicationId): array
{
    grantEnsureMilestoneTables($crad);

    $stmt = $crad->prepare('SELECT id FROM grant_applications WHERE id = ? AND status = ? LIMIT 1');
    $stmt->execute([$applicationId, grantStatusApprovedFunded()]);
    if (!$stmt->fetchColumn()) {
        return ['ok' => false, 'error' => 'Approved & Funded application not found.'];
    }

    $existing = $crad->prepare('SELECT COUNT(*) FROM grant_funded_project_milestones WHERE grant_application_id = ?');
    $existing->execute([$applicationId]);
    if ((int) $existing->fetchColumn() > 0) {
        return ['ok' => true];
    }

    try {
        $crad->beginTransaction();
        $insert = $crad->prepare("
            INSERT INTO grant_funded_project_milestones
                (grant_application_id, milestone_order, milestone_name, completion_pct, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");

        foreach (grantDefaultFundedMilestoneDefinitions() as $def) {
            $insert->execute([
                $applicationId,
                (int) $def['order'],
                (string) $def['name'],
                (float) $def['completion_pct'],
                (string) $def['status'],
            ]);
        }

        $crad->commit();

        return ['ok' => true];
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantInitializeFundedProjectMilestones: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to initialize project milestones.'];
    }
}

function grantBackfillFundedProjectMilestones(PDO $crad): void
{
    grantEnsureMilestoneTables($crad);

    $stmt = $crad->prepare('SELECT id FROM grant_applications WHERE status = ? ORDER BY id ASC');
    $stmt->execute([grantStatusApprovedFunded()]);

    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $applicationId) {
        grantInitializeFundedProjectMilestones($crad, (int) $applicationId);
    }
}

/**
 * @return list<array<string, mixed>>
 */
function grantGetFundedMilestoneOverview(PDO $crad): array
{
    grantEnsureMilestoneTables($crad);
    grantBackfillFundedProjectMilestones($crad);

    $canTrack = grantUserCanTrackFundedMilestones();
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    $sql = "
        SELECT ga.id AS grant_application_id,
               ga.proposal_reference,
               ga.research_title,
               ga.applicant_name,
               ga.college_dept,
               ga.updated_at AS funded_at,
               go.funding_title
          FROM grant_applications ga
         INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
         WHERE ga.status = ?
    ";
    $params = [grantStatusApprovedFunded()];

    if (!$canTrack && grantUserCanApply()) {
        $sql .= ' AND ga.applicant_user_id = ?';
        $params[] = $userId;
    } elseif (!$canTrack) {
        return [];
    }

    $sql .= ' ORDER BY ga.updated_at DESC, ga.id DESC';

    $stmt = $crad->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($rows === []) {
        return [];
    }

    $ids = array_map(static fn(array $row): int => (int) ($row['grant_application_id'] ?? 0), $rows);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statsStmt = $crad->prepare("
        SELECT grant_application_id,
               COUNT(*) AS milestone_count,
               SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_count,
               SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_count,
               SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
               ROUND(AVG(completion_pct), 1) AS avg_completion_pct,
               MAX(updated_at) AS milestones_updated_at
          FROM grant_funded_project_milestones
         WHERE grant_application_id IN ({$placeholders})
         GROUP BY grant_application_id
    ");
    $statsStmt->execute($ids);
    $statsMap = [];
    foreach ($statsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $statRow) {
        $statsMap[(int) ($statRow['grant_application_id'] ?? 0)] = $statRow;
    }

    foreach ($rows as &$row) {
        $appId = (int) ($row['grant_application_id'] ?? 0);
        $stats = $statsMap[$appId] ?? [];
        $row['milestone_count'] = (int) ($stats['milestone_count'] ?? 0);
        $row['completed_count'] = (int) ($stats['completed_count'] ?? 0);
        $row['in_progress_count'] = (int) ($stats['in_progress_count'] ?? 0);
        $row['pending_count'] = (int) ($stats['pending_count'] ?? 0);
        $row['avg_completion_pct'] = (float) ($stats['avg_completion_pct'] ?? 0);
        $row['milestones_updated_at'] = (string) ($stats['milestones_updated_at'] ?? '');
        $row['progress_label'] = grantFundedMilestoneProgressLabel($row);
    }
    unset($row);

    return $rows;
}

/**
 * @param array<string, mixed> $row
 */
function grantFundedMilestoneProgressLabel(array $row): string
{
    $total = (int) ($row['milestone_count'] ?? 0);
    $completed = (int) ($row['completed_count'] ?? 0);
    if ($total <= 0) {
        return 'Awaiting Milestones';
    }
    if ($completed >= $total) {
        return 'All Milestones Completed';
    }
    if ((int) ($row['in_progress_count'] ?? 0) > 0) {
        return 'In Progress';
    }

    return 'Pending';
}

/**
 * @return array<string, mixed>|null
 */
function grantGetFundedMilestoneDetail(PDO $crad, int $applicationId): ?array
{
    $overviewRows = grantGetFundedMilestoneOverview($crad);
    $application = null;
    foreach ($overviewRows as $row) {
        if ((int) ($row['grant_application_id'] ?? 0) === $applicationId) {
            $application = $row;
            break;
        }
    }
    if ($application === null) {
        return null;
    }

    $stmt = $crad->prepare("
        SELECT *
          FROM grant_funded_project_milestones
         WHERE grant_application_id = ?
         ORDER BY milestone_order ASC
    ");
    $stmt->execute([$applicationId]);
    $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($milestones as &$milestone) {
        $milestone['document_url'] = grantMilestoneDocumentUrl((int) ($milestone['id'] ?? 0));
        $milestone['has_document'] = trim((string) ($milestone['supporting_doc'] ?? '')) !== '';
    }
    unset($milestone);

    $evidence = [];
    if (grantUserCanTrackFundedMilestones()) {
        if (!function_exists('grantGetFundedResearchEvidence')) {
            require_once __DIR__ . '/grant-funded-research-helpers.php';
        }
        $evidence = grantGetFundedResearchEvidence($crad, $applicationId);
    }

    return [
        'application' => $application,
        'milestones'  => $milestones,
        'evidence'    => $evidence,
        'can_track'   => grantUserCanTrackFundedMilestones(),
    ];
}

function grantMilestoneDocumentUrl(int $milestoneId): string
{
    if ($milestoneId <= 0) {
        return '';
    }

    return grantCradScriptUrl('grant-milestone-file.php', ['id' => $milestoneId]);
}

/**
 * @param list<array<string, mixed>> $rows
 */
function grantMilestoneOverviewFingerprint(array $rows): string
{
    $parts = [];
    foreach ($rows as $row) {
        $parts[] = implode(':', [
            $row['grant_application_id'] ?? '',
            $row['completed_count'] ?? '',
            $row['in_progress_count'] ?? '',
            $row['pending_count'] ?? '',
            $row['avg_completion_pct'] ?? '',
            $row['milestones_updated_at'] ?? '',
            $row['funded_at'] ?? '',
            $row['evidence_count'] ?? '',
            $row['disbursement_updated_at'] ?? '',
            $row['released_count'] ?? '',
        ]);
    }

    return md5(implode('|', $parts));
}

/**
 * @param array<string, mixed>|null $detail
 */
function grantMilestoneDetailFingerprint(?array $detail): string
{
    if ($detail === null) {
        return '';
    }

    $parts = [!empty($detail['can_track']) ? '1' : '0'];
    foreach ($detail['milestones'] ?? [] as $milestone) {
        $parts[] = implode(':', [
            $milestone['id'] ?? '',
            $milestone['status'] ?? '',
            $milestone['completion_pct'] ?? '',
            $milestone['due_date'] ?? '',
            $milestone['remarks'] ?? '',
            $milestone['supporting_doc'] ?? '',
            $milestone['updated_at'] ?? '',
        ]);
    }

    foreach ($detail['evidence'] ?? [] as $evidence) {
        $parts[] = implode(':', [
            'ev',
            $evidence['id'] ?? '',
            $evidence['milestone_id'] ?? '',
            $evidence['evidence_title'] ?? '',
            $evidence['status'] ?? '',
            $evidence['created_at'] ?? '',
        ]);
    }

    return md5(implode('|', $parts));
}

function grantNormalizeMilestoneStatus(string $status): string
{
    $status = trim($status);
    if (in_array($status, grantMilestoneStatusOptions(), true)) {
        return $status;
    }

    return 'Pending';
}

function grantMilestoneStatusClass(string $status): string
{
    return match (grantNormalizeMilestoneStatus($status)) {
        'Completed'   => 'completed',
        'In Progress' => 'in-progress',
        default       => 'pending',
    };
}

/**
 * @param array<string, mixed> $input
 * @return array{ok: bool, error?: string, detail?: array<string, mixed>|null}
 */
function grantUpdateFundedProjectMilestone(
    PDO $crad,
    int $milestoneId,
    array $input,
    int $userId,
    string $userName
): array {
    grantEnsureMilestoneTables($crad);

    if (!grantUserCanTrackFundedMilestones()) {
        return ['ok' => false, 'error' => 'You are not authorized to update milestones.'];
    }

    $stmt = $crad->prepare("
        SELECT m.*, ga.status AS application_status
          FROM grant_funded_project_milestones m
         INNER JOIN grant_applications ga ON ga.id = m.grant_application_id
         WHERE m.id = ?
         LIMIT 1
    ");
    $stmt->execute([$milestoneId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['ok' => false, 'error' => 'Milestone not found.'];
    }

    if ((string) ($row['application_status'] ?? '') !== grantStatusApprovedFunded()) {
        return ['ok' => false, 'error' => 'Milestone tracking is only available for funded projects.'];
    }

    $status = grantNormalizeMilestoneStatus((string) ($input['status'] ?? $row['status'] ?? 'Pending'));
    $completion = (float) ($input['completion_pct'] ?? $row['completion_pct'] ?? 0);
    $completion = max(0, min(100, round($completion, 2)));

    if ($status === 'Completed') {
        $completion = 100.0;
    } elseif ($status === 'Pending' && !isset($input['completion_pct'])) {
        $completion = 0.0;
    } elseif ($status === 'In Progress' && $completion <= 0) {
        $completion = max($completion, 1.0);
    }

    $dueDate = trim((string) ($input['due_date'] ?? ''));
    $dueDate = $dueDate !== '' ? $dueDate : null;
    $remarks = trim((string) ($input['remarks'] ?? '')) ?: null;

    try {
        $crad->prepare("
            UPDATE grant_funded_project_milestones
               SET due_date = ?,
                   completion_pct = ?,
                   status = ?,
                   remarks = ?,
                   updated_by_user_id = ?,
                   updated_by_name = ?,
                   updated_at = NOW()
             WHERE id = ?
        ")->execute([
            $dueDate,
            $completion,
            $status,
            $remarks,
            $userId > 0 ? $userId : null,
            $userName,
            $milestoneId,
        ]);

        $applicationId = (int) ($row['grant_application_id'] ?? 0);

        $updatedRow = $crad->prepare('SELECT * FROM grant_funded_project_milestones WHERE id = ? LIMIT 1');
        $updatedRow->execute([$milestoneId]);
        $milestoneRow = $updatedRow->fetch(PDO::FETCH_ASSOC) ?: $row;
        grantNotifyApplicantMilestoneUpdated($crad, $applicationId, $milestoneRow, $userName);

        return [
            'ok'     => true,
            'detail' => grantGetFundedMilestoneDetail($crad, $applicationId),
        ];
    } catch (Throwable $e) {
        error_log('grantUpdateFundedProjectMilestone: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to update milestone.'];
    }
}

/**
 * @param array<string, mixed> $file
 * @return array{ok: bool, error?: string, detail?: array<string, mixed>|null}
 */
function grantUploadFundedMilestoneDocument(
    PDO $crad,
    int $milestoneId,
    array $file,
    int $userId,
    string $userName
): array {
    grantEnsureMilestoneTables($crad);

    if (!grantUserCanTrackFundedMilestones()) {
        return ['ok' => false, 'error' => 'You are not authorized to upload milestone documents.'];
    }

    $stmt = $crad->prepare('SELECT * FROM grant_funded_project_milestones WHERE id = ? LIMIT 1');
    $stmt->execute([$milestoneId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['ok' => false, 'error' => 'Milestone not found.'];
    }

    if (empty($file['ok'])) {
        return ['ok' => false, 'error' => $file['error'] ?? 'Supporting document is required.'];
    }

    try {
        $crad->prepare("
            UPDATE grant_funded_project_milestones
               SET supporting_doc = ?,
                   supporting_doc_original = ?,
                   updated_by_user_id = ?,
                   updated_by_name = ?,
                   updated_at = NOW()
             WHERE id = ?
        ")->execute([
            $file['stored_name'] ?? null,
            $file['original_name'] ?? null,
            $userId > 0 ? $userId : null,
            $userName,
            $milestoneId,
        ]);

        $applicationId = (int) ($row['grant_application_id'] ?? 0);

        $updatedRow = $crad->prepare('SELECT * FROM grant_funded_project_milestones WHERE id = ? LIMIT 1');
        $updatedRow->execute([$milestoneId]);
        $milestoneRow = $updatedRow->fetch(PDO::FETCH_ASSOC) ?: $row;
        grantNotifyApplicantMilestoneUpdated($crad, $applicationId, $milestoneRow, $userName);

        return [
            'ok'     => true,
            'detail' => grantGetFundedMilestoneDetail($crad, $applicationId),
        ];
    } catch (Throwable $e) {
        error_log('grantUploadFundedMilestoneDocument: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to upload supporting document.'];
    }
}

/**
 * @param array<string, mixed> $milestone
 */
function grantNotifyApplicantMilestoneUpdated(
    PDO $crad,
    int $applicationId,
    array $milestone,
    string $updatedByName
): void {
    require_once __DIR__ . '/grant-evaluation-helpers.php';
    if (!function_exists('grantFundedResearchUrl')) {
        require_once __DIR__ . '/grant-funded-research-helpers.php';
    }

    $stmt = $crad->prepare('SELECT * FROM grant_applications WHERE id = ? LIMIT 1');
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        return;
    }

    $recipientUserId = (int) ($application['applicant_user_id'] ?? 0);
    if ($recipientUserId <= 0) {
        return;
    }

    $refLabel = trim((string) ($application['proposal_reference'] ?? ''));
    if ($refLabel === '') {
        $refLabel = 'Proposal #' . $applicationId;
    }

    $milestoneLabel = (string) ($milestone['milestone_name'] ?? ('Milestone ' . (int) ($milestone['tranche_number'] ?? 0)));
    $status = (string) ($milestone['status'] ?? 'Pending');
    $completion = number_format((float) ($milestone['completion_pct'] ?? 0), 0);

    $body = sprintf(
        '%s — %s updated to %s (%s%%). Updated by %s. View Funded Research for timeline and requirements.',
        $refLabel,
        $milestoneLabel,
        $status,
        $completion,
        $updatedByName !== '' ? $updatedByName : 'CRAD Staff'
    );

    $recipientRole = 'student';
    if (function_exists('db')) {
        $mainDb = db();
        if ($mainDb) {
            $userStmt = $mainDb->prepare('SELECT role_key FROM users WHERE id = ? LIMIT 1');
            $userStmt->execute([$recipientUserId]);
            $recipientRole = (string) ($userStmt->fetchColumn() ?: 'student');
        }
    }

    $eventKey = 'grant-proposal:grant_milestone_update:'
        . $applicationId
        . ':m' . (int) ($milestone['id'] ?? 0)
        . ':s' . strtolower(preg_replace('/[^a-z0-9]+/i', '', $status))
        . ':p' . (int) $completion;

    grantInsertGrantProposalNotification(
        $crad,
        $applicationId,
        $recipientUserId,
        $recipientRole,
        'grant_milestone_update',
        $eventKey,
        'Milestone Updated',
        $body,
        grantFundedResearchUrl($applicationId)
    );
}
