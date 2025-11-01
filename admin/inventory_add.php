<?php
// Page configuration
$pageTitle = 'Add Item';
$currentPage = 'inventory';
$additionalCSS = '<link rel="stylesheet" href="/ireserve/assets/css/inventory_form.css?v=1">';
$additionalJS = '<script>
    document.getElementById("image").addEventListener("change", function(e) {
        const fileName = e.target.files[0]?.name || "No file chosen";
        document.querySelector(".file-name").textContent = fileName;
    });
</script>';

// Include reusable components
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <?php include 'includes/topbar.php'; ?>

            <!-- Form Content -->
            <div class="form-container">
                <div class="form-card">
                    <div class="form-header">
                        <h2><i class="bi bi-plus-circle"></i> Add New Item</h2>
                        <a href="/ireserve/admin/inventory.php" class="btn-back">
                            <i class="bi bi-arrow-left"></i> Back to Inventory
                        </a>
                    </div>

                    <?php
                    // Display error messages
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        $errorMessages = [
                            'missing_fields' => 'Please fill in all required fields.',
                            'upload_failed' => 'Failed to upload image. Please try again.',
                            'file_too_large' => 'Image file is too large. Maximum size is 5MB.',
                            'invalid_file_type' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.',
                            'database_error' => 'Database error. Please try again.',
                            'prepare_failed' => 'System error. Please contact administrator.'
                        ];
                        $message = $errorMessages[$error] ?? 'An error occurred. Please try again.';
                        echo '<div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> ' . $message . '</div>';
                    }
                    ?>

                    <form action="inventory_add_process.php" method="POST" enctype="multipart/form-data" class="inventory-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">
                                    <i class="bi bi-tag"></i> Item Name <span class="required">*</span>
                                </label>
                                <input type="text" id="name" name="name" placeholder="Enter item name" required>
                            </div>

                            <div class="form-group">
                                <label for="category_id">
                                    <i class="bi bi-folder"></i> Category <span class="required">*</span>
                                </label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">-- Select Category --</option>
                                    <?php
                                    $result = $conn->query("SELECT * FROM categories ORDER BY name");
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='{$row['category_id']}'>{$row['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="sku">
                                    <i class="bi bi-upc-scan"></i> SKU <span class="required">*</span>
                                </label>
                                <input type="text" id="sku" name="sku" placeholder="e.g., EQP-PROJ-001" required>
                                <small class="form-hint">Stock Keeping Unit (unique identifier)</small>
                            </div>

                            <div class="form-group">
                                <label for="quantity">
                                    <i class="bi bi-box"></i> Quantity <span class="required">*</span>
                                </label>
                                <input type="number" id="quantity" name="quantity" min="0" value="1" placeholder="0" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">
                                    <i class="bi bi-check-circle"></i> Status <span class="required">*</span>
                                </label>
                                <select id="status" name="status" required>
                                    <option value="available">Available</option>
                                    <option value="booked">Booked</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="location">
                                    <i class="bi bi-geo-alt"></i> Location
                                </label>
                                <input type="text" id="location" name="location" placeholder="e.g., Store A, Level 2">
                                <small class="form-hint">Physical location of the item</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="description">
                                    <i class="bi bi-text-left"></i> Description
                                </label>
                                <textarea id="description" name="description" rows="4" placeholder="Enter item description (optional)"></textarea>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full-width">
                                <label for="image">
                                    <i class="bi bi-image"></i> Item Image
                                </label>
                                <div class="file-input-wrapper">
                                    <input type="file" id="image" name="image" accept="image/*" class="file-input">
                                    <label for="image" class="file-label">
                                        <i class="bi bi-upload"></i> Choose Image
                                        <span class="file-name">No file chosen</span>
                                    </label>
                                </div>
                                <small class="form-hint">Supported formats: JPG, PNG, GIF (Max 5MB)</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="bi bi-check-circle"></i> Add Item
                            </button>
                            <a href="/ireserve/admin/inventory.php" class="btn-cancel">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>