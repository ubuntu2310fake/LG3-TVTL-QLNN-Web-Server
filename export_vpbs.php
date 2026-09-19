<?php
// export_vpbs.php

// --- BẬT HIỂN THỊ LỖI (DEBUG) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/config.php';

// Kiểm tra quyền
if (function_exists('checkRole')) {
    checkRole(['TEACHER', 'ADMIN', 'RED_FLAG']);
}

$current_week = get_current_week($pdo);
$selected_week = isset($_REQUEST['week']) ? (int)$_REQUEST['week'] : $current_week;
$is_skipped = is_week_skipped($selected_week, $pdo);
$msg = "";
$preview_data = [];

// --- 2. HÀM LẤY DỮ LIỆU VÀ SẮP XẾP TỰ NHIÊN ---
function getViolationData($pdo, $week) {
    // Bước 1: Lấy dữ liệu thô (Chỉ lấy vi phạm hiệu lực: r.is_deleted = 0)
    $sql = "SELECT r.*, s.name as student_name, c.name as class_name, vt.short_code, vt.content
            FROM violation_record r
            JOIN violation_type vt ON r.violation_type_id = vt.id
            LEFT JOIN student s ON r.student_id = s.id
            LEFT JOIN classroom c ON r.class_id = c.id
            WHERE r.week_number = ? AND vt.scope = 'GATE' AND (r.is_deleted = 0 OR r.is_deleted IS NULL)
            ORDER BY r.date_created ASC"; // Tạm thời chỉ sort ngày
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$week]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Bước 2: Gom nhóm dữ liệu
    $merged = [];
    foreach ($records as $r) {
        if (!$r['student_id']) continue;
        
        $timeKey = date('Y-m-d H:i', strtotime($r['date_created']));
        $key = $r['student_id'] . '_' . $timeKey;
        $code = $r['short_code'] ?: 'KHAC_GATE';

        if (!isset($merged[$key])) {
            $merged[$key] = [
                'date_created' => $r['date_created'],
                'student_name' => $r['student_name'],
                'class_name' => $r['class_name'] ?: 'Z_Unknown', // Để lớp chưa xác định xuống cuối
                'total' => 0,
                'details' => [],
                'notes' => []
            ];
        }
        
        $merged[$key]['total'] += $r['recorded_points'];
        if (!isset($merged[$key]['details'][$code])) $merged[$key]['details'][$code] = 0;
        $merged[$key]['details'][$code] += $r['recorded_points'];

        if (!empty($r['note'])) $merged[$key]['notes'][] = $r['note'];
        if (!$r['violation_type_id']) $merged[$key]['notes'][] = $r['recorded_violation_name'];
    }
    
    $final_data = array_values($merged);

    // Bước 3: SẮP XẾP TỰ NHIÊN (NATURAL SORT) BẰNG PHP
    // Đây là chìa khóa để 10A2 đứng trước 10A11
    usort($final_data, function($a, $b) {
        // 1. So sánh Tên Lớp (Natural Sort)
        // strnatcmp: Hàm so sánh chuỗi thông minh (hiểu số học)
        $res = strnatcmp($a['class_name'], $b['class_name']);
        
        if ($res !== 0) {
            return $res;
        }

        // 2. Nếu cùng lớp -> So sánh Thời gian
        return strtotime($a['date_created']) - strtotime($b['date_created']);
    });

    return $final_data;
}

// --- 3. XỬ LÝ XUẤT EXCEL ---
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'export') {
    global $translation_dict;
    $translation_dict = json_decode(file_get_contents(__DIR__ . '/languages/vi.json'), true) ?: [];
    $template_path = __DIR__ . '/static/VPBS.xlsx';
    $autoload_path = __DIR__ . '/vendor/autoload.php';

    if (!file_exists($autoload_path)) {
        $msg = __('server_err_no_excel_lib', "Lỗi Server: Chưa cài thư viện Excel (vendor/autoload.php không tồn tại).");
    } elseif (!file_exists($template_path)) {
        $msg = __('err_no_template_file', "Lỗi: Không tìm thấy file mẫu VPBS.xlsx trong thư mục static/");
    } else {
        try {
            require_once $autoload_path;
            
            $data = getViolationData($pdo, $selected_week);
            
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($template_path);
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', "DANH SÁCH HỌC SINH VI PHẠM NỘI QUY NHÀ TRƯỜNG TUẦN " . $selected_week);
            $sheet->setCellValue('P1', "TỔNG HỢP ĐIỂM TRỪ BỔ SUNG CỦA ĐOÀN TRƯỜNG TUẦN " . $selected_week);

            $col_map = [
                'DIMUON' => 'F', 'KSOVIN' => 'G', 'DEPLE' => 'H', 
                'KTHE' => 'I', 'DTM' => 'J', 'KDP' => 'K', 'MBH' => 'L'
            ];

            $row = 4;
            $idx = 1;

            foreach ($data as $item) {
                $dateObj = new DateTime($item['date_created']);
                
                // Giờ VP: 07:05S hoặc 13:30C
                $h_int = (int)$dateObj->format('H');
                $session = $h_int < 12 ? 'S' : 'C';
                $timeStr = $dateObj->format('H:i') . $session;
                
                // Ngày VP: thẳng ngày d/m (VD: 13/9)
                $dateStr = $dateObj->format('j/n');

                $sheet->setCellValue('A' . $row, $idx++);
                $sheet->setCellValue('B' . $row, $timeStr);
                $sheet->setCellValue('C' . $row, $dateStr);
                $sheet->setCellValue('D' . $row, $item['student_name']);
                $sheet->setCellValue('E' . $row, $item['class_name']);

                $other_points = 0;
                foreach ($item['details'] as $code => $pts) {
                    if (isset($col_map[$code])) {
                        $col = $col_map[$code];
                        $curr = $sheet->getCell($col . $row)->getValue();
                        $sheet->setCellValue($col . $row, (float)$curr + $pts);
                    } else {
                        $other_points += $pts;
                    }
                }
                
                if ($other_points > 0) {
                    $curr = $sheet->getCell('M' . $row)->getValue();
                    $sheet->setCellValue('M' . $row, (float)$curr + $other_points);
                }

                $sheet->setCellValue('N' . $row, $item['total']);
                $sheet->setCellValue('O' . $row, implode(", ", array_unique($item['notes'])));

                // Style
                $styleArray = [
                    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                    'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                ];
                $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray($styleArray);
                $sheet->getStyle('A'.$row.':C'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('E'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F'.$row.':N'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('O'.$row)->getAlignment()->setWrapText(true);

                $row++;
            }

            // Clean output buffer
            while (ob_get_level()) ob_end_clean();

            $filename = "VPBS_Tuan_$selected_week.xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            $msg = __('excel_err_prefix', "Lỗi Excel: ") . $e->getMessage();
        }
    }
}

// --- 4. LẤY DỮ LIỆU PREVIEW ---
try {
    $preview_data = getViolationData($pdo, $selected_week);
} catch (Exception $e) {
    $msg = __('db_err_prefix', "Lỗi DB: ") . $e->getMessage();
}

require_once 'views/export_vpbs_view.php';