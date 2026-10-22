<?php

include "../includes/auth.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];


if (!isset($_GET["id"])) {

    header("Location: ../subjects/index.php");
    exit();

}

$id = (int) $_GET["id"];


/* Lấy bài tập và kiểm tra chủ sở hữu */

$sql = "SELECT
            a.*,
            s.user_id
        FROM assignments a

        JOIN subjects s
            ON a.subject_id = s.id

        WHERE a.id = ?
        AND s.user_id = ?";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows != 1) {

    header("Location: ../subjects/index.php");
    exit();

}

$assignment = $result->fetch_assoc();

$subject_id = $assignment["subject_id"];

$message = "";


/* UPDATE */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST["title"]);

    $description =
        trim($_POST["description"]);

    $deadline = $_POST["deadline"];

    $status = $_POST["status"];


    if ($deadline == "") {
        $deadline = null;
    }


    if ($title == "") {

        $message =
            "Tên bài tập không được để trống.";

    } else {

        $sql = "UPDATE assignments
                SET title = ?,
                    description = ?,
                    deadline = ?,
                    status = ?
                WHERE id = ?";


        $stmt = $conn->prepare($sql);


        $stmt->bind_param(
            "ssssi",
            $title,
            $description,
            $deadline,
            $status,
            $id
        );


        if ($stmt->execute()) {

            header(
                "Location: ../subjects/detail.php?id="
                . $subject_id
                . "&success=edit"
            );

            exit();

        } else {

            $message =
                "Không thể cập nhật bài tập.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Sửa bài tập</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body class="dashboard-body">


<div class="dashboard-main" style="margin-left: 0;">


    <div class="page-header">

        <h1>✏️ Sửa bài tập</h1>


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

                <label>Tên bài tập</label>

                <input
                    type="text"
                    name="title"
                    value="<?php
                    echo htmlspecialchars(
                        $assignment["title"]
                    );
                    ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>Mô tả</label>

                <textarea
                    name="description"
                ><?php
                echo htmlspecialchars(
                    $assignment["description"] ?? ""
                );
                ?></textarea>

            </div>


            <div class="form-group">

                <label>Deadline</label>

                <input
                    type="datetime-local"
                    name="deadline"
                    value="<?php

                    if ($assignment["deadline"]) {

                        echo date(
                            "Y-m-d\TH:i",
                            strtotime(
                                $assignment["deadline"]
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
                            $assignment["status"]
                            == "Chưa hoàn thành"
                        ) echo "selected";
                        ?>
                    >
                        Chưa hoàn thành
                    </option>


                    <option
                        value="Đang thực hiện"
                        <?php
                        if (
                            $assignment["status"]
                            == "Đang thực hiện"
                        ) echo "selected";
                        ?>
                    >
                        Đang thực hiện
                    </option>


                    <option
                        value="Hoàn thành"
                        <?php
                        if (
                            $assignment["status"]
                            == "Hoàn thành"
                        ) echo "selected";
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