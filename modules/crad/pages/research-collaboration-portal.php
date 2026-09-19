<?php
/**
 * SMS 2 - Research Collaboration Portal
 * Module: CRAD
 */
require_once __DIR__ . '/../../../config/config.php';

$pageTitle    = 'Research Collaboration Portal';
$activeModule = 'crad';
$activePage   = 'research-collaboration-portal';
$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Research Collaboration Portal', 'url' => null],
];

$cradProcess = [
    'kicker' => 'CRAD Officer · Collaboration Workflow',
    'description' => 'Coordinate internal and external research partnerships, MoUs, joint projects, and milestone tracking.',
    'metrics' => [
        ['label' => 'Open Collaborations', 'value' => '8', 'icon' => 'fa-handshake', 'tone' => 'blue'],
        ['label' => 'Pending MoU', 'value' => '3', 'icon' => 'fa-file-signature', 'tone' => 'amber'],
        ['label' => 'Active Partners', 'value' => '14', 'icon' => 'fa-building', 'tone' => 'green'],
        ['label' => 'Milestones Due', 'value' => '5', 'icon' => 'fa-flag', 'tone' => 'purple'],
    ],
    'steps' => [
        ['Create Collaboration Request', 'Register partner institution, project scope, and lead researchers.'],
        ['Review Partnership Fit', 'Check agenda alignment, SDG focus, roles, and resource commitments.'],
        ['Approve MoU / Agreement', 'Route endorsement, signing, and official partnership documentation.'],
        ['Track Milestones', 'Monitor joint outputs, meetings, deliverables, and project closure.'],
    ],
    'columns' => ['Reference', 'Collaboration Project', 'Partner', 'Status', 'Updated'],
    'fields' => ['reference', 'title', 'owner', 'status', 'updated'],
    'records' => [
        [
            'reference' => 'COL-2026-009',
            'title' => 'Barangay Disaster Resilience Mapping',
            'owner' => 'Brgy. Kaligayahan LGU',
            'status' => 'Active',
            'status_class' => 'active',
            'updated' => 'Jul 17, 2026',
        ],
        [
            'reference' => 'COL-2026-008',
            'title' => 'STEM Mentoring Exchange',
            'owner' => 'Partner Senior High School',
            'status' => 'Pending MoU',
            'status_class' => 'pending',
            'updated' => 'Jul 15, 2026',
        ],
        [
            'reference' => 'COL-2026-007',
            'title' => 'Industry Capstone Alignment Talks',
            'owner' => 'Local Tech Cooperative',
            'status' => 'Under Review',
            'status_class' => 'review',
            'updated' => 'Jul 12, 2026',
        ],
        [
            'reference' => 'COL-2026-006',
            'title' => 'Community Health Literacy Drive',
            'owner' => 'City Health Office',
            'status' => 'Open',
            'status_class' => 'open',
            'updated' => 'Jul 8, 2026',
        ],
    ],
    'actions' => [
        ['label' => 'New Collaboration', 'process' => 'new', 'icon' => 'fa-plus', 'class' => 'primary'],
        ['label' => 'Review Partnership', 'process' => 'validate', 'icon' => 'fa-search', 'class' => 'ghost'],
        ['label' => 'Approve MoU', 'process' => 'approve', 'icon' => 'fa-file-signature', 'class' => 'ghost'],
        ['label' => 'Collaboration Report', 'process' => 'report', 'icon' => 'fa-file-export', 'class' => 'ghost'],
    ],
    'form' => [
        ['label' => 'Collaboration Reference', 'type' => 'text', 'name' => 'reference', 'placeholder' => 'COL-2026-00X'],
        ['label' => 'Project Title', 'type' => 'text', 'name' => 'title', 'placeholder' => 'Joint project title'],
        ['label' => 'Partner Organization', 'type' => 'text', 'name' => 'partner', 'placeholder' => 'School / LGU / Industry'],
        ['label' => 'Collaboration Type', 'type' => 'select', 'name' => 'type', 'options' => [
            'Internal Inter-College Collaboration',
            'External Academic Partnership',
            'LGU / Community Partnership',
            'Industry Research Partnership',
        ]],
        ['label' => 'Lead Researcher / Office', 'type' => 'text', 'name' => 'lead', 'placeholder' => 'Faculty or CRAD focal'],
        ['label' => 'Scope & Milestone Notes', 'type' => 'textarea', 'name' => 'notes', 'placeholder' => 'Objectives, deliverables, timeline...'],
    ],
    'notice' => 'External collaborations require documented partnership instruments before data sharing, fieldwork, or joint publication activities begin.',
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>
<?php require_once ROOT_PATH . '/includes/crad-module-process.php'; ?>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
