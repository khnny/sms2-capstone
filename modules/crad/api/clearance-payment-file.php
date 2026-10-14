<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/research-services-clearance.php';
require_once ROOT_PATH . '/modules/crad/includes/research-clearance-payment.php';

requireAuth();

$crad = rscDb();
$paymentId = (int) ($_GET['id'] ?? 0);
if (!$crad instanceof PDO || $paymentId <= 0) {
    http_response_code(404);
    exit('File not found.');
}

$payment = rcpFindById($crad, $paymentId);
if (!$payment) {
    http_response_code(404);
    exit('File not found.');
}

$role = getCurrentUserRoleKey();
$allowed = rcpCanApprove();
if ($role === 'student') {
    $group = chapterRegisteredStudentGroup($crad);
    $allowed = $group && (int) $group['id'] === (int) ($payment['research_group_id'] ?? 0);
}
if (!$allowed) {
    http_response_code(403);
    exit('Access denied.');
}

$stored = rcpPersistentImageData($payment);
if ($stored !== null) {
    $data = $stored['data'];
    $mime = $stored['mime'];
} else {
    $file = basename(str_replace('\\', '/', (string) ($payment['uploaded_file'] ?? '')));
    $path = rcpPaymentImagePath($file);
    if ($file === '' || $file === '.' || $file === '..' || $path === null) {
        error_log('RCP payment image missing: id=' . $paymentId . ' file=' . $file);
        http_response_code(404);
        exit('File not found.');
    }
    $data = (string) @file_get_contents($path);
    $info = @getimagesize($path);
    $mime = strtolower((string) ($info['mime'] ?? ''));
}
$allowedMimes = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
if (!isset($allowedMimes[$mime])) {
    error_log('RCP payment image invalid: id=' . $paymentId . ' path=' . $path);
    http_response_code(415);
    exit('Unsupported file type.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) strlen($data));
header('Content-Disposition: inline; filename="payment.' . $allowedMimes[$mime] . '"');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
echo $data;
exit;
