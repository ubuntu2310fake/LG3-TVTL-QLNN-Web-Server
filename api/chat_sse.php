<?php
// api/chat_sse.php — SSE stream riêng dành cho chat psychology_messages
// Client lắng nghe: khi có tin nhắn mới → server push ngay, không cần polling
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo "data: {\"error\":\"unauthorized\"}\n\n";
    exit;
}

$my_id     = (int)$_SESSION['user']['id'];
$partner_id = (int)($_GET['partner_id'] ?? 0);

session_write_close(); // Giải phóng session lock ngay

// SSE Headers
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');
@ini_set('zlib.output_compression', 0);
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', 1);

set_time_limit(0);
ignore_user_abort(false);

function chat_flush() {
    if (ob_get_level() > 0) ob_flush();
    flush();
}

while (ob_get_level()) ob_end_clean();

// Gửi comment kết nối ban đầu
echo ": connected\n\n";
echo "retry: 3000\n\n";
chat_flush();

// Lấy ID tin nhắn mới nhất để không replay tin cũ
$lastMsgId = 0;
if ($partner_id > 0) {
    $lastMsgId = (int)$pdo->prepare("
        SELECT COALESCE(MAX(id), 0) FROM psychology_messages
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
    ")->execute([$my_id, $partner_id, $partner_id, $my_id])
    ? $pdo->query("
        SELECT COALESCE(MAX(id), 0) FROM psychology_messages
        WHERE (sender_id = $my_id AND receiver_id = $partner_id)
           OR (sender_id = $partner_id AND receiver_id = $my_id)
    ")->fetchColumn() : 0;
}

$startTime    = time();
$maxRuntime   = 50;   // Thoát sau 50s → browser tự reconnect
$lastPing     = time();
$pingInterval = 15;
$pollInterval = 600;  // poll DB mỗi 600ms

// Prepare câu truy vấn tin nhắn mới
$stmtMsg = $pdo->prepare("
    SELECT m.id, m.sender_id, m.receiver_id, m.content, m.is_read,
           m.created_at, m.reply_id, m.reactions, m.is_anonymous,
           r.content as reply_content
    FROM psychology_messages m
    LEFT JOIN psychology_messages r ON m.reply_id = r.id
    WHERE m.id > ?
      AND (
          (m.sender_id = ? AND m.receiver_id = ?)
          OR (m.sender_id = ? AND m.receiver_id = ?)
      )
    ORDER BY m.id ASC
    LIMIT 20
");

// Prepare cập nhật is_read
$stmtRead = $pdo->prepare("
    UPDATE psychology_messages SET is_read = 1
    WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
");

while (true) {
    if (time() - $startTime >= $maxRuntime) {
        echo ": reconnect\n\n";
        chat_flush();
        break;
    }

    // Fetch tin nhắn mới
    if ($partner_id > 0) {
        try {
            $stmtMsg->execute([$lastMsgId, $my_id, $partner_id, $partner_id, $my_id]);
            $newMsgs = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $newMsgs = [];
        }

        if (!empty($newMsgs)) {
            // Đánh dấu đã đọc
            try { $stmtRead->execute([$my_id, $partner_id]); } catch(Exception $e) {}

            foreach ($newMsgs as $msg) {
                $lastMsgId = (int)$msg['id'];
                echo "id: {$msg['id']}\n";
                echo "event: new_message\n";
                echo "data: " . json_encode($msg, JSON_UNESCAPED_UNICODE) . "\n\n";
            }
            chat_flush();
        }
    }

    // Keep-alive ping
    if (time() - $lastPing >= $pingInterval) {
        echo ": ping\n\n";
        $lastPing = time();
        chat_flush();
    }

    usleep($pollInterval * 1000);
}
?>
