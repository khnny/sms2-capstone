<?php
/**
 * HostForge-safe sms2_users writes for Add / Edit User.
 *
 * Live hf_db_162pzmvl has every sms2_users column this insert uses, including
 * password_changed_at and notes, and its role rows include crad_officer (the
 * form value "crad" is normalized to that key before the foreign key check).
 * A partial import can still leave id as a plain integer, sometimes with no
 * primary key. Insert then fails with 1364, and ALTER ... AUTO_INCREMENT fails
 * with 1075 until id is a key. The create path adds a PRIMARY KEY on id when
 * the table has none, then restores AUTO_INCREMENT when ALTER is allowed.
 * Otherwise the message names database/patches/fix_users_autoincrement.sql.
 * Student saves can hit the same fallback when profile DDL implicitly commits
 * and the later commit() finds no transaction.
 */
declare(strict_types=1);

/**
 * @return array<string, array<string, mixed>>|null
 */
function umSms2UsersColumns(PDO $pdo): ?array
{
    try {
        $rows = $pdo->query('SHOW COLUMNS FROM `sms2_users`')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('save-user column lookup failed: ' . $e->getMessage());
        return null;
    }

    $meta = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $field = strtolower((string) ($row['Field'] ?? ''));
        if ($field !== '' && preg_match('/^[a-z0-9_]+$/', $field) === 1) {
            $meta[$field] = $row;
        }
    }

    return $meta;
}

function umColumnIsNullable(array $column): bool
{
    return strtoupper((string) ($column['Null'] ?? 'YES')) === 'YES';
}

function umColumnHasDefault(array $column): bool
{
    if (array_key_exists('Default', $column) && $column['Default'] !== null) {
        return true;
    }
    $extra = strtolower((string) ($column['Extra'] ?? ''));

    return str_contains($extra, 'auto_increment')
        || str_contains($extra, 'default_generated')
        || str_contains($extra, 'current_timestamp');
}

function umColumnIsGenerated(array $column): bool
{
    $extra = strtolower((string) ($column['Extra'] ?? ''));

    return str_contains($extra, 'generated')
        || str_contains($extra, 'virtual')
        || str_contains($extra, 'stored generated');
}

/**
 * @return list<string>|null Null when the column is not an ENUM.
 */
function umEnumValues(array $column): ?array
{
    $type = (string) ($column['Type'] ?? '');
    if (preg_match('/^enum\((.*)\)$/i', $type, $match) !== 1) {
        return null;
    }
    if (preg_match_all("/'((?:\\\\'|[^'])*)'/", $match[1], $parts) < 1) {
        return [];
    }

    return array_map(static fn (string $value): string => stripcslashes($value), $parts[1]);
}

function umAssertEnumValue(array $column, string $name, string $value): void
{
    $allowed = umEnumValues($column);
    if ($allowed === null || in_array($value, $allowed, true)) {
        return;
    }
    $list = $allowed === [] ? 'none' : implode(', ', $allowed);
    throw new InvalidArgumentException(
        'The users table column ' . $name . ' does not allow "' . $value
        . '". Allowed values: ' . $list . '. Nothing was changed.'
    );
}

/**
 * Additive only: add columns the account form writes, and restore AUTO_INCREMENT
 * on id. Failures are logged so a DB user without ALTER can still omit optional
 * columns and save.
 *
 * @param array<string, array<string, mixed>> $columns
 * @return array<string, array<string, mixed>>
 */
function umRepairSms2UsersForAccountWrite(PDO $pdo, array $columns): array
{
    $add = [
        'password_changed_at' => 'DATETIME NULL',
        'notes' => 'TEXT NULL',
        'student_id' => 'VARCHAR(40) NULL',
    ];
    $changed = false;
    foreach ($add as $name => $definition) {
        if (isset($columns[$name])) {
            continue;
        }
        try {
            $pdo->exec('ALTER TABLE `sms2_users` ADD COLUMN `' . $name . '` ' . $definition);
            $changed = true;
            error_log('save-user schema repair added sms2_users.' . $name);
        } catch (Throwable $e) {
            error_log('save-user schema repair could not add sms2_users.' . $name . ': ' . $e->getMessage());
        }
    }

    if (isset($columns['id'])) {
        $extra = strtolower((string) ($columns['id']['Extra'] ?? ''));
        if (!str_contains($extra, 'auto_increment')) {
            umEnsureTableIdAutoIncrement($pdo, 'sms2_users');
            $changed = true;
        }
    }

    if (!$changed) {
        return $columns;
    }
    $fresh = umSms2UsersColumns($pdo);

    return $fresh ?? $columns;
}

