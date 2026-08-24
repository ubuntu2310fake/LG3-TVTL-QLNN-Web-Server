<?php
// input_academic.php
require_once 'includes/config.php';
checkRole(['ADMIN', 'TEACHER', 'RED_FLAG']); 

// 1. XỬ LÝ LƯU DỮ LIỆU (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean(); header('Content-Type: application/json');
    try {
        $week = $_POST['week'];
        $class_ids = $_POST['class_ids'] ?? [];

        $pdo->beginTransaction();
        
        foreach ($class_ids as $cid) {
            // [FIX LỖI TRUNCATED TẠI ĐÂY]
            // Kiểm tra nếu rỗng thì gán bằng 0, ngược lại ép kiểu số
            $raw_score = $_POST["score_$cid"] ?? '';
            $score = ($raw_score === '') ? 0 : (float)$raw_score;

            $raw_count = $_POST["count_$cid"] ?? '';
            $count = ($raw_count === '') ? 0 : (int)$raw_count;

            $current_school_year = get_current_school_year($pdo);
            // Kiểm tra xem đã có dữ liệu chưa
            $stmtCheck = $pdo->prepare("SELECT id, school_year FROM academic_score WHERE class_id = ? AND week_number = ?");
            $stmtCheck->execute([$cid, $week]);
            $exists = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($exists) {

                // Update
                $stmt = $pdo->prepare("UPDATE academic_score SET total_score = ?, period_count = ? WHERE id = ?");
                $stmt->execute([$score, $count, $exists['id']]);
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO academic_score (class_id, week_number, total_score, period_count, school_year) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$cid, $week, $score, $count, $current_school_year]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'msg' => __('academic_saved', 'Đã lưu dữ liệu học tập!')]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// 2. LẤY DỮ LIỆU HIỂN THỊ (GET)
$current_week = get_current_week($pdo);
$view_week = isset($_GET['week']) ? (int)$_GET['week'] : $current_week;

// Lấy danh sách lớp (Sắp xếp chuẩn, loại trừ các lớp đã tốt nghiệp)
$classes = $pdo->query("SELECT * FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%' ORDER BY grade ASC, LENGTH(name) ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Lấy dữ liệu cũ của tuần đang chọn để fill vào form
$current_school_year = get_current_school_year($pdo);
$stmtData = $pdo->prepare("SELECT class_id, total_score, period_count FROM academic_score WHERE week_number = ? AND school_year = ?");
$stmtData->execute([$view_week, $current_school_year]);
$rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);

// Chuyển về dạng Map JS: { class_id: {score: ..., count: ...} }
$academic_data = [];
foreach ($rows as $r) {
    $academic_data[$r['class_id']] = [
        'score' => (float)$r['total_score'],
        'count' => (int)$r['period_count']
    ];
}

require_once 'views/input_academic_view.php';