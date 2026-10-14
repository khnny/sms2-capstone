<?php
/**
 * SMS 2 – Passkey WebAuthn API (JSON)
 *
 * Actions:
 *   register_options / register_verify  (authenticated)
 *   delete_prepare / delete_send_email / delete  (authenticated + step-up)
 *   login_options / login_verify        (public)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/passkey.php';
require_once ROOT_PATH . '/includes/security-workflow.php';
require_once ROOT_PATH . '/includes/audit.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function smsPasskeyJson(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$body = [];
if (is_string($raw) && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}

$action = (string) ($body['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '');
$csrf = (string) ($body['csrf'] ?? $_POST['csrf'] ?? '');

$needAuth = in_array($action, [
    'stepup_prepare',
    'stepup_send_email',
    'stepup_verify',
    'register_options',
    'register_verify',
    'delete',
    'delete_prepare',
    'delete_send_email',
], true);

if ($needAuth) {
    if (!isAuthenticated()) {
        smsPasskeyJson(['ok' => false, 'error' => 'Please sign in first.'], 401);
    }
    if (!csrfVerify($csrf)) {
        smsPasskeyJson(['ok' => false, 'error' => 'Security check failed.'], 403);
    }
}

try {

    if ($action === 'stepup_prepare') {
        $uid = (int) getCurrentUserId();
        $info = smsPasskeyStepUpMethod($uid);
        $payload = [
            'ok' => true,
            'method' => $info['method'],
            'label' => $info['label'],
            'email_masked' => $info['email_masked'] ?? '',
            'otp_dev' => '',
            'message' => '',
        ];

        if ($info['method'] === 'email') {
            $issued = smsIssueOtpToEmail($uid, 'passkey_add', null, 10, 'passkey setup');
            if (empty($issued['ok'])) {
                smsPasskeyJson(['ok' => false, 'error' => (string) ($issued['error'] ?: 'Could not send email code.')], 400);
            }
            $payload['message'] = !empty($issued['emailed'])
                ? 'We emailed a code to ' . ($info['email_masked'] ?? 'your email') . '.'
                : 'Could not email the code' . (($issued['error'] ?? '') !== '' ? ': ' . $issued['error'] : '') . '.';
            if (!empty($issued['show_local']) && !empty($issued['code'])) {
                $payload['otp_dev'] = (string) $issued['code'];
                $payload['message'] .= ' Local code shown below (SMTP not delivering).';
            }
        } elseif ($info['method'] === 'authenticator') {
            $payload['message'] = 'Enter the 6-digit code from Google Authenticator.';
        } else {
            $payload['message'] = 'Enter your account password to continue.';
        }

        smsPasskeyJson($payload);
    }

    if ($action === 'stepup_send_email') {
        $uid = (int) getCurrentUserId();
        $info = smsPasskeyStepUpMethod($uid);
        if ($info['method'] !== 'email') {
            smsPasskeyJson(['ok' => false, 'error' => 'Email verification is not required for your account.'], 400);
        }
        $issued = smsIssueOtpToEmail($uid, 'passkey_add', null, 10, 'passkey setup');
        if (empty($issued['ok'])) {
            smsPasskeyJson(['ok' => false, 'error' => (string) ($issued['error'] ?: 'Could not send email code.')], 400);
        }
        $out = [
            'ok' => true,
            'message' => !empty($issued['emailed'])
                ? 'Code resent to ' . ($info['email_masked'] ?? 'your email') . '.'
                : 'Could not email the code' . (($issued['error'] ?? '') !== '' ? ': ' . $issued['error'] : '') . '.',
            'otp_dev' => '',
        ];
        if (!empty($issued['show_local']) && !empty($issued['code'])) {
            $out['otp_dev'] = (string) $issued['code'];
        }
        smsPasskeyJson($out);
    }

    if ($action === 'stepup_verify') {
        $uid = (int) getCurrentUserId();
        $method = (string) ($body['method'] ?? '');
        $proof = smsPasskeyVerifyStepUpProof($uid, $method, $body, 'passkey_add');
        if (empty($proof['ok'])) {
            logActivity('security', 'Passkey step-up failed (' . $method . '): ' . ($proof['error'] ?? ''), 'System', $uid);
            smsPasskeyJson(['ok' => false, 'error' => $proof['error'] ?: 'Verification failed.'], 400);
        }
        smsPasskeyStepUpGrant($uid, 300);
        logActivity('security', 'Passkey step-up succeeded via ' . $method, 'System', $uid);
        smsPasskeyJson(['ok' => true, 'expires_in' => 300]);
    }
    if ($action === 'register_options') {
        $uid = (int) getCurrentUserId();
        if (!smsPasskeyStepUpOk($uid)) {
            smsPasskeyJson(['ok' => false, 'error' => 'Verify your identity before adding a passkey.', 'need_stepup' => true], 403);
        }
        try {
            $opts = smsPasskeyRegisterOptions($uid);
        } catch (Throwable $e) {
            smsPasskeyJson(['ok' => false, 'error' => $e->getMessage() ?: 'Could not start passkey setup.', 'need_stepup' => true], 403);
        }
        smsPasskeyJson(['ok' => true, 'options' => $opts]);
    }

    if ($action === 'register_verify') {
        $uid = (int) getCurrentUserId();
        $cred = is_array($body['credential'] ?? null) ? $body['credential'] : [];
        $name = (string) ($body['device_name'] ?? 'Passkey');
        $result = smsPasskeyRegisterVerify($uid, $cred, $name);
        if (empty($result['ok'])) {
            smsPasskeyJson(['ok' => false, 'error' => $result['error'] ?? 'Registration failed.'], 400);
        }
        smsPasskeyJson(['ok' => true]);
    }

    if ($action === 'delete_prepare') {
        $uid = (int) getCurrentUserId();
        $id = (int) ($body['passkey_id'] ?? 0);
        smsEnsurePasskeyTable();
        $pdo = db();
        $owns = false;
        if ($pdo && $id > 0) {
            $st = $pdo->prepare('SELECT id FROM `sms2_user_passkeys` WHERE id = ? AND user_id = ? LIMIT 1');
            $st->execute([$id, $uid]);
            $owns = (bool) $st->fetch();
        }
        if (!$owns) {
            smsPasskeyJson(['ok' => false, 'error' => 'Passkey not found.'], 404);
        }

        $info = smsPasskeyRemoveMethod($uid);
        $payload = [
            'ok' => true,
            'method' => $info['method'],
            'label' => $info['label'],
            'email_masked' => $info['email_masked'],
            'otp_dev' => '',
            'message' => '',
        ];

        if ($info['method'] === 'email') {
            $issued = smsIssueOtpToEmail($uid, 'passkey_remove', null, 10, 'passkey removal');
            if (empty($issued['ok'])) {
                smsPasskeyJson(['ok' => false, 'error' => 'Could not send email code.'], 400);
            }
            $payload['message'] = !empty($issued['emailed'])
                ? 'We emailed a code to ' . $info['email_masked'] . '.'
                : 'Could not email the code' . ($issued['error'] !== '' ? ': ' . $issued['error'] : '') . '.';
            if (!empty($issued['show_local']) && !empty($issued['code'])) {
                $payload['otp_dev'] = (string) $issued['code'];
                $payload['message'] .= ' Local code shown below (SMTP not delivering).';
            }
        } elseif ($info['method'] === 'authenticator') {
            $payload['message'] = 'Enter the 6-digit code from Google Authenticator.';
        } else {
            $payload['message'] = 'Enter your account password to remove this passkey.';
        }

        smsPasskeyJson($payload);
    }

    if ($action === 'delete_send_email') {
        $uid = (int) getCurrentUserId();
        $info = smsPasskeyRemoveMethod($uid);
        if ($info['method'] !== 'email') {
            smsPasskeyJson(['ok' => false, 'error' => 'Email verification is not required for your account.'], 400);
        }
        $issued = smsIssueOtpToEmail($uid, 'passkey_remove', null, 10, 'passkey removal');
        if (empty($issued['ok'])) {
            smsPasskeyJson(['ok' => false, 'error' => 'Could not send email code.'], 400);
        }
        $out = [
            'ok' => true,
            'message' => !empty($issued['emailed'])
                ? 'Code resent to ' . $info['email_masked'] . '.'
                : 'Could not email the code' . ($issued['error'] !== '' ? ': ' . $issued['error'] : '') . '.',
            'otp_dev' => '',
        ];
        if (!empty($issued['show_local']) && !empty($issued['code'])) {
            $out['otp_dev'] = (string) $issued['code'];
        }
        smsPasskeyJson($out);
    }

    if ($action === 'delete') {
        $uid = (int) getCurrentUserId();
        $id = (int) ($body['passkey_id'] ?? 0);
        $method = (string) ($body['method'] ?? '');
        $proof = smsPasskeyVerifyRemoveProof($uid, $method, $body);
        if (empty($proof['ok'])) {
            smsPasskeyJson(['ok' => false, 'error' => $proof['error'] ?: 'Verification failed.'], 400);
        }
        if (!smsPasskeyDelete($uid, $id)) {
            smsPasskeyJson(['ok' => false, 'error' => 'Could not remove passkey.'], 400);
        }
        logActivity('update', 'Removed a passkey (verified via ' . $method . ')', 'System', $uid);
        smsPasskeyJson(['ok' => true]);
    }

    if ($action === 'login_options') {
        $username = (string) ($body['username'] ?? '');
        $opts = smsPasskeyLoginOptions($username !== '' ? $username : null);
        smsPasskeyJson(['ok' => true, 'options' => $opts]);
    }

    if ($action === 'login_verify') {
        require_once ROOT_PATH . '/includes/module-controls.php';
        require_once ROOT_PATH . '/includes/authentication.php';

        $cred = is_array($body['credential'] ?? null) ? $body['credential'] : [];
        $result = smsPasskeyLoginVerify($cred);
        if (empty($result['ok']) || empty($result['user'])) {
            smsPasskeyJson(['ok' => false, 'error' => $result['error'] ?? 'Passkey login failed.'], 400);
        }
        $user = $result['user'];
        // Passkey is phishing-resistant — complete login without password / TOTP step.
        smsCompleteLoginSession($user, (string) ($user['email'] ?? $user['username'] ?? ''));

        if (smsIsSystemInMaintenance() && !smsCanBypassSystemControls()) {
            logout();
            smsPasskeyJson([
                'ok' => false,
                'error' => 'The system is under maintenance. Please try again later.',
                'redirect' => BASE_URL . '/account/maintenance.php',
            ], 403);
        }

        $redirect = BASE_URL . '/dashboard/index.php';
        if (!empty($_SESSION['must_change_password'])) {
            $redirect = BASE_URL . '/login/change-password.php';
        }
        smsPasskeyJson(['ok' => true, 'redirect' => $redirect]);
    }

    smsPasskeyJson(['ok' => false, 'error' => 'Unknown action.'], 400);
} catch (Throwable $e) {
    error_log('SMS2 passkey API: ' . $e->getMessage());
    smsPasskeyJson(['ok' => false, 'error' => 'Passkey request failed. Please try again.'], 500);
}
