<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/uploads.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/research-progress-helpers.php';

requireAuth();

$crad = cradDb();
$attachmentId = (int) ($_GET['id'] ?? 0);
if ($attachmentId <= 0) {
    http_response_code(404);
    echo 'Document not found.';
    exit;
}

$stmt = $crad->prepare(
    "SELECT rpa.*, rpu.research_group_id, rpu.research_plan_id, rpu.milestone_id,
            rpu.submitted_by_user_id, rg.group_number
     FROM `crad_research_progress_attachments` rpa
     INNER JOIN `crad_research_progress_updates` rpu ON rpu.id = rpa.progress_update_id
     INNER JOIN `crad_research_groups` rg ON rg.id = rpu.research_group_id
     WHERE rpa.id = ?
     LIMIT 1"
);
$stmt->execute([$attachmentId]);
$attachment = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$attachment) {
    http_response_code(404);
    echo 'Document not found.';
    exit;
}

$role = getCurrentUserRoleKey();
$allowed = false;
if ($role === 'student') {
    $studentId = trim((string) ($_SESSION['student_id'] ?? ''));
    $studentUserId = (int) ($_SESSION['user_id'] ?? 0);
    $group = rpGetRegisteredResearchGroup($crad, $studentId, $studentUserId);
    $allowed = $group && (int) $group['id'] === (int) $attachment['research_group_id'];
} elseif ($role === 'adviser') {
    $adviserUserId = (int) ($_SESSION['user_id'] ?? 0);
    $adviserEmail = rpCurrentUserEmail();
    $allowed = (bool) rpGetProgressUpdateForAdviser(
        $crad,
        (int) $attachment['progress_update_id'],
        $adviserUserId,
        $adviserEmail
    );
}

if (!$allowed) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

// Resolve the file path in a cross-platform way.  The DB may contain:
// (a) an absolute path that matches the current server (same OS/root),
// (b) stored_subdir + stored_name columns (best-practice relative), or
// (c) a Windows absolute path deployed to Linux (or vice-versa).
$root = realpath(smsUploadRoot());
$path = false;

// Strategy 1: Try the stored file_path directly (works if same server).
if (!empty($attachment['file_path'])) {
    $candidate = realpath((string) $attachment['file_path']);
    if ($candidate && $root && strpos($candidate, $root . DIRECTORY_SEPARATOR) === 0 && is_file($candidate)) {
        $path = $candidate;
    }
}

// Strategy 2: Reconstruct from stored_subdir + stored_name (cross-platform).
if (!$path && !empty($attachment['stored_name'])) {
    $subdir = trim((string) ($attachment['stored_subdir'] ?? ''), '/\\');
    $candidate = $root . '/' . ($subdir !== '' ? $subdir . '/' : '') . $attachment['stored_name'];
    $candidate = realpath($candidate);
    if ($candidate && $root && strpos($candidate, $root . DIRECTORY_SEPARATOR) === 0 && is_file($candidate)) {
        $path = $candidate;
    }
}

// Strategy 3: Extract relative path from a Windows absolute path on Linux.
if (!$path && !empty($attachment['file_path'])) {
    $fp = str_replace('\\', '/', (string) $attachment['file_path']);
    // Strip everything up to and including 'storage/uploads/'
    if (preg_match('#storage/uploads/(.+)$#i', $fp, $m)) {
        $candidate = realpath($root . '/' . $m[1]);
        if ($candidate && $root && strpos($candidate, $root . DIRECTORY_SEPARATOR) === 0 && is_file($candidate)) {
            $path = $candidate;
        }
    }
}

if (!$root || !$path) {
    http_response_code(404);
    echo 'Document not found.';
    exit;
}

$download = (($_GET['download'] ?? '') === '1');
$name = preg_replace('/[^a-zA-Z0-9._ -]/', '_', (string) ($attachment['file_name'] ?? 'progress-document'));
$mimeByExtension = [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];
$extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
header('Content-Type: ' . ($mimeByExtension[$extension] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . addslashes($name) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
