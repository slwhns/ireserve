<?php
session_start();
echo "<h2>Current Session Variables:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Expected:</h2>";
echo "<ul>";
echo "<li>user_id: Should be set</li>";
echo "<li>name: " . (isset($_SESSION['name']) ? $_SESSION['name'] : 'NOT SET') . "</li>";
echo "<li>email: " . (isset($_SESSION['email']) ? $_SESSION['email'] : 'NOT SET') . "</li>";
echo "<li>role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . "</li>";
echo "</ul>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red; font-weight: bold;'>❌ user_id is NOT SET - Please log out and log in again!</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ user_id is set: " . $_SESSION['user_id'] . "</p>";
}

echo "<br><a href='admin_page.php'>Back to Dashboard</a>";
echo " | <a href='/ireserve/logout.php'>Logout</a>";
?>
