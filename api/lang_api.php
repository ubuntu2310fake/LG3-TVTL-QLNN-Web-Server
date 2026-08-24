<?php
// api/lang_api.php — Trả về bộ dịch (window.LANG) theo ngôn ngữ phiên hiện tại
// Public, không yêu cầu đăng nhập
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
echo json_encode($translation_dict, JSON_UNESCAPED_UNICODE);
