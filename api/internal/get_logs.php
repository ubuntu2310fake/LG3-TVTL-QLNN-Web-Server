<?php
// File: api/internal/get_logs.php - LOCAL PHP IMPLEMENTATION
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php';

$headers = getallheaders();
$auth = $headers['Authorization'] ?? '';

if ($auth !== 'Bearer ' . SSO_SECRET_KEY) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$inputData = json_decode(file_get_contents('php://input'), true) ?: [];
$codes = $inputData['student_codes'] ?? [];

if (empty($codes)) {
    echo json_encode([]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($codes), '?'));
$sql = "SELECT id, username, full_name, question, advice, risk_level, created_at, school_year 
        FROM psychology_logs 
        WHERE username IN ($placeholders) 
        ORDER BY created_at DESC 
        LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($codes);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($logs as &$l) {
    if ($l['created_at']) {
        $dt = new DateTime($l['created_at']);
        $l['created_at'] = $dt->format('H:i d/m');
    }
}

echo json_encode($logs);
exit;
