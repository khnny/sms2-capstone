-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 21, 2026 at 12:34 PM
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
-- Database: `sms2_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `sms2_activity_logs`
--

CREATE TABLE `sms2_activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `role_key` varchar(40) DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `module_key` varchar(60) DEFAULT NULL,
  `detail` varchar(500) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_action` (`action`),
  KEY `idx_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms2_admin_announcements`
--

CREATE TABLE `sms2_admin_announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `body` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','unpublished','archived') NOT NULL DEFAULT 'draft',
  `audience` varchar(40) NOT NULL DEFAULT 'all',
  `category` varchar(40) NOT NULL DEFAULT 'General',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_by_name` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `published_at` datetime DEFAULT NULL,
  `scheduled_for` date DEFAULT NULL,
  `expires_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms2_login_throttles`
--

CREATE TABLE `sms2_login_throttles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `throttle_key` char(64) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_login_throttle_key` (`throttle_key`),
  KEY `idx_login_throttle_ip` (`ip_address`),
  KEY `idx_login_throttle_locked` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms2_password_resets`
--

CREATE TABLE `sms2_password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms2_password_resets`
--

INSERT INTO `sms2_password_resets` (`id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_ip`, `created_at`) VALUES
(2, 1, '550d259303762ee9ce8b5378b3b6b1e212a4b5cf796b005404689bb4c5596866', '2026-08-06 14:05:55', '2026-08-06 13:06:28', '::1', '2026-08-06 13:05:55'),
(3, 9, '691edab739335bc353c7ecaa7d183393ea51e47def723d4f3e68adccdd10fcb9', '2026-08-06 14:18:23', '2026-08-06 13:18:33', '::1', '2026-08-06 13:18:23'),
(4, 9, '55eabae148518a30c44e17552b678572afdda8d79fc2c59f95152780545da52b', '2026-08-06 14:18:33', NULL, '::1', '2026-08-06 13:18:33'),
(5, 1598, '76aa8b71c4cf80db296a44c0cb1fd6e32d7bf202b5e9d8ff343d65b915b61fea', '2026-09-20 23:10:00', '2026-09-20 22:12:05', '::1', '2026-09-20 22:10:00'),
(6, 1598, '24b67e1249a2de27e3a0d29b5796b4ee4b71f5e66e274d94917f07e64c0cfe82', '2026-09-20 23:12:05', '2026-09-20 22:13:33', '::1', '2026-09-20 22:12:05'),
(7, 1598, 'd01e65d1afce7a7c79b4a884dbeba9b240ae4c02255297ee0bfa8ba8a9e69e8f', '2026-09-20 23:13:33', '2026-09-20 23:36:35', '::1', '2026-09-20 22:13:33'),
(8, 1598, 'e2133532d56fd6d2686f992bb96370f45af74b8382b2ce80d21fe1f38a861303', '2026-09-21 00:36:35', '2026-09-20 23:56:12', '::1', '2026-09-20 23:36:35'),
(9, 1598, '4342674130566ecb2408575b9223e015fdf7442d2be2cb2aec72788a044bfd1f', '2026-09-21 00:56:12', '2026-09-21 00:02:02', '::1', '2026-09-20 23:56:12'),
(10, 1598, 'e45930b9b41d8be9c07d0bcf54360acc5d97c853908582587b8bae30c6b0f5a1', '2026-09-21 01:02:02', '2026-09-21 00:02:39', '::1', '2026-09-21 00:02:02'),
(11, 1598, 'd4a9c0fd9b262c25b7c5530c7f3b652d32eea94b980947306d08752fa3aa837c', '2026-09-21 01:02:39', NULL, '::1', '2026-09-21 00:02:39'),
(12, 1354, 'f62dc31004f6594f3e43e6b81ff2d17d7a235c6c8de8c9d7c4763a7b5c64c807', '2026-09-21 01:08:51', '2026-09-21 00:11:42', '::1', '2026-09-21 00:08:51'),
(13, 1354, '6863d2663b7d31e1a97dd63e7b678d13e13ee6e3cd3891fdbfde5b93db70817b', '2026-09-21 01:11:42', NULL, '::1', '2026-09-21 00:11:42');

-- --------------------------------------------------------

--
-- Table structure for table `sms2_password_reset_requests`
--

CREATE TABLE `sms2_password_reset_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `module_key` varchar(60) NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `requested_password_hash` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `admin_note` varchar(500) DEFAULT NULL,
  `temp_password_set` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms2_password_reset_requests`
--

INSERT INTO `sms2_password_reset_requests` (`id`, `user_id`, `module_key`, `reason`, `requested_password_hash`, `status`, `admin_id`, `admin_note`, `temp_password_set`, `created_at`, `resolved_at`) VALUES
(1, 1598, 'student_portal', 'lost details', NULL, 'approved', 1, NULL, 0, '2026-09-20 22:30:13', '2026-09-20 22:31:50');

-- --------------------------------------------------------

--
-- Table structure for table `sms2_roles`
--

