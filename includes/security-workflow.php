<?php
/**
 * SMS 2 – Security helpers (OTP + password reset requests)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/icons.php';

/**
 * Ensure security-related tables exist (safe to call repeatedly).
 */
function smsEnsureSecurityTables(): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS security_otps (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                purpose VARCHAR(40) NOT NULL,
                code_hash CHAR(64) NOT NULL,
                module_key VARCHAR(60) NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_otp_user (user_id),
                KEY idx_otp_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $e) {
        error_log('smsEnsureSecurityTables security_otps: ' . $e->getMessage());
    }

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS password_reset_requests (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                module_key VARCHAR(60) NOT NULL,
                reason VARCHAR(500) NULL,
                requested_password_hash VARCHAR(255) NULL,
                status ENUM(\'pending\',\'approved\',\'rejected\',\'cancelled\') NOT NULL DEFAULT \'pending\',
                admin_id INT UNSIGNED NULL,
                admin_note VARCHAR(500) NULL,
                temp_password_set TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                resolved_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_prr_user (user_id),
                KEY idx_prr_status (status),
                KEY idx_prr_module (module_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $e) {
        error_log('smsEnsureSecurityTables password_reset_requests: ' . $e->getMessage());
    }

    // Upgrade older installs that lack requested_password_hash
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM password_reset_requests LIKE 'requested_password_hash'")->fetch();
        if (!$cols) {
            $pdo->exec(
                'ALTER TABLE password_reset_requests
                 ADD COLUMN requested_password_hash VARCHAR(255) NULL AFTER reason'
            );
        }
    } catch (Throwable $e) {
        // ignore
    }

    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS login_throttles (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                throttle_key CHAR(64) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                locked_until DATETIME NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_login_throttle_key (throttle_key),
                KEY idx_login_throttle_ip (ip_address),
                KEY idx_login_throttle_locked (locked_until)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $e) {
        error_log('smsEnsureSecurityTables login_throttles: ' . $e->getMessage());
    }

    if (function_exists('smsEnsureAuthenticatorTable')) {
        smsEnsureAuthenticatorTable();
    } else {
        require_once __DIR__ . '/totp.php';
        smsEnsureAuthenticatorTable();
    }
}

/**
 * Create a 6-digit OTP. Returns plaintext code (show once / email later).
 */
function smsCreateOtp(int $userId, string $purpose, ?string $moduleKey = null, int $ttlMinutes = 10): ?string
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    smsEnsureSecurityTables();

    // Invalidate previous unused OTPs for same purpose
    $pdo->prepare(
        'UPDATE security_otps SET used_at = NOW()
         WHERE user_id = ? AND purpose = ? AND used_at IS NULL'
    )->execute([$userId, $purpose]);

    $code = (string) random_int(100000, 999999);
    $hash = hash('sha256', $code);

    $pdo->prepare(
        'INSERT INTO security_otps (user_id, purpose, code_hash, module_key, expires_at)
         VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
    )->execute([$userId, $purpose, $hash, $moduleKey, $ttlMinutes]);

    return $code;
}

