<?php
/**
 * Admin Registration Page
 * 
 * Allows invited users to complete their admin registration using
 * a one-time invitation token. Validates token, collects user info,
 * and creates admin account.
 * 
 * Security Features:
 * - Token validation (must exist, not used, valid format)
 * - Password strength requirements
 * - Password confirmation matching
 * - Prepared statements for all queries
 * - Audit logging
 * - One-time use tokens
 */

session_name('ireserve_admin');
session_start();

// Include required files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$error = '';
$success = false;
$invite = null;
$faculty_name = '';

// Get token from URL parameter
$token = $_GET['token'] ?? '';

// Validate token format (must be 32 hex characters)
if (empty($token) || !preg_match('/^[a-f0-9]{32}$/', $token)) {
    $error = 'Invalid or missing invitation token.';
} else {
    // Fetch invite details using prepared statement
    $stmt = $conn->prepare("
        SELECT ai.token, ai.email, ai.faculty_id, ai.used, ai.used_at, 
               creator.name as invited_by, f.faculty_name
        FROM admin_invites ai
        JOIN users creator ON ai.created_by = creator.id
        JOIN faculties f ON ai.faculty_id = f.faculty_id
        WHERE ai.token = ?
        LIMIT 1
    ");
    
    if (!$stmt) {
        $error = 'Database error. Please try again.';
        error_log("Prepare failed: " . $conn->error);
    } else {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'This invitation token does not exist or has expired.';
        } else {
            $invite = $result->fetch_assoc();
            $faculty_name = $invite['faculty_name'];
            
            // Check if token has already been used
            if ($invite['used'] == 1) {
                $error = 'This invitation has already been used and cannot be used again.';
            }
        }
        
        $stmt->close();
    }
}

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && $invite) {
    
    // Get form inputs
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate name
    if (empty($name)) {
        $error = 'Full name is required.';
    } elseif (strlen($name) < 3) {
        $error = 'Name must be at least 3 characters long.';
    } elseif (strlen($name) > 255) {
        $error = 'Name is too long (maximum 255 characters).';
    }
    // Validate password
    elseif (empty($password)) {
        $error = 'Password is required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check password strength
        $password_check = validate_password_strength($password);
        if (!$password_check['valid']) {
            $error = $password_check['message'];
        } else {
            // Hash password using PHP's secure password hashing
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin transaction to ensure atomicity
            $conn->begin_transaction();
            
            try {
                // Insert new admin user
                $stmt = $conn->prepare("
                    INSERT INTO users (name, email, password, role, faculty_id, is_active) 
                    VALUES (?, ?, ?, 'admin', ?, 1)
                ");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("sssi", $name, $invite['email'], $password_hash, $invite['faculty_id']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create user: " . $stmt->error);
                }
                
                $new_user_id = $conn->insert_id;
                $stmt->close();
                
                // Mark invitation as used
                $stmt = $conn->prepare("
                    UPDATE admin_invites 
                    SET used = 1, used_at = NOW(), used_by = ? 
                    WHERE token = ?
                ");
                
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param("is", $new_user_id, $token);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update invite: " . $stmt->error);
                }
                
                $stmt->close();
                
                // Log successful registration to audit log
                $details = json_encode([
                    'user_id' => $new_user_id,
                    'email' => $invite['email'],
                    'faculty_id' => $invite['faculty_id'],
                    'name' => $name
                ]);
                audit_log($new_user_id, 'register_admin', $new_user_id, $details);
                
                // Commit transaction
                $conn->commit();
                
                $success = true;
                
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = 'Registration failed. Please try again or contact support.';
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - iReserve</title>
    <link rel="stylesheet" href="../assets/css/login_style.css">
    <link rel="stylesheet" href="../assets/css/admin_management.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .registration-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 90%;
            padding: 40px;
            margin: 20px;
        }
        .registration-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .registration-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .registration-header .subtitle {
            color: #666;
            font-size: 14px;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .success-box h2 {
            margin-top: 0;
            color: #155724;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        .required {
            color: #e74c3c;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .password-requirements {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        .password-requirements h4 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 13px;
            color: #495057;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        .password-requirements li {
            margin-bottom: 5px;
            color: #6c757d;
        }
        .invite-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .invite-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #004085;
        }
        .invite-info strong {
            color: #002752;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-header">
            <h1>🎓 Admin Registration</h1>
            <p class="subtitle">iReserve - UPSI Facility Booking System</p>
        </div>
        
        <?php if ($success): ?>
            <div class="success-box">
                <h2>✅ Registration Successful!</h2>
                <p>Your admin account has been created successfully.</p>
                <p>You can now log in to the system.</p>
                <a href="../index.php" class="btn" style="display: inline-block; text-decoration: none; margin-top: 20px;">
                    Go to Login
                </a>
            </div>
        <?php elseif ($error): ?>
            <div class="error-box">
                <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php if (strpos($error, 'already been used') !== false): ?>
                <a href="../index.php" class="btn btn-secondary">Go to Login</a>
            <?php endif; ?>
        <?php else: ?>
            <div class="invite-info">
                <p><strong>📧 Email:</strong> <?php echo htmlspecialchars($invite['email']); ?></p>
                <p><strong>🏫 Faculty:</strong> <?php echo htmlspecialchars($faculty_name); ?></p>
                <p><strong>👤 Invited by:</strong> <?php echo htmlspecialchars($invite['invited_by']); ?></p>
            </div>
            
            <form method="POST" action="" id="registrationForm">
                <div class="form-group">
                    <label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-control" 
                           placeholder="Enter your full name"
                           required
                           minlength="3"
                           maxlength="255"
                           autocomplete="name"
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    <small>Enter your full name as it should appear in the system</small>
                </div>
                
                <div class="password-requirements">
                    <h4>Password Requirements:</h4>
                    <ul>
                        <li>At least 8 characters long</li>
                        <li>Contains uppercase letter (A-Z)</li>
                        <li>Contains lowercase letter (a-z)</li>
                        <li>Contains number (0-9)</li>
                        <li>Contains special character (!@#$%^&*)</li>
                    </ul>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Enter password"
                           required
                           minlength="8"
                           autocomplete="new-password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="form-control" 
                           placeholder="Re-enter password"
                           required
                           minlength="8"
                           autocomplete="new-password">
                    <small>Please re-enter your password to confirm</small>
                </div>
                
                <button type="submit" class="btn">Complete Registration</button>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        // Client-side password validation
        document.getElementById('registrationForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const name = document.getElementById('name').value.trim();
            
            // Validate name
            if (name.length < 3) {
                e.preventDefault();
                alert('Name must be at least 3 characters long.');
                return false;
            }
            
            // Validate passwords match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match. Please check and try again.');
                return false;
            }
            
            // Validate password strength
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
            
            const errors = [];
            if (!requirements.length) errors.push('at least 8 characters');
            if (!requirements.uppercase) errors.push('an uppercase letter');
            if (!requirements.lowercase) errors.push('a lowercase letter');
            if (!requirements.number) errors.push('a number');
            if (!requirements.special) errors.push('a special character');
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Password must contain: ' + errors.join(', '));
                return false;
            }
            
            return true;
        });
        
        // Real-time password match indicator
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        
        if (confirmInput) {
            confirmInput.addEventListener('input', function() {
                if (this.value.length > 0) {
                    if (this.value === passwordInput.value) {
                        this.style.borderColor = '#28a745';
                    } else {
                        this.style.borderColor = '#dc3545';
                    }
                } else {
                    this.style.borderColor = '#ddd';
                }
            });
        }
    </script>
</body>
</html>
