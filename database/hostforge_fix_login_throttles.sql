-- HostForge one-shot repair: sms_login_throttles
-- Run in phpMyAdmin on your hf_db_* database if login shows:
--   Field 'id' doesn't have a default value
--
-- Prefer deploying updated PHP (auto-repairs on next login).
-- Use this SQL only when you need a manual fix before redeploy.

-- 1) Clear rows (throttle data is ephemeral)
TRUNCATE TABLE sms_login_throttles;

-- 2) Rebuild structure (works whether or not PK/AI already exist)
ALTER TABLE sms_login_throttles
  MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;

-- If "Multiple primary key defined" appears, skip this next statement:
ALTER TABLE sms_login_throttles
  ADD PRIMARY KEY (id);

-- If "Duplicate key name" appears, skip that statement:
ALTER TABLE sms_login_throttles
  ADD UNIQUE KEY uq_login_throttle_key (throttle_key);

ALTER TABLE sms_login_throttles
  ADD KEY idx_login_throttle_ip (ip_address);

ALTER TABLE sms_login_throttles
  ADD KEY idx_login_throttle_locked (locked_until);
