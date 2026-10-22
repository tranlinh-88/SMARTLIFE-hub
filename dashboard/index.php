<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = (int)$_SESSION["user_id"];


/* =====================================================
   HÀM HỖ TRỢ
===================================================== */

function getCount($conn, $sql, $user_id)
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    return (int)$row["total"];
}


function getMoney($conn, $sql, $user_id)
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    return (float)$row["total"];
}


/* =====================================================
   1. THỐNG KÊ CƠ BẢN
===================================================== */

$total_subjects = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM subjects
     WHERE user_id = ?",
    $user_id
);


$total_tasks = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM tasks
     WHERE user_id = ?
     AND status != 'Hoàn thành'",
    $user_id
);


$total_assignments = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM assignments a
     INNER JOIN subjects s
        ON a.subject_id = s.id
     WHERE s.user_id = ?
     AND a.status != 'Hoàn thành'",
    $user_id
);


$total_habits = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM habits
     WHERE user_id = ?",
    $user_id
);


$total_goals = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM goals
     WHERE user_id = ?
     AND status != 'Hoàn thành'",
    $user_id
);


$total_notifications = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM notifications
     WHERE user_id = ?
     AND is_read = 0",
    $user_id
);

$notification_stmt = $conn->prepare(
    "SELECT id, title, message, type, is_read, created_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC, id DESC
     LIMIT 12"
);
$notification_stmt->bind_param("i", $user_id);
$notification_stmt->execute();
$notifications = $notification_stmt->get_result()->fetch_all(MYSQLI_ASSOC);


/* =====================================================
   2. TÀI CHÍNH THÁNG HIỆN TẠI
===================================================== */

$total_income = getMoney(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM incomes
     WHERE user_id = ?
     AND YEAR(income_date) = YEAR(CURDATE())
     AND MONTH(income_date) = MONTH(CURDATE())",
    $user_id
);


$total_expense = getMoney(
    $conn,
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM expenses
     WHERE user_id = ?
     AND YEAR(expense_date) = YEAR(CURDATE())
     AND MONTH(expense_date) = MONTH(CURDATE())",
    $user_id
);


$balance =
    $total_income - $total_expense;


/* =====================================================
   3. ĐIỂM HỌC TẬP
===================================================== */

/*
    Điểm học tập =
    số bài tập hoàn thành / tổng bài tập × 100

    Nếu chưa có bài tập:
    điểm = 0
*/

$sql = "SELECT
            COUNT(*) AS total,
            SUM(
                CASE
                    WHEN a.status = 'Hoàn thành'
                    THEN 1
                    ELSE 0
                END
            ) AS completed

        FROM assignments a

        INNER JOIN subjects s
            ON a.subject_id = s.id

        WHERE s.user_id = ?";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$study_data =
    $stmt->get_result()->fetch_assoc();


$study_total =
    (int)$study_data["total"];

$study_completed =
    (int)($study_data["completed"] ?? 0);


$study_score =
    $study_total > 0
    ? round(
        ($study_completed / $study_total)
        * 100
    )
    : 0;


/* =====================================================
   4. ĐIỂM CÔNG VIỆC
===================================================== */

$sql = "SELECT
            COUNT(*) AS total,
            SUM(
                CASE
                    WHEN status = 'Hoàn thành'
                    THEN 1
                    ELSE 0
                END
            ) AS completed

        FROM tasks

        WHERE user_id = ?";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$task_data =
    $stmt->get_result()->fetch_assoc();


$task_total =
    (int)$task_data["total"];

$task_completed =
    (int)($task_data["completed"] ?? 0);


$task_score =
    $task_total > 0
    ? round(
        ($task_completed / $task_total)
        * 100
    )
    : 0;


/* =====================================================
   5. ĐIỂM TÀI CHÍNH
===================================================== */

/*
    Cách tính:

    Nếu không có thu nhập:
        score = 0

    Nếu chi <= thu:
        score dựa trên tỷ lệ tiền còn lại.

    Để điểm không quá khắc nghiệt:
        50 điểm cơ bản nếu không chi vượt thu.
        Phần tiết kiệm tạo thêm tối đa 50 điểm.

    Ví dụ:
        Thu 3 triệu
        Chi 1.5 triệu
        Còn 50%

        Score = 50 + 50×0.5 = 75
*/

