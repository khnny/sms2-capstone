<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/audit.php';
require_once ROOT_PATH . '/includes/announcements.php';
requireAuth();

$announcementRole = function_exists('smsNormalizeRoleKey') ? smsNormalizeRoleKey(getCurrentUserRoleKey()) : getCurrentUserRoleKey();
$canManageAnnouncements = smsAnnouncementCanManage();
$announcementError = '';
$announcementSuccess = (string) ($_SESSION['flash_communication_success'] ?? '');
unset($_SESSION['flash_communication_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canManageAnnouncements) {
        http_response_code(403);
        exit('Forbidden');
    }
    if (!csrfVerify()) {
        $announcementError = 'Your session check expired. Refresh the page and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $result = smsAnnouncementSave($_POST, $id > 0 ? $id : null, isset($_FILES['image']) ? $_FILES['image'] : null);
            if (!empty($result['ok'])) {
                $_SESSION['flash_communication_success'] = $id > 0 ? 'Announcement updated.' : 'Announcement saved.';
                header('Location: ' . BASE_URL . '/communication/announcements.php');
                exit;
            }
            $announcementError = (string) ($result['error'] ?? 'Could not save the announcement.');
        } elseif ($action === 'status') {
            $result = smsAnnouncementSetStatus((int) ($_POST['id'] ?? 0), (string) ($_POST['status'] ?? ''));
            if (!empty($result['ok'])) {
                $_SESSION['flash_communication_success'] = 'Announcement status updated.';
                header('Location: ' . BASE_URL . '/communication/announcements.php');
                exit;
            }
            $announcementError = (string) ($result['error'] ?? 'Could not update the announcement.');
        } elseif ($action === 'delete') {
            $result = smsAnnouncementDelete((int) ($_POST['id'] ?? 0));
            if (!empty($result['ok'])) {
                $_SESSION['flash_communication_success'] = 'Announcement deleted.';
                header('Location: ' . BASE_URL . '/communication/announcements.php');
                exit;
            }
            $announcementError = (string) ($result['error'] ?? 'Could not delete the announcement.');
        }
    }
}

$allAnnouncements = $canManageAnnouncements
    ? smsAnnouncementFetch(false, 100)
    : smsAnnouncementFetch(true, 100, $announcementRole);
$announcementStorageAvailable = db() instanceof PDO;
$announcements = $allAnnouncements;
$editingAnnouncement = null;
if ($canManageAnnouncements && (int) ($_GET['edit'] ?? 0) > 0) {
    foreach ($allAnnouncements as $row) {
        if ((int) $row['id'] === (int) $_GET['edit']) {
            $editingAnnouncement = $row;
            break;
        }
    }
}
$announcementCategories = ['General', 'Research', 'Defense', 'Deadline', 'Important', 'Submission', 'Consultation', 'Policy'];
$announcementAudiences = [
    'all' => 'All users', 'student' => 'Students', 'adviser' => 'Advisers',
    'research_coordinator' => 'Research Coordinators', 'department_head' => 'Department Heads',
    'panel' => 'Panelists', 'crad_officer' => 'CRAD Officers', 'research_director' => 'Research Directors',
    'grammarian' => 'Grammarians', 'department_chair' => 'Department Chairs', 'research_office' => 'Research Office',
    'research_grant' => 'Research Grant', 'review_committee' => 'Review Committee', 'vpaa' => 'VPAA',
    'superadmin' => 'Super Admins', 'sms_admin' => 'Admins', 'admission' => 'Admissions', 'registrar' => 'Registrars',
    'finance' => 'Finance', 'hr' => 'HR', 'it_office' => 'IT Office', 'osa' => 'OSA', 'qa' => 'QA Office',
];

$pageTitle = 'Announcement Bulletin';
$activeModule = '';
$activePage = 'communication-announcements';
$breadcrumbs = [['label' => 'Communication', 'url' => null], ['label' => 'Announcements', 'url' => null]];
require_once ROOT_PATH . '/includes/breadcrumbs.php';
?>
<?php require_once ROOT_PATH . '/includes/layout-start.php'; ?>
<?php renderBreadcrumbs($breadcrumbs); ?>

