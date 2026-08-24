<?php
// logout.php
header('Access-Control-Allow-Origin: *');

// 1. Ép PHP dùng lại PHPSESSID do App Flutter gửi lên (nếu có)
$headers = getallheaders();
if (isset($headers['Cookie'])) {
    preg_match('/PHPSESSID=([^;]+)/', $headers['Cookie'], $matches);
    if (!empty($matches[1])) {
        session_id($matches[1]); 
    }
}

session_start();
require_once 'includes/config.php'; 

$currentSessId = session_id();

try {
    // ---------------------------------------------------------
    // BƯỚC 1: TRUY NGƯỢC DATABASE ĐỂ XÓA TẬN GỐC TOKEN 
    // (Xử lý dứt điểm lỗi App gọi API không gửi kèm Cookie)
    // ---------------------------------------------------------
    $stmtGet = $pdo->prepare("SELECT token_selector FROM user_sessions WHERE session_id = ?");
    $stmtGet->execute([$currentSessId]);
    $targetSession = $stmtGet->fetch(PDO::FETCH_ASSOC);

    if ($targetSession && !empty($targetSession['token_selector'])) {
        $pdo->prepare("DELETE FROM user_tokens WHERE selector = ?")->execute([$targetSession['token_selector']]);
    }

    // Dọn dẹp thêm token trên trình duyệt (Dành cho bản Web)
    if (isset($_COOKIE['remember_token'])) {
        $parts = explode(':', $_COOKIE['remember_token']);
        if (count($parts) === 2) {
            $pdo->prepare("DELETE FROM user_tokens WHERE selector = ?")->execute([$parts[0]]);
        }
        setcookie('remember_token', '', time() - 3600, '/', "", false, true);
    }

    if (isset($_COOKIE['session'])) {
        setcookie('session', '', time() - 3600, '/');
    }

    // ---------------------------------------------------------
    // BƯỚC 2: CẮT ĐỨT HOÀN TOÀN THÔNG BÁO TỪ ACC CŨ
    // ---------------------------------------------------------
    // Xóa liên kết của thiết bị này trong bảng push để nó không nhận thông báo cũ nữa
    $pdo->prepare("DELETE FROM push_subscription WHERE session_id = ?")->execute([$currentSessId]);

    // ---------------------------------------------------------
    // BƯỚC 3: XÓA PHIÊN ĐĂNG NHẬP
    // ---------------------------------------------------------
    $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?")->execute([$currentSessId]);

} catch (Exception $e) {
    // Lỗi kết nối DB thì bỏ qua, vẫn tiến hành đăng xuất dọn dẹp bộ nhớ
}

// ---------------------------------------------------------
// BƯỚC 4: HỦY SESSION BỘ NHỚ PHP
// ---------------------------------------------------------
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// ---------------------------------------------------------
// BƯỚC 5: TRẢ VỀ JSON (CHO APP) HOẶC CHUYỂN HƯỚNG (CHO WEB)
// ---------------------------------------------------------
$is_api_request = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) 
                  || isset($_GET['api']) 
                  || (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Dart') !== false);

if ($is_api_request) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success', 'msg' => __('logout_safe_msg', 'Đã đăng xuất và gỡ thông báo an toàn!')]);
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>