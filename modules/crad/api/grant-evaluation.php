<?php
/**
 * CRAD Grant Proposal Evaluation API (Review Committee).
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/grant-evaluation-helpers.php';

requireAuth();

if (function_exists('getCurrentUserRoleKey') && getCurrentUserRoleKey() === 'crad_officer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CRAD Officer uses Approval Workflows for grant sign-off monitoring.']);
    exit;
}

if (!grantUserCanEvaluate()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = trim((string) ($_GET['action'] ?? ($_POST['action'] ?? '')));

try {
    $crad = getCradDatabaseConnection();
    grantEnsureEvaluationTables($crad);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'CRAD database unavailable.']);
    exit;
}

switch ($action) {
        case 'get_queue':
        $queue = grantEvaluationQueue($crad);
        if (grantIsAdviserEvaluationViewer()) {
            $pending = count(array_filter($queue, static fn(array $r): bool => empty($r['my_evaluation_id'])));
            $scored  = grantAdviserEvaluationScoredCount($crad);
        } elseif (grantIsGrantApproverEvaluationViewer()) {
            $pending = count(array_filter($queue, static fn(array $r): bool => empty($r['my_evaluation_id'])));
            $scored  = count(array_filter($queue, static fn(array $r): bool => !empty($r['my_evaluation_id'])));
        } elseif (grantIsGrantWorkflowMonitor()) {
            $counts  = grantMonitorEvaluationCounts($crad);
            $pending = $counts['pending'];
            $scored  = $counts['scored'];
        } else {
            $pending = count(array_filter($queue, static fn(array $r): bool => empty($r['my_evaluation_id'])));
            $scored  = count(array_filter($queue, static fn(array $r): bool => !empty($r['my_evaluation_id'])));
        }
        echo json_encode([
            'success' => true,
            'queue'   => $queue,
            'pending' => $pending,
            'scored'  => $scored,
            'count'   => count($queue),
        ]);
        break;

    case 'submit_evaluation':
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

        $result = grantSubmitProposalEvaluation($crad, $applicationId, $_POST);
        if ($result['ok']) {
            $recommendation = (string) ($result['recommendation'] ?? '');
            if ($recommendation === 'disapprove') {
                $message = 'Proposal disapproved. The researcher has been notified.';
            } elseif ($recommendation === 'require_revisions') {
                $message = 'Revision request sent. The researcher has been notified.';
            } elseif ($recommendation === 'recommend' && grantIsAdviserEvaluationViewer()) {
                $message = 'Adviser evaluation submitted. You may now sign off in Approval Workflows.';
            } elseif ($recommendation === 'recommend' && grantIsGrantApproverEvaluationViewer()) {
                $message = grantEvaluationStepLabel((string) grantEvaluationTypeForApproverRole())
                    . ' evaluation submitted. You may now sign off in Approval Workflows.';
            } elseif ($recommendation === 'recommend') {
                $message = 'Proposal recommended for approval workflow.';
            } else {
                $message = 'Evaluation submitted successfully.';
            }
            echo json_encode([
                'success'        => true,
                'message'        => $message,
                'id'             => $result['id'],
                'total_score'    => $result['total_score'],
                'recommendation' => $result['recommendation'] ?? null,
                'new_status'     => $result['new_status'] ?? null,
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to save evaluation.']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
