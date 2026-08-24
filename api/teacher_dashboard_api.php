<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['TEACHER', 'ADMIN', 'RED_FLAG'])) {
    echo json_encode(['status' => 'error', 'msg' => __('no_permission', 'Không có quyền truy cập')]); exit;
}

$week = $_GET['week'] ?? get_current_week($pdo);
$class_id = isset($_GET['class_id']) && $_GET['class_id'] > 0 ? (int)$_GET['class_id'] : ($_SESSION['user']['homeroom_class_id'] ?? 0);
if (!$class_id && $_SESSION['user']['role'] === 'ADMIN') {
    $stmt = $pdo->query("SELECT id FROM classroom LIMIT 1");
    $class_id = (int)$stmt->fetchColumn();
}

$input = json_decode(file_get_contents('php://input'), true);
if (isset($input['action'])) {
    if ($input['action'] == 'update_exemption' && $_SESSION['user']['role'] !== 'RED_FLAG') {
        $pdo->prepare("UPDATE student SET has_exemption=?, exemption_reason=? WHERE id=?")
            ->execute([$input['is_exempt']?1:0, $input['reason'], $input['student_id']]);
        echo json_encode(['status'=>'success']); exit;
    }
    if ($input['action'] == 'delete_violation' && $_SESSION['user']['role'] !== 'RED_FLAG') {
        $stmtChk = $pdo->prepare("SELECT school_year FROM violation_record WHERE id = ?");
        $stmtChk->execute([$input['id']]);
        $rowChk = $stmtChk->fetch(PDO::FETCH_ASSOC);

        $pdo->prepare("UPDATE violation_record SET is_deleted=1 WHERE id=?")->execute([$input['id']]);
        
        require_once __DIR__ . '/../includes/sse_push.php';
        sse_push($pdo, 'violation_deleted', ['id' => (int)$input['id']]);

        echo json_encode(['status'=>'success']); exit;
    }
}

if ($class_id == 0) { echo json_encode(['status' => 'success', 'has_class' => false]); exit; }

$current_school_year = get_current_school_year($pdo);

// 1. Lấy thông tin lớp
$my_class = $pdo->prepare("SELECT * FROM classroom WHERE id = ?"); $my_class->execute([$class_id]); $my_class = $my_class->fetch(PDO::FETCH_ASSOC);
$teacher = $pdo->prepare("SELECT full_name FROM users WHERE homeroom_class_id = ? LIMIT 1"); $teacher->execute([$class_id]); $teacher = $teacher->fetchColumn() ?: __('unassigned', 'Chưa phân công');

// 2. Học sinh & Vi phạm
$students = $pdo->prepare("SELECT id, name, code, has_exemption, exemption_reason FROM student WHERE class_id = ? ORDER BY SUBSTRING_INDEX(name, ' ', -1) ASC");
$students->execute([$class_id]); $students = $students->fetchAll(PDO::FETCH_ASSOC);

$sqlVio = "SELECT vr.id, vr.recorded_violation_name, vt.content_en AS recorded_violation_name_en, vr.recorded_points, vr.date_created, vr.reporter, s.name as student_name 
           FROM violation_record vr LEFT JOIN student s ON vr.student_id = s.id 
           LEFT JOIN violation_type vt ON vr.violation_type_id = vt.id
           WHERE vr.class_id = ? AND vr.week_number = ? AND (vr.is_deleted = 0 OR vr.is_deleted IS NULL) AND vr.school_year = ? ORDER BY vr.date_created DESC";
$stmtVio = $pdo->prepare($sqlVio); $stmtVio->execute([$class_id, $week, $current_school_year]); $violations = $stmtVio->fetchAll(PDO::FETCH_ASSOC);

// 3. Sổ đoàn trường (Matrix)
$stmtClsCols = $pdo->query("SELECT short_code, max_penalty_points FROM violation_type WHERE scope = 'CLASS' ORDER BY id ASC");
$dbScores = [];
while ($row = $stmtClsCols->fetch(PDO::FETCH_ASSOC)) {
    $dbScores[$row['short_code']] = (float)($row['max_penalty_points'] !== null ? $row['max_penalty_points'] : 1.0);
}
$defaultOrder = ["SS", "VS", "CSVC", "TB", "XE", "DP", "SV", "THE", "DT"];
$MAX = [];
foreach ($defaultOrder as $code) {
    if (isset($dbScores[$code])) {
        $MAX[$code] = $dbScores[$code];
        unset($dbScores[$code]);
    }
}
foreach ($dbScores as $code => $max) {
    $MAX[$code] = $max;
}
if (empty($MAX)) {
    $MAX = ['SS'=>1, 'VS'=>1, 'CSVC'=>1, 'TB'=>1, 'XE'=>1, 'DP'=>2, 'SV'=>1, 'THE'=>1, 'DT'=>1];
}
$ORDER = array_keys($MAX);
$day_data = []; for($i=2;$i<=7;$i++) $day_data[$i] = $MAX;

