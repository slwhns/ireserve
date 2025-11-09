<?php
session_name('ireserve_user');
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['email'])) {
    header("Location: /ireserve/index.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];

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

// Check if ref_code is provided
if (!isset($_GET['ref']) || empty($_GET['ref'])) {
    header("Location: /ireserve/user/bookingsDetail.php?error=missing_ref");
    exit();
}

$ref_code = trim($_GET['ref']);

// Fetch booking details
$sql = "SELECT 
            b.ref_code, 
            b.item_id,
            b.qty, 
            b.start_at, 
            b.end_at, 
            b.status, 
            b.purpose,
            i.name AS item_name,
            i.quantity,
            COALESCE((SELECT SUM(qty) FROM bookings WHERE item_id = b.item_id AND status = 'taken'), 0) AS qty_taken
        FROM bookings b
        LEFT JOIN inventory_items i ON i.item_id = b.item_id
        WHERE b.ref_code = ? AND b.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $ref_code, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: /ireserve/user/bookingsDetail.php?error=booking_not_found");
    exit();
}

$booking = $result->fetch_assoc();
$stmt->close();

// Only allow editing of pending bookings
if ($booking['status'] !== 'pending') {
    header("Location: /ireserve/user/bookingsDetail.php?error=cannot_edit_approved_booking");
    exit();
}

// Calculate available quantity (total - taken)
$available_qty = max(0, (int)$booking['quantity'] - (int)$booking['qty_taken']);

// Calculate max available quantity (current available + booked quantity)
$max_qty = $available_qty + $booking['qty'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking | iReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="/ireserve/assets/css/style.css">
    <link rel="stylesheet" href="/ireserve/assets/css/user_page.css">
    <link rel="stylesheet" href="/ireserve/assets/css/bookingForm.css">
    <link rel="stylesheet" href="/ireserve/assets/css/settings.css">
</head>

<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Notification Modal -->
    <div id="notificationModal" class="notification-modal">
        <div class="notification-content">
            <div class="notification-icon" id="notificationIcon"></div>
            <div class="notification-message" id="notificationMessage"></div>
            <button class="notification-btn-ok" id="notificationBtnOk">OK</button>
        </div>
    </div>

    <div class="booking-form-container">
        <div class="back-button-container-top">
            <a href="/ireserve/user/bookingsDetail.php" class="btn-back">
                <i class="ri-arrow-left-line"></i> Back to My Bookings
            </a>
        </div>

        <div class="booking-form-header">
            <h1><i class="ri-edit-line"></i> Edit Booking</h1>
            <p>Update your booking details below</p>
        </div>

        <div class="booking-form-wrapper">
            <form action="/ireserve/user/update_booking.php" method="POST" id="editBookingForm" class="booking-form">
                <input type="hidden" name="ref_code" value="<?php echo htmlspecialchars($booking['ref_code']); ?>">
                <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($booking['item_id']); ?>">
                <input type="hidden" name="original_qty" value="<?php echo htmlspecialchars($booking['qty']); ?>">
                
                <div class="form-row">
                    <div class="form-group-full">
                        <label for="itemName"><i class="ri-file-list-line"></i> Item Name</label>
                        <input type="text" 
                               id="itemName" 
                               value="<?php echo htmlspecialchars($booking['item_name']); ?>"
                               readonly 
                               style="background: #f5f5f5; cursor: not-allowed;" />
                        <small class="form-hint">Item cannot be changed. Cancel and create a new booking to select a different item.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="startDate"><i class="ri-calendar-line"></i> Start Date *</label>
                        <input type="text" 
                               id="startDate" 
                               name="start_date" 
                               value="<?php echo date('Y-m-d H:i', strtotime($booking['start_at'])); ?>"
                               required 
                               readonly />
                        <small class="form-hint">Select the start date for your booking</small>
                    </div>

                    <div class="form-group">
                        <label for="endDate"><i class="ri-calendar-check-line"></i> End Date *</label>
                        <input type="text" 
                               id="endDate" 
                               name="end_date" 
                               value="<?php echo date('Y-m-d H:i', strtotime($booking['end_at'])); ?>"
                               required 
                               readonly />
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
                               max="<?php echo $max_qty; ?>"
                               value="<?php echo htmlspecialchars($booking['qty']); ?>"
                               required />
                        <small class="form-hint">Maximum available: <?php echo $max_qty; ?> units</small>
                    </div>

                    <div class="form-group">
                        <label for="purpose"><i class="ri-file-text-line"></i> Purpose (Optional)</label>
                        <input type="text" 
                               id="purpose" 
                               name="purpose" 
                               placeholder="e.g., Class project, Event, etc."
                               value="<?php echo htmlspecialchars($booking['purpose'] ?? ''); ?>" />
                        <small class="form-hint">Why do you need this item?</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="ri-save-line"></i> Update Booking
                    </button>
                    <a href="/ireserve/user/bookingsDetail.php" class="btn-cancel">
                        <i class="ri-close-line"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="/ireserve/assets/js/script.js"></script>
    <script>
        // Initialize Flatpickr for date pickers
        flatpickr("#startDate", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: true
        });

        flatpickr("#endDate", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            time_24hr: true
        });

        // Form validation
        document.getElementById('editBookingForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('startDate').value);
            const endDate = new Date(document.getElementById('endDate').value);
            
            if (endDate <= startDate) {
                e.preventDefault();
                alert('End date must be after start date!');
                return false;
            }
        });
    </script>
</body>
</html>
