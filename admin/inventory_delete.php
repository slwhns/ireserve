<?php
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: /ireserve/index.php");
    exit();
}

// Check if item_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: inventory.php?error=missing_id");
    exit();
}

$item_id = (int)$_GET['id'];

// Get item details first (to delete image if exists)
$sql = "SELECT image_url FROM inventory_items WHERE item_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        
        // Delete the item from database
        $deleteSql = "DELETE FROM inventory_items WHERE item_id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        
        if ($deleteStmt) {
            $deleteStmt->bind_param("i", $item_id);
            
            if ($deleteStmt->execute()) {
                // Delete image file if exists
                if (!empty($item['image_url'])) {
                    $imagePath = __DIR__ . '/..' . $item['image_url'];
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
                
                $deleteStmt->close();
                header("Location: inventory.php?success=Item deleted successfully");
                exit();
            } else {
                $deleteStmt->close();
                header("Location: inventory.php?error=delete_failed");
                exit();
            }
        } else {
            header("Location: inventory.php?error=prepare_failed");
            exit();
        }
    } else {
        $stmt->close();
        header("Location: inventory.php?error=item_not_found");
        exit();
    }
} else {
    header("Location: inventory.php?error=database_error");
    exit();
}

$conn->close();
?>
