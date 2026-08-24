<?php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') { echo json_encode(['status'=>'error', 'msg'=>__('no_permission', 'Không có quyền')]); exit; }

$current_range = $_GET['range'] ?? '1h';
$now = new DateTime();
if ($current_range === '24h') {
    $startTime = (clone $now)->modify('-24 hours');
} elseif ($current_range === '7d') {
    $startTime = (clone $now)->modify('-7 days');
} else {
    $startTime = (clone $now)->modify('-1 hour');
}
$startTimeUnix = $startTime->getTimestamp();

$logPath = '/var/www/html/logs/access_system.log';

try {
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
    $error_rate = $total_requests > 0 ? round(($error_count / $total_requests) * 100, 2) : 0;

    $stats = [];
    ksort($grouped);

    foreach ($grouped as $timeLabel => $data) {
        $displayLabel = $timeLabel;
        if ($current_range === '7d') {
            $displayLabel = date('d/m', strtotime($timeLabel));
        }
        $stats[] = [
            'time' => $displayLabel,
            'requests' => $data['requests']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'overview' => [
            'total_requests' => $total_requests,
            'unique_visitors' => $unique_visitors,
            'avg_latency' => $avg_latency,
            'error_rate' => $error_rate
        ],
        'stats' => $stats
    ]);
} catch (Exception $e) {
    echo json_encode(['status'=>'success', 'overview'=>['total_requests'=>0,'unique_visitors'=>0,'avg_latency'=>0,'error_rate'=>0], 'stats'=>[]]);
}
?>