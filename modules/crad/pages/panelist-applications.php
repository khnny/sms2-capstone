<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once __DIR__ . '/../includes/panelist-workflow.php';

requireAuth();
if (!smsRoleAllowedForModule(['crad_officer'], 'crad')) {
    http_response_code(403);
    exit('Forbidden');
}

$pdo = cradDb();
if (!$pdo instanceof PDO) {
    http_response_code(503);
    exit('CRAD database unavailable.');
}
cradEnsurePanelistWorkflowSchema($pdo);

if (isset($_GET['document'])) {
    $documentId = filter_var($_GET['document'], FILTER_VALIDATE_INT);
    if (!$documentId || $documentId < 1) {
        http_response_code(404);
        exit('Document not found.');
    }
    $stmt = $pdo->prepare(
        'SELECT d.original_name, d.mime_type, d.file_size, d.file_data, a.application_ref
         FROM `crad_panel_application_documents` d
         JOIN `crad_panelist_applications` a ON a.id = d.application_id
         WHERE d.id = ? LIMIT 1'
    );
    $stmt->execute([$documentId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$document) {
        http_response_code(404);
        exit('Document not found.');
    }
    header('X-Content-Type-Options: nosniff');
    header('Content-Type: ' . (string) $document['mime_type']);
    header('Content-Length: ' . (int) $document['file_size']);
    header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', basename((string) $document['original_name'])) . '"');
    echo $document['file_data'];
    exit;
}

$notice = '';
$error = '';
$selectedId = max(0, (int) ($_GET['id'] ?? 0));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['review_action'] ?? '') === 'update_status') {
    $selectedId = max(0, (int) ($_POST['application_id'] ?? 0));
    $nextStatus = trim((string) ($_POST['status'] ?? ''));
    $note = trim((string) ($_POST['note'] ?? ''));
    if (!csrfVerify()) {
        $error = 'Security check failed. Refresh the page and try again.';
    } elseif ($selectedId <= 0 || !in_array($nextStatus, ['Under Review', 'Approved', 'Rejected', 'Request Revision'], true)) {
        $error = 'Select a valid application and review outcome.';
    } elseif (($nextStatus === 'Request Revision' || $nextStatus === 'Rejected') && $note === '') {
        $error = 'Add a clear note for a revision request or rejection.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id, status FROM `crad_panelist_applications` WHERE id = ? FOR UPDATE');
            $stmt->execute([$selectedId]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$current || !in_array((string) $current['status'], ['Submitted', 'Under Review'], true)) {
                throw new RuntimeException('This application is no longer available for review.');
            }
            $stmt = $pdo->prepare(
                'UPDATE `crad_panelist_applications`
                 SET status = ?, reviewed_by = ?, reviewed_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([$nextStatus, (int) getCurrentUserId(), $selectedId]);
            if ($nextStatus === 'Approved') {
                cradSyncApprovedPanelistPool($pdo, $selectedId);
            } else {
                cradSyncApprovedPanelistPool($pdo, $selectedId);
            }
            cradPanelApplicationHistory(
                $pdo,
                $selectedId,
                (string) $current['status'],
                $nextStatus,
                (int) getCurrentUserId(),
                getCurrentUserName(),
                $note
            );
            $pdo->commit();
            logActivity('panel_application_review', 'Panelist application ' . $selectedId . ' changed to ' . $nextStatus, 'crad');
            $notice = 'Application status updated to ' . $nextStatus . '.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Panelist application review failed: ' . $e->getMessage());
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update the application.';
        }
    }
}

