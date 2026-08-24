<?php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!defined('ROOT_ADMIN_ID')) {
    define('ROOT_ADMIN_ID', 1);
}

if (function_exists('checkRole')) {
    checkRole(['ADMIN', 'TEACHER', 'RED_FLAG']);
}

$default_week = get_current_week($pdo);
$currentUser = $_SESSION['user']['username'] ?? 'unknown';
$currentUserId = $_SESSION['user']['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/sse_push.php';
    
    if (isset($_POST['suggest_query'])) {
        $q = trim($_POST['suggest_query']);
        $stmt = $pdo->prepare("
            SELECT s.id, s.code, s.name, s.image_url, c.name as class_name 
            FROM student s 
            LEFT JOIN classroom c ON s.class_id = c.id 
            WHERE s.name LIKE ? OR s.code LIKE ? 
            LIMIT 10
        ");
        $stmt->execute(["%$q%", "%$q%"]);
        echo json_encode(['status' => 'success', 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if (isset($_POST['get_class_students'])) {
        $cid = $_POST['class_id'];
        $stmt = $pdo->prepare("
            SELECT s.id, s.code, s.name, s.image_url, c.name as class_name
            FROM student s 
            JOIN classroom c ON s.class_id = c.id
            WHERE s.class_id = ? 
            ORDER BY CAST(RIGHT(s.code, 3) AS UNSIGNED) ASC
        ");
        $stmt->execute([$cid]);
        echo json_encode(['status' => 'success', 'students' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if (isset($_POST['student_id'])) {
        try {
            $pdo->beginTransaction();

            $student_id = $_POST['student_id'];
            $week = $_POST['week'] ?? $default_week;

            if (is_week_skipped($week, $pdo)) {
                throw new Exception(__('week_frozen', "Tuần học này đã bị đóng băng do trùng lịch nghỉ lễ/Tết của toàn trường."));
            }
            $note = $_POST['other_note'] ?? '';
            $custom_time = $_POST['custom_time'] ?? null;
            $created_at = $custom_time ? date('Y-m-d H:i:s', strtotime($custom_time)) : date('Y-m-d H:i:s');
            
            $violation_ids = [];
            if (isset($_POST['violation_ids'])) {
                $raw_ids = $_POST['violation_ids'];
                if (is_array($raw_ids)) $violation_ids = $raw_ids;
                else $violation_ids = explode(',', $raw_ids); 
            }
            $violation_ids = array_unique(array_filter($violation_ids));

            if (empty($violation_ids)) {
                throw new Exception(__('no_violation_selected', "Không có lỗi vi phạm nào được chọn."));
            }

            $stmtStu = $pdo->prepare("
                SELECT s.class_id, s.name, s.code, c.name as class_name 
                FROM student s 
                LEFT JOIN classroom c ON s.class_id = c.id 
                WHERE s.id = ?
            ");
            $stmtStu->execute([$student_id]);
            $studentData = $stmtStu->fetch(PDO::FETCH_ASSOC);
            
            if (!$studentData) throw new Exception(__('student_not_found', "Không tìm thấy học sinh"));
            $class_id = $studentData['class_id'];
            $studentName = $studentData['name'];
            $className = $studentData['class_name'];

            $inQuery = implode(',', array_fill(0, count($violation_ids), '?'));
            $stmtVio = $pdo->prepare("SELECT id, content, content_en, points FROM violation_type WHERE id IN ($inQuery)");
            $stmtVio->execute($violation_ids);
            $allVioTypes = $stmtVio->fetchAll(PDO::FETCH_ASSOC);

            $lang = $_SESSION['lang'] ?? 'vi';

            $vioMap = [];
            foreach ($allVioTypes as $v) {
                $vioMap[$v['id']] = $v;
            }

            $current_school_year = get_current_school_year($pdo);

            $sql = "INSERT INTO violation_record 
                    (student_id, class_id, violation_type_id, recorded_violation_name, recorded_points, reporter, week_number, note, date_created, school_year) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtInsert = $pdo->prepare($sql);
            
            $new_data_response = [];
            $list_error_names = [];
            $total_deducted = 0;

            foreach ($violation_ids as $vid) {
                if (isset($vioMap[$vid])) {
                    $vioType = $vioMap[$vid];
                    $vioName = $vioType['content'];
                    $stmtInsert->execute([
                        $student_id, $class_id, $vid, $vioName, 
                        $vioType['points'], $currentUser, $week, $note, $created_at, $current_school_year
                    ]);

                    $new_data_response[] = [
                        'id' => $pdo->lastInsertId(),
                        'student_name' => $studentName,
                        'class_name' => $className,
                        'violation_name' => $vioName,
                        'violation_name_en' => $vioType['content_en'] ?? '',
                        'recorded_points' => $vioType['points'],
                        'time_str' => date('H:i d/m', strtotime($created_at)),
                        'violation_type_id' => $vid
                    ];

                    $list_error_names[] = $vioType['content'];
                    $total_deducted += $vioType['points'];
                }
            }

            if (!empty($list_error_names)) {
                $pushTargets = [];
                
                $stmtU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmtU->execute([$studentData['code']]);
                $uStudent = $stmtU->fetch(PDO::FETCH_ASSOC);
                if ($uStudent) $pushTargets[] = ['id' => $uStudent['id'], 'type' => 'STUDENT'];

                $stmtMgr = $pdo->prepare("
                    SELECT id FROM users WHERE homeroom_class_id = ? AND id != ?
                    UNION
                    SELECT id FROM users WHERE role = 'ADMIN' AND id != ?
                "); 
                $stmtMgr->execute([$class_id, $currentUserId, $currentUserId]); 
                $managerIds = $stmtMgr->fetchAll(PDO::FETCH_COLUMN);
                
                if (!in_array(ROOT_ADMIN_ID, $managerIds)) {
                    $managerIds[] = ROOT_ADMIN_ID;
                }
                $managerIds = array_unique($managerIds);

                foreach($managerIds as $mid) {
                    $pushTargets[] = ['id' => $mid, 'type' => 'MANAGER'];
                }

                $errors_str = implode(', ', $list_error_names);
                if (!empty($note)) $errors_str .= " (" . __('note_prefix', "Ghi chú:") . " $note)";

                $queuePayload = [
                    'created_by' => $currentUserId,
                    'student_name' => $studentName,
                    'class_name' => $className,
                    'total_points' => $total_deducted,
                    'errors_str' => $errors_str,
                    'targets' => $pushTargets
                ];

                $stmtQ = $pdo->prepare("INSERT INTO notification_queue (payload) VALUES (?)");
                $stmtQ->execute([json_encode($queuePayload)]);
            }

            $pdo->commit();

            $currentFullName = $_SESSION['user']['full_name'] ?? $currentUser;
            foreach ($new_data_response as $rec) {
                sse_push($pdo, 'violation_new', [
                    'id'           => $rec['id'] ?? 0,
                    'student_name' => $studentName,
                    'student_code' => $studentData['code'] ?? '',
                    'class_name'   => $className,
                    'display_name' => $rec['violation_name'] ?? '',
                    'display_name_en' => $rec['violation_name_en'] ?? '',
                    'recorded_points' => $rec['recorded_points'] ?? 0,
                    'reporter'     => $currentUser,
                    'reporter_fullname' => $currentFullName,
                    'time_label'   => date('H:i d/m', strtotime($created_at)),
                    'date_created' => $created_at,
                    'violation_type_id' => $rec['violation_type_id'] ?? 0,
                    'week_number'  => $week,
                    'note'         => $note
                ]);
            }

            echo json_encode([
                'status' => 'success',
                'new_data' => $new_data_response
            ]);

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    if (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        $stmt = $pdo->prepare("UPDATE violation_record SET is_deleted = 1 WHERE id = ? AND (reporter = ? OR ? = 'ADMIN')");
        $stmt->execute([$id, $currentUser, $_SESSION['user']['role'] ?? '']);

        if ($stmt->rowCount() > 0) {
            sse_push($pdo, 'violation_deleted', ['id' => (int)$id]);
        }

        echo json_encode(['status' => 'success']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'recent_json') {
    $current_school_year = get_current_school_year($pdo);
    $lang = $_SESSION['lang'] ?? 'vi';
    $stmt = $pdo->prepare("
        SELECT r.id, r.recorded_violation_name, r.recorded_points, r.date_created, r.note,
               s.name as student_name, c.name as class_name,
               vt.content_en AS recorded_violation_name_en
        FROM violation_record r
        LEFT JOIN student s ON r.student_id = s.id
        LEFT JOIN classroom c ON r.class_id = c.id
        LEFT JOIN violation_type vt ON r.violation_type_id = vt.id
        WHERE r.reporter = ? AND r.is_deleted = 0 AND r.school_year = ?
        ORDER BY r.date_created DESC
        LIMIT 15
    ");
    $stmt->execute([$currentUser, $current_school_year]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['display_name'] = ($lang === 'en' && !empty($row['recorded_violation_name_en']))
            ? $row['recorded_violation_name_en']
            : $row['recorded_violation_name'];
        $row['time_label'] = date('H:i d/m', strtotime($row['date_created']));
    }
    echo json_encode(['status' => 'success', 'violations' => $rows, 'lang' => $lang]);
    exit;
}