/**
 * HostForge dumps often keep the id column and drop its key and AUTO_INCREMENT.
 * Insert then fails with 1364. MODIFY ... AUTO_INCREMENT fails with 1075 until
 * id is a key, so a missing primary key is added first.
 */
function umEnsureTableIdAutoIncrement(PDO $pdo, string $table): void
{
    $allowed = [
        'sms2_users' => true,
        'crad_research_adviser_assignments' => true,
        'crad_research_coordinator_assignments' => true,
    ];
    if (!isset($allowed[$table])) {
        return;
    }

    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . "` LIKE 'id'");
        $col = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if ($stmt) {
            $stmt->closeCursor();
        }
        if (!is_array($col)) {
            return;
        }
        $extra = strtolower((string) ($col['Extra'] ?? ''));
        if (str_contains($extra, 'auto_increment')) {
            return;
        }
        $type = (string) ($col['Type'] ?? 'int(10) unsigned');
        if (preg_match('/^[a-z0-9(), ]+$/i', $type) !== 1) {
            $type = 'int(10) unsigned';
        }
        if (strtoupper((string) ($col['Null'] ?? 'NO')) === 'YES') {
            $pdo->exec('ALTER TABLE `' . $table . '` MODIFY `id` ' . $type . ' NOT NULL');
        }

        $keyStmt = $pdo->query('SHOW KEYS FROM `' . $table . '`');
        $keys = $keyStmt ? $keyStmt->fetchAll(PDO::FETCH_ASSOC) : [];
        if ($keyStmt) {
            $keyStmt->closeCursor();
        }
        $hasPrimary = false;
        $primaryOnId = false;
        $idIsIndexed = false;
        foreach ($keys as $key) {
            if (!is_array($key)) {
                continue;
            }
            $column = strtolower((string) ($key['Column_name'] ?? ''));
            if ((string) ($key['Key_name'] ?? '') === 'PRIMARY') {
                $hasPrimary = true;
                if ($column === 'id') {
                    $primaryOnId = true;
                }
            }
            if ($column === 'id') {
                $idIsIndexed = true;
            }
        }
        if (!$hasPrimary) {
            $pdo->exec('ALTER TABLE `' . $table . '` ADD PRIMARY KEY (`id`)');
            error_log('save-user schema repair added PRIMARY KEY on ' . $table . '.id');
        } elseif (!$primaryOnId && !$idIsIndexed) {
            $pdo->exec('ALTER TABLE `' . $table . '` ADD KEY `idx_' . $table . '_id` (`id`)');
            error_log('save-user schema repair added KEY on ' . $table . '.id');
        }

        $pdo->exec('ALTER TABLE `' . $table . '` MODIFY `id` ' . $type . ' NOT NULL AUTO_INCREMENT');
        error_log('save-user schema repair set ' . $table . '.id AUTO_INCREMENT');
    } catch (Throwable $e) {
        error_log('save-user schema repair could not set ' . $table . '.id AUTO_INCREMENT: ' . $e->getMessage());
    }
}

/**
 * @param array<string, mixed> $fields
 * @param array<string, array<string, mixed>> $columns
 * @return array{sql: string, params: list<mixed>}
 */
