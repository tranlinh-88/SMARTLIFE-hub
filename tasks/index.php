<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];


/* =====================================================
   LẤY THAM SỐ TÌM KIẾM / LỌC
===================================================== */

$keyword = trim($_GET["keyword"] ?? "");
$status = trim($_GET["status"] ?? "");
$priority = trim($_GET["priority"] ?? "");


/* =====================================================
   TẠO CÂU SQL
===================================================== */

$sql = "SELECT *
        FROM tasks
        WHERE user_id = ?";

$params = [$user_id];
$types = "i";


/* TÌM KIẾM */

if ($keyword != "") {

    $sql .= " AND (
                title LIKE ?
                OR description LIKE ?
              )";

    $search_keyword = "%" . $keyword . "%";

    $params[] = $search_keyword;
    $params[] = $search_keyword;

    $types .= "ss";
}


/* LỌC TRẠNG THÁI */

if ($status != "") {

    $sql .= " AND status = ?";

    $params[] = $status;
    $types .= "s";
}


/* LỌC MỨC ƯU TIÊN */

if ($priority != "") {

    $sql .= " AND priority = ?";

    $params[] = $priority;
    $types .= "s";
}


$sql .= " ORDER BY
            CASE priority
                WHEN 'Cao' THEN 1
                WHEN 'Trung bình' THEN 2
                WHEN 'Thấp' THEN 3
                ELSE 4
            END,
            deadline ASC";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$tasks = $stmt->get_result();


/* =====================================================
   THỐNG KÊ
===================================================== */

$sql_stats = "
    SELECT

        COUNT(*) AS total,

        SUM(
            CASE
                WHEN status = 'Chưa hoàn thành'
                THEN 1
                ELSE 0
            END
        ) AS not_started,

        SUM(
            CASE
                WHEN status = 'Đang thực hiện'
                THEN 1
                ELSE 0
            END
        ) AS doing,

        SUM(
            CASE
                WHEN status = 'Hoàn thành'
                THEN 1
                ELSE 0
            END
        ) AS completed

    FROM tasks

    WHERE user_id = ?
";


$stmt_stats = $conn->prepare($sql_stats);

$stmt_stats->bind_param(
    "i",
    $user_id
);

$stmt_stats->execute();

$stats = $stmt_stats
    ->get_result()
    ->fetch_assoc();


