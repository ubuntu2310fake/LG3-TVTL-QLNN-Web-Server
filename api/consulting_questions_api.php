<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$isEn = (isset($_GET['lang']) && $_GET['lang'] == 'en') || (isset($_SESSION['lang']) && $_SESSION['lang'] == 'en') || (isset($_COOKIE['lang']) && $_COOKIE['lang'] == 'en');

$data = [
    'discData' => [],
    'mtvtDb' => [],
    'hollandData' => [],
    'miData' => []
];

// Fetch ALL questions from DB
$stmt = $pdo->query("SELECT test_type, group_code, question_code, content, en_content FROM consulting_questions ORDER BY id ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $type = $row['test_type'];
    $group = $row['group_code'];
    $code = $row['question_code'];
    $content = ($isEn && !empty($row['en_content'])) ? $row['en_content'] : $row['content'];

    if ($type == 'HOLLAND') {
        $data['hollandData'][$group][] = ['code' => $code, 'text' => $content];
    } elseif ($type == 'MI') {
        $data['miData'][$group][] = ['code' => $code, 'text' => $content];
    } elseif ($type == 'DISC') {
        $data['discData'][$group][] = $content;
    } elseif ($type == 'MTVT') {
        // Handle both ':' and '_' separators from DB (Aesthetic_h or Aesthetic:h)
        $groupStr = str_replace('_', ':', $group);
        $parts = explode(':', $groupStr);
        $cat = $parts[0];
        $hl = $parts[1] ?? 'h';
        
        // Translate category if not English
        if (!$isEn) {
            $catMap = [
                'Aesthetic' => 'Thẩm mỹ',
                'Economic' => 'Kinh tế',
                'Individualistic' => 'Cá nhân',
                'Power' => 'Quyền lực',
                'Altruistic' => 'Vị tha',
                'Regulatory' => 'Quy tắc',
                'Theoretical' => 'Lý thuyết'
            ];
            $cat = $catMap[$cat] ?? $cat;
        }

        if (!isset($data['mtvtDb'][$cat])) {
            $data['mtvtDb'][$cat] = ['h' => [], 'l' => []];
        }
        $data['mtvtDb'][$cat][$hl][] = $content;
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>