<main class="communication-prototype" data-communication-page="announcements" aria-busy="true">
    <header class="communication-page-header">
        <div>
            <span class="communication-kicker">Research communication</span>
            <h1>Announcement bulletin</h1>
            <p>Research notices, defense updates, and important deadlines for your role.</p>
        </div>
        <?php if ($canManageAnnouncements): ?>
            <a class="btn btn-sms-primary btn-sm" href="#announcementComposer"><?= smsIcon('plus', ['aria-hidden' => 'true']) ?> Create announcement</a>
        <?php endif; ?>
    </header>

    <section class="communication-summary-strip" aria-label="Announcement summary">
        <div><span><?= $canManageAnnouncements ? 'All notices' : 'Available notices' ?></span><strong><?= count($announcements) ?></strong></div>
        <div><span>Published</span><strong><?= count(array_filter($announcements, static fn(array $a): bool => $a['status'] === 'published')) ?></strong></div>
        <div><span>Categories</span><strong><?= count(array_unique(array_column($announcements, 'category'))) ?></strong></div>
        <div class="communication-summary-note"><?= smsIcon('info-circle', ['aria-hidden' => 'true']) ?> <?= $canManageAnnouncements ? 'Manage notices for the research community.' : 'Showing notices published for your audience.' ?></div>
    </section>

    <?php if ($announcementSuccess !== ''): ?><div class="alert alert-success py-2" role="status"><?= e($announcementSuccess) ?></div><?php endif; ?>
    <?php if ($announcementError !== ''): ?><div class="alert alert-danger py-2" role="alert"><?= e($announcementError) ?></div><?php endif; ?>
    <?php if (!$announcementStorageAvailable): ?><div class="alert alert-warning py-2" role="alert">Announcements are temporarily unavailable. Please try again shortly.</div><?php endif; ?>

    <?php if ($canManageAnnouncements): ?>
        <section class="communication-surface communication-composer" id="announcementComposer" aria-labelledby="announcementComposerTitle">
            <div class="communication-surface-heading">
                <div>
                    <h2 id="announcementComposerTitle"><?= $editingAnnouncement ? 'Edit announcement' : 'Create announcement' ?></h2>
                    <p>Choose the audience and publication timing for this notice.</p>
                </div>
                <?php if ($editingAnnouncement): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(BASE_URL . '/communication/announcements.php') ?>">Cancel edit</a><?php endif; ?>
            </div>
            <form method="post" enctype="multipart/form-data" class="communication-announcement-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) ($editingAnnouncement['id'] ?? 0) ?>">
                <label>Title<input class="form-control" type="text" name="title" maxlength="180" required value="<?= e((string) ($editingAnnouncement['title'] ?? '')) ?>" placeholder="e.g. Research proposal submissions"></label>
                <label>Category<select class="form-select" name="category" required><?php foreach ($announcementCategories as $category): ?><option value="<?= e($category) ?>" <?= ($editingAnnouncement['category'] ?? 'General') === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></label>
                <label>Target audience<select class="form-select" name="audience" required><?php foreach ($announcementAudiences as $audienceKey => $audienceLabel): ?><option value="<?= e($audienceKey) ?>" <?= ($editingAnnouncement['audience'] ?? 'all') === $audienceKey ? 'selected' : '' ?>><?= e($audienceLabel) ?></option><?php endforeach; ?></select></label>
                <label>Publication status<select class="form-select" name="status" required><?php foreach (['draft' => 'Draft', 'published' => 'Published', 'unpublished' => 'Unpublished', 'archived' => 'Archived'] as $statusKey => $statusLabel): ?><option value="<?= e($statusKey) ?>" <?= ($editingAnnouncement['status'] ?? 'draft') === $statusKey ? 'selected' : '' ?>><?= e($statusLabel) ?></option><?php endforeach; ?></select></label>
                <label>Publish on<input class="form-control" type="date" name="scheduled_for" value="<?= e((string) ($editingAnnouncement['scheduled_for'] ?? '')) ?>"></label>
                <label>Expiry date<input class="form-control" type="date" name="expires_at" value="<?= e((string) ($editingAnnouncement['expires_at'] ?? '')) ?>"></label>
                <label class="communication-form-body">Announcement content<textarea class="form-control" name="body" rows="4" maxlength="4000" required><?= e((string) ($editingAnnouncement['body'] ?? '')) ?></textarea></label>
                <label class="communication-form-image">Optional image<input class="form-control" type="file" name="image" accept="image/png,.png"><span class="form-text">PNG, up to 5 MB</span></label>
                <div class="communication-form-actions"><button type="submit" class="btn btn-sms-primary btn-sm"><?= smsIcon('save', ['aria-hidden' => 'true']) ?> <?= $editingAnnouncement ? 'Save changes' : 'Save announcement' ?></button></div>
            </form>
        </section>
    <?php endif; ?>

    <section class="communication-surface" aria-labelledby="announcementFiltersTitle">
        <div class="communication-surface-heading">
            <div>
                <h2 id="announcementFiltersTitle">Filter announcements</h2>
                <p>Refine by category, audience, status, or published date.</p>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-ann-reset>Reset filters</button>
        </div>
        <div class="communication-filters">
            <label>Category
                <select class="form-select" data-ann-filter="category">
                    <option value="">All categories</option>
                    <?php foreach ($announcementCategories as $category): ?><option><?= e($category) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Audience
                <select class="form-select" data-ann-filter="audience">
                    <option value="">All audiences</option>
                    <?php foreach (array_unique(array_column($announcements, 'audience')) as $audience): ?>
                        <option value="<?= e((string) $audience) ?>"><?= e($announcementAudiences[$audience] ?? ucfirst(str_replace('_', ' ', (string) $audience))) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Status
                <select class="form-select" data-ann-filter="status">
                    <option value="">All statuses</option>
                    <?php if ($canManageAnnouncements): ?><option>Published</option><option>Draft</option><option>Unpublished</option><option>Archived</option><?php else: ?><option>Published</option><?php endif; ?>
                </select>
            </label>
            <label>Published from
                <input class="form-control" type="date" data-ann-filter="from" aria-label="Published from">
            </label>
            <label>Published to
                <input class="form-control" type="date" data-ann-filter="to" aria-label="Published to">
            </label>
            <label class="communication-filter-search">Search
                <input class="form-control" type="search" data-ann-filter="search" placeholder="Title, message, audience">
            </label>
        </div>
    </section>

    <div class="communication-results-heading">
        <div><h2>Notices</h2><span data-ann-count aria-live="polite"></span></div>
        <span class="communication-results-caption">Newest first</span>
    </div>
    <div class="communication-announcement-list" data-ann-list>
        <?php foreach ($announcements as $announcement): ?>
            <?php $announcementDateLabel = !empty($announcement['scheduled_for']) ? date('M j, Y', strtotime((string) $announcement['scheduled_for'])) : (string) ($announcement['published_label'] ?: $announcement['updated_label']); ?>
            <article class="communication-announcement-card"
                     data-announcement
                     data-category="<?= e((string) $announcement['category']) ?>"
                     data-audience="<?= e((string) $announcement['audience']) ?>"
                     data-status="<?= e((string) $announcement['status']) ?>"
                     data-date="<?= e((string) ($announcement['scheduled_for'] ?: $announcement['date_iso'])) ?>"
                     data-search="<?= e(strtolower(implode(' ', [$announcement['title'], $announcement['body'], $announcement['audience'], $announcement['created_by_name']]))) ?>">
                <span class="communication-announcement-accent <?= e(strtolower($announcement['category'])) ?>" aria-hidden="true"></span>
                <div class="communication-announcement-content">
                    <div class="communication-announcement-topline">
                        <div class="communication-announcement-tags">
                            <span class="communication-type-pill <?= e(strtolower((string) $announcement['category'])) ?>"><?= e((string) $announcement['category']) ?></span>
                        </div>
                        <span class="communication-status <?= e(strtolower((string) $announcement['status'])) ?>"><?= e(ucfirst((string) $announcement['status'])) ?></span>
                    </div>
                    <h3><?= e($announcement['title']) ?></h3>
                    <p><?= e(function_exists('mb_strimwidth') ? mb_strimwidth((string) $announcement['body'], 0, 220, '…') : substr((string) $announcement['body'], 0, 220)) ?></p>
                    <footer>
                        <span><?= smsIcon('users', ['aria-hidden' => 'true']) ?> <?= e($announcementAudiences[$announcement['audience']] ?? ucfirst(str_replace('_', ' ', (string) $announcement['audience']))) ?></span>
                        <span><?= smsIcon('calendar', ['aria-hidden' => 'true']) ?> <?= e($announcementDateLabel) ?></span>
                        <button type="button" class="communication-detail-trigger" data-ann-detail
                                data-title="<?= e((string) $announcement['title']) ?>"
                                data-body="<?= e((string) $announcement['body']) ?>"
                                data-category="<?= e((string) $announcement['category']) ?>"
                                data-audience="<?= e($announcementAudiences[$announcement['audience']] ?? ucfirst(str_replace('_', ' ', (string) $announcement['audience']))) ?>"
                                data-status="<?= e(ucfirst((string) $announcement['status'])) ?>"
                                data-date="<?= e($announcementDateLabel) ?>"
                                data-expiry="<?= e((string) ($announcement['expires_at'] ?? '')) ?>"
                                data-image="<?= e(smsAnnouncementImageUrl((int) $announcement['id'], (string) ($announcement['image_path'] ?? ''))) ?>"
                                data-author="<?= e((string) ($announcement['created_by_name'] ?: 'CRAD Research Office')) ?>">
                            <?= smsIcon('external-link-alt', ['aria-hidden' => 'true']) ?> Details
                        </button>
                    </footer>
                    <?php if ($canManageAnnouncements): ?>
                        <div class="communication-announcement-actions">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(BASE_URL . '/communication/announcements.php?edit=' . (int) $announcement['id'] . '#announcementComposer') ?>"><?= smsIcon('pen', ['aria-hidden' => 'true']) ?> Edit</a>
                            <?php foreach (['published' => 'Unpublish', 'draft' => 'Publish', 'unpublished' => 'Publish', 'archived' => 'Restore'] as $currentStatus => $actionLabel): ?>
                                <?php if ($announcement['status'] === $currentStatus): ?>
                                    <form method="post" class="d-inline"><?= csrfField() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $announcement['id'] ?>"><input type="hidden" name="status" value="<?= $currentStatus === 'published' ? 'unpublished' : 'published' ?>"><button class="btn btn-sm btn-outline-primary" type="submit"><?= e($actionLabel) ?></button></form>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($announcement['status'] !== 'archived'): ?><form method="post" class="d-inline"><?= csrfField() ?><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int) $announcement['id'] ?>"><input type="hidden" name="status" value="archived"><button class="btn btn-sm btn-outline-secondary" type="submit">Archive</button></form><?php endif; ?>
                            <form method="post" class="d-inline" data-confirm-delete><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $announcement['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit"><?= smsIcon('trash', ['aria-hidden' => 'true']) ?> Delete</button></form>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="communication-empty-state" data-ann-empty hidden>
        <span><?= smsIcon('search', ['aria-hidden' => 'true']) ?></span>
        <strong><?= $announcements ? 'No announcements match these filters' : 'No announcements available yet' ?></strong>
        <p><?= $announcements ? 'Try another category, status, date range, or search phrase.' : ($canManageAnnouncements ? 'Create the first research notice for your community.' : 'New notices for your audience will appear here.') ?></p>
        <button type="button" class="btn btn-sm btn-outline-primary" data-ann-reset>Clear filters</button>
    </div>
    <div class="communication-live-status" data-ann-status role="status" aria-live="polite"></div>
</main>
<div class="modal fade" id="communicationAnnouncementModal" tabindex="-1" aria-labelledby="communicationAnnouncementTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content communication-event-modal">
        <div class="modal-header"><div><span class="communication-kicker">Announcement details</span><h2 class="modal-title" id="communicationAnnouncementTitle"></h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body"><div class="communication-detail-meta" data-ann-modal-meta></div><img class="communication-detail-image" data-ann-modal-image alt="" hidden><p class="communication-event-description" data-ann-modal-body></p></div>
        <div class="modal-footer"><button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
    </div></div>
</div>
<script src="<?= e(BASE_URL . '/assets/js/communication-prototype.js?v=2') ?>" defer></script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
