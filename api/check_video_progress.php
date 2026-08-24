<?php
header('Content-Type: application/json');

$taskId = $_GET['task_id'] ?? '';
if (!$taskId || strpos($taskId, '..') !== false) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid Task ID']); exit;
}

$dir = '../static/uploads/hls_videos/' . $taskId . '/';
$durationFile = $dir . 'duration.txt';
$progressFile = $dir . 'progress.txt';
$masterFile = $dir . 'index.m3u8';

// 1. Kiểm tra xem FFmpeg đã nhả ra file Master Playlist chưa (Dấu hiệu hoàn tất)
if (file_exists($masterFile)) {
    // Check thêm xem file progress đã báo 'end' chưa cho chắc chắn
    if (!file_exists($progressFile) || strpos(file_get_contents($progressFile), 'progress=end') !== false) {
        echo json_encode([
            'status' => 'done', 
            'url' => '/static/uploads/hls_videos/' . $taskId . '/index.m3u8'
        ]);
        exit;
    }
}

// 2. Tính toán Phần trăm (%) hiện tại
$percent = 0;
if (file_exists($durationFile) && file_exists($progressFile)) {
    $duration = (float)file_get_contents($durationFile);
    $content = file_get_contents($progressFile);
    
    // Tìm thông số out_time_us (micro-giây) mà FFmpeg sinh ra
    preg_match_all('/out_time_us=(\d+)/', $content, $matches_us);
    if (!empty($matches_us[1])) {
        $currentSec = end($matches_us[1]) / 1000000;
        if ($duration > 0) {
            $percent = round(($currentSec / $duration) * 100);
            if ($percent > 99) $percent = 99; // Neo ở 99% cho đến khi ra file index.m3u8
        }
    }
}

echo json_encode(['status' => 'processing', 'percent' => $percent]);
?>