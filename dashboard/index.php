<?php
/**
 * SMS 2 - Dashboard
 */
$pageTitle    = 'Dashboard';
$activeModule = 'dashboard';
$breadcrumbs  = [];

require_once __DIR__ . '/../includes/breadcrumbs.php';
require_once __DIR__ . '/../includes/layout-start.php';

$visibleModules = getVisibleModules($MODULES);
$isStudentPortal = getCurrentUserRoleKey() === 'student';

if ($isStudentPortal):
    require_once ROOT_PATH . '/modules/student-portal/includes/student-profile.php';
    $studentProfile = studentPortalLoadProfile(
        db(),
        (int) ($_SESSION['user_id'] ?? 0),
        (string) ($_SESSION['student_id'] ?? ''),
        getCurrentUserName() ?: 'Student',
        (string) ($_SESSION['user_email'] ?? '')
    );
    $studentId = $studentProfile['student_id'] !== '' ? $studentProfile['student_id'] : (string) ($_SESSION['student_id'] ?? '');
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<div class="student-portal">
    <div class="page-header student-portal-header">
        <div>
            <span class="student-kicker">Student Portal</span>
            <h1>Welcome back, <?= htmlspecialchars(getCurrentUserName()) ?></h1>
            <p>Profile, student ID, schedule, records, subjects, professors, and payments.</p>
        </div>
        <div class="student-term-badge">
            <?= smsIcon('calendar-check') ?>
            <span>SY 2026-2027</span>
        </div>
    </div>

    <section class="academic-notices-panel" aria-labelledby="studentAcademicNoticesTitle">
        <div class="academic-notices-icon" aria-hidden="true"><?= smsIcon('bullhorn') ?></div>
        <div>
            <span class="ai-insight-kicker">Academic notices</span>
            <h2 class="ai-insight-title" id="studentAcademicNoticesTitle">Important reminders</h2>
            <p class="ai-insight-copy">Stay on track with registration, payments, and academic records this term.</p>
            <ul class="ai-insight-list">
                <li>Review your class schedule before the next payment or registration activity.</li>
                <li>Keep your academic records and subject details ready for advising.</li>
            </ul>
            <div class="ai-insight-actions">
                <a class="ai-insight-action" href="#class-schedule"><?= smsIcon('calendar-check', ['aria-hidden' => 'true']) ?> Open schedule</a>
                <a class="ai-insight-action" href="#academic-records"><?= smsIcon('file-lines', ['aria-hidden' => 'true']) ?> View records</a>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4 dashboard-stats">
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card primary" id="student-id">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3"><?= smsIcon('id-card') ?></div>
                    <div>
                        <h6 class="text-muted mb-0 small">Student ID</h6>
                        <h4 class="mb-0 fw-bold"><?= htmlspecialchars($studentId) ?></h4>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card warning" id="account-balance">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3"><?= smsIcon('wallet') ?></div>
                    <div>
                        <h6 class="text-muted mb-0 small">Account Balance</h6>
                        <h4 class="mb-0 fw-bold">PHP 8,450.00</h4>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card success">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3"><?= smsIcon('book-open') ?></div>
                    <div>
                        <h6 class="text-muted mb-0 small">Enrolled Subjects</h6>
                        <h4 class="mb-0 fw-bold">6</h4>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <section class="card stat-card info">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3"><?= smsIcon('chart-line') ?></div>
                    <div>
                        <h6 class="text-muted mb-0 small">GWA</h6>
                        <h4 class="mb-0 fw-bold">1.75</h4>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <section class="card student-profile-card h-100" id="my-profile">
                <div class="card-body">
                    <div class="student-avatar mb-3">
                        <?= smsIcon('user-graduate') ?>
                    </div>
                    <h5 class="fw-semibold mb-1"><?= htmlspecialchars($studentProfile['name']) ?></h5>
                    <p class="text-muted mb-3"><?= htmlspecialchars($studentProfile['program']) ?></p>
                    <div class="student-detail-list">
                        <div><span>Student ID</span><strong><?= htmlspecialchars($studentId) ?></strong></div>
                        <div><span>Year Level</span><strong><?= htmlspecialchars($studentProfile['year_level']) ?></strong></div>
                        <div><span>Section</span><strong><?= htmlspecialchars($studentProfile['section']) ?></strong></div>
                        <div><span>Status</span><strong class="text-success"><?= htmlspecialchars($studentProfile['status']) ?></strong></div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-8">
            <section class="card h-100" id="class-schedule">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">
                        <?= smsIcon('calendar-alt', ['class' => 'text-sms-primary me-2']) ?>Class Schedule
                    </h5>
                    <div class="table-responsive">
                        <table class="table student-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Room</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Web Systems and Technologies</td><td>Mon / Wed</td><td>8:00 AM - 9:30 AM</td><td>Lab 204</td></tr>
                                <tr><td>Database Management</td><td>Tue / Thu</td><td>10:00 AM - 11:30 AM</td><td>Room 302</td></tr>
                                <tr><td>Systems Analysis and Design</td><td>Friday</td><td>1:00 PM - 4:00 PM</td><td>Room 210</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <section class="card h-100" id="academic-records">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">
                        <?= smsIcon('file-alt', ['class' => 'text-sms-primary me-2']) ?>Academic Records
                    </h5>
                    <div class="student-record-grid">
                        <div><span>Current Semester</span><strong>1st Semester</strong></div>
                        <div><span>Completed Units</span><strong>54</strong></div>
                        <div><span>Current Units</span><strong>18</strong></div>
                        <div><span>Academic Standing</span><strong>Good Standing</strong></div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="card h-100" id="subjects-professors">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">
                        <?= smsIcon('chalkboard-teacher', ['class' => 'text-sms-primary me-2']) ?>Subject & Professors
                    </h5>
                    <div class="student-list">
                        <div><strong>Web Systems and Technologies</strong><span>Prof. Maria Santos</span></div>
                        <div><strong>Database Management</strong><span>Prof. Carlo Reyes</span></div>
                        <div><strong>Systems Analysis and Design</strong><span>Prof. Ana Lim</span></div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-12">
            <section class="card" id="payment-history">
                <div class="card-body">
                    <h5 class="card-title fw-semibold mb-3">
                        <?= smsIcon('receipt', ['class' => 'text-sms-primary me-2']) ?>Payment History
                    </h5>
                    <div class="table-responsive">
                        <table class="table student-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reference No.</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Jul 5, 2026</td><td>OR-2026-0018</td><td>Tuition Down Payment</td><td class="text-end">PHP 5,000.00</td><td><span class="badge text-bg-success">Paid</span></td></tr>
                                <tr><td>Jun 20, 2026</td><td>OR-2026-0009</td><td>Registration Fee</td><td class="text-end">PHP 1,500.00</td><td><span class="badge text-bg-success">Paid</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/layout-end.php';
