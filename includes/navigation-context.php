<?php
/**
 * SMS 2 - Navigation context helpers for sidebar and layout.
 */

require_once __DIR__ . '/grant-review-workflow-urls.php';

if (!function_exists('smsSidebarMode')) {
    /**
     * Which sidebar template to render for the logged-in role.
     */
    function smsSidebarMode(string $roleKey): string
    {
        $roleKey = function_exists('smsNormalizeRoleKey')
            ? smsNormalizeRoleKey($roleKey)
            : $roleKey;

        if ($roleKey === 'student') {
            return 'student';
        }

        if (in_array($roleKey, ['adviser', 'panel', 'grammarian', 'research_director', 'hr'], true)) {
            return 'faculty_workspace';
        }

        return 'admin_modules';
    }
}

if (!function_exists('smsResolveActiveModuleFromRequest')) {
    /**
     * Infer module key from the current request path.
     */
    function smsResolveActiveModuleFromRequest(): string
    {
        $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptPath === '') {
            return '';
        }

        if (preg_match('#/modules/student-portal(?:/|$)#', $scriptPath)) {
            return 'student_portal';
        }

        if (preg_match('#/modules/([a-z0-9_-]+)(?:/|$)#', $scriptPath, $matches)) {
            $folder = (string) ($matches[1] ?? '');
            if ($folder === 'student-portal') {
                return 'student_portal';
            }

            return str_replace('-', '_', $folder);
        }

        if (str_contains($scriptPath, '/account/module-security.php')) {
            $module = (string) ($_GET['module'] ?? '');
            if ($module === 'student-portal') {
                return 'student_portal';
            }

            return $module;
        }

        if (str_ends_with($scriptPath, '/dashboard/index.php')) {
            return 'dashboard';
        }

        return '';
    }
}

if (!function_exists('smsResolveActivePageFromRequest')) {
    /**
     * Infer page slug from the current script basename.
     */
    function smsResolveActivePageFromRequest(): string
    {
        $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($script === '' || $script === 'index.php') {
            return '';
        }

        return preg_replace('/\.php$/', '', $script) ?? '';
    }
}

if (!function_exists('smsEffectiveActiveModule')) {
    /**
     * Module key used for sidebar highlighting and expansion.
     */
    function smsEffectiveActiveModule(string $activeModule, string $roleKey): string
    {
        $activeModule = trim($activeModule);
        $roleKey = function_exists('smsNormalizeRoleKey')
            ? smsNormalizeRoleKey($roleKey)
            : $roleKey;

        if ($activeModule !== '' && $activeModule !== 'dashboard') {
            return $activeModule;
        }

        $fromRequest = smsResolveActiveModuleFromRequest();
        if ($fromRequest !== '' && $fromRequest !== 'dashboard') {
            return $fromRequest;
        }

        if (!function_exists('smsPrimaryModuleForRole')) {
            require_once __DIR__ . '/security-workflow.php';
        }

        $primary = function_exists('smsPrimaryModuleForRole')
            ? (string) smsPrimaryModuleForRole($roleKey)
            : '';

        if ($primary !== '' && $primary !== 'System') {
            return $primary;
        }

        return $activeModule;
    }
}

if (!function_exists('smsSidebarHighlightModule')) {
    /**
     * Module to expand/highlight in admin_modules sidebar mode.
     */
    function smsSidebarHighlightModule(string $activeModule, string $roleKey): string
    {
        if ($activeModule !== '' && $activeModule !== 'dashboard') {
            return $activeModule;
        }

        if (!function_exists('smsPrimaryModuleForRole')) {
            require_once __DIR__ . '/security-workflow.php';
        }

        $primary = function_exists('smsPrimaryModuleForRole')
            ? (string) smsPrimaryModuleForRole($roleKey)
            : '';

        return ($primary !== '' && $primary !== 'System') ? $primary : $activeModule;
    }
}

