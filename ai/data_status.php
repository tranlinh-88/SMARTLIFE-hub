<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];
$counts = [];
$queries = [
    'Môn học' => ['SELECT COUNT(*) total FROM subjects WHERE user_id=?', 'i'],
    'Bài tập' => ['SELECT COUNT(*) total FROM assignments a INNER JOIN subjects s ON s.id=a.subject_id WHERE s.user_id=?', 'i'],
    'Công việc' => ['SELECT COUNT(*) total FROM tasks WHERE user_id=?', 'i'],
    'Khoản thu' => ['SELECT COUNT(*) total FROM incomes WHERE user_id=?', 'i'],
    'Khoản chi' => ['SELECT COUNT(*) total FROM expenses WHERE user_id=?', 'i'],
    'Thói quen' => ['SELECT COUNT(*) total FROM habits WHERE user_id=?', 'i'],
    'Mục tiêu' => ['SELECT COUNT(*) total FROM goals WHERE user_id=?', 'i'],
];
foreach ($queries as $label => [$sql, $types]) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) { $counts[$label] = 'Không truy vấn được'; continue; }
    $stmt->bind_param($types, $userId);
    $stmt->execute();
    $counts[$label] = (int)$stmt->get_result()->fetch_assoc()['total'];
}
$name = htmlspecialchars((string)($_SESSION['full_name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars((string)($_SESSION['email'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Kiểm tra dữ liệu tài khoản</title><link rel="stylesheet" href="../assets/css/style.css"><style>body{background:#f3f6fb}.box{max-width:760px;margin:36px auto;padding:24px;background:white;border:1px solid #e4e9f0;border-radius:16px}.box h1{margin-top:0}.id{padding:14px;background:#eef3ff;border-radius:10px;line-height:1.8}.rows{display:grid;grid-template-columns:1fr auto;gap:0 16px}.rows div{padding:12px 4px;border-bottom:1px solid #edf0f5}.link{display:inline-block;margin-top:18px;color:#315de8;text-decoration:none}</style></head>
<body><main class="box"><h1>Kiểm tra dữ liệu tài khoản</h1><p>Trang này chỉ thống kê dữ liệu gắn với tài khoản đang đăng nhập; không thay đổi dữ liệu.</p><div class="id"><strong>Tài khoản:</strong> <?= $name ?><br><strong>Email:</strong> <?= $email ?><br><strong>user_id đang đăng nhập:</strong> <?= $userId ?></div><h2>Số bản ghi SmartLife</h2><div class="rows"><?php foreach ($counts as $label => $count): ?><div><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div><div><strong><?= $count ?></strong></div><?php endforeach; ?></div><p>Nếu các số liệu đều bằng 0 nhưng bạn thấy dữ liệu trong phpMyAdmin, dữ liệu đó có thể đang gắn với một `user_id` khác.</p><a class="link" href="assistant.php">← Quay lại AI Assistant</a></main></body></html>
