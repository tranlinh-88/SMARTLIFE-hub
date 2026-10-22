<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id =
    $_SESSION["user_id"];


$id =
    isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;


/* LẤY MỤC TIÊU */

$sql = "SELECT *
        FROM goals
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


$goal =
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


    $current_value =
        $_POST["current_value"] ?? "";


    if (
        !is_numeric($current_value)
        ||
        $current_value < 0
    ) {

        $message =
            "Tiến độ không hợp lệ.";

    }

    else {


        $target_value =
            (float)$goal["target_value"];


        if (
            $current_value
            > $target_value
        ) {

            $current_value =
                $target_value;

        }


        if (
            $current_value
            >= $target_value
        ) {

            $status =
                "Hoàn thành";

        } else {

            $status =
                "Đang thực hiện";

        }


        $sql = "UPDATE goals

                SET current_value = ?,
                    status = ?

                WHERE id = ?
                AND user_id = ?";


        $stmt =
            $conn->prepare($sql);


        $stmt->bind_param(
            "dsii",
            $current_value,
            $status,
            $id,
            $user_id
        );


        if ($stmt->execute()) {

            header(
                "Location: index.php?success=progress"
            );

            exit();

        }


        $message =
            "Không thể cập nhật tiến độ.";

    }

}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

<meta charset="UTF-8">

<title>Cập nhật tiến độ</title>

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
    📈 Cập nhật tiến độ
</h1>

<p>

<?php
echo htmlspecialchars(
    $goal["title"]
);
?>

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


<div class="goal-progress-current">

<p>
    Mục tiêu:
</p>

<h2>

<?php
echo number_format(
    $goal["target_value"],
    0,
    ",",
    "."
);
?>

</h2>

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
    max="<?php echo htmlspecialchars($goal["target_value"]); ?>"
    value="<?php echo htmlspecialchars($goal["current_value"]); ?>"
    required
>

</div>


<button
    type="submit"
    class="btn-primary"
>
    Cập nhật tiến độ
</button>


</form>

</div>

</div>

</body>
</html>