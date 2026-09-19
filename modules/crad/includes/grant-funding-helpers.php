<?php
/**
 * CRAD Grant Funding — Budget & Disbursement helpers
 *
 * Records fund releases (tranches) for APPROVED & FUNDED proposals.
 * Monitoring only — does not transfer actual funds.
 */
declare(strict_types=1);

require_once __DIR__ . '/grant-helpers.php';

function grantBudgetDisbursementUrl(int $applicationId = 0): string
{
    $url = BASE_URL . '/modules/crad/pages/budget-disbursement.php';
    if ($applicationId > 0) {
        $url .= '?id=' . $applicationId;
    }

    return $url;
}

/**
 * CRAD Staff, Finance-authorized personnel, and grant module admins.
 */
function grantUserCanReleaseFunds(): bool
{
    if (grantUserCanManage()) {
        return true;
    }

    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    return in_array($roleKey, ['finance', 'superadmin'], true);
}

function grantUserCanViewFundingDisbursement(): bool
{
    return grantUserCanReleaseFunds() || grantUserCanApply();
}

function grantRequireFundingReleaseAccess(): void
{
    if (grantUserCanReleaseFunds()) {
        return;
    }

    grantRedirectUnauthorized();
}

function grantRequireFundingViewAccess(): void
{
    if (grantUserCanViewFundingDisbursement()) {
        return;
    }

    grantRedirectUnauthorized();
}

