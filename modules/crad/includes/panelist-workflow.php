<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function cradEnsurePanelistWorkflowSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS `crad_panelist_applications` (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        application_ref VARCHAR(32) NOT NULL,
        resume_token_hash CHAR(64) NOT NULL,
        applicant_name VARCHAR(180) NOT NULL DEFAULT '',
        applicant_email VARCHAR(190) NOT NULL DEFAULT '',
        status VARCHAR(32) NOT NULL DEFAULT 'Draft',
        application_data LONGTEXT NOT NULL,
        submitted_at DATETIME DEFAULT NULL,
        reviewed_by INT UNSIGNED DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_panel_application_ref (application_ref),
        UNIQUE KEY uniq_panel_resume_token (resume_token_hash),
        KEY idx_panel_application_status (status),
        KEY idx_panel_application_email (applicant_email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `crad_panel_application_documents` (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        application_id INT UNSIGNED NOT NULL,
        document_type VARCHAR(40) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        mime_type VARCHAR(120) NOT NULL,
        file_size INT UNSIGNED NOT NULL,
        file_data LONGBLOB NOT NULL,
        uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_panel_application_document (application_id, document_type),
        KEY idx_panel_document_application (application_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `crad_panel_application_history` (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        application_id INT UNSIGNED NOT NULL,
        old_status VARCHAR(32) DEFAULT NULL,
        new_status VARCHAR(32) NOT NULL,
        actor_user_id INT UNSIGNED DEFAULT NULL,
        actor_name VARCHAR(180) NOT NULL DEFAULT 'Applicant',
        note TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_panel_application_history (application_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS `crad_panelist_pool` (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        application_id INT UNSIGNED NOT NULL,
        application_ref VARCHAR(32) NOT NULL,
        applicant_name VARCHAR(180) NOT NULL DEFAULT '',
        applicant_email VARCHAR(190) NOT NULL DEFAULT '',
        institution VARCHAR(190) NOT NULL DEFAULT '',
        department VARCHAR(190) NOT NULL DEFAULT '',
        position VARCHAR(190) NOT NULL DEFAULT '',
        primary_specialization VARCHAR(190) NOT NULL DEFAULT '',
        secondary_specialization VARCHAR(190) NOT NULL DEFAULT '',
        research_areas TEXT NOT NULL,
        expertise_keywords TEXT NOT NULL,
        years_research_experience VARCHAR(80) NOT NULL DEFAULT '',
        defense_preferences TEXT NOT NULL,
        available_days VARCHAR(190) NOT NULL DEFAULT '',
        available_time_ranges VARCHAR(190) NOT NULL DEFAULT '',
        preferred_schedule TEXT NOT NULL,
        unavailable_periods TEXT NOT NULL,
        panel_status VARCHAR(32) NOT NULL DEFAULT 'Pending',
        approval_date DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_panel_pool_application (application_id),
        KEY idx_panel_pool_status (panel_status),
        KEY idx_panel_pool_name (applicant_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function cradPanelApplicationHistory(
    PDO $pdo,
    int $applicationId,
    ?string $oldStatus,
    string $newStatus,
    ?int $actorUserId,
    string $actorName,
    string $note = ''
): void {
    $stmt = $pdo->prepare(
        'INSERT INTO `crad_panel_application_history`
            (application_id, old_status, new_status, actor_user_id, actor_name, note)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$applicationId, $oldStatus, $newStatus, $actorUserId, $actorName, $note]);
}

function cradPanelistPoolStatuses(): array
{
    return ['Pending', 'Eligible', 'Available', 'Assigned', 'Inactive'];
}

function cradEnsurePanelistAssignmentSchema(PDO $pdo): void
{
    $assignmentTable = sms2_quote_table(crad_table('panel_pool_assignments'));
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$assignmentTable} (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        pool_id INT UNSIGNED NOT NULL,
        research_group_id INT UNSIGNED NOT NULL,
        defense_type VARCHAR(60) NOT NULL,
        panel_role VARCHAR(20) NOT NULL DEFAULT 'Member',
        assignment_status VARCHAR(32) NOT NULL DEFAULT 'Assigned',
        assigned_by INT UNSIGNED DEFAULT NULL,
        assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_pool_group_defense (pool_id, research_group_id, defense_type),
        KEY idx_pool_assignment_group (research_group_id, defense_type),
        KEY idx_pool_assignment_status (assignment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `crad_research_defense_schedules` (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        research_group_id INT UNSIGNED DEFAULT NULL,
        proposal_id INT UNSIGNED DEFAULT NULL,
        proposal_number VARCHAR(30) DEFAULT NULL,
        group_number VARCHAR(40) NOT NULL DEFAULT '',
        research_group VARCHAR(120) NOT NULL DEFAULT '',
        research_title VARCHAR(255) NOT NULL DEFAULT '',
        adviser_name VARCHAR(160) DEFAULT NULL,
        panel_members TEXT DEFAULT NULL,
        panel_chair VARCHAR(160) DEFAULT NULL,
        defense_type VARCHAR(60) NOT NULL DEFAULT 'Proposal Defense',
        venue VARCHAR(120) DEFAULT NULL,
        defense_datetime DATETIME DEFAULT NULL,
        defense_end_datetime DATETIME DEFAULT NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'Ready for Scheduling',
        recorded_by INT UNSIGNED DEFAULT NULL,
        recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_rds_proposal_id (proposal_id),
        KEY idx_rds_proposal_number (proposal_number),
        KEY idx_rds_status (status),
        KEY idx_rds_group_stage (research_group_id, defense_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $columns = $pdo->query("SHOW COLUMNS FROM `crad_research_defense_schedules`")->fetchAll(PDO::FETCH_COLUMN, 0);
    $addColumns = [
        'research_group_id' => "ALTER TABLE `crad_research_defense_schedules` ADD research_group_id INT UNSIGNED DEFAULT NULL",
        'proposal_id' => "ALTER TABLE `crad_research_defense_schedules` ADD proposal_id INT UNSIGNED DEFAULT NULL",
        'proposal_number' => "ALTER TABLE `crad_research_defense_schedules` ADD proposal_number VARCHAR(30) DEFAULT NULL",
        'research_group' => "ALTER TABLE `crad_research_defense_schedules` ADD research_group VARCHAR(120) NOT NULL DEFAULT ''",
        'research_title' => "ALTER TABLE `crad_research_defense_schedules` ADD research_title VARCHAR(255) NOT NULL DEFAULT ''",
        'adviser_name' => "ALTER TABLE `crad_research_defense_schedules` ADD adviser_name VARCHAR(160) DEFAULT NULL",
        'panel_members' => "ALTER TABLE `crad_research_defense_schedules` ADD panel_members TEXT DEFAULT NULL",
        'panel_chair' => "ALTER TABLE `crad_research_defense_schedules` ADD panel_chair VARCHAR(160) DEFAULT NULL",
        'defense_type' => "ALTER TABLE `crad_research_defense_schedules` ADD defense_type VARCHAR(60) NOT NULL DEFAULT 'Proposal Defense'",
        'venue' => "ALTER TABLE `crad_research_defense_schedules` ADD venue VARCHAR(120) DEFAULT NULL",
        'defense_datetime' => "ALTER TABLE `crad_research_defense_schedules` ADD defense_datetime DATETIME DEFAULT NULL",
        'defense_end_datetime' => "ALTER TABLE `crad_research_defense_schedules` ADD defense_end_datetime DATETIME DEFAULT NULL",
        'status' => "ALTER TABLE `crad_research_defense_schedules` ADD status VARCHAR(40) NOT NULL DEFAULT 'Ready for Scheduling'",
        'recorded_by' => "ALTER TABLE `crad_research_defense_schedules` ADD recorded_by INT UNSIGNED DEFAULT NULL",
        'recorded_at' => "ALTER TABLE `crad_research_defense_schedules` ADD recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE `crad_research_defense_schedules` ADD updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];
    foreach ($addColumns as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec($sql);
        }
    }

    $legacyUnique = $pdo->query(
        "SHOW INDEX FROM `crad_research_defense_schedules`
         WHERE Key_name = 'uniq_rds_group_number' AND Non_unique = 0"
    )->fetch();
    if ($legacyUnique) {
        $pdo->exec('ALTER TABLE `crad_research_defense_schedules` DROP INDEX uniq_rds_group_number');
    }
}

function cradPanelAvailabilityConflicts(array $panelist, DateTimeImmutable $start, DateTimeImmutable $end): array
{
    $conflicts = [];
    $days = trim((string) ($panelist['available_days'] ?? ''));
    $dayNumber = (int) $start->format('N');
    $dayAliases = [
        1 => ['monday', 'mon'],
        2 => ['tuesday', 'tue', 'tues'],
        3 => ['wednesday', 'wed'],
        4 => ['thursday', 'thu', 'thur', 'thurs'],
        5 => ['friday', 'fri'],
        6 => ['saturday', 'sat'],
        7 => ['sunday', 'sun'],
    ];
    $availableDayNumbers = [];
    if (preg_match('/\bweekdays?\b/i', $days) === 1) {
        $availableDayNumbers = [1, 2, 3, 4, 5];
    } elseif (preg_match('/\bweekends?\b/i', $days) === 1) {
        $availableDayNumbers = [6, 7];
    } else {
        foreach ($dayAliases as $number => $aliases) {
            foreach ($aliases as $alias) {
                if (preg_match('/\b' . $alias . '\b/i', $days) === 1) {
                    $availableDayNumbers[] = $number;
                    break;
                }
            }
        }
        if (preg_match('/\b(mon(?:day)?|tue(?:sday)?|wed(?:nesday)?|thu(?:rsday)?|fri(?:day)?)\s*(?:-|to|–)\s*(mon(?:day)?|tue(?:sday)?|wed(?:nesday)?|thu(?:rsday)?|fri(?:day)?)\b/i', $days, $range) === 1) {
            $rangeStart = array_search(strtolower($range[1]), ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], true);
            $rangeEnd = array_search(strtolower($range[2]), ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], true);
            if ($rangeStart === false) {
                $rangeStart = array_search(substr(strtolower($range[1]), 0, 3), ['mon', 'tue', 'wed', 'thu', 'fri'], true);
            }
            if ($rangeEnd === false) {
                $rangeEnd = array_search(substr(strtolower($range[2]), 0, 3), ['mon', 'tue', 'wed', 'thu', 'fri'], true);
            }
            if ($rangeStart !== false && $rangeEnd !== false && $rangeStart <= $rangeEnd) {
                $availableDayNumbers = range($rangeStart + 1, $rangeEnd + 1);
            }
        }
    }
    if ($days !== '' && $availableDayNumbers !== [] && !in_array($dayNumber, $availableDayNumbers, true)) {
        $conflicts[] = $panelist['applicant_name'] . ' is not available on ' . $start->format('l') . '.';
    }

    $unavailable = trim((string) ($panelist['unavailable_periods'] ?? ''));
    if ($unavailable !== '' && (
        stripos($unavailable, $start->format('Y-m-d')) !== false
        || preg_match('/\b' . preg_quote($start->format('F j'), '/') . '\b/i', $unavailable) === 1
    )) {
        $conflicts[] = $panelist['applicant_name'] . ' marked this date as unavailable.';
    }

    $timeRanges = trim((string) ($panelist['available_time_ranges'] ?? ''));
    if ($timeRanges !== '') {
        preg_match_all(
            '/(\d{1,2}(?::\d{2})?\s*(?:am|pm)?)\s*(?:-|to|–)\s*(\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i',
            $timeRanges,
            $matches,
            PREG_SET_ORDER
        );
        if ($matches === []) {
            $conflicts[] = $panelist['applicant_name'] . "'s availability time format could not be checked; use a range such as 9:00 AM-5:00 PM.";
        } else {
            $isCovered = false;
            foreach ($matches as $match) {
                $rangeStart = strtotime($start->format('Y-m-d') . ' ' . $match[1]);
                $rangeEnd = strtotime($start->format('Y-m-d') . ' ' . $match[2]);
                if ($rangeStart !== false && $rangeEnd !== false
                    && $start->getTimestamp() >= $rangeStart && $end->getTimestamp() <= $rangeEnd) {
                    $isCovered = true;
                    break;
                }
            }
            if (!$isCovered) {
                $conflicts[] = $panelist['applicant_name'] . ' is outside the available time range they provided.';
            }
        }
    }

    return $conflicts;
}

function cradPanelistQualifiedForDefenseType(array $panelist, string $defenseType): bool
{
    $preferences = trim((string) ($panelist['defense_preferences'] ?? ''));
    if ($preferences === '') {
        return true;
    }

    $decoded = json_decode($preferences, true);
    if (is_array($decoded)) {
        $preferences = implode(', ', array_map('strval', $decoded));
    }

    return stripos($preferences, $defenseType) !== false;
}

function cradSyncApprovedPanelistPool(PDO $pdo, int $applicationId): void
{
    $stmt = $pdo->prepare('SELECT * FROM `crad_panelist_applications` WHERE id = ? LIMIT 1');
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$application) {
        return;
    }

    $data = json_decode((string) $application['application_data'], true) ?: [];
    $applicantName = trim((string) ($application['applicant_name'] ?: ($data['applicant_name'] ?? '')));
    $email = trim((string) ($application['applicant_email'] ?: ($data['applicant_email'] ?? '')));

    if ((string) $application['status'] !== 'Approved') {
        $stmt = $pdo->prepare(
            'UPDATE `crad_panelist_pool`
             SET panel_status = CASE WHEN panel_status = "Assigned" THEN "Assigned" ELSE "Inactive" END,
                 updated_at = CURRENT_TIMESTAMP
             WHERE application_id = ?'
        );
        $stmt->execute([$applicationId]);
        return;
    }

    $panelStatus = 'Eligible';
    $poolData = [
        'application_id' => $applicationId,
        'application_ref' => (string) $application['application_ref'],
        'applicant_name' => $applicantName,
        'applicant_email' => $email,
        'institution' => (string) ($data['institution'] ?? ''),
        'department' => (string) ($data['college_department'] ?? ''),
        'position' => (string) ($data['position'] ?? ''),
        'primary_specialization' => (string) ($data['primary_specialization'] ?? ''),
        'secondary_specialization' => (string) ($data['secondary_specialization'] ?? ''),
        'research_areas' => (string) ($data['research_areas'] ?? ''),
        'expertise_keywords' => (string) ($data['expertise_keywords'] ?? ''),
        'years_research_experience' => (string) ($data['years_research_experience'] ?? ''),
        'defense_preferences' => (string) ($data['defense_preferences'] ?? ''),
        'available_days' => (string) ($data['available_days'] ?? ''),
        'available_time_ranges' => (string) ($data['available_time_ranges'] ?? ''),
        'preferred_schedule' => (string) ($data['preferred_schedule'] ?? ''),
        'unavailable_periods' => (string) ($data['unavailable_periods'] ?? ''),
        'panel_status' => $panelStatus,
        'approval_date' => null,
    ];

    $check = $pdo->prepare('SELECT id FROM `crad_panelist_pool` WHERE application_id = ? LIMIT 1');
    $check->execute([$applicationId]);
    if ($check->fetchColumn() !== false) {
        $stmt = $pdo->prepare(
            'UPDATE `crad_panelist_pool`
             SET application_ref = ?, applicant_name = ?, applicant_email = ?, institution = ?, department = ?,
                 position = ?, primary_specialization = ?, secondary_specialization = ?, research_areas = ?,
                 expertise_keywords = ?, years_research_experience = ?, defense_preferences = ?,
                 available_days = ?, available_time_ranges = ?, preferred_schedule = ?, unavailable_periods = ?,
                 panel_status = ?, approval_date = COALESCE(approval_date, NOW()), updated_at = CURRENT_TIMESTAMP
             WHERE application_id = ?'
        );
        $stmt->execute([
            $poolData['application_ref'],
            $poolData['applicant_name'],
            $poolData['applicant_email'],
            $poolData['institution'],
            $poolData['department'],
            $poolData['position'],
            $poolData['primary_specialization'],
            $poolData['secondary_specialization'],
            $poolData['research_areas'],
            $poolData['expertise_keywords'],
            $poolData['years_research_experience'],
            $poolData['defense_preferences'],
            $poolData['available_days'],
            $poolData['available_time_ranges'],
            $poolData['preferred_schedule'],
            $poolData['unavailable_periods'],
            $poolData['panel_status'],
            $applicationId,
        ]);
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO `crad_panelist_pool`
            (application_id, application_ref, applicant_name, applicant_email, institution, department, position,
             primary_specialization, secondary_specialization, research_areas, expertise_keywords,
             years_research_experience, defense_preferences, available_days, available_time_ranges,
             preferred_schedule, unavailable_periods, panel_status, approval_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, NOW()))'
    );
    $stmt->execute([
        $poolData['application_id'],
        $poolData['application_ref'],
        $poolData['applicant_name'],
        $poolData['applicant_email'],
        $poolData['institution'],
        $poolData['department'],
        $poolData['position'],
        $poolData['primary_specialization'],
        $poolData['secondary_specialization'],
        $poolData['research_areas'],
        $poolData['expertise_keywords'],
        $poolData['years_research_experience'],
        $poolData['defense_preferences'],
        $poolData['available_days'],
        $poolData['available_time_ranges'],
        $poolData['preferred_schedule'],
        $poolData['unavailable_periods'],
        $poolData['panel_status'],
        $poolData['approval_date'],
    ]);
}
