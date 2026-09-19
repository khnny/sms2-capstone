<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/faculty/includes/final-defense-evaluation.php';

$pageTitle = 'Final Defense Evaluation';
$activeModule = 'faculty';
$activePage = 'panel-final-defense-evaluation';
$breadcrumbs = [
    ['label' => 'Panel Portal', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Final Defense Evaluation', 'url' => null],
];

require_once ROOT_PATH . '/includes/layout-start.php';
finalDefenseRequirePanelMember();
renderBreadcrumbs($breadcrumbs);

$crad = finalDefenseDb();
$message = '';
$error = '';
$selectedId = (int) ($_GET['id'] ?? 0);
$showHistory = (($_GET['history'] ?? '') === '1');

if (!$crad instanceof PDO) {
    $error = 'CRAD database connection is unavailable.';
} else {
    finalDefenseEnsureSchema($crad);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_final_evaluation') {
        if (!csrfVerify()) {
            $error = 'Security check failed. Please refresh and try again.';
        } else {
            $selectedId = (int) ($_POST['schedule_id'] ?? 0);
            $result = finalDefenseSubmitEvaluation($crad, $selectedId, $_POST);
            if (!empty($result['ok'])) {
                $message = (string) ($result['message'] ?? 'Evaluation submitted successfully.');
                $selectedId = 0;
            } else {
                $error = (string) ($result['error'] ?? 'Unable to submit evaluation.');
            }
        }
    }
}

$defense = $crad instanceof PDO && $selectedId > 0 ? finalDefenseAssignedSchedule($crad, $selectedId) : null;
if ($defense && $defense['evaluation_id'] !== null) {
    $defense = null;
    if ($error === '') {
        $error = 'This Final Defense already has your evaluation.';
    }
}
$rows = $crad instanceof PDO ? finalDefenseRows($crad, $showHistory) : [];
?>

