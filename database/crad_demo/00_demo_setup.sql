-- Create the batch registry and verify the consolidated schema before any demo records are inserted.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET @crad_demo_batch := 'CRAD_DEMO_2026_01';
SET @crad_demo_password_hash := '$2y$10$aw6AyQoVQN0GjjC5sEbi9.rHyehQzN2B7MqWSS9HuZs/3XT2Mslv.';
SET @crad_demo_signature := 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC1EQVR42mP8/x8AAwMCAO+a4l8AAAAASUVORK5CYII=';

CREATE TABLE IF NOT EXISTS `crad_demo_seed_records` (
  `batch_id` varchar(40) NOT NULL,
  `record_key` varchar(100) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `record_id` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`batch_id`, `record_key`),
  UNIQUE KEY `uq_crad_demo_record` (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TEMPORARY TABLE `tmp_crad_demo_setup_guard` (`guard_id` tinyint unsigned NOT NULL PRIMARY KEY) ENGINE=MEMORY;

INSERT INTO `tmp_crad_demo_setup_guard` VALUES (1);

INSERT INTO `tmp_crad_demo_setup_guard` SELECT IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name IN ('sms2_users','sms2_student_profiles','sms2_roles','crad_research_groups','crad_research_group_members','crad_research_coordinator_assignments','crad_research_adviser_assignments','crad_research_assignment_cycles','crad_title_approvals','crad_research_proposals','crad_research_services_clearances','crad_research_plans','crad_research_milestones','crad_research_progress_updates','crad_research_progress_feedback','crad_research_progress_notifications','crad_chapter_submissions','crad_chapter_evaluations','crad_chapter_evaluation_notifications','crad_panel_member_availability','crad_research_defense_schedules','crad_research_panel_assignments','crad_panel_assignment_notifications','crad_preoral_defense_evaluations','crad_research_revision_cycles','crad_final_defense_recommendations','crad_final_defense_evaluations','crad_manuscript_submissions','crad_manuscript_evaluations','crad_final_manuscript_approvals','crad_publications'))=31 AND (SELECT COUNT(*) FROM sms2_roles WHERE role_key IN ('student','research_coordinator','adviser','crad_officer','department_head','panel','grammarian'))=7,2,1);

SELECT 'CRAD demo setup verified; import 01_demo_users.sql next' AS setup_status;