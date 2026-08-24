<?php
// api/exam_import_api.php
require_once '../includes/config.php';
require_once '../vendor/autoload.php'; 

use PhpOffice\PhpSpreadsheet\IOFactory;
header('Content-Type: application/json');

try {
    $examId = (int)$_POST['exam_id'];
    $spreadsheet = IOFactory::load($_FILES['fileScore']['tmp_name']);
    $data = $spreadsheet->getActiveSheet()->toArray();
    $headers = $data[0];

    $pdo->beginTransaction();
    // Xóa dữ liệu cũ của kỳ thi này
    $pdo->prepare("DELETE FROM lg3_exam_config WHERE exam_id = ?")->execute([$examId]);
    $pdo->prepare("DELETE FROM lg3_exam_scores WHERE exam_id = ?")->execute([$examId]);

    $mapping = []; $colCount = 1;
    $subjectGroups = []; // Để nhóm TN/TL lại tính tổng

    // Bước 1: Ánh xạ header
    foreach ($headers as $idx => $header) {
        if ($idx < 5) continue; // Bỏ qua SBD, Tên, Lớp...
        $header = trim($header);
        $colCode = "col" . $colCount;
        
        // Nhận diện loại điểm
        $type = 'TONG'; $sKey = $header;
        if (strpos($header, '_TN') !== false) { $type = 'TN'; $sKey = str_replace('_TN', '', $header); }
        elseif (strpos($header, '_TL') !== false) { $type = 'TL'; $sKey = str_replace('_TL', '', $header); }
        elseif (strpos($header, '_tong') !== false) { $type = 'TONG'; $sKey = str_replace('_tong', '', $header); }

        $mapping[$idx] = ['col' => $colCode, 'type' => $type, 'key' => $sKey];
        $subjectGroups[$sKey][$type] = $colCode;

        $pdo->prepare("INSERT INTO lg3_exam_config (exam_id, subject_key, col_code, score_type) VALUES (?, ?, ?, ?)")
            ->execute([$examId, $sKey, $colCode, $type]);
        $colCount++;
    }

    // Bước 2: Import dữ liệu & Tự động tính toán
    $stmt = $pdo->prepare("INSERT INTO lg3_exam_scores (exam_id, sbd, student_name, class_name, dob, col1, col2, col3, col4, col5 ... col50) VALUES (...)"); // Viết đủ 50 col

    for ($i = 1; $i < count($data); $i++) {
        $row = $data[$i];
        if (empty($row[1])) continue;

        $scores = array_fill(1, 50, 0);
        // Đổ điểm từ Excel vào mảng scores tạm thời
        foreach ($mapping as $excelIdx => $map) {
            $scores[(int)str_replace('col', '', $map['col'])] = (float)$row[$excelIdx];
        }

        // THỰC HIỆN CÔNG THỨC: TONG = ROUND(TN + TL, 1)
        foreach ($subjectGroups as $sKey => $cols) {
            if (isset($cols['TN']) && isset($cols['TL']) && isset($cols['TONG'])) {
                $idxTN = (int)str_replace('col', '', $cols['TN']);
                $idxTL = (int)str_replace('col', '', $cols['TL']);
                $idxTong = (int)str_replace('col', '', $cols['TONG']);
                
                $scores[$idxTong] = round($scores[$idxTN] + $scores[$idxTL], 1);
            }
        }

        // Thực thi Insert (Xây dựng mảng tham số cho PDO)
        $params = array_merge([$examId, $row[1], $row[2], $row[3], $row[4]], array_values($scores));
        $stmt->execute($params);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'msg' => __('imported_calc_success', 'Đã import và tự động tính toán điểm tổng!')]);
} catch (Exception $e) { $pdo->rollBack(); echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }