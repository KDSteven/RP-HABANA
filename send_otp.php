<?php
// send_otp.php
header('Content-Type: application/json');

require 'config/db.php'; // $conn (mysqli)
require_once __DIR__ . '/config/load_env.php';

// Housekeeping: remove expired OTPs (and optionally those with too many attempts)
$conn->query("DELETE FROM otp_codes WHERE expires_at < NOW() OR attempts >= 5");

//
// 1) Load secrets (use env vars if possible)
//
$SEMAPHORE_API_KEY = getenv('SEMAPHORE_API_KEY');
$SEMAPHORE_SENDER  = getenv('SEMAPHORE_SENDER')  ?: 'RHABANA'; // must be approved in Semaphore

if (!$SEMAPHORE_API_KEY || $SEMAPHORE_API_KEY === 'PUT_YOUR_API_KEY_HERE') {
  echo json_encode(['success' => false, 'message' => 'Semaphore API key not configured.']);
  exit;
}

//
// 2) Read and validate phone
//
$rawPhone = trim($_POST['phone_number'] ?? '');
if ($rawPhone === '') {
  echo json_encode(['success' => false, 'message' => 'Missing phone_number.']);
  exit;
}

function normalizePHMobile($p) {
  // Remove all non-digits
  $digits = preg_replace('/\D+/', '', $p);

  // Case 1: 09XXXXXXXXX → +639XXXXXXXXX
  if (preg_match('/^09\d{9}$/', $digits)) {
    return '+63' . substr($digits, 1);
  }

  // Case 2: 639XXXXXXXXX → +639XXXXXXXXX
  if (preg_match('/^639\d{9}$/', $digits)) {
    return '+' . $digits;
  }

  // Case 3: +639XXXXXXXXX → keep it
  if (preg_match('/^\+639\d{9}$/', $p)) {
    return $p;
  }

  // If none matched, just return original (Semaphore will reject if invalid)
  return $p;
}

$phone = normalizePHMobile($rawPhone);

// Basic PH mobile format guard
if (!preg_match('/^\+639\d{9}$/', $phone)) {
  echo json_encode(['success' => false, 'message' => 'Invalid PH mobile number. Use 09XXXXXXXXX or +639XXXXXXXXX.']);
  exit;
}

//
// 3) Generate OTP and upsert to DB (5-minute expiry)
//
$otp     = strval(random_int(100000, 999999));
$expires = date('Y-m-d H:i:s', time() + 5 * 60);

// Prevent spam: 1 OTP per minute
$check = $conn->prepare("SELECT created_at FROM otp_codes WHERE phone_number=?");
$check->bind_param('s', $phone);
$check->execute();
$row = $check->get_result()->fetch_assoc();
$check->close();

if ($row && strtotime($row['created_at']) > time() - 60) {
  echo json_encode(['success' => false, 'message' => 'Please wait 1 minute before requesting another OTP.']);
  exit;
}

$stmt = $conn->prepare("
  INSERT INTO otp_codes (phone_number, otp, expires_at, attempts)
  VALUES (?, ?, ?, 0)
  ON DUPLICATE KEY UPDATE
    otp = VALUES(otp),
    expires_at = VALUES(expires_at),
    attempts = 0
");
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'DB error (prepare): '.$conn->error]);
  exit;
}
$stmt->bind_param('sss', $phone, $otp, $expires);
if (!$stmt->execute()) {
  $stmt->close();
  echo json_encode(['success' => false, 'message' => 'DB error (execute): '.$conn->error]);
  exit;
}
$stmt->close();

//
// 4) Build message
//
$message = "Your One-Time Passcode (OTP) is {$otp}. It expires in 5 minutes. Do not share this code.";

// Optional: Throttle per phone to avoid spamming (example basic lockout)
// You can keep a separate table or reuse otp_codes with timestamps if needed.

//
// 5) Send via Semaphore
//
$endpoint = 'https://api.semaphore.co/api/v4/messages';
$postData = [
  'apikey'     => $SEMAPHORE_API_KEY,
  'number'     => $phone,         // single number
  'message'    => $message,
  'sendername' => $SEMAPHORE_SENDER // must be registered/approved
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
  CURLOPT_POST            => true,
  CURLOPT_POSTFIELDS      => http_build_query($postData),
  CURLOPT_RETURNTRANSFER  => true,
  CURLOPT_CONNECTTIMEOUT  => 10,
  CURLOPT_TIMEOUT         => 20,
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
  echo json_encode(['success' => false, 'message' => 'Semaphore request failed: '.$curlErr]);
  exit;
}

// Semaphore returns JSON (array of sent messages) or an error object.
// Examples:
//  - Success: [ { "message_id":"...", "status":"Queued", ... } ]
//  - Error:   { "error":"Invalid apikey" }
$parsed = json_decode($response, true);

// If parse fails, show raw
if ($parsed === null) {
  echo json_encode([
    'success' => false,
    'message' => 'Semaphore response error.',
    'debug'   => ['http_code' => $httpCode, 'raw' => $response]
  ]);
  exit;
}

// Determine success
$ok = false;
if (is_array($parsed)) {
  // If it’s a numerically-indexed array, treat as success
  if (isset($parsed[0]) && isset($parsed[0]['status'])) {
    $ok = true;
  } elseif (isset($parsed['error'])) {
    $ok = false;
  }
}

// Final response
if ($ok && $httpCode >= 200 && $httpCode < 300) {
  echo json_encode(['success' => true, 'message' => 'OTP sent successfully.']);
} else {
  $errMsg = is_array($parsed) && isset($parsed['error']) ? $parsed['error'] : 'Failed to send OTP.';
  echo json_encode([
    'success' => false,
    'message' => $errMsg,
    'debug'   => ['http_code' => $httpCode, 'response' => $parsed]
  ]);
}
