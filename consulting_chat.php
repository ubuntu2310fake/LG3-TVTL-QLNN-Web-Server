<?php
// File: consulting_chat.php - LOCAL PHP CHAT & FRIENDSHIP SERVICE
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
require_once 'includes/config.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Vui lòng đăng nhập' : 'Login required')]);
    exit;
}

$my_id = (int)$_SESSION['user']['id'];
$my_role = strtolower($_SESSION['user']['role']);
$my_username = $_SESSION['user']['username'];
$my_fullname = $_SESSION['user']['full_name'];

$endpoint = $_GET['endpoint'] ?? '';

// Check frozen school year
$current_school_year = $pdo->query("SELECT value FROM config WHERE `key` = 'current_school_year'")->fetchColumn() ?: '2026-2027';

// Auto-migrate is_anonymous column in psychology_messages table
try {
    $pdo->exec("ALTER TABLE psychology_messages ADD COLUMN is_anonymous TINYINT(1) DEFAULT 0");
} catch (Exception $e) {}

function analyze_chat_risk($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $keywords_danger = ["tự tử", "chết", "giết", "không muốn sống", "nhảy lầu", "cắt tay", "uống thuốc", "kết thúc", "vĩnh biệt", "tuyệt vọng", "bắt nạt", "có thai"];
    $keywords_warning = ["căng thẳng", "áp lực", "stress", "mệt mỏi", "buồn", "chán", "lo lắng", "tự ti", "thất vọng", "cô đơn", "khóc"];
    
    foreach ($keywords_danger as $kw) {
        if (mb_strpos($text, $kw) !== false) return 'DANGER';
    }
    foreach ($keywords_warning as $kw) {
        if (mb_strpos($text, $kw) !== false) return 'WARNING';
    }
    return 'NORMAL';
}

