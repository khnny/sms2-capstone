<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/includes/research-services-clearance.php';

requireAuth();

$crad = rscDb();
header('Content-Type: application/json; charset=utf-8');
if (!$crad instanceof PDO) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}
rscEnsureSchema($crad);
rcpEnsureSchema($crad);
$role = getCurrentUserRoleKey();
$action = trim((string) ($_POST['action'] ?? $_GET['action'] ?? ''));

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null);

        if ($action === 'student_upload') {
            if ($role !== 'student') {
                throw new RuntimeException('Forbidden');
            }
            $group = chapterRegisteredStudentGroup($crad);
            if (!$group) {
                throw new InvalidArgumentException('No research group is registered yet.');
            }
            $file = is_array($_FILES['payment_file'] ?? null) ? $_FILES['payment_file'] : [];
            $stage = rcpNormalizeStage((string) ($_POST['research_stage'] ?? 'research_1'));
            $result = rcpStudentUpload(
                $crad,
                (int) $group['id'],
                $file,
                (string) ($_POST['or_number'] ?? ''),
                $stage
            );
            echo json_encode([
                'ok' => !empty($result['ok']),
                'error' => $result['error'] ?? null,
                'payment' => isset($result['payment']) ? rcpPublicRow($result['payment']) : null,
                'rows' => rcpStudentInbox($crad, (int) $group['id']),
            ], JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if ($action === 'student_update_or') {
            if ($role !== 'student') {
                throw new RuntimeException('Forbidden');
            }
            $group = chapterRegisteredStudentGroup($crad);
            if (!$group) {
                throw new InvalidArgumentException('No research group is registered yet.');
            }
            $stage = rcpNormalizeStage((string) ($_POST['research_stage'] ?? 'research_1'));
            $memberReferences = json_decode((string) ($_POST['member_or_numbers'] ?? '{}'), true);
            $savedReferences = rcpSaveMemberReferences(
                $crad,
                (int) $group['id'],
                is_array($memberReferences) ? $memberReferences : [],
                $stage
            );
            if (empty($savedReferences['ok'])) throw new InvalidArgumentException((string) ($savedReferences['error'] ?? 'Invalid reference numbers.'));
            $primaryReference = trim((string) ($_POST['or_number'] ?? '')) ?: (string) (reset($savedReferences['references']) ?: '');
            $result = rcpStudentUpdateOr(
                $crad,
                (int) $group['id'],
                $stage,
                $primaryReference
            );
            echo json_encode([
                'ok' => !empty($result['ok']),
                'error' => $result['error'] ?? null,
                'payment' => isset($result['payment']) ? rcpPublicRow($result['payment']) : null,
                'rows' => rcpStudentInbox($crad, (int) $group['id']),
            ], JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if ($action === 'admin_approve' || $action === 'admin_reject') {
            if (!rcpCanApprove()) {
                throw new RuntimeException('Forbidden');
            }
            $row = rcpFindById($crad, (int) ($_POST['id'] ?? 0));
            if (!$row) {
                throw new InvalidArgumentException('College payment not found.');
            }
            $result = $action === 'admin_approve'
                ? rcpAdminApprove($crad, $row, (string) ($_POST['or_number'] ?? ''), (string) ($_POST['remarks'] ?? ''))
                : rcpAdminReject($crad, $row);
            echo json_encode([
                'ok' => !empty($result['ok']),
                'error' => $result['error'] ?? null,
                'payment' => isset($result['payment']) ? rcpPublicRow($result['payment']) : null,
            ], JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        throw new InvalidArgumentException('Unknown action.');
    }

    if ($role === 'student') {
        $group = chapterRegisteredStudentGroup($crad);
        $stage = rcpNormalizeStage((string) ($_GET['stage'] ?? 'research_1'));
        $rows = $group ? rcpStudentInbox($crad, (int) $group['id']) : [];
        $row = null;
        foreach ($rows as $item) {
            if (($item['research_stage'] ?? '') === $stage) {
                $row = $item;
                break;
            }
        }
        echo json_encode([
            'ok' => true,
            'last_sync' => date('M j, Y g:i:s A'),
            'has_group' => (bool) $group,
            'stage' => $stage,
            'payment' => $row,
            'rows' => $rows,
        ]);
        exit;
    }

    if (!rcpCanApprove()) {
        throw new RuntimeException('Forbidden');
    }
    $rows = rcpListForAdmin($crad);
    echo json_encode([
        'ok' => true,
        'last_sync' => date('M j, Y g:i:s A'),
        'rows' => array_map(static function (array $row) use ($crad): array {
            $row = rcpEnsureOrFromImage($crad, $row);
            $row['members'] = rcpGroupPaymentMembers(
                $crad,
                (int) ($row['research_group_id'] ?? 0),
                rcpNormalizeStage((string) ($row['research_stage'] ?? 'research_1'))
            );
            $public = rcpPublicRow($row);
            $public['group_number'] = (string) ($row['group_number'] ?? '');
            $public['research_title'] = (string) ($row['research_title'] ?? '');
            $public['group_name'] = (string) ($row['group_name'] ?? '');
            return $public;
        }, $rows),
    ], JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
