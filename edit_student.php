<?php
// edit_student.php
require_once 'includes/config.php';
checkRole(['TEACHER', 'ADMIN']);

function syncAvatarToPython($username, $avatarUrl) {
    // Đã đóng cURL đồng bộ sang Python Flask
    return;
}

// Lấy student ID từ GET
$student_code = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM student WHERE code = ?");
$stmt->execute([$student_code]);
$student = $stmt->fetch(PDO::FETCH_OBJ);

if (!$student) die(__('student_not_found_err', "Không tìm thấy học sinh"));

// Lấy User liên kết
$stmtU = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmtU->execute([$student->code]);
$linked_user = $stmtU->fetch(PDO::FETCH_OBJ);

// XỬ LÝ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_clean(); header('Content-Type: application/json');
    $action = $_POST['action'];
    
    try {
        if ($action == 'update_direct') {
            $name = $_POST['name'];
            $dob = $_POST['dob'];
            $class_id = $_POST['class_id'];
            $image_url = $student->image_url;

            // 1. XỬ LÝ KHI XÓA ẢNH (Về mặc định)
            if ($_POST['delete_image'] == '1' && $student->image_url) {
                if (file_exists($student->image_url)) unlink($student->image_url);
                $image_url = null;

                // [SYNC PYTHON] Gửi ảnh mặc định
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                $domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $defaultUrl = $domain . "/static/default.png";
                syncAvatarToPython($student->code, $defaultUrl);
            }

            // 2. XỬ LÝ KHI UPLOAD ẢNH MỚI
            if (!empty($_FILES['image']['name'])) {
                $target_dir = "static/uploads/avatars/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                
                // Thêm time() vào tên file để tránh cache (như bên my_profile)
                $filename = uniqid() . "_" . time() . "_" . basename($_FILES["image"]["name"]);
                $target_file = $target_dir . $filename;
                
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // Xóa ảnh cũ nếu có
                    if ($student->image_url && file_exists($student->image_url)) unlink($student->image_url);
                    $image_url = $target_file;

                    // [SYNC PYTHON] Gửi ảnh mới vừa up
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
                    $domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
                    $fullUrl = $domain . "/" . $target_file;
                    syncAvatarToPython($student->code, $fullUrl);
                }
            }

            // Update Student
            $sql = "UPDATE student SET name=?, dob=?, class_id=?, image_url=? WHERE id=?";
            $pdo->prepare($sql)->execute([$name, $dob, $class_id, $image_url, $student->id]);

            // Update User
            if ($linked_user) {
                $role = $_POST['user_role'];
                $standing = $_POST['standing_class_id'] ?: null;
                $pdo->prepare("UPDATE users SET role=?, homeroom_class_id=?, avatar=? WHERE id=?")->execute([$role, $standing, $image_url, $linked_user->id]);
            }

            echo json_encode(['status' => 'success', 'msg' => __('update_success', 'Cập nhật thành công!'), 'new_avatar_url' => $image_url]);
        } 
        elseif ($action == 'approve_changes' || $action == 'reject_changes') {
            if ($action == 'approve_changes') {
                $pdo->prepare("UPDATE student SET name=pending_name, dob=pending_dob, has_pending_changes=0, pending_name=NULL, pending_dob=NULL WHERE id=?")->execute([$student->id]);
            } else {
                $pdo->prepare("UPDATE student SET has_pending_changes=0, pending_name=NULL, pending_dob=NULL WHERE id=?")->execute([$student->id]);
            }
            echo json_encode(['status' => 'success', 'msg' => __('request_processed', 'Đã xử lý yêu cầu'), 'reload' => true]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// Lấy danh sách lớp
$classes = $pdo->query("SELECT * FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%' ORDER BY grade ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once 'views/edit_student_view.php';