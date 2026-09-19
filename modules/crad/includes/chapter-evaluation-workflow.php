<?php
/**
 * Chapter 1-3 document submission and grammarian evaluation workflow.
 */
declare(strict_types=1);

require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/audit.php';
require_once ROOT_PATH . '/includes/uploads.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/includes/icons.php';
require_once ROOT_PATH . '/modules/crad/includes/research-progress-helpers.php';

/**
 * Grammarian scoring: five criteria at 20% each = 100%.
 *
 * @return list<array{key:string,label:string,weight:float}>
 */
function chapterEvaluationCriteria(): array
{
    return [
        ['key' => 'content', 'label' => 'Content', 'weight' => 20.0],
        ['key' => 'methodology', 'label' => 'Methodology', 'weight' => 20.0],
        ['key' => 'references', 'label' => 'References', 'weight' => 20.0],
        ['key' => 'format', 'label' => 'Format', 'weight' => 20.0],
        ['key' => 'grammar', 'label' => 'Grammar', 'weight' => 20.0],
    ];
}

function chapterLiveEvaluatorName(): string
{
    $fallback = trim((string) ($_SESSION['user_name'] ?? ''));
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $sms = function_exists('db') ? db() : null;
    if ($sms instanceof PDO && $userId > 0) {
        try {
            $stmt = $sms->prepare(
                "SELECT full_name FROM users
                 WHERE id = ? AND TRIM(COALESCE(full_name, '')) <> ''
                 LIMIT 1"
            );
            $stmt->execute([$userId]);
            $name = trim((string) $stmt->fetchColumn());
            if ($name !== '') {
                return $name;
            }
        } catch (Throwable $e) {
            // keep session fallback
        }
    }
    return $fallback;
}

function chapterEvaluationMaxPoints(): float
{
    return 20.0;
}

function chapterEvaluationTotalMax(): float
{
    $total = 0.0;
    foreach (chapterEvaluationCriteria() as $item) {
        $total += (float) $item['weight'];
    }
    return $total;
}

function chapterRegistryFullyApprovedClause(string $alias = 't'): string
{
    return "{$alias}.status = 'Approved'
        AND {$alias}.coordinator_status = 'Approved'
        AND {$alias}.crad_status = 'Approved'
        AND {$alias}.adviser_signature_data IS NOT NULL AND {$alias}.adviser_signature_data <> ''
        AND {$alias}.coordinator_signature_data IS NOT NULL AND {$alias}.coordinator_signature_data <> ''
        AND {$alias}.crad_signature_data IS NOT NULL AND {$alias}.crad_signature_data <> ''";
}

function chapterRegistryStudentIdentity(): array
{
    return [
        'student_id' => trim((string) ($_SESSION['student_id'] ?? '')),
        'email' => strtolower(trim((string) ($_SESSION['user_email'] ?? ''))),
        'name' => strtolower(trim((string) ($_SESSION['user_name'] ?? ''))),
        'user_id' => (int) ($_SESSION['user_id'] ?? 0),
    ];
}

