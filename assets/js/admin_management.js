/**
 * Admin Management JavaScript
 * 
 * Client-side functionality for admin invite and management pages
 * Provides validation, user feedback, and enhanced interactions
 */

// =====================================================
// Email Validation
// =====================================================

/**
 * Validate email format using regex
 * @param {string} email - Email address to validate
 * @returns {boolean} True if valid email format
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Show validation error on input field
 * @param {HTMLElement} input - Input element
 * @param {string} message - Error message to display
 */
function showInputError(input, message) {
    // Remove any existing error
    clearInputError(input);
    
    // Add error class
    input.classList.add('input-error');
    
    // Create error message element
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.cssText = 'color: #dc3545; font-size: 12px; margin-top: 5px;';
    errorDiv.textContent = message;
    
    // Insert after input
    input.parentNode.insertBefore(errorDiv, input.nextSibling);
}

/**
 * Clear validation error from input field
 * @param {HTMLElement} input - Input element
 */
function clearInputError(input) {
    input.classList.remove('input-error');
    
    // Remove error message if exists
    const errorMsg = input.parentNode.querySelector('.error-message');
    if (errorMsg) {
        errorMsg.remove();
    }
}

// =====================================================
// Password Strength Validation
// =====================================================

/**
 * Check password strength against requirements
 * @param {string} password - Password to check
 * @returns {Object} Object with validation results
 */
function checkPasswordStrength(password) {
    return {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password)
    };
}

/**
 * Get password strength message
 * @param {Object} requirements - Requirements object from checkPasswordStrength
 * @returns {string} Error message or empty string if valid
 */
function getPasswordStrengthMessage(requirements) {
    const errors = [];
    
    if (!requirements.length) errors.push('at least 8 characters');
    if (!requirements.uppercase) errors.push('an uppercase letter');
    if (!requirements.lowercase) errors.push('a lowercase letter');
    if (!requirements.number) errors.push('a number');
    if (!requirements.special) errors.push('a special character');
    
    if (errors.length > 0) {
        return 'Password must contain: ' + errors.join(', ');
    }
    
    return '';
}

// =====================================================
// Form Submission Handlers
// =====================================================

/**
 * Handle invite form submission
 */
function handleInviteFormSubmit(event) {
    const form = event.target;
    const emailInput = form.querySelector('#email');
    const email = emailInput.value.trim();
    
    // Clear previous errors
    clearInputError(emailInput);
    
    // Validate email
    if (!email) {
        event.preventDefault();
        showInputError(emailInput, 'Email address is required.');
        emailInput.focus();
        return false;
    }
    
    if (!validateEmail(email)) {
        event.preventDefault();
        showInputError(emailInput, 'Please enter a valid email address.');
        emailInput.focus();
        return false;
    }
    
    // Confirm action
    if (!confirm('Send admin invitation to ' + email + '?')) {
        event.preventDefault();
        return false;
    }
    
    return true;
}

/**
 * Handle registration form submission
 */
function handleRegistrationFormSubmit(event) {
    const form = event.target;
    const nameInput = form.querySelector('#name');
    const passwordInput = form.querySelector('#password');
    const confirmPasswordInput = form.querySelector('#confirm_password');
    
    const name = nameInput.value.trim();
    const password = passwordInput.value;
    const confirmPassword = confirmPasswordInput.value;
    
    // Clear previous errors
    clearInputError(nameInput);
    clearInputError(passwordInput);
    clearInputError(confirmPasswordInput);
    
    // Validate name
    if (name.length < 3) {
        event.preventDefault();
        showInputError(nameInput, 'Name must be at least 3 characters long.');
        nameInput.focus();
        return false;
    }
    
    // Validate passwords match
    if (password !== confirmPassword) {
        event.preventDefault();
        showInputError(confirmPasswordInput, 'Passwords do not match.');
        confirmPasswordInput.focus();
        return false;
    }
    
    // Validate password strength
    const requirements = checkPasswordStrength(password);
    const strengthMessage = getPasswordStrengthMessage(requirements);
    
    if (strengthMessage) {
        event.preventDefault();
        showInputError(passwordInput, strengthMessage);
        passwordInput.focus();
        return false;
    }
    
    return true;
}

// =====================================================
// Admin Deactivation
// =====================================================

/**
 * Deactivate admin with confirmation
 * @param {number} userId - User ID to deactivate
 * @param {string} name - Admin name for confirmation
 */
