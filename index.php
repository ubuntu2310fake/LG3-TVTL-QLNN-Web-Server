<?php
// index.php
require_once 'includes/config.php';

$user = $_SESSION['user'] ?? null;
$is_logged_in = !empty($user);
$vapid_public_key = defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '';

// BẢNG XẾP HẠNG TOP 3 LỚP CỦA 3 NHÓM THI ĐUA (TUẦN GẦN NHẤT)
$current_school_year = get_current_school_year($pdo);
$rulesStr = get_config_for_year($pdo, 'ranking_rules', $current_school_year, null);
$rules = $rulesStr ? json_decode($rulesStr, true) : ['max_base'=>60, 'divisor'=>6, 'weight_aca'=>0.5, 'weight_con'=>0.5];

$max_base = floatval($rules['max_base']);
$divisor  = floatval($rules['divisor']);
$weight_aca = floatval($rules['weight_aca']);
$weight_con = floatval($rules['weight_con']);

$start_date_str = get_config_for_year($pdo, 'start_date', $current_school_year, date('Y-09-05'));
$excluded_dates_json = get_config_for_year($pdo, 'excluded_dates', $current_school_year, '[]');
$excluded_list = json_decode($excluded_dates_json, true) ?: [];

$current_week = get_current_week($pdo);
$top_week = $current_week;
$top_3_by_group = [1 => [], 2 => [], 3 => []];
$school_days = 0;

for ($w = $current_week; $w >= 1; $w--) {
    $start_date_obj = new DateTime($start_date_str);
    $week_start = clone $start_date_obj;
    $week_start->modify("+" . (($w - 1) * 7) . " days");
    
    $days = 0;
    for ($i = 0; $i < 7; $i++) {
        $day = clone $week_start;
        $day->modify("+$i days");
        if ($day->format('N') == 7) continue;
        if (!in_array($day->format('Y-m-d'), $excluded_list)) {
            $days++;
        }
    }
    
    if ($days > 0) {
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM academic_score WHERE week_number = ? AND school_year = ?");
        $check_stmt->execute([$w, $current_school_year]);
        $has_data = $check_stmt->fetchColumn() > 0;
        
        if (!$has_data) {
            $check_stmt2 = $pdo->prepare("SELECT COUNT(*) FROM violation_record WHERE week_number = ? AND is_deleted = 0 AND school_year = ?");
            $check_stmt2->execute([$w, $current_school_year]);
            $has_data = $check_stmt2->fetchColumn() > 0;
        }
        
        if ($has_data) {
            $school_days = $days;
            $top_week = $w;
            break;
        }
    }
}

if ($school_days > 0) {
    $raw_classes = $pdo->query("SELECT * FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%'")->fetchAll();
    $classes = [];
    foreach($raw_classes as $c) { $classes[$c['id']] = $c; }
    $classes = array_values($classes);
    
    $week_results = [];
    foreach ($classes as $c) {
        $stmt_aca = $pdo->prepare("SELECT period_count, total_score, bonus_score FROM academic_score WHERE class_id = ? AND week_number = ? AND school_year = ?");
        $stmt_aca->execute([$c['id'], $top_week, $current_school_year]);
        $acad = $stmt_aca->fetch();

        $raw_period_count = $acad ? (int)$acad['period_count'] : 0;
        $raw_total_score = $acad ? (float)$acad['total_score'] : 0;
        $bonus = $acad ? (float)$acad['bonus_score'] : 0;
        $w_score_aca = ($raw_period_count > 0) ? ($raw_total_score / $raw_period_count) : 0;

        $stmt_vio = $pdo->prepare("SELECT SUM(recorded_points) as total FROM violation_record WHERE class_id = ? AND week_number = ? AND is_deleted = 0 AND school_year = ?");
        $stmt_vio->execute([$c['id'], $top_week, $current_school_year]);
        $w_total_penalty = (float)($stmt_vio->fetchColumn() ?: 0);

        $base_per_day = $max_base / 6;
        $divisor_per_day = $divisor / 6;
        $max_base_dynamic = $base_per_day * $school_days;
        $divisor_dynamic = $divisor_per_day * $school_days;
        
        $w_score_con = max(0, ($max_base_dynamic - ($w_total_penalty / $school_days)) / $divisor_dynamic);
        $w_total = (($w_score_aca * $weight_aca) + ($w_score_con * $weight_con)) + $bonus;

        $week_results[] = [
            'class_name' => $c['name'],
            'group' => $c['competition_group'] ?? 1,
            'tb' => number_format($w_total, 2),
            '_raw_total' => $w_total
        ];
    }
    
    foreach ([1, 2, 3] as $g_id) {
        $group_items = array_filter($week_results, function($item) use ($g_id) { return (int)$item['group'] === $g_id; });
        usort($group_items, function($a, $b) { return $b['_raw_total'] <=> $a['_raw_total']; });
        $top_3_by_group[$g_id] = array_slice($group_items, 0, 3);
    }
}

require_once 'views/index_view.php';
?>