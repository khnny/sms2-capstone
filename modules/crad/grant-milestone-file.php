<?php
/**
 * Secure milestone supporting document viewer.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once __DIR__ . '/includes/grant-milestone-helpers.php';

requireAuth();

if (!grantUserCanViewFundedMilestones()) {
    http_response_code(403);
    exit('Access denied.');
}

$milestoneId = (int) ($_GET['id'] ?? 0);
if ($milestoneId <= 0) {
    http_response_code(400);
    exit('Invalid request.');
}

try {
    $crad = getCradDatabaseConnection();
    grantEnsureMilestoneTables($crad);
    $stmt = $crad->prepare("
        SELECT m.*, ga.applicant_user_id, ga.status AS application_status
          FROM grant_funded_project_milestones m
         INNER JOIN grant_applications ga ON ga.id = m.grant_application_id
         WHERE m.id = ?
         LIMIT 1
    ");
    $stmt->execute([$milestoneId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Database error.');
}

if (!$row) {
    http_response_code(404);
    exit('Milestone not found.');
}

if (!in_array((string) ($row['application_status'] ?? ''), grantPostFundingApplicationStatuses(), true)) {
    http_response_code(403);
    exit('Access denied.');
}

$canAccess = grantUserCanTrackFundedMilestones();
if (!$canAccess && grantUserCanApply()) {
    $canAccess = (int) ($row['applicant_user_id'] ?? 0) === (int) ($_SESSION['user_id'] ?? 0);
}

if (!$canAccess) {
    http_response_code(403);
    exit('Access denied.');
}

$stored = trim((string) ($row['supporting_doc'] ?? ''));
if ($stored === '') {
    http_response_code(404);
    exit('Document not found.');
}

$uploadRoot = ROOT_PATH . '/storage/uploads/grant_milestones';
$path = $uploadRoot . '/' . basename($stored);
$realPath = realpath($path);
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

$ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg', 'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    default => mime_content_type($realPath) ?: 'application/octet-stream',
};

$original = trim((string) ($row['supporting_doc_original'] ?? ''));
$downloadName = $original !== '' ? $original : basename($realPath);

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . rawurlencode($downloadName) . '"');
header('Content-Length: ' . (string) filesize($realPath));
header('Cache-Control: private, no-store');
readfile($realPath);
exit;
