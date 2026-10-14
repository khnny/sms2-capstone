<?php
/**
 * SMS 2 – User Management – Role & Permissions
 * Interactive permission matrix — changes persist to session and
 * immediately affect sidebar visibility for each role.
 */
require_once __DIR__ . '/../../../config/config.php';

$pageTitle    = 'Role & Permissions';
$activeModule = 'user-management';
$activePage   = 'role-permissions';
$breadcrumbs  = [
    ['label' => 'User Management',    'url' => BASE_URL . '/modules/user-management/index.php'],
    ['label' => 'Role & Permissions', 'url' => null],
];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
requireSuperAdmin();

/* ── Role definitions (Super Admin omitted — system-level, not matrix-editable) ── */
$roles = [
    'sms_admin'  => ['label' => 'Admin',        'icon' => 'fa-user-cog',         'color' => 'sms_admin'],
    'admission'  => ['label' => 'Admission',   'icon' => 'fa-user-check',       'color' => 'admission'],
    'registrar'  => ['label' => 'Registrar',   'icon' => 'fa-folder-open',      'color' => 'registrar'],
    'finance'    => ['label' => 'Finance',      'icon' => 'fa-credit-card',      'color' => 'finance'],
    'hr'         => ['label' => 'Dean',         'icon' => 'fa-user-tie',         'color' => 'hr'],
    'it_office'  => ['label' => 'IT Office',    'icon' => 'fa-laptop',           'color' => 'it_office'],
    'osa'        => ['label' => 'OSA',          'icon' => 'fa-users',            'color' => 'osa'],
    'qa'         => ['label' => 'QA',           'icon' => 'fa-award',            'color' => 'qa'],
    'crad'       => ['label' => 'CRAD',         'icon' => 'fa-flask',            'color' => 'crad'],
];

/* ── Default access matrix ─────────────────────────────────── */
$defaultMatrix = [
    'enrollment'      => ['icon'=>'fa-user-graduate',      'label'=>'Enrollment Management',    'admin'=>true,  'registrar'=>false, 'finance'=>false,'hr'=>false, 'adviser'=>false,'panel'=>false,'it_office'=>false,'osa'=>false,'qa'=>false,'crad'=>false],
    'registrar'       => ['icon'=>'fa-folder-open',        'label'=>'Registrar',                'admin'=>true,  'registrar'=>true,  'finance'=>false,'hr'=>false, 'adviser'=>false,'panel'=>false,'it_office'=>false,'osa'=>false,'qa'=>false,'crad'=>false],
    'curriculum'      => ['icon'=>'fa-book',               'label'=>'Curriculum & Subjects',    'admin'=>true,  'registrar'=>true,  'finance'=>false,'hr'=>false, 'adviser'=>false,'panel'=>false,'it_office'=>false,'osa'=>false,'qa'=>false,'crad'=>false],
    'accreditation'   => ['icon'=>'fa-award',              'label'=>'Accreditation Management', 'admin'=>true,  'registrar'=>false, 'finance'=>false,'hr'=>false, 'adviser'=>false,'panel'=>false,'it_office'=>false,'osa'=>false,'qa'=>true, 'crad'=>false],
    'payment'         => ['icon'=>'fa-credit-card',        'label'=>'Payment Management',       'admin'=>true,  'registrar'=>false, 'finance'=>true, 'hr'=>false, 'adviser'=>false,'panel'=>false,'it_office'=>false,'osa'=>false,'qa'=>false,'crad'=>false],
    'faculty'         => ['icon'=>'fa-chalkboard-teacher', 'label'=>'Faculty Management',       'admin'=>true,  'registrar'=>false, 'finance'=>false,'hr'=>true,  'adviser'=>true, 'panel'=>true, 'it_office'=>false,'osa'=>false,'qa'=>false,'crad'=>false],
    'scheduling'      => ['icon'=>'fa-calendar-alt',       'label'=>'Class Schedule',           'admin'=>true,  'registrar'=>true,  'finance'=>false,'hr'=>false, 'adviser'=>false,'panel'=>false,'it_office'=>false,'osa'=>false,'qa'=>false,'crad'=>false],
    'cocurricular'    => ['icon'=>'fa-users',              'label'=>'Co-Curricular',            'admin'=>true,  'registrar'=>false, 'finance'=>false,'hr'=>false, 'adviser'=>false,'panel'=>false,'it_office'=>false,'osa'=>true, 'qa'=>false,'crad'=>false],
    'lms'             => ['icon'=>'fa-laptop',             'label'=>'Online Learning & LMS',    'admin'=>true,  'registrar'=>false, 'finance'=>false,'hr'=>false, 'adviser'=>false,'panel'=>false,'it_office'=>true, 'osa'=>false,'qa'=>false,'crad'=>false],
    'crad'            => ['icon'=>'fa-flask',              'label'=>'CRAD',                     'admin'=>true,  'registrar'=>false, 'finance'=>false,'hr'=>false, 'adviser'=>false,'panel'=>false,'it_office'=>false,'osa'=>false,'qa'=>false,'crad'=>true],
];

