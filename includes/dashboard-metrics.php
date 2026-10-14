<?php
/**
 * SMS 2 - Shared dashboard metrics
 *
 * One provider for every role dashboard. Each figure is a live query against a
 * table that actually backs that role's module. When a module has no backing
 * table the figure is a real 0 and the panel says so — no mock values.
 */

declare(strict_types=1);

/**
 * Human-readable one-liners for the Workspace summary panel.
 *
 * @return array<int, string>
 */
function smsDashSummary(string $roleKey): array
{
    $pdo = smsDashDb();
    $out = [];
    $add = static function (string $line) use (&$out): void {
        if (count($out) < 5) {
            $out[] = $line;
        }
    };

    switch ($roleKey) {
        case 'superadmin':
            $add(smsDashCount($pdo, 'sms2_users') . ' user account' . (smsDashCount($pdo, 'sms2_users') === 1 ? '' : 's') . ' across '
                . smsDashCount($pdo, 'sms2_roles') . ' role' . (smsDashCount($pdo, 'sms2_roles') === 1 ? '' : 's') . '.');
            $add(smsDashCount($pdo, 'sms2_role_permissions', 'granted = 1') . ' permission grant' . (smsDashCount($pdo, 'sms2_role_permissions', 'granted = 1') === 1 ? '' : 's') . ' recorded.');
            $add(smsDashCount($pdo, 'sms2_activity_logs') . ' audit event' . (smsDashCount($pdo, 'sms2_activity_logs') === 1 ? '' : 's') . ' logged.');
            $add(smsDashCount($pdo, 'sms2_password_resets') . ' password reset' . (smsDashCount($pdo, 'sms2_password_resets') === 1 ? '' : 's') . ' on record.');
            break;

        case 'sms_admin':
            $awaiting = smsDashCount($pdo, 'crad_research_clearance_payments', "status IN ('pending','rejected')")
                + smsDashCount($pdo, 'crad_research_services_clearances', "status <> 'clearance_done'");
            $add($awaiting > 0
                ? $awaiting . ' item' . ($awaiting === 1 ? '' : 's') . ' waiting on approval.'
                : 'No payment or clearance is waiting for approval.');
            $add(smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch() . " AND status = 'Finalized'")
                . ' of ' . smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch())
                . ' pre-oral schedules finalized.');
            $add(smsDashCount($pdo, 'crad_research_panel_assignments') . ' panel assignment'
                . (smsDashCount($pdo, 'crad_research_panel_assignments') === 1 ? '' : 's') . ' recorded.');
            $add(smsDashCount($pdo, 'crad_research_services_clearances', "status = 'clearance_done'")
                . ' clearance' . (smsDashCount($pdo, 'crad_research_services_clearances', "status = 'clearance_done'") === 1 ? '' : 's') . ' completed.');
            break;

        case 'crad_officer':
        case 'research_coordinator':
        case 'research_director':
        case 'adviser':
        case 'panel':
            $add(smsDashCount($pdo, 'crad_research_groups') . ' research group'
                . (smsDashCount($pdo, 'crad_research_groups') === 1 ? '' : 's') . ' registered, '
                . smsDashCount($pdo, 'crad_research_groups', "status = 'Approved'") . ' approved.');
            $add(smsDashCount($pdo, 'crad_research_panel_assignments') . ' panel assignment'
                . (smsDashCount($pdo, 'crad_research_panel_assignments') === 1 ? '' : 's') . ' on record.');
            $add(smsDashCount($pdo, 'crad_research_defense_schedules') . ' defense schedule'
                . (smsDashCount($pdo, 'crad_research_defense_schedules') === 1 ? '' : 's') . ' recorded.');
            $add(smsDashCount($pdo, 'crad_research_services_clearances', "status = 'clearance_done'")
                . ' clearance' . (smsDashCount($pdo, 'crad_research_services_clearances', "status = 'clearance_done'") === 1 ? '' : 's') . ' completed.');
            break;

        case 'research_grant':
            $add(smsDashCount($pdo, 'crad_grant_opportunities') . ' grant call'
                . (smsDashCount($pdo, 'crad_grant_opportunities') === 1 ? '' : 's') . ' published.');
            $add(smsDashCount($pdo, 'crad_grant_applications') . ' application'
                . (smsDashCount($pdo, 'crad_grant_applications') === 1 ? '' : 's') . ' on record.');
            $add(smsDashMoney(smsDashSum($pdo, 'crad_grant_funding_disbursements', 'amount_released', "status = 'Released'")) . ' released.');
            break;

        case 'admission':
        case 'registrar':
        case 'student':
            $add(smsDashCount($pdo, 'sms2_student_profiles') . ' student record'
                . (smsDashCount($pdo, 'sms2_student_profiles') === 1 ? '' : 's') . ' on file.');
            $add(smsDashCount($pdo, 'sms2_student_profiles', "LOWER(COALESCE(enrollment_status,'')) = 'enrolled'")
                . ' currently enrolled.');
            $add(count(smsDashGroup($pdo, "SELECT COALESCE(NULLIF(TRIM(program),''),'Unassigned') AS label, COUNT(*) AS total FROM `sms2_student_profiles` GROUP BY label"))
                . ' program' . (count(smsDashGroup($pdo, "SELECT COALESCE(NULLIF(TRIM(program),''),'Unassigned') AS label, COUNT(*) AS total FROM `sms2_student_profiles` GROUP BY label")) === 1 ? '' : 's') . ' represented.');
            break;

        case 'it_office':
        case 'hr':
            $add(smsDashCount($pdo, 'sms2_users') . ' system account'
                . (smsDashCount($pdo, 'sms2_users') === 1 ? '' : 's') . ' registered.');
            $add(smsDashCount($pdo, 'sms2_users', 'last_seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)')
                . ' account' . (smsDashCount($pdo, 'sms2_users', 'last_seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)') === 1 ? '' : 's') . ' active in the last 15 minutes.');
            $add(smsDashCount($pdo, 'sms2_activity_logs') . ' audit event'
                . (smsDashCount($pdo, 'sms2_activity_logs') === 1 ? '' : 's') . ' logged.');
            $add(smsDashCount($pdo, 'sms2_password_resets') . ' password reset request'
                . (smsDashCount($pdo, 'sms2_password_resets') === 1 ? '' : 's') . ' on record.');
            break;

        case 'finance':
            $out[] = 'This workspace has no payment records table yet, so no figures can be reported.';
            break;

        case 'osa':
            $out[] = 'This workspace has no co-curricular records table yet, so no figures can be reported.';
            break;

        case 'qa':
            $out[] = 'This workspace has no accreditation records table yet, so no figures can be reported.';
            break;
    }

    if ($out === []) {
        $out[] = 'No live metrics are available for this workspace yet.';
    }
    return $out;
}

