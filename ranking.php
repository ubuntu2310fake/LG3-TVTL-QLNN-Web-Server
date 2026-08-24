<?php
// ranking.php (CONTROLLER)
require_once 'includes/db.php';
require_once 'includes/functions.php';

// --- PHẦN 1: LOGIC TÍNH TOÁN ĐIỂM TỪ DATABASE ---
$current_school_year = get_current_school_year($pdo);
$rulesStr = get_config_for_year($pdo, 'ranking_rules', $current_school_year, null);
$rules = $rulesStr ? json_decode($rulesStr, true) : ['max_base'=>60, 'divisor'=>6, 'weight_aca'=>0.5, 'weight_con'=>0.5];

$max_base = floatval($rules['max_base']);
$divisor  = floatval($rules['divisor']);
// ĐỔI TÊN BIẾN TRỌNG SỐ ĐỂ KHÔNG BỊ TRÙNG LẶP
$weight_aca = floatval($rules['weight_aca']);
$weight_con = floatval($rules['weight_con']);

$start_date_str = get_config_for_year($pdo, 'start_date', $current_school_year, date('Y-09-05'));
$excluded_dates_json = get_config_for_year($pdo, 'excluded_dates', $current_school_year, '[]');
$excluded_list = json_decode($excluded_dates_json, true) ?: [];
$start_year = (int)date('Y', strtotime($start_date_str));

$end_hk1_date = get_config_for_year($pdo, 'end_hk1_date', $current_school_year, '');
if (empty($end_hk1_date)) $end_hk1_date = ($start_year + 1) . "-01-31";
$end_year_date = get_config_for_year($pdo, 'end_year_date', $current_school_year, '');
if (empty($end_year_date)) $end_year_date = ($start_year + 1) . "-05-31";

// Hàm quy đổi ngày sang tuần (đã trừ ngày nghỉ)
$get_week_by_date = function($date_str) use ($start_date_str, $excluded_list) {
    $start = new DateTime($start_date_str);
    $target = new DateTime($date_str);
    $target->setTime(0,0,0); $start->setTime(0,0,0);
    if ($target < $start) return 1;

    $diff = $start->diff($target);
    $exclude_count = 0;
    foreach ($excluded_list as $ex_date_str) {
        $ex_date = new DateTime($ex_date_str); $ex_date->setTime(0,0,0);
        if ($ex_date >= $start && $ex_date <= $target) $exclude_count++;
    }
    return max(1, floor(($diff->days - $exclude_count) / 7) + 1);
};

// NGĂN CHẶN TRÌNH DUYỆT LƯU CACHE (ĐẢM BẢO NHẬN JS MỚI)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// --- PHẦN 2: XỬ LÝ LỌC TỪ GIAO DIỆN CHÍNH ---
$filter_type = $_GET['filter_type'] ?? 'week';
$selected_week = isset($_GET['week']) && $_GET['week'] !== '' ? (int)$_GET['week'] : get_current_week($pdo);
$selected_month = $_GET['month'] ?? '';

$start_week = $selected_week;
$end_week = $selected_week;
$filter_label = __('week', 'Tuần') . " $selected_week";

if ($filter_type === 'month' && !empty($selected_month)) {
    if (strpos($selected_month, 'm_') === 0) {
        $m = (int)str_replace('m_', '', $selected_month);
        $y = ($m >= 8 && $m <= 12) ? $start_year : $start_year + 1;
        $first_day = "$y-" . str_pad($m, 2, '0', STR_PAD_LEFT) . "-01";
        $last_day = date('Y-m-t', strtotime($first_day));
        if (strtotime($first_day) < strtotime($start_date_str)) $first_day = $start_date_str;

        $start_week = $get_week_by_date($first_day);
        $end_week = $get_week_by_date($last_day);
        $filter_label = __('month', 'Tháng') . " $m";
    } elseif ($selected_month == 'hk1') {
        $start_week = $get_week_by_date($start_date_str);
        $end_week = $get_week_by_date($end_hk1_date);
        $filter_label = __('semester_1', 'Học kỳ 1');
    } elseif ($selected_month == 'hk2') {
        $start_week = $get_week_by_date(date('Y-m-d', strtotime($end_hk1_date . ' + 1 day')));
        $end_week = $get_week_by_date($end_year_date);
        $filter_label = __('semester_2', 'Học kỳ 2');
    } elseif ($selected_month == 'year') {
        $start_week = $get_week_by_date($start_date_str);
        $end_week = $get_week_by_date($end_year_date);
        $filter_label = __('full_year', 'Cả năm học');
    }
}

if ($start_week > $end_week) { $temp = $start_week; $start_week = $end_week; $end_week = $temp; }
$n_weeks = ($end_week - $start_week) + 1;
if ($n_weeks < 1) $n_weeks = 1;

// --- PHẦN 3: VÒNG LẶP TÍNH ĐIỂM (LOGIC CHUẨN XÁC) ---
$raw_classes = $pdo->query("SELECT * FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%'")->fetchAll();
// Khử trùng lặp lớp
$classes = [];
foreach($raw_classes as $c) { $classes[$c['id']] = $c; }
$classes = array_values($classes);

$gate_violation_ids = $pdo->query("SELECT id FROM violation_type WHERE scope = 'GATE'")->fetchAll(PDO::FETCH_COLUMN);

$all_res = [];

