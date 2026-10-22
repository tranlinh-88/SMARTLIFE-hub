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


/* LẤY DỮ LIỆU */

$sql = "SELECT *
        FROM expenses
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

    header(
        "Location: index.php"
    );

    exit();

}


$expense =
    $result->fetch_assoc();


$message = "";


/* UPDATE */

if (
    $_SERVER["REQUEST_METHOD"]
    === "POST"
) {


    $token =
        $_POST["csrf_token"] ?? "";


    if (!verify_csrf($token)) {

        http_response_code(403);

        die(
            "Yêu cầu không hợp lệ."
        );

    }


    $category =
        trim(
            $_POST["category"] ?? ""
        );

    $amount =
        $_POST["amount"] ?? "";

    $description =
        trim(
            $_POST["description"] ?? ""
        );

    $expense_date =
        $_POST["expense_date"] ?? "";


    if (
        $category == ""
        ||
        !is_numeric($amount)
        ||
        $amount <= 0
        ||
        $expense_date == ""
    ) {

        $message =
            "Thông tin không hợp lệ.";

    }

    else {


        $sql = "UPDATE expenses

                SET category = ?,
                    amount = ?,
                    description = ?,
                    expense_date = ?

                WHERE id = ?
                AND user_id = ?";


        $stmt =
            $conn->prepare($sql);


        $stmt->bind_param(
            "sdssii",
            $category,
            $amount,
            $description,
            $expense_date,
            $id,
            $user_id
        );


        if ($stmt->execute()) {

            $redirect_month = date(
                "Y-m",
                strtotime($expense_date)
            );

            header(
                "Location: index.php?month="
                . urlencode($redirect_month)
                . "&success=expense_edit"
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

<title>Sửa chi tiêu</title>

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

    <h1>✏️ Sửa chi tiêu</h1>

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

<label>Danh mục</label>

<select
    name="category"
    required
>

<?php

$categories = [
    "Ăn uống",
    "Đi lại",
    "Học tập",
    "Mua sắm",
    "Giải trí",
    "Sức khỏe",
    "Khác"
];

foreach (
    $categories as $category
):

?>

<option
    value="<?php echo htmlspecialchars($category); ?>"

    <?php
    if (
        $expense["category"]
        == $category
    )
        echo "selected";
    ?>
>

    <?php
    echo htmlspecialchars($category);
    ?>

</option>

<?php endforeach; ?>

</select>

</div>


<div class="form-group">

<label>Số tiền</label>

<input
    type="number"
    name="amount"
    min="1"
    step="1"
    value="<?php echo htmlspecialchars($expense["amount"]); ?>"
    required
>

</div>


<div class="form-group">

<label>Mô tả</label>

<input
    type="text"
    name="description"
    value="<?php echo htmlspecialchars($expense["description"] ?? ""); ?>"
>

</div>


<div class="form-group">

<label>Ngày chi</label>

<input
    type="date"
    name="expense_date"
    value="<?php echo htmlspecialchars($expense["expense_date"]); ?>"
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