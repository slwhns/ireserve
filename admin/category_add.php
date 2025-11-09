<?php
session_name('ireserve_admin');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ireserve/login_register.php");
    exit();
}

// Include database config
require_once '../config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: inventory.php");
    exit();
}

// Validate required fields
if (empty($_POST['category_name'])) {
    header("Location: inventory.php?error=missing_fields");
    exit();
}

// Sanitize and prepare data
$category_name = trim($_POST['category_name']);
$category_description = trim($_POST['category_description']);

// Check if category already exists
$checkSql = "SELECT category_id FROM categories WHERE name = ?";
$checkStmt = $conn->prepare($checkSql);

if ($checkStmt) {
    $checkStmt->bind_param("s", $category_name);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        header("Location: inventory.php?error=category_exists");
        exit();
    }
    $checkStmt->close();
}

// Insert new category
$sql = "INSERT INTO categories (name, description, created_at) VALUES (?, ?, NOW())";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ss", $category_name, $category_description);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: inventory.php?success=category_added");
        exit();
    } else {
        $stmt->close();
        $conn->close();
        header("Location: inventory.php?error=category_error");
        exit();
    }
} else {
    $conn->close();
    header("Location: inventory.php?error=database_error");
    exit();
}
?>
