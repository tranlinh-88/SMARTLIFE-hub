<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || !verify_csrf($input['csrf_token'] ?? '')) {
        http_response_code(400);
        echo json_encode(['error' => 'Phiên làm việc không hợp lệ. Hãy tải lại trang.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $messages = $input['messages'] ?? [];
    if (!is_array($messages) || count($messages) < 1 || count($messages) > 10) {
        http_response_code(400);
        echo json_encode(['error' => 'Tin nhắn không hợp lệ. Tối đa 10 lượt gần nhất được gửi.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cleanMessages = [];
    foreach ($messages as $message) {
        if (!is_array($message) || !in_array($message['role'] ?? '', ['user', 'assistant'], true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Định dạng tin nhắn không hợp lệ.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $content = trim((string)($message['content'] ?? ''));
        if ($content === '' || mb_strlen($content, 'UTF-8') > 2000) {
            http_response_code(400);
            echo json_encode(['error' => 'Mỗi tin nhắn cần có nội dung và không dài quá 2.000 ký tự.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $cleanMessages[] = ['role' => $message['role'], 'content' => $content];
    }

    $body = json_encode([
        'model' => 'qwen3:1.7b',
        'messages' => array_merge([
            ['role' => 'system', 'content' => 'Bạn là trợ lý AI thử nghiệm chạy cục bộ. Trả lời bằng tiếng Việt, rõ ràng và ngắn gọn.'],
        ], $cleanMessages),
        'stream' => false,
        'think' => false,
        'options' => ['num_ctx' => 2048, 'num_predict' => 400],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $body,
            'timeout' => 120,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents('http://127.0.0.1:11434/api/chat', false, $context);
    if ($response === false) {
        http_response_code(503);
        echo json_encode(['error' => 'Không kết nối được Ollama tại 127.0.0.1:11434. Hãy cài và mở Ollama, sau đó tải mô hình qwen3:1.7b.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = json_decode($response, true);
    if (!is_array($result) || isset($result['error'])) {
        http_response_code(502);
        echo json_encode(['error' => $result['error'] ?? 'Ollama trả về dữ liệu không hợp lệ. Kiểm tra mô hình qwen3:1.7b đã được tải chưa.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['reply' => $result['message']['content'] ?? 'Mô hình chưa trả về câu trả lời.'], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thử AI cục bộ | SmartLife</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f6fb;color:#172033;font:16px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}.wrap{max-width:820px;margin:40px auto;padding:0 18px}.card{background:#fff;border:1px solid #e3e8f0;border-radius:18px;box-shadow:0 12px 36px #1c355210;overflow:hidden}.head{padding:24px 26px;border-bottom:1px solid #edf0f5}.head h1{font-size:24px;margin:0 0 6px}.head p{margin:0;color:#61708a}.notice{margin:18px 22px;padding:12px 14px;background:#eff7ff;border-radius:10px;color:#27547f;font-size:14px}.chat{height:400px;overflow:auto;padding:6px 22px 20px}.msg{white-space:pre-wrap;max-width:85%;padding:11px 14px;border-radius:14px;margin:12px 0}.user{margin-left:auto;background:#335eea;color:white;border-bottom-right-radius:4px}.assistant{background:#f0f3f8;color:#202a3c;border-bottom-left-radius:4px}.error{background:#fff0ef;color:#962f28}.form{padding:16px 20px 20px;border-top:1px solid #edf0f5;display:flex;gap:10px}.form textarea{resize:vertical;min-height:48px;max-height:130px;flex:1;border:1px solid #d7deea;border-radius:12px;padding:12px;font:inherit}.form button,.actions button{border:0;border-radius:10px;padding:0 18px;background:#335eea;color:#fff;font-weight:650;cursor:pointer}.form button:disabled{opacity:.6;cursor:wait}.actions{padding:0 22px 18px;display:flex;justify-content:space-between;align-items:center;color:#657188;font-size:13px}.actions button{background:#e9edf5;color:#34415a;padding:8px 12px}.back{display:inline-block;margin:0 0 14px;color:#335eea;text-decoration:none}
    </style>
</head>
<body>
<main class="wrap">
    <a class="back" href="assistant.php">← Quay lại AI Assistant</a>
    <section class="card">
        <header class="head"><h1>Thử AI chạy trên máy</h1><p>Ollama · Qwen3 1.7B · Không gửi dữ liệu SmartLife lên dịch vụ bên ngoài</p></header>
        <div class="notice">Bản thử nghiệm này không lưu hội thoại vào cơ sở dữ liệu và không đọc dữ liệu học tập, task, tài chính, thói quen hay mục tiêu. Tin nhắn chỉ nằm trong trang hiện tại.</div>
        <div class="chat" id="chat" aria-live="polite"><div class="msg assistant">Xin chào! Hãy gửi một câu hỏi để thử mô hình cục bộ. Nếu Ollama chưa chạy, trang sẽ hướng dẫn bạn.</div></div>
        <div class="actions"><span id="status">Lịch sử tạm thời: tối đa 10 tin nhắn gần nhất</span><button type="button" id="clear">Xóa đoạn chat</button></div>
        <form class="form" id="form"><textarea id="prompt" maxlength="2000" placeholder="Nhập câu hỏi..." required></textarea><button id="send" type="submit">Gửi</button></form>
    </section>
</main>
<script>
const token = <?= json_encode(csrf_token()) ?>;
const chat = document.getElementById('chat');
const form = document.getElementById('form');
const promptInput = document.getElementById('prompt');
const sendButton = document.getElementById('send');
let history = [];
function addMessage(text, role, extra = '') {
    const item = document.createElement('div');
    item.className = `msg ${role} ${extra}`;
    item.textContent = text;
    chat.appendChild(item);
    chat.scrollTop = chat.scrollHeight;
    return item;
}
document.getElementById('clear').addEventListener('click', () => {
    history = [];
    chat.replaceChildren();
    addMessage('Đoạn chat đã được xóa. Bạn có thể bắt đầu câu hỏi mới.', 'assistant');
});
form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const text = promptInput.value.trim();
    if (!text || sendButton.disabled) return;
    addMessage(text, 'user');
    history.push({role: 'user', content: text});
    history = history.slice(-10);
    promptInput.value = '';
    sendButton.disabled = true;
    sendButton.textContent = 'Đang trả lời…';
    const pending = addMessage('Đang chờ mô hình…', 'assistant');
    try {
        const response = await fetch(location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({csrf_token: token, messages: history})
        });
        const data = await response.json();
        pending.remove();
        if (!response.ok) {
            addMessage(data.error || 'Có lỗi khi gọi Ollama.', 'assistant', 'error');
            history.pop();
            return;
        }
        addMessage(data.reply, 'assistant');
        history.push({role: 'assistant', content: data.reply});
        history = history.slice(-10);
    } catch (error) {
        pending.remove();
        addMessage('Không nhận được phản hồi. Hãy kiểm tra Ollama và thử lại.', 'assistant', 'error');
        history.pop();
    } finally {
        sendButton.disabled = false;
        sendButton.textContent = 'Gửi';
        promptInput.focus();
    }
});
</script>
</body>
</html>
