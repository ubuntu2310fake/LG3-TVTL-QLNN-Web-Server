<?php
// api/profile_api.php
require_once '../includes/config.php';
require_once '../includes/totp.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => __('not_logged_in', 'Chưa đăng nhập')]); exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];
$currentSessId = session_id();
session_write_close();

// --- XỬ LÝ 1: UPLOAD ĐỔI AVATAR (MỚI) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = "u{$userId}_" . time() . "." . $ext;
    
    $targetDir = "../static/uploads/avatars/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $targetPath = $targetDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath) || copy($file['tmp_name'], $targetPath)) {
        @unlink($file['tmp_name']);
        $dbPath = "static/uploads/avatars/" . $newFileName;
        
        // 1. Cập nhật bảng users (cột avatar)
        $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$dbPath, $userId]);
        
        // 2. Cập nhật bảng student nếu có
        $stmtS = $pdo->prepare("SELECT id FROM student WHERE code = ?");
        $stmtS->execute([$user['username']]);
        if ($s = $stmtS->fetch()) {
            $pdo->prepare("UPDATE student SET image_url = ? WHERE id = ?")->execute([$dbPath, $s['id']]);
        }
        
        // 3. Đồng bộ sang Python AI (Đã đóng vì Python Flask đã tắt)
        /*
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $fullUrl = $domain . "/" . $dbPath;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => $user['username'], 'avatar' => $fullUrl]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer khoa_bi_mat_ket_noi_hai_app_123456_secure']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_TIMEOUT, 2); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch); curl_close($ch);
        */
        
        echo json_encode(['status'=>'success', 'msg'=>__('avatar_changed', 'Đã đổi Avatar!'), 'new_avatar_url' => $dbPath]);
    } else {
        echo json_encode(['status'=>'error', 'msg'=>__('avatar_upload_error', 'Lỗi upload ảnh lên server')]);
    }
    exit;
}

// --- XỬ LÝ 1.5: XÓA AVATAR VỀ MẶC ĐỊNH ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_avatar') {
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    
    // Xóa file ảnh cũ nếu không phải ảnh default
    if ($u && $u['avatar'] && !str_contains($u['avatar'], 'default.png')) {
        if (file_exists("../" . $u['avatar'])) unlink("../" . $u['avatar']);
    }
    
    // Xóa trong bảng users
    $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?")->execute([$userId]);
    
    // Xóa trong bảng student
    $stmtS = $pdo->prepare("SELECT id FROM student WHERE code = ?");
    $stmtS->execute([$user['username']]);
    if ($s = $stmtS->fetch()) {
        $pdo->prepare("UPDATE student SET image_url = NULL WHERE id = ?")->execute([$s['id']]);
    }
    
    // Đồng bộ sang Python AI (Đã đóng vì Python Flask đã tắt)
    /*
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $defaultUrl = $domain . "/static/default.png";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => $user['username'], 'avatar' => $defaultUrl]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer khoa_bi_mat_ket_noi_hai_app_123456_secure']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_TIMEOUT, 2); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch); curl_close($ch);
    */
    
    echo json_encode(['status'=>'success', 'msg'=>__('avatar_deleted', 'Đã xóa ảnh đại diện!')]); 
    exit;
}

