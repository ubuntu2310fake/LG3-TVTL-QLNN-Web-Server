<?php
// class_check.php

// 1. CHỈ GỌI CONFIG (Nó đã tự gọi functions và session_start)
require_once 'includes/config.php';

// Kiểm tra quyền
if (function_exists('checkRole')) {
    checkRole(['ADMIN', 'TEACHER', 'RED_FLAG']);
}

$currentUser = $_SESSION['user']['username'] ?? 'admin';

// =================================================================
// XỬ LÝ LƯU DỮ LIỆU (Action Save) - LOGIC MỚI (SOFT DELETE)
// =================================================================
if (isset($_GET['action']) && $_GET['action'] === 'save_matrix') {
    ob_clean(); 
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) throw new Exception("Dữ liệu lỗi");

        $class_id = $input['class_id'];
        $week = $input['week'];
        $scores = $input['scores'];
        $note = $input['general_note'];
        $bonus = $input['bonus_score'];

        // 1. Lấy ngày bắt đầu và ngày nghỉ học
        $stmtCfg = $pdo->prepare("SELECT value FROM config WHERE `key` = 'start_date'");
        $stmtCfg->execute();
        $startStr = $stmtCfg->fetchColumn();
        $startDate = $startStr ? new DateTime($startStr) : new DateTime('2025-09-05');
        if ($startDate->format('N') != 1) {
            $startDate->modify('monday this week');
        }

        $stmtEx = $pdo->prepare("SELECT value FROM config WHERE `key` = 'excluded_dates'");
        $stmtEx->execute();
        $exStr = $stmtEx->fetchColumn() ?: '[]';
        $excluded_list = json_decode($exStr, true) ?: [];

        $current_school_year = get_current_school_year($pdo);

        $pdo->beginTransaction();

        // 2. Map ID lỗi
        $stmtMap = $pdo->query("SELECT id, short_code, content FROM violation_type WHERE scope = 'CLASS'");
        $vioMap = []; $vioContent = [];
        while ($row = $stmtMap->fetch(PDO::FETCH_ASSOC)) {
            $vioMap[$row['short_code']] = $row['id'];
            $vioContent[$row['short_code']] = $row['content'];
        }

        // 3. CHUẨN BỊ SQL (Insert & Update)
        // Check tồn tại: Tìm bản ghi cùng Lớp, Cùng Loại Lỗi, Cùng Ngày (DATE), Cùng Năm học
        $sqlCheck = "SELECT id, school_year FROM violation_record 
                     WHERE class_id = ? AND violation_type_id = ? AND DATE(date_created) = ? AND school_year = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);

        // Insert mới (Chưa từng có lỗi)
        $sqlInsert = "INSERT INTO violation_record 
                       (class_id, violation_type_id, recorded_violation_name, recorded_points, week_number, date_created, submitted_at, reporter, is_deleted, school_year) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, 0, ?)";
        $stmtInsert = $pdo->prepare($sqlInsert);

        // Update (Nếu lỗi đã tồn tại -> Cập nhật điểm hoặc Xóa mềm)
        $sqlUpdate = "UPDATE violation_record 
                       SET recorded_points = ?, is_deleted = ?, reporter = ?, submitted_at = NOW() 
                       WHERE id = ?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        
        $stmtClass = $pdo->prepare("SELECT name FROM classroom WHERE id = ?");
        $stmtClass->execute([$class_id]);
        $className = $stmtClass->fetchColumn();
        $currentFullName = $_SESSION['user']['full_name'] ?? $currentUser;
        
        $new_class_violations = [];
        $updated_class_violations = [];
        $deleted_ids = [];

        foreach ($scores as $s) {
            $deduction = floatval($s['deduction']);

            // Làm tròn nhẹ để tránh lỗi số học 0.0000001
            if ($deduction < 0.01) $deduction = 0;

            $code = $s['code'];
            $dayIdx = (int)$s['day']; // 2, 3, 4...

            // Tính ngày của cột đó
            $daysToAdd = (($week - 1) * 7) + ($dayIdx - 2);
            $targetDate = clone $startDate;
            $targetDate->modify("+$daysToAdd days");
            $targetDate->setTime(8, 0, 0); 
            $dateCreatedStr = $targetDate->format('Y-m-d H:i:s');
            $dateOnly = $targetDate->format('Y-m-d'); // Dùng để so sánh trong SQL Check

            if (in_array($dateOnly, $excluded_list)) {
                $deduction = 0;
            }

            if (isset($vioMap[$code])) {
                $vid = $vioMap[$code];
                $vname = $vioContent[$code] . " (T$dayIdx)"; // VD: Vệ sinh (T2)
                $vname_en = ""; // Need to fetch EN name if possible, but class check doesn't have it easily available, skip for now.

                // A. KIỂM TRA XEM ĐÃ CÓ BẢN GHI TRONG DB CHƯA?
                $stmtCheck->execute([$class_id, $vid, $dateOnly, $current_school_year]);
                $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $existingId = $existing['id'];
                    // --- TRƯỜNG HỢP 1: ĐÃ CÓ TRONG DB ---
                    if ($deduction > 0) {
                        // Vẫn bị trừ điểm -> Update điểm mới, bật lại (is_deleted = 0)
                        $stmtUpdate->execute([$deduction, 0, $currentUser, $existingId]);
                        $updated_class_violations[] = [
                            'id' => $existingId,
                            'recorded_points' => $deduction,
                            'reporter' => $currentUser,
                            'reporter_fullname' => $currentFullName,
                            'submitted_at' => date('Y-m-d H:i:s')
                        ];
                    } else {
                        // Hết bị trừ (Điểm về Max) -> XÓA MỀM (is_deleted = 1)
                        $stmtUpdate->execute([0, 1, $currentUser, $existingId]);
                        $deleted_ids[] = $existingId;
                    }
                } else {
                    // --- TRƯỜNG HỢP 2: CHƯA CÓ TRONG DB ---
                    if ($deduction > 0) {
                        // Có lỗi mới -> Insert
                        $stmtInsert->execute([
                            $class_id, $vid, $vname, $deduction, $week, 
                            $dateCreatedStr, $currentUser, $current_school_year
                        ]);
                        $new_class_violations[] = [
                            'id' => $pdo->lastInsertId(),
                            'class_name' => $className,
                            'week_number' => $week,
                            'display_name' => $vname,
                            'recorded_points' => $deduction,
                            'reporter' => $currentUser,
                            'reporter_fullname' => $currentFullName,
                            'submitted_at' => date('Y-m-d H:i:s')
                        ];
                    }
                }
            }
        }

        // ... (Phần lưu điểm cộng Academic Score) ...
        $stmtChk = $pdo->prepare("SELECT id, school_year FROM academic_score WHERE class_id = ? AND week_number = ? AND school_year = ?");
        $stmtChk->execute([$class_id, $week, $current_school_year]);
        $acaRow = $stmtChk->fetch(PDO::FETCH_ASSOC);

        if ($acaRow) {
            $stmtUpd = $pdo->prepare("UPDATE academic_score SET note = ?, bonus_score = ? WHERE id = ?");
            $stmtUpd->execute([$note, $bonus, $acaRow['id']]);
        } else {
            $stmtInsAca = $pdo->prepare("INSERT INTO academic_score (class_id, week_number, note, bonus_score, school_year) VALUES (?, ?, ?, ?, ?)");
            $stmtInsAca->execute([$class_id, $week, $note, $bonus, $current_school_year]);
        }

        $pdo->commit();
        
        require_once 'includes/sse_push.php';
        foreach($new_class_violations as $ev) {
            sse_push($pdo, 'violation_class_new', $ev);
        }
        foreach($updated_class_violations as $ev) {
            sse_push($pdo, 'violation_class_updated', $ev);
        }
        foreach($deleted_ids as $did) {
            sse_push($pdo, 'violation_deleted', ['id' => (int)$did]);
        }
        
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// =================================================================
// XỬ LÝ LƯU NGÀY NGHỈ (AJAX POST)
// =================================================================
if (isset($_GET['action']) && $_GET['action'] === 'toggle_holiday') {
    ob_clean();
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $date = $input['date'];
        $is_holiday = $input['is_holiday'];
        
        $stmtEx = $pdo->prepare("SELECT value FROM config WHERE `key` = 'excluded_dates'");
        $stmtEx->execute();
        $exStr = $stmtEx->fetchColumn() ?: '[]';
        $excluded_list = json_decode($exStr, true) ?: [];
        
        if ($is_holiday) {
            if (!in_array($date, $excluded_list)) {
                $excluded_list[] = $date;
            }
        } else {
            $excluded_list = array_values(array_diff($excluded_list, [$date]));
        }
        
        $newJson = json_encode($excluded_list);
        $stmtUpdate = $pdo->prepare("UPDATE config SET value = ? WHERE `key` = 'excluded_dates'");
        $stmtUpdate->execute([$newJson]);
        
        if ($redis_connected) {
            $redis->del('lg3_system_settings');
        }
        
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// =================================================================
// XỬ LÝ LẤY DỮ LIỆU (AJAX GET)
// =================================================================
if (isset($_GET['action']) && $_GET['action'] === 'load_matrix') {
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        $cid = $_GET['class_id'];
        $week = $_GET['week'];
        
        $stmtCfg = $pdo->prepare("SELECT value FROM config WHERE `key` = 'start_date'");
        $stmtCfg->execute();
        $startStr = $stmtCfg->fetchColumn() ?: '2025-08-30';
        $startDate = new DateTime($startStr);
        if ($startDate->format('N') != 1) {
            $startDate->modify('monday this week');
        }

        $stmtEx = $pdo->prepare("SELECT value FROM config WHERE `key` = 'excluded_dates'");
        $stmtEx->execute();
        $exStr = $stmtEx->fetchColumn() ?: '[]';
        $excluded_list = json_decode($exStr, true) ?: [];

        $holidays = [];
        $day_dates = [];
        for ($dayIdx = 2; $dayIdx <= 7; $dayIdx++) {
            $daysToAdd = (($week - 1) * 7) + ($dayIdx - 2);
            $targetDate = clone $startDate;
            $targetDate->modify("+$daysToAdd days");
            $dateOnly = $targetDate->format('Y-m-d');
            $day_dates[$dayIdx] = $dateOnly;
            if (in_array($dateOnly, $excluded_list)) {
                $holidays[] = $dayIdx;
            }
        }
        $is_skipped = (count($holidays) === 6);
        
        $current_school_year = get_current_school_year($pdo);

        $gate = $pdo->prepare("SELECT vr.*, vt.content FROM violation_record vr JOIN violation_type vt ON vr.violation_type_id = vt.id WHERE vr.class_id = ? AND vr.week_number = ? AND vt.scope = 'GATE' AND vr.school_year = ? AND (vr.is_deleted = 0 OR vr.is_deleted IS NULL)");
        $gate->execute([$cid, $week, $current_school_year]);
        $gateData = $gate->fetchAll(PDO::FETCH_ASSOC);
        
        $cls = $pdo->prepare("SELECT vr.*, vt.short_code FROM violation_record vr JOIN violation_type vt ON vr.violation_type_id = vt.id WHERE vr.class_id = ? AND vr.week_number = ? AND vt.scope = 'CLASS' AND vr.school_year = ? AND (vr.is_deleted = 0 OR vr.is_deleted IS NULL)");
        $cls->execute([$cid, $week, $current_school_year]);
        $clsRecs = $cls->fetchAll(PDO::FETCH_ASSOC);
        
        $savedScores = [];
        foreach($clsRecs as $r) {
            $d = (new DateTime($r['date_created']))->format('N') + 1; 
            if (preg_match('/\(T(\d)\)/', $r['recorded_violation_name'], $matches)) $d = (int)$matches[1];
            $savedScores[] = ['day' => ($d > 7 ? 7 : $d), 'code' => $r['short_code'], 'deduction' => (float)$r['recorded_points']];
        }

        $stmtAca = $pdo->prepare("SELECT note, bonus_score FROM academic_score WHERE class_id = ? AND week_number = ? AND school_year = ?");
        $stmtAca->execute([$cid, $week, $current_school_year]);
        $acaData = $stmtAca->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'gate_data' => $gateData, 
            'saved_scores' => $savedScores,
            'general_note' => $acaData['note'] ?? '',
            'bonus_score' => isset($acaData['bonus_score']) ? (float)$acaData['bonus_score'] : 0,
            'holidays' => $holidays,
            'day_dates' => $day_dates,
            'is_skipped' => $is_skipped
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// =================================================================
// GIAO DIỆN HTML
// =================================================================
// 1. Lấy tất cả lớp về (Loại trừ các lớp K46 / đã tốt nghiệp)
$classes = $pdo->query("SELECT * FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%'")->fetchAll(PDO::FETCH_ASSOC);

// 2. Sử dụng hàm strnatcmp để sắp xếp tự nhiên
usort($classes, function($a, $b) {
    if ($a['grade'] != $b['grade']) {
        return $a['grade'] - $b['grade'];
    }
    return strnatcmp($a['name'], $b['name']);
});
$default_week = get_current_week($pdo);

// 3. Lấy tên đầy đủ của các mã lỗi từ CSDL để đọc cho TalkBack
$stmtMap = $pdo->query("SELECT short_code, content FROM violation_type WHERE scope = 'CLASS'");
$vioContent = [];
while ($row = $stmtMap->fetch(PDO::FETCH_ASSOC)) {
    $vioContent[$row['short_code']] = $row['content'];
}

require_once 'views/class_check_view.php';