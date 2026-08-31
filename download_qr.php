<?php
// download_qr.php
require_once 'includes/config.php';
checkRole(['ADMIN']);

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$class_id = isset($_GET['class_id']) ? $_GET['class_id'] : '';

// 1. Fetch students
if (!empty($class_id)) {
    $stmt = $pdo->prepare("SELECT s.*, c.name as class_name FROM student s JOIN classroom c ON s.class_id = c.id WHERE s.class_id = ? AND c.grade < 13 AND c.name NOT LIKE 'K46%' ORDER BY COALESCE(s.thuylinh, CAST(RIGHT(s.code, 3) AS UNSIGNED)) ASC, s.code ASC");
    $stmt->execute([$class_id]);
} else {
    $stmt = $pdo->prepare("SELECT s.*, c.name as class_name FROM student s JOIN classroom c ON s.class_id = c.id WHERE c.grade < 13 AND c.name NOT LIKE 'K46%' ORDER BY c.grade ASC, LENGTH(c.name) ASC, c.name ASC, COALESCE(s.thuylinh, CAST(RIGHT(s.code, 3) AS UNSIGNED)) ASC, s.code ASC");
    $stmt->execute();
}
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($students)) {
    die("<h3>" . __('no_student_found', 'Không tìm thấy học sinh nào.') . "</h3>");
}

// 2. Prepare Domain URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain_url = $protocol . $_SERVER['HTTP_HOST'];

// 3. Check Graphics Engine (PNG via GD vs fallback SVG)
$use_gd = extension_loaded('gd');
$ext = $use_gd ? 'png' : 'svg';

// 4. Create ZIP
$zip_file = tempnam(sys_get_temp_dir(), 'qr_zip');
$zip = new ZipArchive();

if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    foreach ($students as $student) {
        $qr_data = $domain_url . '/' . $student['code'];
        
        $options = new QROptions([
            'outputType' => $use_gd ? QRCode::OUTPUT_IMAGE_PNG : QRCode::OUTPUT_MARKUP_SVG,
            'outputBase64' => false,
            'quietzoneSize' => 3, // Khoảng 15px chừa mép mỗi cạnh
        ]);
        
        $qrcode = new QRCode($options);
        $image_data = $qrcode->render($qr_data);
        
        // Clean filenames and path (Định dạng: STT_MÃ HS_TÊN)
        $stt = !empty($student['thuylinh']) ? $student['thuylinh'] : (preg_match('/(\d{2,3})$/', $student['code'], $m) ? (int)$m[1] : '');
        $sttPrefix = $stt !== '' ? str_pad($stt, 2, '0', STR_PAD_LEFT) . '_' : '';
        $rawFileName = $sttPrefix . $student['code'] . '_' . $student['name'];
        
        $class_folder = preg_replace('/[\\/\\\:\*\?"<>\|]/', '_', $student['class_name']);
        $student_file = preg_replace('/[\\/\\\:\*\?"<>\|]/', '_', $rawFileName) . '.' . $ext;
        $zip_path = $class_folder . '/' . $student_file;
        
        $zip->addFromString($zip_path, $image_data);
    }
    $zip->close();
} else {
    die("<h3>" . __('zip_create_error', 'Lỗi tạo file ZIP.') . "</h3>");
}

// 5. Send ZIP to user
$filename = "DanhSach_QR_LG3";
if (!empty($class_id) && !empty($students[0]['class_name'])) {
    $filename .= "_" . preg_replace('/[\\/\\\:\*\?"<>\|]/', '_', $students[0]['class_name']);
}
$filename .= ".zip";

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($zip_file));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($zip_file);
unlink($zip_file);
exit;
?>