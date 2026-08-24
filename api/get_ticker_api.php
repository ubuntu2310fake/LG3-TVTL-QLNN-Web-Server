<?php
require_once '../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

$ticker_school = '';
try {
    $ticker_school = $pdo->query("SELECT value FROM config WHERE `key` = 'ticker_school'")->fetchColumn();
} catch (Exception $e) {}

$sys_bc_msg = '';

$msgs = [];
if (!empty($sys_bc_msg)) $msgs[] = "🔥 " . $sys_bc_msg;
if (!empty($ticker_school)) $msgs[] = "📢 " . $ticker_school;

$ticker_text = implode(" | ", $msgs);

echo json_encode([
    'status' => 'success', 
    'ticker' => $ticker_text
]);
?>