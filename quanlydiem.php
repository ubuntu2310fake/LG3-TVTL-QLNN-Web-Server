<?php
// quanlydiem.php - File gốc tại thư mục root
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Chỉ cho phép Admin, Giáo viên hoặc các tài khoản có quyền quản lý truy cập
checkRole(['ADMIN', 'TEACHER']); 
?>

<div id="ajax-page-wrapper">
    <?php include 'views/manage_exams_view.php'; ?>
</div>

<?php 
require_once 'includes/footer.php'; 
?>