return;
endif;
?>

<?php
$roleKey = getCurrentUserRoleKey();
// Legacy CRAD proposal board disabled — crad_officer uses the glass analytics dashboard below.
if (false):
    $cradProposals = [
        [
            'reference' => 'SUB-2026-001',
            'date' => '2026-06-19',
            'college' => 'College of Computer Studies',
            'sdg' => 'SDG 11: Sustainable Cities and Communities',
            'title' => 'IOT-BASED FLOOD MONITORING AND EARLY WARNING SYSTEM FOR BARANGAY KALIGAYAHAN',
            'team' => 'Santos, Maria E. · Reyes, Mark L.',
            'status' => 'Approved',
            'status_class' => 'approved',
            'discipline' => 'Engineering, Information Technology, and Computing',
            'agenda' => 'Science, Technology, Digital Transformation, and Innovation',
        ],
        [
            'reference' => 'SUB-2026-002',
            'date' => '2026-07-02',
            'college' => 'College of Business Administration',
            'sdg' => 'SDG 8: Decent Work and Economic Growth',
            'title' => 'POST-PANDEMIC MARKETING ADAPTABILITY OF MICRO-ENTERPRISES IN NOVALICHES',
            'team' => 'Aquino, Jose P.',
            'status' => 'Under Review',
            'status_class' => 'review',
            'discipline' => 'Business, Entrepreneurship, Hospitality, and Tourism Management',
            'agenda' => 'Inclusive Economic Development, Entrepreneurship, and Industry Competitiveness',
        ],
    ];
?>

