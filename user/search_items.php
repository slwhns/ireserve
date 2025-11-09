<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

session_name('ireserve_user');
session_start();
if (!isset($_SESSION['email'])) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    http_response_code(401);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '' || mb_strlen($q) < 1) {
    echo json_encode(['ok' => true, 'results' => [], 'total' => 0]);
    exit;
}

// Prepare wildcard for LIKE searches
$like = '%' . $q . '%';

// Search items - show all items regardless of status
$sql = "SELECT i.item_id, i.name, i.sku, i.quantity, i.location, i.status, i.image_url, c.name AS category_name,
               COALESCE((
                   SELECT SUM(b.qty)
                   FROM bookings b
                   WHERE b.item_id = i.item_id AND b.status = 'taken'
               ), 0) AS qty_taken
        FROM inventory_items i
        LEFT JOIN categories c ON i.category_id = c.category_id
        WHERE (i.name LIKE ? OR i.sku LIKE ? OR i.location LIKE ? OR c.name LIKE ?)
        ORDER BY i.item_id DESC LIMIT 20";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'db_error_prepare']);
    exit;
}
$stmt->bind_param('ssss', $like, $like, $like, $like);
$stmt->execute();
$res = $stmt->get_result();
$results = [];
while ($row = $res->fetch_assoc()) {
    $available = max(0, (int)$row['quantity'] - (int)$row['qty_taken']);
    $results[] = [
        'id' => (int)$row['item_id'],
        'name' => $row['name'],
        'sku' => $row['sku'],
        'category' => $row['category_name'],
        'quantity' => (int)$row['quantity'],
        'available' => $available,
        'location' => $row['location'],
        'status' => $row['status'],
        'image' => $row['image_url'] ?: '/ireserve/assets/images/no-image.png'
    ];
}

echo json_encode(['ok' => true, 'results' => $results, 'total' => count($results)]);

$stmt->close();
