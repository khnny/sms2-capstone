<?php
/**
 * Install Panel Pre-Oral Defense evaluation tables.
 * Run: C:\xampp\php\php.exe modules/crad/database/install_panel_preoral.php
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

echo "DONE. Panel Pre-Oral evaluation tables ready.\n";
