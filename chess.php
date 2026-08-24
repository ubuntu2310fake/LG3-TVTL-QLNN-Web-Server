<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    header("Location: index.php");
    exit;
}

$currentUserId = $currentUser['id'] ?? 0;

$page_title = __('chess_game', 'Cờ vua');
require_once 'views/chess_view.php';
