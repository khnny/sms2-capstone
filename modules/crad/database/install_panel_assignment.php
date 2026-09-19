<?php
/**
 * Install Research Director Panel Assignment tables.
 * Run: C:\xampp\php\php.exe modules/crad/database/install_panel_assignment.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Forbidden. Run from CLI only.\n";
    exit(1);
}

require_once __DIR__ . '/../config/config.php';

$pdo = getCradDatabaseConnection();

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS research_panel_assignments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        research_group_id INT UNSIGNED NOT NULL,
        defense_schedule_id INT UNSIGNED DEFAULT NULL,
        proposal_id INT UNSIGNED DEFAULT NULL,
        title_approval_id INT UNSIGNED DEFAULT NULL,
        proposal_number VARCHAR(30) DEFAULT NULL,
        group_number VARCHAR(40) NOT NULL DEFAULT '',
        research_title VARCHAR(255) NOT NULL DEFAULT '',
        panel_user_id INT UNSIGNED NOT NULL,
        panel_name VARCHAR(150) NOT NULL DEFAULT '',
        panel_email VARCHAR(190) NOT NULL DEFAULT '',
        expertise VARCHAR(255) NOT NULL DEFAULT '',
        availability_status VARCHAR(40) NOT NULL DEFAULT 'Pending',
        assignment_status VARCHAR(40) NOT NULL DEFAULT 'Assigned',
        defense_phase VARCHAR(60) NOT NULL DEFAULT 'Pre-Oral Defense',
        assigned_by INT UNSIGNED DEFAULT NULL,
        assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_panel_assignment_phase (research_group_id, panel_user_id, defense_phase),
        KEY idx_panel_assignment_group (research_group_id),
        KEY idx_panel_assignment_user (panel_user_id),
        KEY idx_panel_assignment_status (assignment_status),
        KEY idx_panel_assignment_schedule (defense_schedule_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS panel_member_availability (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        panel_user_id INT UNSIGNED NOT NULL,
        availability_status VARCHAR(40) NOT NULL DEFAULT 'Pending',
        notes TEXT DEFAULT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_panel_availability_user (panel_user_id),
        KEY idx_panel_availability_status (availability_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS panel_assignment_notifications (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_key VARCHAR(140) NOT NULL,
        recipient_user_id INT UNSIGNED NOT NULL,
        recipient_role VARCHAR(60) NOT NULL DEFAULT 'panel',
        recipient_email VARCHAR(190) NOT NULL DEFAULT '',
        panel_assignment_id INT UNSIGNED DEFAULT NULL,
        research_group_id INT UNSIGNED DEFAULT NULL,
        title VARCHAR(160) NOT NULL DEFAULT '',
        body TEXT DEFAULT NULL,
        url VARCHAR(500) NOT NULL DEFAULT '',
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_panel_assignment_notification (event_key),
        KEY idx_panel_notification_recipient (recipient_user_id, recipient_role, recipient_email),
        KEY idx_panel_notification_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

echo "DONE. Panel Assignment tables ready.\n";
