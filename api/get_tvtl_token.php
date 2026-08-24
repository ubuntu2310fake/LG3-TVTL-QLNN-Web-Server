<?php
// api/get_tvtl_token.php
require_once '../includes/config.php';
require_once '../includes/jwt_helper.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => __('not_logged_in', 'Chưa đăng nhập')]);
    exit;
}

try {
    // Sử dụng hàm đã có sẵn trong jwt_helper.php
    $token = generate_sso_token($_SESSION['user']);
    
    echo json_encode([
        'status' => 'success',
        'token' => $token,
        'tvtl_base_url' => ''
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>