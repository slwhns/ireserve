
<!-- ============ Navigation Sidebar ============ -->
<div class="navigation">
    <ul>
        <li class="logo">
            <a href="/ireserve/admin/admin_page.php">
                <span class="icon">
                    <img class="brand-logo" src="/ireserve/assets/images/logo.png" alt="iReserve logo">
                </span>
                <span class="title">iReserve</span>
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'dashboard') ? 'hovered' : ''; ?>">
            <a href="/ireserve/admin/admin_page.php">
                <span class="icon"><i class="bi bi-house"></i></span>
                <span class="title">Dashboard</span>
            </a>
        </li>
        
        <li class="<?php echo ($currentPage == 'users') ? 'hovered' : ''; ?>">
            <a href="/ireserve/admin/userList.php">
                <span class="icon"><i class="bi bi-people"></i></span>
                <span class="title">User</span>
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'inventory') ? 'hovered' : ''; ?>">
            <a href="/ireserve/admin/inventory.php">
                <span class="icon"><i class="bi bi-backpack2"></i></span>
                <span class="title">Inventory</span>
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'bookings') ? 'hovered' : ''; ?>">
            <a href="/ireserve/admin/bookings.php">
                <span class="icon"><i class="bi bi-calendar2-check"></i></span>
                <span class="title">Bookings</span>
            </a>
        </li>

        <li class="<?php echo ($currentPage == 'settings') ? 'hovered' : ''; ?>">
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
