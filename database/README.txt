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

HostForge:
  - Choose MySQL in the database step. HostForge's MySQL option is MariaDB,
    which is compatible with this project.
  - Do not choose PostgreSQL; these schema dumps are MySQL/MariaDB SQL.
  - HostForge-injected DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME,
    DB_PASSWORD, DB_CONNECTION, and DB_CHARSET are supported automatically.
  - After the first successful deployment, open the web terminal and run:

     php database/migrate.php

It creates/uses the configured database name from environment variables, then
records the applied dump hashes in schema_migrations. If CRAD_DB_NAME is not
set, the CRAD tables are installed into the same HostForge database as SMS2.

Useful options:
  - --fresh  Drop and rebuild both databases. DESTROYS DATA.
  - --force  Apply SQL even when tables already exist.

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