foreach ($defaultMatrix as $modKey => &$modDefaults) {
    $modDefaults['sms_admin']  = true;
    $modDefaults['admission']  = ($modKey === 'enrollment');
    unset($modDefaults['admin']);
    unset($modDefaults['panel']);
    unset($modDefaults['superadmin']);
}
unset($modDefaults);

/* ── Load permissions from DB (preferred) + JSON fallback ──── */
$matrix = $defaultMatrix;
$pdo = db();
if ($pdo) {
    try {
        $rows = $pdo->query('SELECT role_key, module_key, granted FROM `sms2_role_permissions`')->fetchAll();
        foreach ($rows as $r) {
            $matrixKey = smsMatrixRoleKey((string) $r['role_key']);
            $mod = (string) $r['module_key'];
            if (isset($matrix[$mod][$matrixKey])) {
                $matrix[$mod][$matrixKey] = ((int) $r['granted'] === 1);
            }
        }
    } catch (Throwable $e) {
        // keep defaults
    }
} else {
    $permFile  = ROOT_PATH . '/config/perm_overrides.json';
    $overrides = [];
    if (file_exists($permFile)) {
        $json      = file_get_contents($permFile);
        $decoded   = json_decode($json, true);
        if (is_array($decoded)) {
            $overrides = $decoded;
        }
    }
    foreach ($overrides as $roleKey => $modules) {
        foreach ($modules as $modKey => $granted) {
            if (isset($matrix[$modKey][$roleKey])) {
                $matrix[$modKey][$roleKey] = (bool) $granted;
            }
        }
    }
}

$roleKeys = array_keys($roles);
$csrf = csrfToken();
?>

<link href="<?= BASE_URL ?>/modules/user-management/assets/css/user-management.css?v=dept-head-badge-2" rel="stylesheet">
<meta name="csrf-token" content="<?= e($csrf) ?>">

<!-- Toast container -->
<div id="umToastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index:1100;"></div>

<?php
$pageBannerIcon        = 'fa-shield-alt';
$pageBannerDescription = 'Check or uncheck modules per role. Super Admin uses User Management only — not shown here. Student Portal is for Student accounts only.';
renderBreadcrumbs($breadcrumbs);
?>

<div class="page-header d-flex justify-content-end align-items-start flex-wrap gap-2">
    <span id="permSaveStatus" class="text-muted" style="font-size:.78rem;"></span>
</div>

<div class="alert alert-info py-2 px-3 mb-4 d-flex align-items-start gap-2" style="font-size:.84rem;">
    <?= smsIcon('user-shield', ['class' => 'mt-1', 'aria-hidden' => 'true']) ?>
    <div>
        <strong>Super Admin</strong> is not listed here — that account uses <strong>User Management</strong> only.
        <strong>Student Portal</strong> is reserved for Student accounts.
    </div>
</div>

