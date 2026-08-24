<?php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['ADMIN', 'TEACHER', 'RED_FLAG'])) {
    echo json_encode(['status' => 'error', 'msg' => __('no_permission', 'Không có quyền truy cập')]); exit;
}

$userRole = $_SESSION['user']['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    if (isset($input['action']) && $input['action'] === 'delete') {
        if ($userRole !== 'ADMIN') {
            echo json_encode(['status' => 'error', 'msg' => __('admin_only', 'Chỉ Admin mới có quyền thực hiện thao tác này!')]);
            exit;
        }

        try {
            $id = $input['delete_id'];
            $current_school_year = get_current_school_year($pdo);

            $stmtChk = $pdo->prepare("SELECT school_year FROM violation_record WHERE id = ?");
            $stmtChk->execute([$id]);
            $rowChk = $stmtChk->fetch(PDO::FETCH_ASSOC);
            if ($rowChk && $rowChk['school_year'] !== $current_school_year) {
                echo json_encode(['status' => 'error', 'msg' => __('cannot_delete_frozen_data', 'Không thể xóa dữ liệu năm học cũ đã đóng băng!')]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE violation_record SET is_deleted = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            require_once '../includes/sse_push.php';
            sse_push($pdo, 'violation_deleted', ['id' => (int)$id]);

            echo json_encode(['status' => 'success', 'msg' => __('record_deleted', 'Đã xóa (ẩn) bản ghi thành công!')]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    if (isset($input['action']) && $input['action'] === 'edit') {
        if ($userRole !== 'ADMIN') {
            echo json_encode(['status' => 'error', 'msg' => __('admin_only', 'Chỉ Admin mới có quyền thực hiện thao tác này!')]);
            exit;
        }

        try {
            $id = $input['edit_id'];
            $v_id = $input['violation_type_id'];
            $week = $input['week_number'];
            $raw_time = $input['date_created'];
            $created_at = date('Y-m-d H:i:s', strtotime($raw_time));

            $current_school_year = get_current_school_year($pdo);
            $stmtChk = $pdo->prepare("SELECT school_year FROM violation_record WHERE id = ?");
            $stmtChk->execute([$id]);
            $rowChk = $stmtChk->fetch(PDO::FETCH_ASSOC);
            if ($rowChk && $rowChk['school_year'] !== $current_school_year) {
                echo json_encode(['status' => 'error', 'msg' => __('cannot_edit_frozen_data', 'Không thể sửa dữ liệu năm học cũ đã đóng băng!')]);
                exit;
            }

            $stmtV = $pdo->prepare("SELECT content, points FROM violation_type WHERE id = ?");
            $stmtV->execute([$v_id]);
            $vData = $stmtV->fetch(PDO::FETCH_ASSOC);

            if (!$vData) {
                throw new Exception(__('violation_not_exist', "Lỗi vi phạm không tồn tại!"));
            }

            $stmtU = $pdo->prepare("
                UPDATE violation_record
                SET violation_type_id = ?,
                    recorded_violation_name = ?,
                    recorded_points = ?,
                    week_number = ?,
                    date_created = ?
                WHERE id = ?
            ");
            $stmtU->execute([
                $v_id,
                $vData['content'],
                $vData['points'],
                $week,
                $created_at,
                $id
            ]);

            require_once __DIR__ . '/../includes/sse_push.php';
            sse_push($pdo, 'violation_class_updated', ['id' => (int)$id]);

            echo json_encode(['status' => 'success', 'msg' => __('update_violation_success', 'Cập nhật lỗi vi phạm thành công!')]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }
}

function getReporterName($row) {
    if (!empty($row['reporter_fullname'])) return $row['reporter_fullname'];
    return $row['reporter_username'] ?? __('system', 'Hệ thống');
}

try {
    $current_school_year = get_current_school_year($pdo);

    $page_gate = isset($_GET['page_gate']) ? max(1, (int)$_GET['page_gate']) : 1;
    $page_class = isset($_GET['page_class']) ? max(1, (int)$_GET['page_class']) : 1;
    $limit = 50;

    $offset_gate = ($page_gate - 1) * $limit;
    $offset_class = ($page_class - 1) * $limit;

    $stmtCountGate = $pdo->prepare("SELECT COUNT(*) FROM violation_record vr JOIN violation_type vt ON vr.violation_type_id = vt.id WHERE (vt.scope = 'GATE' OR vr.student_id IS NOT NULL) AND vr.school_year = ?");
    $stmtCountGate->execute([$current_school_year]);
    $totalGate = $stmtCountGate->fetchColumn();
    $total_pages_gate = ceil($totalGate / $limit);

    $stmtCountClass = $pdo->prepare("SELECT COUNT(*) FROM violation_record vr JOIN violation_type vt ON vr.violation_type_id = vt.id WHERE vt.scope = 'CLASS' AND vr.student_id IS NULL AND vr.school_year = ?");
    $stmtCountClass->execute([$current_school_year]);
    $totalClass = $stmtCountClass->fetchColumn();
    $total_pages_class = ceil($totalClass / $limit);

    $sqlGate = "SELECT vr.id, vr.recorded_violation_name, vt.content_en AS violation_name_en, vr.recorded_points, vr.date_created, vr.is_deleted, vr.week_number, vr.note,
                vt.content_en AS recorded_violation_name_en,
                s.name AS student_name, s.code AS student_code, c.name AS class_name, u.full_name AS reporter_fullname, vr.reporter AS reporter_username
                FROM violation_record vr LEFT JOIN student s ON vr.student_id = s.id
                LEFT JOIN classroom c ON vr.class_id = c.id LEFT JOIN users u ON vr.reporter = u.username
                JOIN violation_type vt ON vr.violation_type_id = vt.id
                WHERE (vt.scope = 'GATE' OR vr.student_id IS NOT NULL) AND vr.school_year = :school_year
                ORDER BY vr.date_created DESC LIMIT :limit OFFSET :offset";
    $stmtGate = $pdo->prepare($sqlGate);
    $stmtGate->bindValue(':school_year', $current_school_year, PDO::PARAM_STR);
    $stmtGate->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmtGate->bindValue(':offset', (int)$offset_gate, PDO::PARAM_INT);
    $stmtGate->execute();

    $gateLogsRaw = $stmtGate->fetchAll(PDO::FETCH_ASSOC);
    $gateLogs = [];
    foreach($gateLogsRaw as $row) { $row['reporter_name'] = getReporterName($row); $gateLogs[] = $row; }

    $sqlClass = "SELECT vr.id, vr.recorded_violation_name, vt.content_en AS violation_name_en, vr.recorded_points, vr.submitted_at, vr.week_number, vr.is_deleted,
                vt.content_en AS recorded_violation_name_en,
                c.name AS class_name, u.full_name AS reporter_fullname, vr.reporter AS reporter_username
                FROM violation_record vr LEFT JOIN classroom c ON vr.class_id = c.id
                LEFT JOIN users u ON vr.reporter = u.username JOIN violation_type vt ON vr.violation_type_id = vt.id
                WHERE vt.scope = 'CLASS' AND vr.student_id IS NULL AND vr.school_year = :school_year
                ORDER BY vr.submitted_at DESC LIMIT :limit OFFSET :offset";
    $stmtClass = $pdo->prepare($sqlClass);
    $stmtClass->bindValue(':school_year', $current_school_year, PDO::PARAM_STR);
    $stmtClass->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmtClass->bindValue(':offset', (int)$offset_class, PDO::PARAM_INT);
    $stmtClass->execute();

    $classLogsRaw = $stmtClass->fetchAll(PDO::FETCH_ASSOC);
    $classLogs = [];
    foreach($classLogsRaw as $row) { $row['reporter_name'] = getReporterName($row); $classLogs[] = $row; }

    echo json_encode([
        'status' => 'success',
        'gate_logs' => $gateLogs,
        'class_logs' => $classLogs,
        'page_gate' => $page_gate,
        'page_class' => $page_class,
        'total_pages_gate' => $total_pages_gate,
        'total_pages_class' => $total_pages_class,
        'role' => $userRole
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>