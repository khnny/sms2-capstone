<?php
/**
 * Research Implementation & Progress Monitoring
 * API Endpoint for Adviser Operations
 * 
 * Handles:
 * - Get assigned research groups
 * - View group progress and milestones
 * - Submit feedback/comments
 * - Request revisions
 * - Approve progress
 * 
 * DUPLICATE PREVENTION: Token-based + batch_key validation
 */

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/research-progress-helpers.php';

// Require authentication
requireAuth();

// Only advisers can access
$currentRole = getCurrentUserRoleKey();
if ($currentRole !== 'adviser') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Advisers only.']);
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

$adviserUserId = (int) ($_SESSION['user_id'] ?? 0);
$adviserEmail = rpCurrentUserEmail();
$adviserName = trim((string) ($_SESSION['user_name'] ?? ''));

if ($adviserUserId <= 0 && $adviserEmail === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Adviser identification required']);
    exit;
}

// Route to appropriate handler
switch ($action) {
    case 'get_assigned_groups':
        handleGetAssignedGroups($crad, $adviserUserId, $adviserEmail);
        break;
        
    case 'get_group_progress':
        handleGetGroupProgress($crad, $adviserUserId, $adviserEmail);
        break;
        
    case 'get_progress_updates':
        handleGetProgressUpdates($crad, $adviserUserId, $adviserEmail);
        break;

    case 'get_revision_monitoring':
        handleGetRevisionMonitoring($crad, $adviserUserId, $adviserEmail);
        break;

    case 'get_revision_detail':
        handleGetRevisionDetail($crad, $adviserUserId, $adviserEmail);
        break;
        
    case 'submit_feedback':
    case 'comment':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        handleSubmitFeedback($crad, $adviserUserId, $adviserEmail, $adviserName);
        break;
        
    case 'request_revision':
    case 'revision':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        handleRequestRevision($crad, $adviserUserId, $adviserEmail, $adviserName);
        break;
        
    case 'approve_progress':
    case 'approve':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }
        handleApproveProgress($crad, $adviserUserId, $adviserEmail, $adviserName);
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
 * Get all research groups assigned to this adviser
 */
function handleGetAssignedGroups(PDO $crad, int $adviserUserId, string $adviserEmail): void
{
    $groups = rpGetAssignedResearchGroupsForAdviser($crad, $adviserUserId, $adviserEmail);
    
    echo json_encode([
        'success' => true,
        'groups' => $groups,
        'count' => count($groups)
    ]);
}

/**
 * Get detailed progress for a specific research group
 */
function handleGetGroupProgress(PDO $crad, int $adviserUserId, string $adviserEmail): void
{
    $groupNumber = $_GET['group_number'] ?? '';
    
    if (empty($groupNumber)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Group number required']);
        return;
    }
    
    $group = rpGetAssignedResearchGroupForAdviser($crad, $adviserUserId, $adviserEmail, $groupNumber);
    
    if (!$group) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied to this research group']);
        return;
    }
    
    $groupId = (int) $group['id'];
    
    // Read only: polling must not create research plans or milestones.
    $plan = rpGetResearchPlan($crad, $groupId);
    
    // Get milestones with progress + per-milestone pending/update stats
    // (same source the Research Progress page renders, so polling stays in sync).
    $milestones = rpGetMilestonesWithUpdateStats($crad, !empty($plan['id']) ? (int) $plan['id'] : null, $groupId);
    if ($plan) {
        $plan = rpApplySyncedPlanProgress($plan, $milestones);
    }
    
    // Get recent progress updates
    $updatesStmt = $crad->prepare("
        SELECT 
            rpu.*,
            rm.milestone_name
        FROM research_progress_updates rpu
        LEFT JOIN research_milestones rm ON rm.id = rpu.milestone_id
        WHERE rpu.research_group_id = ?
        ORDER BY rpu.submitted_at DESC
        LIMIT 20
    ");
    $updatesStmt->execute([$groupId]);
    $updates = $updatesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get feedback history
    $feedback = [];
    if (!empty($plan['id'])) {
        $feedbackStmt = $crad->prepare("
            SELECT 
                rpf.*,
                rm.milestone_name,
                rpu.update_title
            FROM research_progress_feedback rpf
            INNER JOIN research_progress_updates rpu ON rpu.id = rpf.progress_update_id
            LEFT JOIN research_milestones rm ON rm.id = rpf.milestone_id
            WHERE rpf.research_plan_id = ?
            ORDER BY rpf.created_at DESC
            LIMIT 20
        ");
        $feedbackStmt->execute([(int) $plan['id']]);
        $feedback = $feedbackStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'group' => $group,
        'plan' => $plan,
        'milestones' => $milestones,
        'updates' => $updates,
        'feedback' => $feedback
    ]);
}

