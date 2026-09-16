<?php
/**
 * Merge modules/crad/database/crad_db.sql into database/sms2_db.sql
 * (single dump for HostForge / migrate). Does NOT apply sms_/crad_ prefixes —
 * run database/apply-table-prefixes.php after this.
 *
 * Usage:
 *   C:\xampp\php\php.exe database\build-unified-sms2-sql.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$sms2Path = $root . '/database/sms2_db.sql';
$cradPath = $root . '/modules/crad/database/crad_db.sql';
$backupPath = $root . '/database/sms2_db.sql.bak-before-crad-merge';

if (!is_readable($sms2Path) || !is_readable($cradPath)) {
    fwrite(STDERR, "Missing sms2_db.sql or crad_db.sql\n");
    exit(1);
}

$sms2 = (string) file_get_contents($sms2Path);
$crad = (string) file_get_contents($cradPath);

if (str_contains($sms2, '-- CRAD MODULE TABLES') || str_contains($sms2, '`crad_title_approvals`')
    || str_contains($sms2, '`crad_title_approvals`')) {
    echo "sms2_db.sql already includes CRAD tables. Nothing to do.\n";
    exit(0);
}

if (!is_file($backupPath)) {
    if (!copy($sms2Path, $backupPath)) {
        fwrite(STDERR, "Could not write backup: {$backupPath}\n");
        exit(1);
    }
    echo "Backed up original SMS2 dump to database/sms2_db.sql.bak-before-crad-merge\n";
}

// Strip CRAD dump wrapper so it lands in the currently selected database.
$crad = preg_replace('/^\\s*CREATE\\s+DATABASE\\b.*$/im', '', $crad) ?? $crad;
$crad = preg_replace('/^\\s*USE\\s+`?[^;`\\n]+`?\\s*;?\\s*$/im', '', $crad) ?? $crad;
$crad = preg_replace('/^--\\s*Database:\\s*`crad_db`\\s*$/im', '-- Database: (merged into main SMS2 dump)', $crad) ?? $crad;

// Drop trailing SET CHARACTER_SET / COMMIT from SMS dump so we can append cleanly.
$sms2 = preg_replace(
    '/\\nSET FOREIGN_KEY_CHECKS=1;\\s*\\nCOMMIT;\\s*(?:\\/\\*!40101[\\s\\S]*)?\\z/',
    "\n",
    $sms2
) ?? $sms2;
$sms2 = rtrim($sms2) . "\n\n";

// Drop leading SET/START from CRAD if already present in SMS header.
$crad = preg_replace(
    '/\\A[\\s\\S]*?(?=--\\s*-{5,}|CREATE TABLE)/',
    '',
    $crad,
    1
) ?? $crad;

$merged = $sms2
    . "-- ============================================================\n"
    . "-- CRAD MODULE TABLES (merged from modules/crad/database/crad_db.sql)\n"
    . "-- Import into the same database as SMS2 (DB_DATABASE / DB_NAME).\n"
    . "-- ============================================================\n\n"
    . "SET FOREIGN_KEY_CHECKS=0;\n\n"
    . trim($crad) . "\n";

if (!str_contains($merged, 'SET FOREIGN_KEY_CHECKS=1')) {
    $merged = rtrim($merged) . "\n\nSET FOREIGN_KEY_CHECKS=1;\nCOMMIT;\n";
}

if (file_put_contents($sms2Path, $merged) === false) {
    fwrite(STDERR, "Failed to write {$sms2Path}\n");
    exit(1);
}

preg_match_all('/CREATE\\s+TABLE\\s+`([^`]+)`/i', $merged, $matches);
$count = count(array_unique($matches[1] ?? []));
echo "Unified dump written to database/sms2_db.sql ({$count} CREATE TABLE statements).\n";
echo "Next: C:\\xampp\\php\\php.exe database\\apply-table-prefixes.php\n";
