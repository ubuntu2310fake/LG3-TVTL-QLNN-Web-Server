<?php 
// 1. NGĂN CHẶN CACHE TỪ TRÌNH DUYỆT ĐIỆN THOẠI
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['ADMIN', 'TEACHER'])) {
    require_once 'includes/header.php'; 
    echo "<div class='alert alert-danger m-5 text-center'>" . (($_SESSION['lang'] ?? 'vi') === 'vi' ? '⛔ Bạn không có quyền truy cập trang quản lý tư vấn.' : '⛔ You do not have permission to access the consulting management page.') . "</div>";
    require_once 'includes/footer.php';
    exit;
}

// 2. TẠO TOKEN JWT BỌC THÉP
function generate_sso_token($user) {
    $secret = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : (getenv('SSO_SECRET_KEY') ?: "khoa_bi_mat_ket_noi_hai_app_123456_secure"); 
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'];
    $avatar = $user['avatar'] ?? 'static/default.png';
    if ($avatar && strpos($avatar, 'http') === false) $avatar = $base_url . "/" . ltrim($avatar, '/');
    $payload = json_encode([
        'sbd' => $user['username'] ?? 'unknown', 
        'name' => $user['full_name'] ?? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Khách' : 'Guest'), 
        'role' => 'teacher', 
        'avatar' => $avatar, 
        'exp' => time() + 900
    ]);
    $b64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $b64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $b64Header . "." . $b64Payload, $secret, true);
    return $b64Header . "." . $b64Payload . "." . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
}
$jwt_token = generate_sso_token($_SESSION['user'] ?? []);

if (isset($_GET['local_api'])) {
    require_once 'consulting_chat.php';
    exit;
}

require_once 'includes/header.php'; 
?>

