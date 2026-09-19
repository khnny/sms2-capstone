<?php
/**
 * Secure grant proposal document viewer for authorized reviewers and managers.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once __DIR__ . '/includes/grant-helpers.php';
require_once __DIR__ . '/includes/grant-evaluation-helpers.php';

requireAuth();

$applicationId = (int) ($_GET['id'] ?? 0);
$field         = preg_replace('/[^a-z_]/', '', strtolower(trim((string) ($_GET['field'] ?? 'proposal'))));

$allowedFields = [
    'proposal'   => ['proposal_pdf', 'proposal_pdf_original'],
    'supporting' => ['supporting_docs', 'supporting_docs_original'],
    'ethics'     => ['ethics_doc', 'ethics_doc_original'],
];

if ($applicationId <= 0 || !isset($allowedFields[$field])) {
    http_response_code(400);
    exit('Invalid request.');
}

$canAccess = grantUserCanManage() || grantUserCanEvaluate();

if (!$canAccess && grantUserCanApply()) {
    $cradCheck = cradDb();
    if ($cradCheck) {
        $ownerStmt = $cradCheck->prepare(
            'SELECT id FROM grant_applications WHERE id = ? AND applicant_user_id = ? LIMIT 1'
        );
        $ownerStmt->execute([$applicationId, (int) ($_SESSION['user_id'] ?? 0)]);
        $canAccess = (bool) $ownerStmt->fetchColumn();
    }
}

if (!$canAccess) {
    http_response_code(403);
    exit('Access denied.');
}

try {
    $crad = getCradDatabaseConnection();
    grantEnsureTables($crad);
    $app = grantGetApplicationForEvaluation($crad, $applicationId);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Database error.');
}

if (!$app) {
    http_response_code(404);
    exit('Proposal not found.');
}

[$storedCol, $originalCol] = $allowedFields[$field];
$storedName = basename((string) ($app[$storedCol] ?? ''));
if ($storedName === '') {
    http_response_code(404);
    exit('File not found.');
}

$filePath = ROOT_PATH . '/storage/uploads/grant_proposals/' . $storedName;
$realPath = realpath($filePath);
$uploadsDir = realpath(ROOT_PATH . '/storage/uploads');

if (
    $realPath === false
    || !is_file($realPath)
    || $uploadsDir === false
    || strncmp($realPath, $uploadsDir, strlen($uploadsDir)) !== 0
) {
    http_response_code(404);
    exit('File not found on disk.');
}

$ext  = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg', 'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    default => 'application/octet-stream',
};

$origName = (string) ($app[$originalCol] ?? basename($realPath));

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($realPath));
header('Content-Disposition: inline; filename="' . rawurlencode($origName) . '"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

readfile($realPath);
exit;
