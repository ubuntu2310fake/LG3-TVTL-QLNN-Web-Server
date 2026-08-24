<?php
require_once '../includes/config.php';
require '../vendor/autoload.php'; // Gọi thư viện PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép Admin
if (session_status() === PHP_SESSION_NONE) if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
    echo json_encode(['status' => 'error', 'msg' => __('no_permission', 'Không có quyền truy cập')]);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'preview' || $action === 'import') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['status' => 'error', 'msg' => __('invalid_excel_file', 'Vui lòng chọn file Excel hợp lệ.')]);
        exit;
    }

    $tmpPath = $_FILES['file']['tmp_name'];
    
    try {
        $spreadsheet = IOFactory::load($tmpPath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Bỏ qua dòng tiêu đề (Dòng 1)
        array_shift($rows);

        $data = [];
        foreach ($rows as $row) {
            // Giả định cột theo format cũ: [0] SBD, [1] Tên, [2] Lớp, [3] Ngày sinh
            $code = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            $class_name = trim($row[2] ?? '');
            $dob = trim($row[3] ?? '');

            if (empty($code) || empty($name)) continue;

            $data[] = [
                'code' => $code,
                'name' => $name,
                'class_name' => $class_name,
                'dob' => $dob
            ];
        }

        // XỬ LÝ: PREVIEW
        if ($action === 'preview') {
            echo json_encode([
                'status' => 'success',
                'total_rows' => count($data),
                'data' => array_slice($data, 0, 50) // Trả về 50 dòng đầu cho nhẹ
            ]);
            exit;
        }

        // XỬ LÝ: IMPORT THẬT VÀO DB
        if ($action === 'import') {
            $pdo->beginTransaction();

            foreach ($data as $item) {
                $code = $item['code'];
                $name = $item['name'];
                $className = $item['class_name'];

                // 1. Kiểm tra và tạo Lớp học nếu chưa có
                $class_id = null;
                if (!empty($className)) {
                    $stmtClass = $pdo->prepare("SELECT id FROM classroom WHERE name = ?");
                    $stmtClass->execute([$className]);
                    $class_id = $stmtClass->fetchColumn();

                    if (!$class_id) {
                        // Tính Khối (Grade) từ tên lớp (VD: 10A1 -> 10)
                        $grade = 10;
                        if (preg_match('/^(\d+)/', $className, $matches)) {
                            $grade = (int)$matches[1];
                        }
                        $stmtInsertClass = $pdo->prepare("INSERT INTO classroom (name, grade) VALUES (?, ?)");
                        $stmtInsertClass->execute([$className, $grade]);
                        $class_id = $pdo->lastInsertId();
                    }
                }

                // 2. Kiểm tra Học sinh (SBD) -> Insert hoặc Update
                $stmtCheckStu = $pdo->prepare("SELECT id FROM student WHERE code = ?");
                $stmtCheckStu->execute([$code]);
                $stu_id = $stmtCheckStu->fetchColumn();

                if ($stu_id) {
                    // Update nếu đã tồn tại
                    $stmtUpdateStu = $pdo->prepare("UPDATE student SET name = ?, class_id = ? WHERE code = ?");
                    $stmtUpdateStu->execute([$name, $class_id, $code]);
                } else {
                    // Insert mới
                    $stmtInsertStu = $pdo->prepare("INSERT INTO student (code, name, class_id) VALUES (?, ?, ?)");
                    $stmtInsertStu->execute([$code, $name, $class_id]);
                }
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'msg' => __('imported_students_prefix', 'Đã import ') . count($data) . __('imported_students_suffix', ' học sinh.')]);
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'msg' => __('excel_processing_error', 'Lỗi xử lý file Excel: ') . $e->getMessage()]);
        exit;
    }
}

// XỬ LÝ: LÀM SẠCH DATABASE
if ($action === 'reset') {
    try {
        $pdo->beginTransaction();
        // Lệnh này cực kỳ nguy hiểm, xóa toàn bộ Học sinh và Lớp
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $pdo->exec("TRUNCATE TABLE student;");
        $pdo->exec("TRUNCATE TABLE classroom;");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        $pdo->commit();

        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'msg' => __('invalid_action', 'Action không hợp lệ')]);
?>