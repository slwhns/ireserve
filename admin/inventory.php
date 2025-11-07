<?php
session_start();
require_once __DIR__ . '/../config.php';

$currentPage = 'inventory';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | iReserve</title>
    <!-- Core admin styles first to ensure sidebar/layout rules load -->
    <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=10">
    <link rel="stylesheet" href="/ireserve/assets/css/inventory.css?v=5">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body>

        <div class="container">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>

            <!-- ====== Main ====== -->
            <div class="main">

                <!-- Topbar -->
                <?php include __DIR__ . '/includes/topbar.php'; ?>
                
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
                        <div class="cardHeader" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                            <div class="cardHeader-left">
                                <h3>Item List</h3>
                            </div>
                            <div class="cardHeader-center" style="flex:1;display:flex;justify-content:center;">
                                <div class="inventory-search" style="position:relative;max-width:420px;width:100%;">
                                    <input type="text" id="inventorySearch" placeholder="Search items by name, SKU or location..." autocomplete="off" style="height:36px;padding:6px 10px;border-radius:6px;border:1px solid #ddd;width:100%;max-width:360px;">
                                    <div id="inventorySearchResults" class="inventory-search-results" style="display:none;position:absolute;z-index:50;background:#fff;border:1px solid #eee;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,0.08);width:360px;max-height:320px;overflow:auto;top:44px;left:50%;transform:translateX(-50%);"></div>
                                </div>
                            </div>
                            <div class="cardHeader-right" style="display:flex;align-items:center;gap:8px;">
                                <a href="/ireserve/admin/admin_page.php" class="btn-back-simple">
                                    <i class="bi bi-arrow-left"></i> Back
                                </a>
                                <a href="/ireserve/admin/inventory_add.php" class="btn">Add Item</a>
                            </div>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <td>Image</td>
                                    <td>Item Name</td>
                                    <td>Category</td>
                                    <td>Location</td>
                                    <td>Quantity</td>
                                    <td>Available</td>
                                    <td>Status</td>
                                    <td>Action</td>
                                </tr>
                            </thead>

                            <tbody>
                                <?php
                                // Pagination setup
                                $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                                $perPage = 5; // items per page
                                if ($page < 1) { $page = 1; }
                                $offset = ($page - 1) * $perPage;

                                // Total items for pagination
                                $totalCount = 0;
                                if ($totalRes = $conn->query("SELECT COUNT(*) AS total FROM inventory_items")) {
                                    $totalRow = $totalRes->fetch_assoc();
                                    $totalCount = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
                                }
                                $totalPages = max(1, (int)ceil($totalCount / $perPage));
                                if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

                                // Fetch paginated items with category names and currently taken quantity
                                // Available = total quantity - sum of quantities from active (status='taken') bookings
                                $stmt = $conn->prepare("SELECT i.*, c.name AS category_name,
                                                            COALESCE((
                                                                SELECT SUM(b.qty)
                                                                FROM bookings b
                                                                WHERE b.item_id = i.item_id AND b.status = 'taken'
                                                            ), 0) AS qty_taken
                                                        FROM inventory_items i
                                                        JOIN categories c ON i.category_id = c.category_id
                                                        ORDER BY i.item_id DESC
                                                        LIMIT ? OFFSET ?");
                                if ($stmt) {
                                    $stmt->bind_param('ii', $perPage, $offset);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                } else {
                                    $result = false;
                                }

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td><img src="<?php echo $row['image_url'] ?: 'assets/images/no-image.png'; ?>" alt="item" class="item-img"></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['location'] ?? 'Not specified'); ?></td>
                                    <td><?php echo (int)$row['quantity']; ?></td>
                                    <?php $available = max(0, ((int)$row['quantity']) - ((int)($row['qty_taken'] ?? 0))); ?>
                                    <td><?php echo (int)$available; ?></td>
                                    <td><span class="status <?php echo htmlspecialchars($row['status']); ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td>
                                        <div class="action-col">
                                            <a href="/ireserve/admin/inventory_edit.php?id=<?php echo (int)$row['item_id']; ?>" class="edit">Edit</a>
                                            <a href="/ireserve/admin/inventory_delete.php?id=<?php echo (int)$row['item_id']; ?>"
                                               class="delete"
                                               onclick="return confirm('Are you sure you want to delete this item? This action cannot be undone.');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;color:#666;">No items found.</td>
                                </tr>
                                <?php
                                }
                                if (isset($stmt) && $stmt) { $stmt->close(); }
                                ?>
                            </tbody>
                        </table>
                        <?php if ($totalPages > 1) { ?>
                        <div class="pagination" style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;gap:12px;flex-wrap:wrap;">
                            <div class="pagination-info" style="color:#666;">
                                <?php
                                $from = $totalCount ? ($offset + 1) : 0;
                                $to = min($offset + $perPage, $totalCount);
                                ?>
                                Showing <?php echo $from; ?>–<?php echo $to; ?> of <?php echo $totalCount; ?>
                            </div>
                            <div class="pagination-links" style="display:flex;gap:6px;align-items:center;">
                                <?php if ($page > 1) { ?>
                                    <a class="page-link prev" href="?page=<?php echo $page - 1; ?>" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">&laquo; Prev</a>
                                <?php } ?>
                                <?php
                                $window = 2;
                                $start = max(1, $page - $window);
                                $end = min($totalPages, $page + $window);
                                if ($start > 1) {
                                    echo '<a class="page-link" href="?page=1" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">1</a>';
                                    if ($start > 2) echo '<span class="ellipsis" style="padding:0 4px;color:#999;">…</span>';
                                }
                                for ($p = $start; $p <= $end; $p++) {
                                    if ($p == $page) {
                                        echo '<span class="page-link active" style="padding:6px 10px;border:1px solid #4c74f5;background:#4c74f5;color:#fff;border-radius:6px;">' . $p . '</span>';
                                    } else {
                                        echo '<a class="page-link" href="?page=' . $p . '" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">' . $p . '</a>';
                                    }
                                }
                                if ($end < $totalPages) {
                                    if ($end < $totalPages - 1) echo '<span class="ellipsis" style="padding:0 4px;color:#999;">…</span>';
                                    echo '<a class="page-link" href="?page=' . $totalPages . '" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">' . $totalPages . '</a>';
                                }
                                ?>
                                <?php if ($page < $totalPages) { ?>
                                    <a class="page-link next" href="?page=<?php echo $page + 1; ?>" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">Next &raquo;</a>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
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

        <script src="/ireserve/assets/js/admin.js?v=8"></script>
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

            // Inventory search (calls admin/search_item.php)
            (function(){
                const invSearchInput = document.getElementById('inventorySearch');
                const invResults = document.getElementById('inventorySearchResults');
                let invTimeout = null;
                if (!invSearchInput || !invResults) return;

                function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]; }); }

                invSearchInput.addEventListener('input', function(e){
                    clearTimeout(invTimeout);
                    const q = e.target.value.trim();
                    if (q.length < 2) { invResults.style.display = 'none'; invResults.innerHTML = ''; return; }
                    invTimeout = setTimeout(()=>{
                        fetch('/ireserve/admin/search_item.php?q=' + encodeURIComponent(q))
                        .then(r => {
                            if (!r.ok) {
                                return r.text().then(t => { console.error('Search request failed', r.status, t); throw new Error('network'); });
                            }
                            return r.json();
                        })
                        .then(data => {
                            if (!data || !data.ok) { console.error('Search endpoint returned error', data); invResults.innerHTML = '<div class="inventory-search-empty">Search error</div>'; invResults.style.display='block'; return; }
                            if (!data.results || data.results.length === 0) { invResults.innerHTML = '<div class="inventory-search-empty">No items found</div>'; invResults.style.display='block'; return; }
                            let html = '';
                            data.results.forEach(item => {
                                html += '<a href="'+ item.url +'" class="inventory-search-item">'
                                    + '<img src="'+ escapeHtml(item.image) +'" alt="">'
                                    + '<div class="meta">'
                                        + '<div class="title">'+ escapeHtml(item.name) +'</div>'
                                        + '<div class="muted">'+ escapeHtml(item.sku || '') + ' • ' + escapeHtml(item.location || 'Unknown') + ' • Qty: ' + (item.quantity||0) + '</div>'
                                    + '</div>'
                                + '</a>';
                            });
                            invResults.innerHTML = html;
                            invResults.style.display = 'block';
                        }).catch(err => {
                            invResults.innerHTML = '<div class="inventory-search-empty">Search error</div>';
                            invResults.style.display = 'block';
                        });
                    }, 250);
                });

                // Close results when clicking outside
                document.addEventListener('click', function(e){
                    if (!invSearchInput.contains(e.target) && !invResults.contains(e.target)) {
                        invResults.style.display = 'none';
                    }
                });
            })();
        </script>
    </body>
</html>