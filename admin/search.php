<?php

session_start();
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit();
}

$results = [
    'users' => [],
    'inventory' => [],
    'bookings' => []
];

$searchTerm = '%' . $query . '%';

// Search Users
$stmt = $conn->prepare("
    SELECT id, name, email, role, picture_url 
    FROM users 
    WHERE name LIKE ? OR email LIKE ? 
    ORDER BY name ASC 
    LIMIT 5
");
if ($stmt) {
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results['users'][] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => $row['role'],
            'picture_url' => $row['picture_url'] ?? '',
            'url' => '/ireserve/admin/view-user.php?id=' . $row['id']
        ];
    }
    $stmt->close();
}

// Search Inventory Items
$stmt = $conn->prepare("
    SELECT i.item_id, i.name, i.sku, i.quantity, i.status, i.location, i.image_url, c.name as category_name
    FROM inventory_items i
    LEFT JOIN categories c ON i.category_id = c.category_id
    WHERE i.name LIKE ? OR i.sku LIKE ? OR i.location LIKE ?
    ORDER BY i.name ASC
    LIMIT 5
");
if ($stmt) {
    $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results['inventory'][] = [
            'id' => $row['item_id'],
            'name' => $row['name'],
            'sku' => $row['sku'],
            'quantity' => $row['quantity'],
            'status' => $row['status'],
            'location' => $row['location'] ?? 'N/A',
            'category' => $row['category_name'] ?? 'Uncategorized',
            'image_url' => $row['image_url'] ?? '',
            'url' => '/ireserve/admin/inventory_view.php?id=' . $row['item_id']
        ];
    }
    $stmt->close();
}

// Search Bookings
$stmt = $conn->prepare("
    SELECT 
        b.booking_id,
        b.booking_reference,
        b.status,
        b.booking_date,
        b.start_date,
        b.end_date,
        u.name as user_name,
        u.email as user_email,
        i.name as item_name,
        i.sku as item_sku
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN inventory_items i ON b.item_id = i.item_id
    WHERE b.booking_reference LIKE ? 
       OR u.name LIKE ? 
       OR u.email LIKE ?
       OR i.name LIKE ?
       OR i.sku LIKE ?
    ORDER BY b.booking_date DESC
    LIMIT 5
");
if ($stmt) {
    $stmt->bind_param('sssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results['bookings'][] = [
            'id' => $row['booking_id'],
            'reference' => $row['booking_reference'],
            'status' => $row['status'],
            'user_name' => $row['user_name'] ?? 'Unknown',
            'user_email' => $row['user_email'] ?? '',
            'item_name' => $row['item_name'] ?? 'Unknown',
            'item_sku' => $row['item_sku'] ?? '',
            'booking_date' => $row['booking_date'],
            'start_date' => $row['start_date'],
            'end_date' => $row['end_date'],
            'url' => '/ireserve/admin/bookings.php?q=' . urlencode($row['booking_reference'])
        ];
    }
    $stmt->close();
}

// Calculate total results
$totalResults = count($results['users']) + count($results['inventory']) + count($results['bookings']);

echo json_encode([
    'query' => $query,
    'total' => $totalResults,
    'results' => $results
]);
