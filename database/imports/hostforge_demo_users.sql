-- HostForge Database Import UI - DEMO USERS (upload AFTER hostforge_import.sql)
-- Small file: bcrypt hashes with known passwords from database/seed_accounts.php
-- Skips Super Admin setup because sms_users will no longer be empty.
--
-- Login examples:
--   superadmin / @Superadmin123
--   cradofficer / @Cradofficer123
--   deptchair / @Department123
--   depthead / @Depthead123
--   s230000001 / @Kenneth8080
--

SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

-- Upsert demo users (safe if username/email already exists)

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (1, 'superadmin', 'superadmin@bestlink.edu.ph', '$2y$10$sZ1ZJLD1ItJqG71h2Wqxw.BgOwRlJvHiVlU9OdxJ8AiPJlCKtniRy', 'Super Admin', 'superadmin', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (2, 'admin', 'admin@bestlink.edu.ph', '$2y$10$kDWWbfxWQdKWZ408nZthp.k1yNCUgjqOB3NycSRuds1Z3jx4GeyBm', 'Admin', 'sms_admin', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (3, 'registrar', 'registrar@bestlink.edu.ph', '$2y$10$LcTM.nwtEFt80u.VHc8CqeGOJvWiNJPNb2C/5h44cO0UN7EJlMoF6', 'Registrar', 'registrar', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (4, 'cradofficer', 'cradofficer@bestlink.ph', '$2y$10$4Mvvntt29Erei/eBU0OWgO3dK4tgFFAR9QwFUsXqEftOJTyj9J.ja', 'CRAD Officer', 'crad_officer', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (5, 'finance', 'finance@bestlink.edu.ph', '$2y$10$0prOoR./16KSBV4euk/PVewyuntepcbAl2N7ZytQ5Tkj7QDxAWeJe', 'Finance', 'finance', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (6, 'studentaffairs', 'studentaffairs@bestlink.edu.ph', '$2y$10$J2RrPRwYmo/Khrweqg.Afu6g6aZ8F/B1XgKqTNd8HCD8e4VLwTlQa', 'Student Affairs', 'osa', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (7, 'itofficer', 'itofficer@bestlink.edu.ph', '$2y$10$Nq0MrlssegyfxRTxzovlaOyM8AHu19ULVc1NjyUQg.G.OgLr5hNX2', 'IT Officer', 'it_office', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (8, 'qualityassurance', 'qualityassurance@bestlink.edu.ph', '$2y$10$asv9LYZ645JxMfnMdLxuaeFolgza24GGn74rVg.LoVqMmrwM3Slw2', 'Quality Assurance', 'qa', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (9, 'dean', 'dean@bestlink.edu.ph', '$2y$10$V2VDMsa2DNDZJg6CfVMf.ua8H6G4mBVCiH6IDcmGSoAvsB63W2v9e', 'Dean', 'hr', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (10, 'admission', 'admission@bestlink.edu.ph', '$2y$10$JYc52e8A3R4tsOsE.VX5fO6DOJ3CO0BTGrgTiuoA5znaiJgCX6Hrm', 'Admission', 'admission', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (11, 'researchcoordinator', 'researchcoordinator@bestlink.edu.ph', '$2y$10$x3zsn1T516ghZizL8Se8guA5X3.I/GDLGnBwmrOYNR8CL54U2ztJ2', 'Mrs. Kris Guevarra', 'research_coordinator', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (12, 'rsantos', 'rsantos@bestlink.edu.ph', '$2y$10$Zaou6ZifqUEBG7u5WoDwbuxWqlpC09Fixgln8YhwdGmSew7B33Qym', 'Dr. Roberto M. Santos', 'adviser', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (13, 'grammarian', 'grammarian@bestlink.edu.ph', '$2y$10$2zEhcXkpefM9aAxxnAQvB.eVj1azJMV3dxiEvW7ooA4/xnfoc0zTm', 'Grammarian', 'grammarian', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (14, 'jobertvalentino', 'jobertvalentino@bestlink.edu.ph', '$2y$10$RcaOC2bv6L8D/yda9M1oUuYTXRUJp.D7LxXjOK3W1bAfzxxv2wZfy', 'Dr. Jobert Valentino', 'panel', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (15, 'jonathanestrada', 'jonathanestrada@bestlink.edu.ph', '$2y$10$dmYPycT/Nq.wXzuhPzWiD.52GwcZds99fa2IA9Z93.SHZKirSQxZi', 'Dr. Jonathan Estrada', 'panel', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (16, 'michelleguevarra', 'michelleguevarra@bestlink.edu.ph', '$2y$10$.xdR0ztK6FP9mPkhfowsgeEA4O7FGUndVmJFm6OEx2ghZiPNRwXPO', 'Dr. Michelle Guevarra', 'panel', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (17, 'reviewcommittee', 'reviewcommittee@bestlink.edu.ph', '$2y$10$SRKceEOtlhgLypmwnKQcZubv21w6VeuxSwDFM1k1DnEQXLjUr/ykC', 'Review Committee', 'review_committee', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (18, 'deptchair', 'deptchair@bestlink.edu.ph', '$2y$10$mQA82dpIdhWs4nPvssJJj.0ImaK3CTObgbQBPPkb70UiVrwDbFc46', 'Department Chair', 'department_chair', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (19, 'researchoffice', 'researchoffice@bestlink.edu.ph', '$2y$10$2C3w.jMQ05qsHyJFMiJ0fOQlUhWs3t95j7fI/nh0ED3FJYcjtUZ2m', 'Research Office', 'research_office', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (20, 'vpaa', 'vpaa@bestlink.edu.ph', '$2y$10$JvJvx6IB52tswGXLCuuinuhdbuqsACd24zD4AUFRF7Q4/j0RvSj6y', 'VPAA', 'vpaa', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (21, 'depthead', 'depthead@bestlink.edu.ph', '$2y$10$6ZmRsfN50QV0P1KUQEtdXeDAL7TwfCvDZMYY/gTmumy9cRufx5bZ.', 'Department Head', 'department_head', NULL, 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

INSERT INTO `sms_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_key`, `student_id`, `status`, `must_change_password`, `failed_login_attempts`, `locked_until`, `password_changed_at`, `created_at`, `updated_at`)
VALUES (22, 's230000001', 's230000001@bestlink.edu.ph', '$2y$10$FudFvbqlb3RF1iAPGBkSmebJCtAgvfowBo2nITMtlE7kWswQHTSRC', 'Student User', 'student', 'S230000001', 'active', 0, 0, NULL, NOW(), NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `email` = VALUES(`email`),
  `password_hash` = VALUES(`password_hash`),
  `full_name` = VALUES(`full_name`),
  `role_key` = VALUES(`role_key`),
  `student_id` = VALUES(`student_id`),
  `status` = 'active',
  `must_change_password` = 0,
  `failed_login_attempts` = 0,
  `locked_until` = NULL;

-- Student profile for demo student
INSERT INTO `sms_student_profiles` (`id`, `user_id`, `student_id`, `program`, `year_level`, `section`, `semester`, `school_year`, `enrollment_status`, `standing`, `mobile`, `address`, `guardian`, `guardian_contact`, `created_at`, `updated_at`)
VALUES (1, 22, 'S230000001', 'Bachelor of Science in Information Technology', '4th Year', 'BSIT 4B', '1st Semester', '2026-2027', 'Enrolled', 'Good Standing', '0917 000 0011', 'Fairview, Quezon City', 'Juan Dela Cruz', '0918 000 0012', NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `user_id` = VALUES(`user_id`),
  `program` = VALUES(`program`),
  `year_level` = VALUES(`year_level`),
  `section` = VALUES(`section`),
  `enrollment_status` = VALUES(`enrollment_status`);

SET FOREIGN_KEY_CHECKS=1;