<!-- Role summary cards — show live count from current matrix -->
<div class="row g-3 mb-4" id="roleSummaryRow">
    <?php foreach ($roles as $key => $role):
        $count = count(array_filter(array_column($matrix, $key)));
    ?>
        <div class="col-6 col-md-4 col-lg-3 col-xl-2" id="roleCard_<?= $key ?>">
            <div class="card text-center" style="padding:.85rem .5rem;">
                <div style="font-size:1.35rem;margin-bottom:.45rem;color:var(--sms-primary);">
                    <?= smsIcon($role['icon']) ?>
                </div>
                <span class="role-badge <?= $key ?> d-inline-flex mx-auto mb-1">
                    <?= htmlspecialchars($role['label']) ?>
                </span>
                <div class="role-module-count" data-role="<?= $key ?>"
                     style="font-size:.68rem;color:var(--sms-text-faint);">
                    <?= $count ?> module(s)
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Permission matrix table -->
<section class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table perm-table mb-0" id="permMatrix">
                <thead>
                    <tr>
                        <th style="min-width:220px;padding-left:1.2rem;">Module</th>
                        <?php foreach ($roles as $key => $role): ?>
                            <th class="role-col-head" data-role="<?= $key ?>">
                                <?= smsIcon($role['icon'], ['class' => 'd-block mb-1', 'style' => 'font-size:.85rem;']) ?>
                                <?= htmlspecialchars($role['label']) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matrix as $modKey => $mod):
                        $rowClass = ($modKey === 'user-management') ? 'perm-row-admin' : '';
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="module-label" style="padding-left:1.2rem;">
                            <?= smsIcon($mod['icon'], ['class' => 'me-2']) ?>
                            <?= htmlspecialchars($mod['label']) ?>
                        </td>
                        <?php foreach ($roleKeys as $rk):
                            // Super Admin column is always locked (controlled at system level)
                            $isLocked = ($rk === 'superadmin');
                            $isChecked = !empty($mod[$rk]);
                            $checked  = $isChecked ? 'checked' : '';
                        ?>
                            <td>
                                <?php if ($isLocked): ?>
                                    <!-- Locked — always checked for admin, show static icon -->
                                    <?php if ($isChecked): ?>
                                        <span class="perm-yes" data-role="<?= $rk ?>" title="Always granted">
                                            <?= smsIcon('check-circle') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="perm-no" title="Locked — no access">
                                            <?= smsIcon('minus') ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Interactive checkbox -->
                                    <label class="perm-checkbox-wrap" title="<?= $mod[$rk] ? 'Click to revoke access' : 'Click to grant access' ?>">
                                        <input type="checkbox"
                                               class="perm-cb"
                                               data-role="<?= $rk ?>"
                                               data-module="<?= $modKey ?>"
                                               <?= $checked ?>>
                                        <span class="perm-cb-visual"></span>
                                    </label>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Legend -->
<div class="d-flex align-items-center gap-3 mt-3 flex-wrap" style="font-size:.78rem;color:var(--sms-text-muted);">
    <span><?= smsIcon('check-circle', ['class' => 'text-success me-1']) ?> Access granted</span>
    <span><?= smsIcon('minus', ['class' => 'me-1']) ?> No access</span>
    <span class="perm-cb-visual" style="display:inline-block;pointer-events:none;"></span><span>= Editable</span>
    <span class="ms-auto"><?= smsIcon('info-circle', ['class' => 'text-primary me-1']) ?>
        Changes apply on the role's next page load.
    </span>
</div>

