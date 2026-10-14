-- Fix HostForge activity logs: id must be AUTO_INCREMENT (error 1364).
ALTER TABLE `sms2_activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;