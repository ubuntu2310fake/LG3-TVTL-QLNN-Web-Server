<!DOCTYPE html>
<html>
<head>
    <title><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng nhập' : 'Login') ?> - <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Super App Lg3' : 'Super App Lg3') ?></title>
    <link rel="stylesheet" href="static/style.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root { --titlebar-height: 32px; }
        body { 
            display: flex; justify-content: center; align-items: center; 
            height: 100vh; margin: 0; 
            background: var(--bg-body); transition: background 0.3s; 
        }
        
        /* ELECTRON TITLEBAR */
        .electron-titlebar { display: none; position: fixed; top: 0; left: 0; width: 100%; height: var(--titlebar-height); background: var(--bg-card); justify-content: space-between; align-items: center; z-index: 99999; -webkit-app-region: drag; border-bottom: 1px solid var(--border-color); user-select: none; box-sizing: border-box; }
        body.is-electron .electron-titlebar { display: flex; }
        body.is-electron { padding-top: var(--titlebar-height); }
        .et-left { display: flex; align-items: center; padding-left: 10px; gap: 8px; font-size: 12px; font-weight: 600; color: var(--text-main); }
        .et-right { display: flex; align-items: center; height: 100%; -webkit-app-region: no-drag; }
        .et-clock { font-size: 13px; font-weight: 600; margin-right: 15px; color: var(--text-muted); min-width: 140px; text-align: right; }
        .et-theme-btn { width: 32px; height: 24px; border: none; background: transparent; border-radius: 4px; margin-right: 5px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-muted); }
        .et-theme-btn:hover { background-color: var(--bg-hover); color: var(--primary-color); }
        .et-btn { width: 46px; height: 100%; border: none; background: transparent; display: flex; justify-content: center; align-items: center; cursor: pointer; outline: none; }
        .et-btn svg { width: 10px; height: 10px; }
        .et-btn svg path, .et-btn svg rect, .et-btn svg polygon { fill: var(--text-main); transition: fill 0.2s; }
        .et-btn:hover { background-color: var(--bg-hover); }
        .et-btn-close:hover { background-color: var(--danger-color); }
        .et-btn-close:hover svg polygon { fill: var(--bg-card); }
        .icon-maximize, .icon-restore { display: none; }

        /* CHECKBOX CUSTOM */
        .custom-checkbox-wrapper { display: flex; align-items: center; cursor: pointer; font-size: 13px; color: var(--text-main); margin-bottom: 15px; user-select: none; }
        .custom-checkbox-wrapper input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
        .custom-checkmark { height: 18px; width: 18px; background-color: var(--bg-input); border: 1px solid var(--border-color); border-radius: 4px; margin-right: 8px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .custom-checkbox-wrapper:hover input ~ .custom-checkmark { border-color: var(--primary-color); }
        .custom-checkbox-wrapper input:checked ~ .custom-checkmark { background-color: var(--primary-color); border-color: var(--primary-color); }
        .custom-checkmark:after { content: ""; display: none; width: 4px; height: 8px; border: solid var(--bg-card); border-width: 0 2px 2px 0; transform: rotate(45deg); margin-bottom: 2px; }
        .custom-checkbox-wrapper input:checked ~ .custom-checkmark:after { display: block; }

        html[data-theme="dark"] body button[type="submit"] { color: #000; font-weight: 800; }
        html[data-theme="dark"] body .custom-checkmark:after { border-color: #000; }

        /* MODAL ANIMATION */
        @keyframes modalFadeIn { 
            from { opacity: 0; transform: translateY(-20px) scale(0.95); } 
            to { opacity: 1; transform: translateY(0) scale(1); } 
        }

        /* ẢNH QR CODE ĐƠN GIẢN, ĐÃ ĐƯỢC THU NHỎ */
        .qr-image {
            max-width: 220px; 
            width: 100%; 
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            margin-top: 10px;
        }
    </style>
    <script>
        window.a11yAnnounce = function(text) {
            let announcer = document.getElementById('a11y-announcer');
            if (!announcer) {
                announcer = document.createElement('div');
                announcer.id = 'a11y-announcer';
                announcer.setAttribute('aria-live', 'assertive');
                announcer.setAttribute('aria-atomic', 'true');
                announcer.style.cssText = 'position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;';
                document.body.appendChild(announcer);
            }
            announcer.textContent = '';
            setTimeout(() => { announcer.textContent = text; }, 50);
        };

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
            let themeMsg = next === 'light' ? 'Chế độ Sáng' : (next === 'dark' ? 'Chế độ Tối' : 'Chế độ Hệ thống');
            window.a11yAnnounce(themeMsg);
        }
        document.addEventListener("DOMContentLoaded", () => {
            applyLoginTheme();
            const changedLang = sessionStorage.getItem('lang_changed_to');
            if (changedLang) {
                sessionStorage.removeItem('lang_changed_to');
                const msg = changedLang === 'vi' ? 'Đã chuyển sang Tiếng Việt' : 'Changed to English';
                setTimeout(() => { window.a11yAnnounce(msg); }, 800);
            }
        });
    </script>
</head>
<body>

    <div id="electronTitlebar" class="electron-titlebar">
        <div class="et-left">
            <img src="/lg3192192.png" style="width: 16px; height: 16px;">
            <span>LG3 - <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Super App LG3' : 'Super App LG3') ?></span>
        </div>
        <div class="et-right">
            <div id="etClock" class="et-clock">--/--/---- --:--:--</div>
            <button class="et-theme-btn" onclick="cycleThemeMode()" aria-label="Toggle Theme"><i id="etThemeIcon" class="fas fa-desktop" aria-hidden="true"></i></button>
            <button class="et-btn" onclick="if(window.electronAPI) window.electronAPI.minimize()" aria-label="Minimize"><svg viewBox="0 0 10.2 1"><rect width="10.2" height="1"></rect></svg></button>
            <button class="et-btn" onclick="if(window.electronAPI) window.electronAPI.toggleMaximize()" aria-label="Maximize or Restore"><svg class="icon-maximize" viewBox="0 0 10 10" style="display:block"><path d="M0,0v10h10V0H0z M9,9H1V1h8V9z"></path></svg><svg class="icon-restore" viewBox="0 0 10 10" style="display:none"><path d="M2.1,0v2H0v8.1h8.2v-2h2V0H2.1z M7.2,9.2H1.1V3h6.1V9.2z M9.2,7.1h-1V2H3.1V1h6.1V7.1z"></path></svg></button>
            <button class="et-btn et-btn-close" onclick="if(window.electronAPI) window.electronAPI.close()" aria-label="Close"><svg viewBox="0 0 10 10"><polygon points="10.2,0.7 9.5,0 5.1,4.4 0.7,0 0,0.7 4.4,5.1 0,9.5 0.7,10.2 5.1,5.8 9.5,10.2 10.2,9.5 5.8,5.1"></polygon></svg></button>
        </div>
    </div>

    <div class="win-card" style="width: 380px; padding: 40px; text-align:center; position: relative;">
        <button onclick="cycleLanguage(event)" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 16px; cursor: pointer; padding: 5px; outline: none;" title="Switch Language">
            <?= ($_SESSION['lang'] ?? 'vi') === 'vi' ? '🇻🇳' : '🇬🇧' ?>
        </button>
        <script>
            function cycleLanguage(e) {
                e.preventDefault();
                const currentLang = '<?= $_SESSION['lang'] ?? 'vi' ?>';
                const newLang = currentLang === 'vi' ? 'en' : 'vi';
                sessionStorage.setItem('lang_changed_to', newLang);
                const separator = window.location.search ? '&' : '?';
                window.location.href = window.location.pathname + window.location.search + separator + 'lang=' + newLang;
            }
        </script>
        <h2 style="color: var(--accent-color); margin-bottom: 5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Super App LG3' : 'Super App LG3') ?></h2>
        <p style="color:var(--text-muted); margin-bottom:30px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trường THPT Lạng Giang số 3' : 'Lang Giang High School No. 3') ?></p>
        
        <?php if (!empty($show_2fa_step)): ?>
        <!-- FORM XÁC THỰC 2FA STEP 2 -->
        <form method="POST" style="text-align:left;">
            <input type="hidden" name="action" value="verify_2fa">
            <input type="hidden" name="next" value="<?= htmlspecialchars($next_url) ?>">
            
            <div style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; padding: 12px; border-radius: 6px; margin-bottom: 20px; text-align: center;">
                <i class="fas fa-shield-alt" style="font-size: 28px; color: #10b981; margin-bottom: 8px;"></i>
                <div style="font-weight: bold; color: var(--text-main); font-size: 15px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'XÁC THỰC 2 YẾU TỐ (2FA)' : '2-FACTOR AUTHENTICATION (2FA)') ?></div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px; line-height: 1.4;">
                    <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mở ứng dụng Google Authenticator hoặc Authy để lấy mã OTP 6 chữ số.' : 'Open Google Authenticator or Authy to get a 6-digit OTP.') ?>
                </div>
            </div>

            <label for="twoFactorCode" style="font-weight:bold; font-size:13px; color: var(--text-main); display:block; margin-bottom: 6px; text-align: center;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập mã 6 chữ số OTP:' : 'Enter 6-digit OTP code:') ?></label>
            <input type="text" id="twoFactorCode" name="two_factor_code" class="win-input" required autofocus maxlength="6" autocomplete="off" placeholder="000000" style="text-align: center; font-size: 24px; letter-spacing: 8px; font-weight: bold; height: 50px; margin-bottom: 20px;">
            
            <button type="submit" class="win-btn" style="width: 100%; height: 45px; font-size: 16px; background: #10b981; color: white; border: none; font-weight: bold;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xác thực & Đăng nhập' : 'Verify & Login') ?></button>
            
            <div style="margin-top: 15px; text-align: center;">
                <a href="login.php" style="color: var(--text-muted); font-size: 13px; text-decoration: none;"><i class="fas fa-arrow-left"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quay lại đăng nhập' : 'Back to Login') ?></a>
            </div>
        </form>
        <?php else: ?>
        <!-- FORM ĐĂNG NHẬP THƯỜNG STEP 1 -->
        <form method="POST" style="text-align:left;">
            <input type="hidden" name="next" value="<?= htmlspecialchars($next_url) ?>">
            <label for="usernameInput" style="font-weight:bold; font-size:13px; color: var(--text-main);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tài khoản' : 'Username') ?>:</label>
            <input type="text" id="usernameInput" name="username" class="win-input" required autofocus value="<?= htmlspecialchars($username??'') ?>">
            
            <label for="loginPassword" style="font-weight:bold; font-size:13px; margin-top:15px; display:block; color: var(--text-main);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mật khẩu' : 'Password') ?>:</label>
            <div style="position: relative; margin-bottom: 15px;">
                <input type="password" name="password" id="loginPassword" class="win-input" required style="margin-bottom: 0; padding-right: 40px;">
                <span id="togglePasswordBtn" role="button" tabindex="0" aria-label="Toggle Password Visibility" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-muted); font-size: 15px; padding: 5px;">
                    <i class="fas fa-eye" id="eyeIcon" aria-hidden="true"></i>
                </span>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <label class="custom-checkbox-wrapper" style="margin-bottom: 0;">
                    <input type="checkbox" name="remember_me" id="remember_me" checked>
                    <span class="custom-checkmark"></span>
                    <span><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Duy trì đăng nhập' : 'Remember Login') ?></span> 
                </label>
                <a href="#" onclick="openForgotPasswordModal(event)" style="color: var(--primary-color); font-size: 13px; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-key"></i> Quên mật khẩu?
                </a>
            </div>
            
            <button type="submit" class="win-btn" style="width: 100%; height: 45px; font-size: 16px; margin-top: 5px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng nhập' : 'Login') ?></button>
        </form>
        <?php endif; ?>

        <div style="margin-top:20px; font-size:15px; text-align: center;">
            <a href="#" onclick="openGuideModal(event)" style="color:var(--primary-color); font-weight: bold; text-decoration:none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-book" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Hướng dẫn đăng nhập' : 'Login Guide') ?>
            </a>
            <div style="margin-top:8px; font-size:13px; color: var(--text-muted);">
                <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mật khẩu mặc định' : 'Default password') ?>: <b style="color: var(--text-main);">123456</b>
            </div>
        </div>

        <?php if (!empty($system_notice)): ?>
        <div class="system-notice-box" style="margin-top: 20px; padding: 12px 15px; background-color: rgba(59, 130, 246, 0.1); border-left: 4px solid var(--primary-color); border-radius: 6px; font-size: 13px; color: var(--text-main); line-height: 1.5; text-align: left;">
            <?= $system_notice ?>
        </div>
        <?php endif; ?>

        <?php if($error): ?>
            <p style="color:var(--danger-color); margin-top:15px; font-size:14px; font-weight: bold;"><?= $error ?></p>
        <?php endif; ?>

        <div style="margin-top: 20px; font-size: 13px; color: #dc3545; font-weight: bold; border-top: 1px solid var(--border-color); padding-top: 15px;">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cần đổi mật khẩu sau khi truy cập' : 'Change your password after logging in') ?>
        </div>
    </div>


    <div id="lg3GuardModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); z-index: 999999; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
        <div style="background: var(--bg-card); max-width: 600px; width: 100%; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.5); animation: modalFadeIn 0.3s ease; display: flex; flex-direction: column; max-height: 90vh;">
            
            <div style="background: var(--danger-color); color: #fff; padding: 15px; text-align: center; font-weight: 900; font-size: 18px; text-transform: uppercase; flex-shrink: 0;">
                <i class="fas fa-shield-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LG3 GUARD - HỆ THỐNG CẢNH BÁO' : 'LG3 GUARD - WARNING SYSTEM') ?>
            </div>
            
            <div style="padding: 20px; color: var(--text-main); font-size: 14px; line-height: 1.5; overflow-y: auto;">
                <p style="text-align: center; color: var(--danger-color); font-weight: 800; text-transform: uppercase; margin-top: 0; margin-bottom: 15px;">
                    <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Phát hiện nhập sai cú pháp tên đăng nhập!' : 'Incorrect username format detected!') ?>
                </p>
                <p style="text-align: center; font-weight: bold; margin-bottom: 15px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng kiểm tra lại tài khoản theo 1 trong 2 trường hợp sau:' : 'Please verify your account based on one of the following two cases:') ?></p>
                
                <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--primary-color);">
                    <strong style="color: var(--primary-color); font-size: 15px;"><i class="fas fa-id-card" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TRƯỜNG HỢP 1: CÓ THẺ HỌC SINH' : 'CASE 1: HAS STUDENT ID CARD') ?></strong>
                    <ul style="margin-top: 8px; padding-left: 20px; margin-bottom: 10px;">
                        <li><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhìn dòng chữ phía trên mã QR. <br>Ví dụ:' : 'Look at the text above the QR code. <br>Example:') ?> <i><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '16 Truong Thanh Hieu_<span style="background: #fde047; color: #000; padding: 0 4px; font-weight: bold;">K48A1016</span>' : '16 Truong Thanh Hieu_<span style="background: #fde047; color: #000; padding: 0 4px; font-weight: bold;">K48A1016</span>') ?></i></li>
                        <li><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tên đăng nhập là dãy số bắt đầu bằng chữ <b>K</b> được bôi vàng.' : 'The username is the alphanumeric string starting with <b>K</b> highlighted in yellow.') ?></li>
                        <li><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '<b>Tuyệt đối không</b> nhập dấu cách hay họ tên.' : '<b>Absolutely do not</b> enter spaces or full names.') ?></li>
                    </ul>
                    <div style="text-align: center;">
                        <img src="/static/guide/ma_qr_mau.png" alt="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mẫu mã QR' : 'QR code sample') ?>" class="qr-image">
                    </div>
                </div>

                <div style="background: rgba(16, 185, 129, 0.05); padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">
                    <strong style="color: #10b981; font-size: 15px;"><i class="fas fa-brain" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TRƯỜNG HỢP 2: KHÔNG CÓ THẺ (Tự ghép mã)' : 'CASE 2: NO CARD (Manually build ID)') ?></strong>
                    <p style="margin: 10px 0; font-size: 13.5px; line-height: 1.6;">
                        <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mã học sinh có dạng là <b>K<span style="color:#d97706;">xx</span>A<span style="color:#2563eb;">y</span><span style="color:#db2777;">zzz</span></b> (đối với lớp A1 → A9) hoặc <b>K<span style="color:#d97706;">xx</span>A<span style="color:#2563eb;">yy</span><span style="color:#db2777;">zzz</span></b> (đối với lớp A10 trở đi).' : 'Student ID format is <b>K<span style="color:#d97706;">xx</span>A<span style="color:#2563eb;">y</span><span style="color:#db2777;">zzz</span></b> (for classes A1 → A9) or <b>K<span style="color:#d97706;">xx</span>A<span style="color:#2563eb;">yy</span><span style="color:#db2777;">zzz</span></b> (for class A10 and above).') ?><br>
                        <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trong đó: <i style="color:#d97706; font-weight:bold;">xx là khóa</i>, <i style="color:#2563eb; font-weight:bold;">y/yy là lớp</i>, <i style="color:#db2777; font-weight:bold;">zzz là STT</i>.' : 'Where: <i style="color:#d97706; font-weight:bold;">xx is cohort</i>, <i style="color:#2563eb; font-weight:bold;">y/yy is class</i>, <i style="color:#db2777; font-weight:bold;">zzz is student number</i>.') ?>
                    </p>
                    <div style="background: var(--bg-card); padding: 12px; border-radius: 6px; font-size: 13px; border: 1px solid var(--border-color); font-family: monospace; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="margin-bottom: 8px; font-family: sans-serif;"><i class="fas fa-angle-right" style="color: var(--text-muted);" aria-hidden="true"></i> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ví dụ' : 'Example') ?> 1:</b> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trương Thanh Hiếu, lớp 10A<b style="color:#2563eb;">1</b>, STT <b style="color:#db2777;">16</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">48</span>A<span style="color:#2563eb;">1</span><span style="color:#db2777;">016</span></span>' : 'Truong Thanh Hieu, class 10A<b style="color:#2563eb;">1</b>, No. <b style="color:#db2777;">16</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">48</span>A<span style="color:#2563eb;">1</span><span style="color:#db2777;">016</span></span>') ?></div>
                        <div style="margin-bottom: 8px; font-family: sans-serif;"><i class="fas fa-angle-right" style="color: var(--text-muted);" aria-hidden="true"></i> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ví dụ' : 'Example') ?> 2:</b> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh Y, lớp 10A<b style="color:#2563eb;">11</b>, STT <b style="color:#db2777;">yy</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">48</span>A<span style="color:#2563eb;">11</span><span style="color:#db2777;">0yy</span></span>' : 'Student Y, class 10A<b style="color:#2563eb;">11</b>, No. <b style="color:#db2777;">yy</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">48</span>A<span style="color:#2563eb;">11</span><span style="color:#db2777;">0yy</span></span>') ?></div>
                        <div style="font-family: sans-serif;"><i class="fas fa-angle-right" style="color: var(--text-muted);" aria-hidden="true"></i> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ví dụ' : 'Example') ?> 3:</b> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh Z, lớp 11A<b style="color:#2563eb;">1</b>, STT <b style="color:#db2777;">zz</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">47</span>A<span style="color:#2563eb;">1</span><span style="color:#db2777;">0zz</span></span>' : 'Student Z, class 11A<b style="color:#2563eb;">1</b>, No. <b style="color:#db2777;">zz</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">47</span>A<span style="color:#2563eb;">1</span><span style="color:#db2777;">0zz</span></span>') ?></div>
                    </div>
                </div>
            </div> 

            <div style="padding: 15px; border-top: 1px solid var(--border-color); text-align: center; background: var(--bg-card); flex-shrink: 0;">
                <button type="button" onclick="closeGuardModal()" style="width: 100%; background: #2563eb !important; color: #ffffff !important; border: none; padding: 12px; border-radius: 8px; font-weight: 800; font-size: 14px; cursor: pointer; text-transform: uppercase; transition: transform 0.1s;" onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'">
                    <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TÔI ĐÃ HIỂU, ĐỂ TÔI NHẬP LẠI!' : 'I UNDERSTAND, LET ME TRY AGAIN!') ?>
                </button>
            </div>
        </div>
    </div>

    <div id="guideModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); z-index: 999999; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
        <div style="background: var(--bg-card); max-width: 600px; width: 100%; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.5); animation: modalFadeIn 0.3s ease; display: flex; flex-direction: column; max-height: 90vh;">
            
            <div style="background: #005fba; color: #ffffff !important; padding: 15px; text-align: center; font-weight: 900; font-size: 18px; text-transform: uppercase; flex-shrink: 0;">
                <i class="fas fa-book" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'HƯỚNG DẪN ĐĂNG NHẬP' : 'LOGIN GUIDE') ?>
            </div>
            
            <div style="padding: 20px; color: var(--text-main); font-size: 14px; line-height: 1.5; overflow-y: auto;">
                
                <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--primary-color);">
                    <strong style="color: var(--primary-color); font-size: 15px;"><i class="fas fa-id-card" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TRƯỜNG HỢP 1: CÓ THẺ HỌC SINH' : 'CASE 1: HAS STUDENT ID CARD') ?></strong>
                    <ul style="margin-top: 8px; padding-left: 20px; margin-bottom: 10px;">
                        <li><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhìn dòng chữ phía trên mã QR. <br>Ví dụ:' : 'Look at the text above the QR code. <br>Example:') ?> <i><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '16 Truong Thanh Hieu_<span style="background: #fde047; color: #000; padding: 0 4px; font-weight: bold;">K48A1016</span>' : '16 Truong Thanh Hieu_<span style="background: #fde047; color: #000; padding: 0 4px; font-weight: bold;">K48A1016</span>') ?></i></li>
                        <li><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tên đăng nhập là dãy số bắt đầu bằng chữ <b>K</b> được bôi vàng.' : 'The username is the alphanumeric string starting with <b>K</b> highlighted in yellow.') ?></li>
                        <li><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '<b>Tuyệt đối không</b> nhập dấu cách hay họ tên.' : '<b>Absolutely do not</b> enter spaces or full names.') ?></li>
                    </ul>
                    <div style="text-align: center;">
                        <img src="/static/guide/ma_qr_mau.png" alt="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mẫu mã QR' : 'QR code sample') ?>" class="qr-image">
                    </div>
                </div>

                <div style="background: rgba(16, 185, 129, 0.05); padding: 15px; border-radius: 8px; border-left: 4px solid #10b981;">
                    <strong style="color: #10b981; font-size: 15px;"><i class="fas fa-brain" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'TRƯỜNG HỢP 2: KHÔNG CÓ THẺ (Tự ghép mã)' : 'CASE 2: NO CARD (Manually build ID)') ?></strong>
                    <p style="margin: 10px 0; font-size: 13.5px; line-height: 1.6;">
                        <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mã học sinh có dạng là <b>K<span style="color:#d97706;">xx</span>A<span style="color:#2563eb;">y</span><span style="color:#db2777;">zzz</span></b> (đối với lớp A1 → A9) hoặc <b>K<span style="color:#d97706;">xx</span>A<span style="color:#2563eb;">yy</span><span style="color:#db2777;">zzz</span></b> (đối với lớp A10 trở đi).' : 'Student ID format is <b>K<span style="color:#d97706;">xx</span>A<span style="color:#2563eb;">y</span><span style="color:#db2777;">zzz</span></b> (for classes A1 → A9) or <b>K<span style="color:#d97706;">xx</span>A<span style="color:#2563eb;">yy</span><span style="color:#db2777;">zzz</span></b> (for class A10 and above).') ?><br>
                        <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trong đó: <i style="color:#d97706; font-weight:bold;">xx là khóa</i>, <i style="color:#2563eb; font-weight:bold;">y/yy là lớp</i>, <i style="color:#db2777; font-weight:bold;">zzz là STT</i>.' : 'Where: <i style="color:#d97706; font-weight:bold;">xx is cohort</i>, <i style="color:#2563eb; font-weight:bold;">y/yy is class</i>, <i style="color:#db2777; font-weight:bold;">zzz is student number</i>.') ?>
                    </p>
                    <div style="background: var(--bg-card); padding: 12px; border-radius: 6px; font-size: 13px; border: 1px solid var(--border-color); font-family: monospace; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="margin-bottom: 8px; font-family: sans-serif;"><i class="fas fa-angle-right" style="color: var(--text-muted);" aria-hidden="true"></i> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ví dụ' : 'Example') ?> 1:</b> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trương Thanh Hiếu, lớp 10A<b style="color:#2563eb;">1</b>, STT <b style="color:#db2777;">16</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">48</span>A<span style="color:#2563eb;">1</span><span style="color:#db2777;">016</span></span>' : 'Truong Thanh Hieu, class 10A<b style="color:#2563eb;">1</b>, No. <b style="color:#db2777;">16</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">48</span>A<span style="color:#2563eb;">1</span><span style="color:#db2777;">016</span></span>') ?></div>
                        <div style="margin-bottom: 8px; font-family: sans-serif;"><i class="fas fa-angle-right" style="color: var(--text-muted);" aria-hidden="true"></i> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ví dụ' : 'Example') ?> 2:</b> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh Y, lớp 10A<b style="color:#2563eb;">11</b>, STT <b style="color:#db2777;">yy</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">48</span>A<span style="color:#2563eb;">11</span><span style="color:#db2777;">0yy</span></span>' : 'Student Y, class 10A<b style="color:#2563eb;">11</b>, No. <b style="color:#db2777;">yy</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">48</span>A<span style="color:#2563eb;">11</span><span style="color:#db2777;">0yy</span></span>') ?></div>
                        <div style="font-family: sans-serif;"><i class="fas fa-angle-right" style="color: var(--text-muted);" aria-hidden="true"></i> <b><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ví dụ' : 'Example') ?> 3:</b> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh Z, lớp 11A<b style="color:#2563eb;">1</b>, STT <b style="color:#db2777;">zz</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">47</span>A<span style="color:#2563eb;">1</span><span style="color:#db2777;">0zz</span></span>' : 'Student Z, class 11A<b style="color:#2563eb;">1</b>, No. <b style="color:#db2777;">zz</b> ➔ <span style="font-size:14px; font-weight:900;">K<span style="color:#d97706;">47</span>A<span style="color:#2563eb;">1</span><span style="color:#db2777;">0zz</span></span>') ?></div>
                    </div>
                </div>
            </div> 

            <div style="padding: 15px; border-top: 1px solid var(--border-color); text-align: center; background: var(--bg-card); flex-shrink: 0;">
                <button type="button" onclick="closeGuideModal()" style="width: 100%; background: var(--bg-hover); color: var(--text-main); border: 1px solid var(--border-color); padding: 12px; border-radius: 8px; font-weight: 800; font-size: 14px; cursor: pointer; text-transform: uppercase; transition: 0.2s;" onmouseover="this.style.background='var(--border-color)';" onmouseout="this.style.background='var(--bg-hover)';">
                    <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'ĐÓNG HƯỚNG DẪN' : 'CLOSE GUIDE') ?>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL KHÔI PHỤC MẬT KHẨU -->
    <div id="forgotPasswordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999999; justify-content: center; align-items: center; padding: 15px; box-sizing: border-box; backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
        <div style="background: var(--bg-card); max-width: 440px; width: 100%; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.5); animation: modalFadeIn 0.3s ease;">
            
            <div style="background: #005fba; color: #ffffff !important; padding: 15px; text-align: center; font-weight: 900; font-size: 17px; text-transform: uppercase;">
                <i class="fas fa-key" aria-hidden="true"></i> KHÔI PHỤC MẬT KHẨU
            </div>
            
            <div style="padding: 22px; color: var(--text-main); font-size: 14px; text-align: left;">
                
                <!-- BƯỚC 1: NHẬP TÊN ĐĂNG NHẬP -->
                <div id="forgotStep1">
                    <p style="margin-top:0; color: var(--text-muted); font-size: 13px; line-height: 1.5;">
                        Nhập <strong>Tên đăng nhập / Mã học sinh</strong> đã liên kết email để nhận mã OTP khôi phục:
                    </p>
                    <div style="margin-bottom: 18px;">
                        <label style="font-weight: bold; font-size: 13px; color: var(--text-main); display: block; margin-bottom: 6px;">
                            Tên đăng nhập / Mã học sinh:
                        </label>
                        <input type="text" id="forgotUsernameInput" class="win-input" placeholder="Ví dụ: K48A1016" style="height: 42px; text-transform: uppercase;">
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="closeForgotPasswordModal()" class="win-btn" style="padding: 8px 16px;">Hủy</button>
                        <button type="button" id="btnSendResetOTP" onclick="sendResetOTP()" class="win-btn" style="background: #005fba; color: #ffffff !important; padding: 8px 20px; font-weight: 700;">Gửi mã OTP qua Mail</button>
                    </div>
                </div>

                <!-- BƯỚC 2: NHẬP MÃ OTP VÀ MẬT KHẨU MỚI -->
                <div id="forgotStep2" style="display: none;">
                    <div style="background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 12.5px; color: var(--text-main);" id="forgotNoticeMsg">
                        ✅ Đã gửi mã xác nhận OTP đến email!
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="font-weight: bold; font-size: 12.5px; color: var(--text-main); display: block; margin-bottom: 5px;">Mã OTP 6 chữ số:</label>
                        <input type="text" id="forgotOTPInput" class="win-input" placeholder="000000" maxlength="6" style="text-align: center; font-size: 22px; letter-spacing: 6px; font-weight: bold; height: 45px;">
                    </div>

                    <div style="margin-bottom: 18px;">
                        <label style="font-weight: bold; font-size: 12.5px; color: var(--text-main); display: block; margin-bottom: 5px;">Mật khẩu mới (Tối thiểu 6 ký tự):</label>
                        <input type="password" id="forgotNewPasswordInput" class="win-input" placeholder="Nhập mật khẩu mới..." style="height: 42px;">
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
                        <button type="button" onclick="sendResetOTP()" class="win-btn" style="padding: 8px 12px; font-size: 12px; background: var(--bg-hover);">Gửi lại OTP</button>
                        <button type="button" id="btnSubmitResetPassword" onclick="submitResetPassword()" class="win-btn" style="background: #10b981; color: white; padding: 8px 20px; font-weight: 700;">Đổi mật khẩu mới</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const lg3GuardModal = document.getElementById('lg3GuardModal');
        const guideModal = document.getElementById('guideModal');
        const usernameInput = document.querySelector('input[name="username"]');

        function closeGuardModal() { 
            lg3GuardModal.style.display = 'none'; 
            usernameInput.value = ''; 
            usernameInput.focus(); 
        }

        function openGuideModal(e) {
            e.preventDefault();
            guideModal.style.display = 'flex';
        }

        function closeGuideModal() {
            guideModal.style.display = 'none';
        }

        function openForgotPasswordModal(e) {
            if (e) e.preventDefault();
            document.getElementById('forgotPasswordModal').style.display = 'flex';
            document.getElementById('forgotStep1').style.display = 'block';
            document.getElementById('forgotStep2').style.display = 'none';
            if (usernameInput && usernameInput.value) {
                document.getElementById('forgotUsernameInput').value = usernameInput.value.trim();
            }
        }

        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').style.display = 'none';
        }

        async function sendResetOTP() {
            var username = document.getElementById('forgotUsernameInput').value.trim();
            var btn = document.getElementById('btnSendResetOTP');
            if (!username) { alert('Vui lòng nhập Tên đăng nhập / Mã học sinh!'); return; }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';

            try {
                var res = await fetch('api/forgot_password_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'send_reset_otp', username: username })
                });
                var data = await res.json();

                if (data.status === 'success') {
                    document.getElementById('forgotStep1').style.display = 'none';
                    document.getElementById('forgotStep2').style.display = 'block';
                    document.getElementById('forgotNoticeMsg').innerText = "✅ " + data.msg;
                } else {
                    alert(data.msg);
                }
            } catch(e) {
                alert('Lỗi kết nối máy chủ!');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Gửi mã OTP qua Mail';
            }
        }

        async function submitResetPassword() {
            var username = document.getElementById('forgotUsernameInput').value.trim();
            var otp = document.getElementById('forgotOTPInput').value.trim();
            var newPassword = document.getElementById('forgotNewPasswordInput').value;
            var btn = document.getElementById('btnSubmitResetPassword');

            if (!otp || otp.length !== 6) { alert('Vui lòng nhập đủ 6 số OTP!'); return; }
            if (!newPassword || newPassword.length < 6) { alert('Mật khẩu mới phải từ 6 ký tự trở lên!'); return; }

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang cập nhật...';

            try {
                var res = await fetch('api/forgot_password_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'reset_password_with_otp',
                        username: username,
                        otp: otp,
                        new_password: newPassword
                    })
                });
                var data = await res.json();

                if (data.status === 'success') {
                    alert("🎉 " + data.msg);
                    closeForgotPasswordModal();
                    if (usernameInput) usernameInput.value = username;
                } else {
                    alert(data.msg);
                }
            } catch(e) {
                alert('Lỗi kết nối máy chủ!');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Đổi mật khẩu mới';
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const val = usernameInput.value;
            if (val.includes(' ')) {
                e.preventDefault();
                lg3GuardModal.style.display = 'flex';
                usernameInput.style.border = '2px solid var(--danger-color)';
            }
        });

        document.getElementById('togglePasswordBtn').addEventListener('click', function () {
            const pwd = document.getElementById('loginPassword');
            const eye = document.getElementById('eyeIcon');
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
            eye.className = pwd.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });

        setInterval(() => {
            const now = new Date();
            const el = document.getElementById('etClock');
            if(el) el.innerText = now.toLocaleString('en-GB');
        }, 1000);

        document.addEventListener("DOMContentLoaded", function() {
            if (navigator.userAgent.toLowerCase().indexOf(' electron/') > -1) document.body.classList.add('is-electron');
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
</body>
</html>