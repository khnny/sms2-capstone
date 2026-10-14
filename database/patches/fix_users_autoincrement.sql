-- Fix HostForge sms2_users.id (errors 1364 and 1075).
-- Partial imports leave id as int(10) unsigned with no key and no AUTO_INCREMENT.
-- AUTO_INCREMENT is rejected with 1075 until id is a key, so the key comes first.
-- Re-runnable: existing rows stay. A primary key that already exists is left alone.

-- 1. PRIMARY KEY (id) only when the table has no primary key.
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
PREPARE sms2_users_ai_stmt FROM @sms2_users_ddl;
EXECUTE sms2_users_ai_stmt;
DEALLOCATE PREPARE sms2_users_ai_stmt;

-- 2. If a primary key already exists on another column, id still needs a key
--    before AUTO_INCREMENT. Do not add a second primary key.
SET @sms2_users_ddl = (
    SELECT IF(
        COUNT(*) > 0,
        'SELECT 1',
        'ALTER TABLE `sms2_users` ADD KEY `idx_sms2_users_id` (`id`)'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sms2_users'
      AND COLUMN_NAME = 'id'
);
PREPARE sms2_users_ai_stmt FROM @sms2_users_ddl;
EXECUTE sms2_users_ai_stmt;
DEALLOCATE PREPARE sms2_users_ai_stmt;

-- 3. Restore AUTO_INCREMENT only when it is missing.
SET @sms2_users_ddl = (
    SELECT IF(
        LOCATE('auto_increment', LOWER(IFNULL(EXTRA, ''))) > 0,
        'SELECT 1',
        'ALTER TABLE `sms2_users` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'sms2_users'
      AND COLUMN_NAME = 'id'
    LIMIT 1
);
PREPARE sms2_users_ai_stmt FROM @sms2_users_ddl;
EXECUTE sms2_users_ai_stmt;
DEALLOCATE PREPARE sms2_users_ai_stmt;