function smsVerifyOtp(int $userId, string $purpose, string $code): bool
{
    $pdo = db();
    if (!$pdo || !preg_match('/^\d{6}$/', $code)) {
        return false;
    }

    smsEnsureSecurityTables();
    $hash = hash('sha256', $code);

    $stmt = $pdo->prepare(
        'SELECT id FROM security_otps
         WHERE user_id = ? AND purpose = ? AND code_hash = ?
           AND used_at IS NULL AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute([$userId, $purpose, $hash]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }

    $pdo->prepare('UPDATE security_otps SET used_at = NOW() WHERE id = ?')
        ->execute([(int) $row['id']]);

    return true;
}

/** Max wrong Authenticator / email codes before temporary lock. */
function smsCodeChallengeMaxAttempts(): int
{
    return max(3, min(10, (int) smsSetting('otp_max_attempts', '5')));
}

/** Lockout after too many wrong codes (default 3 minutes). */
function smsCodeChallengeLockSeconds(): int
{
    $sec = (int) smsSetting('otp_lock_seconds', '0');
    if ($sec > 0) {
        return max(60, min(900, $sec));
    }
    return max(60, min(900, (int) smsSetting('otp_lock_minutes', '3') * 60));
}

/** Minimum wait between email OTP resends. */
function smsOtpResendCooldownSeconds(): int
{
    return max(30, min(300, (int) smsSetting('otp_resend_cooldown', '60')));
}

/** Max email OTP sends in a short window before longer lock. */
function smsOtpResendMaxBurst(): int
{
    return max(2, min(8, (int) smsSetting('otp_resend_max', '3')));
}

function smsCodeGateSessionKey(int $userId, string $purpose): string
{
    return 'u' . max(0, $userId) . '|' . strtolower(trim($purpose));
}

/**
 * @return array{attempts:int,max:int,remaining:int,locked:bool,wait_seconds:int,message:string}
 */
function smsGetCodeGate(int $userId, string $purpose): array
{
    $max = smsCodeChallengeMaxAttempts();
    $key = smsCodeGateSessionKey($userId, $purpose);
    $row = $_SESSION['sms_code_gates'][$key] ?? null;
    $attempts = is_array($row) ? max(0, (int) ($row['attempts'] ?? 0)) : 0;
    $lockedUntil = is_array($row) ? (int) ($row['locked_until'] ?? 0) : 0;
    $now = time();

    if ($lockedUntil > 0 && $lockedUntil <= $now) {
        unset($_SESSION['sms_code_gates'][$key]);
        $attempts = 0;
        $lockedUntil = 0;
    }

    $locked = $lockedUntil > $now;
    $wait = $locked ? max(1, $lockedUntil - $now) : 0;
    $remaining = max(0, $max - $attempts);

    $message = '';
    if ($locked) {
        $message = 'Too many wrong codes. Please try again later ('
            . (function_exists('smsFormatDuration') ? smsFormatDuration($wait) : ($wait . 's'))
            . ').';
    }

    return [
        'attempts' => $attempts,
        'max' => $max,
        'remaining' => $remaining,
        'locked' => $locked,
        'wait_seconds' => $wait,
        'message' => $message,
    ];
}

/**
 * Record a wrong Authenticator / email code. Locks after max attempts.
 *
 * @return array{attempts:int,max:int,remaining:int,locked:bool,wait_seconds:int,message:string}
 */
function smsRegisterCodeFailure(int $userId, string $purpose): array
{
    $max = smsCodeChallengeMaxAttempts();
    $lockSec = smsCodeChallengeLockSeconds();
    $key = smsCodeGateSessionKey($userId, $purpose);
    $gate = smsGetCodeGate($userId, $purpose);
    if ($gate['locked']) {
        return $gate;
    }

    $attempts = (int) $gate['attempts'] + 1;
    $lockedUntil = 0;
    if ($attempts >= $max) {
        $lockedUntil = time() + $lockSec;
        $attempts = $max;
    }

    $_SESSION['sms_code_gates'][$key] = [
        'attempts' => $attempts,
        'locked_until' => $lockedUntil,
    ];

    return smsGetCodeGate($userId, $purpose);
}

function smsClearCodeGate(int $userId, string $purpose): void
{
    $key = smsCodeGateSessionKey($userId, $purpose);
    unset($_SESSION['sms_code_gates'][$key], $_SESSION['sms_otp_resend'][$key]);
}

/**
 * @return array{ok:bool,error:string,wait_seconds:int,locked:bool}
 */
function smsCheckOtpResendAllowed(int $userId, string $purpose): array
{
    // Wrong-code lockout also blocks resending
    $codeGate = smsGetCodeGate($userId, $purpose);
    if (!empty($codeGate['locked'])) {
        return [
            'ok' => false,
            'error' => $codeGate['message'],
            'wait_seconds' => (int) $codeGate['wait_seconds'],
            'locked' => true,
        ];
    }

    $key = smsCodeGateSessionKey($userId, $purpose);
    $row = $_SESSION['sms_otp_resend'][$key] ?? null;
    $now = time();
    $cooldown = smsOtpResendCooldownSeconds();
    $maxBurst = smsOtpResendMaxBurst();
    $lockSec = smsCodeChallengeLockSeconds();

    $count = is_array($row) ? max(0, (int) ($row['count'] ?? 0)) : 0;
    $lastAt = is_array($row) ? (int) ($row['last_at'] ?? 0) : 0;
    $lockedUntil = is_array($row) ? (int) ($row['locked_until'] ?? 0) : 0;
    $windowStart = is_array($row) ? (int) ($row['window_start'] ?? 0) : 0;

    if ($lockedUntil > 0 && $lockedUntil <= $now) {
        $lockedUntil = 0;
        $count = 0;
        $windowStart = 0;
        unset($_SESSION['sms_otp_resend'][$key]);
    }

    if ($lockedUntil > $now) {
        $wait = max(1, $lockedUntil - $now);
        return [
            'ok' => false,
            'error' => 'Too many code requests. Please try again later ('
                . (function_exists('smsFormatDuration') ? smsFormatDuration($wait) : ($wait . 's'))
                . ').',
            'wait_seconds' => $wait,
            'locked' => true,
        ];
    }

    // Reset burst window after lock duration
    if ($windowStart > 0 && ($now - $windowStart) >= $lockSec) {
        $count = 0;
        $windowStart = 0;
    }

    if ($lastAt > 0 && ($now - $lastAt) < $cooldown) {
        $wait = max(1, $cooldown - ($now - $lastAt));
        return [
            'ok' => false,
            'error' => 'Please wait '
                . (function_exists('smsFormatDuration') ? smsFormatDuration($wait) : ($wait . 's'))
                . ' before requesting another code.',
            'wait_seconds' => $wait,
            'locked' => false,
        ];
    }

    if ($count >= $maxBurst) {
        $lockedUntil = $now + $lockSec;
        $_SESSION['sms_otp_resend'][$key] = [
            'count' => $count,
            'last_at' => $lastAt,
            'locked_until' => $lockedUntil,
            'window_start' => $windowStart > 0 ? $windowStart : $now,
        ];
        return [
            'ok' => false,
            'error' => 'Too many code requests. Please try again later ('
                . (function_exists('smsFormatDuration') ? smsFormatDuration($lockSec) : ($lockSec . 's'))
                . ').',
            'wait_seconds' => $lockSec,
            'locked' => true,
        ];
    }

    return ['ok' => true, 'error' => '', 'wait_seconds' => 0, 'locked' => false];
}

function smsMarkOtpResend(int $userId, string $purpose): void
{
    $key = smsCodeGateSessionKey($userId, $purpose);
    $row = $_SESSION['sms_otp_resend'][$key] ?? [];
    $now = time();
    $windowStart = (int) ($row['window_start'] ?? 0);
    if ($windowStart <= 0 || ($now - $windowStart) >= smsCodeChallengeLockSeconds()) {
        $windowStart = $now;
        $count = 0;
    } else {
        $count = max(0, (int) ($row['count'] ?? 0));
    }

    $_SESSION['sms_otp_resend'][$key] = [
        'count' => $count + 1,
        'last_at' => $now,
        'locked_until' => (int) ($row['locked_until'] ?? 0),
        'window_start' => $windowStart,
    ];
}

/**
 * Wrong-code message with remaining attempts, or lockout text.
 */
function smsCodeFailureMessage(array $gate, string $kind = 'code'): string
{
    if (!empty($gate['locked'])) {
        return (string) ($gate['message'] !== '' ? $gate['message']
            : 'Too many wrong codes. Please try again later.');
    }
    $left = (int) ($gate['remaining'] ?? 0);
    $label = $kind === 'authenticator' ? 'Authenticator code' : ($kind === 'email' ? 'email code' : 'code');
    if ($left > 0) {
        return 'Invalid or expired ' . $label . '. ' . $left . ' attempt'
            . ($left === 1 ? '' : 's') . ' left before a temporary lock.';
    }
    return 'Invalid or expired ' . $label . '. Please try again.';
}

/**
 * Create an OTP and email it to the user’s account address.
 * On-screen code is only returned when email fails and local fallback is enabled.
 *
 * @return array{ok:bool,code:?string,emailed:bool,to:string,error:string,show_local:bool}
 */
function smsIssueOtpToEmail(
    int $userId,
    string $purpose,
    ?string $moduleKey = null,
    int $ttlMinutes = 10,
    string $purposeLabel = 'password change'
): array {
    require_once __DIR__ . '/mail.php';

    $empty = [
        'ok' => false,
        'code' => null,
        'emailed' => false,
        'to' => '',
        'error' => 'Could not generate OTP.',
        'show_local' => false,
    ];

    $resend = smsCheckOtpResendAllowed($userId, $purpose);
    if (empty($resend['ok'])) {
        return [
            'ok' => false,
            'code' => null,
            'emailed' => false,
            'to' => '',
            'error' => (string) ($resend['error'] !== '' ? $resend['error'] : 'Please try again later.'),
            'show_local' => false,
        ];
    }

    $code = smsCreateOtp($userId, $purpose, $moduleKey, $ttlMinutes);
    if (!$code) {
        return $empty;
    }

    $pdo = db();
    $user = [
        'id' => $userId,
        'full_name' => '',
        'email' => '',
    ];
    if ($pdo) {
        $stmt = $pdo->prepare('SELECT id, full_name, email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row) {
            $user = $row;
        }
    }

    $sent = smsSendOtpEmail($user, $code, $purposeLabel, $ttlMinutes);
    $showLocal = empty($sent['ok']) && smsSetting('mail_show_link_on_failure', '0') === '1';
    // Never surface OTP on public login 2FA (anti-leak)
    if (strtolower(trim($purpose)) === 'login_2fa') {
        $showLocal = false;
    }

    smsMarkOtpResend($userId, $purpose);

    return [
        'ok' => true,
        'code' => $showLocal ? $code : null,
        'emailed' => !empty($sent['ok']),
        'to' => (string) ($sent['to'] ?? ''),
        'error' => !empty($sent['ok']) ? '' : (string) ($sent['error'] ?? 'Email failed.'),
        'show_local' => $showLocal,
    ];
}

/**
 * Password strength policy (college SMS 2).
 *
 * @return array{min:int,rules:array<string,string>}
 */
function smsPasswordPolicy(): array
{
    $min = max(8, (int) smsSetting('min_password_length', '8'));
    return [
        'min' => $min,
        'rules' => [
            'length'  => "At least {$min} characters",
            'upper'   => 'At least one uppercase letter (A–Z)',
            'lower'   => 'At least one lowercase letter (a–z)',
            'number'  => 'At least one number (0–9)',
            'special' => 'At least one special character (!@#$%^&* etc.)',
        ],
    ];
}

/**
 * Validate password against policy.
 *
 * @return array{ok:bool,checks:array<string,bool>,missing:list<string>,message:string}
 */
function smsValidatePasswordStrength(string $password): array
{
    $policy = smsPasswordPolicy();
    $checks = [
        'length'  => strlen($password) >= $policy['min'],
        'upper'   => (bool) preg_match('/[A-Z]/', $password),
        'lower'   => (bool) preg_match('/[a-z]/', $password),
        'number'  => (bool) preg_match('/[0-9]/', $password),
        'special' => (bool) preg_match('/[^A-Za-z0-9]/', $password),
    ];

    $missing = [];
    foreach ($checks as $key => $ok) {
        if (!$ok) {
            $missing[] = $policy['rules'][$key];
        }
    }

    $ok = $missing === [];
    return [
        'ok'      => $ok,
        'checks'  => $checks,
        'missing' => $missing,
        'message' => $ok ? 'Password meets security requirements.' : ('Password needs: ' . implode('; ', $missing)),
    ];
}

/**
 * Render live password-requirement checklist (pair with assets/js/password-strength.js).
 */
function smsPasswordStrengthMarkup(string $inputId = 'password'): string
{
    $policy = smsPasswordPolicy();
    $items = '';
    foreach ($policy['rules'] as $key => $label) {
        $items .= '<li class="pw-rule" data-rule="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '">'
            . smsIcon('circle', ['class' => 'pw-rule-icon', 'aria-hidden' => 'true'])
            . ' <span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
            . '</li>';
    }

    return '<div class="pw-strength mt-2" data-pw-input="' . htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') . '" data-pw-min="' . (int) $policy['min'] . '">'
        . '<div class="pw-strength-head">'
        . '<span class="pw-strength-label">Password strength</span>'
        . '<span class="pw-strength-score" data-pw-score>—</span>'
        . '</div>'
        . '<div class="pw-strength-bar" aria-hidden="true"><span class="pw-strength-bar-fill"></span></div>'
        . '<div class="pw-strength-rules-title">Requirements</div>'
        . '<ul class="pw-rules list-unstyled mb-0">' . $items . '</ul>'
        . '</div>';
}

/**
 * Active users allowed for admin reset within a module (role-scoped).
 *
 * @return list<array<string,mixed>>
 */
function smsUsersForModuleReset(string $moduleKey): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    if (function_exists('smsEnsureUserPresenceColumn')) {
        smsEnsureUserPresenceColumn();
    }

    $roles = array_values(array_filter(
        smsRolesForModule($moduleKey),
        static fn(string $r): bool => !smsIsGrantedAdminRole($r)
    ));
    if ($roles === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $stmt = $pdo->prepare(
        "SELECT id, full_name, username, email, role_key, status, last_login_at, last_seen_at
         FROM users
         WHERE status IN ('active','locked')
           AND role_key IN ($placeholders)
         ORDER BY full_name ASC"
    );
    $stmt->execute($roles);
    return $stmt->fetchAll() ?: [];
}

/**
 * Primary module used when logging auth events for a role.
 */
function smsPrimaryModuleForRole(string $roleKey): string
{
    if (function_exists('smsAllowedModuleKeysForRole')) {
        $allowed = smsAllowedModuleKeysForRole($roleKey);
        $priority = [
            'user-management', 'enrollment', 'registrar', 'curriculum',
            'accreditation', 'payment', 'faculty', 'scheduling',
            'cocurricular', 'lms', 'crad', 'crad_grant', 'reports-analytics',
            'student_portal',
        ];
        foreach ($priority as $moduleKey) {
            if (in_array($moduleKey, $allowed, true)) {
                return $moduleKey;
            }
        }
    }

    $map = [
        'superadmin'   => 'user-management',
        'sms_admin'    => 'enrollment',
        'admin'        => 'user-management',
        'admission'    => 'enrollment',
        'registrar'    => 'registrar',
        'crad_officer' => 'crad',
        'research_coordinator' => 'crad',
        'department_head' => 'crad',
        'department_chair' => 'crad',
        'research_office' => 'crad',
        'research_grant' => 'crad_grant',
        'review_committee' => 'crad_grant',
        'finance'      => 'payment',
        'hr'           => 'faculty',
        'adviser'      => 'faculty',
        'panel'        => 'faculty',
        'research_director' => 'faculty',
        'grammarian'   => 'faculty',
        'it_office'    => 'lms',
        'osa'          => 'cocurricular',
        'qa'           => 'accreditation',
        'vpaa'         => 'accreditation',
        'student'      => 'student_portal',
    ];
    return $map[$roleKey] ?? 'System';
}

/**
 * Role keys that typically work in a given module (for activity log filtering).
 *
 * @return list<string>
 */
function smsRolesForModule(string $moduleKey): array
{
    $pdo = db();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare(
                'SELECT DISTINCT role_key FROM role_permissions WHERE module_key = ? AND granted = 1'
            );
            $stmt->execute([$moduleKey]);
            $roles = array_map(
                static function ($role): string {
                    $roleKey = (string) $role;
                    if (function_exists('smsNormalizeRoleKey')) {
                        return smsNormalizeRoleKey($roleKey);
                    }
                    return $roleKey === 'crad' ? 'crad_officer' : $roleKey;
                },
                $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []
            );
            if ($roles !== []) {
                return array_values(array_unique($roles));
            }
        } catch (Throwable $e) {
            // fall back to static defaults below
        }
    }

    $map = [
        'enrollment'         => ['admission', 'registrar', 'superadmin', 'sms_admin'],
        'registrar'          => ['registrar', 'superadmin', 'sms_admin'],
        'curriculum'         => ['registrar', 'superadmin', 'sms_admin'],
        'scheduling'         => ['registrar', 'superadmin', 'sms_admin'],
        'payment'            => ['finance', 'superadmin', 'sms_admin'],
        'faculty'            => ['hr', 'adviser', 'panel', 'grammarian', 'superadmin', 'sms_admin'],
        'cocurricular'       => ['osa', 'superadmin', 'sms_admin'],
        'lms'                => ['it_office', 'superadmin', 'sms_admin'],
        'crad'               => ['crad_officer', 'research_coordinator', 'department_head', 'superadmin', 'sms_admin'],
        'crad_grant'         => ['research_grant', 'review_committee', 'superadmin', 'sms_admin'],
        'accreditation'      => ['qa', 'superadmin', 'sms_admin'],
        'reports-analytics'  => ['superadmin', 'sms_admin', 'registrar', 'finance', 'hr', 'it_office', 'osa', 'qa', 'crad_officer', 'research_coordinator'],
        'user-management'    => ['superadmin', 'sms_admin'],
        'student_portal'     => ['student'],
    ];
    return $map[$moduleKey] ?? ['superadmin', 'sms_admin'];
}

