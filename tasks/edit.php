<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];


if (!isset($_GET["id"])) {

    header("Location: index.php");
    exit();

}


$id = (int)$_GET["id"];


/* =====================================================
   LẤY CÔNG VIỆC
===================================================== */

$sql = "SELECT *
        FROM tasks
        WHERE id = ?
        AND user_id = ?";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    header("Location: index.php");
    exit();

}


$task = $result->fetch_assoc();

$message = "";


/* =====================================================
   UPDATE
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* CSRF */

    $token =
        $_POST["csrf_token"] ?? "";


    if (!verify_csrf($token)) {

        http_response_code(403);

        die(
            "Yêu cầu không hợp lệ."
        );

    }


    $title =
        trim($_POST["title"] ?? "");

    $description =
        trim($_POST["description"] ?? "");

    $priority =
        $_POST["priority"] ?? "";

    $deadline =
        $_POST["deadline"] ?? "";

    $status =
        $_POST["status"] ?? "";


    if ($title == "") {

        $message =
            "Tên công việc không được để trống.";

    } else {


        if ($deadline == "") {
            $deadline = null;
        }


        $sql = "UPDATE tasks

                SET title = ?,
                    description = ?,
                    priority = ?,
                    deadline = ?,
                    status = ?

                WHERE id = ?
                AND user_id = ?";


        $stmt = $conn->prepare($sql);


        $stmt->bind_param(
            "sssssii",
            $title,
            $description,
            $priority,
            $deadline,
            $status,
            $id,
            $user_id
        );


        if ($stmt->execute()) {

            header(
                "Location: index.php?success=edit"
            );

            exit();

        } else {

            $message =
                "Không thể cập nhật công việc.";

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

    <title>Sửa công việc</title>

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

            <h1>✏️ Sửa công việc</h1>

            <p>
                Cập nhật thông tin công việc.
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


            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars(csrf_token()); ?>"
            >


            <div class="form-group">

                <label>Tên công việc *</label>

                <input
                    type="text"
                    name="title"
                    value="<?php echo htmlspecialchars($task["title"]); ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Mô tả</label>

                <textarea
                    name="description"
                ><?php echo htmlspecialchars($task["description"] ?? ""); ?></textarea>

            </div>


            <div class="form-group">

                <label>Mức ưu tiên</label>

                <select name="priority">


                    <option
                        value="Cao"
                        <?php
                        if ($task["priority"] == "Cao")
                            echo "selected";
                        ?>
                    >
                        🔴 Cao
                    </option>


                    <option
                        value="Trung bình"
                        <?php
                        if (
                            $task["priority"]
                            == "Trung bình"
                        )
                            echo "selected";
                        ?>
                    >
                        🟡 Trung bình
                    </option>


                    <option
                        value="Thấp"
                        <?php
                        if ($task["priority"] == "Thấp")
                            echo "selected";
                        ?>
                    >
                        🟢 Thấp
                    </option>


                </select>

            </div>


            <div class="form-group">

                <label>Deadline</label>

                <input
                    type="datetime-local"
                    name="deadline"

                    value="<?php

                    if ($task["deadline"]) {

                        echo date(
                            "Y-m-d\TH:i",
                            strtotime(
                                $task["deadline"]
                            )
                        );

                    }

                    ?>"
                >

            </div>


            <div class="form-group">

                <label>Trạng thái</label>

                <select name="status">


                    <option
                        value="Chưa hoàn thành"
                        <?php
                        if (
                            $task["status"]
                            == "Chưa hoàn thành"
                        )
                            echo "selected";
                        ?>
                    >
                        Chưa hoàn thành
                    </option>


                    <option
                        value="Đang thực hiện"
                        <?php
                        if (
                            $task["status"]
                            == "Đang thực hiện"
                        )
                            echo "selected";
                        ?>
                    >
                        Đang thực hiện
                    </option>


                    <option
                        value="Hoàn thành"
                        <?php
                        if (
                            $task["status"]
                            == "Hoàn thành"
                        )
                            echo "selected";
                        ?>
                    >
                        Hoàn thành
                    </option>


                </select>

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