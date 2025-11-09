<?php
session_name('ireserve_user');
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
    header("Location: /ireserve/index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Validate required fields
if (!isset($_POST['ref_code']) || !isset($_POST['item_id']) || !isset($_POST['start_date']) || 
    !isset($_POST['end_date']) || !isset($_POST['quantity']) || !isset($_POST['original_qty'])) {
    header("Location: /ireserve/user/bookingsDetail.php?error=missing_fields");
    exit();
}

$ref_code = trim($_POST['ref_code']);
$item_id = (int)$_POST['item_id'];
$start_date = trim($_POST['start_date']);
$end_date = trim($_POST['end_date']);
$quantity = (int)$_POST['quantity'];
$original_qty = (int)$_POST['original_qty'];
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';

// Validate quantity
if ($quantity < 1) {
    header("Location: /ireserve/user/editBooking.php?ref={$ref_code}&error=invalid_quantity");
    exit();
}

// Validate dates
$start_timestamp = strtotime($start_date);
$end_timestamp = strtotime($end_date);

if ($start_timestamp === false || $end_timestamp === false) {
    header("Location: /ireserve/user/editBooking.php?ref={$ref_code}&error=invalid_date");
    exit();
}

if ($end_timestamp <= $start_timestamp) {
    header("Location: /ireserve/user/editBooking.php?ref={$ref_code}&error=end_before_start");
    exit();
}

if ($start_timestamp < time()) {
    header("Location: /ireserve/user/editBooking.php?ref={$ref_code}&error=past_date");
    exit();
}

// Format dates for database
$start_at = date('Y-m-d H:i:s', $start_timestamp);
$end_at = date('Y-m-d H:i:s', $end_timestamp);

// Verify the booking belongs to this user and is still pending
$check_sql = "SELECT status FROM bookings WHERE ref_code = ? AND user_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param('si', $ref_code, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: /ireserve/user/bookingsDetail.php?error=booking_not_found");
    exit();
}

$booking_status = $result->fetch_assoc()['status'];
$stmt->close();

if ($booking_status !== 'pending') {
    header("Location: /ireserve/user/bookingsDetail.php?error=cannot_edit_approved_booking");
    exit();
}

// Check item availability if quantity changed
if ($quantity !== $original_qty) {
    $qty_diff = $quantity - $original_qty;
    
    // Check if there's enough available quantity
    $check_qty_sql = "SELECT i.quantity, 
                      COALESCE((SELECT SUM(qty) FROM bookings WHERE item_id = ? AND status = 'taken'), 0) AS qty_taken
                      FROM inventory_items i WHERE i.item_id = ?";
    $stmt = $conn->prepare($check_qty_sql);
    $stmt->bind_param('ii', $item_id, $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: /ireserve/user/editBooking.php?ref={$ref_code}&error=item_not_found");
        exit();
    }
    
    $item_data = $result->fetch_assoc();
    $available_qty = max(0, (int)$item_data['quantity'] - (int)$item_data['qty_taken']);
    $stmt->close();
    
    // If increasing quantity, check if enough available
    if ($qty_diff > 0 && $available_qty < $qty_diff) {
        header("Location: /ireserve/user/editBooking.php?ref={$ref_code}&error=insufficient_quantity");
        exit();
    }
}

// Start transaction
$conn->begin_transaction();

try {
    // Update the booking
    $update_sql = "UPDATE bookings 
                   SET start_at = ?, end_at = ?, qty = ?, purpose = ?
                   WHERE ref_code = ? AND user_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param('ssissi', $start_at, $end_at, $quantity, $purpose, $ref_code, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update booking');
    }
    $stmt->close();

    // Commit transaction
    $conn->commit();
    
    header("Location: /ireserve/user/bookingsDetail.php?success=booking_updated");
    exit();
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    header("Location: /ireserve/user/editBooking.php?ref={$ref_code}&error=update_failed");
    exit();
}
