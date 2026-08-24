<?php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => __('please_login_again', 'Vui lòng đăng nhập lại.')]); exit;
}

function verify_flask_hash_cp($password, $hash) {
    if (strpos($hash, 'pbkdf2:sha256') !== 0) return false;
    $parts = explode('$', $hash);
    if (count($parts) !== 3) return false;
    list($method_iter, $salt, $db_hash) = $parts;
    $method_parts = explode(':', $method_iter);
    $iterations = intval($method_parts[2]);
    $calc_hash = hash_pbkdf2("sha256", $password, $salt, $iterations, 32, false);
    return hash_equals($db_hash, $calc_hash);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $old_pass = $input['old_password'] ?? '';
    $new_pass = $input['new_password'] ?? '';
    $confirm_pass = $input['confirm_password'] ?? '';
    $userId = $_SESSION['user']['id'];

    try {
        if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) throw new Exception(__('fill_all_info', 'Vui lòng điền đủ thông tin.'));
        if ($new_pass !== $confirm_pass) throw new Exception(__('confirm_password_mismatch', 'Mật khẩu xác nhận không khớp.'));
        if (strlen($new_pass) < 6) throw new Exception(__('new_password_length', 'Mật khẩu mới phải có ít nhất 6 ký tự.'));
        if (!preg_match('/[A-Z]/', $new_pass)) throw new Exception(__('pass_need_uppercase', 'Mật khẩu mới phải chứa ít nhất 1 chữ cái viết hoa (A-Z).'));
        if (!preg_match('/[a-z]/', $new_pass)) throw new Exception(__('pass_need_lowercase', 'Mật khẩu mới phải chứa ít nhất 1 chữ cái viết thường (a-z).'));
        if (!preg_match('/[0-9]/', $new_pass)) throw new Exception(__('pass_need_number', 'Mật khẩu mới phải chứa ít nhất 1 chữ số (0-9).'));
        if (!preg_match('/[^A-Za-z0-9]/', $new_pass)) throw new Exception(__('pass_need_special', 'Mật khẩu mới phải chứa ít nhất 1 ký tự đặc biệt (ví dụ: !@#$%...).'));

        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userDB = $stmt->fetch();
        if (!$userDB) throw new Exception(__('account_not_found', 'Không tìm thấy tài khoản.'));

        $is_valid = false;
        $current_hash = $userDB['password_hash'];
        if (strpos($current_hash, '$2y$') === 0) {
            if (password_verify($old_pass, $current_hash)) $is_valid = true;
        } else {
            if (verify_flask_hash_cp($old_pass, $current_hash)) $is_valid = true;
        }

        if ($is_valid) {
            $is_default = ($new_pass === '123456') ? 'on' : 'off';
            $new_hash_str = password_hash($new_pass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ?, is_default_password = ? WHERE id = ?")->execute([$new_hash_str, $is_default, $userId]);
            if (isset($_SESSION['user'])) {
                $_SESSION['user']['is_default_password'] = $is_default;
            }
            echo json_encode(['status' => 'success', 'msg' => __('password_changed_success', 'Đổi mật khẩu thành công!')]);
        } else {
            throw new Exception(__('incorrect_current_password', 'Mật khẩu hiện tại không đúng.'));
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}
?>