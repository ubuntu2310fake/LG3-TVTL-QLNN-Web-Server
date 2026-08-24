<?php
require_once '../includes/config.php';
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "TABLE: $t\n";
    $stmt2 = $pdo->query("SHOW COLUMNS FROM $t");
    $cols = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo " - " . $c['Field'] . "\n";
    }
}