/**
 * Get pending/recent progress updates for review
 */
function handleGetProgressUpdates(PDO $crad, int $adviserUserId, string $adviserEmail): void
{
    rpEnsureProgressAttachmentSchema($crad);

    $assignmentMatch = rpAdviserAssignmentMatchSql('raa2', 'rg');
    $identitySql = rpAdviserIdentitySql('raa2');
    $statusSql = rpActiveAdviserAssignmentStatusSql('raa2');

    $stmt = $crad->prepare("
        SELECT 
            rpu.*,
            rm.milestone_name,
            rg.group_number,
            rg.group_name,
            rg.research_title,
            rpa.id AS attachment_id,
            rpa.file_name AS attachment_name,
            (SELECT COUNT(*) FROM research_progress_feedback WHERE progress_update_id = rpu.id) as feedback_count
        FROM research_progress_updates rpu
        INNER JOIN research_groups rg ON rg.id = rpu.research_group_id
        INNER JOIN research_adviser_assignments raa ON raa.id = (
            SELECT raa2.id
            FROM research_adviser_assignments raa2
            WHERE {$assignmentMatch}
              AND {$identitySql}
              AND {$statusSql}
            ORDER BY (raa2.assignment_status = 'Confirmed') DESC,
                     (raa2.assignment_status = 'Assigned') DESC,
                     raa2.updated_at DESC,
                     raa2.id DESC
            LIMIT 1
        )
        LEFT JOIN research_milestones rm ON rm.id = rpu.milestone_id
        LEFT JOIN research_progress_attachments rpa ON rpa.id = (
            SELECT rpa2.id
            FROM research_progress_attachments rpa2
            WHERE rpa2.progress_update_id = rpu.id
            ORDER BY rpa2.id DESC
            LIMIT 1
        )
        ORDER BY rpu.submitted_at DESC
        LIMIT 50
    ");
    $stmt->execute(rpAdviserIdentityParams($adviserUserId, $adviserEmail));
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'updates' => $updates
    ]);
}

/**
 * Submit feedback/comment on a progress update
 */
function handleSubmitFeedback(PDO $crad, int $adviserUserId, string $adviserEmail, string $adviserName): void
{
    $input = $GLOBALS['rpJsonInput'] ?: $_POST;
    
    // Validate required fields
    $updateId = (int) ($input['progress_update_id'] ?? $input['update_id'] ?? 0);

    if ($updateId <= 0 || empty($input['feedback_text'])) {
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
    if (rpIsTokenRecentlyUsed($crad, 'research_progress_feedback', $submissionToken, 5)) {
        http_response_code(409);
        echo json_encode([
            'success' => false, 
            'message' => 'Duplicate submission detected. Please wait before submitting again.',
            'is_duplicate' => true
        ]);
        return;
    }
    
    // Get progress update and verify adviser access through the official assignment.
    $update = rpGetProgressUpdateForAdviser($crad, $updateId, $adviserUserId, $adviserEmail);
    
    if (!$update) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied or update not found']);
        return;
    }
    
    try {
        $crad->beginTransaction();
        
        // Insert feedback
        $insertStmt = $crad->prepare("
            INSERT INTO research_progress_feedback (
                progress_update_id, research_plan_id, milestone_id,
                adviser_user_id, adviser_name, feedback_type, feedback_text,
                submission_token
            ) VALUES (?, ?, ?, ?, ?, 'Comment', ?, ?)
        ");
        
        $insertStmt->execute([
            $updateId,
            $update['research_plan_id'],
            $update['milestone_id'] ?? null,
            $adviserUserId,
            $adviserName,
            $input['feedback_text'],
            $submissionToken
        ]);
        
        $feedbackId = (int) $crad->lastInsertId();
        
        // Log activity
        rpLogActivity($crad, [
            'research_plan_id' => $update['research_plan_id'],
            'research_group_id' => $update['research_group_id'],
            'user_id' => $adviserUserId,
            'user_name' => $adviserName,
            'user_role' => 'adviser',
            'action' => 'feedback_submitted',
            'entity_type' => 'feedback',
            'entity_id' => $feedbackId,
            'detail' => 'Adviser provided feedback'
        ]);
        
        // Create notification for student (DUPLICATE PREVENTION via batch_key)
        rpCreateNotification($crad, [
            'recipient_user_id' => $update['submitted_by_user_id'] ?? null,
            'recipient_email' => '',
            'recipient_role' => 'student',
            'batch_key' => 'feedback:' . $feedbackId,
            'notification_type' => 'adviser_feedback',
            'title' => 'New Adviser Feedback',
            'body' => 'Your adviser commented on your progress update',
            'related_entity_type' => 'feedback',
            'related_entity_id' => $feedbackId,
            'action_url' => BASE_URL . '/modules/student-portal/pages/progress-updates.php'
        ]);
        
        $crad->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'feedback_id' => $feedbackId
        ]);
        
    } catch (Throwable $e) {
        $crad->rollBack();
        error_log('Feedback submission failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to submit feedback']);
    }
}

