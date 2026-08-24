<?php
// api/ranking_api.php - OPTIMIZED: N+1 → Batch Queries + Redis Cache
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) { echo json_encode(['status' => 'error', 'msg' => __('not_logged_in', 'Chưa đăng nhập')]); exit; }
session_write_close(); // Giải phóng session lock sớm - API read-only

try {
    $current_school_year = get_current_school_year($pdo);

    $filter_type    = $_GET['filter_type'] ?? 'week';
    $selected_week  = isset($_GET['week']) && $_GET['week'] !== '' ? (int)$_GET['week'] : get_current_week($pdo);
    $selected_month = $_GET['month'] ?? '';

    // ============================================================
    // Redis Cache Check (trả về ngay nếu có cache)
    // ============================================================
    $cache_key = "ranking:{$current_school_year}:{$filter_type}:{$selected_week}:{$selected_month}";
    if ($redis_connected) {
        $cached = $redis->get($cache_key);
        if ($cached !== false) {
            echo $cached;
            exit;
        }
    }

    // ============================================================
    // Load Config (1 query duy nhất)
    // ============================================================
    $stmtCfg = $pdo->query("SELECT `key`, value FROM config WHERE `key` IN ('ranking_rules', 'start_date_{$current_school_year}', 'end_hk1_date_{$current_school_year}', 'end_year_date_{$current_school_year}', 'excluded_dates_{$current_school_year}', 'start_date', 'end_hk1_date', 'end_year_date', 'excluded_dates')");
    $configs = $stmtCfg->fetchAll(PDO::FETCH_KEY_PAIR);

    $rulesStr   = $configs['ranking_rules'] ?? null;
    $rules      = $rulesStr ? json_decode($rulesStr, true) : ['max_base'=>60,'divisor'=>6,'weight_aca'=>0.5,'weight_con'=>0.5];
    $max_base   = floatval($rules['max_base'] ?? 60);
    $divisor    = floatval($rules['divisor'] ?? 6);
    $weight_aca = floatval($rules['weight_aca'] ?? 0.5);
    $weight_con = floatval($rules['weight_con'] ?? 0.5);

    $start_date_str     = $configs["start_date_{$current_school_year}"] ?? ($configs['start_date'] ?? date('Y-09-05'));
    $end_hk1_date       = $configs["end_hk1_date_{$current_school_year}"] ?? ($configs['end_hk1_date'] ?? '');
    $end_year_date      = $configs["end_year_date_{$current_school_year}"] ?? ($configs['end_year_date'] ?? '');
    $excluded_dates_json = $configs["excluded_dates_{$current_school_year}"] ?? ($configs['excluded_dates'] ?? '[]');
    $excluded_list      = json_decode($excluded_dates_json, true) ?: [];
    $excluded_set       = array_flip($excluded_list); // O(1) lookup

    $start_year = (int)date('Y', strtotime($start_date_str));

    $get_week_by_date = function($date_str) use ($start_date_str, $excluded_list) {
        $start  = new DateTime($start_date_str); $start->setTime(0,0,0);
        $target = new DateTime($date_str);       $target->setTime(0,0,0);
        if ($target < $start) return 1;
        $diff = $start->diff($target);
        $exclude_count = 0;
        foreach ($excluded_list as $ex) {
            $ex_d = new DateTime($ex); $ex_d->setTime(0,0,0);
            if ($ex_d >= $start && $ex_d <= $target) $exclude_count++;
        }
        return max(1, floor(($diff->days - $exclude_count) / 7) + 1);
    };

    // Tính start_week / end_week
    $start_week = $selected_week;
    $end_week   = $selected_week;

    if ($filter_type === 'month' && !empty($selected_month)) {
        if (strpos($selected_month, 'm_') === 0) {
            $m = (int)str_replace('m_', '', $selected_month);
            $y = ($m >= 8 && $m <= 12) ? $start_year : $start_year + 1;
            $first_day = "$y-" . str_pad($m, 2, '0', STR_PAD_LEFT) . "-01";
            $last_day  = date('Y-m-t', strtotime($first_day));
            if (strtotime($first_day) < strtotime($start_date_str)) $first_day = $start_date_str;
            $start_week = $get_week_by_date($first_day);
            $end_week   = $get_week_by_date($last_day);
        } elseif ($selected_month == 'hk1') {
            $start_week = $get_week_by_date($start_date_str);
            $end_week   = $get_week_by_date($end_hk1_date);
        } elseif ($selected_month == 'hk2') {
            $start_week = $get_week_by_date(date('Y-m-d', strtotime($end_hk1_date . ' + 1 day')));
            $end_week   = $get_week_by_date($end_year_date);
        } elseif ($selected_month == 'year') {
            $start_week = $get_week_by_date($start_date_str);
            $end_week   = $get_week_by_date($end_year_date);
        }
    }
    if ($start_week > $end_week) { [$start_week, $end_week] = [$end_week, $start_week]; }

    // ============================================================
    // Load Classes (1 query)
    // ============================================================
    $classes   = $pdo->query("SELECT id, name, grade, competition_group FROM classroom WHERE grade < 13 AND name NOT LIKE 'K46%' ORDER BY grade ASC, LENGTH(name) ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $class_ids = array_column($classes, 'id');

    if (empty($class_ids)) {
        echo json_encode(['status' => 'success', 'current_week' => $selected_week, 'grouped_ranking' => [], 'grouped_data' => []]);
        exit;
    }

    $in_placeholders = implode(',', array_fill(0, count($class_ids), '?'));

    // ============================================================
    // [BATCH QUERY 1] Toàn bộ academic_score - 1 query thay N×M queries
    // ============================================================
    $stmtAca = $pdo->prepare(
        "SELECT class_id, week_number, period_count, total_score, bonus_score
         FROM academic_score
         WHERE class_id IN ($in_placeholders)
           AND week_number BETWEEN ? AND ?
           AND (school_year = ? OR school_year IS NULL)"
    );
    $stmtAca->execute(array_merge($class_ids, [$start_week, $end_week, $current_school_year]));
    $aca_map = [];
    while ($row = $stmtAca->fetch(PDO::FETCH_ASSOC)) {
        $aca_map[$row['class_id']][$row['week_number']] = $row;
    }

    // ============================================================
    // [BATCH QUERY 2] Toàn bộ violation_record - 1 query thay N×M queries
    // ============================================================
    $stmtVio = $pdo->prepare(
        "SELECT class_id, week_number, violation_type_id, SUM(recorded_points) as total
         FROM violation_record
         WHERE class_id IN ($in_placeholders)
           AND week_number BETWEEN ? AND ?
           AND (is_deleted = 0 OR is_deleted IS NULL)
           AND (school_year = ? OR school_year IS NULL)
         GROUP BY class_id, week_number, violation_type_id"
    );
    $stmtVio->execute(array_merge($class_ids, [$start_week, $end_week, $current_school_year]));
    $vio_map = [];
    while ($row = $stmtVio->fetch(PDO::FETCH_ASSOC)) {
        $vio_map[$row['class_id']][$row['week_number']][] = $row;
    }

    // ============================================================
    // [BATCH QUERY 3] Gate violation IDs (1 query nhỏ)
    // ============================================================
    $gate_violation_ids = $pdo->query("SELECT id FROM violation_type WHERE scope = 'GATE'")->fetchAll(PDO::FETCH_COLUMN);
    $gate_ids_set = array_flip($gate_violation_ids);

    // ============================================================
    // Tính school_days mỗi tuần 1 lần (dùng lại cho tất cả lớp)
    // ============================================================
    $school_days_per_week = [];
    $start_date_obj = new DateTime($start_date_str);
    for ($w = $start_week; $w <= $end_week; $w++) {
        $week_start = clone $start_date_obj;
        $week_start->modify('+' . (($w - 1) * 7) . ' days');
        $school_days = 0;
        for ($i = 0; $i < 7; $i++) {
            $day = clone $week_start;
            $day->modify("+$i days");
            if ($day->format('N') == 7) continue;
            if (!isset($excluded_set[$day->format('Y-m-d')])) $school_days++;
        }
        $school_days_per_week[$w] = $school_days;
    }

    // ============================================================
    // Tính điểm (Pure PHP, không thêm SQL nào nữa)
    // ============================================================
    $all_res         = [];
    $base_per_day    = $max_base / 6;
    $divisor_per_day = $divisor / 6;

    foreach ($classes as $c) {
        $cid = $c['id'];
        $sum_aca = $sum_con = $sum_bonus = $sum_total = $sum_gate = 0.0;
        $actual_n_weeks = 0;

        for ($w = $start_week; $w <= $end_week; $w++) {
            $school_days = $school_days_per_week[$w] ?? 0;
            if ($school_days === 0) continue;
            $actual_n_weeks++;

            $acad       = $aca_map[$cid][$w] ?? null;
            $raw_period = $acad ? (int)$acad['period_count']  : 0;
            $raw_total  = $acad ? (float)$acad['total_score']  : 0.0;
            $bonus      = $acad ? (float)$acad['bonus_score']  : 0.0;
            $w_aca      = ($raw_period > 0) ? ($raw_total / $raw_period) : 0.0;

            $vios = $vio_map[$cid][$w] ?? [];
            $w_total_penalty = $w_gate_penalty = 0.0;
            foreach ($vios as $v) {
                $pts = (float)$v['total'];
                $w_total_penalty += $pts;
                if (isset($gate_ids_set[$v['violation_type_id']])) $w_gate_penalty += $pts;
            }

            $max_base_dyn  = $base_per_day * $school_days;
            $divisor_dyn   = $divisor_per_day * $school_days;
            $w_con         = max(0.0, ($max_base_dyn - $w_total_penalty) / $divisor_dyn);
            $w_total_score = ($w_aca * $weight_aca) + ($w_con * $weight_con) + $bonus;

            $sum_aca   += $w_aca;
            $sum_con   += $w_con;
            $sum_bonus += $bonus;
            $sum_total += $w_total_score;
            $sum_gate  += $w_gate_penalty;
        }

        $dw        = $actual_n_weeks > 0 ? $actual_n_weeks : 1;
        $avg_total = $sum_total / $dw;

        $all_res[] = [
            'class_name'  => $c['name'],
            'group'       => $c['competition_group'] ?: __('general_group', 'Khối Chung'),
            'nn'          => number_format($sum_con  / $dw, 2),
            'ht'          => number_format($sum_aca  / $dw, 2),
            'tb'          => number_format($avg_total, 2),
            'final_score' => $avg_total,
            '_raw_total'  => $avg_total,
            'gate_points' => round($sum_gate / $dw, 2),
            'bonus'       => $sum_bonus / $dw,
            'rank'        => 0,
        ];
    }

    // Xếp hạng theo nhóm thi đua
    $grouped_data = [];
    $group_names_en = [];
    $groups = array_unique(array_column($all_res, 'group'));
    sort($groups);
    foreach ($groups as $g) {
        $group_items = array_values(array_filter($all_res, fn($x) => $x['group'] === $g));
        usort($group_items, fn($a, $b) => $b['final_score'] <=> $a['final_score']);
        $rank = $actual_rank = 1; $prev_score = null;
        foreach ($group_items as &$item) {
            if ($prev_score !== null && $item['final_score'] < $prev_score) $actual_rank = $rank;
            $item['rank'] = $actual_rank;
            $prev_score   = $item['final_score'];
            $rank++;
        }
        unset($item);
        $grouped_data[$g] = $group_items;
        $group_names_en[$g] = str_replace('Nhóm Thi Đua', 'Competition Group', $g);
    }

    $labels = [
        'nn' => 'NN', 'nn_en' => 'Behavior',
        'ht' => 'HT', 'ht_en' => 'Academic',
        'vpbs' => 'VPBS', 'vpbs_en' => 'Penalty',
        'tong_tiet' => 'Tổng tiết', 'tong_tiet_en' => 'Total Periods',
        'thuong' => 'Thưởng', 'thuong_en' => 'Bonus',
        'nhom_thi_dua' => 'Nhóm Thi Đua', 'nhom_thi_dua_en' => 'Competition Group'
    ];

    $result = json_encode([
        'status'          => 'success',
        'current_week'    => $selected_week,
        'grouped_ranking' => $grouped_data,
        'grouped_data'    => $grouped_data,
        'group_names_en'  => $group_names_en,
        'labels'          => $labels,
    ], JSON_UNESCAPED_UNICODE);

    // Cache Redis
    if ($redis_connected) {
        $current_w = get_current_week($pdo);
        $ttl = ($filter_type === 'week' && $selected_week === $current_w) ? 60 : 300;
        $redis->setex($cache_key, $ttl, $result);
    }

    echo $result;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>