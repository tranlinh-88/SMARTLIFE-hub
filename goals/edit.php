<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];

$id =
    isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


$sql = "SELECT *
        FROM goals
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


$goal = $result->fetch_assoc();

$message = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $token =
        $_POST["csrf_token"] ?? "";


    if (!verify_csrf($token)) {

        http_response_code(403);
        die("Yêu cầu không hợp lệ.");

    }


    $title =
        trim($_POST["title"] ?? "");

    $description =
        trim(
            $_POST["description"] ?? ""
        );

    $target_value =
        $_POST["target_value"] ?? "";

    $current_value =
        $_POST["current_value"] ?? "";

    $deadline =
        $_POST["deadline"] ?? "";


    if ($deadline == "") {
        $deadline = null;
    }


    if (
        $title == ""
        ||
        !is_numeric($target_value)
        ||
        $target_value <= 0
        ||
        !is_numeric($current_value)
        ||
        $current_value < 0
    ) {

        $message =
            "Thông tin không hợp lệ.";

    }

    else {


        if (
            $current_value
            > $target_value
        ) {

            $current_value =
                $target_value;

        }


        $status =
            $current_value >= $target_value
            ? "Hoàn thành"
            : "Đang thực hiện";


        $sql = "UPDATE goals

                SET title = ?,
                    description = ?,
                    target_value = ?,
                    current_value = ?,
                    deadline = ?,
                    status = ?

                WHERE id = ?
                AND user_id = ?";


        $stmt =
            $conn->prepare($sql);


        $stmt->bind_param(
            "ssddssii",
            $title,
            $description,
            $target_value,
            $current_value,
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

        }


        $message =
            "Không thể cập nhật mục tiêu.";

    }

}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<title>Sửa mục tiêu</title>

<link
    rel="stylesheet"
    href="../assets/css/style.css"
>

</head>


<body class="dashboard-body">


<div
    class="dashboard-main"
    style="margin-left:0;"
>


<div class="page-header">

<h1>
    ✏️ Sửa mục tiêu
</h1>

<a
    href="index.php"
    class="btn-view"
>
    ← Quay lại
</a>

</div>


<div class="form-container">


<?php if ($message): ?>

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

<label>Tên mục tiêu</label>

<input
    type="text"
    name="title"
    value="<?php echo htmlspecialchars($goal["title"]); ?>"
    required
>

</div>


<div class="form-group">

<label>Mô tả</label>

<textarea
    name="description"
><?php echo htmlspecialchars($goal["description"] ?? ""); ?></textarea>

</div>


<div class="form-group">

<label>Giá trị mục tiêu</label>

<input
    type="number"
    name="target_value"
    min="1"
    step="1"
    value="<?php echo htmlspecialchars($goal["target_value"]); ?>"
    required
>

</div>


<div class="form-group">

<label>Tiến độ hiện tại</label>

<input
    type="number"
    name="current_value"
    min="0"
    step="1"
    value="<?php echo htmlspecialchars($goal["current_value"]); ?>"
    required
>

</div>


<div class="form-group">

<label>Deadline</label>

<input
    type="date"
    name="deadline"
    value="<?php echo htmlspecialchars($goal["deadline"] ?? ""); ?>"
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