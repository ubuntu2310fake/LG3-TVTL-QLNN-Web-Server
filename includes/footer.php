</div> <div id="sidebarHoverCard" class="sidebar-hover-card">
        <div class="shc-header">
            <div class="shc-icon"><i class="fas fa-info"></i></div>
            <div class="shc-title">Tiêu đề</div>
        </div>
        <div class="shc-desc">Mô tả tính năng chi tiết sẽ hiện ở đây.</div>
    </div>

    <?php if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['STUDENT', 'RED_FLAG'])): 
        // 1. CẤU HÌNH KEY (Phải giống hệt bên app.py Python)
        $secret_key = "khoa_bi_mat_ket_noi_hai_app_123456_secure"; 
        
        // 2. TẠO TOKEN
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        // Xử lý avatar: Chuyển sang tuyệt đối để App Python đọc được
        $raw_avatar = $_SESSION['user']['avatar'] ?? 'static/default.png';
        if ($raw_avatar && strpos($raw_avatar, 'http') === false) {
            $avatar_full = "https://qlnn.testifiyonline.xyz/" . ltrim($raw_avatar, '/');
        } else {
            $avatar_full = $raw_avatar;
        }

        $payload = json_encode([
            'sbd' => $_SESSION['user']['username'],
            'name' => $_SESSION['user']['full_name'],
            'role' => $_SESSION['user']['role'],
            'avatar' => $avatar_full, // Sử dụng biến đã xử lý
            'exp' => time() + 300 
        ]);

        // Mã hóa JWT chuẩn (HS256)
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt_token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        // 3. TẠO URL IFRAME
        $chat_url = "https://tvtl.testifiyonline.xyz/sso_login?token=" . $jwt_token . "&view=chat";
    ?>
    
    <style>
        .chat-bubble { position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; background: #005fba; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; cursor: pointer; z-index: 9999; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .chat-iframe-container { display: none; position: fixed; bottom: 90px; right: 20px; width: 360px; height: 550px; background: #fff; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.2); z-index: 10000; overflow: hidden; border: 1px solid #ddd; flex-direction: column; }
        .chat-header-bar { background: #005fba; color: white; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; font-weight: bold; }
    </style>

    <div class="chat-bubble" onclick="toggleChatPopup()"><i class="fas fa-comments"></i></div>

    <div class="chat-iframe-container" id="chatPopup">
        <div class="chat-header-bar">
            <span><i class="fas fa-user-md"></i> Chat</span>
            <div style="cursor:pointer" onclick="toggleChatPopup()"><i class="fas fa-times"></i></div>
        </div>
        <iframe src="<?= $chat_url ?>" style="width:100%; height:100%; border:none; background:#fff;" allow="camera; microphone"></iframe>
    </div>

    <script>
        function toggleChatPopup() {
            const popup = document.getElementById('chatPopup');
            popup.style.display = (popup.style.display === 'none' || !popup.style.display) ? 'flex' : 'none';
        }
    </script>
    <?php endif; ?>

    <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

    <div class="pwa-promo-overlay" id="pwaPromoModal">
        <div class="pwa-promo-box">
            <img src="https://qlnn.testifiyonline.xyz/lg3512512.png" alt="Logo" class="pwa-logo">
            <h2 class="pwa-title">Trải nghiệm tốt hơn với App "Nền Nếp"</h2>
            <p class="pwa-desc">Cài đặt ứng dụng để nhận thông báo tức thời và truy cập mượt mà hơn.</p>
            <a href="/" class="pwa-btn-switch" onclick="closePwaPromo()">Chuyển sang App</a>
            <button class="pwa-btn-close" onclick="closePwaPromo(true)">Quay lại sử dụng web</button>
        </div>
    </div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script>
    // --- JS XỬ LÝ HOVER SIDEBAR ---
    document.addEventListener("DOMContentLoaded", () => {
        // [ĐÃ FIX] Chọn thêm cả các link trong footer (.sidebar-footer)
        const links = document.querySelectorAll('.sidebar-menu-scroll .nav-link, .sidebar-footer .nav-link');
        const card = document.getElementById('sidebarHoverCard');
        const cIcon = card.querySelector('.shc-icon i');
        const cTitle = card.querySelector('.shc-title');
        const cDesc = card.querySelector('.shc-desc');
        let hoverTimer;

        links.forEach(link => {
            link.addEventListener('mouseenter', () => {
                // Chỉ hoạt động trên PC (màn hình lớn)
                if(window.innerWidth < 992) return;

                const title = link.getAttribute('data-title');
                const desc = link.getAttribute('data-desc');
                const iconClass = link.querySelector('i').className;

                if(!title) return; // Nếu không có title thì không hiện

                // Đặt thời gian trễ 0.5s (500ms)
                hoverTimer = setTimeout(() => {
                    // 1. Cập nhật nội dung
                    cTitle.innerText = title;
                    cDesc.innerText = desc;
                    cIcon.className = iconClass;

                    // 2. Tính toán vị trí (Căn giữa theo chiều dọc của nút)
                    const rect = link.getBoundingClientRect();
                    // Lấy vị trí top của link + nửa chiều cao link - nửa chiều cao card (ước lượng)
                    // Hoặc đơn giản: Top của link
                    const topPos = rect.top + (rect.height / 2) - 60; // Trừ 60 để căn giữa icon
                    
                    card.style.top = `${Math.max(10, topPos)}px`; // Đảm bảo không bị che trên cùng
                    
                    // 3. Hiện card
                    card.classList.add('active');
                }, 500);
            });

            link.addEventListener('mouseleave', () => {
                // Hủy timer nếu chưa đủ 0.5s
                clearTimeout(hoverTimer);
                // Ẩn card
                card.classList.remove('active');
            });
        });
    });
</script>
<script>
    const THEME_MODES = ['light', 'dark', 'system'];
    function getSavedMode() { return localStorage.getItem('theme_mode') || 'system'; }
    function getEffectiveTheme(mode) {
        if (mode === 'system') return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        return mode;
    }
    function applyTheme(mode) {
        localStorage.setItem('theme_mode', mode);
        const effectiveTheme = getEffectiveTheme(mode);
        if (effectiveTheme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        else document.documentElement.removeAttribute('data-theme');
        updateThemeIcons(mode);
        sendThemeToIframes(effectiveTheme);
    }
    function cycleThemeMode() {
        const current = getSavedMode();
        let nextIndex = THEME_MODES.indexOf(current) + 1;
        if (nextIndex >= THEME_MODES.length) nextIndex = 0;
        const nextMode = THEME_MODES[nextIndex];
        applyTheme(nextMode);
        let msg = nextMode === 'light' ? "☀️ Chế độ Sáng" : (nextMode === 'dark' ? "🌙 Chế độ Tối" : "🖥️ Chế độ Hệ thống");
        
        Toastify({ text: msg, duration: 2000, gravity: "bottom", position: "center", style: { background: "var(--accent-color)", borderRadius: "8px", marginBottom: "20px", boxShadow: "0 4px 12px rgba(0,0,0,0.15)" } }).showToast();
    }
    function updateThemeIcons(mode) {
        const iconClass = { 'light': 'fa-sun', 'dark': 'fa-moon', 'system': 'fa-desktop' };
        const cls = iconClass[mode] || 'fa-desktop';
        const iconMobile = document.getElementById('themeIconMobile');
        const iconPC = document.getElementById('themeIconPC');
        const iconTitlebar = document.getElementById('etThemeIcon');
        if(iconMobile) iconMobile.className = `fas ${cls}`;
        if(iconPC) iconPC.className = `fas ${cls}`;
        if(iconTitlebar) iconTitlebar.className = `fas ${cls}`;
    }
    function sendThemeToIframes(effectiveTheme) {
        const frames = document.getElementsByTagName('iframe');
        for (let i = 0; i < frames.length; i++) {
            try { frames[i].contentWindow.postMessage({ type: 'theme_change', theme: effectiveTheme }, '*'); } catch (e) {}
        }
    }
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
        if (getSavedMode() === 'system') applyTheme('system');
    });

    document.addEventListener("DOMContentLoaded", function() {
        const savedMode = getSavedMode();
        applyTheme(savedMode);
        
        if (navigator.userAgent.toLowerCase().indexOf(' electron/') > -1) {
             document.body.classList.add('is-electron');
        }

        function updateEtClock() {
            const now = new Date();
            const d = String(now.getDate()).padStart(2, '0');
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const y = now.getFullYear();
            const h = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            
            const el = document.getElementById('etClock');
            if(el) el.innerText = `${d}/${m}/${y} ${h}:${min}:${s}`;
        }
        setInterval(updateEtClock, 1000); updateEtClock();

        if (window.electronAPI && window.electronAPI.onWindowStateChange) {
            window.electronAPI.onWindowStateChange((state) => {
                const iconMax = document.querySelector('.icon-maximize');
                const iconRes = document.querySelector('.icon-restore');
                if (state === 'maximized') {
                    if(iconMax) iconMax.style.display = 'none';
                    if(iconRes) iconRes.style.display = 'block';
                } else {
                    if(iconMax) iconMax.style.display = 'block';
                    if(iconRes) iconRes.style.display = 'none';
                }
            });
        }
        
        const modal = document.getElementById('pwaPromoModal');
        const isApp = (window.matchMedia('(display-mode: standalone)').matches) || (window.navigator.standalone === true);
        const isDismissed = sessionStorage.getItem('pwa_promo_dismissed');
        const isRoot = window.location.pathname === '/' || window.location.pathname === '';
        const isSmallScreen = window.innerWidth < 992;
        const platform = navigator.platform || '';
        const isDevEnvironment = platform.indexOf('Win') !== -1 || (platform.indexOf('Mac') !== -1 && navigator.maxTouchPoints === 0);
        if (!isApp && !isDismissed && !isRoot && isSmallScreen && !isDevEnvironment) {
            setTimeout(() => { if(modal) modal.style.display = 'flex'; }, 1500); 
        }
        const effectiveTheme = getEffectiveTheme(savedMode);
        const frames = document.getElementsByTagName('iframe');
        for (let i = 0; i < frames.length; i++) {
            frames[i].addEventListener('load', function() {
                this.contentWindow.postMessage({ type: 'theme_change', theme: effectiveTheme }, '*');
            });
            setTimeout(() => { try { frames[i].contentWindow.postMessage({ type: 'theme_change', theme: effectiveTheme }, '*'); } catch(e){} }, 500);
        }
        const activeLink = document.querySelector('.sidebar-menu-scroll .nav-link.active');
        if (activeLink) {
            setTimeout(() => { activeLink.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' }); }, 300);
        }
    });

    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active');
        document.getElementById('overlay').classList.toggle('active');
        if (sidebar.classList.contains('active')) {
            setTimeout(() => {
                const activeLink = document.querySelector('.sidebar-menu-scroll .nav-link.active');
                if (activeLink) activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        }
    }
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(registration) { console.log('SW Registered'); }, function(err) { console.log('SW Failed: ', err); });
        });
    }
    function handleChatResize() {
        const popup = document.getElementById('chatPopup');
        if (!popup || popup.style.display === 'none') return;
        const isMobile = window.innerWidth < 992;
        if (isMobile) {
            popup.style.width = '100%'; popup.style.height = '100%';
            if (CSS.supports('height: 100dvh')) popup.style.height = '100dvh';
            popup.style.top = '0'; popup.style.left = '0'; popup.style.right = '0'; popup.style.bottom = 'auto'; popup.style.borderRadius = '0';
        } else {
            popup.style.width = '400px'; popup.style.height = '600px'; 
            popup.style.top = 'auto'; popup.style.left = 'auto'; popup.style.right = '20px'; popup.style.bottom = '100px'; popup.style.borderRadius = '16px';
        }
    }
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', handleChatResize);
        window.visualViewport.addEventListener('scroll', handleChatResize);
    }
    window.addEventListener('resize', handleChatResize);
    function toggleChatPopup() {
        const popup = document.getElementById('chatPopup');
        const iframe = document.getElementById('chatFrame');
        const body = document.body;
        const isHidden = (popup.style.display === 'none' || popup.style.display === '');
        if (isHidden) {
            popup.style.display = 'flex'; popup.classList.add('show-animation'); body.classList.add('chat-open'); 
            handleChatResize();
            if (!iframe.src || iframe.src === window.location.href) {
                fetch('/get_sso_chat_url').then(r => r.json()).then(d => { 
                    if(d.url) {
                        const currentMode = localStorage.getItem('theme_mode') || 'system';
                        const effectiveTheme = getEffectiveTheme(currentMode);
                        const separator = d.url.includes('?') ? '&' : '?';
                        iframe.src = d.url + separator + 'theme=' + effectiveTheme;
                    } 
                }).catch(e => console.error(e));
            }
        } else {
            popup.style.display = 'none'; popup.classList.remove('show-animation'); body.classList.remove('chat-open'); 
        }
    }
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('open_chat')) setTimeout(toggleChatPopup, 500); 
    function checkPwaStatus() { }
    function closePwaPromo(savePreference = false) {
        const modal = document.getElementById('pwaPromoModal');
        if(modal) modal.style.display = 'none';
        if (savePreference) sessionStorage.setItem('pwa_promo_dismissed', 'true');
    }