/**
 * Staff/student requests Super Admin to set a chosen new password.
 */
function smsCreatePasswordResetRequest(
    int $userId,
    string $moduleKey,
    string $reason,
    string $newPassword
): array {
    $pdo = db();
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database unavailable'];
    }

    smsEnsureSecurityTables();

    $reason = trim($reason);
    if ($reason === '') {
        return ['ok' => false, 'error' => 'Reason is required.'];
    }

    $strength = smsValidatePasswordStrength($newPassword);
    if (!$strength['ok']) {
        return ['ok' => false, 'error' => $strength['message']];
    }

    $stmt = $pdo->prepare(
        'SELECT id FROM password_reset_requests
         WHERE user_id = ? AND status = \'pending\' LIMIT 1'
    );
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'You already have a pending password reset request.'];
    }

    $pdo->prepare(
        'INSERT INTO password_reset_requests (user_id, module_key, reason, requested_password_hash)
         VALUES (?, ?, ?, ?)'
    )->execute([
        $userId,
        $moduleKey,
        substr($reason, 0, 500),
        password_hash($newPassword, PASSWORD_DEFAULT),
    ]);

    logActivity(
        'password_reset_request',
        'Requested password reset via ' . $moduleKey . ': ' . substr($reason, 0, 120),
        $moduleKey,
        $userId
    );

    return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
}

