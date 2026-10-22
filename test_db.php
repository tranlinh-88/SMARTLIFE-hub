<?php

include "config/database.php";

echo "<h2>Kết nối database thành công!</h2>";

$sql = "SELECT * FROM users";
$result = $conn->query($sql);

echo "<h3>Danh sách người dùng:</h3>";

while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row["id"] . "<br>";
    echo "Họ tên: " . $row["full_name"] . "<br>";
    echo "Email: " . $row["email"] . "<br>";
    echo "<hr>";
}

?>