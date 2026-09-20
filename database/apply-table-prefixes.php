<?php
/**
 * Apply sms_ / crad_ table prefixes to SQL dumps and PHP application code.
 *
 * Usage:
 *   C:\xampp\php\php.exe database\apply-table-prefixes.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);

$smsTables = [
    'password_reset_requests',
    'user_authenticators',
    'user_passkeys',
    'login_throttles',
    'password_resets',
    'role_permissions',
    'security_otps',
    'system_settings',
    'activity_logs',
    'schema_migrations',
    'admin_announcements',
    'student_profiles',
    'roles',
    'users',
];

$cradTables = [
    'chapter_evaluation_notifications',
    'chapter_submission_history',
    'chapter_evaluations',
    'chapter_submissions',
    'final_defense_evaluations',
    'final_defense_recommendations',
    'final_manuscript_approvals',
    'grant_document_repository_items',
    'grant_document_repository',
    'grant_funded_progress_evidence',
    'grant_funded_project_milestones',
    'grant_funding_disbursements',
    'grant_final_output_submissions',
    'grant_publications_ip_repository',
    'grant_proposal_approval_steps',
    'grant_proposal_approval_workflows',
    'grant_proposal_evaluations',
    'grant_proposal_notifications',
    'grant_proposal_versions',
    'grant_applications',
    'grant_opportunities',
    'manuscript_evaluations',
    'manuscript_submissions',
    'panel_assignment_notifications',
    'panel_member_availability',
    'preoral_defense_evaluations',
    'proposal_documents',
    'proposal_drafts',
    'proposal_members',
    'proposal_status_logs',
    'publications',
    'research_adviser_assignments',
    'research_coordinator_assignments',
    'research_defense_schedules',
    'research_groups',
    'research_milestones',
    'research_panel_assignments',
    'research_plans',
    'research_progress_activity_logs',
    'research_progress_attachments',
    'research_progress_feedback',
    'research_progress_notifications',
    'research_progress_updates',
    'research_proposals',
    'research_revision_cycles',
    'research_venues',
    'research_services_clearances',
    'research_clearance_notifications',
    'research_clearance_payments',
    'title_approvals',
    'ai_request_events',
    'research_progress_ai_analyses',
];

// Longest names first so grant_document_repository_items beats grant_document_repository.
usort($smsTables, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
usort($cradTables, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

$allMap = [];
foreach ($smsTables as $t) {
    $allMap[$t] = 'sms_' . $t;
}
foreach ($cradTables as $t) {
    $allMap[$t] = 'crad_' . $t;
}

/**
 * Replace unprefixed table identifiers with prefixed ones.
 */
