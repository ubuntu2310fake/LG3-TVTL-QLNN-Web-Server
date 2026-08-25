<?php
// header.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

// BIẾN XÁC ĐỊNH IFRAME TỪ FLUTTER
$is_iframe = isset($_GET['iframe']) && $_GET['iframe'] == 1;

// =================================================================
// 1. CÁC HÀM HỖ TRỢ (Login & Auto-Login)
// =================================================================

if (!function_exists('header_get_device_name')) {
    function header_get_device_name() {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (strpos($ua, 'Windows') !== false) return 'Windows PC';
        if (strpos($ua, 'Macintosh') !== false) return 'MacBook';
        if (strpos($ua, 'iPhone') !== false) return 'iPhone';
        if (strpos($ua, 'Android') !== false) return 'Android Device';
        return (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thiết bị lạ' : 'Unknown Device');
    }
}

if (!function_exists('header_perform_login')) {
    function header_perform_login($pdo, $user, $selector = null) {
        $old_sess_id = session_id();

        session_regenerate_id(true);
        $_SESSION['user'] = $user;
        $sessId = session_id();
        
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $dev = header_get_device_name();
        
        $sessionExists = false;
        $db_old_session_id = null;

        if ($selector) {
            $stmtCheck = $pdo->prepare("SELECT id, session_id FROM user_sessions WHERE token_selector = ?");
            $stmtCheck->execute([$selector]);
            $sessionExists = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($sessionExists) {
                $db_old_session_id = $sessionExists['session_id'];
            }
        }

        if ($sessionExists) {
            $stmtUpdate = $pdo->prepare("UPDATE user_sessions SET session_id = ?, ip_address = ?, last_active = NOW() WHERE token_selector = ?");
            $stmtUpdate->execute([$sessId, $ip, $selector]);
            
            if (!empty($db_old_session_id) && $db_old_session_id !== $sessId) {
                $pdo->prepare("UPDATE push_subscription SET session_id = ? WHERE session_id = ?")->execute([$sessId, $db_old_session_id]);
            }
        } else {
            $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?")->execute([$sessId]);
            $stmtSess = $pdo->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, device_name, token_selector, last_active) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmtSess->execute([$user['id'], $sessId, $ip, $ua, $dev, $selector]);
        }
        
        $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")->execute([$user['id']]);
    }
}

// =================================================================
// 2. LOGIC AUTO LOGIN THUẦN PHP
// =================================================================

if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
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

$current_user = $_SESSION['user'] ?? null;

$must_change_pass = false;
if ($current_user && isset($current_user['id'])) {
    if (!isset($_SESSION['user']['is_default_password'])) {
        try {
            $stmt_chk = $pdo->prepare("SELECT is_default_password FROM users WHERE id = ?");
            $stmt_chk->execute([$current_user['id']]);
            $_SESSION['user']['is_default_password'] = $stmt_chk->fetchColumn() ?: 'off';
        } catch (Exception $e) {
            $_SESSION['user']['is_default_password'] = 'off';
        }
    }
    if (($_SESSION['user']['is_default_password'] ?? 'off') === 'on') {
        $must_change_pass = true;
    }
}

if (!function_exists('is_active')) {
    function is_active($path) { 
        $cur = basename($_SERVER['PHP_SELF']);
        $cur = preg_replace('/\.php$/', '', $cur);
        $path = preg_replace('/\.php$/', '', $path);
        
        $aliasMap = [
            'student_detail' => 'manage_students',
            'teacher_detail' => 'manage_users',
            'class_detail' => 'class_check',
            'input_academic_detail' => 'input_academic'
        ];
        
        if (isset($aliasMap[$cur])) {
            $cur = $aliasMap[$cur];
        }

        if (($path == '/' || $path == 'index') && ($cur == 'index' || $cur == '')) return 'active';
        return ($cur == $path) ? 'active' : ''; 
    }
}

// =================================================================
// 3. LOGIC LẤY THÔNG BÁO TICKER 
// =================================================================
$ticker_school = '';
try {
    $ticker_school = $pdo->query("SELECT value FROM config WHERE `key` = 'ticker_school'")->fetchColumn();
} catch (Exception $e) {}

