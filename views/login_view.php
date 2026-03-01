<!DOCTYPE html>
<html>
<head>
    <title>Đăng nhập - App Nền Nếp</title>
    <link rel="stylesheet" href="static/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root { --titlebar-height: 32px; }
        .electron-titlebar { display: none; position: fixed; top: 0; left: 0; width: 100%; height: var(--titlebar-height); background: #ffffff; justify-content: space-between; align-items: center; z-index: 99999; -webkit-app-region: drag; border-bottom: 1px solid #e2e8f0; user-select: none; box-sizing: border-box; }
        body.is-electron .electron-titlebar { display: flex; }
        body.is-electron { padding-top: var(--titlebar-height) !important; }
        .et-left { display: flex; align-items: center; padding-left: 10px; gap: 8px; font-size: 12px; font-weight: 600; color: #1d1d1f; }
        .et-right { display: flex; align-items: center; height: 100%; -webkit-app-region: no-drag; }
        .et-clock { font-size: 13px; font-weight: 600; margin-right: 15px; color: #64748b; min-width: 140px; text-align: right; }
        .et-theme-btn { width: 32px; height: 24px; border: none; background: transparent; border-radius: 4px; margin-right: 5px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b; }
        .et-theme-btn:hover { background-color: rgba(0,0,0,0.05); color: #005fba; }
        .et-btn { width: 46px; height: 100%; border: none; background: transparent; display: flex; justify-content: center; align-items: center; cursor: pointer; outline: none; }
        .et-btn svg { width: 10px; height: 10px; }
        .et-btn svg path, .et-btn svg rect, .et-btn svg polygon { fill: #1d1d1f; transition: fill 0.2s; }
        .et-btn:hover { background-color: #e5e5e5; }
        .et-btn-close:hover { background-color: #d93025 !important; }
        .et-btn-close:hover svg polygon { fill: white !important; }
        .icon-maximize, .icon-restore { display: none; }
        [data-theme="dark"] .electron-titlebar { background: #1e293b; border-bottom-color: #334155; }
        [data-theme="dark"] .et-left { color: #ffffff; }
        [data-theme="dark"] .et-clock { color: #cbd5e1; }
        [data-theme="dark"] .et-btn:hover { background-color: rgba(255,255,255,0.1); }
        [data-theme="dark"] .et-btn svg path, [data-theme="dark"] .et-btn svg rect, [data-theme="dark"] .et-btn svg polygon, [data-theme="dark"] .et-theme-btn { fill: #ffffff !important; color: #ffffff !important; }
        [data-theme="dark"] body { background-color: #0f172a !important; }
        [data-theme="dark"] .win-card { background-color: #1e293b !important; color: #fff !important; border-color: #334155; }
        [data-theme="dark"] h2 { color: #60a5fa !important; }
        [data-theme="dark"] label, [data-theme="dark"] p, [data-theme="dark"] a { color: #cbd5e1 !important; }
        [data-theme="dark"] .win-input { background-color: #334155 !important; color: #fff !important; border: none; }
    </style>
    <script>
        function applyLoginTheme() {
            const savedMode = localStorage.getItem('theme_mode') || 'system';
            let effectiveTheme = savedMode;
            if (savedMode === 'system') effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            if (effectiveTheme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
            else document.documentElement.removeAttribute('data-theme');
            const icon = document.getElementById('etThemeIcon');
            if(icon) icon.className = effectiveTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        }
        function cycleThemeMode() {
            const current = localStorage.getItem('theme_mode') || 'system';
            const modes = ['light', 'dark', 'system'];
            let next = modes[(modes.indexOf(current) + 1) % modes.length];
            localStorage.setItem('theme_mode', next);
            applyLoginTheme();
        }
        document.addEventListener("DOMContentLoaded", applyLoginTheme);
    </script>
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f0f2f5;">

    <div id="electronTitlebar" class="electron-titlebar">
        <div class="et-left">
            <img src="https://testifiyonline.xyz/lg3192192.png" style="width: 16px; height: 16px;">
            <span>LG3 - Tư vấn tâm lý và Quản lý thi đua</span>
        </div>
        <div class="et-right">
            <div id="etClock" class="et-clock">--/--/---- --:--:--</div>
            <button class="et-theme-btn" onclick="cycleThemeMode()" title="Đổi giao diện"><i id="etThemeIcon" class="fas fa-desktop"></i></button>
            <button class="et-btn" onclick="if(window.electronAPI) window.electronAPI.minimize()"><svg viewBox="0 0 10.2 1"><rect width="10.2" height="1"></rect></svg></button>
            <button class="et-btn" onclick="if(window.electronAPI) window.electronAPI.toggleMaximize()"><svg class="icon-maximize" viewBox="0 0 10 10" style="display:block"><path d="M0,0v10h10V0H0z M9,9H1V1h8V9z"></path></svg><svg class="icon-restore" viewBox="0 0 10 10" style="display:none"><path d="M2.1,0v2H0v8.1h8.2v-2h2V0H2.1z M7.2,9.2H1.1V3h6.1V9.2z M9.2,7.1h-1V2H3.1V1h6.1V7.1z"></path></svg></button>
            <button class="et-btn et-btn-close" onclick="if(window.electronAPI) window.electronAPI.close()"><svg viewBox="0 0 10 10"><polygon points="10.2,0.7 9.5,0 5.1,4.4 0.7,0 0,0.7 4.4,5.1 0,9.5 0.7,10.2 5.1,5.8 9.5,10.2 10.2,9.5 5.8,5.1"></polygon></svg></button>
        </div>
    </div>

    <div class="win-card" style="width: 380px; padding: 40px; text-align:center;">
        <h2 style="color: var(--accent-color); margin-bottom: 5px;">Hệ Thống Nền Nếp</h2>
        <p style="color:#666; margin-bottom:30px;">Trường THPT Lạng Giang số 3</p>
        
        <form method="POST" style="text-align:left;">
            <input type="hidden" name="next" value="<?= htmlspecialchars($next_url) ?>">
            <label style="font-weight:bold; font-size:13px;">Tài khoản (GV hoặc Mã HS):</label>
            <input type="text" name="username" class="win-input" required autofocus value="<?= htmlspecialchars($username??'') ?>">
            
            <label style="font-weight:bold; font-size:13px; margin-top:15px; display:block;">Mật khẩu:</label>
            
            <div style="position: relative; margin-bottom: 10px;">
                <input type="password" name="password" id="loginPassword" class="win-input" required style="margin-bottom: 0; padding-right: 40px;">
                
                <span id="togglePasswordBtn" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 15px; padding: 5px; transition: color 0.2s;">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                </span>
            </div>
            
            <label class="custom-checkbox-wrapper">
                <input type="checkbox" name="remember_me" id="remember_me" checked>
                <span class="custom-checkmark"></span>
                <span class="checkbox-label-text">Giữ trạng thái đăng nhập</span> </label>
            
            <button type="submit" class="win-btn mt-20" style="width: 100%; height: 45px; font-size: 16px;">
                Đăng Nhập
            </button>
        </form>

        <div style="margin-top:20px; font-size:13px;">
            <a href="/ranking" style="color:#666; text-decoration:none;">Bảng xếp hạng các lớp trong trường</a>
        </div>

        <?php if($error): ?>
            <p style="color:red; margin-top:15px; font-size:14px;"><?= $error ?></p>
        <?php endif; ?>
    </div>
    
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (navigator.userAgent.toLowerCase().indexOf(' electron/') > -1) document.body.classList.add('is-electron');
            setInterval(() => {
                const now = new Date();
                const el = document.getElementById('etClock');
                if(el) el.innerText = now.toLocaleString('en-GB');
            }, 1000);
            if (window.electronAPI && window.electronAPI.onWindowStateChange) {
                window.electronAPI.onWindowStateChange((state) => {
                    const iconMax = document.querySelector('.icon-maximize');
                    const iconRes = document.querySelector('.icon-restore');
                    if (state === 'maximized') { if(iconMax) iconMax.style.display = 'none'; if(iconRes) iconRes.style.display = 'block'; }
                    else { if(iconMax) iconMax.style.display = 'block'; if(iconRes) iconRes.style.display = 'none'; }
                });
            }
        });
    </script>
    <script>
        // JS Xử lý Mắt xem mật khẩu
        document.getElementById('togglePasswordBtn').addEventListener('click', function () {
            const pwdInput = document.getElementById('loginPassword');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
                this.style.color = 'var(--primary-color)'; // Sáng lên màu xanh khi xem pass
            } else {
                pwdInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
                this.style.color = 'var(--text-muted)'; // Tối lại khi ẩn pass
            }
        });
    </script>
</body>
</html>