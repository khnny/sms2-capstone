-- HostForge-ready: Research Group early flow + dual-confirm cycle
-- Run in HostForge SQL editor on the app database (same DB as sms2 / crad tables).
-- Safe to re-run CREATE TABLE IF NOT EXISTS; ADD COLUMN statements may error if
-- the column already exists — ignore those errors or run only missing pieces.

CREATE TABLE IF NOT EXISTS `crad_research_group_members` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `research_group_id` INT UNSIGNED NOT NULL,
  `member_order` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `student_id` VARCHAR(40) NOT NULL DEFAULT '',
  `full_name` VARCHAR(160) NOT NULL DEFAULT '',
  `section` VARCHAR(80) NOT NULL DEFAULT '',
  `email` VARCHAR(190) NOT NULL DEFAULT '',
  `or_number` VARCHAR(80) NOT NULL DEFAULT '',
  `is_leader` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rgm_group` (`research_group_id`),
  KEY `idx_rgm_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crad_research_assignment_cycles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `research_group_id` INT UNSIGNED DEFAULT NULL,
  `group_number` VARCHAR(40) NOT NULL DEFAULT '',
  `student_id` VARCHAR(40) NOT NULL DEFAULT '',
  `coordinator_assignment_id` INT UNSIGNED DEFAULT NULL,
  `adviser_assignment_id` INT UNSIGNED DEFAULT NULL,
  `status` VARCHAR(40) NOT NULL DEFAULT 'pending_confirmation',
  `coordinator_confirmed_at` DATETIME DEFAULT NULL,
  `coordinator_confirmed_by` INT UNSIGNED DEFAULT NULL,
  `adviser_confirmed_at` DATETIME DEFAULT NULL,
  `adviser_confirmed_by` INT UNSIGNED DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `cancelled_by` INT UNSIGNED DEFAULT NULL,
  `cancel_role` VARCHAR(40) NOT NULL DEFAULT '',
  `cancel_reason` TEXT DEFAULT NULL,
  `assigned_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rac_group` (`research_group_id`),
  KEY `idx_rac_group_number` (`group_number`),
  KEY `idx_rac_student` (`student_id`),
  KEY `idx_rac_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Additive columns on crad_research_groups (ignore "Duplicate column" errors)
ALTER TABLE `crad_research_groups`
  ADD COLUMN `flow_status` VARCHAR(40) NOT NULL DEFAULT 'draft' AFTER `status`;
ALTER TABLE `crad_research_groups`
  ADD COLUMN `incomplete_reason` TEXT DEFAULT NULL AFTER `flow_status`;
ALTER TABLE `crad_research_groups`
  ADD COLUMN `is_complete` TINYINT(1) NOT NULL DEFAULT 0 AFTER `incomplete_reason`;
ALTER TABLE `crad_research_groups`
  ADD COLUMN `member_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `is_complete`;
ALTER TABLE `crad_research_groups`
  ADD COLUMN `min_members_required` INT UNSIGNED NOT NULL DEFAULT 5 AFTER `member_count`;
ALTER TABLE `crad_research_groups`
  ADD COLUMN `submitted_by_user_id` INT UNSIGNED DEFAULT NULL AFTER `min_members_required`;
ALTER TABLE `crad_research_groups`
  ADD COLUMN `submitted_at` DATETIME DEFAULT NULL AFTER `submitted_by_user_id`;
ALTER TABLE `crad_research_groups`
  ADD COLUMN `dh_decision` VARCHAR(20) NOT NULL DEFAULT '' AFTER `submitted_at`;
ALTER TABLE `crad_research_groups`
  ADD COLUMN `dh_decision_by` INT UNSIGNED DEFAULT NULL AFTER `dh_decision`;
ALTER TABLE `crad_research_groups`
  ADD COLUMN `dh_decision_at` DATETIME DEFAULT NULL AFTER `dh_decision_by`;
ALTER TABLE `crad_research_groups`
  ADD COLUMN `dh_remarks` TEXT DEFAULT NULL AFTER `dh_decision_at`;

ALTER TABLE `crad_research_adviser_assignments`
  ADD COLUMN `confirmation_status` VARCHAR(40) NOT NULL DEFAULT 'pending_confirmation' AFTER `assignment_status`;
ALTER TABLE `crad_research_adviser_assignments`
  ADD COLUMN `confirmed_at` DATETIME DEFAULT NULL AFTER `confirmation_status`;
ALTER TABLE `crad_research_adviser_assignments`
  ADD COLUMN `confirmed_by` INT UNSIGNED DEFAULT NULL AFTER `confirmed_at`;

ALTER TABLE `crad_research_coordinator_assignments`
  ADD COLUMN `confirmation_status` VARCHAR(40) NOT NULL DEFAULT 'pending_confirmation' AFTER `status`;
ALTER TABLE `crad_research_coordinator_assignments`
  ADD COLUMN `confirmed_at` DATETIME DEFAULT NULL AFTER `confirmation_status`;
ALTER TABLE `crad_research_coordinator_assignments`
  ADD COLUMN `confirmed_by` INT UNSIGNED DEFAULT NULL AFTER `confirmed_at`;
