<?php
/**
 * SMS 2 - CRAD - Overview
 */
require_once __DIR__ . '/../../includes/authentication.php';

$cradRoleKey = getCurrentUserRoleKey();
if ($cradRoleKey === 'department_head') {
    header('Location: ' . BASE_URL . '/modules/crad/pages/research-coordinator-management.php');
    exit;
}
$cradOverviewLabel = $cradRoleKey === 'research_coordinator' ? 'Research Coordinator' : 'CRAD';
$pageTitle    = $cradOverviewLabel;
$activeModule = 'crad';
$activePage   = '';
$breadcrumbs  = [
    ['label' => $cradOverviewLabel, 'url' => null],
];

require_once __DIR__ . '/../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../includes/layout-start.php';
require_once __DIR__ . '/../../includes/module-index-grid.php';
require_once __DIR__ . '/../../includes/layout-end.php';
