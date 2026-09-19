<?php
/**
 * Faculty Module - Revision Monitoring
 * Research groups eligible for Adviser-side revision monitoring after a
 * 3/3 Pre-Oral Defense APPROVED WITH REVISION consensus.
 */

$pageTitle = 'Revision Monitoring';
$activeModule = 'faculty';
$activePage = 'revision-monitoring';

$pageBannerIcon        = 'fa-redo';
$pageBannerDescription = 'Monitor research groups requiring revisions after their Pre-Oral Defense.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';

$breadcrumbs = [
    ['label' => 'Faculty',            'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Research Monitoring','url' => null],
    ['label' => 'Revision Monitoring', 'url' => null],
];

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);

// Module check
try {
    $crad = cradDb();
    $tablesCheck = $crad->query("SHOW TABLES LIKE 'research_plans'")->fetch();
    if (!$tablesCheck) {
        throw new Exception('Research Progress module not installed.');
    }
} catch (Throwable $e) {
    echo '<div class="alert alert-warning m-3">'
        . smsIcon('exclamation-triangle', ['class' => 'me-2'])
        . '<strong>Module Not Installed</strong><br>'
        . 'The Research Progress module database tables are not yet installed.'
        . '</div>';
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

$adviserUserId = (int) ($_SESSION['user_id'] ?? 0);
$adviserEmail  = rpCurrentUserEmail();

$groups = rpGetRevisionMonitoringGroups($crad, $adviserUserId, $adviserEmail);
$counts = rpRevisionMonitoringCounts($groups);
?>

<style>
.rm-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.25rem 0.7rem;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 700;
    white-space: nowrap;
}
.rm-status-active { background: rgba(99,102,241,0.12); color: #6366f1; }
.rm-status-pending { background: rgba(59,130,246,0.12); color: #3b82f6; }
.rm-status-review { background: rgba(245,158,11,0.12); color: #f59e0b; }
.rm-status-completed { background: rgba(16,185,129,0.12); color: #10b981; }
.rm-panel-badge { background: rgba(239,68,68,0.10); color: #ef4444; }
.rm-panel-decision { background: rgba(245,158,11,0.10); color: #b47814; }
</style>

<div class="glass-dashboard" data-live-update-page="revision-monitoring">
    <div class="glass-board">

        <!-- ── Live / Summary ────────────────────────── -->
        <div class="d-flex align-items-center gap-2 justify-content-end mb-3">
            <div class="rm-live-badge">
                <span class="rm-live-dot"></span>Live
            </div>
            <?php if (!empty($groups)): ?>
                <span class="badge bg-warning text-dark" style="font-size:0.82rem;padding:0.45rem 0.85rem;border-radius:999px;font-weight:800;">
                    <?= smsIcon('redo', ['class' => 'me-1']) ?><?= count($groups) ?> Active Revision Case(s)
                </span>
            <?php endif; ?>
        </div>

        <p class="text-muted mb-3" style="font-size:0.85rem;">
            Monitor research groups requiring revisions after their Pre-Oral Defense (3/3 Panel APPROVED WITH REVISION).
        </p>

        <!-- ── Summary Chips ─────────────────────────── -->
        <?php if (!empty($groups)): ?>
        <div class="rm-stats-row">
            <div class="rm-stat-chip">
                <div class="rm-stat-chip-icon" style="background:rgba(99,102,241,0.12);color:#6366f1;"><?= smsIcon('redo') ?></div>
                <div class="rm-stat-chip-value" style="color:#6366f1;" data-live-active-count><?= $counts['active'] ?></div>
                <div class="rm-stat-chip-label">Active Revisions</div>
            </div>
            <div class="rm-stat-chip">
                <div class="rm-stat-chip-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6;"><?= smsIcon('clock') ?></div>
                <div class="rm-stat-chip-value" style="color:#3b82f6;" data-live-pending-count><?= $counts['pending'] ?></div>
                <div class="rm-stat-chip-label">Pending Review</div>
            </div>
            <div class="rm-stat-chip">
                <div class="rm-stat-chip-icon" style="background:rgba(16,185,129,0.12);color:#10b981;"><?= smsIcon('check-circle') ?></div>
                <div class="rm-stat-chip-value" style="color:#10b981;" data-live-completed-count><?= $counts['completed'] ?></div>
                <div class="rm-stat-chip-label">Completed</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Revision Cases ─────────────────────────── -->
        <?php if (!empty($groups)): ?>
            <div class="row g-4" data-groups-container>
                <?php foreach ($groups as $group):
                    $panelCount = (int) ($group['assigned_panel_count'] ?? 0);
                    $awrCount   = (int) ($group['awr_count'] ?? 0);
                    $revStatus  = (string) ($group['revision_status'] ?? 'For Revision');
                    $statusClass = match ($revStatus) {
                        'Completed'          => 'rm-status-completed',
                        'Revision Submitted' => 'rm-status-pending',
                        'Under Adviser Review' => 'rm-status-review',
                        default              => 'rm-status-active',
                    };
                    $defenseDate = !empty($group['defense_datetime'])
                        ? date('M j, Y h:i A', strtotime($group['defense_datetime']))
                        : 'Not recorded';
                ?>
                    <div class="col-xl-4 col-lg-6" data-group-id="<?= (int) $group['research_group_id'] ?>" data-group-number="<?= htmlspecialchars((string) $group['group_number']) ?>">
                        <div class="glass-panel rm-group-card">
                            <div class="glass-panel-body d-flex flex-column h-100">

                                <!-- Badge row -->
                                <div class="rm-group-badge-row">
                                    <span class="badge bg-primary" style="font-size:0.82rem;font-weight:800;padding:0.35rem 0.8rem;">
                                        <?= htmlspecialchars((string) $group['group_number']) ?>
                                    </span>
                                    <span class="badge bg-secondary" style="font-size:0.78rem;font-weight:700;">
                                        <?= htmlspecialchars((string) $group['academic_year']) ?>
                                    </span>
                                    <span class="badge rm-panel-badge" style="font-size:0.72rem;font-weight:800;">
                                        <?= smsIcon('users', ['class' => 'me-1']) ?><?= $panelCount ?>/3 Panels
                                    </span>
                                </div>

                                <!-- Title -->
                                <h6 class="rm-group-title mt-2"><?= htmlspecialchars((string) ($group['group_name'] ?? '')) ?></h6>
                                <p class="rm-group-subtitle"><?= htmlspecialchars((string) ($group['research_title'] ?? 'Research title pending')) ?></p>

                                <!-- Meta -->
                                <div class="rm-group-meta">
                                    <?= smsIcon('gavel') ?>
                                    Pre-Oral Defense &middot; <?= $defenseDate ?>
                                </div>
                                <?php if (!empty($group['venue'])): ?>
                                    <div class="rm-group-meta">
                                        <?= smsIcon('map-marker-alt') ?>
                                        <?= htmlspecialchars((string) $group['venue']) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Panel decision + evaluations -->
                                <div class="mt-3 d-flex align-items-center justify-content-between gap-2"
                                     style="font-size:0.8rem;color:var(--sms-text);">
                                    <span class="badge rm-panel-decision" style="font-size:0.72rem;font-weight:800;">
                                        <?= htmlspecialchars((string) $group['panel_decision']) ?>
                                    </span>
                                    <span style="color:var(--sms-text-muted);">
                                        Panel Evaluations: <?= htmlspecialchars((string) $group['panel_evaluations_summary']) ?>
                                    </span>
                                </div>

                                <!-- Revision status + last activity -->
                                <div class="mt-3 d-flex align-items-center justify-content-between gap-2"
                                     style="font-size:0.8rem;color:var(--sms-text);">
                                    <span>Revision Status</span>
                                    <span class="rm-status-pill <?= $statusClass ?>" data-revision-status data-revision-status-label>
                                        <?= htmlspecialchars($revStatus) ?>
                                    </span>
                                </div>
                                <div class="mt-1" style="font-size:0.74rem;color:var(--sms-text-muted);">
                                    <?= smsIcon('clock', ['class' => 'me-1']) ?>
                                    Last updated: <?= !empty($group['revision_last_activity_at'])
                                        ? date('M j, Y g:i A', strtotime($group['revision_last_activity_at']))
                                        : 'Just now' ?>
                                </div>

                                <!-- Actions -->
                                <div class="rm-group-actions">
                                    <a href="<?= BASE_URL ?>/modules/faculty/pages/revision-monitoring-view.php?group=<?= urlencode((string) $group['group_number']) ?>"
                                       class="rm-primary-action">
                                        <?= smsIcon('eye', ['class' => 'me-1']) ?>View Revision
                                    </a>
                                    <a href="<?= BASE_URL ?>/modules/faculty/pages/submitted-updates.php?group=<?= urlencode((string) $group['group_number']) ?>"
                                       class="rm-sec-action">
                                        <?= smsIcon('inbox', ['class' => 'me-1']) ?>Progress Review
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="glass-panel">
                <div class="glass-panel-body rm-empty">
                    <div class="rm-empty-icon"><?= smsIcon('redo') ?></div>
                    <h6>No Revision Cases</h6>
                    <p>
                        No research groups currently meet the 3/3 Panel
                        APPROVED WITH REVISION condition for revision monitoring.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Live refresh indicator -->
        <div class="rm-refresh-bar" id="rmRefreshBar">
            <?= smsIcon('sync-alt', ['class' => 'rm-refresh-icon']) ?>
            <span id="rmRefreshText">Last updated: <?= date('g:i:s A') ?></span>
        </div>

    </div>
</div>

<script>
(function () {
    const initialGroupsHash = <?= json_encode(array_map(static fn($g) => [
        'group_number'         => (string) ($g['group_number'] ?? ''),
        'revision_status'      => (string) ($g['revision_status'] ?? ''),
        'revision_last_activity_at' => (string) ($g['revision_last_activity_at'] ?? ''),
    ], $groups)) ?>;
    const apiUrl = '<?= BASE_URL ?>/modules/crad/api/adviser-progress.php?action=get_revision_monitoring';

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

    function applyChanges(data) {
        if (!data || !Array.isArray(data.groups)) return;
        const counters = data.counters || {};
        const activeEl = document.querySelector('[data-live-active-count]');
        const pendEl  = document.querySelector('[data-live-pending-count]');
        const compEl  = document.querySelector('[data-live-completed-count]');
        if (activeEl) activeEl.textContent = counters.active || 0;
        if (pendEl)   pendEl.textContent = counters.pending || 0;
        if (compEl)   compEl.textContent = counters.completed || 0;

        const liveHash = data.groups.map(function (g) {
            return {
                group_number: String(g.group_number || ''),
                revision_status: String(g.revision_status || ''),
                revision_last_activity_at: String(g.revision_last_activity_at || '')
            };
        });

        if (JSON.stringify(liveHash) !== JSON.stringify(initialGroupsHash)) {
            // Structure changed — reload to render new/removed cases accurately.
            window.location.reload();
            return;
        }
        // Same groups — update status pills in place.
        liveHash.forEach(function (item) {
            const card = Array.prototype.find.call(
                document.querySelectorAll('[data-group-number]'),
                function (el) { return el.getAttribute('data-group-number') === item.group_number; }
            );
            if (!card) return;
            const label = card.querySelector('[data-revision-status-label]');
            if (label) label.textContent = item.revision_status;
        });
    }

    async function refresh() {
        try {
            const resp = await fetch(apiUrl + '&_=' + Date.now(), {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();
            if (data.success) {
                applyChanges(data);
            }
        } catch (err) {
            console.error('[RevisionMonitoring] poll error:', err);
        }
        tick();
    }

    refresh();
    setInterval(refresh, 10000);
})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
