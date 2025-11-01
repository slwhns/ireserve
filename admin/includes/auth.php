<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: /ireserve/index.php");
    exit();
}

// Optional: Check if user is admin
if (isset($requireAdmin) && $requireAdmin === true) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: /ireserve/user_page.php");
        exit();
    }
}

// Connect to database
require_once __DIR__ . '/../../config.php';
?>
