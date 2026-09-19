<?php
/**
 * SMS 2 - Leave Application & Approval
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../config/config.php';

$pageTitle    = 'Leave Application & Approval';
$activeModule = 'faculty';
$activePage   = 'leave-application-approval';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Leave Application & Approval', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php require_once ROOT_PATH . '/includes/submodule-process.php'; ?>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
