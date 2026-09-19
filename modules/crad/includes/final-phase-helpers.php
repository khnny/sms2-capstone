<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/uploads.php';
require_once ROOT_PATH . '/modules/faculty/includes/final-defense-evaluation.php';
require_once ROOT_PATH . '/modules/crad/includes/research-progress-helpers.php';

/**
 * Final manuscript scoring: eight criteria totaling 100%.
 *
 * @return list<array{key:string,label:string,weight:float}>
 */
function manuscriptEvaluationCriteria(): array
{
    return [
        ['key' => 'content', 'label' => 'Content', 'weight' => 12.5],
        ['key' => 'methodology', 'label' => 'Methodology', 'weight' => 12.5],
        ['key' => 'results', 'label' => 'Results', 'weight' => 12.5],
        ['key' => 'conclusions', 'label' => 'Conclusions', 'weight' => 12.5],
        ['key' => 'recommendations', 'label' => 'Recommendations', 'weight' => 12.5],
        ['key' => 'references', 'label' => 'References', 'weight' => 12.5],
        ['key' => 'formatting', 'label' => 'Formatting', 'weight' => 12.5],
        ['key' => 'compliance', 'label' => 'Compliance', 'weight' => 12.5],
    ];
}

function manuscriptEvaluationTotalMax(): float
{
    $total = 0.0;
    foreach (manuscriptEvaluationCriteria() as $item) {
        $total += (float) $item['weight'];
    }
    return $total;
}

