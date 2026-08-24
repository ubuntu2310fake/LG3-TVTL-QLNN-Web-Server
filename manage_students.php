<?php
// manage_students.php
require_once 'includes/config.php';
checkRole(['TEACHER', 'ADMIN']);

// XỬ LÝ API QUICK APPROVE (Giữ nguyên)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_approve') {
    ob_clean(); header('Content-Type: application/json');
    $code = $_POST['code'];
    $stmt = $pdo->prepare("SELECT * FROM student WHERE code = ?");
    $stmt->execute([$code]);
    $s = $stmt->fetch();
    if ($s && $s['has_pending_changes']) {
        if ($s['pending_name']) $s['name'] = $s['pending_name'];
        if ($s['pending_dob']) $s['dob'] = $s['pending_dob'];
        $upd = $pdo->prepare("UPDATE student SET name=?, dob=?, has_pending_changes=0, pending_name=NULL, pending_dob=NULL WHERE id=?");
        $upd->execute([$s['name'], $s['dob'], $s['id']]);
        echo json_encode(['status' => 'success']);
    } else { echo json_encode(['status' => 'error', 'msg' => __('no_changes', 'Không có thay đổi')]); }
    exit;
}

// XỬ LÝ PHÂN TRANG & SEARCH
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$class_id = $_GET['class_id'] ?? '';

// Query Students
$sql = "SELECT s.*, c.name as class_name FROM student s JOIN classroom c ON s.class_id = c.id WHERE c.grade < 13 AND c.name NOT LIKE 'K46%'";
$params = [];
if ($search) {
    $sql .= " AND (s.name LIKE ? OR s.code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($class_id) {
    $sql .= " AND s.class_id = ?";
    $params[] = $class_id;
}

// Count total
$countSql = str_replace("SELECT s.*, c.name as class_name", "SELECT COUNT(*)", $sql);
$stmtCnt = $pdo->prepare($countSql);
$stmtCnt->execute($params);
$totalRows = $stmtCnt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// [SỬA] Fetch data: Thêm LENGTH(c.name) để sort tự nhiên (10A2 trước 10A10)
$sql .= " ORDER BY c.grade ASC, LENGTH(c.name) ASC, c.name ASC, s.code ASC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_OBJ);

// [SỬA] Query Classes for Dropdown: Thêm LENGTH(name) ASC
$classes = $pdo->query("SELECT * FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%' ORDER BY grade ASC, LENGTH(name) ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Red Flags Map
$red_flags = [];
$rf_users = $pdo->query("SELECT u.username, c.name as class_name FROM users u JOIN classroom c ON u.homeroom_class_id = c.id WHERE u.role = 'RED_FLAG' AND c.grade < 13 AND c.name NOT LIKE 'K46%'")->fetchAll();
foreach ($rf_users as $u) $red_flags[$u['username']] = $u['class_name'];

require_once 'views/manage_students_view.php';