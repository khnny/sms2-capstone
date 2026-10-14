<?php
/**
 * SMS 2 - Subject Offering History
 * Module: Curriculum & Subject Management
 */
require_once __DIR__ . '/../../../../config/config.php';

$pageTitle    = 'Subject Offering History';
$activeModule = 'curriculum';
$activePage   = 'subject-offering-history';
$breadcrumbs  = [
    ['label' => 'Curriculum & Subject Management', 'url' => BASE_URL . '/modules/curriculum/index.php'],
    ['label' => 'Subject Offering History', 'url' => null],
];

require_once __DIR__ . '/../../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php require_once ROOT_PATH . '/includes/submodule-process.php'; ?>
<?php require_once __DIR__ . '/../../../../includes/layout-end.php'; ?>
