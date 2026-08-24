<?php
// File: api_search_students.php - FINAL VERSION (EXACT MATCH PRIORITY)
require_once __DIR__ . '/includes/config.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$headers = getallheaders();
$auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';
if ($auth !== "Bearer " . SSO_SECRET_KEY) {
    http_response_code(401); die(json_encode([]));
}

$input = json_decode(file_get_contents('php://input'), true);
$query = isset($input['query']) ? trim($input['query']) : '';

if (strlen($query) < 2) { echo json_encode([]); exit; }

try {
    // LOGIC TÌM KIẾM MỚI:
    // 1. Loại trừ Teacher/Admin.
    // 2. ORDER BY: Dùng CASE WHEN để nếu username trùng khít với query thì đưa lên đầu (0), còn lại xếp sau (1).
    $sql = "SELECT username, full_name, avatar FROM users 
            WHERE role NOT IN ('TEACHER', 'ADMIN', 'teacher', 'admin') 
            AND (username LIKE :q OR full_name LIKE :q)
            ORDER BY 
                CASE WHEN username = :exact_q THEN 0 ELSE 1 END, 
                full_name ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':q' => "%$query%",
        ':exact_q' => $query // Tham số so sánh tuyệt đối
    ]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    foreach ($users as $u) {
        $avatar = $u['avatar'] ?? 'static/default.png';
        if ($avatar && strpos($avatar, 'http') === false) {
            $avatar = $domain . '/' . ltrim($avatar, '/');
        }
        $results[] = [
            'username' => $u['username'],
            'full_name' => $u['full_name'],
            'avatar' => $avatar
        ];
    }
    echo json_encode($results);
} catch (Exception $e) { echo json_encode([]); }
?>