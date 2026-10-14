<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/research-services-clearance.php';

const RD_PANEL_CONTEXT_KEY = 'panel_assignment_context';
const RD_PANEL_DEFAULT_REQUIRED_COUNT = 3;

function rdPanelDb(): ?PDO
{
    return function_exists('cradDb') ? cradDb() : null;
}

function rdPanelPageUrl(string $view, array $params = []): string
{
    $routes = [
        'retrieve-defense-ready-research' => '/modules/crad/pages/retrieve-defense-ready-research.php',
        'select-panel-members' => '/modules/crad/pages/select-panel-members.php',
        'check-panel-availability' => '/modules/crad/pages/check-panel-availability.php',
        'assign-panel-members' => '/modules/crad/pages/assign-panel-members.php',
    ];
    $path = $routes[$view] ?? $routes['retrieve-defense-ready-research'];
    $query = $params ? ('?' . http_build_query($params)) : '';

    return BASE_URL . $path . $query;
}

function rdPanelRequiredCount(): int
{
    if (defined('RESEARCH_COORDINATOR_REQUIRED_PANEL_COUNT')) {
        return max(1, (int) constant('RESEARCH_COORDINATOR_REQUIRED_PANEL_COUNT'));
    }
    if (defined('RESEARCH_DIRECTOR_REQUIRED_PANEL_COUNT')) {
        return max(1, (int) constant('RESEARCH_DIRECTOR_REQUIRED_PANEL_COUNT'));
    }

    return RD_PANEL_DEFAULT_REQUIRED_COUNT;
}

function rdPanelActiveAssignmentSql(string $alias = 'rpa'): string
{
    return "{$alias}.defense_phase = 'Pre-Oral Defense' AND {$alias}.assignment_status = 'Assigned'";
}

function rdPanelAssignmentState(int $panelCount): string
{
    if ($panelCount <= 0) {
        return 'pending';
    }

    return $panelCount >= rdPanelRequiredCount() ? 'complete' : 'partial';
}

function rdPanelAssignmentComplete(int $panelCount): bool
{
    return rdPanelAssignmentState($panelCount) === 'complete';
}

function rdPanelNamesFromString(string $value): array
{
    $names = preg_split('/\r\n|\r|\n|,/', $value) ?: [];
    $clean = [];
    foreach ($names as $name) {
        $name = trim($name);
        if ($name !== '') {
            $clean[strtolower($name)] = $name;
        }
    }

    return array_values($clean);
}

function rdPanelFormatNames(array $names): string
{
    $clean = [];
    foreach ($names as $name) {
        $name = trim((string) $name);
        if ($name !== '') {
            $clean[strtolower($name)] = $name;
        }
    }

    return implode("\n", array_values($clean));
}

function rdPanelReadySql(): string
{
    return "SELECT
                rg.id AS research_group_id,
                rg.proposal_id,
                rg.title_approval_id,
                rg.proposal_number,
                rg.group_number,
                COALESCE(NULLIF(rg.group_name, ''), rg.group_number, CONCAT('Group ', LPAD(rg.id, 2, '0'))) AS group_name,
                COALESCE(NULLIF(rg.research_title, ''), 'Research title pending') AS research_title,
                rg.academic_year,
                COALESCE(NULLIF(raa.adviser_name, ''), NULLIF(rg.adviser, ''), 'For adviser verification') AS adviser_name,
                COUNT(DISTINCT rpa.panel_user_id) AS panel_count,
                GROUP_CONCAT(DISTINCT COALESCE(NULLIF(u.full_name, ''), NULLIF(rpa.panel_name, ''), 'Panel Member') ORDER BY COALESCE(NULLIF(u.full_name, ''), NULLIF(rpa.panel_name, ''), 'Panel Member') SEPARATOR '\n') AS panel_members,
                MAX(rpa.updated_at) AS panel_updated_at,
                GREATEST(
                    COALESCE(MAX(rsc.updated_at), '1000-01-01 00:00:00'),
                    COALESCE(MAX(rpa.updated_at), '1000-01-01 00:00:00')
                ) AS updated_at
             FROM `crad_research_groups` rg
             LEFT JOIN `crad_research_adviser_assignments` raa ON raa.id = (
                SELECT raa2.id FROM `crad_research_adviser_assignments` raa2
                WHERE raa2.assignment_status IN ('Assigned', 'Confirmed')
                  AND ((raa2.research_group_id IS NOT NULL AND raa2.research_group_id = rg.id)
                    OR (raa2.group_number IS NOT NULL AND raa2.group_number <> '' AND raa2.group_number = rg.group_number))
                ORDER BY (raa2.assignment_status = 'Confirmed') DESC, raa2.updated_at DESC, raa2.id DESC LIMIT 1
             )
             INNER JOIN `crad_research_services_clearances` rsc
               ON rsc.research_group_id = rg.id
              AND rsc.research_stage = 'research_1'
              AND rsc.status = 'clearance_done'
             LEFT JOIN `crad_research_panel_assignments` rpa
               ON rpa.research_group_id = rg.id
              AND " . rdPanelActiveAssignmentSql('rpa') . "
             LEFT JOIN sms2_users u ON u.id = rpa.panel_user_id
             GROUP BY rg.id, rg.proposal_id, rg.title_approval_id, rg.proposal_number, rg.group_number,
                      group_name, research_title, rg.academic_year, adviser_name";
}

function rdPanelReadyRows(): array
{
    $crad = rdPanelDb();
    if (!$crad instanceof PDO) {
        return [];
    }
    try {
        rscEnsureSchema($crad);
        return $crad->query(rdPanelReadySql() . " ORDER BY updated_at DESC, research_group_id DESC")->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('RD panel ready rows failed: ' . $e->getMessage());
        return [];
    }
}

function rdPanelReadyGroup(int $groupId): ?array
{
    foreach (rdPanelReadyRows() as $row) {
        if ((int) $row['research_group_id'] === $groupId) {
            return $row;
        }
    }
    return null;
}

function rdPanelContext(): array
{
    $context = $_SESSION[RD_PANEL_CONTEXT_KEY] ?? [];
    if (!is_array($context)) {
        $context = [];
    }
    return [
        'group_id' => (int) ($context['group_id'] ?? 0),
        'selected_panel_ids' => array_values(array_unique(array_filter(array_map('intval', (array) ($context['selected_panel_ids'] ?? []))))),
    ];
}

function rdPanelSaveContext(int $groupId, array $panelIds): void
{
    if ($groupId <= 0) {
        unset($_SESSION[RD_PANEL_CONTEXT_KEY]);
        return;
    }

    $_SESSION[RD_PANEL_CONTEXT_KEY] = [
        'group_id' => $groupId,
        'selected_panel_ids' => array_values(array_unique(array_filter(array_map('intval', $panelIds)))),
    ];
}

function rdPanelClearContext(): void
{
    unset($_SESSION[RD_PANEL_CONTEXT_KEY]);
}

function rdPanelEligibleRoleKeys(): array
{
    return ['panel', 'department_chair'];
}

function rdPanelRoleLabel(string $roleKey): string
{
    return $roleKey === 'department_chair' ? 'Department Chair' : 'Panel Member';
}

