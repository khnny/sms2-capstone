<?php
/** Integration checks. Destructive: use only a disposable sms2_test_* database. */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/database/migrate-lib.php';
if (!str_starts_with(DB_NAME, 'sms2_test_')) {
    fwrite(STDERR, "Refusing to run: DB_DATABASE must start with sms2_test_. This test rebuilds that database.\n");
    exit(1);
}
require_once dirname(__DIR__) . '/config/session.php';

$checks = 0;
function deploymentCheck(bool $ok, string $label): void
{
    global $checks;
    if (!$ok) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $checks++;
    echo 'PASS: ' . $label . PHP_EOL;
}

try {
    sms2RunMigrations(['fresh' => true]);
    $pdo = getDatabaseConnection();
    deploymentCheck((int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn() === 2, 'fresh mode retains both schemas and migration records');
    deploymentCheck((int) $pdo->query('SELECT COUNT(*) FROM sms2_users')->fetchColumn() > 0, 'SMS2 users remain after CRAD import');
    $pdo->exec("INSERT INTO sms2_system_settings (setting_key, setting_value) VALUES ('deployment_test_sentinel', 'preserve-me')");
    sms2RunMigrations();
    deploymentCheck($pdo->query("SELECT setting_value FROM sms2_system_settings WHERE setting_key = 'deployment_test_sentinel'")->fetchColumn() === 'preserve-me', 'repeat migration preserves existing data');
    $pdo->exec("DELETE FROM schema_migrations WHERE migration_key = '2026_09_21_sms2_prefixed_dump'");
    $pdo->exec('ALTER TABLE sms2_security_otps MODIFY id INT UNSIGNED NOT NULL');
    $pdo->exec('ALTER TABLE sms2_security_otps DROP PRIMARY KEY');
    $detected = false;
    try {
        sms2RunMigrations();
    } catch (RuntimeException $e) {
        $detected = str_contains($e->getMessage(), 'has no primary key');
    }
    deploymentCheck($detected && (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn() === 1, 'interrupted import with all tables but missing indexes is not marked complete');
    $pdo->exec('ALTER TABLE sms2_security_otps ADD PRIMARY KEY (id), MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT');
    sms2RunMigrations();
    deploymentCheck((int) $pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn() === 2, 'complete existing schema can be adopted after repair');
    $forceRejected = false;
    try {
        sms2RunMigrations(['force' => true]);
    } catch (RuntimeException $e) {
        $forceRejected = str_contains($e->getMessage(), 'Cannot replay a full dump');
    }
    deploymentCheck($forceRejected, 'force mode refuses to replay full dumps over existing data');
    $pdo->exec('ALTER TABLE crad_research_group_members DROP INDEX idx_rgm_group_stage, DROP COLUMN research_stage');
    sms2RunMigrations();
    deploymentCheck((bool) $pdo->query("SHOW COLUMNS FROM crad_research_group_members LIKE 'research_stage'")->fetchColumn(), 'repeat migration repairs legacy member research-stage separation');
    foreach (['crad_research_clearance_payments', 'crad_research_services_clearances'] as $table) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME IN (\'uploaded_blob\',\'uploaded_mime\',\'uploaded_size\')');
        $stmt->execute([$table]);
        deploymentCheck((int) $stmt->fetchColumn() === 3, $table . ' has durable upload columns');
    }

    $pdo->exec('ALTER TABLE sms2_admin_announcements DROP COLUMN category, DROP COLUMN scheduled_for, DROP COLUMN expires_at');
    $pdo->exec('ALTER TABLE crad_panel_member_availability DROP COLUMN availability_windows_json');
    $pdo->exec('ALTER TABLE crad_research_services_clearances DROP COLUMN crad_remarks, DROP COLUMN uploaded_blob, DROP COLUMN uploaded_mime, DROP COLUMN uploaded_size');
    sms2RunMigrations();
    deploymentCheck((bool) $pdo->query("SHOW COLUMNS FROM sms2_admin_announcements LIKE 'category'")->fetchColumn(), 'recorded migrations still apply additive announcement upgrades');
    deploymentCheck((bool) $pdo->query("SHOW COLUMNS FROM crad_panel_member_availability LIKE 'availability_windows_json'")->fetchColumn(), 'legacy panel availability gets v3 scheduling columns');
    deploymentCheck((bool) $pdo->query("SHOW COLUMNS FROM crad_research_services_clearances LIKE 'uploaded_blob'")->fetchColumn(), 'legacy clearances gain durable uploads without replaying dumps');

    $originalUsers = (int) $pdo->query('SELECT COUNT(*) FROM sms2_users')->fetchColumn();
    foreach (sms2_table_map()['sms2'] as $logical => $physical) {
        if ($logical !== 'schema_migrations') {
            $pdo->exec('RENAME TABLE ' . sms2MigrateQuoteIdentifier($physical) . ' TO ' . sms2MigrateQuoteIdentifier('sms_' . $logical));
        }
    }
    $legacyBlocked = false;
    try {
        sms2RunMigrations();
    } catch (RuntimeException $e) {
        $legacyBlocked = str_contains($e->getMessage(), 'Legacy users tables detected');
    }
    deploymentCheck($legacyBlocked, 'initial-install migration refuses to strand legacy sms_* accounts');
    $prefixCommand = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/database/migrate_to_single_prefixed.php');
    exec($prefixCommand . ' --dry-run 2>&1', $dryOutput, $dryCode);
    deploymentCheck($dryCode === 0 && (bool) $pdo->query("SHOW TABLES LIKE 'sms_users'")->fetchColumn(), 'prefix dry-run does not rename or lose legacy users');
    exec($prefixCommand . ' 2>&1', $prefixOutput, $prefixCode);
    if ($prefixCode !== 0) {
        throw new RuntimeException(implode("\n", $prefixOutput));
    }
    deploymentCheck((int) $pdo->query('SELECT COUNT(*) FROM sms2_users')->fetchColumn() === $originalUsers, 'mixed sms_* and crad_* installation converts without losing accounts');
    deploymentCheck($pdo->query("SELECT setting_value FROM sms2_system_settings WHERE setting_key = 'deployment_test_sentinel'")->fetchColumn() === 'preserve-me', 'prefix conversion preserves existing settings');

    require_once dirname(__DIR__) . '/modules/crad/includes/research-group-flow.php';
    require_once dirname(__DIR__) . '/modules/crad/includes/title-approval-assignees.php';
    require_once dirname(__DIR__) . '/modules/crad/includes/panelist-workflow.php';
    cradEnsurePanelistWorkflowSchema($pdo);
    cradEnsurePanelistAssignmentSchema($pdo);
    deploymentCheck((bool) $pdo->query("SHOW TABLES LIKE 'crad_panelist_applications'")->fetchColumn(), 'panelist installer creates application tables');
    deploymentCheck(cradRgFlowTitleUnlockState($pdo, 'TEST-LEADER')['stage'] === 'no_group', 'title is blocked before a group exists');
    $members = [];
    for ($i = 0; $i < 5; $i++) {
        $members[] = ['student_id' => $i === 0 ? 'TEST-LEADER' : 'TEST-MEMBER-' . $i, 'full_name' => 'Test Member ' . $i, 'section' => 'TEST'];
    }
    $save = static fn(array $rows, string $reason = '', bool $submit = true): array => cradRgFlowSaveSubmission($pdo, 'TEST-LEADER', 'Test Leader', 'leader@example.invalid', 'TEST', 1, $rows, $reason, $submit);
    deploymentCheck(!$save(array_slice($members, 0, 2))['ok'], 'incomplete group requires a reason');
    deploymentCheck(!$save(array_merge($members, [['student_id' => 'TEST-SIX', 'full_name' => 'Sixth Member']]))['ok'], 'six-member group is rejected');
    $result = $save($members, '', false);
    deploymentCheck($result['ok'] && cradRgFlowTitleUnlockState($pdo, 'TEST-LEADER')['stage'] === 'draft', 'draft does not unlock title submission');
    $result = $save($members);
    $groupId = (int) ($result['group_id'] ?? 0);
    deploymentCheck($result['ok'] && cradRgFlowTitleUnlockState($pdo, 'TEST-LEADER')['stage'] === 'pending_dh', 'complete group still needs Department Head approval');
    deploymentCheck(!cradRgFlowDhDecide($pdo, $groupId, 'rejected', 1)['ok'], 'Department Head rejection requires remarks');
    deploymentCheck(cradRgFlowDhDecide($pdo, $groupId, 'rejected', 1, 'Test rejection')['ok'] && cradRgFlowTitleUnlockState($pdo, 'TEST-LEADER')['stage'] === 'rejected', 'rejected group remains blocked');
    $save($members);
    deploymentCheck(cradRgFlowDhDecide($pdo, $groupId, 'approved', 1)['ok'] && cradRgFlowTitleUnlockState($pdo, 'TEST-LEADER')['stage'] === 'awaiting_assignment', 'DH approval alone does not unlock title');
    $group = cradRgFlowGetStudentGroup($pdo, 'TEST-LEADER');
    $groupNumber = (string) $group['group_number'];
    $pdo->prepare("INSERT INTO crad_research_coordinator_assignments (research_group_id, group_number, student_id, coordinator_user_id, coordinator_name, coordinator_email) VALUES (?, ?, ?, 1, 'Test Coordinator', 'coordinator@example.invalid')")->execute([$groupId, $groupNumber, 'TEST-LEADER']);
    $coordinatorId = (int) $pdo->lastInsertId();
    $cycle = cradRgFlowStartAssignmentCycle($pdo, $groupId, $groupNumber, 'TEST-LEADER', $coordinatorId, null, 1);
    deploymentCheck(!empty($cycle['ok']), 'coordinator-only assignment starts a cycle');
    $cycleId = (int) $pdo->query('SELECT MAX(id) FROM crad_research_assignment_cycles')->fetchColumn();
    deploymentCheck(cradRgFlowConfirmRole($pdo, $cycleId, 'coordinator', 1)['status'] === 'waiting_adviser', 'one confirmation cannot mark a group fully assigned');
    $pdo->prepare("INSERT INTO crad_research_adviser_assignments (research_group_id, group_number, student_id, adviser_user_id, adviser_name, adviser_email, assignment_status) VALUES (?, ?, ?, 1, 'Test Adviser', 'adviser@example.invalid', 'Assigned')")->execute([$groupId, $groupNumber, 'TEST-LEADER']);
    $adviserId = (int) $pdo->lastInsertId();
    $extended = cradRgFlowStartAssignmentCycle($pdo, $groupId, $groupNumber, 'TEST-LEADER', $coordinatorId, $adviserId, 1);
    deploymentCheck(!empty($extended['ok']) && (int) $pdo->query('SELECT MAX(id) FROM crad_research_assignment_cycles')->fetchColumn() === $cycleId, 'later adviser assignment extends the existing cycle');
    deploymentCheck((bool) $pdo->query('SELECT coordinator_confirmed_at FROM crad_research_assignment_cycles WHERE id = ' . $cycleId)->fetchColumn(), 'extending a cycle preserves coordinator confirmation');
    deploymentCheck(cradRgFlowTitleUnlockState($pdo, 'TEST-LEADER')['stage'] === 'awaiting_confirmation', 'title stays blocked until both assigned roles confirm');
    deploymentCheck(cradRgFlowConfirmRole($pdo, $cycleId, 'adviser', 1)['status'] === 'confirmed', 'second confirmation marks Fully Assigned');
    deploymentCheck(cradRgFlowTitleUnlockState($pdo, 'TEST-LEADER')['ok'], 'DH approval and both confirmations unlock title');
    deploymentCheck(!cradRgFlowConfirmRole($pdo, $cycleId, 'adviser', 1)['ok'], 'completed cycle rejects duplicate confirmation');
    $pdo->prepare("INSERT INTO crad_research_adviser_assignments (research_group_id, group_number, student_id, adviser_user_id, adviser_name, assignment_status) VALUES (?, ?, ?, 1, 'Replacement Adviser', 'Assigned')")->execute([$groupId, $groupNumber, 'TEST-LEADER']);
    $replacementId = (int) $pdo->lastInsertId();
    $new = cradRgFlowStartAssignmentCycle($pdo, $groupId, $groupNumber, 'TEST-LEADER', $coordinatorId, $replacementId, 1);
    $newId = (int) $pdo->query('SELECT MAX(id) FROM crad_research_assignment_cycles')->fetchColumn();
    deploymentCheck(!empty($new['ok']) && $newId !== $cycleId && !cradRgFlowTitleUnlockState($pdo, 'TEST-LEADER')['ok'], 'reassignment blocks title until fresh confirmations');
    deploymentCheck(cradRgFlowCancelCycle($pdo, $newId, 'adviser', 1, 'Test decline')['ok'] && !cradRgFlowTitleUnlockState($pdo, 'TEST-LEADER')['ok'], 'declined assignment requires reassignment');

    require_once dirname(__DIR__) . '/modules/crad/includes/chapter-evaluation-workflow.php';
    require_once dirname(__DIR__) . '/modules/crad/includes/research-clearance-payment.php';
    deploymentCheck(!chapterIsReadyForPreOral($pdo, $groupId), 'missing chapter approvals block pre-oral');
    deploymentCheck(!rcpCanUploadStage($pdo, $groupId, 'research_2')['ok'], 'Research 2 payment is blocked before Research 1 clearance');
    $insert = $pdo->prepare("INSERT INTO crad_chapter_submissions (research_group_id, chapter_number, version_number, status, submission_token) VALUES (?, ?, ?, ?, ?)");
    for ($i = 1; $i <= 3; $i++) {
        $insert->execute([$groupId, $i, 1, 'Accepted', 'test-chapter-' . $i]);
    }
    deploymentCheck(chapterIsReadyForPreOral($pdo, $groupId), 'three accepted chapters unlock chapter-based pre-oral readiness');
    $insert->execute([$groupId, 2, 2, 'Needs Revision', 'test-chapter-2-revision']);
    deploymentCheck(!chapterIsReadyForPreOral($pdo, $groupId), 'a newer chapter needing revision blocks pre-oral');
    require_once dirname(__DIR__) . '/modules/crad/includes/final-phase-helpers.php';
    deploymentCheck(!fpIsEligibleForFinalApproval($pdo, $groupId), 'final manuscript approval is blocked without final-defense schedule and panel');
    require_once dirname(__DIR__) . '/includes/notifications.php';
    $pdo->exec("INSERT INTO crad_panel_assignment_notifications (event_key, recipient_user_id, recipient_role, research_group_id, title, body) VALUES ('deployment-test-panel-notification', 1, 'panel', NULL, 'Test panel notification', 'Integration fixture')");
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role_key'] = 'panel';
    $_SESSION['user_email'] = 'panel@example.invalid';
    $notifications = smsCurrentUserNotifications();
    deploymentCheck(in_array('deployment-test-panel-notification', array_column($notifications, 'batch_key'), true), 'panel notification query resolves prefixed tables and returns assigned notifications');
    echo "Deployment integration checks passed: {$checks}\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
