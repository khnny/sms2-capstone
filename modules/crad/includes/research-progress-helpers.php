<?php
/**
 * Research Implementation & Progress Monitoring
 * Helper Functions for Duplicate Prevention and Database Operations
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/uploads.php';

function rpNormalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

function rpCurrentUserEmail(): string
{
    $email = rpNormalizeEmail((string) ($_SESSION['user_email'] ?? ''));
    if ($email !== '') {
        return $email;
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0 || !function_exists('db')) {
        return '';
    }

    try {
        $sms = db();
        if (!$sms) {
            return '';
        }
        $stmt = $sms->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        return rpNormalizeEmail((string) ($stmt->fetchColumn() ?: ''));
    } catch (Throwable $e) {
        error_log('Unable to resolve current user email: ' . $e->getMessage());
        return '';
    }
}

function rpAdviserAssignmentMatchSql(string $assignmentAlias = 'raa', string $groupAlias = 'rg'): string
{
    return "((
                {$assignmentAlias}.research_group_id IS NOT NULL
            AND {$assignmentAlias}.research_group_id = {$groupAlias}.id
        ) OR (
                {$assignmentAlias}.research_group_id IS NULL
            AND {$assignmentAlias}.group_number IS NOT NULL
            AND {$assignmentAlias}.group_number = {$groupAlias}.group_number
        ))";
}

function rpAdviserIdentitySql(string $assignmentAlias = 'raa'): string
{
    return "((
                {$assignmentAlias}.adviser_user_id IS NOT NULL
            AND {$assignmentAlias}.adviser_user_id = :adviser_user_id
        ) OR (
                :adviser_email <> ''
            AND TRIM(COALESCE({$assignmentAlias}.adviser_email, '')) <> ''
            AND LOWER(TRIM({$assignmentAlias}.adviser_email)) = :adviser_email_match
        ))";
}

function rpActiveAdviserAssignmentStatusSql(string $assignmentAlias = 'raa'): string
{
    return "{$assignmentAlias}.assignment_status IN ('Assigned', 'Confirmed')";
}

function rpAdviserIdentityParams(int $adviserUserId, string $adviserEmail): array
{
    $email = rpNormalizeEmail($adviserEmail);
    return [
        ':adviser_user_id' => $adviserUserId,
        ':adviser_email' => $email,
        ':adviser_email_match' => $email,
    ];
}

function rpDefaultMilestoneRows(): array
{
    return [
        ['id' => null, 'milestone_name' => 'Chapter 1', 'milestone_order' => 1, 'description' => 'Introduction and Background', 'status' => 'Not Started', 'progress_percentage' => 0, 'pending_count' => 0, 'update_count' => 0, 'last_update_at' => null, 'start_date' => null, 'target_date' => null, 'completed_at' => null, 'researcher_notes' => '', 'adviser_remarks' => ''],
        ['id' => null, 'milestone_name' => 'Chapter 2', 'milestone_order' => 2, 'description' => 'Review of Related Literature', 'status' => 'Not Started', 'progress_percentage' => 0, 'pending_count' => 0, 'update_count' => 0, 'last_update_at' => null, 'start_date' => null, 'target_date' => null, 'completed_at' => null, 'researcher_notes' => '', 'adviser_remarks' => ''],
        ['id' => null, 'milestone_name' => 'Chapter 3', 'milestone_order' => 3, 'description' => 'Methodology', 'status' => 'Not Started', 'progress_percentage' => 0, 'pending_count' => 0, 'update_count' => 0, 'last_update_at' => null, 'start_date' => null, 'target_date' => null, 'completed_at' => null, 'researcher_notes' => '', 'adviser_remarks' => ''],
        ['id' => null, 'milestone_name' => 'Chapter 4', 'milestone_order' => 4, 'description' => 'Results / System Design and Development', 'status' => 'Not Started', 'progress_percentage' => 0, 'pending_count' => 0, 'update_count' => 0, 'last_update_at' => null, 'start_date' => null, 'target_date' => null, 'completed_at' => null, 'researcher_notes' => '', 'adviser_remarks' => ''],
        ['id' => null, 'milestone_name' => 'Chapter 5', 'milestone_order' => 5, 'description' => 'Summary, Conclusions and Recommendations', 'status' => 'Not Started', 'progress_percentage' => 0, 'pending_count' => 0, 'update_count' => 0, 'last_update_at' => null, 'start_date' => null, 'target_date' => null, 'completed_at' => null, 'researcher_notes' => '', 'adviser_remarks' => ''],
        ['id' => null, 'milestone_name' => 'System Development', 'milestone_order' => 6, 'description' => 'System Implementation', 'status' => 'Not Started', 'progress_percentage' => 0, 'pending_count' => 0, 'update_count' => 0, 'last_update_at' => null, 'start_date' => null, 'target_date' => null, 'completed_at' => null, 'researcher_notes' => '', 'adviser_remarks' => ''],
        ['id' => null, 'milestone_name' => 'Testing', 'milestone_order' => 7, 'description' => 'Testing and Quality Assurance', 'status' => 'Not Started', 'progress_percentage' => 0, 'pending_count' => 0, 'update_count' => 0, 'last_update_at' => null, 'start_date' => null, 'target_date' => null, 'completed_at' => null, 'researcher_notes' => '', 'adviser_remarks' => ''],
        ['id' => null, 'milestone_name' => 'Documentation', 'milestone_order' => 8, 'description' => 'Final Documentation and Report', 'status' => 'Not Started', 'progress_percentage' => 0, 'pending_count' => 0, 'update_count' => 0, 'last_update_at' => null, 'start_date' => null, 'target_date' => null, 'completed_at' => null, 'researcher_notes' => '', 'adviser_remarks' => ''],
    ];
}

function rpGetResearchPlan(PDO $crad, int $groupId): ?array
{
    if ($groupId <= 0) {
        return null;
    }

    $stmt = $crad->prepare('SELECT * FROM research_plans WHERE research_group_id = ? LIMIT 1');
    $stmt->execute([$groupId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    return $plan ?: null;
}

function rpGroupAcademicPhase(PDO $crad, int $groupId): string
{
    $plan = rpGetResearchPlan($crad, $groupId);
    if (!$plan || !rpIsFirstSemesterComplete($crad, (int) $plan['id'], $groupId)) {
        return CRAD_PHASE_1ST_SEM;
    }

    return CRAD_PHASE_2ND_SEM;
}

function _rpAddColumnIfMissing(PDO $crad, string $table, string $column, string $definition): void
{
    try {
        $stmt = $crad->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?"
        );
        $stmt->execute([$table, $column]);
        if ((int) $stmt->fetchColumn() === 0) {
            $crad->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        }
    } catch (Throwable $e) {
        error_log("_rpAddColumnIfMissing($table.$column): " . $e->getMessage());
    }
}

function rpEnsureFinalDefenseRecommendationSchema(PDO $crad): void
{
    _rpAddColumnIfMissing($crad, 'research_plans', 'final_defense_recommended', 'TINYINT(1) NOT NULL DEFAULT 0');
    _rpAddColumnIfMissing($crad, 'research_plans', 'final_defense_recommended_by', 'INT UNSIGNED DEFAULT NULL');
    _rpAddColumnIfMissing($crad, 'research_plans', 'final_defense_recommended_by_name', 'VARCHAR(150) DEFAULT NULL');
    _rpAddColumnIfMissing($crad, 'research_plans', 'final_defense_recommended_at', 'DATETIME DEFAULT NULL');
    _rpAddColumnIfMissing($crad, 'research_plans', 'final_defense_recommendation_remarks', 'TEXT DEFAULT NULL');
}

function rpMarkFinalDefenseRecommended(
    PDO $crad,
    int $groupId,
    int $adviserUserId,
    string $adviserName,
    string $remarks
): bool {
    if ($groupId <= 0 || $adviserUserId <= 0 || trim($adviserName) === '') {
        return false;
    }

    rpEnsureFinalDefenseRecommendationSchema($crad);
    $stmt = $crad->prepare(
        "UPDATE research_plans
         SET final_defense_recommended = 1,
             final_defense_recommended_by = ?,
             final_defense_recommended_by_name = ?,
             final_defense_recommended_at = NOW(),
             final_defense_recommendation_remarks = ?
         WHERE research_group_id = ?"
    );
    $stmt->execute([$adviserUserId, trim($adviserName), trim($remarks), $groupId]);
    return $stmt->rowCount() >= 0;
}

function rpRevokeFinalDefenseRecommendation(PDO $crad, int $groupId): bool
{
    if ($groupId <= 0) {
        return false;
    }

    rpEnsureFinalDefenseRecommendationSchema($crad);
    $stmt = $crad->prepare(
        "UPDATE research_plans
         SET final_defense_recommended = 0,
             final_defense_recommended_by = NULL,
             final_defense_recommended_by_name = NULL,
             final_defense_recommended_at = NULL,
             final_defense_recommendation_remarks = NULL
         WHERE research_group_id = ?"
    );
    $stmt->execute([$groupId]);
    return $stmt->rowCount() >= 0;
}

function rpIsFinalDefenseRecommended(PDO $crad, int $groupId): bool
{
    $recommendation = rpGetFinalDefenseRecommendation($crad, $groupId);
    return !empty($recommendation['final_defense_recommended']);
}

function rpGetFinalDefenseRecommendation(PDO $crad, int $groupId): ?array
{
    if ($groupId <= 0) {
        return null;
    }

    rpEnsureFinalDefenseRecommendationSchema($crad);
    $stmt = $crad->prepare(
        "SELECT final_defense_recommended,
                final_defense_recommended_by,
                final_defense_recommended_by_name,
                final_defense_recommended_at,
                final_defense_recommendation_remarks
         FROM research_plans
         WHERE research_group_id = ?
         LIMIT 1"
    );
    $stmt->execute([$groupId]);
    $recommendation = $stmt->fetch(PDO::FETCH_ASSOC);
    return $recommendation ?: null;
}

function rpChapterMilestoneNames(): array
{
    return [
        1 => 'Chapter 1',
        2 => 'Chapter 2',
        3 => 'Chapter 3',
    ];
}

function rpMilestoneChapterNumber(array $milestone): ?int
{
    $order = (int) ($milestone['milestone_order'] ?? 0);
    $name = strtolower(trim((string) ($milestone['milestone_name'] ?? '')));

    if ($order >= 1 && $order <= 5 && preg_match('/^chapter\s+' . $order . '\b/i', (string) ($milestone['milestone_name'] ?? ''))) {
        return $order;
    }

    if (preg_match('/^chapter\s+([1-5])\b/i', $name, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

function rpChapterSubmissionProgressState(PDO $crad, int $groupId, int $chapter): array
{
    $empty = [
        'progress_percentage' => 0.0,
        'status' => 'Not Started',
        'chapter_status' => null,
        'latest_submission_id' => null,
        'latest_version_number' => null,
        'latest_submitted_at' => null,
        'latest_updated_at' => null,
    ];

    if ($groupId <= 0 || $chapter < 1 || $chapter > 3) {
        return $empty;
    }

    try {
        $stmt = $crad->prepare(
            "SELECT id, version_number, status, submitted_at, updated_at
             FROM chapter_submissions
             WHERE research_group_id = :group_id
               AND chapter_number = :chapter
             ORDER BY version_number DESC, id DESC"
        );
        $stmt->execute([':group_id' => $groupId, ':chapter' => $chapter]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Chapter milestone sync lookup failed: ' . $e->getMessage());
        return $empty;
    }

    if (!$submissions) {
        return $empty;
    }

    $latest = $submissions[0];
    $highestProgress = 0.0;
    foreach ($submissions as $submission) {
        $status = (string) ($submission['status'] ?? '');
        $highestProgress = max($highestProgress, match ($status) {
            'Accepted' => 100.0,
            'Under Review', 'Needs Revision' => 66.0,
            'Submitted' => 33.0,
            default => 0.0,
        });
    }

    $latestStatus = (string) ($latest['status'] ?? '');
    $progress = match ($latestStatus) {
        'Accepted' => 100.0,
        'Under Review', 'Needs Revision' => 66.0,
        'Submitted' => max(33.0, min(66.0, $highestProgress)),
        default => 0.0,
    };

    $status = match ($latestStatus) {
        'Accepted' => 'Completed',
        'Needs Revision' => 'Revision Requested',
        'Submitted', 'Under Review' => 'In Progress',
        default => 'Not Started',
    };

    return [
        'progress_percentage' => $progress,
        'status' => $status,
        'chapter_status' => $latestStatus ?: null,
        'latest_submission_id' => (int) ($latest['id'] ?? 0) ?: null,
        'latest_version_number' => (int) ($latest['version_number'] ?? 0) ?: null,
        'latest_submitted_at' => $latest['submitted_at'] ?? null,
        'latest_updated_at' => $latest['updated_at'] ?? null,
    ];
}

function rpApplyChapterMilestoneOverrides(PDO $crad, int $groupId, array $milestones): array
{
    // Ensure the panel_remarks column exists (idempotent, runs once per request).
    rpEnsurePanelRemarksColumn($crad);

    // Fetch the group's official Pre-Oral panel result once for the whole milestone list.
    $panelRecord   = rpGetGroupPanelApproval($crad, $groupId);
    $panelApproved = ($panelRecord !== null && ($panelRecord['final_result'] ?? '') === 'APPROVED');

    // If the panel has officially approved, write the final state to Chapter 1-3 DB rows
    // before we read them back into the milestone array. This is the idempotent sync point
    // that fires on every milestone read — real-time, no separate cron/webhook needed.
    if ($panelApproved) {
        rpSyncChapterMilestonesFromPanelApproval($crad, $groupId, $panelRecord);
    }

    $plan = rpGetResearchPlan($crad, $groupId);
    if ($plan) {
        rpSetCurrentStageIfFirstSemesterComplete($crad, (int) $plan['id'], $groupId);
    }

    foreach ($milestones as &$milestone) {
        $chapter = rpMilestoneChapterNumber($milestone);
        $milestone['is_chapter_synced'] = false;
        $milestone['panel_approved']    = false;
        $milestone['panel_remarks']     = null;

        if ($chapter) {
            $milestone['chapter_number'] = $chapter;

            // ── Final panel-approved path ────────────────────────────────
            // Annotate Chapter 1-3 milestones with the live panel approval state
            // so both the PHP templates and the JS polling response carry this data.
            if ($panelApproved && in_array($chapter, [1, 2, 3], true)) {
                $milestone['panel_approved']    = true;
                $milestone['panel_remarks']     = $panelRecord['panel_remarks'];
                $milestone['is_chapter_synced'] = true;
                // progress_percentage will be forced to 100 by rpNormalizeApprovedMilestoneProgress
                // via the DB sync above — no further override needed here.

            // ── Intermediate progress path (no panel approval yet) ───────
            // Combine two independent tracks and use the higher of the two:
            //   Track A — chapter_submissions / Grammarian review
            //             (rpChapterSubmissionProgressState: 0 / 33 / 66 / 100)
            //   Track B — research_milestones.status / Adviser review
            //             (rpMilestoneStatusToProgress: 0 / 25 / 50 / 75)
            //
            // Taking the max means whichever track is more advanced wins,
            // so neither track can regress the displayed progress.
            // This override is IN-MEMORY ONLY — no DB writes here.
            } elseif (in_array($chapter, [1, 2, 3], true)) {
                $trackA = rpChapterSubmissionProgressState($crad, $groupId, $chapter);
                $trackB = rpMilestoneStatusToProgress((string) ($milestone['status'] ?? 'Not Started'));

                $intermediate = max((float) $trackA['progress_percentage'], $trackB);

                // Clamp: never exceed 90% without official panel approval so 100%
                // remains exclusively the panel-APPROVED signal.
                if ($intermediate > 0.0) {
                    $milestone['progress_percentage'] = min(90.0, $intermediate);
                }
            }
        }

        if ((string) ($milestone['status'] ?? '') === 'Approved') {
            $milestone['progress_percentage'] = 100.0;
        }
    }
    unset($milestone);

    return $milestones;
}

/**
 * Map a research_milestones.status value to an intermediate progress percentage
 * for Chapter 1-3 milestones when the Pre-Oral Panel has not yet approved.
 *
 * Deterministic — same input always produces the same output.
 * Returns a float in [0, 75]:
 *   Not Started          →  0%
 *   In Progress          → 25%
 *   Submitted for Review → 50%   (adviser received the submission)
 *   Revision Requested   → 50%   (adviser is engaged; progress acknowledged)
 *   Approved             → 75%   (adviser approved; waiting for panel)
 *
 * 100% is exclusively reserved for the final Panel APPROVED result and is
 * enforced by rpSyncChapterMilestonesFromPanelApproval / rpNormalizeApprovedMilestoneProgress.
 *
 * @param string $status  Value of research_milestones.status
 * @return float
 */
