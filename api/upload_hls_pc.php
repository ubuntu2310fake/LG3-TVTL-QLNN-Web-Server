<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Unauthorized']); exit;
}

$taskId = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['task_id'] ?? '');
if (!$taskId) {
    echo json_encode(['status' => 'error', 'msg' => __('missing_task_id', 'Thiếu Task ID')]); exit;
}

$targetDir = '../static/uploads/hls_videos/' . $taskId . '/';
if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

// Nhận mảng file (.ts và .m3u8) từ Trình duyệt
if (isset($_FILES['files'])) {
    $fileCount = count($_FILES['files']['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $name = basename($_FILES['files']['name'][$i]);
            move_uploaded_file($_FILES['files']['tmp_name'][$i], $targetDir . $name);
        }
    }
}

echo json_encode([
    'status' => 'success', 
    'url' => 'static/uploads/hls_videos/' . $taskId . '/index.m3u8'
]);
?>