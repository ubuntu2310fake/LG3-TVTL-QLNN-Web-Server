<?php
// traffic_monitor.php
require_once 'includes/config.php';
checkRole(['ADMIN']);

// 1. XỬ LÝ LOGIC BACKEND
$current_range = $_GET['range'] ?? '1h';

// Xác định mốc thời gian truy vấn
$now = new DateTime();
if ($current_range === '24h') {
    $startTime = (clone $now)->modify('-24 hours');
} elseif ($current_range === '7d') {
    $startTime = (clone $now)->modify('-7 days');
} else {
    // Default 1h
    $startTime = (clone $now)->modify('-1 hour');
}
$startTimeUnix = $startTime->getTimestamp();

$logPath = '/var/www/html/logs/access_system.log';

try {
    // Đọc tất cả logs trong khoảng thời gian xác định từ file hệ thống
    $logs = get_recent_system_logs($logPath, $startTimeUnix);

    $total_requests = count($logs);
    $unique_ips = [];
    $total_latency = 0;
    $error_count = 0;
    $grouped = [];

    foreach ($logs as $log) {
        $logTime = new DateTime($log['time']);
        
        if ($current_range === '24h') {
            $timeLabel = $logTime->format('H:00');
        } elseif ($current_range === '7d') {
            $timeLabel = $logTime->format('Y-m-d');
        } else {
            $timeLabel = $logTime->format('H:i');
        }
        
        $ip = $log['ip'];
        $unique_ips[$ip] = true;
        $total_latency += $log['duration'];
        if ($log['status'] >= 400) {
            $error_count++;
        }
        
        if (!isset($grouped[$timeLabel])) {
            $grouped[$timeLabel] = [
                'requests' => 0,
                'unique_ips' => [],
                'total_response_time' => 0
            ];
        }
        
        $grouped[$timeLabel]['requests']++;
        $grouped[$timeLabel]['unique_ips'][$ip] = true;
        $grouped[$timeLabel]['total_response_time'] += $log['duration'];
    }

    $unique_visitors = count($unique_ips);
    $avg_latency = $total_requests > 0 ? round($total_latency / $total_requests, 2) : 0;
    $error_count_num = $error_count;
    $error_rate = $total_requests > 0 ? round(($error_count / $total_requests) * 100, 2) : 0;

    // Dữ liệu biểu đồ (Stats)
    $stats = [];
    ksort($grouped);

    foreach ($grouped as $timeLabel => $data) {
        $displayLabel = $timeLabel;
        if ($current_range === '7d') {
            $displayLabel = date('d/m', strtotime($timeLabel));
        }

        $stats[] = [
            'time' => $displayLabel,
            'requests' => $data['requests'],
            'unique_ips' => count($data['unique_ips']),
            'avg_response_time' => round($data['total_response_time'] / $data['requests'], 2)
        ];
    }

    // Đọc 150 dòng logs mới nhất để stream lên giao diện Admin
    $recent_logs = get_recent_system_logs($logPath, null, 150);

} catch (Exception $e) {
    $total_requests = 0; $unique_visitors = 0; $avg_latency = 0; $error_rate = 0; $stats = []; $recent_logs = [];
}

require_once 'views/traffic_monitor_view.php';