<div class="crad-officer-dashboard">
    <section class="crad-metric-grid" aria-label="Proposal summary">
        <article class="crad-metric-card">
            <div class="crad-metric-icon submitted"><?= smsIcon('file-alt') ?></div>
            <div><span>Submitted Proposals</span><strong>2</strong></div>
        </article>
        <article class="crad-metric-card">
            <div class="crad-metric-icon approved"><?= smsIcon('check-circle') ?></div>
            <div><span>Approved Titles</span><strong>1</strong></div>
        </article>
        <article class="crad-metric-card">
            <div class="crad-metric-icon pending"><?= smsIcon('clock') ?></div>
            <div><span>Pending Review</span><strong>1</strong></div>
        </article>
    </section>

    <section class="crad-proposal-board">
        <header class="crad-board-header">
            <div>
                <span class="crad-board-kicker">CRAD Officer Workspace</span>
                <h1>Research Title Proposals</h1>
                <p>Review submissions, monitor evaluation status, and print registered approval forms.</p>
            </div>
            <label class="crad-search-box">
                <?= smsIcon('search') ?>
                <input type="search" id="cradProposalSearch" placeholder="Search by title, team, or reference..." aria-label="Search proposals">
            </label>
        </header>

        <div class="crad-proposal-list" id="cradProposalList">
            <?php foreach ($cradProposals as $index => $proposal): ?>
                <article class="crad-proposal-item"
                         data-search="<?= htmlspecialchars(strtolower(implode(' ', $proposal))) ?>">
                    <div class="crad-proposal-main">
                        <div class="crad-proposal-meta">
                            <span><?= htmlspecialchars($proposal['reference']) ?></span>
                            <span>Date: <?= htmlspecialchars($proposal['date']) ?></span>
                            <span class="crad-meta-pill college"><?= htmlspecialchars($proposal['college']) ?></span>
                            <span class="crad-meta-pill sdg"><?= smsIcon('circle') ?><?= htmlspecialchars($proposal['sdg']) ?></span>
                        </div>
                        <h2><?= htmlspecialchars($proposal['title']) ?></h2>
                        <p><?= smsIcon('users') ?> Team: <?= htmlspecialchars($proposal['team']) ?></p>
                    </div>
                    <div class="crad-proposal-actions">
                        <span class="crad-status <?= htmlspecialchars($proposal['status_class']) ?>">
                            <?= htmlspecialchars($proposal['status']) ?>
                        </span>
                        <div>
                            <button type="button"
                                    class="crad-assessment-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#cradAssessmentModal"
                                    data-proposal-index="<?= $index ?>">
                                View Assessment
                            </button>
                            <button type="button"
                                    class="crad-icon-btn"
                                    title="Print proposal"
                                    data-print-proposal="<?= $index ?>">
                                <?= smsIcon('print') ?>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <div class="crad-empty-state" id="cradEmptyState" hidden>
                <?= smsIcon('search') ?>
                <strong>No proposals found</strong>
                <span>Try another title, team, or reference number.</span>
            </div>
        </div>
    </section>

    <aside class="crad-policy-notice">
        <div class="crad-policy-icon"><?= smsIcon('info-circle') ?></div>
        <div>
            <h2>Institutional Research Agenda Policy</h2>
            <p>All research titles must align with Bestlink College of the Philippines' institutional research agenda and support one United Nations Sustainable Development Goal. Evaluation requires approval marks on all screening criteria.</p>
        </div>
    </aside>
</div>

