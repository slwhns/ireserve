<?php
require_once __DIR__ . '/config.php';

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;

echo "<h1>Booking Debug Information</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #2a2185; color: white; }
    .approved { background: #d4edda; }
    .taken { background: #cce5ff; }
    .returned { background: #f8d7da; }
    .pending { background: #fff3cd; }
    .rejected { background: #f8d7da; }
</style>";

if ($item_id > 0) {
    // Get item info
    $itemStmt = $conn->prepare("SELECT item_id, name, quantity, status FROM inventory_items WHERE item_id = ?");
    $itemStmt->bind_param('i', $item_id);
    $itemStmt->execute();
    $itemResult = $itemStmt->get_result();
    $item = $itemResult->fetch_assoc();
    $itemStmt->close();
    
    if ($item) {
        echo "<h2>Item: " . htmlspecialchars($item['name']) . " (ID: $item_id)</h2>";
        echo "<p><strong>Total Quantity:</strong> " . $item['quantity'] . "</p>";
        echo "<p><strong>Status:</strong> " . $item['status'] . "</p>";
        
        // Get all bookings for this item
        $bookingStmt = $conn->prepare("
            SELECT b.booking_id, b.ref_code, b.status, b.qty, 
                   DATE(b.start_at) as start_date, DATE(b.end_at) as end_date,
                   b.start_at, b.end_at,
                   u.name as user_name
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE b.item_id = ?
            ORDER BY b.start_at DESC
        ");
        $bookingStmt->bind_param('i', $item_id);
        $bookingStmt->execute();
        $bookingResult = $bookingStmt->get_result();
        
        echo "<h3>All Bookings</h3>";
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Ref Code</th>
                <th>User</th>
                <th>Status</th>
                <th>Quantity</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Blocks Availability?</th>
              </tr>";
        
        while ($booking = $bookingResult->fetch_assoc()) {
            $blocks = in_array($booking['status'], ['approved', 'taken']) ? '✓ YES' : '✗ NO';
            $class = strtolower($booking['status']);
            
            echo "<tr class='$class'>";
            echo "<td>" . $booking['booking_id'] . "</td>";
            echo "<td>" . htmlspecialchars($booking['ref_code']) . "</td>";
            echo "<td>" . htmlspecialchars($booking['user_name']) . "</td>";
            echo "<td><strong>" . strtoupper($booking['status']) . "</strong></td>";
            echo "<td>" . $booking['qty'] . "</td>";
            echo "<td>" . $booking['start_date'] . "</td>";
            echo "<td>" . $booking['end_date'] . "</td>";
            echo "<td>" . $blocks . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        $bookingStmt->close();
        
        // Test availability for different date ranges
        echo "<h3>Test Date Availability</h3>";
        echo "<form method='GET'>";
        echo "<input type='hidden' name='item_id' value='$item_id'>";
        echo "<label>Start Date: <input type='date' name='test_start' value='" . ($_GET['test_start'] ?? date('Y-m-d')) . "'></label> ";
        echo "<label>End Date: <input type='date' name='test_end' value='" . ($_GET['test_end'] ?? date('Y-m-d', strtotime('+7 days'))) . "'></label> ";
        echo "<button type='submit'>Check Availability</button>";
        echo "</form>";
        
        if (isset($_GET['test_start']) && isset($_GET['test_end'])) {
            $test_start = $_GET['test_start'];
            $test_end = $_GET['test_end'];
            
            $testStmt = $conn->prepare("
                SELECT COALESCE(SUM(b.qty), 0) AS qty_booked
                FROM bookings b
                WHERE b.item_id = ?
                AND b.status IN ('approved', 'taken')
                AND DATE(b.start_at) <= ?
                AND DATE(b.end_at) >= ?
            ");
            $testStmt->bind_param('iss', $item_id, $test_end, $test_start);
            $testStmt->execute();
            $testResult = $testStmt->get_result();
            $testData = $testResult->fetch_assoc();
            $testStmt->close();
            
            $qty_booked = (int)$testData['qty_booked'];
            $available = (int)$item['quantity'] - $qty_booked;
            
            echo "<div style='background: #e7f3ff; padding: 15px; margin: 20px 0; border-left: 4px solid #2a2185;'>";
            echo "<h4>Availability Check Results</h4>";
            echo "<p><strong>Date Range:</strong> $test_start to $test_end</p>";
            echo "<p><strong>Quantity Booked (approved/taken):</strong> $qty_booked</p>";
            echo "<p><strong>Available Quantity:</strong> <span style='font-size: 1.5em; color: " . ($available > 0 ? 'green' : 'red') . ";'><strong>$available</strong></span></p>";
            if ($available > 0) {
                echo "<p style='color: green;'>✓ This item CAN be booked for these dates!</p>";
            } else {
                echo "<p style='color: red;'>✗ This item is FULLY BOOKED for these dates.</p>";
            }
            echo "</div>";
            
            // Show which bookings are blocking
            $blockingStmt = $conn->prepare("
                SELECT b.ref_code, b.status, b.qty, 
                       DATE(b.start_at) as start_date, DATE(b.end_at) as end_date,
                       u.name as user_name
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                WHERE b.item_id = ?
                AND b.status IN ('approved', 'taken')
                AND DATE(b.start_at) <= ?
                AND DATE(b.end_at) >= ?
            ");
            $blockingStmt->bind_param('iss', $item_id, $test_end, $test_start);
            $blockingStmt->execute();
            $blockingResult = $blockingStmt->get_result();
            
            if ($blockingResult->num_rows > 0) {
                echo "<h4>Bookings Blocking These Dates:</h4>";
                echo "<table>";
                echo "<tr><th>Ref Code</th><th>User</th><th>Status</th><th>Qty</th><th>Start</th><th>End</th></tr>";
                while ($blocking = $blockingResult->fetch_assoc()) {
                    echo "<tr class='" . strtolower($blocking['status']) . "'>";
                    echo "<td>" . htmlspecialchars($blocking['ref_code']) . "</td>";
                    echo "<td>" . htmlspecialchars($blocking['user_name']) . "</td>";
                    echo "<td>" . $blocking['status'] . "</td>";
                    echo "<td>" . $blocking['qty'] . "</td>";
                    echo "<td>" . $blocking['start_date'] . "</td>";
                    echo "<td>" . $blocking['end_date'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            $blockingStmt->close();
        }
    }
} else {
    echo "<h2>Select an Item to Debug</h2>";
    
    $itemsStmt = $conn->query("SELECT item_id, name, quantity FROM inventory_items ORDER BY name");
    echo "<ul>";
    while ($item = $itemsStmt->fetch_assoc()) {
        echo "<li><a href='?item_id=" . $item['item_id'] . "'>" . htmlspecialchars($item['name']) . " (Qty: " . $item['quantity'] . ")</a></li>";
    }
    echo "</ul>";
}
?>
