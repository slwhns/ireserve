<?php
session_start();
require_once __DIR__ . '/../config.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inventory | iReserve</title>
        <link rel="stylesheet" href="/ireserve/assets/css/inventory.css?v=2">
        <link rel="stylesheet" href="/ireserve/assets/css/admin.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body>

        <div class="container">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>

            <!-- ====== Main ====== -->
            <div class="main">

                <!-- Topbar -->
                <div class="topbar">
                    <div class="toggle">
                        <i class="bi bi-list"></i>
                    </div>
                    <div class="search">
                        <label>
                            <input type="text" placeholder="Search here">
                            <i class="bi bi-search"></i>
                        </label>
                    </div>
                    <div class="user">
                        <?php
                        // Get current user's profile image
                        $userImg = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23666"%3E%3Cpath d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/%3E%3C/svg%3E';
                        if (isset($_SESSION['user_id']) && isset($conn) && $conn) {
                            $stmt = $conn->prepare('SELECT picture_url FROM users WHERE id = ? LIMIT 1');
                            if ($stmt) {
                                $stmt->bind_param('i', $_SESSION['user_id']);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                if ($res && $row = $res->fetch_assoc()) {
                                    if (!empty($row['picture_url'])) { $userImg = $row['picture_url']; }
                                }
                                $stmt->close();
                            }
                        }
                        ?>
                        <img src="<?= htmlspecialchars($userImg) ?>" alt="profile" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23666%22%3E%3Cpath d=%22M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z%22/%3E%3C/svg%3E';">
                    </div>
                </div>
                
                <!-- ====== Content ====== -->
                <div class="details">
                    <?php
                    // Display success messages
                    if (isset($_GET['success'])) {
                        $success = $_GET['success'];
                        $successMessages = [
                            'added' => 'Item added successfully!',
                            'updated' => 'Item updated successfully!',
                            'deleted' => 'Item deleted successfully!',
                            'category_added' => 'Category added successfully!',
                            'category_deleted' => 'Category deleted successfully!'
                        ];
                        $message = isset($successMessages[$success])
                            ? $successMessages[$success]
                            : htmlspecialchars(urldecode($success));
                        echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' . $message . '</div>';
                    }

                    // Display error messages
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        $errorMessages = [
                            'missing_id' => 'Item ID is missing.',
                            'item_not_found' => 'Item not found.',
                            'database_error' => 'Database error occurred.',
                            'delete_failed' => 'Failed to delete item.',
                            'category_exists' => 'Category already exists.',
                            'category_error' => 'Failed to add category.',
                            'category_in_use' => 'Cannot delete category while it still has items.',
                            'category_delete_failed' => 'Failed to delete category.'
                        ];
                        $message = $errorMessages[$error] ?? 'An error occurred. Please try again.';
                        echo '<div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> ' . $message . '</div>';
                    }
                    ?>
                    
                    <div class="inventory-content-wrapper">
                        <div class="inventoryTable">
                        <div class="cardHeader">
                            <h3>Item List</h3>
                            <a href="/ireserve/admin/inventory_add.php" class="btn">Add Item</a>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <td>Image</td>
                                    <td>Item Name</td>
                                    <td>Category</td>
                                    <td>Quantity</td>
                                    <td>Status</td>
                                    <td>Action</td>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                $sql = "SELECT i.*, c.name AS category_name FROM inventory_items i 
                                        JOIN categories c ON i.category_id = c.category_id";
                                $result = $conn->query($sql);

                                while ($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                <td><img src="<?php echo $row['image_url'] ?: 'assets/images/no-image.png'; ?>" alt="item" class="item-img"></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['category_name']; ?></td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td><span class="status <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td>
                                    <a href="/ireserve/admin/inventory_edit.php?id=<?php echo $row['item_id']; ?>" class="edit">Edit</a>
                                    <a href="/ireserve/admin/inventory_delete.php?id=<?php echo $row['item_id']; ?>" 
                                       class="delete" 
                                       onclick="return confirm('Are you sure you want to delete this item? This action cannot be undone.');">Delete</a>
                                </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Right Sidebar -->
                    <div class="inventory-sidebar">
                        <!-- Total Items Card -->
                        <div class="sidebar-card">
                            <div class="card-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="card-info">
                                <h4>Total Items</h4>
                                <?php
                                $totalItemsQuery = "SELECT COUNT(*) as total FROM inventory_items";
                                $totalResult = $conn->query($totalItemsQuery);
                                $totalItems = $totalResult->fetch_assoc()['total'];
                                ?>
                                <p class="count"><?php echo $totalItems; ?></p>
                            </div>
                        </div>

                        <!-- Categories Card -->
                        <div class="sidebar-card categories-card">
                            <div class="card-header-section">
                                <h4><i class="bi bi-folder"></i> Categories</h4>
                                <button class="btn-add-category" onclick="showAddCategoryModal()">
                                    <i class="bi bi-plus-circle"></i> Add
                                </button>
                            </div>
                            <div class="categories-list">
                                <?php
                                $categoriesQuery = "SELECT c.*, COUNT(i.item_id) as item_count 
                                                   FROM categories c 
                                                   LEFT JOIN inventory_items i ON c.category_id = i.category_id 
                                                   GROUP BY c.category_id 
                                                   ORDER BY c.name";
                                $categoriesResult = $conn->query($categoriesQuery);
                                
                                while ($category = $categoriesResult->fetch_assoc()) {
                                ?>
                                <div class="category-item">
                                    <div class="category-info">
                                        <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                        <span class="category-count"><?php echo (int)$category['item_count']; ?> items</span>
                                    </div>
                                    <div class="category-actions">
                                        <a href="/ireserve/admin/category_delete.php?id=<?php echo (int)$category['category_id']; ?>"
                                           class="btn-delete-category"
                                           onclick="return confirm('Delete this category? This cannot be undone.');">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                                <?php } ?>
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
                    <h3><i class="bi bi-folder-plus"></i> Add New Category</h3>
                    <span class="close" onclick="closeAddCategoryModal()">&times;</span>
                </div>
                <form action="/ireserve/admin/category_add.php" method="POST" class="category-form">
                    <div class="form-group">
                        <label for="category_name">Category Name <span class="required">*</span></label>
                        <input type="text" id="category_name" name="category_name" placeholder="Enter category name" required>
                    </div>
                    <div class="form-group">
                        <label for="category_description">Description</label>
                        <textarea id="category_description" name="category_description" rows="3" placeholder="Enter category description (optional)"></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn-submit"><i class="bi bi-check-circle"></i> Add Category</button>
                        <button type="button" class="btn-cancel" onclick="closeAddCategoryModal()"><i class="bi bi-x-circle"></i> Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Feedback Modal (Success/Error) -->
        <div id="feedbackModal" class="modal">
            <div class="modal-content modal-alert">
                <div class="modal-header">
                    <h3 id="feedbackTitle"><i class="bi bi-check-circle"></i> Success</h3>
                    <span class="close" onclick="closeFeedbackModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <p id="feedbackMessage">Operation completed successfully.</p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-submit" onclick="closeFeedbackModal()">OK</button>
                </div>
            </div>
        </div>

        <script src="/ireserve/assets/js/admin.js"></script>
        <script>
            function showAddCategoryModal() {
                document.getElementById('addCategoryModal').style.display = 'flex';
            }

            function closeAddCategoryModal() {
                document.getElementById('addCategoryModal').style.display = 'none';
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('addCategoryModal');
                if (event.target == modal) {
                    closeAddCategoryModal();
                }
            }

            // Feedback modal helpers
            function openFeedbackModal(type, message) {
                const modal = document.getElementById('feedbackModal');
                const content = modal.querySelector('.modal-content');
                const title = document.getElementById('feedbackTitle');
                const text = document.getElementById('feedbackMessage');

                content.classList.remove('success', 'error');
                if (type === 'success') {
                    content.classList.add('success');
                    title.innerHTML = '<i class="bi bi-check-circle"></i> Success';
                } else {
                    content.classList.add('error');
                    title.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Error';
                }
                text.textContent = message;

                modal.style.display = 'flex';

                // Auto-hide after 5s
                setTimeout(closeFeedbackModal, 5000);
            }

            function closeFeedbackModal() {
                const modal = document.getElementById('feedbackModal');
                if (modal) modal.style.display = 'none';
            }

            // Convert inline alerts into modal on load
            document.addEventListener('DOMContentLoaded', function() {
                const alertEl = document.querySelector('.alert');
                if (alertEl) {
                    const isSuccess = alertEl.classList.contains('alert-success');
                    const message = alertEl.textContent.trim();
                    // Hide inline alert and show modal version
                    alertEl.style.display = 'none';
                    openFeedbackModal(isSuccess ? 'success' : 'error', message);
                }
            });
        </script>
    </body>
</html>