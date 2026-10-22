<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];

$message = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* CSRF */

    $token =
        $_POST["csrf_token"] ?? "";

    if (!verify_csrf($token)) {

        http_response_code(403);

        die(
            "Yêu cầu không hợp lệ."
        );

    }


    /* INPUT */

    $category =
        trim($_POST["category"] ?? "");

    $amount =
        $_POST["amount"] ?? "";

    $description =
        trim(
            $_POST["description"] ?? ""
        );

    $expense_date =
        $_POST["expense_date"] ?? "";


    /* VALIDATE */

    if (
        $category == ""
        ||
        $amount == ""
        ||
        $expense_date == ""
    ) {

        $message =
            "Vui lòng nhập đầy đủ thông tin.";

    }

    elseif (
        !is_numeric($amount)
        ||
        $amount <= 0
    ) {

        $message =
            "Số tiền phải lớn hơn 0.";

    }

    else {


        $sql = "INSERT INTO expenses
                (
                    user_id,
                    category,
                    amount,
                    description,
                    expense_date
                )

                VALUES (?, ?, ?, ?, ?)";


        $stmt =
            $conn->prepare($sql);


        $stmt->bind_param(
            "isdss",
            $user_id,
            $category,
            $amount,
            $description,
            $expense_date
        );


        if ($stmt->execute()) {

            // Lấy tháng từ ngày chi tiêu vừa nhập
            $redirect_month = date(
                "Y-m",
                strtotime($expense_date)
            );

            header(
                "Location: index.php?month="
                . urlencode($redirect_month)
                . "&success=expense_add"
            );

            exit();
        }


        $message =
            "Không thể thêm chi tiêu.";

    }

}

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Thêm chi tiêu</title>

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
                💸 Thêm chi tiêu
            </h1>

            <p>
                Ghi lại khoản chi của bạn.
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
                echo htmlspecialchars(
                    $message
                );
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
                    Danh mục *
                </label>

                <select
                    name="category"
                    required
                >

                    <option value="">
                        -- Chọn danh mục --
                    </option>

                    <option value="Ăn uống">
                        🍜 Ăn uống
                    </option>

                    <option value="Đi lại">
                        🛵 Đi lại
                    </option>

                    <option value="Học tập">
                        📚 Học tập
                    </option>

                    <option value="Mua sắm">
                        🛍 Mua sắm
                    </option>

                    <option value="Giải trí">
                        🎮 Giải trí
                    </option>

                    <option value="Sức khỏe">
                        ❤️ Sức khỏe
                    </option>

                    <option value="Khác">
                        📦 Khác
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Số tiền *
                </label>

                <input
                    type="number"
                    name="amount"
                    min="1"
                    step="1"
                    placeholder="Ví dụ: 50000"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Mô tả
                </label>

                <input
                    type="text"
                    name="description"
                    placeholder="Ví dụ: Ăn trưa"
                >

            </div>


            <div class="form-group">

                <label>
                    Ngày chi *
                </label>

                <input
                    type="date"
                    name="expense_date"
                    value="<?php echo date('Y-m-d'); ?>"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn-expense"
            >
                + Thêm chi tiêu
            </button>


        </form>

    </div>

</div>

</body>
</html>