<?php
/**
 * Secure final output / publications & IP file viewer.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/uploads.php';
require_once __DIR__ . '/includes/grant-final-output-helpers.php';

requireAuth();

if (!grantUserCanViewPublicationsIp()) {
    http_response_code(403);
    exit('Access denied.');
}

$submissionId = (int) ($_GET['submission_id'] ?? 0);
$type = trim((string) ($_GET['type'] ?? ''));
$index = (int) ($_GET['index'] ?? 0);

if ($submissionId <= 0 || !in_array($type, ['final_pdf', 'supporting'], true)) {
    http_response_code(400);
    exit('Invalid request.');
}

try {
    $crad = getCradDatabaseConnection();
    grantEnsureFinalOutputTables($crad);
    $stmt = $crad->prepare("
        SELECT s.*, ga.applicant_user_id, ga.status AS application_status
          FROM grant_final_output_submissions s
         INNER JOIN grant_applications ga ON ga.id = s.grant_application_id
         WHERE s.id = ?
         LIMIT 1
    ");
    $stmt->execute([$submissionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Database error.');
}

if (!$row) {
    http_response_code(404);
    exit('File not found.');
}

$canAccess = grantUserCanVerifyPublicationsIp();
if (!$canAccess && grantUserCanSubmitFinalOutput()) {
    $canAccess = (int) ($row['applicant_user_id'] ?? 0) === (int) ($_SESSION['user_id'] ?? 0);
}

if (!$canAccess) {
    http_response_code(403);
    exit('Access denied.');
}

$filePath = null;
$originalName = 'download';

if ($type === 'final_pdf') {
    $filePath = (string) ($row['final_pdf_path'] ?? '');
    $originalName = (string) ($row['final_pdf_original'] ?? 'final-research.pdf');
} else {
    $files = [];
    if (!empty($row['supporting_files_json'])) {
        $decoded = json_decode((string) $row['supporting_files_json'], true);
        if (is_array($decoded)) {
            $files = $decoded;
        }
    }
    if (!isset($files[$index])) {
        http_response_code(404);
        exit('Supporting file not found.');
    }
    $filePath = (string) ($files[$index]['path'] ?? '');
    $originalName = (string) ($files[$index]['original_name'] ?? 'supporting-file');
}

if ($filePath === '') {
    http_response_code(404);
    exit('File not found.');
}

$fullPath = grantResolveStoredUploadPath((string) $filePath, ['grant_final_output', 'grant_final_output_supporting']);
if ($fullPath === null) {
    http_response_code(404);
    exit('File missing on server.');
}

$mime = mime_content_type($fullPath) ?: 'application/octet-stream';
$safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $originalName) ?: 'download';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $safeName . '"');
header('Content-Length: ' . (string) filesize($fullPath));
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
exit;
