<?php
header('Content-Type: application/json; charset=utf-8');
// Minimal search endpoint for admin to find inventory items by name, sku, item_code or location
require_once __DIR__ . '/../config.php';

// Basic simple auth check (session) - using admin session
session_name('ireserve_admin');
session_start();
if (empty($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
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

// Use prepared statement to avoid SQL injection
$sql = "SELECT item_id, name, sku, quantity, location, status, image_url FROM inventory_items
        WHERE name LIKE ? OR sku LIKE ? OR location LIKE ?
        ORDER BY item_id DESC LIMIT 50";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    // Return a helpful error flag; callers will show a generic message.
    echo json_encode(['ok' => false, 'error' => 'db_error_prepare']);
    exit;
}
$stmt->bind_param('sss', $like, $like, $like);
$stmt->execute();
$res = $stmt->get_result();
$results = [];
while ($row = $res->fetch_assoc()) {
    $results[] = [
        'id' => (int)$row['item_id'],
        'name' => $row['name'],
        'sku' => $row['sku'],
        'quantity' => (int)$row['quantity'],
        'location' => $row['location'],
        'status' => $row['status'],
        'image' => $row['image_url'] ?: '/ireserve/assets/images/no-image.png',
        'url' => '/ireserve/admin/inventory_edit.php?id=' . (int)$row['item_id']
    ];
}

echo json_encode(['ok' => true, 'results' => $results, 'total' => count($results)]);

$stmt->close();
