/**
 * SMS 2 – Theme Manager (light / dark)
 * Persists preference and updates icons.
 * Does NOT call Chart.update() — that triggers Chart.js getLabels crashes on v4 proxies.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'sms2-theme';
    var VALID = { light: true, dark: true };

    function normalize(theme) {
        return VALID[theme] ? theme : 'light';
    }

    function getStoredTheme() {
        try {
            return normalize(localStorage.getItem(STORAGE_KEY));
        } catch (e) {
            return 'light';
        }
    }

    function storeTheme(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) { /* private mode / blocked storage */ }
    }

    function cssVar(name, fallback) {
        var value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return value || fallback;
    }

    function sanitizeColor(value, fallback) {
        if (typeof value !== 'string') return fallback;
        value = value.trim();
        if (!value || value.indexOf('var(') !== -1) return fallback;
        if (/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i.test(value)) return value;
        if (/^rgba?\(/i.test(value) || /^hsla?\(/i.test(value)) return value;
        return fallback;
    }

    /** Safe: only set Chart.defaults — never mutate chart.options or call update(). */
    function applyChartDefaults() {
        if (typeof Chart === 'undefined') return;
        try {
            Chart.defaults.color = sanitizeColor(cssVar('--sms-chart-text', '#64748b'), '#64748b');
        } catch (e) { /* ignore */ }
    }

    function applyTheme(theme, options) {
        theme = normalize(theme);
        var root = document.documentElement;
        root.setAttribute('data-theme', theme);
        root.setAttribute('data-bs-theme', theme);
        root.style.colorScheme = theme;
        if (!options || options.silent !== true) {
            root.style.backgroundColor = '';
        }

        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            var next = theme === 'dark' ? 'light' : 'dark';
            btn.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
            btn.setAttribute('title', theme === 'dark' ? 'Light mode' : 'Dark mode');
            btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
            btn.dataset.nextTheme = next;
        });

        applyChartDefaults();

        if (!options || options.silent !== true) {
            try {
                window.dispatchEvent(new CustomEvent('sms2:themechange', { detail: { theme: theme } }));
            } catch (e) { /* older browsers */ }
        }

        return theme;
    }

    function toggleTheme() {
        var current = document.documentElement.getAttribute('data-theme') || getStoredTheme();
        var next = current === 'dark' ? 'light' : 'dark';
        storeTheme(next);
        applyTheme(next);
        return next;
    }

    function bindToggles() {
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            if (btn.dataset.themeBound === '1') return;
            btn.dataset.themeBound = '1';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                toggleTheme();
            });
        });
    }

    // Guard Chart.update against getLabels / proxy crashes (uncaught in resize paths)
    function patchChartUpdate() {
        if (typeof Chart === 'undefined' || !Chart.prototype || Chart.prototype._smsUpdatePatched) return;
        var original = Chart.prototype.update;
        if (typeof original !== 'function') return;
        Chart.prototype.update = function () {
            try {
                if (!this || !this.ctx || this.destroyed) return this;
                return original.apply(this, arguments);
            } catch (err) {
                return this;
            }
        };
        Chart.prototype._smsUpdatePatched = true;
    }

    window.SMS2Theme = {
        get: function () {
            return document.documentElement.getAttribute('data-theme') || getStoredTheme();
        },
        set: function (theme) {
            theme = normalize(theme);
            storeTheme(theme);
            return applyTheme(theme);
        },
        toggle: toggleTheme,
        // Kept for callers — defaults only, no chart.update()
        refreshCharts: applyChartDefaults
    };

    patchChartUpdate();
    applyTheme(document.documentElement.getAttribute('data-theme') || getStoredTheme(), { silent: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bindToggles();
            patchChartUpdate();
            document.documentElement.style.backgroundColor = '';
            applyTheme(getStoredTheme(), { silent: true });
        });
    } else {
        bindToggles();
        patchChartUpdate();
        document.documentElement.style.backgroundColor = '';
    }
})();