</script>
<script>
    function startLiveClock() {
        function update() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            const time = now.toLocaleTimeString('vi-VN', { hour12: false });
            const fullDateTime = `${day}/${month}/${year} ${time}`;
            document.querySelectorAll('.live-clock').forEach(el => {
                el.innerText = fullDateTime;
                el.style.display = 'block';
            });
        }
        update();
        setInterval(update, 1000);
    }
    document.addEventListener("DOMContentLoaded", function() {
        startLiveClock();
    });

    function checkPwaStatus() { /* (Đã gộp vào init) */ }
    function closePwaPromo(savePreference = false) {
        const modal = document.getElementById('pwaPromoModal');
        if(modal) modal.style.display = 'none';
        if (savePreference) sessionStorage.setItem('pwa_promo_dismissed', 'true');
    }

    const modal = document.getElementById('pwaPromoModal');
        const isApp = (window.matchMedia('(display-mode: standalone)').matches) || (window.navigator.standalone === true);
        const isDismissed = sessionStorage.getItem('pwa_promo_dismissed');
        const isRoot = window.location.pathname === '/' || window.location.pathname === '';
        const isSmallScreen = window.innerWidth < 992;
        const platform = navigator.platform || '';
        const isDevEnvironment = platform.indexOf('Win') !== -1 || (platform.indexOf('Mac') !== -1 && navigator.maxTouchPoints === 0);

        if (!isApp && !isDismissed && !isRoot && isSmallScreen && !isDevEnvironment) {
            setTimeout(() => { if(modal) modal.style.display = 'flex'; }, 1500); 
        }
    // --- 2. JS HEARTBEAT & SESSION SYNC ---
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('sw.js'));
    }
    
    function sendHeartbeat() {
        fetch('api/session_heartbeat.php', { method: 'GET' }).catch(e => {});
    }
    sendHeartbeat();
    setInterval(sendHeartbeat, 5 * 60 * 1000);

    // --- 3. JS UTILS ---
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
        document.getElementById('overlay').classList.toggle('active');
        if (document.querySelector('.sidebar').classList.contains('active')) {
            setTimeout(() => {
                const activeLink = document.querySelector('.sidebar-menu-scroll .nav-link.active');
                if (activeLink) activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        }
    }
    function startLiveClock() {
        setInterval(() => {
            const now = new Date();
            const d = String(now.getDate()).padStart(2, '0');
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const y = now.getFullYear();
            const h = String(now.getHours()).padStart(2, '0');
            const min = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');
            const str = `${d}/${m}/${y} ${h}:${min}:${s}`;
            document.querySelectorAll('.live-clock').forEach(el => { el.innerText = str; el.style.display = 'block'; });
        }, 1000);
    }

    // --- 4. LOGIC PWA (ĐÃ FIX: KHÔNG HIỆN Ở TRANG CHỦ) ---
    function checkPwaStatus() {
        const modal = document.getElementById('pwaPromoModal');
        const isApp = window.matchMedia('(display-mode: standalone)').matches;
        const path = window.location.pathname;
        const isRoot = path === '/' || path === '' || path.endsWith('index.php');
        
        if (!isApp && !sessionStorage.getItem('pwa_promo_dismissed') && !isRoot && window.innerWidth < 992) {
            setTimeout(() => { if(modal) modal.style.display = 'flex'; }, 1500);
        }
    }
    function closePwaPromo(save = false) {
        document.getElementById('pwaPromoModal').style.display = 'none';
        if (save) sessionStorage.setItem('pwa_promo_dismissed', 'true');
    }
    function sendThemeToIframes(effectiveTheme) {
        const frames = document.getElementsByTagName('iframe');
        for (let i = 0; i < frames.length; i++) {
            try { frames[i].contentWindow.postMessage({ type: 'theme_change', theme: effectiveTheme }, '*'); } catch (e) {}
        }
    }
</script>
</body>
</html>