if ($total_income <= 0) {

    $finance_score = 0;

} elseif ($total_expense > $total_income) {

    $finance_score = 30;

} else {

    $saving_rate =
        ($total_income - $total_expense)
        / $total_income;


    $finance_score =
        round(
            50 + ($saving_rate * 50)
        );

}


$finance_score =
    max(
        0,
        min(100, $finance_score)
    );


/* =====================================================
   6. ĐIỂM THÓI QUEN
   TỶ LỆ CHECK-IN 7 NGÀY
===================================================== */

$sql = "SELECT COUNT(*) AS total
        FROM habits
        WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$habit_count =
    (int)$stmt
        ->get_result()
        ->fetch_assoc()["total"];


if ($habit_count > 0) {

    $sql = "SELECT COUNT(*) AS total

            FROM habit_logs hl

            INNER JOIN habits h
                ON hl.habit_id = h.id

            WHERE h.user_id = ?

            AND hl.status = 1

            AND hl.log_date
                BETWEEN
                DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                AND CURDATE()";


    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();


    $habit_checks =
        (int)$stmt
            ->get_result()
            ->fetch_assoc()["total"];


    /*
        Ví dụ:
        2 habits × 7 ngày
        = tối đa 14 check-in
    */

    $possible_checks =
        $habit_count * 7;


    $habit_score =
        round(
            ($habit_checks /
            $possible_checks)
            * 100
        );


    $habit_score =
        min(100, $habit_score);

} else {

    $habit_score = 0;

}


/* =====================================================
   7. ĐIỂM MỤC TIÊU
===================================================== */

$sql = "SELECT
            target_value,
            current_value
        FROM goals
        WHERE user_id = ?";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$goal_result =
    $stmt->get_result();


$goal_percent_total = 0;
$goal_count = 0;


while (
    $goal =
    $goal_result->fetch_assoc()
) {

    $target =
        (float)$goal["target_value"];

    $current =
        (float)$goal["current_value"];


    if ($target > 0) {

        $percent =
            ($current / $target)
            * 100;

        $percent =
            min(100, $percent);


        $goal_percent_total +=
            $percent;

        $goal_count++;

    }

}


$goal_score =
    $goal_count > 0
    ? round(
        $goal_percent_total
        / $goal_count
    )
    : 0;


/* =====================================================
   8. SMARTLIFE SCORE
===================================================== */

$smartlife_score =
    round(
        (
            $study_score
            +
            $task_score
            +
            $finance_score
            +
            $habit_score
            +
            $goal_score
        )
        / 5
    );


$smartlife_score =
    max(
        0,
        min(100, $smartlife_score)
    );


/* =====================================================
   9. HOẠT ĐỘNG THÓI QUEN 7 NGÀY
===================================================== */

$seven_day_labels = [];
$seven_day_values = [];


for ($i = 6; $i >= 0; $i--) {

    $date =
        date(
            "Y-m-d",
            strtotime("-$i days")
        );


    $seven_day_labels[] =
        date(
            "d/m",
            strtotime($date)
        );


    $sql = "SELECT COUNT(*) AS total

            FROM habit_logs hl

            INNER JOIN habits h
                ON hl.habit_id = h.id

            WHERE h.user_id = ?

            AND hl.log_date = ?

            AND hl.status = 1";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        "is",
        $user_id,
        $date
    );


    $stmt->execute();


    $seven_day_values[] =
        (int)$stmt
            ->get_result()
            ->fetch_assoc()["total"];

}


/* =====================================================
   10. THU / CHI 6 THÁNG
===================================================== */

$month_labels = [];
$income_values = [];
$expense_values = [];


for ($i = 5; $i >= 0; $i--) {


    $month_start =
        date(
            "Y-m-01",
            strtotime("-$i months")
        );


    $month_end =
        date(
            "Y-m-t",
            strtotime($month_start)
        );


    $month_labels[] =
        date(
            "m/Y",
            strtotime($month_start)
        );


    /* THU */

    $sql = "SELECT
                COALESCE(SUM(amount), 0)
                AS total

            FROM incomes

            WHERE user_id = ?

            AND income_date
                BETWEEN ? AND ?";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        "iss",
        $user_id,
        $month_start,
        $month_end
    );


    $stmt->execute();


    $income_values[] =
        (float)$stmt
            ->get_result()
            ->fetch_assoc()["total"];


    /* CHI */

    $sql = "SELECT
                COALESCE(SUM(amount), 0)
                AS total

            FROM expenses

            WHERE user_id = ?

            AND expense_date
                BETWEEN ? AND ?";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        "iss",
        $user_id,
        $month_start,
        $month_end
    );


    $stmt->execute();


    $expense_values[] =
        (float)$stmt
            ->get_result()
            ->fetch_assoc()["total"];

}


