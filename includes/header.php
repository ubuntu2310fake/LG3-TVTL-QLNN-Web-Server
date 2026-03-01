<?php
// header.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

// [FIX] Định nghĩa phiên bản App
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');

// =================================================================
// 1. CÁC HÀM HỖ TRỢ (Login & Auto-Login)
// =================================================================

// Hàm giải mã cookie Flask cũ (Giữ nguyên để tương thích)
if (!function_exists('header_decode_flask_cookie')) {
    function header_decode_flask_cookie($cookie_value) {
        $parts = explode('.', $cookie_value);
        if (count($parts) < 1) return null;
        $payload_b64 = str_replace(['-', '_'], ['+', '/'], $parts[0]);
        $pad = strlen($payload_b64) % 4;
        if ($pad) $payload_b64 .= str_repeat('=', 4 - $pad);
        $json_str = base64_decode($payload_b64);
        if (!$json_str) return null;
        $data = json_decode($json_str, true);
        return $data['_user_id'] ?? null;
    }
}

// Hàm lấy tên thiết bị
if (!function_exists('header_get_device_name')) {
    function header_get_device_name() {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (strpos($ua, 'Windows') !== false) return 'Windows PC';
        if (strpos($ua, 'Macintosh') !== false) return 'MacBook';
        if (strpos($ua, 'iPhone') !== false) return 'iPhone';
        if (strpos($ua, 'Android') !== false) return 'Android Device';
        return 'Thiết bị lạ';
    }
}

// [QUAN TRỌNG] Hàm thực hiện đăng nhập & GHI ĐÈ SESSION
if (!function_exists('header_perform_login')) {
    function header_perform_login($pdo, $user, $selector = null) {
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        
        $sessId = session_id();
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $dev = header_get_device_name();
        
        $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?")->execute([$sessId]);
        
        if ($selector) {
            $pdo->prepare("DELETE FROM user_sessions WHERE token_selector = ?")->execute([$selector]);
        }
        
        $stmtSess = $pdo->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, device_name, token_selector, last_active) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmtSess->execute([$user['id'], $sessId, $ip, $ua, $dev, $selector]);
        
        $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")->execute([$user['id']]);
    }
}

// =================================================================
// 2. LOGIC AUTO LOGIN (Chạy mỗi khi tải trang)
// =================================================================

if (!isset($_SESSION['user'])) {
    if (isset($_COOKIE['remember_token'])) {
        $parts = explode(':', $_COOKIE['remember_token']);
        if (count($parts) === 2) {
            $selector = $parts[0];
            $validator = $parts[1];

            $stmt = $pdo->prepare("SELECT * FROM user_tokens WHERE selector = ? AND expiry > NOW()");
            $stmt->execute([$selector]);
            $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tokenRow && hash_equals($tokenRow['hashed_validator'], hash('sha256', $validator))) {
                $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmtUser->execute([$tokenRow['user_id']]);
                $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    header_perform_login($pdo, $user, $selector);
                }
            } else {
                setcookie('remember_token', '', time() - 3600, '/', "", false, true);
            }
        }
    }
    elseif (isset($_COOKIE['session'])) {
        $flask_user_id = header_decode_flask_cookie($_COOKIE['session']);
        if ($flask_user_id) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$flask_user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $selector = bin2hex(random_bytes(16));
                $validator = bin2hex(random_bytes(32));
                $hashed_validator = hash('sha256', $validator);
                $expiry = date('Y-m-d H:i:s', time() + (86400 * 365 * 10)); // 10 năm
                
                $pdo->prepare("INSERT INTO user_tokens (user_id, selector, hashed_validator, expiry) VALUES (?, ?, ?, ?)")
                    ->execute([$user['id'], $selector, $hashed_validator, $expiry]);
                
                setcookie('remember_token', "$selector:$validator", time() + (86400 * 365 * 10), "/", "", false, true);
                header_perform_login($pdo, $user, $selector);
                setcookie('session', '', time() - 3600, '/'); 
                header("Refresh:0"); 
            }
        }
    }
}

$current_user = $_SESSION['user'] ?? null;

// Hàm check active menu
if (!function_exists('is_active')) {
    function is_active($path) { 
        $cur = basename($_SERVER['PHP_SELF']);
        if (($path == '/' || $path == 'index') && ($cur == 'index' || $cur == '')) return 'active';
        return ($cur == $path) ? 'active' : ''; 
    }
}

