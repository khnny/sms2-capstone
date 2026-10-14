<?php
/**
 * Research Services Clearance — after Grammarian scores Chapter 1-3,
 * before panel assignment.
 */
declare(strict_types=1);

require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/chapter-evaluation-workflow.php';
require_once ROOT_PATH . '/modules/crad/includes/research-clearance-image.php';
require_once ROOT_PATH . '/modules/crad/includes/research-clearance-payment.php';

function rscDb(): ?PDO
{
    return function_exists('cradDb') ? cradDb() : getCradDatabaseConnection();
}

function rscSmsDb(): ?PDO
{
    return function_exists('db') ? db() : null;
}

function rscLiveAccountName(int $userId = 0, string $email = '', string $roleKey = '', string $fallback = ''): string
{
    $fallback = trim($fallback);
    $sms = rscSmsDb();
    if (!$sms instanceof PDO) {
        return $fallback;
    }
    try {
        if ($userId > 0) {
            $stmt = $sms->prepare(
                "SELECT full_name FROM `sms2_users`
                 WHERE id = ? AND TRIM(COALESCE(full_name, '')) <> ''
                 LIMIT 1"
            );
            $stmt->execute([$userId]);
            $name = trim((string) $stmt->fetchColumn());
            if ($name !== '') {
                return $name;
            }
        }
        $email = strtolower(trim($email));
        if ($email !== '') {
            $stmt = $sms->prepare(
                "SELECT full_name FROM `sms2_users`
                 WHERE LOWER(TRIM(email)) = ? AND TRIM(COALESCE(full_name, '')) <> ''
                 LIMIT 1"
            );
            $stmt->execute([$email]);
            $name = trim((string) $stmt->fetchColumn());
            if ($name !== '') {
                return $name;
            }
        }
        $roleKey = strtolower(trim($roleKey));
        if ($roleKey !== '') {
            $stmt = $sms->prepare(
                "SELECT full_name FROM `sms2_users`
                 WHERE role_key = ? AND TRIM(COALESCE(full_name, '')) <> ''
                   AND (status = 'active' OR status = 1 OR status IS NULL OR status = '')
                 ORDER BY id DESC
                 LIMIT 1"
            );
            $stmt->execute([$roleKey]);
            $name = trim((string) $stmt->fetchColumn());
            if ($name !== '') {
                return $name;
            }
        }
    } catch (Throwable $e) {
        // keep fallback
    }
    return $fallback;
}