<div class="glass-dashboard">
    <div class="glass-board">
        <?php if ($message !== ''): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

        <?php if (!$defense): ?>
            <section class="glass-panel p-4">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                    <h5 class="mb-0"><?= smsIcon('clipboard-check', ['class' => 'me-2 text-primary']) ?>Final Defenses for Evaluation</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/modules/faculty/pages/panel-final-defense-evaluation.php<?= $showHistory ? '' : '?history=1' ?>">
                            <?= smsIcon('history', ['class' => 'me-1']) ?><?= $showHistory ? 'Pending Evaluations' : 'Evaluation History' ?>
                        </a>
                        <span class="badge text-bg-primary"><?= count($rows) ?> Records</span>
                    </div>
                </div>
                <?php if (!$rows): ?>
                    <div class="text-center text-muted py-5">No Final Defense records found in this view.</div>
                <?php else: ?>
                    <div class="table-responsive"><table class="table align-middle mb-0">
                        <thead><tr><th>Research Group</th><th>Research Title</th><th>Date / Time</th><th>Venue</th><th><?= $showHistory ? 'Result' : 'Action' ?></th></tr></thead>
                        <tbody><?php foreach ($rows as $row): ?>
                            <tr>
                                <td><strong><?= e((string) (($row['research_group'] ?? '') ?: 'Research Group')) ?></strong><div class="small text-muted"><?= e((string) ($row['group_number'] ?? '')) ?></div></td>
                                <td><?= e((string) ($row['research_title'] ?? '')) ?></td>
                                <td><?= e(date('M j, Y h:i A', strtotime((string) $row['defense_datetime']))) ?></td>
                                <td><?= e((string) (($row['venue'] ?? '') ?: 'TBA')) ?></td>
                                <td><?php if ($showHistory): ?>
                                    <span class="badge text-bg-success"><?= e((string) ($row['panel_result'] ?? 'Submitted')) ?></span>
                                    <div class="small text-muted mt-1"><?= e((string) ($row['panel_score'] ?? '')) ?></div>
                                <?php else: ?>
                                    <a class="btn btn-sm btn-primary" href="<?= BASE_URL ?>/modules/faculty/pages/panel-final-defense-evaluation.php?id=<?= (int) $row['id'] ?>"><?= smsIcon('pen', ['class' => 'me-1']) ?>Evaluate</a>
                                <?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="glass-panel p-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                    <div><h5 class="mb-1">Final Defense Evaluation</h5><div class="text-muted"><?= e((string) ($defense['research_group'] ?? 'Research Group')) ?></div></div>
                    <span class="badge text-bg-success">Final Defense</span>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><small class="text-muted">Research Title</small><div class="fw-bold"><?= e((string) ($defense['research_title'] ?? '')) ?></div></div>
                    <div class="col-md-3"><small class="text-muted">Date</small><div><?= e(date('M j, Y', strtotime((string) $defense['defense_datetime']))) ?></div></div>
                    <div class="col-md-3"><small class="text-muted">Panel Member</small><div><?= e(getCurrentUserName()) ?></div></div>
                </div>
                <form method="post" id="finalDefenseEvalForm" data-total-max="<?= (int) finalDefenseEvaluationTotalMax() ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="submit_final_evaluation">
                    <input type="hidden" name="schedule_id" value="<?= (int) $defense['id'] ?>">
                    <p class="text-muted mb-3">Each criterion is worth <strong>20%</strong>. Perfect scores across all five criteria total <strong>100%</strong>. Scores above 20% cannot be submitted.</p>
                    <div class="row g-3 mb-3">
                        <?php foreach (finalDefenseRubric() as $criterion): ?>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><?= e($criterion['label']) ?> <span class="text-muted">(max <?= (int) $criterion['max'] ?>%)</span></label>
                                <input type="number"
                                       class="form-control js-final-score"
                                       name="<?= e($criterion['key']) ?>_score"
                                       min="0"
                                       max="<?= (int) $criterion['max'] ?>"
                                       step="0.01"
                                       data-max="<?= (int) $criterion['max'] ?>"
                                       data-label="<?= e($criterion['label']) ?>"
                                       required>
                                <div class="invalid-feedback d-block js-score-feedback"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Overall Total</label>
                        <input type="text" class="form-control" data-final-overall value="0.00" readonly>
                        <div class="form-text">Maximum <?= (int) finalDefenseEvaluationTotalMax() ?>%</div>
                    </div>
                    <div class="alert alert-danger d-none" id="finalScoreError" role="alert"></div>
                    <div class="mb-3"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="4"></textarea></div>
                    <div class="mb-3"><label class="form-label">Result</label><select class="form-select" name="result" required><option value="">Select result...</option><option value="APPROVED">APPROVED</option><option value="APPROVED WITH REVISION">APPROVED WITH REVISION</option><option value="FAILED">FAILED</option></select></div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="finalDefenseSubmitBtn"><?= smsIcon('check', ['class' => 'me-1']) ?>Submit Final Defense Evaluation</button>
                        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/modules/faculty/pages/panel-final-defense-evaluation.php">Back</a>
                    </div>
                </form>
                <script>
                (function () {
                    var form = document.getElementById('finalDefenseEvalForm');
                    if (!form) return;
                    var totalMax = parseFloat(form.getAttribute('data-total-max') || '100');
                    var errorEl = document.getElementById('finalScoreError');
                    var overallEl = form.querySelector('[data-final-overall]');
                    var submitBtn = document.getElementById('finalDefenseSubmitBtn');

                    function validate(showMessage) {
                        var sum = 0;
                        var firstError = '';
                        form.querySelectorAll('.js-final-score').forEach(function (input) {
                            var max = parseFloat(input.getAttribute('data-max') || '20');
                            var label = input.getAttribute('data-label') || 'Score';
                            var raw = String(input.value || '').trim();
                            var n = raw === '' ? NaN : parseFloat(raw);
                            var message = '';
                            if (raw !== '' && (isNaN(n) || n < 0)) {
                                message = label + ' Score must be 0 to ' + max + '.';
                            } else if (!isNaN(n) && n > max) {
                                message = label + ' Score cannot exceed ' + max + '%. Evaluation cannot be submitted.';
                            }
                            if (!isNaN(n)) sum += n;
                            input.classList.toggle('is-invalid', !!message);
                            var feedback = input.parentElement ? input.parentElement.querySelector('.js-score-feedback') : null;
                            if (feedback) feedback.textContent = message;
                            if (message && !firstError) firstError = message;
                        });
                        if (!firstError && sum > totalMax) {
                            firstError = 'Total score cannot exceed ' + totalMax + '%. Evaluation cannot be submitted.';
                        }
                        if (overallEl) overallEl.value = sum.toFixed(2);
                        if (errorEl) {
                            errorEl.textContent = (showMessage || firstError) ? (firstError || '') : '';
                            errorEl.classList.toggle('d-none', !errorEl.textContent);
                        }
                        if (submitBtn) submitBtn.disabled = !!firstError;
                        return !firstError;
                    }

                    form.querySelectorAll('.js-final-score').forEach(function (input) {
                        input.addEventListener('input', function () { validate(true); });
                        input.addEventListener('change', function () { validate(true); });
                    });
                    form.addEventListener('submit', function (e) {
                        if (!validate(true)) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    });
                    validate(false);
                })();
                </script>
            </section>
        <?php endif; ?>
    </div>
</div>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
