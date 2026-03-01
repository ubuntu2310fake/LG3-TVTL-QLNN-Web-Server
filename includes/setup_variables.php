<?php
// includes/setup_variables.php
// BẢNG ĐIỀU KHIỂN BIẾN MÔI TRƯỜNG DÀNH CHO ADMIN TRƯỜNG
// (File này TUYỆT ĐỐI KHÔNG MÃ HÓA để các trường tự do chỉnh sửa)

// =================================================================
// 1. CẤU HÌNH APP PYTHON (TƯ VẤN TÂM LÝ)
// =================================================================
define('TVTL_BASE_URL', 'https://tvtl.TÊN MIỀN TRƯỜNG BẠN');
define('SSO_SECRET_KEY', 'khoa_bi_mat_ket_noi_hai_app_123456_secure'); // SSO Key mặc định

// =================================================================
// 2. CẤU HÌNH PUSH NOTIFICATION (VAPID KEYS)
// =================================================================
define('VAPID_PUBLIC_KEY', 'ĐIỀN VAPID_PUBLIC_KEY CỦA BẠN VÀO ĐÂY');
define('VAPID_PRIVATE_KEY', 'ĐIỀN VAPID PRIVATE KEY CỦA BẠN VÀO ĐÂY');
define('VAPID_SUBJECT', 'mailto:EMAIL TRƯỜNG');

// =================================================================
// 3. CẤU HÌNH TÀI KHOẢN BẢN QUYỀN (PORTAL KHÁCH HÀNG)
// =================================================================
// Trường học nhập Email và Mật khẩu đã đăng ký trên hệ thống thanh toán
define('LICENSE_EMAIL', 'EMAIL');
define('LICENSE_PASS', 'MẬT KHẨU');
define('LICENSE_KEY', 'KEY BẢN QUYỀN');

?>