CREATE TABLE `sms2_roles` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `label` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms2_roles`
--

INSERT INTO `sms2_roles` (`id`, `role_key`, `label`, `description`, `is_system`, `created_at`) VALUES
(1, 'admin', 'Super Admin', 'Legacy super admin access', 1, '2026-07-22 22:24:44'),
(2, 'registrar', 'Registrar', 'Enrollment, records, scheduling', 1, '2026-07-22 22:24:44'),
(3, 'finance', 'Finance', 'Payments and receivables', 1, '2026-07-22 22:24:44'),
(4, 'hr', 'Dean', 'Dean and faculty processes', 1, '2026-07-22 22:24:44'),
(5, 'it_office', 'IT Office', 'LMS and IT modules', 1, '2026-07-22 22:24:44'),
(6, 'osa', 'OSA', 'Student affairs / co-curricular', 1, '2026-07-22 22:24:44'),
(7, 'qa', 'QA Office', 'Accreditation and quality', 1, '2026-07-22 22:24:44'),
(8, 'crad_officer', 'CRAD Officer', 'Research and development', 1, '2026-07-22 22:24:44'),
(9, 'student', 'Student', 'Student portal only', 1, '2026-07-22 22:24:44'),
(10, 'superadmin', 'Super Admin', 'Full system access', 1, '2026-08-08 17:25:19'),
(11, 'admission', 'Admission', 'Admission office access', 1, '2026-08-08 17:25:19'),
(56, 'research_coordinator', 'Research Coordinator', 'Research coordination access', 1, '2026-08-08 18:13:51'),
(102, 'adviser', 'Adviser', 'Research adviser faculty account', 1, '2026-08-08 21:35:14'),
(213, 'research_director', 'Research Director', 'Research defense scheduling director account', 1, '2026-08-09 19:31:14'),
(384, 'research_grant', 'CRAD Officer', 'Research grant management access', 1, '2026-08-10 20:01:49'),
(770, 'grammarian', 'Grammarian', 'Research grammar and manuscript evaluation account', 1, '2026-08-14 11:06:49'),
(788, 'panel', 'Panel Member', 'Research defense panel account', 1, '2026-08-15 17:07:16'),
(800, 'sms_admin', 'Admin', 'General administrator account', 1, '2026-08-18 00:38:50'),
(901, 'review_committee', 'Review Committee', 'Grant proposal review and rubric evaluation', 1, '2026-08-31 07:04:35'),
(1206, 'department_chair', 'Department Chair', 'Grant approval — department chair sign-off', 1, '2026-08-31 10:29:00'),
(1207, 'research_office', 'Research Office', 'Grant approval — research office sign-off', 1, '2026-08-31 10:29:00'),
(1208, 'vpaa', 'VPAA', 'Grant approval — VPAA sign-off', 1, '2026-08-31 10:29:00'),
(1686, 'department_head', 'Department Head', 'Adviser and panel assignment', 1, '2026-09-19 01:01:39');

-- --------------------------------------------------------

--
-- Table structure for table `sms2_role_permissions`
--

CREATE TABLE `sms2_role_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `module_key` varchar(60) NOT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms2_role_permissions`
--

INSERT INTO `sms2_role_permissions` (`id`, `role_key`, `module_key`, `granted`, `updated_at`) VALUES
(1156, 'superadmin', 'user-management', 1, '2026-08-31 10:29:00'),
(1157, 'admission', 'enrollment', 1, '2026-08-31 10:29:00'),
(1158, 'registrar', 'registrar', 1, '2026-08-31 10:29:00'),
(1159, 'registrar', 'curriculum', 1, '2026-08-31 10:29:00'),
(1160, 'registrar', 'scheduling', 1, '2026-08-31 10:29:00'),
(1161, 'crad_officer', 'crad', 1, '2026-08-31 10:29:00'),
(1162, 'research_coordinator', 'crad', 1, '2026-08-31 10:29:00'),
(1163, 'department_chair', 'crad', 1, '2026-08-31 10:29:00'),
(1164, 'research_office', 'crad', 1, '2026-08-31 10:29:00'),
(1165, 'research_director', 'faculty', 1, '2026-08-31 10:29:00'),
(1166, 'grammarian', 'faculty', 1, '2026-08-31 10:29:00'),
(1167, 'review_committee', 'crad_grant', 1, '2026-08-31 10:29:00'),
(1168, 'panel', 'faculty', 1, '2026-08-31 10:29:00'),
(1169, 'finance', 'payment', 1, '2026-08-31 10:29:00'),
(1170, 'osa', 'cocurricular', 1, '2026-08-31 10:29:00'),
(1171, 'it_office', 'lms', 1, '2026-08-31 10:29:00'),
(1172, 'qa', 'accreditation', 1, '2026-08-31 10:29:00'),
(1173, 'vpaa', 'accreditation', 1, '2026-08-31 10:29:00'),
(1174, 'hr', 'faculty', 1, '2026-08-31 10:29:00'),
(1175, 'student', 'student_portal', 1, '2026-08-31 10:29:00'),
(1179, 'sms_admin', 'enrollment', 1, '2026-09-18 22:08:12'),
(1180, 'sms_admin', 'registrar', 1, '2026-09-18 22:08:12'),
(1181, 'sms_admin', 'curriculum', 1, '2026-09-18 22:08:12'),
(1182, 'sms_admin', 'accreditation', 1, '2026-09-18 22:08:12'),
(1183, 'sms_admin', 'payment', 1, '2026-09-18 22:08:12'),
(1184, 'sms_admin', 'faculty', 1, '2026-09-18 22:08:12'),
(1185, 'sms_admin', 'scheduling', 1, '2026-09-18 22:08:12'),
(1186, 'sms_admin', 'cocurricular', 1, '2026-09-18 22:08:12'),
(1187, 'sms_admin', 'lms', 1, '2026-09-18 22:08:12'),
(1188, 'sms_admin', 'crad', 1, '2026-09-18 22:08:12'),
(1189, 'adviser', 'faculty', 1, '2026-08-31 10:29:24'),
(1193, 'research_grant', 'crad_grant', 1, '2026-08-31 10:29:24'),
(1945, 'department_head', 'crad', 1, '2026-09-19 01:01:39');

-- --------------------------------------------------------

--
-- Table structure for table `sms2_security_otps`
--

CREATE TABLE `sms2_security_otps` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `purpose` varchar(40) NOT NULL,
  `code_hash` char(64) NOT NULL,
  `module_key` varchar(60) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_otp_user` (`user_id`),
  KEY `idx_otp_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms2_security_otps`
