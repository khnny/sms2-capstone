-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 08:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30
--
-- HostForge: import into the ATTACHED database (DB_DATABASE, e.g. hf_db_xxxx).
-- Do not create a separate database named sms2_db unless that is DB_DATABASE.
-- This dump has no USE `sms2_db` so tables land in whatever DB is selected.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS=0;
START TRANSACTION;
SET time_zone = "+00:00";

DROP TABLE IF EXISTS `sms_activity_logs`;
DROP TABLE IF EXISTS `sms_login_throttles`;
DROP TABLE IF EXISTS `sms_password_resets`;
DROP TABLE IF EXISTS `sms_password_reset_requests`;
DROP TABLE IF EXISTS `sms_role_permissions`;
DROP TABLE IF EXISTS `sms_roles`;
DROP TABLE IF EXISTS `sms_security_otps`;
DROP TABLE IF EXISTS `sms_system_settings`;
DROP TABLE IF EXISTS `sms_user_authenticators`;
DROP TABLE IF EXISTS `sms_user_passkeys`;
DROP TABLE IF EXISTS `sms_users`;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Import into the currently selected database (HostForge DB_DATABASE).
--

-- --------------------------------------------------------

--
-- Table structure for table `sms_activity_logs`
--

CREATE TABLE `sms_activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `role_key` varchar(40) DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `module_key` varchar(60) DEFAULT NULL,
  `detail` varchar(500) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_activity_logs`
--

