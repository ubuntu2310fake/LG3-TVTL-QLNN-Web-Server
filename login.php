<?php
// login.php
require_once 'includes/config.php';
require_once 'includes/totp.php';

// --- LẤY THÔNG TIN KẺ TẤN CÔNG ---
$client_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
if (strpos($client_ip, ',') !== false) {
    $client_ip = trim(explode(',', $client_ip)[0]);
}
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Không xác định';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_username = trim($_POST['username'] ?? '');
    
    if (preg_match('/[\'"#;\-]|(--)|(\bOR\b)|(\bUNION\b)|(\bSELECT\b)/i', $raw_username)) {
        $reason = __('sql_injection_attack', 'Cố tình tấn công SQL Injection với chuỗi: ') . substr($raw_username, 0, 30);
        $stmtBlock = $pdo->prepare("INSERT INTO banned_ips (ip_address, reason, banned_at, expires_at, user_agent) VALUES (?, ?, NOW(), NOW() + INTERVAL 1 MINUTE, ?)");
        $stmtBlock->execute([$client_ip, $reason, $user_agent]);
        header("Location: login.php");
        exit();
    }
}

// Kiểm tra IP có đang bị khóa không
$stmtBan = $pdo->prepare("SELECT expires_at, reason, user_agent FROM banned_ips WHERE ip_address = ? AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
$stmtBan->execute([$client_ip]);
$banned = $stmtBan->fetch(PDO::FETCH_ASSOC);

if ($banned) {
    http_response_code(403);
    $expire_timestamp = strtotime($banned['expires_at']) * 1000;
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <title><?= __('system_access_denied', 'Hệ thống - Cấm truy cập') ?></title>
        <link rel="stylesheet" href="static/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script>
            const savedMode = localStorage.getItem('theme_mode') || 'system';
            let effectiveTheme = savedMode;
            if (savedMode === 'system') effectiveTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            if (effectiveTheme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        </script>
    </head>
    <body style="display:flex; justify-content:center; align-items:center; height:100vh; background: var(--bg-body); padding: 20px;">
        <div class="win-card" style="max-width: 450px; width: 100%; text-align: center; border-top: 5px solid var(--danger-color); padding: 40px 25px;">
            <div style="width: 80px; height: 80px; background: rgba(217, 48, 37, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
                <i class="fas fa-shield-virus" style="font-size: 40px; color: var(--danger-color);"></i>
            </div>
            <h2 style="color: var(--danger-color); margin-bottom: 10px; font-size: 22px;"><?= __('connection_refused', 'KẾT NỐI BỊ TỪ CHỐI') ?></h2>
            <p style="color: var(--text-muted); font-size: 14px; line-height: 1.6; margin-bottom: 25px;"><?= __('firewall_blocked_ip', 'Tường lửa phát hiện truy cập độc hại. Nhằm bảo vệ dữ liệu nhà trường, địa chỉ IP của bạn đã bị khóa tự động.') ?></p>
            <div style="background: var(--bg-hover); padding: 15px; border-radius: 8px; text-align: left; margin-bottom: 25px; border: 1px solid var(--border-color); font-size: 13px;">
                <div style="margin-bottom: 8px;"><strong style="color: var(--text-main);"><i class="fas fa-globe"></i> <?= __('ip_public', 'IP Public:') ?></strong> <span style="color: var(--primary-color); font-family: monospace; font-size: 14px;"><?= htmlspecialchars($client_ip) ?></span></div>
                <div style="margin-bottom: 8px; display: flex; align-items: flex-start; gap: 5px;"><strong style="color: var(--text-main); white-space: nowrap;"><i class="fas fa-laptop"></i> <?= __('device', 'Thiết bị:') ?></strong> <span style="color: var(--text-muted); word-break: break-all;"><?= htmlspecialchars($banned['user_agent']) ?></span></div>
                <div style="margin-bottom: 8px;"><strong style="color: var(--text-main);"><i class="fas fa-exclamation-triangle"></i> <?= __('punishment_reason', 'Lý do phạt:') ?></strong> <span style="color: var(--danger-color);"><?= htmlspecialchars($banned['reason']) ?></span></div>
                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed var(--border-color);"><strong style="color: var(--text-main);"><i class="fas fa-unlock-alt"></i> <?= __('auto_unlock_after', 'Tự động mở khóa sau:') ?></strong> <span style="color: #f59e0b; font-weight: bold; font-size: 16px;" id="countdownTimer"><?= __('calculating', 'Đang tính...') ?></span></div>
            </div>
            <button onclick="window.location.reload()" class="win-btn" style="width: 100%; height: 45px;"><i class="fas fa-sync-alt"></i> <?= __('try_access_again', 'Thử truy cập lại') ?></button>
        </div>
        <script>
            const expireTime = <?= $expire_timestamp ?>;
            const timerEl = document.getElementById('countdownTimer');
            const countdown = setInterval(() => {
                const distance = expireTime - new Date().getTime();
                if (distance < 0) {
                    clearInterval(countdown);
                    timerEl.innerHTML = "<?= __('punishment_expired_reload', 'Đã hết hạn phạt. Vui lòng tải lại trang!') ?>";
                    timerEl.style.color = "var(--primary-color)";
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    timerEl.innerHTML = Math.floor((distance % (1000 * 60)) / 1000) + " <?= __('seconds', 'giây') ?>";
                }
            }, 1000);
        </script>
    </body>
    </html>
    <?php
    exit();
}

function get_device_name() {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (strpos($ua, 'Flutter') !== false)       return 'App Di Động';
    if (strpos($ua, 'iPhone') !== false)        return 'iPhone';
    if (strpos($ua, 'iPad') !== false)          return 'iPad';
    if (strpos($ua, 'Android') !== false)       return 'Android';
    if (strpos($ua, 'Windows') !== false)       return 'Windows PC';
    if (strpos($ua, 'Macintosh') !== false)     return 'MacBook';
    if (strpos($ua, 'Linux') !== false)         return 'Linux PC';
    if (strpos($ua, 'CriOS') !== false)         return 'Chrome iOS';
    return 'Thiết bị lạ';
}

function login_user_action($pdo, $user, $selector = null) {
    $old_sess_id = session_id();
    session_regenerate_id(true);
    $_SESSION['user'] = $user;
    $sessId = session_id();

    // Lấy IP thật qua Cloudflare
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    if (strpos($ip, ',') !== false) $ip = trim(explode(',', $ip)[0]);

    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $dev = get_device_name();

    // Lấy old_session_id từ token_selector nếu có (để đồng bộ push)
    $old_session_id = null;
    if ($selector) {
        $stmtCheck = $pdo->prepare("SELECT session_id FROM user_sessions WHERE token_selector = ? LIMIT 1");
        $stmtCheck->execute([$selector]);
        $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        if ($row) $old_session_id = $row['session_id'];
    }

    // Xóa session cũ cùng loại thiết bị của user (tránh lặp)
    $pdo->prepare("
        DELETE FROM user_sessions
        WHERE user_id = ? AND device_name = ? AND session_id != ?
    ")->execute([$user['id'], $dev, $sessId]);

    // UPSERT session: không bao giờ tạo bản ghi trùng
    $pdo->prepare("
        INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, device_name, token_selector, last_active)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            ip_address     = VALUES(ip_address),
            last_active    = NOW(),
            token_selector = VALUES(token_selector)
    ")->execute([$user['id'], $sessId, $ip, $ua, $dev, $selector]);

    // Đồng bộ push_subscription nếu đổi session
    if ($old_session_id && $old_session_id !== $sessId) {
        $pdo->prepare("UPDATE push_subscription SET session_id = ? WHERE session_id = ?")
            ->execute([$sessId, $old_session_id]);
    }

    $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")->execute([$user['id']]);
}

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
            if ($user && empty($user['two_factor_enabled'])) {
                login_user_action($pdo, $user, $selector);
            }
        } else {
            setcookie('remember_token', '', time() - 3600, '/', "", false, true);
        }
    }
}

