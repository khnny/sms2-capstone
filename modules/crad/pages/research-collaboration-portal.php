<?php
/**
 * SMS 2 - Research Collaboration Portal
 * Module: CRAD
 * Demo shell only — no live collaboration ledger yet.
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
    'kicker' => 'CRAD Officer · Collaboration Workflow (demo shell)',
    'description' => 'Sample UI only — metrics and records below are placeholders until a live collaboration table is wired.',
    'metrics' => [
        ['label' => 'Open Collaborations', 'value' => '—', 'icon' => 'fa-handshake', 'tone' => 'blue'],
        ['label' => 'Pending MoU', 'value' => '—', 'icon' => 'fa-file-signature', 'tone' => 'amber'],
        ['label' => 'Active Partners', 'value' => '—', 'icon' => 'fa-building', 'tone' => 'green'],
        ['label' => 'Milestones Due', 'value' => '—', 'icon' => 'fa-flag', 'tone' => 'purple'],
    ],
    'steps' => [
        ['Create Collaboration Request', 'Register partner institution, project scope, and lead researchers.'],
        ['Review Partnership Fit', 'Check agenda alignment, SDG focus, roles, and resource commitments.'],
        ['Approve MoU / Agreement', 'Route endorsement, signing, and official partnership documentation.'],
        ['Track Milestones', 'Monitor joint outputs, meetings, deliverables, and project closure.'],
    ],
    'columns' => ['Reference', 'Collaboration Project', 'Partner', 'Status', 'Updated'],
    'fields' => ['reference', 'title', 'owner', 'status', 'updated'],
    'records' => [],
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
    'notice' => 'This page is a process shell. Forms do not persist to a collaboration database yet.',
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
require_once ROOT_PATH . '/includes/crad-module-process.php';
require_once __DIR__ . '/../../../includes/layout-end.php';
