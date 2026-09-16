<?php
declare(strict_types=1);

$root = dirname(__DIR__);

$smsTables = [
    'password_reset_requests', 'user_authenticators', 'user_passkeys', 'login_throttles',
    'password_resets', 'role_permissions', 'security_otps', 'system_settings',
    'activity_logs', 'schema_migrations', 'roles', 'users',
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
    'proposal_status_logs', 'publications', 'research_adviser_assignments',
    'research_coordinator_assignments', 'research_defense_schedules', 'research_groups',
    'research_milestones', 'research_panel_assignments', 'research_plans',
    'research_progress_activity_logs', 'research_progress_attachments', 'research_progress_feedback',
    'research_progress_notifications', 'research_progress_updates', 'research_proposals',
    'research_revision_cycles', 'research_venues', 'title_approvals', 'ai_request_events',
    'research_progress_ai_analyses',
];

$skipDirs = ['vendor', 'node_modules', '.git'];
$skipFiles = [
    'apply-table-prefixes.php',
    'build-unified-sms2-sql.php',
    '_sweep_unprefixed.php',
];

$hits = [];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $path = $file->getPathname();
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));
    foreach ($skipDirs as $d) {
        if (str_starts_with($rel, $d . '/')) {
            continue 2;
        }
    }
    if (!str_ends_with($rel, '.php')) {
        continue;
    }
    if (in_array(basename($rel), $skipFiles, true)) {
        continue;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        continue;
    }

    foreach (array_merge($smsTables, $cradTables) as $table) {
        $patterns = [
            "/(?:FROM|INTO|JOIN|UPDATE|TABLE|REFERENCES)\\s+`{$table}`/i",
            "/(?:FROM|INTO|JOIN|UPDATE|TABLE|REFERENCES)\\s+{$table}\\b/i",
            "/SHOW TABLES LIKE '{$table}'/i",
            "/AFTER (?:DELETE|INSERT|UPDATE) ON {$table}\\b/i",
            "/sms2_db\\.{$table}\\b/i",
            "/crad_db\\.{$table}\\b/i",
        ];
        foreach ($patterns as $p) {
            if (preg_match_all($p, $raw, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as $match) {
                    $line = substr_count(substr($raw, 0, $match[1]), "\n") + 1;
                    $snippet = trim(preg_replace('/\\s+/', ' ', $match[0]));
                    $hits[] = "{$rel}:{$line}: {$snippet}";
                }
            }
        }
    }
}

$hits = array_values(array_unique($hits));
sort($hits);
echo count($hits) . " leftover hit(s)\n";
foreach ($hits as $h) {
    echo $h . "\n";
}
