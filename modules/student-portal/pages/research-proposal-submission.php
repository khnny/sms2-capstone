<?php
/**
 * SMS 2 - Research Proposal Submission
 * Student Portal — CRAD FORM S2 V3 (Title Approval)
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../modules/crad/config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/breadcrumbs.php';
require_once ROOT_PATH . '/modules/crad/includes/title-approval-assignees.php';
require_once ROOT_PATH . '/modules/crad/includes/research-group-flow.php';

$studentId     = $_SESSION['student_id'] ?? 'S230000001';
$studentUserId = $_SESSION['user_id']    ?? null;
$studentName   = $_SESSION['user_name']  ?? 'Juan Dela Cruz';

/**
 * Format a person name as "Last, First M.I." (CRAD Form S2).
 * Example: John Kenneth C. Abejuela → Abejuela, John Kenneth C.
 */
$smsFormatLastFirstMi = static function (string $fullName): string {
    $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');
    if ($fullName === '') {
        return '';
    }

    $isMiddleInitial = static function (string $token): bool {
        return (bool) preg_match('/^[A-Za-z]\.?$/', $token);
    };
    $isSurnameParticle = static function (string $token): bool {
        return in_array(strtolower(rtrim($token, '.')), [
            'de', 'del', 'dela', 'da', 'das', 'do', 'dos',
            'la', 'las', 'los', 'van', 'von', 'san', 'santa', 'sta', 'sto',
        ], true);
    };
    $normalizeMi = static function (string $token): string {
        return strtoupper(substr($token, 0, 1)) . '.';
    };

    $lastName = '';
    $given = '';
    $middleInitial = '';

    if (str_contains($fullName, ',')) {
        [$lastName, $rest] = array_pad(array_map('trim', explode(',', $fullName, 2)), 2, '');
        $restParts = array_values(array_filter(preg_split('/\s+/', $rest) ?: [], static fn($p) => $p !== ''));
        if ($restParts !== [] && $isMiddleInitial($restParts[count($restParts) - 1])) {
            $middleInitial = $normalizeMi(array_pop($restParts));
        }
        $given = implode(' ', $restParts);
    } else {
        $parts = array_values(array_filter(preg_split('/\s+/', $fullName) ?: [], static fn($p) => $p !== ''));
        if ($parts === []) {
            return '';
        }
        if (count($parts) === 1) {
            return $parts[0];
        }

        $lastParts = [array_pop($parts)];
        while ($parts !== [] && $isSurnameParticle($parts[count($parts) - 1])) {
            array_unshift($lastParts, array_pop($parts));
        }
        $lastName = implode(' ', $lastParts);

        if ($parts !== [] && $isMiddleInitial($parts[count($parts) - 1])) {
            $middleInitial = $normalizeMi(array_pop($parts));
        }
        $given = implode(' ', $parts);
    }

    $formatted = trim($lastName);
    $suffix = trim($given . ($middleInitial !== '' ? ' ' . $middleInitial : ''));
    if ($suffix !== '') {
        $formatted .= ', ' . $suffix;
    }

    return $formatted;
};

$defaultMemberName = $smsFormatLastFirstMi($studentName);
$requestedResubmitId = (int) ($_GET['resubmit_title_approval'] ?? 0);
$submitted = ($_GET['process'] ?? '') === 'submit-proposal';
$resubmitSubmission = null;

/* ── Try to restore a previously saved submission from the DB ──────── */
$existingSubmission  = null; // will hold the title_approvals row if found
$alreadySentToAdviser = false; // true when the row was saved before this pageload
try {
    $cradPdoEarly = getCradDatabaseConnection();
    if ($requestedResubmitId > 0) {
        $exStmt = $cradPdoEarly->prepare(
            "SELECT * FROM `crad_title_approvals`
             WHERE student_id = :sid AND id = :id
             LIMIT 1"
        );
        $exStmt->execute([':sid' => $studentId, ':id' => $requestedResubmitId]);
        $resubmitSubmission = $exStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } else {
        $exStmt = $cradPdoEarly->prepare(
            "SELECT * FROM `crad_title_approvals`
             WHERE student_id = :sid
             ORDER BY id DESC
             LIMIT 1"
        );
        $exStmt->execute([':sid' => $studentId]);
        $existingSubmission = $exStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable) {
    $existingSubmission = null;
    $resubmitSubmission = null;
}

$isResubmitMode = false;

if ($existingSubmission) {
    if (!$submitted && $requestedResubmitId <= 0 && ((string) ($existingSubmission['status'] ?? '') === 'Returned' || (string) ($existingSubmission['coordinator_status'] ?? '') === 'Returned')) {
        header('Location: ' . BASE_URL . '/notifications/view.php?type=returned_title_approval&title_approval=' . (int) $existingSubmission['id']);
        exit;
    }
    /* Restore all submitted* vars from DB — works on refresh / back */
    $submitted             = true;
    $isResubmitMode = ((string) ($existingSubmission['status'] ?? '') === 'Returned' || (string) ($existingSubmission['coordinator_status'] ?? '') === 'Returned')
        && ($requestedResubmitId <= 0 || $requestedResubmitId === (int) ($existingSubmission['id'] ?? 0));
    $alreadySentToAdviser  = !$isResubmitMode; // returned rows can be sent again without duplicating

    $submittedDate         = $existingSubmission['submission_date'] ?? date('Y-m-d');
    $submittedDepartment   = $existingSubmission['department']      ?? 'College of Computer Studies';
    $submittedDiscipline   = $existingSubmission['discipline_cluster'] ?? '';
    $submittedSdg          = $existingSubmission['primary_sdg']     ?? '';
    $submittedTitle        = strtoupper(trim((string) ($existingSubmission['proposed_title'] ?? '')));
    $submittedJustification = trim((string) ($existingSubmission['sdg_justification'] ?? ''));

    /* Restore research_agenda — may be the "Others" raw text */
    $rawAgenda     = $existingSubmission['research_agenda'] ?? '';
    $submittedAgenda = $rawAgenda;

    /* Decode members JSON back into parallel arrays */
    $membersDecoded   = json_decode((string) ($existingSubmission['members_json'] ?? '[]'), true);
    $submittedMembers  = [];
    $submittedSections = [];
    if (is_array($membersDecoded)) {
        foreach ($membersDecoded as $m) {
            $submittedMembers[]  = $m[0] ?? ($m['name']    ?? '');
            $submittedSections[] = $m[1] ?? ($m['section'] ?? '');
        }
    }
} else {
    /* Fresh submission via GET (just submitted the form right now) */
    $submittedDate         = $_GET['submission_date'] ?? date('Y-m-d');
    $submittedDepartment   = $_GET['department']      ?? 'College of Computer Studies';
    $submittedMembers      = array_values((array) ($_GET['member_name']    ?? []));
    $submittedSections     = array_values((array) ($_GET['member_section'] ?? []));
    $submittedDiscipline   = $_GET['discipline_cluster'] ?? '';
    $submittedAgenda       = $_GET['research_agenda']    ?? '';
    if (str_starts_with($submittedAgenda, 'Others')) {
        $submittedAgenda = trim((string) ($_GET['research_agenda_others'] ?? '')) ?: $submittedAgenda;
    }
    $submittedSdg           = $_GET['primary_sdg']      ?? '';
    $submittedTitle         = strtoupper(trim((string) ($_GET['proposed_title']   ?? '')));
    $submittedJustification = trim((string) ($_GET['sdg_justification'] ?? ''));
}

/* ── Look up assigned adviser for this student ─────────────────── */
if ($resubmitSubmission && ((string) ($resubmitSubmission['status'] ?? '') === 'Returned' || (string) ($resubmitSubmission['coordinator_status'] ?? '') === 'Returned')) {
    $isResubmitMode = true;
    $alreadySentToAdviser = false;
}

$assignedAdviserName  = '';
$assignedAdviserEmail = '';
$assignedCoordName    = '';
$rgMembersLocked = false;

/* Restore signatures from the saved submission; names always come from Admin assignments. */
$adviserSignatureData = ''; // base64 PNG of the adviser's digital signature (if approved)
$coordinatorSignatureData = '';
$cradSignatureData = '';
$coordinatorScreening = [];
if ($existingSubmission) {
    $adviserSignatureData = (string) ($existingSubmission['adviser_signature_data'] ?? '');
    $coordinatorSignatureData = (string) ($existingSubmission['coordinator_signature_data'] ?? '');
    $cradSignatureData = (string) ($existingSubmission['crad_signature_data'] ?? '');
    $decodedScreening = json_decode((string) ($existingSubmission['coordinator_screening_json'] ?? '{}'), true);
    $coordinatorScreening = is_array($decodedScreening) ? $decodedScreening : [];
}

try {
    $cradPdoAssign = (isset($cradPdoEarly) && $cradPdoEarly instanceof PDO)
        ? $cradPdoEarly
        : getCradDatabaseConnection();
    $officialAssignees = cradStudentOfficialAssignees($cradPdoAssign, (string) $studentId);
    $assignedAdviserName  = (string) ($officialAssignees['adviser_name'] ?? '');
    $assignedAdviserEmail = (string) ($officialAssignees['adviser_email'] ?? '');
    $assignedCoordName    = (string) ($officialAssignees['coordinator_name'] ?? '');
    // Research Group gate + auto-fill members for Title Approval Form.
    $rgTitleGate = ['ok' => false, 'message' => 'Research Group required.', 'members' => []];
    $rgMembersLocked = false;
    try {
        if (isset($cradPdoAssign) && $cradPdoAssign instanceof PDO) {
            $rgTitleGate = cradRgFlowTitleApprovalGate($cradPdoAssign, (string) $studentId);
            // Always prefer Research Group members for the Title Approval members section.
            $rgMembersForForm = [];
            if (function_exists('cradRgFlowGetStudentGroup') && function_exists('cradRgFlowMembersAsTitleJson')) {
                $rgGroupRow = cradRgFlowGetStudentGroup($cradPdoAssign, (string) $studentId);
                if ($rgGroupRow) {
                    $rgMembersForForm = cradRgFlowMembersAsTitleJson($cradPdoAssign, (int) $rgGroupRow['id']);
                }
            }
            if ($rgMembersForForm === [] && !empty($rgTitleGate['members'])) {
                $rgMembersForForm = $rgTitleGate['members'];
            }
            $rgMembersLocked = false;
            if ($rgMembersForForm !== []) {
                $rgMembersLocked = true;
                $submittedMembers = [];
                $submittedSections = [];
                foreach ($rgMembersForForm as $mRow) {
                    $submittedMembers[] = (string) ($mRow[0] ?? '');
                    $submittedSections[] = (string) ($mRow[1] ?? '');
                }
            }
        }
    } catch (Throwable $e) {
        error_log('Title approval research group gate failed: ' . $e->getMessage());
    }
    cradSyncTitleApprovalAssigneeNames($cradPdoAssign, (string) $studentId);
} catch (Throwable $e) {
    error_log('Title approval assignee lookup failed: ' . $e->getMessage());
}

if (isset($_GET['ajax']) && (string) $_GET['ajax'] === 'assignees') {
    requireAuth();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'adviser_name' => $assignedAdviserName,
        'adviser_email' => $assignedAdviserEmail,
        'coordinator_name' => $assignedCoordName,
        'ready' => $assignedAdviserName !== '' && $assignedCoordName !== '' && !empty($rgTitleGate['ok']),
        'gate_ok' => !empty($rgTitleGate['ok']),
        'gate_stage' => (string) ($rgTitleGate['stage'] ?? ''),
        'gate_message' => (string) ($rgTitleGate['message'] ?? ''),
        'server_time' => date('c'),
    ]);
    exit;
}

