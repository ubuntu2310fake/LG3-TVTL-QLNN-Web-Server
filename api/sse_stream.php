<?php
// api/sse_stream.php — Server-Sent Events push endpoint
require_once __DIR__ . '/../includes/config.php';

// Chỉ cho phép user đã đăng nhập
if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo "data: {\"error\":\"unauthorized\"}\n\n";
    exit;
}

// Giải phóng lock session ngay lập tức để không block các request khác của cùng user (PHP Session Locking)
session_write_close();


// =====================================================
// SSE HEADERS
// =====================================================
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');   // Nginx: tắt buffer
header('Connection: keep-alive');
// Tắt compression để đảm bảo flush ngay
@ini_set('zlib.output_compression', 0);
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', 1);

// Cho phép chạy lâu (script SSE cần giữ kết nối)
set_time_limit(0);
ignore_user_abort(false);

function sse_flush() {
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

// Xóa buffer hiện tại
while (ob_get_level()) ob_end_clean();

// =====================================================
// Lấy ID event cuối cùng từ header
// =====================================================
$lastId = (int)($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0);
if ($lastId === 0) {
    // Bắt đầu từ event ID hiện tại — không replay lịch sử
    $row = $pdo->query("SELECT COALESCE(MAX(id), 0) FROM sse_events")->fetchColumn();
    $lastId = (int)$row;
}

// Dọn dẹp events cũ hơn 10 phút
try {
    $pdo->exec("DELETE FROM sse_events WHERE created_at < NOW() - INTERVAL 10 MINUTE");
} catch (Exception $e) {}

// =====================================================
// SEND INITIAL COMMENT (giữ kết nối ngay lập tức)
// =====================================================
echo ": connected\n\n";
echo "retry: 3000\n\n"; // Browser reconnect sau 3s nếu mất kết nối
sse_flush();

// =====================================================
// VÒNG LẶP PUSH CHÍNH
// =====================================================
$startTime    = time();
$maxRuntime   = 50;  // Giây — thoát trước khi PHP/nginx timeout (thường 60s)
$lastPing     = time();
$pingInterval = 15;  // Gửi keep-alive ping mỗi 15s
$pollInterval = 800; // Kiểm tra DB mỗi 0.8s (ms → usleep)

$stmt = $pdo->prepare(
    "SELECT id, event_type, payload FROM sse_events 
     WHERE id > ? 
     ORDER BY id ASC 
     LIMIT 30"
);

while (true) {
    // Thoát sau maxRuntime — browser sẽ tự reconnect theo `retry`
    if (time() - $startTime >= $maxRuntime) {
        echo ": reconnect\n\n";
        sse_flush();
        break;
    }

    // --- Fetch events mới ---
    try {
        $stmt->execute([$lastId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // DB lỗi — giữ kết nối, thử lại sau
        $events = [];
    }

    foreach ($events as $event) {
        echo "id: {$event['id']}\n";
        echo "event: {$event['event_type']}\n";
        echo "data: {$event['payload']}\n\n";
        $lastId = (int)$event['id'];
    }

    // --- Keep-alive ping ---
    if (time() - $lastPing >= $pingInterval) {
        echo ": ping\n\n";
        $lastPing = time();
    }

    if (!empty($events) || time() - $lastPing >= $pingInterval) {
        sse_flush();
    }

    usleep($pollInterval * 1000);
}
