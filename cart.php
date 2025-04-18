<?php
session_start();
$conn = new mysqli("localhost", "root", "", "thoitrangtrungcook");
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng."]);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $price = intval($_POST['price']);
    $size = $conn->real_escape_string($_POST['size']);
    $quantity = intval($_POST['quantity']);

    // Kiểm tra sản phẩm có tồn tại không
    $checkProduct = $conn->prepare("SELECT ID, TenSP, SL, HinhAnh, GiaBan FROM hanghoa WHERE ID = ? AND ThuocTinh = ?");
    if ($checkProduct === false) {
        echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
        exit;
    }
    $checkProduct->bind_param("is", $product_id, $size);
    $checkProduct->execute();
    $productResult = $checkProduct->get_result();

    if ($productResult->num_rows == 0) {
        echo json_encode(["success" => false, "message" => "Sản phẩm với ID $product_id và size $size không tồn tại."]);
        exit;
    }

    $product = $productResult->fetch_assoc();
    $stock = $product['SL'];

    if ($quantity > $stock) {
        echo json_encode(["success" => false, "message" => "Không đủ hàng tồn kho! Còn lại: $stock."]);
        exit;
    }

    // Kiểm tra sản phẩm đã có trong giỏ hàng của tài khoản này chưa
    $checkCart = $conn->prepare("SELECT ID, SL FROM giohang WHERE ID_Account = ? AND ProductID = ? AND ThuocTinh = ?");
    if ($checkCart === false) {
        echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
        exit;
    }
    $checkCart->bind_param("iis", $user_id, $product_id, $size);
    $checkCart->execute();
    $cartResult = $checkCart->get_result();

    if ($cartResult->num_rows > 0) {
        // Cập nhật số lượng
        $cartItem = $cartResult->fetch_assoc();
        $newQuantity = $cartItem['SL'] + $quantity;
        if ($newQuantity > $stock) {
            echo json_encode(["success" => false, "message" => "Tổng số lượng vượt quá tồn kho! Còn lại: $stock."]);
            exit;
        }
        $updateCart = $conn->prepare("UPDATE giohang SET SL = ? WHERE ID = ?");
        if ($updateCart === false) {
            echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
            exit;
        }
        $updateCart->bind_param("ii", $newQuantity, $cartItem['ID']);
        if (!$updateCart->execute()) {
            echo json_encode(["success" => false, "message" => "Lỗi cập nhật giỏ hàng: " . $conn->error]);
            exit;
        }
    } else {
        // Thêm mới vào giỏ hàng
        $stmt = $conn->prepare("INSERT INTO giohang (ID_Account, ProductID, TenSP, SL, GiaBan, ThuocTinh, HinhAnh) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("iisiiss", $user_id, $product_id, $product['TenSP'], $quantity, $product['GiaBan'], $size, $product['HinhAnh']);
        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "message" => "Lỗi thêm vào giỏ hàng: " . $stmt->error]);
            exit;
        }
    }

    // Cập nhật tồn kho
    $update = $conn->prepare("UPDATE hanghoa SET SL = SL - ? WHERE ID = ? AND ThuocTinh = ?");
    if ($update === false) {
        echo json_encode(["success" => false, "message" => "Lỗi chuẩn bị truy vấn: " . $conn->error]);
        exit;
    }
    $update->bind_param("iis", $quantity, $product_id, $size);
    if (!$update->execute()) {
        echo json_encode(["success" => false, "message" => "Lỗi cập nhật tồn kho: " . $conn->error]);
        exit;
    }

    // Lấy giỏ hàng của tài khoản này
    $sql = "SELECT ID, ProductID, TenSP, SL, GiaBan, ThuocTinh, HinhAnh FROM giohang WHERE ID_Account = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result === false) {
        echo json_encode(["success" => false, "message" => "Lỗi truy vấn giỏ hàng: " . $conn->error]);
        exit;
    }

    $cart = [];
    while ($row = $result->fetch_assoc()) {
        $cart[] = [
            "id" => $row['ID'],
            "product_id" => $row['ProductID'],
            "name" => $row['TenSP'],
            "quantity" => $row['SL'],
            "price" => $row['GiaBan'],
            "size" => $row['ThuocTinh'],
            "image" => $row['HinhAnh']
        ];
    }

    echo json_encode(["success" => true, "cart" => $cart]);
    exit;
}

$conn->close();
?>