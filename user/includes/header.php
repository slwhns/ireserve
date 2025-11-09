<!-- Navigation Header -->
<nav>
    <div class="nav_logo">
        <img src="/ireserve/assets/images/logo.png" alt="iReserve Logo" style="height: 40px; vertical-align: middle; margin-right: 8px;">
        iReserve
    </div>
    <ul class="nav_links">
        <li class="link"><a href="/ireserve/user/user_page.php">Home</a></li>
        <li class="link"><a href="/ireserve/user/allItems.php">All Items</a></li>
        <li class="link"><a href="/ireserve/user/bookingForm.php">Book Now</a></li>
        <li class="link"><a href="/ireserve/user/about.php">About Us</a></li>
        <li class="link">
            <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle Dark Mode">
                <i class="ri-moon-line"></i>
            </button>
        </li>
        <li class="link user-profile-menu">
            <div class="profile-dropdown">
                <img src="<?php echo htmlspecialchars($user_picture); ?>" alt="Profile" class="profile-pic" id="profilePic">
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="dropdown-header">
                        <img src="<?php echo htmlspecialchars($user_picture); ?>" alt="Profile">
                        <div>
                            <h4><?php echo htmlspecialchars($user_name); ?></h4>
                            <p><?php echo htmlspecialchars($user_email); ?></p>
                        </div>
                    </div>
                    <a href="/ireserve/user/bookingsDetail.php" class="dropdown-item"><i class="ri-bookmark-line"></i> My Bookings</a>
                    <a href="/ireserve/user/settings.php" class="dropdown-item"><i class="ri-settings-line"></i> Settings</a>
                    <hr class="dropdown-divider">
                    <a href="/ireserve/logout.php" class="dropdown-item logout-item"><i class="ri-logout-box-line"></i> Logout</a>
                </div>
            </div>
        </li>
    </ul>
</nav>
