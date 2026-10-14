<?php
/**
 * Per-student My Profile records.
 */

declare(strict_types=1);

function studentPortalEnsureProfileSchema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sms2_student_profiles` (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED NOT NULL,
            student_id VARCHAR(40) NOT NULL,
            program VARCHAR(200) NOT NULL DEFAULT 'Bachelor of Science in Information Technology',
            year_level VARCHAR(40) NOT NULL DEFAULT '4th Year',
            section VARCHAR(40) NOT NULL DEFAULT 'BSIT 4A',
            semester VARCHAR(40) NOT NULL DEFAULT '1st Semester',
            school_year VARCHAR(20) NOT NULL DEFAULT '2026-2027',
            enrollment_status VARCHAR(40) NOT NULL DEFAULT 'Enrolled',
            standing VARCHAR(40) NOT NULL DEFAULT 'Good Standing',
            mobile VARCHAR(40) DEFAULT NULL,
            address VARCHAR(255) DEFAULT NULL,
            guardian VARCHAR(150) DEFAULT NULL,
            guardian_contact VARCHAR(40) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_sp_user (user_id),
            UNIQUE KEY uq_sp_student_id (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * @return array<string,string>
 */
function studentPortalKnownProfileSeed(string $studentId): array
{
    $studentId = strtoupper(trim($studentId));

    if ($studentId === 'S230000001') {
        return [
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '4th Year',
            'section' => 'BSIT 4B',
            'semester' => '1st Semester',
            'school_year' => '2026-2027',
            'enrollment_status' => 'Enrolled',
            'standing' => 'Good Standing',
            'mobile' => '0917 000 0011',
            'address' => 'Fairview, Quezon City',
            'guardian' => 'Juan Dela Cruz',
            'guardian_contact' => '0918 000 0012',
        ];
    }

    if ($studentId === 'S230106713') {
        return [
            'program' => 'Bachelor of Science in Information Technology',
            'year_level' => '4th Year',
            'section' => 'BSIT 4A',
            'semester' => '1st Semester',
            'school_year' => '2026-2027',
            'enrollment_status' => 'Enrolled',
            'standing' => 'Good Standing',
            'mobile' => '0917 000 0001',
            'address' => 'Novaliches, Quezon City',
            'guardian' => 'Maria Dela Cruz',
            'guardian_contact' => '0918 000 0002',
        ];
    }

    return [
        'program' => 'Bachelor of Science in Information Technology',
        'year_level' => '4th Year',
        'section' => 'BSIT 4A',
        'semester' => '1st Semester',
        'school_year' => '2026-2027',
        'enrollment_status' => 'Enrolled',
        'standing' => 'Good Standing',
        'mobile' => '',
        'address' => '',
        'guardian' => '',
        'guardian_contact' => '',
    ];
}

function studentPortalUpsertProfile(PDO $pdo, int $userId, string $studentId, array $fields = []): void
{
    if ($userId <= 0 || trim($studentId) === '') {
        return;
    }

    studentPortalEnsureProfileSchema($pdo);
    $studentId = strtoupper(trim($studentId));
    $seed = array_merge(studentPortalKnownProfileSeed($studentId), $fields);

    $pdo->prepare("
        INSERT INTO `sms2_student_profiles`
            (user_id, student_id, program, year_level, section, semester, school_year,
             enrollment_status, standing, mobile, address, guardian, guardian_contact)
        VALUES
            (:user_id, :student_id, :program, :year_level, :section, :semester, :school_year,
             :enrollment_status, :standing, :mobile, :address, :guardian, :guardian_contact)
        ON DUPLICATE KEY UPDATE
            student_id = VALUES(student_id),
            program = IF(VALUES(program) <> '', VALUES(program), program),
            year_level = IF(VALUES(year_level) <> '', VALUES(year_level), year_level),
            section = IF(VALUES(section) <> '', VALUES(section), section),
            semester = IF(VALUES(semester) <> '', VALUES(semester), semester),
            school_year = IF(VALUES(school_year) <> '', VALUES(school_year), school_year),
            enrollment_status = IF(VALUES(enrollment_status) <> '', VALUES(enrollment_status), enrollment_status),
            standing = IF(VALUES(standing) <> '', VALUES(standing), standing),
            mobile = IF(VALUES(mobile) <> '', VALUES(mobile), mobile),
            address = IF(VALUES(address) <> '', VALUES(address), address),
            guardian = IF(VALUES(guardian) <> '', VALUES(guardian), guardian),
            guardian_contact = IF(VALUES(guardian_contact) <> '', VALUES(guardian_contact), guardian_contact)
    ")->execute([
        ':user_id' => $userId,
        ':student_id' => $studentId,
        ':program' => (string) ($seed['program'] ?? ''),
        ':year_level' => (string) ($seed['year_level'] ?? ''),
        ':section' => (string) ($seed['section'] ?? ''),
        ':semester' => (string) ($seed['semester'] ?? ''),
        ':school_year' => (string) ($seed['school_year'] ?? ''),
        ':enrollment_status' => (string) ($seed['enrollment_status'] ?? 'Enrolled'),
        ':standing' => (string) ($seed['standing'] ?? 'Good Standing'),
        ':mobile' => (string) ($seed['mobile'] ?? ''),
        ':address' => (string) ($seed['address'] ?? ''),
        ':guardian' => (string) ($seed['guardian'] ?? ''),
        ':guardian_contact' => (string) ($seed['guardian_contact'] ?? ''),
    ]);
}

function studentPortalEnsureProfileForUser(int $userId, string $studentId = '', string $roleKey = 'student'): void
{
    if ($userId <= 0 || $roleKey !== 'student') {
        return;
    }

    $pdo = db();
    if (!$pdo) {
        return;
    }

    try {
        if ($studentId === '') {
            $stmt = $pdo->prepare('SELECT student_id FROM `sms2_users` WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $studentId = strtoupper(trim((string) ($stmt->fetchColumn() ?: '')));
        }
        if ($studentId === '') {
            return;
        }
        $exists = $pdo->prepare('SELECT id FROM `sms2_student_profiles` WHERE user_id = :uid OR student_id = :sid LIMIT 1');
        $exists->execute([':uid' => $userId, ':sid' => $studentId]);
        if ($exists->fetch()) {
            return;
        }
        studentPortalUpsertProfile($pdo, $userId, $studentId);
    } catch (Throwable $e) {
        error_log('Student profile ensure failed: ' . $e->getMessage());
    }
}

/**
 * @return array<string,string>
 */
function studentPortalLoadProfile(?PDO $pdo, int $userId, string $studentId, string $name, string $email): array
{
    $studentId = strtoupper(trim($studentId));
    $row = null;

    if ($pdo instanceof PDO && ($userId > 0 || $studentId !== '')) {
        try {
            studentPortalEnsureProfileSchema($pdo);

            if ($userId > 0 && $studentId === '') {
                $userStmt = $pdo->prepare('SELECT student_id, full_name, email FROM `sms2_users` WHERE id = :id LIMIT 1');
                $userStmt->execute([':id' => $userId]);
                $user = $userStmt->fetch() ?: [];
                $studentId = strtoupper(trim((string) ($user['student_id'] ?? '')));
                if ($name === '' || $name === 'User' || $name === 'Student') {
                    $name = trim((string) ($user['full_name'] ?? $name));
                }
                if ($email === '') {
                    $email = trim((string) ($user['email'] ?? ''));
                }
            }

            if ($userId > 0 && $studentId !== '') {
                $check = $pdo->prepare('SELECT id FROM `sms2_student_profiles` WHERE user_id = :uid OR student_id = :sid LIMIT 1');
                $check->execute([':uid' => $userId, ':sid' => $studentId]);
                if (!$check->fetch()) {
                    studentPortalUpsertProfile($pdo, $userId, $studentId);
                }
            }

            if ($userId > 0) {
                $stmt = $pdo->prepare('SELECT * FROM `sms2_student_profiles` WHERE user_id = :uid LIMIT 1');
                $stmt->execute([':uid' => $userId]);
                $row = $stmt->fetch() ?: null;
            }
            if (!$row && $studentId !== '') {
                $stmt = $pdo->prepare('SELECT * FROM `sms2_student_profiles` WHERE student_id = :sid LIMIT 1');
                $stmt->execute([':sid' => $studentId]);
                $row = $stmt->fetch() ?: null;
            }
        } catch (Throwable $e) {
            error_log('Student profile load failed: ' . $e->getMessage());
        }
    }

    $seed = studentPortalKnownProfileSeed($studentId);

    return [
        'name' => $name !== '' ? $name : 'Student',
        'student_id' => $studentId !== '' ? $studentId : (string) ($row['student_id'] ?? ''),
        'program' => trim((string) ($row['program'] ?? '')) ?: $seed['program'],
        'year_level' => trim((string) ($row['year_level'] ?? '')) ?: $seed['year_level'],
        'section' => trim((string) ($row['section'] ?? '')) ?: $seed['section'],
        'semester' => trim((string) ($row['semester'] ?? '')) ?: $seed['semester'],
        'school_year' => trim((string) ($row['school_year'] ?? '')) ?: $seed['school_year'],
        'status' => trim((string) ($row['enrollment_status'] ?? '')) ?: $seed['enrollment_status'],
        'standing' => trim((string) ($row['standing'] ?? '')) ?: $seed['standing'],
        'email' => $email !== '' ? $email : 'student@bcp.edu.ph',
        'mobile' => trim((string) ($row['mobile'] ?? '')) ?: $seed['mobile'],
        'address' => trim((string) ($row['address'] ?? '')) ?: $seed['address'],
        'guardian' => trim((string) ($row['guardian'] ?? '')) ?: $seed['guardian'],
        'guardian_contact' => trim((string) ($row['guardian_contact'] ?? '')) ?: $seed['guardian_contact'],
    ];
}