if (!function_exists('getCurrentUserRoleKey')) {
    require_once __DIR__ . '/authentication.php';
}
require_once __DIR__ . '/../config/database.php';

/**
 * Singleton PDO, or null when the database is unavailable.
 */
function smsDashDb(): ?PDO
{
    static $pdo = null;
    static $tried = false;
    if ($tried) {
        return $pdo;
    }
    $tried = true;
    try {
        $pdo = getDatabaseConnection();
    } catch (Throwable $e) {
        error_log('smsDashDb: ' . $e->getMessage());
        $pdo = null;
    }
    return $pdo;
}

/**
 * True when the table exists in this deployment.
 */
function smsDashHasTable(?PDO $pdo, string $table): bool
{
    if (!$pdo instanceof PDO || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    try {
        // SHOW TABLES cannot bind a parameter under native prepares, so the
        // validated identifier is interpolated as a literal.
        $found = $pdo->query("SHOW TABLES LIKE '" . $table . "'")->fetchColumn();
        $cache[$table] = (bool) $found;
    } catch (Throwable $e) {
        error_log('smsDashHasTable ' . $table . ': ' . $e->getMessage());
        $cache[$table] = false;
    }
    return $cache[$table];
}

/**
 * COUNT(*) with an optional filter. Returns 0 for a missing table.
 */
function smsDashCount(?PDO $pdo, string $table, string $where = '', array $params = []): int
{
    if (!smsDashHasTable($pdo, $table)) {
        return 0;
    }
    try {
        $sql = 'SELECT COUNT(*) FROM `' . $table . '`' . ($where !== '' ? ' WHERE ' . $where : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('smsDashCount ' . $table . ': ' . $e->getMessage());
        return 0;
    }
}

/**
 * COALESCE(SUM(col)) with an optional filter. Returns 0.0 for a missing table.
 */
function smsDashSum(?PDO $pdo, string $table, string $column, string $where = '', array $params = []): float
{
    if (!smsDashHasTable($pdo, $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return 0.0;
    }
    try {
        $sql = 'SELECT COALESCE(SUM(`' . $column . '`), 0) FROM `' . $table . '`'
            . ($where !== '' ? ' WHERE ' . $where : '');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (float) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('smsDashSum ' . $table . ': ' . $e->getMessage());
        return 0.0;
    }
}

/**
 * @return array<int, array{label: string, total: int}>
 */
function smsDashGroup(?PDO $pdo, string $sql, string $labelColumn = 'label', string $totalColumn = 'total'): array
{
    if (!$pdo instanceof PDO) {
        return [];
    }
    try {
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('smsDashGroup: ' . $e->getMessage());
        return [];
    }
    $out = [];
    foreach ($rows as $row) {
        $label = trim((string) ($row[$labelColumn] ?? ''));
        if ($label === '') {
            $label = 'Unassigned';
        }
        $out[] = ['label' => $label, 'total' => (int) ($row[$totalColumn] ?? 0)];
    }
    return $out;
}

/**
 * @return array<int, array<string, mixed>>
 */
function smsDashRows(?PDO $pdo, string $sql, int $limit = 50): array
{
    if (!$pdo instanceof PDO) {
        return [];
    }
    try {
        return $pdo->query($sql . ' LIMIT ' . max(1, $limit))->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('smsDashRows: ' . $e->getMessage());
        return [];
    }
}

function smsDashAgo(string $value): string
{
    $value = trim($value);
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return 'Just now';
    }
    $ts = strtotime($value);
    if ($ts === false || $ts > time()) {
        return 'Just now';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return $m . ' min' . ($m === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return $h . ' hr' . ($h === 1 ? '' : 's') . ' ago';
    }
    $d = (int) floor($diff / 86400);
    if ($d < 30) {
        return $d . ' day' . ($d === 1 ? '' : 's') . ' ago';
    }
    return date('M j, Y', $ts);
}

function smsDashMoney(float $amount): string
{
    return '₱' . number_format($amount, 0);
}

/**
 * Defence scheduling is keyed by a free-text phase label.
 */
function smsDashPreOralMatch(): string
{
    return "(LOWER(COALESCE(defense_type, '')) LIKE '%pre-oral%'"
        . " OR LOWER(COALESCE(defense_type, '')) LIKE '%preoral%')";
}

/* ──────────────────────────────────────────────────────────────────────────
 * Per-role metric definitions
 * ────────────────────────────────────────────────────────────────────────── */

/**
 * Stat cards for a role. Each entry:
 *   icon, label, value, type, deltaLabel, liveKey
 *
 * @return array<int, array<string, string>>
 */
function smsDashStatCards(string $roleKey): array
{
    $pdo = smsDashDb();
    $viewerId = function_exists('getCurrentUserId') ? (int) getCurrentUserId() : 0;

    $card = static fn(string $icon, string $label, $value, string $type, string $deltaLabel, string $liveKey): array => [
        'icon' => $icon, 'label' => $label,
        'value' => is_string($value) ? $value : number_format((float) $value, 0),
        'type' => $type, 'delta' => 'Live', 'deltaDir' => 'neutral',
        'deltaLabel' => $deltaLabel, 'liveKey' => $liveKey,
    ];

    switch ($roleKey) {
        case 'superadmin':
            return [
                $card('fa-users-cog', 'Managed Accounts', smsDashCount($pdo, 'sms2_users'), 'primary', 'registered users', 'users_total'),
                $card('fa-user-shield', 'Roles Defined', smsDashCount($pdo, 'sms2_roles'), 'info', 'in role matrix', 'roles_total'),
                $card('fa-key', 'Permission Grants', smsDashCount($pdo, 'sms2_role_permissions', 'granted = 1'), 'success', 'granted entries', 'permissions_granted'),
                $card('fa-history', 'Audit Events', smsDashCount($pdo, 'sms2_activity_logs'), 'warning', 'logged events', 'activity_total'),
            ];

        case 'sms_admin':
            return [
                $card('fa-file-invoice', 'Payment Approvals', smsDashCount($pdo, 'crad_research_clearance_payments', "status IN ('pending','rejected')"), 'warning', 'awaiting approval', 'payment_pending'),
                $card('fa-stamp', 'Clearance Approvals', smsDashCount($pdo, 'crad_research_services_clearances', "status <> 'clearance_done'"), 'success', 'awaiting sign-off', 'clearance_pending'),
                $card('fa-calendar-alt', 'Pre-Oral Defense', smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch()), 'primary', 'scheduled groups', 'preoral_total'),
                $card('fa-gavel', 'Final Defense', smsDashCount($pdo, 'crad_research_defense_schedules', 'NOT ' . smsDashPreOralMatch()), 'info', 'scheduled groups', 'final_total'),
            ];

        case 'admission':
            return [
                $card('fa-user-plus', 'Student Records', smsDashCount($pdo, 'sms2_student_profiles'), 'primary', 'on file', 'students_total'),
                $card('fa-user-check', 'Enrolled', smsDashCount($pdo, 'sms2_student_profiles', "LOWER(COALESCE(enrollment_status,'')) = 'enrolled'"), 'success', 'active enrollment', 'students_enrolled'),
                $card('fa-list', 'Programs', count(smsDashGroup($pdo, "SELECT COALESCE(NULLIF(TRIM(program),''),'Unassigned') AS label, COUNT(*) AS total FROM `sms2_student_profiles` GROUP BY label")), 'info', 'represented', 'programs_total'),
                $card('fa-clipboard-list', 'Pending Validation', 0, 'warning', 'no enrollment queue table', 'admission_pending'),
            ];

        case 'registrar':
            return [
                $card('fa-folder-open', 'Student Records', smsDashCount($pdo, 'sms2_student_profiles'), 'primary', 'on file', 'students_total'),
                $card('fa-layer-group', 'Programs', count(smsDashGroup($pdo, "SELECT COALESCE(NULLIF(TRIM(program),''),'Unassigned') AS label, COUNT(*) AS total FROM `sms2_student_profiles` GROUP BY label")), 'info', 'represented', 'programs_total'),
                $card('fa-sitemap', 'Sections', smsDashCount($pdo, 'sms2_student_profiles', "TRIM(COALESCE(section,'')) <> ''"), 'success', 'with section', 'sections_total'),
                $card('fa-file-signature', 'Pending Requests', 0, 'warning', 'no registrar request table', 'registrar_pending'),
            ];

        case 'finance':
            return [
                $card('fa-file-invoice', 'Collected', smsDashMoney(0.0), 'success', 'no payment table', 'finance_collected'),
                $card('fa-clock', 'Pending', 0, 'warning', 'no payment table', 'finance_pending'),
                $card('fa-money-bill', 'Unpaid Accounts', 0, 'info', 'no payment table', 'finance_unpaid'),
                $card('fa-receipt', 'Receipts Issued', 0, 'primary', 'no payment table', 'finance_receipts'),
            ];

        case 'hr':
            return [
                $card('fa-chalkboard-teacher', 'Faculty Records', 0, 'primary', 'no faculty table', 'hr_faculty'),
                $card('fa-user-tie', 'Leave Requests', 0, 'warning', 'no leave table', 'hr_leave'),
                $card('fa-users-cog', 'Staff Accounts', smsDashCount($pdo, 'sms2_users'), 'info', 'system accounts', 'hr_accounts'),
                $card('fa-clipboard-check', 'Pending Approvals', 0, 'success', 'no HR queue table', 'hr_pending'),
            ];

        case 'it_office':
            return [
                $card('fa-users-cog', 'User Accounts', smsDashCount($pdo, 'sms2_users'), 'primary', 'registered', 'users_total'),
                $card('fa-signal', 'Active Sessions', smsDashCount($pdo, 'sms2_users', 'last_seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'), 'success', 'seen in 15 min', 'users_recent'),
                $card('fa-shield-alt', 'Security Events', smsDashCount($pdo, 'sms2_activity_logs', "LOWER(COALESCE(module_key,'')) IN ('user-management','system')"), 'info', 'security log', 'security_events'),
                $card('fa-key', 'Pending Resets', smsDashCount($pdo, 'sms2_password_resets'), 'warning', 'reset requests', 'password_resets'),
            ];

        case 'osa':
            return [
                $card('fa-users', 'Registered Clubs', 0, 'primary', 'no clubs table', 'osa_clubs'),
                $card('fa-calendar-check', 'Events This Month', 0, 'success', 'no events table', 'osa_events'),
                $card('fa-user-check', 'Active Members', 0, 'info', 'no membership table', 'osa_members'),
                $card('fa-hand-holding-usd', 'Budget Requests', 0, 'warning', 'no OSA budget table', 'osa_budget'),
            ];

        case 'qa':
            return [
                $card('fa-award', 'Accredited Programs', 0, 'success', 'no accreditation table', 'qa_programs'),
                $card('fa-clipboard-list', 'Compliance Items', 0, 'primary', 'no compliance table', 'qa_items'),
                $card('fa-exclamation-circle', 'Non-Conformities', 0, 'warning', 'no findings table', 'qa_findings'),
                $card('fa-calendar-alt', 'Next Audit', '—', 'info', 'no audit schedule table', 'qa_next_audit'),
            ];

        case 'crad_officer':
            return [
                $card('fa-file-lines', 'Total Applications', smsDashCount($pdo, 'crad_research_proposals'), 'primary', 'submitted proposals', 'applications_total'),
                $card('fa-clock', 'Pending Applications', smsDashCount($pdo, 'crad_research_proposals', "status IN ('Submitted','In Progress','Panel Assigned')"), 'warning', 'awaiting decision', 'applications_pending'),
                $card('fa-check-double', 'Approved Applications', smsDashCount($pdo, 'crad_research_proposals', "status = 'Approved'"), 'success', 'approved proposals', 'applications_approved'),
                $card('fa-calendar-alt', 'Scheduled Defenses', smsDashCount($pdo, 'crad_research_defense_schedules', "defense_datetime IS NOT NULL AND LOWER(COALESCE(status,'')) IN ('scheduled','finalized')"), 'info', 'confirmed schedules', 'defenses_scheduled'),
                $card('fa-check-circle', 'Completed Defenses', smsDashCount($pdo, 'crad_research_defense_schedules', "LOWER(COALESCE(status,'')) IN ('completed','passed')"), 'success', 'completed reviews', 'defenses_completed'),
                $card('fa-user-tie', 'Panel Members', smsDashCount($pdo, 'crad_research_panel_assignments'), 'info', 'panelists assigned', 'panel_assigned'),
            ];

        case 'research_coordinator':
            return [
                $card('fa-flask', 'Assigned Research', smsDashCount($pdo, 'crad_research_coordinator_assignments', "coordinator_user_id = ? AND status = 'Active'", [$viewerId]), 'primary', 'active groups', 'coordinator_groups'),
                $card('fa-check-double', 'Approved Research', smsDashCount($pdo, 'crad_research_groups', "status = 'Approved' AND id IN (SELECT research_group_id FROM crad_research_coordinator_assignments WHERE coordinator_user_id = ? AND status = 'Active')", [$viewerId]), 'success', 'assigned groups', 'coordinator_approved'),
                $card('fa-user-tie', 'Adviser Assignments', smsDashCount($pdo, 'crad_research_adviser_assignments', "research_group_id IN (SELECT research_group_id FROM crad_research_coordinator_assignments WHERE coordinator_user_id = ? AND status = 'Active')", [$viewerId]), 'info', 'within assigned groups', 'coordinator_advisers'),
                $card('fa-tasks', 'Open Cycles', smsDashCount($pdo, 'crad_research_assignment_cycles', "status <> 'completed'"), 'warning', 'assignment cycles', 'cycles_open'),
            ];

        case 'research_grant':
            return [
                $card('fa-bullhorn', 'Grant Calls', smsDashCount($pdo, 'crad_grant_opportunities'), 'primary', 'published calls', 'grant_calls'),
                $card('fa-file-alt', 'Applications', smsDashCount($pdo, 'crad_grant_applications'), 'info', 'submitted', 'grant_applications'),
                $card('fa-check-circle', 'Approved & Funded', smsDashCount($pdo, 'crad_grant_applications', "status IN ('Approved','Funded','Approved & Funded')"), 'success', 'funded', 'grant_funded'),
                $card('fa-peso-sign', 'Funds Released', smsDashMoney(smsDashSum($pdo, 'crad_grant_funding_disbursements', 'amount_released', "status = 'Released'")), 'warning', 'disbursed', 'grant_released'),
            ];

        case 'adviser':
        case 'panel':
            $isPanel = ($roleKey === 'panel');
            return [
                $card('fa-flask', 'Assigned Research', $isPanel
                    ? smsDashCount($pdo, 'crad_research_panel_assignments', 'panel_user_id = ?', [$viewerId])
                    : smsDashCount($pdo, 'crad_research_adviser_assignments', 'adviser_user_id = ?', [$viewerId]), 'primary', 'your assignments', $isPanel ? 'my_panel_assigned' : 'my_adviser_assigned'),
                $card('fa-calendar', 'Defense Schedules', smsDashCount($pdo, 'crad_research_defense_schedules', $isPanel
                    ? 'research_group_id IN (SELECT research_group_id FROM crad_research_panel_assignments WHERE panel_user_id = ?)'
                    : 'research_group_id IN (SELECT research_group_id FROM crad_research_adviser_assignments WHERE adviser_user_id = ?)', [$viewerId]), 'warning', 'your assigned groups', $isPanel ? 'my_panel_defenses' : 'my_adviser_defenses'),
                $card('fa-folder-open', $isPanel ? 'Panel Evaluations' : 'Chapter Submissions',
                    $isPanel
                        ? smsDashCount($pdo, 'crad_preoral_defense_evaluations', 'panel_user_id = ?', [$viewerId])
                        : smsDashCount($pdo, 'crad_chapter_submissions', 'research_group_id IN (SELECT research_group_id FROM crad_research_adviser_assignments WHERE adviser_user_id = ?)', [$viewerId]), 'info', 'your assigned groups', $isPanel ? 'my_panel_evaluations' : 'my_adviser_chapters'),
                $card('fa-check-square', $isPanel ? 'Final Defense Evaluations' : 'Research Groups',
                    $isPanel
                        ? smsDashCount($pdo, 'crad_final_defense_evaluations', 'panel_user_id = ?', [$viewerId])
                        : smsDashCount($pdo, 'crad_research_groups', 'id IN (SELECT research_group_id FROM crad_research_adviser_assignments WHERE adviser_user_id = ?)', [$viewerId]), 'success', 'your assigned work', $isPanel ? 'my_panel_final_evaluations' : 'my_adviser_groups'),
            ];

        case 'research_director':
            return [
                $card('fa-check-double', 'Defense Ready', smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch() . " AND status = 'Finalized'"), 'primary', 'finalized', 'preoral_finalized'),
                $card('fa-calendar-plus', 'Pre-Oral Scheduled', smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch()), 'warning', 'scheduled', 'preoral_total'),
                $card('fa-users', 'Panel Assignments', smsDashCount($pdo, 'crad_research_panel_assignments'), 'info', 'panelists', 'panel_assigned'),
                $card('fa-archive', 'For Archiving', smsDashCount($pdo, 'crad_research_plans', 'COALESCE(final_defense_recommended, 0) = 1'), 'success', 'recommended', 'archiving'),
            ];

        case 'student':
            return [
                $card('fa-folder-open', 'My Record', smsDashCount($pdo, 'sms2_student_profiles'), 'primary', 'student profiles', 'students_total'),
                $card('fa-flask', 'Research Groups', smsDashCount($pdo, 'crad_research_groups'), 'info', 'registered', 'research_groups'),
                $card('fa-stamp', 'Clearances', smsDashCount($pdo, 'crad_research_services_clearances', "status = 'clearance_done'"), 'success', 'cleared', 'clearance_done'),
                $card('fa-file-invoice', 'Approved Payments', smsDashCount($pdo, 'crad_research_clearance_payments', "status = 'approved'"), 'warning', 'approved', 'payment_approved'),
            ];
    }

    // Fallback for any role without a bespoke definition.
    return [
        $card('fa-users-cog', 'User Accounts', smsDashCount($pdo, 'sms2_users'), 'primary', 'registered', 'users_total'),
        $card('fa-flask', 'Research Groups', smsDashCount($pdo, 'crad_research_groups'), 'info', 'registered', 'research_groups'),
        $card('fa-stamp', 'Clearances', smsDashCount($pdo, 'crad_research_services_clearances'), 'success', 'on record', 'clearance_total'),
        $card('fa-history', 'Activity Events', smsDashCount($pdo, 'sms2_activity_logs'), 'warning', 'logged', 'activity_total'),
    ];
}

