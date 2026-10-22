<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";


if (
    $_SERVER["REQUEST_METHOD"]
    !== "POST"
) {

    header(
        "Location: ../expenses/index.php"
    );

    exit();

}


$token =
    $_POST["csrf_token"] ?? "";


if (!verify_csrf($token)) {

    http_response_code(403);

    die(
        "Yêu cầu không hợp lệ."
    );

}


$id =
    isset($_POST["id"])
    ? (int)$_POST["id"]
    : 0;


$user_id =
    $_SESSION["user_id"];


if ($id <= 0) {

    header(
        "Location: ../expenses/index.php"
    );

    exit();

}


$sql = "DELETE FROM incomes
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


$month = $_POST["month"] ?? date("Y-m");


if (!preg_match('/^\d{4}-\d{2}$/', $month)) {

    $month = date("Y-m");

}


header(
    "Location: ../expenses/index.php?month="
    . urlencode($month)
    . "&success=income_delete"
);

exit();

?>