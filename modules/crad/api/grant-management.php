<?php
/**
 * SMS 2 - CRAD Grant Management API
 *
 * JSON endpoint for the CORE SYSTEM grant management pages.
 *
 * Actions (GET):
 *   generate_token         → fresh one-time publish token (officer)
 *   generate_apply_token   → fresh one-time proposal-submit token
 *   get_dashboard_stats    → aggregated counts
 *   get_opportunities      → full opportunities list
 *   get_applications       → full applications/proposals list
 *                            (optional: &opportunity_id=N)
 *
 * Actions (POST):
 *   publish_opportunity    → create a new grant call (officer)
 *   submit_proposal        → submit a full BRGFAMS Form 1 proposal
 *                            (multipart/form-data with file uploads)
 *
 * DUPLICATE PREVENTION
 *   Every mutating request must carry a unique one-time token stored
 *   server-side in the session (crad_grant_tokens / crad_apply_tokens).
 *   Tokens expire after 10 minutes. Each token is consumed on first use.
 *
 * ACCESS: crad_officer, research_grant (CRAD grant module), and granted admins.
 */

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../../../includes/uploads.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/grant-helpers.php';

requireAuth();

if (!grantUserCanView()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Action may arrive in GET params, POST body (form-data), or JSON body.
$action = trim((string) ($_GET['action'] ?? ($_POST['action'] ?? '')));
if ($action === '' && $method === 'POST') {
    $raw     = file_get_contents('php://input');
    $decoded = json_decode((string) $raw, true);
    if (is_array($decoded)) {
        $action = trim((string) ($decoded['action'] ?? ''));
    }
}

try {
    $crad = getCradDatabaseConnection();
    grantEnsureTables($crad);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'CRAD database unavailable.']);
    exit;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Mint a fresh one-time session token stored in the given session bucket.
 * Purges stale tokens (older than 10 min) before inserting the new one.
 */
function _mintSessionToken(string $bucketKey): string
{
    $token = bin2hex(random_bytes(16));
    if (!isset($_SESSION[$bucketKey])) {
        $_SESSION[$bucketKey] = [];
    }
    $now = time();
    $_SESSION[$bucketKey] = array_filter(
        $_SESSION[$bucketKey],
        static fn(int $ts): bool => ($now - $ts) < 600
    );
    $_SESSION[$bucketKey][$token] = $now;
    return $token;
}

