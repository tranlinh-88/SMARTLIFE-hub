<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];

$message = "";


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
            $_POST["target_days"]
            ?? 30
        );


    if ($habit_name == "") {

        $message =
            "Vui lòng nhập tên thói quen.";

    }

    elseif (
        $target_days < 1
        ||
        $target_days > 365
    ) {

        $message =
            "Mục tiêu ngày phải từ 1 đến 365.";

    }

    else {


        $sql = "INSERT INTO habits
                (
                    user_id,
                    habit_name,
                    description,
                    target_days
                )

                VALUES (?, ?, ?, ?)";


        $stmt =
            $conn->prepare($sql);


        $stmt->bind_param(
            "issi",
            $user_id,
            $habit_name,
            $description,
            $target_days
        );


        if ($stmt->execute()) {

            header(
                "Location: index.php?success=add"
            );

            exit();

        }


        $message =
            "Không thể thêm thói quen.";

    }

}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<title>Thêm thói quen</title>

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
            🔥 Thêm thói quen
        </h1>

        <p>
            Tạo thói quen mới
            để theo dõi mỗi ngày.
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
        Tên thói quen *
    </label>

    <input
        type="text"
        name="habit_name"
        placeholder="Ví dụ: Tập thể dục"
        required
    >

</div>


<div class="form-group">

    <label>
        Mô tả
    </label>

    <textarea
        name="description"
        placeholder="Ví dụ: Tập 30 phút mỗi ngày"
    ></textarea>

</div>


<div class="form-group">

    <label>
        Mục tiêu số ngày *
    </label>

    <input
        type="number"
        name="target_days"
        min="1"
        max="365"
        value="30"
        required
    >

</div>


<button
    type="submit"
    class="btn-primary"
>
    + Thêm thói quen
</button>


</form>

</div>

</div>

</body>
</html>