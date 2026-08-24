<?php
// manage_users.php
require_once 'includes/config.php';
checkRole(['ADMIN']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean(); header('Content-Type: application/json');
    $action = $_POST['action'];
    
    try {
        if ($action == 'create') {
            $u = $_POST['username'];
            $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $f = $_POST['full_name'];
            $r = $_POST['role'];
            
            // Check exist
            $chk = $pdo->prepare("SELECT id FROM users WHERE username=?");
            $chk->execute([$u]);
            if ($chk->fetch()) die(json_encode(['status'=>'error', 'msg'=>__('user_exists', 'User đã tồn tại')]));
            
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, is_default_password) VALUES (?, ?, ?, ?, 'on')");
            $stmt->execute([$u, $p, $f, $r]);
            echo json_encode(['status'=>'success', 'msg'=>__('create_success', 'Tạo thành công'), 'reload'=>true]);
        } 
        elseif ($action == 'assign_homeroom') {
            $uid = $_POST['user_id'];
            $cid = $_POST['class_id'] ?: null;
            $pdo->prepare("UPDATE users SET homeroom_class_id=? WHERE id=?")->execute([$cid, $uid]);
            echo json_encode(['status'=>'success', 'msg'=>__('updated_class', 'Đã cập nhật lớp')]);
        }
    } catch (Exception $e) {
        echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]);
    }
    exit;
}

// Fetch users & classes
$users = $pdo->query("SELECT u.*, c.name as class_name FROM users u LEFT JOIN classroom c ON u.homeroom_class_id=c.id WHERE role != 'STUDENT'")->fetchAll(PDO::FETCH_OBJ);
$classes = $pdo->query("SELECT * FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%' ORDER BY grade ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'views/manage_users_view.php';