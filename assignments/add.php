<?php

include "../includes/auth.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];


if (!isset($_GET["subject_id"])) {

    header("Location: ../subjects/index.php");
    exit();

}

$subject_id = (int) $_GET["subject_id"];


/* Kiểm tra môn học có thuộc user không */

$sql = "SELECT id, subject_name
        FROM subjects
        WHERE id = ?
        AND user_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $subject_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows != 1) {

    header("Location: ../subjects/index.php");
    exit();

}

$subject = $result->fetch_assoc();

$message = "";


/* THÊM BÀI TẬP */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);

    $description =
        trim($_POST["description"]);

    $deadline = $_POST["deadline"];

    $status = $_POST["status"];


    if ($title == "") {

        $message =
            "Vui lòng nhập tên bài tập.";

    } else {

        if ($deadline == "") {
            $deadline = null;
        }


        $sql = "INSERT INTO assignments
                (
                    subject_id,
                    title,
                    description,
                    deadline,
                    status
                )
                VALUES (?, ?, ?, ?, ?)";


        $stmt = $conn->prepare($sql);


        $stmt->bind_param(
            "issss",
            $subject_id,
            $title,
            $description,
            $deadline,
            $status
        );


        if ($stmt->execute()) {

            header(
                "Location: ../subjects/detail.php?id="
                . $subject_id
                . "&success=add"
            );

            exit();

        } else {

            $message =
                "Không thể thêm bài tập.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Thêm bài tập</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body class="dashboard-body">


<div class="dashboard-main" style="margin-left: 0;">


    <div class="page-header">

        <div>

            <h1>📝 Thêm bài tập</h1>

            <p>

                Môn:

                <?php
                echo htmlspecialchars(
                    $subject["subject_name"]
                );
                ?>

            </p>

        </div>


        <a
            href="../subjects/detail.php?id=<?php echo $subject_id; ?>"
            class="btn-view"
        >
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

                <label>Tên bài tập *</label>

                <input
                    type="text"
                    name="title"
                    placeholder="Ví dụ: Bài tập PHP CRUD"
                    required
                >

            </div>


            <div class="form-group">

                <label>Mô tả</label>

                <textarea
                    name="description"
                    placeholder="Mô tả bài tập..."
                ></textarea>

            </div>


            <div class="form-group">

                <label>Deadline</label>

                <input
                    type="datetime-local"
                    name="deadline"
                >

            </div>


            <div class="form-group">

                <label>Trạng thái</label>

                <select name="status">

                    <option value="Chưa hoàn thành">
                        Chưa hoàn thành
                    </option>

                    <option value="Đang thực hiện">
                        Đang thực hiện
                    </option>

                    <option value="Hoàn thành">
                        Hoàn thành
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="btn-primary"
            >
                Thêm bài tập
            </button>


        </form>

    </div>

</div>

</body>
</html>