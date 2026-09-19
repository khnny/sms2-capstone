<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/uploads.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/final-phase-helpers.php';
requireAuth();
$crad = cradDb();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $crad->prepare('SELECT ms.*, rg.leader_id FROM manuscript_submissions ms INNER JOIN research_groups rg ON rg.id = ms.research_group_id WHERE ms.id = ? LIMIT 1');
$stmt->execute([$id]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { http_response_code(404); exit('Document not found.'); }
$role = getCurrentUserRoleKey(); $allowed = smsRoleAllowedForModule(['crad_officer','research_coordinator','adviser'], 'crad');
if ($role === 'adviser') {
	$allowed = fpIsAssignedAdviser($crad, (int) $row['research_group_id'], (int) ($_SESSION['user_id'] ?? 0), (string) ($_SESSION['user_email'] ?? ''));
} elseif ($role === 'student' && (int) ($row['leader_id'] ?? 0) === (int) ($_SESSION['user_id'] ?? 0)) {
	$allowed = true;
}
if (!$allowed) { http_response_code(403); exit('Forbidden'); }
$subdir = trim((string) $row['stored_subdir'], '/'); $stored = basename((string) $row['stored_name']);
$root = realpath(smsUploadRoot()); $path = realpath(smsUploadRoot() . '/' . $subdir . '/' . $stored);
if (!$root || !$path || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) { http_response_code(404); exit('Document file not found.'); }
header('Content-Type: ' . ((string) $row['file_mime'] ?: 'application/octet-stream'));
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: inline; filename="' . addslashes((string) $row['original_name']) . '"');
readfile($path);