function umUserInsertStatement(array $columns, array $fields): array
{
    foreach (['role_key', 'status'] as $enumColumn) {
        if (isset($columns[$enumColumn])) {
            umAssertEnumValue($columns[$enumColumn], $enumColumn, (string) $fields[$enumColumn]);
        }
    }

    $names = [];
    $placeholders = [];
    $params = [];
    $push = static function (string $name, string $placeholder, mixed $value) use (&$names, &$placeholders, &$params): void {
        $names[] = '`' . $name . '`';
        $placeholders[] = $placeholder;
        if ($placeholder === '?') {
            $params[] = $value;
        }
    };

    foreach (['username', 'email', 'password_hash', 'full_name', 'role_key'] as $name) {
        if (!isset($columns[$name])) {
            throw new InvalidArgumentException(
                'The users table is missing the required column ' . $name . ', so the account was not saved.'
            );
        }
        $push($name, '?', $fields[$name]);
    }

    if (isset($columns['student_id'])) {
        $studentId = $fields['student_id'];
        $omitForDefault = $studentId === null
            && !umColumnIsNullable($columns['student_id'])
            && umColumnHasDefault($columns['student_id']);
        if ($studentId === null && !umColumnIsNullable($columns['student_id']) && !umColumnHasDefault($columns['student_id'])) {
            throw new InvalidArgumentException(
                'The users table requires a student id, so the account was not saved.'
            );
        }
        if (!$omitForDefault) {
            $push('student_id', '?', $studentId);
        }
    } elseif ($fields['student_id'] !== null && $fields['student_id'] !== '') {
        throw new InvalidArgumentException(
            'The users table is missing the student_id column, so this student account was not saved.'
        );
    }

    if (!isset($columns['status'])) {
        throw new InvalidArgumentException(
            'The users table is missing the required column status, so the account was not saved.'
        );
    }
    $push('status', '?', $fields['status']);

    if (isset($columns['notes'])) {
        $note = $fields['notes'] !== '' ? $fields['notes'] : null;
        if ($note === null && !umColumnIsNullable($columns['notes'])) {
            if (!umColumnHasDefault($columns['notes'])) {
                $push('notes', '?', '');
            }
        } else {
            $push('notes', '?', $note);
        }
    } elseif (($fields['notes'] ?? '') !== '') {
        throw new InvalidArgumentException(
            'The users table is missing the notes column, so the account was not saved. Clear Notes or add the column, then try again.'
        );
    }

    if (isset($columns['password_changed_at'])) {
        $push('password_changed_at', 'NOW()', null);
    }

    foreach (['must_change_password' => 0, 'failed_login_attempts' => 0] as $name => $value) {
        if (!isset($columns[$name]) || umColumnIsNullable($columns[$name]) || umColumnHasDefault($columns[$name]) || umColumnIsGenerated($columns[$name])) {
            continue;
        }
        $push($name, '?', $value);
    }
    foreach (['created_at', 'updated_at'] as $name) {
        if (!isset($columns[$name]) || umColumnIsNullable($columns[$name]) || umColumnHasDefault($columns[$name]) || umColumnIsGenerated($columns[$name])) {
            continue;
        }
        $push($name, 'NOW()', null);
    }

    if (isset($columns['id'])) {
        $extra = strtolower((string) ($columns['id']['Extra'] ?? ''));
        if (!str_contains($extra, 'auto_increment') && !umColumnHasDefault($columns['id']) && !umColumnIsNullable($columns['id'])) {
            throw new InvalidArgumentException(
                'The users table id column is missing AUTO_INCREMENT, so a new account cannot be saved. Nothing was changed. Run database/patches/fix_users_autoincrement.sql (it adds a PRIMARY KEY on id when that key is missing, then sets AUTO_INCREMENT).'
            );
        }
    }

    $writing = [];
    foreach ($names as $quoted) {
        $writing[strtolower(trim($quoted, '`'))] = true;
    }
    foreach ($columns as $name => $column) {
        if (isset($writing[$name]) || umColumnIsNullable($column) || umColumnHasDefault($column) || umColumnIsGenerated($column)) {
            continue;
        }
        throw new InvalidArgumentException(
            'The users table column ' . $name . ' is required and has no default, so the account was not saved.'
        );
    }

    return [
        'sql' => 'INSERT INTO `sms2_users` (' . implode(', ', $names) . ') VALUES (' . implode(', ', $placeholders) . ')',
        'params' => $params,
    ];
}

/**
 * @param array<string, mixed> $fields
 * @param array<string, array<string, mixed>> $columns
 * @return array{sql: string, params: list<mixed>}
 */
