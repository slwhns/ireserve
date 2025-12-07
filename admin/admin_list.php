<?php
/**
 * Admin List Page
 * 
 * Displays all administrators for the current admin's faculty
 * with options to create invites, edit, and deactivate admins.
 * 
 * Security Features:
 * - Faculty-scoped access (admins only see their faculty)
 * - Session-based authentication
 * - CSRF tokens for actions
 * - Last-admin protection (cannot deactivate if only one active)
 */

session_name('ireserve_admin');
session_start();

// Include required files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

// Check authentication - must be logged-in admin
$admin = current_admin();
if (!$admin) {
    header("Location: /ireserve/index.php");
    exit();
}

// Verify admin has faculty assigned
if (empty($admin['faculty_id'])) {
    die("Error: Your admin account is not associated with a faculty. Please contact system administrator.");
}

// Get faculty name
$faculty_name = get_faculty_name($admin['faculty_id']);

// Count active admins for last-admin check
$active_admin_count = count_active_admins($admin['faculty_id']);

// Fetch all admins for current faculty using prepared statement
$stmt = $conn->prepare("
    SELECT 
        u.id, 
        u.name, 
        u.email, 
        u.is_active, 
        u.last_login, 
        u.created_at,
        (SELECT COUNT(*) FROM admin_invites WHERE created_by = u.id) as invites_sent
    FROM users u
    WHERE u.role = 'admin' 
    AND u.faculty_id = ?
    ORDER BY u.is_active DESC, u.created_at DESC
");

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $admin['faculty_id']);
$stmt->execute();
$result = $stmt->get_result();
$admins = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch pending invites for current faculty
$stmt = $conn->prepare("
    SELECT 
        ai.token,
        ai.email,
        ai.created_at,
        creator.name as invited_by
    FROM admin_invites ai
    JOIN users creator ON ai.created_by = creator.id
    WHERE ai.faculty_id = ?
    AND ai.used = 0
    ORDER BY ai.created_at DESC
");

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $admin['faculty_id']);
$stmt->execute();
$result = $stmt->get_result();
$pending_invites = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management | iReserve</title>
    <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=10">
    <link rel="stylesheet" href="/ireserve/assets/css/admin_userList.css?v=10">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 0 35px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 25px;
            color: white;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-icon {
            font-size: 48px;
            opacity: 0.9;
        }
        .stat-info h3 {
            font-size: 36px;
            margin: 0;
            font-weight: 700;
        }
        .stat-info p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Admin table adjustments */
        .adminList table td:nth-child(1) { width: auto; }
        .adminList table td:nth-child(2) { width: auto; }
        .adminList table td:nth-child(3) { width: 100px; text-align: center; }
        .adminList table td:nth-child(4) { width: 140px; }
        .adminList table td:nth-child(5) { width: 90px; text-align: center; }
        .adminList table td:nth-child(6) { width: 110px; }
        .adminList table td:nth-child(7) { width: 120px; text-align: center; }
        
        .badge-small {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-you {
            background: #d1ecf1;
            color: #0c5460;
            margin-left: 8px;
        }
        
        .btn-deactivate {
            padding: 5px 12px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-deactivate:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        
        .text-muted-small {
            color: #6c757d;
            font-size: 12px;
        }
        
        .inactive-row {
            opacity: 0.6;
            background: #f8f9fa;
        }
        
        /* Pending invites section */
        .invitesList {
            position: relative;
            display: block;
            background: var(--white);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 7px 25px rgba(0, 0, 0, 0.08);
        }
        
        .copy-link-input {
            width: 100%;
            padding: 8px 12px;
            background: #f8f9fa;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #495057;
        }
    </style>
</head>
<body>
    <!-- ============ Navigation Bar ============ -->
    <div class="container">
        <!-- ============ Sidebar ============ -->
        <?php 
        $currentPage = 'admin';
        include __DIR__ . '/includes/sidebar.php'; 
        ?>

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

            <!-- ====================== Main Content ====================== -->
            <div class="details">
                <?php
                // Don't display inline alerts - we'll show modal instead
                $hasMessage = false;
                $messageText = '';
                $messageType = '';
                
                if (isset($_GET['success'])) {
                    $hasMessage = true;
                    $messageText = urldecode($_GET['success']);
                    $messageType = 'success';
                }
                if (isset($_GET['error'])) {
                    $hasMessage = true;
                    $messageText = urldecode($_GET['error']);
                    $messageType = 'error';
                }
                ?>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-info">
                        <h3><?php echo $active_admin_count; ?></h3>
                        <p>Active Admins</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📨</div>
                    <div class="stat-info">
                        <h3><?php echo count($pending_invites); ?></h3>
                        <p>Pending Invites</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <h3><?php echo count($admins); ?></h3>
                        <p>Total Admins</p>
                    </div>
                </div>
            </div>

            <div class="details">
                <!-- Active Admins Table -->
                <div class="adminList">
                    <div class="cardHeader">
                        <h2>Administrator List - <?php echo htmlspecialchars($faculty_name); ?></h2>
                        <a href="admin_create_invite.php" class="btn">
                            <i class="bi bi-plus-circle"></i> Create Invite
                        </a>
                    </div>

                    <?php if (count($admins) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <td>Name</td>
                                    <td>Email</td>
                                    <td>Status</td>
                                    <td>Last Login</td>
                                    <td>Invites</td>
                                    <td>Joined</td>
                                    <td>Action</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin_user): ?>
                                    <tr class="<?php echo $admin_user['is_active'] ? '' : 'inactive-row'; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($admin_user['name']); ?></strong>
                                            <?php if ($admin_user['id'] == $admin['id']): ?>
                                                <span class="badge-small badge-you">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($admin_user['email']); ?></td>
                                        <td>
                                            <?php if ($admin_user['is_active']): ?>
                                                <span class="badge-small badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge-small badge-inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($admin_user['last_login']) {
                                                echo date('M d, Y H:i', strtotime($admin_user['last_login']));
                                            } else {
                                                echo '<span class="text-muted-small">Never</span>';
                                            }
                                            ?>
                                        </td>
                                        <td style="text-align: center;"><?php echo $admin_user['invites_sent']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($admin_user['created_at'])); ?></td>
                                        <td style="text-align: center;">
                                            <?php if ($admin_user['is_active']): ?>
                                                <?php if ($admin_user['id'] == $admin['id']): ?>
                                                    <span class="text-muted-small" title="Cannot deactivate yourself">
                                                        <i class="bi bi-lock"></i> Self
                                                    </span>
                                                <?php elseif ($active_admin_count <= 1): ?>
                                                    <span class="text-muted-small" title="Cannot deactivate the last admin">
                                                        <i class="bi bi-lock"></i> Last
                                                    </span>
                                                <?php else: ?>
                                                    <button 
                                                        onclick="deactivateAdmin(<?php echo $admin_user['id']; ?>, '<?php echo htmlspecialchars($admin_user['name'], ENT_QUOTES); ?>')"
                                                        class="btn-deactivate"
                                                        title="Deactivate admin">
                                                        <i class="bi bi-x-circle"></i> Deactivate
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge-small badge-inactive">Deactivated</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted-small" style="text-align: center; padding: 40px;">No administrators found for your faculty.</p>
                    <?php endif; ?>
                </div>

                <!-- Pending Invites -->
                <?php if (count($pending_invites) > 0): ?>
                    <div class="invitesList" style="margin-top: 20px;">
                        <div class="cardHeader">
                            <h2><i class="bi bi-hourglass-split"></i> Pending Invitations</h2>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <td style="width: 30%;">Email</td>
                                    <td style="width: 20%;">Invited By</td>
                                    <td style="width: 18%;">Sent Date</td>
                                    <td style="width: 37%;">Registration Link</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_invites as $invite): ?>
                                    <tr>
                                        <td style="width: 30%; word-break: break-word; overflow-wrap: break-word;">
                                            <?php echo htmlspecialchars($invite['email']); ?>
                                        </td>
                                        <td style="width: 20%; word-break: break-word;"><?php echo htmlspecialchars($invite['invited_by']); ?></td>
                                        <td style="width: 18%; white-space: nowrap;"><?php echo date('M d, Y H:i', strtotime($invite['created_at'])); ?></td>
                                        <td style="width: 37%;">
                                            <div style="display: flex; gap: 8px; align-items: center; min-width: 0;">
                                                <input 
                                                    type="text" 
                                                    class="copy-link-input" 
                                                    readonly
                                                    value="<?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/ireserve/admin/admin_register.php?token=' . $invite['token']); ?>"
                                                    style="flex: 1; min-width: 0; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 12px; font-family: monospace; background: #f9fafb; color: #374151; overflow: hidden; text-overflow: ellipsis;"
                                                    onclick="this.select();">
                                                <button 
                                                    onclick="copyInviteLink(this)" 
                                                    style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; white-space: nowrap; transition: background 0.2s; flex-shrink: 0;"
                                                    onmouseover="this.style.background='#1d4ed8'"
                                                    onmouseout="this.style.background='#2563eb'">
                                                    <i class="bi bi-clipboard"></i> Copy
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div> <!-- /details -->
        </div> <!-- /main -->
    </div> <!-- /container -->

    <!-- Notification Modal -->
    <div id="notificationModal" class="modal" style="display: none;">
        <div class="modal-content modal-alert">
            <div class="modal-header">
                <h3 id="modalTitle"><i class="bi bi-info-circle"></i> Notification</h3>
            </div>
            <div class="modal-body">
                <p id="modalMessage"></p>
            </div>
            <div class="modal-footer" style="padding: 15px 30px; text-align: right; border-top: 1px solid #dee2e6;">
                <button onclick="closeNotificationModal()" class="btn" style="background: var(--blue); color: white; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    OK
                </button>
            </div>
        </div>
    </div>

    <script src="/ireserve/assets/js/admin.js?v=8"></script>
    <script>
        // Copy invite link function
        function copyInviteLink(button) {
            const input = button.previousElementSibling;
            input.select();
            input.setSelectionRange(0, 99999); // For mobile
            
            try {
                document.execCommand('copy');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check"></i> Copied!';
                button.style.background = '#10b981';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '#2563eb';
                }, 2000);
            } catch (err) {
                alert('Failed to copy link. Please copy manually.');
            }
        }
        
        // Show notification modal if there's a message
        <?php if ($hasMessage): ?>
        window.addEventListener('DOMContentLoaded', function() {
            showNotificationModal('<?php echo addslashes($messageText); ?>', '<?php echo $messageType; ?>');
        });
        <?php endif; ?>

        function showNotificationModal(message, type) {
            const modal = document.getElementById('notificationModal');
            const content = modal.querySelector('.modal-content');
            const title = document.getElementById('modalTitle');
            const messageEl = document.getElementById('modalMessage');
            
            // Reset classes
            content.classList.remove('success', 'error');
            
            // Set type-specific styling
            if (type === 'success') {
                content.classList.add('success');
                title.innerHTML = '<i class="bi bi-check-circle"></i> Success';
            } else if (type === 'error') {
                content.classList.add('error');
                title.innerHTML = '<i class="bi bi-exclamation-circle"></i> Error';
            }
            
            messageEl.textContent = message;
            modal.style.display = 'flex';
            
            // Remove URL parameters after showing modal
            if (window.history.replaceState) {
                const url = window.location.pathname;
                window.history.replaceState({}, document.title, url);
            }
        }

        function closeNotificationModal() {
            const modal = document.getElementById('notificationModal');
            modal.style.display = 'none';
        }

        // Close modal on background click
        window.onclick = function(event) {
            const modal = document.getElementById('notificationModal');
            if (event.target === modal) {
                closeNotificationModal();
            }
        }

        function deactivateAdmin(adminId, adminName) {
            if (!confirm(`Are you sure you want to deactivate ${adminName}?\n\nThis will:\n- Revoke their admin access\n- Log them out immediately\n- Prevent them from logging in as admin\n\nThis action can only be reversed by a system administrator.`)) {
                return;
            }

            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'admin_deactivate.php';

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'admin_id';
            idInput.value = adminId;

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?php echo $csrf_token; ?>';

            form.appendChild(idInput);
            form.appendChild(csrfInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
