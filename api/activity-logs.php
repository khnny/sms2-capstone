<?php
/**
 * SMS 2 – Super Admin activity-log live feed.
 *
 * Kept under /api so shared-host security rules do not treat it as an
 * executable file inside an includes directory.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/user-management/includes/activity-logs-query.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isAuthenticated() || !userCanAccessModule('user-management')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

// The session is needed only for authorization. Releasing its lock prevents
// the two-second polling request from delaying audit writes in other requests.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

echo json_encode(['ok' => true] + umActivityLogsPayload(db()), JSON_UNESCAPED_UNICODE);
