<?php
session_name('ireserve_user');
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['email'])) {
    header("Location: /ireserve/index.php");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];

// Fetch user details including profile picture
$user_name = 'User';
$user_picture = '/ireserve/assets/images/user.png';

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT name, picture_url FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $user_name = $user_data['name'];
        $user_picture = !empty($user_data['picture_url']) ? $user_data['picture_url'] : $user_picture;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - iReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="/ireserve/assets/css/style.css">
    <link rel="stylesheet" href="/ireserve/assets/css/user_page.css">
    <link rel="stylesheet" href="/ireserve/assets/css/bookingsDetail.css">
    <link rel="stylesheet" href="/ireserve/assets/css/settings.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="bookings-container">
        <div class="back-button-container-top">
            <a href="/ireserve/user/user_page.php" class="btn-back">
                <i class="ri-arrow-left-line"></i> Back
            </a>
        </div>

        <div class="bookings-header">
            <h1><i class="ri-bookmark-line"></i> My Bookings</h1>
            <p>Track and manage all your equipment reservations</p>
        </div>

        <!-- Filter Tabs -->
        <div class="bookings-filters">
            <div class="filter-tab active" data-filter="all">
                <i class="ri-list-check"></i>
                All Bookings
            </div>
            <div class="filter-tab" data-filter="pending">
                <i class="ri-time-line"></i>
                Pending
            </div>
            <div class="filter-tab" data-filter="approved">
                <i class="ri-checkbox-circle-line"></i>
                Approved
            </div>
            <div class="filter-tab" data-filter="taken">
                <i class="ri-hand-coin-line"></i>
                Taken
            </div>
            <div class="filter-tab" data-filter="returned">
                <i class="ri-arrow-go-back-line"></i>
                Returned
            </div>
            <div class="filter-tab" data-filter="rejected">
                <i class="ri-close-circle-line"></i>
                Rejected
            </div>
        </div>

        <!-- Bookings Grid -->
        <div class="bookings-grid" id="bookingsGrid">
            <!-- Bookings will be loaded here by JavaScript -->
            <div class="loading-bookings">
                <div class="loading-spinner"></div>
                <p>Loading your bookings...</p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="/ireserve/assets/js/script.js"></script>
    <script src="/ireserve/assets/js/bookingsDetail.js"></script>
</body>
</html>
