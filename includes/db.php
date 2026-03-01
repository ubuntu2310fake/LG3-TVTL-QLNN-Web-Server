<?php
// includes/db.php
require_once __DIR__ . '/config.php';

try {
    $dsn = "mysql:host=" . $db_host . ";dbname=" . $db_name . ";charset=" . $db_charset;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (\PDOException $e) {
    die("Lỗi kết nối Database: " . $e->getMessage());
}

// Hàm khởi động session an toàn
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>