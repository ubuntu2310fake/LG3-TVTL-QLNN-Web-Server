<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hệ thống đang bận' : 'System busy') ?></title>
    <link rel="stylesheet" href="static/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; background-color: var(--bg-body); height: 100vh; margin: 0;">

    <div class="win-card" style="max-width: 450px; text-align: center; border-top: 4px solid var(--primary-color); margin: 20px;">
        <div style="font-size: 50px; color: var(--primary-color); margin-bottom: 20px;">
            <i class="fas fa-hourglass-half fa-spin" style="--fa-animation-duration: 3s;"></i>
        </div>
        
        <h2 style="color: var(--text-heading); margin-bottom: 10px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hệ thống đang bận xử lý' : 'System is busy processing') ?></h2>
        
        <p style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">
            <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hiện có quá nhiều yêu cầu cùng lúc. Vui lòng đợi' : 'There are too many requests at the moment. Please wait') ?>
            <b style="color: var(--primary-color); font-size: 18px;"><span id="timer">20</span> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'giây' : 'seconds') ?></b> 
            <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'để hệ thống tự động tải lại.' : 'for the system to auto-reload.') ?>
        </p>
        
        <div style="margin-top: 30px; display: flex; flex-direction: column; gap: 12px;">
            <button onclick="location.href='index.php'" class="win-btn">
                <i class="fas fa-sync-alt"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'THỬ VÀO LẠI NGAY' : 'TRY AGAIN NOW') ?>
            </button>
            
            <div style="margin: 10px 0; border-top: 1px solid var(--border-color);"></div>
            
            <p style="font-size: 12px; color: var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn là Giáo viên hoặc Quản trị viên?' : 'Are you a Teacher or Administrator?') ?></p>
            <a href="login.php" class="win-btn win-btn-secondary">
                <i class="fas fa-user-shield"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'ĐĂNG NHẬP TRUY CẬP' : 'LOGIN TO ACCESS') ?>
            </a>
        </div>
    </div>

    <script>
        let t = 20;
        const timerEl = document.getElementById('timer');
        
        // Đếm ngược
        const interval = setInterval(() => {
            if (t > 0) {
                t--;
                timerEl.innerText = t;
            } else {
                clearInterval(interval);
                location.href = 'index.php'; // Tự động quay về trang chủ
            }
        }, 1000);
    </script>
</body>
</html>