function replaceTableNames(string $raw, array $map): string
{
    // Already fully prefixed dumps — skip (idempotent).
    if (str_contains($raw, '`sms_users`') && str_contains($raw, '`crad_title_approvals`')
        && !str_contains($raw, 'CREATE TABLE `users`') && !str_contains($raw, 'CREATE TABLE `title_approvals`')) {
        return $raw;
    }

    $out = $raw;

    // Hard-coded cross-database qualifiers → same-DB prefixed table.
    $out = str_replace('sms2_db.users', 'sms_users', $out);
    $out = str_replace('`sms2_db`.`users`', '`sms_users`', $out);
    $out = str_replace('sms2_db.roles', 'sms_roles', $out);

    // Tables already created as crad_* in some helpers — normalize double prefix.
    $out = str_replace('crad_crad_', 'crad_', $out);

    foreach ($map as $old => $new) {
        // Skip if this file already uses the new name exclusively for CREATE.
        $patterns = [
            // Backtick-quoted identifiers (SQL dumps, many PHP strings)
            '/`' . preg_quote($old, '/') . '`/' => '`' . $new . '`',
            // CREATE TABLE IF NOT EXISTS name
            '/\\bCREATE\\s+TABLE\\s+IF\\s+NOT\\s+EXISTS\\s+' . preg_quote($old, '/') . '\\b/i'
                => 'CREATE TABLE IF NOT EXISTS ' . $new,
            // SHOW TABLES LIKE 'name'
            "/SHOW\\s+TABLES\\s+LIKE\\s+'" . preg_quote($old, '/') . "'/i"
                => "SHOW TABLES LIKE '" . $new . "'",
            // FROM / INTO / JOIN / UPDATE / REFERENCES (unquoted)
            '/\\bFROM\\s+' . preg_quote($old, '/') . '\\b/' => 'FROM ' . $new,
            '/\\bINTO\\s+' . preg_quote($old, '/') . '\\b/' => 'INTO ' . $new,
            '/\\bJOIN\\s+' . preg_quote($old, '/') . '\\b/' => 'JOIN ' . $new,
            '/\\bUPDATE\\s+' . preg_quote($old, '/') . '\\b/' => 'UPDATE ' . $new,
            '/\\bDELETE\\s+FROM\\s+' . preg_quote($old, '/') . '\\b/' => 'DELETE FROM ' . $new,
            '/\\bREFERENCES\\s+' . preg_quote($old, '/') . '\\b/' => 'REFERENCES ' . $new,
            '/\\bTABLE\\s+' . preg_quote($old, '/') . '\\b/' => 'TABLE ' . $new,
            '/\\bDROP\\s+TABLE\\s+IF\\s+EXISTS\\s+' . preg_quote($old, '/') . '\\b/i'
                => 'DROP TABLE IF EXISTS ' . $new,
        ];

        foreach ($patterns as $pattern => $replacement) {
            $out = preg_replace($pattern, $replacement, $out) ?? $out;
        }
    }

    // Explicit helpers that use short names in PHP string fragments.
    $out = str_replace("SHOW TABLES LIKE 'users'", "SHOW TABLES LIKE 'sms_users'", $out);
    $out = str_replace("SHOW TABLES LIKE 'roles'", "SHOW TABLES LIKE 'sms_roles'", $out);
    $out = str_replace("FROM `users`", "FROM `sms_users`", $out);
    $out = str_replace("INTO `users`", "INTO `sms_users`", $out);
    $out = str_replace("JOIN `users`", "JOIN `sms_users`", $out);
    $out = str_replace("UPDATE `users`", "UPDATE `sms_users`", $out);
    $out = str_replace("REFERENCES `users`", "REFERENCES `sms_users`", $out);
    $out = str_replace("REFERENCES `roles`", "REFERENCES `sms_roles`", $out);

    // Aliased forms: FROM users u / JOIN roles r (word boundary after name)
    foreach (['users' => 'sms_users', 'roles' => 'sms_roles'] as $old => $new) {
        $out = preg_replace('/\\bFROM\\s+' . $old . '(\\s+)/', 'FROM ' . $new . '$1', $out) ?? $out;
        $out = preg_replace('/\\bJOIN\\s+' . $old . '(\\s+)/', 'JOIN ' . $new . '$1', $out) ?? $out;
        $out = preg_replace('/\\bINTO\\s+' . $old . '(\\s*\\()/', 'INTO ' . $new . '$1', $out) ?? $out;
        $out = preg_replace('/\\bUPDATE\\s+' . $old . '(\\s+)/', 'UPDATE ' . $new . '$1', $out) ?? $out;
    }

    return $out;
}

$extensions = ['php', 'sql', 'md', 'txt', 'js'];
$skipDirs = [
    '.git',
    'node_modules',
    'vendor',
    'storage',
    '.cursor',
];

$changed = 0;
$scanned = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));

    $parts = explode('/', $rel);
    if (isset($parts[0]) && in_array($parts[0], $skipDirs, true)) {
        continue;
    }

    $ext = strtolower($file->getExtension());
    if (!in_array($ext, $extensions, true)) {
        continue;
    }

    // Do not rewrite this renamer's source map arrays.
    if ($rel === 'database/apply-table-prefixes.php') {
        continue;
    }

    $raw = (string) file_get_contents($path);
    $scanned++;

    // Skip if already looks fully prefixed for core tables and no old CREATE users.
    if ($ext === 'sql' && str_contains($raw, '`sms_users`') && !str_contains($raw, 'CREATE TABLE `users`')
        && (!str_contains($raw, 'CREATE TABLE `title_approvals`') || str_contains($raw, '`crad_title_approvals`'))
        && !str_contains($raw, 'CREATE TABLE `admin_announcements`')
        && !str_contains($raw, 'CREATE TABLE `student_profiles`')
        && !str_contains($raw, 'CREATE TABLE `research_services_clearances`')) {
        // Still may need sms2_db.users cleanup in non-dump files; SQL dumps OK to skip if clean.
        if (!str_contains($raw, 'sms2_db.users') && !str_contains($raw, 'CREATE TABLE `users`')) {
            continue;
        }
    }

    $out = replaceTableNames($raw, $allMap);

    // Quoted table-name literals (avoid short ambiguous words like users/roles in prose).
    foreach ($allMap as $old => $new) {
        if (in_array($old, ['users', 'roles'], true)) {
            continue;
        }
        $out = str_replace("'" . $old . "'", "'" . $new . "'", $out);
        $out = str_replace('"' . $old . '"', '"' . $new . '"', $out);
    }

    if ($out !== $raw) {
        file_put_contents($path, $out);
        $changed++;
        echo "Updated: {$rel}\n";
    }
}

echo "\nScanned {$scanned} files, updated {$changed}.\n";
echo "sms_users / crad_title_approvals expected in database/sms2_db.sql\n";
