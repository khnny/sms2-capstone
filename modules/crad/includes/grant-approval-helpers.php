<?php
/**
 * CRAD Grant Proposal — Sequential approval workflow helpers.
 *
 * Sign-off sequence: Adviser → Dept. Chair → Dean → Research Office → VPAA → Finance
 */
declare(strict_types=1);

require_once __DIR__ . '/grant-helpers.php';
require_once dirname(__DIR__, 3) . '/includes/grant-review-workflow-urls.php';
require_once __DIR__ . '/grant-evaluation-helpers.php';
require_once __DIR__ . '/grant-evaluation-rubric-ui.php';

/**
 * Send approvers to their module shell (Faculty / Accreditation / Payment) when opened via CRAD paths.
 */
function grantRedirectReviewWorkflowShellIfNeeded(string $pageSlug): void
{
    if (defined('SMS2_GRANT_APPROVAL_SHELL_MODULE')) {
        return;
    }

    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';
    $moduleKey = grantReviewWorkflowModuleKeyForRole($roleKey);
    if ($moduleKey === 'crad') {
        return;
    }

    $applicationId = (int) ($_GET['id'] ?? 0);
    $redirect = grantReviewWorkflowPageUrl($pageSlug, $applicationId, $moduleKey);
    header('Location: ' . $redirect);
    exit;
}

/** @return list<array{key: string, order: int, label: string, short: string, role: string}> */
function grantApprovalStepDefinitions(): array
{
    return [
        ['key' => 'adviser',           'order' => 1, 'label' => 'Academic Adviser',    'short' => 'Adviser',          'role' => 'adviser'],
        ['key' => 'department_chair',  'order' => 2, 'label' => 'Dept. Chair',         'short' => 'Dept. Chair',      'role' => 'department_chair'],
        ['key' => 'dean',              'order' => 3, 'label' => 'College Dean',        'short' => 'Dean',             'role' => 'hr'],
        ['key' => 'research_office',   'order' => 4, 'label' => 'Research Office',     'short' => 'Research Office',  'role' => 'research_office'],
        ['key' => 'vpaa',              'order' => 5, 'label' => 'VPAA Sign-off',       'short' => 'VPAA',             'role' => 'vpaa'],
        ['key' => 'finance',           'order' => 6, 'label' => 'Finance Office',      'short' => 'Finance',          'role' => 'finance'],
    ];
}

/** @return array<string, array{key: string, order: int, label: string, short: string, role: string}> */
function grantApprovalStepMap(): array
{
    $map = [];
    foreach (grantApprovalStepDefinitions() as $step) {
        $map[$step['key']] = $step;
    }

    return $map;
}

/** @return list<string> */
function grantApprovalApproverRoleKeys(): array
{
    return array_values(array_unique(array_map(
        static fn(array $step): string => (string) $step['role'],
        grantApprovalStepDefinitions()
    )));
}

function grantApprovalPipelineLabel(): string
{
    $display = [
        'adviser'           => 'Adviser',
        'department_chair'  => 'Department Chair',
        'dean'              => 'Dean',
        'research_office'   => 'Research Office',
        'vpaa'              => 'VPAA',
        'finance'           => 'Finance',
    ];
    $parts = [];
    foreach (grantApprovalStepDefinitions() as $step) {
        $key = (string) ($step['key'] ?? '');
        $parts[] = $display[$key] ?? (string) ($step['short'] ?? $step['label'] ?? $key);
    }

    return implode(' → ', $parts);
}

/** @return list<string> */
function grantApprovalLegacyStepRoleKeys(string $roleKey): array
{
    $legacy = [
        'department_chair' => ['research_coordinator'],
        'research_office'  => ['crad_officer'],
        'vpaa'             => ['qa'],
    ];

    return $legacy[$roleKey] ?? [];
}

/** Role keys that match workflow steps for the logged-in approver (incl. legacy step roles). */
function grantApprovalUserStepRoleKeys(string $userRoleKey): array
{
    return array_values(array_unique(array_merge(
        [$userRoleKey],
        grantApprovalLegacyStepRoleKeys($userRoleKey)
    )));
}

function grantUserCanViewApprovalWorkflow(): bool
{
    if (grantUserCanMonitorApprovalWorkflow()) {
        return true;
    }

    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    if (in_array($roleKey, array_merge(grantApprovalApproverRoleKeys(), ['superadmin']), true)) {
        return true;
    }

    if (!function_exists('smsHasGrantedModuleAdminAccess')) {
        require_once dirname(__DIR__, 3) . '/includes/authentication.php';
    }

    return smsHasGrantedModuleAdminAccess('crad')
        || smsHasGrantedModuleAdminAccess('crad_grant');
}

function grantUserCanMonitorApprovalWorkflow(): bool
{
    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    return in_array($roleKey, ['crad_officer', 'superadmin'], true)
        || (function_exists('smsHasGrantedModuleAdminAccess') && smsHasGrantedModuleAdminAccess('crad'));
}

function grantRequireApprovalAccess(): void
{
    if (grantUserCanViewApprovalWorkflow()) {
        return;
    }

    grantRedirectUnauthorized();
}

function grantApprovalActiveModuleKey(): string
{
    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    return match ($roleKey) {
        'adviser'                => 'faculty',
        'research_coordinator'   => 'crad',
        'department_chair'       => 'crad',
        'hr'                     => 'faculty',
        'qa'                     => 'accreditation',
        'vpaa'                   => 'accreditation',
        'research_office'        => 'crad',
        'finance'                => 'payment',
        default                  => 'crad',
    };
}

function grantApprovalBreadcrumbModuleLabel(): string
{
    return match (grantApprovalActiveModuleKey()) {
        'faculty'        => 'Faculty',
        'accreditation'  => 'QA Office',
        'payment'        => 'Payment Management',
        default          => 'CRAD',
    };
}

