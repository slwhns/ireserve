<?php
// Page configuration
$pageTitle = 'View Item Details';
$currentPage = 'inventory';
$additionalCSS = '<link rel="stylesheet" href="/ireserve/assets/css/inventory_form.css?v=2">
<style>
    .item-details-container {
        background: #fafafa;
    }
    .detail-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .detail-card h3 {
        font-size: 1.1rem;
        color: #374151;
        margin: 0 0 15px 0;
        font-weight: 600;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    .info-item {
        padding: 12px;
        background: #f9fafb;
        border-radius: 6px;
        border-left: 3px solid #5570f1;
    }
    .info-label {
        font-size: 0.8rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .info-value {
        font-size: 1rem;
        color: #111827;
        font-weight: 500;
    }
    .qty-card {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 16px;
        text-align: center;
    }
    .qty-card.total { border-color: #5570f1; }
    .qty-card.available { border-color: #10b981; }
    .qty-card.taken { border-color: #f59e0b; }
    .qty-label {
        font-size: 0.85rem;
        color: #6b7280;
        margin-bottom: 8px;
    }
    .qty-value {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .qty-card.total .qty-value { color: #5570f1; }
    .qty-card.available .qty-value { color: #10b981; }
    .qty-card.taken .qty-value { color: #f59e0b; }
    .action-btn {
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    .action-btn.edit {
        background: #5570f1;
        color: white;
    }
    .action-btn.edit:hover {
        background: #3d5ee1;
    }
    .action-btn.delete {
        background: #ef4444;
        color: white;
    }
    .action-btn.delete:hover {
        background: #dc2626;
    }
    @media (max-width: 768px) {
        .item-details-container > div:first-child {
            grid-template-columns: 1fr !important;
        }
        .item-image-section {
            position: static !important;
        }
    }
</style>';

// Include reusable components
require_once 'includes/auth.php';
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// Check if item_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: inventory.php?error=missing_id");
    exit();
}

$item_id = (int)$_GET['id'];
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Get admin's faculty ID
$adminFacultyId = $_SESSION['faculty_id'] ?? null;

// Fetch item details and verify it belongs to admin's faculty
if ($adminFacultyId) {
    $sql = "SELECT i.*, c.name AS category_name, f.faculty_name, f.faculty_code,
            COALESCE((
                SELECT SUM(b.qty)
                FROM bookings b
                WHERE b.item_id = i.item_id AND b.status = 'collect'
            ), 0) AS qty_taken
            FROM inventory_items i 
            JOIN categories c ON i.category_id = c.category_id
            JOIN faculties f ON i.faculty_id = f.faculty_id
            WHERE i.item_id = ? AND i.faculty_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $item_id, $adminFacultyId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $item = $result->fetch_assoc();
        } else {
            header("Location: inventory.php?error=item_not_found");
            exit();
        }
        $stmt->close();
    } else {
        header("Location: inventory.php?error=database_error");
        exit();
    }
} else {
    header("Location: inventory.php?error=unauthorized");
    exit();
}

// Calculate available quantity
$total_qty = (int)$item['quantity'];
$qty_taken = (int)($item['qty_taken'] ?? 0);
$available = max(0, $total_qty - $qty_taken);
?>

<!-- ============ Main Content ============ -->
<div class="main">
    <!-- ============ Top Bar ============ -->
    <?php include 'includes/topbar.php'; ?>

    <!-- ====================== Form Content ====================== -->
    <div class="form-container">
        <div class="form-card" style="max-width: 900px;">
            <div class="form-header">
                <h2>Item Details</h2>
                <a href="/ireserve/admin/inventory.php?page=<?php echo $current_page; ?>" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Back to Inventory
                </a>
            </div>

            <div class="item-details-container" style="padding: 20px;">
                <!-- Item Header -->
                <div class="detail-card" style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: start; gap: 24px;">
                        <img src="<?php echo htmlspecialchars($item['image_url'] ?: '/ireserve/assets/images/no-image.png'); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             style="width: 180px; height: 180px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; flex-shrink: 0;">
                        <div style="flex: 1; min-width: 0;">
                            <h1 style="font-size: 1.5rem; margin: 0 0 8px 0; color: #111827; font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></h1>
                            <p style="color: #6b7280; font-size: 0.9rem; margin: 0 0 16px 0;">
                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($item['category_name']); ?>
                            </p>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                                <span style="background: #eff6ff; color: #2563eb; padding: 6px 12px; border-radius: 4px; font-size: 0.85rem; font-weight: 500;">
                                    <i class="bi bi-upc-scan"></i> <?php echo htmlspecialchars($item['sku']); ?>
                                </span>
                                <span class="status <?php echo htmlspecialchars($item['status']); ?>" style="padding: 6px 12px; border-radius: 4px; font-size: 0.85rem; display: inline-block;">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="detail-card">
                    <h3><i class="bi bi-file-text"></i> Description</h3>
                    <div style="padding: 15px; background: #f9fafb; border-radius: 6px; line-height: 1.6; color: #374151;">
                        <?php echo nl2br(htmlspecialchars($item['description'] ?: 'No description available.')); ?>
                    </div>
                </div>

                <!-- Quantity Overview -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                    <div class="qty-card total">
                        <div class="qty-label">Total Quantity</div>
                        <div class="qty-value"><?php echo $total_qty; ?></div>
                        <div style="font-size: 0.8rem; color: #9ca3af;">Items in stock</div>
                    </div>
                    <div class="qty-card available">
                        <div class="qty-label">Available</div>
                        <div class="qty-value"><?php echo $available; ?></div>
                        <div style="font-size: 0.8rem; color: #9ca3af;">Ready to book</div>
                    </div>
                    <div class="qty-card taken">
                        <div class="qty-label">In Use</div>
                        <div class="qty-value"><?php echo $qty_taken; ?></div>
                        <div style="font-size: 0.8rem; color: #9ca3af;">Currently booked</div>
                    </div>
                </div>

                <!-- Metadata -->
                <div class="detail-card">
                    <h3><i class="bi bi-clock-history"></i> History</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Created</div>
                            <div class="info-value"><?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Last Updated</div>
                            <div class="info-value"><?php echo date('M j, Y g:i A', strtotime($item['updated_at'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="detail-card">
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <a href="/ireserve/admin/inventory_edit.php?id=<?php echo $item_id; ?>&page=<?php echo $current_page; ?>" class="action-btn edit">
                            <i class="bi bi-pencil"></i> Edit Item
                        </a>
                        <a href="/ireserve/admin/inventory_delete.php?id=<?php echo $item_id; ?>" class="action-btn delete"
                           onclick="return confirm('Are you sure you want to delete this item?\n\nItem: <?php echo htmlspecialchars($item['name']); ?>\nSKU: <?php echo htmlspecialchars($item['sku']); ?>\n\nThis action cannot be undone.');">
                            <i class="bi bi-trash"></i> Delete Item
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
