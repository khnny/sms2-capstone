<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';

function finalDefenseRequirePanelMember(): void
{
    requireAuth();
    if (!smsIsPanelDefenseRole()) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function finalDefenseDb(): ?PDO
{
    return function_exists('cradDb') ? cradDb() : null;
}

function finalDefenseCurrentPanelId(PDO $crad): int
{
    $sessionName = trim((string) ($_SESSION['user_name'] ?? ''));
    $sessionEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    $assignmentIdentities = [
        ['column' => 'panel_name', 'value' => $sessionName, 'expression' => 'TRIM(panel_name)'],
        ['column' => 'panel_email', 'value' => $sessionEmail, 'expression' => 'LOWER(TRIM(panel_email))'],
    ];
    foreach ($assignmentIdentities as $identity) {
        if ($identity['value'] === '') {
            continue;
        }
        try {
            $stmt = $crad->prepare(
                "SELECT panel_user_id
                 FROM `crad_research_panel_assignments`
                 WHERE {$identity['expression']} = ?
                   AND defense_phase = 'Final Defense'
                   AND assignment_status = 'Assigned'
                 ORDER BY id DESC
                 LIMIT 1"
            );
            $stmt->execute([$identity['value']]);
            $panelId = (int) ($stmt->fetchColumn() ?: 0);
            if ($panelId > 0) {
                return $panelId;
            }
        } catch (Throwable $e) {
            error_log('Final Defense assignment identity lookup failed: ' . $e->getMessage());
        }
    }

    foreach ([
        ['column' => 'full_name', 'value' => $sessionName],
        ['column' => 'email', 'value' => $sessionEmail],
    ] as $identity) {
        if ($identity['value'] === '') {
            continue;
        }
        try {
            $operator = $identity['column'] === 'email' ? 'LOWER(TRIM(email))' : 'TRIM(full_name)';
            $stmt = $crad->prepare("SELECT id FROM sms2_users WHERE {$operator} = ? AND role_key = 'panel' LIMIT 1");
            $stmt->execute([$identity['value']]);
            $userId = (int) ($stmt->fetchColumn() ?: 0);
            if ($userId > 0) {
                return $userId;
            }
        } catch (Throwable $e) {
            error_log('Final Defense panel identity lookup failed: ' . $e->getMessage());
        }
    }

    return (int) (getCurrentUserId() ?? 0);
}

/**
 * Final Defense panel scoring: five criteria at 20% each = 100%.
 *
 * @return list<array{key:string,label:string,min:float,max:float}>
 */
function finalDefenseRubric(): array
{
    return [
        ['key' => 'content', 'label' => 'Content', 'min' => 0, 'max' => 20],
        ['key' => 'methodology', 'label' => 'Methodology', 'min' => 0, 'max' => 20],
        ['key' => 'references', 'label' => 'References', 'min' => 0, 'max' => 20],
        ['key' => 'format', 'label' => 'Format', 'min' => 0, 'max' => 20],
        ['key' => 'defense', 'label' => 'Defense', 'min' => 0, 'max' => 20],
    ];
}

function finalDefenseEvaluationTotalMax(): float
{
    $total = 0.0;
    foreach (finalDefenseRubric() as $criterion) {
        $total += (float) $criterion['max'];
    }
    return $total;
}

function finalDefenseAssignedSchedule(PDO $crad, int $scheduleId): ?array
{
    if ($scheduleId <= 0) {
        return null;
    }

    $stmt = $crad->prepare(
        "SELECT rds.id, rds.research_group_id, rds.group_number, rds.research_group,
                rds.research_title, rds.adviser_name, rds.venue,
                rds.defense_datetime, rds.defense_end_datetime, rds.status,
                rds.defense_type,
                                rpa.panel_user_id AS assigned_panel_user_id,
                                rpa.panel_name,
                                (SELECT fde.id FROM `crad_final_defense_evaluations` fde
                 WHERE fde.defense_schedule_id = rds.id
                                     AND fde.panel_user_id = rpa.panel_user_id
                 LIMIT 1) AS evaluation_id
         FROM `crad_research_defense_schedules` rds
         INNER JOIN `crad_research_panel_assignments` rpa
           ON rpa.research_group_id = rds.research_group_id
                    AND rpa.defense_schedule_id = rds.id
           AND rpa.defense_phase = 'Final Defense'
          AND rpa.assignment_status = 'Assigned'
                    AND rpa.panel_user_id = :panel_id
         WHERE rds.id = :schedule_id
           AND LOWER(TRIM(COALESCE(rds.defense_type, ''))) = 'final defense'
           AND rds.defense_datetime IS NOT NULL
           AND EXISTS (
                SELECT 1
                FROM `crad_research_groups` rg_gate
                WHERE rg_gate.id = rds.research_group_id
                  AND " . cradOfficialRegistryGroupWhereSql('rg_gate') . "
           )
         LIMIT 1"
    );
    $panelId = finalDefenseCurrentPanelId($crad);
    $stmt->execute([
        ':panel_id' => $panelId,
        ':schedule_id' => $scheduleId,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function finalDefenseRows(PDO $crad, bool $history = false): array
{
    $panelId = finalDefenseCurrentPanelId($crad);
    $evaluationFilter = $history ? 'fde.id IS NOT NULL' : 'fde.id IS NULL';

    $stmt = $crad->prepare(
        "SELECT rds.id, rds.research_group_id, rds.group_number, rds.research_group,
                rds.research_title, rds.adviser_name, rds.venue,
                rds.defense_datetime, rds.defense_end_datetime, rds.status,
                rds.defense_type, fde.id AS evaluation_id,
                fde.result AS panel_result, fde.overall_score AS panel_score,
                fde.submitted_at
         FROM `crad_research_defense_schedules` rds
         INNER JOIN `crad_research_panel_assignments` rpa
           ON rpa.research_group_id = rds.research_group_id
          AND rpa.defense_schedule_id = rds.id
          AND rpa.panel_user_id = :panel_id
          AND rpa.defense_phase = 'Final Defense'
          AND rpa.assignment_status = 'Assigned'
         LEFT JOIN `crad_final_defense_evaluations` fde
           ON fde.defense_schedule_id = rds.id
          AND fde.panel_user_id = :panel_id_eval
         WHERE LOWER(TRIM(COALESCE(rds.defense_type, ''))) = 'final defense'
           AND rds.defense_datetime IS NOT NULL
           AND LOWER(rds.status) IN ('scheduled', 'finalized', 'final', 'completed', 'passed', 'failed')
           AND EXISTS (
                SELECT 1
                FROM `crad_research_groups` rg_gate
                WHERE rg_gate.id = rds.research_group_id
                  AND " . cradOfficialRegistryGroupWhereSql('rg_gate') . "
           )
           AND {$evaluationFilter}
         ORDER BY rds.defense_datetime DESC, rds.id DESC"
    );
    $stmt->execute([
        ':panel_id' => $panelId,
        ':panel_id_eval' => $panelId,
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function finalDefenseSubmitEvaluation(PDO $crad, int $scheduleId, array $data): array
{
    $defense = finalDefenseAssignedSchedule($crad, $scheduleId);
    if (!$defense) {
        return ['ok' => false, 'error' => 'This Final Defense is not assigned to your panel account.'];
    }
    if ($defense['evaluation_id'] !== null) {
        return ['ok' => false, 'error' => 'This Final Defense already has your evaluation.'];
    }

    $scores = [];
    foreach (finalDefenseRubric() as $criterion) {
        $raw = trim((string) ($data[$criterion['key'] . '_score'] ?? ''));
        if ($raw === '' || !is_numeric($raw)) {
            return ['ok' => false, 'error' => 'Please enter a valid score for ' . $criterion['label'] . '.'];
        }
        $score = (float) $raw;
        if ($score < $criterion['min'] || $score > $criterion['max']) {
            return [
                'ok' => false,
                'error' => $criterion['label'] . ' Score cannot exceed ' . (int) $criterion['max']
                    . '%. Evaluation cannot be submitted.',
            ];
        }
        $scores[$criterion['key']] = $score;
    }

    $overall = round(array_sum($scores), 2);
    $totalMax = finalDefenseEvaluationTotalMax();
    if ($overall > $totalMax) {
        return [
            'ok' => false,
            'error' => 'Total score cannot exceed ' . (int) $totalMax . '%. Evaluation cannot be submitted.',
        ];
    }

    $result = strtoupper(trim((string) ($data['result'] ?? '')));
    if (!in_array($result, ['APPROVED', 'APPROVED WITH REVISION', 'FAILED'], true)) {
        return ['ok' => false, 'error' => 'Please select a valid result.'];
    }

    try {
        $stmt = $crad->prepare(
            "INSERT INTO `crad_final_defense_evaluations`
                (defense_schedule_id, research_group_id, panel_user_id, panel_name,
                 content_score, methodology_score, references_score, format_score, defense_score,
                 remarks, result, overall_score, status, submitted_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', NOW(), NOW())"
        );
        $stmt->execute([
            $scheduleId,
            (int) ($defense['research_group_id'] ?? 0) ?: null,
            (int) ($defense['assigned_panel_user_id'] ?? finalDefenseCurrentPanelId($crad)),
            getCurrentUserName(),
            $scores['content'],
            $scores['methodology'],
            $scores['references'],
            $scores['format'],
            $scores['defense'],
            trim((string) ($data['remarks'] ?? '')),
            $result,
            $overall,
        ]);
        return ['ok' => true, 'message' => 'Final Defense evaluation submitted successfully.'];
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'error' => 'This Final Defense already has your evaluation.'];
        }
        error_log('Final Defense evaluation submit failed: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Unable to submit Final Defense evaluation.'];
    }
}
