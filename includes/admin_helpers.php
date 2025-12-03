<?php
/**
 * Admin Helper Functions
 * 
 * Provides utility functions for admin management features including:
 * - Session management
 * - Faculty validation
 * - Audit logging
 * - CSRF protection
 * 
 * Security: All functions use prepared statements and validate input
 */

/**
 * Get current admin user data from session
 * 
 * Returns the current logged-in admin's information including
 * user_id, name, email, role, faculty_id, and is_active status
 * 
 * @global mysqli $conn Database connection
 * @return array|null Admin data or null if not found
 */
function current_admin() {
    global $conn;
    
    // Check if user is logged in via session
    if (!isset($_SESSION['email'])) {
        return null;
    }
    
    $email = $_SESSION['email'];
    
    // Use prepared statement to fetch admin data
    $stmt = $conn->prepare("
        SELECT id, name, email, role, faculty_id, is_active 
        FROM users 
        WHERE email = ? AND role = 'admin' AND is_active = 1
        LIMIT 1
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $admin = $result->fetch_assoc();
    $stmt->close();
    
    return $admin;
}

/**
 * Count active admins for a specific faculty
 * 
 * Used to prevent deactivating the last admin in a faculty
 * Only counts admins where is_active = 1
 * 
 * @global mysqli $conn Database connection
 * @param int $faculty_id Faculty ID to count admins for
 * @return int Number of active admins
 */
function count_active_admins($faculty_id) {
    global $conn;
    
    // Validate faculty_id is provided
    if (empty($faculty_id)) {
        return 0;
    }
    
    // Prepare statement to count active admins
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE role = 'admin' 
        AND faculty_id = ? 
        AND is_active = 1
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return 0;
    }
    
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return (int)$row['count'];
}

/**
 * Check if a user belongs to a specific faculty
 * 
 * Validates that a given user_id is associated with the specified faculty
 * Used for authorization checks before performing faculty-scoped actions
 * 
 * @global mysqli $conn Database connection
 * @param int $user_id User ID to check
 * @param int $faculty_id Faculty ID to verify against
 * @return bool True if user belongs to faculty, false otherwise
 */
function is_same_faculty($user_id, $faculty_id) {
    global $conn;
    
    // Validate inputs
    if (!is_numeric($user_id) || !is_numeric($faculty_id)) {
        return false;
    }
    
    // Check if user's faculty matches provided faculty
    $stmt = $conn->prepare("
        SELECT 1 
        FROM users 
        WHERE id = ? 
        AND faculty_id = ? 
        LIMIT 1
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("ii", $user_id, $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

/**
 * Log administrative action to audit_logs table
 * 
 * Records all significant admin actions for security and compliance
 * Captures actor, action type, target, additional details, and IP address
 * 
 * @global mysqli $conn Database connection
 * @param int|null $actor_id User ID performing the action (null for system actions)
 * @param string $action Action description (e.g., 'create_invite', 'deactivate_admin')
 * @param string|null $target Target of the action (e.g., email, user_id)
 * @param string|null $details Additional JSON or text details
 * @return bool True if logged successfully, false otherwise
 */
function audit_log($actor_id, $action, $target = null, $details = null) {
    global $conn;
    
    // Validate required parameters
    if (empty($action)) {
        return false;
    }
    
    // Get client IP address (supports proxy headers)
    $ip = get_client_ip();
    
    // Prepare insert statement
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (actor_id, action, target, details, ip) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issss", $actor_id, $action, $target, $details, $ip);
    $success = $stmt->execute();
    
    if (!$success) {
        error_log("Audit log failed: " . $stmt->error);
    }
    
    $stmt->close();
    return $success;
}

/**
 * Get client IP address
 * 
 * Retrieves the real client IP, accounting for proxies and load balancers
 * Checks common proxy headers in order of reliability
 * 
 * @return string Client IP address
 */
function get_client_ip() {
    // Check for IP from shared internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IP passed from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Can contain multiple IPs, take the first one
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    // Check for remote address
    elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    else {
        $ip = 'UNKNOWN';
    }
    
    // Validate IP format
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = 'INVALID';
    }
    
    return $ip;
}

/**
 * Generate CSRF token for form protection
 * 
 * Creates a unique token stored in the session to prevent
 * Cross-Site Request Forgery attacks on POST forms
 * 
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from form submission
 * 
 * Compares submitted token with session token using timing-safe comparison
 * Prevents CSRF attacks by ensuring requests originate from legitimate forms
 * 
 * @param string $token Token from POST request
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Use hash_equals to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check rate limit for admin invites
 * 
 * Prevents abuse by limiting an admin to 5 invites per hour
 * Counts invites created in the last 60 minutes
 * 
 * @global mysqli $conn Database connection
 * @param int $admin_id Admin user ID to check
 * @return bool True if under limit, false if limit exceeded
 */
function check_invite_rate_limit($admin_id) {
    global $conn;
    
    if (!is_numeric($admin_id)) {
        return false;
    }
    
    // Count invites created in the last hour
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM admin_invites 
        WHERE created_by = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    // Limit is 5 invites per hour
    return (int)$row['count'] < 5;
}

/**
 * Validate email format
 * 
 * Uses PHP filter to validate email address format
 * 
 * @param string $email Email address to validate
 * @return bool True if valid email format, false otherwise
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if email is already an admin in a faculty
 * 
 * Prevents duplicate admin accounts for the same email in a faculty
 * 
 * @global mysqli $conn Database connection
 * @param string $email Email to check
 * @param int $faculty_id Faculty ID to check within
 * @return bool True if email already exists as admin, false otherwise
 */
function email_exists_as_admin($email, $faculty_id) {
    global $conn;
    
    if (!validate_email($email) || !is_numeric($faculty_id)) {
        return false;
    }
    
    $stmt = $conn->prepare("
        SELECT 1 
        FROM users 
        WHERE email = ? 
        AND faculty_id = ? 
        AND role = 'admin'
        LIMIT 1
    ");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("si", $email, $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

/**
 * Validate password strength
 * 
 * Ensures password meets security requirements:
 * - At least 8 characters long
 * - Contains at least one uppercase letter
 * - Contains at least one lowercase letter
 * - Contains at least one number
 * - Contains at least one special character
 * 
 * @param string $password Password to validate
 * @return array ['valid' => bool, 'message' => string]
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => empty($errors),
        'message' => empty($errors) ? 'Password is strong' : implode('. ', $errors)
    ];
}

/**
 * Get faculty name by ID
 * 
 * @global mysqli $conn Database connection
 * @param int $faculty_id Faculty ID
 * @return string|null Faculty name or null if not found
 */
function get_faculty_name($faculty_id) {
    global $conn;
    
    if (!is_numeric($faculty_id)) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT faculty_name FROM faculties WHERE faculty_id = ? LIMIT 1");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['faculty_name'];
}

/**
 * Update user's last login timestamp
 * 
 * @global mysqli $conn Database connection
 * @param int $id User ID
 * @return bool Success status
 */
function update_last_login($id) {
    global $conn;
    
    if (!is_numeric($id)) {
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    
    return $success;
}
