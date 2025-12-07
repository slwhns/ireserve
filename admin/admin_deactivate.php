<?php
/**
 * Admin Deactivation Handler
 * 
 * Handles POST requests to deactivate (soft-delete) admin accounts.
 * Implements critical security checks:
 * - Prevents self-deactivation
 * - Prevents deactivating last active admin in faculty
 * - Faculty-scoped access control
 * - CSRF protection
 * - Audit logging
 */

session_name('ireserve_admin');
session_start();

// Include required files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Check authentication - must be logged-in admin
$admin = current_admin();
if (!$admin) {
    $response['message'] = 'Unauthorized access. Please log in.';
    $response['redirect'] = '/ireserve/index.php';
    
    // Return JSON for AJAX or redirect for normal form
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        header("Location: /ireserve/index.php");
    }
    exit();
}

// Verify admin has faculty_id assigned
if (empty($admin['faculty_id'])) {
    $response['message'] = 'Your admin account is not associated with a faculty.';
    output_response($response);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST is allowed.';
    output_response($response);
    exit();
}

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrf_token)) {
    $response['message'] = 'Invalid security token. Please refresh the page and try again.';
    output_response($response);
    exit();
}

// Get target id to deactivate (check both 'admin_id' and 'id' for compatibility)
$target_id = isset($_POST['admin_id']) ? intval($_POST['admin_id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if ($target_id <= 0) {
    $response['message'] = 'Invalid user ID provided.';
    output_response($response);
    exit();
}

// SECURITY CHECK 1: Prevent self-deactivation
if ($target_id === $admin['id']) {
    $response['message'] = 'You cannot deactivate your own account. Please ask another administrator to deactivate your account if needed.';
    output_response($response);
    exit();
}

// Fetch target user details to verify they exist and belong to same faculty
$stmt = $conn->prepare("
    SELECT id, name, email, role, faculty_id, is_active 
    FROM users 
    WHERE id = ? 
    LIMIT 1
");

if (!$stmt) {
    $response['message'] = 'Database error. Please try again.';
    error_log("Prepare failed: " . $conn->error);
    output_response($response);
    exit();
}

$stmt->bind_param("i", $target_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'User not found.';
    $stmt->close();
    output_response($response);
    exit();
}

$target_user = $result->fetch_assoc();
$stmt->close();

// SECURITY CHECK 2: Verify user is an admin
if ($target_user['role'] !== 'admin') {
    $response['message'] = 'Target user is not an administrator.';
    output_response($response);
    exit();
}

// SECURITY CHECK 3: Verify same faculty (faculty-scoped access control)
if ($target_user['faculty_id'] != $admin['faculty_id']) {
    $response['message'] = 'Access denied. You can only deactivate administrators within your faculty.';
    audit_log($admin['id'], 'deactivate_admin_denied', $target_id, 'Cross-faculty deactivation attempt blocked');
    output_response($response);
    exit();
}

// SECURITY CHECK 4: Verify user is currently active
if ($target_user['is_active'] != 1) {
    $response['message'] = 'This administrator is already deactivated.';
    output_response($response);
    exit();
}

// SECURITY CHECK 5: Count active admins - prevent deactivating last admin
$active_count = count_active_admins($admin['faculty_id']);

if ($active_count <= 1) {
    $response['message'] = 'Cannot deactivate the last active administrator. Your faculty must have at least one active admin at all times.';
    audit_log($admin['id'], 'deactivate_admin_denied', $target_id, 'Last admin protection triggered');
    output_response($response);
    exit();
}

// All checks passed - proceed with deactivation (soft delete)
$stmt = $conn->prepare("
    UPDATE users 
    SET is_active = 0 
    WHERE id = ? 
    AND role = 'admin' 
    AND faculty_id = ?
    LIMIT 1
");

if (!$stmt) {
    $response['message'] = 'Database error. Please try again.';
    error_log("Prepare failed: " . $conn->error);
    output_response($response);
    exit();
}

$stmt->bind_param("ii", $target_id, $admin['faculty_id']);

if ($stmt->execute()) {
    // Check if any rows were actually updated
    if ($stmt->affected_rows > 0) {
        // Success - log to audit trail
        $details = json_encode([
            'target_id' => $target_id,
            'target_name' => $target_user['name'],
            'target_email' => $target_user['email'],
            'faculty_id' => $admin['faculty_id'],
            'active_admins_remaining' => ($active_count - 1)
        ]);
        
        audit_log($admin['id'], 'deactivate_admin', $target_id, $details);
        
        $response['success'] = true;
        $response['message'] = "Administrator \"{$target_user['name']}\" has been successfully deactivated.";
        $response['redirect'] = 'admin_list.php?success=' . urlencode($response['message']);
    } else {
        $response['message'] = 'No changes were made. The administrator may have already been deactivated.';
        $response['redirect'] = 'admin_list.php?error=' . urlencode($response['message']);
    }
} else {
    $response['message'] = 'Failed to deactivate administrator. Please try again.';
    $response['redirect'] = 'admin_list.php?error=' . urlencode($response['message']);
    error_log("Execute failed: " . $stmt->error);
}

$stmt->close();

output_response($response);
exit();

/**
 * Output response based on request type (AJAX vs form POST)
 * 
 * @param array $response Response data
 */
function output_response($response) {
    // Check if AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        // Traditional form POST - redirect with session message
        $_SESSION['admin_message'] = $response['message'];
        $_SESSION['admin_message_type'] = $response['success'] ? 'success' : 'error';
        
        $redirect_url = !empty($response['redirect']) ? $response['redirect'] : 'admin_list.php';
        header("Location: " . $redirect_url);
    }
}
