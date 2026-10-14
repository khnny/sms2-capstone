<?php
/**
 * SMS 2 - Scripts
 */
?>
<!-- Bootstrap JS (local) -->
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/sms-icons.js?v=2"></script>
<!-- SMS 2 App -->
<?php if (empty($omitThemeJs)): ?>
<script src="<?= BASE_URL ?>/assets/js/theme.js?v=2"></script>
<?php endif; ?>
<?php if (empty($omitAppChromeJs)): ?>
<script src="<?= BASE_URL ?>/assets/js/ph-clock.js"></script>
<script src="<?= BASE_URL ?>/assets/js/sidebar.js?v=collapsible-overview-1"></script>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script src="<?= BASE_URL ?>/assets/js/sms-confirm.js?v=3"></script>
<script src="<?= BASE_URL ?>/assets/js/sms-security-ui.js?v=6"></script>
<!-- Global Search -->
<script src="<?= BASE_URL ?>/assets/js/search.js?v=3"></script>
<?php
    $idleMinutes = 2;
    if (function_exists('smsSetting')) {
        $idleMinutes = max(1, (int) smsSetting('session_timeout_minutes', '2'));
    }
    $idleSeconds = $idleMinutes * 60;
?>
<script>
window.SMS_IDLE_LOGOUT = {
    idleSeconds: <?= (int) $idleSeconds ?>,
    logoutUrl: <?= json_encode(BASE_URL . '/login/logout.php?timeout=1', JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script src="<?= BASE_URL ?>/assets/js/idle-logout.js?v=1"></script>
<?php endif; ?>

<?php 
// Research Progress Live Updates (READ-ONLY Polling) - Only for student-portal and faculty modules
$loadResearchProgressLive = false;

// Check if current page is in student-portal or faculty modules
if (isset($activeModule)) {
    if ($activeModule === 'student-portal' || $activeModule === 'faculty') {
        $loadResearchProgressLive = true;
    }
}

// Alternative check: Check URL path if $activeModule not set
if (!$loadResearchProgressLive) {
    $currentPath = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($currentPath, '/student-portal/') !== false || strpos($currentPath, '/faculty/') !== false) {
        $loadResearchProgressLive = true;
    }
}

if ($loadResearchProgressLive): 
?>
<!-- Research Progress Live Updates (READ-ONLY Polling) -->
<script src="<?= BASE_URL ?>/assets/js/research-progress-live.js?v=2"></script>
<script>
// Auto-detect role and initialize
(function() {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/student-portal/')) {
        document.body.classList.add('student-portal');
    } else if (currentPath.includes('/faculty/')) {
        document.body.classList.add('faculty-portal');
    }
})();
</script>
<?php endif; ?>

<?php
$loadGrantOutputsSidebarLive = false;
if (function_exists('getCurrentUserRoleKey')) {
    $grantOutputsRole = getCurrentUserRoleKey();
    if (in_array($grantOutputsRole, ['crad_officer', 'research_grant', 'superadmin'], true)) {
        $loadGrantOutputsSidebarLive = true;
    }
}
if ($loadGrantOutputsSidebarLive):
?>
<script src="<?= BASE_URL ?>/assets/js/grant-outputs-sidebar-live.js?v=1"></script>
<?php endif; ?>
<script src="<?= BASE_URL ?>/assets/js/password-strength.js?v=2"></script>
</body>
</html>
