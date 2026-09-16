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
 * SMS2 main database (login / users / roles) — usually attached in the deploy wizard.
 * HostForge injects DB_* for the attached database. Use the INTERNAL values
 * that work from the app container (often port 3306 even if the UI shows another port).
 *
 * DB_HOST=mariadb-XXXX.internal
 * DB_PORT=3306
 * DB_DATABASE=hf_db_XXXX
 * DB_USERNAME=hf_XXXX
 * DB_PASSWORD=<from HostForge Databases → sms2_db>
 * DB_CONNECTION=mysql
 * DB_CHARSET=utf8mb4
 */

/*
 * CRAD module database — second MariaDB. HostForge does NOT auto-inject this.
 * Set CRAD_DB_* explicitly. Do not reuse DB_DATABASE / DB_PASSWORD for CRAD.
 *
 * Prefer the in-cluster MariaDB port (usually 3306). The Databases page may
 * show a published port (e.g. 33632); that often fails from the app pod.
 *
 * CRAD_DB_HOST=mariadb-XXXX.internal
 * CRAD_DB_PORT=3306
 * CRAD_DB_NAME=hf_db_XXXX
 * CRAD_DB_USER=hf_XXXX
 * CRAD_DB_PASS=<from HostForge Databases → crad_db>
 * CRAD_DB_CHARSET=utf8mb4
 */
