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


$sql = "SELECT *
        FROM incomes
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
        "Location: ../expenses/index.php"
    );

    exit();

}


$income =
    $result->fetch_assoc();


$message = "";


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


    $source =
        trim(
            $_POST["source"] ?? ""
        );

    $amount =
        $_POST["amount"] ?? "";

    $description =
        trim(
            $_POST["description"] ?? ""
        );

    $income_date =
        $_POST["income_date"] ?? "";


    if (
        $source == ""
        ||
        !is_numeric($amount)
        ||
        $amount <= 0
        ||
        $income_date == ""
    ) {

        $message =
            "Thông tin không hợp lệ.";

    }

    else {


        $sql = "UPDATE incomes

                SET source = ?,
                    amount = ?,
                    description = ?,
                    income_date = ?

                WHERE id = ?
                AND user_id = ?";


        $stmt =
            $conn->prepare($sql);


        $stmt->bind_param(
            "sdssii",
            $source,
            $amount,
            $description,
            $income_date,
            $id,
            $user_id
        );


        if ($stmt->execute()) {

            $redirect_month = date(
                "Y-m",
                strtotime($income_date)
            );

            header(
                "Location: ../expenses/index.php?month="
                . urlencode($redirect_month)
                . "&success=income_edit"
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

<title>Sửa thu nhập</title>

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

<h1>✏️ Sửa thu nhập</h1>

<a
    href="../expenses/index.php"
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

<label>Nguồn thu</label>

<select
    name="source"
    required
>

<?php

$sources = [
    "Tiền gia đình",
    "Lương Part-time",
    "Tiền thưởng",
    "Học bổng",
    "Kinh doanh",
    "Khác"
];


foreach (
    $sources as $source
):

?>

<option
    value="<?php echo htmlspecialchars($source); ?>"

    <?php
    if (
        $income["source"]
        == $source
    )
        echo "selected";
    ?>
>

<?php
echo htmlspecialchars($source);
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
    value="<?php echo htmlspecialchars($income["amount"]); ?>"
    required
>

</div>


<div class="form-group">

<label>Mô tả</label>

<input
    type="text"
    name="description"
    value="<?php echo htmlspecialchars($income["description"] ?? ""); ?>"
>

</div>


<div class="form-group">

<label>Ngày nhận</label>

<input
    type="date"
    name="income_date"
    value="<?php echo htmlspecialchars($income["income_date"]); ?>"
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