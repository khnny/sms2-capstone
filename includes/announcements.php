<?php
/**
 * SMS 2 – Admin announcements for student dashboards.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function smsEnsureAnnouncementTables(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();
    if (!$pdo) {
        return;
    }

    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS admin_announcements (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(180) NOT NULL,
                body TEXT NOT NULL,
                image_path VARCHAR(255) NULL,
                status ENUM('published','unpublished') NOT NULL DEFAULT 'published',
                audience VARCHAR(40) NOT NULL DEFAULT 'student',
                created_by INT UNSIGNED NULL,
                created_by_name VARCHAR(150) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                published_at DATETIME NULL,
                PRIMARY KEY (id),
                KEY idx_ann_status_published (status, published_at),
                KEY idx_ann_audience (audience)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        try {
            $col = $pdo->query("SHOW COLUMNS FROM admin_announcements LIKE 'image_path'")->fetch();
            if (!$col) {
                $pdo->exec("ALTER TABLE admin_announcements ADD image_path VARCHAR(255) NULL AFTER body");
            }
        } catch (Throwable $e) {
            error_log('smsEnsureAnnouncementTables image_path: ' . $e->getMessage());
        }
        $ready = true;
    } catch (Throwable $e) {
        error_log('smsEnsureAnnouncementTables: ' . $e->getMessage());
    }
}

/**
 * @return list<array<string, mixed>>
 */
function smsAnnouncementFetch(bool $publishedOnly = false, int $limit = 50): array
{
    smsEnsureAnnouncementTables();
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    $limit = max(1, min(100, $limit));
    $sql = 'SELECT id, title, body, image_path, status, audience, created_by, created_by_name,
                   DATE_FORMAT(created_at, "%b %e, %Y %h:%i %p") AS created_label,
                   DATE_FORMAT(updated_at, "%b %e, %Y %h:%i %p") AS updated_label,
                   DATE_FORMAT(published_at, "%b %e, %Y %h:%i %p") AS published_label,
                   UNIX_TIMESTAMP(IFNULL(published_at, updated_at)) AS stamp
              FROM admin_announcements';
    if ($publishedOnly) {
        $sql .= " WHERE status = 'published' AND audience = 'student'";
    }
    $sql .= ' ORDER BY IFNULL(published_at, updated_at) DESC, id DESC LIMIT ' . $limit;

    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('smsAnnouncementFetch: ' . $e->getMessage());
        return [];
    }
}

/**
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function smsAnnouncementPublicRows(array $rows): array
{
    $out = [];
    foreach ($rows as $row) {
        $imageName = (string) ($row['image_path'] ?? '');
        $id = (int) ($row['id'] ?? 0);
        $out[] = [
            'id' => $id,
            'title' => (string) ($row['title'] ?? ''),
            'body' => (string) ($row['body'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'image_url' => smsAnnouncementImageUrl($id, $imageName),
            'posted_by' => (string) ($row['created_by_name'] ?? 'Admin'),
            'posted_at' => (string) ($row['published_label'] ?: ($row['updated_label'] ?? '')),
        ];
    }

    return $out;
}

function smsAnnouncementImageUrl(int $id, ?string $storedName): string
{
    if ($id < 1 || basename(trim((string) $storedName)) === '') {
        return '';
    }

    return BASE_URL . '/account/announcement-image.php?id=' . $id;
}

function smsAnnouncementImagePath(?string $storedName): ?string
{
    require_once ROOT_PATH . '/includes/uploads.php';
    $storedName = basename(trim((string) $storedName));
    if ($storedName === '' || !preg_match('/^[a-f0-9]{32}\.png$/i', $storedName)) {
        return null;
    }

    $uploadsDir = realpath(smsUploadRoot());
    $realPath = realpath(smsUploadRoot() . '/announcements/' . $storedName);
    if (
        $uploadsDir === false
        || $realPath === false
        || !is_file($realPath)
        || strncmp($realPath, $uploadsDir, strlen($uploadsDir)) !== 0
    ) {
        return null;
    }

    return $realPath;
}

/**
 * @param array<string, mixed>|null $file
 * @return array{ok:bool,error:string,stored_name:?string}
 */
function smsAnnouncementStorePng(?array $file): array
{
    if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'error' => '', 'stored_name' => null];
    }

    require_once ROOT_PATH . '/includes/uploads.php';
    $upload = smsSecureUpload($file, [
        'subdir' => 'announcements',
        'max_bytes' => 5 * 1024 * 1024,
        'allowed' => ['png' => ['image/png']],
        'required' => false,
    ]);
    if (empty($upload['ok'])) {
        return ['ok' => false, 'error' => (string) ($upload['error'] ?: 'PNG upload failed.'), 'stored_name' => null];
    }

    $stored = (string) ($upload['stored_name'] ?? '');
    if ($stored === '') {
        return ['ok' => true, 'error' => '', 'stored_name' => null];
    }

    $info = @getimagesize((string) $upload['path']);
    if ($info === false || (int) ($info[2] ?? 0) !== IMAGETYPE_PNG) {
        @unlink((string) $upload['path']);
        return ['ok' => false, 'error' => 'Please upload a valid PNG image.', 'stored_name' => null];
    }

    return ['ok' => true, 'error' => '', 'stored_name' => $stored];
}

