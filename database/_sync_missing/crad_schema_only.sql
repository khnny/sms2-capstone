SET FOREIGN_KEY_CHECKS=0;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `grant_document_repository` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `archive_reference` varchar(40) NOT NULL DEFAULT '',
  `status` enum('ARCHIVED') NOT NULL DEFAULT 'ARCHIVED',
  `item_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `archived_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `archived_by_name` varchar(120) DEFAULT NULL,
  `archived_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_document_repository`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gdr_application` (`grant_application_id`),
  ADD KEY `idx_gdr_reference` (`archive_reference`),
  ADD KEY `idx_gdr_archived` (`archived_at`);

ALTER TABLE `grant_document_repository`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_document_repository_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `repository_id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `category` varchar(40) NOT NULL,
  `item_label` varchar(255) NOT NULL DEFAULT '',
  `item_type` enum('file','record') NOT NULL DEFAULT 'record',
  `file_path` varchar(255) DEFAULT NULL,
  `file_original` varchar(255) DEFAULT NULL,
  `download_url` varchar(500) DEFAULT NULL,
  `summary_text` text DEFAULT NULL,
  `metadata_json` text DEFAULT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_document_repository_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gdri_repository` (`repository_id`),
  ADD KEY `idx_gdri_application` (`grant_application_id`),
  ADD KEY `idx_gdri_category` (`category`);

ALTER TABLE `grant_document_repository_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_final_output_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `version_number` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `final_research_title` varchar(500) NOT NULL DEFAULT '',
  `authors` varchar(500) NOT NULL DEFAULT '',
  `abstract` text DEFAULT NULL,
  `publication_type` enum('Journal','Conference','Book Chapter','Repository','Other') NOT NULL DEFAULT 'Journal',
  `journal_conference` varchar(255) NOT NULL DEFAULT '',
  `doi` varchar(120) NOT NULL DEFAULT '',
  `publication_url` varchar(500) NOT NULL DEFAULT '',
  `ip_information` text DEFAULT NULL,
  `copyright_info` text DEFAULT NULL,
  `patent_info` text DEFAULT NULL,
  `other_ip_info` text DEFAULT NULL,
  `final_pdf_path` varchar(255) DEFAULT NULL,
  `final_pdf_original` varchar(255) DEFAULT NULL,
  `supporting_files_json` text DEFAULT NULL,
  `status` enum('FINAL_OUTPUT_SUBMITTED','RETURNED_FOR_CORRECTION','VERIFIED') NOT NULL DEFAULT 'FINAL_OUTPUT_SUBMITTED',
  `return_reason` text DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `submitted_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `submitted_by_name` varchar(120) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `reviewed_by_name` varchar(120) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_final_output_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gfos_application` (`grant_application_id`),
  ADD KEY `idx_gfos_status` (`status`),
  ADD KEY `idx_gfos_submitted` (`submitted_at`);

ALTER TABLE `grant_final_output_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_funded_progress_evidence` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `milestone_id` int(10) UNSIGNED DEFAULT NULL,
  `evidence_title` varchar(200) NOT NULL DEFAULT '',
  `notes` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_original` varchar(255) DEFAULT NULL,
  `submitted_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `submitted_by_name` varchar(120) DEFAULT NULL,
  `status` enum('Submitted','Acknowledged') NOT NULL DEFAULT 'Submitted',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_funded_progress_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gfpe_application` (`grant_application_id`),
  ADD KEY `idx_gfpe_milestone` (`milestone_id`),
  ADD KEY `idx_gfpe_created` (`created_at`);

ALTER TABLE `grant_funded_progress_evidence`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_funded_project_milestones` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `milestone_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `milestone_name` varchar(120) NOT NULL,
  `due_date` date DEFAULT NULL,
  `completion_pct` decimal(5,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
  `supporting_doc` varchar(255) DEFAULT NULL,
  `supporting_doc_original` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `updated_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `updated_by_name` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_funded_project_milestones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gfpm_app_order` (`grant_application_id`,`milestone_order`),
  ADD KEY `idx_gfpm_application` (`grant_application_id`),
  ADD KEY `idx_gfpm_status` (`status`);

ALTER TABLE `grant_funded_project_milestones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_funding_disbursements` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `tranche_number` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `tranche_label` varchar(80) NOT NULL DEFAULT '',
  `approved_budget` decimal(14,2) NOT NULL DEFAULT 0.00,
  `amount_released` decimal(14,2) NOT NULL DEFAULT 0.00,
  `release_date` date DEFAULT NULL,
  `reference_number` varchar(80) DEFAULT NULL,
  `status` enum('Pending','Released','Cancelled') NOT NULL DEFAULT 'Pending',
  `released_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `released_by_name` varchar(120) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_funding_disbursements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gfd_app_tranche` (`grant_application_id`,`tranche_number`),
  ADD KEY `idx_gfd_application` (`grant_application_id`),
  ADD KEY `idx_gfd_status` (`status`);

ALTER TABLE `grant_funding_disbursements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_proposal_approval_steps` (
  `id` int(10) UNSIGNED NOT NULL,
  `workflow_id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `step_key` varchar(40) NOT NULL,
  `step_order` tinyint(3) UNSIGNED NOT NULL,
  `step_label` varchar(80) NOT NULL,
  `approver_role_key` varchar(40) NOT NULL,
  `status` enum('Queued','Pending','Approved','Returned') NOT NULL DEFAULT 'Queued',
  `approver_user_id` int(10) UNSIGNED DEFAULT NULL,
  `approver_name` varchar(150) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `signature_data` mediumtext DEFAULT NULL,
  `acted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_proposal_approval_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpas_workflow_step` (`workflow_id`,`step_key`),
  ADD KEY `idx_gpas_application` (`grant_application_id`),
  ADD KEY `idx_gpas_status` (`status`),
  ADD KEY `idx_gpas_role` (`approver_role_key`);

