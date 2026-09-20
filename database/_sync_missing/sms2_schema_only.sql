SET FOREIGN_KEY_CHECKS=0;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `student_profiles` (
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

ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sp_user` (`user_id`),
  ADD UNIQUE KEY `uq_sp_student_id` (`student_id`);

ALTER TABLE `student_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

SET FOREIGN_KEY_CHECKS=1;
