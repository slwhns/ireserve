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
if (empty($_POST['item_id']) || empty($_POST['name']) || empty($_POST['category_id']) || 
    empty($_POST['sku']) || !isset($_POST['quantity']) || empty($_POST['status'])) {
    header("Location: inventory_edit.php?id={$_POST['item_id']}&error=missing_fields");
    exit();
}

// Sanitize and prepare data
$item_id = (int)$_POST['item_id'];
$name = trim($_POST['name']);
$category_id = (int)$_POST['category_id'];
$sku = trim($_POST['sku']);
$description = trim($_POST['description']);
$quantity = (int)$_POST['quantity'];
$status = trim($_POST['status']);
$location = trim($_POST['location']);
$latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
$longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
$current_image = $_POST['current_image'];
$image_url = $current_image; // Keep current image by default

// Handle image upload if new file is provided
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    // Get file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'gif');
    
    // Validate file type
    if (!in_array($fileExt, $allowed)) {
        header("Location: inventory_edit.php?id=$item_id&error=invalid_file_type");
        exit();
    }
    
    // Validate file size (5MB max)
    if ($fileSize > 5242880) {
        header("Location: inventory_edit.php?id=$item_id&error=file_too_large");
        exit();
    }
    
    // Generate unique file name
    $fileNameNew = uniqid('item_', true) . '.' . $fileExt;
    $uploadDir = '../assets/images/inventory/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileDestination = $uploadDir . $fileNameNew;
    
    // Move uploaded file
    if (move_uploaded_file($fileTmpName, $fileDestination)) {
        // Delete old image if it exists and is different
        if (!empty($current_image) && file_exists('..' . $current_image)) {
            unlink('..' . $current_image);
        }
        
        $image_url = '/ireserve/assets/images/inventory/' . $fileNameNew;
    } else {
        header("Location: inventory_edit.php?id=$item_id&error=upload_failed");
        exit();
    }
} elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    // Handle other upload errors
    header("Location: inventory_edit.php?id=$item_id&error=upload_failed");
    exit();
}

// Prepare SQL statement to update item
$sql = "UPDATE inventory_items 
        SET name = ?, 
            category_id = ?, 
            sku = ?, 
            description = ?, 
            quantity = ?, 
            status = ?, 
            location = ?,
            latitude = ?,
            longitude = ?,
            image_url = ?,
            updated_at = CURRENT_TIMESTAMP 
        WHERE item_id = ?";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param(
        "sississddsi", 
        $name, 
        $category_id, 
        $sku, 
        $description, 
        $quantity, 
        $status, 
        $location,
        $latitude,
        $longitude,
        $image_url,
        $item_id
    );
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: inventory.php?success=updated");
        exit();
    } else {
        $stmt->close();
        $conn->close();
        header("Location: inventory_edit.php?id=$item_id&error=database_error");
        exit();
    }
} else {
    $conn->close();
    header("Location: inventory_edit.php?id=$item_id&error=prepare_failed");
    exit();
}

?>
