<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];


/* =====================================================
   7 NGÀY GẦN NHẤT
===================================================== */

$days = [];

for ($i = 6; $i >= 0; $i--) {

    $date = date(
        "Y-m-d",
        strtotime("-$i days")
    );

    $days[] = $date;
}


/* =====================================================
   LẤY THÓI QUEN
===================================================== */

$sql = "SELECT *
        FROM habits
        WHERE user_id = ?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();


$habits = [];

while ($row = $result->fetch_assoc()) {

    $habit_id = $row["id"];


    /* ===============================================
       LẤY LOG CỦA THÓI QUEN
    =============================================== */

    $sql_log = "SELECT log_date, status
                FROM habit_logs
                WHERE habit_id = ?
                AND status = 1
                ORDER BY log_date DESC";

    $stmt_log =
        $conn->prepare($sql_log);

    $stmt_log->bind_param(
        "i",
        $habit_id
    );

    $stmt_log->execute();

    $log_result =
        $stmt_log->get_result();


    $log_dates = [];

    while (
        $log =
        $log_result->fetch_assoc()
    ) {

        $log_dates[] =
            $log["log_date"];
    }


    /* ===============================================
       TỔNG NGÀY ĐÃ HOÀN THÀNH
    =============================================== */

    $completed_days =
        count($log_dates);


    /* ===============================================
       TỶ LỆ HOÀN THÀNH
    =============================================== */

    $target_days =
        (int)$row["target_days"];


    if ($target_days > 0) {

        $percentage =
            ($completed_days /
            $target_days) * 100;

    } else {

        $percentage = 0;

    }


    $percentage =
        min(100, $percentage);


    /* ===============================================
       STREAK
    =============================================== */

    $streak = 0;

    $check_date =
        date("Y-m-d");


    /*
       Nếu hôm nay chưa check-in nhưng
       hôm qua có check-in thì streak vẫn
       được tính từ hôm qua.
    */

    if (
        !in_array(
            $check_date,
            $log_dates
        )
    ) {

        $check_date =
            date(
                "Y-m-d",
                strtotime("-1 day")
            );

    }


    while (
        in_array(
            $check_date,
            $log_dates
        )
    ) {

        $streak++;

        $check_date =
            date(
                "Y-m-d",
                strtotime(
                    $check_date
                    . " -1 day"
                )
            );

    }


    $row["completed_days"] =
        $completed_days;

    $row["percentage"] =
        $percentage;

    $row["streak"] =
        $streak;

    $row["log_dates"] =
        $log_dates;


    $habits[] = $row;

}

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
        Thói quen - SmartLife
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body class="dashboard-body">


<!-- SIDEBAR -->

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

        <a
            href="index.php"
            class="active"
        >
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


