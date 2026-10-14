<?php
/**
 * Shared navigation / module page icons
 */
if (!function_exists('smsNavPageIcon')) {
    function smsNavPageIcon(string $slug): string
    {
        static $map = [
            'user-accounts' => 'fa-user-cog',
            'role-permissions' => 'fa-shield-alt',
            'module-security' => 'fa-lock',
            'activity-logs' => 'fa-history',
            'system-settings' => 'fa-sliders-h',
            'security-settings' => 'fa-shield-alt',
            'announcements' => 'fa-bullhorn',
            'online-pre-registration' => 'fa-globe',
            'document-upload-portal' => 'fa-cloud-upload-alt',
            'enrollment-validation' => 'fa-check-double',
            'id-number-generation' => 'fa-hashtag',
            'grade-level-assignment' => 'fa-layer-group',
            'waiting-list-queue' => 'fa-stream',
            'cross-enrollment-checker' => 'fa-exchange-alt',
            'auto-section-assignment' => 'fa-magic',
            'parent-notification' => 'fa-bell',
            'enrollment-dashboard' => 'fa-chart-pie',
            'student-information-system' => 'fa-id-badge',
            'persona-file-database' => 'fa-database',
            'guardian-emergency-contact' => 'fa-phone-alt',
            'academic-history' => 'fa-history',
            'health-record-log' => 'fa-heartbeat',
            'rfid-qr-code-integration' => 'fa-qrcode',
            'student-id-generation' => 'fa-id-card',
            'document-requests' => 'fa-file-signature',
            'student-status-tracker' => 'fa-user-check',
            'digital-file-storage' => 'fa-folder',
            'transcript-management' => 'fa-scroll',
            'proposal-submission-tracking' => 'fa-clipboard-list',
            'register-proposal' => 'fa-file-signature',
            'research-group-number' => 'fa-users',
            'capstone-group-student-registry' => 'fa-clipboard-list',
            'adviser-panel-assignment' => 'fa-user-tie',
            'research-coordinator-management' => 'fa-user-cog',
            'research-grants-funding-assistance' => 'fa-hand-holding-usd',
            'research-defense-scheduling' => 'fa-calendar-check',
            'documentation-publication-management' => 'fa-book',
            'research-analytics-reporting' => 'fa-chart-line',
            'dashboard-analytics' => 'fa-chart-pie',
            'core-system-dashboard' => 'fa-chart-pie',
            'approval-workflows' => 'fa-tasks',
            'reviewer-evaluation' => 'fa-clipboard-check',
            'grant-opportunities' => 'fa-hand-holding-usd',
            'proposals-applications' => 'fa-file-alt',
            'publications-ip' => 'fa-book-open',
            'document-repository' => 'fa-archive',
            'revisions-requested' => 'fa-edit',
            'approved-research' => 'fa-clipboard-check',
            'research-group' => 'fa-users',
    'research-group-approvals' => 'fa-user-check',
    'assignment-confirmation' => 'fa-handshake',
    'retrieve-approved-research' => 'fa-download',
            'retrieve-defense-ready-research' => 'fa-download',
            'find-contact-adviser' => 'fa-user-tie',
            'adviser-availability' => 'fa-calendar-check',
            'assign-research-adviser' => 'fa-user-plus',
            'select-panel-members' => 'fa-user-friends',
            'check-panel-availability' => 'fa-calendar-check',
            'find-contact-panel' => 'fa-users',
            'panel-availability' => 'fa-calendar-alt',
            'assign-panel-members' => 'fa-user-friends',
            'send-notifications' => 'fa-paper-plane',
            'manage-assignments' => 'fa-tasks',
            'summary-dashboard' => 'fa-chart-pie',
            'for-evaluation' => 'fa-clipboard-check',
            'evaluation-scoring' => 'fa-star-half-alt',
            'evaluation-history' => 'fa-history',
            'performance-trends' => 'fa-chart-line',
            'export-center' => 'fa-file-export',
            'enrollment-analytics' => 'fa-user-graduate',
            'student-records-report' => 'fa-folder-open',
            'document-release-analytics' => 'fa-file-signature',
            'curriculum-analytics' => 'fa-book',
            'class-schedule-analytics' => 'fa-calendar-alt',
            'research-proposal-analytics' => 'fa-clipboard-list',
            'research-clearance' => 'fa-stamp',
            'college-payment' => 'fa-receipt',
            'approval-clearance-payment' => 'fa-file-invoice',
            'submit-chapters' => 'fa-file-upload',
            'my-submissions' => 'fa-folder-open',
            'submission-status' => 'fa-chart-line',
            'submission-history' => 'fa-history',
            'adviser-grants-report' => 'fa-user-tie',
            'publication-repository-report' => 'fa-book',
            'collections-analytics' => 'fa-peso-sign',
            'receivables-report' => 'fa-file-invoice-dollar',
            'faculty-load-report' => 'fa-chalkboard-teacher',
            'leave-evaluation-analytics' => 'fa-calendar-check',
            'lms-engagement-report' => 'fa-laptop',
            'module-completion-analytics' => 'fa-tasks',
            'club-activity-report' => 'fa-users',
            'volunteer-budget-analytics' => 'fa-hand-holding-heart',
            'accreditation-compliance-report' => 'fa-award',
            'audit-findings-analytics' => 'fa-clipboard-check',
        ];

        if (isset($map[$slug])) {
            return $map[$slug];
        }

        if (str_contains($slug, 'payment') || str_contains($slug, 'billing') || str_contains($slug, 'fee') || str_contains($slug, 'peso') || str_contains($slug, 'receivable') || str_contains($slug, 'penalty') || str_contains($slug, 'discount') || str_contains($slug, 'scholarship') || str_contains($slug, 'collection') || str_contains($slug, 'ledger')) {
            return 'fa-peso-sign';
        }
        if (str_contains($slug, 'report') || str_contains($slug, 'analytics') || str_contains($slug, 'dashboard')) {
            return 'fa-chart-bar';
        }
        if (str_contains($slug, 'schedule') || str_contains($slug, 'calendar') || str_contains($slug, 'timetable') || str_contains($slug, 'time-block')) {
            return 'fa-calendar';
        }
        if (str_contains($slug, 'upload') || str_contains($slug, 'document') || str_contains($slug, 'file') || str_contains($slug, 'repository')) {
            return 'fa-file-alt';
        }
        if (str_contains($slug, 'faculty') || str_contains($slug, 'teacher') || str_contains($slug, 'teaching') || str_contains($slug, 'evaluation')) {
            return 'fa-chalkboard-teacher';
        }
        if (str_contains($slug, 'quiz') || str_contains($slug, 'lms') || str_contains($slug, 'lesson') || str_contains($slug, 'classroom') || str_contains($slug, 'assignment') || str_contains($slug, 'module')) {
            return 'fa-laptop';
        }
        if (str_contains($slug, 'research') || str_contains($slug, 'grant') || str_contains($slug, 'proposal') || str_contains($slug, 'publication')) {
            return 'fa-flask';
        }
        if (str_contains($slug, 'club') || str_contains($slug, 'event') || str_contains($slug, 'volunteer') || str_contains($slug, 'co-curricular') || str_contains($slug, 'cocurricular')) {
            return 'fa-users';
        }
        if (str_contains($slug, 'curriculum') || str_contains($slug, 'subject') || str_contains($slug, 'elective') || str_contains($slug, 'prerequisite') || str_contains($slug, 'strand')) {
            return 'fa-book';
        }
        if (str_contains($slug, 'accreditation') || str_contains($slug, 'compliance') || str_contains($slug, 'audit') || str_contains($slug, 'quality')) {
            return 'fa-award';
        }
        if (str_contains($slug, 'leave') || str_contains($slug, 'attendance') || str_contains($slug, 'clearance') || str_contains($slug, 'payroll') || str_contains($slug, 'salary')) {
            return 'fa-user-clock';
        }
        if (str_contains($slug, 'room') || str_contains($slug, 'conflict') || str_contains($slug, 'section') || str_contains($slug, 'exam') || str_contains($slug, 'substitute') || str_contains($slug, 'cloning')) {
            return 'fa-calendar-check';
        }
        if (str_contains($slug, 'user') || str_contains($slug, 'account') || str_contains($slug, 'role') || str_contains($slug, 'permission') || str_contains($slug, 'activity-log') || str_contains($slug, 'system-settings')) {
            return 'fa-users-cog';
        }

        return 'fa-circle';
    }
}
