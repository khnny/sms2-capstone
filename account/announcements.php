<?php
/**
 * SMS 2 – Admin Announcements (visible on student dashboards in real time).
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/audit.php';
require_once ROOT_PATH . '/includes/announcements.php';

requireAdminAccountSettings();
smsEnsureAnnouncementTables();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $error = 'Security check failed. Refresh the page and try again.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'publish') {
            $result = smsAnnouncementPublish(
                (string) ($_POST['title'] ?? ''),
                (string) ($_POST['body'] ?? ''),
                isset($_FILES['image']) && is_array($_FILES['image']) ? $_FILES['image'] : null
            );
            if (!empty($result['ok'])) {
                $_SESSION['flash_admin_success'] = 'Announcement published. Students will see it on their dashboard.';
                header('Location: ' . BASE_URL . '/account/announcements.php');
                exit;
            }
            $error = (string) ($result['error'] ?? 'Could not publish.');
        } elseif ($action === 'unpublish' || $action === 'republish') {
            $result = smsAnnouncementSetStatus(
                (int) ($_POST['id'] ?? 0),
                $action === 'republish' ? 'published' : 'unpublished'
            );
            if (!empty($result['ok'])) {
                $_SESSION['flash_admin_success'] = $action === 'republish'
                    ? 'Announcement is visible to students again.'
                    : 'Announcement hidden from student dashboards.';
                header('Location: ' . BASE_URL . '/account/announcements.php');
                exit;
            }
            $error = (string) ($result['error'] ?? 'Could not update.');
        } elseif ($action === 'delete') {
            $result = smsAnnouncementDelete((int) ($_POST['id'] ?? 0));
            if (!empty($result['ok'])) {
                $_SESSION['flash_admin_success'] = 'Announcement deleted.';
                header('Location: ' . BASE_URL . '/account/announcements.php');
                exit;
            }
            $error = (string) ($result['error'] ?? 'Could not delete.');
        }
    }
}

if (!empty($_SESSION['flash_admin_success'])) {
    $success = (string) $_SESSION['flash_admin_success'];
    unset($_SESSION['flash_admin_success']);
}

$all = smsAnnouncementFetch(false, 50);
$published = array_values(array_filter($all, static fn(array $row): bool => ($row['status'] ?? '') === 'published'));

$pageTitle = 'Announcements';
$activeModule = 'dashboard';
$activePage = 'announcements';
$breadcrumbs = [
    ['label' => 'System', 'url' => BASE_URL . '/account/announcements.php'],
    ['label' => 'Announcements', 'url' => null],
];
$pageBannerIcon = 'fa-bullhorn';
$pageBannerDescription = 'Publish announcements that appear in real time on student dashboards.';

require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-5">
        <section class="card sms-sec-card h-100">
            <div class="card-body">
                <div class="sms-sec-card-head mb-3">
                    <div class="sms-sec-card-title">
                        <span class="sms-sec-icon"><?= smsIcon('bullhorn', ['aria-hidden' => 'true']) ?></span>
                        <div>
                            <h2 class="h5 fw-bold mb-0">New announcement</h2>
                            <p class="sms-sec-lead mb-0 mt-1">Students see published messages on their dashboard within a few seconds.</p>
                        </div>
                    </div>
                </div>
                <form method="post" autocomplete="off" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="publish">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="annTitle">Title</label>
                        <input type="text" class="form-control" id="annTitle" name="title" maxlength="180" required placeholder="e.g. Enrollment reminder">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="annBody">Message</label>
                        <textarea class="form-control" id="annBody" name="body" rows="6" maxlength="4000" required placeholder="Write the announcement students should read."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="annImage">PNG image</label>
                        <input type="file" class="form-control" id="annImage" name="image" accept="image/png,.png">
                        <div class="form-text">Optional. Students will see this picture with the announcement.</div>
                        <img id="annImagePreview" class="sms-ann-image mt-2" alt="PNG preview" hidden>
                    </div>
                    <button type="submit" class="btn btn-sms-primary">
                        <?= smsIcon('send', ['class' => 'me-1']) ?>Publish to students
                    </button>
                </form>
            </div>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="card sms-sec-card h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h2 class="h6 fw-semibold mb-0">Live on student dashboards</h2>
                        <p class="small text-muted mb-0">This is what students currently see.</p>
                    </div>
                    <span class="um-live-badge" id="adminAnnLiveBadge">
                        <span class="um-live-dot" aria-hidden="true"></span>
                        <span id="adminAnnLiveLabel">Live</span>
                    </span>
                </div>
                <div id="adminAnnPublished" data-empty="No published announcements yet.">
                    <?php if (!$published): ?>
                        <p class="text-muted small mb-0">No published announcements yet.</p>
                    <?php else: ?>
                        <?php foreach ($published as $row): ?>
                            <?php $imageUrl = smsAnnouncementImageUrl((int) $row['id'], (string) ($row['image_path'] ?? '')); ?>
                            <article class="sms-ann-item">
                                <h3><?= e((string) $row['title']) ?></h3>
                                <?php if ($imageUrl !== ''): ?>
                                    <img class="sms-ann-image" src="<?= e($imageUrl) ?>" alt="">
                                <?php endif; ?>
                                <p><?= nl2br(e((string) $row['body'])) ?></p>
                                <small><?= e((string) ($row['created_by_name'] ?: 'Admin')) ?> · <?= e((string) ($row['published_label'] ?: $row['updated_label'])) ?></small>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<section class="card sms-sec-card mt-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table submodule-table align-middle mb-0" id="adminAnnTable"
                   data-live-url="<?= e(BASE_URL . '/account/announcements-data.php') ?>">
                <thead>
                    <tr>
                        <th style="padding-left:1.2rem;">Title</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="adminAnnBody">
                    <?php if (!$all): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No announcements yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($all as $row): ?>
                            <tr>
                                <td style="padding-left:1.2rem;">
                                    <strong><?= e((string) $row['title']) ?></strong>
                                    <div class="small text-muted" style="max-width:420px;"><?= e((string) $row['body']) ?></div>
                                </td>
                                <td><?= ($row['status'] ?? '') === 'published' ? 'Published' : 'Hidden' ?></td>
                                <td class="small text-muted"><?= e((string) ($row['published_label'] ?: $row['updated_label'])) ?></td>
                                <td class="text-end" style="padding-right:1.2rem;">
                                    <form method="post" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <?php if (($row['status'] ?? '') === 'published'): ?>
                                            <input type="hidden" name="action" value="unpublish">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Hide</button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="republish">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Publish</button>
                                        <?php endif; ?>
                                    </form>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this announcement?');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<style>
.sms-ann-item { border: 1px solid var(--sms-border); border-radius: 12px; padding: .85rem 1rem; margin-bottom: .7rem; background: var(--sms-surface); }
.sms-ann-item h3 { font-size: .92rem; font-weight: 750; margin: 0; color: var(--sms-heading); }
.sms-ann-item p { margin: .35rem 0 .4rem; font-size: .82rem; color: var(--sms-text); white-space: pre-wrap; }
.sms-ann-item small { color: var(--sms-text-muted); font-size: .72rem; }
.sms-ann-image { display: block; width: 100%; max-height: 320px; object-fit: contain; border-radius: 10px; margin: .55rem 0; background: var(--sms-surface-muted, #f8fafc); }
.um-live-badge { display: inline-flex; align-items: center; gap: .4rem; padding: .22rem .65rem; border-radius: 999px; background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.3); font-size: .72rem; font-weight: 700; color: #10b981; }
.um-live-badge.is-stale { background: rgba(245,158,11,.12); border-color: rgba(245,158,11,.3); color: #d97706; }
.um-live-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; position: relative; }
.um-live-dot::after { content: ''; position: absolute; inset: -4px; border-radius: 50%; background: currentColor; opacity: .35; animation: um-live-pulse 1.8s ease-out infinite; }
@keyframes um-live-pulse { 0% { transform: scale(.6); opacity: .45; } 100% { transform: scale(2.2); opacity: 0; } }
</style>

<script>
(function () {
    var endpoint = '<?= BASE_URL ?>/account/announcements-data.php';
    var publishedBox = document.getElementById('adminAnnPublished');
    var badge = document.getElementById('adminAnnLiveBadge');
    var label = document.getElementById('adminAnnLiveLabel');
    var stamp = '';
    var inFlight = false;

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    function setLive(ok) {
        if (badge) badge.classList.toggle('is-stale', !ok);
        if (label) label.textContent = ok ? 'Live' : 'Reconnecting';
    }
    function renderPublished(rows) {
        if (!publishedBox) return;
        if (!rows || !rows.length) {
            publishedBox.innerHTML = '<p class="text-muted small mb-0">No published announcements yet.</p>';
            return;
        }
        publishedBox.innerHTML = rows.map(function (row) {
            var image = row.image_url
                ? '<img class="sms-ann-image" src="' + esc(row.image_url) + '" alt="">'
                : '';
            return '<article class="sms-ann-item"><h3>' + esc(row.title) + '</h3>' + image + '<p>' + esc(row.body).replace(/\n/g, '<br>') + '</p><small>' + esc(row.posted_by) + ' · ' + esc(row.posted_at) + '</small></article>';
        }).join('');
    }
    function poll() {
        if (inFlight || document.hidden) return;
        inFlight = true;
        fetch(endpoint, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin', cache: 'no-store' })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || !data.ok) { setLive(false); return; }
                setLive(true);
                if (data.stamp === stamp) return;
                stamp = data.stamp;
                renderPublished(data.announcements || []);
            })
            .catch(function () { setLive(false); })
            .finally(function () { inFlight = false; });
    }
    poll();
    setInterval(poll, 3000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });

    var imageInput = document.getElementById('annImage');
    var imagePreview = document.getElementById('annImagePreview');
    if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function () {
            var file = imageInput.files && imageInput.files[0];
            if (!file) {
                imagePreview.hidden = true;
                imagePreview.removeAttribute('src');
                return;
            }
            if (file.type !== 'image/png') {
                imagePreview.hidden = true;
                imageInput.value = '';
                alert('Please choose a PNG image.');
                return;
            }
            var reader = new FileReader();
            reader.onload = function () {
                imagePreview.src = String(reader.result || '');
                imagePreview.hidden = false;
            };
            reader.readAsDataURL(file);
        });
    }
})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
