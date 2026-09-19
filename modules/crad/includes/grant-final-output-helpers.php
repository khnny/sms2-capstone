<?php
/**
 * Grant — Final Output / Publications & IP helpers (Steps 19–20)
 */
declare(strict_types=1);

require_once __DIR__ . '/grant-helpers.php';
require_once __DIR__ . '/grant-milestone-helpers.php';

function grantPublicationsIpUrl(int $applicationId = 0): string
{
    $url = BASE_URL . '/modules/crad/pages/publications-ip.php';
    if ($applicationId > 0) {
        $url .= '?id=' . $applicationId;
    }

    return $url;
}

/**
 * @return list<string>
 */
function grantFinalOutputEligibleApplicationStatuses(): array
{
    return [
        grantStatusApprovedFunded(),
        grantStatusFinalOutputSubmitted(),
        grantStatusOutputVerified(),
        grantStatusArchived(),
    ];
}

function grantUserCanVerifyPublicationsIp(): bool
{
    return grantUserCanManage();
}

function grantUserCanSubmitFinalOutput(): bool
{
    return grantUserCanApply();
}

function grantUserCanViewPublicationsIp(): bool
{
    return grantUserCanSubmitFinalOutput() || grantUserCanVerifyPublicationsIp();
}

function grantRequirePublicationsIpAccess(): void
{
    if (grantUserCanViewPublicationsIp()) {
        return;
    }

    grantRedirectUnauthorized();
}

/**
 * @return list<string>
 */
function grantFinalOutputPublicationTypes(): array
{
    return ['Journal', 'Conference', 'Book Chapter', 'Repository', 'Other'];
}

