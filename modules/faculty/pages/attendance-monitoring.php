<?php
/**
 * SMS 2 - Attendance Monitoring
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../config/config.php';

$pageTitle    = 'Attendance Monitoring';
$activeModule = 'faculty';
$activePage   = 'attendance-monitoring';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Attendance Monitoring', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php require_once ROOT_PATH . '/includes/submodule-process.php'; ?>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
