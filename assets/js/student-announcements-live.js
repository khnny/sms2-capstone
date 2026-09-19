/**
 * SMS 2 – Student dashboard live announcements
 */
(function () {
    var root = document.getElementById('studentAnnouncements');
    if (!root || !window.fetch) return;

    var endpoint = root.getAttribute('data-live-url') || '';
    if (!endpoint) return;

    var listEl = document.getElementById('studentAnnouncementsList');
    var emptyEl = document.getElementById('studentAnnouncementsEmpty');
    var badge = document.getElementById('studentAnnLiveBadge');
    var stamp = root.getAttribute('data-stamp') || '';
    var inFlight = false;

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setLive(ok) {
        if (!badge) return;
        badge.classList.toggle('is-stale', !ok);
        var label = badge.querySelector('[data-live-label]');
        if (label) label.textContent = ok ? 'Live' : 'Reconnecting';
    }

    function render(rows) {
        if (!listEl) return;
        if (!rows || !rows.length) {
            listEl.innerHTML = '';
            if (emptyEl) emptyEl.hidden = false;
            return;
        }
        if (emptyEl) emptyEl.hidden = true;
        listEl.innerHTML = rows.map(function (row) {
            var image = row.image_url
                ? '<img class="student-ann-image" src="' + esc(row.image_url) + '" alt="">'
                : '';
            return '<article class="student-ann-item">'
                + '<h3>' + esc(row.title) + '</h3>'
                + image
                + '<p>' + esc(row.body).replace(/\n/g, '<br>') + '</p>'
                + '<small>' + esc(row.posted_by || 'Admin') + ' · ' + esc(row.posted_at || '') + '</small>'
                + '</article>';
        }).join('');
    }

    function poll() {
        if (inFlight || document.hidden) return;
        inFlight = true;
        fetch(endpoint, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || !data.ok) {
                    setLive(false);
                    return;
                }
                setLive(true);
                if (data.stamp === stamp) return;
                stamp = data.stamp;
                root.setAttribute('data-stamp', stamp);
                render(data.announcements || []);
            })
            .catch(function () { setLive(false); })
            .finally(function () { inFlight = false; });
    }

    poll();
    setInterval(poll, 3000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) poll();
    });
})();
