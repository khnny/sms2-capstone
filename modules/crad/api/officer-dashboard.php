<?php
/**
 * SMS 2 - CRAD Officer dashboard live feed
 *
 * Returns the CRAD Officer's own module figures as JSON so the dashboard can
 * refresh in place without a page reload. Read-only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once __DIR__ . '/../includes/officer-dashboard-helpers.php';

requireAuth();

$role = getCurrentUserRoleKey();
if ($role !== 'crad_officer' && !smsIsGrantedAdminRole($role)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Forbidden'], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$data = cradOfficerDashboardData();
$data['last_sync'] = date('M j, Y g:i:s A');

echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