// --- XỬ LÝ 2: KICK THIẾT BỊ (GIỮ NGUYÊN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_device') {
    $sid = $_POST['device_id'];
    if ($sid === $currentSessId) { echo json_encode(['status'=>'error', 'msg'=>__('cannot_kick_self', 'Không thể tự kick!')]); exit; }
    
    $stmtGet = $pdo->prepare("SELECT token_selector FROM user_sessions WHERE session_id = ?");
    $stmtGet->execute([$sid]);
    $targetSession = $stmtGet->fetch(PDO::FETCH_ASSOC);
    if ($targetSession && !empty($targetSession['token_selector'])) {
        $pdo->prepare("DELETE FROM user_tokens WHERE selector = ?")->execute([$targetSession['token_selector']]);
    }
    $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?")->execute([$sid]);
    $pdo->prepare("DELETE FROM push_subscription WHERE session_id = ?")->execute([$sid]);
    echo json_encode(['status'=>'success', 'msg'=>__('kicked_device', 'Đã đăng xuất thiết bị kia!')]); exit;
}


    // Xóa bộ đệm output để trả về JSON sạch
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    try {
        // --- 0.0 XỬ LÝ LIÊN KẾT EMAIL & GỬI MÃ OTP ---
        if (isset($_POST['action']) && $_POST['action'] === 'send_email_otp') {
            $email = trim($_POST['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'msg' => __('invalid_email', 'Địa chỉ email không hợp lệ!')]);
                exit;
            }

            $stmtChk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND email_verified = 1 AND id != ?");
            $stmtChk->execute([$email, $userId]);
            if ($stmtChk->fetch()) {
                echo json_encode(['status' => 'error', 'msg' => __('email_already_used', 'Email này đã được liên kết với một tài khoản khác!')]);
                exit;
            }

            $otp = sprintf("%06d", rand(100000, 999999));
            $expires = date('Y-m-d H:i:s', time() + 600); // 10 phút

            $pdo->prepare("UPDATE users SET email = ?, otp_code = ?, otp_expires = ? WHERE id = ?")
                ->execute([$email, $otp, $expires, $userId]);

            $subject = '📧 MÃ XÁC NHẬN LIÊN KẾT EMAIL - LG3 SUPER APP';
            $htmlBody = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
                <style>
                    body, table, td, p, h1, h2, a, span { font-family: -apple-system, BlinkMacSystemFont, "Be Vietnam Pro", sans-serif !important; }
                </style>
            </head>
            <body style="margin: 0; padding: 0; background-color: #f5f7fa; font-family: -apple-system, BlinkMacSystemFont, "Be Vietnam Pro", sans-serif !important; color: #1d1d1f; -webkit-font-smoothing: antialiased;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f5f7fa; padding: 25px 10px;">
                    <tr><td align="center">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 480px; background-color: #ffffff; border-radius: 14px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 14px rgba(0,0,0,0.06);">
                            <tr>
                                <td style="background: linear-gradient(135deg, #005fba 0%, #00478c 100%); padding: 26px 20px; text-align: center;">
                                    <img src="https://noiboqlnn.testifiyonline.xyz/lg3100100.png" width="64" height="64" alt="LG3 Logo" style="border-radius: 50%; border: 3px solid #ffffff; display: block; margin: 0 auto 10px auto;">
                                    <h1 style="margin: 0; color: #ffffff; font-size: 19px; font-weight: 800;">THPT LẠNG GIANG SỐ 3</h1>
                                    <p style="margin: 3px 0 0 0; color: #bae6fd; font-size: 12px; font-weight: 600; text-transform: uppercase;">LG3 Super App • Liên kết Email</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 26px 22px;">
                                    <h2 style="margin: 0 0 12px 0; color: #005fba; font-size: 17px; font-weight: 700;">📧 XÁC NHẬN LIÊN KẾT EMAIL</h2>
                                    <p style="margin: 0 0 16px 0; color: #475569; font-size: 13.5px; line-height: 1.6;">
                                        Mã OTP xác nhận liên kết email của bạn là:
                                    </p>
                                    <div style="text-align: center; margin: 22px 0;">
                                        <span style="display: inline-block; background-color: #eff6ff; border: 2px dashed #005fba; color: #005fba; font-size: 28px; font-weight: 800; padding: 12px 28px; border-radius: 10px; letter-spacing: 5px;">' . $otp . '</span>
                                    </div>
                                    <p style="margin: 0; color: #64748b; font-size: 12px; font-style: italic; text-align: center;">
                                        * Mã OTP có hiệu lực trong 10 phút. Tuyệt đối không tiết lộ mã cho ai khác.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="background-color: #f1f5f9; padding: 16px 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">
                                    <strong style="color: #005fba;">TRƯỜNG THPT LẠNG GIANG SỐ 3</strong><br>
                                    THÔN MỸ LỘC, XÃ TIÊN LỤC, TỈNH BẮC NINH | support@testifiyonline.xyz
                                </td>
                            </tr>
                        </table>
                    </td></tr>
                </table>
            </body>
            </html>';

            $mailRes = send_resend_email($email, $subject, $htmlBody);
            if ($mailRes['status']) {
                echo json_encode(['status' => 'success', 'msg' => __('otp_sent_to_email', 'Đã gửi mã xác nhận OTP đến email ') . $email]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => __('failed_send_email', 'Gửi email thất bại: ') . ($mailRes['msg'] ?? '')]);
            }
            exit;
        }

        if (isset($_POST['action']) && $_POST['action'] === 'verify_email_otp') {
            $otp = trim($_POST['otp'] ?? '');
            $stmtU = $pdo->prepare("SELECT email, otp_code, otp_expires FROM users WHERE id = ?");
            $stmtU->execute([$userId]);
            $uData = $stmtU->fetch(PDO::FETCH_ASSOC);

            if (empty($uData['otp_code']) || $uData['otp_code'] !== $otp) {
                echo json_encode(['status' => 'error', 'msg' => __('invalid_otp', 'Mã OTP không chính xác!')]);
                exit;
            }

            if (strtotime($uData['otp_expires']) < time()) {
                echo json_encode(['status' => 'error', 'msg' => __('otp_expired', 'Mã OTP đã hết hạn!')]);
                exit;
            }

            $pdo->prepare("UPDATE users SET email_verified = 1, otp_code = NULL, otp_expires = NULL WHERE id = ?")->execute([$userId]);
            $_SESSION['user']['email'] = $uData['email'];
            $_SESSION['user']['email_verified'] = 1;

            echo json_encode(['status' => 'success', 'msg' => __('email_linked_success', 'Đã liên kết và xác thực email thành công!')]);
            exit;
        }

        if (isset($_POST['action']) && $_POST['action'] === 'unlink_email') {
            $pdo->prepare("UPDATE users SET email = NULL, email_verified = 0, otp_code = NULL, otp_expires = NULL WHERE id = ?")->execute([$userId]);
            $_SESSION['user']['email'] = null;
            $_SESSION['user']['email_verified'] = 0;
            echo json_encode(['status' => 'success', 'msg' => __('email_unlinked', 'Đã hủy liên kết email!')]);
            exit;
        }

        // --- 0. XỬ LÝ 2FA (BẢO MẬT 2 YẾU TỐ) ---
        if (isset($_POST['action']) && $_POST['action'] === 'get_2fa_setup') {
            $stmtU = $pdo->prepare("SELECT two_factor_secret, two_factor_enabled FROM users WHERE id = ?");
            $stmtU->execute([$userId]);
            $uData = $stmtU->fetch(PDO::FETCH_ASSOC);

            $secret = $uData['two_factor_secret'] ?? '';
            $enabled = (int)($uData['two_factor_enabled'] ?? 0);

            if (empty($secret)) {
                $secret = TOTP::generateSecret();
                $pdo->prepare("UPDATE users SET two_factor_secret = ? WHERE id = ?")->execute([$secret, $userId]);
                $_SESSION['user']['two_factor_secret'] = $secret;
            }

            $qrCode = TOTP::getQRCodeDataUri($secret, $user['username']);
            $otpUri = TOTP::getProvisioningUri($secret, $user['username'], 'THPT LG3');
            echo json_encode([
                'status' => 'success',
                'secret' => $secret,
                'qr_code' => $qrCode,
                'otp_uri' => $otpUri,
                'enabled' => $enabled
            ]);
            exit;
        }

        if (isset($_POST['action']) && $_POST['action'] === 'enable_2fa') {
            $code = trim($_POST['code'] ?? '');
            $stmtU = $pdo->prepare("SELECT two_factor_secret FROM users WHERE id = ?");
            $stmtU->execute([$userId]);
            $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
            $secret = $uData['two_factor_secret'] ?? '';

            if (empty($secret)) {
                echo json_encode(['status' => 'error', 'msg' => __('2fa_secret_missing', 'Chưa khởi tạo khoá Secret 2FA!')]);
                exit;
            }

            if (TOTP::verifyCode($secret, $code)) {
                $pdo->prepare("UPDATE users SET two_factor_enabled = 1 WHERE id = ?")->execute([$userId]);
                $_SESSION['user']['two_factor_enabled'] = 1;
                echo json_encode(['status' => 'success', 'msg' => __('2fa_enabled_success', 'Đã bật Bảo mật 2 Yếu tố (2FA) thành công!')]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => __('invalid_2fa_code', 'Mã xác thực 2FA không chính xác hoặc đã hết hạn!')]);
            }
            exit;
        }

        if (isset($_POST['action']) && $_POST['action'] === 'disable_2fa') {
            $code = trim($_POST['code'] ?? '');
            $password = $_POST['password'] ?? '';
            
            $stmtU = $pdo->prepare("SELECT password_hash, two_factor_secret FROM users WHERE id = ?");
            $stmtU->execute([$userId]);
            $uData = $stmtU->fetch(PDO::FETCH_ASSOC);
            $secret = $uData['two_factor_secret'] ?? '';

            $verified = false;
            if (!empty($code) && !empty($secret) && TOTP::verifyCode($secret, $code)) {
                $verified = true;
            } elseif (!empty($password) && password_verify($password, $uData['password_hash'])) {
                $verified = true;
            }

            if ($verified) {
                $pdo->prepare("UPDATE users SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?")->execute([$userId]);
                $_SESSION['user']['two_factor_enabled'] = 0;
                $_SESSION['user']['two_factor_secret'] = null;
                echo json_encode(['status' => 'success', 'msg' => __('2fa_disabled_success', 'Đã tắt Bảo mật 2 Yếu tố (2FA) thành công!')]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => __('invalid_2fa_code_or_password', 'Mã xác thực hoặc mật khẩu không chính xác!')]);
            }
            exit;
        }
        // 1. KICK SESSION (ĐÁ THIẾT BỊ)
        if (isset($_POST['action']) && $_POST['action'] === 'delete_device') {
            $sid = $_POST['device_id'];
            if ($sid === $currentSessId) { echo json_encode(['status'=>'error', 'msg'=>__('cannot_kick_self', 'Không thể tự kick thiết bị đang sử dụng!')]); exit; }

            $stmtGet = $pdo->prepare("SELECT token_selector FROM user_sessions WHERE session_id = ?");
            $stmtGet->execute([$sid]);
            $targetSession = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if ($targetSession && !empty($targetSession['token_selector'])) {
                $pdo->prepare("DELETE FROM user_tokens WHERE selector = ?")->execute([$targetSession['token_selector']]);
            }

            $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM push_subscription WHERE session_id = ?")->execute([$sid]);

            $sessPath = session_save_path() ?: sys_get_temp_dir();
            $sessFile = $sessPath . DIRECTORY_SEPARATOR . "sess_" . $sid;
            if (file_exists($sessFile)) @unlink($sessFile);
            
            echo json_encode(['status'=>'success', 'msg'=>__('device_logged_out', 'Đã đăng xuất thiết bị thành công!')]); exit;
        }

        // 2. TEST PUSH
        if (isset($_POST['action']) && $_POST['action'] === 'test_push') {
            $sid = $_POST['device_id'];
            $stmt = $pdo->prepare("SELECT id FROM push_subscription WHERE session_id = ?");
            $stmt->execute([$sid]);
            
            if ($stmt->fetch()) {
                $push_sent = sendPushToUser($pdo, $userId, __('push_test_title', '🚀 Thử nghiệm Push LG3'), __('push_test_body', 'Đây là thông báo test từ thiết bị của bạn!'));
                if ($push_sent) { echo json_encode(['status'=>'success', 'msg'=>__('push_test_success', 'Đã gửi tín hiệu test thành công!')]); } 
                else { echo json_encode(['status'=>'error', 'msg'=>__('push_test_failed', 'Gửi thất bại! Hãy kiểm tra lại file cấu hình Firebase.')]); }
                exit;
            } else { echo json_encode(['status'=>'error', 'msg'=>__('push_not_enabled', 'Thiết bị này chưa bật thông báo!')]); exit; }
        }

        // 3. XÓA AVATAR TRÊN WEB
        if (isset($_POST['delete_image']) && $_POST['delete_image'] === '1') {
            $currentPath = $_SESSION['user']['avatar'] ?? $_SESSION['user']['image_url'] ?? '';
            if ($currentPath && file_exists($currentPath) && strpos($currentPath, 'default.png') === false) {
                @unlink($currentPath); 
            }
            
            $defaultPath = 'static/default.png';
            $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?")->execute([$userId]);
            if ($student) { $pdo->prepare("UPDATE student SET image_url = NULL WHERE id = ?")->execute([$student['id']]); }

            $_SESSION['user']['avatar'] = $defaultPath;
            $_SESSION['user']['image_url'] = $defaultPath;
            
            if (function_exists('syncAvatarToPython')) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                $domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $fullUrl = $domain . "/" . $defaultPath; 
                syncAvatarToPython($user['username'], $fullUrl);
            }

            echo json_encode(['status' => 'success', 'msg' => __('avatar_deleted', 'Đã xóa ảnh đại diện!'), 'new_avatar_url' => $defaultPath]);
            exit;
        }

        // 4. UPLOAD AVATAR TRÊN WEB
        if (isset($_FILES['image']) || isset($_FILES['avatar'])) {
            $file = $_FILES['image'] ?? $_FILES['avatar'];
            if ($file['error'] === 0) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    
                    $oldPath = $_SESSION['user']['avatar'] ?? $_SESSION['user']['image_url'] ?? '';
                    if ($oldPath && file_exists($oldPath) && strpos($oldPath, 'default.png') === false) { @unlink($oldPath); }
                    
                    $fileName = "u{$userId}_" . time() . ".jpg";
                    $targetDir = 'static/uploads/avatars';
                    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                    
                    $newPath = "$targetDir/$fileName";
                    
                    if (move_uploaded_file($file['tmp_name'], $newPath) || copy($file['tmp_name'], $newPath)) {
                        @unlink($file['tmp_name']);
                        $pdo->prepare("UPDATE users SET avatar=? WHERE id=?")->execute([$newPath, $userId]);
                        if ($student) { $pdo->prepare("UPDATE student SET image_url=? WHERE id=?")->execute([$newPath, $student['id']]); }
                        
                        $_SESSION['user']['avatar'] = $newPath;
                        $_SESSION['user']['image_url'] = $newPath;
                        
                        if (function_exists('syncAvatarToPython')) {
                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                            $domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                            $fullUrl = $domain . "/" . $newPath;
                            syncAvatarToPython($user['username'], $fullUrl);
                        }

                        echo json_encode(['status'=>'success', 'msg'=>__('avatar_changed', 'Đã đổi ảnh đại diện!'), 'new_avatar_url'=>$newPath]); 
                        exit;
                    } else { 
                        echo json_encode(['status'=>'error', 'msg'=>__('cannot_save_image', 'Không thể lưu file ảnh!')]); 
                        exit; 
                    }
                } else { 
                    echo json_encode(['status'=>'error', 'msg'=>__('invalid_image_type', 'Chỉ chấp nhận file ảnh (jpg, png, gif, webp)!')]); 
                    exit; 
                }
            } else {
                $upload_errors = [
                    1 => 'Dung lượng file vượt quá giới hạn hệ thống (upload_max_filesize).',
                    2 => 'Dung lượng file quá lớn.',
                    3 => 'File chỉ được tải lên một phần.',
                    4 => 'Không có file nào được tải lên.',
                    6 => 'Thiếu thư mục tạm trên máy chủ.',
                    7 => 'Không thể ghi file lên đĩa.'
                ];
                $errMsg = $upload_errors[$file['error']] ?? ('Lỗi tải file (Mã: ' . $file['error'] . ')');
                echo json_encode(['status'=>'error', 'msg'=>$errMsg]); exit;
            }
        }

        // 5. CẬP NHẬT TÊN/NGÀY SINH & MXH TÀI KHOẢN
        if (isset($_POST['name']) || isset($_POST['full_name']) || isset($_POST['facebook_url'])) {
            if ($student) {
                // Chỉ gửi phê duyệt nếu Họ tên hoặc Ngày sinh có sự thay đổi so với thông tin gốc
                $nameChanged = (isset($_POST['name']) && trim($_POST['name']) !== $student['name']);
                $dobChanged = (isset($_POST['dob']) && trim($_POST['dob']) !== $student['dob']);
                
                if ($nameChanged || $dobChanged) {
                    $pdo->prepare("UPDATE student SET pending_name=?, pending_dob=?, has_pending_changes=1 WHERE id=?")
                        ->execute([trim($_POST['name']), trim($_POST['dob']), $student['id']]);
                } else if ($student['has_pending_changes']) {
                    // Nếu gửi lại trùng khớp hoàn toàn thông tin gốc thì tự động hủy trạng thái chờ duyệt
                    $pdo->prepare("UPDATE student SET pending_name=NULL, pending_dob=NULL, has_pending_changes=0 WHERE id=?")
                        ->execute([$student['id']]);
                }
                
                // Mạng xã hội lưu trực tiếp không cần phê duyệt
                $fb = !empty($_POST['facebook_url']) ? trim($_POST['facebook_url']) : null;
                $tt = !empty($_POST['tiktok_url']) ? trim($_POST['tiktok_url']) : null;
                $yt = !empty($_POST['youtube_url']) ? trim($_POST['youtube_url']) : null;
                $ig = !empty($_POST['instagram_url']) ? trim($_POST['instagram_url']) : null;
                $zl = !empty($_POST['zalo_url']) ? trim($_POST['zalo_url']) : null;
                $gh = !empty($_POST['github_url']) ? trim($_POST['github_url']) : null;
                $th = !empty($_POST['threads_url']) ? trim($_POST['threads_url']) : null;

                $pdo->prepare("
                    UPDATE student 
                    SET facebook_url = ?, tiktok_url = ?, youtube_url = ?, 
                        instagram_url = ?, zalo_url = ?, github_url = ?, threads_url = ? 
                    WHERE id = ?
                ")->execute([$fb, $tt, $yt, $ig, $zl, $gh, $th, $student['id']]);
            } else {
                $newFullName = $_POST['full_name'] ?? $user['full_name'];
                $pdo->prepare("UPDATE users SET full_name=? WHERE id=?")->execute([$newFullName, $userId]);
                $_SESSION['user']['full_name'] = $newFullName;
            }
            echo json_encode(['status'=>'success', 'msg'=>__('info_updated', 'Đã cập nhật thông tin!')]); exit;
        }

    } catch (Exception $e) {
        echo json_encode(['status'=>'error', 'msg'=>__('server_error', 'Lỗi Server: ').$e->getMessage()]); exit;
    }

