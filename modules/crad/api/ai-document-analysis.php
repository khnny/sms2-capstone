<?php
/**
 * Adviser AI document analysis for submitted research files.
 */
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../includes/authentication.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/research-progress-helpers.php';
require_once __DIR__ . '/../includes/ai-document-analysis.php';

requireAuth();

$currentRole = getCurrentUserRoleKey();
if ($currentRole !== 'adviser') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Advisers only.']);
    exit;
}

@set_time_limit(120);

$rawInput = file_get_contents('php://input');
$decodedInput = json_decode((string) $rawInput, true);
if (!is_array($decodedInput)) {
    $decodedInput = $_POST;
}

$action = (string) ($decodedInput['action'] ?? $_GET['action'] ?? 'analyze');
$adviserUserId = (int) ($_SESSION['user_id'] ?? 0);
$adviserEmail = rpCurrentUserEmail();
$adviserName = trim((string) ($_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? ''));

try {
    $crad = cradDb();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

rpEnsureProgressAttachmentSchema($crad);
rpEnsureAiAnalysisSchema($crad);

$updateId = (int) ($decodedInput['update_id'] ?? 0);
if ($updateId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Progress update is required.']);
    exit;
}

$update = rpGetProgressUpdateForAdviser($crad, $updateId, $adviserUserId, $adviserEmail);
if (!$update) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied to this submission.']);
    exit;
}

if ($action === 'latest') {
    $analysis = rpLatestAiAnalysisForUpdate($crad, $updateId);
    echo json_encode([
        'success' => true,
        'analysis' => $analysis,
    ]);
    exit;
}

$attachment = rpLatestAttachmentForUpdate($crad, $updateId);
if (!$attachment) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No attached research file was found for this submission.']);
    exit;
}

$path = rpResolveUploadPath((string) ($attachment['file_path'] ?? ''));
if ($path === null) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'The attached research file could not be read from storage.']);
    exit;
}

$milestoneStmt = $crad->prepare('SELECT milestone_name FROM research_milestones WHERE id = ? LIMIT 1');
$milestoneStmt->execute([(int) ($update['milestone_id'] ?? 0)]);
$milestoneName = (string) ($milestoneStmt->fetchColumn() ?: '');
$fileName = (string) ($attachment['file_name'] ?? basename($path));
$text = rpExtractDocumentText($path, $fileName, (string) ($attachment['file_type'] ?? ''));
$result = rpAnalyzeResearchDocument($text, $milestoneName, $fileName);
if (empty($result['ok'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => (string) ($result['message'] ?? 'AI analysis failed.')]);
    exit;
}

$analysisId = rpSaveAiAnalysis($crad, [
    'progress_update_id' => $updateId,
    'attachment_id' => (int) ($attachment['id'] ?? 0),
    'milestone_name' => $milestoneName,
    'verdict' => $result['verdict'] ?? 'needs_revision',
    'grammar_quality' => $result['grammar_quality'] ?? 'fair',
    'summary' => $result['summary'] ?? '',
    'notes' => $result['notes'] ?? [],
    'source' => $result['source'] ?? 'ai',
    'analyzed_by' => $adviserUserId,
    'analyzed_by_name' => $adviserName,
]);

$saved = rpLatestAiAnalysisForUpdate($crad, $updateId);
echo json_encode([
    'success' => true,
    'message' => 'AI analysis completed. Review the notes before you approve or request revision.',
    'analysis_id' => $analysisId,
    'analysis' => $saved,
    'revision_text' => $saved ? rpFormatAiNotesForRevision($saved + ['milestone_name' => $milestoneName]) : '',
]);
