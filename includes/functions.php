<?php
// includes/functions.php
require_once __DIR__ . '/setup_variables.php';
require_once __DIR__ . '/version.php';
require_once __DIR__ . '/push_helper.php';

function get_config_for_year($pdo, $key, $school_year, $default = '') {
    try {
        $stmt = $pdo->prepare("SELECT value FROM config WHERE `key` = ?");
        $stmt->execute([$key . "_" . $school_year]);
        $val = $stmt->fetchColumn();
        if ($val !== false) {
            return $val;
        }
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function get_settings($pdo) {
    global $redis, $redis_connected;
    $current_school_year = get_current_school_year($pdo);
    $cache_key = 'lg3_system_settings_' . $current_school_year;

    // [TỐI ƯU] Lấy Settings từ Redis
    if ($redis_connected && $redis->exists($cache_key)) {
        return json_decode($redis->get($cache_key), true);
    }

    $settings = ['start_date' => '2025-09-05']; // Mặc định
    try {
        $settings['start_date'] = get_config_for_year($pdo, 'start_date', $current_school_year, '2025-09-05');
    } catch (Exception $e) { }

    // Lưu vào Redis, sống 24h
    if ($redis_connected) $redis->setex($cache_key, 86400, json_encode($settings));
    
    return $settings;
}

function get_current_week($pdo) {
    try {
        $current_school_year = get_current_school_year($pdo);
        $current_start_date = get_config_for_year($pdo, 'start_date', $current_school_year, date('Y-m-d'));

        $start = new DateTime($current_start_date);
        $now = new DateTime();
        $now->setTime(0,0,0); 
        $start->setTime(0,0,0);

        if ($now < $start) return 1; 

        $diff = $start->diff($now);
        $days_passed = $diff->days;

        return floor($days_passed / 7) + 1;
    } catch (Exception $e) { 
        return 1; 
    }
}

function is_week_skipped($week, $pdo) {
    try {
        $current_school_year = get_current_school_year($pdo);
        $start_date_str = get_config_for_year($pdo, 'start_date', $current_school_year, date('Y-m-d'));
        $excluded_dates_json = get_config_for_year($pdo, 'excluded_dates', $current_school_year, '[]');
        $excluded_list = json_decode($excluded_dates_json, true) ?: [];

        $start = new DateTime($start_date_str);
        
        $week_start = clone $start;
        $week_start->modify("+" . (($week - 1) * 7) . " days");

        $school_days = 0;
        for ($i = 0; $i < 7; $i++) {
            $day = clone $week_start;
            $day->modify("+$i days");
            
            if ($day->format('N') == 7) continue;

            $dateStr = $day->format('Y-m-d');
            if (!in_array($dateStr, $excluded_list)) {
                $school_days++;
            }
        }
        return ($school_days === 0);
    } catch (Exception $e) {
        return false;
    }
}

function checkRole($allowed_roles = []) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }
    if (!empty($allowed_roles) && !in_array($_SESSION['user']['role'], $allowed_roles)) {
        http_response_code(403); die(__('access_denied', 'Bạn không có quyền truy cập trang này.'));
    }
}

function get_current_school_year($pdo) {
    static $school_year = null;
    if ($school_year !== null) return $school_year;
    try {
        $stmt = $pdo->prepare("SELECT value FROM config WHERE `key` = 'current_school_year'");
        $stmt->execute();
        $school_year = $stmt->fetchColumn() ?: '2026-2027';
    } catch (Exception $e) {
        $school_year = '2026-2027';
    }
    return $school_year;
}

