<?php
/**
 * Email Notification Helper
 * Sends email notifications for booking status changes
 */

require_once __DIR__ . '/email_config.php';

/**
 * Send booking notification email
 * 
 * @param string $userEmail Recipient email address
 * @param string $userName User's name
 * @param string $status Booking status (approved, rejected, taken, returned)
 * @param array $bookingData Booking details
 * @return bool Success status
 */
function sendBookingNotification($userEmail, $userName, $status, $bookingData) {
    if (!EMAIL_ENABLED) {
        return true; // Silently skip if emails disabled
    }

    // Use PHPMailer if SMTP is enabled, otherwise use PHP mail()
    if (EMAIL_USE_SMTP) {
        return sendEmailWithPHPMailer($userEmail, $userName, $status, $bookingData);
    } else {
        return sendEmailWithPHPMail($userEmail, $userName, $status, $bookingData);
    }
}

/**
 * Send email using PHPMailer (SMTP)
 */
function sendEmailWithPHPMailer($userEmail, $userName, $status, $bookingData) {
    // Check if PHPMailer is installed
    $phpmailerPath = __DIR__ . '/../vendor/autoload.php';
    
    if (!file_exists($phpmailerPath)) {
        // Fallback to PHP mail if PHPMailer not installed
        error_log("PHPMailer not found, falling back to PHP mail()");
        return sendEmailWithPHPMail($userEmail, $userName, $status, $bookingData);
    }

    require_once $phpmailerPath;

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = getEmailSubject($status);
        $mail->Body    = getEmailBody($userName, $status, $bookingData);
        $mail->AltBody = strip_tags(getEmailBody($userName, $status, $bookingData));

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send email using PHP's built-in mail() function
 */
function sendEmailWithPHPMail($userEmail, $userName, $status, $bookingData) {
    $subject = getEmailSubject($status);
    $message = getEmailBody($userName, $status, $bookingData);
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDRESS . ">\r\n";
    
    return mail($userEmail, $subject, $message, $headers);
}

/**
 * Get email subject based on status
 */
function getEmailSubject($status) {
    switch($status) {
        case 'approved':
            return EMAIL_SUBJECT_APPROVED;
        case 'rejected':
            return EMAIL_SUBJECT_REJECTED;
        case 'taken':
            return EMAIL_SUBJECT_TAKEN;
        case 'returned':
            return EMAIL_SUBJECT_RETURNED;
        default:
            return 'Booking Status Update - iReserve';
    }
}

/**
 * Generate email body HTML
 */
function getEmailBody($userName, $status, $bookingData) {
    $refCode = $bookingData['ref_code'] ?? 'N/A';
    $itemName = $bookingData['item_name'] ?? 'N/A';
    $startDate = $bookingData['start_date'] ?? 'N/A';
    $endDate = $bookingData['end_date'] ?? 'N/A';
    $adminNote = $bookingData['admin_note'] ?? '';
    
    // Status-specific content
    $statusColor = '';
    $statusTitle = '';
    $statusMessage = '';
    $actionButton = '';

    switch($status) {
        case 'approved':
            $statusColor = '#10b981';
            $statusTitle = '✓ Booking Approved';
            $statusMessage = 'Good news! Your booking request has been approved by the admin.';
            $actionButton = '<a href="http://localhost/ireserve/user/bookingsDetail.php" style="display: inline-block; padding: 12px 24px; background: #2a2185; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px;">View My Bookings</a>';
            break;
        case 'rejected':
            $statusColor = '#ef4444';
            $statusTitle = '✗ Booking Rejected';
            $statusMessage = 'Unfortunately, your booking request has been rejected by the admin.';
            $actionButton = '<a href="http://localhost/ireserve/user/user_page.php" style="display: inline-block; padding: 12px 24px; background: #2a2185; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px;">Browse Items</a>';
            break;
        case 'taken':
            $statusColor = '#3b82f6';
            $statusTitle = '📦 Ready for Collection';
            $statusMessage = 'Your item is ready! Please collect it at your earliest convenience.';
            $actionButton = '<a href="http://localhost/ireserve/user/bookingsDetail.php" style="display: inline-block; padding: 12px 24px; background: #2a2185; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px;">View Details</a>';
            break;
        case 'returned':
            $statusColor = '#8b5cf6';
            $statusTitle = '✓ Return Confirmed';
            $statusMessage = 'Thank you! We have confirmed the return of your item.';
            $actionButton = '<a href="http://localhost/ireserve/user/bookingsDetail.php" style="display: inline-block; padding: 12px 24px; background: #2a2185; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px;">View History</a>';
            break;
    }

    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f7f8fb;'>
        <div style='max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #2a2185 0%, #1a1555 100%); padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 28px;'>iReserve</h1>
                <p style='color: rgba(255,255,255,0.9); margin: 5px 0 0 0; font-size: 14px;'>UPSI Booking System</p>
            </div>

            <!-- Content -->
            <div style='padding: 30px;'>
                <h2 style='color: #2a2185; margin: 0 0 10px 0; font-size: 24px;'>Hello, {$userName}!</h2>
                
                <div style='background: {$statusColor}15; border-left: 4px solid {$statusColor}; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                    <h3 style='color: {$statusColor}; margin: 0 0 10px 0; font-size: 18px;'>{$statusTitle}</h3>
                    <p style='margin: 0; color: #555;'>{$statusMessage}</p>
                </div>

                <div style='background: #f7f8fb; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h4 style='margin: 0 0 15px 0; color: #2a2185; font-size: 16px;'>Booking Details</h4>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; color: #6b7280; font-weight: 600;'>Reference Code:</td>
                            <td style='padding: 8px 0; color: #111;'>{$refCode}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #6b7280; font-weight: 600;'>Item:</td>
                            <td style='padding: 8px 0; color: #111;'>{$itemName}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #6b7280; font-weight: 600;'>Start Date:</td>
                            <td style='padding: 8px 0; color: #111;'>{$startDate}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #6b7280; font-weight: 600;'>End Date:</td>
                            <td style='padding: 8px 0; color: #111;'>{$endDate}</td>
                        </tr>
                    </table>
                </div>

                " . ($adminNote ? "
                <div style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                    <h4 style='margin: 0 0 8px 0; color: #92400e; font-size: 14px; font-weight: 600;'>Admin Note:</h4>
                    <p style='margin: 0; color: #78350f;'>{$adminNote}</p>
                </div>
                " : "") . "

                <div style='text-align: center;'>
                    {$actionButton}
                </div>

                <p style='color: #6b7280; font-size: 14px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb;'>
                    If you have any questions, please contact the admin or visit the iReserve system.
                </p>
            </div>

            <!-- Footer -->
            <div style='background: #f7f8fb; padding: 20px; text-align: center; border-top: 1px solid #e5e7eb;'>
                <p style='color: #6b7280; margin: 0; font-size: 13px;'>© " . date('Y') . " UPSI - iReserve System</p>
                <p style='color: #9ca3af; margin: 5px 0 0 0; font-size: 12px;'>Faculty of Computing and Meta-Technology</p>
            </div>
        </div>
    </body>
    </html>
    ";

    return $html;
}
?>
