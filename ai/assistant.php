<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/context.php';
require_once __DIR__ . '/rules.php';

ai_ensure_tables($conn);
$user_id = (int)$_SESSION['user_id'];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Yêu cầu không hợp lệ. Vui lòng tải lại trang.');
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'new') {
        $title = 'Cuộc trò chuyện mới';
        $stmt = $conn->prepare('INSERT INTO ai_conversations (user_id, title) VALUES (?, ?)');
        $stmt->bind_param('is', $user_id, $title);
        $stmt->execute();
        header('Location: assistant.php?id=' . $conn->insert_id);
        exit;
    }
    if ($action === 'delete') {
        $id = (int)($_POST['conversation_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM ai_conversations WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $user_id);
        $stmt->execute();
        header('Location: assistant.php');
        exit;
    }
}

$conversations = [];
$stmt = $conn->prepare('SELECT id, title, updated_at FROM ai_conversations WHERE user_id = ? ORDER BY updated_at DESC, id DESC');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conversation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($conversation_id) {
    $stmt = $conn->prepare('SELECT id, title FROM ai_conversations WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $conversation_id, $user_id);
    $stmt->execute();
    $conversation = $stmt->get_result()->fetch_assoc();
    if (!$conversation) { http_response_code(404); die('Không tìm thấy cuộc trò chuyện.'); }
} elseif ($conversations) {
    $conversation_id = (int)$conversations[0]['id'];
    $conversation = $conversations[0];
} else {
    $title = 'Cuộc trò chuyện mới';
    $stmt = $conn->prepare('INSERT INTO ai_conversations (user_id, title) VALUES (?, ?)');
    $stmt->bind_param('is', $user_id, $title);
    $stmt->execute();
    $conversation_id = $conn->insert_id;
    header('Location: assistant.php?id=' . $conversation_id);
    exit;
}

$stmt = $conn->prepare('SELECT id, role, content, created_at FROM ai_messages WHERE conversation_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $conversation_id);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$insights = ai_build_insights($conn, $user_id);
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>AI Assistant mô phỏng | SmartLife</title><link rel="stylesheet" href="../assets/css/style.css"><style>
.ai-layout{display:grid;grid-template-columns:245px minmax(0,1fr);gap:20px;max-width:1400px;margin:28px auto;padding:0 22px}.ai-panel{background:#fff;border:1px solid #e4e9f0;border-radius:16px;padding:18px}.ai-side-title{font-size:18px;margin:0 0 14px}.ai-home{display:block;margin:0 0 14px;padding:9px 10px;border-radius:9px;background:#f5f7fb;color:#315de8;text-decoration:none;font-weight:650}.ai-new{width:100%;padding:11px;border:0;border-radius:10px;background:#315de8;color:white;font-weight:700;cursor:pointer}.ai-conv{display:block;margin-top:9px;padding:10px;border-radius:9px;color:#27334a;text-decoration:none;background:#f5f7fb;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ai-conv.active{background:#eaf0ff;color:#2148c7;font-weight:650}.ai-main{min-width:0}.ai-head{display:flex;justify-content:space-between;align-items:center;gap:12px}.ai-head h1{margin:0;font-size:25px}.ai-chip{font-size:13px;color:#246044;background:#e8f7ef;padding:7px 10px;border-radius:99px}.ai-grid{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:18px;margin-top:16px}.ai-chat{display:flex;flex-direction:column;background:#fff;border:1px solid #e4e9f0;border-radius:16px;min-height:620px;overflow:hidden}.ai-log{flex:1;max-height:610px;overflow:auto;padding:18px}.ai-msg{white-space:pre-wrap;max-width:88%;padding:12px 15px;border-radius:14px;margin:9px 0;line-height:1.55}.ai-msg.user{margin-left:auto;background:#315de8;color:white;border-bottom-right-radius:4px}.ai-msg.assistant{background:#f1f4f8;border-bottom-left-radius:4px;color:#202b3e}.ai-form{display:flex;gap:10px;border-top:1px solid #edf0f5;padding:14px}.ai-form textarea{flex:1;resize:vertical;min-height:48px;max-height:130px;padding:12px;border:1px solid #d8dfeb;border-radius:11px;font:inherit}.ai-form button{border:0;background:#315de8;color:white;border-radius:10px;padding:0 18px;font-weight:700;cursor:pointer}.ai-form button:disabled{opacity:.6}.ai-insights h2{font-size:17px;margin:0 0 12px}.ai-insight{border:1px solid #e8edf4;border-radius:12px;padding:12px;margin:10px 0;background:#fff}.ai-insight strong{display:block;margin-bottom:4px}.ai-insight p{margin:0;color:#59667c;font-size:14px}.ai-meta{font-size:12px;color:#718096;margin-top:6px}.ai-disclaimer{color:#657188;font-size:13px;line-height:1.5;margin-top:12px}.ai-empty{color:#647187;padding:12px}.ai-delete{border:0;background:transparent;color:#9b4242;cursor:pointer;font-size:12px;margin-top:12px;padding:7px}.ai-mobile{display:none}@media(max-width:1000px){.ai-grid{grid-template-columns:1fr}.ai-insights{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.ai-insights h2,.ai-disclaimer{grid-column:1/-1}}@media(max-width:680px){.ai-layout{display:block;padding:0 12px;margin:15px auto}.ai-sidebar{margin-bottom:12px}.ai-conv-list{display:flex;gap:7px;overflow:auto}.ai-conv{min-width:155px;margin-top:0}.ai-head{align-items:flex-start;flex-direction:column}.ai-insights{display:block}.ai-chat{min-height:65vh}.ai-log{max-height:55vh}.ai-msg{max-width:96%}}
</style></head><body class="dashboard-body">
<div class="ai-layout">
<aside class="ai-panel ai-sidebar"><h2 class="ai-side-title">🤖 SmartLife AI</h2><a class="ai-home" href="../dashboard/index.php">← Về trang chủ SmartLife</a><form method="post"><input type="hidden" name="csrf_token" value="<?= $h(csrf_token()) ?>"><input type="hidden" name="action" value="new"><button class="ai-new">＋ Cuộc trò chuyện mới</button></form><div class="ai-conv-list"><?php foreach ($conversations as $item): ?><a class="ai-conv <?= (int)$item['id'] === $conversation_id ? 'active' : '' ?>" href="assistant.php?id=<?= (int)$item['id'] ?>"><?= $h($item['title']) ?></a><?php endforeach; ?></div><form method="post" onsubmit="return confirm('Xóa cuộc trò chuyện này?')"><input type="hidden" name="csrf_token" value="<?= $h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="conversation_id" value="<?= $conversation_id ?>"><button class="ai-delete">Xóa cuộc trò chuyện đang mở</button></form><p class="ai-disclaimer">AI mô phỏng bằng quy tắc PHP, không gọi API và không cần API key.</p></aside>
<main class="ai-main"><header class="ai-head"><h1>Trợ lý cá nhân hóa</h1><span class="ai-chip">● Chạy bằng PHP + MySQL</span></header><div class="ai-grid"><section class="ai-chat"><div class="ai-log" id="ai-log"><?php if (!$messages): ?><div class="ai-msg assistant">Chào bạn! Mình đã tổng hợp dữ liệu trong tài khoản để đưa ra gợi ý bên cạnh. Bạn có thể hỏi về deadline, học tập, thu chi, thói quen hoặc mục tiêu. Mình trả lời theo quy tắc và số liệu hiện có, không phải mô hình ngôn ngữ.</div><?php endif; ?><?php foreach ($messages as $message): ?><div class="ai-msg <?= $message['role'] === 'user' ? 'user' : 'assistant' ?>"><?= nl2br($h($message['content'])) ?><div class="ai-meta"><?= $h($message['created_at']) ?></div></div><?php endforeach; ?></div><form class="ai-form" id="ai-form"><textarea id="ai-input" maxlength="1000" placeholder="Ví dụ: Tuần này mình nên ưu tiên việc gì?" required></textarea><button id="ai-send">Gửi</button></form></section>
<aside class="ai-insights"><h2>Gợi ý từ dữ liệu của bạn</h2><?php foreach ($insights as $insight): ?><article class="ai-insight"><strong><?= $h($insight['title']) ?></strong><p><?= $h($insight['text']) ?></p></article><?php endforeach; ?><p class="ai-disclaimer">Gợi ý được suy ra từ dữ liệu hiện có của tài khoản đang đăng nhập. Tài chính chỉ là tổng hợp thu chi, không phải tư vấn chuyên môn.</p></aside></div></main></div>
<script>
const chatLog=document.getElementById('ai-log'), form=document.getElementById('ai-form'), input=document.getElementById('ai-input'), send=document.getElementById('ai-send');
function bubble(content,role){const el=document.createElement('div');el.className='ai-msg '+role;el.textContent=content;chatLog.appendChild(el);chatLog.scrollTop=chatLog.scrollHeight;return el;}
chatLog.scrollTop=chatLog.scrollHeight;
form.addEventListener('submit',async e=>{e.preventDefault();const text=input.value.trim();if(!text||send.disabled)return;bubble(text,'user');input.value='';send.disabled=true;const wait=bubble('Đang phân tích dữ liệu…','assistant');try{const r=await fetch('chat.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({csrf_token:<?= json_encode(csrf_token()) ?>,conversation_id:<?= $conversation_id ?>,message:text})});const data=await r.json();wait.remove();if(!r.ok)bubble(data.error||'Không thể xử lý câu hỏi.','assistant');else{bubble(data.reply,'assistant');if(data.title)document.title=data.title+' | SmartLife AI';}}catch(err){wait.remove();bubble('Không kết nối được với ứng dụng. Vui lòng thử lại.','assistant');}finally{send.disabled=false;input.focus();chatLog.scrollTop=chatLog.scrollHeight;}});
</script></body></html>
