SMS 2 – Database & Security Adoption
====================================

Architecture (unified database)
-------------------------------
One MariaDB database holds both:
  - sms_* tables (users, roles, login, settings, …)
  - crad_* tables (title_approvals, research_groups, grants, …)

Import ONLY database/sms2_db.sql. Do not run a second crad_db import unless
you intentionally keep CRAD_DB_NAME different from DB_NAME.

XAMPP (local)
-------------
1. Start Apache + MySQL (XAMPP)
2. Copy the project folder into htdocs. Any folder name is OK.
3. Install schema (once), from the project folder:
     C:\xampp\php\php.exe database\install.php
   Or:
     C:\xampp\php\php.exe database\migrate.php --force
4. Open setup and create YOUR Super Admin (no demo accounts):
     http://localhost/<your-folder-name>/setup/
5. After setup, add staff/student users in User Management.

If the other computer has a MySQL password or different database names, copy:
     config\local.example.php
to:
     config\local.php
then edit the values there. Set CRAD_DB_NAME to the same value as DB_NAME
(default in local.example.php is already sms2_db).

What the unified dump creates:
  - sms_roles, sms_role_permissions, sms_system_settings, sms_users, …
  - crad_title_approvals, crad_research_groups, crad_grant_*, …

What install does NOT create:
  - demo logins / sample users

Deployment migration
--------------------
For web/database deployment:

     php database/migrate.php

By default this imports database/sms2_db.sql (sms_* + crad_*) into DB_NAME.
modules/crad/database/crad_db.sql is applied ONLY when CRAD_DB_NAME differs
from DB_NAME (legacy split install).

HostForge (hostforgeplatform.cloud)
----------------------------------
SMS 2 is PHP (not Laravel). Use php database/migrate.php — not artisan migrate.

How HostForge wires the database
  - On deploy, choose MySQL (MariaDB). HostForge injects seven variables onto
    the APPLICATION: DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD,
    DB_CONNECTION, DB_CHARSET. SMS 2 reads these names automatically.
  - Attach ONE database (e.g. hf_db_5yxohe3e). CRAD reuses it: CRAD_DB_NAME
    defaults to DB_NAME / DB_DATABASE. You do NOT need a second MariaDB or
    CRAD_DB_* pointing at a separate host.
  - Remove obsolete CRAD_DB_* that pointed at a dead separate MariaDB
    (e.g. mariadb-edee97zl / crad_db).
  - Migrations/schema do NOT run automatically. An empty attached database
    looks like a broken login: connection works but sms_users is missing.

Step-by-step
1. Deploy the app and attach ONE MySQL database.
2. Do NOT upload config/local.php with XAMPP localhost settings. When DB_* env
   vars are present, the app skips local.php automatically.
3. Import database/sms2_db.sql INTO the attached DB_DATABASE
   (e.g. hf_db_5yxohe3e). Do not create a separate database named sms2_db
   unless that is the attached DB_DATABASE.
   Verify:
     SHOW TABLES LIKE 'sms_%';
     SHOW TABLES LIKE 'crad_%';
   You need sms_users, sms_roles, and crad_title_approvals (among others).
   Web helper: /setup/deploy-db.php?token=YOUR_SMS2_DEPLOY_TOKEN
4. Optional: set CRAD_DB_NAME to the same value as DB_DATABASE (redundant;
   the app already defaults CRAD_DB_NAME to DB_NAME).
5. Run schema (app web terminal, after first deploy):
     php database/migrate.php --force
   Or HostForge Databases → Import sms2_db.sql into DB_DATABASE.
   Or /setup/deploy-db.php?token=YOUR_TOKEN
6. Optional env vars:
     SMS2_DEPLOY_TOKEN=<plain random secret — not PHP code>
     SMS2_BASE_URL=<public path if auto-detect is wrong>

   See config/local.hostforge.example.php for a full reference.

7. Redeploy after changing Environment Variables.
8. Open: https://YOUR-SITE/setup/health.php?token=YOUR_TOKEN
   Confirm sms_users count > 0, crad_title_approvals present, and CRAD shows
   same-database mode.
9. Log in (e.g. superadmin@bestlink.edu.ph). Import does not reset passwords.

If login says "invalid credentials" but health check shows sms_users > 0:
  - Wrong password — reset in the ATTACHED database console (see below).
  - You may have imported into the wrong database — SHOW TABLES on the
    database named in DB_DATABASE.
  - Clear lockouts:
      DELETE FROM sms_login_throttles;
      UPDATE sms_users SET failed_login_attempts = 0, locked_until = NULL;
  - Complete the "Verify you are human" CAPTCHA before Sign In.

Password reset (attached database console or app terminal):
     php -r "echo password_hash('YourNewPassword123!', PASSWORD_DEFAULT);"
  Copy the hash, then in the ATTACHED database SQL console:
     UPDATE sms_users SET password_hash = 'PASTE_HASH_HERE',
       failed_login_attempts = 0, locked_until = NULL, must_change_password = 0
     WHERE email = 'superadmin@bestlink.edu.ph';

CLI migrate options:
  php database/migrate.php
  php database/migrate.php --force
  php database/migrate.php --fresh   (DESTROYS DATA)
  php database/migrate.php --sms2-only   (skip legacy separate crad_db.sql)

Rebuild tooling (developers):
  php database/build-unified-sms2-sql.php
  php database/apply-table-prefixes.php

Docker startup option:
  Set SMS2_RUN_MIGRATIONS=1 to run database/migrate.php before Apache starts.

InfinityFree (free hosting)
---------------------------
InfinityFree has no SSH, so use the web deploy helper instead of CLI migrate.

1. Sign up at https://infinityfree.net and create a hosting account.
2. vPanel → MySQL Databases: create one database. Copy hostname, db name, user, password.
3. Copy config/local.infinityfree.example.php to config/local.php on the server.
   Fill in MySQL values. Use the SAME db name for DB_NAME and CRAD_DB_NAME.
4. Upload the project to htdocs via FTP (FileZilla). Put files in htdocs root if possible.
5. Replace .htaccess with .htaccess.infinityfree if the site shows HTTP 500
   (InfinityFree often blocks php_value in .htaccess).
6. Open: https://YOUR-SITE.infinityfreeapp.com/setup/deploy-db.php?token=YOUR_TOKEN
7. Open: https://YOUR-SITE.infinityfreeapp.com/setup/ and create the Super Admin.
8. Remove SMS2_DEPLOY_TOKEN from config/local.php after migration succeeds.

Alternative: import database/sms2_db.sql once via phpMyAdmin (unified dump).
