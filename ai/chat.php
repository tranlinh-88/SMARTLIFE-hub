<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/rules.php';
require_once __DIR__ . '/context.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
function ai_json_error(string $message, int $status): void
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') ai_json_error('Phương thức không được hỗ trợ.', 405);
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || !verify_csrf($input['csrf_token'] ?? '')) ai_json_error('Phiên làm việc hết hạn. Hãy tải lại trang.', 400);
$user_id = (int)$_SESSION['user_id'];
$conversation_id = (int)($input['conversation_id'] ?? 0);
$message = trim((string)($input['message'] ?? ''));
if ($conversation_id < 1 || $message === '' || mb_strlen($message, 'UTF-8') > 1000) ai_json_error('Câu hỏi cần có nội dung và tối đa 1.000 ký tự.', 400);

ai_ensure_tables($conn);
$stmt = $conn->prepare('SELECT id, title FROM ai_conversations WHERE id=? AND user_id=?');
$stmt->bind_param('ii', $conversation_id, $user_id);
$stmt->execute();
$conversation = $stmt->get_result()->fetch_assoc();
if (!$conversation) ai_json_error('Không tìm thấy cuộc trò chuyện.', 404);

$stmt = $conn->prepare('SELECT COUNT(*) message_count FROM ai_messages WHERE conversation_id=?');
$stmt->bind_param('i', $conversation_id);
$stmt->execute();
$isFirst = (int)$stmt->get_result()->fetch_assoc()['message_count'] === 0;
$context = ai_context_data($conn, $user_id);
$reply = ai_rule_reply($message, $context);
$legacyColumns = [];
$columnResult = $conn->query('SHOW COLUMNS FROM ai_messages');
if ($columnResult) while ($column = $columnResult->fetch_assoc()) $legacyColumns[$column['Field']] = true;
$hasLegacyMessageFields = isset($legacyColumns['sender'], $legacyColumns['message']);

$conn->begin_transaction();
try {
    $role = 'user';
    if ($hasLegacyMessageFields) {
        $legacySender = 'user';
        $stmt = $conn->prepare('INSERT INTO ai_messages (conversation_id, role, content, sender, message) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $conversation_id, $role, $message, $legacySender, $message);
    } else {
        $stmt = $conn->prepare('INSERT INTO ai_messages (conversation_id, role, content) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $conversation_id, $role, $message);
    }
    $stmt->execute();
    $role = 'assistant';
    if ($hasLegacyMessageFields) {
        $legacySender = 'ai';
        $stmt->bind_param('issss', $conversation_id, $role, $reply, $legacySender, $reply);
    } else {
        $stmt->bind_param('iss', $conversation_id, $role, $reply);
    }
    $stmt->execute();
    if ($isFirst) {
        $newTitle = mb_substr($message, 0, 55, 'UTF-8');
        $stmt = $conn->prepare('UPDATE ai_conversations SET title=? WHERE id=? AND user_id=?');
        $stmt->bind_param('sii', $newTitle, $conversation_id, $user_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare('UPDATE ai_conversations SET updated_at=CURRENT_TIMESTAMP WHERE id=? AND user_id=?');
        $stmt->bind_param('ii', $conversation_id, $user_id);
        $stmt->execute();
    }
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    ai_json_error('Chưa lưu được tin nhắn. Vui lòng thử lại.', 500);
}
echo json_encode(['reply' => $reply, 'title' => $isFirst ? $newTitle : $conversation['title']], JSON_UNESCAPED_UNICODE);
