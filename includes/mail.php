<?php
/**
 * SMS 2 – Mail helper (PHPMailer SMTP + password reset / OTP emails)
 */
require_once __DIR__ . '/security.php';

/**
 * @return array{ok:bool,error:string}
 */
function smsSendMail(string $to, string $subject, string $htmlBody, string $textBody = ''): array
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid recipient email.'];
    }

    $fromEmail = trim(smsSetting('mail_from_email', 'noreply@bestlink.edu.ph'));
    $fromName = trim(smsSetting('mail_from_name', APP_SHORT_NAME));
    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $fromEmail = 'noreply@bestlink.edu.ph';
    }
    if ($fromName === '') {
        $fromName = APP_SHORT_NAME;
    }

    if ($textBody === '') {
        $textBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody)), ENT_QUOTES | ENT_HTML5));
    }

    $host = trim(smsSetting('smtp_host', ''));
    if ($host === '') {
        return [
            'ok' => false,
            'error' => 'Email is not configured yet. Open System Settings → Notifications / Email and set SMTP (for Gmail: smtp.gmail.com, port 587, TLS, your Gmail + App Password).',
        ];
    }

    return smsSendMailSmtp($to, $subject, $htmlBody, $textBody, $fromEmail, $fromName);
}

function smsMailEncodeAddress(string $name, string $email): string
{
    $name = trim(str_replace(["\r", "\n"], '', $name));
    $email = trim(str_replace(["\r", "\n"], '', $email));
    if ($name === '') {
        return $email;
    }
    return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
}

/**
 * Resolve SMTP password: env → local.php constant → storage/keys file → encrypted setting.
 */
function smsSmtpPassword(): string
{
    // HostForge / container env (preferred for production — survives redeploys)
    $envKeys = ['SMS2_SMTP_PASSWORD', 'SMTP_PASSWORD'];
    foreach ($envKeys as $envKey) {
        $raw = null;
        if (function_exists('sms2_env')) {
            $raw = sms2_env($envKey);
        }
        if ($raw === null || $raw === '') {
            $g = getenv($envKey);
            if ($g !== false && $g !== '') {
                $raw = $g;
            }
        }
        if (($raw === null || $raw === '') && isset($_ENV[$envKey]) && is_scalar($_ENV[$envKey])) {
            $raw = (string) $_ENV[$envKey];
        }
        if (($raw === null || $raw === '') && isset($_SERVER[$envKey]) && is_scalar($_SERVER[$envKey])) {
            $raw = (string) $_SERVER[$envKey];
        }
        if (is_string($raw) && $raw !== '') {
            $fromEnv = preg_replace('/\s+/', '', trim($raw)) ?? trim($raw);
            if ($fromEnv !== '') {
                return $fromEnv;
            }
        }
    }

    if (defined('SMS2_SMTP_PASSWORD')) {
        $local = trim((string) constant('SMS2_SMTP_PASSWORD'));
        $local = preg_replace('/\s+/', '', $local) ?? $local;
        if ($local !== '') {
            return $local;
        }
    }

    $file = ROOT_PATH . '/storage/keys/smtp_app_password';
    if (is_readable($file)) {
        $fromFile = trim((string) file_get_contents($file));
        // Strip spaces (Gmail App Passwords are often copied with spaces)
        $fromFile = preg_replace('/\s+/', '', $fromFile) ?? $fromFile;
        if ($fromFile !== '') {
            return $fromFile;
        }
    }

    $fromDb = (string) smsSetting('smtp_password', '');
    if ($fromDb !== '') {
        return preg_replace('/\s+/', '', $fromDb) ?? $fromDb;
    }

    return '';
}

/**
 * Send via PHPMailer SMTP using System Settings credentials.
 *
 * @return array{ok:bool,error:string}
 */
