<?php
function ai_rows(mysqli $conn, string $sql, string $types, array $params): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function ai_one(mysqli $conn, string $sql, string $types, array $params): array
{
    $rows = ai_rows($conn, $sql, $types, $params);
    return $rows[0] ?? [];
}

function ai_build_insights(mysqli $conn, int $userId): array
{
    $today = date('Y-m-d');
    $week = date('Y-m-d', strtotime('+7 days'));
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');

    $taskStats = ai_one($conn, "SELECT COUNT(*) total, SUM(status <> 'Hoàn thành') open_count, SUM(status <> 'Hoàn thành' AND deadline < ?) overdue, SUM(status <> 'Hoàn thành' AND deadline BETWEEN ? AND ?) due_soon FROM tasks WHERE user_id = ?", 'sssi', [$today, $today, $week, $userId]);
    $assignStats = ai_one($conn, "SELECT COUNT(*) total, SUM(a.status <> 'Hoàn thành' AND a.deadline < ?) overdue, SUM(a.status <> 'Hoàn thành' AND a.deadline BETWEEN ? AND ?) due_soon FROM assignments a INNER JOIN subjects s ON s.id=a.subject_id WHERE s.user_id = ?", 'sssi', [$today, $today, $week, $userId]);
    $topDeadlines = ai_rows($conn, "SELECT title, deadline, status, 'Công việc' item_type FROM tasks WHERE user_id=? AND status <> 'Hoàn thành' AND deadline IS NOT NULL UNION ALL SELECT a.title, a.deadline, a.status, s.subject_name item_type FROM assignments a INNER JOIN subjects s ON s.id=a.subject_id WHERE s.user_id=? AND a.status <> 'Hoàn thành' AND a.deadline IS NOT NULL ORDER BY deadline ASC LIMIT 5", 'ii', [$userId, $userId]);
    $money = ai_one($conn, "SELECT (SELECT COALESCE(SUM(amount),0) FROM incomes WHERE user_id=? AND income_date BETWEEN ? AND ?) income_total, (SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND expense_date BETWEEN ? AND ?) expense_total", 'ississ', [$userId, $monthStart, $monthEnd, $userId, $monthStart, $monthEnd]);
    $topExpense = ai_one($conn, "SELECT category, SUM(amount) total FROM expenses WHERE user_id=? AND expense_date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC LIMIT 1", 'iss', [$userId, $monthStart, $monthEnd]);
    $habitStats = ai_one($conn, "SELECT COUNT(*) total_habits, (SELECT COUNT(*) FROM habit_logs hl INNER JOIN habits h ON h.id=hl.habit_id WHERE h.user_id=? AND hl.status=1 AND hl.log_date BETWEEN ? AND ?) checkins FROM habits WHERE user_id=?", 'issi', [$userId, date('Y-m-d', strtotime('-6 days')), $today, $userId]);
    $weakHabit = ai_one($conn, "SELECT h.habit_name, COUNT(hl.id) checks FROM habits h LEFT JOIN habit_logs hl ON hl.habit_id=h.id AND hl.status=1 AND hl.log_date BETWEEN ? AND ? WHERE h.user_id=? GROUP BY h.id, h.habit_name ORDER BY checks ASC, h.id LIMIT 1", 'ssi', [date('Y-m-d', strtotime('-6 days')), $today, $userId]);
    $goals = ai_rows($conn, "SELECT title, target_value, current_value, deadline, status FROM goals WHERE user_id=? ORDER BY CASE WHEN status='Hoàn thành' THEN 1 ELSE 0 END, deadline IS NULL, deadline ASC LIMIT 10", 'i', [$userId]);
    $activeGoals = array_values(array_filter($goals, static fn($g) => $g['status'] !== 'Hoàn thành'));

    $taskOpen = (int)($taskStats['open_count'] ?? 0) + (int)($assignStats['total'] ?? 0) - (int)($assignStats['overdue'] ?? 0) - (int)($assignStats['due_soon'] ?? 0);
    $overdue = (int)($taskStats['overdue'] ?? 0) + (int)($assignStats['overdue'] ?? 0);
    $dueSoon = (int)($taskStats['due_soon'] ?? 0) + (int)($assignStats['due_soon'] ?? 0);
    $income = (float)($money['income_total'] ?? 0);
    $expense = (float)($money['expense_total'] ?? 0);
    $balance = $income - $expense;
    $insights = [];

    if ($overdue > 0 || $dueSoon > 0) {
        $text = $overdue > 0 ? "Có {$overdue} đầu việc đã quá hạn." : '';
        if ($dueSoon > 0) $text .= ($text ? ' ' : '') . "{$dueSoon} đầu việc đến hạn trong 7 ngày tới.";
        if ($topDeadlines) $text .= ' Gần nhất: ' . $topDeadlines[0]['title'] . ' (' . date('d/m/Y', strtotime($topDeadlines[0]['deadline'])) . ').';
        $text .= ' Hãy ưu tiên việc gần hạn và chia thành các bước nhỏ.';
        $insights[] = ['title' => '⏰ Deadline cần chú ý', 'text' => $text];
    } else {
        $insights[] = ['title' => '✅ Deadline', 'text' => $topDeadlines ? 'Chưa thấy mục quá hạn hoặc đến hạn trong 7 ngày. Việc gần nhất: ' . $topDeadlines[0]['title'] . '.' : 'Chưa có deadline sắp tới trong danh sách công việc và bài tập.'];
    }

    $moneyText = 'Tháng này: thu ' . number_format($income, 0, ',', '.') . 'đ, chi ' . number_format($expense, 0, ',', '.') . 'đ, chênh lệch ' . number_format($balance, 0, ',', '.') . 'đ.';
    if ($expense > $income) $moneyText .= ' Chi đang cao hơn thu; nên rà soát các khoản không thiết yếu.';
    elseif ($income > 0 && $expense / $income >= 0.8) $moneyText .= ' Chi đã đạt ít nhất 80% thu nhập tháng; cân nhắc theo dõi hạn mức.';
    elseif ($income == 0 && $expense == 0) $moneyText .= ' Chưa có giao dịch trong tháng để phân tích.';
    if (!empty($topExpense['category'])) $moneyText .= ' Nhóm chi nhiều nhất: ' . $topExpense['category'] . ' (' . number_format((float)$topExpense['total'], 0, ',', '.') . 'đ).';
    $insights[] = ['title' => '💰 Thu chi tháng này', 'text' => $moneyText];

    $habitCount = (int)($habitStats['total_habits'] ?? 0);
    $checks = (int)($habitStats['checkins'] ?? 0);
    $habitText = $habitCount ? "Bạn có {$habitCount} thói quen và ghi nhận {$checks} lượt hoàn thành trong 7 ngày qua." : 'Bạn chưa tạo thói quen nào.';
    if ($habitCount && $weakHabit) $habitText .= ' Thói quen có ít lượt ghi nhận nhất là “' . $weakHabit['habit_name'] . '” (' . (int)$weakHabit['checks'] . ' lần); thử đặt nhắc nhở và mục tiêu nhỏ hơn.';
    $insights[] = ['title' => '🔥 Thói quen', 'text' => $habitText];

    if ($activeGoals) {
        $g = $activeGoals[0]; $target = (float)$g['target_value']; $current = (float)$g['current_value'];
        $percent = $target > 0 ? min(100, round($current / $target * 100)) : 0;
        $goalText = 'Mục tiêu đang ưu tiên: “' . $g['title'] . '”, đạt ' . $percent . '% (' . number_format($current, 2, ',', '.') . '/' . number_format($target, 2, ',', '.') . ').';
        if (!empty($g['deadline'])) $goalText .= ' Hạn: ' . date('d/m/Y', strtotime($g['deadline'])) . '.';
        $goalText .= ' Chia phần còn lại thành mốc tuần để dễ theo dõi.';
    } else $goalText = 'Chưa có mục tiêu đang thực hiện. Hãy tạo mục tiêu cụ thể, có mốc thời gian và cách đo tiến độ.';
    $insights[] = ['title' => '🎯 Mục tiêu', 'text' => $goalText];

    return $insights;
}

function ai_context_data(mysqli $conn, int $userId): array
{
    return [
        'insights' => ai_build_insights($conn, $userId),
        'tasks' => ai_rows($conn, "SELECT title, deadline, priority, status FROM tasks WHERE user_id=? ORDER BY CASE WHEN status='Hoàn thành' THEN 1 ELSE 0 END, deadline ASC LIMIT 30", 'i', [$userId]),
        'assignments' => ai_rows($conn, "SELECT a.title, a.deadline, a.status, s.subject_name FROM assignments a INNER JOIN subjects s ON s.id=a.subject_id WHERE s.user_id=? ORDER BY CASE WHEN a.status='Hoàn thành' THEN 1 ELSE 0 END, a.deadline ASC LIMIT 30", 'i', [$userId]),
        'goals' => ai_rows($conn, "SELECT title, target_value, current_value, deadline, status FROM goals WHERE user_id=? ORDER BY deadline ASC LIMIT 30", 'i', [$userId]),
    ];
}
