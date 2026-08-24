<?php
// change_password.php
require_once 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

// --- HÀM HỖ TRỢ CHECK PASSWORD CŨ (PYTHON/FLASK) ---
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

// --- XỬ LÝ POST (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear buffer để trả về JSON sạch
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'msg' => __('unknown_error', 'Lỗi không xác định')];

    try {
        $old_pass = $_POST['old_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        // Validate
        if (empty($old_pass) || empty($new_pass)) throw new Exception(__('fill_all_info', "Vui lòng điền đầy đủ thông tin."));
        if ($new_pass !== $confirm_pass) throw new Exception(__('confirm_pass_mismatch', "Mật khẩu xác nhận không khớp."));
        if (strlen($new_pass) < 6) throw new Exception(__('pass_min_length', "Mật khẩu mới phải có ít nhất 6 ký tự."));
        if (!preg_match('/[A-Z]/', $new_pass)) throw new Exception(__('pass_need_uppercase', "Mật khẩu mới phải chứa ít nhất 1 chữ cái viết hoa (A-Z)."));
        if (!preg_match('/[a-z]/', $new_pass)) throw new Exception(__('pass_need_lowercase', "Mật khẩu mới phải chứa ít nhất 1 chữ cái viết thường (a-z)."));
        if (!preg_match('/[0-9]/', $new_pass)) throw new Exception(__('pass_need_number', "Mật khẩu mới phải chứa ít nhất 1 chữ số (0-9)."));
        if (!preg_match('/[^A-Za-z0-9]/', $new_pass)) throw new Exception(__('pass_need_special', "Mật khẩu mới phải chứa ít nhất 1 ký tự đặc biệt (ví dụ: !@#$%...)."));

        // Lấy mật khẩu hiện tại
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userDB = $stmt->fetch();

        if (!$userDB) throw new Exception(__('account_not_found', "Không tìm thấy tài khoản."));

        // Kiểm tra mật khẩu cũ (PHP + Python)
        $is_valid = false;
        $current_hash = $userDB['password_hash'];

        if (strpos($current_hash, '$2y$') === 0) {
            if (password_verify($old_pass, $current_hash)) $is_valid = true;
        } else {
            if (verify_flask_hash_cp($old_pass, $current_hash)) $is_valid = true;
        }

        if ($is_valid) {
            // Hash mật khẩu mới
            $is_default = ($new_pass === '123456') ? 'on' : 'off';
            $new_hash_str = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password_hash = ?, is_default_password = ? WHERE id = ?");
            $upd->execute([$new_hash_str, $is_default, $userId]);
            if (isset($_SESSION['user'])) {
                $_SESSION['user']['is_default_password'] = $is_default;
            }
            
            $response = ['status' => 'success', 'msg' => __('change_pass_success', 'Đổi mật khẩu thành công!')];
        } else {
            throw new Exception(__('incorrect_current_pass', "Mật khẩu hiện tại không đúng."));
        }

    } catch (Exception $e) {
        $response['msg'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

require_once 'views/change_password_view.php';