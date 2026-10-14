<?php
/**
 * Assignment confirmation API (Coordinator / Adviser Accept, Decline, or Later).
 * Primary UX is the global modal; assignment-confirmation.php remains a fallback page.
 * Later stores a session flag so the modal stays dismissed while the bell keeps the request.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once ROOT_PATH . '/includes/authentication.php';
require_once ROOT_PATH . '/includes/security.php';
require_once ROOT_PATH . '/modules/crad/config/config.php';
require_once ROOT_PATH . '/modules/crad/includes/research-group-flow.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Authentication required.']);
    exit;
}

requireAuth();

$roleKey = getCurrentUserRoleKey();
if (!in_array($roleKey, ['adviser', 'research_coordinator'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Only Research Coordinator or Adviser may confirm assignments.']);
    exit;
}

$pdo = cradDb();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Database unavailable.']);
    exit;
}

cradRgFlowEnsureSchema($pdo);

$userId = (int) ($_SESSION['user_id'] ?? 0);
$email = (string) ($_SESSION['user_email'] ?? '');
$fullName = (string) ($_SESSION['user_name'] ?? $_SESSION['full_name'] ?? '');
$yourRoleLabel = $roleKey === 'adviser' ? 'Adviser' : 'Coordinator';

/**
 * Session keys for cycles where the user clicked Later (suppress modal until Accept/Decline).
 *
 * @return list<int>
 */
$getLaterCycleIds = static function (): array {
    $raw = (array) ($_SESSION['assignment_confirm_later'] ?? []);
    $ids = [];
    foreach ($raw as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    return array_values(array_unique($ids));
};

/**
 * @param list<int> $ids
 */
$setLaterCycleIds = static function (array $ids): void {
    $clean = [];
    foreach ($ids as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $clean[] = $id;
        }
    }
    $_SESSION['assignment_confirm_later'] = array_values(array_unique($clean));
};

/**
 * @param list<array<string,mixed>> $rows
 * @param list<int> $laterIds
 * @return list<array<string,mixed>>
 */
$filterOutLater = static function (array $rows, array $laterIds): array {
    if ($laterIds === []) {
        return $rows;
    }
    $laterLookup = array_fill_keys($laterIds, true);
    $out = [];
    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? $row['cycle_id'] ?? 0);
        if ($id > 0 && isset($laterLookup[$id])) {
            continue;
        }
        $out[] = $row;
    }
    return $out;
};

/**
 * @param list<array<string,mixed>> $rows
 * @return list<array<string,mixed>>
 */
