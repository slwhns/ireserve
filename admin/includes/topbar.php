<?php
// Resolve current user's profile image from DB (fallback to default)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default avatar SVG (person icon)
$defaultUserImg = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23666"%3E%3Cpath d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/%3E%3C/svg%3E';
$defaultAdminImg = '/ireserve/assets/images/admin.png'; // Default admin profile picture
$userImage = $defaultUserImg;
$userName = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
$userRole = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

// Only query DB if we have user context
if (!empty($_SESSION['user_id']) || !empty($_SESSION['email'])) {
    // Attempt to access existing connection; include config if needed
    if (!isset($conn)) {
        $configPath = __DIR__ . '/../../config.php';
        if (file_exists($configPath)) {
            require_once $configPath;
        }
    }

    if (isset($conn) && $conn) {
        if (!empty($_SESSION['user_id'])) {
            $stmt = $conn->prepare('SELECT name, picture_url FROM users WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $_SESSION['user_id']);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    if (!empty($row['name'])) { $userName = $row['name']; }
                    if (!empty($row['picture_url'])) { 
                        $userImage = $row['picture_url']; 
                    } else {
                        // Use admin default if user is admin and no picture set
                        $userImage = ($userRole === 'admin') ? $defaultAdminImg : $defaultUserImg;
                    }
                    // DEBUG: Uncomment to see what's fetched
                    // error_log("Topbar: Fetched picture_url = " . ($row['picture_url'] ?? 'NULL') . " for user_id = " . $_SESSION['user_id']);
                }
                $stmt->close();
            }
        } elseif (!empty($_SESSION['email'])) {
            $stmt = $conn->prepare('SELECT name, picture_url FROM users WHERE email = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $_SESSION['email']);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    if (!empty($row['name'])) { $userName = $row['name']; }
                    if (!empty($row['picture_url'])) { 
                        $userImage = $row['picture_url']; 
                    } else {
                        // Use admin default if user is admin and no picture set
                        $userImage = ($userRole === 'admin') ? $defaultAdminImg : $defaultUserImg;
                    }
                }
                $stmt->close();
            }
        }
    }
}
?>
<!-- ============ Top Bar ============ -->
<div class="topbar">
    <div class="toggle">
        <i class="bi bi-list"></i>
    </div>

    <div class="topbar-right">
        <button id="darkModeToggle" class="dark-mode-toggle" title="Toggle Dark Mode">
            <i class="ri-moon-line"></i>
        </button>
        <div class="user">
            <img src="<?= htmlspecialchars($userImage) ?>" alt="<?= htmlspecialchars($userName) ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23666%22%3E%3Cpath d=%22M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z%22/%3E%3C/svg%3E';">
        </div>
    </div>
</div>