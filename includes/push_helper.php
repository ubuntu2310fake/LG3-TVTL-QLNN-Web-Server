<?php
// includes/push_helper.php — CHUẨN HÓA HẠ TẦNG PUSH NOTIFICATION CHUYÊN SÂU
// Tương thích 100% với Web Notification API (PWA), Service Worker (sw.js) & Flutter App (FCM + Awesome Notifications)

if (!function_exists('getFCMAccessToken')) {
    require_once __DIR__ . '/functions.php';
}

/**
 * Đẩy một Job vào hàng đợi notification_queue
 */
function enqueueNotification(PDO $pdo, string $type, array $payloadData): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO notification_queue (type, payload, status, created_at) VALUES (?, ?, 'pending', NOW())");
        return $stmt->execute([$type, json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    } catch (Exception $e) {
        error_log("❌ Error enqueuing push notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Xây dựng Web Push JSON Payload (Dành cho Web PWA / Service Worker sw.js)
 */
function buildWebPushPayload(string $title, string $body, string $url, string $type, string $targetId = '', string $action = 'open_url'): string {
    $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $baseUrl = $protocol . "://" . $domain;

    return json_encode([
        'title' => $title,
        'body'  => $body,
        'icon'  => $baseUrl . '/lg3192192.png',
        'badge' => $baseUrl . '/lg3192192.png',
        'data'  => [
            'url'       => $url,
            'type'      => $type,
            'target_id' => (string)$targetId,
            'action'    => $action,
            'sound'     => 'default',
            'platform'  => 'web'
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Xây dựng FCM HTTP v1 Message Array (Dành cho Flutter App)
 * LƯU Ý BẮT BUỘC: Mọi value trong mảng 'data' PHẢI là kiểu STRING!
 */
function buildFcmMessageArray(string $token, string $title, string $body, string $url, string $type, string $targetId = '', string $action = 'open_url', string $channelKey = 'default_channel'): array {
    return [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => (string)$title,
                'body'  => (string)$body
            ],
            'data' => [
                'title'       => (string)$title,
                'body'        => (string)$body,
                'url'         => (string)$url,
                'type'        => (string)$type,
                'target_id'   => (string)$targetId,
                'action'      => (string)$action,
                'icon'        => 'lg3192192',
                'sound'       => 'default',
                'platform'    => 'app',
                'channel_key' => (string)$channelKey
            ],
            'android' => [
                'priority' => 'HIGH',
                'notification' => [
                    'sound'        => 'default',
                    'channel_id'   => $channelKey,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ]
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1
                    ]
                ]
            ]
        ]
    ];
}

class PushHelper {
    public static function isMobilePlatform(string $platform): bool {
        $p = strtolower(trim($platform));
        return in_array($p, ['app', 'android', 'ios', 'flutter', 'mobile']);
    }

    public static function sendToUser(PDO $pdo, int $user_id, string $title, string $body, string $url = '/', string $type = 'GENERAL', string $targetId = '', string $action = 'open_url', string $channelKey = 'default_channel'): bool {
        return sendPushToUserEx($pdo, $user_id, $title, $body, $url, $type, $targetId, $action, $channelKey);
    }

    public static function sendBulk(PDO $pdo, array $pushDataList): bool {
        return sendBulkPushEx($pdo, $pushDataList);
    }
}

/**
 * Gửi Push Notification Đơn tới 1 User
 */
function sendPushToUserEx(PDO $pdo, int $user_id, string $title, string $body, string $url = '/', string $type = 'GENERAL', string $targetId = '', string $action = 'open_url', string $channelKey = 'default_channel'): bool {
    $stmt = $pdo->prepare("SELECT endpoint, p256dh, auth, platform FROM push_subscription WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$subs) return false;

    $has_success = false;
    $fcm_auth = null;

    $authWeb = [
        'VAPID' => [
            'subject'    => defined('VAPID_SUBJECT') ? VAPID_SUBJECT : '',
            'publicKey'  => defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '',
            'privateKey' => defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : ''
        ]
    ];
    $webPush = new \Minishlink\WebPush\WebPush($authWeb);

    $webPayload = buildWebPushPayload($title, $body, $url, $type, $targetId, $action);
    $badEndpoints = [];

    foreach ($subs as $s) {
        if (PushHelper::isMobilePlatform($s['platform'] ?? '')) {
            if (!$fcm_auth) $fcm_auth = getFCMAccessToken();
            if (!$fcm_auth || !$fcm_auth['token']) continue;

            $message = buildFcmMessageArray($s['endpoint'], $title, $body, $url, $type, $targetId, $action, $channelKey);

            $ch = curl_init("https://fcm.googleapis.com/v1/projects/{$fcm_auth['project_id']}/messages:send");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $fcm_auth['token'],
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message, JSON_UNESCAPED_UNICODE));
            $res = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode == 200) {
                $has_success = true;
            } elseif (in_array($httpcode, [404, 410])) {
                $badEndpoints[] = $s['endpoint'];
            }
        } else {
            if (empty($s['p256dh']) || empty($s['auth'])) continue;
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint'  => $s['endpoint'],
                'publicKey' => $s['p256dh'],
                'authToken' => $s['auth']
            ]);
            $webPush->queueNotification($subscription, $webPayload);
        }
    }

    foreach ($webPush->flush() as $report) {
        if ($report->isSuccess()) {
            $has_success = true;
        } else {
            if ($report->isSubscriptionExpired()) {
                $badEndpoints[] = (string)$report->getEndpoint();
            }
        }
    }

    if (!empty($badEndpoints)) {
        $badEndpoints = array_values(array_unique($badEndpoints));
        $in  = str_repeat('?,', count($badEndpoints) - 1) . '?';
        $stmtDel = $pdo->prepare("DELETE FROM push_subscription WHERE endpoint IN ($in)");
        $stmtDel->execute($badEndpoints);
    }

    return $has_success;
}

