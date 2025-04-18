<?php
session_start();
$conn = new mysqli("localhost", "root", "", "thoitrangtrungcook");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tenTaiKhoan = trim($_POST['tenTaiKhoan']);
    $matKhau = trim($_POST['matKhau']);
    $confirm_matKhau = trim($_POST['confirm_matKhau']);

    // Kiểm tra đầu vào
    if (empty($tenTaiKhoan) || empty($matKhau) || empty($confirm_matKhau)) {
        $message = "Vui lòng điền đầy đủ thông tin.";
    } elseif ($matKhau !== $confirm_matKhau) {
        $message = "Mật khẩu xác nhận không khớp.";
    } elseif (strlen($matKhau) < 1) {
        $message = "Mật khẩu phải có ít nhất 1 ký tự.";
    } else {
        // Kiểm tra tên tài khoản đã tồn tại
        $stmt = $conn->prepare("SELECT ID_Account FROM taikhoan WHERE TenTaiKhoan = ?");
        if ($stmt === false) {
            $message = "Lỗi chuẩn bị truy vấn: " . $conn->error;
        } else {
            $stmt->bind_param("s", $tenTaiKhoan);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $message = "Tên tài khoản đã tồn tại.";
            } else {
                // Mã hóa mật khẩu và lưu tài khoản
                $hashed_matKhau = password_hash($matKhau, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO taikhoan (TenTaiKhoan, MatKhau) VALUES (?, ?)");
                if ($stmt === false) {
                    $message = "Lỗi chuẩn bị truy vấn: " . $conn->error;
                } else {
                    $stmt->bind_param("ss", $tenTaiKhoan, $hashed_matKhau);
                    if ($stmt->execute()) {
                        // Lấy ID_Account của tài khoản vừa tạo
                        $stmt->close();
                        $stmt = $conn->prepare("SELECT ID_Account, TenTaiKhoan FROM taikhoan WHERE TenTaiKhoan = ?");
                        $stmt->bind_param("s", $tenTaiKhoan);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = $result->fetch_assoc();

                        // Đăng nhập tự động
                        $_SESSION['user_id'] = $user['ID_Account'];
                        $_SESSION['user_name'] = $user['TenTaiKhoan'];
                        header("Location: index.php");
                        exit;
                    } else {
                        $message = "Lỗi khi đăng ký: " . $stmt->error;
                    }
                }
                $stmt->close();
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Đăng Ký</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"/>
</head>
<body class="bg-gray-100 font-sans">
  <div class="container mx-auto p-6 max-w-md">
    <h1 class="text-2xl font-bold mb-6 text-center">Đăng Ký Tài Khoản</h1>
    <div class="bg-white p-6 rounded shadow">
      <?php if ($message): ?>
        <p class="mb-4 text-center <?php echo strpos($message, 'thành công') !== false ? 'text-green-600' : 'text-red-600'; ?>">
          <?php echo htmlspecialchars($message); ?>
        </p>
      <?php endif; ?>
      <form method="POST" action="register.php">
        <div class="mb-4">
          <label for="tenTaiKhoan" class="block font-semibold mb-1">Tên tài khoản</label>
          <input type="text" id="tenTaiKhoan" name="tenTaiKhoan" required class="w-full p-2 border rounded" value="<?php echo isset($_POST['tenTaiKhoan']) ? htmlspecialchars($_POST['tenTaiKhoan']) : ''; ?>">
        </div>
        <div class="mb-4">
          <label for="matKhau" class="block font-semibold mb-1">Mật khẩu</label>
          <input type="password" id="matKhau" name="matKhau" required class="w-full p-2 border rounded">
        </div>
        <div class="mb-4">
          <label for="confirm_matKhau" class="block font-semibold mb-1">Xác nhận mật khẩu</label>
          <input type="password" id="confirm_matKhau" name="confirm_matKhau" required class="w-full p-2 border rounded">
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
          Đăng Ký
        </button>
      </form>
      <p class="mt-4 text-center">
        Đã có tài khoản? <a href="login.php" class="text-blue-600 hover:underline">Đăng nhập</a>
      </p>
    </div>
  </div>
</body>
</html>