function rscEnsureSchema(?PDO $crad = null): void
{
    $crad = $crad ?: rscDb();
    if (!$crad instanceof PDO) {
        return;
    }
    // Schema is deployment-owned by modules/crad/database/crad_db.sql.
    // Do not run CREATE/ALTER TABLE during a web request.
    return;

    // DDL implicitly commits MySQL transactions; skip while a txn is open.
    if ($crad->inTransaction()) {
        return;
    }

    $crad->exec(
        "CREATE TABLE IF NOT EXISTS `crad_research_services_clearances` (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            research_group_id INT UNSIGNED NOT NULL,
            research_stage VARCHAR(20) NOT NULL DEFAULT 'research_1',
            title_approval_id INT UNSIGNED DEFAULT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'draft',
            or_number VARCHAR(80) NOT NULL DEFAULT '',
            leader_student_no VARCHAR(40) NOT NULL DEFAULT '',
            leader_group_no VARCHAR(40) NOT NULL DEFAULT '',
            program VARCHAR(200) NOT NULL DEFAULT '',
            section VARCHAR(80) NOT NULL DEFAULT '',
            research_title VARCHAR(255) NOT NULL DEFAULT '',
            members_json LONGTEXT DEFAULT NULL,
            grammarian_name VARCHAR(160) NOT NULL DEFAULT '',
            statistician_name VARCHAR(160) NOT NULL DEFAULT '',
            adviser_name VARCHAR(160) NOT NULL DEFAULT '',
            adviser_user_id INT UNSIGNED DEFAULT NULL,
            adviser_email VARCHAR(190) NOT NULL DEFAULT '',
            adviser_signature LONGTEXT DEFAULT NULL,
            adviser_signed_at DATETIME DEFAULT NULL,
            crad_name VARCHAR(160) NOT NULL DEFAULT '',
            crad_user_id INT UNSIGNED DEFAULT NULL,
            crad_signature LONGTEXT DEFAULT NULL,
            crad_signed_at DATETIME DEFAULT NULL,
            uploaded_file VARCHAR(255) DEFAULT NULL,
            uploaded_original VARCHAR(255) DEFAULT NULL,
            uploaded_at DATETIME DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_rsc_group_stage (research_group_id, research_stage),
            KEY idx_rsc_status (status),
            KEY idx_rsc_adviser (adviser_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    foreach ([
        'mis_verified' => "ALTER TABLE `crad_research_services_clearances` ADD COLUMN mis_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER uploaded_at",
        'aa_verified' => "ALTER TABLE `crad_research_services_clearances` ADD COLUMN aa_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER mis_verified",
        'mis_verified_at' => "ALTER TABLE `crad_research_services_clearances` ADD COLUMN mis_verified_at DATETIME DEFAULT NULL AFTER aa_verified",
        'aa_verified_at' => "ALTER TABLE `crad_research_services_clearances` ADD COLUMN aa_verified_at DATETIME DEFAULT NULL AFTER mis_verified_at",
        'export_hash' => "ALTER TABLE `crad_research_services_clearances` ADD COLUMN export_hash VARCHAR(64) NOT NULL DEFAULT '' AFTER aa_verified_at",
        'form_verified' => "ALTER TABLE `crad_research_services_clearances` ADD COLUMN form_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER export_hash",
        'mis_signature' => "ALTER TABLE `crad_research_services_clearances` ADD COLUMN mis_signature LONGTEXT DEFAULT NULL AFTER form_verified",
        'aa_signature' => "ALTER TABLE `crad_research_services_clearances` ADD COLUMN aa_signature LONGTEXT DEFAULT NULL AFTER mis_signature",
        'research_stage' => "ALTER TABLE `crad_research_services_clearances` ADD COLUMN research_stage VARCHAR(20) NOT NULL DEFAULT 'research_1' AFTER research_group_id",
        'crad_remarks' => "ALTER TABLE `crad_research_services_clearances` ADD COLUMN crad_remarks VARCHAR(500) NOT NULL DEFAULT '' AFTER crad_signed_at",
    ] as $column => $sql) {
        try {
            if (!$crad->query("SHOW COLUMNS FROM `crad_research_services_clearances` LIKE " . $crad->quote($column))->fetch()) {
                $crad->exec($sql);
            }
        } catch (Throwable $e) {
            error_log('rsc schema column ' . $column . ': ' . $e->getMessage());
        }
    }

    try {
        $crad->exec("UPDATE `crad_research_services_clearances` SET research_stage = 'research_1' WHERE TRIM(COALESCE(research_stage, '')) = ''");
        $indexes = $crad->query("SHOW INDEX FROM `crad_research_services_clearances`")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $hasStageUnique = false;
        foreach ($indexes as $idx) {
            if (($idx['Key_name'] ?? '') === 'uniq_rsc_group_stage') {
                $hasStageUnique = true;
                break;
            }
        }
        if (!$hasStageUnique) {
            try {
                $crad->exec('ALTER TABLE `crad_research_services_clearances` DROP INDEX uniq_rsc_group');
            } catch (Throwable $e) {
                // legacy index name may differ
            }
            $crad->exec(
                'ALTER TABLE `crad_research_services_clearances`
                 ADD UNIQUE KEY uniq_rsc_group_stage (research_group_id, research_stage)'
            );
        }
        try {
            $crad->exec('ALTER TABLE `crad_research_services_clearances` MODIFY or_number VARCHAR(80) NOT NULL DEFAULT \'\'');
        } catch (Throwable $e) {
            // keep existing width if alter fails
        }
    } catch (Throwable $e) {
        error_log('rsc schema stage unique: ' . $e->getMessage());
    }

    $crad->exec(
        "CREATE TABLE IF NOT EXISTS `crad_research_clearance_notifications` (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_key VARCHAR(190) NOT NULL,
            recipient_user_id INT UNSIGNED DEFAULT NULL,
            recipient_role VARCHAR(40) NOT NULL DEFAULT '',
            recipient_email VARCHAR(190) NOT NULL DEFAULT '',
            clearance_id INT UNSIGNED DEFAULT NULL,
            type VARCHAR(40) NOT NULL DEFAULT '',
            title VARCHAR(190) NOT NULL DEFAULT '',
            body TEXT DEFAULT NULL,
            url VARCHAR(255) NOT NULL DEFAULT '',
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_rsc_notif_event (event_key),
            KEY idx_rsc_notif_recipient (recipient_user_id, recipient_role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    rcpEnsureSchema($crad);
    if (function_exists('rcpPurgeDisconnectedPayments')) {
        rcpPurgeDisconnectedPayments($crad);
    }
    rscPurgeDisconnectedClearances($crad);
}

/**
 * Drop clearance rows (and uploads/notifications) when the research group is gone.
 */
function rscPurgeDisconnectedClearances(PDO $crad): int
{
    $orphans = $crad->query(
        "SELECT c.id, c.uploaded_file
         FROM `crad_research_services_clearances` c
         LEFT JOIN `crad_research_groups` rg ON rg.id = c.research_group_id
         WHERE c.research_group_id IS NULL
            OR c.research_group_id < 1
            OR rg.id IS NULL"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($orphans === []) {
        return 0;
    }

    $ids = [];
    foreach ($orphans as $row) {
        $ids[] = (int) ($row['id'] ?? 0);
        $file = basename(str_replace('\\', '/', (string) ($row['uploaded_file'] ?? '')));
        if ($file !== '' && $file !== '.' && $file !== '..') {
            $path = rscUploadedImagePath($file);
            if ($path !== null) {
                @unlink($path);
            }
        }
    }
    $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));
    if ($ids === []) {
        return 0;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    try {
        $crad->prepare(
            "DELETE FROM `crad_research_clearance_notifications`
             WHERE clearance_id IN ($placeholders)"
        )->execute($ids);
    } catch (Throwable $e) {
        // older schema may not have clearance_id
        try {
            $crad->prepare(
                "DELETE FROM `crad_research_clearance_notifications`
                 WHERE event_key LIKE 'clearance:%'
                   AND (" . implode(' OR ', array_map(static fn(int $id): string => "event_key LIKE " . $crad->quote('%:' . $id . ':%'), $ids)) . ")"
            )->execute();
        } catch (Throwable $e2) {
            // ignore
        }
    }
    $stmt = $crad->prepare("DELETE FROM `crad_research_services_clearances` WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    return $stmt->rowCount();
}

function rscNormalizeStage(string $stage): string
{
    return function_exists('rcpNormalizeStage')
        ? rcpNormalizeStage($stage)
        : (strtolower(trim($stage)) === 'research_2' ? 'research_2' : 'research_1');
}

function rscStageLabel(string $stage): string
{
    return function_exists('rcpStageLabel')
        ? rcpStageLabel($stage)
        : (rscNormalizeStage($stage) === 'research_2' ? 'Research 2' : 'Research 1');
}

function rscOrColumnLabel(string $stage): string
{
    return rscNormalizeStage($stage) === 'research_2'
        ? 'Research 2 / Defense O.R. No.'
        : 'Research 1 / Defense O.R. No.';
}

function rscApprovedPayment(PDO $crad, int $groupId, string $stage = 'research_1'): ?array
{
    $row = rcpFindByGroup($crad, $groupId, $stage);
    if (!$row || (string) ($row['status'] ?? '') !== 'approved') {
        return null;
    }
    return $row;
}

/**
 * Approved per-researcher references for one clearance row.
 *
 * The Admin approves one Reference / O.R. number per approved researcher in
 * "Clearance Status", so the student inbox must list the very same names and
 * numbers instead of a single group-level reference.
 *
 * @return array<int, array{name: string, or_number: string}> max 5 entries
 */
function rscApprovedOrList(array $public): array
{
    $fallback = trim((string) ($public['or_number'] ?? ''));
    $out = [];
    foreach ((array) ($public['members'] ?? []) as $member) {
        if (!is_array($member)) {
            continue;
        }
        $name = trim((string) ($member['name'] ?? ''));
        $orNumber = trim((string) ($member['or_number'] ?? '')) ?: $fallback;
        if ($name === '' && $orNumber === '') {
            continue;
        }
        $out[] = ['name' => $name, 'or_number' => $orNumber];
        if (count($out) >= 5) {
            break;
        }
    }
    if ($out === [] && $fallback !== '') {
        $out[] = ['name' => '', 'or_number' => $fallback];
    }
    return $out;
}

function rscPaymentUnlocksClearance(PDO $crad, int $groupId, ?array $clearance = null, string $stage = ''): bool
{
    $stage = rscNormalizeStage($stage !== '' ? $stage : (string) ($clearance['research_stage'] ?? 'research_1'));
    $status = (string) ($clearance['status'] ?? '');
    if (in_array($status, ['sent_to_adviser', 'adviser_signed', 'crad_received', 'clearance_done'], true)) {
        return true;
    }
    return rcpIsApproved($crad, $groupId, $stage);
}

function rscStudentUrl(): string
{
    return BASE_URL . '/modules/student-portal/pages/research-clearance.php';
}

function rscAdviserUrl(?int $id = null): string
{
    $url = BASE_URL . '/modules/faculty/pages/research-clearance.php';
    return $id ? $url . '?id=' . $id : $url;
}

function rscCradUrl(?int $id = null): string
{
    $url = BASE_URL . '/modules/crad/pages/research-clearance.php';
    return $id ? $url . '?id=' . $id : $url;
}

function rscIsChapterReady(PDO $crad, int $groupId): bool
{
    if ($groupId <= 0) {
        return false;
    }
    try {
        $stmt = $crad->prepare(
            "SELECT 1
             FROM `crad_research_groups` rg
             INNER JOIN `crad_chapter_submissions` ch1 ON ch1.id = (
                SELECT cs1.id FROM `crad_chapter_submissions` cs1
                WHERE cs1.research_group_id = rg.id AND cs1.chapter_number = 1
                ORDER BY cs1.version_number DESC, cs1.id DESC LIMIT 1
             )
             INNER JOIN `crad_chapter_evaluations` ce1 ON ce1.submission_id = ch1.id
             INNER JOIN `crad_chapter_submissions` ch2 ON ch2.id = (
                SELECT cs2.id FROM `crad_chapter_submissions` cs2
                WHERE cs2.research_group_id = rg.id AND cs2.chapter_number = 2
                ORDER BY cs2.version_number DESC, cs2.id DESC LIMIT 1
             )
             INNER JOIN `crad_chapter_evaluations` ce2 ON ce2.submission_id = ch2.id
             INNER JOIN `crad_chapter_submissions` ch3 ON ch3.id = (
                SELECT cs3.id FROM `crad_chapter_submissions` cs3
                WHERE cs3.research_group_id = rg.id AND cs3.chapter_number = 3
                ORDER BY cs3.version_number DESC, cs3.id DESC LIMIT 1
             )
             INNER JOIN `crad_chapter_evaluations` ce3 ON ce3.submission_id = ch3.id
             WHERE rg.id = :gid
               AND ch1.status = 'Accepted'
               AND ch2.status = 'Accepted'
               AND ch3.status = 'Accepted'
               AND UPPER(REPLACE(ce1.result, ' ', '_')) IN ('APPROVED', 'APPROVED_WITH_REVISION')
               AND UPPER(REPLACE(ce2.result, ' ', '_')) IN ('APPROVED', 'APPROVED_WITH_REVISION')
               AND UPPER(REPLACE(ce3.result, ' ', '_')) IN ('APPROVED', 'APPROVED_WITH_REVISION')
             LIMIT 1"
        );
        $stmt->execute([':gid' => $groupId]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('rscIsChapterReady: ' . $e->getMessage());
        return false;
    }
}

function rscSplitName(string $fullName): array
{
    $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');
    if ($fullName === '') {
        return ['last' => '', 'first' => ''];
    }
    if (str_contains($fullName, ',')) {
        [$last, $first] = array_pad(array_map('trim', explode(',', $fullName, 2)), 2, '');
        return ['last' => $last, 'first' => $first];
    }
    $parts = array_values(array_filter(preg_split('/\s+/', $fullName) ?: [], static fn($p) => $p !== ''));
    if (count($parts) === 1) {
        return ['last' => $parts[0], 'first' => ''];
    }
    $particles = ['de', 'del', 'dela', 'da', 'das', 'do', 'dos', 'la', 'las', 'los', 'van', 'von', 'san', 'santa', 'sta', 'sto'];
    $lastParts = [array_pop($parts)];
    while ($parts !== [] && in_array(strtolower((string) $parts[count($parts) - 1]), $particles, true)) {
        array_unshift($lastParts, array_pop($parts));
    }
    return ['last' => implode(' ', $lastParts), 'first' => implode(' ', $parts)];
}

function rscNameFingerprint(string $name): string
{
    $parts = preg_split('/\s+/', strtolower(trim(preg_replace('/[^a-z0-9\s]/', ' ', $name) ?? '')), -1, PREG_SPLIT_NO_EMPTY);
    if (!is_array($parts) || $parts === []) {
        return '';
    }
    sort($parts);
    return implode('', $parts);
}

function rscMemberKeys(string $name, string $studentId = ''): array
{
    $keys = [];
    $id = strtoupper(preg_replace('/\s+/', '', $studentId) ?? '');
    if ($id !== '' && preg_match('/^S?\d+/i', $id)) {
        $keys[] = 'id:' . $id;
    }
    $print = rscNameFingerprint($name);
    if ($print !== '') {
        $keys[] = 'name:' . $print;
    }
    return $keys;
}

function rscLooksLikeStudentNumber(string $value): bool
{
    // Student numbers in this system are "S230000001" style, or plain digits.
    return preg_match('/^S?\d+$/i', $value) === 1;
}

/**
 * Normalize a Reference / O.R. number used on the clearance forms.
 *
 * Accepts the legacy "OR-123" form and the college receipt references
 * (e.g. HMBP260814005188418). Student numbers, dates and amounts are never
 * read as a reference so the id/reference split stays unambiguous.
 */
function rscExtractOrNumber(string $value): string
{
    $value = strtoupper(trim(preg_replace('/\s+/', '', $value) ?? ''));
    if ($value === '' || rscLooksLikeStudentNumber($value)) {
        return '';
    }
    if (preg_match('/^OR-[\w-]+$/', $value)) {
        return $value;
    }
    // Receipt references mix letters and digits, e.g. HMBP260814005188418.
    if (preg_match('/^(?:PHP|USD|\$)/i', $value)) {
        return '';
    }
    return preg_match('/^(?=.*[A-Z])(?=.*\d)[A-Z0-9][A-Z0-9\-\/]{3,79}$/', $value) === 1
        ? $value
        : '';
}

/**
 * The reference Admin approved for one researcher, as printed on the form.
 *
 * Anything non-empty that was approved is shown verbatim; the group reference
 * is only a fallback for a researcher without one of their own.
 */
function rscMemberOrNumber(string $memberOr, string $fallbackOr): string
{
    $memberOr = trim($memberOr);
    return $memberOr !== '' ? $memberOr : trim($fallbackOr);
}

function rscExtractStudentId(string $value): string
{
    $value = trim($value);
    if ($value === '' || rscExtractOrNumber($value) !== '') {
        return '';
    }
    return rscLooksLikeStudentNumber($value) ? $value : '';
}

function rscAddUniqueMember(array &$roster, string $name, string $studentId = '', string $orNumber = ''): void
{
    $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
    $studentId = rscExtractStudentId($studentId);
    $orNumber = rscExtractOrNumber($orNumber);
    if ($name === '' || preg_match('/^(n\/?a|none|tbd|-)$/i', $name)) {
        return;
    }
    $newKeys = rscMemberKeys($name, $studentId);
    if ($newKeys === []) {
        return;
    }
    foreach ($roster as $i => $row) {
        $existingKeys = rscMemberKeys((string) $row['name'], (string) $row['student_id']);
        if (array_intersect($newKeys, $existingKeys) !== []) {
            if ($studentId !== '' && trim((string) $row['student_id']) === '') {
                $roster[$i]['student_id'] = $studentId;
            }
            if ($orNumber !== '' && rscExtractOrNumber((string) $row['or_number']) === '') {
                $roster[$i]['or_number'] = $orNumber;
            }
            return;
        }
    }
    $roster[] = ['name' => $name, 'student_id' => $studentId, 'or_number' => $orNumber];
}

function rscDedupeMembers(array $members): array
{
    $roster = [];
    foreach ($members as $member) {
        if (!is_array($member)) {
            continue;
        }
        $third = trim((string) ($member['or_number'] ?? $member['student_id'] ?? $member[2] ?? ''));
        rscAddUniqueMember(
            $roster,
            (string) ($member['name'] ?? $member[0] ?? ''),
            (string) ($member['student_id'] ?? (rscExtractStudentId($third) !== '' ? $third : '')),
            (string) ($member['or_number'] ?? (rscExtractOrNumber($third) !== '' ? $third : ''))
        );
    }
    return $roster;
}

function rscMembersFromGroup(PDO $crad, array $group): array
{
    $roster = [];
    $proposalId = (int) ($group['proposal_id'] ?? 0);
    if ($proposalId > 0) {
        try {
            $stmt = $crad->prepare(
                "SELECT student_id, student_name FROM `crad_proposal_members`
                 WHERE proposal_id = ? ORDER BY sort_order ASC, id ASC"
            );
            $stmt->execute([$proposalId]);
            foreach ($stmt->fetchAll() ?: [] as $m) {
                rscAddUniqueMember($roster, (string) ($m['student_name'] ?? ''), (string) ($m['student_id'] ?? ''));
            }
        } catch (Throwable $e) {
            $roster = [];
        }
    }

    $json = trim((string) ($group['members_json'] ?? ''));
    if ($json !== '') {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            foreach ($decoded as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $name = trim((string) ($entry[0] ?? $entry['name'] ?? ''));
                $third = trim((string) ($entry[2] ?? $entry['student_id'] ?? $entry['or_number'] ?? ''));
                rscAddUniqueMember(
                    $roster,
                    $name,
                    (string) ($entry['student_id'] ?? ''),
                    (string) ($entry['or_number'] ?? $third)
                );
            }
        }
    }

    rscAddUniqueMember(
        $roster,
        (string) ($group['leader_name'] ?? $group['title_student_name'] ?? ''),
        (string) ($group['leader_id'] ?? $group['title_student_id'] ?? '')
    );

    return $roster;
}

function rscLoadGroupContext(PDO $crad, int $groupId): ?array
{
    $stmt = $crad->prepare(
        "SELECT rg.*, t.members_json, t.department AS title_department, t.student_id AS title_student_id,
                t.student_name AS title_student_name, t.student_user_id,
                aa.adviser_user_id, aa.adviser_name, aa.adviser_email
         FROM `crad_research_groups` rg
         LEFT JOIN `crad_title_approvals` t ON t.id = rg.title_approval_id
         LEFT JOIN `crad_research_adviser_assignments` aa ON aa.id = (
            SELECT aa2.id FROM `crad_research_adviser_assignments` aa2
            WHERE (aa2.research_group_id = rg.id
                OR (aa2.group_number IS NOT NULL AND aa2.group_number <> '' AND aa2.group_number = rg.group_number))
            ORDER BY (aa2.assignment_status IN ('Assigned','Confirmed')) DESC, aa2.updated_at DESC, aa2.id DESC
            LIMIT 1
         )
         WHERE rg.id = ?
         LIMIT 1"
    );
    $stmt->execute([$groupId]);
    $group = $stmt->fetch() ?: null;
    if (!$group) {
        return null;
    }

    $grammarian = '';
    $grammarianUserId = 0;
    try {
        $gStmt = $crad->prepare(
            "SELECT evaluator_user_id, evaluator_name FROM `crad_chapter_evaluations`
             WHERE research_group_id = ?
             ORDER BY id DESC LIMIT 1"
        );
        $gStmt->execute([$groupId]);
        $eval = $gStmt->fetch() ?: null;
        if ($eval) {
            $grammarianUserId = (int) ($eval['evaluator_user_id'] ?? 0);
            $grammarian = trim((string) ($eval['evaluator_name'] ?? ''));
        }
    } catch (Throwable $e) {
        $grammarian = '';
    }
    $grammarian = rscLiveAccountName($grammarianUserId, '', 'grammarian', $grammarian);

    $adviserUserId = (int) ($group['adviser_user_id'] ?? 0);
    $adviserEmail = strtolower(trim((string) ($group['adviser_email'] ?? '')));
    $adviserName = rscLiveAccountName(
        $adviserUserId,
        $adviserEmail,
        '',
        trim((string) ($group['adviser_name'] ?? $group['adviser'] ?? ''))
    );
    $group['resolved_adviser_name'] = $adviserName;
    $group['adviser_name'] = $adviserName;

    $program = trim((string) ($group['college_dept'] ?? ''));
    if ($program === '') {
        $program = trim((string) ($group['title_department'] ?? ''));
    }
    $section = '';
    $sms = function_exists('db') ? db() : null;
    $leaderId = trim((string) ($group['leader_id'] ?? $group['title_student_id'] ?? ''));
    if ($sms instanceof PDO && $leaderId !== '') {
        try {
            if (!function_exists('studentPortalEnsureProfileSchema')) {
                require_once ROOT_PATH . '/modules/student-portal/includes/student-profile.php';
            }
            studentPortalEnsureProfileSchema($sms);
            $pStmt = $sms->prepare('SELECT program, section FROM `sms2_student_profiles` WHERE student_id = ? LIMIT 1');
            $pStmt->execute([$leaderId]);
            $profile = $pStmt->fetch() ?: null;
            if ($profile) {
                if ($program === '') {
                    $program = trim((string) ($profile['program'] ?? ''));
                }
                $section = trim((string) ($profile['section'] ?? ''));
            }
        } catch (Throwable $e) {
            // keep blanks
        }
    }

    $group['resolved_program'] = $program;
    $group['resolved_section'] = $section;
    $group['resolved_grammarian'] = $grammarian;
    $group['resolved_members'] = rscMembersFromGroup($crad, $group);
    return $group;
}

function rscGenerateOrNumber(int $groupId, string $groupNumber = ''): string
{
    $fromGroup = strtoupper(preg_replace('/[^A-Z0-9]/', '', $groupNumber) ?? '');
    $fromGroup = preg_replace('/^RG/', '', $fromGroup) ?? '';
    if ($fromGroup !== '') {
        return 'OR-' . $fromGroup;
    }
    return 'OR-' . date('y') . str_pad((string) max(1, $groupId), 5, '0', STR_PAD_LEFT);
}

function rscResolveGroupOrNumber(int $groupId, string $groupNumber, array $members): string
{
    foreach ($members as $member) {
        if (!is_array($member)) {
            continue;
        }
        $or = rscExtractOrNumber((string) ($member['or_number'] ?? ''));
        if ($or !== '') {
            return $or;
        }
    }
    return rscGenerateOrNumber($groupId, $groupNumber);
}

function rscEnsureForReadyGroup(PDO $crad, int $groupId, string $stage = 'research_1'): ?array
{
    rscEnsureSchema($crad);
    $stage = rscNormalizeStage($stage);
    if ($groupId <= 0) {
        return null;
    }

    $existing = rscFindByGroup($crad, $groupId, $stage);
    if ($stage === 'research_1') {
        if (!$existing && !rscIsChapterReady($crad, $groupId)) {
            return null;
        }
        if (!$existing && !rscPaymentUnlocksClearance($crad, $groupId, null, 'research_1')) {
            return null;
        }
    } else {
        if (!rscClearanceDoneExists($crad, $groupId, 'research_1')) {
            return $existing;
        }
        if (!rcpIsFinalManuscriptApproved($crad, $groupId)) {
            return $existing;
        }
        if (!$existing && !rscPaymentUnlocksClearance($crad, $groupId, null, 'research_2')) {
            return null;
        }
    }

    $ctx = rscLoadGroupContext($crad, $groupId);
    if (!$ctx) {
        return $existing;
    }

    $members = $ctx['resolved_members'] ?? [];
    // Preserve the reference snapshot already approved for this clearance
    // stage. The same group may later submit a different Research 2 payment.
    if ($existing) {
        $approvedMembers = json_decode((string) ($existing['members_json'] ?? ''), true) ?: [];
        foreach ($members as $index => &$member) {
            $approvedReference = is_array($approvedMembers[$index] ?? null)
                ? trim((string) ($approvedMembers[$index]['or_number'] ?? ''))
                : '';
            if (is_array($member) && $approvedReference !== '') {
                $member['or_number'] = $approvedReference;
            }
        }
        unset($member);
    }
    $payload = [
        'title_approval_id' => (int) ($ctx['title_approval_id'] ?? 0) ?: null,
        'leader_student_no' => trim((string) ($ctx['leader_id'] ?? $ctx['title_student_id'] ?? '')),
        'leader_group_no' => trim((string) ($ctx['group_number'] ?? '')),
        'program' => (string) ($ctx['resolved_program'] ?? ''),
        'section' => (string) ($ctx['resolved_section'] ?? ''),
        'research_title' => trim((string) ($ctx['research_title'] ?? '')),
        'members_json' => json_encode($members, JSON_UNESCAPED_UNICODE),
        'grammarian_name' => (string) ($ctx['resolved_grammarian'] ?? ''),
        'adviser_name' => trim((string) ($ctx['resolved_adviser_name'] ?? $ctx['adviser_name'] ?? $ctx['adviser'] ?? '')),
        'statistician_name' => trim((string) ($ctx['resolved_adviser_name'] ?? $ctx['adviser_name'] ?? $ctx['adviser'] ?? '')),
        'adviser_user_id' => (int) ($ctx['adviser_user_id'] ?? 0) ?: null,
        'adviser_email' => strtolower(trim((string) ($ctx['adviser_email'] ?? ''))),
    ];

    $or = rscResolveGroupOrNumber($groupId, (string) ($ctx['group_number'] ?? ''), $members);
    $payment = rscApprovedPayment($crad, $groupId, $stage);
    if ($payment) {
        $payOr = trim((string) ($payment['or_number'] ?? ''));
        if ($payOr !== '') {
            $or = $payOr;
            $payload['members_json'] = json_encode($members, JSON_UNESCAPED_UNICODE);

            // Repair legacy forms where a live refresh copied the first
            // payment reference to every researcher. Only do this when the
            // approved roster clearly contains distinct references.
            if ($existing && function_exists('rcpGroupPaymentMembers')) {
                $storedMembers = json_decode((string) ($existing['members_json'] ?? ''), true) ?: [];
                $storedReferences = [];
                foreach ($storedMembers as $storedMember) {
                    if (is_array($storedMember)) {
                        $storedReferences[] = trim((string) ($storedMember['or_number'] ?? ''));
                    }
                }
                $storedReferences = array_values(array_unique(array_filter($storedReferences, static fn(string $value): bool => $value !== '')));
                $approvedMembers = rcpGroupPaymentMembers($crad, $groupId, rscNormalizeStage((string) ($row['research_stage'] ?? 'research_1')));
                $approvedReferences = array_values(array_unique(array_filter(array_map(
                    static fn(array $member): string => trim((string) ($member['or_number'] ?? '')),
                    $approvedMembers
                ), static fn(string $value): bool => $value !== '')));
                if (count($storedReferences) === 1 && $storedReferences[0] === $payOr && count($approvedReferences) > 1) {
                    foreach ($members as $index => &$member) {
                        if (is_array($member) && isset($approvedMembers[$index])) {
                            $member['or_number'] = (string) ($approvedMembers[$index]['or_number'] ?? $payOr);
                        }
                    }
                    unset($member);
                    $payload['members_json'] = json_encode($members, JSON_UNESCAPED_UNICODE);
                }
            }
        }
    }
    $payload['or_number'] = $or;

    if (!$existing) {
        $stmt = $crad->prepare(
            "INSERT INTO `crad_research_services_clearances`
                (research_group_id, research_stage, title_approval_id, status, or_number, leader_student_no, leader_group_no,
                 program, section, research_title, members_json, grammarian_name, statistician_name, adviser_name,
                 adviser_user_id, adviser_email)
             VALUES
                (:gid, :stage, :tid, 'draft', :or_number, :leader_student_no, :leader_group_no,
                 :program, :section, :research_title, :members_json, :grammarian_name, :statistician_name, :adviser_name,
                 :adviser_user_id, :adviser_email)"
        );
        $stmt->execute([
            ':gid' => $groupId,
            ':stage' => $stage,
            ':tid' => $payload['title_approval_id'],
            ':or_number' => $or,
            ':leader_student_no' => $payload['leader_student_no'],
            ':leader_group_no' => $payload['leader_group_no'],
            ':program' => $payload['program'],
            ':section' => $payload['section'],
            ':research_title' => $payload['research_title'],
            ':members_json' => $payload['members_json'],
            ':grammarian_name' => $payload['grammarian_name'],
            ':statistician_name' => $payload['statistician_name'],
            ':adviser_name' => $payload['adviser_name'],
            ':adviser_user_id' => $payload['adviser_user_id'],
            ':adviser_email' => $payload['adviser_email'],
        ]);
        return rscFindByGroup($crad, $groupId, $stage);
    }

    $crad->prepare(
        "UPDATE `crad_research_services_clearances`
         SET title_approval_id = :tid,
             research_stage = :stage,
             or_number = :or_number,
             leader_student_no = :leader_student_no,
             leader_group_no = :leader_group_no,
             program = :program,
             section = :section,
             research_title = :research_title,
             members_json = :members_json,
             grammarian_name = :grammarian_name,
             statistician_name = :statistician_name,
             adviser_name = :adviser_name,
             adviser_user_id = :adviser_user_id,
             adviser_email = :adviser_email
         WHERE id = :id"
    )->execute([
        ':tid' => $payload['title_approval_id'],
        ':stage' => $stage,
        ':or_number' => $or,
        ':leader_student_no' => $payload['leader_student_no'],
        ':leader_group_no' => $payload['leader_group_no'],
        ':program' => $payload['program'],
        ':section' => $payload['section'],
        ':research_title' => $payload['research_title'],
        ':members_json' => $payload['members_json'],
        ':grammarian_name' => $payload['grammarian_name'],
        ':statistician_name' => $payload['statistician_name'],
        ':adviser_name' => $payload['adviser_name'],
        ':adviser_user_id' => $payload['adviser_user_id'],
        ':adviser_email' => $payload['adviser_email'],
        ':id' => (int) $existing['id'],
    ]);
    return rscFindById($crad, (int) $existing['id']);
}

function rscFindByGroup(PDO $crad, int $groupId, string $stage = 'research_1'): ?array
{
    if ($groupId <= 0) {
        return null;
    }
    $stage = rscNormalizeStage($stage);
    $stmt = $crad->prepare(
        'SELECT * FROM `crad_research_services_clearances`
         WHERE research_group_id = ? AND research_stage = ?
         LIMIT 1'
    );
    $stmt->execute([$groupId, $stage]);
    $row = $stmt->fetch() ?: null;
    return $row ?: null;
}

function rscFindById(PDO $crad, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $crad->prepare('SELECT * FROM `crad_research_services_clearances` WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: null;
    return $row ?: null;
}

function rscNotify(PDO $crad, string $eventKey, int $clearanceId, array $recipient, string $type, string $title, string $body, string $url): void
{
    rscEnsureSchema($crad);
    $stmt = $crad->prepare(
        "INSERT IGNORE INTO `crad_research_clearance_notifications`
            (event_key, recipient_user_id, recipient_role, recipient_email, clearance_id, type, title, body, url)
         VALUES
            (:event_key, :user_id, :role, :email, :clearance_id, :type, :title, :body, :url)"
    );
    $stmt->execute([
        ':event_key' => $eventKey,
        ':user_id' => (int) ($recipient['id'] ?? 0) ?: null,
        ':role' => (string) ($recipient['role_key'] ?? $recipient['role'] ?? ''),
        ':email' => strtolower(trim((string) ($recipient['email'] ?? ''))),
        ':clearance_id' => $clearanceId,
        ':type' => $type,
        ':title' => $title,
        ':body' => $body,
        ':url' => $url,
    ]);
}

function rscStudentRecipients(PDO $crad, array $clearance): array
{
    $ctx = rscLoadGroupContext($crad, (int) ($clearance['research_group_id'] ?? 0));
    if (!$ctx) {
        return [];
    }
    $recipients = [[
        'id' => (int) ($ctx['student_user_id'] ?? 0),
        'role_key' => 'student',
        'email' => strtolower(trim((string) ($ctx['leader_email'] ?? ''))),
    ]];
    $sms = function_exists('db') ? db() : null;
    $leaderId = trim((string) ($ctx['leader_id'] ?? $ctx['title_student_id'] ?? ''));
    if ($sms instanceof PDO && $leaderId !== '' && (int) ($recipients[0]['id'] ?? 0) <= 0) {
        try {
            $uStmt = $sms->prepare("SELECT id, email, role_key FROM `sms2_users` WHERE student_id = ? AND role_key = 'student' LIMIT 1");
            $uStmt->execute([$leaderId]);
            $user = $uStmt->fetch() ?: null;
            if ($user) {
                $recipients[0] = $user;
            }
        } catch (Throwable $e) {
            // keep fallback recipient
        }
    }
    return $recipients;
}

/** Department Heads receive the Research 1 → Pre-Oral panel-assignment handoff. */
function rscDepartmentHeadRecipients(): array
{
    $sms = function_exists('db') ? db() : null;
    if (!$sms instanceof PDO) {
        return [];
    }
    try {
        $stmt = $sms->query(
            "SELECT id, email, role_key FROM `sms2_users`
             WHERE role_key = 'department_head' AND status = 'active'"
        );
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        error_log('Department Head clearance recipients failed: ' . $e->getMessage());
        return [];
    }
}

function rscNormalizeSignature(string $signature): string
{
    $signature = trim($signature);
    if ($signature === '' || !preg_match('#^data:image/(png|jpeg);base64,#i', $signature)) {
        return '';
    }
    if (strlen($signature) > 900000) {
        return '';
    }
    return $signature;
}

function rscSendToAdviser(PDO $crad, array $clearance): array
{
    return [
        'ok' => false,
        'error' => 'Send to Adviser is no longer used. Print your clearance, get it signed, then upload the signed image for CRAD approval.',
    ];
}

function rscAdviserSign(PDO $crad, array $clearance, string $signature, string $signerName): array
{
    return [
        'ok' => false,
        'error' => 'Adviser digital signing is no longer used for Research Services Clearance. Students upload the signed form for CRAD approval.',
    ];
}

/**
 * Student uploads the printed & physically signed clearance image → CRAD queue.
 */
function rscStudentUploadSigned(PDO $crad, array $clearance, array $file = []): array
{
    $status = (string) ($clearance['status'] ?? '');
    $allowed = ['draft', 'sent_to_adviser', 'adviser_signed', 'crad_received', 'rejected'];
    if (!in_array($status, $allowed, true)) {
        if ($status === 'clearance_done') {
            return ['ok' => false, 'error' => 'This clearance is already approved.'];
        }
        return ['ok' => false, 'error' => 'This clearance cannot accept an upload right now.'];
    }
    if (!rscPaymentUnlocksClearance($crad, (int) ($clearance['research_group_id'] ?? 0), $clearance)) {
        return ['ok' => false, 'error' => 'Admin must approve the collage payment before you can upload the signed clearance.'];
    }

    $hasNewFile = $file !== [] && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    if (!$hasNewFile) {
        return ['ok' => false, 'error' => 'Choose a PNG or JPG picture of your signed clearance form.'];
    }
    $saved = rscStoreUpload((int) $clearance['id'], $file);
    if (empty($saved['ok'])) {
        return $saved;
    }

    $oldFile = basename(str_replace('\\', '/', trim((string) ($clearance['uploaded_file'] ?? ''))));
    if ($oldFile !== '' && $oldFile !== (string) $saved['file']) {
        $oldPath = rscUploadedImagePath($oldFile);
        if ($oldPath !== null) {
            @unlink($oldPath);
        }
    }

    $id = (int) $clearance['id'];
    $crad->prepare(
        "UPDATE `crad_research_services_clearances`
         SET status = 'crad_received',
             uploaded_file = :file,
             uploaded_blob = :blob,
             uploaded_mime = :mime,
             uploaded_size = :size,
             uploaded_original = :orig,
             uploaded_at = NOW(),
             form_verified = 1,
             crad_remarks = '',
             sent_at = COALESCE(sent_at, NOW())
         WHERE id = :id"
    )->execute([
        ':file' => (string) $saved['file'],
        ':blob' => (string) $saved['data'],
        ':mime' => (string) $saved['mime'],
        ':size' => (int) $saved['size'],
        ':orig' => (string) $saved['original'],
        ':id' => $id,
    ]);

    $fresh = rscFindById($crad, $id);
    if (function_exists('logActivity')) {
        logActivity(
            'create',
            'Submitted signed ' . rscStageLabel((string) ($clearance['research_stage'] ?? 'research_1'))
                . ' clearance for research group #' . (int) $clearance['research_group_id'],
            'crad'
        );
    }
    $sms = function_exists('db') ? db() : null;
    if ($sms instanceof PDO) {
        $officers = $sms->query(
            "SELECT id, email, role_key FROM `sms2_users`
             WHERE role_key IN ('crad_officer', 'sms_admin') AND status = 'active'"
        )->fetchAll() ?: [];
        foreach ($officers as $officer) {
            rscNotify(
                $crad,
                'clearance-student-upload:' . $id . ':u' . (int) $officer['id'],
                $id,
                $officer,
                'student_signed_upload',
                'Signed clearance for review',
                'A student uploaded a signed Research Services Clearance. Please review and approve the signature.',
                rscCradUrl($id)
            );
        }
    }
    return ['ok' => true, 'clearance' => $fresh];
}

function rscCradReceive(PDO $crad, array $clearance, array $file = []): array
{
    // Legacy CRAD upload path — students now upload; CRAD may still re-upload if needed.
    $status = (string) ($clearance['status'] ?? '');
    if (!in_array($status, ['draft', 'sent_to_adviser', 'adviser_signed', 'crad_received', 'clearance_done'], true)) {
        return ['ok' => false, 'error' => 'This clearance is not ready for an image upload.'];
    }

    $hasNewFile = $file !== [] && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    if (!$hasNewFile) {
        return ['ok' => false, 'error' => 'Choose the Research Services Clearance picture to upload or re-upload.'];
    }
    $saved = rscStoreUpload((int) $clearance['id'], $file);
    if (empty($saved['ok'])) {
        return $saved;
    }

    $oldFile = basename(str_replace('\\', '/', trim((string) ($clearance['uploaded_file'] ?? ''))));
    if ($oldFile !== '' && $oldFile !== (string) $saved['file']) {
        $oldPath = rscUploadedImagePath($oldFile);
        if ($oldPath !== null) {
            @unlink($oldPath);
        }
    }

    $nextStatus = $status === 'clearance_done' ? 'clearance_done' : 'crad_received';
    $crad->prepare(
        "UPDATE `crad_research_services_clearances`
         SET status = :status,
             uploaded_file = :file,
             uploaded_blob = :blob,
             uploaded_mime = :mime,
             uploaded_size = :size,
             uploaded_original = :orig,
             uploaded_at = NOW(),
             form_verified = 1
         WHERE id = :id"
    )->execute([
        ':status' => $nextStatus,
        ':file' => (string) $saved['file'],
        ':blob' => (string) $saved['data'],
        ':mime' => (string) $saved['mime'],
        ':size' => (int) $saved['size'],
        ':orig' => (string) $saved['original'],
        ':id' => (int) $clearance['id'],
    ]);

    return ['ok' => true, 'clearance' => rscFindById($crad, (int) $clearance['id'])];
}

function rscVerifyOfficialFormImage(array $clearance, string $path, string $originalName = ''): array
{
    if ($path === '' || !is_file($path)) {
        return ['ok' => false, 'error' => 'Upload the Research Services Clearance picture first.'];
    }
    $info = @getimagesize($path);
    if (!$info || empty($info[0]) || empty($info[1])) {
        return ['ok' => false, 'error' => 'That file is not a clearance form picture. Upload the PNG or JPG of the Research Services Clearance.'];
    }
    $mime = strtolower((string) ($info['mime'] ?? ''));
    if (!in_array($mime, ['image/png', 'image/jpeg'], true)) {
        return ['ok' => false, 'error' => 'Upload a PNG or JPG picture of the Research Services Clearance form.'];
    }
    $width = (int) $info[0];
    $height = (int) $info[1];
    if ($width < 400 || $height < 300) {
        return ['ok' => false, 'error' => 'That picture is too small to be the Research Services Clearance form.'];
    }
    return ['ok' => true];
}

function rscImageLooksLikePaperForm(string $path): bool
{
    $bin = @file_get_contents($path);
    if ($bin === false || $bin === '') {
        return false;
    }
    $im = @imagecreatefromstring($bin);
    if (!$im) {
        return false;
    }
    $w = imagesx($im);
    $h = imagesy($im);
    $light = 0;
    $total = 0;
    $stepX = max(1, (int) floor($w / 24));
    $stepY = max(1, (int) floor($h / 24));
    for ($y = 4; $y < $h; $y += $stepY) {
        for ($x = 4; $x < $w; $x += $stepX) {
            $rgb = imagecolorat($im, $x, $y);
            $r = ($rgb >> 16) & 255;
            $g = ($rgb >> 8) & 255;
            $b = $rgb & 255;
            $total++;
            if ($r > 190 && $g > 190 && $b > 190) {
                $light++;
            }
        }
    }
    imagedestroy($im);
    return $total > 0 && ($light / $total) >= 0.38;
}

function rscCradVerifyMarks(PDO $crad, array $clearance, bool $mis, bool $aa): array
{
    $status = (string) ($clearance['status'] ?? '');
    if (!in_array($status, ['adviser_signed', 'crad_received'], true)) {
        return ['ok' => false, 'error' => 'Upload the adviser-signed clearance first.'];
    }
    if (trim((string) ($clearance['uploaded_file'] ?? '')) === '') {
        return ['ok' => false, 'error' => 'Upload the adviser-signed clearance form first.'];
    }
    if (trim((string) ($clearance['adviser_signature'] ?? '')) === '') {
        return ['ok' => false, 'error' => 'The adviser signature is missing.'];
    }
    $crad->prepare(
        "UPDATE `crad_research_services_clearances`
         SET mis_verified = :mis,
             aa_verified = :aa,
             mis_verified_at = CASE WHEN :mis2 = 1 THEN COALESCE(mis_verified_at, NOW()) ELSE NULL END,
             aa_verified_at = CASE WHEN :aa2 = 1 THEN COALESCE(aa_verified_at, NOW()) ELSE NULL END,
             status = CASE WHEN status = 'adviser_signed' THEN 'crad_received' ELSE status END
         WHERE id = :id"
    )->execute([
        ':mis' => $mis ? 1 : 0,
        ':aa' => $aa ? 1 : 0,
        ':mis2' => $mis ? 1 : 0,
        ':aa2' => $aa ? 1 : 0,
        ':id' => (int) $clearance['id'],
    ]);
    return ['ok' => true, 'clearance' => rscFindById($crad, (int) $clearance['id'])];
}

function rscHasPhysicalSignature(array $clearance, string $field): bool
{
    return trim((string) ($clearance[$field] ?? '')) !== '';
}

function rscCanCradSign(array $clearance): bool
{
    return rscCanCradApprove($clearance);
}

/** CRAD can approve when student uploaded a signed clearance image. */
function rscCanCradApprove(array $clearance): bool
{
    return trim((string) ($clearance['uploaded_file'] ?? '')) !== ''
        && in_array((string) ($clearance['status'] ?? ''), ['crad_received', 'adviser_signed'], true);
}

function rscCradSign(PDO $crad, array $clearance, string $signature, string $signerName): array
{
    // Signature pad optional — approving the uploaded signed form is enough.
    return rscCradApproveSigned($crad, $clearance, $signerName);
}

/** Queue a cleared Research 2 group for Final Defense without replacing an existing schedule. */
function rscQueueFinalDefenseScheduling(PDO $crad, array $clearance): void
{
    if (rscNormalizeStage((string) ($clearance['research_stage'] ?? 'research_1')) !== 'research_2') {
        return;
    }

    $groupId = (int) ($clearance['research_group_id'] ?? 0);
    if ($groupId <= 0) {
        return;
    }

    try {
        $existing = $crad->prepare(
            "SELECT 1 FROM `crad_research_defense_schedules`
             WHERE research_group_id = ?
               AND LOWER(TRIM(COALESCE(defense_type, ''))) = LOWER(?)
             LIMIT 1"
        );
        $existing->execute([$groupId, CRAD_DEFENSE_TYPE_FINAL]);
        if ($existing->fetchColumn()) {
            return;
        }

        $group = rscLoadGroupContext($crad, $groupId);
        if (!$group) {
            return;
        }
        $insert = $crad->prepare(
            "INSERT INTO `crad_research_defense_schedules`
                (research_group_id, proposal_id, proposal_number, group_number, research_group, research_title,
                 adviser_name, panel_members, panel_chair, defense_type, status, recorded_by, recorded_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, '', '', ?, 'Ready for Scheduling', ?, NOW(), NOW())"
        );
        $insert->execute([
            $groupId,
            (int) ($group['proposal_id'] ?? 0) ?: null,
            (string) ($group['proposal_number'] ?? ''),
            (string) ($group['group_number'] ?? ''),
            (string) ($group['group_name'] ?? 'Research Group'),
            (string) ($group['research_title'] ?? ''),
            (string) ($group['adviser_name'] ?? ''),
            CRAD_DEFENSE_TYPE_FINAL,
            (int) ($_SESSION['user_id'] ?? 0) ?: null,
        ]);
    } catch (Throwable $e) {
        error_log('Final Defense scheduling queue failed: ' . $e->getMessage());
    }
}

/**
 * CRAD approves the student-uploaded signed clearance (no digital pad required).
 */
function rscCradApproveSigned(PDO $crad, array $clearance, string $approverName = ''): array
{
    if (!rscCanCradApprove($clearance)) {
        return ['ok' => false, 'error' => 'Upload/signed clearance image is required before CRAD can approve.'];
    }
    $name = trim($approverName) !== '' ? trim($approverName) : getCurrentUserName();
    $crad->prepare(
        "UPDATE `crad_research_services_clearances`
         SET status = 'clearance_done',
             crad_signed_at = NOW(),
             crad_name = :name,
             crad_user_id = :uid,
             form_verified = 1,
             crad_remarks = ''
         WHERE id = :id"
    )->execute([
        ':name' => $name,
        ':uid' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
        ':id' => (int) $clearance['id'],
    ]);

    $fresh = rscFindById($crad, (int) $clearance['id']) ?: $clearance;
    if (function_exists('logActivity')) {
        logActivity(
            'update',
            'Approved signed ' . rscStageLabel((string) ($clearance['research_stage'] ?? 'research_1'))
                . ' clearance for research group #' . (int) $clearance['research_group_id'],
            'crad'
        );
    }
    rscQueueFinalDefenseScheduling($crad, $fresh);

    // Research 1 completion is the gate for Pre-Oral panel assignment. The
    // Department Head's existing Defense-Ready page reads this same status;
    // notify them immediately with the group preselected for assignment.
    if (rscNormalizeStage((string) ($fresh['research_stage'] ?? 'research_1')) === 'research_1') {
        $groupId = (int) ($fresh['research_group_id'] ?? 0);
        $groupNumber = trim((string) ($fresh['leader_group_no'] ?? ''));
        $groupLabel = $groupNumber !== '' ? $groupNumber : ('research group #' . $groupId);
        $panelUrl = BASE_URL . '/modules/crad/pages/retrieve-defense-ready-research.php?group_id=' . $groupId;
        foreach (rscDepartmentHeadRecipients() as $recipient) {
            rscNotify(
                $crad,
                'research-1-panel-ready:' . (int) $fresh['id'] . ':u' . (int) ($recipient['id'] ?? 0),
                (int) $fresh['id'],
                $recipient,
                'panel_assignment_ready',
                'Research 1 ready for panel assignment',
                $groupLabel . ' completed Research 1 clearance and is ready for Pre-Oral panel assignment.',
                $panelUrl
            );
        }
    }
    foreach (rscStudentRecipients($crad, $clearance) as $recipient) {
        rscNotify(
            $crad,
            'clearance-done:' . (int) $clearance['id'] . ':' . time(),
            (int) $clearance['id'],
            $recipient,
            'clearance_done',
            'Clearance approved',
            'CRAD approved your signed Research Services Clearance. Your clearance is complete.',
            rscStudentUrl()
        );
    }
    return ['ok' => true, 'clearance' => $fresh];
}

/**
 * CRAD rejects the signed upload — student must re-upload.
 */
function rscCradRejectSigned(PDO $crad, array $clearance, string $reason = ''): array
{
    $status = (string) ($clearance['status'] ?? '');
    if (!in_array($status, ['crad_received', 'adviser_signed'], true)) {
        return ['ok' => false, 'error' => 'This clearance is not waiting for CRAD review.'];
    }
    if (trim((string) ($clearance['uploaded_file'] ?? '')) === '') {
        return ['ok' => false, 'error' => 'There is no uploaded clearance image to reject.'];
    }
    $reason = trim($reason);
    if ($reason === '') {
        $reason = 'Please re-upload a clearer signed clearance form.';
    }
    if (strlen($reason) > 500) {
        $reason = substr($reason, 0, 500);
    }

    $crad->prepare(
        "UPDATE `crad_research_services_clearances`
         SET status = 'rejected',
             form_verified = 0,
             crad_remarks = :remarks,
             crad_signed_at = NULL,
             crad_name = '',
             crad_user_id = NULL
         WHERE id = :id"
    )->execute([
        ':remarks' => $reason,
        ':id' => (int) $clearance['id'],
    ]);

    $fresh = rscFindById($crad, (int) $clearance['id']) ?: $clearance;
    if (function_exists('logActivity')) {
        logActivity(
            'update',
            'Returned signed ' . rscStageLabel((string) ($clearance['research_stage'] ?? 'research_1'))
                . ' clearance for research group #' . (int) $clearance['research_group_id'],
            'crad'
        );
    }
    foreach (rscStudentRecipients($crad, $clearance) as $recipient) {
        rscNotify(
            $crad,
            'clearance-rejected:' . (int) $clearance['id'] . ':' . time(),
            (int) $clearance['id'],
            $recipient,
            'clearance_rejected',
            'Clearance returned — re-upload needed',
            'CRAD rejected your signed clearance. Reason: ' . $reason . ' Please print/sign again and re-upload.',
            rscStudentUrl()
        );
    }
    return ['ok' => true, 'clearance' => $fresh];
}

function rscUploadPublicUrl(array $row): string
{
    $file = basename(str_replace('\\', '/', trim((string) ($row['uploaded_file'] ?? ''))));
    if ($file === '' || $file === '.' || $file === '..') {
        return '';
    }
    $stamp = strtotime((string) ($row['uploaded_at'] ?? '')) ?: time();
    return BASE_URL . '/modules/crad/api/research-clearance-file.php?id=' . (int) ($row['id'] ?? 0) . '&v=' . $stamp;
}

function rscUploadedImagePath(string $file): ?string
{
    $normalized = str_replace('\\', '/', trim($file));
    $basename = basename($normalized);
    if ($basename === '' || $basename === '.' || $basename === '..') {
        return null;
    }
    $candidates = [
        ROOT_PATH . '/storage/uploads/research-clearance/' . $basename,
        ROOT_PATH . '/uploads/research-clearance/' . $basename,
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return null;
}

/** @return array{data: string, mime: string}|null */
function rscPersistentImageData(array $row): ?array
{
    $data = $row['uploaded_blob'] ?? null;
    $mime = strtolower(trim((string) ($row['uploaded_mime'] ?? '')));
    if (!is_string($data) || $data === '' || !in_array($mime, ['image/png', 'image/jpeg'], true)) {
        return null;
    }
    return ['data' => $data, 'mime' => $mime];
}

function rscStoreUpload(int $clearanceId, array $file): array
{
    $code = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($code !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'The clearance image is too large for the server. Use the PNG from Adviser → Download Image.',
            UPLOAD_ERR_FORM_SIZE => 'The clearance image is too large. Please upload a smaller PNG or JPG.',
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Choose the Research Services Clearance picture first.',
        ];
        return ['ok' => false, 'error' => $messages[$code] ?? 'Upload failed. Please try again.'];
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $name = (string) ($file['name'] ?? 'clearance.png');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }
    $info = @getimagesize($tmp);
    $mime = strtolower((string) ($info['mime'] ?? ''));
    if (!in_array($mime, ['image/png', 'image/jpeg'], true)) {
        return ['ok' => false, 'error' => 'Upload a PNG or JPG picture of the Research Services Clearance form.'];
    }
    $data = @file_get_contents($tmp);
    if (!is_string($data) || $data === '') {
        return ['ok' => false, 'error' => 'The uploaded clearance could not be read.'];
    }
    $ext = $mime === 'image/png' ? 'png' : 'jpg';
    $stored = 'rsc-' . $clearanceId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    return ['ok' => true, 'file' => $stored, 'original' => $name, 'data' => $data, 'mime' => $mime, 'size' => strlen($data)];
}

function rscStatusLabel(string $status): string
{
    return match ($status) {
        'draft' => 'Ready to print',
        'sent_to_adviser' => 'Ready to print',
        'adviser_signed' => 'Awaiting signed upload',
        'crad_received' => 'Awaiting CRAD approval',
        'rejected' => 'Rejected — re-upload',
        'clearance_done' => 'Clearance done',
        default => $status,
    };
}

/**
 * Roster the clearance form must print, rebuilt from the Admin-approved list.
 *
 * The approved roster is the source of truth: it holds one name + reference
 * per researcher, capped at five, in approved order. The stored snapshot is
 * only used to carry over a researcher or reference the approval list is
 * missing, so a stale or duplicated row can never reach the printed form.
 * Running on every live poll keeps First Name, Last Name and the
 * Research 1 / Defense O.R. No. column in step with the Admin approval.
 *
 * @return array<int, array{name: string, student_id: string, or_number: string}>
 */
function rscSyncApprovedRoster(PDO $crad, int $groupId, array $members, string $groupOr, string $stage = 'research_1'): array
{
    $approved = function_exists('rcpGroupPaymentMembers')
        ? rcpGroupPaymentMembers($crad, $groupId, $stage)
        : [];
    if ($approved === []) {
        return array_slice(rscDedupeMembers($members), 0, 5);
    }

    $referencesByStudent = [];
    $referencesByName = [];
    foreach ($approved as $approvedMember) {
        $reference = trim((string) ($approvedMember['or_number'] ?? ''));
        if ($reference === '') {
            continue;
        }
        $studentId = strtolower(trim((string) ($approvedMember['student_id'] ?? '')));
        $name = strtolower(trim(preg_replace('/\s+/', ' ', (string) ($approvedMember['name'] ?? '')) ?? ''));
        if ($studentId !== '') {
            $referencesByStudent[$studentId] = $reference;
        }
        if ($name !== '') {
            $referencesByName[$name] = $reference;
        }
    }

    $roster = [];
    foreach ($approved as $approvedMember) {
        $roster[] = [
            'name' => trim((string) ($approvedMember['name'] ?? '')),
            'student_id' => trim((string) ($approvedMember['student_id'] ?? '')),
            'or_number' => rscMemberOrNumber((string) ($approvedMember['or_number'] ?? ''), $groupOr),
        ];
    }
    // Carry over anyone the clearance still tracks but the approval list omits.
    foreach ($members as $member) {
        if (!is_array($member)) {
            continue;
        }
        $name = trim(preg_replace('/\s+/', ' ', (string) ($member['name'] ?? '')) ?? '');
        if ($name === '') {
            continue;
        }
        $studentId = strtolower(trim((string) ($member['student_id'] ?? '')));
        $reference = trim((string) ($member['or_number'] ?? ''));
        if ($reference === '' || $reference === trim($groupOr)) {
            $reference = ($studentId !== '' ? ($referencesByStudent[$studentId] ?? '') : '')
                ?: ($referencesByName[strtolower($name)] ?? '')
                ?: $reference;
        }
        $roster[] = ['name' => $name, 'student_id' => (string) ($member['student_id'] ?? ''), 'or_number' => $reference];
    }

    return array_slice(rscDedupeMembers($roster), 0, 5);
}

function rscAttachPaymentFields(array $row): array
{
    $crad = rscDb();
    $payment = $row['_payment'] ?? null;
    $stage = rscNormalizeStage((string) ($row['research_stage'] ?? 'research_1'));
    if (!is_array($payment)) {
        $gid = (int) ($row['research_group_id'] ?? 0);
        $payment = ($crad instanceof PDO && $gid > 0) ? rscApprovedPayment($crad, $gid, $stage) : null;
    }
    if (is_array($payment)) {
        $or = trim((string) ($payment['or_number'] ?? ''));
        if ($or !== '') {
            $row['or_number'] = $or;
        }
        // Always rebuild the roster from the current approved references. This
        // runs on every student live poll, so the form can never keep a stale
        // snapshot, a duplicated row, or one reference copied to everyone.
        if ($crad instanceof PDO) {
            $members = json_decode((string) ($row['members_json'] ?? ''), true) ?: [];
            $roster = rscSyncApprovedRoster($crad, (int) ($row['research_group_id'] ?? 0), $members, $or, $stage);
            $row['members_json'] = json_encode($roster, JSON_UNESCAPED_UNICODE);
        }
        $row['payment_remarks'] = trim((string) ($payment['remarks'] ?? '')) ?: 'HMA';
        $row['payment_approved'] = true;
    } else {
        $row['payment_remarks'] = trim((string) ($row['payment_remarks'] ?? '')) ?: 'HMA';
        $row['payment_approved'] = false;
    }
    $row['research_stage'] = $stage;
    return $row;
}

function rscPublicRow(array $row): array
{
    $row = rscAttachPaymentFields($row);
    $row = rscApplyUploadedSignatures($row);
    $members = rscDedupeMembers(json_decode((string) ($row['members_json'] ?? ''), true) ?: []);
    return [
        'id' => (int) $row['id'],
        'research_group_id' => (int) $row['research_group_id'],
        'research_stage' => rscNormalizeStage((string) ($row['research_stage'] ?? 'research_1')),
        'stage_label' => rscStageLabel((string) ($row['research_stage'] ?? 'research_1')),
        'status' => (string) $row['status'],
        'status_label' => rscStatusLabel((string) $row['status']),
        'or_number' => (string) $row['or_number'],
        'payment_remarks' => (string) ($row['payment_remarks'] ?? 'HMA'),
        'payment_approved' => !empty($row['payment_approved']),
        'member_count' => count($members),
        'leader_student_no' => (string) $row['leader_student_no'],
        'leader_group_no' => (string) $row['leader_group_no'],
        'program' => (string) $row['program'],
        'section' => (string) $row['section'],
        'research_title' => (string) $row['research_title'],
        'members' => $members,
        'grammarian_name' => (string) $row['grammarian_name'],
        'statistician_name' => (string) $row['statistician_name'],
        'adviser_name' => (string) $row['adviser_name'],
        'adviser_signed_at' => (string) ($row['adviser_signed_at'] ?? ''),
        'crad_name' => (string) ($row['crad_name'] ?? ''),
        'crad_signed_at' => (string) ($row['crad_signed_at'] ?? ''),
        'uploaded_original' => (string) ($row['uploaded_original'] ?? ''),
        'uploaded_url' => rscUploadPublicUrl($row),
        'uploaded_at' => (string) ($row['uploaded_at'] ?? ''),
        'uploaded_at_label' => rscFormatDateTimeLabel((string) ($row['uploaded_at'] ?? '')),
        'has_upload' => trim((string) ($row['uploaded_file'] ?? '')) !== '',
        'form_verified' => (int) ($row['form_verified'] ?? 0) === 1,
        'has_adviser_signature' => trim((string) ($row['adviser_signature'] ?? '')) !== '',
        'has_mis_signature' => trim((string) ($row['mis_signature'] ?? '')) !== ''
            || (int) ($row['mis_verified'] ?? 0) === 1
            || rscUploadedFormHasPhysicalMarks($row),
        'has_aa_signature' => trim((string) ($row['aa_signature'] ?? '')) !== ''
            || (int) ($row['aa_verified'] ?? 0) === 1
            || rscUploadedFormHasPhysicalMarks($row),
        'has_crad_signature' => trim((string) ($row['crad_signature'] ?? '')) !== '',
        'mis_verified' => (int) ($row['mis_verified'] ?? 0) === 1 || rscUploadedFormHasPhysicalMarks($row),
        'aa_verified' => (int) ($row['aa_verified'] ?? 0) === 1 || rscUploadedFormHasPhysicalMarks($row),
        'can_crad_sign' => rscCanCradApprove($row),
        'can_student_upload' => in_array((string) ($row['status'] ?? ''), ['draft', 'sent_to_adviser', 'adviser_signed', 'crad_received', 'rejected'], true)
            && !empty($row['payment_approved']),
        'crad_remarks' => (string) ($row['crad_remarks'] ?? ''),
        'sent_at' => (string) ($row['sent_at'] ?? ''),
        'sent_at_label' => rscFormatDateTimeLabel((string) ($row['sent_at'] ?? '')),
        'updated_at' => (string) ($row['updated_at'] ?? ''),
        'updated_at_label' => rscFormatDateTimeLabel((string) ($row['updated_at'] ?? '')),
        'form_html' => rscRenderFormHtml($row),
    ];
}

function rscFormatDateTimeLabel(string $value): string
{
    $value = trim($value);
    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return '—';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return $value;
    }
    return date('M j, Y g:i A', $ts);
}

function rscApplyUploadedSignatures(array $row): array
{
    $hasMis = trim((string) ($row['mis_signature'] ?? '')) !== '';
    $hasAa = trim((string) ($row['aa_signature'] ?? '')) !== '';
    if ($hasMis && $hasAa) {
        return $row;
    }
    $file = basename(str_replace('\\', '/', trim((string) ($row['uploaded_file'] ?? ''))));
    if ($file === '' || $file === '.' || $file === '..' || !defined('ROOT_PATH')) {
        return $row;
    }
    $path = rscUploadedImagePath($file);
    if ($path === null) {
        return $row;
    }
    try {
        $extracted = rscExtractPhysicalSignatures($path, $row);
    } catch (Throwable $e) {
        return $row;
    }
    if (!$hasMis && trim((string) ($extracted['mis'] ?? '')) !== '') {
        $row['mis_signature'] = $extracted['mis'];
    }
    if (!$hasAa && trim((string) ($extracted['aa'] ?? '')) !== '') {
        $row['aa_signature'] = $extracted['aa'];
    }
    $row['mis_signature'] = rscCleanSignatureDataUrl((string) ($row['mis_signature'] ?? ''));
    $row['aa_signature'] = rscCleanSignatureDataUrl((string) ($row['aa_signature'] ?? ''));
    return $row;
}

function rscPersistUploadedSignatures(PDO $crad, array $row): array
{
    $file = basename(str_replace('\\', '/', trim((string) ($row['uploaded_file'] ?? ''))));
    $hydrated = $row;
    if ($file !== '' && $file !== '.' && $file !== '..' && defined('ROOT_PATH')) {
        $path = rscUploadedImagePath($file);
        if ($path !== null) {
            try {
                $extracted = rscExtractPhysicalSignatures($path, $row);
                if (trim((string) ($extracted['mis'] ?? '')) !== '') {
                    $hydrated['mis_signature'] = $extracted['mis'];
                }
                if (trim((string) ($extracted['aa'] ?? '')) !== '') {
                    $hydrated['aa_signature'] = $extracted['aa'];
                }
            } catch (Throwable $e) {
                $hydrated = rscApplyUploadedSignatures($row);
            }
        }
    }
    $mis = trim((string) ($hydrated['mis_signature'] ?? ''));
    $aa = trim((string) ($hydrated['aa_signature'] ?? ''));
    if ($mis === '' && $aa === '') {
        return $hydrated;
    }
    $crad->prepare(
        "UPDATE `crad_research_services_clearances`
         SET mis_signature = :mis,
             aa_signature = :aa,
             mis_verified = CASE WHEN TRIM(:mis_ok) <> '' THEN 1 ELSE 0 END,
             aa_verified = CASE WHEN TRIM(:aa_ok) <> '' THEN 1 ELSE 0 END,
             mis_verified_at = CASE WHEN TRIM(:mis_at) <> '' THEN COALESCE(mis_verified_at, NOW()) ELSE NULL END,
             aa_verified_at = CASE WHEN TRIM(:aa_at) <> '' THEN COALESCE(aa_verified_at, NOW()) ELSE NULL END
         WHERE id = :id"
    )->execute([
        ':mis' => $mis !== '' ? $mis : (string) ($row['mis_signature'] ?? ''),
        ':aa' => $aa !== '' ? $aa : (string) ($row['aa_signature'] ?? ''),
        ':mis_ok' => $mis,
        ':aa_ok' => $aa,
        ':mis_at' => $mis,
        ':aa_at' => $aa,
        ':id' => (int) ($row['id'] ?? 0),
    ]);
    return rscFindById($crad, (int) ($row['id'] ?? 0)) ?: $hydrated;
}

function rscUploadedFormHasPhysicalMarks(array $row): bool
{
    return trim((string) ($row['uploaded_file'] ?? '')) !== ''
        && (int) ($row['form_verified'] ?? 0) === 1;
}

function rscParseFlexibleDate(?string $value): ?int
{
    $value = trim((string) preg_replace('/\s+/', ' ', (string) $value));
    if ($value === '') {
        return null;
    }
    if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{2}|\d{4})$#', $value, $m)) {
        $year = (int) $m[3];
        if ($year < 100) {
            $year += $year >= 70 ? 1900 : 2000;
        }
        $ts = mktime(0, 0, 0, (int) $m[1], (int) $m[2], $year);
        return $ts ?: null;
    }
    $value = (string) preg_replace('/\bSept\.?\b/i', 'Sep', $value);
    $ts = strtotime($value);
    return $ts ?: null;
}

function rscMonthLabel(int $ts): string
{
    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
        9 => 'Sept', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];
    return $months[(int) date('n', $ts)] ?? date('M', $ts);
}

function rscFormatDateParts(?string $value): array
{
    $ts = rscParseFlexibleDate($value);
    if (!$ts) {
        return ['numeric' => '', 'words' => ''];
    }
    return [
        'numeric' => date('n/j/y', $ts),
        'words' => rscMonthLabel($ts) . ' ' . date('j, Y', $ts),
    ];
}

function rscFormatDate(?string $value): string
{
    $parts = rscFormatDateParts($value);
    if ($parts['numeric'] === '') {
        return '';
    }
    return $parts['numeric'] . ' / ' . $parts['words'];
}

function rscFormatDateCell(?string $value): string
{
    $parts = rscFormatDateParts($value);
    if ($parts['numeric'] === '') {
        return '';
    }
    $e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    return $e($parts['numeric']) . '<br>' . $e($parts['words']);
}

function rscDateIfSigned(string $signature, ?string ...$dates): ?string
{
    if (trim($signature) === '') {
        return null;
    }
    foreach ($dates as $date) {
        if (trim((string) $date) !== '') {
            return $date;
        }
    }
    return null;
}

function rscRenderFormHtml(array $row, bool $duplicate = true): string
{
    $row = rscAttachPaymentFields($row);
    $row = rscApplyUploadedSignatures($row);
    $copy = static function (array $row): string {
        $e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $members = rscDedupeMembers(json_decode((string) ($row['members_json'] ?? ''), true) ?: []);
        $fallbackOr = trim((string) ($row['or_number'] ?? ''));
        $remarks = trim((string) ($row['payment_remarks'] ?? '')) ?: 'HMA';
        $memberRows = '';
        foreach ($members as $member) {
            $split = rscSplitName((string) ($member['name'] ?? ''));
            $memberOr = rscMemberOrNumber((string) ($member['or_number'] ?? ''), $fallbackOr);
            $memberRows .= '<tr>'
                . '<td>' . $e($split['first']) . '</td>'
                . '<td>' . $e($split['last']) . '</td>'
                . '<td>' . $e($memberOr) . '</td>'
                . '<td>' . $e($remarks) . '</td>'
                . '</tr>';
        }
        $adviserSig = trim((string) ($row['adviser_signature'] ?? ''));
        $misSig = trim((string) ($row['mis_signature'] ?? ''));
        $aaSig = trim((string) ($row['aa_signature'] ?? ''));
        $cradSig = trim((string) ($row['crad_signature'] ?? ''));
        $adviserImg = $adviserSig !== '' ? '<img src="' . $e($adviserSig) . '" alt="Adviser signature">' : '';
        $misImg = $misSig !== '' ? '<img class="rsc-sig-ink" src="' . $e($misSig) . '" alt="MIS signature">' : '';
        $aaImg = $aaSig !== '' ? '<img class="rsc-sig-ink" src="' . $e($aaSig) . '" alt="AA signature">' : '';
        $cradImg = $cradSig !== '' ? '<img src="' . $e($cradSig) . '" alt="CRAD signature">' : '';
        $adviserDate = rscFormatDateCell($row['adviser_signed_at'] ?? null);
        $misDate = rscFormatDateCell(rscDateIfSigned($misSig, $row['mis_verified_at'] ?? null, $row['uploaded_at'] ?? null));
        $aaDate = rscFormatDateCell(rscDateIfSigned($aaSig, $row['aa_verified_at'] ?? null, $row['uploaded_at'] ?? null));
        $cradDate = rscFormatDateCell($row['crad_signed_at'] ?? null);

        return '<div class="rsc-sheet">'
            . '<div class="rsc-meta">Leader Student No.: <strong>' . $e($row['leader_student_no'] ?? '') . '</strong>'
            . ' &nbsp; Leader Group No.: <strong>' . $e($row['leader_group_no'] ?? '') . '</strong></div>'
            . '<div class="rsc-letterhead">'
            . '<div class="rsc-seal"><img src="' . $e(BASE_URL . '/images/bcp-crest.png?v=rsc-logo-1') . '" alt="Bestlink College of the Philippines logo"></div>'
            . '<div class="rsc-heading">'
            . '<div class="rsc-school">BESTLINK COLLEGE OF THE PHILIPPINES</div>'
            . '<div class="rsc-address">#1071 Brgy. Kaligayahan, Quirino Highway, Novaliches, Quezon City</div>'
            . '<div class="rsc-center">CENTER FOR RESEARCH AND DEVELOPMENT</div>'
            . '<div class="rsc-title">Research Services Clearance</div>'
            . '</div>'
            . '<div class="rsc-seal rsc-seal--right">CRD</div>'
            . '</div>'
            . '<table class="rsc-table"><tbody>'
            . '<tr><th style="width:18%">Program</th><td style="width:47%">' . $e($row['program'] ?? '') . '</td>'
            . '<th style="width:15%">Section</th><td>' . $e($row['section'] ?? '') . '</td></tr>'
            . '<tr><th>Research Title</th><td colspan="3">' . $e($row['research_title'] ?? '') . '</td></tr>'
            . '</tbody></table>'
            . '<table class="rsc-table rsc-table--members"><thead><tr>'
            . '<th>First Name</th><th>Last Name</th><th>' . $e(rscOrColumnLabel((string) ($row['research_stage'] ?? 'research_1'))) . '</th><th>Remarks</th>'
            . '</tr></thead><tbody>' . $memberRows . '</tbody></table>'
            . '<table class="rsc-table rsc-table--tasks"><thead><tr>'
            . '<th style="width:48%">Task</th><th>Name and Signature</th><th style="width:18%">Date</th>'
            . '</tr></thead><tbody>'
            . '<tr><td>1. Submitted OR Copy to Research Adviser</td>'
            . '<td>Adviser: ' . $e($row['adviser_name'] ?? '') . $adviserImg . '</td>'
            . '<td>' . $adviserDate . '</td></tr>'
            . '<tr><td>2. OR no. Verified by Accounting / MIS</td><td>MIS: ' . $misImg . '</td><td>' . $misDate . '</td></tr>'
            . '<tr><td>3. Turnitin username and Password Released by AAI / AA</td><td>AA: ' . $aaImg . '</td><td>' . $aaDate . '</td></tr>'
            . '<tr><td>4. Research Services Personnel Assignment<br>'
            . 'Grammarian: <strong>' . $e($row['grammarian_name'] ?? '') . '</strong><br>'
            . 'Statistician / Technical Adviser: <strong>' . $e(trim((string) ($row['adviser_name'] ?? $row['statistician_name'] ?? ''))) . '</strong></td>'
            . '<td>CRAD: ' . $e($row['crad_name'] ?? '') . $cradImg . '</td>'
            . '<td>' . $cradDate . '</td></tr>'
            . '</tbody></table></div>';
    };

    $html = '<div class="rsc-print-set">' . $copy($row);
    if ($duplicate) {
        $html .= $copy($row);
    }
    return $html . '</div>';
}

function rscStudentCanAccess(PDO $crad, array $clearance): bool
{
    $group = chapterRegisteredStudentGroup($crad);
    return $group && (int) $group['id'] === (int) ($clearance['research_group_id'] ?? 0);
}

function rscAdviserCanAccess(array $clearance): bool
{
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    $email = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    $name = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
    if ($uid > 0 && $uid === (int) ($clearance['adviser_user_id'] ?? 0)) {
        return true;
    }
    if ($email !== '' && $email === strtolower(trim((string) ($clearance['adviser_email'] ?? '')))) {
        return true;
    }
    return $name !== '' && $name === strtolower(trim((string) ($clearance['adviser_name'] ?? '')));
}

function rscCanManageAsCrad(): bool
{
    $role = getCurrentUserRoleKey();
    return $role === 'crad_officer' || smsIsGrantedAdminRole($role);
}

function rscRefreshExisting(PDO $crad, ?array $row): ?array
{
    if (!$row) {
        return null;
    }
    $stage = rscNormalizeStage((string) ($row['research_stage'] ?? 'research_1'));
    $fresh = rscEnsureForReadyGroup($crad, (int) ($row['research_group_id'] ?? 0), $stage);
    return $fresh ?: $row;
}

function rscRefreshRows(PDO $crad, array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        $fresh = rscRefreshExisting($crad, is_array($row) ? $row : null);
        if ($fresh) {
            $out[] = $fresh;
        }
    }
    return $out;
}

