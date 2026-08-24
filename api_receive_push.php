<?php
// api_receive_push.php - PHIÊN BẢN CÓ DEBUG LOG
require_once 'includes/config.php';

// Hàm ghi log debug
function writeLog($msg) {
    file_put_contents('debug_push.txt', "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
}

$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';

// Check Key
if ($auth !== 'Bearer ' . SSO_SECRET_KEY) {
    writeLog("❌ " . __('auth_err_key_mismatch', 'Lỗi Auth: Key không khớp.') . " Nhận được: $auth");
    http_response_code(401);
    echo json_encode(['status' => 'error', 'msg' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    writeLog("❌ " . __('err_no_json_data', 'Lỗi: Không có dữ liệu JSON gửi sang.'));
    echo json_encode(['status' => 'error', 'msg' => 'No data']);
    exit;
}

try {
    $payload = json_encode($input);
    $type = $input['type'] ?? 'PSYCHOLOGY'; 
    
    // Ghi log để kiểm tra dữ liệu nhận được
    writeLog("✅ " . __('receive_request', 'Nhận yêu cầu:') . " Loại=$type | Nội dung=" . json_encode($input, JSON_UNESCAPED_UNICODE));

    $stmt = $pdo->prepare("INSERT INTO notification_queue (type, payload, status, created_at) VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$type, $payload]);
    
    $newId = $pdo->lastInsertId();
    writeLog("-> " . __('inserted_to_queue', 'Đã Insert vào Queue thành công. Job ID: ') . $newId);

    echo json_encode(['status' => 'success', 'job_id' => $newId]);
} catch (Exception $e) {
    writeLog("💥 Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>