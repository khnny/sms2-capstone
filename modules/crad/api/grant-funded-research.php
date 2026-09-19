<?php
/**
 * CRAD Grant — Researcher Funded Research API
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../../../includes/uploads.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/grant-funded-research-helpers.php';

requireAuth();

if (!grantUserCanViewFundedResearchDashboard()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = trim((string) ($_GET['action'] ?? ($_POST['action'] ?? '')));

try {
    $crad = getCradDatabaseConnection();
    grantEnsureFundedResearchTables($crad);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'CRAD database unavailable.']);
    exit;
}

$userId   = (int) ($_SESSION['user_id'] ?? 0);
$userName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'));

switch ($action) {
    case 'get_overview':
        $overview = grantGetFundedResearchOverview($crad);
        $selectedId = (int) ($_GET['id'] ?? 0);
        $detail = $selectedId > 0 ? grantGetFundedResearchDetail($crad, $selectedId) : null;
        echo json_encode([
            'success'              => true,
            'overview'             => $overview,
            'detail'               => $detail,
            'overview_fingerprint' => grantFundedResearchOverviewFingerprint($overview),
            'detail_fingerprint'   => grantFundedResearchDetailFingerprint($detail),
        ]);
        break;

    case 'submit_evidence':
        if (!grantUserCanConductFundedResearch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not authorized to submit evidence.']);
            break;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
        }

        require_once ROOT_PATH . '/includes/security.php';

        $applicationId = (int) ($_POST['grant_application_id'] ?? 0);
        if ($applicationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid project selected.']);
            break;
        }

        $upload = smsSecureUpload(
            $_FILES['supporting_file'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            [
                'subdir'    => 'grant_funded_evidence',
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
                    'jpg'  => ['image/jpeg'],
                    'jpeg' => ['image/jpeg'],
                    'png'  => ['image/png'],
                ],
            ]
        );

        if (empty($upload['ok'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => trim((string) ($upload['error'] ?? '')) ?: 'File upload failed.',
            ]);
            break;
        }

        $result = grantSubmitFundedProgressEvidence(
            $crad,
            $applicationId,
            $_POST,
            $upload,
            $userId,
            $userName
        );

        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Submission failed.']);
            break;
        }

        $overview = grantGetFundedResearchOverview($crad);
        echo json_encode([
            'success'              => true,
            'message'              => 'Progress evidence submitted successfully.',
            'detail'               => $result['detail'],
            'overview'             => $overview,
            'overview_fingerprint' => grantFundedResearchOverviewFingerprint($overview),
            'detail_fingerprint'   => grantFundedResearchDetailFingerprint($result['detail'] ?? null),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
