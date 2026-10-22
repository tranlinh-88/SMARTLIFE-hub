<?php

include "../includes/auth.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];

if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$id = (int) $_GET["id"];


/* LẤY MÔN HỌC */

$sql = "SELECT *
        FROM subjects
        WHERE id = ?
        AND user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {

    header("Location: index.php");
    exit();

}

$subject = $result->fetch_assoc();

$message = "";


/* UPDATE */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $subject_name = trim($_POST["subject_name"]);
    $teacher = trim($_POST["teacher"]);
    $semester = trim($_POST["semester"]);

    if ($subject_name == "") {

        $message = "Tên môn học không được để trống.";

    } else {

        $sql = "UPDATE subjects
                SET subject_name = ?,
                    teacher = ?,
                    semester = ?
                WHERE id = ?
                AND user_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssii",
            $subject_name,
            $teacher,
            $semester,
            $id,
            $user_id
        );

        if ($stmt->execute()) {

            header("Location: index.php?success=edit");
            exit();

        } else {

            $message = "Không thể cập nhật môn học.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Sửa môn học</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body class="dashboard-body">


<div class="dashboard-main" style="margin-left: 0;">

    <div class="page-header">

        <div>

            <h1>✏️ Sửa môn học</h1>

        </div>

        <a href="index.php" class="btn-view">
            ← Quay lại
        </a>

    </div>


    <div class="form-container">


        <?php if ($message != ""): ?>

            <div class="error-message">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-group">

                <label>Tên môn học</label>

                <input
                    type="text"
                    name="subject_name"
                    value="<?php
                    echo htmlspecialchars(
                        $subject["subject_name"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Giảng viên</label>

                <input
                    type="text"
                    name="teacher"
                    value="<?php
                    echo htmlspecialchars(
                        $subject["teacher"] ?? ""
                    );
                    ?>"
                >

            </div>


            <div class="form-group">

                <label>Học kỳ</label>

                <input
                    type="text"
                    name="semester"
                    value="<?php
                    echo htmlspecialchars(
                        $subject["semester"] ?? ""
                    );
                    ?>"
                >

            </div>


            <button
                type="submit"
                class="btn-primary"
            >
                Lưu thay đổi
            </button>


        </form>

    </div>

</div>

</body>
</html>