<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ireserve/login_register.php");
    exit();
}

require_once '../config.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: userList.php");
    exit();
}

// Validate required fields
if (empty($_POST['user_id']) || empty($_POST['name']) || empty($_POST['email']) || empty($_POST['role'])) {
    header("Location: edit-user.php?id={$_POST['user_id']}&error=missing_fields");
    exit();
}

// Sanitize and prepare data
$user_id = (int)$_POST['user_id'];
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$role = trim($_POST['role']);
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
$matrics_number = isset($_POST['matrics_number']) ? trim($_POST['matrics_number']) : null;
$course = isset($_POST['course']) ? trim($_POST['course']) : null;
$faculty = isset($_POST['faculty']) ? trim($_POST['faculty']) : null;
$current_picture = isset($_POST['current_picture']) ? trim($_POST['current_picture']) : null;
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Handle profile picture upload
$picture_url = $current_picture; // Keep current by default
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_picture'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        header("Location: edit-user.php?id=$user_id&error=invalid_file_type");
        exit();
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        header("Location: edit-user.php?id=$user_id&error=file_too_large");
        exit();
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . uniqid() . '_' . time() . '.' . $extension;
    $uploadDir = __DIR__ . '/../assets/images/users/';
    $uploadPath = $uploadDir . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Delete old picture if it exists and is in the users folder
        if ($current_picture && strpos($current_picture, '/assets/images/users/') !== false) {
            $oldFile = __DIR__ . '/..' . parse_url($current_picture, PHP_URL_PATH);
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        $picture_url = '/ireserve/assets/images/users/' . $filename;
    } else {
        header("Location: edit-user.php?id=$user_id&error=upload_failed");
        exit();
    }
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: edit-user.php?id=$user_id&error=invalid_email");
    exit();
}

// Validate role
if (!in_array($role, ['user', 'admin'])) {
    $role = 'user';
}

// Check if email already exists for another user
$checkSql = "SELECT id FROM users WHERE email = ? AND id != ?";
$checkStmt = $conn->prepare($checkSql);

if ($checkStmt) {
    $checkStmt->bind_param("si", $email, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        header("Location: edit-user.php?id=$user_id&error=email_exists");
        exit();
    }
    $checkStmt->close();
}

// If password fields provided and match, hash new password
$password_sql = '';
$params = [];
$types = '';

if ($new_password !== '') {
    if ($new_password !== $confirm_password) {
        header("Location: edit-user.php?id=$user_id&error=update_failed");
        exit();
    }
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $password_sql = ", password = ?";
}

// Update user with optional fields
$sql = "UPDATE users SET name = ?, email = ?, role = ?, phone_number = ?, matrics_number = ?, course = ?, faculty = ?, picture_url = ? $password_sql WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    if ($password_sql !== '') {
        $stmt->bind_param("sssssssssi", $name, $email, $role, $phone_number, $matrics_number, $course, $faculty, $picture_url, $password_hash, $user_id);
    } else {
        $stmt->bind_param("ssssssssi", $name, $email, $role, $phone_number, $matrics_number, $course, $faculty, $picture_url, $user_id);
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
    header("Location: userList.php?success=user_updated");
        exit();
    } else {
        $stmt->close();
        $conn->close();
        header("Location: edit-user.php?id=$user_id&error=update_failed");
        exit();
    }
} else {
    $conn->close();
    header("Location: edit-user.php?id=$user_id&error=database_error");
    exit();
}
?>
