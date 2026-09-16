<?php
declare(strict_types=1);

putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3306');
putenv('DB_DATABASE=sms2_db');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=');
putenv('DB_CONNECTION=mysql');
putenv('CRAD_DB_HOST=mariadb-edee97zl.internal');
putenv('CRAD_DB_NAME=hf_db_edee97zl');
putenv('CRAD_DB_USER=hf_czivmdlprb');
putenv('CRAD_DB_PASS=stale');
// No CRAD_FORCE_SEPARATE → must ignore stale CRAD_* and reuse main DB.

foreach (['DB_HOST','DB_DATABASE','DB_USERNAME','DB_PASSWORD','CRAD_DB_HOST','CRAD_DB_NAME','CRAD_DB_USER','CRAD_DB_PASS'] as $k) {
    $_SERVER[$k] = getenv($k);
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/modules/crad/config/config.php';

echo 'CRAD_HOST=' . CRAD_DB_HOST . PHP_EOL;
echo 'CRAD_NAME=' . CRAD_DB_NAME . PHP_EOL;
echo 'DB_HOST=' . DB_HOST . PHP_EOL;
echo 'DB_NAME=' . DB_NAME . PHP_EOL;
echo 'same=' . ((CRAD_DB_HOST === DB_HOST && CRAD_DB_NAME === DB_NAME) ? 'yes' : 'no') . PHP_EOL;

$pdo = getCradDatabaseConnection();
echo 'connected=' . (isset($pdo) ? 'yes' : 'no') . PHP_EOL;
echo file_get_contents(dirname(__DIR__) . '/debug-4aceee.log');
