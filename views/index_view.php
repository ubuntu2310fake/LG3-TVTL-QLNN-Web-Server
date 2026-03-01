<?php
include 'includes/header.php';
?>

<style>
    /* 1. CẤU HÌNH CSS CƠ BẢN */
    .home-card { 
        background: var(--bg-card); 
        border-radius: 20px; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
        width: 92%; max-width: 450px; 
        margin: 30px auto; padding: 30px 25px; 
        text-align: center; 
        border: 1px solid var(--border-color); 
        box-sizing: border-box; 
    }
    
    /* Logo App */
    .app-icon-wrapper { 
        width: 100px; 
        height: 100px; 
        background: transparent; 
        display: flex; align-items: center; justify-content: center; 
        margin: 0 auto 10px; 
        box-shadow: none; 
        border: none;
    }
    .app-icon-img { 
        width: 100%; 
        height: 100%; 
        object-fit: contain; 
        border-radius: 0; 
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2)); 
    }

    /* Typography */
    .app-title { font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 8px; }
    .app-desc { color: var(--text-muted); font-size: 0.9rem; line-height: 1.4; margin-bottom: 25px; padding: 0 10px; }
    .state-section { display: none; margin-bottom: 15px; }
    
    .ios-guide-box { background: var(--bg-hover); border: 1px dashed var(--border-color); padding: 12px; border-radius: 12px; font-size: 13px; color: var(--text-main); text-align: left; }
    
    /* 2. KHUNG LOGIN BOX (LIGHT MODE) */
    .login-box { 
        background: #fff1f2; 
        border: 1px solid #fecdd3; 
        padding: 15px; 
        border-radius: 12px; 
        transition: all 0.3s ease; 
    }
    .login-box-title { color:#be123c; font-weight:bold; font-size:14px; }
    .login-box-desc { font-size:13px; color:#881337; margin:5px 0 12px; }

    /* NÚT BẤM CHUNG */
    .action-btn { 
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; 
        padding: 12px; border-radius: 10px; 
        font-weight: 600; font-size: 14px; 
        margin-bottom: 10px; cursor: pointer; 
        border: none; text-decoration: none; 
        transition: 0.2s; box-sizing: border-box; 
    }
    
    /* MÀU NÚT (LIGHT MODE) */
    .btn-install { background: #0f172a; color: white; }
    .btn-login { background: #0284c7; color: white; }
    .btn-noti { background: #10b981; color: white; }
    .btn-noti.disabled { background: var(--bg-hover); color: #10b981; cursor: default; }
    
    /* 3. CẤU HÌNH DARK MODE */
    [data-theme="dark"] .login-box {
        background: #3f1018; 
        border-color: #881337;
    }
    [data-theme="dark"] .login-box-title { color: #f43f5e; } 
    [data-theme="dark"] .login-box-desc { color: #fda4af; } 

    [data-theme="dark"] .btn-install { 
        background: #e2e8f0; 
        color: #0f172a !important; 
    }
    
    /* Style giống Sidebar */
    [data-theme="dark"] .btn-login { 
        background: #1e293b; 
        color: #38bdf8 !important; 
        border: 1px solid #334155; 
        box-shadow: none;
    }
    [data-theme="dark"] .btn-login:hover {
        background: #334155; 
    }
    
    [data-theme="dark"] .home-card { border-color: #334155; }

    .menu-grid { display: grid; gap: 10px; margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 20px; }
    .menu-btn { background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-muted); padding: 12px; border-radius: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; font-weight: 500; font-size: 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .menu-btn:hover { background: var(--bg-hover); color: var(--accent-color); }

    /* CSS Mũi tên */
    .menu-guide-overlay { position: fixed; top: 15px; left: 60px; z-index: 10001; display: none; pointer-events: none; animation: floatLeftRight 1.5s infinite ease-in-out; display: flex; align-items: center; gap: 10px; }
    .guide-arrow { font-size: 32px; color: var(--accent-color); filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)); }
    .guide-text-bubble { background: #fff; color: var(--accent-color); padding: 8px 14px; border-radius: 20px; border-top-left-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); font-weight: 700; font-size: 14px; border: 2px solid var(--accent-color); white-space: nowrap; }
    @keyframes floatLeftRight { 0%, 100% { transform: translateX(0); } 50% { transform: translateX(10px); } }
</style>

<div id="appMenuGuide" class="menu-guide-overlay" style="display: none;">
    <i class="fas fa-arrow-left guide-arrow"></i> 
    <div class="guide-text-bubble">Bấm vào đây để bắt đầu</div>
</div>

<div class="home-card">
    <div class="app-icon-wrapper">
        <img src="https://qlnn.testifiyonline.xyz/lg3512512.png" class="app-icon-img" alt="Logo">
    </div>
    
    <h1 class="app-title">Tư vấn tâm lý và Quản lý thi đua</h1>
    <p class="app-desc">Theo dõi thi đua, tra cứu xếp hạng và tư vấn tâm lý học đường 24/7.</p>

    <div id="section-install-android" class="state-section">
        <button id="btn-install-android" class="action-btn btn-install"><i class="fas fa-download"></i> Cài đặt Ứng dụng</button>
    </div>
    <div id="section-install-ios" class="state-section">
        <div class="ios-guide-box">
            <i class="fab fa-apple"></i> <b>Cài đặt trên iPhone:</b><br>1. Bấm nút <b>Chia sẻ</b> <i class="fas fa-share-square"></i><br>2. Chọn <b>"Thêm vào MH chính"</b>.
        </div>
    </div>
    <div id="section-login-required" class="state-section">
        <div class="login-box">
            <span class="login-box-title"><i class="fas fa-lock"></i> Yêu cầu đăng nhập</span>
            <p class="login-box-desc">Đăng nhập để hệ thống nhận diện thiết bị.</p>
            <a href="login" class="action-btn btn-login"><i class="fas fa-sign-in-alt"></i> ĐĂNG NHẬP NGAY</a>
        </div>
    </div>
    <div id="section-authenticated" class="state-section">
        <button id="btn-noti" class="action-btn btn-noti"><i class="fas fa-bell"></i> BẬT THÔNG BÁO</button>
        <div class="menu-grid">
            <?php if ($user && $user['role'] == 'ADMIN'): ?>
                <a href="import_students.php" class="menu-btn"><i class="fas fa-file-import"></i> Import Dữ Liệu</a>
                <a href="manage_users.php" class="menu-btn"><i class="fas fa-users-cog"></i> Quản Lý Tài Khoản</a>
            <?php elseif ($user && ($user['role'] == 'TEACHER' || $user['role'] == 'RED_FLAG')): ?>
                <a href="gate_check.php" class="menu-btn"><i class="fas fa-edit"></i> Kiểm tra cổng</a>
                <a href="teacher_dashboard.php" class="menu-btn"><i class="fas fa-chalkboard-teacher"></i> Lớp Của Tôi</a>
            <?php elseif ($user && $user['role'] == 'STUDENT'): ?>
                <a href="my_violations.php" class="menu-btn"><i class="fas fa-user-check"></i> Lỗi Của Tôi</a>
                <a href="ranking.php" class="menu-btn" style="color:#d97706; border-color:#fcd34d;"><i class="fas fa-trophy"></i> Bảng Xếp Hạng</a>
            <?php endif; ?>
            <a href="logout.php" style="display:block; margin-top:15px; color:#ef4444; font-size:12px; text-decoration:none;">Đăng xuất</a>
        </div>
    </div>
</div>

<script>
    const PUBLIC_KEY = "<?= $vapid_public_key ?>";
    const FIREBASE_SENDER_ID = "318238111941"; 
    
    const IS_LOGGED_IN = <?= json_encode($is_logged_in) ?>;
    const btnNoti = document.getElementById('btn-noti');
    const guideOverlay = document.getElementById('appMenuGuide');
    let deferredPrompt; 

    function getSmartDeviceName() {
        var ua = navigator.userAgent;
        if (/Android/i.test(ua)) return "Android Device";
        if (/iPhone/i.test(ua)) return "iPhone";
        if (/Windows/i.test(ua)) return "Windows PC";
        return "Unknown Device";
    }

    async function autoCheckAndClaim() {
        if (window.electronAPI) {
            if (localStorage.getItem('electron_fcm_registered')) {
                btnNoti.innerHTML = '<i class="fas fa-check-circle"></i> ĐÃ BẬT TRÊN APP';
                btnNoti.classList.add('disabled');
            }
            return;
        }
        if (!('serviceWorker' in navigator)) return;
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        if (sub) {
            const jsonSub = sub.toJSON(); jsonSub.device_model = getSmartDeviceName();
            fetch('api/subscribe.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(jsonSub) });
            btnNoti.innerHTML = '<i class="fas fa-check-circle"></i> ĐÃ BẬT THÔNG BÁO';
            btnNoti.classList.add('disabled');
        }
    }

    // --- SỰ KIỆN BẤM NÚT ---
    btnNoti.addEventListener('click', async () => {
        if (btnNoti.classList.contains('disabled')) return;
        btnNoti.innerText = "Đang kết nối...";
        btnNoti.classList.add('disabled');

        // [CASE 1] ELECTRON APP
        if (window.electronAPI && window.electronAPI.setupFCM) {
            console.log("--> [UI] Bắt đầu quy trình...");
            
            // Timeout 15s
            const timer = setTimeout(() => {
                alert("⚠️ Quá thời gian (15s)!\nMáy chủ Google không phản hồi. Hãy thử khởi động lại App.");
                btnNoti.innerText = "THỬ LẠI";
                btnNoti.classList.remove('disabled');
            }, 15000);

            try {
                // 1. Gửi lệnh
                window.electronAPI.setupFCM(FIREBASE_SENDER_ID);
                
                // 2. Chờ Token
                window.electronAPI.onFCMToken(async (token) => {
                    clearTimeout(timer); // Hủy timeout
                    console.log("--> [UI] Nhận Token:", token);
                    btnNoti.innerText = "Đang lưu...";
                    
                    const res = await fetch('api/subscribe.php', {
                        method: 'POST', 
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({ endpoint: token, type: 'electron_fcm', device_model: "Windows PC (App)", keys: { p256dh: "", auth: "" } })
                    });

                    if((await res.json()).status === 'success') {
                        alert("✅ Đã bật thông báo thành công!");
                        btnNoti.innerHTML = '<i class="fas fa-check-circle"></i> ĐÃ BẬT TRÊN APP';
                        localStorage.setItem('electron_fcm_registered', 'true');
                    } else { throw new Error("Lỗi Server"); }
                });

            } catch (e) { clearTimeout(timer); alert("Lỗi: " + e.message); btnNoti.innerText = "THỬ LẠI"; btnNoti.classList.remove('disabled'); }
            return;
        }

        // [CASE 2] WEB
        try {
            if ((await Notification.requestPermission()) !== 'granted') throw new Error("Cần cấp quyền.");
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(PUBLIC_KEY) });
            const jsonSub = sub.toJSON(); jsonSub.device_model = getSmartDeviceName();
            const res = await fetch('api/subscribe.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(jsonSub) });
            if((await res.json()).status === 'success') { alert("✅ Thành công!"); location.reload(); }
        } catch(e) { alert("Lỗi Web: "+e.message); btnNoti.innerText="THỬ LẠI"; btnNoti.classList.remove('disabled'); }
    });

    function urlBase64ToUint8Array(b64) {
        const p = '='.repeat((4-b64.length%4)%4);
        const b = (b64+p).replace(/-/g,'+').replace(/_/g,'/');
        const r = window.atob(b);
        const o = new Uint8Array(r.length);
        for(let i=0;i<r.length;++i) o[i]=r.charCodeAt(i);
        return o;
    }

    // UI Logic
    window.addEventListener('beforeinstallprompt', (e) => { e.preventDefault(); deferredPrompt = e; });
    document.getElementById('btn-install-android').addEventListener('click', async () => { if (deferredPrompt) deferredPrompt.prompt(); });
    const isIos = /iPhone|iPad/.test(navigator.userAgent) && !window.MSStream;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    // Thay thế dòng: const isMobile = window.innerWidth < 992;
    // Bằng đoạn sau:
    const isMobileUA = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isMobile = isMobileUA && (navigator.maxTouchPoints > 0);
    const isElectron = navigator.userAgent.toLowerCase().indexOf(' electron/') > -1;
    if (isStandalone || !isMobile || isElectron) {
        document.getElementById('section-install-android').style.display='none';
        document.getElementById('section-install-ios').style.display='none';
        if(IS_LOGGED_IN) { document.getElementById('section-authenticated').style.display='block'; autoCheckAndClaim(); } 
        else { document.getElementById('section-login-required').style.display='block'; }
    } else {
        if(guideOverlay) guideOverlay.style.display = 'none';
        if(isIos) document.getElementById('section-install-ios').style.display='block';
        else document.getElementById('section-install-android').style.display='block';
    }

    if (window.electronAPI && window.electronAPI.onNotification) {
        window.electronAPI.onNotification((payload) => {
            const title = payload.notification.title || payload.data.title || "Thông báo";
            const body = payload.notification.body || payload.data.body || "";
            const noti = new Notification(title, { body: body, icon: 'https://qlnn.testifiyonline.xyz/lg3192192.png' });
            noti.onclick = () => { if(payload.data && payload.data.url) window.location.href = payload.data.url; };
        });
    }
</script>

<?php include 'includes/footer.php'; ?>