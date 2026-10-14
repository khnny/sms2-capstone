<?php
/**
 * One-shot: rewrite SQL dumps to sms2_* / crad_* prefixed table names.
 * Creates .preprefix.bak backups. Safe to re-run only if bak restored first.
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/config/tables.php';

$map = sms2_table_map();

/**
 * @param array<string, string> $logicalToPhysical
 */
function sms2_prefix_rewrite_sql_file(string $path, array $logicalToPhysical): void
{
    if (!is_readable($path)) {
        throw new RuntimeException('Not readable: ' . $path);
    }

    $bak = $path . '.preprefix.bak';
    if (!is_file($bak)) {
        if (!copy($path, $bak)) {
            throw new RuntimeException('Backup failed: ' . $bak);
        }
        echo "Backup: {$bak}\n";
    } else {
        // Restore from bak then rewrite (idempotent)
        copy($bak, $path);
        echo "Restored from bak: {$path}\n";
    }

    $sql = (string) file_get_contents($path);
    uksort($logicalToPhysical, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

    foreach ($logicalToPhysical as $old => $new) {
        if ($old === 'schema_migrations') {
            continue;
        }
        $sql = str_replace('`' . $old . '`', '`' . $new . '`', $sql);
    }

    // Soft-FK comments
    $sql = str_replace('sms2_db.users', 'sms2_users', $sql);
    $sql = str_replace('sms2_db users', 'sms2_users', $sql);

    file_put_contents($path, $sql);

    preg_match_all('/CREATE\s+TABLE\s+`([^`]+)`/i', $sql, $matches);
    echo basename($path) . ' tables: ' . implode(', ', $matches[1] ?? []) . "\n";
}

$sms2Map = $map['sms2'];
unset($sms2Map['schema_migrations']);

sms2_prefix_rewrite_sql_file($root . '/database/sms2_db.sql', $sms2Map);
sms2_prefix_rewrite_sql_file($root . '/modules/crad/database/crad_db.sql', $map['crad']);

echo "Done.\n";