function rdPanelFacultyRows(): array
{
    $sms = db();
    $crad = rdPanelDb();
    if (!$sms instanceof PDO) {
        return [];
    }

    $roles = rdPanelEligibleRoleKeys();
    $placeholders = implode(',', array_fill(0, count($roles), '?'));
    $stmt = $sms->prepare(
        "SELECT id, full_name, username, email, role_key
         FROM `sms2_users`
         WHERE role_key IN ($placeholders)
           AND status = 'active'
         ORDER BY CASE WHEN role_key = 'department_chair' THEN 0 ELSE 1 END, full_name ASC"
    );
    $stmt->execute($roles);
    $rows = $stmt->fetchAll() ?: [];

    if (!$rows) {
        return [];
    }

    $availabilityStmt = null;
    $loadStmt = null;
    $ensureAvailability = null;
    if ($crad instanceof PDO) {
        $availabilityStmt = $crad->prepare("SELECT availability_status, notes FROM `crad_panel_member_availability` WHERE panel_user_id = ?");
        $loadStmt = $crad->prepare(
            "SELECT COUNT(*) FROM `crad_research_panel_assignments`
             WHERE panel_user_id = ? AND assignment_status = 'Assigned' AND defense_phase = 'Pre-Oral Defense'"
        );
        $ensureAvailability = $crad->prepare(
            "INSERT INTO `crad_panel_member_availability` (panel_user_id, availability_status, notes, updated_at, created_at)
             VALUES (?, 'Available', '', NOW(), NOW())
             ON DUPLICATE KEY UPDATE panel_user_id = panel_user_id"
        );
    }

    foreach ($rows as &$row) {
        $userId = (int) $row['id'];
        $row['role_key'] = (string) ($row['role_key'] ?? 'panel');
        $row['role_label'] = rdPanelRoleLabel($row['role_key']);
        $row['expertise'] = $row['role_key'] === 'department_chair' ? 'Department Chair' : '';
        $row['availability_status'] = 'Available';
        $row['availability_notes'] = '';
        $row['current_assignments'] = 0;
        if (!$crad instanceof PDO) {
            continue;
        }
        try {
            $ensureAvailability?->execute([$userId]);
        } catch (Throwable $e) {
            error_log('Panel availability ensure failed: ' . $e->getMessage());
        }
        $availabilityStmt->execute([$userId]);
        $availability = $availabilityStmt->fetch() ?: [];
        $loadStmt->execute([$userId]);
        $row['availability_status'] = (string) (($availability['availability_status'] ?? '') ?: 'Available');
        $row['availability_notes'] = (string) ($availability['notes'] ?? '');
        $row['current_assignments'] = (int) $loadStmt->fetchColumn();
    }
    unset($row);
    return $rows;
}

function rdPanelSelectedIds(): array
{
    if (isset($_GET['panel_ids']) && is_array($_GET['panel_ids'])) {
        return array_values(array_unique(array_filter(array_map('intval', $_GET['panel_ids']))));
    }
    $raw = (string) ($_GET['panel_ids'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $posted = $_POST['panel_ids'] ?? [];
        if (is_array($posted)) {
            return array_values(array_unique(array_filter(array_map('intval', $posted))));
        }
    }
    return array_values(array_unique(array_filter(array_map('intval', explode(',', $raw)))));
}

function rdPanelResolveContext(array $panels, bool $useRequest = true): array
{
    $context = rdPanelContext();
    $sourceGroupId = 0;
    $panelIds = [];
    $hasExplicitGroup = false;
    $hasExplicitPanels = false;

    if ($useRequest && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['research_group_id'])) {
        $sourceGroupId = (int) $_POST['research_group_id'];
        $hasExplicitGroup = true;
    } elseif ($useRequest && isset($_GET['group_id'])) {
        $sourceGroupId = (int) $_GET['group_id'];
        $hasExplicitGroup = true;
    } else {
        $sourceGroupId = (int) $context['group_id'];
    }

    if ($useRequest && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['panel_ids'])) {
        $panelIds = rdPanelSelectedIds();
        $hasExplicitPanels = true;
    } elseif ($useRequest && isset($_GET['panel_ids'])) {
        $panelIds = rdPanelSelectedIds();
        $hasExplicitPanels = true;
    } else {
        $panelIds = (array) $context['selected_panel_ids'];
    }

    $group = $sourceGroupId > 0 ? rdPanelReadyGroup($sourceGroupId) : null;
    $invalidGroup = $sourceGroupId > 0 && !$group;
    if ($invalidGroup) {
        rdPanelClearContext();
        return ['group_id' => 0, 'group' => null, 'panel_ids' => [], 'invalid_group' => true];
    }

    if ($hasExplicitGroup && $sourceGroupId > 0 && $sourceGroupId !== (int) $context['group_id']) {
        $panelIds = $hasExplicitPanels ? $panelIds : [];
    }

    $validPanelIds = array_column($panels, 'id');
    $panelIds = array_values(array_intersect(
        array_values(array_unique(array_filter(array_map('intval', $panelIds)))),
        array_map('intval', $validPanelIds)
    ));

    if ($group) {
        rdPanelSaveContext((int) $group['research_group_id'], $panelIds);
    }

    return [
        'group_id' => $group ? (int) $group['research_group_id'] : 0,
        'group' => $group,
        'panel_ids' => $panelIds,
        'invalid_group' => false,
    ];
}

function rdPanelStats(array $readyRows, array $panels = [], array $selectedIds = []): array
{
    $pending = 0;
    $partial = 0;
    $assigned = 0;
    foreach ($readyRows as $row) {
        $state = rdPanelAssignmentState((int) ($row['panel_count'] ?? 0));
        if ($state === 'complete') {
            $assigned++;
        } elseif ($state === 'partial') {
            $partial++;
        } else {
            $pending++;
        }
    }

    $selectedPanels = array_values(array_filter($panels, static fn(array $panel): bool => in_array((int) $panel['id'], $selectedIds, true)));
    $available = count(array_filter($selectedPanels ?: $panels, static fn(array $panel): bool => strcasecmp((string) ($panel['availability_status'] ?? ''), 'Available') === 0));
    $availabilityPending = count(array_filter($selectedPanels ?: $panels, static fn(array $panel): bool => strcasecmp((string) ($panel['availability_status'] ?? 'Pending'), 'Pending') === 0));
    $unavailable = count(array_filter($selectedPanels ?: $panels, static fn(array $panel): bool => strcasecmp((string) ($panel['availability_status'] ?? ''), 'Unavailable') === 0));

    return [
        'total_ready' => count($readyRows),
        'pending_assignment' => $pending,
        'partially_assigned' => $partial,
        'fully_assigned' => $assigned,
        'selected' => count($selectedPanels),
        'available' => $available,
        'availability_pending' => $availabilityPending,
        'unavailable' => $unavailable,
    ];
}

function rdPanelBadgeClass(string $status): string
{
    $status = strtolower(trim($status));
    if ($status === 'available' || $status === 'defense ready' || $status === 'assigned' || $status === 'ready') {
        return 'success';
    }
    if ($status === 'unavailable' || $status === 'cannot assign') {
        return 'danger';
    }
    return 'warning';
}

function rdPanelIsAvailable(array $panel): bool
{
    return strcasecmp((string) ($panel['availability_status'] ?? 'Pending'), 'Available') === 0;
}

function rdPanelSelectionState(array $selectedPanels): array
{
    if (!$selectedPanels) {
        return [
            'can_continue' => false,
            'message' => 'Select at least one available Panel Member to continue.',
        ];
    }

    foreach ($selectedPanels as $panel) {
        if (strcasecmp((string) ($panel['availability_status'] ?? 'Pending'), 'Unavailable') === 0) {
            return [
                'can_continue' => false,
                'message' => 'One or more selected Panel Members are unavailable.',
            ];
        }
    }

    foreach ($selectedPanels as $panel) {
        if (!rdPanelIsAvailable($panel)) {
            return [
                'can_continue' => false,
                'message' => 'Please wait until all selected Panel Members are available.',
            ];
        }
    }

    return [
        'can_continue' => true,
        'message' => '',
    ];
}