--

INSERT INTO `sms2_security_otps` (`id`, `user_id`, `purpose`, `code_hash`, `module_key`, `expires_at`, `used_at`, `created_at`) VALUES
(1, 3, 'auth_setup', '00aa177502733dd1e947e9addf22e1e33ff0a061d3af840955c53e19f129d130', NULL, '2026-07-23 12:32:33', NULL, '2026-07-23 12:22:33'),
(2, 10, 'auth_setup', '434b2a7ce1742c5901ad141e3fd48d88a160776362d41a87fe120c61735dca25', NULL, '2026-07-23 12:41:18', '2026-07-23 12:31:35', '2026-07-23 12:31:18'),
(3, 1, 'login_2fa', 'fe8a2e43fdc5dfd231e4a5365fb4b97accb2d492d0618b8cb1239f2003384197', 'System', '2026-08-06 12:18:17', '2026-08-07 13:47:43', '2026-08-06 12:08:17'),
(4, 1, 'login_2fa', 'e5533c6584ffce50ff4b8a86ab22a27b4aec09511ca75cc45ae10f872e3b395f', 'System', '2026-08-06 12:35:41', '2026-08-07 13:47:43', '2026-08-06 12:25:41'),
(5, 1, 'login_2fa', '42b95c3f3a5bc304110b877053c1d5165d141cb2dd8aa4640078b64f9cd3f55a', 'System', '2026-08-06 12:37:17', '2026-08-07 13:47:43', '2026-08-06 12:27:17'),
(6, 1, 'login_2fa', 'b121a650c927ab1c64dbe5dcd7b1ee6cad3e31559cc17a9498e845353b39543c', 'System', '2026-08-06 12:49:29', '2026-08-07 13:47:43', '2026-08-06 12:39:29'),
(7, 1, 'login_2fa', 'd8a6f5265b58db60841f94a1910e75b34d95e7d484a4d684ba76c8a376ae8b74', 'System', '2026-08-06 12:51:04', '2026-08-07 13:47:43', '2026-08-06 12:41:04'),
(8, 1, 'login_2fa', '464da7d02deaa950b60fba29e5dd949ddb3d4d2108cc4d07dfd084fa472e4c9f', 'System', '2026-08-06 12:54:49', '2026-08-07 13:47:43', '2026-08-06 12:44:49'),
(9, 1, 'login_2fa', '52f02070f7f89d4a26b2de369b5d3f25b1d5ad68edca4505583d7dfae7629fe4', 'System', '2026-08-06 12:57:18', '2026-08-07 13:47:43', '2026-08-06 12:47:18'),
(10, 1, 'login_2fa', 'b901c750520b0d84eccbd2e6c98d5090d087acf2e44adff08fd38976d0db9412', 'System', '2026-08-06 13:19:23', '2026-08-07 13:47:43', '2026-08-06 13:09:23'),
(11, 1, 'password_change', 'a2b739a763e75c0377332f0cf70bc4d4b1fa1e6c777d960a592a3a9a072a3d40', 'admin-account', '2026-08-06 13:20:17', '2026-08-07 13:47:43', '2026-08-06 13:10:17'),
(12, 1, 'login_2fa', '96fb4145442779cd5534a2c540a7e9c6af8ec7d5d65d44f3a362bd466f75ea1f', 'System', '2026-08-06 13:23:25', '2026-08-07 13:47:43', '2026-08-06 13:13:25'),
(13, 1, 'login_2fa', '1a06a98bfb9fd1053e961bd6756f21edf14accb3eec42542a172d8ad2ae0aa66', 'System', '2026-08-06 13:25:26', '2026-08-07 13:47:43', '2026-08-06 13:15:26'),
(14, 1, 'login_2fa', '98e0b51ec04c9d63f34871bfe2e7a6b7284c174c2cdb537b35a4532978a1cff0', 'System', '2026-08-06 13:30:15', '2026-08-07 13:47:43', '2026-08-06 13:20:15'),
(15, 1, 'login_2fa', '6c828c6267e0b07f37a35b2000fc08831c60cece48c87fce4a60aabd2bbf634e', 'System', '2026-08-06 13:35:35', '2026-08-07 13:47:43', '2026-08-06 13:25:35'),
(16, 1, 'login_2fa', '78b7b05beda2d061eafc5c3dc98d62ab33427d54ccad3efbcc93c5686f68379c', 'System', '2026-08-06 13:41:19', '2026-08-07 13:47:43', '2026-08-06 13:31:19'),
(17, 3, 'login_2fa', 'f0bda89589cd1f9af75f462a57bbdf5d5baf94be8e6553c731c314ca8eea028d', 'System', '2026-08-06 13:42:02', '2026-08-07 13:47:43', '2026-08-06 13:32:02'),
(18, 3, 'login_2fa', '38acad02af807fed2131bc29cba07e19e1df2ab2cb5d0dd6324bb02f13a55556', 'System', '2026-08-06 13:51:38', '2026-08-07 13:47:43', '2026-08-06 13:41:38'),
(19, 1, 'login_2fa', 'cdc036ae8ca397a128794e366b26d21e3e8ec44b03aee9f90735aca07f1ad5a6', 'System', '2026-08-06 14:05:22', '2026-08-07 13:47:43', '2026-08-06 13:55:22'),
(20, 1, 'login_2fa', 'edbde5b085641bb56b53105fa228487e2f7d0c2e1a230f920ddbebe2f2c72e14', 'System', '2026-08-06 14:12:17', '2026-08-07 13:47:43', '2026-08-06 14:02:17'),
(21, 1, 'login_2fa', '59e1d8a04bbdb577a5127fa8e4b51d51f57aa2daad911e88e97b6c843272f28d', 'System', '2026-08-06 14:16:28', '2026-08-07 13:47:43', '2026-08-06 14:06:28'),
(22, 3, 'login_2fa', 'e6c1d8b78e999e9bc4f1b633fa0a584669ec5984759d4adabf59745f49bc503e', 'System', '2026-08-06 14:53:44', '2026-08-07 13:47:43', '2026-08-06 14:43:44'),
(23, 1, 'login_2fa', '264797c49afc4ad46350f996be7c1894b7e19828bdb52083f16a24301e3edb82', 'System', '2026-08-06 16:02:00', '2026-08-07 13:47:43', '2026-08-06 15:52:00'),
(24, 3, 'login_2fa', '5c7435d07f6f8c48816e5d6c8522b3add82f6cce0e3284bb92589998761e4583', 'System', '2026-08-06 16:02:53', '2026-08-07 13:47:43', '2026-08-06 15:52:53'),
(25, 3, 'login_2fa', 'a880ff676aac443c05712a3771640b6275a86282c34ba45a2d1075c757b86991', 'System', '2026-08-06 16:45:53', '2026-08-07 13:47:43', '2026-08-06 16:35:53'),
(26, 1, 'login_2fa', 'cd26d8606d44d05c0e9116c5d9b445655a9132310ad7bda5983a969e9ea9f26d', 'System', '2026-08-06 20:13:26', '2026-08-07 13:47:43', '2026-08-06 20:03:26'),
(27, 1, 'login_2fa', '967cb927a3559c93bbbe1d503405f92049cfeaeedcb2fd11c3ec9eee0c4d2f6e', 'System', '2026-08-06 20:31:15', '2026-08-07 13:47:43', '2026-08-06 20:21:15'),
(28, 3, 'login_2fa', '365e076e8bb97bae37a97870e02200186d2776cdced25150c0d37cc086eb8a5b', 'System', '2026-08-06 20:35:28', '2026-08-07 13:47:43', '2026-08-06 20:25:28'),
(29, 1, 'login_2fa', 'ad13979ae9e79127941523c4bbb960dcee573c5765e05db86dbd1bdf67527e62', 'System', '2026-08-07 11:54:29', '2026-08-07 13:47:43', '2026-08-07 11:44:29'),
(30, 3, 'login_2fa', '8c4b0774bc5f82a69bba1cde2b2965d76c7e7db49b4c2a7a4c7415b60bf784cf', 'System', '2026-08-07 11:55:03', '2026-08-07 13:47:43', '2026-08-07 11:45:03'),
(31, 1, 'login_2fa', '90858f8d89d06736dd2f40b75ff8eb3a998e0c93fcbd8e9933f9e73630d7127f', 'System', '2026-08-07 13:57:27', '2026-08-07 13:47:43', '2026-08-07 13:47:27'),
(32, 1, 'passkey_remove', 'df8c90265057ad814bde1ac13e69cd5d1aa480375cd8a00f9e9b4a29e917b7fe', NULL, '2026-08-07 14:33:29', NULL, '2026-08-07 14:23:29'),
(33, 1598, 'auth_setup', '3a33bc749930d47eaddc966845e47ba858999f823194644d34c960ee30d7949e', NULL, '2026-09-20 22:24:42', NULL, '2026-09-20 22:14:42'),
(34, 990, 'login_2fa', 'f2d1fe1efaa6ec1695f737c8379831f6de7e1fd9bc6aee75c4890ed0c7ab3811', 'System', '2026-09-20 22:27:31', NULL, '2026-09-20 22:17:31'),
(35, 1354, 'forgot_password', '1a5fa25ed0adabc030b602d0f921b9ce8f4d350b8fba6381cbee96f7bc5ca373', 'login-forgot', '2026-09-21 00:18:39', '2026-09-21 00:26:43', '2026-09-21 00:16:39'),
(36, 1354, 'forgot_password', '8b45b16bb853409ac86cc3cca6a933ceafeb608bb7a31c19a723f5ad5bd594bd', 'login-forgot', '2026-09-21 00:28:43', '2026-09-21 00:31:15', '2026-09-21 00:26:43'),
(37, 1354, 'forgot_password', '6d20a1134df48b669a3446d654537785a481d178b82bfbb36e1ee92c0dbe8a44', 'login-forgot', '2026-09-21 00:33:15', '2026-09-21 00:33:23', '2026-09-21 00:31:15'),
(38, 1354, 'forgot_password', 'dfb4b4fd2aad5e510728bf4c794cc9ff4ce84ec51e2d29a96f1ba3d8c5c6eac6', 'login-forgot', '2026-09-21 00:35:23', '2026-09-21 00:34:09', '2026-09-21 00:33:23'),
(39, 1354, 'forgot_password', '37d4c8664ab63d82e6fd5ef93b23f1f2fd833d31ed28850ae637a4330a228397', 'login-forgot', '2026-09-21 00:36:09', '2026-09-21 00:34:32', '2026-09-21 00:34:09'),
(40, 1354, 'forgot_password', '19e05e49501875540ed47100e2c58275f57af67bab37e15b1c560d927f71710b', 'login-forgot', '2026-09-21 00:36:32', '2026-09-21 00:34:45', '2026-09-21 00:34:32');

