<?php
/**
 * SMS 2 - Documentation & Publication Management
 * Module: CRAD
 */
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/audit.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/final-phase-helpers.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
requireAuth();
if (!smsRoleAllowedForModule(['crad_officer', 'research_coordinator'], 'crad')) { http_response_code(403); exit('Forbidden'); }
$crad = cradDb();
finalPhaseEnsureSchema($crad);
$publicationMessage = '';
$publicationError = '';
$validTitleApprovalSql = cradValidTitleApprovalWhereSql('ta');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) { $publicationError = 'Security check failed.'; }
    else {
        if (($_POST['publication_action'] ?? '') === 'create') {
            $groupId = (int) ($_POST['research_group_id'] ?? 0);
            $groupStmt = $crad->prepare("SELECT rg.research_title, rg.group_name FROM research_groups rg INNER JOIN title_approvals ta ON ta.id = rg.title_approval_id AND {$validTitleApprovalSql} INNER JOIN final_manuscript_approvals fma ON fma.research_group_id = rg.id AND fma.status = 'Approved' WHERE rg.id = ? LIMIT 1");
            $groupStmt->execute([$groupId]);
            $group = $groupStmt->fetch(PDO::FETCH_ASSOC);
            if (!$group || !fpIsFinalManuscriptApproved($crad, $groupId)) {
                $publicationError = 'Only an approved final manuscript can create a publication record.';
            } else {
                $check = $crad->prepare('SELECT id FROM publications WHERE research_group_id = ? LIMIT 1');
                $check->execute([$groupId]);
                if ($check->fetch()) {
                    $publicationError = 'A publication record already exists for this group.';
                } else {
                    $stmt = $crad->prepare("INSERT INTO publications (research_group_id, title, authors, status, created_by_user, created_by_name) VALUES (?, ?, ?, 'Draft', ?, ?)");
                    $stmt->execute([$groupId, $group['research_title'], $group['group_name'], (int) ($_SESSION['user_id'] ?? 0), (string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? '')]);
                    logActivity('create', 'Created publication record for research group #' . $groupId, 'crad');
                    $publicationMessage = 'Publication record created.';
                }
            }
        }
        if (($_POST['publication_action'] ?? '') !== 'create') {
        $publicationId = (int) ($_POST['publication_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? 'Draft');
        $publicationCheck = $crad->prepare(
            "SELECT p.research_group_id, fma.status AS approval_status
             FROM publications p
             INNER JOIN research_groups rg ON rg.id = p.research_group_id
             INNER JOIN title_approvals ta ON ta.id = rg.title_approval_id AND {$validTitleApprovalSql}
             LEFT JOIN final_manuscript_approvals fma ON fma.research_group_id = p.research_group_id
             WHERE p.id = ? LIMIT 1"
        );
        $publicationCheck->execute([$publicationId]);
        $publicationRecord = $publicationCheck->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$publicationRecord) {
            $publicationError = 'Publication record not found or title approval is no longer valid.';
        } elseif ($status !== 'Draft' && (string) ($publicationRecord['approval_status'] ?? '') !== 'Approved') {
            $publicationError = 'Only a group with an approved final manuscript can enter publication processing.';
        }
        if (($_POST['publication_action'] ?? '') === 'create') {
            $status = 'Draft';
        } elseif ($publicationError === '' && !in_array($status, ['Draft', 'For Publication', 'Published', 'Archived'], true)) { $publicationError = 'Invalid publication status.'; }
        elseif ($publicationError === '') {
            $stmt = $crad->prepare("UPDATE publications SET publication_outlet = ?, publication_date = NULLIF(?, ''), doi_link = ?, status = ?, notes = ? WHERE id = ?");
            $stmt->execute([trim((string) ($_POST['publication_outlet'] ?? '')), trim((string) ($_POST['publication_date'] ?? '')), trim((string) ($_POST['doi_link'] ?? '')), $status, trim((string) ($_POST['notes'] ?? '')), $publicationId]);
            logActivity('update', 'Updated publication record #' . $publicationId, 'crad');
            $publicationMessage = 'Publication record updated.';
        }
        }
    }
}
$publicationRows = $crad->query("SELECT p.*, rg.group_number, rg.group_name FROM publications p INNER JOIN research_groups rg ON rg.id = p.research_group_id INNER JOIN title_approvals ta ON ta.id = rg.title_approval_id AND {$validTitleApprovalSql} ORDER BY p.updated_at DESC, p.id DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
$publicationMetrics = ['total' => count($publicationRows), 'for_publication' => 0, 'published' => 0, 'archived' => 0];
foreach ($publicationRows as $publicationRow) { if ($publicationRow['status'] === 'For Publication') $publicationMetrics['for_publication']++; if ($publicationRow['status'] === 'Published') $publicationMetrics['published']++; if ($publicationRow['status'] === 'Archived') $publicationMetrics['archived']++; }
$breadcrumbs = [['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'], ['label' => 'Documentation & Publication Management', 'url' => null]];
require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<div class="glass-dashboard"><div class="glass-board"><div class="row g-3 mb-4"><div class="col-md-3"><div class="glass-panel p-3 d-flex align-items-center gap-3"><div class="mpl-stat-icon blue"><?= smsIcon('folder-open') ?></div><div><small>Total Records</small><h3 class="mb-0"><?= $publicationMetrics['total'] ?></h3></div></div></div><div class="col-md-3"><div class="glass-panel p-3 d-flex align-items-center gap-3"><div class="mpl-stat-icon amber"><?= smsIcon('clock') ?></div><div><small>For Publication</small><h3 class="mb-0"><?= $publicationMetrics['for_publication'] ?></h3></div></div></div><div class="col-md-3"><div class="glass-panel p-3 d-flex align-items-center gap-3"><div class="mpl-stat-icon green"><?= smsIcon('check-circle') ?></div><div><small>Published</small><h3 class="mb-0"><?= $publicationMetrics['published'] ?></h3></div></div></div><div class="col-md-3"><div class="glass-panel p-3 d-flex align-items-center gap-3"><div class="mpl-stat-icon purple"><?= smsIcon('archive') ?></div><div><small>Archived</small><h3 class="mb-0"><?= $publicationMetrics['archived'] ?></h3></div></div></div></div><div class="glass-panel"><div class="glass-panel-body"><h5 class="glass-panel-title">Publication Records</h5><?php if ($publicationMessage): ?><div class="alert alert-success"><?= e($publicationMessage) ?></div><?php endif; ?><?php if ($publicationError): ?><div class="alert alert-danger"><?= e($publicationError) ?></div><?php endif; ?><?php if (!$publicationRows): ?><p class="text-muted">No approved manuscripts have created publication records yet.</p><?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Group</th><th>Title</th><th>Outlet</th><th>Status</th><th>Updated</th><th>Manage</th></tr></thead><tbody><?php foreach ($publicationRows as $row): ?><tr><td><?= e((string) ($row['group_number'] ?? '')) ?></td><td><?= e((string) $row['title']) ?></td><td><?= e((string) $row['publication_outlet']) ?></td><td><?= e((string) $row['status']) ?></td><td><?= e((string) $row['updated_at']) ?></td><td><details><summary class="btn btn-sm btn-outline-primary">Edit</summary><form method="post" class="mt-2"><?= csrfField() ?><input type="hidden" name="publication_id" value="<?= (int) $row['id'] ?>"><input class="form-control form-control-sm mb-2" name="publication_outlet" value="<?= e((string) $row['publication_outlet']) ?>" placeholder="Publication outlet"><input class="form-control form-control-sm mb-2" name="publication_date" type="date" value="<?= e((string) $row['publication_date']) ?>"><input class="form-control form-control-sm mb-2" name="doi_link" value="<?= e((string) $row['doi_link']) ?>" placeholder="DOI or link"><select class="form-select form-select-sm mb-2" name="status"><?php foreach (['Draft','For Publication','Published','Archived'] as $option): ?><option <?= $row['status'] === $option ? 'selected' : '' ?>><?= $option ?></option><?php endforeach; ?></select><textarea class="form-control form-control-sm mb-2" name="notes"><?= e((string) ($row['notes'] ?? '')) ?></textarea><button class="btn btn-primary btn-sm">Save</button></form></details></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div></div></div></div>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; exit;

$pageTitle    = 'Documentation & Publication Management';
$activeModule = 'crad';
$activePage   = 'documentation-publication-management';
$breadcrumbs  = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Documentation & Publication Management', 'url' => null],
];

