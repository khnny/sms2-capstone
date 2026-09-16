SMS 2 – Database & Security Adoption
====================================

1. Start Apache + MySQL (XAMPP)
2. Copy the project folder into htdocs. Any folder name is OK.
3. Install schema (once), from the project folder:
     C:\xampp\php\php.exe database\install.php
4. Open setup and create YOUR Super Admin (no demo accounts):
     http://localhost/<your-folder-name>/setup/
5. After setup, add staff/student users in User Management.

If the other computer has a MySQL password or different database names, copy:
     config\local.example.php
to:
     config\local.php
then edit the values there.

What install creates:
  - roles, role_permissions, system_settings, empty users table

What install does NOT create:
  - demo logins / sample users

Deployment migration
--------------------
For web/database deployment, run both SQL dumps with:

     php database/migrate.php

This runner calls:
  - database/sms2_db.sql
  - modules/crad/database/crad_db.sql

HostForge (hostforgeplatform.cloud)
----------------------------------
SMS 2 is PHP (not Laravel). Use php database/migrate.php — not artisan migrate.

How HostForge wires databases (read this first)
  - On deploy, choose MySQL (MariaDB). HostForge injects seven variables onto
    the APPLICATION: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD,
    DB_CONNECTION, DB_CHARSET. SMS 2 reads these names automatically.
  - Only the database you ATTACH during the deploy wizard gets those DB_* keys.
    A second provisioned database does NOT update DB_* — you must add CRAD_DB_*
    manually from that database's own connection row on the Databases page.
  - The host is internal (e.g. mariadb-vnbeokwb.internal) — reachable only from
    your app inside the workspace, not from your laptop.
  - Migrations/schema do NOT run automatically. An empty attached database looks
    like a broken login: the connection works but users/roles tables are missing.

Step-by-step
1. Deploy the app and attach ONE MySQL database (the main SMS2 / login database).
2. Do NOT upload config/local.php with XAMPP localhost settings. When DB_* env
   vars are present, the app skips local.php automatically.
3. Verify which database has your tables. On the Databases page, open the
   ATTACHED database console (the name in DB_DATABASE, e.g. hf_db_xxxx) and run:
     SHOW TABLES;
   You need tables like users, roles, system_settings. If empty or users is
   missing, import database/sms2_db.sql INTO THIS DATABASE. Do not create a
   separate database named sms2_db unless that is the attached DB_DATABASE.
   The dump drops leftover tables (like login_throttles) so a re-upload can
   replace an empty HostForge schema.
   Web helper: /setup/deploy-db.php?token=YOUR_SMS2_DEPLOY_TOKEN
4. If you provisioned a second database for CRAD, import
   modules/crad/database/crad_db.sql there, then add Environment Variables
   from THAT database's row (HostForge does not copy these for you):

     CRAD_DB_HOST=mariadb-yyyy.internal
     CRAD_DB_PORT=33632
     CRAD_DB_NAME=hf_db_yyyy
     CRAD_DB_USER=<crad user>
     CRAD_DB_PASS=<crad password>
     CRAD_DB_CHARSET=utf8mb4

   Without CRAD_DB_HOST and CRAD_DB_NAME, CRAD wrongly falls back to DB_HOST /
   DB_DATABASE (the first attached database).

5. Run schema (app web terminal, after first deploy):
     php database/migrate.php --sms2-only
   Or HostForge Databases → Import sms2_db.sql into DB_DATABASE.
   Or /setup/deploy-db.php?token=YOUR_TOKEN (sms2-only by default).

6. Optional env vars:
     SMS2_DEPLOY_TOKEN=<plain random secret — not PHP code>
     SMS2_BASE_URL=<public path if auto-detect is wrong>

   See config/local.hostforge.example.php for a full reference.

7. Redeploy after changing Environment Variables.
8. Open: https://YOUR-SITE/setup/health.php?token=YOUR_TOKEN
   Confirm users count > 0 and both DB connections are OK.
9. Log in (e.g. superadmin@bestlink.edu.ph). Import does not reset passwords.

If login says "invalid credentials" but health check shows users > 0:
  - Wrong password — reset in the ATTACHED database console (see below).
  - You may have imported users into the wrong database — SHOW TABLES on the
    database named in DB_DATABASE (e.g. hf_db_vnbeokwb), not a second instance.
  - Clear lockouts:
      DELETE FROM login_throttles;
      UPDATE users SET failed_login_attempts = 0, locked_until = NULL;
  - Complete the "Verify you are human" CAPTCHA before Sign In.

Password reset (attached database console or app terminal):
     php -r "echo password_hash('YourNewPassword123!', PASSWORD_DEFAULT);"
  Copy the hash, then in the ATTACHED database SQL console:
     UPDATE users SET password_hash = 'PASTE_HASH_HERE',
       failed_login_attempts = 0, locked_until = NULL, must_change_password = 0
     WHERE email = 'superadmin@bestlink.edu.ph';

CLI migrate options:
  php database/migrate.php
  php database/migrate.php --force
  php database/migrate.php --fresh   (DESTROYS DATA)

Docker startup option:
  Set SMS2_RUN_MIGRATIONS=1 to run database/migrate.php before Apache starts.

InfinityFree (free hosting)
---------------------------
InfinityFree has no SSH, so use the web deploy helper instead of CLI migrate.

1. Sign up at https://infinityfree.net and create a hosting account.
2. vPanel → MySQL Databases: create one database. Copy hostname, db name, user, password.
3. Copy config/local.infinityfree.example.php to config/local.php on the server.
   Fill in MySQL values. Use the SAME db name for all module defines (free plan = 1 database).
4. Upload the project to htdocs via FTP (FileZilla). Put files in htdocs root if possible.
5. Replace .htaccess with .htaccess.infinityfree if the site shows HTTP 500
   (InfinityFree often blocks php_value in .htaccess).
6. Open: https://YOUR-SITE.infinityfreeapp.com/setup/deploy-db.php?token=YOUR_TOKEN
7. Open: https://YOUR-SITE.infinityfreeapp.com/setup/ and create the Super Admin.
8. Remove SMS2_DEPLOY_TOKEN from config/local.php after migration succeeds.

Alternative: import database/sms2_db.sql and modules/crad/database/crad_db.sql
via phpMyAdmin instead of step 6.
