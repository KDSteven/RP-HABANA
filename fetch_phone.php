<?php
session_start();               // ⬅️ add this
include 'config/db.php';
header('Content-Type: application/json');

$username = trim($_POST['username'] ?? '');
if ($username === '') {
  echo json_encode(['success' => false, 'message' => 'Username is required.']);
  exit;
}

// Normalize function (same as in send_otp and verify_otp)
function normalizePHMobile($p) {
  $digits = preg_replace('/\D+/', '', $p);
  if (preg_match('/^09\d{9}$/', $digits)) return '+63' . substr($digits, 1);
  if (preg_match('/^639\d{9}$/', $digits)) return '+' . $digits;
  if (preg_match('/^\+639\d{9}$/', $p)) return $p;
  return $p;
}

$stmt = $conn->prepare("SELECT phone_number FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  $phone = normalizePHMobile($row['phone_number']);

  // Validate format before returning
  if (!preg_match('/^\+639\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format in user record.']);
  } else {
    echo json_encode(['success' => true, 'phone_number' => $phone]);
  }
} else {
  echo json_encode(['success' => false, 'message' => 'No account found with that username.']);
}

$stmt->close();
$conn->close();
?>
