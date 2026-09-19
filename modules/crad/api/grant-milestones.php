<?php
/**
 * CRAD Grant — Funded project milestones API
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../../../includes/uploads.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/grant-milestone-helpers.php';

requireAuth();

if (!grantUserCanViewFundedMilestones()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = trim((string) ($_GET['action'] ?? ($_POST['action'] ?? '')));

try {
    $crad = getCradDatabaseConnection();
    grantBackfillFundedProjectMilestones($crad);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'CRAD database unavailable.']);
    exit;
}

$userId   = (int) ($_SESSION['user_id'] ?? 0);
$userName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'));

switch ($action) {
    case 'get_overview':
        $overview = grantGetFundedMilestoneOverview($crad);
        $selectedId = (int) ($_GET['id'] ?? 0);
        $detail = $selectedId > 0 ? grantGetFundedMilestoneDetail($crad, $selectedId) : null;
        echo json_encode([
            'success'              => true,
            'overview'             => $overview,
            'detail'               => $detail,
            'overview_fingerprint' => grantMilestoneOverviewFingerprint($overview),
            'detail_fingerprint'   => grantMilestoneDetailFingerprint($detail),
            'can_track'            => grantUserCanTrackFundedMilestones(),
        ]);
        break;

    case 'get_detail':
        $applicationId = (int) ($_GET['id'] ?? 0);
        if ($applicationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid project selected.']);
            break;
        }
        $detail = grantGetFundedMilestoneDetail($crad, $applicationId);
        if ($detail === null) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Funded project not found or access denied.']);
            break;
        }
        echo json_encode([
            'success'            => true,
            'detail'             => $detail,
            'detail_fingerprint' => grantMilestoneDetailFingerprint($detail),
        ]);
        break;

    case 'update_milestone':
        if (!grantUserCanTrackFundedMilestones()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not authorized to update milestones.']);
            break;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
        }

        $body = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
        $milestoneId = (int) ($body['milestone_id'] ?? 0);
        if ($milestoneId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid milestone selected.']);
            break;
        }

        $result = grantUpdateFundedProjectMilestone($crad, $milestoneId, $body, $userId, $userName);
        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Update failed.']);
            break;
        }

        $overview = grantGetFundedMilestoneOverview($crad);
        echo json_encode([
            'success'              => true,
            'message'              => 'Milestone updated successfully.',
            'detail'               => $result['detail'],
            'overview'             => $overview,
            'overview_fingerprint' => grantMilestoneOverviewFingerprint($overview),
            'detail_fingerprint'   => grantMilestoneDetailFingerprint($result['detail'] ?? null),
        ]);
        break;

    case 'upload_document':
        if (!grantUserCanTrackFundedMilestones()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not authorized to upload documents.']);
            break;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
        }

        require_once ROOT_PATH . '/includes/security.php';
        $milestoneId = (int) ($_POST['milestone_id'] ?? 0);
        if ($milestoneId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid milestone selected.']);
            break;
        }

        $upload = smsSecureUpload(
            $_FILES['supporting_doc'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            [
                'subdir'    => 'grant_milestones',
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

        $result = grantUploadFundedMilestoneDocument($crad, $milestoneId, $upload, $userId, $userName);
        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Upload failed.']);
            break;
        }

        $overview = grantGetFundedMilestoneOverview($crad);
        echo json_encode([
            'success'              => true,
            'message'              => 'Supporting document uploaded.',
            'detail'               => $result['detail'],
            'overview'             => $overview,
            'overview_fingerprint' => grantMilestoneOverviewFingerprint($overview),
            'detail_fingerprint'   => grantMilestoneDetailFingerprint($result['detail'] ?? null),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
