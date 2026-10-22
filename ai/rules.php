<?php
function ai_ensure_tables(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS ai_conversations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT NOT NULL,
        title VARCHAR(180) NOT NULL DEFAULT 'Cuộc trò chuyện mới',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id), KEY idx_ai_conversations_user_updated (user_id, updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $conn->query("CREATE TABLE IF NOT EXISTS ai_messages (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        conversation_id INT UNSIGNED NOT NULL,
        role ENUM('user','assistant') NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), KEY idx_ai_messages_conversation (conversation_id, id),
        CONSTRAINT fk_ai_messages_conversation FOREIGN KEY (conversation_id) REFERENCES ai_conversations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Older versions of this project used created_at on conversations and
    // sender/message on messages. Upgrade those tables without dropping chat history.
    $conversationColumns = [];
    $result = $conn->query('SHOW COLUMNS FROM ai_conversations');
    if ($result) while ($column = $result->fetch_assoc()) $conversationColumns[$column['Field']] = true;
    if (!isset($conversationColumns['updated_at'])) {
        $conn->query('ALTER TABLE ai_conversations ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    $messageColumns = [];
    $result = $conn->query('SHOW COLUMNS FROM ai_messages');
    if ($result) while ($column = $result->fetch_assoc()) $messageColumns[$column['Field']] = true;
    if (!isset($messageColumns['role'])) {
        $conn->query("ALTER TABLE ai_messages ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");
        if (isset($messageColumns['sender'])) {
            $conn->query("UPDATE ai_messages SET role = CASE WHEN sender = 'ai' OR sender = 'assistant' THEN 'assistant' ELSE 'user' END");
        }
    }
    if (!isset($messageColumns['content'])) {
        $conn->query('ALTER TABLE ai_messages ADD COLUMN content TEXT NULL');
        if (isset($messageColumns['message'])) {
            $conn->query('UPDATE ai_messages SET content = message WHERE content IS NULL');
        }
        $conn->query("UPDATE ai_messages SET content = '' WHERE content IS NULL");
        $conn->query('ALTER TABLE ai_messages MODIFY COLUMN content TEXT NOT NULL');
    }
}

function ai_rule_reply(string $question, array $context): string
{
    $q = mb_strtolower($question, 'UTF-8');
    $insights = $context['insights'] ?? [];
    $pick = static function (string $prefix) use ($insights): string {
        foreach ($insights as $insight) if (strpos($insight['title'], $prefix) !== false) return $insight['text'];
        return '';
    };
    $isDeadline = preg_match('/deadline|hạn|trễ|quá hạn|ưu tiên|việc gì|bài tập|công việc/u', $q);
    $isFinance = preg_match('/tiền|chi tiêu|thu nhập|thu chi|tài chính|chi nhiều|ngân sách/u', $q);
    $isHabit = preg_match('/thói quen|duy trì|streak|check.?in|nề nếp/u', $q);
    $isGoal = preg_match('/mục tiêu|tiến độ|hoàn thành|kế hoạch/u', $q);
    $isStudy = preg_match('/học|môn|bài tập|ôn thi|assignment/u', $q);

    if (preg_match('/^(xin chào|chào|hello|hi|bạn là ai)/u', trim($q))) {
        return "Chào bạn! Mình là AI mô phỏng của SmartLife. Mình dùng các quy tắc PHP để đọc số liệu trong tài khoản hiện tại và trả lời theo chủ đề. Bạn có thể hỏi “Tuần này mình cần ưu tiên gì?”, “Tình hình thu chi tháng này ra sao?” hoặc “Thói quen nào cần cải thiện?”";
    }

    if ($isStudy) {
        $items = [];
        foreach (($context['assignments'] ?? []) as $item) {
            if ($item['status'] === 'Hoàn thành') continue;
            $due = $item['deadline'] ? ' — hạn ' . date('d/m/Y', strtotime($item['deadline'])) : ' — chưa đặt hạn';
            $items[] = '• ' . $item['title'] . ' (' . $item['subject_name'] . ')' . $due;
            if (count($items) >= 6) break;
        }
        $reply = $items ? "Các bài tập đang mở mình tìm thấy:\n" . implode("\n", $items) : 'Mình chưa tìm thấy bài tập đang mở trong dữ liệu học tập.';
        $reply .= "\n\n" . $pick('⏰');
        return trim($reply . "\n\nGợi ý: dành một phiên tập trung cho bài gần hạn nhất, sau đó cập nhật trạng thái khi hoàn tất.");
    }
    if ($isFinance) return $pick('💰') . "\n\nĐây là phép tổng hợp giao dịch tháng hiện tại, không phải dự báo hay tư vấn tài chính.";
    if ($isHabit) return $pick('🔥') . "\n\nThử giữ mục tiêu nhỏ và đều đặn trong tuần tới; dữ liệu check-in sẽ giúp bạn thấy xu hướng rõ hơn.";
    if ($isGoal) {
        $items = [];
        foreach (($context['goals'] ?? []) as $goal) {
            $target = (float)$goal['target_value']; $current = (float)$goal['current_value'];
            $pct = $target > 0 ? min(100, round($current / $target * 100)) : 0;
            $items[] = '• ' . $goal['title'] . ': ' . $pct . '% (' . $goal['status'] . ')';
            if (count($items) >= 5) break;
        }
        return ($items ? "Tiến độ mục tiêu hiện có:\n" . implode("\n", $items) : 'Chưa có mục tiêu được ghi nhận.') . "\n\n" . $pick('🎯');
    }
    if ($isDeadline) {
        $items = [];
        foreach (array_merge($context['tasks'] ?? [], $context['assignments'] ?? []) as $item) {
            if ($item['status'] === 'Hoàn thành') continue;
            $due = $item['deadline'] ? ' — ' . date('d/m/Y', strtotime($item['deadline'])) : ' — chưa đặt hạn';
            $items[] = '• ' . $item['title'] . $due;
            if (count($items) >= 7) break;
        }
        return ($items ? "Một số đầu việc đang mở:\n" . implode("\n", $items) : 'Mình chưa tìm thấy đầu việc đang mở.') . "\n\n" . $pick('⏰') . "\n\nHãy ưu tiên việc quá hạn hoặc gần đến hạn trước.";
    }

    $summary = array_map(static fn($i) => $i['title'] . ': ' . $i['text'], $insights);
    return "Mình phân tích theo dữ liệu SmartLife và các quy tắc có sẵn. Tóm tắt hiện tại:\n• " . implode("\n• ", $summary) . "\n\nBạn có thể hỏi cụ thể về deadline, bài tập, thu chi, thói quen hoặc mục tiêu để mình lọc đúng phần dữ liệu liên quan.";
}
