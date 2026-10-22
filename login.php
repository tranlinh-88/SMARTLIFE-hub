<?php

session_start();

include "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if ($email == "" || $password == "") {

        $message = "Vui lòng nhập email và mật khẩu.";

    } else {

        $sql = "SELECT id, full_name, email, password
                FROM users
                WHERE email = ?";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows == 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];

                header("Location: dashboard/index.php");
                exit();

            } else {

                $message = "Email hoặc mật khẩu không đúng.";

            }

        } else {

            $message = "Email hoặc mật khẩu không đúng.";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Đăng nhập - SmartLife Hub</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<div class="auth-container">

    <div class="auth-box">

        <h1>🌱 SmartLife Hub</h1>

        <h2>Đăng nhập</h2>

        <?php if (isset($_GET["register"])): ?>

            <div class="success-message">
                Đăng ký thành công! Hãy đăng nhập.
            </div>

        <?php endif; ?>

        <?php if ($message != ""): ?>

            <div class="error-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Nhập email"
                    required
                >

            <div class="form-group">

    <label>Mật khẩu</label>

    <div class="password-box">

        <input
            type="password"
            name="password"
            id="login_password"
            placeholder="Nhập mật khẩu"
            required
        >

        <button
            type="button"
            class="show-password"
            onclick="showPassword('login_password', this)"
        >
            👁
        </button>

    </div>

</div>

            <button type="submit" class="auth-button">
                Đăng nhập
            </button>

        </form>

        <p class="auth-link">

            Chưa có tài khoản?

            <a href="register.php">
                Đăng ký
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