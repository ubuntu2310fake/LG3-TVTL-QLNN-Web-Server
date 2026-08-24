<?php
// api/exam_data_api.php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

// Công khai tra cứu điểm thi, không yêu cầu đăng nhập


$action = $_GET['action'] ?? '';

try {
    // THÊM MỚI: API LẤY DANH SÁCH KỲ THI CHO BỘ LỌC
    if ($action === 'list') {
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'vi';
        $stmt = $pdo->query("SELECT id, exam_name, exam_name_en FROM lg3_exams ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Return localized name
        $data = array_map(function($r) use ($lang) {
            return [
                'id' => $r['id'],
                'exam_name' => ($lang === 'en' && !empty($r['exam_name_en'])) ? $r['exam_name_en'] : $r['exam_name']
            ];
        }, $rows);
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

    $examId = (int)($_GET['exam_id'] ?? 0);
    if (!$examId) {
        echo json_encode(['status' => 'error', 'msg' => __('invalid_exam_id', 'ID kỳ thi không hợp lệ')]);
        exit;
    }

    // 1. LẤY CẤU HÌNH MÔN VÀ GỘP NHÓM (TN/TL/TONG)
    $stmtConf = $pdo->prepare("SELECT subject_key, col_code, score_type FROM lg3_exam_config WHERE exam_id = ? ORDER BY id ASC");
    $stmtConf->execute([$examId]);
    $rawConfig = $stmtConf->fetchAll(PDO::FETCH_ASSOC);

    $groupedConfig = [];
    foreach ($rawConfig as $item) {
        $sub = $item['subject_key'];
        $type = $item['score_type']; 
        $groupedConfig[$sub][$type] = $item['col_code'];
    }

    // 2. LẤY DANH SÁCH ĐIỂM (Fix Sort: Ưu tiên chiều dài tên lớp -> Tên Lớp -> SBD)
    $stmtScores = $pdo->prepare("
        SELECT * FROM lg3_exam_scores 
        WHERE exam_id = ? 
        ORDER BY LENGTH(class_name) ASC, class_name ASC, sbd ASC
    ");
    $stmtScores->execute([$examId]);
    $scores = $stmtScores->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'config' => $groupedConfig,
        'data' => $scores
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>