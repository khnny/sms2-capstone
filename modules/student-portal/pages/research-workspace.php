<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';

requireAuth();
if (getCurrentUserRoleKey() !== 'student') {
    http_response_code(403);
    exit('Forbidden');
}

$pageTitle = 'Research Workspace';
$activeModule = 'student_portal';
$activePage = 'research-workspace';
$pageBannerIcon = 'fa-flask';
$pageBannerDescription = 'Follow your research journey, see the latest status, and continue to the next action.';

$breadcrumbs = [
    ['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
    ['label' => 'Research Workspace', 'url' => null],
];

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
$formatDate = static function ($value): string {
    $value = trim((string) $value);
    $timestamp = $value !== '' ? strtotime($value) : false;
    return $timestamp !== false ? date('F j, Y', $timestamp) : '';
};
$studentId = trim((string) ($_SESSION['student_id'] ?? ''));
$studentUserId = (int) ($_SESSION['user_id'] ?? 0);
$studentEmail = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
$researchGroup = null;
$workflowGroup = null;
$researchPlan = null;
$titleApproval = null;
$proposal = null;
$latestProgressUpdate = null;
$latestFeedback = null;
$chapterSubmissions = [];
$researchLookupAvailable = false;
$workflowLookupAvailable = false;
$registeredGroupDetails = null;
$registeredGroupDetailsAvailable = false;

try {
    require_once ROOT_PATH . '/modules/crad/config/config.php';
    require_once ROOT_PATH . '/modules/crad/includes/research-progress-helpers.php';
    $crad = cradDb();
    $researchGroup = rpGetRegisteredResearchGroup($crad, $studentId, $studentUserId);
    $researchLookupAvailable = true;

    $workflowGroupStmt = $crad->prepare(
        "SELECT g.*
         FROM `crad_research_groups` g
         WHERE (:sid <> '' AND LOWER(TRIM(g.leader_id)) = LOWER(:sid_match))
            OR (:email <> '' AND LOWER(TRIM(g.leader_email)) = :email_match)
            OR (:uid > 0 AND g.submitted_by_user_id = :uid_match)
            OR EXISTS (
                SELECT 1
                FROM `crad_research_group_members` m
                WHERE m.research_group_id = g.id
                  AND :member_sid <> ''
                  AND LOWER(TRIM(m.student_id)) = LOWER(:member_sid_match)
            )
         ORDER BY g.id DESC
         LIMIT 1"
    );
    $workflowGroupStmt->execute([
        ':sid' => $studentId,
        ':sid_match' => $studentId,
        ':email' => $studentEmail,
        ':email_match' => $studentEmail,
        ':uid' => $studentUserId,
        ':uid_match' => $studentUserId,
        ':member_sid' => $studentId,
        ':member_sid_match' => $studentId,
    ]);
    $workflowGroup = $workflowGroupStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $workflowLookupAvailable = true;

    $groupTitleApprovalId = (int) ($workflowGroup['title_approval_id'] ?? 0);
    $titleApprovalStmt = $crad->prepare(
        "SELECT t.*
         FROM `crad_title_approvals` t
         WHERE (:title_id > 0 AND t.id = :title_id_match)
            OR (:sid <> '' AND t.student_id = :sid_match)
            OR (:uid > 0 AND t.student_user_id = :uid_match)
         ORDER BY
            CASE WHEN :preferred_id > 0 AND t.id = :preferred_id_match THEN 0 ELSE 1 END,
            t.id DESC
         LIMIT 1"
    );
    $titleApprovalStmt->execute([
        ':title_id' => $groupTitleApprovalId,
        ':title_id_match' => $groupTitleApprovalId,
        ':sid' => $studentId,
        ':sid_match' => $studentId,
        ':uid' => $studentUserId,
        ':uid_match' => $studentUserId,
        ':preferred_id' => $groupTitleApprovalId,
        ':preferred_id_match' => $groupTitleApprovalId,
    ]);
    $titleApproval = $titleApprovalStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $proposalId = (int) ($workflowGroup['proposal_id'] ?? 0);
    if ($proposalId > 0) {
        $proposalStmt = $crad->prepare(
            'SELECT id, ref_code, proposal_number, status, progress, date_submitted, approved_at
             FROM `crad_research_proposals` WHERE id = ? LIMIT 1'
        );
        $proposalStmt->execute([$proposalId]);
        $proposal = $proposalStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if ($researchGroup) {
        $researchPlan = rpGetResearchPlan($crad, (int) ($researchGroup['id'] ?? 0));
        try {
            require_once ROOT_PATH . '/modules/crad/includes/chapter-evaluation-workflow.php';
            $registeredGroupDetails = chapterRegisteredStudentGroup($crad);
            $registeredGroupDetailsAvailable = is_array($registeredGroupDetails)
                && (int) ($registeredGroupDetails['id'] ?? 0) === (int) ($researchGroup['id'] ?? 0);
            if (!$registeredGroupDetailsAvailable) {
                $registeredGroupDetails = null;
            }
        } catch (Throwable $e) {
            error_log('Student research workspace registered-group details load failed: ' . $e->getMessage());
        }

        $researchGroupId = (int) ($researchGroup['id'] ?? 0);
        if ($researchGroupId > 0 && $researchPlan) {
            try {
                $progressStmt = $crad->prepare(
                    'SELECT update_title, milestone_status, submitted_at
                     FROM `crad_research_progress_updates`
                     WHERE research_group_id = ?
                     ORDER BY submitted_at DESC, id DESC
                     LIMIT 1'
                );
                $progressStmt->execute([$researchGroupId]);
                $latestProgressUpdate = $progressStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Throwable $e) {
                error_log('Student research workspace latest progress lookup failed: ' . $e->getMessage());
            }
            try {
                $feedbackStmt = $crad->prepare(
                    'SELECT adviser_name, feedback_text, feedback_type, created_at
                     FROM `crad_research_progress_feedback`
                     WHERE research_plan_id = ?
                     ORDER BY created_at DESC, id DESC
                     LIMIT 1'
                );
                $feedbackStmt->execute([(int) ($researchPlan['id'] ?? 0)]);
                $latestFeedback = $feedbackStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Throwable $e) {
                error_log('Student research workspace latest adviser feedback lookup failed: ' . $e->getMessage());
            }
        }

        if ($registeredGroupDetailsAvailable && $researchGroupId > 0) {
            try {
                $chapterSubmissions = chapterLatestSubmissionsForGroup($crad, $researchGroupId);
            } catch (Throwable $e) {
                error_log('Student research workspace chapter status lookup failed: ' . $e->getMessage());
            }
        }
    }
} catch (Throwable $e) {
    error_log('Student research workspace summary load failed: ' . $e->getMessage());
}

$groupSummary = $registeredGroupDetailsAvailable ? $registeredGroupDetails : $researchGroup;
$groupFields = [
    ['Group name', 'group_name'],
    ['Group number', 'group_number'],
    ['Research title', 'research_title'],
    ['College / department', 'college_dept'],
    ['Academic year', 'academic_year'],
    ['Recorded member count', 'member_count'],
    ['Adviser', 'adviser_name'],
    ['Coordinator', 'coordinator_name'],
    ['Group status', 'status'],
    ['Group workflow status', 'flow_status'],
];
$groupAdviser = trim((string) ($groupSummary['adviser_name'] ?? $groupSummary['adviser'] ?? ''));

$groupFlowStatus = strtolower(trim((string) ($workflowGroup['flow_status'] ?? '')));
$groupCompleted = in_array($groupFlowStatus, ['approved', 'ready_for_assignment'], true);
$groupState = 'current';
$groupStateLabel = $workflowGroup ? 'Draft' : 'Not submitted';
$groupDetail = $workflowLookupAvailable
    ? 'Submit your group details and member information to begin the research process.'
    : 'Group status is temporarily unavailable.';
$groupResponsible = 'Student';
$groupDate = $formatDate($workflowGroup['submitted_at'] ?? '');
if (!$workflowLookupAvailable) {
    $groupState = 'unknown';
    $groupStateLabel = 'Status unavailable';
} elseif ($workflowGroup) {
    if ($groupCompleted) {
        $groupState = 'complete';
        $groupStateLabel = 'Completed';
        $groupDetail = 'The Department Head approved this research group.';
        $groupResponsible = 'Completed';
    } elseif (in_array($groupFlowStatus, ['pending_dh_approval', 'pending_incomplete_approval'], true)) {
        $groupState = 'in_progress';
        $groupStateLabel = $groupFlowStatus === 'pending_incomplete_approval'
            ? 'Under review · Incomplete'
            : 'Under Department Head review';
        $groupDetail = trim((string) ($workflowGroup['incomplete_reason'] ?? '')) ?: 'Your submitted group is waiting for the Department Head’s decision.';
        $groupResponsible = 'Department Head';
    } elseif ($groupFlowStatus === 'rejected') {
        $groupState = 'rejected';
        $groupStateLabel = 'Revision required';
        $groupDetail = trim((string) ($workflowGroup['dh_remarks'] ?? '')) ?: 'Update your group details and submit them again.';
        $groupResponsible = 'Student';
    }
}

$titleStatus = strtolower(trim((string) ($titleApproval['status'] ?? '')));
$coordinatorStatus = strtolower(trim((string) ($titleApproval['coordinator_status'] ?? '')));
$cradStatus = strtolower(trim((string) ($titleApproval['crad_status'] ?? '')));
$titleFullyApproved = $titleApproval
    && $titleStatus === 'approved'
    && $coordinatorStatus === 'approved'
    && $cradStatus === 'approved'
    && trim((string) ($titleApproval['adviser_signature_data'] ?? '')) !== ''
    && trim((string) ($titleApproval['coordinator_signature_data'] ?? '')) !== ''
    && trim((string) ($titleApproval['crad_signature_data'] ?? '')) !== '';
$titleState = 'locked';
$titleStateLabel = 'Locked';
$titleDetail = 'Complete Department Head approval of your group before starting title approval.';
$titleResponsible = 'Waiting for Research Group';
$titleDate = '';
if ($groupCompleted) {
    if (!$titleApproval) {
        $titleState = 'current';
        $titleStateLabel = 'Ready to submit';
        $titleDetail = 'Submit your proposed title for adviser and coordinator review.';
        $titleResponsible = 'Student';
    } else {
        $titleDate = $formatDate($titleApproval['submission_date'] ?? $titleApproval['sent_at'] ?? '');
        $returnedStatus = in_array($titleStatus, ['returned', 'revision requested', 'needs revision'], true)
            || in_array($coordinatorStatus, ['returned', 'revision requested', 'needs revision'], true)
            || in_array($cradStatus, ['returned', 'revision requested', 'needs revision'], true);
        $rejectedStatus = in_array($titleStatus, ['rejected', 'declined'], true)
            || in_array($coordinatorStatus, ['rejected', 'declined'], true)
            || in_array($cradStatus, ['rejected', 'declined'], true);
        if ($returnedStatus) {
            $titleState = 'revision';
            $titleStateLabel = 'Revision requested';
            $titleDetail = trim((string) ($titleApproval['coordinator_remarks'] ?? ''))
                ?: (trim((string) ($titleApproval['adviser_remarks'] ?? '')) ?: 'Review the returned title and submit a revision.');
            $titleResponsible = 'Student';
        } elseif ($rejectedStatus) {
            $titleState = 'rejected';
            $titleStateLabel = 'Rejected';
            $titleDetail = trim((string) ($titleApproval['coordinator_remarks'] ?? ''))
                ?: (trim((string) ($titleApproval['adviser_remarks'] ?? '')) ?: 'Open the title approval page to review the decision.');
            $titleResponsible = 'Student';
        } elseif ($titleFullyApproved) {
            $titleState = 'complete';
            $titleStateLabel = 'Completed';
            $titleDetail = 'Your title has been approved by the adviser, coordinator, and CRAD.';
            $titleResponsible = 'Completed';
        } else {
            $titleState = 'in_progress';
            $titleStateLabel = 'Under review';
            if ($titleStatus !== 'approved' && $titleStatus !== 'reviewed') {
                $titleResponsible = trim((string) ($titleApproval['adviser_name'] ?? '')) ?: 'Research Adviser';
            } elseif ($coordinatorStatus !== 'approved') {
                $titleResponsible = trim((string) ($titleApproval['coordinator_name'] ?? '')) ?: 'Research Coordinator';
            } else {
                $titleResponsible = 'CRAD';
            }
            $titleDetail = 'Your title approval is waiting for ' . $titleResponsible . '.';
        }
    }
}

$proposalStatus = strtolower(trim((string) ($proposal['status'] ?? '')));
$proposalState = 'locked';
$proposalStateLabel = 'Locked';
$proposalDetail = 'Title approval must be complete before proposal review can proceed.';
$proposalResponsible = 'Waiting for Title Approval';
if ($titleFullyApproved) {
    if (!$proposal) {
        $proposalState = 'current';
        $proposalStateLabel = 'Pending submission';
        $proposalDetail = 'Your title is approved. Continue to the proposal page to check the next available action.';
        $proposalResponsible = 'Student';
    } elseif (in_array($proposalStatus, ['returned', 'needs revision', 'revision requested'], true)) {
        $proposalState = 'revision';
        $proposalStateLabel = 'Revision requested';
        $proposalDetail = 'A proposal revision is required before review can continue.';
        $proposalResponsible = 'Student';
    } elseif (in_array($proposalStatus, ['rejected', 'declined'], true)) {
        $proposalState = 'rejected';
        $proposalStateLabel = 'Rejected';
        $proposalDetail = 'Open the proposal page to review the decision and available next steps.';
        $proposalResponsible = 'Student';
    } elseif ($proposalStatus === 'approved') {
        $proposalState = 'complete';
        $proposalStateLabel = 'Completed';
        $proposalDetail = 'The proposal has been approved.';
        $proposalResponsible = 'Completed';
    } else {
        $proposalState = 'in_progress';
        $proposalStateLabel = $proposalStatus !== '' ? ucwords(str_replace('_', ' ', $proposalStatus)) : 'Under review';
        $proposalDetail = 'Your proposal is moving through the existing review process.';
        $proposalResponsible = $proposalStatus === 'panel assigned' ? 'Review Panel' : 'Research Coordinator';
    }
}

$planProgress = $researchPlan['overall_progress'] ?? null;
$hasPlanProgress = is_numeric($planProgress)
    && is_finite((float) $planProgress)
    && (float) $planProgress >= 0
    && (float) $planProgress <= 100;
$progressState = 'locked';
$progressStateLabel = 'Locked';
$progressDetail = 'Research development becomes available after your group is officially registered.';
$progressResponsible = 'Waiting for registration';
if ($researchGroup) {
    if (!$researchPlan) {
        $progressState = 'current';
        $progressStateLabel = 'Ready to begin';
        $progressDetail = 'Your group is registered. Open the research plan to view or begin development.';
        $progressResponsible = 'Student and Adviser';
    } elseif (strtolower((string) ($researchPlan['status'] ?? '')) === 'completed') {
        $progressState = 'complete';
        $progressStateLabel = 'Completed';
        $progressDetail = 'The recorded research plan is complete.';
        $progressResponsible = 'Completed';
    } else {
        $progressState = 'in_progress';
        $progressStateLabel = 'In progress';
        $progressValue = $hasPlanProgress ? (int) round((float) $planProgress) : null;
        $progressDetail = $progressValue !== null
            ? 'Recorded research plan progress: ' . $progressValue . '%.'
            : (trim((string) ($researchPlan['current_stage'] ?? '')) ?: 'Your research plan is active.');
        $progressResponsible = trim((string) ($researchPlan['adviser_name'] ?? '')) ?: ($groupAdviser ?: 'Student and Adviser');
    }
}

$feedbackState = 'locked';
$feedbackStateLabel = 'Locked';
$feedbackDetail = 'Adviser feedback will appear when a research plan is available.';
$feedbackResponsible = 'Waiting for registration';
$feedbackDate = '';
if ($researchPlan) {
    if ($latestFeedback) {
        $feedbackType = strtolower(trim((string) ($latestFeedback['feedback_type'] ?? 'comment')));
        $feedbackDate = $formatDate($latestFeedback['created_at'] ?? '');
        if (in_array($feedbackType, ['revision request', 'revision requested'], true)) {
            $feedbackState = 'revision';
            $feedbackStateLabel = 'Revision requested';
            $feedbackResponsible = 'Student';
        } else {
            $feedbackState = 'complete';
            $feedbackStateLabel = ucwords((string) ($latestFeedback['feedback_type'] ?? 'Feedback received'));
            $feedbackResponsible = trim((string) ($latestFeedback['adviser_name'] ?? '')) ?: ($groupAdviser ?: 'Research Adviser');
        }
        $feedbackDetail = trim((string) ($latestFeedback['feedback_text'] ?? '')) ?: 'New adviser feedback is available.';
    } elseif ($latestProgressUpdate) {
        $feedbackState = 'in_progress';
        $feedbackStateLabel = 'Waiting for Adviser';
        $feedbackResponsible = $groupAdviser ?: 'Research Adviser';
        $feedbackDetail = 'Your latest progress update is waiting for adviser feedback.';
    } else {
        $feedbackState = 'pending';
        $feedbackStateLabel = 'No feedback yet';
        $feedbackResponsible = $groupAdviser ?: 'Research Adviser';
        $feedbackDetail = 'Feedback will appear here after a progress update is reviewed.';
    }
}

$submissionState = 'locked';
$submissionStateLabel = 'Locked';
$submissionDetail = 'Chapter submission becomes available after official group registration.';
$submissionResponsible = 'Waiting for registration';
$submissionDate = '';
if ($registeredGroupDetailsAvailable) {
    if ($chapterSubmissions === []) {
        $submissionState = 'current';
        $submissionStateLabel = 'Ready when eligible';
        $submissionDetail = 'Your registered group can check chapter submission eligibility on the submission page.';
        $submissionResponsible = 'Student';
    } else {
        $submissionStatuses = array_map(
            static fn(array $row): string => strtolower(trim((string) ($row['status'] ?? ''))),
            $chapterSubmissions
        );
        $submissionDate = $formatDate($chapterSubmissions[0]['submitted_at'] ?? '');
        if (in_array('needs revision', $submissionStatuses, true)) {
            $submissionState = 'revision';
            $submissionStateLabel = 'Revision requested';
            $submissionDetail = 'At least one chapter submission needs revision.';
            $submissionResponsible = 'Student';
        } elseif (array_diff($submissionStatuses, ['accepted']) === []) {
            $submissionState = 'complete';
            $submissionStateLabel = 'All submitted chapters accepted';
            $submissionDetail = 'Every chapter currently submitted has been accepted.';
            $submissionResponsible = 'Completed';
        } else {
            $submissionState = 'in_progress';
            $submissionStateLabel = 'Under review';
            $submissionDetail = count($chapterSubmissions) . ' chapter submission(s) are on record.';
            $submissionResponsible = 'Research Adviser';
        }
    }
}

$workflowSteps = [
    [
        'title' => 'Research Group',
        'description' => $groupDetail,
        'state' => $groupState,
        'status' => $groupStateLabel,
        'responsible' => $groupResponsible,
        'date' => $groupDate,
        'href' => BASE_URL . '/modules/student-portal/pages/research-group.php',
        'button' => $groupState === 'complete' ? 'View Research Group' : 'Open Research Group',
        'icon' => 'users',
    ],
    [
        'title' => 'Title Approval',
        'description' => $titleDetail,
        'state' => $titleState,
        'status' => $titleStateLabel,
        'responsible' => $titleResponsible,
        'date' => $titleDate,
        'href' => BASE_URL . '/modules/student-portal/pages/research-proposal-submission.php',
        'button' => $titleState === 'revision' ? 'Revise Title' : ($titleApproval ? 'View Title Approval' : 'Start Title Approval'),
        'icon' => 'file-alt',
    ],
    [
        'title' => 'Proposal Review',
        'description' => $proposalDetail,
        'state' => $proposalState,
        'status' => $proposalStateLabel,
        'responsible' => $proposalResponsible,
        'date' => $formatDate($proposal['date_submitted'] ?? ''),
        'href' => BASE_URL . '/modules/student-portal/pages/research-proposal-submission.php',
        'button' => $proposalState === 'revision' ? 'Revise Proposal' : 'View Proposal',
        'icon' => 'clipboard-check',
    ],
    [
        'title' => 'Research Progress',
        'description' => $progressDetail,
        'state' => $progressState,
        'status' => $progressStateLabel,
        'responsible' => $progressResponsible,
        'date' => $formatDate($latestProgressUpdate['submitted_at'] ?? ''),
        'href' => BASE_URL . '/modules/student-portal/pages/my-research.php',
        'button' => 'View Progress',
        'icon' => 'chart-line',
    ],
    [
        'title' => 'Adviser Feedback',
        'description' => $feedbackDetail,
        'state' => $feedbackState,
        'status' => $feedbackStateLabel,
        'responsible' => $feedbackResponsible,
        'date' => $feedbackDate,
        'href' => BASE_URL . '/modules/student-portal/pages/adviser-feedback.php',
        'button' => 'View Feedback',
        'icon' => 'comments',
    ],
    [
        'title' => 'Chapter Submissions',
        'description' => $submissionDetail,
        'state' => $submissionState,
        'status' => $submissionStateLabel,
        'responsible' => $submissionResponsible,
        'date' => $submissionDate,
        'href' => BASE_URL . '/modules/student-portal/pages/submit-chapters.php',
        'button' => 'View Submissions',
        'icon' => 'file-upload',
    ],
];

$completedSteps = count(array_filter($workflowSteps, static fn(array $step): bool => $step['state'] === 'complete'));
$currentStepIndex = null;
foreach ($workflowSteps as $index => $step) {
    if (in_array($step['state'], ['current', 'in_progress', 'revision', 'rejected'], true)) {
        $currentStepIndex = $index;
        break;
    }
}
$currentStep = $currentStepIndex !== null ? $workflowSteps[$currentStepIndex] : null;
$workflowProgress = (int) round(($completedSteps / count($workflowSteps)) * 100);

$groupSummary = $registeredGroupDetailsAvailable ? $registeredGroupDetails : ($workflowGroup ?? $researchGroup);
$groupAdviser = trim((string) ($groupSummary['adviser_name'] ?? $groupSummary['adviser'] ?? $groupAdviser));
$researchTitle = trim((string) ($titleApproval['proposed_title'] ?? $proposal['research_title'] ?? $groupSummary['research_title'] ?? ''));
$groupNumber = trim((string) ($groupSummary['group_number'] ?? ''));
$researchBase = BASE_URL . '/modules/student-portal/pages/';
$actionSections = [
    [
        'id' => 'group-title',
        'title' => 'Group & title',
        'description' => 'Manage your group record and title approval.',
        'actions' => [
            ['Research Group', 'research-group.php', 'Update group members or check the Department Head decision.', 'users', 'Open Research Group'],
            ['Proposal / Title Approval', 'research-proposal-submission.php', 'Submit, review, or revise your title approval.', 'file-alt', 'View Title Approval'],
        ],
    ],
    [
        'id' => 'progress-feedback',
        'title' => 'Progress & feedback',
        'description' => 'Follow your plan, milestones, updates, and adviser comments.',
        'actions' => [
            ['My Research', 'my-research.php', 'View the registered group and research progress.', 'flask', 'View Research'],
            ['Research Plan', 'research-plan.php', 'Review the research plan and milestones.', 'project-diagram', 'View Research Plan'],
            ['Milestones', 'milestones.php', 'Review milestone details and linked updates.', 'tasks', 'View Milestones'],
            ['Progress Updates', 'progress-updates.php', 'Submit or review progress updates.', 'chart-line', 'View Progress'],
            ['Adviser Feedback', 'adviser-feedback.php', 'Review feedback on your research progress.', 'comments', 'View Feedback'],
        ],
    ],
    [
        'id' => 'submissions-history',
        'title' => 'Submissions & history',
        'description' => 'Check chapter submissions, review outcomes, and past versions.',
        'actions' => [
            ['Submit Chapters 1–3', 'submit-chapters.php', 'Submit eligible chapters; existing adviser approval gates remain in effect.', 'file-upload', 'Submit Chapters'],
            ['My Submissions', 'my-submissions.php', 'Review your chapter submissions and available actions.', 'folder-open', 'View Submissions'],
            ['Submission Status', 'submission-status.php', 'Check the current review status of submitted chapters.', 'chart-line', 'Check Status'],
            ['Submission History', 'submission-history.php', 'Review chapter submission and revision history.', 'history', 'View History'],
        ],
    ],
    [
        'id' => 'final-services',
        'title' => 'Final manuscript & clearance',
        'description' => 'Open these services when the existing workflow makes them available.',
        'actions' => [
            ['Final Manuscript', 'final-manuscript.php', 'Review final manuscript requirements and submission history.', 'file-alt', 'View Final Manuscript'],
            ['Research Clearance', 'research-clearance.php', 'Check your clearance requirements and available actions.', 'stamp', 'View Clearance'],
        ],
    ],
];

require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>

<main class="glass-dashboard sms-research-workspace" aria-labelledby="research-workspace-title">
    <div class="glass-board">
        <section class="sms-rw-hero glass-panel p-4 mb-4" aria-labelledby="research-workspace-title">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                <div>
                    <span class="sms-rw-eyebrow"><?= smsIcon('flask', ['aria-hidden' => 'true']) ?> Student research</span>
                    <h2 class="h3 mb-2" id="research-workspace-title">Research Workspace</h2>
                    <p class="text-muted mb-0">Everything about your research, organized in one continuous process.</p>
                </div>
                <a class="btn btn-outline-primary sms-rw-back" href="<?= $escape(BASE_URL . '/modules/student-portal/pages/dashboard.php') ?>">
                    <?= smsIcon('arrow-left', ['class' => 'me-2', 'aria-hidden' => 'true']) ?>Back to Student Portal
                </a>
            </div>
            <?php if ($researchTitle !== '' || $groupNumber !== '' || $groupAdviser !== ''): ?>
                <div class="sms-rw-summary mt-4">
                    <?php if ($researchTitle !== ''): ?>
                        <div class="sms-rw-summary-title">
                            <span>Research title</span>
                            <strong><?= $escape($researchTitle) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if ($groupNumber !== ''): ?>
                        <div><span>Research group</span><strong><?= $escape($groupNumber) ?></strong></div>
                    <?php endif; ?>
                    <?php if ($groupAdviser !== ''): ?>
                        <div><span>Adviser</span><strong><?= $escape($groupAdviser) ?></strong></div>
                    <?php endif; ?>
                    <?php if ($groupSummary && trim((string) ($groupSummary['coordinator_name'] ?? '')) !== ''): ?>
                        <div><span>Coordinator</span><strong><?= $escape($groupSummary['coordinator_name']) ?></strong></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!$researchLookupAvailable || !$workflowLookupAvailable): ?>
            <div class="alert alert-warning" role="status">
                <?= smsIcon('exclamation-triangle', ['class' => 'me-2', 'aria-hidden' => 'true']) ?>
                Some live status details could not be loaded. Existing workflow pages remain available and continue to enforce eligibility.
            </div>
        <?php endif; ?>

        <section class="glass-panel p-4 mb-4" id="workflow-overview" aria-labelledby="workflow-overview-title">
            <div class="sms-rw-section-head">
                <div>
                    <span class="sms-rw-eyebrow"><?= smsIcon('project-diagram', ['aria-hidden' => 'true']) ?> Process progress</span>
                    <h2 class="h4 mb-1" id="workflow-overview-title">Your research journey</h2>
                </div>
                <span class="sms-rw-progress-count"><?= $completedSteps ?> of <?= count($workflowSteps) ?> steps completed</span>
            </div>
            <div class="progress sms-rw-progress mb-4" role="progressbar" aria-label="Research workflow progress"
                 aria-valuenow="<?= $workflowProgress ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: <?= $workflowProgress ?>%"></div>
            </div>

            <?php if ($currentStep): ?>
                <article class="sms-rw-current mb-4" aria-label="Current workflow step">
                    <div class="sms-rw-current-icon"><?= smsIcon($currentStep['icon'], ['aria-hidden' => 'true']) ?></div>
                    <div class="sms-rw-current-copy">
                        <span class="sms-rw-eyebrow">Current process · Step <?= $currentStepIndex + 1 ?> of <?= count($workflowSteps) ?></span>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h3 class="h5 mb-0"><?= $escape($currentStep['title']) ?></h3>
                            <span class="sms-rw-status sms-rw-status-<?= $escape($currentStep['state']) ?>">
                                <?= $escape($currentStep['status']) ?>
                            </span>
                        </div>
                        <p class="mb-2"><?= $escape($currentStep['description']) ?></p>
                        <div class="sms-rw-current-meta">
                            <span><strong>Responsible:</strong> <?= $escape($currentStep['responsible']) ?></span>
                            <?php if ($currentStep['date'] !== ''): ?>
                                <span><strong>Updated:</strong> <?= $escape($currentStep['date']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a class="btn btn-primary sms-rw-action" href="<?= $escape($currentStep['href']) ?>">
                        <?= smsIcon('arrow-right', ['class' => 'me-2', 'aria-hidden' => 'true']) ?><?= $escape($currentStep['button']) ?>
                    </a>
                </article>
            <?php else: ?>
                <div class="sms-rw-current mb-4" role="status">
                    <div class="sms-rw-current-icon"><?= smsIcon('check-circle', ['aria-hidden' => 'true']) ?></div>
                    <div class="sms-rw-current-copy">
                        <span class="sms-rw-eyebrow">Research journey</span>
                        <h3 class="h5 mb-1">All available steps are complete</h3>
                        <p class="mb-0">Open any step below to review its current records.</p>
                    </div>
                </div>
            <?php endif; ?>

            <ol class="sms-rw-steps" aria-label="Research workflow steps">
                <?php foreach ($workflowSteps as $index => $step): ?>
                    <?php
                    $isCurrentStep = $currentStepIndex === $index;
                    $stepIcon = match ($step['state']) {
                        'complete' => 'check',
                        'revision' => 'sync-alt',
                        'rejected' => 'xmark',
                        'locked' => 'lock',
                        'in_progress' => 'clock',
                        'unknown' => 'question-circle',
                        default => (string) ($index + 1),
                    };
                    ?>
                    <li class="sms-rw-step sms-rw-step-<?= $escape($step['state']) ?><?= $isCurrentStep ? ' is-current' : '' ?>"
                        <?= $isCurrentStep ? 'aria-current="step"' : '' ?>>
                        <span class="sms-rw-step-marker" aria-hidden="true">
                            <?php if (in_array($step['state'], ['current', 'pending'], true)): ?>
                                <?= sprintf('%02d', $index + 1) ?>
                            <?php else: ?>
                                <?= smsIcon($stepIcon, ['aria-hidden' => 'true']) ?>
                            <?php endif; ?>
                        </span>
                        <div class="sms-rw-step-body">
                            <div class="sms-rw-step-heading">
                                <div>
                                    <span class="sms-rw-step-kicker">Step <?= $index + 1 ?></span>
                                    <h3><?= $escape($step['title']) ?></h3>
                                </div>
                                <span class="sms-rw-status sms-rw-status-<?= $escape($step['state']) ?>"><?= $escape($step['status']) ?></span>
                            </div>
                            <p><?= $escape($step['description']) ?></p>
                            <div class="sms-rw-step-meta">
                                <span><strong>Responsible:</strong> <?= $escape($step['responsible']) ?></span>
                                <?php if ($step['date'] !== ''): ?>
                                    <span><strong>Date:</strong> <?= $escape($step['date']) ?></span>
                                <?php endif; ?>
                            </div>
                            <a class="btn <?= $isCurrentStep ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm sms-rw-action"
                               href="<?= $escape($step['href']) ?>">
                                <?= smsIcon($step['icon'], ['class' => 'me-2', 'aria-hidden' => 'true']) ?><?= $escape($step['button']) ?>
                            </a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <?php if ($researchGroup): ?>
            <section class="glass-panel p-4 mb-4" id="group-details" aria-labelledby="group-details-title">
                <div class="sms-rw-section-head">
                    <div>
                        <span class="sms-rw-eyebrow"><?= smsIcon('users', ['aria-hidden' => 'true']) ?> Group record</span>
                        <h2 class="h5 mb-0" id="group-details-title">Research group details</h2>
                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="<?= $escape($researchBase . 'research-group.php') ?>">View Research Group</a>
                </div>
                <div class="row g-3 mt-1">
                    <?php foreach ($groupFields as [$label, $key]):
                        $value = $groupSummary[$key] ?? null;
                        if (!is_scalar($value) || trim((string) $value) === '') {
                            continue;
                        }
                    ?>
                        <div class="col-sm-6 col-xl-4">
                            <div class="sms-rw-detail">
                                <span><?= $escape($label) ?></span>
                                <strong><?= $escape($value) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($hasPlanProgress): ?>
                        <div class="col-sm-6 col-xl-4">
                            <div class="sms-rw-detail">
                                <span>Research plan progress</span>
                                <strong><?= (int) round((float) $planProgress) ?>%</strong>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($researchPlan && trim((string) ($researchPlan['current_stage'] ?? '')) !== ''): ?>
                        <div class="col-sm-6 col-xl-4">
                            <div class="sms-rw-detail">
                                <span>Current plan stage</span>
                                <strong><?= $escape($researchPlan['current_stage']) ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php foreach ($actionSections as $section): ?>
            <section class="glass-panel p-4 mb-4" id="<?= $escape($section['id']) ?>" aria-labelledby="<?= $escape($section['id']) ?>-title">
                <div class="sms-rw-section-head">
                    <div>
                        <span class="sms-rw-eyebrow">Research workspace</span>
                        <h2 class="h5 mb-1" id="<?= $escape($section['id']) ?>-title"><?= $escape($section['title']) ?></h2>
                        <p class="text-muted mb-0"><?= $escape($section['description']) ?></p>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <?php foreach ($section['actions'] as [$label, $file, $description, $icon, $buttonLabel]): ?>
                        <div class="col-md-6 col-xl-4">
                            <article class="sms-rw-action-card h-100">
                                <div class="sms-rw-action-icon"><?= smsIcon($icon, ['aria-hidden' => 'true']) ?></div>
                                <div class="sms-rw-action-content">
                                    <h3><?= $escape($label) ?></h3>
                                    <p><?= $escape($description) ?></p>
                                    <a class="btn btn-outline-primary btn-sm" href="<?= $escape($researchBase . $file) ?>">
                                        <?= $escape($buttonLabel) ?><?= smsIcon('arrow-right', ['class' => 'ms-2', 'aria-hidden' => 'true']) ?>
                                    </a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
