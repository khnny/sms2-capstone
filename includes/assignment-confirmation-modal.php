<?php
/**
 * Global dual-assignment confirmation modal (Coordinator / Adviser).
 * Shown on layout pages when the logged-in user has a cycle awaiting their Accept/Decline.
 * Navbar bell also lists pending Approve Assignment; Later keeps the item in the bell.
 */
declare(strict_types=1);

if (!function_exists('getCurrentUserRoleKey') || !function_exists('isAuthenticated') || !isAuthenticated()) {
    return;
}

$smsAssignConfirmRole = getCurrentUserRoleKey();
if (!in_array($smsAssignConfirmRole, ['adviser', 'research_coordinator'], true)) {
    return;
}

$smsAssignConfirmApi = rtrim((string) BASE_URL, '/') . '/api/assignment-confirmation.php';
$smsAssignConfirmCsrf = function_exists('csrfToken') ? csrfToken() : '';
$smsAssignConfirmRoleLabel = $smsAssignConfirmRole === 'adviser' ? 'Adviser' : 'Coordinator';
?>
<style>
.sms-assign-confirm-modal .modal-content {
    border: 0;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 1rem 2.5rem rgba(15, 23, 42, 0.28);
}
.sms-assign-confirm-modal .modal-header {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 55%, #0ea5e9 100%);
    color: #fff;
    border-bottom: 0;
    padding: 1rem 1.25rem;
}
.sms-assign-confirm-modal .modal-header .btn-close {
    filter: invert(1) grayscale(1);
    opacity: 0.85;
}
.sms-assign-confirm-modal .sms-assign-confirm-kicker {
    display: block;
    font-size: 0.75rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    opacity: 0.9;
    margin-bottom: 0.15rem;
}
.sms-assign-confirm-modal .modal-title {
    font-size: 1.1rem;
    font-weight: 700;
}
.sms-assign-confirm-modal .sms-assign-confirm-meta {
    display: grid;
    gap: 0.65rem;
}
.sms-assign-confirm-modal .sms-assign-confirm-row {
    display: grid;
    grid-template-columns: 8.5rem 1fr;
    gap: 0.5rem;
    align-items: start;
}
.sms-assign-confirm-modal .sms-assign-confirm-label {
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 600;
}
.sms-assign-confirm-modal .sms-assign-confirm-value {
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 600;
    word-break: break-word;
}
.sms-assign-confirm-modal .sms-assign-confirm-note {
    margin-top: 0.85rem;
    padding: 0.75rem 0.9rem;
    border-radius: 0.65rem;
    background: #eff6ff;
    color: #1e3a8a;
    font-size: 0.875rem;
}
.sms-assign-confirm-modal .sms-assign-confirm-alert {
    display: none;
    margin-bottom: 0.75rem;
}
.sms-assign-confirm-modal .modal-footer {
    border-top: 1px solid #e2e8f0;
    gap: 0.5rem;
}
[data-theme="dark"] .sms-assign-confirm-modal .sms-assign-confirm-value {
    color: #e2e8f0;
}
[data-theme="dark"] .sms-assign-confirm-modal .sms-assign-confirm-label {
    color: #94a3b8;
}
[data-theme="dark"] .sms-assign-confirm-modal .sms-assign-confirm-note {
    background: rgba(37, 99, 235, 0.18);
    color: #bfdbfe;
}
[data-theme="dark"] .sms-assign-confirm-modal .modal-footer {
    border-top-color: rgba(148, 163, 184, 0.25);
}
</style>