function rdPanelSelectedUrl(string $view, int $groupId, array $selectedIds): string
{
    return rdPanelPageUrl($view, [
        'group_id' => $groupId,
        'panel_ids' => implode(',', $selectedIds),
    ]);
}

function rdPanelAssignedRows(int $groupId): array
{
    $crad = rdPanelDb();
    if (!$crad instanceof PDO || $groupId <= 0) {
        return [];
    }

    try {
        $stmt = $crad->prepare(
            "SELECT rpa.panel_user_id AS id,
                    COALESCE(NULLIF(u.full_name, ''), NULLIF(rpa.panel_name, ''), 'Panel Member') AS full_name,
                    COALESCE(NULLIF(u.email, ''), NULLIF(rpa.panel_email, ''), '') AS email,
                    rpa.expertise,
                    COALESCE(NULLIF(pma.availability_status, ''), NULLIF(rpa.availability_status, ''), 'Assigned') AS availability_status,
                    rpa.assignment_status,
                    rpa.assigned_at
             FROM `crad_research_panel_assignments` rpa
             LEFT JOIN sms2_users u ON u.id = rpa.panel_user_id
             LEFT JOIN `crad_panel_member_availability` pma ON pma.panel_user_id = rpa.panel_user_id
             WHERE rpa.research_group_id = ?
               AND " . rdPanelActiveAssignmentSql('rpa') . "
             ORDER BY rpa.assigned_at ASC, rpa.id ASC"
        );
        $stmt->execute([$groupId]);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('RD assigned panel rows failed: ' . $e->getMessage());
        return [];
    }
}

function rdPanelAssign(array $data): array
{
    if (!smsCanManageCoordinatorAssignments()) {
        return ['ok' => false, 'message' => 'Forbidden.'];
    }

    $crad = rdPanelDb();
    if (!$crad instanceof PDO) {
        return ['ok' => false, 'message' => 'CRAD database unavailable.'];
    }

    $groupId = (int) ($data['research_group_id'] ?? 0);
    $selectedIds = array_values(array_unique(array_filter(array_map('intval', (array) ($data['panel_ids'] ?? [])))));
    $group = rdPanelReadyGroup($groupId);
    if (!$group) {
        $clearanceMessage = 'Cannot assign panel until Research Services Clearance signatures are complete (Adviser and CRAD).';
        try {
            if ($groupId > 0 && rscClearanceDoneExists($crad, $groupId)) {
                $clearanceMessage = 'Research group is not defense-ready.';
            }
        } catch (Throwable $e) {
            // keep clearance lock message
        }
        return ['ok' => false, 'message' => $clearanceMessage];
    }
    if (!$selectedIds) {
        return ['ok' => false, 'message' => 'Select at least one panel member.'];
    }

    $panelRows = rdPanelFacultyRows();
    $panelById = [];
    foreach ($panelRows as $panel) {
        $panelById[(int) $panel['id']] = $panel;
    }
    foreach ($selectedIds as $panelId) {
        if (empty($panelById[$panelId])) {
            return ['ok' => false, 'message' => 'Invalid panel member selection.'];
        }
        if (!rdPanelIsAvailable($panelById[$panelId])) {
            return ['ok' => false, 'message' => 'All selected Panel Members must be available before assignment.'];
        }
    }

    try {
        $crad->beginTransaction();
        $deactivate = $crad->prepare(
            "UPDATE `crad_research_panel_assignments`
             SET assignment_status = 'Removed', updated_at = NOW()
             WHERE research_group_id = ?
               AND defense_phase = 'Pre-Oral Defense'
               AND assignment_status = 'Assigned'
               AND panel_user_id NOT IN (" . implode(',', array_fill(0, count($selectedIds), '?')) . ")"
        );
        $deactivate->execute(array_merge([$groupId], $selectedIds));

        $findAssignment = $crad->prepare(
            "SELECT id
             FROM `crad_research_panel_assignments`
             WHERE research_group_id = ?
               AND panel_user_id = ?
               AND defense_phase = 'Pre-Oral Defense'
             ORDER BY (assignment_status = 'Assigned') DESC, updated_at DESC, id DESC
             LIMIT 1"
        );
        $removeDuplicates = $crad->prepare(
            "UPDATE `crad_research_panel_assignments`
             SET assignment_status = 'Removed', updated_at = NOW()
             WHERE research_group_id = ?
               AND panel_user_id = ?
               AND defense_phase = 'Pre-Oral Defense'
               AND id <> ?"
        );
        $updateAssignment = $crad->prepare(
            "UPDATE `crad_research_panel_assignments`
             SET proposal_id = :proposal_id,
                 title_approval_id = :title_approval_id,
                 proposal_number = :proposal_number,
                 group_number = :group_number,
                 research_title = :research_title,
                 panel_name = :panel_name,
                 panel_email = :panel_email,
                 expertise = :expertise,
                 availability_status = :availability_status,
                 assignment_status = 'Assigned',
                 assigned_by = :assigned_by,
                 assigned_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id"
        );
        $insert = $crad->prepare(
            "INSERT INTO `crad_research_panel_assignments`
                (research_group_id, defense_schedule_id, proposal_id, title_approval_id, proposal_number,
                 group_number, research_title, panel_user_id, panel_name, panel_email, expertise,
                 availability_status, assignment_status, defense_phase, assigned_by, assigned_at, created_at, updated_at)
             VALUES
                (:research_group_id, NULL, :proposal_id, :title_approval_id, :proposal_number,
                 :group_number, :research_title, :panel_user_id, :panel_name, :panel_email, :expertise,
                 :availability_status, 'Assigned', 'Pre-Oral Defense', :assigned_by, NOW(), NOW(), NOW())"
        );
        $notify = $crad->prepare(
            "INSERT IGNORE INTO `crad_panel_assignment_notifications`
                (event_key, recipient_user_id, recipient_role, recipient_email, panel_assignment_id,
                 research_group_id, title, body, url, is_read, created_at)
             VALUES
                (:event_key, :recipient_user_id, :recipient_role, :recipient_email, :panel_assignment_id,
                 :research_group_id, :title, :body, :url, 0, NOW())"
        );

        $inserted = 0;
        foreach ($selectedIds as $panelId) {
            $panel = $panelById[$panelId];
            $assignmentData = [
                ':proposal_id' => (int) ($group['proposal_id'] ?? 0) ?: null,
                ':title_approval_id' => (int) ($group['title_approval_id'] ?? 0) ?: null,
                ':proposal_number' => (string) ($group['proposal_number'] ?? ''),
                ':group_number' => (string) ($group['group_number'] ?? ''),
                ':research_title' => (string) ($group['research_title'] ?? ''),
                ':panel_name' => (string) $panel['full_name'],
                ':panel_email' => (string) $panel['email'],
                ':expertise' => (string) ($panel['expertise'] ?? ''),
                ':availability_status' => (string) ($panel['availability_status'] ?? 'Pending'),
                ':assigned_by' => (int) getCurrentUserId(),
            ];

            $findAssignment->execute([$groupId, $panelId]);
            $assignmentId = (int) ($findAssignment->fetchColumn() ?: 0);
            if ($assignmentId > 0) {
                $updateAssignment->execute($assignmentData + [':id' => $assignmentId]);
                $removeDuplicates->execute([$groupId, $panelId, $assignmentId]);
            } else {
                $insert->execute($assignmentData + [
                    ':research_group_id' => $groupId,
                    ':panel_user_id' => $panelId,
                ]);
                $assignmentId = (int) $crad->lastInsertId();
                $inserted++;
            }

            if ($assignmentId > 0) {
                $url = BASE_URL . '/modules/faculty/pages/assigned-defenses.php?group=' . rawurlencode((string) ($group['group_number'] ?? ''));
                $notify->execute([
                    ':event_key' => 'preoral-panel-assignment:' . $groupId . ':u' . $panelId,
                    ':recipient_user_id' => $panelId,
                    ':recipient_role' => (string) (($panel['role_key'] ?? '') ?: 'panel'),
                    ':recipient_email' => (string) $panel['email'],
                    ':panel_assignment_id' => $assignmentId,
                    ':research_group_id' => $groupId,
                    ':title' => 'Pre-Oral Panel Assignment',
                    ':body' => 'You have been assigned as a Panel Member for ' . (string) ($group['group_name'] ?? $group['group_number'] ?? 'Research Group') . "\n" . (string) ($group['research_title'] ?? '') . "\nDefense Phase: Pre-Oral Defense",
                    ':url' => $url,
                ]);
            }
        }
        $crad->commit();
        rdPanelClearContext();
        return ['ok' => true, 'message' => $inserted > 0 ? 'Panel members assigned successfully.' : 'Selected panel members were already assigned.'];
    } catch (PDOException $e) {
        if ($crad->inTransaction()) {
            $crad->rollBack();
        }
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'message' => 'One or more selected panel members are already assigned to this Pre-Oral Defense research.'];
        }
        error_log('RD panel assignment failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => 'Unable to assign panel members.'];
    }
}