function smsAnnouncementDeleteFile(?string $storedName): void
{
    $path = smsAnnouncementImagePath($storedName);
    if ($path !== null) {
        @unlink($path);
    }
}

function smsAnnouncementStamp(array $rows): string
{
    $maxId = 0;
    $maxStamp = 0;
    foreach ($rows as $row) {
        $maxId = max($maxId, (int) ($row['id'] ?? 0));
        $maxStamp = max($maxStamp, (int) ($row['stamp'] ?? 0));
    }

    return $maxId . ':' . $maxStamp . ':' . count($rows);
}

function smsAnnouncementPublish(string $title, string $body, ?array $imageFile = null): array
{
    smsEnsureAnnouncementTables();
    $pdo = db();
    $title = trim($title);
    $body = trim($body);
    if ($title === '' || $body === '') {
        return ['ok' => false, 'error' => 'Title and message are required.'];
    }
    if (function_exists('mb_strlen')) {
        if (mb_strlen($title) > 180) {
            return ['ok' => false, 'error' => 'Title is too long.'];
        }
        if (mb_strlen($body) > 4000) {
            return ['ok' => false, 'error' => 'Message is too long.'];
        }
    } elseif (strlen($title) > 180 || strlen($body) > 4000) {
        return ['ok' => false, 'error' => 'Announcement is too long.'];
    }
    if (!$pdo) {
        return ['ok' => false, 'error' => 'Database is unavailable.'];
    }

    $image = smsAnnouncementStorePng($imageFile);
    if (empty($image['ok'])) {
        return ['ok' => false, 'error' => (string) ($image['error'] ?: 'PNG upload failed.')];
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO admin_announcements
                (title, body, image_path, status, audience, created_by, created_by_name, published_at)
             VALUES (?, ?, ?, \'published\', \'student\', ?, ?, NOW())'
        );
        $stmt->execute([
            $title,
            $body,
            $image['stored_name'],
            getCurrentUserId(),
            substr((string) getCurrentUserName(), 0, 150),
        ]);
        if (function_exists('logActivity')) {
            logActivity('create', 'Published student announcement: ' . $title, 'dashboard');
        }

        return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
    } catch (Throwable $e) {
        smsAnnouncementDeleteFile($image['stored_name'] ?? null);
        error_log('smsAnnouncementPublish: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not publish the announcement.'];
    }
}

function smsAnnouncementSetStatus(int $id, string $status): array
{
    smsEnsureAnnouncementTables();
    $pdo = db();
    if ($id < 1 || !$pdo) {
        return ['ok' => false, 'error' => 'Invalid announcement.'];
    }
    if (!in_array($status, ['published', 'unpublished'], true)) {
        return ['ok' => false, 'error' => 'Invalid status.'];
    }

    try {
        $sql = $status === 'published'
            ? 'UPDATE admin_announcements SET status = ?, published_at = IFNULL(published_at, NOW()), updated_at = NOW() WHERE id = ?'
            : 'UPDATE admin_announcements SET status = ?, updated_at = NOW() WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $id]);
        if (function_exists('logActivity')) {
            logActivity('update', ($status === 'published' ? 'Republished' : 'Unpublished') . ' student announcement #' . $id, 'dashboard');
        }

        return ['ok' => true];
    } catch (Throwable $e) {
        error_log('smsAnnouncementSetStatus: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not update the announcement.'];
    }
}

function smsAnnouncementDelete(int $id): array
{
    smsEnsureAnnouncementTables();
    $pdo = db();
    if ($id < 1 || !$pdo) {
        return ['ok' => false, 'error' => 'Invalid announcement.'];
    }

    try {
        $lookup = $pdo->prepare('SELECT image_path FROM admin_announcements WHERE id = ? LIMIT 1');
        $lookup->execute([$id]);
        $imagePath = (string) ($lookup->fetchColumn() ?: '');
        $stmt = $pdo->prepare('DELETE FROM admin_announcements WHERE id = ?');
        $stmt->execute([$id]);
        smsAnnouncementDeleteFile($imagePath);
        if (function_exists('logActivity')) {
            logActivity('delete', 'Deleted student announcement #' . $id, 'dashboard');
        }

        return ['ok' => true];
    } catch (Throwable $e) {
        error_log('smsAnnouncementDelete: ' . $e->getMessage());
        return ['ok' => false, 'error' => 'Could not delete the announcement.'];
    }
}
