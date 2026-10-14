<?php
/**
 * SMS 2 - Welcome Page
 */
require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/module-controls.php';

if (smsNeedsSetup()) {
    header('Location: ' . BASE_URL . '/setup/index.php');
    exit;
}

if (smsIsSystemInMaintenance()) {
    if (isAuthenticated() && smsCanBypassSystemControls()) {
        header('Location: ' . BASE_URL . '/modules/user-management/pages/system-settings.php');
        exit;
    }
    header('Location: ' . BASE_URL . '/account/maintenance.php');
    exit;
}

if (isAuthenticated()) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$pageTitle = 'Welcome';
$bodyClass = 'welcome-page';
$omitAppChromeJs = true;
$welcomeHeroUrl = smsWelcomeHeroImageUrl();

require_once ROOT_PATH . '/includes/header.php';
?>

<div class="sms-welcome-bg" aria-hidden="true" style="--welcome-image: url('<?= e($welcomeHeroUrl) ?>')"></div>

<main class="sms-welcome-main">
    <section class="sms-welcome-shell" aria-label="Welcome">
        <header class="sms-welcome-shell-head">
            <a class="sms-welcome-brand" href="<?= BASE_URL ?>/welcome/index.php">
                <img src="<?= e(smsBrandLogoUrl()) ?>?v=crest3" alt="<?= e(APP_SHORT_NAME) ?> logo" width="44" height="44">
                <span>
                    <strong>Bestlink College</strong>
                    <small>of the Philippines</small>
                </span>
            </a>
            <span class="sms-welcome-help">Help Center</span>
        </header>

        <div class="sms-welcome-shell-body">
            <aside class="sms-welcome-showcase">
                <div class="sms-welcome-hero" aria-hidden="true">
                    <img
                        class="sms-welcome-hero-img"
                        src="<?= e($welcomeHeroUrl) ?>?v=1"
                        alt=""
                        width="800"
                        height="600"
                        decoding="async"
                        fetchpriority="high"
                    >
                </div>
                <div class="sms-welcome-showcase-copy">
                    <span class="sms-welcome-tag">Student Management System 2</span>
                    <h1>One connected space for your academic journey.</h1>
                    <p>Access enrollment, academic records, student services, and every stage of your research—from group formation to final approval.</p>
                    <div class="sms-welcome-actions">
                        <a href="<?= BASE_URL ?>/login/login.php" class="sms-welcome-btn sms-welcome-btn-primary" data-auth-transition data-auth-direction="left">
                            <?= smsIcon('login', ['aria-hidden' => 'true']) ?>
                            Sign in to SMS2
                        </a>
                        <a href="<?= BASE_URL ?>/login/student-admission.php" class="sms-welcome-btn sms-welcome-btn-secondary" data-auth-transition data-auth-direction="left">
                            <?= smsIcon('id', ['aria-hidden' => 'true']) ?>
                            Student admission
                        </a>
                    </div>
                    <div class="sms-welcome-assurance">
                        <?= smsIcon('shield', ['aria-hidden' => 'true']) ?>
                        Protected by secure authentication and role-based access
                    </div>
                </div>
            </aside>
        </div>

        <footer class="sms-welcome-shell-foot">
            <p>&copy; <?= date('Y') ?> <?= e(INSTITUTION) ?></p>
            <nav aria-label="Footer">
                <span>Help Center</span>
                <span aria-hidden="true">·</span>
                <span>Privacy</span>
                <span aria-hidden="true">·</span>
                <span>Accessibility</span>
            </nav>
        </footer>
    </section>
</main>

<?php require_once ROOT_PATH . '/includes/scripts.php'; ?>
<script src="<?= BASE_URL ?>/assets/js/auth-transition.js?v=8"></script>
