<?php
/**
 * SMS 2 - Electives Manager
 * Module: Curriculum & Subject Management
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Electives Manager';
$activeModule = 'curriculum';
$activePage   = 'electives-manager';
$breadcrumbs  = [
    ['label' => 'Curriculum & Subject Management', 'url' => BASE_URL . '/modules/curriculum/index.php'],
    ['label' => 'Electives Manager', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php require_once ROOT_PATH . '/includes/submodule-process.php'; ?>
<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
