<?php
/**
 * CRAD Module — Secure File Viewer
 * Serves uploaded student documents to authenticated CRAD officers.
 *
 * Usage: /sms2_system/modules/crad/file-view.php?pid=123&key=manuscript
 *   pid = proposal_id (from crad_db research_proposals.id)
 *   key = doc_key (manuscript, approval, abstract, etc.)
 */
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';

requireAuth();

// ── Input validation ──────────────────────────────────────────────────────────
$proposalId = (int) ($_GET['pid'] ?? 0);
$docKey     = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_GET['key'] ?? '')));

if ($proposalId <= 0 || $docKey === '') {
    http_response_code(400);
    exit('Invalid request.');
}

// ── Fetch document record from crad_db ────────────────────────────────────────
try {
    $cradPdo = getCradDatabaseConnection();

    // Also verify proposal exists (security: ensure pid is valid)
    $stmtP = $cradPdo->prepare(
        "SELECT submitted_by_user FROM research_proposals WHERE id = :pid LIMIT 1"
    );
    $stmtP->execute([':pid' => $proposalId]);
    $proposalRow = $stmtP->fetch();

    if (!$proposalRow) {
        http_response_code(404);
        exit('Proposal not found.');
    }

    // Get document record
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

// ── Build file path ───────────────────────────────────────────────────────────
// Subdir = student_docs/u{submitted_by_user}
$userId     = (int) ($proposalRow['submitted_by_user'] ?? 0);
$storedName = basename($doc['stored_name']); // safety: strip any path component
$subdir     = 'student_docs/u' . $userId;
$filePath   = ROOT_PATH . '/storage/uploads/' . $subdir . '/' . $storedName;
$realPath   = realpath($filePath);

// Verify file is within uploads directory (no traversal)
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

// ── MIME type ─────────────────────────────────────────────────────────────────
$ext  = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mime = match($ext) {
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

// ── Serve file ────────────────────────────────────────────────────────────────
$origName = $doc['original_name'] ?: basename($realPath);

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($realPath));
// inline = browser renders it (PDF, image); does not force download
header('Content-Disposition: inline; filename="' . rawurlencode($origName) . '"');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');

readfile($realPath);
exit;
