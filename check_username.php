<?php
require 'config/db.php'; // adjust path if needed
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$username = trim($body['username'] ?? '');

$validSyntax = (bool) preg_match('/^[A-Za-z0-9._]{4,20}$/', $username);

if ($username === '' || !$validSyntax) {
  echo json_encode(['ok' => true, 'available' => false, 'reason' => 'invalid']);
  exit;
}

$stmt = $conn->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;
$stmt->close();

echo json_encode(['ok' => true, 'available' => !$exists]);
