<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
requireAuth();

$pageTitle = 'Research Calendar';
$activeModule = '';
$activePage = 'communication-calendar';
$breadcrumbs = [['label' => 'Communication', 'url' => null], ['label' => 'Research Calendar', 'url' => null]];
require_once ROOT_PATH . '/includes/breadcrumbs.php';
$calendarTimezone = new DateTimeZone('Asia/Manila');
$calendarMonthLabel = (new DateTimeImmutable('first day of this month', $calendarTimezone))->format('F Y');
?>
<?php require_once ROOT_PATH . '/includes/layout-start.php'; ?>
<?php $eventJson = '[]'; ?>
<?php renderBreadcrumbs($breadcrumbs); ?>

<main class="communication-prototype" data-communication-page="calendar"
      data-events-url="<?= e(BASE_URL . '/communication/calendar-events.php') ?>"
      aria-busy="true">
    <header class="communication-page-header">
        <div>
            <span class="communication-kicker">Research communication</span>
            <h1>Research calendar &amp; bulletin</h1>
            <p>Authorized CRAD defense schedules and research deadlines from their source records.</p>
        </div>
    </header>

    <section class="communication-calendar-toolbar" aria-label="Calendar controls">
        <div class="communication-calendar-month-nav">
            <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-prev aria-label="Previous month"><?= smsIcon('chevron-left', ['aria-hidden' => 'true']) ?></button>
            <h2 data-calendar-month-label><?= e($calendarMonthLabel) ?></h2>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-next aria-label="Next month"><?= smsIcon('chevron-right', ['aria-hidden' => 'true']) ?></button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-calendar-today>Today</button>
        </div>
        <div class="communication-view-switch" role="group" aria-label="Calendar view">
            <button type="button" class="active" data-calendar-view="month" aria-pressed="true"><?= smsIcon('calendar', ['aria-hidden' => 'true']) ?> Month</button>
            <button type="button" data-calendar-view="list" aria-pressed="false"><?= smsIcon('list', ['aria-hidden' => 'true']) ?> List</button>
        </div>
    </section>

    <section class="communication-surface communication-calendar-filters" aria-labelledby="calendarFilterTitle">
        <div class="communication-surface-heading">
            <div><h2 id="calendarFilterTitle">Filter events</h2><p>Filters apply to both month and list views.</p></div>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-event-reset>Reset filters</button>
        </div>
        <div class="communication-filters">
            <label>Event type
                <select class="form-select" data-event-filter="type">
                    <option value="">All event types</option>
                    <option>Deadline</option><option>Defense</option>
                </select>
            </label>
            <label>Audience
                <select class="form-select" data-event-filter="audience">
                    <option value="">All audiences</option>
                    <option>Research students</option><option>Research students &amp; panel</option><option>Faculty &amp; panel</option><option>Everyone</option>
                </select>
            </label>
            <label>Status
                <select class="form-select" data-event-filter="status">
                    <option value="">All statuses</option><option>Scheduled</option><option>Rescheduled</option><option>Cancelled</option><option>Canceled</option><option>Finalized</option><option>Final</option><option>Active</option><option>Not Started</option><option>In Progress</option><option>Submitted for Review</option><option>Revision Requested</option><option>Approved</option><option>Completed</option>
                </select>
            </label>
            <label>From date<input class="form-control" type="date" data-event-filter="from"></label>
            <label>To date<input class="form-control" type="date" data-event-filter="to"></label>
            <label class="communication-filter-search">Research group
                <input class="form-control" type="search" data-event-filter="group" placeholder="Group, project, or all groups">
            </label>
        </div>
    </section>

    <section class="communication-calendar-surface" aria-label="Research event calendar">
        <div class="communication-loading" data-calendar-loading role="status" aria-live="polite">
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Loading calendar…
        </div>
        <div class="communication-calendar-legend">
            <span><i class="deadline"></i> Deadline</span><span><i class="defense"></i> Defense</span>
        </div>
        <div class="communication-calendar-grid" data-calendar-grid role="grid" aria-label="Monthly research calendar"></div>
        <div class="communication-event-list" data-event-list hidden></div>
        <div class="communication-empty-state" data-event-empty hidden>
            <span><?= smsIcon('calendar-times', ['aria-hidden' => 'true']) ?></span>
            <strong>No events match these filters</strong>
            <p>Change the event type, audience, date range, or research group.</p>
            <button type="button" class="btn btn-sm btn-outline-primary" data-event-reset>Clear filters</button>
        </div>
    </section>
    <div class="communication-live-status" data-calendar-status role="status" aria-live="polite"></div>
    <script type="application/json" data-calendar-events><?= $eventJson ?: '[]' ?></script>
</main>

<div class="modal fade" id="communicationEventModal" tabindex="-1" aria-labelledby="communicationEventTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content communication-event-modal">
            <div class="modal-header">
                <div><span class="communication-kicker">Research event</span><h2 class="modal-title" id="communicationEventTitle">Event details</h2></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-event-details></div>
            <div class="modal-footer"><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
<script src="<?= e(BASE_URL . '/assets/js/communication-prototype.js?v=3') ?>" defer></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
