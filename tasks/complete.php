<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit();

}


/* CSRF */

$token =
    $_POST["csrf_token"] ?? "";


if (!verify_csrf($token)) {

    http_response_code(403);

    die(
        "Yêu cầu không hợp lệ. CSRF token không đúng."
    );

}


/* ID */

$id =
    isset($_POST["id"])
    ? (int)$_POST["id"]
    : 0;


if ($id <= 0) {

    header("Location: index.php");
    exit();

}


$user_id =
    $_SESSION["user_id"];


/* =====================================================
   UPDATE
===================================================== */

$sql = "UPDATE tasks

        SET status = 'Hoàn thành'

        WHERE id = ?
        AND user_id = ?";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $id,
    $user_id
);

$stmt->execute();


if ($stmt->affected_rows > 0) {

    header(
        "Location: index.php?success=complete"
    );

} else {

    header(
        "Location: index.php"
    );

}


exit();

?>