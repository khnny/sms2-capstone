HostForge / phpMyAdmin import
=============================

Do NOT import database/sms2_db.sql in phpMyAdmin.
That file uses DELIMITER and CREATE TRIGGER, which HostForge's importer rejects.

Use these files instead (same data, no triggers):

  1) Prefer split (smaller uploads):
     - 01_sms_tables.sql   (~655 KB)  — auth/users (sms_*)
     - 02_crad_tables.sql  (~580 KB)  — research (crad_*)

  2) Or one file:
     - sms2_db_phpmyadmin_safe.sql  (~1.2 MB)

Steps on HostForge
------------------
1. Open phpMyAdmin for your app database (hf_db_... / sms2_db).
2. Select that database in the left sidebar (do NOT create crad_db).
3. Import tab → choose 01_sms_tables.sql → Go.
4. Import tab → choose 02_crad_tables.sql → Go.
5. Confirm ~64 tables (sms_* + crad_*).

If import still fails
--------------------
- Max size: use the two split files, not the full dump.
- "Table already exists": drop sms_* and crad_* tables first, or empty the DB, then re-import.
- Timeout: import 01 then 02 separately; wait for each to finish.
- Wrong DB: you must import into the same DB named in config/local.php (DB_NAME).

Triggers are optional; the app recreates needed CRAD notification triggers at runtime.
Full dump with triggers (CLI only): ../sms2_db.sql
