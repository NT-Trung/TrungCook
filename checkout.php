<?php
$conn = new mysqli("localhost", "root", "", "fashion_store");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Giả lập thanh toán bằng cách xoá toàn bộ giỏ hàng
$conn->query("TRUNCATE TABLE cart");

echo "<h2>✅ Thanh toán thành công!</h2>";
echo "<p>Cảm ơn bạn đã mua sắm. Đơn hàng của bạn đang được xử lý.</p>";
echo "<a href='index.html'>← Về trang chủ</a>";

$conn->close();
?>
