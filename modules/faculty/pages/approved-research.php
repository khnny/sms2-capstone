<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
// Authenticate first so JSON endpoints are never exposed without a session.
requireAuth();

// Include the shared faculty handler BEFORE any output: for ?faculty_ajax=...
// requests it returns clean JSON (no HTML prefix) and exits early.
require_once ROOT_PATH . '/modules/faculty/includes/faculty-account-page.php';

$pageTitle  = 'Research Status';
$activeModule = 'faculty';
$activePage = 'approved-research';
require_once ROOT_PATH . '/includes/layout-start.php';
renderFacultyAccountPage($pageTitle, $activePage, 'approved-research');
require_once ROOT_PATH . '/includes/layout-end.php';
