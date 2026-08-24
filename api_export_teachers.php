<?php
// File: api_export_teachers.php (Đặt ở thư mục gốc Web PHP)

require_once __DIR__ . '/includes/config.php'; 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// 1. Kiểm tra bảo mật (Dùng chung SSO Secret Key với Python)
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// SSO_SECRET_KEY được định nghĩa trong includes/config.php
if ($auth_header !== "Bearer " . SSO_SECRET_KEY) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

try {
    // 2. Lấy tất cả Giáo viên và Admin từ bảng users chính
    $sql = "SELECT username, full_name, avatar, role FROM users WHERE role IN ('TEACHER', 'ADMIN')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Chuẩn hóa dữ liệu (Đặc biệt là URL Avatar)
    $results = [];
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $domain = $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    foreach ($users as $user) {
        $avatar = $user['avatar'] ?? 'static/default.png';

        // Nếu avatar là đường dẫn tương đối, nối thêm domain vào
        if ($avatar && strpos($avatar, 'http') === false) {
            $avatar = $domain . '/' . ltrim($avatar, '/');
        }

        $results[] = [
            'username' => $user['username'], // Dùng username làm khóa chính để map
            'full_name' => $user['full_name'],
            'avatar'    => $avatar,
            'role'      => $user['role']
        ];
    }

    echo json_encode($results);

} catch (Exception $e) {
    echo json_encode([]);
}
?>