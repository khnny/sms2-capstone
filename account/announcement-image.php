<?php
/**
 * SMS 2 – Serve announcement PNG to authenticated students/admins.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/announcements.php';
require_once ROOT_PATH . '/includes/uploads.php';

requireAuth();

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(400);
    exit('Invalid request.');
}

$role = getCurrentUserRoleKey();
$isAdmin = smsIsGrantedAdminRole($role);
$isStudent = $role === 'student';
if (!$isAdmin && !$isStudent) {
    http_response_code(403);
    exit('Forbidden');
}

smsEnsureAnnouncementTables();
$pdo = db();
if (!$pdo) {
    http_response_code(500);
    exit('Database error.');
}

try {
    $stmt = $pdo->prepare(
        'SELECT image_path, status, audience
           FROM `sms2_admin_announcements`
          WHERE id = ?
          LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    error_log('announcement-image: ' . $e->getMessage());
    http_response_code(500);
    exit('Database error.');
}

if (!$row || empty($row['image_path'])) {
    http_response_code(404);
    exit('Image not found.');
}

if ($isStudent && (($row['status'] ?? '') !== 'published' || ($row['audience'] ?? '') !== 'student')) {
    http_response_code(404);
    exit('Image not found.');
}

$realPath = smsAnnouncementImagePath((string) $row['image_path']);
if ($realPath === null) {
    http_response_code(404);
    exit('Image not found.');
}

header('Content-Type: image/png');
header('Content-Length: ' . (string) filesize($realPath));
header('Content-Disposition: inline; filename="announcement.png"');
header('Cache-Control: private, max-age=60');
header('X-Content-Type-Options: nosniff');
readfile($realPath);
exit;
