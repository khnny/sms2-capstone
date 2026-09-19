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
        $stmt = $crad->prepare("SELECT availability_status, notes, updated_at FROM panel_member_availability WHERE panel_user_id = ?");
        $stmt->execute([(int) getCurrentUserId()]);
        return $stmt->fetch() ?: ['availability_status' => 'Pending', 'notes' => '', 'updated_at' => null];
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
    $status = trim((string) ($_POST['availability_status'] ?? ''));
    if (!in_array($status, ['Available', 'Pending', 'Unavailable'], true)) {
        return ['type' => 'danger', 'message' => 'Select a valid availability status.'];
    }
    $crad = cradDb();
    if (!$crad instanceof PDO) {
        return ['type' => 'danger', 'message' => 'CRAD database unavailable.'];
    }
    try {
        $stmt = $crad->prepare(
            "INSERT INTO panel_member_availability
                (panel_user_id, availability_status, notes, created_at, updated_at)
             VALUES
                (?, ?, '', NOW(), NOW())
             ON DUPLICATE KEY UPDATE availability_status = VALUES(availability_status), updated_at = NOW()"
        );
        $stmt->execute([(int) getCurrentUserId(), $status]);
        return ['type' => 'success', 'message' => 'Availability saved.'];
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
                <p class="text-muted">Set Availability for Active Assignment Records</p>
                <form method="post">
                    <?= csrfField() ?>
                    <input type="hidden" name="panel_action" value="update_availability">
                    <div class="btn-group mb-3" role="group" aria-label="Panel availability">
                        <?php foreach (['Available', 'Pending', 'Unavailable'] as $option): ?>
                            <input class="btn-check" type="radio" name="availability_status" id="panel-availability-<?= strtolower($option) ?>" value="<?= e($option) ?>" <?= $status === $option ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="panel-availability-<?= strtolower($option) ?>"><?= e($option) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <div><button class="btn btn-sms-primary"><?= smsIcon('save', ['class' => 'me-1']) ?>Save Availability</button></div>
                </form>
            </section>
        <?php endif; ?>
    </div>
    <?php
}
