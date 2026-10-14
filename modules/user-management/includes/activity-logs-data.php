<?php
/**
 * SMS 2 – Super Admin activity logs live feed.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once __DIR__ . '/activity-logs-query.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (!isAuthenticated() || !userCanAccessModule('user-management')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

// The request needs the session only for authorization. Releasing its lock
// immediately prevents this frequent polling endpoint from delaying writes
// that generate new activity-log records in another request.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

echo json_encode(['ok' => true] + umActivityLogsPayload(db()), JSON_UNESCAPED_UNICODE);
