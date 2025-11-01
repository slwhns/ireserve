<?php
session_start();
require_once '../config.php';

echo "<h2>Diagnostics for User Profile Picture</h2>";

echo "<h3>Session Info:</h3>";
echo "<pre>";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "name: " . ($_SESSION['name'] ?? 'NOT SET') . "\n";
echo "email: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";
echo "role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<h3>Database Query Result:</h3>";
    $stmt = $conn->prepare('SELECT id, name, email, picture_url FROM users WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            echo "<pre>";
            print_r($row);
            echo "</pre>";
            
            echo "<h3>Picture URL Status:</h3>";
            if (empty($row['picture_url'])) {
                echo "<p style='color: red;'>❌ picture_url is EMPTY or NULL in database!</p>";
                echo "<p>You need to upload a profile picture for this user.</p>";
                echo "<p><a href='edit-user.php?id=" . $_SESSION['user_id'] . "'>Edit Your Profile</a></p>";
            } else {
                echo "<p style='color: green;'>✅ picture_url: " . htmlspecialchars($row['picture_url']) . "</p>";
                echo "<p>Image preview:</p>";
                echo "<img src='" . htmlspecialchars($row['picture_url']) . "' style='max-width: 200px; border: 1px solid #ddd; border-radius: 8px;'>";
            }
        }
        $stmt->close();
    }
}

echo "<br><br><a href='admin_page.php'>Back to Dashboard</a>";
?>