/**
 * @return list<array<string,mixed>>
 */
function smsPendingPasswordRequests(?string $moduleKey = null): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }
    smsEnsureSecurityTables();

    if ($moduleKey) {
        $stmt = $pdo->prepare(
            'SELECT r.*, u.full_name, u.email, u.username, u.role_key
             FROM password_reset_requests r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.status = \'pending\' AND r.module_key = ?
             ORDER BY r.created_at ASC'
        );
        $stmt->execute([$moduleKey]);
    } else {
        $stmt = $pdo->query(
            'SELECT r.*, u.full_name, u.email, u.username, u.role_key
             FROM password_reset_requests r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.status = \'pending\'
             ORDER BY r.created_at ASC'
        );
    }

    return $stmt->fetchAll() ?: [];
}

/**
 * Admin approves request — applies the password the user already chose.
 *
 * @return array{ok:bool,error?:string}
 */
function smsApprovePasswordRequest(int $requestId, int $adminId): array
{
    $pdo = db();
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database unavailable'];
    }
    smsEnsureSecurityTables();

    $stmt = $pdo->prepare(
        'SELECT * FROM password_reset_requests WHERE id = ? AND status = \'pending\' LIMIT 1'
    );
    $stmt->execute([$requestId]);
    $req = $stmt->fetch();
    if (!$req) {
        return ['ok' => false, 'error' => 'Request not found or already resolved'];
    }

    $hash = (string) ($req['requested_password_hash'] ?? '');
    if ($hash === '') {
        return ['ok' => false, 'error' => 'Request has no chosen password. Ask the user to submit a new request.'];
    }

    $pdo->prepare(
        'UPDATE users
         SET password_hash = ?, must_change_password = 0, password_changed_at = NOW(),
             failed_login_attempts = 0, locked_until = NULL,
             status = CASE WHEN status = \'locked\' THEN \'active\' ELSE status END
         WHERE id = ?'
    )->execute([$hash, (int) $req['user_id']]);

    $pdo->prepare(
        'UPDATE password_reset_requests
         SET status = \'approved\', admin_id = ?, temp_password_set = 0, resolved_at = NOW(),
             requested_password_hash = NULL
         WHERE id = ?'
    )->execute([$adminId, $requestId]);

    logActivity(
        'password_reset',
        'Approved password reset for ' . smsUserLogLabel((int) $req['user_id']),
        (string) $req['module_key'],
        $adminId
    );

    return ['ok' => true];
}

