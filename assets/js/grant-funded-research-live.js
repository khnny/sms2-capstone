/**
 * SMS 2 – Real-time researcher funded research dashboard
 */
(function () {
    'use strict';

    var POLL_MS = 5000;
    var root = document.querySelector('[data-grant-funded-research-live="1"]');
    if (!root) return;

    var selectedId = parseInt(root.getAttribute('data-selected-id') || '0', 10) || 0;
    var canSubmit = root.getAttribute('data-can-submit') === '1';
    var apiBase = (function () {
        var path = window.location.pathname || '';
        var idx = path.indexOf('/modules/');
        if (idx === -1) return '/modules/crad/api/grant-funded-research.php';
        return path.slice(0, idx) + '/modules/crad/api/grant-funded-research.php';
    })();

    var projectSelect = document.getElementById('gfrProjectSelect');
    var detailPanel = document.getElementById('gfrDetailPanel');
    var evidenceDialog = document.getElementById('gfrEvidenceDialog');
    var evidenceForm = document.getElementById('gfrEvidenceForm');
    var evidenceApplicationId = document.getElementById('gfrEvidenceApplicationId');
    var evidenceMilestone = document.getElementById('gfrEvidenceMilestone');
    var evidenceTitleInput = document.getElementById('gfrEvidenceTitleInput');
    var evidenceNotes = document.getElementById('gfrEvidenceNotes');
    var evidenceFile = document.getElementById('gfrEvidenceFile');
    var evidenceSubmitBtn = document.getElementById('gfrEvidenceSubmitBtn');

    var lastOverviewFp = '';
    var lastDetailFp = '';
    var lastOverviewCount = null;
    var paused = false;
    var submitting = false;
    var currentDetail = null;

    function esc(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fileHref(url) {
        return String(url || '').replace(/\/modules\/crad\/modules\/crad\//g, '/modules/crad/');
    }

    function formatPeso(amount) {
        var n = Number(amount) || 0;
        return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function formatDueDate(value) {
        if (!value) return '—';
        try {
            return new Date(value + 'T00:00:00').toLocaleDateString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric'
            });
        } catch (e) {
            return value;
        }
    }

    function formatDateTime(value) {
        if (!value) return '—';
        try {
            return new Date(value.replace(' ', 'T')).toLocaleString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric',
                hour: 'numeric', minute: '2-digit'
            });
        } catch (e) {
            return value;
        }
    }

    function statusClass(status) {
        if (status === 'Completed' || status === 'Released') return 'completed';
        if (status === 'In Progress' || status === 'Submitted') return 'in-progress';
        return 'pending';
    }

    function setSubmitButtonBusy(busy) {
        if (!evidenceSubmitBtn) return;
        evidenceSubmitBtn.disabled = !!busy;
        evidenceSubmitBtn.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    function forceHideLoader() {
        if (window.SMS2Loader && typeof window.SMS2Loader.forceHide === 'function') {
            window.SMS2Loader.forceHide();
        }
    }

    function updateStats(overview, detail) {
        var funded = document.querySelector('[data-gfr-funded-count]');
        var pending = document.querySelector('[data-gfr-pending-count]');
        if (funded && Array.isArray(overview)) funded.textContent = String(overview.length);
        if (pending && detail) {
            pending.textContent = String((detail.pending_requirements || []).length);
        }
    }

    function renderProjectSelect(overview) {
        if (!projectSelect || !Array.isArray(overview) || overview.length === 0) return;

        var current = selectedId;
        projectSelect.innerHTML = overview.map(function (row) {
            var appId = parseInt(row.grant_application_id, 10) || 0;
            var ref = esc(row.proposal_reference || 'Proposal');
            var title = esc(row.research_title || 'Untitled');
            var label = esc(row.progress_label || '');
            var selected = appId === current ? ' selected' : '';
            return '<option value="' + appId + '"' + selected + '>' + ref + ': ' + title + ' — ' + label + '</option>';
        }).join('');

        if (current <= 0 && overview.length > 0) {
            selectedId = parseInt(overview[0].grant_application_id, 10) || 0;
            projectSelect.value = String(selectedId);
            root.setAttribute('data-selected-id', String(selectedId));
        }
    }

    function buildTimeline(timeline) {
        return (timeline || []).map(function (item) {
            var cls = esc(item.status_class || statusClass(item.status));
            var due = item.due_date ? '<span>Due ' + esc(formatDueDate(item.due_date)) + '</span>' : '';
            return '<div class="gfr-timeline-item ' + cls + '">' +
                '<div class="gfr-timeline-dot"></div>' +
                '<div class="gfr-timeline-content">' +
                '<strong>' + esc(item.name) + '</strong>' +
                '<span class="gfr-status ' + cls + '">' + esc(item.status || 'Pending') + '</span>' +
                '<div class="gfr-timeline-meta">' +
                '<span>' + esc(Math.round(Number(item.completion_pct || 0))) + '% complete</span>' + due +
                '</div></div></div>';
        }).join('');
    }

    function buildRequirements(requirements) {
        if (!requirements || requirements.length === 0) {
            return '<p class="gfr-muted">No pending requirements. You are on track.</p>';
        }
        return requirements.map(function (req) {
            var milestoneAttr = req.milestone_id ? ' data-milestone-id="' + esc(req.milestone_id) + '"' : '';
            var actionClass = req.level === 'action' ? ' gfrRequirementAction' : '';
            return '<div class="gfr-requirement ' + esc(req.level || 'info') + actionClass + '"' + milestoneAttr + '>' +
                '<strong>' + esc(req.label) + '</strong>' +
                '<span>' + esc(req.detail) + '</span></div>';
        }).join('');
    }

    function buildMilestoneRows(milestones) {
        return (milestones || []).map(function (m) {
            var status = m.status || 'Pending';
            var cls = statusClass(status);
            var doc = m.has_document
                ? '<a href="' + esc(fileHref(m.document_url)) + '" target="_blank" rel="noopener"><i class="ti ti-file"></i> ' +
                    esc(m.supporting_doc_original || 'View') + '</a>'
                : '—';
            return '<tr><td style="font-weight:700;">' + esc(m.milestone_name) + '</td>' +
                '<td>' + esc(formatDueDate(m.due_date)) + '</td>' +
                '<td><strong>' + esc(Math.round(Number(m.completion_pct || 0))) + '%</strong></td>' +
                '<td><span class="gfr-status ' + cls + '">' + esc(status) + '</span></td>' +
                '<td>' + doc + '</td></tr>';
        }).join('');
    }

    function buildTrancheRows(tranches) {
        return (tranches || []).map(function (t) {
            var status = t.status || 'Pending';
            var cls = statusClass(status);
            return '<tr><td style="font-weight:700;">' + esc(t.tranche_label || ('Tranche ' + (t.tranche_number || ''))) + '</td>' +
                '<td>' + esc(formatPeso(t.amount_released)) + '</td>' +
                '<td><span class="gfr-status ' + cls + '">' + esc(status) + '</span></td>' +
                '<td>' + esc(t.release_date || '—') + '</td>' +
                '<td>' + esc(t.reference_number || '—') + '</td></tr>';
        }).join('');
    }

    function buildEvidenceRows(evidence) {
        if (!evidence || evidence.length === 0) {
            return '<tr><td colspan="5" class="gfr-muted" style="text-align:center;padding:1.25rem;">No evidence submitted yet.</td></tr>';
        }
        return evidence.map(function (row) {
            var file = row.has_file
                ? '<a href="' + esc(fileHref(row.file_url)) + '" target="_blank" rel="noopener"><i class="ti ti-file"></i> ' +
                    esc(row.file_original || 'View') + '</a>'
                : '—';
            return '<tr><td>' + esc(formatDateTime(row.created_at)) + '</td>' +
                '<td>' + esc(row.milestone_name || '—') + '</td>' +
                '<td>' + esc(row.evidence_title || '') + '</td>' +
                '<td>' + file + '</td>' +
                '<td><span class="gfr-status in-progress">' + esc(row.status || 'Submitted') + '</span></td></tr>';
        }).join('');
    }

    function renderDetail(detail) {
        if (!detailPanel) return;
        currentDetail = detail;

        if (!detail || !detail.application) {
            detailPanel.innerHTML = '<div class="gfr-panel"><div class="gfr-empty"><i class="ti ti-tasks" style="font-size:2rem;color:#cbd5e1;"></i>' +
                '<p style="margin:.5rem 0 0;">Select a funded grant to view details.</p></div></div>';
            canSubmit = false;
            root.setAttribute('data-can-submit', '0');
            return;
        }

        var app = detail.application;
        var ref = esc(app.proposal_reference || 'Proposal');
        var showSubmit = !!detail.can_submit_evidence;
        canSubmit = showSubmit;
        root.setAttribute('data-can-submit', showSubmit ? '1' : '0');

        var submitBtn = showSubmit
            ? '<button type="button" class="gfr-btn gfr-btn-primary gfrSubmitEvidenceBtn">' +
                '<i class="ti ti-upload"></i> Submit Progress Evidence</button>'
            : '';

        detailPanel.innerHTML =
            '<div class="gfr-detail" data-application-id="' + esc(app.grant_application_id) + '">' +
            '<div class="gfr-panel gfr-grant-card">' +
            '<div class="gfr-grant-head"><div><h2 class="gfr-panel-title">' + ref + '</h2>' +
            '<p class="gfr-grant-subtitle">' + esc(app.research_title || '—') + '</p></div>' +
            '<span class="gfr-funded-badge"><i class="ti ti-circle-check"></i> APPROVED &amp; FUNDED</span></div>' +
            '<div class="gfr-summary-grid">' +
            '<div class="gfr-summary-card"><span>Grant Program</span><strong>' + esc(app.funding_title || '—') + '</strong></div>' +
            '<div class="gfr-summary-card"><span>Approved Budget</span><strong>' + esc(formatPeso(detail.approved_budget)) + '</strong></div>' +
            '<div class="gfr-summary-card released"><span>Total Released</span><strong>' + esc(formatPeso(detail.total_released)) + '</strong></div>' +
            '<div class="gfr-summary-card pending"><span>Balance Pending</span><strong>' + esc(formatPeso(detail.balance_pending)) + '</strong></div>' +
            '<div class="gfr-summary-card"><span>Funding Status</span><strong>' + esc(detail.funding_status_label || '') + '</strong></div>' +
            '<div class="gfr-summary-card"><span>Overall Progress</span><strong>' + esc(Number(app.avg_completion_pct || 0).toFixed(1)) + '%</strong></div>' +
            '</div><div class="gfr-link-row">' +
            '<a class="gfr-btn gfr-btn-ghost" href="' + esc(detail.milestones_url || '#') + '"><i class="ti ti-list-check"></i> Project Milestones</a>' +
            '<a class="gfr-btn gfr-btn-ghost" href="' + esc(detail.disbursement_url || '#') + '"><i class="ti ti-cash"></i> Fund Releases</a>' +
            submitBtn + '</div></div>' +
            '<div class="gfr-grid-2">' +
            '<div class="gfr-panel"><h3 class="gfr-section-title"><i class="ti ti-timeline me-1"></i>Project Timeline</h3>' +
            '<div class="gfr-timeline">' + buildTimeline(detail.timeline) + '</div></div>' +
            '<div class="gfr-panel"><h3 class="gfr-section-title"><i class="ti ti-alert-circle me-1"></i>Pending Requirements</h3>' +
            '<div class="gfr-requirements" id="gfrRequirements">' + buildRequirements(detail.pending_requirements) + '</div></div></div>' +
            '<div class="gfr-panel"><h3 class="gfr-section-title"><i class="ti ti-layers-intersect me-1"></i>Milestones</h3>' +
            '<div class="gfr-table-wrap"><table class="gfr-table"><thead><tr><th>Milestone</th><th>Due Date</th><th>Completion %</th><th>Status</th><th>Document</th></tr></thead>' +
            '<tbody>' + buildMilestoneRows(detail.milestones) + '</tbody></table></div></div>' +
            '<div class="gfr-panel"><h3 class="gfr-section-title"><i class="ti ti-cash me-1"></i>Fund Releases</h3>' +
            '<div class="gfr-table-wrap"><table class="gfr-table"><thead><tr><th>Tranche</th><th>Amount</th><th>Status</th><th>Release Date</th><th>Reference</th></tr></thead>' +
            '<tbody>' + buildTrancheRows(detail.tranches) + '</tbody></table></div></div>' +
            '<div class="gfr-panel"><h3 class="gfr-section-title"><i class="ti ti-folder me-1"></i>Your Progress Evidence</h3>' +
            '<div class="gfr-table-wrap"><table class="gfr-table"><thead><tr><th>Submitted</th><th>Milestone</th><th>Title</th><th>File</th><th>Status</th></tr></thead>' +
            '<tbody>' + buildEvidenceRows(detail.evidence) + '</tbody></table></div></div></div>';

        bindDetailActions();
        populateMilestoneOptions(detail.milestones);
        if (evidenceApplicationId) {
            evidenceApplicationId.value = String(app.grant_application_id || selectedId);
        }
    }

    function populateMilestoneOptions(milestones, selectedMilestoneId) {
        if (!evidenceMilestone) return;
        var options = '<option value="">Select milestone…</option>';
        (milestones || []).forEach(function (m) {
            if ((m.status || '') === 'Completed') return;
            var id = parseInt(m.id, 10) || 0;
            var selected = selectedMilestoneId && id === selectedMilestoneId ? ' selected' : '';
            options += '<option value="' + id + '"' + selected + '>' + esc(m.milestone_name || ('Milestone ' + id)) + '</option>';
        });
        evidenceMilestone.innerHTML = options;
    }

    function openEvidenceDialog(milestoneId) {
        if (!evidenceDialog || !canSubmit) return;
        if (evidenceTitleInput) evidenceTitleInput.value = '';
        if (evidenceNotes) evidenceNotes.value = '';
        if (evidenceFile) evidenceFile.value = '';
        if (currentDetail) populateMilestoneOptions(currentDetail.milestones, milestoneId || 0);
        setSubmitButtonBusy(false);
        submitting = false;
        paused = true;
        evidenceDialog.classList.add('show');
    }

    function closeEvidenceDialog() {
        if (!evidenceDialog) return;
        evidenceDialog.classList.remove('show');
        paused = false;
        submitting = false;
        setSubmitButtonBusy(false);
    }

    function bindDetailActions() {
        document.querySelectorAll('.gfrSubmitEvidenceBtn').forEach(function (btn) {
            btn.addEventListener('click', function () { openEvidenceDialog(0); });
        });
        document.querySelectorAll('.gfrRequirementAction').forEach(function (el) {
            el.addEventListener('click', function () {
                var milestoneId = parseInt(el.getAttribute('data-milestone-id') || '0', 10) || 0;
                openEvidenceDialog(milestoneId);
            });
        });
    }

    function fetchOverview(isPoll) {
        var url = apiBase + '?action=get_overview';
        if (selectedId > 0) url += '&id=' + encodeURIComponent(selectedId);

        return fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data || !data.success) return;

                var overview = data.overview || [];
                if (lastOverviewCount !== null && overview.length !== lastOverviewCount) {
                    window.location.reload();
                    return;
                }
                lastOverviewCount = overview.length;

                var overviewFp = data.overview_fingerprint || '';
                var detailFp = data.detail_fingerprint || '';
                var overviewChanged = isPoll && lastOverviewFp !== '' && overviewFp !== lastOverviewFp;
                var detailChanged = isPoll && lastDetailFp !== '' && detailFp !== lastDetailFp;
                var shouldRender = !isPoll || overviewChanged || detailChanged;

                if (shouldRender) {
                    updateStats(overview, data.detail);
                    renderProjectSelect(overview);
                    if (data.detail) renderDetail(data.detail);
                }

                lastOverviewFp = overviewFp;
                lastDetailFp = detailFp;

                if (overviewChanged || detailChanged) flashUpdate();
            })
            .catch(function () {});
    }

    function flashUpdate() {
        var panel = detailPanel && detailPanel.querySelector('.gfr-panel');
        if (!panel) return;
        panel.classList.add('gfr-panel-updated');
        setTimeout(function () { panel.classList.remove('gfr-panel-updated'); }, 1200);
    }

    if (projectSelect) {
        projectSelect.addEventListener('change', function () {
            selectedId = parseInt(projectSelect.value, 10) || 0;
            root.setAttribute('data-selected-id', String(selectedId));
            fetchOverview(false);
        });
    }

    function parseJsonResponse(response) {
        return response.text().then(function (text) {
            if (!text) {
                return { success: false, message: 'Empty server response.' };
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                return { success: false, message: 'Server error while submitting evidence. Please try again.' };
            }
        });
    }

    if (evidenceForm) {
        evidenceForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (submitting) return;

            submitting = true;
            setSubmitButtonBusy(true);

            var formData = new FormData(evidenceForm);
            formData.append('action', 'submit_evidence');

            fetch(apiBase + '?action=submit_evidence', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            })
                .then(function (r) {
                    return parseJsonResponse(r).then(function (data) {
                        if (!r.ok && data && !data.message) {
                            data.message = 'Submission failed.';
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    if (!data || !data.success) {
                        alert((data && data.message) || 'Submission failed.');
                        forceHideLoader();
                        return;
                    }
                    closeEvidenceDialog();
                    lastOverviewFp = data.overview_fingerprint || lastOverviewFp;
                    lastDetailFp = data.detail_fingerprint || lastDetailFp;
                    if (data.overview) {
                        updateStats(data.overview, data.detail);
                        renderProjectSelect(data.overview);
                    }
                    if (data.detail) renderDetail(data.detail);
                    flashUpdate();
                    if (typeof window.SMSRefreshNotifications === 'function') {
                        window.SMSRefreshNotifications();
                    }
                })
                .catch(function () {
                    alert('Submission request failed. Check your connection and try again.');
                    forceHideLoader();
                })
                .finally(function () {
                    submitting = false;
                    setSubmitButtonBusy(false);
                });
        });
    }

    document.getElementById('gfrEvidenceCancelBtn')?.addEventListener('click', closeEvidenceDialog);

    bindDetailActions();
    fetchOverview(false);

    function poll() {
        if (paused || document.hidden || submitting) return;
        if (evidenceDialog && evidenceDialog.classList.contains('show')) return;
        fetchOverview(true);
    }

    setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) poll();
    });
})();
