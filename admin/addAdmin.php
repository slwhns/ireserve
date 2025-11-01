<?php
session_start();

// Restrict to admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header('Location: /ireserve/login_register.php');
    exit();
}

require_once '../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin | iReserve</title>
    <link rel="stylesheet" href="/ireserve/assets/css/admin.css">
    <link rel="stylesheet" href="/ireserve/assets/css/inventory_form.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="container">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <div class="main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>
        <div class="form-container">
            <div class="form-card">
                <div class="form-header">
                    <h2><i class="bi bi-person-plus"></i> Add Admin</h2>
                    <a href="/ireserve/admin/userList.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                </div>

                <?php
                if (isset($_GET['error'])) {
                    $error = $_GET['error'];
                    $errorMessages = [
                        'missing_fields' => 'Please fill in all required fields.',
                        'invalid_email' => 'Invalid email format.',
                        'email_exists' => 'Email already exists.',
                        'weak_password' => 'Password must be at least 6 characters.',
                        'database_error' => 'Database error. Please try again.',
                        'prepare_failed' => 'System error. Please contact administrator.'
                    ];
                    $message = $errorMessages[$error] ?? 'An error occurred. Please try again.';
                    echo '<div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> ' . $message . '</div>';
                }
                ?>

                <form action="/ireserve/admin/addAdmin_process.php" method="POST" enctype="multipart/form-data" class="inventory-form">
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="name"><i class="bi bi-person"></i> Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" placeholder="Enter full name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="email"><i class="bi bi-envelope"></i> Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" placeholder="Enter email address" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone_number"><i class="bi bi-telephone"></i> Phone Number</label>
                            <input type="text" id="phone_number" name="phone_number" placeholder="e.g., 0123456789" maxlength="20">
                        </div>
                        <div class="form-group">
                            <label for="matrics_number"><i class="bi bi-card-list"></i> Matrics Number</label>
                            <input type="text" id="matrics_number" name="matrics_number" maxlength="20">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="profile_picture"><i class="bi bi-image"></i> Profile Picture</label>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/jpg,image/png,image/gif" onchange="previewImage(this, 'imagePreview')">
                            <small style="color: #666; display: block; margin-top: 5px;">Optional. Max 2MB. Formats: JPG, PNG, GIF.</small>
                            <div id="imagePreview" style="margin-top: 10px; display: none;">
                                <img src="" alt="Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 1px solid #ddd;">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="course"><i class="bi bi-journal-bookmark"></i> Course</label>
                            <input type="text" id="course" name="course" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="faculty"><i class="bi bi-building"></i> Faculty</label>
                            <input type="text" id="faculty" name="faculty" maxlength="100">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password"><i class="bi bi-lock"></i> Password <span class="required">*</span></label>
                            <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password"><i class="bi bi-lock-fill"></i> Confirm Password <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="bi bi-check-circle"></i> Create Admin
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
