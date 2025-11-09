<?php

require_once __DIR__ . '/../config.php'; // e.g. sets $conn = new mysqli(...);

$currentPage = 'users';

// Pagination settings
$perPageAdmin = 10;
$perPageUser = 10;

// Pages from query params
$pageAdmin = isset($_GET['page_admin']) && is_numeric($_GET['page_admin']) ? (int)$_GET['page_admin'] : 1;
$pageUser  = isset($_GET['page_user']) && is_numeric($_GET['page_user']) ? (int)$_GET['page_user'] : 1;
if ($pageAdmin < 1) { $pageAdmin = 1; }
if ($pageUser < 1) { $pageUser = 1; }

$offsetAdmin = ($pageAdmin - 1) * $perPageAdmin;
$offsetUser  = ($pageUser  - 1) * $perPageUser;

// Total counts
$totalAdmins = 0; $totalUsers = 0;
if ($res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='admin'")) {
    $row = $res->fetch_assoc();
    $totalAdmins = isset($row['total']) ? (int)$row['total'] : 0;
}
if ($res = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='user'")) {
    $row = $res->fetch_assoc();
    $totalUsers = isset($row['total']) ? (int)$row['total'] : 0;
}

$totalPagesAdmin = max(1, (int)ceil($totalAdmins / $perPageAdmin));
$totalPagesUser  = max(1, (int)ceil($totalUsers / $perPageUser));
if ($pageAdmin > $totalPagesAdmin) { $pageAdmin = $totalPagesAdmin; $offsetAdmin = ($pageAdmin - 1) * $perPageAdmin; }
if ($pageUser > $totalPagesUser) { $pageUser = $totalPagesUser; $offsetUser = ($pageUser - 1) * $perPageUser; }

// Fetch Admins (paginated)
$stmtA = $conn->prepare("SELECT id, name, email, picture_url FROM users WHERE role='admin' ORDER BY id DESC LIMIT ? OFFSET ?");
if ($stmtA) {
    $stmtA->bind_param('ii', $perPageAdmin, $offsetAdmin);
    $stmtA->execute();
    $admins = $stmtA->get_result();
} else {
    $admins = false;
}

