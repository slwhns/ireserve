<?php
// Page configuration
$pageTitle = 'Edit Item';
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

// Check if item_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: inventory.php?error=missing_id");
    exit();
}

$item_id = (int)$_GET['id'];

// Fetch item details
$sql = "SELECT * FROM inventory_items WHERE item_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $item_id);
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
?>

<!-- ============ Main Content ============ -->
<div class="main">
    <!-- ============ Top Bar ============ -->
    <?php include 'includes/topbar.php'; ?>

    <!-- ====================== Form Content ====================== -->
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <h2><i class="bi bi-pencil-square"></i> Edit Item</h2>
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

            <form action="inventory_update.php" method="POST" enctype="multipart/form-data" class="inventory-form">
                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($item['image_url']); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">
                            <i class="bi bi-tag"></i> Item Name <span class="required">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" placeholder="Enter item name" required>
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
                                $selected = ($row['category_id'] == $item['category_id']) ? 'selected' : '';
                                echo "<option value='{$row['category_id']}' $selected>{$row['name']}</option>";
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
                        <input type="text" id="sku" name="sku" value="<?php echo htmlspecialchars($item['sku']); ?>" placeholder="e.g., EQP-PROJ-001" required>
                        <small class="form-hint">Stock Keeping Unit (unique identifier)</small>
                    </div>

                    <div class="form-group">
                        <label for="quantity">
                            <i class="bi bi-box"></i> Quantity <span class="required">*</span>
                        </label>
                        <input type="number" id="quantity" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" placeholder="0" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">
                            <i class="bi bi-check-circle"></i> Status <span class="required">*</span>
                        </label>
                        <select id="status" name="status" required>
                            <option value="available" <?php echo ($item['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                            <option value="booked" <?php echo ($item['status'] == 'booked') ? 'selected' : ''; ?>>Booked</option>
                            <option value="maintenance" <?php echo ($item['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="unavailable" <?php echo ($item['status'] == 'unavailable') ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="location">
                            <i class="bi bi-geo-alt"></i> Location
                        </label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($item['location']); ?>" placeholder="e.g., Store A, Level 2">
                        <small class="form-hint">Physical location of the item</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude">
                            <i class="bi bi-compass"></i> Latitude
                        </label>
                        <input type="text" id="latitude" name="latitude" value="<?php echo htmlspecialchars($item['latitude'] ?? ''); ?>" placeholder="e.g., 6.1167" step="any">
                        <small class="form-hint">Click on map to set coordinates</small>
                    </div>
                    <div class="form-group">
                        <label for="longitude">
                            <i class="bi bi-compass"></i> Longitude
                        </label>
                        <input type="text" id="longitude" name="longitude" value="<?php echo htmlspecialchars($item['longitude'] ?? ''); ?>" placeholder="e.g., 102.2833" step="any">
                        <small class="form-hint">Click on map to set coordinates</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>
                            <i class="bi bi-map"></i> Set Location on Map
                        </label>
                        <div id="locationMap" style="width: 100%; height: 400px; border-radius: 8px; border: 1px solid #ddd;"></div>
                        <small class="form-hint">Click anywhere on the map to set the item's location</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="description">
                            <i class="bi bi-text-left"></i> Description
                        </label>
                        <textarea id="description" name="description" rows="4" placeholder="Enter item description (optional)"><?php echo htmlspecialchars($item['description']); ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="image">
                            <i class="bi bi-image"></i> Item Image
                        </label>
                        
                        <?php if (!empty($item['image_url'])): ?>
                            <div class="current-image">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="Current image" style="max-width: 200px; max-height: 200px; border-radius: 8px; margin-bottom: 10px;">
                                <p class="text-muted small">Current image (upload new to replace)</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="file-input-wrapper">
                            <input type="file" id="image" name="image" accept="image/*" class="file-input">
                            <label for="image" class="file-label">
                                <i class="bi bi-upload"></i> Choose New Image
                                <span class="file-name">No file chosen</span>
                            </label>
                        </div>
                        <small class="form-hint">Supported formats: JPG, PNG, GIF (Max 5MB). Leave empty to keep current image.</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-check-circle"></i> Update Item
                    </button>
                    <a href="/ireserve/admin/inventory.php" class="btn-cancel">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
    let map, marker;
    
    function initMap() {
        // Default location (Kota Bharu, Kelantan, Malaysia)
        const defaultLocation = { lat: 6.1167, lng: 102.2833 };
        
        map = new google.maps.Map(document.getElementById('locationMap'), {
            zoom: 13,
            center: defaultLocation,
            mapTypeControl: true,
            streetViewControl: true
        });
        
        // Add click listener to map
        map.addListener('click', (event) => {
            placeMarker(event.latLng);
        });
        
        // Check if there are existing coordinates
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        
        if (latInput.value && lngInput.value) {
            const existingLocation = {
                lat: parseFloat(latInput.value),
                lng: parseFloat(lngInput.value)
            };
            map.setCenter(existingLocation);
            placeMarker(new google.maps.LatLng(existingLocation.lat, existingLocation.lng));
        }
    }
    
    function placeMarker(location) {
        if (marker) {
            marker.setPosition(location);
        } else {
            marker = new google.maps.Marker({
                position: location,
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP
            });
            
            // Update coordinates when marker is dragged
            marker.addListener('dragend', () => {
                updateCoordinates(marker.getPosition());
            });
        }
        
        updateCoordinates(location);
        map.panTo(location);
    }
    
    function updateCoordinates(location) {
        document.getElementById('latitude').value = location.lat().toFixed(6);
        document.getElementById('longitude').value = location.lng().toFixed(6);
    }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap"></script>

<?php require_once 'includes/footer.php'; ?>