<style>
    /* =========================================
       FIX TOÀN BỘ SANG BIẾN CSS (AMOLED CLEAN)
       ========================================= */
    .db-layout { display: flex; width: 100%; height: calc(100vh - 80px); background: var(--bg-card); overflow: hidden; position: relative; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .db-sidebar { width: 320px; background: var(--bg-card); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; z-index: 10; transition: all 0.3s; }
    
    .db-chat-area { flex: 1; display: flex; flex-direction: column; background: var(--bg-body); position: relative; z-index: 5; }
    .db-empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); background: var(--bg-body); }
    .db-empty-state i { font-size: 60px; margin-bottom: 15px; opacity: 0.5; color: var(--primary-color); }
    
    .db-chat-content { display: none; flex-direction: column; height: 100%; width: 100%; background: var(--bg-body); }
    .db-chat-content.active { display: flex; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    .user-list { flex: 1; overflow-y: auto; padding: 10px; }
    .user-item { padding: 12px 15px; display: flex; align-items: center; cursor: pointer; border-radius: 10px; transition: 0.2s; margin-bottom: 5px; color: var(--text-main); }
    .user-item:hover { background: var(--bg-hover); }
    .user-item.active { background: rgba(0, 132, 255, 0.1); border-left: 3px solid var(--primary-color); }
    .user-item img { width: 45px; height: 45px; border-radius: 50%; margin-right: 12px; object-fit: cover; border: 1px solid var(--border-color); }
    
    .db-chat-header { height: 60px; background: var(--bg-card); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; padding: 0 15px; z-index: 10; color: var(--text-main); }
    .db-btn-back { margin-right: 10px; background: transparent; border: none; font-size: 18px; color: inherit; cursor: pointer; display: none; }
    @media (max-width: 768px) { .db-sidebar { width: 100%; position: absolute; height: 100%; } .db-chat-area { width: 100%; position: absolute; height: 100%; transform: translateX(100%); transition: transform 0.4s; z-index: 20; } .db-chat-area.active { transform: translateX(0); } .db-btn-back { display: block; } }

    .chat-msgs { flex: 1; overflow-y: auto; padding: 15px; display: flex; flex-direction: column; gap: 8px; }
    .msg-row { display: flex; width: 100%; position: relative; margin-bottom: 12px; }
    .msg-me { justify-content: flex-end; } .msg-other { justify-content: flex-start; }
    
    .bubble { max-width: 75%; padding: 10px 14px; border-radius: 18px; font-size: 15px; line-height: 1.4; position: relative; cursor: pointer; word-wrap: break-word; user-select: none; transition: transform 0.1s ease; }
    .bubble:active { transform: scale(0.98); }
    
    .msg-me .bubble { background: var(--primary-color); color: white; border-bottom-right-radius: 4px; }
    .msg-other .bubble { background: var(--bg-input); color: var(--text-main); border-bottom-left-radius: 4px; border: 1px solid var(--border-color); }

    .reply-quote { font-size: 12px; opacity: 0.85; background: rgba(0,0,0,0.05); padding: 5px 8px; border-radius: 8px; margin-bottom: 5px; border-left: 3px solid rgba(0,0,0,0.2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .msg-me .reply-quote { background: rgba(255,255,255,0.15); border-left-color: #fff; }

    .reaction-badge { position: absolute; bottom: -10px; right: 2px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 1px 5px; font-size: 13px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); z-index: 2; color: var(--text-main); }
    .msg-me .reaction-badge { right: auto; left: 2px; }

    .input-wrapper { background: var(--bg-card); border-top: 1px solid var(--border-color); padding: 5px 0; }
    .reply-preview { display: none; padding: 8px 15px; background: var(--bg-hover); border-bottom: 1px solid var(--border-color); font-size: 13px; color: var(--text-muted); align-items: center; justify-content: space-between; }
    .chat-input { padding: 8px 15px; display: flex; gap: 10px; align-items: center; }
    .chat-input input { flex: 1; padding: 10px 18px; border-radius: 22px; border: 1px solid var(--border-color); background: var(--bg-input); outline: none; font-size: 15px; color: var(--text-main); font-family: inherit; }
    .btn-send-chat { color: var(--primary-color); background: none; border: none; font-size: 22px; cursor: pointer; transition: 0.2s; }
    .btn-send-chat:hover { transform: scale(1.1); }

    /* MENU NGỮ CẢNH NỔI */
    .tvtl-custom-menu-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; z-index: 3000; background: rgba(0,0,0,0.1); }
    .tvtl-custom-menu { position: absolute; background: var(--bg-card); border-radius: 12px; width: 240px; box-shadow: 0 8px 30px rgba(0,0,0,0.2); overflow: hidden; border: 1px solid var(--border-color); animation: slideUpMenu 0.15s ease-out; }
    @keyframes slideUpMenu { from { transform: translateY(10px); opacity:0; } to { transform: translateY(0); opacity:1; } }
    .tvtl-reaction-bar { display: flex; justify-content: space-evenly; padding: 10px; border-bottom: 1px solid var(--border-color); }
    .tvtl-reaction-emoji { font-size: 24px; cursor: pointer; transition: transform 0.2s; }
    .tvtl-reaction-emoji:hover { transform: scale(1.3); }
    .tvtl-menu-item { padding: 12px 18px; font-size: 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; color: var(--text-main); }
    .tvtl-menu-item:hover { background: var(--bg-hover); }
    .tvtl-menu-item.text-danger { color: var(--danger-color); }

    /* ÉP MÀU CHỮ CỦA GIÁO VIÊN TRÊN AMOLED */
    html[data-theme="dark"] body .msg-me .bubble {
        color: #000000;
        font-weight: 600;
    }
</style>

<div id="db-menu-overlay" class="tvtl-custom-menu-overlay" onclick="TeacherChatApp.hideMenu()">
    <div id="db-custom-menu" class="tvtl-custom-menu" onclick="event.stopPropagation()">
        <div class="tvtl-reaction-bar">
            <span class="tvtl-reaction-emoji" onclick="TeacherChatApp.react('❤️')">❤️</span>
            <span class="tvtl-reaction-emoji" onclick="TeacherChatApp.react('😆')">😆</span>
            <span class="tvtl-reaction-emoji" onclick="TeacherChatApp.react('😮')">😮</span>
            <span class="tvtl-reaction-emoji" onclick="TeacherChatApp.react('😢')">😢</span>
            <span class="tvtl-reaction-emoji" onclick="TeacherChatApp.react('😡')">😡</span>
            <span class="tvtl-reaction-emoji" onclick="TeacherChatApp.react('👍')">👍</span>
            <span class="tvtl-reaction-emoji" onclick="TeacherChatApp.react('')">❌</span>
        </div>
        <div class="menu-list">
            <div class="tvtl-menu-item" onclick="TeacherChatApp.prepReply()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trả lời' : 'Reply') ?> <i class="fa-solid fa-reply"></i></div>
            <div class="tvtl-menu-item" onclick="TeacherChatApp.copyTxt()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Sao chép' : 'Copy') ?> <i class="fa-solid fa-copy"></i></div>
            <div id="db-btn-delete" class="tvtl-menu-item text-danger" onclick="TeacherChatApp.delMsg()"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Xóa' : 'Delete') ?> <i class="fa-solid fa-trash"></i></div>
        </div>
    </div>
</div>

<div class="db-layout page-transition">
    <div class="db-sidebar">
        <div style="padding: 15px; font-weight: 700; font-size: 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; color: var(--text-main);"><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trò chuyện' : 'Chats') ?></div>
        <div class="user-list" id="db-user-list">
            <div style="text-align: center; color: var(--text-muted); padding: 20px;"><i class="fas fa-spinner fa-spin"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đang tải...' : 'Loading...') ?></div>
        </div>
    </div>
    
    <div class="db-chat-area" id="db-chat-area">
        <div id="db-empty-state" class="db-empty-state">
            <i class="fa-brands fa-facebook-messenger"></i>
            <p><?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Chọn một học sinh để bắt đầu trò chuyện' : 'Select a student to start chatting') ?></p>
        </div>

        <div id="db-chat-content" class="db-chat-content">
            <div class="db-chat-header">
                <button class="db-btn-back" onclick="TeacherChatApp.closeRoom()"><i class="fa-solid fa-arrow-left"></i></button>
                <img id="db-room-ava" src="static/default.png" style="width:38px; height:38px; border-radius:50%; margin-right:10px; object-fit: cover;">
                <b id="db-room-name" style="font-size: 16px;">...</b>
            </div>
            
            <div class="chat-msgs" id="db-msg-box"></div>
            
            <div class="input-wrapper">
                <div id="db-reply-preview" class="reply-preview">
                    <div><i class="fa-solid fa-reply"></i> <?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Trả lời' : 'Replying to') ?> <b id="db-reply-name">...</b></div>
                    <i class="fa-solid fa-xmark" style="cursor:pointer; padding:5px; font-size: 16px;" onclick="TeacherChatApp.cancelReply()"></i>
                </div>
                
                <div class="chat-input">
                    <label for="db-img-input" style="cursor:pointer; padding:0 5px; color:var(--primary-color);"><i class="fa-solid fa-image" style="font-size:22px;"></i></label>
                    <input type="file" id="db-img-input" accept="image/*" style="display:none;" onchange="TeacherChatApp.uploadImg(this)">
                    <input type="text" id="db-inp-msg" placeholder="<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Nhập tin nhắn...' : 'Type a message...') ?>" autocomplete="off" onkeypress="if(event.key==='Enter') TeacherChatApp.sendMsg()">
                    <button class="btn-send-chat" onclick="TeacherChatApp.sendMsg()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.TeacherChatApp = (function() {
    let partnerId = null;
    let selectedMsgId = null;
    let replyingToId = null;
    let pollingTimer = null;  // contacts sidebar
    let chatSSE = null;       // SSE connection for messages
    const pythonBase = '';

    async function reqPy(endpoint, bodyData = null, isMultipart = false) {
        let opts = { method: bodyData ? 'POST' : 'GET' };
        if (bodyData) {
            if (isMultipart) { opts.body = bodyData; } 
            else { opts.headers = { 'Content-Type': 'application/json' }; opts.body = JSON.stringify(bodyData); }
        }
        try { const res = await fetch(`?local_api=1&endpoint=${encodeURIComponent(endpoint)}`, opts); return await res.json(); } catch(e) { return null; }
    }

    async function loadContacts() {
        const users = await reqPy('/api/teacher/get_conversations');
        const list = document.getElementById('db-user-list');
        let h = '';
        if(users && users.length > 0) {
            users.forEach(u => {
let active = (u.partner_id == partnerId) ? 'active' : '';
                let ava = u.avatar || 'static/default.png';
                if (!ava.startsWith('http')) ava = pythonBase + ava;
                
                let isUnread = u.unread_count > 0;
                let fw = isUnread ? '800' : 'normal';
                let colorName = isUnread ? 'var(--text-main)' : 'var(--text-main)';
                let colorMsg = isUnread ? 'var(--primary-color)' : 'var(--text-muted)';
                let msgPrefix = u.last_msg_sender_id == <?= $_SESSION['user']['id'] ?> ? (window.LANG&&window.LANG.you_prefix || 'Bạn: ') : '';
                let lastMsg = u.last_msg_content || (window.LANG&&window.LANG.click_to_chat || 'Bấm để trò chuyện');
                if (lastMsg.length > 25) lastMsg = lastMsg.substring(0, 25) + '...';
                lastMsg = lastMsg.replace(/</g, '&lt;');

                h += `<div class="user-item ${active}" onclick="TeacherChatApp.openRoom(${u.partner_id}, '${u.partner_name}', '${ava}')">
                        <div style="position:relative;">
                            <img src="${ava}">
                            ${isUnread ? '<div style="position:absolute; bottom:2px; right:12px; width:12px; height:12px; background:var(--primary-color); border-radius:50%; border:2px solid var(--bg-card);"></div>' : ''}
                        </div>
                        <div style="flex:1; overflow:hidden;">
                            <b style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:${fw}; color:${colorName};">${u.partner_name}</b>
                            <small style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:${fw}; color:${colorMsg}; opacity: ${isUnread ? '1' : '0.7'};">${msgPrefix}${lastMsg}</small>
                        </div>
                      </div>`;
            });
        } else { h = '<div style="text-align: center; padding: 20px; color: var(--text-muted);">' + (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Không có học sinh nào.'" : "'No students.'") ?>) + '</div>'; }
        if(list.innerHTML !== h) list.innerHTML = h;
    }

async function loadMessages(forceScroll = false) {
        if(!partnerId) return;
        const msgs = await reqPy('/api/chat/get', { partner_id: partnerId });
        const box = document.getElementById('db-msg-box');
        
        // KHI HỌC SINH BẬT ẨN DANH VÀ XÓA LỊCH SỬ CHAT, ĐẨY GIÁO VIÊN RA NGOÀI MÀN HÌNH CHỜ
        if (!msgs || msgs.length === 0) {
            TeacherChatApp.closeRoom();
            loadConversations();
            return;
        }

        let html = '';
        if (msgs) {
            msgs.forEach(m => {
                let isMe = (m.sender_id != partnerId);
                let cls = isMe ? 'msg-me' : 'msg-other';
                let cRaw = m.content || '';
                let cShow = cRaw;

                if (cRaw.startsWith('[IMG]:')) {
                    cShow = `<img src="${pythonBase}${cRaw.replace('[IMG]:','')}" style="max-width:220px; border-radius:12px; display:block; cursor:pointer;" onclick="window.open(this.src,'_blank')">`;
                    cRaw = "[" + (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Hình ảnh'" : "'Image'") ?>) + "]";
                } else { cShow = cShow.replace(/</g, "&lt;"); }

                let rHtml = '';
                if (m.reply_id && m.reply_content) {
                    let rC = m.reply_content.startsWith('[IMG]:') ? '[' + (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Hình ảnh'" : "'Image'") ?>) + ']' : m.reply_content.replace(/</g, "&lt;");
                    rHtml = `<div class="reply-quote"><i class="fa-solid fa-reply"></i> ${rC}</div>`;
                }

                let rctHtml = m.reactions ? `<div class="reaction-badge">${m.reactions}</div>` : '';
                let anonHeader = (!isMe && m.is_anonymous == 1) ? `<div style="font-size:11px; font-weight:bold; color:#8b5cf6; margin-bottom:3px;"><i class="fa-solid fa-user-secret"></i> ${<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Học sinh ẩn danh 🛡️'" : "'Anonymous Student 🛡️'") ?>}</div>` : '';

                html += `
                <div class="msg-row ${cls}">
                    <div class="bubble" data-msg-id="${m.id}"
                         oncontextmenu="TeacherChatApp.showMenu(event, ${m.id}, ${isMe}, '${cRaw.replace(/'/g, "\\'")}'); return false;"
                         ontouchstart="window.longPressTimer = setTimeout(() => TeacherChatApp.showMenu(event, ${m.id}, ${isMe}, '${cRaw.replace(/'/g, "\\'")}'), 500)"
                         ontouchend="clearTimeout(window.longPressTimer)">
                        ${anonHeader}
                        ${rHtml}
                        ${cShow}
                        ${rctHtml}
                    </div>
                </div>`;
            });
        }

        if(box.innerHTML !== html) {
            box.innerHTML = html;
            if(forceScroll) setTimeout(() => box.scrollTo({top: box.scrollHeight, behavior: 'smooth'}), 50);
        }
    }

    return {
        init: function() {
            loadContacts();
            // Contacts sidebar: làm mới nhẹ mỗi 5 giây
            pollingTimer = setInterval(() => loadContacts(), 5000);
        },
        destroy: function() {
            if (pollingTimer) clearInterval(pollingTimer);
            if (chatSSE) { chatSSE.close(); chatSSE = null; }
        },
        openRoom: function(pid, name, ava) {
            partnerId = pid;
            document.getElementById('db-empty-state').style.display = 'none';
            document.getElementById('db-chat-content').classList.add('active');
            document.getElementById('db-chat-area').classList.add('active');
            document.getElementById('db-room-name').innerText = name;
            document.getElementById('db-room-ava').src = ava;
            this.cancelReply();

            // Load lịch sử tin nhắn ngược lại lần đầu
            loadMessages(true);
            loadContacts();

            // Đóng SSE cũ nếu đang mở phòng khác
            if (chatSSE) { chatSSE.close(); chatSSE = null; }

            // Kết nối SSE mới cho phòng chat này
            chatSSE = new EventSource(`api/chat_sse.php?partner_id=${pid}`);

            chatSSE.addEventListener('new_message', function(e) {
                const msg = JSON.parse(e.data);
                const box = document.getElementById('db-msg-box');
                if (!box) return;

                const isMe = (msg.sender_id != pid);
                const cls  = isMe ? 'msg-me' : 'msg-other';

                let cRaw  = msg.content || '';
                let cShow = cRaw;
                if (cRaw.startsWith('[IMG]:')) {
                    const src = pythonBase + cRaw.replace('[IMG]:', '');
                    cShow = `<img src="${src}" style="max-width:220px;border-radius:12px;display:block;cursor:pointer" onclick="window.open(this.src,'_blank')">`;
                } else {
                    cShow = cShow.replace(/</g, '&lt;');
                }

                let rHtml = '';
                if (msg.reply_id && msg.reply_content) {
                    const rC = msg.reply_content.startsWith('[IMG]:') ? '[Hình ảnh]' : msg.reply_content.replace(/</g,'&lt;');
                    rHtml = `<div class="reply-quote"><i class="fa-solid fa-reply"></i> ${rC}</div>`;
                }

                const rctHtml  = msg.reactions ? `<div class="reaction-badge">${msg.reactions}</div>` : '';
                const anonHdr  = (!isMe && msg.is_anonymous == 1)
                    ? `<div style="font-size:11px;font-weight:bold;color:#8b5cf6;margin-bottom:3px"><i class="fa-solid fa-user-secret"></i> Học sinh ẩn danh 🛡️</div>`
                    : '';
                const cRawEsc  = cRaw.replace(/'/g, "\\'");

                // Tránh render trùng nếu đã có trong loadMessages
                if (box.querySelector(`[data-msg-id="${msg.id}"]`)) return;

                const div = document.createElement('div');
                div.className = `msg-row ${cls}`;
                div.innerHTML = `
                    <div class="bubble" data-msg-id="${msg.id}"
                         oncontextmenu="TeacherChatApp.showMenu(event,${msg.id},${isMe},'${cRawEsc}');return false;"
                         ontouchstart="window.longPressTimer=setTimeout(()=>TeacherChatApp.showMenu(event,${msg.id},${isMe},'${cRawEsc}'),500)"
                         ontouchend="clearTimeout(window.longPressTimer)">
                        ${anonHdr}${rHtml}${cShow}${rctHtml}
                    </div>`;
                box.appendChild(div);
                box.scrollTo({ top: box.scrollHeight, behavior: 'smooth' });

                // Làm mới danh sách sidebar
                loadContacts();
            });

            chatSSE.onerror = function() {
                // SSE tự reconnect, không cần xử lý thêm
            };
        },
        closeRoom: function() {
            // Đóng SSE khi rời phòng
            if (chatSSE) { chatSSE.close(); chatSSE = null; }
            partnerId = null;
            document.getElementById('db-chat-area').classList.remove('active');
            document.getElementById('db-chat-content').classList.remove('active');
            document.getElementById('db-empty-state').style.display = 'flex';
        },
        sendMsg: async function() {
            const inp = document.getElementById('db-inp-msg');
            const txt = inp.value.trim();
            if(!txt || !partnerId) return;
            inp.value = '';
            const rid = replyingToId;
            this.cancelReply();

            const box = document.getElementById('db-msg-box');
            box.innerHTML += `<div class="msg-row msg-me"><div class="bubble" style="opacity:0.6">${txt.replace(/</g, "&lt;")}</div></div>`;
            box.scrollTop = box.scrollHeight;

            let payload = { receiver_id: partnerId, content: txt };
            if (rid) payload.reply_id = parseInt(rid);
            await reqPy('/api/chat/send', payload);
            loadMessages(true);
        },
        uploadImg: async function(input) {
            const file = input.files[0];
            if(!file || !partnerId) return;
            const fd = new FormData(); fd.append('file', file);
            const data = await reqPy('/api/chat/upload', fd, true);
            if(data && data.success) {
                let payload = { receiver_id: partnerId, content: `[IMG]:${data.url}` };
                if (replyingToId) payload.reply_id = parseInt(replyingToId);
                await reqPy('/api/chat/send', payload);
                this.cancelReply();
                loadMessages(true);
            } else { alert(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Lỗi tải ảnh lên máy chủ'" : "'Error uploading image to server'") ?>); }
            input.value = '';
        },
        showMenu: function(e, msgId, isMe, content) {
            e.preventDefault();
            selectedMsgId = msgId;
            const menu = document.getElementById('db-custom-menu');
            menu.dataset.content = content; menu.dataset.isMe = isMe;
            document.getElementById('db-btn-delete').style.display = isMe ? 'flex' : 'none';
            document.getElementById('db-menu-overlay').style.display = 'block';
            let x = e.clientX || (e.touches && e.touches[0].clientX);
            let y = e.clientY || (e.touches && e.touches[0].clientY);
            if (x + 240 > window.innerWidth) x = window.innerWidth - 250;
            if (y + 250 > window.innerHeight) y = window.innerHeight - 260;
            menu.style.left = x + 'px'; menu.style.top = y + 'px';
        },
        hideMenu: function() { document.getElementById('db-menu-overlay').style.display = 'none'; },
        react: async function(emoji) {
            this.hideMenu();
            await reqPy('/api/chat/react', { message_id: parseInt(selectedMsgId), emoji: emoji });
            loadMessages(false);
        },
        prepReply: function() {
            this.hideMenu();
            const isMe = document.getElementById('db-custom-menu').dataset.isMe === 'true';
            replyingToId = selectedMsgId;
            document.getElementById('db-reply-preview').style.display = 'flex';
            document.getElementById('db-reply-name').innerText = isMe ? (<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'chính mình'" : "'yourself'") ?>) : document.getElementById('db-room-name').innerText;
            document.getElementById('db-inp-msg').focus();
        },
        cancelReply: function() {
            replyingToId = null;
            document.getElementById('db-reply-preview').style.display = 'none';
        },
        copyTxt: function() {
            navigator.clipboard.writeText(document.getElementById('db-custom-menu').dataset.content);
            this.hideMenu();
        },
        delMsg: async function() {
            this.hideMenu();
            if(!confirm(<?= (($_SESSION['lang'] ?? 'vi') === 'vi' ? "'Bạn muốn xóa tin nhắn này?'" : "'Do you want to delete this message?'") ?>)) return;
            await reqPy('/api/chat/delete', { message_id: parseInt(selectedMsgId) });
            loadMessages(false);
        }
    };
})();

window.pageInit = function() { window.TeacherChatApp.init(); };
window.pageDestroy = function() { window.TeacherChatApp.destroy(); };
</script>

<?php require_once 'includes/footer.php'; ?>