function rdPanelRenderMemberCard(array $panel, array $selectedIds = []): void
{
    $panelId = (int) ($panel['id'] ?? 0);
    $isSelected = in_array($panelId, $selectedIds, true);
    $availabilityStatus = (string) ($panel['availability_status'] ?? 'Pending');
    $roleLabel = (string) ($panel['role_label'] ?? rdPanelRoleLabel((string) ($panel['role_key'] ?? 'panel')));
    ?>
    <label class="rdpa-panel-card" data-rd-panel-card data-panel-id="<?= $panelId ?>">
        <input class="form-check-input" type="checkbox" name="panel_ids[]" value="<?= $panelId ?>" <?= $isSelected ? 'checked' : '' ?>>
        <strong data-rd-panel-name><?= e((string) ($panel['full_name'] ?? '')) ?></strong>
        <span class="badge text-bg-<?= ($panel['role_key'] ?? '') === 'department_chair' ? 'primary' : 'secondary' ?> ms-1" data-rd-panel-role><?= e($roleLabel) ?></span>
        <span class="email" data-rd-panel-email><?= e((string) ($panel['email'] ?? '')) ?></span>
        <div class="rdpa-detail"><small>Expertise</small><span data-rd-panel-expertise><?= e((string) (($panel['expertise'] ?? '') !== '' ? $panel['expertise'] : 'Not recorded')) ?></span></div>
        <div class="rdpa-detail"><small>Availability</small><span class="badge text-bg-<?= e(rdPanelBadgeClass($availabilityStatus)) ?>" data-rd-panel-availability><?= e($availabilityStatus) ?></span></div>
        <div class="rdpa-detail"><small>Current Assignments</small><span data-rd-panel-assignments><?= (int) ($panel['current_assignments'] ?? 0) ?></span></div>
        <span class="badge text-bg-success rdpa-selected-label">Selected</span>
    </label>
    <?php
}

function rdPanelMemberCardHtml(array $panel, array $selectedIds = []): string
{
    ob_start();
    rdPanelRenderMemberCard($panel, $selectedIds);
    return trim((string) ob_get_clean());
}

function rdPanelRenderRows(array $rows): void
{
    if (!$rows): ?>
        <div class="rdpa-empty">
            <?= smsIcon('flask') ?>
            <strong>No Defense-Ready Research</strong>
            <span>Research groups appear here only after Chapters 1–3 are accepted and Research Services Clearance signatures are complete.</span>
        </div>
    <?php else: ?>
        <div class="table-responsive"><table class="table align-middle mb-0 rdpa-table"><thead><tr><th>Reference No.</th><th>Group</th><th>Research Title</th><th>Academic Year</th><th>Adviser</th><th>Pre-Oral Status</th><th>Panel Assignment</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td class="rdpa-nowrap"><?= e((string) (($row['proposal_number'] ?? '') ?: ($row['group_number'] ?? ''))) ?></td>
                <td class="rdpa-group-cell"><strong><?= e((string) ($row['group_number'] ?? '')) ?></strong><small><?= e((string) ($row['group_name'] ?? '')) ?></small></td>
                <td class="rdpa-title"><?= e((string) ($row['research_title'] ?? '')) ?></td>
                <td><?= e((string) ($row['academic_year'] ?? '')) ?></td>
                <td><?= e((string) ($row['adviser_name'] ?? '')) ?></td>
                <td><span class="badge text-bg-success">Defense Ready</span></td>
                <?php
                    $panelState = rdPanelAssignmentState((int) ($row['panel_count'] ?? 0));
                    $panelLabel = $panelState === 'complete' ? 'Assigned' : ($panelState === 'partial' ? 'Partially Assigned' : 'Pending Panel Assignment');
                ?>
                <td><span class="badge text-bg-<?= $panelState === 'complete' ? 'success' : 'warning' ?>"><?= e($panelLabel) ?></span></td>
                <td><a class="btn btn-sm btn-sms-primary rdpa-row-action" href="<?= e(rdPanelPageUrl('select-panel-members', ['group_id' => (int) $row['research_group_id']])) ?>">Select a Research</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif;
}

function rdPanelRenderResearchPicker(array $rows, string $emptyTitle = 'No Research Selected'): void
{
    if (!$rows): ?>
        <div class="rdpa-empty">
            <?= smsIcon('flask') ?>
            <strong>No Defense-Ready Research</strong>
            <span>Research groups appear here only after Chapters 1–3 are accepted and Research Services Clearance signatures are complete.</span>
        </div>
    <?php return; endif; ?>
    <div class="rdpa-empty rdpa-empty--picker">
        <?= smsIcon('search') ?>
        <div>
            <strong><?= e($emptyTitle) ?></strong>
            <span>Please choose a Defense-Ready Research Group to continue.</span>
        </div>
    </div>
    <div class="rdpa-picker-grid">
        <?php foreach ($rows as $row): ?>
            <article class="rdpa-picker-card">
                <div>
                    <strong><?= e((string) ($row['group_number'] ?? '')) ?></strong>
                    <small><?= e((string) ($row['group_name'] ?? '')) ?></small>
                </div>
                <p><?= e((string) ($row['research_title'] ?? 'Research title pending')) ?></p>
                <div class="rdpa-picker-meta">
                    <span><?= e((string) ($row['academic_year'] ?? '')) ?></span>
                    <span><?= e((string) ($row['adviser_name'] ?? '')) ?></span>
                    <span class="badge text-bg-success">Defense Ready</span>
                </div>
                <a class="btn btn-sm btn-sms-primary rdpa-picker-action" href="<?= e(rdPanelPageUrl('select-panel-members', ['group_id' => (int) $row['research_group_id']])) ?>">Select</a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php
}

