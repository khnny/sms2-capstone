<?php
/**
 * CRAD Grant — Final Output / Publications & IP API
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/grant-final-output-helpers.php';

requireAuth();

if (!grantUserCanViewPublicationsIp()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = trim((string) ($_GET['action'] ?? ($_POST['action'] ?? '')));

try {
    $crad = getCradDatabaseConnection();
    grantEnsureFinalOutputTables($crad);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'CRAD database unavailable.']);
    exit;
}

$userId   = (int) ($_SESSION['user_id'] ?? 0);
$userName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'));

$uploadOpts = [
    'max_bytes' => 15 * 1024 * 1024,
    'allowed'   => [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/octet-stream',
        ],
        'zip'  => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
    ],
];

switch ($action) {
    case 'outputs_stats':
        if (!grantUserCanVerifyPublicationsIp()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
            break;
        }
        require_once __DIR__ . '/../includes/grant-document-repository-helpers.php';
        $overview = grantGetFinalOutputOverview($crad);
        $repoOverview = grantGetDocumentRepositoryOverview($crad);
        echo json_encode([
            'success'               => true,
            'pending_verification'  => count(array_filter($overview, static fn(array $r): bool => !empty($r['needs_verification']))),
            'pending_archive'       => count(array_filter($repoOverview, static fn(array $r): bool => !empty($r['needs_archive']))),
            'stats_fingerprint'     => md5(implode('|', [
                grantFinalOutputOverviewFingerprint($overview),
                grantDocumentRepositoryOverviewFingerprint($repoOverview),
            ])),
        ]);
        break;

    case 'get_overview':
        $overview = grantGetFinalOutputOverview($crad);
        $selectedId = (int) ($_GET['id'] ?? 0);
        $detail = $selectedId > 0 ? grantGetFinalOutputDetail($crad, $selectedId) : null;
        $pendingCount = count(array_filter($overview, static fn(array $r): bool => !empty($r['needs_verification'])));
        echo json_encode([
            'success'              => true,
            'overview'             => $overview,
            'detail'               => $detail,
            'pending_count'        => $pendingCount,
            'overview_fingerprint' => grantFinalOutputOverviewFingerprint($overview),
            'detail_fingerprint'   => grantFinalOutputDetailFingerprint($detail),
            'can_verify'           => grantUserCanVerifyPublicationsIp(),
        ]);
        break;

    case 'submit_final_output':
        if (!grantUserCanSubmitFinalOutput()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not authorized to submit final output.']);
            break;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
        }

        require_once ROOT_PATH . '/includes/security.php';
        require_once ROOT_PATH . '/includes/uploads.php';

        $applicationId = (int) ($_POST['grant_application_id'] ?? 0);
        if ($applicationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid project selected.']);
            break;
        }

        $pdfUpload = smsSecureUpload(
            $_FILES['final_pdf'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            array_merge($uploadOpts, [
                'subdir'   => 'grant_final_output',
                'required' => true,
                'allowed'  => ['pdf' => ['application/pdf']],
            ])
        );

        $supportingUpload = smsSecureUpload(
            $_FILES['supporting_file'] ?? ['error' => UPLOAD_ERR_NO_FILE],
            array_merge($uploadOpts, [
                'subdir'   => 'grant_final_output_supporting',
                'required' => false,
            ])
        );

        if (!$supportingUpload['ok']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $supportingUpload['error'] ?? 'Supporting file upload failed.']);
            break;
        }

        $result = grantSubmitFinalOutput(
            $crad,
            $applicationId,
            $_POST,
            $pdfUpload,
            $supportingUpload,
            $userId,
            $userName
        );

        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Submission failed.']);
            break;
        }

        $overview = grantGetFinalOutputOverview($crad);
        echo json_encode([
            'success'              => true,
            'message'              => 'Final output submitted. Status: FINAL_OUTPUT_SUBMITTED.',
            'detail'               => $result['detail'],
            'overview'             => $overview,
            'pending_count'        => count(array_filter($overview, static fn(array $r): bool => !empty($r['needs_verification']))),
            'overview_fingerprint' => grantFinalOutputOverviewFingerprint($overview),
            'detail_fingerprint'   => grantFinalOutputDetailFingerprint($result['detail'] ?? null),
        ]);
        break;

    case 'verify':
        if (!grantUserCanVerifyPublicationsIp()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not authorized to verify submissions.']);
            break;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
        }

        $applicationId = (int) ($_POST['grant_application_id'] ?? 0);
        if ($applicationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid project selected.']);
            break;
        }

        $result = grantVerifyFinalOutput($crad, $applicationId, $_POST, $userId, $userName);

        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Verification failed.']);
            break;
        }

        $overview = grantGetFinalOutputOverview($crad);
        echo json_encode([
            'success'              => true,
            'message'              => 'Output verified and recorded. Status: OUTPUT_VERIFIED.',
            'detail'               => $result['detail'],
            'overview'             => $overview,
            'pending_count'        => count(array_filter($overview, static fn(array $r): bool => !empty($r['needs_verification']))),
            'overview_fingerprint' => grantFinalOutputOverviewFingerprint($overview),
            'detail_fingerprint'   => grantFinalOutputDetailFingerprint($result['detail'] ?? null),
        ]);
        break;

    case 'return_for_correction':
        if (!grantUserCanVerifyPublicationsIp()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not authorized to return submissions.']);
            break;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
            break;
        }

        $applicationId = (int) ($_POST['grant_application_id'] ?? 0);
        $reason = trim((string) ($_POST['return_reason'] ?? ''));
        if ($applicationId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid project selected.']);
            break;
        }

        $result = grantReturnFinalOutput($crad, $applicationId, $reason, $userId, $userName);

        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Return failed.']);
            break;
        }

        $overview = grantGetFinalOutputOverview($crad);
        echo json_encode([
            'success'              => true,
            'message'              => 'Submission returned for correction.',
            'detail'               => $result['detail'],
            'overview'             => $overview,
            'pending_count'        => count(array_filter($overview, static fn(array $r): bool => !empty($r['needs_verification']))),
            'overview_fingerprint' => grantFinalOutputOverviewFingerprint($overview),
            'detail_fingerprint'   => grantFinalOutputDetailFingerprint($result['detail'] ?? null),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
