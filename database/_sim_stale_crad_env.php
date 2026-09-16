<?php
/**
 * Local simulation of HostForge stale CRAD_DB_* env (from production stack trace).
 * Writes NDJSON to debug-4aceee.log via getCradDatabaseConnection instrumentation.
 */
declare(strict_types=1);

putenv('DB_HOST=mariadb-5yxohe3e.internal');
putenv('DB_PORT=3306');
putenv('DB_DATABASE=hf_db_5yxohe3e');
putenv('DB_USERNAME=hf_etho0gcrm7');
putenv('DB_PASSWORD=local-sim-not-real');
putenv('DB_CONNECTION=mysql');
putenv('DB_CHARSET=utf8mb4');

putenv('CRAD_DB_HOST=mariadb-edee97zl.internal');
putenv('CRAD_DB_PORT=3306');
putenv('CRAD_DB_NAME=hf_db_edee97zl');
putenv('CRAD_DB_USER=hf_czivmdlprb');
putenv('CRAD_DB_PASS=local-sim-not-real');

$_SERVER['DB_HOST'] = getenv('DB_HOST');
$_SERVER['DB_DATABASE'] = getenv('DB_DATABASE');
$_SERVER['DB_USERNAME'] = getenv('DB_USERNAME');
$_SERVER['DB_PASSWORD'] = getenv('DB_PASSWORD');
$_SERVER['CRAD_DB_HOST'] = getenv('CRAD_DB_HOST');
$_SERVER['CRAD_DB_NAME'] = getenv('CRAD_DB_NAME');
$_SERVER['CRAD_DB_USER'] = getenv('CRAD_DB_USER');
$_SERVER['CRAD_DB_PASS'] = getenv('CRAD_DB_PASS');

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/modules/crad/config/config.php';

echo "CRAD_DB_HOST=" . CRAD_DB_HOST . PHP_EOL;
echo "CRAD_DB_NAME=" . CRAD_DB_NAME . PHP_EOL;
echo "DB_HOST=" . DB_HOST . PHP_EOL;
echo "DB_NAME=" . DB_NAME . PHP_EOL;

try {
    getCradDatabaseConnection();
    echo "CONNECTED\n";
} catch (Throwable $e) {
    echo "THREW: " . $e->getMessage() . PHP_EOL;
}

$log = dirname(__DIR__) . '/debug-4aceee.log';
echo "Log exists: " . (is_file($log) ? 'yes' : 'no') . PHP_EOL;
if (is_file($log)) {
    echo file_get_contents($log);
}
