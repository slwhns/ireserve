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
        <title>Items Location | iReserve</title>
        <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
        <link rel="stylesheet" href="/ireserve/assets/css/style.css">
        <link rel="stylesheet" href="/ireserve/assets/css/user_page.css?v=2">
    </head>

    <body>
        <?php include __DIR__ . '/includes/header.php'; ?>

        <!-- Items Location Map -->
        <section class="section_container map_container">
            <div class="back-button-container-top">
                <a href="/ireserve/user/user_page.php" class="btn-back">
                    <i class="ri-arrow-left-line"></i> Back
                </a>
            </div>

            <div class="map_header">
                <h1><i class="ri-map-pin-line"></i> Items Location</h1>
                <p class="map_description">Explore the locations of all available items across campus. Click on markers to view details and book items.</p>
            </div>
            
            <div class="map_wrapper">
                <div id="map"></div>
            </div>
        </section>

        <?php include __DIR__ . '/includes/footer.php'; ?>

        <!-- Main Script -->
        <script src="/ireserve/assets/js/script.js"></script>

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
