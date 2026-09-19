<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/uploads.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/research-progress-helpers.php';

requireAuth();

$crad = cradDb();
rpEnsureProgressAttachmentSchema($crad);
$attachmentId = (int) ($_GET['id'] ?? 0);
if ($attachmentId <= 0) {
    http_response_code(404);
    echo 'Document not found.';
    exit;
}

$stmt = $crad->prepare(
    "SELECT rpa.*, rpu.research_group_id, rpu.research_plan_id, rpu.milestone_id,
            rpu.submitted_by_user_id, rg.group_number
     FROM research_progress_attachments rpa
     INNER JOIN research_progress_updates rpu ON rpu.id = rpa.progress_update_id
     INNER JOIN research_groups rg ON rg.id = rpu.research_group_id
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

$root = realpath(smsUploadRoot());
$path = realpath((string) $attachment['file_path']);
if (!$root || !$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
    http_response_code(404);
    echo 'Document not found.';
    exit;
}

$download = (($_GET['download'] ?? '') === '1');
$name = preg_replace('/[^a-zA-Z0-9._ -]/', '_', (string) ($attachment['file_name'] ?? 'progress-document'));
header('Content-Type: ' . ((string) ($attachment['file_type'] ?? '') ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . addslashes($name) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