/**
 * Flat live values keyed by liveKey, for the JSON poller.
 *
 * @return array<string, int|float>
 */
function smsDashLiveValues(string $roleKey): array
{
    $pdo = smsDashDb();
    $out = [];
    foreach (smsDashStatCards($roleKey) as $card) {
        $key = (string) ($card['liveKey'] ?? '');
        if ($key === '') {
            continue;
        }
        $out[$key] = smsDashLiveValue($pdo, $key);
    }
    return $out;
}

/**
 * Resolve one liveKey to a real number (or string for non-numeric labels).
 */
function smsDashLiveValue(?PDO $pdo, string $key)
{
    $viewerId = function_exists('getCurrentUserId') ? (int) getCurrentUserId() : 0;
    switch ($key) {
        case 'users_total':       return smsDashCount($pdo, 'sms2_users');
        case 'users_recent':      return smsDashCount($pdo, 'sms2_users', 'last_seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
        case 'roles_total':       return smsDashCount($pdo, 'sms2_roles');
        case 'permissions_granted': return smsDashCount($pdo, 'sms2_role_permissions', 'granted = 1');
        case 'activity_total':    return smsDashCount($pdo, 'sms2_activity_logs');
        case 'security_events':   return smsDashCount($pdo, 'sms2_activity_logs', "LOWER(COALESCE(module_key,'')) IN ('user-management','system')");
        case 'password_resets':   return smsDashCount($pdo, 'sms2_password_resets');

        case 'students_total':    return smsDashCount($pdo, 'sms2_student_profiles');
        case 'students_enrolled': return smsDashCount($pdo, 'sms2_student_profiles', "LOWER(COALESCE(enrollment_status,'')) = 'enrolled'");
        case 'programs_total':    return count(smsDashGroup($pdo, "SELECT COALESCE(NULLIF(TRIM(program),''),'Unassigned') AS label, COUNT(*) AS total FROM `sms2_student_profiles` GROUP BY label"));
        case 'sections_total':    return smsDashCount($pdo, 'sms2_student_profiles', "TRIM(COALESCE(section,'')) <> ''");

        case 'research_groups':   return smsDashCount($pdo, 'crad_research_groups');
        case 'coordinator_groups': return smsDashCount($pdo, 'crad_research_coordinator_assignments', "coordinator_user_id = ? AND status = 'Active'", [$viewerId]);
        case 'coordinator_approved': return smsDashCount($pdo, 'crad_research_groups', "status = 'Approved' AND id IN (SELECT research_group_id FROM crad_research_coordinator_assignments WHERE coordinator_user_id = ? AND status = 'Active')", [$viewerId]);
        case 'coordinator_advisers': return smsDashCount($pdo, 'crad_research_adviser_assignments', 'research_group_id IN (SELECT research_group_id FROM crad_research_coordinator_assignments WHERE coordinator_user_id = ? AND status = \'Active\')', [$viewerId]);
        case 'my_adviser_assigned': return smsDashCount($pdo, 'crad_research_adviser_assignments', 'adviser_user_id = ?', [$viewerId]);
        case 'my_adviser_defenses': return smsDashCount($pdo, 'crad_research_defense_schedules', 'research_group_id IN (SELECT research_group_id FROM crad_research_adviser_assignments WHERE adviser_user_id = ?)', [$viewerId]);
        case 'my_adviser_chapters': return smsDashCount($pdo, 'crad_chapter_submissions', 'research_group_id IN (SELECT research_group_id FROM crad_research_adviser_assignments WHERE adviser_user_id = ?)', [$viewerId]);
        case 'my_adviser_groups': return smsDashCount($pdo, 'crad_research_groups', 'id IN (SELECT research_group_id FROM crad_research_adviser_assignments WHERE adviser_user_id = ?)', [$viewerId]);
        case 'my_panel_assigned': return smsDashCount($pdo, 'crad_research_panel_assignments', 'panel_user_id = ?', [$viewerId]);
        case 'my_panel_defenses': return smsDashCount($pdo, 'crad_research_defense_schedules', 'research_group_id IN (SELECT research_group_id FROM crad_research_panel_assignments WHERE panel_user_id = ?)', [$viewerId]);
        case 'my_panel_evaluations': return smsDashCount($pdo, 'crad_preoral_defense_evaluations', 'panel_user_id = ?', [$viewerId]);
        case 'my_panel_final_evaluations': return smsDashCount($pdo, 'crad_final_defense_evaluations', 'panel_user_id = ?', [$viewerId]);
        case 'applications_total': return smsDashCount($pdo, 'crad_research_proposals');
        case 'applications_pending': return smsDashCount($pdo, 'crad_research_proposals', "status IN ('Submitted','In Progress','Panel Assigned')");
        case 'applications_approved': return smsDashCount($pdo, 'crad_research_proposals', "status = 'Approved'");
        case 'defenses_scheduled': return smsDashCount($pdo, 'crad_research_defense_schedules', "defense_datetime IS NOT NULL AND LOWER(COALESCE(status,'')) IN ('scheduled','finalized')");
        case 'defenses_completed': return smsDashCount($pdo, 'crad_research_defense_schedules', "LOWER(COALESCE(status,'')) IN ('completed','passed')");
        case 'approved_research': return smsDashCount($pdo, 'crad_research_groups', "status = 'Approved'");
        case 'adviser_assigned':  return smsDashCount($pdo, 'crad_research_adviser_assignments');
        case 'panel_assigned':    return smsDashCount($pdo, 'crad_research_panel_assignments');
        case 'panel_evaluations': return smsDashCount($pdo, 'crad_preoral_defense_evaluations');
        case 'final_evaluations': return smsDashCount($pdo, 'crad_final_defense_evaluations');
        case 'chapter_submissions': return smsDashCount($pdo, 'crad_chapter_submissions');
        case 'defense_total':     return smsDashCount($pdo, 'crad_research_defense_schedules');
        case 'preoral_total':     return smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch());
        case 'preoral_finalized': return smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch() . " AND status = 'Finalized'");
        case 'final_total':       return smsDashCount($pdo, 'crad_research_defense_schedules', 'NOT ' . smsDashPreOralMatch());
        case 'archiving':         return smsDashCount($pdo, 'crad_research_plans', 'COALESCE(final_defense_recommended, 0) = 1');
        case 'cycles_open':       return smsDashCount($pdo, 'crad_research_assignment_cycles', "status <> 'completed'");

        case 'payment_pending':   return smsDashCount($pdo, 'crad_research_clearance_payments', "status IN ('pending','rejected')");
        case 'payment_approved':  return smsDashCount($pdo, 'crad_research_clearance_payments', "status = 'approved'");
        case 'clearance_pending': return smsDashCount($pdo, 'crad_research_services_clearances', "status <> 'clearance_done'");
        case 'clearance_done':    return smsDashCount($pdo, 'crad_research_services_clearances', "status = 'clearance_done'");
        case 'clearance_total':   return smsDashCount($pdo, 'crad_research_services_clearances');

        case 'grant_calls':         return smsDashCount($pdo, 'crad_grant_opportunities');
        case 'grant_applications':  return smsDashCount($pdo, 'crad_grant_applications');
        case 'grant_funded':        return smsDashCount($pdo, 'crad_grant_applications', "status IN ('Approved','Funded','Approved & Funded')");
        case 'grant_released':      return smsDashSum($pdo, 'crad_grant_funding_disbursements', 'amount_released', "status = 'Released'");
    }

    // Modules with no backing table stay an honest zero.
    return 0;
}

