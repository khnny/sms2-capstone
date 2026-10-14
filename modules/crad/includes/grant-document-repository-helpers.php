<?php
/**
 * Grant — Document Repository archive helpers (Step 21)
 */
declare(strict_types=1);

require_once __DIR__ . '/grant-helpers.php';
require_once __DIR__ . '/grant-final-output-helpers.php';
require_once __DIR__ . '/grant-evaluation-helpers.php';
require_once __DIR__ . '/grant-approval-helpers.php';
require_once __DIR__ . '/grant-funding-helpers.php';
require_once __DIR__ . '/grant-milestone-helpers.php';
require_once __DIR__ . '/grant-funded-research-helpers.php';

function grantDocumentRepositoryUrl(int $applicationId = 0): string
{
    $url = BASE_URL . '/modules/crad/pages/document-repository.php';
    if ($applicationId > 0) {
        $url .= '?id=' . $applicationId;
    }

    return $url;
}

/**
 * @return array<string, string>
 */
function grantDocumentRepositoryCategories(): array
{
    return [
        'proposal'            => 'Proposal',
        'proposal_revisions'  => 'Proposal Revisions',
        'reviewer_scores'     => 'Reviewer Scores',
        'approval_history'    => 'Approval History',
        'approved_budget'     => 'Approved Budget',
        'project_progress'    => 'Project Progress',
        'final_output'        => 'Final Output',
        'publication'         => 'Publication',
        'ip_documentation'    => 'IP Documentation',
    ];
}

function grantUserCanArchiveDocuments(): bool
{
    return grantUserCanManage();
}

function grantRequireDocumentRepositoryAccess(): void
{
    if (grantUserCanArchiveDocuments()) {
        return;
    }

    grantRedirectUnauthorized();
}

