(function () {
    var root = document.querySelector('[data-rsc-live]');
    if (!root) return;

    var endpoint = root.getAttribute('data-rsc-endpoint') || '';
    var csrf = root.getAttribute('data-rsc-csrf') || '';
    var role = root.getAttribute('data-rsc-role') || '';
    var formBox = root.querySelector('[data-rsc-form]');
    var statusEl = root.querySelector('[data-rsc-status]');
    var syncEl = root.querySelector('[data-rsc-sync]');
    var listBody = root.querySelector('[data-rsc-rows]');
    var acceptBtn = root.querySelector('[data-rsc-accept]');
    var signBtn = root.querySelector('[data-rsc-sign]');
    var approveBtn = root.querySelector('[data-rsc-approve]');
    var rejectBtn = root.querySelector('[data-rsc-reject]');
    var printBtn = root.querySelector('[data-rsc-print]');
    var downloadBtn = root.querySelector('[data-rsc-download]');
    var fileInput = root.querySelector('[data-rsc-file]');
    var emptyEl = root.querySelector('[data-rsc-empty]');
    var uploadGate = root.querySelector('[data-rsc-upload-gate]');
    var misAaNote = root.querySelector('[data-rsc-mis-aa-note]');
    var rejectNote = root.querySelector('[data-rsc-reject-note]');
    var rejectText = root.querySelector('[data-rsc-reject-text]');
    var detailEl = root.querySelector('[data-rsc-detail]');
    var pickEl = root.querySelector('[data-rsc-pick]');
    var closeBtn = root.querySelector('[data-rsc-close]');
    var groupSelect = root.querySelector('[data-rsc-group]');
    var current = null;
    var selectedId = root.getAttribute('data-rsc-id') || '';
    var uploading = false;
    var isCrad = role === 'crad_officer' || role === 'admin' || role === 'sms_admin' || role === 'superadmin';
    var selectedStage = root.getAttribute('data-rsc-stage') || '';
    var emptyText = root.querySelector('[data-rsc-empty-text]');

    function post(action, extra) {
        var fd = extra instanceof FormData ? extra : new FormData();
        fd.append('action', action);
        fd.append('csrf_token', csrf);
        fd.append('id', current && current.id ? String(current.id) : selectedId);
        if (extra && !(extra instanceof FormData)) {
            Object.keys(extra).forEach(function (key) { fd.append(key, extra[key]); });
        }
        return fetch(endpoint, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        }).then(function (r) {
            return r.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    return { ok: false, error: 'Request failed. Please refresh and try again.' };
                }
            });
        });
    }

    function applyClearance(row) {
        current = row;
        var signedDone = !!(row && row.status === 'clearance_done');
        var showUploadImg = !!(row && row.has_upload && row.uploaded_url && (isCrad || signedDone || row.status === 'crad_received'));
        var showForm = !!(row && row.form_html) && (!isCrad || signedDone) && !showUploadImg;

        if (isCrad && row && row.has_upload && row.uploaded_url) {
            showUploadImg = true;
            showForm = false;
        }

        if (formBox) {
            if (showUploadImg) {
                formBox.hidden = false;
                formBox.innerHTML = '<div class="rsc-upload-frame"><img class="rsc-upload-img" alt="Uploaded clearance form" src="'
                    + row.uploaded_url + '"></div>';
            } else if (showForm) {
                formBox.hidden = false;
                formBox.innerHTML = row.form_html;
            } else if (row && row.form_html && role === 'student') {
                formBox.hidden = false;
                formBox.innerHTML = row.form_html;
            } else {
                formBox.hidden = true;
                if (!(formBox.innerHTML || '').trim()) formBox.innerHTML = '';
            }
        }

        if (statusEl) {
            var stageBit = row && row.stage_label ? (row.stage_label + ' — ') : '';
            statusEl.textContent = row ? (stageBit + (row.status_label || row.status)) : '';
        }

        var canStudentUpload = !!(role === 'student' && row && row.can_student_upload);
        var canCradUpload = false; // CRAD reviews only — student uploads signed image
        if (acceptBtn) {
            acceptBtn.hidden = !(canStudentUpload || canCradUpload);
            acceptBtn.disabled = false;
            var uploadLabel = acceptBtn.querySelector('[data-rsc-upload-label]');
            if (uploadLabel) {
                if (role === 'student') {
                    uploadLabel.textContent = row && row.has_upload ? 'Re-upload signed image' : 'Upload signed image';
                } else {
                    uploadLabel.textContent = row && row.has_upload ? 'Re-upload Image' : 'Upload Image';
                }
            }
        }

        if (signBtn) signBtn.hidden = true;
        if (approveBtn) {
            approveBtn.hidden = !(isCrad && row && row.can_crad_sign);
            approveBtn.disabled = false;
        }
        if (rejectBtn) {
            rejectBtn.hidden = !(isCrad && row && row.can_crad_sign);
            rejectBtn.disabled = false;
        }
        if (rejectNote) {
            rejectNote.hidden = !(role === 'student' && row && row.status === 'rejected');
        }
        if (rejectText && role === 'student') {
            if (row && row.status === 'rejected') {
                rejectText.textContent = 'CRAD rejected your clearance'
                    + (row.crad_remarks ? ': ' + row.crad_remarks : '.')
                    + ' Please re-upload a corrected signed image.';
            } else {
                rejectText.textContent = '';
            }
        }
        if (printBtn) {
            // Print only for students (form). Hide for CRAD officers.
            printBtn.hidden = !(role === 'student' && row && row.form_html);
        }
        if (downloadBtn) downloadBtn.hidden = !row;
        if (detailEl) detailEl.hidden = !row;
        if (pickEl) {
            if (isCrad) pickEl.hidden = !!row || !(listBody && listBody.querySelector('[data-rsc-open]'));
            else pickEl.hidden = !(role === 'adviser') || !!row;
        }

        var metaTitle = root.querySelector('[data-rsc-meta-title]');
        var metaOr = root.querySelector('[data-rsc-meta-or]');
        var metaUploaded = root.querySelector('[data-rsc-meta-uploaded]');
        var metaFile = root.querySelector('[data-rsc-meta-file]');
        if (metaTitle) metaTitle.textContent = (row && row.research_title) ? row.research_title : '—';
        if (metaOr) metaOr.textContent = (row && row.or_number) ? row.or_number : '—';
        if (metaUploaded) metaUploaded.textContent = (row && row.uploaded_at_label) ? row.uploaded_at_label : '—';
        if (metaFile) metaFile.textContent = (row && row.uploaded_original) ? row.uploaded_original : '—';

        var closeBtnEl = root.querySelector('[data-rsc-close]');
        if (closeBtnEl) closeBtnEl.hidden = !(isCrad && row);

        if (emptyEl) {
            if (role === 'student') {
                emptyEl.hidden = !!(row && row.form_html);
                if (emptyText) {
                    emptyText.textContent = (row && row.locked_reason)
                        ? row.locked_reason
                        : 'Open a clearance in the inbox. Print, get it signed, then upload the image for CRAD approval.';
                }
            } else if (role === 'adviser') {
                emptyEl.hidden = true;
            } else if (isCrad) {
                emptyEl.hidden = !!row;
            }
        }

        if (uploadGate) uploadGate.hidden = !(isCrad && row && !row.has_upload);
        if (misAaNote) {
            misAaNote.hidden = !(isCrad && row && row.has_upload && row.status !== 'clearance_done');
        }
    }

    function escapeHtml(value) {
        return String(value === undefined || value === null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // One name + O.R. number per approved researcher, exactly as approved by
    // Admin. Falls back to a single reference when no per-member numbers exist.
    function approvedOrCell(row) {
        var list = row && row.or_list && row.or_list.length ? row.or_list : [];
        if (!list.length) {
            return row && row.or_number ? escapeHtml(row.or_number) : '—';
        }
        return '<div class="rsc-or-list">' + list.map(function (ref) {
            ref = ref || {};
            var name = ref.name ? '<span class="rsc-or-name">' + escapeHtml(ref.name) + '</span>' : '';
            var orNo = '<span class="rsc-or-no">' + escapeHtml(ref.or_number || '—') + '</span>';
            return '<div class="rsc-or-item">' + name + orNo + '</div>';
        }).join('') + '</div>';
    }

    function renderRows(rows) {
        if (!listBody) return;
        var isStudent = role === 'student';
        if (!rows || !rows.length) {
            listBody.innerHTML = '<tr><td colspan="' + (isStudent ? 4 : (isCrad ? 7 : 6)) + '" class="text-muted">'
                + (isCrad ? 'No signed clearances waiting for review.' : 'No clearance forms yet.')
                + '</td></tr>';
            return;
        }
        listBody.innerHTML = rows.map(function (row) {
            var active = '';
            if (current && row.id && String(current.id) === String(row.id)) active = ' class="table-active"';
            else if (!current && selectedStage && row.research_stage === selectedStage) active = ' class="table-active"';
            if (isStudent) {
                return '<tr' + active + ' data-rsc-open="' + (row.id || 0) + '" data-rsc-stage="' + (row.research_stage || 'research_1') + '">'
                    + '<td><strong>' + (row.stage_label || '') + '</strong></td>'
                    + '<td>' + approvedOrCell(row) + '</td>'
                    + '<td>' + (row.status_label || row.status || 'Not available yet') + '</td>'
                    + '<td><button type="button" class="btn btn-sm btn-outline-primary" data-rsc-open="' + (row.id || 0) + '" data-rsc-stage="' + (row.research_stage || 'research_1') + '">Open</button></td>'
                    + '</tr>';
            }
            if (isCrad) {
                return '<tr' + active + ' data-rsc-open="' + row.id + '">'
                    + '<td>' + (row.leader_group_no || '—') + '</td>'
                    + '<td><strong>' + (row.stage_label || 'Research 1') + '</strong></td>'
                    + '<td>' + (row.research_title || '—') + '</td>'
                    + '<td>' + (row.or_number || '—') + '</td>'
                    + '<td>' + (row.uploaded_at_label || '—') + '</td>'
                    + '<td>' + (row.status_label || row.status || '') + '</td>'
                    + '<td><button type="button" class="btn btn-sm btn-outline-primary" data-rsc-open="' + row.id + '">View</button></td>'
                    + '</tr>';
            }
            return '<tr' + active + ' data-rsc-open="' + row.id + '">'
                + '<td>' + (row.leader_group_no || '') + '</td>'
                + '<td><strong>' + (row.stage_label || 'Research 1') + '</strong></td>'
                + '<td>' + (row.research_title || '') + '</td>'
                + '<td>' + (row.or_number || '') + '</td>'
                + '<td>' + (row.status_label || row.status) + '</td>'
                + '<td><button type="button" class="btn btn-sm btn-outline-primary" data-rsc-open="' + row.id + '">Open</button></td>'
                + '</tr>';
        }).join('');
    }

    function refresh() {
        if (uploading) return;
        var url = endpoint;
        var q = [];
        if (selectedId) q.push('id=' + encodeURIComponent(selectedId));
        if (role === 'student' && selectedStage) q.push('stage=' + encodeURIComponent(selectedStage));
        if (q.length) url += (endpoint.indexOf('?') >= 0 ? '&' : '?') + q.join('&');
        fetch(url, { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                return r.text().then(function (text) {
                    try { return JSON.parse(text); }
                    catch (e) { return null; }
                });
            })
            .then(function (data) {
                if (!data || !data.ok) return;
                if (syncEl) syncEl.textContent = data.last_sync || '';
                if (role === 'student') {
                    if (data.rows) renderRows(data.rows);
                    if (data.clearance && data.clearance.id) {
                        selectedId = String(data.clearance.id);
                        selectedStage = data.clearance.research_stage || selectedStage;
                        root.setAttribute('data-rsc-stage', selectedStage);
                        applyClearance(data.clearance);
                    } else {
                        selectedId = '';
                        var locked = null;
                        if (data.rows && selectedStage) {
                            locked = data.rows.filter(function (row) { return row.research_stage === selectedStage; })[0] || null;
                        }
                        applyClearance(locked || null);
                        if (locked && emptyText && locked.locked_reason) {
                            emptyText.textContent = locked.locked_reason;
                        }
                    }
                } else if (isCrad) {
                    if (data.rows) renderRows(data.rows);
                    if (emptyEl) emptyEl.hidden = !!(data.rows && data.rows.length);
                    if (selectedId && data.clearance && String(data.clearance.id) === String(selectedId)) {
                        applyClearance(data.clearance);
                    } else if (!selectedId) {
                        applyClearance(null);
                    } else if (selectedId && (!data.clearance || String(data.clearance.id) !== String(selectedId))) {
                        // Keep waiting for matching payload; don't wipe selection mid-click
                        if (data.rows) {
                            var stillThere = data.rows.some(function (row) { return String(row.id) === String(selectedId); });
                            if (!stillThere) {
                                selectedId = '';
                                applyClearance(null);
                            }
                        }
                    }
                    if (pickEl) pickEl.hidden = !!(selectedId) || !(data.rows && data.rows.length);
                } else if (role === 'adviser') {
                    if (data.rows) renderRows(data.rows);
                    applyClearance(null);
                    if (emptyEl) {
                        emptyEl.hidden = false;
                        emptyEl.textContent = 'Adviser signing is no longer required. Students upload signed clearances for CRAD approval.';
                    }
                    if (pickEl) pickEl.hidden = true;
                } else if (selectedId && data.clearance) {
                    selectedId = String(data.clearance.id);
                    applyClearance(data.clearance);
                    if (data.rows) renderRows(data.rows);
                } else {
                    selectedId = '';
                    applyClearance(null);
                    if (data.rows) renderRows(data.rows);
                }
            })
            .catch(function () {});
    }

    function uploadPickedFile() {
        if (uploading || !fileInput || !fileInput.files || !fileInput.files[0]) {
            return;
        }
        var picked = fileInput.files[0];
        if (!/\.(png|jpe?g)$/i.test(picked.name || '')) {
            alert('Upload a PNG or JPG picture of the signed clearance form.');
            fileInput.value = '';
            return;
        }
        var fd = new FormData();
        fd.append('clearance_file', picked);
        uploading = true;
        var action = role === 'student' ? 'student_upload_signed' : 'crad_receive';
        post(action, fd).then(function (data) {
            fileInput.value = '';
            if (data && data.ok) {
                if (data.clearance) applyClearance(data.clearance);
                refresh();
                return;
            }
            alert((data && data.error) || 'Could not upload the clearance image. Please try again.');
        }).catch(function () {
            alert('Could not upload the clearance image. Please try again.');
        }).finally(function () {
            uploading = false;
        });
    }

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) {
                uploadPickedFile();
            }
        });
    }

    if (approveBtn) {
        approveBtn.addEventListener('click', function () {
            if (!current || !current.can_crad_sign) {
                alert('A signed clearance image is required before approval.');
                return;
            }
            if (!window.confirm('Approve this signed clearance?')) return;
            approveBtn.disabled = true;
            if (rejectBtn) rejectBtn.disabled = true;
            post('crad_approve').then(function (data) {
                if (data && data.ok) {
                    selectedId = '';
                    applyClearance(null);
                    refresh();
                } else if (data && data.error) {
                    alert(data.error);
                }
            }).finally(function () {
                approveBtn.disabled = false;
                if (rejectBtn) rejectBtn.disabled = false;
            });
        });
    }

    if (rejectBtn) {
        rejectBtn.addEventListener('click', function () {
            if (!current || !current.can_crad_sign) {
                alert('A signed clearance image is required before rejection.');
                return;
            }
            var reason = window.prompt('Reason for rejection (student will see this):', 'Please re-upload a clearer signed clearance form.');
            if (reason === null) return;
            reason = String(reason).trim();
            if (!reason) {
                alert('Please enter a rejection reason.');
                return;
            }
            rejectBtn.disabled = true;
            if (approveBtn) approveBtn.disabled = true;
            post('crad_reject', { reason: reason }).then(function (data) {
                if (data && data.ok) {
                    alert('Rejected. The student was notified to re-upload.');
                    selectedId = '';
                    applyClearance(null);
                    refresh();
                } else if (data && data.error) {
                    alert(data.error);
                }
            }).finally(function () {
                rejectBtn.disabled = false;
                if (approveBtn) approveBtn.disabled = false;
            });
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', function () { window.print(); });
    }
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () {
            var id = current && current.id ? String(current.id) : selectedId;
            if (!id) return;
            var url = endpoint + (endpoint.indexOf('?') >= 0 ? '&' : '?') + 'action=download_image&id=' + encodeURIComponent(id);
            window.location.href = url;
        });
    }

    root.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-rsc-open]');
        if (!btn) return;
        selectedId = btn.getAttribute('data-rsc-open') || '';
        selectedStage = btn.getAttribute('data-rsc-stage') || selectedStage || 'research_1';
        root.setAttribute('data-rsc-stage', selectedStage);
        if (!selectedId || selectedId === '0') selectedId = '';
        refresh();
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            selectedId = '';
            applyClearance(null);
            refresh();
        });
    }
    if (groupSelect) {
        groupSelect.addEventListener('change', function () {
            selectedId = groupSelect.value || '';
            refresh();
        });
    }

    // Keep adviser signature modal inert if present (legacy pages).
    if (signBtn) signBtn.hidden = true;

    refresh();
    window.setInterval(refresh, 1000);
})();
