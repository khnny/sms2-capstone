<?php
/**
 * SMS 2 - Salary Grade & Payroll Setup
 * Module: Faculty Management
 */
require_once __DIR__ . '/../../../config/config.php';

$pageTitle    = 'Salary Grade & Payroll Setup';
$activeModule = 'faculty';
$activePage   = 'salary-grade-payroll-setup';
$breadcrumbs  = [
    ['label' => 'Faculty Management', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Salary Grade & Payroll Setup', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php require_once ROOT_PATH . '/includes/submodule-process.php'; ?>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
