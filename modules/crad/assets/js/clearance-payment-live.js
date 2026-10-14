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
    var saveOrBtn = document.getElementById('rcpSaveOrBtn');
    var orNotice = root.querySelector('[data-rcp-or-notice]');
    var orFound = root.querySelector('[data-rcp-or-found]');
    var memberReferences = root.querySelector('[data-rcp-member-references]');
    var listBody = root.querySelector('[data-rcp-list]');
    var studentList = root.querySelector('[data-rcp-student-list]');
    var detail = root.querySelector('[data-rcp-detail]');
    var adminOr = root.querySelector('[data-rcp-or]');
    var receiptStudent = root.querySelector('[data-rcp-receipt-student]');
    var receiptStudentWrap = root.querySelector('[data-rcp-receipt-student-wrap]');
    var adminMemberReferences = root.querySelector('[data-rcp-member-references-admin]');
    var adminMemberReferencesWrap = root.querySelector('[data-rcp-member-references-wrap]');
    var adminRemarks = root.querySelector('[data-rcp-remarks]');
    var approveBtn = root.querySelector('[data-rcp-approve]');
    var rejectBtn = root.querySelector('[data-rcp-reject]');
    var gateEl = root.querySelector('[data-rcp-gate]');
    var lockedEl = root.querySelector('[data-rcp-locked]');
    var uploadPanel = root.querySelector('[data-rcp-upload-panel]');
    var current = null;
    var uploading = false;
    var lastStamp = '';
    var orDirty = false;
    // Id of the record whose detail panel is open ('' = none). Drives the
    // View / Unview toggle label in the Action column.
    var viewingId = '';
    // Set when the approver closes a record so live polling cannot reopen it.
    var dismissed = false;
    // Cached list so the Action label can flip to View immediately on close.
    var lastRows = [];

    function esc(value) { return String(value || '').replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]; }); }
    function memberReferencePayload() {
        var values = {};
        if (memberReferences) memberReferences.querySelectorAll('[data-rcp-member-or]').forEach(function (input) { values[input.getAttribute('data-member-id')] = input.value; });
        return values;
    }
    // The payment's own reference is the first researcher number on file, so
    // the single Reference / O.R. field is no longer needed.
    function primaryMemberReference() {
        if (!memberReferences) return '';
        var first = '';
        memberReferences.querySelectorAll('[data-rcp-member-or]').forEach(function (input) {
            if (first === '') first = String(input.value || '').trim();
        });
        return first;
    }
    function renderMemberReferences(row) {
        if (!memberReferences) return;
        var members = row && Array.isArray(row.members) ? row.members : [];
        if (!members.length) { memberReferences.innerHTML = ''; return; }
        // Each field shows only what is stored for THIS research stage, so a
        // stage with no upload yet stays blank. Autoscan and manual entry both
        // write into these same fields.
        memberReferences.innerHTML = '<label class="form-label fw-bold mb-2">Approved Researchers — Reference / O.R. Number <small class="text-muted">(' + members.length + ' required)</small></label>' + members.map(function (member, index) {
            return '<div class="input-group mb-2"><span class="input-group-text">' + (index + 1) + '. ' + esc(member.name || member.student_id || 'Researcher') + '</span><input type="text" class="form-control" data-rcp-member-or data-member-id="' + member.id + '" value="' + esc(member.or_number || '') + '" placeholder="Reference / O.R. Number" maxlength="80" autocomplete="off"></div>';
        }).join('');
    }


    function rowStamp(row) {
        return row ? [row.id, row.research_stage, row.status, row.uploaded_url, row.or_number, row.remarks, row.updated_at, row.can_upload, row.locked_reason,
            (Array.isArray(row.members) ? row.members.map(function (member) { return [member.id, member.or_number].join(':'); }).join(',') : '')].join('|') : '';
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
        // Polling must never erase a reference the student is still typing
        // (or has typed but not yet saved).
        if (!orDirty) renderMemberReferences(row);
        // Confirm what the scan managed to read for this stage.
        var filled = 0;
        if (memberReferences) {
            memberReferences.querySelectorAll('[data-rcp-member-or]').forEach(function (el) {
                if (String(el.value || '').trim() !== '') filled++;
            });
        }
        if (orFound) {
            orFound.hidden = !(row && row.has_upload && filled > 0 && !locked);
        }
        if (orNotice) orNotice.hidden = !locked && !!(row && row.has_upload && filled === 0);
        if (saveOrBtn) saveOrBtn.disabled = !(row && row.id && row.status !== 'approved' && !locked);
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
        viewingId = row && row.id ? String(row.id) : '';
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
        var typingReference = document.activeElement === adminOr;
        var typingRemarks = document.activeElement === adminRemarks;
        // The page polls every second.  Do not replace text being entered by
        // the approver with the still-saved value from the server.
        if (adminOr && (forceFields || !typingReference)) adminOr.value = row.or_number || '';
        if (receiptStudent) receiptStudent.value = row.receipt_student_name || '';
        if (receiptStudentWrap) receiptStudentWrap.hidden = !row.receipt_student_name;
        if (adminMemberReferences && adminMemberReferencesWrap) {
            var memberRows = Array.isArray(row.members) ? row.members : [];
            adminMemberReferencesWrap.hidden = memberRows.length === 0;
            adminMemberReferences.innerHTML = memberRows.map(function (member, index) {
                return '<div class="d-flex justify-content-between gap-2 py-1"><span>' + (index + 1) + '. ' + esc(member.name || member.student_id || 'Researcher') + '</span><strong>' + esc(member.or_number || '—') + '</strong></div>';
            }).join('');
        }
        if (forceFields || !typingRemarks) {
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
        lastRows = Array.isArray(rows) ? rows : [];
        if (!lastRows.length) {
            listBody.innerHTML = '<tr><td colspan="6" class="text-muted">No clearance payment has been sent to admin yet.</td></tr>';
            return;
        }
        listBody.innerHTML = lastRows.map(function (row) {
            var isOpen = viewingId !== '' && String(row.id) === viewingId;
            var active = isOpen ? ' class="table-active"' : '';
            // The open record offers Unview so the same button closes it again.
            var action = isOpen
                ? '<button type="button" class="btn btn-sm btn-outline-secondary" data-rcp-open-id="' + row.id + '">Unview</button>'
                : '<button type="button" class="btn btn-sm btn-outline-primary" data-rcp-open-id="' + row.id + '">View</button>';
            return '<tr' + active + ' data-rcp-open-id="' + row.id + '">'
                + '<td>' + (row.group_number || '') + '</td>'
                + '<td>' + (row.stage_label || '') + '</td>'
                + '<td>' + (row.research_title || '') + '</td>'
                + '<td>' + ((Array.isArray(row.members) && row.members.length)
                    ? row.members.map(function (member) { return esc(member.or_number || '—'); }).join('<br>')
                    : esc(row.or_number || '')) + '</td>'
                + '<td>' + esc(row.status_label || row.status) + '</td>'
                + '<td>' + action + '</td>'
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
                    if (dismissed) {
                        // The approver closed this record; a poll must not
                        // reopen it behind their back.
                        if (current) applyAdmin(null);
                        renderList(rows);
                        return;
                    }
                    var incoming = newestPending(rows);
                    if (current) {
                        var match = rows.filter(function (row) { return String(row.id) === String(current.id); })[0];
                        if (match) incoming = match;
                    }
                    var changed = !current || (incoming && String(incoming.id) !== String(current.id)) || rowStamp(incoming) !== lastStamp;
                    if (changed) applyAdmin(incoming, !!(incoming && (!current || String(incoming.id) !== String(current.id))));
                    // Rendered after applyAdmin so the Action label matches the
                    // record that is actually open.
                    renderList(rows);
                }
            })
            .catch(function () {});
    }

    root.addEventListener('click', function (event) {
        var openStage = event.target.closest('[data-rcp-open-stage]');
        if (openStage) {
            orDirty = false;
            selectedStage = openStage.getAttribute('data-rcp-open-stage') || 'research_1';
            root.setAttribute('data-rcp-stage', selectedStage);
            refresh();
            return;
        }
        var openBtn = event.target.closest('[data-rcp-open-id]');
        if (openBtn && role !== 'student') {
            event.preventDefault();
            var id = openBtn.getAttribute('data-rcp-open-id');
            // Clicking the record that is already open closes it (Unview).
            if (viewingId !== '' && String(viewingId) === String(id)) {
                dismissed = true;
                applyAdmin(null);
                renderList(lastRows);
                return;
            }
            dismissed = false;
            fetch(endpoint, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data || !data.ok) return;
                    var rows = data.rows || [];
                    var match = rows.filter(function (row) { return String(row.id) === String(id); })[0];
                    if (match) {
                        applyAdmin(match, true);
                        renderList(rows);
                        // Keep the selected record visible after an explicit
                        // View click, including when the page is live-polling.
                        if (detail) {
                            detail.hidden = false;
                            detail.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }
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
                or_number: primaryMemberReference(),
                research_stage: selectedStage
            }, fileInput.files[0])
                .then(function (data) {
                    if (data && data.ok) {
                        orDirty = false;
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

    if (saveOrBtn) {
        saveOrBtn.addEventListener('click', function () {
            if (!current || !current.id) return;
            saveOrBtn.disabled = true;
            post('student_update_or', {
                or_number: primaryMemberReference(),
                member_or_numbers: JSON.stringify(memberReferencePayload()),
                research_stage: selectedStage
            }).then(function (data) {
                if (data && data.ok) {
                    orDirty = false;
                    if (data.rows) renderStudentList(data.rows);
                    if (data.payment) applyStudent(data.payment);
                } else if (data && data.error) {
                    alert(data.error);
                }
            }).finally(function () {
                refresh();
            });
        });
    }

    if (memberReferences) memberReferences.addEventListener('input', function () { orDirty = true; });

    if (approveBtn) {
        approveBtn.addEventListener('click', function () {
            if (!current) return;
            approveBtn.disabled = true;
            post('admin_approve', {
                id: current.id,
                or_number: adminOr ? adminOr.value : '',
                remarks: adminRemarks ? adminRemarks.value : 'HMA'
            }).then(function (data) {
                if (data && data.ok && data.payment) { dismissed = false; applyAdmin(data.payment); }
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
                if (data && data.ok && data.payment) { dismissed = false; applyAdmin(data.payment); }
                else if (data && data.error) alert(data.error);
                refresh();
            }).finally(function () { rejectBtn.disabled = false; });
        });
    }

    refresh();
    window.setInterval(refresh, 1000);
})();
