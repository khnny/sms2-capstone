<?php
/**
 * Official SMS 2 account credentials (email + password).
 * Used by seed/update scripts — keep in sync with stakeholder handoff list.
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
            'username' => 'cradofficer',
            'email' => 'cradofficer@bestlink.ph',
            'password' => '@Cradofficer123',
            'full_name' => 'CRAD Officer',
            'role_key' => 'crad_officer',
            'student_id' => null,
            'lookup' => ['cradofficer@bestlink.edu.ph'],
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
            'username' => 'reviewcommittee',
            'email' => 'reviewcommittee@bestlink.edu.ph',
            'password' => '@Committee123',
            'full_name' => 'Review Committee Member',
            'role_key' => 'review_committee',
            'student_id' => null,
            'lookup' => [],
        ],
        [
            'username' => 'depthead',
            'email' => 'depthead@bestlink.edu.ph',
            'password' => '@Depthead123',
            'full_name' => 'Department Head',
            'role_key' => 'department_head',
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