function finalPhaseEnsureSchema(PDO $crad): void
{
    finalDefenseEnsureSchema($crad);
    $tables = [
        "CREATE TABLE IF NOT EXISTS final_defense_recommendations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            research_group_id INT UNSIGNED NOT NULL,
            group_number VARCHAR(40) NOT NULL DEFAULT '',
            adviser_user_id INT UNSIGNED DEFAULT NULL,
            adviser_name VARCHAR(150) NOT NULL DEFAULT '',
            status ENUM('Not Ready','Recommended') NOT NULL DEFAULT 'Not Ready',
            remarks TEXT DEFAULT NULL,
            recommended_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), UNIQUE KEY uniq_fdr_group (research_group_id), KEY idx_fdr_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS manuscript_submissions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            research_group_id INT UNSIGNED NOT NULL,
            version_number INT UNSIGNED NOT NULL,
            status ENUM('Submitted','Under Review','For Revision','Approved') NOT NULL DEFAULT 'Submitted',
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
            reviewed_at DATETIME DEFAULT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), UNIQUE KEY uniq_manuscript_version (research_group_id, version_number),
            UNIQUE KEY uniq_manuscript_token (submission_token), KEY idx_manuscript_status (status), KEY idx_manuscript_group (research_group_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS manuscript_evaluations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id INT UNSIGNED NOT NULL,
            research_group_id INT UNSIGNED NOT NULL,
            evaluator_user_id INT UNSIGNED NOT NULL,
            evaluator_name VARCHAR(150) NOT NULL DEFAULT '',
            content_score DECIMAL(5,2) NOT NULL,
            methodology_score DECIMAL(5,2) NOT NULL,
            results_score DECIMAL(5,2) NOT NULL,
            conclusions_score DECIMAL(5,2) NOT NULL,
            recommendations_score DECIMAL(5,2) NOT NULL,
            references_score DECIMAL(5,2) NOT NULL,
            formatting_score DECIMAL(5,2) NOT NULL,
            compliance_score DECIMAL(5,2) NOT NULL,
            remarks TEXT DEFAULT NULL,
            result ENUM('APPROVED','FOR REVISION') NOT NULL,
            overall_score DECIMAL(5,2) NOT NULL,
            evaluated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_meval_submission (submission_id), KEY idx_meval_group (research_group_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS final_manuscript_approvals (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            research_group_id INT UNSIGNED NOT NULL,
            defense_schedule_id INT UNSIGNED DEFAULT NULL,
            approved_by_user INT UNSIGNED DEFAULT NULL,
            approved_by_name VARCHAR(150) NOT NULL DEFAULT '',
            status ENUM('Pending','Approved','Returned') NOT NULL DEFAULT 'Pending',
            remarks TEXT DEFAULT NULL,
            approved_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), UNIQUE KEY uniq_fma_group (research_group_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS publications (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            research_group_id INT UNSIGNED NOT NULL,
            title VARCHAR(500) NOT NULL DEFAULT '',
            authors TEXT DEFAULT NULL,
            publication_outlet VARCHAR(255) NOT NULL DEFAULT '',
            publication_date DATE DEFAULT NULL,
            doi_link VARCHAR(500) NOT NULL DEFAULT '',
            status ENUM('Draft','For Publication','Published','Archived') NOT NULL DEFAULT 'Draft',
            notes TEXT DEFAULT NULL,
            created_by_user INT UNSIGNED DEFAULT NULL,
            created_by_name VARCHAR(150) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY idx_pub_group (research_group_id), KEY idx_pub_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS research_revision_cycles (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            research_group_id INT UNSIGNED NOT NULL,
            defense_schedule_id INT UNSIGNED NOT NULL,
            official_result VARCHAR(60) NOT NULL DEFAULT 'APPROVED WITH REVISION',
            revision_status VARCHAR(60) NOT NULL DEFAULT 'Needs Revision',
            original_name VARCHAR(255) NOT NULL DEFAULT '',
            stored_subdir VARCHAR(180) NOT NULL DEFAULT '',
            stored_name VARCHAR(120) NOT NULL DEFAULT '',
            file_size INT UNSIGNED NOT NULL DEFAULT 0,
            file_mime VARCHAR(120) NOT NULL DEFAULT '',
            submission_token VARCHAR(64) DEFAULT NULL,
            opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id), UNIQUE KEY uniq_revision_group_defense (research_group_id, defense_schedule_id),
            KEY idx_revision_status (revision_status), UNIQUE KEY uniq_revision_token (submission_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
    foreach ($tables as $sql) {
        try { $crad->exec($sql); } catch (Throwable $e) { error_log('Final phase schema failed: ' . $e->getMessage()); }
    }

    foreach ([
        'final_defense_recommendations' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
        'final_manuscript_approvals' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
        'manuscript_evaluations' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
        'manuscript_submissions' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
        'publications' => 'INT UNSIGNED NOT NULL AUTO_INCREMENT',
    ] as $table => $definition) {
        try {
            $idColumn = $crad->query("SHOW COLUMNS FROM `{$table}` LIKE 'id'")->fetch(PDO::FETCH_ASSOC);
            if ($idColumn && stripos((string) ($idColumn['Extra'] ?? ''), 'auto_increment') === false) {
                $crad->exec("ALTER TABLE `{$table}` MODIFY `id` {$definition}");
            }
        } catch (Throwable $e) {
            error_log('Final phase id schema repair failed for ' . $table . ': ' . $e->getMessage());
        }
    }

    try {
        $legacyUnique = $crad->query(
            "SHOW INDEX FROM research_defense_schedules
             WHERE Key_name = 'uniq_rds_group_number' AND Non_unique = 0"
        )->fetch();
        if ($legacyUnique) {
            $crad->exec('ALTER TABLE research_defense_schedules DROP INDEX uniq_rds_group_number');
        }
    } catch (Throwable $e) {
        error_log('Final phase schedule index cleanup failed: ' . $e->getMessage());
    }

    foreach ([
        'original_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
        'stored_subdir' => "VARCHAR(180) NOT NULL DEFAULT ''",
        'stored_name' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'file_size' => 'INT UNSIGNED NOT NULL DEFAULT 0',
        'file_mime' => "VARCHAR(120) NOT NULL DEFAULT ''",
        'submission_token' => 'VARCHAR(64) DEFAULT NULL',
    ] as $column => $definition) {
        try {
            $check = $crad->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'research_revision_cycles'
                   AND COLUMN_NAME = ?"
            );
            $check->execute([$column]);
            if ((int) $check->fetchColumn() === 0) {
                $crad->exec("ALTER TABLE research_revision_cycles ADD COLUMN `{$column}` {$definition}");
            }
        } catch (Throwable $e) {
            error_log('Revision evidence schema failed: ' . $e->getMessage());
        }
    }
}

function fpIsRecommendedForFinalDefense(PDO $crad, int $groupId): bool
{
    $plan = rpGetResearchPlan($crad, $groupId);
    if (!$plan || !rpIsFirstSemesterComplete($crad, (int) $plan['id'], $groupId)) {
        return false;
    }
    $row = fpGetFinalDefenseRecommendation($crad, $groupId);
    return (string) ($row['status'] ?? '') === 'Recommended';
}

function fpAreAllMilestonesApprovedForFinalDefense(PDO $crad, int $groupId): bool
{
    if ($groupId <= 0) {
        return false;
    }

    $stmt = $crad->prepare(
        "SELECT COUNT(rm.id) AS total_count,
                SUM(CASE WHEN rm.status IN ('Approved', 'Completed') THEN 1 ELSE 0 END) AS approved_count
         FROM research_plans rp
         INNER JOIN research_milestones rm ON rm.research_plan_id = rp.id
         WHERE rp.research_group_id = ?"
    );
    $stmt->execute([$groupId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $totalCount = (int) ($row['total_count'] ?? 0);
    return $totalCount > 0 && (int) ($row['approved_count'] ?? 0) === $totalCount;
}

function fpSaveFinalDefenseRecommendation(PDO $crad, int $groupId, string $groupNumber, int $adviserUserId, string $adviserName, string $remarks): bool
{
    if ($groupId <= 0 || $adviserUserId <= 0 || trim($adviserName) === '') {
        return false;
    }
    if (!fpAreAllMilestonesApprovedForFinalDefense($crad, $groupId)) {
        return false;
    }

    finalPhaseEnsureSchema($crad);
    $stmt = $crad->prepare(
        "INSERT INTO final_defense_recommendations
            (research_group_id, group_number, adviser_user_id, adviser_name, status, remarks, recommended_at)
         VALUES (?, ?, ?, ?, 'Recommended', ?, NOW())
         ON DUPLICATE KEY UPDATE
            research_group_id = VALUES(research_group_id), group_number = VALUES(group_number), adviser_user_id = VALUES(adviser_user_id),
            adviser_name = VALUES(adviser_name), status = 'Recommended', remarks = VALUES(remarks),
            recommended_at = NOW()"
    );
    $stmt->execute([$groupId, trim($groupNumber), $adviserUserId, trim($adviserName), trim($remarks)]);
    rpEnsureFinalDefenseRecommendationSchema($crad);
    $legacy = $crad->prepare(
        "UPDATE research_plans
         SET final_defense_recommended = 1,
             final_defense_recommended_by = ?,
             final_defense_recommended_by_name = ?,
             final_defense_recommended_at = NOW(),
             final_defense_recommendation_remarks = ?
         WHERE research_group_id = ?"
    );
    $legacy->execute([$adviserUserId, trim($adviserName), trim($remarks), $groupId]);
    return true;
}

function fpClearFinalDefenseRecommendation(PDO $crad, int $groupId): bool
{
    if ($groupId <= 0) {
        return false;
    }

    finalPhaseEnsureSchema($crad);
    $stmt = $crad->prepare(
        "INSERT INTO final_defense_recommendations (research_group_id, status)
         VALUES (?, 'Not Ready')
         ON DUPLICATE KEY UPDATE status = 'Not Ready', adviser_user_id = NULL,
            adviser_name = '', remarks = NULL, recommended_at = NULL"
    );
    $stmt->execute([$groupId]);
    rpEnsureFinalDefenseRecommendationSchema($crad);
    $legacy = $crad->prepare(
        "UPDATE research_plans
         SET final_defense_recommended = 0,
             final_defense_recommended_by = NULL,
             final_defense_recommended_by_name = NULL,
             final_defense_recommended_at = NULL,
             final_defense_recommendation_remarks = NULL
         WHERE research_group_id = ?"
    );
    $legacy->execute([$groupId]);
    return true;
}

function fpGetFinalDefenseRecommendation(PDO $crad, int $groupId): ?array
{
    if ($groupId <= 0) return null;
    finalPhaseEnsureSchema($crad);
    $stmt = $crad->prepare("SELECT * FROM final_defense_recommendations WHERE research_group_id = ? LIMIT 1");
    $stmt->execute([$groupId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $row['final_defense_recommended'] = (string) ($row['status'] ?? '') === 'Recommended' ? 1 : 0;
        $row['final_defense_recommended_by'] = $row['adviser_user_id'] ?? null;
        $row['final_defense_recommended_by_name'] = $row['adviser_name'] ?? '';
        $row['final_defense_recommended_at'] = $row['recommended_at'] ?? null;
        $row['final_defense_recommendation_remarks'] = $row['remarks'] ?? null;
        return $row;
    }
    $legacy = rpGetFinalDefenseRecommendation($crad, $groupId);
    if (!empty($legacy['final_defense_recommended'])) {
        $groupStmt = $crad->prepare("SELECT group_number FROM research_groups WHERE id = ? LIMIT 1");
        $groupStmt->execute([$groupId]);
        $groupNumber = (string) ($groupStmt->fetchColumn() ?: '');
        $sync = $crad->prepare(
            "INSERT INTO final_defense_recommendations
                (research_group_id, group_number, adviser_user_id, adviser_name, status, remarks, recommended_at)
             VALUES (?, ?, ?, ?, 'Recommended', ?, COALESCE(?, NOW()))
             ON DUPLICATE KEY UPDATE
                group_number = VALUES(group_number),
                adviser_user_id = VALUES(adviser_user_id),
                adviser_name = VALUES(adviser_name),
                status = 'Recommended',
                remarks = VALUES(remarks),
                recommended_at = VALUES(recommended_at)"
        );
        $sync->execute([
            $groupId,
            $groupNumber,
            (int) ($legacy['final_defense_recommended_by'] ?? 0) ?: null,
            (string) ($legacy['final_defense_recommended_by_name'] ?? ''),
            (string) ($legacy['final_defense_recommendation_remarks'] ?? ''),
            $legacy['final_defense_recommended_at'] ?? null,
        ]);
        $stmt->execute([$groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['final_defense_recommended'] = 1;
            $row['final_defense_recommended_by'] = $row['adviser_user_id'] ?? null;
            $row['final_defense_recommended_by_name'] = $row['adviser_name'] ?? '';
            $row['final_defense_recommended_at'] = $row['recommended_at'] ?? null;
            $row['final_defense_recommendation_remarks'] = $row['remarks'] ?? null;
            return $row;
        }
    }
    return null;
}

function fpGetLatestManuscriptSubmission(PDO $crad, int $groupId): ?array
{
    finalPhaseEnsureSchema($crad);
    $stmt = $crad->prepare("SELECT * FROM manuscript_submissions WHERE research_group_id = ? ORDER BY version_number DESC, id DESC LIMIT 1");
    $stmt->execute([$groupId]); $row = $stmt->fetch(PDO::FETCH_ASSOC); return $row ?: null;
}

function fpGetManuscriptEvaluation(PDO $crad, int $submissionId): ?array
{
    finalPhaseEnsureSchema($crad);
    $stmt = $crad->prepare("SELECT * FROM manuscript_evaluations WHERE submission_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$submissionId]); $row = $stmt->fetch(PDO::FETCH_ASSOC); return $row ?: null;
}

function fpIsManuscriptApproved(PDO $crad, int $groupId): bool
{
    $submission = fpGetLatestManuscriptSubmission($crad, $groupId);
    $evaluation = $submission ? fpGetManuscriptEvaluation($crad, (int) $submission['id']) : null;
    return (string) ($submission['status'] ?? '') === 'Approved' && (string) ($evaluation['result'] ?? '') === 'APPROVED';
}

function fpGetFinalDefenseSchedule(PDO $crad, int $groupId): ?array
{
    $stmt = $crad->prepare("SELECT * FROM research_defense_schedules WHERE research_group_id = ? AND defense_type = ? AND LOWER(status) IN ('scheduled', 'finalized', 'final') ORDER BY defense_datetime DESC, id DESC LIMIT 1");
    $stmt->execute([$groupId, CRAD_DEFENSE_TYPE_FINAL]); $row = $stmt->fetch(PDO::FETCH_ASSOC); return $row ?: null;
}

function fpGetFinalDefensePanel(PDO $crad, int $groupId): array
{
    $stmt = $crad->prepare("SELECT * FROM research_panel_assignments WHERE research_group_id = ? AND defense_phase = ? ORDER BY id ASC");
    $stmt->execute([$groupId, CRAD_DEFENSE_PHASE_FINAL]); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function fpGroupNeedsFinalRevision(PDO $crad, int $groupId): bool
{
    $schedule = fpGetFinalDefenseSchedule($crad, $groupId); $panel = fpGetFinalDefensePanel($crad, $groupId);
    if (!$schedule || !$panel) return false;
        $stmt = $crad->prepare(
                "SELECT COUNT(DISTINCT rpa.panel_user_id) AS assigned_count,
                                COUNT(DISTINCT CASE WHEN fde.id IS NOT NULL THEN rpa.panel_user_id END) AS submitted_count,
                                COUNT(DISTINCT CASE WHEN fde.result = 'APPROVED WITH REVISION' THEN rpa.panel_user_id END) AS revision_count
                 FROM research_panel_assignments rpa
                 LEFT JOIN final_defense_evaluations fde
                     ON fde.defense_schedule_id = ?
                    AND fde.panel_user_id = rpa.panel_user_id
                 WHERE rpa.research_group_id = ?
                     AND rpa.defense_phase = ?
                     AND rpa.assignment_status = 'Assigned'"
        );
        $stmt->execute([(int) $schedule['id'], $groupId, CRAD_DEFENSE_PHASE_FINAL]);
        $evaluationState = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $needsRevision = (int) ($evaluationState['assigned_count'] ?? 0) > 0
                && (int) ($evaluationState['submitted_count'] ?? 0) === (int) ($evaluationState['assigned_count'] ?? 0)
                && (int) ($evaluationState['revision_count'] ?? 0) > 0;
    if ($needsRevision) {
        $cycle = $crad->prepare("INSERT INTO research_revision_cycles (research_group_id, defense_schedule_id, official_result, revision_status) VALUES (?, ?, 'APPROVED WITH REVISION', 'Needs Revision') ON DUPLICATE KEY UPDATE revision_status = IF(revision_status = 'Compliant', revision_status, 'Needs Revision')");
        $cycle->execute([$groupId, (int) $schedule['id']]);
    }
    return $needsRevision;
}

function fpGetRevisionCycle(PDO $crad, int $groupId): ?array
{
    finalPhaseEnsureSchema($crad);
    $stmt = $crad->prepare("SELECT * FROM research_revision_cycles WHERE research_group_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$groupId]); $row = $stmt->fetch(PDO::FETCH_ASSOC); return $row ?: null;
}

function fpSetRevisionStatus(PDO $crad, int $groupId, string $status): bool
{
    if (!in_array($status, ['Needs Revision', 'Under Review', 'Compliant'], true)) return false;
    $cycle = fpGetRevisionCycle($crad, $groupId);
    if (!$cycle) return false;
    if (in_array($status, ['Under Review', 'Compliant'], true)) {
        $stmt = $crad->prepare(
            "SELECT id
             FROM research_progress_updates
             WHERE research_group_id = ?
               AND submitted_at >= ?
             ORDER BY submitted_at DESC, id DESC
             LIMIT 1"
        );
        $stmt->execute([$groupId, (string) ($cycle['opened_at'] ?? '1000-01-01 00:00:00')]);
        if (!$stmt->fetchColumn()) return false;
    }
    $stmt = $crad->prepare("UPDATE research_revision_cycles SET revision_status = ?, completed_at = IF(? = 'Compliant', NOW(), NULL), updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $status, (int) $cycle['id']]); return true;
}

function fpStoreRevisionEvidence(PDO $crad, int $groupId, array $file): bool
{
    if ($groupId <= 0 || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return false;
    }

    $cycle = fpGetRevisionCycle($crad, $groupId);
    if (!$cycle) return false;

    $upload = smsSecureUpload($file, [
        'subdir' => 'research_revisions/g' . $groupId,
        'max_bytes' => 20 * 1024 * 1024,
        'allowed' => smsUploadAllowedDocuments(),
        'required' => true,
    ]);
    if (empty($upload['ok'])) return false;

    $token = bin2hex(random_bytes(32));
    $stmt = $crad->prepare(
        "UPDATE research_revision_cycles
         SET original_name = ?, stored_subdir = ?, stored_name = ?, file_size = ?, file_mime = ?,
             submission_token = ?, updated_at = NOW()
         WHERE id = ?"
    );
    $stmt->execute([
        (string) ($upload['original_name'] ?? ''),
        'research_revisions/g' . $groupId,
        (string) ($upload['stored_name'] ?? basename((string) ($upload['path'] ?? ''))),
        (int) ($upload['size'] ?? 0),
        (string) ($upload['mime'] ?? ''),
        $token,
        (int) $cycle['id'],
    ]);
    return $stmt->rowCount() > 0;
}

function fpIsEligibleForFinalApproval(PDO $crad, int $groupId): bool
{
    $schedule = fpGetFinalDefenseSchedule($crad, $groupId); $panel = fpGetFinalDefensePanel($crad, $groupId);
    if (!$schedule || !$panel) return false;
    if (fpGroupNeedsFinalRevision($crad, $groupId)) {
        return (string) (fpGetRevisionCycle($crad, $groupId)['revision_status'] ?? '') === 'Compliant';
    }
    $stmt = $crad->prepare(
        "SELECT COUNT(DISTINCT rpa.panel_user_id) AS assigned_count,
                COUNT(DISTINCT CASE WHEN fde.result = 'APPROVED' THEN rpa.panel_user_id END) AS approved_count
         FROM research_panel_assignments rpa
         LEFT JOIN final_defense_evaluations fde
           ON fde.defense_schedule_id = ?
          AND fde.panel_user_id = rpa.panel_user_id
         WHERE rpa.research_group_id = ?
           AND rpa.defense_phase = ?
           AND rpa.assignment_status = 'Assigned'"
    );
    $stmt->execute([(int) $schedule['id'], $groupId, CRAD_DEFENSE_PHASE_FINAL]);
    $approval = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $assignedCount = (int) ($approval['assigned_count'] ?? 0);
    return $assignedCount > 0
        && (int) ($approval['approved_count'] ?? 0) === $assignedCount;
}

function fpGetFinalDefenseRevisionGroups(PDO $crad, int $adviserUserId, string $adviserEmail): array
{
    finalPhaseEnsureSchema($crad);
    $adviserEmail = strtolower(trim($adviserEmail));
    $stmt = $crad->prepare(
        "SELECT rg.id AS research_group_id, rg.group_number, rg.group_name, rg.research_title,
                rg.academic_year, rds.id AS defense_schedule_id, rds.defense_datetime, rds.venue,
                COUNT(DISTINCT rpa.panel_user_id) AS assigned_panel_count,
                COUNT(DISTINCT fde.panel_user_id) AS submitted_eval_count,
                COUNT(DISTINCT CASE WHEN fde.result = 'APPROVED WITH REVISION' THEN fde.panel_user_id END) AS awr_count,
                rc.revision_status, rc.opened_at, rc.updated_at AS revision_updated_at
         FROM research_groups rg
         INNER JOIN research_defense_schedules rds
           ON rds.research_group_id = rg.id
          AND LOWER(TRIM(COALESCE(rds.defense_type, ''))) = LOWER(:final_defense_type)
         INNER JOIN research_panel_assignments rpa
           ON rpa.research_group_id = rg.id
          AND rpa.defense_phase = :final_defense_phase
          AND rpa.assignment_status = 'Assigned'
         LEFT JOIN final_defense_evaluations fde
           ON fde.defense_schedule_id = rds.id
          AND fde.panel_user_id = rpa.panel_user_id
         LEFT JOIN research_revision_cycles rc
           ON rc.research_group_id = rg.id
          AND rc.defense_schedule_id = rds.id
         WHERE EXISTS (
             SELECT 1
             FROM research_adviser_assignments raa
             WHERE raa.assignment_status IN ('Assigned', 'Confirmed')
               AND (raa.research_group_id = rg.id
                    OR (raa.research_group_id IS NULL AND raa.group_number = rg.group_number))
               AND ((raa.adviser_user_id IS NOT NULL AND raa.adviser_user_id = :adviser_user_id)
                    OR (:adviser_email <> '' AND LOWER(TRIM(COALESCE(raa.adviser_email, ''))) = :adviser_email_match))
         )
         GROUP BY rg.id, rg.group_number, rg.group_name, rg.research_title, rg.academic_year,
                  rds.id, rds.defense_datetime, rds.venue, rc.revision_status, rc.opened_at, rc.updated_at
         HAVING assigned_panel_count > 0
            AND submitted_eval_count = assigned_panel_count
            AND awr_count = assigned_panel_count
         ORDER BY rds.defense_datetime DESC, rg.id DESC"
    );
    $stmt->execute([
        ':adviser_user_id' => $adviserUserId,
        ':adviser_email' => $adviserEmail,
        ':adviser_email_match' => $adviserEmail,
        ':final_defense_type' => CRAD_DEFENSE_TYPE_FINAL,
        ':final_defense_phase' => CRAD_DEFENSE_PHASE_FINAL,
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as &$row) {
        $groupId = (int) ($row['research_group_id'] ?? 0);
        fpGroupNeedsFinalRevision($crad, $groupId);
        $cycle = fpGetRevisionCycle($crad, $groupId);
        $row['revision_status'] = (string) (($cycle['revision_status'] ?? '') ?: 'Needs Revision');
        $row['opened_at'] = $cycle['opened_at'] ?? null;
        $row['revision_updated_at'] = $cycle['updated_at'] ?? null;
        $updateStmt = $crad->prepare(
            "SELECT id, update_title, submitted_at, milestone_status
             FROM research_progress_updates
             WHERE research_group_id = ?
               AND submitted_at >= ?
             ORDER BY submitted_at DESC, id DESC
             LIMIT 1"
        );
        $updateStmt->execute([$groupId, (string) ($cycle['opened_at'] ?? '1000-01-01 00:00:00')]);
        $revisionUpdate = $updateStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        $row['revision_submitted'] = $revisionUpdate !== null;
        $row['revision_update_id'] = $revisionUpdate['id'] ?? null;
        $row['revision_update_title'] = $revisionUpdate['update_title'] ?? null;
        $row['revision_submitted_at'] = $revisionUpdate['submitted_at'] ?? null;
        $row['revision_milestone_status'] = $revisionUpdate['milestone_status'] ?? null;
    }
    unset($row);
    return $rows;
}

function fpGetFinalDefenseRevisionDetail(PDO $crad, int $groupId, int $adviserUserId, string $adviserEmail): ?array
{
    $groups = fpGetFinalDefenseRevisionGroups($crad, $adviserUserId, $adviserEmail);
    $group = null;
    foreach ($groups as $candidate) {
        if ((int) ($candidate['research_group_id'] ?? 0) === $groupId) {
            $group = $candidate;
            break;
        }
    }
    if (!$group) {
        return null;
    }

    $stmt = $crad->prepare(
        "SELECT fde.panel_name, fde.panel_user_id, fde.content_score, fde.methodology_score,
                fde.references_score, fde.format_score, fde.overall_score, fde.result,
                fde.remarks, fde.submitted_at
         FROM final_defense_evaluations fde
         WHERE fde.defense_schedule_id = ?
         ORDER BY fde.panel_name ASC, fde.panel_user_id ASC"
    );
    $stmt->execute([(int) $group['defense_schedule_id']]);
    $group['panel_evaluations'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $group['revision_status'] = (string) ($group['revision_status'] ?: 'Needs Revision');
    return $group;
}

function fpSetFinalDefenseRevisionStatus(PDO $crad, int $groupId, int $adviserUserId, string $adviserEmail, string $status): bool
{
    if (!in_array($status, ['Needs Revision', 'Under Review', 'Compliant'], true)) {
        return false;
    }

    $groups = fpGetFinalDefenseRevisionGroups($crad, $adviserUserId, $adviserEmail);
    $group = null;
    foreach ($groups as $candidate) {
        if ((int) ($candidate['research_group_id'] ?? 0) === $groupId) {
            $group = $candidate;
            break;
        }
    }
    if (!$group) {
        return false;
    }

    if (in_array($status, ['Under Review', 'Compliant'], true) && empty($group['revision_submitted'])) {
        return false;
    }

    fpGroupNeedsFinalRevision($crad, $groupId);
    $stmt = $crad->prepare(
        "UPDATE research_revision_cycles
         SET revision_status = ?,
             completed_at = IF(? = 'Compliant', NOW(), NULL),
             updated_at = NOW()
         WHERE research_group_id = ?
           AND defense_schedule_id = ?"
    );
    $stmt->execute([
        $status,
        $status,
        $groupId,
        (int) $group['defense_schedule_id'],
    ]);
    return $stmt->rowCount() > 0;
}

function fpGetFinalManuscriptApproval(PDO $crad, int $groupId): ?array
{
    finalPhaseEnsureSchema($crad); $stmt = $crad->prepare("SELECT * FROM final_manuscript_approvals WHERE research_group_id = ? LIMIT 1");
    $stmt->execute([$groupId]); $row = $stmt->fetch(PDO::FETCH_ASSOC); return $row ?: null;
}

function fpIsFinalManuscriptApproved(PDO $crad, int $groupId): bool
{
    return (string) (fpGetFinalManuscriptApproval($crad, $groupId)['status'] ?? '') === 'Approved';
}

function fpNotifyFinalManuscriptApproval(PDO $crad, array $submission, string $title, string $body): void
{
    $submissionId = (int) ($submission['id'] ?? 0);
    if ($submissionId <= 0) {
        return;
    }

    try {
        $crad->prepare(
            "INSERT IGNORE INTO chapter_evaluation_notifications
                (event_key, recipient_user_id, recipient_role, recipient_email, submission_id, type, title, body, url)
             VALUES (?, ?, 'student', ?, ?, 'final_manuscript_approved', ?, ?, ?)"
        )->execute([
            'student:final_manuscript_approved:' . $submissionId,
            (int) ($submission['submitted_by_user'] ?? 0) ?: null,
            (string) ($submission['submitted_by_email'] ?? ''),
            $submissionId,
            $title,
            $body,
            BASE_URL . '/modules/student-portal/pages/final-manuscript.php',
        ]);
    } catch (Throwable $e) {
        error_log('Final manuscript approval notification failed: ' . $e->getMessage());
    }
}

function fpResearchGroupSummary(PDO $crad, int $groupId): ?array
{
    $stmt = $crad->prepare("SELECT id, group_number, COALESCE(NULLIF(group_name,''), group_number, 'Research Group') AS group_name, research_title, adviser AS adviser_name, academic_year FROM research_groups WHERE id = ? LIMIT 1");
    $stmt->execute([$groupId]); $row = $stmt->fetch(PDO::FETCH_ASSOC); return $row ?: null;
}

function fpIsAssignedAdviser(PDO $crad, int $groupId, int $userId, string $email): bool
{
    if ($groupId <= 0 || ($userId <= 0 && trim($email) === '')) {
        return false;
    }

    $stmt = $crad->prepare(
        "SELECT COUNT(*)
         FROM research_adviser_assignments
         WHERE research_group_id = ?
           AND assignment_status IN ('Assigned', 'Confirmed')
         AND ((adviser_user_id IS NOT NULL AND adviser_user_id = ?)
             OR (? <> '' AND LOWER(TRIM(COALESCE(adviser_email, ''))) = LOWER(?)))"
    );
    $stmt->execute([$groupId, $userId, trim($email), trim($email)]);
    return (int) $stmt->fetchColumn() > 0;
}
