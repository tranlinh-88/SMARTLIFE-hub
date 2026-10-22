<?php

include "../includes/auth.php";
include "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION["user_id"];

    $subject_name = trim($_POST["subject_name"]);
    $teacher = trim($_POST["teacher"]);
    $semester = trim($_POST["semester"]);

    if ($subject_name == "") {

        $message = "Vui lòng nhập tên môn học.";

    } else {

        $sql = "INSERT INTO subjects
                (user_id, subject_name, teacher, semester)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "isss",
            $user_id,
            $subject_name,
            $teacher,
            $semester
        );

        if ($stmt->execute()) {

            header("Location: index.php?success=add");
            exit();

        } else {

            $message = "Không thể thêm môn học.";

        }
    }
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

    <title>Thêm môn học</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
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

        <a href="index.php" class="active">
            📚 Học tập
        </a>

    </div>

</div>


<div class="dashboard-main">

    <div class="page-header">

        <div>

            <h1>➕ Thêm môn học</h1>

            <p>
                Thêm môn học mới vào SmartLife.
            </p>

        </div>

        <a href="index.php" class="btn-view">
            ← Quay lại
        </a>

    </div>


    <div class="form-container">

        <?php if ($message != ""): ?>

            <div class="error-message">

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <form method="POST">

            <div class="form-group">

                <label>Tên môn học *</label>

                <input
                    type="text"
                    name="subject_name"
                    placeholder="Ví dụ: Lập trình Web"
                    required
                >

            </div>


            <div class="form-group">

                <label>Giảng viên</label>

                <input
                    type="text"
                    name="teacher"
                    placeholder="Tên giảng viên"
                >

            </div>


            <div class="form-group">

                <label>Học kỳ</label>

                <input
                    type="text"
                    name="semester"
                    placeholder="Ví dụ: Học kỳ 1"
                >

            </div>


            <button
                type="submit"
                class="btn-primary"
            >
                Thêm môn học
            </button>

        </form>

    </div>

</div>

</body>
</html>