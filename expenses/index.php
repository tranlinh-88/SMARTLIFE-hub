<?php

include "../includes/auth.php";
include "../includes/csrf.php";
include "../config/database.php";

$user_id = $_SESSION["user_id"];


/* =====================================================
   THÁNG ĐANG XEM
===================================================== */

$month = $_GET["month"] ?? date("Y-m");


// Kiểm tra đúng định dạng YYYY-MM
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date("Y-m");
}

$start_date = $month . "-01";

$end_date = date(
    "Y-m-t",
    strtotime($start_date)
);


/* =====================================================
   TỔNG THU
===================================================== */

$sql = "SELECT COALESCE(SUM(amount), 0) AS total
        FROM incomes
        WHERE user_id = ?
        AND income_date BETWEEN ? AND ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iss",
    $user_id,
    $start_date,
    $end_date
);

$stmt->execute();

$total_income = $stmt
    ->get_result()
    ->fetch_assoc()["total"];


/* =====================================================
   TỔNG CHI
===================================================== */

$sql = "SELECT COALESCE(SUM(amount), 0) AS total
        FROM expenses
        WHERE user_id = ?
        AND expense_date BETWEEN ? AND ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iss",
    $user_id,
    $start_date,
    $end_date
);

$stmt->execute();

$total_expense = $stmt
    ->get_result()
    ->fetch_assoc()["total"];


/* SỐ DƯ */

$balance =
    $total_income - $total_expense;


/* =====================================================
   DANH SÁCH CHI TIÊU
===================================================== */

$sql = "SELECT *
        FROM expenses
        WHERE user_id = ?
        AND expense_date BETWEEN ? AND ?
        ORDER BY expense_date DESC, id DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iss",
    $user_id,
    $start_date,
    $end_date
);

$stmt->execute();

$expenses = $stmt->get_result();


/* =====================================================
   DANH SÁCH THU NHẬP
===================================================== */

$sql = "SELECT *
        FROM incomes
        WHERE user_id = ?
        AND income_date BETWEEN ? AND ?
        ORDER BY income_date DESC, id DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iss",
    $user_id,
    $start_date,
    $end_date
);

$stmt->execute();

$incomes = $stmt->get_result();


/* =====================================================
   CHI TIÊU THEO DANH MỤC
===================================================== */

$sql = "SELECT
            category,
            SUM(amount) AS total
        FROM expenses
        WHERE user_id = ?
        AND expense_date BETWEEN ? AND ?
        GROUP BY category
        ORDER BY total DESC";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iss",
    $user_id,
    $start_date,
    $end_date
);

$stmt->execute();

$category_result =
    $stmt->get_result();


$category_labels = [];
$category_values = [];

while (
    $row =
    $category_result->fetch_assoc()
) {

    $category_labels[] =
        $row["category"];

    $category_values[] =
        (float)$row["total"];
}


/* =====================================================
   THU / CHI 6 THÁNG GẦN NHẤT
===================================================== */

$chart_months = [];
$chart_income = [];
$chart_expense = [];


for ($i = 5; $i >= 0; $i--) {

    $date = date(
        "Y-m-01",
        strtotime("-$i months")
    );

    $chart_start = $date;

    $chart_end = date(
        "Y-m-t",
        strtotime($date)
    );


    $chart_months[] =
        date(
            "m/Y",
            strtotime($date)
        );


    /* THU */

    $sql = "SELECT
                COALESCE(SUM(amount), 0)
                AS total
            FROM incomes
            WHERE user_id = ?
            AND income_date
                BETWEEN ? AND ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iss",
        $user_id,
        $chart_start,
        $chart_end
    );

    $stmt->execute();

    $chart_income[] =
        (float)$stmt
            ->get_result()
            ->fetch_assoc()["total"];


    /* CHI */

    $sql = "SELECT
                COALESCE(SUM(amount), 0)
                AS total
            FROM expenses
            WHERE user_id = ?
            AND expense_date
                BETWEEN ? AND ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iss",
        $user_id,
        $chart_start,
        $chart_end
    );

    $stmt->execute();

    $chart_expense[] =
        (float)$stmt
            ->get_result()
            ->fetch_assoc()["total"];
}

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Tài chính - SmartLife Hub
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

    <!-- Chart.js -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>


<body class="dashboard-body">


<!-- SIDEBAR -->

<div class="sidebar">

    <div class="sidebar-logo">
        🌱 SmartLife
    </div>


    <div class="sidebar-menu">

        <a href="../dashboard/index.php">
            🏠 Dashboard
        </a>

        <a href="../subjects/index.php">
            📚 Học tập
        </a>

        <a href="../tasks/index.php">
            ✅ Công việc
        </a>

        <a
            href="index.php"
            class="active"
        >
            💰 Tài chính
        </a>

        <a href="../habits/index.php">
            🔥 Thói quen
        </a>

        <a href="../goals/index.php">
            🎯 Mục tiêu
        </a>

        <a href="../ai/assistant.php">
            🤖 AI Assistant
        </a>

    </div>

