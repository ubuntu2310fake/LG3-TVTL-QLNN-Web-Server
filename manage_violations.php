<?php
// manage_violations.php (CONTROLLER)
require_once 'includes/config.php';
checkRole(['ADMIN']);

// 1. XỬ LÝ API (POST REQUESTS)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';

    try {
        // --- A. CẬP NHẬT THỜI GIAN (Ngày bắt đầu & Ngày nghỉ) ---
        if ($action === 'update_timeline') {
            $start_date = $_POST['start_date'];
            $end_hk1_date = $_POST['end_hk1_date'] ?? '';
            $end_year_date = $_POST['end_year_date'] ?? '';
            $excluded_dates = $_POST['excluded_dates'];

            $current_school_year = get_current_school_year($pdo);

            $saveConfigYear = function($key, $val) use ($pdo, $current_school_year) {
                $yearKey = $key . "_" . $current_school_year;
                $check = $pdo->prepare("SELECT value FROM config WHERE `key` = ?");
                $check->execute([$yearKey]);
                if ($check->fetch()) {
                    $pdo->prepare("UPDATE config SET value = ? WHERE `key` = ?")->execute([$val, $yearKey]);
                } else {
                    $pdo->prepare("INSERT INTO config (`key`, value) VALUES (?, ?)")->execute([$yearKey, $val]);
                }
                
                $checkGlobal = $pdo->prepare("SELECT value FROM config WHERE `key` = ?");
                $checkGlobal->execute([$key]);
                if ($checkGlobal->fetch()) {
                    $pdo->prepare("UPDATE config SET value = ? WHERE `key` = ?")->execute([$val, $key]);
                } else {
                    $pdo->prepare("INSERT INTO config (`key`, value) VALUES (?, ?)")->execute([$key, $val]);
                }
            };

            $saveConfigYear('start_date', $start_date);
            $saveConfigYear('end_hk1_date', $end_hk1_date);
            $saveConfigYear('end_year_date', $end_year_date);
            
            // Xử lý chuỗi ngày nghỉ
            $ex_array = [];
            if (!empty($excluded_dates)) {
                $ex_array = array_map('trim', explode(',', $excluded_dates));
            }
            $saveConfigYear('excluded_dates', json_encode($ex_array));

            // Clear cache
            if (isset($redis_connected) && $redis_connected) {
                $redis->del('lg3_system_settings_' . $current_school_year);
            }

            echo json_encode(['status' => 'success', 'msg' => __('updated_system_time_for_year', 'Đã cập nhật thời gian hệ thống cho năm học ') . $current_school_year . '!']);
            exit;
        }

        // --- B. LƯU CẤU HÌNH LUẬT THI ĐUA & TICKER ---
        if ($action === 'save_rules') {
            $ranking_rules = json_encode([
                'max_base' => floatval($_POST['max_base']),
                'divisor' => floatval($_POST['divisor']),
                'weight_aca' => floatval($_POST['weight_aca']),
                'weight_con' => floatval($_POST['weight_con'])
            ]);

            // Lưu Config
            $stmt = $pdo->prepare("INSERT INTO config (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
            $stmt->execute(['ranking_rules', $ranking_rules]);
            $stmt->execute(['ticker_school', trim($_POST['ticker_school'] ?? '')]);

            echo json_encode(['status' => 'success', 'msg' => __('config_saved_success', 'Đã lưu cấu hình thành công!')]);
            exit;
        }

        // --- D. THAY ĐỔI NĂM HỌC HIỆN TẠI ---
        if ($action === 'change_school_year') {
            $school_year = trim($_POST['school_year']);
            if (empty($school_year)) {
                echo json_encode(['status' => 'error', 'msg' => __('school_year_cannot_be_empty', 'Năm học không được để trống!')]);
                exit;
            }

            // Lưu vào config
            $stmt = $pdo->prepare("INSERT INTO config (`key`, value) VALUES ('current_school_year', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
            $stmt->execute([$school_year]);

            // Clear cache
            if (isset($redis_connected) && $redis_connected) {
                $redis->del('lg3_system_settings_' . $school_year);
            }

            echo json_encode(['status' => 'success', 'msg' => __('switched_school_year', 'Đã chuyển sang năm học ') . $school_year . '!']);
            exit;
        }

        // --- C. CÁC ACTION KHÁC (ADD/EDIT/DELETE VIOLATION) ---
        if ($action === 'add' || $action === 'edit') {
            $content = trim($_POST['content']);
            $content_en = trim($_POST['content_en'] ?? '');
            $points = floatval($_POST['points']);
            $scope = $_POST['scope'];
            $short_code = trim($_POST['short_code']);
            $max_penalty_points = (isset($_POST['max_penalty_points']) && $_POST['max_penalty_points'] !== '') ? floatval($_POST['max_penalty_points']) : null;

            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO violation_type (content, content_en, points, scope, short_code, max_penalty_points) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$content, $content_en, $points, $scope, $short_code, $max_penalty_points]);
                echo json_encode(['status' => 'success', 'msg' => __('add_violation_success', 'Thêm lỗi mới thành công!')]);
            } else {
                $id = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE violation_type SET content=?, content_en=?, points=?, scope=?, short_code=?, max_penalty_points=? WHERE id=?");
                $stmt->execute([$content, $content_en, $points, $scope, $short_code, $max_penalty_points, $id]);
                echo json_encode(['status' => 'success', 'msg' => __('update_success', 'Cập nhật thành công!')]);
            }
            exit;
        } 
        
        elseif ($action === 'delete') {
            $id = $_POST['id'];
            $count = $pdo->query("SELECT COUNT(*) FROM violation_record WHERE violation_type_id = $id")->fetchColumn();
            if ($count > 0) {
                echo json_encode(['status' => 'error', 'msg' => __('cannot_delete_due_to_data', 'Không thể xóa vì đã có dữ liệu vi phạm liên quan.')]);
            } else {
                $pdo->prepare("DELETE FROM violation_type WHERE id = ?")->execute([$id]);
                echo json_encode(['status' => 'success', 'msg' => __('deleted_violation_error', 'Đã xóa lỗi vi phạm!')]);
            }
            exit;
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => __('server_error', 'Lỗi Server: ') . $e->getMessage()]);
    }
    exit;
}