function grantEnsureFundingTables(PDO $crad): void
{
    grantEnsureTables($crad);

    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    _grantAddColumnIfMissing($crad, 'grant_applications', 'approved_budget',
        'DECIMAL(14,2) NULL DEFAULT NULL AFTER requested_budget');

    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_funding_disbursements (
            id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            grant_application_id  INT UNSIGNED NOT NULL,
            tranche_number        TINYINT UNSIGNED NOT NULL DEFAULT 1,
            tranche_label         VARCHAR(80)  NOT NULL DEFAULT '',
            approved_budget       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            amount_released       DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            release_date          DATE NULL DEFAULT NULL,
            reference_number      VARCHAR(80) NULL DEFAULT NULL,
            status                ENUM('Pending','Released','Cancelled') NOT NULL DEFAULT 'Pending',
            released_by_user_id   INT UNSIGNED NULL DEFAULT NULL,
            released_by_name      VARCHAR(120) NULL DEFAULT NULL,
            remarks               TEXT NULL,
            created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_gfd_app_tranche (grant_application_id, tranche_number),
            KEY idx_gfd_application (grant_application_id),
            KEY idx_gfd_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * @return array{ok: bool, error?: string}
 */
function grantInitializeFundingDisbursementPlan(PDO $crad, int $applicationId): array
{
    grantEnsureFundingTables($crad);

    $stmt = $crad->prepare("
        SELECT ga.*, go.max_funding_cap
          FROM grant_applications ga
         INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
         WHERE ga.id = ?
           AND ga.status = ?
         LIMIT 1
    ");
    $stmt->execute([$applicationId, grantStatusApprovedFunded()]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        return ['ok' => false, 'error' => 'Approved & Funded application not found.'];
    }

    $existing = $crad->prepare('SELECT COUNT(*) FROM grant_funding_disbursements WHERE grant_application_id = ?');
    $existing->execute([$applicationId]);
    if ((int) $existing->fetchColumn() > 0) {
        return ['ok' => true];
    }

    $requested = (float) ($application['requested_budget'] ?? 0);
    $approved = (float) ($application['approved_budget'] ?? 0);
    if ($approved <= 0) {
        $approved = $requested > 0 ? $requested : (float) ($application['max_funding_cap'] ?? 0);
    }
    if ($approved <= 0) {
        $approved = $requested;
    }

    if ($approved > 0 && (float) ($application['approved_budget'] ?? 0) <= 0) {
        $crad->prepare('UPDATE grant_applications SET approved_budget = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$approved, $applicationId]);
    }

    $tranches = grantBuildDefaultTrancheAmounts($approved);

    try {
        $crad->beginTransaction();
        $insert = $crad->prepare("
            INSERT INTO grant_funding_disbursements
                (grant_application_id, tranche_number, tranche_label, approved_budget,
                 amount_released, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'Pending', NOW(), NOW())
        ");

        foreach ($tranches as $index => $amount) {
            $number = $index + 1;
            $insert->execute([
                $applicationId,
                $number,
                'Tranche ' . $number,
                $approved,
                $amount,
            ]);
        }

        $crad->commit();

        return ['ok' => true];
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantInitializeFundingDisbursementPlan: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to initialize disbursement plan.'];
    }
}

/**
 * @return list<float>
 */
function grantBuildDefaultTrancheAmounts(float $approvedBudget): array
{
    if ($approvedBudget <= 0) {
        return [0.0];
    }

    $half = round($approvedBudget / 2, 2);
    $remainder = round($approvedBudget - $half, 2);

    return [$half, $remainder];
}

function grantBackfillFundingDisbursementPlans(PDO $crad): void
{
    grantEnsureFundingTables($crad);

    $stmt = $crad->prepare('SELECT id FROM grant_applications WHERE status = ? ORDER BY id ASC');
    $stmt->execute([grantStatusApprovedFunded()]);

    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $applicationId) {
        grantInitializeFundingDisbursementPlan($crad, (int) $applicationId);
    }
}

/**
 * @return list<array<string, mixed>>
 */
function grantGetFundedDisbursementOverview(PDO $crad): array
{
    grantEnsureFundingTables($crad);
    grantBackfillFundingDisbursementPlans($crad);

    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $canManage = grantUserCanReleaseFunds();

    $sql = "
        SELECT ga.id AS grant_application_id,
               ga.proposal_reference,
               ga.research_title,
               ga.applicant_name,
               ga.college_dept,
               ga.requested_budget,
               COALESCE(ga.approved_budget, ga.requested_budget, 0) AS approved_budget,
               ga.updated_at AS funded_at,
               go.funding_title
          FROM grant_applications ga
         INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
         WHERE ga.status = ?
    ";
    $params = [grantStatusApprovedFunded()];

    if (!$canManage && grantUserCanApply()) {
        $sql .= ' AND ga.applicant_user_id = ?';
        $params[] = $userId;
    } elseif (!$canManage) {
        return [];
    }

    $sql .= '
         ORDER BY ga.updated_at DESC, ga.id DESC
    ';

    $stmt = $crad->prepare($sql);
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($rows === []) {
        return [];
    }

    $ids = array_map(static fn(array $row): int => (int) ($row['grant_application_id'] ?? 0), $rows);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $trancheStmt = $crad->prepare("
        SELECT grant_application_id,
               COUNT(*) AS tranche_count,
               SUM(CASE WHEN status = 'Released' THEN 1 ELSE 0 END) AS released_count,
               SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
               COALESCE(SUM(CASE WHEN status = 'Released' THEN amount_released ELSE 0 END), 0) AS total_released,
               MAX(updated_at) AS disbursement_updated_at
          FROM grant_funding_disbursements
         WHERE grant_application_id IN ({$placeholders})
         GROUP BY grant_application_id
    ");
    $trancheStmt->execute($ids);
    $trancheMap = [];
    foreach ($trancheStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $trancheRow) {
        $trancheMap[(int) ($trancheRow['grant_application_id'] ?? 0)] = $trancheRow;
    }

    foreach ($rows as &$row) {
        $appId = (int) ($row['grant_application_id'] ?? 0);
        $stats = $trancheMap[$appId] ?? [];
        $row['tranche_count'] = (int) ($stats['tranche_count'] ?? 0);
        $row['released_count'] = (int) ($stats['released_count'] ?? 0);
        $row['pending_count'] = (int) ($stats['pending_count'] ?? 0);
        $row['total_released'] = (float) ($stats['total_released'] ?? 0);
        $row['disbursement_updated_at'] = (string) ($stats['disbursement_updated_at'] ?? '');
        $approved = (float) ($row['approved_budget'] ?? 0);
        $released = (float) ($row['total_released'] ?? 0);
        $row['balance_pending'] = max(0, round($approved - $released, 2));
        $row['funding_status_label'] = grantFundingStatusLabel(
            (int) ($row['tranche_count'] ?? 0),
            (int) ($row['released_count'] ?? 0),
            (int) ($row['pending_count'] ?? 0)
        );
    }
    unset($row);

    return $rows;
}

function grantFundingStatusLabel(int $trancheCount, int $releasedCount, int $pendingCount): string
{
    if ($trancheCount <= 0) {
        return 'Awaiting Plan';
    }
    if ($releasedCount >= $trancheCount) {
        return 'Fully Released';
    }
    if ($releasedCount > 0) {
        return 'Partially Released';
    }

    return 'Pending Release';
}

/**
 * @return array<string, mixed>|null
 */
function grantGetFundingDisbursementDetail(PDO $crad, int $applicationId): ?array
{
    grantEnsureFundingTables($crad);
    grantBackfillFundingDisbursementPlans($crad);

    $overviewRows = grantGetFundedDisbursementOverview($crad);
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
          FROM grant_funding_disbursements
         WHERE grant_application_id = ?
         ORDER BY tranche_number ASC
    ");
    $stmt->execute([$applicationId]);
    $tranches = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $approved = (float) ($application['approved_budget'] ?? 0);
    $releasedTotal = 0.0;
    foreach ($tranches as $tranche) {
        if ((string) ($tranche['status'] ?? '') === 'Released') {
            $releasedTotal += (float) ($tranche['amount_released'] ?? 0);
        }
    }

    return [
        'application'     => $application,
        'tranches'        => $tranches,
        'approved_budget' => $approved,
        'total_released'  => round($releasedTotal, 2),
        'balance_pending' => max(0, round($approved - $releasedTotal, 2)),
        'can_release'     => grantUserCanReleaseFunds(),
    ];
}

/**
 * @param list<array<string, mixed>> $rows
 */
function grantFundingOverviewFingerprint(array $rows): string
{
    $parts = [];
    foreach ($rows as $row) {
        $parts[] = implode(':', [
            $row['grant_application_id'] ?? '',
            $row['approved_budget'] ?? '',
            $row['total_released'] ?? '',
            $row['released_count'] ?? '',
            $row['pending_count'] ?? '',
            $row['disbursement_updated_at'] ?? '',
            $row['funded_at'] ?? '',
        ]);
    }

    return md5(implode('|', $parts));
}

/**
 * @param array<string, mixed>|null $detail
 */
function grantFundingDetailFingerprint(?array $detail): string
{
    if ($detail === null) {
        return '';
    }

    $parts = [
        (string) ($detail['approved_budget'] ?? ''),
        (string) ($detail['total_released'] ?? ''),
        (string) ($detail['balance_pending'] ?? ''),
        !empty($detail['can_release']) ? '1' : '0',
    ];

    foreach ($detail['tranches'] ?? [] as $tranche) {
        $parts[] = implode(':', [
            $tranche['id'] ?? '',
            $tranche['tranche_number'] ?? '',
            $tranche['status'] ?? '',
            $tranche['amount_released'] ?? '',
            $tranche['release_date'] ?? '',
            $tranche['reference_number'] ?? '',
            $tranche['updated_at'] ?? '',
        ]);
    }

    return md5(implode('|', $parts));
}

function grantFormatPeso(float $amount): string
{
    return '₱' . number_format($amount, 0, '.', ',');
}

function grantGenerateDisbursementReference(array $application, int $trancheNumber): string
{
    $ref = trim((string) ($application['proposal_reference'] ?? ''));
    $base = $ref !== '' ? preg_replace('/[^A-Za-z0-9\-]/', '', $ref) : ('GA' . (int) ($application['grant_application_id'] ?? $application['id'] ?? 0));

    return 'DISB-' . $base . '-T' . $trancheNumber;
}

/**
 * @param array<string, mixed> $input
 * @return array{ok: bool, error?: string, detail?: array<string, mixed>|null}
 */
function grantReleaseFundingTranche(
    PDO $crad,
    int $disbursementId,
    array $input,
    int $userId,
    string $userName
): array {
    grantEnsureFundingTables($crad);

    if (!grantUserCanReleaseFunds()) {
        return ['ok' => false, 'error' => 'You are not authorized to release funds.'];
    }

    $stmt = $crad->prepare("
        SELECT d.*, ga.proposal_reference, ga.research_title, ga.applicant_user_id, ga.status AS application_status
          FROM grant_funding_disbursements d
         INNER JOIN grant_applications ga ON ga.id = d.grant_application_id
         WHERE d.id = ?
         LIMIT 1
    ");
    $stmt->execute([$disbursementId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['ok' => false, 'error' => 'Disbursement tranche not found.'];
    }

    if ((string) ($row['application_status'] ?? '') !== grantStatusApprovedFunded()) {
        return ['ok' => false, 'error' => 'Only Approved & Funded proposals can receive fund releases.'];
    }

    if ((string) ($row['status'] ?? '') !== 'Pending') {
        return ['ok' => false, 'error' => 'This tranche is no longer pending release.'];
    }

    $amount = (float) ($input['amount_released'] ?? $row['amount_released'] ?? 0);
    if ($amount <= 0) {
        $trancheNumber = (int) ($row['tranche_number'] ?? 1);
        $approved = (float) ($row['approved_budget'] ?? 0);
        $amounts = grantBuildDefaultTrancheAmounts($approved);
        $amount = (float) ($amounts[$trancheNumber - 1] ?? $approved);
    }

    $releaseDate = trim((string) ($input['release_date'] ?? ''));
    if ($releaseDate === '') {
        $releaseDate = date('Y-m-d');
    }

    $reference = trim((string) ($input['reference_number'] ?? ''));
    if ($reference === '') {
        $reference = grantGenerateDisbursementReference($row, (int) ($row['tranche_number'] ?? 1));
    }

    $remarks = trim((string) ($input['remarks'] ?? '')) ?: null;

    try {
        $crad->prepare("
            UPDATE grant_funding_disbursements
               SET amount_released = ?,
                   release_date = ?,
                   reference_number = ?,
                   status = 'Released',
                   released_by_user_id = ?,
                   released_by_name = ?,
                   remarks = ?,
                   updated_at = NOW()
             WHERE id = ?
        ")->execute([
            $amount,
            $releaseDate,
            $reference,
            $userId > 0 ? $userId : null,
            $userName,
            $remarks,
            $disbursementId,
        ]);

        $applicationId = (int) ($row['grant_application_id'] ?? 0);
        grantNotifyApplicantFundReleased($crad, $applicationId, $row, $amount, $reference, $userName);

        return [
            'ok'                => true,
            'detail'            => grantGetFundingDisbursementDetail($crad, $applicationId),
            'reference_number'  => $reference,
            'amount_released'   => $amount,
            'tranche_number'    => (int) ($row['tranche_number'] ?? 0),
        ];
    } catch (Throwable $e) {
        error_log('grantReleaseFundingTranche: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to record fund release.'];
    }
}

/**
 * @param array<string, mixed> $tranche
 */
function grantNotifyApplicantFundReleased(
    PDO $crad,
    int $applicationId,
    array $tranche,
    float $amount,
    string $reference,
    string $releasedByName
): void {
    require_once __DIR__ . '/grant-evaluation-helpers.php';

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

    $trancheLabel = (string) ($tranche['tranche_label'] ?? ('Tranche ' . (int) ($tranche['tranche_number'] ?? 1)));
    $body = sprintf(
        '%s — %s released %s (Ref: %s). Recorded by %s. View Budget & Disbursement for tranche status.',
        $refLabel,
        $trancheLabel,
        grantFormatPeso($amount),
        $reference,
        $releasedByName !== '' ? $releasedByName : 'CRAD Staff'
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

    $eventKey = 'grant-proposal:grant_fund_release:'
        . $applicationId
        . ':t' . (int) ($tranche['tranche_number'] ?? 0)
        . ':d' . (int) ($tranche['id'] ?? 0);

    grantInsertGrantProposalNotification(
        $crad,
        $applicationId,
        $recipientUserId,
        $recipientRole,
        'grant_fund_release',
        $eventKey,
        'Fund Tranche Released',
        $body,
        grantBudgetDisbursementUrl($applicationId)
    );
}
