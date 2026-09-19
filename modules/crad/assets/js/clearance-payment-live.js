(function () {
    var root = document.querySelector('[data-rcp-live]');
    if (!root) return;
    var role = root.getAttribute('data-rcp-role') || '';
    var endpoint = root.getAttribute('data-rcp-endpoint') || '';
    var csrf = root.getAttribute('data-rcp-csrf') || '';
    var selectedStage = root.getAttribute('data-rcp-stage') || 'research_1';
    var statusEl = root.querySelector('[data-rcp-status]');
    var syncEl = root.querySelector('[data-rcp-sync]');
    var preview = root.querySelector('[data-rcp-preview]');
    var fileNameEl = root.querySelector('[data-rcp-file-name]');
    var uploadBtn = document.getElementById('rcpUploadBtn');
    var uploadLabel = root.querySelector('[data-rcp-upload-label]');
    var fileInput = document.getElementById('rcpFile');
    var orInput = document.getElementById('rcpOr');
    var listBody = root.querySelector('[data-rcp-list]');
    var studentList = root.querySelector('[data-rcp-student-list]');
    var detail = root.querySelector('[data-rcp-detail]');
    var adminOr = root.querySelector('[data-rcp-or]');
    var adminRemarks = root.querySelector('[data-rcp-remarks]');
    var approveBtn = root.querySelector('[data-rcp-approve]');
    var rejectBtn = root.querySelector('[data-rcp-reject]');
    var gateEl = root.querySelector('[data-rcp-gate]');
    var lockedEl = root.querySelector('[data-rcp-locked]');
    var uploadPanel = root.querySelector('[data-rcp-upload-panel]');
    var current = null;
    var uploading = false;
    var lastStamp = '';

    function rowStamp(row) {
        return row ? [row.id, row.research_stage, row.status, row.uploaded_url, row.or_number, row.remarks, row.updated_at, row.can_upload, row.locked_reason].join('|') : '';
    }

    function newestPending(rows) {
        if (!rows || !rows.length) return null;
        var pending = rows.filter(function (row) { return row.status === 'pending'; });
        return pending[0] || rows[0];
    }

    function post(action, extra, file) {
        var fd = extra instanceof FormData ? extra : new FormData();
        if (!(extra instanceof FormData)) {
            Object.keys(extra || {}).forEach(function (key) { fd.append(key, extra[key]); });
        }
        fd.append('action', action);
        fd.append('csrf_token', csrf);
        if (file) fd.append('payment_file', file);
        return fetch(endpoint, { method: 'POST', credentials: 'same-origin', body: fd })
            .then(function (r) { return r.json(); });
    }

    function applyStudent(row) {
        current = row;
        selectedStage = row && row.research_stage ? row.research_stage : selectedStage;
        root.setAttribute('data-rcp-stage', selectedStage);
        var locked = !!(row && row.locked_reason);
        var canUpload = !!(row && row.can_upload && !locked);
        if (statusEl) {
            statusEl.textContent = row
                ? ((row.stage_label || 'Research') + ' — ' + (row.status_label || row.status || 'No collage payment uploaded yet'))
                : 'No collage payment uploaded yet';
        }
        if (fileNameEl) fileNameEl.textContent = row && row.uploaded_original ? row.uploaded_original : '';
        if (uploadLabel) uploadLabel.textContent = row && row.has_upload ? 'Re-upload' : 'Upload';
        if (uploadBtn) uploadBtn.disabled = !canUpload;
        if (fileInput) {
            fileInput.disabled = !canUpload;
            if (!canUpload) fileInput.value = '';
        }
        if (orInput) orInput.value = row && row.or_number ? row.or_number : '';
        if (gateEl) {
            if (locked) {
                gateEl.hidden = true;
                gateEl.classList.add('d-none');
            } else {
                gateEl.hidden = false;
                gateEl.classList.remove('d-none');
                gateEl.textContent = 'Upload the ' + ((row && row.stage_label) || 'Research') +
                    ' collage payment picture. After Admin approves it, that O.R. number and remarks appear on the matching clearance form.';
            }
        }
        if (lockedEl) {
            if (locked) {
                lockedEl.hidden = false;
                lockedEl.classList.remove('d-none');
                lockedEl.textContent = row.locked_reason;
            } else {
                lockedEl.hidden = true;
                lockedEl.classList.add('d-none');
                lockedEl.textContent = '';
            }
        }
        if (uploadPanel) {
            // Hide upload controls entirely while Research 2 (or any stage) is locked.
            uploadPanel.hidden = locked || !(canUpload || (row && row.has_upload));
        }
        if (preview) {
            if (!locked && row && row.uploaded_url) {
                preview.hidden = false;
                preview.innerHTML = '<img class="rsc-upload-img" alt="Collage payment" src="' + row.uploaded_url + '">';
            } else {
                preview.hidden = true;
                preview.innerHTML = '';
            }
        }
    }

    function renderStudentList(rows) {
        if (!studentList) return;
        if (!rows || !rows.length) {
            studentList.innerHTML = '<tr><td colspan="4" class="text-muted">No payment stages yet.</td></tr>';
            return;
        }
        studentList.innerHTML = rows.map(function (row) {
            var active = selectedStage === row.research_stage ? ' class="table-active"' : '';
            return '<tr' + active + ' data-rcp-open-stage="' + row.research_stage + '">'
                + '<td><strong>' + (row.stage_label || '') + '</strong></td>'
                + '<td>' + (row.or_number || '—') + '</td>'
                + '<td>' + (row.status_label || 'Not uploaded') + '</td>'
                + '<td><button type="button" class="btn btn-sm btn-outline-primary" data-rcp-open-stage="' + row.research_stage + '">Open</button></td>'
                + '</tr>';
        }).join('');
    }

    function applyAdmin(row, forceFields) {
        current = row;
        if (!detail) return;
        if (!row) {
            detail.hidden = true;
            lastStamp = '';
            return;
        }
        detail.hidden = false;
        if (statusEl) {
            statusEl.textContent = (row.stage_label ? row.stage_label + ' — ' : '') + (row.status_label || row.status);
        }
        var typing = document.activeElement === adminOr || document.activeElement === adminRemarks;
        if (adminOr) adminOr.value = row.or_number || '';
        if (forceFields || !typing) {
            if (adminRemarks && (forceFields || !adminRemarks.value || adminRemarks.value === 'HMA')) {
                adminRemarks.value = row.remarks || 'HMA';
            }
        }
        if (preview) {
            preview.innerHTML = row.uploaded_url
                ? '<img class="rsc-upload-img" alt="Collage payment" src="' + row.uploaded_url + '">'
                : '';
        }
        if (approveBtn) approveBtn.disabled = row.status === 'approved';
        if (rejectBtn) rejectBtn.disabled = row.status === 'approved';
        lastStamp = rowStamp(row);
    }

    function renderList(rows) {
        if (!listBody) return;
        if (!rows || !rows.length) {
            listBody.innerHTML = '<tr><td colspan="5" class="text-muted">No clearance payment has been sent to admin yet.</td></tr>';
            return;
        }
        listBody.innerHTML = rows.map(function (row) {
            var active = current && String(current.id) === String(row.id) ? ' class="table-active"' : '';
            return '<tr' + active + ' data-rcp-open-id="' + row.id + '">'
                + '<td>' + (row.group_number || '') + '</td>'
                + '<td>' + (row.stage_label || '') + '</td>'
                + '<td>' + (row.research_title || '') + '</td>'
                + '<td>' + (row.or_number || '') + '</td>'
                + '<td>' + (row.status_label || row.status) + '</td>'
                + '</tr>';
        }).join('');
    }

    function refresh() {
        if (uploading) return;
        var url = endpoint;
        if (role === 'student') {
            url += (endpoint.indexOf('?') >= 0 ? '&' : '?') + 'stage=' + encodeURIComponent(selectedStage);
        }
        fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) return;
                if (syncEl) syncEl.textContent = data.last_sync || '';
                if (role === 'student') {
                    renderStudentList(data.rows || []);
                    applyStudent(data.payment);
                } else {
                    var rows = data.rows || [];
                    var incoming = newestPending(rows);
                    if (current) {
                        var match = rows.filter(function (row) { return String(row.id) === String(current.id); })[0];
                        if (match) incoming = match;
                    }
                    var changed = !current || (incoming && String(incoming.id) !== String(current.id)) || rowStamp(incoming) !== lastStamp;
                    renderList(rows);
                    if (changed) applyAdmin(incoming, !!(incoming && (!current || String(incoming.id) !== String(current.id))));
                }
            })
            .catch(function () {});
    }

    root.addEventListener('click', function (event) {
        var openStage = event.target.closest('[data-rcp-open-stage]');
        if (openStage) {
            selectedStage = openStage.getAttribute('data-rcp-open-stage') || 'research_1';
            root.setAttribute('data-rcp-stage', selectedStage);
            refresh();
            return;
        }
        var openId = event.target.closest('[data-rcp-open-id]');
        if (openId && role !== 'student') {
            var id = openId.getAttribute('data-rcp-open-id');
            fetch(endpoint, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.ok) return;
                    var rows = data.rows || [];
                    renderList(rows);
                    var match = rows.filter(function (row) { return String(row.id) === String(id); })[0];
                    if (match) applyAdmin(match, true);
                });
        }
    });

    if (uploadBtn && fileInput) {
        uploadBtn.addEventListener('click', function () {
            if (current && current.locked_reason) {
                alert(current.locked_reason);
                return;
            }
            if (!(current && current.can_upload)) {
                alert('This collage payment stage is locked.');
                return;
            }
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Choose the collage payment PNG or JPG first.');
                return;
            }
            uploading = true;
            uploadBtn.disabled = true;
            post('student_upload', {
                or_number: orInput ? orInput.value : '',
                research_stage: selectedStage
            }, fileInput.files[0])
                .then(function (data) {
                    if (data && data.ok) {
                        if (data.rows) renderStudentList(data.rows);
                        if (data.payment) applyStudent(data.payment);
                    } else if (data && data.error) {
                        alert(data.error);
                    }
                })
                .finally(function () {
                    uploading = false;
                    fileInput.value = '';
                    refresh();
                });
        });
    }

    if (approveBtn) {
        approveBtn.addEventListener('click', function () {
            if (!current) return;
            approveBtn.disabled = true;
            post('admin_approve', {
                id: current.id,
                or_number: adminOr ? adminOr.value : '',
                remarks: adminRemarks ? adminRemarks.value : 'HMA'
            }).then(function (data) {
                if (data && data.ok && data.payment) applyAdmin(data.payment);
                else if (data && data.error) alert(data.error);
                refresh();
            }).finally(function () { approveBtn.disabled = false; });
        });
    }

    if (rejectBtn) {
        rejectBtn.addEventListener('click', function () {
            if (!current) return;
            rejectBtn.disabled = true;
            post('admin_reject', { id: current.id }).then(function (data) {
                if (data && data.ok && data.payment) applyAdmin(data.payment);
                else if (data && data.error) alert(data.error);
                refresh();
            }).finally(function () { rejectBtn.disabled = false; });
        });
    }

    refresh();
    window.setInterval(refresh, 1000);
})();
