<?php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
    echo json_encode(['status' => 'error', 'msg' => __('no_permission', 'Không có quyền truy cập')]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($input['action']) && $input['action'] === 'unban') {
        $id = $input['id'];
        $stmt = $pdo->prepare("DELETE FROM banned_ips WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['status' => 'success', 'msg' => __('ip_unbanned_success', 'Đã mở khóa IP thành công!')]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => __('error_unbanning_ip', 'Có lỗi xảy ra khi mở khóa.')]);
        }
        exit;
    }
}

// Lấy danh sách IP
$stmt = $pdo->query("SELECT * FROM banned_ips ORDER BY banned_at DESC");
$ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gắn thêm flag is_expired cho từng IP
foreach ($ips as &$row) {
    $row['is_expired'] = (strtotime($row['expires_at']) < time());
}
echo json_encode(['status' => 'success', 'data' => $ips]);
?>