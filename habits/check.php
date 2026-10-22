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


/* =====================================================
   KIỂM TRA THÓI QUEN THUỘC USER
===================================================== */

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


$result =
    $stmt->get_result();


if ($result->num_rows !== 1) {

    header("Location: index.php");
    exit();

}


/* =====================================================
   NGÀY HÔM NAY
===================================================== */

$today = date("Y-m-d");


/* =====================================================
   KIỂM TRA ĐÃ CHECK-IN CHƯA
===================================================== */

$sql = "SELECT id
        FROM habit_logs
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


$result =
    $stmt->get_result();


if ($result->num_rows === 0) {


    $sql = "INSERT INTO habit_logs
            (
                habit_id,
                log_date,
                status
            )

            VALUES (?, ?, 1)";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        "is",
        $habit_id,
        $today
    );


    $stmt->execute();

}


header(
    "Location: index.php?success=check"
);

exit();

?>