// Hard-block Research Proposal apply until Research Group + coor/adviser + dual confirm.
// Allow viewing when a Title Approval / proposal row already exists.
$rgProposalAccess = ['ok' => false, 'message' => 'Submit your Research Group first.', 'stage' => 'no_group'];
try {
    $rgAccessPdo = isset($cradPdoAssign) && $cradPdoAssign instanceof PDO
        ? $cradPdoAssign
        : (function_exists('getCradDatabaseConnection') ? getCradDatabaseConnection() : null);
    if ($rgAccessPdo instanceof PDO && function_exists('cradRgFlowProposalAccessGate')) {
        $rgProposalAccess = cradRgFlowProposalAccessGate($rgAccessPdo, (string) $studentId);
    }
} catch (Throwable $e) {
    error_log('Research proposal access gate failed: ' . $e->getMessage());
}
$hasExistingTitleRow = ((int) ($existingSubmission['id'] ?? 0) > 0)
    || ((int) ($resubmitSubmission['id'] ?? 0) > 0)
    || !empty($alreadySentToAdviser);
if (empty($rgProposalAccess['ok']) && !$hasExistingTitleRow) {
    $pageTitle = 'Research Proposal Submission';
    $activeModule = 'student_portal';
    $activePage = 'research-proposal-submission';
    $breadcrumbs = [
        ['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/dashboard.php'],
        ['label' => 'Research Proposal', 'url' => null],
    ];
    require_once ROOT_PATH . '/includes/layout-start.php';
    if (function_exists('renderBreadcrumbs')) {
        renderBreadcrumbs($breadcrumbs);
    }
    echo '<div class="glass-dashboard"><div class="glass-board"><div class="glass-panel"><div class="glass-panel-body">';
    echo '<div class="alert alert-warning mb-3" role="status">';
    echo htmlspecialchars((string) ($rgProposalAccess['message'] ?? 'Submit your Research Group first.'));
    echo '</div>';
    echo '<a class="btn btn-primary" href="' . htmlspecialchars(BASE_URL . '/modules/student-portal/pages/research-group.php') . '">';
    echo 'Open Research Group</a>';
    echo '</div></div></div></div>';
    require_once ROOT_PATH . '/includes/layout-end.php';
    exit;
}

$pageTitle = 'Research Proposal Submission';
$activeModule = 'student_portal';
$activePage = 'research-proposal-submission';
$breadcrumbs = [
    ['label' => 'Student Portal', 'url' => BASE_URL . '/modules/student-portal/pages/my-profile.php'],
    ['label' => 'Research Proposal Submission', 'url' => null],
];

$departments = [
    'College of Computer Studies',
    'College of Business Administration',
    'College of Education',
    'College of Criminal Justice',
    'College of Hospitality & Tourism Management',
    'College of Nursing and Health Sciences',
];

$screeningChecked = static function (array $screening, string $key, string $value): string {
    return strtolower((string) ($screening[$key] ?? '')) === $value ? '&#10003;' : '';
};

$disciplineClusters = [
    'Natural Sciences, Environmental Studies, and Mathematics',
    'Education, Curriculum, Teaching, and Learning',
    'Health Sciences, Mental Health, and Allied Professions',
    'Engineering, Information Technology, and Computing',
    'Humanities, Social Sciences, and Public Safety',
    'Business, Entrepreneurship, Hospitality, and Tourism Management',
];

$researchAgendas = [
    'Quality Education and Human Capital Development',
    'Inclusive Economic Development, Entrepreneurship, and Industry Competitiveness',
    'Science, Technology, Digital Transformation, and Innovation',
    'Community Health, Well-being, and Social Development',
    'Sustainable Communities, Environmental Stewardship, and Climate Resilience',
    'Peace, Good Governance, Public Safety, and Social Justice',
    'Global Partnerships, Research Excellence, and Institutional Sustainability',
    'Others [Please Specify Below]',
];

$sdgOptions = [
    ['num' => 1,  'title' => 'No Poverty'],
    ['num' => 2,  'title' => 'Zero Hunger'],
    ['num' => 3,  'title' => 'Good Health and Well-being'],
    ['num' => 4,  'title' => 'Quality Education'],
    ['num' => 5,  'title' => 'Gender Equality'],
    ['num' => 6,  'title' => 'Clean Water and Sanitation'],
    ['num' => 7,  'title' => 'Affordable and Clean Energy'],
    ['num' => 8,  'title' => 'Decent Work and Economic Growth'],
    ['num' => 9,  'title' => 'Industry, Innovation and Infrastructure'],
    ['num' => 10, 'title' => 'Reduced Inequalities'],
    ['num' => 11, 'title' => 'Sustainable Cities and Communities'],
    ['num' => 12, 'title' => 'Responsible Consumption and Production'],
    ['num' => 13, 'title' => 'Climate Action'],
    ['num' => 14, 'title' => 'Life Below Water'],
    ['num' => 15, 'title' => 'Life on Land'],
    ['num' => 16, 'title' => 'Peace, Justice and Strong Institutions'],
    ['num' => 17, 'title' => 'Partnerships for the Goals'],
];

$defaultDiscipline = 'Engineering, Information Technology, and Computing';
$defaultAgenda = 'Science, Technology, Digital Transformation, and Innovation';
$defaultSdg = 9;

