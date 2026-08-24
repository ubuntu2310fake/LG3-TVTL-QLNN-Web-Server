<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Unauthorized']); exit;
}

$chunk = $_FILES['chunk_data']['tmp_name'] ?? '';
$fileName = preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_POST['file_name'] ?? '');
$chunkIndex = (int)($_POST['chunk_index'] ?? 0);
$totalChunks = (int)($_POST['total_chunks'] ?? 0);

if (!$chunk || !$fileName) {
    echo json_encode(['status' => 'error', 'msg' => __('missing_chunk_data', 'Thiếu dữ liệu Chunk')]); exit;
}

$tempDir = '../static/uploads/temp_chunks/';
$hlsDir = '../static/uploads/hls_videos/';

if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
if (!is_dir($hlsDir)) mkdir($hlsDir, 0777, true);

$tempFilePath = $tempDir . 'tmp_' . $fileName;

// 1. Nhận và dán các mảnh 8MB lại với nhau
$out = fopen($tempFilePath, $chunkIndex == 0 ? "wb" : "ab");
if ($out) {
    $in = fopen($chunk, "rb");
    if ($in) {
        while ($buff = fread($in, 4096)) fwrite($out, $buff);
    }
    fclose($in); fclose($out);
}

// 2. KHI MẢNH CUỐI CÙNG ĐÃ LÊN XONG -> KÍCH HOẠT TIẾN TRÌNH RENDER NGẦM
if ($chunkIndex == $totalChunks - 1) {
    $taskId = uniqid('vid_');
    $targetPath = $hlsDir . $taskId . '/';
    mkdir($targetPath, 0777, true);
    
    $progressFile = $targetPath . 'progress.txt';
    $logFile = $targetPath . 'log.txt';
    
    // Lấy thời lượng Video để tính phần trăm
    $durationCmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($tempFilePath);
    $duration = trim(shell_exec($durationCmd));
    file_put_contents($targetPath . 'duration.txt', $duration);
    
    // Quét xem Video có tiếng không
    $audioCheckCmd = "ffprobe -i " . escapeshellarg($tempFilePath) . " -show_streams -select_streams a -loglevel error";
    $hasAudio = trim(shell_exec($audioCheckCmd)) !== '';
    
    // ========================================================
    // LỆNH FFMPEG "STREAM COPY" - COPY HÌNH (0% CPU) + CHUẨN HÓA TIẾNG
    // ========================================================
    
    // Nếu có tiếng thì ép về AAC 128k (Rất nhẹ), không có tiếng thì bỏ qua
    $audioCmd = $hasAudio ? "-c:a aac -b:a 128k" : "";
    
    // Lệnh mới: Dùng -c:v copy (KHÔNG RENDER LẠI VIDEO)
    $ffmpegCmd = "ffmpeg -y -i " . escapeshellarg($tempFilePath) . " "
               . "-c:v copy " 
               . "{$audioCmd} "
               . "-f hls -hls_time 10 -hls_playlist_type vod -hls_flags independent_segments "
               . "-hls_segment_filename " . escapeshellarg($targetPath . "segment_%03d.ts") . " "
               . "-progress " . escapeshellarg($progressFile) . " " 
               . escapeshellarg($targetPath . "index.m3u8");

    // Đẩy tiến trình chạy ngầm & Tự động xóa file Gốc
    $fullCmd = "nohup sh -c " . escapeshellarg($ffmpegCmd . " ; rm -f " . escapeshellarg($tempFilePath)) . " > " . escapeshellarg($logFile) . " 2>&1 &";
    shell_exec($fullCmd);
    
    echo json_encode(['status' => 'processing', 'task_id' => $taskId]);
} else {
    echo json_encode(['status' => 'success', 'msg' => __('received_chunk_prefix', "Đã nhận mảnh ") . $chunkIndex]);
}
?>