if (!function_exists('smsShowsMainDashboard')) {
    /**
     * Whether the global glass dashboard should appear in sidebar / post-login.
     */
    function smsShowsMainDashboard(string $roleKey): bool
    {
        $roleKey = function_exists('smsNormalizeRoleKey')
            ? smsNormalizeRoleKey($roleKey)
            : $roleKey;

        if ($roleKey === 'student') {
            return false;
        }

        if (in_array($roleKey, [
            'adviser',
            'panel',
            'grammarian',
            'research_director',
            'research_coordinator',
            'department_head',
            'research_grant',
            'review_committee',
            'department_chair',
            'research_office',
            'vpaa',
            'hr',
        ], true)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('smsRoleHomeUrl')) {
    /**
     * Default landing URL after login and sidebar Home link.
     */
    function smsRoleHomeUrl(string $roleKey): string
    {
        $roleKey = function_exists('smsNormalizeRoleKey')
            ? smsNormalizeRoleKey($roleKey)
            : $roleKey;

        $homes = [
            'student'              => BASE_URL . '/modules/student-portal/pages/dashboard.php',
            'research_coordinator' => BASE_URL . '/modules/crad/index.php',
            'department_head'      => BASE_URL . '/modules/crad/pages/research-coordinator-management.php',
            'department_chair'     => grantReviewWorkflowPageUrl('approval-workflows', 0, 'crad'),
            'research_office'      => grantReviewWorkflowPageUrl('approval-workflows', 0, 'crad'),
            'vpaa'                 => grantReviewWorkflowPageUrl('approval-workflows', 0, 'accreditation'),
            'finance'              => grantReviewWorkflowPageUrl('approval-workflows', 0, 'payment'),
            'hr'                   => grantReviewWorkflowPageUrl('approval-workflows', 0, 'faculty'),
            'crad_officer'         => BASE_URL . '/dashboard/index.php',
            'grammarian'           => BASE_URL . '/modules/faculty/pages/for-evaluation.php',
            'panel'                => BASE_URL . '/modules/faculty/pages/assigned-defenses.php',
            'research_director'    => BASE_URL . '/modules/faculty/pages/research-director.php?view=overview',
            'adviser'              => BASE_URL . '/modules/faculty/pages/assigned-research.php',
            'research_grant'       => BASE_URL . '/modules/crad/pages/grant-opportunities.php',
            'review_committee'     => BASE_URL . '/modules/crad/pages/reviewer-evaluation.php',
        ];

        if (isset($homes[$roleKey])) {
            return $homes[$roleKey];
        }

        if (!function_exists('smsPrimaryModuleForRole')) {
            require_once __DIR__ . '/security-workflow.php';
        }

        $primary = function_exists('smsPrimaryModuleForRole')
            ? (string) smsPrimaryModuleForRole($roleKey)
            : '';

        if ($primary === 'student_portal') {
            return BASE_URL . '/modules/student-portal/pages/dashboard.php';
        }

        return BASE_URL . '/dashboard/index.php';
    }
}

if (!function_exists('smsRoleHomeLabel')) {
    function smsRoleHomeLabel(string $roleKey): string
    {
        $roleKey = function_exists('smsNormalizeRoleKey')
            ? smsNormalizeRoleKey($roleKey)
            : $roleKey;

        $labels = [
            'student'              => 'Home',
            'research_coordinator' => 'Home',
            'department_head'      => 'Home',
            'grammarian'           => 'Home',
            'panel'                => 'Home',
            'research_director'    => 'Home',
            'adviser'              => 'Home',
            'hr'                   => 'Home',
            'crad_officer'         => 'Dashboard',
            'research_grant'       => 'Home',
            'review_committee'     => 'Home',
        ];

        return $labels[$roleKey] ?? (smsShowsMainDashboard($roleKey) ? 'Dashboard' : 'Home');
    }
}

if (!function_exists('smsRoleHomeIsActive')) {
    function smsRoleHomeIsActive(string $roleKey, string $scriptPath, string $activePage): bool
    {
        $roleKey = function_exists('smsNormalizeRoleKey')
            ? smsNormalizeRoleKey($roleKey)
            : $roleKey;

        if ($roleKey === 'department_head') {
            return false;
        }

        $homeUrl = smsRoleHomeUrl($roleKey);
        $homePath = (string) (parse_url($homeUrl, PHP_URL_PATH) ?? '');
        if ($homePath !== '' && str_ends_with($scriptPath, $homePath)) {
            return true;
        }

        $roleKey = function_exists('smsNormalizeRoleKey')
            ? smsNormalizeRoleKey($roleKey)
            : $roleKey;

        return match ($roleKey) {
            'grammarian'        => $activePage === 'for-evaluation',
            'panel'             => $activePage === 'assigned-defenses',
            'research_director' => str_contains($scriptPath, '/research-director.php')
                && (($activePage === '') || $activePage === 'overview'),
            'adviser'           => $activePage === 'assigned-research',
            'hr'                => in_array($activePage, ['approval-workflows', 'reviewer-evaluation'], true)
                || str_contains($scriptPath, '/modules/faculty/pages/approval-workflows.php')
                || str_contains($scriptPath, '/modules/faculty/pages/reviewer-evaluation.php')
                || str_contains($scriptPath, '/modules/crad/pages/approval-workflows.php')
                || str_contains($scriptPath, '/modules/crad/pages/reviewer-evaluation.php'),
            'department_chair', 'research_office' => in_array($activePage, ['approval-workflows', 'reviewer-evaluation'], true)
                || str_contains($scriptPath, '/modules/crad/pages/approval-workflows.php')
                || str_contains($scriptPath, '/modules/crad/pages/reviewer-evaluation.php'),
            'vpaa' => in_array($activePage, ['approval-workflows', 'reviewer-evaluation'], true)
                || str_contains($scriptPath, '/modules/accreditation/pages/approval-workflows.php')
                || str_contains($scriptPath, '/modules/accreditation/pages/reviewer-evaluation.php')
                || str_contains($scriptPath, '/modules/crad/pages/approval-workflows.php')
                || str_contains($scriptPath, '/modules/crad/pages/reviewer-evaluation.php'),
            'qa' => in_array($activePage, ['approval-workflows', 'reviewer-evaluation'], true)
                || str_contains($scriptPath, '/modules/accreditation/pages/approval-workflows.php')
                || str_contains($scriptPath, '/modules/accreditation/pages/reviewer-evaluation.php'),
            'finance' => in_array($activePage, ['approval-workflows', 'reviewer-evaluation'], true)
                || str_contains($scriptPath, '/modules/payment/pages/approval-workflows.php')
                || str_contains($scriptPath, '/modules/payment/pages/reviewer-evaluation.php'),
            'research_coordinator' => str_contains($scriptPath, '/modules/crad/index.php'),
            'research_grant' => str_contains($scriptPath, '/modules/crad/pages/grant-opportunities.php'),
            'review_committee' => str_contains($scriptPath, '/modules/crad/pages/reviewer-evaluation.php'),
            'student'           => str_contains($scriptPath, '/modules/student-portal/pages/dashboard.php'),
            default             => str_ends_with($scriptPath, '/dashboard/index.php'),
        };
    }
}
