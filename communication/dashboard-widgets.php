<?php
require_once ROOT_PATH . '/config/database.php';
require_once __DIR__ . '/event-provider.php';
$communicationTimezone = new DateTimeZone('Asia/Manila');
$communicationToday = new DateTimeImmutable('today', $communicationTimezone);
$communicationEventsUnavailable = false;
try {
    $communicationUpcomingEvents = smsCalendarUpcomingEvents(
        $communicationToday,
        $communicationToday->modify('+14 days'),
        ['Deadline', 'Defense']
    );
} catch (Throwable $e) {
    error_log('Communication dashboard calendar load failed: ' . $e->getMessage());
    $communicationUpcomingEvents = [];
    $communicationEventsUnavailable = true;
}
usort(
    $communicationUpcomingEvents,
    static fn(array $a, array $b): int => strcmp($a['starts_at'], $b['starts_at'])
);
$communicationUpcomingEvents = array_slice($communicationUpcomingEvents, 0, 3);
$communicationRecentActivity = [];
try {
    $activityPdo = db();
    if ($activityPdo instanceof PDO) {
        $activityStmt = $activityPdo->prepare(
            'SELECT action, module_key, detail, created_at
               FROM `sms2_activity_logs`
              WHERE user_id = ?
              ORDER BY created_at DESC, id DESC
              LIMIT 4'
        );
        $activityStmt->execute([(int) getCurrentUserId()]);
        $communicationRecentActivity = $activityStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {
    error_log('communication dashboard activity: ' . $e->getMessage());
}
?>
<section class="communication-dashboard" aria-label="Research calendar and recent activity">
    <div class="communication-dashboard-heading">
        <div>
            <span class="communication-kicker">Plan ahead</span>
            <h2>Research activity</h2>
        </div>
    </div>
    <div class="communication-dashboard-grid">
        <section class="communication-dashboard-panel" aria-labelledby="dashboardUpcomingTitle">
            <div class="communication-panel-heading">
                <div>
                    <h3 id="dashboardUpcomingTitle">Upcoming research events</h3>
                    <p>Research deadlines and defense schedules</p>
                </div>
                <a href="<?= e(BASE_URL . '/communication/calendar.php') ?>">View calendar <span aria-hidden="true">→</span></a>
            </div>
            <?php if ($communicationUpcomingEvents): ?>
                <ul class="communication-upcoming-list">
                    <?php foreach ($communicationUpcomingEvents as $event): ?>
                        <li>
                            <time datetime="<?= e($event['date']) ?>">
                                <strong><?= e((new DateTimeImmutable($event['date'], $communicationTimezone))->format('M d')) ?></strong>
                                <span><?= e((new DateTimeImmutable($event['date'], $communicationTimezone))->format('D')) ?></span>
                            </time>
                            <div>
                                <strong><?= e($event['title']) ?></strong>
                                <span><?= e($event['start_time']) ?><?= $event['end_time'] !== '' ? ' – ' . e($event['end_time']) : '' ?> · <?= e($event['location']) ?></span>
                            </div>
                            <span class="communication-type-pill <?= e(strtolower($event['type'])) ?>"><?= e($event['type']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="communication-empty"><?= $communicationEventsUnavailable ? 'Upcoming events are temporarily unavailable.' : 'No upcoming events.' ?></p>
            <?php endif; ?>
        </section>
        <section class="communication-dashboard-panel" aria-labelledby="dashboardActivityTitle">
            <div class="communication-panel-heading">
                <div><h3 id="dashboardActivityTitle">Recent activity</h3><p>Latest updates from your account</p></div>
            </div>
            <?php if ($communicationRecentActivity): ?>
                <ul class="communication-recent-activity">
                    <?php foreach ($communicationRecentActivity as $activity): ?>
                        <li>
                            <span class="communication-activity-icon"><?= smsIcon('history', ['aria-hidden' => 'true']) ?></span>
                            <div><strong><?= e(ucfirst(str_replace('_', ' ', (string) $activity['action']))) ?></strong><p><?= e((string) $activity['detail']) ?></p></div>
                            <time datetime="<?= e((string) $activity['created_at']) ?>"><?= e(date('M j, g:i A', strtotime((string) $activity['created_at']))) ?></time>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="communication-empty">Your recent actions will appear here.</p>
            <?php endif; ?>
        </section>
    </div>
</section>
