<?php

require_once __DIR__ . '/../config.php'; // e.g. sets $conn = new mysqli(...);

// Fetch Admins
$admins = $conn->query("
    SELECT id, name, email
    FROM users
    WHERE role = 'admin'
");

// Fetch Users
$users = $conn->query("
    SELECT id, name, email
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
        <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=4">
        <link rel="stylesheet" href="/ireserve/assets/css/admin_userList.css?v=3">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    </head>
    <body>

        <div class="container">
            <!-- ====== Sidebar (navigation) ====== -->
            <div class="navigation">
                <ul>
                    <!-- Logo -->
                    <li class="logo">
                        <a href="/ireserve/admin/admin_page.php">
                            <span class="icon">
                                <img class="brand-logo" src="/ireserve/assets/images/ireserve_logo.png" alt="iReserve logo">
                            </span>
                            <span class="title">iReserve</span>
                        </a>
                    </li>

                    <li>
                        <a href="/ireserve/admin/admin_page.php">
                            <span class="icon"><i class="bi bi-speedometer2"></i></span>
                            <span class="title">Dashboard</span>
                        </a>
                    </li>

                    <li>
                        <a class="active" href="/ireserve/admin/userList.php">
                            <span class="icon"><i class="bi bi-people"></i></span>
                            <span class="title">User</span>
                        </a>
                    </li>

                    <li>
                        <a class="active" href="/ireserve/admin/inventory.php">
                            <span class="icon"><i class="bi bi-backpack2"></i></span>
                            <span class="title">Inventory</span>
                        </a>
                    </li>

                    <li>
                        <a href="/ireserve/admin/approvals.php">
                            <span class="icon"><i class="bi bi-clipboard-check"></i></span>
                            <span class="title">Approval</span>
                        </a>
                    </li>

                    <li>
                        <a href="/ireserve/admin/settings.php">
                            <span class="icon"><i class="bi bi-gear"></i></span>
                            <span class="title">Settings</span>
                        </a>
                    </li>

                    <li class="logout">
                        <a href="/ireserve/admin/logout.php">
                            <span class="icon"><i class="bi bi-box-arrow-right"></i></span>
                            <span class="title">Logout</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- ====== Main ====== -->
            <div class="main">
                <!-- Topbar (kept consistent with your current layout) -->
                <div class="topbar">
                    <div class="toggle">
                        <i class="bi bi-list"></i>
                    </div>
                    <div class="search">
                        <label>
                            <input type="text" placeholder="Search here">
                            <i class="bi bi-search"></i>
                        </label>
                    </div>
                    <div class="user">
                        <img src="/ireserve/assets/images/img1.png" alt="profile">
                    </div>
                </div>

                <!-- Two cards in a responsive grid: Admins & Users -->
                
                <div class="details">
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
                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td class="action">
                                                <a class="icon-btn" href="view-user.php?id=<?= (int)$row['id'] ?>" title="View"><i class="bi bi-eye"></i></a>
                                                <a class="icon-btn" href="edit-user.php?id=<?= (int)$row['id'] ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                <a class="icon-btn danger" href="delete-user.php?id=<?= (int)$row['id'] ?>" title="Delete" onclick="return confirm('Delete this admin?')"><i class="bi bi-trash3"></i></a>
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
                            <a href="/ireserve/admin/viewUser.php" class="btn">View More</a>
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
                                            <td><?= htmlspecialchars($row['name']) ?></td>
                                            <td><?= htmlspecialchars($row['email']) ?></td>
                                            <td class="action">
                                                <a class="icon-btn" href="view-user.php?id=<?= (int)$row['id'] ?>" title="View"><i class="bi bi-eye"></i></a>
                                                <a class="icon-btn" href="edit-user.php?id=<?= (int)$row['id'] ?>" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                <a class="icon-btn danger" href="delete-user.php?id=<?= (int)$row['id'] ?>" title="Delete" onclick="return confirm('Delete this admin?')"><i class="bi bi-trash3"></i></a>
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

        <script src="/ireserve/assets/js/admin.js"></script>
    </body>
</html>