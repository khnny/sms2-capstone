<?php
/**
 * SMS 2 - Academic Strand Assignment
 * Module: Curriculum & Subject Management
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Academic Strand Assignment';
$activeModule = 'curriculum';
$activePage   = 'academic-strand-assignment';
$breadcrumbs  = [
    ['label' => 'Curriculum & Subject Management', 'url' => BASE_URL . '/modules/curriculum/index.php'],
    ['label' => 'Academic Strand Assignment', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php require_once ROOT_PATH . '/includes/submodule-process.php'; ?>
<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
