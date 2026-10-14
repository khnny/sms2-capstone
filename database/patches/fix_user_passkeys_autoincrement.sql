-- Fix HostForge sms2_user_passkeys: id must be AUTO_INCREMENT (error 1364),
-- and credential_id wide enough for base64url WebAuthn ids.
ALTER TABLE `sms2_user_passkeys`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  MODIFY `credential_id` varchar(1024) NOT NULL;

-- Optional indexes (ignore errors if they already exist)
-- ALTER TABLE `sms2_user_passkeys` ADD UNIQUE KEY `uq_passkey_cred` (`credential_id`(255));
-- ALTER TABLE `sms2_user_passkeys` ADD KEY `idx_passkey_user` (`user_id`);