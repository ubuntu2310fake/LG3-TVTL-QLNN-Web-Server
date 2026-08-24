<?php
require_once 'includes/config.php';
$sql = file_get_contents('insert_questions.sql');
$pdo->exec($sql);
echo "Imported successfully!";
?>
