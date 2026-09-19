<?php
/**
 * CRAD Grant Management — Helper Functions
 *
 * Provides table auto-provisioning and shared query functions for the
 * CORE SYSTEM pages (Dashboard & Analytics, Grant Opportunities,
 * Proposals & Applications) and their JSON API.
 *
 * All functions in this file are idempotent and read-safe:
 * - Table creation uses CREATE TABLE IF NOT EXISTS.
 * - Column additions use ALTER TABLE … ADD COLUMN IF NOT EXISTS (MariaDB) or
 *   a SHOW COLUMNS guard (MySQL 5.7 compat) — safe no-ops when already present.
 * - Status expiry uses UPDATE … WHERE deadline < NOW() (harmless no-op
 *   if all rows are already correct).
 * - No data is ever deleted by any helper here.
 *
 * Requires: modules/crad/config/config.php (cradDb / getCradDatabaseConnection).
 */

declare(strict_types=1);

/**
 * Roles that can publish grant calls and manage grant opportunities.
 */
function grantUserCanManage(): bool
{
    if (!function_exists('smsRoleAllowedForModule')) {
        require_once dirname(__DIR__, 3) . '/includes/authentication.php';
    }

    if (smsIsGrantedAdminRole(getCurrentUserRoleKey())) {
        return true;
    }

    if (smsRoleAllowedForModule(['crad_officer'], 'crad')) {
        return true;
    }

    if (smsRoleAllowedForModule(['research_grant'], 'crad_grant')) {
        return true;
    }

    return smsHasGrantedModuleAdminAccess('crad')
        || smsHasGrantedModuleAdminAccess('crad_grant');
}

/**
 * Roles that can browse grant calls and submit proposals (researchers).
 */
function grantUserCanApply(): bool
{
    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    return in_array($roleKey, ['student', 'adviser'], true);
}

/**
 * Anyone who may open grant opportunity / proposal pages.
 */
function grantUserCanView(): bool
{
    return grantUserCanManage() || grantUserCanApply();
}

/**
 * Sidebar / layout module key for the current grant page user.
 */
function grantActiveModuleKey(): string
{
    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    return match ($roleKey) {
        'research_grant', 'review_committee' => 'crad_grant',
        'student'        => 'student_portal',
        'adviser'        => 'faculty',
        default          => 'crad',
    };
}

/**
 * Breadcrumb parent label on grant pages.
 */
function grantBreadcrumbModuleLabel(): string
{
    return match (grantActiveModuleKey()) {
        'crad_grant'     => 'Research Grant',
        'student_portal' => 'Student Portal',
        'faculty'        => 'Faculty',
        default          => 'CRAD',
    };
}

/**
 * Absolute URL to a script in modules/crad, even if BASE_URL was
 * detected from a nested /modules/crad/api/ request.
 */
