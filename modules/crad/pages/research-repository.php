<?php
/**
 * SMS 2 - Research Repository
 * Module: CRAD
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/final-phase-helpers.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';

requireAuth();
if (!smsRoleAllowedForModule(['crad_officer', 'research_coordinator'], 'crad')) {
    http_response_code(403);
    exit('Forbidden');
}

$pageTitle = 'Research Repository';
$activeModule = 'crad';
$activePage = 'research-repository';
$allowedRepositoryStatuses = ['For Publication', 'Published', 'Archived'];
$requestedStatus = trim((string) ($_GET['status'] ?? ''));
if (!in_array($requestedStatus, $allowedRepositoryStatuses, true)) {
    $requestedStatus = '';
}
$selectedPublicationId = (int) ($_GET['id'] ?? 0);
$crad = cradDb();
if (!$crad instanceof PDO) {
    http_response_code(503);
    exit('CRAD database unavailable.');
}
finalPhaseEnsureSchema($crad);
$validTitleApprovalSql = cradValidTitleApprovalWhereSql('ta');

function repositoryStatusClass(string $status): string
{
    return match ($status) {
        'For Publication' => 'repo-status--for-publication',
        'Published' => 'repo-status--published',
        'Archived' => 'repo-status--archived',
        default => 'repo-status--muted',
    };
}

function repositoryRecordUrl(int $id): string
{
    return BASE_URL . '/modules/crad/pages/research-repository.php?id=' . $id;
}

function repositoryRows(PDO $crad, string $validTitleApprovalSql, string $status = ''): array
{
    $params = [];
    $statusSql = '';
    if ($status !== '') {
        $statusSql = ' AND p.status = ?';
        $params[] = $status;
    }

    $stmt = $crad->prepare(
        "SELECT
            p.id,
            p.research_group_id,
            p.title,
            p.authors,
            p.publication_outlet,
            p.publication_date,
            p.doi_link,
            p.status,
            p.notes,
            p.created_by_name,
            p.created_at,
            p.updated_at,
            rg.group_number,
            rg.group_name,
            rg.academic_year,
            rg.college_dept,
            fma.status AS final_approval_status,
            fma.approved_at AS final_approved_at
         FROM publications p
         INNER JOIN research_groups rg ON rg.id = p.research_group_id
         INNER JOIN title_approvals ta ON ta.id = rg.title_approval_id AND {$validTitleApprovalSql}
         LEFT JOIN final_manuscript_approvals fma ON fma.research_group_id = p.research_group_id
         WHERE p.status IN ('For Publication', 'Published', 'Archived')
           {$statusSql}
         ORDER BY p.updated_at DESC, p.id DESC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function repositoryMetrics(PDO $crad, string $validTitleApprovalSql): array
{
    $metrics = ['total' => 0, 'for_publication' => 0, 'published' => 0, 'archived' => 0];
    $stmt = $crad->query(
        "SELECT p.status, COUNT(*) AS total
         FROM publications p
         INNER JOIN research_groups rg ON rg.id = p.research_group_id
         INNER JOIN title_approvals ta ON ta.id = rg.title_approval_id AND {$validTitleApprovalSql}
         WHERE p.status IN ('For Publication', 'Published', 'Archived')
         GROUP BY p.status"
    );
    foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
        $count = (int) ($row['total'] ?? 0);
        $metrics['total'] += $count;
        if (($row['status'] ?? '') === 'For Publication') {
            $metrics['for_publication'] = $count;
        } elseif (($row['status'] ?? '') === 'Published') {
            $metrics['published'] = $count;
        } elseif (($row['status'] ?? '') === 'Archived') {
            $metrics['archived'] = $count;
        }
    }
    return $metrics;
}

function repositoryRecord(PDO $crad, string $validTitleApprovalSql, int $id): ?array
{
    $stmt = $crad->prepare(
        "SELECT
            p.*,
            rg.group_number,
            rg.group_name,
            rg.research_title AS registry_title,
            rg.academic_year,
            rg.college_dept,
            fma.status AS final_approval_status,
            fma.approved_by_name,
            fma.approved_at AS final_approved_at,
            ms.version_number AS manuscript_version,
            ms.status AS manuscript_status,
            ms.submitted_at AS manuscript_submitted_at,
            me.result AS manuscript_result,
            me.overall_score AS manuscript_score,
            me.evaluated_at AS manuscript_evaluated_at
         FROM publications p
         INNER JOIN research_groups rg ON rg.id = p.research_group_id
         INNER JOIN title_approvals ta ON ta.id = rg.title_approval_id AND {$validTitleApprovalSql}
         LEFT JOIN final_manuscript_approvals fma ON fma.research_group_id = p.research_group_id
         LEFT JOIN manuscript_submissions ms ON ms.id = (
            SELECT ms2.id
            FROM manuscript_submissions ms2
            WHERE ms2.research_group_id = p.research_group_id
            ORDER BY ms2.version_number DESC, ms2.id DESC
            LIMIT 1
         )
         LEFT JOIN manuscript_evaluations me ON me.id = (
            SELECT me2.id
            FROM manuscript_evaluations me2
            WHERE me2.submission_id = ms.id
            ORDER BY me2.id DESC
            LIMIT 1
         )
         WHERE p.id = ?
           AND p.status IN ('For Publication', 'Published', 'Archived')
         LIMIT 1"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

$repositoryRows = repositoryRows($crad, $validTitleApprovalSql, $requestedStatus);
$repositoryMetrics = repositoryMetrics($crad, $validTitleApprovalSql);
$selectedRecord = $selectedPublicationId > 0 ? repositoryRecord($crad, $validTitleApprovalSql, $selectedPublicationId) : null;

if (($_GET['ajax'] ?? '') === 'repository-records') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'rows' => $repositoryRows,
        'metrics' => $repositoryMetrics,
        'selected' => $selectedRecord,
        'selected_missing' => $selectedPublicationId > 0 && !$selectedRecord,
        'synced_at' => date('M j, Y h:i:s A'),
    ]);
    exit;
}

$breadcrumbs = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Research Repository', 'url' => null],
];
if ($selectedRecord) {
    $breadcrumbs[] = ['label' => 'REP-' . (int) $selectedRecord['id'], 'url' => null];
}

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<style>
    .repo-stats { display: grid; gap: 1rem; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 1.25rem; }
    .repo-stat { background: var(--sms-surface); border: 1px solid var(--sms-border); border-radius: 12px; box-shadow: var(--sms-shadow-xs); color: var(--sms-text); display: block; min-height: 92px; padding: 1rem; text-decoration: none; }
    .repo-stat:hover { color: var(--sms-text); text-decoration: none; }
    .repo-stat small { color: var(--sms-text-muted); display: block; font-weight: 700; margin-bottom: .25rem; }
    .repo-stat strong { color: var(--sms-heading); display: block; font-size: 1.75rem; line-height: 1; }
    .repo-stat.is-active { border-color: var(--sms-primary); box-shadow: 0 0 0 3px var(--sms-input-focus); }
    .repo-panel { background: var(--sms-surface); border: 1px solid var(--sms-border); border-radius: 14px; box-shadow: var(--sms-shadow-sm); margin-bottom: 1rem; padding: 1.1rem; }
    .repo-panel__head { align-items: center; display: flex; gap: 1rem; justify-content: space-between; margin-bottom: .8rem; }
    .repo-panel__head h2, .repo-panel__head h5 { margin: 0; }
    .repo-synced { color: var(--sms-text-muted); font-size: .82rem; font-weight: 700; white-space: nowrap; }
    .repo-status { border-radius: 999px; display: inline-flex; font-size: .78rem; font-weight: 800; line-height: 1; padding: .45rem .7rem; white-space: nowrap; }
    .repo-status--for-publication { background: rgba(217,119,6,.14); color: #92400e; }
    .repo-status--published { background: rgba(22,163,74,.14); color: #166534; }
    .repo-status--archived { background: rgba(100,116,139,.16); color: #334155; }
    .repo-status--muted { background: var(--sms-surface-muted); color: var(--sms-text-muted); }
    .repo-open-btn { align-items: center; background: var(--sms-primary); border-radius: 8px; color: #fff; display: inline-flex; font-weight: 800; gap: .4rem; min-height: 34px; padding: .45rem .8rem; text-decoration: none; }
    .repo-open-btn:hover { color: #fff; text-decoration: none; }
    .repo-detail-grid { display: grid; gap: .75rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .repo-detail-item { background: var(--sms-surface-muted); border: 1px solid var(--sms-border); border-radius: 10px; padding: .85rem; }
    .repo-detail-item small { color: var(--sms-text-muted); display: block; font-weight: 800; margin-bottom: .25rem; text-transform: uppercase; }
    .repo-detail-item span, .repo-detail-item a { overflow-wrap: anywhere; }
    .repo-detail-item.is-wide { grid-column: 1 / -1; }
    @media (max-width: 992px) { .repo-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .repo-detail-grid { grid-template-columns: 1fr; } }
    @media (max-width: 576px) { .repo-stats { grid-template-columns: 1fr; } .repo-panel__head { align-items: flex-start; flex-direction: column; } }
</style>

<div class="glass-dashboard">
    <div class="glass-board">
        <div class="repo-stats" data-repo-metrics>
            <a class="repo-stat <?= $requestedStatus === '' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(BASE_URL . '/modules/crad/pages/research-repository.php') ?>"><small>Catalogued Records</small><strong data-metric="total"><?= (int) $repositoryMetrics['total'] ?></strong></a>
            <a class="repo-stat <?= $requestedStatus === 'For Publication' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(BASE_URL . '/modules/crad/pages/research-repository.php?status=For+Publication') ?>"><small>For Publication</small><strong data-metric="for_publication"><?= (int) $repositoryMetrics['for_publication'] ?></strong></a>
            <a class="repo-stat <?= $requestedStatus === 'Published' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(BASE_URL . '/modules/crad/pages/research-repository.php?status=Published') ?>"><small>Published</small><strong data-metric="published"><?= (int) $repositoryMetrics['published'] ?></strong></a>
            <a class="repo-stat <?= $requestedStatus === 'Archived' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(BASE_URL . '/modules/crad/pages/research-repository.php?status=Archived') ?>"><small>Archived</small><strong data-metric="archived"><?= (int) $repositoryMetrics['archived'] ?></strong></a>
        </div>

        <?php if ($selectedPublicationId > 0): ?>
            <div class="repo-panel" data-repo-detail>
                <div class="repo-panel__head">
                    <div>
                        <h2>Repository Record <?= $selectedRecord ? 'REP-' . (int) $selectedRecord['id'] : '' ?></h2>
                        <div class="text-muted">Live details from publication, manuscript, and final approval records.</div>
                    </div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <span class="repo-synced" data-repo-synced>Synced <?= htmlspecialchars(date('M j, Y h:i:s A')) ?></span>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(BASE_URL . '/modules/crad/pages/research-repository.php') ?>">Back</a>
                    </div>
                </div>
                <div data-repo-detail-body>
                    <?php if (!$selectedRecord): ?>
                        <div class="alert alert-warning mb-0">This repository record is no longer For Publication, Published, or Archived.</div>
                    <?php else: ?>
                        <div class="repo-detail-grid">
                            <div class="repo-detail-item is-wide"><small>Research Title</small><span><?= e((string) $selectedRecord['title']) ?></span></div>
                            <div class="repo-detail-item"><small>Status</small><span class="repo-status <?= repositoryStatusClass((string) $selectedRecord['status']) ?>"><?= e((string) $selectedRecord['status']) ?></span></div>
                            <div class="repo-detail-item"><small>Group</small><span><?= e((string) (($selectedRecord['group_number'] ?? '') ?: ($selectedRecord['group_name'] ?? 'Not recorded'))) ?></span></div>
                            <div class="repo-detail-item"><small>Academic Year</small><span><?= e((string) (($selectedRecord['academic_year'] ?? '') ?: 'Not recorded')) ?></span></div>
                            <div class="repo-detail-item"><small>Authors</small><span><?= nl2br(e((string) (($selectedRecord['authors'] ?? '') ?: 'Not recorded'))) ?></span></div>
                            <div class="repo-detail-item"><small>Outlet</small><span><?= e((string) (($selectedRecord['publication_outlet'] ?? '') ?: 'Not recorded')) ?></span></div>
                            <div class="repo-detail-item"><small>Publication Date</small><span><?= e((string) (($selectedRecord['publication_date'] ?? '') ?: 'Not recorded')) ?></span></div>
                            <div class="repo-detail-item"><small>DOI / Link</small><?php if (trim((string) ($selectedRecord['doi_link'] ?? '')) !== ''): ?><a href="<?= e((string) $selectedRecord['doi_link']) ?>" target="_blank" rel="noopener"><?= e((string) $selectedRecord['doi_link']) ?></a><?php else: ?><span>Not recorded</span><?php endif; ?></div>
                            <div class="repo-detail-item"><small>Final Manuscript Approval</small><span><?= e((string) (($selectedRecord['final_approval_status'] ?? '') ?: 'Not recorded')) ?></span></div>
                            <div class="repo-detail-item"><small>Manuscript Evaluation</small><span><?= e((string) (($selectedRecord['manuscript_result'] ?? '') ?: 'Not recorded')) ?><?= $selectedRecord['manuscript_score'] !== null ? ' (' . e((string) $selectedRecord['manuscript_score']) . ')' : '' ?></span></div>
                            <div class="repo-detail-item"><small>Updated</small><span><?= e((string) $selectedRecord['updated_at']) ?></span></div>
                            <div class="repo-detail-item is-wide"><small>Notes</small><span><?= nl2br(e((string) (($selectedRecord['notes'] ?? '') ?: 'No notes recorded.'))) ?></span></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="repo-panel">
            <div class="repo-panel__head">
                <div>
                    <h5>Research Repository</h5>
                    <div class="text-muted">Showing only records with repository statuses: For Publication, Published, or Archived.</div>
                </div>
                <span class="repo-synced" data-repo-synced>Synced <?= htmlspecialchars(date('M j, Y h:i:s A')) ?></span>
            </div>
            <p class="text-muted mb-0 <?= $repositoryRows ? 'd-none' : '' ?>" data-repo-empty>No publication records are ready for the repository.</p>
            <div class="table-responsive <?= !$repositoryRows ? 'd-none' : '' ?>" data-repo-table-wrap>
                <table class="table align-middle mb-0">
                    <thead><tr><th>Repository Record</th><th>Research Title</th><th>Outlet</th><th>Status</th><th>Updated</th><th>Action</th></tr></thead>
                    <tbody data-repo-rows>
                        <?php foreach ($repositoryRows as $row): ?>
                            <tr>
                                <td>REP-<?= (int) $row['id'] ?></td>
                                <td><?= e((string) $row['title']) ?><div class="small text-muted"><?= e((string) ($row['group_number'] ?? '')) ?></div></td>
                                <td><?= e((string) (($row['publication_outlet'] ?? '') ?: 'Not recorded')) ?></td>
                                <td><span class="repo-status <?= repositoryStatusClass((string) $row['status']) ?>"><?= e((string) $row['status']) ?></span></td>
                                <td><?= e((string) $row['updated_at']) ?></td>
                                <td><a class="repo-open-btn" href="<?= htmlspecialchars(repositoryRecordUrl((int) $row['id'])) ?>"><?= smsIcon('folder-open', ['aria-hidden' => 'true']) ?>Open</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rowsBody = document.querySelector('[data-repo-rows]');
    const tableWrap = document.querySelector('[data-repo-table-wrap]');
    const emptyState = document.querySelector('[data-repo-empty]');
    const detailBody = document.querySelector('[data-repo-detail-body]');
    const syncedNodes = document.querySelectorAll('[data-repo-synced]');
    const metricKeys = ['total', 'for_publication', 'published', 'archived'];
    const baseDetailUrl = <?= json_encode(BASE_URL . '/modules/crad/pages/research-repository.php?id=') ?>;
    const esc = function (value) {
        return String(value ?? '').replace(/[&<>"']/g, function (ch) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch];
        });
    };
    const statusClass = function (status) {
        if (status === 'For Publication') return 'repo-status--for-publication';
        if (status === 'Published') return 'repo-status--published';
        if (status === 'Archived') return 'repo-status--archived';
        return 'repo-status--muted';
    };
    const renderRows = function (rows) {
        if (!rowsBody || !tableWrap || !emptyState) return;
        tableWrap.classList.toggle('d-none', rows.length === 0);
        emptyState.classList.toggle('d-none', rows.length !== 0);
        rowsBody.innerHTML = rows.map(function (row) {
            const id = parseInt(row.id || '0', 10) || 0;
            return '<tr>' +
                '<td>REP-' + id + '</td>' +
                '<td>' + esc(row.title || '') + '<div class="small text-muted">' + esc(row.group_number || '') + '</div></td>' +
                '<td>' + esc(row.publication_outlet || 'Not recorded') + '</td>' +
                '<td><span class="repo-status ' + statusClass(row.status || '') + '">' + esc(row.status || '') + '</span></td>' +
                '<td>' + esc(row.updated_at || '') + '</td>' +
                '<td><a class="repo-open-btn" href="' + esc(baseDetailUrl + id) + '"><?= smsIcon('folder-open', ['aria-hidden' => 'true']) ?>Open</a></td>' +
            '</tr>';
        }).join('');
    };
    const renderDetail = function (record, selectedMissing) {
        if (!detailBody) return;
        if (!record || selectedMissing) {
            detailBody.innerHTML = '<div class="alert alert-warning mb-0">This repository record is no longer For Publication, Published, or Archived.</div>';
            return;
        }
        const score = record.manuscript_score !== null && record.manuscript_score !== undefined ? ' (' + esc(record.manuscript_score) + ')' : '';
        const doi = record.doi_link ? '<a href="' + esc(record.doi_link) + '" target="_blank" rel="noopener">' + esc(record.doi_link) + '</a>' : '<span>Not recorded</span>';
        detailBody.innerHTML = '<div class="repo-detail-grid">' +
            '<div class="repo-detail-item is-wide"><small>Research Title</small><span>' + esc(record.title || '') + '</span></div>' +
            '<div class="repo-detail-item"><small>Status</small><span class="repo-status ' + statusClass(record.status || '') + '">' + esc(record.status || '') + '</span></div>' +
            '<div class="repo-detail-item"><small>Group</small><span>' + esc(record.group_number || record.group_name || 'Not recorded') + '</span></div>' +
            '<div class="repo-detail-item"><small>Academic Year</small><span>' + esc(record.academic_year || 'Not recorded') + '</span></div>' +
            '<div class="repo-detail-item"><small>Authors</small><span>' + esc(record.authors || 'Not recorded').replace(/\n/g, '<br>') + '</span></div>' +
            '<div class="repo-detail-item"><small>Outlet</small><span>' + esc(record.publication_outlet || 'Not recorded') + '</span></div>' +
            '<div class="repo-detail-item"><small>Publication Date</small><span>' + esc(record.publication_date || 'Not recorded') + '</span></div>' +
            '<div class="repo-detail-item"><small>DOI / Link</small>' + doi + '</div>' +
            '<div class="repo-detail-item"><small>Final Manuscript Approval</small><span>' + esc(record.final_approval_status || 'Not recorded') + '</span></div>' +
            '<div class="repo-detail-item"><small>Manuscript Evaluation</small><span>' + esc(record.manuscript_result || 'Not recorded') + score + '</span></div>' +
            '<div class="repo-detail-item"><small>Updated</small><span>' + esc(record.updated_at || '') + '</span></div>' +
            '<div class="repo-detail-item is-wide"><small>Notes</small><span>' + esc(record.notes || 'No notes recorded.').replace(/\n/g, '<br>') + '</span></div>' +
        '</div>';
    };
    const refreshRepository = async function () {
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax', 'repository-records');
            url.searchParams.set('_', Date.now().toString());
            const response = await fetch(url.toString(), {headers: {'Accept': 'application/json'}, credentials: 'same-origin', cache: 'no-store'});
            const data = await response.json();
            if (!data.ok) return;
            renderRows(Array.isArray(data.rows) ? data.rows : []);
            renderDetail(data.selected || null, !!data.selected_missing);
            metricKeys.forEach(function (key) {
                document.querySelectorAll('[data-metric="' + key + '"]').forEach(function (node) {
                    node.textContent = String((data.metrics && data.metrics[key]) || 0);
                });
            });
            syncedNodes.forEach(function (node) {
                node.textContent = 'Synced ' + (data.synced_at || '');
            });
        } catch (error) {
            // Keep the last rendered data visible if a refresh request fails.
        }
    };
    window.setInterval(refreshRepository, 10000);
});
</script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
