<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];

$sql = "SELECT *
        FROM subjects
        WHERE user_id = ?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Học tập - SmartLife Hub</title>

    <link rel="stylesheet" href="../assets/css/style.css">

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

        <a href="index.php" class="active">
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


<!-- MAIN -->

<div class="dashboard-main">

    <div class="page-header">

        <div>
            <h1>📚 Quản lý học tập</h1>
            <p>Quản lý môn học và bài tập của bạn.</p>
        </div>

        <a href="add.php" class="btn-primary">
            + Thêm môn học
        </a>

    </div>


    <?php if (isset($_GET["success"])): ?>

        <div class="success-message">

            <?php

            if ($_GET["success"] == "add") {
                echo "Thêm môn học thành công!";
            }

            if ($_GET["success"] == "edit") {
                echo "Cập nhật môn học thành công!";
            }

            if ($_GET["success"] == "delete") {
                echo "Xóa môn học thành công!";
            }

            ?>

        </div>

    <?php endif; ?>


    <div class="subject-grid">

        <?php if ($result->num_rows > 0): ?>

            <?php while ($subject = $result->fetch_assoc()): ?>

                <div class="subject-card">

                    <div class="subject-icon">
                        📖
                    </div>

                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $subject["subject_name"]
                        );
                        ?>
                    </h3>

                    <p>
                        👨‍🏫 Giảng viên:
                        <?php
                        echo htmlspecialchars(
                            $subject["teacher"] ?? ""
                        );
                        ?>
                    </p>

                    <p>
                        🎓 Học kỳ:
                        <?php
                        echo htmlspecialchars(
                            $subject["semester"] ?? ""
                        );
                        ?>
                    </p>

                    <div class="subject-actions">

                        <a
                            href="detail.php?id=<?php echo $subject["id"]; ?>"
                            class="btn-view"
                        >
                            Xem
                        </a>

                        <a
                            href="edit.php?id=<?php echo $subject["id"]; ?>"
                            class="btn-edit"
                        >
                            Sửa
                        </a>

                        <form
                            method="POST"
                            action="delete.php"
                            class="inline-form"
                            onsubmit="return confirm('Xóa môn học sẽ xóa luôn các bài tập thuộc môn này. Bạn có chắc không?');"
                        >

                            <input
                                type="hidden"
                                name="id"
                                value="<?php echo (int)$subject["id"]; ?>"
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

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-box">

                <h3>📚 Chưa có môn học</h3>

                <p>
                    Hãy thêm môn học đầu tiên của bạn.
                </p>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>