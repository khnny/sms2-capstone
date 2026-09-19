<?php
/**
 * Faculty Module - Revision Monitoring (Detail)
 * Shows the 3 Panel Member evaluations and remarks for a consensus
 * APPROVED WITH REVISION Pre-Oral Defense, plus the live revision status
 * derived from the existing progress-update workflow.
 */

$pageTitle = 'View Revision';
$activeModule = 'faculty';
$activePage = 'revision-monitoring-view';

$pageBannerIcon        = 'fa-redo';
$pageBannerDescription = 'Panel evaluation summary and revision monitoring for a research group.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';

$breadcrumbs = [
    ['label' => 'Faculty',                 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Revision Monitoring',      'url' => BASE_URL . '/modules/faculty/pages/revision-monitoring.php'],
    ['label' => 'View Revision',            'url' => null],
];

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);

try {
    $crad = cradDb();
    $tablesCheck = $crad->query("SHOW TABLES LIKE 'research_plans'")->fetch();
    if (!$tablesCheck) {
        throw new Exception('Research Progress module not installed.');
    }
} catch (Throwable $e) {
    echo '<div class="alert alert-warning m-3">' . smsIcon('exclamation-triangle', ['class' => 'me-2']) . '<strong>Module Not Installed</strong></div>';
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

$adviserUserId = (int) ($_SESSION['user_id'] ?? 0);
$adviserEmail  = rpCurrentUserEmail();
$groupNumber   = (string) ($_GET['group'] ?? '');

$detail = rpGetRevisionDetail($crad, $adviserUserId, $adviserEmail, $groupNumber);

if (!$detail) {
    echo '<div class="glass-dashboard"><div class="glass-board">'
        . '<div class="glass-panel"><div class="glass-panel-body rm-empty">'
        . '<div class="rm-empty-icon">' . smsIcon('ban', ['style' => 'color:#ef4444;']) . '</div>'
        . '<h6>Case Not Available</h6>'
        . '<p>This revision case is not available or you do not have access to it.</p>'
        . '<a href="' . BASE_URL . '/modules/faculty/pages/revision-monitoring.php" class="btn btn-primary mt-3">'
        . smsIcon('redo', ['class' => 'me-2']) . 'Back to Revision Monitoring'
        . '</a>'
        . '</div></div>'
        . '</div></div>';
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

$group = $detail['group'];
$panels = $detail['panels'];
$updates = $detail['updates'];

$revStatus      = (string) ($group['revision_status'] ?? 'For Revision');
$statusClass    = match ($revStatus) {
    'Completed'             => 'rm-status-completed',
    'Revision Submitted'    => 'rm-status-pending',
    'Under Adviser Review'  => 'rm-status-review',
    default                 => 'rm-status-active',
};
$defenseDate = !empty($group['defense_datetime'])
    ? date('M j, Y h:i A', strtotime($group['defense_datetime']))
    : 'Not recorded';
?>

<style>
.rm-panel-card { border: 1px solid var(--sms-border); border-radius: 10px; }
.rm-panel-card-header { background: rgba(99,102,241,0.06); }
.rm-panel-result { font-weight: 800; }
.rm-result-awr { color: #b47814; }
.rm-result-approved { color: #059669; }
.rm-result-failed { color: #ef4444; }
.rm-remarks-box { background: #f8fafc; border-left: 3px solid var(--sms-border); padding: 0.75rem 1rem; border-radius: 8px; }
.rm-timeline-item { border-left: 2px solid var(--sms-border); padding-left: 1rem; margin-bottom: 1rem; }
.rm-timeline-item:last-child { border-left: none; padding-left: 0; }
.rm-timeline-bubble { background: #f8fafc; border: 1px solid var(--sms-border); border-radius: 8px; padding: 0.6rem 0.9rem; }

/* Dark mode overrides */
[data-theme="dark"] .rm-remarks-box {
    background: rgba(255, 255, 255, 0.05);
    border-left-color: rgba(148, 163, 184, 0.25);
    color: var(--sms-text);
}
[data-theme="dark"] .rm-timeline-bubble {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(148, 163, 184, 0.18);
    color: var(--sms-text);
}
[data-theme="dark"] .rm-result-awr   { color: #fbbf24; }
[data-theme="dark"] .rm-result-approved { color: #34d399; }
[data-theme="dark"] .rm-result-failed   { color: #f87171; }
</style>

<div class="glass-dashboard" data-live-update-page="revision-monitoring-view"
     data-group-number="<?= htmlspecialchars((string) $group['group_number']) ?>">
    <div class="glass-board">

        <!-- ── Group Hero ────────────────────────────── -->
        <div class="rm-group-hero" style="padding:1.1rem 1.4rem;">
            <div class="rm-group-hero-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge"><?= htmlspecialchars((string) $group['group_number']) ?></span>
                            <span class="badge"><?= htmlspecialchars((string) $group['academic_year']) ?></span>
                            <span class="badge rm-panel-decision" style="font-size:0.72rem;"><?= htmlspecialchars((string) $group['panel_decision']) ?></span>
                        </div>
                        <h5 class="mt-2 mb-1" style="font-size:1.05rem;"><?= htmlspecialchars((string) ($group['group_name'] ?? '')) ?></h5>
                        <p style="font-size:0.85rem;margin:0;color:var(--sms-text-muted);">
                            <?= htmlspecialchars((string) ($group['research_title'] ?? 'Research title pending')) ?>
                        </p>
                        <div class="d-flex align-items-center flex-wrap gap-3 mt-2" style="font-size:0.8rem;color:var(--sms-text);overflow-wrap:anywhere;">
                            <span><?= smsIcon('gavel', ['class' => 'me-1']) ?>Pre-Oral Defense: <?= $defenseDate ?></span>
                            <?php if (!empty($group['venue'])): ?>
                                <span><?= smsIcon('map-marker-alt', ['class' => 'me-1']) ?><?= htmlspecialchars((string) $group['venue']) ?></span>
                            <?php endif; ?>
                            <span><?= smsIcon('user-tie', ['class' => 'me-1']) ?>Adviser: <?= htmlspecialchars((string) ($group['adviser_name'] ?? $group['defense_adviser_name'] ?? '')) ?></span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end">
                            <div class="rm-status-pill <?= $statusClass ?>" data-revision-status>
                                <?= htmlspecialchars($revStatus) ?>
                            </div>
                            <div style="font-size:0.7rem;color:var(--sms-text-muted);margin-top:0.3rem;">
                                Last activity: <?= !empty($group['revision_last_activity_at'])
                                    ? date('M j, Y g:i A', strtotime($group['revision_last_activity_at'])) : 'Just now' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Consensus Summary ─────────────────────── -->
        <div class="glass-panel mb-4">
            <div class="glass-panel-body">
                <h5 class="mb-3" style="font-weight:800;">
                    <?= smsIcon('clipboard-check', ['class' => 'me-2', 'style' => 'color:#6366f1;']) ?>Panel Evaluation Consensus
                </h5>
                <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:0.9rem;">
                    <span><?= smsIcon('users', ['class' => 'me-1']) ?>Assigned Panel Members: <strong><?= (int) ($group['assigned_panel_count'] ?? 0) ?></strong></span>
                    <span><?= smsIcon('poll', ['class' => 'me-1']) ?>Submitted Evaluations: <strong><?= (int) ($group['submitted_eval_count'] ?? 0) ?></strong></span>
                    <span><?= smsIcon('file-signature', ['class' => 'me-1']) ?>Panel Decision: <strong style="color:#b47814;">APPROVED WITH REVISION (<?= (int) ($group['awr_count'] ?? 0) ?> of <?= (int) ($group['assigned_panel_count'] ?? 0) ?>)</strong></span>
                </div>
            </div>
        </div>

        <!-- ── Panel Member Evaluations ──────────────── -->
        <h5 class="mb-3" style="font-weight:800;">
            <?= smsIcon('file-signature', ['class' => 'me-2', 'style' => 'color:#3b82f6;']) ?>Panel Evaluation Summary
        </h5>

        <div class="row g-4">
            <?php foreach ($panels as $index => $panel):
                $result   = (string) ($panel['panel_result'] ?? '');
                $resultCls = $result === 'APPROVED WITH REVISION' ? 'rm-result-awr'
                    : ($result === 'APPROVED' ? 'rm-result-approved' : 'rm-result-failed');
                $resultIcon = $result === 'APPROVED WITH REVISION' ? 'exclamation-triangle'
                    : ($result === 'APPROVED' ? 'check-circle' : 'times-circle');
            ?>
                <div class="col-md-6" data-panel="<?= ($index + 1) ?>">
                    <div class="glass-panel rm-panel-card h-100">
                        <div class="glass-panel-body">
                            <div class="rm-panel-card-header d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary" style="font-size:0.78rem;font-weight:800;">
                                    Panel Member <?= ($index + 1) ?>
                                </span>
                                <span class="badge bg-secondary" style="font-size:0.72rem;font-weight:700;">
                                    <?= htmlspecialchars($result) ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <?= smsIcon('user-tie', ['style' => 'color:#6366f1;']) ?>
                                <strong class="rm-panel-result <?= $resultCls ?>">
                                    <?= htmlspecialchars((string) ($panel['panel_name'] ?? 'Panel Member')) ?>
                                </strong>
                            </div>
                            <?php if (!empty($panel['panel_email'])): ?>
                                <div class="d-flex align-items-center gap-2 mb-2" style="font-size:0.8rem;color:var(--sms-text-muted);">
                                    <?= smsIcon('envelope', ['class' => 'me-1']) ?>
                                    <?= htmlspecialchars((string) $panel['panel_email']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex align-items-center gap-3 mb-2" style="font-size:0.8rem;color:var(--sms-text);">
                                <?php if ($panel['overall_score'] !== null): ?>
                                    <span><?= smsIcon('star', ['class' => 'me-1']) ?>Overall Score: <strong><?= number_format((float) $panel['overall_score'], 2) ?></strong></span>
                                <?php endif; ?>
                                <span><?= smsIcon('clock', ['class' => 'me-1']) ?>Evaluated: <?= $panel['evaluated_at'] ? date('M j, Y g:i A', strtotime($panel['evaluated_at'])) : 'Not recorded' ?></span>
                            </div>

                            <!-- Remarks -->
                            <div class="mb-2">
                                <div class="rm-section-block-label" style="color:var(--sms-heading);font-weight:700;">
                                    <?= smsIcon($resultIcon, ['class' => 'me-1']) ?>Remarks
                                </div>
                                <div class="rm-remarks-box">
                                    <?php if (!empty($panel['remarks'])): ?>
                                        <?= nl2br(htmlspecialchars((string) $panel['remarks'])) ?>
                                    <?php else: ?>
                                        <span style="color:var(--sms-text-muted);">No remarks provided.</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Scores breakdown -->
                            <?php $hasScores = $panel['content_score'] !== null && $panel['methodology_score'] !== null; ?>
                            <?php if ($hasScores): ?>
                                <table class="table table-sm table-bordered mb-0" style="font-size:0.78rem;">
                                    <thead class="table-light"><tr><th>Criterion</th><th>Score</th></tr></thead>
                                    <tbody>
                                        <tr><td>Content</td><td><?= number_format((float) $panel['content_score'], 2) ?></td></tr>
                                        <tr><td>Methodology</td><td><?= number_format((float) $panel['methodology_score'], 2) ?></td></tr>
                                        <tr><td>References</td><td><?= number_format((float) $panel['references_score'], 2) ?></td></tr>
                                        <tr><td>Format</td><td><?= number_format((float) $panel['format_score'], 2) ?></td></tr>
                                        <?php if (array_key_exists('defense_score', $panel) && $panel['defense_score'] !== null): ?>
                                        <tr><td>Defense</td><td><?= number_format((float) $panel['defense_score'], 2) ?></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Revision Workflow / Existing Updates ────── -->
        <div class="glass-panel mb-4">
            <div class="glass-panel-body">
                <h5 class="mb-3" style="font-weight:800;">
                    <?= smsIcon('redo', ['class' => 'me-2', 'style' => 'color:#10b981;']) ?>Revision Workflow
                </h5>
                <p style="font-size:0.85rem;color:var(--sms-text-muted);">
                    The student works on revisions and submits progress updates through the
                    existing Student → Adviser workflow. Review and provide feedback via the
                    existing Submitted Updates page.
                </p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= BASE_URL ?>/modules/faculty/pages/revision-monitoring.php"
                       class="btn btn-sm btn-outline-secondary">
                        <?= smsIcon('list', ['class' => 'me-1']) ?>Back to Revision Monitoring
                    </a>
                    <a href="<?= BASE_URL ?>/modules/faculty/pages/submitted-updates.php?group=<?= urlencode((string) $group['group_number']) ?>"
                       class="btn btn-sm btn-primary">
                        <?= smsIcon('inbox', ['class' => 'me-1']) ?>Open Progress Review
                    </a>
                </div>

                <?php if (!empty($updates)): ?>
                    <div class="mt-3">
                        <div class="mb-2" style="font-weight:700;font-size:0.85rem;color:var(--sms-heading);">Recent Student Submissions</div>
                        <div>
                            <?php foreach ($updates as $u): ?>
                                <div class="rm-timeline-item">
                                    <div class="rm-timeline-bubble">
                                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                            <div>
                                                <strong style="font-size:0.85rem;"><?= htmlspecialchars((string) ($u['update_title'] ?? 'Progress Update')) ?></strong>
                                                <?php if (!empty($u['milestone_name'])): ?>
                                                    <span class="badge bg-secondary" style="font-size:0.68rem;font-weight:700;"><?= htmlspecialchars((string) $u['milestone_name']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size:0.78rem;color:var(--sms-text-muted);">
                                                <?= date('M j, Y g:i A', strtotime($u['submitted_at'])) ?>
                                            </div>
                                        </div>
                                        <div class="mt-1" style="font-size:0.8rem;color:var(--sms-text);">
                                            Status: <span class="badge bg-info text-dark"><?= htmlspecialchars((string) ($u['milestone_status'] ?? '')) ?></span>
                                            &middot; Progress: <?= number_format((float) ($u['new_progress'] ?? 0)) ?>%
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-3" style="font-size:0.8rem;color:var(--sms-text-muted);">
                        No student submissions yet. The student may submit a progress update
                        through the existing workflow once revisions begin.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Live refresh indicator -->
        <div class="rm-refresh-bar" id="rmRefreshBar">
            <?= smsIcon('sync-alt', ['class' => 'rm-refresh-icon']) ?>
            <span id="rmRefreshText">Last updated: <?= date('g:i:s A') ?></span>
        </div>

    </div>
</div>

<script>
(function () {
    const detailHash = <?= json_encode([
        'group_number' => (string) ($group['group_number'] ?? ''),
        'revision_status' => $revStatus,
        'revision_last_activity_at' => (string) ($group['revision_last_activity_at'] ?? ''),
    ]) ?>;
    const detailUrl = '<?= BASE_URL ?>/modules/crad/api/adviser-progress.php?action=get_revision_detail&group_number=' + encodeURIComponent(detailHash.group_number);

    function tick() {
        const now = new Date();
        const bar = document.getElementById('rmRefreshBar');
        const icon = document.getElementById('rmRefreshIcon');
        const textEl = document.getElementById('rmRefreshText');
        if (bar && textEl) {
            textEl.textContent = 'Last updated: ' + now.toLocaleTimeString();
            bar.classList.add('rm-just-updated');
            setTimeout(() => bar.classList.remove('rm-just-updated'), 1500);
        }
        if (bar && icon) {
            bar.classList.add('rm-spinning');
            setTimeout(() => bar.classList.remove('rm-spinning'), 700);
        }
    }

    async function refresh() {
        try {
            const resp = await fetch(detailUrl + '&_=' + Date.now(), {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();
            if (data.success && data.detail) {
                const g = data.detail.group || {};
                const liveHash = {
                    group_number: String(g.group_number || ''),
                    revision_status: String(g.revision_status || ''),
                    revision_last_activity_at: String(g.revision_last_activity_at || '')
                };
                if (JSON.stringify(liveHash) !== JSON.stringify(detailHash)) {
                    // Revision status changed (e.g. student submitted an update) — reload.
                    window.location.reload();
                    return;
                }
            }
        } catch (err) {
            console.error('[RevisionMonitoringView] poll error:', err);
        }
        tick();
    }

    refresh();
    setInterval(refresh, 10000);
})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
