<?php
session_name('ireserve_admin');
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ireserve/login_register.php");
    exit();
}

require_once '../config.php';

// Check if user_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: userList.php?error=missing_id");
    exit();
}

$user_id = (int)$_GET['id'];

// Fetch user details
$sql = "SELECT id, name, email, phone_number, matrics_number, course, faculty, role, picture_url FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        header("Location: userList.php?error=user_not_found");
        exit();
    }
    $stmt->close();
} else {
    header("Location: userList.php?error=database_error");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User | iReserve</title>
    <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=10">
    <link rel="stylesheet" href="/ireserve/assets/css/inventory_form.css">
    <link rel="stylesheet" href="/ireserve/assets/css/admin_userList.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <!-- ============ Navigation Bar ============ -->
    <div class="container">
        <!-- ============ Sidebar ============ -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- ============ Main Content ============ -->
        <div class="main">
            <!-- ============ Top Bar ============ -->
            <?php include __DIR__ . '/includes/topbar.php'; ?>

            <!-- ====================== Form Content ====================== -->
            <div class="form-container">
                <div class="form-card">
                    <div class="form-header">
                        <h2><i class="bi bi-person-circle"></i> User Details</h2>
                        <a href="/ireserve/admin/userList.php" class="btn-back">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>

                    <div class="user-details-view">
                        <div class="detail-row">
                            <label><i class="bi bi-image"></i> Profile Image:</label>
                            <?php $uimg = !empty($user['picture_url']) ? $user['picture_url'] : '/ireserve/assets/images/img1.png'; ?>
                            <span>
                                <img src="<?php echo htmlspecialchars($uimg); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:1px solid rgba(0,0,0,0.1);" />
                            </span>
                        </div>

                        <div class="detail-row">
                            <label><i class="bi bi-person"></i> Name:</label>
                            <span><?php echo htmlspecialchars($user['name']); ?></span>
                        </div>

                        <div class="detail-row">
                            <label><i class="bi bi-envelope"></i> Email:</label>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>

                        <div class="detail-row">
                            <label><i class="bi bi-telephone"></i> Phone Number:</label>
                            <span><?php echo htmlspecialchars($user['phone_number'] ?? '—'); ?></span>
                        </div>

                        <div class="detail-row">
                            <label><i class="bi bi-card-list"></i> Matrics Number:</label>
                            <span><?php echo htmlspecialchars($user['matrics_number'] ?? '—'); ?></span>
                        </div>

                        <div class="detail-row">
                            <label><i class="bi bi-journal-bookmark"></i> Course:</label>
                            <span><?php echo htmlspecialchars($user['course'] ?? '—'); ?></span>
                        </div>

                        <div class="detail-row">
                            <label><i class="bi bi-building"></i> Faculty:</label>
                            <span><?php echo htmlspecialchars($user['faculty'] ?? '—'); ?></span>
                        </div>

                        <div class="detail-row">
                            <label><i class="bi bi-shield-check"></i> Role:</label>
                            <span class="badge badge-<?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </div>

                    </div>

                    <div class="form-actions">
                        <a href="/ireserve/admin/edit-user.php?id=<?php echo $user['id']; ?>" class="btn-submit">
                            <i class="bi bi-pencil-square"></i> Edit User
                        </a>
                        <a href="/ireserve/admin/userList.php" class="btn-cancel">
                            <i class="bi bi-x-circle"></i> Close
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/ireserve/assets/js/admin.js"></script>
</body>
</html>
