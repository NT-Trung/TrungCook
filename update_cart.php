<?php
session_start();
$conn = new mysqli("localhost", "root", "", "thoitrangtrungcook");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Vui lòng đăng nhập để thực hiện thao tác."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = isset($input['action']) ? $input['action'] : '';
$cart_id = isset($input['cart_id']) ? intval($input['cart_id']) : 0;

if (!$action || !$cart_id) {
    echo json_encode(["success" => false, "message" => "Thiếu thông tin hành động hoặc ID giỏ hàng."]);
    exit;
}

// Kiểm tra bản ghi giỏ hàng thuộc về người dùng
$checkCart = $conn->prepare("SELECT ProductID, SL, ThuocTinh FROM giohang WHERE ID = ? AND ID_Account = ?");
if ($checkCart === false) {
    echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
    exit;
}
$checkCart->bind_param("ii", $cart_id, $user_id);
$checkCart->execute();
$cartResult = $checkCart->get_result();

if ($cartResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Không tìm thấy sản phẩm trong giỏ hàng của bạn."]);
    exit;
}
$cartItem = $cartResult->fetch_assoc();
$product_id = $cartItem['ProductID'];
$current_quantity = $cartItem['SL'];
$size = $cartItem['ThuocTinh'];

if ($action === 'update') {
    $new_quantity = isset($input['quantity']) ? intval($input['quantity']) : 0;

    if ($new_quantity < 1) {
        echo json_encode(["success" => false, "message" => "Số lượng phải lớn hơn 0."]);
        exit;
    }

    // Kiểm tra tồn kho
    $checkStock = $conn->prepare("SELECT SL FROM hanghoa WHERE ID = ? AND ThuocTinh = ?");
    if ($checkStock === false) {
        echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
        exit;
    }
    $checkStock->bind_param("is", $product_id, $size);
    $checkStock->execute();
    $stockResult = $checkStock->get_result();
    $stock = $stockResult->fetch_assoc()['SL'];

    if ($new_quantity > $stock + $current_quantity) {
        echo json_encode(["success" => false, "message" => "Số lượng vượt quá tồn kho! Còn lại: $stock."]);
        exit;
    }

    // Cập nhật số lượng trong giohang
    $updateCart = $conn->prepare("UPDATE giohang SET SL = ? WHERE ID = ? AND ID_Account = ?");
    if ($updateCart === false) {
        echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
        exit;
    }
    $updateCart->bind_param("iii", $new_quantity, $cart_id, $user_id);
    if (!$updateCart->execute()) {
        echo json_encode(["success" => false, "message" => "Lỗi cập nhật giỏ hàng: " . $updateCart->error]);
        exit;
    }

    // Cập nhật tồn kho trong hanghoa
    $quantity_diff = $new_quantity - $current_quantity;
    $updateStock = $conn->prepare("UPDATE hanghoa SET SL = SL - ? WHERE ID = ? AND ThuocTinh = ?");
    if ($updateStock === false) {
        echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
        exit;
    }
    $updateStock->bind_param("iis", $quantity_diff, $product_id, $size);
    if (!$updateStock->execute()) {
        echo json_encode(["success" => false, "message" => "Lỗi cập nhật tồn kho: " . $updateStock->error]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Cập nhật số lượng thành công."]);
} elseif ($action === 'delete') {
    // Hoàn lại tồn kho
    $restoreStock = $conn->prepare("UPDATE hanghoa SET SL = SL + ? WHERE ID = ? AND ThuocTinh = ?");
    if ($restoreStock === false) {
        echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
        exit;
    }
    $restoreStock->bind_param("iis", $current_quantity, $product_id, $size);
    if (!$restoreStock->execute()) {
        echo json_encode(["success" => false, "message" => "Lỗi cập nhật tồn kho: " . $restoreStock->error]);
        exit;
    }

    // Xóa bản ghi khỏi giohang
    $deleteCart = $conn->prepare("DELETE FROM giohang WHERE ID = ? AND ID_Account = ?");
    if ($deleteCart === false) {
        echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
        exit;
    }
    $deleteCart->bind_param("ii", $cart_id, $user_id);
    if (!$deleteCart->execute()) {
        echo json_encode(["success" => false, "message" => "Lỗi xóa sản phẩm: " . $deleteCart->error]);
        exit;
    }

    echo json_encode(["success" => true, "message" => "Đã xóa sản phẩm khỏi giỏ hàng."]);
} else {
    echo json_encode(["success" => false, "message" => "Hành động không hợp lệ."]);
}

$conn->close();
?>