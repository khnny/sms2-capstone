<?php
/**
 * SMS 2 - Logout
 */
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';

logout();

require_once ROOT_PATH . '/includes/module-controls.php';
if (function_exists('smsIsSystemInMaintenance') && smsIsSystemInMaintenance()) {
    header('Location: ' . BASE_URL . '/account/maintenance.php');
    exit;
}

$timeout = !empty($_GET['timeout']) ? 1 : 0;
header('Location: ' . BASE_URL . '/login/login.php' . ($timeout ? '?timeout=1' : ''));
exit;
