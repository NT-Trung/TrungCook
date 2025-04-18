<?php
session_start();
$conn = new mysqli("localhost", "root", "", "thoitrangtrungcook");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy một sản phẩm đại diện (nhóm theo TenSP, lấy bản ghi đầu tiên)
$sql = "SELECT TenSP, HinhAnh, MIN(GiaBan) as GiaBan, MIN(ID) as ID 
        FROM hanghoa 
        GROUP BY TenSP 
        ORDER BY ID DESC";
$result = $conn->query($sql);
$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Đếm số lượng sản phẩm trong giỏ hàng của tài khoản này
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sqlCart = "SELECT SUM(SL) as total FROM giohang WHERE ID_Account = ?";
    $stmtCart = $conn->prepare($sqlCart);
    $stmtCart->bind_param("i", $user_id);
    $stmtCart->execute();
    $resultCart = $stmtCart->get_result();
    $cartCount = $resultCart && $resultCart->num_rows > 0 ? $resultCart->fetch_assoc()['total'] : 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Thời Trang TrungCook</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 font-sans">
  <header class="bg-white shadow">
    <div class="container mx-auto p-4 flex justify-between items-center">
      <h1 class="text-xl font-bold">Thời Trang TrungCook</h1>
      <nav class="flex items-center">
        <a href="index.php" class="mx-2 text-gray-700 hover:text-blue-500">Trang chủ</a>
        <a href="#pants" class="mx-2 text-gray-700 hover:text-blue-500">Quần</a>
        <a href="#shirts" class="mx-2 text-gray-700 hover:text-blue-500">Áo</a>
        <a href="#contact" class="mx-2 text-gray-700 hover:text-blue-500">Liên hệ</a>
        <div class="relative">
          <a href="cart-view.php" class="mx-2 text-gray-700 hover:text-blue-500">
            🛒 Giỏ hàng (<span id="cartCount"><?php echo $cartCount; ?></span>)
          </a>
        </div>
        <?php if (isset($_SESSION['user_name'])): ?>
          <span class="mx-2 text-gray-700">Chào, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
          <a href="logout.php" class="mx-2 text-gray-700 hover:text-blue-500">Đăng xuất</a>
        <?php else: ?>
          <a href="login.php" class="mx-2 text-gray-700 hover:text-blue-500">Đăng nhập</a>
          <a href="register.php" class="mx-2 text-gray-700 hover:text-blue-500">Đăng ký</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <div class="container mx-auto p-6">
    <h2 id="pants" class="text-2xl font-bold mb-6">Sản Phẩm - Quần</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
      <?php foreach ($products as $product): ?>
        <div class="bg-white p-4 rounded shadow">
          <img src="<?php echo htmlspecialchars($product['HinhAnh']); ?>" alt="<?php echo htmlspecialchars($product['TenSP']); ?>" class="w-full h-48 object-cover rounded mb-4">
          <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($product['TenSP']); ?></h3>
          <p class="text-red-500 font-semibold"><?php echo number_format($product['GiaBan']); ?> đ</p>
          <a href="product-detail.php?id=<?php echo $product['ID']; ?>" class="mt-2 inline-block bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
            Xem chi tiết
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
<?php $conn->close(); ?>