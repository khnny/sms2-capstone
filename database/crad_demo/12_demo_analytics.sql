-- Read-only verification and analytics checks; requires all 13 import stages to have succeeded.

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

CREATE TEMPORARY TABLE `tmp_crad_demo_guard` (`guard_id` tinyint unsigned NOT NULL PRIMARY KEY) ENGINE=MEMORY;

INSERT INTO `tmp_crad_demo_guard` VALUES (1);

-- Fail the import before commit if any team exceeds the UI's five-member limit,
-- if a staged group has a missing student, or if an official stage lacks its
-- title approval, dual confirmation, or required signed approvals.
INSERT INTO `tmp_crad_demo_guard`
SELECT IF(
  EXISTS (
    SELECT 1 FROM `crad_demo_seed_records` r
    JOIN `crad_research_group_members` m
      ON r.table_name='crad_research_group_members' AND r.record_id=m.id
    WHERE r.batch_id=@crad_demo_batch
    GROUP BY m.research_group_id
    HAVING COUNT(*) > 5
  )
  OR EXISTS (
    SELECT 1 FROM `tmp_crad_demo_project` p
    LEFT JOIN `crad_demo_seed_records` g
      ON g.batch_id=@crad_demo_batch AND g.record_key=CONCAT('group:',LPAD(p.project_no,2,'0'))
    LEFT JOIN `crad_research_group_members` m ON m.research_group_id=g.record_id
    GROUP BY p.project_no
    HAVING COUNT(m.id) <> 5
  )
  OR EXISTS (
    SELECT 1 FROM `tmp_crad_demo_project` p
    JOIN `crad_demo_seed_records` g
      ON g.batch_id=@crad_demo_batch
     AND g.record_key=CONCAT('official-group:',LPAD(p.project_no,2,'0'))
    LEFT JOIN `crad_research_group_members` m ON m.research_group_id=g.record_id
    WHERE p.project_no BETWEEN 13 AND 16
    GROUP BY p.project_no
    HAVING COUNT(m.id) <> 5
  )
  OR EXISTS (
    SELECT 1 FROM `tmp_crad_demo_project` p
    JOIN `crad_demo_seed_records` g
      ON g.batch_id=@crad_demo_batch AND g.record_key=CONCAT('official-group:',LPAD(p.project_no,2,'0'))
    LEFT JOIN `crad_title_approvals` t ON t.id=(
      SELECT r.record_id FROM `crad_demo_seed_records` r
      WHERE r.batch_id=@crad_demo_batch AND r.record_key=CONCAT('title:',LPAD(p.project_no,2,'0'))
    )
    WHERE p.project_no BETWEEN 13 AND 16
      AND (t.id IS NULL OR t.status<>'Approved' OR t.coordinator_status<>'Approved'
        OR t.crad_status<>'Approved' OR COALESCE(t.adviser_signature_data,'')=''
        OR COALESCE(t.coordinator_signature_data,'')='' OR COALESCE(t.crad_signature_data,'')='')
  )
  OR (SELECT COUNT(*) FROM `crad_demo_seed_records`
      WHERE batch_id=@crad_demo_batch AND table_name='sms2_users') <>
     (SELECT COUNT(*) FROM `tmp_crad_demo_people`)
,1,3);

SELECT
  CASE
    WHEN (SELECT COUNT(*) FROM `crad_demo_seed_records`
          WHERE batch_id=@crad_demo_batch) <> 616
      THEN 'ERROR: expected 616 registered demo records'
    WHEN (SELECT COUNT(*) FROM `crad_demo_seed_records`
          WHERE batch_id=@crad_demo_batch AND table_name='sms2_users') <> 117
      THEN 'ERROR: expected 117 registered demo users'
    WHEN (SELECT COUNT(*) FROM `crad_demo_seed_records`
          WHERE batch_id=@crad_demo_batch AND table_name='crad_research_adviser_assignments') <> 14
      THEN 'ERROR: expected 14 registered adviser assignments'
    WHEN (SELECT COUNT(DISTINCT CONCAT(adviser_email,'|',adviser_name))
          FROM `crad_research_adviser_assignments` a
          JOIN `crad_demo_seed_records` r
            ON r.batch_id=@crad_demo_batch
           AND r.table_name='crad_research_adviser_assignments'
           AND r.record_id=a.id) <> 14
      THEN 'ERROR: demo adviser assignment identities are not unique'
    WHEN EXISTS (
      SELECT 1 FROM `crad_demo_seed_records` r
      JOIN `crad_research_group_members` m
        ON r.table_name='crad_research_group_members' AND r.record_id=m.id
      WHERE r.batch_id=@crad_demo_batch
      GROUP BY m.research_group_id
      HAVING COUNT(*) > 5
    ) THEN 'ERROR: a demo group has more than five students'
    WHEN EXISTS (
      SELECT 1 FROM `tmp_crad_demo_project` p
      LEFT JOIN `crad_demo_seed_records` g
        ON g.batch_id=@crad_demo_batch AND g.record_key=CONCAT('group:',LPAD(p.project_no,2,'0'))
      LEFT JOIN `crad_research_group_members` m ON m.research_group_id=g.record_id
      GROUP BY p.project_no
      HAVING COUNT(m.id) <> 5
    ) THEN 'ERROR: a demo group does not have exactly five students'
    WHEN EXISTS (
      SELECT 1 FROM `tmp_crad_demo_project` p
      JOIN `crad_demo_seed_records` g
        ON g.batch_id=@crad_demo_batch
       AND g.record_key=CONCAT('official-group:',LPAD(p.project_no,2,'0'))
      LEFT JOIN `crad_research_group_members` m ON m.research_group_id=g.record_id
      WHERE p.project_no BETWEEN 13 AND 16
      GROUP BY p.project_no
      HAVING COUNT(m.id) <> 5
    ) THEN 'ERROR: an official demo group does not have exactly five students'
    WHEN EXISTS (
      SELECT 1 FROM `tmp_crad_demo_project` p
      JOIN `crad_demo_seed_records` g
        ON g.batch_id=@crad_demo_batch AND g.record_key=CONCAT('official-group:',LPAD(p.project_no,2,'0'))
      LEFT JOIN `crad_title_approvals` t ON t.id=(
        SELECT r.record_id FROM `crad_demo_seed_records` r
        WHERE r.batch_id=@crad_demo_batch AND r.record_key=CONCAT('title:',LPAD(p.project_no,2,'0'))
      )
      WHERE p.project_no BETWEEN 13 AND 16
        AND (t.id IS NULL OR t.status<>'Approved' OR t.coordinator_status<>'Approved'
          OR t.crad_status<>'Approved' OR COALESCE(t.adviser_signature_data,'')=''
          OR COALESCE(t.coordinator_signature_data,'')='' OR COALESCE(t.crad_signature_data,'')='')
    ) THEN 'ERROR: an official group is missing a complete title approval'
    ELSE 'CRAD demo prerequisite checks passed'
  END AS `crad_demo_validation`;


SELECT table_name,COUNT(*) AS records FROM crad_demo_seed_records WHERE batch_id=@crad_demo_batch GROUP BY table_name ORDER BY table_name;

SELECT COUNT(DISTINCT group_id) AS groups,MAX(member_count) AS largest_group FROM (SELECT g.id AS group_id,COUNT(m.id) AS member_count FROM crad_demo_seed_records r JOIN crad_research_groups g ON r.table_name='crad_research_groups' AND r.record_id=g.id LEFT JOIN crad_research_group_members m ON m.research_group_id=g.id WHERE r.batch_id=@crad_demo_batch GROUP BY g.id) demo_groups;