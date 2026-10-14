<?php
/**
 * SMS 2 - Top Navigation Bar
 */
require_once __DIR__ . '/authentication.php';
require_once __DIR__ . '/navigation-context.php';
require_once __DIR__ . '/notifications.php';
if (!isset($MODULES)) {
    require_once __DIR__ . '/../config/config.php';
}
$visibleModulesNav = getVisibleModules($MODULES);
$navRoleKey = getCurrentUserRoleKey();
$navMessages = [];
$navNotifications = [];
smsMarkNotificationFromRequest();
$navStudentResearchGroup = (isset($studentResearchGroup) && is_array($studentResearchGroup)) ? $studentResearchGroup : null;
$navStudentReturnedProposals = [];

if ($navRoleKey === 'student') {
    try {
        require_once __DIR__ . '/../modules/crad/config/config.php';
        $navCradPdo = function_exists('cradDb') ? cradDb() : null;

        if ($navCradPdo instanceof PDO) {
            $navStudentId = trim((string) ($_SESSION['student_id'] ?? ''));
            $navStudentEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
            $navStudentName = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
            $navStudentUserId = (int) ($_SESSION['user_id'] ?? 0);

            $navIdentityParams = [
                ':student_id_value' => $navStudentId,
                ':student_id_rep' => $navStudentId,
                ':student_email_value' => $navStudentEmail,
                ':student_email_rep' => $navStudentEmail,
                ':student_name_value' => $navStudentName,
                ':student_name_rep' => $navStudentName,
                ':user_id_value' => $navStudentUserId,
                ':user_id_match' => $navStudentUserId,
            ];

            if (!$navStudentResearchGroup) {
                $navStmt = $navCradPdo->prepare(
                    "SELECT p.proposal_number, p.research_title, p.registration_status,
                            p.rep_name, p.rep_id, p.rep_email, p.submitted_by_user,
                            g.group_number, g.group_name, g.status, g.date_assigned, g.created_at
                     FROM `crad_research_groups` g
                     INNER JOIN `crad_research_proposals` p ON p.id = g.proposal_id
                     WHERE g.group_number IS NOT NULL
                       AND (
                            (:student_id_value <> '' AND p.rep_id = :student_id_rep)
                         OR (:student_email_value <> '' AND LOWER(p.rep_email) = :student_email_rep)
                         OR (:student_name_value <> '' AND LOWER(TRIM(p.rep_name)) = :student_name_rep)
                         OR (:user_id_value > 0 AND p.submitted_by_user = :user_id_match)
                       )
                     ORDER BY g.date_assigned DESC, g.id DESC
                     LIMIT 1"
                );
                $navStmt->execute($navIdentityParams);
                $navStudentResearchGroup = $navStmt->fetch() ?: null;
            }

            $navReturnedStmt = $navCradPdo->prepare(
                "SELECT ref_code, research_title, notes, updated_at
                 FROM `crad_research_proposals`
                 WHERE status = 'Returned'
                   AND (
                        (:student_id_value <> '' AND rep_id = :student_id_rep)
                     OR (:student_email_value <> '' AND LOWER(rep_email) = :student_email_rep)
                     OR (:student_name_value <> '' AND LOWER(TRIM(rep_name)) = :student_name_rep)
                     OR (:user_id_value > 0 AND submitted_by_user = :user_id_match)
                   )
                 ORDER BY updated_at DESC, id DESC
                 LIMIT 5"
            );
            $navReturnedStmt->execute($navIdentityParams);
            $navStudentReturnedProposals = $navReturnedStmt->fetchAll() ?: [];
        }
    } catch (Throwable $e) {
        error_log('Navbar student notification error: ' . $e->getMessage());
    }
}

