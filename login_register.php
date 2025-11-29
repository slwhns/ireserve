<?php
require_once 'config.php';

if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $matrics_number = $_POST['matrics_number'];
    $phone_number = $_POST['phone_number'];
    $program = $_POST['program'];
    $faculty = $_POST['faculty'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'user'; // Only allow user registration

    // Check if passwords match
    if ($password !== $confirm_password) {
        // Start default session for error messages
        session_start();
        $_SESSION['register_error'] = "Passwords do not match.";
        $_SESSION['active_form'] = 'register';
        header("Location: index.php");
        exit();
    }

    // Hash the password
    $password = password_hash($password, PASSWORD_DEFAULT);

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmail->bind_param('s', $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();
    
    if ($result->num_rows > 0) {
        // Start default session for error messages
        session_start();
        $_SESSION['register_error'] = "Email already registered.";
        $_SESSION['active_form'] = 'register';
        $checkEmail->close();
    } else {
        $checkEmail->close();
        // Set default profile picture
        $default_picture = '/ireserve/assets/images/user.png';
        // Insert new user using prepared statement
        $stmt = $conn->prepare("INSERT INTO users (name, matrics_number, phone_number, program, faculty, email, password, role, picture_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssssss', $name, $matrics_number, $phone_number, $program, $faculty, $email, $password, $role, $default_picture);
        $stmt->execute();
        $stmt->close();
        // Set session for success popup
        session_start();
        $_SESSION['register_success'] = true;
        $_SESSION['active_form'] = 'login';
    }
    header("Location: index.php");
    exit();
}

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email ='$email'");
    if ($result->num_rows >0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            
            if ($user['role'] === 'admin') {
                // Use separate session for admin
                session_name('ireserve_admin');
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = 'admin';
                header("Location: admin/admin_page.php");
            } else {
                // Use separate session for user
                session_name('ireserve_user');
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = 'user';
                header("Location: user/user_page.php");
            }
            exit();
        } 
    } 

    // Start default session for error messages
    session_start();
    $_SESSION['login_error'] = "Invalid email or password.";
    $_SESSION['active_form'] = 'login';
    header("Location: index.php");
    exit();
}

// If accessed directly without POST, redirect to index
header("Location: index.php");
exit();
?>