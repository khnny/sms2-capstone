-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 08:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
-- Table structure for table `crad_chapter_evaluations`
--

CREATE TABLE `crad_chapter_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `submission_id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED NOT NULL,
  `evaluator_user_id` int(10) UNSIGNED NOT NULL,
  `evaluator_name` varchar(150) NOT NULL DEFAULT '',
  `content_score` decimal(5,2) NOT NULL,
  `methodology_score` decimal(5,2) NOT NULL,
  `references_score` decimal(5,2) NOT NULL,
  `format_score` decimal(5,2) NOT NULL,
  `content_remarks` text DEFAULT NULL,
  `methodology_remarks` text DEFAULT NULL,
  `references_remarks` text DEFAULT NULL,
  `format_remarks` text DEFAULT NULL,
  `overall_feedback` text DEFAULT NULL,
  `result` enum('APPROVED','APPROVED WITH REVISION') NOT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `evaluated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crad_chapter_evaluations`
--

INSERT INTO `crad_chapter_evaluations` (`id`, `submission_id`, `research_group_id`, `evaluator_user_id`, `evaluator_name`, `content_score`, `methodology_score`, `references_score`, `format_score`, `content_remarks`, `methodology_remarks`, `references_remarks`, `format_remarks`, `overall_feedback`, `result`, `overall_score`, `evaluated_at`, `created_at`) VALUES
(25, 29, 61, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, '', '', '', '', '', 'APPROVED', 100.00, '2026-08-23 04:07:50', '2026-08-23 04:07:50'),
(26, 30, 61, 475, 'Grammarian', 100.00, 100.00, 100.00, 99.97, '', '', '', '', '', 'APPROVED', 99.99, '2026-08-23 04:07:59', '2026-08-23 04:07:59'),
(27, 31, 61, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, '', '', '', '', '', 'APPROVED', 100.00, '2026-08-23 04:08:08', '2026-08-23 04:08:08'),
(28, 32, 62, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, '', '', '', '', '', 'APPROVED', 100.00, '2026-08-28 15:33:31', '2026-08-28 15:33:31'),
(29, 33, 62, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, '', '', '', '', '', 'APPROVED', 100.00, '2026-08-28 15:33:46', '2026-08-28 15:33:46'),
(30, 34, 62, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, '', '', '', '', '', 'APPROVED', 100.00, '2026-08-28 15:33:53', '2026-08-28 15:33:53'),
(31, 35, 63, 475, 'Grammarian', 99.00, 99.00, 99.00, 99.00, '', '', '', '', '', 'APPROVED', 99.00, '2026-08-28 16:30:28', '2026-08-28 16:30:28'),
(32, 36, 63, 475, 'Grammarian', 99.00, 99.00, 99.00, 98.96, '', '', '', '', '', 'APPROVED', 98.99, '2026-08-28 16:30:40', '2026-08-28 16:30:40'),
(33, 37, 63, 475, 'Grammarian', 99.00, 99.00, 99.00, 99.00, '', '', '', '', '', 'APPROVED', 99.00, '2026-08-28 16:30:48', '2026-08-28 16:30:48'),
(34, 38, 64, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, '', '', '', '', '', 'APPROVED', 100.00, '2026-08-31 05:32:02', '2026-08-31 05:32:02'),
(35, 39, 64, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, '', '', '', '', '', 'APPROVED', 100.00, '2026-08-31 05:32:16', '2026-08-31 05:32:16'),
(36, 40, 64, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, '', '', '', '', '', 'APPROVED', 100.00, '2026-08-31 05:32:32', '2026-08-31 05:32:32'),
(37, 41, 65, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, '', '', '', '', '', 'APPROVED', 100.00, '2026-08-31 09:42:10', '2026-08-31 09:42:10'),
(38, 42, 65, 475, 'Grammarian', 100.00, 100.00, 99.97, 100.00, '', '', '', '', '', 'APPROVED', 99.99, '2026-08-31 09:42:25', '2026-08-31 09:42:25'),
(39, 43, 65, 475, 'Grammarian', 100.00, 100.00, 100.00, 100.00, '', '', '', '', '', 'APPROVED', 100.00, '2026-08-31 09:42:36', '2026-08-31 09:42:36');

-- --------------------------------------------------------

--
-- Table structure for table `crad_chapter_evaluation_notifications`
--