/**
 * Is this liveKey a money value?
 */
function smsDashIsMoney(string $key): bool
{
    return in_array($key, ['grant_released', 'finance_collected'], true);
}

/**
 * Widget data for the glass board: donut, table, progress, activity, summary.
 *
 * @return array<string, mixed>
 */
function smsDashWidgets(string $roleKey): array
{
    $pdo = smsDashDb();
    $palette = ['#3b82f6', '#8b5cf6', '#22c55e', '#f59e0b', '#06b6d4', '#ef4444'];

    $buildLegend = static function (array $rows) use ($palette): array {
        $total = 0;
        foreach ($rows as $r) {
            $total += (int) $r['total'];
        }
        $out = [];
        $i = 0;
        foreach ($rows as $r) {
            $n = (int) $r['total'];
            $out[] = [
                'label' => (string) $r['label'],
                'pct'   => ($total > 0 ? (int) round($n / $total * 100) : 0) . '%',
                'color' => $palette[$i % count($palette)],
            ];
            $i++;
        }
        return $out;
    };

    $donutRows = [];
    $sourceTitle = 'Records by module';
    $sourceSub = 'Live distribution';
    $donutLabel = 'Records';
    $tableTitle = 'Recent records';
    $tableSub = 'Latest entries';
    $tableRows = [];
    $activityTitle = 'Recent activity';
    $activitySub = 'Latest recorded events';
    $activities = [];
    $progressTitle = 'Module progress';
    $progressSub = 'Completion against available records';
    $progressItems = [];

    switch ($roleKey) {
        case 'superadmin':
            $donutRows = smsDashGroup($pdo, "SELECT COALESCE(NULLIF(TRIM(module_key),''),'System') AS label, COUNT(*) AS total FROM `sms2_activity_logs` GROUP BY label ORDER BY total DESC");
            $sourceTitle = 'Activity by module';
            $sourceSub = 'Logged events per workspace';
            $donutLabel = 'Events';
            $tableTitle = 'User accounts';
            $tableSub = 'Registered accounts';
            foreach (smsDashRows($pdo, "SELECT full_name, role_key, status, last_login_at FROM `sms2_users` ORDER BY COALESCE(last_login_at, created_at) DESC") as $r) {
                $tableRows[] = [
                    'name' => (string) ($r['full_name'] ?? ''), 'initial' => strtoupper(substr((string) ($r['full_name'] ?? '?'), 0, 1)),
                    'role' => (string) ($r['role_key'] ?? ''), 'status' => 'active', 'statusLabel' => (string) ($r['status'] ?? ''),
                    'when' => smsDashAgo((string) ($r['last_login_at'] ?? '')),
                ];
            }
            $progressItems = [
                ['label' => 'Accounts registered', 'pct' => 100, 'tone' => 'blue', 'done' => smsDashCount($pdo, 'sms2_users'), 'total' => max(1, smsDashCount($pdo, 'sms2_users'))],
                ['label' => 'Permission grants', 'pct' => 100, 'tone' => 'green', 'done' => smsDashCount($pdo, 'sms2_role_permissions', 'granted = 1'), 'total' => max(1, smsDashCount($pdo, 'sms2_role_permissions'))],
                ['label' => 'Roles defined', 'pct' => 100, 'tone' => 'orange', 'done' => smsDashCount($pdo, 'sms2_roles'), 'total' => max(1, smsDashCount($pdo, 'sms2_roles'))],
            ];
            foreach (smsDashRows($pdo, "SELECT action, module_key, detail, created_at FROM `sms2_activity_logs` ORDER BY created_at DESC, id DESC") as $r) {
                $activities[] = [
                    'icon' => 'fa-history', 'tone' => 'blue',
                    'text' => trim((string) ($r['action'] ?? 'activity')) . ' - ' . trim((string) ($r['module_key'] ?? 'System')),
                    'when' => smsDashAgo((string) ($r['created_at'] ?? '')),
                ];
            }
            break;

        case 'sms_admin':
            $preOral = smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch());
            $final = smsDashCount($pdo, 'crad_research_defense_schedules', 'NOT ' . smsDashPreOralMatch());
            $donutRows = array_values(array_filter([
                ['label' => 'Pre-Oral', 'total' => $preOral],
                ['label' => 'Final Defense', 'total' => $final],
            ], static fn(array $r): bool => (int) $r['total'] > 0));
            $sourceTitle = 'Defense workload by phase';
            $sourceSub = 'Scheduled research groups';
            $donutLabel = 'Groups';
            $tableTitle = 'Research groups';
            $tableSub = 'Latest registered groups';
            foreach (smsDashRows($pdo, "SELECT group_number, research_title, college_dept, status, created_at FROM `crad_research_groups` ORDER BY created_at DESC, id DESC") as $r) {
                $gn = (string) ($r['group_number'] ?? '');
                $tableRows[] = [
                    'name' => $gn !== '' ? $gn : ('Group #' . (string) ($r['id'] ?? '')),
                    'initial' => strtoupper(substr($gn !== '' ? $gn : 'G', 0, 1)),
                    'role' => trim((string) ($r['college_dept'] ?? '')) !== '' ? (string) $r['college_dept'] : 'Unassigned college',
                    'status' => ((string) ($r['status'] ?? '')) === 'Approved' ? 'active' : 'away',
                    'statusLabel' => (string) ($r['status'] ?? ''),
                    'when' => smsDashAgo((string) ($r['created_at'] ?? '')),
                ];
            }
            $clearDone = smsDashCount($pdo, 'crad_research_services_clearances', "status = 'clearance_done'");
            $progressItems = [
                ['label' => 'Pre-Oral finalized', 'pct' => 100, 'tone' => 'blue', 'done' => smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch() . " AND status = 'Finalized'"), 'total' => max(1, $preOral)],
                ['label' => 'Payments approved', 'pct' => 100, 'tone' => 'green', 'done' => smsDashCount($pdo, 'crad_research_clearance_payments', "status = 'approved'"), 'total' => max(1, smsDashCount($pdo, 'crad_research_clearance_payments'))],
                ['label' => 'Clearances completed', 'pct' => 100, 'tone' => 'orange', 'done' => $clearDone, 'total' => max(1, smsDashCount($pdo, 'crad_research_services_clearances'))],
                ['label' => 'Panel confirmed', 'pct' => 100, 'tone' => 'red', 'done' => smsDashCount($pdo, 'crad_research_panel_assignments', "LOWER(COALESCE(availability_status,'')) IN ('available','confirmed')"), 'total' => max(1, smsDashCount($pdo, 'crad_research_panel_assignments'))],
            ];
            foreach (smsDashRows($pdo, "SELECT title, created_at FROM `crad_research_clearance_notifications` ORDER BY created_at DESC, id DESC") as $r) {
                $activities[] = [
                    'icon' => 'fa-stamp', 'tone' => 'blue',
                    'text' => trim((string) ($r['title'] ?? 'Clearance update')),
                    'when' => smsDashAgo((string) ($r['created_at'] ?? '')),
                ];
            }
            foreach (smsDashRows($pdo, "SELECT panel_name, group_number, assigned_at FROM `crad_research_panel_assignments` ORDER BY assigned_at DESC, id DESC") as $r) {
                $activities[] = [
                    'icon' => 'fa-user-tie', 'tone' => 'green',
                    'text' => trim((string) ($r['panel_name'] ?? 'Panel member')) . ' assigned to ' . trim((string) ($r['group_number'] ?? 'group')),
                    'when' => smsDashAgo((string) ($r['assigned_at'] ?? '')),
                ];
            }
            break;

        case 'admission':
        case 'registrar':
        case 'student':
            $donutRows = smsDashGroup($pdo, "SELECT COALESCE(NULLIF(TRIM(program),''),'Unassigned') AS label, COUNT(*) AS total FROM `sms2_student_profiles` GROUP BY label ORDER BY total DESC");
            $sourceTitle = 'Students by program';
            $sourceSub = 'Distribution across programs';
            $donutLabel = 'Students';
            $tableTitle = 'Student records';
            $tableSub = 'Profiles on file';
            foreach (smsDashRows($pdo, "SELECT student_id, program, year_level, section, enrollment_status FROM `sms2_student_profiles` ORDER BY id DESC") as $r) {
                $sid = (string) ($r['student_id'] ?? '');
                $tableRows[] = [
                    'name' => $sid !== '' ? $sid : ('Profile #' . (string) ($r['id'] ?? '')),
                    'initial' => strtoupper(substr($sid !== '' ? $sid : 'P', 0, 1)),
                    'role' => trim((string) ($r['program'] ?? '')) !== '' ? (string) $r['program'] : 'Unassigned program',
                    'status' => 'active', 'statusLabel' => (string) ($r['enrollment_status'] ?? ''),
                    'when' => 'On file',
                ];
            }
            $progressItems = [
                ['label' => 'Enrolled', 'pct' => 100, 'tone' => 'green', 'done' => smsDashCount($pdo, 'sms2_student_profiles', "LOWER(COALESCE(enrollment_status,'')) = 'enrolled'"), 'total' => max(1, smsDashCount($pdo, 'sms2_student_profiles'))],
                ['label' => 'With section', 'pct' => 100, 'tone' => 'blue', 'done' => smsDashCount($pdo, 'sms2_student_profiles', "TRIM(COALESCE(section,'')) <> ''"), 'total' => max(1, smsDashCount($pdo, 'sms2_student_profiles'))],
                ['label' => 'With year level', 'pct' => 100, 'tone' => 'orange', 'done' => smsDashCount($pdo, 'sms2_student_profiles', "TRIM(COALESCE(year_level,'')) <> ''"), 'total' => max(1, smsDashCount($pdo, 'sms2_student_profiles'))],
            ];
            break;

        case 'it_office':
        case 'hr':
            $donutRows = smsDashGroup($pdo, "SELECT COALESCE(NULLIF(TRIM(role_key),''),'Unassigned') AS label, COUNT(*) AS total FROM `sms2_users` GROUP BY label ORDER BY total DESC");
            $sourceTitle = 'Accounts by role';
            $sourceSub = 'Registered system accounts';
            $donutLabel = 'Accounts';
            $tableTitle = 'User accounts';
            $tableSub = 'Registered accounts';
            foreach (smsDashRows($pdo, "SELECT full_name, role_key, status, last_seen_at FROM `sms2_users` ORDER BY COALESCE(last_seen_at, created_at) DESC") as $r) {
                $tableRows[] = [
                    'name' => (string) ($r['full_name'] ?? ''), 'initial' => strtoupper(substr((string) ($r['full_name'] ?? '?'), 0, 1)),
                    'role' => (string) ($r['role_key'] ?? ''), 'status' => 'active', 'statusLabel' => (string) ($r['status'] ?? ''),
                    'when' => smsDashAgo((string) ($r['last_seen_at'] ?? '')),
                ];
            }
            $progressItems = [
                ['label' => 'Active accounts', 'pct' => 100, 'tone' => 'green', 'done' => smsDashCount($pdo, 'sms2_users', "status = 'active'"), 'total' => max(1, smsDashCount($pdo, 'sms2_users'))],
                ['label' => 'Seen recently', 'pct' => 100, 'tone' => 'blue', 'done' => smsDashCount($pdo, 'sms2_users', 'last_seen_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'), 'total' => max(1, smsDashCount($pdo, 'sms2_users'))],
                ['label' => 'Password resets', 'pct' => 100, 'tone' => 'orange', 'done' => smsDashCount($pdo, 'sms2_password_resets'), 'total' => max(1, smsDashCount($pdo, 'sms2_password_resets'))],
            ];
            foreach (smsDashRows($pdo, "SELECT action, module_key, created_at FROM `sms2_activity_logs` ORDER BY created_at DESC, id DESC") as $r) {
                $activities[] = [
                    'icon' => 'fa-shield-alt', 'tone' => 'purple',
                    'text' => trim((string) ($r['action'] ?? 'activity')) . ' - ' . trim((string) ($r['module_key'] ?? 'System')),
                    'when' => smsDashAgo((string) ($r['created_at'] ?? '')),
                ];
            }
            break;

        case 'finance':
        case 'osa':
        case 'qa':
            // These modules have no backing tables in this deployment.
            $donutRows = [];
            $sourceTitle = 'No data source';
            $sourceSub = 'This module has no records table yet';
            $donutLabel = 'Records';
            $progressItems = [
                ['label' => 'Records available', 'pct' => 0, 'tone' => 'blue', 'done' => 0, 'total' => 1],
            ];
            $activities[] = ['icon' => 'fa-info-circle', 'tone' => 'blue', 'text' => 'No records table is deployed for this module yet.', 'when' => '—'];
            break;

        default:
            // CRAD-family roles.
            $donutRows = smsDashGroup($pdo, "SELECT COALESCE(NULLIF(TRIM(college_dept),''),'Unassigned') AS label, COUNT(*) AS total FROM `crad_research_groups` GROUP BY label ORDER BY total DESC");
            $sourceTitle = 'Research groups by college';
            $sourceSub = 'Registered CRAD research groups';
            $donutLabel = 'Groups';
            $tableTitle = 'Research groups';
            $tableSub = 'Latest registered groups';
            foreach (smsDashRows($pdo, "SELECT group_number, research_title, college_dept, status, created_at FROM `crad_research_groups` ORDER BY created_at DESC, id DESC") as $r) {
                $gn = (string) ($r['group_number'] ?? '');
                $tableRows[] = [
                    'name' => $gn !== '' ? $gn : ('Group #' . (string) ($r['id'] ?? '')),
                    'initial' => strtoupper(substr($gn !== '' ? $gn : 'G', 0, 1)),
                    'role' => trim((string) ($r['college_dept'] ?? '')) !== '' ? (string) $r['college_dept'] : 'Unassigned college',
                    'status' => ((string) ($r['status'] ?? '')) === 'Approved' ? 'active' : 'away',
                    'statusLabel' => (string) ($r['status'] ?? ''),
                    'when' => smsDashAgo((string) ($r['created_at'] ?? '')),
                ];
            }
            $preOral = smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch());
            $preOralFin = smsDashCount($pdo, 'crad_research_defense_schedules', smsDashPreOralMatch() . " AND status = 'Finalized'");
            $clearDone = smsDashCount($pdo, 'crad_research_services_clearances', "status = 'clearance_done'");
            $clearAll = max(1, smsDashCount($pdo, 'crad_research_services_clearances'));
            $groups = max(1, smsDashCount($pdo, 'crad_research_groups'));
            $progressItems = [
                ['label' => 'Groups registered', 'pct' => 100, 'tone' => 'blue', 'done' => smsDashCount($pdo, 'crad_research_groups'), 'total' => $groups],
                ['label' => 'Title approved', 'pct' => 100, 'tone' => 'green', 'done' => smsDashCount($pdo, 'crad_research_groups', "status = 'Approved'"), 'total' => $groups],
                ['label' => 'Pre-Oral finalized', 'pct' => 100, 'tone' => 'orange', 'done' => $preOralFin, 'total' => max(1, $preOral)],
                ['label' => 'Clearance approved', 'pct' => 100, 'tone' => 'red', 'done' => $clearDone, 'total' => $clearAll],
            ];
            foreach (smsDashRows($pdo, "SELECT title, created_at FROM `crad_research_clearance_notifications` ORDER BY created_at DESC, id DESC") as $r) {
                $activities[] = [
                    'icon' => 'fa-stamp', 'tone' => 'blue',
                    'text' => trim((string) ($r['title'] ?? 'Clearance update')),
                    'when' => smsDashAgo((string) ($r['created_at'] ?? '')),
                ];
            }
            foreach (smsDashRows($pdo, "SELECT panel_name, group_number, assigned_at FROM `crad_research_panel_assignments` ORDER BY assigned_at DESC, id DESC") as $r) {
                $activities[] = [
                    'icon' => 'fa-user-tie', 'tone' => 'green',
                    'text' => trim((string) ($r['panel_name'] ?? 'Panel member')) . ' assigned to ' . trim((string) ($r['group_number'] ?? 'group')),
                    'when' => smsDashAgo((string) ($r['assigned_at'] ?? '')),
                ];
            }
            break;
    }

    if ($tableRows === []) {
        $tableRows = [['name' => 'No records yet', 'initial' => '—', 'role' => 'Nothing on file for this module', 'status' => 'away', 'statusLabel' => 'Empty', 'when' => '—']];
    }
    if ($activities === []) {
        $activities = [['icon' => 'fa-info-circle', 'tone' => 'blue', 'text' => 'No activity recorded yet.', 'when' => '—']];
    }
    $progressItems = array_slice($progressItems, 0, 5);
    if ($progressItems === []) {
        $progressItems = [['label' => 'No stage data', 'pct' => 0, 'tone' => 'blue', 'done' => 0, 'total' => 1]];
    }

    $donutTotal = 0;
    foreach ($donutRows as $r) {
        $donutTotal += (int) $r['total'];
    }

    $stats = [];
    foreach (smsDashStatCards($roleKey) as $c) {
        $stats[(string) $c['liveKey']] = smsDashLiveValue($pdo, (string) $c['liveKey']);
    }

    return [
        'stats'        => $stats,
        'donut_rows'   => $donutRows,
        'donut_total'  => $donutTotal,
        'donut_label'  => $donutLabel,
        'legend'       => $buildLegend($donutRows),
        'source_title' => $sourceTitle,
        'source_sub'   => $sourceSub,
        'table_title'  => $tableTitle,
        'table_sub'    => $tableSub,
        'table_rows'   => array_slice($tableRows, 0, 5),
        'progress'     => $progressItems,
        'progress_title' => $progressTitle,
        'progress_sub' => $progressSub,
        'activity_title' => $activityTitle,
        'activity_sub' => $activitySub,
        'activity'     => array_slice($activities, 0, 6),
    ];
}
