<?php
include 'includes/header.php';
?>

<style>
    /* 1. CẤU HÌNH CSS CƠ BẢN */
    .home-card { 
        background: var(--bg-card); border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
        width: 92%; max-width: 450px; margin: 30px auto; padding: 30px 25px; 
        text-align: center; border: 1px solid var(--border-color); box-sizing: border-box; 
    }
    .app-icon-wrapper { 
        width: 100px; height: 100px; background: transparent; display: flex; align-items: center; justify-content: center; 
        margin: 0 auto 10px; box-shadow: none; border: none;
    }
    .app-icon-img { width: 100%; height: 100%; object-fit: contain; border-radius: 0; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2)); }
    .app-title { font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
    .app-desc { color: var(--text-muted); font-size: 0.9rem; line-height: 1.4; margin-bottom: 25px; padding: 0 10px; }
    .state-section { display: none; margin-bottom: 15px; }
    .ios-guide-box { background: var(--bg-hover); border: 1px dashed var(--border-color); padding: 12px; border-radius: 12px; font-size: 13px; color: var(--text-main); text-align: left; }
    
    /* 2. KHUNG LOGIN BOX & NÚT BẤM */
    .login-box { background: #fff1f2; border: 1px solid #fecdd3; padding: 15px; border-radius: 12px; transition: all 0.3s ease; }
    .login-box-title { color:#be123c; font-weight:bold; font-size:14px; }
    .login-box-desc { font-size:13px; color:#881337; margin:5px 0 12px; }
    .action-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px; border-radius: 10px; font-weight: 600; font-size: 14px; margin-bottom: 10px; cursor: pointer; border: none; text-decoration: none; transition: 0.2s; box-sizing: border-box; }
    .btn-apk { background: #166534; color: white; padding: 14px; font-size: 15px; box-shadow: 0 4px 15px rgba(22, 101, 52, 0.3); margin-bottom: 5px; }
    .btn-apk:hover { background: #15803d; }
    .btn-install-outline { background: transparent; color: var(--text-main); border: 2px dashed var(--border-color); padding: 10px; font-size: 13px; }
    .btn-install { background: #0f172a; color: white; }
    .btn-login { background: #0284c7; color: white; }
    .download-stats { font-size: 12.5px; color: var(--text-muted); margin-bottom: 15px; font-weight: 500; }
    .download-stats b { color: #166534; }
    .apk-banner { background: #ecfdf5; color: #065f46; padding: 15px; border-radius: 12px; font-size: 13px; margin-bottom: 20px; border: 1px solid #a7f3d0; text-align: left; }
    .apk-banner a { color: #059669; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; margin-top: 8px; background: #d1fae5; padding: 6px 12px; border-radius: 6px; border: 1px solid #6ee7b7; transition: 0.2s; }
    .apk-banner a:hover { background: #a7f3d0; }
    
    /* 3. CẤU HÌNH DARK MODE */
    [data-theme="dark"] .login-box { background: #3f1018; border-color: #881337; }
    [data-theme="dark"] .login-box-title { color: #f43f5e; } [data-theme="dark"] .login-box-desc { color: #fda4af; } 
    [data-theme="dark"] .btn-install { background: #e2e8f0; color: #0f172a !important; }
    [data-theme="dark"] .btn-install-outline { color: #94a3b8; border-color: #475569; }
    [data-theme="dark"] .apk-banner { background: #022c22; color: #a7f3d0; border-color: #064e3b; }
    [data-theme="dark"] .apk-banner a { color: #34d399; background: #064e3b; border-color: #047857; }
    [data-theme="dark"] .apk-banner a:hover { background: #065f46; }
    [data-theme="dark"] .download-stats b { color: #4ade80; }
    [data-theme="dark"] .btn-login { background: #1e293b; color: #38bdf8 !important; border: 1px solid #334155; box-shadow: none; }
    [data-theme="dark"] .btn-login:hover { background: #334155; }
    [data-theme="dark"] .home-card { border-color: #334155; }

    /* Mũi tên hướng dẫn */
    .menu-guide-overlay { position: fixed; top: 15px; left: 60px; z-index: 10001; display: none; pointer-events: none; animation: floatLeftRight 1.5s infinite ease-in-out; display: flex; align-items: center; gap: 10px; }
    .guide-arrow { font-size: 32px; color: var(--accent-color); filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)); }
    .guide-text-bubble { background: #fff; color: #005fba; padding: 8px 14px; border-radius: 20px; border-top-left-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); font-weight: 700; font-size: 14px; border: 2px solid #005fba; white-space: nowrap; }
    @keyframes floatLeftRight { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(10px); } }

    /* =========================================================
       GIAO DIỆN DASHBOARD (ĐÃ BỎ SẠCH ANIMATION TĨNH)
       ========================================================= */
    #dashboard-container { width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px 0; box-sizing: border-box; }
    .dash-header { display: flex; justify-content: space-between; align-items: center; background: var(--bg-card); padding: 20px 25px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid var(--border-color); margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
    .dash-header-title h2 { margin: 0 0 5px; font-size: 20px; color: var(--primary-color); }
    .dash-header-title p { margin: 0; font-size: 13px; color: var(--text-muted); }
    .dash-actions-row { display: flex; gap: 12px; flex-wrap: wrap; }
    .dash-action-btn { background: var(--bg-input); border: 1px solid var(--border-color); color: var(--text-main); padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .dash-action-btn:hover { background: var(--bg-hover); color: var(--accent-color); border-color: var(--accent-color); transform: translateY(-2px); }
    .dash-btn-noti { background: #047857; color: #fff; border: none; padding: 10px 18px; border-radius: 10px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .dash-btn-noti.disabled { background: var(--bg-hover); color: #047857; cursor: default; }

    .dash-grid { display: grid; grid-template-columns: 1fr; gap: 25px; align-items: start; }
    @media (max-width: 992px) { .dash-grid { grid-template-columns: 1fr; } }
    
    .dash-panel { background: var(--bg-card); border-radius: 16px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid var(--border-color); }
    .dash-panel-title { display: flex; justify-content: space-between; align-items: center; margin-top: 0; margin-bottom: 20px; font-size: 16px; font-weight: 700; }
    .dash-panel-link { font-size: 12px; font-weight: normal; color: var(--text-muted); text-decoration: none; }
    .dash-panel-link:hover { color: var(--primary-color); text-decoration: underline; }

    .group-cards-wrapper { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
    .group-card { background: var(--bg-input); border-radius: 12px; padding: 15px; border: 1px solid var(--border-color); }
    .group-card-title { font-size: 14px; font-weight: 800; color: var(--primary-color); margin-bottom: 15px; text-align: center; text-transform: uppercase; border-bottom: 1px dashed var(--border-color); padding-bottom: 10px; }
    .group-class-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-color); }
    .group-class-row:last-child { border-bottom: none; padding-bottom: 0; }
    
    .news-widget-item { display: flex; gap: 12px; align-items: center; text-decoration: none; background: var(--bg-input); padding: 12px; border-radius: 10px; border: 1px solid var(--border-color); transition: 0.2s; margin-bottom: 10px; }
    .news-widget-item:hover { background: var(--bg-hover); border-color: var(--primary-color); transform: translateX(5px); }
</style>

<div id="appMenuGuide" class="menu-guide-overlay" style="display: none;">
    <i class="fas fa-arrow-left guide-arrow" aria-hidden="true"></i> 
    <div class="guide-text-bubble"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bấm vào đây để bắt đầu' : 'Click here to start') ?></div>
</div>

<?php if ($is_logged_in): ?>
    <div id="dashboard-container">
        <div class="dash-header">
            <div class="dash-header-title">
                <h2><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Siêu ứng dụng LG3' : 'LG3 Super App') ?></h2>
                <p><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chào mừng' : 'Welcome back') ?> <b><?= htmlspecialchars($_SESSION['user']['full_name'] ?? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn' : 'You')) ?></b> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'quay trở lại hệ thống!' : 'back to the system!') ?></p>
            </div>
            
            <div class="dash-actions-row">
                <?php if ($user && $user['role'] == 'ADMIN'): ?>
                    <a href="import_students.php" class="dash-action-btn"><i class="fas fa-file-import" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập Dữ Liệu' : 'Import Data') ?></a>
                    <a href="manage_users.php" class="dash-action-btn"><i class="fas fa-users-cog" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tài Khoản' : 'Accounts') ?></a>
                <?php elseif ($user && ($user['role'] == 'TEACHER' || $user['role'] == 'RED_FLAG')): ?>
                    <a href="gate_check.php" class="dash-action-btn"><i class="fas fa-edit" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trực cổng' : 'Gate Duty') ?></a>
                    <a href="teacher_dashboard.php" class="dash-action-btn"><i class="fas fa-chalkboard-teacher" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp Của Tôi' : 'My Class') ?></a>
                <?php elseif ($user && $user['role'] == 'STUDENT'): ?>
                    <a href="my_violations.php" class="dash-action-btn"><i class="fas fa-user-check" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi Của Tôi' : 'My Violations') ?></a>
                    <a href="ranking.php" class="dash-action-btn" style="color:#92400e; border-color:#fcd34d;"><i class="fas fa-trophy" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xem Đầy Đủ BXH' : 'View Full Ranking') ?></a>
                <?php endif; ?>

                <button id="btn-noti" class="dash-btn-noti"><i class="fas fa-bell" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bật Thông Báo' : 'Turn on Notifications') ?></button>
                <a href="logout.php" class="dash-action-btn" role="button" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng xuất' : 'Logout') ?>" style="color: var(--danger-color); border-color: #fecaca; background: #fef2f2;"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></a>
            </div>
        </div>

        <div class="dash-grid">
            <div class="dash-panel">
                <h3 class="dash-panel-title" style="color: #92400e;">
                    <span><i class="fas fa-award" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Dẫn đầu các Nhóm Thi Đua' : 'Leading Competition Groups') ?></span>
                    <a href="ranking.php" class="dash-panel-link"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xem bảng đầy đủ' : 'View Full Board') ?> <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                </h3>
                <div id="dash-rank-groups" class="group-cards-wrapper">
                    <div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 20px;"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang tải dữ liệu BXH...' : 'Loading ranking data...') ?></div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div id="unauth-container" class="home-card">
        <div class="app-icon-wrapper">
            <img src="/lg3512512.png" class="app-icon-img" alt="Logo">
        </div>
        <h1 class="app-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trường THPT Lạng Giang số 3' : 'Lang Giang No 3 High School') ?></h1>
        <p class="app-desc"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cổng thông tin điện tử và Hệ sinh thái tiện ích số hỗ trợ học đường.' : 'E-portal and digital utility ecosystem for school support.') ?></p>

        <div id="section-install-android" class="state-section">
            <p style="font-size: 13px; color: var(--text-main); margin-bottom: 12px; font-weight: 600;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chọn phiên bản phù hợp với máy của bạn:' : 'Choose the version suitable for your device:') ?></p>
            <a href="https://github.com/ubuntu2310fake/LG3-TVTL-QLNN-Mobile/releases/download/1.0.5_r1/LG3_TVTL_QLNN_Android_arm64-v8a.apk" class="action-btn btn-apk" style="margin-bottom: 8px;">
                <i class="fab fa-android fa-lg" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Máy Android đời mới (Khuyên dùng)' : 'Modern Android device (Recommended)') ?>
            </a>
            <a href="https://github.com/ubuntu2310fake/LG3-TVTL-QLNN-Mobile/releases/download/1.0.5_r1/LG3_TVTL_QLNN_Android_armeabi-v7a.apk" class="action-btn" style="background: #64748b; color: white; margin-bottom: 15px;">
                <i class="fas fa-mobile-alt fa-lg" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Máy Android đời cũ (32-bit)' : 'Old Android device (32-bit)') ?>
            </a>
            <div class="download-stats"><i class="fas fa-fire" style="color: #ef4444;" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đã có' : 'Has') ?> <b class="apk-download-count">...</b> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'lượt tải' : 'downloads') ?></div>
            <p style="font-size: 12px; color: var(--text-muted); margin: 15px 0 10px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '-- Hoặc dùng bản Web --' : '-- Or use Web version --') ?></p>
            <button id="btn-install-android" class="action-btn btn-install-outline"><i class="fas fa-plus-square" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thêm vào Màn hình chính' : 'Add to Home Screen') ?></button>
        </div>

        <div id="section-install-ios" class="state-section">
            <div class="ios-guide-box"><i class="fab fa-apple" aria-hidden="true"></i> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cài đặt trên iPhone:' : 'Install on iPhone:') ?></b><br>1. <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bấm nút <b>Chia sẻ</b>' : 'Tap the <b>Share</b> button') ?> <i class="fas fa-share-square" aria-hidden="true"></i><br>2. <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chọn <b>"Thêm vào MH chính"</b>.' : 'Select <b>"Add to Home Screen"</b>.') ?></div>
        </div>

        <div id="section-apk-recommend" class="state-section">
            <div class="apk-banner">
                <i class="fas fa-rocket" aria-hidden="true"></i> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn muốn trải nghiệm tốt hơn?' : 'Want a better experience?') ?></b><br>
                <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hệ thống LG3 nay đã có ứng dụng gốc cho Android, tải nhanh và mượt mà hơn bản Web hiện tại.' : 'The LG3 system now has a native Android app, faster and smoother than the web version.') ?>
                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 12px;">
                    <a href="https://github.com/ubuntu2310fake/LG3-TVTL-QLNN-Mobile/releases/download/1.0.4_r1/LG3_TVTL_QLNN_Android_arm64-v8a.apk" style="justify-content: center;"><i class="fas fa-download" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tải cho máy đời mới' : 'Download for modern device') ?></a>
                </div>
            </div>
        </div>

        <div id="section-login-required" class="state-section">
            <div class="login-box">
                <span class="login-box-title"><i class="fas fa-lock" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Yêu cầu đăng nhập' : 'Login Required') ?></span>
                <p class="login-box-desc"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng nhập để hệ thống nhận diện thiết bị và cấp quyền truy cập.' : 'Login for the system to identify your device and grant access.') ?></p>
                <a href="login.php" class="action-btn btn-login"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'ĐĂNG NHẬP HỆ THỐNG' : 'LOGIN TO SYSTEM') ?></a>
            </div>
        </div>
    </div>
<?php endif; ?>


<script>
    var PUBLIC_KEY = "<?= $vapid_public_key ?>";
    var FIREBASE_SENDER_ID = "318238111941"; 
    var IS_LOGGED_IN = <?= json_encode($is_logged_in) ?>;
    var deferredPrompt; 

    window.onbeforeinstallprompt = (e) => { e.preventDefault(); deferredPrompt = e; };

    function getSmartDeviceName() {
        var ua = navigator.userAgent;
        if (/Android/i.test(ua)) return "Android Device";
        if (/iPhone/i.test(ua)) return "iPhone";
        if (/Windows/i.test(ua)) return "Windows PC";
        return "Unknown Device";
    }

    function urlBase64ToUint8Array(b64) {
        var p = '='.repeat((4-b64.length%4)%4);
        var b = (b64+p).replace(/-/g,'+').replace(/_/g,'/');
        var r = window.atob(b);
        var o = new Uint8Array(r.length);
        for(var i=0;i<r.length;++i) o[i]=r.charCodeAt(i);
        return o;
    }

    async function fetchGitHubDownloads() {
        try {
            var res = await fetch('https://api.github.com/repos/ubuntu2310fake/LG3-TVTL-QLNN-Mobile/releases');
            var releases = await res.json();
            var totalDownloads = 0;
            releases.forEach(release => { if (release.assets) { release.assets.forEach(asset => { totalDownloads += asset.download_count; }); } });
            var elements = document.querySelectorAll('.apk-download-count');
            elements.forEach(el => el.innerText = totalDownloads);
        } catch (e) {
            var elements = document.querySelectorAll('.apk-download-count');
            elements.forEach(el => el.innerText = ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'hàng trăm' : 'hundreds of') ?>")); 
        }
    }

    async function loadDashboardData() {
        try {
            let resRank = await fetch('api/ranking_api.php?_t=' + new Date().getTime(), { headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache' }, credentials: 'same-origin' });
            let jsonRank = await resRank.json();
            let htmlRank = '';
            
            if(jsonRank.status === 'success' && jsonRank.grouped_ranking && Object.keys(jsonRank.grouped_ranking).length > 0) {
                for (const [groupName, classes] of Object.entries(jsonRank.grouped_ranking)) {
                    let top3 = classes.slice(0, 3);
                    let medals = ['🥇', '🥈', '🥉'];
                    let classListHtml = top3.map((c, i) => `
                        <div class="group-class-row">
                            <div style="font-size: 13px; font-weight: 600; color: var(--text-main);">${medals[i] || '#'+(i+1)} <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lớp' : 'Class') ?> ${c.class_name}</div>
                            <div style="font-weight: 800; color: #92400e; font-size: 14px;">${c.tb}</div>
                        </div>
                    `).join('');
                    htmlRank += `<div class="group-card"><div class="group-card-title">${groupName}</div>${classListHtml}</div>`;
                }
            } else { htmlRank = `<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 10px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chưa có dữ liệu.' : 'No data available.') ?></div>`; }
            const rankBox = document.getElementById('dash-rank-groups');
            if(rankBox) rankBox.innerHTML = htmlRank;
        } catch(e) { console.error('Dashboard rank error:', e); }


    }

    // Tự động reload dashboard khi nhận được tín hiệu SSE thay đổi dữ liệu từ app-shell-pro.js
    window.addEventListener('violation_data_changed', () => {
        loadDashboardData();
    });

    var initIndexUI = function() {
        if (!IS_LOGGED_IN) {
            fetchGitHubDownloads();
        }

        var btnNoti = document.getElementById('btn-noti');
        var guideOverlay = document.getElementById('appMenuGuide');
        var btnInstallAndroid = document.getElementById('btn-install-android');
        
        var secAndroid = document.getElementById('section-install-android');
        var secIos = document.getElementById('section-install-ios');
        var secLogin = document.getElementById('section-login-required');
        var secApkRecommend = document.getElementById('section-apk-recommend');
        
        if(btnInstallAndroid) {
            btnInstallAndroid.onclick = async () => { if (deferredPrompt) deferredPrompt.prompt(); };
        }

        async function autoCheckAndClaim() {
            if (window.electronAPI) {
                if (localStorage.getItem('electron_fcm_registered')) {
                    if(btnNoti) { btnNoti.innerHTML = '<i class="fas fa-check-circle" aria-hidden="true"></i> ' + ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'ĐÃ BẬT TRÊN APP' : 'ENABLED ON APP') ?>"); btnNoti.classList.add('disabled'); }
                }
                return;
            }
            if (!('serviceWorker' in navigator)) return;
            var reg = await navigator.serviceWorker.ready;
            var sub = await reg.pushManager.getSubscription();
            if (sub) {
                if(btnNoti) { btnNoti.innerHTML = '<i class="fas fa-check-circle" aria-hidden="true"></i> ' + ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'THÔNG BÁO ĐANG BẬT' : 'NOTIFICATIONS ON') ?>"); btnNoti.classList.add('disabled'); }
            }
        }

        if(btnNoti) {
            btnNoti.onclick = async () => {
                if (btnNoti.classList.contains('disabled')) return;
                btnNoti.innerText = ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang kết nối...' : 'Connecting...') ?>");
                btnNoti.classList.add('disabled');

                if (window.electronAPI && window.electronAPI.setupFCM) {
                    var timer = setTimeout(() => {
                        alert("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '⚠️ Quá thời gian (15s)! Máy chủ không phản hồi.' : '⚠️ Timeout (15s)! Server not responding.') ?>");
                        btnNoti.innerHTML = '<i class="fas fa-bell" aria-hidden="true"></i> ' + ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'THỬ LẠI' : 'RETRY') ?>"); btnNoti.classList.remove('disabled');
                    }, 15000);

                    try {
                        window.electronAPI.setupFCM(FIREBASE_SENDER_ID);
                        window.electronAPI.onFCMToken(async (token) => {
                            clearTimeout(timer);
                            btnNoti.innerText = ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang lưu...' : 'Saving...') ?>");
                            var res = await fetch('api/subscribe.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ endpoint: token, type: 'electron_fcm', device_model: "Windows PC (App)", keys: { p256dh: "", auth: "" } }) });
                            if((await res.json()).status === 'success') {
                                alert("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '✅ Đã bật thông báo thành công!' : '✅ Notifications enabled successfully!') ?>");
                                btnNoti.innerHTML = '<i class="fas fa-check-circle" aria-hidden="true"></i> ' + ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'ĐÃ BẬT TRÊN APP' : 'ENABLED ON APP') ?>");
                                localStorage.setItem('electron_fcm_registered', 'true');
                            } else { throw new Error("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi Server' : 'Server Error') ?>"); }
                        });
                    } catch (e) { clearTimeout(timer); alert(("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi: ' : 'Error: ') ?>") + e.message); btnNoti.innerHTML = '<i class="fas fa-bell" aria-hidden="true"></i> ' + ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'THỬ LẠI' : 'RETRY') ?>"); btnNoti.classList.remove('disabled'); }
                    return;
                }

                try {
                    if ((await Notification.requestPermission()) !== 'granted') throw new Error("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cần cấp quyền.' : 'Permission needed.') ?>");
                    var reg = await navigator.serviceWorker.ready;
                    var sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(PUBLIC_KEY) });
                    var jsonSub = sub.toJSON(); jsonSub.device_model = getSmartDeviceName();
                    var res = await fetch('api/subscribe.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(jsonSub) });
                    if((await res.json()).status === 'success') { 
                        if (typeof Toastify !== 'undefined') {
                            Toastify({text: "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '✅ Đã bật thông báo thành công!' : '✅ Notifications enabled successfully!') ?>", style:{background:"#047857"}}).showToast();
                        } else {
                            alert("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '✅ Đã bật thông báo thành công!' : '✅ Notifications enabled successfully!') ?>"); 
                        }
                        btnNoti.innerHTML = '<i class="fas fa-check-circle" aria-hidden="true"></i> ' + ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'THÔNG BÁO ĐANG BẬT' : 'NOTIFICATIONS ON') ?>");
                        btnNoti.classList.add('disabled');
                    }
                } catch(e) { alert(("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi Web: ' : 'Web Error: ') ?>") + e.message); btnNoti.innerHTML='<i class="fas fa-bell" aria-hidden="true"></i> ' + ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'THỬ LẠI' : 'RETRY') ?>"); btnNoti.classList.remove('disabled'); }
            };
        }

        var isIos = /iPhone|iPad/.test(navigator.userAgent) && !window.MSStream;
        var isAndroid = /Android/i.test(navigator.userAgent);
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
        var isMobileUA = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        var isMobile = isMobileUA && (navigator.maxTouchPoints > 0);
        var isElectron = navigator.userAgent.toLowerCase().indexOf(' electron/') > -1;
        
        // KIỂM TRA PHÂN LUỒNG LOGIC JS
        if (IS_LOGGED_IN) {
            autoCheckAndClaim();
            loadDashboardData();
        } else {
            // Chỉ bật mở chức năng tải app cho view chưa đăng nhập
            if (isStandalone || !isMobile || isElectron) {
                if(secAndroid) secAndroid.style.display='none';
                if(secIos) secIos.style.display='none';
                if (isAndroid && isStandalone) { if(secApkRecommend) secApkRecommend.style.display='block'; }
                if(secLogin) secLogin.style.display='block'; 
            } else {
                if(guideOverlay) guideOverlay.style.display = 'none';
                if(isIos) { if(secIos) secIos.style.display='block'; } else { if(secAndroid) secAndroid.style.display='block'; }
            }
        }

        if (window.electronAPI && window.electronAPI.onNotification && !window.isElectronNotiAttached) {
            window.electronAPI.onNotification((payload) => {
                var title = payload.notification.title || payload.data.title || ("<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thông báo' : 'Notification') ?>");
                var body = payload.notification.body || payload.data.body || "";
                var noti = new Notification(title, { body: body, icon: 'https://qlnn.testifiyonline.xyz/lg3192192.png' });
            });
            window.isElectronNotiAttached = true;
        }
    };
    
    // Giao hoàn toàn cho SPA lo liệu mượt mà, đợi chút rồi vẽ Data vào (không đụng gì đến display: block/none nữa)
    setTimeout(initIndexUI, 150); 
</script>

<?php include 'includes/footer.php'; ?>