/* =====================================================
   11. CÔNG VIỆC GẦN DEADLINE
===================================================== */

$sql = "SELECT
            id,
            title,
            priority,
            deadline,
            status

        FROM tasks

        WHERE user_id = ?

        AND status != 'Hoàn thành'

        ORDER BY
            CASE
                WHEN deadline IS NULL
                THEN 1
                ELSE 0
            END,
            deadline ASC

        LIMIT 5";


$stmt =
    $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$recent_tasks =
    $stmt->get_result();


/* =====================================================
   12. BÀI TẬP GẦN DEADLINE
===================================================== */

$sql = "SELECT
            a.id,
            a.title,
            a.deadline,
            a.status,
            s.subject_name

        FROM assignments a

        INNER JOIN subjects s
            ON a.subject_id = s.id

        WHERE s.user_id = ?

        AND a.status != 'Hoàn thành'

        ORDER BY
            CASE
                WHEN a.deadline IS NULL
                THEN 1
                ELSE 0
            END,
            a.deadline ASC

        LIMIT 5";


$stmt =
    $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$assignments =
    $stmt->get_result();


/* =====================================================
   13. MỤC TIÊU
===================================================== */

$sql = "SELECT
            id,
            title,
            target_value,
            current_value,
            deadline,
            status

        FROM goals

        WHERE user_id = ?

        ORDER BY
            CASE
                WHEN status = 'Hoàn thành'
                THEN 1
                ELSE 0
            END,
            deadline ASC

        LIMIT 4";


$stmt =
    $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$dashboard_goals =
    $stmt->get_result();

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
    Dashboard - SmartLife Hub
</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css?v=20261021-goals-notifications"
>

<script
    src="https://cdn.jsdelivr.net/npm/chart.js">
</script>

</head>


<body class="dashboard-body">


<!-- =====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">

    <div class="sidebar-logo">
        🌱 SmartLife
    </div>


    <div class="sidebar-menu">

        <a
            href="index.php"
            class="active"
        >
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

        <a href="../goals/index.php">
            🎯 Mục tiêu
        </a>

        <a href="../ai/assistant.php">
            🤖 AI Assistant
        </a>

    </div>

</div>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="dashboard-main">


<!-- TOPBAR -->

