<?php
session_name('ireserve_user');
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['email'])) {
    header("Location: /ireserve/index.php");
    exit();
}

// Get user information from database
$user_email = $_SESSION['email'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

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
    <title>About Us - iReserve</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.4.0/fonts/remixicon.css" rel="stylesheet"/>
    <link rel="stylesheet" href="/ireserve/assets/css/style.css">
    <link rel="stylesheet" href="/ireserve/assets/css/user_page.css">
    <link rel="stylesheet" href="/ireserve/assets/css/about.css">
    <link rel="stylesheet" href="/ireserve/assets/css/settings.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="about-container">
        <div class="back-button-container-top">
            <a href="/ireserve/user/user_page.php" class="btn-back">
                <i class="ri-arrow-left-line"></i> Back
            </a>
        </div>

        <div class="about-hero">
            <h1><i class="ri-information-line"></i> About iReserve</h1>
            <p>Your trusted platform for easy and efficient inventory reservation</p>
        </div>

        <div class="about-section">
            <h2><i class="ri-building-line"></i> Who We Are</h2>
            <p>
                iReserve is a dedicated facility and equipment reservation system developed exclusively for Universiti Pendidikan Sultan Idris (UPSI).
                Our platform streamlines the process of booking and managing university-owned resources—such as equipment, tools, rooms, and facilities—ensuring
                that students, lecturers, and staff can access what they need efficiently and hassle-free.
            </p>
            <p>
                Our goal is to make resource management at UPSI more organized and accessible, so you can focus on what matters.
                With iReserve, getting what you need is just a few clicks away.
            </p>
        </div>

        <div class="about-section">
            <h2><i class="ri-star-line"></i> Key Features</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="ri-search-eye-line"></i>
                    <h3>Easy Search</h3>
                    <p>Find the items you need quickly with our powerful search and filter system.</p>
                </div>
                <div class="feature-card">
                    <i class="ri-calendar-check-line"></i>
                    <h3>Real-time Availability</h3>
                    <p>Check item availability in real-time and book instantly without conflicts.</p>
                </div>
                <div class="feature-card">
                    <i class="ri-bookmark-line"></i>
                    <h3>Simple Booking</h3>
                    <p>Reserve items with just a few clicks. Select dates, quantity, and submit.</p>
                </div>
                <div class="feature-card">
                    <i class="ri-shield-check-line"></i>
                    <h3>Admin Approval</h3>
                    <p>All bookings go through an approval process to ensure proper management.</p>
                </div>
                <div class="feature-card">
                    <i class="ri-notification-line"></i>
                    <h3>Track Status</h3>
                    <p>Monitor your booking status from pending to approved to returned.</p>
                </div>
                <div class="feature-card">
                    <i class="ri-user-smile-line"></i>
                    <h3>User Friendly</h3>
                    <p>Intuitive interface designed for ease of use and efficiency.</p>
                </div>
            </div>
        </div>

        <div class="about-section">
            <h2><i class="ri-lightbulb-line"></i> How It Works</h2>
            <p><strong>1. Browse:</strong> Explore available items through our catalog or use the search feature.</p>
            <p><strong>2. Book:</strong> Select your desired item, choose dates and quantity, then submit your booking request.</p>
            <p><strong>3. Approve:</strong> Wait for admin approval. You'll be notified of the decision.</p>
            <p><strong>4. Collect:</strong> Once approved and the start date arrives, pick up your reserved item.</p>
            <p><strong>5. Return:</strong> Return the item.</p>
        </div>

        <div class="contact-info">
            <h3><i class="ri-mail-line"></i> Get In Touch</h3>
            <div class="contact-item">
                <i class="ri-mail-send-line"></i>
                <p><strong>Email:</strong> support@ireserve.com</p>
            </div>
            <div class="contact-item">
                <i class="ri-phone-line"></i>
                <p><strong>Phone:</strong> +1 (555) 123-4567</p>
            </div>
            <div class="contact-item">
                <i class="ri-map-pin-line"></i>
                <p><strong>Address:</strong> 123 Reservation St, Booking City, BC 12345</p>
            </div>
            <div class="contact-item">
                <i class="ri-time-line"></i>
                <p><strong>Hours:</strong> Monday - Friday, 9:00 AM - 5:00 PM</p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="/ireserve/assets/js/script.js"></script>
</body>
</html>
