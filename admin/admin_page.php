<?php

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

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
            <div class="navigation">
                <ul>

                    <li class="logo">
                        <a href="/ireserve/admin/admin_page.php">
                            <span class="icon">
                                <img class="brand-logo" src="/ireserve/assets/images/ireserve_logo.png" alt="iReserve logo">
                            </span>
                            <span class="title">iReserve</span>
                        </a>
                    </li>

                    <li>
                        <a href="/ireserve/admin/admin_page.php">
                            <span class="icon"><i class="bi bi-house"></i></span>
                            <span class="title">Dashboard</span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="/ireserve/admin/userList.php">
                            <span class="icon"><i class="bi bi-people"></i></span>
                            <span class="title">User</span>
                        </a>
                    </li>

                    <li>
                        <a class="active" href="/ireserve/admin/inventory.php">
                            <span class="icon"><i class="bi bi-backpack2"></i></span>
                            <span class="title">Inventory</span>
                        </a>
                    </li>

                    <li>
                        <a href="/ireserve/admin/approvals.php">
                            <span class="icon"><i class="bi bi-calendar2-check"></i></span>
                            <span class="title">Bookings</span>
                        </a>
                    </li>

                    <li>
                        <a href="/ireserve/admin/settings.php">
                            <span class="icon"><i class="bi bi-gear"></i></span>
                            <span class="title">Settings</span>
                        </a>
                    </li>

                    <li>
                        <a href="/ireserve/logout.php" class="logout">
                            <span class="icon"><i class="bi bi-box-arrow-right"></i></span>
                            <span class="title">Logout</span>
                        </a>
                    </li>

                </ul>
            </div>

            <!-- ============ Main Content ============ -->
            <div class="main">
                <div class="topbar">
                    <div class="toggle">
                        <i class="bi bi-list"></i>
                    </div>

                    <div class="search">
                        <label>
                            <input type="text" placeholder="Search here">
                            <button type="submit" class="icon-btn">
                                <i class="bi bi-search"></i>
                            </button>
                        </label>
                    </div>

                    <div class="user">
                        <img src="/ireserve/assets/images/img1.png" alt="User Image">
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