/**
 * Request revision for a progress update
 */
function handleRequestRevision(PDO $crad, int $adviserUserId, string $adviserEmail, string $adviserName): void
{
    $input = $GLOBALS['rpJsonInput'] ?: $_POST;
    
    $updateId = (int) ($input['progress_update_id'] ?? $input['update_id'] ?? 0);

    if ($updateId <= 0 || empty($input['feedback_text'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }
    
    // Token validation
    $submissionToken = $input['submission_token'] ?? '';
    if (empty($submissionToken) || rpIsTokenRecentlyUsed($crad, 'research_progress_feedback', $submissionToken, 5)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Duplicate or invalid submission']);
        return;
    }
    
    // Verify access through the official adviser assignment.
    $update = rpGetProgressUpdateForAdviser($crad, $updateId, $adviserUserId, $adviserEmail);
    
    if (!$update) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    try {
        $crad->beginTransaction();
        
        // Insert revision request feedback
        $insertStmt = $crad->prepare("
            INSERT INTO research_progress_feedback (
                progress_update_id, research_plan_id, milestone_id,
                adviser_user_id, adviser_name, feedback_type, feedback_text,
                new_milestone_status, submission_token
            ) VALUES (?, ?, ?, ?, ?, 'Revision Request', ?, 'Revision Requested', ?)
        ");
        
        $insertStmt->execute([
            $updateId,
            $update['research_plan_id'],
            $update['milestone_id'] ?? null,
            $adviserUserId,
            $adviserName,
            $input['feedback_text'],
            $submissionToken
        ]);
        
        $feedbackId = (int) $crad->lastInsertId();

        $crad->prepare("UPDATE research_progress_updates SET milestone_status = 'Revision Requested', updated_at = NOW() WHERE id = ?")
            ->execute([$updateId]);
        
        if (!empty($update['milestone_id'])) {
            $updateMilestone = $crad->prepare("
                UPDATE research_milestones 
                SET status = 'Revision Requested',
                    adviser_remarks = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateMilestone->execute([$input['feedback_text'], $update['milestone_id']]);
            rpRecalculateOverallProgress($crad, (int) $update['research_plan_id']);
        }
        
        // Log and notify
        rpLogActivity($crad, [
            'research_plan_id' => $update['research_plan_id'],
            'research_group_id' => $update['research_group_id'],
            'user_id' => $adviserUserId,
            'user_name' => $adviserName,
            'user_role' => 'adviser',
            'action' => 'revision_requested',
            'entity_type' => 'feedback',
            'entity_id' => $feedbackId,
            'detail' => 'Adviser requested revision'
        ]);
        
        rpCreateNotification($crad, [
            'recipient_user_id' => $update['submitted_by_user_id'] ?? null,
            'recipient_role' => 'student',
            'batch_key' => 'revision:' . $feedbackId,
            'notification_type' => 'revision_requested',
            'title' => 'Revision Requested',
            'body' => 'Your adviser requested revisions on your progress update',
            'related_entity_type' => 'feedback',
            'related_entity_id' => $feedbackId
        ]);
        
        $crad->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Revision request submitted successfully',
            'feedback_id' => $feedbackId
        ]);
        
    } catch (Throwable $e) {
        $crad->rollBack();
        error_log('Revision request failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to request revision']);
    }
}

/**
 * Approve progress update
 */
function handleApproveProgress(PDO $crad, int $adviserUserId, string $adviserEmail, string $adviserName): void
{
    $input = $GLOBALS['rpJsonInput'] ?: $_POST;
    
    $updateId = (int) ($input['progress_update_id'] ?? $input['update_id'] ?? 0);

    if ($updateId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing progress update ID']);
        return;
    }
    
    // Token validation
    $submissionToken = $input['submission_token'] ?? '';
    if (empty($submissionToken) || rpIsTokenRecentlyUsed($crad, 'research_progress_feedback', $submissionToken, 5)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Duplicate or invalid submission']);
        return;
    }
    
    // Verify access through the official adviser assignment.
    $update = rpGetProgressUpdateForAdviser($crad, $updateId, $adviserUserId, $adviserEmail);
    
    if (!$update) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }

    if ((string) ($update['milestone_status'] ?? '') !== 'Submitted for Review') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only submitted progress updates can be approved.']);
        return;
    }

    $latestStmt = $crad->prepare(
        "SELECT id
         FROM research_progress_updates
         WHERE research_group_id = ?
           AND milestone_id <=> ?
         ORDER BY submitted_at DESC, id DESC
         LIMIT 1"
    );
    $latestStmt->execute([(int) $update['research_group_id'], $update['milestone_id'] ?? null]);
    if ((int) ($latestStmt->fetchColumn() ?: 0) !== $updateId) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Only the latest submitted update for this milestone can be approved.']);
        return;
    }
    
    try {
        $crad->beginTransaction();
        
        // Insert approval feedback
        $insertStmt = $crad->prepare("
            INSERT INTO research_progress_feedback (
                progress_update_id, research_plan_id, milestone_id,
                adviser_user_id, adviser_name, feedback_type, feedback_text,
                new_milestone_status, submission_token
            ) VALUES (?, ?, ?, ?, ?, 'Progress Approved', ?, 'Approved', ?)
        ");
        
        $insertStmt->execute([
            $updateId,
            $update['research_plan_id'],
            $update['milestone_id'] ?? null,
            $adviserUserId,
            $adviserName,
            $input['remarks'] ?? $input['feedback_text'] ?? 'Progress approved',
            $submissionToken
        ]);
        
        $feedbackId = (int) $crad->lastInsertId();

        $crad->prepare("UPDATE research_progress_updates SET milestone_status = 'Approved', updated_at = NOW() WHERE id = ?")
            ->execute([$updateId]);
        
        if (!empty($update['milestone_id'])) {
            // Determine whether this is a Chapter 1-3 milestone.
            // For Chapter 1-3, the Adviser approval alone must NOT set the final
            // Approved / 100% state — that requires the official Pre-Oral Panel
            // result to be APPROVED first.  We still persist adviser_remarks so
            // both portals always show the adviser's feedback.
            $milestoneRowStmt = $crad->prepare(
                "SELECT milestone_order, milestone_name
                 FROM research_milestones
                 WHERE id = ? AND research_plan_id = ?
                 LIMIT 1"
            );
            $milestoneRowStmt->execute([$update['milestone_id'], $update['research_plan_id']]);
            $milestoneRow = $milestoneRowStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $chapterNum = $milestoneRow ? rpMilestoneChapterNumber($milestoneRow) : null;
            $isChapter123 = ($chapterNum !== null && in_array($chapterNum, [1, 2, 3], true));

            if ($isChapter123) {
                // Check whether the panel has already officially approved this group.
                $panelRecord  = rpGetGroupPanelApproval($crad, (int) $update['research_group_id']);
                $panelApproved = ($panelRecord !== null && ($panelRecord['final_result'] ?? '') === 'APPROVED');

                if ($panelApproved) {
                    // Panel already approved → write full final state now.
                    // rpSyncChapterMilestonesFromPanelApproval handles the complete
                    // Chapter 1-3 batch; we still update this individual row here for
                    // immediate consistency, then let the batch sync handle the others.
                    $updateMilestone = $crad->prepare("
                        UPDATE research_milestones
                        SET progress_percentage = 100,
                            status              = 'Approved',
                            completed_at        = COALESCE(completed_at, NOW()),
                            adviser_remarks     = ?,
                            updated_at          = NOW()
                        WHERE id = ?
                          AND research_plan_id  = ?
                    ");
                    $updateMilestone->execute([
                        $input['remarks'] ?? $input['feedback_text'] ?? 'Approved',
                        $update['milestone_id'],
                        $update['research_plan_id'],
                    ]);
                } else {
                    // No panel approval yet → save adviser_remarks but keep the
                    // milestone status as 'Submitted for Review' and leave the
                    // current progress_percentage unchanged.
                    // The milestone will be promoted to Approved/100% automatically
                    // by rpSyncChapterMilestonesFromPanelApproval() as soon as the
                    // panel submits all evaluations with result = APPROVED.
                    $updateMilestone = $crad->prepare("
                        UPDATE research_milestones
                        SET adviser_remarks = ?,
                            updated_at      = NOW()
                        WHERE id = ?
                          AND research_plan_id = ?
                    ");
                    $updateMilestone->execute([
                        $input['remarks'] ?? $input['feedback_text'] ?? 'Progress approved',
                        $update['milestone_id'],
                        $update['research_plan_id'],
                    ]);
                }
            } else {
                // Non-chapter milestone (Chapter 4, 5, System Dev, Testing, etc.) —
                // existing behaviour unchanged: Adviser approval immediately finalises it.
                $updateMilestone = $crad->prepare("
                    UPDATE research_milestones
                    SET progress_percentage = 100,
                        status              = 'Approved',
                        completed_at        = COALESCE(completed_at, NOW()),
                        adviser_remarks     = ?,
                        updated_at          = NOW()
                    WHERE id = ?
                      AND research_plan_id  = ?
                ");
                $updateMilestone->execute([
                    $input['remarks'] ?? $input['feedback_text'] ?? 'Approved',
                    $update['milestone_id'],
                    $update['research_plan_id'],
                ]);
            }

            rpRecalculateOverallProgress($crad, (int) $update['research_plan_id']);
            rpSetCurrentStageIfFirstSemesterComplete(
                $crad,
                (int) $update['research_plan_id'],
                (int) $update['research_group_id']
            );
        }
        
        rpLogActivity($crad, [
            'research_plan_id' => $update['research_plan_id'],
            'research_group_id' => $update['research_group_id'],
            'user_id' => $adviserUserId,
            'user_name' => $adviserName,
            'user_role' => 'adviser',
            'action' => 'progress_approved',
            'entity_type' => 'feedback',
            'entity_id' => $feedbackId,
            'detail' => 'Adviser approved progress'
        ]);
        
        rpCreateNotification($crad, [
            'recipient_user_id' => $update['submitted_by_user_id'] ?? null,
            'recipient_role' => 'student',
            'batch_key' => 'approval:' . $feedbackId,
            'notification_type' => 'progress_approved',
            'title' => 'Progress Approved',
            'body' => 'Your adviser approved your progress update',
            'related_entity_type' => 'feedback',
            'related_entity_id' => $feedbackId
        ]);
        
        $crad->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Progress approved successfully',
            'feedback_id' => $feedbackId
        ]);
        
    } catch (Throwable $e) {
        $crad->rollBack();
        error_log('Progress approval failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to approve progress']);
    }
}

/**
 * Get all research groups eligible for Revision Monitoring (3/3 AWR).
 */
function handleGetRevisionMonitoring(PDO $crad, int $adviserUserId, string $adviserEmail): void
{
    $groups = rpGetRevisionMonitoringGroups($crad, $adviserUserId, $adviserEmail);
    echo json_encode([
        'success'    => true,
        'groups'     => $groups,
        'count'      => count($groups),
        'counters'   => rpRevisionMonitoringCounts($groups),
        'generated_at' => date('Y-m-d H:i:s'),
    ]);
}

/**
 * Get the detailed revision case (3 panel results + remarks) for one Adviser-owned group.
 */
function handleGetRevisionDetail(PDO $crad, int $adviserUserId, string $adviserEmail): void
{
    $groupNumber = (string) ($_GET['group_number'] ?? $_GET['group'] ?? '');
    if ($groupNumber === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Group number required']);
        return;
    }

    $detail = rpGetRevisionDetail($crad, $adviserUserId, $adviserEmail, $groupNumber);
    if (!$detail) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied or this group is not under revision monitoring.']);
        return;
    }

    echo json_encode([
        'success'    => true,
        'detail'     => $detail,
    ]);
}
