<?php
// =====================================================
// Page Configuration - Customize these for each page
// =====================================================
$pageTitle = 'Your Page Title';           // Shows in browser tab
$currentPage = 'dashboard';                 // Highlights active menu: dashboard, users, inventory, bookings, settings
$requireAdmin = true;                       // Set to true if only admins can access
$additionalCSS = '';                        // Add extra CSS files if needed
$additionalJS = '';                         // Add extra JS code if needed

// Example with additional CSS:
// $additionalCSS = '<link rel="stylesheet" href="/ireserve/assets/css/your-custom.css">';

// Example with additional JS:
// $additionalJS = '<script src="/ireserve/assets/js/your-script.js"></script>';

// =====================================================
// Include Required Components (Don't modify)
// =====================================================
require_once 'includes/auth.php';           // Authentication check
require_once 'includes/header.php';         // HTML head, opening tags
require_once 'includes/sidebar.php';        // Navigation sidebar
?>

<!-- =====================================================
     Main Content Area - Your page content goes here
     ===================================================== -->
<div class="main">
    <?php include 'includes/topbar.php'; ?>

    <!-- Your page-specific content starts here -->
    <div class="details">
        <div class="your-content-wrapper">
            <h2>Your Page Content</h2>
            
            <!-- Add your content here -->
            <p>This is where your page-specific content goes.</p>
            
        </div>
    </div>
    <!-- Your page-specific content ends here -->

</div>

<?php require_once 'includes/footer.php'; ?>
