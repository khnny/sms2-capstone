<?php
declare(strict_types=1);

function rcpNormalizeStage(string $stage): string
{
    return strtolower(trim($stage)) === 'research_2' ? 'research_2' : 'research_1';
}

function rcpStageLabel(string $stage): string
{
    return rcpNormalizeStage($stage) === 'research_2' ? 'Research 2' : 'Research 1';
}

function rcpStageList(): array
{
    return [
        ['key' => 'research_1', 'label' => 'Research 1'],
        ['key' => 'research_2', 'label' => 'Research 2'],
    ];
}

function rcpEnsureSchema(PDO $crad): void
{
    // Schema is deployment-owned by modules/crad/database/crad_db.sql.
    // Do not run CREATE/ALTER TABLE during a web request.
    return;

    $crad->exec(
        "CREATE TABLE IF NOT EXISTS `crad_research_clearance_payments` (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            research_group_id INT UNSIGNED NOT NULL,
            research_stage VARCHAR(20) NOT NULL DEFAULT 'research_1',
            student_user_id INT UNSIGNED DEFAULT NULL,
            uploaded_file VARCHAR(255) NOT NULL DEFAULT '',
            uploaded_original VARCHAR(255) NOT NULL DEFAULT '',
            or_number VARCHAR(80) NOT NULL DEFAULT '',
            remarks VARCHAR(120) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            approved_by_user_id INT UNSIGNED DEFAULT NULL,
            approved_by_name VARCHAR(160) NOT NULL DEFAULT '',
            approved_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_rcp_group_stage (research_group_id, research_stage),
            KEY idx_rcp_group (research_group_id),
            KEY idx_rcp_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    try {
        if (!$crad->query("SHOW COLUMNS FROM `crad_research_clearance_payments` LIKE 'research_stage'")->fetch()) {
            $crad->exec(
                "ALTER TABLE `crad_research_clearance_payments`
                 ADD COLUMN research_stage VARCHAR(20) NOT NULL DEFAULT 'research_1' AFTER research_group_id"
            );
        }
        $crad->exec("UPDATE `crad_research_clearance_payments` SET research_stage = 'research_1' WHERE TRIM(COALESCE(research_stage, '')) = ''");
        $indexes = $crad->query("SHOW INDEX FROM `crad_research_clearance_payments`")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $hasStageUnique = false;
        foreach ($indexes as $idx) {
            if (($idx['Key_name'] ?? '') === 'uniq_rcp_group_stage') {
                $hasStageUnique = true;
                break;
            }
        }
        if (!$hasStageUnique) {
            try {
                $crad->exec('ALTER TABLE `crad_research_clearance_payments` DROP INDEX uniq_rcp_group');
            } catch (Throwable $e) {
                // optional legacy index
            }
            $crad->exec(
                'ALTER TABLE `crad_research_clearance_payments`
                 ADD UNIQUE KEY uniq_rcp_group_stage (research_group_id, research_stage)'
            );
        }
    } catch (Throwable $e) {
        error_log('rcp schema stage: ' . $e->getMessage());
    }
}

function rcpFindByGroup(PDO $crad, int $groupId, string $stage = 'research_1'): ?array
{
    if ($groupId <= 0) {
        return null;
    }
    $stage = rcpNormalizeStage($stage);
    $stmt = $crad->prepare(
        "SELECT * FROM `crad_research_clearance_payments`
         WHERE research_group_id = ? AND research_stage = ?
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([$groupId, $stage]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rcpFindById(PDO $crad, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $crad->prepare('SELECT * FROM `crad_research_clearance_payments` WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function rcpIsApproved(PDO $crad, int $groupId, string $stage = 'research_1'): bool
{
    $row = rcpFindByGroup($crad, $groupId, $stage);
    return $row && (string) ($row['status'] ?? '') === 'approved';
}

function rcpUploadPublicUrl(array $row): string
{
    $file = basename(str_replace('\\', '/', trim((string) ($row['uploaded_file'] ?? ''))));
    if ($file === '' || $file === '.' || $file === '..') {
        return '';
    }
    $stamp = strtotime((string) ($row['updated_at'] ?? $row['created_at'] ?? '')) ?: time();
    return BASE_URL . '/modules/crad/api/clearance-payment-file.php?id=' . (int) ($row['id'] ?? 0) . '&v=' . $stamp;
}

function rcpStatusLabel(string $status): string
{
    return match ($status) {
        'draft' => 'Enter Reference / O.R. Numbers',
        'pending' => 'Waiting for Admin approval',
        'approved' => 'Approved',
        'rejected' => 'Returned — upload again',
        default => $status,
    };
}

function rcpPublicRow(array $row): array
{
    $stage = rcpNormalizeStage((string) ($row['research_stage'] ?? 'research_1'));
    return [
        'id' => (int) ($row['id'] ?? 0),
        'research_group_id' => (int) ($row['research_group_id'] ?? 0),
        'research_stage' => $stage,
        'stage_label' => rcpStageLabel($stage),
        'status' => (string) ($row['status'] ?? ''),
        'status_label' => rcpStatusLabel((string) ($row['status'] ?? '')),
        'or_number' => (string) ($row['or_number'] ?? ''),
        'receipt_student_name' => (string) ($row['receipt_student_name'] ?? ''),
        'remarks' => (string) ($row['remarks'] ?? ''),
        'uploaded_original' => (string) ($row['uploaded_original'] ?? ''),
        'uploaded_url' => rcpUploadPublicUrl($row),
        'has_upload' => trim((string) ($row['uploaded_file'] ?? '')) !== '',
        'approved_by_name' => (string) ($row['approved_by_name'] ?? ''),
        'approved_at' => (string) ($row['approved_at'] ?? ''),
        'updated_at' => (string) ($row['updated_at'] ?? ''),
        'can_upload' => !empty($row['can_upload']),
        'locked_reason' => (string) ($row['locked_reason'] ?? ''),
        'members' => is_array($row['members'] ?? null) ? $row['members'] : [],
    ];
}

/**
 * Does the stage column exist? Older deployments have not been migrated yet.
 */
function rcpMemberStageScoped(PDO $crad): bool
{
    static $scoped = null;
    if ($scoped !== null) {
        return $scoped;
    }
    try {
        // SHOW COLUMNS cannot bind a parameter under native prepares, so the
        // known column name is interpolated as a literal.
        $scoped = (bool) $crad->query("SHOW COLUMNS FROM `crad_research_group_members` LIKE 'research_stage'")->fetchColumn();
    } catch (Throwable $e) {
        error_log('rcpMemberStageScoped: ' . $e->getMessage());
        $scoped = false;
    }
    return $scoped;
}

/**
 * Approved researchers for one research stage (one receipt reference each).
 *
 * Research 1 and Research 2 are separate collage payments, so each stage keeps
 * its own references. A stage with no upload yet therefore reports no numbers.
 */
function rcpGroupPaymentMembers(PDO $crad, int $groupId, string $stage = 'research_1'): array
{
    if ($groupId < 1) return [];
    $stage = rcpNormalizeStage($stage);
    $scoped = rcpMemberStageScoped($crad);
    try {
        // A research group may submit at most five approved researchers for
        // this clearance payment. The order is the approved group roster.
        $sql = "SELECT id, full_name, student_id, or_number FROM `crad_research_group_members`"
            . ($scoped ? ' WHERE research_group_id = ? AND research_stage = ?' : ' WHERE research_group_id = ?')
            . ' ORDER BY member_order ASC, id ASC LIMIT 5';
        $stmt = $crad->prepare($sql);
        $stmt->execute($scoped ? [$groupId, $stage] : [$groupId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if ($rows === [] && function_exists('rscLoadGroupContext') && function_exists('rscMembersFromGroup')) {
            // Legacy approved titles may predate the group-member table. Seed
            // the same approved roster for this stage so each researcher can
            // retain an individual reference number.
            $group = rscLoadGroupContext($crad, $groupId);
            $roster = $group ? rscMembersFromGroup($crad, $group) : [];
            $insertSql = $scoped
                ? 'INSERT INTO `crad_research_group_members`
                     (research_group_id, research_stage, member_order, student_id, full_name, section, email, or_number, is_leader)
                   VALUES (:gid, :stage, :ord, :sid, :name, \'\', \'\', \'\', :leader)'
                : 'INSERT INTO `crad_research_group_members`
                     (research_group_id, member_order, student_id, full_name, section, email, or_number, is_leader)
                   VALUES (:gid, :ord, :sid, :name, \'\', \'\', \'\', :leader)';
            $insert = $crad->prepare($insertSql);
            foreach (array_slice($roster, 0, 5) as $order => $member) {
                $params = [
                    ':gid'    => (int) $groupId,
                    ':ord'    => $order + 1,
                    ':sid'    => (string) ($member['student_id'] ?? ''),
                    ':name'   => (string) ($member['name'] ?? ''),
                    ':leader' => $order === 0 ? 1 : 0,
                ];
                if ($scoped) {
                    $params[':stage'] = $stage;
                }
                $insert->execute($params);
            }
            $stmt->execute($scoped ? [$groupId, $stage] : [$groupId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }
        return array_map(static fn (array $member): array => [
            'id' => (int) ($member['id'] ?? 0), 'name' => (string) ($member['full_name'] ?? ''),
            'student_id' => (string) ($member['student_id'] ?? ''), 'or_number' => (string) ($member['or_number'] ?? ''),
        ], $rows);
    } catch (Throwable $e) { return []; }
}

function rcpSaveMemberReferences(PDO $crad, int $groupId, array $submitted, string $stage = 'research_1'): array
{
    $stage = rcpNormalizeStage($stage);
    $members = rcpGroupPaymentMembers($crad, $groupId, $stage);
    if ($members === []) return ['ok' => false, 'error' => 'No approved research members were found.'];
    $references = [];
    foreach ($members as $member) {
        $id = (int) $member['id'];
        $reference = strtoupper(trim((string) ($submitted[(string) $id] ?? '')));
        if ($reference === '' || strlen($reference) > 80) return ['ok' => false, 'error' => 'Enter one Reference / O.R. Number for every approved researcher.'];
        $references[$id] = $reference;
    }
    $save = $crad->prepare('UPDATE `crad_research_group_members` SET or_number = ? WHERE id = ? AND research_group_id = ?');
    foreach ($references as $id => $reference) $save->execute([$reference, $id, $groupId]);
    return ['ok' => true, 'references' => $references];
}

/**
 * Words that sit near a "Reference No." label on a receipt but are never the
 * reference itself. Windows OCR frequently returns the labels as one run and
 * the values as another, so a naive "label then next word" match picks these up.
 */
function rcpReferenceStopWords(): array
{
    return [
        'AMOUNT', 'AMOUNTS', 'TOTAL', 'TOTALS', 'SUBTOTAL', 'BALANCE', 'DATE', 'DATES',
        'CASH', 'PAID', 'CHANGE', 'REFERENCE', 'REFERENCES', 'RECEIPT',
        'RECEIPTS', 'OFFICIAL', 'TRANSACTION', 'NOTE', 'NOTES', 'REMARKS', 'BILLER',
        'NAME', 'STUDENT', 'NUMBER', 'AND', 'THE', 'FOR', 'WITH',
        'ACCOUNT', 'ACCOUNTANT', 'CASHIER', 'INVOICE', 'PARTICULARS', 'DESCRIPTION',
        'QTY', 'QUANTITY', 'NET', 'VAT', 'TAX', 'DISCOUNT', 'MODE', 'PAYMENT',
    ];
}

/**
 * Does this token look like a real college receipt reference?
 */
function rcpIsPlausibleReference(string $token): bool
{
    $token = strtoupper(trim($token));
    if ($token === '' || strlen($token) < 4 || strlen($token) > 80) {
        return false;
    }
    // Currency, dates, amounts, and bare student numbers are never references.
    if (preg_match('/^(?:PHP|USD|P)$/i', $token)) {
        return false;
    }
    if (preg_match('/^\$?\d{1,3}(?:,\d{3})*(?:\.\d{1,2})?$/', $token)) {
        return false;
    }
    if (preg_match('/^\d{1,4}[-\/]\d{1,2}[-\/]\d{1,4}$/', $token)) {
        return false;
    }
    if (preg_match('/^S?\d{6,}$/', $token)) {
        return false;
    }
    if (in_array($token, rcpReferenceStopWords(), true)) {
        return false;
    }
    if (preg_match('/^OR[-\/][A-Z0-9]{3,}$/', $token)) {
        return true;
    }
    // Real references mix letters and digits.
    return (bool) preg_match('/[A-Z]/', $token) && (bool) preg_match('/\d/', $token);
}

/**
 * Every plausible reference in the text, best first.
 *
 * @return array<int, string>
 */
function rcpReferenceCandidates(string $text): array
{
    $normalized = strtoupper(str_ireplace(['0R NO', '0R NUMBER'], ['OR NO', 'OR NUMBER'], $text));
    $candidates = [];

    // Strong, self-identifying references anywhere in the text (HelloMoney etc).
    if (preg_match_all('/\b(HMBP[-\/]?[A-Z0-9]{6,})\b/', $normalized, $m)) {
        foreach ($m[1] as $hit) {
            $candidates[] = strtoupper($hit);
        }
    }
    if (preg_match_all('/\b(OR[-\/][A-Z0-9\-]{3,})\b/', $normalized, $m)) {
        foreach ($m[1] as $hit) {
            $candidates[] = strtoupper($hit);
        }
    }

    // Label-adjacent value, accepted only when the value itself is plausible.
    // OCR often emits "Reference No. Amount :" with the real value elsewhere.
    $label = '(?:O\\s*R|OFFICIAL\\s+RECEIPT|RECEIPT|REFERENCE|TRANSACTION\\s+(?:REFERENCE|NO\\.?|NUMBER|ID))\\s*(?:NO\\.?|NUMBER|#|ID)?';
    if (preg_match_all('/' . $label . '\\s*[:#-]?\\s*([A-Z0-9][A-Z0-9\\-\\/]{3,79})\\b/i', $normalized, $m)) {
        foreach ($m[1] as $hit) {
            $hit = strtoupper(trim((string) $hit));
            if (rcpIsPlausibleReference($hit)) {
                $candidates[] = $hit;
            }
        }
    }

    // Last resort: any standalone token that looks like a reference.
    if (preg_match_all('/\b([A-Z0-9][A-Z0-9\-]{5,39})\b/', $normalized, $m)) {
        foreach ($m[1] as $hit) {
            $hit = strtoupper(trim((string) $hit));
            if (rcpIsPlausibleReference($hit)) {
                $candidates[] = $hit;
            }
        }
    }

    // Strongest references win: real ones carry more entropy than stray words.
    $unique = array_values(array_unique($candidates));
    usort($unique, static function (string $a, string $b): int {
        $score = static function (string $v): int {
            return (str_contains($v, 'HMBP') ? 1000 : 0)
                + (str_starts_with($v, 'OR') ? 500 : 0)
                + strlen($v);
        };
        return $score($b) <=> $score($a);
    });
    return $unique;
}

/** @return array{reference_number: string, student_name: string} */
function rcpParseReceiptDetails(string $source): array
{
    $text = trim(preg_replace('/\s+/', ' ', trim($source)) ?? '');
    $details = ['reference_number' => '', 'student_name' => ''];
    if ($text === '') return $details;

    $candidates = rcpReferenceCandidates($text);
    if ($candidates !== []) {
        $details['reference_number'] = $candidates[0];
    }

    // Receipts commonly place the name on the line after "Student Name".
    $lines = preg_split('/\R/', $source) ?: [];
    foreach ($lines as $index => $line) {
        if (!preg_match('/^\s*STUDENT\s*NAME\s*[:\-]?\s*(.*)$/i', trim($line), $m)) continue;
        $name = trim((string) $m[1]);
        if ($name === '' && isset($lines[$index + 1])) $name = trim((string) $lines[$index + 1]);
        if ($name !== '') {
            $details['student_name'] = $name;
            break;
        }
    }
    if ($details['student_name'] === '' && preg_match('/\bSTUDENT\s*NAME\s*[:\-]?\s*(.+?)(?=\s+(?:STUDENT\s*(?:NUMBER|NO\.?|ID)|PAYMENT\s+FOR|BILLER|AMOUNT|REMARKS)\b|$)/i', $text, $m)) {
        $details['student_name'] = trim($m[1]);
    }
    $details['student_name'] = trim(preg_replace('/[^\p{L} .\'\-]/u', '', $details['student_name']) ?? '');
    if (!preg_match('/\p{L}/u', $details['student_name'])) {
        $details['student_name'] = '';
        return $details;
    }

    // OCR can emit the receipt reference inside the name run. Drop any token
    // that is really a reference, a stop word, or a lone initial so the
    // approver sees a readable name for validation.
    $nameTokens = preg_split('/\s+/', $details['student_name']) ?: [];
    $kept = [];
    // Bare machine tokens that OCR leaves in the name run.
    $codePrefixes = ['HMBP', 'OR', 'REF', 'TXN', 'PHP', 'USD'];
    foreach ($nameTokens as $token) {
        $bare = strtoupper(rtrim($token, '.-'));
        if ($bare === '' || $bare === '&') {
            continue;
        }
        if (strlen($bare) < 2 && !preg_match('/^[A-Z]$/i', $bare)) {
            continue;
        }
        if (in_array($bare, $codePrefixes, true)) {
            continue;
        }
        if (rcpIsPlausibleReference($bare) || in_array($bare, rcpReferenceStopWords(), true)) {
            continue;
        }
        $kept[] = $token;
    }
    // Keep at most the two name-like words that follow the label.
    $details['student_name'] = trim(implode(' ', array_slice($kept, 0, 3)));
    if (!preg_match('/\p{L}/u', $details['student_name'])) {
        $details['student_name'] = '';
    }
    return $details;
}

function rcpParseReferenceNumber(string $text): string
{
    return rcpParseReceiptDetails($text)['reference_number'];
}

function rcpOcrImageText(string $path): string
{
    $script = ROOT_PATH . '/modules/crad/includes/win-ocr.ps1';
    if ($path === '' || !is_file($path) || !is_file($script) || !function_exists('exec')) {
        return '';
    }
    $real = realpath($path) ?: $path;
    $tmpDir = sys_get_temp_dir();
    $token = bin2hex(random_bytes(4));
    $outFile = $tmpDir . DIRECTORY_SEPARATOR . 'rcp-ocr-' . $token . '.txt';
    $jobFile = $tmpDir . DIRECTORY_SEPARATOR . 'rcp-ocr-job-' . $token . '.json';
    file_put_contents($jobFile, json_encode(['image' => $real, 'out' => $outFile], JSON_UNESCAPED_SLASHES));
    $out = [];
    $code = 0;
    $text = '';
    if (PHP_OS_FAMILY === 'Windows') {
        $ps = 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
        if (!is_file($ps)) {
            $ps = 'powershell';
        }
        $cmd = $ps
            . ' -NoProfile -ExecutionPolicy Bypass -File '
            . escapeshellarg($script)
            . ' -JobFile '
            . escapeshellarg($jobFile);
        @exec($cmd, $out, $code);
        $text = is_file($outFile) ? trim((string) @file_get_contents($outFile)) : '';
    } else {
        // Linux production hosts do not provide Windows.Media.Ocr. Reuse the
        // same OCR step through the host's installed Tesseract executable.
        @exec('command -v tesseract 2>/dev/null', $out, $code);
        if ($code === 0 && !empty($out[0])) {
            $out = [];
            @exec(escapeshellcmd(trim((string) $out[0])) . ' ' . escapeshellarg($real) . ' stdout -l eng 2>/dev/null', $out, $code);
            $text = trim(implode("\n", $out));
        }
    }
    @unlink($outFile);
    @unlink($jobFile);
    if ($text === '') {
        $text = trim(implode(' ', $out));
    }
    return $text;
}

function rcpExtractReferenceFromImage(string $path): string
{
    return rcpExtractReceiptDetailsFromImage($path)['reference_number'];
}

/** @return array{reference_number: string, student_name: string} */
function rcpExtractReceiptDetailsFromImage(string $path): array
{
    return rcpParseReceiptDetails(rcpOcrImageText($path));
}

function rcpPaymentImagePath(string $file): ?string
{
    $normalized = str_replace('\\', '/', trim($file));
    $file = basename($normalized);
    if ($file === '' || $file === '.' || $file === '..') {
        return null;
    }
    $candidates = [
        ROOT_PATH . '/storage/uploads/college-payment/' . $file,
        ROOT_PATH . '/uploads/college-payment/' . $file,
    ];
    if (preg_match('#(?:^|/)storage/uploads/college-payment/([^/]+)$#i', $normalized, $match)) {
        $candidates[] = ROOT_PATH . '/storage/uploads/college-payment/' . basename($match[1]);
    }
    if (preg_match('#(?:^|/)uploads/college-payment/([^/]+)$#i', $normalized, $match)) {
        $candidates[] = ROOT_PATH . '/uploads/college-payment/' . basename($match[1]);
    }
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return null;
}

/** @return array{data: string, mime: string}|null */
function rcpPersistentImageData(array $row): ?array
{
    $data = $row['uploaded_blob'] ?? null;
    $mime = strtolower(trim((string) ($row['uploaded_mime'] ?? '')));
    if (!is_string($data) || $data === '' || !in_array($mime, ['image/png', 'image/jpeg'], true)) {
        return null;
    }
    return ['data' => $data, 'mime' => $mime];
}

function rcpEnsureOrFromImage(PDO $crad, array $row): array
{
    $or = trim((string) ($row['or_number'] ?? ''));
    $receiptStudentName = trim((string) ($row['receipt_student_name'] ?? ''));
    if ($or !== '' && !preg_match('/^OR-\d+$/i', $or) && $receiptStudentName !== '') {
        return $row;
    }
    $file = basename(str_replace('\\', '/', (string) ($row['uploaded_file'] ?? '')));
    if ($file === '') {
        return $row;
    }
    $lock = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rcp-or-lock-' . (int) ($row['id'] ?? 0);
    if (is_file($lock) && (time() - (int) filemtime($lock)) < 30) {
        return $row;
    }
    @touch($lock);
    $imagePath = rcpPaymentImagePath($file);
    $details = $imagePath ? rcpExtractReceiptDetailsFromImage($imagePath) : ['reference_number' => '', 'student_name' => ''];
    $extracted = $details['reference_number'];
    if ($extracted !== '' && strcasecmp($extracted, $or) !== 0) {
        $crad->prepare('UPDATE `crad_research_clearance_payments` SET or_number = ? WHERE id = ?')
            ->execute([$extracted, (int) ($row['id'] ?? 0)]);
        $row['or_number'] = $extracted;
    }
    // This is shown to the approver for validation; it never changes the
    // authenticated student or the assigned research group.
    $row['receipt_student_name'] = $details['student_name'];
    return $row;
}

function rcpIsFinalManuscriptApproved(PDO $crad, int $groupId): bool
{
    if ($groupId <= 0) {
        return false;
    }
    if (function_exists('fpIsManuscriptApproved')) {
        return fpIsManuscriptApproved($crad, $groupId);
    }
    try {
        $stmt = $crad->prepare(
            "SELECT ms.status AS ms_status, me.result AS me_result
             FROM `crad_manuscript_submissions` ms
             LEFT JOIN `crad_manuscript_evaluations` me ON me.id = (
                SELECT me2.id FROM `crad_manuscript_evaluations` me2
                WHERE me2.submission_id = ms.id
                ORDER BY me2.id DESC
                LIMIT 1
             )
             WHERE ms.research_group_id = ?
             ORDER BY ms.version_number DESC, ms.id DESC
             LIMIT 1"
        );
        $stmt->execute([$groupId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        return (string) ($row['ms_status'] ?? '') === 'Approved'
            && strtoupper(trim((string) ($row['me_result'] ?? ''))) === 'APPROVED';
    } catch (Throwable $e) {
        return false;
    }
}

function rcpCanUploadStage(PDO $crad, int $groupId, string $stage): array
{
    $stage = rcpNormalizeStage($stage);
    if ($stage === 'research_1') {
        return ['ok' => true, 'reason' => ''];
    }
    if (!function_exists('rscClearanceDoneExists') || !rscClearanceDoneExists($crad, $groupId, 'research_1')) {
        return [
            'ok' => false,
            'reason' => 'Finish Research 1 clearance (Pre-Oral) before uploading Research 2 collage payment.',
        ];
    }
    if (!rcpIsFinalManuscriptApproved($crad, $groupId)) {
        return [
            'ok' => false,
            'reason' => 'Final Manuscript must be approved before uploading Research 2 collage payment.',
        ];
    }
    return ['ok' => true, 'reason' => ''];
}

function rcpStoreUpload(int $groupId, array $file): array
{
    $code = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($code !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Choose a PNG or JPG picture of the collage payment.'];
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $name = (string) ($file['name'] ?? 'payment.png');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }
    $info = @getimagesize($tmp);
    $mime = strtolower((string) ($info['mime'] ?? ''));
    if (!in_array($mime, ['image/png', 'image/jpeg'], true)) {
        return ['ok' => false, 'error' => 'Upload a PNG or JPG picture of the collage payment.'];
    }
    $ext = $mime === 'image/png' ? 'png' : 'jpg';
    $data = @file_get_contents($tmp);
    if (!is_string($data) || $data === '') {
        return ['ok' => false, 'error' => 'The payment image could not be read.'];
    }
    $stored = 'rcp-' . $groupId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    return ['ok' => true, 'file' => $stored, 'original' => $name, 'path' => $tmp, 'data' => $data, 'mime' => $mime, 'size' => strlen($data)];
}

/**
 * Every reference found on a receipt image, in reading order.
 *
 * @return array<int, string>
 */
function rcpScannedReferences(string $imagePath): array
{
    $text = rcpOcrImageText($imagePath);
    if (trim($text) === '') {
        return [];
    }
    return rcpReferenceCandidates($text);
}

/**
 * Fill this stage's per-researcher references from a receipt scan.
 *
 * A receipt normally lists one reference per researcher, so the scanned values
 * are assigned in order. Only blank fields are written, so a value the student
 * already typed is never overwritten. Anything the scan could not read stays
 * blank for manual entry.
 *
 * @return array{assigned: int, blank: int, references: array<int, string>}
 */
function rcpApplyScannedMemberReferences(PDO $crad, int $groupId, string $stage, array $scanned): array
{
    $members = rcpGroupPaymentMembers($crad, $groupId, $stage);
    if ($members === []) {
        return ['assigned' => 0, 'blank' => 0, 'references' => []];
    }
    $save = $crad->prepare(
        'UPDATE `crad_research_group_members` SET or_number = ? WHERE id = ? AND research_group_id = ?'
    );
    $assigned = 0;
    $blank = 0;
    $references = [];
    $cursor = 0;
    foreach ($members as $member) {
        $id = (int) $member['id'];
        $current = trim((string) ($member['or_number'] ?? ''));
        if ($current !== '') {
            // Already known for this stage; keep it and resync the scan cursor.
            $references[] = $current;
            $cursor++;
            continue;
        }
        $next = trim((string) ($scanned[$cursor] ?? ''));
        if ($next !== '') {
            $save->execute([strtoupper($next), $id, $groupId]);
            $references[] = strtoupper($next);
            $assigned++;
        } else {
            $blank++;
        }
        $cursor++;
    }
    return ['assigned' => $assigned, 'blank' => $blank, 'references' => $references];
}

function rcpStudentUpload(PDO $crad, int $groupId, array $file, string $orNumber = '', string $stage = 'research_1'): array
{
    rcpEnsureSchema($crad);
    $stage = rcpNormalizeStage($stage);
    if ($groupId <= 0) {
        return ['ok' => false, 'error' => 'No research group is registered for this student.'];
    }
    $gate = rcpCanUploadStage($crad, $groupId, $stage);
    if (empty($gate['ok'])) {
        return ['ok' => false, 'error' => (string) ($gate['reason'] ?? 'This payment stage is locked.')];
    }
    $saved = rcpStoreUpload($groupId, $file);
    if (empty($saved['ok'])) {
        return $saved;
    }
    $existing = rcpFindByGroup($crad, $groupId, $stage);
    $details = rcpExtractReceiptDetailsFromImage((string) ($saved['path'] ?? ''));
    $or = $details['reference_number'];
    if ($or === '') {
        $typed = strtoupper(trim($orNumber));
        $or = rcpParseReferenceNumber($typed) ?: $typed;
    }
    if ($existing && (string) ($existing['status'] ?? '') === 'approved') {
        return ['ok' => false, 'error' => rcpStageLabel($stage) . ' collage payment is already approved.'];
    }
    if ($existing) {
        $old = basename(str_replace('\\', '/', (string) ($existing['uploaded_file'] ?? '')));
        $crad->prepare(
            "UPDATE `crad_research_clearance_payments`
             SET uploaded_file = :file,
                 uploaded_blob = :blob,
                 uploaded_mime = :mime,
                 uploaded_size = :size,
                 uploaded_original = :original,
                 or_number = :or_number,
                 research_stage = :stage,
                 status = 'draft',
                 approved_by_user_id = NULL,
                 approved_by_name = '',
                 approved_at = NULL
             WHERE id = :id"
        )->execute([
            ':file' => (string) $saved['file'],
            ':blob' => (string) $saved['data'],
            ':mime' => (string) $saved['mime'],
            ':size' => (int) $saved['size'],
            ':original' => (string) $saved['original'],
            ':or_number' => $or,
            ':stage' => $stage,
            ':id' => (int) $existing['id'],
        ]);
        if ($old !== '' && $old !== (string) $saved['file']) {
            foreach ([
                ROOT_PATH . '/storage/uploads/college-payment/' . $old,
                ROOT_PATH . '/uploads/college-payment/' . $old,
            ] as $oldPath) {
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }
        $fresh = rcpFindById($crad, (int) $existing['id']);
    } else {
        $crad->prepare(
            "INSERT INTO `crad_research_clearance_payments`
                (research_group_id, research_stage, student_user_id, uploaded_file, uploaded_blob, uploaded_mime, uploaded_size, uploaded_original, or_number, remarks, status)
             VALUES
                (:gid, :stage, :uid, :file, :blob, :mime, :size, :original, :or_number, '', 'draft')"
        )->execute([
            ':gid' => $groupId,
            ':stage' => $stage,
            ':uid' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
            ':file' => (string) $saved['file'],
            ':blob' => (string) $saved['data'],
            ':mime' => (string) $saved['mime'],
            ':size' => (int) $saved['size'],
            ':original' => (string) $saved['original'],
            ':or_number' => $or,
        ]);
        $fresh = rcpFindById($crad, (int) $crad->lastInsertId());
    }
    // Autoscan: put every reference the receipt shows onto this stage's
    // approved researchers, in order. Fields the scan cannot read stay blank
    // for the student to type manually.
    $scan = rcpApplyScannedMemberReferences(
        $crad,
        $groupId,
        $stage,
        rcpScannedReferences((string) ($saved['path'] ?? ''))
    );
    if ($fresh && $or === '' && $scan['references'] !== []) {
        $or = (string) $scan['references'][0];
    }
    if ($fresh && $or !== '') {
        $crad->prepare('UPDATE `crad_research_clearance_payments` SET or_number = ? WHERE id = ?')
            ->execute([$or, (int) $fresh['id']]);
        $fresh['or_number'] = $or;
    }
    if ($fresh) {
        // Send the OCR result back immediately after upload. On later page
        // loads it is safely re-read from the original payment image.
        $fresh['receipt_student_name'] = $details['student_name'];
        $fresh['members'] = rcpGroupPaymentMembers($crad, $groupId, $stage);
        $fresh['scanned_assigned'] = (int) $scan['assigned'];
        $fresh['scanned_blank'] = (int) $scan['blank'];
    }
    if (function_exists('logActivity')) {
        logActivity(
            'create',
            'Submitted ' . rcpStageLabel($stage) . ' collage payment for research group #' . $groupId,
            'crad'
        );
    }
    return ['ok' => true, 'payment' => $fresh];
}

function rcpStudentUpdateOr(PDO $crad, int $groupId, string $stage, string $orNumber): array
{
    $row = rcpFindByGroup($crad, $groupId, $stage);
    $orNumber = trim($orNumber);
    if (!$row || (string) ($row['status'] ?? '') === 'approved') {
        return ['ok' => false, 'error' => 'This payment can no longer be updated.'];
    }
    if ($orNumber === '' || strlen($orNumber) > 80) {
        return ['ok' => false, 'error' => 'Enter the O.R. number exactly as printed on the receipt.'];
    }
    $crad->prepare("UPDATE `crad_research_clearance_payments` SET or_number = ?, status = 'pending' WHERE id = ?")
        ->execute([$orNumber, (int) $row['id']]);
    if (function_exists('logActivity')) {
        logActivity(
            'update',
            'Updated ' . rcpStageLabel($stage) . ' payment reference number for research group #' . $groupId,
            'crad'
        );
    }
    $fresh = rcpFindById($crad, (int) $row['id']);
    if ($fresh) $fresh['members'] = rcpGroupPaymentMembers($crad, $groupId, rcpNormalizeStage((string) ($row['research_stage'] ?? 'research_1')));
    return ['ok' => true, 'payment' => $fresh];
}

function rcpApplyToClearance(PDO $crad, int $groupId, string $orNumber, string $remarks, string $stage = 'research_1'): void
{
    $stage = rcpNormalizeStage($stage);
    $clearance = function_exists('rscFindByGroup') ? rscFindByGroup($crad, $groupId, $stage) : null;
    if (!$clearance) {
        return;
    }
    $members = json_decode((string) ($clearance['members_json'] ?? ''), true) ?: [];
    if (is_array($members)) {
        $savedReferences = rcpGroupPaymentMembers($crad, $groupId, $stage);
        $referencesByStudent = [];
        $referencesByName = [];
        foreach ($savedReferences as $savedReference) {
            $reference = trim((string) ($savedReference['or_number'] ?? ''));
            $studentId = strtolower(trim((string) ($savedReference['student_id'] ?? '')));
            $name = strtolower(trim(preg_replace('/\s+/', ' ', (string) ($savedReference['name'] ?? '')) ?? ''));
            if ($reference === '') {
                continue;
            }
            if ($studentId !== '') {
                $referencesByStudent[$studentId] = $reference;
            }
            if ($name !== '') {
                $referencesByName[$name] = $reference;
            }
        }
        foreach ($members as &$member) {
            if (is_array($member)) {
                $studentId = strtolower(trim((string) ($member['student_id'] ?? '')));
                $name = strtolower(trim(preg_replace('/\s+/', ' ', (string) ($member['name'] ?? '')) ?? ''));
                $member['or_number'] = (string) (
                    ($studentId !== '' ? ($referencesByStudent[$studentId] ?? null) : null)
                    ?? ($name !== '' ? ($referencesByName[$name] ?? null) : null)
                    ?? $orNumber
                );
            }
        }
        unset($member);
    }
    $crad->prepare(
        "UPDATE `crad_research_services_clearances`
         SET or_number = :or_number,
             members_json = :members
         WHERE id = :id"
    )->execute([
        ':or_number' => $orNumber,
        ':members' => json_encode($members, JSON_UNESCAPED_UNICODE),
        ':id' => (int) $clearance['id'],
    ]);
}

function rcpAdminApprove(PDO $crad, array $payment, string $orNumber, string $remarks): array
{
    $or = trim($orNumber) !== '' ? trim($orNumber) : trim((string) ($payment['or_number'] ?? ''));
    $note = trim($remarks) !== '' ? trim($remarks) : 'HMA';
    $stage = rcpNormalizeStage((string) ($payment['research_stage'] ?? 'research_1'));
    if ($or === '') {
        return ['ok' => false, 'error' => 'Enter the O.R. number from the college payment.'];
    }
    $crad->prepare(
        "UPDATE `crad_research_clearance_payments`
         SET status = 'approved',
             or_number = :or_number,
             remarks = :remarks,
             approved_by_user_id = :uid,
             approved_by_name = :name,
             approved_at = NOW()
         WHERE id = :id"
    )->execute([
        ':or_number' => $or,
        ':remarks' => $note,
        ':uid' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
        ':name' => (string) (function_exists('getCurrentUserName') ? getCurrentUserName() : ''),
        ':id' => (int) $payment['id'],
    ]);
    rcpApplyToClearance($crad, (int) $payment['research_group_id'], $or, $note, $stage);
    if (function_exists('rscEnsureForReadyGroup')) {
        rscEnsureForReadyGroup($crad, (int) $payment['research_group_id'], $stage);
        rcpApplyToClearance($crad, (int) $payment['research_group_id'], $or, $note, $stage);
    }
    $fresh = rcpFindById($crad, (int) $payment['id']);
    if (function_exists('logActivity')) {
        logActivity(
            'update',
            'Approved ' . rcpStageLabel($stage) . ' collage payment for research group #'
                . (int) $payment['research_group_id'] . ' (reference ' . $or . ')',
            'crad'
        );
    }
    if (function_exists('rscNotify') && function_exists('rscStudentRecipients')) {
        $clearance = [
            'id' => (int) ($payment['id'] ?? 0),
            'research_group_id' => (int) ($payment['research_group_id'] ?? 0),
        ];
        foreach (rscStudentRecipients($crad, $clearance) as $recipient) {
            rscNotify(
                $crad,
                'clearance-payment:' . (int) $payment['id'],
                0,
                $recipient,
                'payment_approved',
                rcpStageLabel($stage) . ' collage payment approved',
                'Your ' . rcpStageLabel($stage) . ' collage payment was approved. The O.R. number and remarks are now on that Research Services Clearance form.',
                function_exists('rscStudentUrl') ? rscStudentUrl() : '#'
            );
        }
    }
    return ['ok' => true, 'payment' => $fresh];
}

function rcpAdminReject(PDO $crad, array $payment): array
{
    $crad->prepare(
        "UPDATE `crad_research_clearance_payments`
         SET status = 'rejected',
             approved_by_user_id = :uid,
             approved_by_name = :name,
             approved_at = NULL
         WHERE id = :id"
    )->execute([
        ':uid' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
        ':name' => (string) (function_exists('getCurrentUserName') ? getCurrentUserName() : ''),
        ':id' => (int) $payment['id'],
    ]);
    if (function_exists('logActivity')) {
        logActivity(
            'update',
            'Returned ' . rcpStageLabel((string) ($payment['research_stage'] ?? 'research_1'))
                . ' collage payment for research group #' . (int) $payment['research_group_id'],
            'crad'
        );
    }
    return ['ok' => true, 'payment' => rcpFindById($crad, (int) $payment['id'])];
}

function rcpCanApprove(): bool
{
    return function_exists('smsIsGrantedAdminRole') && smsIsGrantedAdminRole(getCurrentUserRoleKey());
}

/**
 * Remove collage-payment rows whose research group no longer exists.
 * Also deletes stored payment images. Safe to call on every live poll.
 */
function rcpPurgeDisconnectedPayments(PDO $crad): int
{
    rcpEnsureSchema($crad);
    $requiredTables = ['crad_research_clearance_payments', 'crad_research_groups'];
    foreach ($requiredTables as $table) {
        try {
            $tableCheck = $crad->prepare('SHOW TABLES LIKE ?');
            $tableCheck->execute([$table]);
            if (!$tableCheck->fetchColumn()) {
                error_log('RCP cleanup skipped; missing table: ' . $table);
                return 0;
            }
        } catch (Throwable $e) {
            error_log('RCP cleanup table check failed: ' . $e->getMessage());
            return 0;
        }
    }
    $orphans = $crad->query(
        "SELECT p.id, p.uploaded_file
         FROM `crad_research_clearance_payments` p
         LEFT JOIN `crad_research_groups` rg ON rg.id = p.research_group_id
         WHERE p.research_group_id IS NULL
            OR p.research_group_id < 1
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
            $path = rcpPaymentImagePath($file);
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
    $stmt = $crad->prepare("DELETE FROM `crad_research_clearance_payments` WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    return $stmt->rowCount();
}

function rcpListForAdmin(PDO $crad): array
{
    rcpEnsureSchema($crad);
    rcpPurgeDisconnectedPayments($crad);

    return $crad->query(
        "SELECT p.*,
                rg.group_number,
                rg.research_title,
                rg.group_name
         FROM `crad_research_clearance_payments` p
         INNER JOIN `crad_research_groups` rg ON rg.id = p.research_group_id
         WHERE p.status <> 'draft'
         ORDER BY FIELD(p.status, 'pending', 'rejected', 'approved'), p.updated_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function rcpStudentInbox(PDO $crad, int $groupId): array
{
    rcpEnsureSchema($crad);
    $rows = [];
    foreach (rcpStageList() as $item) {
        $stage = $item['key'];
        $row = rcpFindByGroup($crad, $groupId, $stage);
        $gate = rcpCanUploadStage($crad, $groupId, $stage);
        if (!$row) {
            $row = [
                'id' => 0,
                'research_group_id' => $groupId,
                'research_stage' => $stage,
                'status' => '',
                'or_number' => '',
                'remarks' => '',
                'uploaded_file' => '',
                'uploaded_original' => '',
                'updated_at' => '',
            ];
        }
        $row['can_upload'] = !empty($gate['ok']) && (string) ($row['status'] ?? '') !== 'approved';
        $row['locked_reason'] = empty($gate['ok']) ? (string) ($gate['reason'] ?? '') : '';
        if (!empty($row['id']) && !empty($row['uploaded_file'])) {
            $row = rcpEnsureOrFromImage($crad, $row);
        }
        $row['members'] = rcpGroupPaymentMembers($crad, $groupId, $stage);
        $public = rcpPublicRow($row);
        if ($public['locked_reason'] !== '' && $public['status'] === '') {
            $public['status_label'] = 'Locked';
        }
        $rows[] = $public;
    }
    return $rows;
}
