<?php
// bookings_action.php
session_name('ireserve_admin');
session_start();
require_once __DIR__ . '/../config.php';

// Ensure admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['ok' => false, 'message' => 'Forbidden - Role: ' . ($_SESSION['role'] ?? 'NOT SET')]);
  exit;
}

header('Content-Type: application/json');

$valid = ['approved','rejected','collect','returned'];
$action = $_POST['action'] ?? '';
$ref    = $_POST['ref_code'] ?? '';
$note   = trim($_POST['note'] ?? '');

if (!in_array($action, $valid, true) || $ref==='') {
  echo json_encode(['ok'=>false,'message'=>'Invalid request']);
  exit;
}

$adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
if (!$adminId) {
  echo json_encode(['ok'=>false,'message'=>'Invalid admin session']);
  exit;
}

// Get admin's faculty ID
$adminFacultyId = $_SESSION['faculty_id'] ?? null;

// Verify booking belongs to admin's faculty before allowing action
if ($adminFacultyId) {
  $checkStmt = $conn->prepare("SELECT b.ref_code FROM bookings b 
                               JOIN inventory_items i ON i.item_id = b.item_id 
                               JOIN faculties f ON CONCAT(f.faculty_name, ' (', f.faculty_code, ')') COLLATE utf8mb4_unicode_ci = i.location 
                               WHERE b.ref_code = ? AND f.faculty_id = ?");
  $checkStmt->bind_param("si", $ref, $adminFacultyId);
  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();
  if ($checkResult->num_rows === 0) {
    $checkStmt->close();
    echo json_encode(['ok'=>false,'message'=>'Unauthorized - booking not in your faculty']);
    exit;
  }
  $checkStmt->close();
} else {
  echo json_encode(['ok'=>false,'message'=>'No faculty assigned']);
  exit;
}

// Update booking status
// Build SQL to also mark actual period: when 'collect' => set start_at NOW(); when 'returned' => set end_at NOW()
if ($action === 'collect') {
  $sql = "UPDATE bookings 
    SET status = ?, admin_note = ?, decided_by = ?, decided_at = NOW(), start_at = NOW()
    WHERE ref_code = ?";
  if (!$stmt = $conn->prepare($sql)) {
    echo json_encode(['ok'=>false, 'message'=>'Database error (prepare)']);
    exit;
  }
  $stmt->bind_param('ssis', $action, $note, $adminId, $ref);
  $ok = $stmt->execute();
  $affected = $conn->affected_rows;
  $stmt->close();
} elseif ($action === 'returned') {
  $sql = "UPDATE bookings 
    SET status = ?, admin_note = ?, decided_by = ?, decided_at = NOW(), end_at = NOW()
    WHERE ref_code = ?";
  if (!$stmt = $conn->prepare($sql)) {
    echo json_encode(['ok'=>false, 'message'=>'Database error (prepare)']);
    exit;
  }
  $stmt->bind_param('ssis', $action, $note, $adminId, $ref);
  $ok = $stmt->execute();
  $affected = $conn->affected_rows;
  $stmt->close();
} else {
  $sql = "UPDATE bookings 
    SET status = ?, admin_note = ?, decided_by = ?, decided_at = NOW()
    WHERE ref_code = ?";
  if (!$stmt = $conn->prepare($sql)) {
    echo json_encode(['ok'=>false, 'message'=>'Database error (prepare)']);
    exit;
  }
  $stmt->bind_param('ssis', $action, $note, $adminId, $ref);
  $ok = $stmt->execute();
  $affected = $conn->affected_rows;
  $stmt->close();
}

if ($ok && $affected > 0) {
  // Try to send email and Telegram notifications, but don't fail if they fail
  try {
    // Fetch booking and user details for notifications
    $notifSql = "SELECT b.booking_id, b.ref_code, b.qty, b.start_at, b.end_at, b.admin_note,
                        u.email, u.name as user_name, u.telegram_chat_id,
                        i.name as item_name
                 FROM bookings b
                 JOIN users u ON b.user_id = u.id
                 LEFT JOIN inventory_items i ON b.item_id = i.item_id
                 WHERE b.ref_code = ?";
    
    if ($notifStmt = $conn->prepare($notifSql)) {
      $notifStmt->bind_param('s', $ref);
      $notifStmt->execute();
      $notifResult = $notifStmt->get_result();
      
      if ($notifData = $notifResult->fetch_assoc()) {
        // Prepare notification data
        $bookingData = [
          'booking_id' => $notifData['booking_id'],
          'ref_code' => $notifData['ref_code'],
          'item_name' => $notifData['item_name'],
          'quantity' => $notifData['qty'],
          'start_date' => date('F j, Y', strtotime($notifData['start_at'])),
          'end_date' => date('F j, Y', strtotime($notifData['end_at'])),
          'admin_note' => $notifData['admin_note']
        ];
        
        // Send Email Notification
        if (file_exists(__DIR__ . '/../includes/email_helper.php')) {
          require_once __DIR__ . '/../includes/email_helper.php';
          sendBookingNotification(
            $notifData['email'],
            $notifData['user_name'],
            $action,
            $bookingData
          );
        }
        
        // Send Telegram Notification
        if (file_exists(__DIR__ . '/../includes/telegram_helper.php')) {
          require_once __DIR__ . '/../includes/telegram_helper.php';
          if (!empty($notifData['telegram_chat_id'])) {
            error_log("Sending Telegram to Chat ID: " . $notifData['telegram_chat_id']);
            $telegramResult = sendTelegramNotification(
              $notifData['telegram_chat_id'],
              $notifData['user_name'],
              $action,
              $bookingData
            );
            error_log("Telegram send result: " . ($telegramResult ? 'SUCCESS' : 'FAILED'));
          } else {
            error_log("No Telegram Chat ID found for user: " . $notifData['user_name']);
          }
        } else {
          error_log("Telegram helper file not found");
        }
      }
      $notifStmt->close();
    }
  } catch (Exception $e) {
    // Log error but continue - don't fail the booking update
    error_log("Notification failed: " . $e->getMessage());
  }
  
  echo json_encode(['ok'=>true, 'message'=>'Updated']);
} else if ($ok && $affected === 0) {
  echo json_encode(['ok'=>false, 'message'=>'Booking not found']);
} else {
  echo json_encode(['ok'=>false, 'message'=>'Update failed: ' . $conn->error]);
}
exit;