$selected = null;
if ($selectedId > 0) {
    $stmt = $pdo->prepare(
        'SELECT a.*, p.panel_status AS pool_status, p.approval_date
         FROM `crad_panelist_applications` a
         LEFT JOIN `crad_panelist_pool` p ON p.application_id = a.id
         WHERE a.id = ? LIMIT 1'
    );
    $stmt->execute([$selectedId]);
    $selected = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
$list = $pdo->query(
    "SELECT a.id, a.application_ref, a.applicant_name, a.applicant_email, a.status, a.submitted_at, a.updated_at,
            p.panel_status AS pool_status
     FROM `crad_panelist_applications` a
     LEFT JOIN `crad_panelist_pool` p ON p.application_id = a.id
     ORDER BY FIELD(a.status, 'Submitted', 'Under Review', 'Request Revision', 'Approved', 'Rejected', 'Draft'),
              a.updated_at DESC, a.id DESC"
)->fetchAll(PDO::FETCH_ASSOC) ?: [];
$history = [];
$docs = [];
$data = [];
if ($selected) {
    $data = json_decode((string) $selected['application_data'], true) ?: [];
    $stmt = $pdo->prepare('SELECT id, document_type, original_name, file_size, uploaded_at FROM `crad_panel_application_documents` WHERE application_id = ? ORDER BY id');
    $stmt->execute([$selectedId]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $stmt = $pdo->prepare('SELECT old_status, new_status, actor_name, note, created_at FROM `crad_panel_application_history` WHERE application_id = ? ORDER BY id DESC');
    $stmt->execute([$selectedId]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$pageTitle = 'Panelist Applications';
$activeModule = 'crad';
$activePage = 'panelist-applications';
$breadcrumbs = [['label' => 'CRAD', 'url' => BASE_URL . '/modules/crad/index.php'], ['label' => $pageTitle, 'url' => null]];
require_once ROOT_PATH . '/includes/layout-start.php';
renderBreadcrumbs($breadcrumbs);
?>
<main class="container-fluid py-3 panel-review-page">
    <header class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
        <div><span class="text-primary text-uppercase fw-bold small">CRAD Officer · Panelist workflow</span><h1 class="h3 mb-1">Panelist applications</h1><p class="text-muted mb-0">Review applicant qualifications and supporting records. Approval grants eligibility only; it does not create an account or assign a panel.</p></div>
        <a class="btn btn-outline-secondary" href="<?= e(BASE_URL . '/modules/crad/pages/panel-configuration.php') ?>">Open panel configuration</a>
    </header>
    <?php if ($notice !== ''): ?><div class="alert alert-success"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <div class="row g-3">
        <section class="col-xl-4">
            <div class="card border-0 shadow-sm h-100"><div class="card-header bg-white py-3"><strong>Applications</strong><span class="badge text-bg-light ms-2"><?= count($list) ?></span></div>
                <div class="list-group list-group-flush panel-application-list">
                    <?php foreach ($list as $item): ?>
                    <a class="list-group-item list-group-item-action <?= (int) $item['id'] === $selectedId ? 'active' : '' ?>" href="?id=<?= (int) $item['id'] ?>">
                        <div class="d-flex justify-content-between gap-2"><strong><?= e((string) $item['applicant_name']) ?></strong><span class="badge rounded-pill text-bg-light"><?= e((string) $item['status']) ?></span></div>
                        <small class="<?= (int) $item['id'] === $selectedId ? 'text-white-50' : 'text-muted' ?>"><?= e((string) $item['application_ref']) ?> · <?= e((string) $item['applicant_email']) ?></small>
                        <?php if (!empty($item['pool_status'])): ?><div class="small <?= (int) $item['id'] === $selectedId ? 'text-white-50' : 'text-muted' ?>">Pool: <?= e((string) $item['pool_status']) ?></div><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                    <?php if (!$list): ?><div class="p-4 text-center text-muted">No applications have been received.</div><?php endif; ?>
                </div>
            </div>
        </section>
        <section class="col-xl-8">
            <?php if ($selected): ?>
            <article class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                    <div><span class="small text-muted"><?= e((string) $selected['application_ref']) ?></span><h2 class="h5 mb-0"><?= e((string) $selected['applicant_name']) ?></h2></div>
                    <span class="badge rounded-pill text-bg-primary"><?= e((string) $selected['status']) ?></span>
                </div>
                <div class="card-body">
                    <?php foreach ([
                        'Personal / Professional Information' => ['applicant_name', 'applicant_email', 'applicant_phone', 'institution', 'college_department', 'position'],
                        'Research Expertise' => ['primary_specialization', 'secondary_specialization', 'research_areas', 'expertise_keywords', 'years_research_experience', 'teaching_professional_experience'],
                        'Panelist Qualifications' => ['highest_degree', 'professional_experience', 'research_experience', 'previous_panelist_experience', 'qualified_areas'],
                        'Availability & Preferences' => ['available_days', 'available_time_ranges', 'preferred_schedule', 'unavailable_periods', 'defense_preferences'],
                    ] as $section => $keys): ?>
                    <section class="mb-4"><h3 class="h6 text-primary border-bottom pb-2"><?= e($section) ?></h3><dl class="row mb-0">
                        <?php foreach ($keys as $key): if (trim((string) ($data[$key] ?? '')) === '') continue; ?>
                        <dt class="col-sm-4 text-muted fw-normal"><?= e(ucwords(str_replace('_', ' ', $key))) ?></dt><dd class="col-sm-8"><?= nl2br(e((string) $data[$key])) ?></dd>
                        <?php endforeach; ?>
                    </dl></section>
                    <?php endforeach; ?>
                    <section class="mb-4"><h3 class="h6 text-primary border-bottom pb-2">Required Documents</h3>
                        <?php if ($docs): ?><div class="list-group"><?php foreach ($docs as $doc): ?>
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="?document=<?= (int) $doc['id'] ?>">
                                <span><strong><?= e(ucwords(str_replace('_', ' ', (string) $doc['document_type']))) ?></strong><small class="d-block text-muted"><?= e((string) $doc['original_name']) ?> · <?= number_format((int) $doc['file_size'] / 1024) ?> KB</small></span><span class="btn btn-sm btn-outline-primary">Download</span>
                            </a>
                        <?php endforeach; ?></div><?php else: ?><p class="text-muted mb-0">No documents uploaded.</p><?php endif; ?>
                    </section>
                    <?php if (in_array((string) $selected['status'], ['Submitted', 'Under Review'], true)): ?>
                    <form method="post" class="border rounded-3 bg-light p-3">
                        <?= csrfField() ?><input type="hidden" name="review_action" value="update_status"><input type="hidden" name="application_id" value="<?= (int) $selected['id'] ?>">
                        <h3 class="h6">Review decision</h3>
                        <label class="form-label" for="reviewStatus">Status</label><select class="form-select mb-3" id="reviewStatus" name="status" required><option value="">Choose an outcome</option><option>Under Review</option><option>Approved</option><option>Request Revision</option><option>Rejected</option></select>
                        <label class="form-label" for="reviewNote">Note for applicant / review record</label><textarea class="form-control mb-3" id="reviewNote" name="note" rows="3" placeholder="Explain the decision or requested changes."></textarea>
                        <button class="btn btn-primary" type="submit">Save review decision</button>
                    </form>
                    <?php endif; ?>
                </div>
            </article>
            <article class="card border-0 shadow-sm"><div class="card-header bg-white"><strong>Application history</strong></div><ol class="list-group list-group-flush">
                <?php foreach ($history as $event): ?><li class="list-group-item"><div class="d-flex justify-content-between gap-2"><strong><?= e((string) $event['new_status']) ?></strong><small class="text-muted"><?= e((string) $event['created_at']) ?></small></div><div><?= e((string) $event['note']) ?></div><small class="text-muted"><?= e((string) $event['actor_name']) ?></small></li><?php endforeach; ?>
                <?php if (!$history): ?><li class="list-group-item text-muted">No review activity recorded.</li><?php endif; ?>
            </ol></article>
            <?php else: ?>
                <div class="card border-0 shadow-sm p-5 text-center"><h2 class="h5">Select an application</h2><p class="text-muted mb-0">View the applicant profile, expertise, availability, supporting documents, and review history.</p></div>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
