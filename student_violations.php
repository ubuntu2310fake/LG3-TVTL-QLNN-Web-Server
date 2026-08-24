<?php
// student_violations.php
require_once 'includes/config.php';
require_once 'includes/functions.php'; // Đã fix: Include functions.php để gọi hàm get_current_week

if (!isset($_SESSION['user'])) { header("Location: login.php"); exit; }

$user = $_SESSION['user'];
// Đã fix: Gọi hàm lấy tuần hiện tại thay vì mặc định là 1
$week = isset($_GET['week']) ? (int)$_GET['week'] : get_current_week($pdo); 

// Lấy Student
$stmtS = $pdo->prepare("SELECT * FROM student WHERE code = ?");
$stmtS->execute([$user['username']]);
$student = $stmtS->fetch(PDO::FETCH_ASSOC);

$my_vios = []; $class_vios = []; $total_minus = 0;
$matrix_data = []; $matrix_total = 0; $matrix_note = ""; $bonus_score = 0;

if ($student) {
    $current_school_year = get_current_school_year($pdo);

    // 1. Vi phạm cá nhân
    $stmtMy = $pdo->prepare("SELECT r.*, v.content as recorded_violation_name, v.content_en as recorded_violation_name_en FROM violation_record r LEFT JOIN violation_type v ON r.violation_type_id=v.id WHERE student_id=? AND week_number=? AND (r.is_deleted = 0 OR r.is_deleted IS NULL) AND r.school_year=? ORDER BY date_created DESC");
    $stmtMy->execute([$student['id'], $week, $current_school_year]);
    $my_vios = $stmtMy->fetchAll(PDO::FETCH_ASSOC);
    foreach($my_vios as $v) $total_minus += $v['recorded_points'];

    // 2. Vi phạm lớp
    $stmtCls = $pdo->prepare("SELECT r.*, s.name as student_name, v.content as violation_name, v.content_en as violation_name_en FROM violation_record r LEFT JOIN student s ON r.student_id=s.id LEFT JOIN violation_type v ON r.violation_type_id=v.id WHERE r.class_id=? AND r.week_number=? AND (r.is_deleted = 0 OR r.is_deleted IS NULL) AND r.school_year=? ORDER BY r.date_created DESC");
    $stmtCls->execute([$student['class_id'], $week, $current_school_year]);
    $class_vios = $stmtCls->fetchAll(PDO::FETCH_ASSOC);

    // 3. Matrix (Logic tính điểm sổ đầu bài)
    $stmtClsCols = $pdo->query("SELECT short_code, max_penalty_points FROM violation_type WHERE scope = 'CLASS' ORDER BY id ASC");
    $dbScores = [];
    while ($row = $stmtClsCols->fetch(PDO::FETCH_ASSOC)) {
        $dbScores[$row['short_code']] = (float)$row['max_penalty_points'];
    }
    $MAX = [];
    $defaultOrder = ["SS", "VS", "CSVC", "TB", "XE", "DP", "SV", "THE", "DT"];
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

    // Lấy lỗi trừ điểm CLASS
    $recs = $pdo->prepare("SELECT r.recorded_points, r.date_created, r.recorded_violation_name, v.short_code FROM violation_record r JOIN violation_type v ON r.violation_type_id=v.id WHERE r.class_id=? AND r.week_number=? AND v.scope='CLASS' AND (r.is_deleted = 0 OR r.is_deleted IS NULL) AND r.school_year=?");
    $recs->execute([$student['class_id'], $week, $current_school_year]);
    
    foreach($recs->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $wd = date('N', strtotime($r['date_created'])) + 1; // Mon=1 -> 2
        if (preg_match('/\(T(\d)\)/', $r['recorded_violation_name'], $matches)) {
            $wd = (int)$matches[1];
        }
        if($wd > 7) $wd = 7;
        $c = $r['short_code'];
        if(isset($day_data[$wd][$c])) $day_data[$wd][$c] = max(0, $day_data[$wd][$c] - $r['recorded_points']);
    }

    // Quy định THPT Lạng Giang số 3: Tuần thi đua bắt đầu từ Thứ 7 tuần trước (7), sau đó đến Thứ 2 -> Thứ 6 (2, 3, 4, 5, 6)
    foreach([7, 2, 3, 4, 5, 6] as $d) {
        $row = ['label'=>$d, 'scores'=>[], 'total'=>0];
        foreach($ORDER as $k) {
            $val = $day_data[$d][$k];
            $row['scores'][] = ['val'=>$val, 'max'=>$MAX[$k]];
            $row['total'] += $val;
        }
        $matrix_total += $row['total'];
        $matrix_data[] = $row;
    }

    // Lấy Note & Bonus
    $aca = $pdo->prepare("SELECT note, bonus_score FROM academic_score WHERE class_id=? AND week_number=? AND school_year=?");
    $aca->execute([$student['class_id'], $week, $current_school_year]);
    $info = $aca->fetch();
    if($info) { $matrix_note = $info['note']; $bonus_score = $info['bonus_score']; }
}

require_once 'views/student_violations_view.php';