<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

function mask_email($email) {
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) return '***@***.***';
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    $len = strlen($name);
    if ($len <= 2) {
        $maskedName = substr($name, 0, 1) . '*';
    } else {
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(1, $len - 3)) . substr($name, -1);
    }
    return $maskedName . '@' . $domain;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = $input['action'] ?? '';

try {
    // 1. GỬI MÃ OTP KHÔI PHỤC MẬT KHẨU
    if ($action === 'send_reset_otp') {
        $username = trim($input['username'] ?? '');
        if (empty($username)) {
            echo json_encode(['status' => 'error', 'msg' => __('enter_username', 'Vui lòng nhập tên đăng nhập / Mã học sinh!')]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, username, full_name, email, email_verified FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['status' => 'error', 'msg' => __('user_not_found', 'Tài khoản không tồn tại trên hệ thống!')]);
            exit;
        }

        if (empty($user['email']) || empty($user['email_verified'])) {
            echo json_encode(['status' => 'error', 'msg' => __('email_not_linked_error', 'Tài khoản này chưa được liên kết & xác thực Email! Vui lòng liên hệ GVCN hoặc BGH để xin cấp lại mật khẩu.')]);
            exit;
        }

        $otp = sprintf("%06d", rand(100000, 999999));
        $expires = date('Y-m-d H:i:s', time() + 600); // 10 phút

        $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ? WHERE id = ?")
            ->execute([$otp, $expires, $user['id']]);

        $displayName = !empty($user['full_name']) ? $user['full_name'] : $user['username'];
        $subject = '🔑 MÃ XÁC NHẬN KHÔI PHỤC MẬT KHẨU - LG3 SUPER APP';
        
        $htmlBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
            <style>
                body, table, td, p, h1, h2, a, span {
                    font-family: -apple-system, BlinkMacSystemFont, "Be Vietnam Pro", sans-serif !important;
                }
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
                                <p style="margin: 3px 0 0 0; color: #bae6fd; font-size: 12px; font-weight: 600; text-transform: uppercase;">LG3 Super App • Khôi phục mật khẩu</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 26px 22px;">
                                <h2 style="margin: 0 0 12px 0; color: #005fba; font-size: 17px; font-weight: 700;">🔑 YÊU CẦU KHÔI PHỤC MẬT KHẨU</h2>
                                <p style="margin: 0 0 14px 0; color: #1d1d1f; font-size: 14px; line-height: 1.6;">
                                    Xin chào <strong>' . htmlspecialchars($displayName) . '</strong>,
                                </p>
                                <p style="margin: 0 0 18px 0; color: #475569; font-size: 13.5px; line-height: 1.6;">
                                    Hệ thống nhận được yêu cầu khôi phục mật khẩu cho tài khoản <strong>' . htmlspecialchars($user['username']) . '</strong>. Mã OTP xác nhận của bạn là:
                                </p>
                                <div style="text-align: center; margin: 22px 0;">
                                    <span style="display: inline-block; background-color: #eff6ff; border: 2px dashed #005fba; color: #005fba; font-size: 28px; font-weight: 800; padding: 12px 28px; border-radius: 10px; letter-spacing: 5px;">' . $otp . '</span>
                                </div>
                                <p style="margin: 0; color: #64748b; font-size: 12px; font-style: italic; text-align: center;">
                                    * Mã OTP có hiệu lực trong vòng 10 phút. Tuyệt đối không tiết lộ mã này cho bất kỳ ai!
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

        $mailRes = send_resend_email($user['email'], $subject, $htmlBody);
        if ($mailRes['status']) {
            echo json_encode([
                'status' => 'success',
                'msg' => __('otp_sent_to_email', 'Mã xác nhận OTP đã được gửi đến email ') . mask_email($user['email']) . '!'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => __('failed_send_email', 'Lỗi gửi email: ') . ($mailRes['msg'] ?? '')]);
        }
        exit;
    }

    // 2. XÁC NHẬN OTP VÀ ĐẶT LẠI MẬT KHẨU MỚI
    if ($action === 'reset_password_with_otp') {
        $username = trim($input['username'] ?? '');
        $otp = trim($input['otp'] ?? '');
        $newPassword = $input['new_password'] ?? '';

        if (empty($username) || empty($otp) || empty($newPassword)) {
            echo json_encode(['status' => 'error', 'msg' => __('fill_all_fields', 'Vui lòng điền đầy đủ thông tin!')]);
            exit;
        }

        if (strlen($newPassword) < 6) {
            echo json_encode(['status' => 'error', 'msg' => __('password_too_short', 'Mật khẩu mới phải có tối thiểu 6 ký tự!')]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, otp_code, otp_expires FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['status' => 'error', 'msg' => __('user_not_found', 'Tài khoản không tồn tại!')]);
            exit;
        }

        if (empty($user['otp_code']) || $user['otp_code'] !== $otp) {
            echo json_encode(['status' => 'error', 'msg' => __('invalid_otp', 'Mã OTP xác nhận không chính xác!')]);
            exit;
        }

        if (strtotime($user['otp_expires']) < time()) {
            echo json_encode(['status' => 'error', 'msg' => __('otp_expired', 'Mã OTP đã hết hạn, vui lòng xin lại mã mới!')]);
            exit;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = ?, is_default_password = 'off', otp_code = NULL, otp_expires = NULL WHERE id = ?")
            ->execute([$newHash, $user['id']]);

        echo json_encode([
            'status' => 'success',
            'msg' => __('password_reset_success', 'Khôi phục mật khẩu thành công! Bạn có thể đăng nhập ngay bằng mật khẩu mới.')
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => __('server_error', 'Lỗi Server: ') . $e->getMessage()]);
    exit;
}

echo json_encode(['status' => 'error', 'msg' => __('invalid_action', 'Thao tác không hợp lệ!')]);
?>
