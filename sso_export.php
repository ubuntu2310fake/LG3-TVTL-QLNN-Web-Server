<?php
// sso_export.php (Đặt tại thư mục gốc của LG3 - Siêu ứng dụng trường THPT Lạng Giang số 3)
session_start();

if (file_exists('includes/config.php')) {
    require_once 'includes/config.php';
} elseif (file_exists('config.php')) {
    require_once 'config.php';
}

// Nếu chưa đăng nhập LG3 thì bắt đăng nhập rồi quay lại đây
if (!isset($_SESSION['user'])) {
    $current_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?next=" . $current_url);
    exit();
}

$secret_key = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : "khoa_bi_mat_ket_noi_hai_app_123456_secure"; 

// Lấy thông tin app yêu cầu từ URL
$app_name = $_GET['app_name'] ?? __('unknown_app', 'Một ứng dụng chưa rõ');
$callback_url = $_GET['callback'] ?? '';

// ==============================================================
// 1. TRUY VẤN LẤY NGÀY SINH (Xử lý chuẩn hóa ngày tháng)
// ==============================================================
$real_dob = '2010-01-01'; // Giá trị chuẩn YYYY-MM-DD để gửi đi
$display_dob = '01/01/2010'; // Giá trị hiển thị lên màn hình

try {
    if (isset($pdo)) {
        $stmtS = $pdo->prepare("SELECT dob FROM student WHERE code = ?");
        $stmtS->execute([$_SESSION['user']['username']]);
        $stu = $stmtS->fetch(PDO::FETCH_ASSOC);
        
        if ($stu && !empty($stu['dob'])) {
            $db_dob = trim($stu['dob']);
            
            // Nếu DB lưu dạng 29/09/2010 (Dấu gạch chéo)
            if (strpos($db_dob, '/') !== false) {
                $date_parts = explode('/', $db_dob); // Tách ra thành mảng
                if (count($date_parts) == 3) {
                    // Cấu trúc lại thành YYYY-MM-DD (Năm-Tháng-Ngày)
                    $real_dob = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
                }
            } else {
                // Nếu DB đã lưu chuẩn YYYY-MM-DD (Ví dụ: 2010-09-29)
                $real_dob = $db_dob;
            }
        }
    }
} catch (Exception $e) {
    error_log("Lỗi lấy DOB SSO: " . $e->getMessage());
}

// Chuyển đổi để hiển thị ra UI: "29/09/2010"
$display_dob = date('d/m/Y', strtotime($real_dob));

// ==============================================================
// 2. XỬ LÝ KHI NGƯỜI DÙNG BẤM NÚT "ĐỒNG Ý ỦY QUYỀN"
// ==============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['authorize'])) {
    
    // ĐÓNG GÓI DỮ LIỆU VÀO JWT
    // ĐÓNG GÓI DỮ LIỆU VÀO JWT
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'username'   => $_SESSION['user']['username'],
        'full_name'  => $_SESSION['user']['full_name'],
        'dob'        => $real_dob,
        'role'       => $_SESSION['user']['role'], // Thêm quyền (STUDENT, TEACHER, ADMIN...)
        'class_name' => $_SESSION['user']['class_name'] ?? __('guest', 'Khách'), // Thêm Lớp để lọc
        'exp'        => time() + 120 
    ]);

    // MÃ HÓA
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    $jwt_token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

    // CHUYỂN HƯỚNG VỀ APP CẤP KEY KÈM TOKEN
    $redirect_to = $_POST['callback_url'] . "?token=" . $jwt_token;
    header("Location: " . $redirect_to);
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= __('sso_auth_title', 'Ủy quyền truy cập - LG3 Nền Nếp') ?></title>
    <link rel="stylesheet" href="static/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            display: flex; justify-content: center; align-items: center; 
            min-height: 100vh; background: var(--bg-body); margin: 0;
            font-family: 'Be Vietnam Pro', sans-serif;
        }
        .auth-card {
            width: 100%; max-width: 420px; padding: 40px 30px; 
            text-align: center; margin: 20px;
        }
        .icon-exchange {
            display: flex; justify-content: center; align-items: center; 
            gap: 15px; margin-bottom: 25px;
        }
        .app-icon {
            width: 60px; height: 60px; border-radius: 14px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); object-fit: cover;
        }
        .tool-icon {
            width: 60px; height: 60px; background: #eff6ff; 
            border-radius: 14px; display: flex; justify-content: center; 
            align-items: center; color: var(--primary-color); font-size: 28px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 1px solid #bfdbfe;
        }
        .info-box {
            background: var(--bg-hover); padding: 20px; border-radius: 12px; 
            border: 1px solid var(--border-color); text-align: left; 
            margin-bottom: 30px;
        }
        .info-box ul {
            margin: 0; padding-left: 20px; font-size: 14px; 
            color: var(--text-main); line-height: 1.8;
        }
    </style>
    <script>
        const savedMode = localStorage.getItem('theme_mode') || 'system';
        if (savedMode === 'dark' || (savedMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>
<body>

    <div class="win-card auth-card">
        <div class="icon-exchange">
            <img src="/lg3192192.png" class="app-icon">
            <i class="fas fa-exchange-alt" style="color: var(--text-muted); font-size: 20px;"></i>
            <div class="tool-icon">
                <i class="fas fa-key"></i>
            </div>
        </div>

        <h3 style="margin: 0 0 10px 0; color: var(--text-main); font-size: 20px;"><?= __('sso_request_title', 'Yêu cầu quyền truy cập') ?></h3>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 25px; line-height: 1.6;">
            <?= __('app_wants_access_prefix', 'Ứng dụng') ?> <b style="color: var(--primary-color);"><?= htmlspecialchars($app_name) ?></b> <?= __('app_wants_access_suffix', 'muốn kết nối với tài khoản LG3 của bạn.') ?>
        </p>

        <div class="info-box">
            <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px; font-weight: 700; text-transform: uppercase;"><?= __('info_shared', 'Thông tin sẽ được chia sẻ:') ?></div>
            <ul>
                <li><?= __('full_name_label', 'Họ và tên:') ?> <b style="color: var(--primary-color);"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></b></li>
                <li><?= __('student_code_device_label', 'Mã HS / Tên máy:') ?> <b><?= htmlspecialchars($_SESSION['user']['username']) ?></b></li>
                <li><?= __('dob_label', 'Thông tin Ngày sinh:') ?> <b><?= htmlspecialchars($display_dob) ?></b></li>
            </ul>
        </div>

        <form method="POST">
            <input type="hidden" name="callback_url" value="<?= htmlspecialchars($callback_url) ?>">
            
            <button type="submit" name="authorize" class="win-btn" style="width: 100%; height: 48px; font-size: 15px; margin-bottom: 12px;">
                <i class="fas fa-check-circle"></i> <?= __('authorize_btn', 'Đồng ý ủy quyền') ?>
            </button>
            
            <a href="index.php" class="win-btn win-btn-secondary" style="width: 100%; height: 48px; font-size: 15px; text-decoration: none;">
                <?= __('cancel_btn', 'Hủy bỏ') ?>
            </a>
        </form>
    </div>

</body>
</html>