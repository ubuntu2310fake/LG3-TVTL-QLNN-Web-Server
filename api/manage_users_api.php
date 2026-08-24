<?php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') { echo json_encode(['status' => 'error', 'msg' => __('no_permission', 'Không có quyền')]); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    if ($action == 'create') {
        $u = $input['username']; $p = password_hash($input['password'], PASSWORD_DEFAULT);
        $f = $input['full_name']; $r = $input['role'];
        $chk = $pdo->prepare("SELECT id FROM users WHERE username=?"); $chk->execute([$u]);
        if ($chk->fetch()) { echo json_encode(['status'=>'error', 'msg'=>__('account_exists', 'Tài khoản đã tồn tại!')]); exit; }
        $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, is_default_password) VALUES (?, ?, ?, ?, 'on')")->execute([$u, $p, $f, $r]);
        echo json_encode(['status'=>'success', 'msg'=>__('create_success', 'Tạo thành công!')]); exit;
    } elseif ($action == 'assign_homeroom') {
        $uid = $input['user_id']; $cid = empty($input['class_id']) ? null : $input['class_id'];
        $pdo->prepare("UPDATE users SET homeroom_class_id=? WHERE id=?")->execute([$cid, $uid]);
        echo json_encode(['status'=>'success', 'msg'=>__('class_assigned', 'Đã phân công lớp!')]); exit;
    }
}

$users = $pdo->query("SELECT u.id, u.username, u.full_name, u.role, u.homeroom_class_id, c.name as class_name FROM users u LEFT JOIN classroom c ON u.homeroom_class_id = c.id WHERE u.role != 'STUDENT' ORDER BY u.role, u.full_name")->fetchAll(PDO::FETCH_ASSOC);
$classes = $pdo->query("SELECT id, name FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%' ORDER BY grade ASC, LENGTH(name) ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['status' => 'success', 'users' => $users, 'classes' => $classes]);
?>