foreach ($classes as $c) {
    $sum_aca_score = 0;
    $sum_con_score = 0;
    $sum_bonus = 0;
    $sum_total = 0;
    $sum_gate_penalty = 0;
    $total_period_count = 0;
    $total_raw_score = 0;
    $actual_n_weeks = 0;
    // Tính ĐỘC LẬP từng tuần rồi mới cộng dồn
    for ($w = $start_week; $w <= $end_week; $w++) {
        // Tính số ngày học thực tế của tuần $w
        $start_date_obj = new DateTime($start_date_str);
        $week_start = clone $start_date_obj;
        $week_start->modify("+" . (($w - 1) * 7) . " days");
        
        $school_days = 0;
        for ($i = 0; $i < 7; $i++) {
            $day = clone $week_start;
            $day->modify("+$i days");
            if ($day->format('N') == 7) continue; // Bỏ qua Chủ nhật
            
            if (!in_array($day->format('Y-m-d'), $excluded_list)) {
                $school_days++;
            }
        }
        
        if ($school_days === 0) {
            // Tuần đóng băng hoàn toàn -> Bỏ qua không tính điểm thi đua
            continue;
        }
        
        $actual_n_weeks++;

        // A. Điểm học tập
        $stmt_aca = $pdo->prepare("SELECT period_count, total_score, bonus_score FROM academic_score WHERE class_id = ? AND week_number = ? AND school_year = ?");
        $stmt_aca->execute([$c['id'], $w, $current_school_year]);
        $acad = $stmt_aca->fetch();

        $raw_period_count = $acad ? (int)$acad['period_count'] : 0;
        $raw_total_score = $acad ? (float)$acad['total_score'] : 0;
        $bonus = $acad ? (float)$acad['bonus_score'] : 0;
        
        // NẾU CHƯA NHẬP SỔ ĐẦU BÀI -> ĂN 0 ĐIỂM HỌC TẬP TUẦN ĐÓ
        $w_score_aca = ($raw_period_count > 0) ? ($raw_total_score / $raw_period_count) : 0;

        // B. Tính điểm trừ nền nếp
        $stmt_vio = $pdo->prepare("SELECT SUM(recorded_points) as total, violation_type_id FROM violation_record WHERE class_id = ? AND week_number = ? AND is_deleted = 0 AND school_year = ? GROUP BY violation_type_id");
        $stmt_vio->execute([$c['id'], $w, $current_school_year]);
        $vios = $stmt_vio->fetchAll();

        $w_total_penalty = 0; $w_gate_penalty = 0;
        foreach ($vios as $v) {
            $w_total_penalty += (float)$v['total'];
            if (in_array($v['violation_type_id'], $gate_violation_ids)) {
                $w_gate_penalty += (float)$v['total'];
            }
        }

        // TÍNH ĐIỂM NỀN NẾP DỰA TRÊN THUẬT TOÁN ĐỘNG
        $base_per_day = $max_base / 6;
        $divisor_per_day = $divisor / 6;
        $max_base_dynamic = $base_per_day * $school_days;
        $divisor_dynamic = $divisor_per_day * $school_days;
        
        $w_score_con = max(0, ($max_base_dynamic - $w_total_penalty) / $divisor_dynamic);

        // TỔNG KẾT TUẦN = (Điểm học tập * Trọng số) + (Điểm nền nếp * Trọng số) + Điểm thưởng
        $w_total = (($w_score_aca * $weight_aca) + ($w_score_con * $weight_con)) + $bonus;

        // CỘNG DỒN VÀO TỔNG CỦA CÁC TUẦN
        $sum_aca_score += $w_score_aca;
        $sum_con_score += $w_score_con;
        $sum_bonus += $bonus;
        $sum_total += $w_total;
        $sum_gate_penalty += $w_gate_penalty;
        $total_period_count += $raw_period_count;
        $total_raw_score += $raw_total_score;
    }

    // TÍNH TRUNG BÌNH (CHIA CHO SỐ TUẦN THỰC HỌC)
    $divisor_weeks = ($actual_n_weeks > 0) ? $actual_n_weeks : 1;
    $avg_aca = $sum_aca_score / $divisor_weeks;
    $avg_con = $sum_con_score / $divisor_weeks;
    $avg_bonus = $sum_bonus / $divisor_weeks;
    $avg_total = $sum_total / $divisor_weeks;
    $avg_gate = $sum_gate_penalty / $divisor_weeks;

    $all_res[] = [
        'class_name' => $c['name'],
        'group' => $c['competition_group'] ?? __('general_group', 'Khối Chung'),
        'nn' => number_format($avg_con, 2),
        'ht' => number_format($avg_aca, 2),
        'tb' => number_format($avg_total, 2),
        '_raw_total' => $avg_total,
        'gate_points' => round($avg_gate, 2),
        'period_count' => $total_period_count,
        'total_score' => $total_raw_score,
        'bonus' => $avg_bonus,
        'rank' => 0 
    ];
}

// Phân nhóm và Xếp hạng tự động theo nhóm thi đua
$grouped_data = [];
$groups = array_unique(array_column($all_res, 'group'));
sort($groups);

foreach ($groups as $g) {
    $group_items = array_filter($all_res, function($item) use ($g) { return $item['group'] === $g; });
    usort($group_items, function($a, $b) { return $b['_raw_total'] <=> $a['_raw_total']; });

    $rank = 1; $actual_rank = 1; $prev_score = null;
    foreach ($group_items as &$item) {
        if ($prev_score !== null && $item['_raw_total'] < $prev_score) $actual_rank = $rank;
        $item['rank'] = $actual_rank;
        $prev_score = $item['_raw_total'];
        $rank++;
    }
    $grouped_data[$g] = $group_items;
}

if (isset($_GET['json']) && $_GET['json'] == 1) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success', 'grouped_data' => $grouped_data]);
    exit;
}

require_once 'views/ranking_view.php';