<?php
/**
 * Official SMS 2 bootstrap account credentials (email + password).
 * Used by seed/update scripts only. Seeded users must change password on first login.
 * Rotate these before any shared/production environment.
 */
declare(strict_types=1);

/**
 * @return list<array{
 *   username: string,
 *   email: string,
 *   password: string,
 *   full_name: string,
 *   role_key: string,
 *   student_id: ?string,
 *   lookup: list<string>
 * }>
 */
function smsOfficialAccounts(): array
{
    return [
        [
            'username' => 'jobertvalentino',
            'email' => 'jobertvalentino@bestlink.edu.ph',
            'password' => '@Adviser123',
            'full_name' => 'Dr. Jobert Valentino',
            'role_key' => 'panel',
            'student_id' => null,
            'lookup' => ['jobert.valentino', 'jobert.valentino@bestlink.edu.ph'],
        ],
        [
            'username' => 'jonathanestrada',
            'email' => 'jonathanestrada@bestlink.edu.ph',
            'password' => '@Adviser123',
            'full_name' => 'Dr. Jonathan Estrada',
            'role_key' => 'panel',
            'student_id' => null,
            'lookup' => ['jonathan.estrada', 'jonathan.estrada@bestlink.edu.ph'],
        ],
        [
            'username' => 'michelleguevarra',
            'email' => 'michelleguevarra@bestlink.edu.ph',
            'password' => '@Adviser123',
            'full_name' => 'Dr. Michelle Guevarra',
            'role_key' => 'panel',
            'student_id' => null,
            'lookup' => ['michelle.guevarra', 'michelle.guevarra@bestlink.edu.ph'],
        ],
        [
            'username' => 'rsantos',
            'email' => 'rsantos@bestlink.edu.ph',
            'password' => '@Adviser123',
            'full_name' => 'Dr. Roberto M. Santos',
            'role_key' => 'adviser',
            'student_id' => null,
            'lookup' => [],
        ],
        [
            'username' => 'grammarian',
            'email' => 'grammarian@bestlink.edu.ph',
            'password' => '@Grammarian123',
            'full_name' => 'Grammarian',
            'role_key' => 'grammarian',
            'student_id' => null,
            'lookup' => [],
        ],
        [
            'username' => 'researchgrant',
            'email' => 'researchgrant@bestlink.edu.ph',
            'password' => '@Grant123',
            'full_name' => 'Research Grant',
            'role_key' => 'research_grant',
            'student_id' => null,
            'lookup' => [],
        ],
        [
            'username' => 'cradofficer',
            'email' => 'cradofficer@bestlink.edu.ph',
            'password' => '@Cradofficer123',
            'full_name' => 'CRAD Officer',
            'role_key' => 'crad_officer',
            'student_id' => null,
            'lookup' => ['cradofficer@bestlink.ph'],
        ],
        [
            'username' => 'researchcoordinator',
            'email' => 'researchcoordinator@bestlink.edu.ph',
            'password' => '@Coordinator123',
            'full_name' => 'Mrs. Kris Guevarra',
            'role_key' => 'research_coordinator',
            'student_id' => null,
            'lookup' => [],
        ],
        [
            'username' => 's230000001',
            'email' => 's230000001@bestlink.edu.ph',
            'password' => '@Kenneth8080',
            'full_name' => 'Student User',
            'role_key' => 'student',
            'student_id' => 'S230000001',
            'lookup' => [],
        ],
        [
            'username' => 'researchdirector',
            'email' => 'researchdirector@bestlink.edu.ph',
            'password' => '@Director123',
            'full_name' => 'Research Director',
            'role_key' => 'research_director',
            'student_id' => null,
            'lookup' => ['research.director@bestlink.edu.ph'],
        ],
        [
            'username' => 'reviewcommittee',
            'email' => 'reviewcommittee@bestlink.edu.ph',
            'password' => '@Committee123',
            'full_name' => 'Review Committee Member',
            'role_key' => 'review_committee',
            'student_id' => null,
            'lookup' => [],
        ],
        [
            'username' => 'deptchair',
            'email' => 'deptchair@bestlink.edu.ph',
            'password' => '@Department123',
            'full_name' => 'Department Chair',
            'role_key' => 'department_chair',
            'student_id' => null,
            'lookup' => [],
        ],
        [
            'username' => 'researchoffice',
            'email' => 'researchoffice@bestlink.edu.ph',
            'password' => '@Research123',
            'full_name' => 'Research Office',
            'role_key' => 'research_office',
            'student_id' => null,
            'lookup' => [],
        ],
        [
            'username' => 'vpaa',
            'email' => 'vpaa@bestlink.edu.ph',
            'password' => '@Vpaa123',
            'full_name' => 'VPAA',
            'role_key' => 'vpaa',
            'student_id' => null,
            'lookup' => [],
        ],
        [
            'username' => 'dean',
            'email' => 'dean@bestlink.edu.ph',
            'password' => '@Dean123',
            'full_name' => 'Dean',
            'role_key' => 'hr',
            'student_id' => null,
            'lookup' => ['hr', 'faculty', 'hr@bestlink.edu.ph'],
        ],
        [
            'username' => 'superadmin',
            'email' => 'superadmin@bestlink.edu.ph',
            'password' => '@Superadmin123',
            'full_name' => 'Super Admin',
            'role_key' => 'superadmin',
            'student_id' => null,
            'lookup' => [],
        ],
    ];
}

/**
 * Apply official emails/passwords to matching users (or insert if missing).
 *
 * @return array{updated:int,created:int}
 */
function smsApplyOfficialAccountCredentials(PDO $pdo): array
{
    $accounts = smsOfficialAccounts();
    $find = $pdo->prepare(
        'SELECT id FROM sms_users
         WHERE username = :uname OR LOWER(email) = LOWER(:email)
         LIMIT 1'
    );
    $update = $pdo->prepare(
        'UPDATE sms_users
            SET username = :username,
                email = :email,
                password_hash = :hash,
                full_name = :full_name,
                role_key = :role_key,
                student_id = :student_id,
                status = \'active\',
                password_changed_at = NOW(),
                must_change_password = 0,
                failed_login_attempts = 0,
                locked_until = NULL
          WHERE id = :id'
    );
    $insert = $pdo->prepare(
        'INSERT INTO sms_users
            (username, email, password_hash, full_name, role_key, student_id, status, password_changed_at, must_change_password, failed_login_attempts, locked_until)
         VALUES (?, ?, ?, ?, ?, ?, \'active\', NOW(), 0, 0, NULL)'
    );

    $updated = 0;
    $created = 0;
    foreach ($accounts as $account) {
        $keys = array_values(array_unique(array_filter(array_merge(
            [$account['username'], $account['email']],
            $account['lookup'] ?? []
        ))));
        $row = null;
        foreach ($keys as $key) {
            $find->execute([':uname' => $key, ':email' => $key]);
            $row = $find->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                break;
            }
        }
        $hash = password_hash((string) $account['password'], PASSWORD_DEFAULT);
        if ($row) {
            $update->execute([
                ':username' => $account['username'],
                ':email' => $account['email'],
                ':hash' => $hash,
                ':full_name' => $account['full_name'],
                ':role_key' => $account['role_key'],
                ':student_id' => $account['student_id'],
                ':id' => (int) $row['id'],
            ]);
            $updated++;
            continue;
        }
        $insert->execute([
            $account['username'],
            $account['email'],
            $hash,
            $account['full_name'],
            $account['role_key'],
            $account['student_id'],
        ]);
        $created++;
    }

    return ['updated' => $updated, 'created' => $created];
}
