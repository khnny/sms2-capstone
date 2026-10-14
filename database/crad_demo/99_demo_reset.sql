-- Remove only the CRAD demo batch and test-created records attached to its
-- registered fictional students/groups. Back up sms2_db before running.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET @crad_demo_batch := 'CRAD_DEMO_2026_01';
START TRANSACTION;

-- The batch registry is the authority for seeded records. These temporary
-- ID sets also capture groups, proposals, and title submissions created later
-- through the UI for the seeded demo students.
CREATE TEMPORARY TABLE `tmp_crad_demo_group_ids` (
  `id` bigint unsigned NOT NULL PRIMARY KEY
) ENGINE=MEMORY;
CREATE TEMPORARY TABLE `tmp_crad_demo_proposal_ids` (
  `id` bigint unsigned NOT NULL PRIMARY KEY
) ENGINE=MEMORY;
CREATE TEMPORARY TABLE `tmp_crad_demo_title_ids` (
  `id` bigint unsigned NOT NULL PRIMARY KEY
) ENGINE=MEMORY;

INSERT IGNORE INTO `tmp_crad_demo_group_ids` (`id`)
SELECT `record_id` FROM `crad_demo_seed_records`
WHERE `batch_id`=@crad_demo_batch AND `table_name`='crad_research_groups';

INSERT IGNORE INTO `tmp_crad_demo_group_ids` (`id`)
SELECT DISTINCT g.id
FROM `crad_research_groups` g
JOIN `sms2_users` u ON u.student_id=g.leader_id
JOIN `crad_demo_seed_records` r
  ON r.batch_id=@crad_demo_batch AND r.table_name='sms2_users' AND r.record_id=u.id
WHERE u.student_id IS NOT NULL AND u.student_id<>''
  AND NOT EXISTS (
    SELECT 1
    FROM `crad_research_group_members` m
    WHERE m.research_group_id=g.id
      AND NOT EXISTS (
        SELECT 1
        FROM `sms2_users` demo_student
        JOIN `crad_demo_seed_records` demo_user
          ON demo_user.batch_id=@crad_demo_batch
         AND demo_user.table_name='sms2_users'
         AND demo_user.record_id=demo_student.id
        WHERE demo_student.student_id=m.student_id
      )
  );

INSERT IGNORE INTO `tmp_crad_demo_proposal_ids` (`id`)
SELECT `record_id` FROM `crad_demo_seed_records`
WHERE `batch_id`=@crad_demo_batch AND `table_name`='crad_research_proposals';

INSERT IGNORE INTO `tmp_crad_demo_proposal_ids` (`id`)
SELECT DISTINCT g.proposal_id
FROM `crad_research_groups` g
WHERE g.id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
  AND g.proposal_id IS NOT NULL;

INSERT IGNORE INTO `tmp_crad_demo_title_ids` (`id`)
SELECT `record_id` FROM `crad_demo_seed_records`
WHERE `batch_id`=@crad_demo_batch AND `table_name`='crad_title_approvals';

INSERT IGNORE INTO `tmp_crad_demo_title_ids` (`id`)
SELECT DISTINCT g.title_approval_id
FROM `crad_research_groups` g
WHERE g.id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
  AND g.title_approval_id IS NOT NULL;

INSERT IGNORE INTO `tmp_crad_demo_title_ids` (`id`)
SELECT DISTINCT t.id
FROM `crad_title_approvals` t
JOIN `sms2_users` u ON u.student_id=t.student_id
JOIN `crad_demo_seed_records` r
  ON r.batch_id=@crad_demo_batch AND r.table_name='sms2_users' AND r.record_id=u.id
WHERE u.student_id IS NOT NULL AND u.student_id<>'';

DELETE FROM `crad_research_progress_notifications`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_progress_notifications'
)
OR (
  related_entity_type='progress_update'
  AND related_entity_id IN (
    SELECT id FROM `crad_research_progress_updates`
    WHERE research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
  )
)
OR (
  related_entity_type='feedback'
  AND related_entity_id IN (
    SELECT f.id
    FROM `crad_research_progress_feedback` f
    JOIN `crad_research_plans` p ON p.id=f.research_plan_id
    WHERE p.research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
  )
);

DELETE FROM `crad_chapter_evaluation_notifications`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_chapter_evaluation_notifications'
)
OR submission_id IN (
  SELECT id FROM `crad_chapter_submissions`
  WHERE research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
)
AND (
  event_key LIKE 'evaluator:new:%'
  OR event_key LIKE 'student:accepted:%'
  OR event_key LIKE 'student:under_review:%'
);

DELETE FROM `crad_panel_assignment_notifications`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_panel_assignment_notifications'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_publications`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_publications'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_final_manuscript_approvals`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_final_manuscript_approvals'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_manuscript_evaluations`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_manuscript_evaluations'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_manuscript_submissions`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_manuscript_submissions'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_final_defense_evaluations`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_final_defense_evaluations'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_final_defense_recommendations`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_final_defense_recommendations'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_research_revision_cycles`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_revision_cycles'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_preoral_defense_evaluations`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_preoral_defense_evaluations'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_research_panel_assignments`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_panel_assignments'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_research_defense_schedules`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_defense_schedules'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_chapter_evaluations`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_chapter_evaluations'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_chapter_submissions`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_chapter_submissions'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_research_progress_feedback`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_progress_feedback'
)
OR research_plan_id IN (
  SELECT id FROM `crad_research_plans`
  WHERE research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
);

DELETE FROM `crad_research_progress_updates`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_progress_updates'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_research_milestones`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_milestones'
)
OR research_plan_id IN (
  SELECT id FROM `crad_research_plans`
  WHERE research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
);

DELETE FROM `crad_research_plans`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_plans'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_research_services_clearances`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_services_clearances'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_research_group_members`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_group_members'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_research_assignment_cycles`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_assignment_cycles'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
OR group_number IN (
  SELECT group_number FROM `crad_research_groups`
  WHERE id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
);

DELETE FROM `crad_research_coordinator_assignments`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_coordinator_assignments'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
OR group_number IN (
  SELECT group_number FROM `crad_research_groups`
  WHERE id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
);

DELETE FROM `crad_research_adviser_assignments`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_research_adviser_assignments'
)
OR research_group_id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
OR group_number IN (
  SELECT group_number FROM `crad_research_groups`
  WHERE id IN (SELECT id FROM `tmp_crad_demo_group_ids`)
)
OR proposal_id IN (SELECT id FROM `tmp_crad_demo_proposal_ids`);

DELETE FROM `crad_research_groups`
WHERE id IN (SELECT id FROM `tmp_crad_demo_group_ids`);

DELETE FROM `crad_research_proposals`
WHERE id IN (SELECT id FROM `tmp_crad_demo_proposal_ids`);

DELETE FROM `crad_title_approvals`
WHERE id IN (SELECT id FROM `tmp_crad_demo_title_ids`);

DELETE FROM `crad_panel_member_availability`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='crad_panel_member_availability'
)
OR panel_user_id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='sms2_users'
);

DELETE FROM `sms2_student_profiles`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='sms2_student_profiles'
);

DELETE FROM `sms2_users`
WHERE id IN (
  SELECT record_id FROM `crad_demo_seed_records`
  WHERE batch_id=@crad_demo_batch AND table_name='sms2_users'
);

DELETE FROM `crad_demo_seed_records`
WHERE batch_id=@crad_demo_batch;

COMMIT;

-- The registry remains installed for subsequent imports. This SQL reset does
-- not remove uploaded files created during UI testing; review demo paths in
-- the application's upload storage separately before deleting any files.
