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

$valid = ['approved','rejected','taken','returned'];
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

// Update booking status
// Build SQL to also mark actual period: when 'taken' => set start_at NOW(); when 'returned' => set end_at NOW()
if ($action === 'taken') {
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
  // Try to send email notification, but don't fail if email fails
  try {
    // Load email helper only when needed
    if (file_exists(__DIR__ . '/../includes/email_helper.php')) {
      require_once __DIR__ . '/../includes/email_helper.php';
      
      // Fetch booking and user details for email notification
      $emailSql = "SELECT b.ref_code, b.start_at, b.end_at, b.admin_note,
                          u.email, u.name as user_name,
                          i.name as item_name
                   FROM bookings b
                   JOIN users u ON b.user_id = u.id
                   LEFT JOIN inventory_items i ON b.item_id = i.item_id
                   WHERE b.ref_code = ?";
      
      if ($emailStmt = $conn->prepare($emailSql)) {
        $emailStmt->bind_param('s', $ref);
        $emailStmt->execute();
        $emailResult = $emailStmt->get_result();
        
        if ($emailData = $emailResult->fetch_assoc()) {
          // Send email notification
          $bookingData = [
            'ref_code' => $emailData['ref_code'],
            'item_name' => $emailData['item_name'],
            'start_at' => date('F j, Y', strtotime($emailData['start_at'])),
            'end_at' => date('F j, Y', strtotime($emailData['end_at'])),
            'admin_note' => $emailData['admin_note']
          ];
          
          sendBookingNotification(
            $emailData['email'],
            $emailData['user_name'],
            $action,
            $bookingData
          );
        }
        $emailStmt->close();
      }
    }
  } catch (Exception $e) {
    // Log error but continue - don't fail the booking update
    error_log("Email notification failed: " . $e->getMessage());
  }
  
  echo json_encode(['ok'=>true, 'message'=>'Updated']);
} else if ($ok && $affected === 0) {
  echo json_encode(['ok'=>false, 'message'=>'Booking not found']);
} else {
  echo json_encode(['ok'=>false, 'message'=>'Update failed: ' . $conn->error]);
}
exit;