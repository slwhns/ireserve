<?php
/**
 * Telegram Configuration
 * 
 * Configure your Telegram Bot settings here for sending notifications
 */

// Telegram Settings
define('TELEGRAM_ENABLED', true); // Set to false to disable Telegram notifications

// Bot Configuration
define('TELEGRAM_BOT_TOKEN', '8480029911:AAGIpbrkbEZQAsBmmQKW83iQzdKfCJgpiGY'); // Get from @BotFather on Telegram

/**
 * Setup Instructions:
 * 
 * STEP 1: Create a Telegram Bot
 * 1. Open Telegram and search for @BotFather
 * 2. Send /newbot command
 * 3. Follow the instructions to name your bot (e.g., "iReserve Notifications Bot")
 * 4. Copy the Bot Token (e.g., 123456789:ABCdefGHIjklMNOpqrsTUVwxyz)
 * 5. Paste it in TELEGRAM_BOT_TOKEN above
 * 
 * STEP 2: Get User Chat IDs
 * Option A - Using @userinfobot:
 * 1. Users search for @userinfobot on Telegram
 * 2. Start a chat with the bot
 * 3. Bot will reply with their Chat ID
 * 4. Users provide this Chat ID when registering/updating profile
 * 
 * Option B - Using your bot directly:
 * 1. Users start a chat with your bot (search by bot username)
 * 2. Send /start command
 * 3. Visit: https://api.telegram.org/bot{YOUR_BOT_TOKEN}/getUpdates
 * 4. Look for "chat":{"id":123456789} in the JSON response
 * 5. The number is the user's Chat ID
 * 
 * STEP 3: Store Chat IDs in Database
 * - Add a 'telegram_chat_id' column to the users table
 * - Users can update their Telegram Chat ID in their profile settings
 * 
 * SQL Command to add column:
 * ALTER TABLE users ADD COLUMN telegram_chat_id VARCHAR(50) DEFAULT NULL;
 */
?>