ALTER TABLE `grant_proposal_approval_steps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_proposal_approval_workflows` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `current_step_key` varchar(40) NOT NULL DEFAULT 'adviser',
  `workflow_status` enum('In Progress','Completed','Returned') NOT NULL DEFAULT 'In Progress',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_proposal_approval_workflows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpaw_application` (`grant_application_id`),
  ADD KEY `idx_gpaw_status` (`workflow_status`),
  ADD KEY `idx_gpaw_current_step` (`current_step_key`);

ALTER TABLE `grant_proposal_approval_workflows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_proposal_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `proposal_version` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Proposal version evaluated',
  `evaluator_user_id` int(10) UNSIGNED NOT NULL,
  `evaluator_name` varchar(150) NOT NULL DEFAULT '',
  `evaluation_type` varchar(20) NOT NULL DEFAULT 'committee' COMMENT 'committee | adviser',
  `score_rationale` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score_methodology` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score_budget` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score_team_capability` decimal(5,2) NOT NULL DEFAULT 0.00,
  `score_compliance` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `comments` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `required_corrections` text DEFAULT NULL,
  `recommendation` varchar(40) DEFAULT NULL COMMENT 'Reviewer decision: disapprove | require_revisions',
  `revision_reason` text DEFAULT NULL COMMENT 'Reason for required revisions',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_proposal_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpe_app_eval_ver` (`grant_application_id`,`evaluator_user_id`,`proposal_version`),
  ADD KEY `idx_gpe_application` (`grant_application_id`),
  ADD KEY `idx_gpe_evaluator` (`evaluator_user_id`),
  ADD KEY `idx_gpe_submitted` (`submitted_at`);

ALTER TABLE `grant_proposal_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_proposal_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_key` varchar(120) NOT NULL,
  `recipient_user_id` int(10) UNSIGNED DEFAULT NULL,
  `recipient_role` varchar(40) NOT NULL DEFAULT '',
  `recipient_email` varchar(190) NOT NULL DEFAULT '',
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `url` varchar(500) NOT NULL DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_proposal_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpn_event` (`event_key`),
  ADD KEY `idx_gpn_recipient_user` (`recipient_user_id`),
  ADD KEY `idx_gpn_application` (`grant_application_id`),
  ADD KEY `idx_gpn_created` (`created_at`);

ALTER TABLE `grant_proposal_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_proposal_versions` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `version_number` int(10) UNSIGNED NOT NULL,
  `version_label` varchar(60) NOT NULL DEFAULT '',
  `proposal_pdf` varchar(255) DEFAULT NULL,
  `proposal_pdf_original` varchar(300) DEFAULT NULL,
  `supporting_docs` varchar(255) DEFAULT NULL,
  `supporting_docs_original` varchar(300) DEFAULT NULL,
  `ethics_doc` varchar(255) DEFAULT NULL,
  `ethics_doc_original` varchar(300) DEFAULT NULL,
  `abstract` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `researcher_notes` text DEFAULT NULL,
  `submitted_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_proposal_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpv_app_ver` (`grant_application_id`,`version_number`),
  ADD KEY `idx_gpv_application` (`grant_application_id`),
  ADD KEY `idx_gpv_submitted` (`submitted_at`);

ALTER TABLE `grant_proposal_versions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `grant_publications_ip_repository` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `submission_id` int(10) UNSIGNED NOT NULL,
  `repository_reference` varchar(40) NOT NULL DEFAULT '',
  `final_research_title` varchar(500) NOT NULL DEFAULT '',
  `authors` varchar(500) NOT NULL DEFAULT '',
  `abstract` text DEFAULT NULL,
  `publication_type` varchar(60) NOT NULL DEFAULT '',
  `journal_conference` varchar(255) NOT NULL DEFAULT '',
  `doi` varchar(120) NOT NULL DEFAULT '',
  `publication_url` varchar(500) NOT NULL DEFAULT '',
  `ip_information` text DEFAULT NULL,
  `copyright_info` text DEFAULT NULL,
  `patent_info` text DEFAULT NULL,
  `other_ip_info` text DEFAULT NULL,
  `final_pdf_path` varchar(255) DEFAULT NULL,
  `final_pdf_original` varchar(255) DEFAULT NULL,
  `supporting_files_json` text DEFAULT NULL,
  `verified_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `verified_by_name` varchar(120) DEFAULT NULL,
  `verified_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grant_publications_ip_repository`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpip_application` (`grant_application_id`),
  ADD KEY `idx_gpip_reference` (`repository_reference`),
  ADD KEY `idx_gpip_verified` (`verified_at`);

