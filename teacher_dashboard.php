<?php
session_start();
require_once 'includes/config.php'; 
require_once 'includes/functions.php';
require_once 'includes/sse_push.php';

// 1. KIỂM TRA QUYỀN VÀ XÓA CACHE TRÌNH DUYỆT
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser || !in_array($currentUser['role'], ['TEACHER', 'ADMIN', 'RED_FLAG'])) {
    header("Location: index.php");
    exit;
}

// 2. XỬ LÝ API/AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // API: Reset mật khẩu học sinh trong lớp
    if (isset($input['action']) && $input['action'] === 'reset_student_password') {
        try {
            $student_id = (int)($input['student_id'] ?? 0);
            if (!$student_id) throw new Exception(__('invalid_student', 'Học sinh không hợp lệ!'));

            $stmtTarget = $pdo->prepare("SELECT s.*, c.name as class_name FROM student s JOIN classroom c ON s.class_id = c.id WHERE s.id = ?");
            $stmtTarget->execute([$student_id]);
            $targetStudent = $stmtTarget->fetch(PDO::FETCH_ASSOC);

            if (!$targetStudent) throw new Exception(__('student_not_found', 'Không tìm thấy học sinh!'));

            // Xác định lớp của người thực hiện reset (Operator)
            $operatorClassId = $currentUser['homeroom_class_id'] ?? 0;
            if ($operatorClassId == 0 && !empty($currentUser['username'])) {
                $stmtOpClass = $pdo->prepare("SELECT class_id FROM student WHERE code = ?");
                $stmtOpClass->execute([$currentUser['username']]);
                $operatorClassId = (int)$stmtOpClass->fetchColumn();
            }

            // Phân quyền: Chỉ cho phép reset nếu thuộc CÙNG LỚP với học sinh (hoặc là ADMIN)
            if ($currentUser['role'] !== 'ADMIN' && $operatorClassId != $targetStudent['class_id']) {
                throw new Exception(__('no_permission_other_class', 'Bạn chỉ có quyền reset mật khẩu cho học sinh trong lớp của mình!'));
            }

            // Mật khẩu mặc định: $2y$10$5wYTD8mBMD1wTnHX4LWt3Or8pZqVVG./Fqa36xTWJgtrecQlVXSyO
            $defaultHash = '$2y$10$5wYTD8mBMD1wTnHX4LWt3Or8pZqVVG./Fqa36xTWJgtrecQlVXSyO';

            $stmtUpd = $pdo->prepare("UPDATE users SET password_hash = ?, is_default_password = 'on' WHERE username = ?");
            $stmtUpd->execute([$defaultHash, $targetStudent['code']]);

            if ($stmtUpd->rowCount() === 0) {
                $stmtIns = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, is_default_password) VALUES (?, ?, ?, 'STUDENT', 'on') ON DUPLICATE KEY UPDATE password_hash = ?, is_default_password = 'on'");
                $stmtIns->execute([$targetStudent['code'], $defaultHash, $targetStudent['name'], $defaultHash]);
            }

            echo json_encode(['status' => 'success', 'msg' => __('reset_password_success', 'Đã đặt lại mật khẩu mặc định thành công cho học sinh ') . $targetStudent['name'] . '!']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // API: Cập nhật miễn trừ
    if (isset($input['action']) && $input['action'] === 'update_exemption') {
        try {
            if ($currentUser['role'] === 'RED_FLAG') throw new Exception(__('no_permission', "Không có quyền"));
            $stmt = $pdo->prepare("UPDATE student SET has_exemption = ?, exemption_reason = ? WHERE id = ?");
            $stmt->execute([$input['is_exempt'] ? 1 : 0, $input['reason'], $input['student_id']]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }
    
    // API: Xóa vi phạm (Sử dụng $_POST thường nếu gửi form, hoặc JSON nếu fetch json)
    if (isset($_POST['action']) && $_POST['action'] === 'delete_violation') {
         try {
            if ($currentUser['role'] === 'RED_FLAG') throw new Exception(__('no_permission', "Không có quyền"));
            $stmt = $pdo->prepare("DELETE FROM violation_record WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            if ($stmt->rowCount() > 0) {
                sse_push($pdo, 'violation_deleted', ['id' => (int)$_POST['id']]);
            }
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }
}

// 3. LẤY DỮ LIỆU HIỂN THỊ
$week = $_GET['week'] ?? get_current_week($pdo);
$selected_week = $week;

// Lấy ID lớp chủ nhiệm (hoặc lớp của học sinh/cán bộ lớp)
$class_id = $_SESSION['user']['homeroom_class_id'] ?? 0;
if ($class_id == 0 && !empty($_SESSION['user']['username'])) {
    $stmtSClass = $pdo->prepare("SELECT class_id FROM student WHERE code = ?");
    $stmtSClass->execute([$_SESSION['user']['username']]);
    $class_id = (int)$stmtSClass->fetchColumn();
}

// =========================================================================
// PHẦN 1: LẤY THÔNG TIN LỚP VÀ GIÁO VIÊN
// =========================================================================
$my_class = null;
$main_teacher = null;
$students = [];

if ($class_id > 0) {
    // Lấy thông tin lớp
    $stmtClass = $pdo->prepare("SELECT * FROM classroom WHERE id = ?");
    $stmtClass->execute([$class_id]);
    $my_class = $stmtClass->fetch();

    if ($my_class) {
        // Lấy thông tin GVCN
        $stmtTeacher = $pdo->prepare("SELECT full_name FROM users WHERE homeroom_class_id = ? AND role IN ('TEACHER', 'ADMIN') LIMIT 1");
        $stmtTeacher->execute([$class_id]);
        $main_teacher = $stmtTeacher->fetch();

        // Lấy danh sách học sinh
        $stmtStudents = $pdo->prepare("SELECT * FROM student WHERE class_id = ? ORDER BY SUBSTRING_INDEX(name, ' ', -1) ASC");
        $stmtStudents->execute([$class_id]);
        $students = $stmtStudents->fetchAll();
    }
}

// =========================================================================
// PHẦN 2: LẤY DANH SÁCH VI PHẠM TRONG TUẦN
// =========================================================================
$violations = [];
if ($class_id > 0) {
    $sqlVio = "
        SELECT vr.*, s.name as student_name, vt.content_en AS recorded_violation_name_en
        FROM violation_record vr
        LEFT JOIN student s ON vr.student_id = s.id
        LEFT JOIN violation_type vt ON vr.violation_type_id = vt.id
        WHERE vr.class_id = ? AND vr.week_number = ? AND (vr.is_deleted = 0 OR vr.is_deleted IS NULL)
        ORDER BY vr.date_created DESC
    ";
    $stmtVio = $pdo->prepare($sqlVio);
    $stmtVio->execute([$class_id, $week]);
    $violations = $stmtVio->fetchAll();
}

// =========================================================================
// PHẦN 3: LẤY DỮ LIỆU MATRIX (SỔ ĐOÀN TRƯỜNG)
// =========================================================================
$matrix_data = []; $matrix_total = 0; $matrix_note = ""; $bonus_score = 0;

if ($class_id > 0) {
    $MAX = ['SS'=>1, 'VS'=>1, 'CSVC'=>1, 'TB'=>1, 'XE'=>1, 'DP'=>2, 'SV'=>1, 'THE'=>1, 'DT'=>1];
    $ORDER = ['SS', 'VS', 'CSVC', 'TB', 'XE', 'DP', 'SV', 'THE', 'DT'];
    $day_data = []; for($i=2;$i<=7;$i++) $day_data[$i] = $MAX;

    // Lấy lỗi trừ điểm CLASS
    $recs = $pdo->prepare("SELECT r.recorded_points, r.date_created, r.recorded_violation_name, v.short_code FROM violation_record r JOIN violation_type v ON r.violation_type_id=v.id WHERE r.class_id=? AND r.week_number=? AND v.scope='CLASS' AND (r.is_deleted = 0 OR r.is_deleted IS NULL)");
    $recs->execute([$class_id, $week]);
    
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
    $aca = $pdo->prepare("SELECT note, bonus_score FROM academic_score WHERE class_id=? AND week_number=?");
    $aca->execute([$class_id, $week]);
    $info = $aca->fetch();
    if($info) { $matrix_note = $info['note']; $bonus_score = $info['bonus_score']; }
}


// =========================================================================
// PHẦN 4: LẤY DỮ LIỆU TÂM LÝ (GỌI API SANG PYTHON)
// =========================================================================
$psychology_logs = [];

// 1. Lấy danh sách mã học sinh (Code) từ danh sách lớp
$student_codes = [];
if (!empty($students)) {
    $student_codes = array_column($students, 'code');
}

// Lấy logs trực tiếp từ cơ sở dữ liệu cục bộ
if (!empty($student_codes)) {
    try {
        $placeholders = implode(',', array_fill(0, count($student_codes), '?'));
        $stmt = $pdo->prepare("SELECT id, username, full_name, question, advice, risk_level, created_at, school_year 
                               FROM psychology_logs 
                               WHERE username IN ($placeholders) 
                               ORDER BY created_at DESC 
                               LIMIT 50");
        $stmt->execute($student_codes);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($data as &$l) {
            if ($l['created_at']) {
                $dt = new DateTime($l['created_at']);
                $l['created_at'] = $dt->format('H:i d/m');
            }
        }
        $psychology_logs = $data;
    } catch (Exception $e) {
        $psychology_logs = [];
    }
}

// 4. Map tên học sinh vào logs (vì Python chỉ trả về Username/Mã HS)
// Tạo bảng tra cứu: ['K48A1016' => 'Nguyễn Văn A', ...]
$student_map = [];
foreach ($students as $s) {
    $student_map[$s['code']] = $s['name'];
}

// Gắn tên thật vào dữ liệu log
foreach ($psychology_logs as &$log) {
    $u = $log['username'] ?? '';
    if (isset($student_map[$u])) {
        $log['student_name'] = $student_map[$u];
    } else {
        $log['student_name'] = $log['full_name'] ?? $u;
    }
}
unset($log);

require_once 'views/teacher_dashboard_view.php';