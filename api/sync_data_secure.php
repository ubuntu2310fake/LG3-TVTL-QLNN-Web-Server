<?php
// api/sync_data_secure.php
error_reporting(0); // Tắt in lỗi ngầm tránh hỏng JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
require_once '../includes/config.php';

try {
    $current_week = function_exists('get_current_week') ? get_current_week($pdo) : 20;
    
    $stmtStu = $pdo->query("SELECT s.id, s.code, s.name, s.image_url, c.name as class_name FROM student s LEFT JOIN classroom c ON s.class_id = c.id WHERE c.grade < 13 AND c.name NOT LIKE 'K46%'");
    $students = $stmtStu->fetchAll(PDO::FETCH_ASSOC);

    $stmtCls = $pdo->query("SELECT id, name, grade FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%'");
    $classes = $stmtCls->fetchAll(PDO::FETCH_ASSOC);
    usort($classes, function($a, $b) {
        if ($a['grade'] != $b['grade']) return $a['grade'] - $b['grade'];
        return strnatcmp($a['name'], $b['name']);
    });

    $stmtGate = $pdo->query("SELECT id, content as name, content_en as name_en, points FROM violation_type WHERE scope = 'GATE' ORDER BY points ASC");
    $gate_violations = $stmtGate->fetchAll(PDO::FETCH_ASSOC);

    $stmtClsCols = $pdo->query("SELECT short_code, max_penalty_points FROM violation_type WHERE scope = 'CLASS' ORDER BY id ASC");
    $dbScores = [];
    while ($row = $stmtClsCols->fetch(PDO::FETCH_ASSOC)) {
        $dbScores[$row['short_code']] = (float)($row['max_penalty_points'] !== null ? $row['max_penalty_points'] : 1.0);
    }
    $defaultOrder = ["SS", "VS", "CSVC", "TB", "XE", "DP", "SV", "THE", "DT"];
    $class_cols = [];
    foreach ($defaultOrder as $code) {
        if (isset($dbScores[$code])) {
            $class_cols[] = ['code' => $code, 'max' => $dbScores[$code]];
            unset($dbScores[$code]);
        }
    }
    foreach ($dbScores as $code => $max) {
        $class_cols[] = ['code' => $code, 'max' => $max];
    }
    if (empty($class_cols)) {
        $baseScores = ["SS"=>1, "VS"=>1, "CSVC"=>1, "TB"=>1, "XE"=>1, "DP"=>2, "SV"=>1, "THE"=>1, "DT"=>1];
        foreach($baseScores as $code => $max) { $class_cols[] = ['code' => $code, 'max' => (float)$max]; }
    }

    $labels = [
        'nn' => 'NN', 'nn_en' => 'Behavior',
        'ht' => 'HT', 'ht_en' => 'Academic',
        'vpbs' => 'VPBS', 'vpbs_en' => 'Penalty',
        'tong_tiet' => 'Tổng tiết', 'tong_tiet_en' => 'Total Periods',
        'thuong' => 'Thưởng', 'thuong_en' => 'Bonus',
        'nhom_thi_dua' => 'Nhóm Thi Đua', 'nhom_thi_dua_en' => 'Competition Group'
    ];

    $rawData = json_encode([
        'current_week' => $current_week,
        'students' => $students,
        'classes' => $classes,
        'gate_violations' => $gate_violations,
        'class_cols' => $class_cols,
        'labels' => $labels
    ], JSON_UNESCAPED_UNICODE);

    // MÃ HÓA AES-256-CBC
    $secret_key = defined('SSO_SECRET_KEY') ? SSO_SECRET_KEY : 'your_sso_secret_key_here';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($rawData, 'aes-256-cbc', $secret_key, 0, $iv);
    
    $securePayload = base64_encode($iv) . ':' . $encrypted;

    echo json_encode([
        'status' => 'success',
        'secure_payload' => $securePayload
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => __('server_error_prefix', 'Lỗi máy chủ: ') . $e->getMessage()]);
}
?>