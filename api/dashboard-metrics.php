<?php
/**
 * SMS 2 - Dashboard live metrics (all roles)
 *
 * Returns the signed-in role's live dashboard figures so every dashboard can
 * refresh in place without a page reload. Read-only.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/dashboard-metrics.php';

requireAuth();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$roleKey = getCurrentUserRoleKey();
$cards   = smsDashStatCards($roleKey);
$values  = [];
$formats = [];
foreach ($cards as $card) {
    $key = (string) ($card['liveKey'] ?? '');
    if ($key === '') {
        continue;
    }
    $values[$key] = smsDashLiveValue(smsDashDb(), $key);
    $formats[$key] = smsDashIsMoney($key) ? 'money' : ((string) ($card['value'] ?? '') === '—' ? 'text' : 'count');
}

$widgets = smsDashWidgets($roleKey);

echo json_encode([
    'ok'          => true,
    'role'        => $roleKey,
    'stats'       => $values,
    'formats'     => $formats,
    'donut_rows'  => $widgets['donut_rows'],
    'donut_total' => $widgets['donut_total'],
    'donut_label' => $widgets['donut_label'],
    'legend'      => $widgets['legend'],
    'last_sync'   => date('M j, Y g:i:s A'),
    'fingerprint' => md5(json_encode([$values, $widgets['donut_rows']]) ?: ''),
], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
