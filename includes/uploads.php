<?php
/**
 * SMS 2 – Secure file upload helpers
 *
 * - Extension + MIME whitelist
 * - Size limit
 * - Random stored filename
 * - Files kept under storage/uploads (not directly executable)
 */
declare(strict_types=1);

require_once __DIR__ . '/crypto.php';

function smsUploadRoot(): string
{
    return ROOT_PATH . '/storage/uploads';
}

function smsUploadEnsureDirs(): bool
{
    $root = smsUploadRoot();
    if (!is_dir($root)) {
        @mkdir($root, 0750, true);
    }
    if (!is_dir($root) || !is_writable($root)) {
        return false;
    }
    $deny = $root . '/.htaccess';
    if (!is_file($deny)) {
        @file_put_contents(
            $deny,
            "# Deny direct web access to uploaded files\n"
            . "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n"
        );
    }
    $keys = ROOT_PATH . '/storage/keys';
    if (!is_dir($keys)) {
        @mkdir($keys, 0700, true);
    }
    $keysDeny = $keys . '/.htaccess';
    if (!is_file($keysDeny)) {
        @file_put_contents(
            $keysDeny,
            "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n"
        );
    }
    $storageDeny = ROOT_PATH . '/storage/.htaccess';
    if (!is_file($storageDeny)) {
        @file_put_contents(
            $storageDeny,
            "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n    Deny from all\n</IfModule>\n"
        );
    }

    return true;
}

/**
 * Allowed document types for student research packet / general docs.
 *
 * @return array<string, list<string>> ext => mime list
 */
function smsUploadAllowedDocuments(): array
{
    return [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/octet-stream',
        ],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
    ];
}

/**
 * @param array $file One $_FILES[...] entry
 * @param array{subdir?:string,max_bytes?:int,allowed?:array<string,list<string>>,required?:bool} $opts
 * @return array{ok:bool,error:string,path:?string,stored_name:?string,original_name:?string,size:int,mime:string}
 */
function smsSecureUpload(array $file, array $opts = []): array
{
    $empty = [
        'ok' => false,
        'error' => 'Upload failed.',
        'path' => null,
        'stored_name' => null,
        'original_name' => null,
        'size' => 0,
        'mime' => '',
    ];

    if (!smsUploadEnsureDirs()) {
        $empty['error'] = 'Upload storage is unavailable or not writable. Ask the hosting administrator to make storage/uploads writable.';
        return $empty;
    }

    $required = !empty($opts['required']);
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postMaxBytes = smsUploadIniBytes((string) ini_get('post_max_size'));
        if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
            $empty['error'] = 'The upload was rejected by the hosting post_max_size limit (' . ini_get('post_max_size') . '). Increase the hosting PHP upload limit.';
            return $empty;
        }
        if ($required) {
            $empty['error'] = 'A required file is missing.';
            return $empty;
        }
        return [
            'ok' => true,
            'error' => '',
            'path' => null,
            'stored_name' => null,
            'original_name' => null,
            'size' => 0,
            'mime' => '',
        ];
    }
    if ($error !== UPLOAD_ERR_OK) {
        $empty['error'] = match ($error) {
            UPLOAD_ERR_INI_SIZE => 'The file is larger than the hosting upload_max_filesize limit (' . ini_get('upload_max_filesize') . ').',
            UPLOAD_ERR_FORM_SIZE => 'The file is larger than the form upload limit.',
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_TMP_DIR => 'The hosting server has no temporary upload directory configured.',
            UPLOAD_ERR_CANT_WRITE => 'The hosting server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
            default => 'Upload failed with error code ' . $error . '.',
        };
        return $empty;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $orig = (string) ($file['name'] ?? '');
    $size = (int) ($file['size'] ?? 0);
    $maxBytes = (int) ($opts['max_bytes'] ?? (10 * 1024 * 1024));
    if ($size <= 0 || $size > $maxBytes) {
        $empty['error'] = 'File must be between 1 byte and ' . (int) round($maxBytes / 1048576) . ' MB.';
        return $empty;
    }
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        $empty['error'] = 'Invalid upload source.';
        return $empty;
    }

    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $allowed = $opts['allowed'] ?? smsUploadAllowedDocuments();
    if ($ext === '' || !isset($allowed[$ext])) {
        $empty['error'] = 'File type not allowed. Use: ' . implode(', ', array_keys($allowed)) . '.';
        return $empty;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $okMimes = $allowed[$ext];
    if (!in_array($mime, $okMimes, true)) {
        // Extra hard reject for script-like types
        if (preg_match('~(php|html|javascript|x-httpd)~i', $mime)) {
            $empty['error'] = 'Rejected dangerous file content.';
            return $empty;
        }
        $empty['error'] = 'File content does not match an allowed type.';
        return $empty;
    }

    // Reject polyglot PHP in disguise (quick scan of first bytes)
    $head = (string) file_get_contents($tmp, false, null, 0, 256);
    if (preg_match('/<\?php|<\?=|<script/i', $head)) {
        $empty['error'] = 'Rejected file: script content detected.';
        return $empty;
    }

    $subdir = preg_replace('/[^a-z0-9_\-\/]/i', '', (string) ($opts['subdir'] ?? 'general')) ?: 'general';
    $subdir = trim(str_replace('..', '', $subdir), '/');
    $destDir = smsUploadRoot() . '/' . $subdir;
    if (!is_dir($destDir)) {
        @mkdir($destDir, 0750, true);
    }
    if (!is_dir($destDir) || !is_writable($destDir)) {
        $empty['error'] = 'The upload folder is not writable on the hosting server.';
        return $empty;
    }

    $stored = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $destDir . '/' . $stored;
    if (!move_uploaded_file($tmp, $dest)) {
        $empty['error'] = 'Could not store uploaded file.';
        return $empty;
    }
    @chmod($dest, 0640);

    return [
        'ok' => true,
        'error' => '',
        'path' => $dest,
        'stored_name' => $stored,
        'original_name' => $orig,
        'size' => $size,
        'mime' => $mime,
    ];
}

function smsUploadIniBytes(string $value): int
{
    $value = trim($value);
    if ($value === '') {
        return 0;
    }
    $number = (float) $value;
    $unit = strtolower(substr($value, -1));
    $multiplier = 1;
    if ($unit === 'g') {
        $multiplier = 1024 * 1024 * 1024;
    } elseif ($unit === 'm') {
        $multiplier = 1024 * 1024;
    } elseif ($unit === 'k') {
        $multiplier = 1024;
    }
    return (int) ($number * $multiplier);
}
