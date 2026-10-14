<?php
/**
 * Migrate existing dual-DB (or unprefixed single DB) → one DB with sms2_* / crad_* tables.
 *
 * Usage (from project root):
 *   php database/migrate_to_single_prefixed.php
 *   php database/migrate_to_single_prefixed.php --dry-run
 *   php database/migrate_to_single_prefixed.php --force
 *
 * Env/config: uses DB_* (target). Optional legacy CRAD source:
 *   LEGACY_CRAD_DB_NAME (default: crad_db if that DB exists and differs from DB_NAME)
 *
 * Safe defaults: refuses if prefixed tables already exist unless --force.
 * Does NOT drop the legacy crad_db database.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Forbidden. Run the prefix migration from CLI only.');
}

$root = dirname(__DIR__);
require_once $root . '/config/database.php';
require_once $root . '/config/tables.php';

$dryRun = in_array('--dry-run', $argv, true);
$force = in_array('--force', $argv, true);

function out(string $m): void
{
    echo $m . PHP_EOL;
}

function qid(string $id): string
{
    return '`' . str_replace('`', '``', $id) . '`';
}

/**
 * @return list<string>
 */
function listTables(PDO $pdo, string $schema): array
{
    $stmt = $pdo->prepare(
        'SELECT TABLE_NAME FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = \'BASE TABLE\'
         ORDER BY TABLE_NAME'
    );
    $stmt->execute([$schema]);
    return array_column($stmt->fetchAll(), 'TABLE_NAME');
}

function databaseExists(PDO $pdo, string $schema): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ? LIMIT 1'
    );
    $stmt->execute([$schema]);
    return (bool) $stmt->fetchColumn();
}

$map = sms2_table_map();
$sms2Map = $map['sms2'];
unset($sms2Map['schema_migrations']);
$cradMap = $map['crad'];

$targetDb = DB_NAME;
$legacyCrad = sms2_env('LEGACY_CRAD_DB_NAME', 'crad_db');

out('Target DB: ' . $targetDb);
out('Dry-run: ' . ($dryRun ? 'yes' : 'no'));

