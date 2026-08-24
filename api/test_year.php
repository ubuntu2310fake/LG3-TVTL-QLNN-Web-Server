<?php
require_once __DIR__ . '/../includes/config.php';
echo "School Year: " . get_current_school_year($pdo) . "\n";
echo "Current Week: " . get_current_week($pdo) . "\n";
