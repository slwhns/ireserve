<?php
/**
 * Admin Invite Creation Page
 * 
 * Allows current admin to create invitation tokens for new admins
 * within their faculty. Includes validation, rate limiting, and
 * email notification with registration link.
 * 
 * Security Features:
 * - Session-based authentication
 * - CSRF token validation
 * - Rate limiting (5 invites per hour)
 * - Faculty-scoped access control
 * - Prepared statements
 * - Audit logging
 */

session_name('ireserve_admin');
session_start();

// Include required files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/email_helper.php';

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

$response = ['success' => false, 'message' => ''];

// Process POST request - create invite
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $response['message'] = 'Invalid security token. Please refresh the page and try again.';
        
        // Return JSON for AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    } else {
        // Get and validate email input
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $response['message'] = 'Email address is required.';
        } elseif (!validate_email($email)) {
            $response['message'] = 'Please enter a valid email address.';
        } elseif (email_exists_as_admin($email, $admin['faculty_id'])) {
            $response['message'] = 'This email is already registered as an admin in your faculty.';
        } else {
            // Check if there's already a pending invite for this email
            $check_stmt = $conn->prepare("SELECT 1 FROM admin_invites WHERE email = ? AND faculty_id = ? AND used = 0 LIMIT 1");
            $check_stmt->bind_param("si", $email, $admin['faculty_id']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $has_pending_invite = $check_result->num_rows > 0;
            $check_stmt->close();
            
            if ($has_pending_invite) {
                $response['message'] = 'There is already a pending invitation for this email address.';
            }
            // Check rate limit - max 5 invites per hour
            elseif (!check_invite_rate_limit($admin['id'])) {
                $response['message'] = 'Rate limit exceeded. You can only create 5 invites per hour. Please try again later.';
            } else {
                // Generate secure random token (32 characters)
                $token = bin2hex(random_bytes(16));
                
                // Insert invite into database using prepared statement
                $stmt = $conn->prepare("
                    INSERT INTO admin_invites (token, email, faculty_id, created_by) 
                    VALUES (?, ?, ?, ?)
                ");
                
                if (!$stmt) {
                    $response['message'] = 'Database error. Please try again.';
                    error_log("Prepare failed: " . $conn->error);
                } else {
                    $stmt->bind_param("ssii", $token, $email, $admin['faculty_id'], $admin['id']);
                    
                    if ($stmt->execute()) {
                        // Log successful invite creation
                        $details = json_encode([
                            'email' => $email,
                            'faculty_id' => $admin['faculty_id'],
                            'token' => $token
                        ]);
                        audit_log($admin['id'], 'create_invite', $email, $details);
                        
                        // Generate registration link
                        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
                        $registration_link = $base_url . "/ireserve/admin/admin_register.php?token=" . urlencode($token);
                        
                        // Send email with registration link
                        $faculty_name = get_faculty_name($admin['faculty_id']);
                        $email_subject = "Admin Invitation - iReserve UPSI";
                        $email_body = "
                            <h2>You've been invited to be an Admin</h2>
                            <p>Hello,</p>
                            <p><strong>{$admin['name']}</strong> has invited you to become an administrator for <strong>{$faculty_name}</strong> in the iReserve facility booking system.</p>
                            <p>To complete your registration, please click the link below:</p>
                            <p><a href='{$registration_link}' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Complete Registration</a></p>
                            <p>Or copy and paste this link into your browser:</p>
                            <p><code>{$registration_link}</code></p>
                            <p><strong>Important:</strong> This link can only be used once and will expire after successful registration.</p>
                            <p>If you did not expect this invitation, please ignore this email.</p>
                            <br>
                            <p>Best regards,<br>iReserve System</p>
                        ";
                        
                        // Attempt to send email (function from email_helper.php)
                        if (function_exists('send_email')) {
                            $email_sent = send_email($email, $email_subject, $email_body);
                        } else {
                            // Fallback if email function not available
                            $email_sent = false;
                            error_log("Email function not available. Registration link: " . $registration_link);
                        }
                        
                        $response['success'] = true;
                        $response['message'] = 'Invitation created successfully! ' . 
                            ($email_sent ? 'An email has been sent to ' . htmlspecialchars($email) . '.' : 
                            'Please manually share this registration link: ' . htmlspecialchars($registration_link));
                        $response['registration_link'] = $registration_link;
                        
                    } else {
                        $response['message'] = 'Failed to create invitation. Please try again.';
                        error_log("Execute failed: " . $stmt->error);
                    }
                    
                    $stmt->close();
                }
            }
        }
        
        // Return JSON for AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    }
}

// Generate CSRF token for form
$csrf_token = generate_csrf_token();

// Get faculty name for display
$faculty_name = get_faculty_name($admin['faculty_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Invite | iReserve</title>
    <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=10">
    <link rel="stylesheet" href="/ireserve/assets/css/admin_management.css?v=2">
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
            
            <!-- ====================== Page Content ====================== -->
            <div class="settings-wrapper">
                <!-- ====================== Page Actions ====================== -->
                <div class="pageActions">
                    <a href="/ireserve/admin/admin_list.php" class="btn-back-simple">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                
                <div class="settings-grid">
                    <!-- ====================== Invite Form ====================== -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2><i class="bi bi-envelope-plus"></i> Create Admin Invitation</h2>
                            <p>Invite a new administrator for <?php echo htmlspecialchars($faculty_name); ?></p>
                        </div>
                        <div class="settings-card-body">
                            <form method="POST" action="" id="inviteForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                
                                <div class="form-group">
                                    <label for="email">Email Address <span class="required">*</span></label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           class="form-control" 
                                           placeholder="admin@upsi.edu.my"
                                           required
                                           autocomplete="off">
                                    <small class="form-text">Enter the email address of the person you want to invite as an admin.</small>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-envelope-plus"></i> Send Invitation
                                    </button>
                                    <a href="admin_list.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- ====================== Information Panel ====================== -->
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <h2><i class="bi bi-info-circle"></i> Important Information</h2>
                        </div>
                        <div class="settings-card-body">
                            <ul class="info-list">
                                <li><strong>Rate Limit:</strong> You can create a maximum of 5 invitations per hour.</li>
                                <li><strong>Faculty Scope:</strong> Invited admins will only manage resources for <?php echo htmlspecialchars($faculty_name); ?>.</li>
                                <li><strong>One-Time Use:</strong> Each invitation link can only be used once for registration.</li>
                                <li><strong>Email Validation:</strong> The system will check if the email is already registered as an admin.</li>
                                <li><strong>Security:</strong> All invitations are tracked in the audit log for security purposes.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification Modal -->
    <div id="notificationModal" class="modal">
        <div class="modal-content modal-alert">
            <div class="modal-header">
                <h3 id="modalTitle"></h3>
                <span class="close" onclick="closeNotificationModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p id="modalMessage"></p>
                <div id="registrationLinkBox" style="display: none; margin-top: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Registration Link:</label>
                    <input type="text" id="registrationLinkInput" readonly 
                           onclick="this.select()" 
                           style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: monospace; font-size: 13px;">
                    <small style="display: block; margin-top: 5px; color: #6b7280;">Click to select and copy</small>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeNotificationModal()">OK</button>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin_management.js"></script>
    <script>
        function showNotificationModal(title, message, isSuccess, registrationLink = null) {
            const modal = document.getElementById('notificationModal');
            const modalContent = modal.querySelector('.modal-content');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const linkBox = document.getElementById('registrationLinkBox');
            const linkInput = document.getElementById('registrationLinkInput');
            
            // Set title with icon
            if (isSuccess) {
                modalTitle.innerHTML = '<i class="bi bi-check-circle-fill"></i> Success';
                modalContent.classList.remove('error');
                modalContent.classList.add('success');
            } else {
                modalTitle.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> Error';
                modalContent.classList.remove('success');
                modalContent.classList.add('error');
            }
            
            modalMessage.textContent = message;
            
            // Show registration link if provided
            if (registrationLink) {
                linkInput.value = registrationLink;
                linkBox.style.display = 'block';
            } else {
                linkBox.style.display = 'none';
            }
            
            modal.style.display = 'flex';
        }
        
        function closeNotificationModal() {
            document.getElementById('notificationModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('notificationModal');
            if (event.target === modal) {
                closeNotificationModal();
            }
        }
        
        // Show notification on page load if message exists
        <?php if (!empty($response['message'])): ?>
        window.addEventListener('DOMContentLoaded', function() {
            showNotificationModal(
                '<?php echo $response['success'] ? 'Success' : 'Error'; ?>',
                <?php echo json_encode($response['message']); ?>,
                <?php echo $response['success'] ? 'true' : 'false'; ?>,
                <?php echo !empty($response['registration_link']) ? json_encode($response['registration_link']) : 'null'; ?>
            );
        });
        <?php endif; ?>
        
        // Client-side validation
        document.getElementById('inviteForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                showNotificationModal('Error', 'Please enter a valid email address.', false);
                return false;
            }
        });
    </script>
</body>
</html>
