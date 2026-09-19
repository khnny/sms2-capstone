<?php
/**
 * Research Coordinator owner for the shared Panel Assignment workflow.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/modules/faculty/includes/research-director-panel-assignment.php';

requireAuth();

$roleKey = getCurrentUserRoleKey();
if (!smsCanManageCoordinatorAssignments($roleKey)) {
    header('Location: ' . BASE_URL . '/modules/crad/index.php');
    exit;
}
if (!smsIsGrantedAdminRole($roleKey)) {
    requireModuleAccess('crad');
}

$rcPanelPageSlug = $rcPanelPageSlug ?? 'retrieve-defense-ready-research';
$rcPanelPages = [
    'retrieve-defense-ready-research' => 'Retrieve Defense-Ready Research',
    'select-panel-members' => 'Select Panel Members',
    'check-panel-availability' => 'Check Panel Availability',
    'assign-panel-members' => 'Assign Panel Members',
];

if (!isset($rcPanelPages[$rcPanelPageSlug])) {
    $rcPanelPageSlug = 'retrieve-defense-ready-research';
}

$pageTitle = $rcPanelPages[$rcPanelPageSlug];
$activeModule = 'crad';
$activePage = $rcPanelPageSlug;
$rcPanelNavLabel = getCurrentUserRoleKey() === 'department_head' ? 'Research Management' : 'Research Coordinator';
$breadcrumbs = [
    ['label' => $rcPanelNavLabel, 'url' => getCurrentUserRoleKey() === 'department_head'
        ? BASE_URL . '/modules/crad/pages/research-coordinator-management.php'
        : BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Panel Assignment', 'url' => rdPanelPageUrl('retrieve-defense-ready-research')],
    ['label' => $pageTitle, 'url' => null],
];

if (isset($_GET['ajax'])) {
    renderResearchCoordinatorPanelAssignment($rcPanelPageSlug);
    exit;
}

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
renderResearchCoordinatorPanelAssignment($rcPanelPageSlug);
require_once ROOT_PATH . '/includes/layout-end.php';