require_once ROOT_PATH . '/includes/layout-start.php';
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="student-portal crad-title-form-wrap">
    <?php if ($submitted): ?>
        <div class="crad-print-preview">
            <div class="crad-print-actions">
                <button type="button" class="crad-btn-print" onclick="window.print()">
                    <?= smsIcon('print') ?> Print Title Approval Form
                </button>
            </div>

            <article class="crad-print-sheet">
                <header class="print-document-header">
                    <div class="print-bcp-logo-wrap">
                        <img class="print-bcp-logo" src="<?= BASE_URL ?>/images/bcp-crest.png?v=20260811" alt="Bestlink College of the Philippines logo">
                    </div>
                    <div class="print-school-name">
                        <strong>BESTLINK COLLEGE OF THE PHILIPPINES</strong>
                        <span>#1071 Brgy. Kaligayahan, Quirino Highway, Novaliches, Quezon City</span>
                        <b>CENTER FOR RESEARCH AND DEVELOPMENT</b>
                    </div>
                    <div class="print-form-code">CRAD Form S2 V3</div>
                </header>

                <h1 class="print-document-title">TITLE APPROVAL FORM</h1>

                <div class="print-meta">
                    <div><strong>Date:</strong> <span><?= htmlspecialchars(date('Y-m-d', strtotime($submittedDate))) ?></span></div>
                    <div><strong>I. Department:</strong> <span><?= htmlspecialchars($submittedDepartment) ?></span></div>
                </div>

                <section class="print-section">
                    <h2>II. Students Information</h2>
                    <table class="print-table print-student-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Name (Last name, First Name, Middle Initial)</th>
                                <th>Section</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars(strtoupper($submittedMembers[$i] ?? '')) ?></td>
                                    <td><?= htmlspecialchars(strtoupper($submittedSections[$i] ?? '')) ?></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </section>

                <div class="print-two-column print-align-row">
                    <section class="print-choice-box">
                        <h2>III. Research Discipline Cluster</h2>
                        <?php foreach ($disciplineClusters as $cluster): ?>
                            <div class="<?= $cluster === $submittedDiscipline ? 'is-selected' : '' ?>">
                                [<?= $cluster === $submittedDiscipline ? '✓' : ' ' ?>] <?= htmlspecialchars($cluster) ?>
                            </div>
                        <?php endforeach; ?>
                    </section>

                    <section class="print-choice-box print-sdg-box">
                        <h2>IV. Sustainable Development Goal (SDG) Alignment</h2>
                        <p>Select the one (1) primary SDG that aligns with proposed research.</p>
                        <div class="print-sdg-list">
                            <?php foreach ($sdgOptions as $sdg): ?>
                                <?php $sdgLabel = 'SDG ' . $sdg['num'] . ' — ' . $sdg['title']; ?>
                                <div class="<?= $sdgLabel === $submittedSdg ? 'is-selected' : '' ?>">
                                    [<?= $sdgLabel === $submittedSdg ? '✓' : ' ' ?>]
                                    SDG <?= (int) $sdg['num'] ?> - <?= htmlspecialchars($sdg['title']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>

                <section class="print-choice-box print-agenda-box">
                    <h2>V. Institutional Research Agenda Alignment</h2>
                    <div class="print-agenda-list">
                        <?php foreach ($researchAgendas as $agenda): ?>
                            <?php
                            $agendaSelected = $agenda === $submittedAgenda
                                || (str_starts_with($submittedAgenda, 'Others')
                                    && str_starts_with($agenda, 'Others'));
                            ?>
                            <div class="<?= $agendaSelected ? 'is-selected' : '' ?>">
                                [<?= $agendaSelected ? '✓' : ' ' ?>] <?= htmlspecialchars($agenda) ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (str_starts_with($submittedAgenda, 'Others')): ?>
                            <div class="print-other-value"><?= htmlspecialchars($submittedAgenda) ?></div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="print-response-section">
                    <h2>VI. Proposed Research Title</h2>
                    <div class="print-response"><?= htmlspecialchars($submittedTitle) ?></div>
                </section>

                <section class="print-response-section">
                    <h2>VII. Sustainable Development Goal Justification</h2>
                    <div class="print-response print-response-small"><?= htmlspecialchars($submittedJustification) ?></div>
                </section>

                <div class="print-two-column print-approval-row">
                    <section class="print-choice-box">
                        <h2>VIII. Research Coordinator Screening</h2>
                        <table class="print-table">
                            <thead><tr><th>Evaluation Criteria</th><th>Yes</th><th>No</th></tr></thead>
                            <tbody>
                                <tr><td>Title aligns with institutional research agenda</td><td><?= $screeningChecked($coordinatorScreening, 'agenda_alignment', 'yes') ?></td><td><?= $screeningChecked($coordinatorScreening, 'agenda_alignment', 'no') ?></td></tr>
                                <tr><td>Proposed study is feasible and original</td><td><?= $screeningChecked($coordinatorScreening, 'feasible_original', 'yes') ?></td><td><?= $screeningChecked($coordinatorScreening, 'feasible_original', 'no') ?></td></tr>
                                <tr><td>Ethical and SDG requirements are satisfied</td><td><?= $screeningChecked($coordinatorScreening, 'ethical_sdg', 'yes') ?></td><td><?= $screeningChecked($coordinatorScreening, 'ethical_sdg', 'no') ?></td></tr>
                            </tbody>
                        </table>
                    </section>
                    <section class="print-choice-box print-signature-box print-approval-ix">
                        <h2>IX. Approval (Name, signature and date)</h2>
                        <div class="print-approval-block">
                            <div class="print-sig-wrap">
                                <div class="print-signature-line"></div>
                                <?php if ($adviserSignatureData !== ''): ?>
                                    <img class="print-adviser-sig-img" src="<?= htmlspecialchars($adviserSignatureData) ?>" alt="Adviser Signature">
                                <?php endif; ?>
                            </div>
                            <strong class="print-approver-name" id="tafAdviserName"><?= htmlspecialchars($assignedAdviserName) ?></strong>
                            <span class="print-approver-role">Research Adviser</span>
                        </div>
                        <div class="print-approval-block">
                            <div class="print-sig-wrap">
                                <div class="print-signature-line"></div>
                                <?php if ($coordinatorSignatureData !== ''): ?>
                                    <img class="print-adviser-sig-img" src="<?= htmlspecialchars($coordinatorSignatureData) ?>" alt="Coordinator Signature">
                                <?php endif; ?>
                            </div>
                            <strong class="print-approver-name" id="tafCoordinatorName"><?= htmlspecialchars($assignedCoordName) ?></strong>
                            <span class="print-approver-role">Program Research Coordinator</span>
                        </div>
                        <div class="print-approval-divider"></div>
                        <div class="print-approval-block print-received-block">
                            <small>Received:</small>
                            <div class="print-sig-wrap">
                                <div class="print-signature-line"></div>
                                <?php if ($cradSignatureData !== ''): ?>
                                    <img class="print-adviser-sig-img" src="<?= htmlspecialchars($cradSignatureData) ?>" alt="CRAD Officer Signature">
                                <?php endif; ?>
                            </div>
                            <strong class="print-approver-name">Center for Research and Development</strong>
                            <span class="print-approver-role">Center for Research and Development Office</span>
                        </div>
                    </section>
                </div>

                <footer class="print-page-footer">CRAD Form S2 V3 &nbsp; • &nbsp; Page 1 of 1</footer>
            </article>

            <div class="crad-below-sheet">
                <?php if (empty($rgTitleGate['ok'])): ?>
<div class="alert alert-warning" id="rgGateNote" role="status">
  <?= htmlspecialchars((string) ($rgTitleGate['message'] ?? 'Complete Research Group and dual confirmation before Title Approval.')) ?>
  <div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars(BASE_URL . '/modules/student-portal/pages/research-group.php') ?>">Open Research Group</a></div>
</div>
<?php endif; ?>
<?php $approvalNamesReady = trim($assignedAdviserName) !== '' && trim($assignedCoordName) !== '' && !empty($rgTitleGate['ok']); ?>
                <button
                    type="button"
                    id="sendToAdviserBtn"
                    class="crad-btn-send-adviser<?= $alreadySentToAdviser ? ' is-sent' : ($approvalNamesReady ? '' : ' is-waiting-names') ?>"
                    <?= ($alreadySentToAdviser || !$approvalNamesReady) ? 'disabled' : '' ?>
                    data-already-sent="<?= $alreadySentToAdviser ? '1' : '0' ?>"
                    data-submission-id="<?= (int) (($resubmitSubmission['id'] ?? null) ?: ($existingSubmission['id'] ?? 0)) ?>"
                    data-resubmit="<?= $isResubmitMode ? '1' : '0' ?>"
                    data-adviser="<?= htmlspecialchars($assignedAdviserName) ?>"
                    data-adviser-email="<?= htmlspecialchars($assignedAdviserEmail) ?>"
                    data-coordinator="<?= htmlspecialchars($assignedCoordName) ?>"
                    data-title="<?= htmlspecialchars($submittedTitle) ?>"
                    data-dept="<?= htmlspecialchars($submittedDepartment) ?>"
                    data-date="<?= htmlspecialchars($submittedDate) ?>"
                    data-student-id="<?= htmlspecialchars($studentId) ?>"
                    data-student-user-id="<?= htmlspecialchars((string)$studentUserId) ?>"
                    data-student-name="<?= htmlspecialchars($studentName) ?>"
                    data-members="<?= htmlspecialchars(json_encode(array_map(null, $submittedMembers, $submittedSections))) ?>"
                    data-discipline="<?= htmlspecialchars($submittedDiscipline) ?>"
                    data-sdg="<?= htmlspecialchars($submittedSdg) ?>"
                    data-agenda="<?= htmlspecialchars($submittedAgenda) ?>"
                    data-justification="<?= htmlspecialchars($submittedJustification) ?>"
                >
                    <span class="crad-btn-send-icon"><?= smsIcon($alreadySentToAdviser ? 'check' : 'paper-plane') ?></span>
                    <span class="crad-btn-send-text"><?= $alreadySentToAdviser ? 'Document Packet Sent' : ($isResubmitMode ? 'Resubmit to Adviser' : 'Send to Adviser') ?></span>
                </button>
                <?php if (!$alreadySentToAdviser): ?>
                    <div class="crad-waiting-names-note" id="tafWaitingNamesNote"<?= $approvalNamesReady ? ' hidden' : '' ?> role="status">
                        <?= smsIcon('lock') ?>
                        <span><?= htmlspecialchars((string) ($rgTitleGate['message'] ?? 'Waiting for coordinator and adviser assignment. You cannot apply for Title Approval yet.')) ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($alreadySentToAdviser): ?>
                <div class="crad-document-packet-note" role="status">
                    <?= smsIcon('paper-plane') ?>
                    <div>
                        <strong>Document Packet Sent</strong>
                        <span>Current status: Document Packet Sent. This status is shown on your dashboard.</span>
                    </div>
                </div>
            <?php endif; ?>
            <?php $resubmitRemarks = trim((string) (($resubmitSubmission['adviser_remarks'] ?? null) ?: ($existingSubmission['adviser_remarks'] ?? ''))); ?>
            <?php if ($isResubmitMode && $resubmitRemarks !== ''): ?>
                <div class="crad-returned-note">
                    <strong><?= smsIcon('comment-dots') ?> Adviser remarks</strong>
                    <span><?= htmlspecialchars($resubmitRemarks) ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
    <form class="crad-title-form" id="cradTitleForm" method="get" action="">
        <input type="hidden" name="process" value="submit-proposal" id="cradProcessField" disabled>
        <?php if ($requestedResubmitId > 0): ?>
            <input type="hidden" name="resubmit_title_approval" value="<?= (int) $requestedResubmitId ?>">
        <?php endif; ?>

        <header class="crad-form-header">
            <div>
                <span class="crad-form-code">CRAD FORM S2 V3</span>
                <h1>Submit Title Approval Form</h1>
                <p>Register complete details for Bestlink CRD evaluation</p>
            </div>

        </header>

        <nav class="crad-stepper" aria-label="Form steps">
            <div class="crad-step is-active" data-step-indicator="1">
                <span class="crad-step-icon"><?= smsIcon('check') ?></span>
                <span class="crad-step-label">Students Information</span>
            </div>
            <div class="crad-step-line"></div>
            <div class="crad-step" data-step-indicator="2">
                <span class="crad-step-icon"><?= smsIcon('check') ?></span>
                <span class="crad-step-label">Alignments &amp; SDGs</span>
            </div>
            <div class="crad-step-line"></div>
            <div class="crad-step" data-step-indicator="3">
                <span class="crad-step-icon">3</span>
                <span class="crad-step-label">Proposed Title</span>
            </div>
        </nav>

        <div class="crad-form-body">
            <!-- Step 1: Students Information -->
            <section class="crad-step-panel is-active" data-step-panel="1">
                <div class="crad-field-row">
                    <div class="crad-field">
                        <label for="submissionDate">Submission Date <span>*</span></label>
                        <input type="date" id="submissionDate" name="submission_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="crad-field">
                        <label for="department">Department / College <span>*</span></label>
                        <select id="department" name="department" required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" <?= $dept === 'College of Computer Studies' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="crad-section-head">
                    <div>
                        <h2>II. Students Information</h2>
                        <p><?= !empty($rgMembersLocked) ? 'Members are loaded from your submitted Research Group (read-only).' : 'Provide complete research group metadata (Maximum 6 members).' ?></p>
                    </div>
                    <?php if (empty($rgMembersLocked)): ?>
                    <button type="button" class="crad-link-btn" id="addMemberBtn">+ Add Member</button>
                    <?php endif; ?>
                </div>

                <div id="memberList" data-rg-locked="<?= !empty($rgMembersLocked) ? '1' : '0' ?>">
                    <?php
                    $memberCards = $submittedMembers !== [] ? $submittedMembers : [$defaultMemberName];
                    $sectionCards = $submittedSections !== [] ? $submittedSections : [''];
                    $cardCount = max(count($memberCards), 1);
                    for ($mi = 0; $mi < $cardCount; $mi++):
                        $mName = (string) ($memberCards[$mi] ?? '');
                        $mSection = (string) ($sectionCards[$mi] ?? '');
                        $ro = !empty($rgMembersLocked) ? 'readonly' : '';
                        $req = !empty($rgMembersLocked) ? '' : 'required';
                    ?>
                    <article class="crad-member-card" data-member="<?= (int) ($mi + 1) ?>">
                        <h3><span><?= (int) ($mi + 1) ?></span> Group Member Profile<?= $mi === 0 ? ' (Leader)' : '' ?></h3>
                        <div class="crad-field-row">
                            <div class="crad-field">
                                <label>Full Name (Last, First, M.I.) <span>*</span></label>
                                <input type="text" name="member_name[]" value="<?= htmlspecialchars($mName) ?>" placeholder="Abejuela, John Kenneth C." <?= $req ?> <?= $ro ?>>
                            </div>
                            <div class="crad-field">
                                <label>Section <span>*</span></label>
                                <input type="text" name="member_section[]" value="<?= htmlspecialchars($mSection) ?>" <?= $req ?> <?= $ro ?>>
                            </div>
                        </div>
                    </article>
                    <?php endfor; ?>
                </div>
            </section>

            <!-- Step 2: Alignments & SDGs -->
            <section class="crad-step-panel" data-step-panel="2" hidden>
                <div class="crad-align-block">
                    <div class="crad-align-head">
                        <h2>III. Research Discipline Cluster <span>*</span></h2>
                        <p>Select the discipline cluster that best aligns with your proposed research.</p>
                    </div>
                    <div class="crad-radio-grid">
                        <?php foreach ($disciplineClusters as $cluster): ?>
                            <label class="crad-radio-card">
                                <input type="radio" name="discipline_cluster" value="<?= htmlspecialchars($cluster) ?>" <?= $cluster === $defaultDiscipline ? 'checked' : '' ?> required>
                                <span><?= htmlspecialchars($cluster) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="crad-align-block">
                    <div class="crad-align-head">
                        <h2>V. Institutional Research Agenda Alignment <span>*</span></h2>
                        <p>Select the primary research agenda of Bestlink College of the Philippines.</p>
                    </div>
                    <div class="crad-radio-list">
                        <?php foreach ($researchAgendas as $agenda): ?>
                            <label class="crad-radio-card crad-radio-card-wide">
                                <input type="radio" name="research_agenda" value="<?= htmlspecialchars($agenda) ?>" <?= $agenda === $defaultAgenda ? 'checked' : '' ?> required>
                                <span><?= htmlspecialchars($agenda) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="crad-field crad-others-field" id="agendaOthersWrap" hidden>
                        <label for="agendaOthers">Please Specify <span>*</span></label>
                        <input type="text" id="agendaOthers" name="research_agenda_others" placeholder="Specify other institutional research agenda">
                    </div>
                </div>

                <div class="crad-align-block">
                    <div class="crad-align-head">
                        <h2>IV. Sustainable Development Goal (SDG) Alignment <span>*</span></h2>
                        <p>Select the <strong>one (1) primary SDG</strong> that aligns with the scope of your proposal.</p>
                    </div>
                    <div class="crad-sdg-tiles">
                        <?php foreach ($sdgOptions as $sdg): ?>
                            <label class="crad-sdg-tile <?= (int) $sdg['num'] === $defaultSdg ? 'is-selected' : '' ?>">
                                <input type="radio" name="primary_sdg" value="SDG <?= (int) $sdg['num'] ?> — <?= htmlspecialchars($sdg['title']) ?>" <?= (int) $sdg['num'] === $defaultSdg ? 'checked' : '' ?> required>
                                <span class="crad-sdg-num">SDG <?= (int) $sdg['num'] ?></span>
                                <span class="crad-sdg-title"><?= htmlspecialchars($sdg['title']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Step 3: Proposed Title -->
            <section class="crad-step-panel" data-step-panel="3" hidden>
                <div class="crad-align-block">
                    <div class="crad-align-head">
                        <h2>VI. Proposed Research Title <span>*</span></h2>
                        <p>Enter the complete, finalized uppercase title proposed by the group.</p>
                    </div>
                    <div class="crad-field">
                        <textarea
                            id="proposedTitle"
                            name="proposed_title"
                            class="crad-title-input"
                            rows="4"
                            placeholder="E.G. DEVELOPMENT OF IOT-BASED FLOOD MONITORING AND COMMUNITY RISK ALERT RESPONSE PORTAL"
                            required
                        ></textarea>
                    </div>
                </div>

                <div class="crad-align-block">
                    <div class="crad-align-head">
                        <h2>VII. Sustainable Development Goal Justification <span>*</span></h2>
                        <p>In one or two sentences, explain how the proposed study contributes directly to the selected SDG.</p>
                    </div>
                    <div class="crad-field">
                        <textarea
                            id="sdgJustification"
                            name="sdg_justification"
                            rows="4"
                            placeholder="e.g. This study directly supports SDG 11 by deploying real-time community water monitors, reducing flash flood response delay, and securing resilient regional infrastructure."
                            required
                        ></textarea>
                    </div>
                </div>

                <aside class="crad-verify-box">
                    <div class="crad-verify-icon"><?= smsIcon('shield-alt') ?></div>
                    <div>
                        <h3>Bestlink CRD Pre-submission Verification</h3>
                        <p>By clicking Submit, you authorize Bestlink College research coordinators to commence active evaluation checks (feasibility, originality, ethics, and agenda compliance).</p>
                    </div>
                </aside>
            </section>
        </div>

        <footer class="crad-form-footer">
            <button type="button" class="crad-btn crad-btn-secondary" id="cradBackBtn" disabled>← Back</button>
            <button type="button" class="crad-btn crad-btn-primary" id="cradNextBtn">Next Step →</button>
        </footer>
    </form>
    <?php endif; ?>
</div>

<style>
.crad-title-form-wrap { max-width: 100%; margin: 0; padding: 0 0.25rem; }
.crad-title-form {
    --crad-bg: #111827;
    --crad-panel: #1a2234;
    --crad-panel-2: #151c2c;
    --crad-border: rgba(148,163,184,0.22);
    --crad-text: #f8fafc;
    --crad-muted: #94a3b8;
    --crad-blue: #2563eb;
    --crad-blue-soft: rgba(37,99,235,0.18);
    background: linear-gradient(180deg, #172033 0%, var(--crad-bg) 42%);
    border: 1px solid rgba(148,163,184,0.16);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 18px 48px rgba(15,23,42,0.28);
    color: var(--crad-text);
}
.crad-form-header {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
    padding: 1.4rem 1.5rem 1.1rem;
    background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 48%, #0f172a 100%);
}
.crad-form-code {
    display: inline-block; margin-bottom: 0.35rem;
    color: rgba(226,232,240,0.78); font-size: 0.72rem; font-weight: 700;
    letter-spacing: 0.08em; text-transform: uppercase;
}
.crad-form-header h1 {
    margin: 0; color: #fff; font-size: 1.55rem; font-weight: 800; letter-spacing: -0.02em;
}
.crad-form-header p { margin: 0.35rem 0 0; color: rgba(226,232,240,0.82); font-size: 0.92rem; }
.crad-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.35rem;
    min-height: 42px; padding: 0.55rem 1rem; border-radius: 10px;
    border: 1px solid transparent; font-size: 0.9rem; font-weight: 700;
    text-decoration: none; cursor: pointer; transition: 0.15s ease;
}
.crad-btn-ghost {
    color: #fff; background: rgba(15,23,42,0.25); border-color: rgba(226,232,240,0.35);
    white-space: nowrap;
}
.crad-btn-ghost:hover { background: rgba(15,23,42,0.4); color: #fff; }
.crad-btn-secondary {
    color: #e2e8f0; background: transparent; border-color: rgba(148,163,184,0.35);
}
.crad-btn-secondary:hover:not(:disabled) { background: rgba(148,163,184,0.12); }
.crad-btn-secondary:disabled { opacity: 0.45; cursor: not-allowed; }
.crad-btn-primary {
    color: #fff; background: var(--crad-blue); border-color: var(--crad-blue);
    box-shadow: 0 8px 20px rgba(37,99,235,0.35);
}
.crad-btn-primary:hover { background: #1d4ed8; color: #fff; }
.crad-btn-success {
    color: #fff; background: #16a34a; border-color: #16a34a;
    box-shadow: 0 8px 20px rgba(22,163,74,0.35);
}
.crad-btn-success:hover { background: #15803d; color: #fff; }
.crad-stepper {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--crad-border);
    background: rgba(15,23,42,0.35); overflow-x: auto;
}
.crad-step {
    display: inline-flex; align-items: center; gap: 0.55rem;
    color: var(--crad-muted); white-space: nowrap; font-size: 0.9rem; font-weight: 600;
}
.crad-step-icon {
    width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 50%; background: #243044; color: #64748b; font-size: 0.75rem; font-weight: 800;
}
.crad-step.is-active { color: #60a5fa; }
.crad-step.is-active .crad-step-icon,
.crad-step.is-complete .crad-step-icon {
    background: var(--crad-blue); color: #fff;
}
.crad-step.is-complete { color: #86efac; }
.crad-step.is-complete .crad-step-icon {
    background: #16a34a; color: #fff;
}
.crad-step-line {
    flex: 1; min-width: 28px; height: 2px; background: rgba(148,163,184,0.25); border-radius: 99px;
}
.crad-form-body { padding: 1.35rem 1.5rem 0.5rem; }
.crad-field-row {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.15rem;
}
.crad-field { display: grid; gap: 0.4rem; margin-bottom: 1rem; }
.crad-field-row .crad-field { margin-bottom: 0; }
.crad-field label {
    color: var(--crad-muted); font-size: 0.72rem; font-weight: 700;
    letter-spacing: 0.05em; text-transform: uppercase;
}
.crad-field label span { color: #f87171; }
.crad-field input,
.crad-field select,
.crad-field textarea {
    width: 100%; min-height: 44px; padding: 0.7rem 0.85rem;
    border: 1px solid var(--crad-border); border-radius: 10px;
    background: var(--crad-panel); color: var(--crad-text);
    font-size: 0.92rem; outline: none;
    color-scheme: dark;
}
.crad-field textarea { min-height: 110px; resize: vertical; }
.crad-field input:focus,
.crad-field select:focus,
.crad-field textarea:focus {
    border-color: #3b82f6; box-shadow: 0 0 0 3px var(--crad-blue-soft);
}
.crad-field select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%94a3b8' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 0.9rem center; padding-right: 2.2rem;
}
.crad-section-head {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
    margin: 0.35rem 0 1rem;
}
.crad-section-head h2 { margin: 0; color: #fff; font-size: 1.05rem; font-weight: 800; }
.crad-section-head p { margin: 0.25rem 0 0; color: var(--crad-muted); font-size: 0.88rem; }
.crad-link-btn {
    border: 0; background: transparent; color: #60a5fa;
    font-size: 0.9rem; font-weight: 700; white-space: nowrap; cursor: pointer;
}
.crad-link-btn:hover { color: #93c5fd; }
.crad-member-card {
    margin-bottom: 1rem; padding: 1rem;
    border: 1px solid var(--crad-border); border-radius: 12px; background: var(--crad-panel-2);
}
.crad-member-card h3 {
    display: flex; align-items: center; gap: 0.55rem;
    margin: 0 0 0.9rem; color: #e2e8f0; font-size: 0.78rem;
    font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase;
}
.crad-member-card h3 span {
    width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 50%; background: var(--crad-blue); color: #fff; font-size: 0.72rem;
}
.crad-member-card .crad-remove {
    margin-left: auto; border: 0; background: transparent; color: #f87171;
    font-size: 0.78rem; font-weight: 700; cursor: pointer;
}
.crad-align-block {
    margin-bottom: 1.5rem; padding-bottom: 1.35rem;
    border-bottom: 1px solid var(--crad-border);
}
.crad-align-block:last-child { border-bottom: 0; margin-bottom: 0.5rem; padding-bottom: 0; }
.crad-align-head { margin-bottom: 0.9rem; }
.crad-align-head h2 {
    margin: 0; color: #fff; font-size: 1.02rem; font-weight: 800;
}
.crad-align-head h2 span { color: #f87171; }
.crad-align-head p {
    margin: 0.3rem 0 0; color: var(--crad-muted); font-size: 0.88rem;
}
.crad-align-head p strong { color: #e2e8f0; }
.crad-radio-grid {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem;
}
.crad-radio-list { display: grid; gap: 0.65rem; }
.crad-radio-card {
    display: flex; align-items: flex-start; gap: 0.7rem;
    padding: 0.85rem 0.95rem; border: 1px solid var(--crad-border);
    border-radius: 12px; background: var(--crad-panel); cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}
.crad-radio-card:hover { border-color: rgba(96,165,250,0.45); }
.crad-radio-card:has(input:checked) {
    border-color: #3b82f6;
    background: rgba(37,99,235,0.16);
    box-shadow: 0 0 0 1px rgba(59,130,246,0.35);
}
.crad-radio-card input {
    margin-top: 0.15rem; accent-color: var(--crad-blue); flex-shrink: 0;
}
.crad-radio-card span {
    color: #e2e8f0; font-size: 0.9rem; line-height: 1.4; font-weight: 600;
}
.crad-title-input {
    min-height: 120px !important;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1.45;
}
.crad-verify-box {
    display: flex; align-items: flex-start; gap: 0.85rem;
    margin: 0.25rem 0 0.75rem; padding: 1rem 1.1rem;
    border: 1px solid rgba(59,130,246,0.35);
    border-radius: 12px;
    background: rgba(30,64,175,0.22);
}
.crad-verify-icon {
    width: 36px; height: 36px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 10px; background: rgba(37,99,235,0.35); color: #93c5fd;
}
.crad-verify-box h3 {
    margin: 0 0 0.3rem; color: #dbeafe; font-size: 0.92rem; font-weight: 800;
}
.crad-verify-box p {
    margin: 0; color: #bfdbfe; font-size: 0.86rem; line-height: 1.45;
}
.crad-sdg-tiles {
    display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem;
}
.crad-sdg-tile {
    position: relative; display: flex; flex-direction: column; justify-content: center;
    min-height: 88px; padding: 0.9rem 0.95rem;
    border: 1px solid var(--crad-border); border-radius: 12px;
    background: var(--crad-panel); cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
}
.crad-sdg-tile input {
    position: absolute; opacity: 0; pointer-events: none;
}
.crad-sdg-num {
    color: var(--crad-muted); font-size: 0.7rem; font-weight: 800;
    letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 0.25rem;
}
.crad-sdg-title {
    color: #f1f5f9; font-size: 0.9rem; font-weight: 700; line-height: 1.3;
}
.crad-sdg-tile:hover { border-color: rgba(251,146,60,0.45); }
.crad-sdg-tile.is-selected,
.crad-sdg-tile:has(input:checked) {
    border-color: #f97316;
    background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
    box-shadow: 0 10px 24px rgba(249,115,22,0.28);
}
.crad-sdg-tile.is-selected .crad-sdg-num,
.crad-sdg-tile:has(input:checked) .crad-sdg-num,
.crad-sdg-tile.is-selected .crad-sdg-title,
.crad-sdg-tile:has(input:checked) .crad-sdg-title {
    color: #fff;
}
.crad-sdg-grid {
    display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.65rem;
}
.crad-check {
    display: flex; align-items: flex-start; gap: 0.65rem;
    padding: 0.75rem 0.85rem; border: 1px solid var(--crad-border);
    border-radius: 10px; background: var(--crad-panel); cursor: pointer;
}
.crad-check input { margin-top: 0.15rem; accent-color: var(--crad-blue); }
.crad-check span { color: #e2e8f0; font-size: 0.86rem; line-height: 1.35; }
.crad-form-footer {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 1rem 1.5rem 1.35rem; border-top: 1px solid var(--crad-border);
}

/* Printable CRAD form */
.crad-print-preview {
    padding: 1rem;
    border-radius: 16px;
    background: #111827;
}
.crad-print-actions {
    width: 210mm; max-width: 100%; display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;
    margin: 0 auto 1rem;
}
.crad-btn-print {
    display: inline-flex; align-items: center; gap: 0.5rem;
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff; font-weight: 600; font-size: 0.9rem;
    padding: 0.55rem 1.3rem; border-radius: 10px; border: none;
    box-shadow: 0 2px 10px rgba(37,99,235,0.35);
    cursor: pointer; transition: opacity 0.15s;
}
.crad-btn-print:hover { opacity: 0.88; }

/* Below-sheet button row */
.crad-below-sheet {
    width: 210mm; max-width: 100%; margin: 0 auto;
    display: flex; flex-direction: column; align-items: flex-end;
    padding: 0.85rem 0 0;
}
.crad-returned-note {
    width: 210mm; max-width: 100%; margin: .75rem auto 0;
    padding: .8rem 1rem; border: 1px solid #fbbf24; border-radius: 10px;
    background: #fffbeb; color: #78350f; font-size: .88rem;
}
.crad-returned-note strong {
    display: flex; align-items: center; gap: .45rem; margin-bottom: .35rem;
    font-weight: 800;
}
.crad-returned-note span { display: block; overflow-wrap: anywhere; }
.crad-btn-send-adviser {
    display: inline-flex; align-items: center; gap: 0.65rem;
    padding: 0.7rem 1.5rem;
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    color: #fff; font-weight: 700; font-size: 0.92rem;
    border: none; border-radius: 12px; cursor: pointer;
    box-shadow: 0 4px 18px rgba(22,163,74,0.45);
    transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
    white-space: nowrap; flex-shrink: 0;
}
.crad-btn-send-adviser:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(22,163,74,0.55);
}
.crad-btn-send-adviser:active { transform: translateY(0); }
.crad-btn-send-adviser:disabled {
    opacity: 0.6; cursor: not-allowed; transform: none;
    box-shadow: none;
}
.crad-btn-send-adviser.is-waiting-names {
    background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
    box-shadow: none;
    pointer-events: none;
}
.crad-waiting-names-note {
    width: 210mm; max-width: 100%; margin: .65rem auto 0;
    display: flex; align-items: flex-start; gap: .55rem;
    padding: .7rem 1rem; border-radius: 10px;
    background: #fff7ed; color: #9a3412; border: 1px solid #fdba74;
    font-size: .86rem; font-weight: 700; line-height: 1.4;
}
.crad-waiting-names-note[hidden] { display: none; }
.crad-btn-send-adviser.is-sent {
    background: linear-gradient(135deg, #0e7490 0%, #0c6380 100%);
    box-shadow: 0 4px 18px rgba(14,116,144,0.4);
    pointer-events: none;
}
.crad-btn-send-icon {
    width: 2rem; height: 2rem; border-radius: 50%;
    background: rgba(255,255,255,0.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}
.crad-document-packet-note {
    width: 210mm; max-width: 100%; margin: .75rem auto 0;
    padding: .8rem 1rem; border: 1px solid #86efac; border-radius: 10px;
    background: #f0fdf4; color: #14532d; font-size: .88rem;
    display: flex; align-items: flex-start; gap: .65rem;
}
.crad-document-packet-note i { margin-top: .12rem; color: #16a34a; }
.crad-document-packet-note strong {
    display: block; margin-bottom: .2rem; font-weight: 800;
}
.crad-document-packet-note span { display: block; overflow-wrap: anywhere; }
.crad-print-sheet {
    position: relative; width: 210mm; min-height: 297mm; max-width: 100%;
    margin: 0 auto 1.25rem; padding: 8mm 11mm 12mm;
    background: #fff; color: #111; font-family: Arial, Helvetica, sans-serif;
    box-shadow: 0 18px 48px rgba(0,0,0,0.35); box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    gap: 2.4mm;
}
.print-document-header {
    display: grid; grid-template-columns: 15mm 1fr auto; align-items: center; gap: 3mm;
    padding-bottom: 2.5mm; border-bottom: 1.5px solid #111;
    flex: 0 0 auto;
}
.print-bcp-mark {
    width: 12mm; height: 12mm; display: grid; place-items: center;
    border: 1.5px solid #333; border-radius: 50%; font-size: 7pt; font-weight: 800;
}
.print-bcp-logo-wrap {
    width: 15mm; height: 15mm; display: flex; align-items: center; justify-content: center;
    overflow: visible; border-radius: 0; background: transparent;
}
.print-bcp-logo {
    width: 12mm; max-width: 12mm; height: auto; display: block; flex: 0 0 auto;
    border-radius: 0 !important; object-fit: contain; background: transparent;
}
.print-school-name { display: grid; gap: 0.5mm; font-size: 7pt; line-height: 1.2; }
.print-school-name strong { font-size: 10pt; }
.print-school-name b { font-size: 8pt; }
.print-form-code { align-self: start; padding-top: 0.5mm; font-size: 7pt; font-weight: 700; }
.print-document-title {
    margin: 0; color: #111; font-size: 14pt; font-weight: 800;
    text-align: center; text-decoration: underline; flex: 0 0 auto;
}
.print-meta {
    display: grid; grid-template-columns: 1fr 1.5fr; gap: 8mm;
    margin: 0; font-size: 8.5pt; flex: 0 0 auto;
}
.print-meta span { display: inline-block; min-width: 40mm; padding: 0 1.5mm 0.5mm; border-bottom: 1px solid #333; }
.print-section { margin: 0; flex: 0 0 auto; }
.print-section h2,
.print-choice-box h2,
.print-response-section h2,
.print-notes-box h2 {
    margin: 0 0 1.4mm; color: #111; font-size: 8pt; font-weight: 800;
}
.print-table { width: 100%; border-collapse: collapse; font-size: 7.5pt; }
.print-table th, .print-table td { padding: 1.3mm 1.4mm; border: 0.8px solid #222; vertical-align: middle; }
.print-table th { font-weight: 800; text-align: center; }
.print-student-table th:first-child, .print-student-table td:first-child { width: 8mm; text-align: center; }
.print-student-table th:nth-child(3) { width: 28mm; }
.print-student-table th:nth-child(4) { width: 36mm; }
.print-student-table td { height: 5.8mm; }
.print-two-column {
    display: grid; grid-template-columns: 1fr 1.15fr; gap: 2.5mm;
    margin: 0; align-items: stretch; flex: 1 1 auto;
}
.print-align-row { grid-template-columns: 0.95fr 1.2fr; min-height: 0; }
.print-choice-stack {
    display: grid;
    gap: 2mm;
    align-content: start;
}
.print-choice-box { padding: 2mm; border: 0.8px solid #222; border-radius: 1.4mm; font-size: 7pt; }
.print-choice-box h2 { padding-bottom: 1mm; border-bottom: 0.8px solid #222; }
.print-choice-box > div { margin: 0.55mm 0; line-height: 1.3; }
.print-choice-box .is-selected { font-weight: 800; }
.print-other-value { padding-left: 3mm; font-style: italic; }
.print-sdg-box > p { margin: 0 0 1.2mm; font-size: 6.5pt; }
.print-sdg-list { display: grid; grid-template-columns: 1fr 1fr; column-gap: 2mm; row-gap: 0.2mm; }
.print-agenda-box { margin: 0; flex: 0 0 auto; }
.print-agenda-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    column-gap: 3mm;
    row-gap: 0.4mm;
}
.print-response-section { margin: 0; flex: 0 0 auto; }
.print-response {
    min-height: 8mm; padding: 2mm 3.5mm; border-bottom: 0.8px solid #222;
    font-size: 9pt; font-weight: 800; text-align: center; line-height: 1.3;
}
.print-response-small { min-height: 10mm; font-size: 8pt; font-weight: 400; text-align: left; }
.print-approval-row {
    margin: 0;
    flex: 0 0 auto;
    grid-template-columns: 1.05fr 0.95fr;
    align-items: start;
}
.print-approval-row > .print-choice-box:first-child {
    align-self: start;
    height: auto;
    min-height: 0;
}
.print-signature-box { text-align: center; }
.print-signature-box h2 { text-align: left; }
.print-signature-line {
    width: 82%;
    max-width: 56mm;
    height: 0;
    margin: 3mm auto 0.8mm;
    border: 0 !important;
    border-bottom: 1.1px solid #111 !important;
    background: none !important;
}
.print-signature-line::before,
.print-signature-line::after {
    content: none !important;
    display: none !important;
}
/* Signature wrapper — line always shows, adviser sig image sits above it */
.print-sig-wrap {
    position: relative;
    width: 82%;
    max-width: 56mm;
    height: 38pt;
    margin: 3mm auto 0.8mm;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}
.print-sig-wrap .print-signature-line {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    max-width: none;
    margin: 0;
}
.print-adviser-sig-img {
    position: absolute;
    bottom: 2pt;
    left: 0;
    width: 100%;
    height: 36pt;
    object-fit: contain;
    object-position: center bottom;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.print-signature-box strong { font-size: 7pt; font-weight: 400; }
.print-evaluator-box {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 4mm;
    min-height: 0;
}
.print-eval-sign-row {
    display: grid;
    justify-items: center;
    gap: 0.8mm;
    width: 100%;
}
.print-eval-line {
    width: 78%;
    max-width: 52mm;
    height: 0;
    border: 0;
    border-bottom: 1.1px solid #111;
}
.print-eval-sign-row span {
    color: #334155;
    font-size: 7pt;
}
.print-approval-ix { padding-bottom: 1mm; }
.print-approval-block {
    display: grid; justify-items: center; gap: 0.35mm;
    margin: 1.2mm 0 2mm;
    width: 100%;
}
.print-approver-name {
    color: #111 !important; font-size: 8pt !important; font-weight: 800 !important;
}
.print-approver-name.is-live-flash {
    animation: tafNameFlash 1.2s ease;
}
@keyframes tafNameFlash {
    0% { background: #fef3c7; }
    100% { background: transparent; }
}
.print-approver-role {
    display: block; color: #334155; font-size: 7pt;
}
.print-approval-divider {
    width: 88%; height: 0; margin: 0.8mm auto 1.4mm;
    border-top: 0.8px dashed #7c8da5;
}
.print-received-block small {
    display: block; width: 82%; max-width: 56mm; margin: 0 auto 0.3mm;
    color: #475569; font-size: 6.5pt; text-align: left;
}
.print-page-footer {
    position: absolute; right: 11mm; bottom: 4.5mm; left: 11mm;
    color: #333; font-size: 7pt; text-align: right;
}
.print-evaluation-table td { height: 11mm; }
.print-evaluation-table th:nth-child(2),
.print-evaluation-table th:nth-child(3) { width: 22mm; }
.print-evaluation-table th:last-child { width: 48mm; }
.print-notes-box { margin-top: 0; padding: 3mm; border: 0.8px solid #222; }
.print-notes-box div { height: 8mm; border-bottom: 0.6px solid #777; }
.print-final-decision { margin-top: 0; flex: 0 0 auto; }
.print-final-decision p { margin: 2.2mm 0; font-size: 8.5pt; }

/* Print visual polish */
.crad-print-sheet {
    --print-navy: #17366f;
    --print-blue: #2457a7;
    --print-pale: #edf4ff;
    --print-line: #b9c7da;
    border-top: 3mm solid var(--print-navy);
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.crad-print-sheet::before {
    content: "";
    position: absolute; top: 3mm; right: 0; width: 30mm; height: 1.1mm;
    background: #e9a23b;
}
.print-document-header {
    padding: 0.8mm 0 2.5mm;
    border-bottom: 1.1px solid var(--print-navy);
}
.print-bcp-mark {
    border: 1.5px solid var(--print-navy);
    color: var(--print-navy);
    box-shadow: inset 0 0 0 1.3mm #fff, inset 0 0 0 1.6mm var(--print-navy);
}
.print-school-name strong { color: var(--print-navy); letter-spacing: 0.02em; }
.print-school-name b { color: #243b63; }
.print-form-code {
    padding: 1.4mm 2mm;
    border: 0.7px solid #c3cede; border-radius: 1.3mm;
    background: var(--print-pale); color: var(--print-navy);
}
.print-document-title {
    position: relative; margin: 0;
    color: var(--print-navy); text-decoration: none; letter-spacing: 0.05em;
}
.print-document-title::after {
    content: ""; display: block; width: 32mm; height: 0.8mm;
    margin: 1.2mm auto 0; border-radius: 99px; background: #e9a23b;
}
.print-meta {
    padding: 2mm 2.8mm; border: 0.7px solid var(--print-line);
    border-radius: 1.3mm; background: #f8fbff;
}
.print-meta strong { color: var(--print-navy); }
.print-meta span { border-bottom-color: #7c8da5; font-weight: 600; }
.print-section h2,
.print-response-section h2,
.print-notes-box h2 {
    padding: 1.3mm 2mm;
    border-left: 1.3mm solid var(--print-blue);
    background: var(--print-pale); color: var(--print-navy);
    letter-spacing: 0.01em;
}
.print-table { border: 0.8px solid #6f7f95; }
.print-table th {
    border-color: #8796aa;
    background: #e4edf9; color: var(--print-navy);
}
.print-table td { border-color: #9ba8b9; }
.print-student-table tbody tr:nth-child(even) td { background: #fafcff; }
.print-choice-box {
    border-color: #8998ab;
    background: #fff;
    box-shadow: inset 0 0 0 0.3mm #f1f5f9;
}
.print-choice-box h2 {
    margin: -2mm -2mm 1.3mm;
    padding: 1.4mm 2.2mm;
    border: 0; border-radius: 1.1mm 1.1mm 0 0;
    background: var(--print-navy); color: #fff;
    letter-spacing: 0.01em;
}
.print-choice-box > div {
    padding: 0.45mm 0.9mm;
    border-radius: 0.7mm;
}
.print-choice-box .is-selected {
    background: #dceaff; color: #12366f;
    box-shadow: inset 1mm 0 0 var(--print-blue);
}
.print-response {
    min-height: 8.5mm; padding: 2.2mm 3.5mm;
    border: 0.7px solid var(--print-line); border-left: 1.3mm solid var(--print-blue);
    border-radius: 1.1mm; background: #fbfdff; color: #12294d;
}
.print-response-small { min-height: 11mm; color: #24364f; }
.print-signature-box { background: #fbfdff; }
.print-signature-line { border-bottom-color: #63738a; }
.print-eval-line { border-bottom-color: #63738a; }
.print-notes-box {
    border-color: #8998ab; border-radius: 1.3mm; background: #fbfdff;
}
.print-notes-box h2 { margin: -3mm -3mm 2mm; border-radius: 1.1mm 1.1mm 0 0; }
.print-page-footer {
    padding-top: 1.2mm; border-top: 0.6px solid var(--print-line);
    color: #52647c; font-weight: 700;
}

@media print {
    @page {
        size: letter portrait;
        margin: 6mm;
    }

    html, body {
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        color: #111 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    body.sms-app .sms-wrapper {
        opacity: 1 !important;
        visibility: visible !important;
    }

    body:has(.crad-print-sheet) .sms-page-loader,
    body:has(.crad-print-sheet) .sms-sidebar,
    body:has(.crad-print-sheet) .sms-navbar,
    body:has(.crad-print-sheet) .sidebar-overlay,
    body:has(.crad-print-sheet) .sidebar-toggle,
    body:has(.crad-print-sheet) .breadcrumb,
    body:has(.crad-print-sheet) nav[aria-label="breadcrumb"],
    body:has(.crad-print-sheet) .module-page-banner,
    body:has(.crad-print-sheet) .sms-footer,
    body:has(.crad-print-sheet) .crad-print-actions,
    body:has(.crad-print-sheet) .crad-below-sheet,
    body:has(.crad-print-sheet) .crad-returned-note,
    body:has(.crad-print-sheet) .sms-main > :not(.student-portal),
    body:has(.crad-print-sheet) .student-portal > :not(.crad-print-preview) {
        display: none !important;
    }

    body:has(.crad-print-sheet) .sms-wrapper,
    body:has(.crad-print-sheet) .sms-content,
    body:has(.crad-print-sheet) .sms-main,
    body:has(.crad-print-sheet) .student-portal,
    body:has(.crad-print-sheet) .crad-title-form-wrap {
        display: block !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        border: 0 !important;
        box-shadow: none !important;
        overflow: visible !important;
    }

    body:has(.crad-print-sheet) .crad-print-preview {
        display: block !important;
        position: static !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: #fff !important;
        box-shadow: none !important;
    }

    body:has(.crad-print-sheet) .crad-print-sheet {
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        max-width: none !important;
        min-height: 267mm !important;
        height: 267mm !important;
        max-height: 267mm !important;
        margin: 0 !important;
        padding: 4mm 5mm 8mm !important;
        gap: 1.55mm !important;
        background: #fff !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        overflow: visible !important;
        page-break-after: auto;
        break-after: auto;
        page-break-inside: avoid;
        break-inside: avoid;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    body:has(.crad-print-sheet) .crad-print-sheet:last-of-type {
        page-break-after: auto;
        break-after: auto;
    }

    body:has(.crad-print-sheet) .crad-print-sheet::before {
        right: 0;
        top: 3mm;
    }

    body:has(.crad-print-sheet) .print-page-footer {
        position: static !important;
        right: auto !important;
        bottom: auto !important;
        left: auto !important;
        margin: auto 0 0 !important;
        width: 100% !important;
    }

    body:has(.crad-print-sheet) .print-document-header {
        grid-template-columns: 14mm 1fr auto !important;
        gap: 2.8mm !important;
        padding-bottom: 1.8mm !important;
    }

    body:has(.crad-print-sheet) .print-bcp-logo-wrap {
        width: 13mm !important;
        height: 13mm !important;
    }

    body:has(.crad-print-sheet) .print-bcp-logo {
        width: 11.5mm !important;
        max-width: 11.5mm !important;
    }

    body:has(.crad-print-sheet) .print-school-name {
        gap: 0.35mm !important;
        font-size: 6.8pt !important;
        line-height: 1.12 !important;
    }

    body:has(.crad-print-sheet) .print-school-name strong { font-size: 9pt !important; }
    body:has(.crad-print-sheet) .print-school-name b { font-size: 7pt !important; }
    body:has(.crad-print-sheet) .print-form-code { padding: 1.1mm 1.7mm !important; font-size: 6.8pt !important; }
    body:has(.crad-print-sheet) .print-document-title { font-size: 14pt !important; margin-bottom: 1mm !important; }
    body:has(.crad-print-sheet) .print-document-title::after { margin-top: 0.8mm !important; height: 0.65mm !important; }
    body:has(.crad-print-sheet) .print-meta { padding: 1.7mm 2.4mm !important; font-size: 7.8pt !important; }
    body:has(.crad-print-sheet) .print-meta span { min-width: 34mm !important; padding-bottom: 0.25mm !important; }
    body:has(.crad-print-sheet) .print-section h2,
    body:has(.crad-print-sheet) .print-response-section h2,
    body:has(.crad-print-sheet) .print-choice-box h2 {
        font-size: 7.6pt !important;
        margin-bottom: 1mm !important;
        padding: 0.95mm 1.6mm !important;
    }
    body:has(.crad-print-sheet) .print-table { font-size: 6.7pt !important; }
    body:has(.crad-print-sheet) .print-table th,
    body:has(.crad-print-sheet) .print-table td { padding: 0.78mm 1mm !important; }
    body:has(.crad-print-sheet) .print-student-table td { height: 4.45mm !important; }
    body:has(.crad-print-sheet) .print-two-column { gap: 2mm !important; align-items: start !important; flex: 0 0 auto !important; }
    body:has(.crad-print-sheet) .print-choice-box { padding: 1.7mm !important; font-size: 6.8pt !important; }
    body:has(.crad-print-sheet) .print-choice-box h2 { margin: -1.7mm -1.7mm 1mm !important; }
    body:has(.crad-print-sheet) .print-choice-box > div { margin: 0.25mm 0 !important; padding: 0.28mm 0.65mm !important; line-height: 1.16 !important; }
    body:has(.crad-print-sheet) .print-sdg-box > p { margin-bottom: 0.6mm !important; font-size: 6.2pt !important; }
    body:has(.crad-print-sheet) .print-sdg-list { row-gap: 0 !important; }
    body:has(.crad-print-sheet) .print-agenda-list { row-gap: 0 !important; }
    body:has(.crad-print-sheet) .print-response {
        min-height: 6.2mm !important;
        padding: 1.45mm 2.5mm !important;
        font-size: 8.8pt !important;
    }
    body:has(.crad-print-sheet) .print-response-small {
        min-height: 7mm !important;
        font-size: 7.2pt !important;
    }
    body:has(.crad-print-sheet) .print-approval-row {
        grid-template-columns: 1.05fr 0.95fr !important;
        align-items: start !important;
    }
    body:has(.crad-print-sheet) .print-approval-row > .print-choice-box:first-child {
        align-self: start !important;
        min-height: 0 !important;
        height: auto !important;
        padding-bottom: 1.5mm !important;
    }
    body:has(.crad-print-sheet) .print-approval-row > .print-choice-box:first-child .print-table {
        margin-bottom: 0 !important;
    }
    body:has(.crad-print-sheet) .print-sig-wrap {
        height: 25pt !important;
        margin: 1.3mm auto 0.45mm !important;
    }
    body:has(.crad-print-sheet) .print-adviser-sig-img {
        height: 24pt !important;
    }
    body:has(.crad-print-sheet) .print-approval-block {
        gap: 0.15mm !important;
        margin: 0.65mm 0 0.9mm !important;
    }
    body:has(.crad-print-sheet) .print-approver-name { font-size: 7.2pt !important; }
    body:has(.crad-print-sheet) .print-approver-role { font-size: 6.3pt !important; }
    body:has(.crad-print-sheet) .print-approval-divider { margin: 0.4mm auto 0.65mm !important; }
    body:has(.crad-print-sheet) .print-received-block small { font-size: 6.2pt !important; margin-bottom: 0 !important; }
    body:has(.crad-print-sheet) .print-page-footer { font-size: 6.4pt !important; padding-top: 0.7mm !important; }

    body:has(.crad-print-sheet) .print-choice-box h2,
    body:has(.crad-print-sheet) .print-section h2,
    body:has(.crad-print-sheet) .print-response-section h2,
    body:has(.crad-print-sheet) .print-notes-box h2,
    body:has(.crad-print-sheet) .print-table th,
    body:has(.crad-print-sheet) .print-choice-box .is-selected,
    body:has(.crad-print-sheet) .print-meta,
    body:has(.crad-print-sheet) .print-form-code,
    body:has(.crad-print-sheet) .print-response {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
@media (max-width: 991.98px) {
    .crad-field-row, .crad-sdg-grid, .crad-radio-grid, .crad-sdg-tiles { grid-template-columns: 1fr; }
}
@media (max-width: 767.98px) {
    .crad-form-header, .crad-section-head, .crad-form-footer { flex-direction: column; align-items: stretch; }
    .crad-btn, .crad-link-btn { width: 100%; justify-content: center; }
}

/* Light mode support */
[data-theme="light"] .crad-title-form {
    --crad-bg: #ffffff;
    --crad-panel: #ffffff;
    --crad-panel-2: #f8fafc;
    --crad-border: #d7e1ef;
    --crad-text: #0f172a;
    --crad-muted: #64748b;
    --crad-blue: #1e40af;
    --crad-blue-soft: rgba(30,64,175,0.12);
    background: #ffffff;
    border-color: #dbe3ef;
    box-shadow: 0 10px 28px rgba(15,33,88,0.08);
    color: var(--crad-text);
}
[data-theme="light"] .crad-form-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #1d4ed8 100%);
}
[data-theme="light"] .crad-stepper {
    background: #f8fafc;
    border-bottom-color: #e2e8f0;
}
[data-theme="light"] .crad-step-icon {
    background: #e2e8f0;
    color: #64748b;
}
[data-theme="light"] .crad-step.is-active { color: #1d4ed8; }
[data-theme="light"] .crad-step.is-complete { color: #15803d; }
[data-theme="light"] .crad-step-line { background: #dbe3ef; }
[data-theme="light"] .crad-btn-ghost {
    color: #fff;
    background: rgba(255,255,255,0.14);
    border-color: rgba(255,255,255,0.45);
}
[data-theme="light"] .crad-btn-secondary {
    color: #334155;
    border-color: #cbd5e1;
    background: #fff;
}
[data-theme="light"] .crad-btn-secondary:hover:not(:disabled) {
    background: #f1f5f9;
}
[data-theme="light"] .crad-section-head h2,
[data-theme="light"] .crad-align-head h2,
[data-theme="light"] .crad-member-card h3 {
    color: #0f172a;
}
[data-theme="light"] .crad-field input,
[data-theme="light"] .crad-field select,
[data-theme="light"] .crad-field textarea {
    background: #fff;
    color: #0f172a;
    border-color: #d7e1ef;
    color-scheme: light;
}
[data-theme="light"] .crad-member-card,
[data-theme="light"] .crad-radio-card,
[data-theme="light"] .crad-sdg-tile {
    background: #fff;
    border-color: #d7e1ef;
}
[data-theme="light"] .crad-radio-card span,
[data-theme="light"] .crad-sdg-title {
    color: #0f172a;
}
[data-theme="light"] .crad-sdg-num { color: #64748b; }
[data-theme="light"] .crad-radio-card:has(input:checked) {
    background: #eff6ff;
    border-color: #3b82f6;
}
[data-theme="light"] .crad-sdg-tile.is-selected,
[data-theme="light"] .crad-sdg-tile:has(input:checked) {
    background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
}
[data-theme="light"] .crad-sdg-tile.is-selected .crad-sdg-num,
[data-theme="light"] .crad-sdg-tile:has(input:checked) .crad-sdg-num,
[data-theme="light"] .crad-sdg-tile.is-selected .crad-sdg-title,
[data-theme="light"] .crad-sdg-tile:has(input:checked) .crad-sdg-title {
    color: #fff;
}
[data-theme="light"] .crad-link-btn { color: #1d4ed8; }
[data-theme="light"] .crad-form-footer {
    border-top-color: #e2e8f0;
    background: #f8fafc;
}
[data-theme="light"] .crad-verify-box {
    background: #eff6ff;
    border-color: #bfdbfe;
}
[data-theme="light"] .crad-verify-box h3 { color: #1e3a8a; }
[data-theme="light"] .crad-verify-box p { color: #334155; }
[data-theme="light"] .crad-print-preview {
    background: #eef2f9;
}
</style>

<script>
(function () {
    var currentStep = 1;
    var totalSteps = 3;
    var maxMembers = 6;
    var form = document.getElementById('cradTitleForm');
    if (!form) {
        // Submitted preview page — do not auto-open print dialog.
        return;
    }
    var memberList = document.getElementById('memberList');
    var rgMembersLocked = memberList && memberList.getAttribute('data-rg-locked') === '1';
    var addMemberBtn = document.getElementById('addMemberBtn');
    var backBtn = document.getElementById('cradBackBtn');
    var nextBtn = document.getElementById('cradNextBtn');
    var processField = document.getElementById('cradProcessField');

    function memberCount() {
        return memberList.querySelectorAll('.crad-member-card').length;
    }

    function renumberMembers() {
        memberList.querySelectorAll('.crad-member-card').forEach(function (card, index) {
            var n = index + 1;
            card.setAttribute('data-member', String(n));
            var title = card.querySelector('h3');
            title.innerHTML = '<span>' + n + '</span> Group Member Profile';
            if (n > 1) {
                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'crad-remove';
                remove.textContent = 'Remove';
                remove.addEventListener('click', function () {
                    card.remove();
                    renumberMembers();
                    if (addMemberBtn) addMemberBtn.disabled = memberCount() >= maxMembers;
                });
                title.appendChild(remove);
            }
        });
        if (addMemberBtn) addMemberBtn.disabled = memberCount() >= maxMembers;
    }



    function createMemberCard(n) {
        var card = document.createElement('article');
        card.className = 'crad-member-card';
        card.setAttribute('data-member', String(n));
        card.innerHTML =
            '<h3><span>' + n + '</span> Group Member Profile</h3>' +
            '<div class="crad-field-row">' +
            '  <div class="crad-field"><label>Full Name (Last, First, M.I.) <span>*</span></label><input type="text" name="member_name[]" placeholder="Abejuela, John Kenneth C." required></div>' +
            '  <div class="crad-field"><label>Section <span>*</span></label><input type="text" name="member_section[]" required></div>' +
            '</div>';
        return card;
    }

    function validateStep(step) {
        var panel = form.querySelector('[data-step-panel="' + step + '"]');
        var fields = panel.querySelectorAll('input[required], select[required], textarea[required]');
        for (var i = 0; i < fields.length; i++) {
            if (!fields[i].checkValidity()) {
                fields[i].reportValidity();
                return false;
            }
        }
        if (step === 2) {
            if (!panel.querySelector('input[name="discipline_cluster"]:checked')) {
                alert('Please select a Research Discipline Cluster.');
                return false;
            }
            var agenda = panel.querySelector('input[name="research_agenda"]:checked');
            if (!agenda) {
                alert('Please select an Institutional Research Agenda.');
                return false;
            }
            if (agenda.value.indexOf('Others') === 0) {
                var others = document.getElementById('agendaOthers');
                if (!others.value.trim()) {
                    others.focus();
                    alert('Please specify the other institutional research agenda.');
                    return false;
                }
            }
            if (!panel.querySelector('input[name="primary_sdg"]:checked')) {
                alert('Please select one primary SDG.');
                return false;
            }
        }
        return true;
    }

    function renderStep() {
        form.querySelectorAll('[data-step-panel]').forEach(function (panel) {
            var step = Number(panel.getAttribute('data-step-panel'));
            var active = step === currentStep;
            panel.hidden = !active;
            panel.classList.toggle('is-active', active);
        });

        form.querySelectorAll('[data-step-indicator]').forEach(function (item) {
            var step = Number(item.getAttribute('data-step-indicator'));
            item.classList.toggle('is-active', step === currentStep);
            item.classList.toggle('is-complete', step < currentStep);

            var icon = item.querySelector('.crad-step-icon');
            if (step < currentStep) {
                icon.innerHTML = '<?= smsIcon('check') ?>';
            } else if (step === currentStep) {
                icon.innerHTML = step === 3 ? '3' : '<?= smsIcon('check') ?>';
            } else if (step === 2) {
                icon.innerHTML = '<?= smsIcon('check') ?>';
            } else {
                icon.textContent = String(step);
            }
        });

        backBtn.disabled = currentStep === 1;
        nextBtn.classList.toggle('crad-btn-primary', currentStep !== totalSteps);
        nextBtn.classList.toggle('crad-btn-success', currentStep === totalSteps);
        nextBtn.textContent = currentStep === totalSteps ? 'Submit Proposed Title' : 'Next Step →';
    }

    if (addMemberBtn) addMemberBtn.addEventListener('click', function () {
      if (rgMembersLocked) return;
        if (memberCount() >= maxMembers) return;
        memberList.appendChild(createMemberCard(memberCount() + 1));
        renumberMembers();
    });

    backBtn.addEventListener('click', function () {
        if (currentStep > 1) {
            currentStep -= 1;
            renderStep();
        }
    });

    nextBtn.addEventListener('click', function () {
        if (!validateStep(currentStep)) return;
        if (currentStep < totalSteps) {
            currentStep += 1;
            renderStep();
            return;
        }
        processField.disabled = false;
        form.submit();
    });

    renumberMembers();
    renderStep();

    var agendaOthersWrap = document.getElementById('agendaOthersWrap');
    var agendaOthers = document.getElementById('agendaOthers');
    form.querySelectorAll('input[name="research_agenda"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var isOthers = radio.value.indexOf('Others') === 0 && radio.checked;
            agendaOthersWrap.hidden = !isOthers;
            agendaOthers.required = isOthers;
            if (!isOthers) agendaOthers.value = '';
        });
    });

    form.querySelectorAll('input[name="primary_sdg"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            form.querySelectorAll('.crad-sdg-tile').forEach(function (tile) {
                tile.classList.toggle('is-selected', tile.querySelector('input').checked);
            });
        });
    });

    var proposedTitle = document.getElementById('proposedTitle');
    proposedTitle.addEventListener('input', function () {
        var start = proposedTitle.selectionStart;
        var end = proposedTitle.selectionEnd;
        proposedTitle.value = proposedTitle.value.toUpperCase();
        proposedTitle.setSelectionRange(start, end);
    });
})();
</script>

<script>
/* Send to Adviser — wires up the post-sheet button */
(function () {
    var btn = document.getElementById('sendToAdviserBtn');
    if (!btn) return;

    /* Create a notice element below the button */
    var notice = document.createElement('div');
    notice.id = 'sendAdviserNotice';
    notice.style.cssText = [
        'width:210mm', 'max-width:100%', 'margin:.6rem auto 0',
        'padding:.7rem 1rem', 'border-radius:10px',
        'font-size:.88rem', 'font-weight:700', 'display:none'
    ].join(';');
    btn.closest('.crad-below-sheet').insertAdjacentElement('afterend', notice);

    function showNotice(msg, type) {
        var isError = type === 'error';
        notice.style.background   = isError ? '#fef2f2' : '#f0fdf4';
        notice.style.color        = isError ? '#991b1b' : '#14532d';
        notice.style.border       = '1px solid ' + (isError ? '#fecaca' : '#86efac');
        notice.innerHTML = (isError ? '<?= smsIcon('exclamation-circle', ['style' => 'margin-right:.4rem']) ?>' : '<?= smsIcon('check-circle', ['style' => 'margin-right:.4rem']) ?>') + msg;
        notice.style.display = 'block';
    }

    function hasApprovalNames() {
        var adviser = String(btn.dataset.adviser || '').trim();
        var coordinator = String(btn.dataset.coordinator || '').trim();
        return adviser !== '' && coordinator !== '';
    }

    function syncSendButton() {
        if (btn.classList.contains('is-sent') || btn.dataset.alreadySent === '1') {
            return;
        }
        var ready = hasApprovalNames();
        btn.disabled = !ready;
        btn.classList.toggle('is-waiting-names', !ready);
        var waitNote = document.getElementById('tafWaitingNamesNote');
        if (waitNote) waitNote.hidden = ready;
    }
    window.tafSyncSendButton = syncSendButton;
    syncSendButton();

    btn.addEventListener('click', function () {
        if (btn.disabled || btn.classList.contains('is-sent') || btn.classList.contains('is-waiting-names')) return;
        if (!hasApprovalNames()) {
            showNotice('Sending is not available yet. Research Adviser and Research Coordinator names are still blank in Section IX.', 'error');
            syncSendButton();
            return;
        }

        btn.disabled = true;
        notice.style.display = 'none';
        var icon = btn.querySelector('.crad-btn-send-icon i');
        var text = btn.querySelector('.crad-btn-send-text');
        if (icon) icon.className = 'fas fa-spinner fa-spin';
        if (text) text.textContent = 'Sending\u2026';

        var payload = {
            submission_id:    btn.dataset.submissionId || 0,
            student_id:       btn.dataset.studentId,
            student_user_id:  btn.dataset.studentUserId || null,
            student_name:     btn.dataset.studentName,
            adviser_name:     btn.dataset.adviser,
            adviser_email:    btn.dataset.adviserEmail,
            coordinator_name: btn.dataset.coordinator,
            research_title:   btn.dataset.title,
            department:       btn.dataset.dept,
            submission_date:  btn.dataset.date,
            discipline:       btn.dataset.discipline,
            primary_sdg:      btn.dataset.sdg,
            research_agenda:  btn.dataset.agenda,
            justification:    btn.dataset.justification,
            members:          btn.dataset.members
        };

        fetch('<?= BASE_URL ?>/modules/crad/api/send-to-adviser.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            /* Adviser has no account */
            if (!data.ok && data.no_account) {
                if (icon) icon.className = 'fas fa-paper-plane';
                if (text) text.textContent = 'Send to Adviser';
                showNotice('The message cannot be sent because the adviser does not have an account.', 'error');
                syncSendButton();
                return;
            }
            if (!data.ok) throw new Error(data.message || 'Server error');

            /* Already sent */
            if (data.already_sent) {
                btn.classList.add('is-sent');
                if (icon) icon.className = 'fas fa-check';
                if (text) text.textContent = 'Document Packet Sent';
                showNotice('<strong>Document Packet Sent</strong><br>Current status: Document Packet Sent. This status is shown on your dashboard.', 'success');
                return;
            }

            /* Success */
            btn.classList.add('is-sent');
            if (icon) icon.className = 'fas fa-check';
            if (text) text.textContent = 'Document Packet Sent';
            showNotice('<strong>Document Packet Sent</strong><br>Current status: Document Packet Sent. This status is shown on your dashboard.', 'success');

            /* Replace the URL so refresh / back still shows the submitted view
               (PHP will load the data from `crad_title_approvals` — no GET params needed) */
            try {
                var cleanUrl = window.location.pathname + '?process=submit-proposal';
                history.replaceState(null, '', cleanUrl);
            } catch (e) { /* ignore */ }
        })
        .catch(function (err) {
            if (icon) icon.className = 'fas fa-paper-plane';
            if (text) text.textContent = 'Send to Adviser';
            showNotice('Could not send: ' + err.message, 'error');
            syncSendButton();
        });
    });
})();
</script>

<script>
(function () {
    var adviserEl = document.getElementById('tafAdviserName');
    var coordEl = document.getElementById('tafCoordinatorName');
    var sendBtn = document.getElementById('sendToAdviserBtn');
    if (!adviserEl && !coordEl && !sendBtn) return;

    var endpoint = <?= json_encode(BASE_URL . '/modules/student-portal/pages/research-proposal-submission.php?ajax=assignees') ?>;

    function applyNames(data) {
        if (!data || !data.ok) return;
        var adviserName = String(data.adviser_name || '').trim();
        var adviserEmail = String(data.adviser_email || '').trim();
        var coordName = String(data.coordinator_name || '').trim();
        if (adviserEl) {
            if (adviserEl.textContent !== adviserName) {
                adviserEl.textContent = adviserName;
                adviserEl.classList.remove('is-live-flash');
                void adviserEl.offsetWidth;
                adviserEl.classList.add('is-live-flash');
            }
        }
        if (coordEl) {
            if (coordEl.textContent !== coordName) {
                coordEl.textContent = coordName;
                coordEl.classList.remove('is-live-flash');
                void coordEl.offsetWidth;
                coordEl.classList.add('is-live-flash');
            }
        }
        if (sendBtn) {
            sendBtn.dataset.adviser = adviserName;
            sendBtn.dataset.adviserEmail = adviserEmail;
            sendBtn.dataset.coordinator = coordName;
            if (typeof window.tafSyncSendButton === 'function') {
                window.tafSyncSendButton();
            }
        }
    }

    function poll() {
        fetch(endpoint + '&t=' + Date.now(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
            credentials: 'same-origin',
            cache: 'no-store'
        })
            .then(function (res) { return res.json(); })
            .then(applyNames)
            .catch(function () {});
    }

    poll();
    setInterval(poll, 2000);
})();
</script>

<?php require_once ROOT_PATH . '/includes/layout-end.php'; ?>
