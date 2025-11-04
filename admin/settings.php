<?php

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../config.php';

$currentPage = 'settings';

// Helper function for escaping HTML
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Get current admin info
$adminId = $_SESSION['user_id'] ?? 0;
$adminName = $_SESSION['name'] ?? '';
$adminEmail = $_SESSION['email'] ?? '';
$adminPicture = $_SESSION['picture_url'] ?? '';

// Fetch categories for management
$categories = [];
$catResult = $conn->query("SELECT * FROM categories ORDER BY name ASC");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Settings | iReserve</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=10">
    <link rel="stylesheet" href="/ireserve/assets/css/settings.css?v=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <div class="main">
            <?php include __DIR__ . '/includes/topbar.php'; ?>
            
            <div class="settings-wrapper">
                <div class="pageActions">
                    <a href="/ireserve/admin/admin_page.php" class="btn-back-simple">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>

                <?php
                // Display success messages
                if (isset($_GET['success'])) {
                    $success = $_GET['success'];
                    $successMessages = [
                        'profile_updated' => 'Profile updated successfully!',
                        'password_changed' => 'Password changed successfully!',
                        'category_added' => 'Category added successfully!',
                        'category_updated' => 'Category updated successfully!',
                        'category_deleted' => 'Category deleted successfully!'
                    ];
                    $message = $successMessages[$success] ?? 'Operation completed successfully.';
                    echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' . $message . '</div>';
                }

                // Display error messages
                if (isset($_GET['error'])) {
                    $error = $_GET['error'];
                    $errorMessages = [
                        'invalid_data' => 'Invalid data provided.',
                        'update_failed' => 'Failed to update settings.',
                        'email_exists' => 'Email address is already in use by another user.',
                        'category_exists' => 'Category already exists.',
                        'category_in_use' => 'Cannot delete category while it has items.',
                        'wrong_password' => 'Current password is incorrect.',
                        'password_mismatch' => 'New passwords do not match.',
                        'weak_password' => 'Password must be at least 6 characters.'
                    ];
                    $message = $errorMessages[$error] ?? 'An error occurred. Please try again.';
                    echo '<div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> ' . $message . '</div>';
                }
                ?>

                <div class="settings-grid">
                    <!-- Profile Settings -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h2><i class="bi bi-person-circle"></i> Profile Settings</h2>
                        </div>
                        <div class="card-body">
                            <form action="/ireserve/admin/settings_update.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" value="<?= h($adminName) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?= h($adminEmail) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="picture">Profile Picture</label>
                                    <div class="profile-preview">
                                        <?php if ($adminPicture): ?>
                                            <img src="<?= h($adminPicture) ?>" alt="Profile" id="preview">
                                        <?php else: ?>
                                            <div class="no-image" id="preview">
                                                <i class="bi bi-person"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" id="picture" name="picture" accept="image/*">
                                    <small>Supported formats: JPG, PNG, GIF (Max 2MB)</small>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Password Change -->
                    <div class="settings-card">
                        <div class="card-header">
                            <h2><i class="bi bi-shield-lock"></i> Change Password</h2>
                        </div>
                        <div class="card-body">
                            <form action="/ireserve/admin/settings_update.php" method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>

                                <div class="form-group">
                                    <label for="new_password">New Password</label>
                                    <input type="password" id="new_password" name="new_password" required>
                                    <small>Minimum 6 characters</small>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Category Management -->
                    <div class="settings-card full-width">
                        <div class="card-header">
                            <h2><i class="bi bi-tags"></i> Category Management</h2>
                        </div>
                        <div class="card-body">
                            <div class="category-actions">
                                <button class="btn btn-primary" onclick="openAddCategoryModal()">
                                    <i class="bi bi-plus-lg"></i> Add Category
                                </button>
                            </div>

                            <div class="categories-list">
                                <table>
                                    <thead>
                                        <tr>
                                            <td>No.</td>
                                            <td>Category Name</td>
                                            <td>Items Count</td>
                                            <td>Action</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($categories)): ?>
                                            <?php 
                                            $no = 1;
                                            foreach ($categories as $cat): 
                                                // Get item count for this category
                                                $catId = $cat['category_id'];
                                                $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM inventory_items WHERE category_id = ?");
                                                $countStmt->bind_param('i', $catId);
                                                $countStmt->execute();
                                                $countResult = $countStmt->get_result();
                                                $itemCount = $countResult->fetch_assoc()['count'] ?? 0;
                                                $countStmt->close();
                                            ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= h($cat['name']) ?></td>
                                                <td><?= $itemCount ?></td>
                                                <td class="action">
                                                    <button class="icon-btn" onclick="editCategory(<?= $cat['category_id'] ?>, '<?= addslashes($cat['name']) ?>')" title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <button class="icon-btn danger" onclick="deleteCategory(<?= $cat['category_id'] ?>, '<?= addslashes($cat['name']) ?>', <?= $itemCount ?>)" title="Delete">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="empty">No categories found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="settings-card full-width">
                        <div class="card-header">
                            <h2><i class="bi bi-info-circle"></i> System Information</h2>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Application Name</label>
                                    <span>iReserve System</span>
                                </div>
                                <div class="info-item">
                                    <label>Version</label>
                                    <span>1.0.0</span>
                                </div>
                                <div class="info-item">
                                    <label>PHP Version</label>
                                    <span><?= phpversion() ?></span>
                                </div>
                                <div class="info-item">
                                    <label>Server Software</label>
                                    <span><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-plus-circle"></i> Add Category</h3>
                <span class="close" onclick="closeAddCategoryModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form action="/ireserve/admin/settings_update.php" method="POST">
                    <input type="hidden" name="action" value="add_category">
                    <div class="form-group">
                        <label for="category_name">Category Name</label>
                        <input type="text" id="category_name" name="category_name" required>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddCategoryModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-pencil-square"></i> Edit Category</h3>
                <span class="close" onclick="closeEditCategoryModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form action="/ireserve/admin/settings_update.php" method="POST">
                    <input type="hidden" name="action" value="edit_category">
                    <input type="hidden" id="edit_category_id" name="category_id">
                    <div class="form-group">
                        <label for="edit_category_name">Category Name</label>
                        <input type="text" id="edit_category_name" name="category_name" required>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditCategoryModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/ireserve/assets/js/admin.js?v=8"></script>
    <script>
        // Profile picture preview
        document.getElementById('picture')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview');
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                }
                reader.readAsDataURL(file);
            }
        });

        // Category modals
        function openAddCategoryModal() {
            document.getElementById('addCategoryModal').style.display = 'flex';
        }

        function closeAddCategoryModal() {
            document.getElementById('addCategoryModal').style.display = 'none';
        }

        function editCategory(id, name) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = name;
            document.getElementById('editCategoryModal').style.display = 'flex';
        }

        function closeEditCategoryModal() {
            document.getElementById('editCategoryModal').style.display = 'none';
        }

        function deleteCategory(id, name, itemCount) {
            if (itemCount > 0) {
                alert('Cannot delete "' + name + '" category because it has ' + itemCount + ' item(s). Please reassign or delete the items first.');
                return;
            }
            if (confirm('Are you sure you want to delete the "' + name + '" category?')) {
                window.location.href = '/ireserve/admin/settings_update.php?action=delete_category&category_id=' + id;
            }
        }

        // Close modals on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddCategoryModal();
                closeEditCategoryModal();
            }
        });

        // Close modals on outside click
        window.onclick = function(e) {
            const addModal = document.getElementById('addCategoryModal');
            const editModal = document.getElementById('editCategoryModal');
            if (e.target === addModal) {
                closeAddCategoryModal();
            }
            if (e.target === editModal) {
                closeEditCategoryModal();
            }
        }
    </script>
</body>
</html>
