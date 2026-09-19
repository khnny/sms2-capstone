<?php
/**
 * Research Implementation & Progress Monitoring
 * API Endpoint for Student (Researcher) Operations
 * 
 * Handles:
 * - Get research plan and milestones
 * - Submit progress updates
 * - Get progress history
 * - Get adviser feedback
 * 
 * DUPLICATE PREVENTION: Token-based + timestamp validation
 */

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../../../includes/uploads.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/research-progress-helpers.php';

// Require authentication
requireAuth();

// Only students with research groups can access
$currentRole = getCurrentUserRoleKey();
if ($currentRole !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Students only.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$rpJsonInput = [];
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $decodedInput = json_decode((string) $rawInput, true);
    if (is_array($decodedInput)) {
        $rpJsonInput = $decodedInput;
    }
}
$action = $_GET['action'] ?? $_POST['action'] ?? ($rpJsonInput['action'] ?? '');

try {
    $crad = cradDb();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get current student's research group
$studentId     = trim((string) ($_SESSION['student_id'] ?? ''));
$studentUserId = (int) ($_SESSION['user_id'] ?? 0);
$studentName   = trim((string) ($_SESSION['user_name'] ?? ''));

if (empty($studentId) && $studentUserId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Student identification required']);
    exit;
}

// Only allow access if the student's group is in the Capstone Group/Student Registry
$researchGroup = rpGetRegisteredResearchGroup($crad, $studentId, $studentUserId);

if (!$researchGroup) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Research Development is not available. Your group must be registered in the Capstone Group/Student Registry first.'
    ]);
    exit;
}

$groupId = (int) $researchGroup['id'];

