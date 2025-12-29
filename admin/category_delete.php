<?php
session_name('ireserve_admin');
session_start();

// Require admin session
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ireserve/login_register.php');
    exit();
}

require_once '../config.php';

// Validate id
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: /ireserve/admin/inventory.php?error=missing_id');
    exit();
}

$category_id = (int)$_GET['id'];

// Get admin's faculty ID
$adminFacultyId = $_SESSION['faculty_id'] ?? null;

// Ensure category exists
$existsSql = 'SELECT name FROM categories WHERE category_id = ?';
if (!$existsStmt = $conn->prepare($existsSql)) {
    header('Location: /ireserve/admin/inventory.php?error=database_error');
    exit();
}
$existsStmt->bind_param('i', $category_id);
$existsStmt->execute();
$existsRes = $existsStmt->get_result();
if ($existsRes->num_rows === 0) {
    $existsStmt->close();
    header('Location: /ireserve/admin/inventory.php?error=item_not_found');
    exit();
}
$existsStmt->close();

// Check if any items in admin's faculty use this category
if ($adminFacultyId) {
    $countSql = "SELECT COUNT(*) AS cnt FROM inventory_items i 
                 WHERE i.category_id = ? AND i.faculty_id = ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param('ii', $category_id, $adminFacultyId);
} else {
    $countSql = 'SELECT COUNT(*) AS cnt FROM inventory_items WHERE category_id = ?';
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param('i', $category_id);
}
if (!$countStmt) {
    header('Location: /ireserve/admin/inventory.php?error=database_error');
    exit();
}
$countStmt->execute();
$countRes = $countStmt->get_result();
$row = $countRes->fetch_assoc();
$countStmt->close();

if ((int)$row['cnt'] > 0) {
    // Block deletion if items exist
    header('Location: /ireserve/admin/inventory.php?error=category_in_use');
    exit();
}

// Safe to delete
$delSql = 'DELETE FROM categories WHERE category_id = ? LIMIT 1';
if (!$delStmt = $conn->prepare($delSql)) {
    header('Location: /ireserve/admin/inventory.php?error=database_error');
    exit();
}
$delStmt->bind_param('i', $category_id);
if ($delStmt->execute()) {
    $delStmt->close();
    $conn->close();
    header('Location: /ireserve/admin/inventory.php?success=category_deleted');
    exit();
} else {
    $delStmt->close();
    $conn->close();
    header('Location: /ireserve/admin/inventory.php?error=category_delete_failed');
    exit();
}