function smsRejectPasswordRequest(int $requestId, int $adminId, string $note = ''): bool
{
    $pdo = db();
    if (!$pdo) {
        return false;
    }
    smsEnsureSecurityTables();

    $stmt = $pdo->prepare(
        'UPDATE password_reset_requests
         SET status = \'rejected\', admin_id = ?, admin_note = ?, resolved_at = NOW(),
             requested_password_hash = NULL
         WHERE id = ? AND status = \'pending\''
    );
    $stmt->execute([$adminId, $note !== '' ? substr($note, 0, 500) : null, $requestId]);

    if ($stmt->rowCount() > 0) {
        $modKey = 'user-management';
        try {
            $q = $pdo->prepare('SELECT module_key FROM password_reset_requests WHERE id = ? LIMIT 1');
            $q->execute([$requestId]);
            $row = $q->fetch();
            if ($row && !empty($row['module_key'])) {
                $modKey = (string) $row['module_key'];
            }
        } catch (Throwable $e) {
            // keep default
        }
        logActivity(
            'update',
            'Rejected password reset request' . ($note !== '' ? ': ' . substr($note, 0, 120) : ''),
            $modKey,
            $adminId
        );
        return true;
    }
    return false;
}

/**
 * Per-module activity logs for Module Security.
 * Strict: only this module’s events — never System / other modules.
 * Includes Super Admin actions done for this module (password reset, Authenticator, etc.).
 *
 * @return list<array<string,mixed>>
 */