// --- XỬ LÝ 3: LẤY DỮ LIỆU HIỂN THỊ (CÓ FIX LOGIC PUSH) ---
$student = null;
if (in_array($user['role'], ['STUDENT', 'RED_FLAG'])) {
    $stmtS = $pdo->prepare("SELECT s.*, c.name as class_name FROM student s LEFT JOIN classroom c ON s.class_id = c.id WHERE s.code = ?");
    $stmtS->execute([$user['username']]);
    $student = $stmtS->fetch(PDO::FETCH_ASSOC);
}

// FIX: Chỉ lấy session MỚI NHẤT của mỗi loại thiết bị (tránh hiển thị lặp)
// Dùng subquery lấy max(id) per device_name → không lặp thiết bị nữa
$stmt = $pdo->prepare("
    SELECT s.session_id, s.device_name, s.user_agent, s.last_active,
           p.platform, p.device_model, (p.id IS NOT NULL) as push_enabled
    FROM user_sessions s
    INNER JOIN (
        SELECT MAX(id) as max_id
        FROM user_sessions
        WHERE user_id = ?
        GROUP BY device_name
    ) latest ON s.id = latest.max_id
    LEFT JOIN push_subscription p ON s.session_id = p.session_id
    ORDER BY s.last_active DESC
    LIMIT 20
");
$stmt->execute([$userId]);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Truy vấn lại DB để lấy chính xác cột avatar, two_factor_enabled, email, email_verified, is_default_password mới nhất
$stmtU = $pdo->prepare("SELECT avatar, two_factor_enabled, email, email_verified, is_default_password FROM users WHERE id = ?");
$stmtU->execute([$userId]);
$freshUser = $stmtU->fetch(PDO::FETCH_ASSOC);

$avatar_url = !empty($freshUser['avatar']) ? $freshUser['avatar'] : 'static/default.png';

echo json_encode([
    'status' => 'success',
    'current_session' => $currentSessId,
    'user' => [
        'id' => $user['id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'role' => $user['role'],
        'avatar' => "/" . ltrim($avatar_url, '/'),
        'email' => $freshUser['email'] ?? null,
        'email_verified' => (int)($freshUser['email_verified'] ?? 0),
        'two_factor_enabled' => (int)($freshUser['two_factor_enabled'] ?? 0),
        'must_change_password' => (($freshUser['is_default_password'] ?? 'off') === 'on')
    ],
    'student' => $student,
    'devices' => $devices
]);
?>