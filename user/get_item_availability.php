<?php
/**
 * Get item availability calendar
 * Returns booked date ranges for an item
 */
header('Content-Type: application/json');
session_name('ireserve_user');
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['email'])) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

if ($item_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'invalid_item_id']);
    exit;
}

// Get item details
$itemStmt = $conn->prepare("SELECT item_id, name, quantity, status FROM inventory_items WHERE item_id = ?");
$itemStmt->bind_param('i', $item_id);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

if ($itemResult->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'item_not_found']);
    exit;
}

$item = $itemResult->fetch_assoc();
$itemStmt->close();

// Check if item can be booked at all
$status = strtolower($item['status']);
if ($status === 'maintenance' || $status === 'unavailable') {
    echo json_encode([
        'ok' => true,
        'can_book' => false,
        'status' => $item['status'],
        'message' => 'This item is currently ' . $item['status'] . ' and cannot be booked.'
    ]);
    exit;
}

// Get all approved/taken bookings for this item
$bookingsStmt = $conn->prepare("
    SELECT DATE(start_at) as start_date, DATE(end_at) as end_date, qty, status, ref_code
    FROM bookings
    WHERE item_id = ?
    AND status IN ('approved', 'taken')
    ORDER BY start_at
");
$bookingsStmt->bind_param('i', $item_id);
$bookingsStmt->execute();
$bookingsResult = $bookingsStmt->get_result();

$bookedRanges = [];
while ($booking = $bookingsResult->fetch_assoc()) {
    $bookedRanges[] = [
        'start' => $booking['start_date'],
        'end' => $booking['end_date'],
        'quantity' => (int)$booking['qty'],
        'status' => $booking['status'],
        'ref_code' => $booking['ref_code']
    ];
}
$bookingsStmt->close();

echo json_encode([
    'ok' => true,
    'can_book' => true,
    'item' => [
        'id' => $item['item_id'],
        'name' => $item['name'],
        'total_quantity' => (int)$item['quantity'],
        'status' => $item['status']
    ],
    'booked_ranges' => $bookedRanges
]);
?>
