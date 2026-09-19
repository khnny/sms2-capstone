<?php
/**
 * CRAD Grant — Document Repository API
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/grant-document-repository-helpers.php';

requireAuth();

if (!grantUserCanArchiveDocuments()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = trim((string) ($_GET['action'] ?? ($_POST['action'] ?? '')));

try {
    $crad = getCradDatabaseConnection();
    grantEnsureDocumentRepositoryTables($crad);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'CRAD database unavailable.']);
    exit;
}

$userId   = (int) ($_SESSION['user_id'] ?? 0);
$userName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'));

switch ($action) {
    case 'get_overview':
        $overview = grantGetDocumentRepositoryOverview($crad);
        $selectedId = (int) ($_GET['id'] ?? 0);
        $detail = $selectedId > 0 ? grantGetDocumentRepositoryDetail($crad, $selectedId) : null;
        $pendingCount = count(array_filter($overview, static fn(array $r): bool => !empty($r['needs_archive'])));
        echo json_encode([
            'success'              => true,
            'overview'             => $overview,
            'detail'               => $detail,
            'pending_count'        => $pendingCount,
            'overview_fingerprint' => grantDocumentRepositoryOverviewFingerprint($overview),
            'detail_fingerprint'   => grantDocumentRepositoryDetailFingerprint($detail),
        ]);
        break;

    case 'archive':
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

        $result = grantArchiveToDocumentRepository($crad, $applicationId, $userId, $userName);
        if (empty($result['ok'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Archive failed.']);
            break;
        }

        $overview = grantGetDocumentRepositoryOverview($crad);
        echo json_encode([
            'success'              => true,
            'message'              => 'Records archived to Document Repository successfully.',
            'detail'               => $result['detail'],
            'overview'             => $overview,
            'pending_count'        => count(array_filter($overview, static fn(array $r): bool => !empty($r['needs_archive']))),
            'overview_fingerprint' => grantDocumentRepositoryOverviewFingerprint($overview),
            'detail_fingerprint'   => grantDocumentRepositoryDetailFingerprint($result['detail'] ?? null),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
