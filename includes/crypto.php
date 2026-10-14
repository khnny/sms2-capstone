<?php
/**
 * SMS 2 – Encryption at rest (AES-256-GCM) for secrets / TOTP.
 *
 * Ciphertext format: sms2enc1.<base64(iv + tag + ciphertext)>
 * Plain legacy values pass through decrypt unchanged until next save.
 */
declare(strict_types=1);

function smsCryptoKeyPath(): string
{
    return ROOT_PATH . '/storage/keys/app.key';
}

/**
 * 32-byte application key (auto-created once). It is independent of account
 * passwords and must remain stable to decrypt existing application secrets.
 * Keep storage/keys off the web.
 */
function smsCryptoAppKey(): string
{
    static $key = null;
    if (is_string($key) && strlen($key) === 32) {
        return $key;
    }

    // 1. Check explicit configuration / environment variable
    $configuredKey = '';
    if (defined('SMS2_APP_KEY') && is_string(SMS2_APP_KEY) && SMS2_APP_KEY !== '') {
        $configuredKey = SMS2_APP_KEY;
    } elseif (function_exists('sms2_env')) {
        $configuredKey = (string) sms2_env('SMS2_APP_KEY', sms2_env('APP_KEY', ''));
    } else {
        $configuredKey = (string) (getenv('SMS2_APP_KEY') ?: (getenv('APP_KEY') ?: ''));
    }

    if ($configuredKey !== '') {
        if (strlen($configuredKey) === 32) {
            $key = $configuredKey;
            return $key;
        }
        $decoded = base64_decode(trim($configuredKey), true);
        if (is_string($decoded) && strlen($decoded) === 32) {
            $key = $decoded;
            return $key;
        }
        // If 64 hex characters
        if (strlen($configuredKey) === 64 && ctype_xdigit($configuredKey)) {
            $key = (string) hex2bin($configuredKey);
            return $key;
        }
        // Arbitrary passphrase - hash to 32 bytes
        $key = hash('sha256', $configuredKey, true);
        return $key;
    }

    // 2. Primary file path: storage/keys/app.key
    $path = smsCryptoKeyPath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('SMS2 encryption key directory could not be created.');
        }
    }

    $lockPath = $path . '.lock';
    $lock = @fopen($lockPath, 'c');
    if ($lock === false) {
        throw new RuntimeException('SMS2 encryption key lock could not be opened.');
    }
    @chmod($lockPath, 0600);

    try {
        if (!flock($lock, LOCK_EX)) {
            throw new RuntimeException('SMS2 encryption key lock could not be acquired.');
        }

        clearstatcache(true, $path);
        if (file_exists($path) || is_link($path)) {
            if (!is_file($path) || !is_readable($path)) {
                throw new RuntimeException('SMS2 encryption key exists but cannot be read; refusing to replace it.');
            }
            $raw = file_get_contents($path);
            if (!is_string($raw)) {
                throw new RuntimeException('SMS2 encryption key could not be read; refusing to replace it.');
            }
            if (strlen($raw) === 32) {
                $key = $raw;
                return $key;
            }
            $decoded = base64_decode(trim($raw), true);
            if (is_string($decoded) && strlen($decoded) === 32) {
                $key = $decoded;
                return $key;
            }

            throw new RuntimeException('SMS2 encryption key is invalid; refusing to replace it.');
        }

        // Serialize first-time creation so concurrent requests cannot replace the key.
        $generated = random_bytes(32);
        $written = @file_put_contents($path, base64_encode($generated), LOCK_EX);
        if ($written === false) {
            throw new RuntimeException(
                'SMS2 encryption key could not be written. Configure SMS2_APP_KEY or make storage/keys/app.key persistent and writable.'
            );
        }
        @chmod($path, 0600);
        $key = $generated;
        return $key;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function smsCryptoIsEncrypted(string $value): bool
{
    return str_starts_with($value, 'sms2enc1.');
}

/**
 * Encrypt a secret for database storage. Empty string stays empty.
 */
function smsSecretEncrypt(string $plaintext): string
{
    if ($plaintext === '') {
        return '';
    }
    if (smsCryptoIsEncrypted($plaintext)) {
        return $plaintext;
    }
    if (!function_exists('openssl_encrypt')) {
        throw new RuntimeException('OpenSSL is required to encrypt application secrets.');
    }

    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        smsCryptoAppKey(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',
        16
    );
    if ($cipher === false || $tag === '') {
        throw new RuntimeException('Application secret encryption failed.');
    }

    return 'sms2enc1.' . base64_encode($iv . $tag . $cipher);
}

/**
 * Decrypt a secret. Legacy plaintext is returned as-is.
 */
function smsSecretDecrypt(string $stored): string
{
    if ($stored === '' || !smsCryptoIsEncrypted($stored)) {
        return $stored;
    }
    if (!function_exists('openssl_decrypt')) {
        throw new RuntimeException('OpenSSL is required to decrypt application secrets.');
    }

    $blob = base64_decode(substr($stored, strlen('sms2enc1.')), true);
    if ($blob === false || strlen($blob) < 28) {
        throw new RuntimeException('Stored application secret is corrupt.');
    }
    $iv = substr($blob, 0, 12);
    $tag = substr($blob, 12, 16);
    $cipher = substr($blob, 28);
    $plain = openssl_decrypt(
        $cipher,
        'aes-256-gcm',
        smsCryptoAppKey(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
    if (!is_string($plain)) {
        throw new RuntimeException('Stored application secret could not be decrypted. Check SMS2_APP_KEY or storage/keys/app.key.');
    }
    return $plain;
}

/** Setting keys stored encrypted at rest. */
function smsEncryptedSettingKeys(): array
{
    return ['smtp_password', 'turnstile_secret_key'];
}

function smsIsEncryptedSettingKey(string $key): bool
{
    return in_array($key, smsEncryptedSettingKeys(), true);
}

/**
 * Encrypt a whole file to another path (AES-256-GCM, streamed in chunks via memory for moderate dumps).
 * Format: sms2bak1 + iv(12) + tag(16) + ciphertext
 *
 * @return array{ok:bool,error:string}
 */
function smsEncryptFileTo(string $sourcePath, string $destPath, string $password): array
{
    if (!is_readable($sourcePath)) {
        return ['ok' => false, 'error' => 'Source file not readable.'];
    }
    if ($password === '' || strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Backup password must be at least 8 characters.'];
    }
    if (!function_exists('openssl_encrypt')) {
        return ['ok' => false, 'error' => 'OpenSSL is required for encrypted backups.'];
    }

    $plain = (string) file_get_contents($sourcePath);
    $salt = random_bytes(16);
    $key = hash_pbkdf2('sha256', $password, $salt, 100000, 32, true);
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($cipher === false) {
        return ['ok' => false, 'error' => 'Could not encrypt backup.'];
    }

    $out = 'sms2bak1' . $salt . $iv . $tag . $cipher;
    if (@file_put_contents($destPath, $out, LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'Could not write encrypted backup file.'];
    }
    @chmod($destPath, 0600);
    return ['ok' => true, 'error' => ''];
}

/**
 * Decrypt sms2bak1 file to destination.
 *
 * @return array{ok:bool,error:string}
 */
function smsDecryptFileTo(string $encPath, string $destPath, string $password): array
{
    if (!is_readable($encPath)) {
        return ['ok' => false, 'error' => 'Encrypted file not readable.'];
    }
    $raw = (string) file_get_contents($encPath);
    if (!str_starts_with($raw, 'sms2bak1') || strlen($raw) < 8 + 16 + 12 + 16 + 1) {
        return ['ok' => false, 'error' => 'Not a valid SMS2 encrypted backup.'];
    }
    $salt = substr($raw, 8, 16);
    $iv = substr($raw, 24, 12);
    $tag = substr($raw, 36, 16);
    $cipher = substr($raw, 52);
    $key = hash_pbkdf2('sha256', $password, $salt, 100000, 32, true);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plain === false) {
        return ['ok' => false, 'error' => 'Wrong password or corrupt backup.'];
    }
    if (@file_put_contents($destPath, $plain, LOCK_EX) === false) {
        return ['ok' => false, 'error' => 'Could not write decrypted file.'];
    }
    return ['ok' => true, 'error' => ''];
}
