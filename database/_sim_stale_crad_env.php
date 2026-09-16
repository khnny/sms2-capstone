<?php
/**
 * Local post-fix simulation: stale CRAD_* + reachable local main DB.
 */
declare(strict_types=1);

putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3306');
putenv('DB_DATABASE=sms2_db');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=');
putenv('DB_CONNECTION=mysql');
putenv('DB_CHARSET=utf8mb4');

putenv('CRAD_DB_HOST=mariadb-edee97zl.internal');
putenv('CRAD_DB_PORT=3306');
putenv('CRAD_DB_NAME=hf_db_edee97zl');
putenv('CRAD_DB_USER=hf_czivmdlprb');
putenv('CRAD_DB_PASS=local-sim-not-real');

foreach (['DB_HOST','DB_DATABASE','DB_USERNAME','DB_PASSWORD','CRAD_DB_HOST','CRAD_DB_NAME','CRAD_DB_USER','CRAD_DB_PASS'] as $k) {
    $_SERVER[$k] = getenv($k);
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/modules/crad/config/config.php';

echo "CRAD=" . CRAD_DB_HOST . '/' . CRAD_DB_NAME . PHP_EOL;
echo "SMS2=" . DB_HOST . '/' . DB_NAME . PHP_EOL;

try {
    $pdo = getCradDatabaseConnection();
    echo "CONNECTED ok class=" . get_class($pdo) . PHP_EOL;
    $has = (bool) $pdo->query("SHOW TABLES LIKE 'sms_users'")->fetchColumn();
    echo "sms_users=" . ($has ? 'yes' : 'no') . PHP_EOL;
} catch (Throwable $e) {
    echo "THREW: " . $e->getMessage() . PHP_EOL;
}

$log = dirname(__DIR__) . '/debug-4aceee.log';
if (is_file($log)) {
    echo "---- log ----\n";
    echo file_get_contents($log);
}
