═══════════════════════════════════════════════════════════════════
  CRAD MODULE — DATABASE SETUP GUIDE
  Bestlink College of the Philippines — SMS 2
═══════════════════════════════════════════════════════════════════

OVERVIEW
--------
Ang CRAD module ay may sariling database: crad_db
Ginagamit ito para sa research proposal submission at tracking.

PAANO MAG-INSTALL
------------------
1. I-start ang XAMPP (Apache + MySQL)
2. Buksan ang browser at pumunta sa:

   http://localhost/sms2_system/modules/crad/database/install.php

3. Makikita mo ang installation progress. Dapat makita mo ang:
   ✔ Connected to MySQL
   ✔ Database `crad_db` ready
   ✔ All schema tables created successfully
   ✔ Table `research_proposals` verified
   ✔ Table `proposal_members` verified
   ✔ Table `proposal_documents` verified
   ✔ Table `proposal_status_logs` verified

4. Tapos na! Pwede mo nang gamitin ang CRAD module.

ALTERNATE: CLI INSTALLATION
----------------------------
Kung gusto mo via command line:

   cd F:\xampp\htdocs\sms2_system
   php modules/crad/database/install.php

TABLES CREATED
--------------
1. research_proposals
   - Main table para sa proposals
   - ref_code: CRD-YYYY-NNNNN format
   - status: Submitted, In Progress, Panel Assigned, Approved, Returned
   - progress: Percentage (10-100)

2. proposal_members
   - Group members ng bawat proposal (max 5)

3. proposal_documents
   - Uploaded files per proposal
   - manuscript, approval, abstract, certificates, etc.

4. proposal_status_logs
   - Audit trail ng status changes

WORKFLOW
--------
1. Student fills out form sa:
   /modules/student-portal/pages/submit-documents.php

2. Pag nag-submit, data is saved to crad_db tapos redirect to:
   /modules/crad/pages/proposal-submission-tracking.php

3. CRAD officer makikita lahat ng submitted proposals with:
   - Reference codes
   - Status badges
   - Progress bars
   - Search and filter

TROUBLESHOOTING
---------------
Q: Hindi nag-create ang database?
A: Check if MySQL is running sa XAMPP Control Panel

Q: May SQL errors?
A: Tingnan ang error message. Usually permissions or existing tables.

Q: Blank page lang?
A: Check PHP error logs sa xampp/php/logs/

Q: Connection failed?
A: Edit modules/crad/config/config.php at i-check ang:
   - CRAD_DB_HOST (default: localhost)
   - CRAD_DB_USER (default: root)
   - CRAD_DB_PASS (default: blank)

═══════════════════════════════════════════════════════════════════
For questions, contact your system administrator.
═══════════════════════════════════════════════════════════════════