-- --------------------------------------------------------

--
-- Table structure for table `sms2_student_profiles`
--

CREATE TABLE `sms2_student_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(40) NOT NULL,
  `program` varchar(200) NOT NULL DEFAULT 'Bachelor of Science in Information Technology',
  `year_level` varchar(40) NOT NULL DEFAULT '4th Year',
  `section` varchar(40) NOT NULL DEFAULT 'BSIT 4A',
  `semester` varchar(40) NOT NULL DEFAULT '1st Semester',
  `school_year` varchar(20) NOT NULL DEFAULT '2026-2027',
  `enrollment_status` varchar(40) NOT NULL DEFAULT 'Enrolled',
  `standing` varchar(40) NOT NULL DEFAULT 'Good Standing',
  `mobile` varchar(40) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `guardian` varchar(150) DEFAULT NULL,
  `guardian_contact` varchar(40) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms2_student_profiles`
--

INSERT INTO `sms2_student_profiles` (`id`, `user_id`, `student_id`, `program`, `year_level`, `section`, `semester`, `school_year`, `enrollment_status`, `standing`, `mobile`, `address`, `guardian`, `guardian_contact`, `created_at`, `updated_at`) VALUES
(1, 9, 'S230000001', 'Bachelor of Science in Information Technology', '4th Year', 'BSIT 4B', '1st Semester', '2026-2027', 'Enrolled', 'Good Standing', '0917 000 0011', 'Fairview, Quezon City', 'Juan Dela Cruz', '0918 000 0012', '2026-09-19 00:29:23', '2026-09-19 00:29:23'),
(2, 1354, 'S230106713', 'Bachelor of Science in Information Technology', '4th Year', 'BSIT 4A', '1st Semester', '2026-2027', 'Enrolled', 'Good Standing', '0917 000 0001', 'Novaliches, Quezon City', 'Maria Dela Cruz', '0918 000 0002', '2026-09-19 00:29:23', '2026-09-19 00:29:23'),
(3, 1558, 'S240115700', 'Bachelor of Science in Information Technology', '4th Year', 'BSIT 4A', '1st Semester', '2026-2027', 'Enrolled', 'Good Standing', '', '', '', '', '2026-09-20 21:13:12', '2026-09-20 21:13:12'),
(4, 1564, 'S230106714', 'Bachelor of Science in Information Technology', '4th Year', 'BSIT 4A', '1st Semester', '2026-2027', 'Enrolled', 'Good Standing', '', '', '', '', '2026-09-20 21:13:55', '2026-09-20 21:13:55');

