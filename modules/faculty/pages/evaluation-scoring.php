<?php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/modules/crad/includes/chapter-evaluation-workflow.php';

$pageTitle    = 'Evaluation & Scoring';
$activeModule = 'faculty';
$activePage   = 'evaluation-scoring';
$pageBannerIcon = 'fa-star-half-alt';
$pageBannerDescription = 'Evaluate and score submitted research chapters.';
$breadcrumbs  = [
    ['label' => 'Grammarian Portal', 'url' => BASE_URL . '/modules/faculty/index.php'],
    ['label' => 'Evaluation & Scoring', 'url' => null],
];

requireAuth();
if (!chapterIsEvaluator()) {
    http_response_code(403);
    exit('Forbidden');
}
$crad = chapterDb();
$id = (int) ($_GET['id'] ?? ($_POST['submission_id'] ?? 0));
$submission = $id > 0 ? chapterGetSubmission($crad, $id) : null;
$message = '';
$error = '';
if ($submission && !chapterEvaluatorCanAccess($submission)) {
    $error = 'This chapter submission is no longer in the active evaluation queue.';
    $submission = null;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $error = 'Security check failed. Please refresh and try again.';
    } elseif (!$submission) {
        $error = 'Submission not found.';
    } elseif (($_POST['action'] ?? '') === 'start_review') {
        $result = chapterStartReview($crad, $submission);
        $message = !empty($result['ok']) ? (string) ($result['message'] ?? 'Review started.') : '';
        $error = empty($result['ok']) ? (string) ($result['error'] ?? 'Unable to start review.') : '';
        $submission = chapterGetSubmission($crad, $id);
    } elseif (($_POST['action'] ?? '') === 'submit_evaluation') {
        $result = chapterSubmitEvaluation($crad, $submission, $_POST);
        $message = !empty($result['ok']) ? 'Evaluation saved. Student status updated to ' . (string) $result['status'] . '.' : '';
        $error = empty($result['ok']) ? (string) ($result['error'] ?? 'Unable to save evaluation.') : '';
        $submission = chapterGetSubmission($crad, $id);
    }
}
$queue = $submission ? [] : chapterEvaluatorQueue($crad, false);
$postedScores = (($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_evaluation' && $error !== '')) ? $_POST : [];

require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="glass-dashboard">
    <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <?php if (!$submission): ?>
        <section class="glass-panel p-4">
            <h5 class="mb-3"><?= smsIcon('list', ['class' => 'me-2 text-primary']) ?>Select Submission</h5>
            <?php if (!$queue): ?><div class="text-center text-muted py-5">No chapter submissions are currently waiting for scoring.</div>
            <?php else: ?><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Group</th><th>Title</th><th>Chapter</th><th>Version</th><th>Status</th><th></th></tr></thead><tbody>
                <?php foreach ($queue as $row): ?><tr><td><?= e($row['group_name']) ?></td><td><?= e($row['research_title']) ?></td><td><?= e(chapterLabel((int) $row['chapter_number'])) ?></td><td>Version <?= (int) $row['version_number'] ?></td><td><span class="badge text-bg-<?= e(chapterStatusClass((string) $row['status'])) ?>"><?= e($row['status']) ?></span></td><td><a class="btn btn-sm btn-sms-primary" href="?id=<?= (int) $row['id'] ?>">Open</a></td></tr><?php endforeach; ?>
            </tbody></table></div><?php endif; ?>
        </section>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-4">
                <section class="glass-panel p-4 h-100">
                    <h5 class="mb-3"><?= smsIcon('info-circle', ['class' => 'me-2 text-primary']) ?>Research Information</h5>
                    <div class="mb-2"><small class="text-muted">Group</small><div class="fw-bold"><?= e($submission['group_name']) ?> · <?= e($submission['group_number']) ?></div></div>
                    <div class="mb-2"><small class="text-muted">Research Title</small><div class="fw-bold"><?= e($submission['research_title']) ?></div></div>
                    <div class="mb-2"><small class="text-muted">Chapter</small><div><?= e(chapterLabel((int) $submission['chapter_number'])) ?> · Version <?= (int) $submission['version_number'] ?></div></div>
                    <div class="mb-2"><small class="text-muted">Submitted By</small><div><?= e($submission['submitted_by_name']) ?></div></div>
                    <div class="mb-3"><small class="text-muted">Submitted Date</small><div><?= e(chapterFormatDate((string) $submission['submitted_at'])) ?></div></div>
                    <span class="badge text-bg-<?= e(chapterStatusClass((string) $submission['status'])) ?>"><?= e($submission['status']) ?></span>
                    <hr>
                    <a class="btn btn-outline-primary w-100 mb-2" href="<?= e(chapterDocumentUrl((int) $submission['id'])) ?>" target="_blank" rel="noopener noreferrer"><?= smsIcon('eye', ['class' => 'me-2']) ?>View/Open Document</a>
                    <a class="btn btn-outline-secondary w-100" href="<?= e(chapterDocumentUrl((int) $submission['id'])) ?>&download=1"><?= smsIcon('download', ['class' => 'me-2']) ?>Download Document</a>
                </section>
            </div>
            <div class="col-lg-8">
                <section class="glass-panel p-4">
                    <?php if (!empty($submission['evaluation_id'])): ?>
                        <h5 class="mb-3">Evaluation Completed</h5>
                        <p class="text-muted mb-3">This submission already has a saved evaluation and cannot be scored again.</p>
                        <div class="table-responsive mb-3">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Criterion</th><th>Weight</th><th>Score</th><th>Remarks</th></tr></thead>
                                <tbody>
                                    <?php foreach (chapterEvaluationCriteria() as $item):
                                        $scoreKey = $item['key'] . '_score';
                                        $remarksKey = $item['key'] . '_remarks';
                                    ?>
                                        <tr>
                                            <td><?= e($item['label']) ?></td>
                                            <td><?= number_format((float) $item['weight'], 0) ?>%</td>
                                            <td><?= e(number_format((float) ($submission[$scoreKey] ?? 0), 2)) ?> / <?= number_format((float) $item['weight'], 0) ?></td>
                                            <td><?= e((string) ($submission[$remarksKey] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th><?= e(number_format((float) ($submission['overall_score'] ?? 0), 2)) ?> / <?= number_format(chapterEvaluationTotalMax(), 0) ?>%</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/modules/faculty/pages/evaluation-history.php">View Evaluation History</a>
                    <?php elseif ((string) $submission['status'] === 'Submitted'): ?>
                        <h5 class="mb-3">Start Review</h5>
                        <p class="text-muted">Start the review to update the student-facing status to Under Review.</p>
                        <form method="post" data-once-form><?= csrfField() ?><input type="hidden" name="submission_id" value="<?= (int) $submission['id'] ?>"><input type="hidden" name="action" value="start_review"><button class="btn btn-sms-primary" data-submit-once><?= smsIcon('play', ['class' => 'me-2']) ?>Start Review</button></form>
                    <?php else: ?>
                        <h5 class="mb-3"><?= smsIcon('star-half-alt', ['class' => 'me-2 text-primary']) ?>Evaluation</h5>
                        <p class="text-muted mb-3">Each criterion is worth <strong>20%</strong>. Perfect scores across all five criteria total <strong>100%</strong>. Scores above the standard cannot be submitted.</p>
                        <form method="post" data-once-form id="grammarianEvalForm" data-max-score="<?= htmlspecialchars((string) chapterEvaluationMaxPoints()) ?>" data-total-max="<?= htmlspecialchars((string) chapterEvaluationTotalMax()) ?>">
                            <?= csrfField() ?><input type="hidden" name="submission_id" value="<?= (int) $submission['id'] ?>"><input type="hidden" name="action" value="submit_evaluation">
                            <?php foreach (chapterEvaluationCriteria() as $item):
                                $postedVal = $postedScores[$item['key'] . '_score'] ?? '';
                                $postedRemarks = $postedScores[$item['key'] . '_remarks'] ?? '';
                            ?>
                                <div class="row g-2 align-items-end mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label" for="<?= e($item['key']) ?>Score"><?= e($item['label']) ?> Score (<?= number_format((float) $item['weight'], 0) ?>%)</label>
                                        <input id="<?= e($item['key']) ?>Score"
                                               type="number"
                                               class="form-control js-eval-score"
                                               name="<?= e($item['key']) ?>_score"
                                               min="0"
                                               max="<?= number_format((float) $item['weight'], 0) ?>"
                                               step="0.01"
                                               inputmode="decimal"
                                               data-label="<?= e($item['label']) ?>"
                                               data-max="<?= htmlspecialchars((string) $item['weight']) ?>"
                                               value="<?= e((string) $postedVal) ?>"
                                               required>
                                        <div class="invalid-feedback js-score-feedback"></div>
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label" for="<?= e($item['key']) ?>Remarks"><?= e($item['label']) ?> Remarks</label>
                                        <input id="<?= e($item['key']) ?>Remarks" type="text" class="form-control" name="<?= e($item['key']) ?>_remarks" maxlength="1000" value="<?= e((string) $postedRemarks) ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-2">
                                <strong>Total Score</strong>
                                <span class="fs-5 fw-bold"><span id="evalTotalScore">0.00</span> / <?= number_format(chapterEvaluationTotalMax(), 0) ?>%</span>
                            </div>
                            <div id="evalScoreError" class="alert alert-danger<?= $error !== '' ? '' : ' d-none' ?>" role="alert"><?= $error !== '' ? e($error) : '' ?></div>
                            <div class="mb-3"><label class="form-label" for="overallFeedback">General Evaluation Feedback</label><textarea id="overallFeedback" class="form-control" name="overall_feedback" rows="4"><?= e((string) ($postedScores['overall_feedback'] ?? '')) ?></textarea></div>
                            <div class="mb-3"><label class="form-label" for="evaluationResult">Result</label><select id="evaluationResult" class="form-select" name="result" required><option value="">Select result...</option><option value="APPROVED"<?= (($postedScores['result'] ?? '') === 'APPROVED') ? ' selected' : '' ?>>APPROVED</option><option value="APPROVED WITH REVISION"<?= (($postedScores['result'] ?? '') === 'APPROVED WITH REVISION') ? ' selected' : '' ?>>APPROVED WITH REVISION</option></select></div>
                            <button type="submit" class="btn btn-sms-primary" id="evalSubmitBtn" data-submit-once><?= smsIcon('check', ['class' => 'me-2']) ?>Submit Evaluation</button>
                        </form>
                        <script>
                        (function () {
                            var form = document.getElementById('grammarianEvalForm');
                            if (!form) return;
                            var inputs = form.querySelectorAll('.js-eval-score');
                            var totalEl = document.getElementById('evalTotalScore');
                            var errorEl = document.getElementById('evalScoreError');
                            var submitBtn = document.getElementById('evalSubmitBtn');
                            var totalMax = parseFloat(form.getAttribute('data-total-max') || '100');
                            var totalWrap = totalEl ? totalEl.closest('.alert') : null;

                            function showError(message) {
                                if (!errorEl) return;
                                errorEl.textContent = message || '';
                                errorEl.classList.toggle('d-none', !message);
                            }

                            function validateScores(showMessage) {
                                var sum = 0;
                                var firstError = '';
                                inputs.forEach(function (input) {
                                    var max = parseFloat(input.getAttribute('data-max') || input.max || '20');
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
                                    var feedback = input.parentElement ? input.parentElement.querySelector('.js-score-feedback') : null;
                                    if (feedback) feedback.textContent = message;
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
                                if (showMessage || firstError) showError(firstError);
                                else if (errorEl && !errorEl.textContent) showError('');
                                if (submitBtn && !submitBtn.dataset.originalHtml) {
                                    submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                                }
                                if (submitBtn && submitBtn.dataset.saving !== '1') {
                                    submitBtn.disabled = !!firstError;
                                }
                                return !firstError;
                            }

                            inputs.forEach(function (input) {
                                input.addEventListener('input', function () { validateScores(true); });
                                input.addEventListener('change', function () { validateScores(true); });
                            });

                            form.addEventListener('submit', function (e) {
                                if (!validateScores(true)) {
                                    e.preventDefault();
                                    e.stopImmediatePropagation();
                                    if (submitBtn) {
                                        submitBtn.disabled = true;
                                        submitBtn.dataset.saving = '';
                                        if (submitBtn.dataset.originalHtml) {
                                            submitBtn.innerHTML = submitBtn.dataset.originalHtml;
                                        }
                                    }
                                    var invalid = form.querySelector('.js-eval-score.is-invalid');
                                    if (invalid) invalid.focus();
                                    return false;
                                }
                            }, true);

                            validateScores(false);
                        })();
                        </script>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    <?php endif; ?>
</div>
<script src="<?= BASE_URL ?>/assets/js/chapter-evaluation-live.js"></script>
<?php require_once __DIR__ . '/../../../includes/layout-end.php'; ?>