<div class="modal fade" id="cradAssessmentModal" tabindex="-1" aria-labelledby="cradAssessmentTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content crad-assessment-modal">
            <div class="modal-header">
                <div>
                    <span class="crad-board-kicker">CRAD Evaluation</span>
                    <h2 class="modal-title fs-5" id="cradAssessmentTitle">Proposal Assessment</h2>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h3 id="cradModalProposalTitle"></h3>
                <div class="crad-modal-meta" id="cradModalProposalMeta"></div>
                <div class="crad-assessment-checklist">
                    <div><?= smsIcon('check-circle') ?><span>Research title is clear, specific, and measurable.</span></div>
                    <div><?= smsIcon('check-circle') ?><span>Study aligns with the selected discipline cluster.</span></div>
                    <div><?= smsIcon('check-circle') ?><span>Institutional research agenda alignment is established.</span></div>
                    <div><?= smsIcon('check-circle') ?><span>Primary SDG contribution is properly justified.</span></div>
                    <div><?= smsIcon('check-circle') ?><span>Initial feasibility, originality, and ethics checks are complete.</span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sms-primary" onclick="window.print()">
                    <?= smsIcon('print', ['class' => 'me-2']) ?>Print Assessment
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.crad-officer-dashboard {
    --crad-bg: #151719;
    --crad-panel: #101214;
    --crad-panel-soft: #181b1e;
    --crad-line: rgba(148,163,184,0.2);
    --crad-text: #f8fafc;
    --crad-muted: #94a3b8;
    min-height: calc(100vh - 130px);
    padding: 1rem;
    border: 1px solid rgba(148,163,184,0.12);
    border-radius: 18px;
    background:
        radial-gradient(circle at 85% 0%, rgba(37,99,235,0.09), transparent 26rem),
        var(--crad-bg);
    color: var(--crad-text);
}
.crad-metric-grid {
    display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 1rem; margin-bottom: 1rem;
}
.crad-metric-card {
    display: flex; align-items: center; gap: 1rem; min-height: 88px; padding: 1rem 1.15rem;
    border: 1px solid var(--crad-line); border-radius: 14px;
    background: linear-gradient(145deg, rgba(20,23,26,0.96), rgba(8,10,12,0.96));
    box-shadow: 0 12px 28px rgba(0,0,0,0.24);
}
.crad-metric-card span {
    display: block; color: var(--crad-muted); font-size: 0.75rem; font-weight: 700; letter-spacing: 0.03em;
}
.crad-metric-card strong { display: block; margin-top: 0.15rem; color: #fff; font-size: 1.65rem; }
.crad-metric-icon {
    width: 46px; height: 46px; display: grid; place-items: center;
    border-radius: 12px; font-size: 1.05rem;
}
.crad-metric-icon.submitted { color: #8da9ff; background: rgba(62,92,190,0.15); }
.crad-metric-icon.approved { color: #34d399; background: rgba(16,185,129,0.13); }
.crad-metric-icon.pending { color: #fbbf24; background: rgba(245,158,11,0.14); }
.crad-proposal-board {
    overflow: hidden; border: 1px solid var(--crad-line); border-radius: 14px;
    background: rgba(12,14,16,0.84); box-shadow: 0 14px 32px rgba(0,0,0,0.2);
}
.crad-board-header {
    display: flex; align-items: center; justify-content: space-between; gap: 1.25rem;
    padding: 1rem 1.15rem; border-bottom: 1px solid var(--crad-line);
}
.crad-board-kicker {
    color: #7fa2ff; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase;
}
.crad-board-header h1 { margin: 0.15rem 0 0; color: #fff; font-size: 1.05rem; font-weight: 800; }
.crad-board-header p { margin: 0.2rem 0 0; color: var(--crad-muted); font-size: 0.78rem; }
.crad-search-box {
    width: min(320px, 100%); display: flex; align-items: center; gap: 0.65rem;
    padding: 0.58rem 0.8rem; border: 1px solid var(--crad-line); border-radius: 9px;
    background: #232629; color: var(--crad-muted);
}
.crad-search-box input {
    width: 100%; border: 0; outline: 0; background: transparent; color: #fff; font-size: 0.8rem;
}
.crad-search-box input::placeholder { color: #7b8491; }
.crad-proposal-item {
    display: grid; grid-template-columns: minmax(0,1fr) auto; gap: 1rem;
    padding: 1rem 1.15rem; border-bottom: 1px solid var(--crad-line);
}
.crad-proposal-item:last-of-type { border-bottom: 0; }
.crad-proposal-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 0.45rem; color: #7f8996; font-size: 0.66rem; }
.crad-meta-pill {
    display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.2rem 0.5rem;
    border-radius: 999px; border: 1px solid rgba(129,140,248,0.25);
}
.crad-meta-pill.college { color: #c4b5fd; background: rgba(109,40,217,0.09); }
.crad-meta-pill.sdg { color: #fbbf24; border-color: rgba(245,158,11,0.22); background: rgba(245,158,11,0.08); }
.crad-meta-pill.sdg i { font-size: 0.35rem; }
.crad-proposal-main h2 {
    margin: 0.55rem 0 0.35rem; color: #f8fafc; font-size: 0.94rem; font-weight: 800; line-height: 1.35;
}
.crad-proposal-main p { margin: 0; color: #8d98a6; font-size: 0.72rem; }
.crad-proposal-main p i { margin-right: 0.35rem; }
.crad-proposal-actions {
    min-width: 160px; display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between; gap: 0.75rem;
}
.crad-proposal-actions > div { display: flex; align-items: center; gap: 0.45rem; }
.crad-status {
    display: inline-flex; padding: 0.28rem 0.7rem; border-radius: 999px;
    font-size: 0.66rem; font-weight: 800;
}
.crad-status.approved { color: #6ee7b7; border: 1px solid rgba(16,185,129,0.25); background: rgba(16,185,129,0.12); }
.crad-status.review { color: #fbbf24; border: 1px solid rgba(245,158,11,0.25); background: rgba(245,158,11,0.1); }
.crad-assessment-btn, .crad-icon-btn {
    min-height: 34px; padding: 0.4rem 0.75rem; border: 1px solid var(--crad-line); border-radius: 8px;
    background: #22262a; color: #d8dee8; font-size: 0.72rem; font-weight: 700;
}
.crad-assessment-btn:hover, .crad-icon-btn:hover { border-color: #5478df; color: #fff; background: #29334a; }
.crad-icon-btn { width: 34px; padding: 0; color: #8da9ff; }
.crad-policy-notice {
    display: flex; align-items: flex-start; gap: 0.85rem; margin-top: 1rem; padding: 1rem 1.1rem;
    border: 1px solid rgba(245,158,11,0.22); border-radius: 14px;
    background: linear-gradient(90deg, rgba(120,74,8,0.2), rgba(77,46,7,0.1)); color: #f4c768;
}
.crad-policy-icon {
    width: 38px; height: 38px; flex: 0 0 auto; display: grid; place-items: center;
    border-radius: 50%; background: #f59e0b; color: #fff;
}
.crad-policy-notice h2 { margin: 0; color: #fbbf24; font-size: 0.86rem; font-weight: 800; }
.crad-policy-notice p { margin: 0.25rem 0 0; color: #d7b977; font-size: 0.75rem; line-height: 1.45; }
.crad-empty-state {
    display: grid; place-items: center; gap: 0.4rem; padding: 2.5rem; color: var(--crad-muted);
}
.crad-empty-state i { color: #6f86c7; font-size: 1.5rem; }
.crad-empty-state strong { color: #e2e8f0; }
.crad-assessment-modal { border: 1px solid #334155; background: #111827; color: #e2e8f0; }
.crad-assessment-modal .modal-header, .crad-assessment-modal .modal-footer { border-color: #334155; }
.crad-assessment-modal h3 { color: #fff; font-size: 1rem; font-weight: 800; }
.crad-modal-meta { margin-bottom: 1rem; color: #94a3b8; font-size: 0.78rem; }
.crad-assessment-checklist { display: grid; gap: 0.65rem; }
.crad-assessment-checklist div {
    display: flex; align-items: center; gap: 0.7rem; padding: 0.75rem;
    border: 1px solid #273449; border-radius: 9px; background: #172033;
}
.crad-assessment-checklist i { color: #34d399; }
@media (max-width: 767.98px) {
    .crad-metric-grid { grid-template-columns: 1fr; }
    .crad-board-header { align-items: stretch; flex-direction: column; }
    .crad-search-box { width: 100%; }
    .crad-proposal-item { grid-template-columns: 1fr; }
    .crad-proposal-actions { min-width: 0; align-items: flex-start; }
}
</style>

<script>
window.CRADOFFICER_PROPOSALS = <?= json_encode($cradProposals, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
(function () {
    var search = document.getElementById('cradProposalSearch');
    var items = Array.prototype.slice.call(document.querySelectorAll('.crad-proposal-item'));
    var empty = document.getElementById('cradEmptyState');

    search.addEventListener('input', function () {
        var query = search.value.trim().toLowerCase();
        var visible = 0;
        items.forEach(function (item) {
            var match = item.dataset.search.indexOf(query) !== -1;
            item.hidden = !match;
            if (match) visible++;
        });
        empty.hidden = visible !== 0;
    });

    document.querySelectorAll('[data-proposal-index]').forEach(function (button) {
        button.addEventListener('click', function () {
            var proposal = window.CRADOFFICER_PROPOSALS[Number(button.dataset.proposalIndex)];
            document.getElementById('cradModalProposalTitle').textContent = proposal.title;
            document.getElementById('cradModalProposalMeta').textContent =
                proposal.reference + ' · ' + proposal.college + ' · ' + proposal.status;
        });
    });

    document.querySelectorAll('[data-print-proposal]').forEach(function (button) {
        button.addEventListener('click', function () {
            var proposal = window.CRADOFFICER_PROPOSALS[Number(button.dataset.printProposal)];
            document.getElementById('cradModalProposalTitle').textContent = proposal.title;
            document.getElementById('cradModalProposalMeta').textContent =
                proposal.reference + ' · ' + proposal.college + ' · ' + proposal.status;
            window.print();
        });
    });
})();
</script>

<?php
require_once __DIR__ . '/../includes/layout-end.php';
return;
endif;
?>

<?php renderBreadcrumbs($breadcrumbs); ?>

<?php
/* ── Role-aware performance metrics ───────────────────── */
$roleKey   = getCurrentUserRoleKey();
$statCards = [];

if (smsIsGrantedAdminRole($roleKey)) {
    $statCards = [
        ['icon'=>'fa-users-cog',  'label'=>'Managed Accounts', 'value'=>'14', 'type'=>'primary', 'delta'=>'+3', 'deltaDir'=>'up', 'deltaLabel'=>'this month'],
        ['icon'=>'fa-shield-alt', 'label'=>'Secured Modules',  'value'=>(string) max(1, count($visibleModules)), 'type'=>'success', 'delta'=>'Active', 'deltaDir'=>'neutral', 'deltaLabel'=>'role scoped'],
        ['icon'=>'fa-user-check', 'label'=>'Active Sessions',  'value'=>'9', 'type'=>'info', 'delta'=>'+2', 'deltaDir'=>'up', 'deltaLabel'=>'today'],
        ['icon'=>'fa-history',    'label'=>'Audit Events',     'value'=>'186', 'type'=>'warning', 'delta'=>'+18', 'deltaDir'=>'up', 'deltaLabel'=>'this week'],
    ];
} elseif ($roleKey === 'admission') {
    $statCards = [
        ['icon'=>'fa-file-signature',  'label'=>'Pre-registrations',    'value'=>'86', 'type'=>'primary', 'delta'=>'+12', 'deltaDir'=>'up', 'deltaLabel'=>'this week'],
        ['icon'=>'fa-cloud-upload-alt','label'=>'Docs for Checking',    'value'=>'31', 'type'=>'warning', 'delta'=>'-6', 'deltaDir'=>'down', 'deltaLabel'=>'vs yesterday'],
        ['icon'=>'fa-user-check',      'label'=>'Validated Applicants', 'value'=>'54', 'type'=>'success', 'delta'=>'+9', 'deltaDir'=>'up', 'deltaLabel'=>'this week'],
        ['icon'=>'fa-layer-group',     'label'=>'Section Placements',   'value'=>'28', 'type'=>'info', 'delta'=>'+4', 'deltaDir'=>'up', 'deltaLabel'=>'today'],
    ];
} elseif ($roleKey === 'registrar') {
    $statCards = [
        ['icon'=>'fa-user-graduate', 'label'=>'Total Students',      'value'=>'2,893', 'type'=>'primary', 'delta'=>'+3.8%', 'deltaDir'=>'up',   'deltaLabel'=>'vs last month'],
        ['icon'=>'fa-file-alt',      'label'=>'Pending Enrollments', 'value'=>'47',    'type'=>'warning', 'delta'=>'-5.4%', 'deltaDir'=>'down', 'deltaLabel'=>'vs last week'],
        ['icon'=>'fa-folder-open',   'label'=>'Document Requests',   'value'=>'28',    'type'=>'info',    'delta'=>'+9.2%', 'deltaDir'=>'up',   'deltaLabel'=>'vs last week'],
        ['icon'=>'fa-check-circle',  'label'=>'Enrolled This Term',  'value'=>'1,204', 'type'=>'success', 'delta'=>'+6.1%', 'deltaDir'=>'up',   'deltaLabel'=>'vs last term'],
    ];
} elseif ($roleKey === 'finance') {
    $statCards = [
        ['icon'=>'fa-peso-sign',           'label'=>'Collections Today',       'value'=>'₱38,500', 'type'=>'success', 'delta'=>'+12.5%', 'deltaDir'=>'up',   'deltaLabel'=>'vs yesterday'],
        ['icon'=>'fa-file-invoice-dollar', 'label'=>'Pending Payments',        'value'=>'134',     'type'=>'warning', 'delta'=>'-3.2%',  'deltaDir'=>'down', 'deltaLabel'=>'vs last week'],
        ['icon'=>'fa-wallet',              'label'=>'Total Collected (Month)', 'value'=>'₱1.2M',   'type'=>'primary', 'delta'=>'+8.4%',  'deltaDir'=>'up',   'deltaLabel'=>'vs last month'],
        ['icon'=>'fa-times-circle',        'label'=>'Overdue Accounts',        'value'=>'23',      'type'=>'info',    'delta'=>'-1.5%',  'deltaDir'=>'down', 'deltaLabel'=>'vs last week'],
    ];
} elseif ($roleKey === 'hr') {
    $statCards = [
        ['icon'=>'fa-chalkboard-teacher', 'label'=>'Total Faculty',          'value'=>'102', 'type'=>'primary', 'delta'=>'+1.0%', 'deltaDir'=>'up',   'deltaLabel'=>'vs last month'],
        ['icon'=>'fa-calendar-times',     'label'=>'On Leave Today',         'value'=>'5',   'type'=>'warning', 'delta'=>'+2',    'deltaDir'=>'up',   'deltaLabel'=>'vs yesterday'],
        ['icon'=>'fa-star',               'label'=>'Avg Evaluation Score',   'value'=>'4.2', 'type'=>'success', 'delta'=>'+0.1',  'deltaDir'=>'up',   'deltaLabel'=>'vs last term'],
        ['icon'=>'fa-clock',              'label'=>'Pending Leave Requests', 'value'=>'8',   'type'=>'info',    'delta'=>'-3',    'deltaDir'=>'down', 'deltaLabel'=>'vs last week'],
    ];
} elseif ($roleKey === 'it_office') {
    $statCards = [
        ['icon'=>'fa-laptop',     'label'=>'Active Classes (LMS)',  'value'=>'148',   'type'=>'primary', 'delta'=>'+5.6%', 'deltaDir'=>'up',   'deltaLabel'=>'vs last week'],
        ['icon'=>'fa-users',      'label'=>'Active LMS Users',      'value'=>'2,104', 'type'=>'success', 'delta'=>'+7.3%', 'deltaDir'=>'up',   'deltaLabel'=>'vs last month'],
        ['icon'=>'fa-tasks',      'label'=>'Pending Submissions',   'value'=>'312',   'type'=>'warning', 'delta'=>'-4.1%', 'deltaDir'=>'down', 'deltaLabel'=>'vs last week'],
        ['icon'=>'fa-chart-line', 'label'=>'Avg Module Completion', 'value'=>'68%',   'type'=>'info',    'delta'=>'+2.4%', 'deltaDir'=>'up',   'deltaLabel'=>'vs last month'],
    ];
} elseif ($roleKey === 'osa') {
    $statCards = [
        ['icon'=>'fa-users',            'label'=>'Registered Clubs',  'value'=>'24',  'type'=>'primary', 'delta'=>'+1',    'deltaDir'=>'up',   'deltaLabel'=>'new this month'],
        ['icon'=>'fa-calendar-check',   'label'=>'Events This Month', 'value'=>'9',   'type'=>'success', 'delta'=>'+3',    'deltaDir'=>'up',   'deltaLabel'=>'vs last month'],
        ['icon'=>'fa-user-check',       'label'=>'Active Members',    'value'=>'876', 'type'=>'info',    'delta'=>'+4.8%', 'deltaDir'=>'up',   'deltaLabel'=>'vs last month'],
        ['icon'=>'fa-hand-holding-usd', 'label'=>'Budget Requests',   'value'=>'6',   'type'=>'warning', 'delta'=>'-2',    'deltaDir'=>'down', 'deltaLabel'=>'vs last week'],
    ];
} elseif ($roleKey === 'qa') {
    $statCards = [
        ['icon'=>'fa-award',              'label'=>'Accredited Programs', 'value'=>'8',        'type'=>'success', 'delta'=>'+1',    'deltaDir'=>'up',   'deltaLabel'=>'this year'],
        ['icon'=>'fa-clipboard-list',     'label'=>'Compliance Items',    'value'=>'142',      'type'=>'primary', 'delta'=>'+6.2%', 'deltaDir'=>'up',   'deltaLabel'=>'vs last audit'],
        ['icon'=>'fa-exclamation-circle', 'label'=>'Non-Conformities',    'value'=>'7',        'type'=>'warning', 'delta'=>'-2',    'deltaDir'=>'down', 'deltaLabel'=>'vs last review'],
        ['icon'=>'fa-calendar-alt',       'label'=>'Next Visit',          'value'=>'Sep 2026', 'type'=>'info',    'delta'=>'On track', 'deltaDir'=>'neutral', 'deltaLabel'=>'schedule'],
    ];
} elseif ($roleKey === 'crad_officer') {
    $grantDashboardMetrics = [
        'total_grant_calls' => 0, 'submitted_proposals' => 0, 'under_review' => 0,
        'revision_required' => 0, 'rejected_proposals' => 0, 'approved_funded_projects' => 0,
        'total_funding' => 0.0, 'ongoing_research' => 0, 'completed_research' => 0,
        'publications' => 0, 'ip_records' => 0, 'updated_at' => '',
    ];
    $grantDashStats = ['submitted' => 0, 'in_progress' => 0, 'completed' => 0, 'committee_scored' => 0];
    try {
        require_once ROOT_PATH . '/modules/crad/config/config.php';
        require_once ROOT_PATH . '/modules/crad/includes/grant-helpers.php';
        require_once ROOT_PATH . '/modules/crad/includes/grant-approval-helpers.php';
        $cradDb = cradDb();
        if ($cradDb) {
            $grantDashboardMetrics = grantGetDashboardMetrics($cradDb);
            $grantDashStats = grantApprovalDashboardStats($cradDb);
        }
    } catch (Throwable $e) {
        error_log('dashboard crad_officer grant stats: ' . $e->getMessage());
    }
    $statCards = [
        ['icon'=>'fa-bullhorn',         'label'=>'Total Grant Calls',          'value'=>grantFormatDashboardMetricValue('total_grant_calls', $grantDashboardMetrics),         'metricKey'=>'total_grant_calls',        'type'=>'primary', 'delta'=>'Live', 'deltaDir'=>'neutral', 'deltaLabel'=>'from CRAD database'],
        ['icon'=>'fa-file-alt',         'label'=>'Submitted Proposals',        'value'=>grantFormatDashboardMetricValue('submitted_proposals', $grantDashboardMetrics),       'metricKey'=>'submitted_proposals',      'type'=>'info',    'delta'=>'Live', 'deltaDir'=>'neutral', 'deltaLabel'=>'all applications'],
        ['icon'=>'fa-check-circle',     'label'=>'Approved & Funded',        'value'=>grantFormatDashboardMetricValue('approved_funded_projects', $grantDashboardMetrics), 'metricKey'=>'approved_funded_projects', 'type'=>'success', 'delta'=>'Live', 'deltaDir'=>'neutral', 'deltaLabel'=>'active funded projects'],
        ['icon'=>'fa-peso-sign',        'label'=>'Total Funding Released',   'value'=>grantFormatDashboardMetricValue('total_funding', $grantDashboardMetrics),            'metricKey'=>'total_funding',            'type'=>'warning', 'delta'=>'Live', 'deltaDir'=>'neutral', 'deltaLabel'=>'disbursements'],
    ];
} elseif ($roleKey === 'research_coordinator') {
    $statCards = [
        ['icon'=>'fa-check-square', 'label'=>'Approved Research',    'value'=>'18', 'type'=>'success', 'delta'=>'+3', 'deltaDir'=>'up', 'deltaLabel'=>'this month'],
        ['icon'=>'fa-user-tie',     'label'=>'Adviser Matches',      'value'=>'12', 'type'=>'primary', 'delta'=>'+4', 'deltaDir'=>'up', 'deltaLabel'=>'assigned'],
        ['icon'=>'fa-envelope',     'label'=>'Coordination Notices', 'value'=>'27', 'type'=>'info',    'delta'=>'+8', 'deltaDir'=>'up', 'deltaLabel'=>'sent'],
        ['icon'=>'fa-tasks',        'label'=>'Open Assignments',     'value'=>'6',  'type'=>'warning', 'delta'=>'-2', 'deltaDir'=>'down', 'deltaLabel'=>'pending'],
    ];
} elseif ($roleKey === 'research_grant') {
    $statCards = [
        ['icon'=>'fa-hand-holding-usd', 'label'=>'Grant Applications', 'value'=>'16',       'type'=>'primary', 'delta'=>'+5',   'deltaDir'=>'up', 'deltaLabel'=>'this month'],
        ['icon'=>'fa-search-dollar',    'label'=>'For Evaluation',     'value'=>'7',        'type'=>'warning', 'delta'=>'+2',   'deltaDir'=>'up', 'deltaLabel'=>'new'],
        ['icon'=>'fa-check-circle',     'label'=>'Funded Research',    'value'=>'9',        'type'=>'success', 'delta'=>'+3',   'deltaDir'=>'up', 'deltaLabel'=>'approved'],
        ['icon'=>'fa-peso-sign',        'label'=>'Funds Released',     'value'=>'PHP 486K', 'type'=>'info',    'delta'=>'+11%', 'deltaDir'=>'up', 'deltaLabel'=>'vs last month'],
    ];
} elseif (in_array($roleKey, ['adviser', 'panel'], true)) {
    $statCards = [
        ['icon'=>'fa-flask',        'label'=>'Assigned Research',  'value'=>'8',  'type'=>'primary', 'delta'=>'+2', 'deltaDir'=>'up', 'deltaLabel'=>'active'],
        ['icon'=>'fa-calendar',     'label'=>'Defense Schedules',  'value'=>'5',  'type'=>'warning', 'delta'=>'+1', 'deltaDir'=>'up', 'deltaLabel'=>'this week'],
        ['icon'=>'fa-folder-open',  'label'=>'Research Documents', 'value'=>'24', 'type'=>'info',    'delta'=>'+6', 'deltaDir'=>'up', 'deltaLabel'=>'reviewed'],
        ['icon'=>'fa-check-square', 'label'=>'Completed Reviews',  'value'=>'11', 'type'=>'success', 'delta'=>'+3', 'deltaDir'=>'up', 'deltaLabel'=>'this month'],
    ];
} elseif ($roleKey === 'research_director') {
    $statCards = [
        ['icon'=>'fa-check-double',  'label'=>'Defense Ready',      'value'=>'14', 'type'=>'primary', 'delta'=>'+4', 'deltaDir'=>'up', 'deltaLabel'=>'verified'],
        ['icon'=>'fa-calendar-plus', 'label'=>'Proposed Schedules', 'value'=>'9',  'type'=>'warning', 'delta'=>'+2', 'deltaDir'=>'up', 'deltaLabel'=>'pending'],
        ['icon'=>'fa-users',         'label'=>'Panel Assignments',  'value'=>'21', 'type'=>'info',    'delta'=>'+5', 'deltaDir'=>'up', 'deltaLabel'=>'this month'],
        ['icon'=>'fa-archive',       'label'=>'For Archiving',      'value'=>'6',  'type'=>'success', 'delta'=>'+1', 'deltaDir'=>'up', 'deltaLabel'=>'completed'],
    ];
} else {
    $statCards = [
        ['icon'=>'fa-flask',            'label'=>'Active Research Projects', 'value'=>'18', 'type'=>'primary', 'delta'=>'+3',    'deltaDir'=>'up',   'deltaLabel'=>'vs last quarter'],
        ['icon'=>'fa-file-upload',      'label'=>'Submitted Proposals',      'value'=>'12', 'type'=>'info',    'delta'=>'+4',    'deltaDir'=>'up',   'deltaLabel'=>'this month'],
        ['icon'=>'fa-book-open',        'label'=>'Publications This Year',   'value'=>'9',  'type'=>'success', 'delta'=>'+2',    'deltaDir'=>'up',   'deltaLabel'=>'vs last year'],
        ['icon'=>'fa-hand-holding-usd', 'label'=>'Active Grants',            'value'=>'4',  'type'=>'warning', 'delta'=>'Stable', 'deltaDir'=>'neutral', 'deltaLabel'=>'current'],
    ];
}

require_once __DIR__ . '/period-filter.php';
$dashboardPeriodKey    = smsDashboardCurrentPeriod();
$dashboardPeriods      = smsDashboardPeriods();
$dashboardPeriodMeta   = $dashboardPeriods[$dashboardPeriodKey];
$dashboardPeriodFactor = $dashboardPeriodMeta['factor'];
if ($roleKey !== 'crad_officer') {
    $statCards = smsDashboardScaleStatCards($statCards, $dashboardPeriodFactor);
}

require_once __DIR__ . '/glass-board.php';
?>

<script src="<?= BASE_URL ?>/assets/js/dashboard-glass.js"></script>
<?php if ($roleKey === 'crad_officer'): ?>
<link href="<?= BASE_URL ?>/assets/css/grant-approval-workflows.css?v=4" rel="stylesheet">
<script src="<?= BASE_URL ?>/assets/js/crad-approval-dashboard-live.js?v=1"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout-end.php'; ?>