$sys_bc_msg = '';

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
$query_theme = $_GET['theme'] ?? $_COOKIE['theme'] ?? '';
$data_theme_attr = '';
if ($query_theme === 'dark') {
    $data_theme_attr = 'data-theme="dark"';
} elseif ($query_theme === 'light') {
    $data_theme_attr = '';
}
?>
<!DOCTYPE html>
<html lang="vi" <?= $data_theme_attr ?>>
<head>
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/">
    <meta property="og:title" content="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LG3 - Siêu ứng dụng trường THPT Lạng Giang số 3' : 'LG3 - Lang Giang High School No. 3 Super App') ?>">
    <meta property="og:description" content="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hệ thống hỗ trợ tư vấn tâm lý học đường và quản lý nền nếp thi đua dành cho giáo viên, học sinh trường THPT Lạng Giang số 3.' : 'Psychological counseling and discipline management system for Lang Giang High School No. 3.') ?>">
    <meta property="og:image" content="/lg3512512.png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="fb:app_id" content="1071451369390506">

    <meta name="apple-mobile-web-app-title" content="LG3">
    <meta name="application-name" content="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LG3 - Siêu ứng dụng trường THPT Lạng Giang số 3' : 'LG3 Super App') ?>">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, interactive-widget=resizes-content">
    
    <title><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LG3 - Siêu ứng dụng trường THPT Lạng Giang số 3' : 'LG3 Super App') ?></title>
    
    <link rel="icon" href="/lg3192192.png">
    <link rel="manifest" href="static/manifest.json">
    <meta name="theme-color" content="#005fba">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="static/style.css?v=<?= filemtime(__DIR__ . '/../static/style.css') ?>">

    <link href="https://cdn.jsdelivr.net/npm/suneditor@latest/dist/css/suneditor.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/suneditor@latest/dist/suneditor.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('keydown', function(e) {
        if (e.key === ',') {
            const activeEl = document.activeElement;
            if (activeEl && (activeEl.tagName === 'INPUT' || activeEl.tagName === 'TEXTAREA')) {
                const isNumeric = activeEl.type === 'number' || 
                                  activeEl.type === 'text' && (
                                      activeEl.classList.contains('score-input') || 
                                      activeEl.classList.contains('win-input-sm') || 
                                      activeEl.id === 'vPoints' || 
                                      activeEl.id === 'vMaxPenalty' ||
                                      activeEl.name && (activeEl.name.startsWith('score_') || activeEl.name.startsWith('count_'))
                                  );
                
                if (isNumeric) {
                    e.preventDefault();
                    showCommaAlert();
                }
            }
        }
    });

    function showCommaAlert() {
        let alertEl = document.getElementById('commaAlertModal');
        if (!alertEl) {
            alertEl = document.createElement('div');
            alertEl.id = 'commaAlertModal';
            alertEl.style.cssText = `
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0, 0, 0, 0.85);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999999;
                backdrop-filter: blur(5px);
                opacity: 0;
                transition: opacity 0.2s ease;
            `;
            alertEl.innerHTML = `
                <div id="commaAlertBox" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="comma_alert_title" aria-describedby="comma_alert_desc" style="
                    background: var(--bg-card, #1e1e2e);
                    border: 2px solid var(--accent-color, #ff5555);
                    border-radius: 16px;
                    padding: 30px;
                    max-width: 450px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.5);
                    transform: scale(0.9);
                    transition: transform 0.2s ease;
                    outline: none;
                ">
                    <div style="font-size: 64px; margin-bottom: 15px; color: var(--accent-color, #ff5555);">⚠️</div>
                    <h3 id="comma_alert_title" style="margin: 0 0 15px 0; font-size: 20px; font-weight: bold; color: var(--text-main, #ffffff); line-height: 1.4;">
                        <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'CẦN GÕ DẤU \".\" ĐỂ NGĂN CÁCH PHẦN SỐ NGUYÊN VỚI SỐ THẬP PHÂN' : 'Decimal Dot Title') ?>
                    </h3>
                    <p id="comma_alert_desc" style="margin: 0 0 25px 0; font-size: 14px; color: var(--text-muted, #a6adc8); line-height: 1.5;">
                        <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng không sử dụng dấu phẩy (,). Hãy dùng dấu chấm (.) để hệ thống tính toán chính xác.' : 'Decimal Dot Desc') ?>
                    </p>
                    <button onclick="closeCommaAlert()" style="
                        background: var(--accent-color, #ff5555);
                        color: #ffffff;
                        border: none;
                        border-radius: 8px;
                        padding: 12px 30px;
                        font-size: 16px;
                        font-weight: bold;
                        cursor: pointer;
                        width: 100%;
                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                        transition: opacity 0.2s;
                    " onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'ĐÃ HIỂU' : 'Understood') ?>
                    </button>
                </div>
            `;
            document.body.appendChild(alertEl);
        }
        alertEl.style.display = 'flex';
        window.lastActiveElementBeforeCommaAlert = document.activeElement;
        setTimeout(() => {
            alertEl.style.opacity = '1';
            const box = document.getElementById('commaAlertBox');
            if (box) {
                box.style.transform = 'scale(1)';
                box.focus();
            }
            if (window.a11yAnnounce) {
                window.a11yAnnounce(
                    document.getElementById('comma_alert_title').innerText + ". " + 
                    document.getElementById('comma_alert_desc').innerText
                );
            }
        }, 10);
    }

    window.closeCommaAlert = function() {
        const alertEl = document.getElementById('commaAlertModal');
        if (alertEl) {
            alertEl.style.opacity = '0';
            setTimeout(() => {
                alertEl.style.display = 'none';
                if (window.lastActiveElementBeforeCommaAlert) {
                    window.lastActiveElementBeforeCommaAlert.focus();
                    window.lastActiveElementBeforeCommaAlert = null;
                }
            }, 200);
        }
    };
    </script>
    <style>
        :root { --titlebar-height: 32px; }
        .electron-titlebar {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: var(--titlebar-height);
            background: var(--bg-card); z-index: 99999; -webkit-app-region: drag; 
            border-bottom: 1px solid var(--border-color); justify-content: space-between; align-items: center;
        }
        .pwa-promo-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6); z-index: 10000;
            display: none; justify-content: center; align-items: center; backdrop-filter: blur(4px);
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
            background: #005fba; color: #ffffff !important; 
            text-decoration: none; font-weight: 700; border-radius: 10px; margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(0, 95, 186, 0.4); transition: transform 0.1s, opacity 0.2s;
        }
        [data-theme="dark"] .pwa-btn-switch {
            background: #ffffff !important;
            color: #000000 !important;
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
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
        .et-left { display: flex; align-items: center; padding-left: 10px; gap: 8px; font-size: 12px; font-weight: 600; color: var(--text-main); }
        .et-right { display: flex; align-items: center; height: 100%; -webkit-app-region: no-drag; }
        .et-clock { font-size: 13px; font-weight: 600; margin-right: 15px; color: var(--text-muted); min-width: 140px; text-align: right; }
        .et-theme-btn { width: 32px; height: 24px; border: none; background: transparent; border-radius: 4px; margin-right: 5px; cursor: pointer; color: var(--text-muted); }
        .et-btn { width: 46px; height: 100%; border: none; background: transparent; display: flex; justify-content: center; align-items: center; cursor: pointer; }
        .et-btn svg { width: 10px; height: 10px; } 
        .et-btn svg path, .et-btn svg rect, .et-btn svg polygon { fill: var(--text-main); }
        .et-btn:hover { background-color: var(--bg-hover); }
        .et-btn-close:hover { background-color: var(--danger-color); } .et-btn-close:hover svg polygon { fill: #ffffff; }
        .icon-maximize, .icon-restore { display: none; }

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

        .nav-link {
            position: relative; margin: 4px 12px; padding: 12px 14px;
            border-radius: 8px; transition: all 0.2s ease;
            display: flex; align-items: center;
            text-decoration: none; color: var(--text-muted); font-weight: 500;
        }
        .nav-link i { width: 24px; text-align: center; margin-right: 12px; font-size: 16px; transition: transform 0.2s; flex-shrink: 0; }
        .nav-link:hover i { transform: scale(1.1); color: var(--primary-color); }
        .nav-link:not(.active):hover { background-color: var(--bg-hover); color: var(--accent-color); }
        .nav-link.active { background-color: rgba(37, 99, 235, 0.1); color: var(--primary-color); font-weight: 700; }
        .nav-link.active i { color: var(--primary-color); }

        .btn-login-sidebar { font-weight: bold; justify-content: center; background: var(--bg-hover); color: var(--primary-color); border: 1px solid var(--border-color); }

        .pc-header-toggle { 
            display: flex !important; justify-content: flex-end; align-items: center; 
            width: 100%; margin-bottom: 10px; gap: 15px; 
        }
        .mobile-header { display: none !important; }

        .custom-ticker-wrap { 
            flex: 1; display: flex; align-items: center; overflow: hidden; white-space: nowrap; 
            margin-right: auto; color: var(--primary-color); font-weight: 500; font-size: 13px; 
            background: rgba(0, 95, 186, 0.05); padding: 8px 15px; border-radius: 6px; 
            border: 1px solid rgba(0, 95, 186, 0.1); 
        }
        .custom-ticker-content { display: inline-block; padding-left: 100%; white-space: nowrap; }
        @keyframes marquee-infinite { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }

        .readonly-banner {
            background-color: rgba(245, 158, 11, 0.1); color: #b45309; border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 12px 20px; border-radius: 8px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02); animation: fadeInBanner 0.5s ease-out;
        }
        .readonly-banner i { font-size: 18px; color: #f59e0b; }
        html[data-theme="dark"] body .readonly-banner { color: #fcd34d; border-color: rgba(245, 158, 11, 0.2); }
        @keyframes fadeInBanner { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* BỐ CỤC CHUẨN & THANH GẠT */
        body { overflow-x: hidden; }
        
        .sidebar { 
            width: var(--sidebar-width, 260px); 
            background: var(--bg-sidebar, var(--bg-card)); 
            border-right: 1px solid var(--border-color); 
            display: flex; 
            flex-direction: column; 
            padding: 20px 15px; 
            height: 100dvh; 
            position: fixed; 
            top: 0; 
            left: 0; 
            z-index: 1000; 
            transition: transform 0.3s ease, background 0.3s; 
            box-sizing: border-box; 
        }

        .sidebar-menu-scroll {
            flex: 1; 
            overflow-y: auto; 
            overflow-x: hidden;
            position: relative; 
        }

        .sidebar-footer { 
            flex-shrink: 0; 
            margin-top: auto; 
            padding-bottom: 0; 
        }
        
        .main-content {
            margin-left: var(--sidebar-width, 260px) !important; 
            width: calc(100% - var(--sidebar-width, 260px)) !important; 
            min-height: 100vh; box-sizing: border-box;
        }

        .sidebar-active-slider {
            position: absolute;
            width: 3px; 
            background: var(--primary-color); 
            border-radius: 4px;
            opacity: 0; 
            z-index: 10; 
            pointer-events: none;
            transition: transform 0.25s cubic-bezier(0.25, 1, 0.5, 1), height 0.2s ease, left 0.25s ease;
        }

        @media (max-width: 991px) {
            .mobile-header { 
                display: flex !important; align-items: center; justify-content: space-between; 
                gap: 5px; width: 100%; flex-wrap: wrap !important; padding-bottom: 5px; 
            }
            .pc-header-toggle { display: none !important; }
            .mobile-header > div:first-child { flex: 1; min-width: 60%; } 
            .mobile-header > div:nth-child(2) { margin-left: auto; }
            .custom-ticker-wrap { 
                width: 100% !important; margin: 5px 0 0 0 !important; order: 999; 
                font-size: 11px; border: none; background: transparent; padding: 0; flex: 0 0 100% !important;
            }
            .main-content { margin-left: 0 !important; width: 100% !important; }
        }

        /* CHẾ ĐỘ NHÚNG IFRAME (FLUTTER APP) */
        body.is-iframe .sidebar, 
        body.is-iframe .mobile-header, 
        body.is-iframe .pc-header-toggle, 
        body.is-iframe .electron-titlebar, 
        body.is-iframe .custom-ticker-wrap,
        body.is-iframe .readonly-banner { display: none !important; }

        body.is-iframe .main-content {
            margin-left: 0 !important; 
            width: 100% !important; 
            padding: 10px !important;
            transform: translateZ(0); 
            will-change: transform;
            -webkit-transform: translate3d(0,0,0);
            backface-visibility: hidden;
            perspective: 1000;
        }
        body.is-iframe { 
            background: var(--bg-card); 
            overscroll-behavior-y: none; 
        }

        /* FIX AMOLED CHO HEADER CHUNG */
        .pc-header, .mobile-header, .bottom-nav, .user-dropdown {
            background-color: var(--bg-card);
            border-color: var(--border-color);
        }
        .search-bar input {
            background-color: var(--bg-input);
            color: var(--text-main);
            border-color: var(--border-color);
        }
        .dropdown-item { color: var(--text-main); }
        .dropdown-item:hover { background-color: var(--bg-hover); }

        html[data-theme="dark"] body .sidebar-active-slider { background: var(--primary-color); }
        html[data-theme="dark"] body .btn-login-sidebar { color: var(--primary-color) !important; }
    </style>

    <canvas id="keepAwakeCanvas" width="1" height="1" style="position: fixed; top: 0; left: 0; pointer-events: none; z-index: 999999;"></canvas>
<script>
    const canvas = document.getElementById('keepAwakeCanvas');
    const gl = canvas.getContext('webgl');
    function renderLoop() {
        gl.clearColor(0, 0, 0, 0.01);
        gl.clear(gl.COLOR_BUFFER_BIT);
        requestAnimationFrame(renderLoop);
    }
    renderLoop();
</script>
    
    <script>
        (function() {
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const qTheme = urlParams.get('theme');
                if (qTheme) {
                    localStorage.setItem('theme_mode', qTheme);
                    if (qTheme === 'dark') {
                        document.documentElement.setAttribute('data-theme', 'dark');
                    } else if (qTheme === 'light') {
                        document.documentElement.removeAttribute('data-theme');
                    }
                    return;
                }
                const savedMode = localStorage.getItem('theme_mode') || 'system';
                let themeToApply = 'light';
                if (savedMode === 'dark') { themeToApply = 'dark'; } 
                else if (savedMode === 'system') {
                    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { themeToApply = 'dark'; }
                }
                if (themeToApply === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); }
            } catch (e) {}
        })();

        window.LANG = <?= json_encode($translation_dict, JSON_UNESCAPED_UNICODE) ?>;
        if (!window.currentLangCode) {
            window.currentLangCode = '<?= $_SESSION['lang'] ?? 'vi' ?>';
        }
        window.toggleLanguage = function() {
            const currentLang = window.currentLangCode;
            const newLang = currentLang === 'vi' ? 'en' : 'vi';
            const separator = window.location.search ? '&' : '?';
            
            // Thông báo giọng nói và hiện Toast ngay lập tức
            const msg = newLang === 'vi' ? 'Đã chuyển sang Tiếng Việt' : 'Changed to English';
            if (window.Toastify) {
                Toastify({ text: '✅ ' + msg, duration: 2500, style: { background: "#10b981" } }).showToast();
            }
            if (window.a11yAnnounce) {
                window.a11yAnnounce(msg);
            }

            fetch(window.location.pathname + window.location.search + separator + 'lang=' + newLang)
                .then(() => {
                    window.currentLangCode = newLang; // Cập nhật lại biến trạng thái phía client
                    window._langJustChanged = true;   // Báo loadPage biết cần swap outer layout
                    if (typeof window.loadPage === 'function') {
                        window.loadPage(window.location.href, false, { force: true });
                    } else {
                        window.location.reload();
                    }
                })
                .catch(() => {
                    window.location.reload();
                });
        };
    </script>

<style>
[data-theme="dark"] [style*="#fff7ed"], [data-theme="dark"] [style*="#fef9c3"], [data-theme="dark"] [style*="#e2e8f0"], [data-theme="dark"] [style*="#f1f5f9"], [data-theme="dark"] [style*="#f8fafc"], [data-theme="dark"] [style*="#fef3c7"], [data-theme="dark"] [style*="#eff6ff"], [data-theme="dark"] [style*="#f0fdf4"], [data-theme="dark"] .note-box, [data-theme="dark"] .info-box { background: #111111 !important; color: var(--text-main) !important; border-color: var(--border-color) !important; }
</style>

</head>
<body class="<?= !empty($is_iframe) ? 'is-iframe' : '' ?>">
    <?php
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $X = 90; $Y = 14; 
    $is_unsupported = false; $is_danger = false; 
    $err_title = __('browser_not_supported', "Trình duyệt không hỗ trợ"); $err_desc = "";
    $show_supermium = false; $is_mobile = false;

    if (!empty($ua)) {
        $current_version = 0;
        if (preg_match('/(Chrome|Edg|Firefox)\/(\d+)/i', $ua, $m)) $current_version = (int)$m[2];

        $is_old_windows = preg_match('/Windows NT (5\.|6\.[0-3])/i', $ua);
        $os_name = __('old_windows', "Windows phiên bản cũ");
        if (preg_match('/Windows NT 5\.[12]/i', $ua)) $os_name = "Windows XP";
        elseif (preg_match('/Windows NT 6\.1/i', $ua)) $os_name = "Windows 7";
        elseif (preg_match('/Windows NT 6\.3/i', $ua)) $os_name = "Windows 8.1";

        if (preg_match('/Android\s+([\d\.]+)/i', $ua)) {
            $is_mobile = true;
            if ($current_version > 0 && $current_version < $X) {
                $is_unsupported = true;
                $err_desc = __('android_browser_old_desc', "Trình duyệt trên Android của bạn quá cũ. Hãy cập nhật lên bản ") . $X . "+.";
            }
        } elseif (preg_match('/OS\s+(\d+)_/i', $ua, $matches)) {
            $is_mobile = true;
            $ios_ver = (int)$matches[1];
            if ($ios_ver < $Y) {
                $is_unsupported = true;
                if (strpos($ua, 'iPhone') !== false && $ios_ver <= 12) {
                    $is_danger = true;
                    $err_title = __('device_not_supported', "Thiết bị không hỗ trợ");
                    $err_desc = __('old_iphone_desc', "Bạn đang dùng iPhone đời cũ. Vui lòng cập nhật phần mềm hoặc nâng cấp thiết bị.");
                } else {
                    $err_desc = __('ios_requirement_desc', "Thiết bị yêu cầu iOS ") . $Y . __('ios_requirement_desc_suffix', "+ để hiển thị chính xác.");
                }
            }
        } else {
            $is_ie = preg_match('/MSIE|Trident/i', $ua);
            if ($is_ie) {
                $is_unsupported = true; $is_danger = true; $err_title = __('browser_obsolete', "Trình duyệt lỗi thời");
                if ($is_old_windows) {
                    $show_supermium = true;
                    $err_desc = __('ie_supermium_desc1', "Bạn đang dùng <b>") . $os_name . __('ie_supermium_desc2', "</b>. IE không còn hỗ trợ, hãy cài <b>Supermium</b> để tiếp tục.");
                } else {
                    $err_desc = __('ie_no_support_desc', "Internet Explorer không còn được hỗ trợ. Vui lòng chuyển sang trình duyệt hiện đại.");
                }
            } elseif ($is_old_windows && $current_version < $X) {
                $is_unsupported = true; $show_supermium = true;
                if (preg_match('/Windows NT 5\./i', $ua)) $is_danger = true;
                $err_title = __('browser_update_needed', "Cần cập nhật trình duyệt");
                $err_desc = __('old_browser_supermium_desc1', "Bạn đang dùng <b>") . $os_name . __('old_browser_supermium_desc2', "</b>. Trình duyệt quá cũ, hãy cài đặt <b>Supermium</b> để tiếp tục.");
            } elseif (!$is_old_windows && $current_version > 0 && $current_version < $X) {
                $is_unsupported = true;
                $err_desc = __('update_browser_desc1', "Vui lòng cập nhật trình duyệt lên phiên bản mới nhất (v") . $X . __('update_browser_desc2', "+).");
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
        .win-card { background: #ffffff; width: 90%; max-width: 480px; margin: 50px auto; border-top: 5px solid <?= $theme_color ?>; border-radius: 12px; padding: 40px 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.15); border: 1px solid #e2e8f0; }
        .icon-wrapper { width: 80px; height: 80px; background-color: <?= $bg_icon ?>; border-radius: 50%; display: inline-block; line-height: 80px; vertical-align: middle; margin-bottom: 20px; }
        .err-title { color: <?= $theme_color ?>; font-size: 24px; font-weight: 700; margin: 0 0 10px 0; }
        .err-desc { color: #64748b; font-size: 15px; line-height: 1.6; margin-bottom: 25px; }
        .info-box { background: #f1f5f9; padding: 15px; border-radius: 8px; text-align: left; margin-bottom: 25px; border: 1px solid #e2e8f0; }
        .info-box-content { color: #64748b; font-family: "Courier New", monospace !important; font-size: 12px; word-wrap: anywhere; background: #e2e8f0; padding: 8px; border-radius: 6px; margin-top: 8px; }
        .win-btn { display: block; width: 100%; height: 48px; line-height: 48px; border-radius: 8px; color: white !important; text-decoration: none; font-weight: 600; font-size: 15px; margin-bottom: 12px; border: none; cursor: pointer; }
        .btn-supermium { background-color: #6366f1; } .btn-chrome { background-color: #10b981; } .btn-reload { background-color: <?= $is_danger ? '#64748b' : '#005fba' ?>; }
    </style>

<style>
[data-theme="dark"] [style*="#fff7ed"], [data-theme="dark"] [style*="#fef9c3"], [data-theme="dark"] [style*="#e2e8f0"], [data-theme="dark"] [style*="#f1f5f9"], [data-theme="dark"] [style*="#f8fafc"], [data-theme="dark"] [style*="#fef3c7"], [data-theme="dark"] [style*="#eff6ff"], [data-theme="dark"] [style*="#f0fdf4"], [data-theme="dark"] .note-box, [data-theme="dark"] .info-box { background: #111111 !important; color: var(--text-main) !important; border-color: var(--border-color) !important; }
</style>

</head>
<body>
    <div class="win-card">
        <div class="icon-wrapper"><i class="fas <?= $is_danger ? 'fa-shield-virus' : 'fa-exclamation-triangle' ?>" aria-hidden="true" style="font-size: 35px; color: <?= $theme_color ?>;"></i></div>
        <h2 class="err-title"><?= $err_title ?></h2>
        <p class="err-desc"><?= $err_desc ?></p>
        <div class="info-box"><div style="font-weight: 600; color: #1e293b;"><i class="fas fa-microchip" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thiết bị nhận diện:' : 'Device Detected') ?></div><div class="info-box-content"><?= htmlspecialchars($ua) ?></div></div>
        <div>
            <?php if ($show_supermium): ?><a href="https://win32subsystem.live/supermium/" target="_blank" class="win-btn btn-supermium"><i class="fas fa-rocket" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tải Supermium (Khuyên dùng)' : 'Download Supermium') ?></a><?php endif; ?>
            <?php if (!$is_mobile): ?><a href="https://www.google.com/chrome/" target="_blank" class="win-btn btn-chrome"><i class="fab fa-chrome" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tải Google Chrome' : 'Download Chrome') ?></a>
            <?php else: ?><button onclick="window.location.reload()" class="win-btn btn-reload"><i class="fas fa-sync-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thử truy cập lại' : 'Retry Access') ?></button><?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php exit(); endif; ?>

    <div id="electronTitlebar" class="electron-titlebar">
        <div class="et-left">
            <img src="/lg3192192.png" style="width: 16px; height: 16px;">
            <span><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Siêu ứng dụng LG3' : 'LG3 Super App') ?></span>
        </div>
        <div class="et-right">
            <div id="etClock" class="et-clock">--:--:--</div>
            <button class="et-theme-btn" onclick="cycleThemeMode()" aria-label="Toggle Theme"><i id="etThemeIcon" class="fas fa-desktop" aria-hidden="true"></i></button>
            <button class="et-btn" onclick="if(window.electronAPI) window.electronAPI.minimize()" aria-label="Minimize"><svg viewBox="0 0 10.2 1"><rect width="10.2" height="1"></rect></svg></button>
            <button class="et-btn" onclick="if(window.electronAPI) window.electronAPI.toggleMaximize()" aria-label="Maximize or Restore"><svg class="icon-maximize" viewBox="0 0 10 10" style="display:block"><path d="M0,0v10h10V0H0z M9,9H1V1h8V9z"></path></svg><svg class="icon-restore" viewBox="0 0 10 10" style="display:none"><path d="M2.1,0v2H0v8.1h8.2v-2h2V0H2.1z M7.2,9.2H1.1V3h6.1V9.2z M9.2,7.1h-1V2H3.1V1h6.1V7.1z"></path></svg></button>
            <button class="et-btn et-btn-close" onclick="if(window.electronAPI) window.electronAPI.close()" aria-label="Close"><svg viewBox="0 0 10 10"><polygon points="10.2,0.7 9.5,0 5.1,4.4 0.7,0 0,0.7 4.4,5.1 0,9.5 0.7,10.2 5.1,5.8 9.5,10.2 10.2,9.5 5.8,5.1"></polygon></svg></button>
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <div style="text-align:center; padding-bottom:20px; margin-bottom:10px; border-bottom:1px solid var(--border-color); flex-shrink: 0;">
            <div style="display:flex; align-items:center; justify-content:center; gap: 10px; margin-bottom: 5px;">
                <a href="index" id="sidebar-logo-link" style="text-decoration:none;" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Siêu ứng dụng trường THPT Lạng Giang số 3' : 'LG3 Super App') ?>">
                    <h2 style="color:var(--primary-color); margin:0; font-size: 18px; display: flex; align-items: center; gap: 8px;">
                        <img src="/lg3192192.png" style="width: 32px; height: 32px; object-fit: contain;" alt="Logo LG3">
                        <span title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Siêu ứng dụng trường THPT Lạng Giang số 3' : 'LG3 Super App') ?>">LG3</span>
                    </h2>
                </a>
            </div>
            <div style="font-size:12px; color:var(--text-muted); margin-top:5px;">
                <?= $current_user ? htmlspecialchars($current_user['full_name'] ?? $current_user['username']) : (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Guest' : 'Guest') ?>
            </div>
        </div>
        
        <div class="sidebar-menu-scroll">
            <div id="sidebar-active-slider" class="sidebar-active-slider"></div>

            <a href="/" class="nav-link <?= is_active('index.php') ?>" 
               data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trang chủ' : 'Home') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xem thông báo, tin tức và các hoạt động mới nhất của nhà trường.' : 'Home Desc') ?>">
                <i class="fas fa-home" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trang chủ' : 'Home') ?>
            </a>
            <a href="ranking" class="nav-link <?= is_active('ranking.php') ?>"
               data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bảng xếp hạng' : 'Ranking') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cập nhật thứ hạng thi đua nền nếp và học tập của các lớp trong tuần.' : 'Ranking Desc') ?>">
                <i class="fas fa-trophy" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bảng xếp hạng' : 'Ranking') ?>
            </a>
            <!-- <a href="news" class="nav-link <?= is_active('news.php') ?>"
               data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'News' : 'News') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xem các bài viết/thông báo của nhà trường.' : 'News Desc') ?>">
                <i class="fas fa-newspaper" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'News' : 'News') ?>
            </a> -->
            <a href="tracuudiemthi" class="nav-link <?= is_active('tracuudiemthi.php') ?>"
               data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tra cứu điểm thi' : 'Exam Lookup') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tra cứu điểm của các kì thi.' : 'Exam Lookup Desc') ?>">
                <i class="fas fa-check " aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tra cứu điểm thi' : 'Exam Lookup') ?>
            </a>
            <a href="grammar_check" class="nav-link <?= is_active('grammar_check.php') ?>"
               data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kiểm tra ngữ pháp AI' : 'Grammar AI Check') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kiểm tra các lỗi chính tả/Ngữ pháp bằng trí tuệ nhân tạo (AI).' : 'Grammar Ai Desc') ?>">
                <i class="fas fa-spell-check" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kiểm tra ngữ pháp AI' : 'Grammar AI Check') ?>
            </a>

            <div style="margin: 20px 0 5px 15px; font-size: 11px; color: var(--text-muted); font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TƯ VẤN TÂM LÝ' : 'COUNSELING') ?></div>
            
            <?php if ($current_user && in_array($current_user['role'], ['TEACHER', 'ADMIN'])): ?>
            <a href="consulting_dashboard" class="nav-link <?= is_active('consulting_dashboard.php') ?>"
               data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chat' : 'Chat') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Khu vực dành cho giáo viên trả lời các câu hỏi ẩn danh từ học sinh.' : 'Qa Dashboard Desc') ?>">
                <i class="fas fa-comments" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chat' : 'Chat') ?>
            </a>
            <?php endif; ?>

            <a href="consulting_ai" class="nav-link <?= is_active('consulting_ai.php') ?>"
               data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Góc tư vấn' : 'Counseling Corner') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trò chuyện với trợ lý ảo tâm lý để được hỗ trợ và giải tỏa căng thẳng.' : 'Counseling Corner Desc') ?>">
                <i class="fas fa-lightbulb" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Góc tư vấn' : 'Counseling Corner') ?>
            </a>
            <a href="consulting_test" class="nav-link <?= is_active('consulting_test.php') ?>"
               data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hướng nghiệp' : 'Career Advice') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bài kiểm tra định hướng nghề nghiệp và khám phá sở thích bản thân.' : 'Career Advice Desc') ?>">
                <i class="fas fa-file-medical-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hướng nghiệp' : 'Career Advice') ?>
            </a>

            <?php if ($current_user): ?>
                <?php if (in_array($current_user['role'], ['TEACHER', 'ADMIN', 'RED_FLAG'])): ?>
                    <div style="margin: 20px 0 5px 15px; font-size: 11px; color: var(--text-muted); font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'HỌC TẬP & NỀN NẾP' : 'ACADEMIC & DISCIPLINE') ?></div>
                    
                    <a href="gate_check" class="nav-link <?= is_active('gate_check.php') ?>"
                       data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kiểm tra cổng' : 'Gate Check') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ghi nhận các lỗi vi phạm của học sinh tại khu vực cổng trường.' : 'Gate Check Desc') ?>">
                        <i class="fas fa-torii-gate" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kiểm tra cổng' : 'Gate Check') ?>
                    </a>
                    <a href="class_check" class="nav-link <?= is_active('class_check.php') ?>"
                       data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kiểm tra lớp' : 'Class Check') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chấm điểm nền nếp sinh hoạt và vệ sinh tại các lớp học.' : 'Class Check Desc') ?>">
                        <i class="fas fa-clipboard-check" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Kiểm tra lớp' : 'Class Check') ?>
                    </a>
                    <a href="violation_history" class="nav-link <?= is_active('violation_history.php') ?>"
                        data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lịch sử vi phạm' : 'Violation History') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Theo dõi các lỗi vi phạm đã được ghi nhận.' : 'Violation History Desc') ?>">
                         <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lịch sử vi phạm' : 'Violation History') ?>
                    </a>
                    
                    <?php if (in_array($current_user['role'], ['TEACHER', 'ADMIN', 'RED_FLAG'])): ?>
                    <a href="input_academic" class="nav-link <?= is_active('input_academic.php') ?>"
                       data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm học tập' : 'Academic Score') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cập nhật tổng điểm và số tiết học của các lớp để tính thi đua.' : 'Academic Scores Desc') ?>">
                        <i class="fas fa-pen-square" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Điểm học tập' : 'Academic Score') ?>
                    </a>
                    <?php endif; ?>
                    
                    <div style="margin: 20px 0 5px 15px; font-size: 11px; color: var(--text-muted); font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'BÁO CÁO' : 'REPORTS') ?></div>
                    <a href="export_vpbs" class="nav-link <?= is_active('export_vpbs.php') ?>"
                       data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xuất báo cáo' : 'Export Report') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tải xuống báo cáo thống kê vi phạm chi tiết dưới dạng Excel.' : 'Export Report Desc') ?>">
                        <i class="fas fa-file-excel" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xuất báo cáo' : 'Export Report') ?>
                    </a>
                    <a href="teacher_dashboard" class="nav-link <?= is_active('teacher_dashboard.php') ?>"
                       data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp của tôi' : 'My Class') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quản lý danh sách học sinh, miễn trừ và theo dõi vi phạm lớp chủ nhiệm.' : 'My Class Desc') ?>">
                        <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp của tôi' : 'My Class') ?>
                    </a>
                <?php endif; ?>

                <?php if ($current_user['role'] == 'ADMIN'): ?>
                    <div style="margin: 20px 0 5px 15px; font-size: 11px; color: var(--text-muted); font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Admin System' : 'Admin System') ?></div>
                    <a href="manage_users" class="nav-link <?= is_active('manage_users.php') ?>"
                       data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Manage Accounts' : 'Manage Accounts') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thêm, sửa, xóa và phân quyền tài khoản cho giáo viên và học sinh.' : 'Manage Accounts Desc') ?>">
                        <i class="fas fa-users-cog" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Account' : 'Account') ?>
                    </a>
                    <a href="manage_students" class="nav-link <?= is_active('manage_students.php') ?>"
                       data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quản lý học sinh' : 'Manage Students') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Danh sách hồ sơ học sinh toàn trường.' : 'Manage Students Desc') ?>">
                        <i class="fas fa-user-graduate" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quản lý học sinh' : 'Manage Students') ?>
                    </a>
                    <a href="manage_violations" class="nav-link <?= is_active('manage_violations.php') ?>"
                       data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quản lý vi phạm' : 'Manage Violations') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chỉnh sửa danh mục lỗi vi phạm và ngày bắt đầu học kỳ.' : 'Manage Violations Desc') ?>">
                        <i class="fas fa-cogs" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quản lý vi phạm' : 'Manage Violations') ?>
                    </a>
                    <a href="traffic_monitor" class="nav-link <?= is_active('traffic_monitor.php') ?>"
                        data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Traffic Monitor' : 'Traffic Monitor') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Theo dõi lưu lượng truy cập và tình trạng quá tải của hệ thống.' : 'Traffic Monitor Desc') ?>">
                         <i class="fas fa-chart-area" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Traffic Monitor' : 'Traffic Monitor') ?>
                    </a>
                    <a href="banned_ips_history" class="nav-link <?= is_active('banned_ips_history.php') ?>"
                        data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ip Ban Log' : 'Ip Ban Log') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Theo dõi lịch sử các địa chỉ IP bị tường lửa khóa tự động.' : 'Ip Ban Log Desc') ?>">
                         <i class="fas fa-shield-virus" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ip Ban Log' : 'Ip Ban Log') ?>
                    </a>
                    <a href="quanlydiem" class="nav-link <?= is_active('quanlydiem.php') ?>"
                        data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập điểm thi' : 'Enter Exam Scores') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập điểm thi của HS theo các kì thi.' : 'Enter Exam Scores Desc') ?>">
                         <i class="fas fa-check" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập điểm thi' : 'Enter Exam Scores') ?>
                    </a>
                <?php endif; ?>

                <div style="margin: 20px 0 5px 15px; font-size: 11px; color: var(--text-muted); font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'CÁ NHÂN' : 'PERSONAL') ?></div>
                <a href="chess" class="nav-link <?= is_active('chess.php') ?>"
                   data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cờ vua' : 'Chess') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chơi cờ vua giải trí hoặc thách đấu với người khác.' : 'Chess Game Desc') ?>">
                    <i class="fas fa-chess" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cờ vua' : 'Chess') ?>
                </a>
                <a href="my_profile" class="nav-link <?= is_active('my_profile.php') ?>"
                   data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hồ sơ của tôi' : 'My Profile') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xem thông tin tài khoản và chỉnh sửa ảnh đại diện.' : 'My Profile Desc') ?>">
                    <i class="fas fa-id-card" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hồ sơ của tôi' : 'My Profile') ?>
                </a>
                
                <?php if (in_array($current_user['role'], ['STUDENT', 'RED_FLAG'])): ?>
                <a href="student_violations" class="nav-link <?= is_active('student_violations.php') ?>"
                   data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vi phạm của tôi' : 'My Violations') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xem lại lịch sử các lỗi vi phạm đã bị ghi nhận.' : 'My Violations Desc') ?>">
                    <i class="fas fa-user-clock" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vi phạm của tôi' : 'My Violations') ?>
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="sidebar-footer">
            <?php if ($current_user): ?>
                <a href="intro" class="nav-link <?= is_active('intro.php') ?>" 
                   data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cài đặt thông báo' : 'Notification Settings') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tùy chỉnh tùy chọn nhận thông báo và thông tin phiên bản.' : 'Notification Settings Desc') ?>">
                    <i class="fas fa-bell" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cài đặt thông báo' : 'Notification Settings') ?>
                </a>
                
                <a href="change_password" class="nav-link <?= is_active('change_password.php') ?>"
                   data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đổi mật khẩu' : 'Change Password') ?>" data-desc="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thay đổi mật khẩu đăng nhập để bảo vệ tài khoản.' : 'Change Password Desc') ?>">
                    <i class="fas fa-key" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đổi mật khẩu' : 'Change Password') ?>
                </a>
                
                <a href="logout" class="nav-link" style="color:var(--danger-color);" data-title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng xuất' : 'Logout') ?>">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng xuất' : 'Logout') ?>
                </a>
            <?php else: ?>
                <a href="login.php?next=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="nav-link btn-login-sidebar">
                    <i class="fas fa-sign-in-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng nhập' : 'Login') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content" role="main">
        <div class="mobile-header">
            <div style="display: flex; align-items: center; min-width: 0; flex: 1; overflow: hidden;">
                <button class="mobile-menu-btn" onclick="toggleSidebar()" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thu gọn hoặc mở rộng thanh thực đơn' : 'Toggle Sidebar') ?>" style="flex-shrink: 0; margin-right: 5px; background: none; border: none; color: var(--text-main); font-size: 20px;"><i class="fas fa-bars" aria-hidden="true"></i></button>
                <h3 title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Siêu ứng dụng trường THPT Lạng Giang số 3' : 'LG3 Super App') ?>" style="margin:0; color:#005fba; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 16px; line-height: 1.2;">
                    LG3
                </h3>
            </div>

            <div style="display:flex; align-items:center; flex-shrink: 0; margin-left: auto;">
                <div class="live-clock" style="font-weight: 700; font-size: 12px; color: var(--primary-color); display: none; text-align: right; line-height: 1.1; margin-right: 8px; user-select: none;">
                    --:--
                </div>
                
                <button class="lang-toggle-btn" onclick="toggleLanguage()" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thay đổi ngôn ngữ' : 'Change Language') ?>" style="flex-shrink: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: none; border: none; color: var(--text-main); font-weight: bold; font-size: 14px; cursor: pointer;">
                    <?= $_SESSION['lang'] === 'vi' ? '🇻🇳' : '🇬🇧' ?>
                </button>

                <button class="theme-toggle-btn" onclick="cycleThemeMode()" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thay đổi giao diện sáng tối' : 'Change Theme') ?>" style="flex-shrink: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: none; border: none; color: var(--text-main);">
                    <i id="themeIconMobile" class="fas fa-desktop" style="font-size: 14px;" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="pc-header-toggle"> 
            <div style="display: flex; align-items: center; gap: 15px; margin-left: auto;">
                <div class="live-clock" style="font-weight: 600; font-size: 14px; color: var(--text-muted); display: none; white-space:nowrap; flex-shrink: 0;">--:--:--</div>
                
                <button class="lang-toggle-btn" onclick="toggleLanguage()" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thay đổi ngôn ngữ' : 'Change Language') ?>" style="flex-shrink: 0; background: none; border: none; color: var(--text-main); cursor: pointer; font-weight: bold; font-size: 14px; display: flex; align-items: center; gap: 5px; padding: 4px 8px; border-radius: 4px;">
                    <?= $_SESSION['lang'] === 'vi' ? '🇻🇳 VN' : '🇬🇧 EN' ?>
                </button>

                <button class="theme-toggle-btn" onclick="cycleThemeMode()" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thay đổi giao diện sáng tối' : 'Change Theme') ?>" style="flex-shrink: 0; background: none; border: none; color: var(--text-main); cursor: pointer;"><i id="themeIconPC" class="fas fa-desktop" aria-hidden="true"></i></button>
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
                
                function applyMarquee(container) {
                    if (!container) return;
                    const tickerContent = container.querySelector('.custom-ticker-content');
                    if (tickerContent) {
                        const textLength = `<?= addslashes($ticker_text_clean) ?>`.length;
                        let duration = (textLength * 0.15) + 8; 
                        tickerContent.style.animation = `marquee-infinite ${duration}s linear infinite`;
                    }
                }

                const pcToggle = document.querySelector('.pc-header-toggle');
                if (pcToggle) {
                    pcToggle.insertAdjacentHTML('afterbegin', tickerHtml);
                    applyMarquee(pcToggle);
                }

                const mobileHeader = document.querySelector('.mobile-header');
                if (mobileHeader) {
                    mobileHeader.insertAdjacentHTML('beforeend', tickerHtml);
                    applyMarquee(mobileHeader);
                }
            });
        </script>
        <?php endif; ?>
        <?php if (defined('LG3_READ_ONLY_ACTIVE') && LG3_READ_ONLY_ACTIVE === true): ?>
        <div class="readonly-banner">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <div>
                <strong><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chế độ Chỉ đọc:' : 'Read Only Mode') ?></strong> <?= htmlspecialchars(LG3_READ_ONLY_REASON) ?><br>
                <span style="font-size: 12px; opacity: 0.8;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn vẫn có thể xem báo cáo và dữ liệu bình thường, nhưng các tính năng thêm/sửa/xóa đã tạm thời bị khóa.' : 'Read Only Desc') ?></span>
            </div>
        </div>
        <?php endif; ?>
        <script>
        window.initGlobalSSEListeners = function() {
            if (!window.SSEManager) return;

            window.SSEManager.on('CHESS_CHALLENGE', function(data) {
                if (window.currentChessChallengeConfirm) {
                    try { window.currentChessChallengeConfirm.close(); } catch(e){}
                }
                
                window.currentChessChallengeConfirm = WinUI.confirm(
                    '<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cờ vua' : 'Chess')) ?>',
                    (data.challenger_name || 'Đối thủ') + ' <?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'muốn thách đấu cờ vua với bạn!' : 'Challenge Received')) ?>',
                    function() {
                        fetch('api/chess_api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=accept&match_id=' + data.match_id
                        }).then(res => res.json()).then(res => {
                            if (res.status === 'success') {
                                if (window.loadPage) {
                                    window.loadPage('chess?match_id=' + data.match_id);
                                } else {
                                    window.location.href = 'chess?match_id=' + data.match_id;
                                }
                            } else {
                                WinUI.alert('<?= addslashes((($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi' : 'System Error')) ?>', res.msg || 'Không thể chấp nhận lời mời');
                            }
                        });
                    },
                    function() {
                        fetch('api/chess_api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'action=decline&match_id=' + data.match_id
                        });
                    }
                );
            });

            window.SSEManager.on('CHESS_CANCELLED', function(data) {
                if (window.currentChessChallengeConfirm) {
                    try { window.currentChessChallengeConfirm.close(); } catch(e){}
                }
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            if (window.initGlobalSSEListeners) {
                window.initGlobalSSEListeners();
            }
        });
        </script>
        <div id="ajax-page-wrapper">