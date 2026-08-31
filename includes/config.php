<?php
// includes/config.php

// 1. KHỞI TẠO MÔI TRƯỜNG & BIẾN TOÀN CỤC
require_once __DIR__ . '/setup_variables.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/db_config.php';

// (Đảm bảo SECRET_KEY đã được định nghĩa bên trong setup_variables.php)


if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    ini_set('session.gc_maxlifetime', 315360000);
    session_set_cookie_params(315360000);
    session_start();
}
date_default_timezone_set('Asia/Ho_Chi_Minh');

// LOCALIZATION ENGINE
if (isset($_GET['lang']) && in_array($_GET['lang'], ['vi', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time() + 365 * 86400, '/', "", false, false);
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['vi', 'en'])) {
    $_SESSION['lang'] = $_COOKIE['lang'];
} else {
    if (!isset($_SESSION['lang'])) {
        $_SESSION['lang'] = 'vi';
    }
}

global $translation_dict;
$lang_file = __DIR__ . '/../languages/' . $_SESSION['lang'] . '.json';
if (file_exists($lang_file)) {
    $translation_dict = json_decode(file_get_contents($lang_file), true) ?: [];
} else {
    $translation_dict = [];
}

if (!function_exists('__')) {
    function __($key, $default = null) {
        global $translation_dict;
        if (isset($translation_dict[$key])) {
            return $translation_dict[$key];
        }
        return $default !== null ? $default : $key;
    }
}

$start_time = microtime(true);

// Nạp file functions
require_once __DIR__ . '/functions.php';

// =================================================================
// 2. KHỞI TẠO CACHE REDIS (Chạy trước Database để gánh tải)
// =================================================================
global $redis, $redis_connected;
$redis_connected = false;

if (class_exists('Redis')) {
    try {
        $redis = new Redis();
        // Giảm timeout kết nối Redis xuống 1s để nếu Redis chết, web không bị treo
        $redis->connect('127.0.0.1', 6379, 1.0); 
        $redis_connected = true;
    } catch (Exception $e) {
        $redis_connected = false; 
        // Bỏ qua lỗi, hệ thống sẽ tự động fallback về MySQL an toàn
    }
}

// =================================================================
// 3. KẾT NỐI DATABASE (PDO)
// =================================================================
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=$db_charset", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Tắt strict mode của GROUP BY để tương thích với các query cũ
    $pdo->exec("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
} catch (PDOException $e) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        die(json_encode(['status' => 'error', 'msg' => 'Database Error']));
    }
    die(__('db_conn_error_config', 'Lỗi kết nối Database. Vui lòng kiểm tra thông tin cấu hình!'));
}

// =================================================================
// 4. LOGIC XỬ LÝ NỀN (AUTO-LOGIN SIÊU TỐC VỚI REDIS)
// =================================================================
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    $parts = explode(':', $_COOKIE['remember_token']);
    if (count($parts) === 2) {
        $selector = $parts[0]; 
        $validator = $parts[1];
        
        $cacheTokenKey = "lg3_remember_token_$selector";
        $tokenRow = null;

        // BƯỚC 1: Thử moi dữ liệu Token từ RAM (Redis) ra trước
        if ($redis_connected) {
            $cachedToken = $redis->get($cacheTokenKey);
            if ($cachedToken) {
                $tokenRow = json_decode($cachedToken, true);
            }
        }

        // BƯỚC 2: Nếu Redis trắng trơn, mới bắt MySQL phải làm việc
        if (!$tokenRow) {
            $stmt = $pdo->prepare("SELECT * FROM user_tokens WHERE selector = ? AND expiry > NOW()");
            $stmt->execute([$selector]);
            $tokenRow = $stmt->fetch();
            
            // Lấy xong thì nhét lại vào Redis, giữ trong 1 giờ (3600s)
            if ($tokenRow && $redis_connected) {
                $redis->setex($cacheTokenKey, 3600, json_encode($tokenRow));
            }
        }
        
        // BƯỚC 3: Xác thực
        if ($tokenRow && hash_equals($tokenRow['hashed_validator'], hash('sha256', $validator))) {
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmtUser->execute([$tokenRow['user_id']]);
            $user = $stmtUser->fetch();
            
            if ($user) {
                $_SESSION['user'] = $user;
                
                // [THỦ THUẬT DEBOUNCE I/O]: Tránh spam lệnh UPDATE vào MySQL
                // Bình thường mỗi lần chuyển trang là MySQL bị đấm 1 lệnh UPDATE last_active.
                // Bây giờ ta dùng Redis làm khiên chặn: 5 phút mới cho UPDATE 1 lần.
                $lastActiveKey = "lg3_user_active_update_" . $user['id'];
                if (!$redis_connected || !$redis->exists($lastActiveKey)) {
                    $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")->execute([$user['id']]);
                    if ($redis_connected) {
                        $redis->setex($lastActiveKey, 300, 1); // Khóa 300 giây
                    }
                }
            }
        } else {
            setcookie('remember_token', '', time() - 3600, '/', "", false, true);
        }
    }
}