function smsModuleActivityLogs(string $moduleKey, int $limit = 100, ?int $userId = null): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $moduleKey = strtolower(trim($moduleKey));
    if ($moduleKey === '') {
        return [];
    }

    $limit = max(1, min(500, $limit));

    $sql = "SELECT id, user_name, role_key, action, detail, module_key, ip_address,
                   created_at,
                   DATE_FORMAT(created_at, '%b %e, %Y %H:%i:%s') AS time,
                   DATE_FORMAT(created_at, '%Y-%m-%d') AS log_date
            FROM activity_logs
            WHERE module_key = ?";
    $params = [$moduleKey];
    if ($userId !== null && $userId > 0) {
        $sql .= " AND user_id = ?";
        $params[] = $userId;
    }
    $sql .= "
            ORDER BY id DESC
            LIMIT {$limit}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll() ?: [];
}

/**
 * Short label for activity log details (name + email/username).
 */
function smsUserLogLabel(int $userId): string
{
    if ($userId <= 0) {
        return 'user #' . $userId;
    }
    $pdo = db();
    if (!$pdo) {
        return 'user #' . $userId;
    }
    $stmt = $pdo->prepare('SELECT full_name, email, username FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch() ?: null;
    if (!$row) {
        return 'user #' . $userId;
    }
    $name = trim((string) ($row['full_name'] ?? ''));
    $who = (string) (($row['email'] ?? '') !== '' ? $row['email'] : ($row['username'] ?? ''));
    if ($name !== '' && $who !== '') {
        return $name . ' (' . $who . ')';
    }
    return $name !== '' ? $name : ($who !== '' ? $who : 'user #' . $userId);
}

/**
 * Display labels for modules (sidebar keys).
 */
function smsModuleLabel(string $moduleKey): string
{
    require_once __DIR__ . '/module-security-catalog.php';
    $info = smsModuleSecurityInfo($moduleKey);
    if ($info) {
        return $info['label'];
    }
    global $MODULES;
    if (isset($MODULES[$moduleKey]['label'])) {
        return (string) $MODULES[$moduleKey]['label'];
    }
    return ucwords(str_replace(['_', '-'], ' ', $moduleKey));
}
