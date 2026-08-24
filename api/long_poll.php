<?php
require_once '../includes/config.php';
session_write_close(); // Giải phóng session lock ngay lập tức để không làm nghẽn các request khác

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

header("Content-Type: application/json");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
if ($lastId <= 0) {
    $stmt = $pdo->query("SELECT MAX(id) FROM sse_events");
    $lastId = (int)$stmt->fetchColumn();
}

$maxRuntime = 25; // 25 seconds for long polling
$startTime = time();

while (true) {
    $username = $_SESSION['user']['username'] ?? null;
    if ($username) {
        $stmt = $pdo->prepare("SELECT * FROM sse_events WHERE id > ? AND (scope = 'all' OR scope = ?) ORDER BY id ASC");
        $stmt->execute([$lastId, 'user:' . $username]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM sse_events WHERE id > ? AND scope = 'all' ORDER BY id ASC");
        $stmt->execute([$lastId]);
    }
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($events) > 0) {
        echo json_encode(['status' => 'success', 'events' => $events, 'last_id' => end($events)['id']]);
        exit;
    }

    if (time() - $startTime >= $maxRuntime) {
        echo json_encode(['status' => 'timeout', 'last_id' => $lastId]);
        exit;
    }

    usleep(500000); // 0.5s
}