function rdPanelRenderSelectedResearchCard(array $group): void
{
    ?>
    <div class="rdpa-inner-card">
        <h4><?= smsIcon('flask', ['class' => 'me-2 text-primary']) ?>Selected Research</h4>
        <div class="rdpa-detail"><small>Group Number</small><strong><?= e((string) $group['group_number']) ?></strong></div>
        <div class="rdpa-detail"><small>Group Name</small><span><?= e((string) $group['group_name']) ?></span></div>
        <div class="rdpa-detail"><small>Research Title</small><strong><?= e((string) $group['research_title']) ?></strong></div>
        <div class="rdpa-detail"><small>Academic Year</small><span><?= e((string) $group['academic_year']) ?></span></div>
        <div class="rdpa-detail"><small>Research Adviser</small><span><?= e((string) $group['adviser_name']) ?></span></div>
        <span class="badge text-bg-success">Defense Ready</span>
    </div>
    <?php
}

function rdPanelStatusLabel(string $availability): string
{
    if (strcasecmp($availability, 'Available') === 0) {
        return 'Ready';
    }
    if (strcasecmp($availability, 'Unavailable') === 0) {
        return 'Cannot Assign';
    }
    return 'Pending';
}

function rdPanelRenderCheckAvailabilityContent(array $group, int $groupId, array $selectedIds, array $selectedPanels, array $stats, array $selectionState): void
{
    $assignUrl = rdPanelSelectedUrl('assign-panel-members', $groupId, $selectedIds);
    ?>
    <div class="rdpa-stats">
        <div class="rdpa-stat"><?= smsIcon('users') ?><div><strong><?= (int) $stats['selected'] ?></strong><span>Selected Panel Members</span></div></div>
        <div class="rdpa-stat"><?= smsIcon('check-circle') ?><div><strong><?= (int) $stats['available'] ?></strong><span>Available</span></div></div>
        <div class="rdpa-stat"><?= smsIcon('clock') ?><div><strong><?= (int) $stats['availability_pending'] ?></strong><span>Pending</span></div></div>
        <div class="rdpa-stat"><?= smsIcon('ban') ?><div><strong><?= (int) $stats['unavailable'] ?></strong><span>Unavailable</span></div></div>
    </div>
    <section class="rdpa-card">
        <div class="rdpa-card-head"><h3><?= smsIcon('flask', ['class' => 'me-2 text-primary']) ?>Selected Research</h3><span class="badge text-bg-success">Defense Ready</span></div>
        <div class="p-3"><strong><?= e((string) $group['group_number']) ?></strong><small class="d-block text-muted"><?= e((string) $group['research_title']) ?></small></div>
    </section>
    <section class="rdpa-card">
        <div class="rdpa-card-head"><h3><?= smsIcon('calendar-check', ['class' => 'me-2 text-primary']) ?>Panel Availability</h3><span class="rdpa-sync">Live availability</span></div>
        <div class="table-responsive"><table class="table align-middle mb-0 rdpa-table"><thead><tr><th>Panel Member</th><th>Expertise</th><th>Current Assignments</th><th>Availability</th><th>Status</th><th>Action</th></tr></thead><tbody>
            <?php foreach ($selectedPanels as $panel): ?>
                <?php
                    $availability = (string) ($panel['availability_status'] ?? 'Pending');
                    $rowReady = strcasecmp($availability, 'Available') === 0;
                    $statusLabel = rdPanelStatusLabel($availability);
                    $statusClass = $rowReady ? 'success' : (strcasecmp($availability, 'Unavailable') === 0 ? 'danger' : 'warning');
                ?>
                <tr>
                    <td><strong><?= e((string) $panel['full_name']) ?></strong><small><?= e((string) $panel['email']) ?></small></td>
                    <td><?= e((string) (($panel['expertise'] ?? '') ?: 'Not recorded')) ?></td>
                    <td><?= (int) $panel['current_assignments'] ?></td>
                    <td><span class="badge text-bg-<?= e(rdPanelBadgeClass($availability)) ?>"><?= e($availability) ?></span></td>
                    <td><span class="badge text-bg-<?= e($statusClass) ?>"><?= e($statusLabel) ?></span></td>
                    <td>
                        <?php if ($rowReady): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e($assignUrl) ?>">Continue</a>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Continue</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody></table></div>
        <div class="rdpa-actions">
            <?php if (!empty($selectionState['message'])): ?><small class="text-muted align-self-center me-auto"><?= e((string) $selectionState['message']) ?></small><?php endif; ?>
            <?php if (!empty($selectionState['can_continue'])): ?>
                <a class="btn btn-sms-primary" href="<?= e($assignUrl) ?>">Proceed to Assign</a>
            <?php else: ?>
                <button type="button" class="btn btn-sms-primary" disabled>Proceed to Assign</button>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

function renderResearchDirectorPanelAssignment(string $view): void
{
    renderResearchCoordinatorPanelAssignment($view);
}

function renderResearchCoordinatorPanelAssignment(string $view): void
{
    $message = $_SESSION['rd_panel_assignment_message'] ?? null;
    unset($_SESSION['rd_panel_assignment_message']);
    if ($view === 'assign-panel-members' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $message = csrfVerify() ? rdPanelAssign($_POST) : ['ok' => false, 'message' => 'Security token expired.'];
    }
    $assignmentCompleted = is_array($message) && !empty($message['ok']);

    if (($_GET['ajax'] ?? '') === 'panel-context' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        if (!csrfVerify()) {
            echo json_encode(['ok' => false, 'message' => 'Security token expired.']);
            exit;
        }
        $contextGroupId = (int) ($_POST['research_group_id'] ?? 0);
        $contextGroup = $contextGroupId > 0 ? rdPanelReadyGroup($contextGroupId) : null;
        if (!$contextGroup) {
            rdPanelClearContext();
            echo json_encode(['ok' => false, 'message' => 'Invalid defense-ready research group.']);
            exit;
        }
        $panelRowsForContext = rdPanelFacultyRows();
        $validPanelIds = array_map('intval', array_column($panelRowsForContext, 'id'));
        $contextPanelIds = array_values(array_intersect(rdPanelSelectedIds(), $validPanelIds));
        rdPanelSaveContext($contextGroupId, $contextPanelIds);
        echo json_encode(['ok' => true, 'group_id' => $contextGroupId, 'panel_ids' => $contextPanelIds]);
        exit;
    }

    if (($_GET['ajax'] ?? '') === 'panel-assignment') {
        header('Content-Type: application/json; charset=utf-8');
        ob_start();
        rdPanelRenderRows(rdPanelReadyRows());
        echo json_encode(['ok' => true, 'html' => trim((string) ob_get_clean()), 'synced_at' => date('M j, Y h:i:s A')]);
        exit;
    }

    $panels = rdPanelFacultyRows();

    if (($_GET['ajax'] ?? '') === 'panel-selection-state') {
        header('Content-Type: application/json; charset=utf-8');
        $panelPayload = [];
        $selectedForHtml = rdPanelSelectedIds();
        foreach ($panels as $panel) {
            $status = (string) ($panel['availability_status'] ?? 'Pending');
            $panelId = (int) $panel['id'];
            $panelPayload[] = [
                'id' => $panelId,
                'full_name' => (string) ($panel['full_name'] ?? ''),
                'email' => (string) ($panel['email'] ?? ''),
                'role_key' => (string) ($panel['role_key'] ?? 'panel'),
                'role_label' => (string) ($panel['role_label'] ?? rdPanelRoleLabel((string) ($panel['role_key'] ?? 'panel'))),
                'expertise' => (string) (($panel['expertise'] ?? '') !== '' ? $panel['expertise'] : 'Not recorded'),
                'availability_status' => $status,
                'badge_class' => rdPanelBadgeClass($status),
                'current_assignments' => (int) ($panel['current_assignments'] ?? 0),
                'html' => rdPanelMemberCardHtml($panel, $selectedForHtml),
            ];
        }
        echo json_encode(['ok' => true, 'panels' => $panelPayload, 'synced_at' => date('M j, Y h:i:s A')]);
        exit;
    }

    $readyRows = rdPanelReadyRows();
    $resolved = rdPanelResolveContext($panels, !$assignmentCompleted);
    $groupId = (int) $resolved['group_id'];
    $group = $resolved['group'];
    $selectedIds = (array) $resolved['panel_ids'];
    $invalidGroup = !empty($resolved['invalid_group']);
    $stats = rdPanelStats($readyRows, $panels, $selectedIds);
    $selectedPanels = array_values(array_filter($panels, static fn(array $panel): bool => in_array((int) $panel['id'], $selectedIds, true)));
    $assignedPanels = $group ? rdPanelAssignedRows($groupId) : [];
    $selectionState = rdPanelSelectionState($selectedPanels);
    if (($_GET['ajax'] ?? '') === 'panel-availability') {
        header('Content-Type: application/json; charset=utf-8');
        if (!$group || !$selectedPanels) {
            echo json_encode(['ok' => false, 'message' => 'No selected Panel Members to check.']);
            exit;
        }
        ob_start();
        rdPanelRenderCheckAvailabilityContent($group, $groupId, $selectedIds, $selectedPanels, $stats, $selectionState);
        echo json_encode([
            'ok' => true,
            'html' => trim((string) ob_get_clean()),
            'can_continue' => !empty($selectionState['can_continue']),
            'synced_at' => date('M j, Y h:i:s A'),
        ]);
        exit;
    }
    if ($view === 'check-panel-availability' && $group && empty($selectionState['can_continue'])) {
        $view = 'select-panel-members';
        $message = [
            'ok' => false,
            'level' => 'warning',
            'message' => 'Cannot continue. All selected Panel Members must be available before checking panel availability.',
        ];
    }
    $pageCopy = [
        'retrieve-defense-ready-research' => ['Retrieve Defense-Ready Research', 'Retrieve research groups that are qualified for Pre-Oral Defense. Panel assignment stays locked until Research Services Clearance signatures are complete.', 'fa-download'],
        'select-panel-members' => ['Select Panel Members', 'Select qualified faculty members for the chosen Pre-Oral Defense research.', 'fa-user-friends'],
        'check-panel-availability' => ['Check Panel Availability', 'Review the current availability of selected Panel Members.', 'fa-calendar-check'],
        'assign-panel-members' => ['Assign Panel Members', 'Assign selected and available Panel Members to the Pre-Oral Defense research.', 'fa-user-plus'],
        'sent-to-notification' => ['Sent to Notification', 'Panel assignment notifications are sent after confirmed assignment.', 'fa-paper-plane'],
    ];
    [$title, $subtitle, $icon] = $pageCopy[$view] ?? $pageCopy['retrieve-defense-ready-research'];
    ?>
    <style>
        .rdpa-wrap { display: flex; flex-direction: column; gap: 1rem; }
        .rdpa-header,
        .rdpa-card {
            background: var(--sms-surface);
            border: 1px solid var(--sms-border);
            border-radius: 14px;
            box-shadow: var(--sms-shadow-sm);
            overflow: hidden;
        }
        .rdpa-header { align-items: center; display: flex; justify-content: space-between; gap: 1rem; padding: 1rem 1.15rem; }
        .rdpa-header h2 { color: var(--sms-heading); font-size: 1.05rem; font-weight: 850; margin: 0; }
        .rdpa-header p { color: var(--sms-text-muted); font-size: .86rem; margin: .2rem 0 0; }
        .rdpa-sync { color: var(--sms-text-muted); flex: 0 0 auto; font-size: .78rem; font-weight: 800; }
        .rdpa-card-head { align-items: center; border-bottom: 1px solid var(--sms-border); display: flex; justify-content: space-between; gap: 1rem; padding: .95rem 1rem; }
        .rdpa-card-head h3 { color: var(--sms-heading); font-size: .96rem; font-weight: 850; margin: 0; }
        .rdpa-stats { display: grid; gap: .85rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .rdpa-stat { align-items: center; background: var(--sms-surface); border: 1px solid var(--sms-border); border-radius: 14px; box-shadow: var(--sms-shadow-xs); display: flex; gap: .85rem; min-height: 76px; padding: .95rem 1rem; }
        .rdpa-stat i { align-items: center; background: var(--sms-primary-xlight); border-radius: 12px; color: var(--sms-primary); display: inline-flex; flex: 0 0 42px; height: 42px; justify-content: center; width: 42px; }
        .rdpa-stat span { color: var(--sms-text-muted); display: block; font-size: .7rem; font-weight: 850; letter-spacing: .04em; text-transform: uppercase; }
        .rdpa-stat strong { color: var(--sms-heading); display: block; font-size: 1.35rem; font-weight: 850; line-height: 1.1; margin-top: .15rem; }
        .rdpa-grid { display: grid; gap: 1rem; grid-template-columns: minmax(280px, .8fr) minmax(0, 1.25fr); padding: 1rem; }
        .rdpa-inner-card { background: var(--sms-surface-muted); border: 1px solid var(--sms-border); border-radius: 12px; padding: 1rem; }
        .rdpa-inner-card h4 { color: var(--sms-heading); font-size: .92rem; font-weight: 850; margin: 0 0 .8rem; }
        .rdpa-detail { margin-bottom: .7rem; }
        .rdpa-detail small { color: var(--sms-text-muted); display: block; font-size: .72rem; font-weight: 850; text-transform: uppercase; }
        .rdpa-detail strong,
        .rdpa-detail span { color: var(--sms-heading); display: block; overflow-wrap: anywhere; }
        .rdpa-panel-grid { display: grid; gap: .8rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .rdpa-panel-card { background: var(--sms-surface); border: 1px solid var(--sms-border); border-radius: 12px; cursor: pointer; display: block; min-height: 164px; padding: .9rem; transition: border-color .15s, box-shadow .15s; }
        .rdpa-panel-card:hover { border-color: var(--sms-primary-light); box-shadow: var(--sms-shadow-xs); }
        .rdpa-panel-card input { margin-right: .45rem; }
        .rdpa-panel-card:has(input:checked) { border-color: var(--sms-success); box-shadow: 0 0 0 3px rgba(22,163,74,.12); }
        .rdpa-panel-card strong { color: var(--sms-heading); display: inline; font-weight: 850; }
        .rdpa-panel-card .email { color: var(--sms-text-muted); display: block; font-size: .78rem; margin: .2rem 0 .75rem; overflow-wrap: anywhere; }
        .rdpa-selected-label { display: none; margin-top: .65rem; }
        .rdpa-panel-card:has(input:checked) .rdpa-selected-label { display: inline-flex; }
        .rdpa-table { min-width: 940px; table-layout: auto; }
        .rdpa-table th { color: var(--sms-heading); font-size: .86rem; font-weight: 850; padding: .8rem .9rem; white-space: nowrap; }
        .rdpa-table td { color: var(--sms-text); padding: .85rem .9rem; vertical-align: middle; }
        .rdpa-table td small { color: var(--sms-text-muted); display: block; font-size: .78rem; margin-top: .2rem; }
        .rdpa-table .badge { white-space: nowrap; }
        .rdpa-group-cell { min-width: 160px; }
        .rdpa-group-cell strong { display: block; white-space: nowrap; }
        .rdpa-title { min-width: 360px; max-width: 620px; overflow-wrap: anywhere; }
        .rdpa-row-action { min-width: 138px; white-space: nowrap; }
        .rdpa-nowrap { white-space: nowrap; }
        .rdpa-empty { align-items: center; color: var(--sms-text-muted); display: flex; flex-direction: column; gap: .45rem; justify-content: center; min-height: 230px; padding: 2rem; text-align: center; }
        .rdpa-empty--picker { align-items: center; background: var(--sms-surface-muted); border: 1px solid var(--sms-border); border-radius: 12px; flex-direction: row; justify-content: flex-start; margin: 1rem 1rem .85rem; min-height: auto; padding: 1rem; text-align: left; }
        .rdpa-empty i { align-items: center; background: var(--sms-primary-xlight); border-radius: 16px; color: var(--sms-primary); display: inline-flex; height: 54px; justify-content: center; width: 54px; }
        .rdpa-empty strong { color: var(--sms-heading); font-size: 1rem; }
        .rdpa-empty--picker div { display: grid; gap: .15rem; }
        .rdpa-picker-grid { display: grid; gap: .85rem; grid-template-columns: repeat(auto-fit, minmax(320px, 520px)); justify-content: start; padding: 0 1rem 1rem; }
        .rdpa-picker-card { background: var(--sms-surface-muted); border: 1px solid var(--sms-border); border-radius: 12px; display: grid; gap: .65rem; padding: 1rem; }
        .rdpa-picker-card strong { color: var(--sms-heading); display: block; font-weight: 850; }
        .rdpa-picker-card small,
        .rdpa-picker-meta { color: var(--sms-text-muted); font-size: .78rem; }
        .rdpa-picker-card p { color: var(--sms-heading); font-weight: 800; margin: 0; overflow-wrap: anywhere; }
        .rdpa-picker-meta { align-items: center; display: flex; flex-wrap: wrap; gap: .45rem; }
        .rdpa-picker-action { justify-self: start; min-width: 128px; }
        .rdpa-actions { display: flex; flex-wrap: wrap; gap: .6rem; justify-content: flex-end; padding: 0 1rem 1rem; }
        .rdpa-assigned-list { display: grid; gap: .75rem; }
        .rdpa-assigned-item { align-items: center; background: var(--sms-surface); border: 1px solid var(--sms-border); border-radius: 12px; display: flex; gap: 1rem; justify-content: space-between; padding: .85rem .95rem; }
        .rdpa-assigned-item strong { color: var(--sms-heading); display: block; font-weight: 850; overflow-wrap: anywhere; }
        .rdpa-assigned-item span:not(.badge) { color: var(--sms-text-muted); display: block; font-size: .78rem; margin-top: .15rem; overflow-wrap: anywhere; }
        .rdpa-confirm-modal .modal-dialog { max-width: 560px; }
        .rdpa-confirm-modal .modal-content { border: 0; border-radius: 14px; box-shadow: 0 24px 70px rgba(15, 23, 42, .24); overflow: hidden; }
        .rdpa-confirm-modal .modal-header { align-items: center; border-bottom: 1px solid var(--sms-border); padding: 1rem 1.15rem; }
        .rdpa-confirm-modal .modal-title { color: var(--sms-heading); font-size: 1rem; font-weight: 850; }
        .rdpa-confirm-modal .modal-body { color: var(--sms-text); font-size: .92rem; line-height: 1.55; padding: 1.15rem; }
        .rdpa-confirm-modal .modal-footer { background: var(--sms-surface-muted); border-top: 1px solid var(--sms-border); gap: .6rem; padding: .95rem 1.15rem; }
        .rdpa-confirm-modal .btn { min-height: 42px; padding-left: 1rem; padding-right: 1rem; }
        @media (max-width: 900px) {
            .rdpa-header,
            .rdpa-card-head { align-items: flex-start; flex-direction: column; }
            .rdpa-stats,
            .rdpa-grid,
            .rdpa-panel-grid,
            .rdpa-picker-grid { grid-template-columns: 1fr; }
            .rdpa-empty--picker { align-items: flex-start; flex-direction: column; }
            .rdpa-sync { width: 100%; }
        }
    </style>

    <div class="rdpa-wrap" data-rd-panel-root data-endpoint="<?= e(rdPanelPageUrl('retrieve-defense-ready-research', ['ajax' => 'panel-assignment'])) ?>" data-context-endpoint="<?= e(rdPanelPageUrl('select-panel-members', ['ajax' => 'panel-context'])) ?>" data-selection-endpoint="<?= e(rdPanelPageUrl('select-panel-members', ['ajax' => 'panel-selection-state'])) ?>" data-check-endpoint="<?= e($group ? rdPanelSelectedUrl('check-panel-availability', $groupId, $selectedIds) . '&ajax=panel-availability' : '') ?>">
        <header class="rdpa-header">
            <div>
                <h2><?= smsIcon(e($icon), ['class' => 'me-2 text-primary']) ?><?= e($title) ?></h2>
                <p><?= e($subtitle) ?></p>
            </div>
            <span class="rdpa-sync" data-rd-panel-sync>Synced <?= e(date('M j, Y h:i:s A')) ?></span>
        </header>

        <?php if ($message): ?><div class="alert alert-<?= e((string) (!empty($message['ok']) ? 'success' : ($message['level'] ?? 'danger'))) ?> mb-0"><?= e((string) $message['message']) ?></div><?php endif; ?>

        <?php if ($view === 'retrieve-defense-ready-research'): ?>
            <div class="rdpa-stats">
                <div class="rdpa-stat"><?= smsIcon('layer-group') ?><div><strong><?= (int) $stats['total_ready'] ?></strong><span>Total Defense-Ready</span></div></div>
                <div class="rdpa-stat"><?= smsIcon('clock') ?><div><strong><?= (int) $stats['pending_assignment'] ?></strong><span>Pending Panel Assignment</span></div></div>
                <div class="rdpa-stat"><?= smsIcon('adjust') ?><div><strong><?= (int) $stats['partially_assigned'] ?></strong><span>Partially Assigned</span></div></div>
                <div class="rdpa-stat"><?= smsIcon('check-circle') ?><div><strong><?= (int) $stats['fully_assigned'] ?></strong><span>Fully Assigned</span></div></div>
            </div>
            <section class="rdpa-card">
                <div class="rdpa-card-head">
                    <h3><?= smsIcon('list', ['class' => 'me-2 text-primary']) ?>Defense-Ready Research</h3>
                    <span class="rdpa-sync" data-rd-panel-sync-table>Live / Synced <?= e(date('M j, Y h:i:s A')) ?></span>
                </div>
                <div data-rd-panel-content><?php rdPanelRenderRows($readyRows); ?></div>
            </section>
        <?php elseif (!$group): ?>
            <section class="rdpa-card">
                <?php if ($invalidGroup): ?>
                    <div class="alert alert-warning m-3 mb-0">Research is no longer available for Panel Assignment.</div>
                <?php endif; ?>
                <?php rdPanelRenderResearchPicker($readyRows); ?>
                <div class="rdpa-actions"><a class="btn btn-outline-primary" href="<?= e(rdPanelPageUrl('retrieve-defense-ready-research')) ?>">View Defense-Ready Research</a></div>
            </section>
        <?php elseif ($view === 'select-panel-members'): ?>
            <section class="rdpa-card">
                <div class="rdpa-grid">
                    <?php rdPanelRenderSelectedResearchCard($group); ?>
                    <div class="rdpa-inner-card">
                        <h4><?= smsIcon('user-friends', ['class' => 'me-2 text-primary']) ?>Eligible Panel Members</h4>
                        <form method="get" action="<?= e(rdPanelPageUrl('check-panel-availability')) ?>" data-rd-panel-select-form>
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>" disabled>
                            <input type="hidden" name="group_id" value="<?= (int) $groupId ?>">
                            <div class="rdpa-panel-grid" data-rd-panel-grid>
                                <?php foreach ($panels as $panel): ?>
                                    <?php rdPanelRenderMemberCard($panel, $selectedIds); ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="rdpa-actions">
                                <small class="text-muted align-self-center me-auto" data-rd-panel-select-message><?= e((string) $selectionState['message']) ?></small>
                                <button class="btn btn-sms-primary mt-3" data-rd-panel-check-button <?= !empty($selectionState['can_continue']) ? '' : 'disabled' ?>>Check Panel Availability</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        <?php elseif ($view === 'check-panel-availability'): ?>
            <?php if (!$selectedPanels): ?>
                <section class="rdpa-card">
                    <div class="rdpa-grid">
                        <?php rdPanelRenderSelectedResearchCard($group); ?>
                        <div class="rdpa-inner-card"><div class="rdpa-empty"><?= smsIcon('user-friends') ?><strong>No Panel Members Selected</strong><span>Please select Panel Members before checking availability.</span><a class="btn btn-sms-primary mt-2" href="<?= e(rdPanelPageUrl('select-panel-members', ['group_id' => $groupId])) ?>">Select Panel Members</a></div></div>
                    </div>
                </section>
            <?php else: ?>
                <div data-rd-panel-check-content>
                    <?php rdPanelRenderCheckAvailabilityContent($group, $groupId, $selectedIds, $selectedPanels, $stats, $selectionState); ?>
                </div>
            <?php endif; ?>
        <?php elseif ($view === 'assign-panel-members'): ?>
            <?php if (!$selectedPanels): ?>
                <section class="rdpa-card">
                    <div class="rdpa-grid">
                        <?php rdPanelRenderSelectedResearchCard($group); ?>
                        <div class="rdpa-inner-card">
                            <?php if ($assignedPanels): ?>
                                <h4><?= smsIcon('user-check', ['class' => 'me-2 text-primary']) ?>Assigned Panel Members</h4>
                                <div class="rdpa-assigned-list">
                                    <?php foreach ($assignedPanels as $panel): ?>
                                        <?php $availability = (string) ($panel['availability_status'] ?? 'Assigned'); ?>
                                        <div class="rdpa-assigned-item">
                                            <div>
                                                <strong><?= e((string) ($panel['full_name'] ?: 'Panel Member')) ?></strong>
                                                <span><?= e((string) ($panel['email'] ?: 'No email recorded')) ?></span>
                                            </div>
                                            <span class="badge text-bg-<?= e(rdPanelBadgeClass($availability)) ?>"><?= e($availability) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="rdpa-actions px-0 pb-0 mt-3">
                                    <a class="btn btn-outline-primary" href="<?= e(rdPanelPageUrl('select-panel-members', ['group_id' => $groupId])) ?>">Update Selection</a>
                                </div>
                            <?php else: ?>
                                <div class="rdpa-empty"><?= smsIcon('user-friends') ?><strong>No Panel Members Ready to Assign</strong><span>Please select Panel Members and check their availability first.</span><div class="d-flex flex-wrap gap-2 justify-content-center mt-2"><a class="btn btn-sms-primary" href="<?= e(rdPanelPageUrl('select-panel-members', ['group_id' => $groupId])) ?>">Select Panel Members</a><a class="btn btn-outline-primary" href="<?= e(rdPanelPageUrl('check-panel-availability')) ?>">Check Panel Availability</a></div></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php else: ?>
                <div class="rdpa-stats">
                    <div class="rdpa-stat"><?= smsIcon('list') ?><div><strong><?= (int) $stats['selected'] ?></strong><span>Total</span></div></div>
                    <div class="rdpa-stat"><?= smsIcon('clock') ?><div><strong><?= (int) $stats['availability_pending'] ?></strong><span>Pending</span></div></div>
                    <div class="rdpa-stat"><?= smsIcon('check-circle') ?><div><strong><?= (int) $stats['available'] ?></strong><span>Available</span></div></div>
                    <div class="rdpa-stat"><?= smsIcon('user-check') ?><div><strong><?= (int) $stats['fully_assigned'] ?></strong><span>Assigned</span></div></div>
                </div>
                <section class="rdpa-card">
                    <div class="rdpa-card-head"><h3><?= smsIcon('user-plus', ['class' => 'me-2 text-primary']) ?>Assign Panel Members</h3></div>
                    <div class="rdpa-grid">
                        <div class="rdpa-inner-card"><h4>Selected Research</h4><div class="rdpa-detail"><small>Group Number</small><strong><?= e((string) $group['group_number']) ?></strong></div><div class="rdpa-detail"><small>Group Name</small><span><?= e((string) $group['group_name']) ?></span></div><div class="rdpa-detail"><small>Research Title</small><strong><?= e((string) $group['research_title']) ?></strong></div><div class="rdpa-detail"><small>Academic Year</small><span><?= e((string) $group['academic_year']) ?></span></div><div class="rdpa-detail"><small>Adviser</small><span><?= e((string) $group['adviser_name']) ?></span></div><span class="badge text-bg-success">Defense Ready</span></div>
                        <div class="rdpa-inner-card"><h4>Ready to Assign</h4><form method="post" data-rd-panel-assign-form><?= csrfField() ?><input type="hidden" name="research_group_id" value="<?= (int) $groupId ?>"><?php foreach ($selectedPanels as $panel): ?><input type="hidden" name="panel_ids[]" value="<?= (int) $panel['id'] ?>"><div class="rdpa-panel-card mb-2" style="cursor:default;"><strong><?= e((string) $panel['full_name']) ?></strong><span class="email"><?= e((string) $panel['email']) ?></span><div class="rdpa-detail"><small>Expertise</small><span><?= e((string) (($panel['expertise'] ?? '') ?: 'Not recorded')) ?></span></div><div class="rdpa-detail"><small>Availability</small><span class="badge text-bg-<?= e(rdPanelBadgeClass((string) $panel['availability_status'])) ?>"><?= e((string) $panel['availability_status']) ?></span></div><div class="rdpa-detail"><small>Current Assignment</small><span><?= (int) $panel['current_assignments'] ?></span></div><span class="badge text-bg-success">Selected</span></div><?php endforeach; ?><button type="button" class="btn btn-sms-primary mt-3" data-rd-panel-open-confirm>Assign Panel Members</button></form></div>
                    </div>
                </section>
            <?php endif; ?>
            <div class="modal fade rdpa-confirm-modal" id="rdPanelAssignConfirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?= smsIcon('user-check', ['class' => 'me-2 text-primary']) ?>Assign Panel Members</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Confirm assigning the selected Panel Members to this Pre-Oral Defense research.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-sms-primary" data-rd-panel-confirm-assign>Assign Panel Members</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <section class="rdpa-card"><div class="rdpa-empty"><?= smsIcon('paper-plane') ?><strong>Sent to Notification</strong><span>Panel assignment notifications are sent automatically after confirmed assignment.</span></div></section>
        <?php endif; ?>
    </div>
    <script src="<?= BASE_URL ?>/assets/js/research-director-panel-assignment.js"></script>
    <?php
}
