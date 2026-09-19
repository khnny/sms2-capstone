<?php
/**
 * CRAD Grant Proposal — Sequential Approval Workflow API
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/grant-approval-helpers.php';

requireAuth();

if (!grantUserCanViewApprovalWorkflow()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = trim((string) ($_GET['action'] ?? ($_POST['action'] ?? '')));

try {
    $crad = getCradDatabaseConnection();
    grantEnsureApprovalTables($crad);
    grantBackfillApprovalWorkflows($crad);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'CRAD database unavailable.']);
    exit;
}

$userId   = (int) ($_SESSION['user_id'] ?? 0);
$userName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'));

switch ($action) {
    case 'get_workflows':
        $workflows = grantApprovalWorkflowList($crad);
        $selectedId = (int) ($_GET['id'] ?? 0);
        $detail = null;
        if ($selectedId > 0) {
            $detail = grantGetApprovalWorkflowDetail($crad, $selectedId);
        }
        echo json_encode([
            'success'     => true,
            'workflows'   => $workflows,
            'detail'      => $detail,
            'fingerprint' => grantApprovalWorkflowFingerprint($workflows)
                . ':' . grantApprovalDetailFingerprint($detail),
            'count'       => count($workflows),
            'in_progress' => count(array_filter(
                $workflows,
                static fn(array $r): bool => (string) ($r['workflow_status'] ?? '') === 'In Progress'
            )),
            'completed'   => count(array_filter(
                $workflows,
                static fn(array $r): bool => (string) ($r['workflow_status'] ?? '') === 'Completed'
            )),
        ]);
        break;

    case 'get_detail':
        $applicationId = (int) ($_GET['id'] ?? 0);
        if ($applicationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Application id is required.']);
            break;
        }
        $detail = grantGetApprovalWorkflowDetail($crad, $applicationId);
        if ($detail === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Workflow not found.']);
            break;
        }
        echo json_encode(['success' => true, 'detail' => $detail]);
        break;

    case 'sign_approve':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
        }
        $body = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
        $applicationId = (int) ($body['application_id'] ?? 0);
        $signature     = trim((string) ($body['signature_data'] ?? ''));
        $remarks       = trim((string) ($body['remarks'] ?? ''));
        $result = grantSubmitApprovalSignoff(
            $crad,
            $applicationId,
            $userId,
            $userName,
            $signature !== '' ? $signature : null,
            $remarks !== '' ? $remarks : null
        );
        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Sign-off failed.']);
            break;
        }
        echo json_encode([
            'success'   => true,
            'message'   => !empty($result['completed'])
                ? (!empty($result['funded'])
                    ? 'Proposal approved and funded. Status is now APPROVED & FUNDED.'
                    : 'Proposal fully approved.')
                : 'Sign-off recorded. Proposal advanced to the next approval level.',
            'completed' => !empty($result['completed']),
            'funded'    => !empty($result['funded']),
            'detail'    => grantGetApprovalWorkflowDetail($crad, $applicationId),
        ]);
        break;

    case 'return_revision':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
        }
        $body = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
        $applicationId = (int) ($body['application_id'] ?? 0);
        $remarks       = trim((string) ($body['remarks'] ?? ''));
        $result = grantReturnProposalFromApproval($crad, $applicationId, $userId, $userName, $remarks);
        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Return failed.']);
            break;
        }
        echo json_encode([
            'success' => true,
            'message' => 'Proposal returned to proponent for revision. The researcher was notified to open Revisions Requested.',
            'detail'  => grantGetApprovalWorkflowDetail($crad, $applicationId),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
