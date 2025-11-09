<?php
session_name('ireserve_user');
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    http_response_code(401);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Check if ref_code is provided
if (!isset($_POST['ref_code']) || empty($_POST['ref_code'])) {
    echo json_encode(['ok' => false, 'error' => 'missing_ref_code']);
    exit;
}

$ref_code = trim($_POST['ref_code']);

// Verify the booking belongs to this user and is still pending
$check_sql = "SELECT status, item_id, qty FROM bookings WHERE ref_code = ? AND user_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param('si', $ref_code, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'booking_not_found']);
    exit;
}

$booking = $result->fetch_assoc();

// Only allow cancellation of pending bookings
if ($booking['status'] !== 'pending') {
    echo json_encode(['ok' => false, 'error' => 'cannot_cancel_approved_booking']);
    exit;
}

$item_id = $booking['item_id'];
$qty = $booking['qty'];
$stmt->close();

// Start transaction
$conn->begin_transaction();

try {
    // Delete the booking
    $delete_sql = "DELETE FROM bookings WHERE ref_code = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param('si', $ref_code, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete booking');
    }
    $stmt->close();

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['ok' => true, 'message' => 'Booking cancelled successfully']);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['ok' => false, 'error' => 'database_error', 'message' => $e->getMessage()]);
}
