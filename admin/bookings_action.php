<?php
// bookings_action.php
ob_start(); // Start output buffering to catch any unwanted output
header('Content-Type: application/json');

// Auth and DB
require_once __DIR__ . '/includes/auth.php'; // starts session and includes config.php
ob_end_clean(); // Clear any output from includes

// Ensure admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['ok' => false, 'message' => 'Forbidden']);
  exit;
}

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
  echo json_encode(['ok'=>true, 'message'=>'Updated']);
} else if ($ok && $affected === 0) {
  echo json_encode(['ok'=>false, 'message'=>'Booking not found']);
} else {
  echo json_encode(['ok'=>false, 'message'=>'Update failed: ' . $conn->error]);
}
exit;