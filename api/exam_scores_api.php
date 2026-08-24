<?php
// api/exam_scores_api.php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$examId = (int)($_GET['exam_id'] ?? 0);
if (!$examId) {
    echo json_encode(['status' => 'error', 'msg' => __('exam_not_found', 'Kỳ thi không tồn tại')]);
    exit;
}

try {
    // 1. Lấy cấu hình môn (Để biết col1 là môn gì...)
    $stmtConf = $pdo->prepare("SELECT col_code, subject_name FROM lg3_exam_config WHERE exam_id = ?");
    $stmtConf->execute([$examId]);
    $config = $stmtConf->fetchAll(PDO::FETCH_KEY_PAIR);

    // 2. Lấy danh sách điểm
    $stmtScores = $pdo->prepare("SELECT * FROM lg3_exam_scores WHERE exam_id = ? ORDER BY class_name ASC, student_name ASC");
    $stmtScores->execute([$examId]);
    $scores = $stmtScores->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'config' => $config,
        'data' => $scores
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}