function umUserUpdateStatement(array $columns, array $fields, int $id): array
{
    foreach (['role_key', 'status'] as $enumColumn) {
        if (isset($columns[$enumColumn])) {
            umAssertEnumValue($columns[$enumColumn], $enumColumn, (string) $fields[$enumColumn]);
        }
    }

    $sets = [];
    $params = [];
    foreach (['full_name', 'username', 'email', 'role_key', 'status'] as $name) {
        if (!isset($columns[$name])) {
            throw new InvalidArgumentException(
                'The users table is missing the required column ' . $name . ', so the account was not saved.'
            );
        }
        $sets[] = '`' . $name . '`=?';
        $params[] = $fields[$name];
    }

    if (isset($columns['notes'])) {
        $note = $fields['notes'] !== '' ? $fields['notes'] : null;
        if ($note === null && !umColumnIsNullable($columns['notes'])) {
            if (!umColumnHasDefault($columns['notes'])) {
                $sets[] = '`notes`=?';
                $params[] = '';
            }
        } else {
            $sets[] = '`notes`=?';
            $params[] = $note;
        }
    } elseif (($fields['notes'] ?? '') !== '') {
        throw new InvalidArgumentException(
            'The users table is missing the notes column, so the account was not saved. Clear Notes or add the column, then try again.'
        );
    }

    if ($fields['student_id'] !== null) {
        if (!isset($columns['student_id'])) {
            throw new InvalidArgumentException(
                'The users table is missing the student_id column, so this student account was not saved.'
            );
        }
        $sets[] = '`student_id`=?';
        $params[] = $fields['student_id'];
    }

    $params[] = $id;

    return [
        'sql' => 'UPDATE `sms2_users` SET ' . implode(', ', $sets) . ' WHERE `id`=?',
        'params' => $params,
    ];
}

function umDbMessageColumn(string $msg): string
{
    $patterns = [
        "/Unknown column '([^']+)'/i",
        "/Field '([^']+)' doesn't have a default value/i",
        "/Column '([^']+)' cannot be null/i",
        "/for column '([^']+)'/i",
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $msg, $match) === 1 && preg_match('/^[A-Za-z0-9_.]+$/', $match[1]) === 1) {
            return $match[1];
        }
    }

    return '';
}

function umRedactDbMessage(string $msg): string
{
    $msg = preg_replace('/\$2[aby]\$\d{2}\$[^\s\'"]+/', '[redacted]', $msg) ?? $msg;
    $msg = preg_replace('/\$argon2[^\s\'"]+/i', '[redacted]', $msg) ?? $msg;
    $msg = preg_replace('/\b(INSERT|UPDATE|DELETE|REPLACE)\b.+$/i', '[query omitted]', $msg) ?? $msg;
    $msg = preg_replace('/\s+/', ' ', trim($msg)) ?? $msg;
    if (strlen($msg) > 240) {
        $msg = substr($msg, 0, 237) . '...';
    }

    return $msg;
}

function umViewerIsSuperAdmin(): bool
{
    if (!function_exists('getCurrentUserRoleKey') || !function_exists('smsNormalizeRoleKey')) {
        return false;
    }

    return smsNormalizeRoleKey((string) getCurrentUserRoleKey()) === 'superadmin';
}

function umPublicDbError(PDOException $e): string
{
    $message = umMapPublicDbError($e);
    if (!umViewerIsSuperAdmin()) {
        return $message;
    }

    $info = is_array($e->errorInfo ?? null) ? $e->errorInfo : [];
    $sqlstate = (string) ($info[0] ?? '');
    $errno = isset($info[1]) && is_numeric($info[1]) ? (string) (int) $info[1] : '';
    $driverMessage = (string) ($info[2] ?? '');
    if ($driverMessage === '') {
        $driverMessage = $e->getMessage();
    }
    $detail = umRedactDbMessage(trim($sqlstate . ' ' . $errno . ' ' . $driverMessage));
    if ($detail === '' || str_contains($message, $detail)) {
        return $message;
    }

    return $message . ' Database detail: ' . $detail;
}

