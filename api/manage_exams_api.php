<?php
// api/manage_exams_api.php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

// Chặn nếu không phải Admin hoặc quyền tương đương
checkRole(['ADMIN']); 

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT * FROM lg3_exams ORDER BY id DESC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'create':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO lg3_exams (exam_name, exam_name_en, school_name) VALUES (?, ?, ?)");
            $stmt->execute([$data['name'], $data['name_en'], $data['school']]);
            echo json_encode(['status' => 'success']);
            break;

        case 'delete':
            $id = (int)$_GET['id'];
            // Xóa điểm trước
            $pdo->prepare("DELETE FROM lg3_exam_scores WHERE exam_id = ?")->execute([$id]);
            // Xóa cấu hình môn
            $pdo->prepare("DELETE FROM lg3_exam_config WHERE exam_id = ?")->execute([$id]);
            // Xóa kỳ thi
            $pdo->prepare("DELETE FROM lg3_exams WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}