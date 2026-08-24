<?php
require_once '../includes/config.php';
$pdo->exec("TRUNCATE TABLE consulting_questions");

// Holland
$holland = [
    'R' => ['R1'=>'Tôi thích sửa chữa máy móc', 'R2'=>'Tôi thích làm việc ngoài trời', 'R3'=>'Tôi thích lắp ráp đồ điện tử'],
    'I' => ['I1'=>'Tôi thích đọc sách khoa học', 'I2'=>'Tôi thích làm thí nghiệm', 'I3'=>'Tôi thích giải quyết các vấn đề phức tạp'],
    'A' => ['A1'=>'Tôi thích vẽ, thiết kế', 'A2'=>'Tôi thích viết lách, sáng tác', 'A3'=>'Tôi thích chơi nhạc cụ'],
    'S' => ['S1'=>'Tôi thích làm công tác xã hội', 'S2'=>'Tôi thích giảng dạy, truyền đạt', 'S3'=>'Tôi thích giúp đỡ người bệnh'],
    'E' => ['E1'=>'Tôi thích kinh doanh buôn bán', 'E2'=>'Tôi thích lãnh đạo đội nhóm', 'E3'=>'Tôi thích thuyết phục người khác'],
    'C' => ['C1'=>'Tôi thích làm việc với con số', 'C2'=>'Tôi thích phân loại tài liệu', 'C3'=>'Tôi thích các công việc có quy trình rõ ràng']
];

foreach ($holland as $g => $qs) {
    foreach ($qs as $c => $q) {
        $pdo->prepare("INSERT INTO consulting_questions (question_code, test_type, group_code, content) VALUES (?, 'HOLLAND', ?, ?)")->execute([$c, $g, $q]);
    }
}

// MI
$mi = [
    'Logic' => ['L1'=>'Tôi tính nhẩm rất nhanh', 'L2'=>'Tôi thích chơi cờ, giải đố logic'],
    'NgonNgu' => ['N1'=>'Tôi dễ dàng học ngoại ngữ', 'N2'=>'Tôi thích kể chuyện, diễn đạt bằng lời']
];
foreach ($mi as $g => $qs) {
    foreach ($qs as $c => $q) {
        $pdo->prepare("INSERT INTO consulting_questions (question_code, test_type, group_code, content) VALUES (?, 'MI', ?, ?)")->execute([$c, $g, $q]);
    }
}

echo "Migrated successfully!";
?>