ALTER TABLE `grant_publications_ip_repository`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `research_clearance_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_key` varchar(190) NOT NULL,
  `recipient_user_id` int(10) UNSIGNED DEFAULT NULL,
  `recipient_role` varchar(40) NOT NULL DEFAULT '',
  `recipient_email` varchar(190) NOT NULL DEFAULT '',
  `clearance_id` int(10) UNSIGNED DEFAULT NULL,
  `type` varchar(40) NOT NULL DEFAULT '',
  `title` varchar(190) NOT NULL DEFAULT '',
  `body` text DEFAULT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `research_clearance_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rsc_notif_event` (`event_key`),
  ADD KEY `idx_rsc_notif_recipient` (`recipient_user_id`,`recipient_role`);

ALTER TABLE `research_clearance_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `research_clearance_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `research_stage` varchar(20) NOT NULL DEFAULT 'research_1',
  `student_user_id` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_file` varchar(255) NOT NULL DEFAULT '',
  `uploaded_original` varchar(255) NOT NULL DEFAULT '',
  `or_number` varchar(80) NOT NULL DEFAULT '',
  `remarks` varchar(120) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `approved_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `approved_by_name` varchar(160) NOT NULL DEFAULT '',
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `research_clearance_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rcp_group_stage` (`research_group_id`,`research_stage`),
  ADD KEY `idx_rcp_group` (`research_group_id`),
  ADD KEY `idx_rcp_status` (`status`);

ALTER TABLE `research_clearance_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `research_progress_ai_analyses` (
  `id` int(10) UNSIGNED NOT NULL,
  `progress_update_id` int(10) UNSIGNED NOT NULL,
  `attachment_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `milestone_name` varchar(180) NOT NULL DEFAULT '',
  `verdict` varchar(40) NOT NULL DEFAULT 'needs_revision',
  `grammar_quality` varchar(40) NOT NULL DEFAULT 'fair',
  `summary` text NOT NULL,
  `notes_json` mediumtext NOT NULL,
  `source` varchar(40) NOT NULL DEFAULT 'cursor',
  `analyzed_by` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `analyzed_by_name` varchar(180) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `research_progress_ai_analyses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpai_update` (`progress_update_id`,`id`);

ALTER TABLE `research_progress_ai_analyses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

CREATE TABLE IF NOT EXISTS `research_services_clearances` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `research_stage` varchar(20) NOT NULL DEFAULT 'research_1',
  `title_approval_id` int(10) UNSIGNED DEFAULT NULL,
  `status` varchar(40) NOT NULL DEFAULT 'draft',
  `or_number` varchar(80) NOT NULL DEFAULT '',
  `leader_student_no` varchar(40) NOT NULL DEFAULT '',
  `leader_group_no` varchar(40) NOT NULL DEFAULT '',
  `program` varchar(200) NOT NULL DEFAULT '',
  `section` varchar(80) NOT NULL DEFAULT '',
  `research_title` varchar(255) NOT NULL DEFAULT '',
  `members_json` longtext DEFAULT NULL,
  `grammarian_name` varchar(160) NOT NULL DEFAULT '',
  `statistician_name` varchar(160) NOT NULL DEFAULT '',
  `adviser_name` varchar(160) NOT NULL DEFAULT '',
  `adviser_user_id` int(10) UNSIGNED DEFAULT NULL,
  `adviser_email` varchar(190) NOT NULL DEFAULT '',
  `adviser_signature` longtext DEFAULT NULL,
  `adviser_signed_at` datetime DEFAULT NULL,
  `crad_name` varchar(160) NOT NULL DEFAULT '',
  `crad_user_id` int(10) UNSIGNED DEFAULT NULL,
  `crad_signature` longtext DEFAULT NULL,
  `crad_signed_at` datetime DEFAULT NULL,
  `uploaded_file` varchar(255) DEFAULT NULL,
  `uploaded_original` varchar(255) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT NULL,
  `mis_verified` tinyint(1) NOT NULL DEFAULT 0,
  `aa_verified` tinyint(1) NOT NULL DEFAULT 0,
  `mis_verified_at` datetime DEFAULT NULL,
  `aa_verified_at` datetime DEFAULT NULL,
  `export_hash` varchar(64) NOT NULL DEFAULT '',
  `form_verified` tinyint(1) NOT NULL DEFAULT 0,
  `mis_signature` longtext DEFAULT NULL,
  `aa_signature` longtext DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `research_services_clearances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rsc_group_stage` (`research_group_id`,`research_stage`),
  ADD KEY `idx_rsc_status` (`status`),
  ADD KEY `idx_rsc_adviser` (`adviser_user_id`);

ALTER TABLE `research_services_clearances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

SET FOREIGN_KEY_CHECKS=1;
