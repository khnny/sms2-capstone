<?php
/**
 * Secure document repository archived file viewer.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/uploads.php';
require_once __DIR__ . '/includes/grant-document-repository-helpers.php';

requireAuth();

if (!grantUserCanArchiveDocuments()) {
    http_response_code(403);
    exit('Access denied.');
}

$itemId = (int) ($_GET['item_id'] ?? 0);
if ($itemId <= 0) {
    http_response_code(400);
    exit('Invalid request.');
}

try {
    $crad = getCradDatabaseConnection();
    grantEnsureDocumentRepositoryTables($crad);
    $stmt = $crad->prepare("
        SELECT i.*, dr.grant_application_id
          FROM grant_document_repository_items i
         INNER JOIN grant_document_repository dr ON dr.id = i.repository_id
         WHERE i.id = ?
           AND i.item_type = 'file'
         LIMIT 1
    ");
    $stmt->execute([$itemId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Database error.');
}

if (!$row || empty($row['file_path'])) {
    http_response_code(404);
    exit('File not found.');
}

$fullPath = grantResolveStoredUploadPath((string) $row['file_path'], []);
if ($fullPath === null) {
    http_response_code(404);
    exit('File missing on server.');
}

$originalName = (string) ($row['file_original'] ?? 'download');
$mime = mime_content_type($fullPath) ?: 'application/octet-stream';
$safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $originalName) ?: 'download';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $safeName . '"');
header('Content-Length: ' . (string) filesize($fullPath));
header('X-Content-Type-Options: nosniff');
readfile($fullPath);
exit;
