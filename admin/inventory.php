<?php
require_once __DIR__ . '/../config.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inventory | iReserve</title>
        <link rel="stylesheet" href="/ireserve/assets/css/inventory.css">
        <link rel="stylesheet" href="/ireserve/assets/css/admin.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body>

        <div class="container">
            <?php include 'sidebar.php'; ?>

            <!-- ====== Main ====== -->
            <div class="main">

                <!-- Topbar (kept consistent with your current layout) -->
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
                        <img src="/ireserve/assets/images/img1.png" alt="profile">
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
                            'deleted' => 'Item deleted successfully!'
                        ];
                        $message = $successMessages[$success] ?? 'Operation completed successfully.';
                        echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' . $message . '</div>';
                    }

                    // Display error messages
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        $errorMessages = [
                            'missing_id' => 'Item ID is missing.',
                            'item_not_found' => 'Item not found.',
                            'database_error' => 'Database error occurred.',
                            'delete_failed' => 'Failed to delete item.'
                        ];
                        $message = $errorMessages[$error] ?? 'An error occurred. Please try again.';
                        echo '<div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> ' . $message . '</div>';
                    }
                    ?>
                    
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
                </div>
            </div>
        </div>

        <script src="/ireserve/assets/js/admin.js"></script>
    </body>
</html>