function grantEnsureDocumentRepositoryTables(PDO $crad): void
{
    grantEnsureTables($crad);
    grantEnsureFinalOutputTables($crad);

    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $crad->exec("
        CREATE TABLE IF NOT EXISTS `crad_grant_document_repository` (
            id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
            grant_application_id    INT UNSIGNED NOT NULL,
            archive_reference       VARCHAR(40) NOT NULL DEFAULT '',
            status                  ENUM('ARCHIVED') NOT NULL DEFAULT 'ARCHIVED',
            item_count              INT UNSIGNED NOT NULL DEFAULT 0,
            archived_by_user_id     INT UNSIGNED NULL DEFAULT NULL,
            archived_by_name        VARCHAR(120) NULL DEFAULT NULL,
            archived_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_gdr_application (grant_application_id),
            KEY idx_gdr_reference (archive_reference),
            KEY idx_gdr_archived (archived_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $crad->exec("
        CREATE TABLE IF NOT EXISTS `crad_grant_document_repository_items` (
            id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
            repository_id           INT UNSIGNED NOT NULL,
            grant_application_id    INT UNSIGNED NOT NULL,
            category                VARCHAR(40) NOT NULL,
            item_label              VARCHAR(255) NOT NULL DEFAULT '',
            item_type               ENUM('file','record') NOT NULL DEFAULT 'record',
            file_path               VARCHAR(255) NULL DEFAULT NULL,
            file_original           VARCHAR(255) NULL DEFAULT NULL,
            download_url            VARCHAR(500) NULL DEFAULT NULL,
            summary_text            TEXT NULL,
            metadata_json           TEXT NULL,
            sort_order              SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_gdri_repository (repository_id),
            KEY idx_gdri_application (grant_application_id),
            KEY idx_gdri_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * @return list<array<string, mixed>>
 */
function grantGetDocumentRepositoryOverview(PDO $crad): array
{
    grantEnsureDocumentRepositoryTables($crad);

    $stmt = $crad->prepare("
        SELECT ga.id AS grant_application_id,
               ga.proposal_reference,
               ga.research_title,
               ga.applicant_name,
               ga.college_dept,
               ga.status AS application_status,
               ga.updated_at AS application_updated_at,
               go.funding_title,
               dr.id AS archive_id,
               dr.archive_reference,
               dr.item_count,
               dr.archived_at,
               dr.archived_by_name,
               dr.updated_at AS archive_updated_at,
               pip.repository_reference AS publication_reference
          FROM `crad_grant_applications` ga
         INNER JOIN `crad_grant_opportunities` go ON go.id = ga.grant_opportunity_id
          LEFT JOIN `crad_grant_document_repository` dr ON dr.grant_application_id = ga.id
          LEFT JOIN `crad_grant_publications_ip_repository` pip ON pip.grant_application_id = ga.id
         WHERE ga.status IN (?, ?)
         ORDER BY
            CASE WHEN dr.id IS NULL AND ga.status = ? THEN 0
                 WHEN dr.id IS NULL THEN 1
                 ELSE 2 END,
            COALESCE(dr.archived_at, ga.updated_at) DESC,
            ga.id DESC
    ");
    $stmt->execute([
        grantStatusOutputVerified(),
        grantStatusArchived(),
        grantStatusOutputVerified(),
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as &$row) {
        $row['workflow_label'] = grantDocumentRepositoryWorkflowLabel($row);
        $row['workflow_class'] = grantDocumentRepositoryWorkflowClass($row);
        $row['needs_archive'] = grantDocumentRepositoryNeedsArchive($row);
    }
    unset($row);

    return $rows;
}

/**
 * @param array<string, mixed> $row
 */
function grantDocumentRepositoryWorkflowLabel(array $row): string
{
    if (!empty($row['archive_id'])) {
        return 'ARCHIVED';
    }
    if ((string) ($row['application_status'] ?? '') === grantStatusOutputVerified()) {
        return 'READY TO ARCHIVE';
    }

    return 'OUTPUT VERIFIED';
}

/**
 * @param array<string, mixed> $row
 */
function grantDocumentRepositoryWorkflowClass(array $row): string
{
    $label = grantDocumentRepositoryWorkflowLabel($row);

    return match ($label) {
        'ARCHIVED'          => 'archived',
        'READY TO ARCHIVE'  => 'ready',
        default             => 'verified',
    };
}

/**
 * @param array<string, mixed> $row
 */
function grantDocumentRepositoryNeedsArchive(array $row): bool
{
    return empty($row['archive_id'])
        && (string) ($row['application_status'] ?? '') === grantStatusOutputVerified();
}

/**
 * @return array<string, mixed>|null
 */
function grantGetDocumentRepositoryDetail(PDO $crad, int $applicationId): ?array
{
    grantEnsureDocumentRepositoryTables($crad);

    if ($applicationId <= 0 || !grantUserCanArchiveDocuments()) {
        return null;
    }

    $stmt = $crad->prepare("
        SELECT ga.*, go.funding_title
          FROM `crad_grant_applications` ga
         INNER JOIN `crad_grant_opportunities` go ON go.id = ga.grant_opportunity_id
         WHERE ga.id = ?
         LIMIT 1
    ");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        return null;
    }

    $appStatus = (string) ($application['status'] ?? '');
    if (!in_array($appStatus, [grantStatusOutputVerified(), grantStatusArchived()], true)) {
        return null;
    }

    $archiveStmt = $crad->prepare('SELECT * FROM `crad_grant_document_repository` WHERE grant_application_id = ? LIMIT 1');
    $archiveStmt->execute([$applicationId]);
    $archive = $archiveStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $categories = [];
    if ($archive !== null) {
        $itemStmt = $crad->prepare("
            SELECT * FROM `crad_grant_document_repository_items`
             WHERE repository_id = ?
             ORDER BY category ASC, sort_order ASC, id ASC
        ");
        $itemStmt->execute([(int) ($archive['id'] ?? 0)]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach (grantDocumentRepositoryCategories() as $key => $label) {
            $categories[$key] = [
                'key'   => $key,
                'label' => $label,
                'items' => array_values(array_filter($items, static fn(array $item): bool => (string) ($item['category'] ?? '') === $key)),
            ];
        }
    } else {
        $manifest = grantBuildDocumentRepositoryManifest($crad, $applicationId);
        foreach (grantDocumentRepositoryCategories() as $key => $label) {
            $categories[$key] = [
                'key'   => $key,
                'label' => $label,
                'items' => $manifest[$key] ?? [],
            ];
        }
    }

    $totalItems = 0;
    foreach ($categories as $cat) {
        $totalItems += count($cat['items'] ?? []);
    }

    return [
        'application'      => $application,
        'archive'          => $archive,
        'categories'       => $categories,
        'total_items'      => $totalItems,
        'workflow_label'   => grantDocumentRepositoryWorkflowLabel([
            'archive_id'         => $archive['id'] ?? null,
            'application_status' => $appStatus,
        ]),
        'workflow_class'   => grantDocumentRepositoryWorkflowClass([
            'archive_id'         => $archive['id'] ?? null,
            'application_status' => $appStatus,
        ]),
        'can_archive'      => $archive === null && $appStatus === grantStatusOutputVerified(),
        'is_archived'      => $archive !== null,
        'file_base_url'    => BASE_URL . '/modules/crad/grant-document-repository-file.php',
    ];
}

/**
 * @return array<string, list<array<string, mixed>>>
 */
function grantBuildDocumentRepositoryManifest(PDO $crad, int $applicationId): array
{
    grantEnsureDocumentRepositoryTables($crad);
    grantEnsureEvaluationTables($crad);
    grantEnsureApprovalTables($crad);
    grantEnsureFundingTables($crad);
    grantEnsureMilestoneTables($crad);
    grantEnsureFundedResearchTables($crad);

    $manifest = [];
    foreach (array_keys(grantDocumentRepositoryCategories()) as $key) {
        $manifest[$key] = [];
    }

    $appStmt = $crad->prepare('SELECT * FROM `crad_grant_applications` WHERE id = ? LIMIT 1');
    $appStmt->execute([$applicationId]);
    $app = $appStmt->fetch(PDO::FETCH_ASSOC);
    if (!$app) {
        return $manifest;
    }

    $order = 0;
    $proposalFiles = [
        ['field' => 'proposal', 'label' => 'Proposal PDF', 'path' => $app['proposal_pdf'] ?? null, 'original' => $app['proposal_pdf_original'] ?? null],
        ['field' => 'supporting', 'label' => 'Supporting Documents', 'path' => $app['supporting_docs'] ?? null, 'original' => $app['supporting_docs_original'] ?? null],
        ['field' => 'ethics', 'label' => 'Ethics Clearance', 'path' => $app['ethics_doc'] ?? null, 'original' => $app['ethics_doc_original'] ?? null],
    ];
    foreach ($proposalFiles as $file) {
        if (empty($file['path'])) {
            continue;
        }
        $manifest['proposal'][] = grantDocumentRepositoryManifestItem(
            'proposal',
            (string) $file['label'],
            'file',
            (string) $file['path'],
            (string) ($file['original'] ?? ''),
            grantProposalFileUrl($applicationId, (string) $file['field']),
            null,
            ['field' => $file['field']],
            $order++
        );
    }

    $verStmt = $crad->prepare("
        SELECT * FROM `crad_grant_proposal_versions`
         WHERE grant_application_id = ?
         ORDER BY version_number ASC
    ");
    $verStmt->execute([$applicationId]);
    foreach ($verStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $ver) {
        $verNum = (int) ($ver['version_number'] ?? 1);
        $verLabel = (string) ($ver['version_label'] ?? ('Version ' . $verNum));
        if (!empty($ver['proposal_pdf'])) {
            $manifest['proposal_revisions'][] = grantDocumentRepositoryManifestItem(
                'proposal_revisions',
                $verLabel . ' — Proposal PDF',
                'file',
                (string) $ver['proposal_pdf'],
                (string) ($ver['proposal_pdf_original'] ?? ''),
                grantProposalFileUrl($applicationId, 'proposal') . '&version=' . $verNum,
                null,
                ['version_number' => $verNum],
                $order++
            );
        }
        if (!empty($ver['supporting_docs'])) {
            $manifest['proposal_revisions'][] = grantDocumentRepositoryManifestItem(
                'proposal_revisions',
                $verLabel . ' — Supporting Docs',
                'file',
                (string) $ver['supporting_docs'],
                (string) ($ver['supporting_docs_original'] ?? ''),
                null,
                null,
                ['version_number' => $verNum],
                $order++
            );
        }
    }

    $evalStmt = $crad->prepare("
        SELECT * FROM `crad_grant_proposal_evaluations`
         WHERE grant_application_id = ?
         ORDER BY submitted_at ASC
    ");
    $evalStmt->execute([$applicationId]);
    foreach ($evalStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $eval) {
        $evalType = (string) ($eval['evaluation_type'] ?? 'committee');
        $evalLabel = grantEvaluationStepLabel($evalType);
        $summary = sprintf(
            "%s — %s\nTotal Score: %s / 100\nRationale: %s | Methodology: %s | Budget: %s | Team: %s | Compliance: %s\nRecommendation: %s",
            $evalLabel,
            (string) ($eval['evaluator_name'] ?? 'Evaluator'),
            number_format((float) ($eval['total_score'] ?? 0), 2),
            number_format((float) ($eval['score_rationale'] ?? 0), 2),
            number_format((float) ($eval['score_methodology'] ?? 0), 2),
            number_format((float) ($eval['score_budget'] ?? 0), 2),
            number_format((float) ($eval['score_team_capability'] ?? 0), 2),
            number_format((float) ($eval['score_compliance'] ?? 0), 2),
            (string) ($eval['recommendation'] ?? '—')
        );
        if (!empty($eval['comments'])) {
            $summary .= "\nComments: " . (string) $eval['comments'];
        }
        $manifest['reviewer_scores'][] = grantDocumentRepositoryManifestItem(
            'reviewer_scores',
            $evalLabel . ' — ' . (string) ($eval['evaluator_name'] ?? 'Evaluator'),
            'record',
            null,
            null,
            null,
            $summary,
            ['evaluation_id' => (int) ($eval['id'] ?? 0)],
            $order++
        );
    }

    $stepStmt = $crad->prepare("
        SELECT * FROM `crad_grant_proposal_approval_steps`
         WHERE grant_application_id = ?
         ORDER BY step_order ASC
    ");
    $stepStmt->execute([$applicationId]);
    foreach ($stepStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $step) {
        $summary = sprintf(
            "%s — %s\nStatus: %s\nApprover: %s\nActed: %s",
            (string) ($step['step_label'] ?? ''),
            (string) ($step['step_key'] ?? ''),
            (string) ($step['status'] ?? 'Queued'),
            (string) ($step['approver_name'] ?? '—'),
            !empty($step['acted_at']) ? (string) $step['acted_at'] : '—'
        );
        if (!empty($step['remarks'])) {
            $summary .= "\nRemarks: " . (string) $step['remarks'];
        }
        $manifest['approval_history'][] = grantDocumentRepositoryManifestItem(
            'approval_history',
            (string) ($step['step_label'] ?? 'Approval Step'),
            'record',
            null,
            null,
            null,
            $summary,
            ['step_id' => (int) ($step['id'] ?? 0)],
            $order++
        );
    }

    $approved = (float) ($app['approved_budget'] ?? $app['requested_budget'] ?? 0);
    $budgetSummary = 'Approved Budget: ' . grantFormatPeso($approved);
    $disbStmt = $crad->prepare("
        SELECT * FROM `crad_grant_funding_disbursements`
         WHERE grant_application_id = ?
         ORDER BY tranche_number ASC
    ");
    $disbStmt->execute([$applicationId]);
    $disbursements = $disbStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    if ($disbursements !== []) {
        $budgetSummary .= "\n\nFund Releases:";
        foreach ($disbursements as $disb) {
            $budgetSummary .= sprintf(
                "\n- %s: %s (%s)%s",
                (string) ($disb['tranche_label'] ?? 'Tranche'),
                grantFormatPeso((float) ($disb['amount_released'] ?? 0)),
                (string) ($disb['status'] ?? 'Pending'),
                !empty($disb['reference_number']) ? ' Ref: ' . $disb['reference_number'] : ''
            );
        }
    }
    $manifest['approved_budget'][] = grantDocumentRepositoryManifestItem(
        'approved_budget',
        'Approved Budget & Disbursements',
        'record',
        null,
        null,
        grantBudgetDisbursementUrl($applicationId),
        $budgetSummary,
        ['approved_budget' => $approved, 'tranche_count' => count($disbursements)],
        $order++
    );

    $msStmt = $crad->prepare("
        SELECT * FROM `crad_grant_funded_project_milestones`
         WHERE grant_application_id = ?
         ORDER BY milestone_order ASC
    ");
    $msStmt->execute([$applicationId]);
    foreach ($msStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $ms) {
        $summary = sprintf(
            "%s — %s (%s%%)\nDue: %s\nRemarks: %s",
            (string) ($ms['milestone_name'] ?? 'Milestone'),
            (string) ($ms['status'] ?? 'Pending'),
            number_format((float) ($ms['completion_pct'] ?? 0), 0),
            !empty($ms['due_date']) ? (string) $ms['due_date'] : '—',
            (string) ($ms['remarks'] ?? '—')
        );
        $item = grantDocumentRepositoryManifestItem(
            'project_progress',
            (string) ($ms['milestone_name'] ?? 'Milestone'),
            !empty($ms['supporting_doc']) ? 'file' : 'record',
            !empty($ms['supporting_doc']) ? (string) $ms['supporting_doc'] : null,
            !empty($ms['supporting_doc_original']) ? (string) $ms['supporting_doc_original'] : null,
            !empty($ms['supporting_doc']) ? grantCradScriptUrl('grant-milestone-file.php', ['id' => (int) ($ms['id'] ?? 0)]) : null,
            $summary,
            ['milestone_id' => (int) ($ms['id'] ?? 0)],
            $order++
        );
        $manifest['project_progress'][] = $item;
    }

    $evStmt = $crad->prepare("
        SELECT * FROM `crad_grant_funded_progress_evidence`
         WHERE grant_application_id = ?
         ORDER BY created_at ASC
    ");
    $evStmt->execute([$applicationId]);
    foreach ($evStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $ev) {
        $manifest['project_progress'][] = grantDocumentRepositoryManifestItem(
            'project_progress',
            'Evidence — ' . (string) ($ev['evidence_title'] ?? 'Progress'),
            !empty($ev['file_path']) ? 'file' : 'record',
            !empty($ev['file_path']) ? (string) $ev['file_path'] : null,
            !empty($ev['file_original']) ? (string) $ev['file_original'] : null,
            !empty($ev['file_path']) ? grantCradScriptUrl('grant-funded-research-file.php', ['id' => (int) ($ev['id'] ?? 0)]) : null,
            (string) ($ev['notes'] ?? ''),
            ['evidence_id' => (int) ($ev['id'] ?? 0)],
            $order++
        );
    }

    $subStmt = $crad->prepare('SELECT * FROM `crad_grant_final_output_submissions` WHERE grant_application_id = ? LIMIT 1');
    $subStmt->execute([$applicationId]);
    $submission = $subStmt->fetch(PDO::FETCH_ASSOC);
    if ($submission) {
        if (!empty($submission['final_pdf_path'])) {
            $manifest['final_output'][] = grantDocumentRepositoryManifestItem(
                'final_output',
                'Final Research PDF',
                'file',
                (string) $submission['final_pdf_path'],
                (string) ($submission['final_pdf_original'] ?? ''),
                BASE_URL . '/modules/crad/grant-final-output-file.php?type=final_pdf&submission_id=' . (int) ($submission['id'] ?? 0),
                (string) ($submission['abstract'] ?? ''),
                ['submission_id' => (int) ($submission['id'] ?? 0)],
                $order++
            );
        }
        if (!empty($submission['supporting_files_json'])) {
            $files = json_decode((string) $submission['supporting_files_json'], true);
            if (is_array($files)) {
                foreach ($files as $idx => $file) {
                    $manifest['final_output'][] = grantDocumentRepositoryManifestItem(
                        'final_output',
                        'Supporting — ' . (string) ($file['original_name'] ?? 'File'),
                        'file',
                        (string) ($file['path'] ?? ''),
                        (string) ($file['original_name'] ?? ''),
                        BASE_URL . '/modules/crad/grant-final-output-file.php?type=supporting&submission_id=' . (int) ($submission['id'] ?? 0) . '&index=' . (int) $idx,
                        null,
                        ['submission_id' => (int) ($submission['id'] ?? 0), 'index' => (int) $idx],
                        $order++
                    );
                }
            }
        }
    }

    $pipStmt = $crad->prepare('SELECT * FROM `crad_grant_publications_ip_repository` WHERE grant_application_id = ? LIMIT 1');
    $pipStmt->execute([$applicationId]);
    $pip = $pipStmt->fetch(PDO::FETCH_ASSOC);
    if ($pip) {
        $pubSummary = sprintf(
            "Reference: %s\nTitle: %s\nAuthors: %s\nType: %s\nJournal/Conference: %s\nDOI: %s\nURL: %s",
            (string) ($pip['repository_reference'] ?? ''),
            (string) ($pip['final_research_title'] ?? ''),
            (string) ($pip['authors'] ?? ''),
            (string) ($pip['publication_type'] ?? ''),
            (string) ($pip['journal_conference'] ?? ''),
            (string) ($pip['doi'] ?? '—'),
            (string) ($pip['publication_url'] ?? '—')
        );
        $manifest['publication'][] = grantDocumentRepositoryManifestItem(
            'publication',
            'Publication Record — ' . (string) ($pip['repository_reference'] ?? ''),
            !empty($pip['final_pdf_path']) ? 'file' : 'record',
            !empty($pip['final_pdf_path']) ? (string) $pip['final_pdf_path'] : null,
            !empty($pip['final_pdf_original']) ? (string) $pip['final_pdf_original'] : null,
            !empty($pip['final_pdf_path']) && $submission
                ? BASE_URL . '/modules/crad/grant-final-output-file.php?type=final_pdf&submission_id=' . (int) ($submission['id'] ?? 0)
                : null,
            $pubSummary,
            ['repository_reference' => (string) ($pip['repository_reference'] ?? '')],
            $order++
        );

        $ipParts = [];
        if (!empty($pip['copyright_info'])) {
            $ipParts[] = 'Copyright: ' . $pip['copyright_info'];
        }
        if (!empty($pip['patent_info'])) {
            $ipParts[] = 'Patent: ' . $pip['patent_info'];
        }
        if (!empty($pip['other_ip_info'])) {
            $ipParts[] = 'Other IP: ' . $pip['other_ip_info'];
        }
        if (!empty($pip['ip_information'])) {
            $ipParts[] = 'IP Information: ' . $pip['ip_information'];
        }
        if ($ipParts !== []) {
            $manifest['ip_documentation'][] = grantDocumentRepositoryManifestItem(
                'ip_documentation',
                'IP Documentation',
                'record',
                null,
                null,
                grantPublicationsIpUrl($applicationId),
                implode("\n", $ipParts),
                ['repository_reference' => (string) ($pip['repository_reference'] ?? '')],
                $order++
            );
        }
    } elseif ($submission) {
        $ipParts = [];
        if (!empty($submission['copyright_info'])) {
            $ipParts[] = 'Copyright: ' . $submission['copyright_info'];
        }
        if (!empty($submission['patent_info'])) {
            $ipParts[] = 'Patent: ' . $submission['patent_info'];
        }
        if (!empty($submission['other_ip_info'])) {
            $ipParts[] = 'Other IP: ' . $submission['other_ip_info'];
        }
        if (!empty($submission['ip_information'])) {
            $ipParts[] = 'IP Information: ' . $submission['ip_information'];
        }
        if ($ipParts !== []) {
            $manifest['ip_documentation'][] = grantDocumentRepositoryManifestItem(
                'ip_documentation',
                'IP Documentation',
                'record',
                null,
                null,
                grantPublicationsIpUrl($applicationId),
                implode("\n", $ipParts),
                null,
                $order++
            );
        }
    }

    return $manifest;
}

/**
 * @param array<string, mixed>|null $metadata
 * @return array<string, mixed>
 */
function grantDocumentRepositoryManifestItem(
    string $category,
    string $label,
    string $type,
    ?string $filePath,
    ?string $fileOriginal,
    ?string $downloadUrl,
    ?string $summary,
    ?array $metadata,
    int $sortOrder
): array {
    return [
        'category'      => $category,
        'item_label'    => $label,
        'item_type'     => $type,
        'file_path'     => $filePath,
        'file_original' => $fileOriginal,
        'download_url'  => $downloadUrl,
        'summary_text'  => $summary,
        'metadata_json' => $metadata !== null ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
        'sort_order'    => $sortOrder,
    ];
}

/**
 * @return array{ok: bool, error?: string, detail?: array<string, mixed>|null}
 */
function grantArchiveToDocumentRepository(PDO $crad, int $applicationId, int $userId, string $userName): array
{
    grantEnsureDocumentRepositoryTables($crad);

    $detail = grantGetDocumentRepositoryDetail($crad, $applicationId);
    if ($detail === null || empty($detail['can_archive'])) {
        return ['ok' => false, 'error' => 'This project is not ready for archiving.'];
    }

    $manifest = grantBuildDocumentRepositoryManifest($crad, $applicationId);
    $allItems = [];
    foreach ($manifest as $items) {
        foreach ($items as $item) {
            $allItems[] = $item;
        }
    }

    if ($allItems === []) {
        return ['ok' => false, 'error' => 'No records found to archive for this project.'];
    }

    $reference = grantGenerateDocumentArchiveReference($crad);

    try {
        $crad->beginTransaction();

        $insert = $crad->prepare("
            INSERT INTO `crad_grant_document_repository`
                (grant_application_id, archive_reference, status, item_count,
                 archived_by_user_id, archived_by_name, archived_at)
            VALUES (?, ?, 'ARCHIVED', ?, ?, ?, NOW())
        ");
        $insert->execute([
            $applicationId,
            $reference,
            count($allItems),
            $userId > 0 ? $userId : null,
            $userName,
        ]);
        $repositoryId = (int) $crad->lastInsertId();

        $itemInsert = $crad->prepare("
            INSERT INTO `crad_grant_document_repository_items`
                (repository_id, grant_application_id, category, item_label, item_type,
                 file_path, file_original, download_url, summary_text, metadata_json, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($allItems as $item) {
            $itemInsert->execute([
                $repositoryId,
                $applicationId,
                (string) ($item['category'] ?? ''),
                (string) ($item['item_label'] ?? ''),
                (string) ($item['item_type'] ?? 'record'),
                $item['file_path'] ?? null,
                $item['file_original'] ?? null,
                $item['download_url'] ?? null,
                $item['summary_text'] ?? null,
                $item['metadata_json'] ?? null,
                (int) ($item['sort_order'] ?? 0),
            ]);
        }

        $appUpdate = $crad->prepare('UPDATE `crad_grant_applications` SET status = ?, updated_at = NOW() WHERE id = ?');
        $appUpdate->execute([grantStatusArchived(), $applicationId]);

        $crad->commit();
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantArchiveToDocumentRepository: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to archive records. Please try again.'];
    }

    return [
        'ok'     => true,
        'detail' => grantGetDocumentRepositoryDetail($crad, $applicationId),
    ];
}

function grantGenerateDocumentArchiveReference(PDO $crad): string
{
    $year = date('Y');
    $prefix = 'DAR-' . $year . '-';

    $stmt = $crad->prepare("
        SELECT archive_reference FROM `crad_grant_document_repository`
         WHERE archive_reference LIKE ?
         ORDER BY id DESC LIMIT 1
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
function grantDocumentRepositoryOverviewFingerprint(array $overview): string
{
    $parts = [];
    foreach ($overview as $row) {
        $parts[] = implode('|', [
            (int) ($row['grant_application_id'] ?? 0),
            (string) ($row['application_status'] ?? ''),
            (string) ($row['archive_id'] ?? ''),
            (string) ($row['archive_updated_at'] ?? ''),
            (string) ($row['item_count'] ?? ''),
        ]);
    }

    return md5(implode(';', $parts));
}

/**
 * @param array<string, mixed>|null $detail
 */
function grantDocumentRepositoryDetailFingerprint(?array $detail): string
{
    if ($detail === null) {
        return '';
    }

    $app = $detail['application'] ?? [];
    $archive = $detail['archive'] ?? [];

    return md5(implode('|', [
        (int) ($app['id'] ?? 0),
        (string) ($app['status'] ?? ''),
        (int) ($archive['id'] ?? 0),
        (string) ($archive['updated_at'] ?? ''),
        (int) ($detail['total_items'] ?? 0),
    ]));
}
