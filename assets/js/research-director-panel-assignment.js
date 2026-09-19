(function () {
    var root = document.querySelector('[data-rd-panel-root]');
    if (!root) return;

    document.addEventListener('click', function (event) {
        var open = event.target.closest('[data-rd-panel-open-confirm]');
        if (open) {
            var modal = document.getElementById('rdPanelAssignConfirmModal');
            if (modal && window.bootstrap) window.bootstrap.Modal.getOrCreateInstance(modal).show();
        }
        var confirm = event.target.closest('[data-rd-panel-confirm-assign]');
        if (confirm) {
            var form = document.querySelector('[data-rd-panel-assign-form]');
            if (!form) return;
            confirm.disabled = true;
            form.submit();
        }
    });

    document.addEventListener('change', function (event) {
        if (!event.target.matches('input[name="panel_ids[]"]')) return;
        var endpoint = root.getAttribute('data-context-endpoint');
        var form = event.target.closest('form');
        updateSelectState(form);
        if (!endpoint || !form) return;
        var group = form.querySelector('input[name="group_id"], input[name="research_group_id"]');
        var token = form.querySelector('input[name="csrf_token"]');
        var data = new FormData();
        if (group) data.append('research_group_id', group.value || '');
        if (token) data.append('csrf_token', token.value || '');
        form.querySelectorAll('input[name="panel_ids[]"]:checked').forEach(function (input) {
            data.append('panel_ids[]', input.value);
        });
        fetch(endpoint, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: data
        }).catch(function () {});
    });

    function normalizedStatus(card) {
        var badge = card.querySelector('[data-rd-panel-availability]');
        return badge ? badge.textContent.trim().toLowerCase() : 'pending';
    }

    function updateSelectState(form) {
        form = form || document.querySelector('[data-rd-panel-select-form]');
        if (!form) return;

        var checkedCards = Array.prototype.slice.call(form.querySelectorAll('input[name="panel_ids[]"]:checked')).map(function (input) {
            return input.closest('[data-rd-panel-card]');
        }).filter(Boolean);
        var button = form.querySelector('[data-rd-panel-check-button]');
        var message = form.querySelector('[data-rd-panel-select-message]');
        var helper = '';
        var canContinue = false;

        if (!checkedCards.length) {
            helper = 'Select at least one available Panel Member to continue.';
        } else if (checkedCards.some(function (card) { return normalizedStatus(card) === 'unavailable'; })) {
            helper = 'One or more selected Panel Members are unavailable.';
        } else if (checkedCards.some(function (card) { return normalizedStatus(card) !== 'available'; })) {
            helper = 'Please wait until all selected Panel Members are available.';
        } else {
            canContinue = true;
        }

        if (button) button.disabled = !canContinue;
        if (message) message.textContent = helper;
    }

    function setBadgeClass(badge, badgeClass) {
        if (!badge) return;
        Array.prototype.slice.call(badge.classList).forEach(function (className) {
            if (className.indexOf('text-bg-') === 0) badge.classList.remove(className);
        });
        badge.classList.add('text-bg-' + (badgeClass || 'warning'));
    }

    function pollSelectionState() {
        var form = document.querySelector('[data-rd-panel-select-form]');
        var endpoint = root.getAttribute('data-selection-endpoint');
        if (!form || !endpoint) return;
        fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || !payload.ok || !Array.isArray(payload.panels)) return;
                var grid = form.querySelector('[data-rd-panel-grid]') || form.querySelector('.rdpa-panel-grid');
                var seen = {};
                payload.panels.forEach(function (panel) {
                    var id = String(panel.id || '');
                    seen[id] = true;
                    var card = form.querySelector('[data-rd-panel-card][data-panel-id="' + id + '"]');
                    if (!card && grid && panel.html) {
                        grid.insertAdjacentHTML('beforeend', panel.html);
                        card = form.querySelector('[data-rd-panel-card][data-panel-id="' + id + '"]');
                    }
                    if (!card) return;
                    var badge = card.querySelector('[data-rd-panel-availability]');
                    var assignments = card.querySelector('[data-rd-panel-assignments]');
                    var name = card.querySelector('[data-rd-panel-name]');
                    var email = card.querySelector('[data-rd-panel-email]');
                    var role = card.querySelector('[data-rd-panel-role]');
                    var expertise = card.querySelector('[data-rd-panel-expertise]');
                    if (name && panel.full_name) name.textContent = panel.full_name;
                    if (email && panel.email) email.textContent = panel.email;
                    if (role && panel.role_label) role.textContent = panel.role_label;
                    if (expertise && panel.expertise) expertise.textContent = panel.expertise;
                    if (badge) {
                        badge.textContent = panel.availability_status || 'Pending';
                        setBadgeClass(badge, panel.badge_class || 'warning');
                    }
                    if (assignments) assignments.textContent = String(panel.current_assignments || 0);
                });
                form.querySelectorAll('[data-rd-panel-card]').forEach(function (card) {
                    var id = card.getAttribute('data-panel-id') || '';
                    var checked = card.querySelector('input[name="panel_ids[]"]');
                    if (!seen[id] && (!checked || !checked.checked)) {
                        card.remove();
                    }
                });
                updateSelectState(form);
                var sync = root.querySelector('[data-rd-panel-sync]');
                if (sync) sync.textContent = 'Synced ' + (payload.synced_at || '');
            })
            .catch(function () {});
    }

    function poll() {
        var endpoint = root.getAttribute('data-endpoint');
        var content = root.querySelector('[data-rd-panel-content]');
        if (!endpoint || !content) return;
        fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || !payload.ok) return;
                content.innerHTML = payload.html || '';
                var sync = root.querySelector('[data-rd-panel-sync]');
                if (sync) sync.textContent = 'Synced ' + (payload.synced_at || '');
            })
            .catch(function () {});
    }

    function pollCheckAvailability() {
        var endpoint = root.getAttribute('data-check-endpoint');
        var content = root.querySelector('[data-rd-panel-check-content]');
        if (!endpoint || !content) return;
        fetch(endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload || !payload.ok) return;
                content.innerHTML = payload.html || '';
                var sync = root.querySelector('[data-rd-panel-sync]');
                if (sync) sync.textContent = 'Synced ' + (payload.synced_at || '');
            })
            .catch(function () {});
    }

    updateSelectState();
    pollSelectionState();
    pollCheckAvailability();
    window.setInterval(poll, 2000);
    window.setInterval(pollSelectionState, 2000);
    window.setInterval(pollCheckAvailability, 2000);
})();
