<?php
// File: banned_ips_history.php (CONTROLLER TRUNG TÂM)
// TUYỆT ĐỐI KHÔNG CHỨA THẺ HTML NÀO Ở ĐÂY

// 1. NẠP CẤU HÌNH & KIỂM TRA QUYỀN
require_once 'includes/config.php';
checkRole(['ADMIN']);

// 2. XỬ LÝ HÀNH ĐỘNG (ACTION) - Mở khóa thủ công
if (isset($_GET['unban']) && is_numeric($_GET['unban'])) {
    $id_to_unban = $_GET['unban'];
    
    // Xóa IP khỏi DB
    $stmt = $pdo->prepare("DELETE FROM banned_ips WHERE id = ?");
    if ($stmt->execute([$id_to_unban])) {
        $_SESSION['flash'] = ['type' => 'success', 'message' => __('ip_unbanned', 'Đã mở khóa IP thành công!')];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'message' => __('ip_unban_err', 'Có lỗi xảy ra khi mở khóa.')];
    }
    
    // Chuyển hướng để tránh lỗi submit lại form
    header("Location: banned_ips_history.php");
    exit(); // Bắt buộc phải có sau khi header Location
}

// 3. CHUẨN BỊ DỮ LIỆU ĐỂ ĐẨY SANG VIEW
$stmt = $pdo->prepare("SELECT * FROM banned_ips ORDER BY banned_at DESC");
$stmt->execute();
$banned_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. GỌI VIEW TƯƠNG ỨNG ĐỂ RENDER HTML (Bước cuối cùng)
require_once 'views/banned_ips_history_view.php';