$navMessageCount = count($navMessages);
$navNotifications = array_merge(smsNotificationPayloadForCurrentUser(), $navNotifications);
$navNotificationCount = count($navNotifications);
$navNotificationUnreadCount = count(array_filter($navNotifications, static fn(array $item): bool => !empty($item['is_unread'])));
?>
<nav class="navbar navbar-expand-lg navbar-dark sms-navbar fixed-top">
    <div class="container-fluid navbar-inner">

        <!-- Left: Toggle + Brand -->
        <div class="navbar-left d-flex align-items-center gap-2">
            <button class="btn btn-link text-white sidebar-toggle p-2" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
                <?= smsIcon('menu-2') ?>
            </button>
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= htmlspecialchars(smsRoleHomeUrl($navRoleKey)) ?>">
                <img src="<?= e(smsBrandLogoUrl()) ?>" alt="BCP" style="height:30px;width:auto;object-fit:contain;">
                <span class="d-none d-sm-inline"><?= htmlspecialchars(APP_SHORT_NAME) ?></span>
            </a>
        </div>

        <!-- Center: Global Search -->
        <div class="navbar-center">
            <div class="navbar-search position-relative">
                <?= smsIcon('search', ['class' => 'navbar-search-icon']) ?>
                <input type="text" id="globalSearch" class="form-control navbar-search-input"
                       placeholder="Search modules and pages..."
                       autocomplete="off"
                       aria-label="Search modules and pages"
                       aria-haspopup="listbox"
                       aria-expanded="false">
                <button class="navbar-search-clear d-none" id="globalSearchClear" type="button" aria-label="Clear search">
                    <?= smsIcon('x') ?>
                </button>
                <div class="search-kbd-hint" aria-hidden="true">
                    <kbd>Ctrl</kbd><kbd>K</kbd>
                </div>
                <!-- Results dropdown -->
                <div class="navbar-search-dropdown" id="searchDropdown" role="listbox" aria-label="Search results">
                    <div class="search-empty" id="searchEmpty">
                        <?= smsIcon('zoom-out') ?>
                        <span>No results found</span>
                    </div>
                    <ul class="search-results-list" id="searchResultsList"></ul>
                </div>
            </div>
        </div>

        <!-- Right: PH Clock + Theme + Messages + Notifications + User -->
        <div class="navbar-right d-flex align-items-center gap-2 gap-md-3">

            <!-- Philippine Standard Time (Asia/Manila) -->
            <?php
            $phClockMs = (int) round(microtime(true) * 1000);
            $phClockSeed = (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('h:i:s A');
            ?>
            <time id="navbarPhClock"
                  class="navbar-ph-clock text-white"
                  datetime="<?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format(DateTimeInterface::ATOM)) ?>"
                  data-server-ms="<?= $phClockMs ?>"
                  title="Philippine Standard Time (UTC+8)"
                  aria-label="Philippine Standard Time">
                <?= htmlspecialchars($phClockSeed) ?>
            </time>

            <!-- Theme toggle -->
            <button type="button"
                    class="btn theme-toggle"
                    data-theme-toggle
                    aria-label="Switch theme"
                    title="Toggle theme">
                <?= smsIcon('moon', ['class' => 'theme-icon-moon', 'aria-hidden' => 'true']) ?>
                <?= smsIcon('sun', ['class' => 'theme-icon-sun', 'aria-hidden' => 'true']) ?>
            </button>

            <!-- Mobile search -->
            <button type="button"
                    class="btn btn-link text-white d-md-none navbar-search-toggle p-2"
                    id="navbarSearchToggle"
                    aria-label="Open search"
                    aria-expanded="false"
                    aria-controls="globalSearch">
                <?= smsIcon('search') ?>
            </button>

            <!-- Messages -->
            <div class="dropdown">
                <button class="btn btn-link text-white position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Messages: <?= $navMessageCount ?>">
                    <?= smsIcon('mail') ?>
                    <?php if ($navMessageCount > 0): ?>
                        <span class="position-absolute badge rounded-pill bg-success notification-badge" style="top:2px;right:-2px;transform:none;"><?= $navMessageCount ?></span>
                    <?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:280px;">
                    <li><h6 class="dropdown-header">Messages</h6></li>
                    <?php if ($navMessageCount === 0): ?>
                        <li><span class="dropdown-item-text text-muted py-2"><?= smsIcon('inbox', ['class' => 'me-2']) ?>No messages</span></li>
                    <?php else: ?>
                        <?php foreach ($navMessages as $message): ?>
                            <li>
                                <a class="dropdown-item d-flex align-items-start gap-2 py-2" href="<?= htmlspecialchars($message['url'] ?? '#') ?>">
                                    <div class="navbar-msg-avatar"><?= htmlspecialchars($message['avatar'] ?? 'M') ?></div>
                                    <div class="navbar-msg-body">
                                        <div class="navbar-msg-name"><?= htmlspecialchars($message['from'] ?? 'Message') ?></div>
                                        <div class="navbar-msg-text"><?= htmlspecialchars($message['text'] ?? '') ?></div>
                                        <div class="navbar-msg-time"><?= htmlspecialchars($message['time'] ?? '') ?></div>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-center text-primary py-2" href="#"><?= smsIcon('mail-opened', ['class' => 'me-1']) ?>View all messages</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn btn-link text-white position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications: <?= $navNotificationCount ?>" data-sms-notification-button>
                    <?= smsIcon('bell') ?>
                    <span class="position-absolute badge rounded-pill bg-danger notification-badge <?= $navNotificationUnreadCount > 0 ? '' : 'd-none' ?>" style="top:2px;right:-2px;transform:none;" data-sms-notification-badge><?= $navNotificationUnreadCount ?></span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow sms-notification-dropdown" data-sms-notification-menu>
                    <div class="sms-notification-head">
                        <h6>Notifications</h6>
                    </div>
                    <div class="sms-notification-tabs" role="tablist" aria-label="Notification filters">
                        <button type="button" class="active" data-sms-notification-filter="all">All</button>
                        <button type="button" data-sms-notification-filter="unread">Unread</button>
                    </div>
                    <div class="sms-notification-empty" data-sms-notification-empty <?= $navNotificationCount === 0 ? '' : 'hidden' ?>>
                        <?= smsIcon('bell-off') ?>
                        <span>No notifications</span>
                    </div>
                    <div class="sms-notification-list" data-sms-notification-items>
                        <?php foreach ($navNotifications as $notification): ?>
                            <?php $isUnread = !empty($notification['is_unread']); ?>
                            <a class="sms-notification-item <?= $isUnread ? 'unread' : '' ?>"
                               href="<?= htmlspecialchars($notification['url'] ?? '#') ?>"
                               data-sms-notification-link
                               data-notification-id="<?= (int) ($notification['id'] ?? 0) ?>"
                               data-notification-batch-key="<?= htmlspecialchars((string) ($notification['batch_key'] ?? '')) ?>"
                               data-notification-status="<?= $isUnread ? 'unread' : 'read' ?>">
                                <span class="sms-notification-icon"><?= smsIcon($notification['icon'] ?? 'info-circle') ?></span>
                                <span class="sms-notification-copy">
                                    <span class="sms-notification-title"><?= htmlspecialchars($notification['label'] ?? 'Notification') ?></span>
                                    <?php if (!empty($notification['preview'] ?? $notification['body'] ?? '')): ?>
                                        <span class="sms-notification-preview"><?= htmlspecialchars((string) ($notification['preview'] ?? $notification['body'])) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($notification['time'])): ?>
                                        <span class="sms-notification-time"><?= htmlspecialchars($notification['time']) ?></span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($isUnread): ?><span class="sms-notification-dot" aria-label="Unread"></span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- User Profile -->
            <div class="dropdown">
                <button class="btn btn-link text-white text-decoration-none dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= smsIcon('user-circle', ['class' => 'ti-lg']) ?>
                    <span class="d-none d-md-inline" data-sms-user-name><?= htmlspecialchars(getCurrentUserName()) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                    <li><h6 class="dropdown-header" data-sms-user-role><?= htmlspecialchars(getCurrentUserRole()) ?></h6></li>
                    <?php
                    $navRole = getCurrentUserRoleKey();
                    if ($navRole === 'student') {
                        $profileHref = BASE_URL . '/modules/student-portal/pages/my-profile.php';
                        $profileLabel = 'My Profile';
                    } elseif (smsIsGrantedAdminRole($navRole)) {
                        $profileHref = BASE_URL . '/account/profile.php';
                        $profileLabel = 'Account Settings';
                    } else {
                        $profileHref = BASE_URL . '/dashboard/index.php';
                        $profileLabel = 'My Profile';
                    }
                    ?>
                    <li>
                        <a class="dropdown-item" href="<?= htmlspecialchars($profileHref) ?>">
                            <?= smsIcon('user', ['class' => 'me-2']) ?><?= htmlspecialchars($profileLabel) ?>
                        </a>
                    </li>
                    <?php if (smsIsGrantedAdminRole($navRole)): ?>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>/account/profile.php?tab=security">
                            <?= smsIcon('key', ['class' => 'me-2']) ?>Login Security
                        </a>
                    </li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger"
                           href="<?= BASE_URL ?>/login/logout.php"
                           data-logout-confirm
                           data-no-loader>
                            <?= smsIcon('logout', ['class' => 'me-2']) ?>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

    </div>
