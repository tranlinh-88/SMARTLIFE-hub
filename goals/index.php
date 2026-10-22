<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id =
    $_SESSION["user_id"];


$sql = "SELECT *
        FROM goals
        WHERE user_id = ?
        ORDER BY
            CASE
                WHEN status = 'Hoàn thành'
                THEN 1
                ELSE 0
            END,
            deadline ASC";


$stmt =
    $conn->prepare($sql);


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$result =
    $stmt->get_result();

$goals = $result->fetch_all(MYSQLI_ASSOC);
$total_goals = count($goals);
$completed_goals = 0;
$total_progress = 0;

foreach ($goals as $goal_summary) {
    $summary_target = (float)$goal_summary["target_value"];
    $summary_current = (float)$goal_summary["current_value"];

    if ($goal_summary["status"] == "Hoàn thành") {
        $completed_goals++;
    }

    if ($summary_target > 0) {
        $total_progress += min(100, max(0, ($summary_current / $summary_target) * 100));
    }
}

$average_progress = $total_goals > 0 ? round($total_progress / $total_goals) : 0;
$active_goals = max(0, $total_goals - $completed_goals);

?>

<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Mục tiêu - SmartLife
</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css?v=20261021-goals-notifications"
>

</head>


<body class="dashboard-body">


<div class="sidebar">

<div class="sidebar-logo">
    🌱 SmartLife
</div>


<div class="sidebar-menu">

<a href="../dashboard/index.php">
    🏠 Dashboard
</a>

<a href="../subjects/index.php">
    📚 Học tập
</a>

<a href="../tasks/index.php">
    ✅ Công việc
</a>

<a href="../expenses/index.php">
    💰 Tài chính
</a>

<a href="../habits/index.php">
    🔥 Thói quen
</a>

<a
    href="index.php"
    class="active"
>
    🎯 Mục tiêu
</a>

<a href="../ai/assistant.php">
    🤖 AI Assistant
</a>

</div>

</div>


<div class="dashboard-main">


<div class="page-header">

<div>

<h1>
    🎯 Mục tiêu của tôi
</h1>

<p>
    Theo dõi và hoàn thành
    những mục tiêu quan trọng.
</p>

</div>


<a
    href="add.php"
    class="btn-primary"
>
    + Thêm mục tiêu
</a>

</div>

<section class="goals-hero" aria-label="Tổng quan mục tiêu">
    <div class="goals-hero-content">
        <span class="goals-hero-eyebrow"><span>✦</span> HÀNH TRÌNH CỦA BẠN</span>
        <h2>Tiến từng bước,<br>chạm mục tiêu lớn.</h2>
        <p>Mỗi lần cập nhật là một bước tiến. Hãy giữ nhịp độ và biến kế hoạch của bạn thành kết quả.</p>
        <a href="add.php" class="goals-hero-button"><span>＋</span> Bắt đầu mục tiêu mới <span class="goals-button-arrow">→</span></a>
        <div class="goals-hero-note"><span>🌱</span> Tiến bộ nhỏ mỗi ngày sẽ tạo nên thay đổi lớn.</div>
    </div>

    <div class="goals-hero-summary">
        <div class="goals-hero-ring" style="--progress: <?php echo $average_progress; ?>;">
            <div><strong><?php echo $average_progress; ?><small>%</small></strong><span>tiến độ trung bình</span></div>
        </div>
        <div class="goals-hero-stats">
            <div class="goals-stat-card"><span class="goals-stat-icon">🎯</span><span><strong><?php echo $total_goals; ?></strong><small>Tổng mục tiêu</small></span></div>
            <div class="goals-stat-card"><span class="goals-stat-icon">🚀</span><span><strong><?php echo $active_goals; ?></strong><small>Đang thực hiện</small></span></div>
            <div class="goals-stat-card"><span class="goals-stat-icon">🏆</span><span><strong><?php echo $completed_goals; ?></strong><small>Đã hoàn thành</small></span></div>
        </div>
    </div>
    <span class="goals-hero-orb goals-hero-orb-one" aria-hidden="true"></span>
    <span class="goals-hero-orb goals-hero-orb-two" aria-hidden="true"></span>
