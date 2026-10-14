-- Additive, re-runnable repair for columns Super Admin Add User writes.
-- Safe on HostForge data: adds missing nullable columns and restores
-- AUTO_INCREMENT on sms2_users.id. Does not delete or rewrite account rows.
-- The application applies the same changes when it can ALTER the table.

SET @sms2_users_ddl = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `sms2_users` ADD COLUMN `password_changed_at` DATETIME NULL',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sms2_users'
      AND COLUMN_NAME = 'password_changed_at'
);
PREPARE sms2_users_stmt FROM @sms2_users_ddl;
EXECUTE sms2_users_stmt;
DEALLOCATE PREPARE sms2_users_stmt;

SET @sms2_users_ddl = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `sms2_users` ADD COLUMN `notes` TEXT NULL',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sms2_users'
      AND COLUMN_NAME = 'notes'
);
PREPARE sms2_users_stmt FROM @sms2_users_ddl;
EXECUTE sms2_users_stmt;
DEALLOCATE PREPARE sms2_users_stmt;

SET @sms2_users_ddl = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `sms2_users` ADD COLUMN `student_id` VARCHAR(40) NULL',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sms2_users'
      AND COLUMN_NAME = 'student_id'
);
PREPARE sms2_users_stmt FROM @sms2_users_ddl;
EXECUTE sms2_users_stmt;
DEALLOCATE PREPARE sms2_users_stmt;

SET @sms2_users_ddl = (
    SELECT IF(
        COUNT(*) > 0,
        'SELECT 1',
        'ALTER TABLE `sms2_users` ADD PRIMARY KEY (`id`)'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sms2_users'
      AND INDEX_NAME = 'PRIMARY'
);
PREPARE sms2_users_stmt FROM @sms2_users_ddl;
EXECUTE sms2_users_stmt;
DEALLOCATE PREPARE sms2_users_stmt;

SET @sms2_users_ddl = (
    SELECT IF(
        LOCATE('auto_increment', LOWER(EXTRA)) > 0,
        'SELECT 1',
        CONCAT(
            'ALTER TABLE `sms2_users` MODIFY `id` ',
            COLUMN_TYPE,
            IF(IS_NULLABLE = 'YES', ' NULL', ' NOT NULL'),
            ' AUTO_INCREMENT'
        )
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sms2_users'
      AND COLUMN_NAME = 'id'
    LIMIT 1
);
PREPARE sms2_users_stmt FROM @sms2_users_ddl;
EXECUTE sms2_users_stmt;
DEALLOCATE PREPARE sms2_users_stmt;
