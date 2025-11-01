<?php

require_once __DIR__ . '/../config.php'; // e.g. sets $conn = new mysqli(...);

// Fetch Admins
$admins = $conn->query("
    SELECT id, name, email, picture_url
    FROM users
    WHERE role = 'admin'
");

// Fetch Users
$users = $conn->query("
    SELECT id, name, email, picture_url
    FROM users
    WHERE role = 'user'
");

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>iReserve · Admin & User List</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=5">
    <link rel="stylesheet" href="/ireserve/assets/css/admin_userList.css?v=5">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body>

        <div class="container">
            <!-- ====== Sidebar (navigation) ====== -->
            <?php include __DIR__ . '/includes/sidebar.php'; ?>

            <!-- ====== Main ====== -->
            <div class="main">
                <!-- Topbar include (dynamic user image) -->
                <?php include __DIR__ . '/includes/topbar.php'; ?>

                <!-- Two cards in a responsive grid: Admins & Users -->
                
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
                                <?php if ($admins && $admins->num_rows): $i=1; ?>
                                    <?php while ($row = $admins->fetch_assoc()): ?>
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
                                                <a class="icon-btn" href="view-user.php?id=<?= (int)$row['id'] ?>" title="View"><i class="bi bi-eye"></i></a>
                                                <a class="icon-btn" href="edit-user.php?id=<?= (int)$row['id'] ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                <a class="icon-btn danger" href="delete-user.php?id=<?= (int)$row['id'] ?>" title="Delete" onclick="return confirm('Delete this user?')"><i class="bi bi-trash3"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="empty">No admins found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
                                <?php if ($users && $users->num_rows): $i=1; ?>
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
                                                <a class="icon-btn" href="view-user.php?id=<?= (int)$row['id'] ?>" title="View"><i class="bi bi-eye"></i></a>
                                                <a class="icon-btn" href="edit-user.php?id=<?= (int)$row['id'] ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                <a class="icon-btn danger" href="delete-user.php?id=<?= (int)$row['id'] ?>" title="Delete" onclick="return confirm('Delete this user?')"><i class="bi bi-trash3"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="empty">No users found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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

        <script src="/ireserve/assets/js/admin.js"></script>
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