CREATE TABLE `crad_chapter_evaluation_notifications` (
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
-- Dumping data for table `crad_chapter_evaluation_notifications`
--

INSERT INTO `crad_chapter_evaluation_notifications` (`id`, `event_key`, `recipient_user_id`, `recipient_role`, `recipient_email`, `submission_id`, `type`, `title`, `body`, `url`, `is_read`, `created_at`) VALUES
(1, 'evaluator:new:1:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 1, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=1', 0, '2026-08-14 11:24:27'),
(2, 'evaluator:new:2:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 2, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=2', 0, '2026-08-14 11:24:31'),
(3, 'evaluator:new:3:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 3, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=3', 1, '2026-08-14 11:24:36'),
(4, 'student:under_review:1', 9, 'student', 'kenlangmalakas0308@gmail.com', 1, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 11:24:57'),
(5, 'student:under_review:3', 9, 'student', 'kenlangmalakas0308@gmail.com', 3, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 11:36:01'),
(6, 'student:needs_revision:3', 9, 'student', 'kenlangmalakas0308@gmail.com', 3, 'needs_revision', 'Chapter 3 needs revision', 'Chapter 3 Version 1 is now Needs Revision.', '/sms2_system/modules/student-portal/pages/submission-status.php', 1, '2026-08-14 11:36:16'),
(7, 'evaluator:new:4:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 4, 'new_submission', 'Revised Chapter Submitted', 'Group 01 submitted Chapter 3 Version 2 for re-evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=4', 1, '2026-08-14 11:36:41'),
(8, 'evaluator:new:5:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 5, 'new_submission', 'New Chapter Submission', 'Group 33 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=5', 1, '2026-08-14 12:07:57'),
(9, 'evaluator:new:6:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 6, 'new_submission', 'New Chapter Submission', 'Group 33 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=6', 0, '2026-08-14 12:08:00'),
(10, 'evaluator:new:7:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 7, 'new_submission', 'New Chapter Submission', 'Group 33 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=7', 1, '2026-08-14 12:08:03'),
(11, 'student:under_review:5', 9, 'student', 'kenlangmalakas0308@gmail.com', 5, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 12:16:20'),
(12, 'student:accepted:5', 9, 'student', 'kenlangmalakas0308@gmail.com', 5, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 12:16:27'),
(13, 'student:under_review:6', 9, 'student', 'kenlangmalakas0308@gmail.com', 6, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 12:16:32'),
(14, 'student:accepted:6', 9, 'student', 'kenlangmalakas0308@gmail.com', 6, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 12:16:37'),
(15, 'evaluator:new:8:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 8, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=8', 0, '2026-08-14 12:46:13'),
(16, 'evaluator:new:9:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 9, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=9', 0, '2026-08-14 12:46:17'),
(17, 'evaluator:new:10:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 10, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=10', 0, '2026-08-14 12:46:20'),
(18, 'student:under_review:8', 9, 'student', 'kenlangmalakas0308@gmail.com', 8, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 12:47:57'),
(19, 'student:accepted:8', 9, 'student', 'kenlangmalakas0308@gmail.com', 8, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 12:48:05'),
(20, 'student:under_review:9', 9, 'student', 'kenlangmalakas0308@gmail.com', 9, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 12:48:09'),
(21, 'student:accepted:9', 9, 'student', 'kenlangmalakas0308@gmail.com', 9, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 12:48:15'),
(22, 'student:under_review:10', 9, 'student', 'kenlangmalakas0308@gmail.com', 10, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 12:48:20'),
(23, 'student:accepted:10', 9, 'student', 'kenlangmalakas0308@gmail.com', 10, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 12:48:26'),
(24, 'evaluator:new:11:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 11, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=11', 0, '2026-08-14 13:23:21'),
(25, 'evaluator:new:12:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 12, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=12', 0, '2026-08-14 13:23:25'),
(26, 'evaluator:new:13:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 13, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=13', 0, '2026-08-14 13:23:28'),
(27, 'student:under_review:11', 9, 'student', 'kenlangmalakas0308@gmail.com', 11, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 13:45:53'),
(28, 'student:accepted:11', 9, 'student', 'kenlangmalakas0308@gmail.com', 11, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 13:46:02'),
(29, 'student:under_review:12', 9, 'student', 'kenlangmalakas0308@gmail.com', 12, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 13:46:08'),
(30, 'student:accepted:12', 9, 'student', 'kenlangmalakas0308@gmail.com', 12, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 13:46:13'),
(31, 'student:under_review:13', 9, 'student', 'kenlangmalakas0308@gmail.com', 13, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 13:46:16'),
(32, 'student:accepted:13', 9, 'student', 'kenlangmalakas0308@gmail.com', 13, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 13:46:22'),
(33, 'evaluator:new:14:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 14, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=14', 0, '2026-08-14 16:40:44'),
(34, 'student:under_review:14', 9, 'student', 'kenlangmalakas0308@gmail.com', 14, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 16:42:06'),
(35, 'student:accepted:14', 9, 'student', 'kenlangmalakas0308@gmail.com', 14, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 1, '2026-08-14 16:42:19'),
(36, 'evaluator:new:15:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 15, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=15', 0, '2026-08-14 17:36:01'),
(37, 'student:under_review:15', 9, 'student', 'kenlangmalakas0308@gmail.com', 15, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 17:36:17'),
(38, 'student:accepted:15', 9, 'student', 'kenlangmalakas0308@gmail.com', 15, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 1, '2026-08-14 17:36:32'),
(39, 'evaluator:new:16:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 16, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=16', 0, '2026-08-14 21:50:29'),
(40, 'student:under_review:16', 9, 'student', 'kenlangmalakas0308@gmail.com', 16, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 21:51:10'),
(41, 'student:accepted:16', 9, 'student', 'kenlangmalakas0308@gmail.com', 16, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-14 21:51:29'),
(42, 'evaluator:new:17:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 17, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=17', 1, '2026-08-15 16:43:36'),
(43, 'evaluator:new:18:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 18, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=18', 1, '2026-08-15 16:43:40'),
(44, 'evaluator:new:19:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 19, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=19', 1, '2026-08-15 16:43:43'),
(45, 'student:under_review:17', 9, 'student', 'kenlangmalakas0308@gmail.com', 17, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 16:48:40'),
(46, 'student:accepted:17', 9, 'student', 'kenlangmalakas0308@gmail.com', 17, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 16:48:46'),
(47, 'student:under_review:18', 9, 'student', 'kenlangmalakas0308@gmail.com', 18, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 16:48:51'),
(48, 'student:accepted:18', 9, 'student', 'kenlangmalakas0308@gmail.com', 18, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 16:48:55'),
(49, 'student:under_review:19', 9, 'student', 'kenlangmalakas0308@gmail.com', 19, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 16:49:00'),
(50, 'student:accepted:19', 9, 'student', 'kenlangmalakas0308@gmail.com', 19, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 16:49:04'),
(51, 'evaluator:new:20:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 20, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=20', 0, '2026-08-15 22:49:29'),
(52, 'evaluator:new:21:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 21, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=21', 0, '2026-08-15 22:49:33'),
(53, 'evaluator:new:22:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 22, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=22', 0, '2026-08-15 22:49:38'),
(54, 'student:under_review:20', 9, 'student', 'kenlangmalakas0308@gmail.com', 20, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 22:50:04'),
(55, 'student:accepted:20', 9, 'student', 'kenlangmalakas0308@gmail.com', 20, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 22:50:09'),
(56, 'student:under_review:21', 9, 'student', 'kenlangmalakas0308@gmail.com', 21, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 22:50:13'),
(57, 'student:accepted:21', 9, 'student', 'kenlangmalakas0308@gmail.com', 21, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 22:50:18'),
(58, 'student:under_review:22', 9, 'student', 'kenlangmalakas0308@gmail.com', 22, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 22:50:21'),
(59, 'student:accepted:22', 9, 'student', 'kenlangmalakas0308@gmail.com', 22, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-15 22:50:27'),
(60, 'evaluator:new:23:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 23, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=23', 0, '2026-08-16 15:00:39'),
(61, 'evaluator:new:24:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 24, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=24', 0, '2026-08-16 15:00:43'),
(62, 'evaluator:new:25:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 25, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=25', 0, '2026-08-16 15:00:47'),
(63, 'student:under_review:23', 9, 'student', 'kenlangmalakas0308@gmail.com', 23, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-16 15:01:13'),
(64, 'student:accepted:23', 9, 'student', 'kenlangmalakas0308@gmail.com', 23, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-16 15:01:21'),
(65, 'student:under_review:24', 9, 'student', 'kenlangmalakas0308@gmail.com', 24, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-16 15:01:26'),
(66, 'student:accepted:24', 9, 'student', 'kenlangmalakas0308@gmail.com', 24, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 1, '2026-08-16 15:01:31'),
(67, 'student:under_review:25', 9, 'student', 'kenlangmalakas0308@gmail.com', 25, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-16 15:01:35'),
(68, 'student:accepted:25', 9, 'student', 'kenlangmalakas0308@gmail.com', 25, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 1, '2026-08-16 15:01:40'),
(69, 'evaluator:new:26:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 26, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=26', 0, '2026-08-16 21:42:19'),
(70, 'evaluator:new:27:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 27, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=27', 0, '2026-08-16 21:42:22'),
(71, 'evaluator:new:28:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 28, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=28', 0, '2026-08-16 21:42:25'),
(72, 'student:under_review:26', 9, 'student', 'kenlangmalakas0308@gmail.com', 26, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-16 21:42:53'),
(73, 'student:accepted:26', 9, 'student', 'kenlangmalakas0308@gmail.com', 26, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-16 21:43:06'),
(74, 'student:under_review:27', 9, 'student', 'kenlangmalakas0308@gmail.com', 27, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-16 21:43:11'),
(75, 'student:accepted:27', 9, 'student', 'kenlangmalakas0308@gmail.com', 27, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-16 21:43:15'),
(76, 'student:under_review:28', 9, 'student', 'kenlangmalakas0308@gmail.com', 28, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-16 21:43:22'),
(77, 'student:accepted:28', 9, 'student', 'kenlangmalakas0308@gmail.com', 28, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-16 21:43:25'),
(78, 'evaluator:new:29:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 29, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=29', 0, '2026-08-23 03:50:56'),
(79, 'evaluator:new:30:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 30, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=30', 0, '2026-08-23 04:07:26'),
(80, 'evaluator:new:31:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 31, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=31', 0, '2026-08-23 04:07:31'),
(81, 'student:under_review:29', 9, 'student', 'kenlangmalakas0308@gmail.com', 29, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-23 04:07:45'),
(82, 'student:accepted:29', 9, 'student', 'kenlangmalakas0308@gmail.com', 29, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-23 04:07:50'),
(83, 'student:under_review:30', 9, 'student', 'kenlangmalakas0308@gmail.com', 30, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-23 04:07:54'),
(84, 'student:accepted:30', 9, 'student', 'kenlangmalakas0308@gmail.com', 30, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-23 04:07:59'),
(85, 'student:under_review:31', 9, 'student', 'kenlangmalakas0308@gmail.com', 31, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-23 04:08:03'),
(86, 'student:accepted:31', 9, 'student', 'kenlangmalakas0308@gmail.com', 31, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-23 04:08:08'),
(87, 'evaluator:new:32:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 32, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=32', 0, '2026-08-28 15:33:05'),
(88, 'evaluator:new:33:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 33, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=33', 0, '2026-08-28 15:33:08'),
(89, 'evaluator:new:34:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 34, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=34', 0, '2026-08-28 15:33:11'),
(90, 'student:under_review:32', 9, 'student', 'kenlangmalakas0308@gmail.com', 32, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 15:33:26'),
(91, 'student:accepted:32', 9, 'student', 'kenlangmalakas0308@gmail.com', 32, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 15:33:31'),
(92, 'student:under_review:33', 9, 'student', 'kenlangmalakas0308@gmail.com', 33, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 15:33:39'),
(93, 'student:accepted:33', 9, 'student', 'kenlangmalakas0308@gmail.com', 33, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 15:33:46'),
(94, 'student:under_review:34', 9, 'student', 'kenlangmalakas0308@gmail.com', 34, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 15:33:49'),
(95, 'student:accepted:34', 9, 'student', 'kenlangmalakas0308@gmail.com', 34, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 1, '2026-08-28 15:33:53'),
(96, 'student:final_manuscript_approved:2', 9, 'student', 'kenlangmalakas0308@gmail.com', 2, 'final_manuscript_approved', 'Final Manuscript Approved', 'Your latest final manuscript for your research group has been approved for the next CRAD stage.', '/sms2_system/modules/student-portal/pages/final-manuscript.php', 0, '2026-08-28 15:55:07'),
(97, 'evaluator:new:35:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 35, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=35', 0, '2026-08-28 16:30:12'),
(98, 'evaluator:new:36:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 36, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=36', 0, '2026-08-28 16:30:15'),
(99, 'evaluator:new:37:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 37, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=37', 0, '2026-08-28 16:30:18'),
(100, 'student:under_review:35', 9, 'student', 'kenlangmalakas0308@gmail.com', 35, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 16:30:22'),
(101, 'student:accepted:35', 9, 'student', 'kenlangmalakas0308@gmail.com', 35, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 16:30:28'),
(102, 'student:under_review:36', 9, 'student', 'kenlangmalakas0308@gmail.com', 36, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 16:30:35'),
(103, 'student:accepted:36', 9, 'student', 'kenlangmalakas0308@gmail.com', 36, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 16:30:40'),
(104, 'student:under_review:37', 9, 'student', 'kenlangmalakas0308@gmail.com', 37, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 16:30:44'),
(105, 'student:accepted:37', 9, 'student', 'kenlangmalakas0308@gmail.com', 37, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-28 16:30:48'),
(106, 'student:final_manuscript_approved:3', 9, 'student', 'kenlangmalakas0308@gmail.com', 3, 'final_manuscript_approved', 'Final Manuscript Approved', 'Your latest final manuscript for your research group has been approved for the next CRAD stage.', '/sms2_system/modules/student-portal/pages/final-manuscript.php', 0, '2026-08-28 16:39:02'),
(107, 'evaluator:new:38:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 38, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=38', 0, '2026-08-31 05:30:20'),
(108, 'evaluator:new:39:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 39, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=39', 0, '2026-08-31 05:30:27'),
(109, 'evaluator:new:40:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 40, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=40', 0, '2026-08-31 05:30:33'),
(110, 'student:under_review:38', 9, 'student', 'kenlangmalakas0308@gmail.com', 38, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 05:31:36'),
(111, 'student:accepted:38', 9, 'student', 'kenlangmalakas0308@gmail.com', 38, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 05:32:02'),
(112, 'student:under_review:39', 9, 'student', 'kenlangmalakas0308@gmail.com', 39, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 05:32:07'),
(113, 'student:accepted:39', 9, 'student', 'kenlangmalakas0308@gmail.com', 39, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 05:32:16'),
(114, 'student:under_review:40', 9, 'student', 'kenlangmalakas0308@gmail.com', 40, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 05:32:23'),
(115, 'student:accepted:40', 9, 'student', 'kenlangmalakas0308@gmail.com', 40, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 05:32:32'),
(116, 'student:final_manuscript_approved:4', 9, 'student', 'kenlangmalakas0308@gmail.com', 4, 'final_manuscript_approved', 'Final Manuscript Approved', 'Your latest final manuscript for your research group has been approved for the next CRAD stage.', '/sms2_system/modules/student-portal/pages/final-manuscript.php', 0, '2026-08-31 06:32:22'),
(117, 'evaluator:new:41:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 41, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 1 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=41', 0, '2026-08-31 09:41:02'),
(118, 'evaluator:new:42:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 42, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 2 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=42', 0, '2026-08-31 09:41:14'),
(119, 'evaluator:new:43:u475', 475, 'grammarian', 'grammarian@bestlink.edu.ph', 43, 'new_submission', 'New Chapter Submission', 'Group 01 submitted Chapter 3 Version 1 for evaluation.', '/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=43', 0, '2026-08-31 09:41:20'),
(120, 'student:under_review:41', 9, 'student', 's230000001@bestlink.edu.ph', 41, 'under_review', 'Chapter 1 is under review', 'Chapter 1 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 09:41:37'),
(121, 'student:accepted:41', 9, 'student', 's230000001@bestlink.edu.ph', 41, 'accepted', 'Chapter 1 accepted', 'Chapter 1 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 09:42:10'),
(122, 'student:under_review:42', 9, 'student', 's230000001@bestlink.edu.ph', 42, 'under_review', 'Chapter 2 is under review', 'Chapter 2 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 09:42:16'),
(123, 'student:accepted:42', 9, 'student', 's230000001@bestlink.edu.ph', 42, 'accepted', 'Chapter 2 accepted', 'Chapter 2 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 09:42:25'),
(124, 'student:under_review:43', 9, 'student', 's230000001@bestlink.edu.ph', 43, 'under_review', 'Chapter 3 is under review', 'Chapter 3 Version 1 is now under review.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 09:42:30'),
(125, 'student:accepted:43', 9, 'student', 's230000001@bestlink.edu.ph', 43, 'accepted', 'Chapter 3 accepted', 'Chapter 3 Version 1 is now Accepted.', '/sms2_system/modules/student-portal/pages/submission-status.php', 0, '2026-08-31 09:42:37');

-- --------------------------------------------------------

--
-- Table structure for table `crad_chapter_submissions`
--

CREATE TABLE `crad_chapter_submissions` (
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
-- Dumping data for table `crad_chapter_submissions`
--

INSERT INTO `crad_chapter_submissions` (`id`, `research_group_id`, `research_plan_id`, `chapter_number`, `version_number`, `status`, `submitted_by_user`, `submitted_by_name`, `submitted_by_email`, `submission_notes`, `original_name`, `stored_subdir`, `stored_name`, `file_size`, `file_mime`, `submission_token`, `submitted_at`, `review_started_at`, `reviewed_at`, `updated_at`) VALUES
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
(43, 65, 26, 3, 1, 'Accepted', 9, 'Student User', 's230000001@bestlink.edu.ph', '', 'OLIVEROS CV.pdf', 'student_chapters/u9', 'c88aaf9cd7d1786adef383829d96f19d.pdf', 294354, 'application/pdf', '8caa0800c83f9cb0d4d5c6ff1739d304e1b31b7863feff6dcc779c651fded0ae', '2026-08-31 09:41:20', '2026-08-31 09:42:30', '2026-08-31 09:42:37', '2026-08-31 09:42:37');

-- --------------------------------------------------------

--
-- Table structure for table `crad_chapter_submission_history`
--

CREATE TABLE `crad_chapter_submission_history` (
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
-- Dumping data for table `crad_chapter_submission_history`
--

INSERT INTO `crad_chapter_submission_history` (`id`, `submission_id`, `research_group_id`, `chapter_number`, `version_number`, `status`, `event_type`, `actor_user_id`, `actor_name`, `actor_role`, `detail`, `created_at`) VALUES
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
(122, 43, 65, 3, 1, 'Accepted', 'evaluated', 475, 'Grammarian', 'grammarian', 'APPROVED', '2026-08-31 09:42:37');

-- --------------------------------------------------------

--
-- Table structure for table `crad_final_defense_evaluations`
--

CREATE TABLE `crad_final_defense_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `defense_schedule_id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `panel_user_id` int(10) UNSIGNED NOT NULL,
  `panel_name` varchar(150) NOT NULL DEFAULT '',
  `content_score` decimal(5,2) NOT NULL,
  `methodology_score` decimal(5,2) NOT NULL,
  `references_score` decimal(5,2) NOT NULL,
  `format_score` decimal(5,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `result` enum('APPROVED','APPROVED WITH REVISION','FAILED') NOT NULL,
  `overall_score` decimal(5,2) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crad_final_defense_evaluations`
--

INSERT INTO `crad_final_defense_evaluations` (`id`, `defense_schedule_id`, `research_group_id`, `panel_user_id`, `panel_name`, `content_score`, `methodology_score`, `references_score`, `format_score`, `remarks`, `result`, `overall_score`, `status`, `submitted_at`, `created_at`) VALUES
(1, 32, 61, 491, 'Dr. Jobert Valentino', 90.00, 90.00, 90.00, 90.00, 'Final Defense evaluation completed.', 'APPROVED', 90.00, 'Submitted', '2026-08-28 08:31:36', '2026-08-28 08:31:36'),
(2, 32, 61, 492, 'Dr. Jonathan Estrada', 90.00, 88.00, 92.00, 90.00, 'Live demo evaluation test', 'APPROVED', 90.00, 'Submitted', '2026-08-28 13:31:08', '2026-08-28 13:31:08'),
(3, 32, 61, 493, 'Dr. Michelle Guevarra', 99.00, 99.00, 99.00, 99.00, '99', 'APPROVED', 99.00, 'Submitted', '2026-08-28 13:34:59', '2026-08-28 13:34:59'),
(4, 38, 62, 491, 'Dr. Jobert Valentino', 100.00, 100.00, 100.00, 100.00, '', 'APPROVED', 100.00, 'Submitted', '2026-08-28 15:53:12', '2026-08-28 15:53:12'),
(5, 38, 62, 492, 'Dr. Jonathan Estrada', 100.00, 100.00, 100.00, 100.00, '', 'APPROVED', 100.00, 'Submitted', '2026-08-28 15:53:31', '2026-08-28 15:53:31'),
(6, 38, 62, 493, 'Dr. Michelle Guevarra', 100.00, 100.00, 100.00, 100.00, '', 'APPROVED', 100.00, 'Submitted', '2026-08-28 15:53:47', '2026-08-28 15:53:47'),
(7, 44, 63, 491, 'Dr. Jobert Valentino', 99.00, 99.00, 99.00, 99.00, '', 'APPROVED', 99.00, 'Submitted', '2026-08-28 16:37:25', '2026-08-28 16:37:25'),
(8, 44, 63, 492, 'Dr. Jonathan Estrada', 99.00, 99.00, 99.00, 99.00, '', 'APPROVED', 99.00, 'Submitted', '2026-08-28 16:38:03', '2026-08-28 16:38:03'),
(9, 44, 63, 493, 'Dr. Michelle Guevarra', 99.00, 99.00, 99.00, 99.00, '', 'APPROVED', 99.00, 'Submitted', '2026-08-28 16:38:21', '2026-08-28 16:38:21'),
(10, 50, 64, 491, 'Dr. Jobert Valentino', 99.99, 100.00, 100.00, 100.00, '', 'APPROVED', 100.00, 'Submitted', '2026-08-31 06:28:01', '2026-08-31 06:28:01'),
(11, 50, 64, 492, 'Dr. Jonathan Estrada', 100.00, 100.00, 100.00, 100.00, '', 'APPROVED', 100.00, 'Submitted', '2026-08-31 06:28:40', '2026-08-31 06:28:40'),
(12, 50, 64, 493, 'Dr. Michelle Guevarra', 99.99, 100.00, 100.00, 100.00, '100', 'APPROVED', 100.00, 'Submitted', '2026-08-31 06:31:46', '2026-08-31 06:31:46');

-- --------------------------------------------------------

--
-- Table structure for table `crad_final_defense_recommendations`
--

CREATE TABLE `crad_final_defense_recommendations` (
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
-- Dumping data for table `crad_final_defense_recommendations`
--

INSERT INTO `crad_final_defense_recommendations` (`id`, `research_group_id`, `group_number`, `adviser_user_id`, `adviser_name`, `status`, `remarks`, `recommended_at`, `created_at`, `updated_at`) VALUES
(1, 61, 'RG-2026-001', 54, 'Dr. Roberto M. Santos', 'Recommended', '', '2026-08-28 15:41:00', '2026-08-28 08:00:15', '2026-08-28 15:41:00'),
(2, 62, 'RG-2026-001', 54, 'Dr. Roberto M. Santos', 'Recommended', '', '2026-08-28 15:45:21', '2026-08-28 15:44:31', '2026-08-28 15:45:21'),
(4, 63, 'RG-2026-001', 54, 'Dr. Roberto M. Santos', 'Recommended', '', '2026-08-28 16:34:27', '2026-08-28 16:34:27', '2026-08-28 16:34:27'),
(5, 64, 'RG-2026-001', 54, 'Dr. Roberto M. Santos', 'Recommended', '', '2026-08-31 06:15:48', '2026-08-31 06:15:48', '2026-08-31 06:15:48');

-- --------------------------------------------------------

--
-- Table structure for table `crad_final_manuscript_approvals`
--

CREATE TABLE `crad_final_manuscript_approvals` (
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
-- Dumping data for table `crad_final_manuscript_approvals`
--

INSERT INTO `crad_final_manuscript_approvals` (`id`, `research_group_id`, `defense_schedule_id`, `approved_by_user`, `approved_by_name`, `status`, `remarks`, `approved_at`, `created_at`, `updated_at`) VALUES
(1, 61, 32, 3, '', 'Approved', 'Final Defense evaluations completed and approved.', '2026-08-28 13:41:26', '2026-08-28 13:41:26', '2026-08-28 13:41:26'),
(2, 62, 38, 3, '', 'Approved', 'done', '2026-08-28 15:55:07', '2026-08-28 15:55:07', '2026-08-28 15:55:07'),
(3, 63, 44, 3, '', 'Approved', 'done', '2026-08-28 16:39:02', '2026-08-28 16:39:02', '2026-08-28 16:39:02'),
(4, 64, 50, 3, '', 'Approved', '100', '2026-08-31 06:32:22', '2026-08-31 06:32:22', '2026-08-31 06:32:22');

-- --------------------------------------------------------

--
-- Table structure for table `crad_grant_applications`
--

CREATE TABLE `crad_grant_applications` (
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
  `status` enum('Submitted','Under Review','Approved','Denied','Withdrawn','Rejected','Revision Required','Resubmitted') NOT NULL DEFAULT 'Submitted',
  `submission_token` varchar(64) DEFAULT NULL COMMENT 'One-time token for duplicate-submission prevention',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crad_grant_applications`
--

INSERT INTO `crad_grant_applications` (`id`, `proposal_reference`, `current_version`, `grant_opportunity_id`, `research_group_id`, `group_number`, `research_title`, `applicant_name`, `college_dept`, `requested_budget`, `abstract`, `objectives`, `proposal_pdf`, `proposal_pdf_original`, `supporting_docs`, `supporting_docs_original`, `ethics_doc`, `ethics_doc_original`, `applicant_user_id`, `application_notes`, `status`, `submission_token`, `submitted_at`, `updated_at`) VALUES
(2, 'GR-2026-001', 2, 1, NULL, NULL, 'AI ANALYSIS', 'Student User', 'College of Studies', 2900.00, 'dsada', 'asdas', 'f3f3ec4b05c99f1fbbe58278c60d4afe.pdf', 'OLIVEROS CV.pdf', 'e411ba015c79a77a27013c4206313257.pdf', 'OLIVEROS CV.pdf', '95ae712a926e7a6e5ceef81ed3ba0f14.pdf', 'OLIVEROS CV.pdf', 9, NULL, 'Under Review', 'c595e3d94db3cd0f42199da922185f07', '2026-08-31 08:46:40', '2026-08-31 08:47:21');

-- --------------------------------------------------------

--
-- Table structure for table `crad_grant_opportunities`
--

CREATE TABLE `crad_grant_opportunities` (
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

--
-- Dumping data for table `crad_grant_opportunities`
--

INSERT INTO `crad_grant_opportunities` (`id`, `funding_title`, `max_funding_cap`, `application_deadline`, `eligibility`, `college_program`, `status`, `created_by_user_id`, `created_by_name`, `created_at`, `updated_at`) VALUES
(1, 'BESTLINK Faculty Seed Grant Call 2026', 350000.00, '2026-09-01', 'Faculty & Student', NULL, 'Open for Application', 3, 'CRAD Officer', '2026-08-31 06:44:48', '2026-08-31 06:44:48');

-- --------------------------------------------------------

--
-- Table structure for table `crad_grant_proposal_approval_steps`
--

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

--
-- Dumping data for table `crad_grant_proposal_approval_steps`
--

INSERT INTO `crad_grant_proposal_approval_steps` (`id`, `workflow_id`, `grant_application_id`, `step_key`, `step_order`, `step_label`, `approver_role_key`, `status`, `approver_user_id`, `approver_name`, `remarks`, `signature_data`, `acted_at`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'adviser', 1, 'Academic Adviser', 'adviser', 'Pending', NULL, NULL, NULL, NULL, NULL, '2026-08-31 08:47:21', '2026-08-31 08:47:21'),
(2, 1, 2, 'department_chair', 2, 'Dept. Chair', 'research_coordinator', 'Queued', NULL, NULL, NULL, NULL, NULL, '2026-08-31 08:47:21', '2026-08-31 08:47:21'),
(3, 1, 2, 'dean', 3, 'College Dean', 'hr', 'Queued', NULL, NULL, NULL, NULL, NULL, '2026-08-31 08:47:21', '2026-08-31 08:47:21'),
(4, 1, 2, 'research_office', 4, 'Research Office', 'crad_officer', 'Queued', NULL, NULL, NULL, NULL, NULL, '2026-08-31 08:47:21', '2026-08-31 08:47:21'),
(5, 1, 2, 'vpaa', 5, 'VPAA Sign-off', 'qa', 'Queued', NULL, NULL, NULL, NULL, NULL, '2026-08-31 08:47:21', '2026-08-31 08:47:21');

-- --------------------------------------------------------

--
-- Table structure for table `crad_grant_proposal_approval_workflows`
--

CREATE TABLE `crad_grant_proposal_approval_workflows` (
  `id` int(10) UNSIGNED NOT NULL,
  `grant_application_id` int(10) UNSIGNED NOT NULL,
  `current_step_key` varchar(40) NOT NULL DEFAULT 'adviser',
  `workflow_status` enum('In Progress','Completed','Returned') NOT NULL DEFAULT 'In Progress',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crad_grant_proposal_approval_workflows`
--

INSERT INTO `crad_grant_proposal_approval_workflows` (`id`, `grant_application_id`, `current_step_key`, `workflow_status`, `started_at`, `completed_at`, `updated_at`) VALUES
(1, 2, 'adviser', 'In Progress', '2026-08-31 08:47:21', NULL, '2026-08-31 08:47:21');

-- --------------------------------------------------------

--
-- Table structure for table `crad_grant_proposal_evaluations`
--

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

--
-- Dumping data for table `crad_grant_proposal_evaluations`
--

INSERT INTO `crad_grant_proposal_evaluations` (`id`, `grant_application_id`, `proposal_version`, `evaluator_user_id`, `evaluator_name`, `evaluation_type`, `score_rationale`, `score_methodology`, `score_budget`, `score_team_capability`, `score_compliance`, `total_score`, `comments`, `recommendations`, `required_corrections`, `recommendation`, `revision_reason`, `submitted_at`, `updated_at`) VALUES
(1, 1, 1, 766, 'Review Committee Member', 'committee', 25.00, 30.00, 19.00, 10.00, 5.00, 89.00, 'asdsa', 'asdas', 'asdas', NULL, NULL, '2026-08-31 07:27:00', '2026-08-31 07:27:00'),
(2, 2, 1, 766, 'Review Committee Member', 'committee', 13.00, 13.00, 13.00, 13.00, 1.00, 53.00, 'sadas', 'asdsa', 'asdas', 'require_revisions', 'sadas', '2026-08-31 08:09:44', '2026-08-31 08:09:44'),
(3, 2, 2, 766, 'Review Committee Member', 'committee', 25.00, 25.00, 20.00, 15.00, 10.00, 95.00, 'sadas', 'asdsa', 'adsaa', 'recommend', NULL, '2026-08-31 08:47:21', '2026-08-31 08:47:21');

-- --------------------------------------------------------

--
-- Table structure for table `crad_grant_proposal_notifications`
--

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

--
-- Dumping data for table `crad_grant_proposal_notifications`
--

INSERT INTO `crad_grant_proposal_notifications` (`id`, `event_key`, `recipient_user_id`, `recipient_role`, `recipient_email`, `grant_application_id`, `type`, `title`, `body`, `url`, `is_read`, `created_at`) VALUES
(1, 'grant-proposal:grant_revision_required:2:v1:u9', 9, '', '', 2, 'grant_revision_required', 'Revise Grant Proposal', 'GR-2026-001 requires revisions. sadas Tap to revise and resubmit.', '/sms2_system/modules/crad/pages/revise-proposal.php?id=2', 1, '2026-08-31 08:46:38');

-- --------------------------------------------------------

--
-- Table structure for table `crad_grant_proposal_versions`
--

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

--
-- Dumping data for table `crad_grant_proposal_versions`
--

INSERT INTO `crad_grant_proposal_versions` (`id`, `grant_application_id`, `version_number`, `version_label`, `proposal_pdf`, `proposal_pdf_original`, `supporting_docs`, `supporting_docs_original`, `ethics_doc`, `ethics_doc_original`, `abstract`, `objectives`, `researcher_notes`, `submitted_by_user_id`, `submitted_at`) VALUES
(1, 1, 1, 'Original', '53ac796ed0ad7af6ca74703f6e4a5b41.pdf', 'OLIVEROS CV.pdf', '72706d8b5bfd82f1135557fa92faf84a.pdf', 'OLIVEROS CV.pdf', '3b1bf340d2db787753a7f77efb0fcdaf.pdf', 'OLIVEROS CV.pdf', 'adsadas', 'adsadas', NULL, 9, '2026-08-31 07:42:38'),
(2, 2, 1, 'Original', 'b594409c59e3ba6839050c9dd63f50a6.pdf', 'OLIVEROS CV.pdf', '6a9719b97b1c60266f543bba2f313c38.pdf', 'OLIVEROS CV.pdf', '757f2e0bf3a62bd565b415570d7df5e8.pdf', 'OLIVEROS CV.pdf', 'dsada', 'asdas', NULL, 9, '2026-08-31 08:46:40'),
(4, 2, 2, 'Revised', 'f3f3ec4b05c99f1fbbe58278c60d4afe.pdf', 'OLIVEROS CV.pdf', 'e411ba015c79a77a27013c4206313257.pdf', 'OLIVEROS CV.pdf', '95ae712a926e7a6e5ceef81ed3ba0f14.pdf', 'OLIVEROS CV.pdf', 'dsada', 'asdas', 'sada', 9, '2026-08-31 08:46:40');

-- --------------------------------------------------------

--
-- Table structure for table `crad_manuscript_evaluations`
--

CREATE TABLE `crad_manuscript_evaluations` (
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
-- Dumping data for table `crad_manuscript_evaluations`
--

INSERT INTO `crad_manuscript_evaluations` (`id`, `submission_id`, `research_group_id`, `evaluator_user_id`, `evaluator_name`, `content_score`, `methodology_score`, `results_score`, `conclusions_score`, `recommendations_score`, `references_score`, `formatting_score`, `compliance_score`, `remarks`, `result`, `overall_score`, `evaluated_at`, `created_at`) VALUES
(1, 0, 61, 3, '', 99.00, 99.00, 99.00, 99.00, 99.00, 99.00, 99.00, 99.00, '99', 'APPROVED', 99.00, '2026-08-28 08:03:48', '2026-08-28 08:03:48'),
(2, 2, 62, 3, '', 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, '100', 'APPROVED', 100.00, '2026-08-28 15:45:48', '2026-08-28 15:45:48'),
(3, 3, 63, 3, '', 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, '100', 'APPROVED', 100.00, '2026-08-28 16:35:30', '2026-08-28 16:35:30'),
(4, 4, 64, 3, '', 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, 100.00, '', 'APPROVED', 100.00, '2026-08-31 06:17:25', '2026-08-31 06:17:25');

-- --------------------------------------------------------

--
-- Table structure for table `crad_manuscript_submissions`
--

CREATE TABLE `crad_manuscript_submissions` (
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
-- Dumping data for table `crad_manuscript_submissions`
--

INSERT INTO `crad_manuscript_submissions` (`id`, `research_group_id`, `version_number`, `status`, `submitted_by_user`, `submitted_by_name`, `submitted_by_email`, `submission_notes`, `original_name`, `stored_subdir`, `stored_name`, `file_size`, `file_mime`, `submission_token`, `submitted_at`, `reviewed_at`, `updated_at`) VALUES
(1, 61, 1, 'Approved', 9, '', 'kenlangmalakas0308@gmail.com', '', 'CRAD_Chapter_1_TO_4 (1).docx', 'manuscripts/g61', '059624a21b3ee3e8c9d5d6d6cae4d3d8.docx', 4824980, 'application/octet-stream', 'c0ba01bd7d587e02c4aac89baad005b9817863f67e132d46c53fd5a8e2f202b8', '2026-08-28 08:01:38', '2026-08-28 08:03:48', '2026-08-28 08:03:48'),
(2, 62, 1, 'Approved', 9, '', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'manuscripts/g62', 'cd889c4f0fa3737edd9c7075cae72eb5.docx', 302605, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'ac45628a1a25f5f177bbcace7b945c684229993d6462e2d2dba7551d88a01198', '2026-08-28 15:45:09', '2026-08-28 15:45:48', '2026-08-28 15:45:48'),
(3, 63, 1, 'Approved', 9, '', 'kenlangmalakas0308@gmail.com', '', 'OJT_PRACTICUM_1_NARRATIVE_REPORT (1) (1).docx', 'manuscripts/g63', 'c4d2449c98f39a5604fedf92ac2cc70e.docx', 302605, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '4be87c933edc484ef2cddf1a86daa6e4521cc149c28b66b278d67db825ffdd44', '2026-08-28 16:34:34', '2026-08-28 16:35:30', '2026-08-28 16:35:30'),
(4, 64, 1, 'Approved', 9, '', 'kenlangmalakas0308@gmail.com', '', 'OLIVEROS CV.pdf', 'manuscripts/g64', '5fa67590cfadb682bbf15dcef84b36d5.pdf', 294354, 'application/pdf', '750a785eb19895c7c3df2b3ff9ed349d8f57813089bb7bdbe2203b7228ca27db', '2026-08-31 06:16:23', '2026-08-31 06:17:25', '2026-08-31 06:17:25');

-- --------------------------------------------------------

--
-- Table structure for table `crad_panel_assignment_notifications`
--

CREATE TABLE `crad_panel_assignment_notifications` (
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
-- Dumping data for table `crad_panel_assignment_notifications`
--

INSERT INTO `crad_panel_assignment_notifications` (`id`, `event_key`, `recipient_user_id`, `recipient_role`, `recipient_email`, `panel_assignment_id`, `research_group_id`, `title`, `body`, `url`, `is_read`, `created_at`) VALUES
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
(60, 'preoral-panel-assignment:65:u493', 493, 'panel', 'michelle.guevarra@bestlink.edu.ph', 39, 65, 'Pre-Oral Panel Assignment', 'You have been assigned as a Panel Member for Group 01\nDEVELOPMENT OF AI ANALYSIS\nDefense Phase: Pre-Oral Defense', '/sms2_system/modules/faculty/pages/assigned-defenses.php?group=RG-2026-001', 0, '2026-08-31 09:43:31');

-- --------------------------------------------------------

--
-- Table structure for table `crad_panel_member_availability`
--

CREATE TABLE `crad_panel_member_availability` (
  `id` int(10) UNSIGNED NOT NULL,
  `panel_user_id` int(10) UNSIGNED NOT NULL,
  `availability_status` varchar(40) NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crad_panel_member_availability`
--

INSERT INTO `crad_panel_member_availability` (`id`, `panel_user_id`, `availability_status`, `notes`, `updated_at`, `created_at`) VALUES
(6, 491, 'Available', '', '2026-08-23 04:08:58', '2026-08-23 04:08:58'),
(7, 492, 'Available', '', '2026-08-23 04:09:10', '2026-08-23 04:09:10'),
(8, 493, 'Available', '', '2026-08-23 04:09:26', '2026-08-23 04:09:26');

-- --------------------------------------------------------

--
-- Table structure for table `crad_preoral_defense_evaluations`
--

CREATE TABLE `crad_preoral_defense_evaluations` (
  `id` int(10) UNSIGNED NOT NULL,
  `defense_schedule_id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `panel_user_id` int(10) UNSIGNED NOT NULL,
  `panel_name` varchar(150) NOT NULL DEFAULT '',
  `content_score` decimal(5,2) NOT NULL,
  `methodology_score` decimal(5,2) NOT NULL,
  `references_score` decimal(5,2) NOT NULL,
  `format_score` decimal(5,2) NOT NULL,
  `remarks` text DEFAULT NULL,
  `result` enum('APPROVED','APPROVED WITH REVISION','FAILED') NOT NULL,
  `overall_score` decimal(5,2) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `crad_proposal_documents`
--

CREATE TABLE `crad_proposal_documents` (
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
-- Table structure for table `crad_proposal_drafts`
--

CREATE TABLE `crad_proposal_drafts` (
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
-- Table structure for table `crad_proposal_members`
--

CREATE TABLE `crad_proposal_members` (
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
-- Table structure for table `crad_proposal_status_logs`
--

CREATE TABLE `crad_proposal_status_logs` (
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
-- Table structure for table `crad_publications`
--

CREATE TABLE `crad_publications` (
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
-- Dumping data for table `crad_publications`
--

INSERT INTO `crad_publications` (`id`, `research_group_id`, `title`, `authors`, `publication_outlet`, `publication_date`, `doi_link`, `status`, `notes`, `created_by_user`, `created_by_name`, `created_at`, `updated_at`) VALUES
(4, 62, 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'Group 01', '', '2026-08-29', 'i3o213uo1u3oi12', 'Published', '', 3, '', '2026-08-28 16:13:42', '2026-08-28 16:14:09'),
(5, 63, 'DEVELOPMENT OF AI', 'Group 01', '', NULL, '', 'Draft', NULL, 3, '', '2026-08-28 16:39:18', '2026-08-28 16:39:18'),
(6, 64, 'DEVELOPMENT OF AI ANALYSIS', 'Group 01', 'crad', '2026-08-31', 'http://localhost/sms2_system/modules/crad/pages/documentation-publication-management.php', 'Published', '', 3, '', '2026-08-31 06:32:29', '2026-08-31 06:33:01');

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_adviser_assignments`
--

CREATE TABLE `crad_research_adviser_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_number` varchar(30) DEFAULT NULL,
  `group_number` varchar(40) DEFAULT NULL,
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

--
-- Dumping data for table `crad_research_adviser_assignments`
--

INSERT INTO `crad_research_adviser_assignments` (`id`, `research_group_id`, `proposal_id`, `proposal_number`, `group_number`, `adviser_name`, `adviser_email`, `adviser_user_id`, `expertise`, `availability_status`, `assignment_status`, `notes`, `assigned_by`, `assigned_at`, `created_at`, `updated_at`, `notification_sent_at`, `notification_sent_by`) VALUES
(107, 65, NULL, 'TAP-2026-00054', 'RG-2026-001', 'Dr. Roberto M. Santos', 'rsantos@bestlink.edu.ph', 54, 'Artificial Intelligence / Machine Learning / Data Analytics / Data Analysis', 'Available', 'Assigned', 'Synced from fully approved research record.', 40, '2026-08-31 09:29:05', '2026-08-14 12:45:37', '2026-08-31 09:29:12', '2026-08-31 09:29:05', 40);

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_coordinator_assignments`
--

CREATE TABLE `crad_research_coordinator_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `research_group_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_id` int(10) UNSIGNED DEFAULT NULL,
  `title_approval_id` int(10) UNSIGNED DEFAULT NULL,
  `proposal_number` varchar(30) DEFAULT NULL,
  `group_number` varchar(40) DEFAULT NULL,
  `group_name` varchar(120) NOT NULL DEFAULT '',
  `research_title` varchar(255) NOT NULL DEFAULT '',
  `coordinator_user_id` int(10) UNSIGNED DEFAULT NULL,
  `coordinator_name` varchar(200) NOT NULL DEFAULT '',
  `coordinator_email` varchar(200) NOT NULL DEFAULT '',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `assigned_by` int(10) UNSIGNED DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crad_research_coordinator_assignments`
--

INSERT INTO `crad_research_coordinator_assignments` (`id`, `research_group_id`, `proposal_id`, `title_approval_id`, `proposal_number`, `group_number`, `group_name`, `research_title`, `coordinator_user_id`, `coordinator_name`, `coordinator_email`, `status`, `assigned_by`, `assigned_at`, `created_at`, `updated_at`) VALUES
(42, 65, NULL, 54, 'TAP-2026-00054', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 40, 'Mrs. Kris Guevarra', 'researchcoordinator@bestlink.edu.ph', 'Active', 3, '2026-08-31 09:29:54', '2026-08-31 09:29:54', '2026-08-31 09:29:54');

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_defense_schedules`
--

CREATE TABLE `crad_research_defense_schedules` (
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
-- Dumping data for table `crad_research_defense_schedules`
--

INSERT INTO `crad_research_defense_schedules` (`id`, `research_group_id`, `proposal_id`, `proposal_number`, `group_number`, `research_group`, `research_title`, `adviser_name`, `panel_members`, `panel_chair`, `venue`, `venue_id`, `defense_datetime`, `defense_end_datetime`, `defense_type`, `status`, `recorded_by`, `finalized_by`, `finalized_at`, `recorded_at`, `updated_at`) VALUES
(28, 61, NULL, 'TAP-2026-00050', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-25 09:00:00', '2026-08-25 10:00:00', 'Pre-Oral', 'Rejected', 116, NULL, NULL, '2026-08-23 04:10:39', '2026-08-23 04:10:47'),
(29, 61, NULL, 'TAP-2026-00050', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-25 11:00:00', '2026-08-25 12:00:00', 'Pre-Oral', 'Rejected', 116, NULL, NULL, '2026-08-23 04:10:39', '2026-08-23 04:10:47'),
(30, 61, NULL, 'TAP-2026-00050', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-25 13:00:00', '2026-08-25 14:00:00', 'Pre-Oral', 'Finalized', 116, 116, '2026-08-23 04:10:47', '2026-08-23 04:10:39', '2026-08-23 04:10:47'),
(31, 61, NULL, 'TAP-2026-00050', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-29 08:15:00', '2026-08-29 09:15:00', 'Final Defense', 'Rejected', 116, NULL, NULL, '2026-08-28 08:16:15', '2026-08-28 08:21:42'),
(32, 61, NULL, 'TAP-2026-00050', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-29 10:15:00', '2026-08-29 11:16:00', 'Final Defense', 'Finalized', 116, 116, '2026-08-28 08:21:42', '2026-08-28 08:16:15', '2026-08-28 08:21:42'),
(33, 61, NULL, 'TAP-2026-00050', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'CRAD Conference Room', 1, '2026-09-01 02:16:00', '2026-09-01 03:16:00', 'Final Defense', 'Rejected', 116, NULL, NULL, '2026-08-28 08:16:15', '2026-08-28 08:21:42'),
(34, 62, NULL, 'TAP-2026-00051', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'AVR Room', 4, '2026-09-01 09:00:00', '2026-09-01 10:00:00', 'Pre-Oral', 'Rejected', 116, NULL, NULL, '2026-08-28 15:35:30', '2026-08-28 15:35:39'),
(35, 62, NULL, 'TAP-2026-00051', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'AVR Room', 4, '2026-09-01 12:00:00', '2026-09-01 13:00:00', 'Pre-Oral', 'Rejected', 116, NULL, NULL, '2026-08-28 15:35:30', '2026-08-28 15:35:39'),
(36, 62, NULL, 'TAP-2026-00051', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'AVR Room', 4, '2026-09-01 15:00:00', '2026-09-01 16:00:00', 'Pre-Oral', 'Finalized', 116, 116, '2026-08-28 15:35:39', '2026-08-28 15:35:30', '2026-08-28 15:35:39'),
(37, 62, NULL, 'TAP-2026-00051', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-09-10 10:00:00', '2026-09-10 11:00:00', 'Final Defense', 'Rejected', 116, NULL, NULL, '2026-08-28 15:48:16', '2026-08-28 15:52:23'),
(38, 62, NULL, 'TAP-2026-00051', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-09-10 12:00:00', '2026-09-10 13:00:00', 'Final Defense', 'Finalized', 116, 116, '2026-08-28 15:52:23', '2026-08-28 15:48:16', '2026-08-28 15:52:23'),
(39, 62, NULL, 'TAP-2026-00051', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-09-10 14:00:00', '2026-09-10 15:00:00', 'Final Defense', 'Rejected', 116, NULL, NULL, '2026-08-28 15:48:16', '2026-08-28 15:52:23'),
(40, 63, NULL, 'TAP-2026-00052', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-29 09:09:00', '2026-08-29 10:00:00', 'Pre-Oral', 'Rejected', 116, NULL, NULL, '2026-08-28 16:32:07', '2026-08-28 16:32:15'),
(41, 63, NULL, 'TAP-2026-00052', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-29 11:00:00', '2026-08-29 12:00:00', 'Pre-Oral', 'Finalized', 116, 116, '2026-08-28 16:32:15', '2026-08-28 16:32:07', '2026-08-28 16:32:15'),
(42, 63, NULL, 'TAP-2026-00052', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-29 13:00:00', '2026-08-29 14:00:00', 'Pre-Oral', 'Rejected', 116, NULL, NULL, '2026-08-28 16:32:07', '2026-08-28 16:32:15'),
(43, 63, NULL, 'TAP-2026-00052', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-31 09:00:00', '2026-08-31 10:00:00', 'Final Defense', 'Rejected', 116, NULL, NULL, '2026-08-28 16:36:58', '2026-08-28 16:37:05'),
(44, 63, NULL, 'TAP-2026-00052', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-31 12:00:00', '2026-08-31 13:00:00', 'Final Defense', 'Finalized', 116, 116, '2026-08-28 16:37:05', '2026-08-28 16:36:58', '2026-08-28 16:37:05'),
(45, 63, NULL, 'TAP-2026-00052', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-31 14:00:00', '2026-08-31 15:00:00', 'Final Defense', 'Rejected', 116, NULL, NULL, '2026-08-28 16:36:58', '2026-08-28 16:37:05'),
(46, 64, NULL, 'TAP-2026-00053', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-08-31 09:00:00', '2026-08-31 10:00:00', 'Pre-Oral', 'Rejected', 116, NULL, NULL, '2026-08-31 06:10:14', '2026-08-31 06:10:27'),
(47, 64, NULL, 'TAP-2026-00053', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'AVR Room', 4, '2026-08-31 11:00:00', '2026-08-31 12:00:00', 'Pre-Oral', 'Rejected', 116, NULL, NULL, '2026-08-31 06:10:14', '2026-08-31 06:10:27'),
(48, 64, NULL, 'TAP-2026-00053', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'AVR Room', 4, '2026-08-31 13:00:00', '2026-08-31 14:00:00', 'Pre-Oral', 'Finalized', 116, 116, '2026-08-31 06:10:27', '2026-08-31 06:10:14', '2026-08-31 06:10:27'),
(49, 64, NULL, 'TAP-2026-00053', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'CRAD Conference Room', 1, '2026-09-03 09:00:00', '2026-09-03 10:00:00', 'Final Defense', 'Rejected', 116, NULL, NULL, '2026-08-31 06:22:22', '2026-08-31 06:22:35'),
(50, 64, NULL, 'TAP-2026-00053', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-09-03 11:00:00', '2026-09-03 12:00:00', 'Final Defense', 'Finalized', 116, 116, '2026-08-31 06:22:35', '2026-08-31 06:22:22', '2026-08-31 06:22:35'),
(51, 64, NULL, 'TAP-2026-00053', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-09-03 13:00:00', '2026-09-03 14:00:00', 'Final Defense', 'Rejected', 116, NULL, NULL, '2026-08-31 06:22:22', '2026-08-31 06:22:35'),
(52, 65, NULL, 'TAP-2026-00054', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-09-01 09:00:00', '2026-09-01 11:00:00', 'Pre-Oral', 'Proposed', 116, NULL, NULL, '2026-08-31 10:02:23', '2026-08-31 10:02:23'),
(53, 65, NULL, 'TAP-2026-00054', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-09-03 09:00:00', '2026-09-03 11:00:00', 'Pre-Oral', 'Proposed', 116, NULL, NULL, '2026-08-31 10:02:23', '2026-08-31 10:02:23'),
(54, 65, NULL, 'TAP-2026-00054', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'Dr. Roberto M. Santos', 'Dr. Jobert Valentino\nDr. Jonathan Estrada\nDr. Michelle Guevarra', 'Dr. Jobert Valentino', 'Computer Laboratory 1', 5, '2026-09-07 09:00:00', '2026-09-07 11:00:00', 'Pre-Oral', 'Selected', 116, NULL, NULL, '2026-08-31 10:02:23', '2026-08-31 10:02:44');

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_groups`
--

CREATE TABLE `crad_research_groups` (
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
-- Dumping data for table `crad_research_groups`
--

INSERT INTO `crad_research_groups` (`id`, `proposal_id`, `title_approval_id`, `proposal_number`, `group_number`, `group_name`, `research_title`, `college_dept`, `adviser`, `academic_year`, `leader_name`, `leader_id`, `leader_email`, `leader_contact`, `status`, `date_assigned`, `created_by`, `created_at`) VALUES
(65, NULL, 54, 'TAP-2026-00054', 'RG-2026-001', 'Group 01', 'DEVELOPMENT OF AI ANALYSIS', 'College of Computer Studies', 'Dr. Roberto M. Santos', '2026-2027', 'Student User', 'S230000001', '', '', 'Approved', '2026-09-01', 3, '2026-08-31 17:28:17');

--
-- Triggers `crad_research_groups`
--
DELIMITER $$
CREATE TRIGGER `trg_research_groups_panel_notifications_after_delete` AFTER DELETE ON `crad_research_groups` FOR EACH ROW BEGIN
                DELETE FROM crad_panel_assignment_notifications
                WHERE research_group_id = OLD.id;
            END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_research_groups_preoral_evals_after_delete` AFTER DELETE ON `crad_research_groups` FOR EACH ROW BEGIN
                DELETE FROM crad_preoral_defense_evaluations
                WHERE research_group_id = OLD.id;
            END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_research_groups_preoral_evaluations_after_delete` AFTER DELETE ON `crad_research_groups` FOR EACH ROW BEGIN
                DELETE FROM crad_preoral_defense_evaluations
                WHERE research_group_id = OLD.id;
            END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_milestones`
--

CREATE TABLE `crad_research_milestones` (
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
-- Dumping data for table `crad_research_milestones`
--

INSERT INTO `crad_research_milestones` (`id`, `research_plan_id`, `milestone_name`, `description`, `milestone_order`, `progress_percentage`, `weight`, `status`, `start_date`, `target_date`, `completed_at`, `researcher_notes`, `adviser_remarks`, `panel_remarks`, `created_at`, `updated_at`) VALUES
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
(216, 26, 'Documentation', 'Final Documentation and Report', 8, 0.00, 1.00, 'Submitted for Review', NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 09:34:19', '2026-08-31 09:35:48');

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_panel_assignments`
--

CREATE TABLE `crad_research_panel_assignments` (
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
-- Dumping data for table `crad_research_panel_assignments`
--

INSERT INTO `crad_research_panel_assignments` (`id`, `research_group_id`, `defense_schedule_id`, `proposal_id`, `title_approval_id`, `proposal_number`, `group_number`, `research_title`, `panel_user_id`, `panel_name`, `panel_email`, `expertise`, `availability_status`, `assignment_status`, `defense_phase`, `assigned_by`, `assigned_at`, `created_at`, `updated_at`) VALUES
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
(37, 65, NULL, NULL, 54, 'TAP-2026-00054', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 491, 'Dr. Jobert Valentino', 'jobert.valentino@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-31 09:43:31', '2026-08-31 09:43:31', '2026-08-31 09:43:31'),
(38, 65, NULL, NULL, 54, 'TAP-2026-00054', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 492, 'Dr. Jonathan Estrada', 'jonathan.estrada@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-31 09:43:31', '2026-08-31 09:43:31', '2026-08-31 09:43:31'),
(39, 65, NULL, NULL, 54, 'TAP-2026-00054', 'RG-2026-001', 'DEVELOPMENT OF AI ANALYSIS', 493, 'Dr. Michelle Guevarra', 'michelle.guevarra@bestlink.edu.ph', '', 'Available', 'Assigned', 'Pre-Oral Defense', 40, '2026-08-31 09:43:31', '2026-08-31 09:43:31', '2026-08-31 09:43:31');

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_plans`
--

CREATE TABLE `crad_research_plans` (
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
-- Dumping data for table `crad_research_plans`
--

INSERT INTO `crad_research_plans` (`id`, `research_group_id`, `research_title`, `group_number`, `adviser_id`, `adviser_name`, `adviser_email`, `start_date`, `target_completion_date`, `current_stage`, `overall_progress`, `status`, `created_at`, `updated_at`, `final_defense_recommended`, `final_defense_recommended_by`, `final_defense_recommended_by_name`, `final_defense_recommended_at`, `final_defense_recommendation_remarks`) VALUES
(21, NULL, 'DEVELOPMENT OF AI ASSISTED', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-23', NULL, 'Planning', 0.00, 'Active', '2026-08-23 03:16:21', '2026-08-23 03:46:14', 0, NULL, NULL, NULL, NULL),
(22, NULL, 'DEVELOPMENT OF AI ANALYSIS', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-23', NULL, 'Pre-Oral Defense', 100.00, 'Active', '2026-08-23 03:49:51', '2026-08-28 08:00:25', 0, NULL, NULL, NULL, NULL),
(23, NULL, 'DEVELOPMENT OF AI ASSISTED DOCUMENT', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-28', NULL, 'Pre-Oral Defense', 100.00, 'Active', '2026-08-28 15:30:09', '2026-08-28 16:05:34', 1, 54, 'Dr. Roberto M. Santos', '2026-08-28 15:45:21', ''),
(24, NULL, 'DEVELOPMENT OF AI', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-28', NULL, 'Pre-Oral Defense', 100.00, 'Active', '2026-08-28 16:28:29', '2026-08-28 16:34:47', 1, 54, 'Dr. Roberto M. Santos', '2026-08-28 16:34:27', ''),
(25, NULL, 'DEVELOPMENT OF AI ANALYSIS', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-31', NULL, 'Pre-Oral Defense', 100.00, 'Active', '2026-08-31 05:28:10', '2026-08-31 06:16:38', 1, 54, 'Dr. Roberto M. Santos', '2026-08-31 06:15:48', ''),
(26, 65, 'DEVELOPMENT OF AI ANALYSIS', 'RG-2026-001', 54, 'Dr. Roberto M. Santos', '', '2026-08-31', NULL, 'Planning', 0.00, 'Active', '2026-08-31 09:34:19', '2026-08-31 09:36:53', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_progress_activity_logs`
--

CREATE TABLE `crad_research_progress_activity_logs` (
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
-- Dumping data for table `crad_research_progress_activity_logs`
--

INSERT INTO `crad_research_progress_activity_logs` (`id`, `research_plan_id`, `user_id`, `user_name`, `user_role`, `action`, `entity_type`, `entity_id`, `old_value`, `new_value`, `description`, `created_at`) VALUES
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
(166, 26, 54, 'Dr. Roberto M. Santos', 'adviser', 'progress_approved', 'feedback', 71, NULL, NULL, 'Adviser approved progress', '2026-08-31 09:36:53');

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_progress_attachments`
--

CREATE TABLE `crad_research_progress_attachments` (
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
-- Table structure for table `crad_research_progress_ai_analyses`
--

CREATE TABLE `crad_research_progress_ai_analyses` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rpai_update` (`progress_update_id`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crad_research_progress_attachments`
--

INSERT INTO `crad_research_progress_attachments` (`id`, `progress_update_id`, `file_name`, `file_path`, `file_type`, `file_size`, `uploaded_by`, `created_at`) VALUES
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
(79, 93, 'OLIVEROS CV.pdf', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/afc9627ed4ff5d61ef4658268ee83f4c.pdf', 'application/pdf', 294354, 9, '2026-08-31 09:35:48');

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_progress_feedback`
--

CREATE TABLE `crad_research_progress_feedback` (
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
-- Dumping data for table `crad_research_progress_feedback`
--

INSERT INTO `crad_research_progress_feedback` (`id`, `progress_update_id`, `milestone_id`, `research_plan_id`, `adviser_user_id`, `adviser_name`, `feedback_text`, `new_milestone_status`, `submission_token`, `feedback_type`, `created_at`, `updated_at`) VALUES
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
(71, 88, 211, 26, 54, 'Dr. Roberto M. Santos', 'Progress approved.', 'Approved', 'fa09c3d0d541bb1b2d0ba34ed41ad298', 'Progress Approved', '2026-08-31 09:36:53', '2026-08-31 09:36:53');

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_progress_notifications`
--

CREATE TABLE `crad_research_progress_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `recipient_user_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'FK to sms_users.id (NULL = role-based)',
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
-- Dumping data for table `crad_research_progress_notifications`
--

INSERT INTO `crad_research_progress_notifications` (`id`, `recipient_user_id`, `recipient_email`, `recipient_role`, `batch_key`, `notification_type`, `title`, `body`, `related_entity_type`, `related_entity_id`, `action_url`, `status`, `created_at`, `read_at`) VALUES
(50, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:53', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 1', 'progress_update', 53, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-23 03:49:58', NULL),
(51, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:54', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 2', 'progress_update', 54, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-23 03:50:08', NULL),
(52, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:55', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 3', 'progress_update', 55, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-23 03:50:18', NULL),
(53, 9, '', 'student', 'approval:36', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 36, NULL, 'unread', '2026-08-23 03:50:29', NULL),
(54, 9, '', 'student', 'approval:37', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 37, NULL, 'unread', '2026-08-23 03:50:36', NULL),
(55, 9, '', 'student', 'approval:38', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 38, NULL, 'unread', '2026-08-23 03:50:45', NULL),
(56, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:56', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 4', 'progress_update', 56, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-23 04:12:04', NULL),
(57, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:57', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 5', 'progress_update', 57, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-23 04:12:11', NULL),
(58, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:58', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for System Development', 'progress_update', 58, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-23 04:12:18', NULL),
(59, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:59', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Testing', 'progress_update', 59, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-23 04:12:25', NULL),
(60, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:60', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Documentation', 'progress_update', 60, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-23 04:12:31', NULL),
(61, 9, '', 'student', 'revision:39', 'revision_requested', 'Revision Requested', 'Your adviser requested revisions on your progress update', 'feedback', 39, NULL, 'unread', '2026-08-23 04:13:01', NULL),
(62, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:61', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Documentation', 'progress_update', 61, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-23 04:13:16', NULL),
(63, 9, '', 'student', 'approval:40', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 40, NULL, 'unread', '2026-08-23 04:13:25', NULL),
(64, 9, '', 'student', 'approval:41', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 41, NULL, 'unread', '2026-08-23 04:13:35', NULL),
(65, 9, '', 'student', 'approval:42', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 42, NULL, 'unread', '2026-08-23 04:13:45', NULL),
(66, 9, '', 'student', 'approval:43', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 43, NULL, 'unread', '2026-08-23 04:13:52', NULL),
(67, 9, '', 'student', 'approval:44', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 44, NULL, 'unread', '2026-08-23 04:13:59', NULL),
(68, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:62', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 1', 'progress_update', 62, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 15:30:17', NULL),
(69, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:63', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 2', 'progress_update', 63, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 15:30:54', NULL),
(70, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:64', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 3', 'progress_update', 64, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 15:31:01', NULL),
(71, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:65', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 4', 'progress_update', 65, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 15:31:08', NULL),
(72, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:66', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 5', 'progress_update', 66, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 15:31:16', NULL),
(73, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:67', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for System Development', 'progress_update', 67, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 15:31:23', NULL),
(74, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:68', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Testing', 'progress_update', 68, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 15:31:30', NULL),
(75, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:69', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Documentation', 'progress_update', 69, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 15:31:36', NULL),
(76, 9, '', 'student', 'approval:45', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 45, NULL, 'unread', '2026-08-28 15:32:47', NULL),
(77, 9, '', 'student', 'approval:46', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 46, NULL, 'unread', '2026-08-28 15:32:53', NULL),
(78, 9, '', 'student', 'approval:47', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 47, NULL, 'unread', '2026-08-28 15:32:57', NULL),
(79, 9, '', 'student', 'approval:48', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 48, NULL, 'unread', '2026-08-28 15:37:17', NULL),
(80, 9, '', 'student', 'approval:49', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 49, NULL, 'unread', '2026-08-28 15:37:22', NULL),
(81, 9, '', 'student', 'approval:50', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 50, NULL, 'unread', '2026-08-28 15:37:29', NULL),
(82, 9, '', 'student', 'approval:51', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 51, NULL, 'unread', '2026-08-28 15:37:35', NULL),
(83, 9, '', 'student', 'approval:52', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 52, NULL, 'unread', '2026-08-28 15:37:44', NULL),
(84, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:70', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 1', 'progress_update', 70, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 16:28:35', NULL),
(85, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:71', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 2', 'progress_update', 71, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 16:28:41', NULL),
(86, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:72', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 3', 'progress_update', 72, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 16:28:47', NULL),
(87, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:73', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 4', 'progress_update', 73, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 16:28:54', NULL),
(88, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:74', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 5', 'progress_update', 74, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 16:29:01', NULL),
(89, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:75', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for System Development', 'progress_update', 75, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 16:29:08', NULL),
(90, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:76', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Testing', 'progress_update', 76, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 16:29:19', NULL),
(91, 9, '', 'student', 'approval:53', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 53, NULL, 'unread', '2026-08-28 16:29:27', NULL),
(92, 9, '', 'student', 'approval:54', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 54, NULL, 'unread', '2026-08-28 16:29:31', NULL),
(93, 9, '', 'student', 'approval:55', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 55, NULL, 'unread', '2026-08-28 16:29:36', NULL),
(94, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:77', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Documentation', 'progress_update', 77, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-28 16:33:53', NULL),
(95, 9, '', 'student', 'approval:56', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 56, NULL, 'unread', '2026-08-28 16:33:58', NULL),
(96, 9, '', 'student', 'approval:57', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 57, NULL, 'unread', '2026-08-28 16:34:03', NULL),
(97, 9, '', 'student', 'approval:58', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 58, NULL, 'unread', '2026-08-28 16:34:11', NULL),
(98, 9, '', 'student', 'approval:59', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 59, NULL, 'unread', '2026-08-28 16:34:16', NULL),
(99, 9, '', 'student', 'approval:60', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 60, NULL, 'unread', '2026-08-28 16:34:23', NULL),
(100, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:78', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 1', 'progress_update', 78, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 05:28:26', NULL),
(101, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:79', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 2', 'progress_update', 79, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 05:28:34', NULL),
(102, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:80', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 3', 'progress_update', 80, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 05:28:45', NULL),
(103, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:81', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 4', 'progress_update', 81, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 05:28:53', NULL),
(104, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:82', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 5', 'progress_update', 82, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 05:29:03', NULL),
(105, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:83', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for System Development', 'progress_update', 83, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 05:29:15', NULL),
(106, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:84', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Testing', 'progress_update', 84, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 05:29:24', NULL),
(107, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:85', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Documentation', 'progress_update', 85, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 05:29:32', NULL),
(108, 9, '', 'student', 'approval:61', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 61, NULL, 'unread', '2026-08-31 05:29:47', NULL),
(109, 9, '', 'student', 'approval:62', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 62, NULL, 'unread', '2026-08-31 05:29:55', NULL),
(110, 9, '', 'student', 'approval:63', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 63, NULL, 'unread', '2026-08-31 05:30:03', NULL),
(111, 9, '', 'student', 'approval:64', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 64, NULL, 'unread', '2026-08-31 06:15:08', NULL),
(112, 9, '', 'student', 'approval:65', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 65, NULL, 'unread', '2026-08-31 06:15:17', NULL),
(113, 9, '', 'student', 'approval:66', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 66, NULL, 'unread', '2026-08-31 06:15:28', NULL),
(114, 9, '', 'student', 'approval:67', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 67, NULL, 'unread', '2026-08-31 06:15:35', NULL),
(115, 9, '', 'student', 'approval:68', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 68, NULL, 'unread', '2026-08-31 06:15:42', NULL),
(116, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:86', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 1', 'progress_update', 86, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 09:34:32', NULL),
(117, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:87', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 2', 'progress_update', 87, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 09:34:41', NULL),
(118, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:88', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 3', 'progress_update', 88, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 09:34:49', NULL),
(119, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:89', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 4', 'progress_update', 89, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 09:35:01', NULL),
(120, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:90', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Chapter 5', 'progress_update', 90, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 09:35:12', NULL),
(121, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:91', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for System Development', 'progress_update', 91, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 09:35:29', NULL),
(122, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:92', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Testing', 'progress_update', 92, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 09:35:37', NULL),
(123, 54, 'rsantos@bestlink.edu.ph', 'adviser', 'progress_update:93', 'progress_update', 'New Progress Update', 'RG-2026-001 submitted a progress update for Documentation', 'progress_update', 93, '/sms2_system/modules/crad/modules/faculty/pages/research-progress.php?group=RG-2026-001', 'unread', '2026-08-31 09:35:48', NULL),
(124, 9, '', 'student', 'approval:69', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 69, NULL, 'unread', '2026-08-31 09:36:20', NULL),
(125, 9, '', 'student', 'approval:70', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 70, NULL, 'unread', '2026-08-31 09:36:29', NULL),
(126, 9, '', 'student', 'approval:71', 'progress_approved', 'Progress Approved', 'Your adviser approved your progress update', 'feedback', 71, NULL, 'unread', '2026-08-31 09:36:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_progress_updates`
--

CREATE TABLE `crad_research_progress_updates` (
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
-- Dumping data for table `crad_research_progress_updates`
--

INSERT INTO `crad_research_progress_updates` (`id`, `research_plan_id`, `research_group_id`, `milestone_id`, `submitted_by_user_id`, `submitted_by_name`, `update_title`, `accomplishments`, `problems_blockers`, `next_planned_activity`, `attachment_path`, `attachment_original_name`, `submission_token`, `previous_progress`, `new_progress`, `milestone_status`, `submitted_at`, `updated_at`) VALUES
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
(93, 26, 65, 216, 9, 'Student User', 'DEVELOPMENT OF AI ANALYSIS', 'fdssdfsd', '', 'weqwe', 'C:\\xampp\\htdocs\\sms2_system/storage/uploads/research_progress/g65/u9/afc9627ed4ff5d61ef4658268ee83f4c.pdf', 'OLIVEROS CV.pdf', '90c4c6b20d3280f3e1c556985d525e17', 0.00, 0.00, 'Submitted for Review', '2026-08-31 09:35:48', '2026-08-31 09:35:48');

-- --------------------------------------------------------

--
-- Table structure for table `crad_research_proposals`
--

CREATE TABLE `crad_research_proposals` (
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
-- Table structure for table `crad_research_revision_cycles`
--

CREATE TABLE `crad_research_revision_cycles` (
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
-- Table structure for table `crad_research_venues`
--

CREATE TABLE `crad_research_venues` (
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
-- Dumping data for table `crad_research_venues`
--

INSERT INTO `crad_research_venues` (`id`, `venue_name`, `capacity`, `venue_type`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CRAD Conference Room', 30, 'Conference Room', 'Available', NULL, '2026-08-10 13:50:31', '2026-08-10 13:50:31'),
(2, 'Research Room 1', 25, 'Research Room', 'Available', NULL, '2026-08-10 13:50:31', '2026-08-10 13:50:31'),
(3, 'Research Room 2', 25, 'Research Room', 'Available', NULL, '2026-08-10 13:50:31', '2026-08-10 13:50:31'),
(4, 'AVR Room', 100, 'Auditorium', 'Available', NULL, '2026-08-10 13:50:31', '2026-08-10 14:20:53'),
(5, 'Computer Laboratory 1', 40, 'Laboratory', 'Available', NULL, '2026-08-10 13:50:31', '2026-08-10 13:50:31');

-- --------------------------------------------------------

--
-- Table structure for table `crad_title_approvals`
--

CREATE TABLE `crad_title_approvals` (
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
-- Dumping data for table `crad_title_approvals`
--

INSERT INTO `crad_title_approvals` (`id`, `student_id`, `student_user_id`, `student_name`, `submission_date`, `department`, `proposed_title`, `discipline_cluster`, `primary_sdg`, `research_agenda`, `sdg_justification`, `members_json`, `adviser_name`, `adviser_email`, `coordinator_name`, `proposal_number`, `status`, `adviser_remarks`, `adviser_signature_data`, `coordinator_status`, `coordinator_remarks`, `coordinator_screening_json`, `coordinator_signature_data`, `coordinator_reviewed_at`, `crad_status`, `crad_signature_data`, `crad_reviewed_at`, `sent_at`, `reviewed_at`, `created_at`, `updated_at`) VALUES
(54, 'S230000001', 9, 'Student User', '2026-09-01', 'College of Computer Studies', 'DEVELOPMENT OF AI ANALYSIS', 'Engineering, Information Technology, and Computing', 'SDG 9 — Industry, Innovation and Infrastructure', 'Science, Technology, Digital Transformation, and Innovation', 'adadsadasdasda', '[[\"User, Student A.\",\"BSIT 4101\",\"OR-2646376\"]]', 'Dr. Roberto M. Santos', 'rsantos@bestlink.edu.ph', 'Mrs. Kris Guevarra', 'TAP-2026-00054', 'Approved', NULL, 'data:image/png;base64,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', 'Approved', NULL, '{\"agenda_alignment\":\"yes\",\"feasible_original\":\"yes\",\"ethical_sdg\":\"yes\"}', 'data:image/png;base64,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', '2026-08-31 09:27:29', 'Approved', 'data:image/png;base64,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', '2026-08-31 09:28:12', '2026-08-31 09:26:24', '2026-08-31 09:26:37', '2026-08-31 09:26:24', '2026-08-31 09:28:17');

--
-- Triggers `crad_title_approvals`
--
DELIMITER $$
CREATE TRIGGER `trg_title_approvals_after_delete` AFTER DELETE ON `crad_title_approvals` FOR EACH ROW BEGIN
            UPDATE crad_research_adviser_assignments a
               SET a.assignment_status = 'Pending'
             WHERE a.assignment_status = 'Assigned'
               AND (
                    (OLD.proposal_number IS NOT NULL
                     AND OLD.proposal_number <> ''
                     AND a.proposal_number = OLD.proposal_number)
                 OR (a.research_group_id IS NOT NULL
                     AND a.research_group_id IN (
                        SELECT g.id
                        FROM crad_research_groups g
                        WHERE g.title_approval_id = OLD.id
                     ))
                 OR (a.group_number IS NOT NULL
                     AND a.group_number <> ''
                     AND a.group_number IN (
                        SELECT g2.group_number
                        FROM crad_research_groups g2
                        WHERE g2.title_approval_id = OLD.id
                     ))
               );
        END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `crad_chapter_evaluations`
--
ALTER TABLE `crad_chapter_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chapter_evaluation_submission` (`submission_id`),
  ADD KEY `idx_chapter_eval_evaluator` (`evaluator_user_id`),
  ADD KEY `idx_chapter_eval_group` (`research_group_id`),
  ADD KEY `idx_chapter_eval_created` (`created_at`);

--
-- Indexes for table `crad_chapter_evaluation_notifications`
--
ALTER TABLE `crad_chapter_evaluation_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chapter_notification_event` (`event_key`),
  ADD KEY `idx_chapter_notification_recipient` (`recipient_user_id`,`recipient_role`,`recipient_email`),
  ADD KEY `idx_chapter_notification_submission` (`submission_id`),
  ADD KEY `idx_chapter_notification_created` (`created_at`);

--
-- Indexes for table `crad_chapter_submissions`
--
ALTER TABLE `crad_chapter_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_chapter_version` (`research_group_id`,`chapter_number`,`version_number`),
  ADD UNIQUE KEY `uniq_chapter_token` (`submission_token`),
  ADD KEY `idx_chapter_status` (`status`),
  ADD KEY `idx_chapter_group` (`research_group_id`),
  ADD KEY `idx_chapter_student` (`submitted_by_user`),
  ADD KEY `idx_chapter_updated` (`updated_at`);

--
-- Indexes for table `crad_chapter_submission_history`
--
ALTER TABLE `crad_chapter_submission_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chapter_history_submission` (`submission_id`),
  ADD KEY `idx_chapter_history_group` (`research_group_id`),
  ADD KEY `idx_chapter_history_created` (`created_at`);

--
-- Indexes for table `crad_final_defense_evaluations`
--
ALTER TABLE `crad_final_defense_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_final_panel_submission` (`defense_schedule_id`,`panel_user_id`),
  ADD KEY `idx_final_group` (`research_group_id`),
  ADD KEY `idx_final_panel` (`panel_user_id`),
  ADD KEY `idx_final_status` (`status`);

--
-- Indexes for table `crad_final_defense_recommendations`
--
ALTER TABLE `crad_final_defense_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_fdr_group` (`research_group_id`),
  ADD KEY `idx_fdr_status` (`status`);

--
-- Indexes for table `crad_final_manuscript_approvals`
--
ALTER TABLE `crad_final_manuscript_approvals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_fma_group` (`research_group_id`);

--
-- Indexes for table `crad_grant_applications`
--
ALTER TABLE `crad_grant_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_ga_token` (`submission_token`),
  ADD KEY `idx_ga_opportunity` (`grant_opportunity_id`),
  ADD KEY `idx_ga_group` (`research_group_id`),
  ADD KEY `idx_ga_status` (`status`),
  ADD KEY `idx_ga_submitted` (`submitted_at`);

--
-- Indexes for table `crad_grant_opportunities`
--
ALTER TABLE `crad_grant_opportunities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_go_status` (`status`),
  ADD KEY `idx_go_deadline` (`application_deadline`),
  ADD KEY `idx_go_created_by` (`created_by_user_id`);

--
-- Indexes for table `crad_grant_proposal_approval_steps`
--
ALTER TABLE `crad_grant_proposal_approval_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpas_workflow_step` (`workflow_id`,`step_key`),
  ADD KEY `idx_gpas_application` (`grant_application_id`),
  ADD KEY `idx_gpas_status` (`status`),
  ADD KEY `idx_gpas_role` (`approver_role_key`);

--
-- Indexes for table `crad_grant_proposal_approval_workflows`
--
ALTER TABLE `crad_grant_proposal_approval_workflows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpaw_application` (`grant_application_id`),
  ADD KEY `idx_gpaw_status` (`workflow_status`),
  ADD KEY `idx_gpaw_current_step` (`current_step_key`);

--
-- Indexes for table `crad_grant_proposal_evaluations`
--
ALTER TABLE `crad_grant_proposal_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpe_app_eval_ver` (`grant_application_id`,`evaluator_user_id`,`proposal_version`),
  ADD KEY `idx_gpe_application` (`grant_application_id`),
  ADD KEY `idx_gpe_evaluator` (`evaluator_user_id`),
  ADD KEY `idx_gpe_submitted` (`submitted_at`);

--
-- Indexes for table `crad_grant_proposal_notifications`
--
ALTER TABLE `crad_grant_proposal_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpn_event` (`event_key`),
  ADD KEY `idx_gpn_recipient_user` (`recipient_user_id`),
  ADD KEY `idx_gpn_application` (`grant_application_id`),
  ADD KEY `idx_gpn_created` (`created_at`);

--
-- Indexes for table `crad_grant_proposal_versions`
--
ALTER TABLE `crad_grant_proposal_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_gpv_app_ver` (`grant_application_id`,`version_number`),
  ADD KEY `idx_gpv_application` (`grant_application_id`),
  ADD KEY `idx_gpv_submitted` (`submitted_at`);

--
-- Indexes for table `crad_manuscript_evaluations`
--
ALTER TABLE `crad_manuscript_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_meval_submission` (`submission_id`),
  ADD KEY `idx_meval_group` (`research_group_id`);

--
-- Indexes for table `crad_manuscript_submissions`
--
ALTER TABLE `crad_manuscript_submissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_manuscript_version` (`research_group_id`,`version_number`),
  ADD UNIQUE KEY `uniq_manuscript_token` (`submission_token`),
  ADD KEY `idx_manuscript_status` (`status`),
  ADD KEY `idx_manuscript_group` (`research_group_id`);

--
-- Indexes for table `crad_panel_assignment_notifications`
--
ALTER TABLE `crad_panel_assignment_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_panel_assignment_notification` (`event_key`),
  ADD KEY `idx_panel_notification_recipient` (`recipient_user_id`,`recipient_role`,`recipient_email`),
  ADD KEY `idx_panel_notification_created` (`created_at`);

--
-- Indexes for table `crad_panel_member_availability`
--
ALTER TABLE `crad_panel_member_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_panel_availability_user` (`panel_user_id`),
  ADD KEY `idx_panel_availability_status` (`availability_status`);

--
-- Indexes for table `crad_preoral_defense_evaluations`
--
ALTER TABLE `crad_preoral_defense_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_preoral_panel_submission` (`defense_schedule_id`,`panel_user_id`),
  ADD KEY `idx_preoral_group` (`research_group_id`),
  ADD KEY `idx_preoral_panel` (`panel_user_id`),
  ADD KEY `idx_preoral_status` (`status`);

--
-- Indexes for table `crad_proposal_documents`
--
ALTER TABLE `crad_proposal_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pd_proposal` (`proposal_id`);

--
-- Indexes for table `crad_proposal_drafts`
--
ALTER TABLE `crad_proposal_drafts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_proposal_draft_student_type` (`student_id`,`form_type`);

--
-- Indexes for table `crad_proposal_members`
--
ALTER TABLE `crad_proposal_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_proposal` (`proposal_id`);

--
-- Indexes for table `crad_proposal_status_logs`
--
ALTER TABLE `crad_proposal_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_psl_proposal` (`proposal_id`);

--
-- Indexes for table `crad_publications`
--
ALTER TABLE `crad_publications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pub_group` (`research_group_id`),
  ADD KEY `idx_pub_status` (`status`);

--
-- Indexes for table `crad_research_adviser_assignments`
--
ALTER TABLE `crad_research_adviser_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_raa_adviser_identity` (`adviser_email`,`adviser_name`),
  ADD KEY `idx_raa_group` (`research_group_id`),
  ADD KEY `idx_raa_proposal` (`proposal_id`),
  ADD KEY `idx_raa_group_number` (`group_number`),
  ADD KEY `idx_raa_status` (`assignment_status`),
  ADD KEY `idx_raa_user` (`adviser_user_id`);

--
-- Indexes for table `crad_research_coordinator_assignments`
--
ALTER TABLE `crad_research_coordinator_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rca_group_number` (`group_number`),
  ADD UNIQUE KEY `uniq_rca_group_coordinator` (`research_group_id`,`coordinator_user_id`),
  ADD KEY `idx_rca_group` (`research_group_id`),
  ADD KEY `idx_rca_title_approval` (`title_approval_id`),
  ADD KEY `idx_rca_status` (`status`);

--
-- Indexes for table `crad_research_defense_schedules`
--
ALTER TABLE `crad_research_defense_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rds_proposal_number` (`proposal_number`),
  ADD KEY `idx_rds_status` (`status`),
  ADD KEY `idx_rds_proposal_id` (`proposal_id`),
  ADD KEY `idx_rds_venue_time` (`venue_id`,`defense_datetime`,`defense_end_datetime`),
  ADD KEY `idx_rds_group_time` (`research_group_id`,`defense_datetime`,`defense_end_datetime`),
  ADD KEY `idx_rds_group_number` (`group_number`);

--
-- Indexes for table `crad_research_groups`
--
ALTER TABLE `crad_research_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_number` (`group_number`),
  ADD UNIQUE KEY `proposal_id` (`proposal_id`),
  ADD UNIQUE KEY `title_approval_id` (`title_approval_id`),
  ADD KEY `idx_rg_proposal_number` (`proposal_number`);

--
-- Indexes for table `crad_research_milestones`
--
ALTER TABLE `crad_research_milestones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rm_plan_name` (`research_plan_id`,`milestone_name`),
  ADD KEY `idx_rm_plan` (`research_plan_id`),
  ADD KEY `idx_rm_status` (`status`),
  ADD KEY `idx_rm_sequence` (`research_plan_id`,`milestone_order`);

--
-- Indexes for table `crad_research_panel_assignments`
--
ALTER TABLE `crad_research_panel_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_panel_assignment_phase` (`research_group_id`,`panel_user_id`,`defense_phase`),
  ADD KEY `idx_panel_assignment_group` (`research_group_id`),
  ADD KEY `idx_panel_assignment_user` (`panel_user_id`),
  ADD KEY `idx_panel_assignment_status` (`assignment_status`),
  ADD KEY `idx_panel_assignment_schedule` (`defense_schedule_id`);

--
-- Indexes for table `crad_research_plans`
--
ALTER TABLE `crad_research_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rp_group` (`research_group_id`),
  ADD KEY `idx_rp_group_number` (`group_number`),
  ADD KEY `idx_rp_adviser` (`adviser_id`),
  ADD KEY `idx_rp_status` (`status`);

--
-- Indexes for table `crad_research_progress_activity_logs`
--
ALTER TABLE `crad_research_progress_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpal_plan` (`research_plan_id`),
  ADD KEY `idx_rpal_user` (`user_id`),
  ADD KEY `idx_rpal_action` (`action`),
  ADD KEY `idx_rpal_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_rpal_created` (`created_at`);

--
-- Indexes for table `crad_research_progress_attachments`
--
ALTER TABLE `crad_research_progress_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpa_update` (`progress_update_id`),
  ADD KEY `idx_rpa_uploaded` (`uploaded_by`);

--
-- Indexes for table `crad_research_progress_feedback`
--
ALTER TABLE `crad_research_progress_feedback`
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
-- Indexes for table `crad_research_progress_notifications`
--
ALTER TABLE `crad_research_progress_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpn_recipient_user` (`recipient_user_id`),
  ADD KEY `idx_rpn_recipient_email` (`recipient_email`),
  ADD KEY `idx_rpn_recipient_role` (`recipient_role`),
  ADD KEY `idx_rpn_batch_key` (`batch_key`),
  ADD KEY `idx_rpn_status` (`status`),
  ADD KEY `idx_rpn_created` (`created_at`);

--
-- Indexes for table `crad_research_progress_updates`
--
ALTER TABLE `crad_research_progress_updates`
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
-- Indexes for table `crad_research_proposals`
--
ALTER TABLE `crad_research_proposals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ref_code` (`ref_code`),
  ADD UNIQUE KEY `proposal_number` (`proposal_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dept` (`college_department`(50)),
  ADD KEY `idx_submitted` (`date_submitted`);

--
-- Indexes for table `crad_research_revision_cycles`
--
ALTER TABLE `crad_research_revision_cycles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rrc_schedule` (`defense_schedule_id`),
  ADD KEY `idx_rrc_group` (`research_group_id`),
  ADD KEY `idx_rrc_status` (`revision_status`);

--
-- Indexes for table `crad_research_venues`
--
ALTER TABLE `crad_research_venues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_research_venue_name` (`venue_name`),
  ADD KEY `idx_research_venues_status` (`status`);

--
-- Indexes for table `crad_title_approvals`
--
ALTER TABLE `crad_title_approvals`
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
-- AUTO_INCREMENT for table `crad_chapter_evaluations`
--
ALTER TABLE `crad_chapter_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `crad_chapter_evaluation_notifications`
--
ALTER TABLE `crad_chapter_evaluation_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `crad_chapter_submissions`
--
ALTER TABLE `crad_chapter_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `crad_chapter_submission_history`
--
ALTER TABLE `crad_chapter_submission_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `crad_final_defense_evaluations`
--
ALTER TABLE `crad_final_defense_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `crad_final_defense_recommendations`
--
ALTER TABLE `crad_final_defense_recommendations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `crad_final_manuscript_approvals`
--
ALTER TABLE `crad_final_manuscript_approvals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `crad_grant_applications`
--
ALTER TABLE `crad_grant_applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `crad_grant_opportunities`
--
ALTER TABLE `crad_grant_opportunities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `crad_grant_proposal_approval_steps`
--
ALTER TABLE `crad_grant_proposal_approval_steps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `crad_grant_proposal_approval_workflows`
--
ALTER TABLE `crad_grant_proposal_approval_workflows`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `crad_grant_proposal_evaluations`
--
ALTER TABLE `crad_grant_proposal_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `crad_grant_proposal_notifications`
--
ALTER TABLE `crad_grant_proposal_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `crad_grant_proposal_versions`
--
ALTER TABLE `crad_grant_proposal_versions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `crad_manuscript_evaluations`
--
ALTER TABLE `crad_manuscript_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `crad_manuscript_submissions`
--
ALTER TABLE `crad_manuscript_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `crad_panel_assignment_notifications`
--
ALTER TABLE `crad_panel_assignment_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `crad_panel_member_availability`
--
ALTER TABLE `crad_panel_member_availability`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `crad_preoral_defense_evaluations`
--
ALTER TABLE `crad_preoral_defense_evaluations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `crad_proposal_documents`
--
ALTER TABLE `crad_proposal_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=204;

--
-- AUTO_INCREMENT for table `crad_proposal_drafts`
--
ALTER TABLE `crad_proposal_drafts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `crad_proposal_members`
--
ALTER TABLE `crad_proposal_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `crad_proposal_status_logs`
--
ALTER TABLE `crad_proposal_status_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `crad_publications`
--
ALTER TABLE `crad_publications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `crad_research_adviser_assignments`
--
ALTER TABLE `crad_research_adviser_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `crad_research_coordinator_assignments`
--
ALTER TABLE `crad_research_coordinator_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `crad_research_defense_schedules`
--
ALTER TABLE `crad_research_defense_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `crad_research_groups`
--
ALTER TABLE `crad_research_groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `crad_research_milestones`
--
ALTER TABLE `crad_research_milestones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=217;

--
-- AUTO_INCREMENT for table `crad_research_panel_assignments`
--
ALTER TABLE `crad_research_panel_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `crad_research_plans`
--
ALTER TABLE `crad_research_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `crad_research_progress_activity_logs`
--
ALTER TABLE `crad_research_progress_activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `crad_research_progress_attachments`
--
ALTER TABLE `crad_research_progress_attachments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `crad_research_progress_feedback`
--
ALTER TABLE `crad_research_progress_feedback`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `crad_research_progress_notifications`
--
ALTER TABLE `crad_research_progress_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `crad_research_progress_updates`
--
ALTER TABLE `crad_research_progress_updates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `crad_research_proposals`
--
ALTER TABLE `crad_research_proposals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `crad_research_revision_cycles`
--
ALTER TABLE `crad_research_revision_cycles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `crad_research_venues`
--
ALTER TABLE `crad_research_venues`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13772;

--
-- AUTO_INCREMENT for table `crad_title_approvals`
--
ALTER TABLE `crad_title_approvals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `crad_proposal_documents`
--
ALTER TABLE `crad_proposal_documents`
  ADD CONSTRAINT `fk_pd_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `crad_research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crad_proposal_members`
--
ALTER TABLE `crad_proposal_members`
  ADD CONSTRAINT `fk_pm_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `crad_research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crad_proposal_status_logs`
--
ALTER TABLE `crad_proposal_status_logs`
  ADD CONSTRAINT `fk_psl_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `crad_research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crad_research_adviser_assignments`
--
ALTER TABLE `crad_research_adviser_assignments`
  ADD CONSTRAINT `fk_raa_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `crad_research_proposals` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `crad_research_coordinator_assignments`
--
ALTER TABLE `crad_research_coordinator_assignments`
  ADD CONSTRAINT `fk_rca_title_approval` FOREIGN KEY (`title_approval_id`) REFERENCES `crad_title_approvals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crad_research_defense_schedules`
--
ALTER TABLE `crad_research_defense_schedules`
  ADD CONSTRAINT `fk_rds_proposal` FOREIGN KEY (`proposal_id`) REFERENCES `crad_research_proposals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crad_research_groups`
--
ALTER TABLE `crad_research_groups`
  ADD CONSTRAINT `fk_rg_title_approval` FOREIGN KEY (`title_approval_id`) REFERENCES `crad_title_approvals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crad_research_milestones`
--
ALTER TABLE `crad_research_milestones`
  ADD CONSTRAINT `fk_rm_research_plan` FOREIGN KEY (`research_plan_id`) REFERENCES `crad_research_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crad_research_plans`
--
ALTER TABLE `crad_research_plans`
  ADD CONSTRAINT `fk_rp_research_group` FOREIGN KEY (`research_group_id`) REFERENCES `crad_research_groups` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `crad_research_progress_activity_logs`
--
ALTER TABLE `crad_research_progress_activity_logs`
  ADD CONSTRAINT `fk_rpal_research_plan` FOREIGN KEY (`research_plan_id`) REFERENCES `crad_research_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crad_research_progress_attachments`
--
ALTER TABLE `crad_research_progress_attachments`
  ADD CONSTRAINT `fk_rpa_progress_update` FOREIGN KEY (`progress_update_id`) REFERENCES `crad_research_progress_updates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crad_research_progress_feedback`
--
ALTER TABLE `crad_research_progress_feedback`
  ADD CONSTRAINT `fk_rpf_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `crad_research_milestones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpf_progress_update` FOREIGN KEY (`progress_update_id`) REFERENCES `crad_research_progress_updates` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpf_research_plan` FOREIGN KEY (`research_plan_id`) REFERENCES `crad_research_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `crad_research_progress_updates`
--
ALTER TABLE `crad_research_progress_updates`
  ADD CONSTRAINT `fk_rpu_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `crad_research_milestones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rpu_research_plan` FOREIGN KEY (`research_plan_id`) REFERENCES `crad_research_plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
