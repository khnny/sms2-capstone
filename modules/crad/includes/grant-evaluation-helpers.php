<?php
/**
 * CRAD Grant Proposal — Review Committee evaluation helpers.
 */
declare(strict_types=1);

require_once __DIR__ . '/grant-helpers.php';

/** @var array<string, int> */
function grantRubricCriteria(): array
{
    return [
        'rationale'        => 25,
        'methodology'      => 30,
        'budget'           => 20,
        'team_capability'  => 15,
        'compliance'       => 10,
    ];
}

function grantRubricMaxTotal(): int
{
    return 100;
}

/** @return array<string, string> */
function grantRecommendationOptions(): array
{
    return [
        'disapprove'         => 'Disapprove',
        'require_revisions'  => 'Require Revisions',
        'recommend'          => 'Recommend',
    ];
}

function grantRecommendationLabel(string $recommendation): string
{
    return grantRecommendationOptions()[$recommendation] ?? ucwords(str_replace('_', ' ', $recommendation));
}

function grantStatusForRecommendation(string $recommendation): ?string
{
    return match ($recommendation) {
        'disapprove'        => 'Rejected',
        'require_revisions' => 'Revision Required',
        'recommend'         => 'Under Review',
        default             => null,
    };
}

function grantIsAdviserEvaluationViewer(): bool
{
    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    return $roleKey === 'adviser';
}

function grantApprovalStepKeyForRole(string $roleKey): ?string
{
    require_once __DIR__ . '/grant-approval-helpers.php';

    foreach (grantApprovalStepDefinitions() as $step) {
        if (($step['role'] ?? '') === $roleKey) {
            return (string) ($step['key'] ?? '');
        }
    }

    return null;
}

/** Dept. Chair, Dean, Research Office, VPAA, Finance — view scores before sign-off. */
function grantIsGrantApproverEvaluationViewer(): bool
{
    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    return grantApprovalStepKeyForRole($roleKey) !== null;
}

/** CRAD Officer / superadmin — monitor all proposals in the approval pipeline. */
function grantIsGrantWorkflowMonitor(): bool
{
    if (grantIsAdviserEvaluationViewer() || grantIsGrantApproverEvaluationViewer()) {
        return false;
    }

    require_once __DIR__ . '/grant-approval-helpers.php';

    return grantUserCanMonitorApprovalWorkflow();
}

function grantUserCanEvaluate(): bool
{
    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    if (in_array($roleKey, ['review_committee', 'adviser'], true)) {
        return true;
    }

    if (grantIsGrantApproverEvaluationViewer()) {
        return true;
    }

    if (!function_exists('smsHasGrantedModuleAdminAccess')) {
        require_once dirname(__DIR__, 3) . '/includes/authentication.php';
    }

    return smsHasGrantedModuleAdminAccess('crad_grant')
        || smsHasGrantedModuleAdminAccess('crad');
}

function grantEvaluationBreadcrumbModuleLabel(): string
{
    if (grantIsAdviserEvaluationViewer()) {
        return 'Faculty';
    }

    if (grantIsGrantApproverEvaluationViewer()) {
        require_once __DIR__ . '/grant-approval-helpers.php';

        return grantApprovalBreadcrumbModuleLabel();
    }

    if (!function_exists('smsHasGrantedModuleAdminAccess')) {
        require_once dirname(__DIR__, 3) . '/includes/authentication.php';
    }

    if (smsHasGrantedModuleAdminAccess('crad')) {
        return 'CRAD';
    }

    return 'Research Grant';
}

function grantEvaluationBreadcrumbModuleUrl(): string
{
    if (grantIsAdviserEvaluationViewer()) {
        return BASE_URL . '/modules/faculty/pages/assigned-research.php';
    }

    if (grantIsGrantApproverEvaluationViewer()) {
        require_once __DIR__ . '/grant-approval-helpers.php';

        return grantApprovalBreadcrumbModuleUrl();
    }

    if (!function_exists('smsHasGrantedModuleAdminAccess')) {
        require_once dirname(__DIR__, 3) . '/includes/authentication.php';
    }

    if (smsHasGrantedModuleAdminAccess('crad')) {
        return BASE_URL . '/modules/crad/index.php';
    }

    return BASE_URL . '/modules/crad/pages/grant-opportunities.php';
}

function grantRequireEvaluateAccess(): void
{
    if (grantUserCanEvaluate()) {
        return;
    }

    grantRedirectUnauthorized();
}

function grantReviewerEvaluationUrl(int $applicationId = 0): string
{
    if (!function_exists('grantReviewWorkflowPageUrl')) {
        require_once dirname(__DIR__, 3) . '/includes/grant-review-workflow-urls.php';
    }

    $moduleKey = defined('SMS2_GRANT_APPROVAL_SHELL_MODULE')
        ? (string) SMS2_GRANT_APPROVAL_SHELL_MODULE
        : grantEvaluationActiveModuleKey();

    return grantReviewWorkflowPageUrl('reviewer-evaluation', $applicationId, $moduleKey);
}

function grantEvaluationActiveModuleKey(): string
{
    if (defined('SMS2_GRANT_APPROVAL_SHELL_MODULE')) {
        return (string) SMS2_GRANT_APPROVAL_SHELL_MODULE;
    }

    if (grantIsAdviserEvaluationViewer()) {
        return 'faculty';
    }

    if (grantIsGrantApproverEvaluationViewer()) {
        require_once __DIR__ . '/grant-approval-helpers.php';

        return grantApprovalActiveModuleKey();
    }

    if (!function_exists('smsHasGrantedModuleAdminAccess')) {
        require_once dirname(__DIR__, 3) . '/includes/authentication.php';
    }

    if (smsHasGrantedModuleAdminAccess('crad')) {
        return 'crad';
    }

    return 'crad_grant';
}

