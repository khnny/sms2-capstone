<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/includes/research-services-clearance.php';

requireAuth();

$crad = rscDb();
if (!$crad instanceof PDO) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    exit;
}
rscEnsureSchema($crad);
$role = getCurrentUserRoleKey();
$action = trim((string) ($_POST['action'] ?? $_GET['action'] ?? ''));

if ($action === 'download_image') {
    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    $row = rscFindById($crad, $id);
    if (!$row || !rscCanDownloadFormImage($crad, $row)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Forbidden']);
        exit;
    }
    rscSendFormPngDownload($crad, $row);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCsrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null);
        $id = (int) ($_POST['id'] ?? 0);
        $row = rscFindById($crad, $id);
        if (!$row) {
            throw new InvalidArgumentException('Clearance not found.');
        }

        if ($action === 'send_to_adviser') {
            if ($role !== 'student' || !rscStudentCanAccess($crad, $row)) {
                throw new RuntimeException('Forbidden');
            }
            $result = rscSendToAdviser($crad, $row);
            echo json_encode(['ok' => !empty($result['ok']), 'error' => $result['error'] ?? null, 'clearance' => isset($result['clearance']) ? rscPublicRow($result['clearance']) : null]);
            exit;
        }

        if ($action === 'adviser_sign') {
            if ($role !== 'adviser' || !rscAdviserCanAccess($row)) {
                throw new RuntimeException('Forbidden');
            }
            $result = rscAdviserSign($crad, $row, (string) ($_POST['signature'] ?? ''), getCurrentUserName());
            echo json_encode(['ok' => !empty($result['ok']), 'error' => $result['error'] ?? null, 'clearance' => isset($result['clearance']) ? rscPublicRow($result['clearance']) : null]);
            exit;
        }

        if ($action === 'crad_receive') {
            if (!rscCanManageAsCrad()) {
                throw new RuntimeException('Forbidden');
            }
            $file = is_array($_FILES['clearance_file'] ?? null) ? $_FILES['clearance_file'] : [];
            $result = rscCradReceive($crad, $row, $file);
            $fresh = $result['clearance'] ?? null;
            echo json_encode([
                'ok' => !empty($result['ok']),
                'error' => $result['error'] ?? null,
                'clearance' => $fresh ? [
                    'id' => (int) ($fresh['id'] ?? 0),
                    'status' => (string) ($fresh['status'] ?? ''),
                    'status_label' => rscStatusLabel((string) ($fresh['status'] ?? '')),
                    'has_upload' => trim((string) ($fresh['uploaded_file'] ?? '')) !== '',
                    'form_verified' => (int) ($fresh['form_verified'] ?? 0) === 1,
                    'uploaded_original' => (string) ($fresh['uploaded_original'] ?? ''),
                    'uploaded_url' => rscUploadPublicUrl($fresh),
                    'uploaded_at' => (string) ($fresh['uploaded_at'] ?? ''),
                    'has_adviser_signature' => trim((string) ($fresh['adviser_signature'] ?? '')) !== '',
                    'has_mis_signature' => true,
                    'has_aa_signature' => true,
                    'has_crad_signature' => trim((string) ($fresh['crad_signature'] ?? '')) !== '',
                    'mis_verified' => true,
                    'aa_verified' => true,
                    'can_crad_sign' => rscCanCradSign($fresh),
                    'form_html' => '',
                ] : null,
            ], JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if ($action === 'crad_verify') {
            if (!rscCanManageAsCrad()) {
                throw new RuntimeException('Forbidden');
            }
            $result = rscCradVerifyMarks(
                $crad,
                $row,
                (string) ($_POST['mis_verified'] ?? '') === '1',
                (string) ($_POST['aa_verified'] ?? '') === '1'
            );
            echo json_encode(['ok' => !empty($result['ok']), 'error' => $result['error'] ?? null, 'clearance' => isset($result['clearance']) ? rscPublicRow($result['clearance']) : null]);
            exit;
        }

        if ($action === 'crad_sign') {
            if (!rscCanManageAsCrad()) {
                throw new RuntimeException('Forbidden');
            }
            $result = rscCradSign($crad, $row, (string) ($_POST['signature'] ?? ''), getCurrentUserName());
            echo json_encode(['ok' => !empty($result['ok']), 'error' => $result['error'] ?? null, 'clearance' => isset($result['clearance']) ? rscPublicRow($result['clearance']) : null]);
            exit;
        }

        throw new InvalidArgumentException('Unknown action.');
    }

    $payload = [
        'ok' => true,
        'server_time' => date('c'),
        'last_sync' => date('M j, Y g:i:s A'),
        'ready' => false,
        'clearance' => null,
        'rows' => [],
    ];

    if ($role === 'student') {
        $group = chapterRegisteredStudentGroup($crad);
        $stage = rscNormalizeStage((string) ($_GET['stage'] ?? ''));
        $id = (int) ($_GET['id'] ?? 0);
        $inbox = $group ? rscStudentInbox($crad, (int) $group['id']) : [];
        $payload['rows'] = array_map(static function (array $row): array {
            $lite = $row;
            $lite['form_html'] = '';
            return $lite;
        }, $inbox);
        $current = null;
        if ($id > 0) {
            $found = rscFindById($crad, $id);
            if ($found && rscStudentCanAccess($crad, $found)) {
                $current = rscRefreshExisting($crad, $found);
            }
        } elseif ($stage !== '') {
            foreach ($inbox as $item) {
                if (($item['research_stage'] ?? '') === $stage && !empty($item['id'])) {
                    $current = rscFindById($crad, (int) $item['id']);
                    break;
                }
            }
        }
        $payload['ready'] = $inbox !== [];
        $payload['clearance'] = $current ? rscPublicRow($current) : null;
        $payload['payment_approved'] = $current
            ? rscPaymentUnlocksClearance($crad, (int) ($current['research_group_id'] ?? 0), $current)
            : false;
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    if ($role === 'adviser') {
        $rows = rscListForAdviser($crad);
        $id = (int) ($_GET['id'] ?? 0);
        $current = $id > 0 ? rscRefreshExisting($crad, rscFindById($crad, $id)) : null;
        if ($current && !rscAdviserCanAccess($current)) {
            $current = null;
        }
        $payload['ready'] = $rows !== [];
        $payload['clearance'] = $current ? rscPublicRow($current) : null;
        $payload['rows'] = array_map(static function (array $row): array {
            $public = rscPublicRow($row);
            $public['form_html'] = '';
            return $public;
        }, $rows);
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    if (rscCanManageAsCrad()) {
        $rows = rscListForCrad($crad);
        $id = (int) ($_GET['id'] ?? 0);
        $current = $id > 0 ? rscRefreshExisting($crad, rscFindById($crad, $id)) : null;
        if (!$current && $rows) {
            $current = $rows[0];
        }
        $payload['ready'] = $rows !== [];
        $payload['clearance'] = $current ? rscPublicRow($current) : null;
        $payload['rows'] = array_map(static function (array $row): array {
            $public = rscPublicRow($row);
            $public['form_html'] = '';
            return $public;
        }, $rows);
        echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
