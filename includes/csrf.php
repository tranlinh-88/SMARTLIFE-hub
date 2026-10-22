<?php

// Tạo CSRF token nếu chưa có
if (!isset($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(
        random_bytes(32)
    );
}


// Hàm lấy token
function csrf_token()
{
    return $_SESSION["csrf_token"];
}


// Hàm kiểm tra token
function verify_csrf($token)
{
    if (
        !isset($_SESSION["csrf_token"]) ||
        empty($token)
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION["csrf_token"],
        $token
    );
}

?>