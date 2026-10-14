<?php
declare(strict_types=1);

require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';

function panelProfileRequire(): void
{
    requireAuth();
    if (getCurrentUserRoleKey() !== 'panel') {
        http_response_code(403);
        exit('Forbidden');
    }
}

function panelProfileAvailability(): array
{
    $crad = cradDb();
    if (!$crad instanceof PDO) {
        return ['availability_status' => 'Pending', 'notes' => ''];
    }
    try {
        $stmt = $crad->prepare("SELECT availability_status, availability_windows_json, notes, updated_at FROM `crad_panel_member_availability` WHERE panel_user_id = ?");
        $stmt->execute([(int) getCurrentUserId()]);
        return $stmt->fetch() ?: ['availability_status' => 'Pending', 'availability_windows_json' => null, 'notes' => '', 'updated_at' => null];
    } catch (Throwable $e) {
        error_log('Panel availability read failed: ' . $e->getMessage());
        return ['availability_status' => 'Pending', 'notes' => ''];
    }
}

function panelProfileSaveAvailability(): ?array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['panel_action'] ?? '') !== 'update_availability') {
        return null;
    }
    if (!csrfVerify()) {
        return ['type' => 'danger', 'message' => 'Security token expired.'];
    }
    $status = is_string($_POST['availability_status'] ?? null)
        ? trim($_POST['availability_status'])
        : '';
    if (!in_array($status, ['Available', 'Pending', 'Unavailable'], true)) {
        return ['type' => 'danger', 'message' => 'Select a valid availability status.'];
    }
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $postedDays = is_array($_POST['availability_days'] ?? null)
        ? array_values(array_filter($_POST['availability_days'], 'is_string'))
        : [];
    $selectedDays = array_values(array_intersect($days, $postedDays));
    $startInputs = is_array($_POST['availability_start'] ?? null) ? $_POST['availability_start'] : [];
    $endInputs = is_array($_POST['availability_end'] ?? null) ? $_POST['availability_end'] : [];
    $windows = [];
    foreach ($selectedDays as $day) {
        if (!is_string($startInputs[$day] ?? null) || !is_string($endInputs[$day] ?? null)) {
            return ['type' => 'danger', 'message' => 'Choose a valid start and end time for each selected day.'];
        }
        $start = trim($startInputs[$day]);
        $end = trim($endInputs[$day]);
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start)
            || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end)
            || $end <= $start
        ) {
            return ['type' => 'danger', 'message' => 'Choose a valid start and end time for each selected day.'];
        }
        $windows[$day] = [['start' => $start, 'end' => $end]];
    }
    if ($status === 'Available' && $windows === []) {
        return ['type' => 'danger', 'message' => 'Add at least one weekly day and time range before setting availability to Available.'];
    }
    $crad = cradDb();
    if (!$crad instanceof PDO) {
        return ['type' => 'danger', 'message' => 'CRAD database unavailable.'];
    }
    try {
        $stmt = $crad->prepare(
            "INSERT INTO `crad_panel_member_availability`
                (panel_user_id, availability_status, availability_windows_json, notes, created_at, updated_at)
             VALUES
                (?, ?, ?, '', NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                availability_status = VALUES(availability_status),
                availability_windows_json = VALUES(availability_windows_json),
                updated_at = NOW()"
        );
        $stmt->execute([
            (int) getCurrentUserId(),
            $status,
            json_encode($windows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
        return ['type' => 'success', 'message' => 'Availability and weekly time windows saved.'];
    } catch (Throwable $e) {
        error_log('Panel availability save failed: ' . $e->getMessage());
        return ['type' => 'danger', 'message' => 'Unable to save availability.'];
    }
}

function renderPanelProfilePage(string $mode): void
{
    panelProfileRequire();
    $notice = panelProfileSaveAvailability();
    $availability = panelProfileAvailability();
    $status = (string) ($availability['availability_status'] ?? 'Pending');
    $storedWindows = json_decode((string) ($availability['availability_windows_json'] ?? ''), true);
    $storedWindows = is_array($storedWindows) ? $storedWindows : [];
    $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    ?>
    <div class="glass-dashboard">
        <?php if ($notice): ?><div class="alert alert-<?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endif; ?>
        <?php if ($mode === 'profile'): ?>
            <section class="glass-panel p-4">
                <h5 class="mb-3"><?= smsIcon('user', ['class' => 'me-2 text-primary']) ?>My Profile</h5>
                <div class="row g-3">
                    <div class="col-md-6"><small class="text-muted">Full Name</small><div class="fw-bold"><?= e(getCurrentUserName()) ?></div></div>
                    <div class="col-md-6"><small class="text-muted">Email</small><div><?= e((string) ($_SESSION['user_email'] ?? '')) ?></div></div>
                    <div class="col-md-6"><small class="text-muted">Role</small><div><span class="badge text-bg-primary">Panel Member</span></div></div>
                    <div class="col-md-6"><small class="text-muted">Availability</small><div><?= e($status) ?></div></div>
                    <div class="col-12"><small class="text-muted">Weekly time windows</small><div><?php
                        $windowLabels = [];
                        foreach ($storedWindows as $day => $intervals) {
                            foreach (is_array($intervals) ? $intervals : [] as $interval) {
                                if (is_array($interval)) {
                                    $windowLabels[] = (string) $day . ' ' . (string) ($interval['start'] ?? '') . '–' . (string) ($interval['end'] ?? '');
                                }
                            }
                        }
                        echo e($windowLabels ? implode(', ', $windowLabels) : 'No weekly time windows saved.');
                    ?></div></div>
                </div>
            </section>
        <?php else: ?>
            <div class="row g-3 mb-4 dashboard-stats">
                <div class="col-6 col-xl-3"><section class="card stat-card primary"><div class="card-body d-flex align-items-center"><div class="stat-icon me-3"><?= smsIcon('folder-open') ?></div><div><span class="text-muted small d-block">Total Records</span><h3 class="mb-0">1</h3></div></div></section></div>
                <div class="col-6 col-xl-3"><section class="card stat-card success"><div class="card-body d-flex align-items-center"><div class="stat-icon me-3"><?= smsIcon('user-check') ?></div><div><span class="text-muted small d-block">Assigned</span><h3 class="mb-0"><?= $status === 'Available' ? '1' : '0' ?></h3></div></div></section></div>
                <div class="col-6 col-xl-3"><section class="card stat-card warning"><div class="card-body d-flex align-items-center"><div class="stat-icon me-3"><?= smsIcon('clock') ?></div><div><span class="text-muted small d-block">Pending</span><h3 class="mb-0"><?= $status === 'Pending' ? '1' : '0' ?></h3></div></div></section></div>
                <div class="col-6 col-xl-3"><section class="card stat-card info"><div class="card-body d-flex align-items-center"><div class="stat-icon me-3"><?= smsIcon('toggle-on') ?></div><div><span class="text-muted small d-block">Availability</span><h3 class="mb-0"><?= e($status) ?></h3></div></div></section></div>
            </div>
            <section class="glass-panel p-4">
                <h5 class="mb-3"><?= smsIcon('user-check', ['class' => 'me-2 text-primary']) ?>Availability Control</h5>
                <p class="text-muted">Set your overall status and the weekly times when you can serve. Configured time windows are used when CRAD generates defense schedule options.</p>
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="panel_action" value="update_availability">
                    <div class="btn-group mb-3" role="group" aria-label="Panel availability">
                        <?php foreach (['Available', 'Pending', 'Unavailable'] as $option): ?>
                            <input class="btn-check" type="radio" name="availability_status" id="panel-availability-<?= strtolower($option) ?>" value="<?= e($option) ?>" <?= $status === $option ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="panel-availability-<?= strtolower($option) ?>"><?= e($option) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <fieldset class="mb-3">
                        <legend class="fs-6 fw-semibold">Weekly availability windows</legend>
                        <div class="row g-2">
                            <?php foreach ($weekdays as $day):
                                $interval = $storedWindows[$day][0] ?? [];
                                $dayStart = (string) ($interval['start'] ?? '09:00');
                                $dayEnd = (string) ($interval['end'] ?? '17:00');
                            ?>
                                <div class="col-12 col-md-6 col-xl-4">
                                    <div class="border rounded-3 p-2 h-100">
                                        <label class="form-check-label fw-semibold">
                                            <input class="form-check-input me-2" type="checkbox" name="availability_days[]" value="<?= e($day) ?>" <?= isset($storedWindows[$day]) ? 'checked' : '' ?>>
                                            <?= e($day) ?>
                                        </label>
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            <label class="visually-hidden" for="availability-start-<?= strtolower($day) ?>"><?= e($day) ?> start time</label>
                                            <input class="form-control form-control-sm" type="time" id="availability-start-<?= strtolower($day) ?>" name="availability_start[<?= e($day) ?>]" value="<?= e($dayStart) ?>" aria-label="<?= e($day) ?> start time">
                                            <span aria-hidden="true">to</span>
                                            <label class="visually-hidden" for="availability-end-<?= strtolower($day) ?>"><?= e($day) ?> end time</label>
                                            <input class="form-control form-control-sm" type="time" id="availability-end-<?= strtolower($day) ?>" name="availability_end[<?= e($day) ?>]" value="<?= e($dayEnd) ?>" aria-label="<?= e($day) ?> end time">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="form-text text-muted d-block mt-2">For days without a saved time window, scheduling keeps the existing status-based availability behavior.</small>
                    </fieldset>
                    <div><button class="btn btn-sms-primary"><?= smsIcon('save', ['class' => 'me-1']) ?>Save Availability</button></div>
                </form>
            </section>
        <?php endif; ?>
    </div>
    <?php
}
