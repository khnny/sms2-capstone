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
    var sendBtn = root.querySelector('[data-rsc-send]');
    var acceptBtn = root.querySelector('[data-rsc-accept]');
    var uploadOk = null;
    var signBtn = root.querySelector('[data-rsc-sign]');
    var printBtn = root.querySelector('[data-rsc-print]');
    var downloadBtn = root.querySelector('[data-rsc-download]');
    var fileInput = root.querySelector('[data-rsc-file]');
    var emptyEl = root.querySelector('[data-rsc-empty]');
    var checkWrap = root.querySelector('[data-rsc-check-wrap]');
    var checkAdviser = root.querySelector('[data-rsc-check-adviser]');
    var checkMis = root.querySelector('[data-rsc-check-mis]');
    var checkAa = root.querySelector('[data-rsc-check-aa]');
    var uploadName = root.querySelector('[data-rsc-upload-name]');
    var uploadGate = root.querySelector('[data-rsc-upload-gate]');
    var misAaNote = root.querySelector('[data-rsc-mis-aa-note]');
    var uploadPreview = root.querySelector('[data-rsc-upload-preview]');
    var uploadView = root.querySelector('[data-rsc-upload-view]');
    var detailEl = root.querySelector('[data-rsc-detail]');
    var pickEl = root.querySelector('[data-rsc-pick]');
    var closeBtn = root.querySelector('[data-rsc-close]');
    var groupSelect = root.querySelector('[data-rsc-group]');
    var current = null;
    var selectedId = root.getAttribute('data-rsc-id') || '';
    var shouldScroll = false;
    var uploading = false;
    var isCrad = role === 'crad_officer' || role === 'admin' || role === 'sms_admin' || role === 'superadmin';
    var selectedStage = root.getAttribute('data-rsc-stage') || '';
    var emptyText = root.querySelector('[data-rsc-empty-text]');
    var isInboxRole = role === 'adviser' || role === 'student';

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
                    return { ok: false, error: 'Upload failed. Please refresh and try the clearance PNG again.' };
                }
            });
        });
    }

    function applyClearance(row) {
        current = row;
        var signedDone = !!(row && row.status === 'clearance_done' && row.form_html);
        var showUpload = !!(isCrad && row && row.has_upload && row.uploaded_url && !signedDone);
        var showForm = !showUpload && !!(row && row.form_html) && (!isCrad || signedDone || !!(row && row.form_verified && row.has_upload));
        if (formBox) {
            if (showUpload) {
                formBox.hidden = false;
                formBox.innerHTML = '<img class="rsc-upload-img" alt="Uploaded clearance form" src="'
                    + row.uploaded_url + '">';
            } else if (showForm || !(formBox.innerHTML || '').trim()) {
                formBox.hidden = !showForm;
                formBox.innerHTML = showForm ? row.form_html : '';
            }
        }
        if (statusEl) {
            var stageBit = row && row.stage_label ? (row.stage_label + ' — ') : '';
            statusEl.textContent = row ? (stageBit + (row.status_label || row.status)) : '';
        }
        if (sendBtn) sendBtn.hidden = !(row && row.status === 'draft' && role === 'student');
        var canUpload = !!(row && isCrad && (row.status === 'adviser_signed' || row.status === 'crad_received' || row.status === 'clearance_done'));
        if (acceptBtn) {
            acceptBtn.hidden = !canUpload;
            acceptBtn.disabled = false;
            var uploadLabel = acceptBtn.querySelector('[data-rsc-upload-label]');
            if (uploadLabel) uploadLabel.textContent = row && row.has_upload ? 'Re-upload Image' : 'Upload Image';
        }
        if (fileInput) fileInput.hidden = false;
        if (signBtn) {
            var canAdviser = role === 'adviser' && row && row.status === 'sent_to_adviser';
            var canCrad = isCrad && row && row.status !== 'clearance_done' && (
                !!row.can_crad_sign
                || !!(row.form_verified && row.has_upload && row.has_adviser_signature)
            );
            signBtn.hidden = !(canAdviser || canCrad);
            signBtn.disabled = false;
            signBtn.title = '';
        }
        if (printBtn) printBtn.hidden = !row || (isCrad && !(row && row.form_verified && row.has_upload));
        if (downloadBtn) downloadBtn.hidden = !row;
        if (detailEl) detailEl.hidden = !row;
        if (pickEl) pickEl.hidden = !(role === 'adviser') || !!row;
        if (emptyEl) {
            if (role === 'student') {
                emptyEl.hidden = !!(row && row.form_html);
                if (emptyText) {
                    emptyText.textContent = (row && row.locked_reason)
                        ? row.locked_reason
                        : 'Open a clearance in the inbox. Research 1 is for Pre-Oral. Research 2 opens after Research 1 is finished and its payment is approved.';
                }
            } else if (role === 'adviser') {
                emptyEl.hidden = true;
            } else {
                emptyEl.hidden = true;
            }
        }
        if (uploadGate) uploadGate.hidden = !(isCrad && row && !(row.form_verified && row.has_upload));
        if (misAaNote) {
            misAaNote.hidden = true;
        }
        if (uploadPreview) uploadPreview.hidden = true;
        if (uploadView) uploadView.innerHTML = '';
        if (checkWrap) checkWrap.hidden = !(isCrad && row && row.has_upload);
        if (checkAdviser) checkAdviser.checked = !!(row && row.has_adviser_signature);
        if (checkMis) checkMis.checked = !!(row && row.mis_verified);
        if (checkAa) checkAa.checked = !!(row && row.aa_verified);
        if (uploadName) uploadName.textContent = row && row.uploaded_original ? row.uploaded_original : '';
        shouldScroll = false;
    }

    function renderRows(rows) {
        if (!listBody) return;
        var isStudent = role === 'student';
        if (!rows || !rows.length) {
            listBody.innerHTML = '<tr><td colspan="' + (isStudent ? 4 : 6) + '" class="text-muted">No clearance forms yet.</td></tr>';
            return;
        }
        listBody.innerHTML = rows.map(function (row) {
            var active = '';
            if (current && row.id && String(current.id) === String(row.id)) active = ' class="table-active"';
            else if (!current && selectedStage && row.research_stage === selectedStage) active = ' class="table-active"';
            if (isStudent) {
                return '<tr' + active + ' data-rsc-open="' + (row.id || 0) + '" data-rsc-stage="' + (row.research_stage || 'research_1') + '">'
                    + '<td><strong>' + (row.stage_label || '') + '</strong></td>'
                    + '<td>' + (row.or_number || '—') + '</td>'
                    + '<td>' + (row.status_label || row.status || 'Not available yet') + '</td>'
                    + '<td><button type="button" class="btn btn-sm btn-outline-primary" data-rsc-open="' + (row.id || 0) + '" data-rsc-stage="' + (row.research_stage || 'research_1') + '">Open</button></td>'
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
                    var cradRow = data.clearance;
                    if (!cradRow && data.rows && data.rows.length) {
                        cradRow = data.rows[0];
                    }
                    if (cradRow) {
                        selectedId = String(cradRow.id);
                        applyClearance(cradRow);
                    } else {
                        selectedId = '';
                        applyClearance(null);
                    }
                    if (emptyEl) emptyEl.hidden = !!(data.rows && data.rows.length);
                    if (groupSelect && data.rows) {
                        var gid = selectedId;
                        groupSelect.innerHTML = data.rows.map(function (row) {
                            return '<option value="' + row.id + '"' + (String(row.id) === String(gid) ? ' selected' : '') + '>'
                                + ((row.stage_label ? row.stage_label + ' · ' : '') + (row.leader_group_no || ('#' + row.id))) + '</option>';
                        }).join('');
                        groupSelect.hidden = data.rows.length < 2;
                    }
                    if (data.rows) renderRows(data.rows);
                } else if (selectedId && data.clearance) {
                    selectedId = String(data.clearance.id);
                    applyClearance(data.clearance);
                    if (data.rows) renderRows(data.rows);
                    if (emptyEl && role === 'adviser') emptyEl.hidden = !!(data.rows && data.rows.length);
                    if (pickEl && role === 'adviser') pickEl.hidden = !!(selectedId || !(data.rows && data.rows.length));
                } else if (!selectedId) {
                    applyClearance(null);
                    if (data.rows) renderRows(data.rows);
                    if (emptyEl && role === 'adviser') emptyEl.hidden = !!(data.rows && data.rows.length);
                    if (pickEl && role === 'adviser') pickEl.hidden = !!(selectedId || !(data.rows && data.rows.length));
                } else {
                    selectedId = '';
                    applyClearance(null);
                    if (data.rows) renderRows(data.rows);
                }
            })
            .catch(function () {});
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', function () {
            sendBtn.disabled = true;
            post('send_to_adviser').then(function (data) {
                if (data && data.ok && data.clearance) applyClearance(data.clearance);
                else if (data && data.error) alert(data.error);
            }).finally(function () { sendBtn.disabled = false; });
        });
    }

    function uploadPickedFile() {
        if (uploading || !fileInput || !fileInput.files || !fileInput.files[0]) {
            return;
        }
        var picked = fileInput.files[0];
        if (!/\.(png|jpe?g)$/i.test(picked.name || '')) {
            alert('Upload a PNG or JPG picture of the Research Services Clearance form.');
            fileInput.value = '';
            return;
        }
        var fd = new FormData();
        fd.append('clearance_file', picked);
        uploading = true;
        post('crad_receive', fd).then(function (data) {
            var name = picked.name;
            fileInput.value = '';
            if (data && data.ok) {
                if (data.clearance && data.clearance.uploaded_url) {
                    current = Object.assign({}, current || {}, data.clearance);
                    if (formBox) {
                        formBox.hidden = false;
                        formBox.innerHTML = '<img class="rsc-upload-img" alt="Uploaded clearance form" src="'
                            + data.clearance.uploaded_url + '">';
                    }
                }
                refresh();
                return;
            }
            alert((data && data.error) || 'Could not re-upload the clearance form. Please try again.');
        }).catch(function () {
            alert('Could not re-upload the clearance form. Please try again.');
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

    function saveVerify() {
        if (!isCrad || !current) return;
        post('crad_verify', {
            mis_verified: checkMis && checkMis.checked ? '1' : '0',
            aa_verified: checkAa && checkAa.checked ? '1' : '0'
        }).then(function (data) {
            if (data && data.ok && data.clearance) applyClearance(data.clearance);
            else if (data && data.error) alert(data.error);
        });
    }
    if (checkMis) checkMis.addEventListener('change', saveVerify);
    if (checkAa) checkAa.addEventListener('change', saveVerify);

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
        shouldScroll = true;
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

    var modal = document.getElementById('rscSigModal');
    var canvas = document.getElementById('rscSigCanvas');
    var ctx = canvas ? canvas.getContext('2d') : null;
    var drawing = false;
    var dirty = false;

    function sizeCanvas() {
        if (!canvas) return;
        var ratio = window.devicePixelRatio || 1;
        var w = canvas.clientWidth || 460;
        var h = canvas.clientHeight || 160;
        canvas.width = Math.floor(w * ratio);
        canvas.height = Math.floor(h * ratio);
        if (ctx) {
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#111';
        }
        dirty = false;
    }

    function pos(ev) {
        var rect = canvas.getBoundingClientRect();
        var t = ev.touches ? ev.touches[0] : ev;
        return { x: t.clientX - rect.left, y: t.clientY - rect.top };
    }

    function openSig() {
        if (isCrad && (!current || !(current.can_crad_sign || (current.form_verified && current.has_upload && current.has_adviser_signature)))) {
            alert('Upload the printed clearance form with the Adviser, MIS, and AA signatures first.');
            return;
        }
        if (!modal) return;
        modal.style.display = 'block';
        sizeCanvas();
    }

    function closeSig() {
        if (modal) modal.style.display = 'none';
    }

    if (signBtn) signBtn.addEventListener('click', openSig);
    document.querySelectorAll('[data-rsc-sig-close]').forEach(function (el) {
        el.addEventListener('click', closeSig);
    });
    var clearBtn = document.getElementById('rscSigClear');
    if (clearBtn) clearBtn.addEventListener('click', sizeCanvas);
    var confirmBtn = document.getElementById('rscSigConfirm');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (!dirty) {
                var err = document.getElementById('rscSigError');
                if (err) err.style.display = 'block';
                return;
            }
            var action = role === 'adviser' ? 'adviser_sign' : 'crad_sign';
            confirmBtn.disabled = true;
            post(action, { signature: canvas.toDataURL('image/png') }).then(function (data) {
                if (data && data.ok && data.clearance) {
                    applyClearance(data.clearance);
                    closeSig();
                } else if (data && data.error) {
                    alert(data.error);
                }
            }).finally(function () { confirmBtn.disabled = false; });
        });
    }

    if (canvas && ctx) {
        canvas.addEventListener('pointerdown', function (ev) {
            drawing = true;
            var p = pos(ev);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            canvas.setPointerCapture(ev.pointerId);
        });
        canvas.addEventListener('pointermove', function (ev) {
            if (!drawing) return;
            var p = pos(ev);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            dirty = true;
        });
        canvas.addEventListener('pointerup', function () { drawing = false; });
        canvas.addEventListener('pointerleave', function () { drawing = false; });
    }

    refresh();
    window.setInterval(refresh, 1000);
})();
