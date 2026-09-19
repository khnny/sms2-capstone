<?php
/**
 * SMS 2 - Faculty Directory
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../config/config.php';

$pageTitle    = 'Faculty Directory';
$activeModule = 'faculty';
$activePage   = 'faculty-directory';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Faculty Directory', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php require_once ROOT_PATH . '/includes/submodule-process.php'; ?>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
