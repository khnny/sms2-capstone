<?php
/**
 * Shared Adviser faculty account page.
 */
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/notifications.php';
/* ── Ensure adviser_signature_data column exists (one-time migration) ── */
(function () {
    try {
        $crad = cradDb();
        if (!$crad) return;
        $cols = $crad->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'adviser_signature_data'")->fetchAll();
        if (!$cols) {
            $crad->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN adviser_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER adviser_remarks");
        }
        $coordStatus = $crad->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'coordinator_status'")->fetchAll();
        if (!$coordStatus) {
            $crad->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_status VARCHAR(30) NOT NULL DEFAULT 'Not Ready' AFTER adviser_signature_data");
        }
        $coordRemarks = $crad->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'coordinator_remarks'")->fetchAll();
        if (!$coordRemarks) {
            $crad->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_remarks TEXT NULL DEFAULT NULL AFTER coordinator_status");
        }
        $coordScreening = $crad->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'coordinator_screening_json'")->fetchAll();
        if (!$coordScreening) {
            $crad->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_screening_json TEXT NULL DEFAULT NULL AFTER coordinator_remarks");
        }
        $coordSig = $crad->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'coordinator_signature_data'")->fetchAll();
        if (!$coordSig) {
            $crad->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER coordinator_remarks");
        }
        $coordReviewed = $crad->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'coordinator_reviewed_at'")->fetchAll();
        if (!$coordReviewed) {
            $crad->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN coordinator_reviewed_at DATETIME NULL DEFAULT NULL AFTER coordinator_signature_data");
        }
        $cradStatus = $crad->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'crad_status'")->fetchAll();
        if (!$cradStatus) {
            $crad->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN crad_status VARCHAR(30) NOT NULL DEFAULT 'Not Ready' AFTER coordinator_reviewed_at");
        }
        $cradSig = $crad->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'crad_signature_data'")->fetchAll();
        if (!$cradSig) {
            $crad->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN crad_signature_data MEDIUMTEXT NULL DEFAULT NULL AFTER crad_status");
        }
        $cradReviewed = $crad->query("SHOW COLUMNS FROM `crad_title_approvals` LIKE 'crad_reviewed_at'")->fetchAll();
        if (!$cradReviewed) {
            $crad->exec("ALTER TABLE `crad_title_approvals` ADD COLUMN crad_reviewed_at DATETIME NULL DEFAULT NULL AFTER crad_signature_data");
        }
    } catch (Throwable) { /* silently ignore */ }
})();

/* ── AJAX handlers (must run before any HTML output) ───────────────
 * Call facultyAccountDispatchAjax() early (see modules/faculty/.../approved-research.php)
 * so JSON responses stay clean. The function is safe to call after output too —
 * it skips header() once output has started instead of emitting a PHP warning.
 */
