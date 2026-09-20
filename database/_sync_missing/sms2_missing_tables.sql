SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `admin_announcements`;
CREATE TABLE `admin_announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `body` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('published','unpublished') NOT NULL DEFAULT 'published',
  `audience` varchar(40) NOT NULL DEFAULT 'student',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_by_name` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `published_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `admin_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ann_status_published` (`status`,`published_at`),
  ADD KEY `idx_ann_audience` (`audience`);

ALTER TABLE `admin_announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

DROP TABLE IF EXISTS `student_profiles`;
CREATE TABLE `student_profiles` (
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

INSERT INTO `student_profiles` (`id`, `user_id`, `student_id`, `program`, `year_level`, `section`, `semester`, `school_year`, `enrollment_status`, `standing`, `mobile`, `address`, `guardian`, `guardian_contact`, `created_at`, `updated_at`) VALUES
(1, 9, 'S230000001', 'Bachelor of Science in Information Technology', '4th Year', 'BSIT 4B', '1st Semester', '2026-2027', 'Enrolled', 'Good Standing', '0917 000 0011', 'Fairview, Quezon City', 'Juan Dela Cruz', '0918 000 0012', '2026-09-19 00:29:23', '2026-09-19 00:29:23'),
(2, 1354, 'S230106713', 'Bachelor of Science in Information Technology', '4th Year', 'BSIT 4A', '1st Semester', '2026-2027', 'Enrolled', 'Good Standing', '0917 000 0001', 'Novaliches, Quezon City', 'Maria Dela Cruz', '0918 000 0002', '2026-09-19 00:29:23', '2026-09-19 00:29:23');

ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sp_user` (`user_id`),
  ADD UNIQUE KEY `uq_sp_student_id` (`student_id`);

ALTER TABLE `student_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

SET FOREIGN_KEY_CHECKS = 1;
