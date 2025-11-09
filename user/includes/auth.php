<?php
session_name('ireserve_user');
session_start();

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: /ireserve/index.php");
    exit();
}

// Connect to database
require_once __DIR__ . '/../../config.php';
?>
