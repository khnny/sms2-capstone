<?php
/**
 * SMS 2 - Online Payment Integration
 * Module: Payment Management
 */
require_once __DIR__ . '/../../../config/config.php';

$pageTitle    = 'Online Payment Integration';
$activeModule = 'payment';
$activePage   = 'online-payment-integration';
$breadcrumbs  = [
    ['label' => 'Payment Management', 'url' => BASE_URL . '/modules/payment/index.php'],
    ['label' => 'Online Payment Integration', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php require_once ROOT_PATH . '/includes/submodule-process.php'; ?>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
