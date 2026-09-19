<?php
/**
 * Shared Google Authenticator setup actions + markup helpers.
 */
declare(strict_types=1);

require_once __DIR__ . '/totp.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/security-ui.php';

/**
 * @return array{handled:bool,success:string,error:string,otp_dev:string,redirect_extra:string}
 */
function smsHandleAuthenticatorPost(int $userId, string $action, array $post, string $accountLabel = ''): array
{
    $out = [
        'handled' => true,
        'success' => '',
        'error' => '',
        'otp_dev' => '',
        'redirect_extra' => '',
    ];

    $pdo = db();
    $row = null;
    if ($pdo) {
        $stmt = $pdo->prepare('SELECT id, email, full_name, password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch() ?: null;
    }
    if (!$row) {
        $out['error'] = 'Account not found.';
        return $out;
    }

    if ($action === 'auth_turn_on') {
        // Reveal password unlock step (Roblox-style)
        $_SESSION['auth_unlock_ui'] = $userId;
        unset($_SESSION['auth_email_otp_sent']);
        $out['redirect_extra'] = 'auth_on=1';
        return $out;
    }

    if ($action === 'auth_request_setup') {
        $password = (string) ($post['confirm_password'] ?? '');
        $emailOtp = trim((string) ($post['email_otp'] ?? ''));
        $unlocked = false;
        if ($password !== '' && password_verify($password, (string) $row['password_hash'])) {
            $unlocked = true;
        } elseif ($emailOtp !== '' && smsVerifyOtp($userId, 'auth_setup', $emailOtp)) {
            $unlocked = true;
        }
        if (!$unlocked) {
            $out['error'] = 'Enter your password, or an email code, to continue.';
            $_SESSION['auth_unlock_ui'] = $userId;
            $out['redirect_extra'] = 'auth_on=1';
            return $out;
        }
        $secret = smsAuthenticatorBeginSetup($userId);
        if (!$secret) {
            $out['error'] = 'Could not start Authenticator setup.';
            return $out;
        }
        unset($_SESSION['auth_unlock_ui'], $_SESSION['auth_email_otp_sent']);
        $_SESSION['auth_setup_user'] = $userId;
        $_SESSION['auth_setup_at'] = time();
        $out['success'] = 'Scan the QR code, then enter the app code to activate.';
        $out['redirect_extra'] = 'auth_setup=1';
        return $out;
    }

    if ($action === 'auth_send_setup_otp') {
        $issued = smsIssueOtpToEmail($userId, 'auth_setup', null, 10, 'Authenticator setup');
        $_SESSION['auth_unlock_ui'] = $userId;
        $_SESSION['auth_email_otp_sent'] = 1;
        if (!empty($issued['show_local']) && !empty($issued['code'])) {
            $out['otp_dev'] = (string) $issued['code'];
        }
        $out['success'] = !empty($issued['emailed'])
            ? 'Email code sent to ' . $issued['to'] . '. Enter it below.'
            : 'Could not email code' . ($issued['error'] !== '' ? ': ' . $issued['error'] : '') . '.';
        $out['redirect_extra'] = 'auth_on=1';
        return $out;
    }

    if ($action === 'auth_confirm_enable') {
        if ((int) ($_SESSION['auth_setup_user'] ?? 0) !== $userId) {
            $out['error'] = 'Setup session expired. Turn On again.';
            return $out;
        }
        $code = trim((string) ($post['totp_code'] ?? ''));
        if (!smsAuthenticatorConfirmEnable($userId, $code)) {
            $out['error'] = 'Invalid Authenticator code. Try again.';
            return $out;
        }
        unset(
            $_SESSION['auth_setup_user'],
            $_SESSION['auth_setup_at'],
            $_SESSION['auth_unlock_ui'],
            $_SESSION['auth_disable_ui'],
            $_SESSION['auth_disable_show_code']
        );
        $out['success'] = 'Authenticator is Active. It will be required at login.';
        logActivity('update', 'Enabled Google Authenticator', 'System', $userId);
        return $out;
    }

    if ($action === 'auth_cancel_setup') {
        unset(
            $_SESSION['auth_setup_user'],
            $_SESSION['auth_setup_at'],
            $_SESSION['auth_unlock_ui'],
            $_SESSION['auth_email_otp_sent']
        );
        $out['success'] = 'Cancelled.';
        return $out;
    }

    if ($action === 'auth_turn_off_start') {
        $_SESSION['auth_disable_ui'] = $userId;
        unset($_SESSION['auth_disable_show_code']);
        $out['redirect_extra'] = 'auth_off=1';
        return $out;
    }

    if ($action === 'auth_disable') {
        $password = (string) ($post['confirm_password'] ?? '');
        $totp = trim((string) ($post['totp_code'] ?? ''));
        $ok = false;
        if ($password !== '' && password_verify($password, (string) $row['password_hash'])) {
            $ok = true;
        } elseif ($totp !== '' && smsAuthenticatorVerifyLogin($userId, $totp)) {
            $ok = true;
        }
        if (!$ok) {
            $out['error'] = 'Enter your password, or an Authenticator code, to turn it off.';
            $_SESSION['auth_disable_ui'] = $userId;
            if ($totp !== '' || !empty($_SESSION['auth_disable_show_code'])) {
                $_SESSION['auth_disable_show_code'] = 1;
            }
            $out['redirect_extra'] = 'auth_off=1';
            return $out;
        }
        smsAuthenticatorDisable($userId);
        unset(
            $_SESSION['auth_setup_user'],
            $_SESSION['auth_setup_at'],
            $_SESSION['auth_unlock_ui'],
            $_SESSION['auth_email_otp_sent'],
            $_SESSION['auth_disable_ui'],
            $_SESSION['auth_disable_show_code']
        );
        $out['success'] = 'Authenticator is Inactive. Turn it on again to set up a new QR code.';
        logActivity('update', 'Disabled Google Authenticator', 'System', $userId);
        return $out;
    }

    if ($action === 'auth_disable_show_code') {
        $_SESSION['auth_disable_ui'] = $userId;
        $_SESSION['auth_disable_show_code'] = 1;
        $out['redirect_extra'] = 'auth_off=1';
        return $out;
    }

    if ($action === 'auth_disable_cancel') {
        unset($_SESSION['auth_disable_ui'], $_SESSION['auth_disable_show_code']);
        $out['success'] = 'Cancelled.';
        return $out;
    }

    $out['handled'] = false;
    return $out;
}

/**
 * Render Authenticator setup card HTML (simple Turn On → password flow).
 */
function smsRenderAuthenticatorCard(int $userId, string $formActionUrl, string $csrfFieldHtml, bool $asBox = false): void
{
    $auth = smsAuthenticatorGet($userId);
    $enabled = $auth && !empty($auth['enabled']);
    $inSetup = ((int) ($_SESSION['auth_setup_user'] ?? 0) === $userId)
        && !empty($_SESSION['auth_setup_at'])
        && ((int) $_SESSION['auth_setup_at'] + 900) >= time();
    $showUnlock = ((int) ($_SESSION['auth_unlock_ui'] ?? 0) === $userId)
        || (isset($_GET['auth_on']) && $_GET['auth_on'] === '1');
    $showDisable = $enabled && (
        ((int) ($_SESSION['auth_disable_ui'] ?? 0) === $userId)
        || (isset($_GET['auth_off']) && $_GET['auth_off'] === '1')
    );
    $disableShowCode = !empty($_SESSION['auth_disable_show_code']);
    $emailOtpSent = !empty($_SESSION['auth_email_otp_sent']);

    $pdo = db();
    if ($pdo) {
        $st = $pdo->prepare('SELECT email, full_name FROM users WHERE id = ? LIMIT 1');
        $st->execute([$userId]);
        $u = $st->fetch() ?: [];
        $label = ((string) ($u['email'] ?? '')) !== ''
            ? (string) $u['email']
            : ((string) ($u['full_name'] ?? 'user'));
    } else {
        $label = 'user';
    }

    $pendingSecret = null;
    if ($inSetup) {
        $row = smsAuthenticatorGet($userId);
        $pendingSecret = $row['pending_secret'] ?? ($row['secret'] ?? null);
        if (!$pendingSecret) {
            $pendingSecret = smsAuthenticatorBeginSetup($userId);
        }
    }
    $badge = '<span class="badge ' . ($enabled ? 'text-bg-success' : 'text-bg-secondary') . '">'
        . ($enabled ? 'Active' : 'Inactive') . '</span>';
    echo $asBox
        ? smsSecBoxStart('Authenticator', 'fa-mobile-alt', $badge)
        : smsSecCardStart('Authenticator', 'fa-mobile-alt', $badge);
    $qrSize = $asBox ? 200 : 280;
    ?>

            <?php if ($inSetup && $pendingSecret): ?>
                <?php $uri = smsTotpOtpAuthUri($pendingSecret, $label); ?>
                <p class="sms-sec-lead">Scan the QR with Google Authenticator, then enter the 6-digit app code to activate.</p>
                <div class="sms-sec-setup">
                    <div><?= smsTotpQrMarkup($uri, $qrSize) ?></div>
                    <div>
                        <p class="small mb-2">Or enter this key manually:</p>
                        <code class="d-inline-block px-2 py-1 bg-light rounded user-select-all"><?= e(trim(chunk_split($pendingSecret, 4, ' '))) ?></code>
                    </div>
                </div>
                <form method="POST" action="<?= e($formActionUrl) ?>" class="sms-sec-form">
                    <?= $csrfFieldHtml ?>
                    <input type="hidden" name="action" value="auth_confirm_enable">
                    <div class="mb-3">
                        <?= smsOtpInput('totp_code', ['id' => 'totp_code', 'required' => true, 'autofocus' => true, 'label' => 'App code', 'hint' => 'Paste or type the 6-digit code from your authenticator app.']) ?>
                    </div>
                    <button type="submit" class="sms-sec-btn sms-sec-btn-primary">Activate</button>
                    <button type="submit" name="action" value="auth_cancel_setup" class="btn btn-outline-secondary ms-1" formnovalidate>Cancel</button>
                </form>

            <?php elseif ($enabled && $showDisable): ?>
                <p class="sms-sec-lead">Confirm your identity to turn Authenticator off. You’ll need a new QR if you turn it on again.</p>
                <form method="POST" action="<?= e($formActionUrl) ?>" class="mb-2">
                    <?= $csrfFieldHtml ?>
                    <input type="hidden" name="action" value="auth_disable">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="disable_password">Password</label>
                        <?= smsPasswordInput([
                            'id' => 'disable_password',
                            'name' => 'confirm_password',
                            'autocomplete' => 'current-password',
                            'placeholder' => 'Enter your password',
                            'autofocus' => !$disableShowCode,
                        ]) ?>
                    </div>
                    <?php if ($disableShowCode): ?>
                        <div class="mb-3">
                            <?= smsOtpInput('totp_code', ['id' => 'disable_totp', 'required' => true, 'autofocus' => true, 'label' => 'Authenticator code']) ?>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="totp_code" value="">
                    <?php endif; ?>
                    <button type="submit" class="sms-sec-btn sms-sec-btn-danger">
                        <?= smsIcon('power-off', ['aria-hidden' => 'true']) ?>Turn Off
                    </button>
                    <button type="submit" name="action" value="auth_disable_cancel" class="btn btn-outline-secondary ms-1" formnovalidate>Cancel</button>
                </form>
                <?php if (!$disableShowCode): ?>
                    <form method="POST" action="<?= e($formActionUrl) ?>">
                        <?= $csrfFieldHtml ?>
                        <input type="hidden" name="action" value="auth_disable_show_code">
                        <button type="submit" class="btn btn-link btn-sm px-0">
                            Use Authenticator code instead
                        </button>
                    </form>
                <?php endif; ?>

            <?php elseif ($enabled): ?>
                <p class="sms-sec-lead">Required at login. Turn off to remove it; turning on again needs a new QR setup.</p>
                <form method="POST" action="<?= e($formActionUrl) ?>">
                    <?= $csrfFieldHtml ?>
                    <input type="hidden" name="action" value="auth_turn_off_start">
                    <button type="submit" class="sms-sec-btn sms-sec-btn-danger">
                        <?= smsIcon('power-off', ['aria-hidden' => 'true']) ?>Turn Off
                    </button>
                </form>

            <?php elseif ($showUnlock): ?>
                <p class="sms-sec-lead">Confirm your password (or email code) to unlock Authenticator setup.</p>
                <form method="POST" action="<?= e($formActionUrl) ?>" class="mb-2">
                    <?= $csrfFieldHtml ?>
                    <input type="hidden" name="action" value="auth_request_setup">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="confirm_password">Password</label>
                        <?= smsPasswordInput([
                            'id' => 'confirm_password',
                            'name' => 'confirm_password',
                            'autocomplete' => 'current-password',
                            'placeholder' => 'Enter your password',
                            'autofocus' => !$emailOtpSent,
                        ]) ?>
                    </div>
                    <?php if ($emailOtpSent): ?>
                        <div class="mb-3">
                            <?= smsOtpInput('email_otp', ['id' => 'email_otp', 'required' => true, 'autofocus' => true, 'label' => 'Email authentication code', 'hint' => 'Check your inbox for the 6-digit code.']) ?>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="email_otp" value="">
                    <?php endif; ?>
                    <button type="submit" class="sms-sec-btn sms-sec-btn-primary">
                        Continue
                    </button>
                    <button type="submit" name="action" value="auth_cancel_setup" class="btn btn-outline-secondary ms-1" formnovalidate>Cancel</button>
                </form>
                <?php if (!$emailOtpSent): ?>
                    <form method="POST" action="<?= e($formActionUrl) ?>">
                        <?= $csrfFieldHtml ?>
                        <input type="hidden" name="action" value="auth_send_setup_otp">
                        <button type="submit" class="btn btn-link btn-sm px-0">
                            Use email authentication code instead
                        </button>
                    </form>
                <?php endif; ?>

            <?php else: ?>
                <p class="sms-sec-lead">Add a second step at login with Google Authenticator.</p>
                <form method="POST" action="<?= e($formActionUrl) ?>">
                    <?= $csrfFieldHtml ?>
                    <input type="hidden" name="action" value="auth_turn_on">
                    <button type="submit" class="sms-sec-btn sms-sec-btn-primary">
                        <?= smsIcon('power-off', ['aria-hidden' => 'true']) ?>Turn On
                    </button>
                </form>
            <?php endif; ?>
    <?= $asBox ? smsSecBoxEnd() : smsSecCardEnd() ?>
    <?php
}