$recs = $pdo->prepare("SELECT r.recorded_points, r.date_created, v.short_code FROM violation_record r JOIN violation_type v ON r.violation_type_id=v.id WHERE r.class_id=? AND r.week_number=? AND v.scope='CLASS' AND (r.is_deleted = 0 OR r.is_deleted IS NULL) AND r.school_year=?");
$recs->execute([$class_id, $week, $current_school_year]);
foreach($recs->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $wd = date('N', strtotime($r['date_created'])) + 1; if($wd > 7) $wd = 7;
    $c = $r['short_code']; if(isset($day_data[$wd][$c])) $day_data[$wd][$c] = max(0, $day_data[$wd][$c] - $r['recorded_points']);
}
$matrix_data = []; $matrix_total = 0;
// Quy định THPT Lạng Giang số 3: Tuần thi đua bắt đầu từ Thứ 7 tuần trước (7), sau đó đến Thứ 2 -> Thứ 6 (2, 3, 4, 5, 6)
foreach([7, 2, 3, 4, 5, 6] as $d) {
    $row = ['day' => "T$d", 'scores' => [], 'total' => 0];
    foreach($ORDER as $k) { $row['scores'][$k] = $day_data[$d][$k]; $row['total'] += $day_data[$d][$k]; }
    $matrix_total += $row['total']; $matrix_data[] = $row;
}
$aca = $pdo->prepare("SELECT note, bonus_score FROM academic_score WHERE class_id=? AND week_number=? AND school_year=?"); $aca->execute([$class_id, $week, $current_school_year]);
$info = $aca->fetch(PDO::FETCH_ASSOC);

// 4. Lấy Cảnh báo Tâm lý trực tiếp từ DB
$psychology_logs = []; $student_codes = array_column($students, 'code');
if (!empty($student_codes)) {
    try {
        $placeholders = implode(',', array_fill(0, count($student_codes), '?'));
        $stmt = $pdo->prepare("SELECT id, username, full_name, question, advice, risk_level, created_at, school_year 
                               FROM psychology_logs 
                               WHERE username IN ($placeholders) 
                               ORDER BY created_at DESC 
                               LIMIT 50");
        $stmt->execute($student_codes);
        $p_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $student_map = array_column($students, 'name', 'code');
        foreach ($p_data as &$log) {
            if ($log['created_at']) {
                $dt = new DateTime($log['created_at']);
                $log['created_at'] = $dt->format('H:i d/m');
            }
            $log['student_name'] = $student_map[$log['username']] ?? ($log['full_name'] ?? __('anonymous', 'Ẩn danh'));
        }
        $psychology_logs = $p_data;
    } catch (Exception $e) {
        $psychology_logs = [];
    }
}

echo json_encode([
    'status' => 'success', 'has_class' => true, 'current_week' => $week,
    'class_info' => ['name' => $my_class['name'], 'teacher' => $teacher],
    'students' => $students, 'violations' => $violations,
    'matrix' => ['data' => $matrix_data, 'total' => $matrix_total, 'bonus' => $info ? (float)$info['bonus_score'] : 0, 'note' => $info ? $info['note'] : ''],
    'psychology' => $psychology_logs,
    'labels' => [
        'nn' => 'NN', 'nn_en' => 'Behavior',
        'ht' => 'HT', 'ht_en' => 'Academic',
        'vpbs' => 'VPBS', 'vpbs_en' => 'Penalty',
        'tong_tiet' => 'Tổng tiết', 'tong_tiet_en' => 'Total Periods',
        'thuong' => 'Thưởng', 'thuong_en' => 'Bonus',
        'nhom_thi_dua' => 'Nhóm Thi Đua', 'nhom_thi_dua_en' => 'Competition Group'
    ]
], JSON_UNESCAPED_UNICODE);
?>