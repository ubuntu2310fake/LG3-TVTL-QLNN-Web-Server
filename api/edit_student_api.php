<?php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['ADMIN', 'TEACHER'])) { 
    echo json_encode(['status'=>'error', 'msg'=>__('no_permission', 'Không có quyền')]); exit; 
}

// HÀM ĐỒNG BỘ ẢNH VỚI APP PYTHON
function syncAvatarToPython($username, $avatarUrl) {
    // Đã đóng cURL đồng bộ sang Python Flask
    return;
}

// XỬ LÝ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if (empty($action)) {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $id = $input['id'] ?? 0;
    }
    
    if ($action == 'approve_changes') {
        $pdo->prepare("UPDATE student SET name=pending_name, dob=pending_dob, has_pending_changes=0, pending_name=NULL, pending_dob=NULL WHERE id=?")->execute([$id]);
        echo json_encode(['status'=>'success', 'msg'=>__('approved_changes', 'Đã duyệt')]); exit;
    } elseif ($action == 'reject_changes') {
        $pdo->prepare("UPDATE student SET has_pending_changes=0, pending_name=NULL, pending_dob=NULL WHERE id=?")->execute([$id]);
        echo json_encode(['status'=>'success', 'msg'=>__('rejected_changes', 'Đã từ chối')]); exit;
    } elseif ($action == 'update_direct') {
        $stmt = $pdo->prepare("SELECT * FROM student WHERE id = ?");
        $stmt->execute([$id]);
        $student = $stmt->fetch(PDO::FETCH_OBJ);
        
        $name = $_POST['name'] ?? '';
        $dob = $_POST['dob'] ?? '';
        $class_id = $_POST['class_id'] ?? '';
        $image_url = $student->image_url;

        // Xóa ảnh
        if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1' && $student->image_url) {
            if (file_exists("../" . $student->image_url)) unlink("../" . $student->image_url);
            $image_url = null;
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            syncAvatarToPython($student->code, $domain . "/static/default.png");
        }

        // Đổi ảnh mới
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)) ?: 'jpg';
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $target_dir = "../static/uploads/avatars/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                $filename = "s{$id}_" . time() . ".jpg";
                $target_file = $target_dir . $filename;
                
                $saved = false;
                if (function_exists('imagecreatefromstring')) {
                    $rawImgData = @file_get_contents($_FILES['image']['tmp_name']);
                    if ($rawImgData !== false) {
                        $srcImg = @imagecreatefromstring($rawImgData);
                        if ($srcImg !== false) {
                            $saved = imagejpeg($srcImg, $target_file, 90);
                            imagedestroy($srcImg);
                        }
                    }
                }
                if (!$saved) {
                    $saved = move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
                }
                
                if ($saved) {
                    if ($student->image_url && file_exists("../" . $student->image_url)) unlink("../" . $student->image_url);
                    $image_url = "static/uploads/avatars/" . $filename;
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                    $domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                    syncAvatarToPython($student->code, $domain . "/" . $image_url);
                }
            }
        }

        // Cập nhật Student
        $thuylinh = isset($_POST['thuylinh']) && $_POST['thuylinh'] !== '' ? (int)$_POST['thuylinh'] : null;
        $pdo->prepare("UPDATE student SET name=?, dob=?, class_id=?, image_url=?, thuylinh=? WHERE id=?")->execute([$name, $dob, $class_id, $image_url, $thuylinh, $id]);

        // Cập nhật User Role (Cờ đỏ)
        $role = $_POST['user_role'] ?? 'STUDENT';
        $standing = !empty($_POST['standing_class_id']) ? $_POST['standing_class_id'] : null;
        
        $stmtU = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmtU->execute([$student->code]);
        $linked_user = $stmtU->fetch(PDO::FETCH_OBJ);

        if ($linked_user) {
            $pdo->prepare("UPDATE users SET role=?, homeroom_class_id=?, avatar=? WHERE id=?")
                ->execute([$role, $standing, $image_url, $linked_user->id]);
        }

        echo json_encode(['status'=>'success', 'msg'=>__('info_saved', 'Đã lưu thông tin')]); exit;
    }
}

// XỬ LÝ GET
$code = $_GET['code'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM student WHERE code = ?"); $stmt->execute([$code]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// --- ĐÃ SỬA: Sắp xếp tự nhiên (10A2 đứng trước 10A10) ---
$classes = $pdo->query("SELECT id, name FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%' ORDER BY grade ASC, LENGTH(name) ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($student) {
    $stmtU = $pdo->prepare("SELECT role, homeroom_class_id FROM users WHERE username = ?");
    $stmtU->execute([$code]);
    $linked_user = $stmtU->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'student' => $student, 'classes' => $classes, 'linked_user' => $linked_user]);
} else {
    echo json_encode(['status' => 'error', 'msg' => __('student_not_found', 'Không tìm thấy học sinh')]);
}
?>