function chapterRegisteredStudentGroup(PDO $crad): ?array
{
    $identity = chapterRegistryStudentIdentity();
    $stmt = $crad->prepare(
        "SELECT rg.*, rp.id AS research_plan_id, rp.status AS plan_status,
                ca.id AS coord_assignment_id, ca.coordinator_name, ca.coordinator_email,
                aa.id AS adviser_assignment_id, aa.adviser_user_id, aa.adviser_name, aa.adviser_email
         FROM research_groups rg
         JOIN title_approvals t ON t.id = rg.title_approval_id
         JOIN research_coordinator_assignments ca ON ca.id = (
            SELECT ca2.id
            FROM research_coordinator_assignments ca2
            WHERE ca2.status = 'Active'
              AND (
                    ca2.research_group_id = rg.id
                 OR (ca2.research_group_id IS NULL AND ca2.group_number = rg.group_number)
              )
            ORDER BY ca2.updated_at DESC, ca2.id DESC
            LIMIT 1
         )
         JOIN research_adviser_assignments aa ON aa.id = (
            SELECT aa2.id
            FROM research_adviser_assignments aa2
            WHERE (
                    aa2.research_group_id = rg.id
                 OR (aa2.research_group_id IS NULL AND aa2.group_number = rg.group_number)
              )
            ORDER BY (aa2.assignment_status = 'Assigned') DESC, aa2.updated_at DESC, aa2.id DESC
            LIMIT 1
         )
         LEFT JOIN research_plans rp ON rp.research_group_id = rg.id
         WHERE rg.title_approval_id IS NOT NULL
           AND " . chapterRegistryFullyApprovedClause('t') . "
           AND TRIM(COALESCE(rg.research_title, '')) <> ''
           AND TRIM(COALESCE(rg.academic_year, '')) <> ''
           AND (TRIM(COALESCE(rg.college_dept, '')) <> '' OR TRIM(COALESCE(t.department, '')) <> '')
           AND (
                (:student_id <> '' AND rg.leader_id = :student_id_match)
             OR (:email <> '' AND LOWER(TRIM(rg.leader_email)) = :email_match)
             OR (:name <> '' AND LOWER(TRIM(rg.leader_name)) = :name_match)
             OR (:user_id > 0 AND t.student_user_id = :user_id_match)
             OR (:student_id2 <> '' AND t.student_id = :student_id_match2)
             OR (:name2 <> '' AND LOWER(TRIM(t.student_name)) = :name_match2)
             OR EXISTS (
                    SELECT 1 FROM proposal_members pm
                    WHERE pm.proposal_id = rg.proposal_id
                      AND (
                           (:student_id3 <> '' AND pm.student_id = :student_id_match3)
                        OR (:email2 <> '' AND LOWER(TRIM(pm.email)) = :email_match2)
                        OR (:name3 <> '' AND LOWER(TRIM(pm.student_name)) = :name_match3)
                      )
                )
             OR (:email3 <> '' AND LOWER(t.members_json) LIKE :email_like)
             OR (:student_id4 <> '' AND t.members_json LIKE :student_id_like)
           )
         ORDER BY rg.date_assigned DESC, rg.created_at DESC, rg.id DESC
         LIMIT 1"
    );
    $stmt->execute([
        ':student_id' => $identity['student_id'],
        ':student_id_match' => $identity['student_id'],
        ':email' => $identity['email'],
        ':email_match' => $identity['email'],
        ':name' => $identity['name'],
        ':name_match' => $identity['name'],
        ':user_id' => $identity['user_id'],
        ':user_id_match' => $identity['user_id'],
        ':student_id2' => $identity['student_id'],
        ':student_id_match2' => $identity['student_id'],
        ':name2' => $identity['name'],
        ':name_match2' => $identity['name'],
        ':student_id3' => $identity['student_id'],
        ':student_id_match3' => $identity['student_id'],
        ':email2' => $identity['email'],
        ':email_match2' => $identity['email'],
        ':name3' => $identity['name'],
        ':name_match3' => $identity['name'],
        ':email3' => $identity['email'],
        ':email_like' => '%' . $identity['email'] . '%',
        ':student_id4' => $identity['student_id'],
        ':student_id_like' => '%' . $identity['student_id'] . '%',
    ]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function chapterSubmissionUnavailableMessage(): string
{
    return 'Document Submission is not yet available. Your research group must be officially registered.';
}

function chapterRenderUnavailableNotice(): void
{
    echo '<div class="alert alert-info">'
        . smsIcon('info-circle', ['class' => 'me-2'])
        . '<strong>Document Submission is not yet available.</strong><br>'
        . 'Your research group must be officially listed in the Capstone Group/Student Registry before you can submit Chapter 1-3. Please ensure your approved research title, group members, adviser, coordinator, program, and academic year are complete.'
        . '</div>';
}

function chapterAllowedChapters(): array
{
    return [1 => 'Chapter 1', 2 => 'Chapter 2', 3 => 'Chapter 3'];
}

function chapterEnsureSchema(PDO $crad): void
{
    $crad->exec(
        "CREATE TABLE IF NOT EXISTS chapter_submissions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            research_group_id INT UNSIGNED NOT NULL,
            research_plan_id INT UNSIGNED DEFAULT NULL,
            chapter_number TINYINT UNSIGNED NOT NULL,
            version_number INT UNSIGNED NOT NULL,
            status ENUM('Submitted','Under Review','Needs Revision','Accepted') NOT NULL DEFAULT 'Submitted',
            submitted_by_user INT UNSIGNED DEFAULT NULL,
            submitted_by_name VARCHAR(150) NOT NULL DEFAULT '',
            submitted_by_email VARCHAR(190) NOT NULL DEFAULT '',
            submission_notes TEXT DEFAULT NULL,
            original_name VARCHAR(255) NOT NULL DEFAULT '',
            stored_subdir VARCHAR(180) NOT NULL DEFAULT '',
            stored_name VARCHAR(120) NOT NULL DEFAULT '',
            file_size INT UNSIGNED NOT NULL DEFAULT 0,
            file_mime VARCHAR(120) NOT NULL DEFAULT '',
            submission_token VARCHAR(64) NOT NULL,
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            review_started_at DATETIME DEFAULT NULL,
            reviewed_at DATETIME DEFAULT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_chapter_version (research_group_id, chapter_number, version_number),
            UNIQUE KEY uniq_chapter_token (submission_token),
            KEY idx_chapter_status (status),
            KEY idx_chapter_group (research_group_id),
            KEY idx_chapter_student (submitted_by_user),
            KEY idx_chapter_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $crad->exec(
        "CREATE TABLE IF NOT EXISTS chapter_submission_history (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id INT UNSIGNED NOT NULL,
            research_group_id INT UNSIGNED NOT NULL,
            chapter_number TINYINT UNSIGNED NOT NULL,
            version_number INT UNSIGNED NOT NULL,
            status VARCHAR(40) NOT NULL,
            event_type VARCHAR(60) NOT NULL,
            actor_user_id INT UNSIGNED DEFAULT NULL,
            actor_name VARCHAR(150) NOT NULL DEFAULT '',
            actor_role VARCHAR(60) NOT NULL DEFAULT '',
            detail TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_chapter_history_submission (submission_id),
            KEY idx_chapter_history_group (research_group_id),
            KEY idx_chapter_history_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $crad->exec(
        "CREATE TABLE IF NOT EXISTS chapter_evaluations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id INT UNSIGNED NOT NULL,
            research_group_id INT UNSIGNED NOT NULL,
            evaluator_user_id INT UNSIGNED NOT NULL,
            evaluator_name VARCHAR(150) NOT NULL DEFAULT '',
            content_score DECIMAL(5,2) NOT NULL,
            methodology_score DECIMAL(5,2) NOT NULL,
            references_score DECIMAL(5,2) NOT NULL,
            format_score DECIMAL(5,2) NOT NULL,
            grammar_score DECIMAL(5,2) NOT NULL DEFAULT 0,
            content_remarks TEXT DEFAULT NULL,
            methodology_remarks TEXT DEFAULT NULL,
            references_remarks TEXT DEFAULT NULL,
            format_remarks TEXT DEFAULT NULL,
            grammar_remarks TEXT DEFAULT NULL,
            overall_feedback TEXT DEFAULT NULL,
            result ENUM('APPROVED','APPROVED WITH REVISION') NOT NULL,
            overall_score DECIMAL(5,2) DEFAULT NULL,
            evaluated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_chapter_evaluation_submission (submission_id),
            KEY idx_chapter_eval_evaluator (evaluator_user_id),
            KEY idx_chapter_eval_group (research_group_id),
            KEY idx_chapter_eval_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $crad->exec(
        "CREATE TABLE IF NOT EXISTS chapter_evaluation_notifications (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_key VARCHAR(120) NOT NULL,
            recipient_user_id INT UNSIGNED DEFAULT NULL,
            recipient_role VARCHAR(60) NOT NULL DEFAULT '',
            recipient_email VARCHAR(190) NOT NULL DEFAULT '',
            submission_id INT UNSIGNED NOT NULL,
            type VARCHAR(60) NOT NULL,
            title VARCHAR(180) NOT NULL,
            body TEXT NOT NULL,
            url VARCHAR(255) NOT NULL DEFAULT '',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_chapter_notification_event (event_key),
            KEY idx_chapter_notification_recipient (recipient_user_id, recipient_role, recipient_email),
            KEY idx_chapter_notification_submission (submission_id),
            KEY idx_chapter_notification_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    try {
        $grammarScore = $crad->query("SHOW COLUMNS FROM chapter_evaluations LIKE 'grammar_score'")->fetch();
        if (!$grammarScore) {
            $crad->exec("ALTER TABLE chapter_evaluations ADD COLUMN grammar_score DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER format_score");
        }
        $grammarRemarks = $crad->query("SHOW COLUMNS FROM chapter_evaluations LIKE 'grammar_remarks'")->fetch();
        if (!$grammarRemarks) {
            $crad->exec("ALTER TABLE chapter_evaluations ADD COLUMN grammar_remarks TEXT DEFAULT NULL AFTER format_remarks");
        }
    } catch (Throwable $e) {
        error_log('Chapter evaluation grammar column check skipped: ' . $e->getMessage());
    }
}

function chapterDb(): PDO
{
    $crad = getCradDatabaseConnection();
    chapterEnsureSchema($crad);
    return $crad;
}

function chapterCurrentStudentGroup(PDO $crad): ?array
{
    $studentId = trim((string) ($_SESSION['student_id'] ?? ''));
    $email = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    $name = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    $stmt = $crad->prepare(
        "SELECT rg.*, rp.id AS research_plan_id, rp.status AS plan_status
         FROM research_groups rg
         LEFT JOIN research_plans rp ON rp.research_group_id = rg.id
         WHERE rg.status = 'Approved'
           AND (
                (:student_id <> '' AND rg.leader_id = :student_id_match)
             OR (:email <> '' AND LOWER(TRIM(rg.leader_email)) = :email_match)
             OR (:name <> '' AND LOWER(TRIM(rg.leader_name)) = :name_match)
             OR EXISTS (
                    SELECT 1 FROM proposal_members pm
                    WHERE pm.proposal_id = rg.proposal_id
                      AND (
                           (:student_id2 <> '' AND pm.student_id = :student_id_match2)
                        OR (:email2 <> '' AND LOWER(TRIM(pm.email)) = :email_match2)
                        OR (:name2 <> '' AND LOWER(TRIM(pm.student_name)) = :name_match2)
                      )
                )
             OR EXISTS (
                    SELECT 1 FROM title_approvals ta
                    WHERE ta.id = rg.title_approval_id
                      AND (
                           (:student_id3 <> '' AND ta.student_id = :student_id_match3)
                        OR (:user_id > 0 AND ta.student_user_id = :user_id_match)
                        OR (:name3 <> '' AND LOWER(TRIM(ta.student_name)) = :name_match3)
                        OR (:email3 <> '' AND LOWER(ta.members_json) LIKE :email_like)
                        OR (:student_id4 <> '' AND ta.members_json LIKE :student_id_like)
                      )
                )
           )
         ORDER BY rg.created_at DESC, rg.id DESC
         LIMIT 1"
    );
    $stmt->execute([
        ':student_id' => $studentId,
        ':student_id_match' => $studentId,
        ':email' => $email,
        ':email_match' => $email,
        ':name' => $name,
        ':name_match' => $name,
        ':student_id2' => $studentId,
        ':student_id_match2' => $studentId,
        ':email2' => $email,
        ':email_match2' => $email,
        ':name2' => $name,
        ':name_match2' => $name,
        ':student_id3' => $studentId,
        ':student_id_match3' => $studentId,
        ':user_id' => $userId,
        ':user_id_match' => $userId,
        ':name3' => $name,
        ':name_match3' => $name,
        ':email3' => $email,
        ':email_like' => '%' . $email . '%',
        ':student_id4' => $studentId,
        ':student_id_like' => '%' . $studentId . '%',
    ]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function chapterSubmissionSelectSql(): string
{
    return "SELECT cs.*, rg.group_number, rg.group_name, rg.research_title, rg.academic_year,
                   rg.adviser, rg.leader_id, rg.leader_email, rg.leader_name,
                   ce.id AS evaluation_id, ce.evaluator_name, ce.result,
                   ce.content_score, ce.methodology_score, ce.references_score, ce.format_score,
                   ce.grammar_score, ce.grammar_remarks, ce.content_remarks, ce.methodology_remarks,
                   ce.references_remarks, ce.format_remarks,
                   ce.overall_score, ce.overall_feedback, ce.evaluated_at
            FROM chapter_submissions cs
            INNER JOIN research_groups rg ON rg.id = cs.research_group_id
            LEFT JOIN chapter_evaluations ce ON ce.submission_id = cs.id";
}

function chapterRegistryGroupGateSql(string $groupAlias = 'rg'): string
{
    return "{$groupAlias}.title_approval_id IS NOT NULL
        AND EXISTS (
            SELECT 1
            FROM title_approvals gate_t
            WHERE gate_t.id = {$groupAlias}.title_approval_id
              AND " . chapterRegistryFullyApprovedClause('gate_t') . "
              AND TRIM(COALESCE({$groupAlias}.research_title, '')) <> ''
              AND TRIM(COALESCE({$groupAlias}.academic_year, '')) <> ''
              AND (
                    TRIM(COALESCE({$groupAlias}.college_dept, '')) <> ''
                 OR TRIM(COALESCE(gate_t.department, '')) <> ''
              )
        )
        AND EXISTS (
            SELECT 1
            FROM research_coordinator_assignments gate_ca
            WHERE gate_ca.status = 'Active'
              AND (
                    gate_ca.research_group_id = {$groupAlias}.id
                 OR (gate_ca.research_group_id IS NULL AND gate_ca.group_number = {$groupAlias}.group_number)
              )
        )
        AND EXISTS (
            SELECT 1
            FROM research_adviser_assignments gate_aa
            WHERE (
                    gate_aa.research_group_id = {$groupAlias}.id
                 OR (gate_aa.research_group_id IS NULL AND gate_aa.group_number = {$groupAlias}.group_number)
              )
        )";
}

function chapterCurrentLatestSubmissionSql(string $submissionAlias = 'cs'): string
{
    return "{$submissionAlias}.id = (
        SELECT latest_cs.id
        FROM chapter_submissions latest_cs
        WHERE latest_cs.research_group_id = {$submissionAlias}.research_group_id
          AND latest_cs.chapter_number = {$submissionAlias}.chapter_number
        ORDER BY latest_cs.version_number DESC, latest_cs.id DESC
        LIMIT 1
    )";
}

function chapterSubmissionIsCurrentValid(PDO $crad, int $submissionId): bool
{
    if ($submissionId <= 0) {
        return false;
    }
    $stmt = $crad->prepare(
        "SELECT 1
         FROM chapter_submissions cs
         INNER JOIN research_groups rg ON rg.id = cs.research_group_id
         WHERE cs.id = :id
           AND " . chapterCurrentLatestSubmissionSql('cs') . "
           AND " . chapterRegistryGroupGateSql('rg') . "
         LIMIT 1"
    );
    $stmt->execute([':id' => $submissionId]);
    return (bool) $stmt->fetchColumn();
}

function chapterSubmissionIsActiveEvaluation(PDO $crad, int $submissionId): bool
{
    if ($submissionId <= 0) {
        return false;
    }
    $stmt = $crad->prepare(
        "SELECT 1
         FROM chapter_submissions cs
         INNER JOIN research_groups rg ON rg.id = cs.research_group_id
         LEFT JOIN chapter_evaluations ce ON ce.submission_id = cs.id
         WHERE cs.id = :id
           AND cs.status IN ('Submitted','Under Review')
           AND ce.id IS NULL
           AND " . chapterCurrentLatestSubmissionSql('cs') . "
           AND " . chapterRegistryGroupGateSql('rg') . "
         LIMIT 1"
    );
    $stmt->execute([':id' => $submissionId]);
    return (bool) $stmt->fetchColumn();
}

function chapterLatestSubmissionsForGroup(PDO $crad, int $groupId): array
{
    $stmt = $crad->prepare(
        chapterSubmissionSelectSql() . "
         WHERE cs.research_group_id = :gid
           AND cs.id IN (
                SELECT MAX(id) FROM chapter_submissions
                WHERE research_group_id = :gid2
                GROUP BY chapter_number
           )
         ORDER BY cs.chapter_number ASC"
    );
    $stmt->execute([':gid' => $groupId, ':gid2' => $groupId]);
    return $stmt->fetchAll() ?: [];
}

function chapterSubmissionHistoryForGroup(PDO $crad, int $groupId): array
{
    $stmt = $crad->prepare(
        "SELECT h.*, rg.group_number, rg.group_name, rg.research_title
         FROM chapter_submission_history h
         INNER JOIN research_groups rg ON rg.id = h.research_group_id
         WHERE h.research_group_id = :gid
         ORDER BY h.created_at ASC, h.id ASC"
    );
    $stmt->execute([':gid' => $groupId]);
    return $stmt->fetchAll() ?: [];
}

function chapterEvaluatorQueue(PDO $crad, bool $history = false): array
{
    if (!chapterIsEvaluator()) {
        return [];
    }
    $where = $history
        ? "ce.id IS NOT NULL"
        : "cs.status IN ('Submitted','Under Review')
           AND ce.id IS NULL
           AND " . chapterCurrentLatestSubmissionSql('cs') . "
           AND " . chapterRegistryGroupGateSql('rg');
    $stmt = $crad->query(
        chapterSubmissionSelectSql() . "
         WHERE {$where}
         ORDER BY " . ($history ? 'ce.evaluated_at DESC' : 'cs.submitted_at ASC') . ", cs.id ASC"
    );
    return $stmt->fetchAll() ?: [];
}

function chapterGetSubmission(PDO $crad, int $id): ?array
{
    $stmt = $crad->prepare(chapterSubmissionSelectSql() . " WHERE cs.id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function chapterIsEvaluator(): bool
{
    return getCurrentUserRoleKey() === 'grammarian' && userCanAccessModule('faculty');
}

function chapterStudentCanAccess(array $submission): bool
{
    $group = chapterRegisteredStudentGroup(chapterDb());
    return $group && (int) $group['id'] === (int) ($submission['research_group_id'] ?? 0);
}

function chapterEvaluatorCanAccess(array $submission): bool
{
    if (!chapterIsEvaluator()) {
        return false;
    }
    if (!empty($submission['evaluation_id'])) {
        return true;
    }
    return chapterSubmissionIsActiveEvaluation(chapterDb(), (int) ($submission['id'] ?? 0));
}

function chapterStatusClass(string $status): string
{
    return match ($status) {
        'Submitted' => 'primary',
        'Under Review' => 'warning',
        'Needs Revision' => 'danger',
        'Accepted' => 'success',
        default => 'secondary',
    };
}

function chapterLabel(int $chapter): string
{
    return chapterAllowedChapters()[$chapter] ?? ('Chapter ' . $chapter);
}

function chapterHistoryInsert(PDO $crad, int $submissionId, int $groupId, int $chapter, int $version, string $status, string $eventType, string $detail = ''): void
{
    $stmt = $crad->prepare(
        "INSERT INTO chapter_submission_history
            (submission_id, research_group_id, chapter_number, version_number, status, event_type,
             actor_user_id, actor_name, actor_role, detail)
         VALUES
            (:sid, :gid, :chapter, :version, :status, :event_type, :actor_user_id, :actor_name, :actor_role, :detail)"
    );
    $stmt->execute([
        ':sid' => $submissionId,
        ':gid' => $groupId,
        ':chapter' => $chapter,
        ':version' => $version,
        ':status' => $status,
        ':event_type' => $eventType,
        ':actor_user_id' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
        ':actor_name' => (string) ($_SESSION['user_name'] ?? ''),
        ':actor_role' => (string) ($_SESSION['user_role_key'] ?? ''),
        ':detail' => $detail,
    ]);
}

function chapterNotify(PDO $crad, string $eventKey, int $submissionId, array $recipient, string $type, string $title, string $body, string $url): void
{
    $stmt = $crad->prepare(
        "INSERT IGNORE INTO chapter_evaluation_notifications
            (event_key, recipient_user_id, recipient_role, recipient_email, submission_id, type, title, body, url)
         VALUES
            (:event_key, :user_id, :role, :email, :submission_id, :type, :title, :body, :url)"
    );
    $stmt->execute([
        ':event_key' => $eventKey,
        ':user_id' => (int) ($recipient['id'] ?? 0) ?: null,
        ':role' => (string) ($recipient['role_key'] ?? ''),
        ':email' => strtolower(trim((string) ($recipient['email'] ?? ''))),
        ':submission_id' => $submissionId,
        ':type' => $type,
        ':title' => $title,
        ':body' => $body,
        ':url' => $url,
    ]);
}

function chapterNotifyEvaluators(PDO $crad, array $submission): void
{
    $sms = db();
    if (!$sms) {
        return;
    }
    $users = $sms->query("SELECT id, email, role_key FROM users WHERE role_key = 'grammarian' AND status = 'active'")->fetchAll() ?: [];
    foreach ($users as $user) {
        $chapter = chapterLabel((int) $submission['chapter_number']);
        $isRevision = (int) $submission['version_number'] > 1;
        $title = $isRevision ? 'Revised Chapter Submitted' : 'New Chapter Submission';
        $body = sprintf(
            $isRevision
                ? '%s submitted %s Version %d for re-evaluation.'
                : '%s submitted %s Version %d for evaluation.',
            (string) $submission['group_name'],
            $chapter,
            (int) $submission['version_number']
        );
        chapterNotify(
            $crad,
            'evaluator:new:' . (int) $submission['id'] . ':u' . (int) $user['id'],
            (int) $submission['id'],
            $user,
            'new_submission',
            $title,
            $body,
            BASE_URL . '/modules/faculty/pages/evaluation-scoring.php?id=' . (int) $submission['id']
        );
    }
}

function chapterNotifyStudent(PDO $crad, array $submission, string $type, string $title, string $body): void
{
    chapterNotify(
        $crad,
        'student:' . $type . ':' . (int) $submission['id'],
        (int) $submission['id'],
        [
            'id' => (int) ($submission['submitted_by_user'] ?? 0),
            'email' => (string) ($submission['submitted_by_email'] ?? ''),
            'role_key' => 'student',
        ],
        $type,
        $title,
        $body,
        BASE_URL . '/modules/student-portal/pages/submission-status.php'
    );
}

function chapterNextVersion(PDO $crad, int $groupId, int $chapter): int
{
    $stmt = $crad->prepare("SELECT MAX(version_number) FROM chapter_submissions WHERE research_group_id = :gid AND chapter_number = :chapter");
    $stmt->execute([':gid' => $groupId, ':chapter' => $chapter]);
    return ((int) $stmt->fetchColumn()) + 1;
}

function chapterMaySubmitNewVersion(PDO $crad, int $groupId, int $chapter): array
{
    $stmt = $crad->prepare(
        "SELECT status, version_number FROM chapter_submissions
         WHERE research_group_id = :gid AND chapter_number = :chapter
         ORDER BY version_number DESC, id DESC
         LIMIT 1"
    );
    $stmt->execute([':gid' => $groupId, ':chapter' => $chapter]);
    $latest = $stmt->fetch();
    if (!$latest) {
        return ['ok' => true, 'error' => ''];
    }
    if ((string) $latest['status'] === 'Needs Revision') {
        return ['ok' => true, 'error' => ''];
    }
    return ['ok' => false, 'error' => 'A newer version can only be submitted after the latest submission needs revision.'];
}

function chapterAdviserSubmissionApproval(PDO $crad, int $groupId, int $chapter): ?array
{
    return rpAdviserApprovedChapter($crad, $groupId, $chapter);
}

function chapterSubmissionEligibility(PDO $crad, int $groupId): array
{
    return rpChapterSubmissionEligibility($crad, $groupId);
}

function chapterIsReadyForPreOral(PDO $crad, int $groupId): bool
{
    if ($groupId <= 0) {
        return false;
    }

    $stmt = $crad->prepare(
        "SELECT COUNT(*)
         FROM chapter_submissions latest
         INNER JOIN (
             SELECT chapter_number, MAX(version_number) AS version_number
             FROM chapter_submissions
             WHERE research_group_id = :group_id
               AND chapter_number IN (1, 2, 3)
             GROUP BY chapter_number
         ) versions
           ON versions.chapter_number = latest.chapter_number
          AND versions.version_number = latest.version_number
         WHERE latest.research_group_id = :latest_group_id
           AND latest.status = 'Accepted'
           AND latest.chapter_number IN (1, 2, 3)"
    );
    $stmt->execute([
        ':group_id' => $groupId,
        ':latest_group_id' => $groupId,
    ]);

    return (int) $stmt->fetchColumn() === 3;
}

function chapterSubmitDocument(PDO $crad, array $group, int $chapter, array $file, string $notes, string $token): array
{
    $registeredGroup = chapterRegisteredStudentGroup($crad);
    if (!$registeredGroup || (int) $registeredGroup['id'] !== (int) ($group['id'] ?? 0)) {
        return ['ok' => false, 'error' => chapterSubmissionUnavailableMessage()];
    }

    $allowed = chapterAllowedChapters();
    if (!isset($allowed[$chapter])) {
        return ['ok' => false, 'error' => 'Invalid chapter selected.'];
    }
    if (!chapterAdviserSubmissionApproval($crad, (int) $group['id'], $chapter)) {
        return [
            'ok' => false,
            'error' => 'Adviser approval is required before this chapter can be submitted for Grammarian evaluation.',
        ];
    }
    if ($token === '' || !preg_match('/^[a-f0-9]{32,64}$/', $token)) {
        return ['ok' => false, 'error' => 'Invalid submission token. Please refresh and try again.'];
    }

    $gate = chapterMaySubmitNewVersion($crad, (int) $group['id'], $chapter);
    if (empty($gate['ok'])) {
        return $gate;
    }

    $subdir = 'student_chapters/u' . max(0, (int) ($_SESSION['user_id'] ?? 0));
    $upload = smsSecureUpload($file, [
        'subdir' => $subdir,
        'max_bytes' => 10 * 1024 * 1024,
        'allowed' => smsUploadAllowedDocuments(),
        'required' => true,
    ]);
    if (empty($upload['ok'])) {
        return ['ok' => false, 'error' => $upload['error'] ?: 'Upload failed.'];
    }

    try {
        $crad->beginTransaction();
        $version = chapterNextVersion($crad, (int) $group['id'], $chapter);
        $stmt = $crad->prepare(
            "INSERT INTO chapter_submissions
                (research_group_id, research_plan_id, chapter_number, version_number, status,
                 submitted_by_user, submitted_by_name, submitted_by_email, submission_notes,
                 original_name, stored_subdir, stored_name, file_size, file_mime, submission_token)
             VALUES
                (:gid, :plan_id, :chapter, :version, 'Submitted',
                 :user_id, :user_name, :user_email, :notes,
                 :original_name, :stored_subdir, :stored_name, :file_size, :file_mime, :token)"
        );
        $stmt->execute([
            ':gid' => (int) $group['id'],
            ':plan_id' => (int) ($group['research_plan_id'] ?? 0) ?: null,
            ':chapter' => $chapter,
            ':version' => $version,
            ':user_id' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
            ':user_name' => (string) ($_SESSION['user_name'] ?? ''),
            ':user_email' => strtolower(trim((string) ($_SESSION['user_email'] ?? ''))),
            ':notes' => $notes,
            ':original_name' => (string) $upload['original_name'],
            ':stored_subdir' => $subdir,
            ':stored_name' => (string) $upload['stored_name'],
            ':file_size' => (int) $upload['size'],
            ':file_mime' => (string) $upload['mime'],
            ':token' => $token,
        ]);
        $submissionId = (int) $crad->lastInsertId();
        chapterHistoryInsert($crad, $submissionId, (int) $group['id'], $chapter, $version, 'Submitted', 'submitted', $notes);
        $submission = chapterGetSubmission($crad, $submissionId);
        if ($submission) {
            chapterNotifyEvaluators($crad, $submission);
        }
        $crad->commit();
        logActivity('create', chapterLabel($chapter) . ' Version ' . $version . ' submitted for grammarian evaluation', 'student_portal');
        return ['ok' => true, 'submission_id' => $submissionId, 'version' => $version];
    } catch (PDOException $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'error' => 'Duplicate submission detected. Please wait for the current request to finish.'];
        }
        error_log('Chapter submission failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Unable to save the submission. Please try again.'];
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('Chapter submission failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Unable to save the submission. Please try again.'];
    }
}

function chapterStartReview(PDO $crad, array $submission): array
{
    if (!chapterEvaluatorCanAccess($submission)) {
        return ['ok' => false, 'error' => 'Access denied.'];
    }
    if (!chapterSubmissionIsActiveEvaluation($crad, (int) ($submission['id'] ?? 0))) {
        return ['ok' => false, 'error' => 'This submission is no longer in the active evaluation queue.'];
    }
    if ((string) $submission['status'] !== 'Submitted') {
        return ['ok' => true, 'message' => 'Review already started.'];
    }

    try {
        $crad->beginTransaction();
        $stmt = $crad->prepare(
            "UPDATE chapter_submissions
             SET status = 'Under Review', review_started_at = COALESCE(review_started_at, NOW())
             WHERE id = :id AND status = 'Submitted'"
        );
        $stmt->execute([':id' => (int) $submission['id']]);
        if ($stmt->rowCount() > 0) {
            chapterHistoryInsert(
                $crad,
                (int) $submission['id'],
                (int) $submission['research_group_id'],
                (int) $submission['chapter_number'],
                (int) $submission['version_number'],
                'Under Review',
                'review_started',
                'Grammarian started review.'
            );
            $fresh = chapterGetSubmission($crad, (int) $submission['id']) ?: $submission;
            chapterNotifyStudent(
                $crad,
                $fresh,
                'under_review',
                chapterLabel((int) $submission['chapter_number']) . ' is under review',
                chapterLabel((int) $submission['chapter_number']) . ' Version ' . (int) $submission['version_number'] . ' is now under review.'
            );
        }
        $crad->commit();
        logActivity('update', 'Started review for ' . chapterLabel((int) $submission['chapter_number']) . ' Version ' . (int) $submission['version_number'], 'faculty');
        return ['ok' => true, 'message' => 'Review started.'];
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('Chapter review start failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Unable to start review. Please try again.'];
    }
}

function chapterSubmitEvaluation(PDO $crad, array $submission, array $data): array
{
    if (!chapterEvaluatorCanAccess($submission)) {
        return ['ok' => false, 'error' => 'Access denied.'];
    }
    if (!chapterSubmissionIsActiveEvaluation($crad, (int) ($submission['id'] ?? 0))) {
        return ['ok' => false, 'error' => 'This submission is no longer in the active evaluation queue.'];
    }
    if ((string) ($submission['status'] ?? '') !== 'Under Review') {
        return ['ok' => false, 'error' => 'Please start the review before submitting an evaluation.'];
    }
    if (!empty($submission['evaluation_id'])) {
        return ['ok' => false, 'error' => 'This submission already has an evaluation.'];
    }

    $maxPoints = chapterEvaluationMaxPoints();
    $scores = [];
    foreach (chapterEvaluationCriteria() as $item) {
        $key = $item['key'] . '_score';
        $raw = trim((string) ($data[$key] ?? ''));
        if ($raw === '' || !is_numeric($raw)) {
            return ['ok' => false, 'error' => 'All scores must be numeric.'];
        }
        $score = (float) $raw;
        $weight = (float) $item['weight'];
        if ($score < 0) {
            return ['ok' => false, 'error' => $item['label'] . ' Score cannot be below 0.'];
        }
        if ($score > $maxPoints || $score > $weight) {
            return ['ok' => false, 'error' => $item['label'] . ' Score cannot exceed ' . number_format($weight, 0) . '%. Evaluation was not submitted.'];
        }
        $scores[$key] = round($score, 2);
    }

    $overall = round(array_sum($scores), 2);
    $totalMax = chapterEvaluationTotalMax();
    if ($overall > $totalMax) {
        return ['ok' => false, 'error' => 'Total score cannot exceed ' . number_format($totalMax, 0) . '%. Evaluation was not submitted.'];
    }

    $result = strtoupper(trim((string) ($data['result'] ?? '')));
    if (!in_array($result, ['APPROVED', 'APPROVED WITH REVISION'], true)) {
        return ['ok' => false, 'error' => 'Invalid evaluation result.'];
    }
    $studentStatus = $result === 'APPROVED' ? 'Accepted' : 'Needs Revision';

    try {
        $crad->beginTransaction();
        $check = $crad->prepare('SELECT id FROM chapter_evaluations WHERE submission_id = :sid LIMIT 1');
        $check->execute([':sid' => (int) $submission['id']]);
        if ($check->fetch()) {
            $crad->rollBack();
            return ['ok' => false, 'error' => 'This submission already has an evaluation.'];
        }
        $stmt = $crad->prepare(
            "INSERT INTO chapter_evaluations
                (submission_id, research_group_id, evaluator_user_id, evaluator_name,
                 content_score, methodology_score, references_score, format_score, grammar_score,
                 content_remarks, methodology_remarks, references_remarks, format_remarks, grammar_remarks,
                 overall_feedback, result, overall_score)
             VALUES
                (:submission_id, :group_id, :evaluator_user_id, :evaluator_name,
                 :content_score, :methodology_score, :references_score, :format_score, :grammar_score,
                 :content_remarks, :methodology_remarks, :references_remarks, :format_remarks, :grammar_remarks,
                 :overall_feedback, :result, :overall_score)"
        );
        $stmt->execute([
            ':submission_id' => (int) $submission['id'],
            ':group_id' => (int) $submission['research_group_id'],
            ':evaluator_user_id' => (int) ($_SESSION['user_id'] ?? 0),
            ':evaluator_name' => chapterLiveEvaluatorName(),
            ':content_score' => $scores['content_score'],
            ':methodology_score' => $scores['methodology_score'],
            ':references_score' => $scores['references_score'],
            ':format_score' => $scores['format_score'],
            ':grammar_score' => $scores['grammar_score'],
            ':content_remarks' => trim((string) ($data['content_remarks'] ?? '')),
            ':methodology_remarks' => trim((string) ($data['methodology_remarks'] ?? '')),
            ':references_remarks' => trim((string) ($data['references_remarks'] ?? '')),
            ':format_remarks' => trim((string) ($data['format_remarks'] ?? '')),
            ':grammar_remarks' => trim((string) ($data['grammar_remarks'] ?? '')),
            ':overall_feedback' => trim((string) ($data['overall_feedback'] ?? '')),
            ':result' => $result,
            ':overall_score' => $overall,
        ]);
        $crad->prepare("UPDATE chapter_submissions SET status = :status, reviewed_at = NOW() WHERE id = :id")
            ->execute([':status' => $studentStatus, ':id' => (int) $submission['id']]);
        chapterHistoryInsert(
            $crad,
            (int) $submission['id'],
            (int) $submission['research_group_id'],
            (int) $submission['chapter_number'],
            (int) $submission['version_number'],
            $studentStatus,
            'evaluated',
            $result
        );
        $fresh = chapterGetSubmission($crad, (int) $submission['id']) ?: $submission;
        chapterNotifyStudent(
            $crad,
            $fresh,
            $studentStatus === 'Accepted' ? 'accepted' : 'needs_revision',
            chapterLabel((int) $submission['chapter_number']) . ' ' . strtolower($studentStatus),
            chapterLabel((int) $submission['chapter_number']) . ' Version ' . (int) $submission['version_number'] . ' is now ' . $studentStatus . '.'
        );
        $crad->commit();
        try {
            require_once __DIR__ . '/research-services-clearance.php';
            rscEnsureForReadyGroup($crad, (int) $submission['research_group_id']);
        } catch (Throwable $clearanceError) {
            error_log('Clearance draft after evaluation: ' . $clearanceError->getMessage());
        }
        logActivity('update', 'Submitted evaluation for ' . chapterLabel((int) $submission['chapter_number']) . ' Version ' . (int) $submission['version_number'], 'faculty');
        return ['ok' => true, 'status' => $studentStatus];
    } catch (PDOException $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'error' => 'This submission already has an evaluation.'];
        }
        error_log('Chapter evaluation failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Unable to save the evaluation. Please try again.'];
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('Chapter evaluation failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Unable to save the evaluation. Please try again.'];
    }
}

function chapterDocumentUrl(int $submissionId): string
{
    return BASE_URL . '/modules/crad/api/chapter-document.php?id=' . $submissionId;
}

function chapterFormatDate(?string $value): string
{
    $ts = $value ? strtotime($value) : false;
    return $ts ? date('M j, Y h:i A', $ts) : '-';
}
