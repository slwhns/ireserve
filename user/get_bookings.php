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
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';

// Build query based on filter
$where = "b.user_id = ?";
$params = [$user_id];
$types = 'i';

if ($status_filter !== 'all') {
    $where .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Fetch bookings with item details
$sql = "SELECT 
            b.ref_code,
            b.item_id,
            b.qty, 
            b.start_at, 
            b.end_at, 
            b.status, 
            b.purpose, 
            b.admin_note, 
            b.created_at,
            i.name AS item_name, 
            i.sku AS item_sku, 
            CONCAT(f.faculty_name, ' (', f.faculty_code, ')') AS item_location, 
            i.image_url AS item_image
        FROM bookings b
        LEFT JOIN inventory_items i ON i.item_id = b.item_id
        LEFT JOIN faculties f ON i.faculty_id = f.faculty_id
        WHERE $where
        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'db_error_prepare']);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = [
        'ref_code' => $row['ref_code'],
        'item_id' => (int)$row['item_id'],
        'qty' => (int)$row['qty'],
        'start_at' => $row['start_at'],
        'end_at' => $row['end_at'],
        'status' => $row['status'],
        'purpose' => $row['purpose'],
        'admin_note' => $row['admin_note'],
        'created_at' => $row['created_at'],
        'item_name' => $row['item_name'],
        'item_sku' => $row['item_sku'],
        'item_location' => $row['item_location'],
        'item_image' => $row['item_image'] ?: '/ireserve/assets/images/no-image.png'
    ];
}

echo json_encode(['ok' => true, 'bookings' => $bookings, 'total' => count($bookings)]);

$stmt->close();
