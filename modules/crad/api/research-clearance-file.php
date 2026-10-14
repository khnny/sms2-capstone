<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/research-services-clearance.php';

requireAuth();

$crad = rscDb();
$clearanceId = (int) ($_GET['id'] ?? 0);
if (!$crad instanceof PDO || $clearanceId <= 0) {
    http_response_code(404);
    exit('File not found.');
}

$clearance = rscFindById($crad, $clearanceId);
if (!$clearance) {
    http_response_code(404);
    exit('File not found.');
}

$role = getCurrentUserRoleKey();
$allowed = smsIsGrantedAdminRole($role) || $role === 'crad_officer';
if ($role === 'student') {
    $group = chapterRegisteredStudentGroup($crad);
    $allowed = $group && (int) $group['id'] === (int) ($clearance['research_group_id'] ?? 0);
}
if (!$allowed) {
    http_response_code(403);
    exit('Access denied.');
}

$stored = rscPersistentImageData($clearance);
if ($stored !== null) {
    $data = $stored['data'];
    $mime = $stored['mime'];
} else {
    $path = rscUploadedImagePath((string) ($clearance['uploaded_file'] ?? ''));
    if ($path === null) {
        error_log('RSC clearance image missing: id=' . $clearanceId);
        http_response_code(404);
        exit('File not found.');
    }
    $data = (string) @file_get_contents($path);
    $info = @getimagesize($path);
    $mime = strtolower((string) ($info['mime'] ?? ''));
}
$allowedMimes = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
if (!isset($allowedMimes[$mime])) {
    error_log('RSC clearance image invalid: id=' . $clearanceId . ' path=' . $path);
    http_response_code(415);
    exit('Unsupported file type.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) strlen($data));
header('Content-Disposition: inline; filename="clearance.' . $allowedMimes[$mime] . '"');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
echo $data;
exit;
