<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/includes/audit.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/final-phase-helpers.php';
requireAuth();
$role = getCurrentUserRoleKey();
if (!smsRoleAllowedForModule(['crad_officer', 'research_coordinator', 'adviser'], 'crad')) {
    http_response_code(403);
    exit('Forbidden');
}
$crad = cradDb();
finalPhaseEnsureSchema($crad);
$message = '';
$error = '';
$criteria = manuscriptEvaluationCriteria();
$totalMax = manuscriptEvaluationTotalMax();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $submissionId = (int) ($_POST['submission_id'] ?? 0);
        $action = (string) ($_POST['review_action'] ?? '');
        $stmt = $crad->prepare(
            "SELECT ms.*, rg.research_title, rg.group_name, rg.group_number
             FROM manuscript_submissions ms
             INNER JOIN research_groups rg ON rg.id = ms.research_group_id
             WHERE ms.id = ? LIMIT 1"
        );
        $stmt->execute([$submissionId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$submission) {
            $error = 'Manuscript submission not found.';
        } elseif (
            $role === 'adviser'
            && !fpIsAssignedAdviser(
                $crad,
                (int) $submission['research_group_id'],
                (int) ($_SESSION['user_id'] ?? 0),
                (string) ($_SESSION['user_email'] ?? '')
            )
        ) {
            $error = 'You are not assigned to this research group.';
        } elseif (!in_array($action, ['approve', 'revision'], true)) {
            $error = 'Invalid review action.';
        } else {
            $scores = [];
            foreach ($criteria as $item) {
                $raw = trim((string) ($_POST[$item['key'] . '_score'] ?? ''));
                if ($raw === '' || !is_numeric($raw)) {
                    $error = 'Please enter a valid score for ' . $item['label'] . '.';
                    break;
                }
                $value = (float) $raw;
                $weight = (float) $item['weight'];
                if ($value < 0) {
                    $error = $item['label'] . ' Score cannot be below 0.';
                    break;
                }
                if ($value > $weight) {
                    $error = $item['label'] . ' Score cannot exceed ' . rtrim(rtrim(number_format($weight, 2, '.', ''), '0'), '.') . '%. Evaluation was not submitted.';
                    break;
                }
                $scores[$item['key']] = round($value, 2);
            }
            if ($error === '') {
                $overall = round(array_sum($scores), 2);
                if ($overall > $totalMax) {
                    $error = 'Total score cannot exceed ' . number_format($totalMax, 0) . '%. Evaluation was not submitted.';
                } else {
                    $result = $action === 'approve' ? 'APPROVED' : 'FOR REVISION';
                    $status = $action === 'approve' ? 'Approved' : 'For Revision';
                    $remarks = trim((string) ($_POST['remarks'] ?? ''));
                    $crad->beginTransaction();
                    try {
                        $crad->prepare('UPDATE manuscript_submissions SET status = ?, reviewed_at = NOW() WHERE id = ?')
                            ->execute([$status, $submissionId]);
                        $crad->prepare(
                            "INSERT INTO manuscript_evaluations
                                (submission_id, research_group_id, evaluator_user_id, evaluator_name,
                                 content_score, methodology_score, results_score, conclusions_score,
                                 recommendations_score, references_score, formatting_score, compliance_score,
                                 remarks, result, overall_score)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        )->execute([
                            $submissionId,
                            $submission['research_group_id'],
                            (int) $_SESSION['user_id'],
                            (string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''),
                            $scores['content'],
                            $scores['methodology'],
                            $scores['results'],
                            $scores['conclusions'],
                            $scores['recommendations'],
                            $scores['references'],
                            $scores['formatting'],
                            $scores['compliance'],
                            $remarks,
                            $result,
                            $overall,
                        ]);
                        $crad->commit();
                        logActivity(
                            'update',
                            ($action === 'approve' ? 'Approved' : 'Returned') . ' final manuscript submission #' . $submissionId,
                            'crad'
                        );
                        $message = 'Manuscript review saved.';
                    } catch (Throwable $e) {
                        if ($crad->inTransaction()) {
                            $crad->rollBack();
                        }
                        error_log('Final manuscript review failed: ' . $e->getMessage());
                        $error = 'Unable to save manuscript review.';
                    }
                }
            }
        }
    }
}

$listSql = "SELECT ms.*, rg.group_number, rg.group_name, rg.research_title
            FROM manuscript_submissions ms
            INNER JOIN research_groups rg ON rg.id = ms.research_group_id
            INNER JOIN (
                SELECT research_group_id, MAX(version_number) version_number
                FROM manuscript_submissions
                GROUP BY research_group_id
            ) latest ON latest.research_group_id = ms.research_group_id
                   AND latest.version_number = ms.version_number";
