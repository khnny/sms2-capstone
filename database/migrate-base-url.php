<?php
/**
 * One-time migration: rewrite stored notification URLs from /SMS2_system to /sms2_system.
 *
 * CLI:  php database/migrate-base-url.php
 * Web:  /sms2_system/database/migrate-base-url.php?run=1  (super_admin session required)
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/**
 * @return array<int, string>
 */
function sms2MigrateStoredBaseUrls(?callable $sink = null): array
{
    require_once __DIR__ . '/../modules/crad/config/config.php';

    $from = '/SMS2_system';
    $to = '/sms2_system';
    $lines = [];
    $log = static function (string $message) use (&$lines, $sink): void {
        $lines[] = $message;
        if ($sink) {
            $sink($message);
        }
    };

    $pdo = getCradDatabaseConnection();
    $tables = [
        'grant_proposal_notifications' => 'url',
        'chapter_evaluation_notifications' => 'url',
        'research_progress_notifications' => 'action_url',
        'panel_assignment_notifications' => 'url',
    ];

    $log('Canonical BASE_URL: ' . (BASE_URL !== '' ? BASE_URL : '(root)'));
    $log('Rewriting stored URLs: ' . $from . ' -> ' . $to);

    $totalUpdated = 0;
    foreach ($tables as $table => $urlColumn) {
        $exists = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table))->fetchColumn();
        if (!$exists) {
            $log('Skipped ' . $table . ' (table not found).');
            continue;
        }

        $columnExists = $pdo->query(
            'SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '` LIKE ' . $pdo->quote($urlColumn)
        )->fetchColumn();
        if (!$columnExists) {
            $log('Skipped ' . $table . ' (column ' . $urlColumn . ' not found).');
            continue;
        }

        $quotedTable = '`' . str_replace('`', '``', $table) . '`';
        $quotedColumn = '`' . str_replace('`', '``', $urlColumn) . '`';
        $stmt = $pdo->prepare(
            'UPDATE ' . $quotedTable . '
             SET ' . $quotedColumn . ' = REPLACE(' . $quotedColumn . ', ?, ?)
             WHERE ' . $quotedColumn . ' LIKE ?'
        );
        $stmt->execute([$from, $to, $from . '%']);
        $count = $stmt->rowCount();
        $totalUpdated += $count;
        $log('Updated ' . $count . ' row(s) in ' . $table . '.' . $urlColumn . '.');
    }

    $log('Done. Total rows updated: ' . $totalUpdated . '.');

    return $lines;
}

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    foreach (sms2MigrateStoredBaseUrls() as $line) {
        echo $line . PHP_EOL;
    }
    exit(0);
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) !== __FILE__) {
    return;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/authentication.php';

if (!isLoggedIn() || ($_SESSION['role_key'] ?? '') !== 'super_admin') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden. Sign in as super_admin or run: php database/migrate-base-url.php';
    exit;
}

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_GET['run'])) {
    echo "Open with ?run=1 to rewrite notification URLs to " . BASE_URL . "\n";
    exit;
}

foreach (sms2MigrateStoredBaseUrls() as $line) {
    echo $line . "\n";
}