$server = new PDO(
    'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=' . DB_CHARSET,
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

if (!databaseExists($server, $targetDb)) {
    throw new RuntimeException('Target database does not exist: ' . $targetDb);
}

$server->exec('USE ' . qid($targetDb));
$existing = listTables($server, $targetDb);

$prefixedSms2 = array_values($sms2Map);
$prefixedCrad = array_values($cradMap);
$hasPrefixed = count(array_intersect($existing, array_merge($prefixedSms2, $prefixedCrad))) > 0;
$hasUnprefixedSms2 = in_array('users', $existing, true) || in_array('sms_users', $existing, true);
$hasUnprefixedCrad = in_array('research_proposals', $existing, true)
    || in_array('title_approvals', $existing, true);

if ($hasPrefixed && !$hasUnprefixedSms2 && !$hasUnprefixedCrad && !$force) {
    out('Prefixed tables already present in ' . $targetDb . '. Nothing to do (use --force to re-check).');
    exit(0);
}

// --- 1) Rename SMS2 tables in place ---
// Resolve every collision before the first rename so an error cannot leave a
// partially converted set of SMS2 tables.
foreach ($sms2Map as $old => $new) {
    $sources = array_values(array_filter([$old, 'sms_' . $old], static fn(string $name): bool => in_array($name, $existing, true)));
    if (count($sources) > 1 || ($sources && in_array($new, $existing, true))) {
        throw new RuntimeException('Ambiguous SMS2 tables for ' . $new . '. Reconcile the legacy and prefixed tables from a backup before continuing.');
    }
}
out('');
out('== Rename SMS2 tables in ' . $targetDb . ' ==');
foreach ($sms2Map as $old => $new) {
    // The original capstone used sms_* alongside already-prefixed crad_*.
    $sources = array_values(array_filter([$old, 'sms_' . $old], static fn(string $name): bool => in_array($name, $existing, true)));
    if (count($sources) > 1 || ($sources && in_array($new, $existing, true))) {
        throw new RuntimeException('Ambiguous SMS2 tables for ' . $new . '. Reconcile the legacy and prefixed tables from a backup before continuing.');
    }
    $old = $sources[0] ?? $old;
    if (in_array($new, $existing, true)) {
        out("  skip {$old} → {$new} (already exists)");
        continue;
    }
    if (!in_array($old, $existing, true)) {
        out("  skip {$old} → {$new} (source missing)");
        continue;
    }
    $sql = 'RENAME TABLE ' . qid($old) . ' TO ' . qid($new);
    out('  ' . $sql);
    if (!$dryRun) {
        $server->exec($sql);
    }
}

// Refresh
$existing = listTables($server, $targetDb);

// --- 2) Bring CRAD from legacy DB if needed ---
$needImportFromLegacy = databaseExists($server, (string) $legacyCrad)
    && (string) $legacyCrad !== $targetDb
    && !$hasUnprefixedCrad
    && count(array_intersect($existing, $prefixedCrad)) === 0;

if ($needImportFromLegacy || (databaseExists($server, (string) $legacyCrad) && (string) $legacyCrad !== $targetDb && !$hasPrefixed)) {
    out('');
    out('== Move CRAD tables from ' . $legacyCrad . ' → ' . $targetDb . ' ==');
    $legacyTables = listTables($server, (string) $legacyCrad);

    // Drop CRAD triggers on legacy first (names known)
    $triggerNames = [
        'trg_research_groups_panel_notifications_after_delete',
        'trg_research_groups_preoral_evals_after_delete',
        'trg_research_groups_preoral_evaluations_after_delete',
        'trg_title_approvals_after_delete',
    ];
    foreach ($triggerNames as $trg) {
        $sql = 'DROP TRIGGER IF EXISTS ' . qid((string) $legacyCrad) . '.' . qid($trg);
        // MySQL drop trigger is schema-scoped via USE
        out('  DROP TRIGGER IF EXISTS ' . $trg . ' (on legacy)');
        if (!$dryRun) {
            $server->exec('USE ' . qid((string) $legacyCrad));
            $server->exec('DROP TRIGGER IF EXISTS ' . qid($trg));
        }
    }
    $server->exec('USE ' . qid($targetDb));

    foreach ($cradMap as $old => $new) {
        if (in_array($new, $existing, true)) {
            out("  skip {$old} → {$new} (already in target)");
            continue;
        }
        if (!in_array($old, $legacyTables, true)) {
            out("  skip {$old} (not in legacy)");
            continue;
        }
        // RENAME across databases
        $sql = 'RENAME TABLE ' . qid((string) $legacyCrad) . '.' . qid($old)
            . ' TO ' . qid($targetDb) . '.' . qid($new);
        out('  ' . $sql);
        if (!$dryRun) {
            $server->exec($sql);
        }
    }
} elseif ($hasUnprefixedCrad) {
    out('');
    out('== Rename unprefixed CRAD tables already in ' . $targetDb . ' ==');
    // Drop triggers that reference old names
    foreach ([
        'trg_research_groups_panel_notifications_after_delete',
        'trg_research_groups_preoral_evals_after_delete',
        'trg_research_groups_preoral_evaluations_after_delete',
        'trg_title_approvals_after_delete',
    ] as $trg) {
        out('  DROP TRIGGER IF EXISTS ' . $trg);
        if (!$dryRun) {
            $server->exec('DROP TRIGGER IF EXISTS ' . qid($trg));
        }
    }
    foreach ($cradMap as $old => $new) {
        if (in_array($new, $existing, true)) {
            out("  skip {$old} → {$new}");
            continue;
        }
        if (!in_array($old, $existing, true)) {
            out("  skip {$old} (missing)");
            continue;
        }
        $sql = 'RENAME TABLE ' . qid($old) . ' TO ' . qid($new);
        out('  ' . $sql);
        if (!$dryRun) {
            $server->exec($sql);
        }
    }
} else {
    out('');
    out('No legacy crad_db move needed (or CRAD already prefixed / not present).');
}

// --- 3) Recreate triggers on prefixed tables ---
out('');
out('== Recreate CRAD triggers ==');
$triggerSql = [
    'DROP TRIGGER IF EXISTS `trg_research_groups_panel_notifications_after_delete`',
    'CREATE TRIGGER `trg_research_groups_panel_notifications_after_delete` AFTER DELETE ON `crad_research_groups` FOR EACH ROW BEGIN
                DELETE FROM crad_panel_assignment_notifications
                WHERE research_group_id = OLD.id;
            END',
    'DROP TRIGGER IF EXISTS `trg_research_groups_preoral_evals_after_delete`',
    'CREATE TRIGGER `trg_research_groups_preoral_evals_after_delete` AFTER DELETE ON `crad_research_groups` FOR EACH ROW BEGIN
                DELETE FROM crad_preoral_defense_evaluations
                WHERE research_group_id = OLD.id;
            END',
    'DROP TRIGGER IF EXISTS `trg_research_groups_preoral_evaluations_after_delete`',
    'CREATE TRIGGER `trg_research_groups_preoral_evaluations_after_delete` AFTER DELETE ON `crad_research_groups` FOR EACH ROW BEGIN
                DELETE FROM crad_preoral_defense_evaluations
                WHERE research_group_id = OLD.id;
            END',
    'DROP TRIGGER IF EXISTS `trg_title_approvals_after_delete`',
    'CREATE TRIGGER `trg_title_approvals_after_delete` AFTER DELETE ON `crad_title_approvals` FOR EACH ROW BEGIN
            DELETE FROM crad_research_coordinator_assignments
             WHERE (title_approval_id IS NOT NULL AND title_approval_id = OLD.id)
                OR (OLD.student_id IS NOT NULL AND OLD.student_id <> \'\' AND student_id = OLD.student_id)
                OR (OLD.student_id IS NOT NULL AND OLD.student_id <> \'\' AND group_number = CONCAT(\'STU-\', OLD.student_id))
                OR (OLD.proposal_number IS NOT NULL AND OLD.proposal_number <> \'\' AND proposal_number = OLD.proposal_number);
            DELETE FROM crad_research_adviser_assignments
             WHERE (OLD.student_id IS NOT NULL AND OLD.student_id <> \'\' AND student_id = OLD.student_id)
                OR (OLD.student_id IS NOT NULL AND OLD.student_id <> \'\' AND group_number = CONCAT(\'STU-\', OLD.student_id))
                OR (OLD.proposal_number IS NOT NULL AND OLD.proposal_number <> \'\' AND proposal_number = OLD.proposal_number);
        END',
];

if (!$dryRun) {
    $server->exec('USE ' . qid($targetDb));
    // Only if crad_research_groups exists
    $finalTables = listTables($server, $targetDb);
    if (in_array('crad_research_groups', $finalTables, true)) {
        foreach ($triggerSql as $stmt) {
            out('  apply trigger DDL…');
            $server->exec($stmt);
        }
    } else {
        out('  skip triggers (crad_research_groups missing)');
    }
} else {
    out('  (dry-run) would recreate 4 triggers');
}

// Record migration marker
if (!$dryRun) {
    $server->exec(
        'CREATE TABLE IF NOT EXISTS `sms2_schema_migrations` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration_key` varchar(120) NOT NULL,
            `source_file` varchar(255) NOT NULL,
            `source_sha256` char(64) NOT NULL,
            `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_migration_key` (`migration_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    // Also keep legacy name if present — migrate-lib may still use schema_migrations
    $server->exec(
        'CREATE TABLE IF NOT EXISTS `schema_migrations` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration_key` varchar(120) NOT NULL,
            `source_file` varchar(255) NOT NULL,
            `source_sha256` char(64) NOT NULL,
            `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_migration_key` (`migration_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $ins = $server->prepare(
        'INSERT INTO `schema_migrations` (`migration_key`, `source_file`, `source_sha256`)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE applied_at = current_timestamp()'
    );
    $ins->execute([
        '2026_09_21_single_db_prefixed',
        'database/migrate_to_single_prefixed.php',
        hash_file('sha256', __FILE__),
    ]);
}

out('');
out($dryRun ? 'Dry-run complete. Re-run without --dry-run to apply.' : 'Migration complete.');
out('Legacy crad_db was NOT dropped. Verify the app, then drop it manually if desired.');
out('Next: ensure config/local.php has CRAD_DB_NAME = DB_NAME (' . $targetDb . ').');