$listParams = [];
if ($role === 'adviser') {
    $listSql .= " WHERE EXISTS (
        SELECT 1 FROM research_adviser_assignments raa
        WHERE raa.research_group_id = ms.research_group_id
          AND raa.assignment_status IN ('Assigned', 'Confirmed')
          AND (
                (raa.adviser_user_id IS NOT NULL AND raa.adviser_user_id = ?)
             OR (? <> '' AND LOWER(TRIM(COALESCE(raa.adviser_email, ''))) = LOWER(?))
          )
    )";
    $listParams = [
        (int) ($_SESSION['user_id'] ?? 0),
        (string) ($_SESSION['user_email'] ?? ''),
        (string) ($_SESSION['user_email'] ?? ''),
    ];
}
$listSql .= ' ORDER BY ms.submitted_at DESC';
$stmt = $crad->prepare($listSql);
$stmt->execute($listParams);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
$breadcrumbs = [
    ['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'],
    ['label' => 'Final Manuscript Review', 'url' => null],
];
require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<div class="glass-dashboard">
    <div class="glass-board">
        <div class="glass-panel">
            <div class="glass-panel-body">
                <h5 class="glass-panel-title">Final Manuscript Review</h5>
                <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if (!$rows): ?>
                    <p class="text-muted">No final manuscript submissions yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Group</th>
                                    <th>Version</th>
                                    <th>File</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td>
                                            <?= e((string) $row['group_number']) ?>
                                            <div class="small text-muted"><?= e((string) $row['research_title']) ?></div>
                                        </td>
                                        <td>v<?= (int) $row['version_number'] ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/modules/crad/api/final-manuscript-document.php?id=<?= (int) $row['id'] ?>" target="_blank">
                                                <?= e((string) $row['original_name']) ?>
                                            </a>
                                        </td>
                                        <td><?= e((string) $row['status']) ?></td>
                                        <td>
                                            <?php if ($row['status'] !== 'Approved'): ?>
                                                <details>
                                                    <summary class="btn btn-sm btn-primary">Review</summary>
                                                    <form method="post"
                                                          class="mt-3 manuscript-review-form"
                                                          data-total-max="<?= e((string) $totalMax) ?>"
                                                          style="min-width:340px">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="submission_id" value="<?= (int) $row['id'] ?>">
                                                        <?php foreach ($criteria as $item): ?>
                                                            <?php $maxLabel = rtrim(rtrim(number_format((float) $item['weight'], 2, '.', ''), '0'), '.'); ?>
                                                            <label class="form-label small mb-1">
                                                                <?= e($item['label']) ?> Score
                                                                <span class="text-muted">(max <?= e($maxLabel) ?>%)</span>
                                                            </label>
                                                            <input class="form-control form-control-sm mb-1 js-ms-score"
                                                                   type="number"
                                                                   name="<?= e($item['key']) ?>_score"
                                                                   min="0"
                                                                   max="<?= e((string) $item['weight']) ?>"
                                                                   step="0.01"
                                                                   data-max="<?= e((string) $item['weight']) ?>"
                                                                   data-label="<?= e($item['label']) ?>"
                                                                   placeholder="<?= e($item['label']) ?> score"
                                                                   required>
                                                            <div class="invalid-feedback js-ms-feedback mb-2"></div>
                                                        <?php endforeach; ?>
                                                        <div class="alert alert-light border d-flex justify-content-between align-items-center py-2 mb-2">
                                                            <strong class="small mb-0">Total</strong>
                                                            <span class="fw-bold"><span class="js-ms-total">0.00</span> / <?= number_format($totalMax, 0) ?>%</span>
                                                        </div>
                                                        <div class="alert alert-danger d-none js-ms-error py-2" role="alert"></div>
                                                        <textarea class="form-control form-control-sm mb-2" name="remarks" placeholder="Remarks"></textarea>
                                                        <button type="submit" class="btn btn-success btn-sm js-ms-submit" name="review_action" value="approve">Approve</button>
                                                        <button type="submit" class="btn btn-warning btn-sm js-ms-submit" name="review_action" value="revision">Request Revision</button>
                                                    </form>
                                                </details>
                                            <?php else: ?>
                                                Approved
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    function validateForm(form, showMessage) {
        var inputs = form.querySelectorAll('.js-ms-score');
        var totalMax = parseFloat(form.getAttribute('data-total-max') || '100');
        var totalEl = form.querySelector('.js-ms-total');
        var errorEl = form.querySelector('.js-ms-error');
        var totalWrap = totalEl ? totalEl.closest('.alert') : null;
        var buttons = form.querySelectorAll('.js-ms-submit');
        var sum = 0;
        var firstError = '';

        inputs.forEach(function (input) {
            var max = parseFloat(input.getAttribute('data-max') || input.max || '12.5');
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
            input.setCustomValidity(message);
            var feedback = input.parentElement
                ? input.parentElement.querySelector('.js-ms-feedback')
                : null;
            if (!feedback) {
                feedback = input.nextElementSibling && input.nextElementSibling.classList.contains('js-ms-feedback')
                    ? input.nextElementSibling
                    : null;
            }
            if (feedback && feedback.classList.contains('js-ms-feedback')) {
                feedback.textContent = message;
                feedback.style.display = message ? 'block' : '';
            }
            if (message && !firstError) firstError = message;
        });

        if (!firstError && sum > totalMax) {
            firstError = 'Total score cannot exceed ' + totalMax + '%. Evaluation cannot be submitted.';
        }

        if (totalEl) totalEl.textContent = sum.toFixed(2);
        if (totalWrap) {
            totalWrap.classList.toggle('alert-danger', !!firstError);
            totalWrap.classList.toggle('alert-light', !firstError);
        }
        if (errorEl) {
            errorEl.textContent = (showMessage || firstError) ? (firstError || '') : '';
            errorEl.classList.toggle('d-none', !errorEl.textContent);
        }
        buttons.forEach(function (btn) { btn.disabled = !!firstError; });
        return !firstError;
    }

    document.querySelectorAll('.manuscript-review-form').forEach(function (form) {
        form.querySelectorAll('.js-ms-score').forEach(function (input) {
            input.addEventListener('input', function () { validateForm(form, true); });
            input.addEventListener('change', function () { validateForm(form, true); });
        });
        form.addEventListener('submit', function (e) {
            if (!validateForm(form, true)) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        });
        validateForm(form, false);
    });
})();
</script>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
