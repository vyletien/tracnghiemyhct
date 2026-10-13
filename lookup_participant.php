<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Phương thức không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$now = time();
$attempts = array_values(array_filter($_SESSION['phone_lookup_attempts'] ?? [], static function ($timestamp) use ($now) {
    return is_int($timestamp) && $timestamp > $now - 60;
}));
if (count($attempts) >= 30) {
    http_response_code(429);
    echo json_encode(['error' => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau ít phút.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$attempts[] = $now;
$_SESSION['phone_lookup_attempts'] = $attempts;

$phone = normalizePhone($_POST['phone'] ?? '');
if (!isValidPhone($phone)) {
    echo json_encode(['found' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare("SELECT u.fullname, u.unit_id, un.name AS unit_name
    FROM users u
    LEFT JOIN units un ON un.id = u.unit_id
    WHERE u.phone = ? AND u.role = 'student'
    LIMIT 1");
$stmt->execute([$phone]);
$participant = $stmt->fetch();

if (!$participant) {
    echo json_encode(['found' => false], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'found' => true,
    'fullname' => $participant['fullname'],
    'unit_id' => $participant['unit_id'] ? (int)$participant['unit_id'] : null,
    'unit_name' => $participant['unit_name'],
], JSON_UNESCAPED_UNICODE);
