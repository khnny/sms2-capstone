<?php
/**
 * CRAD Grant Funding — Budget & Disbursement API
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/grant-funding-helpers.php';

requireAuth();

if (!grantUserCanViewFundingDisbursement()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = trim((string) ($_GET['action'] ?? ($_POST['action'] ?? '')));

try {
    $crad = getCradDatabaseConnection();
    grantEnsureFundingTables($crad);
    grantBackfillFundingDisbursementPlans($crad);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'CRAD database unavailable.']);
    exit;
}

$userId   = (int) ($_SESSION['user_id'] ?? 0);
$userName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'));

switch ($action) {
    case 'get_overview':
        $overview = grantGetFundedDisbursementOverview($crad);
        $selectedId = (int) ($_GET['id'] ?? 0);
        $detail = $selectedId > 0 ? grantGetFundingDisbursementDetail($crad, $selectedId) : null;
        echo json_encode([
            'success'              => true,
            'overview'             => $overview,
            'detail'               => $detail,
            'overview_fingerprint' => grantFundingOverviewFingerprint($overview),
            'detail_fingerprint'   => grantFundingDetailFingerprint($detail),
            'can_release'          => grantUserCanReleaseFunds(),
        ]);
        break;

    case 'get_detail':
        $applicationId = (int) ($_GET['id'] ?? 0);
        if ($applicationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid proposal selected.']);
            break;
        }
        $detail = grantGetFundingDisbursementDetail($crad, $applicationId);
        if ($detail === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Funded proposal not found or access denied.']);
            break;
        }
        echo json_encode([
            'success'            => true,
            'detail'             => $detail,
            'detail_fingerprint' => grantFundingDetailFingerprint($detail),
        ]);
        break;

    case 'release_tranche':
        if (!grantUserCanReleaseFunds()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not authorized to release funds.']);
            break;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
        }

        $body = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
        $disbursementId = (int) ($body['disbursement_id'] ?? 0);
        if ($disbursementId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid tranche selected.']);
            break;
        }

        $result = grantReleaseFundingTranche($crad, $disbursementId, $body, $userId, $userName);
        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Release failed.']);
            break;
        }

        $overview = grantGetFundedDisbursementOverview($crad);
        $applicationId = (int) (($result['detail']['application']['grant_application_id'] ?? 0));
        $reference = (string) ($result['reference_number'] ?? '');
        $message = 'Fund release recorded successfully.';
        if ($reference !== '') {
            $message .= ' Reference: ' . $reference . '.';
        }

        echo json_encode([
            'success'              => true,
            'message'              => $message,
            'reference_number'     => $reference,
            'detail'               => $result['detail'],
            'overview'             => $overview,
            'overview_fingerprint' => grantFundingOverviewFingerprint($overview),
            'detail_fingerprint'   => grantFundingDetailFingerprint($result['detail'] ?? null),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
