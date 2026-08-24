<?php
// api/import_scores_api.php
require_once '../includes/config.php';
require_once '../vendor/autoload.php'; 

use PhpOffice\PhpSpreadsheet\IOFactory;
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'ADMIN') {
    echo json_encode(['status' => 'error', 'msg' => __('insufficient_permissions', 'Quyền hạn không đủ')]); exit;
}

try {
    $examId = (int)$_POST['exam_id'];
    if (!$examId) throw new Exception(__('exam_not_selected', "Chưa chọn kỳ thi mục tiêu!"));

    $spreadsheet = IOFactory::load($_FILES['fileScore']['tmp_name']);
    $data = $spreadsheet->getActiveSheet()->toArray();

    // 1. DÒ DÒNG HEADER
    $headerRowIndex = 0;
    foreach ($data as $rowIndex => $rowCells) {
        if (in_array('SBD', $rowCells) || in_array('sbd', $rowCells)) {
            $headerRowIndex = $rowIndex; break;
        }
    }

    $headers = $data[$headerRowIndex]; 
    $startScoreCol = 0;
    $dobColIndex = -1; // Vị trí cột Ngày sinh

    foreach($headers as $idx => $val) {
        $cleanHeader = mb_strtolower(trim($val ?? ''));
        // Tìm cột Ngày sinh
        if (in_array($cleanHeader, [__('dob_header_vi', 'ngày sinh'), 'dob', 'ngaysinh'])) {
            $dobColIndex = $idx;
        }
        // Điểm thường bắt đầu sau các cột định danh
        if (in_array($cleanHeader, [__('class_header_vi', 'lớp'), __('dob_header_vi', 'ngày sinh'), 'dob', 'class'])) {
            $startScoreCol = max($startScoreCol, $idx + 1);
        }
    }

    $mapping = []; $colCount = 1; $subjectGroups = []; 
    $pendingTN = null; $pendingTL = null;

    foreach ($headers as $idx => $header) {
        if ($idx < $startScoreCol) continue; 
        $header = trim($header ?? ''); if (empty($header)) continue;
        $colCode = "col" . $colCount; $headerLower = mb_strtolower($header);
        
        if (preg_match('/^TN(\.\d+)?$/i', $header) || $headerLower == __('write_header_vi', 'viết')) {
            $pendingTN = ['idx' => $idx, 'col' => $colCode];
            $mapping[$idx] = ['col' => $colCode, 'type' => 'TN', 'subject' => 'PENDING'];
            $colCount++; continue;
        } else if (preg_match('/^TL(\.\d+)?$/i', $header) || $headerLower == __('speak_header_vi', 'nói')) {
            $pendingTL = ['idx' => $idx, 'col' => $colCode];
            $mapping[$idx] = ['col' => $colCode, 'type' => 'TL', 'subject' => 'PENDING'];
            $colCount++; continue;
        }

        $type = 'TONG'; $sKey = $header;
        if (strpos($header, '_TN') !== false) { $type = 'TN'; $sKey = str_replace('_TN', '', $header); } 
        elseif (strpos($header, '_TL') !== false) { $type = 'TL'; $sKey = str_replace('_TL', '', $header); }

        $mapping[$idx] = ['col' => $colCode, 'type' => $type, 'subject' => $sKey];
        $subjectGroups[$sKey][$type] = $colCode;

        if ($type == 'TONG') {
            if ($pendingTN) { $mapping[$pendingTN['idx']]['subject'] = $sKey; $subjectGroups[$sKey]['TN'] = $pendingTN['col']; $pendingTN = null; }
            if ($pendingTL) { $mapping[$pendingTL['idx']]['subject'] = $sKey; $subjectGroups[$sKey]['TL'] = $pendingTL['col']; $pendingTL = null; }
        }
        $colCount++;
    }

    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM lg3_exam_config WHERE exam_id = ?")->execute([$examId]);
    $pdo->prepare("DELETE FROM lg3_exam_scores WHERE exam_id = ?")->execute([$examId]);

    $stmtConf = $pdo->prepare("INSERT INTO lg3_exam_config (exam_id, subject_key, col_code, score_type) VALUES (?, ?, ?, ?)");
    foreach ($subjectGroups as $sKey => $cols) {
        foreach ($cols as $type => $cCode) { $stmtConf->execute([$examId, $sKey, $cCode, $type]); }
    }

    $colNames = []; $placeholders = [];
    for($j=1; $j<=50; $j++) { $colNames[] = "col$j"; $placeholders[] = "?"; }
    // THÊM CỘT DOB VÀO CÂU LỆNH INSERT
    $sqlInsert = "INSERT INTO lg3_exam_scores (exam_id, sbd, student_name, class_name, dob, " . implode(", ", $colNames) . ") VALUES (?, ?, ?, ?, ?, " . implode(", ", $placeholders) . ")";
    $stmtScore = $pdo->prepare($sqlInsert);

    for ($i = $headerRowIndex + 1; $i < count($data); $i++) {
        $row = $data[$i];
        if (empty($row[1])) continue; 

        $scores = array_fill(1, 50, 0);
        foreach ($mapping as $excelIdx => $map) {
            $cIdx = (int)str_replace('col', '', $map['col']);
            $rawVal = trim((string)($row[$excelIdx] ?? '0'));
            $val = str_replace(',', '.', $rawVal === '' ? '0' : $rawVal);
            $scores[$cIdx] = is_numeric($val) ? (float)$val : 0;
        }

        // Tự cộng điểm
        foreach ($subjectGroups as $sKey => $group) {
            if (isset($group['TN']) && isset($group['TL']) && isset($group['TONG'])) {
                $idxTN = (int)str_replace('col', '', $group['TN']);
                $idxTL = (int)str_replace('col', '', $group['TL']);
                $idxTong = (int)str_replace('col', '', $group['TONG']);
                $scores[$idxTong] = round($scores[$idxTN] + $scores[$idxTL], 1);
            }
        }

        // Chuẩn bị tham số (Thêm DOB vào vị trí tương ứng)
        $dobVal = ($dobColIndex !== -1) ? (string)($row[$dobColIndex] ?? '') : '';
        $params = [
            $examId, 
            (string)($row[1] ?? ''), 
            (string)($row[2] ?? ''), 
            (string)($row[3] ?? ''),
            $dobVal // GIÁ TRỊ CỘT NGÀY SINH
        ];
        for($k=1; $k<=50; $k++) { $params[] = $scores[$k]; }
        
        $stmtScore->execute($params); // ĐÃ SỬA CHUẨN
    }

    $pdo->commit();

    require_once '../includes/push_helper.php';
    $stmtExam = $pdo->prepare("SELECT name FROM lg3_exams WHERE id = ?");
    $stmtExam->execute([$examId]);
    $examName = $stmtExam->fetchColumn() ?: 'Kỳ thi';

    enqueueNotification($pdo, 'EXAM_SCORES', [
        'exam_id'   => (string)$examId,
        'exam_name' => $examName
    ]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}