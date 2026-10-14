<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden. Run from CLI only.\n");
}

require_once __DIR__ . '/../includes/panelist-workflow.php';

cradEnsurePanelistWorkflowSchema(getCradDatabaseConnection());
echo "Panelist application tables are ready.\n";