// =================================================================
// 3. LOGIC LẤY THÔNG BÁO TICKER (TỪ DB TRƯỜNG VÀ RUST BROADCAST)
// =================================================================
$ticker_school = '';
try {
    $ticker_school = $pdo->query("SELECT value FROM config WHERE `key` = 'ticker_school'")->fetchColumn();
} catch (Exception $e) {}

// Lấy Broadcast từ Server Pay thông qua Rust
$sys_bc = $SYS_LICENSE['broadcast'] ?? null;
$sys_bc_msg = ($sys_bc && $sys_bc['type'] === 'Dải Ticker ngang') ? $sys_bc['msg'] : '';

$full_ticker_html = '';
$ticker_text_clean = '';

if (!empty($ticker_school) || !empty($sys_bc_msg)) {
    $msgs = [];
    if (!empty($sys_bc_msg)) $msgs[] = "🔥 " . htmlspecialchars($sys_bc_msg);
    if (!empty($ticker_school)) $msgs[] = "📢 " . htmlspecialchars($ticker_school);
    $ticker_text_clean = implode(" | ", $msgs);
    
    $full_ticker_html = '
    <div class="custom-ticker-wrap">
        <div class="custom-ticker-content">
            ' . $ticker_text_clean . '
        </div>
    </div>';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, interactive-widget=resizes-content">
    
    <title>LG3 - Tư vấn tâm lý và Quản lý thi đua</title>
    
    <link rel="icon" href="https://qlnn.testifiyonline.xyz/lg3192192.png">
    <link rel="manifest" href="static/manifest.json">
    <meta name="theme-color" content="#005fba">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="static/style.css">

    <style>
        :root { --titlebar-height: 32px; }
        .electron-titlebar {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: var(--titlebar-height);
            background: #ffffff; z-index: 99999; -webkit-app-region: drag; 
            border-bottom: 1px solid #e2e8f0; justify-content: space-between; align-items: center;
        }
        .pwa-promo-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6); z-index: 10000;
            display: none; 
            justify-content: center; 
            align-items: center;
            backdrop-filter: blur(4px);
        }
        .pwa-promo-box {
            background: var(--bg-card); width: 85%; max-width: 320px;
            padding: 30px 20px; border-radius: 20px;
            text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: popupFadeIn 0.3s ease-out; color: var(--text-main); border: 1px solid var(--border-color);
        }
        .pwa-logo { width: 80px; height: 80px; object-fit: contain; margin-bottom: 15px; }
        .pwa-title { font-size: 18px; color: var(--text-main); margin: 0 0 10px; font-weight: 700; line-height: 1.4; }
        .pwa-desc { font-size: 14px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.5; }
        
        .pwa-btn-switch {
            display: block; width: 100%; padding: 12px 0;
            background: var(--accent-color); color: #ffffff !important; 
            text-decoration: none; font-weight: 600; border-radius: 10px; margin-bottom: 12px;
            box-shadow: 0 4px 10px rgba(0, 95, 186, 0.3); transition: transform 0.1s;
        }
        .pwa-btn-switch:active { transform: scale(0.98); }
        .pwa-btn-close {
            background: none; border: none; font-size: 13px;
            color: var(--text-muted); text-decoration: underline; cursor: pointer; padding: 5px;
        }
        @keyframes popupFadeIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        body.is-electron .electron-titlebar { display: flex; }
        body.is-electron .pc-header-toggle, body.is-electron .mobile-header { display: none !important; }
        body.is-electron .sidebar { top: var(--titlebar-height) !important; height: calc(100vh - var(--titlebar-height)) !important; }
        body.is-electron .main-content { margin-top: var(--titlebar-height) !important; }
        .et-left { display: flex; align-items: center; padding-left: 10px; gap: 8px; font-size: 12px; font-weight: 600; color: #1d1d1f; }
        .et-right { display: flex; align-items: center; height: 100%; -webkit-app-region: no-drag; }
        .et-clock { font-size: 13px; font-weight: 600; margin-right: 15px; color: #64748b; min-width: 140px; text-align: right; }
        .et-theme-btn { width: 32px; height: 24px; border: none; background: transparent; border-radius: 4px; margin-right: 5px; cursor: pointer; color: #64748b; }
        .et-btn { width: 46px; height: 100%; border: none; background: transparent; display: flex; justify-content: center; align-items: center; cursor: pointer; }
        .et-btn svg { width: 10px; height: 10px; } 
        .et-btn svg path, .et-btn svg rect, .et-btn svg polygon { fill: #1d1d1f; }
        .et-btn:hover { background-color: #e5e5e5; }
        .et-btn-close:hover { background-color: #d93025 !important; } .et-btn-close:hover svg polygon { fill: white !important; }
        .icon-maximize, .icon-restore { display: none; }
        [data-theme="dark"] .electron-titlebar { background: #1e293b; border-bottom-color: #334155; }
        [data-theme="dark"] .et-left, [data-theme="dark"] .et-clock { color: #cbd5e1; }
        [data-theme="dark"] .et-btn svg path, [data-theme="dark"] .et-theme-btn { fill: #ffffff !important; color: #ffffff !important; }

        /* --- 2. CSS SIDEBAR TOOLTIP CARD --- */
        .sidebar-hover-card {
            position: fixed; left: 265px; width: 300px;
            background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px;
            padding: 20px; box-shadow: 0 15px 40px -5px rgba(0,0,0,0.25); z-index: 9999;
            opacity: 0; visibility: hidden; transform: scale(0.95) translateX(-15px); pointer-events: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .sidebar-hover-card.active { opacity: 1; visibility: visible; transform: scale(1) translateX(0); }
        .shc-header { display: flex; align-items: center; gap: 15px; margin-bottom: 12px; }
        .shc-icon {
            width: 50px; height: 50px; border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-color), #60a5fa);
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 24px; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);
        }
        .shc-title { font-size: 18px; font-weight: 800; color: var(--text-main); line-height: 1.2; }
        .shc-desc { font-size: 14px; color: var(--text-muted); line-height: 1.5; }
        .sidebar-hover-card::before {
            content: ""; position: absolute; top: 30px; left: -8px;
            width: 16px; height: 16px; background: var(--bg-card);
            border-left: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);
            transform: rotate(45deg);
        }

        /* --- CSS NAV LINK --- */
        .nav-link {
            position: relative; margin: 4px 12px; padding: 12px 14px;
            border-radius: 8px; transition: all 0.2s ease;
            display: flex; align-items: center;
            text-decoration: none; color: var(--text-muted); font-weight: 500;
        }
        .nav-link i { width: 24px; text-align: center; margin-right: 12px; font-size: 16px; transition: transform 0.2s; }
        .nav-link:hover i { transform: scale(1.1); color: var(--primary-color); }
        .nav-link:not(.active):hover { background-color: var(--bg-hover); color: var(--accent-color); }
        .nav-link.active { background-color: #eff6ff; color: var(--primary-color) !important; font-weight: 700; }
        .nav-link.active i { color: var(--primary-color); }

        /* --- CSS NÚT LOGIN SIDEBAR --- */
        .btn-login-sidebar { font-weight: bold; justify-content: center; background: #e0f2fe; color: #0369a1 !important; border: 1px solid transparent; }
        [data-theme="dark"] .btn-login-sidebar { background: #1e293b; color: #38bdf8 !important; border: 1px solid #334155; }
        [data-theme="dark"] .btn-login-sidebar:hover { background: #334155; }

        /* --- CSS HEADER CHUẨN (PC & MOBILE) --- */
        .pc-header-toggle { 
            display: flex !important; 
            justify-content: flex-end; /* Ép đồng hồ và theme sang phải */
            align-items: center; 
            width: 100%; 
            margin-bottom: 10px;
            gap: 15px; 
        }
        .mobile-header { display: none !important; }

        /* HEADER TICKER (CUSTOM) - ĐÃ FIX LỆCH PHA */
        .custom-ticker-wrap { 
            flex: 1; 
            display: flex; 
            align-items: center; 
            overflow: hidden; 
            white-space: nowrap; 
            margin-right: auto; /* Dàn đều, đẩy đồng hồ sang lề phải */
            color: var(--primary-color); 
            font-weight: 500; font-size: 13px; 
            background: rgba(0, 95, 186, 0.05); 
            padding: 8px 15px; 
            border-radius: 6px; 
            border: 1px solid rgba(0, 95, 186, 0.1); 
        }
        .custom-ticker-content { 
            display: inline-block; 
            padding-left: 100%; /* Bắt đầu chạy từ lề phải của khung */
            white-space: nowrap;
        }
        @keyframes marquee-infinite { 
            0% { transform: translateX(0); } 
            100% { transform: translateX(-100%); } 
        }

        @media (max-width: 991px) {
            .mobile-header { 
                display: flex !important; 
                align-items: center; 
                justify-content: space-between; 
                gap: 5px; 
                width: 100%; 
                flex-wrap: wrap !important; /* Rớt dòng cho Ticker */
                padding-bottom: 5px; 
            }
            .pc-header-toggle { display: none !important; }
            .mobile-header > div:first-child { flex: 1; min-width: 60%; } 
            .mobile-header > div:nth-child(2) { margin-left: auto; } /* Giữ cụm Đồng hồ sát phải */

            .custom-ticker-wrap { 
                width: 100% !important; 
                margin: 5px 0 0 0 !important; 
                order: 999; /* Luôn ép nằm ở dòng dưới cùng */
                font-size: 11px; 
                border: none; 
                background: transparent; 
                padding: 0; 
                flex: 0 0 100% !important;
            }
        }
    </style>
    
    <script>
        (function() {
            try {
                const savedMode = localStorage.getItem('theme_mode') || 'system';
                let themeToApply = 'light';
                if (savedMode === 'dark') { themeToApply = 'dark'; } 
                else if (savedMode === 'system') {
                    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { themeToApply = 'dark'; }
                }
                if (themeToApply === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); }
            } catch (e) {}
        })();
    </script>
</head>
<body>
    <?php
    // --- LOGIC PHÁT HIỆN TRÌNH DUYỆT & HĐH ---
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $X = 90; $Y = 14; 

    $is_unsupported = false; $is_danger = false; 
    $err_title = "Trình duyệt không hỗ trợ"; $err_desc = "";
    $show_supermium = false; $is_mobile = false;

    if (!empty($ua)) {
        $current_version = 0;
        if (preg_match('/(Chrome|Edg|Firefox)\/(\d+)/i', $ua, $m)) $current_version = (int)$m[2];

        $is_old_windows = preg_match('/Windows NT (5\.|6\.[0-3])/i', $ua);
        $os_name = "Windows phiên bản cũ";
        if (preg_match('/Windows NT 5\.[12]/i', $ua)) $os_name = "Windows XP";
        elseif (preg_match('/Windows NT 6\.1/i', $ua)) $os_name = "Windows 7";
        elseif (preg_match('/Windows NT 6\.3/i', $ua)) $os_name = "Windows 8.1";

        if (preg_match('/Android\s+([\d\.]+)/i', $ua)) {
            $is_mobile = true;
            if ($current_version > 0 && $current_version < $X) {
                $is_unsupported = true;
                $err_desc = "Trình duyệt trên Android của bạn quá cũ. Hãy cập nhật lên bản $X+.";
            }
        } elseif (preg_match('/OS\s+(\d+)_/i', $ua, $matches)) {
            $is_mobile = true;
            $ios_ver = (int)$matches[1];
            if ($ios_ver < $Y) {
                $is_unsupported = true;
                if (strpos($ua, 'iPhone') !== false && $ios_ver <= 12) {
                    $is_danger = true;
                    $err_title = "Thiết bị không hỗ trợ";
                    $err_desc = "Bạn đang dùng iPhone đời cũ. Vui lòng cập nhật phần mềm hoặc nâng cấp thiết bị.";
                } else {
                    $err_desc = "Thiết bị yêu cầu iOS $Y+ để hiển thị chính xác.";
                }
            }
        } else {
            $is_ie = preg_match('/MSIE|Trident/i', $ua);
            if ($is_ie) {
                $is_unsupported = true; $is_danger = true; $err_title = "Trình duyệt lỗi thời";
                if ($is_old_windows) {
                    $show_supermium = true;
                    $err_desc = "Bạn đang dùng <b>$os_name</b>. IE không còn hỗ trợ, hãy cài <b>Supermium</b> để tiếp tục.";
                } else {
                    $err_desc = "Internet Explorer không còn được hỗ trợ. Vui lòng chuyển sang trình duyệt hiện đại.";
                }
            } elseif ($is_old_windows && $current_version < $X) {
                $is_unsupported = true; $show_supermium = true;
                if (preg_match('/Windows NT 5\./i', $ua)) $is_danger = true;
                $err_title = "Cần cập nhật trình duyệt";
                $err_desc = "Bạn đang dùng <b>$os_name</b>. Trình duyệt quá cũ, hãy cài đặt <b>Supermium</b> để tiếp tục.";
            } elseif (!$is_old_windows && $current_version > 0 && $current_version < $X) {
                $is_unsupported = true;
                $err_desc = "Vui lòng cập nhật trình duyệt lên phiên bản mới nhất (v$X+).";
            }
        }
    }

    if ($is_unsupported):
        $theme_color = $is_danger ? "#ef4444" : "#f59e0b";
        $bg_icon = $is_danger ? "#fee2e2" : "#fef3c7";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $err_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8fafc; text-align: center; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .win-card { background: #ffffff; width: 90%; max-width: 480px; margin: 50px auto; border-top: 5px solid <?= $theme_color ?>; border-radius: 12px; padding: 40px 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); text-align: center; border: 1px solid #e2e8f0; }
        .icon-wrapper { width: 80px; height: 80px; background-color: <?= $bg_icon ?>; border-radius: 50%; display: inline-block; line-height: 80px; vertical-align: middle; margin-bottom: 20px; }
        .err-title { color: <?= $theme_color ?>; font-size: 24px; font-weight: 700; margin: 0 0 10px 0; }
        .err-desc { color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 25px; }
        .info-box { background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: left; margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .info-box-content { color: #64748b; font-family: "Courier New", monospace !important; font-size: 12px; word-wrap: anywhere; background: #e2e8f0; padding: 8px; border-radius: 6px; margin-top: 8px; }
        .btn-group { display: block; }
        .win-btn { display: block; width: 100%; height: 48px; line-height: 48px; border-radius: 8px; color: white !important; text-decoration: none; font-weight: 600; font-size: 15px; margin-bottom: 12px; border: none; cursor: pointer; }
        .btn-supermium { background-color: #6366f1; }
        .btn-chrome { background-color: #10b981; }
        .btn-reload { background-color: <?= $is_danger ? '#64748b' : '#005fba' ?>; }
    </style>
</head>
<body>
    <div class="win-card">
        <div class="icon-wrapper"><i class="fas <?= $is_danger ? 'fa-shield-virus' : 'fa-exclamation-triangle' ?>" style="font-size: 35px; color: <?= $theme_color ?>;"></i></div>
        <h2 class="err-title"><?= $err_title ?></h2>
        <p class="err-desc"><?= $err_desc ?></p>
        <div class="info-box"><div style="font-weight: 600; color: #1e293b;"><i class="fas fa-microchip"></i> Thiết bị nhận diện:</div><div class="info-box-content"><?= htmlspecialchars($ua) ?></div></div>
        <div class="btn-group">
            <?php if ($show_supermium): ?><a href="https://win32subsystem.live/supermium/" target="_blank" class="win-btn btn-supermium"><i class="fas fa-rocket"></i> Tải Supermium (Khuyên dùng)</a><?php endif; ?>
            <?php if (!$is_mobile): ?><a href="https://www.google.com/chrome/" target="_blank" class="win-btn btn-chrome"><i class="fab fa-chrome"></i> Tải Google Chrome</a>
            <?php else: ?><button onclick="window.location.reload()" class="win-btn btn-reload"><i class="fas fa-sync-alt"></i> Thử truy cập lại</button><?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php exit(); endif; ?>

    <div id="electronTitlebar" class="electron-titlebar">
        <div class="et-left">
            <img src="https://qlnn.testifiyonline.xyz/lg3192192.png" style="width: 16px; height: 16px;">
            <span>LG3 - Quản lý nền nếp</span>
        </div>
        <div class="et-right">
            <div id="etClock" class="et-clock">--:--:--</div>
            <button class="et-theme-btn" onclick="cycleThemeMode()"><i id="etThemeIcon" class="fas fa-desktop"></i></button>
            <button class="et-btn" onclick="if(window.electronAPI) window.electronAPI.minimize()"><svg viewBox="0 0 10.2 1"><rect width="10.2" height="1"></rect></svg></button>
            <button class="et-btn" onclick="if(window.electronAPI) window.electronAPI.toggleMaximize()"><svg class="icon-maximize" viewBox="0 0 10 10" style="display:block"><path d="M0,0v10h10V0H0z M9,9H1V1h8V9z"></path></svg><svg class="icon-restore" viewBox="0 0 10 10" style="display:none"><path d="M2.1,0v2H0v8.1h8.2v-2h2V0H2.1z M7.2,9.2H1.1V3h6.1V9.2z M9.2,7.1h-1V2H3.1V1h6.1V7.1z"></path></svg></button>
            <button class="et-btn et-btn-close" onclick="if(window.electronAPI) window.electronAPI.close()"><svg viewBox="0 0 10 10"><polygon points="10.2,0.7 9.5,0 5.1,4.4 0.7,0 0,0.7 4.4,5.1 0,9.5 0.7,10.2 5.1,5.8 9.5,10.2 10.2,9.5 5.8,5.1"></polygon></svg></button>
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <div style="text-align:center; padding-bottom:20px; margin-bottom:10px; border-bottom:1px solid var(--border-color); flex-shrink: 0;">
            <div style="display:flex; align-items:center; justify-content:center; gap: 10px; margin-bottom: 5px;">
                <a href="index" style="text-decoration:none;">
                    <h2 style="color:var(--primary-color); margin:0; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                        <img src="https://qlnn.testifiyonline.xyz/lg3192192.png" style="width: 32px; height: 32px; object-fit: contain;">
                        <span>LG3-TVTL-QLNN</span>
                    </h2>
                </a>
            </div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:5px;">
                <?= $current_user ? htmlspecialchars($current_user['full_name'] ?? $current_user['username']) : 'Khách' ?>
            </div>
        </div>
        
        <div class="sidebar-menu-scroll">
            <a href="/" class="nav-link <?= is_active('index.php') ?>" 
               data-title="Trang chủ" data-desc="Xem thông báo, tin tức và các hoạt động mới nhất của nhà trường.">
                <i class="fas fa-home"></i> Trang chủ
            </a>
            <a href="ranking" class="nav-link <?= is_active('ranking.php') ?>"
               data-title="Bảng Xếp Hạng" data-desc="Cập nhật thứ hạng thi đua nền nếp và học tập của các lớp trong tuần.">
                <i class="fas fa-trophy"></i> Bảng Xếp Hạng
            </a>

            <div style="margin: 20px 0 5px 15px; font-size: 11px; color: var(--text-muted); font-weight: bold;">TƯ VẤN TÂM LÝ</div>
            
            <?php if ($current_user && in_array($current_user['role'], ['TEACHER', 'ADMIN'])): ?>
            <a href="consulting_dashboard" class="nav-link <?= is_active('consulting_dashboard.php') ?>"
               data-title="Giải đáp thắc mắc" data-desc="Khu vực dành cho giáo viên trả lời các câu hỏi ẩn danh từ học sinh.">
                <i class="fas fa-question-circle"></i> Giải đáp thắc mắc
            </a>
            <?php endif; ?>

            <a href="consulting_ai" class="nav-link <?= is_active('consulting_ai.php') ?>"
               data-title="Góc tư vấn" data-desc="Trò chuyện với trợ lý ảo tâm lý để được hỗ trợ và giải tỏa căng thẳng.">
                <i class="fas fa-lightbulb"></i> Góc tư vấn
            </a>
            <a href="consulting_test" class="nav-link <?= is_active('consulting_test.php') ?>"
               data-title="Trắc nghiệm Holland" data-desc="Bài kiểm tra định hướng nghề nghiệp và khám phá sở thích bản thân.">
                <i class="fas fa-file-medical-alt"></i> Trắc nghiệm Holland
            </a>

            <?php if ($current_user): ?>
                <?php if (in_array($current_user['role'], ['TEACHER', 'ADMIN', 'RED_FLAG'])): ?>
                    <div style="margin: 20px 0 5px 15px; font-size: 11px; color: var(--text-muted); font-weight: bold;">CÔNG TÁC NỀN NẾP</div>
                    
                    <a href="gate_check" class="nav-link <?= is_active('gate_check.php') ?>"
                       data-title="Kiểm tra Cổng" data-desc="Ghi nhận các lỗi vi phạm của học sinh tại khu vực cổng trường.">
                        <i class="fas fa-torii-gate"></i> Kiểm tra Cổng
                    </a>
                    <a href="class_check" class="nav-link <?= is_active('class_check.php') ?>"
                       data-title="Kiểm tra Lớp" data-desc="Chấm điểm nền nếp sinh hoạt và vệ sinh tại các lớp học.">
                        <i class="fas fa-clipboard-check"></i> Kiểm tra Lớp
                    </a>
                    <a href="violation_history" class="nav-link <?= is_active('violation_history.php') ?>"
                        data-title="Lịch sử vi phạm" data-desc="Theo dõi các lỗi vi phạm đã được ghi nhận.">
                         <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử vi phạm
                    </a>
                    
                    <?php if (in_array($current_user['role'], ['TEACHER', 'ADMIN'])): ?>
                    <a href="input_academic" class="nav-link <?= is_active('input_academic.php') ?>"
                       data-title="Nhập điểm học tập" data-desc="Cập nhật tổng điểm và số tiết học của các lớp để tính thi đua.">
                        <i class="fas fa-pen-square"></i> Nhập điểm học tập
                    </a>
                    <?php endif; ?>
                    
                    <div style="margin: 20px 0 5px 15px; font-size: 11px; color: var(--text-muted); font-weight: bold;">BÁO CÁO</div>
                    <a href="export_vpbs" class="nav-link <?= is_active('export_vpbs.php') ?>"
                       data-title="Xuất Báo Cáo" data-desc="Tải xuống báo cáo thống kê vi phạm chi tiết dưới dạng Excel.">
                        <i class="fas fa-file-excel"></i> Xuất Báo Cáo
                    </a>
                    <a href="teacher_dashboard" class="nav-link <?= is_active('teacher_dashboard.php') ?>"
                       data-title="Lớp Của Tôi" data-desc="Quản lý danh sách học sinh, miễn trừ và theo dõi vi phạm lớp chủ nhiệm.">
                        <i class="fas fa-chalkboard-teacher"></i> Lớp Của Tôi
                    </a>
                <?php endif; ?>

                <?php if ($current_user['role'] == 'ADMIN'): ?>
                    <div style="margin: 20px 0 5px 15px; font-size: 11px; color: var(--text-muted); font-weight: bold;">QUẢN TRỊ</div>
                    <a href="manage_users" class="nav-link <?= is_active('manage_users.php') ?>"
                       data-title="Quản lý Tài khoản" data-desc="Thêm, sửa, xóa và phân quyền tài khoản cho giáo viên và học sinh.">
                        <i class="fas fa-users-cog"></i> Tài khoản
                    </a>
                    <a href="manage_students" class="nav-link <?= is_active('manage_students.php') ?>"
                       data-title="Quản lý Học sinh" data-desc="Danh sách hồ sơ học sinh toàn trường.">
                        <i class="fas fa-user-graduate"></i> Học sinh
                    </a>
                    <a href="manage_violations" class="nav-link <?= is_active('manage_violations.php') ?>"
                       data-title="Cấu hình Hệ thống" data-desc="Chỉnh sửa danh mục lỗi vi phạm và ngày bắt đầu học kỳ.">
                        <i class="fas fa-cogs"></i> Cấu hình & Lỗi
                    </a>
                    <a href="traffic_monitor" class="nav-link <?= is_active('traffic_monitor.php') ?>"
                        data-title="Biểu đồ truy cập" data-desc="Theo dõi lưu lượng truy cập và tình trạng quá tải của hệ thống.">
                         <i class="fas fa-chart-area"></i> Biểu đồ truy cập
                    </a>
                    <a href="banned_ips_history" class="nav-link <?= is_active('banned_ips_history.php') ?>"
                        data-title="Sổ đen IP" data-desc="Theo dõi lịch sử các địa chỉ IP bị tường lửa khóa tự động.">
                         <i class="fas fa-shield-virus"></i> Sổ đen IP (Banned)
                    </a>
                    <a href="settings" class="nav-link <?= is_active('settings.php') ?>"
                        data-title="Cài đặt hệ thống" data-desc="Cấu hình máy chủ, cập nhật OTA Engine và kiểm tra giấy phép phần mềm.">
                         <i class="fas fa-server"></i> Cài đặt hệ thống
                    </a>
                <?php endif; ?>

                <div style="margin: 20px 0 5px 15px; font-size: 11px; color: var(--text-muted); font-weight: bold;">CÁ NHÂN</div>
                <a href="my_profile" class="nav-link <?= is_active('my_profile.php') ?>"
                   data-title="Hồ sơ cá nhân" data-desc="Xem thông tin tài khoản và chỉnh sửa ảnh đại diện.">
                    <i class="fas fa-id-card"></i> Hồ sơ của tôi
                </a>
                
                <?php if (in_array($current_user['role'], ['STUDENT', 'RED_FLAG'])): ?>
                <a href="student_violations" class="nav-link <?= is_active('student_violations.php') ?>"
                   data-title="Lỗi của tôi" data-desc="Xem lại lịch sử các lỗi vi phạm đã bị ghi nhận.">
                    <i class="fas fa-user-clock"></i> Lỗi của tôi
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <?php if ($current_user): ?>
                <a href="intro" class="nav-link <?= is_active('intro.php') ?>" 
                   data-title="Cài đặt thông báo" data-desc="Tùy chỉnh tùy chọn nhận thông báo và thông tin phiên bản.">
                    <i class="fas fa-bell"></i> Cài đặt thông báo
                </a>
                
                <a href="change_password" class="nav-link <?= is_active('change_password.php') ?>"
                   data-title="Đổi mật khẩu" data-desc="Thay đổi mật khẩu đăng nhập để bảo vệ tài khoản.">
                    <i class="fas fa-key"></i> Đổi mật khẩu
                </a>
                
                <a href="logout" class="nav-link" style="color:var(--danger-color);"
                   data-title="Đăng xuất" data-desc="Thoát phiên làm việc hiện tại an toàn.">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            <?php else: ?>
                <a href="login.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="nav-link btn-login-sidebar">
                    <i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content">
        <div class="mobile-header">
            <div style="display: flex; align-items: center; min-width: 0; flex: 1; overflow: hidden;">
                <button class="mobile-menu-btn" onclick="toggleSidebar()" style="flex-shrink: 0; margin-right: 5px;"><i class="fas fa-bars"></i></button>
                <h3 style="margin:0; color:var(--primary-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 16px; line-height: 1.2;">
                    LG3-TVTL-QLNN
                </h3>
            </div>

            <div style="display:flex; align-items:center; flex-shrink: 0; margin-left: auto;">
                <div class="live-clock" style="font-weight: 700; font-size: 12px; color: var(--primary-color); display: none; text-align: right; line-height: 1.1; margin-right: 8px;">
                    --:--
                </div>
                
                <button class="theme-toggle-btn" onclick="cycleThemeMode()" style="flex-shrink: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <i id="themeIconMobile" class="fas fa-desktop" style="font-size: 14px;"></i>
                </button>
            </div>
        </div>

        <div class="pc-header-toggle"> 
            <div style="display: flex; align-items: center; gap: 15px; margin-left: auto;">
                <div class="live-clock" style="font-weight: 600; font-size: 14px; color: var(--text-muted); display: none; white-space:nowrap; flex-shrink: 0;">--:--:--</div>
                <button class="theme-toggle-btn" onclick="cycleThemeMode()" style="flex-shrink: 0;"><i id="themeIconPC" class="fas fa-desktop"></i></button>
            </div>
        </div>

        <?php if (isset($_SESSION['flash'])): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Toastify({ 
                        text: "<?= addslashes($_SESSION['flash']['message']) ?>", 
                        duration: 3000, 
                        style: { background: "<?= $_SESSION['flash']['type'] == 'error' ? '#ef4444' : '#10b981' ?>" } 
                    }).showToast();
                });
            </script>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <?php if (!empty($full_ticker_html)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const tickerHtml = `<?= $full_ticker_html ?>`;
                
                // Hàm tính toán tốc độ chạy chữ dựa trên độ dài văn bản
                function applyMarquee(container) {
                    if (!container) return;
                    const tickerContent = container.querySelector('.custom-ticker-content');
                    if (tickerContent) {
                        const textLength = `<?= addslashes($ticker_text_clean) ?>`.length;
                        // Tính toán: Mỗi ký tự tốn 0.15 giây để chạy ngang + 8 giây dự phòng padding
                        let duration = (textLength * 0.15) + 8; 
                        tickerContent.style.animation = `marquee-infinite ${duration}s linear infinite`;
                    }
                }

                // 1. Chèn vào PC (vào ngay đầu tiên của pc-header-toggle để nó đẩy cụm đồng hồ sang phải)
                const pcToggle = document.querySelector('.pc-header-toggle');
                if (pcToggle) {
                    pcToggle.insertAdjacentHTML('afterbegin', tickerHtml);
                    applyMarquee(pcToggle);
                }

                // 2. Chèn vào Mobile (vào cuối cùng của mobile-header để rớt xuống dòng)
                const mobileHeader = document.querySelector('.mobile-header');
                if (mobileHeader) {
                    mobileHeader.insertAdjacentHTML('beforeend', tickerHtml);
                    applyMarquee(mobileHeader);
                }
            });
        </script>
        <?php endif; ?>