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

// Fetch user details with faculty name
$sql = "SELECT u.id, u.name, u.email, u.phone_number, u.matrics_number, u.program, u.faculty_id, u.role, u.picture_url, f.faculty_name 
        FROM users u 
        LEFT JOIN faculties f ON u.faculty_id = f.faculty_id 
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        header("Location: userList.php?error=user_not_found");
        exit();
    }
    $stmt->close();
} else {
    header("Location: userList.php?error=database_error");
    exit();
}

// Fetch all faculties for dropdown
$faculties = [];
$faculties_result = $conn->query("SELECT faculty_id, faculty_name FROM faculties ORDER BY faculty_name");
if ($faculties_result) {
    while ($row = $faculties_result->fetch_assoc()) {
        $faculties[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User | iReserve</title>
    <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=10">
    <link rel="stylesheet" href="/ireserve/assets/css/inventory_form.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <!-- ============ Navigation Bar ============ -->
    <div class="container">
        <!-- ============ Sidebar ============ -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- ============ Main Content ============ -->
        <div class="main">
            <!-- ============ Top Bar ============ -->
            <?php include __DIR__ . '/includes/topbar.php'; ?>

            <!-- ====================== Form Content ====================== -->
            <div class="form-container">
                <div class="form-card">
                    <div class="form-header">
                        <h2><i class="bi bi-pencil-square"></i> Edit User</h2>
                        <a href="/ireserve/admin/userList.php" class="btn-back">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>

                    <?php
                    // Display error messages
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        $errorMessages = [
                            'missing_fields' => 'Please fill in all required fields.',
                            'invalid_email' => 'Invalid email format.',
                            'email_exists' => 'Email already exists.',
                            'database_error' => 'Database error. Please try again.',
                            'update_failed' => 'Failed to update user.'
                        ];
                        $message = $errorMessages[$error] ?? 'An error occurred. Please try again.';
                        echo '<div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> ' . $message . '</div>';
                    }
                    ?>

                    <form action="update-user.php" method="POST" enctype="multipart/form-data" class="inventory-form">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <input type="hidden" name="current_picture" value="<?php echo htmlspecialchars($user['picture_url'] ?? ''); ?>">
                        
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="name">
                                    <i class="bi bi-person"></i> Name <span class="required">*</span>
                                </label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" placeholder="Enter full name" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="email">
                                    <i class="bi bi-envelope"></i> Email <span class="required">*</span>
                                </label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Enter email address" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone_number">
                                    <i class="bi bi-telephone"></i> Phone Number
                                </label>
                                <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" placeholder="e.g., 0123456789" maxlength="20">
                            </div>
                            <div class="form-group">
                                <label for="matrics_number">
                                    <i class="bi bi-card-list"></i> Matrics Number
                                </label>
                                <input type="text" id="matrics_number" name="matrics_number" value="<?php echo htmlspecialchars($user['matrics_number'] ?? ''); ?>" maxlength="20">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="program">
                                    <i class="bi bi-journal-bookmark"></i> Program
                                </label>
                                <input type="text" id="program" name="program" value="<?php echo htmlspecialchars($user['program'] ?? ''); ?>" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="faculty_id">
                                    <i class="bi bi-building"></i> Faculty
                                </label>
                                <select id="faculty_id" name="faculty_id">
                                    <option value="">-- Select Faculty --</option>
                                    <?php foreach ($faculties as $fac): ?>
                                        <option value="<?php echo $fac['faculty_id']; ?>" <?php echo ($user['faculty_id'] == $fac['faculty_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($fac['faculty_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="profile_picture">
                                    <i class="bi bi-image"></i> Profile Picture
                                </label>
                                <?php if (!empty($user['picture_url'])): ?>
                                    <div style="margin-bottom: 10px;">
                                        <img src="<?php echo htmlspecialchars($user['picture_url']); ?>" alt="Current" style="max-width: 120px; max-height: 120px; border-radius: 8px; border: 1px solid #ddd;">
                                        <p style="font-size: 12px; color: #666; margin-top: 5px;">Current profile picture</p>
                                    </div>
                                <?php endif; ?>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/jpg,image/png,image/gif" onchange="previewImage(this, 'imagePreview')">
                                <small style="color: #666; display: block; margin-top: 5px;">Optional. Max 2MB. Leave empty to keep current. Formats: JPG, PNG, GIF.</small>
                                <div id="imagePreview" style="margin-top: 10px; display: none;">
                                    <p style="font-size: 12px; color: #666; margin-bottom: 5px;">New preview:</p>
                                    <img src="" alt="Preview" style="max-width: 120px; max-height: 120px; border-radius: 8px; border: 1px solid #ddd;">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="role">
                                    <i class="bi bi-shield-check"></i> Role <span class="required">*</span>
                                </label>
                                <select id="role" name="role" required>
                                    <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">
                                    <i class="bi bi-lock"></i> New Password
                                </label>
                                <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="bi bi-lock-fill"></i> Confirm Password
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="bi bi-check-circle"></i> Update User
                            </button>
                            <a href="/ireserve/admin/userList.php" class="btn-cancel">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="/ireserve/assets/js/admin.js"></script>
    <script>
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const img = preview.querySelector('img');
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // Validate file size (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be less than 2MB');
                input.value = '';
                preview.style.display = 'none';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only JPG, PNG, and GIF images are allowed');
                input.value = '';
                preview.style.display = 'none';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                img.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    }
    </script>
</body>
</html>
