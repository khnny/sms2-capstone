<?php
/**
 * SMS 2 - Breadcrumb Renderer
 *
 * @param array $breadcrumbs Array of ['label' => string, 'url' => string|null]
 */
function renderBreadcrumbs(array $breadcrumbs): void
{
    if (!empty($breadcrumbs)): ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?= BASE_URL ?>/dashboard/index.php"><?= smsIcon('home') ?></a>
            </li>
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index === array_key_last($breadcrumbs)): ?>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= htmlspecialchars($crumb['label']) ?>
                    </li>
                <?php else: ?>
                    <li class="breadcrumb-item">
                        <?php if (!empty($crumb['url'])): ?>
                            <a href="<?= htmlspecialchars($crumb['url']) ?>"><?= htmlspecialchars($crumb['label']) ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($crumb['label']) ?>
                        <?php endif; ?>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
        renderModulePageBanner($breadcrumbs);
    endif;

    if (!empty($GLOBALS['isRoleDashboardHome'])) {
        require ROOT_PATH . '/communication/dashboard-announcements.php';
        require ROOT_PATH . '/communication/dashboard-widgets.php';
    }

}

function renderModulePageBanner(array $breadcrumbs): void
{
    if (!empty($GLOBALS['hideModulePageBanner'])) {
        return;
    }

    $pageTitle = trim((string) ($GLOBALS['pageTitle'] ?? ''));
    if ($pageTitle === '' || in_array($pageTitle, ['Dashboard', 'Notification'], true)) {
        return;
    }

    $activeModule = (string) ($GLOBALS['activeModule'] ?? '');
    $moduleLabel = '';
    $moduleIcon = 'fa-layer-group';

    if ($activeModule !== '' && !empty($GLOBALS['MODULES'][$activeModule]) && is_array($GLOBALS['MODULES'][$activeModule])) {
        $moduleLabel = (string) ($GLOBALS['MODULES'][$activeModule]['label'] ?? '');
        $moduleIcon = (string) ($GLOBALS['MODULES'][$activeModule]['icon'] ?? $moduleIcon);
    }
    $moduleIcon = trim((string) ($GLOBALS['pageBannerIcon'] ?? $moduleIcon));
    if ($moduleIcon === '') {
        $moduleIcon = 'fa-layer-group';
    }

    if ($moduleLabel === '' && !empty($breadcrumbs[0]['label'])) {
        $moduleLabel = (string) $breadcrumbs[0]['label'];
    }

    $description = trim((string) ($GLOBALS['pageBannerDescription'] ?? ''));
    if ($description === '') {
        $description = $moduleLabel !== ''
            ? 'Manage ' . $pageTitle . ' under ' . $moduleLabel . '.'
            : 'Manage ' . $pageTitle . '.';
    }

    $backUrl = trim((string) ($GLOBALS['pageBannerBackUrl'] ?? ''));
    $backLabel = trim((string) ($GLOBALS['pageBannerBackLabel'] ?? ''));
    $hasCradProcessDescription = $activeModule === 'crad'
        && !empty($GLOBALS['cradProcess']['description']);
    ?>
    <section class="module-page-banner sms-page-header" aria-label="<?= htmlspecialchars($pageTitle) ?> header">
        <div class="module-page-banner__title">
            <h1><?= smsIcon($moduleIcon) ?><?= htmlspecialchars($pageTitle) ?></h1>
            <?php if (!$hasCradProcessDescription): ?>
                <p><?= htmlspecialchars($description) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($backUrl !== ''): ?>
            <a class="module-page-banner__action" href="<?= htmlspecialchars($backUrl) ?>">
                <?= smsIcon('arrow-left') ?>
                <?= htmlspecialchars($backLabel !== '' ? $backLabel : 'Back') ?>
            </a>
        <?php endif; ?>
    </section>
    <?php
}
