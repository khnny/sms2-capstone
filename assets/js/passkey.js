/**
 * SMS 2 – Passkey (WebAuthn) client helpers
 */
(function () {
    function b64urlToBuf(b64url) {
        var pad = '='.repeat((4 - (b64url.length % 4)) % 4);
        var b64 = (b64url + pad).replace(/-/g, '+').replace(/_/g, '/');
        var str = atob(b64);
        var buf = new ArrayBuffer(str.length);
        var view = new Uint8Array(buf);
        for (var i = 0; i < str.length; i++) view[i] = str.charCodeAt(i);
        return buf;
    }

    function bufToB64url(buf) {
        var bytes = buf instanceof ArrayBuffer ? new Uint8Array(buf) : new Uint8Array(buf.buffer || buf);
        var str = '';
        for (var i = 0; i < bytes.length; i++) str += String.fromCharCode(bytes[i]);
        return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    }

    function prepareCreateOptions(options) {
        var o = Object.assign({}, options);
        o.challenge = b64urlToBuf(options.challenge);
        o.user = Object.assign({}, options.user, { id: b64urlToBuf(options.user.id) });
        if (options.excludeCredentials && options.excludeCredentials.length) {
            o.excludeCredentials = options.excludeCredentials.map(function (c) {
                return {
                    type: c.type || 'public-key',
                    id: b64urlToBuf(c.id),
                    transports: c.transports
                };
            });
        } else {
            delete o.excludeCredentials;
        }
        return o;
    }

    function prepareGetOptions(options) {
        var o = Object.assign({}, options);
        o.challenge = b64urlToBuf(options.challenge);
        if (options.allowCredentials && options.allowCredentials.length) {
            o.allowCredentials = options.allowCredentials.map(function (c) {
                return {
                    type: c.type || 'public-key',
                    id: b64urlToBuf(c.id),
                    transports: c.transports || ['internal', 'hybrid', 'usb', 'nfc', 'ble']
                };
            });
        } else {
            delete o.allowCredentials;
        }
        return o;
    }

    async function postJson(url, body) {
        var res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        });
        var data = await res.json().catch(function () { return {}; });
        if (!res.ok || !data.ok) {
            throw new Error((data && data.error) || 'Request failed');
        }
        return data;
    }

    function supported() {
        return !!(window.PublicKeyCredential && navigator.credentials && navigator.credentials.create);
    }

    function browserUpdateHint() {
        return 'Update your browser, then try again. Use Chrome, Edge, or Safari.';
    }

    function assertCanUsePasskeys(kind) {
        kind = kind || 'register';
        if (!supported()) {
            throw new Error(
                kind === 'login'
                    ? 'Passkey sign-in isn’t available. Use email and password instead.'
                    : browserUpdateHint()
            );
        }
        if (!window.isSecureContext || (/^\d+\.\d+\.\d+\.\d+$/.test(window.location.hostname || '') && window.location.hostname !== '127.0.0.1')) {
            throw new Error(
                kind === 'login'
                    ? 'Passkey sign-in isn’t available. Use email and password instead.'
                    : 'Passkeys need a secure site address. Try again from this device’s usual sign-in page.'
            );
        }
    }

    function friendlyWebAuthnError(err, kind) {
        var name = err && err.name ? err.name : '';
        var msg = (err && err.message) ? String(err.message) : '';

        if (kind === 'login') {
            if (name === 'AbortError') {
                return 'Passkey sign-in was cancelled. Try again or use another method.';
            }
            if (name === 'NotAllowedError' || /timed out or was not allowed/i.test(msg)) {
                return 'No matching passkey. Try again, or use another method to sign in.';
            }
            if (name === 'NotSupportedError' || name === 'SecurityError') {
                return 'Passkey sign-in isn’t available. Use email and password instead.';
            }
            return 'Couldn’t sign in with passkey. Try again or use another method.';
        }

        if (name === 'AbortError') {
            return 'Passkey setup was cancelled.';
        }
        if (name === 'NotAllowedError' || /timed out or was not allowed/i.test(msg)) {
            return 'Passkey setup was cancelled or timed out. Try again.';
        }
        if (name === 'InvalidStateError') {
            return 'This device already has a passkey for this account. Remove it, then add a new one.';
        }
        if (name === 'NotSupportedError') {
            return browserUpdateHint();
        }
        if (name === 'SecurityError') {
            return 'Passkey setup failed. Try again from this device’s usual sign-in page.';
        }
        return msg || 'Could not create passkey.';
    }

    function friendlyLoginMessage(err) {
        var msg = (err && err.message) ? String(err.message) : '';
        if (/matching passkey|try again|another method|cancelled|isn’t available|aren’t available|email and password/i.test(msg)) {
            return msg;
        }
        if (/unknown passkey|origin|relying party|incomplete|request failed/i.test(msg)) {
            return 'No matching passkey. Try again, or use another method to sign in.';
        }
        return 'Couldn’t sign in with passkey. Try again or use another method.';
    }

    function showMsg(el, text, ok) {
        if (!el) return;
        el.hidden = false;
        el.textContent = text;
        el.className = 'small mb-2 ' + (ok ? 'text-success' : 'text-danger');
    }

    /* ── Minimal CBOR decode (maps / bytes / ints) for attestation authData ── */
    function cborDecode(bytes, offset) {
        offset = offset || 0;
        var initial = bytes[offset++];
        var major = initial >> 5;
        var additional = initial & 31;
        var value;
        var readLen = function (add) {
            if (add < 24) return { v: add, o: offset };
            if (add === 24) return { v: bytes[offset++], o: offset };
            if (add === 25) {
                var v16 = (bytes[offset] << 8) | bytes[offset + 1];
                offset += 2;
                return { v: v16, o: offset };
            }
            if (add === 26) {
                var v32 = ((bytes[offset] << 24) | (bytes[offset + 1] << 16) | (bytes[offset + 2] << 8) | bytes[offset + 3]) >>> 0;
                offset += 4;
                return { v: v32, o: offset };
            }
            throw new Error('CBOR length unsupported');
        };

        if (major === 0) {
            value = readLen(additional);
            return { value: value.v, offset: value.o };
        }
        if (major === 1) {
            value = readLen(additional);
            return { value: -1 - value.v, offset: value.o };
        }
        if (major === 2) {
            value = readLen(additional);
            offset = value.o;
            var b = bytes.slice(offset, offset + value.v);
            return { value: b, offset: offset + value.v };
        }
        if (major === 3) {
            value = readLen(additional);
            offset = value.o;
            var s = bytes.slice(offset, offset + value.v);
            var str = '';
            for (var i = 0; i < s.length; i++) str += String.fromCharCode(s[i]);
            return { value: str, offset: offset + value.v };
        }
        if (major === 4) {
            value = readLen(additional);
            offset = value.o;
            var arr = [];
            for (var a = 0; a < value.v; a++) {
                var item = cborDecode(bytes, offset);
                arr.push(item.value);
                offset = item.offset;
            }
            return { value: arr, offset: offset };
        }
        if (major === 5) {
            value = readLen(additional);
            offset = value.o;
            var map = {};
            for (var m = 0; m < value.v; m++) {
                var k = cborDecode(bytes, offset);
                offset = k.offset;
                var v = cborDecode(bytes, offset);
                offset = v.offset;
                map[k.value] = v.value;
            }
            return { value: map, offset: offset };
        }
        throw new Error('CBOR major unsupported: ' + major);
    }

    function p256CoseToSpki(xBytes, yBytes) {
        if (!xBytes || !yBytes || xBytes.length !== 32 || yBytes.length !== 32) {
            return null;
        }
        // SubjectPublicKeyInfo for P-256 uncompressed point
        var prefix = [
            0x30, 0x59, 0x30, 0x13, 0x06, 0x07, 0x2a, 0x86, 0x48, 0xce, 0x3d, 0x02, 0x01,
            0x06, 0x08, 0x2a, 0x86, 0x48, 0xce, 0x3d, 0x03, 0x01, 0x07, 0x03, 0x42, 0x00, 0x04
        ];
        var out = new Uint8Array(prefix.length + 64);
        out.set(prefix, 0);
        out.set(xBytes, prefix.length);
        out.set(yBytes, prefix.length + 32);
        return out.buffer;
    }

    function publicKeyFromAttestationObject(attestationObjectBuf) {
        try {
            var bytes = new Uint8Array(attestationObjectBuf);
            var decoded = cborDecode(bytes, 0);
            var map = decoded.value;
            if (!map || !map.authData) return null;
            var authData = map.authData instanceof Uint8Array ? map.authData : new Uint8Array(map.authData);
            if (authData.length < 55) return null;
            var flags = authData[32];
            if ((flags & 0x40) === 0) return null; // no attested credential data
            var credIdLen = (authData[53] << 8) | authData[54];
            var coseStart = 55 + credIdLen;
            var cose = cborDecode(authData, coseStart).value;
            // COSE_Key:  -2 = x, -3 = y for EC2
            var x = cose[-2] || cose['-2'];
            var y = cose[-3] || cose['-3'];
            if (!(x instanceof Uint8Array)) x = new Uint8Array(x);
            if (!(y instanceof Uint8Array)) y = new Uint8Array(y);
            return p256CoseToSpki(x, y);
        } catch (e) {
            return null;
        }
    }

    function extractPublicKey(cred) {
        var pkBuf = null;
        try {
            if (cred.response && typeof cred.response.getPublicKey === 'function') {
                pkBuf = cred.response.getPublicKey();
            }
        } catch (e) { /* ignore */ }
        try {
            if (!pkBuf && typeof cred.getPublicKey === 'function') {
                pkBuf = cred.getPublicKey();
            }
        } catch (e2) { /* ignore */ }
        if (!pkBuf && cred.response && cred.response.attestationObject) {
            pkBuf = publicKeyFromAttestationObject(cred.response.attestationObject);
        }
        return pkBuf ? bufToB64url(pkBuf) : '';
    }

    async function register(api, csrf, deviceName) {
        assertCanUsePasskeys('register');
        var optRes = await postJson(api, { action: 'register_options', csrf: csrf });

        var createOnce = async function (opts) {
            return navigator.credentials.create({ publicKey: prepareCreateOptions(opts) });
        };

        var cred;
        try {
            cred = await createOnce(optRes.options);
        } catch (err) {
            var canRetry = err && (err.name === 'NotSupportedError' || err.name === 'NotAllowedError');
            if (canRetry && optRes.options && optRes.options.authenticatorSelection) {
                var soft = JSON.parse(JSON.stringify(optRes.options));
                soft.authenticatorSelection = {
                    residentKey: 'preferred',
                    userVerification: 'preferred'
                };
                try {
                    cred = await createOnce(soft);
                } catch (err2) {
                    throw new Error(friendlyWebAuthnError(err2, 'register'));
                }
            } else {
                throw new Error(friendlyWebAuthnError(err, 'register'));
            }
        }
        if (!cred) throw new Error('Passkey setup cancelled.');

        var publicKey = extractPublicKey(cred);
        if (!publicKey) {
            throw new Error(
                'Passkey could not be saved. Remove it from your device, then add it again.'
            );
        }

        var attestation = {
            id: typeof cred.id === 'string' ? cred.id : bufToB64url(cred.rawId),
            clientDataJSON: bufToB64url(cred.response.clientDataJSON),
            publicKey: publicKey
        };
        await postJson(api, {
            action: 'register_verify',
            csrf: csrf,
            device_name: deviceName || 'Passkey',
            credential: attestation
        });
    }

    async function login(api, username) {
        assertCanUsePasskeys('login');
        username = (username || '').trim();
        var optRes = await postJson(api, {
            action: 'login_options',
            username: username
        });
        var assertion;
        try {
            assertion = await navigator.credentials.get({
                publicKey: prepareGetOptions(optRes.options)
            });
        } catch (err) {
            throw new Error(friendlyWebAuthnError(err, 'login'));
        }
        if (!assertion) throw new Error('Passkey sign-in was cancelled. Try again or use another method.');

        var payload = {
            id: typeof assertion.id === 'string' ? assertion.id : bufToB64url(assertion.rawId),
            clientDataJSON: bufToB64url(assertion.response.clientDataJSON),
            authenticatorData: bufToB64url(assertion.response.authenticatorData),
            signature: bufToB64url(assertion.response.signature)
        };
        return postJson(api, { action: 'login_verify', credential: payload });
    }

    async function removeKey(api, csrf, id, proof) {
        var body = Object.assign({
            action: 'delete',
            csrf: csrf,
            passkey_id: id
        }, proof || {});
        await postJson(api, body);
    }

    async function prepareRemove(api, csrf, id) {
        return postJson(api, { action: 'delete_prepare', csrf: csrf, passkey_id: id });
    }

    async function resendRemoveEmail(api, csrf) {
        return postJson(api, { action: 'delete_send_email', csrf: csrf });
    }

    function noticeIcon(name) {
        return window.smsIconHtml ? window.smsIconHtml(name) : '';
    }

    function isErrorNotice(text) {
        var value = String(text || '').toLowerCase();
        return value.indexOf('could not') !== -1
            || value.indexOf('failed') !== -1
            || value.indexOf('invalid') !== -1
            || value.indexOf('incorrect') !== -1;
    }

    function setRemoveErr(text) {
        var el = document.getElementById('smsPasskeyRemoveErr');
        if (!el) return;
        if (!text) {
            el.hidden = true;
            el.innerHTML = '';
            return;
        }
        el.hidden = false;
        el.className = 'sms-confirm-notice sms-confirm-notice--danger w-100';
        el.innerHTML = noticeIcon('alert-circle') + '<span>' + String(text) + '</span>';
    }

    function setRemoveInfo(text, otpDev) {
        var el = document.getElementById('smsPasskeyRemoveInfo');
        if (!el) return;
        if (!text && !otpDev) {
            el.hidden = true;
            el.innerHTML = '';
            return;
        }
        el.hidden = false;
        var tone = isErrorNotice(text) ? 'danger' : 'info';
        el.className = 'sms-confirm-notice sms-confirm-notice--' + tone + ' w-100';
        var body = text ? String(text) : '';
        if (otpDev) {
            body += (body ? ' ' : '') + '<strong>Local OTP:</strong> <code>' + String(otpDev) + '</code>';
        }
        el.innerHTML = noticeIcon(tone === 'danger' ? 'alert-circle' : 'info-circle')
            + '<span>' + body + '</span>';
    }

    function applyRemoveNotice(message, otpDev) {
        var text = String(message || '');
        var dev = String(otpDev || '');
        setRemoveErr('');
        setRemoveInfo('');
        if (!text && !dev) return;
        if (isErrorNotice(text)) {
            setRemoveErr(text);
            if (dev) {
                setRemoveInfo('', dev);
            }
            return;
        }
        setRemoveInfo(text, dev);
    }

    function showVerifyPanel(method) {
        ['smsPkVerifyAuthenticator', 'smsPkVerifyEmail', 'smsPkVerifyPassword'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.hidden = true;
        });
        var map = {
            authenticator: 'smsPkVerifyAuthenticator',
            email: 'smsPkVerifyEmail',
            password: 'smsPkVerifyPassword'
        };
        var panel = document.getElementById(map[method] || 'smsPkVerifyPassword');
        if (panel) panel.hidden = false;
        if (window.smsSecurityUi && typeof window.smsSecurityUi.enhancePasswords === 'function') {
            window.smsSecurityUi.enhancePasswords(document.getElementById('smsPasskeyRemoveModal'));
        }
    }

    function collectRemoveProof(method) {
        if (method === 'authenticator') {
            return {
                method: 'authenticator',
                totp_code: (document.getElementById('smsPkTotp') || {}).value || ''
            };
        }
        if (method === 'email') {
            return {
                method: 'email',
                otp_code: (document.getElementById('smsPkOtp') || {}).value || ''
            };
        }
        return {
            method: 'password',
            password: (document.getElementById('smsPkPassword') || {}).value || ''
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        var card = document.getElementById('smsPasskeyCard');
        if (card) {
            var api = card.getAttribute('data-passkey-api') || '';
            var csrf = card.getAttribute('data-csrf') || '';
            var msg = document.getElementById('smsPasskeyMsg');
            var addBtn = document.getElementById('smsPasskeyAdd');
            var pendingId = 0;
            var pendingMethod = card.getAttribute('data-remove-method') || 'password';
            var modalEl = document.getElementById('smsPasskeyRemoveModal');
            var modal = null;
            if (modalEl && window.bootstrap && bootstrap.Modal) {
                modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            }

            if (!supported()) {
                showMsg(msg, browserUpdateHint(), false);
                if (addBtn) addBtn.disabled = true;
            }

            if (addBtn) {
                addBtn.addEventListener('click', async function () {
                    addBtn.disabled = true;
                    try {
                        assertCanUsePasskeys('register');
                        var name = window.prompt('Name this passkey (e.g. This PC, Phone)', 'This device') || 'Passkey';
                        await register(api, csrf, name);
                        showMsg(msg, 'Passkey added. Reloading…', true);
                        window.location.reload();
                    } catch (err) {
                        showMsg(msg, (err && err.message) ? err.message : 'Could not add passkey.', false);
                        addBtn.disabled = false;
                    }
                });
            }

            card.querySelectorAll('.sms-passkey-remove').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = parseInt(btn.getAttribute('data-id') || '0', 10);
                    var name = btn.getAttribute('data-name') || 'this passkey';
                    if (!id) return;

                    var startRemoval = async function () {
                        btn.disabled = true;
                        setRemoveErr('');
                        setRemoveInfo('');
                        try {
                            var prep = await prepareRemove(api, csrf, id);
                            pendingId = id;
                            pendingMethod = prep.method || 'password';
                            var lead = document.getElementById('smsPasskeyRemoveLead');
                            if (lead) {
                                lead.textContent = 'Are you sure you want to remove “' + name + '”? Only this passkey will be removed.';
                            }
                            var title = document.getElementById('smsPasskeyRemoveTitle');
                            if (title) title.textContent = 'Remove this passkey?';
                            applyRemoveNotice(prep.message || '', prep.otp_dev || '');
                            showVerifyPanel(pendingMethod);
                            ['smsPkTotp', 'smsPkOtp', 'smsPkPassword'].forEach(function (fid) {
                                var f = document.getElementById(fid);
                                if (f) f.value = '';
                            });
                            if (modal) {
                                modal.show();
                            } else {
                                var proof = null;
                                if (pendingMethod === 'authenticator') {
                                    proof = { method: 'authenticator', totp_code: window.prompt('Authenticator code') || '' };
                                } else if (pendingMethod === 'email') {
                                    proof = { method: 'email', otp_code: window.prompt((prep.message || 'Email code') + '\nEnter code') || '' };
                                } else {
                                    proof = { method: 'password', password: window.prompt('Enter your password') || '' };
                                }
                                await removeKey(api, csrf, pendingId, proof);
                                window.location.reload();
                                return;
                            }
                        } catch (err) {
                            showMsg(msg, (err && err.message) ? err.message : 'Could not start removal.', false);
                            pendingId = 0;
                        }
                        btn.disabled = false;
                    };

                    if (typeof window.smsConfirm === 'function') {
                        window.smsConfirm(
                            'Are you sure you want to remove “' + name + '”? Only this passkey will be removed; any others stay.',
                            startRemoval,
                            { title: 'Remove this passkey?', okText: 'Yes, continue' }
                        );
                    } else if (window.confirm('Are you sure you want to remove “' + name + '”?')) {
                        startRemoval();
                    }
                });
            });

            if (modalEl) {
                modalEl.addEventListener('hidden.bs.modal', function () {
                    pendingId = 0;
                    setRemoveErr('');
                    setRemoveInfo('');
                    var confirmBtnReset = document.getElementById('smsPasskeyRemoveConfirm');
                    if (confirmBtnReset) confirmBtnReset.disabled = false;
                });
            }

            var confirmBtn = document.getElementById('smsPasskeyRemoveConfirm');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', async function () {
                    var idToRemove = pendingId;
                    if (!idToRemove) {
                        setRemoveErr('Select a passkey first.');
                        return;
                    }
                    confirmBtn.disabled = true;
                    setRemoveErr('');
                    try {
                        var proof = collectRemoveProof(pendingMethod);
                        await removeKey(api, csrf, idToRemove, proof);
                        pendingId = 0;
                        if (modal) modal.hide();
                        showMsg(msg, 'Passkey removed. Reloading…', true);
                        window.location.reload();
                    } catch (err) {
                        setRemoveErr((err && err.message) ? err.message : 'Could not remove passkey.');
                        confirmBtn.disabled = false;
                    }
                });
            }

            var resendBtn = document.getElementById('smsPkResendEmail');
            if (resendBtn) {
                resendBtn.addEventListener('click', async function () {
                    resendBtn.disabled = true;
                    try {
                        var out = await resendRemoveEmail(api, csrf);
                        applyRemoveNotice(out.message || 'Code sent.', out.otp_dev || '');
                    } catch (err) {
                        setRemoveErr((err && err.message) ? err.message : 'Could not resend code.');
                    }
                    resendBtn.disabled = false;
                });
            }
        }

        var loginBtn = document.getElementById('smsPasskeyLoginBtn');
        if (loginBtn) {
            var loginApi = loginBtn.getAttribute('data-passkey-api') || '';
            var loginMsg = document.getElementById('smsPasskeyLoginMsg');
            if (!supported()) {
                showMsg(loginMsg, 'Passkey sign-in isn’t available. Use email and password instead.', false);
                loginBtn.disabled = true;
                loginBtn.dataset.passkeyUnsupported = '1';
            }
            loginBtn.addEventListener('click', async function () {
                loginBtn.disabled = true;
                try {
                    var userInput = document.getElementById('username');
                    var username = userInput ? userInput.value.trim() : '';
                    var result = await login(loginApi, username);
                    showMsg(loginMsg, 'Signed in with passkey…', true);
                    window.location.href = result.redirect || '/';
                } catch (err) {
                    showMsg(loginMsg, friendlyLoginMessage(err), false);
                    loginBtn.disabled = false;
                }
            });
        }
    });

    window.SMS2Passkey = { supported: supported, register: register, login: login };
})();
