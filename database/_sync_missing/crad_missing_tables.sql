SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `crad_grant_document_repository`;
CREATE TABLE `crad_grant_document_repository` (
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

INSERT INTO `crad_grant_document_repository` (`id`, `grant_application_id`, `archive_reference`, `status`, `item_count`, `archived_by_user_id`, `archived_by_name`, `archived_at`, `updated_at`) VALUES
(1, 8, 'DAR-2026-001', 'ARCHIVED', 32, 3, 'User', '2026-09-18 03:33:53', '2026-09-18 03:33:53');

ALTER TABLE `crad_grant_document_repository`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gdr_application` (`grant_application_id`),
  ADD KEY `idx_gdr_reference` (`archive_reference`),
  ADD KEY `idx_gdr_archived` (`archived_at`);

ALTER TABLE `crad_grant_document_repository`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

DROP TABLE IF EXISTS `crad_grant_document_repository_items`;
CREATE TABLE `crad_grant_document_repository_items` (
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

INSERT INTO `crad_grant_document_repository_items` (`id`, `repository_id`, `grant_application_id`, `category`, `item_label`, `item_type`, `file_path`, `file_original`, `download_url`, `summary_text`, `metadata_json`, `sort_order`, `created_at`) VALUES
(1, 1, 8, 'proposal', 'Proposal PDF', 'file', '764440cbeb67f86f451e1e85d88081ec.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-proposal-file.php?id=8&field=proposal', NULL, '{\"field\":\"proposal\"}', 0, '2026-09-18 03:33:53'),
(2, 1, 8, 'proposal', 'Supporting Documents', 'file', 'b579a21b6b589636d32fc62cb87212cb.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-proposal-file.php?id=8&field=supporting', NULL, '{\"field\":\"supporting\"}', 1, '2026-09-18 03:33:53'),
(3, 1, 8, 'proposal', 'Ethics Clearance', 'file', 'efb7d05d2e9f4954fba0cdabf98259ec.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-proposal-file.php?id=8&field=ethics', NULL, '{\"field\":\"ethics\"}', 2, '2026-09-18 03:33:53'),
(4, 1, 8, 'proposal_revisions', 'Original — Proposal PDF', 'file', '764440cbeb67f86f451e1e85d88081ec.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-proposal-file.php?id=8&field=proposal&version=1', NULL, '{\"version_number\":1}', 3, '2026-09-18 03:33:53'),
(5, 1, 8, 'proposal_revisions', 'Original — Supporting Docs', 'file', 'b579a21b6b589636d32fc62cb87212cb.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', NULL, NULL, '{\"version_number\":1}', 4, '2026-09-18 03:33:53'),
(6, 1, 8, 'reviewer_scores', 'Review Committee — Review Committee Member', 'record', NULL, NULL, NULL, 'Review Committee — Review Committee Member\nTotal Score: 100.00 / 100\nRationale: 25.00 | Methodology: 30.00 | Budget: 20.00 | Team: 15.00 | Compliance: 10.00\nRecommendation: recommend\nComments: a', '{\"evaluation_id\":27}', 5, '2026-09-18 03:33:53'),
(7, 1, 8, 'reviewer_scores', 'Academic Adviser — Dr. Roberto M. Santos', 'record', NULL, NULL, NULL, 'Academic Adviser — Dr. Roberto M. Santos\nTotal Score: 100.00 / 100\nRationale: 25.00 | Methodology: 30.00 | Budget: 20.00 | Team: 15.00 | Compliance: 10.00\nRecommendation: recommend', '{\"evaluation_id\":28}', 6, '2026-09-18 03:33:53'),
(8, 1, 8, 'reviewer_scores', 'Department Chair — Department Chair', 'record', NULL, NULL, NULL, 'Department Chair — Department Chair\nTotal Score: 100.00 / 100\nRationale: 25.00 | Methodology: 30.00 | Budget: 20.00 | Team: 15.00 | Compliance: 10.00\nRecommendation: recommend', '{\"evaluation_id\":29}', 7, '2026-09-18 03:33:53'),
(9, 1, 8, 'reviewer_scores', 'College Dean — Dean', 'record', NULL, NULL, NULL, 'College Dean — Dean\nTotal Score: 100.00 / 100\nRationale: 25.00 | Methodology: 30.00 | Budget: 20.00 | Team: 15.00 | Compliance: 10.00\nRecommendation: recommend', '{\"evaluation_id\":30}', 8, '2026-09-18 03:33:53'),
(10, 1, 8, 'reviewer_scores', 'Research Office — Research Office', 'record', NULL, NULL, NULL, 'Research Office — Research Office\nTotal Score: 100.00 / 100\nRationale: 25.00 | Methodology: 30.00 | Budget: 20.00 | Team: 15.00 | Compliance: 10.00\nRecommendation: recommend', '{\"evaluation_id\":31}', 9, '2026-09-18 03:33:53'),
(11, 1, 8, 'reviewer_scores', 'VPAA — VPAA', 'record', NULL, NULL, NULL, 'VPAA — VPAA\nTotal Score: 100.00 / 100\nRationale: 25.00 | Methodology: 30.00 | Budget: 20.00 | Team: 15.00 | Compliance: 10.00\nRecommendation: recommend', '{\"evaluation_id\":32}', 10, '2026-09-18 03:33:53'),
(12, 1, 8, 'reviewer_scores', 'Finance Office — Finance', 'record', NULL, NULL, NULL, 'Finance Office — Finance\nTotal Score: 100.00 / 100\nRationale: 25.00 | Methodology: 30.00 | Budget: 20.00 | Team: 15.00 | Compliance: 10.00\nRecommendation: recommend', '{\"evaluation_id\":33}', 11, '2026-09-18 03:33:53'),
(13, 1, 8, 'approval_history', 'Academic Adviser', 'record', NULL, NULL, NULL, 'Academic Adviser — adviser\nStatus: Approved\nApprover: User\nActed: 2026-09-18 03:27:16', '{\"step_id\":32}', 12, '2026-09-18 03:33:53'),
(14, 1, 8, 'approval_history', 'Dept. Chair', 'record', NULL, NULL, NULL, 'Dept. Chair — department_chair\nStatus: Approved\nApprover: User\nActed: 2026-09-18 03:28:20', '{\"step_id\":33}', 13, '2026-09-18 03:33:53'),
(15, 1, 8, 'approval_history', 'College Dean', 'record', NULL, NULL, NULL, 'College Dean — dean\nStatus: Approved\nApprover: User\nActed: 2026-09-18 03:28:58', '{\"step_id\":34}', 14, '2026-09-18 03:33:53'),
(16, 1, 8, 'approval_history', 'Research Office', 'record', NULL, NULL, NULL, 'Research Office — research_office\nStatus: Approved\nApprover: User\nActed: 2026-09-18 03:29:36', '{\"step_id\":35}', 15, '2026-09-18 03:33:53'),
(17, 1, 8, 'approval_history', 'VPAA Sign-off', 'record', NULL, NULL, NULL, 'VPAA Sign-off — vpaa\nStatus: Approved\nApprover: User\nActed: 2026-09-18 03:30:02', '{\"step_id\":36}', 16, '2026-09-18 03:33:53'),
(18, 1, 8, 'approval_history', 'Finance Office', 'record', NULL, NULL, NULL, 'Finance Office — finance\nStatus: Approved\nApprover: User\nActed: 2026-09-18 03:30:29', '{\"step_id\":37}', 17, '2026-09-18 03:33:53'),
(19, 1, 8, 'approved_budget', 'Approved Budget & Disbursements', 'record', NULL, NULL, '/sms2_system/modules/crad/pages/budget-disbursement.php?id=8', 'Approved Budget: ₱2,600\n\nFund Releases:\n- Tranche 1: ₱1,300 (Released) Ref: DISB-GR-2026-001-T1\n- Tranche 2: ₱1,300 (Released) Ref: DISB-GR-2026-001-T2', '{\"approved_budget\":2600,\"tranche_count\":2}', 18, '2026-09-18 03:33:53'),
(20, 1, 8, 'project_progress', 'Project Start', 'record', NULL, NULL, NULL, 'Project Start — Completed (100%)\nDue: —\nRemarks: —', '{\"milestone_id\":11}', 19, '2026-09-18 03:33:53'),
(21, 1, 8, 'project_progress', 'Data Gathering', 'file', '88195128443fedc25d40c7292e3d454b.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-milestone-file.php?id=12', 'Data Gathering — Completed (100%)\nDue: 2026-09-18\nRemarks: —', '{\"milestone_id\":12}', 20, '2026-09-18 03:33:53'),
(22, 1, 8, 'project_progress', 'Analysis', 'file', 'fa6cd325759c78fa5ce491fa0ec4203c.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-milestone-file.php?id=13', 'Analysis — Completed (100%)\nDue: 2026-09-18\nRemarks: —', '{\"milestone_id\":13}', 21, '2026-09-18 03:33:53'),
(23, 1, 8, 'project_progress', 'Final Report', 'file', 'b77206d312ef03f325d2d09202f9accb.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-milestone-file.php?id=14', 'Final Report — Completed (100%)\nDue: 2026-09-18\nRemarks: —', '{\"milestone_id\":14}', 22, '2026-09-18 03:33:53'),
(24, 1, 8, 'project_progress', 'Publication', 'file', 'e82b7e87acd88c2621710f5f1c5bc7f5.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-milestone-file.php?id=15', 'Publication — Completed (100%)\nDue: 2026-09-18\nRemarks: —', '{\"milestone_id\":15}', 23, '2026-09-18 03:33:53'),
(25, 1, 8, 'project_progress', 'Evidence — Ai Driven Data Analytics', 'file', '4c8adcb62a85cbc40405bfed404e35fb.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-funded-research-file.php?id=9', '', '{\"evidence_id\":9}', 24, '2026-09-18 03:33:53'),
(26, 1, 8, 'project_progress', 'Evidence — Ai Driven Data Analytics', 'file', '24bbe2cf466da6a02318b6a424f31d2b.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-funded-research-file.php?id=10', '', '{\"evidence_id\":10}', 25, '2026-09-18 03:33:53'),
(27, 1, 8, 'project_progress', 'Evidence — Ai Driven Data Analytics', 'file', '1e75bc27af75c0542193ef27c9f09d4e.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-funded-research-file.php?id=11', '', '{\"evidence_id\":11}', 26, '2026-09-18 03:33:53'),
(28, 1, 8, 'project_progress', 'Evidence — Ai Driven Data Analytics', 'file', '3fc87cd05ec789b7dfe364a1d4d93da0.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-funded-research-file.php?id=12', '', '{\"evidence_id\":12}', 27, '2026-09-18 03:33:53'),
(29, 1, 8, 'final_output', 'Final Research PDF', 'file', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/23adf38391ef58c27b1a14ff52e79263.pdf', 'file-sample_150kB.pdf', '/sms2_system/modules/crad/grant-final-output-file.php?type=final_pdf&submission_id=3', 'asdasdas', '{\"submission_id\":3}', 28, '2026-09-18 03:33:53'),
(30, 1, 8, 'final_output', 'Supporting — OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'file', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output_supporting/12c8f013bad6a439c5187808f9a772fc.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '/sms2_system/modules/crad/grant-final-output-file.php?type=supporting&submission_id=3&index=0', NULL, '{\"submission_id\":3,\"index\":0}', 29, '2026-09-18 03:33:53'),
(31, 1, 8, 'publication', 'Publication Record — PIP-2026-003', 'file', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/23adf38391ef58c27b1a14ff52e79263.pdf', 'file-sample_150kB.pdf', '/sms2_system/modules/crad/grant-final-output-file.php?type=final_pdf&submission_id=3', 'Reference: PIP-2026-003\nTitle: Ai Driven Data Analytics\nAuthors: Student User\nType: Journal\nJournal/Conference: asdas\nDOI: \nURL: http://localhost/sms2_system/modules/crad/pages/publications-ip.php', '{\"repository_reference\":\"PIP-2026-003\"}', 30, '2026-09-18 03:33:53'),
(32, 1, 8, 'ip_documentation', 'IP Documentation', 'record', NULL, NULL, '/sms2_system/modules/crad/pages/publications-ip.php?id=8', 'IP Information: asdasd', '{\"repository_reference\":\"PIP-2026-003\"}', 31, '2026-09-18 03:33:53');

ALTER TABLE `crad_grant_document_repository_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gdri_repository` (`repository_id`),
  ADD KEY `idx_gdri_application` (`grant_application_id`),
  ADD KEY `idx_gdri_category` (`category`);

ALTER TABLE `crad_grant_document_repository_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

DROP TABLE IF EXISTS `crad_grant_final_output_submissions`;
CREATE TABLE `crad_grant_final_output_submissions` (
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

INSERT INTO `crad_grant_final_output_submissions` (`id`, `grant_application_id`, `version_number`, `final_research_title`, `authors`, `abstract`, `publication_type`, `journal_conference`, `doi`, `publication_url`, `ip_information`, `copyright_info`, `patent_info`, `other_ip_info`, `final_pdf_path`, `final_pdf_original`, `supporting_files_json`, `status`, `return_reason`, `verification_notes`, `submitted_by_user_id`, `submitted_by_name`, `submitted_at`, `reviewed_by_user_id`, `reviewed_by_name`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 'ai analysis', 'Student User', 'adasdsada', 'Journal', 'asdsa', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'sadas', NULL, NULL, NULL, 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/20fbebfce55289306d6bfbb63c4e3777.pdf', 'OLIVEROS CV.pdf', '[{\"path\":\"C:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/39c7dccbc6b58439374010ce48077be1.pdf\",\"original_name\":\"Diaz CV.pdf_20260813_105004_0000.pdf\",\"stored_name\":\"39c7dccbc6b58439374010ce48077be1.pdf\"}]', '', NULL, NULL, 9, 'User', '2026-08-31 16:38:45', 3, 'User', '2026-08-31 16:40:51', '2026-08-31 16:38:45', '2026-08-31 16:40:51'),
(2, 7, 1, 'Ai Driven Data Analytics', 'Student User', 'dasdasdas', 'Journal', 'dsadas', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'sadas', 'asd', 'asdas', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/64c266a836d68bc4468d4c06689b6aa8.pdf', 'file-sample_150kB.pdf', '[{\"path\":\"F:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/ae998301f6ce05be4836133a88d19de9.docx\",\"original_name\":\"OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx\",\"stored_name\":\"ae998301f6ce05be4836133a88d19de9.docx\"}]', '', NULL, NULL, 9, 'User', '2026-09-18 03:22:08', 3, 'User', '2026-09-18 03:22:29', '2026-09-18 03:22:08', '2026-09-18 03:22:29'),
(3, 8, 1, 'Ai Driven Data Analytics', 'Student User', 'asdasdas', 'Journal', 'asdas', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'asdasd', NULL, NULL, NULL, 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/23adf38391ef58c27b1a14ff52e79263.pdf', 'file-sample_150kB.pdf', '[{\"path\":\"F:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/12c8f013bad6a439c5187808f9a772fc.docx\",\"original_name\":\"OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx\",\"stored_name\":\"12c8f013bad6a439c5187808f9a772fc.docx\"}]', '', NULL, NULL, 9, 'User', '2026-09-18 03:33:23', 3, 'User', '2026-09-18 03:33:30', '2026-09-18 03:33:23', '2026-09-18 03:33:30');

ALTER TABLE `crad_grant_final_output_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gfos_application` (`grant_application_id`),
  ADD KEY `idx_gfos_status` (`status`),
  ADD KEY `idx_gfos_submitted` (`submitted_at`);

ALTER TABLE `crad_grant_final_output_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

DROP TABLE IF EXISTS `crad_grant_funded_progress_evidence`;
CREATE TABLE `crad_grant_funded_progress_evidence` (
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

INSERT INTO `crad_grant_funded_progress_evidence` (`id`, `grant_application_id`, `milestone_id`, `evidence_title`, `notes`, `file_path`, `file_original`, `submitted_by_user_id`, `submitted_by_name`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 2, 'done', NULL, '08d2da99a1015997444d899fa46a0d1f.pdf', 'OLIVEROS CV.pdf', 9, 'User', 'Submitted', '2026-08-31 16:04:10', '2026-08-31 16:04:10'),
(2, 5, 3, 'done', NULL, '90d52a0e2d826deae5c328f9eac04e51.pdf', 'OLIVEROS CV.pdf', 9, 'User', 'Submitted', '2026-08-31 16:08:47', '2026-08-31 16:08:47'),
(3, 5, 4, 'week 2', NULL, '32d641db454454bc0a71d67ecae8d3d0.pdf', 'OLIVEROS CV.pdf', 9, 'User', 'Submitted', '2026-08-31 16:08:59', '2026-08-31 16:08:59'),
(4, 5, 5, 'done', NULL, 'ebc40321cf8207581e57069fbc7b5b25.pdf', 'OLIVEROS CV.pdf', 9, 'User', 'Submitted', '2026-08-31 16:09:09', '2026-08-31 16:09:09'),
(5, 7, 7, 'Ai Driven Data Analytics', 'sadas', 'f3eaf77345521c15052107fbc28d5ef1.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 9, 'User', 'Submitted', '2026-09-18 03:11:42', '2026-09-18 03:11:42'),
(6, 7, 8, 'Ai Driven Data Analytics', NULL, 'e5d6218be12d9d5f923b6a9ce75ab3c2.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 9, 'User', 'Submitted', '2026-09-18 03:18:26', '2026-09-18 03:18:26'),
(7, 7, 9, 'Ai Driven Data Analytics', NULL, 'c5f8091ccbda57de8f8bb0de0704e5d0.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 9, 'User', 'Submitted', '2026-09-18 03:18:32', '2026-09-18 03:18:32'),
(8, 7, 10, 'Ai Driven Data Analytics', NULL, 'c80415b9f1495a6060967c07a2202302.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 9, 'User', 'Submitted', '2026-09-18 03:18:38', '2026-09-18 03:18:38'),
(9, 8, 12, 'Ai Driven Data Analytics', NULL, '4c8adcb62a85cbc40405bfed404e35fb.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 9, 'User', 'Submitted', '2026-09-18 03:31:46', '2026-09-18 03:31:46'),
(10, 8, 13, 'Ai Driven Data Analytics', NULL, '24bbe2cf466da6a02318b6a424f31d2b.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 9, 'User', 'Submitted', '2026-09-18 03:31:51', '2026-09-18 03:31:51'),
(11, 8, 14, 'Ai Driven Data Analytics', NULL, '1e75bc27af75c0542193ef27c9f09d4e.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 9, 'User', 'Submitted', '2026-09-18 03:31:58', '2026-09-18 03:31:58'),
(12, 8, 15, 'Ai Driven Data Analytics', NULL, '3fc87cd05ec789b7dfe364a1d4d93da0.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 9, 'User', 'Submitted', '2026-09-18 03:32:05', '2026-09-18 03:32:05');

ALTER TABLE `crad_grant_funded_progress_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gfpe_application` (`grant_application_id`),
  ADD KEY `idx_gfpe_milestone` (`milestone_id`),
  ADD KEY `idx_gfpe_created` (`created_at`);

ALTER TABLE `crad_grant_funded_progress_evidence`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

DROP TABLE IF EXISTS `crad_grant_funded_project_milestones`;
CREATE TABLE `crad_grant_funded_project_milestones` (
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

INSERT INTO `crad_grant_funded_project_milestones` (`id`, `grant_application_id`, `milestone_order`, `milestone_name`, `due_date`, `completion_pct`, `status`, `supporting_doc`, `supporting_doc_original`, `remarks`, `updated_by_user_id`, `updated_by_name`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 'Project Start', NULL, 100.00, 'Completed', NULL, NULL, NULL, NULL, NULL, '2026-08-31 14:41:26', '2026-08-31 14:41:26'),
(2, 5, 2, 'Data Gathering', NULL, 100.00, 'Completed', '2a7227d63dad821917cea9db255ebc76.pdf', 'OLIVEROS CV.pdf', 'sdasda', 3, 'User', '2026-08-31 14:41:26', '2026-08-31 16:08:16'),
(3, 5, 3, 'Analysis', '2026-08-31', 100.00, 'Completed', 'e33fc0e55fc37f7549946bd53fce56a3.pdf', 'OLIVEROS CV.pdf', NULL, 3, 'User', '2026-08-31 14:41:26', '2026-08-31 16:09:47'),
(4, 5, 4, 'Final Report', NULL, 100.00, 'Completed', '0e425eef515f88b4aca1c9ffdf95c89d.pdf', 'OLIVEROS CV.pdf', NULL, 3, 'User', '2026-08-31 14:41:26', '2026-08-31 16:10:01'),
(5, 5, 5, 'Publication', NULL, 100.00, 'Completed', 'd73f169b079774a38ea2f4b4a07f8047.pdf', 'OLIVEROS CV.pdf', NULL, 3, 'User', '2026-08-31 14:41:26', '2026-08-31 16:10:32'),
(6, 7, 1, 'Project Start', NULL, 100.00, 'Completed', NULL, NULL, NULL, NULL, NULL, '2026-09-18 03:09:55', '2026-09-18 03:09:55'),
(7, 7, 2, 'Data Gathering', '2026-09-18', 100.00, 'Completed', '8cba92d4732349063daae33789064cde.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', NULL, 3, 'User', '2026-09-18 03:09:55', '2026-09-18 03:19:10'),
(8, 7, 3, 'Analysis', '2026-09-18', 100.00, 'Completed', 'ca8b89bea8ed0e456c65ec3a8a073eda.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'Done', 3, 'User', '2026-09-18 03:09:55', '2026-09-18 03:19:00'),
(9, 7, 4, 'Final Report', '2026-09-18', 100.00, 'Completed', '22896ca35408159a6f404adbf7424f3b.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', NULL, 3, 'User', '2026-09-18 03:09:55', '2026-09-18 03:19:28'),
(10, 7, 5, 'Publication', NULL, 100.00, 'Completed', 'dc256426e828908445f622fe1b21d113.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', NULL, 3, 'User', '2026-09-18 03:09:55', '2026-09-18 03:19:35'),
(11, 8, 1, 'Project Start', NULL, 100.00, 'Completed', NULL, NULL, NULL, NULL, NULL, '2026-09-18 03:30:29', '2026-09-18 03:30:29'),
(12, 8, 2, 'Data Gathering', '2026-09-18', 100.00, 'Completed', '88195128443fedc25d40c7292e3d454b.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', NULL, 3, 'User', '2026-09-18 03:30:29', '2026-09-18 03:32:25'),
(13, 8, 3, 'Analysis', '2026-09-18', 100.00, 'Completed', 'fa6cd325759c78fa5ce491fa0ec4203c.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', NULL, 3, 'User', '2026-09-18 03:30:29', '2026-09-18 03:32:36'),
(14, 8, 4, 'Final Report', '2026-09-18', 100.00, 'Completed', 'b77206d312ef03f325d2d09202f9accb.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', NULL, 3, 'User', '2026-09-18 03:30:29', '2026-09-18 03:32:44'),
(15, 8, 5, 'Publication', '2026-09-18', 100.00, 'Completed', 'e82b7e87acd88c2621710f5f1c5bc7f5.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', NULL, 3, 'User', '2026-09-18 03:30:29', '2026-09-18 03:32:54');

ALTER TABLE `crad_grant_funded_project_milestones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gfpm_app_order` (`grant_application_id`,`milestone_order`),
  ADD KEY `idx_gfpm_application` (`grant_application_id`),
  ADD KEY `idx_gfpm_status` (`status`);

ALTER TABLE `crad_grant_funded_project_milestones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

DROP TABLE IF EXISTS `crad_grant_funding_disbursements`;
CREATE TABLE `crad_grant_funding_disbursements` (
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

INSERT INTO `crad_grant_funding_disbursements` (`id`, `grant_application_id`, `tranche_number`, `tranche_label`, `approved_budget`, `amount_released`, `release_date`, `reference_number`, `status`, `released_by_user_id`, `released_by_name`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 'Tranche 1', 15000.00, 7500.00, '2026-08-31', 'DISB-GR-2026-001-T1', 'Released', 3, 'User', NULL, '2026-08-31 14:35:24', '2026-08-31 14:57:57'),
(2, 5, 2, 'Tranche 2', 15000.00, 7500.00, '2026-08-31', 'DISB-GR-2026-001-T2', 'Released', 3, 'User', NULL, '2026-08-31 14:35:24', '2026-08-31 14:59:27'),
(3, 7, 1, 'Tranche 1', 2500.00, 1250.00, '2026-09-18', 'DISB-GR-2026-001-T1', 'Released', 3, 'User', NULL, '2026-09-18 03:09:55', '2026-09-18 03:10:45'),
(4, 7, 2, 'Tranche 2', 2500.00, 1250.00, '2026-09-18', 'DISB-GR-2026-001-T2', 'Released', 3, 'User', NULL, '2026-09-18 03:09:55', '2026-09-18 03:10:52'),
(5, 8, 1, 'Tranche 1', 2600.00, 1300.00, '2026-09-18', 'DISB-GR-2026-001-T1', 'Released', 3, 'User', NULL, '2026-09-18 03:30:29', '2026-09-18 03:31:22'),
(6, 8, 2, 'Tranche 2', 2600.00, 1300.00, '2026-09-18', 'DISB-GR-2026-001-T2', 'Released', 3, 'User', NULL, '2026-09-18 03:30:29', '2026-09-18 03:31:25');

ALTER TABLE `crad_grant_funding_disbursements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gfd_app_tranche` (`grant_application_id`,`tranche_number`),
  ADD KEY `idx_gfd_application` (`grant_application_id`),
  ADD KEY `idx_gfd_status` (`status`);

ALTER TABLE `crad_grant_funding_disbursements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

DROP TABLE IF EXISTS `crad_grant_proposal_approval_steps`;
CREATE TABLE `crad_grant_proposal_approval_steps` (
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

INSERT INTO `crad_grant_proposal_approval_steps` (`id`, `workflow_id`, `grant_application_id`, `step_key`, `step_order`, `step_label`, `approver_role_key`, `status`, `approver_user_id`, `approver_name`, `remarks`, `signature_data`, `acted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'adviser', 1, 'Academic Adviser', 'adviser', 'Approved', 54, 'User', NULL, 'data:image/png;

ALTER TABLE `crad_grant_proposal_approval_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpas_workflow_step` (`workflow_id`,`step_key`),
  ADD KEY `idx_gpas_application` (`grant_application_id`),
  ADD KEY `idx_gpas_status` (`status`),
  ADD KEY `idx_gpas_role` (`approver_role_key`);

ALTER TABLE `crad_grant_proposal_approval_steps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

DROP TABLE IF EXISTS `crad_grant_proposal_approval_workflows`;
CREATE TABLE `crad_grant_proposal_approval_workflows` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `current_step_key` varchar(40) NOT NULL DEFAULT 'adviser',
  `workflow_status` enum('In Progress','Completed','Returned') NOT NULL DEFAULT 'In Progress',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `crad_grant_proposal_approval_workflows` (`id`, `grant_application_id`, `current_step_key`, `workflow_status`, `started_at`, `completed_at`, `updated_at`) VALUES
(1, 2, 'vpaa', 'Completed', '2026-08-31 08:47:21', '2026-08-31 11:07:08', '2026-08-31 11:07:08'),
(2, 3, 'finance', 'Completed', '2026-08-31 11:44:23', '2026-08-31 12:13:46', '2026-08-31 12:13:46'),
(3, 4, 'finance', 'In Progress', '2026-08-31 13:34:26', NULL, '2026-08-31 13:59:06'),
(4, 5, 'finance', 'Completed', '2026-08-31 14:15:58', '2026-08-31 14:30:05', '2026-08-31 14:30:05'),
(5, 7, 'finance', 'Completed', '2026-09-18 03:04:26', '2026-09-18 03:09:55', '2026-09-18 03:09:55'),
(6, 8, 'finance', 'Completed', '2026-09-18 03:26:45', '2026-09-18 03:30:29', '2026-09-18 03:30:29');

ALTER TABLE `crad_grant_proposal_approval_workflows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpaw_application` (`grant_application_id`),
  ADD KEY `idx_gpaw_status` (`workflow_status`),
  ADD KEY `idx_gpaw_current_step` (`current_step_key`);

ALTER TABLE `crad_grant_proposal_approval_workflows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

DROP TABLE IF EXISTS `crad_grant_proposal_evaluations`;
CREATE TABLE `crad_grant_proposal_evaluations` (
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

INSERT INTO `crad_grant_proposal_evaluations` (`id`, `grant_application_id`, `proposal_version`, `evaluator_user_id`, `evaluator_name`, `evaluation_type`, `score_rationale`, `score_methodology`, `score_budget`, `score_team_capability`, `score_compliance`, `total_score`, `comments`, `recommendations`, `required_corrections`, `recommendation`, `revision_reason`, `submitted_at`, `updated_at`) VALUES
(1, 1, 1, 766, 'Review Committee Member', 'committee', 25.00, 30.00, 19.00, 10.00, 5.00, 89.00, 'asdsa', 'asdas', 'asdas', NULL, NULL, '2026-08-31 07:27:00', '2026-08-31 07:27:00'),
(2, 2, 1, 766, 'Review Committee Member', 'committee', 13.00, 13.00, 13.00, 13.00, 1.00, 53.00, 'sadas', 'asdsa', 'asdas', 'require_revisions', 'sadas', '2026-08-31 08:09:44', '2026-08-31 08:09:44'),
(3, 2, 2, 766, 'Review Committee Member', 'committee', 25.00, 25.00, 20.00, 15.00, 10.00, 95.00, 'sadas', 'asdsa', 'adsaa', 'recommend', NULL, '2026-08-31 08:47:21', '2026-08-31 08:47:21'),
(4, 2, 2, 54, 'Dr. Roberto M. Santos', 'adviser', 20.00, 30.00, 20.00, 10.00, 10.00, 90.00, 'sdasd', 'sdasd', 'dsadsa', NULL, NULL, '2026-08-31 11:03:24', '2026-08-31 11:03:24'),
(5, 3, 1, 766, 'Review Committee Member', 'committee', 10.00, 30.00, 20.00, 15.00, 10.00, 85.00, 'adsa', 'c', 'ads', 'recommend', NULL, '2026-08-31 11:44:23', '2026-08-31 11:44:23'),
(6, 3, 1, 54, 'Dr. Roberto M. Santos', 'adviser', 10.00, 10.00, 10.00, 10.00, 10.00, 50.00, 'sdasa', 'sadsa', 'sdasda', NULL, NULL, '2026-08-31 12:02:01', '2026-08-31 12:02:01'),
(7, 4, 1, 766, 'Review Committee Member', 'committee', 25.00, 25.00, 20.00, 15.00, 10.00, 95.00, 'sA', 'ASDAS', 'SDA', 'recommend', NULL, '2026-08-31 13:34:26', '2026-08-31 13:34:26'),
(8, 4, 1, 54, 'Dr. Roberto M. Santos', 'adviser', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, NULL, NULL, '2026-08-31 13:44:17', '2026-08-31 13:44:17'),
(9, 4, 1, 990, 'Department Chair', 'department_chair', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, NULL, NULL, '2026-08-31 13:56:36', '2026-08-31 13:56:36'),
(10, 4, 1, 8, 'Dean', 'dean', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, NULL, NULL, '2026-08-31 13:57:28', '2026-08-31 13:57:28'),
(11, 4, 1, 991, 'Research Office', 'research_office', 10.00, 30.00, 20.00, 15.00, 10.00, 85.00, NULL, NULL, NULL, NULL, NULL, '2026-08-31 13:58:16', '2026-08-31 13:58:16'),
(12, 4, 1, 992, 'VPAA', 'vpaa', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, NULL, NULL, '2026-08-31 13:59:01', '2026-08-31 13:59:01'),
(13, 5, 1, 766, 'Review Committee Member', 'committee', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-08-31 14:15:58', '2026-08-31 14:15:58'),
(14, 5, 1, 54, 'Dr. Roberto M. Santos', 'adviser', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-08-31 14:16:46', '2026-08-31 14:16:46'),
(15, 5, 1, 990, 'Department Chair', 'department_chair', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-08-31 14:19:03', '2026-08-31 14:19:03'),
(16, 5, 1, 8, 'Dean', 'dean', 25.00, 25.00, 20.00, 15.00, 4.00, 89.00, NULL, NULL, NULL, 'recommend', NULL, '2026-08-31 14:21:15', '2026-08-31 14:21:15'),
(17, 5, 1, 991, 'Research Office', 'research_office', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-08-31 14:22:05', '2026-08-31 14:22:05'),
(18, 5, 1, 992, 'VPAA', 'vpaa', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-08-31 14:22:52', '2026-08-31 14:22:52'),
(19, 5, 1, 4, 'Finance', 'finance', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-08-31 14:29:57', '2026-08-31 14:29:57'),
(20, 7, 1, 766, 'Review Committee Member', 'committee', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-09-18 03:04:26', '2026-09-18 03:04:26'),
(21, 7, 1, 54, 'Dr. Roberto M. Santos', 'adviser', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, 'dasd', 'asd', 'asdas', 'recommend', NULL, '2026-09-18 03:06:00', '2026-09-18 03:06:00'),
(22, 7, 1, 990, 'Department Chair', 'department_chair', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-09-18 03:06:44', '2026-09-18 03:06:44'),
(23, 7, 1, 8, 'Dean', 'dean', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, 'dasd', 'sadas', 'asdas', 'recommend', NULL, '2026-09-18 03:07:20', '2026-09-18 03:07:20'),
(24, 7, 1, 991, 'Research Office', 'research_office', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-09-18 03:08:22', '2026-09-18 03:08:22'),
(25, 7, 1, 992, 'VPAA', 'vpaa', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, 'sadas', 'asd', 'asdas', 'recommend', NULL, '2026-09-18 03:09:22', '2026-09-18 03:09:22'),
(26, 7, 1, 4, 'Finance', 'finance', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-09-18 03:09:51', '2026-09-18 03:09:51'),
(27, 8, 1, 766, 'Review Committee Member', 'committee', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, 'a', NULL, NULL, 'recommend', NULL, '2026-09-18 03:26:45', '2026-09-18 03:26:45'),
(28, 8, 1, 54, 'Dr. Roberto M. Santos', 'adviser', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-09-18 03:27:11', '2026-09-18 03:27:11'),
(29, 8, 1, 990, 'Department Chair', 'department_chair', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-09-18 03:28:14', '2026-09-18 03:28:14'),
(30, 8, 1, 8, 'Dean', 'dean', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-09-18 03:28:53', '2026-09-18 03:28:53'),
(31, 8, 1, 991, 'Research Office', 'research_office', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-09-18 03:29:32', '2026-09-18 03:29:32'),
(32, 8, 1, 992, 'VPAA', 'vpaa', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-09-18 03:29:58', '2026-09-18 03:29:58'),
(33, 8, 1, 4, 'Finance', 'finance', 25.00, 30.00, 20.00, 15.00, 10.00, 100.00, NULL, NULL, NULL, 'recommend', NULL, '2026-09-18 03:30:25', '2026-09-18 03:30:25');

ALTER TABLE `crad_grant_proposal_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpe_app_eval_ver` (`grant_application_id`,`evaluator_user_id`,`proposal_version`),
  ADD KEY `idx_gpe_application` (`grant_application_id`),
  ADD KEY `idx_gpe_evaluator` (`evaluator_user_id`),
  ADD KEY `idx_gpe_submitted` (`submitted_at`);

ALTER TABLE `crad_grant_proposal_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

DROP TABLE IF EXISTS `crad_grant_proposal_notifications`;
CREATE TABLE `crad_grant_proposal_notifications` (
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

INSERT INTO `crad_grant_proposal_notifications` (`id`, `event_key`, `recipient_user_id`, `recipient_role`, `recipient_email`, `grant_application_id`, `type`, `title`, `body`, `url`, `is_read`, `created_at`) VALUES
(111, 'grant-proposal:finance_pending:8:u4', 4, 'finance', '', 8, 'grant_finance_pending', 'Pending Finance Approval', 'GR-2026-001 is pending Finance Office final approval after VPAA sign-off. Review it under Payment Management → Approval Workflows. Title: Ai Driven Data Analytics', '/sms2_system/modules/payment/pages/approval-workflows.php?id=8', 0, '2026-09-18 03:30:02'),
(112, 'grant-proposal:grant_approved_funded:8:v1:u9', 9, 'student', '', 8, 'grant_approved_funded', 'Approved & Funded', 'GR-2026-001 (Ai Driven Data Analytics) is APPROVED & FUNDED after all six institutional sign-offs. Finance Office recorded the final approval.', '/sms2_system/modules/crad/pages/budget-disbursement.php?id=8', 1, '2026-09-18 03:30:29'),
(113, 'grant-proposal:grant_fund_release:8:t1:d5', 9, 'student', '', 8, 'grant_fund_release', 'Fund Tranche Released', 'GR-2026-001 — Tranche 1 released ₱1,300 (Ref: DISB-GR-2026-001-T1). Recorded by User. View Budget & Disbursement for tranche status.', '/sms2_system/modules/crad/pages/budget-disbursement.php?id=8', 1, '2026-09-18 03:31:22'),
(114, 'grant-proposal:grant_fund_release:8:t2:d6', 9, 'student', '', 8, 'grant_fund_release', 'Fund Tranche Released', 'GR-2026-001 — Tranche 2 released ₱1,300 (Ref: DISB-GR-2026-001-T2). Recorded by User. View Budget & Disbursement for tranche status.', '/sms2_system/modules/crad/pages/budget-disbursement.php?id=8', 1, '2026-09-18 03:31:25'),
(115, 'grant-proposal:grant_milestone_update:8:m12:scompleted:p100', 9, 'student', '', 8, 'grant_milestone_update', 'Milestone Updated', 'GR-2026-001 — Data Gathering updated to Completed (100%). Updated by User. View Funded Research for timeline and requirements.', '/sms2_system/modules/crad/pages/funded-research.php?id=8', 1, '2026-09-18 03:32:25'),
(119, 'grant-proposal:grant_milestone_update:8:m13:scompleted:p100', 9, 'student', '', 8, 'grant_milestone_update', 'Milestone Updated', 'GR-2026-001 — Analysis updated to Completed (100%). Updated by User. View Funded Research for timeline and requirements.', '/sms2_system/modules/crad/pages/funded-research.php?id=8', 1, '2026-09-18 03:32:36'),
(121, 'grant-proposal:grant_milestone_update:8:m14:scompleted:p100', 9, 'student', '', 8, 'grant_milestone_update', 'Milestone Updated', 'GR-2026-001 — Final Report updated to Completed (100%). Updated by User. View Funded Research for timeline and requirements.', '/sms2_system/modules/crad/pages/funded-research.php?id=8', 1, '2026-09-18 03:32:44'),
(123, 'grant-proposal:grant_milestone_update:8:m15:scompleted:p100', 9, 'student', '', 8, 'grant_milestone_update', 'Milestone Updated', 'GR-2026-001 — Publication updated to Completed (100%). Updated by User. View Funded Research for timeline and requirements.', '/sms2_system/modules/crad/pages/funded-research.php?id=8', 1, '2026-09-18 03:32:54'),
(125, 'grant-proposal:final_output_submitted:8:u3', 3, 'crad_officer', '', 8, 'grant_final_output_submitted', 'Final Output Submitted', 'GR-2026-001 — final output submitted by User. Title: Ai Driven Data Analytics. Verify under Outputs & Records → Publications & IP.', '/sms2_system/modules/crad/pages/publications-ip.php?id=8', 0, '2026-09-18 03:33:23'),
(126, 'grant-proposal:final_output_verified:8', 9, 'student', '', 8, 'grant_final_output_verified', 'Output Verified', 'GR-2026-001 — your final output has been verified (OUTPUT_VERIFIED) and recorded in the Publications & IP Repository (PIP-2026-003). Verified by User. Proceed to Document Repository for permanent archiving.', '/sms2_system/modules/crad/pages/publications-ip.php?id=8', 1, '2026-09-18 03:33:30');

ALTER TABLE `crad_grant_proposal_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpn_event` (`event_key`),
  ADD KEY `idx_gpn_recipient_user` (`recipient_user_id`),
  ADD KEY `idx_gpn_application` (`grant_application_id`),
  ADD KEY `idx_gpn_created` (`created_at`);

ALTER TABLE `crad_grant_proposal_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

DROP TABLE IF EXISTS `crad_grant_proposal_versions`;
CREATE TABLE `crad_grant_proposal_versions` (
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

INSERT INTO `crad_grant_proposal_versions` (`id`, `grant_application_id`, `version_number`, `version_label`, `proposal_pdf`, `proposal_pdf_original`, `supporting_docs`, `supporting_docs_original`, `ethics_doc`, `ethics_doc_original`, `abstract`, `objectives`, `researcher_notes`, `submitted_by_user_id`, `submitted_at`) VALUES
(1, 1, 1, 'Original', '53ac796ed0ad7af6ca74703f6e4a5b41.pdf', 'OLIVEROS CV.pdf', '72706d8b5bfd82f1135557fa92faf84a.pdf', 'OLIVEROS CV.pdf', '3b1bf340d2db787753a7f77efb0fcdaf.pdf', 'OLIVEROS CV.pdf', 'adsadas', 'adsadas', NULL, 9, '2026-08-31 07:42:38'),
(2, 2, 1, 'Original', 'b594409c59e3ba6839050c9dd63f50a6.pdf', 'OLIVEROS CV.pdf', '6a9719b97b1c60266f543bba2f313c38.pdf', 'OLIVEROS CV.pdf', '757f2e0bf3a62bd565b415570d7df5e8.pdf', 'OLIVEROS CV.pdf', 'dsada', 'asdas', NULL, 9, '2026-08-31 08:46:40'),
(4, 2, 2, 'Revised', 'f3f3ec4b05c99f1fbbe58278c60d4afe.pdf', 'OLIVEROS CV.pdf', 'e411ba015c79a77a27013c4206313257.pdf', 'OLIVEROS CV.pdf', '95ae712a926e7a6e5ceef81ed3ba0f14.pdf', 'OLIVEROS CV.pdf', 'dsada', 'asdas', 'sada', 9, '2026-08-31 08:46:40'),
(5, 3, 1, 'Original', 'bf397c70c1ea835a393e6b25aceac34d.pdf', 'OLIVEROS CV.pdf', '57aae4180acef208c0d86635d413673a.pdf', 'OLIVEROS CV.pdf', '11fdbc98c4857488b1153c1b35f2ae6f.pdf', 'OLIVEROS CV.pdf', 'sadasd', 'asdas', NULL, 9, '2026-08-31 11:43:42'),
(6, 4, 1, 'Original', '554ef2a9aab43ba9626415abd863c06d.pdf', 'OLIVEROS CV.pdf', 'e756dec1b73523d3fadc516b13c8b8df.pdf', 'OLIVEROS CV.pdf', '94c401e14d58b14cf3ea870b076aa98d.pdf', 'OLIVEROS CV.pdf', 'asdas', 'asdsa', NULL, 9, '2026-08-31 13:32:27'),
(7, 5, 1, 'Original', '6d7a59295ee3d7cd325d51cdb880d11c.pdf', 'OLIVEROS CV.pdf', '2e8ab8d2fe5b210a8ebd034054e70bc1.pdf', 'OLIVEROS CV.pdf', '2eb694566c7d8741b6189a97af16dfd8.pdf', 'OLIVEROS CV.pdf', 'dsda', 'sdas', NULL, 9, '2026-08-31 14:11:48'),
(8, 6, 1, 'Original', '0d0b58178c75c12bb34efeb65a6adb2b.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '2eeb2cc07dd3d0ba23696335da3163c1.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '1f0a8200402cb2b861a6cfb50ddd2450.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'ASDAS', 'ADASD', NULL, 9, '2026-09-17 05:16:35'),
(9, 7, 1, 'Original', 'eed4e374729d25be763323873eff3a01.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'cc974173d6973c41db5a981d320ee0ce.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '296531845aad0c2742688fb1c87daf2c.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'asdas', 'asdas', NULL, 9, '2026-09-18 03:03:54'),
(10, 8, 1, 'Original', '764440cbeb67f86f451e1e85d88081ec.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'b579a21b6b589636d32fc62cb87212cb.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'efb7d05d2e9f4954fba0cdabf98259ec.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'sadas', 'asdsa', NULL, 9, '2026-09-18 03:26:24');

ALTER TABLE `crad_grant_proposal_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpv_app_ver` (`grant_application_id`,`version_number`),
  ADD KEY `idx_gpv_application` (`grant_application_id`),
  ADD KEY `idx_gpv_submitted` (`submitted_at`);

ALTER TABLE `crad_grant_proposal_versions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

DROP TABLE IF EXISTS `crad_grant_publications_ip_repository`;
CREATE TABLE `crad_grant_publications_ip_repository` (
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

INSERT INTO `crad_grant_publications_ip_repository` (`id`, `grant_application_id`, `submission_id`, `repository_reference`, `final_research_title`, `authors`, `abstract`, `publication_type`, `journal_conference`, `doi`, `publication_url`, `ip_information`, `copyright_info`, `patent_info`, `other_ip_info`, `final_pdf_path`, `final_pdf_original`, `supporting_files_json`, `verified_by_user_id`, `verified_by_name`, `verified_at`, `created_at`) VALUES
(1, 5, 1, 'PIP-2026-001', 'ai analysis', 'Student User', 'adasdsada', 'Journal', 'asdsa', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'sadas', NULL, NULL, NULL, 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/20fbebfce55289306d6bfbb63c4e3777.pdf', 'OLIVEROS CV.pdf', '[{\"path\":\"C:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/39c7dccbc6b58439374010ce48077be1.pdf\",\"original_name\":\"Diaz CV.pdf_20260813_105004_0000.pdf\",\"stored_name\":\"39c7dccbc6b58439374010ce48077be1.pdf\"}]', 3, 'User', '2026-08-31 16:40:51', '2026-08-31 16:40:51'),
(2, 7, 2, 'PIP-2026-002', 'Ai Driven Data Analytics', 'Student User', 'dasdasdas', 'Journal', 'dsadas', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'sadas', 'asd', 'asdas', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/64c266a836d68bc4468d4c06689b6aa8.pdf', 'file-sample_150kB.pdf', '[{\"path\":\"F:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/ae998301f6ce05be4836133a88d19de9.docx\",\"original_name\":\"OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx\",\"stored_name\":\"ae998301f6ce05be4836133a88d19de9.docx\"}]', 3, 'User', '2026-09-18 03:22:29', '2026-09-18 03:22:29'),
(3, 8, 3, 'PIP-2026-003', 'Ai Driven Data Analytics', 'Student User', 'asdasdas', 'Journal', 'asdas', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'asdasd', NULL, NULL, NULL, 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/23adf38391ef58c27b1a14ff52e79263.pdf', 'file-sample_150kB.pdf', '[{\"path\":\"F:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/12c8f013bad6a439c5187808f9a772fc.docx\",\"original_name\":\"OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx\",\"stored_name\":\"12c8f013bad6a439c5187808f9a772fc.docx\"}]', 3, 'User', '2026-09-18 03:33:30', '2026-09-18 03:33:30');

ALTER TABLE `crad_grant_publications_ip_repository`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpip_application` (`grant_application_id`),
  ADD KEY `idx_gpip_reference` (`repository_reference`),
  ADD KEY `idx_gpip_verified` (`verified_at`);

ALTER TABLE `crad_grant_publications_ip_repository`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

DROP TABLE IF EXISTS `crad_research_clearance_notifications`;
CREATE TABLE `crad_research_clearance_notifications` (
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

INSERT INTO `crad_research_clearance_notifications` (`id`, `event_key`, `recipient_user_id`, `recipient_role`, `recipient_email`, `clearance_id`, `type`, `title`, `body`, `url`, `is_read`, `created_at`) VALUES
(9, 'clearance-payment:1', 1354, 'student', '', 0, 'payment_approved', 'College payment approved', 'Your college payment was approved. The O.R. number and remarks are now on your Research Services Clearance form.', '/sms2_system/modules/student-portal/pages/research-clearance.php', 0, '2026-09-19 12:07:01'),
(15, 'clearance-payment:2', 1354, 'student', '', 0, 'payment_approved', 'Research 2 college payment approved', 'Your Research 2 college payment was approved. The O.R. number and remarks are now on that Research Services Clearance form.', '/sms2_system/modules/student-portal/pages/research-clearance.php', 0, '2026-09-19 13:00:57'),
(20, 'clearance-payment:3', 1354, 'student', '', 0, 'payment_approved', 'Research 1 collage payment approved', 'Your Research 1 collage payment was approved. The O.R. number and remarks are now on that Research Services Clearance form.', '/sms2_system/modules/student-portal/pages/research-clearance.php', 0, '2026-09-19 13:29:42'),
(25, 'clearance-payment:4', 1354, 'student', '', 0, 'payment_approved', 'Research 2 collage payment approved', 'Your Research 2 collage payment was approved. The O.R. number and remarks are now on that Research Services Clearance form.', '/sms2_system/modules/student-portal/pages/research-clearance.php', 0, '2026-09-19 13:36:32');

ALTER TABLE `crad_research_clearance_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rsc_notif_event` (`event_key`),
  ADD KEY `idx_rsc_notif_recipient` (`recipient_user_id`,`recipient_role`);

ALTER TABLE `crad_research_clearance_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

DROP TABLE IF EXISTS `crad_research_clearance_payments`;
CREATE TABLE `crad_research_clearance_payments` (
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

ALTER TABLE `crad_research_clearance_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rcp_group_stage` (`research_group_id`,`research_stage`),
  ADD KEY `idx_rcp_group` (`research_group_id`),
  ADD KEY `idx_rcp_status` (`status`);

ALTER TABLE `crad_research_clearance_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

DROP TABLE IF EXISTS `crad_research_progress_ai_analyses`;
CREATE TABLE `crad_research_progress_ai_analyses` (
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

INSERT INTO `crad_research_progress_ai_analyses` (`id`, `progress_update_id`, `attachment_id`, `milestone_name`, `verdict`, `grammar_quality`, `summary`, `notes_json`, `source`, `analyzed_by`, `analyzed_by_name`, `created_at`) VALUES
(1, 94, 80, 'Chapter 1', 'needs_revision', 'poor', 'Grammar and writing issues were found in Chapter 1. Do not approve until the student revises the notes below.', '[{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"sec\\\"\",\"example\":\"...VITY Name: Abejuela, John Kenneth C. Yr.&Sec. 4th 41005 Date:08\\/28\\/2026 Activity Ove...\"},{\"issue\":\"“Part time” is usually hyphenated in this context.\",\"suggestion\":\"Change to: \\\"Part-Time\\\"\",\"example\":\"...entation requirements. 2-Hour Time Plan Part Time Points I – Hard Case Scenario 60 minute...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Last Name\\\"\",\"example\":\"...ocument. Submit one PDF\\/DOCX file named LastName_FirstName_Week8_OnlineActivity.  PART I...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"First-name\\\"\",\"example\":\"...Submit one PDF\\/DOCX file named LastName_FirstName_Week8_OnlineActivity.  PART I – CASE SC...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Online Activity\\\"\",\"example\":\"...OCX file named LastName_FirstName_Week8_OnlineActivity.  PART I – CASE SCENARIO 60 minutes | 5...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"SEES\\\"\",\"example\":\"... Student Enrollment and Records System (SERS). The implementation team has only 14 d...\"},{\"issue\":\"Consider using an em dash in dialogues and enumerations.\",\"suggestion\":\"Change to: \\\"—\\\"\",\"example\":\"...influence the choice of software media. - Unreliable internet connection – Two ca...\"},{\"issue\":\"Consider using an em dash in dialogues and enumerations.\",\"suggestion\":\"Change to: \\\"—\\\"\",\"example\":\"... not depend only on online downloading. - Large installation package – The softwa...\"},{\"issue\":\"Consider using an em dash in dialogues and enumerations.\",\"suggestion\":\"Change to: \\\"—\\\"\",\"example\":\"...oo long and consume a lot of bandwidth. - 60 target computers – The team needs a ...\"},{\"issue\":\"Consider using an em dash in dialogues and enumerations.\",\"suggestion\":\"Change to: \\\"—\\\"\",\"example\":\"... package on many computers efficiently. - Same software version is required – All...\"},{\"issue\":\"Consider using an em dash in dialogues and enumerations.\",\"suggestion\":\"Change to: \\\"—\\\"\",\"example\":\"...compatibility and maintenance problems. - Security of the installer – The team mu...\"},{\"issue\":\"This sentence does not start with an uppercase letter.\",\"suggestion\":\"Change to: \\\"Issue\\\"\",\"example\":\"... is not automatically the best choice.  issue tracking, reporting, or support. Choosi...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-02 07:55:16'),
(2, 95, 81, 'Chapter 2', 'needs_revision', 'poor', 'Grammar and writing issues were found in Chapter 2. Do not approve until the student revises the notes below.', '[{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"sec\\\"\",\"example\":\"...VITY Name: Abejuela, John Kenneth C. Yr.&Sec. 4th 41005 Date:08\\/28\\/2026 Activity Ove...\"},{\"issue\":\"“Part time” is usually hyphenated in this context.\",\"suggestion\":\"Change to: \\\"Part-Time\\\"\",\"example\":\"...entation requirements. 2-Hour Time Plan Part Time Points I – Hard Case Scenario 60 minute...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Last Name\\\"\",\"example\":\"...ocument. Submit one PDF\\/DOCX file named LastName_FirstName_Week8_OnlineActivity.  PART I...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"First-name\\\"\",\"example\":\"...Submit one PDF\\/DOCX file named LastName_FirstName_Week8_OnlineActivity.  PART I – CASE SC...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Online Activity\\\"\",\"example\":\"...OCX file named LastName_FirstName_Week8_OnlineActivity.  PART I – CASE SCENARIO 60 minutes | 5...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"SEES\\\"\",\"example\":\"... Student Enrollment and Records System (SERS). The implementation team has only 14 d...\"},{\"issue\":\"Consider using an em dash in dialogues and enumerations.\",\"suggestion\":\"Change to: \\\"—\\\"\",\"example\":\"...influence the choice of software media. - Unreliable internet connection – Two ca...\"},{\"issue\":\"Consider using an em dash in dialogues and enumerations.\",\"suggestion\":\"Change to: \\\"—\\\"\",\"example\":\"... not depend only on online downloading. - Large installation package – The softwa...\"},{\"issue\":\"Consider using an em dash in dialogues and enumerations.\",\"suggestion\":\"Change to: \\\"—\\\"\",\"example\":\"...oo long and consume a lot of bandwidth. - 60 target computers – The team needs a ...\"},{\"issue\":\"Consider using an em dash in dialogues and enumerations.\",\"suggestion\":\"Change to: \\\"—\\\"\",\"example\":\"... package on many computers efficiently. - Same software version is required – All...\"},{\"issue\":\"Consider using an em dash in dialogues and enumerations.\",\"suggestion\":\"Change to: \\\"—\\\"\",\"example\":\"...compatibility and maintenance problems. - Security of the installer – The team mu...\"},{\"issue\":\"This sentence does not start with an uppercase letter.\",\"suggestion\":\"Change to: \\\"Issue\\\"\",\"example\":\"... is not automatically the best choice.  issue tracking, reporting, or support. Choosi...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-02 07:57:44'),
(3, 97, 83, 'Chapter 1', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 1. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 00:36:24'),
(4, 98, 84, 'Chapter 2', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 2. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 00:36:40'),
(5, 99, 85, 'Chapter 3', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 3. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 00:36:54'),
(6, 100, 86, 'Chapter 1', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 1. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 09:17:44'),
(7, 101, 87, 'Chapter 2', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 2. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 09:18:05'),
(8, 102, 88, 'Chapter 3', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 3. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 09:18:21'),
(9, 108, 94, 'Chapter 1', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 1. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 11:57:03'),
(10, 109, 95, 'Chapter 2', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 2. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 11:57:36'),
(11, 110, 96, 'Chapter 3', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 3. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 11:57:47'),
(12, 111, 97, 'Chapter 4', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 4. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 12:39:08'),
(13, 112, 98, 'Chapter 5', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 5. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 12:39:19'),
(14, 113, 99, 'System Development', 'needs_revision', 'fair', 'Grammar and writing issues were found in System Development. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 12:39:31'),
(15, 114, 100, 'Testing', 'needs_revision', 'fair', 'Grammar and writing issues were found in Testing. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 12:39:43'),
(16, 115, 101, 'Documentation', 'needs_revision', 'fair', 'Grammar and writing issues were found in Documentation. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 12:40:28'),
(17, 116, 102, 'Chapter 1', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 1. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 13:18:31'),
(18, 117, 103, 'Chapter 2', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 2. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 13:18:42'),
(19, 117, 103, 'Chapter 2', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 2. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 13:19:18'),
(20, 118, 104, 'Chapter 3', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 3. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 13:20:00'),
(21, 119, 105, 'Chapter 4', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 4. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 13:20:16'),
(22, 120, 106, 'Chapter 5', 'needs_revision', 'fair', 'Grammar and writing issues were found in Chapter 5. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 13:20:36'),
(23, 122, 107, 'Testing', 'needs_revision', 'fair', 'Grammar and writing issues were found in Testing. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 13:20:57'),
(24, 123, 108, 'Documentation', 'needs_revision', 'fair', 'Grammar and writing issues were found in Documentation. Do not approve until the student revises the notes below.', '[{\"issue\":\"To join two clauses or introduce examples, consider using an em dash.\",\"suggestion\":\"Change to: \\\" — \\\"\",\"example\":\"COURSE CODE: PRAC101 Section: BSIT - 4105\\/NA COURSE TITLE: OJT \\/ PRACTICUM 1 ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"GENERA\\\"\",\"example\":\"...t Semester, SY 2026-2027 PROFESSOR: MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"BENGUELA\\\"\",\"example\":\"...MR. GELERA, EMERSON D. NAME OF STUDENT: ABEJUELA, JOHN KENNETH C. OJT NARRATIVE REPORT  ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"Fundamental\\\"\",\"example\":\"...OJT NARRATIVE REPORT  TOPIC:Session 7 : Fundumental of Web App Security  SPEAKER: MR. MARC ...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"TORNADO\\\"\",\"example\":\"... of Web App Security  SPEAKER: MR. MARC TONIDO DURATION: 8:00 AM – 9:30 AM SCREENSHOT ...\"},{\"issue\":\"If the text is a generality, ‘of the’ is not necessary.\",\"suggestion\":\"Change to: \\\"some\\\"\",\"example\":\"...ols such as HTTPS. Lastly, we discussed some of the OWASP Top 10 vulnerabilities. These inc...\"},{\"issue\":\"Possible spelling mistake found.\",\"suggestion\":\"Change to: \\\"AXE\\\"\",\"example\":\"...e Data Exposure, XML External Entities (XXE), Broken Access Control, Security Misco...\"}]', 'grammar_engine', 54, 'Dr. Roberto M. Santos', '2026-09-19 13:21:07');

ALTER TABLE `crad_research_progress_ai_analyses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpai_update` (`progress_update_id`,`id`);

ALTER TABLE `crad_research_progress_ai_analyses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

DROP TABLE IF EXISTS `crad_research_services_clearances`;
CREATE TABLE `crad_research_services_clearances` (
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

ALTER TABLE `crad_research_services_clearances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rsc_group_stage` (`research_group_id`,`research_stage`),
  ADD KEY `idx_rsc_status` (`status`),
  ADD KEY `idx_rsc_adviser` (`adviser_user_id`);

ALTER TABLE `crad_research_services_clearances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

SET FOREIGN_KEY_CHECKS = 1;
