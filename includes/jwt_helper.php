<?php
// includes/jwt_helper.php

function generate_sso_token($user) {
    // 1. Gọi trực tiếp biến SECRET KEY từ config.php
    $secret = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : 'default_fallback_secret_123'; 
    
    // Header
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    
    // 2. Tự động nhận diện domain hiện tại để set link ảnh truy cập tuyệt đối cho App Python
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $domain = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . "://" . $domain;

    $avatar = $user['avatar'] ?? 'static/default.png';
    // Đảm bảo avatar là một URL tuyệt đối (http...)
    if ($avatar && strpos($avatar, 'http') === false) {
        $avatar = $base_url . "/" . ltrim($avatar, '/');
    }

    // Payload
    $payload = json_encode([
        'sbd' => $user['username'],
        'name' => $user['full_name'],
        'role' => $user['role'],
        'avatar' => $avatar,
        'exp' => time() + 300 // Token hết hạn sau 5 phút (Chống copy link)
    ]);

    // Encode
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    // Sign
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}
?>