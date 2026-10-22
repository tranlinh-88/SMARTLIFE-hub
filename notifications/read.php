<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Phương thức không được hỗ trợ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !verify_csrf($input['csrf_token'] ?? '')) {
    http_response_code(400);
    echo json_encode(['error' => 'Phiên làm việc không hợp lệ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];
if (!empty($input['all'])) {
    $stmt = $conn->prepare('UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0');
    $stmt->bind_param('i', $userId);
} else {
    $notificationId = (int)($input['id'] ?? 0);
    if ($notificationId < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Thông báo không hợp lệ.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $stmt = $conn->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?');
    $stmt->bind_param('ii', $notificationId, $userId);
}

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error' => 'Không thể cập nhật trạng thái thông báo.'], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