function smsSendMailSmtp(
    string $to,
    string $subject,
    string $htmlBody,
    string $textBody,
    string $fromEmail,
    string $fromName
): array {
    $host = trim(smsSetting('smtp_host', ''));
    $port = (int) smsSetting('smtp_port', '587');
    $enc = strtolower(trim(smsSetting('smtp_encryption', 'tls')));
    $user = trim(smsSetting('smtp_username', ''));
    if (defined('SMS2_SMTP_USERNAME')) {
        $overrideUser = trim((string) constant('SMS2_SMTP_USERNAME'));
        if ($overrideUser !== '') {
            $user = $overrideUser;
        }
    }
    if ($user === '') {
        foreach (['SMS2_SMTP_USERNAME', 'SMTP_USERNAME'] as $envKey) {
            $raw = function_exists('sms2_env') ? sms2_env($envKey) : null;
            if (($raw === null || $raw === '') && isset($_ENV[$envKey]) && is_scalar($_ENV[$envKey])) {
                $raw = (string) $_ENV[$envKey];
            }
            if (($raw === null || $raw === '') && isset($_SERVER[$envKey]) && is_scalar($_SERVER[$envKey])) {
                $raw = (string) $_SERVER[$envKey];
            }
            if (is_string($raw) && trim($raw) !== '') {
                $user = trim($raw);
                break;
            }
        }
    }
    $pass = smsSmtpPassword();

    if ($host === '') {
        return [
            'ok' => false,
            'error' => 'Email is not configured yet. Open System Settings → Notifications / Email and set SMTP.',
        ];
    }

    if ($user !== '' && $pass === '') {
        return [
            'ok' => false,
            'error' => 'SMTP password missing. Set HostForge env SMS2_SMTP_PASSWORD (then restart/redeploy), or re-save App Password in System Settings, or put it in storage/keys/smtp_app_password on the server.',
        ];
    }

    // Surface which source is empty to speed up HostForge debugging (no secret leaked).
    if ($user === '' && $pass !== '') {
        return [
            'ok' => false,
            'error' => 'SMTP username is missing. Set smtp_username in System Settings or HostForge env SMS2_SMTP_USERNAME.',
        ];
    }

    if ($port <= 0) {
        $port = $enc === 'ssl' ? 465 : 587;
    }

    // Gmail (and many providers) require From to match the authenticated mailbox.
    if ($user !== '' && filter_var($user, FILTER_VALIDATE_EMAIL)) {
        $fromDomain = strtolower((string) substr(strrchr($fromEmail, '@') ?: '', 1));
        $userDomain = strtolower((string) substr(strrchr($user, '@') ?: '', 1));
        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || $fromDomain !== $userDomain) {
            $fromEmail = $user;
        }
    }

    $phpmailerRoot = ROOT_PATH . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR . 'src';
    $required = [
        $phpmailerRoot . DIRECTORY_SEPARATOR . 'Exception.php',
        $phpmailerRoot . DIRECTORY_SEPARATOR . 'PHPMailer.php',
        $phpmailerRoot . DIRECTORY_SEPARATOR . 'SMTP.php',
    ];
    foreach ($required as $file) {
        if (!is_file($file)) {
            $msg = 'PHPMailer is missing. Expected files under PHPMailer/src/.';
            error_log('SMS2 ' . $msg);
            return ['ok' => false, 'error' => $msg];
        }
    }

    require_once $required[0];
    require_once $required[1];
    require_once $required[2];

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = ($user !== '');
        if ($user !== '') {
            $mail->Username = $user;
            $mail->Password = $pass;
        }

        if ($enc === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($enc === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $mail->Timeout = 20;
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);
        $mail->XMailer = 'SMS2 / PHPMailer';

        $mail->send();

        return ['ok' => true, 'error' => ''];
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        $detail = trim($e->getMessage());
        if (isset($mail) && is_object($mail) && !empty($mail->ErrorInfo)) {
            $detail = trim((string) $mail->ErrorInfo);
        }
        $msg = 'SMTP send failed: ' . ($detail !== '' ? $detail : 'Unknown PHPMailer error.');
        error_log('SMS2 ' . $msg);
        return ['ok' => false, 'error' => $msg];
    } catch (Throwable $e) {
        $msg = 'SMTP send failed: ' . $e->getMessage();
        error_log('SMS2 ' . $msg);
        return ['ok' => false, 'error' => $msg];
    }
}

