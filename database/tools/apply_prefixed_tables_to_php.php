<?php
/**
 * Rewrite PHP sources to use prefixed physical table names from config/tables.php.
 *
 * Conservative: only replaces backtick-quoted names and SQL keyword + table patterns.
 * Skips vendor-like dirs. Creates .preprefix.bak beside each changed file once.
 *
 *   php database/tools/apply_prefixed_tables_to_php.php
 *   php database/tools/apply_prefixed_tables_to_php.php --dry-run
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/config/tables.php';

$dryRun = in_array('--dry-run', $argv, true);

$map = sms2_table_map();
$all = [];
foreach (['sms2', 'crad'] as $group) {
    foreach ($map[$group] as $logical => $physical) {
        if ($logical === 'schema_migrations') {
            continue; // migrate-lib still uses schema_migrations by default
        }
        $all[$logical] = $physical;
    }
}
uksort($all, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));

$skipDirs = [
    DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR,
];

$skipFiles = [
    'tables.php',
    'prefix_sql_dumps.php',
    'apply_prefixed_tables_to_php.php',
    'migrate_to_single_prefixed.php',
];

/**
 * @param array<string, string> $all
 */
function rewrite_php_content(string $content, array $all): string
{
    // Cross-db qualifier → plain prefixed table (do NOT strip bare "crad_db." from paths like crad_db.sql)
    $content = str_replace('sms2_db.users', 'sms2_users', $content);
    $content = str_replace('`sms2_db`.`users`', '`sms2_users`', $content);
    $content = preg_replace('/\bcrad_db\.(?=`?[a-z_])/i', '', $content) ?? $content;

    foreach ($all as $logical => $physical) {
        // Already prefixed — skip if content only has physical
        $content = str_replace('`' . $logical . '`', '`' . $physical . '`', $content);

        // SHOW TABLES LIKE 'logical'
        $content = str_replace("LIKE '" . $logical . "'", "LIKE '" . $physical . "'", $content);
        $content = str_replace('LIKE "' . $logical . '"', 'LIKE "' . $physical . '"', $content);

        // Keyword + optional backtick + logical name + word boundary
        $patterns = [
            '/\b(FROM|JOIN|INTO|UPDATE|TABLE|EXISTS)\s+`' . preg_quote($logical, '/') . '`\b/i',
            '/\b(FROM|JOIN|INTO|UPDATE|TABLE|EXISTS)\s+' . preg_quote($logical, '/') . '\b/i',
            '/\b(LEFT\s+JOIN|RIGHT\s+JOIN|INNER\s+JOIN|CROSS\s+JOIN)\s+`' . preg_quote($logical, '/') . '`\b/i',
            '/\b(LEFT\s+JOIN|RIGHT\s+JOIN|INNER\s+JOIN|CROSS\s+JOIN)\s+' . preg_quote($logical, '/') . '\b/i',
            '/\bREFERENCES\s+`' . preg_quote($logical, '/') . '`\b/i',
            '/\bREFERENCES\s+' . preg_quote($logical, '/') . '\b/i',
            '/\bON\s+`' . preg_quote($logical, '/') . '`\b/i', // CREATE TRIGGER ... ON table
        ];
        foreach ($patterns as $pattern) {
            $content = preg_replace_callback(
                $pattern,
                static function (array $m) use ($physical): string {
                    $kw = $m[1];
                    // Preserve keyword casing from match
                    return $kw . ' `' . $physical . '`';
                },
                $content
            ) ?? $content;
        }

        // DELETE FROM logical
        $content = preg_replace(
            '/\bDELETE\s+FROM\s+`' . preg_quote($logical, '/') . '`\b/i',
            'DELETE FROM `' . $physical . '`',
            $content
        ) ?? $content;
        $content = preg_replace(
            '/\bDELETE\s+FROM\s+' . preg_quote($logical, '/') . '\b/i',
            'DELETE FROM `' . $physical . '`',
            $content
        ) ?? $content;

        // INSERT IGNORE INTO / REPLACE INTO
        $content = preg_replace(
            '/\b(INSERT(?:\s+IGNORE)?|REPLACE)\s+INTO\s+`' . preg_quote($logical, '/') . '`\b/i',
            '$1 INTO `' . $physical . '`',
            $content
        ) ?? $content;
        $content = preg_replace(
            '/\b(INSERT(?:\s+IGNORE)?|REPLACE)\s+INTO\s+' . preg_quote($logical, '/') . '\b/i',
            '$1 INTO `' . $physical . '`',
            $content
        ) ?? $content;

        // ALTER TABLE / TRUNCATE / DROP TABLE / CREATE TABLE IF NOT EXISTS
        $content = preg_replace(
            '/\b(ALTER|TRUNCATE|DROP|CREATE)\s+TABLE(\s+IF\s+NOT\s+EXISTS)?\s+`' . preg_quote($logical, '/') . '`\b/i',
            '$1 TABLE$2 `' . $physical . '`',
            $content
        ) ?? $content;
        $content = preg_replace(
            '/\b(ALTER|TRUNCATE|DROP|CREATE)\s+TABLE(\s+IF\s+NOT\s+EXISTS)?\s+' . preg_quote($logical, '/') . '\b/i',
            '$1 TABLE$2 `' . $physical . '`',
            $content
        ) ?? $content;

        // SHOW COLUMNS FROM / DESCRIBE
        $content = preg_replace(
            '/\bSHOW\s+COLUMNS\s+FROM\s+`' . preg_quote($logical, '/') . '`\b/i',
            'SHOW COLUMNS FROM `' . $physical . '`',
            $content
        ) ?? $content;
        $content = preg_replace(
            '/\bSHOW\s+COLUMNS\s+FROM\s+' . preg_quote($logical, '/') . '\b/i',
            'SHOW COLUMNS FROM `' . $physical . '`',
            $content
        ) ?? $content;
        $content = preg_replace(
            '/\bDESCRIBE\s+`' . preg_quote($logical, '/') . '`\b/i',
            'DESCRIBE `' . $physical . '`',
            $content
        ) ?? $content;
        $content = preg_replace(
            '/\bDESCRIBE\s+' . preg_quote($logical, '/') . '\b/i',
            'DESCRIBE `' . $physical . '`',
            $content
        ) ?? $content;
    }

    return $content;
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$changed = 0;
$scanned = 0;

/** @var SplFileInfo $file */
foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $norm = str_replace('/', DIRECTORY_SEPARATOR, $path);
    $skip = false;
    foreach ($skipDirs as $dir) {
        if (str_contains($norm, $dir)) {
            $skip = true;
            break;
        }
    }
    if ($skip || in_array($file->getBasename(), $skipFiles, true)) {
        continue;
    }
    // Skip backup dumps tools under database/tools except we already skipped by name
    if (str_ends_with($path, '.preprefix.bak')) {
        continue;
    }

    $scanned++;
    $original = (string) file_get_contents($path);
    $updated = rewrite_php_content($original, $all);
    if ($updated === $original) {
        continue;
    }

    $changed++;
    echo ($dryRun ? '[dry] ' : '') . substr($path, strlen($root) + 1) . PHP_EOL;
    if (!$dryRun) {
        $bak = $path . '.preprefix.bak';
        if (!is_file($bak)) {
            copy($path, $bak);
        }
        file_put_contents($path, $updated);
    }
}

echo PHP_EOL . "Scanned {$scanned} PHP files; " . ($dryRun ? 'would change' : 'changed') . " {$changed}." . PHP_EOL;
