<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();
require_once __DIR__ . '/event-provider.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$timezone = new DateTimeZone('Asia/Manila');
$startValue = trim((string) ($_GET['start'] ?? ''));
$endValue = trim((string) ($_GET['end'] ?? ''));
$start = DateTimeImmutable::createFromFormat('!Y-m-d', $startValue, $timezone);
$end = DateTimeImmutable::createFromFormat('!Y-m-d', $endValue, $timezone);
if (
    !$start
    || !$end
    || $start->format('Y-m-d') !== $startValue
    || $end->format('Y-m-d') !== $endValue
    || $end < $start
    || $start->diff($end)->days > 366
) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Choose a valid date range of no more than one year.']);
    exit;
}

try {
    echo json_encode(
        ['ok' => true, 'events' => smsCalendarEvents($start, $end)],
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
    );
} catch (Throwable $e) {
    error_log('Calendar event endpoint failed: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Calendar events are temporarily unavailable.']);
}
