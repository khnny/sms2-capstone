<?php
/**
 * Researcher — Conduct Funded Research helpers
 */
declare(strict_types=1);

require_once __DIR__ . '/grant-helpers.php';
require_once __DIR__ . '/grant-funding-helpers.php';
require_once __DIR__ . '/grant-milestone-helpers.php';

function grantFundedResearchUrl(int $applicationId = 0): string
{
    $url = BASE_URL . '/modules/crad/pages/funded-research.php';
    if ($applicationId > 0) {
        $url .= '?id=' . $applicationId;
    }

    return $url;
}

function grantUserCanConductFundedResearch(): bool
{
    return grantUserCanApply();
}

function grantUserCanViewFundedResearchDashboard(): bool
{
    return grantUserCanConductFundedResearch() || grantUserCanTrackFundedMilestones();
}

function grantRequireFundedResearchAccess(): void
{
    if (grantUserCanViewFundedResearchDashboard()) {
        return;
    }

    grantRedirectUnauthorized();
}

function grantEnsureFundedResearchTables(PDO $crad): void
{
    grantEnsureMilestoneTables($crad);
    grantEnsureFundingTables($crad);

    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $crad->exec("
        CREATE TABLE IF NOT EXISTS `crad_grant_funded_progress_evidence` (
            id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            grant_application_id  INT UNSIGNED NOT NULL,
            milestone_id          INT UNSIGNED NULL DEFAULT NULL,
            evidence_title        VARCHAR(200) NOT NULL DEFAULT '',
            notes                 TEXT NULL,
            file_path             VARCHAR(255) NULL DEFAULT NULL,
            file_original         VARCHAR(255) NULL DEFAULT NULL,
            submitted_by_user_id  INT UNSIGNED NULL DEFAULT NULL,
            submitted_by_name     VARCHAR(120) NULL DEFAULT NULL,
            status                ENUM('Submitted','Acknowledged') NOT NULL DEFAULT 'Submitted',
            created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_gfpe_application (grant_application_id),
            KEY idx_gfpe_milestone (milestone_id),
            KEY idx_gfpe_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * @return list<array<string, mixed>>
 */
function grantGetFundedResearchOverview(PDO $crad): array
{
    grantEnsureFundedResearchTables($crad);

    $rows = grantGetFundedMilestoneOverview($crad);
    if ($rows === []) {
        return [];
    }

    $ids = array_map(static fn(array $row): int => (int) ($row['grant_application_id'] ?? 0), $rows);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $evidenceMap = [];
    $evStmt = $crad->prepare("
        SELECT grant_application_id, COUNT(*) AS evidence_count, MAX(created_at) AS evidence_updated_at
          FROM `crad_grant_funded_progress_evidence`
         WHERE grant_application_id IN ({$placeholders})
         GROUP BY grant_application_id
    ");
    $evStmt->execute($ids);
    foreach ($evStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $evRow) {
        $evidenceMap[(int) ($evRow['grant_application_id'] ?? 0)] = $evRow;
    }

    $fundingRows = grantGetFundedDisbursementOverview($crad);
    $fundingMap = [];
    foreach ($fundingRows as $fundingRow) {
        $fundingMap[(int) ($fundingRow['grant_application_id'] ?? 0)] = $fundingRow;
    }

    foreach ($rows as &$row) {
        $appId = (int) ($row['grant_application_id'] ?? 0);
        $evStats = $evidenceMap[$appId] ?? [];
        $fundStats = $fundingMap[$appId] ?? [];
        $row['evidence_count'] = (int) ($evStats['evidence_count'] ?? 0);
        $row['evidence_updated_at'] = (string) ($evStats['evidence_updated_at'] ?? '');
        $row['released_count'] = (int) ($fundStats['released_count'] ?? 0);
        $row['pending_tranche_count'] = (int) ($fundStats['pending_count'] ?? 0);
        $row['disbursement_updated_at'] = (string) ($fundStats['disbursement_updated_at'] ?? '');
        $row['funding_status_label'] = (string) ($fundStats['funding_status_label'] ?? '');
        $row['total_released'] = (float) ($fundStats['total_released'] ?? 0);
        $row['balance_pending'] = (float) ($fundStats['balance_pending'] ?? 0);
    }
    unset($row);

    return $rows;
}

/**
 * @param list<array<string, mixed>> $milestones
 * @param list<array<string, mixed>> $tranches
 * @param list<array<string, mixed>> $evidence
 * @return list<array<string, mixed>>
 */
function grantBuildFundedResearchPendingRequirements(
    array $milestones,
    array $tranches,
    array $evidence
): array {
    $requirements = [];
    $today = date('Y-m-d');

    $evidenceByMilestone = [];
    foreach ($evidence as $row) {
        $milestoneId = (int) ($row['milestone_id'] ?? 0);
        if ($milestoneId > 0) {
            $evidenceByMilestone[$milestoneId] = ($evidenceByMilestone[$milestoneId] ?? 0) + 1;
        }
    }

    foreach ($tranches as $tranche) {
        if ((string) ($tranche['status'] ?? '') !== 'Pending') {
            continue;
        }
        $label = (string) ($tranche['tranche_label'] ?? ('Tranche ' . (int) ($tranche['tranche_number'] ?? 0)));
        $requirements[] = [
            'type'  => 'fund_release',
            'level' => 'info',
            'label' => $label . ' fund release pending',
            'detail' => 'Awaiting CRAD staff to record ' . $label . ' disbursement.',
        ];
    }

    foreach ($milestones as $milestone) {
        $milestoneId = (int) ($milestone['id'] ?? 0);
        $name = (string) ($milestone['milestone_name'] ?? 'Milestone');
        $status = (string) ($milestone['status'] ?? 'Pending');
        $dueDate = trim((string) ($milestone['due_date'] ?? ''));
        $hasDoc = !empty($milestone['has_document']);
        $hasEvidence = ($evidenceByMilestone[$milestoneId] ?? 0) > 0;

        if ($dueDate !== '' && $dueDate < $today && $status !== 'Completed') {
            $requirements[] = [
                'type'  => 'overdue',
                'level' => 'warning',
                'label' => 'Overdue milestone: ' . $name,
                'detail' => 'Due date was ' . date('M j, Y', strtotime($dueDate)) . '.',
                'milestone_id' => $milestoneId,
            ];
        }

        if ($status === 'In Progress' && !$hasDoc && !$hasEvidence) {
            $requirements[] = [
                'type'  => 'evidence',
                'level' => 'action',
                'label' => 'Submit progress evidence for ' . $name,
                'detail' => 'Upload supporting documents for this active milestone.',
                'milestone_id' => $milestoneId,
            ];
        }
    }

    return $requirements;
}

/**
 * @return list<array<string, mixed>>
 */
function grantGetFundedResearchEvidence(PDO $crad, int $applicationId): array
{
    grantEnsureFundedResearchTables($crad);

    $stmt = $crad->prepare("
        SELECT e.*, m.milestone_name
          FROM `crad_grant_funded_progress_evidence` e
          LEFT JOIN `crad_grant_funded_project_milestones` m ON m.id = e.milestone_id
         WHERE e.grant_application_id = ?
         ORDER BY e.created_at DESC, e.id DESC
    ");
    $stmt->execute([$applicationId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return array_map(static function (array $row): array {
        $row['file_url'] = grantFundedResearchEvidenceFileUrl((int) ($row['id'] ?? 0));
        $row['has_file'] = trim((string) ($row['file_path'] ?? '')) !== '';

        return $row;
    }, $rows);
}

function grantFundedResearchEvidenceFileUrl(int $evidenceId): string
{
    if ($evidenceId <= 0) {
        return '';
    }

    return grantCradScriptUrl('grant-funded-research-file.php', ['id' => $evidenceId]);
}

/**
 * @return array<string, mixed>|null
 */
function grantGetFundedResearchDetail(PDO $crad, int $applicationId): ?array
{
    grantEnsureFundedResearchTables($crad);
    grantBackfillFundedProjectMilestones($crad);
    grantBackfillFundingDisbursementPlans($crad);

    $milestoneDetail = grantGetFundedMilestoneDetail($crad, $applicationId);
    if ($milestoneDetail === null) {
        return null;
    }

    $fundingDetail = grantGetFundingDisbursementDetail($crad, $applicationId);
    if ($fundingDetail === null) {
        return null;
    }

    $application = $milestoneDetail['application'];
    $fundingApplication = $fundingDetail['application'] ?? [];
    foreach (['funding_status_label', 'total_released', 'balance_pending', 'released_count', 'pending_count', 'tranche_count'] as $field) {
        if (array_key_exists($field, $fundingApplication)) {
            $application[$field] = $fundingApplication[$field];
        }
    }

    $milestones = $milestoneDetail['milestones'] ?? [];
    $tranches = $fundingDetail['tranches'] ?? [];
    $evidence = grantGetFundedResearchEvidence($crad, $applicationId);
    $pendingRequirements = grantBuildFundedResearchPendingRequirements($milestones, $tranches, $evidence);

    $timeline = [];
    foreach ($milestones as $milestone) {
        $timeline[] = [
            'id'             => (int) ($milestone['id'] ?? 0),
            'name'           => (string) ($milestone['milestone_name'] ?? ''),
            'status'         => (string) ($milestone['status'] ?? 'Pending'),
            'completion_pct' => (float) ($milestone['completion_pct'] ?? 0),
            'due_date'       => (string) ($milestone['due_date'] ?? ''),
            'status_class'   => grantMilestoneStatusClass((string) ($milestone['status'] ?? 'Pending')),
        ];
    }

    $isOwner = grantUserCanConductFundedResearch();

    return [
        'application'            => $application,
        'approved_budget'        => (float) ($fundingDetail['approved_budget'] ?? 0),
        'total_released'         => (float) ($fundingDetail['total_released'] ?? 0),
        'balance_pending'        => (float) ($fundingDetail['balance_pending'] ?? 0),
        'funding_status_label'   => (string) ($application['funding_status_label'] ?? ''),
        'milestones'             => $milestones,
        'tranches'               => $tranches,
        'evidence'               => $evidence,
        'pending_requirements'   => $pendingRequirements,
        'timeline'               => $timeline,
        'can_submit_evidence'    => $isOwner,
        'milestones_url'         => grantProjectMilestonesUrl() . '?id=' . $applicationId,
        'disbursement_url'       => grantBudgetDisbursementUrl($applicationId),
    ];
}

/**
 * @param array<string, mixed> $input
 * @return array{ok: bool, error?: string, detail?: array<string, mixed>|null}
 */
function grantSubmitFundedProgressEvidence(
    PDO $crad,
    int $applicationId,
    array $input,
    ?array $file,
    int $userId,
    string $userName
): array {
    grantEnsureFundedResearchTables($crad);

    if (!grantUserCanConductFundedResearch()) {
        return ['ok' => false, 'error' => 'You are not authorized to submit progress evidence.'];
    }

    $detail = grantGetFundedResearchDetail($crad, $applicationId);
    if ($detail === null || empty($detail['can_submit_evidence'])) {
        return ['ok' => false, 'error' => 'Funded project not found or access denied.'];
    }

    $milestoneId = (int) ($input['milestone_id'] ?? 0);
    if ($milestoneId <= 0) {
        return ['ok' => false, 'error' => 'Please select a milestone.'];
    }

    $validMilestone = false;
    foreach ($detail['milestones'] ?? [] as $milestone) {
        if ((int) ($milestone['id'] ?? 0) === $milestoneId) {
            $validMilestone = true;
            break;
        }
    }
    if (!$validMilestone) {
        return ['ok' => false, 'error' => 'Invalid milestone selected.'];
    }

    $title = trim((string) ($input['evidence_title'] ?? ''));
    if ($title === '') {
        return ['ok' => false, 'error' => 'Evidence title is required.'];
    }

    $notes = trim((string) ($input['notes'] ?? '')) ?: null;
    $storedName = null;
    $originalName = null;

    if ($file !== null && !empty($file['ok'])) {
        $storedName = (string) ($file['stored_name'] ?? '');
        $originalName = (string) ($file['original_name'] ?? '');
    }

    if ($storedName === null || $storedName === '') {
        return ['ok' => false, 'error' => 'Supporting file is required.'];
    }

    try {
        $crad->prepare("
            INSERT INTO `crad_grant_funded_progress_evidence`
                (grant_application_id, milestone_id, evidence_title, notes,
                 file_path, file_original, submitted_by_user_id, submitted_by_name, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', NOW(), NOW())
        ")->execute([
            $applicationId,
            $milestoneId,
            $title,
            $notes,
            $storedName,
            $originalName,
            $userId > 0 ? $userId : null,
            $userName,
        ]);

        return [
            'ok'     => true,
            'detail' => grantGetFundedResearchDetail($crad, $applicationId),
        ];
    } catch (Throwable $e) {
        error_log('grantSubmitFundedProgressEvidence: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to submit progress evidence.'];
    }
}

/**
 * @param list<array<string, mixed>> $rows
 */
function grantFundedResearchOverviewFingerprint(array $rows): string
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
            $row['evidence_count'] ?? '',
            $row['evidence_updated_at'] ?? '',
            $row['released_count'] ?? '',
            $row['disbursement_updated_at'] ?? '',
        ]);
    }

    return md5(implode('|', $parts));
}

/**
 * @param array<string, mixed>|null $detail
 */
function grantFundedResearchDetailFingerprint(?array $detail): string
{
    if ($detail === null) {
        return '';
    }

    $parts = [
        $detail['approved_budget'] ?? '',
        $detail['total_released'] ?? '',
        $detail['balance_pending'] ?? '',
        count($detail['pending_requirements'] ?? []),
        count($detail['evidence'] ?? []),
    ];

    foreach ($detail['tranches'] ?? [] as $tranche) {
        $parts[] = implode(':', [
            $tranche['id'] ?? '',
            $tranche['status'] ?? '',
            $tranche['amount_released'] ?? '',
            $tranche['reference_number'] ?? '',
        ]);
    }

    $parts[] = grantMilestoneDetailFingerprint([
        'can_track' => false,
        'milestones' => $detail['milestones'] ?? [],
    ]);

    return md5(implode('|', $parts));
}
