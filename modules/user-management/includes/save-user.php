<?php
/**
 * SMS 2 – Save / update / archive / purge user (admin API)
 * Prefer set_status (archive/restore). Hard delete only for already-archived accounts.
 */
declare(strict_types=1);

ini_set('display_errors', '0');
if (ob_get_level() === 0) {
    ob_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/security-workflow.php';
require_once __DIR__ . '/user-account-schema.php';

/**
 * JSON body only. Drops any notice/warning text so the browser can read the payload.
 */
function umEmit(array $payload, int $status = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('CDN-Cache-Control: no-store');
        header('Cloudflare-CDN-Cache-Control: no-store');
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        $json = '{"ok":false,"error":"The account could not be saved because the response could not be prepared."}';
    }
    echo $json;
    exit;
}

/**
 * A post-insert error must not turn a durable account into a failed save response.
 */
function umCreatedAccountPayloadIfPersisted(
    PDO $pdo,
    bool $insertCompleted,
    int $userId,
    string $username,
    string $email,
    Throwable $cause
): ?array {
    if (!$insertCompleted) {
        return null;
    }

    try {
        $row = null;
        if ($userId > 0) {
            $stmt = $pdo->prepare(
                'SELECT id, full_name, username, email, role_key AS role, status, notes
                 FROM `sms2_users` WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!$row && $username !== '' && $email !== '') {
            $stmt = $pdo->prepare(
                'SELECT id, full_name, username, email, role_key AS role, status, notes
                 FROM `sms2_users` WHERE username = ? AND email = ? LIMIT 1'
            );
            $stmt->execute([$username, $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (Throwable $verificationError) {
        error_log('save-user persisted account verification failed: ' . $verificationError->getMessage());
        return null;
    }

    if (!$row || (int) ($row['id'] ?? 0) <= 0) {
        return null;
    }

    error_log('save-user follow-up failed after account insert: ' . $cause->getMessage());
    $warning = $cause instanceof InvalidArgumentException
        ? $cause->getMessage()
        : 'Some follow-up setup could not be completed. Check the server logs.';
    return [
        'ok' => true,
        'created' => true,
        'id' => (int) $row['id'],
        'message' => 'Account successfully created.',
        'warning' => $warning,
        'user' => $row,
    ];
}

$pdo = null;
$newUserId = 0;
$userInsertCompleted = false;
$username = '';
$email = '';

register_shutdown_function(static function () use (&$pdo, &$newUserId, &$userInsertCompleted, &$username, &$email): void {
    $err = error_get_last();
    if (!$err || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    error_log('save-user fatal: ' . ($err['message'] ?? ''));
    if ($pdo instanceof PDO) {
        $createdPayload = umCreatedAccountPayloadIfPersisted(
            $pdo,
            $userInsertCompleted,
            $newUserId,
            $username,
            $email,
            new Error('Fatal PHP error occurred after account insert.')
        );
        if ($createdPayload !== null) {
            umEmit($createdPayload);
        }
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
    }
    echo json_encode([
        'ok' => false,
        'error' => 'Add User stopped because of a server error. Nothing was saved. Refresh the page and try again.',
    ]);
});

if (!isAuthenticated() || !userCanAccessModule('user-management')) {
    umEmit(['ok' => false, 'error' => 'You do not have permission to add or update users.'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    umEmit(['ok' => false, 'error' => 'Add User only accepts a submitted form. Refresh the page and try again.'], 405);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    // Fallback to form POST
    $data = $_POST;
}

if (ob_get_level() > 0) {
    ob_clean();
}
requireCsrf(isset($data['csrf_token']) ? (string) $data['csrf_token'] : null);

$pdo = db();
if (!$pdo) {
    umEmit(['ok' => false, 'error' => 'The database is unavailable, so the account was not saved.'], 500);
}

$action = (string) ($data['action'] ?? 'save');
$validRoles = ['superadmin', 'sms_admin', 'admission', 'registrar', 'finance', 'hr', 'adviser', 'research_director', 'grammarian', 'panel', 'it_office', 'osa', 'qa', 'crad', 'crad_officer', 'research_coordinator', 'department_head', 'department_chair', 'research_office', 'vpaa', 'review_committee', 'student'];
$validStatus = ['active', 'inactive', 'locked', 'suspended'];

/**
 * Password posted from User Accounts (new_password preferred; password kept for compatibility).
 */
function umPostedPassword(array $data): string
{
    $password = (string) ($data['new_password'] ?? '');
    if ($password === '') {
        $password = (string) ($data['password'] ?? '');
    }
    return $password;
}

/**
 * Optional confirmation field. Empty confirm is allowed only when password is also empty.
 */
function umRequirePasswordConfirm(string $password, array $data): void
{
    if ($password === '') {
        return;
    }
    $confirm = (string) ($data['new_password_confirm'] ?? $data['password_confirm'] ?? '');
    if ($confirm === '') {
        throw new InvalidArgumentException('Please confirm the new password.');
    }
    if (!hash_equals($password, $confirm)) {
        throw new InvalidArgumentException('New password and confirmation do not match.');
    }
}

function umApplyUserPassword(int $userId, string $password): void
{
    $strength = smsValidatePasswordStrength($password);
    if (!$strength['ok']) {
        throw new InvalidArgumentException($strength['message']);
    }
    if (!smsSetUserPassword($userId, $password, false)) {
        throw new InvalidArgumentException('The password could not be saved. Try a different password.');
    }
}

/**
 * @return array<string, string>
 */
function umRoleLabels(): array
{
    return [
        'superadmin' => 'Super Admin',
        'sms_admin' => 'Admin',
        'admission' => 'Admission',
        'registrar' => 'Registrar',
        'finance' => 'Finance',
        'hr' => 'Dean',
        'adviser' => 'Adviser',
        'research_director' => 'Research Director',
        'grammarian' => 'Grammarian',
        'panel' => 'Panel Member',
        'it_office' => 'IT Office',
        'osa' => 'OSA',
        'qa' => 'QA Office',
        'crad_officer' => 'CRAD Officer',
        'research_coordinator' => 'Research Coordinator',
        'department_head' => 'Department Head',
        'department_chair' => 'Department Chair',
        'research_office' => 'Research Office',
        'vpaa' => 'VPAA',
        'review_committee' => 'Review Committee',
        'student' => 'Student',
    ];
}

/**
 * sms2_users.role_key is a foreign key. Seed the selected role so a
 * legitimate Add User is not rejected when that role row is missing.
 */
function umEnsureRoleRow(PDO $pdo, string $role): void
{
    try {
        $check = $pdo->prepare('SELECT role_key FROM `sms2_roles` WHERE role_key = ? LIMIT 1');
        $check->execute([$role]);
        if ($check->fetch()) {
            return;
        }
        $labels = umRoleLabels();
        $label = $labels[$role] ?? $role;
        $pdo->prepare(
            'INSERT IGNORE INTO `sms2_roles` (role_key, label, description, is_system) VALUES (?, ?, ?, 1)'
        )->execute([$role, $label, $label]);
    } catch (Throwable $e) {
        // A missing role row still fails the user insert with a clear message.
        error_log('save-user role ensure skipped: ' . $e->getMessage());
    }
}

function umAssertUniqueAccount(PDO $pdo, string $username, string $email, int $ignoreId): void
{
    $byName = $pdo->prepare('SELECT id FROM `sms2_users` WHERE LOWER(username) = ? AND id <> ? LIMIT 1');
    $byName->execute([$username, $ignoreId]);
    if ($byName->fetch()) {
        throw new InvalidArgumentException('That username is already in use. Choose a different username.');
    }

    $byEmail = $pdo->prepare('SELECT id FROM `sms2_users` WHERE LOWER(email) = ? AND id <> ? LIMIT 1');
    $byEmail->execute([$email, $ignoreId]);
    if ($byEmail->fetch()) {
        throw new InvalidArgumentException('That email address is already in use. Choose a different email.');
    }
}


/**
 * Ensure the optional users.id link column exists on the adviser assignment
 * table (idempotent). Failures surface at insert time, never silently here.
 */
function rcEnsureAdviserUserColumn(PDO $crad): void
{
    try {
        $col = $crad->query("SHOW COLUMNS FROM `crad_research_adviser_assignments` LIKE 'adviser_user_id'")->fetch();
        if (!$col) {
            $crad->exec("ALTER TABLE `crad_research_adviser_assignments` ADD COLUMN adviser_user_id INT UNSIGNED DEFAULT NULL AFTER adviser_email, ADD KEY idx_raa_user (adviser_user_id)");
        }
    } catch (Throwable $e) {
        error_log('Adviser account sync column check skipped: ' . $e->getMessage());
    }
}

/**
 * Ensure research_coordinator_assignments.group_number is nullable so a
 * coordinator account can be recorded before a research group is assigned
 * (idempotent). MySQL UNIQUE indexes allow multiple NULLs.
 */
function rcEnsureCoordinatorGroupNullable(PDO $crad): void
{
    try {
        $col = $crad->query("SHOW COLUMNS FROM `crad_research_coordinator_assignments` LIKE 'group_number'")->fetch();
        if ($col && strtoupper((string) ($col['Null'] ?? 'YES')) === 'NO') {
            $crad->exec("ALTER TABLE `crad_research_coordinator_assignments` MODIFY group_number VARCHAR(40) DEFAULT NULL");
        }
    } catch (Throwable $e) {
        error_log('Coordinator account sync column check skipped: ' . $e->getMessage());
    }
}

/**
 * After a Research Adviser or Research Coordinator account is saved in users,
 * make sure a corresponding account record exists in the matching assignment
 * table (idempotent; never overwrites an existing group assignment). Uses the
 * new/existing users.id as the reference where the schema has a user column.
 */
function rcSyncAssignmentFromUserAccount(int $userId, string $role, string $fullName, string $email, string $status, bool $accountCommitted = false): void
{
    if ($userId <= 0 || !in_array($role, ['adviser', 'research_coordinator'], true)) {
        return;
    }

    $who = $role === 'adviser' ? 'adviser' : 'research coordinator';
    $fail = static function (string $reason) use ($accountCommitted, $who): void {
        if ($accountCommitted) {
            throw new InvalidArgumentException('The user account was saved, but the ' . $who . ' assignment could not be updated. ' . $reason);
        }
        throw new InvalidArgumentException('The ' . $who . ' account was not created. ' . $reason);
    };

    try {
        require_once ROOT_PATH . '/modules/crad/config/config.php';
        $crad = getCradDatabaseConnection();
    } catch (Throwable $e) {
        error_log('Assignment sync connection failed: ' . $e->getMessage());
        $fail('The research assignment database is unavailable. Try again when it is reachable.');
    }

    try {
        if ($role === 'adviser') {
            rcEnsureAdviserUserColumn($crad);
            umEnsureTableIdAutoIncrement($crad, 'crad_research_adviser_assignments');

            $stmt = $crad->prepare(
                "SELECT id, adviser_user_id, research_group_id, proposal_id, group_number
                 FROM `crad_research_adviser_assignments`
                 WHERE adviser_user_id = :uid
                    OR LOWER(TRIM(adviser_email)) = LOWER(TRIM(:email))
                 ORDER BY id ASC
                 LIMIT 1"
            );
            $stmt->execute([':uid' => $userId, ':email' => $email]);
            $existing = $stmt->fetch();

            if ($existing) {
                $crad->prepare(
                    "UPDATE `crad_research_adviser_assignments`
                        SET adviser_user_id = :uid,
                            adviser_name = :name,
                            adviser_email = :email,
                            availability_status = CASE
                                WHEN assignment_status = 'Assigned' THEN availability_status
                                ELSE 'Available'
                            END,
                            updated_at = NOW()
                      WHERE id = :id"
                )->execute([
                    ':uid' => $userId,
                    ':name' => $fullName,
                    ':email' => $email,
                    ':id' => (int) $existing['id'],
                ]);
                return;
            }

            $crad->prepare(
                "INSERT INTO `crad_research_adviser_assignments`
                    (adviser_user_id, adviser_name, adviser_email, expertise,
                     availability_status, assignment_status, notes, assigned_by, created_at, updated_at)
                 VALUES (?, ?, ?, 'General Research Methods', 'Available', 'Pending',
                         'Synced from adviser user account.', ?, NOW(), NOW())"
            )->execute([
                $userId,
                $fullName,
                $email,
                (int) ($_SESSION['user_id'] ?? 0) ?: null,
            ]);
            return;
        }

        rcEnsureCoordinatorGroupNullable($crad);
        umEnsureTableIdAutoIncrement($crad, 'crad_research_coordinator_assignments');

        $stmt = $crad->prepare(
            "SELECT id, group_number FROM `crad_research_coordinator_assignments`
             WHERE coordinator_user_id = :uid
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            if (trim((string) ($existing['group_number'] ?? '')) === '') {
                $crad->prepare("UPDATE `crad_research_coordinator_assignments` SET coordinator_name = :name, coordinator_email = :email, updated_at = NOW() WHERE id = :id")
                    ->execute([':name' => $fullName, ':email' => $email, ':id' => (int) $existing['id']]);
            }
            return;
        }

        $crad->prepare(
            "INSERT INTO `crad_research_coordinator_assignments`
                (coordinator_user_id, coordinator_name, coordinator_email,
                 status, assigned_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
        )->execute([
            $userId,
            $fullName,
            $email,
            strtolower($status) === 'active' ? 'Active' : 'Inactive',
            (int) ($_SESSION['user_id'] ?? 0) ?: null,
        ]);
    } catch (InvalidArgumentException $e) {
        throw $e;
    } catch (Throwable $e) {
        error_log('Assignment sync failed: ' . $e->getMessage());
        $detail = function_exists('umRedactDbMessage') ? umRedactDbMessage($e->getMessage()) : '';
        $suffix = $detail !== '' ? ' Database detail: ' . $detail : '';
        if ($e instanceof PDOException && (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), '1062'))) {
            $fail('An assignment with this name or email already exists.' . $suffix);
        }
        $fail(($accountCommitted
            ? 'Refresh the page to see the saved account.'
            : 'Its assignment record could not be saved, so nothing was changed.') . $suffix);
    }
}

try {
    if ($action === 'set_status') {
        $id = (int) ($data['user_id'] ?? 0);
        $status = trim((string) ($data['status'] ?? ''));
        if ($id <= 0 || !in_array($status, $validStatus, true)) {
            throw new InvalidArgumentException('Invalid user or status');
        }
        if ($id === getCurrentUserId() && $status !== 'active') {
            throw new InvalidArgumentException('You cannot archive your own account');
        }
        $stmt = $pdo->prepare('UPDATE `sms2_users` SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        if ($stmt->rowCount() < 1) {
            // still ok if status unchanged
            $check = $pdo->prepare('SELECT id FROM `sms2_users` WHERE id = ? LIMIT 1');
            $check->execute([$id]);
            if (!$check->fetch()) {
                throw new InvalidArgumentException('User not found');
            }
        }
        $label = $status === 'active' ? 'Restored' : 'Archived';
        logActivity('update', $label . ' user #' . $id . ' (status=' . $status . ')', 'user-management');
        umEmit(['ok' => true, 'status' => $status]);
    }

    if ($action === 'delete') {
        $id = (int) ($data['user_id'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid user');
        }
        if ($id === getCurrentUserId()) {
            throw new InvalidArgumentException('You cannot delete your own account');
        }
        // Permanent delete only from archive (inactive / locked / suspended)
        $stmt = $pdo->prepare('SELECT status FROM `sms2_users` WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('User not found');
        }
        $cur = (string) ($row['status'] ?? '');
        if (!in_array($cur, ['inactive', 'locked', 'suspended'], true)) {
            throw new InvalidArgumentException('Archive the user first. Permanent delete is only allowed for archived accounts.');
        }
        $pdo->prepare('DELETE FROM `sms2_users` WHERE id = ?')->execute([$id]);
        logActivity('delete', 'Permanently deleted archived user #' . $id, 'user-management');
        umEmit(['ok' => true]);
    }

    if ($action === 'reset_password') {
        $id = (int) ($data['user_id'] ?? 0);
        $temp = (string) ($data['password'] ?? '');
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid user');
        }
        $strength = smsValidatePasswordStrength($temp);
        if (!$strength['ok']) {
            throw new InvalidArgumentException($strength['message']);
        }
        if (!smsSetUserPassword($id, $temp, true)) {
            throw new InvalidArgumentException('The password could not be reset. Try a different password.');
        }
        logActivity('password_reset', 'Admin reset password for user #' . $id, 'user-management');
        umEmit(['ok' => true]);
    }

    // save (create / update)
    $id = (int) ($data['user_id'] ?? 0);
    $fullName = trim((string) ($data['full_name'] ?? ''));
    $username = strtolower(trim((string) ($data['username'] ?? '')));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $role = smsNormalizeRoleKey(trim((string) ($data['role'] ?? '')));
    $status = trim((string) ($data['status'] ?? 'active'));
    $password = umPostedPassword($data);
    $notes = trim((string) ($data['notes'] ?? ''));
    $studentId = null;
    umRequirePasswordConfirm($password, $data);

    $missing = [];
    if ($fullName === '') {
        $missing[] = 'full name';
    }
    if ($username === '') {
        $missing[] = 'username';
    }
    if ($email === '') {
        $missing[] = 'email';
    }
    if ($missing !== []) {
        throw new InvalidArgumentException('Enter ' . implode(', ', $missing) . '.');
    }
    if (!in_array($role, $validRoles, true)) {
        throw new InvalidArgumentException('Select a valid role.');
    }
    if (strlen($fullName) > 150) {
        throw new InvalidArgumentException('Full name must be 150 characters or fewer.');
    }
    if (strlen($username) > 80) {
        throw new InvalidArgumentException('Username must be 80 characters or fewer.');
    }
    if ($id <= 0 && preg_match('/^[a-z0-9]+$/', $username) !== 1) {
        throw new InvalidArgumentException('Username can contain letters and numbers only (A-Z, 0-9).');
    }
    if (strlen($email) > 190) {
        throw new InvalidArgumentException('Email must be 190 characters or fewer.');
    }
    if (!in_array($status, $validStatus, true)) {
        $status = 'active';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Enter a valid email address.');
    }

    umEnsureRoleRow($pdo, $role);
    umAssertUniqueAccount($pdo, $username, $email, $id);

    if ($role === 'student' && preg_match('/^s\d+$/i', $username)) {
        $studentId = strtoupper($username);
    }

    $userColumns = umSms2UsersColumns($pdo);
    if ($userColumns !== null) {
        $userColumns = umRepairSms2UsersForAccountWrite($pdo, $userColumns);
    }
    $accountFields = [
        'username' => $username,
        'email' => $email,
        'full_name' => $fullName,
        'role_key' => $role,
        'status' => $status,
        'student_id' => $studentId,
        'notes' => $notes,
    ];

    if ($id > 0) {
        $passwordUpdated = false;
        if ($password !== '' && $status === 'locked') {
            $status = 'active';
        }
        $accountFields['status'] = $status;
        if ($userColumns === null) {
            $updateSql = 'UPDATE `sms2_users` SET full_name=?, username=?, email=?, role_key=?, status=?, notes=?';
            $updateParams = [$fullName, $username, $email, $role, $status, $notes !== '' ? $notes : null];
            if ($studentId !== null) {
                $updateSql .= ', student_id=?';
                $updateParams[] = $studentId;
            }
            $updateSql .= ' WHERE id=?';
            $updateParams[] = $id;
            $pdo->prepare($updateSql)->execute($updateParams);
        } else {
            $update = umUserUpdateStatement($userColumns, $accountFields, $id);
            $pdo->prepare($update['sql'])->execute($update['params']);
        }
        if ($password !== '') {
            umApplyUserPassword($id, $password);
            $passwordUpdated = true;
        }
        rcSyncAssignmentFromUserAccount($id, $role, $fullName, $email, $status, true);
        if ($role === 'student') {
            require_once ROOT_PATH . '/modules/student-portal/includes/student-profile.php';
            studentPortalEnsureProfileForUser($id, (string) ($studentId ?? ''), $role);
        }
        logActivity(
            $passwordUpdated ? 'password_reset' : 'update',
            ($passwordUpdated ? 'Updated user and password for ' : 'Updated user ') . $username,
            'user-management'
        );
        umEmit([
            'ok' => true,
            'updated' => true,
            'password_updated' => $passwordUpdated,
            'message' => $passwordUpdated
                ? 'Password updated. The user can sign in with the new password now.'
                : 'User account updated.',
            'user' => [
                'id' => $id,
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'status' => $status,
                'notes' => $notes,
            ],
        ]);
    }

    if ($password === '') {
        throw new InvalidArgumentException('Password is required for new users.');
    }
    $strength = smsValidatePasswordStrength($password);
    if (!$strength['ok']) {
        throw new InvalidArgumentException($strength['message']);
    }

    $accountFields['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    $insert = null;
    if ($userColumns !== null) {
        $insert = umUserInsertStatement($userColumns, $accountFields);
    }

    $pdo->beginTransaction();
    try {
        if ($insert === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO `sms2_users` (username, email, password_hash, full_name, role_key, student_id, status, notes, password_changed_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $username,
                $email,
                $accountFields['password_hash'],
                $fullName,
                $role,
                $studentId,
                $status,
                $notes !== '' ? $notes : null,
            ]);
            $userInsertCompleted = true;
        } else {
            $pdo->prepare($insert['sql'])->execute($insert['params']);
            $userInsertCompleted = true;
        }

        $newUserId = (int) $pdo->lastInsertId();
        rcSyncAssignmentFromUserAccount($newUserId, $role, $fullName, $email, $status);
        // Profile setup runs CREATE TABLE, which implicitly commits. Keep it
        // outside this transaction so commit() is not called with none active.
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            try {
                $pdo->rollBack();
            } catch (Throwable $rollbackError) {
                error_log('save-user rollback failed: ' . $rollbackError->getMessage());
            }
        }
        throw $e;
    }

    if ($role === 'student') {
        require_once ROOT_PATH . '/modules/student-portal/includes/student-profile.php';
        studentPortalEnsureProfileForUser($newUserId, (string) ($studentId ?? ''), $role);
    }

    logActivity('create', 'Created user ' . $username, 'user-management');
    umEmit([
        'ok' => true,
        'created' => true,
        'id' => $newUserId,
        'message' => 'Account successfully created.',
        'user' => [
            'id' => $newUserId,
            'full_name' => $fullName,
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'status' => $status,
            'notes' => $notes,
        ],
    ]);
} catch (InvalidArgumentException $e) {
    $createdPayload = umCreatedAccountPayloadIfPersisted($pdo, $userInsertCompleted, $newUserId, $username, $email, $e);
    if ($createdPayload !== null) {
        umEmit($createdPayload);
    }
    umEmit(['ok' => false, 'error' => $e->getMessage()], 400);
} catch (PDOException $e) {
    $createdPayload = umCreatedAccountPayloadIfPersisted($pdo, $userInsertCompleted, $newUserId, $username, $email, $e);
    if ($createdPayload !== null) {
        umEmit($createdPayload);
    }
    umEmit(['ok' => false, 'error' => umPublicDbError($e)], 400);
} catch (Throwable $e) {
    error_log('save-user: ' . $e->getMessage());
    $createdPayload = umCreatedAccountPayloadIfPersisted($pdo, $userInsertCompleted, $newUserId, $username, $email, $e);
    if ($createdPayload !== null) {
        umEmit($createdPayload);
    }
    umEmit([
        'ok' => false,
        'error' => 'The account could not be saved. Nothing was changed. Refresh the page and try again.',
    ], 500);
}
