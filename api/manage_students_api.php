<?php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra quyền
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['ADMIN', 'TEACHER'])) { 
    echo json_encode(['status'=>'error', 'msg'=>__('no_permission', 'Không có quyền')]);
    exit; 
}
session_write_close(); // Giải phóng session lock sớm

// Xử lý POST (Duyệt nhanh thay đổi)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (($input['action'] ?? '') === 'quick_approve') {
        $stmt = $pdo->prepare("SELECT * FROM student WHERE code = ?"); 
        $stmt->execute([$input['code']]); 
        $s = $stmt->fetch();
        
        if ($s && $s['has_pending_changes']) {
            $name = $s['pending_name'] ?: $s['name']; 
            $dob = $s['pending_dob'] ?: $s['dob'];
            $pdo->prepare("UPDATE student SET name=?, dob=?, has_pending_changes=0, pending_name=NULL, pending_dob=NULL WHERE id=?")->execute([$name, $dob, $s['id']]);
            echo json_encode(['status' => 'success', 'msg' => __('changes_approved', 'Đã duyệt thay đổi!')]);
        } else { 
            echo json_encode(['status' => 'error', 'msg' => __('no_pending_requests', 'Không có yêu cầu chờ duyệt')]); 
        }
        exit;
    }
}

// Lấy tham số GET
$search = $_GET['search'] ?? ''; 
$class_id = $_GET['class_id'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = 20;
$offset = ($page - 1) * $limit;

$whereClause = "WHERE 1=1";
$params = [];

if ($search) { 
    $whereClause .= " AND (s.name LIKE ? OR s.code LIKE ?)"; 
    $params[] = "%$search%"; 
    $params[] = "%$search%"; 
}
if ($class_id) { 
    $whereClause .= " AND s.class_id = ?"; 
    $params[] = $class_id; 
}

// Đếm tổng
$countSql = "SELECT COUNT(*) FROM student s $whereClause";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalStudents = $stmtCount->fetchColumn();
$totalPages = ceil($totalStudents / $limit);

// Lấy dữ liệu 
$sql = "SELECT s.id, s.code, s.name, s.dob, s.has_pending_changes, s.image_url, c.name as class_name,
               u.role, hc.name as homeroom_class_name
        FROM student s 
        JOIN classroom c ON s.class_id = c.id 
        LEFT JOIN users u ON s.code = u.username
        LEFT JOIN classroom hc ON u.homeroom_class_id = hc.id
        $whereClause 
        ORDER BY 
            CAST(SUBSTRING(s.code, 2, 2) AS UNSIGNED) DESC, 
            CAST(SUBSTRING(s.code, 5, LENGTH(s.code) - 7) AS UNSIGNED) ASC, 
            CAST(RIGHT(s.code, 3) AS UNSIGNED) ASC 
        LIMIT $limit OFFSET $offset";

$students = $pdo->prepare($sql); 
$students->execute($params);

// --- Classroom list với Redis cache (TTL 10 phút - ít thay đổi) ---
$classes = null;
if ($redis_connected) {
    $cached_classes = $redis->get('classrooms_active');
    if ($cached_classes !== false) $classes = json_decode($cached_classes, true);
}
if ($classes === null) {
    $classes = $pdo->query("SELECT id, name FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%' ORDER BY grade ASC, LENGTH(name) ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    if ($redis_connected) $redis->setex('classrooms_active', 600, json_encode($classes));
}

echo json_encode([
    'status' => 'success', 
    'students' => $students->fetchAll(PDO::FETCH_ASSOC), 
    'classes' => $classes,
    'total_pages' => $totalPages,
    'current_page' => $page
]);
?>