(function () {
    'use strict';

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[char];
        });
    }

    function dateFromIso(value) {
        var parts = String(value || '').split('-').map(Number);
        return parts.length === 3 ? new Date(parts[0], parts[1] - 1, parts[2]) : new Date(NaN);
    }

    function formatIso(value, options) {
        var date = dateFromIso(value);
        return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat(undefined, options).format(date);
    }

    function initAnnouncements(root) {
        var cards = Array.from(root.querySelectorAll('[data-announcement]'));
        var controls = Array.from(root.querySelectorAll('[data-ann-filter]'));
        var count = root.querySelector('[data-ann-count]');
        var empty = root.querySelector('[data-ann-empty]');
        var list = root.querySelector('[data-ann-list]');
        var status = root.querySelector('[data-ann-status]');

        function applyFilters() {
            var filters = {};
            controls.forEach(function (control) {
                filters[control.getAttribute('data-ann-filter')] = control.value.trim().toLowerCase();
            });

            if (filters.from && filters.to && filters.from > filters.to) {
                if (status) status.textContent = 'Choose a “Published to” date that is the same as or later than “Published from”.';
                cards.forEach(function (card) { card.hidden = true; });
                if (empty) empty.hidden = true;
                if (count) count.textContent = '0 shown';
                return;
            }
            if (status) status.textContent = '';

            var visible = 0;
            cards.forEach(function (card) {
                var date = card.getAttribute('data-date') || '';
                var matches = (!filters.category || (card.getAttribute('data-category') || '').toLowerCase() === filters.category)
                    && (!filters.audience || (card.getAttribute('data-audience') || '').toLowerCase() === filters.audience)
                    && (!filters.status || (card.getAttribute('data-status') || '').toLowerCase() === filters.status)
                    && (!filters.from || date >= filters.from)
                    && (!filters.to || date <= filters.to)
                    && (!filters.search || (card.getAttribute('data-search') || '').toLowerCase().indexOf(filters.search) !== -1);
                card.hidden = !matches;
                if (matches) visible++;
            });
            if (count) count.textContent = visible + ' of ' + cards.length + ' shown';
            if (empty) empty.hidden = visible !== 0;
            if (list) list.hidden = visible === 0;
        }

        controls.forEach(function (control) {
            control.addEventListener('input', applyFilters);
            control.addEventListener('change', applyFilters);
        });
        root.querySelectorAll('[data-ann-reset]').forEach(function (button) {
            button.addEventListener('click', function () {
                controls.forEach(function (control) { control.value = ''; });
                applyFilters();
            });
        });
        var announcementModal = document.getElementById('communicationAnnouncementModal');
        root.addEventListener('click', function (event) {
            var detailButton = event.target.closest('[data-ann-detail]');
            if (detailButton && announcementModal && window.bootstrap && window.bootstrap.Modal) {
                announcementModal.querySelector('#communicationAnnouncementTitle').textContent = detailButton.dataset.title || '';
                announcementModal.querySelector('[data-ann-modal-body]').textContent = detailButton.dataset.body || '';
                announcementModal.querySelector('[data-ann-modal-meta]').textContent = [
                    detailButton.dataset.category,
                    detailButton.dataset.status,
                    detailButton.dataset.audience,
                    detailButton.dataset.date,
                    detailButton.dataset.expiry ? 'Until ' + detailButton.dataset.expiry : '',
                    detailButton.dataset.author
                ].filter(Boolean).join(' · ');
                var announcementImage = announcementModal.querySelector('[data-ann-modal-image]');
                announcementImage.hidden = !detailButton.dataset.image;
                if (detailButton.dataset.image) announcementImage.src = detailButton.dataset.image;
                else announcementImage.removeAttribute('src');
                window.bootstrap.Modal.getOrCreateInstance(announcementModal).show();
            }
        });
        root.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm('Delete this announcement? This cannot be undone.')) event.preventDefault();
            });
        });
        root.setAttribute('aria-busy', 'false');
        applyFilters();
    }

    function initCalendar(root) {
        var eventsNode = root.querySelector('[data-calendar-events]');
        var calendarGrid = root.querySelector('[data-calendar-grid]');
        var eventList = root.querySelector('[data-event-list]');
        var empty = root.querySelector('[data-event-empty]');
        var loading = root.querySelector('[data-calendar-loading]');
        var status = root.querySelector('[data-calendar-status]');
        var monthLabel = root.querySelector('[data-calendar-month-label]');
        var controls = Array.from(root.querySelectorAll('[data-event-filter]'));
        var viewButtons = Array.from(root.querySelectorAll('[data-calendar-view]'));
        var modalElement = document.getElementById('communicationEventModal');
        var details = modalElement ? modalElement.querySelector('[data-event-details]') : null;
        var events = [];
        var view = 'month';
        var cursor = new Date();
        var eventsUrl = root.getAttribute('data-events-url') || '';
        var latestRequest = 0;
        var eventLoadFailed = false;
        var loadedRange = {
            start: new Date(cursor.getFullYear(), cursor.getMonth(), 1),
            end: new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0)
        };

        try {
            events = JSON.parse(eventsNode ? eventsNode.textContent : '[]');
            if (!Array.isArray(events)) throw new Error('Unexpected event data.');
        } catch (error) {
            if (status) status.textContent = 'Calendar could not load. Refresh the page to try again.';
            if (loading) loading.hidden = true;
            root.setAttribute('aria-busy', 'false');
            return;
        }

        function getFilteredEvents() {
            var filters = {};
            controls.forEach(function (control) {
                filters[control.getAttribute('data-event-filter')] = control.value.trim().toLowerCase();
            });
            return events.filter(function (event) {
                return (!filters.type || event.type.toLowerCase() === filters.type)
                    && (!filters.audience || event.audience.toLowerCase() === filters.audience)
                    && (!filters.status || event.status.toLowerCase() === filters.status)
                    && (!filters.from || event.date >= filters.from)
                    && (!filters.to || event.date <= filters.to)
                    && (!filters.group || event.research_group.toLowerCase().indexOf(filters.group) !== -1);
            });
        }

        function eventButton(event) {
            return '<button type="button" class="communication-calendar-event ' + escapeHtml(event.type.toLowerCase())
                + '" data-event-open="' + escapeHtml(event.id) + '" title="' + escapeHtml(event.title)
                + '" aria-label="' + escapeHtml(event.title + ', ' + formatIso(event.date, { dateStyle: 'full' }))
                + '">' + escapeHtml(event.start_time) + ' · ' + escapeHtml(event.title) + '</button>';
        }

        function renderMonth(filtered) {
            var year = cursor.getFullYear();
            var month = cursor.getMonth();
            var monthEvents = filtered.filter(function (event) {
                var date = dateFromIso(event.date);
                return date.getFullYear() === year && date.getMonth() === month;
            });
            if (monthLabel) {
                monthLabel.textContent = new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(cursor);
            }
            if (!calendarGrid) return monthEvents.length;

            var firstDay = new Date(year, month, 1);
            var mondayOffset = (firstDay.getDay() + 6) % 7;
            var start = new Date(year, month, 1 - mondayOffset);
            var daysInGrid = mondayOffset + new Date(year, month + 1, 0).getDate() > 35 ? 42 : 35;
            var today = new Date();
            var html = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map(function (day) {
                return '<div class="communication-calendar-weekday" role="columnheader">' + day + '</div>';
            }).join('');

            for (var i = 0; i < daysInGrid; i++) {
                var day = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
                var iso = [
                    day.getFullYear(),
                    String(day.getMonth() + 1).padStart(2, '0'),
                    String(day.getDate()).padStart(2, '0')
                ].join('-');
                var dayEvents = monthEvents.filter(function (event) { return event.date === iso; });
                var outside = day.getMonth() !== month;
                var isToday = day.toDateString() === today.toDateString();
                html += '<div class="communication-calendar-day' + (outside ? ' is-outside' : '')
                    + (isToday ? ' is-today' : '') + '" role="gridcell" aria-label="'
                    + escapeHtml(formatIso(iso, { dateStyle: 'full' })) + '"><time datetime="' + iso + '">'
                    + day.getDate() + '</time><div class="communication-calendar-day-events">'
                    + dayEvents.map(eventButton).join('') + '</div></div>';
            }
            calendarGrid.innerHTML = html;
            return monthEvents.length;
        }

        function renderList(filtered) {
            if (!eventList) return;
            var sorted = filtered.slice().sort(function (a, b) {
                return a.starts_at.localeCompare(b.starts_at);
            });
            eventList.innerHTML = sorted.map(function (event) {
                return '<article class="communication-event-list-card ' + escapeHtml(event.type.toLowerCase()) + '">'
                    + '<time class="communication-event-list-date" datetime="' + escapeHtml(event.date) + '">'
                    + escapeHtml(formatIso(event.date, { month: 'short', day: 'numeric' })) + '</time><div><h3>'
                    + escapeHtml(event.title) + '</h3><p>' + escapeHtml(event.start_time + ' · ' + event.location)
                    + ' · ' + escapeHtml(event.research_group) + '</p></div><button type="button" class="btn btn-sm btn-outline-primary" data-event-open="'
                    + escapeHtml(event.id) + '">Details</button></article>';
            }).join('');
        }

        function render() {
            var fromDate = '';
            var toDate = '';
            controls.forEach(function (control) {
                var key = control.getAttribute('data-event-filter');
                if (key === 'from') fromDate = control.value;
                if (key === 'to') toDate = control.value;
            });
            if (fromDate && toDate && fromDate > toDate) {
                if (status) status.textContent = 'Choose a To date that is the same as or later than From date.';
                if (loading) loading.hidden = true;
                if (calendarGrid) calendarGrid.hidden = true;
                if (eventList) eventList.hidden = true;
                if (empty) empty.hidden = true;
                return;
            }
            var filtered = getFilteredEvents();
            if (status) status.textContent = '';
            if (loading) loading.hidden = true;
            var monthCount = renderMonth(filtered);
            renderList(filtered);
            var visible = view === 'month' ? monthCount : filtered.length;
            if (calendarGrid) calendarGrid.hidden = view !== 'month' || visible === 0;
            if (eventList) eventList.hidden = view !== 'list' || visible === 0;
            if (empty) empty.hidden = visible !== 0 || eventLoadFailed;
        }

        function isoDate(date) {
            return [
                date.getFullYear(),
                String(date.getMonth() + 1).padStart(2, '0'),
                String(date.getDate()).padStart(2, '0')
            ].join('-');
        }

        function loadRange(start, end) {
            if (!eventsUrl) {
                if (status) status.textContent = 'Calendar could not load because its event service is unavailable.';
                return;
            }
            var requestId = ++latestRequest;
            eventLoadFailed = false;
            if (loading) loading.hidden = false;
            root.setAttribute('aria-busy', 'true');
            if (status) status.textContent = '';
            var query = new URLSearchParams({ start: isoDate(start), end: isoDate(end) });
            fetch(eventsUrl + '?' + query.toString(), { credentials: 'same-origin', headers: { Accept: 'application/json' } })
                .then(function (response) {
                    if (!response.ok) throw new Error('Calendar service returned ' + response.status + '.');
                    return response.json();
                })
                .then(function (payload) {
                    if (!payload || payload.ok !== true || !Array.isArray(payload.events)) {
                        throw new Error('Calendar service returned invalid event data.');
                    }
                    if (requestId !== latestRequest) return;
                    events = payload.events;
                    eventLoadFailed = false;
                    loadedRange = { start: start, end: end };
                    render();
                })
                .catch(function (error) {
                    if (requestId !== latestRequest) return;
                    console.error('Calendar event request failed:', error);
                    events = [];
                    eventLoadFailed = true;
                    render();
                    if (status) status.textContent = 'Calendar events could not be loaded. Refresh the page or try a different date range.';
                })
                .finally(function () {
                    if (requestId !== latestRequest) return;
                    if (loading) loading.hidden = true;
                    root.setAttribute('aria-busy', 'false');
                });
        }

        function reloadCalendarRange() {
            var from = '';
            var to = '';
            controls.forEach(function (control) {
                if (control.getAttribute('data-event-filter') === 'from') from = control.value;
                if (control.getAttribute('data-event-filter') === 'to') to = control.value;
            });
            if (from && to && from > to) {
                render();
                return;
            }

            var start;
            var end;
            if (from || to) {
                var startValue = from || to;
                var endValue = to || from;
                start = dateFromIso(startValue);
                end = dateFromIso(endValue);
                if (!from) start.setDate(start.getDate() - 366);
                if (!to) end.setDate(end.getDate() + 366);
            } else {
                start = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
                end = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0);
            }
            if (isoDate(start) !== isoDate(loadedRange.start) || isoDate(end) !== isoDate(loadedRange.end)) {
                loadRange(start, end);
            } else {
                render();
            }
        }

        function setView(nextView) {
            view = nextView;
            viewButtons.forEach(function (button) {
                var active = button.getAttribute('data-calendar-view') === view;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
            render();
        }

        function showDetails(id) {
            var event = events.find(function (item) { return item.id === id; });
            if (!event || !details || !modalElement) return;
            if (status) status.textContent = '';
            details.innerHTML = '<p class="communication-event-description">' + escapeHtml(event.description) + '</p>'
                + '<dl class="communication-event-details">'
                + '<div><dt>Date</dt><dd>' + escapeHtml(formatIso(event.date, { dateStyle: 'full' })) + '</dd></div>'
                + '<div><dt>Time</dt><dd>' + escapeHtml(event.start_time + (event.end_time ? ' – ' + event.end_time : '')) + '</dd></div>'
                + '<div><dt>Time zone</dt><dd>' + escapeHtml(event.timezone) + '</dd></div>'
                + '<div><dt>Location</dt><dd>' + escapeHtml(event.location) + '</dd></div>'
                + '<div><dt>Research group</dt><dd>' + escapeHtml(event.research_group) + '</dd></div>'
                + '<div><dt>Audience</dt><dd>' + escapeHtml(event.audience) + '</dd></div>'
                + '<div><dt>Status</dt><dd><span class="communication-status ' + escapeHtml(event.status.toLowerCase())
                + '">' + escapeHtml(event.status) + '</span></dd></div>'
                + '<div><dt>Event type</dt><dd>' + escapeHtml(event.type) + '</dd></div></dl>';
            var title = modalElement.querySelector('#communicationEventTitle');
            if (title) title.textContent = event.title;
            if (window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            } else {
                if (status) status.textContent = 'Event details dialog could not open. Refresh the page and try again.';
            }
        }

        controls.forEach(function (control) {
            var filter = control.getAttribute('data-event-filter');
            control.addEventListener('input', filter === 'from' || filter === 'to' ? reloadCalendarRange : render);
            control.addEventListener('change', filter === 'from' || filter === 'to' ? reloadCalendarRange : render);
        });
        viewButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setView(button.getAttribute('data-calendar-view'));
            });
        });
        root.querySelectorAll('[data-calendar-prev]').forEach(function (button) {
            button.addEventListener('click', function () {
                cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
                reloadCalendarRange();
            });
        });
        root.querySelectorAll('[data-calendar-next]').forEach(function (button) {
            button.addEventListener('click', function () {
                cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
                reloadCalendarRange();
            });
        });
        root.querySelectorAll('[data-calendar-today]').forEach(function (button) {
            button.addEventListener('click', function () {
                var today = new Date();
                cursor = new Date(today.getFullYear(), today.getMonth(), 1);
                reloadCalendarRange();
            });
        });
        root.querySelectorAll('[data-event-reset]').forEach(function (button) {
            button.addEventListener('click', function () {
                controls.forEach(function (control) { control.value = ''; });
                if (status) status.textContent = '';
                reloadCalendarRange();
            });
        });
        root.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-event-open]');
            if (trigger) showDetails(trigger.getAttribute('data-event-open'));
        });
        root.setAttribute('aria-busy', 'false');
        render();
        if (eventsUrl) loadRange(loadedRange.start, loadedRange.end);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-communication-page="announcements"]').forEach(initAnnouncements);
        document.querySelectorAll('[data-communication-page="calendar"]').forEach(initCalendar);
    });
})();