</section>

<?php if (isset($_GET["success"])): ?>

<div class="success-message">

<?php

switch ($_GET["success"]) {

    case "add":
        echo "Thêm mục tiêu thành công!";
        break;

    case "edit":
        echo "Cập nhật mục tiêu thành công!";
        break;

    case "progress":
        echo "Cập nhật tiến độ thành công!";
        break;

    case "delete":
        echo "Xóa mục tiêu thành công!";
        break;

}

?>

</div>

<?php endif; ?>


<div class="goal-page-grid">


<?php if ($total_goals > 0): ?>


<?php foreach ($goals as $goal): ?>


<?php

$target =
    (float)$goal["target_value"];

$current =
    (float)$goal["current_value"];


if ($target > 0) {

    $percentage =
        ($current / $target)
        * 100;

} else {

    $percentage = 0;

}


$percentage =
    min(
        100,
        max(0, $percentage)
    );


$is_overdue = false;


if (
    !empty($goal["deadline"])
    &&
    strtotime($goal["deadline"])
        < strtotime(date("Y-m-d"))
    &&
    $goal["status"]
        != "Hoàn thành"
) {

    $is_overdue = true;

}

?>


<div class="goal-page-card">


<div class="goal-page-header">

<div>

<h2>

🎯
<?php
echo htmlspecialchars(
    $goal["title"]
);
?>

</h2>

<p>

<?php
echo htmlspecialchars(
    $goal["description"] ?? ""
);
?>

</p>

</div>


<span
    class="<?php
    echo $goal["status"] == "Hoàn thành"
        ? "goal-status-complete"
        : "goal-status-active";
    ?>"
>

<?php
echo htmlspecialchars(
    $goal["status"]
);
?>

</span>

</div>


<div class="goal-numbers">

<strong>

<?php
echo number_format(
    $current,
    0,
    ",",
    "."
);
?>

/

<?php
echo number_format(
    $target,
    0,
    ",",
    "."
);
?>

</strong>


<span>

<?php
echo round($percentage);
?>%

</span>

</div>


<div class="progress">

<div
    class="progress-bar"
    style="width:
    <?php echo $percentage; ?>%;"
></div>

</div>


<div class="goal-deadline">

📅 Deadline:

<?php

if ($goal["deadline"]) {

    echo date(
        "d/m/Y",
        strtotime(
            $goal["deadline"]
        )
    );

} else {

    echo "Không có";

}

?>


<?php if ($is_overdue): ?>

<span class="overdue-text">
    ⚠ Quá hạn
</span>

<?php endif; ?>

</div>


<div class="goal-actions">


<?php
if (
    $goal["status"]
    != "Hoàn thành"
):
?>

<a
    href="progress.php?id=<?php echo (int)$goal["id"]; ?>"
    class="btn-progress"
>
    + Cập nhật tiến độ
</a>

<?php endif; ?>


<a
    href="edit.php?id=<?php echo (int)$goal["id"]; ?>"
    class="btn-edit"
>
    Sửa
</a>


<form
    method="POST"
    action="delete.php"
    class="inline-form"
    onsubmit="return confirm('Bạn có chắc muốn xóa mục tiêu này?');"
>

<input
    type="hidden"
    name="id"
    value="<?php echo (int)$goal["id"]; ?>"
>

<input
    type="hidden"
    name="csrf_token"
    value="<?php echo htmlspecialchars(csrf_token()); ?>"
>

<button
    type="submit"
    class="btn-delete"
>
    Xóa
</button>

</form>


</div>


</div>


<?php endforeach; ?>


<?php else: ?>


<div class="empty-box">

<h2>
    🎯 Chưa có mục tiêu
</h2>

<p>
    Hãy tạo mục tiêu đầu tiên
    của bạn.
</p>

</div>


<?php endif; ?>


</div>

</div>

</body>
</html>
