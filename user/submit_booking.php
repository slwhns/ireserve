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
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : ''; // Optional field
$user_id = (int)$_SESSION['user_id'];

// Validate input (purpose is optional, so not validated)
if ($item_id <= 0 || empty($start_date) || empty($end_date) || $quantity <= 0) {
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
$stmt = $conn->prepare("SELECT i.item_id, i.name, i.quantity, i.status,
                               COALESCE((
                                   SELECT SUM(b.qty)
                                   FROM bookings b
                                   WHERE b.item_id = i.item_id AND b.status = 'taken'
                               ), 0) AS qty_taken
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

// Check if enough quantity is available
$available = (int)$item['quantity'] - (int)$item['qty_taken'];

// Check if item has any available quantity
if ($available <= 0) {
    header("Location: user_page.php?error=item_unavailable");
    exit();
}

// Check if requested quantity is available
if ($quantity > $available) {
    header("Location: user_page.php?error=insufficient_quantity&available=" . $available);
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
