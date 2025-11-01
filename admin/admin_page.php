<?php

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

require_once __DIR__ . '/../config.php';

?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Page</title>
        <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=4">
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
                            <div class="numbers">150</div>
                            <div class="cardName">Users</div>
                        </div>
                        <div class="iconBx">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>

                    <div class="card">
                        <div>
                            <div class="numbers">300</div>
                            <div class="cardName">Approval</div>
                        </div>
                        <div class="iconBx">
                            <i class="bi bi-pen"></i>
                        </div>
                    </div>

                    <div class="card">
                        <div>
                            <div class="numbers">10</div>
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
                            <a href="#" class="btn">View All</a>
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
                                <tr>
                                    <td>John Doe</td>
                                    <td>Projector</td>
                                    <td>2024-06-15</td>
                                    <td><span class="status approved">Approved</span></td>
                                </tr>
                                <tr>
                                    <td>Jane Smith</td>
                                    <td>Laptop</td>
                                    <td>2024-06-16</td>
                                    <td><span class="status pending">Pending</span></td>
                                </tr>
                                <tr>
                                    <td>Mike Johnson</td>
                                    <td>Camera</td>
                                    <td>2024-06-17</td>
                                    <td><span class="status rejected">Declined</span></td>
                                </tr>
                                <tr>
                                    <td>Emily Davis</td>
                                    <td>Microphone</td>
                                    <td>2024-06-18</td>
                                    <td><span class="status approved">Approved</span></td>
                                </tr>
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
                                <tr>
                                    <td>Projector</td>
                                    <td>120</td>
                                </tr>
                                <tr>
                                    <td>Laptop</td>
                                    <td>95</td>
                                </tr>
                                <tr>
                                    <td>Camera</td>
                                    <td>80</td>
                                </tr>
                                <tr>
                                    <td>Microphone</td>
                                    <td>60</td>
                                </tr>
                            </tbody>
                    </div>
                </div>

            </div>
        </div>

    <script src="/ireserve/assets/js/admin.js"></script>
    </body>

</html>