<?php
session_name('ireserve_admin');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ireserve/login_register.php");
    exit();
}

require_once '../config.php';

// Check if user_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: userList.php?error=missing_id");
    exit();
}

$user_id = (int)$_GET['id'];

// Prevent admin from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    header("Location: userList.php?error=cannot_delete_self");
    exit();
}

// Check if user exists and get their picture_url
$checkSql = "SELECT id, picture_url FROM users WHERE id = ?";
$checkStmt = $conn->prepare($checkSql);
$userPicture = null;

if ($checkStmt) {
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        $checkStmt->close();
        $conn->close();
        header("Location: userList.php?error=user_not_found");
        exit();
    }
    
    $userData = $checkResult->fetch_assoc();
    $userPicture = $userData['picture_url'];
    $checkStmt->close();
}

// Delete user
$sql = "DELETE FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Delete profile picture file if it exists in users folder
        if ($userPicture && strpos($userPicture, '/assets/images/users/') !== false) {
            $picturePath = __DIR__ . '/..' . parse_url($userPicture, PHP_URL_PATH);
            if (file_exists($picturePath)) {
                unlink($picturePath);
            }
        }
        
        $stmt->close();
        $conn->close();
        header("Location: userList.php?success=user_deleted");
        exit();
    } else {
        $stmt->close();
        $conn->close();
        header("Location: userList.php?error=delete_failed");
        exit();
    }
} else {
    $conn->close();
    header("Location: userList.php?error=database_error");
    exit();
}
?>