function rscListForAdviser(PDO $crad): array
{
    rscEnsureSchema($crad);
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    $email = strtolower(trim((string) ($_SESSION['user_email'] ?? '')));
    $name = strtolower(trim((string) ($_SESSION['user_name'] ?? '')));
    $stmt = $crad->prepare(
        "SELECT * FROM `crad_research_services_clearances`
         WHERE status IN ('sent_to_adviser','adviser_signed','crad_received','clearance_done')
           AND (
                (:uid > 0 AND adviser_user_id = :uid_match)
             OR (:email <> '' AND LOWER(TRIM(adviser_email)) = :email_match)
             OR (:name <> '' AND LOWER(TRIM(adviser_name)) = :name_match)
           )
         ORDER BY updated_at DESC, id DESC"
    );
    $stmt->execute([
        ':uid' => $uid,
        ':uid_match' => $uid,
        ':email' => $email,
        ':email_match' => $email,
        ':name' => $name,
        ':name_match' => $name,
    ]);
    return rscRefreshRows($crad, $stmt->fetchAll() ?: []);
}

function rscListForCrad(PDO $crad): array
{
    rscEnsureSchema($crad);
    $stmt = $crad->query(
        "SELECT * FROM `crad_research_services_clearances`
         WHERE status IN ('crad_received','adviser_signed','clearance_done')
           AND TRIM(COALESCE(uploaded_file, '')) <> ''
         ORDER BY FIELD(status,'crad_received','adviser_signed','clearance_done'), updated_at DESC"
    );
    return rscRefreshRows($crad, $stmt->fetchAll() ?: []);
}

