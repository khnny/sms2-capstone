-- Fix HostForge / imported sms2_db where login_throttles.id has no AUTO_INCREMENT.
-- Run this on the attached sms2_db (hf_db_*), then retry login.
-- Without this, failed logins crash with: Field 'id' doesn't have a default value

ALTER TABLE login_throttles
  MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;

-- Required for ON DUPLICATE KEY UPDATE attempt counting
ALTER TABLE login_throttles
  ADD UNIQUE KEY uq_login_throttle_key (throttle_key);

-- Clear any stuck lock rows after failed attempts during the crash
DELETE FROM login_throttles;