// ── Route ─────────────────────────────────────────────────────────────────────
switch ($action) {

    // ── Token generation ──────────────────────────────────────────────────────

    case 'generate_token':
        if (!grantUserCanManage()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        echo json_encode(['success' => true, 'token' => _mintSessionToken('crad_grant_tokens')]);
        break;

    case 'generate_apply_token':
        if (!grantUserCanApply()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        echo json_encode(['success' => true, 'token' => _mintSessionToken('crad_apply_tokens')]);
        break;

    // ── Read actions ──────────────────────────────────────────────────────────

    case 'get_dashboard_stats':
        if (!grantUserCanManage()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        echo json_encode(['success' => true, 'stats' => grantDashboardStats($crad)]);
        break;

    case 'get_dashboard_metrics':
        if (!grantUserCanManage()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        $metrics = grantGetDashboardMetrics($crad);
        echo json_encode([
            'success'     => true,
            'metrics'     => $metrics,
            'fingerprint' => grantDashboardMetricsFingerprint($metrics),
        ]);
        break;

    case 'get_opportunities':
        $opps = grantGetOpportunities($crad);
        echo json_encode(['success' => true, 'opportunities' => $opps, 'count' => count($opps)]);
        break;

    case 'get_applications':
        $oppId = isset($_GET['opportunity_id']) ? (int) $_GET['opportunity_id'] : null;
        if (grantUserCanManage()) {
            $apps = grantGetApplications($crad, $oppId);
        } else {
            $apps = grantGetMyApplications($crad);
            if ($oppId !== null && $oppId > 0) {
                $apps = array_values(array_filter(
                    $apps,
                    static fn(array $row): bool => (int) ($row['grant_opportunity_id'] ?? 0) === $oppId
                ));
            }
        }
        echo json_encode(['success' => true, 'applications' => $apps, 'count' => count($apps)]);
        break;

    // ── Publish a new grant call (officer only) ───────────────────────────────

    case 'publish_opportunity':
        if (!grantUserCanManage()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $input = $_POST;
        if (empty($input)) {
            $raw     = file_get_contents('php://input');
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $input = $decoded;
            }
        }

        // Token validation
        $token       = trim((string) ($input['token'] ?? ''));
        $validTokens = $_SESSION['crad_grant_tokens'] ?? [];
        if ($token === '' || !isset($validTokens[$token])) {
            $code = ($token === '') ? 400 : 409;
            http_response_code($code);
            echo json_encode([
                'success' => false,
                'message' => $token === ''
                    ? 'Submission token is required.'
                    : 'Invalid or expired submission token. Please reload the form and try again.',
            ]);
            exit;
        }
        unset($_SESSION['crad_grant_tokens'][$token]);

        $userId   = (int) ($_SESSION['user_id'] ?? 0);
        $userName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? ''));

        $result = grantPublishOpportunity($crad, [
            'funding_title'        => $input['funding_title']        ?? '',
            'max_funding_cap'      => $input['max_funding_cap']       ?? 0,
            'application_deadline' => $input['application_deadline']  ?? '',
            'eligibility'          => $input['eligibility']           ?? 'Open',
            'college_program'      => $input['college_program']       ?? '',
            'created_by_user_id'   => $userId,
            'created_by_name'      => $userName,
        ]);

        if ($result['ok']) {
            echo json_encode([
                'success' => true,
                'message' => 'Grant call published successfully.',
                'id'      => $result['id'],
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to publish grant call.']);
        }
        break;

    // ── Submit a full research grant proposal (BRGFAMS Form 1) ───────────────

    case 'submit_proposal':
        if (!grantUserCanApply()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        // All scalar fields arrive in $_POST (multipart/form-data).
        // All file fields arrive in $_FILES.

        $userId   = (int) ($_SESSION['user_id'] ?? 0);
        $userName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? ''));

        // ── Process file uploads first (before DB, so failures don't leave orphan rows)
        // Proposal PDF — required
        $proposalUpload = smsSecureUpload(
            $_FILES['proposal_pdf'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            [
                'subdir'    => 'grant_proposals',
                'required'  => true,
                'max_bytes' => 10 * 1024 * 1024,          // 10 MB
                'allowed'   => [
                    'pdf'  => ['application/pdf'],
                    'doc'  => ['application/msword', 'application/octet-stream'],
                    'docx' => [
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/zip',
                        'application/octet-stream',
                    ],
                ],
            ]
        );

        // Supporting documents — optional
        $supportingUpload = smsSecureUpload(
            $_FILES['supporting_docs'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            [
                'subdir'   => 'grant_proposals',
                'required' => false,
                'max_bytes' => 10 * 1024 * 1024,
            ]
        );

        // Ethics clearance document — optional
        $ethicsUpload = smsSecureUpload(
            $_FILES['ethics_doc'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            [
                'subdir'   => 'grant_proposals',
                'required' => false,
                'max_bytes' => 10 * 1024 * 1024,
            ]
        );

        // ── Delegate to helper (token validation, DB validation, INSERT) ──────
        $result = grantSubmitProposal(
            $crad,
            [
                'grant_opportunity_id' => $_POST['grant_opportunity_id'] ?? 0,
                'lead_proponent'       => $_POST['lead_proponent']        ?? $userName,
                'research_title'       => $_POST['research_title']        ?? '',
                'college_dept'         => $_POST['college_dept']          ?? '',
                'requested_budget'     => $_POST['requested_budget']      ?? 0,
                'abstract'             => $_POST['abstract']              ?? '',
                'objectives'           => $_POST['objectives']            ?? '',
                'group_number'         => $_POST['group_number']          ?? '',
                'application_notes'    => $_POST['application_notes']     ?? '',
                'applicant_user_id'    => $userId,
                'apply_token'          => $_POST['apply_token']           ?? '',
            ],
            [
                'proposal_pdf'    => $proposalUpload,
                'supporting_docs' => $supportingUpload,
                'ethics_doc'      => $ethicsUpload,
            ]
        );

        if ($result['ok']) {
            echo json_encode([
                'success'     => true,
                'message'     => 'Proposal submitted successfully.',
                'id'          => $result['id'],
                'status_label'=> 'Pending Evaluation',
            ]);
        } else {
            // If token was already consumed in grantSubmitProposal but upload/DB failed,
            // we can't re-issue the same token; the JS will fetch a fresh one.
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to submit proposal.']);
        }
        break;

    case 'get_revisions':
        if (!grantUserCanApply()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        $revisions = grantEnrichRevisionApplications($crad, grantGetMyRevisionRequiredApplications($crad));
        echo json_encode(['success' => true, 'revisions' => $revisions, 'count' => count($revisions)]);
        break;

    case 'resubmit_proposal':
        if (!grantUserCanApply()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            exit;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            exit;
        }

        $applicationId = (int) ($_POST['grant_application_id'] ?? 0);
        if ($applicationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid proposal selected.']);
            exit;
        }

        $proposalUpload = smsSecureUpload(
            $_FILES['proposal_pdf'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            [
                'subdir'    => 'grant_proposals',
                'required'  => true,
                'max_bytes' => 10 * 1024 * 1024,
                'allowed'   => [
                    'pdf'  => ['application/pdf'],
                    'doc'  => ['application/msword', 'application/octet-stream'],
                    'docx' => [
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/zip',
                        'application/octet-stream',
                    ],
                ],
            ]
        );
        $supportingUpload = smsSecureUpload(
            $_FILES['supporting_docs'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            ['subdir' => 'grant_proposals', 'required' => false, 'max_bytes' => 10 * 1024 * 1024]
        );
        $ethicsUpload = smsSecureUpload(
            $_FILES['ethics_doc'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            ['subdir' => 'grant_proposals', 'required' => false, 'max_bytes' => 10 * 1024 * 1024]
        );

        $result = grantResubmitProposal(
            $crad,
            $applicationId,
            ['researcher_notes' => $_POST['researcher_notes'] ?? ''],
            [
                'proposal_pdf'    => $proposalUpload,
                'supporting_docs' => $supportingUpload,
                'ethics_doc'      => $ethicsUpload,
            ]
        );

        if ($result['ok']) {
            echo json_encode([
                'success'      => true,
                'message'      => 'Proposal resubmitted successfully. It is now under committee review.',
                'id'           => $result['id'],
                'reference'    => $result['reference'] ?? '',
                'version'      => $result['version'] ?? null,
                'new_status'   => $result['new_status'] ?? 'Under Review',
                'status_label' => 'UNDER REVIEW',
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to resubmit proposal.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