<div class="modal fade sms-assign-confirm-modal" id="smsAssignConfirmModal" tabindex="-1" aria-labelledby="smsAssignConfirmTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <span class="sms-assign-confirm-kicker">Assignment Confirmation</span>
          <h5 class="modal-title" id="smsAssignConfirmTitle">Approve or Decline Assignment</h5>
        </div>
        <button type="button" class="btn-close" id="smsAssignConfirmClose" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert sms-assign-confirm-alert" id="smsAssignConfirmAlert" role="alert"></div>
        <div class="sms-assign-confirm-meta" id="smsAssignConfirmMeta">
          <div class="sms-assign-confirm-row">
            <span class="sms-assign-confirm-label">Research Title</span>
            <span class="sms-assign-confirm-value" data-field="research_title">—</span>
          </div>
          <div class="sms-assign-confirm-row">
            <span class="sms-assign-confirm-label">Group</span>
            <span class="sms-assign-confirm-value" data-field="group_name">—</span>
          </div>
          <div class="sms-assign-confirm-row">
            <span class="sms-assign-confirm-label">Student / Leader</span>
            <span class="sms-assign-confirm-value" data-field="leader_name">—</span>
          </div>
          <div class="sms-assign-confirm-row">
            <span class="sms-assign-confirm-label">Your Role</span>
            <span class="sms-assign-confirm-value" data-field="your_role"><?= htmlspecialchars($smsAssignConfirmRoleLabel, ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <div class="sms-assign-confirm-row">
            <span class="sms-assign-confirm-label">Assigned By</span>
            <span class="sms-assign-confirm-value" data-field="assigned_by_label">Department Head</span>
          </div>
          <div class="sms-assign-confirm-row">
            <span class="sms-assign-confirm-label">Coordinator</span>
            <span class="sms-assign-confirm-value" data-field="coordinator_name">—</span>
          </div>
          <div class="sms-assign-confirm-row">
            <span class="sms-assign-confirm-label">Adviser</span>
            <span class="sms-assign-confirm-value" data-field="adviser_name">—</span>
          </div>
          <div class="sms-assign-confirm-row">
            <span class="sms-assign-confirm-label">Status</span>
            <span class="sms-assign-confirm-value" data-field="overall_label">Pending Confirmation</span>
          </div>
        </div>
        <div class="sms-assign-confirm-note">
          Fully Assigned only after both Coordinator and Adviser accept. If either declines, status becomes Needs Reassignment.
        </div>
        <div class="mt-3" id="smsAssignConfirmDeclineWrap" hidden>
          <label for="smsAssignConfirmReason" class="form-label">Decline reason (optional)</label>
          <textarea class="form-control" id="smsAssignConfirmReason" rows="2" placeholder="Optional reason for the Department Head"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="smsAssignConfirmDismiss">Later</button>
        <button type="button" class="btn btn-outline-danger" id="smsAssignConfirmDecline">Decline</button>
        <button type="button" class="btn btn-success" id="smsAssignConfirmAccept">Accept</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
    'use strict';

    var apiUrl = <?= json_encode($smsAssignConfirmApi, JSON_UNESCAPED_SLASHES) ?>;
    var csrfToken = <?= json_encode($smsAssignConfirmCsrf, JSON_UNESCAPED_SLASHES) ?>;
    var queue = [];
    var current = null;
    var busy = false;
    var modalEl = document.getElementById('smsAssignConfirmModal');
    if (!modalEl || !window.bootstrap) {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var alertEl = document.getElementById('smsAssignConfirmAlert');
    var reasonEl = document.getElementById('smsAssignConfirmReason');
    var declineWrap = document.getElementById('smsAssignConfirmDeclineWrap');
    var acceptBtn = document.getElementById('smsAssignConfirmAccept');
    var declineBtn = document.getElementById('smsAssignConfirmDecline');

    function setAlert(type, message) {
        if (!alertEl) return;
        if (!message) {
            alertEl.style.display = 'none';
            alertEl.textContent = '';
            alertEl.className = 'alert sms-assign-confirm-alert';
            return;
        }
        alertEl.className = 'alert sms-assign-confirm-alert alert-' + (type || 'info');
        alertEl.textContent = message;
        alertEl.style.display = 'block';
    }

    var dismissBtn = document.getElementById('smsAssignConfirmDismiss');

    function setBusy(state) {
        busy = !!state;
        if (acceptBtn) acceptBtn.disabled = busy;
        if (declineBtn) declineBtn.disabled = busy;
        if (dismissBtn) dismissBtn.disabled = busy;
    }

    function fillFields(item) {
        var map = {
            research_title: item.research_title || 'Pending Title',
            group_name: item.group_name || '—',
            leader_name: item.leader_name || '—',
            your_role: item.your_role || '',
            assigned_by_label: item.assigned_by_label || 'Department Head',
            coordinator_name: (item.coordinator_name || '—') + (item.coordinator_confirmed ? ' (accepted)' : ' (pending)'),
            adviser_name: (item.adviser_name || '—') + (item.adviser_confirmed ? ' (accepted)' : ' (pending)'),
            overall_label: item.overall_label || 'Pending Confirmation'
        };
        Object.keys(map).forEach(function (key) {
            var el = modalEl.querySelector('[data-field="' + key + '"]');
            if (el) el.textContent = map[key];
        });
        if (reasonEl) reasonEl.value = '';
        if (declineWrap) declineWrap.hidden = true;
        setAlert('', '');
    }

    function showNext() {
        current = queue.length ? queue[0] : null;
        if (!current) {
            modal.hide();
            return;
        }
        fillFields(current);
        modal.show();
    }

    function applyItems(items) {
        queue = Array.isArray(items) ? items.slice() : [];
        showNext();
    }

    async function loadPending() {
        try {
            var res = await fetch(apiUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            var data = await res.json();
            if (!data || !data.ok) {
                return;
            }
            if (data.csrf_token) {
                csrfToken = data.csrf_token;
            }
            applyItems(data.items || []);
        } catch (err) {
            // Silent: modal is progressive enhancement over assignment-confirmation.php
        }
    }

    async function submitAction(action) {
        if (!current || busy) return;
        setBusy(true);
        setAlert('', '');
        try {
            var body = new FormData();
            body.append('csrf_token', csrfToken || '');
            body.append('cycle_id', String(current.cycle_id || 0));
            body.append('action', action);
            if (action === 'decline' || action === 'cancel') {
                body.append('cancel_reason', reasonEl ? reasonEl.value : '');
            }
            // later/dismiss: no extra fields
            var res = await fetch(apiUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
                body: body
            });
            var data = await res.json();
            if (data && data.csrf_token) {
                csrfToken = data.csrf_token;
            }
            if (!data || !data.ok) {
                setAlert('danger', (data && data.message) ? data.message : 'Action failed.');
                setBusy(false);
                return;
            }
            setAlert('success', data.message || 'Saved.');
            if (typeof window.SMSRefreshNotifications === 'function') {
                try { window.SMSRefreshNotifications(); } catch (e) {}
            }
            var delay = (action === 'later' || action === 'dismiss') ? 250 : 700;
            setTimeout(function () {
                setBusy(false);
                applyItems(data.items || []);
            }, delay);
        } catch (err) {
            setAlert('danger', 'Network error. Please try again or open Assignment Confirmation.');
            setBusy(false);
        }
    }

    if (acceptBtn) {
        acceptBtn.addEventListener('click', function () {
            submitAction('accept');
        });
    }
    if (declineBtn) {
        declineBtn.addEventListener('click', function () {
            if (declineWrap && declineWrap.hidden) {
                declineWrap.hidden = false;
                if (reasonEl) reasonEl.focus();
                setAlert('warning', 'Optionally add a reason, then click Decline again to confirm.');
                return;
            }
            submitAction('decline');
        });
    }
    if (dismissBtn) {
        dismissBtn.addEventListener('click', function () {
            // Later: close modal for this cycle (session) and keep it in Notifications.
            submitAction('later');
        });
    }
    var closeBtn = document.getElementById('smsAssignConfirmClose');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            // Same as Later: suppress modal for this cycle; bell keeps the request.
            submitAction('later');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadPending);
    } else {
        loadPending();
    }

    // Re-check periodically so a new DH assign surfaces without full reload.
    window.setInterval(function () {
        if (busy || (modalEl.classList.contains('show') && current)) {
            return;
        }
        loadPending();
    }, 15000);
})();
</script>
