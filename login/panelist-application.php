<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/includes/panelist-workflow.php';

$pdo = cradDb();
if (!$pdo instanceof PDO) {
    http_response_code(503);
    exit('Panelist applications are temporarily unavailable. Please try again later.');
}
cradEnsurePanelistWorkflowSchema($pdo);

$errors = [];
$notice = '';
$application = null;
$applicationData = [];
$documents = [];
$token = trim((string) ($_GET['resume'] ?? ''));
$validToken = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
if ($validToken) {
    $stmt = $pdo->prepare('SELECT * FROM `crad_panelist_applications` WHERE resume_token_hash = ? LIMIT 1');
    $stmt->execute([hash('sha256', $token)]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($application) {
        $applicationData = json_decode((string) $application['application_data'], true) ?: [];
        $docStmt = $pdo->prepare(
            'SELECT document_type, original_name, file_size, uploaded_at
             FROM `crad_panel_application_documents` WHERE application_id = ?'
        );
        $docStmt->execute([(int) $application['id']]);
        foreach (($docStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $doc) {
            $documents[(string) $doc['document_type']] = $doc;
        }
    } else {
        $errors[] = 'That private application link is invalid or has expired.';
    }
}

$fields = [
    'applicant_name', 'applicant_email', 'applicant_phone', 'institution', 'college_department', 'position',
    'primary_specialization', 'secondary_specialization', 'research_areas', 'expertise_keywords',
    'years_research_experience', 'teaching_professional_experience', 'highest_degree',
    'professional_experience', 'research_experience', 'previous_panelist_experience',
    'qualified_areas', 'available_days', 'available_time_ranges', 'preferred_schedule',
    'unavailable_periods', 'defense_preferences'
];
$form = [];
foreach ($fields as $field) {
    $form[$field] = (string) ($applicationData[$field] ?? '');
}
$form['defense_preferences'] = is_array($applicationData['defense_preferences'] ?? null)
    ? implode(', ', array_filter(array_map('trim', array_map('strval', $applicationData['defense_preferences']))))
    : (string) ($applicationData['defense_preferences'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify()) {
        $errors[] = 'Your security token expired. Refresh the page and try again.';
    }
    $postedToken = trim((string) ($_POST['resume_token'] ?? ''));
    if ($postedToken !== '' && preg_match('/^[a-f0-9]{64}$/', $postedToken) !== 1) {
        $errors[] = 'The application link is invalid.';
    }
    $application = null;
    if ($postedToken !== '') {
        $stmt = $pdo->prepare('SELECT * FROM `crad_panelist_applications` WHERE resume_token_hash = ? LIMIT 1');
        $stmt->execute([hash('sha256', $postedToken)]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$application) {
            $errors[] = 'This application could not be found.';
        } elseif (!in_array((string) $application['status'], ['Draft', 'Request Revision'], true)) {
            $errors[] = 'This application is no longer editable.';
        }
    }
    foreach ($fields as $field) {
        $value = $_POST[$field] ?? ($form[$field] ?? '');
        if (is_array($value)) {
            $form[$field] = implode(', ', array_filter(
                array_map(static fn($option): string => trim((string) $option), $value),
                static fn(string $option): bool => $option !== ''
            ));
        } else {
            $form[$field] = trim((string) $value);
        }
    }

    $action = (string) ($_POST['application_action'] ?? 'draft');
    if (!in_array($action, ['draft', 'submit'], true)) {
        $errors[] = 'Select a valid application action.';
    }
    if ($action === 'submit') {
        foreach ([
            'applicant_name' => 'Full name',
            'applicant_email' => 'Email address',
            'applicant_phone' => 'Contact number',
            'institution' => 'Institution',
            'college_department' => 'College or department',
            'position' => 'Position or academic rank',
            'primary_specialization' => 'Primary specialization',
            'research_areas' => 'Research areas or expertise',
            'years_research_experience' => 'Years of research experience',
            'highest_degree' => 'Highest degree or qualification',
            'qualified_areas' => 'Areas qualified to serve as a panelist',
        ] as $field => $label) {
            if ($form[$field] === '') {
                $errors[] = $label . ' is required.';
            }
        }
        foreach (['applicant_email'] as $field) {
            if ($form[$field] !== '' && filter_var($form[$field], FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = 'Enter a valid ' . str_replace('_', ' ', $field) . '.';
            }
        }
        if (!isset($_POST['applicant_consent'])) {
            $errors[] = 'Confirm that the information is accurate before submitting.';
        }
        foreach (['curriculum_vitae', 'degree_proof'] as $requiredDocument) {
            $wasUploaded = isset($_FILES['documents']['name'][$requiredDocument])
                && (int) ($_FILES['documents']['error'][$requiredDocument] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
            if (!$wasUploaded && !isset($documents[$requiredDocument])) {
                $errors[] = $requiredDocument === 'curriculum_vitae'
                    ? 'Upload your curriculum vitae.'
                    : 'Upload proof of your highest degree or qualification.';
            }
        }
    }

    $uploadedDocuments = [];
    if (!$errors && isset($_FILES['documents']) && is_array($_FILES['documents']['name'] ?? null)) {
        $allowedExtensions = ['pdf', 'docx', 'jpg', 'jpeg', 'png'];
        foreach ($_FILES['documents']['name'] as $type => $originalName) {
            $uploadError = (int) ($_FILES['documents']['error'][$type] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($uploadError !== UPLOAD_ERR_OK) {
                $errors[] = 'A document could not be uploaded. Please try again.';
                continue;
            }
            $size = (int) ($_FILES['documents']['size'][$type] ?? 0);
            $tmpName = (string) ($_FILES['documents']['tmp_name'][$type] ?? '');
            $extension = strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION));
            if ($size <= 0 || $size > 10 * 1024 * 1024 || !in_array($extension, $allowedExtensions, true) || !is_uploaded_file($tmpName)) {
                $errors[] = 'Documents must be PDF, DOCX, JPG, or PNG files no larger than 10 MB.';
                continue;
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: 'application/octet-stream';
            $mimeAllowed = in_array($mime, [
                'application/pdf', 'application/zip',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg', 'image/png',
            ], true);
            if (!$mimeAllowed) {
                $errors[] = 'A document type does not match its file contents.';
                continue;
            }
            $contents = file_get_contents($tmpName);
            if ($contents === false) {
                $errors[] = 'A document could not be read. Please select it again.';
                continue;
            }
            $uploadedDocuments[(string) $type] = [
                'name' => mb_substr(basename((string) $originalName), 0, 255),
                'mime' => $mime,
                'size' => $size,
                'data' => $contents,
            ];
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $token = $postedToken !== '' ? $postedToken : bin2hex(random_bytes(32));
            $dataJson = json_encode($form, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if ($application) {
                $stmt = $pdo->prepare(
                    'UPDATE `crad_panelist_applications`
                     SET applicant_name = ?, applicant_email = ?, application_data = ?
                     WHERE id = ?'
                );
                $stmt->execute([$form['applicant_name'], $form['applicant_email'], $dataJson, (int) $application['id']]);
                $applicationId = (int) $application['id'];
                $oldStatus = (string) $application['status'];
            } else {
                $reference = 'PA-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $stmt = $pdo->prepare(
                    'INSERT INTO `crad_panelist_applications`
                        (application_ref, resume_token_hash, applicant_name, applicant_email, status, application_data)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$reference, hash('sha256', $token), $form['applicant_name'], $form['applicant_email'], 'Draft', $dataJson]);
                $applicationId = (int) $pdo->lastInsertId();
                $oldStatus = null;
            }
            foreach ($uploadedDocuments as $type => $document) {
                $stmt = $pdo->prepare(
                    'INSERT INTO `crad_panel_application_documents`
                        (application_id, document_type, original_name, mime_type, file_size, file_data)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE original_name = VALUES(original_name), mime_type = VALUES(mime_type),
                        file_size = VALUES(file_size), file_data = VALUES(file_data), uploaded_at = CURRENT_TIMESTAMP'
                );
                $stmt->bindValue(1, $applicationId, PDO::PARAM_INT);
                $stmt->bindValue(2, $type);
                $stmt->bindValue(3, $document['name']);
                $stmt->bindValue(4, $document['mime']);
                $stmt->bindValue(5, $document['size'], PDO::PARAM_INT);
                $stmt->bindValue(6, $document['data'], PDO::PARAM_LOB);
                $stmt->execute();
            }
            if ($action === 'submit') {
                $stmt = $pdo->prepare(
                    "UPDATE `crad_panelist_applications`
                     SET status = 'Submitted', submitted_at = COALESCE(submitted_at, NOW())
                     WHERE id = ?"
                );
                $stmt->execute([$applicationId]);
                cradPanelApplicationHistory($pdo, $applicationId, $oldStatus, 'Submitted', null, $form['applicant_name'], 'Application submitted for review.');
                $pdo->commit();
                header('Location: ' . BASE_URL . '/login/panelist-application.php?resume=' . rawurlencode($token) . '&submitted=1');
                exit;
            }
            if ($oldStatus === null) {
                cradPanelApplicationHistory($pdo, $applicationId, null, 'Draft', null, $form['applicant_name'], 'Application draft created.');
            }
            $pdo->commit();
            header('Location: ' . BASE_URL . '/login/panelist-application.php?resume=' . rawurlencode($token) . '&saved=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Panelist application save failed: ' . $e->getMessage());
            $errors[] = 'The application could not be saved. Please try again.';
        }
    }
    $token = $postedToken;
}

if (isset($_GET['submitted'])) {
    $notice = 'Your application has been submitted. Keep this private link to track its status or respond to a revision request.';
} elseif (isset($_GET['saved'])) {
    $notice = 'Your draft is saved. Keep this private link to return to your application.';
}
$status = (string) ($application['status'] ?? 'Draft');
$readOnly = $application && !in_array($status, ['Draft', 'Request Revision'], true);
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Research Panelist Application | Bestlink College of the Philippines</title>
    <link rel="stylesheet" href="<?= e(BASE_URL . '/assets/css/panelist-application.css') ?>">
</head>
<body class="panel-application-page">
    <header class="panel-app-header">
        <a class="panel-app-brand" href="<?= e(BASE_URL . '/login/login.php') ?>">
            <span class="panel-app-brand-mark">BCP</span>
            <span><strong>Research &amp; Development</strong><small>Panelist application</small></span>
        </a>
        <a class="panel-app-login" href="<?= e(BASE_URL . '/login/login.php') ?>">Return to sign in</a>
    </header>
    <main class="panel-app-main">
        <section class="panel-app-intro">
            <span class="panel-app-eyebrow">Bestlink College of the Philippines</span>
            <h1>Apply to become a Research Panelist</h1>
            <p>Submit your professional background, research expertise, and availability for CRAD review. Approved applicants are added to the institutional panelist pool and considered for future defense panel assignments.</p>
            <div class="panel-app-trust"><span aria-hidden="true">✓</span> This application supports panelist qualification review only; it does not assign a research group or defense.</div>
        </section>
        <?php if ($notice !== ''): ?><div class="panel-app-alert success" role="status"><?= e($notice) ?></div><?php endif; ?>
        <?php foreach ($errors as $error): ?><div class="panel-app-alert error" role="alert"><?= e($error) ?></div><?php endforeach; ?>
        <?php if ($application): ?>
            <section class="panel-app-status-card">
                <div><span class="panel-app-eyebrow">Application reference</span><strong><?= e((string) $application['application_ref']) ?></strong></div>
                <span class="panel-app-status status-<?= e(strtolower(str_replace(' ', '-', $status))) ?>"><?= e($status) ?></span>
                <p>Application status is separate from panel assignment and defense scheduling. Save the private URL in your browser to check this application later.</p>
            </section>
        <?php endif; ?>

        <?php if (!$readOnly): ?>
        <form class="panel-app-form" id="panelApplicationForm" method="post" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="resume_token" value="<?= e($token) ?>">
            <div class="panel-app-progress" aria-label="Application progress">
                <div class="panel-app-progress-top"><span id="stepLabel">Step 1 of 6</span><span id="stepName">Personal Information</span></div>
                <div class="panel-app-progress-track"><span id="progressFill"></span></div>
                <ol class="panel-app-step-list">
                    <?php foreach (['Personal & Professional Information', 'Research Expertise', 'Panelist Qualifications', 'Availability & Preferences', 'Required Documents', 'Review & Submit'] as $index => $step): ?>
                    <li class="<?= $index === 0 ? 'is-active' : '' ?>" data-step-indicator="<?= $index ?>"><span><?= $index + 1 ?></span><b><?= e($step) ?></b></li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <section class="panel-app-card" data-step="0">
                <span class="panel-app-eyebrow">01 · Personal &amp; Professional Information</span>
                <h2>Applicant profile</h2>
                <p class="panel-app-help">Provide your professional and institutional details for CRAD review.</p>
                <div class="panel-app-grid">
                    <label>Full name <input name="applicant_name" required value="<?= e($form['applicant_name']) ?>"></label>
                    <label>Email address <input type="email" name="applicant_email" required value="<?= e($form['applicant_email']) ?>"></label>
                    <label>Contact number <input name="applicant_phone" required value="<?= e($form['applicant_phone']) ?>"></label>
                    <label>Institution <input name="institution" required value="<?= e($form['institution']) ?>"></label>
                    <label>College / department <input name="college_department" required value="<?= e($form['college_department']) ?>"></label>
                    <label>Position / academic rank <input name="position" required value="<?= e($form['position']) ?>"></label>
                </div>
            </section>
            <section class="panel-app-card" data-step="1" hidden>
                <span class="panel-app-eyebrow">02 · Research Expertise</span>
                <h2>Your specialization and expertise</h2>
                <p class="panel-app-help">This information helps CRAD assess your suitability for future defense panel roles.</p>
                <div class="panel-app-grid">
                    <label>Primary research specialization <input name="primary_specialization" required value="<?= e($form['primary_specialization']) ?>"></label>
                    <label>Secondary specialization <input name="secondary_specialization" value="<?= e($form['secondary_specialization']) ?>"></label>
                    <label class="wide">Research areas / expertise <textarea name="research_areas" rows="3" required><?= e($form['research_areas']) ?></textarea></label>
                    <label class="wide">Keywords or fields of expertise <textarea name="expertise_keywords" rows="3"><?= e($form['expertise_keywords']) ?></textarea></label>
                    <label>Years of research experience <input name="years_research_experience" required value="<?= e($form['years_research_experience']) ?>"></label>
                    <label class="wide">Teaching / professional experience <textarea name="teaching_professional_experience" rows="3"><?= e($form['teaching_professional_experience']) ?></textarea></label>
                </div>
            </section>
            <section class="panel-app-card" data-step="2" hidden>
                <span class="panel-app-eyebrow">03 · Panelist Qualifications</span>
                <h2>Professional and panelist qualifications</h2>
                <p class="panel-app-help">Describe your relevant credentials, research background, and experience in review or defense settings.</p>
                <div class="panel-app-grid">
                    <label>Highest degree / educational qualification <input name="highest_degree" required value="<?= e($form['highest_degree']) ?>"></label>
                    <label class="wide">Professional experience <textarea name="professional_experience" rows="3"><?= e($form['professional_experience']) ?></textarea></label>
                    <label class="wide">Research experience <textarea name="research_experience" rows="3"><?= e($form['research_experience']) ?></textarea></label>
                    <label class="wide">Previous panelist or thesis defense experience <textarea name="previous_panelist_experience" rows="3"><?= e($form['previous_panelist_experience']) ?></textarea></label>
                    <label class="wide">Areas where you are qualified to serve as a panelist <textarea name="qualified_areas" rows="3" required><?= e($form['qualified_areas']) ?></textarea></label>
                </div>
            </section>
            <section class="panel-app-card" data-step="3" hidden>
                <span class="panel-app-eyebrow">04 · Availability &amp; Preferred Review Roles</span>
                <h2>Availability and defense preferences</h2>
                <p class="panel-app-help">These items describe your qualifications and general availability only; they do not create an assignment.</p>
                <div class="panel-app-grid">
                    <label>Available days <input name="available_days" placeholder="e.g. Monday-Friday or Weekdays" value="<?= e($form['available_days']) ?>"></label>
                    <label>Available time ranges <input name="available_time_ranges" placeholder="e.g. 9:00 AM-5:00 PM" value="<?= e($form['available_time_ranges']) ?>"></label>
                    <label class="wide">Preferred schedule or comments <textarea name="preferred_schedule" rows="3"><?= e($form['preferred_schedule']) ?></textarea></label>
                    <label class="wide">Unavailable periods or dates <textarea name="unavailable_periods" rows="3"><?= e($form['unavailable_periods']) ?></textarea></label>
                </div>
                <div class="panel-app-checklist">
                    <h3>Preferred defense / review roles</h3>
                    <?php $selectedDefensePrefs = array_map('trim', preg_split('/\s*,\s*/', (string) $form['defense_preferences'])); ?>
                    <?php foreach (['Title Defense', 'Proposal Defense', 'Final Defense', 'Research Proposal Review', 'Manuscript Review', 'Other research review activities'] as $option): ?>
                        <label class="panel-app-checkbox"><input type="checkbox" name="defense_preferences[]" value="<?= e($option) ?>" <?= in_array($option, $selectedDefensePrefs, true) ? 'checked' : '' ?>> <span><?= e($option) ?></span></label>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="panel-app-card" data-step="4" hidden>
                <span class="panel-app-eyebrow">05 · Required Documents</span>
                <h2>Attach supporting documents</h2>
                <p class="panel-app-help">Upload the documents that support your qualifications and professional background. Accepted formats: PDF, DOCX, JPG, or PNG (up to 10 MB each).</p>
                <div class="panel-app-upload-grid">
                    <?php foreach (['curriculum_vitae' => 'Curriculum vitae (required)', 'degree_proof' => 'Proof of degree / qualification (required)', 'other_document' => 'Other supporting document (optional)'] as $key => $label): ?>
                    <label class="panel-app-upload">
                        <span class="panel-app-upload-icon" aria-hidden="true">↑</span><strong><?= e($label) ?></strong>
                        <small><?= isset($documents[$key]) ? 'Current file: ' . e((string) $documents[$key]['original_name']) : 'Choose a file to upload' ?></small>
                        <input type="file" name="documents[<?= e($key) ?>]" accept=".pdf,.docx,.jpg,.jpeg,.png">
                    </label>
                    <?php endforeach; ?>
                </div>
            </section>
            <section class="panel-app-card" data-step="5" hidden>
                <span class="panel-app-eyebrow">06 · Review &amp; Submit</span>
                <h2>Review your profile before sending</h2>
                <p class="panel-app-help">Confirm the details below before final submission. CRAD review determines whether you are added to the panelist pool and remains separate from panel assignment.</p>
                <div class="panel-app-review" id="applicationReview"></div>
                <label class="panel-app-consent"><input type="checkbox" name="applicant_consent" value="1"> I confirm that the information and supporting documents are accurate and may be reviewed by the CRAD Officer.</label>
            </section>
            <div class="panel-app-actions">
                <button class="panel-app-button subtle" type="button" id="previousStep" hidden>Back</button>
                <button class="panel-app-button subtle" type="submit" name="application_action" value="draft">Save as draft</button>
                <span class="panel-app-actions-spacer"></span>
                <button class="panel-app-button primary" type="button" id="nextStep">Continue</button>
                <button class="panel-app-button primary" type="button" id="prepareSubmit" hidden>Submit application</button>
            </div>
        </form>
        <dialog class="panel-app-confirm" id="submitConfirm">
            <form method="dialog"><span class="panel-app-eyebrow">Final confirmation</span><h2>Submit your application?</h2><p>After submission, the CRAD Officer will review your qualifications and determine whether you are eligible for the panelist pool. This does not create a panel assignment or research group membership.</p><div class="panel-app-confirm-actions"><button class="panel-app-button subtle" value="cancel">Review again</button><button class="panel-app-button primary" id="confirmSubmit" value="submit">Confirm submission</button></div></form>
        </dialog>
        <?php elseif ($application): ?>
            <section class="panel-app-card">
                <span class="panel-app-eyebrow">Application tracking</span><h2><?= $status === 'Approved' ? 'Application approved' : ($status === 'Rejected' ? 'Application decision' : 'Application received') ?></h2>
                <p><?= $status === 'Approved' ? 'You are now eligible for panel configuration. Approval does not automatically assign you to a research group or create a panelist account.' : 'Your application and review status are shown below.' ?></p>
                <?php if (in_array($status, ['Draft', 'Request Revision'], true)): ?>
                    <a class="panel-app-button primary" href="<?= e(BASE_URL . '/login/panelist-application.php?resume=' . rawurlencode($token)) ?>">Edit application</a>
                <?php endif; ?>
                <ol class="panel-app-history"><?php
                    $historyStmt = $pdo->prepare('SELECT old_status, new_status, actor_name, note, created_at FROM `crad_panel_application_history` WHERE application_id = ? ORDER BY id DESC');
                    $historyStmt->execute([(int) $application['id']]);
                    foreach (($historyStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $history): ?>
                    <li><strong><?= e((string) $history['new_status']) ?></strong><span><?= e((string) $history['note']) ?></span><small><?= e((string) $history['actor_name']) ?> · <?= e((string) $history['created_at']) ?></small></li>
                <?php endforeach; ?></ol>
            </section>
        <?php endif; ?>
        <footer class="panel-app-footer">Research and Development Office · Bestlink College of the Philippines</footer>
    </main>
    <script src="<?= e(BASE_URL . '/assets/js/panelist-application.js') ?>" defer></script>
</body>
</html>
