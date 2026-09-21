HostForge / phpMyAdmin import
=============================

Do NOT import database/sms2_db.sql in phpMyAdmin.
That file uses DELIMITER and CREATE TRIGGER, which HostForge's importer rejects.

HostForge Database Import UI
----------------------------
Prefer this path when using HostForge's own database import upload (not phpMyAdmin):

  - hostforge_import.sql  (~86 KB)  -- lean schema + tiny seed

Steps:
1. Wait until the database status shows Running.
2. Open HostForge Database Import UI for your existing hf_db_* / sms2_db database.
3. Upload hostforge_import.sql (schema + roles/settings).
4. Upload hostforge_demo_users.sql (demo logins with known passwords).
5. Confirm tables exist (sms_* + crad_*). Login with demo accounts (no /setup needed).

Demo logins (after hostforge_demo_users.sql):
  - superadmin / @Superadmin123
  - cradofficer / @Cradofficer123
  - deptchair / @Department123
  - depthead / @Depthead123
  - s230000001 / @Kenneth8080

Do NOT upload sms2_db_phpmyadmin_safe.sql (~1.2 MB) to HostForge Import UI --
it often fails with "file may not be a dump it recognises."

HostForge Database Import UI files
----------------------------------
  - hostforge_import.sql       (~86 KB)  -- lean schema + tiny seed
  - hostforge_demo_users.sql   (~18 KB)  -- demo accounts (upload 2nd)

phpMyAdmin / larger dumps (optional)
------------------------------------
Use these files instead (same data, no triggers) if you have phpMyAdmin:

  1) Prefer split (smaller uploads):
     - 01_sms_tables.sql   (~655 KB)  -- auth/users (sms_*)
     - 02_crad_tables.sql  (~580 KB)  -- research (crad_*)

  2) Or one file:
     - sms2_db_phpmyadmin_safe.sql  (~1.2 MB)

Steps on HostForge phpMyAdmin
-----------------------------
1. Open phpMyAdmin for your app database (hf_db_... / sms2_db).
2. Select that database in the left sidebar (do NOT create crad_db).
3. Import tab -> choose 01_sms_tables.sql -> Go.
4. Import tab -> choose 02_crad_tables.sql -> Go.
5. Confirm ~64 tables (sms_* + crad_*).

If import still fails
--------------------
- Max size: use hostforge_import.sql (HostForge UI) or the two split files (phpMyAdmin).
- "Table already exists": drop sms_* and crad_* tables first, or empty the DB, then re-import.
- Timeout: import 01 then 02 separately; wait for each to finish.
- Wrong DB: you must import into the same DB named in config/local.php (DB_NAME).
- Login error "Field 'id' doesn't have a default value": deploy updated PHP
  (auto-repairs sms_login_throttles) or run ../hostforge_fix_login_throttles.sql once.

Triggers are optional; the app recreates needed CRAD notification triggers at runtime.
Full dump with triggers (CLI only): ../sms2_db.sql
