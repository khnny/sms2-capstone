/**
 * SMS 2 — Client-side idle auto-logout
 * Logs out when there is no user interaction for the configured idle period.
 */
(function () {
    'use strict';

    var cfg = window.SMS_IDLE_LOGOUT;
    if (!cfg || !cfg.logoutUrl) {
        return;
    }

    var idleMs = Math.max(60, parseInt(cfg.idleSeconds, 10) || 120) * 1000;
    var logoutUrl = String(cfg.logoutUrl);
    var timer = null;
    var loggingOut = false;

    function goLogout() {
        if (loggingOut) {
            return;
        }
        loggingOut = true;
        window.location.href = logoutUrl;
    }

    function bump() {
        if (loggingOut) {
            return;
        }
        if (timer) {
            window.clearTimeout(timer);
        }
        timer = window.setTimeout(goLogout, idleMs);
    }

    var events = [
        'mousedown',
        'mousemove',
        'keydown',
        'scroll',
        'touchstart',
        'click',
        'wheel',
        'pointerdown',
    ];

    events.forEach(function (name) {
        document.addEventListener(name, bump, { capture: true, passive: true });
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            bump();
        }
    });

    bump();
})();
