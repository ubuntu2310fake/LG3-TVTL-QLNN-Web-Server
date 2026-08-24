<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['user'] = ['id' => 1, 'role' => 'ADMIN'];
require_once __DIR__ . '/../includes/config.php';
$stmt_vio = $pdo->prepare("SELECT SUM(recorded_points) as total FROM violation_record WHERE class_id = 5 AND week_number = 1 AND (is_deleted = 0 OR is_deleted IS NULL) AND school_year = '2026-2027'");
$stmt_vio->execute();
$vios = $stmt_vio->fetch(PDO::FETCH_ASSOC);
echo "Total Penalty: " . ($vios['total'] ?? 'NULL') . "\n";