// =================================================================
// 5. GHI LOG TRAFFIC TỰ ĐỘNG (CÓ TÍCH HỢP DỌN RÁC THÔNG MINH)
// =================================================================
register_shutdown_function(function() use ($start_time) {
    $path = $_SERVER['REQUEST_URI'] ?? '/';
    // Bỏ qua các file tĩnh để không làm phình file log
    if (strpos($path, '/static/') !== false || strpos($path, 'favicon.ico') !== false) return;
    
    try {
        $duration = (microtime(true) - $start_time) * 1000; 
        $ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (strpos($ip_address, ',') !== false) $ip_address = trim(explode(',', $ip_address)[0]);
        $status_code = http_response_code() ?: 200;
        
        $logDir = '/var/www/html/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Bảo vệ thư mục log bằng .htaccess
        $htaccessFile = $logDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Require all denied\n");
        }
        
        $logFile = $logDir . '/access_system.log';
        $logLine = sprintf("[%s]\t%s\t%s\t%s\t%s\n", date('Y-m-d H:i:s'), $ip_address, round($duration, 2), $status_code, $path);
        
        // Ghi log bảo vệ chống xung đột ghi bằng LOCK_EX
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // Cơ chế tự động dọn dẹp log (Xoay vòng thông minh xác suất)
        // Trung bình cứ 500 requests thì kiểm tra dung lượng log. Nếu > 10MB, chỉ giữ lại 5000 dòng mới nhất.
        if (rand(1, 500) === 1) {
            if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) {
                $recentLines = [];
                $handle = fopen($logFile, 'r');
                if ($handle) {
                    $bufferSize = 4096;
                    fseek($handle, 0, SEEK_END);
                    $pos = ftell($handle);
                    $leftover = '';
                    while ($pos > 0 && count($recentLines) < 5000) {
                        $readSize = min($bufferSize, $pos);
                        $pos -= $readSize;
                        fseek($handle, $pos);
                        $chunk = fread($handle, $readSize) . $leftover;
                        $lines = explode("\n", $chunk);
                        $leftover = array_shift($lines);
                        for ($i = count($lines) - 1; $i >= 0; $i--) {
                            $line = trim($lines[$i]);
                            if ($line !== '') {
                                $recentLines[] = $line;
                                if (count($recentLines) >= 5000) break;
                            }
                        }
                    }
                    fclose($handle);
                    $recentLines = array_reverse($recentLines);
                    file_put_contents($logFile, implode("\n", $recentLines) . "\n", LOCK_EX);
                }
            }
        }
    } catch (Exception $e) { 
        error_log(__('log_write_error', 'Lỗi ghi log: ') . $e->getMessage()); 
    }
});