$normalizePending = static function (array $rows, string $yourRoleLabel): array {
    $out = [];
    foreach ($rows as $row) {
        $overall = function_exists('cradRgFlowOverallStatusFromCycle')
            ? cradRgFlowOverallStatusFromCycle($row)
            : (string) ($row['status'] ?? '');
        $overallLabel = function_exists('cradRgFlowOverallStatusLabel')
            ? cradRgFlowOverallStatusLabel($overall !== '' ? $overall : (string) ($row['status'] ?? ''))
            : str_replace('_', ' ', (string) ($row['status'] ?? ''));

        $out[] = [
            'cycle_id' => (int) ($row['id'] ?? 0),
            'research_title' => (string) (($row['research_title'] ?? '') ?: 'Pending Title'),
            'group_name' => (string) (($row['group_name'] ?? '') ?: ($row['group_number'] ?? '') ?: ''),
            'leader_name' => (string) (($row['leader_name'] ?? '') ?: ($row['student_id'] ?? '') ?: ''),
            'coordinator_name' => (string) ($row['coordinator_name'] ?? ''),
            'adviser_name' => (string) ($row['adviser_name'] ?? ''),
            'coordinator_confirmed' => !empty($row['coordinator_confirmed_at']),
            'adviser_confirmed' => !empty($row['adviser_confirmed_at']),
            'status' => (string) ($row['status'] ?? ''),
            'overall_status' => $overall,
            'overall_label' => $overallLabel,
            'your_role' => $yourRoleLabel,
            'assigned_by_label' => 'Department Head',
        ];
    }
    return $out;
};

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pending = cradRgFlowPendingConfirmationsForUser($pdo, $roleKey, $userId, $email, $fullName);
    $pendingIds = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $pending);
    $laterIds = array_values(array_filter(
        $getLaterCycleIds(),
        static fn(int $id): bool => in_array($id, $pendingIds, true)
    ));
    $setLaterCycleIds($laterIds);
    // Modal queue excludes Later'd cycles; bell still shows them via notifications.php.
    $modalPending = $filterOutLater($pending, $laterIds);
    echo json_encode([
        'ok' => true,
        'role' => $roleKey,
        'your_role' => $yourRoleLabel,
        'count' => count($modalPending),
        'items' => $normalizePending($modalPending, $yourRoleLabel),
        'later_cycle_ids' => $laterIds,
        'csrf_token' => csrfToken(),
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$csrf = (string) ($_POST['csrf_token'] ?? '');
if (!csrfVerify($csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Security check failed. Please refresh and try again.']);
    exit;
}

$cycleId = (int) ($_POST['cycle_id'] ?? 0);
$action = strtolower(trim((string) ($_POST['action'] ?? '')));
$reason = trim((string) ($_POST['cancel_reason'] ?? ''));

if ($cycleId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Missing assignment cycle.']);
    exit;
}

// Ensure this cycle is still pending for the current user before acting.
$pending = cradRgFlowPendingConfirmationsForUser($pdo, $roleKey, $userId, $email, $fullName);
$allowedIds = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $pending);
if (!in_array($cycleId, $allowedIds, true)) {
    echo json_encode(['ok' => false, 'message' => 'No open confirmation for this assignment (already responded or not yours).']);
    exit;
}

if ($action === 'later' || $action === 'dismiss') {
    // Session-only: close modal for this cycle; keep Approve Assignment in navbar bell.
    $laterIds = $getLaterCycleIds();
    if (!in_array($cycleId, $laterIds, true)) {
        $laterIds[] = $cycleId;
    }
    $setLaterCycleIds($laterIds);
    $result = [
        'ok' => true,
        'message' => 'Saved for later. Open it anytime from Notifications.',
        'status' => '',
        'overall_label' => '',
    ];
} elseif ($action === 'confirm' || $action === 'accept') {
    $result = cradRgFlowConfirmRole($pdo, $cycleId, $roleKey, $userId);
    if (!empty($result['ok'])) {
        $setLaterCycleIds(array_values(array_filter(
            $getLaterCycleIds(),
            static fn(int $id): bool => $id !== $cycleId
        )));
    }
} elseif ($action === 'cancel' || $action === 'decline') {
    $result = cradRgFlowCancelCycle($pdo, $cycleId, $roleKey, $userId, $reason);
    if (!empty($result['ok'])) {
        $setLaterCycleIds(array_values(array_filter(
            $getLaterCycleIds(),
            static fn(int $id): bool => $id !== $cycleId
        )));
    }
} else {
    $result = ['ok' => false, 'message' => 'Unknown action.'];
}

if (!empty($result['ok']) && function_exists('logActivity') && !in_array($action, ['later', 'dismiss'], true)) {
    $logAction = ($action === 'confirm' || $action === 'accept') ? 'confirm' : 'cancel';
    logActivity('assignment_' . $logAction, 'Cycle #' . $cycleId . ' - ' . (string) ($result['message'] ?? ''));
}

$remaining = cradRgFlowPendingConfirmationsForUser($pdo, $roleKey, $userId, $email, $fullName);
$laterIds = $getLaterCycleIds();
$modalRemaining = $filterOutLater($remaining, $laterIds);

echo json_encode([
    'ok' => !empty($result['ok']),
    'message' => (string) ($result['message'] ?? 'Action failed.'),
    'status' => (string) ($result['status'] ?? ''),
    'overall_label' => (string) ($result['overall_label'] ?? ''),
    'remaining_count' => count($modalRemaining),
    'items' => $normalizePending($modalRemaining, $yourRoleLabel),
    'later_cycle_ids' => $laterIds,
    'csrf_token' => csrfToken(),
]);
