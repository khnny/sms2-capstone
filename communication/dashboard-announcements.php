<?php
require_once ROOT_PATH . '/includes/announcements.php';

$dashboardAnnouncementRole = function_exists('getCurrentUserRoleKey')
    ? getCurrentUserRoleKey()
    : 'guest';
if (function_exists('smsNormalizeRoleKey')) {
    $dashboardAnnouncementRole = smsNormalizeRoleKey($dashboardAnnouncementRole);
}
$dashboardAnnouncements = smsAnnouncementFetch(true, 20, $dashboardAnnouncementRole);
$dashboardAnnouncements = array_slice($dashboardAnnouncements, 0, 2);
?>
<section class="dashboard-announcements" aria-labelledby="dashboardImportantAnnouncements">
    <div class="dashboard-announcements-heading">
        <div>
            <span class="dashboard-announcements-kicker">For your audience</span>
            <h2 id="dashboardImportantAnnouncements">Important announcements</h2>
        </div>
        <a href="<?= e(BASE_URL . '/communication/announcements.php') ?>">View all <span aria-hidden="true">→</span></a>
    </div>
    <?php if ($dashboardAnnouncements): ?>
        <ul class="dashboard-announcements-list">
            <?php foreach ($dashboardAnnouncements as $announcement): ?>
                <li>
                    <span class="dashboard-announcement-category <?= e(strtolower((string) $announcement['category'])) ?>">
                        <?= e((string) $announcement['category']) ?>
                    </span>
                    <div>
                        <strong><?= e((string) $announcement['title']) ?></strong>
                        <p><?= e(function_exists('mb_strimwidth') ? mb_strimwidth((string) $announcement['body'], 0, 150, '…') : substr((string) $announcement['body'], 0, 150)) ?></p>
                    </div>
                    <time datetime="<?= e((string) ($announcement['scheduled_for'] ?: $announcement['date_iso'])) ?>">
                        <?= e((string) ($announcement['published_label'] ?: $announcement['updated_label'])) ?>
                    </time>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="dashboard-announcements-empty">No current announcements for your audience.</p>
    <?php endif; ?>
</section>
