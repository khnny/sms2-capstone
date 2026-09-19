<?php
/**
 * SMS 2 - Real page UI definitions (fleet-style list content per module/page)
 */

if (!function_exists('smsModulePageUi')) {
    function smsModulePageUi(string $module, string $pageSlug, string $pageTitle = ''): ?array
    {
        $catalog = smsModulePageUiCatalog();
        $page = $catalog[$module][$pageSlug] ?? null;
        if ($page === null) {
            return null;
        }

        $title = $pageTitle !== '' ? $pageTitle : ($page['title'] ?? $pageSlug);
        $rows = $page['rows'] ?? [];
        if ($module === 'crad' && $pageSlug === 'research-analytics-reporting' && function_exists('cradDb')) {
            try {
                $crad = cradDb();
                if ($crad instanceof PDO) {
                    $count = static function (PDO $db, string $sql): string {
                        try {
                            return (string) ((int) $db->query($sql)->fetchColumn());
                        } catch (Throwable $e) {
                            error_log('CRAD analytics metric query failed: ' . $e->getMessage());
                            return '0';
                        }
                    };
                    $totalGroups = $count($crad, 'SELECT COUNT(*) FROM research_groups');
                    $approvedTitles = $count($crad, "SELECT COUNT(*) FROM title_approvals WHERE status = 'Approved'");
                    $withAdvisers = $count($crad, "SELECT COUNT(DISTINCT research_group_id) FROM research_adviser_assignments WHERE assignment_status IN ('Assigned', 'Confirmed') AND research_group_id IS NOT NULL");
                    $preOralCompleted = $count($crad, "SELECT COUNT(*) FROM research_defense_schedules WHERE defense_type = 'Pre-Oral' AND LOWER(status) IN ('scheduled', 'finalized', 'final', 'completed', 'passed')");
                    $finalDefenseCompleted = $count($crad, "SELECT COUNT(*) FROM research_defense_schedules WHERE defense_type = 'Final Defense' AND LOWER(status) IN ('scheduled', 'finalized', 'final', 'completed', 'passed')");
                    $finalApproved = $count($crad, "SELECT COUNT(*) FROM final_manuscript_approvals WHERE status = 'Approved'");
                    $forRevision = $count($crad, "SELECT COUNT(*) FROM research_revision_cycles WHERE revision_status IN ('Needs Revision', 'Under Review')");
                    $validPublicationTitleSql = function_exists('cradValidTitleApprovalWhereSql') ? cradValidTitleApprovalWhereSql('pub_ta') : "pub_ta.status = 'Approved'";
                    $published = $count($crad, "SELECT COUNT(*) FROM publications p INNER JOIN research_groups pub_rg ON pub_rg.id = p.research_group_id INNER JOIN title_approvals pub_ta ON pub_ta.id = pub_rg.title_approval_id AND {$validPublicationTitleSql} WHERE p.status = 'Published'");
                    $page['stats'] = [
                        ['label' => 'Total Groups', 'value' => $totalGroups, 'icon' => 'fa-users', 'tone' => 'blue'],
                        ['label' => 'Approved Titles', 'value' => $approvedTitles, 'icon' => 'fa-check-circle', 'tone' => 'green'],
                        ['label' => 'Final Defenses Completed', 'value' => $finalDefenseCompleted, 'icon' => 'fa-gavel', 'tone' => 'amber'],
                        ['label' => 'Published', 'value' => $published, 'icon' => 'fa-book-open', 'tone' => 'purple'],
                    ];
                    if (!empty($page['rows'][0])) {
                        $page['rows'][0]['detail'] = 'Total Groups ' . $totalGroups
                            . ' | Approved Titles ' . $approvedTitles
                            . ' | With Advisers ' . $withAdvisers
                            . ' | Pre-Oral Completed ' . $preOralCompleted
                            . ' | Final Defense Completed ' . $finalDefenseCompleted
                            . ' | Final Approved ' . $finalApproved
                            . ' | For Revision ' . $forRevision
                            . ' | Published ' . $published;
                        $page['rows'][0]['schedule'] = date('M j, Y');
                    }
                    $page['rows'] = array_values(array_filter([
                        $totalGroups !== '0' ? ['reference' => 'RAN-001', 'subject' => 'Capstone Analytics', 'subtitle' => 'Overall capstone performance', 'owner' => 'CRAD', 'detail' => 'Total Groups ' . $totalGroups . ' | Approved Titles ' . $approvedTitles . ' | With Advisers ' . $withAdvisers . ' | Pre-Oral Completed ' . $preOralCompleted . ' | Final Defense Completed ' . $finalDefenseCompleted . ' | Final Approved ' . $finalApproved . ' | For Revision ' . $forRevision . ' | Published ' . $published, 'schedule' => date('M j, Y'), 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Capstone Analytics'] : null,
                        $withAdvisers !== '0' ? ['reference' => 'RAN-004', 'subject' => 'Adviser Reports', 'subtitle' => 'Adviser assignments', 'owner' => 'CRAD', 'detail' => 'Assigned groups and adviser monitoring', 'schedule' => date('M j, Y'), 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Adviser Reports'] : null,
                        $preOralCompleted !== '0' || $finalDefenseCompleted !== '0' ? ['reference' => 'RAN-003', 'subject' => 'Defense Reports', 'subtitle' => 'Pre-Oral and Final Defense', 'owner' => 'CRAD', 'detail' => 'Schedules, panel evaluations, and defense outcomes', 'schedule' => date('M j, Y'), 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Defense Reports'] : null,
                        $finalApproved !== '0' ? ['reference' => 'RAN-005', 'subject' => 'Completion Reports', 'subtitle' => 'Final manuscript approval', 'owner' => 'CRAD', 'detail' => 'Completed research groups and approval status', 'schedule' => date('M j, Y'), 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Completion Reports'] : null,
                        ($published !== '0' || $approvedTitles !== '0') ? ['reference' => 'RAN-006', 'subject' => 'Publication Reports', 'subtitle' => 'Publication and repository', 'owner' => 'CRAD', 'detail' => 'Draft, For Publication, Published, and Archived records', 'schedule' => date('M j, Y'), 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Publication Reports'] : null,
                        $forRevision !== '0' ? ['reference' => 'RAN-002', 'subject' => 'Progress Reports', 'subtitle' => 'Research development', 'owner' => 'CRAD', 'detail' => 'Milestone and research group progress', 'schedule' => date('M j, Y'), 'status' => 'Needs Attention', 'status_class' => 'cancelled', 'type' => 'Progress Reports'] : null,
                    ], static fn($row): bool => is_array($row)));
                }
            } catch (Throwable $e) {
                error_log('CRAD analytics catalog stats failed: ' . $e->getMessage());
            }
        }
        if ($module === 'crad' && $pageSlug === 'research-analytics-reporting') {
            $selectedReport = trim((string) ($_GET['report'] ?? ''));
            if ($selectedReport !== '') {
                $rows = array_values(array_filter($rows, static function (array $row) use ($selectedReport): bool {
                    $type = strtolower(trim((string) ($row['type'] ?? '')));
                    return $type === strtolower(str_replace('-', ' ', $selectedReport));
                }));
            }
        }
        $statuses = ['All Status'];
        $types = ['All Types'];
        foreach ($rows as $row) {
            $st = (string) ($row['status'] ?? '');
            $tp = (string) ($row['type'] ?? ($row['subtitle'] ?? ''));
            if ($st !== '' && !in_array($st, $statuses, true)) {
                $statuses[] = $st;
            }
            if ($tp !== '' && !in_array($tp, $types, true)) {
                $types[] = $tp;
            }
        }

        return [
            'description' => $page['description'] ?? ('Manage ' . $title . ' records.'),
            'add_label' => $page['add_label'] ?? ('+ Add ' . $title),
            'add_process' => $page['add_process'] ?? 'new',
            'stats' => $page['stats'] ?? [],
            'search_placeholder' => $page['search_placeholder'] ?? 'Search records...',
            'statuses' => $statuses,
            'types' => $types,
            'list_title' => $title . ' List',
            'list_subtitle' => $page['list_subtitle'] ?? ('View and manage all ' . strtolower($title) . ' records.'),
            'columns' => $page['columns'] ?? [
                'ref' => 'Reference No.',
                'subject' => 'Subject',
                'owner' => 'Assigned To',
                'detail' => 'Detail',
                'schedule' => 'Schedule',
            ],
            'rows' => $rows,
            'show_archive_button' => $pageSlug !== 'research-analytics-reporting',
            'show_add_button' => $pageSlug !== 'research-analytics-reporting',
        ];
    }
}

if (!function_exists('smsBuildSampleRows')) {
    function smsBuildSampleRows(string $prefix, int $start, array $items, string $type = 'General'): array
    {
        $rows = [];
        $dates = [
            'Jul 18, 2026 09:00',
            'Jul 17, 2026 14:30',
            'Jul 16, 2026 10:15',
            'Jul 15, 2026 08:45',
            'Jul 14, 2026 13:00',
        ];
        foreach ($items as $index => $item) {
            [$subject, $owner, $detail, $status, $statusClass] = $item;
            $rowType = $item[5] ?? $type;
            $rows[] = [
                'reference' => $prefix . '-2026-' . str_pad((string) ($start + $index), 3, '0', STR_PAD_LEFT),
                'subject' => $subject,
                'subtitle' => $rowType,
                'owner' => $owner,
                'detail' => $detail,
                'schedule' => $dates[$index % count($dates)],
                'status' => $status,
                'status_class' => $statusClass,
                'type' => $rowType,
            ];
        }
        return $rows;
    }
}

if (!function_exists('smsModulePageUiCatalog')) {
    function smsModulePageUiCatalog(): array
    {
        static $catalog = null;
        if ($catalog !== null) {
            return $catalog;
        }

        require_once ROOT_PATH . '/includes/module-page-ui-offices.php';
        require_once ROOT_PATH . '/includes/module-page-ui-offices-more.php';

        $catalog = [
            'enrollment' => smsEnrollmentPageUiCatalog(),
            'registrar' => smsRegistrarPageUiCatalog(),
            'curriculum' => smsCurriculumPageUiCatalog(),
            'scheduling' => smsSchedulingPageUiCatalog(),
            'payment' => smsPaymentPageUiCatalog(),
            'faculty' => smsFacultyPageUiCatalog(),
            'lms' => smsLmsPageUiCatalog(),
            'cocurricular' => smsCocurricularPageUiCatalog(),
            'accreditation' => smsAccreditationPageUiCatalog(),
            'crad' => smsCradPageUiCatalog(),
        ];

        return $catalog;
    }
}

if (!function_exists('smsEnrollmentPageUiCatalog')) {
    function smsEnrollmentPageUiCatalog(): array
    {
        return [
            'online-pre-registration' => [
                'title' => 'Online Pre-registration',
                'description' => 'Manage applicant online forms, program choices, admission checks, and pre-registration results.',
                'add_label' => '+ New Pre-registration',
                'list_subtitle' => 'View and manage all online pre-registration applications.',
                'search_placeholder' => 'Search by application no., applicant, or program.',
                'stats' => [
                    ['label' => 'Total Applications', 'value' => '5', 'icon' => 'fa-layer-group', 'tone' => 'blue'],
                    ['label' => 'Pending Review', 'value' => '2', 'icon' => 'fa-clock', 'tone' => 'amber'],
                    ['label' => 'Validated', 'value' => '2', 'icon' => 'fa-check-circle', 'tone' => 'green'],
                    ['label' => 'Returned', 'value' => '1', 'icon' => 'fa-undo', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Application No.',
                    'subject' => 'Applicant',
                    'owner' => 'Program',
                    'detail' => 'Grade Level',
                    'schedule' => 'Submitted',
                ],
                'rows' => [
                    ['reference' => 'PREREG-2026-001', 'subject' => 'Sofia Reyes', 'subtitle' => 'New Student', 'owner' => 'BSIT', 'detail' => '1st Year', 'schedule' => 'Jul 18, 2026 09:12', 'status' => 'Pending', 'status_class' => 'pending', 'type' => 'New Student'],
                    ['reference' => 'PREREG-2026-002', 'subject' => 'Mark Villanueva', 'subtitle' => 'Transferee', 'owner' => 'BSED English', 'detail' => '2nd Year', 'schedule' => 'Jul 17, 2026 14:40', 'status' => 'Validated', 'status_class' => 'completed', 'type' => 'Transferee'],
                    ['reference' => 'PREREG-2026-003', 'subject' => 'Angela Cruz', 'subtitle' => 'New Student', 'owner' => 'BSBA', 'detail' => '1st Year', 'schedule' => 'Jul 16, 2026 11:05', 'status' => 'In Progress', 'status_class' => 'progress', 'type' => 'New Student'],
                    ['reference' => 'PREREG-2026-004', 'subject' => 'Carlos Mendoza', 'subtitle' => 'Returnee', 'owner' => 'BSCrim', 'detail' => '3rd Year', 'schedule' => 'Jul 15, 2026 08:30', 'status' => 'Returned', 'status_class' => 'cancelled', 'type' => 'Returnee'],
                    ['reference' => 'PREREG-2026-005', 'subject' => 'Liza Santos', 'subtitle' => 'New Student', 'owner' => 'BSHM', 'detail' => '1st Year', 'schedule' => 'Jul 14, 2026 16:20', 'status' => 'Validated', 'status_class' => 'completed', 'type' => 'New Student'],
                ],
            ],
            'document-upload-portal' => [
                'title' => 'Document Upload Portal',
                'description' => 'Monitor applicant uploaded requirements, verification remarks, missing files, and resubmission requests.',
                'add_label' => '+ Add Requirement',
                'list_subtitle' => 'View and manage all uploaded enrollment documents.',
                'search_placeholder' => 'Search by file no., applicant, or document type.',
                'stats' => [
                    ['label' => 'Uploaded Files', 'value' => '5', 'icon' => 'fa-cloud-upload-alt', 'tone' => 'blue'],
                    ['label' => 'For Verification', 'value' => '2', 'icon' => 'fa-search', 'tone' => 'amber'],
                    ['label' => 'Verified', 'value' => '2', 'icon' => 'fa-check', 'tone' => 'green'],
                    ['label' => 'Rejected', 'value' => '1', 'icon' => 'fa-times-circle', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'File No.',
                    'subject' => 'Applicant',
                    'owner' => 'Document Type',
                    'detail' => 'File Format',
                    'schedule' => 'Uploaded',
                ],
                'rows' => [
                    ['reference' => 'DOC-2026-101', 'subject' => 'Sofia Reyes', 'subtitle' => 'Birth Certificate', 'owner' => 'PSA Birth Certificate', 'detail' => 'PDF · 1.2 MB', 'schedule' => 'Jul 18, 2026 10:05', 'status' => 'For Verification', 'status_class' => 'pending', 'type' => 'Birth Certificate'],
                    ['reference' => 'DOC-2026-102', 'subject' => 'Mark Villanueva', 'subtitle' => 'Form 138', 'owner' => 'Report Card', 'detail' => 'PDF · 860 KB', 'schedule' => 'Jul 17, 2026 13:22', 'status' => 'Verified', 'status_class' => 'completed', 'type' => 'Report Card'],
                    ['reference' => 'DOC-2026-103', 'subject' => 'Angela Cruz', 'subtitle' => 'Good Moral', 'owner' => 'Good Moral Certificate', 'detail' => 'JPG · 420 KB', 'schedule' => 'Jul 16, 2026 09:48', 'status' => 'Rejected', 'status_class' => 'cancelled', 'type' => 'Good Moral'],
                    ['reference' => 'DOC-2026-104', 'subject' => 'Carlos Mendoza', 'subtitle' => 'TOR', 'owner' => 'Transcript of Records', 'detail' => 'PDF · 2.1 MB', 'schedule' => 'Jul 15, 2026 15:10', 'status' => 'Verified', 'status_class' => 'completed', 'type' => 'TOR'],
                    ['reference' => 'DOC-2026-105', 'subject' => 'Liza Santos', 'subtitle' => 'ID Photo', 'owner' => '2x2 Photo', 'detail' => 'PNG · 180 KB', 'schedule' => 'Jul 14, 2026 11:35', 'status' => 'For Verification', 'status_class' => 'pending', 'type' => 'ID Photo'],
                ],
            ],
            'enrollment-validation' => [
                'title' => 'Enrollment Validation',
                'description' => 'Validate enrollment eligibility, duplicate records, missing requirements, and admission rule checks.',
                'add_label' => '+ New Validation',
                'list_subtitle' => 'View and manage enrollment validation queue.',
                'search_placeholder' => 'Search by validation no., student, or finding.',
                'stats' => [
                    ['label' => 'In Queue', 'value' => '5', 'icon' => 'fa-list-check', 'tone' => 'blue'],
                    ['label' => 'Failed Checks', 'value' => '1', 'icon' => 'fa-exclamation-triangle', 'tone' => 'amber'],
                    ['label' => 'Passed', 'value' => '3', 'icon' => 'fa-check-double', 'tone' => 'green'],
                    ['label' => 'Exceptions', 'value' => '1', 'icon' => 'fa-flag', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Validation No.',
                    'subject' => 'Student / Applicant',
                    'owner' => 'Checked By',
                    'detail' => 'Finding',
                    'schedule' => 'Validated',
                ],
                'rows' => [
                    ['reference' => 'VAL-2026-201', 'subject' => 'Sofia Reyes', 'subtitle' => 'Requirements Check', 'owner' => 'Admissions Staff', 'detail' => 'Complete documents', 'schedule' => 'Jul 18, 2026 11:00', 'status' => 'Passed', 'status_class' => 'completed', 'type' => 'Requirements'],
                    ['reference' => 'VAL-2026-202', 'subject' => 'Mark Villanueva', 'subtitle' => 'Duplicate Check', 'owner' => 'Registrar Aide', 'detail' => 'No duplicate found', 'schedule' => 'Jul 17, 2026 15:40', 'status' => 'Passed', 'status_class' => 'completed', 'type' => 'Duplicate'],
                    ['reference' => 'VAL-2026-203', 'subject' => 'Angela Cruz', 'subtitle' => 'Balance Check', 'owner' => 'Finance Desk', 'detail' => 'Outstanding balance', 'schedule' => 'Jul 16, 2026 10:20', 'status' => 'Failed', 'status_class' => 'cancelled', 'type' => 'Balance'],
                    ['reference' => 'VAL-2026-204', 'subject' => 'Carlos Mendoza', 'subtitle' => 'Eligibility Check', 'owner' => 'Admissions Staff', 'detail' => 'Eligible returnee', 'schedule' => 'Jul 15, 2026 09:05', 'status' => 'Passed', 'status_class' => 'completed', 'type' => 'Eligibility'],
                    ['reference' => 'VAL-2026-205', 'subject' => 'Liza Santos', 'subtitle' => 'Exception Review', 'owner' => 'Enrollment Officer', 'detail' => 'Pending guardian consent', 'schedule' => 'Jul 14, 2026 14:55', 'status' => 'Exception', 'status_class' => 'pending', 'type' => 'Exception'],
                ],
            ],
            'id-number-generation' => [
                'title' => 'ID Number Generation',
                'description' => 'Generate and verify student ID numbers, barcode/QR references, and release readiness.',
                'add_label' => '+ New ID Request',
                'list_subtitle' => 'View and manage student ID number generation requests.',
                'search_placeholder' => 'Search by request no., student name, or ID number.',
                'stats' => [
                    ['label' => 'ID Requests', 'value' => '5', 'icon' => 'fa-hashtag', 'tone' => 'blue'],
                    ['label' => 'For Encoding', 'value' => '2', 'icon' => 'fa-keyboard', 'tone' => 'amber'],
                    ['label' => 'Generated', 'value' => '2', 'icon' => 'fa-id-card', 'tone' => 'green'],
                    ['label' => 'Ready to Claim', 'value' => '1', 'icon' => 'fa-hand-holding', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Request No.',
                    'subject' => 'Student',
                    'owner' => 'Generated ID',
                    'detail' => 'Program / Year',
                    'schedule' => 'Processed',
                ],
                'rows' => [
                    ['reference' => 'IDGEN-2026-001', 'subject' => 'Sofia Reyes', 'subtitle' => 'New Enrollee', 'owner' => 'S260000101', 'detail' => 'BSIT · 1st Year', 'schedule' => 'Jul 18, 2026 09:40', 'status' => 'Generated', 'status_class' => 'completed', 'type' => 'New Enrollee'],
                    ['reference' => 'IDGEN-2026-002', 'subject' => 'Mark Villanueva', 'subtitle' => 'Transferee', 'owner' => 'S260000102', 'detail' => 'BSED · 2nd Year', 'schedule' => 'Jul 17, 2026 13:15', 'status' => 'Ready to Claim', 'status_class' => 'scheduled', 'type' => 'Transferee'],
                    ['reference' => 'IDGEN-2026-003', 'subject' => 'Angela Cruz', 'subtitle' => 'New Enrollee', 'owner' => 'Pending', 'detail' => 'BSBA · 1st Year', 'schedule' => 'Jul 16, 2026 10:50', 'status' => 'For Encoding', 'status_class' => 'pending', 'type' => 'New Enrollee'],
                    ['reference' => 'IDGEN-2026-004', 'subject' => 'Carlos Mendoza', 'subtitle' => 'Returnee', 'owner' => 'S230000088', 'detail' => 'BSCrim · 3rd Year', 'schedule' => 'Jul 15, 2026 08:20', 'status' => 'Generated', 'status_class' => 'completed', 'type' => 'Returnee'],
                    ['reference' => 'IDGEN-2026-005', 'subject' => 'Liza Santos', 'subtitle' => 'New Enrollee', 'owner' => 'Pending', 'detail' => 'BSHM · 1st Year', 'schedule' => 'Jul 14, 2026 15:05', 'status' => 'For Encoding', 'status_class' => 'pending', 'type' => 'New Enrollee'],
                ],
            ],
            'grade-level-assignment' => [
                'title' => 'Grade Level Assignment',
                'description' => 'Assign approved enrollees to the correct grade level, year level, and academic track.',
                'add_label' => '+ New Assignment',
                'list_subtitle' => 'View and manage grade level assignment batches.',
                'search_placeholder' => 'Search by assignment no., student, or grade level.',
                'stats' => [
                    ['label' => 'Assignments', 'value' => '5', 'icon' => 'fa-layer-group', 'tone' => 'blue'],
                    ['label' => 'For Review', 'value' => '1', 'icon' => 'fa-clock', 'tone' => 'amber'],
                    ['label' => 'Assigned', 'value' => '3', 'icon' => 'fa-user-check', 'tone' => 'green'],
                    ['label' => 'Conflicts', 'value' => '1', 'icon' => 'fa-exclamation', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Assignment No.',
                    'subject' => 'Student',
                    'owner' => 'Assigned Level',
                    'detail' => 'Program / Strand',
                    'schedule' => 'Assigned',
                ],
                'rows' => [
                    ['reference' => 'GLA-2026-011', 'subject' => 'Sofia Reyes', 'subtitle' => 'Incoming', 'owner' => '1st Year', 'detail' => 'BSIT', 'schedule' => 'Jul 18, 2026 10:00', 'status' => 'Assigned', 'status_class' => 'completed', 'type' => 'Incoming'],
                    ['reference' => 'GLA-2026-012', 'subject' => 'Mark Villanueva', 'subtitle' => 'Transferee', 'owner' => '2nd Year', 'detail' => 'BSED English', 'schedule' => 'Jul 17, 2026 11:30', 'status' => 'Assigned', 'status_class' => 'completed', 'type' => 'Transferee'],
                    ['reference' => 'GLA-2026-013', 'subject' => 'Angela Cruz', 'subtitle' => 'Incoming', 'owner' => '1st Year', 'detail' => 'BSBA', 'schedule' => 'Jul 16, 2026 09:15', 'status' => 'For Review', 'status_class' => 'pending', 'type' => 'Incoming'],
                    ['reference' => 'GLA-2026-014', 'subject' => 'Carlos Mendoza', 'subtitle' => 'Returnee', 'owner' => '3rd Year', 'detail' => 'BSCrim', 'schedule' => 'Jul 15, 2026 14:00', 'status' => 'Conflict', 'status_class' => 'cancelled', 'type' => 'Returnee'],
                    ['reference' => 'GLA-2026-015', 'subject' => 'Liza Santos', 'subtitle' => 'Incoming', 'owner' => '1st Year', 'detail' => 'BSHM', 'schedule' => 'Jul 14, 2026 16:45', 'status' => 'Assigned', 'status_class' => 'completed', 'type' => 'Incoming'],
                ],
            ],
            'waiting-list-queue' => [
                'title' => 'Waiting List Queue',
                'description' => 'Rank waitlisted applicants, monitor slot openings, and move applicants when capacity becomes available.',
                'add_label' => '+ Add to Waitlist',
                'list_subtitle' => 'View and manage all waitlisted enrollment applicants.',
                'search_placeholder' => 'Search by queue no., applicant, or program.',
                'stats' => [
                    ['label' => 'Waitlisted', 'value' => '5', 'icon' => 'fa-stream', 'tone' => 'blue'],
                    ['label' => 'Priority High', 'value' => '2', 'icon' => 'fa-arrow-up', 'tone' => 'amber'],
                    ['label' => 'Promoted', 'value' => '1', 'icon' => 'fa-user-plus', 'tone' => 'green'],
                    ['label' => 'On Hold', 'value' => '1', 'icon' => 'fa-pause', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Queue No.',
                    'subject' => 'Applicant',
                    'owner' => 'Program',
                    'detail' => 'Queue Rank',
                    'schedule' => 'Listed',
                ],
                'rows' => [
                    ['reference' => 'WAIT-2026-001', 'subject' => 'Nina Lopez', 'subtitle' => 'BSIT Slot', 'owner' => 'BSIT', 'detail' => 'Rank 01', 'schedule' => 'Jul 18, 2026 08:10', 'status' => 'Priority High', 'status_class' => 'pending', 'type' => 'BSIT'],
                    ['reference' => 'WAIT-2026-002', 'subject' => 'Paolo Garcia', 'subtitle' => 'BSBA Slot', 'owner' => 'BSBA', 'detail' => 'Rank 02', 'schedule' => 'Jul 17, 2026 12:25', 'status' => 'Waiting', 'status_class' => 'scheduled', 'type' => 'BSBA'],
                    ['reference' => 'WAIT-2026-003', 'subject' => 'Rina Dela Cruz', 'subtitle' => 'BSED Slot', 'owner' => 'BSED English', 'detail' => 'Rank 03', 'schedule' => 'Jul 16, 2026 09:50', 'status' => 'Promoted', 'status_class' => 'completed', 'type' => 'BSED'],
                    ['reference' => 'WAIT-2026-004', 'subject' => 'Jules Ramos', 'subtitle' => 'BSCrim Slot', 'owner' => 'BSCrim', 'detail' => 'Rank 04', 'schedule' => 'Jul 15, 2026 15:35', 'status' => 'On Hold', 'status_class' => 'cancelled', 'type' => 'BSCrim'],
                    ['reference' => 'WAIT-2026-005', 'subject' => 'Kyla Fernandez', 'subtitle' => 'BSHM Slot', 'owner' => 'BSHM', 'detail' => 'Rank 05', 'schedule' => 'Jul 14, 2026 11:05', 'status' => 'Priority High', 'status_class' => 'pending', 'type' => 'BSHM'],
                ],
            ],
            'cross-enrollment-checker' => [
                'title' => 'Cross-enrollment Checker',
                'description' => 'Detect schedule conflicts, overload subjects, and cross-enrollment rule violations before final enrollment.',
                'add_label' => '+ New Check',
                'list_subtitle' => 'View and manage cross-enrollment conflict checks.',
                'search_placeholder' => 'Search by check no., student, or conflict.',
                'stats' => [
                    ['label' => 'Checks Run', 'value' => '5', 'icon' => 'fa-exchange-alt', 'tone' => 'blue'],
                    ['label' => 'Conflicts', 'value' => '2', 'icon' => 'fa-bolt', 'tone' => 'amber'],
                    ['label' => 'Cleared', 'value' => '2', 'icon' => 'fa-check', 'tone' => 'green'],
                    ['label' => 'Needs Fix', 'value' => '1', 'icon' => 'fa-wrench', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Check No.',
                    'subject' => 'Student',
                    'owner' => 'Subject Pair',
                    'detail' => 'Conflict Type',
                    'schedule' => 'Checked',
                ],
                'rows' => [
                    ['reference' => 'XCHK-2026-301', 'subject' => 'Sofia Reyes', 'subtitle' => 'Schedule', 'owner' => 'IT101 / GE01', 'detail' => 'Time overlap Mon 8-10', 'schedule' => 'Jul 18, 2026 09:55', 'status' => 'Conflict', 'status_class' => 'cancelled', 'type' => 'Schedule'],
                    ['reference' => 'XCHK-2026-302', 'subject' => 'Mark Villanueva', 'subtitle' => 'Load', 'owner' => 'EDUC201 / EDUC202', 'detail' => 'Within load limit', 'schedule' => 'Jul 17, 2026 14:10', 'status' => 'Cleared', 'status_class' => 'completed', 'type' => 'Load'],
                    ['reference' => 'XCHK-2026-303', 'subject' => 'Angela Cruz', 'subtitle' => 'Prerequisite', 'owner' => 'ACCTG2', 'detail' => 'Missing ACCTG1', 'schedule' => 'Jul 16, 2026 11:40', 'status' => 'Needs Fix', 'status_class' => 'pending', 'type' => 'Prerequisite'],
                    ['reference' => 'XCHK-2026-304', 'subject' => 'Carlos Mendoza', 'subtitle' => 'Schedule', 'owner' => 'CRIM301 / PE3', 'detail' => 'No conflict', 'schedule' => 'Jul 15, 2026 08:45', 'status' => 'Cleared', 'status_class' => 'completed', 'type' => 'Schedule'],
                    ['reference' => 'XCHK-2026-305', 'subject' => 'Liza Santos', 'subtitle' => 'Room', 'owner' => 'HM101 / NSTP1', 'detail' => 'Same room conflict', 'schedule' => 'Jul 14, 2026 13:20', 'status' => 'Conflict', 'status_class' => 'cancelled', 'type' => 'Room'],
                ],
            ],
            'auto-section-assignment' => [
                'title' => 'Auto Section Assignment',
                'description' => 'Automatically place validated enrollees into open sections based on capacity, program, and schedule rules.',
                'add_label' => '+ Run Assignment',
                'list_subtitle' => 'View and manage automatic section assignment results.',
                'search_placeholder' => 'Search by batch no., student, or section.',
                'stats' => [
                    ['label' => 'Batches', 'value' => '5', 'icon' => 'fa-magic', 'tone' => 'blue'],
                    ['label' => 'Unassigned', 'value' => '1', 'icon' => 'fa-user-clock', 'tone' => 'amber'],
                    ['label' => 'Assigned', 'value' => '3', 'icon' => 'fa-users', 'tone' => 'green'],
                    ['label' => 'Full Sections', 'value' => '1', 'icon' => 'fa-door-closed', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Batch No.',
                    'subject' => 'Student',
                    'owner' => 'Assigned Section',
                    'detail' => 'Room / Schedule',
                    'schedule' => 'Assigned',
                ],
                'rows' => [
                    ['reference' => 'SEC-2026-501', 'subject' => 'Sofia Reyes', 'subtitle' => 'BSIT', 'owner' => 'BSIT-1A', 'detail' => 'Lab 3 · MWF 8-10', 'schedule' => 'Jul 18, 2026 10:30', 'status' => 'Assigned', 'status_class' => 'completed', 'type' => 'BSIT'],
                    ['reference' => 'SEC-2026-502', 'subject' => 'Mark Villanueva', 'subtitle' => 'BSED', 'owner' => 'BSED-2B', 'detail' => 'Room 204 · TTh 1-3', 'schedule' => 'Jul 17, 2026 11:15', 'status' => 'Assigned', 'status_class' => 'completed', 'type' => 'BSED'],
                    ['reference' => 'SEC-2026-503', 'subject' => 'Angela Cruz', 'subtitle' => 'BSBA', 'owner' => 'Pending', 'detail' => 'No open slot', 'schedule' => 'Jul 16, 2026 09:05', 'status' => 'Unassigned', 'status_class' => 'pending', 'type' => 'BSBA'],
                    ['reference' => 'SEC-2026-504', 'subject' => 'Carlos Mendoza', 'subtitle' => 'BSCrim', 'owner' => 'CRIM-3A', 'detail' => 'Room 110 · MWF 1-3', 'schedule' => 'Jul 15, 2026 14:40', 'status' => 'Assigned', 'status_class' => 'completed', 'type' => 'BSCrim'],
                    ['reference' => 'SEC-2026-505', 'subject' => 'Liza Santos', 'subtitle' => 'BSHM', 'owner' => 'HM-1C', 'detail' => 'Section full', 'schedule' => 'Jul 14, 2026 16:00', 'status' => 'Full Section', 'status_class' => 'cancelled', 'type' => 'BSHM'],
                ],
            ],
            'parent-notification' => [
                'title' => 'Parent Notification',
                'description' => 'Send and track parent/guardian notices about enrollment status, missing requirements, and next steps.',
                'add_label' => '+ Send Notice',
                'list_subtitle' => 'View and manage parent and guardian enrollment notifications.',
                'search_placeholder' => 'Search by notice no., parent, or student.',
                'stats' => [
                    ['label' => 'Notices', 'value' => '5', 'icon' => 'fa-bell', 'tone' => 'blue'],
                    ['label' => 'Queued', 'value' => '1', 'icon' => 'fa-hourglass-half', 'tone' => 'amber'],
                    ['label' => 'Delivered', 'value' => '3', 'icon' => 'fa-paper-plane', 'tone' => 'green'],
                    ['label' => 'Failed', 'value' => '1', 'icon' => 'fa-exclamation-circle', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Notice No.',
                    'subject' => 'Parent / Guardian',
                    'owner' => 'Student',
                    'detail' => 'Channel',
                    'schedule' => 'Sent',
                ],
                'rows' => [
                    ['reference' => 'NOTIF-2026-701', 'subject' => 'Maria Reyes', 'subtitle' => 'Mother', 'owner' => 'Sofia Reyes', 'detail' => 'SMS + Email', 'schedule' => 'Jul 18, 2026 10:12', 'status' => 'Delivered', 'status_class' => 'completed', 'type' => 'Status Update'],
                    ['reference' => 'NOTIF-2026-702', 'subject' => 'Ramon Villanueva', 'subtitle' => 'Father', 'owner' => 'Mark Villanueva', 'detail' => 'Email', 'schedule' => 'Jul 17, 2026 13:45', 'status' => 'Delivered', 'status_class' => 'completed', 'type' => 'Requirements'],
                    ['reference' => 'NOTIF-2026-703', 'subject' => 'Elena Cruz', 'subtitle' => 'Mother', 'owner' => 'Angela Cruz', 'detail' => 'SMS', 'schedule' => 'Jul 16, 2026 09:20', 'status' => 'Failed', 'status_class' => 'cancelled', 'type' => 'Missing Docs'],
                    ['reference' => 'NOTIF-2026-704', 'subject' => 'Joel Mendoza', 'subtitle' => 'Guardian', 'owner' => 'Carlos Mendoza', 'detail' => 'Email', 'schedule' => 'Jul 15, 2026 15:00', 'status' => 'Queued', 'status_class' => 'pending', 'type' => 'Schedule'],
                    ['reference' => 'NOTIF-2026-705', 'subject' => 'Ana Santos', 'subtitle' => 'Mother', 'owner' => 'Liza Santos', 'detail' => 'SMS + Email', 'schedule' => 'Jul 14, 2026 11:30', 'status' => 'Delivered', 'status_class' => 'completed', 'type' => 'Enrollment Confirmed'],
                ],
            ],
            'enrollment-dashboard' => [
                'title' => 'Enrollment Dashboard',
                'description' => 'Monitor live enrollment counts, bottlenecks, and office workload across the enrollment pipeline.',
                'add_label' => '+ Refresh Dashboard',
                'add_process' => 'report',
                'list_subtitle' => 'View key enrollment pipeline indicators and recent activity.',
                'search_placeholder' => 'Search by metric, office, or status.',
                'stats' => [
                    ['label' => 'Applicants Today', 'value' => '48', 'icon' => 'fa-user-plus', 'tone' => 'blue'],
                    ['label' => 'For Validation', 'value' => '17', 'icon' => 'fa-spinner', 'tone' => 'amber'],
                    ['label' => 'Enrolled', 'value' => '126', 'icon' => 'fa-user-graduate', 'tone' => 'green'],
                    ['label' => 'Waitlisted', 'value' => '23', 'icon' => 'fa-stream', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Report ID',
                    'subject' => 'Pipeline Item',
                    'owner' => 'Owner Office',
                    'detail' => 'Current Count',
                    'schedule' => 'Last Sync',
                ],
                'rows' => [
                    ['reference' => 'ENRD-001', 'subject' => 'Online Pre-registration', 'subtitle' => 'Intake', 'owner' => 'Admissions', 'detail' => '48 new / 12 pending', 'schedule' => 'Jul 18, 2026 17:00', 'status' => 'Active', 'status_class' => 'scheduled', 'type' => 'Intake'],
                    ['reference' => 'ENRD-002', 'subject' => 'Document Verification', 'subtitle' => 'Requirements', 'owner' => 'Admissions', 'detail' => '19 for checking', 'schedule' => 'Jul 18, 2026 17:00', 'status' => 'Busy', 'status_class' => 'pending', 'type' => 'Requirements'],
                    ['reference' => 'ENRD-003', 'subject' => 'ID Generation', 'subtitle' => 'Encoding', 'owner' => 'Registrar Support', 'detail' => '11 ready', 'schedule' => 'Jul 18, 2026 16:55', 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Encoding'],
                    ['reference' => 'ENRD-004', 'subject' => 'Section Assignment', 'subtitle' => 'Placement', 'owner' => 'Enrollment Desk', 'detail' => '8 unassigned', 'schedule' => 'Jul 18, 2026 16:50', 'status' => 'Attention', 'status_class' => 'cancelled', 'type' => 'Placement'],
                    ['reference' => 'ENRD-005', 'subject' => 'Parent Notices', 'subtitle' => 'Communication', 'owner' => 'Admissions', 'detail' => '32 delivered today', 'schedule' => 'Jul 18, 2026 16:45', 'status' => 'Active', 'status_class' => 'scheduled', 'type' => 'Communication'],
                ],
            ],
        ];
    }
}

if (!function_exists('smsRegistrarPageUiCatalog')) {
    function smsRegistrarPageUiCatalog(): array
    {
        $pages = [
            'student-information-system' => ['Student Information System', 'Student Master Record', 'personal profile updates', 'S230000101', 'BSIT · Active', 'For Review'],
            'persona-file-database' => ['Persona File Database', 'Persona File', 'identity documents', 'PF-2026-011', 'Folder A-12', 'Indexed'],
            'guardian-emergency-contact' => ['Guardian & Emergency Contact', 'Guardian Contact', 'emergency contacts', 'Maria Dela Cruz', 'Mother · 0917***', 'Updated'],
            'academic-history' => ['Academic History', 'Academic Record', 'grades and standing', 'TOR Draft 001', '92 units earned', 'Ready'],
            'health-record-log' => ['Health Record Log', 'Health Record', 'clinic and medical logs', 'Clinic Log 001', 'Cleared', 'Posted'],
            'rfid-qr-code-integration' => ['RFID/QR Code Integration', 'Credential Token', 'RFID/QR binding', 'RFID-00041', 'Gate Access', 'Active'],
            'student-id-generation' => ['Student ID Generation', 'Student ID Card', 'print and release IDs', 'IDCARD-2026-01', 'S230000101', 'For Print'],
            'document-requests' => ['Document Requests', 'Document Request', 'certificates and TOR', 'DOC-REQ-088', 'Good Moral', 'Processing'],
            'student-status-tracker' => ['Student Status Tracker', 'Student Status', 'enrollment standing', 'STATUS-221', 'Irregular', 'Monitoring'],
            'digital-file-storage' => ['Digital File Storage', 'Digital File', 'scanned archives', 'FILE-441', 'Transcript Scan', 'Stored'],
            'transcript-management' => ['Transcript Management', 'Transcript', 'official TOR release', 'TOR-2026-019', 'Claim Window B', 'For Release'],
        ];

        $out = [];
        $i = 1;
        foreach ($pages as $slug => $meta) {
            [$title, $subjectLabel, $focus, $sampleA, $sampleDetail, $status] = $meta;
            $out[$slug] = [
                'title' => $title,
                'description' => 'Manage registrar ' . $focus . ' with verification, approval, and release tracking.',
                'add_label' => '+ New ' . $subjectLabel,
                'list_subtitle' => 'View and manage all ' . strtolower($title) . ' records.',
                'search_placeholder' => 'Search by reference, student, or detail.',
                'stats' => [
                    ['label' => 'Total Records', 'value' => '5', 'icon' => 'fa-folder-open', 'tone' => 'blue'],
                    ['label' => 'For Checking', 'value' => '2', 'icon' => 'fa-search', 'tone' => 'amber'],
                    ['label' => 'Released', 'value' => '2', 'icon' => 'fa-check', 'tone' => 'green'],
                    ['label' => 'On Hold', 'value' => '1', 'icon' => 'fa-pause', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Reference No.',
                    'subject' => $subjectLabel,
                    'owner' => 'Handled By',
                    'detail' => 'Detail',
                    'schedule' => 'Updated',
                ],
                'rows' => smsBuildSampleRows('REG', $i, [
                    ['Sofia Reyes', $sampleA, $sampleDetail, $status, 'completed'],
                    ['Mark Villanueva', 'Record ' . ($i + 1), 'Registrar Desk', 'In Progress', 'progress'],
                    ['Angela Cruz', 'Record ' . ($i + 2), 'Window A', 'Pending', 'pending'],
                    ['Carlos Mendoza', 'Record ' . ($i + 3), 'For Compliance', 'On Hold', 'cancelled'],
                    ['Liza Santos', 'Record ' . ($i + 4), 'Released Packet', 'Completed', 'completed'],
                ], $title),
            ];
            $i += 10;
        }
        return $out;
    }
}

if (!function_exists('smsCradPageUiCatalog')) {
    function smsCradPageUiCatalog(): array
    {
        return [
            'proposal-submission-tracking' => [
                'title' => 'Research Proposal Submission & Tracking',
                'description' => 'Track research proposal drafts, adviser assignment, and CRAD approval decisions.',
                'add_label' => '+ New Proposal',
                'list_subtitle' => 'View and manage research proposal submissions.',
                'search_placeholder' => 'Search by proposal no., title, or lead researcher.',
                'stats' => [
                    ['label' => 'Proposals', 'value' => '5', 'icon' => 'fa-flask', 'tone' => 'blue'],
                    ['label' => 'Pending', 'value' => '2', 'icon' => 'fa-clock', 'tone' => 'amber'],
                    ['label' => 'Approved', 'value' => '2', 'icon' => 'fa-check', 'tone' => 'green'],
                    ['label' => 'Returned', 'value' => '1', 'icon' => 'fa-undo', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Proposal No.',
                    'subject' => 'Research Title',
                    'owner' => 'Lead Researcher',
                    'detail' => 'College / Dept',
                    'schedule' => 'Submitted',
                ],
                'rows' => [
                    ['reference' => 'RES-2026-401', 'subject' => 'Smart Campus Attendance via RFID & AI', 'subtitle' => 'Proposal', 'owner' => 'Dr. Hank Pym', 'detail' => 'CCS', 'schedule' => 'Jul 18, 2026 09:30', 'status' => 'Panel Assigned', 'status_class' => 'scheduled', 'type' => 'Proposal'],
                    ['reference' => 'RES-2026-402', 'subject' => 'Gamified Curriculum Impact Study', 'subtitle' => 'Proposal', 'owner' => 'Prof. Jean Grey', 'detail' => 'Education', 'schedule' => 'Jul 17, 2026 14:15', 'status' => 'Pending', 'status_class' => 'pending', 'type' => 'Proposal'],
                    ['reference' => 'RES-2026-403', 'subject' => 'Micro-Enterprise Marketing Adaptability', 'subtitle' => 'Proposal', 'owner' => 'Prof. Clara Reyes', 'detail' => 'Business', 'schedule' => 'Jul 16, 2026 10:00', 'status' => 'Approved', 'status_class' => 'completed', 'type' => 'Proposal'],
                    ['reference' => 'RES-2026-404', 'subject' => 'Community Mental Health Literacy', 'subtitle' => 'Proposal', 'owner' => 'Dr. Ana Mendoza', 'detail' => 'Education', 'schedule' => 'Jul 15, 2026 11:45', 'status' => 'In Progress', 'status_class' => 'progress', 'type' => 'Proposal'],
                    ['reference' => 'RES-2026-405', 'subject' => 'Solid Waste Segregation Awareness', 'subtitle' => 'Proposal', 'owner' => 'Prof. Joel Cruz', 'detail' => 'Criminology', 'schedule' => 'Jul 14, 2026 08:20', 'status' => 'Returned', 'status_class' => 'cancelled', 'type' => 'Proposal'],
                ],
            ],
            'register-proposal' => [
                'title' => 'Register Proposal',
                'description' => 'Register a new research proposal and generate its official proposal number.',
                'add_label' => '+ Register Proposal',
                'list_subtitle' => 'Register new research proposals with auto-generated proposal numbers.',
                'search_placeholder' => 'Search by proposal no., title, or lead researcher.',
                'stats' => [
                    ['label' => 'Proposals', 'value' => '5', 'icon' => 'fa-file-signature', 'tone' => 'blue'],
                    ['label' => 'Pending', 'value' => '2', 'icon' => 'fa-clock', 'tone' => 'amber'],
                    ['label' => 'Approved', 'value' => '2', 'icon' => 'fa-check', 'tone' => 'green'],
                    ['label' => 'Returned', 'value' => '1', 'icon' => 'fa-undo', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Proposal No.',
                    'subject' => 'Research Title',
                    'owner' => 'Lead Researcher',
                    'detail' => 'College / Dept',
                    'schedule' => 'Submitted',
                ],
                'rows' => [
                    ['reference' => 'CRD-2026-00001', 'subject' => 'Smart Campus Attendance via RFID & AI', 'subtitle' => 'Proposal', 'owner' => 'Dr. Hank Pym', 'detail' => 'CCS', 'schedule' => 'Jul 18, 2026 09:30', 'status' => 'Submitted', 'status_class' => 'pending', 'type' => 'Proposal'],
                    ['reference' => 'CRD-2026-00002', 'subject' => 'Gamified Curriculum Impact Study', 'subtitle' => 'Proposal', 'owner' => 'Prof. Jean Grey', 'detail' => 'Education', 'schedule' => 'Jul 17, 2026 14:15', 'status' => 'Submitted', 'status_class' => 'pending', 'type' => 'Proposal'],
                    ['reference' => 'CRD-2026-00003', 'subject' => 'Micro-Enterprise Marketing Adaptability', 'subtitle' => 'Proposal', 'owner' => 'Prof. Clara Reyes', 'detail' => 'Business', 'schedule' => 'Jul 16, 2026 10:00', 'status' => 'Approved', 'status_class' => 'completed', 'type' => 'Proposal'],
                    ['reference' => 'CRD-2026-00004', 'subject' => 'Community Mental Health Literacy', 'subtitle' => 'Proposal', 'owner' => 'Dr. Ana Mendoza', 'detail' => 'Education', 'schedule' => 'Jul 15, 2026 11:45', 'status' => 'In Progress', 'status_class' => 'progress', 'type' => 'Proposal'],
                    ['reference' => 'CRD-2026-00005', 'subject' => 'Solid Waste Segregation Awareness', 'subtitle' => 'Proposal', 'owner' => 'Prof. Joel Cruz', 'detail' => 'Criminology', 'schedule' => 'Jul 14, 2026 08:20', 'status' => 'Returned', 'status_class' => 'cancelled', 'type' => 'Proposal'],
                ],
            ],
            'research-group-number' => [
                'title' => 'Research Group Number',
                'description' => 'Register and manage research group numbers assigned to student research teams.',
                'add_label' => '+ New Research Group',
                'list_subtitle' => 'View and manage assigned research group numbers.',
                'search_placeholder' => 'Search by group no., title, adviser, or college.',
                'stats' => [
                    ['label' => 'Groups', 'value' => '0', 'icon' => 'fa-users', 'tone' => 'blue'],
                    ['label' => 'Active', 'value' => '0', 'icon' => 'fa-check-circle', 'tone' => 'green'],
                    ['label' => 'Pending', 'value' => '0', 'icon' => 'fa-clock', 'tone' => 'amber'],
                    ['label' => 'Completed', 'value' => '0', 'icon' => 'fa-flag-checkered', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref'      => 'Group No.',
                    'subject'  => 'Research Title',
                    'owner'    => 'Adviser',
                    'detail'   => 'College / Dept',
                    'schedule' => 'Date Assigned',
                ],
                'rows' => [],
            ],
            'adviser-panel-assignment' => [
                'title' => 'Record Adviser/Panel Assignment',
                'description' => 'Match approved research groups with faculty advisers and defense panel members by expertise, load, and college fit.',
                'add_label' => '+ New Assignment',
                'list_subtitle' => 'View and manage adviser assignments for research groups.',
                'search_placeholder' => 'Search by assignment no., group, adviser, or panel member.',
                'stats' => [
                    ['label' => 'Requests', 'value' => '6', 'icon' => 'fa-user-clock', 'tone' => 'blue'],
                    ['label' => 'For Assignment', 'value' => '2', 'icon' => 'fa-clock', 'tone' => 'amber'],
                    ['label' => 'Assigned', 'value' => '3', 'icon' => 'fa-user-check', 'tone' => 'green'],
                    ['label' => 'Under Review', 'value' => '1', 'icon' => 'fa-search', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Assignment No.',
                    'subject' => 'Research Group / Title',
                    'owner' => 'Adviser / Panel',
                    'detail' => 'College',
                    'schedule' => 'Updated',
                ],
                'rows' => [
                    ['reference' => 'ADV-2026-016', 'subject' => 'AI-Based Enrollment Prediction Model', 'subtitle' => 'Adviser & Panel Request', 'owner' => 'Dr. Liza M. Torres (Adviser) · Dr. Art C. Lim (Panel)', 'detail' => 'CCS', 'schedule' => 'Jul 18, 2026', 'status' => 'For Assignment', 'status_class' => 'pending', 'type' => 'Adviser & Panel'],
                    ['reference' => 'ADV-2026-015', 'subject' => 'IoT Flood Monitoring — CCS Group A', 'subtitle' => 'Adviser & Panel Request', 'owner' => 'Dr. Roberto M. Santos (Adviser) · Prof. Nina G. Cruz (Panel)', 'detail' => 'CCS', 'schedule' => 'Jul 16, 2026', 'status' => 'Assigned', 'status_class' => 'completed', 'type' => 'Adviser & Panel'],
                    ['reference' => 'ADV-2026-014', 'subject' => 'Micro-Enterprise Marketing Adaptability', 'subtitle' => 'Adviser & Panel Request', 'owner' => 'Prof. Clara T. Reyes (Adviser) · Dr. Jose B. Tan (Panel)', 'detail' => 'CBA', 'schedule' => 'Jul 14, 2026', 'status' => 'Assigned', 'status_class' => 'completed', 'type' => 'Adviser & Panel'],
                    ['reference' => 'ADV-2026-013', 'subject' => 'Community Mental Health Literacy Study', 'subtitle' => 'Adviser & Panel Request', 'owner' => 'Dr. Ana L. Mendoza (Adviser) · Prof. Rhea D. Santos (Panel)', 'detail' => 'COE', 'schedule' => 'Jul 12, 2026', 'status' => 'Under Review', 'status_class' => 'progress', 'type' => 'Adviser & Panel'],
                ],
            ],
            'research-grants-funding-assistance' => [
                'title' => 'Research Grants & Funding Assistance',
                'description' => 'Evaluate research grant applications, budget requests, and funding release status.',
                'add_label' => '+ New Grant Application',
                'list_subtitle' => 'View and manage research grant and funding requests.',
                'search_placeholder' => 'Search by grant no., study, or amount.',
                'stats' => [
                    ['label' => 'Applications', 'value' => '4', 'icon' => 'fa-file-invoice-dollar', 'tone' => 'blue'],
                    ['label' => 'Under Evaluation', 'value' => '1', 'icon' => 'fa-search-dollar', 'tone' => 'amber'],
                    ['label' => 'Approved', 'value' => '2', 'icon' => 'fa-hand-holding-usd', 'tone' => 'green'],
                    ['label' => 'Denied', 'value' => '1', 'icon' => 'fa-ban', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Grant No.',
                    'subject' => 'Grant / Study',
                    'owner' => 'Requested Amount',
                    'detail' => 'Grant Type',
                    'schedule' => 'Updated',
                ],
                'rows' => [
                    ['reference' => 'GRN-2026-008', 'subject' => 'IoT Sensor Kits for Flood Study', 'subtitle' => 'Materials', 'owner' => '₱18,500', 'detail' => 'Equipment Support', 'schedule' => 'Jul 17, 2026', 'status' => 'Under Evaluation', 'status_class' => 'pending', 'type' => 'Equipment'],
                    ['reference' => 'GRN-2026-007', 'subject' => 'Community Survey Printing Support', 'subtitle' => 'Fieldwork', 'owner' => '₱6,200', 'detail' => 'Survey Assistance', 'schedule' => 'Jul 15, 2026', 'status' => 'Approved', 'status_class' => 'completed', 'type' => 'Survey'],
                    ['reference' => 'GRN-2026-006', 'subject' => 'Conference Presentation Assistance', 'subtitle' => 'Publication', 'owner' => '₱12,000', 'detail' => 'Conference Support', 'schedule' => 'Jul 13, 2026', 'status' => 'For Review', 'status_class' => 'progress', 'type' => 'Conference'],
                    ['reference' => 'GRN-2026-005', 'subject' => 'Prototype Fabrication Support', 'subtitle' => 'Prototype', 'owner' => '₱25,000', 'detail' => 'Prototype Support', 'schedule' => 'Jul 10, 2026', 'status' => 'Denied', 'status_class' => 'cancelled', 'type' => 'Prototype'],
                ],
            ],
            'research-defense-scheduling' => [
                'title' => 'Research Defense Scheduling',
                'description' => 'Schedule research defense hearings, assign panels, manage room availability, and track defense results.',
                'add_label' => '+ New Defense Schedule',
                'list_subtitle' => 'View and manage research defense schedules and outcomes.',
                'search_placeholder' => 'Search by defense no., research group, or panel.',
                'stats' => [
                    ['label' => 'Scheduled Defenses', 'value' => '0', 'icon' => 'fa-calendar-check', 'tone' => 'blue'],
                    ['label' => 'Today', 'value' => '0', 'icon' => 'fa-clock', 'tone' => 'amber'],
                    ['label' => 'Completed', 'value' => '0', 'icon' => 'fa-check-circle', 'tone' => 'green'],
                    ['label' => 'Postponed', 'value' => '0', 'icon' => 'fa-calendar-times', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Defense No.',
                    'subject' => 'Research Title / Group',
                    'owner' => 'Panel Chair',
                    'detail' => 'Room / Venue',
                    'schedule' => 'Defense Date',
                ],
                'rows' => [],
            ],
            'documentation-publication-management' => [
                'title' => 'Documentation & Publication Management',
                'description' => 'Track research documentation packets, publication clearances, and release readiness.',
                'add_label' => '+ New Documentation',
                'list_subtitle' => 'View and manage research documentation and publication records.',
                'search_placeholder' => 'Search by packet no., title, or venue.',
                'stats' => [
                    ['label' => 'Packets', 'value' => '4', 'icon' => 'fa-book', 'tone' => 'blue'],
                    ['label' => 'For Review', 'value' => '1', 'icon' => 'fa-clock', 'tone' => 'amber'],
                    ['label' => 'Cleared', 'value' => '2', 'icon' => 'fa-check', 'tone' => 'green'],
                    ['label' => 'Returned', 'value' => '1', 'icon' => 'fa-undo', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Packet No.',
                    'subject' => 'Manuscript / Title',
                    'owner' => 'Author',
                    'detail' => 'Target Venue',
                    'schedule' => 'Updated',
                ],
                'rows' => smsBuildSampleRows('PUB', 20, [
                    ['IoT Flood Monitoring Final Paper', 'CCS Group A', 'Institutional Journal', 'Cleared', 'completed'],
                    ['Marketing Adaptability Manuscript', 'CBA Group B', 'Conference Proceedings', 'For Review', 'pending'],
                    ['Mental Health Literacy Article', 'COE Group C', 'Peer Review Journal', 'Cleared', 'completed'],
                    ['Waste Segregation Awareness Paper', 'Crim Group D', 'Campus Research Digest', 'Returned', 'cancelled'],
                ], 'Publication'),
            ],
            'research-analytics-reporting' => [
                'title' => 'Research Analytics & Reporting',
                'description' => 'Monitor research productivity, proposal acceptance rates, grants disbursed, defense outcomes, and publication counts.',
                'add_label' => '+ Generate Report',
                'add_process' => 'report',
                'list_subtitle' => 'View research performance analytics and generate institutional reports.',
                'search_placeholder' => 'Search by metric, college, or report.',
                'stats' => [
                    ['label' => 'Total Groups', 'value' => '1', 'icon' => 'fa-users', 'tone' => 'blue'],
                    ['label' => 'Approved Titles', 'value' => '1', 'icon' => 'fa-check-circle', 'tone' => 'green'],
                    ['label' => 'Final Defenses Completed', 'value' => '1', 'icon' => 'fa-gavel', 'tone' => 'amber'],
                    ['label' => 'Published', 'value' => '1', 'icon' => 'fa-book-open', 'tone' => 'purple'],
                ],
                'columns' => [
                    'ref' => 'Report ID',
                    'subject' => 'Research Metric',
                    'owner' => 'College / Office',
                    'detail' => 'Current Value',
                    'schedule' => 'As of',
                ],
                'rows' => [
                    ['reference' => 'RAN-001', 'subject' => 'Capstone Analytics', 'subtitle' => 'Overall capstone performance', 'owner' => 'CRAD', 'detail' => 'Total groups, approvals, defenses, and publication status', 'schedule' => 'Aug 28, 2026', 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Capstone Analytics'],
                    ['reference' => 'RAN-002', 'subject' => 'Progress Reports', 'subtitle' => 'Research development', 'owner' => 'CRAD', 'detail' => 'Milestone and research group progress', 'schedule' => 'Aug 28, 2026', 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Progress Reports'],
                    ['reference' => 'RAN-003', 'subject' => 'Defense Reports', 'subtitle' => 'Pre-Oral and Final Defense', 'owner' => 'CRAD', 'detail' => 'Schedules, panel evaluations, and defense outcomes', 'schedule' => 'Aug 28, 2026', 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Defense Reports'],
                    ['reference' => 'RAN-004', 'subject' => 'Adviser Reports', 'subtitle' => 'Adviser assignments', 'owner' => 'CRAD', 'detail' => 'Assigned groups and adviser monitoring', 'schedule' => 'Aug 28, 2026', 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Adviser Reports'],
                    ['reference' => 'RAN-005', 'subject' => 'Completion Reports', 'subtitle' => 'Final manuscript approval', 'owner' => 'CRAD', 'detail' => 'Completed research groups and approval status', 'schedule' => 'Aug 28, 2026', 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Completion Reports'],
                    ['reference' => 'RAN-006', 'subject' => 'Publication Reports', 'subtitle' => 'Publication and repository', 'owner' => 'CRAD', 'detail' => 'Draft, For Publication, Published, and Archived records', 'schedule' => 'Aug 28, 2026', 'status' => 'On Track', 'status_class' => 'completed', 'type' => 'Publication Reports'],
                ],
            ],
        ];
    }
}
