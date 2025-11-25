<?php
/**
 * Email Configuration
 * 
 * Configure your email settings here for sending notifications
 */

// Email Settings
define('EMAIL_ENABLED', true); // Set to false to disable email notifications

// SMTP Configuration (recommended for reliability)
define('EMAIL_USE_SMTP', true); // Set to true to use SMTP, false to use PHP mail()

// SMTP Settings (for Gmail, Outlook, or your hosting provider)
define('SMTP_HOST', 'smtp.gmail.com'); // Gmail: smtp.gmail.com, Outlook: smtp.office365.com
define('SMTP_PORT', 587); // Usually 587 for TLS or 465 for SSL
define('SMTP_USERNAME', 'd20221101821@siswa.upsi.edu.my'); // Your email address
define('SMTP_PASSWORD', 'tufk qcny ndgm wrjc'); // For Gmail: use App Password, not regular password
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'

// Sender Information
define('EMAIL_FROM_ADDRESS', 'd20221101821@siswa.upsi.edu.my'); // Same as SMTP_USERNAME
define('EMAIL_FROM_NAME', 'iReserve - UPSI'); // Display name for emails

// Email Templates
define('EMAIL_SUBJECT_APPROVED', 'Your Booking Has Been Approved - iReserve');
define('EMAIL_SUBJECT_REJECTED', 'Your Booking Has Been Rejected - iReserve');
define('EMAIL_SUBJECT_TAKEN', 'Your Item is Ready for Collection - iReserve');
define('EMAIL_SUBJECT_RETURNED', 'Item Return Confirmed - iReserve');

/**
 * Setup Instructions:
 * 
 * FOR GMAIL:
 * 1. Enable 2-Step Verification in your Google Account
 * 2. Go to: https://myaccount.google.com/apppasswords
 * 3. Create an App Password for "Mail"
 * 4. Use that 16-character password in SMTP_PASSWORD above
 * 5. Update SMTP_USERNAME and EMAIL_FROM_ADDRESS with your Gmail
 * 
 * FOR OTHER EMAIL PROVIDERS:
 * - Ask your hosting provider for SMTP details
 * - Update SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD accordingly
 */
?>