function deactivateAdmin(userId, name) {
    if (confirm('Are you sure you want to deactivate ' + name + '?\n\nThey will no longer be able to access the admin panel.')) {
        const form = document.getElementById('deactivateForm');
        if (form) {
            document.getElementById('deactivate_id').value = userId;
            form.submit();
        }
    }
}

// =====================================================
// Copy to Clipboard
// =====================================================

/**
 * Copy text to clipboard and show notification
 * @param {string} text - Text to copy
 */
function copyToClipboard(text) {
    // Modern clipboard API
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showNotification('✓ Copied to clipboard!', 'success');
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
            showNotification('Failed to copy to clipboard', 'error');
        });
    } else {
        // Fallback for older browsers
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showNotification('✓ Copied to clipboard!', 'success');
            } else {
                showNotification('Failed to copy to clipboard', 'error');
            }
        } catch (err) {
            console.error('Failed to copy: ', err);
            showNotification('Failed to copy to clipboard', 'error');
        }
        
        document.body.removeChild(tempInput);
    }
}

/**
 * Show temporary notification message
 * @param {string} message - Message to display
 * @param {string} type - Notification type (success, error, info)
 */
function showNotification(message, type = 'info') {
    // Remove any existing notifications
    const existingNotification = document.querySelector('.notification-toast');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'notification-toast notification-' + type;
    notification.textContent = message;
    
    // Style notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        font-weight: 600;
        font-size: 14px;
        animation: slideIn 0.3s ease-out;
    `;
    
    // Set colors based on type
    if (type === 'success') {
        notification.style.background = '#28a745';
        notification.style.color = 'white';
    } else if (type === 'error') {
        notification.style.background = '#dc3545';
        notification.style.color = 'white';
    } else {
        notification.style.background = '#17a2b8';
        notification.style.color = 'white';
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(function() {
        notification.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(function() {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}

// =====================================================
// Real-time Password Match Indicator
// =====================================================

/**
 * Setup real-time password matching indicator
 */
function setupPasswordMatchIndicator() {
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    
    if (passwordInput && confirmInput) {
        confirmInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                if (this.value === passwordInput.value) {
                    this.style.borderColor = '#28a745';
                    clearInputError(this);
                } else {
                    this.style.borderColor = '#dc3545';
                }
            } else {
                this.style.borderColor = '#ced4da';
            }
        });
        
        passwordInput.addEventListener('input', function() {
            // Re-check confirm password if it has value
            if (confirmInput.value.length > 0) {
                confirmInput.dispatchEvent(new Event('input'));
            }
        });
    }
}

// =====================================================
// Input Auto-trim
// =====================================================

/**
 * Setup auto-trim for text inputs on blur
 */
function setupAutoTrim() {
    const textInputs = document.querySelectorAll('input[type="text"], input[type="email"]');
    
    textInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            this.value = this.value.trim();
        });
    });
}

// =====================================================
// Session Message Display
// =====================================================

/**
 * Display session messages if they exist
 */
function displaySessionMessages() {
    // This would be populated by PHP via data attributes or inline script
    const messageContainer = document.getElementById('session-message');
    
    if (messageContainer) {
        const message = messageContainer.dataset.message;
        const type = messageContainer.dataset.type;
        
        if (message) {
            showNotification(message, type);
        }
    }
}

// =====================================================
// Animation Keyframes (add to page dynamically)
// =====================================================

function addAnimationStyles() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .input-error {
            border-color: #dc3545 !important;
            background-color: #fff5f5;
        }
        
        .input-success {
            border-color: #28a745 !important;
        }
    `;
    document.head.appendChild(style);
}

// =====================================================
// Initialize on Page Load
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    // Add animation styles
    addAnimationStyles();
    
    // Setup password match indicator
    setupPasswordMatchIndicator();
    
    // Setup auto-trim
    setupAutoTrim();
    
    // Display session messages
    displaySessionMessages();
    
    // Attach form handlers
    const inviteForm = document.getElementById('inviteForm');
    if (inviteForm) {
        inviteForm.addEventListener('submit', handleInviteFormSubmit);
    }
    
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        registrationForm.addEventListener('submit', handleRegistrationFormSubmit);
    }
    
    // Setup copy-to-clipboard for registration links
    const copyLinks = document.querySelectorAll('.copy-link, .copy-link-input');
    copyLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            this.select();
            copyToClipboard(this.value);
        });
    });
});

// =====================================================
// Export functions for use in inline scripts
// =====================================================

window.adminManagement = {
    validateEmail: validateEmail,
    checkPasswordStrength: checkPasswordStrength,
    deactivateAdmin: deactivateAdmin,
    copyToClipboard: copyToClipboard,
    showNotification: showNotification
};