</div>


<!-- MAIN -->

<div class="dashboard-main">


    <!-- HEADER -->

    <div class="page-header">

        <div>

            <h1>
                💰 Quản lý tài chính
            </h1>

            <p>
                Theo dõi thu nhập, chi tiêu
                và số dư cá nhân.
            </p>

        </div>


        <div class="finance-buttons">

            <a
                href="../incomes/add.php"
                class="btn-income"
            >
                + Thu nhập
            </a>

            <a
                href="add.php"
                class="btn-expense"
            >
                + Chi tiêu
            </a>

        </div>

    </div>


    <!-- THÔNG BÁO -->

    <?php if (isset($_GET["success"])): ?>

        <div class="success-message">

            <?php

            switch ($_GET["success"]) {

                case "income_add":
                    echo "Thêm thu nhập thành công!";
                    break;

                case "income_edit":
                    echo "Cập nhật thu nhập thành công!";
                    break;

                case "income_delete":
                    echo "Xóa thu nhập thành công!";
                    break;

                case "expense_add":
                    echo "Thêm chi tiêu thành công!";
                    break;

                case "expense_edit":
                    echo "Cập nhật chi tiêu thành công!";
                    break;

                case "expense_delete":
                    echo "Xóa chi tiêu thành công!";
                    break;

            }

            ?>

        </div>

    <?php endif; ?>


    <!-- LỌC THÁNG -->

    <div class="finance-filter">

        <form method="GET">

            <label>
                Xem theo tháng:
            </label>

            <input
                type="month"
                name="month"
                value="<?php
                echo htmlspecialchars($month);
                ?>"
            >

            <button
                type="submit"
                class="btn-primary"
            >
                Xem
            </button>

            <a
                href="index.php"
                class="btn-reset"
            >
                Tháng hiện tại
            </a>

        </form>

    </div>


    <!-- THỐNG KÊ -->

    <div class="finance-summary-grid">


        <div class="finance-summary-card">

            <span>💵</span>

            <p>Thu nhập</p>

            <h2 class="income-text">

                <?php
                echo number_format(
                    $total_income,
                    0,
                    ",",
                    "."
                );
                ?> đ

            </h2>

        </div>


        <div class="finance-summary-card">

            <span>💸</span>

            <p>Chi tiêu</p>

            <h2 class="expense-text">

                <?php
                echo number_format(
                    $total_expense,
                    0,
                    ",",
                    "."
                );
                ?> đ

            </h2>

        </div>


        <div class="finance-summary-card">

            <span>💰</span>

            <p>Số dư</p>

            <h2>

                <?php
                echo number_format(
                    $balance,
                    0,
                    ",",
                    "."
                );
                ?> đ

            </h2>

        </div>


    </div>


    <!-- BIỂU ĐỒ -->

    <div class="finance-chart-grid">


        <div class="chart-card">

            <h3>
                📊 Chi tiêu theo danh mục
            </h3>

            <div class="chart-wrapper">

                <canvas
                    id="categoryChart"
                ></canvas>

            </div>

        </div>


        <div class="chart-card">

            <h3>
                📈 Thu / Chi 6 tháng
            </h3>

            <div class="chart-wrapper">

                <canvas
                    id="monthlyChart"
                ></canvas>

            </div>

        </div>


    </div>


    <!-- ==============================
         CHI TIÊU
    =============================== -->

    <div class="finance-section">


        <div class="finance-section-header">

            <h2>
                💸 Danh sách chi tiêu
            </h2>

            <a
                href="add.php"
                class="btn-expense"
            >
                + Thêm chi tiêu
            </a>

        </div>


        <div class="table-container">


            <?php
            if ($expenses->num_rows > 0):
            ?>


                <table class="data-table">

                    <thead>

                    <tr>

                        <th>Ngày</th>
                        <th>Danh mục</th>
                        <th>Mô tả</th>
                        <th>Số tiền</th>
                        <th>Thao tác</th>

                    </tr>

                    </thead>


                    <tbody>


                    <?php
                    while (
                        $expense =
                        $expenses->fetch_assoc()
                    ):
                    ?>


                        <tr>

                            <td>

                                <?php
                                echo date(
                                    "d/m/Y",
                                    strtotime(
                                        $expense[
                                            "expense_date"
                                        ]
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $expense[
                                        "category"
                                    ]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $expense[
                                        "description"
                                    ] ?? ""
                                );
                                ?>

                            </td>


                            <td>

                                <strong
                                    class="expense-text"
                                >

                                    <?php
                                    echo number_format(
                                        $expense[
                                            "amount"
                                        ],
                                        0,
                                        ",",
                                        "."
                                    );
                                    ?> đ

                                </strong>

                            </td>


                            <td>

                                <div
                                    class="task-actions"
                                >

                                    <a
                                        href="edit.php?id=<?php echo (int)$expense["id"]; ?>"
                                        class="btn-edit"
                                    >
                                        Sửa
                                    </a>


                                    <form
                                        method="POST"
                                        action="delete.php"
                                        class="inline-form"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa khoản chi này?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo (int)$expense["id"]; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="month"
                                            value="<?php echo htmlspecialchars($month); ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars(csrf_token()); ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn-delete"
                                        >
                                            Xóa
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>


            <?php else: ?>


                <div class="empty-box">

                    <h3>
                        💸 Chưa có chi tiêu
                    </h3>

                    <p>
                        Chưa có khoản chi nào
                        trong tháng này.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


    <!-- ==============================
         THU NHẬP
    =============================== -->

    <div class="finance-section">


        <div class="finance-section-header">

            <h2>
                💵 Danh sách thu nhập
            </h2>

            <a
                href="../incomes/add.php"
                class="btn-income"
            >
                + Thêm thu nhập
            </a>

        </div>


        <div class="table-container">


            <?php
            if ($incomes->num_rows > 0):
            ?>


                <table class="data-table">

                    <thead>

                    <tr>

                        <th>Ngày</th>
                        <th>Nguồn thu</th>
                        <th>Mô tả</th>
                        <th>Số tiền</th>
                        <th>Thao tác</th>

                    </tr>

                    </thead>


                    <tbody>


                    <?php
                    while (
                        $income =
                        $incomes->fetch_assoc()
                    ):
                    ?>


                        <tr>

                            <td>

                                <?php
                                echo date(
                                    "d/m/Y",
                                    strtotime(
                                        $income[
                                            "income_date"
                                        ]
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $income["source"]
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $income[
                                        "description"
                                    ] ?? ""
                                );
                                ?>

                            </td>


                            <td>

                                <strong
                                    class="income-text"
                                >

                                    <?php
                                    echo number_format(
                                        $income[
                                            "amount"
                                        ],
                                        0,
                                        ",",
                                        "."
                                    );
                                    ?> đ

                                </strong>

                            </td>


                            <td>

                                <div
                                    class="task-actions"
                                >

                                    <a
                                        href="../incomes/edit.php?id=<?php echo (int)$income["id"]; ?>"
                                        class="btn-edit"
                                    >
                                        Sửa
                                    </a>


                                    <form
                                        method="POST"
                                        action="../incomes/delete.php"
                                        class="inline-form"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa khoản thu này?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?php echo (int)$income["id"]; ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="month"
                                            value="<?php echo htmlspecialchars($month); ?>"
                                        >
                                        
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php echo htmlspecialchars(csrf_token()); ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn-delete"
                                        >
                                            Xóa
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                    </tbody>

                </table>


            <?php else: ?>


                <div class="empty-box">

                    <h3>
                        💵 Chưa có thu nhập
                    </h3>

                    <p>
                        Chưa có khoản thu nào
                        trong tháng này.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>


</div>


<!-- =====================================================
     CHART JS
===================================================== -->

<script>

/* DỮ LIỆU PHP → JAVASCRIPT */

const categoryLabels =
    <?php
    echo json_encode(
        $category_labels,
        JSON_UNESCAPED_UNICODE
    );
    ?>;

const categoryValues =
    <?php
    echo json_encode(
        $category_values
    );
    ?>;


const chartMonths =
    <?php
    echo json_encode(
        $chart_months,
        JSON_UNESCAPED_UNICODE
    );
    ?>;


const chartIncome =
    <?php
    echo json_encode(
        $chart_income
    );
    ?>;


const chartExpense =
    <?php
    echo json_encode(
        $chart_expense
    );
    ?>;


/* =========================================
   BIỂU ĐỒ DANH MỤC
========================================= */

const categoryCanvas =
    document.getElementById(
        "categoryChart"
    );


new Chart(
    categoryCanvas,
    {

        type: "doughnut",

        data: {

            labels:
                categoryLabels,

            datasets: [

                {

                    label:
                        "Chi tiêu",

                    data:
                        categoryValues

                }

            ]

        },


        options: {

            responsive: true,

            maintainAspectRatio: false

        }

    }
);


/* =========================================
   BIỂU ĐỒ THU CHI
========================================= */

const monthlyCanvas =
    document.getElementById(
        "monthlyChart"
    );


new Chart(
    monthlyCanvas,
    {

        type: "bar",

        data: {

            labels:
                chartMonths,

            datasets: [

                {

                    label:
                        "Thu nhập",

                    data:
                        chartIncome

                },

                {

                    label:
                        "Chi tiêu",

                    data:
                        chartExpense

                }

            ]

        },


        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {

                    beginAtZero: true

                }

            }

        }

    }
);

</script>


</body>
</html>