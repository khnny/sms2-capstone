<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';

requireAuth();

$roleKey = getCurrentUserRoleKey();
if ($roleKey === 'department_head' || smsIsGrantedAdminRole($roleKey)) {
    header('Location: ' . BASE_URL . '/modules/crad/pages/research-coordinator-management.php?notice=adviser-merged#rcm-adviser');
    exit;
}

$rcPageSlug = 'assign-research-adviser';
require_once __DIR__ . '/../includes/research-coordinator-assignment-page.php';