function umMapPublicDbError(PDOException $e): string
{
    $info = is_array($e->errorInfo ?? null) ? $e->errorInfo : [];
    $sqlstate = (string) ($info[0] ?? '');
    $errno = isset($info[1]) && is_numeric($info[1]) ? (int) $info[1] : 0;
    $driverMessage = (string) ($info[2] ?? '');
    $msg = $driverMessage !== '' ? $driverMessage : $e->getMessage();
    $full = $sqlstate . ' ' . $errno . ' ' . $msg . ' ' . $e->getMessage();
    $column = umDbMessageColumn($msg . ' ' . $e->getMessage());
    error_log('save-user PDO sqlstate=' . $sqlstate . ' errno=' . $errno . ' ' . umRedactDbMessage($e->getMessage()));

    $is = static function (int $code, string $state, string $phrase) use ($errno, $sqlstate, $full): bool {
        if ($code > 0 && $errno === $code) {
            return true;
        }
        if ($state !== '' && ($sqlstate === $state || str_contains($full, 'SQLSTATE[' . $state . ']'))) {
            return true;
        }
        if ($phrase !== '' && str_contains(strtolower($full), strtolower($phrase))) {
            return true;
        }
        if ($code > 0 && preg_match('/\b' . $code . '\b/', $full) === 1) {
            return true;
        }

        return false;
    };

    if ($is(1452, '23000', 'foreign key') && ($errno === 1452 || str_contains(strtolower($full), 'foreign key') || preg_match('/\b1452\b/', $full) === 1)) {
        return 'That role is not available in the database yet. Refresh User Accounts and try again.';
    }
    if ($is(1054, '42S22', 'unknown column')) {
        $named = $column !== '' ? $column : 'that the save uses';
        return 'The users table is missing the column ' . $named . ', so the account was not saved.';
    }
    if ($is(1364, 'HY000', "doesn't have a default value") && ($errno === 1364 || str_contains(strtolower($full), "doesn't have a default value") || preg_match('/\b1364\b/', $full) === 1)) {
        if ($column === 'id' || str_ends_with($column, '.id')) {
            return 'The users table id column is missing AUTO_INCREMENT, so the account was not saved. Run database/patches/fix_users_autoincrement.sql (it adds a PRIMARY KEY on id when that key is missing, then sets AUTO_INCREMENT).';
        }
        $named = $column !== '' ? $column : 'a required column';
        return 'The users table column ' . $named . ' has no default value, so the account was not saved.';
    }
    if ($is(1075, '42000', 'must be defined as a key') && ($errno === 1075 || str_contains(strtolower($full), 'must be defined as a key') || preg_match('/\b1075\b/', $full) === 1)) {
        return 'The users table id column is not a key, so it cannot be AUTO_INCREMENT and the account was not saved. Run database/patches/fix_users_autoincrement.sql.';
    }
    if ($is(1048, '23000', 'cannot be null') && ($errno === 1048 || str_contains(strtolower($full), 'cannot be null') || preg_match('/\b1048\b/', $full) === 1)) {
        $named = $column !== '' ? $column : 'a required field';
        return 'The users table requires a value for ' . $named . ', so the account was not saved.';
    }
    if ($is(1406, '22001', 'data too long')) {
        $named = $column !== '' ? ' (' . $column . ')' : '';
        return 'One of the fields' . $named . ' is too long. Shorten the name, username, email, or notes and try again.';
    }
    if (str_contains($full, 'uq_users_username') || str_contains($full, "for key 'username'")) {
        return 'That username is already in use. Choose a different username.';
    }
    if (str_contains($full, 'uq_users_email') || str_contains($full, "for key 'email'")) {
        return 'That email address is already in use. Choose a different email.';
    }
    if (str_contains($full, 'uniq_raa_adviser_identity')) {
        return 'An adviser assignment with this name and email already exists, so the account was not created.';
    }
    if (str_contains($full, 'uniq_rca_')) {
        return 'A research coordinator assignment already exists for this account, so the user was not created.';
    }
    if ($is(1062, '23000', 'duplicate') && ($errno === 1062 || str_contains(strtolower($full), 'duplicate') || preg_match('/\b1062\b/', $full) === 1)) {
        return 'That username or email is already in use.';
    }
    if ($is(1265, '', 'data truncated') || $is(1264, '', '') || $is(1366, '', 'incorrect string value') || $is(1366, '', 'incorrect integer value')) {
        $named = $column !== '' ? $column : 'status or role';
        return 'The database does not allow that value for ' . $named . ', so the account was not saved.';
    }
    if ($is(1292, '22007', 'incorrect datetime')) {
        $named = $column !== '' ? $column : 'a date field';
        return 'The database rejected the date value for ' . $named . ', so the account was not saved.';
    }
    if (str_contains(strtolower($full), 'no active transaction')) {
        return 'The save transaction was already closed, so the account may already exist. Refresh User Accounts before trying again.';
    }

    error_log('save-user PDO unmapped sqlstate=' . $sqlstate . ' errno=' . $errno);

    return 'The account could not be saved because the database rejected it. Nothing was changed.';
}