</nav>

<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-labelledby="logoutConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content sms-logout-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutConfirmTitle">Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to logout?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <a class="btn btn-danger" href="<?= BASE_URL ?>/login/logout.php" id="logoutConfirmBtn" data-loader-message="Signing out…">
                    <span class="logout-confirm-idle">Yes, logout</span>
                    <span class="logout-confirm-loading d-none">
                        <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Logging out...
                    </span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
/* Global Search Index — built from PHP $MODULES visible to current user */
window.SMS2_SEARCH_INDEX = (function() {
    var base = '<?= BASE_URL ?>';
    var items = [];
    <?php foreach ($visibleModulesNav as $navModuleKey => $module): ?>
    items.push({type:'module',label:<?= json_encode($module['label']) ?>,icon:<?= json_encode($module['icon']) ?>,url:base+'/modules/<?= $navModuleKey ?>/index.php',keywords:<?= json_encode(strtolower($module['label'])) ?>});
    <?php foreach (($module['pages'] ?? []) as $page): ?>
    items.push({type:'page',label:<?= json_encode($page['title']) ?>,parent:<?= json_encode($module['label']) ?>,icon:<?= json_encode($module['icon']) ?>,url:<?= json_encode(($page['slug'] ?? '') === 'security-settings' ? BASE_URL . '/account/module-security.php?module=' . urlencode((string) $navModuleKey) : BASE_URL . '/modules/' . $navModuleKey . '/pages/' . $page['slug'] . '.php') ?>,keywords:<?= json_encode(strtolower($page['title'].' '.$module['label'])) ?>});
    <?php endforeach; ?>
    <?php endforeach; ?>
    <?php unset($navModuleKey, $module, $page); ?>    return items;
})();

