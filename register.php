<?php

include "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($full_name == "" || $email == "" || $password == "") {

        $message = "Vui lòng nhập đầy đủ thông tin.";

    } elseif ($password != $confirm_password) {

        $message = "Mật khẩu xác nhận không khớp.";

    } else {

        // Kiểm tra email đã tồn tại
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            $message = "Email này đã được sử dụng.";

        } else {

            // Mã hóa mật khẩu
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Thêm tài khoản
            $sql = "INSERT INTO users (full_name, email, password)
                    VALUES (?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sss",
                $full_name,
                $email,
                $hashed_password
            );

            if ($stmt->execute()) {

                header("Location: login.php?register=success");
                exit();

            } else {

                $message = "Đăng ký thất bại.";

            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng ký - SmartLife Hub</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<div class="auth-container">

    <div class="auth-box">

        <h1>🌱 SmartLife Hub</h1>

        <h2>Đăng ký tài khoản</h2>

        <?php if ($message != ""): ?>

            <div class="error-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label>Họ và tên</label>

                <input
                    type="text"
                    name="full_name"
                    placeholder="Nhập họ và tên"
                    required
                >

            </div>

            <div class="form-group">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Nhập email"
                    required
                >

            </div>

      <div class="form-group">

    <label>Mật khẩu</label>

    <div class="password-box">

        <input
            type="password"
            name="password"
            id="password"
            placeholder="Nhập mật khẩu"
            required
        >

        <button
            type="button"
            class="show-password"
            onclick="showPassword('password', this)"
        >
            👁
        </button>

    </div>

</div>

         <div class="form-group">

    <label>Nhập lại mật khẩu</label>

    <div class="password-box">

        <input
            type="password"
            name="confirm_password"
            id="confirm_password"
            placeholder="Nhập lại mật khẩu"
            required
        >

        <button
            type="button"
            class="show-password"
            onclick="showPassword('confirm_password', this)"
        >
            👁
        </button>

    </div>

</div>

            <button type="submit" class="auth-button">
                Đăng ký
            </button>

        </form>

        <p class="auth-link">

            Đã có tài khoản?

            <a href="login.php">
                Đăng nhập
            </a>

        </p>

    </div>

</div>

<script>

function showPassword(inputId, button) {

    var input = document.getElementById(inputId);

    if (input.type === "password") {

        input.type = "text";
        button.innerHTML = "🙈";

    } else {

        input.type = "password";
        button.innerHTML = "👁";

    }

}

</script>

</body>

</html>