<div class="topbar">

    <div>

        <h2>
            Xin chào,
            <?php
            echo htmlspecialchars(
                $_SESSION["full_name"]
            );
            ?> 👋
        </h2>

        <p>
            Tổng quan SmartLife của bạn
        </p>

    </div>


    <div class="topbar-user">

        <div class="notification" id="notification-area">
            <button class="notification-trigger" id="notification-trigger" type="button" aria-label="Mở thông báo" aria-expanded="false" aria-controls="notification-panel">
                <span aria-hidden="true">🔔</span>
                <?php if ($total_notifications > 0): ?>
                    <span class="notification-number" id="notification-badge"><?php echo $total_notifications; ?></span>
                <?php endif; ?>
            </button>
            <section class="notification-panel" id="notification-panel" aria-label="Thông báo" hidden>
                <div class="notification-panel-head">
                    <div><strong>Thông báo</strong><small><?php echo $total_notifications; ?> mục chưa đọc</small></div>
                    <?php if ($total_notifications > 0): ?>
                        <button type="button" id="mark-all-notifications">Đánh dấu đã đọc</button>
                    <?php endif; ?>
                </div>
                <div class="notification-list">
                    <?php if (!$notifications): ?>
                        <p class="notification-empty">Bạn chưa có thông báo nào.</p>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                            $notification_type = strtolower((string)$notification["type"]);
                            $notification_message_lower = mb_strtolower((string)$notification["message"], "UTF-8");
                            $notification_href = "index.php";
                            if ($notification_type === "deadline" && mb_strpos($notification_message_lower, "bài tập") !== false) $notification_href = "../subjects/index.php";
                            elseif (in_array($notification_type, ["task", "deadline", "work", "công việc"], true)) $notification_href = "../tasks/index.php";
                            elseif (in_array($notification_type, ["study", "learning", "học tập"], true)) $notification_href = "../subjects/index.php";
                            elseif (in_array($notification_type, ["habit", "thói quen"], true)) $notification_href = "../habits/index.php";
                            elseif (in_array($notification_type, ["goal", "mục tiêu"], true)) $notification_href = "../goals/index.php";
                            elseif (in_array($notification_type, ["finance", "income", "expense", "tài chính"], true)) $notification_href = "../expenses/index.php";
                            ?>
                            <a class="notification-item <?php echo (int)$notification["is_read"] === 0 ? "unread" : ""; ?>" href="<?php echo $notification_href; ?>" data-notification-id="<?php echo (int)$notification["id"]; ?>">
                                <span class="notification-item-icon" aria-hidden="true"><?php echo $notification_type === "task" || $notification_type === "deadline" ? "✅" : ($notification_type === "study" ? "📚" : ($notification_type === "habit" ? "🔥" : ($notification_type === "goal" ? "🎯" : "🔔"))); ?></span>
                                <span class="notification-item-copy"><strong><?php echo htmlspecialchars($notification["title"], ENT_QUOTES, "UTF-8"); ?></strong><span><?php echo htmlspecialchars($notification["message"], ENT_QUOTES, "UTF-8"); ?></span><small><?php echo date("d/m/Y H:i", strtotime($notification["created_at"])); ?></small></span>
                                <?php if ((int)$notification["is_read"] === 0): ?><span class="notification-unread-dot" aria-label="Chưa đọc"></span><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>


        <div class="user-info">

            <strong>

                <?php
                echo htmlspecialchars(
                    $_SESSION["full_name"]
                );
                ?>

            </strong>

            <small>

                <?php
                echo htmlspecialchars(
                    $_SESSION["email"]
                );
                ?>

            </small>

        </div>


        <a
            href="../logout.php"
            class="logout-button"
        >
            Đăng xuất
        </a>

    </div>

</div>

