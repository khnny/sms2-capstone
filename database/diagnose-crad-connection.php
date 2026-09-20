<?php
/**
 * Safe CRAD DB connectivity check (CLI only — no passwords printed).
 *
 *   php database/diagnose-crad-connection.php
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/modules/crad/config/config.php';

echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'yes' : 'NO') . PHP_EOL;
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : '(undef)') . PHP_EOL;
echo "DB_PORT: " . (defined('DB_PORT') ? DB_PORT : '(undef)') . PHP_EOL;
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : '(undef)') . PHP_EOL;
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : '(undef)') . PHP_EOL;
echo "CRAD_DB_HOST: " . CRAD_DB_HOST . PHP_EOL;
echo "CRAD_DB_PORT: " . CRAD_DB_PORT . PHP_EOL;
echo "CRAD_DB_NAME: " . CRAD_DB_NAME . PHP_EOL;
echo "CRAD_DB_USER: " . CRAD_DB_USER . PHP_EOL;
echo "CRAD_DB_PASS: " . (CRAD_DB_PASS === '' ? '(empty)' : '(set, hidden)') . PHP_EOL;

try {
    $sms = getDatabaseConnection();
    echo "Main SMS PDO: OK (" . $sms->query('SELECT DATABASE()')->fetchColumn() . ")" . PHP_EOL;
} catch (Throwable $e) {
    echo "Main SMS PDO: FAIL — " . $e->getMessage() . PHP_EOL;
}

try {
    $crad = getCradDatabaseConnection();
    $db = (string) $crad->query('SELECT DATABASE()')->fetchColumn();
    echo "CRAD PDO: OK (" . $db . ")" . PHP_EOL;

    $need = [
        'crad_chapter_submissions',
        'crad_chapter_evaluations',
        'crad_research_groups',
        'crad_title_approvals',
    ];
    foreach ($need as $table) {
        $exists = (bool) $crad->query('SHOW TABLES LIKE ' . $crad->quote($table))->fetchColumn();
        echo ($exists ? '[OK] ' : '[MISSING] ') . $table . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "CRAD PDO: FAIL — " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo "Done." . PHP_EOL;
