<?php
/**
 * Student Portal - Progress Updates
 * Submit progress updates with duplicate prevention
 */

$pageTitle = 'Progress Updates';
$activeModule = 'student_portal';
$activePage = 'progress-updates';

$pageBannerIcon        = 'fa-chart-line';
$pageBannerDescription = 'Submit your research development progress.';

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/breadcrumbs.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once __DIR__ . '/../../../modules/crad/includes/research-progress-helpers.php';

$breadcrumbs = [
    ['label' => 'Student Portal',    'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
    ['label' => 'My Research',       'url' => BASE_URL . '/modules/student-portal/pages/my-research.php'],
    ['label' => 'Progress Updates',  'url' => null],
];

require_once __DIR__ . '/../../../includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);

// Check if module is properly installed
try {
    $crad = cradDb();
    $tablesCheck = $crad->query("SHOW TABLES LIKE 'research_plans'")->fetch();
    if (!$tablesCheck) {
        throw new Exception('Research Progress module not installed.');
    }
} catch (Throwable $e) {
    echo '<div class="alert alert-warning">'
        . smsIcon('exclamation-triangle', ['class' => 'me-2'])
        . '<strong>Module Not Installed</strong><br>'
        . 'The Research Progress module database tables are not yet installed.'
        . '</div>';
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

// Get student's research group — only if it is in the Capstone Group/Student Registry
$studentId     = trim((string) ($_SESSION['student_id'] ?? ''));
$studentUserId = (int) ($_SESSION['user_id'] ?? 0);
$studentName   = trim((string) ($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''));

$researchGroup = rpGetRegisteredResearchGroup($crad, $studentId, $studentUserId);

if (!$researchGroup) {
    echo '<div class="alert alert-info">'
        . smsIcon('info-circle', ['class' => 'me-2'])
        . '<strong>Research Development is not yet available.</strong><br>'
        . 'Your research group must be officially registered in the Capstone Group/Student Registry before you can access this section.'
        . ' Please ensure your title approval is fully signed and your adviser and coordinator assignments are in place.'
        . '</div>';
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

$groupId = (int) $researchGroup['id'];
$defaultUpdateTitle = trim((string) ($researchGroup['research_title'] ?? ''));

// Get or create research plan
$plan = rpGetOrCreateResearchPlan($crad, $groupId);

// Get milestones from Research Development progress only.
// Uses rpGetMilestonesWithPendingFlags so each milestone carries has_pending_submission.
$milestones = rpGetMilestonesWithPendingFlags($crad, (int) $plan['id'], $groupId);

// Build a lookup: milestone_id => has_pending_submission (bool), for use in PHP template
// and also serialised into JS as the initial pending-state map.
$milestonePendingMap = [];
foreach ($milestones as $m) {
    $milestonePendingMap[(int) $m['id']] = !empty($m['has_pending_submission']);
}

// Get pre-selected milestone if provided
$selectedMilestoneId = isset($_GET['milestone_id']) ? (int)$_GET['milestone_id'] : null;
$selectedMilestone = null;
if ($selectedMilestoneId) {
    foreach ($milestones as $m) {
        if ((int)$m['id'] === $selectedMilestoneId) {
            $selectedMilestone = $m;
            break;
        }
    }
}

// Determine whether the pre-selected (or only) milestone is already pending review.
// This drives the initial render state without a round-trip.
$selectedIsPending = $selectedMilestone && !empty($selectedMilestone['has_pending_submission']);

// Get recent progress updates
try {
    $recentUpdatesStmt = $crad->prepare("
        SELECT rpu.*, rm.milestone_name
        FROM research_progress_updates rpu
        LEFT JOIN research_milestones rm ON rm.id = rpu.milestone_id
        WHERE rpu.research_group_id = ?
        ORDER BY rpu.submitted_at DESC
        LIMIT 5
    ");
    $recentUpdatesStmt->execute([$groupId]);
    $recentUpdates = $recentUpdatesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Recent updates query error: ' . $e->getMessage());
    $recentUpdates = [];
}
?>

<div class="glass-dashboard">
    <div class="glass-board">

        <div class="row g-4">
            <!-- Submit Form -->
            <div class="col-lg-7">
                <div class="glass-panel">
                    <div class="glass-panel-body">
                        <div class="glass-panel-head">
                            <div>
                                <h5 class="glass-panel-title">Submit Progress Update</h5>
                                <p class="glass-panel-sub">Report your research development progress</p>
                            </div>
                        </div>

                        <form id="progressUpdateForm" enctype="multipart/form-data">
                            <input type="hidden" id="submission_token" name="submission_token" value="">
                            <div id="progress_form_alert" class="alert d-none" role="alert"></div>

                            <!-- Milestone Selection -->
                            <div class="mb-3">
                                <label class="form-label" style="font-weight:700;color:var(--sms-heading);">
                                    Milestone <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="milestone_id" name="milestone_id" required>
                                    <option value="">-- Select Milestone --</option>
                                    <?php foreach ($milestones as $milestone): ?>
                                        <option value="<?= $milestone['id'] ?>" 
                                                data-current-progress="<?= $milestone['progress_percentage'] ?>"
                                                data-current-status="<?= htmlspecialchars((string) ($milestone['status'] ?? 'Not Started'), ENT_QUOTES) ?>"
                                                data-chapter-number="<?= (int) ($milestone['chapter_number'] ?? 0) ?>"
                                                data-pending="<?= empty($milestone['has_pending_submission']) ? '0' : '1' ?>"
                                                data-pending-at="<?= htmlspecialchars((string) ($milestone['pending_submitted_at'] ?? ''), ENT_QUOTES) ?>"
                                                <?= $selectedMilestone && (int)$milestone['id'] === (int)$selectedMilestone['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($milestone['milestone_name']) ?> 
                                            (Current: <?= number_format((float)$milestone['progress_percentage'], 1) ?>%)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Progress Percentage -->
                            <div class="mb-3">
                                <label class="form-label" style="font-weight:700;color:var(--sms-heading);">
                                    New Progress Percentage <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" class="form-range flex-grow-1" 
                                           id="new_progress" name="new_progress" 
                                           min="0" max="100" value="<?= $selectedMilestone ? $selectedMilestone['progress_percentage'] : 0 ?>" step="1">
                                    <div style="font-size:1.5rem;font-weight:800;color:var(--sms-primary);min-width:60px;text-align:right;">
                                        <span id="progress_display">0</span>%
                                    </div>
                                </div>
                                <small class="text-muted">Current Progress: <strong id="current_progress_display">0%</strong></small>
                            </div>

                            <!-- Status -->
                            <div class="mb-3">
                                <label class="form-label" style="font-weight:700;color:var(--sms-heading);">
                                    Milestone Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="milestone_status" name="milestone_status" required>
                                    <option value="Submitted for Review">Submitted for Review</option>
                                </select>
                                <small class="text-muted">Select "Submitted for Review" when ready for adviser review</small>
                            </div>

                            <!-- Document -->
                            <div class="mb-3">
                                <label class="form-label" style="font-weight:700;color:var(--sms-heading);">
                                    Document <span class="text-danger d-none" id="document_required_marker">*</span>
                                </label>
                                <input type="file" class="form-control" id="document" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <div class="form-text">Allowed: PDF, DOC, DOCX, JPG, PNG. Max 10 MB.</div>
                            </div>

                            <!-- Update Title -->
                            <div class="mb-3">
                                <label class="form-label" style="font-weight:700;color:var(--sms-heading);">
                                    Update Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" 
                                       id="update_title" name="update_title" 
                                       placeholder="e.g., Completed database design and implementation" 
                                       value="<?= htmlspecialchars($defaultUpdateTitle, ENT_QUOTES) ?>"
                                       required maxlength="255">
                            </div>

                            <!-- Accomplishments -->
                            <div class="mb-3">
                                <label class="form-label" style="font-weight:700;color:var(--sms-heading);">
                                    Accomplishments <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="accomplishments" name="accomplishments" 
                                          rows="4" required
                                          placeholder="What have you completed? List your achievements and completed tasks."></textarea>
                            </div>

                            <!-- Problems/Blockers -->
                            <div class="mb-3">
                                <label class="form-label" style="font-weight:700;color:var(--sms-heading);">
                                    Problems / Blockers (Optional)
                                </label>
                                <textarea class="form-control" id="problems_blockers" name="problems_blockers" 
                                          rows="3"
                                          placeholder="Any challenges, issues, or roadblocks encountered?"></textarea>
                            </div>

                            <!-- Next Planned Activity -->
                            <div class="mb-3">
                                <label class="form-label" style="font-weight:700;color:var(--sms-heading);">
                                    Next Planned Activity <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="next_planned_activity" name="next_planned_activity" 
                                          rows="3" required
                                          placeholder="What will you work on next?"></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" id="submitBtn" class="btn btn-primary btn-lg">
                                    <?= smsIcon('paper-plane', ['class' => 'me-2']) ?>Submit Progress Update
                                </button>
                            </div>
                            <div id="submitted_state" class="alert alert-success d-none mt-3" role="status">
                                <div class="d-flex align-items-start gap-2">
                                    <?= smsIcon('check-circle', ['class' => 'mt-1']) ?>
                                    <div>
                                        <strong>Done Sent.</strong>
                                        <div>Your progress update has been submitted and sent to your adviser for review.</div>
                                        <div class="d-flex gap-2 flex-wrap mt-3">
                                            <a class="btn btn-sm btn-success" href="<?= BASE_URL ?>/modules/student-portal/pages/my-research.php">
                                                <?= smsIcon('chart-line', ['class' => 'me-1']) ?>View My Research
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-success" id="submitAnotherBtn">
                                                <?= smsIcon('plus', ['class' => 'me-1']) ?>Submit Another Update
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending-review lock banner.
                                 Shown whenever the selected milestone already has a
                                 submission awaiting adviser action (has_pending_submission=true).
                                 Hidden/shown entirely by JS — no page reload needed. -->
                            <div id="pending_review_state" class="d-none mt-3" role="status">
                                <div class="p-4" style="border-radius:12px;background:rgba(59,130,246,0.07);border:1.5px solid rgba(59,130,246,0.25);">
                                    <div class="d-flex align-items-start gap-3">
                                        <div style="flex-shrink:0;width:40px;height:40px;border-radius:10px;background:rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:center;">
                                            <?= smsIcon('paper-plane', ['style' => 'color:#3b82f6;font-size:1.1rem;']) ?>
                                        </div>
                                        <div style="flex:1;">
                                            <div style="font-weight:700;color:var(--sms-heading);font-size:1rem;margin-bottom:4px;">
                                                Progress Update Submitted
                                            </div>
                                            <div id="pending_review_milestone_label" style="font-size:0.85rem;color:var(--sms-text-muted);margin-bottom:10px;">
                                                Your progress update is currently waiting for Adviser review.
                                            </div>
                                            <span class="badge" style="background:rgba(59,130,246,0.12);color:#3b82f6;font-weight:700;font-size:0.78rem;padding:6px 14px;border-radius:20px;">
                                                <?= smsIcon('clock', ['class' => 'me-1']) ?>Waiting for Adviser Review
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

            <!-- Recent Updates -->
            <div class="col-lg-5">
                <div class="glass-panel">
                    <div class="glass-panel-body">
                        <div class="glass-panel-head">
                            <div>
                                <h5 class="glass-panel-title">Recent Updates</h5>
                                <p class="glass-panel-sub">Your submission history</p>
                            </div>
                        </div>

                        <?php if (!empty($recentUpdates)): ?>
                            <?php foreach ($recentUpdates as $update): ?>
                                <div class="mb-3 pb-3" style="border-bottom:1px solid var(--sms-border-soft);">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div style="flex:1;">
                                            <div style="font-weight:700;color:var(--sms-heading);font-size:0.9rem;">
                                                <?= htmlspecialchars($update['update_title']) ?>
                                            </div>
                                            <?php if ($update['milestone_name']): ?>
                                                <div style="font-size:0.75rem;color:var(--sms-text-muted);">
                                                    <?= smsIcon('bookmark', ['class' => 'me-1']) ?>
                                                    <?= htmlspecialchars($update['milestone_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end ms-3">
                                            <div style="font-size:1.1rem;font-weight:800;color:var(--sms-primary);">
                                                <?= number_format((float)$update['new_progress'], 0) ?>%
                                            </div>
                                        </div>
                                    </div>
                                    <div style="font-size:0.75rem;color:var(--sms-text-muted);">
                                        <?= smsIcon('clock', ['class' => 'me-1']) ?>
                                        <?= date('M d, Y g:i A', strtotime($update['submitted_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">
                                <?= smsIcon('inbox', ['style' => 'font-size:2rem;color:var(--sms-border);display:block;margin-bottom:1rem;']) ?>
                                No progress updates yet
                            </p>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- Help Card -->
                <div class="glass-panel mt-3">
                    <div class="glass-panel-body">
                        <div style="font-weight:700;color:var(--sms-heading);margin-bottom:1rem;">
                            <?= smsIcon('info-circle', ['class' => 'me-2', 'style' => 'color:var(--sms-primary);']) ?>
                            Tips for Progress Updates
                        </div>
                        <ul style="font-size:0.85rem;color:var(--sms-text);line-height:1.8;margin:0;padding-left:1.5rem;">
                            <li>Be specific about completed tasks</li>
                            <li>Include measurable achievements</li>
                            <li>Report problems early for assistance</li>
                            <li>Update regularly (weekly recommended)</li>
                            <li>Mark "Submitted for Review" when ready</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
// Generate submission token on page load
let submissionToken = null;

document.addEventListener('DOMContentLoaded', function() {
    // Generate token
    fetch('<?= BASE_URL ?>/modules/crad/api/research-progress.php?action=generate_token')
        .then(r => r.json())
        .then(data => {
            if (data.success && data.token) {
                submissionToken = data.token;
                document.getElementById('submission_token').value = data.token;
            }
        })
        .catch(err => console.error('Token generation failed:', err));

    // Pending-submission map seeded from PHP at page-render time.
    // Keys are milestone IDs (strings), values are booleans.
    // Updated in real-time by refreshMilestoneOptions() after every poll / submit.
    const pendingMap = <?= json_encode(array_map('boolval', $milestonePendingMap), JSON_FORCE_OBJECT) ?>;

    const progressInput          = document.getElementById('new_progress');
    const progressDisplay        = document.getElementById('progress_display');
    const milestoneSelect        = document.getElementById('milestone_id');
    const currentProgressDisplay = document.getElementById('current_progress_display');
    const statusSelect           = document.getElementById('milestone_status');
    const documentInput          = document.getElementById('document');
    const documentRequiredMarker = document.getElementById('document_required_marker');
    const submitBtn              = document.getElementById('submitBtn');
    const pendingReviewState     = document.getElementById('pending_review_state');
    const pendingReviewLabel     = document.getElementById('pending_review_milestone_label');
    let isSubmitting = false;
    let formLocked   = false;

    progressInput.addEventListener('input', function() {
        progressDisplay.textContent = this.value;
    });

    // ------------------------------------------------------------------ //
    // updateMilestoneState — replaces the old updateApprovedState().
    // Handles three mutually exclusive lock states:
    //   1. Approved          — milestone fully approved by adviser
    //   2. Pending review    — submitted and awaiting adviser action
    //   3. Available         — student may submit
    // ------------------------------------------------------------------ //
    function updateMilestoneState(currentStatus, isPending, milestoneName, pendingAt) {
        const isApproved = currentStatus === 'Approved';

        // Lock inputs for any non-submittable state
        const lockInputs = isApproved || isPending;
        progressInput.disabled  = lockInputs;
        statusSelect.disabled   = lockInputs;
        documentInput.disabled  = lockInputs;

        // Show/hide the pending-review banner
        if (pendingReviewState) {
            pendingReviewState.classList.toggle('d-none', !isPending);
        }

        if (isPending && pendingReviewLabel && milestoneName) {
            let labelHtml = 'Your <strong>' + escapeHtml(milestoneName) + '</strong> progress update is currently waiting for Adviser review.';
            if (pendingAt) {
                try {
                    const d = new Date(pendingAt.replace(' ', 'T'));
                    if (!isNaN(d.getTime())) {
                        labelHtml += ' <span style="color:var(--sms-text-muted);font-size:0.8rem;">Submitted ' + d.toLocaleString() + '</span>';
                    }
                } catch (_) { /* ignore date parse errors */ }
            }
            pendingReviewLabel.innerHTML = labelHtml;
        }

        if (!isSubmitting && !formLocked) {
            submitBtn.disabled = lockInputs;
            if (isApproved) {
                submitBtn.classList.replace('btn-primary', 'btn-success');
                submitBtn.innerHTML = '<?= smsIcon('check-circle', ['class' => 'me-2']) ?>Milestone Approved';
            } else if (isPending) {
                submitBtn.classList.replace('btn-primary', 'btn-secondary');
                submitBtn.innerHTML = '<?= smsIcon('clock', ['class' => 'me-2']) ?>Waiting for Adviser Review';
            } else {
                // Restore to submittable state (e.g. adviser returned the milestone)
                submitBtn.classList.remove('btn-success', 'btn-secondary');
                submitBtn.classList.add('btn-primary');
                submitBtn.innerHTML = '<?= smsIcon('paper-plane', ['class' => 'me-2']) ?>Submit Progress Update';
            }
        }
    }

    // Escape HTML for safe injection into innerHTML
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Update current progress and lock state when milestone dropdown changes
    milestoneSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            return;
        }
        const currentProgress = selectedOption.getAttribute('data-current-progress') || 0;
        const currentStatus   = selectedOption.getAttribute('data-current-status') || 'Not Started';
        const isPending       = selectedOption.getAttribute('data-pending') === '1';
        const pendingAt       = selectedOption.getAttribute('data-pending-at') || '';
        const milestoneName   = selectedOption.textContent.replace(/\s*\(Current:.*\)/, '').trim();

        currentProgressDisplay.textContent = parseFloat(currentProgress).toFixed(1) + '%';
        progressInput.value    = Math.ceil(parseFloat(currentProgress));
        progressDisplay.textContent = progressInput.value;
        statusSelect.value = 'Submitted for Review';

        updateMilestoneState(currentStatus, isPending, milestoneName, pendingAt);
        updateDocumentRequirement();
    });

    function selectedChapterNumber() {
        const selectedOption = milestoneSelect.options[milestoneSelect.selectedIndex];
        return parseInt(selectedOption ? (selectedOption.getAttribute('data-chapter-number') || '0') : '0', 10);
    }

    function updateDocumentRequirement() {
        const selectedOption = milestoneSelect.options[milestoneSelect.selectedIndex];
        const isPending = selectedOption ? selectedOption.getAttribute('data-pending') === '1' : false;
        // If pending, document field is already disabled — no required marker needed.
        const required = !isPending && selectedChapterNumber() >= 1 && selectedChapterNumber() <= 5 && statusSelect.value === 'Submitted for Review';
        documentInput.required = required;
        if (documentRequiredMarker) {
            documentRequiredMarker.classList.toggle('d-none', !required);
        }
    }

    // ------------------------------------------------------------------ //
    // refreshMilestoneOptions — called after every poll and after submit.
    // Updates option data attributes AND the pendingMap in-place.
    // ------------------------------------------------------------------ //
    function refreshMilestoneOptions(milestones) {
        const selectedId = milestoneSelect.value;

        milestones.forEach(function (milestone) {
            const id = String(milestone.id || '');
            if (!id) { return; }

            const option = Array.from(milestoneSelect.options).find(function (c) {
                return c.value === id;
            });
            if (!option) { return; }

            const progress  = parseFloat(milestone.progress_percentage || 0);
            const status    = milestone.status || 'Not Started';
            const isPending = milestone.has_pending_submission ? '1' : '0';
            const pendingAt = milestone.pending_submitted_at || '';

            option.setAttribute('data-current-progress', progress);
            option.setAttribute('data-current-status', status);
            option.setAttribute('data-pending', isPending);
            option.setAttribute('data-pending-at', pendingAt);
            option.textContent = milestone.milestone_name + ' (Current: ' + progress.toFixed(1) + '%)';

            // Keep the pendingMap in sync so any further logic can reference it.
            pendingMap[id] = isPending === '1';
        });

        // Re-evaluate the selected milestone's state.
        if (selectedId) {
            milestoneSelect.dispatchEvent(new Event('change'));
        }
    }

    async function pollCurrentMilestones() {
        try {
            const response = await fetch('<?= BASE_URL ?>/modules/crad/api/research-progress.php?action=get_milestones', {
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const data = await response.json();
            if (data.success && Array.isArray(data.milestones)) {
                refreshMilestoneOptions(data.milestones);
            }
        } catch (error) {
            console.error('Milestone refresh failed:', error);
        }
    }

    statusSelect.addEventListener('change', updateDocumentRequirement);

    // Initialize displays on page load
    if (milestoneSelect.value) {
        milestoneSelect.dispatchEvent(new Event('change'));
    } else {
        progressDisplay.textContent = progressInput.value;
    }

    setInterval(pollCurrentMilestones, 15000);

    // ------------------------------------------------------------------ //
    // Form submission
    // ------------------------------------------------------------------ //
    const form      = document.getElementById('progressUpdateForm');
    const formAlert = document.getElementById('progress_form_alert');

    function showFormAlert(type, message) {
        formAlert.className = `alert alert-${type}`;
        formAlert.innerHTML = message;
        formAlert.classList.remove('d-none');
        formAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideFormAlert() {
        formAlert.className = 'alert d-none';
        formAlert.textContent = '';
    }

    function restoreSubmitButton() {
        isSubmitting = false;
        // Re-evaluate current milestone state — honours pending/approved locks.
        if (milestoneSelect.value) {
            milestoneSelect.dispatchEvent(new Event('change'));
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<?= smsIcon('paper-plane', ['class' => 'me-2']) ?>Submit Progress Update';
        }
    }

    function setSubmittedState() {
        isSubmitting = false;
        formLocked   = true;
        form.querySelectorAll('input, select, textarea').forEach(function (field) {
            field.disabled = true;
        });
        submitBtn.disabled = true;
        submitBtn.classList.remove('btn-primary', 'btn-secondary');
        submitBtn.classList.add('btn-success');
        submitBtn.innerHTML = '<?= smsIcon('check-circle', ['class' => 'me-2']) ?>Done Sent';

        const submittedState = document.getElementById('submitted_state');
        if (submittedState) {
            submittedState.classList.remove('d-none');
            submittedState.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // Show the pending-review lock state — used both on page load (via change event)
    // and when the API returns 409 with is_pending_review.
    function setPendingReviewState(milestoneName, pendingAt) {
        formLocked = true;
        form.querySelectorAll('input, select, textarea').forEach(function (field) {
            field.disabled = true;
        });
        submitBtn.disabled = true;
        submitBtn.classList.remove('btn-primary', 'btn-success');
        submitBtn.classList.add('btn-secondary');
        submitBtn.innerHTML = '<?= smsIcon('clock', ['class' => 'me-2']) ?>Waiting for Adviser Review';

        if (pendingReviewState) {
            pendingReviewState.classList.remove('d-none');
            pendingReviewState.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        if (pendingReviewLabel && milestoneName) {
            let labelHtml = 'Your <strong>' + escapeHtml(milestoneName) + '</strong> progress update is currently waiting for Adviser review.';
            if (pendingAt) {
                try {
                    const d = new Date(pendingAt.replace(' ', 'T'));
                    if (!isNaN(d.getTime())) {
                        labelHtml += ' <span style="color:var(--sms-text-muted);font-size:0.8rem;">Submitted ' + d.toLocaleString() + '</span>';
                    }
                } catch (_) { /* ignore */ }
            }
            pendingReviewLabel.innerHTML = labelHtml;
        }
    }

    async function readJsonResponse(response) {
        const text = await response.text();
        if (!text.trim()) { return {}; }
        try {
            return JSON.parse(text);
        } catch (error) {
            console.error('Unexpected submit response:', text);
            throw new Error(response.ok ? 'invalid_success_response' : 'invalid_error_response');
        }
    }

    const submitAnotherBtn = document.getElementById('submitAnotherBtn');
    if (submitAnotherBtn) {
        submitAnotherBtn.addEventListener('click', function () {
            window.location.reload();
        });
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        hideFormAlert();

        if (!submissionToken) {
            showFormAlert('warning', '<?= smsIcon('clock', ['class' => 'me-2']) ?>Please wait, initializing submission token...');
            return;
        }

        // DUPLICATE PREVENTION: disable immediately on first click.
        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

        const formData = new FormData(form);
        formData.set('action', 'submit_progress');
        formData.set('new_progress', progressInput.value);
        formData.set('milestone_status', statusSelect.value);

        try {
            const response = await fetch('<?= BASE_URL ?>/modules/crad/api/research-progress.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            const result = await readJsonResponse(response);

            if (response.status === 409) {
                if (result.is_pending_review) {
                    // This milestone already has an active pending submission.
                    // Show the waiting-state banner — do NOT treat this as an error.
                    // Mark the option as pending so the change-handler stays in sync.
                    const currentOption = milestoneSelect.options[milestoneSelect.selectedIndex];
                    if (currentOption) {
                        currentOption.setAttribute('data-pending', '1');
                        currentOption.setAttribute('data-pending-at', result.submitted_at || '');
                        pendingMap[currentOption.value] = true;
                    }
                    isSubmitting = false;
                    setPendingReviewState(result.milestone_name || '', result.submitted_at || '');
                } else {
                    // Server rejected the submission (duplicate token, approved
                    // milestone, etc.). Surface the server's actual reason instead
                    // of assuming every 409 is a duplicate.
                    const msg    = result.message || 'Duplicate submission detected. This update was already submitted.';
                    const isDup  = result.is_duplicate || /duplicate/i.test(msg);
                    showFormAlert(isDup ? 'warning' : 'danger', '<?= smsIcon('copy', ['class' => 'me-2']) ?>' + escapeHtml(msg));
                    restoreSubmitButton();
                }
                return;
            }

            if (!response.ok) {
                showFormAlert('danger', '<?= smsIcon('exclamation-triangle', ['class' => 'me-2']) ?>' + (result.message || 'Failed to submit progress update. Please try again.'));
                restoreSubmitButton();
                return;
            }

            if (result.success) {
                submissionToken = null;
                showFormAlert('success', '<?= smsIcon('check-circle', ['class' => 'me-2']) ?><strong>Done Sent.</strong> Your adviser can now see this progress update in Submitted Updates.');
                setSubmittedState();

                // Refresh option data from the fresh milestones the server returned.
                if (Array.isArray(result.milestones)) {
                    refreshMilestoneOptions(result.milestones);
                }

                // Navigate to the next milestone (or clean page) after 1.8 s.
                const nextId = result.next_milestone_id || null;
                const redirectUrl = nextId
                    ? '<?= BASE_URL ?>/modules/student-portal/pages/progress-updates.php?milestone_id=' + encodeURIComponent(nextId)
                    : '<?= BASE_URL ?>/modules/student-portal/pages/progress-updates.php';

                setTimeout(function () {
                    window.location.href = redirectUrl;
                }, 1800);
            } else {
                showFormAlert('danger', '<?= smsIcon('exclamation-triangle', ['class' => 'me-2']) ?>' + (result.message || 'Failed to submit progress update. Please try again.'));
                restoreSubmitButton();
            }
        } catch (error) {
            console.error('Submission error:', error);
            const message = error.message === 'invalid_success_response'
                ? '<?= smsIcon('check-circle', ['class' => 'me-2']) ?><strong>Done Sent.</strong> The update was submitted, but the confirmation response could not be read. Redirecting...'
                : '<?= smsIcon('wifi', ['class' => 'me-2']) ?>Network error. Please check your connection and try again.';
            showFormAlert(error.message === 'invalid_success_response' ? 'success' : 'danger', message);
            if (error.message === 'invalid_success_response') {
                setSubmittedState();
                setTimeout(function () {
                    window.location.href = '<?= BASE_URL ?>/modules/student-portal/pages/progress-updates.php';
                }, 1800);
                return;
            }
            restoreSubmitButton();
        }
    });
});
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
