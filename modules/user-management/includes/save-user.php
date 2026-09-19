<?php
/**
 * SMS 2 – Save / update / archive / purge user (admin API)
 * Prefer set_status (archive/restore). Hard delete only for already-archived accounts.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/security-workflow.php';

header('Content-Type: application/json');

if (!isAuthenticated() || !userCanAccessModule('user-management')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    // Fallback to form POST
    $data = $_POST;
}

requireCsrf(isset($data['csrf_token']) ? (string) $data['csrf_token'] : null);

$pdo = db();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
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
        throw new RuntimeException('Could not update password');
    }
}

/**
 * Ensure the optional users.id link column exists on the adviser assignment
 * table (idempotent). Failures surface at insert time, never silently here.
 */
function rcEnsureAdviserUserColumn(PDO $crad): void
{
    try {
        $col = $crad->query("SHOW COLUMNS FROM research_adviser_assignments LIKE 'adviser_user_id'")->fetch();
        if (!$col) {
            $crad->exec("ALTER TABLE research_adviser_assignments ADD COLUMN adviser_user_id INT UNSIGNED DEFAULT NULL AFTER adviser_email, ADD KEY idx_raa_user (adviser_user_id)");
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
        $col = $crad->query("SHOW COLUMNS FROM research_coordinator_assignments LIKE 'group_number'")->fetch();
        if ($col && strtoupper((string) ($col['Null'] ?? 'YES')) === 'NO') {
            $crad->exec("ALTER TABLE research_coordinator_assignments MODIFY group_number VARCHAR(40) DEFAULT NULL");
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
function rcSyncAssignmentFromUserAccount(int $userId, string $role, string $fullName, string $email, string $status): void
{
    if ($userId <= 0 || !in_array($role, ['adviser', 'research_coordinator'], true)) {
        return;
    }

    require_once ROOT_PATH . '/modules/crad/config/config.php';
    $crad = getCradDatabaseConnection();

    try {
        if ($role === 'adviser') {
            rcEnsureAdviserUserColumn($crad);

            $stmt = $crad->prepare(
                "SELECT id, adviser_user_id, research_group_id, proposal_id, group_number
                 FROM research_adviser_assignments
                 WHERE adviser_user_id = :uid
                    OR LOWER(TRIM(adviser_email)) = LOWER(TRIM(:email))
                 ORDER BY id ASC
                 LIMIT 1"
            );
            $stmt->execute([':uid' => $userId, ':email' => $email]);
            $existing = $stmt->fetch();

            if ($existing) {
                $crad->prepare(
                    "UPDATE research_adviser_assignments
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
                "INSERT INTO research_adviser_assignments
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

        $stmt = $crad->prepare(
            "SELECT id, group_number FROM research_coordinator_assignments
             WHERE coordinator_user_id = :uid
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            if (trim((string) ($existing['group_number'] ?? '')) === '') {
                $crad->prepare("UPDATE research_coordinator_assignments SET coordinator_name = :name, coordinator_email = :email, updated_at = NOW() WHERE id = :id")
                    ->execute([':name' => $fullName, ':email' => $email, ':id' => (int) $existing['id']]);
            }
            return;
        }

        $crad->prepare(
            "INSERT INTO research_coordinator_assignments
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
    } catch (Throwable $e) {
        throw new RuntimeException('Assignment record could not be created: ' . $e->getMessage(), 0, $e);
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
        $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        if ($stmt->rowCount() < 1) {
            // still ok if status unchanged
            $check = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
            $check->execute([$id]);
            if (!$check->fetch()) {
                throw new InvalidArgumentException('User not found');
            }
        }
        $label = $status === 'active' ? 'Restored' : 'Archived';
        logActivity('update', $label . ' user #' . $id . ' (status=' . $status . ')', 'user-management');
        echo json_encode(['ok' => true, 'status' => $status]);
        exit;
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
        $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new InvalidArgumentException('User not found');
        }
        $cur = (string) ($row['status'] ?? '');
        if (!in_array($cur, ['inactive', 'locked', 'suspended'], true)) {
            throw new InvalidArgumentException('Archive the user first. Permanent delete is only allowed for archived accounts.');
        }
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        logActivity('delete', 'Permanently deleted archived user #' . $id, 'user-management');
        echo json_encode(['ok' => true]);
        exit;
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
            throw new RuntimeException('Reset failed');
        }
        logActivity('password_reset', 'Admin reset password for user #' . $id, 'user-management');
        echo json_encode(['ok' => true]);
        exit;
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

    if ($fullName === '' || $username === '' || $email === '' || !in_array($role, $validRoles, true)) {
        throw new InvalidArgumentException('Missing or invalid fields');
    }
    if (!in_array($status, $validStatus, true)) {
        $status = 'active';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email');
    }

    if ($role === 'student' && preg_match('/^s\d+$/i', $username)) {
        $studentId = strtoupper($username);
    }

    if ($id > 0) {
        $passwordUpdated = false;
        if ($password !== '' && $status === 'locked') {
            $status = 'active';
        }
        $updateSql = 'UPDATE users SET full_name=?, username=?, email=?, role_key=?, status=?, notes=?';
        $updateParams = [$fullName, $username, $email, $role, $status, $notes !== '' ? $notes : null];
        if ($studentId !== null) {
            $updateSql .= ', student_id=?';
            $updateParams[] = $studentId;
        }
        $updateSql .= ' WHERE id=?';
        $updateParams[] = $id;
        $pdo->prepare($updateSql)->execute($updateParams);
        if ($password !== '') {
            umApplyUserPassword($id, $password);
            $passwordUpdated = true;
        }
        try {
            rcSyncAssignmentFromUserAccount($id, $role, $fullName, $email, $status);
        } catch (Throwable $e) {
            error_log('Assignment sync after user save: ' . $e->getMessage());
            throw $e;
        }
        if ($role === 'student') {
            require_once ROOT_PATH . '/modules/student-portal/includes/student-profile.php';
            studentPortalEnsureProfileForUser($id, (string) ($studentId ?? ''), $role);
        }
        logActivity(
            $passwordUpdated ? 'password_reset' : 'update',
            ($passwordUpdated ? 'Updated user and password for ' : 'Updated user ') . $username,
            'user-management'
        );
        echo json_encode([
            'ok' => true,
            'updated' => true,
            'password_updated' => $passwordUpdated,
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
        exit;
    }

    if ($password === '') {
        throw new InvalidArgumentException('Password is required for new users');
    }
    $strength = smsValidatePasswordStrength($password);
    if (!$strength['ok']) {
        throw new InvalidArgumentException($strength['message']);
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, role_key, student_id, status, notes, password_changed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $username,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $fullName,
            $role,
            $studentId,
            $status,
            $notes !== '' ? $notes : null,
        ]);

        $newUserId = (int) $pdo->lastInsertId();
        rcSyncAssignmentFromUserAccount($newUserId, $role, $fullName, $email, $status);
        if ($role === 'student') {
            require_once ROOT_PATH . '/modules/student-portal/includes/student-profile.php';
            studentPortalEnsureProfileForUser($newUserId, (string) ($studentId ?? ''), $role);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    logActivity('create', 'Created user ' . $username, 'user-management');
    echo json_encode([
        'ok' => true,
        'created' => true,
        'id' => $newUserId,
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
} catch (PDOException $e) {
    error_log('save-user PDO: ' . $e->getMessage());
    http_response_code(400);
    $msg = 'Could not save user';
    if (str_contains($e->getMessage(), 'Duplicate')) {
        $msg = 'Username or email already exists';
    }
    echo json_encode(['ok' => false, 'error' => $msg]);
} catch (Throwable $e) {
    error_log('save-user: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