function grantEnsureEvaluationTables(PDO $crad): void
{
    grantEnsureTables($crad);

    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_proposal_evaluations (
            id                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            grant_application_id  INT UNSIGNED  NOT NULL,
            evaluator_user_id     INT UNSIGNED  NOT NULL,
            evaluator_name        VARCHAR(150)  NOT NULL DEFAULT '',
            score_rationale       DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
            score_methodology     DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
            score_budget          DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
            score_team_capability DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
            score_compliance      DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
            total_score           DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
            comments              TEXT          DEFAULT NULL,
            recommendations       TEXT          DEFAULT NULL,
            required_corrections  TEXT          DEFAULT NULL,
            recommendation        VARCHAR(40)   DEFAULT NULL,
            revision_reason       TEXT          DEFAULT NULL,
            submitted_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                 ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_gpe_app_evaluator (grant_application_id, evaluator_user_id),
            KEY idx_gpe_application (grant_application_id),
            KEY idx_gpe_evaluator (evaluator_user_id),
            KEY idx_gpe_submitted (submitted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    _grantAddColumnIfMissing($crad, 'grant_proposal_evaluations', 'recommendation',
        "VARCHAR(40) DEFAULT NULL COMMENT 'Reviewer decision: disapprove | require_revisions' AFTER required_corrections");
    _grantAddColumnIfMissing($crad, 'grant_proposal_evaluations', 'revision_reason',
        "TEXT DEFAULT NULL COMMENT 'Reason for required revisions' AFTER recommendation");
    _grantAddColumnIfMissing($crad, 'grant_proposal_evaluations', 'proposal_version',
        "INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Proposal version evaluated' AFTER grant_application_id");
    _grantAddColumnIfMissing($crad, 'grant_proposal_evaluations', 'evaluation_type',
        "VARCHAR(20) NOT NULL DEFAULT 'committee' COMMENT 'committee | adviser' AFTER evaluator_name");

    _grantEnsureEvaluationVersionIndex($crad);

    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_proposal_notifications (
            id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            event_key           VARCHAR(120)  NOT NULL,
            recipient_user_id   INT UNSIGNED  DEFAULT NULL,
            recipient_role      VARCHAR(40)   NOT NULL DEFAULT '',
            recipient_email     VARCHAR(190)  NOT NULL DEFAULT '',
            grant_application_id INT UNSIGNED NOT NULL,
            type                VARCHAR(40)   NOT NULL DEFAULT '',
            title               VARCHAR(200)  NOT NULL DEFAULT '',
            body                TEXT          NOT NULL,
            url                 VARCHAR(500)  NOT NULL DEFAULT '',
            is_read             TINYINT(1)    NOT NULL DEFAULT 0,
            created_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_gpn_event (event_key),
            KEY idx_gpn_recipient_user (recipient_user_id),
            KEY idx_gpn_application (grant_application_id),
            KEY idx_gpn_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function grantEvaluationTypeCommittee(): string
{
    return 'committee';
}

function grantEvaluationTypeAdviser(): string
{
    return 'adviser';
}

/** @return list<string> */
function grantPipelineEvaluationTypes(): array
{
    return [
        'committee',
        'adviser',
        'department_chair',
        'dean',
        'research_office',
        'vpaa',
        'finance',
    ];
}

/** @return list<string> */
function grantApproverStepEvaluationTypes(): array
{
    return ['department_chair', 'dean', 'research_office', 'vpaa', 'finance'];
}

function grantEvaluationStepLabel(string $evaluationType): string
{
    return match ($evaluationType) {
        'committee'        => 'Review Committee',
        'adviser'          => 'Academic Adviser',
        'department_chair' => 'Department Chair',
        'dean'             => 'College Dean',
        'research_office'  => 'Research Office',
        'vpaa'             => 'VPAA',
        'finance'          => 'Finance Office',
        default            => ucwords(str_replace('_', ' ', $evaluationType)),
    };
}

function grantEvaluationTypeForApproverRole(string $roleKey = ''): ?string
{
    if ($roleKey === '' && function_exists('getCurrentUserRoleKey')) {
        $roleKey = getCurrentUserRoleKey();
    }

    require_once __DIR__ . '/grant-approval-helpers.php';
    $stepKey = grantApprovalStepKeyForRole($roleKey);
    if ($stepKey === null || $stepKey === '' || $stepKey === 'adviser') {
        return null;
    }

    return $stepKey;
}

function grantGetEvaluationByTypeAndApplication(
    PDO $crad,
    int $applicationId,
    string $evaluationType,
    ?int $proposalVersion = null
): ?array {
    grantEnsureEvaluationTables($crad);

    if ($proposalVersion === null) {
        $app = grantGetApplicationForEvaluation($crad, $applicationId);
        $proposalVersion = max(1, (int) ($app['current_version'] ?? 1));
    }

    $stmt = $crad->prepare("
        SELECT *
          FROM grant_proposal_evaluations
         WHERE grant_application_id = ?
           AND evaluation_type = ?
           AND proposal_version = ?
         ORDER BY id DESC
         LIMIT 1
    ");
    $stmt->execute([$applicationId, $evaluationType, $proposalVersion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * @return array<string, array<string, mixed>>
 */
function grantGetPipelineEvaluationsForApplication(PDO $crad, int $applicationId): array
{
    $app = grantGetApplicationForEvaluation($crad, $applicationId);
    if ($app === null) {
        return [];
    }

    $version = max(1, (int) ($app['current_version'] ?? 1));
    $result  = [];

    $committeeEvals = grantGetLatestEvaluationsForApplications($crad, [$applicationId]);
    if (!empty($committeeEvals[$applicationId])) {
        $result['committee'] = $committeeEvals[$applicationId];
    }

    foreach (grantPipelineEvaluationTypes() as $type) {
        if ($type === 'committee') {
            continue;
        }
        $row = grantGetEvaluationByTypeAndApplication($crad, $applicationId, $type, $version);
        if ($row) {
            $result[$type] = $row;
        }
    }

    return $result;
}

function grantGetApproverEvaluationByApplication(
    PDO $crad,
    int $applicationId,
    ?int $evaluatorUserId = null,
    ?int $proposalVersion = null,
    ?string $roleKey = null
): ?array {
    $evaluationType = grantEvaluationTypeForApproverRole($roleKey ?? '');
    if ($evaluationType === null) {
        return null;
    }

    grantEnsureEvaluationTables($crad);

    $evaluatorUserId = $evaluatorUserId ?? (int) ($_SESSION['user_id'] ?? 0);
    if ($evaluatorUserId <= 0) {
        return null;
    }

    if ($proposalVersion === null) {
        $app = grantGetApplicationForEvaluation($crad, $applicationId);
        $proposalVersion = max(1, (int) ($app['current_version'] ?? 1));
    }

    $stmt = $crad->prepare("
        SELECT *
          FROM grant_proposal_evaluations
         WHERE grant_application_id = ?
           AND evaluator_user_id = ?
           AND evaluation_type = ?
           AND proposal_version = ?
         LIMIT 1
    ");
    $stmt->execute([
        $applicationId,
        $evaluatorUserId,
        $evaluationType,
        $proposalVersion,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function grantHasApproverStepEvaluation(
    PDO $crad,
    int $applicationId,
    ?int $evaluatorUserId = null,
    ?string $roleKey = null
): bool {
    return grantGetApproverEvaluationByApplication($crad, $applicationId, $evaluatorUserId, null, $roleKey) !== null;
}

function grantApproverEvaluationScoredCount(PDO $crad): int
{
    grantEnsureEvaluationTables($crad);

    $evaluationType = grantEvaluationTypeForApproverRole();
    $userId         = (int) ($_SESSION['user_id'] ?? 0);
    if ($evaluationType === null || $userId <= 0) {
        return 0;
    }

    $stmt = $crad->prepare("
        SELECT COUNT(*)
          FROM grant_proposal_evaluations
         WHERE evaluator_user_id = ?
           AND evaluation_type = ?
    ");
    $stmt->execute([$userId, $evaluationType]);

    return (int) $stmt->fetchColumn();
}

/**
 * @return array{ok: true, scores: array<string, float>, total: float}|array{ok: false, error: string}
 */
function grantValidateRubricScoresFromInput(array $input): array
{
    $criteria = grantRubricCriteria();
    $scores   = [];
    $total    = 0.0;

    foreach ($criteria as $key => $max) {
        $field = 'score_' . $key;
        if (!array_key_exists($field, $input) && !array_key_exists($key, $input)) {
            return ['ok' => false, 'error' => 'All rubric criteria scores are required.'];
        }
        $raw = (float) ($input[$field] ?? $input[$key] ?? -1);
        if ($raw < 0 || $raw > $max) {
            $label = ucwords(str_replace('_', ' ', $key));

            return ['ok' => false, 'error' => "{$label} score must be between 0 and {$max}."];
        }
        $scores[$field] = round($raw, 2);
        $total += $scores[$field];
    }

    $total = round($total, 2);
    if ($total > grantRubricMaxTotal()) {
        return ['ok' => false, 'error' => 'Total score cannot exceed 100.'];
    }

    return ['ok' => true, 'scores' => $scores, 'total' => $total];
}

function _grantEnsureEvaluationVersionIndex(PDO $crad): void
{
    try {
        $old = $crad->query("SHOW INDEX FROM grant_proposal_evaluations WHERE Key_name = 'uniq_gpe_app_evaluator'")->fetch();
        if ($old) {
            $crad->exec('ALTER TABLE grant_proposal_evaluations DROP INDEX uniq_gpe_app_evaluator');
        }
        $new = $crad->query("SHOW INDEX FROM grant_proposal_evaluations WHERE Key_name = 'uniq_gpe_app_eval_ver'")->fetch();
        if (!$new) {
            $crad->exec('ALTER TABLE grant_proposal_evaluations ADD UNIQUE KEY uniq_gpe_app_eval_ver (grant_application_id, evaluator_user_id, proposal_version)');
        }
    } catch (Throwable $e) {
        error_log('_grantEnsureEvaluationVersionIndex: ' . $e->getMessage());
    }
}

/**
 * Proposals awaiting committee review (Submitted / Under Review, not yet scored by current evaluator).
 *
 * @return array<int, array<string, mixed>>
 */
/**
 * Proposals at the Academic Adviser approval step (committee already recommended).
 *
 * @return array<int, array<string, mixed>>
 */
function grantAdviserEvaluationQueue(PDO $crad): array
{
    require_once __DIR__ . '/grant-approval-helpers.php';
    grantEnsureApprovalTables($crad);
    grantEnsureEvaluationTables($crad);

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return [];
    }

    $committeeType = grantEvaluationTypeCommittee();
    $adviserType   = grantEvaluationTypeAdviser();

    $stmt = $crad->prepare("
        SELECT
            ga.id,
            ga.grant_opportunity_id,
            ga.applicant_name,
            ga.applicant_user_id,
            ga.college_dept,
            ga.requested_budget,
            ga.research_title,
            ga.abstract,
            ga.objectives,
            ga.proposal_pdf,
            ga.proposal_pdf_original,
            ga.supporting_docs,
            ga.supporting_docs_original,
            ga.ethics_doc,
            ga.ethics_doc_original,
            ga.status,
            ga.submitted_at,
            ga.updated_at,
            ga.proposal_reference,
            ga.current_version,
            go.funding_title,
            go.max_funding_cap,
            go.eligibility,
            go.application_deadline,
            w.id AS workflow_id,
            w.current_step_key,
            w.workflow_status,
            w.updated_at AS workflow_updated_at,
            adviser_step.status AS adviser_step_status,
            adviser_step.acted_at AS adviser_acted_at,
            committee_eval.id AS committee_evaluation_id,
            committee_eval.total_score AS committee_total_score,
            committee_eval.submitted_at AS committee_evaluated_at,
            my_eval.id AS my_evaluation_id,
            my_eval.total_score AS my_total_score,
            my_eval.submitted_at AS my_evaluated_at
        FROM grant_proposal_approval_workflows w
        INNER JOIN grant_applications ga ON ga.id = w.grant_application_id
        INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
        INNER JOIN grant_proposal_approval_steps adviser_step
                ON adviser_step.workflow_id = w.id
               AND adviser_step.step_key = 'adviser'
        LEFT JOIN grant_proposal_evaluations committee_eval
               ON committee_eval.id = (
                    SELECT MAX(e2.id)
                      FROM grant_proposal_evaluations e2
                     WHERE e2.grant_application_id = ga.id
                       AND e2.proposal_version = COALESCE(NULLIF(ga.current_version, 0), 1)
                       AND e2.evaluation_type = ?
               )
        LEFT JOIN grant_proposal_evaluations my_eval
               ON my_eval.grant_application_id = ga.id
              AND my_eval.evaluator_user_id = ?
              AND my_eval.evaluation_type = ?
              AND my_eval.proposal_version = COALESCE(NULLIF(ga.current_version, 0), 1)
        WHERE w.workflow_status = 'In Progress'
          AND w.current_step_key = 'adviser'
          AND adviser_step.status IN ('Pending', 'Queued')
        ORDER BY w.updated_at ASC, ga.id ASC
    ");
    $stmt->execute([$committeeType, $userId, $adviserType]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Proposals at the current approver's workflow step (Dept. Chair → VPAA).
 *
 * @return array<int, array<string, mixed>>
 */
function grantApproverEvaluationQueue(PDO $crad): array
{
    require_once __DIR__ . '/grant-approval-helpers.php';
    grantEnsureApprovalTables($crad);
    grantEnsureEvaluationTables($crad);

    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';
    $stepKey = grantApprovalStepKeyForRole($roleKey);
    if ($stepKey === null || $stepKey === '') {
        return [];
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return [];
    }

    $committeeType = grantEvaluationTypeCommittee();
    $adviserType   = grantEvaluationTypeAdviser();

    $financeVpaaClause = $stepKey === 'finance'
        ? " AND EXISTS (
                SELECT 1
                  FROM grant_proposal_approval_steps vp
                 WHERE vp.workflow_id = w.id
                   AND vp.step_key = 'vpaa'
                   AND vp.status = 'Approved'
          )"
        : '';

    $stmt = $crad->prepare("
        SELECT
            ga.id,
            ga.grant_opportunity_id,
            ga.applicant_name,
            ga.applicant_user_id,
            ga.college_dept,
            ga.requested_budget,
            ga.research_title,
            ga.abstract,
            ga.objectives,
            ga.proposal_pdf,
            ga.proposal_pdf_original,
            ga.supporting_docs,
            ga.supporting_docs_original,
            ga.ethics_doc,
            ga.ethics_doc_original,
            ga.status,
            ga.submitted_at,
            ga.updated_at,
            ga.proposal_reference,
            ga.current_version,
            go.funding_title,
            go.max_funding_cap,
            go.eligibility,
            go.application_deadline,
            w.id AS workflow_id,
            w.current_step_key,
            w.workflow_status,
            w.updated_at AS workflow_updated_at,
            my_step.status AS approver_step_status,
            my_step.acted_at AS approver_acted_at,
            committee_eval.id AS committee_evaluation_id,
            committee_eval.total_score AS committee_total_score,
            committee_eval.submitted_at AS committee_evaluated_at,
            adviser_eval.id AS adviser_evaluation_id,
            adviser_eval.total_score AS adviser_total_score,
            adviser_eval.submitted_at AS adviser_evaluated_at,
            my_eval.id AS my_evaluation_id,
            my_eval.total_score AS my_total_score,
            my_eval.submitted_at AS my_evaluated_at
        FROM grant_proposal_approval_workflows w
        INNER JOIN grant_applications ga ON ga.id = w.grant_application_id
        INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
        INNER JOIN grant_proposal_approval_steps my_step
                ON my_step.workflow_id = w.id
               AND my_step.step_key = ?
        LEFT JOIN grant_proposal_evaluations committee_eval
               ON committee_eval.id = (
                    SELECT MAX(e2.id)
                      FROM grant_proposal_evaluations e2
                     WHERE e2.grant_application_id = ga.id
                       AND e2.proposal_version = COALESCE(NULLIF(ga.current_version, 0), 1)
                       AND e2.evaluation_type = ?
               )
        LEFT JOIN grant_proposal_evaluations adviser_eval
               ON adviser_eval.id = (
                    SELECT MAX(e3.id)
                      FROM grant_proposal_evaluations e3
                     WHERE e3.grant_application_id = ga.id
                       AND e3.proposal_version = COALESCE(NULLIF(ga.current_version, 0), 1)
                       AND e3.evaluation_type = ?
               )
        LEFT JOIN grant_proposal_evaluations my_eval
               ON my_eval.grant_application_id = ga.id
              AND my_eval.evaluator_user_id = ?
              AND my_eval.evaluation_type = ?
              AND my_eval.proposal_version = COALESCE(NULLIF(ga.current_version, 0), 1)
        WHERE w.workflow_status = 'In Progress'
          AND w.current_step_key = ?
          AND my_step.status IN ('Pending', 'Queued')
          {$financeVpaaClause}
        ORDER BY w.updated_at ASC, ga.id ASC
    ");
    $stmt->execute([$stepKey, $committeeType, $adviserType, $userId, $stepKey, $stepKey]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * All proposals in the approval workflow (CRAD Officer monitor view).
 *
 * @return array<int, array<string, mixed>>
 */
function grantMonitorEvaluationQueue(PDO $crad): array
{
    require_once __DIR__ . '/grant-approval-helpers.php';
    grantEnsureApprovalTables($crad);
    grantBackfillApprovalWorkflows($crad);
    grantEnsureEvaluationTables($crad);

    $committeeType = grantEvaluationTypeCommittee();
    $adviserType   = grantEvaluationTypeAdviser();

    $stmt = $crad->prepare("
        SELECT
            ga.id,
            ga.grant_opportunity_id,
            ga.applicant_name,
            ga.applicant_user_id,
            ga.college_dept,
            ga.requested_budget,
            ga.research_title,
            ga.abstract,
            ga.objectives,
            ga.proposal_pdf,
            ga.proposal_pdf_original,
            ga.supporting_docs,
            ga.supporting_docs_original,
            ga.ethics_doc,
            ga.ethics_doc_original,
            ga.status,
            ga.submitted_at,
            ga.updated_at,
            ga.proposal_reference,
            ga.current_version,
            go.funding_title,
            go.max_funding_cap,
            go.eligibility,
            go.application_deadline,
            w.id AS workflow_id,
            w.current_step_key,
            w.workflow_status,
            w.updated_at AS workflow_updated_at,
            cs.step_label AS current_step_label,
            committee_eval.id AS committee_evaluation_id,
            committee_eval.total_score AS committee_total_score,
            committee_eval.submitted_at AS committee_evaluated_at,
            adviser_eval.id AS adviser_evaluation_id,
            adviser_eval.total_score AS adviser_total_score,
            adviser_eval.submitted_at AS adviser_evaluated_at
        FROM grant_proposal_approval_workflows w
        INNER JOIN grant_applications ga ON ga.id = w.grant_application_id
        INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
        LEFT JOIN grant_proposal_approval_steps cs
               ON cs.workflow_id = w.id AND cs.step_key = w.current_step_key
        LEFT JOIN grant_proposal_evaluations committee_eval
               ON committee_eval.id = (
                    SELECT MAX(e2.id)
                      FROM grant_proposal_evaluations e2
                     WHERE e2.grant_application_id = ga.id
                       AND e2.proposal_version = COALESCE(NULLIF(ga.current_version, 0), 1)
                       AND e2.evaluation_type = ?
               )
        LEFT JOIN grant_proposal_evaluations adviser_eval
               ON adviser_eval.id = (
                    SELECT MAX(e3.id)
                      FROM grant_proposal_evaluations e3
                     WHERE e3.grant_application_id = ga.id
                       AND e3.proposal_version = COALESCE(NULLIF(ga.current_version, 0), 1)
                       AND e3.evaluation_type = ?
               )
        ORDER BY w.updated_at DESC, ga.id DESC
    ");
    $stmt->execute([$committeeType, $adviserType]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/** @return array{pending: int, scored: int} */
function grantMonitorEvaluationCounts(PDO $crad): array
{
    require_once __DIR__ . '/grant-approval-helpers.php';
    grantEnsureApprovalTables($crad);

    $pending = (int) $crad->query("
        SELECT COUNT(*) FROM grant_proposal_approval_workflows WHERE workflow_status = 'In Progress'
    ")->fetchColumn();
    $scored = (int) $crad->query("
        SELECT COUNT(*) FROM grant_proposal_approval_workflows WHERE workflow_status = 'Completed'
    ")->fetchColumn();

    return ['pending' => $pending, 'scored' => $scored];
}

function grantApproverSignoffCount(PDO $crad): int
{
    require_once __DIR__ . '/grant-approval-helpers.php';
    grantEnsureApprovalTables($crad);

    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';
    $userId  = (int) ($_SESSION['user_id'] ?? 0);
    if ($roleKey === '' || $userId <= 0) {
        return 0;
    }

    $stmt = $crad->prepare("
        SELECT COUNT(*)
          FROM grant_proposal_approval_steps
         WHERE approver_role_key = ?
           AND approver_user_id = ?
           AND status = 'Approved'
    ");
    $stmt->execute([$roleKey, $userId]);

    return (int) $stmt->fetchColumn();
}

function grantGetLatestAdviserEvaluationByApplication(PDO $crad, int $applicationId): ?array
{
    grantEnsureEvaluationTables($crad);

    $app = grantGetApplicationForEvaluation($crad, $applicationId);
    if ($app === null) {
        return null;
    }

    $proposalVersion = max(1, (int) ($app['current_version'] ?? 1));

    $stmt = $crad->prepare("
        SELECT *
          FROM grant_proposal_evaluations
         WHERE grant_application_id = ?
           AND evaluation_type = ?
           AND proposal_version = ?
         ORDER BY id DESC
         LIMIT 1
    ");
    $stmt->execute([
        $applicationId,
        grantEvaluationTypeAdviser(),
        $proposalVersion,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function grantAdviserEvaluationScoredCount(PDO $crad): int
{
    grantEnsureEvaluationTables($crad);

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return 0;
    }

    $stmt = $crad->prepare("
        SELECT COUNT(*)
          FROM grant_proposal_evaluations
         WHERE evaluator_user_id = ?
           AND evaluation_type = ?
    ");
    $stmt->execute([$userId, grantEvaluationTypeAdviser()]);

    return (int) $stmt->fetchColumn();
}

function grantGetAdviserEvaluationByApplication(
    PDO $crad,
    int $applicationId,
    ?int $evaluatorUserId = null,
    ?int $proposalVersion = null
): ?array {
    grantEnsureEvaluationTables($crad);

    $evaluatorUserId = $evaluatorUserId ?? (int) ($_SESSION['user_id'] ?? 0);
    if ($evaluatorUserId <= 0) {
        return null;
    }

    if ($proposalVersion === null) {
        $app = grantGetApplicationForEvaluation($crad, $applicationId);
        $proposalVersion = max(1, (int) ($app['current_version'] ?? 1));
    }

    $stmt = $crad->prepare("
        SELECT *
          FROM grant_proposal_evaluations
         WHERE grant_application_id = ?
           AND evaluator_user_id = ?
           AND evaluation_type = ?
           AND proposal_version = ?
         LIMIT 1
    ");
    $stmt->execute([
        $applicationId,
        $evaluatorUserId,
        grantEvaluationTypeAdviser(),
        $proposalVersion,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function grantHasAdviserEvaluation(PDO $crad, int $applicationId, ?int $evaluatorUserId = null): bool
{
    return grantGetAdviserEvaluationByApplication($crad, $applicationId, $evaluatorUserId) !== null;
}

function grantApplicationOpenForEvaluationViewer(PDO $crad, int $applicationId): bool
{
    if (grantIsAdviserEvaluationViewer()) {
        require_once __DIR__ . '/grant-approval-helpers.php';
        grantEnsureApprovalTables($crad);

        $stmt = $crad->prepare("
            SELECT w.id
              FROM grant_proposal_approval_workflows w
             WHERE w.grant_application_id = ?
               AND w.workflow_status = 'In Progress'
               AND w.current_step_key = 'adviser'
             LIMIT 1
        ");
        $stmt->execute([$applicationId]);

        return (bool) $stmt->fetchColumn();
    }

    if (grantIsGrantApproverEvaluationViewer()) {
        require_once __DIR__ . '/grant-approval-helpers.php';
        grantEnsureApprovalTables($crad);

        $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';
        $stepKey = grantApprovalStepKeyForRole($roleKey);
        if ($stepKey === null || $stepKey === '') {
            return false;
        }

        $stmt = $crad->prepare("
            SELECT w.id
              FROM grant_proposal_approval_workflows w
             WHERE w.grant_application_id = ?
               AND w.workflow_status = 'In Progress'
               AND w.current_step_key = ?
             LIMIT 1
        ");
        $stmt->execute([$applicationId, $stepKey]);

        return (bool) $stmt->fetchColumn();
    }

    if (grantIsGrantWorkflowMonitor()) {
        require_once __DIR__ . '/grant-approval-helpers.php';

        return grantGetApprovalWorkflowByApplicationId($crad, $applicationId) !== null;
    }

    $application = grantGetApplicationForEvaluation($crad, $applicationId);

    return $application !== null
        && in_array((string) ($application['status'] ?? ''), ['Submitted', 'Under Review'], true);
}

function grantEvaluationQueue(PDO $crad, ?int $evaluatorUserId = null): array
{
    grantEnsureEvaluationTables($crad);

    if (grantIsAdviserEvaluationViewer()) {
        return grantAdviserEvaluationQueue($crad);
    }

    if (grantIsGrantApproverEvaluationViewer()) {
        return grantApproverEvaluationQueue($crad);
    }

    if (grantIsGrantWorkflowMonitor()) {
        return grantMonitorEvaluationQueue($crad);
    }

    $evaluatorUserId = $evaluatorUserId ?? (int) ($_SESSION['user_id'] ?? 0);
    if ($evaluatorUserId <= 0) {
        return [];
    }

    $stmt = $crad->prepare("
        SELECT
            ga.id,
            ga.grant_opportunity_id,
            ga.applicant_name,
            ga.applicant_user_id,
            ga.college_dept,
            ga.requested_budget,
            ga.research_title,
            ga.abstract,
            ga.objectives,
            ga.proposal_pdf,
            ga.proposal_pdf_original,
            ga.supporting_docs,
            ga.supporting_docs_original,
            ga.ethics_doc,
            ga.ethics_doc_original,
            ga.status,
            ga.submitted_at,
            ga.updated_at,
            ga.proposal_reference,
            ga.current_version,
            go.funding_title,
            go.max_funding_cap,
            go.eligibility,
            go.application_deadline,
            ev.id AS my_evaluation_id,
            ev.total_score AS my_total_score,
            ev.submitted_at AS my_evaluated_at
        FROM grant_applications ga
        INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
        LEFT JOIN grant_proposal_evaluations ev
               ON ev.grant_application_id = ga.id
              AND ev.evaluator_user_id = ?
              AND ev.evaluation_type = ?
              AND ev.proposal_version = COALESCE(NULLIF(ga.current_version, 0), 1)
        WHERE ga.status IN ('Submitted', 'Under Review')
        ORDER BY ga.submitted_at ASC, ga.id ASC
    ");
    $stmt->execute([$evaluatorUserId, grantEvaluationTypeCommittee()]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function grantGetApplicationForEvaluation(PDO $crad, int $applicationId): ?array
{
    grantEnsureEvaluationTables($crad);

    $stmt = $crad->prepare("
        SELECT
            ga.*,
            go.funding_title,
            go.max_funding_cap,
            go.eligibility,
            go.application_deadline
        FROM grant_applications ga
        INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
        WHERE ga.id = ?
        LIMIT 1
    ");
    $stmt->execute([$applicationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function grantGetEvaluationByApplication(PDO $crad, int $applicationId, ?int $evaluatorUserId = null, ?int $proposalVersion = null): ?array
{
    grantEnsureEvaluationTables($crad);

    $evaluatorUserId = $evaluatorUserId ?? (int) ($_SESSION['user_id'] ?? 0);
    if ($evaluatorUserId <= 0) {
        return null;
    }

    if ($proposalVersion === null) {
        $app = grantGetApplicationForEvaluation($crad, $applicationId);
        $proposalVersion = max(1, (int) ($app['current_version'] ?? 1));
    }

    $stmt = $crad->prepare("
        SELECT *
        FROM grant_proposal_evaluations
        WHERE grant_application_id = ?
          AND evaluator_user_id = ?
          AND evaluation_type = ?
          AND proposal_version = ?
        LIMIT 1
    ");
    $stmt->execute([$applicationId, $evaluatorUserId, grantEvaluationTypeCommittee(), $proposalVersion]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Latest committee evaluation per application (for researcher feedback display).
 *
 * @param  array<int, int> $applicationIds
 * @return array<int, array<string, mixed>>
 */
function grantGetLatestEvaluationsForApplications(PDO $crad, array $applicationIds): array
{
    grantEnsureEvaluationTables($crad);

    $applicationIds = array_values(array_unique(array_filter(array_map('intval', $applicationIds))));
    if ($applicationIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($applicationIds), '?'));
    $stmt = $crad->prepare("
        SELECT e.*
        FROM grant_proposal_evaluations e
        INNER JOIN grant_applications ga ON ga.id = e.grant_application_id
        INNER JOIN (
            SELECT e2.grant_application_id, MAX(e2.id) AS latest_id
            FROM grant_proposal_evaluations e2
            INNER JOIN grant_applications ga2 ON ga2.id = e2.grant_application_id
            WHERE e2.grant_application_id IN ({$placeholders})
              AND e2.proposal_version = COALESCE(NULLIF(ga2.current_version, 0), 1)
              AND e2.evaluation_type = ?
              AND (
                    ga2.status NOT IN ('Revision Required', 'Rejected')
                 OR (ga2.status = 'Revision Required' AND e2.recommendation = 'require_revisions')
                 OR (ga2.status = 'Rejected' AND e2.recommendation = 'disapprove')
              )
            GROUP BY e2.grant_application_id
        ) latest ON latest.latest_id = e.id
    ");
    $stmt->execute(array_merge($applicationIds, [grantEvaluationTypeCommittee()]));

    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $map[(int) $row['grant_application_id']] = $row;
    }

    return $map;
}

function grantProposalsApplicationsUrl(): string
{
    return BASE_URL . '/modules/crad/pages/proposals-applications.php';
}

function grantNotifyApplicantEvaluationDecision(
    PDO $crad,
    array $application,
    string $recommendation,
    float $totalScore,
    string $evaluatorName,
    ?string $revisionReason = null
): void {
    grantEnsureEvaluationTables($crad);

    $applicationId = (int) ($application['id'] ?? 0);
    $recipientUserId = (int) ($application['applicant_user_id'] ?? 0);
    if ($applicationId <= 0 || $recipientUserId <= 0) {
        return;
    }

    $title = (string) ($application['research_title'] ?? 'your grant proposal');
    $titleShort = mb_strimwidth($title, 0, 80, '…');

    if ($recommendation === 'disapprove') {
        $type = 'grant_rejected';
        $notifTitle = 'Grant Proposal Rejected';
        $body = sprintf(
            'Your grant proposal "%s" was disapproved by the review committee (score: %s/100). View details in Proposals & Applications.',
            $titleShort,
            number_format($totalScore, 1)
        );
        $notifUrl = grantProposalsApplicationsUrl();
    } elseif ($recommendation === 'require_revisions') {
        $type = 'grant_revision_required';
        $notifTitle = 'Revise Grant Proposal';
        $ref = trim((string) ($application['proposal_reference'] ?? ''));
        $refLabel = $ref !== '' ? $ref : ('Proposal #' . $applicationId);
        $reasonPreview = trim((string) $revisionReason);
        if ($reasonPreview !== '') {
            $reasonPreview = mb_strimwidth($reasonPreview, 0, 120, '…');
            $body = sprintf(
                '%s requires revisions. %s Tap to revise and resubmit.',
                $refLabel,
                $reasonPreview
            );
        } else {
            $body = sprintf(
                '%s requires revisions. Review committee feedback is ready — tap to revise and resubmit.',
                $refLabel
            );
        }
        $notifUrl = grantReviseProposalUrl($applicationId);
    } else {
        return;
    }

    $recipientRole = 'student';
    if (function_exists('db')) {
        $mainDb = db();
        if ($mainDb) {
            $userStmt = $mainDb->prepare('SELECT role_key FROM users WHERE id = ? LIMIT 1');
            $userStmt->execute([$recipientUserId]);
            $recipientRole = (string) ($userStmt->fetchColumn() ?: 'student');
        }
    }

    $eventKey = 'grant-proposal:' . $type . ':' . $applicationId
        . ':v' . max(1, (int) ($application['current_version'] ?? 1))
        . ':u' . $recipientUserId;
    $stmt = $crad->prepare("
        INSERT INTO grant_proposal_notifications
            (event_key, recipient_user_id, recipient_role, recipient_email,
             grant_application_id, type, title, body, url)
        VALUES
            (?, ?, ?, '', ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            body = VALUES(body),
            url = VALUES(url),
            is_read = 0,
            created_at = NOW()
    ");
    $stmt->execute([
        $eventKey,
        $recipientUserId,
        $recipientRole !== '' ? $recipientRole : 'student',
        $applicationId,
        $type,
        $notifTitle,
        $body,
        $notifUrl,
    ]);
}

function grantInsertGrantProposalNotification(
    PDO $crad,
    int $applicationId,
    int $recipientUserId,
    string $recipientRole,
    string $type,
    string $eventKey,
    string $title,
    string $body,
    string $url
): void {
    grantEnsureEvaluationTables($crad);

    if ($applicationId <= 0 || $recipientUserId <= 0) {
        return;
    }

    $stmt = $crad->prepare("
        INSERT INTO grant_proposal_notifications
            (event_key, recipient_user_id, recipient_role, recipient_email,
             grant_application_id, type, title, body, url)
        VALUES
            (?, ?, ?, '', ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            body = VALUES(body),
            url = VALUES(url),
            is_read = 0,
            created_at = NOW()
    ");
    $stmt->execute([
        $eventKey,
        $recipientUserId,
        $recipientRole !== '' ? $recipientRole : 'student',
        $applicationId,
        $type,
        $title,
        $body,
        $url,
    ]);
}

/**
 * Notify researcher when an approver returns the proposal for revision (NO branch).
 *
 * @param array<string, mixed> $application
 * @param array<string, mixed> $currentStep
 */
function grantNotifyApplicantApprovalReturn(
    PDO $crad,
    array $application,
    array $currentStep,
    string $returnedByName,
    string $remarks
): void {
    $applicationId = (int) ($application['id'] ?? 0);
    $recipientUserId = (int) ($application['applicant_user_id'] ?? 0);
    if ($applicationId <= 0 || $recipientUserId <= 0) {
        return;
    }

    $ref = trim((string) ($application['proposal_reference'] ?? ''));
    $refLabel = $ref !== '' ? $ref : ('Proposal #' . $applicationId);
    $stepOrder = (int) ($currentStep['step_order'] ?? 0);
    $stepLabel = (string) ($currentStep['step_label'] ?? 'Approver');
    $reason = mb_strimwidth(trim($remarks), 0, 160, '…');

    $body = sprintf(
        '%s was returned for revision. Returned by: %s (Approval Level %d). Reason: %s Open Revisions Requested to revise and resubmit.',
        $refLabel,
        $returnedByName !== '' ? $returnedByName : $stepLabel,
        $stepOrder,
        $reason
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

    $eventKey = 'grant-proposal:grant_approval_return:' . $applicationId
        . ':v' . max(1, (int) ($application['current_version'] ?? 1))
        . ':lvl' . $stepOrder
        . ':u' . $recipientUserId;

    grantInsertGrantProposalNotification(
        $crad,
        $applicationId,
        $recipientUserId,
        $recipientRole,
        'grant_approval_return',
        $eventKey,
        'Proposal Returned for Revision',
        $body,
        grantRevisionsRequestedUrl()
    );
}

/** Notify researcher when Finance completes the final approval (Level 6). */
function grantNotifyApplicantApprovedFunded(PDO $crad, array $application, string $approverName): void
{
    require_once __DIR__ . '/grant-funding-helpers.php';

    $applicationId = (int) ($application['id'] ?? 0);
    $recipientUserId = (int) ($application['applicant_user_id'] ?? 0);
    if ($applicationId <= 0 || $recipientUserId <= 0) {
        return;
    }

    $ref = trim((string) ($application['proposal_reference'] ?? ''));
    $refLabel = $ref !== '' ? $ref : ('Proposal #' . $applicationId);
    $titleShort = mb_strimwidth((string) ($application['research_title'] ?? 'your grant proposal'), 0, 80, '…');

    $body = sprintf(
        '%s (%s) is APPROVED & FUNDED after all six institutional sign-offs. Finance Office recorded the final approval.',
        $refLabel,
        $titleShort
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

    $eventKey = 'grant-proposal:grant_approved_funded:' . $applicationId
        . ':v' . max(1, (int) ($application['current_version'] ?? 1))
        . ':u' . $recipientUserId;

    grantInsertGrantProposalNotification(
        $crad,
        $applicationId,
        $recipientUserId,
        $recipientRole,
        'grant_approved_funded',
        $eventKey,
        'Approved & Funded',
        $body,
        grantBudgetDisbursementUrl($applicationId)
    );
}

function grantParseEvaluationRecommendationInput(array $input): array
{
    $recommendation = strtolower(trim((string) ($input['recommendation'] ?? '')));
    if (!array_key_exists($recommendation, grantRecommendationOptions())) {
        return ['ok' => false, 'error' => 'Please select a recommendation decision.'];
    }

    $revisionReason = trim((string) ($input['revision_reason'] ?? ''));
    if ($recommendation === 'require_revisions' && $revisionReason === '') {
        return ['ok' => false, 'error' => 'Revision reason is required when selecting Require Revisions.'];
    }

    return [
        'ok'              => true,
        'recommendation'  => $recommendation,
        'revision_reason' => $revisionReason,
    ];
}

/**
 * Apply disapprove / require_revisions after a pipeline-stage rubric evaluation.
 *
 * @param array<string, mixed> $application
 * @return array{recommendation: string, new_status: string}|null
 */
function grantApplyPipelineEvaluationRecommendation(
    PDO $crad,
    int $applicationId,
    array $application,
    string $recommendation,
    string $revisionReason,
    string $evaluatorName,
    float $totalScore,
    string $roleKey
): ?array {
    if ($recommendation === 'recommend') {
        return null;
    }

    require_once __DIR__ . '/grant-approval-helpers.php';
    grantEnsureApprovalTables($crad);

    $evaluatorUserId = (int) ($_SESSION['user_id'] ?? 0);

    if ($recommendation === 'disapprove') {
        $crad->prepare("
            UPDATE grant_applications
               SET status = 'Rejected', updated_at = NOW()
             WHERE id = ?
        ")->execute([$applicationId]);

        $workflow = grantGetApprovalWorkflowByApplicationId($crad, $applicationId);
        if ($workflow !== null) {
            $workflowId = (int) ($workflow['id'] ?? 0);
            $crad->prepare("
                UPDATE grant_proposal_approval_workflows
                   SET workflow_status = 'Cancelled', updated_at = NOW()
                 WHERE id = ?
            ")->execute([$workflowId]);
        }

        grantNotifyApplicantEvaluationDecision(
            $crad,
            $application,
            'disapprove',
            $totalScore,
            $evaluatorName,
            $revisionReason !== '' ? $revisionReason : null
        );

        return ['recommendation' => 'disapprove', 'new_status' => 'Rejected'];
    }

    $workflow = grantGetApprovalWorkflowByApplicationId($crad, $applicationId);
    $stepKey  = grantApprovalStepKeyForRole($roleKey) ?? '';

    if ($workflow !== null && $stepKey !== '') {
        $workflowId = (int) ($workflow['id'] ?? 0);
        $currentStep = null;
        foreach (grantGetApprovalSteps($crad, $workflowId) as $step) {
            if ((string) ($step['step_key'] ?? '') === $stepKey) {
                $currentStep = $step;
                break;
            }
        }

        if ($currentStep !== null) {
            $returnedByLabel = grantApprovalReturnedByLabel($currentStep);
            $crad->prepare("
            UPDATE grant_proposal_approval_steps
               SET status = 'Returned',
                   approver_user_id = ?,
                   approver_name = ?,
                   remarks = ?,
                   acted_at = NOW(),
                   updated_at = NOW()
             WHERE workflow_id = ? AND step_key = ?
        ")->execute([
            $evaluatorUserId > 0 ? $evaluatorUserId : null,
            $returnedByLabel,
            $revisionReason,
            $workflowId,
            $stepKey,
        ]);

            $crad->prepare("
            UPDATE grant_proposal_approval_workflows
               SET workflow_status = 'Returned', updated_at = NOW()
             WHERE id = ?
        ")->execute([$workflowId]);

            grantNotifyApplicantApprovalReturn(
                $crad,
                $application,
                $currentStep,
                $returnedByLabel,
                $revisionReason
            );
        }
    }

    $crad->prepare("
        UPDATE grant_applications
           SET status = 'Revision Required', updated_at = NOW()
         WHERE id = ?
    ")->execute([$applicationId]);

    if ($workflow === null) {
        grantNotifyApplicantEvaluationDecision(
            $crad,
            $application,
            'require_revisions',
            $totalScore,
            $evaluatorName,
            $revisionReason
        );
    }

    return ['recommendation' => 'require_revisions', 'new_status' => 'Revision Required'];
}

function grantSubmitAdviserProposalEvaluation(PDO $crad, int $applicationId, array $input): array
{
    grantEnsureEvaluationTables($crad);

    $evaluatorUserId = (int) ($_SESSION['user_id'] ?? 0);
    $evaluatorName   = trim((string) ($_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? ''));

    if ($evaluatorUserId <= 0) {
        return ['ok' => false, 'error' => 'Invalid evaluator session.'];
    }

    if (!grantApplicationOpenForEvaluationViewer($crad, $applicationId)) {
        return ['ok' => false, 'error' => 'This proposal is not awaiting Academic Adviser evaluation.'];
    }

    $application = grantGetApplicationForEvaluation($crad, $applicationId);
    if (!$application) {
        return ['ok' => false, 'error' => 'Proposal not found.'];
    }

    $proposalVersion = max(1, (int) ($application['current_version'] ?? 1));

    if (grantGetAdviserEvaluationByApplication($crad, $applicationId, $evaluatorUserId, $proposalVersion)) {
        return ['ok' => false, 'error' => 'You have already submitted your adviser evaluation for this proposal version.'];
    }

    $parsedRecommendation = grantParseEvaluationRecommendationInput($input);
    if (empty($parsedRecommendation['ok'])) {
        return ['ok' => false, 'error' => $parsedRecommendation['error'] ?? 'Invalid recommendation.'];
    }

    $parsed = grantValidateRubricScoresFromInput($input);
    if (empty($parsed['ok'])) {
        return ['ok' => false, 'error' => $parsed['error'] ?? 'Invalid rubric scores.'];
    }

    $recommendation  = (string) $parsedRecommendation['recommendation'];
    $revisionReason  = (string) $parsedRecommendation['revision_reason'];
    $scores          = $parsed['scores'];
    $total           = (float) $parsed['total'];
    $comments            = trim((string) ($input['comments'] ?? ''));
    $recommendations     = trim((string) ($input['recommendations'] ?? ''));
    $requiredCorrections = trim((string) ($input['required_corrections'] ?? ''));
    if ($recommendation === 'require_revisions' && $requiredCorrections === '') {
        $requiredCorrections = $revisionReason;
    }

    try {
        $crad->beginTransaction();

        $stmt = $crad->prepare("
            INSERT INTO grant_proposal_evaluations
                (grant_application_id, proposal_version, evaluator_user_id, evaluator_name, evaluation_type,
                 score_rationale, score_methodology, score_budget,
                 score_team_capability, score_compliance, total_score,
                 comments, recommendations, required_corrections,
                 recommendation, revision_reason,
                 submitted_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?,
                 NOW(), NOW())
        ");
        $stmt->execute([
            $applicationId,
            $proposalVersion,
            $evaluatorUserId,
            $evaluatorName,
            grantEvaluationTypeAdviser(),
            $scores['score_rationale'],
            $scores['score_methodology'],
            $scores['score_budget'],
            $scores['score_team_capability'],
            $scores['score_compliance'],
            $total,
            $comments !== '' ? $comments : null,
            $recommendations !== '' ? $recommendations : null,
            $requiredCorrections !== '' ? $requiredCorrections : null,
            $recommendation,
            $revisionReason !== '' ? $revisionReason : null,
        ]);

        $decision = grantApplyPipelineEvaluationRecommendation(
            $crad,
            $applicationId,
            $application,
            $recommendation,
            $revisionReason,
            $evaluatorName,
            $total,
            'adviser'
        );

        $crad->commit();

        $result = [
            'ok'          => true,
            'id'          => (int) $crad->lastInsertId(),
            'total_score' => $total,
            'recommendation' => $recommendation,
        ];
        if ($decision !== null) {
            $result['new_status'] = $decision['new_status'];
        }

        return $result;
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantSubmitAdviserProposalEvaluation: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to save adviser evaluation. Please try again.'];
    }
}

function grantSubmitApproverProposalEvaluation(PDO $crad, int $applicationId, array $input): array
{
    grantEnsureEvaluationTables($crad);

    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';
    $evaluationType = grantEvaluationTypeForApproverRole($roleKey);
    if ($evaluationType === null) {
        return ['ok' => false, 'error' => 'Your role is not authorized to score at this approval level.'];
    }

    $evaluatorUserId = (int) ($_SESSION['user_id'] ?? 0);
    $evaluatorName   = trim((string) ($_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? ''));

    if ($evaluatorUserId <= 0) {
        return ['ok' => false, 'error' => 'Invalid evaluator session.'];
    }

    if (!grantApplicationOpenForEvaluationViewer($crad, $applicationId)) {
        return [
            'ok'    => false,
            'error' => grantEvaluationStepLabel($evaluationType) . ' evaluation is not open for this proposal.',
        ];
    }

    $application = grantGetApplicationForEvaluation($crad, $applicationId);
    if (!$application) {
        return ['ok' => false, 'error' => 'Proposal not found.'];
    }

    $proposalVersion = max(1, (int) ($application['current_version'] ?? 1));

    if (grantGetApproverEvaluationByApplication($crad, $applicationId, $evaluatorUserId, $proposalVersion, $roleKey)) {
        return [
            'ok'    => false,
            'error' => 'You have already submitted your '
                . grantEvaluationStepLabel($evaluationType)
                . ' evaluation for this proposal version.',
        ];
    }

    $parsedRecommendation = grantParseEvaluationRecommendationInput($input);
    if (empty($parsedRecommendation['ok'])) {
        return ['ok' => false, 'error' => $parsedRecommendation['error'] ?? 'Invalid recommendation.'];
    }

    $parsed = grantValidateRubricScoresFromInput($input);
    if (empty($parsed['ok'])) {
        return ['ok' => false, 'error' => $parsed['error'] ?? 'Invalid rubric scores.'];
    }

    $recommendation  = (string) $parsedRecommendation['recommendation'];
    $revisionReason  = (string) $parsedRecommendation['revision_reason'];
    $scores          = $parsed['scores'];
    $total           = (float) $parsed['total'];
    $comments            = trim((string) ($input['comments'] ?? ''));
    $recommendations     = trim((string) ($input['recommendations'] ?? ''));
    $requiredCorrections = trim((string) ($input['required_corrections'] ?? ''));
    if ($recommendation === 'require_revisions' && $requiredCorrections === '') {
        $requiredCorrections = $revisionReason;
    }

    try {
        $crad->beginTransaction();

        $stmt = $crad->prepare("
            INSERT INTO grant_proposal_evaluations
                (grant_application_id, proposal_version, evaluator_user_id, evaluator_name, evaluation_type,
                 score_rationale, score_methodology, score_budget,
                 score_team_capability, score_compliance, total_score,
                 comments, recommendations, required_corrections,
                 recommendation, revision_reason,
                 submitted_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?,
                 NOW(), NOW())
        ");
        $stmt->execute([
            $applicationId,
            $proposalVersion,
            $evaluatorUserId,
            $evaluatorName,
            $evaluationType,
            $scores['score_rationale'],
            $scores['score_methodology'],
            $scores['score_budget'],
            $scores['score_team_capability'],
            $scores['score_compliance'],
            $total,
            $comments !== '' ? $comments : null,
            $recommendations !== '' ? $recommendations : null,
            $requiredCorrections !== '' ? $requiredCorrections : null,
            $recommendation,
            $revisionReason !== '' ? $revisionReason : null,
        ]);

        $decision = grantApplyPipelineEvaluationRecommendation(
            $crad,
            $applicationId,
            $application,
            $recommendation,
            $revisionReason,
            $evaluatorName,
            $total,
            $roleKey
        );

        $crad->commit();

        $result = [
            'ok'             => true,
            'id'             => (int) $crad->lastInsertId(),
            'total_score'    => $total,
            'recommendation' => $recommendation,
        ];
        if ($decision !== null) {
            $result['new_status'] = $decision['new_status'];
        }

        return $result;
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantSubmitApproverProposalEvaluation: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to save evaluation. Please try again.'];
    }
}

/**
 * @param array<string, mixed> $input
 * @return array{ok: bool, id?: int, total_score?: float, recommendation?: string, new_status?: string, error?: string}
 */
function grantSubmitProposalEvaluation(PDO $crad, int $applicationId, array $input): array
{
    grantEnsureEvaluationTables($crad);

    if (grantIsAdviserEvaluationViewer()) {
        return grantSubmitAdviserProposalEvaluation($crad, $applicationId, $input);
    }

    if (grantIsGrantApproverEvaluationViewer()) {
        return grantSubmitApproverProposalEvaluation($crad, $applicationId, $input);
    }

    $evaluatorUserId = (int) ($_SESSION['user_id'] ?? 0);
    $evaluatorName   = trim((string) ($_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? ''));

    if ($evaluatorUserId <= 0) {
        return ['ok' => false, 'error' => 'Invalid evaluator session.'];
    }

    $application = grantGetApplicationForEvaluation($crad, $applicationId);
    if (!$application) {
        return ['ok' => false, 'error' => 'Proposal not found.'];
    }

    if (!in_array((string) ($application['status'] ?? ''), ['Submitted', 'Under Review'], true)) {
        return ['ok' => false, 'error' => 'This proposal is no longer open for committee evaluation.'];
    }

    $proposalVersion = max(1, (int) ($application['current_version'] ?? 1));

    if (grantGetEvaluationByApplication($crad, $applicationId, $evaluatorUserId, $proposalVersion)) {
        return ['ok' => false, 'error' => 'You have already submitted an evaluation for this proposal version.'];
    }

    $parsedRecommendation = grantParseEvaluationRecommendationInput($input);
    if (empty($parsedRecommendation['ok'])) {
        return ['ok' => false, 'error' => $parsedRecommendation['error'] ?? 'Invalid recommendation.'];
    }

    $recommendation = (string) $parsedRecommendation['recommendation'];
    $revisionReason = (string) $parsedRecommendation['revision_reason'];

    $newStatus = grantStatusForRecommendation($recommendation);
    if ($newStatus === null) {
        return ['ok' => false, 'error' => 'Invalid recommendation selected.'];
    }

    $currentStatus = (string) ($application['status'] ?? '');
    $shouldUpdateStatus = true;
    if ($recommendation === 'recommend') {
        if (in_array($currentStatus, ['Rejected', 'Revision Required', 'Approved', 'Denied', 'Withdrawn'], true)) {
            $newStatus = $currentStatus;
            $shouldUpdateStatus = false;
        } elseif ($currentStatus === 'Submitted') {
            $newStatus = 'Under Review';
        } else {
            $newStatus = $currentStatus;
            $shouldUpdateStatus = false;
        }
    }

    $criteria = grantRubricCriteria();
    $parsed   = grantValidateRubricScoresFromInput($input);
    if (empty($parsed['ok'])) {
        return ['ok' => false, 'error' => $parsed['error'] ?? 'Invalid rubric scores.'];
    }

    $scores = $parsed['scores'];
    $total  = (float) $parsed['total'];

    $comments            = trim((string) ($input['comments'] ?? ''));
    $recommendations     = trim((string) ($input['recommendations'] ?? ''));
    $requiredCorrections = trim((string) ($input['required_corrections'] ?? ''));
    if ($recommendation === 'require_revisions' && $requiredCorrections === '') {
        $requiredCorrections = $revisionReason;
    }

    try {
        $crad->beginTransaction();

        $stmt = $crad->prepare("
            INSERT INTO grant_proposal_evaluations
                (grant_application_id, proposal_version, evaluator_user_id, evaluator_name, evaluation_type,
                 score_rationale, score_methodology, score_budget,
                 score_team_capability, score_compliance, total_score,
                 comments, recommendations, required_corrections,
                 recommendation, revision_reason,
                 submitted_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?, ?,
                 ?, ?,
                 NOW(), NOW())
        ");
        $stmt->execute([
            $applicationId,
            $proposalVersion,
            $evaluatorUserId,
            $evaluatorName,
            grantEvaluationTypeCommittee(),
            $scores['score_rationale'],
            $scores['score_methodology'],
            $scores['score_budget'],
            $scores['score_team_capability'],
            $scores['score_compliance'],
            $total,
            $comments !== '' ? $comments : null,
            $recommendations !== '' ? $recommendations : null,
            $requiredCorrections !== '' ? $requiredCorrections : null,
            $recommendation,
            $revisionReason !== '' ? $revisionReason : null,
        ]);

        if ($shouldUpdateStatus && $newStatus !== $currentStatus) {
            $crad->prepare("
                UPDATE grant_applications
                   SET status = ?, updated_at = NOW()
                 WHERE id = ?
            ")->execute([$newStatus, $applicationId]);
        }

        if (in_array($recommendation, ['disapprove', 'require_revisions'], true)) {
            grantNotifyApplicantEvaluationDecision(
                $crad,
                $application,
                $recommendation,
                $total,
                $evaluatorName,
                $revisionReason !== '' ? $revisionReason : null
            );
        }

        $crad->commit();

        if ($recommendation === 'recommend') {
            require_once __DIR__ . '/grant-approval-helpers.php';
            grantStartApprovalWorkflow($crad, $applicationId);
        }

        return [
            'ok'             => true,
            'id'             => (int) $crad->lastInsertId(),
            'total_score'    => $total,
            'recommendation' => $recommendation,
            'new_status'     => $newStatus,
        ];
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantSubmitProposalEvaluation: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Failed to save evaluation. Please try again.'];
    }
}

function grantProposalFileUrl(int $applicationId, string $field = 'proposal'): string
{
    return BASE_URL . '/modules/crad/grant-proposal-file.php?id=' . $applicationId . '&field=' . rawurlencode($field);
}

/**
 * Ensure revision/rejection notifications exist for the researcher's pending decisions.
 */
function grantBackfillApplicantDecisionNotifications(PDO $crad, ?int $userId = null): void
{
    static $done = [];
    $userId = $userId ?? (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0 || isset($done[$userId])) {
        return;
    }
    $done[$userId] = true;

    grantEnsureEvaluationTables($crad);

    try {
        $stmt = $crad->prepare("
            SELECT ga.*
              FROM grant_applications ga
             WHERE ga.applicant_user_id = ?
               AND ga.status IN ('Revision Required', 'Rejected')
        ");
        $stmt->execute([$userId]);
        $apps = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($apps === []) {
            return;
        }

        $appIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $apps);
        $evals  = grantGetLatestEvaluationsForApplications($crad, $appIds);

        foreach ($apps as $app) {
            $appId = (int) ($app['id'] ?? 0);
            if ($appId <= 0) {
                continue;
            }

            $eval = $evals[$appId] ?? null;
            $rec  = $eval ? (string) ($eval['recommendation'] ?? '') : '';
            if ((string) ($app['status'] ?? '') === 'Revision Required' && $rec !== 'require_revisions') {
                $rec = 'require_revisions';
            } elseif ((string) ($app['status'] ?? '') === 'Rejected' && $rec !== 'disapprove') {
                $rec = 'disapprove';
            }

            if (!in_array($rec, ['require_revisions', 'disapprove'], true)) {
                continue;
            }

            grantNotifyApplicantEvaluationDecision(
                $crad,
                $app,
                $rec,
                (float) ($eval['total_score'] ?? 0),
                (string) ($eval['evaluator_name'] ?? 'Review Committee'),
                $eval ? (string) ($eval['revision_reason'] ?? '') : null
            );
        }
    } catch (Throwable $e) {
        error_log('grantBackfillApplicantDecisionNotifications: ' . $e->getMessage());
    }
}

/**
 * Grant proposal notifications for the signed-in researcher.
 *
 * @return array<int, array<string, mixed>>
 */
function grantProposalNotificationsForCurrentUser(int $limit = 8): array
{
    $crad = cradDb();
    if (!$crad) {
        return [];
    }

    grantEnsureEvaluationTables($crad);

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId > 0) {
        grantBackfillApplicantDecisionNotifications($crad, $userId);
    }

    try {
        $table = $crad->query("SHOW TABLES LIKE 'grant_proposal_notifications'")->fetchColumn();
        if (!$table) {
            return [];
        }

        if (!function_exists('smsCurrentUserNotificationWhere')) {
            require_once dirname(__DIR__, 3) . '/includes/notifications.php';
        }

        $where = smsCurrentUserNotificationWhere();
        $stmt = $crad->prepare("
            SELECT id, event_key, type, title, body, url, is_read, created_at
            FROM grant_proposal_notifications
            WHERE {$where['sql']}
            ORDER BY created_at DESC, id DESC
            LIMIT :limit
        ");
        foreach ($where['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            $type = (string) ($row['type'] ?? '');
            $icon = match ($type) {
                'grant_rejected'        => 'fa-times-circle',
                'grant_approved_funded' => 'fa-check-circle',
                'grant_fund_release'    => 'fa-money-bill-wave',
                'grant_milestone_update'=> 'fa-tasks',
                'grant_approval_return' => 'fa-undo',
                default                 => 'fa-edit',
            };
            return [
                'id' => -2000000 - (int) ($row['id'] ?? 0),
                'batch_key' => (string) ($row['event_key'] ?? ''),
                'type' => $type,
                'icon' => $icon,
                'status' => ((int) ($row['is_read'] ?? 0) === 1) ? 'read' : 'unread',
                'title' => (string) ($row['title'] ?? 'Grant Proposal Update'),
                'body' => (string) ($row['body'] ?? ''),
                'url' => (string) ($row['url'] ?? grantProposalsApplicationsUrl()),
                'created_at' => (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    } catch (Throwable $e) {
        error_log('grantProposalNotificationsForCurrentUser: ' . $e->getMessage());
        return [];
    }
}

function grantMarkProposalNotificationRead(int $notificationId): void
{
    if ($notificationId >= -2000000) {
        return;
    }

    $grantNotificationId = abs($notificationId) - 2000000;
    if ($grantNotificationId <= 0) {
        return;
    }

    $crad = cradDb();
    if (!$crad) {
        return;
    }

    grantEnsureEvaluationTables($crad);

    try {
        if (!function_exists('smsCurrentUserNotificationWhere')) {
            require_once dirname(__DIR__, 3) . '/includes/notifications.php';
        }
        $where = smsCurrentUserNotificationWhere();
        $stmt = $crad->prepare("
            UPDATE grant_proposal_notifications
               SET is_read = 1
             WHERE id = :notification_id
               AND {$where['sql']}
             LIMIT 1
        ");
        $stmt->bindValue(':notification_id', $grantNotificationId, PDO::PARAM_INT);
        foreach ($where['params'] as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
    } catch (Throwable $e) {
        error_log('grantMarkProposalNotificationRead: ' . $e->getMessage());
    }
}
