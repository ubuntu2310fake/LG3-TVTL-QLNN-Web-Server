<?php
// profile_public.php
require_once 'includes/config.php';

// Nhận và chuẩn hóa mã học sinh từ URL
$code = isset($_GET['code']) ? trim($_GET['code']) : '';
$code = strtoupper($code);

$student = null;
if (!empty($code)) {
    $stmt = $pdo->prepare("
        SELECT s.*, c.name AS class_name 
        FROM student s 
        LEFT JOIN classroom c ON s.class_id = c.id 
        WHERE s.code = ?
    ");
    $stmt->execute([$code]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Nếu không tìm thấy học sinh, trả về trang lỗi 404
if (!$student) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không tìm thấy học sinh - LG3 Super App' : 'Student not found - LG3 Super App') ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="static/style.css">
        <script>
            // Apply theme dynamically to match the portal
            const savedMode = localStorage.getItem('theme_mode') || 'system';
            let themeToApply = 'light';
            if (savedMode === 'dark') { 
                themeToApply = 'dark'; 
            } else if (savedMode === 'system') {
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { 
                    themeToApply = 'dark'; 
                }
            }
            if (themeToApply === 'dark') { 
                document.documentElement.setAttribute('data-theme', 'dark'); 
            }
        </script>
        <style>
            body {
                background-color: var(--bg-body);
                color: var(--text-color);
                font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
                box-sizing: border-box;
            }
            .error-card {
                background: var(--bg-card);
                border: 1px solid var(--border-color);
                border-radius: 20px;
                padding: 40px 30px;
                text-align: center;
                max-width: 450px;
                width: 100%;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            }
            [data-theme="dark"] .error-card {
                box-shadow: none;
            }
            .error-icon {
                font-size: 60px;
                color: #ef4444;
                margin-bottom: 20px;
            }
            h1 {
                font-size: 24px;
                margin: 0 0 10px 0;
                font-weight: 700;
                color: var(--text-main);
            }
            p {
                color: var(--text-muted);
                font-size: 14px;
                line-height: 1.6;
                margin: 0 0 25px 0;
            }
            .btn-home {
                background: var(--primary-color);
                color: #ffffff !important;
                text-decoration: none;
                padding: 12px 30px;
                border-radius: 30px;
                font-weight: 600;
                font-size: 14px;
                display: inline-block;
                transition: all 0.3s;
                border: none;
                cursor: pointer;
            }
            [data-theme="dark"] .btn-home {
                color: #000000 !important;
                background: #ffffff;
            }
            .btn-home:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(59, 130, 246, 0.2);
            }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h1><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không Tìm Thấy Học Sinh' : 'Student Not Found') ?></h1>
            <p><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mã học sinh' : 'Student code') ?> <strong><?= htmlspecialchars($code) ?></strong> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'không tồn tại hoặc đã chuyển trường. Vui lòng kiểm tra lại.' : 'does not exist or has transferred. Please check again.') ?></p>
            <a href="/" class="btn-home"><i class="fas fa-home"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quay lại Trang chủ' : 'Back to Home') ?></a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Xử lý ảnh đại diện của học sinh
$avatar_url = $student['image_url'];
if (empty($avatar_url) || !file_exists($avatar_url)) {
    $avatar_url = 'static/default.png';
}

// Tìm tài khoản user tương ứng để hỗ trợ Kết Bạn
$stmtU = $pdo->prepare("SELECT id, username, full_name, avatar FROM users WHERE username = ?");
$stmtU->execute([$code]);
$targetUser = $stmtU->fetch(PDO::FETCH_ASSOC);

$target_user_id = $targetUser ? (int)$targetUser['id'] : null;
$my_id = isset($_SESSION['user']) ? (int)$_SESSION['user']['id'] : null;

$is_self = ($my_id && $target_user_id && $my_id === $target_user_id);
$relation = 'none'; // 'none', 'sent', 'received', 'friend'
$req_id = null;

if ($my_id && $target_user_id && !$is_self) {
    $stmtF = $pdo->prepare("
        SELECT id, user_id_1, user_id_2, status 
        FROM friendships 
        WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)
    ");
    $stmtF->execute([$my_id, $target_user_id, $target_user_id, $my_id]);
    $rel = $stmtF->fetch(PDO::FETCH_ASSOC);
    if ($rel) {
        $req_id = (int)$rel['id'];
        if ($rel['status'] === 'accepted') {
            $relation = 'friend';
        } elseif ($rel['status'] === 'pending') {
            if ((int)$rel['user_id_1'] === $my_id) {
                $relation = 'sent';
            } else {
                $relation = 'received';
            }
        }
    }
}

// Lập danh sách các mạng xã hội có dữ liệu
$socials = [];
if (!empty($student['facebook_url'])) {
    $socials[] = [
        'name' => 'Facebook',
        'url' => $student['facebook_url'],
        'icon' => 'fab fa-facebook',
        'class' => 'btn-facebook'
    ];
}
if (!empty($student['tiktok_url'])) {
    $socials[] = [
        'name' => 'TikTok',
        'url' => $student['tiktok_url'],
        'icon' => 'fab fa-tiktok',
        'class' => 'btn-tiktok'
    ];
}
if (!empty($student['instagram_url'])) {
    $socials[] = [
        'name' => 'Instagram',
        'url' => $student['instagram_url'],
        'icon' => 'fab fa-instagram',
        'class' => 'btn-instagram'
    ];
}
if (!empty($student['youtube_url'])) {
    $socials[] = [
        'name' => 'YouTube',
        'url' => $student['youtube_url'],
        'icon' => 'fab fa-youtube',
        'class' => 'btn-youtube'
    ];
}
if (!empty($student['zalo_url'])) {
    $socials[] = [
        'name' => 'Zalo',
        'url' => $student['zalo_url'],
        'icon' => 'fas fa-comment-dots',
        'class' => 'btn-zalo'
    ];
}
if (!empty($student['github_url'])) {
    $socials[] = [
        'name' => 'GitHub',
        'url' => $student['github_url'],
        'icon' => 'fab fa-github',
        'class' => 'btn-github'
    ];
}
if (!empty($student['threads_url'])) {
    $socials[] = [
        'name' => 'Threads',
        'url' => $student['threads_url'],
        'icon' => 'fab fa-at',
        'class' => 'btn-threads'
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bio của <?= htmlspecialchars($student['name']) ?> - LG3 Super App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="static/style.css">
    <script>
        // Apply theme dynamically to match the portal
        const savedMode = localStorage.getItem('theme_mode') || 'system';
        let themeToApply = 'light';
        if (savedMode === 'dark') { 
            themeToApply = 'dark'; 
        } else if (savedMode === 'system') {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { 
                themeToApply = 'dark'; 
            }
        }
        if (themeToApply === 'dark') { 
            document.documentElement.setAttribute('data-theme', 'dark'); 
        }
    </script>
    <style>
        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
        }

        .bio-container {
            max-width: 480px;
            width: 100%;
            text-align: center;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        [data-theme="dark"] .bio-container {
            box-shadow: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .avatar-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }

        .avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--border-color);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background-color: var(--bg-hover);
        }

        .avatar:hover {
            transform: scale(1.08) rotate(3deg);
        }

        .badge-class {
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary-color);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            border: 2px solid var(--bg-card);
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        [data-theme="dark"] .badge-class {
            color: #000000;
            background: #ffffff;
        }

        .name {
            font-size: 24px;
            font-weight: 800;
            margin: 15px 0 5px 0;
            color: var(--text-main);
        }

        .student-code {
            font-family: monospace;
            font-size: 13px;
            font-weight: bold;
            color: var(--text-muted);
            letter-spacing: 2px;
            margin-bottom: 30px;
        }

        .link-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 10px;
        }

        .bio-link {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 16px 20px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid var(--border-color);
            box-sizing: border-box;
            background: var(--bg-input);
            color: var(--text-main);
            overflow: hidden;
        }

        .bio-link i {
            position: absolute;
            left: 20px;
            font-size: 20px;
        }

        .bio-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            background: var(--bg-hover);
        }

        /* Hiệu ứng màu thương hiệu đặc trưng khi hover */
        .btn-facebook:hover {
            background: #1877F2 !important;
            border-color: #1877F2 !important;
            color: #ffffff !important;
        }
        .btn-tiktok:hover {
            background: #000000 !important;
            border-color: #222222 !important;
            color: #ffffff !important;
            box-shadow: 0 0 15px rgba(254, 44, 85, 0.4), 0 0 15px rgba(37, 244, 238, 0.4) !important;
        }
        .btn-instagram:hover {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%) !important;
            border-color: transparent !important;
            color: #ffffff !important;
        }
        .btn-youtube:hover {
            background: #FF0000 !important;
            border-color: #FF0000 !important;
            color: #ffffff !important;
        }
        .btn-zalo:hover {
            background: #0068FF !important;
            border-color: #0068FF !important;
            color: #ffffff !important;
        }
        .btn-github:hover {
            background: #24292e !important;
            border-color: #24292e !important;
            color: #ffffff !important;
        }
        .btn-threads:hover {
            background: #101010 !important;
            border-color: #101010 !important;
            color: #ffffff !important;
        }

        .empty-state {
            padding: 30px 10px;
            color: var(--text-muted);
            font-size: 14px;
            font-style: italic;
        }

        .empty-state i {
            display: block;
            font-size: 40px;
            margin-bottom: 15px;
            color: var(--text-muted);
            opacity: 0.5;
        }

        .footer-logo {
            margin-top: 40px;
            font-size: 11px;
            color: var(--text-muted);
            opacity: 0.5;
            letter-spacing: 1px;
        }

        .btn-friend-action {
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-add-friend {
            background: #005fba;
            color: #ffffff !important;
        }
        .btn-add-friend:hover {
            background: #004c99;
            transform: translateY(-2px);
        }
        .btn-cancel-request {
            background: #f59e0b;
            color: #ffffff !important;
        }
        .btn-cancel-request:hover {
            background: #d97706;
            transform: translateY(-2px);
        }
        .btn-accept-friend {
            background: #10b981;
            color: #ffffff !important;
        }
        .btn-accept-friend:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        .btn-msg-friend {
            background: #005fba;
            color: #ffffff !important;
        }
        .btn-msg-friend:hover {
            background: #004c99;
            transform: translateY(-2px);
        }
        .btn-unfriend {
            background: #ef4444;
            color: #ffffff !important;
        }
        .btn-unfriend:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="bio-container">
        <div class="avatar-wrapper">
            <img class="avatar" src="<?= htmlspecialchars($avatar_url) ?>" alt="Avatar" onerror="this.src='static/default.png'">
            <div class="badge-class"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?> <?= htmlspecialchars($student['class_name'] ?? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tự do' : 'Free')) ?></div>
        </div>

        <div class="name"><?= htmlspecialchars($student['name']) ?></div>
        <div class="student-code"><?= htmlspecialchars($student['code']) ?></div>

        <!-- FRIENDSHIP ACTION SECTION -->
        <div style="margin-bottom: 25px;">
            <?php if (!$my_id): ?>
                <a href="/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn-friend-action btn-add-friend" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
                    <i class="fas fa-user-plus"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng nhập để kết bạn' : 'Login to connect') ?>
                </a>
            <?php elseif ($is_self): ?>
                <div style="display:inline-block; padding: 6px 16px; background: var(--bg-hover); border-radius: 20px; font-size: 13px; color: var(--text-muted); border: 1px solid var(--border-color);">
                    <i class="fas fa-user-check"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trang hồ sơ của bạn' : 'Your Profile Page') ?>
                </div>
            <?php elseif ($target_user_id): ?>
                <div id="friendActionBox" style="display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                    <?php if ($relation === 'none'): ?>
                        <button type="button" onclick="handleFriendAction('request')" class="btn-friend-action btn-add-friend">
                            <i class="fas fa-user-plus"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Gửi kết bạn' : 'Add Friend') ?>
                        </button>
                    <?php elseif ($relation === 'sent'): ?>
                        <button type="button" onclick="handleFriendAction('cancel')" class="btn-friend-action btn-cancel-request" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bấm để hủy lời mời' : 'Click to cancel request') ?>">
                            <i class="fas fa-user-clock"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đã gửi lời mời (Hủy)' : 'Request Sent (Cancel)') ?>
                        </button>
                    <?php elseif ($relation === 'received'): ?>
                        <button type="button" onclick="handleFriendAction('accept')" class="btn-friend-action btn-accept-friend">
                            <i class="fas fa-check"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đồng ý' : 'Accept') ?>
                        </button>
                        <button type="button" onclick="handleFriendAction('reject')" class="btn-friend-action btn-unfriend">
                            <i class="fas fa-times"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Từ chối' : 'Decline') ?>
                        </button>
                    <?php elseif ($relation === 'friend'): ?>
                        <a href="/?view=chat&partner_id=<?= $target_user_id ?>" class="btn-friend-action btn-msg-friend" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
                            <i class="fas fa-comment-dots"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhắn tin' : 'Message') ?>
                        </a>
                        <button type="button" onclick="handleFriendAction('unfriend')" class="btn-friend-action btn-unfriend">
                            <i class="fas fa-user-minus"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hủy kết bạn' : 'Unfriend') ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="link-list">
            <?php if (!empty($socials)): ?>
                <?php foreach ($socials as $social): ?>
                    <a href="<?= htmlspecialchars($social['url']) ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="bio-link <?= $social['class'] ?>">
                        <i class="<?= $social['icon'] ?>"></i>
                        <?= htmlspecialchars($social['name']) ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-link-slash"></i>
                    <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chưa có liên kết mạng xã hội nào được cập nhật.' : 'No social media links updated yet.') ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer-logo">LG3 PORTAL - BIO SYSTEM</div>
    </div>

    <script>
        async function handleFriendAction(action) {
            const targetId = <?= json_encode($target_user_id) ?>;
            const reqId = <?= json_encode($req_id) ?>;
            
            if (!targetId) return;

            if (action === 'unfriend') {
                if (!confirm('<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn có chắc chắn muốn hủy kết bạn với người này?' : 'Are you sure you want to unfriend this user?') ?>')) return;
            }

            try {
                let url = '';
                let payload = {};

                if (action === 'request') {
                    url = '/consulting_chat.php?endpoint=/api/friends/request';
                    payload = { target_id: targetId };
                } else if (action === 'cancel') {
                    url = '/consulting_chat.php?endpoint=/api/friends/cancel';
                    payload = { target_id: targetId };
                } else if (action === 'accept' || action === 'reject') {
                    url = '/consulting_chat.php?endpoint=/api/friends/respond';
                    payload = { req_id: reqId, action: action };
                } else if (action === 'unfriend') {
                    url = '/consulting_chat.php?endpoint=/api/friends/unfriend';
                    payload = { target_id: targetId };
                }

                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.msg || 'Thao tác thất bại');
                }
            } catch (e) {
                alert('Lỗi kết nối');
            }
        }
    </script>
</body>
</html>
