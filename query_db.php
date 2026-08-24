<?php
require_once 'includes/db_config.php';
$pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=$db_charset", $db_user, $db_pass);
$stmt = $pdo->query("SELECT * FROM violation_type");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