function rscClearanceDoneExists(PDO $crad, int $groupId, string $stage = 'research_1'): bool
{
    rscEnsureSchema($crad);
    $stage = rscNormalizeStage($stage);
    $stmt = $crad->prepare(
        "SELECT 1 FROM `crad_research_services_clearances`
         WHERE research_group_id = ?
           AND research_stage = ?
           AND status = 'clearance_done'
         LIMIT 1"
    );
    $stmt->execute([$groupId, $stage]);
    return (bool) $stmt->fetchColumn();
}

function rscStudentInbox(PDO $crad, int $groupId): array
{
    $out = [];
    foreach ([['research_1', true], ['research_2', false]] as [$stage, $needChapter]) {
        $stage = rscNormalizeStage((string) $stage);
        $chapterOk = !$needChapter || rscIsChapterReady($crad, $groupId);
        $r1Done = $stage === 'research_1' || rscClearanceDoneExists($crad, $groupId, 'research_1');
        $manuscriptOk = $stage === 'research_1' || rcpIsFinalManuscriptApproved($crad, $groupId);
        $paymentOk = rscPaymentUnlocksClearance($crad, $groupId, null, $stage);
        $ready = $chapterOk && $r1Done && $manuscriptOk && $paymentOk;
        $row = null;
        if ($ready) {
            $row = rscEnsureForReadyGroup($crad, $groupId, $stage);
        } else {
            $row = rscFindByGroup($crad, $groupId, $stage);
        }
        $public = $row ? rscPublicRow($row) : [
            'id' => 0,
            'research_group_id' => $groupId,
            'research_stage' => $stage,
            'stage_label' => rscStageLabel($stage),
            'status' => '',
            'status_label' => 'Not available yet',
            'or_number' => '',
            'form_html' => '',
            'leader_group_no' => '',
            'research_title' => '',
        ];
        // The inbox O.R. cell must always reflect the current Admin-approved
        // payment for its own research stage, even before a form is opened.
        $approvedPayment = rscApprovedPayment($crad, $groupId, $stage);
        if ($approvedPayment) {
            $approvedReference = trim((string) ($approvedPayment['or_number'] ?? ''));
            if ($approvedReference !== '') {
                $public['or_number'] = $approvedReference;
            }
            // Use the exact same named references visible to Admin.
            if (function_exists('rcpGroupPaymentMembers')) {
                $public['members'] = rcpGroupPaymentMembers($crad, $groupId, $stage);
            }
        }
        // One name + O.R. number per approved researcher, exactly as approved
        // by Admin, so the inbox never collapses them into a single reference.
        $public['or_list'] = $approvedPayment ? rscApprovedOrList($public) : [];
        $locked = '';
        if ($stage === 'research_2' && !rscClearanceDoneExists($crad, $groupId, 'research_1')) {
            $locked = 'Finish Research 1 clearance first.';
        } elseif ($stage === 'research_2' && !$manuscriptOk) {
            $locked = 'Final Manuscript must be approved before Research 2 clearance.';
        } elseif ($needChapter && !$chapterOk) {
            $locked = 'Chapter 1-3 must be scored first.';
        } elseif (!$paymentOk) {
            $locked = 'Upload and wait for Admin approval of ' . rscStageLabel($stage) . ' collage payment.';
        }
        $public['ready'] = $ready;
        $public['locked_reason'] = $locked;
        $out[] = $public;
    }
    return $out;
}
