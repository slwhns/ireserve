# Telegram Notifications Setup Guide

## Overview
The iReserve system now supports Telegram notifications for booking status changes. Users will receive instant notifications when their bookings are approved, rejected, taken, or returned.

## Setup Instructions

### Step 1: Database Setup
Run the SQL migration to add the Telegram Chat ID column:

```sql
-- Run this in your MySQL database
ALTER TABLE users 
ADD COLUMN telegram_chat_id VARCHAR(50) DEFAULT NULL AFTER picture_url;

CREATE INDEX idx_telegram_chat_id ON users(telegram_chat_id);
```

Or simply import the file: `database_telegram_migration.sql`

### Step 2: Create Telegram Bot

1. Open Telegram and search for **@BotFather**
2. Send the command: `/newbot`
3. Follow the instructions:
   - Choose a name for your bot (e.g., "iReserve Notifications")
   - Choose a username (must end with 'bot', e.g., "ireserve_upsi_bot")
4. Copy the Bot Token (format: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

### Step 3: Configure Bot Token

Edit `includes/telegram_config.php`:

```php
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE'); // Paste your token here
define('TELEGRAM_ENABLED', true); // Set to true to enable
```

### Step 4: Test Bot Connection

Create a test file `test_telegram.php` in your root directory:

```php
<?php
require_once __DIR__ . '/includes/telegram_helper.php';

$result = testTelegramBot();

if ($result['success']) {
    echo "✅ Bot connected successfully!\n";
    echo "Bot Name: " . $result['bot_info']['first_name'] . "\n";
    echo "Bot Username: @" . $result['bot_info']['username'] . "\n";
} else {
    echo "❌ Failed to connect: " . $result['message'] . "\n";
}
?>
```

Run: `php test_telegram.php`

### Step 5: User Setup - Getting Chat ID

Users need to get their Telegram Chat ID:

**Method 1: Using @userinfobot**
1. Open Telegram
2. Search for `@userinfobot`
3. Start the bot (click START or send any message)
4. Bot replies with Chat ID
5. Copy the Chat ID

**Method 2: Using your bot**
1. Search for your bot (e.g., `@ireserve_upsi_bot`)
2. Start a chat with the bot (click START)
3. Visit: `https://api.telegram.org/bot{YOUR_BOT_TOKEN}/getUpdates`
4. Look for `"chat":{"id":123456789}` in the JSON
5. Copy the number

### Step 6: Add Chat ID to Profile

1. Users log in to iReserve
2. Go to Settings page
3. Enter their Telegram Chat ID in the "Telegram Chat ID" field
4. Click "Save Changes"

## How It Works

### Notification Flow

1. Admin changes booking status (Approve/Reject/Taken/Returned)
2. System updates booking in database
3. System sends both:
   - Email notification (if email configured)
   - Telegram notification (if Chat ID provided)
4. User receives instant Telegram message

### Notification Types

**Approved** 🎉
```
Your booking request has been approved!
Item: [Item Name]
Booking ID: #123
Quantity: 2
Period: Nov 26, 2025 to Nov 30, 2025
```

**Rejected** ❌
```
Your booking request has been rejected.
Item: [Item Name]
Please contact administrator for more info.
```

**Taken** 📋
```
Item Ready for Collection
Your booking status: Taken
Please return by the end date.
```

**Returned** ✅
```
Item Return Confirmed
Thank you for using iReserve!
```

## Configuration Options

### Disable Telegram Notifications

Edit `includes/telegram_config.php`:

```php
define('TELEGRAM_ENABLED', false); // Disable all Telegram notifications
```

### Testing Notifications

Create a test file to send test notifications:

```php
<?php
require_once __DIR__ . '/includes/telegram_helper.php';

$testChatId = "YOUR_CHAT_ID"; // Replace with your Chat ID

$bookingData = [
    'booking_id' => 123,
    'item_name' => 'Test Item',
    'quantity' => 1,
    'start_date' => 'November 26, 2025',
    'end_date' => 'November 30, 2025'
];

$result = sendTelegramNotification($testChatId, "Test User", "approved", $bookingData);

echo $result ? "✅ Sent!" : "❌ Failed";
?>
```

## Troubleshooting

### Issue: "Failed to send Telegram notification"

**Solution:**
1. Check Bot Token is correct in `telegram_config.php`
2. Verify Chat ID is correct (numbers only, no spaces)
3. Ensure bot is not blocked by the user
4. Check error logs: `error_log()` messages

### Issue: Bot not responding

**Solution:**
1. Verify bot exists: Search for it in Telegram
2. Check Bot Token: Visit `https://api.telegram.org/bot{TOKEN}/getMe`
3. Ensure bot is not revoked in @BotFather

### Issue: User not receiving notifications

**Solution:**
1. User must START the bot first in Telegram
2. Verify Chat ID is saved in database
3. Check `telegram_chat_id` column in users table
4. Test with: `SELECT telegram_chat_id FROM users WHERE id = USER_ID;`

## Security Notes

- **Bot Token**: Keep it secret! Never commit to public repositories
- **Chat IDs**: Store securely, treat as sensitive user data
- **SSL**: Use HTTPS in production for API calls
- **Rate Limits**: Telegram limits ~30 messages/second

## Files Modified/Created

**New Files:**
- `includes/telegram_config.php` - Configuration
- `includes/telegram_helper.php` - Notification functions
- `database_telegram_migration.sql` - Database changes
- `TELEGRAM_SETUP.md` - This file

**Modified Files:**
- `admin/bookings_action.php` - Added Telegram notifications
- `user/settings.php` - Added Chat ID field + help modal
- `assets/css/settings.css` - Added modal styling

## Support

For issues or questions:
1. Check Telegram Bot API docs: https://core.telegram.org/bots/api
2. Review error logs in PHP error log
3. Test bot connection with `test_telegram.php`

## Next Steps (Optional Enhancements)

- [ ] Add inline keyboard buttons in Telegram messages
- [ ] Allow users to reply to bot for quick actions
- [ ] Send booking reminders via Telegram
- [ ] Add Telegram bot commands (/mybookings, /cancel, etc.)
- [ ] Create admin bot for managing bookings via Telegram

---

**Setup Complete!** 🎉

Users can now receive instant Telegram notifications for all booking updates!
