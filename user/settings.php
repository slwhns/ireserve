<?php
session_name('ireserve_user');
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['email'])) {
    header("Location: /ireserve/index.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];

// Fetch user details with faculty name
$stmt = $conn->prepare("SELECT u.name, u.email, u.matrics_number, u.phone_number, u.program, u.faculty_id, u.picture_url, u.telegram_chat_id, 
                               f.faculty_name, f.faculty_code 
                        FROM users u 
                        LEFT JOIN faculties f ON u.faculty_id = f.faculty_id 
                        WHERE u.id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$user_name = $user['name'];
$user_matrics = $user['matrics_number'] ?? '';
$user_phone = $user['phone_number'] ?? '';
$user_program = $user['program'] ?? '';
$user_faculty_id = $user['faculty_id'] ?? '';
$user_faculty_name = !empty($user['faculty_name']) ? $user['faculty_name'] . ' (' . $user['faculty_code'] . ')' : '';
$user_telegram = $user['telegram_chat_id'] ?? '';
$user_picture = !empty($user['picture_url']) ? $user['picture_url'] : '/ireserve/assets/images/user.png';

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_picture'])) {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $file_type = $_FILES['profile_picture']['type'];
            $file_size = $_FILES['profile_picture']['size'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            } elseif ($file_size > $max_size) {
                $error_message = "File size must be less than 5MB.";
            } else {
                // Create upload directory if it doesn't exist
                $upload_dir = __DIR__ . '/../assets/images/users/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique filename
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                $db_path = '/ireserve/assets/images/users/' . $new_filename;
                
                // Delete old profile picture if it exists and is not the default
                if (!empty($user['picture_url']) && 
                    $user['picture_url'] !== '/ireserve/assets/images/user.png' &&
                    $user['picture_url'] !== '/ireserve/assets/images/logo.png') {
                    $old_file = __DIR__ . '/..' . str_replace('/ireserve/', '/', $user['picture_url']);
                    // Only delete if it's in the users directory and belongs to this user
                    if (file_exists($old_file) && strpos($user['picture_url'], '/assets/images/users/') !== false) {
                        unlink($old_file);
                    }
                }
                
                // Upload new file
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    // Update database
                    $update_pic = $conn->prepare("UPDATE users SET picture_url = ? WHERE id = ?");
                    $update_pic->bind_param('si', $db_path, $user_id);
                    
                    if ($update_pic->execute()) {
                        $user_picture = $db_path;
                        $user['picture_url'] = $db_path; // Update the user array
                        header("Location: /ireserve/user/settings.php?success=picture_updated");
                        exit();
                    } else {
                        $error_message = "Failed to update profile picture in database.";
                    }
                    $update_pic->close();
                } else {
                    $error_message = "Failed to upload file.";
                }
            }
        } else {
            $error_message = "Please select a file to upload.";
        }
    }
    
    if (isset($_POST['remove_picture'])) {
        // Delete current profile picture if it exists and is not the default
        if (!empty($user['picture_url']) && 
            $user['picture_url'] !== '/ireserve/assets/images/user.png' &&
            $user['picture_url'] !== '/ireserve/assets/images/logo.png') {
            $old_file = __DIR__ . '/..' . str_replace('/ireserve/', '/', $user['picture_url']);
            // Only delete if it's in the users directory
            if (file_exists($old_file) && strpos($user['picture_url'], '/assets/images/users/') !== false) {
                unlink($old_file);
            }
        }
        
        // Reset to default
        $default_pic = '/ireserve/assets/images/user.png';
        $update_pic = $conn->prepare("UPDATE users SET picture_url = ? WHERE id = ?");
        $update_pic->bind_param('si', $default_pic, $user_id);
        
        if ($update_pic->execute()) {
            $user_picture = $default_pic;
            $user['picture_url'] = $default_pic; // Update the user array
            header("Location: /ireserve/user/settings.php?success=picture_removed");
            exit();
        } else {
            $error_message = "Failed to remove profile picture.";
        }
        $update_pic->close();
    }
    
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $matrics_number = trim($_POST['matrics_number']);
        $phone_number = trim($_POST['phone_number']);
        $program = trim($_POST['program']);
        $faculty_id = trim($_POST['faculty_id']);
        $email = trim($_POST['email']);
        
        if (!empty($name) && !empty($email)) {
            // Check if email is already taken by another user
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->bind_param('si', $email, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = "Email is already taken by another user.";
            } else {
                // Get Telegram Chat ID from POST
                $telegram_chat_id = trim($_POST['telegram_chat_id'] ?? '');
                
                $update_stmt = $conn->prepare("UPDATE users SET name = ?, matrics_number = ?, phone_number = ?, program = ?, faculty_id = ?, email = ?, telegram_chat_id = ? WHERE id = ?");
                $update_stmt->bind_param('sssssssi', $name, $matrics_number, $phone_number, $program, $faculty_id, $email, $telegram_chat_id, $user_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    header("Location: /ireserve/user/settings.php?success=profile_updated");
                    exit();
                } else {
                    $error_message = "Failed to update profile.";
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        } else {
            $error_message = "Name and email are required.";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            // Verify current password
            $pwd_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $pwd_stmt->bind_param('i', $user_id);
            $pwd_stmt->execute();
            $pwd_result = $pwd_stmt->get_result();
            $pwd_data = $pwd_result->fetch_assoc();
            
            if (password_verify($current_password, $pwd_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pwd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_pwd->bind_param('si', $hashed_password, $user_id);
                
                if ($update_pwd->execute()) {
                    header("Location: /ireserve/user/settings.php?success=password_changed");
                    exit();
                } else {
                    $error_message = "Failed to change password.";
                }
                $update_pwd->close();
            } else {
                $error_message = "Current password is incorrect.";
            }
            $pwd_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | iReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="/ireserve/assets/css/style.css?v=2">
    <link rel="stylesheet" href="/ireserve/assets/css/user_page.css">
    <link rel="stylesheet" href="/ireserve/assets/css/settings.css?v=3">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="settings-container">
        <div class="back-button-container-top">
            <a href="/ireserve/user/user_page.php" class="btn-back">
                <i class="ri-arrow-left-line"></i> Back
            </a>
        </div>

        <div class="settings-header">
            <h1><i class="ri-settings-3-line"></i> Account Settings</h1>
            <p>Manage your account information and preferences</p>
        </div>

        <div class="settings-grid">
            <!-- Profile Picture -->
            <div class="settings-card">
                <div class="card-header">
                    <h2><i class="ri-image-line"></i> Profile Picture</h2>
                </div>
                <div class="profile-picture-section">
                    <div class="current-picture">
                        <img src="<?php echo htmlspecialchars($user_picture); ?>" alt="Profile Picture" id="profilePreview">
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="settings-form">
                        <div class="form-group">
                            <label for="profile_picture">Upload New Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/jpg,image/gif">
                            <small>Max size: 5MB. Formats: JPG, PNG, GIF</small>
                        </div>
                        <div class="button-group">
                            <button type="submit" name="update_picture" class="btn btn-primary">
                                <i class="ri-upload-line"></i> Upload Picture
                            </button>
                            <?php if ($user_picture !== '/ireserve/assets/images/user.png'): ?>
                            <button type="submit" name="remove_picture" class="btn btn-secondary" onclick="return confirm('Are you sure you want to remove your profile picture?')">
                                <i class="ri-delete-bin-line"></i> Remove Picture
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="settings-card">
                <div class="card-header">
                    <h2><i class="ri-user-line"></i> Profile Information</h2>
                </div>
                <form method="POST" class="settings-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="matrics_number">Matrics Number</label>
                        <input type="text" id="matrics_number" name="matrics_number" value="<?php echo htmlspecialchars($user_matrics); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                        <small>Matrics number cannot be changed. Contact admin if needed.</small>
                    </div>
                    <div class="form-group">
                        <label for="program">Program</label>
                        <input type="text" id="program" name="program" value="<?php echo htmlspecialchars($user_program); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                        <small>Program assignment cannot be changed. Contact admin if needed.</small>
                    </div>
                    <div class="form-group">
                        <label for="faculty_name">Faculty</label>
                        <input type="text" id="faculty_name" name="faculty_name" value="<?php echo htmlspecialchars($user_faculty_name); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;">
                        <input type="hidden" name="faculty_id" value="<?php echo htmlspecialchars($user_faculty_id); ?>">
                        <small>Faculty assignment cannot be changed. Contact admin if needed.</small>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_phone); ?>">
                    </div>
                    <div class="form-group">
                        <label for="telegram_chat_id">
                            <i class="ri-telegram-line"></i> Telegram Chat ID 
                            <span class="help-text">(Optional - for booking notifications)</span>
                        </label>
                        <input type="text" id="telegram_chat_id" name="telegram_chat_id" 
                               value="<?php echo htmlspecialchars($user_telegram); ?>" 
                               placeholder="e.g., 123456789">
                        <small class="form-help">
                            <a href="#" onclick="showTelegramHelp(); return false;" style="color: var(--primary-color);">
                                <i class="ri-question-line"></i> How to get my Chat ID?
                            </a>
                        </small>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="ri-save-line"></i> Save Changes
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="settings-card">
                <div class="card-header">
                    <h2><i class="ri-lock-password-line"></i> Change Password</h2>
                </div>
                <form method="POST" class="settings-form">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">
                        <i class="ri-key-line"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <div id="notificationIcon" class="notification-icon"></div>
            <p id="notificationMessage"></p>
            <button id="notificationBtnOk" class="btn-ok">OK</button>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="/ireserve/assets/js/script.js?v=2"></script>
    <script>
        // Profile picture preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profilePreview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Telegram Help - Show as popup notification
        function showTelegramHelp() {
            const helpContent = `
                <div style="text-align: left; max-height: 400px; overflow-y: auto;">
                    <h3 style="margin: 0 0 20px 0; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                        <i class="ri-telegram-fill"></i> How to Setup Telegram Notifications
                    </h3>
                    
                    <div style="margin-bottom: 15px; display: flex; gap: 15px; align-items: flex-start;">
                        <div style="background: var(--primary-color); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">1</div>
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: var(--text-dark);">Get Your Chat ID</h4>
                            <p style="margin: 0; color: var(--text-light); line-height: 1.6;">Open Telegram, search for <code style="background: #f4f4f4; padding: 2px 8px; border-radius: 4px; font-family: monospace; color: var(--primary-color);">@userinfobot</code>, click START, and copy your Chat ID</p>
                        </div>
                    </div>

                    <div style="margin-bottom: 15px; display: flex; gap: 15px; align-items: flex-start;">
                        <div style="background: var(--primary-color); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">2</div>
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: var(--text-dark);">Save Chat ID Here</h4>
                            <p style="margin: 0; color: var(--text-light); line-height: 1.6;">Enter your Chat ID in the field above and click "Save Changes"</p>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-start;">
                        <div style="background: var(--primary-color); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">3</div>
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: var(--text-dark);">⚠️ IMPORTANT: Start the iReserve Bot</h4>
                            <p style="margin: 0; color: var(--text-light); line-height: 1.6;">Search for <code style="background: #f4f4f4; padding: 2px 8px; border-radius: 4px; font-family: monospace; color: var(--primary-color);">@ireserve_upsi_bot</code> in Telegram and click <strong>START</strong>. This is required to receive notifications!</p>
                        </div>
                    </div>

                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; color: #856404; display: flex; align-items: flex-start; gap: 10px; font-size: 0.95rem; margin-bottom: 15px;">
                        <i class="ri-alert-line" style="flex-shrink: 0; margin-top: 2px;"></i>
                        <div>
                            <strong>Critical Step:</strong> You won't receive any notifications until you start a chat with <code style="background: #f4f4f4; padding: 2px 6px; border-radius: 4px;">@ireserve_upsi_bot</code>. This is a Telegram security requirement.
                        </div>
                    </div>

                    <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; color: #1976d2; display: flex; align-items: center; gap: 10px; font-size: 0.95rem;">
                        <i class="ri-information-line"></i>
                        <span>Once configured, you'll receive instant notifications when your bookings are approved, rejected, ready for collection, or returned!</span>
                    </div>
                </div>
            `;
            
            showNotification(helpContent, 'info');
        }
    </script>
</body>
</html>