/**
 * Shared BCP-branded HTML email shell (matches auth / login UI colors).
 */
function smsMailWrapHtml(string $title, string $innerHtml): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $inst = htmlspecialchars(INSTITUTION, ENT_QUOTES, 'UTF-8');
    $short = htmlspecialchars(APP_SHORT_NAME, ENT_QUOTES, 'UTF-8');

    return '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . $safeTitle . '</title></head>'
        . '<body style="margin:0;padding:0;background:#e8eef7;font-family:\'Segoe UI\',Tahoma,Geneva,Verdana,sans-serif;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#e8eef7;padding:28px 12px;">'
        . '<tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 18px 40px rgba(5,22,55,0.14);border:1px solid #dbe4f0;">'
        // Header — same blue gradient as login / forgot password
        . '<tr><td style="background:linear-gradient(145deg,#051637 0%,#0b2a6b 42%,#1a6fc4 100%);padding:22px 24px;text-align:center;">'
        . '<div style="display:inline-block;background:rgba(255,255,255,0.14);border:1px solid rgba(255,255,255,0.28);border-radius:999px;padding:6px 14px;margin-bottom:10px;">'
        . '<span style="color:#fff;font-size:12px;font-weight:800;letter-spacing:0.06em;">' . $short . '</span>'
        . '</div>'
        . '<div style="color:#fff;font-size:20px;font-weight:800;letter-spacing:-0.02em;line-height:1.25;">' . $safeTitle . '</div>'
        . '<div style="color:rgba(226,232,240,0.92);font-size:12px;font-weight:600;margin-top:6px;">' . $inst . '</div>'
        . '</td></tr>'
        // Body
        . '<tr><td style="padding:26px 24px 8px;color:#0f172a;font-size:15px;line-height:1.55;font-weight:500;">'
        . $innerHtml
        . '</td></tr>'
        // Footer
        . '<tr><td style="padding:8px 24px 22px;text-align:center;">'
        . '<div style="height:1px;background:#e2e8f0;margin:0 0 16px;"></div>'
        . '<div style="color:#64748b;font-size:12px;font-weight:600;line-height:1.45;">'
        . 'This message was sent by ' . $inst . ' · ' . $short
        . '<br>Do not reply to this email.</div>'
        . '</td></tr>'
        . '</table>'
        . '</td></tr></table>'
        . '</body></html>';
}

/**
 * Send password-reset link to the account email (or an explicit recipient).
 *
 * @param array<string,mixed> $user
 * @return array{ok:bool,error:string,to:string}
 */
function smsSendPasswordResetEmail(array $user, string $resetUrl, ?string $toOverride = null): array
{
    $to = trim((string) ($toOverride ?? ''));
    if ($to === '') {
        $to = trim((string) ($user['email'] ?? ''));
    }
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'This account has no valid email on file.', 'to' => ''];
    }

    $name = trim((string) ($user['full_name'] ?? 'User'));
    if ($name === '') {
        $name = 'User';
    }

    $subject = APP_SHORT_NAME . ' password reset';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
    $app = htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8');

    $inner = '<p style="margin:0 0 12px;">Hi <strong>' . $safeName . '</strong>,</p>'
        . '<p style="margin:0 0 18px;color:#334155;">We received a request to reset your password for <strong>' . $app . '</strong>.</p>'
        . '<p style="margin:0 0 22px;text-align:center;">'
        . '<a href="' . $safeUrl . '" style="display:inline-block;padding:13px 22px;background:#5350d6;color:#ffffff;text-decoration:none;border-radius:999px;font-weight:800;font-size:14px;box-shadow:0 8px 18px rgba(83,80,214,0.28);">Reset your password</a>'
        . '</p>'
        . '<p style="margin:0 0 8px;color:#64748b;font-size:13px;">Or copy this link:</p>'
        . '<p style="margin:0 0 16px;word-break:break-all;color:#4338ca;font-size:12px;font-weight:600;">' . $safeUrl . '</p>'
        . '<p style="margin:0;color:#64748b;font-size:13px;">This link expires in <strong>1 hour</strong>. If you did not request this, you can ignore this email.</p>';

    $html = smsMailWrapHtml('Password reset', $inner);

    $text = "Hi {$name},\n\n"
        . "We received a request to reset your password for " . APP_NAME . ".\n\n"
        . "Open this link to reset your password (expires in 1 hour):\n{$resetUrl}\n\n"
        . "If you did not request this, ignore this email.\n\n"
        . INSTITUTION . " · " . APP_NAME . "\n";

    $result = smsSendMail($to, $subject, $html, $text);
    $result['to'] = $to;
    return $result;
}

