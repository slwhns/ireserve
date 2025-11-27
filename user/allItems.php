<?php
session_name('ireserve_user');
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['email'])) {
    header("Location: /ireserve/index.php");
    exit();
}

// Get user information from database
$user_email = $_SESSION['email'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Fetch user details including profile picture
$user_name = 'User';
$user_picture = '/ireserve/assets/images/user.png';

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT name, picture_url FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $user_name = $user_data['name'];
        $user_picture = !empty($user_data['picture_url']) ? $user_data['picture_url'] : $user_picture;
    }
    $stmt->close();
}

// Pagination settings
$items_per_page = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build the WHERE clause - show all items with their status
$where_conditions = ["1=1"];  // Show all items
$params = [];
$types = '';

if ($category_filter > 0) {
    $where_conditions[] = "i.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if (!empty($search_query)) {
    $where_conditions[] = "(i.name LIKE ? OR i.sku LIKE ? OR i.location LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM inventory_items i WHERE {$where_clause}";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_items = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_items = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_items / $items_per_page);

// Fetch items
$sql = "SELECT i.item_id, i.name, i.sku, i.location, i.image_url, i.quantity, i.status,
               COALESCE(c.name, 'Uncategorized') AS category_name
        FROM inventory_items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        WHERE {$where_clause}
        ORDER BY i.item_id DESC
        LIMIT ? OFFSET ?";

$params[] = $items_per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all categories for filter
$categories = $conn->query("SELECT category_id, name FROM categories ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Items - iReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="/ireserve/assets/css/style.css">
    <link rel="stylesheet" href="/ireserve/assets/css/user_page.css">
    <link rel="stylesheet" href="/ireserve/assets/css/allItems.css">
    <link rel="stylesheet" href="/ireserve/assets/css/settings.css">
</head>

<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Popup Notification Modal -->
    <div id="notificationModal" class="notification-modal">
        <div class="notification-content">
            <div class="notification-icon" id="notificationIcon"></div>
            <div class="notification-message" id="notificationMessage"></div>
            <button class="notification-btn-ok" id="notificationBtnOk">OK</button>
        </div>
    </div>

    <div class="all-items-container">
        <div class="back-button-container-top">
            <a href="/ireserve/user/user_page.php" class="btn-back">
                <i class="ri-arrow-left-line"></i> Back
            </a>
        </div>

        <div class="all-items-header">
            <h1><i class="ri-stack-line"></i> All Available Items</h1>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label for="search"><i class="ri-search-line"></i> Search</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           placeholder="Search by name..."
                           value="<?php echo htmlspecialchars($search_query); ?>">
                </div>

                <div class="filter-group">
                    <label for="category"><i class="ri-filter-line"></i> Category</label>
                    <select id="category" name="category">
                        <option value="0">All Categories</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_id']; ?>" 
                                <?php echo ($category_filter == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-filter">
                        <i class="ri-search-line"></i> Filter
                    </button>
                    <a href="/ireserve/user/allItems.php" class="btn-reset">
                        <i class="ri-refresh-line"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Items Grid -->
        <div class="items-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $imagePath = !empty($row['image_url']) ? $row['image_url'] : '/ireserve/assets/images/no-image.png';
                    $status = strtolower($row['status'] ?? 'active');
                    // Allow booking if status is not maintenance, retired, or unavailable
                    $isAvailable = ($status != 'maintenance' && $status != 'retired' && $status != 'unavailable');
            ?>
            <div class="item-card">
                <div class="item-image">
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                    <div class="item-badge"><?php echo htmlspecialchars($row['category_name']); ?></div>
                    <?php if ($status == 'maintenance'): ?>
                    <div class="status-overlay maintenance-overlay">
                        <i class="ri-tools-line"></i> Maintenance
                    </div>
                    <?php elseif ($status == 'retired'): ?>
                    <div class="status-overlay retired-overlay">
                        <i class="ri-forbid-line"></i> Retired
                    </div>
                    <?php elseif ($status == 'unavailable'): ?>
                    <div class="status-overlay unavailable-overlay">
                        <i class="ri-close-circle-line"></i> Unavailable
                    </div>
                    <?php endif; ?>
                </div>
                <div class="item-content">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p class="item-sku"><i class="ri-barcode-line"></i> SKU: <?php echo htmlspecialchars($row['sku']); ?></p>
                    <p class="item-location"><i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($row['location'] ?? 'Location not specified'); ?></p>
                    <div class="item-quantity">
                        <span class="qty-label">Total Quantity:</span>
                        <span class="qty-value <?php echo $isAvailable ? 'in-stock' : 'out-of-stock'; ?>">
                            <?php echo (int)$row['quantity']; ?>
                        </span>
                    </div>
                    <div class="item-actions">
                        <a href="/ireserve/user/viewItem.php?id=<?php echo (int)$row['item_id']; ?>" 
                           class="btn-view-item">
                            <i class="ri-eye-line"></i> View
                        </a>
                        <?php if ($isAvailable) { ?>
                        <a href="/ireserve/user/bookingForm.php?item_id=<?php echo (int)$row['item_id']; ?>" 
                           class="btn-book-item">
                            <i class="ri-bookmark-line"></i> Book
                        </a>
                        <?php } else { ?>
                        <button class="btn-unavailable" disabled>
                            <i class="ri-close-circle-line"></i> 
                            <?php 
                            if ($status == 'maintenance') echo 'Maintenance';
                            elseif ($status == 'retired') echo 'Retired';
                            elseif ($status == 'unavailable') echo 'Unavailable';
                            else echo 'Not Available';
                            ?>
                        </button>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
            ?>
            <div class="no-items-found">
                <i class="ri-inbox-line"></i>
                <h3>No items found</h3>
                <p>Try adjusting your search or filter criteria</p>
                <a href="/ireserve/user/allItems.php" class="btn-back">View All Items</a>
            </div>
            <?php
            }
            $stmt->close();
            ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
               class="page-link">
                <i class="ri-arrow-left-s-line"></i> Previous
            </a>
            <?php endif; ?>

            <div class="page-numbers">
                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                <a href="?page=<?php echo $i; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                   class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>

            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?><?php echo $category_filter > 0 ? '&category=' . $category_filter : ''; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
               class="page-link">
                Next <i class="ri-arrow-right-s-line"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="/ireserve/assets/js/script.js"></script>
</body>

</html>
