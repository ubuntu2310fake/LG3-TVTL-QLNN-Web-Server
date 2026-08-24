<?php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['ADMIN', 'TEACHER', 'RED_FLAG'])) {
    echo json_encode(['status' => 'error', 'msg' => __('no_permission', 'Không có quyền truy cập')]); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $week = $input['week'];
    $scores = $input['scores'];
    
    $current_school_year = get_current_school_year($pdo);

    try {
        $pdo->beginTransaction();
        foreach ($scores as $s) {
            $cid = $s['class_id']; 
            $score = (float)$s['score']; 
            $count = (int)$s['count'];
            
            $stmtCheck = $pdo->prepare("SELECT id FROM academic_score WHERE class_id = ? AND week_number = ? AND school_year = ?");
            $stmtCheck->execute([$cid, $week, $current_school_year]);
            
            if ($stmtCheck->fetchColumn()) {
                $pdo->prepare("UPDATE academic_score SET total_score=?, period_count=? WHERE class_id=? AND week_number=? AND school_year=?")->execute([$score, $count, $cid, $week, $current_school_year]);
            } else {
                $pdo->prepare("INSERT INTO academic_score (class_id, week_number, total_score, period_count, school_year) VALUES (?, ?, ?, ?, ?)")->execute([$cid, $week, $score, $count, $current_school_year]);
            }
        }
        $pdo->commit();

        require_once '../includes/push_helper.php';
        $pushedClasses = [];
        foreach ($scores as $s) {
            $cid = (int)$s['class_id'];
            if (!empty($cid) && !in_array($cid, $pushedClasses)) {
                $pushedClasses[] = $cid;
                $stmtC = $pdo->prepare("SELECT name FROM classroom WHERE id = ?");
                $stmtC->execute([$cid]);
                $cName = $stmtC->fetchColumn() ?: 'Lớp';
                enqueueNotification($pdo, 'ACADEMIC_SCORE', [
                    'class_id'   => $cid,
                    'class_name' => $cName,
                    'week'       => (string)$week
                ]);
            }
        }

        echo json_encode(['status' => 'success', 'msg' => __('academic_score_saved', 'Đã lưu điểm học tập thành công!')]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

$week = $_GET['week'] ?? get_current_week($pdo);
$current_school_year = get_current_school_year($pdo);

$classes = $pdo->query("SELECT id, name FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%' ORDER BY grade ASC, LENGTH(name) ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmtData = $pdo->prepare("SELECT class_id, total_score, period_count FROM academic_score WHERE week_number = ? AND school_year = ?");
$stmtData->execute([$week, $current_school_year]);
$scoreMap = []; 
foreach ($stmtData->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $scoreMap[$r['class_id']] = $r;
}

$result = [];
foreach ($classes as $c) {
    $cid = $c['id'];
    $result[] = [
        'class_id' => $cid, 
        'class_name' => $c['name'],
        'score' => isset($scoreMap[$cid]) ? (float)$scoreMap[$cid]['total_score'] : 0,
        'count' => isset($scoreMap[$cid]) ? (int)$scoreMap[$cid]['period_count'] : 0
    ];
}
$labels = [
    'nn' => 'NN', 'nn_en' => 'Behavior',
    'ht' => 'HT', 'ht_en' => 'Academic',
    'vpbs' => 'VPBS', 'vpbs_en' => 'Penalty',
    'tong_tiet' => 'Tổng tiết', 'tong_tiet_en' => 'Total Periods',
    'thuong' => 'Thưởng', 'thuong_en' => 'Bonus',
    'nhom_thi_dua' => 'Nhóm Thi Đua', 'nhom_thi_dua_en' => 'Competition Group'
];
echo json_encode(['status' => 'success', 'current_week' => $week, 'data' => $result, 'labels' => $labels]);
?>