document.addEventListener('DOMContentLoaded', function () {
    const notificationEndpoint = '<?= BASE_URL ?>/api/notifications.php';
    const notificationButton = document.querySelector('[data-sms-notification-button]');
    const notificationBadge = document.querySelector('[data-sms-notification-badge]');
    const notificationEmpty = document.querySelector('[data-sms-notification-empty]');
    const notificationItems = document.querySelector('[data-sms-notification-items]');
    const notificationFilters = document.querySelectorAll('[data-sms-notification-filter]');
    let notificationFilter = 'all';
    let notificationCache = [];

    const esc = function (value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
        });
    };

    const compactPreview = function (item) {
        const text = String(item.preview || item.body || '').replace(/\s+/g, ' ').trim();
        return text.length > 64 ? text.slice(0, 63) + '...' : text;
    };

    const renderNotifications = function (items) {
        notificationCache = Array.isArray(items) ? items : [];
        const visibleItems = notificationFilter === 'unread'
            ? notificationCache.filter(function (item) { return Boolean(item.is_unread); })
            : notificationCache;
        const count = Array.isArray(items) ? items.length : 0;
        const unreadCount = notificationCache.filter(function (item) { return Boolean(item.is_unread); }).length;
        if (notificationBadge) {
            notificationBadge.textContent = String(unreadCount);
            notificationBadge.classList.toggle('d-none', unreadCount === 0);
        }
        if (notificationButton) {
            notificationButton.setAttribute('aria-label', 'Notifications: ' + count);
        }
        if (notificationEmpty) {
            notificationEmpty.hidden = visibleItems.length !== 0;
            const emptyText = notificationEmpty.querySelector('span');
            if (emptyText) {
                emptyText.textContent = notificationFilter === 'unread' ? 'No unread notifications' : 'No notifications';
            }
        }
        notificationFilters.forEach(function (button) {
            const isActive = button.dataset.smsNotificationFilter === notificationFilter;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            if (button.dataset.smsNotificationFilter === 'unread') {
                button.textContent = unreadCount > 0 ? 'Unread ' + unreadCount : 'Unread';
            }
        });
        if (!notificationItems) return;
        notificationItems.innerHTML = visibleItems.map(function (item) {
            const isUnread = Boolean(item.is_unread);
            return '<a class="sms-notification-item ' + (isUnread ? 'unread' : '') + '" href="' + esc(item.url || '#') + '" data-sms-notification-link data-notification-id="' + esc(item.id || '') + '" data-notification-batch-key="' + esc(item.batch_key || '') + '" data-notification-status="' + (isUnread ? 'unread' : 'read') + '">' +
                '<span class="sms-notification-icon">' + (window.smsIconHtml ? window.smsIconHtml(item.icon || 'info-circle') : '') + '</span>' +
                '<span class="sms-notification-copy">' +
                    '<span class="sms-notification-title">' + esc(item.label || 'Notification') + '</span>' +
                    (compactPreview(item) ? '<span class="sms-notification-preview">' + esc(compactPreview(item)) + '</span>' : '') +
                    (item.time ? '<span class="sms-notification-time">' + esc(item.time) + '</span>' : '') +
                '</span>' +
                (isUnread ? '<span class="sms-notification-dot" aria-label="Unread"></span>' : '') +
            '</a>';
        }).join('');
    };

    notificationFilters.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            notificationFilter = button.dataset.smsNotificationFilter || 'all';
            renderNotifications(notificationCache);
        });
    });

    const refreshNotifications = async function () {
        if (!notificationButton) return;
        try {
            const res = await fetch(notificationEndpoint, {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (data && data.ok) {
                renderNotifications(data.items || []);
                if (data.user) {
                    var nameEl = document.querySelector('[data-sms-user-name]');
                    if (nameEl && data.user.name) nameEl.textContent = data.user.name;
                    var roleEl = document.querySelector('[data-sms-user-role]');
                    if (roleEl && data.user.role) roleEl.textContent = data.user.role;
                }
            }
        } catch (error) {
            return;
        }
    };
    window.SMSRefreshNotifications = refreshNotifications;

    document.addEventListener('click', function (event) {
        const link = event.target.closest('[data-sms-notification-link]');
        if (!link) return;
        const id = Number(link.dataset.notificationId || 0);
        const batchKey = String(link.dataset.notificationBatchKey || '');
        if (id <= 0 && !batchKey) return;
        notificationCache = notificationCache.map(function (item) {
            const itemId = Number(item.id || 0);
            const itemBatchKey = String(item.batch_key || '');
            if (!((id > 0 && itemId === id) || (batchKey && itemBatchKey === batchKey))) return item;
            return Object.assign({}, item, { is_unread: false, status: 'read' });
        });
        renderNotifications(notificationCache);
        const form = new FormData();
        form.append('notification_id', String(id));
        form.append('batch_key', batchKey);
        fetch(notificationEndpoint, {
            method: 'POST',
            body: form,
            headers: { 'Accept': 'application/json' },
            cache: 'no-store',
            credentials: 'same-origin'
        }).catch(function () {});
    });

    refreshNotifications();
    window.setInterval(refreshNotifications, 5000);

    const logoutLink = document.querySelector('[data-logout-confirm]');
    const modalEl = document.getElementById('logoutConfirmModal');
    const confirmBtn = document.getElementById('logoutConfirmBtn');
    if (!logoutLink || !modalEl || typeof bootstrap === 'undefined') return;

    const logoutModal = new bootstrap.Modal(modalEl);
    logoutLink.addEventListener('click', function (event) {
        event.preventDefault();
        logoutModal.show();
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (window.SMS2Loader && typeof window.SMS2Loader.forceHide === 'function') {
            window.SMS2Loader.forceHide();
        }
    });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            const idle = confirmBtn.querySelector('.logout-confirm-idle');
            const loading = confirmBtn.querySelector('.logout-confirm-loading');
            if (idle && loading) {
                idle.classList.add('d-none');
                loading.classList.remove('d-none');
            }
            confirmBtn.classList.add('disabled');
            confirmBtn.setAttribute('aria-disabled', 'true');

            if (window.SMS2Loader && typeof window.SMS2Loader.show === 'function') {
                window.SMS2Loader.show(confirmBtn.getAttribute('data-loader-message') || 'Signing out…');
            }
        });
    }
});
</script>