/**
 * Gửi Bulk Push Notification cho danh sách nhiều Jobs song song
 */
function sendBulkPushEx(PDO $pdo, array $pushDataList): bool {
    if (empty($pushDataList)) return true;

    $userIds = array_values(array_unique(array_column($pushDataList, 'user_id')));
    if (empty($userIds)) return false;

    $inQuery = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $pdo->prepare("SELECT user_id, endpoint, p256dh, auth, platform FROM push_subscription WHERE user_id IN ($inQuery)");
    $stmt->execute($userIds);
    $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $subsByUser = [];
    foreach ($subs as $s) {
        $subsByUser[$s['user_id']][] = $s;
    }

    $authWeb = [
        'VAPID' => [
            'subject'    => defined('VAPID_SUBJECT') ? VAPID_SUBJECT : '',
            'publicKey'  => defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '',
            'privateKey' => defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : ''
        ]
    ];
    $webPush = new \Minishlink\WebPush\WebPush($authWeb);

    $has_success = false;
    $fcm_auth = null;
    $mh = curl_multi_init();
    $curl_handles = [];
    $chToEndpoint = [];
    $badEndpoints = [];

    foreach ($pushDataList as $job) {
        $uid        = $job['user_id'];
        $title      = $job['title'] ?? 'Thông báo';
        $body       = $job['body'] ?? '';
        $url        = $job['url'] ?? '/';
        $type       = $job['type'] ?? 'GENERAL';
        $targetId   = (string)($job['target_id'] ?? '');
        $action     = $job['action'] ?? 'open_url';
        $channelKey = $job['channel_key'] ?? 'default_channel';

        if (empty($subsByUser[$uid])) continue;

        $webPayload = buildWebPushPayload($title, $body, $url, $type, $targetId, $action);

        foreach ($subsByUser[$uid] as $s) {
            if (PushHelper::isMobilePlatform($s['platform'] ?? '')) {
                if (!$fcm_auth) $fcm_auth = getFCMAccessToken();
                if ($fcm_auth && $fcm_auth['token']) {
                    $message = buildFcmMessageArray($s['endpoint'], $title, $body, $url, $type, $targetId, $action, $channelKey);

                    $ch = curl_init("https://fcm.googleapis.com/v1/projects/{$fcm_auth['project_id']}/messages:send");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $fcm_auth['token'],
                        'Content-Type: application/json'
                    ]);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message, JSON_UNESCAPED_UNICODE));

                    curl_multi_add_handle($mh, $ch);
                    $curl_handles[] = $ch;
                    $chToEndpoint[(int)$ch] = $s['endpoint'];
                }
            } else {
                if (empty($s['p256dh']) || empty($s['auth'])) continue;
                $subscription = \Minishlink\WebPush\Subscription::create([
                    'endpoint'  => $s['endpoint'],
                    'publicKey' => $s['p256dh'],
                    'authToken' => $s['auth']
                ]);
                $webPush->queueNotification($subscription, $webPayload);
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
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code == 200) {
                $has_success = true;
            } elseif (in_array($code, [404, 410])) {
                $chId = (int)$ch;
                if (isset($chToEndpoint[$chId])) {
                    $badEndpoints[] = $chToEndpoint[$chId];
                }
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
    }
    curl_multi_close($mh);

    foreach ($webPush->flush() as $report) {
        if ($report->isSuccess()) {
            $has_success = true;
        } else {
            if ($report->isSubscriptionExpired()) {
                $badEndpoints[] = (string)$report->getEndpoint();
            }
        }
    }

    if (!empty($badEndpoints)) {
        $badEndpoints = array_values(array_unique($badEndpoints));
        $in  = str_repeat('?,', count($badEndpoints) - 1) . '?';
        $stmtDel = $pdo->prepare("DELETE FROM push_subscription WHERE endpoint IN ($in)");
        $stmtDel->execute($badEndpoints);
    }

    return $has_success;
}

if (!function_exists('sendPushToUser')) {
    function sendPushToUser($pdo, $user_id, $title, $body, $url = '/', $type = 'GENERAL', $targetId = '', $action = 'open_url', $channelKey = 'default_channel') {
        return sendPushToUserEx($pdo, (int)$user_id, $title, $body, $url, $type, $targetId, $action, $channelKey);
    }
}

if (!function_exists('sendBulkPush')) {
    function sendBulkPush($pdo, $pushDataList) {
        return sendBulkPushEx($pdo, $pushDataList);
    }
}
?>