function grantEnsureFinalOutputTables(PDO $crad): void
{
    grantEnsureTables($crad);

    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $crad->exec("UPDATE grant_final_output_submissions SET status = 'OUTPUT_VERIFIED' WHERE status = 'VERIFIED'");

    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_final_output_submissions (
            id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
            grant_application_id    INT UNSIGNED NOT NULL,
            version_number          INT UNSIGNED NOT NULL DEFAULT 1,
            final_research_title    VARCHAR(500) NOT NULL DEFAULT '',
            authors                 VARCHAR(500) NOT NULL DEFAULT '',
            abstract                TEXT NULL,
            publication_type        ENUM('Journal','Conference','Book Chapter','Repository','Other') NOT NULL DEFAULT 'Journal',
            journal_conference      VARCHAR(255) NOT NULL DEFAULT '',
            doi                     VARCHAR(120) NOT NULL DEFAULT '',
            publication_url         VARCHAR(500) NOT NULL DEFAULT '',
            ip_information          TEXT NULL,
            copyright_info          TEXT NULL,
            patent_info             TEXT NULL,
            other_ip_info           TEXT NULL,
            final_pdf_path          VARCHAR(255) NULL DEFAULT NULL,
            final_pdf_original      VARCHAR(255) NULL DEFAULT NULL,
            supporting_files_json   TEXT NULL,
            status                  ENUM('FINAL_OUTPUT_SUBMITTED','RETURNED_FOR_CORRECTION','OUTPUT_VERIFIED') NOT NULL DEFAULT 'FINAL_OUTPUT_SUBMITTED',
            return_reason           TEXT NULL,
            verification_notes      TEXT NULL,
            submitted_by_user_id    INT UNSIGNED NULL DEFAULT NULL,
            submitted_by_name       VARCHAR(120) NULL DEFAULT NULL,
            submitted_at            DATETIME NULL DEFAULT NULL,
            reviewed_by_user_id     INT UNSIGNED NULL DEFAULT NULL,
            reviewed_by_name        VARCHAR(120) NULL DEFAULT NULL,
            reviewed_at             DATETIME NULL DEFAULT NULL,
            created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_gfos_application (grant_application_id),
            KEY idx_gfos_status (status),
            KEY idx_gfos_submitted (submitted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_publications_ip_repository (
            id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
            grant_application_id    INT UNSIGNED NOT NULL,
            submission_id           INT UNSIGNED NOT NULL,
            repository_reference    VARCHAR(40) NOT NULL DEFAULT '',
            final_research_title    VARCHAR(500) NOT NULL DEFAULT '',
            authors                 VARCHAR(500) NOT NULL DEFAULT '',
            abstract                TEXT NULL,
            publication_type        VARCHAR(60) NOT NULL DEFAULT '',
            journal_conference      VARCHAR(255) NOT NULL DEFAULT '',
            doi                     VARCHAR(120) NOT NULL DEFAULT '',
            publication_url         VARCHAR(500) NOT NULL DEFAULT '',
            ip_information          TEXT NULL,
            copyright_info          TEXT NULL,
            patent_info             TEXT NULL,
            other_ip_info           TEXT NULL,
            final_pdf_path          VARCHAR(255) NULL DEFAULT NULL,
            final_pdf_original      VARCHAR(255) NULL DEFAULT NULL,
            supporting_files_json   TEXT NULL,
            verified_by_user_id     INT UNSIGNED NULL DEFAULT NULL,
            verified_by_name        VARCHAR(120) NULL DEFAULT NULL,
            verified_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_gpip_application (grant_application_id),
            KEY idx_gpip_reference (repository_reference),
            KEY idx_gpip_verified (verified_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * @return list<array<string, mixed>>
 */
function grantGetFinalOutputOverview(PDO $crad): array
{
    grantEnsureFinalOutputTables($crad);

    $canVerify = grantUserCanVerifyPublicationsIp();
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $statuses = grantFinalOutputEligibleApplicationStatuses();
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));

    $sql = "
        SELECT ga.id AS grant_application_id,
               ga.proposal_reference,
               ga.research_title,
               ga.applicant_name,
               ga.college_dept,
               ga.status AS application_status,
               ga.updated_at AS application_updated_at,
               go.funding_title,
               s.id AS submission_id,
               s.status AS submission_status,
               s.final_research_title,
               s.publication_type,
               s.submitted_at,
               s.reviewed_at,
               s.updated_at AS submission_updated_at,
               r.id AS repository_id,
               r.repository_reference,
               r.verified_at
          FROM grant_applications ga
         INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
          LEFT JOIN grant_final_output_submissions s ON s.grant_application_id = ga.id
          LEFT JOIN grant_publications_ip_repository r ON r.grant_application_id = ga.id
         WHERE ga.status IN ({$placeholders})
    ";
    $params = $statuses;

    if (!$canVerify && grantUserCanSubmitFinalOutput()) {
        $sql .= ' AND ga.applicant_user_id = ?';
        $params[] = $userId;
    } elseif (!$canVerify) {
        return [];
    }

    $sql .= ' ORDER BY
        CASE WHEN ga.status = ? THEN 0
             WHEN ga.status = ? THEN 1
             ELSE 2 END,
        COALESCE(s.submitted_at, ga.updated_at) DESC,
        ga.id DESC';

    $params[] = grantStatusFinalOutputSubmitted();
    $params[] = grantStatusApprovedFunded();

    $stmt = $crad->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$row) {
        $row['workflow_label'] = grantFinalOutputWorkflowLabel($row);
        $row['workflow_class'] = grantFinalOutputWorkflowClass($row);
        $row['needs_verification'] = grantFinalOutputNeedsVerification($row);
    }
    unset($row);

    return $rows;
}

/**
 * @param array<string, mixed> $row
 */
function grantFinalOutputWorkflowLabel(array $row): string
{
    $appStatus = (string) ($row['application_status'] ?? '');
    $subStatus = (string) ($row['submission_status'] ?? '');

    if ($appStatus === grantStatusOutputVerified() || $subStatus === 'OUTPUT_VERIFIED') {
        return 'OUTPUT VERIFIED';
    }
    if ($subStatus === 'RETURNED_FOR_CORRECTION') {
        return 'RETURNED FOR CORRECTION';
    }
    if ($appStatus === grantStatusFinalOutputSubmitted() || $subStatus === 'FINAL_OUTPUT_SUBMITTED') {
        return 'FINAL OUTPUT SUBMITTED';
    }

    return 'READY TO SUBMIT';
}

/**
 * @param array<string, mixed> $row
 */
function grantFinalOutputWorkflowClass(array $row): string
{
    $label = grantFinalOutputWorkflowLabel($row);

    return match ($label) {
        'OUTPUT VERIFIED'          => 'verified',
        'FINAL OUTPUT SUBMITTED'   => 'submitted',
        'RETURNED FOR CORRECTION'  => 'returned',
        default                    => 'ready',
    };
}

/**
 * @param array<string, mixed> $row
 */
function grantFinalOutputNeedsVerification(array $row): bool
{
    return (string) ($row['application_status'] ?? '') === grantStatusFinalOutputSubmitted()
        && (string) ($row['submission_status'] ?? '') === 'FINAL_OUTPUT_SUBMITTED';
}

/**
 * @return array<string, mixed>|null
 */
function grantGetFinalOutputDetail(PDO $crad, int $applicationId): ?array
{
    grantEnsureFinalOutputTables($crad);

    if ($applicationId <= 0) {
        return null;
    }

    $stmt = $crad->prepare("
        SELECT ga.*, go.funding_title
          FROM grant_applications ga
         INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
         WHERE ga.id = ?
         LIMIT 1
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        return null;
    }

    $canVerify = grantUserCanVerifyPublicationsIp();
    if (!$canVerify && grantUserCanSubmitFinalOutput()) {
        if ((int) ($application['applicant_user_id'] ?? 0) !== (int) ($_SESSION['user_id'] ?? 0)) {
            return null;
        }
    } elseif (!$canVerify) {
        return null;
    }

    $appStatus = (string) ($application['status'] ?? '');
    if (!in_array($appStatus, grantFinalOutputEligibleApplicationStatuses(), true)) {
        return null;
    }

    $subStmt = $crad->prepare('SELECT * FROM grant_final_output_submissions WHERE grant_application_id = ? LIMIT 1');
    $subStmt->execute([$applicationId]);
    $submission = $subStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $repoStmt = $crad->prepare('SELECT * FROM grant_publications_ip_repository WHERE grant_application_id = ? LIMIT 1');
    $repoStmt->execute([$applicationId]);
    $repository = $repoStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $canSubmit = grantUserCanSubmitFinalOutput()
        && (int) ($application['applicant_user_id'] ?? 0) === (int) ($_SESSION['user_id'] ?? 0)
        && in_array($appStatus, [grantStatusApprovedFunded()], true)
        && ($submission === null || (string) ($submission['status'] ?? '') === 'RETURNED_FOR_CORRECTION');

    $canVerifyAction = $canVerify
        && $submission !== null
        && (string) ($submission['status'] ?? '') === 'FINAL_OUTPUT_SUBMITTED'
        && $appStatus === grantStatusFinalOutputSubmitted();

    $supportingFiles = [];
    if ($submission !== null && !empty($submission['supporting_files_json'])) {
        $decoded = json_decode((string) $submission['supporting_files_json'], true);
        if (is_array($decoded)) {
            $supportingFiles = $decoded;
        }
    }

    return [
        'application'        => $application,
        'submission'         => $submission,
        'repository'         => $repository,
        'supporting_files'   => $supportingFiles,
        'workflow_label'     => grantFinalOutputWorkflowLabel([
            'application_status' => $appStatus,
            'submission_status'  => (string) ($submission['status'] ?? ''),
        ]),
        'workflow_class'     => grantFinalOutputWorkflowClass([
            'application_status' => $appStatus,
            'submission_status'  => (string) ($submission['status'] ?? ''),
        ]),
        'default_authors'    => trim((string) ($submission['authors'] ?? $application['applicant_name'] ?? '')),
        'default_title'      => trim((string) ($submission['final_research_title'] ?? $application['research_title'] ?? '')),
        'can_submit'         => $canSubmit,
        'can_verify'         => $canVerifyAction,
        'publication_types'  => grantFinalOutputPublicationTypes(),
        'file_base_url'      => BASE_URL . '/modules/crad/grant-final-output-file.php',
    ];
}

/**
 * @param array<string, mixed> $post
 * @param array<string, mixed> $pdfUpload
 * @param array<string, mixed>|null $supportingUpload
 * @return array{ok: bool, error?: string, detail?: array<string, mixed>|null}
 */
function grantSubmitFinalOutput(
    PDO $crad,
    int $applicationId,
    array $post,
    array $pdfUpload,
    ?array $supportingUpload,
    int $userId,
    string $userName
): array {
    grantEnsureFinalOutputTables($crad);

    $detail = grantGetFinalOutputDetail($crad, $applicationId);
    if ($detail === null || empty($detail['can_submit'])) {
        return ['ok' => false, 'error' => 'You cannot submit final output for this project.'];
    }

    $title = trim((string) ($post['final_research_title'] ?? ''));
    $authors = trim((string) ($post['authors'] ?? ''));
    $abstract = trim((string) ($post['abstract'] ?? ''));
    $publicationType = trim((string) ($post['publication_type'] ?? 'Journal'));
    $journal = trim((string) ($post['journal_conference'] ?? ''));
    $doi = trim((string) ($post['doi'] ?? ''));
    $pubUrl = trim((string) ($post['publication_url'] ?? ''));
    $ipInfo = trim((string) ($post['ip_information'] ?? ''));
    $copyright = trim((string) ($post['copyright_info'] ?? ''));
    $patent = trim((string) ($post['patent_info'] ?? ''));
    $otherIp = trim((string) ($post['other_ip_info'] ?? ''));

    if ($title === '') {
        return ['ok' => false, 'error' => 'Final Research Title is required.'];
    }
    if ($authors === '') {
        return ['ok' => false, 'error' => 'Authors are required.'];
    }
    if ($abstract === '') {
        return ['ok' => false, 'error' => 'Abstract is required.'];
    }
    if (!in_array($publicationType, grantFinalOutputPublicationTypes(), true)) {
        return ['ok' => false, 'error' => 'Invalid publication type.'];
    }
    if ($journal === '') {
        return ['ok' => false, 'error' => 'Journal / Conference is required.'];
    }
    if (empty($pdfUpload['ok']) || empty($pdfUpload['path'])) {
        return ['ok' => false, 'error' => $pdfUpload['error'] ?? 'Final Research PDF is required.'];
    }

    $existing = $detail['submission'] ?? null;
    $version = 1;
    if ($existing !== null) {
        $version = max(1, (int) ($existing['version_number'] ?? 1));
        if ((string) ($existing['status'] ?? '') === 'RETURNED_FOR_CORRECTION') {
            $version++;
        }
    }

    $supportingJson = null;
    if ($supportingUpload !== null && !empty($supportingUpload['ok']) && !empty($supportingUpload['path'])) {
        $supportingJson = json_encode([[
            'path'          => (string) $supportingUpload['path'],
            'original_name' => (string) ($supportingUpload['original_name'] ?? ''),
            'stored_name'   => (string) ($supportingUpload['stored_name'] ?? ''),
        ]], JSON_UNESCAPED_UNICODE);
    } elseif ($existing !== null && !empty($existing['supporting_files_json'])) {
        $supportingJson = (string) $existing['supporting_files_json'];
    }

    try {
        $crad->beginTransaction();

        if ($existing === null) {
            $insert = $crad->prepare("
                INSERT INTO grant_final_output_submissions
                    (grant_application_id, version_number, final_research_title, authors, abstract,
                     publication_type, journal_conference, doi, publication_url,
                     ip_information, copyright_info, patent_info, other_ip_info,
                     final_pdf_path, final_pdf_original, supporting_files_json,
                     status, submitted_by_user_id, submitted_by_name, submitted_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'FINAL_OUTPUT_SUBMITTED', ?, ?, NOW())
            ");
            $insert->execute([
                $applicationId,
                $version,
                $title,
                $authors,
                $abstract,
                $publicationType,
                $journal,
                $doi,
                $pubUrl,
                $ipInfo !== '' ? $ipInfo : null,
                $copyright !== '' ? $copyright : null,
                $patent !== '' ? $patent : null,
                $otherIp !== '' ? $otherIp : null,
                (string) $pdfUpload['path'],
                (string) ($pdfUpload['original_name'] ?? ''),
                $supportingJson,
                $userId > 0 ? $userId : null,
                $userName,
            ]);
        } else {
            $update = $crad->prepare("
                UPDATE grant_final_output_submissions
                   SET version_number = ?,
                       final_research_title = ?,
                       authors = ?,
                       abstract = ?,
                       publication_type = ?,
                       journal_conference = ?,
                       doi = ?,
                       publication_url = ?,
                       ip_information = ?,
                       copyright_info = ?,
                       patent_info = ?,
                       other_ip_info = ?,
                       final_pdf_path = ?,
                       final_pdf_original = ?,
                       supporting_files_json = ?,
                       status = 'FINAL_OUTPUT_SUBMITTED',
                       return_reason = NULL,
                       verification_notes = NULL,
                       submitted_by_user_id = ?,
                       submitted_by_name = ?,
                       submitted_at = NOW(),
                       reviewed_by_user_id = NULL,
                       reviewed_by_name = NULL,
                       reviewed_at = NULL,
                       updated_at = NOW()
                 WHERE grant_application_id = ?
            ");
            $update->execute([
                $version,
                $title,
                $authors,
                $abstract,
                $publicationType,
                $journal,
                $doi,
                $pubUrl,
                $ipInfo !== '' ? $ipInfo : null,
                $copyright !== '' ? $copyright : null,
                $patent !== '' ? $patent : null,
                $otherIp !== '' ? $otherIp : null,
                (string) $pdfUpload['path'],
                (string) ($pdfUpload['original_name'] ?? ''),
                $supportingJson,
                $userId > 0 ? $userId : null,
                $userName,
                $applicationId,
            ]);
        }

        $appUpdate = $crad->prepare('UPDATE grant_applications SET status = ?, updated_at = NOW() WHERE id = ?');
        $appUpdate->execute([grantStatusFinalOutputSubmitted(), $applicationId]);

        $crad->commit();
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantSubmitFinalOutput: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to submit final output. Please try again.'];
    }

    $application = $detail['application'];
    grantNotifyCradFinalOutputSubmitted($crad, $application, $title, $userName);

    return [
        'ok'     => true,
        'detail' => grantGetFinalOutputDetail($crad, $applicationId),
    ];
}

/**
 * @param array<string, mixed> $post
 * @return array{ok: bool, error?: string, detail?: array<string, mixed>|null}
 */
function grantVerifyFinalOutput(PDO $crad, int $applicationId, array $post, int $userId, string $userName): array
{
    grantEnsureFinalOutputTables($crad);

    $detail = grantGetFinalOutputDetail($crad, $applicationId);
    if ($detail === null || empty($detail['can_verify'])) {
        return ['ok' => false, 'error' => 'This submission is not ready for verification.'];
    }

    $submission = $detail['submission'];
    if ($submission === null) {
        return ['ok' => false, 'error' => 'Submission not found.'];
    }

    $notes = trim((string) ($post['verification_notes'] ?? ''));

    try {
        $crad->beginTransaction();

        $subId = (int) ($submission['id'] ?? 0);
        $verifySub = $crad->prepare("
            UPDATE grant_final_output_submissions
               SET status = 'OUTPUT_VERIFIED',
                   verification_notes = ?,
                   reviewed_by_user_id = ?,
                   reviewed_by_name = ?,
                   reviewed_at = NOW(),
                   updated_at = NOW()
             WHERE id = ?
        ");
        $verifySub->execute([
            $notes !== '' ? $notes : null,
            $userId > 0 ? $userId : null,
            $userName,
            $subId,
        ]);

        $reference = grantGenerateRepositoryReference($crad);

        $repoInsert = $crad->prepare("
            INSERT INTO grant_publications_ip_repository
                (grant_application_id, submission_id, repository_reference,
                 final_research_title, authors, abstract, publication_type, journal_conference,
                 doi, publication_url, ip_information, copyright_info, patent_info, other_ip_info,
                 final_pdf_path, final_pdf_original, supporting_files_json,
                 verified_by_user_id, verified_by_name, verified_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                submission_id = VALUES(submission_id),
                repository_reference = VALUES(repository_reference),
                final_research_title = VALUES(final_research_title),
                authors = VALUES(authors),
                abstract = VALUES(abstract),
                publication_type = VALUES(publication_type),
                journal_conference = VALUES(journal_conference),
                doi = VALUES(doi),
                publication_url = VALUES(publication_url),
                ip_information = VALUES(ip_information),
                copyright_info = VALUES(copyright_info),
                patent_info = VALUES(patent_info),
                other_ip_info = VALUES(other_ip_info),
                final_pdf_path = VALUES(final_pdf_path),
                final_pdf_original = VALUES(final_pdf_original),
                supporting_files_json = VALUES(supporting_files_json),
                verified_by_user_id = VALUES(verified_by_user_id),
                verified_by_name = VALUES(verified_by_name),
                verified_at = NOW()
        ");
        $repoInsert->execute([
            $applicationId,
            $subId,
            $reference,
            (string) ($submission['final_research_title'] ?? ''),
            (string) ($submission['authors'] ?? ''),
            (string) ($submission['abstract'] ?? ''),
            (string) ($submission['publication_type'] ?? ''),
            (string) ($submission['journal_conference'] ?? ''),
            (string) ($submission['doi'] ?? ''),
            (string) ($submission['publication_url'] ?? ''),
            $submission['ip_information'] ?? null,
            $submission['copyright_info'] ?? null,
            $submission['patent_info'] ?? null,
            $submission['other_ip_info'] ?? null,
            (string) ($submission['final_pdf_path'] ?? ''),
            (string) ($submission['final_pdf_original'] ?? ''),
            $submission['supporting_files_json'] ?? null,
            $userId > 0 ? $userId : null,
            $userName,
        ]);

        $appUpdate = $crad->prepare('UPDATE grant_applications SET status = ?, updated_at = NOW() WHERE id = ?');
        $appUpdate->execute([grantStatusOutputVerified(), $applicationId]);

        $crad->commit();
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantVerifyFinalOutput: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Verification failed. Please try again.'];
    }

    grantNotifyApplicantFinalOutputVerified($crad, $detail['application'], $reference, $userName);

    return [
        'ok'     => true,
        'detail' => grantGetFinalOutputDetail($crad, $applicationId),
    ];
}

/**
 * @return array{ok: bool, error?: string, detail?: array<string, mixed>|null}
 */
function grantReturnFinalOutput(
    PDO $crad,
    int $applicationId,
    string $reason,
    int $userId,
    string $userName
): array {
    grantEnsureFinalOutputTables($crad);

    $detail = grantGetFinalOutputDetail($crad, $applicationId);
    if ($detail === null || empty($detail['can_verify'])) {
        return ['ok' => false, 'error' => 'This submission cannot be returned.'];
    }

    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'error' => 'Return reason is required.'];
    }

    $submission = $detail['submission'];
    if ($submission === null) {
        return ['ok' => false, 'error' => 'Submission not found.'];
    }

    try {
        $crad->beginTransaction();

        $subId = (int) ($submission['id'] ?? 0);
        $returnSub = $crad->prepare("
            UPDATE grant_final_output_submissions
               SET status = 'RETURNED_FOR_CORRECTION',
                   return_reason = ?,
                   reviewed_by_user_id = ?,
                   reviewed_by_name = ?,
                   reviewed_at = NOW(),
                   updated_at = NOW()
             WHERE id = ?
        ");
        $returnSub->execute([$reason, $userId > 0 ? $userId : null, $userName, $subId]);

        $appUpdate = $crad->prepare('UPDATE grant_applications SET status = ?, updated_at = NOW() WHERE id = ?');
        $appUpdate->execute([grantStatusApprovedFunded(), $applicationId]);

        $crad->commit();
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantReturnFinalOutput: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to return submission. Please try again.'];
    }

    grantNotifyApplicantFinalOutputReturned($crad, $detail['application'], $reason, $userName);

    return [
        'ok'     => true,
        'detail' => grantGetFinalOutputDetail($crad, $applicationId),
    ];
}

function grantGenerateRepositoryReference(PDO $crad): string
{
    $year = date('Y');
    $prefix = 'PIP-' . $year . '-';

    $stmt = $crad->prepare("
        SELECT repository_reference
          FROM grant_publications_ip_repository
         WHERE repository_reference LIKE ?
         ORDER BY id DESC
         LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $last = (string) ($stmt->fetchColumn() ?: '');
    $next = 1;
    if ($last !== '' && preg_match('/-(\d+)$/', $last, $m)) {
        $next = (int) $m[1] + 1;
    }

    return $prefix . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
}

/**
 * @param list<array<string, mixed>> $overview
 */
function grantFinalOutputOverviewFingerprint(array $overview): string
{
    $parts = [];
    foreach ($overview as $row) {
        $parts[] = implode('|', [
            (int) ($row['grant_application_id'] ?? 0),
            (string) ($row['application_status'] ?? ''),
            (string) ($row['submission_status'] ?? ''),
            (string) ($row['submission_updated_at'] ?? ''),
            (string) ($row['repository_reference'] ?? ''),
            (string) ($row['verified_at'] ?? ''),
        ]);
    }

    return md5(implode(';', $parts));
}

/**
 * @param array<string, mixed>|null $detail
 */
function grantFinalOutputDetailFingerprint(?array $detail): string
{
    if ($detail === null) {
        return '';
    }

    $app = $detail['application'] ?? [];
    $sub = $detail['submission'] ?? [];
    $repo = $detail['repository'] ?? [];

    return md5(implode('|', [
        (int) ($app['id'] ?? 0),
        (string) ($app['status'] ?? ''),
        (string) ($sub['status'] ?? ''),
        (string) ($sub['updated_at'] ?? ''),
        (string) ($sub['return_reason'] ?? ''),
        (string) ($repo['verified_at'] ?? ''),
        (string) ($repo['repository_reference'] ?? ''),
    ]));
}

/**
 * @param array<string, mixed> $application
 */
function grantNotifyCradFinalOutputSubmitted(
    PDO $crad,
    array $application,
    string $finalTitle,
    string $submittedByName
): void {
    require_once __DIR__ . '/grant-evaluation-helpers.php';

    if (!function_exists('db')) {
        return;
    }

    $mainDb = db();
    if (!$mainDb) {
        return;
    }

    $applicationId = (int) ($application['id'] ?? 0);
    if ($applicationId <= 0) {
        return;
    }

    $cradUsers = $mainDb->query("
        SELECT id FROM users WHERE role_key = 'crad_officer' AND status = 'active'
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $ref = trim((string) ($application['proposal_reference'] ?? ''));
    $refLabel = $ref !== '' ? $ref : ('Proposal #' . $applicationId);
    $url = grantPublicationsIpUrl($applicationId);
    $body = sprintf(
        '%s — final output submitted by %s. Title: %s. Verify under Outputs & Records → Publications & IP.',
        $refLabel,
        $submittedByName !== '' ? $submittedByName : 'Researcher',
        mb_strimwidth($finalTitle, 0, 120, '…')
    );

    foreach ($cradUsers as $row) {
        $userId = (int) ($row['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }

        grantInsertGrantProposalNotification(
            $crad,
            $applicationId,
            $userId,
            'crad_officer',
            'grant_final_output_submitted',
            'grant-proposal:final_output_submitted:' . $applicationId . ':u' . $userId,
            'Final Output Submitted',
            $body,
            $url
        );
    }
}

/**
 * @param array<string, mixed> $application
 */
function grantNotifyApplicantFinalOutputVerified(
    PDO $crad,
    array $application,
    string $repositoryReference,
    string $verifiedByName
): void {
    require_once __DIR__ . '/grant-evaluation-helpers.php';

    $applicationId = (int) ($application['id'] ?? 0);
    $recipientUserId = (int) ($application['applicant_user_id'] ?? 0);
    if ($applicationId <= 0 || $recipientUserId <= 0) {
        return;
    }

    $ref = trim((string) ($application['proposal_reference'] ?? ''));
    $refLabel = $ref !== '' ? $ref : ('Proposal #' . $applicationId);

    $body = sprintf(
        '%s — your final output has been verified (OUTPUT_VERIFIED) and recorded in the Publications & IP Repository (%s). Verified by %s. Proceed to Document Repository for permanent archiving.',
        $refLabel,
        $repositoryReference,
        $verifiedByName !== '' ? $verifiedByName : 'CRAD Staff'
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

    grantInsertGrantProposalNotification(
        $crad,
        $applicationId,
        $recipientUserId,
        $recipientRole,
        'grant_final_output_verified',
        'grant-proposal:final_output_verified:' . $applicationId,
        'Output Verified',
        $body,
        grantPublicationsIpUrl($applicationId)
    );
}

/**
 * @param array<string, mixed> $application
 */
function grantNotifyApplicantFinalOutputReturned(
    PDO $crad,
    array $application,
    string $reason,
    string $returnedByName
): void {
    require_once __DIR__ . '/grant-evaluation-helpers.php';

    $applicationId = (int) ($application['id'] ?? 0);
    $recipientUserId = (int) ($application['applicant_user_id'] ?? 0);
    if ($applicationId <= 0 || $recipientUserId <= 0) {
        return;
    }

    $ref = trim((string) ($application['proposal_reference'] ?? ''));
    $refLabel = $ref !== '' ? $ref : ('Proposal #' . $applicationId);

    $body = sprintf(
        '%s — your final output was returned for correction by %s. Reason: %s. Resubmit under Publications & IP.',
        $refLabel,
        $returnedByName !== '' ? $returnedByName : 'CRAD Staff',
        mb_strimwidth($reason, 0, 240, '…')
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

    grantInsertGrantProposalNotification(
        $crad,
        $applicationId,
        $recipientUserId,
        $recipientRole,
        'grant_final_output_returned',
        'grant-proposal:final_output_returned:' . $applicationId,
        'Final Output Returned',
        $body,
        grantPublicationsIpUrl($applicationId)
    );
}
