<?php
// Tên file: api/login_api.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/config.php';
require_once '../includes/totp.php';

// Ép PHP dùng lại PHPSESSID do Flutter gửi lên (nếu có)
$headers = getallheaders();
if (isset($headers['Cookie'])) {
    preg_match('/PHPSESSID=([^;]+)/', $headers['Cookie'], $matches);
    if (!empty($matches[1])) {
        session_id($matches[1]); 
    }
}

if (session_status() === PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) { session_start(); } }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_token = trim($_POST['remember_token'] ?? '');
    $two_factor_code = trim($_POST['two_factor_code'] ?? $_POST['otp'] ?? '');
    // Bắt thêm cờ remember_me từ app gửi lên
    $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] === '1';

    $user = null;
    $selector = null;
    $login_success = false;
    $new_remember_token = '';

    // KỊCH BẢN 1: ĐĂNG NHẬP BẰNG REMEMBER TOKEN (Auto-login từ Splash Screen)
    if (!empty($remember_token) && strpos($remember_token, ':') !== false) {
        list($selector, $validator) = explode(':', $remember_token);
        $stmt = $pdo->prepare("SELECT * FROM user_tokens WHERE selector = ? AND expiry > NOW()");
        $stmt->execute([$selector]);
        $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tokenRow && hash_equals($tokenRow['hashed_validator'], hash('sha256', $validator))) {
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmtUser->execute([$tokenRow['user_id']]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $login_success = true;
                $new_remember_token = $remember_token; // Giữ nguyên token cũ để trả về
            }
        }
    } 
    // KỊCH BẢN 2: ĐĂNG NHẬP BẰNG TÀI KHOẢN / MẬT KHẨU
    else if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // KIỂM TRA BẢO MẬT 2FA
            if (!empty($user['two_factor_enabled']) && !empty($user['two_factor_secret'])) {
                if (empty($two_factor_code)) {
                    echo json_encode([
                        'status' => '2fa_required',
                        'msg' => __('2fa_required_msg', 'Tài khoản của bạn đã bật xác thực 2FA. Vui lòng nhập mã OTP 6 chữ số!')
                    ]);
                    exit;
                }
                if (!TOTP::verifyCode($user['two_factor_secret'], $two_factor_code)) {
                    echo json_encode([
                        'status' => 'error',
                        'msg' => __('invalid_2fa_code', 'Mã xác thực 2FA không chính xác hoặc đã hết hạn!')
                    ]);
                    exit;
                }
            }

            $login_success = true;
            
            // Chỉ tạo Token mới nếu user có chọn "Nhớ đăng nhập"
            if ($remember_me) {
                $selector = bin2hex(random_bytes(16));
                $validator = bin2hex(random_bytes(32));
                $hashed_validator = hash('sha256', $validator);
                $expiry = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 ngày
                
                $pdo->prepare("INSERT INTO user_tokens (user_id, selector, hashed_validator, expiry) VALUES (?, ?, ?, ?)")->execute([$user['id'], $selector, $hashed_validator, $expiry]);
                
                $new_remember_token = "$selector:$validator";
                
                // Set cookie giống hệt web
                setcookie('remember_token', $new_remember_token, time() + (86400 * 30), "/", "", false, true);
            }
        }
    }

    // XỬ LÝ KHI ĐĂNG NHẬP THÀNH CÔNG
    if ($login_success) {
        $_SESSION['user'] = $user;
        $sessId = session_id();
        
        // Lấy IP thật cho API App
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        
        // Cập nhật last_active cho user
        $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")->execute([$user['id']]);
        
        // === ĐĂNG KÝ THIẾT BỊ KHI ĐĂNG NHẬP (1 lần duy nhất, không lặp) ===
        $ua_raw = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Lấy tên thiết bị chi tiết từ POST (App gửi lên) hoặc Detect từ User-Agent
        $dev = $_POST['device_name'] ?? 'Thiết bị lạ';
        if ($dev === 'Thiết bị lạ') {
            if (strpos($ua_raw, 'Flutter') !== false)         $dev = 'App Di Động';
            elseif (strpos($ua_raw, 'iPhone') !== false)      $dev = 'iPhone';
            elseif (strpos($ua_raw, 'iPad') !== false)        $dev = 'iPad';
            elseif (strpos($ua_raw, 'Android') !== false)     $dev = 'Android';
            elseif (strpos($ua_raw, 'Windows') !== false)     $dev = 'Windows PC';
            elseif (strpos($ua_raw, 'Macintosh') !== false)   $dev = 'MacBook';
            elseif (strpos($ua_raw, 'Linux') !== false)       $dev = 'Linux PC';
            elseif (strpos($ua_raw, 'CriOS') !== false)       $dev = 'Chrome iOS';
        }

        // Xóa session cũ cùng loại thiết bị của user này (tránh lặp)
        $pdo->prepare("
            DELETE FROM user_sessions
            WHERE user_id = ? AND device_name = ? AND session_id != ?
        ")->execute([$user['id'], $dev, $sessId]);

        // UPSERT session: cập nhật nếu đã có session_id này, insert mới nếu chưa có
        $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, device_name, token_selector, last_active)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                ip_address = VALUES(ip_address),
                last_active = NOW(),
                token_selector = VALUES(token_selector)
        ")->execute([$user['id'], $sessId, $ip, $ua_raw, $dev, $selector]);

        // Đồng bộ push_subscription nếu đổi session_id
        if (!empty($old_session_id) && $old_session_id !== $sessId) {
            $pdo->prepare("UPDATE push_subscription SET session_id = ? WHERE session_id = ?")
                ->execute([$sessId, $old_session_id]);
        }

        // Trả cookie PHPSESSID về cho an toàn thêm
        setcookie('PHPSESSID', $sessId, 0, "/", "", false, true);

        echo json_encode([
            'status' => 'success',
            'session_id' => $sessId,
            'remember_token' => $new_remember_token, 
            'must_change_password' => (($user['is_default_password'] ?? 'off') === 'on'),
            'user' => [
                'id' => $user['id'], 
                'full_name' => $user['full_name'], 
                'role' => $user['role'], 
                'avatar' => $user['avatar'] ?? '',
                'must_change_password' => (($user['is_default_password'] ?? 'off') === 'on')
            ]
        ]);
    }
}
?>