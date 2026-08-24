<?php
// includes/sse_push.php — Helper để đẩy event vào hàng đợi SSE

/**
 * Đẩy một sự kiện vào hàng đợi SSE (bảng sse_events).
 * Client đang kết nối sẽ nhận ngay lập tức qua EventSource.
 *
 * @param PDO    $pdo      DB connection
 * @param string $type     Tên event (vd: 'violation_new', 'violation_deleted')
 * @param array  $payload  Dữ liệu kèm theo (sẽ được json_encode)
 * @param string $scope    'all' | 'role:TEACHER' | 'user:username' (chưa dùng — cho tương lai)
 */
function sse_push(PDO $pdo, string $type, array $payload, string $scope = 'all'): void {
    try {
        $pdo->prepare(
            "INSERT INTO sse_events (event_type, payload, scope) VALUES (?, ?, ?)"
        )->execute([
            $type,
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $scope,
        ]);
    } catch (\Exception $e) {
        // Fail silently — SSE là bonus, không được làm gián đoạn luồng chính
    }
}