// Route to appropriate handler
switch ($action) {
    case 'get_research_plan':
        handleGetResearchPlan($crad, $groupId, $researchGroup);
        break;
        
    case 'get_milestones':
        handleGetMilestones($crad, $groupId);
        break;
        
    case 'submit_progress':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        handleSubmitProgress($crad, $groupId, $researchGroup, $studentUserId, $studentName);
        break;
        
    case 'get_progress_history':
        handleGetProgressHistory($crad, $groupId);
        break;
        
    case 'get_adviser_feedback':
        handleGetAdviserFeedback($crad, $groupId);
        break;
        
    case 'generate_token':
        // Generate submission token for duplicate prevention
        echo json_encode([
            'success' => true,
            'token' => rpGenerateSubmissionToken()
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

/**
 * Get or create research plan with milestones
 */
function handleGetResearchPlan(PDO $crad, int $groupId, array $researchGroup): void
{
    // Get or create plan (idempotent)
    $plan = rpGetOrCreateResearchPlan($crad, $groupId);
    
    if (!$plan) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to get research plan']);
        return;
    }
    
    // Get milestones from Research Development progress only.
    $milestones = rpGetMilestonesForPlan($crad, (int) $plan['id'], $groupId);
    $plan = rpApplySyncedPlanProgress($plan, $milestones);
    
    // Get latest progress update
    $latestUpdateStmt = $crad->prepare("
        SELECT * FROM research_progress_updates 
        WHERE research_group_id = ?
        ORDER BY submitted_at DESC
        LIMIT 1
    ");
    $latestUpdateStmt->execute([$groupId]);
    $latestUpdate = $latestUpdateStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'plan' => $plan,
        'group' => [
            'group_number' => $researchGroup['group_number'],
            'group_name' => $researchGroup['group_name'],
            'research_title' => $researchGroup['research_title'],
            'adviser' => $researchGroup['adviser_name'] ?: $researchGroup['adviser']
        ],
        'milestones' => $milestones,
        'latest_update' => $latestUpdate ?: null
    ]);
}

/**
 * Get all milestones for the research plan
 */
function handleGetMilestones(PDO $crad, int $groupId): void
{
    // Get plan first. Read-only polling must not create a plan.
    $planStmt = $crad->prepare("SELECT id FROM research_plans WHERE research_group_id = ? LIMIT 1");
    $planStmt->execute([$groupId]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        echo json_encode(['success' => true, 'milestones' => []]);
        return;
    }
    
    $milestones = rpGetMilestonesWithPendingFlags($crad, (int) $plan['id'], $groupId);
    
    echo json_encode([
        'success' => true,
        'milestones' => $milestones
    ]);
}

/**
 * Submit progress update with duplicate prevention
 */
function handleSubmitProgress(PDO $crad, int $groupId, array $researchGroup, int $userId, string $userName): void
{
    // Get POST data. Multipart is used when a progress document is uploaded.
    $input = $GLOBALS['rpJsonInput'] ?: $_POST;
    
    // Validate required fields
    if (!isset($input['milestone_id']) || !isset($input['new_progress'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    // Validate submission token (DUPLICATE PREVENTION)
    $submissionToken = $input['submission_token'] ?? '';
    if (empty($submissionToken)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Submission token required']);
        return;
    }
    
    // Check for duplicate token usage
    if (rpIsTokenRecentlyUsed($crad, 'research_progress_updates', $submissionToken, 5)) {
        http_response_code(409);
        echo json_encode([
            'success' => false, 
            'message' => 'Duplicate submission detected. Please wait before submitting again.',
            'is_duplicate' => true
        ]);
        return;
    }
    
    // Get research plan
    $planStmt = $crad->prepare("SELECT * FROM research_plans WHERE research_group_id = ? LIMIT 1");
    $planStmt->execute([$groupId]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Research plan not found']);
        return;
    }
    
    // Get milestone to update
    $milestoneStmt = $crad->prepare("
        SELECT * FROM research_milestones 
        WHERE id = ? AND research_plan_id = ?
        LIMIT 1
    ");
    $milestoneStmt->execute([$input['milestone_id'], $plan['id']]);
    $milestone = $milestoneStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$milestone) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Milestone not found']);
        return;
    }

    if ((string) ($milestone['status'] ?? '') === 'Approved') {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This milestone is already approved and finalized at 100%.']);
        return;
    }

    // PENDING REVIEW GUARD — database-level check (not session-based).
    // Rejects a new submission if this milestone already has an active
    // "Submitted for Review" update that the adviser has not yet acted on.
    $pendingUpdate = rpHasPendingSubmission($crad, $groupId, (int) $input['milestone_id']);
    if ($pendingUpdate) {
        http_response_code(409);
        echo json_encode([
            'success'           => false,
            'is_pending_review' => true,
            'milestone_name'    => $milestone['milestone_name'],
            'submitted_at'      => $pendingUpdate['submitted_at'],
            'message'           => $milestone['milestone_name'] . ' already has a progress update awaiting Adviser review. Please wait for your adviser to respond before submitting another update.',
        ]);
        return;
    }

    $allowedStudentStatuses = ['Not Started', 'Submitted for Review'];
    $milestoneStatus = trim((string) ($input['milestone_status'] ?? 'Not Started'));
    if (!in_array($milestoneStatus, $allowedStudentStatuses, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid milestone status selected. Students may only choose Not Started or Submitted for Review.']);
        return;
    }

    $chapterNumber = rpMilestoneChapterNumber($milestone);
    $uploadedDocument = null;
    $uploadedPath = null;
    $requiresDocument = $chapterNumber !== null && $milestoneStatus === 'Submitted for Review';
    $document = is_array($_FILES['document'] ?? null) ? $_FILES['document'] : [];
    if ($requiresDocument || (isset($document['error']) && (int) $document['error'] !== UPLOAD_ERR_NO_FILE)) {
        $upload = smsSecureUpload($document, [
            'subdir' => 'research_progress/g' . $groupId . '/u' . max(0, $userId),
            'max_bytes' => 10 * 1024 * 1024,
            'allowed' => smsUploadAllowedDocuments(),
            'required' => $requiresDocument,
        ]);
        if (empty($upload['ok'])) {
            http_response_code(400);
            $chapterLabel = $chapterNumber ? ('Chapter ' . $chapterNumber) : 'milestone';
            $message = $requiresDocument
                ? 'Please upload the ' . $chapterLabel . ' document for Adviser review.'
                : ($upload['error'] ?: 'Upload failed.');
            echo json_encode(['success' => false, 'message' => $message]);
            return;
        }
        if (!empty($upload['path'])) {
            $uploadedDocument = $upload;
            $uploadedPath = (string) $upload['path'];
        }
    }

    // Prepare progress update data
    $progressData = [
        'research_plan_id' => $plan['id'],
        'research_group_id' => $groupId,
        'milestone_id' => $input['milestone_id'],
        'submitted_by_user_id' => $userId,
        'submitted_by_name' => $userName,
        'update_title' => $input['update_title'] ?? $milestone['milestone_name'] . ' Progress Update',
        'previous_progress' => (float) $milestone['progress_percentage'],
        'new_progress' => (float) $input['new_progress'],
        'milestone_status' => $milestoneStatus,
        'accomplishments' => $input['accomplishments'] ?? null,
        'problems_blockers' => $input['problems_blockers'] ?? null,
        'next_planned_activity' => $input['next_planned_activity'] ?? null,
        'attachment_path' => $uploadedDocument['path'] ?? null,
        'attachment_original_name' => $uploadedDocument['original_name'] ?? null,
        'uploaded_document' => $uploadedDocument,
        'submission_token' => $submissionToken
    ];
    
    // Submit progress update
    $result = rpSubmitProgressUpdate($crad, $progressData);
    
    if (!$result['success']) {
        if ($uploadedPath && is_file($uploadedPath)) {
            @unlink($uploadedPath);
        }
        http_response_code(400);
        echo json_encode($result);
        return;
    }
    
    // Create notification for adviser (with duplicate prevention)
    if (!empty($researchGroup['adviser_user_id'])) {
        rpCreateNotification($crad, [
            'recipient_user_id' => $researchGroup['adviser_user_id'],
            'recipient_email' => $researchGroup['adviser_email'] ?? '',
            'recipient_role' => 'adviser',
            'batch_key' => 'progress_update:' . $result['update_id'],
            'notification_type' => 'progress_update',
            'title' => 'New Progress Update',
            'body' => $researchGroup['group_number'] . ' submitted a progress update for ' . $milestone['milestone_name'],
            'related_entity_type' => 'progress_update',
            'related_entity_id' => $result['update_id'],
            'action_url' => BASE_URL . '/modules/faculty/pages/research-progress.php?group=' . $researchGroup['group_number']
        ]);
    }
    
    // Determine the next/current milestone using the actual milestone_order sequence.
    // Re-fetch fresh milestones (post-commit) so the order, statuses, and pending flags are current.
    $freshMilestones = rpGetMilestonesWithPendingFlags($crad, (int) $plan['id'], $groupId);

    $nextMilestoneId  = null;
    $submittedOrder   = (int) ($milestone['milestone_order'] ?? 0);

    // Walk the ordered list and find the first milestone whose order is greater
    // than the one just submitted. This respects the actual DB order without
    // hardcoding any chapter names or IDs.
    foreach ($freshMilestones as $fm) {
        if ((int) ($fm['milestone_order'] ?? 0) > $submittedOrder) {
            $nextMilestoneId = (int) $fm['id'];
            break;
        }
    }

    echo json_encode([
        'success'            => true,
        'message'            => 'Progress update submitted successfully',
        'update_id'          => $result['update_id'],
        'next_milestone_id'  => $nextMilestoneId,   // null when no further milestone exists
        'milestones'         => $freshMilestones,    // fresh list for client-side UI refresh
    ]);
}

/**
 * Get progress update history for the research group
 */
function handleGetProgressHistory(PDO $crad, int $groupId): void
{
    $stmt = $crad->prepare("
        SELECT 
            rpu.*,
            rm.milestone_name,
            rm.milestone_order
        FROM research_progress_updates rpu
        LEFT JOIN research_milestones rm ON rm.id = rpu.milestone_id
        WHERE rpu.research_group_id = ?
        ORDER BY rpu.submitted_at DESC
        LIMIT 50
    ");
    $stmt->execute([$groupId]);
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'updates' => $updates
    ]);
}

/**
 * Get adviser feedback for the research group
 */
function handleGetAdviserFeedback(PDO $crad, int $groupId): void
{
    $stmt = $crad->prepare("
        SELECT 
            rpf.*,
            rm.milestone_name,
            rpu.update_title,
            rpu.submitted_at as update_submitted_at
        FROM research_progress_feedback rpf
        INNER JOIN research_progress_updates rpu ON rpu.id = rpf.progress_update_id
        LEFT JOIN research_milestones rm ON rm.id = rpf.milestone_id
        WHERE rpu.research_group_id = ?
        ORDER BY rpf.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$groupId]);
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'feedback' => $feedback
    ]);
}
