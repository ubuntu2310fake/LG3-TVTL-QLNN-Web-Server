<?php
// Chặn truy cập trang tin tức cho toàn bộ role
http_response_code(403);
require_once 'includes/header.php';
?>
<div class="container py-5 text-center">
    <div class="card shadow-sm p-4 mx-auto" style="max-width: 500px; border-radius: 16px; margin-top: 50px;">
        <div style="margin-bottom: 20px;">
            <i class="fas fa-ban" style="font-size: 50px; color: #dc3545;"></i>
        </div>
        <h3 style="font-weight: bold; margin-bottom: 12px;"><?= __('feature_disabled', 'Tính năng đã bị vô hiệu hóa') ?></h3>
        <p style="color: #6c757d; font-size: 15px; line-height: 1.5;"><?= __('news_disabled_desc', 'Chức năng Tin tức & Thông báo hiện đang tạm đóng đối với tất cả tài khoản.') ?></p>
        <div style="margin-top: 24px;">
            <a href="/" class="btn btn-primary" style="padding: 10px 24px; border-radius: 8px; text-decoration: none;"><i class="fas fa-home" style="margin-right: 6px;"></i> <?= __('back_to_home', 'Về trang chủ') ?></a>
        </div>
    </div>
</div>
<?php
require_once 'includes/footer.php';
exit;
?>
