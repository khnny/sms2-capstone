<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';

requireAuth();
// Adviser Research Clearance removed — redirect away.
header('Location: ' . BASE_URL . '/modules/faculty/pages/assigned-research.php');
exit;