$cradProcess = [
    'kicker' => 'CRAD Officer · Publication Workflow',
    'description' => 'Manage research manuscripts, institutional documentation, ethics clearance attachments, and publication endorsements.',
    'metrics' => [
        ['label' => 'For Formatting', 'value' => '6', 'icon' => 'fa-file-alt', 'tone' => 'blue'],
        ['label' => 'Ethics / Similarity', 'value' => '3', 'icon' => 'fa-shield-alt', 'tone' => 'amber'],
        ['label' => 'Endorsed to Publish', 'value' => '11', 'icon' => 'fa-book-open', 'tone' => 'green'],
        ['label' => 'Avg. Turnaround', 'value' => '4 days', 'icon' => 'fa-clock', 'tone' => 'purple'],
    ],
    'steps' => [
        ['Receive Manuscript / Document', 'Log research paper, abstract, poster, or institutional research document.'],
        ['Check Format & Completeness', 'Validate template, authorship, abstract, keywords, and required attachments.'],
        ['Run Ethics / Similarity Review', 'Confirm ethics clearance and similarity screening before endorsement.'],
        ['Endorse for Publication', 'Approve campus journal, conference, or repository publication routing.'],
    ],
    'columns' => ['Reference', 'Document / Manuscript', 'Target Outlet', 'Status', 'Updated'],
    'fields' => ['reference', 'title', 'owner', 'status', 'updated'],
    'records' => [
        [
            'reference' => 'PUB-2026-021',
            'title' => 'Flood Monitoring Early Warning System',
            'owner' => 'BCP Research Journal',
            'status' => 'For Review',
            'status_class' => 'pending',
            'updated' => 'Jul 18, 2026',
        ],
        [
            'reference' => 'PUB-2026-020',
            'title' => 'Micro-Enterprise Marketing Adaptability',
            'owner' => 'National Research Forum',
            'status' => 'Ethics Check',
            'status_class' => 'evaluation',
            'updated' => 'Jul 16, 2026',
        ],
        [
            'reference' => 'PUB-2026-019',
            'title' => 'Waste Segregation Awareness Output',
            'owner' => 'Campus Poster Exhibit',
            'status' => 'Published',
            'status_class' => 'published',
            'updated' => 'Jul 11, 2026',
        ],
        [
            'reference' => 'PUB-2026-018',
            'title' => 'Mental Health Literacy Baseline Paper',
            'owner' => 'College Research Colloquium',
            'status' => 'Endorsed',
            'status_class' => 'approved',
            'updated' => 'Jul 9, 2026',
        ],
    ],
    'actions' => [
        ['label' => 'New Document Entry', 'process' => 'new', 'icon' => 'fa-plus', 'class' => 'primary'],
        ['label' => 'Validate Format', 'process' => 'validate', 'icon' => 'fa-spell-check', 'class' => 'ghost'],
        ['label' => 'Endorse Publication', 'process' => 'approve', 'icon' => 'fa-stamp', 'class' => 'ghost'],
        ['label' => 'Publication Report', 'process' => 'report', 'icon' => 'fa-file-export', 'class' => 'ghost'],
    ],
    'form' => [
        ['label' => 'Document Reference', 'type' => 'text', 'name' => 'reference', 'placeholder' => 'PUB-2026-00X'],
        ['label' => 'Title', 'type' => 'text', 'name' => 'title', 'placeholder' => 'Manuscript or document title'],
        ['label' => 'Document Type', 'type' => 'select', 'name' => 'doc_type', 'options' => [
            'Full Research Paper',
            'Abstract / Extended Abstract',
            'Poster / Infographic',
            'Institutional Research Report',
        ]],
        ['label' => 'Target Outlet', 'type' => 'select', 'name' => 'outlet', 'options' => [
            'BCP Research Journal',
            'College Research Colloquium',
            'National Research Forum',
            'Campus Poster Exhibit',
        ]],
        ['label' => 'Authors / Proponents', 'type' => 'text', 'name' => 'authors', 'placeholder' => 'Comma-separated names'],
        ['label' => 'Reviewer Remarks', 'type' => 'textarea', 'name' => 'remarks', 'placeholder' => 'Formatting notes, ethics remarks, endorsement conditions...'],
    ],
    'notice' => 'No manuscript is endorsed for external publication without completed ethics documentation and acceptable similarity screening results.',
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>
<?php require_once ROOT_PATH . '/includes/crad-module-process.php'; ?>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
