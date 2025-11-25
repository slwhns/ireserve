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
        <link rel="stylesheet" href="/ireserve/assets/css/style.css">
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
                $sql = "SELECT i.item_id, i.name, i.location, i.image_url, i.quantity, 
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
                        WHERE COALESCE(LOWER(i.status), 'active') IN ('active', 'available') AND (SELECT COUNT(*) FROM bookings b2 WHERE b2.item_id = i.item_id) >= 1
                        ORDER BY booking_count DESC, i.item_id DESC
                        LIMIT 12";
                
                $result = $conn->query($sql);
                
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $available = max(0, (int)$row['quantity'] - (int)$row['qty_taken']);
                        $imagePath = !empty($row['image_url']) ? $row['image_url'] : '/ireserve/assets/images/no-image.png';
                        $booking_count = (int)$row['booking_count'];
                ?>
                <div class="popular_card">
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                    <div class="popular_content">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <h4><i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($row['location'] ?? 'Location not specified'); ?></h4>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($row['category_name']); ?></p>
                        <p><strong>Bookings:</strong> <?php echo $booking_count; ?> times</p>
                        <p><strong>Available:</strong> <?php echo $available; ?> / <?php echo (int)$row['quantity']; ?></p>
                        <?php if ($available > 0) { ?>
                        <a href="/ireserve/user/bookingForm.php?item_id=<?php echo (int)$row['item_id']; ?>" 
                           class="btn-book-item">
                            <i class="ri-bookmark-line"></i> Book This Item
                        </a>
                        <?php } else { ?>
                        <button class="btn-unavailable" disabled>
                            <i class="ri-close-circle-line"></i> Not Available
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

        <!-- Items Location Map -->
        <section class="section_container map_container">
            <h2 class="section_header">Items Location Map</h2>
            <div id="map" style="width: 100%; height: 500px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.08);"></div>
        </section>

        <?php include __DIR__ . '/includes/footer.php'; ?>

        <!-- Flatpickr JS for Date Picker -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <!-- Main Script - Load first -->
        <script src="/ireserve/assets/js/script.js"></script>
        <!-- User Booking Functionality -->
        <script src="/ireserve/assets/js/user_booking.js"></script>

        <!-- Google Maps Script -->
        <script>
        function initMap() {
            // Default center: UPSI location
            const defaultCenter = { lat: 5.3567, lng: 100.3021 };
            
            const map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: defaultCenter,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true
            });

            // Fetch items with coordinates
            <?php
            $mapSql = "SELECT item_id, name, location, latitude, longitude, image_url, quantity,
                              COALESCE((SELECT SUM(qty) FROM bookings WHERE item_id = i.item_id AND status = 'taken'), 0) AS qty_taken
                       FROM inventory_items i
                       WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
                       AND COALESCE(LOWER(status), 'active') IN ('active', 'available')";
            $mapResult = $conn->query($mapSql);
            
            if ($mapResult && $mapResult->num_rows > 0):
                $markers = [];
                while ($item = $mapResult->fetch_assoc()) {
                    $available = max(0, (int)$item['quantity'] - (int)$item['qty_taken']);
                    $markers[] = [
                        'id' => $item['item_id'],
                        'name' => $item['name'],
                        'location' => $item['location'] ?? 'N/A',
                        'lat' => (float)$item['latitude'],
                        'lng' => (float)$item['longitude'],
                        'available' => $available,
                        'total' => (int)$item['quantity'],
                        'image' => $item['image_url'] ?? '/ireserve/assets/images/no-image.png'
                    ];
                }
                echo 'const items = ' . json_encode($markers) . ';';
            else:
                echo 'const items = [];';
            endif;
            ?>

            // Create markers for each item
            items.forEach(item => {
                const marker = new google.maps.Marker({
                    position: { lat: item.lat, lng: item.lng },
                    map: map,
                    title: item.name,
                    icon: {
                        url: item.available > 0 ? 'http://maps.google.com/mapfiles/ms/icons/green-dot.png' : 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                        scaledSize: new google.maps.Size(40, 40)
                    }
                });

                const infoContent = `
                    <div style="max-width: 250px; padding: 10px;">
                        <img src="${item.image}" alt="${item.name}" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px; margin-bottom: 10px;">
                        <h3 style="margin: 0 0 8px 0; color: #2a2185; font-size: 16px;">${item.name}</h3>
                        <p style="margin: 4px 0; color: #6b7280; font-size: 14px;">
                            <i class="ri-map-pin-line"></i> ${item.location}
                        </p>
                        <p style="margin: 4px 0; color: ${item.available > 0 ? '#10b981' : '#ef4444'}; font-weight: 600; font-size: 14px;">
                            Available: ${item.available} / ${item.total}
                        </p>
                        ${item.available > 0 ? `
                            <a href="/ireserve/user/bookingForm.php?item_id=${item.id}" 
                               style="display: inline-block; margin-top: 8px; padding: 8px 16px; background: #2a2185; color: white; text-decoration: none; border-radius: 6px; font-size: 13px;">
                                Book Now
                            </a>
                        ` : `
                            <span style="display: inline-block; margin-top: 8px; padding: 8px 16px; background: #e5e7eb; color: #6b7280; border-radius: 6px; font-size: 13px;">
                                Not Available
                            </span>
                        `}
                    </div>
                `;

                const infoWindow = new google.maps.InfoWindow({
                    content: infoContent
                });

                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
            });

            // Auto-center map if there are items
            if (items.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                items.forEach(item => {
                    bounds.extend({ lat: item.lat, lng: item.lng });
                });
                map.fitBounds(bounds);
            }
        }
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCEWZ4A9Tzq8j7Ud6r4tHhoHSLMqJbPonY&callback=initMap"></script>
    </body>

</html>