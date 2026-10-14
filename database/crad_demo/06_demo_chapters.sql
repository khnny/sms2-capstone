-- 06_demo_chapters.sql: dependency-ordered CRAD demo stage; batch CRAD_DEMO_2026_01.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET @crad_demo_batch := 'CRAD_DEMO_2026_01';
SET @crad_demo_password_hash := '$2y$10$aw6AyQoVQN0GjjC5sEbi9.rHyehQzN2B7MqWSS9HuZs/3XT2Mslv.';
SET @crad_demo_signature := 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC1EQVR42mP8/x8AAwMCAO+a4l8AAAAASUVORK5CYII=';

-- These temporary staging tables are session-local and disappear after import.
CREATE TEMPORARY TABLE `tmp_crad_demo_project` (
  `project_no` tinyint unsigned NOT NULL PRIMARY KEY,
  `placeholder_number` varchar(40) NOT NULL,
  `official_number` varchar(40) DEFAULT NULL,
  `proposal_number` varchar(40) DEFAULT NULL,
  `group_name` varchar(40) NOT NULL,
  `research_title` varchar(500) NOT NULL,
  `flow_status` varchar(40) NOT NULL,
  `group_status` varchar(40) NOT NULL,
  `cycle_status` varchar(40) DEFAULT NULL,
  `coordinator_no` tinyint unsigned DEFAULT NULL,
  `adviser_no` tinyint unsigned DEFAULT NULL,
  `coordinator_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `adviser_confirmed` tinyint(1) NOT NULL DEFAULT 0,
  `dh_no` tinyint unsigned NOT NULL,
  `title_stage` varchar(32) NOT NULL
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tmp_crad_demo_project` VALUES
(1,'STU-S269990001',NULL,NULL,'Demo Student Team 01','Developing a privacy-aware campus room-finder for accessible study spaces','pending_dh_approval','Pending Approval',NULL,NULL,NULL,0,0,1,'none'),
(2,'STU-S269990006',NULL,NULL,'Demo Student Team 02','A mobile queue and appointment system for campus student services','ready_for_assignment','Pending Assignment','pending_confirmation',1,NULL,0,0,2,'none'),
(3,'STU-S269990011',NULL,NULL,'Demo Student Team 03','Evaluating low-bandwidth learning tools for first-year computing students','ready_for_assignment','Pending Assignment','pending_confirmation',NULL,2,0,0,3,'none'),
(4,'STU-S269990016',NULL,NULL,'Demo Student Team 04','A usability study of accessible digital learning materials','ready_for_assignment','Pending Assignment','pending_confirmation',3,3,0,0,4,'none'),
(5,'STU-S269990021',NULL,NULL,'Demo Student Team 05','A secure inventory and equipment-lending application for laboratories','ready_for_assignment','Pending Assignment','waiting_adviser',4,4,1,0,5,'none'),
(6,'STU-S269990026',NULL,NULL,'Demo Student Team 06','A campus energy-use dashboard using privacy-preserving aggregation','ready_for_assignment','Pending Assignment','waiting_coordinator',5,5,0,1,1,'none'),
(7,'STU-S269990031',NULL,NULL,'Demo Student Team 07','A digital consultation scheduler for academic advising','ready_for_assignment','Pending Assignment','needs_reassignment',1,1,0,0,2,'none'),
(8,'STU-S269990036',NULL,NULL,'Demo Student Team 08','A searchable repository of open educational computing resources','ready_for_assignment','Pending Assignment','confirmed',2,6,1,1,3,'none'),
(9,'STU-S269990041',NULL,NULL,'Demo Student Team 09','Designing a mobile-based disaster preparedness information system for urban communities','ready_for_assignment','Pending Assignment','confirmed',3,7,1,1,4,'adviser_pending'),
(10,'STU-S269990046',NULL,NULL,'Demo Student Team 10','Predictive analytics for early identification of at-risk first-year computing students','ready_for_assignment','Pending Assignment','confirmed',4,8,1,1,5,'coordinator_returned'),
(11,'STU-S269990051',NULL,NULL,'Demo Student Team 11','Energy-efficient smart irrigation monitoring using LoRaWAN sensor nodes','ready_for_assignment','Pending Assignment','confirmed',5,9,1,1,1,'crad_pending'),
(12,'STU-S269990056',NULL,NULL,'Demo Student Team 12','A privacy-preserving analytics framework for campus sustainability reporting','ready_for_assignment','Pending Assignment','confirmed',1,10,1,1,2,'registration_ready'),
(13,'STU-S269990061',NULL,NULL,'Demo Student Team 13','A multilingual campus wayfinding application with accessible route recommendations','ready_for_assignment','Migrated','confirmed',2,11,1,1,3,'registered'),
(14,'STU-S269990066',NULL,NULL,'Demo Student Team 14','Assessing explainable machine-learning alerts for student support services','ready_for_assignment','Migrated','confirmed',3,12,1,1,4,'registered'),
(15,'STU-S269990071',NULL,NULL,'Demo Student Team 15','A secure digital records workflow for community health outreach programs','ready_for_assignment','Migrated','confirmed',4,13,1,1,5,'registered'),
(16,'STU-S269990076',NULL,NULL,'Demo Student Team 16','A solar-powered sensor network for monitoring urban community gardens','ready_for_assignment','Migrated','confirmed',5,14,1,1,1,'registered');

CREATE TEMPORARY TABLE `tmp_crad_demo_given` (
  `n` tinyint unsigned NOT NULL PRIMARY KEY,
  `given_name` varchar(40) NOT NULL
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `tmp_crad_demo_given` VALUES
(1,'Ava'),(2,'Benjamin'),(3,'Chloe'),(4,'Diego'),(5,'Eliana'),
(6,'Felix'),(7,'Grace'),(8,'Hugo'),(9,'Isla'),(10,'Julian'),
(11,'Kai'),(12,'Luna'),(13,'Mateo'),(14,'Naomi'),(15,'Owen');

CREATE TEMPORARY TABLE `tmp_crad_demo_surname` (
  `n` tinyint unsigned NOT NULL PRIMARY KEY,
  `surname` varchar(40) NOT NULL
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `tmp_crad_demo_surname` VALUES
(1,'Alvarez'),(2,'Bennett'),(3,'Cruz'),(4,'Dela Vega'),(5,'Flores'),(6,'Garcia');

CREATE TEMPORARY TABLE `tmp_crad_demo_sequence` (
  `n` smallint unsigned NOT NULL PRIMARY KEY
);
INSERT INTO `tmp_crad_demo_sequence` VALUES
(1),(2),(3),(4),(5),(6),(7),(8),(9),(10),
(11),(12),(13),(14),(15),(16),(17),(18),(19),(20),
(21),(22),(23),(24),(25),(26),(27),(28),(29),(30),
(31),(32),(33),(34),(35),(36),(37),(38),(39),(40),
(41),(42),(43),(44),(45),(46),(47),(48),(49),(50),
(51),(52),(53),(54),(55),(56),(57),(58),(59),(60),
(61),(62),(63),(64),(65),(66),(67),(68),(69),(70),
(71),(72),(73),(74),(75),(76),(77),(78),(79),(80);

CREATE TEMPORARY TABLE `tmp_crad_demo_people` (
  `record_key` varchar(100) NOT NULL PRIMARY KEY,
  `username` varchar(80) NOT NULL,
  `email` varchar(190) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `role_key` varchar(40) NOT NULL,
  `student_id` varchar(40) DEFAULT NULL,
  `is_student` tinyint(1) NOT NULL DEFAULT 0,
  `project_no` tinyint unsigned DEFAULT NULL,
  `student_seq` smallint unsigned DEFAULT NULL,
  UNIQUE KEY `uq_tmp_demo_username` (`username`),
  UNIQUE KEY `uq_tmp_demo_email` (`email`),
  UNIQUE KEY `uq_tmp_demo_student` (`student_id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tmp_crad_demo_people`
  (`record_key`,`username`,`email`,`full_name`,`role_key`,`student_id`,`is_student`)
VALUES
('user:coordinator-01','crad_demo_coordinator_01','coordinator01@crad-demo.invalid','Mira Santos','research_coordinator',NULL,0),
('user:coordinator-02','crad_demo_coordinator_02','coordinator02@crad-demo.invalid','Adrian Reyes','research_coordinator',NULL,0),
('user:coordinator-03','crad_demo_coordinator_03','coordinator03@crad-demo.invalid','Bianca Flores','research_coordinator',NULL,0),
('user:coordinator-04','crad_demo_coordinator_04','coordinator04@crad-demo.invalid','Carlos Navarro','research_coordinator',NULL,0),
('user:coordinator-05','crad_demo_coordinator_05','coordinator05@crad-demo.invalid','Dana Villanueva','research_coordinator',NULL,0),
('user:adviser-01','crad_demo_adviser_01','adviser01@crad-demo.invalid','Dr. Rafael Mendoza','adviser',NULL,0),
('user:adviser-02','crad_demo_adviser_02','adviser02@crad-demo.invalid','Dr. Celeste Aquino','adviser',NULL,0),
('user:adviser-03','crad_demo_adviser_03','adviser03@crad-demo.invalid','Dr. Emilio Bautista','adviser',NULL,0),
('user:adviser-04','crad_demo_adviser_04','adviser04@crad-demo.invalid','Dr. Sofia Castillo','adviser',NULL,0),
('user:adviser-05','crad_demo_adviser_05','adviser05@crad-demo.invalid','Dr. Tomas Herrera','adviser',NULL,0),
('user:adviser-06','crad_demo_adviser_06','adviser06@crad-demo.invalid','Dr. Lucia Cabrera','adviser',NULL,0),
('user:adviser-07','crad_demo_adviser_07','adviser07@crad-demo.invalid','Dr. Gabriel Santos','adviser',NULL,0),
('user:adviser-08','crad_demo_adviser_08','adviser08@crad-demo.invalid','Dr. Isabel Cruz','adviser',NULL,0),
('user:adviser-09','crad_demo_adviser_09','adviser09@crad-demo.invalid','Dr. Victor Ramos','adviser',NULL,0),
('user:adviser-10','crad_demo_adviser_10','adviser10@crad-demo.invalid','Dr. Elena Castillo','adviser',NULL,0),
('user:adviser-11','crad_demo_adviser_11','adviser11@crad-demo.invalid','Dr. Marco Villanueva','adviser',NULL,0),
('user:adviser-12','crad_demo_adviser_12','adviser12@crad-demo.invalid','Dr. Teresa Bautista','adviser',NULL,0),
('user:adviser-13','crad_demo_adviser_13','adviser13@crad-demo.invalid','Dr. Daniel Navarro','adviser',NULL,0),
('user:adviser-14','crad_demo_adviser_14','adviser14@crad-demo.invalid','Dr. Angela Flores','adviser',NULL,0),
('user:officer-01','crad_demo_officer_01','officer01@crad-demo.invalid','Leah Domingo','crad_officer',NULL,0),
('user:officer-02','crad_demo_officer_02','officer02@crad-demo.invalid','Marco Estrada','crad_officer',NULL,0),
('user:officer-03','crad_demo_officer_03','officer03@crad-demo.invalid','Nina Garcia','crad_officer',NULL,0),
('user:officer-04','crad_demo_officer_04','officer04@crad-demo.invalid','Paolo Lim','crad_officer',NULL,0),
('user:officer-05','crad_demo_officer_05','officer05@crad-demo.invalid','Rosa Mercado','crad_officer',NULL,0),
('user:department-head-01','crad_demo_department_head_01','head01@crad-demo.invalid','Dr. Elena Ramos','department_head',NULL,0),
('user:department-head-02','crad_demo_department_head_02','head02@crad-demo.invalid','Dr. Victor Salazar','department_head',NULL,0),
('user:department-head-03','crad_demo_department_head_03','head03@crad-demo.invalid','Dr. Isabel Torres','department_head',NULL,0),
('user:department-head-04','crad_demo_department_head_04','head04@crad-demo.invalid','Dr. Gabriel Uy','department_head',NULL,0),
('user:department-head-05','crad_demo_department_head_05','head05@crad-demo.invalid','Dr. Maya Valdez','department_head',NULL,0),
('user:panel-01','crad_demo_panel_01','panel01@crad-demo.invalid','Prof. Daniel Abad','panel',NULL,0),
('user:panel-02','crad_demo_panel_02','panel02@crad-demo.invalid','Prof. Teresa Chua','panel',NULL,0),
('user:panel-03','crad_demo_panel_03','panel03@crad-demo.invalid','Prof. Luis de Leon','panel',NULL,0),
('user:panel-04','crad_demo_panel_04','panel04@crad-demo.invalid','Prof. Angela Fernandez','panel',NULL,0),
('user:panel-05','crad_demo_panel_05','panel05@crad-demo.invalid','Prof. Noel Ibarra','panel',NULL,0),
('user:grammarian-01','crad_demo_grammarian_01','grammarian01@crad-demo.invalid','Beatriz Javier','grammarian',NULL,0),
('user:grammarian-02','crad_demo_grammarian_02','grammarian02@crad-demo.invalid','Cesar Katigbak','grammarian',NULL,0),
('user:grammarian-03','crad_demo_grammarian_03','grammarian03@crad-demo.invalid','Lara Manalo','grammarian',NULL,0);

INSERT INTO `tmp_crad_demo_people`
  (`record_key`,`username`,`email`,`full_name`,`role_key`,`student_id`,`is_student`,`project_no`,`student_seq`)
SELECT
  CONCAT('user:student-',LPAD(s.n,3,'0')),
  CONCAT('crad_demo_student_',LPAD(s.n,3,'0')),
  CONCAT(LOWER(REPLACE(CONCAT(g.given_name,'.',f.surname), ' ', '')),'.',LPAD(s.n,3,'0'),'@student-demo.invalid'),
  CONCAT(g.given_name,' ',f.surname),
  'student',
  CONCAT('S269990',LPAD(s.n,3,'0')),
  1,
  CEILING(s.n / 5),
  s.n
FROM `tmp_crad_demo_sequence` s
JOIN `tmp_crad_demo_given` g ON g.n = MOD(s.n - 1, 15) + 1
JOIN `tmp_crad_demo_surname` f ON f.n = FLOOR((s.n - 1) / 15) + 1;

CREATE TEMPORARY TABLE `tmp_crad_demo_slots` (
  `slot_no` tinyint unsigned NOT NULL PRIMARY KEY
);
INSERT INTO `tmp_crad_demo_slots` VALUES (1),(2),(3),(4),(5);

-- No new roles or permissions are inserted. Every account uses an existing
-- sms2_roles.role_key; dashboard access remains governed by existing policy.

UPDATE `tmp_crad_demo_project` p
JOIN `crad_demo_seed_records` r
  ON r.batch_id=@crad_demo_batch
 AND r.record_key=CONCAT('official-group:',LPAD(p.project_no,2,'0'))
 AND r.table_name='crad_research_groups'
JOIN `crad_research_groups` g ON g.id=r.record_id
SET p.official_number=g.group_number
WHERE p.project_no BETWEEN 13 AND 16;

UPDATE `tmp_crad_demo_project` p
JOIN `crad_demo_seed_records` r
  ON r.batch_id=@crad_demo_batch
 AND r.record_key=CONCAT('title:',LPAD(p.project_no,2,'0'))
 AND r.table_name='crad_title_approvals'
JOIN `crad_title_approvals` t ON t.id=r.record_id
SET p.proposal_number=t.proposal_number
WHERE p.title_stage <> 'none';

CREATE TEMPORARY TABLE `tmp_crad_demo_stage_guard` (`guard_id` tinyint unsigned NOT NULL PRIMARY KEY) ENGINE=MEMORY;

INSERT INTO `tmp_crad_demo_stage_guard` VALUES (1);

INSERT INTO `tmp_crad_demo_stage_guard` SELECT IF((SELECT COUNT(*) FROM crad_demo_seed_records WHERE batch_id=@crad_demo_batch AND record_key LIKE 'official-group:%')=4 AND (SELECT COUNT(*) FROM crad_demo_seed_records WHERE batch_id=@crad_demo_batch AND record_key LIKE 'plan:%')=4,2,1);

START TRANSACTION;

INSERT INTO `crad_chapter_submissions`
  (`research_group_id`,`research_plan_id`,`chapter_number`,`version_number`,`status`,
   `submitted_by_user`,`submitted_by_name`,`submitted_by_email`,`submission_notes`,
   `original_name`,`stored_subdir`,`stored_name`,`file_size`,`file_mime`,`submission_token`,
   `submitted_at`,`review_started_at`,`reviewed_at`,`updated_at`)
SELECT official.record_id,plan.record_id,s.slot_no,1,
       IF(p.project_no=13 AND s.slot_no=3,'Under Review','Accepted'),
       leader_user.id,leader.full_name,leader.email,
       'Fictional Chapter 1-3 demo submission; replace with a real upload before downloading.',
       CONCAT('CRAD-DEMO-',LPAD(p.project_no,2,'0'),'-Chapter-',s.slot_no,'.pdf'),
       CONCAT('crad_demo/group-',LPAD(p.project_no,2,'0')),
       CONCAT('demo-',LPAD(p.project_no,2,'0'),'-chapter-',s.slot_no,'.pdf'),
       0,'application/pdf',SHA2(CONCAT(@crad_demo_batch,':chapter:',p.project_no,':',s.slot_no),256),
       IF(p.project_no IN (15,16),'2026-09-10 10:00:00','2026-09-16 10:00:00'),
       IF(p.project_no IN (15,16),'2026-09-10 11:00:00','2026-09-16 11:00:00'),
       IF(p.project_no=13 AND s.slot_no=3,NULL,
          IF(p.project_no IN (15,16),'2026-09-10 15:00:00','2026-09-17 15:00:00')),
       IF(p.project_no IN (15,16),'2026-09-10 15:00:00','2026-09-17 15:00:00')
FROM `tmp_crad_demo_project` p
JOIN `tmp_crad_demo_slots` s
JOIN `crad_demo_seed_records` official ON official.batch_id=@crad_demo_batch AND official.record_key=CONCAT('official-group:',LPAD(p.project_no,2,'0'))
JOIN `crad_demo_seed_records` plan ON plan.batch_id=@crad_demo_batch AND plan.record_key=CONCAT('plan:',LPAD(p.project_no,2,'0'))
JOIN `tmp_crad_demo_people` leader ON leader.project_no=p.project_no AND leader.student_seq=(p.project_no-1)*5+1
JOIN `sms2_users` leader_user ON leader_user.username=leader.username
WHERE p.project_no BETWEEN 13 AND 16 AND s.slot_no<=3
AND NOT EXISTS (
  SELECT 1 FROM `crad_demo_seed_records` r
  WHERE r.batch_id=@crad_demo_batch AND r.record_key=CONCAT('chapter:',LPAD(p.project_no,2,'0'),':',s.slot_no)
);

INSERT INTO `crad_demo_seed_records` (`batch_id`,`record_key`,`table_name`,`record_id`)
SELECT @crad_demo_batch,CONCAT('chapter:',LPAD(p.project_no,2,'0'),':',s.slot_no),
       'crad_chapter_submissions',cs.id
FROM `tmp_crad_demo_project` p
JOIN `tmp_crad_demo_slots` s
JOIN `crad_demo_seed_records` official ON official.batch_id=@crad_demo_batch AND official.record_key=CONCAT('official-group:',LPAD(p.project_no,2,'0'))
JOIN `crad_chapter_submissions` cs ON cs.research_group_id=official.record_id AND cs.chapter_number=s.slot_no AND cs.version_number=1
WHERE p.project_no BETWEEN 13 AND 16 AND s.slot_no<=3
AND NOT EXISTS (
  SELECT 1 FROM `crad_demo_seed_records` r
  WHERE r.batch_id=@crad_demo_batch AND r.record_key=CONCAT('chapter:',LPAD(p.project_no,2,'0'),':',s.slot_no)
);

INSERT INTO `crad_chapter_evaluations`
  (`submission_id`,`research_group_id`,`evaluator_user_id`,`evaluator_name`,
   `content_score`,`methodology_score`,`references_score`,`format_score`,`grammar_score`,
   `content_remarks`,`methodology_remarks`,`references_remarks`,`format_remarks`,`grammar_remarks`,
   `overall_feedback`,`result`,`overall_score`,`evaluated_at`,`created_at`)
SELECT chapter.record_id,official.record_id,grader_user.id,grader.full_name,
       18,18,17,18,18,'Scope and objectives are clearly stated.',
       'Methods are reproducible for the fictional study.',
       'Sources and citations are suitable for the demo.',
       'Formatting follows the sample institutional guide.',
       'Minor language edits completed.','Approved for the next research stage.',
       'APPROVED',89,
       IF(p.project_no IN (15,16),'2026-09-10 15:00:00','2026-09-17 15:00:00'),
       IF(p.project_no IN (15,16),'2026-09-10 15:00:00','2026-09-17 15:00:00')
FROM `tmp_crad_demo_project` p
JOIN `tmp_crad_demo_slots` s
JOIN `crad_demo_seed_records` official ON official.batch_id=@crad_demo_batch AND official.record_key=CONCAT('official-group:',LPAD(p.project_no,2,'0'))
JOIN `crad_demo_seed_records` chapter ON chapter.batch_id=@crad_demo_batch AND chapter.record_key=CONCAT('chapter:',LPAD(p.project_no,2,'0'),':',s.slot_no)
JOIN `tmp_crad_demo_people` grader ON grader.record_key=CONCAT('user:grammarian-',LPAD(MOD(s.slot_no-1,3)+1,2,'0'))
JOIN `sms2_users` grader_user ON grader_user.username=grader.username
WHERE p.project_no BETWEEN 13 AND 16 AND s.slot_no<=3
  AND NOT (p.project_no=13 AND s.slot_no=3)
AND NOT EXISTS (
  SELECT 1 FROM `crad_demo_seed_records` r
  WHERE r.batch_id=@crad_demo_batch
    AND r.record_key=CONCAT('chapter-eval:',LPAD(p.project_no,2,'0'),':',s.slot_no)
);

INSERT INTO `crad_demo_seed_records` (`batch_id`,`record_key`,`table_name`,`record_id`)
SELECT @crad_demo_batch,CONCAT('chapter-eval:',LPAD(p.project_no,2,'0'),':',s.slot_no),
       'crad_chapter_evaluations',e.id
FROM `tmp_crad_demo_project` p
JOIN `tmp_crad_demo_slots` s
JOIN `crad_demo_seed_records` chapter ON chapter.batch_id=@crad_demo_batch AND chapter.record_key=CONCAT('chapter:',LPAD(p.project_no,2,'0'),':',s.slot_no)
JOIN `crad_chapter_evaluations` e ON e.submission_id=chapter.record_id
WHERE p.project_no BETWEEN 13 AND 16 AND s.slot_no<=3
  AND NOT (p.project_no=13 AND s.slot_no=3)
AND NOT EXISTS (
  SELECT 1 FROM `crad_demo_seed_records` r
  WHERE r.batch_id=@crad_demo_batch
    AND r.record_key=CONCAT('chapter-eval:',LPAD(p.project_no,2,'0'),':',s.slot_no)
);

INSERT INTO `crad_chapter_evaluation_notifications`
  (`event_key`,`recipient_user_id`,`recipient_role`,`recipient_email`,`submission_id`,
   `type`,`title`,`body`,`url`,`is_read`,`created_at`)
SELECT CONCAT('evaluator:new:',chapter.record_id,':u',reviewer_user.id),
       reviewer_user.id,'grammarian',reviewer.email,chapter.record_id,
       'new_submission','New Chapter Submission',
       CONCAT(p.group_name,' submitted Chapter ',s.slot_no,' Version 1 for evaluation.'),
       CONCAT('/sms2_system/modules/faculty/pages/evaluation-scoring.php?id=',chapter.record_id),
       0,IF(p.project_no IN (15,16),'2026-09-10 10:00:00','2026-09-16 10:00:00')
FROM `tmp_crad_demo_project` p
JOIN `tmp_crad_demo_slots` s
JOIN `crad_demo_seed_records` chapter
  ON chapter.batch_id=@crad_demo_batch
 AND chapter.record_key=CONCAT('chapter:',LPAD(p.project_no,2,'0'),':',s.slot_no)
JOIN `tmp_crad_demo_people` reviewer ON reviewer.record_key LIKE 'user:grammarian-%'
JOIN `sms2_users` reviewer_user ON reviewer_user.username=reviewer.username
WHERE p.project_no BETWEEN 13 AND 16 AND s.slot_no<=3
AND NOT EXISTS (
  SELECT 1 FROM `crad_demo_seed_records` r
  WHERE r.batch_id=@crad_demo_batch
    AND r.record_key=CONCAT('chapter-notice:evaluator:',LPAD(p.project_no,2,'0'),':',s.slot_no,':',reviewer_user.id)
);

INSERT INTO `crad_demo_seed_records` (`batch_id`,`record_key`,`table_name`,`record_id`)
SELECT @crad_demo_batch,
       CONCAT('chapter-notice:evaluator:',LPAD(p.project_no,2,'0'),':',s.slot_no,':',reviewer_user.id),
       'crad_chapter_evaluation_notifications',n.id
FROM `tmp_crad_demo_project` p
JOIN `tmp_crad_demo_slots` s
JOIN `crad_demo_seed_records` chapter
  ON chapter.batch_id=@crad_demo_batch
 AND chapter.record_key=CONCAT('chapter:',LPAD(p.project_no,2,'0'),':',s.slot_no)
JOIN `tmp_crad_demo_people` reviewer ON reviewer.record_key LIKE 'user:grammarian-%'
JOIN `sms2_users` reviewer_user ON reviewer_user.username=reviewer.username
JOIN `crad_chapter_evaluation_notifications` n
  ON n.event_key=CONCAT('evaluator:new:',chapter.record_id,':u',reviewer_user.id)
WHERE p.project_no BETWEEN 13 AND 16 AND s.slot_no<=3
AND NOT EXISTS (
  SELECT 1 FROM `crad_demo_seed_records` r
  WHERE r.batch_id=@crad_demo_batch
    AND r.record_key=CONCAT('chapter-notice:evaluator:',LPAD(p.project_no,2,'0'),':',s.slot_no,':',reviewer_user.id)
);

INSERT INTO `crad_chapter_evaluation_notifications`
  (`event_key`,`recipient_user_id`,`recipient_role`,`recipient_email`,`submission_id`,
   `type`,`title`,`body`,`url`,`is_read`,`created_at`)
SELECT CONCAT('student:',IF(cs.status='Accepted','accepted','under_review'),':',cs.id),
       leader_user.id,'student',leader.email,cs.id,
       IF(cs.status='Accepted','accepted','under_review'),
       IF(cs.status='Accepted',
          CONCAT('Chapter ',s.slot_no,' accepted'),
          CONCAT('Chapter ',s.slot_no,' is under review')),
       IF(cs.status='Accepted',
          CONCAT('Chapter ',s.slot_no,' Version 1 is now Accepted.'),
          CONCAT('Chapter ',s.slot_no,' Version 1 is now under review.')),
       '/sms2_system/modules/student-portal/pages/submission-status.php',
       0,IF(p.project_no IN (15,16),'2026-09-10 15:00:00','2026-09-17 15:00:00')
FROM `tmp_crad_demo_project` p
JOIN `tmp_crad_demo_slots` s
JOIN `crad_demo_seed_records` chapter
  ON chapter.batch_id=@crad_demo_batch
 AND chapter.record_key=CONCAT('chapter:',LPAD(p.project_no,2,'0'),':',s.slot_no)
JOIN `crad_chapter_submissions` cs ON cs.id=chapter.record_id
JOIN `tmp_crad_demo_people` leader
  ON leader.project_no=p.project_no AND leader.student_seq=(p.project_no-1)*5+1
JOIN `sms2_users` leader_user ON leader_user.username=leader.username
WHERE p.project_no BETWEEN 13 AND 16 AND s.slot_no<=3
AND NOT EXISTS (
  SELECT 1 FROM `crad_demo_seed_records` r
  WHERE r.batch_id=@crad_demo_batch
    AND r.record_key=CONCAT('chapter-notice:student:',LPAD(p.project_no,2,'0'),':',s.slot_no)
);

INSERT INTO `crad_demo_seed_records` (`batch_id`,`record_key`,`table_name`,`record_id`)
SELECT @crad_demo_batch,
       CONCAT('chapter-notice:student:',LPAD(p.project_no,2,'0'),':',s.slot_no),
       'crad_chapter_evaluation_notifications',n.id
FROM `tmp_crad_demo_project` p
JOIN `tmp_crad_demo_slots` s
JOIN `crad_demo_seed_records` chapter
  ON chapter.batch_id=@crad_demo_batch
 AND chapter.record_key=CONCAT('chapter:',LPAD(p.project_no,2,'0'),':',s.slot_no)
JOIN `crad_chapter_submissions` cs ON cs.id=chapter.record_id
JOIN `crad_chapter_evaluation_notifications` n
  ON n.event_key=CONCAT('student:',IF(cs.status='Accepted','accepted','under_review'),':',cs.id)
WHERE p.project_no BETWEEN 13 AND 16 AND s.slot_no<=3
AND NOT EXISTS (
  SELECT 1 FROM `crad_demo_seed_records` r
  WHERE r.batch_id=@crad_demo_batch
    AND r.record_key=CONCAT('chapter-notice:student:',LPAD(p.project_no,2,'0'),':',s.slot_no)
);


COMMIT;

SELECT '06_demo_chapters.sql complete' AS stage, COUNT(*) AS batch_registry_records FROM crad_demo_seed_records WHERE batch_id=@crad_demo_batch;