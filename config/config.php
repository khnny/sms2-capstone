<?php
/**
 * SMS 2 - Global Configuration
 * Bestlink College of the Philippines
 */

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Bestlink College of the Philippines');
}
if (!defined('APP_SHORT_NAME')) {
    define('APP_SHORT_NAME', 'BCP');
}
if (!defined('INSTITUTION')) {
    define('INSTITUTION', 'Bestlink College of the Philippines');
}
if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.0');
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

// Optional machine-specific overrides. Copy config/local.example.php to
// config/local.php on another computer if its MySQL settings are different.
$sms2LocalConfig = __DIR__ . '/local.php';
if (is_readable($sms2LocalConfig)) {
    require_once $sms2LocalConfig;
}

if (!function_exists('sms2_env')) {
    function sms2_env(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('sms2_env_first')) {
    function sms2_env_first(array $keys, ?string $default = null): ?string
    {
        foreach ($keys as $key) {
            $value = sms2_env((string) $key);
            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }
}

if (!function_exists('sms2_detect_base_url')) {
    function sms2_detect_base_url(): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $markers = [
            '/account/',
            '/api/',
            '/dashboard/',
            '/database/',
            '/login/',
            '/modules/',
            '/notifications/',
            '/setup/',
            '/welcome/',
        ];

        // Use the leftmost marker so nested module APIs
        // (e.g. /modules/crad/api/...) do not treat /api/ as the app root.
        $bestPos = null;
        foreach ($markers as $marker) {
            $pos = strpos($scriptName, $marker);
            if ($pos !== false && ($bestPos === null || $pos < $bestPos)) {
                $bestPos = $pos;
            }
        }
        if ($bestPos !== null) {
            return rtrim(substr($scriptName, 0, $bestPos), '/');
        }

        $dir = str_replace('\\', '/', dirname($scriptName));
        return $dir === '/' || $dir === '.' ? '' : rtrim($dir, '/');
    }
}

if (!function_exists('sms2_normalize_base_url')) {
    /**
     * Canonical lowercase path for the local XAMPP install folder.
     */
    function sms2_normalize_base_url(string $url): string
    {
        $url = rtrim($url, '/');
        if ($url === '' || $url === '/') {
            return '';
        }

        $segments = explode('/', trim($url, '/'));
        $lastIndex = count($segments) - 1;
        if ($lastIndex >= 0 && strcasecmp((string) $segments[$lastIndex], 'sms2_system') === 0) {
            $segments[$lastIndex] = 'sms2_system';
            return '/' . implode('/', $segments);
        }

        return $url;
    }
}

// Auto-detect the folder name so the app still works when copied to another
// XAMPP htdocs directory. Set SMS2_BASE_URL or APP_BASE_URL to override.
if (!defined('BASE_URL')) {
    $baseUrlCandidate = defined('SMS2_LOCAL_BASE_URL')
        ? (string) SMS2_LOCAL_BASE_URL
        : (string) sms2_env('SMS2_BASE_URL', sms2_env('APP_BASE_URL', sms2_detect_base_url()));
    define('BASE_URL', sms2_normalize_base_url(rtrim($baseUrlCandidate, '/')));
}

if (!function_exists('smsBrandLogoUrl')) {
    function smsBrandLogoUrl(): string
    {
        static $url = null;
        if ($url !== null) {
            return $url;
        }

        $png = ROOT_PATH . '/images/bcp-logo-source.png';
        $url = is_readable($png)
            ? BASE_URL . '/images/bcp-logo-source.png'
            : BASE_URL . '/images/sms-brand.svg';

        return $url;
    }
}

if (!function_exists('smsWelcomeHeroImageUrl')) {
    function smsWelcomeHeroImageUrl(): string
    {
        static $url = null;
        if ($url !== null) {
            return $url;
        }

        $candidates = [
            '/images/school1.png',
            '/images/bcp-campus.jpg',
            '/images/bcp-campus.png',
        ];

        foreach ($candidates as $path) {
            if (is_readable(ROOT_PATH . $path)) {
                $url = BASE_URL . $path;
                return $url;
            }
        }

        $url = BASE_URL . '/images/school1.png';
        return $url;
    }
}

if (!function_exists('smsLoginHeroImageUrl')) {
    function smsLoginHeroImageUrl(): string
    {
        static $url = null;
        if ($url !== null) {
            return $url;
        }

        $candidates = [
            '/images/school2.png',
            '/images/bcp-campus.jpg',
            '/images/bcp-campus.png',
        ];

        foreach ($candidates as $path) {
            if (is_readable(ROOT_PATH . $path)) {
                $url = BASE_URL . $path;
                return $url;
            }
        }

        $url = BASE_URL . '/images/school2.png';
        return $url;
    }
}

date_default_timezone_set('Asia/Manila');

/**
 * Module registry — drives sidebar navigation and subsystem index cards.
 * slug: URL-safe filename (without .php)
 */
$MODULES = [
    'enrollment' => [
        'label' => 'Enrollment Management',
        'icon'  => 'fa-user-graduate',
        'groups' => [
            'Registration' => [
                'online-pre-registration',
                'document-upload-portal',
                'enrollment-validation',
            ],
            'Student Assignment' => [
                'id-number-generation',
                'grade-level-assignment',
                'auto-section-assignment',
            ],
            'Queue & Waitlist' => [
                'waiting-list-queue',
                'cross-enrollment-checker',
            ],
            'Communication' => [
                'parent-notification',
            ],
            'Reports' => [
                'enrollment-dashboard',
            ],
        ],
        'pages' => [
            ['slug' => 'online-pre-registration', 'title' => 'Online Pre-registration'],
            ['slug' => 'document-upload-portal', 'title' => 'Document Upload Portal'],
            ['slug' => 'enrollment-validation', 'title' => 'Enrollment Validation'],
            ['slug' => 'id-number-generation', 'title' => 'ID Number Generation'],
            ['slug' => 'grade-level-assignment', 'title' => 'Grade Level Assignment'],
            ['slug' => 'waiting-list-queue', 'title' => 'Waiting List Queue'],
            ['slug' => 'cross-enrollment-checker', 'title' => 'Cross-enrollment Checker'],
            ['slug' => 'auto-section-assignment', 'title' => 'Auto Section Assignment'],
            ['slug' => 'parent-notification', 'title' => 'Parent Notification'],
            ['slug' => 'enrollment-dashboard', 'title' => 'Enrollment Dashboard'],
        ],
    ],
    'registrar' => [
        'label' => 'Registrar',
        'icon'  => 'fa-folder-open',
        'groups' => [
            'Student Records' => [
                'student-information-system',
                'persona-file-database',
                'guardian-emergency-contact',
                'academic-history',
            ],
            'Health & Compliance' => [
                'health-record-log',
                'rfid-qr-code-integration',
            ],
            'Documents & ID' => [
                'student-id-generation',
                'document-requests',
                'transcript-management',
            ],
            'Digital Files' => [
                'student-status-tracker',
                'digital-file-storage',
            ],
        ],
        'pages' => [
            ['slug' => 'student-information-system', 'title' => 'Student Information System'],
            ['slug' => 'persona-file-database', 'title' => 'Persona File Database'],
            ['slug' => 'guardian-emergency-contact', 'title' => 'Guardian & Emergency Contact'],
            ['slug' => 'academic-history', 'title' => 'Academic History'],
            ['slug' => 'health-record-log', 'title' => 'Health Record Log'],
            ['slug' => 'rfid-qr-code-integration', 'title' => 'RFID/QR Code Integration'],
            ['slug' => 'student-id-generation', 'title' => 'Student ID Generation'],
            ['slug' => 'document-requests', 'title' => 'Document Requests'],
            ['slug' => 'student-status-tracker', 'title' => 'Student Status Tracker'],
            ['slug' => 'digital-file-storage', 'title' => 'Digital File Storage'],
            ['slug' => 'transcript-management', 'title' => 'Transcript Management'],
        ],
    ],
    'curriculum' => [
        'label' => 'Curriculum & Subject Management',
        'icon'  => 'fa-book',
        'groups' => [
            'Curriculum Setup' => [
                'curriculum-builder',
                'subject-mapping',
                'pre-requisite-configuration',
                'electives-manager',
                'academic-strand-assignment',
            ],
            'Subject Management' => [
                'subject-offering-history',
                'subject-equivalency-tool',
                'grade-weighting-setup',
            ],
            'Validation & Export' => [
                'ched-deped-validator',
                'curriculum-export-tool',
            ],
        ],
        'pages' => [
            ['slug' => 'curriculum-builder', 'title' => 'Curriculum Builder'],
            ['slug' => 'subject-mapping', 'title' => 'Subject Mapping'],
            ['slug' => 'pre-requisite-configuration', 'title' => 'Pre-requisite Configuration'],
            ['slug' => 'electives-manager', 'title' => 'Electives Manager'],
            ['slug' => 'academic-strand-assignment', 'title' => 'Academic Strand Assignment'],
            ['slug' => 'subject-offering-history', 'title' => 'Subject Offering History'],
            ['slug' => 'subject-equivalency-tool', 'title' => 'Subject Equivalency Tool'],
            ['slug' => 'grade-weighting-setup', 'title' => 'Grade Weighting Setup'],
            ['slug' => 'ched-deped-validator', 'title' => 'CHED/DepEd Validator'],
            ['slug' => 'curriculum-export-tool', 'title' => 'Curriculum Export Tool'],
        ],
    ],
    'accreditation' => [
        'label' => 'Accreditation Management',
        'icon'  => 'fa-award',
        'groups' => [
            'Documents & Reports' => [
                'accreditation-document-repository',
                'self-assessment-report-builder',
            ],
            'Compliance & Tracking' => [
                'compliance-matrix-criteria-tracking',
                'program-accreditation-tracker',
                'faculty-staff-qualification-tracking',
                'physical-facilities-monitoring',
            ],
            'Quality Audits' => [
                'internal-quality-audit-scheduler',
                'continuous-improvement-action-planning',
            ],
            'Accreditation Visits' => [
                'accreditation-visit-management',
            ],
            'Reports' => [
                'accreditation-reports-analytics',
            ],
            'Review & Workflow' => [
                'reviewer-evaluation',
                'approval-workflows',
            ],
        ],
        'pages' => [
            ['slug' => 'accreditation-document-repository', 'title' => 'Accreditation Document Repository'],
            ['slug' => 'self-assessment-report-builder', 'title' => 'Self Assessment Report Builder'],
            ['slug' => 'compliance-matrix-criteria-tracking', 'title' => 'Compliance Matrix & Criteria Tracking'],
            ['slug' => 'internal-quality-audit-scheduler', 'title' => 'Internal Quality Audit Scheduler'],
            ['slug' => 'accreditation-visit-management', 'title' => 'Accreditation Visit Management'],
            ['slug' => 'program-accreditation-tracker', 'title' => 'Program Accreditation Tracker'],
            ['slug' => 'faculty-staff-qualification-tracking', 'title' => 'Faculty & Staff Qualification Tracking'],
            ['slug' => 'physical-facilities-monitoring', 'title' => 'Physical Facilities Monitoring'],
            ['slug' => 'continuous-improvement-action-planning', 'title' => 'Continuous Improvement Action Planning'],
            ['slug' => 'accreditation-reports-analytics', 'title' => 'Accreditation Reports & Analytics'],
            ['slug' => 'reviewer-evaluation', 'title' => 'Reviewer Evaluation'],
            ['slug' => 'approval-workflows', 'title' => 'Approval Workflows'],
        ],
    ],
    'payment' => [
        'label' => 'Payment Management',
        'icon'  => 'fa-credit-card',
        'groups' => [
            'Billing & Collections' => [
                'student-billing-invoicing',
                'payment-collection-portal',
                'online-payment-integration',
            ],
            'Fees & Discounts' => [
                'fee-setup-configuration',
                'discount-scholarship-application',
                'penalty-due-date-management',
            ],
            'Ledger & Receivables' => [
                'payment-history-ledger-system',
                'accounts-receivable-management',
            ],
            'Reports & Audit' => [
                'collection-reporting-analytics',
                'audit-access-control',
            ],
            'Review & Workflow' => [
                'reviewer-evaluation',
                'approval-workflows',
            ],
        ],
        'pages' => [
            ['slug' => 'student-billing-invoicing', 'title' => 'Student Billing & Invoicing'],
            ['slug' => 'payment-collection-portal', 'title' => 'Payment Collection Portal'],
            ['slug' => 'online-payment-integration', 'title' => 'Online Payment Integration'],
            ['slug' => 'fee-setup-configuration', 'title' => 'Fee Setup & Configuration'],
            ['slug' => 'discount-scholarship-application', 'title' => 'Discount & Scholarship Application'],
            ['slug' => 'payment-history-ledger-system', 'title' => 'Payment History & Ledger System'],
            ['slug' => 'collection-reporting-analytics', 'title' => 'Collection Reporting & Analytics'],
            ['slug' => 'accounts-receivable-management', 'title' => 'Accounts Receivable Management'],
            ['slug' => 'penalty-due-date-management', 'title' => 'Penalty & Due Date Management'],
            ['slug' => 'audit-access-control', 'title' => 'Audit & Access Control'],
            ['slug' => 'reviewer-evaluation', 'title' => 'Reviewer Evaluation'],
            ['slug' => 'approval-workflows', 'title' => 'Approval Workflows'],
        ],
    ],
    'faculty' => [
        'label' => 'Faculty Management',
        'icon'  => 'fa-chalkboard-teacher',
        'groups' => [
            'Faculty Profiles' => [
                'faculty-profile',
                'faculty-directory',
                'teaching-history',
            ],
            'Schedule & Load' => [
                'subject-load-tracker',
                'schedule-assignment',
                'attendance-monitoring',
            ],
            'Leave & Payroll' => [
                'leave-application-approval',
                'salary-grade-payroll-setup',
            ],
            'Evaluation & Clearance' => [
                'evaluation-summary',
                'clearance-system',
            ],
        ],
        'pages' => [
            ['slug' => 'faculty-profile', 'title' => 'Faculty Profile'],
            ['slug' => 'subject-load-tracker', 'title' => 'Subject Load Tracker'],
            ['slug' => 'schedule-assignment', 'title' => 'Schedule Assignment'],
            ['slug' => 'attendance-monitoring', 'title' => 'Attendance Monitoring'],
            ['slug' => 'leave-application-approval', 'title' => 'Leave Application & Approval'],
            ['slug' => 'salary-grade-payroll-setup', 'title' => 'Salary Grade & Payroll Setup'],
            ['slug' => 'teaching-history', 'title' => 'Teaching History'],
            ['slug' => 'clearance-system', 'title' => 'Clearance System'],
            ['slug' => 'evaluation-summary', 'title' => 'Evaluation Summary'],
            ['slug' => 'faculty-directory', 'title' => 'Faculty Directory'],
        ],
    ],
    'scheduling' => [
        'label' => 'Class Schedule',
        'icon'  => 'fa-calendar-alt',
        'groups' => [
            'Schedule Setup' => [
                'section-assignment-tool',
                'teacher-schedule-mapping',
                'special-class-scheduler',
                'exam-timetable-generator',
            ],
            'Conflict & Rooms' => [
                'conflict-checker',
                'room-availability-checker',
                'substitute-assignment-tracker',
            ],
            'Tools & Integration' => [
                'schedule-cloning-tool',
                'time-block-generator',
                'calendar-integration',
            ],
        ],
        'pages' => [
            ['slug' => 'section-assignment-tool', 'title' => 'Section Assignment Tool'],
            ['slug' => 'teacher-schedule-mapping', 'title' => 'Teacher Schedule Mapping'],
            ['slug' => 'conflict-checker', 'title' => 'Conflict Checker'],
            ['slug' => 'exam-timetable-generator', 'title' => 'Exam Timetable Generator'],
            ['slug' => 'substitute-assignment-tracker', 'title' => 'Substitute Assignment Tracker'],
            ['slug' => 'special-class-scheduler', 'title' => 'Special Class Scheduler'],
            ['slug' => 'room-availability-checker', 'title' => 'Room Availability Checker'],
            ['slug' => 'schedule-cloning-tool', 'title' => 'Schedule Cloning Tool'],
            ['slug' => 'time-block-generator', 'title' => 'Time Block Generator'],
            ['slug' => 'calendar-integration', 'title' => 'Calendar Integration'],
        ],
    ],
    'cocurricular' => [
        'label' => 'Co-Curricular',
        'icon'  => 'fa-users',
        'groups' => [
            'Clubs & Membership' => [
                'club-registration-portal',
                'student-club-membership',
                'club-officer-elections',
                'club-directory',
            ],
            'Events & Activities' => [
                'event-activity-logs',
                'attendance-tracker',
                'club-achievement-records',
                'inter-school-communication',
            ],
            'Budget & Volunteering' => [
                'budget-requests',
                'volunteer-hour-tracking',
            ],
        ],
        'pages' => [
            ['slug' => 'club-registration-portal', 'title' => 'Club Registration Portal'],
            ['slug' => 'student-club-membership', 'title' => 'Student Club Membership'],
            ['slug' => 'club-officer-elections', 'title' => 'Club Officer Elections'],
            ['slug' => 'event-activity-logs', 'title' => 'Event & Activity Logs'],
            ['slug' => 'attendance-tracker', 'title' => 'Attendance Tracker'],
            ['slug' => 'club-achievement-records', 'title' => 'Club Achievement Records'],
            ['slug' => 'budget-requests', 'title' => 'Budget Requests'],
            ['slug' => 'inter-school-communication', 'title' => 'Inter-school Communication'],
            ['slug' => 'volunteer-hour-tracking', 'title' => 'Volunteer Hour Tracking'],
            ['slug' => 'club-directory', 'title' => 'Club Directory'],
        ],
    ],
    'lms' => [
        'label' => 'Online Learning & LMS',
        'icon'  => 'fa-laptop',
        'groups' => [
            'Class & Materials' => [
                'class-portal',
                'lesson-material-upload',
                'multimedia-support',
                'virtual-classroom-integration',
            ],
            'Assessments' => [
                'assignment-submission',
                'online-quiz',
                'grading-integration',
                'feedback-comments',
            ],
            'Tracking & Analytics' => [
                'module-completion-tracking',
                'lms-analytics',
            ],
        ],
        'pages' => [
            ['slug' => 'class-portal', 'title' => 'Class Portal'],
            ['slug' => 'lesson-material-upload', 'title' => 'Lesson Material Upload'],
            ['slug' => 'assignment-submission', 'title' => 'Assignment Submission'],
            ['slug' => 'online-quiz', 'title' => 'Online Quiz'],
            ['slug' => 'virtual-classroom-integration', 'title' => 'Virtual Classroom Integration'],
            ['slug' => 'grading-integration', 'title' => 'Grading Integration'],
            ['slug' => 'feedback-comments', 'title' => 'Feedback & Comments'],
            ['slug' => 'module-completion-tracking', 'title' => 'Module Completion Tracking'],
            ['slug' => 'multimedia-support', 'title' => 'Multimedia Support'],
            ['slug' => 'lms-analytics', 'title' => 'LMS Analytics'],
        ],
    ],
    'crad' => [
        'label' => 'CRAD',
        'icon'  => 'fa-flask',
        'groups' => [
            'Research Clearance' => [
                'research-clearance',
            ],
            'Research Proposal' => [
                'register-proposal',
                'research-group-number',
            ],
            'Research Management' => [
                'research-coordinator-management',
                'research-defense-scheduling',
                'capstone-group-student-registry',
            ],
            'Core System' => [
                'dashboard-analytics',
                'grant-opportunities',
                'proposals-applications',
            ],
            'Review & Workflow' => [
                'reviewer-evaluation',
                'approval-workflows',
            ],
            'Financial & Tracking' => [
                'approved-funded',
                'budget-disbursement',
                'project-milestones',
            ],
            'Outputs & Records' => [
                'publications-ip',
                'document-repository',
            ],
            'Research Documents' => [
                'documentation-publication-management',
                'final-manuscript-review',
                'revision-compliance',
                'final-manuscript-approval',
                'publication-create',
                'research-repository',
            ],
            'Reports' => [
                'research-analytics-reporting',
            ],
        ],
        'pages' => [
            ['slug' => 'register-proposal', 'title' => 'Register Proposal'],
            ['slug' => 'research-group-number', 'title' => 'Research Group Number'],
            ['slug' => 'adviser-panel-assignment', 'title' => 'Record Adviser/Panel Assignment'],
            ['slug' => 'research-coordinator-management', 'title' => 'Research Coordinator Management'],
            ['slug' => 'capstone-group-student-registry', 'title' => 'Capstone Group/Student Registry'],
            ['slug' => 'research-defense-scheduling', 'title' => 'Research Defense Scheduling'],
            ['slug' => 'dashboard-analytics', 'title' => 'Dashboard & Analytics'],
            ['slug' => 'grant-opportunities', 'title' => 'Grant Opportunities'],
            ['slug' => 'proposals-applications', 'title' => 'Proposals & Applications'],
            ['slug' => 'reviewer-evaluation', 'title' => 'Reviewer Evaluation'],
            ['slug' => 'approval-workflows', 'title' => 'Approval Workflows'],
            ['slug' => 'approved-funded', 'title' => 'Approved & Funded'],
            ['slug' => 'funded-research', 'title' => 'Conduct Funded Research'],
            ['slug' => 'budget-disbursement', 'title' => 'Budget & Disbursement'],
            ['slug' => 'project-milestones', 'title' => 'Project Milestones'],
            ['slug' => 'publications-ip', 'title' => 'Publications & IP'],
            ['slug' => 'document-repository', 'title' => 'Document Repository'],
            ['slug' => 'research-clearance', 'title' => 'Research Services Clearance'],
            ['slug' => 'approval-clearance-payment', 'title' => 'Approval Clearance Payment'],
            ['slug' => 'documentation-publication-management', 'title' => 'Documentation & Publication Management'],
            ['slug' => 'final-manuscript-review', 'title' => 'Final Manuscript Review'],
            ['slug' => 'revision-compliance', 'title' => 'Revision & Compliance'],
            ['slug' => 'final-manuscript-approval', 'title' => 'Final Manuscript Approval'],
            ['slug' => 'publication-create', 'title' => 'Create Publication Record'],
            ['slug' => 'research-repository', 'title' => 'Research Repository'],
            ['slug' => 'research-analytics-reporting', 'title' => 'Research Analytics & Reporting'],
        ],
    ],
    'reports-analytics' => [
        'label' => 'Reports & Analytics',
        'icon'  => 'fa-chart-bar',
        'pages' => [
            // Pages are filtered per role via includes/reports-catalog.php
            ['slug' => 'performance-trends', 'title' => 'Performance Trends'],
            ['slug' => 'export-center', 'title' => 'Export Center'],
            ['slug' => 'enrollment-analytics', 'title' => 'Enrollment Analytics'],
            ['slug' => 'student-records-report', 'title' => 'Student Records Report'],
            ['slug' => 'document-release-analytics', 'title' => 'Document Release Analytics'],
            ['slug' => 'curriculum-analytics', 'title' => 'Curriculum Analytics'],
            ['slug' => 'class-schedule-analytics', 'title' => 'Class Schedule Analytics'],
            ['slug' => 'research-proposal-analytics', 'title' => 'Research Proposal Analytics'],
            ['slug' => 'adviser-grants-report', 'title' => 'Adviser & Grants Report'],
            ['slug' => 'publication-repository-report', 'title' => 'Publication & Repository Report'],
            ['slug' => 'collections-analytics', 'title' => 'Collections Analytics'],
            ['slug' => 'receivables-report', 'title' => 'Receivables Report'],
            ['slug' => 'faculty-load-report', 'title' => 'Faculty Load Report'],
            ['slug' => 'leave-evaluation-analytics', 'title' => 'Leave & Evaluation Analytics'],
            ['slug' => 'lms-engagement-report', 'title' => 'LMS Engagement Report'],
            ['slug' => 'module-completion-analytics', 'title' => 'Module Completion Analytics'],
            ['slug' => 'club-activity-report', 'title' => 'Club & Activity Report'],
            ['slug' => 'volunteer-budget-analytics', 'title' => 'Volunteer & Budget Analytics'],
            ['slug' => 'accreditation-compliance-report', 'title' => 'Accreditation Compliance Report'],
            ['slug' => 'audit-findings-analytics', 'title' => 'Audit Findings Analytics'],
        ],
    ],

    // ── Research Grant (CRAD Officer – grant management view) ───────────────
    'crad_grant' => [
        'label' => 'Research Grant',
        'icon'  => 'fa-hand-holding-usd',
        'groups' => [
            'Grant Management' => [
                'grant-opportunities',
                'post-publish-grant-call',
                'grant-applications',
            ],
            'Review & Workflow' => [
                'reviewer-evaluation',
            ],
            'Proposal Evaluation' => [
                'for-evaluation',
                'evaluation-scoring',
                'evaluation-history',
            ],
            'Approval Workflow' => [
                'approval-workflows',
            ],
            'Funding Management' => [
                'approved-funded',
                'budget-disbursement',
                'project-milestones',
                'fund-release',
                'disbursement-records',
                'funding-status',
            ],
            'Research Monitoring' => [
                'funded-research',
                'progress-tracking',
            ],
            'Outputs & Records' => [
                'publications-ip',
                'document-repository',
            ],
            'Reports' => [
                'grant-reports',
                'funding-reports',
                'research-analytics',
            ],
        ],
        'pages' => [
            ['slug' => 'grant-opportunities',       'title' => 'Grant Opportunities'],
            ['slug' => 'post-publish-grant-call',   'title' => 'Post / Publish Grant Call'],
            ['slug' => 'grant-applications',        'title' => 'Grant Applications'],
            ['slug' => 'reviewer-evaluation',       'title' => 'Reviewer Evaluation'],
            ['slug' => 'for-evaluation',            'title' => 'For Evaluation'],
            ['slug' => 'evaluation-scoring',        'title' => 'Evaluation & Scoring'],
            ['slug' => 'evaluation-history',        'title' => 'Evaluation History'],
            ['slug' => 'approval-workflows',         'title' => 'Approval Workflows'],
            ['slug' => 'pending-approvals',         'title' => 'Pending Approvals'],
            ['slug' => 'approval-status',           'title' => 'Approval Status'],
            ['slug' => 'approval-history',          'title' => 'Approval History'],
            ['slug' => 'approved-funded',           'title' => 'Approved & Funded'],
            ['slug' => 'budget-disbursement',       'title' => 'Budget & Disbursement'],
            ['slug' => 'fund-release',              'title' => 'Fund Release'],
            ['slug' => 'disbursement-records',      'title' => 'Disbursement Records'],
            ['slug' => 'funding-status',            'title' => 'Funding Status'],
            ['slug' => 'funded-research',           'title' => 'Funded Research'],
            ['slug' => 'project-milestones',        'title' => 'Project Milestones'],
            ['slug' => 'progress-tracking',         'title' => 'Progress Tracking'],
            ['slug' => 'publications-ip',           'title' => 'Publications & IP'],
            ['slug' => 'document-repository',      'title' => 'Document Repository'],
            ['slug' => 'grant-reports',             'title' => 'Grant Reports'],
            ['slug' => 'funding-reports',           'title' => 'Funding Reports'],
            ['slug' => 'research-analytics',        'title' => 'Research Analytics'],
        ],
    ],

    // ── Superadmin only ──────────────────────────────────────────────────────
    'user-management' => [
        'label' => 'User Management',
        'icon'  => 'fa-users-cog',
        'hide_overview' => true,
        'groups' => [
            'Accounts & Roles' => [
                'user-accounts',
                'role-permissions',
            ],
            'Security' => [
                'module-security',
            ],
            'Monitoring' => [
                'activity-logs',
            ],
            'Settings' => [
                'system-settings',
            ],
        ],
        'pages' => [
            ['slug' => 'user-accounts',    'title' => 'User Accounts'],
            ['slug' => 'role-permissions', 'title' => 'Role & Permissions'],
            ['slug' => 'module-security',  'title' => 'Module Security'],
            ['slug' => 'activity-logs',    'title' => 'Activity Logs'],
            ['slug' => 'system-settings',  'title' => 'System Settings'],
        ],
    ],
];
