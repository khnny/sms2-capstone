<?php
/**
 * CRAD Module — Secure File Viewer
 * Serves uploaded student documents to authorized roles only.
 *
 * Usage: /modules/crad/file-view.php?pid=123&key=manuscript
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';

requireAuth();

$proposalId = (int) ($_GET['pid'] ?? 0);
$docKey     = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_GET['key'] ?? '')));

if ($proposalId <= 0 || $docKey === '') {
    http_response_code(400);
    exit('Invalid request.');
}

try {
    $cradPdo = getCradDatabaseConnection();

    $stmtP = $cradPdo->prepare(
        "SELECT submitted_by_user FROM research_proposals WHERE id = :pid LIMIT 1"
    );
    $stmtP->execute([':pid' => $proposalId]);
    $proposalRow = $stmtP->fetch();

    if (!$proposalRow) {
        http_response_code(404);
        exit('Proposal not found.');
    }

    $sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
    $ownerId = (int) ($proposalRow['submitted_by_user'] ?? 0);
    $role = getCurrentUserRoleKey();
    $staffRoles = [
        'crad_officer',
        'research_coordinator',
        'research_director',
        'adviser',
        'panel',
        'grammarian',
    ];
    $canView = ($ownerId > 0 && $ownerId === $sessionUserId)
        || in_array($role, $staffRoles, true)
        || smsIsGrantedAdminRole($role);

    if (!$canView) {
        http_response_code(403);
        exit('Access denied.');
    }

    $stmtD = $cradPdo->prepare(
        "SELECT stored_name, original_name FROM proposal_documents
         WHERE proposal_id = :pid AND doc_key = :key LIMIT 1"
    );
    $stmtD->execute([':pid' => $proposalId, ':key' => $docKey]);
    $doc = $stmtD->fetch();
} catch (Throwable $e) {
    error_log('CRAD file-view error: ' . $e->getMessage());
    http_response_code(500);
    exit('Database error.');
}

if (!$doc || empty($doc['stored_name'])) {
    http_response_code(404);
    exit('File not found.');
}

$userId     = (int) ($proposalRow['submitted_by_user'] ?? 0);
$storedName = basename($doc['stored_name']);
$subdir     = 'student_docs/u' . $userId;
$filePath   = ROOT_PATH . '/storage/uploads/' . $subdir . '/' . $storedName;
$realPath   = realpath($filePath);

$uploadsDir = realpath(ROOT_PATH . '/storage/uploads');
if (
    $realPath === false ||
    !file_exists($realPath) ||
    $uploadsDir === false ||
    strncmp($realPath, $uploadsDir, strlen($uploadsDir)) !== 0
) {
    http_response_code(404);
    exit('File not found on disk.');
}

$ext  = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mime = match ($ext) {
    'pdf'  => 'application/pdf',
    'jpg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'webp' => 'image/webp',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    default => 'application/octet-stream',
};

$origName = $doc['original_name'] ?: basename($realPath);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($realPath));
header('Content-Disposition: inline; filename="' . rawurlencode($origName) . '"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

readfile($realPath);
exit;