// 2. LẤY DỮ LIỆU HIỂN THỊ (GET REQUEST)
$current_school_year = get_current_school_year($pdo);

$current_start_date = get_config_for_year($pdo, 'start_date', $current_school_year, date('Y-09-05'));
$end_hk1_date = get_config_for_year($pdo, 'end_hk1_date', $current_school_year, '');
$end_year_date = get_config_for_year($pdo, 'end_year_date', $current_school_year, '');
$excluded_dates_json = get_config_for_year($pdo, 'excluded_dates', $current_school_year, '[]');

$excluded_list = json_decode($excluded_dates_json, true) ?: [];
$current_week = get_current_week($pdo);
$excluded_dates_string = implode(', ', $excluded_list);

$stmtList = $pdo->query("SELECT * FROM violation_type ORDER BY scope ASC, points ASC");
$violations = $stmtList->fetchAll();

$stmtCfg = $pdo->query("SELECT `key`, value FROM config WHERE `key` IN ('ranking_rules', 'ticker_school')");
$configs = $stmtCfg->fetchAll(PDO::FETCH_KEY_PAIR);

$rules = isset($configs['ranking_rules']) ? json_decode($configs['ranking_rules'], true) : [
    'max_base' => 60, 'divisor' => 6, 'weight_aca' => 0.5, 'weight_con' => 0.5
];

$ticker_school = $configs['ticker_school'] ?? '';

require_once 'views/manage_violations_view.php';
?>