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
    $crad->exec(
        "CREATE TABLE IF NOT EXISTS research_clearance_payments (
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
        if (!$crad->query("SHOW COLUMNS FROM research_clearance_payments LIKE 'research_stage'")->fetch()) {
            $crad->exec(
                "ALTER TABLE research_clearance_payments
                 ADD COLUMN research_stage VARCHAR(20) NOT NULL DEFAULT 'research_1' AFTER research_group_id"
            );
        }
        $crad->exec("UPDATE research_clearance_payments SET research_stage = 'research_1' WHERE TRIM(COALESCE(research_stage, '')) = ''");
        $indexes = $crad->query("SHOW INDEX FROM research_clearance_payments")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $hasStageUnique = false;
        foreach ($indexes as $idx) {
            if (($idx['Key_name'] ?? '') === 'uniq_rcp_group_stage') {
                $hasStageUnique = true;
                break;
            }
        }
        if (!$hasStageUnique) {
            try {
                $crad->exec('ALTER TABLE research_clearance_payments DROP INDEX uniq_rcp_group');
            } catch (Throwable $e) {
                // optional legacy index
            }
            $crad->exec(
                'ALTER TABLE research_clearance_payments
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
        "SELECT * FROM research_clearance_payments
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
    $stmt = $crad->prepare('SELECT * FROM research_clearance_payments WHERE id = ? LIMIT 1');
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
    return BASE_URL . '/uploads/college-payment/' . rawurlencode($file) . '?v=' . $stamp;
}

function rcpStatusLabel(string $status): string
{
    return match ($status) {
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
        'remarks' => (string) ($row['remarks'] ?? ''),
        'uploaded_original' => (string) ($row['uploaded_original'] ?? ''),
        'uploaded_url' => rcpUploadPublicUrl($row),
        'has_upload' => trim((string) ($row['uploaded_file'] ?? '')) !== '',
        'approved_by_name' => (string) ($row['approved_by_name'] ?? ''),
        'approved_at' => (string) ($row['approved_at'] ?? ''),
        'updated_at' => (string) ($row['updated_at'] ?? ''),
        'can_upload' => !empty($row['can_upload']),
        'locked_reason' => (string) ($row['locked_reason'] ?? ''),
    ];
}

function rcpParseReferenceNumber(string $text): string
{
    $text = strtoupper(trim(preg_replace('/\s+/', ' ', $text) ?? ''));
    if ($text === '') {
        return '';
    }
    if (preg_match('/\b(HMBP[0-9]{8,})\b/', $text, $m)) {
        return $m[1];
    }
    if (preg_match('/REFERENCE\s*(NO\.?|NUMBER)\s*[:#]?\s*([A-Z0-9]{8,})/', $text, $m)) {
        return $m[2];
    }
    if (preg_match('/\b(OR-[0-9]{4,})\b/', $text, $m)) {
        return $m[1];
    }
    return '';
}

function rcpOcrImageText(string $path): string
{
    $script = ROOT_PATH . '/modules/crad/includes/win-ocr.ps1';
    if ($path === '' || !is_file($path) || !is_file($script)) {
        return '';
    }
    $real = realpath($path) ?: $path;
    $tmpDir = sys_get_temp_dir();
    $token = bin2hex(random_bytes(4));
    $outFile = $tmpDir . DIRECTORY_SEPARATOR . 'rcp-ocr-' . $token . '.txt';
    $jobFile = $tmpDir . DIRECTORY_SEPARATOR . 'rcp-ocr-job-' . $token . '.json';
    file_put_contents($jobFile, json_encode(['image' => $real, 'out' => $outFile], JSON_UNESCAPED_SLASHES));
    $ps = 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe';
    if (!is_file($ps)) {
        $ps = 'powershell';
    }
    $cmd = $ps
        . ' -NoProfile -ExecutionPolicy Bypass -File '
        . escapeshellarg($script)
        . ' -JobFile '
        . escapeshellarg($jobFile);
    $out = [];
    $code = 0;
    @exec($cmd, $out, $code);
    $text = is_file($outFile) ? trim((string) @file_get_contents($outFile)) : '';
    @unlink($outFile);
    @unlink($jobFile);
    if ($text === '') {
        $text = trim(implode(' ', $out));
    }
    return $text;
}

function rcpExtractReferenceFromImage(string $path): string
{
    return rcpParseReferenceNumber(rcpOcrImageText($path));
}

function rcpEnsureOrFromImage(PDO $crad, array $row): array
{
    $or = trim((string) ($row['or_number'] ?? ''));
    if ($or !== '' && !preg_match('/^OR-\d+$/i', $or)) {
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
    $extracted = rcpExtractReferenceFromImage(ROOT_PATH . '/uploads/college-payment/' . $file);
    if ($extracted === '' || strcasecmp($extracted, $or) === 0) {
        return $row;
    }
    $crad->prepare('UPDATE research_clearance_payments SET or_number = ? WHERE id = ?')
        ->execute([$extracted, (int) ($row['id'] ?? 0)]);
    $row['or_number'] = $extracted;
    return $row;
}

function rcpCanUploadStage(PDO $crad, int $groupId, string $stage): array
{
    $stage = rcpNormalizeStage($stage);
    if ($stage === 'research_1') {
        return ['ok' => true, 'reason' => ''];
    }
    if (function_exists('rscClearanceDoneExists') && rscClearanceDoneExists($crad, $groupId, 'research_1')) {
        return ['ok' => true, 'reason' => ''];
    }
    return [
        'ok' => false,
        'reason' => 'Finish Research 1 clearance (Pre-Oral) before uploading Research 2 collage payment.',
    ];
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
    $dir = ROOT_PATH . '/uploads/college-payment';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Could not store the college payment picture.'];
    }
    $stored = 'rcp-' . $groupId . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $path = $dir . '/' . $stored;
    if (!move_uploaded_file($tmp, $path)) {
        return ['ok' => false, 'error' => 'Could not store the college payment picture.'];
    }
    return ['ok' => true, 'file' => $stored, 'original' => $name, 'path' => $path];
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
    $or = rcpExtractReferenceFromImage((string) ($saved['path'] ?? ''));
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
            "UPDATE research_clearance_payments
             SET uploaded_file = :file,
                 uploaded_original = :original,
                 or_number = :or_number,
                 research_stage = :stage,
                 status = 'pending',
                 approved_by_user_id = NULL,
                 approved_by_name = '',
                 approved_at = NULL
             WHERE id = :id"
        )->execute([
            ':file' => (string) $saved['file'],
            ':original' => (string) $saved['original'],
            ':or_number' => $or,
            ':stage' => $stage,
            ':id' => (int) $existing['id'],
        ]);
        if ($old !== '' && $old !== (string) $saved['file']) {
            $oldPath = ROOT_PATH . '/uploads/college-payment/' . $old;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
        $fresh = rcpFindById($crad, (int) $existing['id']);
    } else {
        $crad->prepare(
            "INSERT INTO research_clearance_payments
                (research_group_id, research_stage, student_user_id, uploaded_file, uploaded_original, or_number, remarks, status)
             VALUES
                (:gid, :stage, :uid, :file, :original, :or_number, '', 'pending')"
        )->execute([
            ':gid' => $groupId,
            ':stage' => $stage,
            ':uid' => (int) ($_SESSION['user_id'] ?? 0) ?: null,
            ':file' => (string) $saved['file'],
            ':original' => (string) $saved['original'],
            ':or_number' => $or,
        ]);
        $fresh = rcpFindById($crad, (int) $crad->lastInsertId());
    }
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
        foreach ($members as &$member) {
            if (is_array($member)) {
                $member['or_number'] = $orNumber;
            }
        }
        unset($member);
    }
    $crad->prepare(
        "UPDATE research_services_clearances
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
        "UPDATE research_clearance_payments
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
        "UPDATE research_clearance_payments
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
    $orphans = $crad->query(
        "SELECT p.id, p.uploaded_file
         FROM research_clearance_payments p
         LEFT JOIN research_groups rg ON rg.id = p.research_group_id
         WHERE p.research_group_id IS NULL
            OR p.research_group_id < 1
            OR rg.id IS NULL"
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if ($orphans === []) {
        return 0;
    }

    $dir = ROOT_PATH . '/uploads/college-payment';
    $ids = [];
    foreach ($orphans as $row) {
        $ids[] = (int) ($row['id'] ?? 0);
        $file = basename(str_replace('\\', '/', (string) ($row['uploaded_file'] ?? '')));
        if ($file !== '' && $file !== '.' && $file !== '..') {
            $path = $dir . '/' . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
    $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));
    if ($ids === []) {
        return 0;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $crad->prepare("DELETE FROM research_clearance_payments WHERE id IN ($placeholders)");
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
         FROM research_clearance_payments p
         INNER JOIN research_groups rg ON rg.id = p.research_group_id
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
        $public = rcpPublicRow($row);
        if ($public['locked_reason'] !== '' && $public['status'] === '') {
            $public['status_label'] = 'Locked';
        }
        $rows[] = $public;
    }
    return $rows;
}
