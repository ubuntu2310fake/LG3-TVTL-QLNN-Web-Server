<?php
// violation_history.php
require_once 'includes/config.php';

// Kiểm tra quyền truy cập
if (function_exists('checkRole')) {
    checkRole(['ADMIN', 'TEACHER', 'RED_FLAG']);
}

$userRole = $_SESSION['user']['role'] ?? '';

// =================================================================
// 0. XỬ LÝ AJAX (SỬA / XÓA LỖI) - CHỈ DÀNH CHO ADMIN
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Kiểm tra quyền cứng ở backend
    if ($userRole !== 'ADMIN') {
        echo json_encode(['status' => 'error', 'msg' => __('admin_only', 'Chỉ Admin mới có quyền thực hiện thao tác này!')]);
        exit;
    }

    // --- API XÓA ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        try {
            $id = $_POST['delete_id'];
            // Check if record is frozen (Read-only school year)
            $check_stmt = $pdo->prepare("SELECT school_year FROM violation_record WHERE id = ?");
            $check_stmt->execute([$id]);
            $rec_year = $check_stmt->fetchColumn();


            $stmt = $pdo->prepare("UPDATE violation_record SET is_deleted = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            require_once 'includes/sse_push.php';
            sse_push($pdo, 'violation_deleted', ['id' => (int)$id]);
            
            echo json_encode(['status' => 'success', 'msg' => __('record_deleted', 'Đã xóa (ẩn) bản ghi thành công!')]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // --- API SỬA ---
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        try {
            $id = $_POST['edit_id'];
            // Check if record is frozen (Read-only school year)
            $check_stmt = $pdo->prepare("SELECT school_year FROM violation_record WHERE id = ?");
            $check_stmt->execute([$id]);
            $rec_year = $check_stmt->fetchColumn();


            $v_id = $_POST['violation_type_id'];
            $week = $_POST['week_number'];
            
            // Xử lý custom time về đúng format Y-m-d H:i:s
            $raw_time = $_POST['date_created'];
            $created_at = date('Y-m-d H:i:s', strtotime($raw_time));

            // Lấy tên và điểm của lỗi mới để cập nhật cho chuẩn
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

            echo json_encode(['status' => 'success', 'msg' => __('update_success', 'Cập nhật thành công!')]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }
}


// =================================================================
// 1. LOGIC BACKEND (HIỂN THỊ DANH SÁCH)
// =================================================================

function getReporterName($row) {
    if (!empty($row['reporter_fullname'])) return $row['reporter_fullname'];
    return $row['reporter_username'] ?? __('system', 'Hệ thống');
}

try {
    // Phân trang
    $page_gate = isset($_GET['page_gate']) ? max(1, (int)$_GET['page_gate']) : 1;
    $page_class = isset($_GET['page_class']) ? max(1, (int)$_GET['page_class']) : 1;
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'tabGate';

    $limit = 50;
    $offset_gate = ($page_gate - 1) * $limit;
    $offset_class = ($page_class - 1) * $limit;

    $current_school_year = get_current_school_year($pdo);

    // Tổng số lượng
    $totalGateStmt = $pdo->prepare("SELECT COUNT(*) FROM violation_record vr JOIN violation_type vt ON vr.violation_type_id = vt.id WHERE (vt.scope = 'GATE' OR vr.student_id IS NOT NULL) AND vr.school_year = ?");
    $totalGateStmt->execute([$current_school_year]);
    $totalGate = $totalGateStmt->fetchColumn();
    $total_pages_gate = ceil($totalGate / $limit);

    $totalClassStmt = $pdo->prepare("SELECT COUNT(*) FROM violation_record vr JOIN violation_type vt ON vr.violation_type_id = vt.id WHERE vt.scope = 'CLASS' AND vr.school_year = ?");
    $totalClassStmt->execute([$current_school_year]);
    $totalClass = $totalClassStmt->fetchColumn();
    $total_pages_class = ceil($totalClass / $limit);

    // Lấy danh sách các lỗi vi phạm Cổng để nhét vào Select Box sửa
    $all_gate_violations = $pdo->query("SELECT id, content, content_en, points FROM violation_type WHERE scope = 'GATE' ORDER BY points ASC")->fetchAll();

    // --- TAB 1: DỮ LIỆU CỔNG (GATE) ---
    $sqlGate = "
        SELECT 
            vr.id, 
            vr.violation_type_id, 
            vr.recorded_violation_name, 
            vt.content AS violation_name_vi,
            vt.content_en AS recorded_violation_name_en,
            vr.recorded_points, 
            vr.date_created, 
            vr.week_number, 
            vr.note,
            vr.is_deleted, 
            s.name AS student_name, 
            s.code AS student_code,
            c.name AS class_name,
            vr.reporter AS reporter_username,
            u.full_name AS reporter_fullname,
            'Active' as status
        FROM violation_record vr
        LEFT JOIN student s ON vr.student_id = s.id
        LEFT JOIN classroom c ON vr.class_id = c.id
        LEFT JOIN users u ON vr.reporter = u.username
        JOIN violation_type vt ON vr.violation_type_id = vt.id
        WHERE (vt.scope = 'GATE' OR vr.student_id IS NOT NULL) AND vr.school_year = :school_year
        ORDER BY vr.date_created DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmtGate = $pdo->prepare($sqlGate);
    $stmtGate->bindValue(':school_year', $current_school_year, PDO::PARAM_STR);
    $stmtGate->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmtGate->bindValue(':offset', (int)$offset_gate, PDO::PARAM_INT);
    $stmtGate->execute();
    $gateLogs = $stmtGate->fetchAll();

    // --- TAB 2: DỮ LIỆU LỚP (CLASS) ---
    $sqlClass = "
        SELECT 
            vr.id,
            vr.recorded_violation_name,
            vt.content AS violation_name_vi,
            vt.content_en AS recorded_violation_name_en,
            vr.recorded_points,
            vr.submitted_at, 
            vr.week_number,
            vr.is_deleted, 
            c.name AS class_name,
            vr.reporter AS reporter_username,
            u.full_name AS reporter_fullname
        FROM violation_record vr
        LEFT JOIN classroom c ON vr.class_id = c.id
        LEFT JOIN users u ON vr.reporter = u.username
        JOIN violation_type vt ON vr.violation_type_id = vt.id
        WHERE vt.scope = 'CLASS' AND vr.school_year = :school_year
        ORDER BY vr.submitted_at DESC
        LIMIT :limit OFFSET :offset
    ";
    $stmtClass = $pdo->prepare($sqlClass);
    $stmtClass->bindValue(':school_year', $current_school_year, PDO::PARAM_STR);
    $stmtClass->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmtClass->bindValue(':offset', (int)$offset_class, PDO::PARAM_INT);
    $stmtClass->execute();
    $classLogs = $stmtClass->fetchAll();

} catch (Exception $e) {
    $error = $e->getMessage();
}

require_once 'views/violation_history_view.php';