<div class="dashboard-main">


    <!-- HEADER -->

    <div class="page-header">

        <div>

            <h1>
                🔥 Theo dõi thói quen
            </h1>

            <p>
                Xây dựng thói quen tốt
                mỗi ngày.
            </p>

        </div>

        <a
            href="add.php"
            class="btn-primary"
        >
            + Thêm thói quen
        </a>

    </div>


    <?php if (isset($_GET["success"])): ?>

        <div class="success-message">

            <?php

            switch ($_GET["success"]) {

                case "add":
                    echo "Thêm thói quen thành công!";
                    break;

                case "edit":
                    echo "Cập nhật thói quen thành công!";
                    break;

                case "check":
                    echo "Check-in hôm nay thành công! 🔥";
                    break;

                case "uncheck":
                    echo "Đã bỏ check-in hôm nay.";
                    break;

                case "delete":
                    echo "Xóa thói quen thành công!";
                    break;

            }

            ?>

        </div>

    <?php endif; ?>


    <?php if (count($habits) > 0): ?>


        <div class="habit-grid">


        <?php foreach ($habits as $habit): ?>


            <div class="habit-card">


                <div class="habit-card-header">

                    <div>

                        <h2>

                            🔥
                            <?php
                            echo htmlspecialchars(
                                $habit["habit_name"]
                            );
                            ?>

                        </h2>

                        <p>

                            <?php
                            echo htmlspecialchars(
                                $habit[
                                    "description"
                                ] ?? ""
                            );
                            ?>

                        </p>

                    </div>


                    <div class="habit-menu">

                        <a
                            href="edit.php?id=<?php echo (int)$habit["id"]; ?>"
                            class="btn-edit"
                        >
                            Sửa
                        </a>


                        <form
                            method="POST"
                            action="delete.php"
                            class="inline-form"
                            onsubmit="return confirm('Bạn có chắc muốn xóa thói quen này?');"
                        >

                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo (int)$habit["id"]; ?>"
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


                <!-- STATS -->

                <div class="habit-stats">

                    <div>

                        <strong>
                            🔥
                            <?php
                            echo $habit["streak"];
                            ?>
                        </strong>

                        <span>
                            Streak
                        </span>

                    </div>


                    <div>

                        <strong>
                            <?php
                            echo $habit[
                                "completed_days"
                            ];
                            ?>
                        </strong>

                        <span>
                            Đã hoàn thành
                        </span>

                    </div>


                    <div>

                        <strong>
                            <?php
                            echo $habit[
                                "target_days"
                            ];
                            ?>
                        </strong>

                        <span>
                            Mục tiêu ngày
                        </span>

                    </div>

                </div>


                <!-- 7 NGÀY -->

                <div class="habit-week">

                    <?php foreach ($days as $day): ?>


                        <?php

                        $checked =
                            in_array(
                                $day,
                                $habit[
                                    "log_dates"
                                ]
                            );

                        ?>


                        <div class="habit-day">

                            <small>

                                <?php

                                $weekdays = [
                                    1 => "T2",
                                    2 => "T3",
                                    3 => "T4",
                                    4 => "T5",
                                    5 => "T6",
                                    6 => "T7",
                                    7 => "CN"
                                ];

                                echo $weekdays[
                                    date(
                                        "N",
                                        strtotime($day)
                                    )
                                ];

                                ?>

                            </small>


                            <div
                                class="
                                habit-circle
                                <?php
                                echo $checked
                                    ? "checked"
                                    : "";
                                ?>
                                "
                            >

                                <?php
                                echo $checked
                                    ? "✓"
                                    : date(
                                        "d",
                                        strtotime($day)
                                    );
                                ?>

                            </div>

                        </div>


                    <?php endforeach; ?>

                </div>


                <!-- PROGRESS -->

                <div class="habit-progress-info">

                    <span>
                        Tiến độ
                    </span>

                    <strong>

                        <?php
                        echo round(
                            $habit["percentage"]
                        );
                        ?>%

                    </strong>

                </div>


                <div class="progress">

                    <div
                        class="progress-bar"
                        style="width:
                        <?php
                        echo $habit[
                            "percentage"
                        ];
                        ?>%;"
                    ></div>

                </div>


                <!-- CHECK TODAY -->

                <div class="habit-check-area">


                    <?php

                    $today =
                        date("Y-m-d");

                    $today_checked =
                        in_array(
                            $today,
                            $habit[
                                "log_dates"
                            ]
                        );

                    ?>


                    <?php if (!$today_checked): ?>


                        <form
                            method="POST"
                            action="check.php"
                        >

                            <input
                                type="hidden"
                                name="habit_id"
                                value="<?php echo (int)$habit["id"]; ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php echo htmlspecialchars(csrf_token()); ?>"
                            >

                            <button
                                type="submit"
                                class="habit-check-button"
                            >
                                ✓ Check-in hôm nay
                            </button>

                        </form>


                    <?php else: ?>


                        <div class="habit-done">

                            ✅ Hôm nay đã hoàn thành

                        </div>


                        <form
                            method="POST"
                            action="uncheck.php"
                        >

                            <input
                                type="hidden"
                                name="habit_id"
                                value="<?php echo (int)$habit["id"]; ?>"
                            >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php echo htmlspecialchars(csrf_token()); ?>"
                            >

                            <button
                                type="submit"
                                class="habit-uncheck-button"
                            >
                                Bỏ check-in
                            </button>

                        </form>


                    <?php endif; ?>


                </div>


            </div>


        <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="empty-box">

            <h2>
                🔥 Chưa có thói quen
            </h2>

            <p>
                Hãy tạo thói quen đầu tiên
                của bạn.
            </p>

        </div>


    <?php endif; ?>


</div>

</body>
</html>