function grantApprovalBreadcrumbModuleUrl(): string
{
    return match (grantApprovalActiveModuleKey()) {
        'faculty'        => BASE_URL . '/modules/faculty/pages/approved-research.php',
        'accreditation'  => BASE_URL . '/modules/accreditation/index.php',
        'payment'        => BASE_URL . '/modules/payment/index.php',
        default          => BASE_URL . '/modules/crad/index.php',
    };
}

function grantApprovalWorkflowListUrl(): string
{
    $moduleKey = defined('SMS2_GRANT_APPROVAL_SHELL_MODULE')
        ? (string) SMS2_GRANT_APPROVAL_SHELL_MODULE
        : grantApprovalActiveModuleKey();

    return grantReviewWorkflowPageUrl('approval-workflows', 0, $moduleKey);
}

/**
 * Notify Finance officers when VPAA approves and funding sign-off is pending.
 *
 * @param array<string, mixed> $application
 */
function grantNotifyFinancePendingSignoff(PDO $crad, array $application): void
{
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

    $financeUsers = $mainDb->query("
        SELECT id FROM users WHERE role_key = 'finance' AND status = 'active'
    ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($financeUsers === []) {
        return;
    }

    $ref = trim((string) ($application['proposal_reference'] ?? ''));
    $refLabel = $ref !== '' ? $ref : ('Proposal #' . $applicationId);
    $title = trim((string) ($application['research_title'] ?? 'Research proposal'));
    $url = grantReviewWorkflowPageUrl('approval-workflows', $applicationId, 'payment');
    $body = sprintf(
        '%s is pending Finance Office final approval after VPAA sign-off. Review it under Payment Management → Approval Workflows.',
        $refLabel
    );
    if ($title !== '') {
        $body .= ' Title: ' . mb_strimwidth($title, 0, 120, '…');
    }

    foreach ($financeUsers as $row) {
        $userId = (int) ($row['id'] ?? 0);
        if ($userId <= 0) {
            continue;
        }

        grantInsertGrantProposalNotification(
            $crad,
            $applicationId,
            $userId,
            'finance',
            'grant_finance_pending',
            'grant-proposal:finance_pending:' . $applicationId . ':u' . $userId,
            'Pending Finance Approval',
            $body,
            $url
        );
    }
}

function grantApprovalRoleLabel(string $roleKey): string
{
    return match ($roleKey) {
        'adviser'              => 'Academic Adviser',
        'department_chair'     => 'Department Chair',
        'research_coordinator' => 'Research Coordinator',
        'hr'                   => 'College Dean',
        'research_office'      => 'Research Office',
        'finance'              => 'Finance Office',
        'crad_officer'         => 'CRAD Officer',
        'vpaa'                 => 'VPAA',
        'qa'                   => 'QA Office',
        'superadmin'           => 'Administrator',
        default                => ucwords(str_replace('_', ' ', $roleKey)),
    };
}

function grantUserCanActOnApprovalStep(string $stepRoleKey): bool
{
    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';

    if ($roleKey === 'superadmin') {
        return true;
    }

    return in_array($stepRoleKey, grantApprovalUserStepRoleKeys($roleKey), true);
}

/**
 * Institutional label shown when a proposal is returned (e.g. "College Dean").
 *
 * @param array<string, mixed> $step
 */
function grantApprovalReturnedByLabel(array $step): string
{
    $label = trim((string) ($step['step_label'] ?? ''));
    if ($label !== '') {
        return $label;
    }

    return grantApprovalRoleLabel((string) ($step['approver_role_key'] ?? ''));
}

/**
 * @param array<string, mixed> $step
 * @return array<string, mixed>|null
 */
function grantBuildApprovalReturnRecord(array $step): ?array
{
    if ((string) ($step['status'] ?? '') !== 'Returned') {
        return null;
    }

    $actedAt = (string) ($step['acted_at'] ?? '');
    $displayAt = '';
    if ($actedAt !== '') {
        $ts = strtotime($actedAt);
        $displayAt = $ts !== false ? date('M d, Y g:i A', $ts) : $actedAt;
    }

    return [
        'returned_by'         => grantApprovalReturnedByLabel($step),
        'approval_level'      => (int) ($step['step_order'] ?? 0),
        'reason'              => trim((string) ($step['remarks'] ?? '')),
        'returned_at'         => $actedAt,
        'returned_at_display' => $displayAt,
    ];
}

function grantEnsureApprovalTables(PDO $crad): void
{
    grantEnsureTables($crad);

    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_proposal_approval_workflows (
            id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            grant_application_id  INT UNSIGNED NOT NULL,
            current_step_key      VARCHAR(40)  NOT NULL DEFAULT 'adviser',
            workflow_status       ENUM('In Progress','Completed','Returned') NOT NULL DEFAULT 'In Progress',
            started_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at          DATETIME     DEFAULT NULL,
            updated_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_gpaw_application (grant_application_id),
            KEY idx_gpaw_status (workflow_status),
            KEY idx_gpaw_current_step (current_step_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $crad->exec("
        CREATE TABLE IF NOT EXISTS grant_proposal_approval_steps (
            id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
            workflow_id           INT UNSIGNED NOT NULL,
            grant_application_id  INT UNSIGNED NOT NULL,
            step_key              VARCHAR(40)  NOT NULL,
            step_order            TINYINT UNSIGNED NOT NULL,
            step_label            VARCHAR(80)  NOT NULL,
            approver_role_key     VARCHAR(40)  NOT NULL,
            status                ENUM('Queued','Pending','Approved','Returned') NOT NULL DEFAULT 'Queued',
            approver_user_id      INT UNSIGNED DEFAULT NULL,
            approver_name         VARCHAR(150) DEFAULT NULL,
            remarks               TEXT         DEFAULT NULL,
            signature_data        MEDIUMTEXT   DEFAULT NULL,
            acted_at              DATETIME     DEFAULT NULL,
            created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_gpas_workflow_step (workflow_id, step_key),
            KEY idx_gpas_application (grant_application_id),
            KEY idx_gpas_status (status),
            KEY idx_gpas_role (approver_role_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    grantMigrateEnsureFinanceApprovalStep($crad);
}

/**
 * Ensure VPAA (order 5) and Finance (order 6) steps exist in the correct sequence.
 */
function grantMigrateEnsureFinanceApprovalStep(PDO $crad): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $workflows = $crad->query("
            SELECT id, grant_application_id, current_step_key, workflow_status
              FROM grant_proposal_approval_workflows
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $hasStep = $crad->prepare(
            "SELECT id FROM grant_proposal_approval_steps WHERE workflow_id = ? AND step_key = ? LIMIT 1"
        );
        $stepStatuses = $crad->prepare(
            "SELECT step_key, status FROM grant_proposal_approval_steps
              WHERE workflow_id = ? AND step_key IN ('research_office', 'vpaa', 'finance')"
        );
        $insertStep = $crad->prepare("
            INSERT INTO grant_proposal_approval_steps
                (workflow_id, grant_application_id, step_key, step_order, step_label,
                 approver_role_key, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $normalizeOrder = $crad->prepare("
            UPDATE grant_proposal_approval_steps
               SET step_order = ?, step_label = ?, updated_at = NOW()
             WHERE workflow_id = ? AND step_key = ?
        ");

        foreach ($workflows as $workflow) {
            $workflowId = (int) ($workflow['id'] ?? 0);
            $appId      = (int) ($workflow['grant_application_id'] ?? 0);
            if ($workflowId <= 0 || $appId <= 0) {
                continue;
            }

            $stepStatuses->execute([$workflowId]);
            $statusByKey = [];
            foreach ($stepStatuses->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $statusByKey[(string) ($row['step_key'] ?? '')] = (string) ($row['status'] ?? '');
            }

            $hasStep->execute([$workflowId, 'vpaa']);
            if (!$hasStep->fetch()) {
                $insertStep->execute([$workflowId, $appId, 'vpaa', 5, 'VPAA Sign-off', 'vpaa', 'Queued']);
            } else {
                $normalizeOrder->execute([5, 'VPAA Sign-off', $workflowId, 'vpaa']);
            }

            $hasStep->execute([$workflowId, 'finance']);
            if (!$hasStep->fetch()) {
                $roStatus   = $statusByKey['research_office'] ?? 'Queued';
                $vpaaStatus = $statusByKey['vpaa'] ?? 'Queued';
                $wfStatus   = (string) ($workflow['workflow_status'] ?? '');

                if ($wfStatus === 'Completed') {
                    $financeStatus = 'Approved';
                } elseif ($vpaaStatus === 'Approved') {
                    $financeStatus = in_array((string) ($workflow['current_step_key'] ?? ''), ['finance'], true)
                        ? 'Pending' : 'Queued';
                } elseif ($roStatus === 'Approved') {
                    $financeStatus = 'Queued';
                } else {
                    $financeStatus = 'Queued';
                }

                $insertStep->execute([$workflowId, $appId, 'finance', 6, 'Finance Office', 'finance', $financeStatus]);
            } else {
                $normalizeOrder->execute([6, 'Finance Office', $workflowId, 'finance']);
            }

            grantFixApprovalWorkflowStepPointer($crad, $workflowId, $workflow);
        }
    } catch (Throwable $e) {
        error_log('grantMigrateEnsureFinanceApprovalStep: ' . $e->getMessage());
    }
}

/**
 * Correct in-progress workflows where Finance was queued before VPAA.
 */
function grantFixApprovalWorkflowStepPointer(PDO $crad, int $workflowId, array $workflow): void
{
    $wfStatus = (string) ($workflow['workflow_status'] ?? '');
    $appId    = (int) ($workflow['grant_application_id'] ?? 0);

    $stmt = $crad->prepare("
        SELECT step_key, status
          FROM grant_proposal_approval_steps
         WHERE workflow_id = ?
           AND step_key IN ('research_office', 'vpaa', 'finance')
    ");
    $stmt->execute([$workflowId]);
    $statusByKey = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $statusByKey[(string) ($row['step_key'] ?? '')] = (string) ($row['status'] ?? '');
    }

    $roStatus      = $statusByKey['research_office'] ?? 'Queued';
    $vpaaStatus    = $statusByKey['vpaa'] ?? 'Queued';
    $financeStatus = $statusByKey['finance'] ?? 'Queued';
    $currentStep   = (string) ($workflow['current_step_key'] ?? '');

    if ($wfStatus === 'Completed' && $vpaaStatus === 'Approved' && $financeStatus !== 'Approved') {
        $crad->prepare("
            UPDATE grant_proposal_approval_workflows
               SET workflow_status = 'In Progress',
                   current_step_key = 'finance',
                   completed_at = NULL,
                   updated_at = NOW()
             WHERE id = ?
        ")->execute([$workflowId]);
        $crad->prepare("
            UPDATE grant_proposal_approval_steps
               SET status = 'Pending', updated_at = NOW()
             WHERE workflow_id = ? AND step_key = 'finance' AND status != 'Approved'
        ")->execute([$workflowId]);
        if ($appId > 0) {
            $crad->prepare("
                UPDATE grant_applications
                   SET status = 'Under Review', updated_at = NOW()
                 WHERE id = ? AND status = 'Approved'
            ")->execute([$appId]);
        }

        return;
    }

    if ($wfStatus !== 'In Progress') {
        return;
    }

    if ($currentStep === 'finance' && $vpaaStatus !== 'Approved') {
        $crad->prepare("
            UPDATE grant_proposal_approval_workflows
               SET current_step_key = 'vpaa', updated_at = NOW()
             WHERE id = ?
        ")->execute([$workflowId]);
        if ($roStatus === 'Approved' && in_array($vpaaStatus, ['Queued', 'Pending'], true)) {
            $crad->prepare("
                UPDATE grant_proposal_approval_steps
                   SET status = 'Pending', updated_at = NOW()
                 WHERE workflow_id = ? AND step_key = 'vpaa'
            ")->execute([$workflowId]);
        }
        if ($financeStatus === 'Pending') {
            $crad->prepare("
                UPDATE grant_proposal_approval_steps
                   SET status = 'Queued', updated_at = NOW()
                 WHERE workflow_id = ? AND step_key = 'finance'
            ")->execute([$workflowId]);
        }

        return;
    }

    if ($vpaaStatus === 'Approved'
        && in_array($financeStatus, ['Queued', 'Pending'], true)
        && $currentStep === 'vpaa') {
        $crad->prepare("
            UPDATE grant_proposal_approval_workflows
               SET current_step_key = 'finance', updated_at = NOW()
             WHERE id = ?
        ")->execute([$workflowId]);
        if ($financeStatus === 'Queued') {
            $crad->prepare("
                UPDATE grant_proposal_approval_steps
                   SET status = 'Pending', updated_at = NOW()
                 WHERE workflow_id = ? AND step_key = 'finance'
            ")->execute([$workflowId]);
        }
    }
}

/**
 * @deprecated Finance step is required again — kept as no-op for older callers.
 */
function grantMigrateRemoveFinanceApprovalStep(PDO $crad): void
{
    grantMigrateEnsureFinanceApprovalStep($crad);
}

function grantApprovalWorkflowUrl(int $applicationId = 0): string
{
    $url = grantApprovalWorkflowListUrl();
    if ($applicationId > 0) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'id=' . $applicationId;
    }

    return $url;
}

/**
 * @return array{ok: bool, workflow_id?: int, error?: string}
 */
function grantStartApprovalWorkflow(PDO $crad, int $applicationId): array
{
    grantEnsureApprovalTables($crad);

    if ($applicationId <= 0) {
        return ['ok' => false, 'error' => 'Invalid application.'];
    }

    $existing = grantGetApprovalWorkflowByApplicationId($crad, $applicationId);
    if ($existing !== null) {
        return ['ok' => true, 'workflow_id' => (int) ($existing['id'] ?? 0)];
    }

    $app = grantGetApplicationForEvaluation($crad, $applicationId);
    if ($app === null) {
        return ['ok' => false, 'error' => 'Proposal not found.'];
    }

    try {
        $crad->beginTransaction();

        $crad->prepare("
            INSERT INTO grant_proposal_approval_workflows
                (grant_application_id, current_step_key, workflow_status, started_at, updated_at)
            VALUES (?, 'adviser', 'In Progress', NOW(), NOW())
        ")->execute([$applicationId]);
        $workflowId = (int) $crad->lastInsertId();

        $insertStep = $crad->prepare("
            INSERT INTO grant_proposal_approval_steps
                (workflow_id, grant_application_id, step_key, step_order, step_label,
                 approver_role_key, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        foreach (grantApprovalStepDefinitions() as $step) {
            $status = $step['order'] === 1 ? 'Pending' : 'Queued';
            $insertStep->execute([
                $workflowId,
                $applicationId,
                $step['key'],
                $step['order'],
                $step['label'],
                $step['role'],
                $status,
            ]);
        }

        $crad->prepare("
            UPDATE grant_applications
               SET status = 'Under Review', updated_at = NOW()
             WHERE id = ?
        ")->execute([$applicationId]);

        $crad->commit();

        return ['ok' => true, 'workflow_id' => $workflowId];
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantStartApprovalWorkflow: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to start approval workflow.'];
    }
}

function grantBackfillApprovalWorkflows(PDO $crad): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    grantEnsureApprovalTables($crad);
    grantEnsureEvaluationTables($crad);

    try {
        $committeeType = grantEvaluationTypeCommittee();
        $stmt = $crad->prepare("
            SELECT DISTINCT ga.id
              FROM grant_applications ga
              JOIN grant_proposal_evaluations e
                ON e.grant_application_id = ga.id
               AND e.recommendation = 'recommend'
               AND e.evaluation_type = ?
               AND e.proposal_version = COALESCE(NULLIF(ga.current_version, 0), 1)
               AND e.id = (
                    SELECT MAX(e2.id)
                      FROM grant_proposal_evaluations e2
                     WHERE e2.grant_application_id = ga.id
                       AND e2.evaluation_type = ?
                       AND e2.proposal_version = COALESCE(NULLIF(ga.current_version, 0), 1)
               )
              LEFT JOIN grant_proposal_approval_workflows w
                ON w.grant_application_id = ga.id
             WHERE w.id IS NULL
               AND ga.status NOT IN ('Rejected', 'Revision Required', 'Denied', 'Withdrawn', 'Approved & Funded')
        ");
        $stmt->execute([$committeeType, $committeeType]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        foreach ($rows as $appId) {
            grantStartApprovalWorkflow($crad, (int) $appId);
        }
    } catch (Throwable $e) {
        error_log('grantBackfillApprovalWorkflows: ' . $e->getMessage());
    }
}

function grantGetApprovalWorkflowByApplicationId(PDO $crad, int $applicationId): ?array
{
    grantEnsureApprovalTables($crad);

    $stmt = $crad->prepare("
        SELECT w.*,
               ga.proposal_reference,
               ga.research_title,
               ga.applicant_name,
               ga.college_dept,
               ga.requested_budget,
               ga.status AS application_status,
               ga.current_version,
               go.funding_title
          FROM grant_proposal_approval_workflows w
          JOIN grant_applications ga ON ga.id = w.grant_application_id
          LEFT JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
         WHERE w.grant_application_id = ?
         LIMIT 1
    ");
    $stmt->execute([$applicationId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * @return list<array<string, mixed>>
 */
function grantGetApprovalSteps(PDO $crad, int $workflowId): array
{
    grantEnsureApprovalTables($crad);

    $stmt = $crad->prepare("
        SELECT *
          FROM grant_proposal_approval_steps
         WHERE workflow_id = ?
         ORDER BY step_order ASC
    ");
    $stmt->execute([$workflowId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @return array<string, mixed>|null
 */
function grantGetApprovalWorkflowDetail(PDO $crad, int $applicationId): ?array
{
    $workflow = grantGetApprovalWorkflowByApplicationId($crad, $applicationId);
    if ($workflow === null) {
        return null;
    }

    $steps = grantGetApprovalSteps($crad, (int) ($workflow['id'] ?? 0));
    $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';
    $currentStepKey = (string) ($workflow['current_step_key'] ?? '');
    $canAct = false;
    $canReturn = false;
    $currentStep = null;

    foreach ($steps as $step) {
        if ((string) ($step['step_key'] ?? '') === $currentStepKey) {
            $currentStep = $step;
            $atCurrentStep = ((string) ($workflow['workflow_status'] ?? '') === 'In Progress')
                && in_array((string) ($step['status'] ?? ''), ['Pending', 'Queued'], true)
                && grantUserCanActOnApprovalStep((string) ($step['approver_role_key'] ?? ''));
            $canReturn = $atCurrentStep;
            $canAct = $atCurrentStep;
            if ($canAct && $currentStepKey === 'adviser' && $roleKey === 'adviser') {
                $userId = (int) ($_SESSION['user_id'] ?? 0);
                $canAct = grantHasAdviserEvaluation($crad, $applicationId, $userId);
            }
            $approverStepKey = grantApprovalStepKeyForRole($roleKey);
            if ($canAct
                && $approverStepKey !== null
                && $approverStepKey !== ''
                && $approverStepKey !== 'adviser'
                && $currentStepKey === $approverStepKey) {
                $userId = (int) ($_SESSION['user_id'] ?? 0);
                $canAct = grantHasApproverStepEvaluation($crad, $applicationId, $userId, $roleKey);
            }
            break;
        }
    }

    $adviserEvalComplete = false;
    $approverEvalComplete = false;
    $needsAdviserScore = false;
    $needsRubricScore = false;
    $committeeEval = null;
    $adviserEval = null;
    $pipelineEvals = grantGetPipelineEvaluationsForApplication($crad, $applicationId);
    $pipelineScorePills = grantFormatPipelineScorePills($pipelineEvals);
    $committeeEval = $pipelineEvals['committee'] ?? null;
    $adviserEval = $pipelineEvals['adviser'] ?? null;
    $monitorStageHint = '';
    $isMonitor = grantUserCanMonitorApprovalWorkflow();
    $approverStepKey = grantApprovalStepKeyForRole($roleKey);

    if ($currentStepKey === 'adviser') {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $adviserEvalComplete = grantHasAdviserEvaluation($crad, $applicationId, $userId);
        $needsAdviserScore = $roleKey === 'adviser'
            && !$adviserEvalComplete
            && (string) ($workflow['workflow_status'] ?? '') === 'In Progress';
        $needsRubricScore = $needsAdviserScore;
    } elseif ($approverStepKey !== null
        && $approverStepKey !== ''
        && $approverStepKey !== 'adviser'
        && $currentStepKey === $approverStepKey
        && (string) ($workflow['workflow_status'] ?? '') === 'In Progress'
        && grantUserCanActOnApprovalStep($roleKey)) {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $approverEvalComplete = grantHasApproverStepEvaluation($crad, $applicationId, $userId, $roleKey);
        $needsRubricScore = !$approverEvalComplete;
        $needsAdviserScore = false;
    }

    if ($isMonitor) {
        $wfStatus = (string) ($workflow['workflow_status'] ?? '');
        if ($wfStatus === 'Completed') {
            $monitorStageHint = 'All institutional sign-offs completed for this proposal.';
        } elseif ($currentStepKey === 'adviser') {
            if ($adviserEval) {
                $score = number_format((float) ($adviserEval['total_score'] ?? 0), 1);
                $monitorStageHint = "Adviser scored {$score}/100 — awaiting administrative sign-off.";
            } else {
                $monitorStageHint = 'Awaiting Academic Adviser rubric score and sign-off.';
            }
        } elseif ($currentStep !== null) {
            $stepEval = grantGetEvaluationByTypeAndApplication($crad, $applicationId, $currentStepKey);
            if ($stepEval) {
                $score = number_format((float) ($stepEval['total_score'] ?? 0), 1);
                $monitorStageHint = grantEvaluationStepLabel($currentStepKey)
                    . " scored {$score}/100 — awaiting administrative sign-off.";
            } else {
                $monitorStageHint = 'Awaiting '
                    . grantEvaluationStepLabel($currentStepKey)
                    . ' rubric score before sign-off.';
            }
        }
    }

    $returnRecord = null;
    if ((string) ($workflow['workflow_status'] ?? '') === 'Returned' && $currentStep !== null) {
        $returnRecord = grantBuildApprovalReturnRecord($currentStep);
    }

    return [
        'workflow'               => $workflow,
        'steps'                  => $steps,
        'current_step'           => $currentStep,
        'can_act'                => $canAct,
        'can_return'             => $canReturn,
        'return_record'          => $returnRecord,
        'adviser_eval_complete'  => $adviserEvalComplete,
        'approver_eval_complete' => $approverEvalComplete,
        'needs_adviser_score'    => $needsAdviserScore,
        'needs_rubric_score'     => $needsRubricScore,
        'role_label'             => grantApprovalRoleLabel($roleKey),
        'current_approver_label' => grantApprovalRoleLabel((string) ($currentStep['approver_role_key'] ?? '')),
        'is_monitor'             => $isMonitor,
        'committee_eval'         => $committeeEval,
        'adviser_eval'           => $adviserEval,
        'pipeline_evals'         => $pipelineEvals,
        'pipeline_score_pills'   => $pipelineScorePills,
        'monitor_stage_hint'     => $monitorStageHint,
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function grantApprovalWorkflowList(PDO $crad): array
{
    grantEnsureApprovalTables($crad);
    grantBackfillApprovalWorkflows($crad);

    $roleKey   = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';
    $isMonitor = grantUserCanMonitorApprovalWorkflow();

    $sql = "
        SELECT w.*,
               ga.proposal_reference,
               ga.research_title,
               ga.applicant_name,
               ga.college_dept,
               ga.requested_budget,
               ga.status AS application_status,
               ga.current_version,
               go.funding_title,
               cs.step_label AS current_step_label,
               cs.approver_role_key AS current_approver_role
          FROM grant_proposal_approval_workflows w
          JOIN grant_applications ga ON ga.id = w.grant_application_id
          LEFT JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
          LEFT JOIN grant_proposal_approval_steps cs
            ON cs.workflow_id = w.id AND cs.step_key = w.current_step_key
    ";

    $params = [];
    if (!$isMonitor) {
        $stepRoleKeys = grantApprovalUserStepRoleKeys($roleKey);
        $placeholders = implode(',', array_fill(0, count($stepRoleKeys), '?'));
        $sql .= " WHERE cs.approver_role_key IN ({$placeholders}) AND w.workflow_status = 'In Progress'";
        $params = $stepRoleKeys;

        if ($roleKey === 'finance') {
            $sql .= " AND w.current_step_key = 'finance'
                      AND cs.step_key = 'finance'
                      AND cs.status = 'Pending'
                      AND EXISTS (
                            SELECT 1
                              FROM grant_proposal_approval_steps vp
                             WHERE vp.workflow_id = w.id
                               AND vp.step_key = 'vpaa'
                               AND vp.status = 'Approved'
                      )";
        }
    } else {
        $sql .= " WHERE 1=1";
    }

    $sql .= " ORDER BY w.updated_at DESC, w.id DESC";

    $stmt = $crad->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function grantApprovalWorkflowFingerprint(array $workflows): string
{
    $parts = [];
    foreach ($workflows as $row) {
        $parts[] = implode(':', [
            $row['id'] ?? '',
            $row['grant_application_id'] ?? '',
            $row['current_step_key'] ?? '',
            $row['workflow_status'] ?? '',
            $row['updated_at'] ?? '',
            $row['application_status'] ?? '',
        ]);
    }

    return md5(implode('|', $parts));
}

/**
 * Fingerprint for the selected workflow detail (step statuses + action state).
 */
function grantApprovalDetailFingerprint(?array $detail): string
{
    if ($detail === null || empty($detail['workflow'])) {
        return '';
    }

    $wf = $detail['workflow'];
    $parts = [
        (string) ($wf['current_step_key'] ?? ''),
        (string) ($wf['workflow_status'] ?? ''),
        (string) ($wf['updated_at'] ?? ''),
        !empty($detail['can_act']) ? '1' : '0',
        !empty($detail['can_return']) ? '1' : '0',
        !empty($detail['adviser_eval_complete']) ? '1' : '0',
        !empty($detail['approver_eval_complete']) ? '1' : '0',
        !empty($detail['needs_adviser_score']) ? '1' : '0',
        !empty($detail['needs_rubric_score']) ? '1' : '0',
    ];

    foreach ($detail['steps'] ?? [] as $step) {
        $parts[] = implode(':', [
            $step['step_key'] ?? '',
            $step['status'] ?? '',
            $step['acted_at'] ?? '',
        ]);
    }

    $parts[] = (string) ($detail['monitor_stage_hint'] ?? '');
    foreach ($detail['pipeline_score_pills'] ?? [] as $pill) {
        $parts[] = ($pill['type'] ?? '') . ':' . ($pill['total_score'] ?? '');
    }
    if (!empty($detail['committee_eval']['total_score'])) {
        $parts[] = 'c:' . $detail['committee_eval']['total_score'];
    }
    if (!empty($detail['adviser_eval']['total_score'])) {
        $parts[] = 'a:' . $detail['adviser_eval']['total_score'];
    }
    if (!empty($detail['return_record']) && is_array($detail['return_record'])) {
        $rr = $detail['return_record'];
        $parts[] = 'ret:' . implode(':', [
            $rr['returned_by'] ?? '',
            $rr['approval_level'] ?? '',
            $rr['reason'] ?? '',
            $rr['returned_at'] ?? '',
        ]);
    }

    return md5(implode('|', $parts));
}

/**
 * @return array{ok: bool, error?: string, completed?: bool}
 */
function grantSubmitApprovalSignoff(
    PDO $crad,
    int $applicationId,
    int $userId,
    string $userName,
    ?string $signatureData = null,
    ?string $remarks = null
): array {
    grantEnsureApprovalTables($crad);

    $detail = grantGetApprovalWorkflowDetail($crad, $applicationId);
    if ($detail === null) {
        return ['ok' => false, 'error' => 'Approval workflow not found.'];
    }

    if (empty($detail['can_act'])) {
        $roleKey = function_exists('getCurrentUserRoleKey') ? getCurrentUserRoleKey() : '';
        $stepKey = (string) ($detail['current_step']['step_key'] ?? '');
        if (!empty($detail['needs_rubric_score']) || !empty($detail['needs_adviser_score'])) {
            return [
                'ok'    => false,
                'error' => 'Complete your '
                    . grantEvaluationStepLabel($stepKey !== '' ? $stepKey : ($roleKey === 'adviser' ? 'adviser' : 'committee'))
                    . ' Evaluation score before signing off.',
            ];
        }

        return ['ok' => false, 'error' => 'You are not authorized to sign off at this stage.'];
    }

    $workflow    = $detail['workflow'];
    $currentStep = $detail['current_step'];
    if ($currentStep === null) {
        return ['ok' => false, 'error' => 'No active approval step.'];
    }

    if ($signatureData !== null && $signatureData !== '' && !str_starts_with($signatureData, 'data:image/png;base64,')) {
        return ['ok' => false, 'error' => 'Invalid signature format.'];
    }

    $workflowId     = (int) ($workflow['id'] ?? 0);
    $stepKey        = (string) ($currentStep['step_key'] ?? '');
    $stepOrder      = (int) ($currentStep['step_order'] ?? 0);
    $definitions    = grantApprovalStepDefinitions();
    $nextStep       = null;
    foreach ($definitions as $def) {
        if ($def['order'] === $stepOrder + 1) {
            $nextStep = $def;
            break;
        }
    }

    try {
        $crad->beginTransaction();

        $crad->prepare("
            UPDATE grant_proposal_approval_steps
               SET status = 'Approved',
                   approver_user_id = ?,
                   approver_name = ?,
                   remarks = ?,
                   signature_data = ?,
                   acted_at = NOW(),
                   updated_at = NOW()
             WHERE workflow_id = ? AND step_key = ?
        ")->execute([
            $userId > 0 ? $userId : null,
            $userName,
            $remarks !== '' ? $remarks : null,
            $signatureData,
            $workflowId,
            $stepKey,
        ]);

        if ($nextStep === null) {
            $crad->prepare("
                UPDATE grant_proposal_approval_workflows
                   SET workflow_status = 'Completed',
                       completed_at = NOW(),
                       updated_at = NOW()
                 WHERE id = ?
            ")->execute([$workflowId]);

            $finalStatus = grantStatusApprovedFunded();
            $crad->prepare("
                UPDATE grant_applications
                   SET status = ?, updated_at = NOW()
                 WHERE id = ?
            ")->execute([$finalStatus, $applicationId]);

            $app = grantGetApplicationForEvaluation($crad, $applicationId);
            if ($app !== null) {
                require_once __DIR__ . '/grant-funding-helpers.php';
                require_once __DIR__ . '/grant-milestone-helpers.php';
                grantInitializeFundingDisbursementPlan($crad, $applicationId);
                grantInitializeFundedProjectMilestones($crad, $applicationId);
                grantNotifyApplicantApprovedFunded($crad, $app, $userName);
            }

            $crad->commit();

            return ['ok' => true, 'completed' => true, 'funded' => true];
        }

        $crad->prepare("
            UPDATE grant_proposal_approval_workflows
               SET current_step_key = ?, updated_at = NOW()
             WHERE id = ?
        ")->execute([$nextStep['key'], $workflowId]);

        $crad->prepare("
            UPDATE grant_proposal_approval_steps
               SET status = 'Pending', updated_at = NOW()
             WHERE workflow_id = ? AND step_key = ?
        ")->execute([$workflowId, $nextStep['key']]);

        if (($nextStep['key'] ?? '') === 'finance') {
            $app = grantGetApplicationForEvaluation($crad, $applicationId);
            if ($app !== null) {
                grantNotifyFinancePendingSignoff($crad, $app);
            }
        }

        $crad->commit();

        return ['ok' => true, 'completed' => false, 'next_step' => $nextStep['key']];
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantSubmitApprovalSignoff: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to record approval sign-off.'];
    }
}

/**
 * @return array{ok: bool, error?: string}
 */
function grantReturnProposalFromApproval(
    PDO $crad,
    int $applicationId,
    int $userId,
    string $userName,
    string $remarks
): array {
    grantEnsureApprovalTables($crad);

    $remarks = trim($remarks);
    if ($remarks === '') {
        return ['ok' => false, 'error' => 'Remarks are required when returning a proposal for revision.'];
    }

    $detail = grantGetApprovalWorkflowDetail($crad, $applicationId);
    if ($detail === null) {
        return ['ok' => false, 'error' => 'Approval workflow not found.'];
    }

    if (empty($detail['can_return'])) {
        return ['ok' => false, 'error' => 'You are not authorized to return this proposal.'];
    }

    $workflow    = $detail['workflow'];
    $currentStep = $detail['current_step'];
    if ($currentStep === null) {
        return ['ok' => false, 'error' => 'No active approval step.'];
    }

    $workflowId = (int) ($workflow['id'] ?? 0);
    $stepKey    = (string) ($currentStep['step_key'] ?? '');
    $returnedByLabel = grantApprovalReturnedByLabel($currentStep);

    try {
        $crad->beginTransaction();

        $crad->prepare("
            UPDATE grant_proposal_approval_steps
               SET status = 'Returned',
                   approver_user_id = ?,
                   approver_name = ?,
                   remarks = ?,
                   acted_at = NOW(),
                   updated_at = NOW()
             WHERE workflow_id = ? AND step_key = ?
        ")->execute([$userId > 0 ? $userId : null, $returnedByLabel, $remarks, $workflowId, $stepKey]);

        $crad->prepare("
            UPDATE grant_proposal_approval_workflows
               SET workflow_status = 'Returned', updated_at = NOW()
             WHERE id = ?
        ")->execute([$workflowId]);

        $crad->prepare("
            UPDATE grant_applications
               SET status = 'Revision Required', updated_at = NOW()
             WHERE id = ?
        ")->execute([$applicationId]);

        $app = grantGetApplicationForEvaluation($crad, $applicationId);
        if ($app !== null) {
            grantNotifyApplicantApprovalReturn(
                $crad,
                $app,
                $currentStep,
                $returnedByLabel,
                $remarks
            );
        }

        $crad->commit();

        return ['ok' => true];
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('grantReturnProposalFromApproval: ' . $e->getMessage());

        return ['ok' => false, 'error' => 'Failed to return proposal for revision.'];
    }
}

/**
 * Remove a returned/completed workflow so resubmit can restart the committee → approval loop.
 */
function grantClearApprovalWorkflowForResubmit(PDO $crad, int $applicationId): void
{
    grantEnsureApprovalTables($crad);

    $workflow = grantGetApprovalWorkflowByApplicationId($crad, $applicationId);
    if ($workflow === null) {
        return;
    }

    $workflowId = (int) ($workflow['id'] ?? 0);
    if ($workflowId <= 0) {
        return;
    }

    $crad->prepare('DELETE FROM grant_proposal_approval_steps WHERE workflow_id = ?')->execute([$workflowId]);
    $crad->prepare('DELETE FROM grant_proposal_approval_workflows WHERE id = ?')->execute([$workflowId]);
}

/**
 * @param  array<int, int> $applicationIds
 * @return array<int, array<string, mixed>>
 */
function grantGetLatestApprovalReturnsForApplications(PDO $crad, array $applicationIds): array
{
    grantEnsureApprovalTables($crad);

    $applicationIds = array_values(array_filter(array_map('intval', $applicationIds), static fn(int $id): bool => $id > 0));
    if ($applicationIds === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($applicationIds), '?'));
    $stmt = $crad->prepare("
        SELECT s.*
          FROM grant_proposal_approval_steps s
         INNER JOIN (
                SELECT grant_application_id, MAX(id) AS max_id
                  FROM grant_proposal_approval_steps
                 WHERE grant_application_id IN ({$placeholders})
                   AND status = 'Returned'
                 GROUP BY grant_application_id
         ) latest ON latest.max_id = s.id
    ");
    $stmt->execute($applicationIds);

    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $map[(int) ($row['grant_application_id'] ?? 0)] = $row;
    }

    return $map;
}

/**
 * @return array<int, array<string, mixed>>
 */
function grantGetApprovedFundedApplications(PDO $crad): array
{
    grantEnsureTables($crad);

    $stmt = $crad->prepare("
        SELECT ga.*, go.funding_title, go.max_funding_cap
          FROM grant_applications ga
         INNER JOIN grant_opportunities go ON go.id = ga.grant_opportunity_id
         WHERE ga.status = ?
         ORDER BY ga.updated_at DESC, ga.id DESC
    ");
    $stmt->execute([grantStatusApprovedFunded()]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Dashboard / monitor summary counts for CRAD Officer.
 *
 * @return array{submitted: int, in_progress: int, completed: int, committee_scored: int}
 */
function grantApprovalDashboardStats(PDO $crad): array
{
    grantEnsureApprovalTables($crad);
    grantBackfillApprovalWorkflows($crad);
    grantEnsureEvaluationTables($crad);

    $submitted = (int) $crad->query("
        SELECT COUNT(*)
          FROM grant_applications
         WHERE status IN ('Submitted', 'Under Review', 'Approved')
    ")->fetchColumn();

    $inProgress = (int) $crad->query("
        SELECT COUNT(*) FROM grant_proposal_approval_workflows WHERE workflow_status = 'In Progress'
    ")->fetchColumn();

    $completed = (int) $crad->query("
        SELECT COUNT(*) FROM grant_proposal_approval_workflows WHERE workflow_status = 'Completed'
    ")->fetchColumn();

    $committeeType = grantEvaluationTypeCommittee();
    $stmt = $crad->prepare("
        SELECT COUNT(DISTINCT grant_application_id)
          FROM grant_proposal_evaluations
         WHERE evaluation_type = ?
           AND recommendation = 'recommend'
    ");
    $stmt->execute([$committeeType]);
    $committeeScored = (int) $stmt->fetchColumn();

    return [
        'submitted'         => $submitted,
        'in_progress'     => $inProgress,
        'completed'       => $completed,
        'committee_scored'=> $committeeScored,
    ];
}

/**
 * UI helper: map step row to display state for the stepper.
 *
 * @return array{state: string, label: string, sub: string, date: string}
 */
function grantApprovalStepDisplayState(array $step, string $currentStepKey, string $workflowStatus): array
{
    $status  = (string) ($step['status'] ?? 'Queued');
    $stepKey = (string) ($step['step_key'] ?? '');
    $actedAt = (string) ($step['acted_at'] ?? '');

    if ($status === 'Approved') {
        $date = $actedAt !== '' ? date('M j, Y', strtotime($actedAt)) : '';

        return ['state' => 'approved', 'label' => 'Approved', 'sub' => '', 'date' => $date];
    }

    if ($status === 'Returned') {
        $date = '';
        if ($actedAt !== '') {
            $ts = strtotime($actedAt);
            $date = $ts !== false ? date('M j, Y g:i A', $ts) : $actedAt;
        }

        return ['state' => 'returned', 'label' => 'Returned', 'sub' => '', 'date' => $date];
    }

    if ($workflowStatus !== 'In Progress') {
        return ['state' => 'queued', 'label' => 'Queued', 'sub' => '', 'date' => '--'];
    }

    if ($stepKey === $currentStepKey && in_array($status, ['Pending', 'Queued'], true)) {
        return ['state' => 'active', 'label' => 'In Review', 'sub' => '', 'date' => 'Pending'];
    }

    return ['state' => 'queued', 'label' => 'Queued', 'sub' => '', 'date' => '--'];
}
