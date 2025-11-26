<?php
session_name('ireserve_user');
session_start();
require_once __DIR__ . '/../config.php';

// Check if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['user_id'])) {
    header("Location: /ireserve/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: user_page.php?error=invalid_request");
    exit();
}

// Get form data
$item_id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
$end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : '';
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
$user_id = (int)$_SESSION['user_id'];

// Validate input - all fields are required including purpose
if ($item_id <= 0 || empty($start_date) || empty($end_date) || $quantity <= 0 || empty($purpose)) {
    header("Location: user_page.php?error=missing_fields");
    exit();
}

// Validate dates
$start = strtotime($start_date);
$end = strtotime($end_date);
$today = strtotime(date('Y-m-d'));

if ($start === false || $end === false) {
    header("Location: user_page.php?error=invalid_date");
    exit();
}

if ($start < $today) {
    header("Location: user_page.php?error=past_date");
    exit();
}

if ($end < $start) {
    header("Location: user_page.php?error=end_before_start");
    exit();
}

// Check if item exists and is active
$stmt = $conn->prepare("SELECT i.item_id, i.name, i.quantity, i.status
                        FROM inventory_items i
                        WHERE i.item_id = ?");
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: user_page.php?error=item_not_found");
    exit();
}

$item = $result->fetch_assoc();
$stmt->close();

// Check if item status allows booking (only active/available items can be booked)
$item_status = strtolower($item['status']);
if ($item_status === 'maintenance' || $item_status === 'unavailable') {
    header("Location: user_page.php?error=item_not_bookable&status=" . urlencode($item['status']));
    exit();
}

// Check availability for the requested date range
// Count quantity already booked during the requested period
// Bookings overlap if: (start_at <= end_date AND end_at >= start_date)
// Only count approved and taken bookings (pending/rejected/returned don't block availability)
$checkAvailStmt = $conn->prepare("
    SELECT COALESCE(SUM(b.qty), 0) AS qty_booked,
           GROUP_CONCAT(CONCAT('ID:', b.booking_id, ' Ref:', b.ref_code, ' Status:', b.status, ' Dates:', DATE(b.start_at), ' to ', DATE(b.end_at)) SEPARATOR '; ') AS blocking_bookings
    FROM bookings b
    WHERE b.item_id = ?
    AND b.status IN ('approved', 'taken')
    AND DATE(b.start_at) <= ?
    AND DATE(b.end_at) >= ?
");
$checkAvailStmt->bind_param('iss', $item_id, $end_date, $start_date);
$checkAvailStmt->execute();
$availResult = $checkAvailStmt->get_result();
$availData = $availResult->fetch_assoc();
$checkAvailStmt->close();

$qty_booked = (int)$availData['qty_booked'];
$available = (int)$item['quantity'] - $qty_booked;

// Debug logging
error_log("=== BOOKING AVAILABILITY CHECK ===");
error_log("Item ID: $item_id, Item Name: " . $item['name']);
error_log("Requested Dates: $start_date to $end_date");
error_log("Total Quantity: " . $item['quantity']);
error_log("Qty Booked (overlapping): $qty_booked");
error_log("Available: $available");
error_log("Blocking Bookings: " . ($availData['blocking_bookings'] ?? 'NONE'));
error_log("===================================");

// Check if item has any available quantity for the requested dates
if ($available <= 0) {
    header("Location: user_page.php?error=dates_fully_booked&start=" . urlencode($start_date) . "&end=" . urlencode($end_date));
    exit();
}

// Check if requested quantity is available for the requested dates
if ($quantity > $available) {
    header("Location: user_page.php?error=insufficient_quantity_for_dates&available=" . $available . "&requested=" . $quantity);
    exit();
}

// Generate unique reference code
$ref_code = 'BK-' . strtoupper(uniqid());

// Insert booking with purpose (can be empty)
$stmt = $conn->prepare("INSERT INTO bookings (ref_code, user_id, item_id, start_at, end_at, qty, purpose, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
$stmt->bind_param('siissis', $ref_code, $user_id, $item_id, $start_date, $end_date, $quantity, $purpose);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: user_page.php?success=booking_submitted");
    exit();
} else {
    $stmt->close();
    header("Location: user_page.php?error=booking_failed");
    exit();
}
