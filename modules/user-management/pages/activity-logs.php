<?php
/**
 * SMS 2 – User Management – Activity Logs (Super Admin full audit trail)
 */
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once __DIR__ . '/../includes/activity-logs-query.php';

$pageTitle    = 'Activity Logs';
$activeModule = 'user-management';
$activePage   = 'activity-logs';
$breadcrumbs  = [
    ['label' => 'User Management', 'url' => BASE_URL . '/modules/user-management/index.php'],
    ['label' => 'Activity Logs',   'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
requireSuperAdmin();

$payload = umActivityLogsPayload(db());
$logs = $payload['logs'];
$actionOptions = array_fill_keys($payload['actions'], true);
$moduleOptions = array_fill_keys($payload['modules'], true);
$total = (int) $payload['stats']['total'];
$logins = (int) $payload['stats']['logins'];
$changes = (int) $payload['stats']['changes'];
$exports = (int) $payload['stats']['exports'];
$liveEndpoint = BASE_URL . '/modules/user-management/includes/activity-logs-data.php';
?>

<link href="<?= BASE_URL ?>/modules/user-management/assets/css/user-management.css" rel="stylesheet">

<?php
$pageBannerIcon        = 'history';
$pageBannerDescription = 'Full Super Admin audit trail across all modules. Updates live as new events are recorded.';
renderBreadcrumbs($breadcrumbs);
?>

<div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div></div>
    <button type="button" class="btn btn-outline-primary btn-sm"
            data-sms-export-csv="#adminLogTable"
            data-sms-export-rows="tbody tr.log-row"
            data-sms-export-filename="sms2-activity-logs.csv">
        <?= smsIcon('file-export', ['class' => 'me-2']) ?>Export CSV
    </button>
</div>

<div class="row g-3 mb-4 dashboard-stats">
    <?php foreach ([
        ['key' => 'total',   'label' => 'Total Events', 'value' => $total,   'icon' => 'list',        'type' => 'primary'],
        ['key' => 'logins',  'label' => 'Login Events', 'value' => $logins,  'icon' => 'login',     'type' => 'info'],
        ['key' => 'changes', 'label' => 'Data Changes', 'value' => $changes, 'icon' => 'database',  'type' => 'warning'],
        ['key' => 'exports', 'label' => 'Exports',      'value' => $exports, 'icon' => 'file-export', 'type' => 'success'],
    ] as $sc): ?>
        <div class="col-6 col-xl-3">
            <section class="card stat-card <?= $sc['type'] ?>">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3"><?= smsIcon($sc['icon']) ?></div>
                    <div>
                        <h6 class="text-muted mb-0 small"><?= $sc['label'] ?></h6>
                        <h4 class="mb-0 fw-bold" data-um-log-stat="<?= e($sc['key']) ?>"><?= $sc['value'] ?></h4>
                    </div>
                </div>
            </section>
        </div>
    <?php endforeach; ?>
</div>

<section class="card sms-sec-card mb-3">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h6 fw-semibold mb-0">Filter logs</h2>
                <p class="small text-muted mb-0">Search by user, then narrow by action, module, or date. Export downloads the currently visible rows.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="um-live-badge" id="adminLogLiveBadge" title="Polling every 3 seconds">
                    <span class="um-live-dot" aria-hidden="true"></span>
                    <span id="adminLogLiveLabel">Live</span>
                </span>
                <span class="small text-muted" id="adminLogSynced"><?= e($payload['synced_at']) ?></span>
                <span class="small text-muted" id="adminLogCount"><?= $total ?> shown</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="adminLogClear">Clear filters</button>
            </div>
        </div>
        <div class="row g-2 g-md-3 um-log-filters">
            <div class="col-md-6 col-xl-3">
                <label class="form-label small mb-1" for="logUserFilter">User</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><?= smsIcon('search', ['style' => 'font-size:.72rem;']) ?></span>
                    <input type="text" id="logUserFilter" class="form-control" placeholder="Name…">
                </div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="logActionFilter">Action type</label>
                <select id="logActionFilter" class="form-select form-select-sm">
                    <option value="">All actions</option>
                    <?php foreach (array_keys($actionOptions) as $actionName): ?>
                        <option value="<?= e($actionName) ?>"><?= e(ucfirst(str_replace('_', ' ', $actionName))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="logModuleFilter">Module</label>
                <select id="logModuleFilter" class="form-select form-select-sm">
                    <option value="">All modules</option>
                    <?php foreach (array_keys($moduleOptions) as $modName): ?>
                        <option value="<?= e($modName) ?>"><?= e($modName) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="logDateFrom">Date from</label>
                <input type="date" id="logDateFrom" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small mb-1" for="logDateTo">Date to</label>
                <input type="date" id="logDateTo" class="form-control form-control-sm">
            </div>
        </div>
    </div>
</section>

<section class="card sms-sec-card">
    <div class="card-body p-0">
        <div class="um-log-scroll table-responsive">
            <table class="table submodule-table align-middle mb-0" id="adminLogTable"
                   data-live-url="<?= e($liveEndpoint) ?>"
                   data-latest-id="<?= (int) $payload['latest_id'] ?>">
                <thead class="um-log-thead">
                    <tr>
                        <th style="padding-left:1.2rem;width:42px;">#</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Detail</th>
                        <th>Module</th>
                        <th>IP Address</th>
                        <th style="white-space:nowrap;">Timestamp</th>
                    </tr>
                </thead>
                <tbody id="logTableBody">
                    <?php if (!$logs): ?>
                        <tr class="admin-log-empty">
                            <td colspan="7" class="text-center text-muted py-4">No activity logs yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log):
                            $userLabel = (string) ($log['user'] ?: 'System');
                            ?>
                            <tr class="log-row"
                                data-id="<?= (int) $log['id'] ?>"
                                data-action="<?= e((string) $log['action']) ?>"
                                data-user="<?= e(strtolower($userLabel)) ?>"
                                data-module="<?= e((string) $log['module']) ?>"
                                data-date="<?= e((string) ($log['log_date'] ?? '')) ?>">
                                <td class="text-muted" style="padding-left:1.2rem;font-size:.75rem;"><?= (int) $log['id'] ?></td>
                                <td>
                                    <div class="um-user-cell">
                                        <span class="um-avatar a"><?= e(strtoupper(substr($userLabel, 0, 1))) ?></span>
                                        <div>
                                            <span class="um-user-name"><?= e($userLabel) ?></span>
                                            <span class="um-user-email"><?= e(ucfirst((string) $log['role'])) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="log-action-badge <?= e((string) $log['action']) ?>">
                                        <?= $log['icon_html'] ?>
                                        <?= e((string) $log['action_label']) ?>
                                    </span>
                                </td>
                                <td style="max-width:260px;font-size:.8rem;"><?= e((string) $log['detail']) ?></td>
                                <td style="font-size:.78rem;color:var(--sms-text-muted);"><?= e((string) $log['module']) ?></td>
                                <td><code style="font-size:.72rem;color:var(--sms-text-faint);"><?= e((string) $log['ip']) ?></code></td>
                                <td style="font-size:.75rem;white-space:nowrap;color:var(--sms-text-muted);"><?= e((string) $log['time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="admin-log-empty-filter" hidden>
                        <td colspan="7" class="text-center text-muted py-4">No logs match the selected filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script src="<?= BASE_URL ?>/modules/user-management/assets/js/user-management.js?v=20260918"></script>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