INSERT INTO `sms_activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(3, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 08:59:38'),
(4, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated Super Admin profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 09:02:04'),
(5, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36', '2026-07-24 09:02:07'),
(6, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:04:48'),
(7, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:46:02'),
(8, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:46:18'),
(9, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated Super Admin profile', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:46:29'),
(10, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:46:33'),
(11, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:47:36'),
(12, 1, 'Super Admin', 'admin', 'view', 'enrollment', 'Opened Enrollment Management Module Security', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:51:40'),
(13, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 12:53:42'),
(14, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:05:23'),
(15, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:05:45'),
(16, 1, 'Super Admin', 'admin', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:05:59'),
(17, 1, NULL, NULL, 'password_reset', 'System', 'Password reset via token', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:06:28'),
(18, 9, 'Student User', 'student', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:06:33'),
(19, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:06:34'),
(20, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:08:29'),
(21, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:08:46'),
(22, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:09:03'),
(23, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:09:41'),
(24, 1, 'Super Admin', 'admin', 'password_reset_request', 'user-management', 'OTP emailed for Super Admin password change', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:10:21'),
(25, 1, 'Super Admin', 'admin', 'password_change', 'user-management', 'Super Admin password reset via account settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:10:29'),
(26, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:11:02'),
(27, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:11:14'),
(28, 9, 'Student User', 'student', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:11:19'),
(29, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:11:19'),
(30, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:12:08'),
(31, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:12:17'),
(32, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:13:35'),
(33, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:13:48'),
(34, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:15:37'),
(35, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:16:14'),
(36, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user s230000001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:17:16'),
(37, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:17:20'),
(38, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:17:32'),
(39, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:17:41'),
(40, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:17:44'),
(41, 9, 'Student User', 'student', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:18:27'),
(42, 9, 'Student User', 'student', 'password_reset_request', 'System', 'Password reset link emailed', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:18:36'),
(43, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:21:02'),
(44, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:22:47'),
(45, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:25:47'),
(46, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user cradofficer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:28:10'),
(47, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Archived user #10 (status=inactive)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:29:05'),
(48, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:29:42'),
(49, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:29:51'),
(50, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user cradofficer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:30:40'),
(51, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:30:43'),
(52, 3, 'CRAD Officer', 'crad_officer', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:30:55'),
(53, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:31:36'),
(54, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user cradofficer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:31:50'),
(55, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:31:52'),
(56, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:32:15'),
(57, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:41:07'),
(58, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:41:17'),
(59, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:41:26'),
(60, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:41:52'),
(61, 3, 'CRAD Officer', 'crad_officer', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:46:20'),
(62, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:55:08'),
(63, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 13:55:34'),
(64, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:02:03'),
(65, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:02:28'),
(66, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:06:11'),
(67, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:06:38'),
(68, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user s230000001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:17:40'),
(69, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:18:23'),
(70, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:18:37'),
(71, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:32:52'),
(72, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user s230000001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:32:59'),
(73, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user finance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:35:47'),
(74, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:36:52'),
(75, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:37:11'),
(76, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:43:31'),
(77, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 14:44:03'),
(78, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:07:06'),
(79, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:13:15'),
(80, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:52:18'),
(81, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:52:21'),
(82, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:53:03'),
(83, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 15:54:55'),
(84, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 16:36:05'),
(85, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 16:51:54'),
(86, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 17:34:40'),
(87, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 17:36:17'),
(88, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00002 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 18:22:42'),
(89, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00003 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 18:36:18'),
(90, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 19:10:53'),
(91, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00002 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 19:11:52'),
(92, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:03:13'),
(93, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:03:50'),
(94, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Permission registrar:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:12:08'),
(95, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Permission registrar:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:12:08'),
(96, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user finance', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:20:39'),
(97, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:20:47'),
(98, 4, 'Finance', 'finance', 'login', 'payment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:20:55'),
(99, 4, 'Finance', 'finance', 'logout', 'payment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:21:04'),
(100, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:21:27'),
(101, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:22:34'),
(102, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:25:41'),
(103, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:26:12'),
(104, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-06 20:26:19'),
(105, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 11:43:55'),
(106, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 11:44:40'),
(107, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 11:44:46'),
(108, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 11:45:15'),
(109, 3, 'CRAD Officer', 'crad_officer', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 12:33:08'),
(110, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:08:00'),
(111, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00003 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:15:45'),
(112, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:39:34'),
(113, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:46:38'),
(114, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:46:45'),
(115, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:47:17'),
(116, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:47:43'),
(117, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:51:23'),
(118, 4, 'Finance', 'finance', 'login', 'payment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:51:31'),
(119, 4, 'Finance', 'finance', 'logout', 'payment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:55:57'),
(120, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 13:56:06'),
(121, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:02:05'),
(122, 4, 'Finance', 'finance', 'login', 'payment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:02:19'),
(123, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:06:50'),
(124, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:06:57'),
(125, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:07:01'),
(126, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:07:10'),
(127, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:07:40'),
(128, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:10:29'),
(129, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:10:36'),
(130, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user hr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:10:53'),
(131, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:10:55'),
(132, 8, 'HR', 'hr', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:03'),
(133, 8, 'HR', 'hr', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:15'),
(134, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:24'),
(135, 4, 'Finance', 'finance', 'logout', 'payment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:33'),
(136, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:11:41'),
(137, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:12:33'),
(138, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:22:41'),
(139, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:22:49'),
(140, 1, 'Super Admin', 'admin', 'update', 'System', 'Added passkey: This device', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:23:01'),
(141, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:23:06'),
(142, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:23:21'),
(143, 1, 'Super Admin', 'admin', 'update', 'System', 'Enabled Google Authenticator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:24:39'),
(144, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:24:41'),
(145, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:24:56'),
(146, 1, 'Super Admin', 'admin', 'update', 'System', 'Disabled Google Authenticator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:25:46'),
(147, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:27:34'),
(148, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:27:42'),
(149, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:27:50'),
(150, 4, 'Finance', 'finance', 'login', 'payment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:28:00'),
(151, 4, 'Finance', 'finance', 'logout', 'payment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:28:02'),
(152, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:28:10'),
(153, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:32:37'),
(154, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:33:33'),
(155, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:33:55'),
(156, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:37:05'),
(157, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 14:37:12'),
(158, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:04:56'),
(159, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:18:44'),
(160, 3, 'CRAD Officer', 'crad_officer', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:19:12'),
(161, 3, 'CRAD Officer', 'crad_officer', 'update', 'System', 'Enabled Google Authenticator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:21:14'),
(162, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:21:16'),
(163, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:22:03'),
(164, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:22:09'),
(165, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:22:17'),
(166, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:23:34'),
(167, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:23:46'),
(168, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:23:57'),
(169, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00004 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:27:28'),
(170, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:27:30'),
(171, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:27:51'),
(172, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:29:05'),
(173, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 15:29:25'),
(174, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 21:37:37'),
(175, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 21:37:40'),
(176, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 21:59:44'),
(177, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 22:01:44'),
(178, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 22:01:51'),
(179, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-07 22:14:54'),
(180, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:37:06'),
(181, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:37:41'),
(182, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:37:49'),
(183, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user studentaffairs', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:38:23'),
(184, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:40:43'),
(185, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:40:52'),
(186, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:48:14'),
(187, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:48:16'),
(188, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:48:21'),
(189, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:48:23'),
(190, 1, 'Super Admin', 'admin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:48:26'),
(191, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:48:29'),
(192, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 01:48:41'),
(193, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 12:12:02'),
(194, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 12:12:12'),
(195, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00003', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 12:38:51'),
(196, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 12:49:15'),
(197, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 12:53:19'),
(198, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00004', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:03:57'),
(199, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:04:02'),
(200, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:05:55'),
(201, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:06:05'),
(202, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:07:15'),
(203, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:08:59'),
(204, 9, 'Student User', 'student', 'create', 'student_portal', 'Resubmitted revised research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:33:56'),
(205, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00006', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:37:18'),
(206, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:37:21'),
(207, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:39:20'),
(208, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00007', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:39:46'),
(209, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:39:48'),
(210, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:41:46'),
(211, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00008', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:42:32'),
(212, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:42:39'),
(213, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:44:07'),
(214, 9, 'Student User', 'student', 'create', 'student_portal', 'Resubmitted revised research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 13:45:25'),
(215, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:52:25'),
(216, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:52:55'),
(217, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:53:04');
INSERT INTO `sms_activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(218, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:53:10'),
(219, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:53:19'),
(220, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:53:53'),
(221, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:54:01'),
(222, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:54:11'),
(223, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:54:19'),
(224, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:54:41'),
(225, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:54:56'),
(226, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:55:04'),
(227, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 15:55:12'),
(228, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:01:45'),
(229, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:01:54'),
(230, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:32:36'),
(231, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:37:00'),
(232, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 16:46:27'),
(233, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:02:31'),
(234, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00013', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:03:28'),
(235, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:03:31'),
(236, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:07:08'),
(237, 1, 'Super Admin', 'admin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:07:17'),
(238, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Permission registrar:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:16:54'),
(239, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Permission registrar:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:18:42'),
(240, 1, 'Super Admin', 'admin', 'update', 'user-management', 'Permission superadmin:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:00'),
(241, 1, 'Super Admin', 'admin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:07'),
(242, 1, 'Super Admin', 'superadmin', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:13'),
(243, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:20'),
(244, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission admission:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:25'),
(245, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission registrar:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:26'),
(246, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:39'),
(247, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:registrar = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:41'),
(248, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:crad = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:42'),
(249, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Reset all role permissions to defaults', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:49'),
(250, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission admission:registrar = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:28:55'),
(251, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission admission:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:03'),
(252, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:crad = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:06'),
(253, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:lms = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:07'),
(254, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:reports-analytics = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:07'),
(255, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:cocurricular = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:08'),
(256, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:scheduling = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:08'),
(257, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:faculty = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:08'),
(258, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:payment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:09'),
(259, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:accreditation = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:09'),
(260, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:curriculum = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:10'),
(261, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:registrar = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:11'),
(262, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:12'),
(263, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission admission:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:12'),
(264, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission registrar:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:29:14'),
(265, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission admission:registrar = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:34:34'),
(266, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:34:54'),
(267, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:34:56'),
(268, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission crad_officer:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:10'),
(269, 1, 'Super Admin', 'superadmin', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:11'),
(270, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:19'),
(271, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:22'),
(272, 1, 'Super Admin', 'superadmin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:29'),
(273, 1, 'Super Admin', 'superadmin', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:35'),
(274, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission crad_officer:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:39'),
(275, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:41'),
(276, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:registrar = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:41'),
(277, 1, 'Super Admin', 'superadmin', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:43'),
(278, 1, 'Super Admin', 'superadmin', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:51'),
(279, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:54'),
(280, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:registrar = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:35:54'),
(281, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:reports-analytics = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:40:49'),
(282, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:reports-analytics = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:40:49'),
(283, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Reset all role permissions to defaults', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:41:08'),
(284, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission admission:registrar = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:41:14'),
(285, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission registrar:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:41:16'),
(286, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:51:29'),
(287, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:51:38'),
(288, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:51:39'),
(289, 2, 'Registrar', 'registrar', 'login', 'registrar', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:51:44'),
(290, 2, 'Registrar', 'registrar', 'logout', 'registrar', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:51:59'),
(291, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:52:07'),
(292, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:52:12'),
(293, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:52:12'),
(294, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:52:13'),
(295, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:52:18'),
(296, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission student:student_portal = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:55:49'),
(297, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission student:student_portal = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:55:50'),
(298, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:55:55'),
(299, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:56:03'),
(300, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:57:09'),
(301, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 17:57:13'),
(302, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission qa:user-management = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:04:41'),
(303, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission qa:user-management = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:04:42'),
(304, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:student_portal = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:05:07'),
(305, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user researchcoordinator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:22:59'),
(306, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:23:01'),
(307, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:23:11'),
(308, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:23:15'),
(309, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:23:28'),
(310, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:33:04'),
(311, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:33:13'),
(312, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:33:20'),
(313, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:33:38'),
(314, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-08 18:38:39'),
(315, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:45:41'),
(316, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:45:47'),
(317, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:45:56'),
(318, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00014', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:46:33'),
(319, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:46:36'),
(320, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:46:38'),
(321, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 18:46:55'),
(322, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:14:20'),
(323, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:14:28'),
(324, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:14:39'),
(325, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:14:59'),
(326, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:38:07'),
(327, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:38:17'),
(328, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:38:28'),
(329, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:38:31'),
(330, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:38:50'),
(331, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:49:46'),
(332, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:49:57'),
(333, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:50:05'),
(334, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:50:35'),
(335, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:50:39'),
(336, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:50:58'),
(337, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00015', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:51:14'),
(338, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-009', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:51:22'),
(339, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:51:24'),
(340, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 20:51:33'),
(341, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:21:51'),
(342, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:21:59'),
(343, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user rsantos', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:43:02'),
(344, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:43:13'),
(345, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:43:20'),
(346, 54, 'Dr. Roberto M. Santos', 'adviser', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:43:55'),
(347, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:54:36'),
(348, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:54:44'),
(349, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:54:48'),
(350, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:55:01'),
(351, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:55:07'),
(352, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:55:21'),
(353, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:55:26'),
(354, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 21:55:35'),
(355, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:00:14'),
(356, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:00:21'),
(357, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:00:59'),
(358, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:enrollment = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:01:11'),
(359, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user registrar', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:01:25'),
(360, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:01:28'),
(361, 2, 'Registrar', 'registrar', 'login', 'registrar', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:01:35'),
(362, 2, 'Registrar', 'registrar', 'view', 'registrar', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:01:46'),
(363, 2, 'Registrar', 'registrar', 'view', 'scheduling', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:02:12'),
(364, 2, 'Registrar', 'registrar', 'logout', 'registrar', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:06:23'),
(365, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:06:29'),
(366, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission registrar:reports-analytics = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:06:38'),
(367, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission registrar:crad = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:06:38'),
(368, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission registrar:lms = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:06:39'),
(369, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission registrar:cocurricular = grant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:06:39'),
(370, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:06:43'),
(371, 2, 'Registrar', 'registrar', 'login', 'registrar', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:06:54'),
(372, 2, 'Registrar', 'registrar', 'logout', 'registrar', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:06:59'),
(373, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:07:07'),
(374, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Reset all role permissions to defaults', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:07:16'),
(375, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Permission superadmin:student_portal = deny', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:07:21'),
(376, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:07:26'),
(377, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:10:03'),
(378, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:10:16'),
(379, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:10:56'),
(380, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:11:01'),
(381, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:11:34'),
(382, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:11:38'),
(383, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:12:27'),
(384, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:13:59'),
(385, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:14:12'),
(386, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:16:46'),
(387, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:16:57'),
(388, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:17:14'),
(389, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:17:22'),
(390, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:17:30'),
(391, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:17:47'),
(392, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-08 22:18:13'),
(393, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:16:17'),
(394, 54, 'Dr. Roberto M. Santos', 'adviser', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:16:44'),
(395, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:17:01'),
(396, 54, 'Dr. Roberto M. Santos', 'adviser', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:17:12'),
(397, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:20:02'),
(398, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:20:15'),
(399, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:33:06'),
(400, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:33:18'),
(401, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:38:10'),
(402, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:38:27'),
(403, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:41:13'),
(404, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:41:21'),
(405, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:42:12'),
(406, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:42:21'),
(407, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:45:56'),
(408, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:46:12'),
(409, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 13:47:42'),
(410, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-09 13:47:52'),
(411, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-09 14:44:20'),
(412, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-09 14:44:35'),
(413, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-09 14:44:59'),
(414, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-09 14:44:59'),
(415, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-09 14:45:15'),
(416, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-09 14:47:33'),
(417, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:03:15'),
(418, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:03:31'),
(419, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:03:44'),
(420, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:03:55'),
(421, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:14:05'),
(422, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:14:12'),
(423, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:14:19'),
(424, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00016', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:14:36'),
(425, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:14:39'),
(426, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:14:43'),
(427, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:14:58'),
(428, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-09 15:38:37'),
(429, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-09 15:43:01'),
(430, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:50:15');
INSERT INTO `sms_activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(431, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:51:49'),
(432, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:52:05'),
(433, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:52:22'),
(434, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-011', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:52:25'),
(435, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:52:35'),
(436, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 15:52:46'),
(437, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:02:04'),
(438, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:02:08'),
(439, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:02:14'),
(440, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00018', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:02:32'),
(441, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-012', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:02:34'),
(442, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:02:38'),
(443, 40, 'Research Coordinator', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:02:47'),
(444, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:02:54'),
(445, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:05:29'),
(446, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:05:36'),
(447, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:05:44'),
(448, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00019', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:06:00'),
(449, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-013', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:06:02'),
(450, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:06:08'),
(451, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:06:26'),
(452, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:17:41'),
(453, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:17:48'),
(454, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:18:13'),
(455, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:20:05'),
(456, 9, 'Student User', 'student', 'create', 'student_portal', 'Resubmitted revised research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:35:44'),
(457, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00021', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:36:06'),
(458, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-014', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:36:08'),
(459, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:36:17'),
(460, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:36:28'),
(461, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:36:55'),
(462, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 16:37:11'),
(463, 3, 'CRAD Officer', 'crad_officer', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:05:20'),
(464, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:08:52'),
(465, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:08:59'),
(466, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:09:05'),
(467, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:09:29'),
(468, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:11:26'),
(469, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:11:34'),
(470, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:12:42'),
(471, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:12:50'),
(472, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:20:57'),
(473, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:21:50'),
(474, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:22:03'),
(475, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:22:16'),
(476, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:22:26'),
(477, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:24:51'),
(478, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:24:59'),
(479, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:25:45'),
(480, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:26:27'),
(481, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user jtan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:26:41'),
(482, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:26:46'),
(483, NULL, 'Dr. Jose B. Tan', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:26:54'),
(484, NULL, 'Dr. Jose B. Tan', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:30:40'),
(485, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:30:46'),
(486, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:31:26'),
(487, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:31:34'),
(488, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:39:10'),
(489, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:40:11'),
(490, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:42:06'),
(491, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:42:17'),
(492, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:49:23'),
(493, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:49:42'),
(494, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:49:52'),
(495, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:50:04'),
(496, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:51:28'),
(497, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:51:38'),
(498, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:51:42'),
(499, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:51:51'),
(500, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:54:55'),
(501, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00022', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:55:38'),
(502, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-015', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 17:55:48'),
(503, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:01:50'),
(504, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:02:02'),
(505, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:24:27'),
(506, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:24:37'),
(507, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:24:45'),
(508, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00023', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:25:17'),
(509, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-014', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:25:20'),
(510, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:25:31'),
(511, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:25:43'),
(512, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:26:48'),
(513, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:26:55'),
(514, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:27:03'),
(515, NULL, 'Unknown', NULL, 'login_failed', 'System', 'Invalid login attempt (unknown credentials)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:27:13'),
(516, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:27:33'),
(517, 9, 'Student User', 'student', 'create', 'student_portal', 'Resubmitted revised research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:28:13'),
(518, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00024', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:28:48'),
(519, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-017', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:28:51'),
(520, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:28:59'),
(521, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:29:14'),
(522, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:29:20'),
(523, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:29:28'),
(524, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:30:02'),
(525, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:30:10'),
(526, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:31:16'),
(527, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:31:32'),
(528, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:38:06'),
(529, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:38:10'),
(530, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:38:16'),
(531, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00025', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:38:44'),
(532, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:38:47'),
(533, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:39:08'),
(534, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:39:18'),
(535, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:39:27'),
(536, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:39:37'),
(537, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:39:46'),
(538, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:39:54'),
(539, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:40:08'),
(540, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:40:17'),
(541, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:50:53'),
(542, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00026', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:51:26'),
(543, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-019', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:51:29'),
(544, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:51:41'),
(545, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:51:48'),
(546, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:52:08'),
(547, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:52:15'),
(548, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:56:00'),
(549, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:56:07'),
(550, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:57:10'),
(551, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:57:18'),
(552, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:58:49'),
(553, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 18:58:57'),
(554, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:24:27'),
(555, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:24:38'),
(556, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:26:57'),
(557, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:27:09'),
(558, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:46:14'),
(559, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:46:23'),
(560, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user researchdirector', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:48:31'),
(561, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:48:38'),
(562, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-09 19:48:46'),
(563, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 13:20:13'),
(564, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 13:20:24'),
(565, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 13:20:43'),
(566, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 14:16:36'),
(567, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 19:18:33'),
(568, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 19:36:23'),
(569, 1, 'Super Admin', 'superadmin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 19:36:30'),
(570, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 19:36:37'),
(571, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 19:42:36'),
(572, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 19:42:43'),
(573, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 19:42:50'),
(574, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user researchgrant', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:07:43'),
(575, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:07:50'),
(576, 222, 'Research Grant', 'research_grant', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:07:55'),
(577, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:08:51'),
(578, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:09:17'),
(579, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:09:25'),
(580, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:09:32'),
(581, 222, 'Research Grant', 'research_grant', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:14:13'),
(582, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:14:21'),
(583, 222, 'Research Grant', 'research_grant', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:21:18'),
(584, 222, 'Research Grant', 'research_grant', 'view', 'crad_grant', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:24:17'),
(585, 222, 'Research Grant', 'research_grant', 'update', 'System', 'Enabled Google Authenticator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:27:06'),
(586, 222, 'Research Grant', 'research_grant', 'update', 'System', 'Disabled Google Authenticator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:27:16'),
(587, 222, 'Research Grant', 'research_grant', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:37:50'),
(588, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:37:57'),
(589, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:38:04'),
(590, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:38:11'),
(591, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:38:16'),
(592, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:41:24'),
(593, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:42:35'),
(594, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:43:20'),
(595, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:43:26'),
(596, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:44:22'),
(597, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:44:29'),
(598, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:44:44'),
(599, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:44:51'),
(600, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:45:27'),
(601, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:45:36'),
(602, 9, 'Student User', 'student', 'create', 'student_portal', 'Resubmitted revised research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:46:03'),
(603, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:46:07'),
(604, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:46:18'),
(605, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00027', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:46:42'),
(606, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:48:30'),
(607, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:48:40'),
(608, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:48:46'),
(609, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:51:37'),
(610, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:51:43'),
(611, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-020', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 20:59:18'),
(612, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:03:31'),
(613, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:03:40'),
(614, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:04:35'),
(615, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:05:08'),
(616, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:16:21'),
(617, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:21:51'),
(618, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:22:00'),
(619, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:25:12'),
(620, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:25:23'),
(621, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:25:33'),
(622, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:25:39'),
(623, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:34:33'),
(624, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:36:31'),
(625, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:38:41'),
(626, 40, 'Research Coordinator', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:38:48'),
(627, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:39:03'),
(628, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:39:18'),
(629, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:39:38'),
(630, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:47:06'),
(631, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 21:47:28'),
(632, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 22:20:14'),
(633, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 22:20:29'),
(634, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 22:29:56'),
(635, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 22:30:12'),
(636, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-10 22:56:42'),
(637, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:08:32'),
(638, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:08:43'),
(639, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:08:49'),
(640, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:09:01'),
(641, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:24:46');
INSERT INTO `sms_activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(642, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:55:23'),
(643, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:55:31'),
(644, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:55:43'),
(645, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:55:53'),
(646, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:56:00'),
(647, 222, 'Research Grant', 'research_grant', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:56:09'),
(648, 222, 'Research Grant', 'research_grant', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:56:18'),
(649, 40, 'Research Coordinator', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:56:28'),
(650, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:56:35'),
(651, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:56:44'),
(652, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 01:56:53'),
(653, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:01:41'),
(654, 54, 'Dr. Roberto M. Santos', 'adviser', 'login_failed', 'System', 'Invalid password', '::1', 'curl/8.13.0', '2026-08-11 02:02:36'),
(655, 54, 'Dr. Roberto M. Santos', 'adviser', 'login_failed', 'System', 'Invalid password', '::1', 'curl/8.13.0', '2026-08-11 02:03:05'),
(656, 54, 'Dr. Roberto M. Santos', 'adviser', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'curl/8.13.0', '2026-08-11 02:03:20'),
(657, 54, 'Dr. Roberto M. Santos', 'adviser', 'login_failed', 'System', 'Invalid password', '::1', 'curl/8.13.0', '2026-08-11 02:03:20'),
(658, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:07:09'),
(659, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:07:15'),
(660, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Restored user #54 (status=active)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:07:31'),
(661, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user rsantos', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:07:39'),
(662, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:07:42'),
(663, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:07:48'),
(664, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:07:54'),
(665, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:08:02'),
(666, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user rsantos', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:08:10'),
(667, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:08:17'),
(668, 54, 'Dr. Roberto M. Santos', 'adviser', 'login_failed', 'System', 'Login blocked — login locked', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:08:23'),
(669, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:08:47'),
(670, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Restored user #54 (status=active)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:08:51'),
(671, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:08:55'),
(672, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:09:03'),
(673, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:10:20'),
(674, 222, 'Research Grant', 'research_grant', 'login', 'System', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:10:30'),
(675, 222, 'Research Grant', 'research_grant', 'view', 'crad_grant', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:11:53'),
(676, 222, 'Research Grant', 'research_grant', 'logout', 'System', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:23:41'),
(677, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 02:23:50'),
(678, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:13:46'),
(679, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:14:29'),
(680, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:27:26'),
(681, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:27:34'),
(682, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:27:43'),
(683, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:27:55'),
(684, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:28:15'),
(685, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:28:17'),
(686, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:28:25'),
(687, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Registered approved proposal number:CRD-2026-00028', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:28:44'),
(688, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated research group number: RG-2026-021', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:28:47'),
(689, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:28:50'),
(690, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:28:59'),
(691, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:33:26'),
(692, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:33:35'),
(693, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:38:00'),
(694, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 12:38:11'),
(695, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:33:29'),
(696, 40, 'Research Coordinator', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:34:40'),
(697, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:34:55'),
(698, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:35:33'),
(699, 40, 'Research Coordinator', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:35:39'),
(700, 40, 'Research Coordinator', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:35:47'),
(701, 40, 'Research Coordinator', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:36:58'),
(702, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:37:21'),
(703, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:38:00'),
(704, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:38:07'),
(705, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:41:27'),
(706, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:41:39'),
(707, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:41:49'),
(708, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:41:57'),
(709, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:50:28'),
(710, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:50:37'),
(711, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:50:50'),
(712, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:51:00'),
(713, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:59:48'),
(714, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 13:59:55'),
(715, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:00:18'),
(716, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:00:43'),
(717, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:01:08'),
(718, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:01:37'),
(719, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:02:57'),
(720, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:03:03'),
(721, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:03:09'),
(722, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:03:45'),
(723, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:03:52'),
(724, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:05:31'),
(725, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:05:39'),
(726, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:07:04'),
(727, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:07:10'),
(728, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:08:13'),
(729, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:08:20'),
(730, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:15:03'),
(731, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:15:14'),
(732, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:18:40'),
(733, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:18:48'),
(734, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:18:59'),
(735, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:19:07'),
(736, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:20:27'),
(737, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:20:33'),
(738, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:20:41'),
(739, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:20:59'),
(740, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:21:14'),
(741, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:21:37'),
(742, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:26:17'),
(743, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:26:23'),
(744, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:30:10'),
(745, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:30:17'),
(746, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:30:44'),
(747, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:30:52'),
(748, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:31:00'),
(749, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:31:15'),
(750, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:31:29'),
(751, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:31:37'),
(752, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:33:59'),
(753, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:34:06'),
(754, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:39:37'),
(755, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:39:44'),
(756, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:40:00'),
(757, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:40:11'),
(758, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:40:20'),
(759, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:40:27'),
(760, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:44:49'),
(761, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:45:10'),
(762, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:45:29'),
(763, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:45:35'),
(764, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:47:17'),
(765, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:47:26'),
(766, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:48:24'),
(767, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:48:30'),
(768, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:48:37'),
(769, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:48:46'),
(770, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:48:52'),
(771, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:48:58'),
(772, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:55:39'),
(773, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:55:47'),
(774, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:55:52'),
(775, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:56:00'),
(776, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:56:07'),
(777, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 14:56:13'),
(778, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-022', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:03:30'),
(779, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:15:09'),
(780, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:15:20'),
(781, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:18:46'),
(782, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:18:57'),
(783, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:22:41'),
(784, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (7 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:33:29'),
(785, 9, 'Student User', 'student', 'create', 'student_portal', 'Submitted research document packet ref:CRD-2026-00001 (6 files)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:42:11'),
(786, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:52:50'),
(787, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:52:57'),
(788, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:53:24'),
(789, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:53:35'),
(790, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:55:46'),
(791, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:55:55'),
(792, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:59:25'),
(793, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 15:59:34'),
(794, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:02:05'),
(795, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:02:12'),
(796, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-023', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:02:22'),
(797, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:02:36'),
(798, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:02:59'),
(799, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:03:01'),
(800, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:03:13'),
(801, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:03:31'),
(802, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:04:03'),
(803, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:04:09'),
(804, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:04:22'),
(805, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:04:35'),
(806, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:04:42'),
(807, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:32:39'),
(808, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:32:47'),
(809, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:32:57'),
(810, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:33:06'),
(811, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:34:51'),
(812, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:35:01'),
(813, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:49:31'),
(814, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:49:46'),
(815, 116, 'Research Director', 'research_director', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:49:49'),
(816, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:49:53'),
(817, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:50:00'),
(818, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:50:36'),
(819, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:50:42'),
(820, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:51:01'),
(821, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:51:07'),
(822, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:52:37'),
(823, 222, 'Research Grant', 'research_grant', 'login', 'crad_grant', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:52:47'),
(824, 222, 'Research Grant', 'research_grant', 'view', 'crad_grant', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:52:49'),
(825, 222, 'Research Grant', 'research_grant', 'logout', 'crad_grant', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:52:51'),
(826, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:53:01'),
(827, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 16:53:02'),
(828, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:01:23'),
(829, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:01:36'),
(830, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:10:37'),
(831, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:10:46'),
(832, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:11:03'),
(833, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:11:18'),
(834, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:14:22'),
(835, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:14:30'),
(836, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:16:00'),
(837, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:16:11'),
(838, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:19:51'),
(839, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:20:01'),
(840, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:20:39'),
(841, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:20:47'),
(842, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:20:55'),
(843, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:21:08'),
(844, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:23:48'),
(845, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:23:59'),
(846, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:24:08'),
(847, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:24:19'),
(848, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:27:43'),
(849, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:27:54'),
(850, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:28:06'),
(851, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:28:14'),
(852, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:31:09'),
(853, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:31:16'),
(854, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:31:24'),
(855, 4, 'Finance', 'finance', 'login', 'payment', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:31:31'),
(856, 4, 'Finance', 'finance', 'logout', 'payment', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:31:39');
INSERT INTO `sms_activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(857, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:31:48'),
(858, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:36:55'),
(859, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:37:09'),
(860, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:44:24'),
(861, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:49:04'),
(862, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:50:40'),
(863, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:50:49'),
(864, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:51:34'),
(865, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:51:41'),
(866, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:51:59'),
(867, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:52:01'),
(868, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 17:52:11'),
(869, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:08:26'),
(870, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:08:33'),
(871, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:09:07'),
(872, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:09:16'),
(873, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:10:11'),
(874, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:10:17'),
(875, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:14:22'),
(876, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:14:33'),
(877, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:16:35'),
(878, 54, 'Dr. Roberto M. Santos', 'adviser', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:16:42'),
(879, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:16:49'),
(880, 54, 'Dr. Roberto M. Santos', 'adviser', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:16:51'),
(881, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:17:03'),
(882, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:17:09'),
(883, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:17:19'),
(884, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:17:25'),
(885, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-021', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:17:32'),
(886, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:17:39'),
(887, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:17:46'),
(888, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:21:18'),
(889, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:21:26'),
(890, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:25:43'),
(891, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:25:49'),
(892, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:26:10'),
(893, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:26:19'),
(894, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:27:08'),
(895, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:27:17'),
(896, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:27:21'),
(897, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:27:31'),
(898, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:36:56'),
(899, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:37:08'),
(900, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:50:56'),
(901, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:51:03'),
(902, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:54:07'),
(903, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 18:56:06'),
(904, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:01:47'),
(905, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:02:39'),
(906, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:10:45'),
(907, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:10:59'),
(908, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:11:10'),
(909, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:11:20'),
(910, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-022', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:11:27'),
(911, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:11:31'),
(912, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:11:49'),
(913, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:14:05'),
(914, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:14:12'),
(915, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:24:48'),
(916, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:24:57'),
(917, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:25:28'),
(918, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:25:34'),
(919, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:29:45'),
(920, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:29:52'),
(921, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:30:16'),
(922, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:31:02'),
(923, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:31:11'),
(924, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:31:50'),
(925, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:31:59'),
(926, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:32:08'),
(927, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:32:20'),
(928, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-023', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:32:29'),
(929, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:32:33'),
(930, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:32:41'),
(931, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:33:42'),
(932, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:33:50'),
(933, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:36:42'),
(934, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:36:51'),
(935, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:38:15'),
(936, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:38:24'),
(937, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:45:57'),
(938, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:46:08'),
(939, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:46:24'),
(940, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:46:35'),
(942, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:46:51'),
(943, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:47:36'),
(944, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:48:18'),
(945, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 19:48:28'),
(946, 222, 'Research Grant', 'research_grant', 'login', 'crad_grant', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 20:56:56'),
(947, 222, 'Research Grant', 'research_grant', 'logout', 'crad_grant', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 20:57:03'),
(948, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 20:57:15'),
(949, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 20:57:35'),
(950, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 20:57:41'),
(951, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-11 21:01:22'),
(952, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 12:50:40'),
(953, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 12:51:32'),
(954, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 12:55:13'),
(955, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 12:55:22'),
(956, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 13:07:10'),
(962, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-12 14:19:57'),
(963, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-12 14:21:50'),
(964, 3, NULL, 'crad_officer', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-12 14:22:00'),
(965, 3, NULL, 'crad_officer', 'logout', 'crad', 'Logged out', '0.0.0.0', NULL, '2026-08-12 14:22:28'),
(973, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-024', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 14:38:37'),
(979, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:06:15'),
(980, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:06:24'),
(981, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:26:31'),
(982, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:26:39'),
(983, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:27:08'),
(984, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:27:20'),
(985, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:27:31'),
(986, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:27:41'),
(987, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-025', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:27:57'),
(988, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:28:16'),
(989, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:28:23'),
(990, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:29:01'),
(991, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:29:08'),
(992, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:29:14'),
(993, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:29:20'),
(994, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-025', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:29:32'),
(995, 3, 'CRAD Officer', 'crad_officer', 'activate', 'crad', 'Coordinator \"Mrs. Kris Guevarra\" assignment for group RG-2026-025 set to Active', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:30:35'),
(996, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:56:03'),
(997, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:56:09'),
(998, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:57:59'),
(999, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:58:09'),
(1000, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:58:18'),
(1001, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:58:26'),
(1002, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:58:57'),
(1003, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-028', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:59:08'),
(1004, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:59:22'),
(1005, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:59:29'),
(1006, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:59:33'),
(1007, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-028', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 16:59:47'),
(1008, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:00:08'),
(1009, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:00:17'),
(1010, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:00:28'),
(1011, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:00:39'),
(1012, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:00:43'),
(1013, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:00:51'),
(1014, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:00:58'),
(1015, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:01:05'),
(1016, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-028', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:01:18'),
(1017, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:33:50'),
(1018, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:33:58'),
(1019, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:34:05'),
(1020, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:34:28'),
(1021, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-029', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:34:57'),
(1022, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:50:42'),
(1023, 1, 'Super Admin', 'superadmin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:51:06'),
(1024, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:51:17'),
(1025, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:51:34'),
(1026, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 17:51:41'),
(1027, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:37:45'),
(1028, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:51:08'),
(1029, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:51:15'),
(1030, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:51:26'),
(1031, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:51:34'),
(1032, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:51:45'),
(1033, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:52:07'),
(1034, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-030', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:52:26'),
(1035, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:52:33'),
(1036, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:52:40'),
(1037, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:52:45'),
(1038, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:52:57'),
(1039, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:53:05'),
(1040, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-030', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 18:53:22'),
(1041, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 20:15:47'),
(1042, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 20:15:57'),
(1043, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 20:40:51'),
(1044, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 20:41:00'),
(1045, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 20:41:16'),
(1046, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 20:41:36'),
(1047, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 20:43:28'),
(1048, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-12 20:43:35'),
(1049, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 00:45:00'),
(1050, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:04:25'),
(1051, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:04:33'),
(1052, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:07:36'),
(1053, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:16:33'),
(1054, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:16:42'),
(1055, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:19:48'),
(1056, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:19:58'),
(1057, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:27:03'),
(1058, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:29:46'),
(1059, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:30:03'),
(1060, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 01:34:15'),
(1061, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:51:04'),
(1062, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 07:51:18'),
(1063, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:34:17'),
(1064, 222, 'Research Grant', 'research_grant', 'login', 'crad_grant', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:34:27'),
(1065, 222, 'Research Grant', 'research_grant', 'logout', 'crad_grant', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:34:35'),
(1066, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:41:45'),
(1067, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:44:07'),
(1068, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:44:16'),
(1069, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:57:20'),
(1070, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:57:32'),
(1071, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:59:49'),
(1072, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 08:59:57'),
(1073, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:00:30'),
(1074, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:00:36'),
(1075, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:00:57'),
(1076, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:01:05'),
(1077, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:01:16'),
(1078, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:01:26'),
(1079, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-031', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:01:35'),
(1080, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-031', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:01:57'),
(1081, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:02:01'),
(1082, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:02:10'),
(1083, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:02:14'),
(1084, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:02:28'),
(1085, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:02:35'),
(1086, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:47:17'),
(1087, 54, 'Dr. Roberto M. Santos', 'adviser', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:47:25'),
(1088, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:47:30');
INSERT INTO `sms_activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1089, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:47:58'),
(1090, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:48:05'),
(1091, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:48:17'),
(1092, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:48:24'),
(1093, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:48:33'),
(1094, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:48:39'),
(1095, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:48:45'),
(1096, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:49:03'),
(1097, 3, 'CRAD Officer', 'crad_officer', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:49:21'),
(1098, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:49:27'),
(1099, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:49:37'),
(1100, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:54:48'),
(1101, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:54:55'),
(1102, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:55:41'),
(1103, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:57:37'),
(1104, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:58:25'),
(1105, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-13 09:58:33'),
(1106, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 10:23:48'),
(1107, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 10:24:12'),
(1108, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 10:45:56'),
(1109, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:00:49'),
(1110, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:01:49'),
(1111, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user grammarian', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:08:15'),
(1112, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:08:22'),
(1113, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:08:33'),
(1114, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:08:47'),
(1115, 475, 'Grammarian', 'grammarian', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:09:35'),
(1116, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:24:27'),
(1117, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:24:31'),
(1118, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:24:36'),
(1119, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:24:57'),
(1120, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:36:01'),
(1121, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:36:16'),
(1122, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 2 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:36:41'),
(1123, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:40:15'),
(1124, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:40:51'),
(1125, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:46:55'),
(1126, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:47:02'),
(1127, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:56:50'),
(1128, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:56:57'),
(1129, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:58:18'),
(1130, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:58:26'),
(1131, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:58:55'),
(1132, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:59:02'),
(1133, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-033', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 11:59:09'),
(1134, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:03:00'),
(1135, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:03:09'),
(1136, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:03:19'),
(1137, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:03:30'),
(1138, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:03:34'),
(1139, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:05:06'),
(1140, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:05:12'),
(1141, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:05:20'),
(1142, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-033', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:05:23'),
(1143, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:07:57'),
(1144, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:08:00'),
(1145, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:08:03'),
(1146, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:08:07'),
(1147, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:08:13'),
(1148, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:15:49'),
(1149, 475, 'Grammarian', 'grammarian', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:16:05'),
(1150, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:16:16'),
(1151, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:16:20'),
(1152, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:16:27'),
(1153, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:16:32'),
(1154, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:16:37'),
(1155, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:17:14'),
(1156, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:17:24'),
(1157, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:19:14'),
(1158, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:19:26'),
(1159, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:20:02'),
(1160, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:20:14'),
(1161, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:22:19'),
(1162, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:25:04'),
(1163, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:44:31'),
(1164, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:44:41'),
(1165, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:44:52'),
(1166, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:44:59'),
(1167, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:45:10'),
(1168, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:45:16'),
(1169, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:45:26'),
(1170, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:45:31'),
(1171, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:45:36'),
(1172, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:45:48'),
(1173, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:45:54'),
(1174, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:45:59'),
(1175, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:46:13'),
(1176, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:46:17'),
(1177, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:46:20'),
(1178, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:46:26'),
(1179, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:46:32'),
(1180, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:47:47'),
(1181, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:47:54'),
(1182, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:47:57'),
(1183, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:48:05'),
(1184, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:48:09'),
(1185, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:48:15'),
(1186, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:48:20'),
(1187, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:48:26'),
(1188, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:49:49'),
(1189, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 12:49:56'),
(1190, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:18:00'),
(1191, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:18:13'),
(1192, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:18:25'),
(1193, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:18:35'),
(1194, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:18:41'),
(1195, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:18:53'),
(1196, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:19:03'),
(1197, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:19:07'),
(1198, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:20:47'),
(1199, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:20:53'),
(1200, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:21:08'),
(1201, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:21:12'),
(1202, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:21:27'),
(1203, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:21:36'),
(1204, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:21:43'),
(1205, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:21:51'),
(1206, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:21:55'),
(1207, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:22:11'),
(1208, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:22:20'),
(1209, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:22:27'),
(1210, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:22:33'),
(1211, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:22:41'),
(1212, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:23:21'),
(1213, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:23:25'),
(1214, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:23:28'),
(1215, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:34:56'),
(1216, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:35:02'),
(1217, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:35:05'),
(1218, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:35:12'),
(1219, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:36:28'),
(1220, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:36:42'),
(1221, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:42:13'),
(1222, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:42:19'),
(1223, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:43:52'),
(1224, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:44:01'),
(1225, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:45:41'),
(1226, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:45:48'),
(1227, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:45:53'),
(1228, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:46:02'),
(1229, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:46:08'),
(1230, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:46:13'),
(1231, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:46:16'),
(1232, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:46:22'),
(1233, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:47:16'),
(1234, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:47:25'),
(1235, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:55:52'),
(1236, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:56:09'),
(1237, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:56:19'),
(1238, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:56:25'),
(1239, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:56:33'),
(1240, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:56:39'),
(1241, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:56:43'),
(1242, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:57:38'),
(1243, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:57:48'),
(1244, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:57:54'),
(1245, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:57:58'),
(1246, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:58:05'),
(1247, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:58:22'),
(1248, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:58:31'),
(1249, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:58:35'),
(1250, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 13:58:41'),
(1251, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:02:17'),
(1252, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:02:25'),
(1253, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:03:36'),
(1254, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:03:44'),
(1255, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:04:09'),
(1256, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:04:15'),
(1257, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:04:23'),
(1258, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:04:43'),
(1259, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:04:52'),
(1260, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:04:59'),
(1261, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:05:06'),
(1262, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:05:12'),
(1263, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:05:16'),
(1264, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:06:11'),
(1265, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:06:20'),
(1266, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:09:43'),
(1267, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:09:50'),
(1268, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:10:27'),
(1269, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:10:38'),
(1270, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:10:45'),
(1271, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:10:48'),
(1272, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:11:00'),
(1273, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:11:07'),
(1274, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:11:20'),
(1275, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:11:26'),
(1276, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:11:29'),
(1277, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:12:30'),
(1278, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:12:39'),
(1279, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:12:53'),
(1280, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:13:05'),
(1281, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:13:14'),
(1282, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:13:24'),
(1283, 3, 'CRAD Officer', 'crad_officer', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:13:28'),
(1284, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:13:42'),
(1285, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-042', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:15:17'),
(1286, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:15:23'),
(1287, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:15:33'),
(1288, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:16:17'),
(1289, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:16:24'),
(1290, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:16:32'),
(1291, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:16:35'),
(1292, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:16:43'),
(1293, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:16:49'),
(1294, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-043', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:16:56'),
(1295, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:17:01'),
(1296, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:17:05'),
(1297, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:17:16');
INSERT INTO `sms_activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1298, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:17:22'),
(1299, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-043', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:17:25'),
(1300, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:18:05'),
(1301, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:18:10'),
(1302, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:36:21'),
(1303, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:36:34'),
(1304, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:36:42'),
(1305, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:36:46'),
(1306, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:37:01'),
(1307, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:37:10'),
(1308, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-044', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:37:16'),
(1309, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-044', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:37:26'),
(1310, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:37:29'),
(1311, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:37:32'),
(1312, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:37:38'),
(1313, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:37:47'),
(1314, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-044', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:37:53'),
(1315, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:44:51'),
(1316, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:44:59'),
(1317, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:45:18'),
(1318, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:45:26'),
(1319, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-045', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:45:45'),
(1320, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-045', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:45:54'),
(1321, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:46:02'),
(1322, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:46:09'),
(1323, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:46:17'),
(1324, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:46:30'),
(1325, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:56:42'),
(1326, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:56:50'),
(1327, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:56:58'),
(1328, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 14:57:12'),
(1329, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:17:32'),
(1330, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:17:40'),
(1331, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:17:57'),
(1332, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:18:02'),
(1333, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:18:09'),
(1334, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:26:19'),
(1335, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:34:36'),
(1336, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:34:53'),
(1337, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:35:16'),
(1338, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:35:37'),
(1339, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:35:49'),
(1340, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:36:01'),
(1341, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:36:08'),
(1342, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:36:16'),
(1343, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:36:19'),
(1344, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:36:37'),
(1345, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:36:45'),
(1346, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:36:47'),
(1347, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:38:15'),
(1348, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 15:38:22'),
(1349, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:04:22'),
(1350, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:04:29'),
(1351, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:40:44'),
(1352, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:41:33'),
(1353, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:41:44'),
(1354, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:42:06'),
(1355, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:42:19'),
(1356, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:46:59'),
(1357, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:47:05'),
(1358, 116, 'Research Director', 'research_director', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:50:03'),
(1359, 9, 'Student User', 'student', 'view', 'student_portal', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 16:53:07'),
(1360, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:30:57'),
(1361, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:31:11'),
(1362, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:32:16'),
(1363, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:32:26'),
(1364, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:32:35'),
(1365, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:32:54'),
(1366, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:33:06'),
(1367, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:33:15'),
(1368, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:33:20'),
(1369, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:33:27'),
(1370, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:33:39'),
(1371, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:33:47'),
(1372, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:33:52'),
(1373, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:34:00'),
(1374, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:34:16'),
(1375, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:36:01'),
(1376, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:36:06'),
(1377, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:36:14'),
(1378, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:36:17'),
(1379, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 17:36:32'),
(1380, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 20:13:54'),
(1381, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 20:18:36'),
(1382, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 20:18:45'),
(1383, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:09:36'),
(1384, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:13:03'),
(1385, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:13:09'),
(1386, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:13:36'),
(1387, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:13:46'),
(1388, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:14:28'),
(1389, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:14:37'),
(1390, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:14:51'),
(1391, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:15:00'),
(1392, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:16:26'),
(1393, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:16:35'),
(1394, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:18:15'),
(1395, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:18:24'),
(1396, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:19:21'),
(1397, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:19:27'),
(1398, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:19:45'),
(1399, 3, 'CRAD Officer', 'crad_officer', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:21:17'),
(1400, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:22:51'),
(1401, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:23:02'),
(1402, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:29:03'),
(1403, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:29:11'),
(1404, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:39:29'),
(1405, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:39:37'),
(1406, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:39:51'),
(1407, 54, 'Dr. Roberto M. Santos', 'adviser', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:40:01'),
(1408, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:40:08'),
(1409, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:40:48'),
(1410, 3, 'CRAD Officer', 'crad_officer', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:40:57'),
(1411, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:41:24'),
(1412, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:41:38'),
(1413, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:42:40'),
(1414, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:42:48'),
(1415, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:44:58'),
(1416, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:45:05'),
(1417, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:50:06'),
(1418, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:50:12'),
(1419, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:50:29'),
(1420, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:50:37'),
(1421, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:50:45'),
(1422, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:51:10'),
(1423, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:51:29'),
(1424, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:51:50'),
(1425, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-14 21:51:58'),
(1426, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:07:51'),
(1427, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:09:59'),
(1428, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:26:22'),
(1429, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:26:29'),
(1430, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:26:54'),
(1431, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:27:01'),
(1432, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:27:07'),
(1433, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:27:21'),
(1434, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:27:30'),
(1435, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:32:15'),
(1436, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:32:28'),
(1437, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:32:33'),
(1438, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:32:40'),
(1439, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:32:47'),
(1440, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:43:36'),
(1441, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:43:40'),
(1442, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:43:43'),
(1443, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:43:48'),
(1444, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:43:55'),
(1445, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:44:33'),
(1446, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:44:40'),
(1447, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:48:40'),
(1448, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:48:46'),
(1449, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:48:51'),
(1450, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:48:55'),
(1451, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:49:00'),
(1452, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:49:04'),
(1453, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:49:22'),
(1454, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 16:49:53'),
(1455, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:08:35'),
(1456, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:08:44'),
(1457, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:10:42'),
(1458, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user jobert.valentino', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:11:04'),
(1459, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user jonathan.estrada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:11:50'),
(1460, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user michelle.guevarra', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:11:55'),
(1461, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user jobert.valentino', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:12:05'),
(1462, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user jonathan.estrada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:12:09'),
(1463, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user michelle.guevarra', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:12:15'),
(1464, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user researchdirector', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:12:24'),
(1465, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user cradofficer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:13:51'),
(1466, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user cradofficer', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:13:59'),
(1467, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:16:11'),
(1468, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:16:18'),
(1469, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:16:51'),
(1470, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:17:01'),
(1471, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:17:19'),
(1472, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:17:40'),
(1473, 491, 'Dr. Jobert Valentino', 'panel', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:24:21'),
(1474, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:41:16'),
(1475, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:41:23'),
(1476, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:42:31'),
(1477, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:42:46'),
(1478, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:45:35'),
(1479, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:45:43'),
(1480, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:49:47'),
(1481, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 17:49:59'),
(1482, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 18:20:28'),
(1483, 492, 'Dr. Jonathan Estrada', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 18:20:35'),
(1484, 492, 'Dr. Jonathan Estrada', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 18:22:47'),
(1485, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 18:23:01'),
(1486, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 18:23:11'),
(1487, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 18:23:26'),
(1488, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 18:26:12'),
(1489, 493, 'Dr. Michelle Guevarra', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 18:26:17'),
(1490, 493, 'Dr. Michelle Guevarra', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 18:53:46'),
(1491, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 18:53:58'),
(1492, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 21:59:01'),
(1493, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 21:59:08'),
(1494, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:07:43'),
(1495, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:07:49'),
(1496, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:08:28'),
(1497, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:08:35'),
(1498, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:08:50'),
(1499, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:08:57'),
(1500, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:18:58'),
(1501, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:19:05'),
(1502, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:19:16'),
(1503, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:19:24'),
(1504, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:19:37'),
(1505, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:19:43'),
(1506, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:19:52');
INSERT INTO `sms_activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1507, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:20:06'),
(1508, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:20:30'),
(1509, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:33:00'),
(1510, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:33:06'),
(1511, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:33:13'),
(1512, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:33:27'),
(1513, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:38:28'),
(1514, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:38:33'),
(1515, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:38:38'),
(1516, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:44:25'),
(1517, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:44:29'),
(1518, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:49:29'),
(1519, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:49:33'),
(1520, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:49:38'),
(1521, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:49:48'),
(1522, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:50:01'),
(1523, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:50:04'),
(1524, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:50:09'),
(1525, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:50:13'),
(1526, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:50:18'),
(1527, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:50:21'),
(1528, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:50:27'),
(1529, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:50:35'),
(1530, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:50:46'),
(1531, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:51:05'),
(1532, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:51:26'),
(1533, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:52:07'),
(1534, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:52:20'),
(1535, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:58:14'),
(1536, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 22:58:22'),
(1537, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:16:49'),
(1538, 493, 'Dr. Michelle Guevarra', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:16:57'),
(1539, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:19:43'),
(1540, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:19:49'),
(1541, 493, 'Dr. Michelle Guevarra', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:40:41'),
(1542, 493, 'Dr. Michelle Guevarra', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:56:29'),
(1543, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:56:42'),
(1544, 491, 'Dr. Jobert Valentino', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:56:51'),
(1545, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:56:56'),
(1546, 492, 'Dr. Jonathan Estrada', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:57:10'),
(1547, 492, 'Dr. Jonathan Estrada', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-15 23:57:19'),
(1548, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:44:24'),
(1549, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:44:42'),
(1550, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:56:23'),
(1551, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:56:34'),
(1552, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:56:46'),
(1553, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:56:50'),
(1554, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:56:58'),
(1555, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:57:06'),
(1556, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:57:14'),
(1557, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:57:33'),
(1558, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:57:39'),
(1559, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:57:41'),
(1560, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:57:50'),
(1561, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:57:56'),
(1562, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:58:10'),
(1563, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 14:58:28'),
(1564, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:00:39'),
(1565, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:00:43'),
(1566, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:00:47'),
(1567, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:01:06'),
(1568, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:01:10'),
(1569, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:01:13'),
(1570, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:01:21'),
(1571, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:01:26'),
(1572, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:01:31'),
(1573, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:01:35'),
(1574, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:01:40'),
(1575, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:01:44'),
(1576, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:01:57'),
(1577, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:04:25'),
(1578, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:04:32'),
(1579, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:15:54'),
(1580, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:16:11'),
(1581, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:20:08'),
(1582, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:20:21'),
(1583, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:20:27'),
(1584, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:20:31'),
(1585, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:54:51'),
(1586, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:54:58'),
(1587, 491, 'Dr. Jobert Valentino', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:55:10'),
(1588, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:55:34'),
(1589, 492, 'Dr. Jonathan Estrada', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:55:43'),
(1590, 492, 'Dr. Jonathan Estrada', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 15:56:00'),
(1591, 492, 'Dr. Jonathan Estrada', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:13:14'),
(1592, 493, 'Dr. Michelle Guevarra', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:13:20'),
(1593, 493, 'Dr. Michelle Guevarra', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #23', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:13:29'),
(1594, 493, 'Dr. Michelle Guevarra', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:14:44'),
(1595, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:15:03'),
(1596, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:24:40'),
(1597, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:24:51'),
(1598, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:24:54'),
(1599, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:25:03'),
(1600, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:47:18'),
(1601, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:47:24'),
(1602, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:48:43'),
(1603, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 16:48:52'),
(1604, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:14:40'),
(1605, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:14:48'),
(1606, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:30:03'),
(1607, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:30:10'),
(1608, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:40:05'),
(1609, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:40:27'),
(1610, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:42:33'),
(1611, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:42:41'),
(1612, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:42:49'),
(1613, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:42:57'),
(1614, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:43:06'),
(1615, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:43:14'),
(1616, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:43:34'),
(1617, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:43:42'),
(1618, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:43:47'),
(1619, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:44:37'),
(1620, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:44:42'),
(1621, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:44:45'),
(1622, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:59:32'),
(1623, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 17:59:51'),
(1624, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:04:31'),
(1625, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 18:04:45'),
(1626, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:00:12'),
(1627, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:00:22'),
(1628, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:00:32'),
(1629, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:00:39'),
(1630, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:00:51'),
(1631, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:00:59'),
(1632, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:01:07'),
(1633, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:01:14'),
(1634, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:01:20'),
(1635, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:06:49'),
(1636, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:06:54'),
(1637, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:09:28'),
(1638, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:14:27'),
(1639, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 19:14:33'),
(1640, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:25:41'),
(1641, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:25:48'),
(1642, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:26:42'),
(1643, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:26:51'),
(1644, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:27:00'),
(1645, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:27:07'),
(1646, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:27:14'),
(1647, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:27:23'),
(1648, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:27:33'),
(1649, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:27:38'),
(1650, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:27:42'),
(1651, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:28:03'),
(1652, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:42:19'),
(1653, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:42:22'),
(1654, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:42:25'),
(1655, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:42:30'),
(1656, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:42:38'),
(1657, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:42:53'),
(1658, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:43:06'),
(1659, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:43:11'),
(1660, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:43:15'),
(1661, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:43:22'),
(1662, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:43:25'),
(1663, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:43:30'),
(1664, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:43:39'),
(1665, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:44:25'),
(1666, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:44:36'),
(1667, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:44:45'),
(1668, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:46:22'),
(1669, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:46:37'),
(1670, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:48:21'),
(1671, 492, 'Dr. Jonathan Estrada', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 21:48:27'),
(1672, 492, 'Dr. Jonathan Estrada', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:04:58'),
(1673, 492, 'Dr. Jonathan Estrada', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:05:04'),
(1674, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:05:11'),
(1675, 491, 'Dr. Jobert Valentino', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:05:36'),
(1676, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:05:39'),
(1677, 493, 'Dr. Michelle Guevarra', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:05:45'),
(1678, 493, 'Dr. Michelle Guevarra', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:05:53'),
(1679, 493, 'Dr. Michelle Guevarra', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:07:39'),
(1680, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:07:53'),
(1681, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:30:05'),
(1682, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:30:16'),
(1683, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:31:35'),
(1684, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:31:42'),
(1685, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:52:16'),
(1686, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-16 22:52:22'),
(1687, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:18:50'),
(1688, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:18:59'),
(1689, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:19:44'),
(1690, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:25:23'),
(1691, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:25:31'),
(1692, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:25:40'),
(1693, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:25:50'),
(1694, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:26:31'),
(1695, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:26:37'),
(1696, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:26:46'),
(1697, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:26:52'),
(1698, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:27:04'),
(1699, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:27:10'),
(1700, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:27:15'),
(1701, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:27:21'),
(1702, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:27:41'),
(1703, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:27:48'),
(1704, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:28:17'),
(1705, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:28:29'),
(1706, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:29:01'),
(1707, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:29:12'),
(1708, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:05'),
(1709, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:08'),
(1710, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:11'),
(1711, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:17'),
(1712, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:23'),
(1713, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:26'),
(1714, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:31');
INSERT INTO `sms_activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1715, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:39'),
(1716, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:46'),
(1717, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:49'),
(1718, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:53'),
(1719, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:33:57'),
(1720, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:34:03'),
(1721, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:34:19'),
(1722, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:34:25'),
(1723, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:34:38'),
(1724, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:34:46'),
(1725, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:35:44'),
(1726, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:36:01'),
(1727, 491, 'Dr. Jobert Valentino', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:36:13'),
(1728, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:36:16'),
(1729, 492, 'Dr. Jonathan Estrada', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:36:24'),
(1730, 492, 'Dr. Jonathan Estrada', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:36:33'),
(1731, 492, 'Dr. Jonathan Estrada', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:36:35'),
(1732, 493, 'Dr. Michelle Guevarra', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:36:43'),
(1733, 493, 'Dr. Michelle Guevarra', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:36:53'),
(1734, 493, 'Dr. Michelle Guevarra', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:38:01'),
(1735, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:38:09'),
(1736, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:45:24'),
(1737, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:45:30'),
(1738, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Approved final manuscript submission #2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:45:48'),
(1739, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:46:03'),
(1740, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:46:09'),
(1741, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:46:32'),
(1742, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:46:38'),
(1743, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:46:51'),
(1744, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:46:58'),
(1745, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:52:35'),
(1746, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:53:03'),
(1747, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:53:16'),
(1748, 492, 'Dr. Jonathan Estrada', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:53:22'),
(1749, 492, 'Dr. Jonathan Estrada', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:53:34'),
(1750, 493, 'Dr. Michelle Guevarra', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:53:40'),
(1751, 493, 'Dr. Michelle Guevarra', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:54:48'),
(1752, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:54:54'),
(1753, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Approved final manuscript for research group #62', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:55:07'),
(1754, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Updated publication record #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:55:24'),
(1755, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Created publication record for research group #62', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 15:55:34'),
(1756, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Updated publication record #2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:04:54'),
(1757, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Updated publication record #2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:04:57'),
(1758, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Updated publication record #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:05:00'),
(1759, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:05:37'),
(1760, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:05:47'),
(1761, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:05:51'),
(1762, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:06:01'),
(1763, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Updated publication record #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:11:20'),
(1764, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Created publication record for research group #62', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:12:27'),
(1765, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Updated publication record #3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:12:36'),
(1766, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Created publication record for research group #62', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:13:42'),
(1767, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Updated publication record #4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:14:09'),
(1768, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:26:01'),
(1769, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:26:10'),
(1770, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:26:37'),
(1771, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:27:02'),
(1772, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:27:10'),
(1773, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:27:20'),
(1774, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:27:30'),
(1775, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:27:36'),
(1776, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:27:43'),
(1777, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:27:47'),
(1778, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:27:56'),
(1779, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:28:03'),
(1780, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:28:11'),
(1781, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:28:16'),
(1782, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:28:19'),
(1783, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:28:25'),
(1784, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:29:40'),
(1785, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:29:53'),
(1786, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:12'),
(1787, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:15'),
(1788, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:18'),
(1789, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:22'),
(1790, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:28'),
(1791, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:35'),
(1792, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:40'),
(1793, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:44'),
(1794, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:48'),
(1795, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:52'),
(1796, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:30:58'),
(1797, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:31:15'),
(1798, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:31:29'),
(1799, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:32:18'),
(1800, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:32:24'),
(1801, 491, 'Dr. Jobert Valentino', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:32:34'),
(1802, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:32:37'),
(1803, 492, 'Dr. Jonathan Estrada', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:32:43'),
(1804, 492, 'Dr. Jonathan Estrada', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:32:54'),
(1805, 492, 'Dr. Jonathan Estrada', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:32:57'),
(1806, 493, 'Dr. Michelle Guevarra', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:33:05'),
(1807, 493, 'Dr. Michelle Guevarra', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:33:21'),
(1808, 493, 'Dr. Michelle Guevarra', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:33:29'),
(1809, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:33:38'),
(1810, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:34:48'),
(1811, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:34:56'),
(1812, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Approved final manuscript submission #3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:35:30'),
(1813, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:35:46'),
(1814, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:36:01'),
(1815, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:37:10'),
(1816, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:37:17'),
(1817, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:37:30'),
(1818, 492, 'Dr. Jonathan Estrada', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:37:55'),
(1819, 492, 'Dr. Jonathan Estrada', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:38:07'),
(1820, 493, 'Dr. Michelle Guevarra', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:38:13'),
(1821, 493, 'Dr. Michelle Guevarra', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:38:23'),
(1822, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:38:29'),
(1823, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Approved final manuscript for research group #63', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:39:02'),
(1824, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Created publication record for research group #63', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-28 16:39:18'),
(1825, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-08-28 18:03:13'),
(1826, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 04:42:23'),
(1827, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 04:43:24'),
(1828, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 04:45:05'),
(1829, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 04:54:30'),
(1830, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 04:55:02'),
(1831, 3, 'CRAD Officer', 'crad_officer', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:14:39'),
(1832, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:15:06'),
(1833, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:16:41'),
(1834, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:21:00'),
(1835, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:21:22'),
(1836, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:21:56'),
(1837, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:22:17'),
(1838, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:22:34'),
(1839, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:25:58'),
(1840, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:26:12'),
(1841, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:26:33'),
(1842, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:27:01'),
(1843, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:27:08'),
(1844, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:27:23'),
(1845, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:27:38'),
(1846, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:30:20'),
(1847, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:30:27'),
(1848, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:30:33'),
(1849, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:30:57'),
(1850, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:31:13'),
(1851, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:31:36'),
(1852, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:32:02'),
(1853, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:32:07'),
(1854, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:32:16'),
(1855, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:32:23'),
(1856, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:32:32'),
(1857, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:33:14'),
(1858, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:33:47'),
(1859, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:34:49'),
(1860, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:35:04'),
(1861, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:35:15'),
(1862, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:35:24'),
(1863, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:35:56'),
(1864, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:36:13'),
(1865, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:37:03'),
(1866, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:37:15'),
(1867, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:47:35'),
(1868, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:48:02'),
(1869, 475, 'Grammarian', 'grammarian', 'view', 'faculty', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:48:16'),
(1870, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:48:31'),
(1871, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:48:42'),
(1872, 3, 'CRAD Officer', 'crad_officer', 'view', 'crad', 'Opened Security Settings', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:48:51'),
(1873, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:50:00'),
(1874, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:50:17'),
(1875, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 05:50:26'),
(1876, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:03:20'),
(1877, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:03:35'),
(1878, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:03:51'),
(1879, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:03:59'),
(1880, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:04:33'),
(1881, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:06:22'),
(1882, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:07:50'),
(1883, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:07:59'),
(1884, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:08:17'),
(1885, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:08:39'),
(1886, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:09:00'),
(1887, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:10:46'),
(1888, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:11:10'),
(1889, 491, 'Dr. Jobert Valentino', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:11:32'),
(1890, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:12:11'),
(1891, 492, 'Dr. Jonathan Estrada', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:12:53'),
(1892, 492, 'Dr. Jonathan Estrada', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:13:13'),
(1893, 492, 'Dr. Jonathan Estrada', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:13:25'),
(1894, 493, 'Dr. Michelle Guevarra', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:13:43'),
(1895, 493, 'Dr. Michelle Guevarra', 'panel', 'create', 'faculty', 'Submitted Pre-Oral Defense evaluation for schedule #48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:14:04'),
(1896, 493, 'Dr. Michelle Guevarra', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:14:16'),
(1897, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:14:46'),
(1898, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:16:45'),
(1899, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:16:57'),
(1900, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Approved final manuscript submission #4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:17:25'),
(1901, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:17:38'),
(1902, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:18:13'),
(1903, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:18:19'),
(1904, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:18:29'),
(1905, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:22:43'),
(1906, 491, 'Dr. Jobert Valentino', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:27:41'),
(1907, 491, 'Dr. Jobert Valentino', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:28:05'),
(1908, 492, 'Dr. Jonathan Estrada', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:28:19'),
(1909, 492, 'Dr. Jonathan Estrada', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:30:59'),
(1910, 493, 'Dr. Michelle Guevarra', 'panel', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:31:20'),
(1911, 493, 'Dr. Michelle Guevarra', 'panel', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:31:51'),
(1912, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:32:06'),
(1913, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Approved final manuscript for research group #64', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:32:22'),
(1914, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Created publication record for research group #64', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:32:29'),
(1915, 3, 'CRAD Officer', 'crad_officer', 'update', 'crad', 'Updated publication record #6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:33:01'),
(1916, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:57:26'),
(1917, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 06:57:52'),
(1918, NULL, 'System', 'admin', 'seed', 'System', 'Official role accounts seeded', 'cli', NULL, '2026-08-31 07:04:36');
INSERT INTO `sms_activity_logs` (`id`, `user_id`, `user_name`, `role_key`, `action`, `module_key`, `detail`, `ip_address`, `user_agent`, `created_at`) VALUES
(1919, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user reviewcommittee', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:21:18'),
(1920, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:22:38'),
(1921, 766, 'Review Committee Member', 'review_committee', 'login', 'crad_grant', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:22:58'),
(1922, 766, 'Review Committee Member', 'review_committee', 'logout', 'crad_grant', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:45:34'),
(1923, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:46:13'),
(1924, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:46:26'),
(1925, 9, 'Student User', 'student', 'lockout', 'System', 'Login locked after failed attempts', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:47:04'),
(1926, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:47:04'),
(1927, 1, 'Super Admin', 'superadmin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:47:39'),
(1928, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:48:05'),
(1929, 3, 'CRAD Officer', 'crad_officer', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:48:18'),
(1930, 1, 'Super Admin', 'superadmin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:50:07'),
(1931, NULL, 'System', 'admin', 'seed', 'System', 'Official role accounts seeded', 'cli', NULL, '2026-08-31 07:50:20'),
(1932, 1, 'Super Admin', 'superadmin', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:51:14'),
(1933, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:51:21'),
(1934, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user superadmin', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:53:31'),
(1935, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:53:39'),
(1936, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:53:51'),
(1937, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:53:57'),
(1938, 9, 'Student User', 'student', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:54:11'),
(1939, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:54:34'),
(1940, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user s230000001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:55:08'),
(1941, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user s230000001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:55:09'),
(1942, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:55:12'),
(1943, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:55:25'),
(1944, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 07:55:40'),
(1945, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:01:34'),
(1946, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:01:48'),
(1947, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:02:24'),
(1948, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:02:33'),
(1949, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:03:27'),
(1950, 766, 'Review Committee Member', 'review_committee', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:03:43'),
(1951, 1, 'Super Admin', 'superadmin', 'login', 'user-management', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:04:01'),
(1952, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user researchcoordinator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:04:22'),
(1953, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user reviewcommittee', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:04:41'),
(1954, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user rsantos', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:04:55'),
(1955, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user jobert.valentino', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:05:01'),
(1956, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user michelle.guevarra', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:05:52'),
(1957, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user michelle.guevarra', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:05:53'),
(1958, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user jonathan.estrada', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:06:01'),
(1959, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user jobert.valentino', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:06:08'),
(1960, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user grammarian', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:06:20'),
(1961, 1, 'Super Admin', 'superadmin', 'update', 'user-management', 'Updated user researchdirector', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:06:29'),
(1962, 1, 'Super Admin', 'superadmin', 'logout', 'user-management', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:07:55'),
(1963, 766, 'Review Committee Member', 'review_committee', 'login', 'crad_grant', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:08:06'),
(1964, 766, 'Review Committee Member', 'review_committee', 'logout', 'crad_grant', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:37:58'),
(1965, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:38:09'),
(1966, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:46:46'),
(1967, 766, 'Review Committee Member', 'review_committee', 'login', 'crad_grant', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:46:57'),
(1968, 766, 'Review Committee Member', 'review_committee', 'logout', 'crad_grant', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:47:48'),
(1969, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:48:10'),
(1970, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:52:08'),
(1971, 766, 'Review Committee Member', 'review_committee', 'login', 'crad_grant', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 08:52:25'),
(1972, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:22:59'),
(1973, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:23:22'),
(1974, 766, 'Review Committee Member', 'review_committee', 'logout', 'crad_grant', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:24:07'),
(1975, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:24:19'),
(1976, 116, 'Research Director', 'research_director', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:24:22'),
(1977, 9, 'Student User', 'student', 'login', 'student_portal', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:24:42'),
(1978, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:26:49'),
(1979, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:27:11'),
(1980, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:27:36'),
(1981, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:28:00'),
(1982, 3, 'CRAD Officer', 'crad_officer', 'create', 'crad', 'Generated title approval research group number: RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:28:17'),
(1983, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:28:36'),
(1984, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:28:53'),
(1985, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:29:14'),
(1986, 3, 'CRAD Officer', 'crad_officer', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:29:26'),
(1987, 3, 'CRAD Officer', 'crad_officer', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:29:35'),
(1988, 3, 'CRAD Officer', 'crad_officer', 'assign', 'crad', 'Assigned coordinator \"Mrs. Kris Guevarra\" to research group RG-2026-001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:29:54'),
(1989, 3, 'CRAD Officer', 'crad_officer', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:32:52'),
(1990, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:33:02'),
(1991, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:37:54'),
(1992, 475, 'Grammarian', 'grammarian', 'login_failed', 'System', 'Invalid password', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:38:24'),
(1993, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:38:34'),
(1994, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:39:14'),
(1995, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:39:24'),
(1996, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:39:35'),
(1997, 475, 'Grammarian', 'grammarian', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:39:44'),
(1998, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 1 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:41:02'),
(1999, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 2 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:41:14'),
(2000, 9, 'Student User', 'student', 'create', 'student_portal', 'Chapter 3 Version 1 submitted for grammarian evaluation', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:41:20'),
(2001, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:41:37'),
(2002, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 1 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:42:10'),
(2003, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:42:16'),
(2004, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 2 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:42:25'),
(2005, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Started review for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:42:30'),
(2006, 475, 'Grammarian', 'grammarian', 'update', 'faculty', 'Submitted evaluation for Chapter 3 Version 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:42:37'),
(2007, 475, 'Grammarian', 'grammarian', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:42:40'),
(2008, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'login', 'crad', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:43:13'),
(2009, 40, 'Mrs. Kris Guevarra', 'research_coordinator', 'logout', 'crad', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:44:45'),
(2010, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 09:44:56'),
(2011, 9, 'Student User', 'student', 'logout', 'student_portal', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 10:13:35'),
(2012, 54, 'Dr. Roberto M. Santos', 'adviser', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 10:13:45'),
(2013, 54, 'Dr. Roberto M. Santos', 'adviser', 'logout', 'faculty', 'Logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 10:16:53'),
(2014, 116, 'Research Director', 'research_director', 'login', 'faculty', 'Logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 OPR/135.0.0.0', '2026-08-31 10:17:13');

-- --------------------------------------------------------

--
-- Table structure for table `sms_login_throttles`
--

CREATE TABLE `sms_login_throttles` (
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
-- Table structure for table `sms_password_resets`
--

CREATE TABLE `sms_password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_password_resets`
--

INSERT INTO `sms_password_resets` (`id`, `user_id`, `token_hash`, `expires_at`, `used_at`, `created_ip`, `created_at`) VALUES
(2, 1, '550d259303762ee9ce8b5378b3b6b1e212a4b5cf796b005404689bb4c5596866', '2026-08-06 14:05:55', '2026-08-06 13:06:28', '::1', '2026-08-06 13:05:55'),
(3, 9, '691edab739335bc353c7ecaa7d183393ea51e47def723d4f3e68adccdd10fcb9', '2026-08-06 14:18:23', '2026-08-06 13:18:33', '::1', '2026-08-06 13:18:23'),
(4, 9, '55eabae148518a30c44e17552b678572afdda8d79fc2c59f95152780545da52b', '2026-08-06 14:18:33', NULL, '::1', '2026-08-06 13:18:33');

-- --------------------------------------------------------

--
-- Table structure for table `sms_password_reset_requests`
--

CREATE TABLE `sms_password_reset_requests` (
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

-- --------------------------------------------------------

--
-- Table structure for table `sms_roles`
--

CREATE TABLE `sms_roles` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `label` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_roles`
--

INSERT INTO `sms_roles` (`id`, `role_key`, `label`, `description`, `is_system`, `created_at`) VALUES
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
(901, 'review_committee', 'Review Committee', 'Grant proposal review and rubric evaluation', 1, '2026-08-31 07:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `sms_role_permissions`
--

CREATE TABLE `sms_role_permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `module_key` varchar(60) NOT NULL,
  `granted` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_role_permissions`
--

INSERT INTO `sms_role_permissions` (`id`, `role_key`, `module_key`, `granted`, `updated_at`) VALUES
(818, 'superadmin', 'user-management', 1, '2026-08-31 07:50:18'),
(819, 'admission', 'enrollment', 1, '2026-08-31 07:50:18'),
(820, 'registrar', 'registrar', 1, '2026-08-31 07:50:18'),
(821, 'registrar', 'curriculum', 1, '2026-08-31 07:50:18'),
(822, 'registrar', 'scheduling', 1, '2026-08-31 07:50:18'),
(823, 'crad_officer', 'crad', 1, '2026-08-31 07:50:18'),
(824, 'research_coordinator', 'crad', 1, '2026-08-31 07:50:18'),
(825, 'research_director', 'faculty', 1, '2026-08-31 07:50:18'),
(826, 'grammarian', 'faculty', 1, '2026-08-31 07:50:18'),
(827, 'review_committee', 'crad_grant', 1, '2026-08-31 07:50:19'),
(828, 'panel', 'faculty', 1, '2026-08-31 07:50:19'),
(829, 'finance', 'payment', 1, '2026-08-31 07:50:19'),
(830, 'osa', 'cocurricular', 1, '2026-08-31 07:50:19'),
(831, 'it_office', 'lms', 1, '2026-08-31 07:50:19'),
(832, 'qa', 'accreditation', 1, '2026-08-31 07:50:19'),
(833, 'hr', 'faculty', 1, '2026-08-31 07:50:19'),
(834, 'student', 'student_portal', 1, '2026-08-31 07:50:19'),
(835, 'sms_admin', 'enrollment', 1, '2026-08-31 07:51:28'),
(836, 'sms_admin', 'registrar', 1, '2026-08-31 07:51:28'),
(837, 'sms_admin', 'curriculum', 1, '2026-08-31 07:51:28'),
(838, 'sms_admin', 'accreditation', 1, '2026-08-31 07:51:28'),
(839, 'sms_admin', 'payment', 1, '2026-08-31 07:51:28'),
(840, 'sms_admin', 'faculty', 1, '2026-08-31 07:51:28'),
(841, 'sms_admin', 'scheduling', 1, '2026-08-31 07:51:28'),
(842, 'sms_admin', 'cocurricular', 1, '2026-08-31 07:51:28'),
(843, 'sms_admin', 'lms', 1, '2026-08-31 07:51:28'),
(844, 'sms_admin', 'crad', 1, '2026-08-31 07:51:28'),
(845, 'adviser', 'faculty', 1, '2026-08-31 07:51:29'),
(849, 'research_grant', 'crad_grant', 1, '2026-08-31 07:51:29');

-- --------------------------------------------------------

--
-- Table structure for table `sms_security_otps`
--

CREATE TABLE `sms_security_otps` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `purpose` varchar(40) NOT NULL,
  `code_hash` char(64) NOT NULL,
  `module_key` varchar(60) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_security_otps`
--

INSERT INTO `sms_security_otps` (`id`, `user_id`, `purpose`, `code_hash`, `module_key`, `expires_at`, `used_at`, `created_at`) VALUES
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
(32, 1, 'passkey_remove', 'df8c90265057ad814bde1ac13e69cd5d1aa480375cd8a00f9e9b4a29e917b7fe', NULL, '2026-08-07 14:33:29', NULL, '2026-08-07 14:23:29');

-- --------------------------------------------------------

--
-- Table structure for table `sms_system_settings`
--

CREATE TABLE `sms_system_settings` (
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_system_settings`
--

INSERT INTO `sms_system_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('crad_active_term', '', '2026-08-28 07:17:54'),
('csrf_enabled', '1', '2026-07-22 22:24:44'),
('lockout_minutes', '1', '2026-07-23 08:05:06'),
('lockout_seconds', '15', '2026-07-23 08:05:06'),
('lockout_unit', 'seconds', '2026-07-23 08:05:06'),
('lockout_value', '15', '2026-07-23 08:05:06'),
('mail_admin_email', 'j14677365@gmail.com', '2026-07-23 10:34:27'),
('mail_from_email', 'noreply@bestlink.edu.ph', '2026-07-23 10:33:25'),
('mail_from_name', 'SMS 2', '2026-07-23 10:33:25'),
('mail_show_link_on_failure', '0', '2026-07-23 10:34:27'),
('max_failed_logins', '3', '2026-07-23 07:33:05'),
('min_password_length', '8', '2026-07-22 22:24:44'),
('module_kick_epoch_crad', '1784849304', '2026-07-23 15:28:24'),
('module_maintenance_crad', '0', '2026-07-23 15:29:22'),
('module_maintenance_msg_crad', 'The system is currently under maintenance. Some services may be temporarily unavailable.\r\n\r\nThank you for your patience and understanding.', '2026-07-23 15:05:14'),
('password_expiry_days', '0', '2026-07-22 22:24:44'),
('require_password_change_first_login', '0', '2026-07-22 22:24:44'),
('session_timeout_minutes', '30', '2026-07-22 22:24:44'),
('smtp_encryption', 'tls', '2026-07-23 10:33:25'),
('smtp_host', 'smtp.gmail.com', '2026-07-23 10:51:48'),
('smtp_password', 'sms2enc1.BnNN43RIftF9bLKe7buHTa6/qaxuGXWg5XruC7mKh67ZYei7aPH7AeOKNpdXJ7A=', '2026-08-06 12:08:17'),
('smtp_port', '587', '2026-07-23 10:33:25'),
('smtp_username', 'j14677365@gmail.com', '2026-07-23 10:33:25');

-- --------------------------------------------------------

--
-- Table structure for table `sms_users`
--

CREATE TABLE `sms_users` (
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
-- Dumping data for table `sms_users`
--

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `last_login_at`, `last_seen_at`, `last_login_ip`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', 'superadmin@bestlink.edu.ph', '$2y$10$yeuumNmFTLjaAsv8N8hNu.QgOpg9DlrTiKrbWet2Q01jtvUasOfAW', 'Super Admin', 'superadmin', NULL, 'active', 0, 0, NULL, '2026-08-31 07:53:31', '2026-08-31 08:04:01', NULL, '::1', NULL, '2026-07-22 22:53:59', '2026-08-31 08:07:55'),
(2, 'registrar', 'registrar@bestlink.edu.ph', '$2y$10$/HmOuAP54dAuUkNOyNJo/e2GwrAszJqpF0sQmGvjofAtM/.6tcp.m', 'Registrar', 'registrar', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:19', '2026-08-08 22:06:54', NULL, '::1', NULL, '2026-07-22 22:53:59', '2026-08-31 07:50:19'),
(3, 'cradofficer', 'cradofficer@bestlink.edu.ph', '$2y$10$bws9bC4veetad0dmgt0kjeRl1nTQvQMGHXVsVEmZSLtFuSVCpMt2i', 'CRAD Officer', 'crad_officer', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:19', '2026-08-31 09:29:35', NULL, '::1', NULL, '2026-07-22 22:53:59', '2026-08-31 09:32:52'),
(4, 'finance', 'finance@bestlink.edu.ph', '$2y$10$ryCgB4R4g9MkgK5wBln8WO279xeqhztZIqEgUCEOpDMyA2wXsmkyW', 'Finance', 'finance', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:19', '2026-08-11 17:31:31', NULL, '::1', NULL, '2026-07-22 22:54:00', '2026-08-31 07:50:19'),
(5, 'studentaffairs', 'studentaffairs@bestlink.edu.ph', '$2y$10$ykS9zsSeg8ESbJDrnyaixuRg.OYKWUljfEzgDhwBWsn4MYjdRR9O2', 'Student Affairs', 'osa', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:19', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-08-31 07:50:19'),
(6, 'itofficer', 'itofficer@bestlink.edu.ph', '$2y$10$h1GQBrr0K5SM8whZCT2QxOmvpIN2aPKslctCSX3VMflxoiHVIdWGC', 'IT Officer', 'it_office', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:19', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-08-31 07:50:19'),
(7, 'qualityassurance', 'qualityassurance@bestlink.edu.ph', '$2y$10$cqKm0cN1jMdxpdS5l3yee.ygI3KG05tRBGw5cyagSwNFT.6YaytVq', 'Quality Assurance', 'qa', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:20', NULL, NULL, NULL, NULL, '2026-07-22 22:54:00', '2026-08-31 07:50:20'),
(8, 'dean', 'dean@bestlink.edu.ph', '$2y$10$WU3FSM1vVIz3HqzXEHAWHOdKlvImfKYlOiGSd7r1XWIgF4IVJdtci', 'Dean', 'hr', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:20', '2026-08-07 14:11:03', NULL, '::1', NULL, '2026-07-22 22:54:00', '2026-08-31 07:50:20'),
(9, 's230000001', 's230000001@bestlink.edu.ph', '$2y$10$oFkiHM6jHKgiRWylpzl.7OiCMx627rzLUmUbyQhZYQQ63YgGq/gsy', 'Student User', 'student', 'S230000001', 'active', 0, 0, NULL, '2026-08-31 07:55:09', '2026-08-31 09:24:42', NULL, '::1', NULL, '2026-07-22 22:54:00', '2026-08-31 10:13:35'),
(20, 'admission', 'admission@bestlink.edu.ph', '$2y$10$1M./oyAWOwzHhIjWoxGCWu5wm/6F/Jc3bzmYeF7hLt/jnhzg6KW9u', 'Admission', 'admission', NULL, 'active', 0, 0, NULL, '2026-08-31 07:50:19', NULL, NULL, NULL, NULL, '2026-08-08 17:25:20', '2026-08-31 07:50:19'),
(40, 'researchcoordinator', 'researchcoordinator@bestlink.edu.ph', '$2y$10$f6AGY/ZDFdykQTCiSK5YYePxBGti0SMMIqIOBuLU0OtnNY6Xwpcn2', 'Mrs. Kris Guevarra', 'research_coordinator', NULL, 'active', 0, 0, NULL, '2026-08-31 08:04:22', '2026-08-31 09:43:13', NULL, '::1', NULL, '2026-08-08 18:09:48', '2026-08-31 09:44:45'),
(54, 'rsantos', 'rsantos@bestlink.edu.ph', '$2y$10$8K5JenMWtmwLwKeqq2086.AXlEED4PzOs/BZvilw.zVzq2Wdc0M.u', 'Dr. Roberto M. Santos', 'adviser', NULL, 'active', 0, 0, NULL, '2026-08-31 08:04:55', '2026-08-31 10:13:45', NULL, '::1', NULL, '2026-08-08 21:35:14', '2026-08-31 10:16:53'),
(116, 'researchdirector', 'research.director@bestlink.edu.ph', '$2y$10$xyO2oPPFEo9x3xs.Jpx12.TyHHtvsiniPa7/qVH6AIiQI.v5O14ZK', 'Research Director', 'research_director', NULL, 'active', 0, 0, NULL, '2026-08-31 08:06:29', '2026-08-31 10:17:13', '2026-09-01 02:17:59', '::1', NULL, '2026-08-09 19:31:14', '2026-08-31 10:17:59'),
(222, 'researchgrant', 'researchgrant@bestlink.edu.ph', '$2y$10$Kmx3XLgjIdLL3S4rP0Bs.uKL0oqNZyDRN4DpDwhtbYc249mAcYx8i', 'Research Grant', 'research_grant', NULL, 'active', 0, 0, NULL, '2026-08-10 20:07:43', '2026-08-13 08:34:27', NULL, '::1', NULL, '2026-08-10 20:01:49', '2026-08-13 08:34:35'),
(475, 'grammarian', 'grammarian@bestlink.edu.ph', '$2y$10$DOubhW7dlaxRDFenQkOz2u0I.zVI3mF17NAGenmLyYU8cvYS4x9CS', 'Grammarian', 'grammarian', NULL, 'active', 0, 0, NULL, '2026-08-31 08:06:20', '2026-08-31 09:39:44', NULL, '::1', NULL, '2026-08-14 11:06:49', '2026-08-31 09:42:40'),
(491, 'jobert.valentino', 'jobert.valentino@bestlink.edu.ph', '$2y$10$AkCzL7RmKfYgXNwrujBK6.nJh7BRnDK3Lb.iwkgJ8r8SVYSJU5.Ge', 'Dr. Jobert Valentino', 'panel', NULL, 'active', 0, 0, NULL, '2026-08-31 08:06:08', '2026-08-31 06:27:41', NULL, '::1', NULL, '2026-08-15 17:07:16', '2026-08-31 08:06:08'),
(492, 'jonathan.estrada', 'jonathan.estrada@bestlink.edu.ph', '$2y$10$OkM9HAk8zWpIlGC.L4h/6.g9CbsjrWcZUt5PAUyUh2OF.HDGRMfyi', 'Dr. Jonathan Estrada', 'panel', NULL, 'active', 0, 0, NULL, '2026-08-31 08:06:01', '2026-08-31 06:28:19', NULL, '::1', NULL, '2026-08-15 17:07:16', '2026-08-31 08:06:01'),
(493, 'michelle.guevarra', 'michelle.guevarra@bestlink.edu.ph', '$2y$10$Y6XHywAVrKL.JSBrpkSMbe8RAtYrHjL9D0eCtprI/p7ViGTwL2DBi', 'Dr. Michelle Guevarra', 'panel', NULL, 'active', 0, 0, NULL, '2026-08-31 08:05:53', '2026-08-31 06:31:20', NULL, '::1', NULL, '2026-08-15 17:07:16', '2026-08-31 08:05:53'),
(758, 'admin', 'admin@bestlink.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'sms_admin', NULL, 'active', 0, 0, NULL, '2026-08-18 00:38:50', NULL, NULL, NULL, NULL, '2026-08-18 00:38:50', '2026-08-18 00:38:50'),
(766, 'reviewcommittee', 'reviewcommittee@bestlink.edu.ph', '$2y$10$GQbE2PiU0TKW3DhZ7bPt3uqTMc5XAOfBMutgb9KpnN6lsVS75rev6', 'Review Committee Member', 'review_committee', NULL, 'active', 0, 0, NULL, '2026-08-31 08:04:41', '2026-08-31 08:52:25', NULL, '::1', NULL, '2026-08-31 07:04:36', '2026-08-31 09:24:07');

-- --------------------------------------------------------

--
-- Table structure for table `sms_user_authenticators`
--

CREATE TABLE `sms_user_authenticators` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `secret` varchar(512) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `pending_secret` varchar(512) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sms_user_authenticators`
--

INSERT INTO `sms_user_authenticators` (`user_id`, `secret`, `enabled`, `pending_secret`, `confirmed_at`, `updated_at`, `created_at`) VALUES
(222, 'sms2enc1.R966vl8cmeyNxW5So6wOrgQpEEYqOBmBitSjC4TY8JIUf66LRmaaCp7tzcsSHXqAkL8KPzQGzhciEiY5', 0, NULL, NULL, '2026-08-10 20:27:16', '2026-08-10 20:25:01');

-- --------------------------------------------------------

--
-- Table structure for table `sms_user_passkeys`
--

CREATE TABLE `sms_user_passkeys` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `credential_id` varchar(255) NOT NULL,
  `public_key` text NOT NULL,
  `sign_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `device_name` varchar(120) NOT NULL DEFAULT 'Passkey',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sms_activity_logs`
--
ALTER TABLE `sms_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_user` (`user_id`),
  ADD KEY `idx_logs_action` (`action`),
  ADD KEY `idx_logs_created` (`created_at`);

--
-- Indexes for table `sms_password_resets`
--
ALTER TABLE `sms_password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reset_user` (`user_id`),
  ADD KEY `idx_reset_token` (`token_hash`),
  ADD KEY `idx_reset_expires` (`expires_at`);

--
-- Indexes for table `sms_password_reset_requests`
--
ALTER TABLE `sms_password_reset_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_prr_user` (`user_id`),
  ADD KEY `idx_prr_status` (`status`),
  ADD KEY `idx_prr_module` (`module_key`),
  ADD KEY `fk_prr_admin` (`admin_id`);

--
-- Indexes for table `sms_roles`
--
ALTER TABLE `sms_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_roles_key` (`role_key`);

--
-- Indexes for table `sms_role_permissions`
--
ALTER TABLE `sms_role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_role_module` (`role_key`,`module_key`),
  ADD KEY `idx_perm_module` (`module_key`);

--
-- Indexes for table `sms_security_otps`
--
ALTER TABLE `sms_security_otps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_system_settings`
--
ALTER TABLE `sms_system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `sms_users`
--
ALTER TABLE `sms_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role_key`),
  ADD KEY `idx_users_status` (`status`),
  ADD KEY `idx_users_student_id` (`student_id`),
  ADD KEY `idx_users_last_seen` (`last_seen_at`);

--
-- Indexes for table `sms_user_authenticators`
--
ALTER TABLE `sms_user_authenticators`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `sms_user_passkeys`
--
ALTER TABLE `sms_user_passkeys`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sms_activity_logs`
--
ALTER TABLE `sms_activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2015;

--
-- AUTO_INCREMENT for table `sms_password_resets`
--
ALTER TABLE `sms_password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sms_password_reset_requests`
--
ALTER TABLE `sms_password_reset_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_roles`
--
ALTER TABLE `sms_roles`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1139;

--
-- AUTO_INCREMENT for table `sms_role_permissions`
--
ALTER TABLE `sms_role_permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1075;

--
-- AUTO_INCREMENT for table `sms_security_otps`
--
ALTER TABLE `sms_security_otps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `sms_users`
--
ALTER TABLE `sms_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=990;

--
-- AUTO_INCREMENT for table `sms_user_authenticators`
--
ALTER TABLE `sms_user_authenticators`
  MODIFY `user_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=223;

--
-- AUTO_INCREMENT for table `sms_user_passkeys`
--
ALTER TABLE `sms_user_passkeys`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sms_activity_logs`
--
ALTER TABLE `sms_activity_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `sms_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sms_password_resets`
--
ALTER TABLE `sms_password_resets`
  ADD CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `sms_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_password_reset_requests`
--
ALTER TABLE `sms_password_reset_requests`
  ADD CONSTRAINT `fk_prr_admin` FOREIGN KEY (`admin_id`) REFERENCES `sms_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prr_user` FOREIGN KEY (`user_id`) REFERENCES `sms_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sms_role_permissions`
--
ALTER TABLE `sms_role_permissions`
  ADD CONSTRAINT `fk_perm_role` FOREIGN KEY (`role_key`) REFERENCES `sms_roles` (`role_key`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sms_users`
--
ALTER TABLE `sms_users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_key`) REFERENCES `sms_roles` (`role_key`) ON UPDATE CASCADE;

-- ============================================================
-- CRAD MODULE TABLES (merged from modules/crad/database/crad_db.sql)
-- Import into the same database as SMS2 (DB_DATABASE / DB_NAME).
-- ============================================================

SET FOREIGN_KEY_CHECKS=0;

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
(54, 'S230000001', 9, 'Student User', '2026-09-01', 'College of Computer Studies', 'DEVELOPMENT OF AI ANALYSIS', 'Engineering, Information Technology, and Computing', 'SDG 9 — Industry, Innovation and Infrastructure', 'Science, Technology, Digital Transformation, and Innovation', 'adadsadasdasda', '[[\"User, Student A.\",\"BSIT 4101\",\"OR-2646376\"]]', 'Dr. Roberto M. Santos', 'rsantos@bestlink.edu.ph', 'Mrs. Kris Guevarra', 'TAP-2026-00054', 'Approved', NULL, 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAZAAAABkCAYAAACoy2Z3AAAP6klEQVR4AeydCXhM5x7G3yyU0NaWNEqrvbe2ElHUY2mJEiVqKVF7xNIisVVo4l7bRSupWIqkqK1Nq7QNl8YVIYmtFJeidaXtVRUiEYII2lrvfN+Yc2c020wmM+fMvJ7nf+bMOd/yfr+/Z9585ztzxvU+/5EACZAACZCABQRcwX8kQAIkQAIkYAEBGogF0FiFBKxCgI2QgMYJ0EA0nkDKJwESIAF7EaCB2Is8+yUBEiABjRPQsIFonDzlkwAJkIDGCdBANJ5AyicBEiABexGggdiLPPslAQ0ToHQSEARoIIICgwRIgARIwGwCNBCzkbECCZAACZCAIEADERRsHU7e3507d5GYtDPfqOTdAC39ejg5IQ6fBLRBgAaijTxpWuWHy+NQr3E7JRo0aY++QaH5hqYHSvEk4GQEaCBOlvDSGu6ebw4iMjr2QcRAzCQMMXlaJLKyspUYEtQbERNDHkSofH36qRpSmru7G4YH95X73JBAKRBgk1YkQAOxIkxHbyov74ZiAsIQDAYhXrv2GqIzj5gHEQtvby8lZk4Nw9WsE0pETBSmYYgQnM+8gPSzGRLfpXPHMYwGIllwQwJqJ0ADUXuG7KzP+FJT246BymUocUnKWNobvV7Duk9ilEg7mgpDjA0dalzUZH9TQhJSdu6Tx2I+eFe+ckMCJKANAjQQbeSpVFUaL2gPGDLW5PKT8Tl3d3d06ugHQxjPKpbHRCnHxfniCBbmMXj42ziXkYmEDWswoA8Xz4vDjWVIQC0EaCBqyYQNdbwbtdhkJmE8y9iyNdlESZrRTCI1cb0ywxCzDZOCZr45mfYzxk2cIWvNmj4JzZs1lvvckAAJaIcADUQ7uTJL6bYdu3TrEfpF7Tlzl5jMKuYuWGqylhEhF7RDdYvZoUhK+AzGMwvjtYwKFTzM0lBQ4ZnvLZS36l69mosWzZugzUvNUbZsmYKK8zgJkIAkoL4NDUR9OSm2otxreYoRnEk/Z2ISfQaG6AxEv6gdNe9DZUFbGMLi+bNMTCJCLmrr74oq7ZlA9sVLmL/oI5Qp447BAwORuDkOvj7PF3vMLEgCJKAeAjQQ9eSiSCW//f67yXcnWrd7XbkU5dv8VZP64k4mcZlJxPq4WGVBO013SWpQ/54mZW31ZvqsefBp6o/y5cph05cr8UH0P2zVNfshARIoBQI0kFKAWpwmr+gu3xgvUOfmXpPVbt++bfIN7W6BQ5WZRfVnmpqcq1TpMWXhumsXf5NZxbzIqcq5V/3bwp7/Tv1yBmIcH8Sswh+3bkldrVo0s7Uk9kcCJGBlAjQQKwMtqrmmrQLkrKFZqy4ms4kmD443bNrB5PjuvQeUJsUahJhBGCIpYa2yqB23cqFSTm07GzZthWEce1M2YPXyeWqTSD0kQAIWEKCBWADN0iptOgRC/DUuvoSXc/mKSTM5OVfkesa1a9d1i9liPUK/qL0nOV6ZWWScOmSyluFRvpxJG2p7k5mVjaBh4yHu+nqtc3v8cHgHGj5fV20yqYcESMBCAsU2EAvbZzUjArt3fKWYgfGdTsb7mb8e1hmIMA9hIiHwaVDPqAXt7Kb9dAr1G7fD5i3bUbFiBQT27IKaNaprZwBUSgIkUCQBGkiRiFjAEgIt2nST1Vq3bAbx/ZEeXU0X+eVJbkiABDRNgAai6fSpT/z+A4dR868vokyZMhg9MhhbNn6M2s89qz6hmlJEsSSgTgI0EHXmRZOqJk6ejW6Bw3D9xk1UrOCB2TMmaXIcFE0CJFA8AjSQ4nFiqSIIiIXyFas/h7gNefzoYTidtq+IGjxNAiSgdQLOYCBaz5Gq9d+6fRtLP4qDeDxKpccfw54d8ZgxZYKqNVMcCZCAdQjQQKzD0Slbyc3NQ7deQxAxNVKOP3rOFPg01OZdY3IA3JAACZhFgAZiFi4WNiZQq24LfHvwO9R40hurlkXLW3WNz3OfBEAEDk2ABuLQ6S29wYlv1IvWmzRuiH2pG9Gze2fxlkECJOBEBGggTpRsawx14+ZE1G3kJ79R7+VZFSmJ6/G4bu3DGm2zDRIgAW0RoIGoOl/qEpeXdwPrvtyMC9kX8YSXJ376fre6BFINCZCATQnQQGyKW7udpezah7YdA7Ft+y5EzorAiSPJ2h0MlZMACViFAA3EKhgdu5G9+w6hZ5838cvpdLzg2wCdOraDu7ubYw+ao3N6AgRQNAEaSNGMnLpE1oWLCBo+XjIIG/cWtm6KwzO1asr33JAACTg3ARqIc+e/0NFHL1yGer5+uHz5KoIG9MLUyeNQrtwjhdbhSRIgAechQANxnlybNdKWfj0wO3IR3Nxc0e21jlg0b6ZZ9cHSJEACDk+ABuLwKTZ/gGLmcfbceVkx/vPl+GTFArnPDQmQAAkYE6CBGNNw8v30s+cxYMhYOfO4fv0GLpw5Ar82LZ2cCodPApojYDPBNBCboVZ/RztS9mDLVv3tuV/Hr0bZsmXVL5oKSYAE7EaABmI39OrpODf3GkaOmYwJ4TPxil9rHNqbgJdbN4eLi4t6RFIJCZCA6gjQQFSXEtsLqlW3pfyGuYdHeXQN6OD0vyBo+wywRxLQJgEaiDbzZjXV/YPHyLbEY9i3b1mLIUFvyPfckAAJkEBRBGggRRFy4PPNWnXBvxJTUOvpmvKHoBrUr+PAo+XQSIAErE3A+gZibYVsr1QIjBgdgV/PnJVt79DNPOQONyRAAiRgBgEaiBmwHKWouNvq30eO487duwgPGwVPz6qOMjSOgwRIwIYEaCA2hK2Grm7e/A2bEpLk73l4VquCd8JC1CCLGqxDgK2QgE0J0EBsitv+ncWt3QAR4TrjOHpwG9xc+V/A/lmhAhLQJgF+emgzb2arvnHzJubMjUH4lPdQv15tdOrohwoeHma3wwokQAIkYCBAAzGQAODIu5lZ2YiaFyuHGDywt/xdD/mGGxIgARKwkAANxEJwWqp29+49iFt2heYRwwdixPABYpdBAiRAAiUiQAMpET5tVK5aw0cK9W/fBlGzJ8t9bkhAXQSoRosEaCBazJoZmseGTZOlq3t7ISJslNznhgRIgASsQYAGYg2KKm3j0OFjSEzaKR+KKBbNmzZppFKllEUCJKBFAjQQLWbtz5rzPfLe+4uRfTEHrq4uWPD+9HzL8CAJkAAJWEqABmIpOZXXCwufidRd+zHqzUHIyfhe5WopjwRIQIsEaCBazFoRmpcsXYOVH69HlcqVEDpycBGleZoESKBEBJy4Mg3EwZIvnnH16ecb5KhWLotGzRrV5T43JEACJGBtAjQQaxO1Y3sn035Gh4B+SPvxFOZHTUO7Ni3tqIZdkwAJODoBGoiDZPiPP26ha+BQORrxo1B9e3eT++rfUCEJkIBWCdBAtJq5h3Tfx31cunQZtZ97Fl0DOsDDo/xDJfiWBEiABKxLgAZiXZ52b+3n/55Gys59dtdBASRAAuonUFKFNJCSElRJfReV6KAMEiAB5yFAA3GeXHOkDk7g1q3bSN75jXz6gHgCQa9+IyCeg1bJuwGM492oxQ5OgsOzFQEaiK1Isx/HI2DnES1cshKNXuyI+i+8gnqN28GnWQf07j8SfYNCZSSn7oV4ErNBpqurK17v3hnjQvU3WxiO85UELCVAA7GUnMrqPfLII9i/859SlfgiYZ+BIYiMjpEhD3KjGQLvz1+KqHkf6nIXqwt9Dus0aosqT/qYzCRmzJ6P9LMZyMy8gIsXc3Ap54r83k/ExBDoIxR/e2cM/h4+FrOmT8Ll899j9bJoVKxYQTMsKFTdBFzVLY/qzCEgfmkwft1yeHt74dgPJ7Fg0QrdB1Cs8qHTLXAosrKyZYjbfs1pm2VLRuDatTxkXbgIA3/x+kV8AqrV9P2TMYhnmM2Zu0SXO2EesVi24jOI2YOXVzWIpyqLL4fWeNIbFSvof1FSnDu452ucPJqK44eSdOYR+iBC8M6EkZj09giMGRVcsgGwNgnkQ8COBpKPGh4qMYH2fq2RpvsgEZGwcQ3WfRKDtWv017x37z0gL3WIyx0Brw+Wlzn6DR5d4j7ZgCmBMROmYcCQsZKv4XLSS+17ob7uMpNgb4i3QsNx584d3Lt3TxqEm5ubzJXImYy4GKyPi8UBnTmIfIoQJvHD4R04cSQZKYnrsU5X5plaNdG0VQB8mvnLPvsHj8GgYeMQFjELGeezTMXxHQlYkQANxIow1dbUi019IR7jHtDpFVzNOiFDfDCJY+npGXKxdeu2VGWGYrzQ+vB+5eoN8XCIhVqzYvtOJG7fJWPft4fVhqtIPadOn8E2qV83jiR9LF3xqW6hupFkY2AWtzYeW7YmQ7AR5X/86RSer18br/q3lfno0rk9hgf3k/kw5EVcXsrJOA6RK5EfGf5+so6XZ9V8tdWp/Rd00pU5sn8revUIQLu2LZF3/QZ27flW138KVn28Hg2atFfy6/mUL0TU8WkjZzdCnxpje/IenMvIzHfMPKguAjQQdeWj1NWIDyZhIuKSh/iL1hCrdNfGxaUQQzws5P79+3g4DH9dF/t1UCj6DgqR0b33UGU2ZPiLXO2vfv690Ufq140jSB8RU+boFqrvyt9ccXd3R9zKhcoMMO1YqrystHv7VxDMDfHZ6kWIjpzyMOISvV+5dK7sY9MXK3D0wDb857sU2bchv76N6kuNLi4uyLl8FZHRsXK2UuzcPRivLcoHvzlBPkm6REBY2SYEaCA2way+TipXflyulYj1EhE9u3eWi6ziL2ERhr+Mi/v6dfxqTJ40WomIiaEPrsMbv4bojukjbNxbCB4YqKkQTzaOkAvUobrF6dGYMWWCMosQzC6dO4auXfz/z/UJL3g/4YlHH61os/8AwsQ8q1WR/Yq+RW5F7Er6CtnpR2WImU5x82qPchm/HMKg/j1txowdWU6ABmI5O9Y0IvBy6+YIDxulhP6DNkQxDP17YzPR8n6IbnF6FMaPHmZEgLsk4HwEaCCW5Jx1SIAESIAEQAPhfwISIAESIAGLCNBALMLGSiRAAnYiwG5VRIAGoqJkUAoJkAAJaIkADURL2aJWEiABElARARqIipJhCynsgwRIgASsRYAGYi2SbIcESIAEnIwADcTJEs7hkgAJ2IuA4/VLA3G8nHJEJEACJGATAjQQm2BmJyRAAiTgeARoII6XU0cdEcdFAiSgMgI0EJUlhHJIgARIQCsEaCBayRR1kgAJkIC9CBTQLw2kADA8TAIkQAIkUDgBGkjhfHiWBEiABEigAAI0kALA8DAJWI8AWyIBxyRAA3HMvHJUJEACJFDqBGggpY6YHZAACZCAYxLQgoE4JnmOigRIgAQ0ToAGovEEUj4JkAAJ2IsADcRe5NkvCWiBADWSQCEEaCCFwOEpEiABEiCBggnQQApmwzMkQAIkQAKFEKCBFAKn5KfYAgmQAAk4LgEaiOPmliMjARIggVIlQAMpVbxsnARIwF4E2G/pE/gfAAAA///YQQ0VAAAABklEQVQDAAGovj37m8/0AAAAAElFTkSuQmCC', 'Approved', NULL, '{\"agenda_alignment\":\"yes\",\"feasible_original\":\"yes\",\"ethical_sdg\":\"yes\"}', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAZAAAABkCAYAAACoy2Z3AAAQAElEQVR4AeydCZxNdRvHf8hekV0peqNG2feQjGnsYZB1bEkyg5coCqkIgwkxUiTZiRQRxtLYDcbYCq2WZIZs2UnveZ5x7zvGDGbmzj33nPubz+c5dznL//98nzPn9z/Pc8656f/lHwmQAAmQAAmkgEB68I8ESIAESIAEUkCAApICaFyFBFxCgBshAYsToIBYPIDsPgmQAAmYRYACYhZ5tksCJEACFidgYQGxOHl2nwRIgAQsToACYvEAsvskQAIkYBYBCohZ5NkuCViYALtOAkKAAiIUaCRAAiRAAskmQAFJNjKuQAIkQAIkIAQoIELB3cb2SIAESMAGBCggNggiXSABEiABMwhQQMygzjZJgATMIsB2XUiAAuJCmNwUCZAACXgTAQqIN0WbvpIACZCACwlQQFwI0xs2RR9JgARIwEGAAuIgwVcSsCmBqr4B8Cnj67SPwqba1FO65W4CFBB3E2d7JOAGAus3RiIkdCKKl62FH348iDNnzqLOCzVw/Hgs/j5/wQ09YBOuJ+B5W6SAeF5M2CMSSBGBGzdu4HjMCUyaPAMvNuuE4aPCcPXqVTzycAEc/z0K7ds2T9F2uRIJJEWAApIUGX5PAhYj0CIwCMWNVFX/QSOQLl069AzqhKhNy7AvajXOX7iIge+OsphH7K6nE6CAeHqE2D9XEbDtdjZt2Y6CRcpj1Zr1yJnjQQx7vx9O/7kX77/TFzmMz6vXbkChJypi89YdKFgwP4oVfRz8IwFXEKCAuIIit0ECJhAYM34KnjZqHI1f6oxLly8jf7482L5pKYJebX9Lb17p9qZ+LlnCB5u//xotmjXUz5yQQGoJUEBSS5Drk4CbCRw+8gdad+iO9z4Yg2N/xiCwdVPEHo7Ggd0RyJ3rIe2NFNGr+gYgZ4FncNoooL878HWsX7VQz1B0AS+Y/H7oCD6fPg/NWndFQMsuXuCx+128ZwFxf9fYIgmQQEIC0bv3oVTF2vhuxVo88MD9mDdzIsaMHIxMmTLqolevXXMW0eXqq9y5H8KhA1vQq3tnnW/nydlzfyN61z4E/3cAatVtibJV6uH1fkOwddtO/PrbYTu7bppvFBDT0LNhEkgegfade6Fm7RZ4ungxzJ0RhnXhC1DnheedG2nVPhiNmnWCFNHlyx7d4oroOXI8IB9taes3blXBaG34Xr1WAHwN4Zg9/xucPXsO7xlnXfNnfozvV87HrsgVtvTfbKcoIGZHgO2TwF0IREXvRckK/li8NBzZsmVFg3p+qOtfE48XeVTXdBTRl6/8HgcO/qpF9DPH92HI4Lgiui5kk8mOnXsQvmY9pP7zTDk/NGreGSIYe/YdQNnSJTBt8od6AcGOzd+hZ/DL8Pd7DkX/U8Qm3nueGxQQz4sJe0QCTgIhoR+jfuP2OHL0mH4nI+kBb/bQ96HjPtW7yx1FdElXJVZE14UtPhk1ZpKm7uo3aY8WbbtB6j+Stpv2aSh+jF6LjWsWYfpnY9G4YW2Le2qt7lNArBUv9tZLCEgBuGW7IAwfNQGXr1xB546tcPLobnw2bR6CjBy/FMeHDB8HubNciuj9+wbjl30bnEV0q2OSiwNEICVt91ixyvggZDxiYk+g6BNFMHhAb4R/Ows7tyxH4xfroED+vLBzms6TY+kNAuIy/uf+Pq//sPJPm1YWE3vSZf3lhqxJQNI0Fao1xIrwCHUgbOxQrNuwFXkKlcKI0WFYsGgZChTIh1HDBkBSVVJE7983SJe16uTChYuGQJxE24498FDBEnp58rCREyBXk1WqWEZrGDGHduqZhlwQULFCGau6aqt+U0CSEU4ZBcV/KF1avC9R3g8y8pSCqMO2bI2CPKYiGV3lohYl0P6V3vBv0AbXr193ehDcayB++vk3/Tx3ehi+/epz7DfSNl1ebqPfWXUiZ1mDh4ZqEVyumiph1DSWLl+D++67D5KKWjjnU2yO+BoLZn+Cwo8Vsqqbtu43BSQF4ZU8s4z8XGFHf47EgH49ULd2TTU/3+o4c/YcpCDqsLqN2yHXwyX1mn5JXYiJuCwxiqqnTp9JgQdcxdMI7IjarTH/dtmqWwYLPk8+ofvFnu3herYh+0klK42+44E++NOvCF+9Tv0sX7U+ylSui3ETpuKrxcuRM+eDaFj/BX1m14kj0fhiyhjUrFHFSE/lA/88lwAFxOTY3H9/drzR+zXIyNJhSxbGjTBllOmwzRHf3NJTEZd2nXvhhx9/uuV7frAWgRmzv9JCeP2ADpBBgeNMs2qVCnqWsfq7ubpvPFroYWs5drO3e/bt17TU8/7N9d6MFoFB6ueRI8cQ2DpAfdwduRJLFk7D50ZBPEuWzDfX5IsVCFBAUhClClUb4J9/bqRgzXtbJVPGjJrjljy3w4o/VVRHoAnPeqpXrXhvG+VSHkNA6hhvDhimZ5Q9Xh+kdbUrV65q//r16aZxXvb1F7oPZM+eTb+3ymRtxCaMHvuJ1mp8ytTEc37NIGmp3w//gSaN6qCf4d/4D99H7JFoTBgzVH3Mmze380ZIq/jJfsYRoIDEcbinacjQt+DvV0OXzf1ISZw48Ze+T7sJt2x1ApevXFGB6NbzbRUMST+OGD0RU7+YqwfP4K4d0K5NU3Uzb55ceLOPtYrhJ/86rSmpWvVa4smSNfSRIUNHfIQPx01G5kyZVDBk0HPowGYVjP59gw1/m6m/nFifAAUkmTHsb4ygChbIp2uVqlQbw0aO1/eckEB8ApKOEpN7OHzK+GLO/P+nICVVuWjeZE3fPPboI5A0Vj9DOKIjVyBDes//l5w+awEkfdqqXTDKPVtPU1JRO/cC6dJhyDt9NeW2eOFU7DJSU2+90R38sy8Bz99bPYx9+XKl9Mal+nVr4dKlyxj54SQdWfboPQixvATXw6Llnu7IjzhJTappyy66L8hZhnwWO3rsuBbBv5w1SVNTMhqXQni5siUxfFQY+g0chuI+xXSZ7Nk8L10lNRm5hFh8efudEBQqWgk9+wyGXMARuX2nFrrDxg5R3w7ujkCPoE7qS5VK5dwDPw1b4abvToACcndGiS4xe9p4HUFOnTRa58+Y8xUq12ikBdErN/PZOoMT2xLwMc4sxKo831hH4WuM/L/D2f3Ra3X/2LpusY7I5ZEajnny+ufxWISETpS36Bj4EsqWfkbfe8pkwOAQlK5UR+/HEGGUs6mJn05HtqxZ1R/xb/vGZZg+ZSzatmrqKd1mP9xMgAKSCuBS4G7apB7WLp8Hye3mMXLYcoNh/sJltYgouW55amoqmuCqHkJArnaTeEpRWM4wxCTWYvI7HBL/iPAvdSQuZxmyb4g9lDPHbR7IBRhyIYbM6PpKILq+0lbemmbXrl3HJ1Nm6j7bpkN3FChSDmGfTMehw0e1T+Jb6IhBWLFkFg7uidAzDPEtV66cOp8T7yVAAXFB7MuWKWEISBC2bfgWNapV1uKoHGzkahv53QY52IjJCFUOOGJ/nTrtgpY9eBMW7prchyMxEpO4iVX1baIH2MNHj2l85QAqQiEmZxn9+wahdMmn78nrmNgTupy/Xw3IhRn6wY2Tixcv6V3f4l/tBm2Q99HSRiptOEaGfowNm7bhqWJPYMPqhSqG+3d9j769u+qjVCpXLOPGXrIpKxCggLg4SlI8lNN7KZQ6TO6qlWYkFSApDzEZgUpaQGzj5u0ym2YyAcn1SzyerdlEU5ESJ0eXqj1bQVM3m9Yu0tSUxNgxL7mv8nseyV3HFcsfMcRP7mr3b9gG8sQD8S9yxy59vpTsq/ONOs26VQshZ1IlnvFxRZPchs0JUEDSKMBSKHWY3FUrI1WxgEZ1NQVQuVJZ/GEUWKU42SCgg7P4Kr9dLd+JOR5fkUZd9MrNHv3jT73sVPg6TM4wxBo1f1nn5cuTW2Mk8ZOYiS1d9IV+98jDBVLNTR5Tki5dOmTIkDb/fjExJxCxfgvCV69Xfyo/10j3L3kk/PwFS/DvjX/h51sdHdo2hzwJYfvGperbC7Wqo0hhPjIE1v9zmwdpswe7rfvWa0jutpXRnthKI6csI1mxUiXiRnznL1zUgqyMhOXHcWSUGN/kLMZ6XpvTYxltx2cn76v5Bjj5CmMxR++avFhHzy6WL5mhZxsSI8c8V7/Ks50mfTTMJZvt1uNtfX6W+CcmRf2mrV5Fi8Bu6uuBn35BxowZ0aCuH/ZGrcaqZXPUv3Gh70GehOCSTnAjXkmAAmJi2LNmzeLMp0vqQEa6Yj9GrzVqKsHo/lpHdAxsjvsyZNCb0SRnLXUUGS0nNKm3xNlEI1fvsDD9ZTYTXXR50/Lsr7j6kvgYZvgaZ/IE14RMZs1d5OQm7OQO/26vtjPYBhkW7DRhLiY/RiS1jaxZsri83/E3KHGXBwn6N2yrZwrx5yX2Xs6UJk2eiZDRcT737PMOijz1LPIXLqdnFnO+/AbbjFRUbOxJ5HzwAS3Kv2HULd4b1AcjhryFmENRkOdLzZr2EaTgny1b1sSa4XckkGwCFJBkI0v7FeRGRSnKxlkw9u5YpQVNOcg5TB7lLQc7h02buQBTps11HlBHjJYD60QUNg40CQ+s8uM8ckCNb3JgTnvPkm7h1KkztxzspW/DR03QA2T8/v+neLV4PsoBdSI+M/zOnz+vU4yFiTzi3MHK8bp720qnaMSxFSG5/c7vpHvpmjlHf9mGvEaaTFKU8mNQ8f1L7L2cJfUfNBzDjZjKHd4rjdRUFmPw8biRbqpYvjQkBSU+njq2B1vWLXb62DOoE17rEojMmTO7puPcCgkkIEABSQDEKh+7vNxG0y37jbMVh0VvWa6pCUm9xLeEPiX2WPpK1RtqukMOVmZYhWoNnIVrn5v3V4SEfnxL19OnT5+of9GRK25j0al9i1vW9aQPGdKnR3Rk4rGKH7fb3s8Ig+MiDYm5iEX40tlaBPck/9gX7yFAAbFRrCWfLYXfhCaj04T21hvBWjh1LFvBGMmaiUJ+NMjRF3kd2L/nbWddMsKWeQktuwfewX03ltLnhH7c9bN/TfAO77uR5Xx3EnC9gLiz92wrxQTk2Uu3jXCnhyU6wjdjub69uqbYN65IAiTgHgIUEPdwZiskQAIkYDsCFBDbhZQOeTEBuk4CbiVAAXErbjZGAiRAAvYhQAGxTyzpCQmQAAm4lQAFJB5uviUBEiABErh3AhSQe2fFJUmABEiABOIRoIDEg8G3JEACZhFgu1YkQAGxYtTYZxIgARLwAAIUEA8IArtAAiRAAlYkQAGxYtRu7zO/IQESIAG3E6CAuB05GyQBEiABexCggNgjjvSCBEjALAJe3C4FxIuDT9dJgARIIDUEKCCpocd1SYAESMCLCVBAvDj4nuE6e0ECJGBVAhQQq0aO/SYBEiABkwlQQEwOAJsnARIgAbMIpLZdCkhqCXJ9EiABEvBSAhQQLw083SYBEiCB1BKggKSWINf3XgL0nAS8nAAFxMt3ALpPAiRAAiklQAFJKTmuRwIkQAJeTsBEAfFy8nSfBEiAb0YB5AAAAbFJREFUBCxOgAJi8QCy+yRAAiRgFgEKiFnk2S4JmEiATZOAKwhQQFxBkdsgARIgAS8kQAHxwqDTZRIgARJwBQEKSEooch0SIAESIAFQQLgTkAAJkAAJpIgABSRF2LgSCZCASQTYrAcRoIB4UDDYFRIgARKwEgEKiJWixb6SAAmQgAcRoIB4UDDc0RW2QQIkQAKuIkABcRVJbocESIAEvIwABcTLAk53SYAEzCJgv3YpIPaLKT0iARIgAbcQoIC4BTMbIQESIAH7EaCA2C+mdvWIfpEACXgYAQqIhwWE3SEBEiABqxCggFglUuwnCZAACZhFIIl2KSBJgOHXJEACJEACdyZAAbkzH84lARIgARJIggAFJAkw/JoEXEeAWyIBexKggNgzrvSKBEiABNKcAAUkzRGzARIgARKwJwErCIg9ydMrEiABErA4AQqIxQPI7pMACZCAWQQoIGaRZ7skYAUC7CMJ3IEABeQOcDiLBEiABEggaQIUkKTZcA4JkAAJkMAdCFBA7gAn9bO4BRIgARKwLwEKiH1jS89IgARIIE0JUEDSFC83TgIkYBYBtpv2BP4HAAD//1Sf/wsAAAAGSURBVAMAbRP+PbMZ2bsAAAAASUVORK5CYII=', '2026-08-31 09:27:29', 'Approved', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAcoAAACgCAYAAACMhCxUAAAQAElEQVR4Aeyd244cVxWGq9o5IITkiQQZC7gwJIzFJW9AngDxJvAmvFEi8QIIbmJHHG6IbBOUaSVCkMRVrL/ca1wu7z5UdVX1PnytXlPVddprfXuyv97VPfGm4gEBCEAAAhCAwF4CiHIvGnZAAAIQgAAEqgpRpvRbQK4QgAAEILA6AUS5OnIahAAEIACBlAggypR6i1xTIkCuEIBAJgQQZSYdSRkQgAAEILAMAUS5DFeuCgEIpESAXCFwgACiPACHXRCAAAQgAAFEye8ABCAAAQikRGD1XBHl6shpEAIQgAAEUiKAKFPqLXKFAAQgAIHVCSDKM5BzKgQgAAEI5E8AUebfx1QIAQhAAAJnEECUZ8Dj1JQIkCsEIACBaQQQ5TRunAUBCEAAAoUQQJSFdDRlQiAlAuQKgZgIIMqYeoNcIAABCEAgOgKIMrouISEIQAACKRHIP1dEmX8fUyEEIAABCJxBAFGeAY9TIQABCEAgfwI5iTL/3qJCCEAAAhBYnQCiXB05DUIAAhCAQEoEEGVKvZVTrtQCAQhAIBECiDKRjiJNCEAAAhC4DAFEeRnutAqBlAiQKwSKJoAoi+5+iocABCAAgWMEEOUxQuyHAAQgkBIBcp2dAKKcHSkXhAAEIACBnAggypx6k1ogAAEIQGB2AguKcvZcuSAEIAABCEBgdQKIcnXkNAgBCEAAAikRQJQp9daCuXJpCEAAAhAIE0CUYS5shQAEIAABCHQEEGWHgR8QSIkAuUIAAmsSQJRr0qYtCEAAAhBIjgCiTK7LSLgoAh9++G5R9WZYLCWlTwBRpt+HVJABgfvv/+LFew8etW/E1/f+e3V90zx8+PB7GZRJCRBIkgCiTLLbSDp1AhKjBOhi3NhjX021Pb765p2H+/azHQIQmItA+DqIMsyFrRCYjYCkqHApamle3Jj/6lAjTeDx78+ffBo6lm0QgMDyBBDl8oxpoRACkqGiP1N0KUqM+zC4F798+rhWbJ9/dm8Y+85lOwQgsDwBRLk84yktcM4FCUh2w5D8PCS/UEiGin0zRS+ptYfkKCkqXIq+nyUEIBAXAUQZV3+QzQwEhpLTa5ecliHJ9bdJdsOQ/DzGpCghKiREj9tnTzaS45jrcCwEIHA5AojycuyTblny8ZB8PPrCudT6UHJ67ZLTck7wNjls290PCVEhIdqmrUJtK5xFx+nBzb/mzIFrQQACyxJAlMvyzebqkqIP9lpq8PeQfDxSK9hk9tpTouuHpHcoNDv0MAZfiYn42Pp9xZCHbavrqv6hjlF04ry+aa6Q5xAVryEQDQFEGU1XxJWIxKhBXIO5QgIYk2HfPn3xrL1+SHLa55LzpW6J9uNQzcbnViE+irqu7+87XjxC++ycl8+APEPHs+1sAlwAAqMJIMrRyPI+QYLUoC8xagQPVduXnWQTChePln3xrL0eyv+cbUMxGqOgHE2M3a1XZyMOvt5W7Re2v3uGcrFrdk/1g8LabLpg1hnCxTYILE4AUS6OOJ0GNBhLkMOMXYw+0PdlNzw2t9fG5KRZY2e9tt06IxPjlSLE4/bpkx/Zvo3Cjz8kz86a+sGsM4STbTkTiKQ2RBlJR1w6Dc0ku7F4l4jk6IO4i3G3K/uF5KiZnMKYBD9rFASTY1+MEt+Vtk+JoDytAT1D17O8uqdyVFjOzDpDoNgGgRkIIMoZIOZ2CUlScsytrn31mGQ0a2wkHIUZ6NTbqZPFuC+X/nbNOBX+hmXqrJMvCvWpsg6B8QQQ5UnMOChXAi5Gk2MdqtFmdP1Z497bqaFz5942ddZZ727Z2huCZu6cuB4ESiCAKEvoZWoMEgiJQ2JU+CzOZnSLzhqDiY3YaPnplu/G8z0261TN713f/HNEExwKgeIJIMrifwXyA3BKRRKGzyJNjK2LxsRz0VnjKbkfOmbfrNPP6Wqu6x+rft/GEgIQOEwAUR7mw95MCXTC2NVmcsz6vwPVpzcCekOwK7lS/brtjDCdCEsI7CeQ9QCxv2z2lEygL4e+PHJnEqcwc6dOfTkQQJQ59OLMNWi2MfMlo7qc1ydJSh5RJbdCMl3Nbfu56vfmxKT/BsK3s4QABKoKUfJb0BHo/zmIBs1cb8u9LoP6P13xBf748tmTn0iYQ1nm2u8FdvEiJZd6UURZas8H6tbfT+Y8cEqSehOg0lXn7bPHP9B6ySFZ7vv8smQu1A6BPgFE2adR+LpmlRo4JRGF45BccphpqA6vSXX6OsuqEo+hMNXnsIEABFK99UrPLUpAg6ZCslR4YxKNBk/NzHxbKst+zv2aUsl/rTx3/X73PyYwbi/Wapt2IBArAWaUsfZMBHlp0FRILApPKUVhKmflrzpUk9aJMAHjc8/3GLeNyfKv/polBEokgChL7PWRNdvA2f3fXyQZhZ9ug2h9wgzTD7/Y0gb6uxmSarlYIgk1bP38N0/X+vnnvs4SAiUSQJQl9vrEmiUZhQ2i3dMvYwNptMKUJJWfclXSWhLHCVg/f2C87t5gGEduwR7HxhGZEkCUmXbskmXZIHpwhumzTBtcG/3zXUvmcuzaLkkdp7y1JA4Q6O0yXtyC7fFgtVwCiLLcvj+7chtIg8LUhSUoxcYeEqdC4lSsJU+1pVwUNjtqtSTGEdA3Yf0M609uwToMlkURQJRFdfcyxbow/e8w90nJBtruae7cSJwKyUwxtzyvrh99rcZUsfJRjlqPPcRBITbDuLq+ubsVumYdxu/u80rL4c9rtk1bBwmwcyUCiHIl0CU043+HKSlpJqKYIk8JwgbkRjGdW/t9P1f5+HpMSwlRoXo99CZCEcpT4j+PSeiqx7cZvw9eHVX/8tU6axAogwCiLKOfL1blFHkqWUlB4QIZu9S5uo6if65EMzYkszGhNofh5/dzkRAVw2P7r2021+rNhm9TXcrfX6+9rOvqrbXbpD0IXJrALKK8dBG0nxaBqfKco8p6wkMyGxN9Gfq6n3+oBpeiZuIeNpvbiJf2+bkqQde9pDA9F5YQKIEAoiyhlxOoUTKQFBQuCS01m5IkTo0ESu1SVD2qTTV6qHZx6A4Y/NA+HafzfNeawrR2/+LtsoRAaQQQZWk9XqVVsMQhSZwaNqDffbtVYlkqJLmx0c9F9ai2sb2h83Sdfp0S5pqzy6sHN38cmzfHQyBlAogy5d4j9zcISBra2BeJXs8dktzYmDMHCbNfo+pe9HZsXW3nzJ9rQSAlAogypd4i14ME7l/f/M8PsGnlt76e8vJQ7pJlaHa5qDAPJcQ+CGRKAFFm2rElllVX1dte9/bZk3d9PfelhKnbwMMZ5pq3Y3NnTH1lE9iUXT7VQyAPAroNLGEOZTnb7LKtvrgj1V+/28hKVcEgVwKIMteepa4iCUiWoduxml3qbznPgPJrP9fa+K2vs4RACQQQZQm9XEiN+kJLIaUeLdNkthnOLvW3nBLm0ZM5AAIFEBhTIqIcQ4tjkyDQF0QSCS+UpGQZml3qdqxC0lSMbL4deTyHQyB5Aogy+S6kAAgcJhASps7QDFwhaSokTUXoFq0dd1/n2JsQ/kxEIIiiCCDKS3c37UNgJQIS5vDbsf2mTYbdU7doJU6FxKmw42oLnhAokgCiLLLb8y96N7jnX+jICv3bsbol62GzxO4ZulRnTvvh+2z1SmwVoZmnH8cSAjkRQJQ59Sa1dARsMO+egxlRt+/MH1merpmmwsXps07ZM1RwB9d+7Jl5hk5hGwSSJoAok+4+ku8TaJr22+HgbuN590SafVKH133W6fJsq+of1e4x5LvbXHWQ7Yc4e2jWqfBjWEIgVQKIMtWeI+83CGyfP3nHB/dj0tRgrkH8/vs337xxITbsJeB8Nfts7CFxKkInmDe7p1grxFux2i3bUFJsg8AEAohyAjROiZ9AX5oa1EODuUbxzaZ+2wdxpBnu17qtfqo9/ZmlXm+ff3ZP4lSIsUKcFdo/DPFWhG7ZIs8hLV7HRABRxtQb5LIYgWOD+csB/JU0NfNZLJnULlxXb52asjgrJE0PiVMRuoa4K5BniE5R26IuFlFG3T0ktwQBDeQKDeQawBX9djRwK3ymWbI0rx48+ruzuX36+Ge+PmYp1grxVtgd20bMFaHriL0iJM/Q8WyDwNIEEOXShLl+1AQ0gCs0gGvgVvQT1oCtQJp9Kueth27ZnipP9YOH3sBwy/a8vuDs0wggygEnXpZLQMJUSJrNkW/QarB+OVDn/WWgfZ9Pzv1bMlWeoVkn8py7d7geouR3AAIBAtveN2glzuFMU6doppn9l4FGfD4pJnPGli8LzYmTa51BAFGeAY9TL01gvfZ9punSHIqzL02fbe5mnN+ul+W8Ldnnkx/7Fad+Punnz7VUPyjUD4rGHuoLRagN9YuCmWeIDttOJYAoTyXFcRDYEdBArdBArQFasdt1t9DgrLAZ51sSp0LiVNx//yZ6eV5dP/pdXVXdv0Fp9f2pivQRmnWaOyd9WYhbtpF2cgRpIcoIOoEU0iUgYSokzWb3uaaJpQ1VJHEqkpBnXf3Ga7D6fuXr5yzXOneqPEOzzrVypp24CSDKuPuH7BIisN19rmli2UicCpPndxKnIlSKxKnoy7M/+wyds/Q23XK9m01W1SdVBo/txM871ReKq+ubRsGsM4NfhgklIMoJ0DgFAqcS2D5/8rbEqZA4FcfkqWtLngoN0h4aqBVL3rq1tj52SVZte2ufTX6kfHIM9YlCfaJo7KE3NIphveoLRTmzziGBsl8jyrL7n+ovQOBcefZnnxKnYkZ5dp9LCktb19F+Nqn85o4ts865kWZzPUSZTVdSSMoEtoGZ58tZTjv61q3EqZgiT7XZVvVHbVv9PufZ5Km/K5pxKsRFYZPOvV8U0oxT4bPOU9vguPgJxC7K+AmSIQQWJBASaNOMl6fdUm0lT8WxdG+ffvrJ7bPHfzh2XIn7t4NZZ2OP1h4lsiipZkRZUm9TaxYEtoHZZ9MclqcK12xHIWl6SJyKKbNPXbP02Jo4Qwwae4S2sy1NAogyzX6LM2uyuhiB7ZnyXPhzz4txWbJhfQNWbzj05sPbae2hW7TbPQL141imRQBRptVfZAuBkwlsA/LUIN40h2efGvgVyDOM2gWpzyL7RzT20OeZ/W2s50EAUebRj1QBgZMJ7AS60aAucSqaCfLUbEq3bRUnN574gao1JEgx3DKLTLx396ePKPezYQ8EiiGwDcw+j8lTcDTzVEiaw5BUPGwW9p1C58Qalt8LD897WJNq9fxbeyBIp5H3ElHm3b9UB4HJBKbK0xuUVDxsFnZPMRSPC0lLk9RiMrVrvybBYR56bfndPT1vr2W4bOyhGflw+2KvufBFCSDKi+KncQikRSAkT82qmubV556tPU6tyoWkpVkqKFNJTCL1MOm9JlR7PVqCp+bnx1lJ3bOxh+rdcpvV0RSxRJRFdDNFQmBZAtverVvNtCSTfphfXig62+x+jMlIIvUYCtVe3z11zJjr6lilY7ndPft5+7pqGWLLoQAAA2BJREFUUmwRpJAVFyNFWRwfCoYABGYgYIJ5SyHZeLiE+kuz1VlC7acqASrsmnfPflu+rnwst3se/WuwDgERQJSiQEAAAlEQMFmNEqob0KXXX0qACrsmEoyid9NNAlGm23dHM+cACORKwOTXCdWWnQRzrZO64iCAKOPoB7KAAAQgAIFICSDKSDuGtEojQL0QgECsBBBlrD1DXhCAAAQgEAUBRBlFN5AEBCCQEgFyLYsAoiyrv6kWAhCAAARGEkCUI4FxOAQgAAEIpETg/FwR5fkMuQIEIAABCGRMAFFm3LmUBgEIQAAC5xNAlOczPPUKHAcBCEAAAgkSQJQJdhopQwACEIDAegQQ5XqsaSklAuQKAQhAYEcAUe5AsIAABCAAAQiECCDKEBW2QQACKREgVwgsSgBRLoqXi0MAAhCAQOoEEGXqPUj+EIAABFIikGCuiDLBTiNlCEAAAhBYjwCiXI81LUEAAhCAQIIEChZlgr1FyhCAAAQgsDoBRLk6chqEAAQgAIGUCCDKlHqr4FwpHQIQgMClCCDKS5GnXQhAAAIQSIIAokyim0gSAikRIFcI5EUAUebVn1QDAQhAAAIzE0CUMwPlchCAAARSIkCuxwkgyuOMOAICEIAABAomgCgL7nxKhwAEIACB4wTiEeXxXDkCAhCAAAQgsDoBRLk6chqEAAQgAIGUCCDKlHornlzJBAIQgEAxBBBlMV1NoRCAAAQgMIUAopxCjXMgkBIBcoUABM4igCjPwsfJEIAABCCQOwFEmXsPUx8EIJASAXKNkACijLBTSAkCEIAABOIhgCjj6QsygQAEIACBCAnsFWWEuZISBCAAAQhAYHUCiHJ15DQIAQhAAAIpEUCUKfXW3lzZAQEIQAACSxFAlEuR5boQgAAEIJAFAUSZRTdSREoEyBUCEEiLAKJMq7/IFgIQgAAEViaAKFcGTnMQgEBKBMgVAlWFKPktgAAEIAABCBwggCgPwGEXBCAAAQikQ2CpTBHlUmS5LgQgAAEIZEEAUWbRjRQBAQhAAAJLEUCUS5DlmhCAAAQgkA0BRJlNV1IIBCAAAQgsQQBRLkGVa6ZEgFwhAAEIHCSAKA/iYScEIAABCJROAFGW/htA/RBIiQC5QuACBBDlBaDTJAQgAAEIpEPg/wAAAP//NBkatQAAAAZJREFUAwAuoN8ELjrzygAAAABJRU5ErkJggg==', '2026-08-31 09:28:12', '2026-08-31 09:26:24', '2026-08-31 09:26:37', '2026-08-31 09:26:24', '2026-08-31 09:28:17');

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

SET FOREIGN_KEY_CHECKS=1;
COMMIT;