$total = (int)($stats["total"] ?? 0);
$not_started = (int)($stats["not_started"] ?? 0);
$doing = (int)($stats["doing"] ?? 0);
$completed = (int)($stats["completed"] ?? 0);

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Công việc - SmartLife Hub</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

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

        <a href="../dashboard/index.php">
            🏠 Dashboard
        </a>

        <a href="../subjects/index.php">
            📚 Học tập
        </a>

        <a href="index.php" class="active">
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


    <div class="page-header">

        <div>

            <h1>✅ Quản lý công việc</h1>

            <p>
                Quản lý công việc, deadline và mức độ ưu tiên.
            </p>

        </div>


        <a
            href="add.php"
            class="btn-primary"
        >
            + Thêm công việc
        </a>

    </div>


    <!-- THÔNG BÁO -->

    <?php if (isset($_GET["success"])): ?>

        <div class="success-message">

            <?php

            if ($_GET["success"] == "add") {
                echo "Thêm công việc thành công!";
            }

            elseif ($_GET["success"] == "edit") {
                echo "Cập nhật công việc thành công!";
            }

            elseif ($_GET["success"] == "complete") {
                echo "Đã hoàn thành công việc!";
            }

            elseif ($_GET["success"] == "delete") {
                echo "Xóa công việc thành công!";
            }

            ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         THỐNG KÊ
    ================================================== -->

    <div class="task-stats">

        <div class="task-stat-card">

            <span>📋</span>

            <div>
                <p>Tổng công việc</p>
                <h3><?php echo $total; ?></h3>
            </div>

        </div>


        <div class="task-stat-card">

            <span>⏳</span>

            <div>
                <p>Chưa hoàn thành</p>
                <h3><?php echo $not_started; ?></h3>
            </div>

        </div>


        <div class="task-stat-card">

            <span>🚀</span>

            <div>
                <p>Đang thực hiện</p>
                <h3><?php echo $doing; ?></h3>
            </div>

        </div>


        <div class="task-stat-card">

            <span>✅</span>

            <div>
                <p>Hoàn thành</p>
                <h3><?php echo $completed; ?></h3>
            </div>

        </div>

    </div>


    <!-- =================================================
         TÌM KIẾM + LỌC
    ================================================== -->

    <div class="filter-box">

        <form method="GET">

            <div class="filter-grid">


                <div class="form-group">

                    <label>Tìm kiếm</label>

                    <input
                        type="text"
                        name="keyword"
                        placeholder="Tên công việc..."
                        value="<?php echo htmlspecialchars($keyword); ?>"
                    >

                </div>


                <div class="form-group">

                    <label>Trạng thái</label>

                    <select name="status">

                        <option value="">
                            Tất cả trạng thái
                        </option>

                        <option
                            value="Chưa hoàn thành"
                            <?php
                            if ($status == "Chưa hoàn thành")
                                echo "selected";
                            ?>
                        >
                            Chưa hoàn thành
                        </option>

                        <option
                            value="Đang thực hiện"
                            <?php
                            if ($status == "Đang thực hiện")
                                echo "selected";
                            ?>
                        >
                            Đang thực hiện
                        </option>

                        <option
                            value="Hoàn thành"
                            <?php
                            if ($status == "Hoàn thành")
                                echo "selected";
                            ?>
                        >
                            Hoàn thành
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>Mức ưu tiên</label>

                    <select name="priority">

                        <option value="">
                            Tất cả mức độ
                        </option>

                        <option
                            value="Cao"
                            <?php
                            if ($priority == "Cao")
                                echo "selected";
                            ?>
                        >
                            Cao
                        </option>

                        <option
                            value="Trung bình"
                            <?php
                            if ($priority == "Trung bình")
                                echo "selected";
                            ?>
                        >
                            Trung bình
                        </option>

                        <option
                            value="Thấp"
                            <?php
                            if ($priority == "Thấp")
                                echo "selected";
                            ?>
                        >
                            Thấp
                        </option>

                    </select>

                </div>


                <div class="filter-actions">

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        🔍 Tìm
                    </button>

                    <a
                        href="index.php"
                        class="btn-reset"
                    >
                        Đặt lại
                    </a>

                </div>

            </div>

        </form>

    </div>


    <!-- =================================================
         DANH SÁCH
    ================================================== -->

    <div class="table-container">


        <?php if ($tasks->num_rows > 0): ?>


            <table class="data-table">

                <thead>

                    <tr>

                        <th>Công việc</th>

                        <th>Ưu tiên</th>

                        <th>Deadline</th>

                        <th>Trạng thái</th>

                        <th>Thao tác</th>

                    </tr>

                </thead>


                <tbody>


                <?php while ($task = $tasks->fetch_assoc()): ?>


                    <tr>


                        <!-- CÔNG VIỆC -->

                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $task["title"]
                                );
                                ?>
                            </strong>

                            <?php
                            if (!empty($task["description"])):
                            ?>

                                <br>

                                <small>
                                    <?php
                                    echo htmlspecialchars(
                                        $task["description"]
                                    );
                                    ?>
                                </small>

                            <?php endif; ?>

                        </td>


                        <!-- PRIORITY -->

                        <td>

                            <?php

                            $priority_class = "";

                            if ($task["priority"] == "Cao") {
                                $priority_class = "priority-high";
                            }

                            elseif ($task["priority"] == "Trung bình") {
                                $priority_class = "priority-medium";
                            }

                            else {
                                $priority_class = "priority-low";
                            }

                            ?>

                            <span class="<?php echo $priority_class; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $task["priority"]
                                );
                                ?>

                            </span>

                        </td>


                        <!-- DEADLINE -->

                        <td>

                            <?php

                                if ($task["deadline"]) {

                                    echo date(
                                        "d/m/Y H:i",
                                        strtotime($task["deadline"])
                                    );


                                    if (
                                        strtotime($task["deadline"]) < time()
                                        &&
                                        $task["status"] != "Hoàn thành"
                                    ) {

                                        echo '<br>
                                        <span class="overdue-text">
                                            ⚠ Quá hạn
                                        </span>';

                                    }

                                } else {

                                    echo "Không có";

                                }

                                ?>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <?php

                            $status_class = "";

                            if (
                                $task["status"]
                                == "Hoàn thành"
                            ) {

                                $status_class =
                                    "task-completed";

                            }

                            elseif (
                                $task["status"]
                                == "Đang thực hiện"
                            ) {

                                $status_class =
                                    "task-doing";

                            }

                            else {

                                $status_class =
                                    "task-pending";

                            }

                            ?>


                            <span class="<?php echo $status_class; ?>">

                                <?php
                                echo htmlspecialchars(
                                    $task["status"]
                                );
                                ?>

                            </span>

                        </td>


                        <!-- ACTION -->

                        <td>

                            <div class="task-actions">


                                <?php
                                if (
                                    $task["status"]
                                    != "Hoàn thành"
                                ):
                                ?>


                                    <form
                                        method="POST"
                                        action="complete.php"
                                        class="inline-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo (int)$task["id"]; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars(csrf_token()); ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn-complete"
                                            title="Hoàn thành"
                                        >
                                            ✓
                                        </button>

                                    </form>


                                <?php endif; ?>


                                <a
                                    href="edit.php?id=<?php echo (int)$task["id"]; ?>"
                                    class="btn-edit"
                                >
                                    Sửa
                                </a>


                                <form
                                    method="POST"
                                    action="delete.php"
                                    class="inline-form"

                                    onsubmit="
                                    return confirm(
                                    'Bạn có chắc muốn xóa công việc này?'
                                    );
                                    "
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?php echo (int)$task["id"]; ?>"
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

                        </td>


                    </tr>


                <?php endwhile; ?>


                </tbody>

            </table>


        <?php else: ?>


            <div class="empty-box">

                <h3>📋 Không tìm thấy công việc</h3>

                <p>
                    Hãy thêm công việc mới hoặc thay đổi bộ lọc.
                </p>

            </div>


        <?php endif; ?>


    </div>


</div>


</body>
</html>