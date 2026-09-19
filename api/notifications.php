<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/notifications.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Authentication required.']);
    exit;
}

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    smsMarkCurrentUserNotificationRead((int) ($_POST['notification_id'] ?? 0));
    smsMarkCurrentUserSyntheticNotificationRead((string) ($_POST['batch_key'] ?? ''));
}

$items = smsNotificationPayloadForCurrentUser();
echo json_encode([
    'ok' => true,
    'count' => count($items),
    'items' => $items,
    'user' => [
        'name' => getCurrentUserName(),
        'role' => getCurrentUserRole(),
        'email' => (string) ($_SESSION['user_email'] ?? ''),
    ],
]);
