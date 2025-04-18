<?php
session_start();
$conn = new mysqli("localhost", "root", "", "thoitrangtrungcook");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Lấy thông tin sản phẩm mặc định (size đầu tiên)
$sql = "SELECT ID, TenSP, SL, ThuocTinh, HinhAnh, MoTa, GiaBan 
        FROM hanghoa 
        WHERE ID = ? 
        ORDER BY ThuocTinh ASC 
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

// Lấy danh sách size
$sqlSizes = "SELECT DISTINCT ThuocTinh 
             FROM hanghoa 
             WHERE ID = ? OR TenSP = (SELECT TenSP FROM hanghoa WHERE ID = ?)";
$stmtSizes = $conn->prepare($sqlSizes);
$stmtSizes->bind_param("ii", $id, $id);
$stmtSizes->execute();
$sizeResult = $stmtSizes->get_result();
$sizes = [];
while ($row = $sizeResult->fetch_assoc()) {
    $sizes[] = $row['ThuocTinh'];
}

// Đếm số lượng sản phẩm trong giỏ hàng
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
  <title>Chi Tiết Sản Phẩm</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
</head>
<body class="bg-gray-100 font-sans">
  <header class="bg-white shadow">
    <div class="container mx-auto p-4 flex justify-between items-center">
      <h1 class="text-xl font-bold">Thời Trang TrungCook</h1>
      <nav class="flex items-center">
        <a href="index.php" class="mx-2 text-gray-700 hover:text-blue-500">Trang chủ</a>
        <a href="index.php#pants" class="mx-2 text-gray-700 hover:text-blue-500">Quần</a>
        <a href="index.php#shirts" class="mx-2 text-gray-700 hover:text-blue-500">Áo</a>
        <a href="index.php#contact" class="mx-2 text-gray-700 hover:text-blue-500">Liên hệ</a>
        <a href="cart-view.php" class="mx-2 text-gray-700 hover:text-blue-500">
          🛒 Giỏ hàng (<span id="cartCount"><?php echo $cartCount; ?></span>)
        </a>
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
    <a href="index.php" class="text-blue-600 hover:underline">← Quay lại trang chính</a>
    <div id="product" class="bg-white p-6 mt-4 rounded shadow">
      <?php if ($product): ?>
        <h1 class="text-2xl font-bold mb-6"><?php echo htmlspecialchars($product['TenSP']); ?></h1>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
          <div>
            <img id="productImage" class="w-full max-w-sm rounded mb-4" src="<?php echo htmlspecialchars($product['HinhAnh']); ?>" alt="<?php echo htmlspecialchars($product['TenSP']); ?>">
            <p id="productPrice" class="text-xl text-red-500 font-semibold"><?php echo number_format($product['GiaBan']); ?> đ</p>
          </div>
          <div>
            <h2 class="text-lg font-semibold mb-2">Mô tả sản phẩm:</h2>
            <p id="productDescription" class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($product['MoTa']); ?></p>
            <p id="productStock" class="text-gray-600 mt-2">Số lượng tồn kho: <?php echo htmlspecialchars($product['SL']); ?></p>
            <p id="productSize" class="text-gray-600">Size: <?php echo htmlspecialchars($product['ThuocTinh']); ?></p>
          </div>
          <div>
            <?php if (isset($_SESSION['user_id'])): ?>
              <form id="addToCartForm">
                <input type="hidden" name="product_id" id="productId" value="<?php echo $product['ID']; ?>">
                <input type="hidden" name="price" id="priceInput" value="<?php echo $product['GiaBan']; ?>">
                <div class="mb-4">
                  <label for="size" class="block font-semibold mb-1">Chọn size:</label>
                  <select id="size" name="size" required class="w-full p-2 border rounded">
                    <option value="">-- Size --</option>
                    <?php foreach ($sizes as $size): ?>
                      <option value="<?php echo htmlspecialchars($size); ?>"><?php echo htmlspecialchars($size); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-4">
                  <label for="quantity" class="block font-semibold mb-1">Số lượng:</label>
                  <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['SL']; ?>" required class="w-24 p-2 border rounded">
                </div>
                <button type="submit" id="addToCartButton" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                  Thêm vào giỏ hàng
                </button>
              </form>
              <div id="cartMessage" class="mt-4 hidden text-green-600 font-semibold"></div>
            <?php else: ?>
              <p class="text-red-600 font-semibold">Vui lòng <a href="login.php" class="text-blue-600 hover:underline">đăng nhập</a> để thêm sản phẩm vào giỏ hàng.</p>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <p class="text-red-500">Không tìm thấy sản phẩm.</p>
      <?php endif; ?>
    </div>
  </div>

  <script>
    <?php if (isset($_SESSION['user_id'])): ?>
      // Xử lý thay đổi size
      document.getElementById('size').addEventListener('change', function () {
        const size = this.value;
        const productId = <?php echo $id; ?>;
        if (size) {
          fetch('get_product_by_size.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: productId, size: size })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              document.getElementById('productImage').src = data.product.HinhAnh;
              document.getElementById('productPrice').textContent = new Intl.NumberFormat('vi-VN').format(data.product.GiaBan) + ' đ';
              document.getElementById('productDescription').textContent = data.product.MoTa;
              document.getElementById('productStock').textContent = 'Số lượng tồn kho: ' + data.product.SL;
              document.getElementById('productSize').textContent = 'Size: ' + data.product.ThuocTinh;
              document.getElementById('quantity').max = data.product.SL;
              document.getElementById('productId').value = data.product.ID;
              document.getElementById('priceInput').value = data.product.GiaBan;
            } else {
              alert(data.message || 'Không tìm thấy sản phẩm với size này.');
            }
          })
          .catch(error => {
            alert('Lỗi kết nối: ' + error.message);
          });
        }
      });

      // Xử lý thêm vào giỏ hàng
      document.getElementById('addToCartForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const size = document.getElementById('size').value;
        const quantity = parseInt(document.getElementById('quantity').value);
        const messageDiv = document.getElementById('cartMessage');

        if (!size) {
          messageDiv.classList.remove('hidden', 'text-green-600');
          messageDiv.classList.add('text-red-600');
          messageDiv.textContent = 'Vui lòng chọn size!';
          setTimeout(() => {
            messageDiv.classList.add('hidden');
            messageDiv.textContent = '';
          }, 3000);
          return;
        }

        const formData = new FormData(this);
        fetch('cart.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          messageDiv.classList.remove('hidden', 'text-red-600');
          if (data.success) {
            messageDiv.classList.add('text-green-600');
            messageDiv.textContent = 'Đã thêm vào giỏ hàng thành công!';
            const currentCount = parseInt(document.getElementById('cartCount').textContent);
            document.getElementById('cartCount').textContent = currentCount + quantity;
          } else {
            messageDiv.classList.add('text-red-600');
            messageDiv.textContent = data.message || 'Lỗi khi thêm vào giỏ hàng!';
          }
          setTimeout(() => {
            messageDiv.classList.add('hidden');
            messageDiv.textContent = '';
          }, 3000);
        })
        .catch(error => {
          messageDiv.classList.remove('hidden', 'text-green-600');
          messageDiv.classList.add('text-red-600');
          messageDiv.textContent = 'Lỗi kết nối: ' + error.message;
          setTimeout(() => {
            messageDiv.classList.add('hidden');
            messageDiv.textContent = '';
          }, 3000);
        });
      });
    <?php endif; ?>
  </script>
</body>
</html>
<?php $conn->close(); ?>