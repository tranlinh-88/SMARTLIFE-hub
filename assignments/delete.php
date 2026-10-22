<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../subjects/index.php");
    exit();

}


// ===============================
// KIỂM TRA CSRF
// ===============================

$token = $_POST["csrf_token"] ?? "";

if (!verify_csrf($token)) {

    http_response_code(403);
    die("Yêu cầu không hợp lệ. CSRF token không đúng.");

}


// ===============================
// LẤY ID
// ===============================

$id = isset($_POST["id"])
    ? (int) $_POST["id"]
    : 0;


if ($id <= 0) {

    header("Location: ../subjects/index.php");
    exit();

}


$user_id = $_SESSION["user_id"];


// ===============================
// KIỂM TRA BÀI TẬP THUỘC USER
// ===============================

$sql = "SELECT a.subject_id
        FROM assignments a

        JOIN subjects s
            ON a.subject_id = s.id

        WHERE a.id = ?
        AND s.user_id = ?";


$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    header("Location: ../subjects/index.php");
    exit();

}


$assignment = $result->fetch_assoc();

$subject_id = $assignment["subject_id"];


// ===============================
// XÓA
// ===============================

$sql = "DELETE FROM assignments
        WHERE id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $id
);

$stmt->execute();


header(
    "Location: ../subjects/detail.php?id="
    . $subject_id
    . "&success=delete"
);

exit();

?>