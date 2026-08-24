</div> </div> <div id="sidebarHoverCard" class="sidebar-hover-card">
        <div class="shc-header">
            <div class="shc-icon"><i class="fas fa-info" aria-hidden="true"></i></div>
            <div class="shc-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tiêu đề' : 'Title') ?></div>
        </div>
        <div class="shc-desc"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mô tả tính năng chi tiết sẽ hiện ở đây.' : 'Detailed feature description will appear here.') ?></div>
    </div>

    <?php 
    // KIỂM TRA BIẾN IFRAME, NẾU LÀ IFRAME THÌ CHẶN KHÔNG RENDER CHAT VÀ PWA PROMO
    $is_iframe = isset($_GET['iframe']) && $_GET['iframe'] == 1;
    if (!$is_iframe): 
    ?>

    <?php if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['STUDENT', 'RED_FLAG'])): 
        // TẠO TOKEN SSO ĐỂ PYTHON NHẬN DIỆN
        $secret_key = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : "khoa_bi_mat_ket_noi_hai_app_123456_secure"; 
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $raw_avatar = $_SESSION['user']['avatar'] ?? 'static/default.png';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $avatar_full = ($raw_avatar && strpos($raw_avatar, 'http') === false) ? $domain . "/" . ltrim($raw_avatar, '/') : $raw_avatar;

        $payload = json_encode([
            'sbd' => $_SESSION['user']['username'],
            'name' => $_SESSION['user']['full_name'],
            'role' => $_SESSION['user']['role'],
            'avatar' => $avatar_full, 
            'exp' => time() + 300 
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        $jwt_token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    ?>
    
    <style>
        /* CSS GIAO DIỆN CHUẨN ĐÃ ĐƯỢC LÀM SẠCH MÀU CỨNG */
        :root { --chat-primary: #0084ff; --chat-bg-bubble: #e4e6eb; }
        [data-theme="dark"] { --chat-primary: #3b82f6; --chat-bg-bubble: var(--bg-input); }

        .sc-bubble-icon { position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; background: var(--chat-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 26px; cursor: pointer; z-index: 9999; box-shadow: 0 4px 15px rgba(0,0,0,0.3); transition: transform 0.2s; }
        .sc-bubble-icon:active { transform: scale(0.9); }
        
        #tvtl-chat-popup { display: none; position: fixed; bottom: 90px; right: 20px; width: 380px; height: 550px; background: var(--bg-card); border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); z-index: 10000; flex-direction: column; overflow: hidden; border: 1px solid var(--border-color); animation: fadeInChat 0.2s ease-out; }
        @keyframes fadeInChat { from { opacity: 0; transform: translateY(20px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }

        .chat-header-main { height: 50px; flex-shrink: 0; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 15px; background: var(--bg-card); color: var(--chat-primary); font-weight: 700; font-size: 16px; }

        .chat-tab { flex: 1; text-align: center; padding: 12px; cursor: pointer; font-weight: 600; color: var(--text-muted); border-bottom: 2px solid transparent; background: var(--bg-card); }
        .chat-tab.active { color: var(--chat-primary); border-bottom-color: var(--chat-primary); background: var(--bg-hover); }
        
        .friend-item { display: flex; align-items: center; padding: 10px 15px; background: var(--bg-card); border-bottom: 1px solid var(--border-color); cursor: pointer; color: var(--text-main); }
        .friend-item:hover { background: var(--bg-hover); }
        .friend-item img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; margin-right: 12px; border: 1px solid var(--border-color); }
        .friend-info { flex: 1; min-width: 0; }
        .friend-name { font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .friend-status { font-size: 11px; color: var(--text-muted); }

        .chat-list { flex: 1; overflow-y: auto; padding: 15px; display: flex; flex-direction: column; gap: 8px; background: var(--bg-body); }
        .msg-row { display: flex; width: 100%; margin-bottom: 5px; position: relative; }
        .msg-me { justify-content: flex-end; } .msg-other { justify-content: flex-start; } 
        
        .bubble { max-width: 80%; padding: 10px 14px; border-radius: 18px; font-size: 15px; line-height: 1.4; word-wrap: break-word; position: relative; cursor: pointer; user-select: none; }
        .bubble:active { transform: scale(0.98); opacity: 0.8; }
        .msg-me .bubble { background: var(--chat-primary); color: white; border-bottom-right-radius: 4px; }
        .msg-other .bubble { background: var(--chat-bg-bubble); color: var(--text-main); border-bottom-left-radius: 4px; border: 1px solid var(--border-color); }

        .input-area { min-height: 60px; border-top: 1px solid var(--border-color); display: flex; flex-direction: column; background: var(--bg-card); padding: 0; }
        .btn-send { width: 40px; height: 40px; border: none; background: none; color: var(--chat-primary); font-size: 20px; cursor: pointer; }

        .reply-quote { font-size: 12px; color: var(--text-muted); background: var(--bg-hover); padding: 5px 8px; border-radius: 8px; margin-bottom: 5px; border-left: 3px solid var(--border-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .msg-me .reply-quote { background: rgba(255,255,255,0.2); color: var(--bg-card); border-left-color: var(--bg-card); }
        
        .reaction-badge { position: absolute; bottom: -10px; right: 0; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 2px 4px; font-size: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); z-index: 2; color: var(--text-main); }
        .msg-me .reaction-badge { right: auto; left: 0; }
        
        .reply-preview { display: none; padding: 8px 15px; background: var(--bg-hover); border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-muted); align-items: center; justify-content: space-between; }

        /* MENU NGỮ CẢNH NỔI */
        .tvtl-custom-menu-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; z-index: 10001; background: rgba(0,0,0,0.2); }
        .tvtl-custom-menu { position: absolute; background: var(--bg-card); border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.25); width: 250px; overflow: hidden; border: 1px solid var(--border-color); display: flex; flex-direction: column; animation: fadeInMenu 0.1s ease-out; }
        @keyframes fadeInMenu { from{opacity:0;transform:scale(0.95);} to{opacity:1;transform:scale(1);} }
        
        .tvtl-reaction-bar { display: flex; justify-content: space-around; padding: 10px 5px; border-bottom: 1px solid var(--border-color); }
        .tvtl-reaction-emoji { font-size: 24px; cursor: pointer; transition: transform 0.2s; }
        .tvtl-reaction-emoji:hover { transform: scale(1.3); }
        
        .tvtl-menu-item { padding: 12px 15px; display: flex; align-items: center; justify-content: space-between; font-size: 15px; cursor: pointer; color: var(--text-main); transition: 0.2s; font-weight: 500; }
        .tvtl-menu-item:hover { background: var(--bg-hover); }
        .tvtl-menu-item.text-danger { color: var(--danger-color); }

        /* Nút phụ cho Tab Bạn bè */
        .btn-friend-action { padding: 6px 12px; border-radius: 6px; border: none; font-size: 12px; font-weight: 600; cursor: pointer; margin-left: 5px; }
        .btn-accept { background: #10b981; color: white; }
        .btn-reject { background: var(--danger-color); color: white; }
        .btn-add { background: var(--chat-primary); color: white; }
        .btn-sent { background: var(--bg-input); color: var(--text-muted); cursor: default; border: 1px solid var(--border-color); } 
        .btn-chat { background: var(--chat-bg-bubble); color: var(--text-main); border: 1px solid var(--border-color); }
        .section-title { font-size: 12px; font-weight: 700; color: var(--text-muted); margin: 15px 0 5px 15px; text-transform: uppercase; }

        @media (max-width: 768px) { #tvtl-chat-popup { width: 100%; height: 100%; bottom: 0; right: 0; border-radius: 0; } }

        /* ==============================================================
           TRỊ TẬN GỐC LỖI TRẮNG XÓA TRÊN AMOLED BẰNG SELECTOR ƯU TIÊN
           ============================================================== */
        html[data-theme="dark"] body .sc-bubble-icon,
        html[data-theme="dark"] body .btn-accept,
        html[data-theme="dark"] body .btn-reject,
        html[data-theme="dark"] body .btn-add,
        html[data-theme="dark"] body .msg-me .bubble {
            color: #000000;
            font-weight: 800;
        }
    </style>

    <div class="sc-bubble-icon" onclick="StudentChatApp.toggle()" role="button" tabindex="0" aria-label="Toggle Chat"><i class="fa-brands fa-facebook-messenger" aria-hidden="true"></i></div>

    <div id="tvtl-menu-overlay" class="tvtl-custom-menu-overlay" onclick="StudentChatApp.hideMenu()">
        <div id="tvtl-menu" class="tvtl-custom-menu" onclick="event.stopPropagation()">
            <div class="tvtl-reaction-bar">
                <span class="tvtl-reaction-emoji" onclick="StudentChatApp.react('❤️')" role="button" tabindex="0">❤️</span>
                <span class="tvtl-reaction-emoji" onclick="StudentChatApp.react('😆')" role="button" tabindex="0">😆</span>
                <span class="tvtl-reaction-emoji" onclick="StudentChatApp.react('😮')" role="button" tabindex="0">😮</span>
                <span class="tvtl-reaction-emoji" onclick="StudentChatApp.react('😢')" role="button" tabindex="0">😢</span>
                <span class="tvtl-reaction-emoji" onclick="StudentChatApp.react('😡')" role="button" tabindex="0">😡</span>
                <span class="tvtl-reaction-emoji" onclick="StudentChatApp.react('👍')" role="button" tabindex="0">👍</span>
                <span class="tvtl-reaction-emoji" onclick="StudentChatApp.react('')" role="button" tabindex="0">❌</span>
            </div>
            <div class="tvtl-menu-item" onclick="StudentChatApp.prepReply()" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trả lời' : 'Reply') ?> <i class="fa-solid fa-reply" aria-hidden="true"></i></div>
            <div class="tvtl-menu-item" onclick="StudentChatApp.copyMsg()" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sao chép' : 'Copy') ?> <i class="fa-solid fa-copy" aria-hidden="true"></i></div>
            <div id="tvtl-menu-delete-btn" class="tvtl-menu-item text-danger" onclick="StudentChatApp.delMsg()" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa' : 'Delete') ?> <i class="fa-solid fa-trash" aria-hidden="true"></i></div>
        </div>
    </div>

    <div id="tvtl-chat-popup">
        <div class="chat-header-main">
            <span><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chat' : 'Chat') ?></span>
            <i class="fas fa-times" style="cursor:pointer; padding:5px;" onclick="StudentChatApp.toggle()" role="button" tabindex="0" aria-label="Close" aria-hidden="true"></i>
        </div>

        <div id="tvtl-list-view" style="display:flex; flex-direction:column; height:100%;">
            <div style="display:flex; border-bottom:1px solid var(--border-color); background:var(--bg-card);">
                <div id="tab-teachers" class="chat-tab active" onclick="StudentChatApp.switchTab('teachers')" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thầy Cô' : 'Teachers') ?></div>
                <div id="tab-friends" class="chat-tab" onclick="StudentChatApp.switchTab('friends')" role="button" tabindex="0"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn Bè' : 'Friends') ?></div>
            </div>
            
            <div id="tab-teachers-content" style="flex:1; overflow-y:auto; padding:0; background:var(--bg-body);">
                <div id="teacher-list">
                    <div style="text-align:center; padding:20px; color:var(--text-muted);"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang tải...' : 'Loading...') ?></div>
                </div>
            </div>

            <div id="tab-friends-content" style="display:none; flex:1; overflow-y:auto; padding:0; background:var(--bg-body);">
                <div style="padding:10px; background:var(--bg-card); border-bottom:1px solid var(--border-color); position:sticky; top:0; z-index:5;">
                    <div style="display:flex; gap:8px;">
                        <input type="text" id="friend-search-inp" aria-label="Search friends" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập tên hoặc Mã HS...' : 'Enter name or Student ID...') ?>" style="flex:1; padding:10px; border:1px solid var(--border-color); background:var(--bg-input); border-radius:8px; outline:none; color:var(--text-main);" onkeypress="if(event.key==='Enter') StudentChatApp.searchFriends()">
                        <button onclick="StudentChatApp.searchFriends()" style="width:50px; background:var(--chat-primary); color:white; border:none; border-radius:8px; cursor:pointer;" class="btn-add" aria-label="Search friends"><i class="fa-solid fa-search" aria-hidden="true"></i></button>
                    </div>
                </div>
                
                <div id="search-results"></div>
                
                <div id="pending-section" style="display:none;">
                    <div class="section-title" style="color:#f59e0b;"><i class="fa-solid fa-clock" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lời mời kết bạn' : 'Friend Requests') ?></div>
                    <div id="pending-list"></div>
                </div>
                
                <div id="sent-section" style="display:none;">
                    <div class="section-title"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đã gửi lời mời' : 'Sent Requests') ?></div>
                    <div id="sent-list"></div>
                </div>
                
                <div class="section-title"><i class="fa-solid fa-user-group" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Danh sách bạn bè' : 'Friends List') ?></div>
                <div id="friend-list">
                    <div style="text-align:center; padding:30px; color:var(--text-muted);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang tải...' : 'Loading...') ?></div>
                </div>
            </div>
        </div>

        <div id="tvtl-chat-screen" style="display:none; position:absolute; top:0; left:0; right:0; bottom:0; background:var(--bg-card); z-index:20; flex-direction:column;">
            <div style="height:50px; background:var(--bg-card); border-bottom:1px solid var(--border-color); display:flex; align-items:center; padding:0 10px;">
                <button onclick="StudentChatApp.closeChat()" style="background:none; border:none; font-size:20px; color:var(--text-main); cursor:pointer;" aria-label="Back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                <img id="tvtl-chat-ava" src="" style="width:34px; height:34px; border-radius:50%; margin-left:10px; margin-right:10px; object-fit:cover; border:1px solid var(--border-color);">
                <b id="tvtl-chat-title" style="flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:var(--text-main);">Chat</b>
                
                <label id="tvtl-anon-toggle-wrapper" style="display:none; align-items:center; gap:5px; font-size:12px; cursor:pointer; background:var(--bg-hover); padding:4px 8px; border-radius:15px; border:1px solid var(--border-color); user-select:none; margin-left:5px;" title="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Gửi tin nhắn ẩn danh cho Thầy Cô' : 'Send anonymous message to Teachers') ?>">
                    <input type="checkbox" id="tvtl-anon-checkbox" style="cursor:pointer;" onchange="StudentChatApp.toggleAnon(this.checked)">
                    <span style="font-weight:600; color:var(--text-main); display:flex; align-items:center; gap:3px;">
                        <i class="fa-solid fa-user-secret" style="color:#8b5cf6;"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Ẩn danh' : 'Anonymous') ?>
                    </span>
                </label>
            </div>
            
            <div id="tvtl-anon-banner" style="display:none; background:#8b5cf6; color:#ffffff; font-size:11px; text-align:center; padding:4px 10px; font-weight:600;">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang bật chế độ nhắn tin Ẩn danh với Thầy Cô' : 'Anonymous mode active with Teachers') ?>
            </div>
            
            <div id="tvtl-chat-msgs" class="chat-list"></div>
            
            <div class="input-area">
                <div id="tvtl-reply-preview" class="reply-preview" style="width: 100%;">
                    <div><i class="fa-solid fa-reply" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang trả lời' : 'Replying to') ?> <b id="tvtl-reply-text" style="color:var(--text-main);">...</b></div>
                    <i class="fa-solid fa-xmark" style="cursor:pointer; padding:5px; color:var(--text-main);" onclick="StudentChatApp.cancelReply()" role="button" tabindex="0" aria-label="Cancel Reply" aria-hidden="true"></i>
                </div>

                <div style="display: flex; width: 100%; padding: 10px; align-items: center; gap: 10px; box-sizing: border-box;">
                    <label for="tvtl-img-input" style="cursor:pointer; padding:0 5px; color:var(--chat-primary);" aria-label="Upload Image">
                        <i class="fa-solid fa-image" style="font-size:20px;" aria-hidden="true"></i>
                    </label>
                    <input type="file" id="tvtl-img-input" accept="image/*" style="display:none;" onchange="StudentChatApp.uploadImg(this)">
                    <input type="text" id="tvtl-chat-inp" aria-label="Type message" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập tin nhắn...' : 'Type message...') ?>" onkeypress="if(event.key==='Enter') StudentChatApp.sendMsg()" style="flex:1; border:1px solid var(--border-color); background:var(--bg-input); padding:10px 15px; border-radius:20px; outline:none; color:var(--text-main);">
                    <button class="btn-send" onclick="StudentChatApp.sendMsg()" aria-label="Send Message"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>
    </div>

    <script>
    const StudentChatApp = (function() {
        const myId = <?= $_SESSION['user']['id'] ?>;
        let currentPartnerId = null;
        let chatInterval = null;
        let longPressTimer;
        let selectedMsgId = null;
        let replyingToId = null;
        
        const pythonBase = '';
        let isPythonLogged = false;

        async function fetchPy(endpoint, options = {}) {
            options.credentials = 'include';
            if (!options.headers && !(options.body instanceof FormData)) options.headers = { 'Content-Type': 'application/json' };
            try {
                const res = await fetch(`consulting_chat.php?endpoint=${encodeURIComponent(endpoint)}`, options);
                if (!res.ok) throw new Error((window.LANG && window.LANG.http_error || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'HTTP Lỗi:' : 'HTTP Error:') ?>") + ` ${res.status}`);
                return await res.json();
            } catch(e) { 
                console.error(window.LANG && window.LANG.fetch_api_error || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Fetch API Lỗi:' : 'Fetch API Error:') ?>", e);
                return null; 
            }
        }

        // --- LOAD DATA FUNCTIONS ---
        async function loadTeachers() {
            const listDiv = document.getElementById('teacher-list');
            try {
                const data = await fetchPy('/api/list_teachers');
                let h = '';
                if (data && Array.isArray(data) && data.length > 0) {
                    data.forEach(t => {
                        let ava = (t.avatar && t.avatar.length > 5) ? t.avatar : 'static/default.png';
                        if (!ava.startsWith('http')) ava = pythonBase + '/' + ava.replace(/^\//, '');
                        h += `<div class="friend-item" onclick="StudentChatApp.openChat(${t.id}, '${t.full_name.replace(/'/g, "\\'")}', '${ava}', true)" role="button" tabindex="0" aria-label="Open chat with ${t.full_name}">
                                <img src="${ava}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(t.full_name)}&background=random'">
                                <div class="friend-info">
                                    <div class="friend-name">${t.full_name}</div>
                                    <div class="friend-status">${window.LANG&&window.LANG.school_counselor_role|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Giáo viên tâm lý" : "School Counselor") ?>'}</div>
                                </div>
                              </div>`;
                    });
                } else { h = `<div style="text-align:center;padding:20px;color:var(--text-muted)">${window.LANG&&window.LANG.no_teachers_yet|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Chưa có giáo viên nào." : "No teachers yet.") ?>'}</div>`; }
                listDiv.innerHTML = h;
            } catch (e) {
                listDiv.innerHTML = `<div style="text-align:center;padding:20px;color:var(--danger-color);"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> ${window.LANG&&window.LANG.chat_server_error|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Mất kết nối đến máy chủ Chat!" : "Lost connection to Chat server!") ?>'}</div>`;
            }
        }

        async function loadFriends() {
            const data = await fetchPy('/api/friends/list');
            if(!data) return;
            
            // Lời mời kết bạn
            const pDiv = document.getElementById('pending-list');
            const pSec = document.getElementById('pending-section');
            if(data.requests && data.requests.length > 0) {
                pSec.style.display = 'block'; let h = '';
                data.requests.forEach(r => {
                    let ava = r.avatar || 'static/default.png'; if (!ava.startsWith('http')) ava = pythonBase + '/' + ava.replace(/^\//, '');
                    h += `<div class="friend-item">
                            <img src="${ava}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(r.full_name)}&background=random'">
                            <div class="friend-info">
                                <div class="friend-name">${r.full_name}</div>
                                <div style="font-size:11px;color:var(--text-muted);">${window.LANG&&window.LANG.student_id_prefix|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Mã HS" : "Student ID") ?>'}: ${r.username || ''}</div>
                            </div>
                            <button class="btn-friend-action btn-accept" onclick="StudentChatApp.respondFriend(${r.req_id}, 'accept')">${window.LANG&&window.LANG.accept_label|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đồng ý" : "Accept") ?>'}</button>
                            <button class="btn-friend-action btn-reject" onclick="StudentChatApp.respondFriend(${r.req_id}, 'reject')">${window.LANG&&window.LANG.reject_label|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Xóa" : "Reject") ?>'}</button>
                          </div>`;
                }); pDiv.innerHTML = h;
            } else pSec.style.display = 'none';

            // Đã gửi lời mời
            const sDiv = document.getElementById('sent-list');
            const sSec = document.getElementById('sent-section');
            if(data.sent && data.sent.length > 0) {
                sSec.style.display = 'block'; let h = '';
                data.sent.forEach(s => {
                    let ava = s.avatar || 'static/default.png'; if (!ava.startsWith('http')) ava = pythonBase + '/' + ava.replace(/^\//, '');
                    h += `<div class="friend-item"><img src="${ava}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(s.full_name)}&background=random'"><div class="friend-info"><div class="friend-name">${s.full_name}</div><div class="friend-status">${window.LANG&&window.LANG.friend_request_sent|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đã gửi lời mời" : "Friend request sent") ?>'}</div></div><button class="btn-friend-action btn-sent" disabled>${window.LANG&&window.LANG.sent_label|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đã gửi" : "Sent") ?>'}</button></div>`;
                }); sDiv.innerHTML = h;
            } else sSec.style.display = 'none';

            // Bạn bè chính thức
            const fDiv = document.getElementById('friend-list');
            if(data.friends && data.friends.length > 0) {
                let h = '';
                data.friends.forEach(f => {
                    let ava = f.avatar || 'static/default.png'; if (!ava.startsWith('http')) ava = pythonBase + '/' + ava.replace(/^\//, '');
                    let online = f.last_active && (new Date() - new Date(f.last_active) < 120000); // Online check 2 mins
                    h += `<div class="friend-item" onclick="StudentChatApp.openChat(${f.id}, '${f.full_name}', '${ava}')" role="button" tabindex="0" aria-label="Open chat with ${f.full_name}">
                            <img src="${ava}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(f.full_name)}&background=random'">
                            <div class="friend-info">
                                <div class="friend-name">${f.full_name}</div>
                                <div class="friend-status" style="color:${online?'#10b981':'var(--text-muted)'}">${online?'● Online':(window.LANG&&window.LANG.offline_label|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Offline" : "Offline") ?>')}</div>
                            </div>
                            <button class="btn-friend-action btn-chat"><i class="fa-solid fa-comment" aria-hidden="true"></i></button>
                          </div>`;
                }); fDiv.innerHTML = h;
            } else fDiv.innerHTML = `<div style="text-align:center;padding:20px;color:var(--text-muted)">${window.LANG&&window.LANG.no_friends_yet|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Chưa có bạn bè. Hãy tìm kiếm!" : "No friends yet. Start searching!") ?>'}</div>`;
        }

        async function loadMsgs(force = false) {
            if(!currentPartnerId) return;
            const msgs = await fetchPy('/api/chat/get', { method:'POST', body:JSON.stringify({partner_id:currentPartnerId}) });
            if(!msgs || !Array.isArray(msgs)) return;
            
            const box = document.getElementById('tvtl-chat-msgs');
            let html = '';
            
            msgs.forEach(m => {
                let isMe = (m.sender_id == myId);
                let cls = isMe ? 'msg-me' : 'msg-other';
                let contentRaw = m.content || ''; 
                let displayContent = contentRaw;
                
                if (contentRaw.startsWith('[IMG]:')) {
                    displayContent = `<img src="${pythonBase}${contentRaw.replace('[IMG]:','')}" style="max-width:200px; border-radius:12px; display:block;" onclick="window.open(this.src,'_blank')">`;
                    contentRaw = window.LANG && window.LANG.image_label || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '[Hình ảnh]' : '[Image]') ?>";
                } else { displayContent = displayContent.replace(/</g, "&lt;"); }

                let replyHtml = '';
                if (m.reply_id && m.reply_content) {
                    let rContent = m.reply_content.startsWith('[IMG]:') ? (window.LANG && window.LANG.image_label || '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "[Hình ảnh]" : "[Image]") ?>') : m.reply_content.replace(/</g, "&lt;");
                    replyHtml = `<div class="reply-quote"><i class="fa-solid fa-reply" aria-hidden="true"></i> ${rContent}</div>`;
                }
                let reactHtml = m.reactions ? `<div class="reaction-badge">${m.reactions}</div>` : '';
                let anonTag = (m.is_anonymous == 1) ? `<div style="font-size:10px; font-weight:bold; color:#8b5cf6; margin-bottom:3px;"><i class="fa-solid fa-user-secret"></i> ${window.LANG && window.LANG.anonymous || '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Ẩn danh" : "Anonymous") ?>'}</div>` : '';

                html += `
                <div class="msg-row ${cls}">
                    <div class="bubble" 
                         oncontextmenu="StudentChatApp.showMenu(event, ${m.id}, ${isMe}, '${contentRaw.replace(/'/g, "\\'")}'); return false;"
                         ontouchstart="window.longPressTimer = setTimeout(() => StudentChatApp.showMenu(event, ${m.id}, ${isMe}, '${contentRaw.replace(/'/g, "\\'")}'), 500)"
                         ontouchend="clearTimeout(window.longPressTimer)">
                        ${anonTag}
                        ${replyHtml}
                        ${displayContent}
                        ${reactHtml}
                    </div>
                </div>`;
            });
            
            if(box.innerHTML !== html) {
                const isAtBottom = (box.scrollHeight - box.scrollTop - box.clientHeight < 150);
                box.innerHTML = html;
                if(force || isAtBottom) setTimeout(() => box.scrollTo({ top: box.scrollHeight }), 50); 
            }
        }

        return {
            toggle: function() {
                const pop = document.getElementById('tvtl-chat-popup');
                if(!pop) return;
                const isOpen = pop.style.display === 'flex';
                pop.style.display = isOpen ? 'none' : 'flex';
                if (!isOpen) { 
                document.getElementById('teacher-list').innerHTML = `<div style="text-align:center;padding:20px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> ${window.LANG&&window.LANG.loading|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đang tải..." : "Loading...") ?>'}</div>`;
                    loadTeachers(); 
                    if(document.getElementById('tab-friends').classList.contains('active')) loadFriends();
                }
                else { if(chatInterval) clearInterval(chatInterval); }
            },
            switchTab: function(t) {
                document.getElementById('tab-teachers').classList.remove('active');
                document.getElementById('tab-friends').classList.remove('active');
                document.getElementById('tab-'+t).classList.add('active');
                document.getElementById('tab-teachers-content').style.display = (t==='teachers'?'block':'none');
                document.getElementById('tab-friends-content').style.display = (t==='friends'?'block':'none');
                if(t === 'friends') loadFriends();
            },
            
            // --- BẠN BÈ TÍNH NĂNG ---
            searchFriends: async function() {
                const q = document.getElementById('friend-search-inp').value.trim();
                const div = document.getElementById('search-results');
                if(!q) { div.innerHTML = ''; return; }
                
                div.innerHTML = `<div style="padding:10px;text-align:center;color:var(--text-muted);"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> ${window.LANG&&window.LANG.searching_label|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đang tìm..." : "Searching...") ?>'}</div>`;
                const users = await fetchPy('/api/friends/search', {method:'POST', body:JSON.stringify({query:q})});
                if(!users || !users.length) { div.innerHTML = `<div style="padding:15px;text-align:center;color:var(--danger-color);">${window.LANG&&window.LANG.no_students_found|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Không tìm thấy học sinh nào." : "No students found.") ?>'}</div>`; return; }
                
                let h = '';
                users.forEach(u => {
                    let ava = (u.avatar && u.avatar.length>5) ? u.avatar : 'static/default.png'; if (!ava.startsWith('http')) ava = pythonBase + '/' + ava.replace(/^\//, '');
                    let btnHtml = '';
                    if (u.relation === 'friend') btnHtml = `<button class="btn-friend-action btn-chat" onclick="StudentChatApp.openChat(${u.id}, '${u.full_name}', '${ava}')">Chat</button>`;
                    else if (u.relation === 'sent') btnHtml = `<button class="btn-friend-action btn-sent" disabled>${window.LANG&&window.LANG.sent_label|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đã gửi" : "Sent") ?>'}</button>`;
                    else if (u.relation === 'received') btnHtml = `<button class="btn-friend-action btn-accept" onclick="alert('${window.LANG&&window.LANG.check_friend_request_below|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Vui lòng kiểm tra mục Lời mời kết bạn bên dưới!" : "Please check Friend Requests below!") ?>'}')">${window.LANG&&window.LANG.accept_label|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Chấp nhận" : "Accept") ?>'}</button>`;
                    else btnHtml = `<button class="btn-friend-action btn-add" onclick="StudentChatApp.addFriend(${u.id}, this)">${window.LANG&&window.LANG.add_friend_label|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Kết bạn" : "Add Friend") ?>'}</button>`;
                    
                    h += `<div class="friend-item" style="cursor:default"><img src="${ava}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(u.full_name)}&background=random'"><div class="friend-info"><div class="friend-name">${u.full_name}</div><div style="font-size:11px;color:var(--text-muted);">${window.LANG&&window.LANG.student_id_prefix|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Mã HS" : "Student ID") ?>'}: ${u.username}</div></div>${btnHtml}</div>`;
                }); 
                div.innerHTML = h;
            },
            addFriend: async function(uid, btnElement) {
                const originalText = btnElement.innerText;
                btnElement.innerText = window.LANG&&window.LANG.sending_label|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đang gửi..." : "Sending...") ?>'; btnElement.disabled = true;
                const data = await fetchPy('/api/friends/request', {method:'POST', body:JSON.stringify({target_id: uid})});
                if(data && data.success) { btnElement.innerText = window.LANG&&window.LANG.sent_label|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đã gửi" : "Sent") ?>'; btnElement.className = "btn-friend-action btn-sent"; loadFriends(); } 
                else { alert(window.LANG&&window.LANG.friend_request_error|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Lỗi gửi lời mời" : "Error sending request") ?>'); btnElement.innerText = originalText; btnElement.disabled = false; }
            },
            respondFriend: async function(rid, act) {
                if(!confirm(act==='accept'?(window.LANG&&window.LANG.confirm_accept_friend|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đồng ý kết bạn?" : "Accept friend request?") ?>'):(window.LANG&&window.LANG.confirm_reject_friend|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Xóa lời mời?" : "Reject request?") ?>'))) return;
                await fetchPy('/api/friends/respond', {method:'POST', body:JSON.stringify({req_id:rid, action:act})});
                loadFriends();
            },

            // --- CHAT TÍNH NĂNG ---
            openChat: function(tid, name, ava, isTeacher = false) {
                currentPartnerId = tid;
                currentPartnerIsTeacher = isTeacher;
                document.getElementById('tvtl-list-view').style.display='none';
                document.getElementById('tvtl-chat-screen').style.display='flex';
                document.getElementById('tvtl-chat-title').innerText = name;
                document.getElementById('tvtl-chat-ava').src = ava;
                
                const anonWrapper = document.getElementById('tvtl-anon-toggle-wrapper');
                if (anonWrapper) {
                    anonWrapper.style.display = isTeacher ? 'flex' : 'none';
                }
                const anonChk = document.getElementById('tvtl-anon-checkbox');
                if (anonChk) {
                    this.toggleAnon(anonChk.checked && isTeacher);
                }

                document.getElementById('tvtl-chat-msgs').innerHTML = '<div style="text-align:center;padding:20px;color:var(--text-muted);"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i></div>';
                
                this.cancelReply();
                loadMsgs(true); 
                if(chatInterval) clearInterval(chatInterval);
                chatInterval = setInterval(() => loadMsgs(false), 3000);
            },
            toggleAnon: function(isAnon) {
                const banner = document.getElementById('tvtl-anon-banner');
                if (banner) {
                    banner.style.display = (isAnon && currentPartnerIsTeacher) ? 'block' : 'none';
                }
            },
            closeChat: function() {
                document.getElementById('tvtl-chat-screen').style.display='none';
                document.getElementById('tvtl-list-view').style.display='flex';
                currentPartnerId = null;
                currentPartnerIsTeacher = false;
                if(chatInterval) clearInterval(chatInterval);
            },
            sendMsg: async function() {
                const inp = document.getElementById('tvtl-chat-inp');
                const txt = inp.value.trim();
                if(!txt || !currentPartnerId) return;
                
                const isAnon = currentPartnerIsTeacher && document.getElementById('tvtl-anon-checkbox')?.checked;
                
                const box = document.getElementById('tvtl-chat-msgs');
                const anonTag = isAnon ? '<span style="font-size:10px; font-weight:bold; color:#8b5cf6; margin-bottom:2px; display:block;"><i class="fa-solid fa-user-secret"></i> Ẩn danh</span>' : '';
                box.innerHTML += `<div class="msg-row msg-me"><div class="bubble" style="opacity:0.6">${anonTag}${txt.replace(/</g, "&lt;")}</div></div>`;
                box.scrollTop = box.scrollHeight;

                await fetchPy('/api/chat/send', {
                    method:'POST', body:JSON.stringify({ receiver_id:currentPartnerId, content:txt, reply_id: replyingToId, is_anonymous: isAnon ? 1 : 0 })
                });
                inp.value=''; this.cancelReply(); loadMsgs(true);
            },
            uploadImg: async function(input) {
                const file = input.files[0]; if(!file) return;
                const formData = new FormData(); formData.append('file', file);
                const data = await fetchPy('/api/chat/upload', {method:'POST', body:formData}, true);
                if(data && data.success) {
                    const isAnon = currentPartnerIsTeacher && document.getElementById('tvtl-anon-checkbox')?.checked;
                    await fetchPy('/api/chat/send', { method:'POST', body:JSON.stringify({ content:`[IMG]:${data.url}`, receiver_id:currentPartnerId, reply_id: replyingToId, is_anonymous: isAnon ? 1 : 0 }) });
                    this.cancelReply(); loadMsgs(true);
                } else { alert(window.LANG && window.LANG.upload_img_error || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi tải ảnh lên máy chủ' : 'Error uploading image') ?>"); }
                input.value = '';
            },
            showMenu: function(e, msgId, isMe, content) {
                e.preventDefault(); selectedMsgId = msgId;
                const menu = document.getElementById('tvtl-menu');
                menu.dataset.content = content; menu.dataset.isMe = isMe;
                
                document.getElementById('tvtl-menu-delete-btn').style.display = isMe ? 'flex' : 'none';
                document.getElementById('tvtl-menu-overlay').style.display = 'block';
                
                let x = e.clientX || (e.touches && e.touches[0].clientX); let y = e.clientY || (e.touches && e.touches[0].clientY);
                if (x + 250 > window.innerWidth) x = window.innerWidth - 260; if (y + 220 > window.innerHeight) y = window.innerHeight - 230;
                menu.style.left = x + 'px'; menu.style.top = y + 'px';
            },
            hideMenu: function() { document.getElementById('tvtl-menu-overlay').style.display = 'none'; },
            react: async function(emoji) {
                this.hideMenu(); if(!selectedMsgId) return;
                await fetchPy('/api/chat/react', { method: 'POST', body: JSON.stringify({ message_id: selectedMsgId, emoji: emoji }) });
                loadMsgs(false);
            },
            prepReply: function() {
                this.hideMenu(); replyingToId = selectedMsgId;
                document.getElementById('tvtl-reply-preview').style.display = 'flex';
                document.getElementById('tvtl-reply-text').innerText = document.getElementById('tvtl-menu').dataset.content.substring(0, 30) + '...';
                document.getElementById('tvtl-chat-inp').focus();
            },
            cancelReply: function() { replyingToId = null; document.getElementById('tvtl-reply-preview').style.display = 'none'; },
            copyMsg: function() { navigator.clipboard.writeText(document.getElementById('tvtl-menu').dataset.content); this.hideMenu(); },
            delMsg: async function() {
                this.hideMenu(); if(!confirm(window.LANG&&window.LANG.delete_message_confirm|| "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Bạn muốn xóa tin nhắn này?' : 'Delete this message?') ?>")) return;
                const d = await fetchPy('/api/chat/delete', { method:'POST', body:JSON.stringify({message_id: selectedMsgId}) });
                if(d && d.success) loadMsgs(false);
            }
        };
    })();
    </script>
    <?php endif; ?>

    <div class="pwa-promo-overlay" id="pwaPromoModal">
        <div class="pwa-promo-box">
            <img src="/lg3512512.png" alt="Logo" class="pwa-logo">
            <h2 class="pwa-title"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trải nghiệm tốt hơn với Siêu ứng dụng LG3' : 'Better experience with LG3 Super App') ?></h2>
            <p class="pwa-desc"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Cài đặt ứng dụng để nhận thông báo tức thời và truy cập mượt mà hơn.' : 'Install the app for instant notifications and smoother access.') ?></p>
            <a href="/" class="pwa-btn-switch" onclick="closePwaPromo()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chuyển sang App' : 'Switch to App') ?></a>
            <button class="pwa-btn-close" onclick="closePwaPromo(true)"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Quay lại sử dụng web' : 'Stay on Web') ?></button>
        </div>
    </div>

    <?php endif; // KẾT THÚC KHỐI KIỂM TRA IFRAME ?>

    <div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()" role="button" tabindex="0" aria-label="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đóng thanh menu' : 'Close sidebar') ?>"></div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
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

    // WinUI3 Toastify Interceptor (chỉ đăng ký 1 lần duy nhất)
    if (window.Toastify && !window.Toastify.isIntercepted) {
        const originalToastify = window.Toastify;
        window.Toastify = function(options) {
            options = options || {};
            options.gravity = "bottom";
            options.position = "center";
            options.className = (options.className || "") + " winui-toast";
            options.style = Object.assign({}, options.style || {}, { fontFamily: "'Be Vietnam Pro', sans-serif" });
            if (options.text) {
                window.a11yAnnounce(options.text);
            }
            return originalToastify(options);
        };
        window.Toastify.isIntercepted = true;
    }
    function updateSidebarSlider() {
        const activeLink = document.querySelector('.sidebar-menu-scroll .nav-link.active');
        const slider = document.getElementById('sidebar-active-slider');
        
        if (activeLink && slider) {
            const linkHeight = activeLink.offsetHeight;
            const topPos = activeLink.offsetTop;
            const leftPos = activeLink.offsetLeft; 
            const sliderHeight = linkHeight * 0.45; 
            const finalY = topPos + (linkHeight - sliderHeight) / 2;
            slider.style.height = `${sliderHeight}px`;
            slider.style.transform = `translateY(${finalY}px)`;
            slider.style.left = `${leftPos}px`; 
            slider.style.opacity = '1';
        } else if (slider) { 
            slider.style.opacity = '0'; 
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        const links = document.querySelectorAll('.sidebar-menu-scroll .nav-link, .sidebar-footer .nav-link');
        const card = document.getElementById('sidebarHoverCard');
        const cIcon = card.querySelector('.shc-icon i');
        const cTitle = card.querySelector('.shc-title');
        const cDesc = card.querySelector('.shc-desc');
        let hoverTimer;

        links.forEach(link => {
            link.addEventListener('mouseenter', () => {
                if(window.innerWidth < 992) return;
                const title = link.getAttribute('data-title');
                const desc = link.getAttribute('data-desc');
                const iconClass = link.querySelector('i').className;
                if(!title) return;

                hoverTimer = setTimeout(() => {
                    cTitle.innerText = title; cDesc.innerText = desc; cIcon.className = iconClass;
                    const rect = link.getBoundingClientRect();
                    const topPos = rect.top + (rect.height / 2) - 60; 
                    card.style.top = `${Math.max(10, topPos)}px`; card.classList.add('active');
                }, 500);
            });
            link.addEventListener('mouseleave', () => { clearTimeout(hoverTimer); card.classList.remove('active'); });
        });

        setTimeout(updateSidebarSlider, 50);
        setTimeout(updateSidebarSlider, 150);

        if (window.ResizeObserver) {
            const ro = new ResizeObserver(() => {
                updateSidebarSlider();
            });
            document.querySelectorAll('.sidebar-menu-scroll .nav-link').forEach(link => {
                ro.observe(link);
            });
        }
    });

    window.addEventListener('resize', updateSidebarSlider);
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
    }
    function cycleThemeMode() {
        const current = getSavedMode();
        let nextIndex = THEME_MODES.indexOf(current) + 1;
        if (nextIndex >= THEME_MODES.length) nextIndex = 0;
        const nextMode = THEME_MODES[nextIndex];
        applyTheme(nextMode);
        
        let msg = nextMode === 'light' ? (window.LANG && window.LANG.light_mode || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '☀️ Chế độ Sáng' : '☀️ Light Mode') ?>") : (nextMode === 'dark' ? (window.LANG && window.LANG.dark_mode || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🌙 Chế độ Tối' : '🌙 Dark Mode') ?>") : (window.LANG && window.LANG.system_mode || "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🖥️ Chế độ Hệ thống' : '🖥️ System Mode') ?>"));
        
        // Đã thay background thành var(--bg-card) và color thành var(--text-main)
        Toastify({ 
            text: msg, 
            duration: 2000, 
            gravity: "bottom", 
            position: "center", 
            style: { 
                background: "var(--bg-card)", 
                color: "var(--text-main)",
                border: "1px solid var(--border-color)",
                borderRadius: "8px", 
                marginBottom: "20px", 
                boxShadow: "0 4px 12px rgba(0,0,0,0.15)" 
            } 
        }).showToast();
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
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => { if (getSavedMode() === 'system') applyTheme('system'); });

    document.addEventListener("DOMContentLoaded", function() {
        const savedMode = getSavedMode(); applyTheme(savedMode);
        if (navigator.userAgent.toLowerCase().indexOf(' electron/') > -1) document.body.classList.add('is-electron');

        const changedLang = sessionStorage.getItem('lang_changed_to');
        if (changedLang) {
            sessionStorage.removeItem('lang_changed_to');
            const msg = changedLang === 'vi' ? 'Đã chuyển sang Tiếng Việt' : 'Changed to English';
            setTimeout(() => { if (window.a11yAnnounce) window.a11yAnnounce(msg); }, 800);
        }

        function updateEtClock() {
            const now = new Date();
            const d = String(now.getDate()).padStart(2, '0'); const m = String(now.getMonth() + 1).padStart(2, '0'); const y = now.getFullYear();
            const h = String(now.getHours()).padStart(2, '0'); const min = String(now.getMinutes()).padStart(2, '0'); const s = String(now.getSeconds()).padStart(2, '0');
            const el = document.getElementById('etClock'); if(el) el.innerText = `${d}/${m}/${y} ${h}:${min}:${s}`;
        }
        setInterval(updateEtClock, 1000); updateEtClock();
    });

    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active'); document.getElementById('overlay').classList.toggle('active');
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
            window.lastActiveElementBeforeSidebar = document.activeElement;
            setTimeout(() => {
                const logoLink = document.getElementById('sidebar-logo-link');
                if (logoLink) logoLink.focus();
                
                const activeLink = document.querySelector('.sidebar-menu-scroll .nav-link.active');
                if (activeLink) activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        } else {
            document.body.style.overflow = '';
            if (window.lastActiveElementBeforeSidebar) {
                window.lastActiveElementBeforeSidebar.focus();
            } else {
                const menuBtn = document.querySelector('.mobile-menu-btn');
                if (menuBtn) menuBtn.focus();
            }
        }
    }
    
    // Auto Focus Trap in active modals and sidebars
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Tab') return;

        let activeLayer = null;
        const layers = document.querySelectorAll('.win-modal-overlay, .modal-overlay, .sidebar.active, .winui-dialog-overlay.active, #commaAlertModal');
        for (let layer of layers) {
            if (window.getComputedStyle(layer).display !== 'none' && window.getComputedStyle(layer).visibility !== 'hidden') {
                activeLayer = layer;
                break;
            }
        }

        if (!activeLayer) return;

        const focusables = activeLayer.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusables.length === 0) return;

        const firstEl = focusables[0];
        const lastEl = focusables[focusables.length - 1];

        if (!activeLayer.contains(document.activeElement)) {
            firstEl.focus();
            e.preventDefault();
            return;
        }

        if (e.shiftKey) { // Shift + Tab
            if (document.activeElement === firstEl) {
                lastEl.focus();
                e.preventDefault();
            }
        } else { // Tab
            if (document.activeElement === lastEl) {
                firstEl.focus();
                e.preventDefault();
            }
        }
    });

    if ('serviceWorker' in navigator) { window.addEventListener('load', function() { navigator.serviceWorker.register('/sw.js').catch(function(err) {}); }); }
</script>

<script>
    function startLiveClock() {
        function update() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0'); const month = String(now.getMonth() + 1).padStart(2, '0'); const year = now.getFullYear();
            const time = now.toLocaleTimeString('vi-VN', { hour12: false });
            const fullDateTime = `${day}/${month}/${year} ${time}`;
            document.querySelectorAll('.live-clock').forEach(el => { el.innerText = fullDateTime; el.style.display = 'block'; });
        }
        update(); setInterval(update, 1000);
    }
    document.addEventListener("DOMContentLoaded", function() { startLiveClock(); checkPwaStatus(); });

    function closePwaPromo(savePreference = false) {
        const modal = document.getElementById('pwaPromoModal'); if(modal) modal.style.display = 'none';
        if (savePreference) sessionStorage.setItem('pwa_promo_dismissed', 'true');
    }

    function checkPwaStatus() {
        const modal = document.getElementById('pwaPromoModal');
        const isApp = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        const path = window.location.pathname; const isRoot = path === '/' || path === '' || path.endsWith('index.php');
        const isSmallScreen = window.innerWidth < 992; const platform = navigator.platform || '';
        const isDevEnvironment = platform.indexOf('Win') !== -1 || (platform.indexOf('Mac') !== -1 && navigator.maxTouchPoints === 0);
        if (!isApp && !sessionStorage.getItem('pwa_promo_dismissed') && !isRoot && isSmallScreen && !isDevEnvironment) {
            setTimeout(() => { if(modal) modal.style.display = 'flex'; }, 1500);
        }
    }
    
    function sendHeartbeat() { fetch('api/session_heartbeat.php', { method: 'GET' }).catch(e => {}); }
    sendHeartbeat(); setInterval(sendHeartbeat, 5 * 60 * 1000);
</script>

<style>
    /* =========================================================
       WINUI 3.0 NATIVE DIALOG REPLACEMENT (AMOLED CLEAN)
       ========================================================= */
    .winui-dialog-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);
        display: flex; align-items: center; justify-content: center;
        z-index: 999999; opacity: 0; pointer-events: none;
        transition: opacity 0.2s ease;
    }
    .winui-dialog-overlay.active { opacity: 1; pointer-events: auto; }
    
    .winui-dialog {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 8px; width: 90%; max-width: 400px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        transform: scale(0.95); transition: transform 0.2s cubic-bezier(0.1, 0.9, 0.2, 1);
        display: flex; flex-direction: column; overflow: visible;
    }
    .winui-dialog-overlay.active .winui-dialog { transform: scale(1); }
    
    .winui-dialog-header { padding: 20px 20px 10px; font-size: 18px; font-weight: 600; color: var(--text-main); border-top-left-radius: 8px; border-top-right-radius: 8px; }
    .winui-dialog-body { padding: 0 20px 20px; font-size: 14px; color: var(--text-main); line-height: 1.5; }
    .winui-dialog-footer { background: var(--bg-hover); padding: 15px 20px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
    
    .winui-btn { padding: 6px 20px; border-radius: 4px; font-size: 14px; font-weight: 500; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-main); transition: 0.1s; min-width: 80px; outline: none; }
    .winui-btn:hover { background: var(--border-color); }
    .winui-btn:active { transform: scale(0.96); }
    
    .winui-btn-close { background: transparent; border: none; font-size: 24px; color: var(--text-muted); cursor: pointer; transition: 0.2s; line-height: 1; padding: 0; outline: none; }
    .winui-btn-close:hover { color: var(--danger-color); }
    
    .winui-btn-primary { background: #005fba; color: #ffffff !important; border: none; }
    .winui-btn-primary:hover { opacity: 0.9; }
    
    /* Ép màu chữ nút trên màn AMOLED */
    html[data-theme="dark"] body .winui-btn-primary { color: #000000; font-weight: 800; }
</style>

<script>

    // =========================================================
    // KHỞI TẠO BỘ THƯ VIỆN WINUI 3.0 DIALOG
    // =========================================================
    window.WinUI = {
        alert: function(title, message) { return this.show(title, message, false); },
        confirm: function(title, message, onYes, onNo) { return this.show(title, message, true, onYes, onNo); },
        popup: function(title, message) {
            const overlay = document.createElement('div');
            overlay.className = 'winui-dialog-overlay';

            const dialogId = 'wDialog_' + Date.now();
            const headerId = 'wHeader_' + Date.now();
            const bodyId = 'wBody_' + Date.now();

            overlay.innerHTML = `
                <div class="winui-dialog" id="${dialogId}" role="dialog" aria-modal="true" aria-labelledby="${headerId}" aria-describedby="${bodyId}" tabindex="-1">
                    <div class="winui-dialog-header" id="${headerId}" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>${title}</span>
                        <button class="winui-btn-close" id="wBtnClosePopup" aria-label="Close">&times;</button>
                    </div>
                    <div class="winui-dialog-body" id="${bodyId}" style="padding-bottom: 20px;">${message}</div>
                </div>
            `;
            document.body.appendChild(overlay);
            
            void overlay.offsetWidth;
            overlay.classList.add('active');

            const lastActive = document.activeElement;
            const closeIt = () => { 
                overlay.classList.remove('active'); 
                setTimeout(() => {
                    overlay.remove();
                    if (lastActive && typeof lastActive.focus === 'function') lastActive.focus();
                }, 200); 
            };

            overlay.querySelector('#wBtnClosePopup').onclick = closeIt;
            
            return { close: closeIt, element: overlay };
        },
        show: function(title, message, isConfirm, onYes, onNo) {
            const overlay = document.createElement('div');
            overlay.className = 'winui-dialog-overlay';
            
            let buttons = isConfirm 
                ? `<button class="winui-btn winui-btn-primary" id="wBtnYes">${window.LANG&&window.LANG.winui_yes|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đồng ý" : "Accept") ?>'}</button>
                   <button class="winui-btn" id="wBtnNo">${window.LANG&&window.LANG.winui_no|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Hủy" : "Cancel") ?>'}</button>`
                : `<button class="winui-btn winui-btn-primary" id="wBtnClose">${window.LANG&&window.LANG.winui_close|| '<?= (($_SESSION["lang"] ?? "vi") === "vi" ? "Đóng" : "Close") ?>'}</button>`;

            const dialogId = 'wDialog_' + Date.now();
            const headerId = 'wHeader_' + Date.now();
            const bodyId = 'wBody_' + Date.now();

            overlay.innerHTML = `
                <div class="winui-dialog" id="${dialogId}" role="dialog" aria-modal="true" aria-labelledby="${headerId}" aria-describedby="${bodyId}" tabindex="-1">
                    <div class="winui-dialog-header" id="${headerId}">${title}</div>
                    <div class="winui-dialog-body" id="${bodyId}">${message}</div>
                    <div class="winui-dialog-footer">${buttons}</div>
                </div>
            `;
            document.body.appendChild(overlay);
            
            // Kích hoạt Animation
            void overlay.offsetWidth;
            overlay.classList.add('active');

            // Focus the dialog container for screen readers to announce it
            setTimeout(() => {
                const dialogEl = document.getElementById(dialogId);
                if (dialogEl) dialogEl.focus();
                if (window.a11yAnnounce) {
                    window.a11yAnnounce(title + ". " + message.replace(/<[^>]*>/g, ''));
                }
            }, 50);

            const lastActive = document.activeElement;

            const closeIt = () => { 
                overlay.classList.remove('active'); 
                setTimeout(() => {
                    overlay.remove();
                    if (lastActive && typeof lastActive.focus === 'function') lastActive.focus();
                }, 200); 
            };

            if (isConfirm) {
                overlay.querySelector('#wBtnYes').onclick = () => { closeIt(); if (onYes) onYes(); };
                overlay.querySelector('#wBtnNo').onclick = () => { closeIt(); if (onNo) onNo(); };
            } else {
                overlay.querySelector('#wBtnClose').onclick = closeIt;
            }

            return { close: closeIt, element: overlay };
        }
    };

    // CƯỚP QUYỀN LỆNH ALERT() MẶC ĐỊNH CỦA TRÌNH DUYỆT!
    window.alert = function(msg) {
        WinUI.alert(window.LANG&&window.LANG.winui_alert_title|| "<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thông báo' : 'Notification') ?>", msg);
    };
</script>

<script src="static/js/app-shell-pro.js?v=<?= time() ?>"></script>

<?php 
if (!empty($must_change_pass) && basename($_SERVER['PHP_SELF']) !== 'logout.php'): 
?>
<!-- MODAL BẮT BUỘC ĐỔI MẬT KHẨU KHỞI TẠO -->
<div id="forceChangePassModal" style="position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.85); backdrop-filter:blur(8px); -webkit-backdrop-filter:blur(8px); z-index:999999; display:flex; align-items:center; justify-content:center; padding:15px; box-sizing:border-box;">
    <div class="win-card" style="max-width:450px; width:100%; background:var(--bg-card); border-radius:16px; padding:25px; box-shadow:0 20px 50px rgba(0,0,0,0.4); border:2px solid var(--accent-color); animation: fadeIn 0.3s ease; text-align:left;">
        <div style="text-align:center; margin-bottom:20px;">
            <div style="width:60px; height:60px; background:rgba(239, 68, 68, 0.12); color:#ef4444; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; font-size:28px;">
                <i class="fas fa-key" aria-hidden="true"></i>
            </div>
            <h3 style="margin:0; color:var(--text-main); font-size:20px; font-weight:700;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Yêu Cầu Đổi Mật Khẩu Mặc Định' : 'Default Password Change Required') ?></h3>
            <p style="margin:8px 0 0; color:var(--text-muted); font-size:13.5px; line-height:1.5;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tài khoản của bạn đang sử dụng mật khẩu mặc định. Để bảo vệ dữ liệu cá nhân, vui lòng tạo mật khẩu mới để tiếp tục.' : 'Your account is using a default password. To protect your personal data, please create a new password to continue.') ?></p>
        </div>
        
        <form id="forceChangePassForm" onsubmit="submitForceChangePass(event)">
            <div class="form-group" style="margin-bottom:15px; text-align:left; position:relative;">
                <label for="force_old_password" style="display:block; margin-bottom:6px; font-weight:600; color:var(--text-muted); font-size:13px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mật khẩu hiện tại' : 'Current password') ?></label>
                <input type="password" id="force_old_password" name="old_password" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập mật khẩu hiện tại' : 'Enter current password') ?>" required style="box-sizing:border-box; width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-input); color:var(--text-main);">
            </div>
            
            <div class="form-group" style="margin-bottom:15px; text-align:left; position:relative;">
                <label for="force_new_password" style="display:block; margin-bottom:6px; font-weight:600; color:var(--text-muted); font-size:13px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Mật khẩu mới' : 'New password') ?></label>
                <input type="password" id="force_new_password" name="new_password" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Tối thiểu 6 ký tự' : 'Minimum 6 characters') ?>" required style="box-sizing:border-box; width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-input); color:var(--text-main);">
                <small style="display:block; margin-top:5px; color:var(--text-muted); font-size:12px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? '🔒 Yêu cầu: Ít nhất 6 ký tự, gồm chữ hoa (A-Z), chữ thường (a-z), số (0-9) và ký tự đặc biệt (!@#...).' : '🔒 Required: At least 6 chars, incl. uppercase (A-Z), lowercase (a-z), numbers (0-9) and special chars (!@#...).') ?></small>
            </div>

            <div class="form-group" style="margin-bottom:20px; text-align:left; position:relative;">
                <label for="force_confirm_password" style="display:block; margin-bottom:6px; font-weight:600; color:var(--text-muted); font-size:13px;"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xác nhận mật khẩu mới' : 'Confirm new password') ?></label>
                <input type="password" id="force_confirm_password" name="confirm_password" class="win-input" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập lại mật khẩu mới' : 'Re-enter new password') ?>" required style="box-sizing:border-box; width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border-color); background:var(--bg-input); color:var(--text-main);">
            </div>
            
            <button type="submit" class="win-btn" style="width:100%; padding:13px; font-weight:bold; background:#005fba; color: #ffffff !important; border:none; border-radius:8px; cursor:pointer; font-size:15px; display:flex; align-items:center; justify-content:center; gap:8px;">
                <i class="fas fa-save" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'LƯU VÀ TIẾP TỤC' : 'SAVE AND CONTINUE') ?>
            </button>
        </form>
        <div style="text-align:center; margin-top:16px;">
            <a href="logout.php" style="color:var(--text-muted); font-size:13px; text-decoration:none; display:inline-flex; align-items:center; gap:6px;"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đăng xuất tài khoản' : 'Logout') ?></a>
        </div>
    </div>
</div>

<script>
async function submitForceChangePass(e) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    var oldText = btn.innerHTML;
    var newPass = document.getElementById('force_new_password').value;
    var confirmPass = document.getElementById('force_confirm_password').value;

    if (newPass !== confirmPass) {
        if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu xác nhận không khớp!", style: { background: "#ef4444" } }).showToast();
        else alert("Mật khẩu xác nhận không khớp!");
        return;
    }
    if (newPass === '123456') {
        if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu mới không được là 123456!", style: { background: "#ef4444" } }).showToast();
        else alert("Mật khẩu mới không được là 123456!");
        return;
    }
    if (newPass.length < 6) {
        if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải có ít nhất 6 ký tự!", style: { background: "#ef4444" } }).showToast();
        else alert("Mật khẩu phải có ít nhất 6 ký tự!");
        return;
    }
    if (!/[A-Z]/.test(newPass)) {
        if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải chứa ít nhất 1 chữ cái viết hoa (A-Z)!", style: { background: "#ef4444" } }).showToast();
        else alert("Mật khẩu phải chứa ít nhất 1 chữ cái viết hoa (A-Z)!");
        return;
    }
    if (!/[a-z]/.test(newPass)) {
        if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải chứa ít nhất 1 chữ cái viết thường (a-z)!", style: { background: "#ef4444" } }).showToast();
        else alert("Mật khẩu phải chứa ít nhất 1 chữ cái viết thường (a-z)!");
        return;
    }
    if (!/[0-9]/.test(newPass)) {
        if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải chứa ít nhất 1 chữ số (0-9)!", style: { background: "#ef4444" } }).showToast();
        else alert("Mật khẩu phải chứa ít nhất 1 chữ số (0-9)!");
        return;
    }
    if (!/[^A-Za-z0-9]/.test(newPass)) {
        if(typeof Toastify !== 'undefined') Toastify({ text: "❌ Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt (!@#...)!", style: { background: "#ef4444" } }).showToast();
        else alert("Mật khẩu phải chứa ít nhất 1 ký tự đặc biệt (!@#...)!");
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Đang xử lý...';

    try {
        var response = await fetch('change_password.php', { method: 'POST', body: new FormData(form) });
        var data = await response.json();
        if (data.status === 'success') {
            if(typeof Toastify !== 'undefined') Toastify({ text: "✅ " + data.msg, style: { background: "#10b981" } }).showToast();
            setTimeout(function() { window.location.reload(); }, 800);
        } else {
            if(typeof Toastify !== 'undefined') Toastify({ text: "❌ " + data.msg, style: { background: "#ef4444" } }).showToast();
            else alert(data.msg);
            btn.disabled = false;
            btn.innerHTML = oldText;
        }
    } catch (error) {
        alert("Lỗi kết nối server!");
        btn.disabled = false;
        btn.innerHTML = oldText;
    }
}
</script>
<?php endif; ?>
</body>
</html>