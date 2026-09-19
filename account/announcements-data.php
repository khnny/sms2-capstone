<?php
/**
 * SMS 2 – Live announcements JSON (admin + students).
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/announcements.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (!isAuthenticated()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$role = getCurrentUserRoleKey();
$isAdmin = smsIsGrantedAdminRole($role);
$isStudent = $role === 'student';

if (!$isAdmin && !$isStudent) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$published = smsAnnouncementPublicRows(smsAnnouncementFetch(true, 20));
$payload = [
    'ok' => true,
    'stamp' => smsAnnouncementStamp($published),
    'synced_at' => date('M j, Y h:i:s A'),
    'announcements' => $published,
];

if ($isAdmin) {
    $all = smsAnnouncementFetch(false, 50);
    $payload['all'] = smsAnnouncementPublicRows($all);
    $payload['stamp'] = smsAnnouncementStamp($all);
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE);
