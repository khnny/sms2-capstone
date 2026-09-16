-- HostForge sms2_db (attached login DB) — import via Databases → sms2_db → Import
-- 1) Stop fatal crash on failed login (Field 'id' doesn't have a default value)
-- 2) Reset CRAD Officer password to documented default (@Cradofficer123)
-- 3) Clear lockout rows so retries work

ALTER TABLE sms_login_throttles
  MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT;

-- Ignore error if unique key already exists
ALTER TABLE sms_login_throttles
  ADD UNIQUE KEY uq_login_throttle_key (throttle_key);

DELETE FROM sms_login_throttles;

-- Dump hash for cradofficer does not match @Cradofficer123; reset it.
UPDATE sms_users
SET password_hash = '$2y$10$bws9bC4veetad0dmgt0kjeRl1nTQvQMGHXVsVEmZSLtFuSVCpMt2i',
    email = 'cradofficer@bestlink.edu.ph',
    failed_login_attempts = 0,
    locked_until = NULL,
    must_change_password = 0,
    password_changed_at = NOW(),
    status = 'active'
WHERE username = 'cradofficer'
   OR email IN ('cradofficer@bestlink.edu.ph', 'cradofficer@bestlink.ph');

-- Ensure adviser role permission exists (seed previously omitted this)
INSERT INTO sms_roles (role_key, label, description)
VALUES ('adviser', 'Adviser', 'Research adviser faculty account')
ON DUPLICATE KEY UPDATE label = VALUES(label), description = VALUES(description);

INSERT INTO sms_role_permissions (role_key, module_key, granted)
VALUES ('adviser', 'faculty', 1)
ON DUPLICATE KEY UPDATE granted = 1;
