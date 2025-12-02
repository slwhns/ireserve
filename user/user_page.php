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
$user_picture = '/ireserve/assets/images/user.png'; // Default to user.png if no profile picture

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
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Student Page | iReserve</title>
        <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <link rel="stylesheet" href="/ireserve/assets/css/style.css?v=2">
        <link rel="stylesheet" href="/ireserve/assets/css/user_page.css">
        <link rel="stylesheet" href="/ireserve/assets/css/allItems.css">
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

        <header class="section_container header_container" id="home">
            <div class="header_image_container">
                <div class="header_content">
                    <h1>Booking Item Getting Easy</h1>
                    <p>Browse, Book, Approve, Take</p>
                </div>
            <div class="booking_container" id="booking">
            <form action="/ireserve/user/submit_booking.php" method="POST" id="bookingForm">
            <div class="form_group">
                    <div class="input_group">
                        <input type="text" id="itemSearch" name="item_name" autocomplete="off" required />
                        <label>Item Name</label>
                        <input type="hidden" id="itemId" name="item_id" required />
                    </div>
                    <p>What item you want to reserve?</p>
                    <div class="search-dropdown" id="searchDropdown"></div>
            </div>
            <div class="form_group">
                    <div class="input_group">
                        <input type="text" id="startDate" name="start_date" required readonly />
                        <label>Start</label>
                    </div>
                    <p>Select start date</p>
            </div>
            <div class="form_group">
                    <div class="input_group">
                        <input type="text" id="endDate" name="end_date" required readonly />
                        <label>End</label>
                    </div>
                    <p>Select end date</p>
            </div>
            <div class="form_group">
                    <div class="input_group">
                        <input type="number" id="quantity" name="quantity" min="1" required />
                        <label>Quantity</label>
                    </div>
                    <p id="availableQty">Enter quantity</p>
            </div>
                <button class="btn" type="button" id="searchBtn"><i class="ri-search-line"></i></button>
            </form>
            </div>
            </div>
        </header>

        <section class="section_container popular_container" id="searchResults">
            <h2 class="section_header" id="resultsHeader">Popular Items</h2>
            <div class="popular_grid" id="resultsGrid">
                <?php
                // Fetch popular items based on booking count
                $sql = "SELECT i.item_id, i.name, i.location, i.image_url, i.quantity, i.status,
                               COALESCE(c.name, 'Uncategorized') AS category_name,
                               COALESCE((
                                   SELECT SUM(b.qty)
                                   FROM bookings b
                                   WHERE b.item_id = i.item_id AND b.status = 'taken'
                               ), 0) AS qty_taken,
                               COALESCE((
                                   SELECT COUNT(*)
                                   FROM bookings b
                                   WHERE b.item_id = i.item_id
                               ), 0) AS booking_count
                        FROM inventory_items i
                        LEFT JOIN categories c ON i.category_id = c.category_id
                        WHERE (SELECT COUNT(*) FROM bookings b2 WHERE b2.item_id = i.item_id) >= 1
                        ORDER BY booking_count DESC, i.item_id DESC
                        LIMIT 12";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $available = max(0, (int)$row['quantity'] - (int)$row['qty_taken']);
                        $imagePath = !empty($row['image_url']) ? $row['image_url'] : '/ireserve/assets/images/no-image.png';
                        $booking_count = (int)$row['booking_count'];
                        $status = strtolower($row['status'] ?? 'active');
                        $isAvailable = ($status == 'active' || $status == 'available') && $available > 0;
                ?>
                <div class="popular_card">
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                    <?php if ($status == 'maintenance'): ?>
                    <div class="item-status-badge maintenance">
                        <i class="ri-tools-line"></i> Maintenance
                    </div>
                    <?php elseif ($status == 'retired'): ?>
                    <div class="item-status-badge retired">
                        <i class="ri-forbid-line"></i> Retired
                    </div>
                    <?php elseif (!$isAvailable && $available == 0): ?>
                    <div class="item-status-badge booked">
                        <i class="ri-calendar-check-line"></i> Fully Booked
                    </div>
                    <?php endif; ?>
                    <div class="popular_content">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <h4><i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($row['location'] ?? 'Location not specified'); ?></h4>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                        <p><strong>Bookings:</strong> <?php echo $booking_count; ?> times</p>
                        <p style="color: <?php echo ($status == 'active' || $status == 'available') && $available > 0 ? '#28a745' : '#dc3545'; ?>">
                            <strong>Available:</strong> 
                            <?php 
                            if ($status == 'active' || $status == 'available') {
                                echo $available . ' / ' . (int)$row['quantity'];
                            } else {
                                echo '0 / ' . (int)$row['quantity'];
                            }
                            ?>
                        </p>
                        <?php if ($isAvailable) { ?>
                        <a href="/ireserve/user/bookingForm.php?item_id=<?php echo (int)$row['item_id']; ?>" 
                           class="btn-book-item">
                            <i class="ri-bookmark-line"></i> Book This Item
                        </a>
                        <?php } else { ?>
                        <button class="btn-unavailable" disabled>
                            <i class="ri-close-circle-line"></i> 
                            <?php 
                            if ($status == 'maintenance') echo 'Maintenance';
                            elseif ($status == 'retired') echo 'Retired';
                            else echo 'Not Available';
                            ?>
                        </button>
                        <?php } ?>
                    </div>
                </div>
                <?php
                    }
                } else {
                ?>
                <div class="no-items">
                    <p>No items available at the moment. Please check back later.</p>
                </div>
                <?php
                }
                ?>
            </div>
        </section>

        <?php include __DIR__ . '/includes/footer.php'; ?>

        <!-- Flatpickr JS for Date Picker -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <!-- Main Script - Load first -->
        <script src="/ireserve/assets/js/script.js"></script>
        <!-- User Booking Functionality -->
        <script src="/ireserve/assets/js/user_booking.js"></script>
    </body>

</html>