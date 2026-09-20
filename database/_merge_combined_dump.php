<?php
declare(strict_types=1);

$smsTables = [
    'password_reset_requests', 'user_authenticators', 'user_passkeys', 'login_throttles',
    'password_resets', 'role_permissions', 'security_otps', 'system_settings', 'activity_logs',
    'schema_migrations', 'admin_announcements', 'student_profiles', 'roles', 'users',
];
$cradTables = [
    'chapter_evaluation_notifications', 'chapter_submission_history', 'chapter_evaluations',
    'chapter_submissions', 'final_defense_evaluations', 'final_defense_recommendations',
    'final_manuscript_approvals', 'grant_document_repository_items', 'grant_document_repository',
    'grant_funded_progress_evidence', 'grant_funded_project_milestones', 'grant_funding_disbursements',
    'grant_final_output_submissions', 'grant_publications_ip_repository', 'grant_proposal_approval_steps',
    'grant_proposal_approval_workflows', 'grant_proposal_evaluations', 'grant_proposal_notifications',
    'grant_proposal_versions', 'grant_applications', 'grant_opportunities', 'manuscript_evaluations',
    'manuscript_submissions', 'panel_assignment_notifications', 'panel_member_availability',
    'preoral_defense_evaluations', 'proposal_documents', 'proposal_drafts', 'proposal_members',
    'proposal_status_logs', 'publications', 'research_adviser_assignments', 'research_coordinator_assignments',
    'research_defense_schedules', 'research_groups', 'research_milestones', 'research_panel_assignments',
    'research_plans', 'research_progress_activity_logs', 'research_progress_attachments',
    'research_progress_feedback', 'research_progress_notifications', 'research_progress_updates',
    'research_proposals', 'research_revision_cycles', 'research_venues', 'research_services_clearances',
    'research_clearance_notifications', 'research_clearance_payments', 'title_approvals',
    'ai_request_events', 'research_progress_ai_analyses',
];
usort($smsTables, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
usort($cradTables, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

function prefixDump(string $raw, array $tables, string $prefix): string
{
    $raw = preg_replace('/^CREATE DATABASE\b.*?;\s*/im', '', $raw) ?? $raw;
    $raw = preg_replace('/^USE\s+[`\']?\w+[`]?\s*;\s*/im', '', $raw) ?? $raw;

    foreach ($tables as $old) {
        $new = $prefix . $old;
        $raw = str_replace('`' . $old . '`', '`' . $new . '`', $raw);
    }
    $raw = str_replace('`' . $prefix . $prefix, '`' . $prefix, $raw);
    return $raw;
}

$root = dirname(__DIR__);
$smsPath = 'A:/download/sms2_db.sql';
$cradPath = 'A:/download/crad_db.sql';
$outPath = $root . '/database/sms2_db.sql';
$cradOut = $root . '/modules/crad/database/crad_db.sql';

$sms = file_get_contents($smsPath);
$crad = file_get_contents($cradPath);
if ($sms === false || $crad === false) {
    fwrite(STDERR, "Cannot read download dumps\n");
    exit(1);
}

$smsPref = prefixDump($sms, $smsTables, 'sms_');
$cradPref = prefixDump($crad, $cradTables, 'crad_');

$header = '-- Combined SMS + CRAD dump for a SINGLE database (sms2_db / HostForge hf_db_*).' . "\n"
    . '-- Table prefixes: sms_* (auth/users) and crad_* (research).' . "\n"
    . '-- Do NOT create a separate crad_db database. Import into the existing app database only.' . "\n"
    . '-- Generated: ' . date('c') . "\n\n"
    . "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n"
    . "SET FOREIGN_KEY_CHECKS = 0;\n"
    . "SET NAMES utf8mb4;\n\n";

$combined = $header
    . "-- ============================================================\n"
    . "-- SMS tables (sms_*)\n"
    . "-- ============================================================\n\n"
    . $smsPref
    . "\n\n-- ============================================================\n"
    . "-- CRAD tables (crad_*) — same database, not crad_db\n"
    . "-- ============================================================\n\n"
    . $cradPref
    . "\n\nSET FOREIGN_KEY_CHECKS = 1;\n";

if (file_put_contents($outPath, $combined) === false) {
    fwrite(STDERR, "Failed writing $outPath\n");
    exit(1);
}

$cradHeader = "-- CRAD table extract (crad_*). Import into sms2_db / app DB only — not a separate crad_db.\n"
    . "SET FOREIGN_KEY_CHECKS = 0;\nSET NAMES utf8mb4;\n\n"
    . $cradPref
    . "\nSET FOREIGN_KEY_CHECKS = 1;\n";
file_put_contents($cradOut, $cradHeader);

preg_match_all('/CREATE TABLE `([^`]+)`/', $combined, $m);
$tables = $m[1];
sort($tables);
$smsCount = count(array_filter($tables, static fn(string $t): bool => str_starts_with($t, 'sms_')));
$cradCount = count(array_filter($tables, static fn(string $t): bool => str_starts_with($t, 'crad_')));
echo "Wrote {$outPath}\n";
echo 'Total CREATE TABLE: ' . count($tables) . " (sms_={$smsCount} crad_={$cradCount})\n";
echo 'Has sms_users: ' . (in_array('sms_users', $tables, true) ? 'yes' : 'NO') . "\n";
echo 'Has crad_research_groups: ' . (in_array('crad_research_groups', $tables, true) ? 'yes' : 'NO') . "\n";
echo 'Bytes: ' . filesize($outPath) . "\n";
