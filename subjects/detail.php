<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];

if (!isset($_GET["id"])) {

    header("Location: index.php");
    exit();

}

$subject_id = (int) $_GET["id"];


/* =========================================
   LẤY MÔN HỌC
========================================= */

$sql = "SELECT *
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

    header("Location: index.php");
    exit();

}

$subject = $result->fetch_assoc();


/* =========================================
   LẤY BÀI TẬP
========================================= */

$sql = "SELECT *
        FROM assignments
        WHERE subject_id = ?
        ORDER BY deadline ASC";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $subject_id
);

$stmt->execute();

$assignments = $stmt->get_result();

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
        <?php
        echo htmlspecialchars(
            $subject["subject_name"]
        );
        ?>
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body class="dashboard-body">


<div class="dashboard-main" style="margin-left: 0;">


    <div class="page-header">

        <div>

            <h1>
                📖
                <?php
                echo htmlspecialchars(
                    $subject["subject_name"]
                );
                ?>
            </h1>

            <p>

                Giảng viên:

                <?php
                echo htmlspecialchars(
                    $subject["teacher"] ?? ""
                );
                ?>

                |

                <?php
                echo htmlspecialchars(
                    $subject["semester"] ?? ""
                );
                ?>

            </p>

        </div>


        <div>

            <a
                href="index.php"
                class="btn-view"
            >
                ← Môn học
            </a>

            <a
                href="../assignments/add.php?subject_id=<?php echo $subject_id; ?>"
                class="btn-primary"
            >
                + Thêm bài tập
            </a>

        </div>

    </div>


    <?php if (isset($_GET["success"])): ?>

        <div class="success-message">

            Thao tác bài tập thành công!

        </div>

    <?php endif; ?>


    <div class="table-container">

        <h2 style="margin-bottom: 20px;">
            📝 Danh sách bài tập
        </h2>


        <?php if ($assignments->num_rows > 0): ?>


            <table class="data-table">

                <thead>

                    <tr>

                        <th>Tên bài tập</th>

                        <th>Deadline</th>

                        <th>Trạng thái</th>

                        <th>Thao tác</th>

                    </tr>

                </thead>


                <tbody>


                <?php
                while (
                    $assignment =
                    $assignments->fetch_assoc()
                ):
                ?>


                    <tr>

                        <td>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $assignment["title"]
                                );
                                ?>
                            </strong>

                            <br>

                            <small>
                                <?php
                                echo htmlspecialchars(
                                    $assignment["description"] ?? ""
                                );
                                ?>
                            </small>

                        </td>


                        <td>

                            <?php

                            if ($assignment["deadline"]) {

                                echo date(
                                    "d/m/Y H:i",
                                    strtotime(
                                        $assignment["deadline"]
                                    )
                                );

                            } else {

                                echo "Không có";

                            }

                            ?>

                        </td>


                        <td>

                            <?php
                            echo htmlspecialchars(
                                $assignment["status"]
                            );
                            ?>

                        </td>


                        <td>

                            <?php
                            if (
                                $assignment["status"]
                                != "Hoàn thành"
                            ):
                            ?>

                                <form
                                    method="POST"
                                    action="../assignments/complete.php"
                                    class="inline-form"
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?php echo (int)$assignment["id"]; ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo htmlspecialchars(csrf_token()); ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn-complete"
                                        title="Đánh dấu hoàn thành"
                                    >
                                        ✓
                                    </button>

                                </form>

                            <?php endif; ?>


                            <a
                                class="btn-edit"
                                href="../assignments/edit.php?id=<?php echo $assignment["id"]; ?>"
                            >
                                Sửa
                            </a>


                            <form
                                method="POST"
                                action="../assignments/delete.php"
                                class="inline-form"
                                onsubmit="return confirm('Bạn có chắc muốn xóa bài tập này?');"
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?php echo (int)$assignment["id"]; ?>"
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

                        </td>

                    </tr>


                <?php endwhile; ?>


                </tbody>

            </table>


        <?php else: ?>


            <div class="empty-box">

                <h3>📝 Chưa có bài tập</h3>

                <p>
                    Hãy thêm bài tập đầu tiên cho môn học này.
                </p>

            </div>


        <?php endif; ?>


    </div>

</div>

</body>
</html>