function getWeeklyMatrixData($pdo, $class_id, $week) {
    $current_school_year = get_current_school_year($pdo);
    $stmtClsCols = $pdo->query("SELECT short_code, max_penalty_points FROM violation_type WHERE scope = 'CLASS' ORDER BY id ASC");
    $dbScores = [];
    while ($row = $stmtClsCols->fetch(PDO::FETCH_ASSOC)) {
        $dbScores[$row['short_code']] = (float)$row['max_penalty_points'];
    }
    $baseScores = [];
    $defaultOrder = ["SS", "VS", "CSVC", "TB", "XE", "DP", "SV", "THE", "DT"];
    foreach ($defaultOrder as $code) {
        if (isset($dbScores[$code])) {
            $baseScores[$code] = $dbScores[$code];
            unset($dbScores[$code]);
        }
    }
    foreach ($dbScores as $code => $max) {
        $baseScores[$code] = $max;
    }
    if (empty($baseScores)) {
        $baseScores = ["SS"=>1, "VS"=>1, "CSVC"=>1, "TB"=>1, "XE"=>1, "DP"=>2, "SV"=>1, "THE"=>1, "DT"=>1];
    }
    $ORDER_COLS = array_keys($baseScores);
    $gateCodes = ['DIMUON' => 0, 'KDP' => 0, 'MBH' => 0, 'KTHE' => 0, 'KHAC_GATE' => 0];
    $maxScores = array_merge($baseScores, $gateCodes);
    $matrix = [];
    for ($d = 2; $d <= 7; $d++) { foreach ($ORDER_COLS as $code) { $matrix[$d][$code] = 0; } }
    $stmt = $pdo->prepare("SELECT r.*, vt.short_code FROM violation_record r JOIN violation_type vt ON r.violation_type_id = vt.id WHERE r.class_id = ? AND r.week_number = ? AND r.is_deleted = 0 AND r.school_year = ?");
    $stmt->execute([$class_id, $week, $current_school_year]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($records as $r) {
        if (in_array($r['short_code'], ['DIMUON', 'KDP', 'MBH', 'KTHE', 'KHAC_GATE'])) continue;
        $date = new DateTime($r['date_created']);
        $d = (int)$date->format('N') + 1;
        if (preg_match('/\(T(\d)\)/', $r['recorded_violation_name'], $matches)) { $d = (int)$matches[1]; }
        if ($d > 7) $d = 7;
        $code = $r['short_code'];
        if (in_array($code, $ORDER_COLS)) { $matrix[$d][$code] += (float)$r['recorded_points']; }
    }
    return ['matrix' => $matrix, 'maxScores' => $maxScores, 'ORDER_COLS' => $ORDER_COLS];
}

// =================================================================
// 2. HÀM GỬI PUSH ĐƠN
// =================================================================
function sendPushToUser($pdo, $user_id, $title, $body, $url = '/') {
    $stmt = $pdo->prepare("SELECT endpoint, p256dh, auth, platform FROM push_subscription WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$subs) return false;

    $has_success = false;
    $fcm_auth = null; 

    $authWeb = [ 'VAPID' => [ 'subject' => defined('VAPID_SUBJECT') ? VAPID_SUBJECT : '', 'publicKey' => defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '', 'privateKey' => defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : '' ] ];
    $webPush = new \Minishlink\WebPush\WebPush($authWeb);
    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";

    foreach ($subs as $s) {
        if ($s['platform'] === 'app') {
            if (!$fcm_auth) $fcm_auth = getFCMAccessToken();
            if (!$fcm_auth || !$fcm_auth['token']) continue;

            // FIX: BẮT BUỘC KHAI BÁO CỤC 'notification' ĐỂ XUYÊN QUA ĐƯỢC CHẾ ĐỘ NGỦ CỦA ANDROID
            $message = [
                'message' => [
                    'token' => $s['endpoint'],
                    'notification' => [
                        'title' => $title, 
                        'body' => $body
                    ],
                    'data' => [ 
                        'title' => $title, 
                        'body' => $body,
                        'url' => $url 
                    ]
                ]
            ];

            $ch = curl_init("https://fcm.googleapis.com/v1/projects/{$fcm_auth['project_id']}/messages:send");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $fcm_auth['token'], 'Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            $res = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpcode == 200) $has_success = true;
            
        } else {
            $payload = json_encode(['title' => $title, 'body'  => $body, 'url' => $url, 'icon' => $protocol . "://" . $domain . "/lg3192192.png"]);
            $subscription = \Minishlink\WebPush\Subscription::create(['endpoint' => $s['endpoint'], 'publicKey' => $s['p256dh'], 'authToken' => $s['auth']]);
            $webPush->queueNotification($subscription, $payload);
        }
    }

    foreach ($webPush->flush() as $report) { if ($report->isSuccess()) $has_success = true; }
    return $has_success;
}

// =================================================================
// 3. HÀM GỬI PUSH HÀNG LOẠT BULK 
// =================================================================
function sendBulkPush($pdo, $pushDataList) {
    if (empty($pushDataList)) return true;
    
    $authWeb = [ 'VAPID' => [ 'subject' => defined('VAPID_SUBJECT') ? VAPID_SUBJECT : '', 'publicKey' => defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '', 'privateKey' => defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : '' ] ];
    $webPush = new \Minishlink\WebPush\WebPush($authWeb);
    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    
    $userIds = array_values(array_unique(array_column($pushDataList, 'user_id')));
    if (empty($userIds)) return false;
    
    $inQuery = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $pdo->prepare("SELECT user_id, endpoint, p256dh, auth, platform FROM push_subscription WHERE user_id IN ($inQuery)");
    $stmt->execute($userIds);
    $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $subsByUser = [];
    foreach ($subs as $s) { $subsByUser[$s['user_id']][] = $s; }
    
    $has_success = false;
    $fcm_auth = null;
    
    $mh = curl_multi_init();
    $curl_handles = [];

    foreach ($pushDataList as $job) {
        $uid = $job['user_id'];
        if (empty($subsByUser[$uid])) continue;
        
        $payload_data = ['title' => $job['title'], 'body'  => $job['body'], 'url'   => $job['url']];
        if (php_sapi_name() !== 'cli') $payload_data['icon'] = $protocol . "://" . $domain . "/lg3192192.png";
        $payload = json_encode($payload_data);

        foreach ($subsByUser[$uid] as $s) {
            if (isset($s['platform']) && $s['platform'] === 'app') {
                if (!$fcm_auth) $fcm_auth = getFCMAccessToken();
                if ($fcm_auth && $fcm_auth['token']) {
                    
                    // FIX: THÊM LẠI CỤC 'notification' ĐỂ XUYÊN APP DEAD
                    $message = [
                        'message' => [
                            'token' => $s['endpoint'],
                            'notification' => [
                                'title' => $job['title'], 
                                'body' => $job['body']
                            ],
                            'data' => [ 
                                'title' => $job['title'], 
                                'body' => $job['body'],
                                'url' => $job['url']
                            ]
                        ]
                    ];
                    
                    $ch = curl_init("https://fcm.googleapis.com/v1/projects/{$fcm_auth['project_id']}/messages:send");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $fcm_auth['token'], 'Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
                    
                    curl_multi_add_handle($mh, $ch);
                    $curl_handles[] = $ch;
                }
            } else {
                $subscription = \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $s['endpoint'], 'publicKey' => $s['p256dh'], 'authToken' => $s['auth']
                ]);
                $webPush->queueNotification($subscription, $payload);
            }
        }
    }
    
    if (!empty($curl_handles)) {
        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        foreach ($curl_handles as $ch) {
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) $has_success = true;
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
    }
    curl_multi_close($mh);
    
    foreach ($webPush->flush() as $report) { if ($report->isSuccess()) $has_success = true; }
    return $has_success;
}