-- --------------------------------------------------------

--
-- Table structure for table `sms2_system_settings`
--

CREATE TABLE `sms2_system_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms2_system_settings`
--

INSERT INTO `sms2_system_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('crad_active_term', '', '2026-08-28 07:17:54'),
('csrf_enabled', '1', '2026-07-22 22:24:44'),
('lockout_minutes', '1', '2026-07-23 08:05:06'),
('lockout_seconds', '15', '2026-07-23 08:05:06'),
('lockout_unit', 'seconds', '2026-07-23 08:05:06'),
('lockout_value', '15', '2026-07-23 08:05:06'),
('login_captcha_enabled', '1', '2026-09-20 22:39:57'),
('mail_admin_email', 'kennethabejuela0308@gmail.com', '2026-09-21 00:25:26'),
('mail_from_email', 'kennethabejuela0308@gmail.com', '2026-09-21 00:25:26'),
('mail_from_name', 'BCP', '2026-09-21 00:25:26'),
('mail_show_link_on_failure', '1', '2026-09-20 23:40:19'),
('max_failed_logins', '3', '2026-07-23 07:33:05'),
('min_password_length', '8', '2026-07-22 22:24:44'),
('module_kick_epoch_crad', '1784849304', '2026-07-23 15:28:24'),
('module_maintenance_crad', '0', '2026-07-23 15:29:22'),
('module_maintenance_msg_crad', 'The system is currently under maintenance. Some services may be temporarily unavailable.\r\n\r\nThank you for your patience and understanding.', '2026-07-23 15:05:14'),
('module_maintenance_student_portal', '0', '2026-09-20 22:35:46'),
('password_expiry_days', '0', '2026-07-22 22:24:44'),
('require_password_change_first_login', '0', '2026-07-22 22:24:44'),
('session_timeout_minutes', '30', '2026-09-21 02:07:26'),
('smtp_encryption', 'tls', '2026-07-23 10:33:25'),
('smtp_host', 'smtp.gmail.com', '2026-07-23 10:51:48'),
('smtp_password', 'sms2enc1.Oa0f+lo0nzQC028oI5zSiNa6z6C83V62Rv132wC9nACe36djJjr19CcL9Bs=', '2026-09-21 00:25:26'),
('smtp_port', '587', '2026-07-23 10:33:25'),
('smtp_username', 'kennethabejuela0308@gmail.com', '2026-09-21 00:25:26'),
('turnstile_site_key', '', '2026-09-20 22:39:57');

-- --------------------------------------------------------

--
-- Table structure for table `sms2_users`
--

CREATE TABLE `sms2_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(80) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `student_id` varchar(40) DEFAULT NULL,
  `status` enum('active','inactive','locked','suspended') NOT NULL DEFAULT 'active',
  `must_change_password` tinyint(1) NOT NULL DEFAULT 0,
  `failed_login_attempts` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_seen_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms2_users`
--

INSERT INTO `sms2_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `last_login_at`, `last_seen_at`, `last_login_ip`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', 'j14677365@gmail.com', '$2y$10$a4NaRWDw7.1Jt2ps9MNI.uodIEoJfOZGqRflwoapW9OtP8e7SKAoC', 'Super Admin', 'superadmin', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:14', '2026-09-21 01:28:42', '2026-09-21 18:13:11', '::1', NULL, '2026-07-22 22:53:59', '2026-09-21 02:13:11'),
(2, 'registrar', 'registrar@bestlink.edu.ph', '$2y$10$/HmOuAP54dAuUkNOyNJo/e2GwrAszJqpF0sQmGvjofAtM/.6tcp.m', 'Registrar', 'registrar', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:19', '2026-08-08 22:06:54', NULL, '::1', NULL, '2026-07-22 22:53:59', '2026-08-31 10:23:26'),
(3, 'cradofficer', 'cradofficer@bestlink.ph', '$2y$10$IpnqwpL9JnMUhHbSOgfxJ.4ra3ccLSYj/jBiRdE5ZcdxVliR2HA3K', 'CRAD Officer', 'crad_officer', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:14', '2026-09-21 02:02:33', NULL, '::1', 'sdada', '2026-07-22 22:53:59', '2026-09-21 02:19:10'),
(4, 'finance', 'finance@bestlink.edu.ph', '$2y$10$DRoqe4euabvGssHKV0nAoeRBxT4pkP1yVa2NIiND4LYfUJq7VKVRe', 'Finance', 'finance', NULL, 'active', 0, 0, NULL, '2026-08-31 12:10:16', '2026-09-18 03:30:16', NULL, '::1', NULL, '2026-07-22 22:54:00', '2026-09-18 03:31:00'),
(5, 'studentaffairs', 'studentaffairs@bestlink.edu.ph', '$2y$10$ykS9zsSeg8ESbJDrnyaixuRg.OYKWUljfEzgDhwBWsn4MYjdRR9O2', 'Student Affairs', 'osa', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:19', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-08-31 10:23:26'),
(6, 'itofficer', 'itofficer@bestlink.edu.ph', '$2y$10$h1GQBrr0K5SM8whZCT2QxOmvpIN2aPKslctCSX3VMflxoiHVIdWGC', 'IT Officer', 'it_office', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:19', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-08-31 10:23:26'),
(7, 'qualityassurance', 'qualityassurance@bestlink.edu.ph', '$2y$10$cqKm0cN1jMdxpdS5l3yee.ygI3KG05tRBGw5cyagSwNFT.6YaytVq', 'Quality Assurance', 'qa', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:20', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-08-31 10:23:26'),
(8, 'dean', 'dean@bestlink.edu.ph', '$2y$10$ADDDVUeDkcHKTZ90VfeuIOOaEFdFBcybnJNFT.QIMWYcEGnm9cf1m', 'Dean', 'hr', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:14', '2026-09-18 03:28:45', NULL, '::1', NULL, '2026-07-22 22:54:00', '2026-09-18 03:29:01'),
(9, 's230000001', 's230000001@bestlink.edu.ph', '$2y$10$A94r731PvDnsBaltuho/quGmMR6T6TvsBh5feQx6d6avkQILTNvW2', 'Student User', 'student', 'S230000001', 'active', 0, 0, NULL, '2026-08-31 10:56:14', '2026-09-18 03:05:26', '2026-09-18 03:36:27', '::1', NULL, '2026-07-22 22:54:00', '2026-09-18 03:36:27'),
(20, 'admission', 'admission@bestlink.edu.ph', '$2y$10$1M./oyAWOwzHhIjWoxGCWu5wm/6F/Jc3bzmYeF7hLt/jnhzg6KW9u', 'Admission', 'admission', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:19', NULL, NULL, NULL, NULL, '2026-08-08 17:25:20', '2026-08-31 10:23:26'),
(40, 'researchcoordinator', 'researchcoordinator@bestlink.edu.ph', '$2y$10$cC78.kC9YWDKQmoQ/BhG3Ou5wNb/rCaLooIiMLhwjV05e05w5nC3C', 'Mrs. Kris Guevarra', 'research_coordinator', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:14', '2026-09-21 01:18:23', NULL, '::1', NULL, '2026-08-08 18:09:48', '2026-09-21 01:18:36'),
(54, 'rsantos', 'rsantos@bestlink.edu.ph', '$2y$10$b/pnRSE8GFojLln4g5It9u946pzW57evxzWArhQso9oUxeqappwYq', 'Dr. Roberto M. Santos', 'adviser', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:14', '2026-09-21 01:46:59', NULL, '::1', NULL, '2026-08-08 21:35:14', '2026-09-21 01:54:45'),
(475, 'grammarian', 'grammarian@bestlink.edu.ph', '$2y$10$5hr6Wv2B84sMn2aCFcqB6u/36lx8QHnep5c7BJRMd3GyKhAH4xAwi', 'Kyle Kuzma', 'grammarian', NULL, 'active', 0, 0, NULL, '2026-09-19 10:03:34', '2026-09-21 01:25:11', NULL, '::1', NULL, '2026-08-14 11:06:49', '2026-09-21 01:28:06'),
(491, 'jobertvalentino', 'jobertvalentino@bestlink.edu.ph', '$2y$10$ouhKTKDlt29J1UyCrPm9r.I31sv6i4DE/r.mKPbhwK7TVtuxqS0Fm', 'Dr. Jobert Valentino', 'panel', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:13', '2026-09-21 02:15:15', NULL, '::1', NULL, '2026-08-15 17:07:16', '2026-09-21 02:15:32'),
(492, 'jonathanestrada', 'jonathanestrada@bestlink.edu.ph', '$2y$10$dQsnxqrSSWWl2gr3YgFqOuFgS4Z18E1MHOqAh51hhhL3mLFgbnAbS', 'Dr. Jonathan Estrada', 'panel', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:13', '2026-09-21 02:15:41', NULL, '::1', NULL, '2026-08-15 17:07:16', '2026-09-21 02:19:17'),
(493, 'michelleguevarra', 'michelleguevarra@bestlink.edu.ph', '$2y$10$YGhw2uzbxt6ENl46DQ8LkekrJ7Rd5o.mUy/TbNBg/bUUqFD5rfRym', 'Dr. Michelle Guevarra', 'panel', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:13', '2026-09-19 12:35:59', NULL, '::1', NULL, '2026-08-15 17:07:16', '2026-09-19 12:36:03'),
(758, 'admin', 'admin@bestlink.edu.ph', '$2y$10$MkfbK0pT7baUocnxCt9NBONcL1yxJ3SeVBmIBuIWRPRFr1CwUfQCG', 'Admin', 'sms_admin', NULL, 'active', 0, 0, NULL, '2026-09-21 01:28:58', '2026-09-21 02:13:51', NULL, '::1', NULL, '2026-08-18 00:38:50', '2026-09-21 02:14:13'),
(766, 'reviewcommittee', 'reviewcommittee@bestlink.edu.ph', '$2y$10$6iOIYjb89i9ErroqSX/u9ur7qyw2u8zausvK16nSPHkRotXFek8Ne', 'Review Committee', 'review_committee', NULL, 'active', 0, 0, NULL, '2026-09-19 01:26:53', '2026-09-19 01:31:07', NULL, '::1', NULL, '2026-08-31 07:04:36', '2026-09-19 01:31:15'),
(990, 'deptchair', 'deptchair@bestlink.edu.ph', '$2y$10$..8x.zJiNd8J7Nt.ayYpSO4n0okueUM9YcV0KErvmqbS6LcfHi4h6', 'Dr. Joseph Alcantara', 'department_chair', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:14', '2026-09-21 02:14:45', NULL, '::1', NULL, '2026-08-31 10:19:39', '2026-09-21 02:15:07'),
(991, 'researchoffice', 'researchoffice@bestlink.edu.ph', '$2y$10$Yjc6RQ6xI9hfWhDi5L6X..mXC3B3DE2r1H0xwSvqeyaQlw.tkuzVu', 'Research Office', 'research_office', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:14', '2026-09-18 03:29:13', NULL, '::1', NULL, '2026-08-31 10:19:39', '2026-09-18 03:29:38'),
(992, 'vpaa', 'vpaa@bestlink.edu.ph', '$2y$10$aBDCU/R1ICzX.oy1SOwT6.pnhhRxRKI4fXGBMM0Wm1mUOPgc/Ae5W', 'VPAA', 'vpaa', NULL, 'active', 0, 0, NULL, '2026-08-31 10:56:14', '2026-09-18 03:29:44', NULL, '::1', NULL, '2026-08-31 10:19:39', '2026-09-18 03:30:09'),
(1354, 's230106713', 'kennethabejuela0308@gmail.com', '$2y$10$hlVtDsX6ruX3NjLwkUeRA.Fel8OfC3MQ3p0Nvz93.tAu3COgbW.Cm', 'John Kenneth Abejuela', 'student', 'S230106713', 'active', 0, 0, NULL, '2026-09-18 21:37:23', '2026-09-21 01:52:30', '2026-09-21 18:33:55', '::1', NULL, '2026-09-18 21:37:23', '2026-09-21 02:33:55'),
(1420, 'depthead', 'depthead@bestlink.edu.ph', '$2y$10$YzKfWRJjdgH7RWtPzle5cest5VbF9VizUzo8O4wGGgbg.OFMvJxSu', 'Jonathan Kuminga', 'department_head', NULL, 'active', 0, 0, NULL, '2026-09-19 01:27:09', '2026-09-21 01:51:48', NULL, '::1', NULL, '2026-09-19 01:01:39', '2026-09-21 01:52:22'),
(1598, 's230106714', 'kenlangmalakas0308@gmail.com', '$2y$10$LAIyCyd4dEGPMcETU7IFxeE/fa3VYtsHLp2rLq4Gh4v8EbjK9B5XC', 'Jopel Caday', 'student', 'S230106714', 'active', 0, 1, NULL, '2026-09-20 22:31:50', '2026-09-20 23:09:19', NULL, '::1', NULL, '2026-09-20 21:15:17', '2026-09-21 00:07:29');

-- --------------------------------------------------------

--
-- Table structure for table `sms2_user_authenticators`
--

CREATE TABLE `sms2_user_authenticators` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `secret` varchar(512) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `pending_secret` varchar(512) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sms2_user_passkeys`
--

CREATE TABLE `sms2_user_passkeys` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL,
  `credential_id` varchar(1024) NOT NULL,
  `public_key` text NOT NULL,
  `sign_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `device_name` varchar(120) NOT NULL DEFAULT 'Passkey',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_passkey_cred` (`credential_id`(255)),
  KEY `idx_passkey_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--


--
-- Indexes for table `sms2_admin_announcements`
--
ALTER TABLE `sms2_admin_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ann_status_published` (`status`,`published_at`),
  ADD KEY `idx_ann_audience` (`audience`);

--
-- Indexes for table `sms2_password_resets`
--
ALTER TABLE `sms2_password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reset_user` (`user_id`),
  ADD KEY `idx_reset_token` (`token_hash`),
  ADD KEY `idx_reset_expires` (`expires_at`);

--
-- Indexes for table `sms2_password_reset_requests`
--
ALTER TABLE `sms2_password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prr_user` (`user_id`),
  ADD KEY `idx_prr_status` (`status`),
  ADD KEY `idx_prr_module` (`module_key`),
  ADD KEY `fk_prr_admin` (`admin_id`);

--
-- Indexes for table `sms2_roles`
--
ALTER TABLE `sms2_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_key` (`role_key`);

--
-- Indexes for table `sms2_role_permissions`
--
ALTER TABLE `sms2_role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_module` (`role_key`,`module_key`),
  ADD KEY `idx_perm_module` (`module_key`);

--
-- Indexes for table `sms2_security_otps`
--
-- sms2_security_otps already declares its primary key in CREATE TABLE.

--
-- Indexes for table `sms2_student_profiles`
--
ALTER TABLE `sms2_student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sp_user` (`user_id`),
  ADD UNIQUE KEY `uq_sp_student_id` (`student_id`);

--
-- Indexes for table `sms2_system_settings`
--
ALTER TABLE `sms2_system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `sms2_users`
--
ALTER TABLE `sms2_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_key`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_student_id` (`student_id`),
  ADD KEY `idx_users_last_seen` (`last_seen_at`);

--
-- Indexes for table `sms2_user_authenticators`
--
ALTER TABLE `sms2_user_authenticators`
  ADD PRIMARY KEY (`user_id`);


--
-- AUTO_INCREMENT for dumped tables
--


--
-- AUTO_INCREMENT for table `sms2_admin_announcements`
--
ALTER TABLE `sms2_admin_announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms2_login_throttles`
--
ALTER TABLE `sms2_login_throttles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms2_password_resets`
--
ALTER TABLE `sms2_password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sms2_password_reset_requests`
--
ALTER TABLE `sms2_password_reset_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sms2_roles`
--
ALTER TABLE `sms2_roles`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2151;

--
-- AUTO_INCREMENT for table `sms2_role_permissions`
--
ALTER TABLE `sms2_role_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2593;

--
-- AUTO_INCREMENT for table `sms2_security_otps`
--
ALTER TABLE `sms2_security_otps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `sms2_student_profiles`
--
ALTER TABLE `sms2_student_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sms2_users`
--
ALTER TABLE `sms2_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1703;

--
-- AUTO_INCREMENT for table `sms2_user_authenticators`
--
ALTER TABLE `sms2_user_authenticators`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1599;


--
-- Constraints for dumped tables
--

--
-- Constraints for table `sms2_activity_logs`
--
ALTER TABLE `sms2_activity_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `sms2_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sms2_password_resets`
--
ALTER TABLE `sms2_password_resets`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `sms2_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms2_password_reset_requests`
--
ALTER TABLE `sms2_password_reset_requests`
  ADD CONSTRAINT `fk_prr_admin` FOREIGN KEY (`admin_id`) REFERENCES `sms2_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prr_user` FOREIGN KEY (`user_id`) REFERENCES `sms2_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms2_role_permissions`
--
ALTER TABLE `sms2_role_permissions`
  ADD CONSTRAINT `fk_perm_role` FOREIGN KEY (`role_key`) REFERENCES `sms2_roles` (`role_key`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sms2_users`
--
ALTER TABLE `sms2_users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_key`) REFERENCES `sms2_roles` (`role_key`) ON UPDATE CASCADE;

--
-- Constraints for table `sms2_user_passkeys`
--
ALTER TABLE `sms2_user_passkeys`
  ADD CONSTRAINT `fk_passkey_user` FOREIGN KEY (`user_id`) REFERENCES `sms2_users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
