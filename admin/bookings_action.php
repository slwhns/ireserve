<?php
// bookings_action.php
header('Content-Type: application/json');

// Auth and DB
require_once __DIR__ . '/includes/auth.php'; // starts session and includes config.php

// Ensure admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['ok' => false, 'message' => 'Forbidden']);
  exit;
}

$valid = ['approved','rejected','returned'];
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
$sql = "UPDATE bookings 
    SET status = ?, admin_note = ?, decided_by = ?, decided_at = NOW()
    WHERE ref_code = ?";

if (!$stmt = $conn->prepare($sql)) {
  echo json_encode(['ok'=>false, 'message'=>'Database error (prepare)']);
  exit;
}
$stmt->bind_param('ssis', $action, $note, $adminId, $ref);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['ok'=>$ok, 'message'=>$ok?'Updated':'Update failed']);