// =================================================================
// 4. HÀM SINH TOKEN FIREBASE 
// =================================================================
function getFCMAccessToken() {
    global $redis, $redis_connected;
    $cache_key = 'fcm_access_token_cache';

    if ($redis_connected && $redis->exists($cache_key)) {
        return json_decode($redis->get($cache_key), true);
    }

    $keyFile = __DIR__ . '/firebase_credentials.json';
    if (!file_exists($keyFile)) return false;
    $key = json_decode(file_get_contents($keyFile), true);

    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'iss' => $key['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => $key['token_uri'],
        'exp' => $now + 3600,
        'iat' => $now
    ]);

    $b64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $b64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    openssl_sign($b64Header . '.' . $b64Payload, $signature, $key['private_key'], 'SHA256');
    $b64Sig = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    $jwt = $b64Header . '.' . $b64Payload . '.' . $b64Sig;

    $ch = curl_init($key['token_uri']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $result = [
        'token' => $res['access_token'] ?? false,
        'project_id' => $key['project_id'] ?? ''
    ];

    if ($redis_connected && $result['token']) {
        $redis->setex($cache_key, 3300, json_encode($result));
    }

    return $result;
}

// =================================================================
// CÁC HÀM HỖ TRỢ ĐỌC LOGS HỆ THỐNG CẤP THẤP HIỆU NĂNG CAO (TRAFFIC MONITOR)
// =================================================================
function get_recent_system_logs($filePath, $startTimeUnix = null, $maxLines = null) {
    if (!file_exists($filePath)) return [];
    
    $handle = fopen($filePath, 'r');
    if (!$handle) return [];
    
    $bufferSize = 4096;
    fseek($handle, 0, SEEK_END);
    $fileSize = ftell($handle);
    $pos = $fileSize;
    
    $leftover = '';
    $results = [];
    $count = 0;
    
    while ($pos > 0) {
        $readSize = min($bufferSize, $pos);
        $pos -= $readSize;
        fseek($handle, $pos);
        $chunk = fread($handle, $readSize);
        if ($chunk === false) break;
        
        $chunk .= $leftover;
        $lines = explode("\n", $chunk);
        
        $leftover = array_shift($lines);
        
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);
            if ($line === '') continue;
            
            $parsed = parse_system_log_line($line);
            if ($parsed) {
                if ($startTimeUnix && strtotime($parsed['time']) < $startTimeUnix) {
                    fclose($handle);
                    return $results;
                }
                $results[] = $parsed;
                $count++;
                if ($maxLines && $count >= $maxLines) {
                    fclose($handle);
                    return $results;
                }
            }
        }
    }
    
    if (trim($leftover) !== '') {
        $parsed = parse_system_log_line(trim($leftover));
        if ($parsed) {
            if (!$startTimeUnix || strtotime($parsed['time']) >= $startTimeUnix) {
                $results[] = $parsed;
            }
        }
    }
    
    fclose($handle);
    return $results;
}

