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

// Get pre-selected item if passed via URL
$preselected_item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$preselected_item_name = '';
$preselected_available = 0;

if ($preselected_item_id > 0) {
    $stmt = $conn->prepare("SELECT i.name, i.quantity, 
                            COALESCE((SELECT SUM(b.qty) FROM bookings b WHERE b.item_id = i.item_id AND b.status = 'taken'), 0) AS qty_taken
                            FROM inventory_items i WHERE i.item_id = ?");
    $stmt->bind_param('i', $preselected_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $item_data = $result->fetch_assoc();
        $preselected_item_name = $item_data['name'];
        $preselected_available = max(0, (int)$item_data['quantity'] - (int)$item_data['qty_taken']);
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Item | iReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/ireserve/assets/css/style.css">
    <link rel="stylesheet" href="/ireserve/assets/css/user_page.css">
    <link rel="stylesheet" href="/ireserve/assets/css/bookingForm.css">
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

    <div class="booking-form-container">
        <div class="back-button-container-top">
            <a href="/ireserve/user/user_page.php" class="btn-back">
                <i class="ri-arrow-left-line"></i> Back
            </a>
        </div>

        <div class="booking-form-header">
            <h1><i class="ri-calendar-check-line"></i> Book an Item</h1>
            <p>Fill in the details below to reserve your item</p>
        </div>

        <div class="booking-form-wrapper">
            <form action="/ireserve/user/submit_booking.php" method="POST" id="bookingForm" class="booking-form">
                
                <div class="form-row">
                    <div class="form-group-full">
                        <label for="itemSearch"><i class="ri-search-line"></i> Item Name *</label>
                        <input type="text" 
                               id="itemSearch" 
                               name="item_name" 
                               autocomplete="off" 
                               value="<?php echo htmlspecialchars($preselected_item_name); ?>"
                               required />
                        <input type="hidden" 
                               id="itemId" 
                               name="item_id" 
                               value="<?php echo $preselected_item_id; ?>"
                               required />
                        <div class="search-dropdown" id="searchDropdown"></div>
                        <small class="form-hint">Search and select the item you want to reserve</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="startDate"><i class="ri-calendar-line"></i> Start Date *</label>
                        <input type="text" id="startDate" name="start_date" required readonly />
                        <small class="form-hint">Select the start date for your booking</small>
                    </div>

                    <div class="form-group">
                        <label for="endDate"><i class="ri-calendar-check-line"></i> End Date *</label>
                        <input type="text" id="endDate" name="end_date" required readonly />
                        <small class="form-hint">Select the end date for your booking</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity"><i class="ri-stack-line"></i> Quantity *</label>
                        <input type="number" 
                               id="quantity" 
                               name="quantity" 
                               min="1" 
                               <?php if ($preselected_available > 0) echo 'max="' . $preselected_available . '"'; ?>
                               required />
                        <small class="form-hint" id="availableQty">
                            <?php 
                            if ($preselected_available > 0) {
                                echo "Available: {$preselected_available} units";
                            } else {
                                echo "Enter the quantity you need";
                            }
                            ?>
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="purpose"><i class="ri-file-text-line"></i> Purpose (Optional)</label>
                        <input type="text" id="purpose" name="purpose" placeholder="e.g., Class project, Event, etc." />
                        <small class="form-hint">Why do you need this item?</small>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="/ireserve/user/user_page.php" class="btn-cancel">
                        <i class="ri-close-line"></i> Cancel
                    </a>
                    <button type="submit" class="btn-submit">
                        <i class="ri-send-plane-line"></i> Submit Booking
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <!-- Flatpickr JS for Date Picker -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Main Script - Load first -->
    <script src="/ireserve/assets/js/script.js"></script>
    <!-- User Booking Functionality -->
    <script src="/ireserve/assets/js/user_booking.js"></script>
    
    <script>
    // Booking Form specific functionality
    document.addEventListener('DOMContentLoaded', function() {
        const itemSearch = document.getElementById('itemSearch');
        const itemIdField = document.getElementById('itemId');
        const searchDropdown = document.getElementById('searchDropdown');
        let searchTimeout;

        if (!itemSearch || !itemIdField) return;

        // Item search autocomplete for booking form
        itemSearch.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Clear item_id when user types
            if (itemIdField.value !== '0') {
                itemIdField.value = '';
            }
            
            clearTimeout(searchTimeout);
            
            if (query.length < 1) {
                searchDropdown.classList.remove('active');
                searchDropdown.innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch('/ireserve/user/search_items.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        if (data.ok && data.results.length > 0) {
                            displayAutocompleteForBooking(data.results);
                        } else {
                            searchDropdown.innerHTML = '<div class="search-item" style="justify-content: center; padding: 10px;"><p>No items found</p></div>';
                            searchDropdown.classList.add('active');
                        }
                    })
                    .catch(error => {
                        console.error('Autocomplete error:', error);
                    });
            }, 300);
        });

        function displayAutocompleteForBooking(results) {
            searchDropdown.innerHTML = '';
            
            results.forEach(item => {
                const status = item.status ? item.status.toLowerCase() : 'active';
                const canBook = (status === 'active' || status === 'available');
                
                // Status badge styling
                let statusColor = 'green';
                let statusText = status.charAt(0).toUpperCase() + status.slice(1);
                if (status === 'maintenance') {
                    statusColor = 'orange';
                } else if (status === 'unavailable') {
                    statusColor = 'red';
                }
                
                const itemDiv = document.createElement('div');
                itemDiv.className = 'search-item';
                itemDiv.style.cursor = canBook ? 'pointer' : 'not-allowed';
                itemDiv.style.opacity = canBook ? '1' : '0.6';
                itemDiv.innerHTML = `
                    <img src="${item.image}" alt="${item.name}" class="search-item-img" style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                    <div class="search-item-info">
                        <h4 style="margin:0;font-size:14px;font-weight:600;">${item.name} ${!canBook ? '🔒' : ''}</h4>
                        <p style="margin:4px 0;font-size:12px;color:#666;">Category: ${item.category} | Location: ${item.location}</p>
                        <p style="margin:2px 0;font-size:12px;">Status: <span style="color:${statusColor};font-weight:600;">${statusText}</span></p>
                        <p style="margin:0;font-size:12px;"><strong>Available: ${item.available}</strong> / ${item.quantity}</p>
                        ${!canBook ? '<p style="margin:2px 0;font-size:11px;color:red;font-style:italic;">Cannot book this item</p>' : ''}
                    </div>
                `;
                
                if (canBook) {
                    itemDiv.addEventListener('click', function() {
                        itemSearch.value = item.name;
                        itemIdField.value = item.id; // Set the hidden item_id field
                        searchDropdown.classList.remove('active');
                        searchDropdown.innerHTML = '';
                        
                        // Update available quantity hint if exists
                        const availableQty = document.getElementById('availableQty');
                        if (availableQty) {
                            availableQty.textContent = 'Available: ' + item.available + ' units';
                        }
                        
                        // Update max quantity
                        const quantityInput = document.getElementById('quantity');
                        if (quantityInput && item.available > 0) {
                            quantityInput.setAttribute('max', item.available);
                        }
                    });
                } else {
                    itemDiv.addEventListener('click', function(e) {
                        e.preventDefault();
                        alert('This item is currently ' + statusText + ' and cannot be booked.');
                    });
                }
                
                searchDropdown.appendChild(itemDiv);
            });
            
            searchDropdown.classList.add('active');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!itemSearch.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.classList.remove('active');
            }
        });

        // Initialize Flatpickr for date inputs
        flatpickr("#startDate", {
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                const endDatePicker = document.querySelector('#endDate')._flatpickr;
                if (endDatePicker) {
                    endDatePicker.set('minDate', dateStr);
                }
            }
        });

        flatpickr("#endDate", {
            dateFormat: "Y-m-d",
            minDate: "today"
        });
    });
    </script>
</body>

</html>
