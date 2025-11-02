<?php

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../config.php';

// Helper function for escaping HTML
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

// Initialize counts
$totalUsers = 0;
$pendingApprovals = 0;
$totalAdmins = 0;

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

// Get pending approvals count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pendingApprovals = $row['count'];
    }
    $stmt->close();
}

// Get total admins count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $totalAdmins = $row['count'];
    }
    $stmt->close();
}

// Get recent bookings (last 10)
$recentBookings = [];
$stmt = $conn->prepare("
    SELECT 
        b.ref_code, b.start_at, b.status,
        u.name AS user_name,
        i.name AS item_name
    FROM bookings b
    LEFT JOIN users u ON u.id = b.user_id
    LEFT JOIN inventory_items i ON i.item_id = b.item_id
    ORDER BY b.created_at DESC
    LIMIT 10
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentBookings[] = $row;
    }
    $stmt->close();
}

// Get top booked items
$topItems = [];
$stmt = $conn->prepare("
    SELECT 
        i.name AS item_name,
        COUNT(*) as booking_count
    FROM bookings b
    LEFT JOIN inventory_items i ON i.item_id = b.item_id
    WHERE i.name IS NOT NULL
    GROUP BY b.item_id, i.name
    ORDER BY booking_count DESC
    LIMIT 5
");
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
    <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=6">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>

    <body>

        <!-- ============ Navigation Bar ============ -->
        <div class="container">
            <?php include __DIR__ . '/includes/sidebar.php'; ?>

            <!-- ============ Main Content ============ -->
            <div class="main">
                <div class="topbar">
                    <div class="toggle">
                        <i class="bi bi-list"></i>
                    </div>

                    <div class="search">
                        <label>
                            <input type="text" placeholder="Search here">
                            <i class="bi bi-search"></i>
                        </label>
                    </div>

                    <div class="user">
                        <?php
                        // Get current user's profile image
                        $userImg = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23666"%3E%3Cpath d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/%3E%3C/svg%3E';
                        if (isset($_SESSION['user_id']) && isset($conn) && $conn) {
                            $stmt = $conn->prepare('SELECT picture_url FROM users WHERE id = ? LIMIT 1');
                            if ($stmt) {
                                $stmt->bind_param('i', $_SESSION['user_id']);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                if ($res && $row = $res->fetch_assoc()) {
                                    if (!empty($row['picture_url'])) { $userImg = $row['picture_url']; }
                                }
                                $stmt->close();
                            }
                        }
                        ?>
                        <img src="<?= htmlspecialchars($userImg) ?>" alt="User Image" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23666%22%3E%3Cpath d=%22M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z%22/%3E%3C/svg%3E';">
                    </div>
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
                            <div class="numbers"><?= h($totalAdmins) ?></div>
                            <div class="cardName">Admin</div>
                        </div>
                        <div class="iconBx">
                            <i class="bi bi-person-badge"></i>
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
                        <div class="cardHeader">
                            <h2>Top Item Bookings</h2>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <td>Equipment</td>
                                    <td>Bookings</td>
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

            </div>
        </div>

    <script src="/ireserve/assets/js/admin.js"></script>
    </body>

</html>