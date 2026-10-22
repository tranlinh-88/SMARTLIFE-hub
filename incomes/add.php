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

        die(
            "Yêu cầu không hợp lệ."
        );

    }


    $source =
        trim($_POST["source"] ?? "");

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
        $amount == ""
        ||
        $income_date == ""
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


        $sql = "INSERT INTO incomes
                (
                    user_id,
                    source,
                    amount,
                    description,
                    income_date
                )

                VALUES (?, ?, ?, ?, ?)";


        $stmt =
            $conn->prepare($sql);


        $stmt->bind_param(
            "isdss",
            $user_id,
            $source,
            $amount,
            $description,
            $income_date
        );


        if ($stmt->execute()) {

            // Lấy tháng từ ngày nhận thu nhập
            $redirect_month = date(
                "Y-m",
                strtotime($income_date)
            );

            header(
                "Location: ../expenses/index.php?month="
                . urlencode($redirect_month)
                . "&success=income_add"
            );

            exit();
        }


        $message =
            "Không thể thêm thu nhập.";

    }

}

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <title>Thêm thu nhập</title>

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
                💵 Thêm thu nhập
            </h1>

            <p>
                Ghi lại khoản thu của bạn.
            </p>

        </div>


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
                    Nguồn thu *
                </label>

                <select
                    name="source"
                    required
                >

                    <option value="">
                        -- Chọn nguồn thu --
                    </option>

                    <option value="Tiền gia đình">
                        👨‍👩‍👦 Tiền gia đình
                    </option>

                    <option value="Lương Part-time">
                        💼 Lương Part-time
                    </option>

                    <option value="Tiền thưởng">
                        🎁 Tiền thưởng
                    </option>

                    <option value="Học bổng">
                        🎓 Học bổng
                    </option>

                    <option value="Kinh doanh">
                        💰 Kinh doanh
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
                    placeholder="Ví dụ: 3000000"
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
                    placeholder="Ví dụ: Lương tháng 9"
                >

            </div>


            <div class="form-group">

                <label>
                    Ngày nhận *
                </label>

                <input
                    type="date"
                    name="income_date"
                    value="<?php echo date('Y-m-d'); ?>"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn-income"
            >
                + Thêm thu nhập
            </button>


        </form>

    </div>

</div>

</body>
</html>