<?php

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$adminId = $_SESSION['user_id'] ?? 0;

// Update Profile
if ($action === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || empty($email)) {
        header("Location: settings.php?error=invalid_data");
        exit();
    }

    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param('si', $email, $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        header("Location: settings.php?error=email_exists");
        exit();
    }
    $stmt->close();
    // Handle profile picture upload
    $picturePath = $_SESSION['picture_url'] ?? '';
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/images/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExt, $allowedExts) && $_FILES['picture']['size'] <= 2097152) { // 2MB
            $fileName = 'profile_' . $adminId . '_' . time() . '.' . $fileExt;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['picture']['tmp_name'], $targetPath)) {
                $picturePath = '/ireserve/assets/images/profiles/' . $fileName;
                
                // Delete old picture if exists
                if (!empty($_SESSION['picture_url']) && file_exists(__DIR__ . '/..' . parse_url($_SESSION['picture_url'], PHP_URL_PATH))) {
                    @unlink(__DIR__ . '/..' . parse_url($_SESSION['picture_url'], PHP_URL_PATH));
                }
            }
        }
    }

    // Update database
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, picture_url = ? WHERE id = ?");
    $stmt->bind_param('sssi', $name, $email, $picturePath, $adminId);
    
    if ($stmt->execute()) {
        // Update session
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['picture_url'] = $picturePath;
        
        header("Location: settings.php?success=profile_updated");
    } else {
        header("Location: settings.php?error=update_failed");
    }
    $stmt->close();
    exit();
}

// Change Password
if ($action === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        header("Location: settings.php?error=invalid_data");
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        header("Location: settings.php?error=password_mismatch");
        exit();
    }

    if (strlen($newPassword) < 6) {
        header("Location: settings.php?error=weak_password");
        exit();
    }

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        header("Location: settings.php?error=wrong_password");
        exit();
    }

    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $hashedPassword, $adminId);
    
    if ($stmt->execute()) {
        header("Location: settings.php?success=password_changed");
    } else {
        header("Location: settings.php?error=update_failed");
    }
    $stmt->close();
    exit();
}

// Add Category
if ($action === 'add_category') {
    $categoryName = trim($_POST['category_name'] ?? '');

    if (empty($categoryName)) {
        header("Location: settings.php?error=invalid_data");
        exit();
    }

    // Check if category already exists
    $stmt = $conn->prepare("SELECT category_id FROM categories WHERE name = ?");
    $stmt->bind_param('s', $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: settings.php?error=category_exists");
        exit();
    }
    $stmt->close();

    // Insert new category
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param('s', $categoryName);
    
    if ($stmt->execute()) {
        header("Location: settings.php?success=category_added");
    } else {
        header("Location: settings.php?error=update_failed");
    }
    $stmt->close();
    exit();
}

// Edit Category
if ($action === 'edit_category') {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $categoryName = trim($_POST['category_name'] ?? '');

    if ($categoryId <= 0 || empty($categoryName)) {
        header("Location: settings.php?error=invalid_data");
        exit();
    }

    // Check if category name already exists (excluding current category)
    $stmt = $conn->prepare("SELECT category_id FROM categories WHERE name = ? AND category_id != ?");
    $stmt->bind_param('si', $categoryName, $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: settings.php?error=category_exists");
        exit();
    }
    $stmt->close();

    // Update category
    $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE category_id = ?");
    $stmt->bind_param('si', $categoryName, $categoryId);
    
    if ($stmt->execute()) {
        header("Location: settings.php?success=category_updated");
    } else {
        header("Location: settings.php?error=update_failed");
    }
    $stmt->close();
    exit();
}

// Delete Category
if ($action === 'delete_category') {
    $categoryId = (int)($_GET['category_id'] ?? 0);

    if ($categoryId <= 0) {
        header("Location: settings.php?error=invalid_data");
        exit();
    }

    // Check if category has items
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM inventory_items WHERE category_id = ?");
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        header("Location: settings.php?error=category_in_use");
        exit();
    }
    $stmt->close();

    // Delete category
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param('i', $categoryId);
    
    if ($stmt->execute()) {
        header("Location: settings.php?success=category_deleted");
    } else {
        header("Location: settings.php?error=update_failed");
    }
    $stmt->close();
    exit();
}

// Invalid action
header("Location: settings.php?error=invalid_data");
exit();
