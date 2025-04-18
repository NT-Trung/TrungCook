<?php
session_start();
$conn = new mysqli("localhost", "root", "", "thoitrangtrungcook");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách sản phẩm trong giỏ hàng
$sql = "SELECT g.ID, g.ProductID, g.TenSP, g.SL, g.GiaBan, g.ThuocTinh, g.HinhAnh, h.SL as Stock 
        FROM giohang g 
        JOIN hanghoa h ON g.ProductID = h.ID AND g.ThuocTinh = h.ThuocTinh 
        WHERE g.ID_Account = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cartItems = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Giỏ Hàng</title>
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
          🛒 Giỏ hàng (<span id="cartCount"><?php echo array_sum(array_column($cartItems, 'SL')); ?></span>)
        </a>
        <span class="mx-2 text-gray-700">Chào, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="mx-2 text-gray-700 hover:text-blue-500">Đăng xuất</a>
      </nav>
    </div>
  </header>

  <div class="container mx-auto p-6">
    <a href="index.php" class="text-blue-600 hover:underline">← Quay lại trang chính</a>
    <div class="bg-white p-6 mt-4 rounded shadow">
      <h1 class="text-2xl font-bold mb-6">Giỏ Hàng</h1>
      <div id="cartMessage" class="mb-4 hidden text-center text-green-600 font-semibold"></div>
      <?php if (count($cartItems) > 0): ?>
        <table class="w-full table-auto border-collapse">
          <thead>
            <tr class="bg-gray-200">
              <th class="px-4 py-2 text-left">Hình ảnh</th>
              <th class="px-4 py-2 text-left">Tên sản phẩm</th>
              <th class="px-4 py-2 text-left">Số lượng</th>
              <th class="px-4 py-2 text-left">Size</th>
              <th class="px-4 py-2 text-left">Giá bán</th>
              <th class="px-4 py-2 text-left">Tổng</th>
              <th class="px-4 py-2 text-left">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cartItems as $item): ?>
              <tr class="border-b" data-cart-id="<?php echo $item['ID']; ?>">
                <td class="px-4 py-2">
                  <img src="<?php echo htmlspecialchars($item['HinhAnh']); ?>" alt="<?php echo htmlspecialchars($item['TenSP']); ?>" class="w-16 h-16 object-cover rounded">
                </td>
                <td class="px-4 py-2"><?php echo htmlspecialchars($item['TenSP']); ?></td>
                <td class="px-4 py-2">
                  <input type="number" class="quantity-input w-16 p-1 border rounded" value="<?php echo htmlspecialchars($item['SL']); ?>" min="1" max="<?php echo $item['Stock']; ?>" data-cart-id="<?php echo $item['ID']; ?>">
                  <button class="update-quantity bg-blue-500 text-white px-2 py-1 rounded ml-2 hover:bg-blue-600">Cập nhật</button>
                </td>
                <td class="px-4 py-2"><?php echo htmlspecialchars($item['ThuocTinh']); ?></td>
                <td class="px-4 py-2"><?php echo number_format($item['GiaBan']); ?> đ</td>
                <td class="px-4 py-2 cart-item-total"><?php echo number_format($item['SL'] * $item['GiaBan']); ?> đ</td>
                <td class="px-4 py-2">
                  <button class="delete-item bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600" data-cart-id="<?php echo $item['ID']; ?>">Xóa</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="mt-6 flex justify-between items-center">
          <p class="text-xl font-semibold">
            Tổng cộng: <span id="cartTotal"><?php
              $total = 0;
              foreach ($cartItems as $item) {
                  $total += $item['SL'] * $item['GiaBan'];
              }
              echo number_format($total);
            ?></span> đ
          </p>
          <a href="#" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">Thanh toán</a>
        </div>
      <?php else: ?>
        <p class="text-gray-600">Giỏ hàng của bạn đang trống.</p>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Xử lý cập nhật số lượng
    document.querySelectorAll('.update-quantity').forEach(button => {
      button.addEventListener('click', function () {
        const cartId = this.parentElement.parentElement.dataset.cartId;
        const quantityInput = this.parentElement.querySelector('.quantity-input');
        const newQuantity = parseInt(quantityInput.value);
        const maxQuantity = parseInt(quantityInput.max);

        if (newQuantity < 1) {
          showMessage('Số lượng phải lớn hơn 0!', 'text-red-600');
          return;
        }
        if (newQuantity > maxQuantity) {
          showMessage(`Số lượng không được vượt quá ${maxQuantity}!`, 'text-red-600');
          return;
        }

        fetch('update_cart.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'update', cart_id: cartId, quantity: newQuantity })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Cập nhật tổng giá sản phẩm
            const row = document.querySelector(`tr[data-cart-id="${cartId}"]`);
            const price = parseInt(row.querySelector('td:nth-child(5)').textContent.replace(/[^0-9]/g, ''));
            const newTotal = newQuantity * price;
            row.querySelector('.cart-item-total').textContent = new Intl.NumberFormat('vi-VN').format(newTotal) + ' đ';

            // Cập nhật tổng giỏ hàng
            updateCartTotal();
            updateCartCount();
            showMessage('Cập nhật số lượng thành công!', 'text-green-600');
          } else {
            showMessage(data.message || 'Lỗi khi cập nhật số lượng!', 'text-red-600');
          }
        })
        .catch(error => {
          showMessage('Lỗi kết nối: ' + error.message, 'text-red-600');
        });
      });
    });

    // Xử lý xóa sản phẩm
    document.querySelectorAll('.delete-item').forEach(button => {
      button.addEventListener('click', function () {
        const cartId = this.dataset.cartId;

        if (confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
          fetch('update_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', cart_id: cartId })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              document.querySelector(`tr[data-cart-id="${cartId}"]`).remove();
              updateCartTotal();
              updateCartCount();
              showMessage('Đã xóa sản phẩm khỏi giỏ hàng!', 'text-green-600');
              if (document.querySelectorAll('tbody tr').length === 0) {
                document.querySelector('table').remove();
                document.querySelector('.mt-6').remove();
                document.querySelector('.bg-white').innerHTML += '<p class="text-gray-600">Giỏ hàng của bạn đang trống.</p>';
              }
            } else {
              showMessage(data.message || 'Lỗi khi xóa sản phẩm!', 'text-red-600');
            }
          })
          .catch(error => {
            showMessage('Lỗi kết nối: ' + error.message, 'text-red-600');
          });
        }
      });
    });

    // Hàm hiển thị thông báo
    function showMessage(message, className) {
      const messageDiv = document.getElementById('cartMessage');
      messageDiv.classList.remove('hidden', 'text-green-600', 'text-red-600');
      messageDiv.classList.add(className);
      messageDiv.textContent = message;
      setTimeout(() => {
        messageDiv.classList.add('hidden');
        messageDiv.textContent = '';
      }, 3000);
    }

    // Hàm cập nhật tổng giỏ hàng
    function updateCartTotal() {
      let total = 0;
      document.querySelectorAll('.cart-item-total').forEach(cell => {
        total += parseInt(cell.textContent.replace(/[^0-9]/g, ''));
      });
      document.getElementById('cartTotal').textContent = new Intl.NumberFormat('vi-VN').format(total);
    }

    // Hàm cập nhật số lượng giỏ hàng
    function updateCartCount() {
      let count = 0;
      document.querySelectorAll('.quantity-input').forEach(input => {
        count += parseInt(input.value);
      });
      document.getElementById('cartCount').textContent = count;
    }
  </script>
</body>
</html>
<?php $conn->close(); ?>