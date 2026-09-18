/**
 * SMS 2 – Real-time funded project milestones polling
 */
(function () {
    'use strict';

    var POLL_MS = 5000;
    var root = document.querySelector('[data-grant-milestones-live="1"]');
    if (!root) return;

    var selectedId = parseInt(root.getAttribute('data-selected-id') || '0', 10) || 0;
    var canTrack = root.getAttribute('data-can-track') === '1';
    var apiBase = (function () {
        var path = window.location.pathname || '';
        var idx = path.indexOf('/modules/');
        if (idx === -1) return '/modules/crad/api/grant-milestones.php';
        return path.slice(0, idx) + '/modules/crad/api/grant-milestones.php';
    })();

    var projectSelect = document.getElementById('gpmProjectSelect');
    var detailPanel = document.getElementById('gpmDetailPanel');
    var editDialog = document.getElementById('gpmEditDialog');
    var editForm = document.getElementById('gpmEditForm');
    var editMilestoneId = document.getElementById('gpmEditMilestoneId');
    var editMilestoneName = document.getElementById('gpmEditMilestoneName');
    var editDueDate = document.getElementById('gpmEditDueDate');
    var editCompletion = document.getElementById('gpmEditCompletion');
    var editStatus = document.getElementById('gpmEditStatus');
    var editRemarks = document.getElementById('gpmEditRemarks');
    var editDocument = document.getElementById('gpmEditDocument');
    var editSaveBtn = document.getElementById('gpmEditSaveBtn');

    var lastOverviewFp = '';
    var lastDetailFp = '';
    var lastOverviewCount = null;
    var paused = false;
    var submitting = false;

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

    function statusClass(status) {
        if (status === 'Completed') return 'completed';
        if (status === 'In Progress') return 'in-progress';
        return 'pending';
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

    function setSaveButtonBusy(busy) {
        if (!editSaveBtn) return;
        editSaveBtn.disabled = !!busy;
        editSaveBtn.setAttribute('aria-busy', busy ? 'true' : 'false');
    }

    function forceHideLoader() {
        if (window.SMS2Loader && typeof window.SMS2Loader.forceHide === 'function') {
            window.SMS2Loader.forceHide();
        }
    }

    function applyMilestoneResponse(data) {
        if (!data || !data.success) return false;

        closeEditDialog();
        lastOverviewFp = data.overview_fingerprint || lastOverviewFp;
        lastDetailFp = data.detail_fingerprint || lastDetailFp;
        if (data.overview) {
            updateStats(data.overview);
            renderProjectSelect(data.overview);
        }
        if (data.detail) renderDetail(data.detail);
        flashUpdate();

        if (data.message) {
            alert(data.message);
        }

        return true;
    }

    function updateStats(overview) {
        var funded = document.querySelector('[data-gpm-funded-count]');
        var active = document.querySelector('[data-gpm-active-count]');
        if (!Array.isArray(overview)) return;
        if (funded) funded.textContent = String(overview.length);
        if (active) {
            var count = overview.filter(function (row) {
                return String(row.progress_label || '') === 'In Progress';
            }).length;
            active.textContent = String(count);
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

    function buildEvidenceRows(evidence) {
        if (!evidence || evidence.length === 0) {
            return '<tr><td colspan="5" class="gpm-muted" style="text-align:center;padding:1.25rem;">No researcher evidence submitted yet.</td></tr>';
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
                '<td><span class="gpm-status in-progress">' + esc(row.status || 'Submitted') + '</span></td></tr>';
        }).join('');
    }

    function formatDateTime(value) {
        if (!value) return '—';
        try {
            return new Date(String(value).replace(' ', 'T')).toLocaleString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric',
                hour: 'numeric', minute: '2-digit'
            });
        } catch (e) {
            return value;
        }
    }

    function buildMilestoneRows(milestones, canTrackRows) {
        return milestones.map(function (milestone) {
            var status = milestone.status || 'Pending';
            var docHtml = milestone.has_document
                ? '<a href="' + esc(fileHref(milestone.document_url)) + '" target="_blank" rel="noopener"><i class="ti ti-file"></i> '
                    + esc(milestone.supporting_doc_original || 'View') + '</a>'
                : '—';
            var remarks = esc(milestone.remarks || '');
            var remarksHtml = remarks ? remarks.replace(/\n/g, '<br>') : '—';
            var actionHtml = canTrackRows
                ? '<button type="button" class="gpm-btn gpm-btn-edit gpmEditMilestoneBtn" '
                    + 'data-milestone-id="' + esc(milestone.id) + '" '
                    + 'data-milestone-name="' + esc(milestone.milestone_name) + '" '
                    + 'data-due-date="' + esc(milestone.due_date || '') + '" '
                    + 'data-completion="' + esc(milestone.completion_pct || 0) + '" '
                    + 'data-status="' + esc(status) + '" '
                    + 'data-remarks="' + esc(milestone.remarks || '') + '">'
                    + '<i class="ti ti-edit"></i> Update</button>'
                : '';

            return '<tr data-milestone-id="' + esc(milestone.id) + '">' +
                '<td style="font-weight:700;">' + esc(milestone.milestone_name) + '</td>' +
                '<td>' + esc(formatDueDate(milestone.due_date)) + '</td>' +
                '<td><strong>' + esc(Math.round(Number(milestone.completion_pct || 0))) + '%</strong></td>' +
                '<td><span class="gpm-status ' + statusClass(status) + '">' + esc(status) + '</span></td>' +
                '<td>' + docHtml + '</td>' +
                '<td style="max-width:220px;font-size:.84rem;">' + remarksHtml + '</td>' +
                (canTrackRows ? '<td>' + actionHtml + '</td>' : '') +
                '</tr>';
        }).join('');
    }

    function renderDetail(detail) {
        if (!detailPanel) return;

        if (!detail || !detail.application) {
            detailPanel.innerHTML = '<div class="gpm-empty"><i class="ti ti-tasks" style="font-size:2rem;color:#cbd5e1;"></i>' +
                '<p style="margin:.5rem 0 0;">Select a funded project to view milestones.</p></div>';
            return;
        }

        var app = detail.application;
        var milestones = detail.milestones || [];
        var evidence = detail.evidence || [];
        var ref = esc(app.proposal_reference || 'Proposal');
        var canTrackRows = !!detail.can_track;
        var colSpan = canTrackRows ? 7 : 6;

        var evidenceSection = canTrackRows
            ? '<div class="gpm-evidence-panel">' +
                '<h3 class="gpm-section-title"><i class="ti ti-folder me-1"></i>Researcher Progress Evidence</h3>' +
                '<div class="gpm-table-wrap"><table class="gpm-table"><thead><tr>' +
                '<th>Submitted</th><th>Milestone</th><th>Title</th><th>File</th><th>Status</th>' +
                '</tr></thead><tbody>' + buildEvidenceRows(evidence) + '</tbody></table></div></div>'
            : '';

        detailPanel.innerHTML =
            '<h2 class="gpm-panel-title">Milestones — ' + ref + '</h2>' +
            '<div class="gpm-meta">' +
            '<span><strong>Research Title:</strong> ' + esc(app.research_title || '—') + '</span>' +
            '<span><strong>Lead Proponent:</strong> ' + esc(app.applicant_name || '—') + '</span>' +
            '<span><strong>Overall Progress:</strong> ' + esc(Number(app.avg_completion_pct || 0).toFixed(1)) + '%</span>' +
            '</div>' +
            '<div class="gpm-table-wrap"><table class="gpm-table" id="gpmMilestoneTable">' +
            '<thead><tr><th>Milestone</th><th>Due Date</th><th>Completion %</th><th>Status</th>' +
            '<th>Supporting Document</th><th>Remarks</th>' +
            (canTrackRows ? '<th></th>' : '') + '</tr></thead>' +
            '<tbody id="gpmMilestoneBody">' +
            (milestones.length ? buildMilestoneRows(milestones, canTrackRows)
                : '<tr><td colspan="' + colSpan + '" style="text-align:center;padding:1.5rem;color:#64748b;">No milestones initialized yet.</td></tr>') +
            '</tbody></table></div>' + evidenceSection;

        bindEditButtons();
    }

    function openEditDialog(btn) {
        if (!editDialog) return;
        if (editMilestoneId) editMilestoneId.value = btn.getAttribute('data-milestone-id') || '';
        if (editMilestoneName) editMilestoneName.textContent = btn.getAttribute('data-milestone-name') || 'Milestone';
        if (editDueDate) editDueDate.value = btn.getAttribute('data-due-date') || '';
        if (editCompletion) editCompletion.value = btn.getAttribute('data-completion') || '0';
        if (editStatus) editStatus.value = btn.getAttribute('data-status') || 'Pending';
        if (editRemarks) editRemarks.value = btn.getAttribute('data-remarks') || '';
        if (editDocument) editDocument.value = '';
        submitting = false;
        setSaveButtonBusy(false);
        paused = true;
        editDialog.classList.add('show');
    }

    function closeEditDialog() {
        if (!editDialog) return;
        editDialog.classList.remove('show');
        paused = false;
        submitting = false;
        setSaveButtonBusy(false);
    }

    function bindEditButtons() {
        if (!canTrack) return;
        document.querySelectorAll('.gpmEditMilestoneBtn').forEach(function (btn) {
            btn.addEventListener('click', function () { openEditDialog(btn); });
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
                    updateStats(overview);
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
        if (!detailPanel) return;
        detailPanel.classList.add('gpm-panel-updated');
        setTimeout(function () { detailPanel.classList.remove('gpm-panel-updated'); }, 1200);
    }

    if (projectSelect) {
        projectSelect.addEventListener('change', function () {
            selectedId = parseInt(projectSelect.value, 10) || 0;
            root.setAttribute('data-selected-id', String(selectedId));
            fetchOverview(false);
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (submitting) return;

            var milestoneId = parseInt(editMilestoneId ? editMilestoneId.value : '0', 10) || 0;
            if (milestoneId <= 0) return;

            submitting = true;
            setSaveButtonBusy(true);
            var payload = {
                milestone_id: milestoneId,
                due_date: editDueDate ? editDueDate.value : '',
                completion_pct: editCompletion ? editCompletion.value : '',
                status: editStatus ? editStatus.value : 'Pending',
                remarks: editRemarks ? editRemarks.value.trim() : ''
            };

            fetch(apiBase + '?action=update_milestone', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.success) {
                        var message = (data && data.message) || 'Update failed.';
                        alert(message);
                        forceHideLoader();
                        return Promise.reject(new Error(message));
                    }

                    var hasFile = editDocument && editDocument.files && editDocument.files.length > 0;
                    if (!hasFile) return data;

                    var formData = new FormData();
                    formData.append('milestone_id', String(milestoneId));
                    formData.append('supporting_doc', editDocument.files[0]);

                    return fetch(apiBase + '?action=upload_document', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    }).then(function (r) { return r.json(); });
                })
                .then(function (data) {
                    if (!data) return;
                    if (!data.success) {
                        alert((data && data.message) || 'Document upload failed.');
                        forceHideLoader();
                        return;
                    }
                    applyMilestoneResponse(data);
                    if (typeof window.SMSRefreshNotifications === 'function') {
                        window.SMSRefreshNotifications();
                    }
                })
                .catch(function (err) {
                    if (!err || !err.message) {
                        alert('Milestone update failed.');
                    }
                    forceHideLoader();
                })
                .finally(function () {
                    submitting = false;
                    setSaveButtonBusy(false);
                });
        });
    }

    document.getElementById('gpmEditCancelBtn')?.addEventListener('click', closeEditDialog);

    if (editStatus && editCompletion) {
        editStatus.addEventListener('change', function () {
            if (editStatus.value === 'Completed') editCompletion.value = '100';
            if (editStatus.value === 'Pending' && parseInt(editCompletion.value, 10) >= 100) {
                editCompletion.value = '0';
            }
        });
    }

    bindEditButtons();
    fetchOverview(false);

    function poll() {
        if (paused || document.hidden || submitting) return;
        if (editDialog && editDialog.classList.contains('show')) return;
        fetchOverview(true);
    }

    setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) poll();
    });
})();
