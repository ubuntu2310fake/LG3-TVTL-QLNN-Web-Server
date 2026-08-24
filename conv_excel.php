<?php
// BẮT BUỘC PHẢI KHAI BÁO USE Ở TRÊN CÙNG
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate; // Thêm thư viện dịch tọa độ

// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Biến lưu thông báo lỗi để in ra màn hình HTML
$error_msg = null;

// XỬ LÝ KHI NGƯỜI DÙNG BẤM NÚT "TẢI XUỐNG"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['json_data'])) {
    
    // BẮT LỖI QUÊN CHẠY COMPOSER
    if (!file_exists('vendor/autoload.php')) {
        $error_msg = __('sys_err_no_vendor', "LỖI HỆ THỐNG: Không tìm thấy thư mục <b>vendor/autoload.php</b>. <br>Bạn phải mở Terminal/SSH trên VPS và chạy lệnh: <code>composer require phpoffice/phpspreadsheet</code>");
    } else {
        require_once 'vendor/autoload.php'; 

        // Dọn dẹp chuỗi JSON người dùng dán vào
        $jsonStr = trim($_POST['json_data']);
        $jsonStr = rtrim($jsonStr, ','); // Xóa dấu phẩy thừa ở cuối
        if (!str_starts_with($jsonStr, '[')) {
            $jsonStr = '[' . $jsonStr . ']'; // Bọc lại thành mảng chuẩn nếu thiếu
        }

        // Chuyển chuỗi thành Mảng PHP
        $data = json_decode($jsonStr, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data) || empty($data)) {
            $error_msg = __('data_err_invalid_json', "LỖI DỮ LIỆU: Nội dung bạn dán vào không đúng cấu trúc JSON (Mã lỗi: ") . json_last_error_msg() . ").";
        } else {
            try {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();

                // TẠO TIÊU ĐỀ CỘT TỰ ĐỘNG (Dòng 1)
                $headers = array_keys($data[0]);
                $colIndex = 1;
                foreach ($headers as $header) {
                    // Dịch số (1) thành chữ (A), ghép với hàng 1 thành 'A1'
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $cellAddress = $colLetter . '1'; 
                    
                    $sheet->setCellValue($cellAddress, $header);
                    $sheet->getStyle($cellAddress)->getFont()->setBold(true);
                    $colIndex++;
                }

                // ĐỔ DỮ LIỆU TỪNG HỌC SINH (Từ dòng 2 trở đi)
                $rowIndex = 2;
                foreach ($data as $row) {
                    $colIndex = 1;
                    foreach ($headers as $header) {
                        $value = isset($row[$header]) ? $row[$header] : '';
                        
                        // Dịch tọa độ và gán dữ liệu
                        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                        $sheet->setCellValue($colLetter . $rowIndex, $value);
                        
                        $colIndex++;
                    }
                    $rowIndex++;
                }

                // RA LỆNH TẢI FILE EXCEL
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="DiemThi_LG3.xlsx"');
                header('Cache-Control: max-age=0');

                // Bắt lỗi nếu thiếu extension php-zip trên server
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                exit; 

            } catch (Exception $e) {
                $error_msg = __('excel_export_err', "LỖI XUẤT EXCEL: ") . $e->getMessage() . __('excel_export_hint', " <br>(Gợi ý: Kiểm tra xem VPS đã bật extension php-zip chưa).");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('tool_json_to_excel_title', 'Tool Convert JSON sang Excel') ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f4f6; display: flex; justify-content: center; padding: 40px 15px; }
        .container { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 800px; }
        h2 { color: #107c41; margin-top: 0; }
        p { color: #4b5563; font-size: 14px; }
        textarea { width: 100%; height: 280px; padding: 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: Consolas, monospace; font-size: 13px; resize: vertical; box-sizing: border-box; background: #f9fafb; color: #1f2937; }
        textarea:focus { outline: none; border-color: #107c41; }
        .btn { background: #107c41; color: white; border: none; padding: 14px 24px; font-size: 16px; border-radius: 8px; cursor: pointer; margin-top: 20px; font-weight: bold; width: 100%; transition: background 0.2s; }
        .btn:hover { background: #0c5e31; }
        .error-alert { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; border-left: 4px solid #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📊 <?= __('tool_json_to_excel_heading', 'Tool Xuất JSON thành Excel') ?></h2>
        <p><?= __('tool_json_to_excel_desc', 'Dán đoạn dữ liệu của bạn vào ô bên dưới. Code sẽ tự động nhận diện tiêu đề cột và sắp xếp dữ liệu tương ứng.') ?></p>
        
        <?php if ($error_msg): ?>
            <div class="error-alert"><?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <textarea name="json_data" required>{"STT":"1","SBD":"18100014","Họ và tên":"Ngô Thế Anh","Lớp":"10A1","TN":6.25,"TL":2.0,"Toán":8.3,"Văn":7.0,"Viết":4.2,"Nói":2.1,"Anh":6.3,"TN.1":4.75,"TL.1":2.0,"Lý":6.8,"TN.2":6.0,"TL.2":2.5,"Hóa":8.5,"TN.3":6.5,"TL.3":3.0,"Sinh":9.5,"TN.4":6.5,"TL.4":2.5,"Sử":9.0,"TN.5":0.0,"TL.5":0.0,"Địa":0.0,"TN.6":0.0,"TL.6":0.0,"KTPL":0.0,"TN.7":0.0,"TL.7":0.0,"CNCN":0.0,"TN.8":0.0,"TL.8":0.0,"CNNN":0.0,"TN.9":4.25,"TL.9":1.75,"Tin":6.0},
{"STT":"2","SBD":"18100034","Họ và tên":"Ngô Thị Ngọc Ánh","Lớp":"10A1","TN":6.25,"TL":2.0,"Toán":8.3,"Văn":7.3,"Viết":4.4,"Nói":2.3,"Anh":6.7,"TN.1":6.0,"TL.1":2.5,"Lý":8.5,"TN.2":4.5,"TL.2":2.5,"Hóa":7.0,"TN.3":3.75,"TL.3":3.0,"Sinh":6.8,"TN.4":6.0,"TL.4":2.5,"Sử":8.5,"TN.5":0.0,"TL.5":0.0,"Địa":0.0,"TN.6":0.0,"TL.6":0.0,"KTPL":0.0,"TN.7":0.0,"TL.7":0.0,"CNCN":0.0,"TN.8":0.0,"TL.8":0.0,"CNNN":0.0,"TN.9":6.25,"TL.9":3.0,"Tin":9.3}</textarea>
            
            <button type="submit" class="btn">⬇ <?= __('download_excel_btn', 'Tải xuống file Excel (.xlsx)') ?></button>
        </form>
    </div>
</body>
</html>