<!-- ── Styles ─────────────────────────────────────────────── -->
<style>
/* Custom checkbox */
.perm-checkbox-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    margin: 0;
    padding: 2px;
}
.perm-checkbox-wrap input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 0; height: 0;
    pointer-events: none;
}
.perm-cb-visual {
    width: 20px;
    height: 20px;
    border-radius: 6px;
    border: 2px solid var(--sms-border);
    background: var(--sms-surface-muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    flex-shrink: 0;
    position: relative;
}
.perm-cb-visual::after {
    content: '';
    display: block;
    width: 5px;
    height: 9px;
    border: 2px solid #fff;
    border-top: none;
    border-left: none;
    transform: rotate(45deg) scale(0) translateY(-1px);
    transition: transform 0.15s cubic-bezier(0.22,1,0.36,1);
    position: absolute;
    top: 2px;
}
.perm-checkbox-wrap input:checked ~ .perm-cb-visual {
    background: var(--sms-success);
    border-color: var(--sms-success);
    box-shadow: 0 4px 12px rgba(22,163,74,0.35);
}
.perm-checkbox-wrap input:checked ~ .perm-cb-visual::after {
    transform: rotate(45deg) scale(1) translateY(-1px);
}
.perm-checkbox-wrap:hover .perm-cb-visual {
    border-color: var(--sms-primary-light);
    box-shadow: 0 0 0 3px rgba(96,165,250,0.18);
}
/* Loading spinner state on checkbox cell */
.perm-cb-saving .perm-cb-visual {
    border-color: var(--sms-primary-light);
    background: var(--sms-primary-xlight);
    animation: permPulse 0.7s ease-in-out infinite alternate;
}
@keyframes permPulse {
    from { opacity: 0.6; } to { opacity: 1; }
}
</style>

<!-- ── Script ─────────────────────────────────────────────── -->
<script>
(function () {
    'use strict';

    var ENDPOINT = '<?= BASE_URL ?>/modules/user-management/includes/save-permissions.php';
    var CSRF = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';

    /* ── Toast helper (reuse from user-management.js if loaded, else inline) */
    function toast(msg, type) {
        if (typeof window.umShowToast === 'function') {
            window.umShowToast(msg, type);
            return;
        }
        var container = document.getElementById('umToastContainer');
        if (!container) return;
        var id   = 'pt-' + Date.now();
        var icons = { success:'fa-check-circle', danger:'fa-exclamation-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle' };
        container.insertAdjacentHTML('beforeend',
            '<div id="' + id + '" class="toast align-items-center text-bg-' + type + ' border-0 mb-2" role="alert" aria-atomic="true">'
            + '<div class="d-flex"><div class="toast-body d-flex align-items-center gap-2">'
            + (window.smsIconHtml ? window.smsIconHtml((icons[type] || icons.info).replace(/^fa-/, '')) : '') + ' ' + msg
            + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>'
            + '</div></div>');
        var el = document.getElementById(id);
        if (el && window.bootstrap) {
            var t = new bootstrap.Toast(el, { delay: 3000 });
            t.show();
            el.addEventListener('hidden.bs.toast', function () { el.remove(); });
        }
    }

    /* ── Update the role summary card count ─────────────────── */
    function updateRoleCount(roleKey) {
        var checkboxes = document.querySelectorAll('.perm-cb[data-role="' + roleKey + '"]');
        var checked    = 0;
        checkboxes.forEach(function (cb) { if (cb.checked) checked++; });
        checked += document.querySelectorAll('.perm-yes[data-role="' + roleKey + '"]').length;

        var countEl = document.querySelector('.role-module-count[data-role="' + roleKey + '"]');
        if (countEl) countEl.textContent = checked + ' module(s)';
    }

    /* ── Send permission change to server ───────────────────── */
    function savePermission(cb) {
        var role    = cb.dataset.role;
        var module  = cb.dataset.module;
        var granted = cb.checked;
        var cell    = cb.closest('td');
        var status  = document.getElementById('permSaveStatus');

        if (cell)   cell.classList.add('perm-cb-saving');
        if (status) status.textContent = 'Saving…';

        fetch(ENDPOINT, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ role: role, module: module, granted: granted, csrf_token: CSRF }),
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (cell) cell.classList.remove('perm-cb-saving');

            if (data.ok) {
                if (status) status.textContent = '';
                toast(
                    (granted ? 'Access granted' : 'Access revoked') + ': <strong>' + module + '</strong> → ' + role,
                    granted ? 'success' : 'warning'
                );
                updateRoleCount(role);
            } else {
                // Revert checkbox on failure
                cb.checked = !granted;
                if (status) status.textContent = '';
                toast(data.error || 'Could not save permission.', 'danger');
            }
        })
        .catch(function () {
            if (cell) cell.classList.remove('perm-cb-saving');
            cb.checked = !granted; // revert
            if (status) status.textContent = '';
            toast('Network error — permission not saved.', 'danger');
        });
    }

    /* ── Wire up all checkboxes ─────────────────────────────── */
    document.querySelectorAll('.perm-cb').forEach(function (cb) {
        cb.addEventListener('change', function () {
            savePermission(cb);
        });
        /* Init summary counts */
        updateRoleCount(cb.dataset.role);
    });

    /* Summary counts are based on actual checked access. */

})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
