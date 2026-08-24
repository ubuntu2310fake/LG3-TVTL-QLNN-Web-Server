<?php
// api/exam_appeal_api.php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => __('access_denied', 'Từ chối truy cập')]);
    exit;
}

try {
    $sbd = $_POST['sbd'] ?? '';
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    $user = $_SESSION['user'];
    
    // 1. CHỈ CHO PHÉP HỌC SINH TỰ PHÚC KHẢO
    if ($user['role'] !== 'STUDENT') {
        echo json_encode(['status' => 'error', 'msg' => __('only_students_can_appeal', 'Chỉ tài khoản Học sinh mới có quyền tự gửi yêu cầu phúc khảo.')]);
        exit;
    }

    // Lấy mã học sinh (username) từ session
    $studentCode = $user['username'];

    // 2. LẤY THÔNG TIN GỐC CỦA HỌC SINH TỪ DATABASE
    $stmtStu = $pdo->prepare("
        SELECT s.name as student_name, s.dob, c.name as class_name 
        FROM student s 
        LEFT JOIN classroom c ON s.class_id = c.id 
        WHERE s.code = ?
    ");
    $stmtStu->execute([$studentCode]);
    $realStudent = $stmtStu->fetch(PDO::FETCH_ASSOC);

    if (!$realStudent) {
        echo json_encode(['status' => 'error', 'msg' => __('student_record_not_found', 'Không tìm thấy hồ sơ học sinh của bạn trong hệ thống.')]);
        exit;
    }

    // 3. LẤY BẢN GHI ĐIỂM THI ĐANG MUỐN PHÚC KHẢO
    $stmtScore = $pdo->prepare("SELECT student_name, dob, class_name FROM lg3_exam_scores WHERE sbd = ? AND exam_id = ?");
    $stmtScore->execute([$sbd, $exam_id]);
    $scoreRecord = $stmtScore->fetch(PDO::FETCH_ASSOC);

    if (!$scoreRecord) {
        echo json_encode(['status' => 'error', 'msg' => __('score_data_not_found', 'Không tìm thấy dữ liệu điểm của SBD này trong kỳ thi.')]);
        exit;
    }

    // 4. SOI KHỚP DỮ LIỆU (Tên, DOB, Lớp)
    $realName = mb_strtolower(trim($realStudent['student_name']));
    $scoreName = mb_strtolower(trim($scoreRecord['student_name']));

    $realDob = trim($realStudent['dob']);
    $scoreDob = trim($scoreRecord['dob']);

    $realClass = mb_strtolower(trim($realStudent['class_name']));
    $scoreClass = mb_strtolower(trim($scoreRecord['class_name']));

    if ($realName !== $scoreName || $realDob !== $scoreDob || $realClass !== $scoreClass) {
        echo json_encode([
            'status' => 'error', 
            'msg' => __('auth_failed_appeal', "Xác thực thất bại! Bạn chỉ được phúc khảo điểm của chính mình.\n\nLý do: Thông tin (Tên, Ngày sinh hoặc Lớp) trên bảng điểm không khớp với Hồ sơ tài khoản của bạn.")
        ]);
        exit;
    }

    // 5. TÌM NGƯỜI NHẬN THÔNG BÁO (GVCN HOẶC ADMIN)
    $stmtMgr = $pdo->prepare("SELECT id FROM users WHERE role IN ('ADMIN', 'TEACHER')");
    $stmtMgr->execute();
    $managerIds = $stmtMgr->fetchAll(PDO::FETCH_COLUMN);
    
    $pushTargets = [];
    foreach($managerIds as $mid) {
        $pushTargets[] = ['id' => $mid, 'type' => 'MANAGER'];
    }

    // 6. ĐÓNG GÓI PAYLOAD GỬI VÀO QUEUE ĐỂ WORKER.PHP XỬ LÝ
    $queuePayload = [
        'created_by' => $user['id'],
        'student_name' => $realStudent['student_name'] . " (SBD: " . $sbd . ")",
        'class_name' => $realStudent['class_name'],
        'total_points' => 0,
        'errors_str' => __('appeal_req_prefix', "Yêu cầu phúc khảo điểm (Kỳ thi ID: ") . $exam_id . __('appeal_req_suffix', "). Lý do: ") . htmlspecialchars($reason),
        'targets' => $pushTargets
    ];

    $stmtQ = $pdo->prepare("INSERT INTO notification_queue (payload) VALUES (?)");
    $stmtQ->execute([json_encode($queuePayload)]);

    echo json_encode(['status' => 'success', 'msg' => __('auth_success_queued', 'Xác thực thành công. Đã đưa vào hàng chờ xử lý!')]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>