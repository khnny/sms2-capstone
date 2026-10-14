<?php
declare(strict_types=1);

require_once ROOT_PATH . '/communication/event-provider.php';

/**
 * Backward-compatible defense-only projection for existing callers.
 *
 * @return list<array<string, mixed>>
 */
function smsCommunicationFinalizedDefenseEvents(): array
{
    $timezone = new DateTimeZone('Asia/Manila');
    $today = new DateTimeImmutable('today', $timezone);
    return array_values(array_filter(
        smsCalendarEvents($today, $today->modify('+366 days')),
        static fn(array $event): bool => ($event['source_type'] ?? '') === 'defense'
    ));
}

/**
 * Backward-compatible entry point; static fixture input is intentionally ignored.
 *
 * @param list<array<string, mixed>> $events
 * @return list<array<string, mixed>>
 */
function smsCommunicationEventsWithOfficialDefenses(array $events): array
{
    unset($events);
    $timezone = new DateTimeZone('Asia/Manila');
    $today = new DateTimeImmutable('today', $timezone);
    return smsCalendarEvents($today->modify('-366 days'), $today->modify('+366 days'));
}
