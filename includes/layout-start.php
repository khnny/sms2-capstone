<?php
/**
 * SMS 2 - Authenticated Layout Start
 * Include after setting $pageTitle, $activeModule, optional $activePage, $breadcrumbs
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/authentication.php';
requireAuth();

// Force password change before using the app
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (!empty($_SESSION['must_change_password']) && $script !== 'change-password.php') {
    header('Location: ' . BASE_URL . '/login/change-password.php');
    exit;
}

$scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$isStudentDashboard = str_ends_with($scriptPath, '/modules/student-portal/pages/dashboard.php');

if (!empty($_GET['assignment_notification'])) {
    header('Location: ' . BASE_URL . '/notifications/view.php?id=' . urlencode((string) $_GET['assignment_notification']));
    exit;
}
if ($isStudentDashboard && !empty($_GET['research_group'])) {
    header('Location: ' . BASE_URL . '/notifications/view.php?type=research_group');
    exit;
}
if ($isStudentDashboard && !empty($_GET['returned_proposal'])) {
    header('Location: ' . BASE_URL . '/notifications/view.php?type=returned_proposal&ref=' . urlencode((string) $_GET['returned_proposal']));
    exit;
}

$pageTitle    = $pageTitle ?? APP_NAME;
$activeModule = $activeModule ?? '';
$activePage   = $activePage ?? '';
$breadcrumbs  = $breadcrumbs ?? [];
$bodyClass    = 'sms-app';

require_once ROOT_PATH . '/includes/navigation-context.php';
$layoutRoleKey = getCurrentUserRoleKey();
$pageAccessModule = (string) $activeModule;
$activeModule = smsEffectiveActiveModule((string) $activeModule, $layoutRoleKey);
if ($activePage === '') {
    $activePage = smsResolveActivePageFromRequest();
}
if ($activePage === '' && str_ends_with($scriptPath, '/dashboard/index.php') && smsSidebarMode($layoutRoleKey) === 'student') {
    $activePage = 'dashboard';
}

$onMainDashboard = str_ends_with($scriptPath, '/dashboard/index.php');
$roleHomeUrl = smsRoleHomeUrl($layoutRoleKey);
$roleHomePath = (string) (parse_url($roleHomeUrl, PHP_URL_PATH) ?? '');
$roleHomeQuery = [];
parse_str((string) (parse_url($roleHomeUrl, PHP_URL_QUERY) ?? ''), $roleHomeQuery);
$isRoleDashboardHome = $layoutRoleKey !== 'student'
    && !$onMainDashboard
    && $roleHomePath !== ''
    && str_ends_with($scriptPath, $roleHomePath)
    && !isset($_GET['id']);
foreach ($_GET as $queryKey => $_queryValue) {
    if (!array_key_exists((string) $queryKey, $roleHomeQuery)) {
        $isRoleDashboardHome = false;
        break;
    }
}
foreach ($roleHomeQuery as $queryKey => $queryValue) {
    $currentQueryValue = $_GET[$queryKey] ?? null;
    if (!is_scalar($currentQueryValue) || (string) $currentQueryValue !== (string) $queryValue) {
        $isRoleDashboardHome = false;
        break;
    }
}
if ($isRoleDashboardHome) {
    $bodyClass .= ' role-dashboard-home';
}

if ($onMainDashboard && !smsShowsMainDashboard($layoutRoleKey)) {
    $homeUrl = smsRoleHomeUrl($layoutRoleKey);
    $homePath = (string) (parse_url($homeUrl, PHP_URL_PATH) ?? '');
    if ($homePath === '' || !str_ends_with($scriptPath, $homePath)) {
        header('Location: ' . $homeUrl);
        exit;
    }
}

requireModuleAccess($pageAccessModule !== '' ? $pageAccessModule : $activeModule);

require_once ROOT_PATH . '/includes/header.php';
?>
<div class="sms-wrapper">
    <?php require_once ROOT_PATH . '/includes/navbar.php'; ?>
    <?php require_once ROOT_PATH . '/includes/sidebar.php'; ?>
    <div class="sms-content">
        <main class="sms-main">
