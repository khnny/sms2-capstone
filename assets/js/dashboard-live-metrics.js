/**
 * Dashboard live metrics (all roles)
 *
 * Polls the shared dashboard feed and refreshes the key metric cards and the
 * distribution donut in place, so every role dashboard stays accurate without
 * a reload. Values are real database counts.
 */
(function () {
    'use strict';

    var board = document.getElementById('glassBoard');
    if (!board) return;

    var POLL_MS = 5000;

    var apiBase = (function () {
        var path = window.location.pathname || '';
        var idx = path.indexOf('/dashboard/');
        if (idx === -1) return '/api/dashboard-metrics.php';
        return path.slice(0, idx) + '/api/dashboard-metrics.php';
    })();

    var lastFingerprint = '';

    function formatValue(value, kind) {
        var n = Number(value) || 0;
        if (kind === 'money') {
            return '₱' + n.toLocaleString('en-PH', { maximumFractionDigits: 0 });
        }
        if (kind === 'text') {
            return '—';
        }
        return n.toLocaleString('en-PH', { maximumFractionDigits: 0 });
    }

    function flash(el) {
        if (!el) return;
        var card = el.closest ? el.closest('.perf-item') : null;
        if (!card) return;
        card.classList.add('updated');
        window.setTimeout(function () { card.classList.remove('updated'); }, 1200);
    }

    function applyStats(stats, formats) {
        if (!stats) return;
        board.querySelectorAll('[data-cod-value]').forEach(function (el) {
            var key = el.getAttribute('data-cod-value');
            if (!key || stats[key] === undefined) return;
            var next = formatValue(stats[key], formats ? formats[key] : 'count');
            if (el.textContent !== next) {
                el.textContent = next;
                flash(el);
            }
        });
    }

    function applyDonut(rows, total, label) {
        if (!Array.isArray(rows) || !rows.length) return;

        var center = document.querySelector('.donut-center strong');
        if (center) center.textContent = String(total);
        var centerLabel = document.querySelector('.donut-center span');
        if (centerLabel && label) centerLabel.textContent = label;

        var legend = document.querySelector('.glass-legend');
        if (legend) {
            var palette = ['#3b82f6', '#8b5cf6', '#22c55e', '#f59e0b', '#06b6d4', '#ef4444'];
            legend.innerHTML = rows.map(function (row, i) {
                var n = parseInt(row.total, 10) || 0;
                var pct = total > 0 ? Math.round((n / total) * 100) : 0;
                var name = String(row.label || '');
                return '<li><span class="leg-left">'
                    + '<span class="dot" style="background:' + palette[i % palette.length] + '"></span>'
                    + '<span class="name">' + name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</span></span>'
                    + '<span class="pct">' + pct + '%</span></li>';
            }).join('');
        }

        if (window.smsGlassDonut && typeof window.smsGlassDonut.update === 'function') {
            window.smsGlassDonut.update(rows.map(function (row) {
                return { label: String(row.label || ''), value: parseInt(row.total, 10) || 0 };
            }));
        }
    }

    function poll(isInitial) {
        if (!isInitial && document.hidden) return;

        fetch(apiBase, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json' }
        })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || !data.ok) return;

                var fp = data.fingerprint || '';
                if (!isInitial && lastFingerprint !== '' && fp !== lastFingerprint) {
                    board.classList.add('gaw-dashboard-dock-updated');
                    window.setTimeout(function () {
                        board.classList.remove('gaw-dashboard-dock-updated');
                    }, 1200);
                }
                lastFingerprint = fp;

                applyStats(data.stats, data.formats);
                applyDonut(data.donut_rows, data.donut_total, data.donut_label);
            })
            .catch(function () { /* silent */ });
    }

    poll(true);
    window.setInterval(function () { poll(false); }, POLL_MS);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) poll(false);
    });
})();
