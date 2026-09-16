<?php
/**
 * HostForge Platform — environment variable reference
 *
 * Do NOT copy this file to config/local.php on HostForge.
 * Set these on the workspace (or application) → Environment Variables,
 * then create a new production deployment so the container picks them up.
 *
 * When DB_* env vars are present, config/local.php is skipped automatically
 * so XAMPP localhost settings cannot override the cloud database.
 *
 * Health check after deploy:
 *   /setup/health.php?token=YOUR_SMS2_DEPLOY_TOKEN
 */

// Optional: protect setup/health.php and setup/deploy-db.php
// define('SMS2_DEPLOY_TOKEN', 'choose-a-long-random-secret');

/*
 * Unified SMS2 + CRAD database (one MariaDB).
 * HostForge injects DB_* for the attached database. Use the INTERNAL values
 * that work from the app container (often port 3306 even if the UI shows another port).
 *
 * Import ONLY database/sms2_db.sql into DB_DATABASE. That dump contains:
 *   - sms_* tables (users, roles, …)
 *   - crad_* tables (title_approvals, research_groups, …)
 *
 * Current provisioned database (HostForge):
 * DB_HOST=mariadb-5yxohe3e.internal
 * DB_PORT=3306
 * DB_DATABASE=hf_db_5yxohe3e
 * DB_USERNAME=hf_etho0gcrm7
 * DB_PASSWORD=<from HostForge Databases → attached DB>
 * DB_CONNECTION=mysql
 * DB_CHARSET=utf8mb4
 * DATABASE_URL=mysql://…@mariadb-5yxohe3e.internal:3306/hf_db_5yxohe3e
 *
 * CRAD reuses the same database by default (CRAD_DB_NAME defaults to DB_NAME).
 * Stale CRAD_DB_* env vars (e.g. mariadb-edee97zl / hf_db_edee97zl) are IGNORED
 * unless you set CRAD_FORCE_SEPARATE=1 for a rare intentional split install.
 *
 * You do NOT need a second MariaDB or CRAD_DB_* pointing at a separate host.
 * Remove obsolete CRAD_DB_* that pointed at a dead separate MariaDB
 * (e.g. mariadb-edee97zl / crad_db) so the health page stops listing them.
 *
 * Paste DB_* into HostForge Environment Variables (or a local gitignored .env).
 * Import database/sms2_db.sql into DB_DATABASE (hf_db_5yxohe3e), not into a
 * newly created database named sms2_db.
 */
