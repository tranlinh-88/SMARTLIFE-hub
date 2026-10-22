<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];

$id =
    isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


/* LẤY THÓI QUEN */

$sql = "SELECT *
        FROM habits
        WHERE id = ?
        AND user_id = ?";


$stmt =
    $conn->prepare($sql);


$stmt->bind_param(
    "ii",
    $id,
    $user_id
);


$stmt->execute();

$result =
    $stmt->get_result();


if ($result->num_rows !== 1) {

    header("Location: index.php");
    exit();

}


$habit =
    $result->fetch_assoc();


$message = "";


/* UPDATE */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $token =
        $_POST["csrf_token"] ?? "";


    if (!verify_csrf($token)) {

        http_response_code(403);
        die("Yêu cầu không hợp lệ.");

    }


    $habit_name =
        trim(
            $_POST["habit_name"] ?? ""
        );


    $description =
        trim(
            $_POST["description"] ?? ""
        );


    $target_days =
        (int)(
            $_POST["target_days"] ?? 30
        );


    if (
        $habit_name == ""
        ||
        $target_days < 1
        ||
        $target_days > 365
    ) {

        $message =
            "Thông tin không hợp lệ.";

    }

    else {


        $sql = "UPDATE habits

                SET habit_name = ?,
                    description = ?,
                    target_days = ?

                WHERE id = ?
                AND user_id = ?";


        $stmt =
            $conn->prepare($sql);


        $stmt->bind_param(
            "ssiii",
            $habit_name,
            $description,
            $target_days,
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
            "Không thể cập nhật.";

    }

}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<title>Sửa thói quen</title>

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

<h1>✏️ Sửa thói quen</h1>

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

<label>Tên thói quen</label>

<input
    type="text"
    name="habit_name"
    value="<?php echo htmlspecialchars($habit["habit_name"]); ?>"
    required
>

</div>


<div class="form-group">

<label>Mô tả</label>

<textarea
    name="description"
><?php echo htmlspecialchars($habit["description"] ?? ""); ?></textarea>

</div>


<div class="form-group">

<label>Mục tiêu số ngày</label>

<input
    type="number"
    name="target_days"
    min="1"
    max="365"
    value="<?php echo (int)$habit["target_days"]; ?>"
    required
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