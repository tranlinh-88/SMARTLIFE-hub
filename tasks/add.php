<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$message = "";

$user_id = $_SESSION["user_id"];


if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* KIỂM TRA CSRF */

    $token = $_POST["csrf_token"] ?? "";

    if (!verify_csrf($token)) {

        http_response_code(403);
        die("Yêu cầu không hợp lệ.");

    }


    /* LẤY DỮ LIỆU */

    $title = trim($_POST["title"] ?? "");

    $description =
        trim($_POST["description"] ?? "");

    $priority =
        $_POST["priority"] ?? "Trung bình";

    $deadline =
        $_POST["deadline"] ?? "";

    $status =
        $_POST["status"] ?? "Chưa hoàn thành";


    /* VALIDATE */

    if ($title == "") {

        $message =
            "Vui lòng nhập tên công việc.";

    } else {


        if ($deadline == "") {
            $deadline = null;
        }


        $sql = "INSERT INTO tasks
                (
                    user_id,
                    title,
                    description,
                    priority,
                    deadline,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?)";


        $stmt = $conn->prepare($sql);


        $stmt->bind_param(
            "isssss",
            $user_id,
            $title,
            $description,
            $priority,
            $deadline,
            $status
        );


        if ($stmt->execute()) {

            header(
                "Location: index.php?success=add"
            );

            exit();

        } else {

            $message =
                "Không thể thêm công việc.";

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

    <title>Thêm công việc</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>


<body class="dashboard-body">


<div
    class="dashboard-main"
    style="margin-left: 0;"
>


    <div class="page-header">

        <div>

            <h1>➕ Thêm công việc</h1>

            <p>
                Tạo công việc mới cho kế hoạch của bạn.
            </p>

        </div>


        <a
            href="index.php"
            class="btn-view"
        >
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


            <!-- CSRF -->

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars(csrf_token()); ?>"
            >


            <!-- TITLE -->

            <div class="form-group">

                <label>Tên công việc *</label>

                <input
                    type="text"
                    name="title"
                    placeholder="Ví dụ: Hoàn thành bài tập PHP"
                    required
                >

            </div>


            <!-- DESCRIPTION -->

            <div class="form-group">

                <label>Mô tả</label>

                <textarea
                    name="description"
                    placeholder="Mô tả công việc..."
                ></textarea>

            </div>


            <!-- PRIORITY -->

            <div class="form-group">

                <label>Mức ưu tiên</label>

                <select name="priority">

                    <option value="Cao">
                        🔴 Cao
                    </option>

                    <option
                        value="Trung bình"
                        selected
                    >
                        🟡 Trung bình
                    </option>

                    <option value="Thấp">
                        🟢 Thấp
                    </option>

                </select>

            </div>


            <!-- DEADLINE -->

            <div class="form-group">

                <label>Deadline</label>

                <input
                    type="datetime-local"
                    name="deadline"
                >

            </div>


            <!-- STATUS -->

            <div class="form-group">

                <label>Trạng thái</label>

                <select name="status">

                    <option
                        value="Chưa hoàn thành"
                        selected
                    >
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
                Thêm công việc
            </button>


        </form>


    </div>


</div>


</body>
</html>