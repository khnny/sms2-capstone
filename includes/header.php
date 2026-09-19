<?php
/**
 * SMS 2 - HTML Header
 * Expects: $pageTitle (string), optional $bodyClass (string)
 */
if (!defined('APP_NAME')) {
    require_once __DIR__ . '/../config/config.php';
}

require_once __DIR__ . '/icons.php';

require_once __DIR__ . '/security.php';
smsSendSecurityHeaders();

$pageTitle = $pageTitle ?? APP_NAME;
$bodyClass = $bodyClass ?? '';
$isCradPage = ($activeModule ?? '') === 'crad';
if ($isCradPage && strpos(' ' . $bodyClass . ' ', ' crad-app ') === false) {
    $bodyClass .= ' crad-app';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="description" content="<?= e(APP_NAME) ?> - <?= e(INSTITUTION) ?>">
    <title><?= e($pageTitle) ?> | <?= e(APP_SHORT_NAME) ?></title>
    <link rel="icon" href="<?= e(smsBrandLogoUrl()) ?>" type="<?= str_ends_with(smsBrandLogoUrl(), '.svg') ? 'image/svg+xml' : 'image/png' ?>">

    <!-- Apply theme before paint to avoid flash of wrong theme.
         Also set inline background so there's no white flash while
         Bootstrap and our CSS are still loading. -->
    <script>
    (function () {
        var DARK_BG  = '#0b1224';
        var LIGHT_BG = '#eef2f9';
        var LOGIN_BG = '#071c48';
        var isAuthLanding = <?= json_encode(
            strpos(' ' . $bodyClass . ' ', ' login-page ') !== false
            || strpos(' ' . $bodyClass . ' ', ' welcome-page ') !== false
        ) ?>;
        try {
            var forced = <?= json_encode(isset($forceTheme) && in_array($forceTheme, ['light', 'dark'], true) ? $forceTheme : '') ?>;
            var t = forced || localStorage.getItem('sms2-theme');
            if (t !== 'dark' && t !== 'light') t = 'light';
            var root = document.documentElement;
            root.setAttribute('data-theme', t);
            root.style.colorScheme = isAuthLanding ? 'light' : t;
            // Auth screens use navy — never flash light gray/white on refresh
            root.style.backgroundColor = isAuthLanding ? LOGIN_BG : (t === 'dark' ? DARK_BG : LIGHT_BG);
            if (forced) {
                root.setAttribute('data-theme-locked', '1');
            }
        } catch (e) {
            document.documentElement.setAttribute('data-theme', 'light');
            document.documentElement.style.backgroundColor = isAuthLanding ? LOGIN_BG : LIGHT_BG;
        }
    })();
    </script>

    <!-- Local vendor assets (offline-safe; no CDN DNS required) -->
    <link href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/vendor/tabler-icons/tabler-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/icons.css?v=4" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/vendor/fonts/inter.css" rel="stylesheet">
    <script src="<?= BASE_URL ?>/assets/vendor/chartjs/chart.umd.min.js"></script>
    <script>
    (function () {
        if (typeof Chart === 'undefined' || !Chart.prototype || Chart.prototype._smsUpdatePatched) return;
        var original = Chart.prototype.update;
        if (typeof original !== 'function') return;
        Chart.prototype.update = function () {
            try {
                if (!this || !this.ctx || this.destroyed) return this;
                return original.apply(this, arguments);
            } catch (err) {
                return this;
            }
        };
        Chart.prototype._smsUpdatePatched = true;
    })();
    </script>
    <!-- SMS 2 Theme -->
    <?php
    $isWelcomeLanding = strpos(' ' . ($bodyClass ?? '') . ' ', ' welcome-page ') !== false;
    if (!$isWelcomeLanding):
    ?>
    <link href="<?= BASE_URL ?>/assets/css/theme.css?v=13" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/layout.css?v=13" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/responsive.css?v=9" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/components.css?v=6" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/module-process-list.css?v=3" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/navbar-components.css?v=7" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard-academic.css?v=4" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/loader.css?v=6" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/sms-security-ui.css?v=20" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/password-strength.css?v=2" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/research-monitoring.css?v=2" rel="stylesheet">
    <?php else: ?>
    <link href="<?= BASE_URL ?>/assets/css/welcome.css?v=8" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/auth-transition.css?v=8" rel="stylesheet">
    <?php endif; ?>
    <?php if ($isCradPage): ?>
    <link href="<?= BASE_URL ?>/modules/crad/assets/css/crad-ui.css?v=12" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>"<?= (strpos(' ' . $bodyClass . ' ', ' login-page ') !== false || strpos(' ' . $bodyClass . ' ', ' welcome-page ') !== false) ? ' style="background:#071c48"' : '' ?>>
<?php if (strpos(' ' . $bodyClass . ' ', ' login-page ') === false && strpos(' ' . $bodyClass . ' ', ' welcome-page ') === false): ?>
<?php
$loaderCrestUrl = is_readable(ROOT_PATH . '/images/bcp-crest.png')
    ? BASE_URL . '/images/bcp-crest.png'
    : smsBrandLogoUrl();
?>
<div id="smsPageLoader" class="sms-page-loader is-active" role="status" aria-live="polite" aria-busy="true" aria-label="Loading">
    <div class="sms-loader-backdrop" aria-hidden="true"></div>
    <div class="sms-loader-content">
        <img src="<?= e($loaderCrestUrl) ?>?v=crest-loader"
             alt="<?= e(INSTITUTION) ?>"
             class="sms-loader-crest"
             width="64"
             height="64"
             onerror="this.onerror=null;this.src='<?= e(smsBrandLogoUrl()) ?>';">
        <p class="sms-loader-school"><?= e(APP_SHORT_NAME) ?></p>
        <span class="sms-loader-label">Loading…</span>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/loader.js?v=4"></script>
<?php else: ?>
<script>document.documentElement.classList.add('sms-app-ready');</script>
<?php endif; ?>
