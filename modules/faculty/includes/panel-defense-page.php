<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/chapter-evaluation-workflow.php';

function panelRequirePanelMember(): void
{
    requireAuth();
    if (!smsIsPanelDefenseRole()) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function panelDb(): ?PDO
{
    return function_exists('cradDb') ? cradDb() : null;
}

function panelNormalizedName(string $value): string
{
    return strtolower((string) preg_replace('/\s+/', ' ', trim($value)));
}

function panelMembersList(?string $value): array
{
    $parts = preg_split('/[,;\r\n]+/', (string) $value) ?: [];
    return array_values(array_filter(array_map('trim', $parts), static fn(string $name): bool => $name !== ''));
}

function panelCurrentUserIsAssigned(array $row): bool
{
    if ((int) ($row['current_panel_assignment_id'] ?? 0) > 0) {
        return true;
    }

    $currentName = panelNormalizedName(getCurrentUserName());
    $currentEmail = panelNormalizedName((string) ($_SESSION['user_email'] ?? ''));
    $members = array_merge(panelMembersList($row['panel_members'] ?? ''), panelMembersList($row['panel_chair'] ?? ''));

    foreach ($members as $member) {
        $member = panelNormalizedName($member);
        if ($member !== '' && ($member === $currentName || ($currentEmail !== '' && $member === $currentEmail))) {
            return true;
        }
    }

    return false;
}

function panelScheduleIsFinal(array $row): bool
{
    $status = strtolower(trim((string) ($row['status'] ?? '')));
    return in_array($status, ['scheduled', 'finalized', 'final', 'completed', 'passed', 'failed'], true)
        && !empty($row['defense_datetime']);
}

function panelDefenseRows(bool $history = false): array
{
    $crad = panelDb();
    if (!$crad instanceof PDO) {
        return [];
    }

    try {
        $sql = "SELECT
                rds.id,
                rds.research_group_id,
                rds.proposal_id,
                rds.proposal_number,
                rds.group_number,
                rds.research_group,
                rds.research_title,
                rds.adviser_name,
                GROUP_CONCAT(DISTINCT COALESCE(NULLIF(u_all.full_name, ''), NULLIF(rpa_all.panel_name, ''), 'Panel Member') ORDER BY COALESCE(NULLIF(u_all.full_name, ''), NULLIF(rpa_all.panel_name, ''), 'Panel Member') SEPARATOR '\n') AS panel_members,
                rds.panel_chair,
                COALESCE(NULLIF(rds.venue, ''), NULLIF(rv.venue_name, ''), '') AS venue,
                rds.defense_end_datetime,
                rds.defense_datetime,
                rds.status,
                rds.updated_at,
                rg.group_name,
                rg.academic_year,
                rpa_self.id AS current_panel_assignment_id,
                ev.id AS evaluation_id,
                ev.result AS panel_result,
                ev.overall_score AS panel_score,
                ev.submitted_at
             FROM research_defense_schedules rds
             LEFT JOIN research_groups rg ON rg.id = rds.research_group_id OR rg.group_number = rds.group_number
             LEFT JOIN research_venues rv ON rv.id = rds.venue_id
             LEFT JOIN research_panel_assignments rpa_self
               ON rpa_self.research_group_id = rds.research_group_id
              AND rpa_self.defense_schedule_id = rds.id
              AND rpa_self.panel_user_id = :panel_user_id_match
              AND rpa_self.defense_phase = 'Pre-Oral Defense'
              AND rpa_self.assignment_status = 'Assigned'
             LEFT JOIN research_panel_assignments rpa_all
               ON rpa_all.research_group_id = rds.research_group_id
              AND rpa_all.defense_schedule_id = rds.id
              AND rpa_all.defense_phase = 'Pre-Oral Defense'
              AND rpa_all.assignment_status = 'Assigned'
             LEFT JOIN sms2_db.users u_all ON u_all.id = rpa_all.panel_user_id
             LEFT JOIN preoral_defense_evaluations ev
               ON ev.defense_schedule_id = rds.id
              AND ev.panel_user_id = :panel_user_id
              AND ev.status = 'Submitted'
             WHERE rds.defense_datetime IS NOT NULL
               AND LOWER(TRIM(COALESCE(rds.defense_type, ''))) = 'pre-oral'
               AND LOWER(rds.status) IN ('scheduled', 'finalized', 'final', 'completed', 'passed', 'failed')
               AND rpa_self.id IS NOT NULL
               AND EXISTS (
                     SELECT 1
                     FROM research_groups rg_gate
                     WHERE rg_gate.id = rds.research_group_id
                       AND " . cradOfficialRegistryGroupWhereSql('rg_gate') . "
               )
               AND (
                     -- History mode additionally requires an evaluation; the registry
                     -- gate above keeps Assigned Defenses and details in sync with
                     -- Research Director records on every live poll.
                     NOT :history_gate
                     OR EXISTS (
                           SELECT 1
                           FROM research_groups rg_gate
                           WHERE rg_gate.id = rds.research_group_id
                             AND " . cradOfficialRegistryGroupWhereSql('rg_gate') . "
                     )
               )
             GROUP BY rds.id, rds.research_group_id, rds.proposal_id, rds.proposal_number,
                      rds.group_number, rds.research_group, rds.research_title, rds.adviser_name,
                      rds.panel_chair, rds.venue, rv.venue_name, rds.defense_datetime, rds.status, rds.updated_at,
                      rds.defense_end_datetime, rg.group_name, rg.academic_year, rpa_self.id,
                      ev.id, ev.result, ev.overall_score, ev.submitted_at
             ORDER BY COALESCE(rds.defense_datetime, rds.updated_at) DESC, rds.id DESC";

        $stmt = $crad->prepare($sql);
        $stmt->execute([
            ':panel_user_id'       => (int) getCurrentUserId(),
            ':panel_user_id_match' => (int) getCurrentUserId(),
            ':history_gate'        => $history ? 1 : 0,
        ]);

        $rows = array_values(array_filter($stmt->fetchAll() ?: [], static function (array $row) use ($history): bool {
            if (!panelScheduleIsFinal($row) || !panelCurrentUserIsAssigned($row)) {
                return false;
            }
            $hasEvaluation = !empty($row['evaluation_id']);
            return $history ? $hasEvaluation : !$hasEvaluation;
        }));

        return array_map('panelHydrateDefenseRow', $rows);
    } catch (Throwable $e) {
        error_log('Panel defense rows failed: ' . $e->getMessage());
        return [];
    }
}

function chapterPanelCanAccessSubmission(PDO $crad, array $submission): bool
{
    if (!smsIsPanelDefenseRole()) {
        return false;
    }

    $groupId = (int) ($submission['research_group_id'] ?? 0);
    if ($groupId <= 0) {
        return false;
    }

    try {
        $stmt = $crad->prepare(
            "SELECT id, panel_members, panel_chair, status, defense_datetime
             FROM research_defense_schedules
             WHERE research_group_id = ?
               AND defense_datetime IS NOT NULL
               AND LOWER(status) IN ('scheduled', 'finalized', 'final', 'completed', 'passed', 'failed')
               AND EXISTS (
                    SELECT 1
                    FROM research_panel_assignments rpa
                    WHERE rpa.research_group_id = research_defense_schedules.research_group_id
                      AND rpa.defense_schedule_id = research_defense_schedules.id
                      AND rpa.panel_user_id = ?
                      AND rpa.defense_phase = 'Pre-Oral Defense'
                      AND rpa.assignment_status = 'Assigned'
               )
               AND EXISTS (
                    SELECT 1
                    FROM research_groups rg_gate
                    WHERE rg_gate.id = research_defense_schedules.research_group_id
                      AND " . cradOfficialRegistryGroupWhereSql('rg_gate') . "
               )
             ORDER BY defense_datetime DESC, id DESC"
        );
        $stmt->execute([
            $groupId,
            (int) getCurrentUserId(),
        ]);
        foreach ($stmt->fetchAll() ?: [] as $row) {
            if (panelScheduleIsFinal($row)) {
                return true;
            }
        }
    } catch (Throwable $e) {
        error_log('Panel chapter document access check failed: ' . $e->getMessage());
    }

    return false;
}

function panelDefenseById(int $id, bool $allowCompleted = true): ?array
{
    if ($id <= 0) {
        return null;
    }

    $rows = array_merge(panelDefenseRows(false), $allowCompleted ? panelDefenseRows(true) : []);
    foreach ($rows as $row) {
        if ((int) ($row['id'] ?? 0) === $id) {
            return $row;
        }
    }
    return null;
}

function panelHydrateDefenseRow(array $row): array
{
    $row['group_name'] = (string) (($row['group_name'] ?? '') ?: ($row['research_group'] ?? ''));
    $row['academic_year'] = (string) (($row['academic_year'] ?? '') ?: 'Not recorded');
    $row['panel_members_list'] = panelMembersList($row['panel_members'] ?? '');
    $row['submitted_count'] = 0;
    $row['required_count'] = count($row['panel_members_list']);
    $row['final_result'] = null;

    $crad = panelDb();
    if ($crad instanceof PDO) {
        try {
            $stmt = $crad->prepare(
                "SELECT result FROM preoral_defense_evaluations
                 WHERE defense_schedule_id = ? AND status = 'Submitted'
                 ORDER BY submitted_at ASC, id ASC"
            );
            $stmt->execute([(int) ($row['id'] ?? 0)]);
            $results = array_map(static fn(array $r): string => (string) $r['result'], $stmt->fetchAll() ?: []);
            $row['submitted_count'] = count($results);
            if ($row['required_count'] > 0 && $row['submitted_count'] >= $row['required_count']) {
                $row['final_result'] = panelFinalResultFromResults($results);
            }
        } catch (Throwable $e) {
            error_log('Panel progress load failed: ' . $e->getMessage());
        }
    }

    return $row;
}

function panelFinalResultFromResults(array $results): string
{
    if (in_array('FAILED', $results, true)) {
        return 'FAILED';
    }
    if (in_array('APPROVED WITH REVISION', $results, true)) {
        return 'APPROVED WITH REVISION';
    }
    return 'APPROVED';
}

/**
 * Pre-Oral panel scoring: five criteria at 20% each = 100%.
 *
 * @return list<array{key:string,label:string,min:float,max:float}>
 */
function panelRubric(): array
{
    return [
        ['key' => 'content', 'label' => 'Content', 'min' => 0, 'max' => 20],
        ['key' => 'methodology', 'label' => 'Methodology', 'min' => 0, 'max' => 20],
        ['key' => 'references', 'label' => 'References', 'min' => 0, 'max' => 20],
        ['key' => 'format', 'label' => 'Format', 'min' => 0, 'max' => 20],
        ['key' => 'defense', 'label' => 'Defense', 'min' => 0, 'max' => 20],
    ];
}

function panelEvaluationTotalMax(): float
{
    $total = 0.0;
    foreach (panelRubric() as $criterion) {
        $total += (float) $criterion['max'];
    }
    return $total;
}

function panelEnsureEvaluationSchema(?PDO $crad = null): void
{
    $crad = $crad ?: panelDb();
    if (!$crad instanceof PDO) {
        return;
    }
    $crad->exec(
        "CREATE TABLE IF NOT EXISTS preoral_defense_evaluations (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            defense_schedule_id INT UNSIGNED NOT NULL,
            research_group_id INT UNSIGNED DEFAULT NULL,
            panel_user_id INT UNSIGNED NOT NULL,
            panel_name VARCHAR(150) NOT NULL DEFAULT '',
            content_score DECIMAL(5,2) NOT NULL,
            methodology_score DECIMAL(5,2) NOT NULL,
            references_score DECIMAL(5,2) NOT NULL,
            format_score DECIMAL(5,2) NOT NULL,
            defense_score DECIMAL(5,2) NOT NULL DEFAULT 0,
            remarks TEXT DEFAULT NULL,
            result ENUM('APPROVED','APPROVED WITH REVISION','FAILED') NOT NULL,
            overall_score DECIMAL(5,2) NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'Submitted',
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_preoral_panel_submission (defense_schedule_id, panel_user_id),
            KEY idx_preoral_group (research_group_id),
            KEY idx_preoral_panel (panel_user_id),
            KEY idx_preoral_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    try {
        if (!$crad->query("SHOW COLUMNS FROM preoral_defense_evaluations LIKE 'defense_score'")->fetch()) {
            $crad->exec(
                "ALTER TABLE preoral_defense_evaluations
                 ADD COLUMN defense_score DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER format_score"
            );
        }
    } catch (Throwable $e) {
        // keep going if the column already exists
    }
}

function panelFormatDate(?string $value, string $format = 'M j, Y h:i A'): string
{
    $timestamp = $value ? strtotime($value) : false;
    return $timestamp ? date($format, $timestamp) : 'Not recorded';
}

function panelChapterDocuments(int $groupId): array
{
    $crad = panelDb();
    if (!$crad instanceof PDO || $groupId <= 0) {
        return [];
    }

    try {
        $documents = [];
        foreach ([1, 2, 3] as $chapter) {
            $stmt = $crad->prepare(
                "SELECT id, chapter_number, version_number, status, original_name, submitted_at
                 FROM chapter_submissions
                 WHERE research_group_id = ?
                   AND chapter_number = ?
                   AND status = 'Accepted'
                 ORDER BY version_number DESC, id DESC
                 LIMIT 1"
            );
            $stmt->execute([$groupId, $chapter]);
            $row = $stmt->fetch();
            if ($row) {
                $documents[] = $row;
            }
        }
        return $documents;
    } catch (Throwable $e) {
        error_log('Panel document load failed: ' . $e->getMessage());
        return [];
    }
}

function panelStudentMembers(array $defense): array
{
    $crad = panelDb();
    $proposalId = (int) ($defense['proposal_id'] ?? 0);
    if (!$crad instanceof PDO || $proposalId <= 0) {
        return [];
    }

    try {
        $stmt = $crad->prepare(
            "SELECT student_name, student_id, email
             FROM proposal_members
             WHERE proposal_id = ?
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([$proposalId]);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Panel team load failed: ' . $e->getMessage());
        return [];
    }
}

function panelSubmitEvaluation(int $scheduleId, array $data): array
{
    $crad = panelDb();
    if (!$crad instanceof PDO) {
        return ['ok' => false, 'error' => 'CRAD database unavailable.'];
    }
    panelEnsureEvaluationSchema($crad);

    $defense = panelDefenseById($scheduleId, false);
    if (!$defense) {
        return ['ok' => false, 'error' => 'Access denied or evaluation already submitted.'];
    }

    $scores = [];
    foreach (panelRubric() as $criterion) {
        $key = $criterion['key'] . '_score';
        $raw = trim((string) ($data[$key] ?? ''));
        if ($raw === '' || !is_numeric($raw)) {
            return ['ok' => false, 'error' => 'Please enter a valid score for ' . $criterion['label'] . '.'];
        }
        $score = (float) $raw;
        $max = (float) $criterion['max'];
        if ($score < (float) $criterion['min']) {
            return ['ok' => false, 'error' => $criterion['label'] . ' Score cannot be below ' . $criterion['min'] . '.'];
        }
        if ($score > $max) {
            return ['ok' => false, 'error' => $criterion['label'] . ' Score cannot exceed ' . number_format($max, 0) . '%. Evaluation was not submitted.'];
        }
        $scores[$criterion['key']] = round($score, 2);
    }

    $result = strtoupper(trim((string) ($data['result'] ?? '')));
    if (!in_array($result, ['APPROVED', 'APPROVED WITH REVISION', 'FAILED'], true)) {
        return ['ok' => false, 'error' => 'Please select a valid result.'];
    }

    $overall = round(array_sum($scores), 2);
    $totalMax = panelEvaluationTotalMax();
    if ($overall > $totalMax) {
        return ['ok' => false, 'error' => 'Total score cannot exceed ' . number_format($totalMax, 0) . '%. Evaluation was not submitted.'];
    }
    $remarks = trim((string) ($data['remarks'] ?? ''));

    try {
        $crad->beginTransaction();
        $stmt = $crad->prepare(
            "INSERT INTO preoral_defense_evaluations
                (defense_schedule_id, research_group_id, panel_user_id, panel_name,
                 content_score, methodology_score, references_score, format_score, defense_score,
                 remarks, result, overall_score, status, submitted_at, created_at)
             VALUES
                (:schedule_id, :group_id, :panel_user_id, :panel_name,
                 :content_score, :methodology_score, :references_score, :format_score, :defense_score,
                 :remarks, :result, :overall_score, 'Submitted', NOW(), NOW())"
        );
        $stmt->execute([
            ':schedule_id' => $scheduleId,
            ':group_id' => (int) ($defense['research_group_id'] ?? 0) ?: null,
            ':panel_user_id' => (int) getCurrentUserId(),
            ':panel_name' => getCurrentUserName(),
            ':content_score' => $scores['content'],
            ':methodology_score' => $scores['methodology'],
            ':references_score' => $scores['references'],
            ':format_score' => $scores['format'],
            ':defense_score' => $scores['defense'],
            ':remarks' => $remarks,
            ':result' => $result,
            ':overall_score' => $overall,
        ]);
        $crad->commit();
        logActivity('create', 'Submitted Pre-Oral Defense evaluation for schedule #' . $scheduleId, 'faculty');
        return ['ok' => true, 'message' => 'Evaluation submitted successfully.'];
    } catch (PDOException $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'error' => 'This defense already has your final evaluation.'];
        }
        error_log('Panel evaluation submit failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Unable to submit evaluation.'];
    }
}

function panelRenderAssignedRows(array $rows): void
{
    if (!$rows): ?>
        <div class="text-center text-muted py-5">No defense records assigned to your panel account yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Research Group</th><th>Reference</th><th>Research Title</th><th>Date / Time</th><th>Venue</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr data-defense-id="<?= (int) $row['id'] ?>">
                            <td><strong><?= e((string) (($row['group_name'] ?? '') ?: 'Research Group')) ?></strong></td>
                            <td><?= e((string) (($row['group_number'] ?? '') ?: ($row['proposal_number'] ?? ''))) ?></td>
                            <td><?= e((string) ($row['research_title'] ?? 'Research title pending')) ?></td>
                            <td><?= e(panelFormatDate($row['defense_datetime'] ?? null)) ?></td>
                            <td><?= e((string) (($row['venue'] ?? '') ?: 'TBA')) ?></td>
                            <td><span class="badge text-bg-primary"><?= e((string) ($row['status'] ?? 'Scheduled')) ?></span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-sms-primary" href="<?= BASE_URL ?>/modules/faculty/pages/defense-details.php?id=<?= (int) $row['id'] ?>">View Defense</a>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/modules/faculty/pages/panel-evaluation-scoring.php?id=<?= (int) $row['id'] ?>">Evaluate</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif;
}

function panelRenderDefenseDetails(?array $defense): void
{
    if (!$defense): ?>
        <div class="text-center text-muted py-5">Select an assigned defense to view details.</div>
    <?php return; endif;
    $documents = panelChapterDocuments((int) ($defense['research_group_id'] ?? 0));
    $students = panelStudentMembers($defense);
    ?>
    <div class="row g-4">
        <div class="col-lg-6"><section class="glass-panel p-4 h-100">
            <h5 class="mb-3">Research Information</h5>
            <div class="mb-2"><small class="text-muted">Group Number</small><div class="fw-bold"><?= e((string) ($defense['group_number'] ?? '')) ?></div></div>
            <div class="mb-2"><small class="text-muted">Group Name</small><div><?= e((string) ($defense['group_name'] ?? '')) ?></div></div>
            <div class="mb-2"><small class="text-muted">Research Title</small><div class="fw-bold"><?= e((string) ($defense['research_title'] ?? '')) ?></div></div>
            <div><small class="text-muted">Academic Year</small><div><?= e((string) ($defense['academic_year'] ?? 'Not recorded')) ?></div></div>
        </section></div>
        <div class="col-lg-6"><section class="glass-panel p-4 h-100">
            <h5 class="mb-3">Defense Information</h5>
            <div class="mb-2"><small class="text-muted">Defense Type</small><div class="fw-bold">Pre-Oral Defense</div></div>
            <div class="mb-2"><small class="text-muted">Date</small><div><?= e(panelFormatDate($defense['defense_datetime'] ?? null, 'M j, Y')) ?></div></div>
            <div class="mb-2"><small class="text-muted">Start Time</small><div><?= e(panelFormatDate($defense['defense_datetime'] ?? null, 'h:i A')) ?></div></div>
            <div class="mb-2"><small class="text-muted">End Time</small><div><?= e(panelFormatDate($defense['defense_end_datetime'] ?? null, 'h:i A')) ?></div></div>
            <div class="mb-2"><small class="text-muted">Venue</small><div><?= e((string) (($defense['venue'] ?? '') ?: 'TBA')) ?></div></div>
            <div><small class="text-muted">Schedule Status</small><div><span class="badge text-bg-primary"><?= e((string) ($defense['status'] ?? 'Scheduled')) ?></span></div></div>
        </section></div>
        <div class="col-lg-6"><section class="glass-panel p-4 h-100">
            <h5 class="mb-3">Research Team</h5>
            <?php if ($students): foreach ($students as $student): ?>
                <div class="mb-2"><strong><?= e((string) $student['student_name']) ?></strong><div class="small text-muted"><?= e((string) $student['student_id']) ?> · <?= e((string) $student['email']) ?></div></div>
            <?php endforeach; else: ?>
                <div class="text-muted">Student researcher list is not recorded for this schedule.</div>
            <?php endif; ?>
            <hr><small class="text-muted">Research Adviser</small><div class="fw-bold"><?= e((string) (($defense['adviser_name'] ?? '') ?: 'Not recorded')) ?></div>
        </section></div>
        <div class="col-lg-6"><section class="glass-panel p-4 h-100">
            <h5 class="mb-3">Panel Members</h5>
            <?php foreach (($defense['panel_members_list'] ?? []) as $member): ?>
                <div class="mb-2"><?= smsIcon('user-check', ['class' => 'me-2 text-primary']) ?><?= e((string) $member) ?></div>
            <?php endforeach; ?>
            <?php if (empty($defense['panel_members_list'])): ?><div class="text-muted">No panel members recorded.</div><?php endif; ?>
        </section></div>
        <div class="col-12"><section class="glass-panel p-4">
            <h5 class="mb-3">Documents</h5>
            <?php if (!$documents): ?><div class="text-muted">No accepted Chapter 1-3 documents available.</div>
            <?php else: ?><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Chapter</th><th>File</th><th>Status</th><th>Submitted</th><th></th></tr></thead><tbody>
                <?php foreach ($documents as $document): ?><tr>
                    <td>Chapter <?= (int) $document['chapter_number'] ?></td>
                    <td><?= e((string) $document['original_name']) ?></td>
                    <td><span class="badge text-bg-success"><?= e((string) $document['status']) ?></span></td>
                    <td><?= e(panelFormatDate($document['submitted_at'] ?? null)) ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= e(chapterDocumentUrl((int) $document['id'])) ?>">Open</a></td>
                </tr><?php endforeach; ?>
            </tbody></table></div><?php endif; ?>
        </section></div>
        <div class="col-12"><section class="glass-panel p-4">
            <h5 class="mb-3">Panel Evaluations</h5>
            <div class="fw-bold"><?= (int) ($defense['submitted_count'] ?? 0) ?> / <?= (int) ($defense['required_count'] ?? 0) ?> Submitted</div>
            <div class="text-muted"><?= $defense['final_result'] ? 'Final Result: ' . e((string) $defense['final_result']) : 'Overall Defense Result: Waiting for remaining panel evaluation' ?></div>
            <?php if (!empty($defense['evaluation_id'])): ?>
                <hr>
                <div class="row g-3">
                    <div class="col-md-4"><small class="text-muted">Your Result</small><div class="fw-bold"><?= e((string) ($defense['panel_result'] ?? 'Submitted')) ?></div></div>
                    <div class="col-md-4"><small class="text-muted">Your Score</small><div class="fw-bold"><?= e((string) ($defense['panel_score'] ?? '')) ?></div></div>
                    <div class="col-md-4"><small class="text-muted">Submitted</small><div><?= e(panelFormatDate($defense['submitted_at'] ?? null)) ?></div></div>
                </div>
            <?php endif; ?>
        </section></div>
    </div>
    <?php
}

function panelRenderScoring(?array $defense, string $message = '', string $error = ''): void
{
    if ($message !== ''): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif;
    if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif;
    if (!$defense): ?>
        <section class="glass-panel p-4">
            <h5 class="mb-3">Pending Evaluations</h5>
            <?php panelRenderAssignedRows(panelDefenseRows(false)); ?>
        </section>
    <?php return; endif; ?>
    <section class="glass-panel p-4">
        <h5 class="mb-3">Evaluation & Scoring</h5>
        <div class="row g-3 mb-3">
            <div class="col-md-6"><small class="text-muted">Research Group</small><div class="fw-bold"><?= e((string) ($defense['group_name'] ?? '')) ?></div></div>
            <div class="col-md-6"><small class="text-muted">Research Title</small><div class="fw-bold"><?= e((string) ($defense['research_title'] ?? '')) ?></div></div>
            <div class="col-md-3"><small class="text-muted">Defense Type</small><div>Pre-Oral Defense</div></div>
            <div class="col-md-3"><small class="text-muted">Date</small><div><?= e(panelFormatDate($defense['defense_datetime'] ?? null)) ?></div></div>
            <div class="col-md-3"><small class="text-muted">Venue</small><div><?= e((string) (($defense['venue'] ?? '') ?: 'TBA')) ?></div></div>
            <div class="col-md-3"><small class="text-muted">Panel Member</small><div><?= e(getCurrentUserName()) ?></div></div>
        </div>
        <form method="post" data-panel-evaluation-form data-total-max="<?= e((string) panelEvaluationTotalMax()) ?>">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="submit_evaluation">
            <input type="hidden" name="schedule_id" value="<?= (int) $defense['id'] ?>">
            <?php foreach (panelRubric() as $criterion): ?>
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label"><?= e($criterion['label']) ?> Score <span class="text-muted fw-normal">(max <?= (int) $criterion['max'] ?>%)</span></label>
                        <input type="number"
                               class="form-control js-panel-score"
                               name="<?= e($criterion['key']) ?>_score"
                               min="<?= (int) $criterion['min'] ?>"
                               max="<?= (int) $criterion['max'] ?>"
                               step="0.01"
                               data-max="<?= (int) $criterion['max'] ?>"
                               data-label="<?= e($criterion['label']) ?>"
                               required>
                        <div class="invalid-feedback js-score-feedback"></div>
                    </div>
                    <div class="col-md-8"><label class="form-label"><?= e($criterion['label']) ?> Remarks</label><input type="text" class="form-control" maxlength="1000"></div>
                </div>
            <?php endforeach; ?>
            <div class="mb-3"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="4"></textarea></div>
            <div id="panelScoreError" class="alert alert-danger d-none" role="alert"></div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Overall Score</label>
                    <div class="input-group">
                        <input type="text" class="form-control" data-panel-overall readonly value="0.00">
                        <span class="input-group-text">/ <?= number_format(panelEvaluationTotalMax(), 0) ?>%</span>
                    </div>
                </div>
                <div class="col-md-6"><label class="form-label">Possible Result</label><select class="form-select" name="result" required><option value="">Select result...</option><option value="APPROVED">APPROVED</option><option value="APPROVED WITH REVISION">APPROVED WITH REVISION</option><option value="FAILED">FAILED</option></select></div>
            </div>
            <button type="button" class="btn btn-sms-primary" data-panel-open-confirm><?= smsIcon('check', ['class' => 'me-2']) ?>Submit Evaluation</button>
        </form>
    </section>
    <div class="modal fade" id="panelSubmitConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
            <div class="modal-content shadow" style="border-radius:14px;overflow:hidden;">
                <div class="modal-header border-0 pb-1">
                    <h6 class="modal-title fw-bold">Submit Evaluation</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-3" style="font-size:0.9rem;color:var(--sms-text,#374151);">
                    Are you sure you want to submit this<br>Pre-Oral Defense evaluation?
                </div>
                <div class="modal-footer border-0 justify-content-center gap-2 pt-0 pb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sms-primary btn-sm px-4" data-panel-confirm-submit>Submit Evaluation</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function panelRenderHistoryRows(array $rows): void
{
    if (!$rows): ?>
        <div class="text-center text-muted py-5">No submitted Pre-Oral Defense evaluations yet.</div>
    <?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Research Group</th><th>Defense</th><th>Submitted</th><th>Result</th><th>Score</th><th>Action</th></tr></thead><tbody>
            <?php foreach ($rows as $row): ?><tr>
                <td><strong><?= e((string) (($row['group_name'] ?? '') ?: 'Research Group')) ?></strong><div class="small text-muted"><?= e((string) ($row['research_title'] ?? '')) ?></div></td>
                <td>Pre-Oral Defense</td>
                <td><?= e(panelFormatDate($row['submitted_at'] ?? null)) ?></td>
                <td><span class="badge text-bg-success"><?= e((string) ($row['panel_result'] ?? 'Submitted')) ?></span></td>
                <td><?= e((string) ($row['panel_score'] ?? '')) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/modules/faculty/pages/defense-details.php?id=<?= (int) $row['id'] ?>">View Evaluation</a></td>
            </tr><?php endforeach; ?>
        </tbody></table></div>
    <?php endif;
}

function renderPanelDefensePage(string $mode, string $message = '', string $error = ''): void
{
    panelRequirePanelMember();
    panelEnsureEvaluationSchema();
    $id = (int) ($_GET['id'] ?? 0);
    $defense = $id > 0 ? panelDefenseById($id, true) : null;
    if ($mode === 'details' && $id <= 0) {
        $available = array_merge(panelDefenseRows(false), panelDefenseRows(true));
        if (count($available) === 1) {
            $defense = $available[0];
        }
    }
    ?>
    <div class="glass-dashboard" data-panel-live="<?= e($mode) ?>" data-endpoint="<?= e(BASE_URL . '/modules/faculty/api/panel-defense.php') ?>">
        <?php if ($mode === 'assigned'): ?>
            <?php $rows = panelDefenseRows(false); ?>
            <section class="glass-panel p-4">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><h5 class="mb-0"><?= smsIcon('clipboard-list', ['class' => 'me-2 text-primary']) ?>Assigned Defenses</h5><span class="badge text-bg-primary" data-panel-count><?= count($rows) ?> Records</span></div>
                <div data-panel-content><?php panelRenderAssignedRows($rows); ?></div>
            </section>
        <?php elseif ($mode === 'details'): ?>
            <div data-panel-content>
                <?php
                if (!$defense && $id <= 0) {
                    echo '<section class="glass-panel p-4"><h5 class="mb-3">Select Defense</h5>';
                    panelRenderAssignedRows(array_merge(panelDefenseRows(false), panelDefenseRows(true)));
                    echo '</section>';
                } else {
                    panelRenderDefenseDetails($defense);
                }
                ?>
            </div>
        <?php elseif ($mode === 'scoring'): ?>
            <div data-panel-content><?php panelRenderScoring($defense, $message, $error); ?></div>
        <?php else: ?>
            <?php $rows = panelDefenseRows(true); ?>
            <section class="glass-panel p-4">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3"><h5 class="mb-0"><?= smsIcon('history', ['class' => 'me-2 text-primary']) ?>Evaluation History</h5><span class="badge text-bg-primary" data-panel-count><?= count($rows) ?> Records</span></div>
                <div data-panel-content><?php panelRenderHistoryRows($rows); ?></div>
            </section>
        <?php endif; ?>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/panel-defense-live.js?v=panel-defense-20-1"></script>
    <?php
}