function rpMilestoneStatusToProgress(string $status): float
{
    return match ($status) {
        'In Progress'          => 25.0,
        'Submitted for Review' => 50.0,
        'Revision Requested'   => 50.0,
        'Approved', 'Completed'=> 75.0,
        default                => 0.0,   // 'Not Started' and any unknown value
    };
}

function rpMilestonesOverallProgress(array $milestones): float
{
    if (!$milestones) {
        return 0.0;
    }

    $total = 0.0;
    foreach ($milestones as $milestone) {
        $total += (float) ($milestone['progress_percentage'] ?? 0);
    }

    return round($total / count($milestones), 2);
}

function rpApplySyncedPlanProgress(array $plan, array $milestones): array
{
    $plan['overall_progress'] = rpMilestonesOverallProgress($milestones);
    return $plan;
}

function rpGetMilestonesForPlan(PDO $crad, ?int $planId, ?int $groupId = null): array
{
    if (!$planId) {
        $milestones = rpDefaultMilestoneRows();
        return $groupId ? rpApplyChapterMilestoneOverrides($crad, $groupId, $milestones) : $milestones;
    }

    // Backfill Chapter 4 & 5 for existing plans that were created before they were added.
    // INSERT IGNORE means this is a no-op once the rows already exist.
    rpEnsureChapter4And5Milestones($crad, $planId);

    $stmt = $crad->prepare("
        SELECT *
        FROM research_milestones
        WHERE research_plan_id = ?
        ORDER BY milestone_order ASC
    ");
    $stmt->execute([$planId]);
    $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $milestones = $milestones ?: rpDefaultMilestoneRows();
    $milestones = rpNormalizeApprovedMilestoneProgress($milestones);
    return $groupId ? rpApplyChapterMilestoneOverrides($crad, $groupId, $milestones) : $milestones;
}

function rpGetMilestonesWithUpdateStats(PDO $crad, ?int $planId, ?int $groupId = null): array
{
    if (!$planId) {
        $milestones = rpDefaultMilestoneRows();
        return $groupId ? rpApplyChapterMilestoneOverrides($crad, $groupId, $milestones) : $milestones;
    }

    // Backfill Chapter 4 & 5 for existing plans that were created before they were added.
    // INSERT IGNORE means this is a no-op once the rows already exist.
    rpEnsureChapter4And5Milestones($crad, $planId);

    $stmt = $crad->prepare(
        "SELECT rm.*,
                (SELECT COUNT(*) FROM research_progress_updates rpu WHERE rpu.milestone_id = rm.id) AS update_count,
                (SELECT COUNT(*) FROM research_progress_updates rpu WHERE rpu.milestone_id = rm.id
                   AND rpu.milestone_status = 'Submitted for Review') AS pending_count,
                (SELECT rpu.submitted_at FROM research_progress_updates rpu WHERE rpu.milestone_id = rm.id
                 ORDER BY rpu.submitted_at DESC LIMIT 1) AS last_update_at
         FROM research_milestones rm
         WHERE rm.research_plan_id = ?
         ORDER BY rm.milestone_order ASC"
    );
    $stmt->execute([$planId]);
    $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: rpDefaultMilestoneRows();

    $milestones = rpNormalizeApprovedMilestoneProgress($milestones);
    return $groupId ? rpApplyChapterMilestoneOverrides($crad, $groupId, $milestones) : $milestones;
}

function rpNormalizeApprovedMilestoneProgress(array $milestones): array
{
    foreach ($milestones as &$milestone) {
        if ((string) ($milestone['status'] ?? '') === 'Approved') {
            $milestone['progress_percentage'] = 100.0;
        }
    }
    unset($milestone);

    return $milestones;
}

function rpSyncChapterMilestonesFromSubmissions(PDO $crad, int $groupId): void
{
    // Chapter 1-3 research progress is adviser-review controlled now.
    // Official Grammarian submissions must not drive Research Development state.
    return;
}

function rpChapterControlledMilestoneState(PDO $crad, int $milestoneId): ?array
{
    return null;
}

function rpEnsureProgressAttachmentSchema(PDO $crad): void
{
    $crad->exec(
        "CREATE TABLE IF NOT EXISTS research_progress_attachments (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            progress_update_id INT UNSIGNED NOT NULL,
            file_name VARCHAR(300) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type VARCHAR(100) NOT NULL DEFAULT '',
            file_size INT UNSIGNED NOT NULL DEFAULT 0,
            uploaded_by INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_rpa_update (progress_update_id),
            KEY idx_rpa_uploaded (uploaded_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function rpLatestAttachmentForUpdate(PDO $crad, int $progressUpdateId): ?array
{
    if ($progressUpdateId <= 0) {
        return null;
    }
    rpEnsureProgressAttachmentSchema($crad);
    $stmt = $crad->prepare(
        "SELECT *
         FROM research_progress_attachments
         WHERE progress_update_id = ?
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([$progressUpdateId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rpProgressAttachmentUrl(int $attachmentId, bool $download = false): string
{
    return BASE_URL . '/modules/crad/api/progress-document.php?id=' . $attachmentId . ($download ? '&download=1' : '');
}

function rpAdviserApprovedChapter(PDO $crad, int $groupId, int $chapter): ?array
{
    if ($groupId <= 0 || $chapter < 1 || $chapter > 3) {
        return null;
    }

    $stmt = $crad->prepare(
        "SELECT rpu.id AS progress_update_id, rpu.milestone_id, rpu.research_group_id,
                rpu.new_progress, rpu.submitted_at, rpu.updated_at,
                rpf.id AS feedback_id, rpf.adviser_user_id AS approved_by,
                rpf.adviser_name AS approved_by_name, rpf.created_at AS approved_at
         FROM research_progress_updates rpu
         INNER JOIN research_milestones rm ON rm.id = rpu.milestone_id
         INNER JOIN research_progress_feedback rpf ON rpf.id = (
            SELECT rpf2.id
            FROM research_progress_feedback rpf2
            WHERE rpf2.progress_update_id = rpu.id
              AND rpf2.feedback_type = 'Progress Approved'
              AND rpf2.new_milestone_status = 'Approved'
            ORDER BY rpf2.created_at DESC, rpf2.id DESC
            LIMIT 1
         )
         WHERE rpu.research_group_id = :gid
           AND rpu.milestone_status IN ('Submitted for Review', 'Approved')
           AND rm.milestone_order = :chapter
           AND LOWER(TRIM(rm.milestone_name)) = :chapter_name
           AND rpu.id = (
                SELECT rpu2.id
                FROM research_progress_updates rpu2
                INNER JOIN research_milestones rm2 ON rm2.id = rpu2.milestone_id
                WHERE rpu2.research_group_id = :gid2
                  AND rm2.milestone_order = :chapter2
                  AND LOWER(TRIM(rm2.milestone_name)) = :chapter_name2
                ORDER BY rpu2.submitted_at DESC, rpu2.id DESC
                LIMIT 1
           )
         LIMIT 1"
    );
    $chapterName = 'chapter ' . $chapter;
    $stmt->execute([
        ':gid' => $groupId,
        ':chapter' => $chapter,
        ':chapter_name' => $chapterName,
        ':gid2' => $groupId,
        ':chapter2' => $chapter,
        ':chapter_name2' => $chapterName,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rpPriorMilestonesApproved(PDO $crad, int $planId, int $order): bool
{
    if ($order <= 1) {
        return true;
    }
    if ($planId <= 0) {
        return false;
    }

    $stmt = $crad->prepare(
        "SELECT COUNT(*)
         FROM research_milestones
         WHERE research_plan_id = ?
           AND milestone_order < ?
           AND status NOT IN ('Approved', 'Completed')"
    );
    $stmt->execute([$planId, $order]);

    return (int) $stmt->fetchColumn() === 0;
}

function rpChapterSubmissionEligibility(PDO $crad, int $groupId): array
{
    $eligibility = [];
    foreach (rpChapterMilestoneNames() as $chapter => $label) {
        // A chapter becomes submittable in the Submission Form as soon as the
        // Adviser approves that chapter's latest Progress Update (Research
        // Development review). Each chapter is independent — real-time from the
        // database, no sequential/panel-dependent prerequisite.
        // chapterSubmitDocument() enforces the exact same rule server-side via
        // rpAdviserApprovedChapter(), so UI and validation always agree.
        $approval = rpAdviserApprovedChapter($crad, $groupId, (int) $chapter);
        $eligible = (bool) $approval;
        $eligibility[(int) $chapter] = [
            'chapter' => (int) $chapter,
            'label' => $label,
            'eligible' => $eligible,
            'message' => $approval ? 'Ready for Submission' : 'Adviser Approval Required',
            'approval' => $approval,
        ];
    }
    return $eligibility;
}

function rpGetAssignedResearchGroupsForAdviser(PDO $crad, int $adviserUserId, string $adviserEmail): array
{
    if ($adviserUserId <= 0 && rpNormalizeEmail($adviserEmail) === '') {
        return [];
    }

    $assignmentMatch = rpAdviserAssignmentMatchSql('raa2', 'rg');
    $identitySql = rpAdviserIdentitySql('raa2');
    $statusSql = rpActiveAdviserAssignmentStatusSql('raa2');

    $stmt = $crad->prepare("
        SELECT
            rg.id,
            rg.group_number,
            rg.group_name,
            rg.research_title,
            rg.academic_year,
            rg.status AS group_status,
            raa.assignment_status,
            raa.adviser_user_id,
            raa.adviser_name,
            raa.adviser_email,
            rp.id AS plan_id,
            COALESCE(rp.overall_progress, 0) AS overall_progress,
            COALESCE(rp.current_stage, 'Planning') AS current_stage,
            rp.status AS plan_status,
            rp.updated_at AS plan_updated_at,
            (SELECT COUNT(*)
               FROM research_progress_updates rpu
              WHERE rpu.research_group_id = rg.id
                AND rpu.milestone_status = 'Submitted for Review') AS pending_reviews,
            (SELECT COUNT(*)
               FROM research_progress_updates rpu2
              WHERE rpu2.research_group_id = rg.id) AS update_count,
            (SELECT MAX(rpu3.submitted_at)
               FROM research_progress_updates rpu3
              WHERE rpu3.research_group_id = rg.id) AS last_update_at,
            (SELECT COUNT(*)
               FROM research_milestones rm2
              WHERE rm2.research_plan_id = rp.id) AS total_milestones,
            (SELECT COUNT(*)
               FROM research_milestones rm3
              WHERE rm3.research_plan_id = rp.id
                AND rm3.status IN ('Approved','Completed')) AS done_milestones
        FROM research_groups rg
        INNER JOIN research_adviser_assignments raa ON raa.id = (
            SELECT raa2.id
            FROM research_adviser_assignments raa2
            WHERE {$assignmentMatch}
              AND {$identitySql}
              AND {$statusSql}
            ORDER BY (raa2.assignment_status = 'Confirmed') DESC,
                     (raa2.assignment_status = 'Assigned') DESC,
                     raa2.updated_at DESC,
                     raa2.id DESC
            LIMIT 1
        )
        LEFT JOIN research_plans rp ON rp.research_group_id = rg.id
        WHERE rg.status = 'Approved'
        ORDER BY rg.date_assigned DESC, rg.id DESC
    ");

    $stmt->execute(rpAdviserIdentityParams($adviserUserId, $adviserEmail));
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($groups as &$group) {
        $group['milestones'] = rpGetMilestonesForPlan(
            $crad,
            !empty($group['plan_id']) ? (int) $group['plan_id'] : null,
            (int) $group['id']
        );
        $group['overall_progress'] = rpMilestonesOverallProgress($group['milestones']);
        $group['total_milestones'] = count($group['milestones']);
        $group['done_milestones'] = count(array_filter(
            $group['milestones'],
            static fn(array $milestone): bool => in_array((string) ($milestone['status'] ?? ''), ['Approved', 'Completed'], true)
        ));
    }
    unset($group);

    return $groups;
}

function rpGetAssignedResearchGroupForAdviser(PDO $crad, int $adviserUserId, string $adviserEmail, string $groupNumber): ?array
{
    $groupNumber = trim($groupNumber);
    if ($groupNumber === '') {
        return null;
    }

    foreach (rpGetAssignedResearchGroupsForAdviser($crad, $adviserUserId, $adviserEmail) as $group) {
        if ((string) ($group['group_number'] ?? '') === $groupNumber) {
            return $group;
        }
    }

    return null;
}

function rpClearActiveAdviserResearchGroup(): void
{
    unset(
        $_SESSION['active_research_group_id'],
        $_SESSION['active_research_group_number']
    );
}

function rpSetActiveAdviserResearchGroup(array $group): void
{
    $_SESSION['active_research_group_id'] = (int) ($group['id'] ?? 0);
    $_SESSION['active_research_group_number'] = (string) ($group['group_number'] ?? '');
}

function rpResolveAdviserResearchGroupContext(PDO $crad, int $adviserUserId, string $adviserEmail, ?string $requestedGroupNumber = null): array
{
    $groups = rpGetAssignedResearchGroupsForAdviser($crad, $adviserUserId, $adviserEmail);
    $requestedGroupNumber = trim((string) $requestedGroupNumber);

    if (!$groups) {
        rpClearActiveAdviserResearchGroup();
        return ['status' => 'no_groups', 'group' => null, 'groups' => []];
    }

    if ($requestedGroupNumber !== '') {
        foreach ($groups as $group) {
            if ((string) ($group['group_number'] ?? '') === $requestedGroupNumber) {
                rpSetActiveAdviserResearchGroup($group);
                return ['status' => 'ok', 'group' => $group, 'groups' => $groups];
            }
        }

        rpClearActiveAdviserResearchGroup();
        return ['status' => 'invalid_requested', 'group' => null, 'groups' => $groups];
    }

    $activeId = (int) ($_SESSION['active_research_group_id'] ?? 0);
    if ($activeId > 0) {
        foreach ($groups as $group) {
            if ((int) ($group['id'] ?? 0) === $activeId) {
                rpSetActiveAdviserResearchGroup($group);
                return ['status' => 'ok', 'group' => $group, 'groups' => $groups];
            }
        }
        rpClearActiveAdviserResearchGroup();
    }

    if (count($groups) === 1) {
        rpSetActiveAdviserResearchGroup($groups[0]);
        return ['status' => 'ok', 'group' => $groups[0], 'groups' => $groups];
    }

    return ['status' => 'needs_selection', 'group' => null, 'groups' => $groups];
}

function rpRenderAdviserGroupSelector(array $groups, string $title = 'Select Research Group', string $description = 'Choose a research group to continue.'): void
{
    $currentPath = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?');
    $action = htmlspecialchars($currentPath ?: '', ENT_QUOTES);
    ?>
    <div class="glass-dashboard"><div class="glass-board">
        <div class="glass-panel"><div class="glass-panel-body rm-empty">
            <div class="rm-empty-icon"><?= smsIcon('layer-group', ['style' => 'color:#2563eb;']) ?></div>
            <h6><?= htmlspecialchars($title) ?></h6>
            <p><?= htmlspecialchars($description) ?></p>
            <form method="GET" action="<?= $action ?>" class="mt-3" style="max-width:520px;margin:0 auto;">
                <div class="input-group">
                    <select name="group" class="form-select" required>
                        <option value="">Select assigned group</option>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= htmlspecialchars((string) $group['group_number'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars((string) $group['group_name']) ?>
                                (<?= htmlspecialchars((string) $group['group_number']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <?= smsIcon('arrow-right', ['class' => 'me-1']) ?>Open
                    </button>
                </div>
            </form>
        </div></div>
    </div></div>
    <?php
}

function rpRenderAdviserNoGroupsState(): void
{
    ?>
    <div class="glass-dashboard"><div class="glass-board">
        <div class="glass-panel"><div class="glass-panel-body rm-empty">
            <div class="rm-empty-icon"><?= smsIcon('users') ?></div>
            <h6>No Research Groups Assigned</h6>
            <p>You currently have no research groups assigned for monitoring.</p>
        </div></div>
    </div></div>
    <?php
}

function rpRenderAdviserGroupAccessDenied(): void
{
    ?>
    <div class="glass-dashboard"><div class="glass-board">
        <div class="glass-panel"><div class="glass-panel-body rm-empty">
            <div class="rm-empty-icon"><?= smsIcon('ban', ['style' => 'color:#ef4444;']) ?></div>
            <h6>Access Denied</h6>
            <p>This research group is not assigned to you or is no longer available.</p>
            <a href="<?= BASE_URL ?>/modules/faculty/pages/my-research-groups.php" class="btn btn-primary mt-3">
                <?= smsIcon('users', ['class' => 'me-2']) ?>View My Research Groups
            </a>
        </div></div>
    </div></div>
    <?php
}

function rpGetProgressUpdateForAdviser(PDO $crad, int $progressUpdateId, int $adviserUserId, string $adviserEmail): ?array
{
    if ($progressUpdateId <= 0) {
        return null;
    }

    $assignmentMatch = rpAdviserAssignmentMatchSql('raa2', 'rg');
    $identitySql = rpAdviserIdentitySql('raa2');
    $statusSql = rpActiveAdviserAssignmentStatusSql('raa2');

    $stmt = $crad->prepare("
        SELECT
            rpu.*,
            rg.group_number,
            rg.leader_id
        FROM research_progress_updates rpu
        INNER JOIN research_groups rg ON rg.id = rpu.research_group_id
        INNER JOIN research_adviser_assignments raa ON raa.id = (
            SELECT raa2.id
            FROM research_adviser_assignments raa2
            WHERE {$assignmentMatch}
              AND {$identitySql}
              AND {$statusSql}
            ORDER BY (raa2.assignment_status = 'Confirmed') DESC,
                     (raa2.assignment_status = 'Assigned') DESC,
                     raa2.updated_at DESC,
                     raa2.id DESC
            LIMIT 1
        )
        WHERE rpu.id = :progress_update_id
        LIMIT 1
    ");

    $params = rpAdviserIdentityParams($adviserUserId, $adviserEmail);
    $params[':progress_update_id'] = $progressUpdateId;
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Get the student's research group — but ONLY if it qualifies for the
 * Capstone Group/Student Registry (fully approved title, all three signatures,
 * active coordinator assignment, and an assigned adviser).
 *
 * This is the single authoritative gate used by every Research Development
 * page in the student portal.  If the group does not appear in the Registry,
 * NULL is returned and the calling page must deny access.
 *
 * @param PDO    $crad          CRAD database connection
 * @param string $studentId     Student ID from session  (e.g. "S230000001")
 * @param int    $studentUserId User ID from session     (sms2_db.users.id)
 * @return array|null           research_groups row (+ adviser info) or null
 */
function rpGetRegisteredResearchGroup(PDO $crad, string $studentId, int $studentUserId): ?array
{
    $stmt = $crad->prepare("
        SELECT
            rg.*,
            COALESCE(raa.adviser_user_id, NULL)   AS adviser_user_id,
            COALESCE(raa.adviser_name,   '')       AS adviser_name,
            COALESCE(raa.adviser_email,  '')       AS adviser_email
        FROM research_groups rg

        /* Title Approval must be fully approved with all three signatures */
        JOIN title_approvals t ON t.id = rg.title_approval_id

        /* Active research coordinator assignment */
        JOIN research_coordinator_assignments ca ON ca.id = (
            SELECT ca2.id
            FROM   research_coordinator_assignments ca2
            WHERE  ca2.status = 'Active'
              AND  (
                    ca2.research_group_id = rg.id
                 OR (ca2.research_group_id IS NULL AND ca2.group_number = rg.group_number)
              )
            ORDER BY ca2.updated_at DESC, ca2.id DESC
            LIMIT 1
        )

        /* Adviser assignment (any status — Assigned or Confirmed) */
        LEFT JOIN research_adviser_assignments raa ON raa.id = (
            SELECT aa2.id
            FROM   research_adviser_assignments aa2
            WHERE  (
                    aa2.research_group_id = rg.id
                 OR (aa2.research_group_id IS NULL AND aa2.group_number = rg.group_number)
              )
            ORDER BY (aa2.assignment_status = 'Confirmed') DESC,
                     (aa2.assignment_status = 'Assigned')  DESC,
                     aa2.updated_at DESC, aa2.id DESC
            LIMIT 1
        )

        WHERE rg.title_approval_id IS NOT NULL

          /* All three approval statuses must be 'Approved' */
          AND t.status               = 'Approved'
          AND t.coordinator_status   = 'Approved'
          AND t.crad_status          = 'Approved'

          /* All three digital signatures must be present */
          AND t.adviser_signature_data     IS NOT NULL
          AND t.adviser_signature_data     <> ''
          AND t.coordinator_signature_data IS NOT NULL
          AND t.coordinator_signature_data <> ''
          AND t.crad_signature_data        IS NOT NULL
          AND t.crad_signature_data        <> ''

          /* Non-empty required fields */
          AND TRIM(COALESCE(rg.research_title, '')) <> ''
          AND TRIM(COALESCE(rg.academic_year,  '')) <> ''
          AND (
               TRIM(COALESCE(rg.college_dept, ''))  <> ''
            OR TRIM(COALESCE(t.department,    ''))   <> ''
          )

          /* Must be this student's group (as leader) */
          AND (
               rg.leader_id = ?
            OR rg.leader_id = (
                 SELECT student_id
                 FROM   sms2_db.users
                 WHERE  id = ?
                 LIMIT  1
               )
          )

        ORDER BY rg.date_assigned DESC
        LIMIT 1
    ");

    $stmt->execute([$studentId, $studentUserId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Get or create research plan for a group (idempotent)
 * Returns existing plan if found, creates new one if not exists
 * 
 * @param PDO $crad CRAD database connection
 * @param int $groupId Research group ID
 * @return array|null Research plan record
 */
function rpGetOrCreateResearchPlan(PDO $crad, int $groupId): ?array
{
    if ($groupId <= 0) {
        return null;
    }
    
    // Check for existing plan first (DUPLICATE PREVENTION)
    $stmt = $crad->prepare("SELECT * FROM research_plans WHERE research_group_id = ? LIMIT 1");
    $stmt->execute([$groupId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        return $existing;
    }
    
    // Get group details
    $groupStmt = $crad->prepare("
        SELECT rg.*, raa.adviser_user_id, raa.adviser_name, raa.adviser_email
        FROM research_groups rg
        LEFT JOIN research_adviser_assignments raa ON raa.id = (
            SELECT raa2.id
            FROM research_adviser_assignments raa2
            WHERE " . rpAdviserAssignmentMatchSql('raa2', 'rg') . "
              AND " . rpActiveAdviserAssignmentStatusSql('raa2') . "
            ORDER BY (raa2.assignment_status = 'Confirmed') DESC,
                     (raa2.assignment_status = 'Assigned') DESC,
                     raa2.updated_at DESC,
                     raa2.id DESC
            LIMIT 1
        )
        WHERE rg.id = ?
        LIMIT 1
    ");
    $groupStmt->execute([$groupId]);
    $group = $groupStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$group) {
        return null;
    }
    
    // Create new plan
    $insertStmt = $crad->prepare("
        INSERT INTO research_plans (
            research_group_id, group_number, research_title,
            adviser_id, adviser_name, start_date, status
        ) VALUES (?, ?, ?, ?, ?, CURDATE(), 'Active')
    ");
    
    try {
        $insertStmt->execute([
            $groupId,
            $group['group_number'],
            $group['research_title'],
            $group['adviser_user_id'] ?? null,
            $group['adviser_name'] ?: $group['adviser']
        ]);
        
        $planId = (int) $crad->lastInsertId();
        
        // Initialize default milestones
        rpInitializeDefaultMilestones($crad, $planId);
        
        // Fetch and return the new plan
        $stmt->execute([$groupId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        
    } catch (PDOException $e) {
        // Check for duplicate key error (unique key is `uniq_rp_group`)
        if (strpos($e->getMessage(), 'uniq_rp_group') !== false) {
            // Another process created it, fetch and return
            $stmt->execute([$groupId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        throw $e;
    }
}

/**
 * Initialize default milestones for a research plan (idempotent)
 * Uses INSERT IGNORE to prevent duplicates
 * 
 * @param PDO $crad CRAD database connection
 * @param int $planId Research plan ID
 * @return int Number of milestones created
 */
function rpInitializeDefaultMilestones(PDO $crad, int $planId): int
{
    $defaultMilestones = [
        ['name' => 'Chapter 1',          'order' => 1, 'desc' => 'Introduction and Background'],
        ['name' => 'Chapter 2',          'order' => 2, 'desc' => 'Review of Related Literature'],
        ['name' => 'Chapter 3',          'order' => 3, 'desc' => 'Methodology'],
        ['name' => 'Chapter 4',          'order' => 4, 'desc' => 'Results / System Design and Development'],
        ['name' => 'Chapter 5',          'order' => 5, 'desc' => 'Summary, Conclusions and Recommendations'],
        ['name' => 'System Development', 'order' => 6, 'desc' => 'System Implementation'],
        ['name' => 'Testing',            'order' => 7, 'desc' => 'Testing and Quality Assurance'],
        ['name' => 'Documentation',      'order' => 8, 'desc' => 'Final Documentation and Report'],
    ];
    
    // Use INSERT IGNORE to prevent duplicate milestones
    $stmt = $crad->prepare("
        INSERT IGNORE INTO research_milestones (
            research_plan_id, milestone_name, milestone_order, description, status
        ) VALUES (?, ?, ?, ?, 'Not Started')
    ");
    
    $count = 0;
    foreach ($defaultMilestones as $milestone) {
        try {
            $stmt->execute([
                $planId,
                $milestone['name'],
                $milestone['order'],
                $milestone['desc']
            ]);
            $count += $stmt->rowCount();
        } catch (PDOException $e) {
            // Ignore duplicate key errors
            if (strpos($e->getMessage(), 'uniq_rm_plan_name') === false) {
                error_log('Failed to create milestone: ' . $e->getMessage());
            }
        }
    }
    
    return $count;
}

/**
 * Ensure Chapter 4 and Chapter 5 milestone records exist for a plan (idempotent backfill).
 *
 * Safe to call on every page load — uses INSERT IGNORE so existing rows, including
 * all their progress/status data, are never touched. Also corrects the milestone_order
 * of System Development, Testing, and Documentation from the old 4/5/6 numbering to
 * the new 6/7/8 numbering if those rows were created before Chapter 4 & 5 were added.
 *
 * @param PDO $crad CRAD database connection
 * @param int $planId Research plan ID
 * @return void
 */
function rpEnsureChapter4And5Milestones(PDO $crad, int $planId): void
{
    if ($planId <= 0) {
        return;
    }

    // Chapters to backfill — only inserted when genuinely absent.
    // We do an explicit SELECT check rather than relying on INSERT IGNORE,
    // because the UNIQUE KEY may not exist on all installations.
    $missing = [
        ['Chapter 4', 4, 'Results / System Design and Development'],
        ['Chapter 5', 5, 'Summary, Conclusions and Recommendations'],
    ];

    $checkStmt = $crad->prepare(
        "SELECT COUNT(*) FROM research_milestones
         WHERE research_plan_id = ? AND milestone_name = ? LIMIT 1"
    );
    $insertStmt = $crad->prepare(
        "INSERT INTO research_milestones
             (research_plan_id, milestone_name, milestone_order, description, status)
         VALUES (?, ?, ?, ?, 'Not Started')"
    );

    foreach ($missing as [$name, $order, $desc]) {
        $checkStmt->execute([$planId, $name]);
        if ((int) $checkStmt->fetchColumn() === 0) {
            $insertStmt->execute([$planId, $name, $order, $desc]);
        }
    }

    // Fix milestone_order for development milestones that were created with the
    // old 6-milestone numbering (order 4/5/6) before Chapter 4 & 5 were added.
    // Only updates rows whose order still holds the old value — no-op otherwise.
    $reorder = $crad->prepare(
        "UPDATE research_milestones
         SET milestone_order = CASE milestone_name
             WHEN 'System Development' THEN 6
             WHEN 'Testing'            THEN 7
             WHEN 'Documentation'      THEN 8
         END
         WHERE research_plan_id = ?
           AND milestone_name IN ('System Development', 'Testing', 'Documentation')
           AND milestone_order IN (4, 5, 6)"
    );
    $reorder->execute([$planId]);
}

/**
 * Generate unique submission token for duplicate prevention
 * 
 * @return string 32-character hex token
 */
function rpGenerateSubmissionToken(): string
{
    return bin2hex(random_bytes(16));
}

/**
 * Check if submission token was recently used (duplicate detection)
 * 
 * @param PDO $crad CRAD database connection
 * @param string $table Table name (research_progress_updates or research_progress_feedback)
 * @param string $token Submission token
 * @param int $windowMinutes Time window in minutes (default 5)
 * @return bool True if token was recently used
 */
function rpIsTokenRecentlyUsed(PDO $crad, string $table, string $token, int $windowMinutes = 5): bool
{
    if (empty($token)) {
        return false;
    }
    
    $allowedTables = ['research_progress_updates', 'research_progress_feedback'];
    if (!in_array($table, $allowedTables, true)) {
        return false;
    }
    
    $timeColumn = $table === 'research_progress_updates' ? 'submitted_at' : 'created_at';
    
    $stmt = $crad->prepare("
        SELECT COUNT(*) FROM {$table}
        WHERE submission_token = ?
          AND {$timeColumn} >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    
    $stmt->execute([$token, $windowMinutes]);
    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Submit progress update with duplicate prevention
 * 
 * @param PDO $crad CRAD database connection
 * @param array $data Progress update data
 * @return array Result with success status and message
 */
function rpSubmitProgressUpdate(PDO $crad, array $data): array
{
    // Validate required fields
    $required = ['research_plan_id', 'research_group_id', 'submitted_by_user_id', 
                 'submitted_by_name', 'new_progress'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            return ['success' => false, 'message' => "Missing required field: {$field}"];
        }
    }
    
    // Check for duplicate submission token
    if (!empty($data['submission_token'])) {
        if (rpIsTokenRecentlyUsed($crad, 'research_progress_updates', $data['submission_token'])) {
            return [
                'success' => false, 
                'message' => 'Duplicate submission detected. Please wait before submitting again.',
                'is_duplicate' => true
            ];
        }
    }
    
    // Validate progress range
    $newProgress = (float) $data['new_progress'];
    if ($newProgress < 0 || $newProgress > 100) {
        return ['success' => false, 'message' => 'Progress must be between 0 and 100'];
    }

    // Ensure the attachments table exists BEFORE opening the transaction.
    // CREATE TABLE is DDL and causes an implicit commit in MySQL/MariaDB,
    // which used to silently end the transaction mid-submission and crash
    // on commit(), leaving the client without a success response.
    if (!empty($data['uploaded_document']) && is_array($data['uploaded_document'])) {
        rpEnsureProgressAttachmentSchema($crad);
    }

    try {
        $crad->beginTransaction();

        // Insert progress update
        $stmt = $crad->prepare("
            INSERT INTO research_progress_updates (
                research_plan_id, research_group_id, milestone_id,
                submitted_by_user_id, submitted_by_name, update_title,
                previous_progress, new_progress, milestone_status,
                accomplishments, problems_blockers, next_planned_activity,
                attachment_path, attachment_original_name, submission_token
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['research_plan_id'],
            $data['research_group_id'],
            $data['milestone_id'] ?? null,
            $data['submitted_by_user_id'],
            $data['submitted_by_name'],
            $data['update_title'] ?? 'Progress Update',
            $data['previous_progress'] ?? 0,
            $newProgress,
            $data['milestone_status'] ?? 'In Progress',
            $data['accomplishments'] ?? null,
            $data['problems_blockers'] ?? null,
            $data['next_planned_activity'] ?? null,
            $data['attachment_path'] ?? null,
            $data['attachment_original_name'] ?? null,
            $data['submission_token'] ?? null
        ]);

        $updateId = (int) $crad->lastInsertId();

        if (!empty($data['uploaded_document']) && is_array($data['uploaded_document'])) {
            $attachment = $data['uploaded_document'];
            $attachStmt = $crad->prepare("
                INSERT INTO research_progress_attachments (
                    progress_update_id, file_name, file_path, file_type, file_size, uploaded_by
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $attachStmt->execute([
                $updateId,
                (string) ($attachment['original_name'] ?? ''),
                (string) ($attachment['path'] ?? ''),
                (string) ($attachment['mime'] ?? ''),
                (int) ($attachment['size'] ?? 0),
                (int) $data['submitted_by_user_id'],
            ]);
        }
        
        // Update milestone progress if milestone_id provided.
        if (!empty($data['milestone_id'])) {
            $updateMilestone = $crad->prepare("
                UPDATE research_milestones 
                SET progress_percentage = ?,
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateMilestone->execute([
                $newProgress,
                $data['milestone_status'] ?? 'In Progress',
                $data['milestone_id']
            ]);
        }
        
        // Recalculate overall progress
        rpRecalculateOverallProgress($crad, (int) $data['research_plan_id']);
        
        // Log activity
        rpLogActivity($crad, [
            'research_plan_id' => $data['research_plan_id'],
            'research_group_id' => $data['research_group_id'],
            'user_id' => $data['submitted_by_user_id'],
            'user_name' => $data['submitted_by_name'],
            'user_role' => 'student',
            'action' => 'progress_updated',
            'entity_type' => 'progress_update',
            'entity_id' => $updateId,
            'detail' => "Progress updated to {$newProgress}%"
        ]);
        
        $crad->commit();
        
        return [
            'success' => true,
            'message' => 'Progress update submitted successfully',
            'update_id' => $updateId
        ];
        
    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('Progress update submission failed: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to submit progress update. Please try again.'
        ];
    }
}

/**
 * Recalculate overall progress for a research plan
 * Based on average of all milestone progress percentages
 * 
 * @param PDO $crad CRAD database connection
 * @param int $planId Research plan ID
 * @return void
 */
function rpRecalculateOverallProgress(PDO $crad, int $planId): void
{
    $groupStmt = $crad->prepare('SELECT research_group_id FROM research_plans WHERE id = ? LIMIT 1');
    $groupStmt->execute([$planId]);
    $groupId = (int) ($groupStmt->fetchColumn() ?: 0);

    $stmt = $crad->prepare("
        SELECT *
        FROM research_milestones
        WHERE research_plan_id = ?
        ORDER BY milestone_order ASC
    ");
    $stmt->execute([$planId]);
    $milestones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $milestones = rpNormalizeApprovedMilestoneProgress($milestones);
    $avgProgress = rpMilestonesOverallProgress($milestones);
    
    $updateStmt = $crad->prepare("
        UPDATE research_plans 
        SET overall_progress = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$avgProgress, $planId]);
}

/**
 * Log activity with duplicate prevention via activity hash
 * 
 * @param PDO $crad CRAD database connection
 * @param array $data Activity data
 * @return void
 */
function rpLogActivity(PDO $crad, array $data): void
{
    // Deduplicate: skip if the same user+action+entity was already logged this minute
    $checkStmt = $crad->prepare("
        SELECT id FROM research_progress_activity_logs
        WHERE user_id    = ?
          AND action     = ?
          AND entity_type = ?
          AND entity_id  = ?
          AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        LIMIT 1
    ");
    $checkStmt->execute([
        $data['user_id']      ?? null,
        $data['action'],
        $data['entity_type']  ?? '',
        $data['entity_id']    ?? null,
    ]);

    if ($checkStmt->fetch()) {
        return;
    }

    // Log the activity
    // Table columns: research_plan_id, user_id, user_name, user_role,
    //                action, entity_type, entity_id, old_value, new_value, description
    try {
        $stmt = $crad->prepare("
            INSERT INTO research_progress_activity_logs (
                research_plan_id, user_id, user_name, user_role,
                action, entity_type, entity_id, description
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['research_plan_id'],
            $data['user_id']      ?? null,
            $data['user_name']    ?? '',
            $data['user_role']    ?? '',
            $data['action'],
            $data['entity_type']  ?? '',
            $data['entity_id']    ?? null,
            $data['detail']       ?? '',
        ]);
    } catch (PDOException $e) {
        // Silently fail — logging must never break main functionality
        error_log('Activity log failed: ' . $e->getMessage());
    }
}

/**
 * Create notification with duplicate prevention via batch_key
 * 
 * @param PDO $crad CRAD database connection
 * @param array $data Notification data
 * @return bool Success status
 */
function rpCreateNotification(PDO $crad, array $data): bool
{
    // Generate batch key for duplicate prevention
    $batchKey = $data['batch_key'] ?? (
        $data['notification_type'] . ':' . 
        ($data['related_entity_id'] ?? uniqid())
    );
    
    // Check if notification with same batch_key exists for this recipient
    $checkStmt = $crad->prepare("
        SELECT id FROM research_progress_notifications
        WHERE batch_key = ?
          AND (
               (recipient_user_id IS NOT NULL AND recipient_user_id = ?)
            OR (recipient_user_id IS NULL AND recipient_email = ?)
            OR (recipient_user_id IS NULL AND recipient_email = '' AND recipient_role = ?)
          )
        LIMIT 1
    ");
    
    $checkStmt->execute([
        $batchKey,
        $data['recipient_user_id'] ?? null,
        $data['recipient_email'] ?? '',
        $data['recipient_role'] ?? ''
    ]);
    
    if ($checkStmt->fetch()) {
        // Duplicate notification exists
        return false;
    }
    
    // Create notification
    try {
        $stmt = $crad->prepare("
            INSERT INTO research_progress_notifications (
                recipient_user_id, recipient_email, recipient_role, batch_key,
                notification_type, title, body, related_entity_type, related_entity_id,
                action_url, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unread')
        ");
        
        $stmt->execute([
            $data['recipient_user_id'] ?? null,
            $data['recipient_email'] ?? '',
            $data['recipient_role'] ?? '',
            $batchKey,
            $data['notification_type'] ?? 'progress_update',
            $data['title'] ?? 'Notification',
            $data['body'] ?? '',
            $data['related_entity_type'] ?? '',
            $data['related_entity_id'] ?? null,
            $data['action_url'] ?? null
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log('Notification creation failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Revision Monitoring — Eligible research groups for the Adviser.
 *
 * A research group appears ONLY when:
 *   - the Adviser officially owns it (research_adviser_assignments),
 *   - it has a finalized Pre-Oral Defense schedule,
 *   - exactly N DISTINCT assigned Panel Members (Pre-Oral Defense phase) exist,
 *   - and ALL N of those assigned panel members submitted an evaluation whose
 *     result is APPROVED WITH REVISION.
 *
 * One case per research group. Real-time: reads only — no writes.
 *
 * @return array<int, array>
 */
function rpGetRevisionMonitoringGroups(PDO $crad, int $adviserUserId, string $adviserEmail): array
{
    if ($adviserUserId <= 0 && rpNormalizeEmail($adviserEmail) === '') {
        return [];
    }

    $identitySql  = rpAdviserIdentitySql('raa2');
    $statusSql    = rpActiveAdviserAssignmentStatusSql('raa2');
    $assignSql    = rpAdviserAssignmentMatchSql('raa2', 'rg');
    $params       = rpAdviserIdentityParams($adviserUserId, $adviserEmail);

    $sql = "
        SELECT
            rg.id                                  AS research_group_id,
            rg.group_number,
            rg.group_name,
            rg.research_title,
            rg.academic_year,
            rg.date_assigned,
            rds.id                                 AS defense_schedule_id,
            rds.defense_datetime,
            COALESCE(NULLIF(rds.venue, ''), '')   AS venue,
            rds.adviser_name                       AS defense_adviser_name,
            rds.updated_at                         AS defense_updated_at,
            a.adviser_name,
            a.adviser_email,
            a.adviser_user_id,
            a.assignment_status                    AS adviser_assignment_status,
            COUNT(DISTINCT rpa.panel_user_id)                                                       AS assigned_panel_count,
            COUNT(DISTINCT CASE WHEN ev.status = 'Submitted' THEN rpa.panel_user_id END)             AS submitted_eval_count,
            COUNT(DISTINCT CASE WHEN ev.result = 'APPROVED WITH REVISION' AND ev.status = 'Submitted' THEN rpa.panel_user_id END) AS awr_count,
            MAX(CASE WHEN ev.result = 'APPROVED WITH REVISION' AND ev.status = 'Submitted' THEN ev.submitted_at END) AS last_awr_at
        FROM research_groups rg
        JOIN research_defense_schedules rds
            ON rds.id = (
                SELECT rds2.id
                FROM research_defense_schedules rds2
                WHERE (rds2.research_group_id = rg.id
                       OR (rds2.research_group_id IS NULL AND rds2.group_number = rg.group_number))
                  AND LOWER(rds2.status) IN ('finalized', 'final', 'completed', 'passed', 'failed')
                  AND rds2.defense_datetime IS NOT NULL
                ORDER BY COALESCE(rds2.defense_datetime, rds2.updated_at) DESC, rds2.id DESC
                LIMIT 1
            )
        JOIN research_adviser_assignments a ON a.id = (
            SELECT raa2.id
            FROM research_adviser_assignments raa2
            WHERE {$assignSql}
              AND {$identitySql}
              AND {$statusSql}
            ORDER BY (raa2.assignment_status = 'Confirmed') DESC,
                     (raa2.assignment_status = 'Assigned') DESC,
                     raa2.updated_at DESC, raa2.id DESC
            LIMIT 1
        )
        LEFT JOIN research_panel_assignments rpa
            ON rpa.research_group_id = rg.id
           AND rpa.defense_phase = '" . CRAD_DEFENSE_PHASE_PRE_ORAL . "'
           AND rpa.assignment_status = 'Assigned'
        LEFT JOIN preoral_defense_evaluations ev
            ON ev.defense_schedule_id = rds.id
           AND ev.panel_user_id = rpa.panel_user_id
           AND ev.status = 'Submitted'
         WHERE 1=1
         GROUP BY rg.id, rds.id, a.id
         HAVING assigned_panel_count > 0
            AND submitted_eval_count = assigned_panel_count
            AND awr_count = assigned_panel_count
        ORDER BY COALESCE(rds.defense_datetime, rds.updated_at) DESC, rg.id DESC
    ";

    try {
        $stmt = $crad->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Revision monitoring groups load failed: ' . $e->getMessage());
        return [];
    }

    foreach ($rows as &$row) {
        $revision               = rpComputeRevisionStatus($crad, (int) $row['research_group_id'], (string) $row['last_awr_at']);
        $row['revision_status']              = $revision['status'];
        $row['revision_last_activity_at']    = $revision['last_activity_at'];
        $row['panel_decision']               = 'APPROVED WITH REVISION';
        $row['panel_evaluations_summary']    = ((int) $row['submitted_eval_count']) . '/' . ((int) $row['assigned_panel_count']) . ' Completed';
        $row['assigned_panel_count']         = (int) $row['assigned_panel_count'];
        $row['awr_count']                    = (int) $row['awr_count'];
    }
    unset($row);

    return $rows;
}

/**
 * Compute the Adviser-side revision monitoring status for a group,
 * anchored to when the 3/3 APPROVED WITH REVISION consensus was reached
 * (the latest AWR evaluation timestamp). Reuses the existing
 * research_progress_updates / research_progress_feedback status fields —
 * NO new status table is introduced.
 */
function rpComputeRevisionStatus(PDO $crad, int $groupId, string $activationTs): array
{
    $activation  = $activationTs !== '' ? $activationTs : '1970-01-01 00:00:01';
    $latest      = null;
    $latestFb    = null;

    try {
        $stmt = $crad->prepare(
            "SELECT rpu.milestone_status, rpu.submitted_at, rpu.update_title, rpu.id
             FROM research_progress_updates rpu
             WHERE rpu.research_group_id = :gid
               AND rpu.submitted_at >= :act
             ORDER BY rpu.submitted_at DESC, rpu.id DESC
             LIMIT 1"
        );
        $stmt->execute([':gid' => $groupId, ':act' => $activation]);
        $latest = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt = $crad->prepare(
            "SELECT rpf.feedback_type, rpf.created_at
             FROM research_progress_feedback rpf
             INNER JOIN research_progress_updates rpu ON rpu.id = rpf.progress_update_id
             WHERE rpu.research_group_id = :gid
               AND rpf.created_at >= :act
             ORDER BY rpf.created_at DESC, rpf.id DESC
             LIMIT 1"
        );
        $stmt->execute([':gid' => $groupId, ':act' => $activation]);
        $latestFb = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        error_log('Revision status compute failed: ' . $e->getMessage());
    }

    $status        = 'For Revision';
    $lastActivity  = $activation;

    if ($latest) {
        $submittedAt = (string) ($latest['submitted_at'] ?? $activation);
        if (strtotime($submittedAt) !== false) {
            $lastActivity = $submittedAt;
        }
        $ms = (string) ($latest['milestone_status'] ?? '');
        if (in_array($ms, ['Approved', 'Completed'], true)) {
            $status = 'Completed';
        } elseif ($ms === 'Submitted for Review') {
            $status = $latestFb ? 'Under Adviser Review' : 'Revision Submitted';
        } else {
            $status = 'For Revision';
        }
    }

    if ($latestFb) {
        $fbCreated = (string) ($latestFb['created_at'] ?? '');
        if (strtotime($fbCreated) !== false && strtotime($fbCreated) >= strtotime($activation) && strtotime($fbCreated) > strtotime($lastActivity)) {
            $lastActivity = $fbCreated;
        }
    }

    return [
        'status'            => $status,
        'latest_update'     => $latest,
        'feedback'          => $latestFb,
        'last_activity_at'  => $lastActivity,
    ];
}

/**
 * Revision Monitoring — Detail (3 panels + remarks) for an Adviser-owned, eligible group.
 */
function rpGetRevisionDetail(PDO $crad, int $adviserUserId, string $adviserEmail, string $groupNumber): ?array
{
    $groupNumber = trim((string) $groupNumber);
    if ($groupNumber === '') {
        return null;
    }

    $groups = rpGetRevisionMonitoringGroups($crad, $adviserUserId, $adviserEmail);
    $group  = null;
    foreach ($groups as $g) {
        if ((string) ($g['group_number'] ?? '') === $groupNumber) {
            $group = $g;
            break;
        }
    }

    if (!$group) {
        return null; // Not eligible or not owned by this Adviser.
    }

    $groupId    = (int) $group['research_group_id'];
    $scheduleId = (int) $group['defense_schedule_id'];

    $panels = [];
    $updates = [];
    try {
        $stmt = $crad->prepare(
            "SELECT
                 COALESCE(NULLIF(u.full_name, ''), NULLIF(rpa.panel_name, ''), 'Panel Member') AS panel_name,
                 COALESCE(NULLIF(rpa.panel_email, ''), '') AS panel_email,
                 ev.result        AS panel_result,
                 ev.overall_score,
                 ev.content_score,
                 ev.methodology_score,
                 ev.references_score,
                 ev.format_score,
                 ev.remarks,
                 ev.submitted_at  AS evaluated_at,
                 ev.status        AS eval_status
             FROM research_panel_assignments rpa
             LEFT JOIN preoral_defense_evaluations ev
                 ON ev.defense_schedule_id = :sched
                AND ev.panel_user_id = rpa.panel_user_id
                AND ev.status = 'Submitted'
             LEFT JOIN sms2_db.users u ON u.id = rpa.panel_user_id
             WHERE rpa.research_group_id = :gid
               AND rpa.defense_phase = '" . CRAD_DEFENSE_PHASE_PRE_ORAL . "'
               AND rpa.assignment_status = 'Assigned'
             ORDER BY COALESCE(u.full_name, rpa.panel_name, 'Panel Member') ASC"
        );
        $stmt->execute([':gid' => $groupId, ':sched' => $scheduleId]);
        $panels = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stmt = $crad->prepare(
            "SELECT rpu.*,
                    rm.milestone_name
             FROM research_progress_updates rpu
             LEFT JOIN research_milestones rm ON rm.id = rpu.milestone_id
             WHERE rpu.research_group_id = :gid
             ORDER BY rpu.submitted_at DESC, rpu.id DESC
             LIMIT 8"
        );
        $stmt->execute([':gid' => $groupId]);
        $updates = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('Revision detail load failed: ' . $e->getMessage());
    }

    return [
        'group'        => $group,
        'panels'       => $panels,
        'updates'      => $updates,
    ];
}

/**
 * Summary counts for the Revision Monitoring dashboard (read-only).
 */
function rpRevisionMonitoringCounts(array $groups): array
{
    $active = 0;
    $pending = 0;
    $completed = 0;
    foreach ($groups as $g) {
        $status = (string) ($g['revision_status'] ?? '');
        if ($status === 'Completed') {
            $completed++;
        } elseif ($status === 'Revision Submitted') {
            $pending++;
        } else {
            // 'For Revision' and 'Under Adviser Review' are active cases.
            $active++;
        }
    }
    return ['active' => $active, 'pending' => $pending, 'completed' => $completed];
}

/**
 * Check whether a milestone already has an active "Submitted for Review" submission
 * that has not yet been acted on by the adviser.
 *
 * "Active / pending" means:
 *   — The research_milestones row status is 'Submitted for Review'
 *   — AND the most-recent research_progress_updates row for this milestone+group
 *     also carries milestone_status = 'Submitted for Review'
 *   — AND no adviser feedback exists for that update yet
 *     (i.e. the adviser hasn't approved or requested revision).
 *
 * This is the authoritative database-level gate used by both the API and the page load.
 *
 * @param PDO $crad       CRAD database connection
 * @param int $groupId    Research group ID
 * @param int $milestoneId Milestone ID (research_milestones.id)
 * @return array|null     The pending update row if one exists, null otherwise
 */
function rpHasPendingSubmission(PDO $crad, int $groupId, int $milestoneId): ?array
{
    if ($groupId <= 0 || $milestoneId <= 0) {
        return null;
    }

    // Fetch the latest progress update for this milestone+group.
    $stmt = $crad->prepare("
        SELECT rpu.*
        FROM research_progress_updates rpu
        WHERE rpu.research_group_id = ?
          AND rpu.milestone_id      = ?
          AND rpu.milestone_status  = 'Submitted for Review'
        ORDER BY rpu.submitted_at DESC, rpu.id DESC
        LIMIT 1
    ");
    $stmt->execute([$groupId, $milestoneId]);
    $latestUpdate = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$latestUpdate) {
        return null;
    }

    // Confirm no adviser feedback exists for this update yet.
    // If the adviser has already acted (approved or requested revision),
    // the submission is no longer "pending" and the student may resubmit
    // according to the existing workflow.
    $fbStmt = $crad->prepare("
        SELECT id
        FROM research_progress_feedback
        WHERE progress_update_id = ?
        LIMIT 1
    ");
    $fbStmt->execute([$latestUpdate['id']]);
    $hasFeedback = (bool) $fbStmt->fetch();

    if ($hasFeedback) {
        // Adviser already responded — not pending any more.
        return null;
    }

    return $latestUpdate;
}

/**
 * Return milestones for a plan, each annotated with:
 *   has_pending_submission  (bool)  — true when the milestone is locked for new submissions
 *   pending_submitted_at    (string|null) — timestamp of the pending update, if any
 *   pending_update_id       (int|null)    — ID of the pending update row, if any
 *
 * This is a thin wrapper around rpGetMilestonesForPlan — all existing data is preserved.
 *
 * @param PDO      $crad
 * @param int|null $planId
 * @param int|null $groupId
 * @return array
 */
function rpGetMilestonesWithPendingFlags(PDO $crad, ?int $planId, ?int $groupId = null): array
{
    $milestones = rpGetMilestonesForPlan($crad, $planId, $groupId);

    if (!$groupId || !$milestones) {
        // Annotate with safe defaults and return early.
        foreach ($milestones as &$m) {
            $m['has_pending_submission'] = false;
            $m['pending_submitted_at']   = null;
            $m['pending_update_id']      = null;
        }
        unset($m);
        return $milestones;
    }

    // Fetch all currently-pending updates for this group in one query to avoid N+1.
    // "Pending" = Submitted for Review AND no adviser feedback yet.
    $stmt = $crad->prepare("
        SELECT rpu.id, rpu.milestone_id, rpu.submitted_at
        FROM research_progress_updates rpu
        WHERE rpu.research_group_id  = ?
          AND rpu.milestone_status   = 'Submitted for Review'
          AND NOT EXISTS (
              SELECT 1
              FROM research_progress_feedback rpf
              WHERE rpf.progress_update_id = rpu.id
          )
        ORDER BY rpu.submitted_at DESC, rpu.id DESC
    ");
    $stmt->execute([$groupId]);
    $pendingRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Index by milestone_id — keep only the most-recent pending row per milestone.
    $pendingByMilestone = [];
    foreach ($pendingRows as $row) {
        $mid = (int) $row['milestone_id'];
        if (!isset($pendingByMilestone[$mid])) {
            $pendingByMilestone[$mid] = $row;
        }
    }

    foreach ($milestones as &$m) {
        $mid = (int) ($m['id'] ?? 0);
        if (isset($pendingByMilestone[$mid])) {
            $m['has_pending_submission'] = true;
            $m['pending_submitted_at']   = $pendingByMilestone[$mid]['submitted_at'];
            $m['pending_update_id']      = (int) $pendingByMilestone[$mid]['id'];
        } else {
            $m['has_pending_submission'] = false;
            $m['pending_submitted_at']   = null;
            $m['pending_update_id']      = null;
        }
    }
    unset($m);

    return $milestones;
}

/**
 * Ensure the panel_remarks column exists on research_milestones.
 * Safe to call repeatedly — uses CREATE TABLE … IF NOT EXISTS pattern via ALTER IGNORE.
 * Called once per page load that reads milestones; silently no-ops after first run.
 */
function rpEnsurePanelRemarksColumn(PDO $crad): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        // Check if column already exists before issuing ALTER.
        $stmt = $crad->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'research_milestones'
               AND COLUMN_NAME  = 'panel_remarks'"
        );
        $stmt->execute();
        if ((int) $stmt->fetchColumn() === 0) {
            $crad->exec(
                "ALTER TABLE research_milestones
                 ADD COLUMN panel_remarks TEXT DEFAULT NULL
                 AFTER adviser_remarks"
            );
        }
    } catch (Throwable $e) {
        // Column may already exist in a race; never break milestone reads.
        error_log('rpEnsurePanelRemarksColumn: ' . $e->getMessage());
    }
}

/**
 * Determine the official Pre-Oral Defense result for a research group.
 *
 * Uses the same precedence rules as panelFinalResultFromResults() in
 * panel-defense-page.php:
 *   FAILED beats everything → APPROVED WITH REVISION beats APPROVED → else APPROVED.
 *
 * Requires ALL assigned panel members to have submitted before producing a result
 * (same logic already enforced by panelHydrateDefenseRow / panelDefenseById).
 *
 * @param PDO $crad
 * @param int $groupId  research_groups.id
 * @return array|null  null = no final result yet; otherwise:
 *   [
 *     'final_result'       => 'APPROVED'|'APPROVED WITH REVISION'|'FAILED',
 *     'defense_schedule_id'=> int,
 *     'total_assigned'     => int,
 *     'total_submitted'    => int,
 *     'panel_remarks'      => string,   // concatenated non-empty remarks
 *     'approved_at'        => string,   // latest submitted_at among evaluations
 *   ]
 */
function rpGetGroupPanelApproval(PDO $crad, int $groupId): ?array
{
    if ($groupId <= 0) {
        return null;
    }

    try {
        // Find the most-recent finalized defense schedule for this group.
        $schedStmt = $crad->prepare(
            "SELECT id
             FROM research_defense_schedules
             WHERE research_group_id = ?
               AND defense_datetime IS NOT NULL
               AND LOWER(status) IN ('scheduled','finalized','final','completed','passed','failed')
             ORDER BY defense_datetime DESC, id DESC
             LIMIT 1"
        );
        $schedStmt->execute([$groupId]);
        $scheduleId = (int) ($schedStmt->fetchColumn() ?: 0);

        if ($scheduleId <= 0) {
            return null;
        }

        // Count how many panel members are formally assigned.
        $assignedStmt = $crad->prepare(
            "SELECT COUNT(DISTINCT panel_user_id)
             FROM research_panel_assignments
             WHERE research_group_id = ?
               AND defense_phase     = '" . CRAD_DEFENSE_PHASE_PRE_ORAL . "'
               AND assignment_status = 'Assigned'"
        );
        $assignedStmt->execute([$groupId]);
        $totalAssigned = (int) $assignedStmt->fetchColumn();

        if ($totalAssigned <= 0) {
            // No formally assigned panel members — cannot determine official result.
            return null;
        }

        // Fetch all submitted evaluations for this schedule+group.
        $evalStmt = $crad->prepare(
            "SELECT result, remarks, submitted_at
             FROM preoral_defense_evaluations
             WHERE defense_schedule_id = ?
               AND status = 'Submitted'
             ORDER BY submitted_at ASC"
        );
        $evalStmt->execute([$scheduleId]);
        $evals = $evalStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalSubmitted = count($evals);

        // All required evaluations must be in before we publish a result.
        if ($totalSubmitted < $totalAssigned) {
            return null;
        }

        // Apply the same precedence logic as panelFinalResultFromResults().
        $results = array_column($evals, 'result');
        if (in_array('FAILED', $results, true)) {
            $finalResult = 'FAILED';
        } elseif (in_array('APPROVED WITH REVISION', $results, true)) {
            $finalResult = 'APPROVED WITH REVISION';
        } else {
            $finalResult = 'APPROVED';
        }

        // Concatenate non-empty panel remarks (preserve individual voices).
        $remarksLines = [];
        foreach ($evals as $eval) {
            $r = trim((string) ($eval['remarks'] ?? ''));
            if ($r !== '') {
                $remarksLines[] = $r;
            }
        }
        $panelRemarks = implode("\n", $remarksLines);
        if ($panelRemarks === '') {
            $panelRemarks = 'Approved by Panel.';
        }

        $latestAt = '';
        foreach ($evals as $eval) {
            if ((string) ($eval['submitted_at'] ?? '') > $latestAt) {
                $latestAt = (string) $eval['submitted_at'];
            }
        }

        return [
            'final_result'        => $finalResult,
            'defense_schedule_id' => $scheduleId,
            'total_assigned'      => $totalAssigned,
            'total_submitted'     => $totalSubmitted,
            'panel_remarks'       => $panelRemarks,
            'approved_at'         => $latestAt,
        ];

    } catch (Throwable $e) {
        error_log('rpGetGroupPanelApproval failed: ' . $e->getMessage());
        return null;
    }
}

function rpIsFirstSemesterComplete(PDO $crad, int $planId, int $groupId): bool
{
    if ($planId <= 0 || $groupId <= 0) {
        return false;
    }

    $milestoneStmt = $crad->prepare(
        "SELECT COUNT(*) AS total,
                SUM(status IN ('Approved', 'Completed')) AS completed
         FROM research_milestones
         WHERE research_plan_id = ?"
    );
    $milestoneStmt->execute([$planId]);
    $milestones = $milestoneStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $totalMilestones = (int) ($milestones['total'] ?? 0);
    if ($totalMilestones <= 0 || (int) ($milestones['completed'] ?? 0) !== $totalMilestones) {
        return false;
    }

    $scheduleStmt = $crad->prepare(
        "SELECT id
         FROM research_defense_schedules
         WHERE research_group_id = ?
           AND defense_type = '" . CRAD_DEFENSE_TYPE_PRE_ORAL . "'
           AND LOWER(TRIM(status)) = 'finalized'
         ORDER BY defense_datetime DESC, id DESC
         LIMIT 1"
    );
    $scheduleStmt->execute([$groupId]);
    $scheduleId = (int) ($scheduleStmt->fetchColumn() ?: 0);
    if ($scheduleId <= 0) {
        return false;
    }

    $panelStmt = $crad->prepare(
        "SELECT COUNT(DISTINCT rpa.panel_user_id) AS assigned_count,
                COUNT(DISTINCT CASE
                    WHEN ev.status = 'Submitted'
                     AND ev.result IN ('APPROVED', 'APPROVED WITH REVISION')
                    THEN rpa.panel_user_id
                END) AS accepted_count
         FROM research_panel_assignments rpa
         LEFT JOIN preoral_defense_evaluations ev
           ON ev.defense_schedule_id = ?
          AND ev.panel_user_id = rpa.panel_user_id
         WHERE rpa.research_group_id = ?
           AND rpa.defense_phase = '" . CRAD_DEFENSE_PHASE_PRE_ORAL . "'
           AND rpa.assignment_status = 'Assigned'"
    );
    $panelStmt->execute([$scheduleId, $groupId]);
    $panel = $panelStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $assignedCount = (int) ($panel['assigned_count'] ?? 0);

    return $assignedCount > 0
        && (int) ($panel['accepted_count'] ?? 0) === $assignedCount;
}

function rpSetCurrentStageIfFirstSemesterComplete(PDO $crad, int $planId, int $groupId): bool
{
    if (!rpIsFirstSemesterComplete($crad, $planId, $groupId)) {
        return false;
    }

    $stmt = $crad->prepare(
        "UPDATE research_plans
         SET current_stage = '" . CRAD_DEFENSE_PHASE_PRE_ORAL . "', updated_at = NOW()
         WHERE id = ? AND research_group_id = ?"
    );
    $stmt->execute([$planId, $groupId]);
    return true;
}

/**
 * Synchronise Chapter 1-3 milestone rows to reflect a confirmed APPROVED
 * Pre-Oral Defense result.
 *
 * Rules:
 *  - Only touches milestones whose milestone_order is 1, 2, or 3 AND whose
 *    milestone_name matches 'chapter N' (case-insensitive).
 *  - Idempotent: skips rows already at progress_percentage=100 AND
 *    status=Approved AND panel_remarks IS NOT NULL.
 *  - Writes: progress_percentage=100, status=Approved, panel_remarks=<text>,
 *    completed_at=COALESCE(completed_at, NOW()).
 *  - Never touches Chapter 4, 5, System Development, Testing, Documentation,
 *    or any other milestone.
 *  - Never deletes adviser_remarks or any other column.
 *  - Recalculates overall plan progress after writing.
 *
 * @param PDO    $crad
 * @param int    $groupId      research_groups.id
 * @param array  $panelRecord  Return value of rpGetGroupPanelApproval() — must have final_result=APPROVED
 * @return int   Number of milestone rows actually updated (0 = already in sync)
 */
function rpSyncChapterMilestonesFromPanelApproval(PDO $crad, int $groupId, array $panelRecord): int
{
    if (($panelRecord['final_result'] ?? '') !== 'APPROVED') {
        return 0;
    }

    // Find the research_plan for this group.
    $planStmt = $crad->prepare(
        "SELECT id FROM research_plans WHERE research_group_id = ? LIMIT 1"
    );
    $planStmt->execute([$groupId]);
    $planId = (int) ($planStmt->fetchColumn() ?: 0);

    if ($planId <= 0) {
        return 0;
    }

    // Fetch Chapter 1-3 milestones for this plan that are not yet fully synced.
    $milestonesStmt = $crad->prepare(
        "SELECT id
         FROM research_milestones
         WHERE research_plan_id  = ?
           AND milestone_order   IN (1, 2, 3)
           AND LOWER(TRIM(milestone_name)) IN ('chapter 1','chapter 2','chapter 3')
           AND NOT (
                 progress_percentage = 100
             AND status              = 'Approved'
             AND panel_remarks       IS NOT NULL
           )"
    );
    $milestonesStmt->execute([$planId]);
    $rows = $milestonesStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$rows) {
        return 0; // Already fully synced — nothing to do.
    }

    $panelRemarks = $panelRecord['panel_remarks'];
    $updated      = 0;

    try {
        $crad->beginTransaction();

        $updateStmt = $crad->prepare(
            "UPDATE research_milestones
             SET progress_percentage = 100,
                 status              = 'Approved',
                 panel_remarks       = ?,
                 completed_at        = COALESCE(completed_at, NOW()),
                 updated_at          = NOW()
             WHERE id = ?
               AND research_plan_id  = ?"
        );

        foreach ($rows as $milestoneId) {
            $updateStmt->execute([$panelRemarks, (int) $milestoneId, $planId]);
            $updated += $updateStmt->rowCount();
        }

        $crad->commit();

        if ($updated > 0) {
            rpRecalculateOverallProgress($crad, $planId);
        }

    } catch (Throwable $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        error_log('rpSyncChapterMilestonesFromPanelApproval failed: ' . $e->getMessage());
        return 0;
    }

    return $updated;
}
