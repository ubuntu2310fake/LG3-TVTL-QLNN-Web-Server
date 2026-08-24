<?php
// my_profile.php
require_once 'includes/config.php';
require_once 'includes/totp.php';
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

$user = $_SESSION['user'];
$userId = $user['id'];
$currentSessId = session_id();

// Lấy thông tin Student
$student = null;
if ($user['role'] == 'STUDENT' || $user['role'] == 'RED_FLAG') {
    $stmtS = $pdo->prepare("SELECT * FROM student WHERE code = ?");
    $stmtS->execute([$user['username']]);
    $student = $stmtS->fetch(PDO::FETCH_ASSOC);
}

// =========================================================
// XỬ LÝ POST (KICK THIẾT BỊ, UPLOAD ẢNH WEB, CẬP NHẬT INFO, 2FA)
// =========================================================
// POST logic moved to api/profile_api.php

// =========================================================
// LẤY DỮ LIỆU HIỂN THỊ LÊN TRÌNH DUYỆT
// =========================================================
// Lấy thêm platform và device_model từ bảng push_subscription
$sql = "
    SELECT s.*, p.id as push_id, p.platform as push_platform, p.device_model as push_device_model
    FROM user_sessions s 
    LEFT JOIN push_subscription p ON s.session_id = p.session_id 
    WHERE s.user_id = ? 
    ORDER BY s.last_active DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý đè tên thiết bị & đổi icon nếu đến từ App
foreach ($devices as &$d) {
    $d['icon_class'] = 'fa-desktop'; // Mặc định là máy tính
    
    // Nếu thiết bị cũ ghi nhận là Android/iPhone trên web
    if (stripos($d['device_name'], 'Android') !== false || stripos($d['device_name'], 'iPhone') !== false) {
        $d['icon_class'] = 'fa-mobile-alt';
    }
    
    // BYPASS: Lấy tên thật từ thiết bị App thông qua push_subscription
    if (isset($d['push_platform']) && $d['push_platform'] === 'app' && !empty($d['push_device_model'])) {
        $d['device_name'] = $d['push_device_model'];
        $d['icon_class'] = 'fa-mobile-alt'; // Đổi icon thành điện thoại
    }
}
unset($d); // Hủy tham chiếu

// FIX CHUẨN: LẤY AVATAR, EMAIL VÀ 2FA MỚI NHẤT TỪ DB
$stmtU = $pdo->prepare("SELECT avatar, email, email_verified, two_factor_enabled, two_factor_secret FROM users WHERE id = ?");
$stmtU->execute([$userId]);
$latestDbUser = $stmtU->fetch(PDO::FETCH_ASSOC);

$is_2fa_enabled = (int)($latestDbUser['two_factor_enabled'] ?? 0);
$two_factor_secret = $latestDbUser['two_factor_secret'] ?? '';
$user_email = $latestDbUser['email'] ?? '';
$is_email_verified = (int)($latestDbUser['email_verified'] ?? 0);

$raw_avatar = $latestDbUser['avatar'] ?? $user['avatar'] ?? $user['image_url'] ?? '';
if (empty($raw_avatar) || strpos($raw_avatar, 'default.png') !== false) {
    $avatar_url = 'static/default.png';
} else {
    $avatar_url = $raw_avatar;
    // Ép phiên Session hiện hành phải nhận link ảnh mới để Sidebar (Header) cập nhật theo
    $_SESSION['user']['avatar'] = $raw_avatar;
    $_SESSION['user']['image_url'] = $raw_avatar;
}

function syncAvatarToPython($username, $avatarUrl) {
    // Đã đóng cURL đồng bộ sang Python Flask
    return;
}

$vapid_key = defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '';

require_once 'views/my_profile_view.php';