$error = '';
$system_notice = "";
$show_2fa_step = false;

$next_url = $_GET['next'] ?? $_POST['next'] ?? 'index.php';
if (strpos($next_url, 'http') === 0 || strpos($next_url, '//') === 0) {
    $next_url = 'index.php';
}

if (isset($_SESSION['user'])) { header("Location: $next_url"); exit(); }

// --- XỬ LÝ NHẬP MÃ 2FA ĐĂNG NHẬP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_2fa') {
    if (isset($_SESSION['pending_2fa_user'])) {
        $pendingUser = $_SESSION['pending_2fa_user'];
        $two_factor_code = trim($_POST['two_factor_code'] ?? '');
        $remember = !empty($_SESSION['pending_2fa_remember']);
        $target_next = $_SESSION['pending_2fa_next'] ?? $next_url;

        if (TOTP::verifyCode($pendingUser['two_factor_secret'], $two_factor_code)) {
            unset($_SESSION['pending_2fa_user'], $_SESSION['pending_2fa_remember'], $_SESSION['pending_2fa_next']);
            
            $selector = null;
            if ($remember) {
                $selector = bin2hex(random_bytes(16));
                $validator = bin2hex(random_bytes(32));
                $hashed_validator = hash('sha256', $validator);
                $expiry = date('Y-m-d H:i:s', time() + (86400 * 30));
                
                $stmtToken = $pdo->prepare("INSERT INTO user_tokens (user_id, selector, hashed_validator, expiry) VALUES (?, ?, ?, ?)");
                $stmtToken->execute([$pendingUser['id'], $selector, $hashed_validator, $expiry]);

                setcookie('remember_token', "$selector:$validator", time() + (86400 * 30), "/", "", false, true);
            }

            login_user_action($pdo, $pendingUser, $selector);
            header("Location: $target_next");
            exit();
        } else {
            $show_2fa_step = true;
            $error = __('invalid_2fa_code', 'Mã xác thực 2FA không chính xác hoặc đã hết hạn!');
        }
    } else {
        header("Location: login.php");
        exit();
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember_me']);

    if (preg_match('/[\'"#;\-]|(--)|(\bOR\b)|(\bUNION\b)|(\bSELECT\b)/i', $username)) {
        $reason = __('sql_injection_attack', 'Cố tình tấn công SQL Injection với chuỗi: ') . substr($username, 0, 30);
        $stmtBlock = $pdo->prepare("INSERT INTO banned_ips (ip_address, reason, banned_at, expires_at) VALUES (?, ?, NOW(), NOW() + INTERVAL 24 HOUR) ON DUPLICATE KEY UPDATE expires_at = NOW() + INTERVAL 24 HOUR, reason = ?");
        $stmtBlock->execute([$client_ip, $reason, $reason]);
        http_response_code(403);
        die(__('vandalism_detected_locked_24h', 'Phát hiện hành vi phá hoại! IP của bạn đã bị ghi log và khóa 24 giờ.'));
    }

    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }

    $max_attempts = 5;
    $lock_time = 60;

    $pdo->prepare("DELETE FROM login_attempts WHERE attempt_time < (NOW() - INTERVAL ? SECOND)")->execute([$lock_time]);

    $stmtAttempts = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE username = ?");
    $stmtAttempts->execute([$username]);
    $attempts = $stmtAttempts->fetchColumn();

    if ($attempts >= $max_attempts) {
        $error = __('account_locked_temporarily', 'Tài khoản này đang bị khóa tạm thời do nhập sai quá nhiều. Vui lòng chờ 1 phút!');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $pdo->prepare("DELETE FROM login_attempts WHERE username = ?")->execute([$username]);

            // KIỂM TRA 2FA
            if (!empty($user['two_factor_enabled']) && !empty($user['two_factor_secret'])) {
                $_SESSION['pending_2fa_user'] = $user;
                $_SESSION['pending_2fa_remember'] = $remember;
                $_SESSION['pending_2fa_next'] = $next_url;
                $show_2fa_step = true;
            } else {
                $selector = null;
                if ($remember) {
                    $selector = bin2hex(random_bytes(16));
                    $validator = bin2hex(random_bytes(32));
                    $hashed_validator = hash('sha256', $validator);
                    $expiry = date('Y-m-d H:i:s', time() + (86400 * 30));
                    
                    $stmtToken = $pdo->prepare("INSERT INTO user_tokens (user_id, selector, hashed_validator, expiry) VALUES (?, ?, ?, ?)");
                    $stmtToken->execute([$user['id'], $selector, $hashed_validator, $expiry]);

                    setcookie('remember_token', "$selector:$validator", time() + (86400 * 30), "/", "", false, true);
                }

                login_user_action($pdo, $user, $selector);
                header("Location: $next_url"); exit();
            }
        } else { 
            $pdo->prepare("INSERT INTO login_attempts (username, ip_address, attempt_time) VALUES (?, ?, NOW())")->execute([$username, $ip]);
            $error = __('invalid_credentials', 'Tài khoản hoặc mật khẩu không chính xác.');
        }
    }
}
require_once 'views/login_view.php';
?>