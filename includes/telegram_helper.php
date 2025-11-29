<?php
/**
 * Telegram Notification Helper
 * Sends Telegram notifications for booking status changes
 */

require_once __DIR__ . '/telegram_config.php';

/**
 * Send booking notification via Telegram
 * 
 * @param string $chatId User's Telegram Chat ID
 * @param string $userName User's name
 * @param string $status Booking status (approved, rejected, collect, returned)
 * @param array $bookingData Booking details
 * @return bool Success status
 */
function sendTelegramNotification($chatId, $userName, $status, $bookingData) {
    if (!TELEGRAM_ENABLED || empty($chatId)) {
        return true; // Silently skip if Telegram disabled or no chat ID
    }

    // Build message
    $message = getTelegramMessage($userName, $status, $bookingData);
    
    // Send message
    return sendTelegramMessage($chatId, $message);
}

/**
 * Send message to Telegram user
 * 
 * @param string $chatId Telegram Chat ID
 * @param string $message Message text
 * @return bool Success status
 */
function sendTelegramMessage($chatId, $message) {
    $botToken = TELEGRAM_BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML' // Support HTML formatting
    ];
    
    // Initialize cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log result
    if ($httpCode == 200) {
        error_log("Telegram notification sent successfully to chat ID: {$chatId}");
        return true;
    } else {
        error_log("Failed to send Telegram notification. HTTP Code: {$httpCode}, Response: {$response}");
        return false;
    }
}

/**
 * Build Telegram message based on booking status
 * 
 * @param string $userName User's name
 * @param string $status Booking status
 * @param array $bookingData Booking details
 * @return string Formatted message
 */
function getTelegramMessage($userName, $status, $bookingData) {
    $itemName = $bookingData['item_name'] ?? 'Unknown Item';
    $bookingId = $bookingData['booking_id'] ?? 'N/A';
    $quantity = $bookingData['quantity'] ?? '0';
    $startDate = $bookingData['start_date'] ?? 'N/A';
    $endDate = $bookingData['end_date'] ?? 'N/A';
    
    $messages = [
        'approved' => "
🎉 <b>Booking Approved!</b>

Hello {$userName},

Your booking request has been <b>approved</b>!

📦 <b>Booking Details:</b>
• Item: {$itemName}
• Booking ID: #{$bookingId}
• Quantity: {$quantity}
• Period: {$startDate} to {$endDate}

✅ <b>Next Steps:</b>
Please collect the item from the designated location during the specified period.

<i>iReserve - UPSI</i>
        ",
        
        'rejected' => "
❌ <b>Booking Rejected</b>

Hello {$userName},

Unfortunately, your booking request has been <b>rejected</b>.

📦 <b>Booking Details:</b>
• Item: {$itemName}
• Booking ID: #{$bookingId}
• Quantity: {$quantity}
• Period: {$startDate} to {$endDate}

Please contact the administrator for more information or try booking a different item.

<i>iReserve - UPSI</i>
        ",
        
        'taken' => "
📋 <b>Item Ready for Collection</b>

Hello {$userName},

Your booking status has been updated to <b>Taken</b>.

📦 <b>Booking Details:</b>
• Item: {$itemName}
• Booking ID: #{$bookingId}
• Quantity: {$quantity}
• Period: {$startDate} to {$endDate}

✅ The item is now in your possession. Please return it by the end date.

<i>iReserve - UPSI</i>
        ",
        
        'returned' => "
✅ <b>Item Return Confirmed</b>

Hello {$userName},

Your item return has been <b>confirmed</b>.

📦 <b>Booking Details:</b>
• Item: {$itemName}
• Booking ID: #{$bookingId}
• Quantity: {$quantity}
• Period: {$startDate} to {$endDate}

Thank you for using iReserve!

<i>iReserve - UPSI</i>
        "
    ];
    
    return isset($messages[$status]) ? trim($messages[$status]) : "Booking status updated to: " . ucfirst($status);
}

/**
 * Test Telegram bot connection
 * 
 * @return array Bot information or error
 */
function testTelegramBot() {
    if (!TELEGRAM_ENABLED) {
        return ['success' => false, 'message' => 'Telegram notifications are disabled'];
    }
    
    $botToken = TELEGRAM_BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$botToken}/getMe";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if ($result['ok']) {
            return [
                'success' => true,
                'bot_info' => $result['result']
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Failed to connect to Telegram Bot',
        'response' => $response
    ];
}
?>