function facultyAccountDispatchAjax(): void
{
    if (empty($_GET['faculty_ajax'])) {
        return;
    }

    $ajaxAction = (string) $_GET['faculty_ajax'];

    $sendJsonHeader = static function (): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }
    };

    /* Poll inbox rows */
    if ($ajaxAction === 'title-inbox') {
        $sendJsonHeader();
        try {
            $rows    = facultyTitleApprovalsInbox();
            $pending = count(array_filter($rows, fn($r) => $r['status'] === 'Pending'));
            echo json_encode([
                'ok'        => true,
                'rows'      => $rows,
                'pending'   => $pending,
                'last_sync' => date('M j, Y g:i:s A'),
            ]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /* Update status (+ optional adviser signature) */
    if ($ajaxAction === 'title-status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $sendJsonHeader();
        $body      = json_decode((string) file_get_contents('php://input'), true) ?? [];
        $id        = (int) ($body['id'] ?? 0);
        $status    = in_array($body['status'] ?? '', ['Reviewed', 'Approved', 'Returned'], true)
                     ? $body['status'] : null;
        $remarks   = trim((string) ($body['remarks'] ?? ''));
        $signature = trim((string) ($body['adviser_signature_data'] ?? ''));
        if ($signature !== '' && !str_starts_with($signature, 'data:image/png;base64,')) {
            $signature = '';
        }
        if ($id && $status) {
            try {
                $ok = facultyTitleApprovalsUpdateStatus($id, $status, $remarks, $signature);
                echo json_encode(['ok' => $ok]);
            } catch (Throwable $e) {
                error_log('Title approval adviser update failed: ' . $e->getMessage());
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['ok' => false, 'error' => 'Invalid input.']);
        }
        exit;
    }

    $sendJsonHeader();
    echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
    exit;
}

/* Auto-dispatch when this file is included before any output (approved-research.php). */
facultyAccountDispatchAjax();

function facultyAccountMap(?string $role = null): array
{
    $role = $role ?? getCurrentUserRoleKey();
    if ($role === 'research_director') {
        return [
            'role_label' => 'Research Director',
            'table' => '',
            'name_col' => '',
            'email_col' => '',
            'role_col' => "'Research Director'",
        ];
    }

    return [
        'role_label' => 'Adviser',
        'table' => 'crad_research_adviser_assignments',
        'name_col' => 'adviser_name',
        'email_col' => 'adviser_email',
        'role_col' => "'Research Adviser'",
    ];
}

function facultyPruneOrphanAssignments(PDO $crad): void
{
    $crad->exec("
        UPDATE `crad_research_adviser_assignments` a
        JOIN `crad_research_proposals` p
          ON a.proposal_number IS NOT NULL
         AND a.proposal_number <> ''
         AND (p.proposal_number = a.proposal_number OR p.ref_code = a.proposal_number)
        SET a.proposal_id = p.id
        WHERE a.proposal_id IS NULL OR a.proposal_id <> p.id
    ");
}

function facultyEnsureAdviserAssignmentSchema(PDO $crad): void
{
    $crad->exec("
        CREATE TABLE IF NOT EXISTS `crad_research_adviser_assignments` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            research_group_id INT UNSIGNED DEFAULT NULL,
            proposal_id INT UNSIGNED DEFAULT NULL,
            proposal_number VARCHAR(30) DEFAULT NULL,
            group_number VARCHAR(40) DEFAULT NULL,
            adviser_name VARCHAR(150) NOT NULL DEFAULT '',
            adviser_email VARCHAR(190) NOT NULL DEFAULT '',
            expertise VARCHAR(255) NOT NULL DEFAULT '',
            availability_status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            assignment_status VARCHAR(40) NOT NULL DEFAULT 'Pending',
            notes TEXT DEFAULT NULL,
            assigned_by INT UNSIGNED DEFAULT NULL,
            assigned_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_raa_group (research_group_id),
            KEY idx_raa_proposal (proposal_id),
            KEY idx_raa_group_number (group_number),
            KEY idx_raa_status (assignment_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    smsAssignmentNotificationEnsureSentSchema($crad);

    try {
        $crad->exec("
            DELETE a
            FROM `crad_research_adviser_assignments` a
            INNER JOIN `crad_research_adviser_assignments` keep
              ON LOWER(TRIM(a.adviser_email)) = LOWER(TRIM(keep.adviser_email))
             AND LOWER(TRIM(a.adviser_name)) = LOWER(TRIM(keep.adviser_name))
             AND a.id < keep.id
            WHERE TRIM(a.adviser_email) <> ''
              AND TRIM(a.adviser_name) <> ''
        ");

        if (!$crad->query("SHOW INDEX FROM `crad_research_adviser_assignments` WHERE Key_name = 'uniq_raa_adviser_identity'")->fetch()) {
            $crad->exec("
                ALTER TABLE `crad_research_adviser_assignments`
                ADD UNIQUE KEY uniq_raa_adviser_identity (adviser_email, adviser_name)
            ");
        }
    } catch (Throwable $e) {
        error_log('Faculty adviser duplicate guard skipped: ' . $e->getMessage());
    }
}

function facultyEnsureCurrentAdviserRows(PDO $crad, ?string $expertise = null, ?string $availability = null): void
{
    $email = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    $name = trim((string) ($_SESSION['user_name'] ?? ''));
    if ($email === '' && $name === '') {
        return;
    }

    facultyEnsureAdviserAssignmentSchema($crad);

    $stmt = $crad->prepare("
        INSERT INTO `crad_research_adviser_assignments`
            (research_group_id, proposal_id, proposal_number, group_number, adviser_name, adviser_email,
             expertise, availability_status, assignment_status, notes, assigned_by, assigned_at, created_at, updated_at,
             notification_sent_at, notification_sent_by)
        SELECT
            g.id,
            g.proposal_id,
            COALESCE(t.proposal_number, g.proposal_number),
            g.group_number,
            COALESCE(NULLIF(t.adviser_name, ''), NULLIF(g.adviser, ''), :fallback_name),
            COALESCE(NULLIF(t.adviser_email, ''), :fallback_email),
            :expertise,
            :availability_status,
            'Pending',
            'Synced from fully approved title approval for adviser account.',
            NULL,
            NULL,
            NOW(),
            NOW(),
            NULL,
            NULL
        FROM `crad_research_groups` g
        JOIN `crad_title_approvals` t ON t.id = g.title_approval_id
        WHERE t.status = 'Approved'
          AND t.coordinator_status = 'Approved'
          AND t.crad_status = 'Approved'
          AND t.adviser_signature_data IS NOT NULL
          AND t.adviser_signature_data <> ''
          AND t.coordinator_signature_data IS NOT NULL
          AND t.coordinator_signature_data <> ''
          AND t.crad_signature_data IS NOT NULL
          AND t.crad_signature_data <> ''
          AND (
                (:email_gate1 <> '' AND LOWER(TRIM(t.adviser_email)) = :email_match1)
             OR (:name_gate1 <> '' AND LOWER(TRIM(t.adviser_name)) = :name_match1)
             OR (:name_gate2 <> '' AND LOWER(TRIM(g.adviser)) = :name_match2)
          )
          AND NOT EXISTS (
              SELECT 1
              FROM `crad_research_adviser_assignments` existing
              WHERE (
                    (:email_gate2 <> '' AND LOWER(TRIM(existing.adviser_email)) = :email_match2)
                 OR (:name_gate3 <> '' AND LOWER(TRIM(existing.adviser_name)) = :name_match3)
              )
          )
    ");
    $stmt->execute([
        ':fallback_name' => $name,
        ':fallback_email' => $email,
        ':expertise' => trim((string) ($expertise ?? '')) !== '' ? trim((string) $expertise) : 'General Research Methods',
        ':availability_status' => in_array($availability, ['Available', 'Pending', 'Unavailable'], true) ? $availability : 'Pending',
        ':email_gate1' => $email,
        ':email_match1' => $email,
        ':email_gate2' => $email,
        ':email_match2' => $email,
        ':name_gate1' => strtolower($name),
        ':name_match1' => strtolower($name),
        ':name_gate2' => strtolower($name),
        ':name_match2' => strtolower($name),
        ':name_gate3' => strtolower($name),
        ':name_match3' => strtolower($name),
    ]);
}

function facultyAccountAssignments(): array
{
    $email = strtolower((string) ($_SESSION['user_email'] ?? ''));
    $name = (string) ($_SESSION['user_name'] ?? '');
    $rows = [];

    $crad = cradDb();
    if (!$crad || $email === '' || getCurrentUserRoleKey() === 'research_director') {
        return $rows;
    }

    $map = facultyAccountMap();
    $table = '`' . str_replace('`', '``', (string) $map['table']) . '`';
    $nameCol = $map['name_col'];
    $emailCol = $map['email_col'];
    $roleCol = $map['role_col'];

    try {
        facultyEnsureAdviserAssignmentSchema($crad);
        facultyEnsureCurrentAdviserRows($crad);
        facultyPruneOrphanAssignments($crad);
        $stmt = $crad->prepare(
            "SELECT
                a.id,
                a.group_number,
                a.proposal_number,
                a.expertise,
                a.availability_status,
                a.assignment_status,
                a.confirmation_status,
                a.updated_at,
                rp.id AS live_proposal_id,
                a.$nameCol AS faculty_name,
                a.$emailCol AS faculty_email,
                $roleCol AS faculty_role,
                COALESCE(
                    NULLIF(rg.group_name, ''),
                    NULLIF(a.group_number, ''),
                    CONCAT('Group ', LPAD(COALESCE(a.research_group_id, 0), 2, '0'))
                ) AS group_name,
                COALESCE(NULLIF(rg.research_title, ''), rp.research_title, ta.proposed_title) AS research_title,
                COALESCE(rg.status, rp.status, ta.status, 'Approved') AS research_status
             FROM $table a
             LEFT JOIN `crad_research_proposals` rp
               ON (a.proposal_id IS NOT NULL AND rp.id = a.proposal_id)
               OR (
                    a.proposal_number IS NOT NULL
                    AND a.proposal_number <> ''
                    AND (rp.proposal_number = a.proposal_number OR rp.ref_code = a.proposal_number)
                  )
             LEFT JOIN `crad_research_groups` rg
               ON (a.research_group_id IS NOT NULL AND rg.id = a.research_group_id)
               OR (a.group_number IS NOT NULL AND a.group_number <> '' AND rg.group_number = a.group_number)
               OR (rg.proposal_id = rp.id)
             LEFT JOIN `crad_title_approvals` ta
               ON ta.id = rg.title_approval_id
             WHERE LOWER(a.$emailCol) = ?
                OR LOWER(a.$nameCol) = LOWER(?)
             ORDER BY FIELD(a.assignment_status, 'Assigned', 'Pending'), a.updated_at DESC, a.id DESC"
        );
        $stmt->execute([$email, $name]);
        $seen = [];
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $proposalId = (int) ($row['live_proposal_id'] ?? 0);
            $title = strtolower(trim((string) ($row['research_title'] ?? '')));
            $key = $title !== '' ? 'title:' . $title : 'proposal:' . $proposalId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rows[] = $row;
        }
    } catch (Throwable $e) {
        error_log('Faculty account assignment load failed: ' . $e->getMessage());
    }

    return $rows;
}

function facultyApprovedResearchAssignments(): array
{
    $email = strtolower((string) ($_SESSION['user_email'] ?? ''));
    $name  = (string) ($_SESSION['user_name'] ?? '');
    $rows  = [];

    $crad = cradDb();
    if (!$crad || $email === '' || getCurrentUserRoleKey() === 'research_director') {
        return $rows;
    }

    $map      = facultyAccountMap();
    $table    = '`' . str_replace('`', '``', (string) $map['table']) . '`';
    $nameCol  = $map['name_col'];
    $emailCol = $map['email_col'];
    $roleCol  = $map['role_col'];

    try {
        $stmt = $crad->prepare(
            "SELECT
                a.id,
                a.group_number,
                a.proposal_number,
                a.expertise,
                a.availability_status,
                a.assignment_status,
                a.confirmation_status,
                a.updated_at,
                rp.id            AS live_proposal_id,
                rp.status        AS proposal_status,
                rp.approved_at,
                a.$nameCol       AS faculty_name,
                a.$emailCol      AS faculty_email,
                $roleCol         AS faculty_role,
                COALESCE(
                    NULLIF(rg.group_name, ''),
                    NULLIF(a.group_number, ''),
                    CONCAT('Group ', LPAD(COALESCE(a.research_group_id, 0), 2, '0'))
                ) AS group_name,
                COALESCE(NULLIF(rg.research_title, ''), rp.research_title) AS research_title,
                COALESCE(rg.status, rp.status, 'Approved') AS research_status
             FROM $table a
             JOIN `crad_research_proposals` rp
               ON (a.proposal_id IS NOT NULL AND rp.id = a.proposal_id)
               OR (
                    a.proposal_number IS NOT NULL
                    AND a.proposal_number <> ''
                    AND (rp.proposal_number = a.proposal_number OR rp.ref_code = a.proposal_number)
                  )
             LEFT JOIN `crad_research_groups` rg
               ON (a.research_group_id IS NOT NULL AND rg.id = a.research_group_id)
               OR (a.group_number IS NOT NULL AND a.group_number <> '' AND rg.group_number = a.group_number)
               OR (rg.proposal_id = rp.id)
             WHERE (LOWER(a.$emailCol) = ? OR LOWER(a.$nameCol) = LOWER(?))
               AND rp.status = 'Approved'
             ORDER BY rp.approved_at DESC, a.updated_at DESC, a.id DESC"
        );
        $stmt->execute([$email, $name]);
        $seen = [];
        foreach (($stmt->fetchAll() ?: []) as $row) {
            $proposalId = (int) ($row['live_proposal_id'] ?? 0);
            $title      = strtolower(trim((string) ($row['research_title'] ?? '')));
            $key        = $title !== '' ? 'title:' . $title : 'proposal:' . $proposalId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rows[]     = $row;
        }
    } catch (Throwable $e) {
        error_log('Approved research load failed: ' . $e->getMessage());
    }

    return $rows;
}

/* â”€â”€ Title Approval Inbox (from students) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
function facultyTitleApprovalsInbox(): array
{
    $email = strtolower((string) ($_SESSION['user_email'] ?? ''));
    $name  = (string) ($_SESSION['user_name'] ?? '');
    $crad  = cradDb();
    if (!$crad || $email === '') {
        return [];
    }
    try {
        $stmt = $crad->prepare(
            "SELECT id, student_id, student_name, submission_date, department,
                    proposed_title, discipline_cluster, primary_sdg, research_agenda,
                    sdg_justification, members_json, adviser_name, adviser_email,
                    coordinator_name, status, adviser_remarks, adviser_signature_data,
                    sent_at, reviewed_at
             FROM `crad_title_approvals`
             WHERE LOWER(adviser_email) = :email
                OR LOWER(adviser_name)  = LOWER(:name)
             ORDER BY
                FIELD(status,'Pending','Reviewed','Approved','Returned'),
                sent_at DESC"
        );
        $stmt->execute([':email' => $email, ':name' => $name]);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('facultyTitleApprovalsInbox failed: ' . $e->getMessage());
        return [];
    }
}

function facultyTitleApprovalsUpdateStatus(int $id, string $status, string $remarks, string $signature = ''): bool
{
    $email = strtolower((string) ($_SESSION['user_email'] ?? ''));
    $name  = (string) ($_SESSION['user_name'] ?? '');
    $crad  = cradDb();
    if (!$crad) {
        return false;
    }
    $coordStatus = $status === 'Approved' ? 'Pending' : 'Not Ready';

    /* Build SET clause — only write signature when it is provided */
    if ($signature !== '') {
        $stmt = $crad->prepare(
            "UPDATE `crad_title_approvals`
             SET status = :status, adviser_remarks = :remarks,
                 adviser_signature_data = :sig,
                 coordinator_status = :coord_status,
                 coordinator_remarks = NULL,
                 coordinator_screening_json = NULL,
                 coordinator_signature_data = NULL,
                 coordinator_reviewed_at = NULL,
                 crad_status = 'Not Ready',
                 crad_signature_data = NULL,
                 crad_reviewed_at = NULL,
                 reviewed_at = NOW()
             WHERE id = :id
               AND (LOWER(adviser_email) = :email OR LOWER(adviser_name) = LOWER(:name))
               AND (:status_gate <> 'Approved' OR status = 'Pending')"
        );
        $stmt->execute([
            ':status'  => $status,
            ':status_gate' => $status,
            ':coord_status' => $coordStatus,
            ':remarks' => $remarks ?: null,
            ':sig'     => $signature,
            ':id'      => $id,
            ':email'   => $email,
            ':name'    => $name,
        ]);
    } else {
        $stmt = $crad->prepare(
            "UPDATE `crad_title_approvals`
             SET status = :status,
                 adviser_remarks = :remarks,
                 adviser_signature_data = CASE WHEN :clear_adviser_signature = 1 THEN NULL ELSE adviser_signature_data END,
                 coordinator_status = :coord_status,
                 coordinator_remarks = NULL,
                 coordinator_screening_json = NULL,
                 coordinator_signature_data = NULL,
                 coordinator_reviewed_at = NULL,
                 crad_status = 'Not Ready',
                 crad_signature_data = NULL,
                 crad_reviewed_at = NULL,
                 reviewed_at = NOW()
             WHERE id = :id
               AND (LOWER(adviser_email) = :email OR LOWER(adviser_name) = LOWER(:name))"
        );
        $stmt->execute([
            ':status'  => $status,
            ':clear_adviser_signature' => $status === 'Returned' ? 1 : 0,
            ':coord_status' => $coordStatus,
            ':remarks' => $remarks ?: null,
            ':id'      => $id,
            ':email'   => $email,
            ':name'    => $name,
        ]);
    }
    return $stmt->rowCount() > 0;
}

function facultyAccountPostNotice(): ?array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['faculty_action'])) {
        return null;
    }

    try {
        requireCsrf((string) ($_POST['csrf_token'] ?? ''));
    } catch (Throwable $e) {
        return ['type' => 'danger', 'message' => 'Security token expired. Refresh the page and try again.'];
    }

    $role = getCurrentUserRoleKey();
    $action = (string) $_POST['faculty_action'];
    if (!in_array($role, ['adviser', 'research_director'], true)) {
        return ['type' => 'danger', 'message' => 'This action is only available for faculty accounts.'];
    }
    if ($role === 'research_director' && $action !== 'update_profile') {
        return ['type' => 'danger', 'message' => 'This action is only available for Adviser accounts.'];
    }

    $oldName = (string) ($_SESSION['user_name'] ?? '');
    $oldEmail = strtolower((string) ($_SESSION['user_email'] ?? ''));
    $map = facultyAccountMap($role);
    $table = '`' . str_replace('`', '``', (string) $map['table']) . '`';
    $nameCol = $map['name_col'];
    $emailCol = $map['email_col'];

    try {
        if ($action === 'update_profile') {
            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['type' => 'danger', 'message' => 'Enter a valid name and email.'];
            }

            $pdo = db();
            if (!$pdo) {
                return ['type' => 'danger', 'message' => 'Main database is unavailable.'];
            }

            $pdo->prepare('UPDATE `sms2_users` SET full_name = ?, email = ? WHERE id = ? LIMIT 1')
                ->execute([$fullName, $email, (int) getCurrentUserId()]);

            $crad = cradDb();
            if ($crad && $role === 'adviser') {
                smsAssignmentNotificationEnsureSentSchema($crad);
                $stmt = $crad->prepare(
                    "UPDATE $table
                     SET $nameCol = ?, $emailCol = ?
                     WHERE LOWER($emailCol) = ? OR LOWER($nameCol) = LOWER(?)"
                );
                $stmt->execute([$fullName, $email, $oldEmail, $oldName]);
            }

            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;
            return ['type' => 'success', 'message' => 'Profile updated successfully.'];
        }

        if ($action === 'update_expertise') {
            $expertise = trim((string) ($_POST['expertise'] ?? ''));
            if ($expertise === '') {
                return ['type' => 'danger', 'message' => 'Expertise cannot be empty.'];
            }

            $crad = cradDb();
            if (!$crad) {
                return ['type' => 'danger', 'message' => 'CRAD database is unavailable.'];
            }

            facultyEnsureAdviserAssignmentSchema($crad);
            facultyEnsureCurrentAdviserRows($crad, $expertise, null);
            $stmt = $crad->prepare(
                "UPDATE $table a
                 SET a.expertise = ?,
                     a.updated_at = NOW()
                 WHERE LOWER(TRIM(a.$emailCol)) = ?
                    OR LOWER(TRIM(a.$nameCol)) = LOWER(?)"
            );
            $stmt->execute([$expertise, $oldEmail, $oldName]);
            return ['type' => 'success', 'message' => 'Expertise updated in the assignment database.'];
        }

        if ($action === 'update_availability') {
            $availability = trim((string) ($_POST['availability_status'] ?? ''));
            $allowedAvailability = ['Available', 'Pending', 'Unavailable'];
            if (!in_array($availability, $allowedAvailability, true)) {
                return ['type' => 'danger', 'message' => 'Select a valid availability status.'];
            }

            $crad = cradDb();
            if (!$crad) {
                return ['type' => 'danger', 'message' => 'CRAD database is unavailable.'];
            }

            facultyEnsureAdviserAssignmentSchema($crad);
            facultyEnsureCurrentAdviserRows($crad, null, $availability);
            facultyPruneOrphanAssignments($crad);
            if ($availability === 'Available') {
                $stmt = $crad->prepare(
                    "UPDATE $table a
                     SET a.availability_status = ?,
                         a.updated_at = NOW()
                     WHERE LOWER(TRIM(a.$emailCol)) = ?
                        OR LOWER(TRIM(a.$nameCol)) = LOWER(?)"
                );
                $stmt->execute([$availability, $oldEmail, $oldName]);
            } else {
                $stmt = $crad->prepare(
                    "UPDATE $table a
                     SET a.availability_status = ?,
                         a.assignment_status = 'Pending',
                         a.assigned_by = NULL,
                         a.assigned_at = NULL,
                         a.notification_sent_at = NULL,
                         a.notification_sent_by = NULL,
                         a.updated_at = NOW()
                     WHERE LOWER(TRIM(a.$emailCol)) = ?
                        OR LOWER(TRIM(a.$nameCol)) = LOWER(?)"
                );
                $stmt->execute([$availability, $oldEmail, $oldName]);
            }
            return ['type' => 'success', 'message' => 'Availability updated successfully.'];
        }

    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate')) {
            return ['type' => 'danger', 'message' => 'That email is already used by another account.'];
        }
        error_log('Faculty account save failed: ' . $e->getMessage());
        return ['type' => 'danger', 'message' => 'Could not save changes. Please check the database.'];
    } catch (Throwable $e) {
        error_log('Faculty account save failed: ' . $e->getMessage());
        return ['type' => 'danger', 'message' => 'Could not save changes.'];
    }

    return null;
}

function facultyAccountPrimaryExpertise(array $assignments): string
{
    foreach ($assignments as $row) {
        $expertise = trim((string) ($row['expertise'] ?? ''));
        if ($expertise !== '') {
            return $expertise;
        }
    }
    return '';
}

function facultyAccountPrimaryAvailability(array $assignments): string
{
    foreach ($assignments as $row) {
        $availability = trim((string) ($row['availability_status'] ?? ''));
        if ($availability !== '') {
            return $availability;
        }
    }
    return 'Pending';
}

function renderFacultyResearchList(array $assignments, string $emptyMessage = 'Approved adviser assignments will appear here automatically.'): void
{
    if (!$assignments) {
        ?>
        <div class="faculty-empty">
            <strong>No assigned research yet.</strong>
            <div class="mt-1"><?= htmlspecialchars($emptyMessage) ?></div>
        </div>
        <?php
        return;
    }
    ?>
    <div class="faculty-research-list">
        <?php foreach ($assignments as $row): ?>
            <?php
            $assignmentStatus = (string) ($row['assignment_status'] ?? 'Pending');
            $statusClass = strtolower($assignmentStatus) === 'assigned' ? 'success' : 'warning';
            $availability = (string) ($row['availability_status'] ?? 'Pending');
            $availabilityClass = strtolower($availability) === 'available'
                ? 'success'
                : (strtolower($availability) === 'unavailable' ? 'danger' : 'warning');
            $updatedAt = strtotime((string) ($row['updated_at'] ?? '')) ?: time();
            ?>
            <article class="faculty-research-card">
                <div>
                    <h3><?= htmlspecialchars((string) $row['research_title']) ?></h3>
                    <div class="faculty-meta">
                        <?= htmlspecialchars((string) $row['group_name']) ?> &middot;
                        <?= htmlspecialchars((string) $row['group_number']) ?> &middot;
                        <?= htmlspecialchars((string) $row['proposal_number']) ?>
                    </div>
                    <div class="faculty-tags">
                        <span class="faculty-pill"><?= htmlspecialchars((string) $row['faculty_role']) ?></span>
                        <span class="faculty-pill"><?= htmlspecialchars((string) $row['expertise']) ?></span>
                        <span class="faculty-pill <?= $statusClass ?>"><?= htmlspecialchars($assignmentStatus) ?></span>
                        <span class="faculty-pill <?= $availabilityClass ?>"><?= htmlspecialchars($availability) ?></span>
                    </div>
                </div>
                <small class="text-muted"><?= htmlspecialchars(date('M j, Y g:i A', $updatedAt)) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function renderFacultyAccountPage(string $title, string $activePage, string $mode = 'overview'): void
{
    $role = getCurrentUserRoleKey();
    $roleLabel = facultyAccountMap($role)['role_label'];
    $notice = facultyAccountPostNotice();
    $assignments = facultyAccountAssignments();
    if (defined('ROOT_PATH') && is_file(ROOT_PATH . '/modules/crad/includes/research-group-flow.php')) {
        require_once ROOT_PATH . '/modules/crad/includes/research-group-flow.php';
    }
    $cradPdo = function_exists('cradDb') ? cradDb() : null;
    $assigned = array_values(array_filter($assignments, static function (array $row) use ($cradPdo): bool {
        if (!$cradPdo || !function_exists('cradRgFlowLatestCycleForGroup')) {
            $confirm = strtolower(trim((string) ($row['confirmation_status'] ?? '')));
            $status = strtolower(trim((string) ($row['assignment_status'] ?? '')));
            return $status === 'confirmed' && $confirm === 'confirmed';
        }
        if (function_exists('cradRgFlowEnsureSchema')) {
            try { cradRgFlowEnsureSchema($cradPdo); } catch (Throwable $e) {}
        }
        $cycle = cradRgFlowLatestCycleForGroup(
            $cradPdo,
            (string) ($row['group_number'] ?? ''),
            (string) ($row['student_id'] ?? ''),
            (int) ($row['research_group_id'] ?? 0)
        );
        $overall = function_exists('cradRgFlowOverallStatusFromCycle')
            ? cradRgFlowOverallStatusFromCycle($cycle)
            : strtolower((string) ($cycle['status'] ?? ''));
        return function_exists('cradRgFlowIsFullyAssignedStatus')
            ? cradRgFlowIsFullyAssignedStatus($overall)
            : ($overall === 'confirmed');
    }));
    $pending = max(0, count($assignments) - count($assigned));
    $csrf = csrfToken();
    $primaryExpertise = facultyAccountPrimaryExpertise($assignments);
    $primaryAvailability = facultyAccountPrimaryAvailability($assignments);
    $availabilityCardClass = strtolower($primaryAvailability) === 'available'
        ? 'success'
        : (strtolower($primaryAvailability) === 'unavailable' ? 'danger' : 'warning');

    ?>
    <style>
        .faculty-account-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; margin-bottom:1rem; }
        .faculty-stat { display:flex; align-items:center; gap:.85rem; border:1px solid var(--sms-border); border-radius:12px; background:var(--sms-card-bg); padding:.95rem 1rem; }
        .faculty-stat-icon { width:42px; height:42px; flex:0 0 auto; display:grid; place-items:center; border-radius:12px; font-size:1rem; }
        .faculty-stat-icon.blue { color:#2563eb; background:rgba(37,99,235,0.12); }
        .faculty-stat-icon.green { color:#059669; background:rgba(16,185,129,0.12); }
        .faculty-stat-icon.amber { color:#d97706; background:rgba(245,158,11,0.14); }
        .faculty-stat-icon.purple { color:#7c3aed; background:rgba(139,92,246,0.12); }
        .faculty-stat span { display:block; color:var(--sms-text-muted); font-size:.72rem; font-weight:800; text-transform:uppercase; }
        .faculty-stat strong { display:block; color:var(--sms-text); font-size:1.45rem; line-height:1.2; }
        .faculty-research-list { display:grid; gap:.85rem; }
        .faculty-research-card { border:1px solid var(--sms-border); border-radius:8px; background:var(--sms-card-bg); padding:1rem; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:start; }
        .faculty-research-card h3 { margin:0 0 .25rem; font-size:1rem; font-weight:800; color:var(--sms-text); }
        .faculty-meta { color:var(--sms-text-muted); font-size:.82rem; font-weight:600; }
        .faculty-tags { display:flex; flex-wrap:wrap; gap:.45rem; margin-top:.7rem; }
        .faculty-pill { display:inline-flex; align-items:center; border-radius:999px; padding:.28rem .65rem; font-size:.72rem; font-weight:800; background:rgba(37,99,235,.10); color:var(--sms-primary); }
        .faculty-pill.success { background:rgba(16,185,129,.16); color:#059669; }
        .faculty-pill.warning { background:rgba(245,158,11,.16); color:#b45309; }
        .faculty-pill.danger { background:rgba(239,68,68,.14); color:#dc2626; }
        .faculty-empty { border:1px dashed var(--sms-border); border-radius:8px; padding:2.5rem 1rem; text-align:center; color:var(--sms-text-muted); background:var(--sms-card-bg); }
        .faculty-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
        .faculty-profile-card { border:1px solid var(--sms-border); border-radius:8px; background:var(--sms-card-bg); padding:1rem; }
        .faculty-profile-label { display:block; color:var(--sms-text-muted); font-size:.72rem; font-weight:800; text-transform:uppercase; margin-bottom:.25rem; }
        .faculty-profile-value { color:var(--sms-text); font-weight:800; overflow-wrap:anywhere; }
        .faculty-choice-row { display:flex; flex-wrap:wrap; gap:.6rem; }
        .faculty-choice-row .btn { border-radius:999px; font-weight:800; }
        .faculty-stat strong.success { color:#059669; }
        .faculty-stat strong.warning { color:#b45309; }
        .faculty-stat strong.danger { color:#dc2626; }
        @media (max-width: 991px) { .faculty-account-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .faculty-research-card { grid-template-columns:1fr; } }
        @media (max-width: 575px) { .faculty-account-grid, .faculty-form-grid { grid-template-columns:1fr; } }
    </style>

    <?php
    $GLOBALS['pageBannerIcon'] = 'fa-user-tie';
    $GLOBALS['pageBannerDescription'] = getCurrentUserName() . ' - ' . (string) ($_SESSION['user_email'] ?? '');
    renderBreadcrumbs([
        ['label' => $roleLabel . ' Account', 'url' => BASE_URL . '/modules/faculty/index.php'],
        ['label' => $title, 'url' => null],
    ]);
    ?>

    <?php if ($notice): ?>
        <div class="alert alert-<?= htmlspecialchars($notice['type']) ?> fw-semibold">
            <?= htmlspecialchars($notice['message']) ?>
        </div>
    <?php endif; ?>

    <div class="faculty-account-grid">
        <section class="faculty-stat"><div class="faculty-stat-icon blue"><?= smsIcon('folder-open') ?></div><div><span>Total Records</span><strong><?= count($assignments) ?></strong></div></section>
        <section class="faculty-stat"><div class="faculty-stat-icon green"><?= smsIcon('user-check') ?></div><div><span>Assigned</span><strong><?= count($assigned) ?></strong></div></section>
        <section class="faculty-stat"><div class="faculty-stat-icon amber"><?= smsIcon('clock') ?></div><div><span>Pending</span><strong><?= $pending ?></strong></div></section>
        <section class="faculty-stat"><div class="faculty-stat-icon purple"><?= smsIcon('toggle-on') ?></div><div><span>Availability</span><strong class="<?= $availabilityCardClass ?>"><?= htmlspecialchars($primaryAvailability) ?></strong></div></section>
    </div>

    <?php if ($mode === 'profile'): ?>
        <section class="card mb-3">
            <div class="card-header">
                <h2 class="h6 mb-0 fw-bold">My Profile</h2>
            </div>
            <div class="card-body">
                <form method="post" class="faculty-form-grid">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="faculty_action" value="update_profile">
                    <div>
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars(getCurrentUserName()) ?>" required>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars((string) ($_SESSION['user_email'] ?? '')) ?>" required>
                    </div>
                    <div class="faculty-profile-card">
                        <span class="faculty-profile-label">Role</span>
                        <span class="faculty-profile-value"><?= htmlspecialchars($roleLabel) ?></span>
                    </div>
                    <div class="faculty-profile-card">
                        <span class="faculty-profile-label">Expertise From DB</span>
                        <span class="faculty-profile-value"><?= htmlspecialchars($primaryExpertise !== '' ? $primaryExpertise : 'No expertise yet') ?></span>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <?= smsIcon('save', ['class' => 'me-1']) ?>Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </section>
    <?php elseif ($mode === 'profile-expertise'): ?>
        <section class="card mb-3">
            <div class="card-header">
                <h2 class="h6 mb-0 fw-bold">Expertise</h2>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="faculty_action" value="update_expertise">
                    <label class="form-label fw-semibold">Expertise Based on Assignment Database</label>
                    <textarea class="form-control mb-3" name="expertise" rows="3" required><?= htmlspecialchars($primaryExpertise) ?></textarea>
                    <button type="submit" class="btn btn-primary">
                        <?= smsIcon('save', ['class' => 'me-1']) ?>Save Expertise
                    </button>
                </form>
            </div>
        </section>
    <?php elseif ($mode === 'profile-availability'): ?>
        <section class="card mb-3">
            <div class="card-header">
                <h2 class="h6 mb-0 fw-bold">Availability Control</h2>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="faculty_action" value="update_availability">
                    <label class="form-label fw-semibold">Set Availability for Active Assignment Records</label>
                    <div class="faculty-choice-row mb-3">
                        <?php foreach (['Available', 'Pending', 'Unavailable'] as $option): ?>
                            <?php $checked = strcasecmp($primaryAvailability, $option) === 0; ?>
                            <input class="btn-check" type="radio" name="availability_status" id="availability-<?= strtolower($option) ?>" value="<?= htmlspecialchars($option) ?>" <?= $checked ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="availability-<?= strtolower($option) ?>">
                                <?= htmlspecialchars($option) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <?= smsIcon('save', ['class' => 'me-1']) ?>Save Availability
                    </button>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!in_array($mode, ['approved-research', 'profile', 'profile-expertise', 'profile-availability'], true)): ?>
    <section class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold"><?= htmlspecialchars($mode === 'overview' ? 'Research Overview' : $title) ?></h2>
            <small class="text-muted">Synced <?= date('M j, Y g:i:s A') ?></small>
        </div>
        <div class="card-body">
            <?php renderFacultyResearchList($assignments); ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($mode === 'approved-research'): ?>
    <?php
    $titleInboxRows = facultyTitleApprovalsInbox();
    $titlePending   = count(array_filter($titleInboxRows, fn($r) => $r['status'] === 'Pending'));
    $inboxEndpoint  = BASE_URL . '/modules/faculty/pages/approved-research.php?faculty_ajax=title-inbox';
    $updateEndpoint = BASE_URL . '/modules/faculty/pages/approved-research.php?faculty_ajax=title-status';
    ?>
    <section class="card mt-3"
        data-ta-inbox="<?= htmlspecialchars($inboxEndpoint) ?>"
        data-ta-update="<?= htmlspecialchars($updateEndpoint) ?>">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold">
                <?= smsIcon('inbox', ['class' => 'me-1 text-primary']) ?>
                Title Approval Submissions
                <?php if ($titlePending > 0): ?>
                    <span class="badge bg-warning text-dark ms-1" id="taPendingBadge"><?= $titlePending ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary ms-1" id="taPendingBadge" <?= $titlePending === 0 ? 'style="display:none"' : '' ?>><?= $titlePending ?></span>
                <?php endif; ?>
            </h2>
            <small class="text-muted" id="taLastSync">Synced <?= date('M j, Y g:i:s A') ?></small>
        </div>
        <div class="card-body p-0">
            <?php if (!$titleInboxRows): ?>
            <div class="faculty-empty" id="taEmpty">
                <?= smsIcon('paper-plane', ['class' => 'fa-2x mb-3 d-block', 'style' => 'color:var(--sms-text-muted);']) ?>
                <strong>No title approval submissions yet.</strong>
                <div class="mt-1">Student submissions sent to you will appear here in real time.</div>
            </div>
            <?php else: ?>
            <div class="faculty-empty" id="taEmpty" style="display:none">
                <?= smsIcon('paper-plane', ['class' => 'fa-2x mb-3 d-block', 'style' => 'color:var(--sms-text-muted);']) ?>
                <strong>No title approval submissions yet.</strong>
                <div class="mt-1">Student submissions sent to you will appear here in real time.</div>
            </div>
            <?php endif; ?>
            <div style="overflow-x:auto">
                <table class="table table-sm mb-0" style="min-width:800px" id="taTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:35%">Research Title</th>
                            <th>Student</th>
                            <th>Department</th>
                            <th>Date Sent</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="taRows">
                        <?php foreach ($titleInboxRows as $taRow): ?>
                        <tr data-ta-id="<?= (int)$taRow['id'] ?>">
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars((string)$taRow['proposed_title']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars((string)$taRow['discipline_cluster']) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars((string)$taRow['student_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars((string)$taRow['student_id']) ?></small>
                            </td>
                            <td><small><?= htmlspecialchars((string)$taRow['department']) ?></small></td>
                            <td><small><?= htmlspecialchars(date('M j, Y', strtotime((string)$taRow['sent_at']))) ?></small></td>
                            <td><?php
                                $s = (string)$taRow['status'];
                                $sc = match($s){
                                    'Pending'  => 'warning text-dark',
                                    'Approved' => 'success',
                                    'Returned' => 'danger',
                                    default    => 'secondary'
                                };
                            ?><span class="badge bg-<?= $sc ?>"><?= htmlspecialchars($s) ?></span></td>
                            <td>
                                <button class="btn btn-primary btn-sm ta-open"
                                    style="font-size:.75rem"
                                    data-ta-row="<?= htmlspecialchars(json_encode($taRow, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS)) ?>">
                                    <?= smsIcon('folder-open', ['class' => 'me-1']) ?>Open
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Title Approval Form modal -->
    <div id="taModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);overflow-y:auto;padding:2rem 1rem;">
        <div style="max-width:780px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.35);">
            <!-- Modal header -->
            <div style="background:linear-gradient(135deg,#1e3a8a 0%,#2563eb 55%,#1d4ed8 100%);padding:1.1rem 1.4rem;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="color:rgba(226,232,240,.78);font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.25rem;">CRAD FORM S2 V3</div>
                    <h2 style="margin:0;color:#fff;font-size:1.15rem;font-weight:800;">Title Approval Form</h2>
                </div>
                <button id="taModalClose" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:8px;padding:.4rem .85rem;cursor:pointer;font-weight:700;font-size:.88rem;">
                    <?= smsIcon('times', ['class' => 'me-1']) ?>Close
                </button>
            </div>

            <!-- Modal body â€” the print-sheet recreated in screen view -->
            <div id="taModalBody" style="padding:1.5rem;font-family:Arial,Helvetica,sans-serif;color:#111;font-size:9pt;"></div>

            <!-- Modal footer actions -->
            <div id="taModalFooter" style="padding:1rem 1.4rem;border-top:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;gap:.75rem;background:#f8fafc;">
                <div id="taModalStatus" style="font-size:.82rem;font-weight:700;color:#475569;"></div>
                <div id="taModalActions" style="display:flex;gap:.6rem;"></div>
            </div>
        </div>
    </div>

    <div id="taReturnModal" style="display:none;position:fixed;inset:0;z-index:10001;background:rgba(15,23,42,.68);overflow-y:auto;padding:2rem 1rem;">
        <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.45);">
            <div style="background:#991b1b;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <div>
                    <div style="color:rgba(254,226,226,.86);font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem;">Title Approval</div>
                    <h3 style="margin:0;color:#fff;font-size:1.05rem;font-weight:800;"><?= smsIcon('undo', ['class' => 'me-2']) ?>Return Submission</h3>
                </div>
                <button id="taReturnClose" type="button" style="background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.32);color:#fff;border-radius:8px;padding:.35rem .75rem;cursor:pointer;font-weight:700;">
                    <?= smsIcon('times') ?>
                </button>
            </div>
            <div style="padding:1.2rem;">
                <label for="taReturnRemarks" style="display:block;font-size:.78rem;font-weight:800;text-transform:uppercase;color:#475569;margin-bottom:.45rem;">Return remarks (optional)</label>
                <textarea id="taReturnRemarks" rows="5" style="width:100%;resize:vertical;border:1px solid #cbd5e1;border-radius:10px;padding:.8rem;font-size:.92rem;outline:none;" placeholder="Write what the student needs to fix before resubmitting."></textarea>
                <div style="margin-top:.7rem;color:#64748b;font-size:.82rem;font-weight:600;">The student will receive this in their portal notifications in real time.</div>
            </div>
            <div style="padding:.9rem 1.2rem;border-top:1px solid #e5e7eb;background:#f8fafc;display:flex;justify-content:flex-end;gap:.65rem;">
                <button id="taReturnCancel" type="button" class="btn btn-outline-secondary btn-sm" style="font-size:.82rem;">Cancel</button>
                <button id="taReturnConfirm" type="button" class="btn btn-danger btn-sm" style="font-size:.82rem;"><?= smsIcon('undo', ['class' => 'me-1']) ?>Return</button>
            </div>
        </div>
    </div>

    <div id="taReturnConfirmModal" style="display:none;position:fixed;inset:0;z-index:10002;background:rgba(15,23,42,.72);overflow-y:auto;padding:2rem 1rem;">
        <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.45);">
            <div style="background:#991b1b;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <div>
                    <div style="color:rgba(254,226,226,.86);font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem;">Title Approval</div>
                    <h3 style="margin:0;color:#fff;font-size:1.05rem;font-weight:800;"><?= smsIcon('exclamation-triangle', ['class' => 'me-2']) ?>Confirm Return</h3>
                </div>
                <button id="taReturnConfirmClose" type="button" style="background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.32);color:#fff;border-radius:8px;padding:.35rem .75rem;cursor:pointer;font-weight:700;">
                    <?= smsIcon('times') ?>
                </button>
            </div>
            <div style="padding:1.2rem;">
                <p style="margin:0 0 .75rem;color:#111827;font-size:1rem;font-weight:800;">Are you sure you want to return this submission?</p>
                <p style="margin:0;color:#64748b;font-size:.9rem;line-height:1.55;font-weight:600;">
                    This action will return the Title Approval submission to the student for revision. If return remarks were entered, they will be sent with the returned status.
                </p>
            </div>
            <div style="padding:.9rem 1.2rem;border-top:1px solid #e5e7eb;background:#f8fafc;display:flex;justify-content:flex-end;gap:.65rem;">
                <button id="taReturnConfirmCancel" type="button" class="btn btn-outline-secondary btn-sm" style="font-size:.82rem;">Cancel</button>
                <button id="taReturnConfirmYes" type="button" class="btn btn-danger btn-sm" style="font-size:.82rem;"><?= smsIcon('undo', ['class' => 'me-1']) ?>Yes, Return Submission</button>
            </div>
        </div>
    </div>

    <div id="taApproveConfirmModal" style="display:none;position:fixed;inset:0;z-index:10003;background:rgba(15,23,42,.72);overflow-y:auto;padding:2rem 1rem;">
        <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.45);">
            <div style="background:#17366f;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <div>
                    <div style="color:rgba(219,234,254,.9);font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem;">TITLE APPROVAL</div>
                    <h3 style="margin:0;color:#fff;font-size:1.05rem;font-weight:800;"><?= smsIcon('check-circle', ['class' => 'me-2']) ?>Confirm Approval</h3>
                </div>
                <button id="taApproveConfirmClose" type="button" style="background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.32);color:#fff;border-radius:8px;padding:.35rem .75rem;cursor:pointer;font-weight:700;">
                    <?= smsIcon('times') ?>
                </button>
            </div>
            <div style="padding:1.2rem;">
                <p style="margin:0 0 .75rem;color:#111827;font-size:1rem;font-weight:800;">Are you sure you want to approve this Title Approval Form?</p>
                <p style="margin:0 0 .55rem;color:#64748b;font-size:.9rem;line-height:1.55;font-weight:600;">Your signature will be saved and this approval will be recorded.</p>
                <p style="margin:0;color:#64748b;font-size:.9rem;line-height:1.55;font-weight:600;">Once confirmed, the existing Title Approval process will continue.</p>
            </div>
            <div style="padding:.9rem 1.2rem;border-top:1px solid #e5e7eb;background:#f8fafc;display:flex;justify-content:flex-end;gap:.65rem;">
                <button id="taApproveConfirmCancel" type="button" class="btn btn-outline-secondary btn-sm" style="font-size:.82rem;">Cancel</button>
                <button id="taApproveConfirmYes" type="button" class="btn btn-success btn-sm" style="font-size:.82rem;"><?= smsIcon('check', ['class' => 'me-1']) ?>Yes, Approve</button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var card      = document.querySelector('[data-ta-inbox]');
        if (!card) return;
        var endpoint  = card.dataset.taInbox;
        var updateUrl = card.dataset.taUpdate;
        var tbody     = document.getElementById('taRows');
        var empty     = document.getElementById('taEmpty');
        var table     = document.getElementById('taTable');
        var badge     = document.getElementById('taPendingBadge');
        var lastSync  = document.getElementById('taLastSync');
        var modal     = document.getElementById('taModal');
        var modalBody = document.getElementById('taModalBody');
        var modalStat = document.getElementById('taModalStatus');
        var modalActs = document.getElementById('taModalActions');
        var returnModal = document.getElementById('taReturnModal');
        var returnRemarks = document.getElementById('taReturnRemarks');
        var returnConfirm = document.getElementById('taReturnConfirm');
        var returnConfirmModal = document.getElementById('taReturnConfirmModal');
        var returnConfirmYes = document.getElementById('taReturnConfirmYes');
        var pendingReturnRow = null;
        var returnRequestInFlight = false;
        var knownIds  = new Set(Array.from(tbody.querySelectorAll('tr[data-ta-id]')).map(function(r){ return r.dataset.taId; }));
        var currentRow = null;

        /* Seed the full-row cache from PHP-rendered rows (signature included via poll) */
        var fullRowCache = {};
        function cacheRows(rows) {
            rows.forEach(function(r) { fullRowCache[String(r.id)] = r; });
        }
        /* Pre-seed from existing button data (no signature yet on first load, but IDs are set) */
        tbody.querySelectorAll('.ta-open').forEach(function(btn) {
            try {
                var r = JSON.parse(btn.dataset.taRow.replace(/&quot;/g,'"'));
                if (r && r.id) { fullRowCache[String(r.id)] = r; }
            } catch(e) {}
        });

        function esc(v){ return String(v == null ? '' : v).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]; }); }
        function fmtDate(v){ if(!v) return '---'; var d = new Date(String(v).replace(' ','T')); return isNaN(d.getTime()) ? v : d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}); }
        function badgeCls(s){ return {Pending:'warning text-dark',Approved:'success',Returned:'danger',Reviewed:'secondary'}[s] || 'secondary'; }

        /* Row HTML - Open button only (strip heavy signature data from attribute) */
        function rowHtml(r, isNew) {
            /* Store a lightweight copy — omit the base64 signature to keep the
               data attribute small. The full row (with signature) is fetched on open. */
            var lightRow = {};
            for (var k in r) {
                if (k !== 'adviser_signature_data') { lightRow[k] = r[k]; }
            }
            var rowJson = JSON.stringify(lightRow).replace(/\\/g,'\\\\').replace(/"/g,'&quot;');
            return '<tr data-ta-id="'+esc(r.id)+'"'+(isNew?' class="table-info"':'')+'>'+
                '<td><div class="fw-bold">'+esc(r.proposed_title)+'</div><small class="text-muted">'+esc(r.discipline_cluster||'')+'</small></td>'+
                '<td><div class="fw-semibold">'+esc(r.student_name)+'</div><small class="text-muted">'+esc(r.student_id)+'</small></td>'+
                '<td><small>'+esc(r.department)+'</small></td>'+
                '<td><small>'+esc(fmtDate(r.sent_at))+'</small></td>'+
                '<td><span class="badge bg-'+badgeCls(r.status)+'">'+esc(r.status)+'</span></td>'+
                '<td><button class="btn btn-primary btn-sm ta-open" style="font-size:.75rem" data-ta-id-open="'+esc(r.id)+'" data-ta-row="'+rowJson+'"><?= smsIcon('folder-open', ['class' => 'me-1']) ?>Open</button></td>'+
                '</tr>';
        }


        function updateBadge(count) {
            if (!badge) return;
            badge.textContent = count;
            badge.style.display = count > 0 ? '' : 'none';
            badge.className = 'badge ms-1 ' + (count > 0 ? 'bg-warning text-dark' : 'bg-secondary');
        }

        function parseMembers(json) {
            try { return JSON.parse(json) || []; } catch(e) { return []; }
        }

        /* Build the Title Approval Form inside the modal */
        function buildFormHtml(r) {
            var members = parseMembers(r.members_json || '[]');
            var memberRows = '';
            for (var i = 0; i < 6; i++) {
                var m = members[i] || [];
                memberRows += '<tr>'+
                    '<td style="text-align:center;border:0.8px solid #222;padding:2mm 1.4mm;">'+(i+1)+'</td>'+
                    '<td style="border:0.8px solid #222;padding:2mm 1.4mm;font-weight:700;">'+esc(m[0]||'')+'</td>'+
                    '<td style="border:0.8px solid #222;padding:2mm 1.4mm;">'+esc(m[1]||'')+'</td>'+
                    '<td style="border:0.8px solid #222;padding:2mm 1.4mm;">'+esc(m[2]||'')+'</td>'+
                    '</tr>';
            }
            var base = window.location.origin + <?= json_encode(BASE_URL) ?>;
            return '<div style="font-family:Arial,Helvetica,sans-serif;color:#111;font-size:9pt;line-height:1.45;">'+

            /* Header */
            '<div style="display:grid;grid-template-columns:15mm 1fr auto;align-items:center;gap:3mm;padding-bottom:2.5mm;border-bottom:2px solid #17366f;margin-bottom:4mm;">'+
            '<div style="width:42px;height:42px;display:flex;align-items:center;justify-content:center;background:transparent;">'+
            '<img src="'+base+'/images/bcp-crest.png?v=20260811" style="width:42px;height:42px;object-fit:contain;border-radius:0;background:transparent;" onerror="this.style.display=\'none\'">'+
            '</div>'+
            '<div style="font-size:7pt;line-height:1.4;">'+
            '<strong style="display:block;font-size:10pt;color:#17366f;">BESTLINK COLLEGE OF THE PHILIPPINES</strong>'+
            '<span>#1071 Brgy. Kaligayahan, Quirino Highway, Novaliches, Quezon City</span><br>'+
            '<b style="font-size:8pt;">CENTER FOR RESEARCH AND DEVELOPMENT</b>'+
            '</div>'+
            '<div style="font-size:7pt;font-weight:700;padding:3px 8px;border:1px solid #c3cede;border-radius:4px;background:#edf4ff;color:#17366f;white-space:nowrap;">CRAD Form S2 V3</div>'+
            '</div>'+

            /* Doc title */
            '<h2 style="text-align:center;font-size:13pt;font-weight:800;color:#17366f;letter-spacing:.05em;margin:0 0 4mm;text-decoration:underline;text-transform:uppercase;">TITLE APPROVAL FORM</h2>'+

            /* Date + Department */
            '<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:6mm;font-size:8.5pt;margin-bottom:4mm;padding:3mm 4mm;border:1px solid #b9c7da;border-radius:4px;background:#f8fbff;">'+
            '<div><strong style="color:#17366f;">Date:</strong> '+esc(r.submission_date||'')+'</div>'+
            '<div><strong style="color:#17366f;">I. Department:</strong> '+esc(r.department||'')+'</div>'+
            '</div>'+

            /* Students table */
            '<div style="margin-bottom:4mm;">'+
            '<div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:3px 6px;margin-bottom:2mm;">II. Students Information</div>'+
            '<table style="width:100%;border-collapse:collapse;font-size:8pt;">'+
            '<thead><tr>'+
            '<th style="border:0.8px solid #222;background:#e4edf9;color:#17366f;padding:2mm 1.4mm;text-align:center;width:6%;">No.</th>'+
            '<th style="border:0.8px solid #222;background:#e4edf9;color:#17366f;padding:2mm 1.4mm;">Name (Last, First, M.I.)</th>'+
            '<th style="border:0.8px solid #222;background:#e4edf9;color:#17366f;padding:2mm 1.4mm;width:20%;">Section</th>'+
            '<th style="border:0.8px solid #222;background:#e4edf9;color:#17366f;padding:2mm 1.4mm;width:25%;">Research Forum OR No.</th>'+
            '</tr></thead><tbody>'+memberRows+'</tbody></table></div>'+

            /* Discipline + SDG */
            '<div style="display:grid;grid-template-columns:1fr 1fr;gap:3mm;margin-bottom:4mm;">'+
            '<div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:7.5pt;">'+
            '<div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">III. Research Discipline Cluster</div>'+
            (r.discipline_cluster ? '<div style="background:#dceaff;color:#12366f;padding:2px 5px;border-radius:3px;font-weight:700;">&#x2713; '+esc(r.discipline_cluster)+'</div>' : '<div style="color:#888;">None selected</div>')+
            '</div>'+
            '<div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:7.5pt;">'+
            '<div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">IV. SDG Alignment</div>'+
            (r.primary_sdg ? '<div style="background:#dceaff;color:#12366f;padding:2px 5px;border-radius:3px;font-weight:700;">&#x2713; '+esc(r.primary_sdg)+'</div>' : '<div style="color:#888;">None selected</div>')+
            '</div>'+
            '</div>'+

            /* Research Agenda */
            '<div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:7.5pt;margin-bottom:4mm;">'+
            '<div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">V. Institutional Research Agenda Alignment</div>'+
            (r.research_agenda ? '<div style="background:#dceaff;color:#12366f;padding:2px 5px;border-radius:3px;font-weight:700;">&#x2713; '+esc(r.research_agenda)+'</div>' : '<div style="color:#888;">None selected</div>')+
            '</div>'+

            /* Title */
            '<div style="margin-bottom:4mm;">'+
            '<div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:3px 6px;margin-bottom:2mm;">VI. Proposed Research Title</div>'+
            '<div style="padding:3mm 5mm;border:1px solid #b9c7da;border-left:3px solid #2457a7;border-radius:4px;background:#fbfdff;font-size:10pt;font-weight:800;text-align:center;color:#12294d;min-height:14mm;">'+esc(r.proposed_title||'')+'</div>'+
            '</div>'+

            /* Justification */
            '<div style="margin-bottom:4mm;">'+
            '<div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:3px 6px;margin-bottom:2mm;">VII. SDG Justification</div>'+
            '<div style="padding:3mm 5mm;border:1px solid #b9c7da;border-left:3px solid #2457a7;border-radius:4px;background:#fbfdff;min-height:14mm;color:#24364f;">'+esc(r.sdg_justification||'')+'</div>'+
            '</div>'+

            /* Approval signatures — Section IX shows saved sig image if present */
            '<div style="display:grid;grid-template-columns:1fr 1fr;gap:3mm;margin-bottom:2mm;align-items:start;">'+
            '<div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:8pt;align-self:start;height:auto;min-height:0;">'+
            '<div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;">VIII. Coordinator Screening</div>'+
            '<table style="width:100%;border-collapse:collapse;font-size:7.5pt;">'+
            '<thead><tr><th style="border:0.8px solid #222;padding:2mm;background:#e4edf9;">Criteria</th><th style="border:0.8px solid #222;padding:2mm;background:#e4edf9;width:12%;">Yes</th><th style="border:0.8px solid #222;padding:2mm;background:#e4edf9;width:12%;">No</th></tr></thead>'+
            '<tbody>'+
            '<tr><td style="border:0.8px solid #222;padding:2mm;">Title aligns with institutional research agenda</td><td style="border:0.8px solid #222;padding:2mm;"></td><td style="border:0.8px solid #222;padding:2mm;"></td></tr>'+
            '<tr><td style="border:0.8px solid #222;padding:2mm;">Proposed study is feasible and original</td><td style="border:0.8px solid #222;padding:2mm;"></td><td style="border:0.8px solid #222;padding:2mm;"></td></tr>'+
            '<tr><td style="border:0.8px solid #222;padding:2mm;">Ethical and SDG requirements are satisfied</td><td style="border:0.8px solid #222;padding:2mm;"></td><td style="border:0.8px solid #222;padding:2mm;"></td></tr>'+
            '</tbody></table>'+
            '</div>'+
            '<div style="padding:3mm;border:1px solid #8998ab;border-radius:4px;font-size:8pt;text-align:center;">'+
            '<div style="font-size:8pt;font-weight:800;background:#17366f;color:#fff;padding:2px 6px;margin:-3mm -3mm 3mm;border-radius:3px 3px 0 0;text-align:left;">IX. Approval (Name, signature and date)</div>'+

            /* ── Adviser signature block ── */
            '<div style="position:relative;width:80%;margin:4mm auto 0;height:54px;">'+
            /* the signature line is always visible underneath */
            '<div style="position:absolute;bottom:0;left:0;right:0;border-bottom:1px solid #111;"></div>'+
            /* saved signature image floats above the line */
            (r.adviser_signature_data
                ? '<img src="'+r.adviser_signature_data+'" style="position:absolute;bottom:2px;left:0;right:0;width:100%;height:50px;object-fit:contain;object-position:center bottom;">'
                : '')+
            '</div>'+
            '<strong style="display:block;font-size:8.5pt;margin-top:1mm;">'+esc(r.adviser_name||'')+'</strong>'+
            '<span style="font-size:7.5pt;color:#555;">Research Adviser</span>'+
            '<div style="margin:5mm 0 2mm;border-bottom:1px solid #111;width:80%;margin-left:auto;margin-right:auto;"></div>'+
            '<strong style="display:block;font-size:8.5pt;">'+esc(r.coordinator_name||'')+'</strong>'+
            '<span style="font-size:7.5pt;color:#555;">Program Research Coordinator</span>'+
            '<div style="margin:5mm 0 2mm;border-top:1px dashed #7c8da5;padding-top:3mm;text-align:left;font-size:7pt;color:#475569;">Received:</div>'+
            '<div style="border-bottom:1px solid #111;width:80%;margin:2mm auto;"></div>'+
            '<strong style="display:block;font-size:8.5pt;">Center for Research and Development</strong>'+
            '<span style="font-size:7.5pt;color:#555;">Center for Research and Development Office</span>'+
            '</div>'+
            '</div>'+

            (r.adviser_remarks ? '<div style="margin-top:3mm;padding:3mm 4mm;background:#fef9ec;border:1px solid #fbbf24;border-radius:4px;font-size:8pt;"><strong>Adviser Remarks:</strong> '+esc(r.adviser_remarks)+'</div>' : '')+
            '</div>';
        }

        /* Open modal */
        function openModal(r) {
            currentRow = r;
            modalBody.innerHTML = buildFormHtml(r);

            /* Status */
            var sc = {Pending:'#92400e',Approved:'#065f46',Returned:'#991b1b',Reviewed:'#1e40af'}[r.status] || '#475569';
            modalStat.innerHTML = '<span style="color:'+sc+';font-weight:800;">&#9679; '+esc(r.status)+'</span>'+(r.adviser_remarks?' &middot; <em>'+esc(r.adviser_remarks)+'</em>':'');

            /* Action buttons */
            if (r.status === 'Pending') {
                modalActs.innerHTML =
                    '<button id="taModalApprove" class="btn btn-success btn-sm" style="font-size:.82rem"><?= smsIcon('check', ['class' => 'me-1']) ?>Approve</button>'+
                    '<button id="taModalReturn" class="btn btn-outline-danger btn-sm" style="font-size:.82rem"><?= smsIcon('undo', ['class' => 'me-1']) ?>Return</button>';
                document.getElementById('taModalApprove').addEventListener('click', function(){ openSigModal(r); });
                document.getElementById('taModalReturn').addEventListener('click', function(){
                    openReturnModal(r);
                });
            } else {
                modalActs.innerHTML = '';
            }

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            currentRow = null;
        }

        function doAction(id, status, remarks, signatureData) {
            return fetch(updateUrl, {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'application/json'},
                credentials: 'same-origin',
                body: JSON.stringify({id: parseInt(id), status: status, remarks: remarks, adviser_signature_data: signatureData || ''})
            })
            .then(function(res){
                if (!res.ok) { throw new Error('Server returned ' + res.status); }
                return res.json();
            })
            .then(function(data){
                if (!data.ok) {
                    alert('Error: ' + (data.error || 'Could not update status.'));
                    return data;
                }

                /* Update currentRow in-place so the open modal immediately reflects
                   the new status + signature without needing to close and re-open */
                if (currentRow && String(currentRow.id) === String(id)) {
                    currentRow.status = status;
                    currentRow.adviser_remarks = remarks || currentRow.adviser_remarks;
                    if (signatureData) { currentRow.adviser_signature_data = signatureData; }
                    /* Re-render the modal body and footer with the updated row */
                    modalBody.innerHTML = buildFormHtml(currentRow);
                    var sc = {Pending:'#92400e',Approved:'#065f46',Returned:'#991b1b',Reviewed:'#1e40af'}[status] || '#475569';
                    modalStat.innerHTML = '<span style="color:'+sc+';font-weight:800;">&#9679; '+esc(status)+'</span>'+(remarks?' &middot; <em>'+esc(remarks)+'</em>':'');
                    modalActs.innerHTML = '';
                } else {
                    closeModal();
                }
                refresh();
                return data;
            })
            .catch(function(err){
                alert('Could not save: ' + err.message);
                return {ok:false, error: err.message};
            });
        }

        /* Bind Open buttons */
        function bindOpen() {
            tbody.querySelectorAll('.ta-open').forEach(function(btn){
                btn.addEventListener('click', function(){
                    var id  = btn.dataset.taIdOpen;
                    /* Use full cached row (includes adviser_signature_data) if available */
                    var row = id && fullRowCache[String(id)]
                        ? fullRowCache[String(id)]
                        : null;
                    if (!row) {
                        try { row = JSON.parse(btn.dataset.taRow.replace(/&quot;/g,'"')); } catch(e){ console.error(e); return; }
                    }
                    openModal(row);
                });
            });
        }

        /* Modal close */
        document.getElementById('taModalClose').addEventListener('click', closeModal);
        modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', function(e){
            if (e.key !== 'Escape') return;
            if (returnConfirmModal.style.display === 'block') {
                cancelReturnConfirm();
                return;
            }
            if (returnModal.style.display === 'block') {
                closeReturnModal();
                return;
            }
            closeModal();
        });

        function openReturnModal(r) {
            pendingReturnRow = r;
            returnRemarks.value = r.adviser_remarks || '';
            returnModal.style.display = 'block';
            setTimeout(function(){ returnRemarks.focus(); }, 30);
        }

        function closeReturnModal() {
            returnModal.style.display = 'none';
            pendingReturnRow = null;
            returnRemarks.value = '';
        }

        function openReturnConfirmModal() {
            if (!pendingReturnRow || returnRequestInFlight) return;
            returnModal.style.display = 'none';
            returnConfirmModal.style.display = 'block';
            setTimeout(function(){ returnConfirmYes.focus(); }, 30);
        }

        function cancelReturnConfirm() {
            if (returnRequestInFlight) return;
            returnConfirmModal.style.display = 'none';
            returnModal.style.display = 'block';
            setTimeout(function(){ returnRemarks.focus(); }, 30);
        }

        function resetReturnConfirmButton() {
            returnRequestInFlight = false;
            returnConfirmYes.disabled = false;
            returnConfirmYes.innerHTML = '<?= smsIcon('undo', ['class' => 'me-1']) ?>Yes, Return Submission';
        }

        document.getElementById('taReturnClose').addEventListener('click', closeReturnModal);
        document.getElementById('taReturnCancel').addEventListener('click', closeReturnModal);
        returnModal.addEventListener('click', function(e){ if (e.target === returnModal) closeReturnModal(); });
        returnConfirm.addEventListener('click', openReturnConfirmModal);
        document.getElementById('taReturnConfirmClose').addEventListener('click', cancelReturnConfirm);
        document.getElementById('taReturnConfirmCancel').addEventListener('click', cancelReturnConfirm);
        returnConfirmModal.addEventListener('click', function(e){ if (e.target === returnConfirmModal) cancelReturnConfirm(); });
        returnConfirmYes.addEventListener('click', function(){
            if (!pendingReturnRow || returnRequestInFlight) return;
            returnRequestInFlight = true;
            returnConfirmYes.disabled = true;
            returnConfirmYes.innerHTML = '<?= smsIcon('spinner', ['class' => 'fa-spin me-1']) ?>Processing...';

            doAction(pendingReturnRow.id, 'Returned', returnRemarks.value.trim(), '').then(function(data){
                if (data && data.ok) {
                    returnConfirmModal.style.display = 'none';
                    closeReturnModal();
                }
                resetReturnConfirmButton();
            });
        });

        /* Refresh every 5s */
        function refresh() {
            fetch(endpoint, {headers:{'Accept':'application/json'}, cache:'no-store', credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (!data.ok) return;
                var rows = Array.isArray(data.rows) ? data.rows : [];
                var newIds = new Set(rows.map(function(r){ return String(r.id); }));
                var added  = rows.filter(function(r){ return !knownIds.has(String(r.id)); });
                knownIds   = newIds;
                /* Cache full rows (including adviser_signature_data) */
                cacheRows(rows);
                tbody.innerHTML = rows.map(function(r){
                    return rowHtml(r, added.some(function(a){ return String(a.id) === String(r.id); }));
                }).join('');
                bindOpen();
                /* Update open modal if status or signature changed */
                if (currentRow) {
                    var updated = rows.find(function(r){ return String(r.id) === String(currentRow.id); });
                    if (updated && (updated.status !== currentRow.status ||
                        (updated.adviser_signature_data && updated.adviser_signature_data !== currentRow.adviser_signature_data))) {
                        openModal(updated);
                    }
                }
                var isEmpty = rows.length === 0;
                empty.style.display = isEmpty ? '' : 'none';
                table.style.display = isEmpty ? 'none' : '';
                updateBadge(data.pending || 0);
                lastSync.textContent = 'Synced ' + (data.last_sync || 'just now');
            })
            .catch(function(){ lastSync.textContent = 'Sync paused'; });
        }

        /* Open the signature pad modal before approving */
        function openSigModal(r) {
            if (typeof window._taSigOpen === 'function') {
                window._taSigOpen(r);
            } else {
                /* Fallback if sig modal not loaded — approve without signature */
                doAction(r.id, 'Approved', '', '');
            }
        }

        /* Listen for the confirm-approve event fired by the signature pad IIFE */
        document.addEventListener('ta-do-approve', function (e) {
            var r   = e.detail.row;
            var sig = e.detail.sig;
            doAction(r.id, 'Approved', '', sig);
        });

        bindOpen();
        if (!tbody.querySelector('tr')) { if (table) table.style.display = 'none'; }
        window.setInterval(refresh, 5000);
    })();
    </script>

    <!-- ── Signature pad modal (shown when adviser clicks Approve) ── -->
    <div id="taSigModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.65);overflow-y:auto;padding:2rem 1rem;">
        <div style="max-width:500px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.45);">
            <div style="background:linear-gradient(135deg,#065f46 0%,#047857 55%,#059669 100%);padding:1rem 1.4rem;display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <div style="color:rgba(209,250,229,.8);font-size:.7rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.2rem;">Title Approval</div>
                    <h3 style="margin:0;color:#fff;font-size:1.05rem;font-weight:800;"><?= smsIcon('signature', ['class' => 'me-2']) ?>Draw Your Signature</h3>
                </div>
                <button id="taSigModalClose" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;border-radius:8px;padding:.35rem .8rem;cursor:pointer;font-weight:700;font-size:.82rem;">
                    <?= smsIcon('times') ?>
                </button>
            </div>
            <div style="padding:1.25rem;">
                <p style="margin:0 0 .75rem;font-size:.82rem;color:#374151;font-weight:600;">
                    Sign in the box below. Your signature will be saved to the Title Approval Form.
                </p>
                <div style="border:2px solid #d1d5db;border-radius:8px;background:#f9fafb;position:relative;overflow:hidden;">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:.4rem .75rem;background:#f3f4f6;border-bottom:1px solid #e5e7eb;">
                        <span style="font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;">Adviser Signature Pad (Draw Below)</span>
                        <button id="taSigClearBtn" style="background:none;border:none;color:#7c3aed;font-size:.75rem;font-weight:800;cursor:pointer;padding:0;">Clear Pad</button>
                    </div>
                    <canvas id="taSigCanvas" style="display:block;width:100%;height:160px;cursor:crosshair;touch-action:none;"></canvas>
                </div>
                <div id="taSigError" style="display:none;margin-top:.6rem;padding:.5rem .75rem;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;color:#991b1b;font-size:.8rem;font-weight:700;">
                    <?= smsIcon('exclamation-circle', ['class' => 'me-1']) ?>Please provide your signature before approving.
                </div>
            </div>
            <div style="padding:.85rem 1.25rem;border-top:1px solid #e5e7eb;display:flex;align-items:center;justify-content:flex-end;gap:.65rem;background:#f9fafb;">
                <button id="taSigCancelBtn" class="btn btn-outline-secondary btn-sm" style="font-size:.82rem;">Cancel</button>
                <button id="taSigConfirmBtn" class="btn btn-success btn-sm" style="font-size:.82rem;">
                    <?= smsIcon('check', ['class' => 'me-1']) ?>Confirm &amp; Approve
                </button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var sigModal   = document.getElementById('taSigModal');
        var canvas     = document.getElementById('taSigCanvas');
        var clearBtn   = document.getElementById('taSigClearBtn');
        var cancelBtn  = document.getElementById('taSigCancelBtn');
        var confirmBtn = document.getElementById('taSigConfirmBtn');
        var closeBtn   = document.getElementById('taSigModalClose');
        var errBox     = document.getElementById('taSigError');
        var approveConfirmModal = document.getElementById('taApproveConfirmModal');
        var approveConfirmYes = document.getElementById('taApproveConfirmYes');
        if (!sigModal || !canvas) return;

        var ctx = canvas.getContext('2d');
        var drawing = false;
        var pendingRow = null;  /* the title_approvals row awaiting approval */
        var pendingSigData = '';
        var approveRequestInFlight = false;

        /* ── Canvas setup ── */
        function resizeCanvas() {
            var ratio  = Math.max(window.devicePixelRatio || 1, 1);
            var rect   = canvas.getBoundingClientRect();
            var w      = Math.floor(rect.width  * ratio);
            var h      = Math.floor(160         * ratio);
            canvas.width  = w;
            canvas.height = h;
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            applyStroke();
        }

        function applyStroke() {
            ctx.strokeStyle = '#0f172a';
            ctx.lineWidth   = 2;
            ctx.lineCap     = 'round';
            ctx.lineJoin    = 'round';
        }

        function pos(e) {
            var rect = canvas.getBoundingClientRect();
            var pt   = e.touches ? e.touches[0] : e;
            return { x: pt.clientX - rect.left, y: pt.clientY - rect.top };
        }

        function hasDrawing() {
            var px = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
            for (var i = 3; i < px.length; i += 4) { if (px[i] > 0) return true; }
            return false;
        }

        canvas.addEventListener('mousedown',  function (e) { applyStroke(); drawing = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); });
        canvas.addEventListener('mousemove',  function (e) { if (!drawing) return; var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); });
        canvas.addEventListener('mouseup',    function ()  { drawing = false; });
        canvas.addEventListener('mouseleave', function ()  { drawing = false; });

        canvas.addEventListener('touchstart', function (e) { e.preventDefault(); applyStroke(); drawing = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }, {passive:false});
        canvas.addEventListener('touchmove',  function (e) { e.preventDefault(); if (!drawing) return; var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); }, {passive:false});
        canvas.addEventListener('touchend',   function ()  { drawing = false; });

        clearBtn.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            errBox.style.display = 'none';
        });

        function closeSigModal() {
            if (approveRequestInFlight) return;
            sigModal.style.display = 'none';
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            errBox.style.display = 'none';
            pendingRow = null;
            pendingSigData = '';
        }

        closeBtn.addEventListener('click',  closeSigModal);
        cancelBtn.addEventListener('click', closeSigModal);
        sigModal.addEventListener('click',  function (e) { if (e.target === sigModal) closeSigModal(); });

        function resetApproveConfirmButton() {
            approveRequestInFlight = false;
            approveConfirmYes.disabled = false;
            approveConfirmYes.innerHTML = '<?= smsIcon('check', ['class' => 'me-1']) ?>Yes, Approve';
        }

        function closeApproveConfirmModal() {
            if (approveRequestInFlight) return;
            approveConfirmModal.style.display = 'none';
        }

        document.getElementById('taApproveConfirmClose').addEventListener('click', closeApproveConfirmModal);
        document.getElementById('taApproveConfirmCancel').addEventListener('click', closeApproveConfirmModal);
        approveConfirmModal.addEventListener('click', function (e) { if (e.target === approveConfirmModal) closeApproveConfirmModal(); });
        approveConfirmYes.addEventListener('click', function () {
            if (!pendingRow || !pendingSigData || approveRequestInFlight) return;
            approveRequestInFlight = true;
            approveConfirmYes.disabled = true;
            approveConfirmYes.innerHTML = 'Processing...';
            document.dispatchEvent(new CustomEvent('ta-do-approve', { detail: { row: pendingRow, sig: pendingSigData } }));
            approveConfirmModal.style.display = 'none';
            sigModal.style.display = 'none';
            setTimeout(resetApproveConfirmButton, 3000);
        });

        confirmBtn.addEventListener('click', function () {
            if (!hasDrawing()) {
                errBox.style.display = '';
                return;
            }
            errBox.style.display = 'none';

            /* Export at a fixed small size (400×100 px) to keep the base64 payload
               well under typical PHP post_max_size regardless of screen DPR */
            var exportW = 400, exportH = 100;
            var offscreen = document.createElement('canvas');
            offscreen.width  = exportW;
            offscreen.height = exportH;
            var octx = offscreen.getContext('2d');
            /* White background so the PNG is legible on the printed form */
            octx.fillStyle = '#ffffff';
            octx.fillRect(0, 0, exportW, exportH);
            /* Scale the drawn strokes down to the export size */
            var scaleX = exportW / (canvas.width  / (window.devicePixelRatio || 1));
            var scaleY = exportH / (canvas.height / (window.devicePixelRatio || 1));
            octx.drawImage(canvas, 0, 0, canvas.width, canvas.height, 0, 0, exportW, exportH);
            pendingSigData = offscreen.toDataURL('image/png');
            approveConfirmModal.style.display = 'block';
            setTimeout(function () { approveConfirmYes.focus(); }, 30);
            return;

            /* Disable confirm while saving */
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<?= smsIcon('spinner', ['class' => 'fa-spin me-1']) ?>Saving…';

            /* Call the shared doAction via a custom event so we stay in the outer IIFE scope */
            var ev = new CustomEvent('ta-do-approve', { detail: { row: pendingRow, sig: sigData } });
            document.dispatchEvent(ev);

            closeSigModal();
            /* Re-enable in case something goes wrong */
            setTimeout(function () {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<?= smsIcon('check', ['class' => 'me-1']) ?>Confirm &amp; Approve';
            }, 3000);
        });

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        /* Expose opener to outer IIFE */
        window._taSigOpen = function (row) {
            pendingRow = row;
            pendingSigData = '';
            resetApproveConfirmButton();
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            errBox.style.display = 'none';
            sigModal.style.display = 'block';
            /* Resize after the modal becomes visible so getBoundingClientRect is accurate */
            setTimeout(resizeCanvas, 30);
        };
    })();
    </script>
    <?php endif; ?>

    <?php
}
