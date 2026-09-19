-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 19, 2026 at 07:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `crad_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `chapter_evaluations`
--

CREATE TABLE `chapter_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `submission_id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `evaluator_user_id` int(10) UNSIGNED NOT NULL,
  `evaluator_name` varchar(150) NOT NULL DEFAULT '',
  `content_score` decimal(5,2) NOT NULL,
  `methodology_score` decimal(5,2) NOT NULL,
  `references_score` decimal(5,2) NOT NULL,
  `format_score` decimal(5,2) NOT NULL,
  `grammar_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `content_remarks` text DEFAULT NULL,
  `methodology_remarks` text DEFAULT NULL,
  `references_remarks` text DEFAULT NULL,
  `format_remarks` text DEFAULT NULL,
  `grammar_remarks` text DEFAULT NULL,
  `overall_feedback` text DEFAULT NULL,
  `result` enum('APPROVED','APPROVED WITH REVISION') NOT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `evaluated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chapter_evaluations`
--

INSERT INTO `chapter_evaluations` (`id`, `submission_id`, `research_group_id`, `evaluator_user_id`, `evaluator_name`, `content_score`, `methodology_score`, `references_score`, `format_score`, `grammar_score`, `content_remarks`, `methodology_remarks`, `references_remarks`, `format_remarks`, `grammar_remarks`, `overall_feedback`, `result`, `overall_score`, `evaluated_at`, `created_at`) VALUES
(25, 29, 61, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 100.00, '2026-08-23 04:07:50', '2026-08-23 04:07:50'),
(26, 30, 61, 475, 'Grammarian', 100.00, 100.00, 100.00, 99.97, 0.00, '', '', '', '', NULL, '', 'APPROVED', 99.99, '2026-08-23 04:07:59', '2026-08-23 04:07:59'),
(27, 31, 61, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 100.00, '2026-08-23 04:08:08', '2026-08-23 04:08:08'),
(28, 32, 62, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 100.00, '2026-08-28 15:33:31', '2026-08-28 15:33:31'),
(29, 33, 62, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 100.00, '2026-08-28 15:33:46', '2026-08-28 15:33:46'),
(30, 34, 62, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 100.00, '2026-08-28 15:33:53', '2026-08-28 15:33:53'),
(31, 35, 63, 475, 'Grammarian', 99.00, 99.00, 99.00, 99.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 99.00, '2026-08-28 16:30:28', '2026-08-28 16:30:28'),
(32, 36, 63, 475, 'Grammarian', 99.00, 99.00, 99.00, 98.96, 0.00, '', '', '', '', NULL, '', 'APPROVED', 98.99, '2026-08-28 16:30:40', '2026-08-28 16:30:40'),
(33, 37, 63, 475, 'Grammarian', 99.00, 99.00, 99.00, 99.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 99.00, '2026-08-28 16:30:48', '2026-08-28 16:30:48'),
(34, 38, 64, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 100.00, '2026-08-31 05:32:02', '2026-08-31 05:32:02'),
(35, 39, 64, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 100.00, '2026-08-31 05:32:16', '2026-08-31 05:32:16'),
(36, 40, 64, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 100.00, '2026-08-31 05:32:32', '2026-08-31 05:32:32'),
(37, 41, 65, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 100.00, '2026-08-31 09:42:10', '2026-08-31 09:42:10'),
(38, 42, 65, 475, 'Grammarian', 100.00, 100.00, 99.97, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 99.99, '2026-08-31 09:42:25', '2026-08-31 09:42:25'),
(39, 43, 65, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, 0.00, '', '', '', '', NULL, '', 'APPROVED', 100.00, '2026-08-31 09:42:36', '2026-08-31 09:42:36'),
(40, 44, 69, 475, 'Grammarian', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 00:42:14', '2026-09-19 00:42:14'),
(41, 45, 69, 475, 'Grammarian', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 00:42:26', '2026-09-19 00:42:26'),
(42, 46, 69, 475, 'Grammarian', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 00:42:37', '2026-09-19 00:42:37'),
(43, 47, 70, 475, 'Grammarian', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 09:42:59', '2026-09-19 09:42:59'),
(44, 48, 70, 475, 'Grammarian', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 09:43:11', '2026-09-19 09:43:11'),
(45, 49, 70, 475, 'Grammarian', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 09:43:20', '2026-09-19 09:43:20'),
(46, 50, 71, 475, 'Kyle Kuzma', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 11:58:45', '2026-09-19 11:58:45'),
(47, 51, 71, 475, 'Kyle Kuzma', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 11:58:56', '2026-09-19 11:58:56'),
(48, 52, 71, 475, 'Kyle Kuzma', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 11:59:07', '2026-09-19 11:59:07'),
(49, 53, 72, 475, 'Kyle Kuzma', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 13:22:07', '2026-09-19 13:22:07'),
(50, 54, 72, 475, 'Kyle Kuzma', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 13:22:16', '2026-09-19 13:22:16'),
(51, 55, 72, 475, 'Kyle Kuzma', 20.00, 20.00, 20.00, 20.00, 20.00, '', '', '', '', '', '', 'APPROVED', 100.00, '2026-09-19 13:22:29', '2026-09-19 13:22:29');

-- --------------------------------------------------------

--
-- Table structure for table `chapter_evaluation_notifications`
--

CREATE TABLE `chapter_evaluation_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_key` varchar(120) NOT NULL,
  `recipient_user_id` int(10) UNSIGNED DEFAULT NULL,
  `recipient_role` varchar(60) NOT NULL DEFAULT '',
  `recipient_email` varchar(190) NOT NULL DEFAULT '',
  `submission_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(60) NOT NULL,
  `title` varchar(180) NOT NULL,
  `body` text NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chapter_evaluation_notifications`
--

INSERT INTO `chapter_evaluation_notifications` (`id`, `event_key`, `recipient_user_id`, `recipient_role`, `recipient_email`, `submission_id`, `type`, `title`, `body`, `url`, `is_read`, `created_at`) VALUES
(126, 'evaluator:new:44:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 44, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=44', 0, '2026-09-19 00:37:11'),
(127, 'evaluator:new:45:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 45, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=45', 0, '2026-09-19 00:37:15'),
(128, 'evaluator:new:46:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 46, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=46', 0, '2026-09-19 00:37:19'),
(129, 'student:under_review:44', 1354, 'student', 'kennethabejuela@yahoo.com', 44, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 00:37:32'),
(130, 'student:accepted:44', 1354, 'student', 'kennethabejuela@yahoo.com', 44, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 00:42:14'),
(131, 'student:under_review:45', 1354, 'student', 'kennethabejuela@yahoo.com', 45, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 00:42:19'),
(132, 'student:accepted:45', 1354, 'student', 'kennethabejuela@yahoo.com', 45, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 00:42:26'),
(133, 'student:under_review:46', 1354, 'student', 'kennethabejuela@yahoo.com', 46, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 00:42:31'),
(134, 'student:accepted:46', 1354, 'student', 'kennethabejuela@yahoo.com', 46, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 00:42:37'),
(135, 'evaluator:new:47:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 47, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=47', 0, '2026-09-19 09:18:34'),
(136, 'evaluator:new:48:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 48, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=48', 0, '2026-09-19 09:18:47'),
(137, 'evaluator:new:49:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 49, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=49', 0, '2026-09-19 09:18:51'),
(138, 'student:under_review:47', 1354, 'student', 'kennethabejuela@yahoo.com', 47, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 09:42:51'),
(139, 'student:accepted:47', 1354, 'student', 'kennethabejuela@yahoo.com', 47, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 09:42:59'),
(140, 'student:under_review:48', 1354, 'student', 'kennethabejuela@yahoo.com', 48, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 09:43:05'),
(141, 'student:accepted:48', 1354, 'student', 'kennethabejuela@yahoo.com', 48, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 09:43:11'),
(142, 'student:under_review:49', 1354, 'student', 'kennethabejuela@yahoo.com', 49, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 09:43:15'),
(143, 'student:accepted:49', 1354, 'student', 'kennethabejuela@yahoo.com', 49, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 09:43:20'),
(144, 'evaluator:new:50:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 50, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=50', 0, '2026-09-19 11:58:17'),
(145, 'evaluator:new:51:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 51, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=51', 0, '2026-09-19 11:58:21'),
(146, 'evaluator:new:52:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 52, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=52', 0, '2026-09-19 11:58:25'),
(147, 'student:under_review:50', 1354, 'student', 'kennethabejuela@yahoo.com', 50, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 11:58:39'),
(148, 'student:accepted:50', 1354, 'student', 'kennethabejuela@yahoo.com', 50, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 11:58:45'),
(149, 'student:under_review:51', 1354, 'student', 'kennethabejuela@yahoo.com', 51, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 11:58:50'),
(150, 'student:accepted:51', 1354, 'student', 'kennethabejuela@yahoo.com', 51, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 11:58:56'),
(151, 'student:under_review:52', 1354, 'student', 'kennethabejuela@yahoo.com', 52, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 11:59:01'),
(152, 'student:accepted:52', 1354, 'student', 'kennethabejuela@yahoo.com', 52, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 1, '2026-09-19 11:59:07'),
(153, 'evaluator:new:53:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 53, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=53', 0, '2026-09-19 13:21:47'),
(154, 'evaluator:new:54:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 54, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=54', 0, '2026-09-19 13:21:51'),
(155, 'evaluator:new:55:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 55, 'new_submission', 'New Chapter Submission', 'Group 68 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=55', 0, '2026-09-19 13:21:54'),
(156, 'student:under_review:53', 1354, 'student', 'kennethabejuela@yahoo.com', 53, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 13:21:59'),
(157, 'student:accepted:53', 1354, 'student', 'kennethabejuela@yahoo.com', 53, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 13:22:07'),
(158, 'student:under_review:54', 1354, 'student', 'kennethabejuela@yahoo.com', 54, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 13:22:12'),
(159, 'student:accepted:54', 1354, 'student', 'kennethabejuela@yahoo.com', 54, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 1, '2026-09-19 13:22:16'),
(160, 'student:under_review:55', 1354, 'student', 'kennethabejuela@yahoo.com', 55, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-09-19 13:22:23'),
(161, 'student:accepted:55', 1354, 'student', 'kennethabejuela@yahoo.com', 55, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 1, '2026-09-19 13:22:29'),
(162, 'student:final_manuscript_approved:6', 1354, 'student', 'kennethabejuela@yahoo.com', 6, 'final_manuscript_approved', 'Final Manuscript Approved', 'Your latest final manuscript for your research group has been approved for the next CRAD stage.', '/sms2_system/modules/student-portal/pages/final-manuscript.php', 0, '2026-09-19 13:48:28');

-- --------------------------------------------------------

--
-- Table structure for table `chapter_submissions`
--

CREATE TABLE `chapter_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `research_plan_id` int(10) UNSIGNED DEFAULT NULL,
  `chapter_number` tinyint(3) UNSIGNED NOT NULL,
  `version_number` int(10) UNSIGNED NOT NULL,
  `status` enum('Submitted','Under Review','Needs Revision','Accepted') NOT NULL DEFAULT 'Submitted',
  `submitted_by_user` int(10) UNSIGNED DEFAULT NULL,
  `submitted_by_name` varchar(150) NOT NULL DEFAULT '',
  `submitted_by_email` varchar(190) NOT NULL DEFAULT '',
  `submission_notes` text DEFAULT NULL,
  `original_name` varchar(255) NOT NULL DEFAULT '',
  `stored_subdir` varchar(180) NOT NULL DEFAULT '',
  `stored_name` varchar(120) NOT NULL DEFAULT '',
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `file_mime` varchar(120) NOT NULL DEFAULT '',
  `submission_token` varchar(64) NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `review_started_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chapter_submissions`
--

INSERT INTO `chapter_submissions` (`id`, `research_group_id`, `research_plan_id`, `chapter_number`, `version_number`, `status`, `submitted_by_user`, `submitted_by_name`, `submitted_by_email`, `submission_notes`, `original_name`, `stored_subdir`, `stored_name`, `file_size`, `file_mime`, `submission_token`, `submitted_at`, `review_started_at`, `reviewed_at`, `updated_at`) VALUES
(29, 61, 22, 1, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u9', '2c66fe8dbd047f238219412c3277ea73.docx', 236268, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '60076d809fe68ad03006c78e3eb0cb7e6b713f49381c9be6f5819e132f909dbe', '2026-08-23 03:50:56', '2026-08-23 04:07:45', '2026-08-23 04:07:50', '2026-08-23 04:07:50'),
(30, 61, 22, 2, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u9', '74ec1209c457db33489fc036d354b869.docx', 236268, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'e18982e452e6050dd4e2eb8de6f2c350a519c3c31ce46abb5d68cb1ab693bada', '2026-08-23 04:07:26', '2026-08-23 04:07:54', '2026-08-23 04:07:59', '2026-08-23 04:07:59'),
(31, 61, 22, 3, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u9', 'a23d4492676bde4479582b935f03be6e.docx', 236268, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '795962d634b938f5a1aa88751bbea1f4771fa7a908a6fc42152dd0b4266b55f5', '2026-08-23 04:07:31', '2026-08-23 04:08:03', '2026-08-23 04:08:08', '2026-08-23 04:08:08'),
(32, 62, 23, 1, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u9', '07e9795835e3e49872ba6d848b7685c6.docx', 302605, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '703e3403822246c74c451ce2f12b73566fb90b0a97d19bbea49d5d0fcb9a88cf', '2026-08-28 15:33:05', '2026-08-28 15:33:26', '2026-08-28 15:33:31', '2026-08-28 15:33:31'),
(33, 62, 23, 2, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u9', '550d95ace2cd8302a68470dbf0afb644.docx', 302605, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'f5f836310ed2735baba97ca0205d463ed350c915fd5bb5fec4bf27b05473196e', '2026-08-28 15:33:08', '2026-08-28 15:33:39', '2026-08-28 15:33:46', '2026-08-28 15:33:46'),
(34, 62, 23, 3, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u9', 'd9bd00055fdcadd5610bc655bb2b053e.docx', 302605, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '14f013839c0abbd2062835337e38c78f7e6795a443c8d58a887ecf5bfc85a6de', '2026-08-28 15:33:11', '2026-08-28 15:33:49', '2026-08-28 15:33:53', '2026-08-28 15:33:53'),
(35, 63, 24, 1, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u9', '5413ca29228d7adc3edaba3ee078efdf.docx', 302605, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '7fb495b6b2dabca9d3610f199900ab3827277df616748b6e78f849c6c0c522e3', '2026-08-28 16:30:12', '2026-08-28 16:30:22', '2026-08-28 16:30:28', '2026-08-28 16:30:28'),
(36, 63, 24, 2, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u9', '3e22a7612aa86bb04175936080d13626.docx', 302605, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'bc81662cd28d38c2fe5219572c8d4dd74570be581f7b123396b56a56a2e54c31', '2026-08-28 16:30:15', '2026-08-28 16:30:35', '2026-08-28 16:30:40', '2026-08-28 16:30:40'),
(37, 63, 24, 3, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u9', 'd63ca46384609e00826df060745c3a10.docx', 302605, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '1091e650c3ac7f3e6ed48b4f6dab3290ab309e75293e5842455bb08943720b3c', '2026-08-28 16:30:18', '2026-08-28 16:30:44', '2026-08-28 16:30:48', '2026-08-28 16:30:48'),
(38, 64, 25, 1, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OLIVEROS CV.pdf', 'student_chapters/u9', 'db565eabd531f233133d7398bd3affcf.pdf', 294354, 'application/pdf', 'eb04651e6e4d8811e7596935b11cc7517de971da398a0aaf6d94f089148b33c3', '2026-08-31 05:30:20', '2026-08-31 05:31:36', '2026-08-31 05:32:02', '2026-08-31 05:32:02'),
(39, 64, 25, 2, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OLIVEROS CV.pdf', 'student_chapters/u9', '9df614acae2f05933d0af2a09abed945.pdf', 294354, 'application/pdf', '88b6cc5da546d5b6c56dcb13bb28a25ac7cea4f939ff5f0a64edffe902785150', '2026-08-31 05:30:27', '2026-08-31 05:32:07', '2026-08-31 05:32:16', '2026-08-31 05:32:16'),
(40, 64, 25, 3, 1, 'Accepted', 9, 'Student User', 'kenlangmalakas0308@gmail.com', '', 'OLIVEROS CV.pdf', 'student_chapters/u9', '6b9019e3663ac5e3ba2b152abf5c8857.pdf', 294354, 'application/pdf', '53c9b60411c62a659c6dee58ba830ab87477c4945af47482fadee00c85a8d8a5', '2026-08-31 05:30:32', '2026-08-31 05:32:23', '2026-08-31 05:32:32', '2026-08-31 05:32:32'),
(41, 65, 26, 1, 1, 'Accepted', 9, 'Student User', 's230000001@bestlink.edu.ph', '', 'OLIVEROS CV.pdf', 'student_chapters/u9', '4f694cb5d0416281cbf798b4f2512b24.pdf', 294354, 'application/pdf', '68dd9f53e015b14e08a5eb96c1a5c383f5e02f34082846d940097fec8b97cc20', '2026-08-31 09:41:02', '2026-08-31 09:41:37', '2026-08-31 09:42:10', '2026-08-31 09:42:10'),
(42, 65, 26, 2, 1, 'Accepted', 9, 'Student User', 's230000001@bestlink.edu.ph', '', 'OLIVEROS CV.pdf', 'student_chapters/u9', 'b54dde9265a89caa8fe66d1c22cdbd62.pdf', 294354, 'application/pdf', '30cd83bc72425ee46feb4a5f1bfb0fd0d35db6061321fe3e590b3c296486416d', '2026-08-31 09:41:14', '2026-08-31 09:42:16', '2026-08-31 09:42:25', '2026-08-31 09:42:25'),
(43, 65, 26, 3, 1, 'Accepted', 9, 'Student User', 's230000001@bestlink.edu.ph', '', 'OLIVEROS CV.pdf', 'student_chapters/u9', 'c88aaf9cd7d1786adef383829d96f19d.pdf', 294354, 'application/pdf', '8caa0800c83f9cb0d4d5c6ff1739d304e1b31b7863feff6dcc779c651fded0ae', '2026-08-31 09:41:20', '2026-08-31 09:42:30', '2026-08-31 09:42:37', '2026-08-31 09:42:37'),
(44, 69, 28, 1, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', '56b28e155f9a3a4c1e72597ad6b52db2.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '20334ee122d4015ac8203054c8ed007725d40155cfe78ebfb94f553115292134', '2026-09-19 00:37:11', '2026-09-19 00:37:32', '2026-09-19 00:42:14', '2026-09-19 00:42:14'),
(45, 69, 28, 2, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', '131a36c04df82ccbb1976825eb2dc6a3.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'a147a8e2028c6ac9c1feae9f8a8022709e584e7f9859db17da14ab20d0e0ebf3', '2026-09-19 00:37:15', '2026-09-19 00:42:19', '2026-09-19 00:42:26', '2026-09-19 00:42:26'),
(46, 69, 28, 3, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', '1d458d7d6acbc5a93dc4dd19c0f3ee3e.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '31cec1b217fc9f96d705f85509fb8789c28fe88c68ac5106e1b11da40b380404', '2026-09-19 00:37:19', '2026-09-19 00:42:31', '2026-09-19 00:42:37', '2026-09-19 00:42:37'),
(47, 70, 29, 1, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', 'e277cf90d24f94ec0a17b32c26fbfbe2.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'e6d5987715ff11d2018b715867543e1a034af908937cdb582e14aa5a594a8b8e', '2026-09-19 09:18:34', '2026-09-19 09:42:51', '2026-09-19 09:42:59', '2026-09-19 09:42:59'),
(48, 70, 29, 2, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', '357e2a36bf02114be05cb150267ef15f.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '2d3315118d1f0541944a70b1668f61e639553602770a47669f6ad0228b266872', '2026-09-19 09:18:47', '2026-09-19 09:43:05', '2026-09-19 09:43:11', '2026-09-19 09:43:11'),
(49, 70, 29, 3, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', '189a1558221b1c198c1945596454f432.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '79ff96b60c4933e9195ea9e727020544ead224998f444fd8ff8abe59fdfbf687', '2026-09-19 09:18:51', '2026-09-19 09:43:15', '2026-09-19 09:43:20', '2026-09-19 09:43:20'),
(50, 71, 30, 1, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', 'dc53b752e1de0a668758a3706e3f15d8.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '9c043b327d587420a74be755f959a85a37babfc865cab40238834fc969784334', '2026-09-19 11:58:17', '2026-09-19 11:58:39', '2026-09-19 11:58:45', '2026-09-19 11:58:45'),
(51, 71, 30, 2, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', '4ed09fe0041ef305896cfdb76649502a.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '1d562ad55cd811ef6fd402327731c4d5c5765420eea9764b43eb065b21b34b5c', '2026-09-19 11:58:21', '2026-09-19 11:58:50', '2026-09-19 11:58:56', '2026-09-19 11:58:56'),
(52, 71, 30, 3, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', '54e7490768e67df3943bfd9fe0478f43.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'cff27c57d539428b3e5e85af058d5bd5e00fd6b46855057d3f3d05e4339d9e2c', '2026-09-19 11:58:25', '2026-09-19 11:59:01', '2026-09-19 11:59:07', '2026-09-19 11:59:07'),
(53, 72, 31, 1, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', 'd9c0b84c3b0336c40e364d90a0861c69.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '37fd62076040ab99f1124163b3f38c622fd3cf8f41f8293ed5c457f1ca8594e3', '2026-09-19 13:21:47', '2026-09-19 13:21:59', '2026-09-19 13:22:07', '2026-09-19 13:22:07'),
(54, 72, 31, 2, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', 'ec87a87de2e2d73d3c6d55d213aabe91.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'ee10fca9c6eb53018b66940a22bfb6b9294aa6023152eb4d224a933e9ba1779f', '2026-09-19 13:21:51', '2026-09-19 13:22:12', '2026-09-19 13:22:16', '2026-09-19 13:22:16'),
(55, 72, 31, 3, 1, 'Accepted', 1354, 'John Kenneth Abejuela', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'student_chapters/u1354', '55ff847188162db3f612a2c57a1ef990.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '10d4a45359b4a7da3d9b925bffe697168221405e4938e26c82a3c75d98873803', '2026-09-19 13:21:54', '2026-09-19 13:22:23', '2026-09-19 13:22:29', '2026-09-19 13:22:29');

-- --------------------------------------------------------

--
-- Table structure for table `chapter_submission_history`
--

CREATE TABLE `chapter_submission_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `submission_id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `chapter_number` tinyint(3) UNSIGNED NOT NULL,
  `version_number` int(10) UNSIGNED NOT NULL,
  `status` varchar(40) NOT NULL,
  `event_type` varchar(60) NOT NULL,
  `actor_user_id` int(10) UNSIGNED DEFAULT NULL,
  `actor_name` varchar(150) NOT NULL DEFAULT '',
  `actor_role` varchar(60) NOT NULL DEFAULT '',
  `detail` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chapter_submission_history`
--

INSERT INTO `chapter_submission_history` (`id`, `submission_id`, `research_group_id`, `chapter_number`, `version_number`, `status`, `event_type`, `actor_user_id`, `actor_name`, `actor_role`, `detail`, `created_at`) VALUES
(78, 29, 61, 1, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-23 03:50:56'),
(79, 30, 61, 2, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-23 04:07:26'),
(80, 31, 61, 3, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-23 04:07:31'),
(81, 29, 61, 1, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-23 04:07:45'),
(82, 29, 61, 1, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-23 04:07:50'),
(83, 30, 61, 2, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-23 04:07:54'),
(84, 30, 61, 2, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-23 04:07:59'),
(85, 31, 61, 3, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-23 04:08:03'),
(86, 31, 61, 3, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-23 04:08:08'),
(87, 32, 62, 1, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-28 15:33:05'),
(88, 33, 62, 2, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-28 15:33:08'),
(89, 34, 62, 3, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-28 15:33:11'),
(90, 32, 62, 1, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-28 15:33:26'),
(91, 32, 62, 1, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-28 15:33:31'),
(92, 33, 62, 2, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-28 15:33:39'),
(93, 33, 62, 2, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-28 15:33:46'),
(94, 34, 62, 3, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-28 15:33:49'),
(95, 34, 62, 3, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-28 15:33:53'),
(96, 35, 63, 1, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-28 16:30:12'),
(97, 36, 63, 2, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-28 16:30:15'),
(98, 37, 63, 3, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-28 16:30:18'),
(99, 35, 63, 1, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-28 16:30:22'),
(100, 35, 63, 1, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-28 16:30:28'),
(101, 36, 63, 2, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-28 16:30:35'),
(102, 36, 63, 2, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-28 16:30:40'),
(103, 37, 63, 3, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-28 16:30:44'),
(104, 37, 63, 3, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-28 16:30:48'),
(105, 38, 64, 1, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-31 05:30:20'),
(106, 39, 64, 2, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-31 05:30:27'),
(107, 40, 64, 3, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-31 05:30:32'),
(108, 38, 64, 1, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-31 05:31:36'),
(109, 38, 64, 1, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-31 05:32:02'),
(110, 39, 64, 2, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-31 05:32:07'),
(111, 39, 64, 2, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-31 05:32:16'),
(112, 40, 64, 3, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-31 05:32:23'),
(113, 40, 64, 3, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-31 05:32:32'),
(114, 41, 65, 1, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-31 09:41:02'),
(115, 42, 65, 2, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-31 09:41:14'),
(116, 43, 65, 3, 1, 'Submitted', 'submitted', 9, 'Student User', 'student', '', '2026-08-31 09:41:20'),
(117, 41, 65, 1, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-31 09:41:37'),
(118, 41, 65, 1, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-31 09:42:10'),
(119, 42, 65, 2, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-31 09:42:16'),
(120, 42, 65, 2, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-31 09:42:25'),
(121, 43, 65, 3, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-08-31 09:42:30'),
(122, 43, 65, 3, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-31 09:42:37'),
(123, 44, 69, 1, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 00:37:11'),
(124, 45, 69, 2, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 00:37:15'),
(125, 46, 69, 3, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 00:37:19'),
(126, 44, 69, 1, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-09-19 00:37:32'),
(127, 44, 69, 1, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-09-19 00:42:14'),
(128, 45, 69, 2, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-09-19 00:42:19'),
(129, 45, 69, 2, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-09-19 00:42:26'),
(130, 46, 69, 3, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-09-19 00:42:31'),
(131, 46, 69, 3, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-09-19 00:42:37'),
(132, 47, 70, 1, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 09:18:34'),
(133, 48, 70, 2, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 09:18:47'),
(134, 49, 70, 3, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 09:18:51'),
(135, 47, 70, 1, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-09-19 09:42:51'),
(136, 47, 70, 1, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-09-19 09:42:59'),
(137, 48, 70, 2, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-09-19 09:43:05'),
(138, 48, 70, 2, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-09-19 09:43:11'),
(139, 49, 70, 3, 1, 'Under Review', 'review_started', 475, 'Grammarian', 'grammarian', 'Grammarian started review.', '2026-09-19 09:43:15'),
(140, 49, 70, 3, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-09-19 09:43:20'),
(141, 50, 71, 1, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 11:58:17'),
(142, 51, 71, 2, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 11:58:21'),
(143, 52, 71, 3, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 11:58:25'),
(144, 50, 71, 1, 1, 'Under Review', 'review_started', 475, 'Kyle Kuzma', 'grammarian', 'Grammarian started review.', '2026-09-19 11:58:39'),
(145, 50, 71, 1, 1, 'Accepted', 'evaluated', 475, 'Kyle Kuzma', 'grammarian', 'APPROVED', '2026-09-19 11:58:45'),
(146, 51, 71, 2, 1, 'Under Review', 'review_started', 475, 'Kyle Kuzma', 'grammarian', 'Grammarian started review.', '2026-09-19 11:58:50'),
(147, 51, 71, 2, 1, 'Accepted', 'evaluated', 475, 'Kyle Kuzma', 'grammarian', 'APPROVED', '2026-09-19 11:58:56'),
(148, 52, 71, 3, 1, 'Under Review', 'review_started', 475, 'Kyle Kuzma', 'grammarian', 'Grammarian started review.', '2026-09-19 11:59:01'),
(149, 52, 71, 3, 1, 'Accepted', 'evaluated', 475, 'Kyle Kuzma', 'grammarian', 'APPROVED', '2026-09-19 11:59:07'),
(150, 53, 72, 1, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 13:21:47'),
(151, 54, 72, 2, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 13:21:51'),
(152, 55, 72, 3, 1, 'Submitted', 'submitted', 1354, 'John Kenneth Abejuela', 'student', '', '2026-09-19 13:21:54'),
(153, 53, 72, 1, 1, 'Under Review', 'review_started', 475, 'Kyle Kuzma', 'grammarian', 'Grammarian started review.', '2026-09-19 13:21:59'),
(154, 53, 72, 1, 1, 'Accepted', 'evaluated', 475, 'Kyle Kuzma', 'grammarian', 'APPROVED', '2026-09-19 13:22:07'),
(155, 54, 72, 2, 1, 'Under Review', 'review_started', 475, 'Kyle Kuzma', 'grammarian', 'Grammarian started review.', '2026-09-19 13:22:12'),
(156, 54, 72, 2, 1, 'Accepted', 'evaluated', 475, 'Kyle Kuzma', 'grammarian', 'APPROVED', '2026-09-19 13:22:16'),
(157, 55, 72, 3, 1, 'Under Review', 'review_started', 475, 'Kyle Kuzma', 'grammarian', 'Grammarian started review.', '2026-09-19 13:22:23'),
(158, 55, 72, 3, 1, 'Accepted', 'evaluated', 475, 'Kyle Kuzma', 'grammarian', 'APPROVED', '2026-09-19 13:22:29');

-- --------------------------------------------------------

--
-- Table structure for table `final_defense_evaluations`
--

CREATE TABLE `final_defense_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `defense_schedule_id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `panel_user_id` int(10) UNSIGNED NOT NULL,
  `panel_name` varchar(150) NOT NULL DEFAULT '',
  `content_score` decimal(5,2) NOT NULL,
  `methodology_score` decimal(5,2) NOT NULL,
  `references_score` decimal(5,2) NOT NULL,
  `format_score` decimal(5,2) NOT NULL,
  `defense_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `result` enum('APPROVED','APPROVED WITH REVISION','FAILED') NOT NULL,
  `overall_score` decimal(5,2) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `final_defense_evaluations`
--

INSERT INTO `final_defense_evaluations` (`id`, `defense_schedule_id`, `research_group_id`, `panel_user_id`, `panel_name`, `content_score`, `methodology_score`, `references_score`, `format_score`, `defense_score`, `remarks`, `result`, `overall_score`, `status`, `submitted_at`, `created_at`) VALUES
(1, 32, 61, 491, 'Dr. Jobert Valentino', 90.00, 90.00, 90.00, 90.00, 0.00, 'Final Defense evaluation completed.', 'APPROVED', 90.00, 'Submitted', '2026-08-28 08:31:36', '2026-08-28 08:31:36'),
(2, 32, 61, 492, 'Dr. Jonathan Estrada', 90.00, 88.00, 92.00, 90.00, 0.00, 'Live demo evaluation test', 'APPROVED', 90.00, 'Submitted', '2026-08-28 13:31:08', '2026-08-28 13:31:08'),
(3, 32, 61, 493, 'Dr. Michelle Guevarra', 99.00, 99.00, 99.00, 99.00, 0.00, '99', 'APPROVED', 99.00, 'Submitted', '2026-08-28 13:34:59', '2026-08-28 13:34:59'),
(4, 38, 62, 491, 'Dr. Jobert Valentino', 100.00, 100.00, 100.00, 100.00, 0.00, '', 'APPROVED', 100.00, 'Submitted', '2026-08-28 15:53:12', '2026-08-28 15:53:12'),
(5, 38, 62, 492, 'Dr. Jonathan Estrada', 100.00, 100.00, 100.00, 100.00, 0.00, '', 'APPROVED', 100.00, 'Submitted', '2026-08-28 15:53:31', '2026-08-28 15:53:31'),
(6, 38, 62, 493, 'Dr. Michelle Guevarra', 100.00, 100.00, 100.00, 100.00, 0.00, '', 'APPROVED', 100.00, 'Submitted', '2026-08-28 15:53:47', '2026-08-28 15:53:47'),
(7, 44, 63, 491, 'Dr. Jobert Valentino', 99.00, 99.00, 99.00, 99.00, 0.00, '', 'APPROVED', 99.00, 'Submitted', '2026-08-28 16:37:25', '2026-08-28 16:37:25'),
(8, 44, 63, 492, 'Dr. Jonathan Estrada', 99.00, 99.00, 99.00, 99.00, 0.00, '', 'APPROVED', 99.00, 'Submitted', '2026-08-28 16:38:03', '2026-08-28 16:38:03'),
(9, 44, 63, 493, 'Dr. Michelle Guevarra', 99.00, 99.00, 99.00, 99.00, 0.00, '', 'APPROVED', 99.00, 'Submitted', '2026-08-28 16:38:21', '2026-08-28 16:38:21'),
(10, 50, 64, 491, 'Dr. Jobert Valentino', 99.99, 100.00, 100.00, 100.00, 0.00, '', 'APPROVED', 100.00, 'Submitted', '2026-08-31 06:28:01', '2026-08-31 06:28:01'),
(11, 50, 64, 492, 'Dr. Jonathan Estrada', 100.00, 100.00, 100.00, 100.00, 0.00, '', 'APPROVED', 100.00, 'Submitted', '2026-08-31 06:28:40', '2026-08-31 06:28:40'),
(12, 50, 64, 493, 'Dr. Michelle Guevarra', 99.99, 100.00, 100.00, 100.00, 0.00, '100', 'APPROVED', 100.00, 'Submitted', '2026-08-31 06:31:46', '2026-08-31 06:31:46'),
(13, 77, 72, 990, 'Dr. Joseph Alcantara', 20.00, 20.00, 20.00, 20.00, 20.00, '', 'APPROVED', 100.00, 'Submitted', '2026-09-19 13:47:20', '2026-09-19 13:47:20'),
(14, 77, 72, 491, 'Dr. Jobert Valentino', 20.00, 20.00, 20.00, 20.00, 20.00, '', 'APPROVED', 100.00, 'Submitted', '2026-09-19 13:47:45', '2026-09-19 13:47:45'),
(15, 77, 72, 492, 'Dr. Jonathan Estrada', 20.00, 20.00, 20.00, 20.00, 20.00, '', 'APPROVED', 100.00, 'Submitted', '2026-09-19 13:48:09', '2026-09-19 13:48:09');

-- --------------------------------------------------------

--
-- Table structure for table `final_defense_recommendations`
--

CREATE TABLE `final_defense_recommendations` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `group_number` varchar(40) NOT NULL DEFAULT '',
  `adviser_user_id` int(10) UNSIGNED DEFAULT NULL,
  `adviser_name` varchar(150) NOT NULL DEFAULT '',
  `status` enum('Not Ready','Recommended') NOT NULL DEFAULT 'Not Ready',
  `remarks` text DEFAULT NULL,
  `recommended_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `final_defense_recommendations`
--

INSERT INTO `final_defense_recommendations` (`id`, `research_group_id`, `group_number`, `adviser_user_id`, `adviser_name`, `status`, `remarks`, `recommended_at`, `created_at`, `updated_at`) VALUES
(1, 61, 'RG-2026-001', 54, 'Dr. Roberto M. Santos', 'Recommended', '', '2026-08-28 15:41:00', '2026-08-28 08:00:15', '2026-08-28 15:41:00'),
(2, 62, 'RG-2026-001', 54, 'Dr. Roberto M. Santos', 'Recommended', '', '2026-08-28 15:45:21', '2026-08-28 15:44:31', '2026-08-28 15:45:21'),
(4, 63, 'RG-2026-001', 54, 'Dr. Roberto M. Santos', 'Recommended', '', '2026-08-28 16:34:27', '2026-08-28 16:34:27', '2026-08-28 16:34:27'),
(5, 64, 'RG-2026-001', 54, 'Dr. Roberto M. Santos', 'Recommended', '', '2026-08-31 06:15:48', '2026-08-31 06:15:48', '2026-08-31 06:15:48'),
(6, 71, 'RG-2026-068', 54, 'Dr. Roberto M. Santos', 'Recommended', 'done', '2026-09-19 12:40:40', '2026-09-19 12:40:40', '2026-09-19 12:40:40'),
(7, 72, 'RG-2026-068', 54, 'Dr. Roberto M. Santos', 'Recommended', '', '2026-09-19 13:34:18', '2026-09-19 13:34:18', '2026-09-19 13:34:18');

-- --------------------------------------------------------

--
-- Table structure for table `final_manuscript_approvals`
--

CREATE TABLE `final_manuscript_approvals` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `defense_schedule_id` int(10) UNSIGNED DEFAULT NULL,
  `approved_by_user` int(10) UNSIGNED DEFAULT NULL,
  `approved_by_name` varchar(150) NOT NULL DEFAULT '',
  `status` enum('Pending','Approved','Returned') NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `final_manuscript_approvals`
--

INSERT INTO `final_manuscript_approvals` (`id`, `research_group_id`, `defense_schedule_id`, `approved_by_user`, `approved_by_name`, `status`, `remarks`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 61, 32, 3, '', 'Approved', 'Final Defense evaluations completed and approved.', '2026-08-28 13:41:26', '2026-08-28 13:41:26', '2026-08-28 13:41:26'),
(2, 62, 38, 3, '', 'Approved', 'done', '2026-08-28 15:55:07', '2026-08-28 15:55:07', '2026-08-28 15:55:07'),
(3, 63, 44, 3, '', 'Approved', 'done', '2026-08-28 16:39:02', '2026-08-28 16:39:02', '2026-08-28 16:39:02'),
(4, 64, 50, 3, '', 'Approved', '100', '2026-08-31 06:32:22', '2026-08-31 06:32:22', '2026-08-31 06:32:22'),
(5, 72, 77, 3, '', 'Approved', 'done', '2026-09-19 13:48:28', '2026-09-19 13:48:28', '2026-09-19 13:48:28');

-- --------------------------------------------------------

--
-- Table structure for table `grant_applications`
--

CREATE TABLE `grant_applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `proposal_reference` varchar(30) DEFAULT NULL COMMENT 'Stable proposal ID e.g. GR-2026-001',
  `current_version` int(10) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Active proposal document version',
  `grant_opportunity_id` int(10) UNSIGNED NOT NULL COMMENT 'FK → grant_opportunities.id',
  `research_group_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK → research_groups.id (nullable for non-capstone applicants)',
  `group_number` varchar(30) DEFAULT NULL,
  `research_title` varchar(500) DEFAULT NULL,
  `applicant_name` varchar(200) NOT NULL DEFAULT '',
  `college_dept` varchar(200) DEFAULT NULL COMMENT 'Academic college / department of the lead proponent',
  `requested_budget` decimal(14,2) DEFAULT NULL COMMENT 'Budget requested by the proponent; must not exceed grant max_funding_cap',
  `approved_budget` decimal(14,2) DEFAULT NULL,
  `abstract` text DEFAULT NULL COMMENT 'Executive abstract of the research proposal',
  `objectives` text DEFAULT NULL COMMENT 'Research objectives',
  `proposal_pdf` varchar(255) DEFAULT NULL COMMENT 'Stored filename of the uploaded proposal PDF/DOC under storage/uploads/grant_proposals/',
  `proposal_pdf_original` varchar(300) DEFAULT NULL COMMENT 'Original filename of the uploaded proposal document',
  `supporting_docs` varchar(255) DEFAULT NULL COMMENT 'Stored filename of optional supporting documents',
  `supporting_docs_original` varchar(300) DEFAULT NULL COMMENT 'Original filename of optional supporting documents',
  `ethics_doc` varchar(255) DEFAULT NULL COMMENT 'Stored filename of optional ethics clearance document',
  `ethics_doc_original` varchar(300) DEFAULT NULL COMMENT 'Original filename of optional ethics clearance document',
  `applicant_user_id` int(10) UNSIGNED DEFAULT NULL,
  `application_notes` text DEFAULT NULL,
  `status` enum('Submitted','Under Review','Approved','Approved & Funded','Final Output Submitted','OUTPUT_VERIFIED','Archived','Denied','Withdrawn','Rejected','Revision Required','Resubmitted') NOT NULL DEFAULT 'Submitted',
  `submission_token` varchar(64) DEFAULT NULL COMMENT 'One-time token for duplicate-submission prevention',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grant_document_repository`
--

CREATE TABLE `grant_document_repository` (
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

--
-- Dumping data for table `grant_document_repository`
--

INSERT INTO `grant_document_repository` (`id`, `grant_application_id`, `archive_reference`, `status`, `item_count`, `archived_by_user_id`, `archived_by_name`, `archived_at`, `updated_at`) VALUES
(1, 8, 'DAR-2026-001', 'ARCHIVED', 32, 3, 'User', '2026-09-18 03:33:53', '2026-09-18 03:33:53');

-- --------------------------------------------------------

--
-- Table structure for table `grant_document_repository_items`
--

CREATE TABLE `grant_document_repository_items` (
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

--
-- Dumping data for table `grant_document_repository_items`
--

INSERT INTO `grant_document_repository_items` (`id`, `repository_id`, `grant_application_id`, `category`, `item_label`, `item_type`, `file_path`, `file_original`, `download_url`, `summary_text`, `metadata_json`, `sort_order`, `created_at`) VALUES
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

-- --------------------------------------------------------

--
-- Table structure for table `grant_final_output_submissions`
--

CREATE TABLE `grant_final_output_submissions` (
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

--
-- Dumping data for table `grant_final_output_submissions`
--

INSERT INTO `grant_final_output_submissions` (`id`, `grant_application_id`, `version_number`, `final_research_title`, `authors`, `abstract`, `publication_type`, `journal_conference`, `doi`, `publication_url`, `ip_information`, `copyright_info`, `patent_info`, `other_ip_info`, `final_pdf_path`, `final_pdf_original`, `supporting_files_json`, `status`, `return_reason`, `verification_notes`, `submitted_by_user_id`, `submitted_by_name`, `submitted_at`, `reviewed_by_user_id`, `reviewed_by_name`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 'ai analysis', 'Student User', 'adasdsada', 'Journal', 'asdsa', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'sadas', NULL, NULL, NULL, 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/20fbebfce55289306d6bfbb63c4e3777.pdf', 'OLIVEROS CV.pdf', '[{\"path\":\"C:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/39c7dccbc6b58439374010ce48077be1.pdf\",\"original_name\":\"Diaz CV.pdf_20260813_105004_0000.pdf\",\"stored_name\":\"39c7dccbc6b58439374010ce48077be1.pdf\"}]', '', NULL, NULL, 9, 'User', '2026-08-31 16:38:45', 3, 'User', '2026-08-31 16:40:51', '2026-08-31 16:38:45', '2026-08-31 16:40:51'),
(2, 7, 1, 'Ai Driven Data Analytics', 'Student User', 'dasdasdas', 'Journal', 'dsadas', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'sadas', 'asd', 'asdas', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/64c266a836d68bc4468d4c06689b6aa8.pdf', 'file-sample_150kB.pdf', '[{\"path\":\"F:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/ae998301f6ce05be4836133a88d19de9.docx\",\"original_name\":\"OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx\",\"stored_name\":\"ae998301f6ce05be4836133a88d19de9.docx\"}]', '', NULL, NULL, 9, 'User', '2026-09-18 03:22:08', 3, 'User', '2026-09-18 03:22:29', '2026-09-18 03:22:08', '2026-09-18 03:22:29'),
(3, 8, 1, 'Ai Driven Data Analytics', 'Student User', 'asdasdas', 'Journal', 'asdas', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'asdasd', NULL, NULL, NULL, 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/23adf38391ef58c27b1a14ff52e79263.pdf', 'file-sample_150kB.pdf', '[{\"path\":\"F:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/12c8f013bad6a439c5187808f9a772fc.docx\",\"original_name\":\"OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx\",\"stored_name\":\"12c8f013bad6a439c5187808f9a772fc.docx\"}]', '', NULL, NULL, 9, 'User', '2026-09-18 03:33:23', 3, 'User', '2026-09-18 03:33:30', '2026-09-18 03:33:23', '2026-09-18 03:33:30');

-- --------------------------------------------------------

--
-- Table structure for table `grant_funded_progress_evidence`
--

CREATE TABLE `grant_funded_progress_evidence` (
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

--
-- Dumping data for table `grant_funded_progress_evidence`
--

INSERT INTO `grant_funded_progress_evidence` (`id`, `grant_application_id`, `milestone_id`, `evidence_title`, `notes`, `file_path`, `file_original`, `submitted_by_user_id`, `submitted_by_name`, `status`, `created_at`, `updated_at`) VALUES
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

-- --------------------------------------------------------

--
-- Table structure for table `grant_funded_project_milestones`
--

CREATE TABLE `grant_funded_project_milestones` (
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

--
-- Dumping data for table `grant_funded_project_milestones`
--

INSERT INTO `grant_funded_project_milestones` (`id`, `grant_application_id`, `milestone_order`, `milestone_name`, `due_date`, `completion_pct`, `status`, `supporting_doc`, `supporting_doc_original`, `remarks`, `updated_by_user_id`, `updated_by_name`, `created_at`, `updated_at`) VALUES
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

-- --------------------------------------------------------

--
-- Table structure for table `grant_funding_disbursements`
--

CREATE TABLE `grant_funding_disbursements` (
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

--
-- Dumping data for table `grant_funding_disbursements`
--

INSERT INTO `grant_funding_disbursements` (`id`, `grant_application_id`, `tranche_number`, `tranche_label`, `approved_budget`, `amount_released`, `release_date`, `reference_number`, `status`, `released_by_user_id`, `released_by_name`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 'Tranche 1', 15000.00, 7500.00, '2026-08-31', 'DISB-GR-2026-001-T1', 'Released', 3, 'User', NULL, '2026-08-31 14:35:24', '2026-08-31 14:57:57'),
(2, 5, 2, 'Tranche 2', 15000.00, 7500.00, '2026-08-31', 'DISB-GR-2026-001-T2', 'Released', 3, 'User', NULL, '2026-08-31 14:35:24', '2026-08-31 14:59:27'),
(3, 7, 1, 'Tranche 1', 2500.00, 1250.00, '2026-09-18', 'DISB-GR-2026-001-T1', 'Released', 3, 'User', NULL, '2026-09-18 03:09:55', '2026-09-18 03:10:45'),
(4, 7, 2, 'Tranche 2', 2500.00, 1250.00, '2026-09-18', 'DISB-GR-2026-001-T2', 'Released', 3, 'User', NULL, '2026-09-18 03:09:55', '2026-09-18 03:10:52'),
(5, 8, 1, 'Tranche 1', 2600.00, 1300.00, '2026-09-18', 'DISB-GR-2026-001-T1', 'Released', 3, 'User', NULL, '2026-09-18 03:30:29', '2026-09-18 03:31:22'),
(6, 8, 2, 'Tranche 2', 2600.00, 1300.00, '2026-09-18', 'DISB-GR-2026-001-T2', 'Released', 3, 'User', NULL, '2026-09-18 03:30:29', '2026-09-18 03:31:25');

-- --------------------------------------------------------

--
-- Table structure for table `grant_opportunities`
--

CREATE TABLE `grant_opportunities` (
  `id` int(10) UNSIGNED NOT NULL,
  `funding_title` varchar(300) NOT NULL,
  `max_funding_cap` decimal(14,2) NOT NULL DEFAULT 0.00,
  `application_deadline` date NOT NULL,
  `eligibility` varchar(100) NOT NULL DEFAULT 'Open',
  `college_program` varchar(200) DEFAULT NULL COMMENT 'Populated when eligibility = Specific College/Program',
  `status` enum('Open for Application','Closed','Expired') NOT NULL DEFAULT 'Open for Application',
  `created_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  `created_by_name` varchar(150) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grant_proposal_approval_steps`
--

CREATE TABLE `grant_proposal_approval_steps` (
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

--
-- Dumping data for table `grant_proposal_approval_steps`
--

INSERT INTO `grant_proposal_approval_steps` (`id`, `workflow_id`, `grant_application_id`, `step_key`, `step_order`, `step_label`, `approver_role_key`, `status`, `approver_user_id`, `approver_name`, `remarks`, `signature_data`, `acted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'adviser', 1, 'Academic Adviser', 'adviser', 'Approved', 54, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 11:03:47', '2026-08-31 08:47:21', '2026-08-31 11:03:47'),
(2, 1, 2, 'department_chair', 2, 'Dept. Chair', 'research_coordinator', 'Approved', 990, 'User', NULL, 'data:image/png;base64,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sWqfl+spRujK11PbIiACIiAC2yewhmFuv1J1LdENzE0tm/pr2bSu7VC9REAEREAEzk9Ahnl+ZjpDBERABESggwRkmA3vdFVfBERABERgOwRkmNvhrFJEQAREQAQaTkCG2fAOVPXrTEB1EwERaBMBGWabelNtEQEREAERqIyADLMytMpYBESgzgRUNxE4LwEZ5nmJ6XgREAEREIFOEpBhdrLb1WgREAERqDOBetZNhlnPflGtREAEREAEakZAhlmzDlF1REAEREAE6klAhnnSL3oWAREQAREQgVMJyDBPxaMXRUAEREAEROCEgAzzhIOe60xAdRMBERCBGhCQYdagE1QFERABERCB+hOQYda/j1RDEagzAdVNBDpDQIbZma5WQ0VABERABC5CQIZ5EXo6VwREQATqTEB1K5WADLNUnMpMBERABESgrQRkmG3tWbVLBERABESgVAIlG2apdVNmIiACIiACIlAbAjLM2nSFKiICIiACIlBnAjLMOvdOyXVTdiIgAiIgApsTkGFuzk5nioAIiIAIdIiADLNDna2m1pmA6iYCIlB3AjLMuveQ6icCIiACIlALAjLMWnSDKiECIlBnAqqbCEBAhgkFSQREQAREQATOICDDPAOQXhYBERABEagzge3VTYa5PdYqSQREQAREoMEEZJgN7jxVXQREQAREYHsEvgYAAP//hLmJggAAAAZJREFUAwDYHo5F4Ix0uAAAAABJRU5ErkJggg==', '2026-08-31 11:04:41', '2026-08-31 08:47:21', '2026-08-31 11:04:41'),
(3, 1, 2, 'dean', 3, 'College Dean', 'hr', 'Approved', 8, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 11:05:45', '2026-08-31 08:47:21', '2026-08-31 11:05:45'),
(4, 1, 2, 'research_office', 4, 'Research Office', 'crad_officer', 'Approved', 991, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 11:06:34', '2026-08-31 08:47:21', '2026-08-31 11:06:34');
INSERT INTO `grant_proposal_approval_steps` (`id`, `workflow_id`, `grant_application_id`, `step_key`, `step_order`, `step_label`, `approver_role_key`, `status`, `approver_user_id`, `approver_name`, `remarks`, `signature_data`, `acted_at`, `created_at`, `updated_at`) VALUES
(5, 1, 2, 'vpaa', 5, 'VPAA Sign-off', 'qa', 'Approved', 992, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 11:07:08', '2026-08-31 08:47:21', '2026-09-19 13:48:19'),
(7, 1, 2, 'finance', 6, 'Finance Office', 'finance', 'Approved', NULL, NULL, NULL, NULL, NULL, '2026-08-31 11:35:22', '2026-09-19 13:48:19'),
(8, 2, 3, 'adviser', 1, 'Academic Adviser', 'adviser', 'Approved', 54, 'User', NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAcwAAACMCAYAAADx9JleAAAMyklEQVR4Aezdz3IcVxUH4G7FgQoQy6kiUiorigUVacuWRfIG8BxkAU9AeAJYkOeAN4ireAMWUhZQZEc0WcRyYggkVjNH8pXGsmY0I3VP39v3m/JRzz91n/sdV/2qeyR7p3EjQKBKgUfvvPd+1MO9w4929w8+uajDLp6rEsSiCdwiIDBvAfIygdIFIgCjrgdj1+18EtW2ze+apn3/orrHTz7/9HHjRoDAKwIC8xWSjJ7QCoENBCIUo9YLxthxNw/G7nHXNb9v27MPok5Pjj+IVxQBAq8KCMxXTTxDIGuBCMWo+wXjURvhGPV0dvRRnFVGZb1wzREYWUBgjjwAhy9WYCuNRzBGXXy+GJ8zHnZxGTXq5Uup0c5NZ4yCMWQUgT4EBGYfivZBoEeBR/sHv9ndO/xyd/8qHJvLzxibF7eLcIzLqFGnJ4LxBYwNgcEEBOZgtHZMYH2Bl0Kyaf/QtM2j5vx2UzBehWNcRo06f6svVwLuERhAQGAOgGqXBNYVSEHZLYZk1zxpm+63i2eNEYpRjRsBAqMJCMzR6B24VoEUkueXXFNQzkOyabrH50E5O3rrycnxH2v1se7JCxS7QIFZ7Og0XppACsouhWQsYB6UKSRPT44/EJSBogjkKSAw85yLriYikELS2eREBmoZVQtUEZhVT9jiRxFIQelschR+ByUwiIDAHITVTmsUSCHpbLLG6VtzDQICs4YpZ73G8ptLQelssvxZWgGBVQICc5WO1wgsEUgh6WxyCZCnCUxQQGBOcKiWNJxACspaziaHk7RnAuUJCMzyZqbjLQukkHQ2uWV4hyOQmYDAzGwg2slHIAWls8l8ZqKTRQH3ty0gMLct7nhZC6SQdDaZ9Zg0R2AUAYE5CruD5iaQgtLZZG6T0Q+BfAQ2Ccx8utYJgR4EUkg6m+wB0y4IVCAgMCsYsiW+LJCC0tnkyy4eESCwWkBgrvYp51WdrhR4tHfwp929w2+dTa5k8iIBAisEBOYKHC+VL5CCsmvbXzdt8yBW1DXNN/6HkJBQBAhsIiAwN9Hy3qIE4oxyMSibrvmu7bqPn54cvbHl/0arKDfNEiBws4DAvNnFswUL7O4f/DMuvaYzyhSUp7Oj15/Mjj8seGlaJ0BgRAGBOSK+Q/crcBmUTfuT8z2/OKMUlOcaviwT8DyBNQUE5ppQ3pavgKDMdzY6IzAlAYE5pWlWthZBWdnALbdGgazWLDCzGodm1hEQlOsoeQ8BAn0LCMy+Re1vMAFBORitHRMgsIaAwLyG5GF+AoIyv5noiECNAgKzxqkXsmZBWcigtEmgEgGBWcmgS1rm8qD0e5QlzVGvBKYmIDCnNtGC15P+GbvG71E2bgQI5CcgMPObSXUdpaC8/Gfs/IMDRf4d0DSBqQsIzKlPOOP1CcqMh6M1AgReERCYr5B4YmgBQTm0sP0TuC7gcR8CArMPRftYS0BQrsXkTQQIZCogMDMdzJTaEpRTmqa1EKhXYKjArFfUyi8FBOUlhTsECExAQGBOYIi5LUFQ5jYR/RAg0IeAwOxDsbR9DNTv7t7Bn3f3D8/8eshAwHZLgMCoAgJzVP7pHHx37/DLpm1/OV9RO6+u7bqPT2f+ZZ65hT8ECExEQGBOZJBjLiPOKpu2edTErWuenJ4c7TyZHX8YD9XGAr6BAIFMBQRmpoMpoa0Xl2C7ea9xVtk0XfeX+VnlW/PH/hAgQGByAgJzciPdzoIWLsHGAbuLsDz+VTxQBCYrYGFVCwjMqse/+eJfnFWeXb8EezoTlptr+g4CBEoSEJglTWvkXhfOKuMS7IuzyiOXYEeei8MTIHAuMPgXgTk4cfkHcFZZ/gytgACB+wsIzPsbTnoPEZbN1a+L+MGeSU/b4ggQWCUgMFfp3PLa1F9euAQbS+1OT45an1UGhSJAoEYBgVnj1NdY8+7+Yddc+93KNb7NWwgQIDBZAYE52dHebWEP9w7+eh6WF99e8A/2XCzAVwIECPQlIDD7kpzIftq2/UVayvwS7I5LsEnDlgCB2gUEZu1/AxbW/3Dv8Nv0cB6W8asj6aEtgV4F7IxAiQICs8SpDdDz7t7hP9q2eRC77rrmu9gqAgQIELgSEJhXFnXfa5ufBkDXdWdPZ0evx31FgECNAta8TEBgLpOp9Pmns+PXKl26ZRMgQGClgMBcyeNFAgQIECBwIZBDYF504msWAgu/UpJFP5ogQIBALgICM5dJjNxH13WnqYUIzYd7B2fpsS0BAgQINI3A9LfgXGD+2eWjrmuenT+Yf2nntwjOZn7fHwIECBAQmP4OLAg8nR39KH7/cn622aWn39w7+G+6b0uAAIGaBZxh1jz9JWt/OjveOeu6/8XLO237vdiqLAU0RYDAFgUE5haxSzrUV7Pj76d+XZpNErYECNQsIDBrnv4ta4/Ls+ktEZp+EChp2BJYQ8BbJicgMCc30n4XtBia7fwmOPv1tTcCBMoREJjlzGq0TiM0F38QaJ6breAcbRwOTIDA/QXutAeBeSe2+r4pfhBIcNY3dysmQOBKQGBeWbi3hsBtwelzzjUQvYUAgSIFBOaWxja1wywLznS5Nl2yffPt955Pbe3WQ4BAnQICs86597bqFJxn81t8zhmVdh7huTO/pfB09plkbAkQKFFAYJY4tQx7/uqLT1+L8IxKn3VeD88I0DzDM0NQLREgkJ2AwMxuJNNoKIIzKsJzfvJ5JjynMVerIFCzgMCsefpbWns6+4zwjBKeW4KfyGEsg0AuAgIzl0lU1Ec681wVnnHpNio+94zyw0MV/QWxVAKZCgjMTAdTS1vLwjOtPz73jNqZ3yJAU0WIphKmScuWwLYF6jqewKxr3lmvdjE80+eecfk26nrjEaKp5lm6k4I0tilIYytMr8t5TIDAXQUE5l3lfN+gAulzzwjRqLh8m6qPMI0gjRp0EXZOgMCkBEoLzEnhW8zdBPoI0zgrjYoz0sWKs9JUAvVu8/FdBKYqIDCnOtlK17VumC7jSZd5Y3tToEa4pkCNbYRq1LL9eZ4AgekICMzpzHL8lWTewWKYpsu7aRuXeaPi89JUy5YTYZoqQjUqgnSxIkxTRaBGLduf5wkQKENAYJYxJ10OLBBhGhWfl6ZKYZq2EahRKVBju6ytFKixjUCNWgzUuJ8CNbYRqFHL9ud5AgTGFxCY489AB4UIRKBGpUCNbQrTtI1AjYowTbVseRGmqSJQoyJIl1UE600VQbtYP3z78J133/35D64d10MCBO4pIDDvCejbCSwKRKBGRZimSmGathGoUSlQY7u4j2X3U7he30bQLtaDneZfz57/51kE75vv/uzHy/bneQIENhMQmJt5eTeBewtEoEalQI1tCtPz7Rv/fuN582A/QnWxIlhvqlUNdWdn7arXvZaJgDaKEBCYRYxJk1UJfPbZN1+f/G0WobpYEaw31XnInhy1N22//vzvX1RlZ7EEBhQQmAPi2jUBAgQIFC9wuQCBeUnhDgECBAgQWC4gMJfbeIUAAQIECFwKCMxLinzu6IQAAQIE8hMQmPnNREcECBAgkKGAwMxwKFrKWUBvBAjUKiAwa528dRMgQIDARgICcyMubyZAIGcBvREYUkBgDqlr3wQIECAwGQGBOZlRWggBAgRyFii/N4FZ/gytgAABAgS2ICAwt4DsEAQIECBQvsCUA7P86VgBAQIECGQjIDCzGYVGCBAgQCBnAYGZ83Sm3Ju1ESBAoDABgVnYwLRLgAABAuMICMxx3B2VQM4CeiNA4AYBgXkDiqcIECBAgMB1AYF5XcRjAgQI5Cygt9EEBOZo9A5MgAABAiUJCMySpqVXAgQIEBhNYI3AHK03ByZAgAABAtkICMxsRqERAgQIEMhZQGDmPJ01evMWAgQIENiOgMDcjrOjECBAgEDhAgKz8AFqP2cBvREgMCUBgTmlaVoLAQIECAwmIDAHo7VjAgRyFtAbgU0FBOamYt5PgAABAlUKCMwqx27RBAgQyFkgz94EZp5z0RUBAgQIZCYgMDMbiHYIECBAIE8BgXkxF18JECBAgMBKAYG5kseLBAgQIEDgQkBgXjj4mrOA3ggQIJCBgMDMYAhaIECAAIH8BQRm/jPSIYGcBfRGoBoBgVnNqC2UAAECBO4jIDDvo+d7CRAgkLOA3noVEJi9ctoZAQIECExVQGBOdbLWRYAAAQK9CvQcmL32ZmcECBAgQCAbAYGZzSg0QoAAAQI5CwjMnKfTc292R4AAAQJ3FxCYd7fznQQIECBQkYDArGjYlpqzgN4IEMhdQGDmPiH9ESBAgEAWAgIzizFoggCBnAX0RiAEBGYoKAIECBAgcIuAwLwFyMsECBAgkLPA9noTmNuzdiQCBAgQKFhAYBY8PK0TIECAwPYE/g8AAP//dxQ/2wAAAAZJREFUAwAUfCVzFQ3K8wAAAABJRU5ErkJggg==', '2026-08-31 12:03:28', '2026-08-31 11:44:23', '2026-08-31 12:03:28'),
(9, 2, 3, 'department_chair', 2, 'Dept. Chair', 'department_chair', 'Approved', 990, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 12:06:50', '2026-08-31 11:44:23', '2026-08-31 12:06:50'),
(10, 2, 3, 'dean', 3, 'College Dean', 'hr', 'Approved', 8, 'User', NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAcwAAACMCAYAAADx9JleAAAN30lEQVR4AeybwXLcxhGGl3KSSg6xVJWKlFQuOeRg+Sl8zsvlkvfxWyi5+AFsnyxfba33I9X2ENolsbsAtnvmY6kJYDCY+ftr1vyYJfVi55cEJCABCUhAAs8S0DCfRWQHCUhAAhKQwG6nYWb+KVCbBCQgAQmkIaBhpimFQiQgAQlIIDMBDTNzddSWmYDaJCCBwQhomIMV3HQlIAEJSOAyAhrmZdx8SgISyExAbRJYgYCGuQJUh5SABCQggf4IaJj91dSMJCABCWQmUFabhlm2dAqXgAQkIIEtCWiYW9J2LglIQAISKEtgCMMsWx2FS0ACEpBAGgIaZppSKEQCEpCABDIT0DAzV2cIbSYpAQlIoAYBDbNGnVQpAQlIQAI3JqBh3rgATi+BzATUJgEJ/EZAw/yNhWcSkIAEJCCBkwQ0zJNovCEBCUggMwG1bU1Aw9yauPNJQAISkEBJAhpmybIpWgISkIAEtiZwjmFurc35JCABCUhAAmkIaJhpSqEQCUhAAhLITEDDzFydc7TZVwISkIAEViWgYa6K18ElIAEJSKAXAhpmL5U0j8wE1CYBCXRAQMPsoIimIAEJSEAC6xPQMNdn7AwSkEBmAmqTwEwCGuZMUHaTgAQkIIGxCWiYY9ff7CUgAQlkJpBKm4aZqhyKkYAEJCCBrAQ0zKyVUZcEJCABCaQioGFOyuGlBCQgAQlI4BgBDfMYFdskIAEJSEACEwIa5gSIl5kJqK1nAi/fvP361d+++KrnHM2tNgENs3b9VC+BLgg8GOXdV/v9i/90kZBJdElAw+yyrCYlge0JXDrjq9dv/3swyq8/Pv+vj0cPEkhHQMNMVxIFSWAsAvvd7h+R8eH8d3HuUQLZCGiY2SqiHgkMRmB/d/fvSPn9t+/+FOcelyTgWEsQ0DCXoOgYEpDARQRevv7ym7vd7g88vN/vfuJoSCArAQ0za2XUJYEBCOx3+3+S5n6///D+u3e/59yQQFYCaxlm1nzVJQEJJCFwv7u8u7tfg95/97/PkshShgROErj/YT151xsSkIAEViJw2F3+caWhHVYCqxDQMFfBmnxQ5UkgB4G/55ChCgnMI6BhzuNkLwlIYEECn79+++Hu8MWQ+8MXR0MC2QlomNkrpL7RCHSf7+dv3n5/8Mo7Ej145f7w+0vXIWAY6Qn4g5q+RAqUQGcE9ru/kJFmCQWjEgENs1K1bqD15Zsv90sGH8XdIA2nTEQgdpcHSe8PUeufaocmoGEOXf7TyYdJnu5x2R0WS8bWOC/jV/2ptu6Hj2JfVc9H/WMR0DDHqvez2WJmRNvxh2/f3S0RfARHMHYYJ3NFsJgSf/7rFz8T9DP6JBA/B31mZ1Y3IrD6tBrm6ojrTIBxtWrDJNu2a84PO4oXBIslMR0LEyVefPxCDwY67ed1XQLUF/X8HHA0JFCJgIZZqVobasUs15qOxZJgjogPhy9MlGjnZYHVNFsidc+jjtMa181I5aMR0DCvqHivj2JiW+f24/f//wwTJZifiIUV02S36ce0W1dl2fmo47IjOpoEtiWgYW7L29nOIIB5HjaeH+IRPqlll0JEm8caBNqaUdcaqlUpgccENMzHPLxKRoCd53S3yU6FHSeLMHFcsq0ZCcSnBhm1qUkCzxHQMJ8j5P0UBNiVtMaJKIyT0DyhkTd4qaFOKKSOHA0JVCSgYVas2sCaWXAxTqLdrbAgE2GeAyNKm3pbr7QiFSaBJwhomE/A8VZuAlPzjAVZ48xTN3eXeWqhkusJaJjXM+xuBBa5aklhnkSYJvoxzoq5oL23aOvSW2795WNGpwhomKfIDNgef5GK0VT9LxyYZvtxLbn4Me1tfph5WYE/s1MXjoYEKhPQMCtXb2Ht/EVq7AT4LxwLD7/pcCzQU+NkAd9UhJPdE4ifqfsLv0mgMIEMhlkYX3/SMZqesiKfWLDZ7bjb3Ka6vJzAm9moAUdDAtUJaJjVK7ii/oofy7JQY4ptxMIdqLiO+9HmcVkCMGbEeFnh3JBAdQIaZvUKrqA/Frn7j2VXGD/TkBgnJptJU3UtLU93l9Wrqf6WgIbZ0vD8nkAY5v1F8W/kwu8y26CtTYvdEMZJtIt928fz+QTgSe8pZ9oMCVQmoGFWrt5K2vnjnxi6uoGweE+NkF0PBhp/FRy5cqR/oZyRnCpadnBOJU4xEriSgIZ5JcBeHw8zwUAq/S6TRRozjIhdDnmEccaizosB/aJPr7XcKi+4wpn5ZAoFozcCGmZvFV0oH8wkFr3Kv8sMA41cWNCJME9eBugT98HHfRZ/zo15BOAFN3rDEqacDx0m3x0BDbO7ki6XULvoYSzLjbz9SOQy3U2ywPMygHly3qriGhNo2zz/lACMjvH7tKctEqhPQMOsX8NVM4iPZjGWVSfaaPAwzql5Hpse08QMMIVj90dvgwuMWg7uLlsanicmcJE0DfMibOM8xEezkW31XWbkEUfMM14Iou3YEVPAHI7dG7UNHnCJ/DFKXkJgGm0eJdAbAQ2zt4qukE+YSi+7TBCx4LN7bHNi0Y+gTxuYA/15rm0f8RwG8CB3eGmUkDBGIKBhblTlytP0tMtkscf4YsGPuvBSwO4oAhPADOJ+HHmO5xkn2jxKQAJjENAwx6jz1VliKAzS7si4rhIYHEaH4bWaMUXMsX0piPuYJ/cI+kU7R8ZhPMbleoQgV3Imd/KFCYw4NyQwAgENc4QqL5BjaygsmgsMufoQscCjNxZ5JmWhxwSJhwWf1qeDfvTnxYHnozfjMj7BfERvv+slV/IiV84JGMCEc0MCoxDQMEep9AJ5YhgxTHZTmC7wofvahZ4XB4xiapyMj6EQ7MIxUAIdRHZe6J8b1zKcO4/9JJCNgIaZrSLJ9WAUSMQUOGYJTAmDisC4QhsLPGZPYHbRfs0xjJOx23Gm1+gg4BXa0No+k/0cveSATvJbiiHjzQn7SCALAQ0zSyWK6MAoQmoYQFxveWTHFvNzjAV9qmHtBR7ziJeImBtjJmhnfiLucUQrmjEirrMG+tCJXjSSB/lybkhgRAIa5ohVvzJnzODKIWY/PjVGFnCCHdt0EBZ0tLWxxQLPSwTmiB7MBaPhnHbmJ0ITGgnu05dc6E/QliXgjr7Qg2byiGuPEnggMNZ3DXOsei+WLQYQg7HorxXHjDHmZRFHR8QtF3TM8ZhphtY4opGgL/ppx5gIGGKcmBXttwo0tNzRieZb6XFeCWQhoGFmqURBHRjVFrJZsDEY5msj2yI+NU0MkMCAppzoi37yIb+4j3FiVvHc1ubJfGhAD7rQh06uDQmMTqCaYY5er3T5s6CuHSzYGEy65I8IQifmfuTWySbygyHPYVLREeNqzTPa1zpi7MwX47daos2jBEYmoGGOXH1zX4UApokBRmCIcybiOfry3DHzjF3nnLHO7YNZYtA8h1GiAT1cGxKQwAMBDfOBg9+XIOAYixHArMI8MbAYGFPDOCMwurh3yZGPYBmLcXkeo2Zezg0JSOAxAQ3zMQ+vJJCOAAbGjq81zhCJ0WF4GF+0zT1itu1HsJglRj33eftJYDQCGuZoFTffsgTCODFPojVQjA/jjDhioJ/kjdnSyDiMp1lCw5DAaQIa5mk23pFAagJhoOeKZGeJsfIcZsk4nBsSkMDTBDTMp/l4VwLpCbA7jMAAERw7zulOE7OMnSX9NEsoJAgllCCgYZYokyIlMI8ABhimyRNhnOwoiTBLfl+JydLHkIAE5hHQMOdxspcEyhDANJ8Si1n6+8qnCHlPAo8I/HqhYf6KwhMJ9EOA3eOp0Cz7qbOZbEtAw9yWt7NJQAISkEBRAhpmwsIpSQISkIAE8hHQMPPVREUSkIAEJJCQgIaZsChKykxAbRKQwKgENMxRK2/eEpCABCRwFgEN8yxcdpaABDITUJsE1iSgYa5J17ElIAEJSKAbAhpmN6U0EQlIQAKZCdTXpmHWr6EZSEACEpDABgQ0zA0gO4UEJCABCdQn0LNh1q+OGUhAAhKQQBoCGmaaUihEAhKQgAQyE9AwM1enZ23mJgEJSKAYAQ2zWMGUKwEJSEACtyGgYd6Gu7NKIDMBtUlAAkcIaJhHoNgkAQlIQAISmBLQMKdEvJaABCSQmYDabkZAw7wZeieWgAQkIIFKBDTMStVSqwQkIAEJ3IzADMO8mTYnloAEJCABCaQhoGGmKYVCJCABCUggMwENM3N1ZmiziwQkIAEJbENAw9yGs7NIQAISkEBxAhpm8QIqPzMBtUlAAj0R0DB7qqa5SEACEpDAagQ0zNXQOrAEJJCZgNokcC4BDfNcYvaXgAQkIIEhCWiYQ5bdpCUgAQlkJpBTm4aZsy6qkoAEJCCBZAQ0zGQFUY4EJCABCeQkoGE+1MXvEpCABCQggScJaJhP4vGmBCQgAQlI4IGAhvnAwe+ZCahNAhKQQAICGmaCIihBAhKQgATyE9Aw89dIhRLITEBtEhiGgIY5TKlNVAISkIAEriGgYV5Dz2clIAEJZCagtkUJaJiL4nQwCUhAAhLolYCG2WtlzUsCEpCABBYlsLBhLqrNwSQgAQlIQAJpCGiYaUqhEAlIQAISyExAw8xcnYW1OZwEJCABCVxOQMO8nJ1PSkACEpDAQAQ0zIGKbaqZCahNAhLITkDDzF4h9UlAAhKQQAoCGmaKMihCAhLITEBtEoCAhgkFQwISkIAEJPAMAQ3zGUDeloAEJCCBzAS206ZhbsfamSQgAQlIoDABDbNw8ZQuAQlIQALbEfgFAAD//5nLgB0AAAAGSURBVAMAgtOZZJShCqUAAAAASUVORK5CYII=', '2026-08-31 12:07:38', '2026-08-31 11:44:23', '2026-08-31 12:07:38'),
(11, 2, 3, 'research_office', 4, 'Research Office', 'research_office', 'Approved', 991, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 12:08:11', '2026-08-31 11:44:23', '2026-08-31 12:08:11'),
(12, 2, 3, 'vpaa', 5, 'VPAA Sign-off', 'vpaa', 'Approved', 992, 'User', NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAcwAAACMCAYAAADx9JleAAAPWklEQVR4AeyczXIcSRVGu8XMsLLFAssRsCFgM3IEPAEE8Ga8GQt4AogYsQFWEGGbBR6vmGHUk6eta6fLpf6tqr6VdTr0qf6yMu89V7qfqyX5auVLAhKQgAQkIIG9BDTMvYgcIAEJSEACElitNMzMXwXGJgEJSEACaQhomGlKYSASkIAEJJCZgIaZuTrGlpmAsUlAAgsjoGEurOCmKwEJSEACpxHQME/j5l0SkEBmAsYmgREIaJgjQHVKCUhAAhJoj4CG2V5NzUgCEpBAZgKzjU3DnG3pDFwCEpCABKYkoGFOSdu1JCABCUhgtgQWYZizrY6BS0ACEpBAGgIaZppSGIgEJCABCWQmoGFmrs4iYjNJCUhAAvMgoGHOo05GKQEJSEACFyagYV64AC4vgcwEjE0CEvhAQMP8wMI9CUhAAhKQwKMENMxH0XhBAhKQQGYCxjY1AQ1zauKuJwEJSEACsySgYc6ybAYtAQlIQAJTEzjGMKeOzfUkIAEJSEACaQhomGlKYSASkIAEJJCZgIaZuTrHxOZYCUhAAhIYlYCGOSpeJ5eABCQggVYIaJitVNI8MhMwNglIoAECGmYDRTQFCUhAAhIYn4CGOT5jV5CABDITMDYJHEhAwzwQlMMkIAEJSGDZBDTMZdff7CUgAQlkJpAqNg0zVTkMRgISkIAEshLQMLNWxrgkIAEJSCAVAQ2zUw4PJSABCUhAAn0ENMw+Kp6TgAQkIAEJdAhomB0gHmYmYGwSkIAELkdAw7wce1eWgAQkIIEZEdAwZ1QsQ5VAZgLGJoHWCWiYrVfY/CQgAQlIYBACGuYgGJ1EAhKQQGYCxjYEAQ1zCIrOIQEJSEACzRPQMJsvsQlKQAISkMAQBMYyzCFicw4JSEACEpBAGgIaZppSGEh2Ak9vbu+vn7/YdPXk2ZffZY/d+CQggfMJaJjnM5zfDEZ8FAEMEZNcl1ffjeX0uu+85yQggbYIaJht1dNsBiQQRnlVXjHtfXm9efnVutbXr+78PgpAbiXQMAG/0RsurqmdRiDeei0++dH3Byb59vXffnDarAff5UAJSCApgY8aQtIYDUsCkxHALOu3WMsD5fYDs5wsCBeSgARSEtAwU5bFoKYmwNuvtVluyguT5IkSTR2P6yUlYFiLJqBhLrr8Jg8BjJK3X+PJsnjlxp9LQkZJQAI1AQ2zpuH+4gjwZBlGSfK8/6pZQkJJYHYERg9YwxwdsQtkJRBPlhEfT5a+/Ro03EpAAl0CGmaXiMfNE8Aou39X6ZNl82U3QQmcTUDDPAOht86fAE+V8cs988/GDCQggTEJaJhj0nXu1AQwS39embpEBieBVAQ0zFTlMJjhCDiTBCQggWEJaJjD8nQ2CUhAAhJolICG2WhhTUsCQYA/nUH8otMu8ctQIcbH/WNsnVMCcySgYc6xasYsgR0EMDuML8yR/5QB7bhle4m/Rw0xPu5nrhBzbwf7SQILJKBhLrDoS0+ZX/aBAebAtgVhZGFwmF1fbuTNn8/wW8FdcZ7rqMuDuULMHeuEibJl/e59Hs+VgHE/RkDDfIyM55slUP/nBC00egwLI+sWDBNEYY78RnCdez2e81xHMZ4t92OiqB7PfpgoW9bHSIkFcV1JoDUCGmZrFTWfowjQ7I+6IdFgzB6TqnPA5EKYIDonZO7HRFHMy7Y20tpMiQURF8aJzlnfeyWQiUAGw8zEw1gWQoCGT6o0d4yH/bmIeDEknuoiZvLByOJ47G1tpLWZYp6I9WGLiBVpnlBRcyagYc65esZ+MgEafjT22nhOnnDkG8MkMZ463jBK8hk5hIOmxzwR5g1fFDdqnkHC7VwJaJhzrdxUcTe8Do090sv89NM1SWLOZpTE1BV80S7zhDvq3uuxBDIS0DAzVsWYJiOA8bBYPP3wJMdxFnXNhHgxoCxPlIdywjgRsfPUibgX7og8EeeUBLIS0DCzVsa4JiGA8WBCsRhvd2Zp3DxZYibEhsFgNsTL8YNmucE4EfmQF0mQJ4I94pySQDYCGma2ihjP5AQwoW7zzta0MZjJwUywIHl12YdxTrC8S0jgKAIa5lG4HNwyAZp3/cTDE96ljJO1gzWGEvutbmFPnjX/odnzdjtiXvgOJeZDn9TGE80R0DCbK6kJnUNgisa9Lz4aeYzBRGJ/CVv4x1vkPGkem/MuQ+TtdnTKvLviYD5E3Vh/19ilXbt+fvvHpzcv/tBK3hpmK5U0j0EJ0Ljrpx2a4dRPEUszy3MKiFFRo0MMkbpiygjG54r5zom91XtLPf65Wq1/t15vfrvK9zopIg3zJGzetAQCmCbNNBpiPEVMbZxLYB05whbTi+Nd2zDJ0pg33Xt2mSF15efWaNf8h15jvkPHLmVcqcl9yfVnRas3L+9+z7YFaZgtVNEcRiVAQwzTZCGMk8bO/piaYo0x4z9m7jA/2O67L8buMsmhzHBfLF7/QKB8vf6pGOUGlbProuY+NMyJSuoy8yaAab55+dU6jJPGTmMoTYJ/SY+S3BRrjBL4kZNigLX51U+HTMV1WIfqsVyP8ZcySeIijiXq+ub2L+SPytfrrysGG75fUHVu9rsa5uxLaAJTEpjCOGkyYczkVhrRekxjZo2pRT40WRQGSM7kXhsfZhnX6xjDJLvj6zFT7JPHFOtkWeP65sXft3r+4t2T5Hr9y25spY5/LnVp0luaTKpbQI8lsJvA8VcxztIYNnFnmNrTm9tBnjiZvzSdSZ9oI5cxt/DBJOFVrwNLcuYcJskYFGbJdXiEalPlnqnVlwcmfum4huZQ8vwXohZotV79fKvuQpvNX6M2pY6/6V5u5VjDbKWS5jE5gdIYrmgSNHMWxwRQaTCDmCZzsgaNmH3E/DSuIddg3jFVGyDxx1rkBT9EnjEuTDLGwZfrcXzpLez78mjBLEtuHxlkyfMn6BPmm9U/VkXUbqtXd7/6ZEyDJzTMBotqStMSoJnTNGjsrFwazHpIU6MRx/x9a5QmN5hBE/9QeswAwyjJa99a5MsYcnzy7PZbxPGl9OTZl99RX9YnNupySB6Mz6inz29fw/b64S3WkluvQZZc/43Id6tXX/3iTVHGnMaMScMck65zL4rA2MbJ/IiGVZrX9u3g0uC2HzQ8Gt+lgWMoiHi6T4rHGGXksU3u4dPV1fozxNxdkXutJ89uv0Exz7nbJ8UoWbPOiVqcO++U9z/FHIuuH8yR7Xq1/jF4u3GUr6+PDLLk+lPUHbe0Yw1zaRU339EJlMZyhTmUpvPe1GhONPShFmeN2jiZl8YX69DgOTeFWIt1EYaC6nVhQaz7nsS4zrjQ/f3m/5vqVc/Z3Sf3WsVYP0fEtEvUpE9Pbm7/180r1iQkYozjDNuSw38r3fflvDXHYpDdeMlns9r8h5xC5evrQIPsztb2sYbZdn3N7kIEaP6l6VzRjCIEGno0stLc7hFNOa6fsmUNmlx3HUyLtVjjlHl33UPMiPkRa3XH35cXcSFYdK8fcvz29d3n5Bdirlr395tvQ+Rf65D5GUNN+nS1Xn/Rl1fcQ96ZVHK4rrQmzseEOaJgueX78u7ZY+M9/4GAhvmBhXsSGJwAzYjGRDOvJy/NbftBU47Gi7khzKgee8h+rHNfXvVaLML8zIsOmatvDDExDyJmVI8ry24/yBWdapL1nPv2i6F+ESL/WsTQp/vN5pta+9aY43Xq/6A3ZbtVzeLrYo5ojrldOua5Gealebm+BE4iQDOPpoWzlEa2/agnw9wQZoQx1cLsamFgqL6ffYwq1touUD5xnnlRzBlzxRxsQ1yLcbElJuapRR6RE+ui+nrG/bev7n642mw+u1qXJ8iivhgLsk2dW+Q4ly31f9CPynarvjw9dzwBDfN4Zt4hgbMIYCylkV2haMI0aBo16pscs6uFgaEwtL5tjN81X8zBNsR9ffcQI4qYyaNvXLZz8Q+BYESe3RjrvKjLXHLr5uHxuAQ0zHH5Lmt2sz2ZAA2aRo3CkNjSyBFGWuvkhc64EaPBTHkCDYUZsT1j6pNuZc1QxBOmWG+JG9WLwBTBGMG/vu6+BPoIaJh9VDwngSQEaOQII61Fkx9ahxgyhlkLIwrVJjXFfqzLNmLaVbauQcJ113ivSaBLQMPsEvFYAm0S2JvVY4aM0YRqU2V/76QTDSAWFHHGtv5HhQY5UTEaXkbDbLi4piaBIQhgNKHaVNmvDemS+8SCIs7YDpG/c0ggCGiYQcKtBCQggUsRcN1ZENAwZ1Emg5SABCQggUsT0DAvXQHXl4AEJCCBzATex6ZhvkfhjgQkIAEJSOBxAhrm42y8IgEJSEACEnhPQMN8jyLPjpFIQAISkEA+AhpmvpoYkQQkIAEJJCSgYSYsiiFlJmBsEpDAUglomEutvHlLQAISkMBRBDTMo3A5WAISyEzA2CQwJgENc0y6zi0BCUhAAs0Q0DCbKaWJSEACEshMYP6xaZjzr6EZSEACEpDABAQ0zAkgu4QEJCABCcyfQMuGOf/qmIEEJCABCaQhoGGmKYWBSEACEpBAZgIaZubqtBybuUlAAhKYGQENc2YFM1wJSEACErgMAQ3zMtxdVQKZCRibBCTQQ0DD7IHiKQlIQAISkECXgIbZJeKxBCQggcwEjO1iBDTMi6F3YQlIQAISmBMBDXNO1TJWCUhAAhK4GIEDDPNisbmwBCQgAQlIIA0BDTNNKQxEAhKQgAQyE9AwM1fngNgcIgEJSEAC0xDQMKfh7CoSkIAEJDBzAhrmzAto+JkJGJsEJNASAQ2zpWqaiwQkIAEJjEZAwxwNrRNLQAKZCRibBI4loGEeS8zxEpCABCSwSAIa5iLLbtISkIAEMhPIGZuGmbMuRiUBCUhAAskIaJjJCmI4EpCABCSQk4CG+a4ufpaABCQgAQnsJKBh7sTjRQlIQAISkMA7AhrmOw5+zkzA2CQgAQkkIKBhJiiCIUhAAhKQQH4CGmb+GhmhBDITMDYJLIaAhrmYUpuoBCQgAQmcQ0DDPIee90pAAhLITMDYBiWgYQ6K08kkIAEJSKBVAhpmq5U1LwlIQAISGJTAwIY5aGxOJgEJSEACEkhDQMNMUwoDkYAEJCCBzAQ0zMzVGTg2p5OABCQggdMJaJins/NOCUhAAhJYEAENc0HFNtXMBIxNAhLITkDDzF4h45OABCQggRQENMwUZTAICUggMwFjkwAENEwoKAlIQAISkMAeAhrmHkBeloAEJCCBzASmi03DnI61K0lAAhKQwIwJaJgzLp6hS0ACEpDAdAS+BwAA///et0PsAAAABklEQVQDAMwUKaADRg1/AAAAAElFTkSuQmCC', '2026-08-31 12:08:34', '2026-08-31 11:44:23', '2026-09-19 13:48:19'),
(13, 2, 3, 'finance', 6, 'Finance Office', 'finance', 'Approved', 4, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 12:13:46', '2026-08-31 11:44:23', '2026-09-19 13:48:19');
INSERT INTO `grant_proposal_approval_steps` (`id`, `workflow_id`, `grant_application_id`, `step_key`, `step_order`, `step_label`, `approver_role_key`, `status`, `approver_user_id`, `approver_name`, `remarks`, `signature_data`, `acted_at`, `created_at`, `updated_at`) VALUES
(14, 3, 4, 'adviser', 1, 'Academic Adviser', 'adviser', 'Approved', 54, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 13:44:23', '2026-08-31 13:34:26', '2026-08-31 13:44:23'),
(15, 3, 4, 'department_chair', 2, 'Dept. Chair', 'department_chair', 'Approved', 990, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 13:56:44', '2026-08-31 13:34:26', '2026-08-31 13:56:44'),
(16, 3, 4, 'dean', 3, 'College Dean', 'hr', 'Approved', 8, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 13:57:33', '2026-08-31 13:34:26', '2026-08-31 13:57:33'),
(17, 3, 4, 'research_office', 4, 'Research Office', 'research_office', 'Approved', 991, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 13:58:21', '2026-08-31 13:34:26', '2026-08-31 13:58:21'),
(18, 3, 4, 'vpaa', 5, 'VPAA Sign-off', 'vpaa', 'Approved', 992, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 13:59:06', '2026-08-31 13:34:26', '2026-09-19 13:48:19'),
(19, 3, 4, 'finance', 6, 'Finance Office', 'finance', 'Pending', NULL, NULL, NULL, NULL, NULL, '2026-08-31 13:34:26', '2026-09-19 13:48:19'),
(20, 4, 5, 'adviser', 1, 'Academic Adviser', 'adviser', 'Approved', 54, 'User', NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAcwAAACMCAYAAADx9JleAAAMQElEQVR4AezdbW/b1hkGYMlpuhVrnQFbXWDtD5gLrL9xf3BfNiDuvm8F6hbD6qx7aRprvG2fhGYkS7Yo6pC8gjy2XijyOddj4MaRHedk4Q8BAgQIECCwVUBgbiVyAAECBAgQWCwEZs1fBXojQIAAgWoEBGY1o9AIgXoFXnz25apbp2df/rHejnVGoH8Bgdm/qTPOQ2A2q3xxdv7n2SzWQgk8ICAwH8DxFIG5CZyenf+9u5NcLJd/KA4/fPtyWerq8qUdZoHxeRYCAnMWY7ZIApsFTj87/+7F3Vuuy+Xyd2uPXK3+kqBc+1yND+qJwAEEBOYBUJ2SwBgETu+CcrlY/rbb72q1+iYB+bYuL77qHuM+gbkJCMy5Tdx6Zy3QvOX6zxdlN9kJytVi9X0JyKvLi89nDWXxhxQY7bkF5mhHp3ECuwuUoGzecn3RftW9kPz24tP2c24TIHBfQGDe93CPwGQETs++/Nfb3eRyeT8oV6sfspu8EpKTmbeFHF5gFoF5eEZXIFCHwP2QXPyq3VXzfcmbkLwJysuLX7efc5sAge0CAnO7kSMIVCvwydn5f1LvdpLdkFz8uFotfhSS1Y5QYyMSEJgjGtY0W7Wqxwq0A/Jkufxlqn2OBGTqNiRffnx1+fLj9vNuEyDwNAGB+TQ3ryIwmEATkP87PTu/LrvIbkCmkevV6r8JyFQCMpXHFQEC/QkIzP4snYlALwJrAvLDZfOnffLm+5Grdki+urz4qP18X7edhwCBdwIC852FWwQGE/jk0/OfUu2dY2sH+V5AprEmIH/KDjJ1dXlxIiSjoggMJyAwh7N2pZkKNMH4OlUCMZ9PTpbPU83GcbmJpR2QCckmIH+x6ViPz1HAmocWEJhDi7ve5AUSju2dYxOMH6TWLTxvraaur1evUwnGUgJynZjHCBxPQGAez96VRyrwyae//7lUOxizc0wlHNftHJtA/DlVAjGf89Zq6tV3Fx+mRkqibQKzEHhMYM4CxCIJFIEmFN+kuqF4cnLy7OTkttYFY15/t2u8F5BNID5P5XlFgMD4BATm+Gam4wMIbAjGk/zZFIpp4zYYr99cX1+/yY6x1N2uUUAGSRGYiIDAnMggF9axk0CCMbVm13iyKRjvQrHJxOvrEojl820wfv3Bq+++/mCnBhxEgMBoBQTmaEen8W0CCcZUOxyzY0xtCsebVGw+lEDM57tQfNaE4rNt1/Q8AQLTFRCY053t7FaWcEzlB29SCcbUunDctGtMKKZ6xnM6AgQmICAwJzDEuS4h4ZhKOKYSjqmux7pwtGvsKrlPgMA2AYG5Tcjz1QgkHFMJx1TCMdVtsHlH9eZv3k5NCceukPv3BNwhsKOAwNwRymHDCyQcUwnHVMIx1e3kJh2bDwnHVN5STXWPc58AAQL7CAjMffS8tleBhGMq4ZhKOKa6F2my8eZvwjGVcEx1j3OfAIHRC1S1AIFZ1Tjm1UzCcd1PsHYVbtKx+ZBwTCUcU93j3CdAgMAhBQTmIXWd+57AuoBc9xOsTTbe/E04phKOqXsnc4cAAQIDCwjMDri7/Qk8JiATjKUSjqn+OnEmAgQI7C8gMPc3dIZGIOGYyvceS+X7j5t2kCUc81k4NoD+EiBQvYDArH5EdTWYUEy1v/eYgEw4ptZ1m/dXE4ylnh6Q687uMQIECAwjIDCHcR7lVRKMqQRiqYRiat3OMYtMOKZKOOazgIyMIkBg7AICc+wT7LH/hGOqG47rLrHut+eUcBSQ68Sm/5gVEpi6gMCc+oS3rG+XgCzhmEAs5bfnbIH1NAECkxMQmJMb6fYFdUOy+4ruW6olHLvHuU+AwFgE9NmHgMDsQ7HycyQgU+23Wrstt0PSW6pdHfcJECCwWAjMiX4VdAMyP6jTXmoCMlXeYhWSbR23CRAg8L7AoQLz/St55KACCcjUY3aRQvKgI3FyAgQmJiAwRzzQbkB2d5FZml1kFBQBAgT2FxCY+xsOdoYEZGrbLnJrSA7WsQsRIEBgOgICs/JZJiDLb9XJDjLVbrn7Tz7yNmuqfYzbBAgQILC/gMDc37D3MyQkU9lJJiC7v1WnvYP0Tz565z/2CV2fAIFKBQRmJYNJQKZKSCYo2621Q9IOsi3jNgECBIYREJjDOG+8ipDcSOMJAvUJ6GjWAgLzCOMvIVl2k+0W7CTbGm4TIECgHgGBOdAshORA0C5DgMBcBQ6+boF5QGIheUBcpyZAgMDAAgKzZ/BtIVnecvWDOz3DOx0BAgQOLCAw9wAuL31MSArKouYzAQIExiUgMPeYVwnKdf8EpL2TFJJ7IHspAQIEKhEQmI8cRAnJh37CNQGZeuSpHd6rgJMRIECgXwGBuaNnCcp1u0n/RdaOiA4jQIDAiAUE5gPDKyG5bTf5wCk8RYDAGgEPERijgMBcM7USlHaTa3A8RIAAgZkKCMy7wZeQtJu8A/GJAIGZClj2JoHZB2YJSrvJTV8iHidAgACBCMwyMEtI2k3mS0ARIECAwC4CNQTmLn32ckwJSrvJXjidhAABArMSmHxglpDs7iZXzZ/2LxeY1dQtlgABAgQeLTDZwCxBuWk3eXV5ceKXC+zw9eIQAgQIELgRmExgJiBT2Umm2kHZbCZXdpM38/aBAAECBJ4oMOrATECmSkC2QzIeJSTtJqOhJihgSQQIDCgwusAsAflQSK6eL36TX1fnLdcBv5JcigABAhMXGEVgdkOyO5PbneRHH5aQvPrby390j3GfAAECgwq42OQEqgzML7744qPdQvLlsoTkYvGn15ObjgURIECAQDUC1QTmx599dVZC8tXr0393vx8ZsdudZDsk86giQIAAAQKPEnjSwUcNzARkKt+PfLb4+dtuSCYgU9lFpnxP8kkz9iICBAgQ6EHgaIF5enZ+nYBMtdeRgHz+5s3nJSCFZFvHbQIECBA4lsDRAjP/NrIs+i4kT0tIfv/9X78pz03ls3UQIECAwLgFjhaY2TkmIFO53YTkq3FT6p4AAQIEpixwtMCcMqq1jU1AvwQIENguIDC3GzmCAAECBAgsBKYvAgIEqhbQHIFaBARmLZPQBwECBAhULSAwqx6P5ggQIFCzwLx6E5jzmrfVEiBAgMATBQTmE+G8jAABAgTmJTC2wJzXdKyWAAECBKoREJjVjEIjBAgQIFCzgMCseTpj602/BAgQmLCAwJzwcC2NAAECBPoTEJj9WToTgZoF9EaAwJ4CAnNPQC8nQIAAgXkICMx5zNkqCRCoWUBvoxAQmKMYkyYJECBA4NgCAvPYE3B9AgQIEKhZ4G1vAvMthRsECBAgQGCzgMDcbOMZAgQIECDwVkBgvqWo54ZOCBAgQKA+AYFZ30x0RIAAAQIVCgjMCoeipZoF9EaAwFwFBOZcJ2/dBAgQIPAoAYH5KC4HEyBQs4DeCBxSQGAeUte5CRAgQGAyAgJzMqO0EAIECNQsMP7eBOb4Z2gFBAgQIDCAgMAcANklCBAgQGD8AlMOzPFPxwoIECBAoBoBgVnNKDRCgAABAjULCMyapzPl3qyNAAECIxMQmCMbmHYJECBA4DgCAvM47q5KoGYBvREgsEZAYK5B8RABAgQIEOgKCMyuiPsECBCoWUBvRxMQmEejd2ECBAgQGJOAwBzTtPRKgAABAkcT2CEwj9abCxMgQIAAgWoEBGY1o9AIAQIECNQsIDBrns4OvTmEAAECBIYREJjDOLsKAQIECIxcQGCOfIDar1lAbwQITElAYE5pmtZCgAABAgcTEJgHo3ViAgRqFtAbgccKCMzHijmeAAECBGYpIDBnOXaLJkCAQM0CdfYmMOuci64IECBAoDIBgVnZQLRDgAABAnUKCMzbufhIgAABAgQeFBCYD/J4kgABAgQI3AoIzFsHH2sW0BsBAgQqEBCYFQxBCwQIECBQv4DArH9GOiRQs4DeCMxGQGDOZtQWSoAAAQL7CAjMffS8lgABAjUL6K1XAYHZK6eTESBAgMBUBQTmVCdrXQQIECDQq0DPgdlrb05GgAABAgSqERCY1YxCIwQIECBQs4DArHk6PffmdAQIECDwdAGB+XQ7ryRAgACBGQkIzBkN21JrFtAbAQK1CwjM2iekPwIECBCoQkBgVjEGTRAgULOA3ghEQGBGQREgQIAAgS0CAnMLkKcJECBAoGaB4XoTmMNZuxIBAgQIjFhAYI54eFonQIAAgeEE/g8AAP//LRbGBgAAAAZJREFUAwDhM2pkK0EwlgAAAABJRU5ErkJggg==', '2026-08-31 14:17:42', '2026-08-31 14:15:58', '2026-08-31 14:17:42'),
(21, 4, 5, 'department_chair', 2, 'Dept. Chair', 'department_chair', 'Approved', 990, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 14:19:54', '2026-08-31 14:15:58', '2026-08-31 14:19:54'),
(22, 4, 5, 'dean', 3, 'College Dean', 'hr', 'Approved', 8, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 14:21:24', '2026-08-31 14:15:58', '2026-08-31 14:21:24'),
(23, 4, 5, 'research_office', 4, 'Research Office', 'research_office', 'Approved', 991, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 14:22:13', '2026-08-31 14:15:58', '2026-08-31 14:22:13'),
(24, 4, 5, 'vpaa', 5, 'VPAA Sign-off', 'vpaa', 'Approved', 992, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 14:23:03', '2026-08-31 14:15:58', '2026-09-19 13:48:19'),
(25, 4, 5, 'finance', 6, 'Finance Office', 'finance', 'Approved', 4, 'User', NULL, 'data:image/png;base64,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', '2026-08-31 14:30:05', '2026-08-31 14:15:58', '2026-09-19 13:48:19');
INSERT INTO `grant_proposal_approval_steps` (`id`, `workflow_id`, `grant_application_id`, `step_key`, `step_order`, `step_label`, `approver_role_key`, `status`, `approver_user_id`, `approver_name`, `remarks`, `signature_data`, `acted_at`, `created_at`, `updated_at`) VALUES
(26, 5, 7, 'adviser', 1, 'Academic Adviser', 'adviser', 'Approved', 54, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:06:12', '2026-09-18 03:04:26', '2026-09-18 03:06:12'),
(27, 5, 7, 'department_chair', 2, 'Dept. Chair', 'department_chair', 'Approved', 990, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:06:51', '2026-09-18 03:04:26', '2026-09-18 03:06:51'),
(28, 5, 7, 'dean', 3, 'College Dean', 'hr', 'Approved', 8, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:07:42', '2026-09-18 03:04:26', '2026-09-18 03:07:42'),
(29, 5, 7, 'research_office', 4, 'Research Office', 'research_office', 'Approved', 991, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:08:27', '2026-09-18 03:04:26', '2026-09-18 03:08:27'),
(30, 5, 7, 'vpaa', 5, 'VPAA Sign-off', 'vpaa', 'Approved', 992, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:09:28', '2026-09-18 03:04:26', '2026-09-19 13:48:19');
INSERT INTO `grant_proposal_approval_steps` (`id`, `workflow_id`, `grant_application_id`, `step_key`, `step_order`, `step_label`, `approver_role_key`, `status`, `approver_user_id`, `approver_name`, `remarks`, `signature_data`, `acted_at`, `created_at`, `updated_at`) VALUES
(31, 5, 7, 'finance', 6, 'Finance Office', 'finance', 'Approved', 4, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:09:55', '2026-09-18 03:04:26', '2026-09-19 13:48:19'),
(32, 6, 8, 'adviser', 1, 'Academic Adviser', 'adviser', 'Approved', 54, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:27:16', '2026-09-18 03:26:45', '2026-09-18 03:27:16'),
(33, 6, 8, 'department_chair', 2, 'Dept. Chair', 'department_chair', 'Approved', 990, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:28:20', '2026-09-18 03:26:45', '2026-09-18 03:28:20'),
(34, 6, 8, 'dean', 3, 'College Dean', 'hr', 'Approved', 8, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:28:58', '2026-09-18 03:26:45', '2026-09-18 03:28:58'),
(35, 6, 8, 'research_office', 4, 'Research Office', 'research_office', 'Approved', 991, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:29:36', '2026-09-18 03:26:45', '2026-09-18 03:29:36'),
(36, 6, 8, 'vpaa', 5, 'VPAA Sign-off', 'vpaa', 'Approved', 992, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:30:02', '2026-09-18 03:26:45', '2026-09-19 13:48:19');
INSERT INTO `grant_proposal_approval_steps` (`id`, `workflow_id`, `grant_application_id`, `step_key`, `step_order`, `step_label`, `approver_role_key`, `status`, `approver_user_id`, `approver_name`, `remarks`, `signature_data`, `acted_at`, `created_at`, `updated_at`) VALUES
(37, 6, 8, 'finance', 6, 'Finance Office', 'finance', 'Approved', 4, 'User', NULL, 'data:image/png;base64,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', '2026-09-18 03:30:29', '2026-09-18 03:26:45', '2026-09-19 13:48:19');

-- --------------------------------------------------------

--
-- Table structure for table `grant_proposal_approval_workflows`
--

CREATE TABLE `grant_proposal_approval_workflows` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `current_step_key` varchar(40) NOT NULL DEFAULT 'adviser',
  `workflow_status` enum('In Progress','Completed','Returned') NOT NULL DEFAULT 'In Progress',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grant_proposal_approval_workflows`
--

INSERT INTO `grant_proposal_approval_workflows` (`id`, `grant_application_id`, `current_step_key`, `workflow_status`, `started_at`, `completed_at`, `updated_at`) VALUES
(1, 2, 'vpaa', 'Completed', '2026-08-31 08:47:21', '2026-08-31 11:07:08', '2026-08-31 11:07:08'),
(2, 3, 'finance', 'Completed', '2026-08-31 11:44:23', '2026-08-31 12:13:46', '2026-08-31 12:13:46'),
(3, 4, 'finance', 'In Progress', '2026-08-31 13:34:26', NULL, '2026-08-31 13:59:06'),
(4, 5, 'finance', 'Completed', '2026-08-31 14:15:58', '2026-08-31 14:30:05', '2026-08-31 14:30:05'),
(5, 7, 'finance', 'Completed', '2026-09-18 03:04:26', '2026-09-18 03:09:55', '2026-09-18 03:09:55'),
(6, 8, 'finance', 'Completed', '2026-09-18 03:26:45', '2026-09-18 03:30:29', '2026-09-18 03:30:29');

-- --------------------------------------------------------

--
-- Table structure for table `grant_proposal_evaluations`
--

CREATE TABLE `grant_proposal_evaluations` (
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

--
-- Dumping data for table `grant_proposal_evaluations`
--

INSERT INTO `grant_proposal_evaluations` (`id`, `grant_application_id`, `proposal_version`, `evaluator_user_id`, `evaluator_name`, `evaluation_type`, `score_rationale`, `score_methodology`, `score_budget`, `score_team_capability`, `score_compliance`, `total_score`, `comments`, `recommendations`, `required_corrections`, `recommendation`, `revision_reason`, `submitted_at`, `updated_at`) VALUES
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

-- --------------------------------------------------------

--
-- Table structure for table `grant_proposal_notifications`
--

CREATE TABLE `grant_proposal_notifications` (
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

--
-- Dumping data for table `grant_proposal_notifications`
--

INSERT INTO `grant_proposal_notifications` (`id`, `event_key`, `recipient_user_id`, `recipient_role`, `recipient_email`, `grant_application_id`, `type`, `title`, `body`, `url`, `is_read`, `created_at`) VALUES
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

-- --------------------------------------------------------

--
-- Table structure for table `grant_proposal_versions`
--

CREATE TABLE `grant_proposal_versions` (
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

--
-- Dumping data for table `grant_proposal_versions`
--

INSERT INTO `grant_proposal_versions` (`id`, `grant_application_id`, `version_number`, `version_label`, `proposal_pdf`, `proposal_pdf_original`, `supporting_docs`, `supporting_docs_original`, `ethics_doc`, `ethics_doc_original`, `abstract`, `objectives`, `researcher_notes`, `submitted_by_user_id`, `submitted_at`) VALUES
(1, 1, 1, 'Original', '53ac796ed0ad7af6ca74703f6e4a5b41.pdf', 'OLIVEROS CV.pdf', '72706d8b5bfd82f1135557fa92faf84a.pdf', 'OLIVEROS CV.pdf', '3b1bf340d2db787753a7f77efb0fcdaf.pdf', 'OLIVEROS CV.pdf', 'adsadas', 'adsadas', NULL, 9, '2026-08-31 07:42:38'),
(2, 2, 1, 'Original', 'b594409c59e3ba6839050c9dd63f50a6.pdf', 'OLIVEROS CV.pdf', '6a9719b97b1c60266f543bba2f313c38.pdf', 'OLIVEROS CV.pdf', '757f2e0bf3a62bd565b415570d7df5e8.pdf', 'OLIVEROS CV.pdf', 'dsada', 'asdas', NULL, 9, '2026-08-31 08:46:40'),
(4, 2, 2, 'Revised', 'f3f3ec4b05c99f1fbbe58278c60d4afe.pdf', 'OLIVEROS CV.pdf', 'e411ba015c79a77a27013c4206313257.pdf', 'OLIVEROS CV.pdf', '95ae712a926e7a6e5ceef81ed3ba0f14.pdf', 'OLIVEROS CV.pdf', 'dsada', 'asdas', 'sada', 9, '2026-08-31 08:46:40'),
(5, 3, 1, 'Original', 'bf397c70c1ea835a393e6b25aceac34d.pdf', 'OLIVEROS CV.pdf', '57aae4180acef208c0d86635d413673a.pdf', 'OLIVEROS CV.pdf', '11fdbc98c4857488b1153c1b35f2ae6f.pdf', 'OLIVEROS CV.pdf', 'sadasd', 'asdas', NULL, 9, '2026-08-31 11:43:42'),
(6, 4, 1, 'Original', '554ef2a9aab43ba9626415abd863c06d.pdf', 'OLIVEROS CV.pdf', 'e756dec1b73523d3fadc516b13c8b8df.pdf', 'OLIVEROS CV.pdf', '94c401e14d58b14cf3ea870b076aa98d.pdf', 'OLIVEROS CV.pdf', 'asdas', 'asdsa', NULL, 9, '2026-08-31 13:32:27'),
(7, 5, 1, 'Original', '6d7a59295ee3d7cd325d51cdb880d11c.pdf', 'OLIVEROS CV.pdf', '2e8ab8d2fe5b210a8ebd034054e70bc1.pdf', 'OLIVEROS CV.pdf', '2eb694566c7d8741b6189a97af16dfd8.pdf', 'OLIVEROS CV.pdf', 'dsda', 'sdas', NULL, 9, '2026-08-31 14:11:48'),
(8, 6, 1, 'Original', '0d0b58178c75c12bb34efeb65a6adb2b.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '2eeb2cc07dd3d0ba23696335da3163c1.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '1f0a8200402cb2b861a6cfb50ddd2450.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'ASDAS', 'ADASD', NULL, 9, '2026-09-17 05:16:35'),
(9, 7, 1, 'Original', 'eed4e374729d25be763323873eff3a01.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'cc974173d6973c41db5a981d320ee0ce.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '296531845aad0c2742688fb1c87daf2c.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'asdas', 'asdas', NULL, 9, '2026-09-18 03:03:54'),
(10, 8, 1, 'Original', '764440cbeb67f86f451e1e85d88081ec.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'b579a21b6b589636d32fc62cb87212cb.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'efb7d05d2e9f4954fba0cdabf98259ec.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'sadas', 'asdsa', NULL, 9, '2026-09-18 03:26:24');

-- --------------------------------------------------------

--
-- Table structure for table `grant_publications_ip_repository`
--

CREATE TABLE `grant_publications_ip_repository` (
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

--
-- Dumping data for table `grant_publications_ip_repository`
--

INSERT INTO `grant_publications_ip_repository` (`id`, `grant_application_id`, `submission_id`, `repository_reference`, `final_research_title`, `authors`, `abstract`, `publication_type`, `journal_conference`, `doi`, `publication_url`, `ip_information`, `copyright_info`, `patent_info`, `other_ip_info`, `final_pdf_path`, `final_pdf_original`, `supporting_files_json`, `verified_by_user_id`, `verified_by_name`, `verified_at`, `created_at`) VALUES
(1, 5, 1, 'PIP-2026-001', 'ai analysis', 'Student User', 'adasdsada', 'Journal', 'asdsa', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'sadas', NULL, NULL, NULL, 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/20fbebfce55289306d6bfbb63c4e3777.pdf', 'OLIVEROS CV.pdf', '[{\"path\":\"C:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/39c7dccbc6b58439374010ce48077be1.pdf\",\"original_name\":\"Diaz CV.pdf_20260813_105004_0000.pdf\",\"stored_name\":\"39c7dccbc6b58439374010ce48077be1.pdf\"}]', 3, 'User', '2026-08-31 16:40:51', '2026-08-31 16:40:51'),
(2, 7, 2, 'PIP-2026-002', 'Ai Driven Data Analytics', 'Student User', 'dasdasdas', 'Journal', 'dsadas', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'sadas', 'asd', 'asdas', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/64c266a836d68bc4468d4c06689b6aa8.pdf', 'file-sample_150kB.pdf', '[{\"path\":\"F:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/ae998301f6ce05be4836133a88d19de9.docx\",\"original_name\":\"OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx\",\"stored_name\":\"ae998301f6ce05be4836133a88d19de9.docx\"}]', 3, 'User', '2026-09-18 03:22:29', '2026-09-18 03:22:29'),
(3, 8, 3, 'PIP-2026-003', 'Ai Driven Data Analytics', 'Student User', 'asdasdas', 'Journal', 'asdas', '', 'http://localhost/sms2_system/modules/crad/pages/publications-ip.php', 'asdasd', NULL, NULL, NULL, 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/grant_final_output/23adf38391ef58c27b1a14ff52e79263.pdf', 'file-sample_150kB.pdf', '[{\"path\":\"F:\\\\xampp\\\\htdocs\\\\sms2_system\\/storage\\/uploads\\/grant_final_output_supporting\\/12c8f013bad6a439c5187808f9a772fc.docx\",\"original_name\":\"OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx\",\"stored_name\":\"12c8f013bad6a439c5187808f9a772fc.docx\"}]', 3, 'User', '2026-09-18 03:33:30', '2026-09-18 03:33:30');

-- --------------------------------------------------------

--
-- Table structure for table `manuscript_evaluations`
--

CREATE TABLE `manuscript_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `submission_id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `evaluator_user_id` int(10) UNSIGNED NOT NULL,
  `evaluator_name` varchar(150) NOT NULL DEFAULT '',
  `content_score` decimal(5,2) NOT NULL,
  `methodology_score` decimal(5,2) NOT NULL,
  `results_score` decimal(5,2) NOT NULL,
  `conclusions_score` decimal(5,2) NOT NULL,
  `recommendations_score` decimal(5,2) NOT NULL,
  `references_score` decimal(5,2) NOT NULL,
  `formatting_score` decimal(5,2) NOT NULL,
  `compliance_score` decimal(5,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `result` enum('APPROVED','FOR REVISION') NOT NULL,
  `overall_score` decimal(5,2) NOT NULL,
  `evaluated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manuscript_evaluations`
--

INSERT INTO `manuscript_evaluations` (`id`, `submission_id`, `research_group_id`, `evaluator_user_id`, `evaluator_name`, `content_score`, `methodology_score`, `results_score`, `conclusions_score`, `recommendations_score`, `references_score`, `formatting_score`, `compliance_score`, `remarks`, `result`, `overall_score`, `evaluated_at`, `created_at`) VALUES
(1, 0, 61, 3, '', 99.00, 99.00, 99.00, 99.00, 99.00, 99.00, 99.00, 99.00, '99', 'APPROVED', 99.00, '2026-08-28 08:03:48', '2026-08-28 08:03:48'),
(2, 2, 62, 3, '', 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, '100', 'APPROVED', 100.00, '2026-08-28 15:45:48', '2026-08-28 15:45:48'),
(3, 3, 63, 3, '', 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, '100', 'APPROVED', 100.00, '2026-08-28 16:35:30', '2026-08-28 16:35:30'),
(4, 4, 64, 3, '', 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, '', 'APPROVED', 100.00, '2026-08-31 06:17:25', '2026-08-31 06:17:25'),
(5, 5, 71, 3, '', 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, '', 'APPROVED', 96.00, '2026-09-19 12:44:44', '2026-09-19 12:44:44'),
(6, 6, 72, 3, '', 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, 12.00, '', 'APPROVED', 96.00, '2026-09-19 13:35:34', '2026-09-19 13:35:34');

-- --------------------------------------------------------

--
-- Table structure for table `manuscript_submissions`
--

CREATE TABLE `manuscript_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `version_number` int(10) UNSIGNED NOT NULL,
  `status` enum('Submitted','Under Review','For Revision','Approved') NOT NULL DEFAULT 'Submitted',
  `submitted_by_user` int(10) UNSIGNED DEFAULT NULL,
  `submitted_by_name` varchar(150) NOT NULL DEFAULT '',
  `submitted_by_email` varchar(190) NOT NULL DEFAULT '',
  `submission_notes` text DEFAULT NULL,
  `original_name` varchar(255) NOT NULL DEFAULT '',
  `stored_subdir` varchar(180) NOT NULL DEFAULT '',
  `stored_name` varchar(120) NOT NULL DEFAULT '',
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `file_mime` varchar(120) NOT NULL DEFAULT '',
  `submission_token` varchar(64) NOT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manuscript_submissions`
--

INSERT INTO `manuscript_submissions` (`id`, `research_group_id`, `version_number`, `status`, `submitted_by_user`, `submitted_by_name`, `submitted_by_email`, `submission_notes`, `original_name`, `stored_subdir`, `stored_name`, `file_size`, `file_mime`, `submission_token`, `submitted_at`, `reviewed_at`, `updated_at`) VALUES
(1, 61, 1, 'Approved', 9, '', 'kenlangmalakas0308@gmail.com', '', 'CRAD_Chapter_1_TO_4 (1).docx', 'manuscripts/g61', '059624a21b3ee3e8c9d5d6d6cae4d3d8.docx', 4824980, 'application/octet-stream', 'c0ba01bd7d587e02c4aac89baad005b9817863f67e132d46c53fd5a8e2f202b8', '2026-08-28 08:01:38', '2026-08-28 08:03:48', '2026-08-28 08:03:48'),
(2, 62, 1, 'Approved', 9, '', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'manuscripts/g62', 'cd889c4f0fa3737edd9c7075cae72eb5.docx', 302605, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'ac45628a1a25f5f177bbcace7b945c684229993d6462e2d2dba7551d88a01198', '2026-08-28 15:45:09', '2026-08-28 15:45:48', '2026-08-28 15:45:48'),
(3, 63, 1, 'Approved', 9, '', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'manuscripts/g63', 'c4d2449c98f39a5604fedf92ac2cc70e.docx', 302605, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '4be87c933edc484ef2cddf1a86daa6e4521cc149c28b66b278d67db825ffdd44', '2026-08-28 16:34:34', '2026-08-28 16:35:30', '2026-08-28 16:35:30'),
(4, 64, 1, 'Approved', 9, '', 'kenlangmalakas0308@gmail.com', '', 'OLIVEROS CV.pdf', 'manuscripts/g64', '5fa67590cfadb682bbf15dcef84b36d5.pdf', 294354, 'application/pdf', '750a785eb19895c7c3df2b3ff9ed349d8f57813089bb7bdbe2203b7228ca27db', '2026-08-31 06:16:23', '2026-08-31 06:17:25', '2026-08-31 06:17:25'),
(5, 71, 1, 'Approved', 1354, '', 'kennethabejuela@yahoo.com', '', 'Research-Services-Clearance-V6-with-New-CRAD-Logo-2.docx', 'manuscripts/g71', '2fa13acf8b1858bd3d948abb5fa6240d.docx', 180422, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '100e1ff22cb074500ccb65bb5da8b7eea647dce4fbdbe6562a973772edd6e85e', '2026-09-19 12:40:49', '2026-09-19 12:44:44', '2026-09-19 12:44:44'),
(6, 72, 1, 'Approved', 1354, '', 'kennethabejuela@yahoo.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'manuscripts/g72', 'd583d12c09e4be669db6e251f47f1bbc.docx', 287104, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'c6aa32cd661262a0194a528dac6b7bfc2021be7a30614d00158bef194762ac3d', '2026-09-19 13:35:08', '2026-09-19 13:35:34', '2026-09-19 13:35:34');

-- --------------------------------------------------------

--
-- Table structure for table `panel_assignment_notifications`
--

CREATE TABLE `panel_assignment_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_key` varchar(140) NOT NULL,
  `recipient_user_id` int(10) UNSIGNED NOT NULL,
  `recipient_role` varchar(60) NOT NULL DEFAULT 'panel',
  `recipient_email` varchar(190) NOT NULL DEFAULT '',
  `panel_assignment_id` int(10) UNSIGNED DEFAULT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(160) NOT NULL DEFAULT '',
  `body` text DEFAULT NULL,
  `url` varchar(500) NOT NULL DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `panel_assignment_notifications`
--

INSERT INTO `panel_assignment_notifications` (`id`, `event_key`, `recipient_user_id`, `recipient_role`, `recipient_email`, `panel_assignment_id`, `research_group_id`, `title`, `body`, `url`, `is_read`, `created_at`) VALUES
(19, 'preoral-panel-assignment:61:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 13, 61, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ANALYSIS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-23 04:09:43'),
(20, 'preoral-panel-assignment:61:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 14, 61, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ANALYSIS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-23 04:09:43'),
(21, 'preoral-panel-assignment:61:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 15, 61, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ANALYSIS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-23 04:09:43'),
(22, 'preoral-defense-finalized:s30:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 13, 61, 'Pre-Oral Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Aug 25, 2026 01:00 PM - 02:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=30', 0, '2026-08-23 04:10:47'),
(23, 'preoral-defense-finalized:s30:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 14, 61, 'Pre-Oral Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Aug 25, 2026 01:00 PM - 02:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=30', 0, '2026-08-23 04:10:47'),
(24, 'preoral-defense-finalized:s30:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 15, 61, 'Pre-Oral Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Aug 25, 2026 01:00 PM - 02:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=30', 0, '2026-08-23 04:10:47'),
(25, 'preoral-defense-finalized:s32:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 13, 61, 'Pre-Oral Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Aug 29, 2026 10:15 AM - 11:16 AM\nVenue: Computer Laboratory 1', '/sms2-capstone-main/sms2-capstone-main/modules/faculty/pages/defense-details.php?id=32', 0, '2026-08-28 08:21:42'),
(26, 'preoral-defense-finalized:s32:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 14, 61, 'Pre-Oral Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Aug 29, 2026 10:15 AM - 11:16 AM\nVenue: Computer Laboratory 1', '/sms2-capstone-main/sms2-capstone-main/modules/faculty/pages/defense-details.php?id=32', 0, '2026-08-28 08:21:42'),
(27, 'preoral-defense-finalized:s32:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 15, 61, 'Pre-Oral Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Aug 29, 2026 10:15 AM - 11:16 AM\nVenue: Computer Laboratory 1', '/sms2-capstone-main/sms2-capstone-main/modules/faculty/pages/defense-details.php?id=32', 0, '2026-08-28 08:21:42'),
(28, 'preoral-panel-assignment:62:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 19, 62, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ASSISTED DOCUMENT\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-28 15:34:33'),
(29, 'preoral-panel-assignment:62:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 20, 62, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ASSISTED DOCUMENT\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-28 15:34:33'),
(30, 'preoral-panel-assignment:62:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 21, 62, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ASSISTED DOCUMENT\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-28 15:34:33'),
(31, 'pre-oral-finalized:s36:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 19, 62, 'Pre-Oral Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ASSISTED DOCUMENT\nDate/Time: Sep 1, 2026 03:00 PM - 04:00 PM\nVenue: AVR Room', '/sms2_system/modules/faculty/pages/defense-details.php?id=36', 0, '2026-08-28 15:35:40'),
(32, 'pre-oral-finalized:s36:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 20, 62, 'Pre-Oral Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ASSISTED DOCUMENT\nDate/Time: Sep 1, 2026 03:00 PM - 04:00 PM\nVenue: AVR Room', '/sms2_system/modules/faculty/pages/defense-details.php?id=36', 0, '2026-08-28 15:35:40'),
(33, 'pre-oral-finalized:s36:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 21, 62, 'Pre-Oral Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ASSISTED DOCUMENT\nDate/Time: Sep 1, 2026 03:00 PM - 04:00 PM\nVenue: AVR Room', '/sms2_system/modules/faculty/pages/defense-details.php?id=36', 0, '2026-08-28 15:35:40'),
(34, 'final-defense-finalized:s38:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 19, 62, 'Final Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ASSISTED DOCUMENT\nDate/Time: Sep 10, 2026 12:00 PM - 01:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=38', 0, '2026-08-28 15:52:23'),
(35, 'final-defense-finalized:s38:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 20, 62, 'Final Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ASSISTED DOCUMENT\nDate/Time: Sep 10, 2026 12:00 PM - 01:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=38', 0, '2026-08-28 15:52:23'),
(36, 'final-defense-finalized:s38:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 21, 62, 'Final Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ASSISTED DOCUMENT\nDate/Time: Sep 10, 2026 12:00 PM - 01:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=38', 0, '2026-08-28 15:52:23'),
(37, 'preoral-panel-assignment:63:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 25, 63, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-28 16:31:11'),
(38, 'preoral-panel-assignment:63:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 26, 63, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-28 16:31:11'),
(39, 'preoral-panel-assignment:63:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 27, 63, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-28 16:31:11'),
(40, 'pre-oral-finalized:s41:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 25, 63, 'Pre-Oral Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI\nDate/Time: Aug 29, 2026 11:00 AM - 12:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=41', 0, '2026-08-28 16:32:15'),
(41, 'pre-oral-finalized:s41:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 26, 63, 'Pre-Oral Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI\nDate/Time: Aug 29, 2026 11:00 AM - 12:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=41', 0, '2026-08-28 16:32:15'),
(42, 'pre-oral-finalized:s41:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 27, 63, 'Pre-Oral Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI\nDate/Time: Aug 29, 2026 11:00 AM - 12:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=41', 0, '2026-08-28 16:32:15'),
(43, 'final-defense-finalized:s44:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 25, 63, 'Final Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI\nDate/Time: Aug 31, 2026 12:00 PM - 01:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=44', 0, '2026-08-28 16:37:05'),
(44, 'final-defense-finalized:s44:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 26, 63, 'Final Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI\nDate/Time: Aug 31, 2026 12:00 PM - 01:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=44', 0, '2026-08-28 16:37:05'),
(45, 'final-defense-finalized:s44:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 27, 63, 'Final Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI\nDate/Time: Aug 31, 2026 12:00 PM - 01:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=44', 0, '2026-08-28 16:37:05'),
(46, 'preoral-panel-assignment:64:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 31, 64, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ANALYSIS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-31 05:34:23'),
(47, 'preoral-panel-assignment:64:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 32, 64, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ANALYSIS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-31 05:34:23'),
(48, 'preoral-panel-assignment:64:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 33, 64, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ANALYSIS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-31 05:34:23'),
(52, 'pre-oral-finalized:s48:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 31, 64, 'Pre-Oral Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Aug 31, 2026 01:00 PM - 02:00 PM\nVenue: AVR Room', '/sms2_system/modules/faculty/pages/defense-details.php?id=48', 0, '2026-08-31 06:10:27'),
(53, 'pre-oral-finalized:s48:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 32, 64, 'Pre-Oral Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Aug 31, 2026 01:00 PM - 02:00 PM\nVenue: AVR Room', '/sms2_system/modules/faculty/pages/defense-details.php?id=48', 0, '2026-08-31 06:10:27'),
(54, 'pre-oral-finalized:s48:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 33, 64, 'Pre-Oral Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Aug 31, 2026 01:00 PM - 02:00 PM\nVenue: AVR Room', '/sms2_system/modules/faculty/pages/defense-details.php?id=48', 0, '2026-08-31 06:10:27'),
(55, 'final-defense-finalized:s50:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 31, 64, 'Final Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Sep 3, 2026 11:00 AM - 12:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=50', 0, '2026-08-31 06:22:35'),
(56, 'final-defense-finalized:s50:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 32, 64, 'Final Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Sep 3, 2026 11:00 AM - 12:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=50', 0, '2026-08-31 06:22:35'),
(57, 'final-defense-finalized:s50:u493', 493, 'panel', 'michelleguevarra@bestlink.edu.ph', 33, 64, 'Final Defense Scheduled', 'RG-2026-001\nDEVELOPMENT OF AI ANALYSIS\nDate/Time: Sep 3, 2026 11:00 AM - 12:00 PM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=50', 0, '2026-08-31 06:22:35'),
(58, 'preoral-panel-assignment:65:u491', 491, 'panel', 'jobert.valentino@bestlink.edu.ph', 37, 65, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ANALYSIS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-31 09:43:31'),
(59, 'preoral-panel-assignment:65:u492', 492, 'panel', 'jonathan.estrada@bestlink.edu.ph', 38, 65, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ANALYSIS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-31 09:43:31'),
(60, 'preoral-panel-assignment:65:u493', 493, 'panel', 'michelle.guevarra@bestlink.edu.ph', 39, 65, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ANALYSIS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-31 09:43:31'),
(61, 'preoral-panel-assignment:69:u990', 990, 'department_chair', 'deptchair@bestlink.edu.ph', 40, 69, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 68\nASDASD\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-068', 0, '2026-09-19 00:47:46'),
(62, 'preoral-panel-assignment:69:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 41, 69, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 68\nASDASD\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-068', 0, '2026-09-19 00:47:46'),
(63, 'preoral-panel-assignment:69:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 42, 69, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 68\nASDASD\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-068', 0, '2026-09-19 00:47:46'),
(64, 'preoral-panel-assignment:71:u990', 990, 'department_chair', 'deptchair@bestlink.edu.ph', 43, 71, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 68\nAI DRIVEN DATA ANALYTICS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-068', 0, '2026-09-19 12:20:40'),
(65, 'preoral-panel-assignment:71:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 44, 71, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 68\nAI DRIVEN DATA ANALYTICS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-068', 0, '2026-09-19 12:20:40'),
(66, 'preoral-panel-assignment:71:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 45, 71, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 68\nAI DRIVEN DATA ANALYTICS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-068', 0, '2026-09-19 12:20:40'),
(67, 'pre-oral-finalized:s66:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 44, 71, 'Pre-Oral Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Sep 25, 2026 09:00 AM - 11:00 AM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=66', 0, '2026-09-19 12:21:15'),
(68, 'pre-oral-finalized:s66:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 45, 71, 'Pre-Oral Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Sep 25, 2026 09:00 AM - 11:00 AM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=66', 0, '2026-09-19 12:21:15'),
(69, 'pre-oral-finalized:s66:u990', 990, 'panel', 'deptchair@bestlink.edu.ph', 43, 71, 'Pre-Oral Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Sep 25, 2026 09:00 AM - 11:00 AM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=66', 0, '2026-09-19 12:21:15'),
(70, 'final-defense-finalized:s68:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 44, 71, 'Final Defense Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Sep 23, 2026 09:00 AM - 11:00 AM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=68', 0, '2026-09-19 13:10:38'),
(71, 'final-defense-finalized:s68:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 45, 71, 'Final Defense Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Sep 23, 2026 09:00 AM - 11:00 AM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=68', 0, '2026-09-19 13:10:38'),
(72, 'final-defense-finalized:s68:u990', 990, 'panel', 'deptchair@bestlink.edu.ph', 43, 71, 'Final Defense Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Sep 23, 2026 09:00 AM - 11:00 AM\nVenue: Computer Laboratory 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=68', 0, '2026-09-19 13:10:38'),
(73, 'preoral-panel-assignment:72:u990', 990, 'department_chair', 'deptchair@bestlink.edu.ph', 49, 72, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 68\nAI DRIVEN DATA ANALYTICS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-068', 0, '2026-09-19 13:32:07'),
(74, 'preoral-panel-assignment:72:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 50, 72, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 68\nAI DRIVEN DATA ANALYTICS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-068', 0, '2026-09-19 13:32:07'),
(75, 'preoral-panel-assignment:72:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 51, 72, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 68\nAI DRIVEN DATA ANALYTICS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-068', 0, '2026-09-19 13:32:07'),
(76, 'pre-oral-finalized:s71:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 50, 72, 'Pre-Oral Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Sep 24, 2026 01:00 PM - 03:00 PM\nVenue: Research Room 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=71', 0, '2026-09-19 13:32:49'),
(77, 'pre-oral-finalized:s71:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 51, 72, 'Pre-Oral Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Sep 24, 2026 01:00 PM - 03:00 PM\nVenue: Research Room 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=71', 0, '2026-09-19 13:32:49'),
(78, 'pre-oral-finalized:s71:u990', 990, 'panel', 'deptchair@bestlink.edu.ph', 49, 72, 'Pre-Oral Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Sep 24, 2026 01:00 PM - 03:00 PM\nVenue: Research Room 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=71', 0, '2026-09-19 13:32:49'),
(79, 'final-defense-finalized:s77:u491', 491, 'panel', 'jobertvalentino@bestlink.edu.ph', 50, 72, 'Final Defense Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Oct 5, 2026 01:00 PM - 03:00 PM\nVenue: Research Room 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=77', 0, '2026-09-19 13:44:19'),
(80, 'final-defense-finalized:s77:u492', 492, 'panel', 'jonathanestrada@bestlink.edu.ph', 51, 72, 'Final Defense Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Oct 5, 2026 01:00 PM - 03:00 PM\nVenue: Research Room 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=77', 0, '2026-09-19 13:44:19'),
(81, 'final-defense-finalized:s77:u990', 990, 'panel', 'deptchair@bestlink.edu.ph', 49, 72, 'Final Defense Scheduled', 'RG-2026-068\nAI DRIVEN DATA ANALYTICS\nDate/Time: Oct 5, 2026 01:00 PM - 03:00 PM\nVenue: Research Room 1', '/sms2_system/modules/faculty/pages/defense-details.php?id=77', 0, '2026-09-19 13:44:19');

-- --------------------------------------------------------

--
-- Table structure for table `panel_member_availability`
--

CREATE TABLE `panel_member_availability` (
  `id` int(10) UNSIGNED NOT NULL,
  `panel_user_id` int(10) UNSIGNED NOT NULL,
  `availability_status` varchar(40) NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `panel_member_availability`
--

INSERT INTO `panel_member_availability` (`id`, `panel_user_id`, `availability_status`, `notes`, `updated_at`, `created_at`) VALUES
(6, 491, 'Available', '', '2026-08-23 04:08:58', '2026-08-23 04:08:58'),
(7, 492, 'Available', '', '2026-08-23 04:09:10', '2026-08-23 04:09:10'),
(8, 493, 'Available', '', '2026-08-23 04:09:26', '2026-08-23 04:09:26'),
(9, 990, 'Available', '', '2026-09-19 00:46:54', '2026-09-19 00:46:54');

-- --------------------------------------------------------

--
-- Table structure for table `preoral_defense_evaluations`
--

CREATE TABLE `preoral_defense_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `defense_schedule_id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `panel_user_id` int(10) UNSIGNED NOT NULL,
  `panel_name` varchar(150) NOT NULL DEFAULT '',
  `content_score` decimal(5,2) NOT NULL,
  `methodology_score` decimal(5,2) NOT NULL,
  `references_score` decimal(5,2) NOT NULL,
  `format_score` decimal(5,2) NOT NULL,
  `defense_score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `result` enum('APPROVED','APPROVED WITH REVISION','FAILED') NOT NULL,
  `overall_score` decimal(5,2) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposal_documents`
--

CREATE TABLE `proposal_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `proposal_id` int(10) UNSIGNED NOT NULL,
  `doc_key` varchar(60) NOT NULL COMMENT 'Slot key: manuscript, approval, abstract, etc.',
  `doc_title` varchar(200) NOT NULL,
  `original_name` varchar(300) NOT NULL,
  `stored_name` varchar(300) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Bytes',
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposal_drafts`
--

CREATE TABLE `proposal_drafts` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to sms2_db users (optional)',
  `form_type` varchar(30) NOT NULL DEFAULT 'document',
  `revision_ref` varchar(30) NOT NULL DEFAULT '' COMMENT 'Returned proposal ref when draft is for revision',
  `draft_data` longtext NOT NULL COMMENT 'JSON encoded draft form fields except upload files',
  `signature_data` mediumtext DEFAULT NULL COMMENT 'Base64 PNG of representative signature draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposal_members`
--

CREATE TABLE `proposal_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `proposal_id` int(10) UNSIGNED NOT NULL,
  `sort_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1 = lead member',
  `student_id` varchar(50) NOT NULL,
  `student_name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposal_status_logs`
--

CREATE TABLE `proposal_status_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `proposal_id` int(10) UNSIGNED NOT NULL,
  `old_status` varchar(30) DEFAULT NULL,
  `new_status` varchar(30) NOT NULL,
  `changed_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to sms2_db users',
  `remarks` text DEFAULT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publications`
--

CREATE TABLE `publications` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(500) NOT NULL DEFAULT '',
  `authors` text DEFAULT NULL,
  `publication_outlet` varchar(255) NOT NULL DEFAULT '',
  `publication_date` date DEFAULT NULL,
  `doi_link` varchar(500) NOT NULL DEFAULT '',
  `status` enum('Draft','For Publication','Published','Archived') NOT NULL DEFAULT 'Draft',
  `notes` text DEFAULT NULL,
  `created_by_user` int(10) UNSIGNED DEFAULT NULL,
  `created_by_name` varchar(150) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `publications`
--

INSERT INTO `publications` (`id`, `research_group_id`, `title`, `authors`, `publication_outlet`, `publication_date`, `doi_link`, `status`, `notes`, `created_by_user`, `created_by_name`, `created_at`, `updated_at`) VALUES
(4, 62, 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'Group 01', '', '2026-08-29', 'i3o213uo1u3oi12', 'Published', '', 3, '', '2026-08-28 16:13:42', '2026-08-28 16:14:09'),
(5, 63, 'DEVELOPMENT OF AI', 'Group 01', '', NULL, '', 'Draft', NULL, 3, '', '2026-08-28 16:39:18', '2026-08-28 16:39:18'),
(6, 64, 'DEVELOPMENT OF AI ANALYSIS', 'Group 01', 'crad', '2026-08-31', 'http://localhost/sms2_system/modules/crad/pages/documentation-publication-management.php', 'Published', '', 3, '', '2026-08-31 06:32:29', '2026-08-31 06:33:01'),
(7, 72, 'AI DRIVEN DATA ANALYTICS', 'Group 68', 'KEnneth', '2026-09-19', 'http://localhost/sms2_system/modules/crad/pages/documentation-publication-management.php', 'Published', '', 3, '', '2026-09-19 13:48:37', '2026-09-19 13:48:54');

-- --------------------------------------------------------

--
-- Table structure for table `research_adviser_assignments`
--

CREATE TABLE `research_adviser_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_number` varchar(30) DEFAULT NULL,
  `group_number` varchar(40) DEFAULT NULL,
  `student_id` varchar(40) DEFAULT NULL,
  `adviser_name` varchar(150) NOT NULL DEFAULT '',
  `adviser_email` varchar(190) NOT NULL DEFAULT '',
  `adviser_user_id` int(10) UNSIGNED DEFAULT NULL,
  `expertise` varchar(255) NOT NULL DEFAULT '',
  `availability_status` varchar(40) NOT NULL DEFAULT 'Pending',
  `assignment_status` varchar(40) NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notification_sent_at` datetime DEFAULT NULL,
  `notification_sent_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_clearance_notifications`
--

CREATE TABLE `research_clearance_notifications` (
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

--
-- Dumping data for table `research_clearance_notifications`
--

INSERT INTO `research_clearance_notifications` (`id`, `event_key`, `recipient_user_id`, `recipient_role`, `recipient_email`, `clearance_id`, `type`, `title`, `body`, `url`, `is_read`, `created_at`) VALUES
(9, 'clearance-payment:1', 1354, 'student', '', 0, 'payment_approved', 'College payment approved', 'Your college payment was approved. The O.R. number and remarks are now on your Research Services Clearance form.', '/sms2_system/modules/student-portal/pages/research-clearance.php', 0, '2026-09-19 12:07:01'),
(15, 'clearance-payment:2', 1354, 'student', '', 0, 'payment_approved', 'Research 2 college payment approved', 'Your Research 2 college payment was approved. The O.R. number and remarks are now on that Research Services Clearance form.', '/sms2_system/modules/student-portal/pages/research-clearance.php', 0, '2026-09-19 13:00:57'),
(20, 'clearance-payment:3', 1354, 'student', '', 0, 'payment_approved', 'Research 1 collage payment approved', 'Your Research 1 collage payment was approved. The O.R. number and remarks are now on that Research Services Clearance form.', '/sms2_system/modules/student-portal/pages/research-clearance.php', 0, '2026-09-19 13:29:42'),
(25, 'clearance-payment:4', 1354, 'student', '', 0, 'payment_approved', 'Research 2 collage payment approved', 'Your Research 2 collage payment was approved. The O.R. number and remarks are now on that Research Services Clearance form.', '/sms2_system/modules/student-portal/pages/research-clearance.php', 0, '2026-09-19 13:36:32');

-- --------------------------------------------------------

--
-- Table structure for table `research_clearance_payments`
--

CREATE TABLE `research_clearance_payments` (
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

-- --------------------------------------------------------

--
-- Table structure for table `research_coordinator_assignments`
--

CREATE TABLE `research_coordinator_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_id` int(10) UNSIGNED DEFAULT NULL,
  `title_approval_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_number` varchar(30) DEFAULT NULL,
  `group_number` varchar(40) DEFAULT NULL,
  `group_name` varchar(120) NOT NULL DEFAULT '',
  `research_title` varchar(255) NOT NULL DEFAULT '',
  `student_id` varchar(40) DEFAULT NULL,
  `coordinator_user_id` int(10) UNSIGNED DEFAULT NULL,
  `coordinator_name` varchar(200) NOT NULL DEFAULT '',
  `coordinator_email` varchar(200) NOT NULL DEFAULT '',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_defense_schedules`
--

CREATE TABLE `research_defense_schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_number` varchar(30) DEFAULT NULL,
  `group_number` varchar(40) NOT NULL,
  `research_group` varchar(120) NOT NULL,
  `research_title` varchar(255) NOT NULL,
  `adviser_name` varchar(160) DEFAULT NULL,
  `panel_members` text DEFAULT NULL,
  `panel_chair` varchar(160) DEFAULT NULL,
  `venue` varchar(120) DEFAULT NULL,
  `venue_id` int(10) UNSIGNED DEFAULT NULL,
  `defense_datetime` datetime DEFAULT NULL,
  `defense_end_datetime` datetime DEFAULT NULL,
  `defense_type` varchar(40) NOT NULL DEFAULT 'Pre-Oral',
  `status` varchar(40) NOT NULL DEFAULT 'Ready for Scheduling',
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `finalized_by` int(10) UNSIGNED DEFAULT NULL,
  `finalized_at` datetime DEFAULT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_defense_schedules`
--

INSERT INTO `research_defense_schedules` (`id`, `research_group_id`, `proposal_id`, `proposal_number`, `group_number`, `research_group`, `research_title`, `adviser_name`, `panel_members`, `panel_chair`, `venue`, `venue_id`, `defense_datetime`, `defense_end_datetime`, `defense_type`, `status`, `recorded_by`, `finalized_by`, `finalized_at`, `recorded_at`, `updated_at`) VALUES
(70, 72, NULL, 'TAP-2026-00060', 'RG-2026-068', 'Group 68', 'AI DRIVEN DATA ANALYTICS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Joseph Alcantara', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-09-21 08:00:00', '2026-09-21 10:00:00', 'Pre-Oral', 'Rejected', 758, NULL, NULL, '2026-09-19 13:32:39', '2026-09-19 13:32:49'),
(71, 72, NULL, 'TAP-2026-00060', 'RG-2026-068', 'Group 68', 'AI DRIVEN DATA ANALYTICS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Joseph Alcantara', 'Dr. Jobert Valentino', 'Research Room 1', 2, '2026-09-24 13:00:00', '2026-09-24 15:00:00', 'Pre-Oral', 'Finalized', 758, 758, '2026-09-19 13:32:49', '2026-09-19 13:32:39', '2026-09-19 13:32:49'),
(72, 72, NULL, 'TAP-2026-00060', 'RG-2026-068', 'Group 68', 'AI DRIVEN DATA ANALYTICS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Joseph Alcantara', 'Dr. Jobert Valentino', 'Research Room 2', 3, '2026-09-28 09:00:00', '2026-09-28 11:00:00', 'Pre-Oral', 'Rejected', 758, NULL, NULL, '2026-09-19 13:32:39', '2026-09-19 13:32:49'),
(76, 72, NULL, 'TAP-2026-00060', 'RG-2026-068', 'Group 68', 'AI DRIVEN DATA ANALYTICS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Joseph Alcantara', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-09-30 08:00:00', '2026-09-30 10:00:00', 'Final Defense', 'Rejected', 758, NULL, NULL, '2026-09-19 13:44:09', '2026-09-19 13:44:19'),
(77, 72, NULL, 'TAP-2026-00060', 'RG-2026-068', 'Group 68', 'AI DRIVEN DATA ANALYTICS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Joseph Alcantara', 'Dr. Jobert Valentino', 'Research Room 1', 2, '2026-10-05 13:00:00', '2026-10-05 15:00:00', 'Final Defense', 'Finalized', 758, 758, '2026-09-19 13:44:19', '2026-09-19 13:44:09', '2026-09-19 13:44:19'),
(78, 72, NULL, 'TAP-2026-00060', 'RG-2026-068', 'Group 68', 'AI DRIVEN DATA ANALYTICS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Joseph Alcantara', 'Dr. Jobert Valentino', 'Research Room 2', 3, '2026-10-08 09:00:00', '2026-10-08 11:00:00', 'Final Defense', 'Rejected', 758, NULL, NULL, '2026-09-19 13:44:09', '2026-09-19 13:44:19');

-- --------------------------------------------------------

--
-- Table structure for table `research_groups`
--

CREATE TABLE `research_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `proposal_id` int(10) UNSIGNED DEFAULT NULL,
  `title_approval_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_number` varchar(30) DEFAULT NULL,
  `group_number` varchar(40) NOT NULL,
  `group_name` varchar(40) NOT NULL DEFAULT '',
  `research_title` varchar(255) NOT NULL DEFAULT '',
  `college_dept` varchar(120) NOT NULL DEFAULT '',
  `adviser` varchar(120) NOT NULL DEFAULT '',
  `academic_year` varchar(20) NOT NULL DEFAULT '',
  `leader_name` varchar(120) NOT NULL DEFAULT '',
  `leader_id` varchar(40) NOT NULL DEFAULT '',
  `leader_email` varchar(120) NOT NULL DEFAULT '',
  `leader_contact` varchar(40) NOT NULL DEFAULT '',
  `status` varchar(40) NOT NULL DEFAULT 'Approved',
  `date_assigned` date NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_groups`
--

INSERT INTO `research_groups` (`id`, `proposal_id`, `title_approval_id`, `proposal_number`, `group_number`, `group_name`, `research_title`, `college_dept`, `adviser`, `academic_year`, `leader_name`, `leader_id`, `leader_email`, `leader_contact`, `status`, `date_assigned`, `created_by`, `created_at`) VALUES
(67, NULL, NULL, NULL, 'STU-S230106713', 'John Kenneth Abejuela', 'Pending Title Approval', '', '', '2026-2027', 'John Kenneth Abejuela', 'S230106713', '', '', 'Pending Assignment', '2026-09-18', 758, '2026-09-18 15:11:09');

--
-- Triggers `research_groups`
--
DELIMITER $$
CREATE TRIGGER `trg_research_groups_panel_notifications_after_delete` AFTER DELETE ON `research_groups` FOR EACH ROW BEGIN
                DELETE FROM panel_assignment_notifications
                WHERE research_group_id = OLD.id;
            END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_research_groups_preoral_evals_after_delete` AFTER DELETE ON `research_groups` FOR EACH ROW BEGIN
                DELETE FROM preoral_defense_evaluations
                WHERE research_group_id = OLD.id;
            END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_research_groups_preoral_evaluations_after_delete` AFTER DELETE ON `research_groups` FOR EACH ROW BEGIN
                DELETE FROM preoral_defense_evaluations
                WHERE research_group_id = OLD.id;
            END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `research_milestones`
--

CREATE TABLE `research_milestones` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_plan_id` int(10) UNSIGNED NOT NULL,
  `milestone_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `milestone_order` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `weight` decimal(5,2) NOT NULL DEFAULT 1.00 COMMENT 'For weighted progress calculation',
  `status` enum('Not Started','In Progress','Submitted for Review','Revision Requested','Approved','Completed') NOT NULL DEFAULT 'Not Started',
  `start_date` date DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `researcher_notes` text DEFAULT NULL,
  `adviser_remarks` text DEFAULT NULL,
  `panel_remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_milestones`
--

INSERT INTO `research_milestones` (`id`, `research_plan_id`, `milestone_name`, `description`, `milestone_order`, `progress_percentage`, `weight`, `status`, `start_date`, `target_date`, `completed_at`, `researcher_notes`, `adviser_remarks`, `panel_remarks`, `created_at`, `updated_at`) VALUES
(175, 21, 'Chapter 4', 'Results / System Design and Development', 4, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-23 03:47:51', '2026-08-23 03:47:51'),
(176, 21, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-23 03:47:51', '2026-08-23 03:47:51'),
(177, 22, 'Chapter 1', 'Introduction and Background', 1, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-23 04:11:53', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-23 03:49:51', '2026-08-23 04:11:53'),
(178, 22, 'Chapter 2', 'Review of Related Literature', 2, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-23 04:11:53', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-23 03:49:51', '2026-08-23 04:11:53'),
(179, 22, 'Chapter 3', 'Methodology', 3, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-23 04:11:53', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-23 03:49:51', '2026-08-23 04:11:53'),
(180, 22, 'Chapter 4', 'Results / System Design and Development', 4, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-23 04:13:25', NULL, 'asd', NULL, '2026-08-23 03:49:51', '2026-08-23 04:13:25'),
(181, 22, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-23 04:13:35', NULL, 'Progress approved.', NULL, '2026-08-23 03:49:51', '2026-08-23 04:13:35'),
(182, 22, 'System Development', 'System Implementation', 6, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-23 04:13:45', NULL, 'Progress approved.', NULL, '2026-08-23 03:49:51', '2026-08-23 04:13:45'),
(183, 22, 'Testing', 'Testing and Quality Assurance', 7, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-23 04:13:52', NULL, 'Progress approved.', NULL, '2026-08-23 03:49:51', '2026-08-23 04:13:52'),
(184, 22, 'Documentation', 'Final Documentation and Report', 8, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-23 04:13:59', NULL, 'Progress approved.', NULL, '2026-08-23 03:49:51', '2026-08-23 04:13:59'),
(185, 23, 'Chapter 1', 'Introduction and Background', 1, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 15:37:11', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-28 15:30:09', '2026-08-28 15:37:11'),
(186, 23, 'Chapter 2', 'Review of Related Literature', 2, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 15:37:11', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-28 15:30:09', '2026-08-28 15:37:11'),
(187, 23, 'Chapter 3', 'Methodology', 3, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 15:37:11', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-28 15:30:09', '2026-08-28 15:37:11'),
(188, 23, 'Chapter 4', 'Results / System Design and Development', 4, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 15:37:17', NULL, 'Progress approved.', NULL, '2026-08-28 15:30:09', '2026-08-28 15:37:17'),
(189, 23, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 15:37:22', NULL, 'Progress approved.', NULL, '2026-08-28 15:30:09', '2026-08-28 15:37:22'),
(190, 23, 'System Development', 'System Implementation', 6, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 15:37:29', NULL, 'Progress approved.', NULL, '2026-08-28 15:30:09', '2026-08-28 15:37:29'),
(191, 23, 'Testing', 'Testing and Quality Assurance', 7, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 15:37:35', NULL, 'Progress approved.', NULL, '2026-08-28 15:30:09', '2026-08-28 15:37:35'),
(192, 23, 'Documentation', 'Final Documentation and Report', 8, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 15:37:44', NULL, 'Progress approved.', NULL, '2026-08-28 15:30:09', '2026-08-28 15:37:44'),
(193, 24, 'Chapter 1', 'Introduction and Background', 1, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 16:33:40', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-28 16:28:29', '2026-08-28 16:33:40'),
(194, 24, 'Chapter 2', 'Review of Related Literature', 2, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 16:33:40', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-28 16:28:29', '2026-08-28 16:33:40'),
(195, 24, 'Chapter 3', 'Methodology', 3, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 16:33:40', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-28 16:28:29', '2026-08-28 16:33:40'),
(196, 24, 'Chapter 4', 'Results / System Design and Development', 4, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 16:33:58', NULL, 'Progress approved.', NULL, '2026-08-28 16:28:29', '2026-08-28 16:33:58'),
(197, 24, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 16:34:03', NULL, 'Progress approved.', NULL, '2026-08-28 16:28:29', '2026-08-28 16:34:03'),
(198, 24, 'System Development', 'System Implementation', 6, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 16:34:11', NULL, 'Progress approved.', NULL, '2026-08-28 16:28:29', '2026-08-28 16:34:11'),
(199, 24, 'Testing', 'Testing and Quality Assurance', 7, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 16:34:16', NULL, 'Progress approved.', NULL, '2026-08-28 16:28:29', '2026-08-28 16:34:16'),
(200, 24, 'Documentation', 'Final Documentation and Report', 8, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-28 16:34:23', NULL, 'Progress approved.', NULL, '2026-08-28 16:28:29', '2026-08-28 16:34:23'),
(201, 25, 'Chapter 1', 'Introduction and Background', 1, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-31 06:14:23', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-31 05:28:10', '2026-08-31 06:14:23'),
(202, 25, 'Chapter 2', 'Review of Related Literature', 2, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-31 06:14:23', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-31 05:28:10', '2026-08-31 06:14:23'),
(203, 25, 'Chapter 3', 'Methodology', 3, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-31 06:14:23', NULL, 'Progress approved.', 'Approved by Panel.', '2026-08-31 05:28:10', '2026-08-31 06:14:23'),
(204, 25, 'Chapter 4', 'Results / System Design and Development', 4, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-31 06:15:08', NULL, 'Progress approved.', NULL, '2026-08-31 05:28:10', '2026-08-31 06:15:08'),
(205, 25, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-31 06:15:17', NULL, 'Progress approved.', NULL, '2026-08-31 05:28:10', '2026-08-31 06:15:17'),
(206, 25, 'System Development', 'System Implementation', 6, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-31 06:15:28', NULL, 'Progress approved.', NULL, '2026-08-31 05:28:10', '2026-08-31 06:15:28'),
(207, 25, 'Testing', 'Testing and Quality Assurance', 7, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-31 06:15:35', NULL, 'Progress approved.', NULL, '2026-08-31 05:28:10', '2026-08-31 06:15:35'),
(208, 25, 'Documentation', 'Final Documentation and Report', 8, 100.00, 1.00, 'Approved', NULL, NULL, '2026-08-31 06:15:42', NULL, 'Progress approved.', NULL, '2026-08-31 05:28:10', '2026-08-31 06:15:42'),
(209, 26, 'Chapter 1', 'Introduction and Background', 1, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, 'Progress approved.', NULL, '2026-08-31 09:34:19', '2026-08-31 09:36:20'),
(210, 26, 'Chapter 2', 'Review of Related Literature', 2, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, 'Progress approved.', NULL, '2026-08-31 09:34:19', '2026-08-31 09:36:29'),
(211, 26, 'Chapter 3', 'Methodology', 3, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, 'Progress approved.', NULL, '2026-08-31 09:34:19', '2026-08-31 09:36:53'),
(212, 26, 'Chapter 4', 'Results / System Design and Development', 4, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 09:34:19', '2026-08-31 09:35:01'),
(213, 26, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 09:34:19', '2026-08-31 09:35:12'),
(214, 26, 'System Development', 'System Implementation', 6, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 09:34:19', '2026-08-31 09:35:29'),
(215, 26, 'Testing', 'Testing and Quality Assurance', 7, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 09:34:19', '2026-08-31 09:35:37'),
(216, 26, 'Documentation', 'Final Documentation and Report', 8, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 09:34:19', '2026-08-31 09:35:48'),
(217, 27, 'Chapter 1', 'Introduction and Background', 1, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-02 07:34:41', '2026-09-02 07:34:55'),
(218, 27, 'Chapter 2', 'Review of Related Literature', 2, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-02 07:34:41', '2026-09-02 07:35:10'),
(219, 27, 'Chapter 3', 'Methodology', 3, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-02 07:34:41', '2026-09-02 07:35:19'),
(220, 27, 'Chapter 4', 'Results / System Design and Development', 4, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-02 07:34:41', '2026-09-02 07:34:41'),
(221, 27, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-02 07:34:41', '2026-09-02 07:34:41'),
(222, 27, 'System Development', 'System Implementation', 6, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-02 07:34:41', '2026-09-02 07:34:41'),
(223, 27, 'Testing', 'Testing and Quality Assurance', 7, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-02 07:34:41', '2026-09-02 07:34:41'),
(224, 27, 'Documentation', 'Final Documentation and Report', 8, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-02 07:34:41', '2026-09-02 07:34:41'),
(225, 28, 'Chapter 1', 'Introduction and Background', 1, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, 'Progress approved.', NULL, '2026-09-19 00:31:49', '2026-09-19 00:36:30'),
(226, 28, 'Chapter 2', 'Review of Related Literature', 2, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, 'Progress approved.', NULL, '2026-09-19 00:31:49', '2026-09-19 00:36:43'),
(227, 28, 'Chapter 3', 'Methodology', 3, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, 'Progress approved.', NULL, '2026-09-19 00:31:49', '2026-09-19 00:36:57'),
(228, 28, 'Chapter 4', 'Results / System Design and Development', 4, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-19 00:31:49', '2026-09-19 00:31:49'),
(229, 28, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-19 00:31:49', '2026-09-19 00:31:49'),
(230, 28, 'System Development', 'System Implementation', 6, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-19 00:31:49', '2026-09-19 00:31:49'),
(231, 28, 'Testing', 'Testing and Quality Assurance', 7, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-19 00:31:49', '2026-09-19 00:31:49'),
(232, 28, 'Documentation', 'Final Documentation and Report', 8, 0.00, 1.00, 'Not Started', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-19 00:31:49', '2026-09-19 00:31:49'),
(233, 29, 'Chapter 1', 'Introduction and Background', 1, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, 'Progress approved.', NULL, '2026-09-19 09:14:57', '2026-09-19 09:17:51'),
(234, 29, 'Chapter 2', 'Review of Related Literature', 2, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, 'Progress approved.', NULL, '2026-09-19 09:14:57', '2026-09-19 09:18:08'),
(235, 29, 'Chapter 3', 'Methodology', 3, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, 'Progress approved.', NULL, '2026-09-19 09:14:57', '2026-09-19 09:18:25'),
(236, 29, 'Chapter 4', 'Results / System Design and Development', 4, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-19 09:14:57', '2026-09-19 09:58:13'),
(237, 29, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-19 09:14:57', '2026-09-19 09:58:19'),
(238, 29, 'System Development', 'System Implementation', 6, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-19 09:14:57', '2026-09-19 09:58:26'),
(239, 29, 'Testing', 'Testing and Quality Assurance', 7, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-19 09:14:57', '2026-09-19 09:58:33'),
(240, 29, 'Documentation', 'Final Documentation and Report', 8, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-19 09:14:57', '2026-09-19 09:58:40'),
(241, 30, 'Chapter 1', 'Introduction and Background', 1, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 12:38:59', NULL, 'Progress approved.', 'Approved by Panel.', '2026-09-19 11:55:47', '2026-09-19 12:38:59'),
(242, 30, 'Chapter 2', 'Review of Related Literature', 2, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 12:38:59', NULL, 'Progress approved.', 'Approved by Panel.', '2026-09-19 11:55:47', '2026-09-19 12:38:59'),
(243, 30, 'Chapter 3', 'Methodology', 3, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 12:38:59', NULL, 'Progress approved.', 'Approved by Panel.', '2026-09-19 11:55:47', '2026-09-19 12:38:59'),
(244, 30, 'Chapter 4', 'Results / System Design and Development', 4, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 12:39:11', NULL, 'Progress approved.', NULL, '2026-09-19 11:55:47', '2026-09-19 12:39:11'),
(245, 30, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 12:39:23', NULL, 'Progress approved.', NULL, '2026-09-19 11:55:47', '2026-09-19 12:39:23'),
(246, 30, 'System Development', 'System Implementation', 6, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 12:39:34', NULL, 'Progress approved.', NULL, '2026-09-19 11:55:47', '2026-09-19 12:39:34'),
(247, 30, 'Testing', 'Testing and Quality Assurance', 7, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 12:39:47', NULL, 'Progress approved.', NULL, '2026-09-19 11:55:47', '2026-09-19 12:39:47'),
(248, 30, 'Documentation', 'Final Documentation and Report', 8, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 12:40:31', NULL, 'Progress approved.', NULL, '2026-09-19 11:55:47', '2026-09-19 12:40:31'),
(249, 31, 'Chapter 1', 'Introduction and Background', 1, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 13:34:14', NULL, 'Progress approved.', 'Approved by Panel.', '2026-09-19 13:17:19', '2026-09-19 13:34:14'),
(250, 31, 'Chapter 2', 'Review of Related Literature', 2, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 13:34:14', NULL, 'done', 'Approved by Panel.', '2026-09-19 13:17:19', '2026-09-19 13:34:14'),
(251, 31, 'Chapter 3', 'Methodology', 3, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 13:34:14', NULL, 'Progress approved.', 'Approved by Panel.', '2026-09-19 13:17:19', '2026-09-19 13:34:14'),
(252, 31, 'Chapter 4', 'Results / System Design and Development', 4, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 13:20:19', NULL, 'Progress approved.', NULL, '2026-09-19 13:17:19', '2026-09-19 13:20:19'),
(253, 31, 'Chapter 5', 'Summary, Conclusions and Recommendations', 5, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 13:20:39', NULL, 'Progress approved.', NULL, '2026-09-19 13:17:19', '2026-09-19 13:20:39'),
(254, 31, 'System Development', 'System Implementation', 6, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 13:20:46', NULL, 'Progress approved.', NULL, '2026-09-19 13:17:19', '2026-09-19 13:20:46'),
(255, 31, 'Testing', 'Testing and Quality Assurance', 7, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 13:21:00', NULL, 'Progress approved.', NULL, '2026-09-19 13:17:19', '2026-09-19 13:21:00'),
(256, 31, 'Documentation', 'Final Documentation and Report', 8, 100.00, 1.00, 'Approved', NULL, NULL, '2026-09-19 13:21:12', NULL, 'Progress approved.', NULL, '2026-09-19 13:17:19', '2026-09-19 13:21:12');

-- --------------------------------------------------------

--
-- Table structure for table `research_panel_assignments`
--

CREATE TABLE `research_panel_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `defense_schedule_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_id` int(10) UNSIGNED DEFAULT NULL,
  `title_approval_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_number` varchar(30) DEFAULT NULL,
  `group_number` varchar(40) NOT NULL DEFAULT '',
  `research_title` varchar(255) NOT NULL DEFAULT '',
  `panel_user_id` int(10) UNSIGNED NOT NULL,
  `panel_name` varchar(150) NOT NULL DEFAULT '',
  `panel_email` varchar(190) NOT NULL DEFAULT '',
  `expertise` varchar(255) NOT NULL DEFAULT '',
  `availability_status` varchar(40) NOT NULL DEFAULT 'Pending',
  `assignment_status` varchar(40) NOT NULL DEFAULT 'Assigned',
  `defense_phase` varchar(60) NOT NULL DEFAULT 'Pre-Oral Defense',
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_panel_assignments`
--

INSERT INTO `research_panel_assignments` (`id`, `research_group_id`, `defense_schedule_id`, `proposal_id`, `title_approval_id`, `proposal_number`, `group_number`, `research_title`, `panel_user_id`, `panel_name`, `panel_email`, `expertise`, `availability_status`, `assignment_status`, `defense_phase`, `assigned_by`, `assigned_at`, `created_at`, `updated_at`) VALUES
(1, 52, NULL, NULL, 35, 'TAP-2026-00035', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED OPEN AI GPT 5,5', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 116, '2026-08-15 18:28:32', '2026-08-15 18:28:32', '2026-08-15 18:28:32'),
(2, 52, NULL, NULL, 35, 'TAP-2026-00035', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED OPEN AI GPT 5,5', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 116, '2026-08-15 18:28:32', '2026-08-15 18:28:32', '2026-08-15 18:28:32'),
(3, 52, NULL, NULL, 35, 'TAP-2026-00035', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED OPEN AI GPT 5,5', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 116, '2026-08-15 18:28:32', '2026-08-15 18:28:32', '2026-08-15 18:28:32'),
(4, 53, NULL, NULL, 37, 'TAP-2026-00037', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT ANALYSIS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-15 22:51:51', '2026-08-15 22:51:51', '2026-08-15 22:51:51'),
(5, 53, NULL, NULL, 37, 'TAP-2026-00037', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT ANALYSIS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-15 22:51:51', '2026-08-15 22:51:51', '2026-08-15 22:51:51'),
(6, 53, NULL, NULL, 37, 'TAP-2026-00037', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT ANALYSIS', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-15 22:51:51', '2026-08-15 22:51:51', '2026-08-15 22:51:51'),
(7, 54, 23, NULL, 43, 'TAP-2026-00043', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT ANALYSIS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-16 15:02:09', '2026-08-16 15:02:09', '2026-08-16 15:15:51'),
(8, 54, 23, NULL, 43, 'TAP-2026-00043', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT ANALYSIS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-16 15:02:09', '2026-08-16 15:02:09', '2026-08-16 15:15:51'),
(9, 54, 23, NULL, 43, 'TAP-2026-00043', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT ANALYSIS', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-16 15:02:09', '2026-08-16 15:02:09', '2026-08-16 15:15:51'),
(10, 57, 26, NULL, 46, 'TAP-2026-00046', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-16 21:44:21', '2026-08-16 21:44:21', '2026-08-16 21:46:14'),
(11, 57, 26, NULL, 46, 'TAP-2026-00046', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-16 21:44:21', '2026-08-16 21:44:21', '2026-08-16 21:46:14'),
(12, 57, 26, NULL, 46, 'TAP-2026-00046', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-16 21:44:21', '2026-08-16 21:44:21', '2026-08-16 21:46:14'),
(13, 61, 30, NULL, 50, 'TAP-2026-00050', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-23 04:09:43', '2026-08-23 04:09:43', '2026-08-23 04:10:47'),
(14, 61, 30, NULL, 50, 'TAP-2026-00050', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-23 04:09:43', '2026-08-23 04:09:43', '2026-08-23 04:10:47'),
(15, 61, 30, NULL, 50, 'TAP-2026-00050', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-23 04:09:43', '2026-08-23 04:09:43', '2026-08-23 04:10:47'),
(16, 61, 32, NULL, 50, 'TAP-2026-00050', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-28 08:21:42', '2026-08-28 08:21:42', '2026-08-28 08:21:42'),
(17, 61, 32, NULL, 50, 'TAP-2026-00050', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-28 08:21:42', '2026-08-28 08:21:42', '2026-08-28 08:21:42'),
(18, 61, 32, NULL, 50, 'TAP-2026-00050', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-28 08:21:42', '2026-08-28 08:21:42', '2026-08-28 08:21:42'),
(19, 62, 36, NULL, 51, 'TAP-2026-00051', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-28 15:34:33', '2026-08-28 15:34:33', '2026-08-28 15:35:39'),
(20, 62, 36, NULL, 51, 'TAP-2026-00051', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-28 15:34:33', '2026-08-28 15:34:33', '2026-08-28 15:35:39'),
(21, 62, 36, NULL, 51, 'TAP-2026-00051', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-28 15:34:33', '2026-08-28 15:34:33', '2026-08-28 15:35:39'),
(22, 62, 38, NULL, 51, 'TAP-2026-00051', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-28 15:52:23', '2026-08-28 15:52:23', '2026-08-28 15:52:23'),
(23, 62, 38, NULL, 51, 'TAP-2026-00051', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-28 15:52:23', '2026-08-28 15:52:23', '2026-08-28 15:52:23'),
(24, 62, 38, NULL, 51, 'TAP-2026-00051', 'RG-2026-001', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-28 15:52:23', '2026-08-28 15:52:23', '2026-08-28 15:52:23'),
(25, 63, 41, NULL, 52, 'TAP-2026-00052', 'RG-2026-001', 'DEVELOPMENT OF AI', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-28 16:31:11', '2026-08-28 16:31:11', '2026-08-28 16:32:15'),
(26, 63, 41, NULL, 52, 'TAP-2026-00052', 'RG-2026-001', 'DEVELOPMENT OF AI', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-28 16:31:11', '2026-08-28 16:31:11', '2026-08-28 16:32:15'),
(27, 63, 41, NULL, 52, 'TAP-2026-00052', 'RG-2026-001', 'DEVELOPMENT OF AI', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-28 16:31:11', '2026-08-28 16:31:11', '2026-08-28 16:32:15'),
(28, 63, 44, NULL, 52, 'TAP-2026-00052', 'RG-2026-001', 'DEVELOPMENT OF AI', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-28 16:37:05', '2026-08-28 16:37:05', '2026-08-28 16:37:05'),
(29, 63, 44, NULL, 52, 'TAP-2026-00052', 'RG-2026-001', 'DEVELOPMENT OF AI', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-28 16:37:05', '2026-08-28 16:37:05', '2026-08-28 16:37:05'),
(30, 63, 44, NULL, 52, 'TAP-2026-00052', 'RG-2026-001', 'DEVELOPMENT OF AI', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-28 16:37:05', '2026-08-28 16:37:05', '2026-08-28 16:37:05'),
(31, 64, 48, NULL, 53, 'TAP-2026-00053', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-31 05:34:37', '2026-08-31 05:34:23', '2026-08-31 06:10:27'),
(32, 64, 48, NULL, 53, 'TAP-2026-00053', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-31 05:34:37', '2026-08-31 05:34:23', '2026-08-31 06:10:27'),
(33, 64, 48, NULL, 53, 'TAP-2026-00053', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-31 05:34:37', '2026-08-31 05:34:23', '2026-08-31 06:10:27'),
(34, 64, 50, NULL, 53, 'TAP-2026-00053', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-31 06:22:35', '2026-08-31 06:22:35', '2026-08-31 06:22:35'),
(35, 64, 50, NULL, 53, 'TAP-2026-00053', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-31 06:22:35', '2026-08-31 06:22:35', '2026-08-31 06:22:35'),
(36, 64, 50, NULL, 53, 'TAP-2026-00053', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 40, '2026-08-31 06:22:35', '2026-08-31 06:22:35', '2026-08-31 06:22:35'),
(37, 65, NULL, NULL, 54, 'TAP-2026-00054', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-31 09:43:31', '2026-08-31 09:43:31', '2026-08-31 10:56:14'),
(38, 65, NULL, NULL, 54, 'TAP-2026-00054', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-31 09:43:31', '2026-08-31 09:43:31', '2026-08-31 10:56:14'),
(39, 65, NULL, NULL, 54, 'TAP-2026-00054', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 493, 'Dr. Michelle Guevarra', 'michelleguevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-31 09:43:31', '2026-08-31 09:43:31', '2026-08-31 10:56:14'),
(40, 69, NULL, NULL, 57, 'TAP-2026-00057', 'RG-2026-068', 'ASDASD', 990, 'Dr. Joseph Alcantara', 'deptchair@bestlink.edu.ph', 'Department Chair', 'Available', 'Assigned', 'Pre-Oral Defense', 758, '2026-09-19 00:47:46', '2026-09-19 00:47:46', '2026-09-19 00:47:46'),
(41, 69, NULL, NULL, 57, 'TAP-2026-00057', 'RG-2026-068', 'ASDASD', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 758, '2026-09-19 00:47:46', '2026-09-19 00:47:46', '2026-09-19 00:47:46'),
(42, 69, NULL, NULL, 57, 'TAP-2026-00057', 'RG-2026-068', 'ASDASD', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 758, '2026-09-19 00:47:46', '2026-09-19 00:47:46', '2026-09-19 00:47:46'),
(43, 71, 66, NULL, 59, 'TAP-2026-00059', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 990, 'Dr. Joseph Alcantara', 'deptchair@bestlink.edu.ph', 'Department Chair', 'Available', 'Assigned', 'Pre-Oral Defense', 1420, '2026-09-19 12:20:40', '2026-09-19 12:20:40', '2026-09-19 12:21:15'),
(44, 71, 66, NULL, 59, 'TAP-2026-00059', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 1420, '2026-09-19 12:20:40', '2026-09-19 12:20:40', '2026-09-19 12:21:15'),
(45, 71, 66, NULL, 59, 'TAP-2026-00059', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 1420, '2026-09-19 12:20:40', '2026-09-19 12:20:40', '2026-09-19 12:21:15'),
(46, 71, 68, NULL, 59, 'TAP-2026-00059', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 1420, '2026-09-19 13:10:38', '2026-09-19 13:10:38', '2026-09-19 13:10:38'),
(47, 71, 68, NULL, 59, 'TAP-2026-00059', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 1420, '2026-09-19 13:10:38', '2026-09-19 13:10:38', '2026-09-19 13:10:38'),
(48, 71, 68, NULL, 59, 'TAP-2026-00059', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 990, 'Dr. Joseph Alcantara', 'deptchair@bestlink.edu.ph', 'Department Chair', 'Available', 'Assigned', 'Final Defense', 1420, '2026-09-19 13:10:38', '2026-09-19 13:10:38', '2026-09-19 13:10:38'),
(49, 72, 71, NULL, 60, 'TAP-2026-00060', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 990, 'Dr. Joseph Alcantara', 'deptchair@bestlink.edu.ph', 'Department Chair', 'Available', 'Assigned', 'Pre-Oral Defense', 1420, '2026-09-19 13:32:07', '2026-09-19 13:32:07', '2026-09-19 13:32:49'),
(50, 72, 71, NULL, 60, 'TAP-2026-00060', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 1420, '2026-09-19 13:32:07', '2026-09-19 13:32:07', '2026-09-19 13:32:49'),
(51, 72, 71, NULL, 60, 'TAP-2026-00060', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 1420, '2026-09-19 13:32:07', '2026-09-19 13:32:07', '2026-09-19 13:32:49'),
(52, 72, 77, NULL, 60, 'TAP-2026-00060', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 491, 'Dr. Jobert Valentino', 'jobertvalentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 1420, '2026-09-19 13:44:19', '2026-09-19 13:44:19', '2026-09-19 13:44:19'),
(53, 72, 77, NULL, 60, 'TAP-2026-00060', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 492, 'Dr. Jonathan Estrada', 'jonathanestrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Final Defense', 1420, '2026-09-19 13:44:19', '2026-09-19 13:44:19', '2026-09-19 13:44:19'),
(54, 72, 77, NULL, 60, 'TAP-2026-00060', 'RG-2026-068', 'AI DRIVEN DATA ANALYTICS', 990, 'Dr. Joseph Alcantara', 'deptchair@bestlink.edu.ph', 'Department Chair', 'Available', 'Assigned', 'Final Defense', 1420, '2026-09-19 13:44:19', '2026-09-19 13:44:19', '2026-09-19 13:44:19');

-- --------------------------------------------------------

--
-- Table structure for table `research_plans`
--

CREATE TABLE `research_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to research_groups; nullable to preserve history if group is removed',
  `research_title` varchar(500) NOT NULL DEFAULT '',
  `group_number` varchar(40) NOT NULL DEFAULT '',
  `adviser_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to sms2_db users (adviser)',
  `adviser_name` varchar(150) NOT NULL DEFAULT '',
  `adviser_email` varchar(190) NOT NULL DEFAULT '',
  `start_date` date DEFAULT NULL,
  `target_completion_date` date DEFAULT NULL,
  `current_stage` varchar(100) NOT NULL DEFAULT 'Planning',
  `overall_progress` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Auto-calculated from milestones',
  `status` enum('Active','Completed','On Hold','Cancelled') NOT NULL DEFAULT 'Active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `final_defense_recommended` tinyint(1) NOT NULL DEFAULT 0,
  `final_defense_recommended_by` int(10) UNSIGNED DEFAULT NULL,
  `final_defense_recommended_by_name` varchar(150) DEFAULT NULL,
  `final_defense_recommended_at` datetime DEFAULT NULL,
  `final_defense_recommendation_remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_plans`
--

INSERT INTO `research_plans` (`id`, `research_group_id`, `research_title`, `group_number`, `adviser_id`, `adviser_name`, `adviser_email`, `start_date`, `target_completion_date`, `current_stage`, `overall_progress`, `status`, `created_at`, `updated_at`, `final_defense_recommended`, `final_defense_recommended_by`, `final_defense_recommended_by_name`, `final_defense_recommended_at`, `final_defense_recommendation_remarks`) VALUES
(21, NULL, 'DEVELOPMENT OF AI ASSISTED', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-23', NULL, 'Planning', 0.00, 'Active', '2026-08-23 03:16:21', '2026-08-23 03:46:14', 0, NULL, NULL, NULL, NULL),
(22, NULL, 'DEVELOPMENT OF AI ANALYSIS', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-23', NULL, 'Pre-Oral Defense', 100.00, 'Active', '2026-08-23 03:49:51', '2026-08-28 08:00:25', 0, NULL, NULL, NULL, NULL),
(23, NULL, 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-28', NULL, 'Pre-Oral Defense', 100.00, 'Active', '2026-08-28 15:30:09', '2026-08-28 16:05:34', 1, 54, 'Dr. Roberto M. Santos', '2026-08-28 15:45:21', ''),
(24, NULL, 'DEVELOPMENT OF AI', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-28', NULL, 'Pre-Oral Defense', 100.00, 'Active', '2026-08-28 16:28:29', '2026-08-28 16:34:47', 1, 54, 'Dr. Roberto M. Santos', '2026-08-28 16:34:27', ''),
(25, NULL, 'DEVELOPMENT OF AI ANALYSIS', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-31', NULL, 'Pre-Oral Defense', 100.00, 'Active', '2026-08-31 05:28:10', '2026-08-31 06:16:38', 1, 54, 'Dr. Roberto M. Santos', '2026-08-31 06:15:48', ''),
(26, NULL, 'DEVELOPMENT OF AI ANALYSIS', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-31', NULL, 'Planning', 0.00, 'Active', '2026-08-31 09:34:19', '2026-08-31 09:36:53', 0, NULL, NULL, NULL, NULL),
(27, NULL, 'DEVELOPMENT OF AI ANALYSIS', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-09-02', NULL, 'Planning', 0.00, 'Active', '2026-09-02 07:34:41', '2026-09-02 07:35:19', 0, NULL, NULL, NULL, NULL),
(28, NULL, 'ASDASD', 'RG-2026-068', 54, 'Dr. Roberto M. Santos', '', '2026-09-19', NULL, 'Planning', 0.00, 'Active', '2026-09-19 00:31:49', '2026-09-19 00:36:57', 0, NULL, NULL, NULL, NULL),
(29, NULL, 'AI ANALYTICS DATA DRIVEN', 'RG-2026-068', 54, 'Dr. Roberto M. Santos', '', '2026-09-19', NULL, 'Planning', 0.00, 'Active', '2026-09-19 09:14:57', '2026-09-19 09:58:40', 0, NULL, NULL, NULL, NULL),
(30, NULL, 'AI DRIVEN DATA ANALYTICS', 'RG-2026-068', 54, 'Dr. Roberto M. Santos', '', '2026-09-19', NULL, 'Pre-Oral Defense', 100.00, 'Active', '2026-09-19 11:55:47', '2026-09-19 12:40:51', 1, 54, 'Dr. Roberto M. Santos', '2026-09-19 12:40:40', 'done'),
(31, NULL, 'AI DRIVEN DATA ANALYTICS', 'RG-2026-068', 54, 'Dr. Roberto M. Santos', '', '2026-09-19', NULL, 'Pre-Oral Defense', 100.00, 'Active', '2026-09-19 13:17:19', '2026-09-19 13:35:39', 1, 54, 'Dr. Roberto M. Santos', '2026-09-19 13:34:18', '');

-- --------------------------------------------------------

--
-- Table structure for table `research_progress_activity_logs`
--

CREATE TABLE `research_progress_activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_plan_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to sms2_db users',
  `user_name` varchar(150) NOT NULL DEFAULT '',
  `user_role` varchar(40) NOT NULL DEFAULT '',
  `action` varchar(100) NOT NULL COMMENT 'milestone_created, progress_updated, feedback_added, etc',
  `entity_type` varchar(50) NOT NULL DEFAULT '' COMMENT 'milestone, progress_update, feedback, etc',
  `entity_id` int(10) UNSIGNED DEFAULT NULL,
  `old_value` text DEFAULT NULL COMMENT 'JSON or text of previous state',
  `new_value` text DEFAULT NULL COMMENT 'JSON or text of new state',
  `description` varchar(500) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_progress_activity_logs`
--

INSERT INTO `research_progress_activity_logs` (`id`, `research_plan_id`, `user_id`, `user_name`, `user_role`, `action`, `entity_type`, `entity_id`, `old_value`, `new_value`, `description`, `created_at`) VALUES
(79, 21, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 43, NULL, NULL, 'Progress updated to 0%', '2026-08-23 03:16:35'),
(90, 22, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 53, NULL, NULL, 'Progress updated to 0%', '2026-08-23 03:49:58'),
(91, 22, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 54, NULL, NULL, 'Progress updated to 0%', '2026-08-23 03:50:08'),
(92, 22, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 55, NULL, NULL, 'Progress updated to 0%', '2026-08-23 03:50:18'),
(93, 22, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 36, NULL, NULL, 'Adviser approved progress', '2026-08-23 03:50:29'),
(94, 22, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 37, NULL, NULL, 'Adviser approved progress', '2026-08-23 03:50:36'),
(95, 22, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 38, NULL, NULL, 'Adviser approved progress', '2026-08-23 03:50:45'),
(96, 22, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 56, NULL, NULL, 'Progress updated to 0%', '2026-08-23 04:12:04'),
(97, 22, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 57, NULL, NULL, 'Progress updated to 0%', '2026-08-23 04:12:11'),
(98, 22, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 58, NULL, NULL, 'Progress updated to 0%', '2026-08-23 04:12:18'),
(99, 22, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 59, NULL, NULL, 'Progress updated to 0%', '2026-08-23 04:12:25'),
(100, 22, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 60, NULL, NULL, 'Progress updated to 0%', '2026-08-23 04:12:31'),
(101, 22, 54, 'Dr. Roberto M. Santos', 'adviser', 'revision_requested', 'feedback', 39, NULL, NULL, 'Adviser requested revision', '2026-08-23 04:13:01'),
(102, 22, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 61, NULL, NULL, 'Progress updated to 0%', '2026-08-23 04:13:16'),
(103, 22, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 40, NULL, NULL, 'Adviser approved progress', '2026-08-23 04:13:25'),
(104, 22, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 41, NULL, NULL, 'Adviser approved progress', '2026-08-23 04:13:35'),
(105, 22, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 42, NULL, NULL, 'Adviser approved progress', '2026-08-23 04:13:45'),
(106, 22, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 43, NULL, NULL, 'Adviser approved progress', '2026-08-23 04:13:52'),
(107, 22, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 44, NULL, NULL, 'Adviser approved progress', '2026-08-23 04:13:59'),
(108, 23, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 62, NULL, NULL, 'Progress updated to 0%', '2026-08-28 15:30:17'),
(109, 23, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 63, NULL, NULL, 'Progress updated to 0%', '2026-08-28 15:30:54'),
(110, 23, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 64, NULL, NULL, 'Progress updated to 0%', '2026-08-28 15:31:01'),
(111, 23, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 65, NULL, NULL, 'Progress updated to 0%', '2026-08-28 15:31:08'),
(112, 23, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 66, NULL, NULL, 'Progress updated to 0%', '2026-08-28 15:31:16'),
(113, 23, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 67, NULL, NULL, 'Progress updated to 0%', '2026-08-28 15:31:23'),
(114, 23, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 68, NULL, NULL, 'Progress updated to 0%', '2026-08-28 15:31:30'),
(115, 23, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 69, NULL, NULL, 'Progress updated to 0%', '2026-08-28 15:31:36'),
(116, 23, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 45, NULL, NULL, 'Adviser approved progress', '2026-08-28 15:32:47'),
(117, 23, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 46, NULL, NULL, 'Adviser approved progress', '2026-08-28 15:32:53'),
(118, 23, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 47, NULL, NULL, 'Adviser approved progress', '2026-08-28 15:32:57'),
(119, 23, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 48, NULL, NULL, 'Adviser approved progress', '2026-08-28 15:37:17'),
(120, 23, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 49, NULL, NULL, 'Adviser approved progress', '2026-08-28 15:37:22'),
(121, 23, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 50, NULL, NULL, 'Adviser approved progress', '2026-08-28 15:37:29'),
(122, 23, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 51, NULL, NULL, 'Adviser approved progress', '2026-08-28 15:37:35'),
(123, 23, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 52, NULL, NULL, 'Adviser approved progress', '2026-08-28 15:37:44'),
(124, 24, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 70, NULL, NULL, 'Progress updated to 0%', '2026-08-28 16:28:35'),
(125, 24, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 71, NULL, NULL, 'Progress updated to 0%', '2026-08-28 16:28:41'),
(126, 24, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 72, NULL, NULL, 'Progress updated to 0%', '2026-08-28 16:28:47'),
(127, 24, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 73, NULL, NULL, 'Progress updated to 0%', '2026-08-28 16:28:54'),
(128, 24, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 74, NULL, NULL, 'Progress updated to 0%', '2026-08-28 16:29:01'),
(129, 24, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 75, NULL, NULL, 'Progress updated to 0%', '2026-08-28 16:29:08'),
(130, 24, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 76, NULL, NULL, 'Progress updated to 0%', '2026-08-28 16:29:19'),
(131, 24, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 53, NULL, NULL, 'Adviser approved progress', '2026-08-28 16:29:27'),
(132, 24, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 54, NULL, NULL, 'Adviser approved progress', '2026-08-28 16:29:31'),
(133, 24, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 55, NULL, NULL, 'Adviser approved progress', '2026-08-28 16:29:36'),
(134, 24, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 77, NULL, NULL, 'Progress updated to 0%', '2026-08-28 16:33:53'),
(135, 24, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 56, NULL, NULL, 'Adviser approved progress', '2026-08-28 16:33:58'),
(136, 24, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 57, NULL, NULL, 'Adviser approved progress', '2026-08-28 16:34:03'),
(137, 24, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 58, NULL, NULL, 'Adviser approved progress', '2026-08-28 16:34:11'),
(138, 24, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 59, NULL, NULL, 'Adviser approved progress', '2026-08-28 16:34:16'),
(139, 24, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 60, NULL, NULL, 'Adviser approved progress', '2026-08-28 16:34:23'),
(140, 25, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 78, NULL, NULL, 'Progress updated to 0%', '2026-08-31 05:28:26'),
(141, 25, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 79, NULL, NULL, 'Progress updated to 0%', '2026-08-31 05:28:34'),
(142, 25, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 80, NULL, NULL, 'Progress updated to 0%', '2026-08-31 05:28:45'),
(143, 25, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 81, NULL, NULL, 'Progress updated to 0%', '2026-08-31 05:28:53'),
(144, 25, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 82, NULL, NULL, 'Progress updated to 0%', '2026-08-31 05:29:03'),
(145, 25, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 83, NULL, NULL, 'Progress updated to 0%', '2026-08-31 05:29:15'),
(146, 25, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 84, NULL, NULL, 'Progress updated to 0%', '2026-08-31 05:29:24'),
(147, 25, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 85, NULL, NULL, 'Progress updated to 0%', '2026-08-31 05:29:32'),
(148, 25, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 61, NULL, NULL, 'Adviser approved progress', '2026-08-31 05:29:47'),
(149, 25, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 62, NULL, NULL, 'Adviser approved progress', '2026-08-31 05:29:55'),
(150, 25, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 63, NULL, NULL, 'Adviser approved progress', '2026-08-31 05:30:03'),
(151, 25, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 64, NULL, NULL, 'Adviser approved progress', '2026-08-31 06:15:08'),
(152, 25, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 65, NULL, NULL, 'Adviser approved progress', '2026-08-31 06:15:17'),
(153, 25, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 66, NULL, NULL, 'Adviser approved progress', '2026-08-31 06:15:28'),
(154, 25, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 67, NULL, NULL, 'Adviser approved progress', '2026-08-31 06:15:35'),
(155, 25, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 68, NULL, NULL, 'Adviser approved progress', '2026-08-31 06:15:42'),
(156, 26, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 86, NULL, NULL, 'Progress updated to 0%', '2026-08-31 09:34:32'),
(157, 26, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 87, NULL, NULL, 'Progress updated to 0%', '2026-08-31 09:34:41'),
(158, 26, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 88, NULL, NULL, 'Progress updated to 0%', '2026-08-31 09:34:49'),
(159, 26, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 89, NULL, NULL, 'Progress updated to 0%', '2026-08-31 09:35:01'),
(160, 26, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 90, NULL, NULL, 'Progress updated to 0%', '2026-08-31 09:35:12'),
(161, 26, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 91, NULL, NULL, 'Progress updated to 0%', '2026-08-31 09:35:29'),
(162, 26, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 92, NULL, NULL, 'Progress updated to 0%', '2026-08-31 09:35:37'),
(163, 26, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 93, NULL, NULL, 'Progress updated to 0%', '2026-08-31 09:35:48'),
(164, 26, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 69, NULL, NULL, 'Adviser approved progress', '2026-08-31 09:36:20'),
(165, 26, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 70, NULL, NULL, 'Adviser approved progress', '2026-08-31 09:36:29'),
(166, 26, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 71, NULL, NULL, 'Adviser approved progress', '2026-08-31 09:36:53'),
(167, 27, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 94, NULL, NULL, 'Progress updated to 0%', '2026-09-02 07:34:55'),
(168, 27, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 95, NULL, NULL, 'Progress updated to 0%', '2026-09-02 07:35:10'),
(169, 27, 9, 'Student User', 'student', 'progress_updated', 'progress_update', 96, NULL, NULL, 'Progress updated to 0%', '2026-09-02 07:35:19'),
(170, 28, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 97, NULL, NULL, 'Progress updated to 0%', '2026-09-19 00:35:41'),
(171, 28, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 98, NULL, NULL, 'Progress updated to 0%', '2026-09-19 00:35:48'),
(172, 28, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 99, NULL, NULL, 'Progress updated to 0%', '2026-09-19 00:35:56'),
(173, 28, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 72, NULL, NULL, 'Adviser approved progress', '2026-09-19 00:36:30'),
(174, 28, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 73, NULL, NULL, 'Adviser approved progress', '2026-09-19 00:36:43'),
(175, 28, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 74, NULL, NULL, 'Adviser approved progress', '2026-09-19 00:36:57'),
(176, 29, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 100, NULL, NULL, 'Progress updated to 0%', '2026-09-19 09:17:10'),
(177, 29, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 101, NULL, NULL, 'Progress updated to 0%', '2026-09-19 09:17:18'),
(178, 29, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 102, NULL, NULL, 'Progress updated to 0%', '2026-09-19 09:17:26'),
(179, 29, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 75, NULL, NULL, 'Adviser approved progress', '2026-09-19 09:17:51'),
(180, 29, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 76, NULL, NULL, 'Adviser approved progress', '2026-09-19 09:18:08'),
(181, 29, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 77, NULL, NULL, 'Adviser approved progress', '2026-09-19 09:18:25'),
(182, 29, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 103, NULL, NULL, 'Progress updated to 0%', '2026-09-19 09:58:13'),
(183, 29, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 104, NULL, NULL, 'Progress updated to 0%', '2026-09-19 09:58:19'),
(184, 29, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 105, NULL, NULL, 'Progress updated to 0%', '2026-09-19 09:58:26'),
(185, 29, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 106, NULL, NULL, 'Progress updated to 0%', '2026-09-19 09:58:33'),
(186, 29, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 107, NULL, NULL, 'Progress updated to 0%', '2026-09-19 09:58:40'),
(187, 30, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 108, NULL, NULL, 'Progress updated to 0%', '2026-09-19 11:55:56'),
(188, 30, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 109, NULL, NULL, 'Progress updated to 0%', '2026-09-19 11:56:04'),
(189, 30, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 110, NULL, NULL, 'Progress updated to 0%', '2026-09-19 11:56:12'),
(190, 30, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 111, NULL, NULL, 'Progress updated to 0%', '2026-09-19 11:56:20'),
(191, 30, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 112, NULL, NULL, 'Progress updated to 0%', '2026-09-19 11:56:26'),
(192, 30, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 113, NULL, NULL, 'Progress updated to 0%', '2026-09-19 11:56:33'),
(193, 30, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 114, NULL, NULL, 'Progress updated to 0%', '2026-09-19 11:56:39'),
(194, 30, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 115, NULL, NULL, 'Progress updated to 0%', '2026-09-19 11:56:48'),
(195, 30, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 78, NULL, NULL, 'Adviser approved progress', '2026-09-19 11:57:28'),
(196, 30, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 79, NULL, NULL, 'Adviser approved progress', '2026-09-19 11:57:39'),
(197, 30, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 80, NULL, NULL, 'Adviser approved progress', '2026-09-19 11:57:51'),
(198, 30, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 81, NULL, NULL, 'Adviser approved progress', '2026-09-19 12:39:11'),
(199, 30, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 82, NULL, NULL, 'Adviser approved progress', '2026-09-19 12:39:23'),
(200, 30, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 83, NULL, NULL, 'Adviser approved progress', '2026-09-19 12:39:34'),
(201, 30, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 84, NULL, NULL, 'Adviser approved progress', '2026-09-19 12:39:47'),
(202, 30, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 85, NULL, NULL, 'Adviser approved progress', '2026-09-19 12:40:31'),
(203, 31, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 116, NULL, NULL, 'Progress updated to 0%', '2026-09-19 13:17:29'),
(204, 31, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 117, NULL, NULL, 'Progress updated to 0%', '2026-09-19 13:17:35'),
(205, 31, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 118, NULL, NULL, 'Progress updated to 0%', '2026-09-19 13:17:43'),
(206, 31, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 119, NULL, NULL, 'Progress updated to 0%', '2026-09-19 13:17:50'),
(207, 31, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 120, NULL, NULL, 'Progress updated to 0%', '2026-09-19 13:17:56'),
(208, 31, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 121, NULL, NULL, 'Progress updated to 0%', '2026-09-19 13:18:02'),
(209, 31, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 122, NULL, NULL, 'Progress updated to 0%', '2026-09-19 13:18:10'),
(210, 31, 1354, 'John Kenneth Abejuela', 'student', 'progress_updated', 'progress_update', 123, NULL, NULL, 'Progress updated to 0%', '2026-09-19 13:18:18'),
(211, 31, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 86, NULL, NULL, 'Adviser approved progress', '2026-09-19 13:18:34'),
(212, 31, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 87, NULL, NULL, 'Adviser approved progress', '2026-09-19 13:19:51'),
(213, 31, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 88, NULL, NULL, 'Adviser approved progress', '2026-09-19 13:20:07'),
(214, 31, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 89, NULL, NULL, 'Adviser approved progress', '2026-09-19 13:20:19'),
(215, 31, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 90, NULL, NULL, 'Adviser approved progress', '2026-09-19 13:20:39'),
(216, 31, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 91, NULL, NULL, 'Adviser approved progress', '2026-09-19 13:20:46'),
(217, 31, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 92, NULL, NULL, 'Adviser approved progress', '2026-09-19 13:21:00'),
(218, 31, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 93, NULL, NULL, 'Adviser approved progress', '2026-09-19 13:21:12');

-- --------------------------------------------------------

--
-- Table structure for table `research_progress_ai_analyses`
--

CREATE TABLE `research_progress_ai_analyses` (
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

--
-- Dumping data for table `research_progress_ai_analyses`
--

INSERT INTO `research_progress_ai_analyses` (`id`, `progress_update_id`, `attachment_id`, `milestone_name`, `verdict`, `grammar_quality`, `summary`, `notes_json`, `source`, `analyzed_by`, `analyzed_by_name`, `created_at`) VALUES
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

-- --------------------------------------------------------

--
-- Table structure for table `research_progress_attachments`
--

CREATE TABLE `research_progress_attachments` (
  `id` int(10) UNSIGNED NOT NULL,
  `progress_update_id` int(10) UNSIGNED NOT NULL,
  `file_name` varchar(300) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) NOT NULL DEFAULT '',
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Bytes',
  `uploaded_by` int(10) UNSIGNED NOT NULL COMMENT 'FK to sms2_db users',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_progress_attachments`
--

INSERT INTO `research_progress_attachments` (`id`, `progress_update_id`, `file_name`, `file_path`, `file_type`, `file_size`, `uploaded_by`, `created_at`) VALUES
(34, 43, 'CRAD_Chapter_1_TO_4_KULANG-PA.docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g60/u9/09a62b2daa1cce84684a82782b95b7df.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 7734354, 9, '2026-08-23 03:16:35'),
(41, 53, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/df3f82b96645757c73e791012ab36dc4.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 236268, 9, '2026-08-23 03:49:58'),
(42, 54, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/c17c49e54baa55cad91e60bd58bcad92.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 236268, 9, '2026-08-23 03:50:08'),
(43, 55, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/7e5a55afefbeda325fee95b26668600b.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 236268, 9, '2026-08-23 03:50:18'),
(44, 56, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/af25964fcccc0a65b09c503100ab6bd6.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 236268, 9, '2026-08-23 04:12:04'),
(45, 57, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/b0b3bb6b5becdf66dd97395b68d85f8d.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 236268, 9, '2026-08-23 04:12:11'),
(46, 58, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/ca491b2b5c87fa0313876eeca2cec331.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 236268, 9, '2026-08-23 04:12:18'),
(47, 59, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/1eb10d997a17fe2c9f5ee73485b77ae8.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 236268, 9, '2026-08-23 04:12:25'),
(48, 61, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/cff34883d0dec4263b79eddc43fcc992.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 236268, 9, '2026-08-23 04:13:16'),
(49, 62, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/e232da6a01b4a6d1fef55ef744cfd286.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 15:30:17'),
(50, 63, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/5eda2ee2c5b63097d514c7fb0febe2cb.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 15:30:54'),
(51, 64, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/7c5f38451672efeefa6fc2f052755647.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 15:31:01'),
(52, 65, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/d5738c98ea85c03003583de5a612bf42.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 15:31:08'),
(53, 66, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/84f08f7484eb161bdcb8756a57e1b6c6.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 15:31:16'),
(54, 67, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/c198a6471dc795e4a704cfb2ef3f808f.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 15:31:23'),
(55, 68, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/b875bb0942866180b655526b5d79cb9c.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 15:31:30'),
(56, 69, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/c4ad5851636b7b71d099039ebb69a252.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 15:31:36'),
(57, 70, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/28b05d83f4c150c45929b60ec2eee255.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 16:28:35'),
(58, 71, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/caf0f3c59320c81adae7ce19efc7c289.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 16:28:41'),
(59, 72, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/77701e54e11148f3638382199cef968e.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 16:28:47'),
(60, 73, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/3e2c3d7261ed6d038c8255773b17d194.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 16:28:54'),
(61, 74, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/0f1662a4f091481e6d76664b0836e6e6.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 16:29:01'),
(62, 75, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/93c3ee330a6b0a0dbcc31b66123df4b8.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 16:29:08'),
(63, 76, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/e7b47a94f20de26b24e04e1dd7f246ac.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 16:29:19'),
(64, 77, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/c7ad58d98d311f6fd3ab1e6a4bf9a098.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 302605, 9, '2026-08-28 16:33:53'),
(65, 78, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/1e8f6014e3459a3ebbe0e8362d99d252.pdf', 'application/pdf', 294354, 9, '2026-08-31 05:28:26'),
(66, 79, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/0f04ea324e3230f9eb7dbff5fc1f0889.pdf', 'application/pdf', 294354, 9, '2026-08-31 05:28:34'),
(67, 80, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/962a28233c08de26f2e9ef1209db1e82.pdf', 'application/pdf', 294354, 9, '2026-08-31 05:28:45'),
(68, 81, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/cb8e4d50ad6222d2ac4243ba948e77b0.pdf', 'application/pdf', 294354, 9, '2026-08-31 05:28:53'),
(69, 82, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/ddd91c717013890ffa4bd9e6503f9789.pdf', 'application/pdf', 294354, 9, '2026-08-31 05:29:03'),
(70, 83, 'Diaz CV.pdf_20260813_105004_0000.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/3c49d34ce74b8c1fb4f6a3820bc4fd77.pdf', 'application/pdf', 62390, 9, '2026-08-31 05:29:15'),
(71, 84, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/e9b4c863c0cdcb67975619e7f63d9e57.pdf', 'application/pdf', 294354, 9, '2026-08-31 05:29:24'),
(72, 85, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/c1b6a2b034dbec7c9fe660ba7ab5cddb.pdf', 'application/pdf', 294354, 9, '2026-08-31 05:29:32'),
(73, 86, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/0b9cb630bca2013fc5b5055241753819.pdf', 'application/pdf', 294354, 9, '2026-08-31 09:34:32'),
(74, 87, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/ef6e446036b4ba2a88d92faf65ddc654.pdf', 'application/pdf', 294354, 9, '2026-08-31 09:34:41'),
(75, 88, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/b0b0378a61ce66eaaca964143b30b63d.pdf', 'application/pdf', 294354, 9, '2026-08-31 09:34:49'),
(76, 89, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/06cc39770a42bf06fff7da5285a54c52.pdf', 'application/pdf', 294354, 9, '2026-08-31 09:35:01'),
(77, 90, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/4b2c7b8f8662c0acfecf51b09123ed51.pdf', 'application/pdf', 294354, 9, '2026-08-31 09:35:12'),
(78, 92, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/3f2e2e77ca556957100166cd43b11e4b.pdf', 'application/pdf', 294354, 9, '2026-08-31 09:35:37'),
(79, 93, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/afc9627ed4ff5d61ef4658268ee83f4c.pdf', 'application/pdf', 294354, 9, '2026-08-31 09:35:48'),
(80, 94, 'Abejuela , Week_8_Online_Activity_Software_Media_Management_Tools.docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g66/u9/bcf642c163a3c02786b8974d291c1c83.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 71201, 9, '2026-09-02 07:34:55'),
(81, 95, 'Abejuela , Week_8_Online_Activity_Software_Media_Management_Tools.docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g66/u9/748584c6106bcd3976f979f98733ba42.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 71201, 9, '2026-09-02 07:35:10'),
(82, 96, 'Abejuela , Week_8_Online_Activity_Software_Media_Management_Tools.docx', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g66/u9/1ac8a01fdb4c8c5dfa9e521c75e178cb.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 71201, 9, '2026-09-02 07:35:19'),
(83, 97, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g69/u1354/bd15754815587a1135b47543a9cdc15e.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 00:35:41'),
(84, 98, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g69/u1354/8a3a08780c7cb01bc0bb2ae56da852ce.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 00:35:48'),
(85, 99, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g69/u1354/eb174f9264d5a50a16324fd099a42d88.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 00:35:56'),
(86, 100, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/86451a18bda66baf69d7ef8aa90313a4.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 09:17:10'),
(87, 101, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/fda85aaef9bb57f3e49e1ac713de0b02.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 09:17:18'),
(88, 102, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/1ec0d39d90abcd14e9dba8ed9ca58c98.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 09:17:26'),
(89, 103, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/2557fb5dc709e1a491f29c071e1633f1.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 09:58:13'),
(90, 104, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/68ae9170245a394321f58192c74c9f52.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 09:58:19'),
(91, 105, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/b88927393754062bda7d375379d619a7.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 09:58:26'),
(92, 106, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/a350aa4a5be5f211fb8b321d547c705f.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 09:58:33'),
(93, 107, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/eaf27663a2127f934bd4314dd3666a9a.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 09:58:40'),
(94, 108, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/c3ffd43c12655848e466a77c0d122fd6.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 11:55:56'),
(95, 109, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/6fa8a645f0be0e24573e30f2695f253d.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 11:56:04'),
(96, 110, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/7845b03e0f469725c3fd5c2cbc012cef.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 11:56:12'),
(97, 111, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/673501f692e757138ac909c07ed879e2.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 11:56:20'),
(98, 112, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/04d32eaa9c11c266d965f35729ce3353.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 11:56:26'),
(99, 113, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/a6662c75278da0bd595e7aef44fe4120.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 11:56:33'),
(100, 114, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/45d339273a5cfc7c12378926418f3b54.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 11:56:39'),
(101, 115, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/8720e2d8c76e3ae01a4eed08332edd30.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 11:56:48'),
(102, 116, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/67ed2a98aaef3ffd396b3e8e4ce0dca4.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 13:17:29'),
(103, 117, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/60986978ae2a423220f60e15d33a41e6.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 13:17:35'),
(104, 118, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/28358606fe317468a3ee2795675dc154.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 13:17:43'),
(105, 119, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/336c492c69cfc6b6cba791537edc3789.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 13:17:50'),
(106, 120, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/3b5197cf57b78b2da650328e4f1c7168.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 13:17:56'),
(107, 122, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/24e9c84bad5f148cd03b484a9899c23d.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 13:18:10'),
(108, 123, 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/6a400c4fd4fa81995fc6ee3b09be3b39.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 287104, 1354, '2026-09-19 13:18:18');

-- --------------------------------------------------------

--
-- Table structure for table `research_progress_feedback`
--

CREATE TABLE `research_progress_feedback` (
  `id` int(10) UNSIGNED NOT NULL,
  `progress_update_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Can be NULL for general milestone feedback',
  `milestone_id` int(10) UNSIGNED DEFAULT NULL,
  `research_plan_id` int(10) UNSIGNED NOT NULL,
  `adviser_user_id` int(10) UNSIGNED NOT NULL,
  `adviser_name` varchar(200) NOT NULL DEFAULT '',
  `feedback_text` text NOT NULL,
  `new_milestone_status` varchar(60) DEFAULT NULL,
  `submission_token` varchar(64) DEFAULT NULL,
  `feedback_type` enum('Comment','Revision Request','Approval','Progress Approved') NOT NULL DEFAULT 'Comment',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_progress_feedback`
--

INSERT INTO `research_progress_feedback` (`id`, `progress_update_id`, `milestone_id`, `research_plan_id`, `adviser_user_id`, `adviser_name`, `feedback_text`, `new_milestone_status`, `submission_token`, `feedback_type`, `created_at`, `updated_at`) VALUES
(36, 53, 177, 22, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'cbab7e8bb4297eed5707494db5aca072', 'Progress Approved', '2026-08-23 03:50:29', '2026-08-23 03:50:29'),
(37, 54, 178, 22, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '3db8eccb296ff187522dda8d89406e2e', 'Progress Approved', '2026-08-23 03:50:36', '2026-08-23 03:50:36'),
(38, 55, 179, 22, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '2e9d9355eb6a72370d738e70531d8dbb', 'Progress Approved', '2026-08-23 03:50:45', '2026-08-23 03:50:45'),
(39, 60, 184, 22, 54, 'Dr. Roberto M. Santos', 'asda', 'Revision Requested', '808fb99c2fe3d46ec2c8c159f7c1371a', 'Revision Request', '2026-08-23 04:13:01', '2026-08-23 04:13:01'),
(40, 56, 180, 22, 54, 'Dr. Roberto M. Santos', 'asd', 'Approved', '437099e8103a1903c1f3a709f28ca760', 'Progress Approved', '2026-08-23 04:13:25', '2026-08-23 04:13:25'),
(41, 57, 181, 22, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'f684e5013bac8056cca3863e47bd7352', 'Progress Approved', '2026-08-23 04:13:35', '2026-08-23 04:13:35'),
(42, 58, 182, 22, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '34a0fbac64e4286ca1562c9deabf4496', 'Progress Approved', '2026-08-23 04:13:45', '2026-08-23 04:13:45'),
(43, 59, 183, 22, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '0199228cd673e9e3bd6a8a294f550e22', 'Progress Approved', '2026-08-23 04:13:52', '2026-08-23 04:13:52'),
(44, 61, 184, 22, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'fe26a976de6911f07e8c7128c3898650', 'Progress Approved', '2026-08-23 04:13:59', '2026-08-23 04:13:59'),
(45, 62, 185, 23, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'af47b77d85b3515a6285ccdacb8491f1', 'Progress Approved', '2026-08-28 15:32:47', '2026-08-28 15:32:47'),
(46, 63, 186, 23, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '038b536a7039f500815f5d5caf26a6a6', 'Progress Approved', '2026-08-28 15:32:53', '2026-08-28 15:32:53'),
(47, 64, 187, 23, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '6d859347e2ffd38a144721950f5acee6', 'Progress Approved', '2026-08-28 15:32:57', '2026-08-28 15:32:57'),
(48, 65, 188, 23, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'c5bdcdff4f2ee78dfdd9137957fae753', 'Progress Approved', '2026-08-28 15:37:17', '2026-08-28 15:37:17'),
(49, 66, 189, 23, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'de459f2c9b4baeb91890ba4ecf252afe', 'Progress Approved', '2026-08-28 15:37:22', '2026-08-28 15:37:22'),
(50, 67, 190, 23, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'ae366421ee920b567e05ff514901569d', 'Progress Approved', '2026-08-28 15:37:29', '2026-08-28 15:37:29'),
(51, 68, 191, 23, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'd0284261dab9d2c26df919065f0a7664', 'Progress Approved', '2026-08-28 15:37:35', '2026-08-28 15:37:35'),
(52, 69, 192, 23, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'e479b32ed11d98958b36ab3ff5e7b1b0', 'Progress Approved', '2026-08-28 15:37:44', '2026-08-28 15:37:44'),
(53, 70, 193, 24, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '429bde8947dbc3683aed8a5b77e1c5b3', 'Progress Approved', '2026-08-28 16:29:27', '2026-08-28 16:29:27'),
(54, 71, 194, 24, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '56357366b16542510b06ded75522a512', 'Progress Approved', '2026-08-28 16:29:31', '2026-08-28 16:29:31'),
(55, 72, 195, 24, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '216a9067f2e597dfca83630cdabac766', 'Progress Approved', '2026-08-28 16:29:35', '2026-08-28 16:29:35'),
(56, 73, 196, 24, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '356609fb6fee97c62ec5c3857ae98d4f', 'Progress Approved', '2026-08-28 16:33:58', '2026-08-28 16:33:58'),
(57, 74, 197, 24, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'e22ab3c8613001bf67270a318e9be606', 'Progress Approved', '2026-08-28 16:34:03', '2026-08-28 16:34:03'),
(58, 75, 198, 24, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '1a62e9b035250f0549c888f4674f8954', 'Progress Approved', '2026-08-28 16:34:11', '2026-08-28 16:34:11'),
(59, 76, 199, 24, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '4a659445e3ea9a05985ab23389ef47b8', 'Progress Approved', '2026-08-28 16:34:16', '2026-08-28 16:34:16'),
(60, 77, 200, 24, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '9abb90ef2957198f47696085f25f79c1', 'Progress Approved', '2026-08-28 16:34:23', '2026-08-28 16:34:23'),
(61, 78, 201, 25, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '150a2e1f8e5be45ab976f7a5a9965d39', 'Progress Approved', '2026-08-31 05:29:47', '2026-08-31 05:29:47'),
(62, 79, 202, 25, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'ea920a26c677e579211aafd7fd287ec1', 'Progress Approved', '2026-08-31 05:29:55', '2026-08-31 05:29:55'),
(63, 80, 203, 25, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'b356f432af193251dfd49c713cb15603', 'Progress Approved', '2026-08-31 05:30:03', '2026-08-31 05:30:03'),
(64, 81, 204, 25, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'bbffa704e7f2e707f2307f6cbf3e7469', 'Progress Approved', '2026-08-31 06:15:08', '2026-08-31 06:15:08'),
(65, 82, 205, 25, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '1d847884e699e2c863f9e3cd6e9ea48c', 'Progress Approved', '2026-08-31 06:15:17', '2026-08-31 06:15:17'),
(66, 83, 206, 25, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '989713e3784796ab62b6d27a46aeb2e4', 'Progress Approved', '2026-08-31 06:15:28', '2026-08-31 06:15:28'),
(67, 84, 207, 25, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '0bf8cf6e90e94bbb8479232779451cba', 'Progress Approved', '2026-08-31 06:15:35', '2026-08-31 06:15:35'),
(68, 85, 208, 25, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'cb4538db89818a051a2ccb5dc5c034cb', 'Progress Approved', '2026-08-31 06:15:42', '2026-08-31 06:15:42'),
(69, 86, 209, 26, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'd44b47d44416bf872495d1a2148ecbbe', 'Progress Approved', '2026-08-31 09:36:20', '2026-08-31 09:36:20'),
(70, 87, 210, 26, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '85cbb39397d0a40d618d796befc7b83d', 'Progress Approved', '2026-08-31 09:36:29', '2026-08-31 09:36:29'),
(71, 88, 211, 26, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'fa09c3d0d541bb1b2d0ba34ed41ad298', 'Progress Approved', '2026-08-31 09:36:53', '2026-08-31 09:36:53'),
(72, 97, 225, 28, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '7da2b6af20a766337fd7e3f000ac370b', 'Progress Approved', '2026-09-19 00:36:30', '2026-09-19 00:36:30'),
(73, 98, 226, 28, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '21c50c6ffdbf952cdf9b5d3e882ee556', 'Progress Approved', '2026-09-19 00:36:43', '2026-09-19 00:36:43'),
(74, 99, 227, 28, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '510a99060df55c9bd45052990114045c', 'Progress Approved', '2026-09-19 00:36:57', '2026-09-19 00:36:57'),
(75, 100, 233, 29, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'e99d34ca5733d452820bdc2c875541ae', 'Progress Approved', '2026-09-19 09:17:51', '2026-09-19 09:17:51'),
(76, 101, 234, 29, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '5c833df6d2173474d31597029d9b7ddb', 'Progress Approved', '2026-09-19 09:18:08', '2026-09-19 09:18:08'),
(77, 102, 235, 29, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '35e6aea47a3004629acf981414c80fca', 'Progress Approved', '2026-09-19 09:18:25', '2026-09-19 09:18:25'),
(78, 108, 241, 30, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'd6bb3ec3d0ff0dbd168e988c64e9b918', 'Progress Approved', '2026-09-19 11:57:28', '2026-09-19 11:57:28'),
(79, 109, 242, 30, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '3f33aa62d21ad7706562a14537c2b6ca', 'Progress Approved', '2026-09-19 11:57:39', '2026-09-19 11:57:39'),
(80, 110, 243, 30, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'ece5cb159d5f70d8fe89ced44d4db41f', 'Progress Approved', '2026-09-19 11:57:51', '2026-09-19 11:57:51'),
(81, 111, 244, 30, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '6f43ce29df8d6f29d174f340f5eca859', 'Progress Approved', '2026-09-19 12:39:11', '2026-09-19 12:39:11'),
(82, 112, 245, 30, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '70eba8b52991d83b5f3d34809abb5dee', 'Progress Approved', '2026-09-19 12:39:23', '2026-09-19 12:39:23'),
(83, 113, 246, 30, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '24fa4e31284ba3fd57d8ad6eb8048de5', 'Progress Approved', '2026-09-19 12:39:34', '2026-09-19 12:39:34'),
(84, 114, 247, 30, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '4f5b902295437eb0f6b1da6241be18a9', 'Progress Approved', '2026-09-19 12:39:47', '2026-09-19 12:39:47'),
(85, 115, 248, 30, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'd82bce334b54e57346bba4258bcacaf8', 'Progress Approved', '2026-09-19 12:40:31', '2026-09-19 12:40:31'),
(86, 116, 249, 31, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '9029ee1d0fd2102123d6e6548d957c5d', 'Progress Approved', '2026-09-19 13:18:34', '2026-09-19 13:18:34'),
(87, 117, 250, 31, 54, 'Dr. Roberto M. Santos', 'done', 'Approved', '172332195e779a7e919703036e55207a', 'Progress Approved', '2026-09-19 13:19:51', '2026-09-19 13:19:51'),
(88, 118, 251, 31, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '898ced2e0de83583db45261a883d9897', 'Progress Approved', '2026-09-19 13:20:07', '2026-09-19 13:20:07'),
(89, 119, 252, 31, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'de75252e87ca7a6fc47885bfdded6f1d', 'Progress Approved', '2026-09-19 13:20:19', '2026-09-19 13:20:19'),
(90, 120, 253, 31, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'c7a08a30b0b94f07a1ef9edee06e3397', 'Progress Approved', '2026-09-19 13:20:39', '2026-09-19 13:20:39'),
(91, 121, 254, 31, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '8729c63f44cb592b8676627ee0e4b6ba', 'Progress Approved', '2026-09-19 13:20:46', '2026-09-19 13:20:46'),
(92, 122, 255, 31, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', '4f0f5e932e5dc33de0456e8d0584a21b', 'Progress Approved', '2026-09-19 13:21:00', '2026-09-19 13:21:00'),
(93, 123, 256, 31, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'dce73893115741ba2849a2fb297a7190', 'Progress Approved', '2026-09-19 13:21:12', '2026-09-19 13:21:12');

-- --------------------------------------------------------

--
-- Table structure for table `research_progress_notifications`
--

CREATE TABLE `research_progress_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipient_user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to sms2_db.users.id (NULL = role-based)',
  `recipient_email` varchar(200) NOT NULL DEFAULT '',
  `recipient_role` varchar(40) NOT NULL DEFAULT '',
  `batch_key` varchar(100) NOT NULL DEFAULT '' COMMENT 'Unique key per event for deduplication',
  `notification_type` varchar(60) NOT NULL DEFAULT 'progress_update',
  `title` varchar(255) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `related_entity_type` varchar(60) NOT NULL DEFAULT '',
  `related_entity_id` int(10) UNSIGNED DEFAULT NULL,
  `action_url` varchar(500) DEFAULT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_progress_notifications`
--

INSERT INTO `research_progress_notifications` (`id`, `recipient_user_id`, `recipient_email`, `recipient_role`, `batch_key`, `notification_type`, `title`, `body`, `related_entity_type`, `related_entity_id`, `action_url`, `status`, `created_at`, `read_at`) VALUES
(130, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:97', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 1', 'progress_update', 97, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 00:35:41', NULL),
(131, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:98', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 2', 'progress_update', 98, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 00:35:48', NULL),
(132, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:99', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 3', 'progress_update', 99, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 00:35:56', NULL),
(133, 1354, '', 'student', 'approval:72', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 72, NULL, 'unread', '2026-09-19 00:36:30', NULL),
(134, 1354, '', 'student', 'approval:73', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 73, NULL, 'unread', '2026-09-19 00:36:43', NULL),
(135, 1354, '', 'student', 'approval:74', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 74, NULL, 'unread', '2026-09-19 00:36:57', NULL),
(136, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:100', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 1', 'progress_update', 100, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 09:17:10', NULL),
(137, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:101', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 2', 'progress_update', 101, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 09:17:18', NULL),
(138, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:102', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 3', 'progress_update', 102, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 09:17:26', NULL),
(139, 1354, '', 'student', 'approval:75', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 75, NULL, 'unread', '2026-09-19 09:17:51', NULL),
(140, 1354, '', 'student', 'approval:76', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 76, NULL, 'unread', '2026-09-19 09:18:08', NULL),
(141, 1354, '', 'student', 'approval:77', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 77, NULL, 'unread', '2026-09-19 09:18:25', NULL),
(142, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:103', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 4', 'progress_update', 103, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 09:58:13', NULL),
(143, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:104', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 5', 'progress_update', 104, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 09:58:19', NULL),
(144, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:105', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for System Development', 'progress_update', 105, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 09:58:26', NULL),
(145, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:106', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Testing', 'progress_update', 106, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 09:58:33', NULL),
(146, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:107', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Documentation', 'progress_update', 107, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 09:58:40', NULL),
(147, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:108', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 1', 'progress_update', 108, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 11:55:56', NULL),
(148, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:109', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 2', 'progress_update', 109, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 11:56:04', NULL),
(149, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:110', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 3', 'progress_update', 110, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 11:56:12', NULL),
(150, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:111', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 4', 'progress_update', 111, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 11:56:20', NULL),
(151, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:112', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 5', 'progress_update', 112, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 11:56:26', NULL),
(152, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:113', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for System Development', 'progress_update', 113, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 11:56:33', NULL),
(153, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:114', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Testing', 'progress_update', 114, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 11:56:39', NULL),
(154, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:115', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Documentation', 'progress_update', 115, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 11:56:48', NULL),
(155, 1354, '', 'student', 'approval:78', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 78, NULL, 'unread', '2026-09-19 11:57:28', NULL),
(156, 1354, '', 'student', 'approval:79', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 79, NULL, 'unread', '2026-09-19 11:57:39', NULL),
(157, 1354, '', 'student', 'approval:80', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 80, NULL, 'unread', '2026-09-19 11:57:51', NULL),
(158, 1354, '', 'student', 'approval:81', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 81, NULL, 'unread', '2026-09-19 12:39:11', NULL),
(159, 1354, '', 'student', 'approval:82', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 82, NULL, 'unread', '2026-09-19 12:39:23', NULL),
(160, 1354, '', 'student', 'approval:83', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 83, NULL, 'unread', '2026-09-19 12:39:34', NULL),
(161, 1354, '', 'student', 'approval:84', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 84, NULL, 'unread', '2026-09-19 12:39:47', NULL),
(162, 1354, '', 'student', 'approval:85', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 85, NULL, 'unread', '2026-09-19 12:40:31', NULL),
(163, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:116', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 1', 'progress_update', 116, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 13:17:29', NULL),
(164, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:117', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 2', 'progress_update', 117, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 13:17:35', NULL),
(165, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:118', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 3', 'progress_update', 118, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 13:17:43', NULL),
(166, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:119', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 4', 'progress_update', 119, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 13:17:50', NULL),
(167, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:120', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Chapter 5', 'progress_update', 120, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 13:17:56', NULL),
(168, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:121', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for System Development', 'progress_update', 121, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 13:18:02', NULL),
(169, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:122', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Testing', 'progress_update', 122, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 13:18:10', NULL),
(170, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:123', 'progress_update', 'New Progress Update', 'RG-2026-068 submitted a progress update for Documentation', 'progress_update', 123, '/sms2_system/modules/faculty/pages/research-progress.php?group=RG-2026-068', 'unread', '2026-09-19 13:18:18', NULL),
(171, 1354, '', 'student', 'approval:86', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 86, NULL, 'unread', '2026-09-19 13:18:34', NULL),
(172, 1354, '', 'student', 'approval:87', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 87, NULL, 'unread', '2026-09-19 13:19:51', NULL),
(173, 1354, '', 'student', 'approval:88', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 88, NULL, 'unread', '2026-09-19 13:20:07', NULL),
(174, 1354, '', 'student', 'approval:89', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 89, NULL, 'unread', '2026-09-19 13:20:19', NULL),
(175, 1354, '', 'student', 'approval:90', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 90, NULL, 'unread', '2026-09-19 13:20:39', NULL),
(176, 1354, '', 'student', 'approval:91', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 91, NULL, 'unread', '2026-09-19 13:20:46', NULL),
(177, 1354, '', 'student', 'approval:92', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 92, NULL, 'unread', '2026-09-19 13:21:00', NULL),
(178, 1354, '', 'student', 'approval:93', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 93, NULL, 'unread', '2026-09-19 13:21:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `research_progress_updates`
--

CREATE TABLE `research_progress_updates` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_plan_id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `milestone_id` int(10) UNSIGNED DEFAULT NULL,
  `submitted_by_user_id` int(10) UNSIGNED NOT NULL,
  `submitted_by_name` varchar(200) NOT NULL DEFAULT '',
  `update_title` varchar(300) NOT NULL,
  `accomplishments` text DEFAULT NULL,
  `problems_blockers` text DEFAULT NULL,
  `next_planned_activity` text DEFAULT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `attachment_original_name` varchar(300) DEFAULT NULL,
  `submission_token` varchar(64) DEFAULT NULL,
  `previous_progress` decimal(5,2) DEFAULT NULL,
  `new_progress` decimal(5,2) NOT NULL,
  `milestone_status` varchar(60) NOT NULL DEFAULT 'In Progress',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_progress_updates`
--

INSERT INTO `research_progress_updates` (`id`, `research_plan_id`, `research_group_id`, `milestone_id`, `submitted_by_user_id`, `submitted_by_name`, `update_title`, `accomplishments`, `problems_blockers`, `next_planned_activity`, `attachment_path`, `attachment_original_name`, `submission_token`, `previous_progress`, `new_progress`, `milestone_status`, `submitted_at`, `updated_at`) VALUES
(43, 21, 60, NULL, 9, 'Student User', 'DEVELOPMENT OF AI ASSISTED', 'sadas', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g60/u9/09a62b2daa1cce84684a82782b95b7df.docx', 'CRAD_Chapter_1_TO_4_KULANG-PA.docx', '8f3692e45ee0853f66293b2ec969314d', 0.00, 0.00, 'Submitted for Review', '2026-08-23 03:16:35', '2026-08-23 03:16:35'),
(53, 22, 61, 177, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'SADAS', '', 'ASDA', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/df3f82b96645757c73e791012ab36dc4.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'e90bcc3cc197bb965809cb9c2e0e3080', 0.00, 0.00, 'Approved', '2026-08-23 03:49:58', '2026-08-23 03:50:29'),
(54, 22, 61, 178, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'ADSAD', '', 'ASDAS', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/c17c49e54baa55cad91e60bd58bcad92.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '7972c30afb20aa7a048bbb0f1d2c3da3', 0.00, 0.00, 'Approved', '2026-08-23 03:50:08', '2026-08-23 03:50:36'),
(55, 22, 61, 179, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'ASDAS', '', 'ADAS', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/7e5a55afefbeda325fee95b26668600b.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '9b1e3f2664b72052437162d568588703', 0.00, 0.00, 'Approved', '2026-08-23 03:50:18', '2026-08-23 03:50:45'),
(56, 22, 61, 180, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdas', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/af25964fcccc0a65b09c503100ab6bd6.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '3bf4afb87e3dbede7e93604e623034ba', 0.00, 0.00, 'Approved', '2026-08-23 04:12:04', '2026-08-23 04:13:25'),
(57, 22, 61, 181, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdas', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/b0b3bb6b5becdf66dd97395b68d85f8d.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '5ccb88ca9b4db2be2a773b0c0d9feea0', 0.00, 0.00, 'Approved', '2026-08-23 04:12:11', '2026-08-23 04:13:35'),
(58, 22, 61, 182, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'adasd', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/ca491b2b5c87fa0313876eeca2cec331.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '4c8b62f4160a5c17900fb95039e90ee1', 0.00, 0.00, 'Approved', '2026-08-23 04:12:18', '2026-08-23 04:13:45'),
(59, 22, 61, 183, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdas', '', 'das', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/1eb10d997a17fe2c9f5ee73485b77ae8.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '2ee796709264f5ab003420cd374a5e94', 0.00, 0.00, 'Approved', '2026-08-23 04:12:25', '2026-08-23 04:13:52'),
(60, 22, 61, 184, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdas', '', 'das', NULL, NULL, '95b713da492cb4c94648d63edaf98090', 0.00, 0.00, 'Revision Requested', '2026-08-23 04:12:31', '2026-08-23 04:13:01'),
(61, 22, 61, 184, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdasd', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g61/u9/cff34883d0dec4263b79eddc43fcc992.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'ed9f89cc33102105cda0fd24e9757687', 0.00, 0.00, 'Approved', '2026-08-23 04:13:16', '2026-08-23 04:13:59'),
(62, 23, 62, 185, 9, 'Student User', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'asdas', '', 'das', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/e232da6a01b4a6d1fef55ef744cfd286.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '08abf973b072e98e204688196ccbeac3', 0.00, 0.00, 'Approved', '2026-08-28 15:30:17', '2026-08-28 15:32:47'),
(63, 23, 62, 186, 9, 'Student User', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'dasdas', '', 'das', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/5eda2ee2c5b63097d514c7fb0febe2cb.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '64a904159da3f2af9d545f8c2417c10f', 0.00, 0.00, 'Approved', '2026-08-28 15:30:54', '2026-08-28 15:32:53'),
(64, 23, 62, 187, 9, 'Student User', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'adasd', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/7c5f38451672efeefa6fc2f052755647.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'd43491d8f89c17c5c7f703da9b3576da', 0.00, 0.00, 'Approved', '2026-08-28 15:31:01', '2026-08-28 15:32:57'),
(65, 23, 62, 188, 9, 'Student User', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'asdasd', '', 'asda', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/d5738c98ea85c03003583de5a612bf42.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '14c3a48699c03c4d62efc66e2f3e1492', 0.00, 0.00, 'Approved', '2026-08-28 15:31:08', '2026-08-28 15:37:17'),
(66, 23, 62, 189, 9, 'Student User', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'asdasd', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/84f08f7484eb161bdcb8756a57e1b6c6.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '7c5eb2f4157c0aba222689bc613ec4e4', 0.00, 0.00, 'Approved', '2026-08-28 15:31:16', '2026-08-28 15:37:22'),
(67, 23, 62, 190, 9, 'Student User', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'asdas', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/c198a6471dc795e4a704cfb2ef3f808f.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'f3dafeb930abcac8b726768777684d76', 0.00, 0.00, 'Approved', '2026-08-28 15:31:23', '2026-08-28 15:37:29'),
(68, 23, 62, 191, 9, 'Student User', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'asdas', '', 'dasd', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/b875bb0942866180b655526b5d79cb9c.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'afa92ac71725ee096ea19bdbeca42a00', 0.00, 0.00, 'Approved', '2026-08-28 15:31:30', '2026-08-28 15:37:35'),
(69, 23, 62, 192, 9, 'Student User', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'dasd', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g62/u9/c4ad5851636b7b71d099039ebb69a252.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'bf8c8c7f2a614664e2dbdad792ad9251', 0.00, 0.00, 'Approved', '2026-08-28 15:31:36', '2026-08-28 15:37:44'),
(70, 24, 63, 193, 9, 'Student User', 'DEVELOPMENT OF AI', 'asdas', '', 'dasdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/28b05d83f4c150c45929b60ec2eee255.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'c06285941c7be6c2ae3f81bb8c31a984', 0.00, 0.00, 'Approved', '2026-08-28 16:28:35', '2026-08-28 16:29:27'),
(71, 24, 63, 194, 9, 'Student User', 'DEVELOPMENT OF AI', 'asda', '', 'dasdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/caf0f3c59320c81adae7ce19efc7c289.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'bcd4b023262a1a8a28a803fb59e15908', 0.00, 0.00, 'Approved', '2026-08-28 16:28:41', '2026-08-28 16:29:31'),
(72, 24, 63, 195, 9, 'Student User', 'DEVELOPMENT OF AI', 'asdasd', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/77701e54e11148f3638382199cef968e.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '7a4a07d369b5dcbf04280267ddbc7023', 0.00, 0.00, 'Approved', '2026-08-28 16:28:47', '2026-08-28 16:29:36'),
(73, 24, 63, 196, 9, 'Student User', 'DEVELOPMENT OF AI', 'asdas', '', 'asda', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/3e2c3d7261ed6d038c8255773b17d194.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'de709355f4f64ed523aa6da44e6d4685', 0.00, 0.00, 'Approved', '2026-08-28 16:28:54', '2026-08-28 16:33:58'),
(74, 24, 63, 197, 9, 'Student User', 'DEVELOPMENT OF AI', 'asdas', '', 'asda', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/0f1662a4f091481e6d76664b0836e6e6.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'e880b3899035ccefcdac1b654243e0d2', 0.00, 0.00, 'Approved', '2026-08-28 16:29:01', '2026-08-28 16:34:03'),
(75, 24, 63, 198, 9, 'Student User', 'DEVELOPMENT OF AI', 'asdasd', '', 'asd', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/93c3ee330a6b0a0dbcc31b66123df4b8.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'd5dc84f912d4033ab7c994a5832b7d2b', 0.00, 0.00, 'Approved', '2026-08-28 16:29:08', '2026-08-28 16:34:11'),
(76, 24, 63, 199, 9, 'Student User', 'DEVELOPMENT OF AI', 'fsasf', '', 'fasfa', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/e7b47a94f20de26b24e04e1dd7f246ac.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '1b6099d2ef250a52d784c49d6def7db1', 0.00, 0.00, 'Approved', '2026-08-28 16:29:19', '2026-08-28 16:34:16'),
(77, 24, 63, 200, 9, 'Student User', 'DEVELOPMENT OF AI', 'asdas', '', 'das', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g63/u9/c7ad58d98d311f6fd3ab1e6a4bf9a098.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '0026fd1ce013574bf6a28b1a7e896c67', 0.00, 0.00, 'Approved', '2026-08-28 16:33:53', '2026-08-28 16:34:23'),
(78, 25, 64, 201, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'sadasd', '', 'asdas', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/1e8f6014e3459a3ebbe0e8362d99d252.pdf', 'OLIVEROS CV.pdf', 'd1a4da63116003c07aa12ad5ffe41d1b', 0.00, 0.00, 'Approved', '2026-08-31 05:28:26', '2026-08-31 05:29:47'),
(79, 25, 64, 202, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdsa', '', 'sadas', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/0f04ea324e3230f9eb7dbff5fc1f0889.pdf', 'OLIVEROS CV.pdf', '6beb825e0ee6318a8e6a2c54d3bbb9b3', 0.00, 0.00, 'Approved', '2026-08-31 05:28:34', '2026-08-31 05:29:55'),
(80, 25, 64, 203, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdasd', '', 'asda', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/962a28233c08de26f2e9ef1209db1e82.pdf', 'OLIVEROS CV.pdf', '4a0fbed0e485071490abe7597e04bf16', 0.00, 0.00, 'Approved', '2026-08-31 05:28:45', '2026-08-31 05:30:03'),
(81, 25, 64, 204, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdasd', '', 'asdas', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/cb8e4d50ad6222d2ac4243ba948e77b0.pdf', 'OLIVEROS CV.pdf', 'ef856f3399d1eb5c6220293ae928b8a5', 0.00, 0.00, 'Approved', '2026-08-31 05:28:53', '2026-08-31 06:15:08'),
(82, 25, 64, 205, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'adsad', '', 'asda', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/ddd91c717013890ffa4bd9e6503f9789.pdf', 'OLIVEROS CV.pdf', '588a7637ead45e533964661ef45235cc', 0.00, 0.00, 'Approved', '2026-08-31 05:29:03', '2026-08-31 06:15:17'),
(83, 25, 64, 206, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdas', '', 'sadas', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/3c49d34ce74b8c1fb4f6a3820bc4fd77.pdf', 'Diaz CV.pdf_20260813_105004_0000.pdf', 'f3b467991b22a417cc5ac786133470cb', 0.00, 0.00, 'Approved', '2026-08-31 05:29:15', '2026-08-31 06:15:28'),
(84, 25, 64, 207, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdas', '', 'sdada', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/e9b4c863c0cdcb67975619e7f63d9e57.pdf', 'OLIVEROS CV.pdf', '4909840571a5dd9de8c7713abea1e589', 0.00, 0.00, 'Approved', '2026-08-31 05:29:24', '2026-08-31 06:15:35'),
(85, 25, 64, 208, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdad', '', 'sadas', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g64/u9/c1b6a2b034dbec7c9fe660ba7ab5cddb.pdf', 'OLIVEROS CV.pdf', '106cb9c755aefea1b9e2cd409390e32f', 0.00, 0.00, 'Approved', '2026-08-31 05:29:32', '2026-08-31 06:15:42'),
(86, 26, 65, 209, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'eadsa', '', 'asdas', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/0b9cb630bca2013fc5b5055241753819.pdf', 'OLIVEROS CV.pdf', '83ab9604308555f7be25b0e22e83a444', 0.00, 0.00, 'Approved', '2026-08-31 09:34:32', '2026-08-31 09:36:20'),
(87, 26, 65, 210, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'sdfds', '', 'sdfsd', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/ef6e446036b4ba2a88d92faf65ddc654.pdf', 'OLIVEROS CV.pdf', '09bb616bb90ee961baeda499028434b3', 0.00, 0.00, 'Approved', '2026-08-31 09:34:41', '2026-08-31 09:36:29'),
(88, 26, 65, 211, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asdas', '', 'sadas', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/b0b0378a61ce66eaaca964143b30b63d.pdf', 'OLIVEROS CV.pdf', '6880a5506088e0b98ba8ee2cd202c193', 0.00, 0.00, 'Approved', '2026-08-31 09:34:49', '2026-08-31 09:36:53'),
(89, 26, 65, 212, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'sadas', '', 'asda', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/06cc39770a42bf06fff7da5285a54c52.pdf', 'OLIVEROS CV.pdf', 'c6d14c3b5a11e89648f42eb1a5922592', 0.00, 0.00, 'Submitted for Review', '2026-08-31 09:35:01', '2026-08-31 09:35:01'),
(90, 26, 65, 213, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'sadsad', '', 'asdas', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/4b2c7b8f8662c0acfecf51b09123ed51.pdf', 'OLIVEROS CV.pdf', '37dcc8cb4c7ac5030b7e408f23e510db', 0.00, 0.00, 'Submitted for Review', '2026-08-31 09:35:12', '2026-08-31 09:35:12'),
(91, 26, 65, 214, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'eqweq', '', 'qweqw', NULL, NULL, 'f3c04411627f6e3711879b7b2670948c', 0.00, 0.00, 'Submitted for Review', '2026-08-31 09:35:29', '2026-08-31 09:35:29'),
(92, 26, 65, 215, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'qweqw', '', 'qweq', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/3f2e2e77ca556957100166cd43b11e4b.pdf', 'OLIVEROS CV.pdf', '4b86cfc220d70f494f5a694899731d73', 0.00, 0.00, 'Submitted for Review', '2026-08-31 09:35:37', '2026-08-31 09:35:37'),
(93, 26, 65, 216, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'fdssdfsd', '', 'weqwe', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/afc9627ed4ff5d61ef4658268ee83f4c.pdf', 'OLIVEROS CV.pdf', '90c4c6b20d3280f3e1c556985d525e17', 0.00, 0.00, 'Submitted for Review', '2026-08-31 09:35:48', '2026-08-31 09:35:48'),
(94, 27, 66, 217, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'asda', '', 'asdas', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g66/u9/bcf642c163a3c02786b8974d291c1c83.docx', 'Abejuela , Week_8_Online_Activity_Software_Media_Management_Tools.docx', 'f1bebb5d9f17bbcbfe27b43abb651eb4', 0.00, 0.00, 'Submitted for Review', '2026-09-02 07:34:55', '2026-09-02 07:34:55'),
(95, 27, 66, 218, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'adas', '', 'asda', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g66/u9/748584c6106bcd3976f979f98733ba42.docx', 'Abejuela , Week_8_Online_Activity_Software_Media_Management_Tools.docx', 'edc075af33bbcab781219fde37957d33', 0.00, 0.00, 'Submitted for Review', '2026-09-02 07:35:10', '2026-09-02 07:35:10'),
(96, 27, 66, 219, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'adas', '', 'asda', 'F:\\xampp\\htdocs\\SMS2_system/storage/uploads/research_progress/g66/u9/1ac8a01fdb4c8c5dfa9e521c75e178cb.docx', 'Abejuela , Week_8_Online_Activity_Software_Media_Management_Tools.docx', 'b2befdbcbe8ba6e72c9a7553fafbacd1', 0.00, 0.00, 'Submitted for Review', '2026-09-02 07:35:19', '2026-09-02 07:35:19'),
(97, 28, 69, 225, 1354, 'John Kenneth Abejuela', 'ASDASD', 'asdsa', 'dasd', 'asda', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g69/u1354/bd15754815587a1135b47543a9cdc15e.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '54903178e81843de761865e973864dc4', 0.00, 0.00, 'Approved', '2026-09-19 00:35:41', '2026-09-19 00:36:30'),
(98, 28, 69, 226, 1354, 'John Kenneth Abejuela', 'ASDASD', 'asdas', 'asdas', 'dasd', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g69/u1354/8a3a08780c7cb01bc0bb2ae56da852ce.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'e9f1c5c3831ef34edba39c5578a89266', 0.00, 0.00, 'Approved', '2026-09-19 00:35:48', '2026-09-19 00:36:43'),
(99, 28, 69, 227, 1354, 'John Kenneth Abejuela', 'ASDASD', 'sada', 'adsasd', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g69/u1354/eb174f9264d5a50a16324fd099a42d88.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'a77035acf7ed27a195b536cbcf736074', 0.00, 0.00, 'Approved', '2026-09-19 00:35:56', '2026-09-19 00:36:57'),
(100, 29, 70, 233, 1354, 'John Kenneth Abejuela', 'AI ANALYTICS DATA DRIVEN', 'adasd', '', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/86451a18bda66baf69d7ef8aa90313a4.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '7c521a8097f52e5b6b130d8d5f37981b', 0.00, 0.00, 'Approved', '2026-09-19 09:17:10', '2026-09-19 09:17:51'),
(101, 29, 70, 234, 1354, 'John Kenneth Abejuela', 'AI ANALYTICS DATA DRIVEN', 'asdas', '', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/fda85aaef9bb57f3e49e1ac713de0b02.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'f9d80c0c39ac8b32a0ad5a859d598605', 0.00, 0.00, 'Approved', '2026-09-19 09:17:18', '2026-09-19 09:18:08'),
(102, 29, 70, 235, 1354, 'John Kenneth Abejuela', 'AI ANALYTICS DATA DRIVEN', 'asdas', '', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/1ec0d39d90abcd14e9dba8ed9ca58c98.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '7863b5d0812cc025f3927f87d7777a6e', 0.00, 0.00, 'Approved', '2026-09-19 09:17:26', '2026-09-19 09:18:25'),
(103, 29, 70, 236, 1354, 'John Kenneth Abejuela', 'AI ANALYTICS DATA DRIVEN', 'asdas', 'dasd', 'asda', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/2557fb5dc709e1a491f29c071e1633f1.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '566000f65b3384273a6024f85aed240d', 0.00, 0.00, 'Submitted for Review', '2026-09-19 09:58:13', '2026-09-19 09:58:13'),
(104, 29, 70, 237, 1354, 'John Kenneth Abejuela', 'AI ANALYTICS DATA DRIVEN', 'asdasd', '', 'asda', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/68ae9170245a394321f58192c74c9f52.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '4e9c78cabe5cf70e5074d05622a4cbdb', 0.00, 0.00, 'Submitted for Review', '2026-09-19 09:58:19', '2026-09-19 09:58:19'),
(105, 29, 70, 238, 1354, 'John Kenneth Abejuela', 'AI ANALYTICS DATA DRIVEN', 'adas', '', 'asda', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/b88927393754062bda7d375379d619a7.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '07583ebff8aa41dcce0fdb5bff0dca26', 0.00, 0.00, 'Submitted for Review', '2026-09-19 09:58:26', '2026-09-19 09:58:26'),
(106, 29, 70, 239, 1354, 'John Kenneth Abejuela', 'AI ANALYTICS DATA DRIVEN', 'asdasd', '', 'asda', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/a350aa4a5be5f211fb8b321d547c705f.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '9d63583dd4f934fde6c76327433ffc6a', 0.00, 0.00, 'Submitted for Review', '2026-09-19 09:58:33', '2026-09-19 09:58:33'),
(107, 29, 70, 240, 1354, 'John Kenneth Abejuela', 'AI ANALYTICS DATA DRIVEN', 'asdas', '', 'ada', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g70/u1354/eaf27663a2127f934bd4314dd3666a9a.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '06de1d21c6b38834bb210e94179f81c0', 0.00, 0.00, 'Submitted for Review', '2026-09-19 09:58:40', '2026-09-19 09:58:40'),
(108, 30, 71, 241, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asdas', '', 'asda', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/c3ffd43c12655848e466a77c0d122fd6.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '79ce9333124bfc6911a3cbbd04106230', 0.00, 0.00, 'Approved', '2026-09-19 11:55:56', '2026-09-19 11:57:28'),
(109, 30, 71, 242, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asda', '', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/6fa8a645f0be0e24573e30f2695f253d.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '46cd8948d33890007dd22fc63df7dbdb', 0.00, 0.00, 'Approved', '2026-09-19 11:56:04', '2026-09-19 11:57:39'),
(110, 30, 71, 243, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'zxccxassad', '', 'dsadas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/7845b03e0f469725c3fd5c2cbc012cef.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '6ecda2e82d2ff4ddf8fe09f835d77cb5', 0.00, 0.00, 'Approved', '2026-09-19 11:56:12', '2026-09-19 11:57:51'),
(111, 30, 71, 244, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asdas', '', 'dasda', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/673501f692e757138ac909c07ed879e2.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '8ad1260d8391e130ba410e1b0f1cc955', 0.00, 0.00, 'Approved', '2026-09-19 11:56:20', '2026-09-19 12:39:11'),
(112, 30, 71, 245, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asdas', '', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/04d32eaa9c11c266d965f35729ce3353.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '9aa11f0393624e87982af3e29427e402', 0.00, 0.00, 'Approved', '2026-09-19 11:56:26', '2026-09-19 12:39:23'),
(113, 30, 71, 246, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asdas', '', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/a6662c75278da0bd595e7aef44fe4120.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '4618109545b6c2f2d6ec71c81da51f86', 0.00, 0.00, 'Approved', '2026-09-19 11:56:33', '2026-09-19 12:39:34'),
(114, 30, 71, 247, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asdasd', '', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/45d339273a5cfc7c12378926418f3b54.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '7bd42d5d1e1a0f924a46f49f42a67a02', 0.00, 0.00, 'Approved', '2026-09-19 11:56:39', '2026-09-19 12:39:47'),
(115, 30, 71, 248, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'ad', '', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g71/u1354/8720e2d8c76e3ae01a4eed08332edd30.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '6938aef338f923aa56244a7936911151', 0.00, 0.00, 'Approved', '2026-09-19 11:56:48', '2026-09-19 12:40:31'),
(116, 31, 72, 249, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'adasd', '', 'asd', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/67ed2a98aaef3ffd396b3e8e4ce0dca4.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '807e9122411d1f764102d4a2769f1a0a', 0.00, 0.00, 'Approved', '2026-09-19 13:17:29', '2026-09-19 13:18:34'),
(117, 31, 72, 250, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'adas', '', 'dasd', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/60986978ae2a423220f60e15d33a41e6.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '822550fabd87239cbfa65fd767b2f71a', 0.00, 0.00, 'Approved', '2026-09-19 13:17:35', '2026-09-19 13:19:51'),
(118, 31, 72, 251, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asdas', '', 'dasda', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/28358606fe317468a3ee2795675dc154.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '8d659c52351108c8f1bd87cc1567d2dc', 0.00, 0.00, 'Approved', '2026-09-19 13:17:43', '2026-09-19 13:20:07'),
(119, 31, 72, 252, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asd', '', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/336c492c69cfc6b6cba791537edc3789.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '6004d1aa2cc430a9b42e391e80f7094f', 0.00, 0.00, 'Approved', '2026-09-19 13:17:50', '2026-09-19 13:20:19'),
(120, 31, 72, 253, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asdas', '', 'asda', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/3b5197cf57b78b2da650328e4f1c7168.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'b23f26324b96b6ed2b1711171f2f8b74', 0.00, 0.00, 'Approved', '2026-09-19 13:17:56', '2026-09-19 13:20:39'),
(121, 31, 72, 254, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asd', '', 'asd', NULL, NULL, '2e002225b907770100dd23a00ceedd07', 0.00, 0.00, 'Approved', '2026-09-19 13:18:02', '2026-09-19 13:20:46'),
(122, 31, 72, 255, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asd', '', 'asd', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/24e9c84bad5f148cd03b484a9899c23d.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', '8e8b0d5458bbee47cf0655488555b4b0', 0.00, 0.00, 'Approved', '2026-09-19 13:18:10', '2026-09-19 13:21:00'),
(123, 31, 72, 256, 1354, 'John Kenneth Abejuela', 'AI DRIVEN DATA ANALYTICS', 'asdas', '', 'asdas', 'F:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g72/u1354/6a400c4fd4fa81995fc6ee3b09be3b39.docx', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'bc88f6c66aa1c81b22e1dd842f03dc5a', 0.00, 0.00, 'Approved', '2026-09-19 13:18:18', '2026-09-19 13:21:12');

-- --------------------------------------------------------

--
-- Table structure for table `research_proposals`
--

CREATE TABLE `research_proposals` (
  `id` int(10) UNSIGNED NOT NULL,
  `ref_code` varchar(30) NOT NULL COMMENT 'Auto-generated reference e.g. CRD-2026-00001',
  `proposal_number` varchar(30) DEFAULT NULL COMMENT 'Official number generated after approved proposal registration',
  `research_title` varchar(500) NOT NULL,
  `program_course` varchar(200) NOT NULL,
  `year_section` varchar(100) NOT NULL,
  `college_department` varchar(200) NOT NULL,
  `research_adviser` varchar(200) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `rep_name` varchar(200) NOT NULL,
  `rep_id` varchar(50) NOT NULL,
  `rep_email` varchar(200) NOT NULL,
  `rep_contact` varchar(20) NOT NULL,
  `status` enum('Submitted','In Progress','Panel Assigned','Approved','Returned') NOT NULL DEFAULT 'Submitted',
  `progress` tinyint(3) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Progress % shown in tracking',
  `date_submitted` date NOT NULL,
  `approved_at` datetime DEFAULT NULL COMMENT 'Date/time when tracking proposal was approved',
  `registered_at` datetime DEFAULT NULL COMMENT 'Date/time when approved proposal received official proposal number',
  `registration_status` enum('Pending','Registered') NOT NULL DEFAULT 'Pending',
  `signature_data` mediumtext DEFAULT NULL COMMENT 'Base64 PNG of representative signature',
  `submitted_by_user` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to sms2_db users (optional)',
  `notes` text DEFAULT NULL COMMENT 'CRAD officer notes',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_revision_cycles`
--

CREATE TABLE `research_revision_cycles` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `defense_schedule_id` int(10) UNSIGNED NOT NULL,
  `official_result` varchar(60) NOT NULL DEFAULT 'APPROVED WITH REVISION',
  `revision_status` varchar(60) NOT NULL DEFAULT 'Needs Revision',
  `opened_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `original_name` varchar(255) NOT NULL DEFAULT '',
  `stored_subdir` varchar(180) NOT NULL DEFAULT '',
  `stored_name` varchar(120) NOT NULL DEFAULT '',
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `file_mime` varchar(120) NOT NULL DEFAULT '',
  `submission_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_services_clearances`
--

CREATE TABLE `research_services_clearances` (
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

-- --------------------------------------------------------

--
-- Table structure for table `research_venues`
--

CREATE TABLE `research_venues` (
  `id` int(10) UNSIGNED NOT NULL,
  `venue_name` varchar(160) NOT NULL,
  `capacity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `venue_type` varchar(80) NOT NULL DEFAULT '',
  `status` varchar(40) NOT NULL DEFAULT 'Available',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `research_venues`
--

INSERT INTO `research_venues` (`id`, `venue_name`, `capacity`, `venue_type`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CRAD Conference Room', 30, 'Conference Room', 'Available', NULL, '2026-08-10 13:50:31', '2026-08-10 13:50:31'),
(2, 'Research Room 1', 25, 'Research Room', 'Available', NULL, '2026-08-10 13:50:31', '2026-08-10 13:50:31'),
(3, 'Research Room 2', 25, 'Research Room', 'Available', NULL, '2026-08-10 13:50:31', '2026-08-10 13:50:31'),
(4, 'AVR Room', 100, 'Auditorium', 'Available', NULL, '2026-08-10 13:50:31', '2026-08-10 14:20:53'),
(5, 'Computer Laboratory 1', 40, 'Laboratory', 'Available', NULL, '2026-08-10 13:50:31', '2026-08-10 13:50:31');

-- --------------------------------------------------------

--
-- Table structure for table `title_approvals`
--

CREATE TABLE `title_approvals` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(50) NOT NULL DEFAULT '',
  `student_user_id` int(10) UNSIGNED DEFAULT NULL,
  `student_name` varchar(200) NOT NULL DEFAULT '',
  `submission_date` date NOT NULL,
  `department` varchar(200) NOT NULL DEFAULT '',
  `proposed_title` varchar(500) NOT NULL DEFAULT '',
  `discipline_cluster` varchar(200) NOT NULL DEFAULT '',
  `primary_sdg` varchar(120) NOT NULL DEFAULT '',
  `research_agenda` varchar(300) NOT NULL DEFAULT '',
  `sdg_justification` text NOT NULL,
  `members_json` longtext NOT NULL,
  `adviser_name` varchar(200) NOT NULL DEFAULT '',
  `adviser_email` varchar(200) NOT NULL DEFAULT '',
  `coordinator_name` varchar(200) NOT NULL DEFAULT '',
  `proposal_number` varchar(30) DEFAULT NULL,
  `status` enum('Pending','Reviewed','Approved','Returned') NOT NULL DEFAULT 'Pending',
  `adviser_remarks` text DEFAULT NULL,
  `adviser_signature_data` mediumtext DEFAULT NULL,
  `coordinator_status` varchar(30) NOT NULL DEFAULT 'Not Ready',
  `coordinator_remarks` text DEFAULT NULL,
  `coordinator_screening_json` text DEFAULT NULL,
  `coordinator_signature_data` mediumtext DEFAULT NULL,
  `coordinator_reviewed_at` datetime DEFAULT NULL,
  `crad_status` varchar(30) NOT NULL DEFAULT 'Not Ready',
  `crad_signature_data` mediumtext DEFAULT NULL,
  `crad_reviewed_at` datetime DEFAULT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `title_approvals`
--
DELIMITER $$
CREATE TRIGGER `trg_title_approvals_after_delete` AFTER DELETE ON `title_approvals` FOR EACH ROW BEGIN
            DELETE FROM research_coordinator_assignments
             WHERE (title_approval_id IS NOT NULL AND title_approval_id = OLD.id)
                OR (OLD.student_id IS NOT NULL AND OLD.student_id <> '' AND student_id = OLD.student_id)
                OR (OLD.student_id IS NOT NULL AND OLD.student_id <> '' AND group_number = CONCAT('STU-', OLD.student_id))
                OR (OLD.proposal_number IS NOT NULL AND OLD.proposal_number <> '' AND proposal_number = OLD.proposal_number);

            DELETE FROM research_adviser_assignments
             WHERE (OLD.student_id IS NOT NULL AND OLD.student_id <> '' AND student_id = OLD.student_id)
                OR (OLD.student_id IS NOT NULL AND OLD.student_id <> '' AND group_number = CONCAT('STU-', OLD.student_id))
                OR (OLD.proposal_number IS NOT NULL AND OLD.proposal_number <> '' AND proposal_number = OLD.proposal_number);
        END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chapter_evaluations`
--
ALTER TABLE `chapter_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chapter_evaluation_submission` (`submission_id`),
  ADD KEY `idx_chapter_eval_evaluator` (`evaluator_user_id`),
  ADD KEY `idx_chapter_eval_group` (`research_group_id`),
  ADD KEY `idx_chapter_eval_created` (`created_at`);

--
-- Indexes for table `chapter_evaluation_notifications`
--
ALTER TABLE `chapter_evaluation_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chapter_notification_event` (`event_key`),
  ADD KEY `idx_chapter_notification_recipient` (`recipient_user_id`,`recipient_role`,`recipient_email`),
  ADD KEY `idx_chapter_notification_submission` (`submission_id`),
  ADD KEY `idx_chapter_notification_created` (`created_at`);

--
-- Indexes for table `chapter_submissions`
--
ALTER TABLE `chapter_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chapter_version` (`research_group_id`,`chapter_number`,`version_number`),
  ADD UNIQUE KEY `uniq_chapter_token` (`submission_token`),
  ADD KEY `idx_chapter_status` (`status`),
  ADD KEY `idx_chapter_group` (`research_group_id`),
  ADD KEY `idx_chapter_student` (`submitted_by_user`),
  ADD KEY `idx_chapter_updated` (`updated_at`);

--
-- Indexes for table `chapter_submission_history`
--
ALTER TABLE `chapter_submission_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chapter_history_submission` (`submission_id`),
  ADD KEY `idx_chapter_history_group` (`research_group_id`),
  ADD KEY `idx_chapter_history_created` (`created_at`);

--
-- Indexes for table `final_defense_evaluations`
--
ALTER TABLE `final_defense_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_final_panel_submission` (`defense_schedule_id`,`panel_user_id`),
  ADD KEY `idx_final_group` (`research_group_id`),
  ADD KEY `idx_final_panel` (`panel_user_id`),
  ADD KEY `idx_final_status` (`status`);

--
-- Indexes for table `final_defense_recommendations`
--
ALTER TABLE `final_defense_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_fdr_group` (`research_group_id`),
  ADD KEY `idx_fdr_status` (`status`);

--
-- Indexes for table `final_manuscript_approvals`
--
ALTER TABLE `final_manuscript_approvals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_fma_group` (`research_group_id`);

--
-- Indexes for table `grant_applications`
--
ALTER TABLE `grant_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_ga_token` (`submission_token`),
  ADD KEY `idx_ga_opportunity` (`grant_opportunity_id`),
  ADD KEY `idx_ga_group` (`research_group_id`),
  ADD KEY `idx_ga_status` (`status`),
  ADD KEY `idx_ga_submitted` (`submitted_at`);

--
-- Indexes for table `grant_document_repository`
--
ALTER TABLE `grant_document_repository`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gdr_application` (`grant_application_id`),
  ADD KEY `idx_gdr_reference` (`archive_reference`),
  ADD KEY `idx_gdr_archived` (`archived_at`);

--
-- Indexes for table `grant_document_repository_items`
--
ALTER TABLE `grant_document_repository_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gdri_repository` (`repository_id`),
  ADD KEY `idx_gdri_application` (`grant_application_id`),
  ADD KEY `idx_gdri_category` (`category`);

--
-- Indexes for table `grant_final_output_submissions`
--
ALTER TABLE `grant_final_output_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gfos_application` (`grant_application_id`),
  ADD KEY `idx_gfos_status` (`status`),
  ADD KEY `idx_gfos_submitted` (`submitted_at`);

--
-- Indexes for table `grant_funded_progress_evidence`
--
ALTER TABLE `grant_funded_progress_evidence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gfpe_application` (`grant_application_id`),
  ADD KEY `idx_gfpe_milestone` (`milestone_id`),
  ADD KEY `idx_gfpe_created` (`created_at`);

--
-- Indexes for table `grant_funded_project_milestones`
--
ALTER TABLE `grant_funded_project_milestones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gfpm_app_order` (`grant_application_id`,`milestone_order`),
  ADD KEY `idx_gfpm_application` (`grant_application_id`),
  ADD KEY `idx_gfpm_status` (`status`);

--
-- Indexes for table `grant_funding_disbursements`
--
ALTER TABLE `grant_funding_disbursements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gfd_app_tranche` (`grant_application_id`,`tranche_number`),
  ADD KEY `idx_gfd_application` (`grant_application_id`),
  ADD KEY `idx_gfd_status` (`status`);

--
-- Indexes for table `grant_opportunities`
--
ALTER TABLE `grant_opportunities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_go_status` (`status`),
  ADD KEY `idx_go_deadline` (`application_deadline`),
  ADD KEY `idx_go_created_by` (`created_by_user_id`);

--
-- Indexes for table `grant_proposal_approval_steps`
--
ALTER TABLE `grant_proposal_approval_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpas_workflow_step` (`workflow_id`,`step_key`),
  ADD KEY `idx_gpas_application` (`grant_application_id`),
  ADD KEY `idx_gpas_status` (`status`),
  ADD KEY `idx_gpas_role` (`approver_role_key`);

--
-- Indexes for table `grant_proposal_approval_workflows`
--
ALTER TABLE `grant_proposal_approval_workflows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpaw_application` (`grant_application_id`),
  ADD KEY `idx_gpaw_status` (`workflow_status`),
  ADD KEY `idx_gpaw_current_step` (`current_step_key`);

--
-- Indexes for table `grant_proposal_evaluations`
--
ALTER TABLE `grant_proposal_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpe_app_eval_ver` (`grant_application_id`,`evaluator_user_id`,`proposal_version`),
  ADD KEY `idx_gpe_application` (`grant_application_id`),
  ADD KEY `idx_gpe_evaluator` (`evaluator_user_id`),
  ADD KEY `idx_gpe_submitted` (`submitted_at`);

--
-- Indexes for table `grant_proposal_notifications`
--
ALTER TABLE `grant_proposal_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpn_event` (`event_key`),
  ADD KEY `idx_gpn_recipient_user` (`recipient_user_id`),
  ADD KEY `idx_gpn_application` (`grant_application_id`),
  ADD KEY `idx_gpn_created` (`created_at`);

--
-- Indexes for table `grant_proposal_versions`
--
ALTER TABLE `grant_proposal_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpv_app_ver` (`grant_application_id`,`version_number`),
  ADD KEY `idx_gpv_application` (`grant_application_id`),
  ADD KEY `idx_gpv_submitted` (`submitted_at`);

--
-- Indexes for table `grant_publications_ip_repository`
--
ALTER TABLE `grant_publications_ip_repository`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpip_application` (`grant_application_id`),
  ADD KEY `idx_gpip_reference` (`repository_reference`),
  ADD KEY `idx_gpip_verified` (`verified_at`);

--
-- Indexes for table `manuscript_evaluations`
--
ALTER TABLE `manuscript_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_meval_submission` (`submission_id`),
  ADD KEY `idx_meval_group` (`research_group_id`);

--
-- Indexes for table `manuscript_submissions`
--
ALTER TABLE `manuscript_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_manuscript_version` (`research_group_id`,`version_number`),
  ADD UNIQUE KEY `uniq_manuscript_token` (`submission_token`),
  ADD KEY `idx_manuscript_status` (`status`),
  ADD KEY `idx_manuscript_group` (`research_group_id`);

--
-- Indexes for table `panel_assignment_notifications`
--
ALTER TABLE `panel_assignment_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_panel_assignment_notification` (`event_key`),
  ADD KEY `idx_panel_notification_recipient` (`recipient_user_id`,`recipient_role`,`recipient_email`),
  ADD KEY `idx_panel_notification_created` (`created_at`);

--
-- Indexes for table `panel_member_availability`
--
ALTER TABLE `panel_member_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_panel_availability_user` (`panel_user_id`),
  ADD KEY `idx_panel_availability_status` (`availability_status`);

--
-- Indexes for table `preoral_defense_evaluations`
--
ALTER TABLE `preoral_defense_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_preoral_panel_submission` (`defense_schedule_id`,`panel_user_id`),
  ADD KEY `idx_preoral_group` (`research_group_id`),
  ADD KEY `idx_preoral_panel` (`panel_user_id`),
  ADD KEY `idx_preoral_status` (`status`);

--
-- Indexes for table `proposal_documents`
--
ALTER TABLE `proposal_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pd_proposal` (`proposal_id`);

--
-- Indexes for table `proposal_drafts`
--
ALTER TABLE `proposal_drafts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_proposal_draft_student_type` (`student_id`,`form_type`);

--
-- Indexes for table `proposal_members`
--
ALTER TABLE `proposal_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proposal` (`proposal_id`);

--
-- Indexes for table `proposal_status_logs`
--
ALTER TABLE `proposal_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_psl_proposal` (`proposal_id`);

--
-- Indexes for table `publications`
--
ALTER TABLE `publications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pub_group` (`research_group_id`),
  ADD KEY `idx_pub_status` (`status`);

--
-- Indexes for table `research_adviser_assignments`
--
ALTER TABLE `research_adviser_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_raa_adviser_identity` (`adviser_email`,`adviser_name`),
  ADD KEY `idx_raa_group` (`research_group_id`),
  ADD KEY `idx_raa_proposal` (`proposal_id`),
  ADD KEY `idx_raa_group_number` (`group_number`),
  ADD KEY `idx_raa_status` (`assignment_status`),
  ADD KEY `idx_raa_user` (`adviser_user_id`),
  ADD KEY `idx_raa_student` (`student_id`);

--
-- Indexes for table `research_clearance_notifications`
--
ALTER TABLE `research_clearance_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rsc_notif_event` (`event_key`),
  ADD KEY `idx_rsc_notif_recipient` (`recipient_user_id`,`recipient_role`);

--
-- Indexes for table `research_clearance_payments`
--
ALTER TABLE `research_clearance_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rcp_group_stage` (`research_group_id`,`research_stage`),
  ADD KEY `idx_rcp_group` (`research_group_id`),
  ADD KEY `idx_rcp_status` (`status`);

--
-- Indexes for table `research_coordinator_assignments`
--
ALTER TABLE `research_coordinator_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rca_group_number` (`group_number`),
  ADD UNIQUE KEY `uniq_rca_group_coordinator` (`research_group_id`,`coordinator_user_id`),
  ADD UNIQUE KEY `uniq_rca_student_id` (`student_id`),
  ADD KEY `idx_rca_group` (`research_group_id`),
  ADD KEY `idx_rca_title_approval` (`title_approval_id`),
  ADD KEY `idx_rca_status` (`status`),
  ADD KEY `idx_rca_student` (`student_id`);

--
-- Indexes for table `research_defense_schedules`
--
ALTER TABLE `research_defense_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rds_proposal_number` (`proposal_number`),
  ADD KEY `idx_rds_status` (`status`),
  ADD KEY `idx_rds_proposal_id` (`proposal_id`),
  ADD KEY `idx_rds_venue_time` (`venue_id`,`defense_datetime`,`defense_end_datetime`),
  ADD KEY `idx_rds_group_time` (`research_group_id`,`defense_datetime`,`defense_end_datetime`),
  ADD KEY `idx_rds_group_number` (`group_number`);

--
-- Indexes for table `research_groups`
--
ALTER TABLE `research_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_number` (`group_number`),
  ADD UNIQUE KEY `proposal_id` (`proposal_id`),
  ADD UNIQUE KEY `title_approval_id` (`title_approval_id`),
  ADD KEY `idx_rg_proposal_number` (`proposal_number`);

--
-- Indexes for table `research_milestones`
--
ALTER TABLE `research_milestones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rm_plan_name` (`research_plan_id`,`milestone_name`),
  ADD KEY `idx_rm_plan` (`research_plan_id`),
  ADD KEY `idx_rm_status` (`status`),
  ADD KEY `idx_rm_sequence` (`research_plan_id`,`milestone_order`);

--
-- Indexes for table `research_panel_assignments`
--
ALTER TABLE `research_panel_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_panel_assignment_phase` (`research_group_id`,`panel_user_id`,`defense_phase`),
  ADD KEY `idx_panel_assignment_group` (`research_group_id`),
  ADD KEY `idx_panel_assignment_user` (`panel_user_id`),
  ADD KEY `idx_panel_assignment_status` (`assignment_status`),
  ADD KEY `idx_panel_assignment_schedule` (`defense_schedule_id`);

--
-- Indexes for table `research_plans`
--
ALTER TABLE `research_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rp_group` (`research_group_id`),
  ADD KEY `idx_rp_group_number` (`group_number`),
  ADD KEY `idx_rp_adviser` (`adviser_id`),
  ADD KEY `idx_rp_status` (`status`);

--
-- Indexes for table `research_progress_activity_logs`
--
ALTER TABLE `research_progress_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpal_plan` (`research_plan_id`),
  ADD KEY `idx_rpal_user` (`user_id`),
  ADD KEY `idx_rpal_action` (`action`),
  ADD KEY `idx_rpal_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_rpal_created` (`created_at`);

--
-- Indexes for table `research_progress_ai_analyses`
--
ALTER TABLE `research_progress_ai_analyses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpai_update` (`progress_update_id`,`id`);

--
-- Indexes for table `research_progress_attachments`
--
ALTER TABLE `research_progress_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpa_update` (`progress_update_id`),
  ADD KEY `idx_rpa_uploaded` (`uploaded_by`);

--
-- Indexes for table `research_progress_feedback`
--
ALTER TABLE `research_progress_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpf_update` (`progress_update_id`),
  ADD KEY `idx_rpf_milestone` (`milestone_id`),
  ADD KEY `idx_rpf_plan` (`research_plan_id`),
  ADD KEY `idx_rpf_created` (`created_at`),
  ADD KEY `idx_rpf_adviser` (`adviser_user_id`),
  ADD KEY `idx_rpf_token` (`submission_token`),
  ADD KEY `idx_rpf_update_adviser` (`progress_update_id`,`adviser_user_id`),
  ADD KEY `idx_rpf_plan_type` (`research_plan_id`,`feedback_type`);

--
-- Indexes for table `research_progress_notifications`
--
ALTER TABLE `research_progress_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpn_recipient_user` (`recipient_user_id`),
  ADD KEY `idx_rpn_recipient_email` (`recipient_email`),
  ADD KEY `idx_rpn_recipient_role` (`recipient_role`),
  ADD KEY `idx_rpn_batch_key` (`batch_key`),
  ADD KEY `idx_rpn_status` (`status`),
  ADD KEY `idx_rpn_created` (`created_at`);

--
-- Indexes for table `research_progress_updates`
--
ALTER TABLE `research_progress_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpu_plan` (`research_plan_id`),
  ADD KEY `idx_rpu_milestone` (`milestone_id`),
  ADD KEY `idx_rpu_researcher` (`submitted_by_user_id`),
  ADD KEY `idx_rpu_submitted` (`submitted_at`),
  ADD KEY `idx_rpu_group` (`research_group_id`),
  ADD KEY `idx_rpu_token` (`submission_token`),
  ADD KEY `idx_rpu_group_milestone` (`research_group_id`,`milestone_id`),
  ADD KEY `idx_rpu_plan_submitted` (`research_plan_id`,`submitted_at`);

--
-- Indexes for table `research_proposals`
--
ALTER TABLE `research_proposals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_code` (`ref_code`),
  ADD UNIQUE KEY `proposal_number` (`proposal_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dept` (`college_department`(50)),
  ADD KEY `idx_submitted` (`date_submitted`);

--
-- Indexes for table `research_revision_cycles`
--
ALTER TABLE `research_revision_cycles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rrc_schedule` (`defense_schedule_id`),
  ADD KEY `idx_rrc_group` (`research_group_id`),
  ADD KEY `idx_rrc_status` (`revision_status`);

--
-- Indexes for table `research_services_clearances`
--
ALTER TABLE `research_services_clearances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rsc_group_stage` (`research_group_id`,`research_stage`),
  ADD KEY `idx_rsc_status` (`status`),
  ADD KEY `idx_rsc_adviser` (`adviser_user_id`);

--
-- Indexes for table `research_venues`
--
ALTER TABLE `research_venues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_research_venue_name` (`venue_name`),
  ADD KEY `idx_research_venues_status` (`status`);

--
-- Indexes for table `title_approvals`
--
ALTER TABLE `title_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ta_student_id` (`student_id`),
  ADD KEY `idx_ta_adviser_email` (`adviser_email`(100)),
  ADD KEY `idx_ta_status` (`status`),
  ADD KEY `idx_ta_sent_at` (`sent_at`),
  ADD KEY `idx_ta_proposal_number` (`proposal_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chapter_evaluations`
--
ALTER TABLE `chapter_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `chapter_evaluation_notifications`
--
ALTER TABLE `chapter_evaluation_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `chapter_submissions`
--
ALTER TABLE `chapter_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `chapter_submission_history`
--
ALTER TABLE `chapter_submission_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=159;

--
-- AUTO_INCREMENT for table `final_defense_evaluations`
--
ALTER TABLE `final_defense_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `final_defense_recommendations`
--
ALTER TABLE `final_defense_recommendations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `final_manuscript_approvals`
--
ALTER TABLE `final_manuscript_approvals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grant_applications`
--
ALTER TABLE `grant_applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `grant_document_repository`
--
ALTER TABLE `grant_document_repository`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grant_document_repository_items`
--
ALTER TABLE `grant_document_repository_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `grant_final_output_submissions`
--
ALTER TABLE `grant_final_output_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `grant_funded_progress_evidence`
--
ALTER TABLE `grant_funded_progress_evidence`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `grant_funded_project_milestones`
--
ALTER TABLE `grant_funded_project_milestones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `grant_funding_disbursements`
--
ALTER TABLE `grant_funding_disbursements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `grant_opportunities`
--
ALTER TABLE `grant_opportunities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `grant_proposal_approval_steps`
--
ALTER TABLE `grant_proposal_approval_steps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `grant_proposal_approval_workflows`
--
ALTER TABLE `grant_proposal_approval_workflows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `grant_proposal_evaluations`
--
ALTER TABLE `grant_proposal_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `grant_proposal_notifications`
--
ALTER TABLE `grant_proposal_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `grant_proposal_versions`
--
ALTER TABLE `grant_proposal_versions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `grant_publications_ip_repository`
--
ALTER TABLE `grant_publications_ip_repository`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `manuscript_evaluations`
--
ALTER TABLE `manuscript_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `manuscript_submissions`
--
ALTER TABLE `manuscript_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `panel_assignment_notifications`
--
ALTER TABLE `panel_assignment_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `panel_member_availability`
--
ALTER TABLE `panel_member_availability`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- AUTO_INCREMENT for table `preoral_defense_evaluations`
--
ALTER TABLE `preoral_defense_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `proposal_documents`
--
ALTER TABLE `proposal_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=204;

--
-- AUTO_INCREMENT for table `proposal_drafts`
--
ALTER TABLE `proposal_drafts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `proposal_members`
--
ALTER TABLE `proposal_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `proposal_status_logs`
--
ALTER TABLE `proposal_status_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `publications`
--
ALTER TABLE `publications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `research_adviser_assignments`
--
ALTER TABLE `research_adviser_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `research_clearance_notifications`
--
ALTER TABLE `research_clearance_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `research_clearance_payments`
--
ALTER TABLE `research_clearance_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `research_coordinator_assignments`
--
ALTER TABLE `research_coordinator_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `research_defense_schedules`
--
ALTER TABLE `research_defense_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `research_groups`
--
ALTER TABLE `research_groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `research_milestones`
--
ALTER TABLE `research_milestones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=257;

--
-- AUTO_INCREMENT for table `research_panel_assignments`
--
ALTER TABLE `research_panel_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `research_plans`
--
ALTER TABLE `research_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `research_progress_activity_logs`
--
ALTER TABLE `research_progress_activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=219;

--
-- AUTO_INCREMENT for table `research_progress_ai_analyses`
--
ALTER TABLE `research_progress_ai_analyses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `research_progress_attachments`
--
ALTER TABLE `research_progress_attachments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `research_progress_feedback`
--
ALTER TABLE `research_progress_feedback`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `research_progress_notifications`
--
ALTER TABLE `research_progress_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT for table `research_progress_updates`
--
ALTER TABLE `research_progress_updates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `research_proposals`
--
ALTER TABLE `research_proposals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `research_revision_cycles`
--
ALTER TABLE `research_revision_cycles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `research_services_clearances`
--
ALTER TABLE `research_services_clearances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `research_venues`
--
ALTER TABLE `research_venues`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15652;

--
-- AUTO_INCREMENT for table `title_approvals`
--
ALTER TABLE `title_approvals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `proposal_documents`
--
ALTER TABLE `proposal_documents`
  ADD CONSTRAINT `fk_pd_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `proposal_members`
--
ALTER TABLE `proposal_members`
  ADD CONSTRAINT `fk_pm_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `proposal_status_logs`
--
ALTER TABLE `proposal_status_logs`
  ADD CONSTRAINT `fk_psl_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `research_adviser_assignments`
--
ALTER TABLE `research_adviser_assignments`
  ADD CONSTRAINT `fk_raa_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `research_proposals` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `research_coordinator_assignments`
--
ALTER TABLE `research_coordinator_assignments`
  ADD CONSTRAINT `fk_rca_title_approval` FOREIGN KEY (`title_approval_id`) REFERENCES `title_approvals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `research_defense_schedules`
--
ALTER TABLE `research_defense_schedules`
  ADD CONSTRAINT `fk_rds_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `research_groups`
--
ALTER TABLE `research_groups`
  ADD CONSTRAINT `fk_rg_title_approval` FOREIGN KEY (`title_approval_id`) REFERENCES `title_approvals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `research_milestones`
--
ALTER TABLE `research_milestones`
  ADD CONSTRAINT `fk_rm_research_plan` FOREIGN KEY (`research_plan_id`) REFERENCES `research_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `research_plans`
--
ALTER TABLE `research_plans`
  ADD CONSTRAINT `fk_rp_research_group` FOREIGN KEY (`research_group_id`) REFERENCES `research_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `research_progress_activity_logs`
--
ALTER TABLE `research_progress_activity_logs`
  ADD CONSTRAINT `fk_rpal_research_plan` FOREIGN KEY (`research_plan_id`) REFERENCES `research_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `research_progress_attachments`
--
ALTER TABLE `research_progress_attachments`
  ADD CONSTRAINT `fk_rpa_progress_update` FOREIGN KEY (`progress_update_id`) REFERENCES `research_progress_updates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `research_progress_feedback`
--
ALTER TABLE `research_progress_feedback`
  ADD CONSTRAINT `fk_rpf_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `research_milestones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpf_progress_update` FOREIGN KEY (`progress_update_id`) REFERENCES `research_progress_updates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpf_research_plan` FOREIGN KEY (`research_plan_id`) REFERENCES `research_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `research_progress_updates`
--
ALTER TABLE `research_progress_updates`
  ADD CONSTRAINT `fk_rpu_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `research_milestones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpu_research_plan` FOREIGN KEY (`research_plan_id`) REFERENCES `research_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