// Fetch Users (paginated)
$stmtU = $conn->prepare("SELECT id, name, email, picture_url FROM users WHERE role='user' ORDER BY id DESC LIMIT ? OFFSET ?");
if ($stmtU) {
    $stmtU->bind_param('ii', $perPageUser, $offsetUser);
    $stmtU->execute();
    $users = $stmtU->get_result();
} else {
    $users = false;
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin & User List | iReserve</title>
        <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=10">
        <link rel="stylesheet" href="/ireserve/assets/css/admin_userList.css?v=8">
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

                <!-- ====================== Page Actions ====================== -->
                <div class="pageActions">
                    <a href="/ireserve/admin/admin_page.php" class="btn-back-simple">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>

                <div class="details">
                    <?php
                    // Display success messages
                    if (isset($_GET['success'])) {
                        $success = $_GET['success'];
                        $successMessages = [
                            'user_updated' => 'User updated successfully!',
                            'user_deleted' => 'User deleted successfully!',
                            'admin_added' => 'Admin added successfully!'
                        ];
                        $message = $successMessages[$success] ?? 'Operation completed successfully.';
                        echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' . $message . '</div>';
                    }

                    // Display error messages
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        $errorMessages = [
                            'missing_id' => 'User ID is missing.',
                            'user_not_found' => 'User not found.',
                            'database_error' => 'Database error occurred.',
                            'delete_failed' => 'Failed to delete user.',
                            'cannot_delete_self' => 'You cannot delete yourself.',
                            'missing_fields' => 'Please fill in all required fields.',
                            'invalid_email' => 'Invalid email format.',
                            'email_exists' => 'Email already exists.',
                            'weak_password' => 'Password must be at least 6 characters.',
                            'prepare_failed' => 'System error. Please try again.'
                        ];
                        $message = $errorMessages[$error] ?? 'An error occurred. Please try again.';
                        echo '<div class="alert alert-error"><i class="bi bi-exclamation-circle"></i> ' . $message . '</div>';
                    }
                    ?>
                    
                    <div class="adminList">
                        <div class="cardHeader">
                            <h2>Admin List</h2>
                            <a href="/ireserve/admin/addAdmin.php" class="btn">Add Admin</a>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <td>No.</td>
                                    <td>Name</td>
                                    <td>Email</td>
                                    <td>Action</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($admins && $admins->num_rows): $i = $offsetAdmin + 1; ?>
                                    <?php while ($row = $admins->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td class="user-cell">
                                                <?php 
                                                $defaultAvatar = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23666"%3E%3Cpath d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/%3E%3C/svg%3E';
                                                $defaultAdminImg = '/ireserve/assets/images/admin.png';
                                                $img = !empty($row['picture_url']) ? $row['picture_url'] : $defaultAdminImg; 
                                                ?>
                                                <img class="avatar" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($row['name']) ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23666%22%3E%3Cpath d=%22M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z%22/%3E%3C/svg%3E';">
                                                <span><?= htmlspecialchars($row['name']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td class="action">
                                                <a class="icon-btn" href="/ireserve/admin/view-user.php?id=<?= (int)$row['id'] ?>" title="View"><i class="bi bi-eye"></i></a>
                                                <a class="icon-btn" href="/ireserve/admin/edit-user.php?id=<?= (int)$row['id'] ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                <a class="icon-btn danger" href="/ireserve/admin/delete-user.php?id=<?= (int)$row['id'] ?>" title="Delete" onclick="return confirm('Delete this admin?')"><i class="bi bi-trash3"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="empty">No admins found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if ($totalPagesAdmin > 1): ?>
                        <div class="pagination" style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;gap:12px;flex-wrap:wrap;">
                            <div class="pagination-info" style="color:#666;">
                                <?php $fromA = $totalAdmins ? ($offsetAdmin + 1) : 0; $toA = min($offsetAdmin + $perPageAdmin, $totalAdmins); ?>
                                Showing <?= $fromA ?>–<?= $toA ?> of <?= $totalAdmins ?>
                            </div>
                            <div class="pagination-links" style="display:flex;gap:6px;align-items:center;">
                                <?php if ($pageAdmin > 1): ?>
                                    <a class="page-link prev" href="?page_admin=<?= $pageAdmin - 1 ?>&page_user=<?= $pageUser ?>" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php
                                $window = 2; $start = max(1, $pageAdmin - $window); $end = min($totalPagesAdmin, $pageAdmin + $window);
                                if ($start > 1) {
                                    echo '<a class="page-link" href="?page_admin=1&page_user=' . $pageUser . '" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">1</a>';
                                    if ($start > 2) echo '<span class="ellipsis" style="padding:0 4px;color:#999;">…</span>';
                                }
                                for ($p = $start; $p <= $end; $p++) {
                                    if ($p == $pageAdmin) {
                                        echo '<span class="page-link active" style="padding:6px 10px;border:1px solid #4c74f5;background:#4c74f5;color:#fff;border-radius:6px;">' . $p . '</span>';
                                    } else {
                                        echo '<a class="page-link" href="?page_admin=' . $p . '&page_user=' . $pageUser . '" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">' . $p . '</a>';
                                    }
                                }
                                if ($end < $totalPagesAdmin) {
                                    if ($end < $totalPagesAdmin - 1) echo '<span class="ellipsis" style="padding:0 4px;color:#999;">…</span>';
                                    echo '<a class="page-link" href="?page_admin=' . $totalPagesAdmin . '&page_user=' . $pageUser . '" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">' . $totalPagesAdmin . '</a>';
                                }
                                ?>
                                <?php if ($pageAdmin < $totalPagesAdmin): ?>
                                    <a class="page-link next" href="?page_admin=<?= $pageAdmin + 1 ?>&page_user=<?= $pageUser ?>" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="userList">
                        <div class="cardHeader">
                            <h2>User List</h2>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <td>No.</td>
                                    <td>Name</td>
                                    <td>Email</td>
                                    <td>Action</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users && $users->num_rows): $i = $offsetUser + 1; ?>
                                    <?php while ($row = $users->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td class="user-cell">
                                                <?php 
                                                $defaultAvatar = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23666"%3E%3Cpath d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/%3E%3C/svg%3E';
                                                $img = !empty($row['picture_url']) ? $row['picture_url'] : $defaultAvatar; 
                                                ?>
                                                <img class="avatar" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($row['name']) ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23666%22%3E%3Cpath d=%22M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z%22/%3E%3C/svg%3E';">
                                                <span><?= htmlspecialchars($row['name']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td class="action">
                                                <a class="icon-btn" href="/ireserve/admin/view-user.php?id=<?= (int)$row['id'] ?>" title="View"><i class="bi bi-eye"></i></a>
                                                <a class="icon-btn" href="/ireserve/admin/edit-user.php?id=<?= (int)$row['id'] ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                <a class="icon-btn danger" href="/ireserve/admin/delete-user.php?id=<?= (int)$row['id'] ?>" title="Delete" onclick="return confirm('Delete this user?')"><i class="bi bi-trash3"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="empty">No users found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if ($totalPagesUser > 1): ?>
                        <div class="pagination" style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;gap:12px;flex-wrap:wrap;">
                            <div class="pagination-info" style="color:#666;">
                                <?php $fromU = $totalUsers ? ($offsetUser + 1) : 0; $toU = min($offsetUser + $perPageUser, $totalUsers); ?>
                                Showing <?= $fromU ?>–<?= $toU ?> of <?= $totalUsers ?>
                            </div>
                            <div class="pagination-links" style="display:flex;gap:6px;align-items:center;">
                                <?php if ($pageUser > 1): ?>
                                    <a class="page-link prev" href="?page_admin=<?= $pageAdmin ?>&page_user=<?= $pageUser - 1 ?>" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">&laquo; Prev</a>
                                <?php endif; ?>
                                <?php
                                $window = 2; $start = max(1, $pageUser - $window); $end = min($totalPagesUser, $pageUser + $window);
                                if ($start > 1) {
                                    echo '<a class="page-link" href="?page_admin=' . $pageAdmin . '&page_user=1" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">1</a>';
                                    if ($start > 2) echo '<span class="ellipsis" style="padding:0 4px;color:#999;">…</span>';
                                }
                                for ($p = $start; $p <= $end; $p++) {
                                    if ($p == $pageUser) {
                                        echo '<span class="page-link active" style="padding:6px 10px;border:1px solid #4c74f5;background:#4c74f5;color:#fff;border-radius:6px;">' . $p . '</span>';
                                    } else {
                                        echo '<a class="page-link" href="?page_admin=' . $pageAdmin . '&page_user=' . $p . '" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">' . $p . '</a>';
                                    }
                                }
                                if ($end < $totalPagesUser) {
                                    if ($end < $totalPagesUser - 1) echo '<span class="ellipsis" style="padding:0 4px;color:#999;">…</span>';
                                    echo '<a class="page-link" href="?page_admin=' . $pageAdmin . '&page_user=' . $totalPagesUser . '" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">' . $totalPagesUser . '</a>';
                                }
                                ?>
                                <?php if ($pageUser < $totalPagesUser): ?>
                                    <a class="page-link next" href="?page_admin=<?= $pageAdmin ?>&page_user=<?= $pageUser + 1 ?>" style="padding:6px 10px;border:1px solid #ddd;border-radius:6px;text-decoration:none;color:#333;">Next &raquo;</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div> <!-- /main -->
        </div> <!-- /container -->

        <!-- Feedback Modal (matches inventory modal styles) -->
        <div id="feedbackModal" class="modal" aria-hidden="true">
            <div class="modal-content modal-alert">
                <div class="modal-header">
                    <h3><i class="bi bi-info-circle"></i> Notification</h3>
                    <span class="close" onclick="closeFeedbackModal()" aria-label="Close">&times;</span>
                </div>
                <div class="modal-body">
                    <p id="feedbackMessage"></p>
                </div>
            </div>
        </div>

        <script src="/ireserve/assets/js/admin.js?v=8"></script>
        <script>
            function openFeedbackModal(message, type) {
                const modal = document.getElementById('feedbackModal');
                const content = modal.querySelector('.modal-content');
                const msg = document.getElementById('feedbackMessage');
                content.classList.remove('success', 'error');
                if (type) content.classList.add(type);
                msg.textContent = message;
                modal.style.display = 'flex';
                // Auto-close after 5 seconds
                setTimeout(closeFeedbackModal, 5000);
            }

            function closeFeedbackModal() {
                const modal = document.getElementById('feedbackModal');
                if (modal) modal.style.display = 'none';
            }

            // Convert inline alerts to popup modal (consistent UX)
            document.addEventListener('DOMContentLoaded', function () {
                const alertEl = document.querySelector('.alert');
                if (alertEl) {
                    const isSuccess = alertEl.classList.contains('alert-success');
                    const type = isSuccess ? 'success' : 'error';
                    const text = alertEl.textContent.trim();
                    // Hide inline alert and show modal instead
                    alertEl.style.display = 'none';
                    openFeedbackModal(text, type);
                }
            });
        </script>
    </body>
</html>