<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: /ireserve/index.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: inventory_add.php");
    exit();
}

// Get form data and sanitize
$name        = trim($_POST['name']);
$category_id = (int)$_POST['category_id'];
$sku         = trim($_POST['sku']);
$description = trim($_POST['description'] ?? '');
$quantity    = (int)$_POST['quantity'];
$status      = $_POST['status'];
$location    = trim($_POST['location'] ?? '');
$created_by  = $_SESSION['user_id'] ?? null; // Assuming user_id is stored in session
$image_url   = null;

// Validate required fields
if (empty($name) || empty($category_id) || empty($sku) || empty($quantity)) {
    header("Location: inventory_add.php?error=missing_fields");
    exit();
}

// Validate status
$valid_statuses = ['available', 'booked', 'maintenance', 'unavailable'];
if (!in_array($status, $valid_statuses)) {
    $status = 'available';
}

// Handle image upload
if (!empty($_FILES['image']['name'])) {
    $targetDir = __DIR__ . "/../assets/images/inventory/";
    
    // Create directory if it doesn't exist
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Get file extension
    $fileExtension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Validate file extension
    if (in_array($fileExtension, $allowedExtensions)) {
        // Generate unique filename
        $fileName = 'item_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $targetFile = $targetDir . $fileName;
        
        // Check file size (5MB max)
        if ($_FILES["image"]["size"] <= 5242880) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $image_url = "/ireserve/assets/images/inventory/" . $fileName;
            } else {
                header("Location: inventory_add.php?error=upload_failed");
                exit();
            }
        } else {
            header("Location: inventory_add.php?error=file_too_large");
            exit();
        }
    } else {
        header("Location: inventory_add.php?error=invalid_file_type");
        exit();
    }
}

// Use prepared statement to prevent SQL injection
$sql = "INSERT INTO inventory_items (name, category_id, sku, description, quantity, status, location, image_url, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param(
        "sississsi", 
        $name, 
        $category_id, 
        $sku, 
        $description, 
        $quantity, 
        $status, 
        $location, 
        $image_url, 
        $created_by
    );
    
    if ($stmt->execute()) {
        $stmt->close();
        header("Location: inventory.php?success=Item added successfully");
        exit();
    } else {
        $stmt->close();
        header("Location: inventory_add.php?error=database_error");
        exit();
    }
} else {
    header("Location: inventory_add.php?error=prepare_failed");
    exit();
}

$conn->close();
?>