function parse_system_log_line($line) {
    if (empty($line)) return null;
    $parts = explode("\t", $line);
    if (count($parts) < 5) return null;
    
    $time = trim($parts[0], '[]');
    $ip = $parts[1];
    $duration = (float)$parts[2];
    $status = (int)$parts[3];
    $path = $parts[4];
    
    return [
        'time' => $time,
        'ip' => $ip,
        'duration' => $duration,
        'status' => $status,
        'path' => $path
    ];
}

/**
 * Gửi email thông báo qua Brevo SMTP Relay
 */
function send_brevo_email($toEmail, $subject, $htmlContent, $fromEmail = null) {
    $host = defined('BREVO_SMTP_HOST') ? BREVO_SMTP_HOST : 'smtp-relay.brevo.com';
    $port = defined('BREVO_SMTP_PORT') ? BREVO_SMTP_PORT : 587;
    $user = defined('BREVO_SMTP_USER') ? BREVO_SMTP_USER : 'ab49da001@smtp-brevo.com';
    $pass = defined('BREVO_SMTP_PASS') ? BREVO_SMTP_PASS : '';

    if (empty($pass)) {
        return ['status' => false, 'msg' => 'Chưa cấu hình BREVO_SMTP_PASS'];
    }

    $from = 'support@testifiyonline.xyz';
    $fromName = 'LG3 Support';
    $to = is_array($toEmail) ? implode(',', $toEmail) : $toEmail;

    $socket = @fsockopen($host, $port, $errno, $errstr, 8);
    if (!$socket) {
        return ['status' => false, 'msg' => "Không thể kết nối Brevo SMTP: $errstr"];
    }

    $readFn = function($s) {
        $res = '';
        while ($str = fgets($s, 512)) {
            $res .= $str;
            if (substr($str, 3, 1) === ' ') break;
        }
        return $res;
    };

    $readFn($socket);
    fputs($socket, "EHLO testifiyonline.xyz\r\n");
    $readFn($socket);
    fputs($socket, "STARTTLS\r\n");
    $readFn($socket);
    @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    fputs($socket, "EHLO testifiyonline.xyz\r\n");
    $readFn($socket);
    fputs($socket, "AUTH LOGIN\r\n");
    $readFn($socket);
    fputs($socket, base64_encode($user) . "\r\n");
    $readFn($socket);
    fputs($socket, base64_encode($pass) . "\r\n");
    $authRes = $readFn($socket);

    if (strpos($authRes, '235') === false) {
        fclose($socket);
        return ['status' => false, 'msg' => 'Xác thực Brevo SMTP thất bại'];
    }

    fputs($socket, "MAIL FROM: <$from>\r\n");
    $readFn($socket);
    fputs($socket, "RCPT TO: <$to>\r\n");
    $readFn($socket);
    fputs($socket, "DATA\r\n");
    $readFn($socket);

    $headers  = "From: $fromName <$from>\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";

    fputs($socket, $headers . $htmlContent . "\r\n.\r\n");
    $dataRes = $readFn($socket);
    fputs($socket, "QUIT\r\n");
    @fclose($socket);

    if (strpos($dataRes, '250') !== false) {
        return ['status' => true, 'id' => 'brevo_' . time()];
    }

    return ['status' => false, 'msg' => 'Brevo SMTP từ chối gửi: ' . $dataRes];
}

/**
 * Gửi email thông báo: Ưu tiên Resend API trước (Nhanh, đẹp, chuẩn ảnh).
 * Nếu Resend lỗi hoặc hết quota -> Tự động chuyển sang Brevo SMTP làm Dự phòng (Fallback).
 */
function send_resend_email($toEmail, $subject, $htmlContent, $fromEmail = null) {
    // 1. Gửi qua Resend API trước (Ưu tiên số 1)
    $apiKey = defined('RESEND_API_KEY') ? RESEND_API_KEY : '';
    if (!empty($apiKey)) {
        $from = $fromEmail ?: (defined('RESEND_FROM_EMAIL') ? RESEND_FROM_EMAIL : 'LG3 Support <support@testifiyonline.xyz>');

        $payload = [
            'from' => $from,
            'to' => is_array($toEmail) ? $toEmail : [$toEmail],
            'subject' => $subject,
            'html' => $htmlContent
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 8
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if (!$err && ($httpCode === 200 || $httpCode === 201)) {
            $resData = json_decode($response, true);
            return ['status' => true, 'id' => $resData['id'] ?? '', 'provider' => 'resend'];
        }
    }

    // 2. Nếu Resend gặp sự cố hoặc hết hạn ngạch -> Chuyển sang Brevo SMTP làm Dự phòng (Fallback)
    $brevoRes = send_brevo_email($toEmail, $subject, $htmlContent, $fromEmail);
    if ($brevoRes['status']) {
        $brevoRes['provider'] = 'brevo_fallback';
    }
    return $brevoRes;
}
?>