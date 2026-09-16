/**
 * SMS 2 — attach CSRF token to mutating same-origin fetch() calls.
 * Server accepts X-CSRF-Token (see smsRequireMutatingCsrf).
 */
(function () {
    'use strict';

    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? (meta.getAttribute('content') || '') : '';
    window.SMS2_CSRF = token;

    if (!token || typeof window.fetch !== 'function') {
        return;
    }

    var originalFetch = window.fetch.bind(window);

    window.fetch = function (input, init) {
        init = init ? Object.assign({}, init) : {};
        var method = String(init.method || 'GET').toUpperCase();
        if (method === 'GET' || method === 'HEAD' || method === 'OPTIONS') {
            return originalFetch(input, init);
        }

        var headers;
        try {
            headers = new Headers(init.headers || {});
        } catch (e) {
            headers = new Headers();
        }
        if (!headers.has('X-CSRF-Token')) {
            headers.set('X-CSRF-Token', token);
        }
        init.headers = headers;
        return originalFetch(input, init);
    };
})();
