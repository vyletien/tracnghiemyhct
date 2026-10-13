<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$attemptId = filter_var($payload['attempt_id'] ?? null, FILTER_VALIDATE_INT);
$eventType = $payload['event_type'] ?? '';
$eventDetail = trim((string)($payload['event_detail'] ?? ''));
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($payload['csrf_token'] ?? '');
$allowedEvents = ['page_open', 'tab_hidden', 'tab_visible', 'page_unload', 'copy', 'cut', 'paste', 'context_menu', 'devtools_shortcut'];

if (!$attemptId || !in_array($eventType, $allowedEvents, true)) {
    http_response_code(422);
    echo json_encode(['success' => false]);
    exit;
}

verify_csrf_token($csrf);

$stmt = $pdo->prepare('SELECT id FROM exam_attempts WHERE id = ? AND user_id = ? AND is_submitted = 0 LIMIT 1');
$stmt->execute([$attemptId, $_SESSION['user_id']]);
if (!$stmt->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO exam_cheat_events (attempt_id, event_type, event_detail) VALUES (?, ?, ?)');
$stmt->execute([$attemptId, $eventType, mb_substr($eventDetail, 0, 255)]);
echo json_encode(['success' => true]);