// 1. GET CONVERSATIONS FOR TEACHER
if ($endpoint === '/api/teacher/get_conversations') {
    if (!in_array($my_role, ['teacher', 'admin'])) {
        echo json_encode([]);
        exit;
    }
    
    // Lấy tất cả các học sinh mà giáo viên này đã từng chat
    $stmt = $pdo->prepare("
        SELECT u.id as partner_id, u.full_name as partner_name, u.avatar, u.role as partner_role, u.last_active, latest.last_msg_time, latest.is_anon_latest
        FROM users u
        JOIN (
            SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as user_id, 
                   MAX(created_at) as last_msg_time,
                   MAX(is_anonymous) as is_anon_latest
            FROM psychology_messages 
            WHERE sender_id = ? OR receiver_id = ? 
            GROUP BY user_id
        ) latest ON u.id = latest.user_id 
        ORDER BY latest.last_msg_time DESC
    ");
    $stmt->execute([$my_id, $my_id, $my_id]);
    $convs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($convs as &$c) {
        if ($c['last_msg_time']) {
            $dt = new DateTime($c['last_msg_time']);
            $c['last_msg_time'] = $dt->format('Y-m-dT%H:%M:%S+07:00');
        }
        if (!empty($c['is_anon_latest'])) {
            $c['partner_name'] = (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh ẩn danh 🛡️' : 'Anonymous Student 🛡️');
            $c['avatar'] = 'static/default.png';
        }
    }
    
    echo json_encode($convs);
    exit;
}

// 2. LIST TEACHERS (For student chat widget)
if ($endpoint === '/api/list_teachers') {
    $stmt = $pdo->query("SELECT id, username, full_name, avatar FROM users WHERE role IN ('TEACHER', 'ADMIN', 'teacher', 'admin')");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($teachers);
    exit;
}

// 3. FRIENDS LIST
if ($endpoint === '/api/friends/list') {
    // Friends (accepted status)
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.avatar, u.last_active 
        FROM users u
        JOIN friendships f ON (f.user_id_1 = u.id OR f.user_id_2 = u.id)
        WHERE (f.user_id_1 = ? OR f.user_id_2 = ?) 
        AND f.status = 'accepted' 
        AND u.id != ?
    ");
    $stmt->execute([$my_id, $my_id, $my_id]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pending requests incoming (target is current user)
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.avatar, f.id as req_id
        FROM users u
        JOIN friendships f ON f.user_id_1 = u.id
        WHERE f.user_id_2 = ? AND f.status = 'pending'
    ");
    $stmt->execute([$my_id]);
    $requests_pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sent requests outgoing
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, u.avatar
        FROM users u
        JOIN friendships f ON f.user_id_2 = u.id
        WHERE f.user_id_1 = ? AND f.status = 'pending'
    ");
    $stmt->execute([$my_id]);
    $sent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'friends' => $friends,
        'requests' => $requests_pending,
        'sent' => $sent_requests
    ]);
    exit;
}

// 4. FRIENDS SEARCH
if ($endpoint === '/api/friends/search') {
    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $query = trim($inputData['query'] ?? '');
    
    if (strlen($query) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT id, username, full_name, avatar 
        FROM users 
        WHERE role NOT IN ('TEACHER', 'ADMIN', 'teacher', 'admin')
        AND id != ?
        AND (username LIKE ? OR full_name LIKE ?)
        LIMIT 50
    ");
    $stmt->execute([$my_id, "%$query%", "%$query%"]);
    $search_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $final_results = [];
    foreach ($search_users as $u) {
        $uid = (int)$u['id'];
        
        $stmt_rel = $pdo->prepare("
            SELECT user_id_1, status FROM friendships 
            WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)
        ");
        $stmt_rel->execute([$my_id, $uid, $uid, $my_id]);
        $rel = $stmt_rel->fetch();
        
        $relation = 'none';
        if ($rel) {
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
        
        $final_results[] = [
            'id' => $uid,
            'username' => $u['username'],
            'full_name' => $u['full_name'],
            'avatar' => $u['avatar'] ?: 'static/default.png',
            'relation' => $relation
        ];
    }
    
    echo json_encode($final_results);
    exit;
}

// 5. FRIEND REQUEST
if ($endpoint === '/api/friends/request') {

    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $target_id = $inputData['target_id'] ?? null;
    
    if (!$target_id) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thiếu target_id' : 'Missing target_id')]);
        exit;
    }
    
    // Check trùng
    $stmt = $pdo->prepare("SELECT id FROM friendships WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)");
    $stmt->execute([$my_id, $target_id, $target_id, $my_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Đã có yêu cầu trước đó' : 'Request already exists')]);
        exit;
    }
    
    $stmt_insert = $pdo->prepare("INSERT INTO friendships (user_id_1, user_id_2, status) VALUES (?, ?, 'pending')");
    $stmt_insert->execute([$my_id, $target_id]);
    
    // Push notification
    $stmt_user = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt_user->execute([$target_id]);
    $u = $stmt_user->fetch();
    $rcv_username = $u ? $u['username'] : null;
    
    try {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $push_url = $protocol . "://127.0.0.1/api_receive_push.php";
        $secret = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : "khoa_bi_mat_ket_noi_hai_app_123456_secure";
        
        $ch_push = curl_init($push_url);
        curl_setopt($ch_push, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_push, CURLOPT_POST, true);
        curl_setopt($ch_push, CURLOPT_POSTFIELDS, json_encode([
            'type' => 'FRIEND_REQUEST',
            'sender_name' => $my_fullname,
            'receiver_id' => $target_id,
            'receiver_username' => $rcv_username,
            'url' => '/?view=chat'
        ]));
        curl_setopt($ch_push, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $secret
        ]);
        curl_setopt($ch_push, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch_push, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch_push);
        curl_close($ch_push);
    } catch (Exception $e) {}
    
    echo json_encode(['success' => true]);
    exit;
}

// 6. FRIEND RESPOND
if ($endpoint === '/api/friends/respond') {

    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $rid = $inputData['req_id'] ?? null;
    $action = $inputData['action'] ?? '';
    
    if (!$rid) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thiếu req_id' : 'Missing req_id')]);
        exit;
    }
    
    if ($action === 'accept') {
        $stmt = $pdo->prepare("UPDATE friendships SET status = 'accepted' WHERE id = ? AND user_id_2 = ?");
        $stmt->execute([$rid, $my_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM friendships WHERE id = ? AND user_id_2 = ?");
        $stmt->execute([$rid, $my_id]);
    }
    
    echo json_encode(['success' => true]);
    exit;
}

// 6.5. CANCEL FRIEND REQUEST
if ($endpoint === '/api/friends/cancel') {
    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $target_id = (int)($inputData['target_id'] ?? 0);
    if ($target_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM friendships WHERE user_id_1 = ? AND user_id_2 = ? AND status = 'pending'");
        $stmt->execute([$my_id, $target_id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// 6.6. UNFRIEND
if ($endpoint === '/api/friends/unfriend') {
    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $target_id = (int)($inputData['target_id'] ?? 0);
    if ($target_id > 0) {
        $stmt = $pdo->prepare("DELETE FROM friendships WHERE ((user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?))");
        $stmt->execute([$my_id, $target_id, $target_id, $my_id]);
    }
    echo json_encode(['success' => true]);
    exit;
}

// 7. GET MESSAGES
if ($endpoint === '/api/chat/get') {
    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $pid = $inputData['partner_id'] ?? null;
    
    if (!$pid) {
        echo json_encode([]);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT m1.*, m2.content as reply_content, m2.sender_id as reply_sender_id 
        FROM psychology_messages m1
        LEFT JOIN psychology_messages m2 ON m1.reply_id = m2.id
        WHERE (m1.sender_id = ? AND m1.receiver_id = ?) 
           OR (m1.sender_id = ? AND m1.receiver_id = ?) 
        ORDER BY m1.created_at ASC
    ");
    $stmt->execute([$my_id, $pid, $pid, $my_id]);
    $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($msgs as &$m) {
        if ($m['created_at']) {
            $dt = new DateTime($m['created_at']);
            $m['created_at'] = $dt->format('Y-m-dT%H:%M:%S+07:00');
        }
        if (!$m['reactions']) {
            $m['reactions'] = null;
        }
    }
    
    echo json_encode($msgs);
    exit;
}

// 8. SEND MESSAGE
if ($endpoint === '/api/chat/send') {

    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $rid = $inputData['receiver_id'] ?? null;
    $content = $inputData['content'] ?? '';
    $reply_id = $inputData['reply_id'] ?? null;
    $is_anonymous = !empty($inputData['is_anonymous']) ? 1 : 0;
    
    if (!$rid || empty($content)) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thiếu receiver_id hoặc content' : 'Missing receiver_id or content')]);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO psychology_messages (sender_id, receiver_id, content, reply_id, is_anonymous) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$my_id, $rid, $content, $reply_id, $is_anonymous]);
    
    // Gửi Push Notification CHAT cho người nhận tin nhắn
    require_once __DIR__ . '/includes/push_helper.php';
    enqueueNotification($pdo, 'CHAT', [
        'receiver_id' => (int)$rid,
        'sender_id'   => (int)$my_id,
        'sender_name' => $is_anonymous ? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh ẩn danh 🛡️' : 'Anonymous Student 🛡️') : $my_fullname,
        'content'     => $content,
        'url'         => '/consulting_dashboard.php'
    ]);

    // Risk scanning & push notification (only for students, i.e. not teacher/admin)
    if (!in_array($my_role, ['teacher', 'admin'])) {
        $risk = analyze_chat_risk($content);
        if ($risk === 'DANGER' || $risk === 'WARNING') {
            try {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $push_url = $protocol . "://127.0.0.1/api_receive_push.php";
                $secret = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : "khoa_bi_mat_ket_noi_hai_app_123456_secure";
                
                $ch_push = curl_init($push_url);
                curl_setopt($ch_push, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch_push, CURLOPT_POST, true);
                curl_setopt($ch_push, CURLOPT_POSTFIELDS, json_encode([
                    'type' => 'PSYCHOLOGY',
                    'student_code' => $is_anonymous ? 'ANONYMOUS' : $my_username,
                    'student_name' => $is_anonymous ? (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Học sinh ẩn danh 🛡️' : 'Anonymous Student 🛡️') : $my_fullname,
                    'risk_level' => $risk,
                    'message' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? '[TRONG KHUNG CHAT]: ' : '[IN CHAT]: ') . $content
                ]));
                curl_setopt($ch_push, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $secret
                ]);
                curl_setopt($ch_push, CURLOPT_TIMEOUT, 2);
                curl_setopt($ch_push, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch_push);
                curl_close($ch_push);
            } catch (Exception $e) {}
        }
    }
    
    echo json_encode(['success' => true]);
    exit;
}

// 9. REACT TO MESSAGE
if ($endpoint === '/api/chat/react') {

    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $msg_id = $inputData['message_id'] ?? null;
    $emoji = $inputData['emoji'] ?? null;
    
    if (!$msg_id) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thiếu message_id' : 'Missing message_id')]);
        exit;
    }
    
    if (empty($emoji)) {
        $emoji = null;
    }
    
    $stmt = $pdo->prepare("UPDATE psychology_messages SET reactions = ? WHERE id = ?");
    $stmt->execute([$emoji, $msg_id]);
    
    echo json_encode(['success' => true]);
    exit;
}

// 10. DELETE MESSAGE
if ($endpoint === '/api/chat/delete') {

    $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
    $msg_id = $inputData['message_id'] ?? null;
    
    if (!$msg_id) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Thiếu message_id' : 'Missing message_id')]);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM psychology_messages WHERE id = ? AND sender_id = ?");
    $stmt->execute([$msg_id, $my_id]);
    
    echo json_encode(['success' => $stmt->rowCount() > 0]);
    exit;
}

// 11. UPLOAD CHAT IMAGE
if ($endpoint === '/api/chat/upload') {

    
    if (empty($_FILES['file'])) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không có file' : 'No file')]);
        exit;
    }
    
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Lỗi upload file' : 'Upload error')]);
        exit;
    }
    
    $allowed_exts = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_exts)) {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'File không hợp lệ' : 'Invalid file')]);
        exit;
    }
    
    $upload_dir = __DIR__ . '/static/uploads/chat';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filename = uniqid() . '.' . $ext;
    $dest = $upload_dir . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        // QUAN TRỌNG: Chỉ trả về URL gốc tĩnh, KHÔNG bọc [IMG]: ở đây
        // Vì JavaScript frontend sẽ tự bọc [IMG]: khi lưu tin nhắn vào DB
        $url = '/static/uploads/chat/' . $filename;
        echo json_encode(['success' => true, 'url' => $url]);
    } else {
        echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Không thể lưu file' : 'Cannot save file')]);
    }
    exit;
}

echo json_encode(['success' => false, 'msg' => (($_SESSION['lang'] ?? 'vi') === 'vi' ? 'Endpoint không hợp lệ' : 'Invalid endpoint')]);
exit;
