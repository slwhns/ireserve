<?php
session_name('ireserve_admin');
session_start();

// Restrict to admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ireserve/login_register.php');
    exit();
}

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /ireserve/admin/userList.php');
    exit();
}

// Required fields
if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['confirm_password'])) {
    header('Location: /ireserve/admin/addAdmin.php?error=missing_fields');
    exit();
}

$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone_number = trim($_POST['phone_number'] ?? '');
$matrics_number = trim($_POST['matrics_number'] ?? '');
$course = trim($_POST['course'] ?? '');
$faculty = trim($_POST['faculty'] ?? '');
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];
$role = 'admin';

// Handle profile picture upload
$picture_url = '/ireserve/assets/images/admin.png'; // Default admin profile picture
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_picture'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        header('Location: /ireserve/admin/addAdmin.php?error=invalid_file_type');
        exit();
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        header('Location: /ireserve/admin/addAdmin.php?error=file_too_large');
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
        $picture_url = '/ireserve/assets/images/users/' . $filename;
    } else {
        header('Location: /ireserve/admin/addAdmin.php?error=upload_failed');
        exit();
    }
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /ireserve/admin/addAdmin.php?error=invalid_email');
    exit();
}

// Password strength and match
if (strlen($password) < 6) {
    header('Location: /ireserve/admin/addAdmin.php?error=weak_password');
    exit();
}
if ($password !== $confirm_password) {
    header('Location: /ireserve/admin/addAdmin.php?error=weak_password'); // reuse with general message
    exit();
}

// Check duplicate email
$checkSql = 'SELECT id FROM users WHERE email = ? LIMIT 1';
$checkStmt = $conn->prepare($checkSql);
if (!$checkStmt) {
    header('Location: /ireserve/admin/addAdmin.php?error=prepare_failed');
    exit();
}
$checkStmt->bind_param('s', $email);
$checkStmt->execute();
$checkRes = $checkStmt->get_result();
if ($checkRes && $checkRes->num_rows > 0) {
    $checkStmt->close();
    header('Location: /ireserve/admin/addAdmin.php?error=email_exists');
    exit();
}
$checkStmt->close();

$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql = 'INSERT INTO users (name, email, phone_number, matrics_number, course, faculty, picture_url, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Location: /ireserve/admin/addAdmin.php?error=prepare_failed');
    exit();
}
$stmt->bind_param('sssssssss', $name, $email, $phone_number, $matrics_number, $course, $faculty, $picture_url, $password_hash, $role);
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: /ireserve/admin/userList.php?success=admin_added');
    exit();
}
$stmt->close();
$conn->close();
header('Location: /ireserve/admin/addAdmin.php?error=database_error');
exit();