function grantCradScriptUrl(string $script, array $query = []): string
{
    $base = rtrim((string) BASE_URL, '/');
    if (preg_match('#/modules/crad$#i', $base) === 1) {
        $base = (string) preg_replace('#/modules/crad$#i', '', $base);
    }
    $url = $base . '/modules/crad/' . ltrim($script, '/');
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

/**
 * Breadcrumb parent URL on grant pages.
 */
function grantBreadcrumbModuleUrl(): string
{
    return match (grantActiveModuleKey()) {
        'student_portal' => BASE_URL . '/modules/student-portal/pages/dashboard.php',
        'faculty'        => BASE_URL . '/modules/faculty/pages/approved-research.php',
        'crad_grant'     => BASE_URL . '/modules/crad/pages/grant-opportunities.php',
        default          => BASE_URL . '/modules/crad/index.php',
    };
}

/**
 * Redirect unauthorized users away from grant management pages.
 */
function grantRequireManageAccess(): void
{
    if (grantUserCanManage()) {
        return;
    }

    grantRedirectUnauthorized();
}

/**
 * Redirect users who cannot view or apply on grant pages.
 */
function grantRequireViewAccess(): void
{
    if (grantUserCanView()) {
        return;
    }

    grantRedirectUnauthorized();
}

/**
 * @internal
 */
function grantRedirectUnauthorized(): void
{
    require_once dirname(__DIR__, 3) . '/config/config.php';
    require_once dirname(__DIR__, 3) . '/includes/navigation-context.php';

    header('Location: ' . smsRoleHomeUrl(function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : ''));
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// Internal: safely add a column to a table only when it is absent.
// Compatible with MySQL 5.7+ / MariaDB (no IF NOT EXISTS on ALTER in MySQL 5.7).
// ─────────────────────────────────────────────────────────────────────────────
function _grantAddColumnIfMissing(PDO $crad, string $table, string $column, string $definition): void
{
    try {
        // Use INFORMATION_SCHEMA — supports parameterized queries on both MySQL and MariaDB.
        // SHOW COLUMNS … LIKE ? is NOT supported as a prepared statement on MariaDB.
        $stmt = $crad->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = ?
                AND COLUMN_NAME  = ?"
        );
        $stmt->execute([$table, $column]);
        if ((int) $stmt->fetchColumn() === 0) {
            $crad->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        }
    } catch (Throwable $e) {
        error_log("_grantAddColumnIfMissing($table.$column): " . $e->getMessage());
    }
}

/**
 * Ensure grant_opportunities and grant_applications tables exist in crad_db,
 * and that grant_applications contains the full proposal columns introduced
 * for the BRGFAMS Form 1 submission flow.
 *
 * Called once per request by every page that needs grant data.
 * Safe to call repeatedly — CREATE/ALTER are no-ops when already correct.
 *
 * @param  PDO $crad  Active crad_db connection.
 * @return void
 */
function grantEnsureTables(PDO $crad): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    // ── grant_opportunities ─────────────────────────────────────────────────
    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_opportunities (
            id               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            funding_title    VARCHAR(300)    NOT NULL,
            max_funding_cap  DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
            application_deadline DATE        NOT NULL,
            eligibility      VARCHAR(100)    NOT NULL DEFAULT 'Open',
            college_program  VARCHAR(200)    DEFAULT NULL
                COMMENT 'Populated when eligibility = Specific College/Program',
            status           ENUM(
                                 'Open for Application',
                                 'Closed',
                                 'Expired'
                             )               NOT NULL DEFAULT 'Open for Application',
            created_by_user_id INT UNSIGNED  DEFAULT NULL,
            created_by_name  VARCHAR(150)    NOT NULL DEFAULT '',
            created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                 ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_go_status          (status),
            KEY idx_go_deadline        (application_deadline),
            KEY idx_go_created_by      (created_by_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // ── grant_applications (core columns — original schema) ─────────────────
    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_applications (
            id                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            grant_opportunity_id  INT UNSIGNED  NOT NULL
                COMMENT 'FK → grant_opportunities.id',
            research_group_id     INT UNSIGNED  DEFAULT NULL
                COMMENT 'FK → research_groups.id (nullable for non-capstone applicants)',
            group_number          VARCHAR(30)   DEFAULT NULL,
            research_title        VARCHAR(500)  DEFAULT NULL,
            applicant_name        VARCHAR(200)  NOT NULL DEFAULT '',
            applicant_user_id     INT UNSIGNED  DEFAULT NULL,
            application_notes     TEXT          DEFAULT NULL,
            status                ENUM(
                                      'Submitted',
                                      'Under Review',
                                      'Approved',
                                      'Denied',
                                      'Withdrawn'
                                  )             NOT NULL DEFAULT 'Submitted',
            submission_token      VARCHAR(64)   DEFAULT NULL
                COMMENT 'One-time token for duplicate-submission prevention',
            submitted_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                    ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_ga_token       (submission_token),
            KEY idx_ga_opportunity         (grant_opportunity_id),
            KEY idx_ga_group               (research_group_id),
            KEY idx_ga_status              (status),
            KEY idx_ga_submitted           (submitted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // ── grant_applications — proposal columns added for BRGFAMS Form 1 ──────
    // Each ALTER is guarded by SHOW COLUMNS so the call is a no-op on existing rows.
    _grantAddColumnIfMissing($crad, 'grant_applications', 'college_dept',
        "VARCHAR(200) DEFAULT NULL COMMENT 'Academic college / department of the lead proponent' AFTER applicant_name");

    _grantAddColumnIfMissing($crad, 'grant_applications', 'requested_budget',
        "DECIMAL(14,2) DEFAULT NULL COMMENT 'Budget requested by the proponent; must not exceed grant max_funding_cap' AFTER college_dept");

    _grantAddColumnIfMissing($crad, 'grant_applications', 'abstract',
        "TEXT DEFAULT NULL COMMENT 'Executive abstract of the research proposal' AFTER requested_budget");

    _grantAddColumnIfMissing($crad, 'grant_applications', 'objectives',
        "TEXT DEFAULT NULL COMMENT 'Research objectives' AFTER abstract");

    _grantAddColumnIfMissing($crad, 'grant_applications', 'proposal_pdf',
        "VARCHAR(255) DEFAULT NULL COMMENT 'Stored filename of the uploaded proposal PDF/DOC under storage/uploads/grant_proposals/' AFTER objectives");

    _grantAddColumnIfMissing($crad, 'grant_applications', 'proposal_pdf_original',
        "VARCHAR(300) DEFAULT NULL COMMENT 'Original filename of the uploaded proposal document' AFTER proposal_pdf");

    _grantAddColumnIfMissing($crad, 'grant_applications', 'supporting_docs',
        "VARCHAR(255) DEFAULT NULL COMMENT 'Stored filename of optional supporting documents' AFTER proposal_pdf_original");

    _grantAddColumnIfMissing($crad, 'grant_applications', 'supporting_docs_original',
        "VARCHAR(300) DEFAULT NULL COMMENT 'Original filename of optional supporting documents' AFTER supporting_docs");

    _grantAddColumnIfMissing($crad, 'grant_applications', 'ethics_doc',
        "VARCHAR(255) DEFAULT NULL COMMENT 'Stored filename of optional ethics clearance document' AFTER supporting_docs_original");

    _grantAddColumnIfMissing($crad, 'grant_applications', 'ethics_doc_original',
        "VARCHAR(300) DEFAULT NULL COMMENT 'Original filename of optional ethics clearance document' AFTER ethics_doc");

    _grantEnsureApplicationStatusEnum($crad);

    _grantAddColumnIfMissing($crad, 'grant_applications', 'proposal_reference',
        "VARCHAR(30) DEFAULT NULL COMMENT 'Stable proposal ID e.g. GR-2026-001' AFTER id");
    _grantAddColumnIfMissing($crad, 'grant_applications', 'current_version',
        "INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Active proposal document version' AFTER proposal_reference");

    grantEnsureProposalVersionTables($crad);
    _grantBackfillProposalReferences($crad);
}

function grantEnsureProposalVersionTables(PDO $crad): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_proposal_versions (
            id                      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            grant_application_id    INT UNSIGNED  NOT NULL,
            version_number          INT UNSIGNED  NOT NULL,
            version_label           VARCHAR(60)   NOT NULL DEFAULT '',
            proposal_pdf            VARCHAR(255)  DEFAULT NULL,
            proposal_pdf_original   VARCHAR(300)  DEFAULT NULL,
            supporting_docs           VARCHAR(255)  DEFAULT NULL,
            supporting_docs_original  VARCHAR(300)  DEFAULT NULL,
            ethics_doc                VARCHAR(255)  DEFAULT NULL,
            ethics_doc_original       VARCHAR(300)  DEFAULT NULL,
            abstract                  TEXT          DEFAULT NULL,
            objectives                TEXT          DEFAULT NULL,
            researcher_notes          TEXT          DEFAULT NULL,
            submitted_by_user_id    INT UNSIGNED  DEFAULT NULL,
            submitted_at              DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_gpv_app_ver (grant_application_id, version_number),
            KEY idx_gpv_application (grant_application_id),
            KEY idx_gpv_submitted (submitted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function grantVersionLabel(int $version): string
{
    return match ($version) {
        1       => 'Original',
        2       => 'Revised',
        default => 'Revised Again',
    };
}

function grantGenerateProposalReference(PDO $crad): string
{
    $year = date('Y');
    $prefix = 'GR-' . $year . '-';

    $stmt = $crad->prepare("
        SELECT proposal_reference
          FROM grant_applications
         WHERE proposal_reference LIKE ?
         ORDER BY id DESC
         LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $last = (string) ($stmt->fetchColumn() ?: '');

    $seq = 1;
    if ($last !== '' && preg_match('/GR-\d{4}-(\d+)$/', $last, $m)) {
        $seq = (int) $m[1] + 1;
    }

    return sprintf('GR-%s-%03d', $year, $seq);
}

function _grantBackfillProposalReferences(PDO $crad): void
{
    try {
        $rows = $crad->query("
            SELECT id
              FROM grant_applications
             WHERE proposal_reference IS NULL OR proposal_reference = ''
             ORDER BY id ASC
        ")->fetchAll(PDO::FETCH_COLUMN) ?: [];

        foreach ($rows as $appId) {
            $ref = grantGenerateProposalReference($crad);
            $crad->prepare("
                UPDATE grant_applications
                   SET proposal_reference = ?, current_version = COALESCE(NULLIF(current_version, 0), 1)
                 WHERE id = ?
            ")->execute([$ref, (int) $appId]);
        }

        $apps = $crad->query("
            SELECT ga.*
              FROM grant_applications ga
             LEFT JOIN grant_proposal_versions gpv
                    ON gpv.grant_application_id = ga.id AND gpv.version_number = 1
             WHERE gpv.id IS NULL
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($apps as $app) {
            grantInsertProposalVersion($crad, $app, 1);
        }

        _grantBackfillApplicantUserIds($crad);
    } catch (Throwable $e) {
        error_log('_grantBackfillProposalReferences: ' . $e->getMessage());
    }
}

function _grantBackfillApplicantUserIds(PDO $crad): void
{
    if (!function_exists('db')) {
        require_once dirname(__DIR__, 2) . '/config/database.php';
    }

    $main = db();
    if (!$main) {
        return;
    }

    try {
        $rows = $crad->query("
            SELECT id, applicant_name, applicant_user_id
              FROM grant_applications
             WHERE applicant_user_id IS NULL OR applicant_user_id = 0
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $update = $crad->prepare('UPDATE grant_applications SET applicant_user_id = ? WHERE id = ?');
        $lookup = $main->prepare('SELECT id FROM users WHERE full_name = ? OR username = ? LIMIT 1');

        foreach ($rows as $row) {
            $name = trim((string) ($row['applicant_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $lookup->execute([$name, $name]);
            $userId = (int) ($lookup->fetchColumn() ?: 0);
            if ($userId > 0) {
                $update->execute([$userId, (int) $row['id']]);
            }
        }
    } catch (Throwable $e) {
        error_log('_grantBackfillApplicantUserIds: ' . $e->getMessage());
    }
}

function grantRevisionsRequestedUrl(): string
{
    return BASE_URL . '/modules/crad/pages/revisions-requested.php';
}

function grantApprovedFundedUrl(): string
{
    return BASE_URL . '/modules/crad/pages/approved-funded.php';
}

/** Final grant status after Finance (Level 6) approves. */
function grantStatusApprovedFunded(): string
{
    return 'Approved & Funded';
}

/** Researcher submitted final output / publication for CRAD verification. */
function grantStatusFinalOutputSubmitted(): string
{
    return 'Final Output Submitted';
}

/** CRAD verified final output — recorded to Publications & IP Repository. */
function grantStatusOutputVerified(): string
{
    return 'OUTPUT_VERIFIED';
}

/** @deprecated Alias for grantStatusOutputVerified() */
function grantStatusPublicationVerified(): string
{
    return grantStatusOutputVerified();
}

/** Permanent records archived to Document Repository. */
function grantStatusArchived(): string
{
    return 'Archived';
}

/**
 * Application statuses where funded-research files remain accessible.
 *
 * @return list<string>
 */
function grantPostFundingApplicationStatuses(): array
{
    return [
        grantStatusApprovedFunded(),
        grantStatusFinalOutputSubmitted(),
        grantStatusOutputVerified(),
        grantStatusArchived(),
    ];
}

function grantApplicationStatusLabel(string $status): string
{
    return match ($status) {
        grantStatusApprovedFunded()      => 'APPROVED & FUNDED',
        grantStatusFinalOutputSubmitted() => 'FINAL OUTPUT SUBMITTED',
        grantStatusOutputVerified()       => 'OUTPUT VERIFIED',
        grantStatusArchived()           => 'ARCHIVED',
        'Submitted'                       => 'Pending Evaluation',
        'Revision Required'               => 'REVISION REQUIRED',
        'Rejected'                        => 'REJECTED',
        default                           => $status,
    };
}

/**
 * Resolve a stored upload path (absolute or basename) under storage/uploads.
 *
 * @param list<string> $preferredSubdirs
 */
function grantResolveStoredUploadPath(string $storedPath, array $preferredSubdirs = []): ?string
{
    $storedPath = trim($storedPath);
    if ($storedPath === '') {
        return null;
    }

    if (is_file($storedPath)) {
        $real = realpath($storedPath);
        $uploadsDir = realpath(smsUploadRoot());
        if ($real !== false && $uploadsDir !== false && strncmp($real, $uploadsDir, strlen($uploadsDir)) === 0) {
            return $real;
        }
    }

    $basename = basename(str_replace('\\', '/', $storedPath));
    $candidates = $preferredSubdirs;
    $candidates[] = 'general';
    foreach ($candidates as $subdir) {
        $candidate = smsUploadRoot() . '/' . trim($subdir, '/') . '/' . $basename;
        if (is_file($candidate)) {
            return realpath($candidate) ?: $candidate;
        }
    }

    $fallback = smsUploadRoot() . '/' . $basename;
    if (is_file($fallback)) {
        return realpath($fallback) ?: $fallback;
    }

    return null;
}

function grantReviseProposalUrl(int $applicationId): string
{
    return BASE_URL . '/modules/crad/pages/revise-proposal.php?id=' . $applicationId;
}

/**
 * @param array<string, mixed> $application
 */
function grantInsertProposalVersion(PDO $crad, array $application, int $versionNumber, ?string $researcherNotes = null): void
{
    grantEnsureProposalVersionTables($crad);

    $stmt = $crad->prepare("
        INSERT INTO grant_proposal_versions
            (grant_application_id, version_number, version_label,
             proposal_pdf, proposal_pdf_original,
             supporting_docs, supporting_docs_original,
             ethics_doc, ethics_doc_original,
             abstract, objectives, researcher_notes,
             submitted_by_user_id, submitted_at)
        VALUES
            (?, ?, ?,
             ?, ?,
             ?, ?,
             ?, ?,
             ?, ?, ?,
             ?, NOW())
        ON DUPLICATE KEY UPDATE
            version_label = VALUES(version_label),
            proposal_pdf = VALUES(proposal_pdf),
            proposal_pdf_original = VALUES(proposal_pdf_original),
            supporting_docs = VALUES(supporting_docs),
            supporting_docs_original = VALUES(supporting_docs_original),
            ethics_doc = VALUES(ethics_doc),
            ethics_doc_original = VALUES(ethics_doc_original),
            abstract = VALUES(abstract),
            objectives = VALUES(objectives),
            researcher_notes = COALESCE(VALUES(researcher_notes), researcher_notes),
            submitted_by_user_id = VALUES(submitted_by_user_id),
            submitted_at = VALUES(submitted_at)
    ");
    $stmt->execute([
        (int) $application['id'],
        $versionNumber,
        grantVersionLabel($versionNumber),
        $application['proposal_pdf'] ?? null,
        $application['proposal_pdf_original'] ?? null,
        $application['supporting_docs'] ?? null,
        $application['supporting_docs_original'] ?? null,
        $application['ethics_doc'] ?? null,
        $application['ethics_doc_original'] ?? null,
        $application['abstract'] ?? null,
        $application['objectives'] ?? null,
        $researcherNotes,
        (int) ($application['applicant_user_id'] ?? $_SESSION['user_id'] ?? 0) ?: null,
    ]);
}

/**
 * @return array<int, array<string, mixed>>
 */
function grantGetProposalVersions(PDO $crad, int $applicationId): array
{
    grantEnsureProposalVersionTables($crad);

    $stmt = $crad->prepare("
        SELECT *
          FROM grant_proposal_versions
         WHERE grant_application_id = ?
         ORDER BY version_number ASC
    ");
    $stmt->execute([$applicationId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @return array<int, array<string, mixed>>
 */
function grantGetMyRevisionRequiredApplications(PDO $crad): array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return [];
    }

    grantEnsureTables($crad);

    try {
        $stmt = $crad->prepare("
            SELECT ga.id
              FROM grant_applications ga
             WHERE ga.applicant_user_id = ?
               AND ga.status = 'Revision Required'
        ");
        $stmt->execute([$userId]);
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if ($ids === []) {
            return [];
        }

        $all = grantGetApplications($crad);
        $idSet = array_flip($ids);
        return array_values(array_filter(
            $all,
            static fn(array $row): bool => isset($idSet[(int) ($row['id'] ?? 0)])
        ));
    } catch (Throwable $e) {
        error_log('grantGetMyRevisionRequiredApplications: ' . $e->getMessage());
        return [];
    }
}

/**
 * Attach return metadata for real-time Revisions Requested polling.
 *
 * @param  list<array<string, mixed>> $revisions
 * @return list<array<string, mixed>>
 */
function grantEnrichRevisionApplications(PDO $crad, array $revisions): array
{
    if ($revisions === []) {
        return [];
    }

    require_once __DIR__ . '/grant-evaluation-helpers.php';
    require_once __DIR__ . '/grant-approval-helpers.php';

    grantEnsureEvaluationTables($crad);
    grantEnsureApprovalTables($crad);

    $ids = array_values(array_filter(array_map(
        static fn(array $row): int => (int) ($row['id'] ?? 0),
        $revisions
    ), static fn(int $id): bool => $id > 0));

    $approvalReturns = grantGetLatestApprovalReturnsForApplications($crad, $ids);
    $evaluations = grantGetLatestEvaluationsForApplications($crad, $ids);

    foreach ($revisions as &$row) {
        $appId = (int) ($row['id'] ?? 0);
        $approvalReturn = $approvalReturns[$appId] ?? null;
        $evaluation = $evaluations[$appId] ?? null;

        $row['return_source'] = '';
        $row['returned_by'] = '';
        $row['approval_level'] = 0;
        $row['return_reason'] = '';
        $row['returned_at'] = (string) ($row['updated_at'] ?? '');

        if ($approvalReturn) {
            $row['return_source'] = 'approval';
            $row['returned_by'] = grantApprovalReturnedByLabel($approvalReturn);
            $row['approval_level'] = (int) ($approvalReturn['step_order'] ?? 0);
            $row['return_reason'] = trim((string) ($approvalReturn['remarks'] ?? ''));
            $row['returned_at'] = (string) ($approvalReturn['acted_at'] ?? $row['returned_at']);
        } elseif ($evaluation) {
            $row['return_source'] = 'committee';
            $row['returned_by'] = 'Review Committee';
            $row['return_reason'] = trim((string) ($evaluation['revision_reason'] ?? $evaluation['required_corrections'] ?? $evaluation['comments'] ?? ''));
            $row['returned_at'] = (string) ($evaluation['submitted_at'] ?? $row['returned_at']);
        }
    }
    unset($row);

    return $revisions;
}

function grantGetApplicationForResearcher(PDO $crad, int $applicationId): ?array
{
    $stmt = $crad->prepare("
        SELECT ga.*, go.funding_title, go.max_funding_cap
          FROM grant_applications ga
         INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
         WHERE ga.id = ?
         LIMIT 1
    ");
    $stmt->execute([$applicationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if (!grantUserCanManage()
        && (int) ($row['applicant_user_id'] ?? 0) !== $userId) {
        return null;
    }

    return $row;
}

/**
 * @param array<string, mixed> $data
 * @param array<string, mixed> $files
 * @return array{ok: bool, id?: int, reference?: string, version?: int, new_status?: string, error?: string}
 */
function grantResubmitProposal(PDO $crad, int $applicationId, array $data, array $files): array
{
    grantEnsureTables($crad);
    grantEnsureProposalVersionTables($crad);

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return ['ok' => false, 'error' => 'Invalid session.'];
    }

    $application = grantGetApplicationForResearcher($crad, $applicationId);
    if (!$application) {
        return ['ok' => false, 'error' => 'Proposal not found or access denied.'];
    }

    if ((string) ($application['status'] ?? '') !== 'Revision Required') {
        return ['ok' => false, 'error' => 'Only proposals marked for revision can be resubmitted.'];
    }

    if ((int) ($application['applicant_user_id'] ?? 0) !== $userId) {
        return ['ok' => false, 'error' => 'You can only revise your own proposals.'];
    }

    $proposalFile = $files['proposal_pdf'] ?? [];
    if (empty($proposalFile['ok'])) {
        return ['ok' => false, 'error' => $proposalFile['error'] ?? 'Revised proposal PDF is required.'];
    }

    $supportingFile = $files['supporting_docs'] ?? [];
    $ethicsFile     = $files['ethics_doc'] ?? [];
    $researcherNotes = trim((string) ($data['researcher_notes'] ?? '')) ?: null;
    $currentVersion  = max(1, (int) ($application['current_version'] ?? 1));
    $nextVersion     = $currentVersion + 1;

    try {
        $crad->beginTransaction();

        grantInsertProposalVersion($crad, $application, $currentVersion, null);

        $updated = array_merge($application, [
            'proposal_pdf'            => $proposalFile['stored_name'] ?? $application['proposal_pdf'],
            'proposal_pdf_original'   => $proposalFile['original_name'] ?? $application['proposal_pdf_original'],
            'supporting_docs'         => !empty($supportingFile['ok'])
                ? ($supportingFile['stored_name'] ?? null)
                : $application['supporting_docs'],
            'supporting_docs_original' => !empty($supportingFile['ok'])
                ? ($supportingFile['original_name'] ?? null)
                : $application['supporting_docs_original'],
            'ethics_doc'              => !empty($ethicsFile['ok'])
                ? ($ethicsFile['stored_name'] ?? null)
                : $application['ethics_doc'],
            'ethics_doc_original'     => !empty($ethicsFile['ok'])
                ? ($ethicsFile['original_name'] ?? null)
                : $application['ethics_doc_original'],
            'current_version'         => $nextVersion,
        ]);

        grantInsertProposalVersion($crad, $updated, $nextVersion, $researcherNotes);

        $crad->prepare("
            UPDATE grant_applications
               SET proposal_pdf = ?,
                   proposal_pdf_original = ?,
                   supporting_docs = ?,
                   supporting_docs_original = ?,
                   ethics_doc = ?,
                   ethics_doc_original = ?,
                   current_version = ?,
                   status = 'Under Review',
                   submitted_at = NOW(),
                   updated_at = NOW()
             WHERE id = ?
        ")->execute([
            $updated['proposal_pdf'],
            $updated['proposal_pdf_original'],
            $updated['supporting_docs'],
            $updated['supporting_docs_original'],
            $updated['ethics_doc'],
            $updated['ethics_doc_original'],
            $nextVersion,
            $applicationId,
        ]);

        require_once __DIR__ . '/grant-approval-helpers.php';
        grantClearApprovalWorkflowForResubmit($crad, $applicationId);

        $crad->commit();

        return [
            'ok'          => true,
            'id'          => $applicationId,
            'reference'   => (string) ($application['proposal_reference'] ?? ''),
            'version'     => $nextVersion,
            'new_status'  => 'Under Review',
        ];
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantResubmitProposal: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Failed to resubmit proposal. Please try again.'];
    }
}

/**
 * Extend grant_applications.status with reviewer decision outcomes.
 */
function _grantEnsureApplicationStatusEnum(PDO $crad): void
{
    try {
        $stmt = $crad->prepare(
            "SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'grant_applications'
                AND COLUMN_NAME = 'status'"
        );
        $stmt->execute();
        $columnType = (string) $stmt->fetchColumn();
        if ($columnType === '') {
            return;
        }
        if (strpos($columnType, 'OUTPUT_VERIFIED') !== false
            && strpos($columnType, 'Archived') !== false) {
            return;
        }

        if (strpos($columnType, 'Publication Verified') !== false) {
            $crad->exec("UPDATE grant_applications SET status = 'OUTPUT_VERIFIED' WHERE status = 'Publication Verified'");
        }

        $crad->exec("
            ALTER TABLE grant_applications
            MODIFY COLUMN status ENUM(
                'Submitted',
                'Under Review',
                'Approved',
                'Approved & Funded',
                'Final Output Submitted',
                'OUTPUT_VERIFIED',
                'Archived',
                'Denied',
                'Withdrawn',
                'Rejected',
                'Revision Required',
                'Resubmitted'
            ) NOT NULL DEFAULT 'Submitted'
        ");
    } catch (Throwable $e) {
        error_log('_grantEnsureApplicationStatusEnum: ' . $e->getMessage());
    }
}

/**
 * Mark any grant opportunities whose deadline has passed as 'Expired'.
 * Safe no-op if all rows are already correct.
 *
 * @param  PDO $crad
 * @return void
 */
function grantExpireDeadlines(PDO $crad): void
{
    try {
        $crad->exec("
            UPDATE grant_opportunities
               SET status    = 'Expired',
                   updated_at = NOW()
             WHERE application_deadline < CURDATE()
               AND status = 'Open for Application'
        ");
    } catch (Throwable $e) {
        error_log('grantExpireDeadlines: ' . $e->getMessage());
    }
}

/**
 * Fetch all grant opportunities, newest first.
 * Also runs the deadline-expiry sweep before returning data.
 *
 * @param  PDO  $crad
 * @return array<int, array<string, mixed>>
 */
function grantGetOpportunities(PDO $crad): array
{
    grantExpireDeadlines($crad);

    $stmt = $crad->query("
        SELECT
            go.id,
            go.funding_title,
            go.max_funding_cap,
            go.application_deadline,
            go.eligibility,
            go.college_program,
            go.status,
            go.created_by_name,
            go.created_at,
            go.updated_at,
            COUNT(ga.id) AS application_count
        FROM grant_opportunities go
        LEFT JOIN grant_applications ga ON ga.grant_opportunity_id = go.id
        GROUP BY go.id
        ORDER BY go.created_at DESC, go.id DESC
    ");

    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Fetch all grant applications (proposals) with their linked opportunity title.
 * Includes all proposal-level columns added for the BRGFAMS Form 1 flow.
 *
 * The SELECT is built dynamically: each new proposal column is only included
 * when it actually exists in the table, so the query never fails on a database
 * that was created before the new columns were added.  grantEnsureTables() must
 * be called before this function to attempt the ALTERs; but if an ALTER did not
 * apply (e.g. insufficient privileges) the SELECT still works.
 *
 * @param  PDO        $crad
 * @param  int|null   $opportunityId  Optional filter by opportunity.
 * @return array<int, array<string, mixed>>
 */
function grantGetApplications(PDO $crad, ?int $opportunityId = null): array
{
    // ── Detect which optional proposal columns are actually present ──────────
    $proposalColumns = [
        'proposal_reference',
        'current_version',
        'college_dept',
        'requested_budget',
        'abstract',
        'objectives',
        'proposal_pdf',
        'proposal_pdf_original',
        'supporting_docs',
        'supporting_docs_original',
        'ethics_doc',
        'ethics_doc_original',
    ];

    $existingColumns = [];
    try {
        $colStmt = $crad->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = 'grant_applications'"
        );
        $colStmt->execute();
        foreach ($colStmt->fetchAll(PDO::FETCH_COLUMN) as $colName) {
            $existingColumns[strtolower((string) $colName)] = true;
        }
    } catch (Throwable $e) {
        error_log('grantGetApplications (INFORMATION_SCHEMA): ' . $e->getMessage());
    }

    // Build the optional SELECT fragments — NULL alias when column absent so
    // callers always get the key in the result array (just null-valued).
    $optionalSelects = '';
    foreach ($proposalColumns as $col) {
        if (!empty($existingColumns[$col])) {
            $optionalSelects .= ",\n            ga.`{$col}`";
        } else {
            $optionalSelects .= ",\n            NULL AS `{$col}`";
        }
    }

    $sql = "
        SELECT
            ga.id,
            ga.grant_opportunity_id,
            go.funding_title,
            go.max_funding_cap,
            ga.research_group_id,
            ga.group_number,
            ga.research_title,
            ga.applicant_name" . $optionalSelects . ",
            ga.applicant_user_id,
            ga.status,
            ga.application_notes,
            ga.submitted_at,
            ga.updated_at
        FROM grant_applications ga
        INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
    ";

    if ($opportunityId !== null && $opportunityId > 0) {
        $stmt = $crad->prepare($sql . ' WHERE ga.grant_opportunity_id = ? ORDER BY ga.submitted_at DESC, ga.id DESC');
        $stmt->execute([$opportunityId]);
    } else {
        $stmt = $crad->query($sql . ' ORDER BY ga.submitted_at DESC, ga.id DESC');
    }

    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * Fetch grant applications for the current researcher (student / adviser).
 *
 * @param  PDO $crad
 * @return array<int, array<string, mixed>>
 */
function grantGetMyApplications(PDO $crad): array
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return [];
    }

    $all = grantGetApplications($crad);
    return array_values(array_filter(
        $all,
        static fn(array $row): bool => (int) ($row['applicant_user_id'] ?? 0) === $userId
    ));
}

/**
 * Return dashboard summary counts for the grant management module.
 *
 * @param  PDO $crad
 * @return array{
 *   total_opportunities: int,
 *   open: int,
 *   closed: int,
 *   expired: int,
 *   total_applications: int,
 *   under_review: int,
 *   approved: int,
 *   denied: int
 * }
 */
function grantDashboardStats(PDO $crad): array
{
    grantExpireDeadlines($crad);

    $defaults = [
        'total_opportunities' => 0,
        'open'                => 0,
        'closed'              => 0,
        'expired'             => 0,
        'total_applications'  => 0,
        'under_review'        => 0,
        'approved'            => 0,
        'denied'              => 0,
    ];

    try {
        $oppStmt = $crad->query("
            SELECT
                COUNT(*)                                                  AS total_opportunities,
                SUM(status = 'Open for Application')                      AS open,
                SUM(status = 'Closed')                                    AS closed,
                SUM(status = 'Expired')                                   AS expired
            FROM grant_opportunities
        ");
        $opp = $oppStmt ? ($oppStmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        $appStmt = $crad->query("
            SELECT
                COUNT(*)                                AS total_applications,
                SUM(status = 'Under Review')            AS under_review,
                SUM(status = 'Approved')                AS approved,
                SUM(status IN ('Denied', 'Rejected'))          AS denied
            FROM grant_applications
        ");
        $app = $appStmt ? ($appStmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];

        return array_merge($defaults, [
            'total_opportunities' => (int) ($opp['total_opportunities'] ?? 0),
            'open'                => (int) ($opp['open']  ?? 0),
            'closed'              => (int) ($opp['closed'] ?? 0),
            'expired'             => (int) ($opp['expired'] ?? 0),
            'total_applications'  => (int) ($app['total_applications'] ?? 0),
            'under_review'        => (int) ($app['under_review'] ?? 0),
            'approved'            => (int) ($app['approved'] ?? 0),
            'denied'              => (int) ($app['denied'] ?? 0),
        ]);

    } catch (Throwable $e) {
        error_log('grantDashboardStats: ' . $e->getMessage());
        return $defaults;
    }
}

/**
 * Full grant dashboard metrics for CRAD Dashboard & Analytics (real-time).
 *
 * @return array<string, int|float|string>
 */
function grantGetDashboardMetrics(PDO $crad): array
{
    grantEnsureTables($crad);
    grantExpireDeadlines($crad);

    if (is_file(__DIR__ . '/grant-final-output-helpers.php')) {
        require_once __DIR__ . '/grant-final-output-helpers.php';
        if (function_exists('grantEnsureFinalOutputTables')) {
            grantEnsureFinalOutputTables($crad);
        }
    }
    if (is_file(__DIR__ . '/grant-document-repository-helpers.php')) {
        require_once __DIR__ . '/grant-document-repository-helpers.php';
        if (function_exists('grantEnsureDocumentRepositoryTables')) {
            grantEnsureDocumentRepositoryTables($crad);
        }
    }
    if (is_file(__DIR__ . '/grant-funding-helpers.php')) {
        require_once __DIR__ . '/grant-funding-helpers.php';
        if (function_exists('grantEnsureFundingTables')) {
            grantEnsureFundingTables($crad);
        }
    }

    $defaults = grantDashboardMetricsDefaults();

    try {
        $defaults['total_grant_calls'] = (int) $crad->query(
            'SELECT COUNT(*) FROM grant_opportunities'
        )->fetchColumn();

        $funded = grantStatusApprovedFunded();
        $finalSubmitted = grantStatusFinalOutputSubmitted();
        $outputVerified = grantStatusOutputVerified();
        $archived = grantStatusArchived();

        $appStmt = $crad->prepare("
            SELECT
                COUNT(*) AS submitted_proposals,
                SUM(status = 'Under Review') AS under_review,
                SUM(status = 'Revision Required') AS revision_required,
                SUM(status IN ('Rejected', 'Denied')) AS rejected_proposals,
                SUM(status = ?) AS approved_funded_projects,
                SUM(status IN (?, ?)) AS ongoing_research,
                SUM(status IN (?, ?)) AS completed_research,
                COALESCE(SUM(
                    CASE WHEN status IN (?, ?, ?, ?)
                    THEN COALESCE(approved_budget, requested_budget, 0) ELSE 0 END
                ), 0) AS total_funding
            FROM grant_applications
        ");
        $appStmt->execute([
            $funded,
            $funded, $finalSubmitted,
            $outputVerified, $archived,
            $funded, $finalSubmitted, $outputVerified, $archived,
        ]);
        $app = $appStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $defaults['submitted_proposals']      = (int) ($app['submitted_proposals'] ?? 0);
        $defaults['under_review']             = (int) ($app['under_review'] ?? 0);
        $defaults['revision_required']        = (int) ($app['revision_required'] ?? 0);
        $defaults['rejected_proposals']       = (int) ($app['rejected_proposals'] ?? 0);
        $defaults['approved_funded_projects'] = (int) ($app['approved_funded_projects'] ?? 0);
        $defaults['ongoing_research']         = (int) ($app['ongoing_research'] ?? 0);
        $defaults['completed_research']       = (int) ($app['completed_research'] ?? 0);
        $defaults['total_funding']            = (float) ($app['total_funding'] ?? 0);

        $releasedStmt = $crad->query("
            SELECT COALESCE(SUM(amount_released), 0)
              FROM grant_funding_disbursements
             WHERE status = 'Released'
        ");
        $releasedTotal = (float) ($releasedStmt ? $releasedStmt->fetchColumn() : 0);
        if ($releasedTotal > 0) {
            $defaults['total_funding'] = $releasedTotal;
        }

        $pubTable = $crad->query("SHOW TABLES LIKE 'grant_publications_ip_repository'")->fetchColumn();
        if ($pubTable) {
            $defaults['publications'] = (int) $crad->query(
                'SELECT COUNT(*) FROM grant_publications_ip_repository'
            )->fetchColumn();

            $defaults['ip_records'] = (int) $crad->query("
                SELECT COUNT(*) FROM grant_publications_ip_repository
                 WHERE TRIM(COALESCE(copyright_info, '')) <> ''
                    OR TRIM(COALESCE(patent_info, '')) <> ''
                    OR TRIM(COALESCE(other_ip_info, '')) <> ''
                    OR TRIM(COALESCE(ip_information, '')) <> ''
            ")->fetchColumn();
        }

        $defaults['updated_at'] = date('Y-m-d H:i:s');
    } catch (Throwable $e) {
        error_log('grantGetDashboardMetrics: ' . $e->getMessage());
    }

    return $defaults;
}

/**
 * @return array<string, int|float|string>
 */
function grantDashboardMetricsDefaults(): array
{
    return [
        'total_grant_calls'          => 0,
        'submitted_proposals'        => 0,
        'under_review'               => 0,
        'revision_required'          => 0,
        'rejected_proposals'         => 0,
        'approved_funded_projects'   => 0,
        'total_funding'              => 0.0,
        'ongoing_research'           => 0,
        'completed_research'         => 0,
        'publications'               => 0,
        'ip_records'                 => 0,
        'updated_at'                 => '',
    ];
}

/**
 * @return list<array{key: string, label: string, icon: string, tone: string, format: string}>
 */
function grantDashboardMetricDefinitions(): array
{
    return [
        ['key' => 'total_grant_calls',        'label' => 'Total Grant Calls',          'icon' => 'fa-bullhorn',         'tone' => 'blue',   'format' => 'int'],
        ['key' => 'submitted_proposals',      'label' => 'Submitted Proposals',        'icon' => 'fa-file-alt',         'tone' => 'purple', 'format' => 'int'],
        ['key' => 'under_review',             'label' => 'Under Review',               'icon' => 'fa-search',           'tone' => 'amber',  'format' => 'int'],
        ['key' => 'revision_required',        'label' => 'Revision Required',          'icon' => 'fa-edit',             'tone' => 'orange', 'format' => 'int'],
        ['key' => 'rejected_proposals',       'label' => 'Rejected Proposals',         'icon' => 'fa-ban',              'tone' => 'red',    'format' => 'int'],
        ['key' => 'approved_funded_projects', 'label' => 'Approved & Funded Projects', 'icon' => 'fa-check-circle',     'tone' => 'green',  'format' => 'int'],
        ['key' => 'total_funding',            'label' => 'Total Funding',              'icon' => 'fa-peso-sign',        'tone' => 'green',  'format' => 'currency'],
        ['key' => 'ongoing_research',         'label' => 'Ongoing Research',           'icon' => 'fa-flask',            'tone' => 'blue',   'format' => 'int'],
        ['key' => 'completed_research',       'label' => 'Completed Research',         'icon' => 'fa-flag-checkered',   'tone' => 'purple', 'format' => 'int'],
        ['key' => 'publications',             'label' => 'Publications',               'icon' => 'fa-book-open',        'tone' => 'teal',   'format' => 'int'],
        ['key' => 'ip_records',               'label' => 'IP Records',                 'icon' => 'fa-shield-alt',       'tone' => 'indigo', 'format' => 'int'],
    ];
}

/**
 * @param array<string, int|float|string> $metrics
 */
function grantDashboardMetricsFingerprint(array $metrics): string
{
    $parts = [];
    foreach (grantDashboardMetricDefinitions() as $def) {
        $key = (string) ($def['key'] ?? '');
        $parts[] = $key . ':' . (string) ($metrics[$key] ?? 0);
    }

    return md5(implode('|', $parts));
}

/**
 * @param array<string, int|float|string> $metrics
 */
function grantFormatDashboardMetricValue(string $key, array $metrics): string
{
    $value = $metrics[$key] ?? 0;
    if ($key === 'total_funding') {
        if (!function_exists('grantFormatPeso') && is_file(__DIR__ . '/grant-funding-helpers.php')) {
            require_once __DIR__ . '/grant-funding-helpers.php';
        }
        if (function_exists('grantFormatPeso')) {
            return grantFormatPeso((float) $value);
        }

        return '₱' . number_format((float) $value, 0, '.', ',');
    }

    return number_format((int) $value);
}

/**
 * Publish a new grant opportunity.
 * Returns ['ok' => true, 'id' => int] on success or ['ok' => false, 'error' => string] on failure.
 *
 * @param  PDO    $crad
 * @param  array  $data  Validated input (funding_title, max_funding_cap, application_deadline,
 *                        eligibility, college_program, created_by_user_id, created_by_name).
 * @return array{ok: bool, id?: int, error?: string}
 */
function grantPublishOpportunity(PDO $crad, array $data): array
{
    $fundingTitle   = trim((string) ($data['funding_title'] ?? ''));
    $maxCap         = (float) ($data['max_funding_cap'] ?? 0);
    $deadline       = trim((string) ($data['application_deadline'] ?? ''));
    $eligibility    = trim((string) ($data['eligibility'] ?? 'Open'));
    $collegeProgram = trim((string) ($data['college_program'] ?? '')) ?: null;
    $createdById    = (int) ($data['created_by_user_id'] ?? 0) ?: null;
    $createdByName  = trim((string) ($data['created_by_name'] ?? ''));

    if ($fundingTitle === '') {
        return ['ok' => false, 'error' => 'Funding title is required.'];
    }
    if ($maxCap <= 0) {
        return ['ok' => false, 'error' => 'Maximum funding cap must be greater than zero.'];
    }
    if ($deadline === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadline)) {
        return ['ok' => false, 'error' => 'A valid application deadline is required (YYYY-MM-DD).'];
    }
    if (strtotime($deadline) <= time()) {
        return ['ok' => false, 'error' => 'Application deadline must be a future date.'];
    }

    $allowedEligibility = [
        'Open',
        'Faculty Researchers',
        'Student Researchers',
        'Faculty & Student',
        'Specific College/Program',
    ];
    if (!in_array($eligibility, $allowedEligibility, true)) {
        $eligibility = 'Open';
    }
    if ($eligibility !== 'Specific College/Program') {
        $collegeProgram = null;
    }

    try {
        $stmt = $crad->prepare("
            INSERT INTO grant_opportunities
                (funding_title, max_funding_cap, application_deadline,
                 eligibility, college_program,
                 status, created_by_user_id, created_by_name,
                 created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?,
                 'Open for Application', ?, ?,
                 NOW(), NOW())
        ");
        $stmt->execute([
            $fundingTitle,
            $maxCap,
            $deadline,
            $eligibility,
            $collegeProgram,
            $createdById,
            $createdByName,
        ]);
        return ['ok' => true, 'id' => (int) $crad->lastInsertId()];

    } catch (Throwable $e) {
        error_log('grantPublishOpportunity: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Failed to publish grant call. Please try again.'];
    }
}

/**
 * Submit a full Research Grant Proposal (BRGFAMS Form 1).
 *
 * Server-side validations enforced here (not only in the browser):
 *  1.  Session token must be present, valid, and unconsumed.
 *  2.  Grant must exist in grant_opportunities.
 *  3.  Grant status must be 'Open for Application'.
 *  4.  application_deadline must be >= today (re-checked at submit time).
 *  5.  lead_proponent (applicant_name) is non-empty.
 *  6.  research_title is non-empty.
 *  7.  college_dept is non-empty.
 *  8.  requested_budget > 0.
 *  9.  requested_budget <= grant max_funding_cap.
 *  10. abstract is non-empty.
 *  11. objectives is non-empty.
 *  12. proposal_pdf upload result is ok (file present and validated by smsSecureUpload).
 *
 * Returns:
 *   ['ok' => true,  'id' => int]
 *   ['ok' => false, 'error' => string]
 *
 * @param  PDO    $crad
 * @param  array  $data  Validated scalar fields — see keys below.
 * @param  array  $files Resolved smsSecureUpload() results keyed by field name:
 *                       proposal_pdf (required), supporting_docs (optional), ethics_doc (optional).
 * @return array{ok: bool, id?: int, error?: string}
 */
function grantSubmitProposal(PDO $crad, array $data, array $files): array
{
    grantEnsureTables($crad);

    // ── Unpack scalar fields ────────────────────────────────────────────────
    $grantId        = (int)    ($data['grant_opportunity_id'] ?? 0);
    $leadProponent  = trim((string) ($data['lead_proponent']   ?? ''));
    $researchTitle  = trim((string) ($data['research_title']   ?? ''));
    $collegeDept    = trim((string) ($data['college_dept']     ?? ''));
    $requestedBudget = (float) ($data['requested_budget']      ?? 0);
    $abstract       = trim((string) ($data['abstract']         ?? ''));
    $objectives     = trim((string) ($data['objectives']       ?? ''));
    $applicantUid   = (int)    ($data['applicant_user_id']     ?? 0) ?: null;
    $groupNumber    = trim((string) ($data['group_number']     ?? '')) ?: null;
    $notes          = trim((string) ($data['application_notes'] ?? '')) ?: null;
    $token          = trim((string) ($data['apply_token']      ?? ''));

    // ── Token validation ────────────────────────────────────────────────────
    if ($token === '') {
        return ['ok' => false, 'error' => 'Submission token is required.'];
    }
    $validTokens = $_SESSION['crad_apply_tokens'] ?? [];
    if (!isset($validTokens[$token])) {
        return ['ok' => false, 'error' => 'Invalid or expired submission token. Please reload the form and try again.'];
    }
    // Consume immediately — prevents exact replay
    unset($_SESSION['crad_apply_tokens'][$token]);

    // ── Basic field validation ──────────────────────────────────────────────
    if ($grantId <= 0) {
        return ['ok' => false, 'error' => 'Invalid grant opportunity selected.'];
    }
    if ($leadProponent === '') {
        return ['ok' => false, 'error' => 'Lead proponent name is required.'];
    }
    if ($researchTitle === '') {
        return ['ok' => false, 'error' => 'Research project title is required.'];
    }
    if ($collegeDept === '') {
        return ['ok' => false, 'error' => 'Academic college / department is required.'];
    }
    if ($requestedBudget <= 0) {
        return ['ok' => false, 'error' => 'Requested budget must be greater than zero.'];
    }
    if ($abstract === '') {
        return ['ok' => false, 'error' => 'Executive abstract is required.'];
    }
    if ($objectives === '') {
        return ['ok' => false, 'error' => 'Research objectives are required.'];
    }

    // ── Verify grant exists, is open, deadline not passed ──────────────────
    try {
        $gStmt = $crad->prepare("
            SELECT id, status, application_deadline, max_funding_cap
              FROM grant_opportunities
             WHERE id = ?
             LIMIT 1
        ");
        $gStmt->execute([$grantId]);
        $grant = $gStmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('grantSubmitProposal (fetch grant): ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not verify grant. Please try again.'];
    }

    if (!$grant) {
        return ['ok' => false, 'error' => 'The selected grant opportunity does not exist.'];
    }
    if ($grant['status'] !== 'Open for Application') {
        return ['ok' => false, 'error' => 'This grant is no longer open for applications.'];
    }
    $deadlineTs = strtotime((string) $grant['application_deadline']);
    if ($deadlineTs !== false && $deadlineTs < mktime(0, 0, 0)) {
        // Auto-flip the row so next page load shows Expired
        try {
            $crad->prepare("UPDATE grant_opportunities SET status='Expired', updated_at=NOW() WHERE id=?")
                 ->execute([$grantId]);
        } catch (Throwable) { /* non-fatal */ }
        return ['ok' => false, 'error' => 'The application deadline for this grant has passed.'];
    }

    // ── Budget cap validation ───────────────────────────────────────────────
    $maxCap = (float) $grant['max_funding_cap'];
    if ($requestedBudget > $maxCap) {
        $capFmt = '₱' . number_format($maxCap, 0);
        return [
            'ok'    => false,
            'error' => "Requested budget cannot exceed the grant funding cap of {$capFmt}.",
        ];
    }

    // ── File upload results ─────────────────────────────────────────────────
    $proposalFile      = $files['proposal_pdf']   ?? [];
    $supportingFile    = $files['supporting_docs'] ?? [];
    $ethicsFile        = $files['ethics_doc']      ?? [];

    if (empty($proposalFile['ok'])) {
        $uploadErr = $proposalFile['error'] ?? 'Proposal PDF is required.';
        return ['ok' => false, 'error' => $uploadErr];
    }

    // ── Insert application ──────────────────────────────────────────────────
    $submissionToken = bin2hex(random_bytes(16));
    $proposalReference = grantGenerateProposalReference($crad);

    try {
        $stmt = $crad->prepare("
            INSERT INTO grant_applications
                (grant_opportunity_id,
                 proposal_reference, current_version,
                 applicant_name, applicant_user_id,
                 college_dept, requested_budget,
                 research_title, group_number,
                 abstract, objectives,
                 proposal_pdf, proposal_pdf_original,
                 supporting_docs, supporting_docs_original,
                 ethics_doc, ethics_doc_original,
                 application_notes,
                 status, submission_token,
                 submitted_at, updated_at)
            VALUES
                (?,
                 ?, 1,
                 ?, ?,
                 ?, ?,
                 ?, ?,
                 ?, ?,
                 ?, ?,
                 ?, ?,
                 ?, ?,
                 ?,
                 'Submitted', ?,
                 NOW(), NOW())
        ");
        $stmt->execute([
            $grantId,
            $proposalReference,
            $leadProponent,
            $applicantUid,
            $collegeDept,
            $requestedBudget,
            $researchTitle,
            $groupNumber,
            $abstract,
            $objectives,
            $proposalFile['stored_name']   ?? null,
            $proposalFile['original_name'] ?? null,
            $supportingFile['stored_name']   ?? null,
            $supportingFile['original_name'] ?? null,
            $ethicsFile['stored_name']   ?? null,
            $ethicsFile['original_name'] ?? null,
            $notes,
            $submissionToken,
        ]);
        $newId = (int) $crad->lastInsertId();

        grantInsertProposalVersion($crad, [
            'id' => $newId,
            'applicant_user_id' => $applicantUid,
            'proposal_pdf' => $proposalFile['stored_name'] ?? null,
            'proposal_pdf_original' => $proposalFile['original_name'] ?? null,
            'supporting_docs' => $supportingFile['stored_name'] ?? null,
            'supporting_docs_original' => $supportingFile['original_name'] ?? null,
            'ethics_doc' => $ethicsFile['stored_name'] ?? null,
            'ethics_doc_original' => $ethicsFile['original_name'] ?? null,
            'abstract' => $abstract,
            'objectives' => $objectives,
        ], 1);

        return ['ok' => true, 'id' => $newId, 'reference' => $proposalReference];

    } catch (Throwable $e) {
        error_log('grantSubmitProposal (insert): ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Failed to submit proposal. Please try again.'];
    }
}