<script>
(() => {
    const area = document.getElementById('notification-area');
    const trigger = document.getElementById('notification-trigger');
    const panel = document.getElementById('notification-panel');
    if (!area || !trigger || !panel) return;
    const csrfToken = <?php echo json_encode(csrf_token()); ?>;
    const closePanel = () => { panel.hidden = true; trigger.setAttribute('aria-expanded', 'false'); };
    trigger.addEventListener('click', () => {
        const opening = panel.hidden;
        panel.hidden = !opening;
        trigger.setAttribute('aria-expanded', String(opening));
    });
    document.addEventListener('click', event => { if (!area.contains(event.target)) closePanel(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') { closePanel(); trigger.focus(); } });
    panel.querySelectorAll('[data-notification-id]').forEach(item => item.addEventListener('click', async event => {
        event.preventDefault();
        try {
            await fetch('../notifications/read.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({csrf_token:csrfToken, id:item.dataset.notificationId})});
        } finally { window.location.href = item.href; }
    }));
    const markAll = document.getElementById('mark-all-notifications');
    if (markAll) markAll.addEventListener('click', async () => {
        markAll.disabled = true;
        try {
            await fetch('../notifications/read.php', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({csrf_token:csrfToken, all:true})});
            window.location.reload();
        } catch (error) { markAll.disabled = false; }
    });
})();
</script>


<!-- =====================================================
     SMARTLIFE SCORE
===================================================== -->

<div class="smartlife-score-section">


    <div class="smartlife-score-card">

        <div
            class="score-circle"
            style="--score: <?php echo $smartlife_score; ?>;"
        >

            <div>

                <strong>
                    <?php
                    echo $smartlife_score;
                    ?>
                </strong>

                <span>/100</span>

            </div>

        </div>


        <div class="score-content">

            <h1>
                🌱 SmartLife Score
            </h1>

            <p>
                Điểm tổng hợp dựa trên
                học tập, công việc, tài chính,
                thói quen và mục tiêu.
            </p>


            <div class="score-small-grid">


                <div>
                    <span>📚 Học tập</span>
                    <strong>
                        <?php echo $study_score; ?>
                    </strong>
                </div>


                <div>
                    <span>✅ Công việc</span>
                    <strong>
                        <?php echo $task_score; ?>
                    </strong>
                </div>


                <div>
                    <span>💰 Tài chính</span>
                    <strong>
                        <?php echo $finance_score; ?>
                    </strong>
                </div>


                <div>
                    <span>🔥 Thói quen</span>
                    <strong>
                        <?php echo $habit_score; ?>
                    </strong>
                </div>


                <div>
                    <span>🎯 Mục tiêu</span>
                    <strong>
                        <?php echo $goal_score; ?>
                    </strong>
                </div>


            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     THỐNG KÊ
===================================================== -->

<h2 class="section-title">
    Tổng quan
</h2>


<div class="dashboard-stat-grid">


<div class="dashboard-stat-card">

    <span class="dashboard-stat-icon">
        📚
    </span>

    <div>
        <p>Môn học</p>
        <h3>
            <?php echo $total_subjects; ?>
        </h3>
    </div>

</div>


<div class="dashboard-stat-card">

    <span class="dashboard-stat-icon">
        📝
    </span>

    <div>
        <p>Bài tập chưa xong</p>
        <h3>
            <?php echo $total_assignments; ?>
        </h3>
    </div>

</div>


<div class="dashboard-stat-card">

    <span class="dashboard-stat-icon">
        ✅
    </span>

    <div>
        <p>Công việc chưa xong</p>
        <h3>
            <?php echo $total_tasks; ?>
        </h3>
    </div>

</div>


<div class="dashboard-stat-card">

    <span class="dashboard-stat-icon">
        🔥
    </span>

    <div>
        <p>Thói quen</p>
        <h3>
            <?php echo $total_habits; ?>
        </h3>
    </div>

</div>


<div class="dashboard-stat-card">

    <span class="dashboard-stat-icon">
        🎯
    </span>

    <div>
        <p>Mục tiêu đang làm</p>
        <h3>
            <?php echo $total_goals; ?>
        </h3>
    </div>

</div>


</div>


<!-- =====================================================
     TÀI CHÍNH
===================================================== -->

<h2 class="section-title">
    💰 Tài chính tháng này
</h2>


<div class="dashboard-finance-grid">


<div class="dashboard-money-card">

    <span>💵</span>

    <p>Thu nhập</p>

    <h3 class="income-text">

        <?php
        echo number_format(
            $total_income,
            0,
            ",",
            "."
        );
        ?> đ

    </h3>

</div>


<div class="dashboard-money-card">

    <span>💸</span>

    <p>Chi tiêu</p>

    <h3 class="expense-text">

        <?php
        echo number_format(
            $total_expense,
            0,
            ",",
            "."
        );
        ?> đ

    </h3>

</div>


<div class="dashboard-money-card">

    <span>💰</span>

    <p>Số dư</p>

    <h3>

        <?php
        echo number_format(
            $balance,
            0,
            ",",
            "."
        );
        ?> đ

    </h3>

</div>


</div>


<!-- =====================================================
     BIỂU ĐỒ
===================================================== -->

<h2 class="section-title">
    📊 Phân tích
</h2>


<div class="dashboard-chart-grid">


    <div class="dashboard-chart-card">

        <h3>
            🔥 Hoạt động 7 ngày
        </h3>

        <p>
            Số thói quen hoàn thành
            mỗi ngày
        </p>

        <div class="dashboard-chart-box">

            <canvas
                id="habitChart"
            ></canvas>

        </div>

    </div>


    <div class="dashboard-chart-card">

        <h3>
            💰 Thu / Chi 6 tháng
        </h3>

        <p>
            So sánh tài chính cá nhân
        </p>

        <div class="dashboard-chart-box">

            <canvas
                id="financeChart"
            ></canvas>

        </div>

    </div>


</div>


<!-- =====================================================
     DEADLINE
===================================================== -->

<div class="dashboard-columns">


<!-- TASK -->

<div class="dashboard-panel">


<div class="panel-header">

    <h2>
        ✅ Công việc gần deadline
    </h2>

</div>


<?php if ($recent_tasks->num_rows > 0): ?>


<?php while ($task = $recent_tasks->fetch_assoc()): ?>


<div class="dashboard-list-item">

<div>

<strong>

<?php
echo htmlspecialchars(
    $task["title"]
);
?>

</strong>


<p>

Ưu tiên:

<?php
echo htmlspecialchars(
    $task["priority"]
);
?>

</p>

</div>


<div class="dashboard-deadline">

<?php

if ($task["deadline"]) {

    echo date(
        "d/m/Y H:i",
        strtotime(
            $task["deadline"]
        )
    );

} else {

    echo "Không deadline";

}

?>

</div>

</div>


<?php endwhile; ?>


<?php else: ?>

<p class="empty-data">
    Không có công việc cần xử lý.
</p>

<?php endif; ?>


</div>


<!-- ASSIGNMENT -->

<div class="dashboard-panel">


<div class="panel-header">

<h2>
    📚 Bài tập gần deadline
</h2>

</div>


<?php
if ($assignments->num_rows > 0):
?>


<?php
while (
    $assignment =
    $assignments->fetch_assoc()
):
?>


<div class="dashboard-list-item">

<div>

<strong>

<?php
echo htmlspecialchars(
    $assignment["title"]
);
?>

</strong>


<p>

<?php
echo htmlspecialchars(
    $assignment["subject_name"]
);
?>

</p>

</div>


<div class="dashboard-deadline">

<?php

if ($assignment["deadline"]) {

    echo date(
        "d/m/Y H:i",
        strtotime(
            $assignment["deadline"]
        )
    );

} else {

    echo "Không deadline";

}

?>

</div>

</div>


<?php endwhile; ?>


<?php else: ?>

<p class="empty-data">
    Không có bài tập cần xử lý.
</p>

<?php endif; ?>


</div>


</div>


<!-- =====================================================
     MỤC TIÊU
===================================================== -->

<div class="dashboard-panel dashboard-goals">

<div class="panel-header">

<h2>
    🎯 Tiến độ mục tiêu
</h2>

</div>


<div class="dashboard-goal-grid">


<?php
if ($dashboard_goals->num_rows > 0):
?>


<?php
while (
    $goal =
    $dashboard_goals->fetch_assoc()
):
?>


<?php

$target =
    (float)$goal["target_value"];

$current =
    (float)$goal["current_value"];


$percent =
    $target > 0
    ? ($current / $target) * 100
    : 0;


$percent =
    min(100, max(0, $percent));

?>


<div class="dashboard-goal-card">

<h3>

<?php
echo htmlspecialchars(
    $goal["title"]
);
?>

</h3>


<div class="dashboard-goal-number">

<span>

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

</span>


<strong>
    <?php
    echo round($percent);
    ?>%
</strong>

</div>


<div class="progress">

<div
    class="progress-bar"
    style="width:
    <?php echo $percent; ?>%;"
></div>

</div>


<p>

<?php

if ($goal["deadline"]) {

    echo "📅 "
        . date(
            "d/m/Y",
            strtotime(
                $goal["deadline"]
            )
        );

} else {

    echo "Không có deadline";

}

?>

</p>

</div>


<?php endwhile; ?>


<?php else: ?>

<p class="empty-data">
    Chưa có mục tiêu.
</p>

<?php endif; ?>


</div>

</div>


</div>


<!-- =====================================================
     CHART.JS
===================================================== -->

<script>

const sevenDayLabels =
<?php
echo json_encode(
    $seven_day_labels,
    JSON_UNESCAPED_UNICODE
);
?>;


const sevenDayValues =
<?php
echo json_encode(
    $seven_day_values
);
?>;


const monthLabels =
<?php
echo json_encode(
    $month_labels,
    JSON_UNESCAPED_UNICODE
);
?>;


const incomeValues =
<?php
echo json_encode(
    $income_values
);
?>;


const expenseValues =
<?php
echo json_encode(
    $expense_values
);
?>;


/* =====================================================
   HABIT CHART
===================================================== */

new Chart(
    document.getElementById(
        "habitChart"
    ),
    {

        type: "line",

        data: {

            labels:
                sevenDayLabels,

            datasets: [

                {

                    label:
                        "Thói quen hoàn thành",

                    data:
                        sevenDayValues,

                    tension: 0.3,

                    fill: false

                }

            ]

        },


        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {

                        precision: 0

                    }

                }

            }

        }

    }
);


/* =====================================================
   FINANCE CHART
===================================================== */

new Chart(
    document.getElementById(
        "financeChart"
    ),
    {

        type: "bar",

        data: {

            labels:
                monthLabels,

            datasets: [

                {

                    label:
                        "Thu nhập",

                    data:
                        incomeValues

                },

                {

                    label:
                        "Chi tiêu",

                    data:
                        expenseValues

                }

            ]

        },


        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {

                    beginAtZero: true

                }

            }

        }

    }
);

</script>


</body>
</html>