/**
 * Email a one-time password (OTP) to the user's account email.
 *
 * @param array<string,mixed> $user
 * @return array{ok:bool,error:string,to:string}
 */
function smsSendOtpEmail(array $user, string $code, string $purposeLabel = 'password change', int $ttlMinutes = 10): array
{
    $to = trim((string) ($user['email'] ?? ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'This account has no valid email on file.', 'to' => ''];
    }

    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) !== 6) {
        return ['ok' => false, 'error' => 'Invalid OTP code.', 'to' => $to];
    }

    $name = trim((string) ($user['full_name'] ?? 'User'));
    if ($name === '') {
        $name = 'User';
    }

    $ttlMinutes = max(1, $ttlMinutes);
    $subject = APP_SHORT_NAME . ' verification code';
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $safePurpose = htmlspecialchars($purposeLabel, ENT_QUOTES, 'UTF-8');
    $app = htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8');

    // Spaced digits for readability in email clients
    $spacedCode = implode(' ', str_split($safeCode));

    $inner = '<p style="margin:0 0 10px;">Hi <strong>' . $safeName . '</strong>,</p>'
        . '<p style="margin:0 0 20px;color:#334155;">Your one-time verification code for <strong>' . $safePurpose . '</strong> on <strong>' . $app . '</strong> is:</p>'
        . '<div style="margin:0 0 20px;text-align:center;">'
        . '<div style="display:inline-block;min-width:220px;padding:18px 22px;border-radius:14px;border:1px solid #c7d2fe;background:linear-gradient(180deg,#eef2ff 0%,#e0e7ff 100%);box-shadow:0 10px 24px rgba(83,80,214,0.12);">'
        . '<div style="font-size:11px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#4338ca;margin-bottom:8px;">Verification code</div>'
        . '<div style="font-size:32px;font-weight:800;letter-spacing:0.28em;color:#0f172a;font-family:Consolas,\'Courier New\',monospace;">' . $spacedCode . '</div>'
        . '</div></div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;background:#fff7ed;border:1px solid #fdba74;border-radius:12px;">'
        . '<tr><td style="padding:12px 14px;color:#9a3412;font-size:13px;font-weight:700;text-align:center;">'
        . 'Expires in ' . (int) $ttlMinutes . ' minute' . ((int) $ttlMinutes === 1 ? '' : 's') . ' · Do not share this code'
        . '</td></tr></table>'
        . '<p style="margin:0;color:#64748b;font-size:13px;">If you did not request this, you can ignore this email. Someone may have typed your address by mistake.</p>';

    $html = smsMailWrapHtml('Verification code', $inner);

    $text = "Hi {$name},\n\n"
        . "Your one-time verification code for {$purposeLabel} on " . APP_NAME . " is:\n\n"
        . "{$code}\n\n"
        . "This code expires in {$ttlMinutes} minutes. Do not share it with anyone.\n\n"
        . "If you did not request this, ignore this email.\n\n"
        . INSTITUTION . " · " . APP_NAME . "\n";

    $result = smsSendMail($to, $subject, $html, $text);
    $result['to'] = $to;
    return $result;
}
