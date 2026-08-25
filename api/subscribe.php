<?php
// api/subscribe.php - ĐĂNG KÝ VÀ CẬP NHẬT PUSH SUBSCRIPTION CHUẨN CHỐNG TRÙNG LẶP
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/config.php';
require_once '../includes/push_helper.php';

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'msg' => __('not_logged_in', 'Chưa đăng nhập')]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['endpoint'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'msg' => __('invalid_data', 'Dữ liệu không hợp lệ')]);
    exit;
}

try {
    $userId  = (int)$_SESSION['user']['id'];
    $endpoint = trim($input['endpoint']);
    $p256dh  = trim($input['keys']['p256dh'] ?? '');
    $auth    = trim($input['keys']['auth'] ?? '');
    $device  = trim($input['device_model'] ?? 'Unknown Device');
    $rawPlatform = trim($input['platform'] ?? 'web');

    // Chuẩn hóa platform ('app' hoặc 'web')
    $platform = PushHelper::isMobilePlatform($rawPlatform) ? 'app' : 'web';
    $currentSession = session_id();

    // 1. Xóa bản ghi cũ trùng endpoint (cho dù của user khác hay session cũ)
    $stmtDel = $pdo->prepare("DELETE FROM push_subscription WHERE endpoint = ?");
    $stmtDel->execute([$endpoint]);

    // 2. Xóa bản ghi cũ của phiên làm việc hiện tại nếu đổi endpoint
    if (!empty($currentSession)) {
        $stmtDelSess = $pdo->prepare("DELETE FROM push_subscription WHERE session_id = ? AND endpoint != ?");
        $stmtDelSess->execute([$currentSession, $endpoint]);
    }

    // 3. Chèn mới duy nhất bản ghi sạch
    $sql = "INSERT INTO push_subscription 
            (user_id, endpoint, p256dh, auth, device_model, platform, session_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $endpoint, $p256dh, $auth, $device, $platform, $currentSession]);

    // 4. Đồng bộ tên thiết bị sang bảng user_sessions cho session hiện hành
    if ($platform === 'app' && !empty($device) && $device !== 'Unknown Device') {
        $pdo->prepare("UPDATE user_sessions SET device_name = ? WHERE session_id = ?")
            ->execute([$device, $currentSession]);
    }

    echo json_encode([
        'status' => 'success', 
        'msg' => __('subscription_updated', 'Đã cập nhật đăng ký thông báo!')
    ]);

} catch (Exception $e) {
    error_log("Subscribe API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'msg' => __('server_error', 'Lỗi Server')]);
}
?>