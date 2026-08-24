<?php
// api/session_heartbeat.php — Chỉ bắn SSE ping, KHÔNG làm gì với user_sessions
// Logic đăng ký thiết bị đã chuyển hoàn toàn sang login_api.php
require_once '../includes/config.php';

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error']);
    exit;
}

$uid = $_SESSION['user']['id'];
session_write_close(); // Giải phóng session lock ngay

// Debounce: Chỉ bắn SSE heartbeat event tối đa 1 lần / 30 giây per user
// (tránh spam DB sse_events)
$debounce_key = "heartbeat_last_{$uid}";
$now = time();

if ($redis_connected) {
    $last = $redis->get($debounce_key);
    if ($last && ($now - (int)$last) < 30) {
        // Chưa đủ 30 giây, bỏ qua
        echo json_encode(['status' => 'ok', 'cached' => true]);
        exit;
    }
    $redis->setex($debounce_key, 60, $now);
}

// Bắn SSE event "heartbeat" để sse_stream.php push xuống client
try {
    $pdo->prepare(
        "INSERT INTO sse_events (event_type, payload, created_at)
         VALUES ('heartbeat', ?, NOW())"
    )->execute([json_encode(['uid' => $uid, 'ts' => $now])]);
} catch (Exception $e) {
    // Bỏ qua lỗi insert SSE
}

echo json_encode(['status' => 'ok']);
?>