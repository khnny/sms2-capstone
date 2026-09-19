(function () {
    var root = document.querySelector('[data-panel-live]');
    if (!root) return;

    function validateScores(form, showMessage) {
        var inputs = form.querySelectorAll('.js-panel-score');
        var totalMax = parseFloat(form.getAttribute('data-total-max') || '100');
        var errorEl = form.querySelector('#panelScoreError');
        var output = form.querySelector('[data-panel-overall]');
        var openBtn = form.querySelector('[data-panel-open-confirm]');
        var sum = 0;
        var firstError = '';

        inputs.forEach(function (input) {
            var max = parseFloat(input.getAttribute('data-max') || input.max || '20');
            var label = input.getAttribute('data-label') || 'Score';
            var raw = String(input.value || '').trim();
            var n = raw === '' ? NaN : parseFloat(raw);
            var message = '';
            if (raw !== '' && (isNaN(n) || n < 0)) {
                message = label + ' Score must be 0 to ' + max + '.';
            } else if (!isNaN(n) && n > max) {
                message = label + ' Score cannot exceed ' + max + '%. Evaluation cannot be submitted.';
            }
            if (!isNaN(n)) sum += n;
            input.classList.toggle('is-invalid', !!message);
            input.setCustomValidity(message);
            var feedback = input.parentElement ? input.parentElement.querySelector('.js-score-feedback') : null;
            if (feedback) feedback.textContent = message;
            if (message && !firstError) firstError = message;
        });

        if (!firstError && sum > totalMax) {
            firstError = 'Total score cannot exceed ' + totalMax + '%. Evaluation cannot be submitted.';
        }

        if (output) output.value = sum.toFixed(2);
        if (errorEl) {
            errorEl.textContent = showMessage || firstError ? (firstError || '') : '';
            errorEl.classList.toggle('d-none', !errorEl.textContent);
        }
        if (openBtn) openBtn.disabled = !!firstError;
        return !firstError;
    }

    function bindScoreForm(form) {
        if (!form || form.dataset.panelScoreBound === '1') return;
        form.dataset.panelScoreBound = '1';
        form.querySelectorAll('.js-panel-score').forEach(function (input) {
            input.addEventListener('input', function () { validateScores(form, true); });
            input.addEventListener('change', function () { validateScores(form, true); });
        });
        validateScores(form, false);
    }

    document.addEventListener('input', function (event) {
        var form = event.target.closest('[data-panel-evaluation-form]');
        if (form) validateScores(form, true);
    });

    document.addEventListener('click', function (event) {
        var open = event.target.closest('[data-panel-open-confirm]');
        if (open) {
            var form = open.closest('form');
            if (!form) return;
            if (!validateScores(form, true) || !form.reportValidity()) return;
            var modalEl = document.getElementById('panelSubmitConfirmModal');
            if (modalEl && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            } else {
                form.submit();
            }
        }

        var confirm = event.target.closest('[data-panel-confirm-submit]');
        if (confirm) {
            var form = document.querySelector('[data-panel-evaluation-form]');
            if (!form || !validateScores(form, true) || !form.reportValidity()) return;
            confirm.disabled = true;
            form.submit();
        }
    });

    function poll() {
        var endpoint = root.getAttribute('data-endpoint');
        var mode = root.getAttribute('data-panel-live');
        if (!endpoint || mode === 'scoring') return;
        var current = new URL(window.location.href);
        var url = endpoint + '?mode=' + encodeURIComponent(mode === 'history' ? 'history' : (mode === 'details' ? 'details' : 'assigned'));
        if (mode === 'details' && current.searchParams.get('id')) {
            url += '&id=' + encodeURIComponent(current.searchParams.get('id'));
        }
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || !payload.ok) return;
                var content = root.querySelector('[data-panel-content]');
                var count = root.querySelector('[data-panel-count]');
                if (content && typeof payload.html === 'string') content.innerHTML = payload.html;
                if (count) count.textContent = payload.count + ' Record' + (payload.count === 1 ? '' : 's');
            })
            .catch(function () {});
    }

    bindScoreForm(document.querySelector('[data-panel-evaluation-form]'));
    window.setInterval(poll, 10000);
})();
