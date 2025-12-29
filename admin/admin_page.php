<?php
session_name('ireserve_admin');
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: /ireserve/index.php");
    exit();
}

require_once __DIR__ . '/../config.php';

$currentPage = 'dashboard';

// Helper function for escaping HTML
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Initialize counts
$totalUsers = 0;
$pendingApprovals = 0;
$totalAdmins = 0;

// Get admin's faculty ID
$adminFacultyId = $_SESSION['faculty_id'] ?? null;

// Get total users count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $totalUsers = $row['count'];
    }
    $stmt->close();
}

// Get pending approvals count - filter by faculty
if ($adminFacultyId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings b 
                            JOIN inventory_items i ON i.item_id = b.item_id 
                            WHERE b.status = 'pending' AND i.faculty_id = ?");
    $stmt->bind_param('i', $adminFacultyId);
} else {
    $stmt = $conn->prepare("SELECT 0 as count");
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pendingApprovals = $row['count'];
    }
    $stmt->close();
}

// Get total bookings count - filter by faculty
if ($adminFacultyId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings b 
                            JOIN inventory_items i ON i.item_id = b.item_id 
                            WHERE b.status IN ('approved', 'returned') AND i.faculty_id = ?");
    $stmt->bind_param('i', $adminFacultyId);
} else {
    $stmt = $conn->prepare("SELECT 0 as count");
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $totalBookings = $row['count'];
    }
    $stmt->close();
}

// Get recent bookings (last 7) - filter by faculty
$recentBookings = [];
if ($adminFacultyId) {
    $stmt = $conn->prepare("
        SELECT 
            b.ref_code, b.start_at, b.status,
            u.name AS user_name,
            i.name AS item_name
        FROM bookings b
        LEFT JOIN users u ON u.id = b.user_id
        LEFT JOIN inventory_items i ON i.item_id = b.item_id
        WHERE i.faculty_id = ?
        ORDER BY b.created_at DESC
        LIMIT 7
    ");
    $stmt->bind_param('i', $adminFacultyId);
} else {
    $stmt = null;
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentBookings[] = $row;
    }
    $stmt->close();
}

// Get top booked items - filter by faculty
$topItems = [];
if ($adminFacultyId) {
    $stmt = $conn->prepare("
        SELECT 
            i.name AS item_name,
            COUNT(*) as booking_count
        FROM bookings b
        LEFT JOIN inventory_items i ON i.item_id = b.item_id
        WHERE i.name IS NOT NULL AND i.faculty_id = ?
        GROUP BY b.item_id, i.name
        ORDER BY booking_count DESC
        LIMIT 5
    ");
    $stmt->bind_param('i', $adminFacultyId);
} else {
    $stmt = null;
}
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $topItems[] = $row;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Page | iReserve</title>
        <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=12">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>

    <body>

        <!-- ============ Navigation Bar ============ -->
        <div class="container">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>

            <!-- ============ Main Content ============ -->
            <div class="main">
                <?php include __DIR__ . '/includes/topbar.php'; ?>

                <!-- ====================== Welcome Message ================== -->
                <div class="welcome-message">
                    <h1>Welcome, <?= h($_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin') ?>!</h1>
                    <p>Here's an overview of iReserve system</p>
                </div>

                <!-- ====================== Cards ================== -->  
                <div class="cardBox">
                    <div class="card">
                        <div>
                            <div class="numbers"><?= h($totalUsers) ?></div>
                            <div class="cardName">Users</div>
                        </div>
                        <div class="iconBx">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>

                    <div class="card">
                        <div>
                            <div class="numbers"><?= h($pendingApprovals) ?></div>
                            <div class="cardName">Pending Approval</div>
                        </div>
                        <div class="iconBx">
                            <i class="bi bi-pen"></i>
                        </div>
                    </div>

                    <div class="card">
                        <div>
                            <div class="numbers"><?= h($totalBookings) ?></div>
                            <div class="cardName">Total Bookings</div>
                        </div>
                        <div class="iconBx">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>

                <!-- ====================== Booking Details List ====================== -->
                <div class="details">
                    <div class="recentBookings">
                        <div class="cardHeader">
                            <h2>Recent Bookings</h2>
                            <a href="bookings.php" class="btn">View All</a>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <td>Name</td>
                                    <td>Equipment</td>
                                    <td>Date</td>
                                    <td>Status</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 20px; color: #999;">No bookings yet</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recentBookings as $booking): ?>
                                    <tr>
                                        <td><?= h($booking['user_name'] ?? 'Unknown') ?></td>
                                        <td><?= h($booking['item_name'] ?? 'Unknown') ?></td>
                                        <td><?= h(date('Y-m-d', strtotime($booking['start_at']))) ?></td>
                                        <td><span class="status <?= h($booking['status']) ?>"><?= h(ucfirst($booking['status'])) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- ====================== Top Item Booked ====================== -->
                    <div class="topBookings">
                        <table>
                            <thead>
                                <tr>
                                    <td>Top Item Bookings</td>
                                    <td>Total</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topItems)): ?>
                                <tr>
                                    <td colspan="2" style="text-align: center; padding: 20px; color: #999;">No bookings yet</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($topItems as $item): ?>
                                    <tr>
                                        <td><?= h($item['item_name']) ?></td>
                                        <td><?= h($item['booking_count']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

    <script src="/ireserve/assets/js/admin.js?v=9"></script>
    
    </body>

</html>