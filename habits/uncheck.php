<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit();

}


$token =
    $_POST["csrf_token"] ?? "";


if (!verify_csrf($token)) {

    http_response_code(403);

    die("Yêu cầu không hợp lệ.");

}


$habit_id =
    isset($_POST["habit_id"])
    ? (int)$_POST["habit_id"]
    : 0;


$user_id =
    $_SESSION["user_id"];


/* KIỂM TRA QUYỀN */

$sql = "SELECT id
        FROM habits
        WHERE id = ?
        AND user_id = ?";


$stmt =
    $conn->prepare($sql);


$stmt->bind_param(
    "ii",
    $habit_id,
    $user_id
);


$stmt->execute();


if (
    $stmt
    ->get_result()
    ->num_rows !== 1
) {

    header("Location: index.php");
    exit();

}


/* XÓA CHECK-IN HÔM NAY */

$today = date("Y-m-d");


$sql = "DELETE FROM habit_logs
        WHERE habit_id = ?
        AND log_date = ?";


$stmt =
    $conn->prepare($sql);


$stmt->bind_param(
    "is",
    $habit_id,
    $today
);


$stmt->execute();


header(
    "Location: index.php?success=uncheck"
);

exit();

?>