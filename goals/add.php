<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id =
    $_SESSION["user_id"];

$message = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $token =
        $_POST["csrf_token"] ?? "";


    if (!verify_csrf($token)) {

        http_response_code(403);

        die("Yêu cầu không hợp lệ.");

    }


    $title =
        trim(
            $_POST["title"] ?? ""
        );


    $description =
        trim(
            $_POST["description"] ?? ""
        );


    $target_value =
        $_POST["target_value"] ?? "";


    $current_value =
        $_POST["current_value"] ?? 0;


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
            "Thông tin mục tiêu không hợp lệ.";

    }

    else {


        /*
         * Không cho tiến độ ban đầu
         * lớn hơn mục tiêu.
         */

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


        $sql = "INSERT INTO goals
                (
                    user_id,
                    title,
                    description,
                    target_value,
                    current_value,
                    deadline,
                    status
                )

                VALUES (
                    ?, ?, ?, ?, ?, ?, ?
                )";


        $stmt =
            $conn->prepare($sql);


        $stmt->bind_param(
            "issddss",
            $user_id,
            $title,
            $description,
            $target_value,
            $current_value,
            $deadline,
            $status
        );


        if ($stmt->execute()) {

            header(
                "Location: index.php?success=add"
            );

            exit();

        }


        $message =
            "Không thể thêm mục tiêu.";

    }

}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<title>Thêm mục tiêu</title>

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

<div>

<h1>
    🎯 Thêm mục tiêu
</h1>

<p>
    Xác định mục tiêu và
    theo dõi tiến độ.
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

<label>
    Tên mục tiêu *
</label>

<input
    type="text"
    name="title"
    placeholder="Ví dụ: Hoàn thành SmartLife Hub"
    required
>

</div>


<div class="form-group">

<label>
    Mô tả
</label>

<textarea
    name="description"
    placeholder="Mô tả mục tiêu..."
></textarea>

</div>


<div class="form-group">

<label>
    Giá trị mục tiêu *
</label>

<input
    type="number"
    name="target_value"
    min="1"
    step="1"
    value="100"
    required
>

</div>


<div class="form-group">

<label>
    Tiến độ hiện tại
</label>

<input
    type="number"
    name="current_value"
    min="0"
    step="1"
    value="0"
>

</div>


<div class="form-group">

<label>
    Deadline
</label>

<input
    type="date"
    name="deadline"
>

</div>


<button
    type="submit"
    class="btn-primary"
>
    + Thêm mục tiêu
</button>


</form>

</div>

</div>

</body>
</html>