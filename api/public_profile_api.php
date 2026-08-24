<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$code = isset($_GET['code']) ? strtoupper(trim($_GET['code'])) : '';

if (empty($code)) {
    echo json_encode(['status' => 'error', 'msg' => 'Missing code']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.*, c.name AS class_name 
    FROM student s 
    LEFT JOIN classroom c ON s.class_id = c.id 
    WHERE s.code = ?
");
$stmt->execute([$code]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo json_encode(['status' => 'error', 'msg' => 'Student not found']);
    exit;
}

// Tìm tài khoản user tương ứng
$stmtU = $pdo->prepare("SELECT id, username, full_name, avatar FROM users WHERE username = ?");
$stmtU->execute([$code]);
$targetUser = $stmtU->fetch(PDO::FETCH_ASSOC);

$target_user_id = $targetUser ? (int)$targetUser['id'] : null;
$my_id = isset($_SESSION['user']) ? (int)$_SESSION['user']['id'] : null;

$is_self = ($my_id && $target_user_id && $my_id === $target_user_id);
$relation = 'none'; // 'none', 'sent', 'received', 'friend'
$req_id = null;

if ($my_id && $target_user_id && !$is_self) {
    $stmtF = $pdo->prepare("
        SELECT id, user_id_1, user_id_2, status 
        FROM friendships 
        WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)
    ");
    $stmtF->execute([$my_id, $target_user_id, $target_user_id, $my_id]);
    $rel = $stmtF->fetch(PDO::FETCH_ASSOC);
    if ($rel) {
        $req_id = (int)$rel['id'];
        if ($rel['status'] === 'accepted') {
            $relation = 'friend';
        } elseif ($rel['status'] === 'pending') {
            if ((int)$rel['user_id_1'] === $my_id) {
                $relation = 'sent';
            } else {
                $relation = 'received';
            }
        }
    }
}

echo json_encode([
    'status' => 'success',
    'student' => $student,
    'target_user_id' => $target_user_id,
    'is_self' => $is_self,
    'relation' => $relation,
    'req_id' => $req_id,
    'is_logged_in' => ($my_id !== null)
]);
?>
