<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "thoitrangtrungcook");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Kết nối thất bại: " . $conn->connect_error]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? intval($input['id']) : 0;
$size = isset($input['size']) ? $conn->real_escape_string($input['size']) : '';

if ($id <= 0 || empty($size)) {
    echo json_encode(["success" => false, "message" => "Dữ liệu không hợp lệ."]);
    exit;
}

// Lấy thông tin sản phẩm theo ID và size
$sql = "SELECT ID, TenSP, SL, ThuocTinh, HinhAnh, MoTa, GiaBan 
        FROM hanghoa 
        WHERE (ID = ? OR TenSP = (SELECT TenSP FROM hanghoa WHERE ID = ?)) 
        AND ThuocTinh = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $id, $id, $size);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
    echo json_encode([
        "success" => true,
        "product" => [
            "ID" => $product['ID'],
            "TenSP" => $product['TenSP'],
            "SL" => $product['SL'],
            "ThuocTinh" => $product['ThuocTinh'],
            "HinhAnh" => $product['HinhAnh'],
            "MoTa" => $product['MoTa'],
            "GiaBan" => $product['GiaBan']
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Không tìm thấy sản phẩm với size này."]);
}

$stmt->close();
$conn->close();
?>