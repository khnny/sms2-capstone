<?php
/**
 * SMS 2 deployment database migration runner.
 *
 * CLI:
 *   php database/migrate.php
 *   php database/migrate.php --fresh
 *
 * Web (InfinityFree / no SSH):
 *   /setup/deploy-db.php?token=YOUR_SMS2_DEPLOY_TOKEN
 *
 * Environment:
 *   HostForge/Laravel-style: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD, DB_CHARSET
 *   Project-specific: SMS2_DB_HOST, SMS2_DB_PORT, SMS2_DB_NAME, SMS2_DB_USER, SMS2_DB_PASS, SMS2_DB_CHARSET
 *   Optional CRAD override: CRAD_DB_HOST, CRAD_DB_PORT, CRAD_DB_NAME, CRAD_DB_USER, CRAD_DB_PASS, CRAD_DB_CHARSET
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Forbidden. Run from CLI or use /setup/deploy-db.php on web hosts.\n";
    exit(1);
}

require_once __DIR__ . '/migrate-lib.php';

$options = [
    'fresh' => in_array('--fresh', $argv ?? [], true),
    'force' => in_array('--force', $argv ?? [], true),
];

try {
    foreach (sms2RunMigrations($options) as $line) {
        echo $line . PHP_EOL;
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
