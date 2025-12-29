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

// Get item ID from URL
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($item_id <= 0) {
    header("Location: /ireserve/user/allItems.php?error=invalid_item");
    exit();
}

// Fetch item details
$stmt = $conn->prepare("SELECT i.*, c.name AS category_name,
                        CONCAT(f.faculty_name, ' (', f.faculty_code, ')') AS location,
                        f.latitude, f.longitude
                        FROM inventory_items i
                        LEFT JOIN categories c ON i.category_id = c.category_id
                        LEFT JOIN faculties f ON i.faculty_id = f.faculty_id
                        WHERE i.item_id = ?");
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: /ireserve/user/allItems.php?error=item_not_found");
    exit();
}

$item = $result->fetch_assoc();
$status = strtolower($item['status'] ?? 'active');
// Allow booking if item status is not maintenance or retired (availability is date-based)
$isAvailable = ($status != 'maintenance' && $status != 'retired' && $status != 'unavailable');
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($item['name']); ?> - iReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="/ireserve/assets/css/style.css">
    <link rel="stylesheet" href="/ireserve/assets/css/user_page.css">
    <link rel="stylesheet" href="/ireserve/assets/css/allItems.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .item-details-container {
            max-width: 1200px;
            margin: 80px auto 60px;
            padding: 0 20px;
        }

        .item-details-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .item-image-section {
            position: relative;
        }

        .item-main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 12px;
        }

        .item-info-section h1 {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 15px;
        }

        .item-meta {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin: 25px 0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .meta-item i {
            font-size: 1.3rem;
            color: var(--primary-color);
        }

        .meta-label {
            font-weight: 600;
            color: var(--text-dark);
            min-width: 100px;
        }

        .meta-value {
            color: var(--text-light);
        }

        .availability-section {
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
        }

        .availability-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-dark);
        }

        .availability-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .stat-box {
            text-align: center;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 5px;
        }

        .item-description {
            margin: 25px 0;
        }

        .item-description h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .item-description p {
            color: var(--text-light);
            line-height: 1.8;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-view,
        .btn-book-now,
        .btn-back {
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-book-now {
            background: var(--primary-color);
            color: #fff;
            flex: 1;
        }

        .btn-book-now:hover {
            background: var(--primary-color-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
        }

        .btn-back {
            background: #f5f5f5;
            color: var(--text-dark);
        }

        .btn-back:hover {
            background: #e0e0e0;
        }

        .btn-unavailable-full {
            background: #ccc;
            color: #666;
            cursor: not-allowed;
            flex: 1;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .status-badge.active,
        .status-badge.available {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.maintenance {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.retired {
            background: #f8d7da;
            color: #721c24;
        }

        .availability-calendar {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .availability-calendar h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .calendar-legend {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .legend-color.available {
            background: #d4edda;
        }

        .legend-color.fully-booked {
            background: #f8d7da;
        }

        .legend-color.partially-booked {
            background: #fff3cd;
        }

        #availabilityCalendar {
            background: white;
            border-radius: 8px;
            padding: 10px;
        }

        /* Flatpickr custom styles for availability */
        .flatpickr-day.fully-booked {
            background: #f8d7da !important;
            color: #721c24 !important;
            border-color: #f5c6cb !important;
            position: relative;
        }

        .flatpickr-day.fully-booked:hover {
            background: #f5c6cb !important;
        }

        .flatpickr-day.fully-booked::after {
            content: "✕";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 20px;
            color: #721c24;
            font-weight: bold;
        }

        .flatpickr-day.partially-booked {
            background: #fff3cd !important;
            color: #856404 !important;
            border-color: #ffeaa7 !important;
        }

        .flatpickr-day.partially-booked:hover {
            background: #ffeaa7 !important;
        }

        @media (max-width: 968px) {
            .item-details-wrapper {
                grid-template-columns: 1fr;
                padding: 25px;
            }

            .item-main-image {
                height: 400px;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="item-details-container">
        <div class="item-details-wrapper">
            <div class="item-image-section">
                <img src="<?php echo htmlspecialchars(!empty($item['image_url']) ? $item['image_url'] : '/ireserve/assets/images/no-image.png'); ?>" 
                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                     class="item-main-image">
                
                <?php if (!empty($item['latitude']) && !empty($item['longitude'])): ?>
                <div class="item-location-map" style="margin-top: 20px;">
                    <h3><i class="ri-map-pin-line"></i> Item Location</h3>
                    <div id="map" style="width: 100%; height: 400px; border-radius: 8px; margin-top: 15px;"></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="item-info-section">
                <span class="status-badge <?php echo htmlspecialchars($status); ?>">
                    <?php 
                    if ($status == 'maintenance') echo 'Under Maintenance';
                    elseif ($status == 'retired') echo 'Retired';
                    elseif ($status == 'active' || $status == 'available') echo 'Available';
                    else echo ucfirst($status);
                    ?>
                </span>
                <h1><?php echo htmlspecialchars($item['name']); ?></h1>

                <div class="item-meta">
                    <div class="meta-item">
                        <i class="ri-folder-line"></i>
                        <span class="meta-label">Category:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="ri-barcode-line"></i>
                        <span class="meta-label">SKU:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($item['sku']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="ri-map-pin-line"></i>
                        <span class="meta-label">Location:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($item['location'] ?? 'Not specified'); ?></span>
                    </div>
                </div>

                <div class="availability-section">
                    <div class="availability-title">
                        <i class="ri-information-line"></i> Availability
                    </div>
                    <div class="availability-stats">
                        <div class="stat-box">
                            <div class="stat-value" style="color: <?php echo $isAvailable ? '#28a745' : '#dc3545'; ?>">
                                <?php echo (int)$item['quantity']; ?>
                            </div>
                            <div class="stat-label">Total Quantity</div>
                        </div>
                    </div>
                </div>

                <!-- Availability Calendar -->
                <div class="availability-calendar">
                    <h3><i class="ri-calendar-line"></i> Availability Calendar</h3>
                    <div class="calendar-legend">
                        <div class="legend-item">
                            <div class="legend-color available"></div>
                            <span>Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color partially-booked"></div>
                            <span>Partially Booked</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color fully-booked"></div>
                            <span>Fully Booked</span>
                        </div>
                    </div>
                    <div id="availabilityCalendar"></div>
                </div>

                <?php if (!empty($item['description'])): ?>
                <div class="item-description">
                    <h3><i class="ri-file-text-line"></i> Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <a href="/ireserve/user/allItems.php" class="btn-back">
                        <i class="ri-arrow-left-line"></i> Back
                    </a>
                    <?php if ($isAvailable): ?>
                    <a href="/ireserve/user/bookingForm.php?item_id=<?php echo $item_id; ?>" class="btn-book-now">
                        <i class="ri-bookmark-line"></i> Book This Item
                    </a>
                    <?php else: ?>
                    <button class="btn-unavailable-full" disabled>
                        <i class="ri-close-circle-line"></i> 
                        <?php 
                        if ($status == 'maintenance') echo 'Under Maintenance';
                        elseif ($status == 'retired') echo 'Retired';
                        elseif ($status == 'unavailable') echo 'Not Available';
                        else echo 'Not Available';
                        ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="/ireserve/assets/js/script.js"></script>
    
    <script>
        // Initialize availability calendar
        const itemId = <?php echo $item_id; ?>;
        const itemTotalQuantity = <?php echo (int)$item['quantity']; ?>;
        let bookedDates = [];

        // Fetch availability data
        fetch('/ireserve/user/get_item_availability.php?item_id=' + itemId)
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    bookedDates = data.booked_ranges || [];
                    
                    // Initialize flatpickr calendar
                    flatpickr("#availabilityCalendar", {
                        inline: true,
                        mode: "range",
                        minDate: "today",
                        dateFormat: "Y-m-d",
                        showMonths: 1,
                        onDayCreate: function(dObj, dStr, fp, dayElem) {
                            const date = dayElem.dateObj;
                            const year = date.getFullYear();
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const day = String(date.getDate()).padStart(2, '0');
                            const dateStr = `${year}-${month}-${day}`;
                            
                            // Calculate booked quantity for this date
                            let bookedQty = 0;
                            bookedDates.forEach(range => {
                                const rangeStart = new Date(range.start);
                                const rangeEnd = new Date(range.end);
                                const currentDate = new Date(dateStr);
                                
                                if (currentDate >= rangeStart && currentDate <= rangeEnd) {
                                    bookedQty += parseInt(range.quantity) || 0;
                                }
                            });
                            
                            // Apply styling based on availability
                            if (bookedQty >= itemTotalQuantity) {
                                dayElem.classList.add('fully-booked');
                                dayElem.title = `Fully booked (${bookedQty}/${itemTotalQuantity})`;
                            } else if (bookedQty > 0) {
                                dayElem.classList.add('partially-booked');
                                const available = itemTotalQuantity - bookedQty;
                                dayElem.title = `${available} available (${bookedQty} booked)`;
                            } else {
                                dayElem.title = `${itemTotalQuantity} available`;
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error loading availability:', error);
            });
    </script>
    
    <?php if (!empty($item['latitude']) && !empty($item['longitude'])): ?>
    <script>
        function initMap() {
            const itemLocation = { 
                lat: <?php echo (float)$item['latitude']; ?>, 
                lng: <?php echo (float)$item['longitude']; ?> 
            };
            
            const map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: itemLocation,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true
            });
            
            const marker = new google.maps.Marker({
                position: itemLocation,
                map: map,
                title: '<?php echo htmlspecialchars($item['name']); ?>',
                animation: google.maps.Animation.DROP
            });
            
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 10px;">
                        <h4 style="margin: 0 0 8px 0; color: #2a2185;"><?php echo htmlspecialchars($item['name']); ?></h4>
                        <p style="margin: 0; color: #666;">
                            <i class="ri-map-pin-line"></i> <?php echo htmlspecialchars($item['location'] ?? 'Location'); ?>
                        </p>
                    </div>
                `
            });
            
            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });
            
            // Auto-open info window
            infoWindow.open(map, marker);
        }
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCEWZ4A9Tzq8j7Ud6r4tHhoHSLMqJbPonY&callback=initMap"></script>
    <?php endif; ?>
</body>

</html>

