<?php
/**
 * Restrict Faculty HR pages to HR / admin roles (not advisers, panels, etc.).
 */
declare(strict_types=1);

if (!function_exists('smsRequireFacultyHrAccess')) {
    function smsRequireFacultyHrAccess(): void
    {
        require_once ROOT_PATH . '/includes/authentication.php';
        requireAuth();
        if (!smsRoleAllowedForModule(['hr', 'superadmin', 'sms_admin'], 'faculty')) {
            http_response_code(403);
            exit('Access denied. This page is limited to HR and system administrators.');
        }
    }
}
