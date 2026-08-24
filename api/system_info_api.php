<?php
// api/system_info_api.php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'success',
    'firmware_version' => defined('APP_VERSION') ? APP_VERSION : '1.0.0',
    'engine_version' => defined('ENGINE_VERSION') ? ENGINE_VERSION : '1.0.0'
]);
?>