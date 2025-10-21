<?php
// verify_otp.php
header('Content-Type: application/json');
session_start();
date_default_timezone_set('Asia/Manila');

require 'config/db.php';

// --- TEMP: safer error handling while you debug ---
// Comment these three lines out in production if you prefer.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Small helper to reply JSON and exit
function jexit($arr) {
  echo json_encode($arr);
  exit;
}

// 1) Read request
$rawPhone = trim($_POST['phone_number'] ?? '');
$otp      = trim($_POST['otp'] ?? '');

if ($rawPhone === '' || $otp === '') {
  jexit(['success' => false, 'message' => 'Missing phone number or OTP.']);
}

// 2) Normalize phone (match your send_otp.php)
function normalizePHMobile($p) {
  $digits = preg_replace('/\D+/', '', $p);
  if (preg_match('/^09\d{9}$/', $digits)) return '+63' . substr($digits, 1);     // 09XXXXXXXXX -> +639XXXXXXXXX
  if (preg_match('/^639\d{9}$/', $digits)) return '+' . $digits;                  // 639XXXXXXXXX -> +639XXXXXXXXX
  if (preg_match('/^\+639\d{9}$/', $p))    return $p;                             // already +639XXXXXXXXX
  return $p; // fallback
}
$phone = normalizePHMobile($rawPhone);

// Quick guard
if (!preg_match('/^\+639\d{9}$/', $phone)) {
  jexit(['success' => false, 'message' => 'Invalid phone number format.']);
}

try {
  // 3) Housekeeping: delete expired OTPs so table doesn't grow forever
  $conn->query("DELETE FROM otp_codes WHERE expires_at < NOW()");

  // 4) Fetch the latest OTP for this phone
  // NOTE: requires otp_codes.created_at to default CURRENT_TIMESTAMP
  $stmt = $conn->prepare("
    SELECT otp, expires_at, attempts
    FROM otp_codes
    WHERE phone_number = ?
    ORDER BY created_at DESC
    LIMIT 1
  ");
  $stmt->bind_param('s', $phone);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  $stmt->close();

  if (!$row) {
    jexit(['success' => false, 'message' => 'No OTP found for this number. Please request a new one.']);
  }

  // 5) Expired?
  if (strtotime($row['expires_at']) < time()) {
    // delete stale row for this phone (optional)
    $del = $conn->prepare("DELETE FROM otp_codes WHERE phone_number = ?");
    $del->bind_param('s', $phone);
    $del->execute();
    $del->close();

    jexit(['success' => false, 'message' => 'OTP expired. Please request a new one.']);
  }

  // 6) Attempts guard (max 5)
  if ((int)$row['attempts'] >= 5) {
    jexit(['success' => false, 'message' => 'Too many invalid attempts. Please request a new OTP.']);
  }

  // 7) Compare
  if (hash_equals($row['otp'], $otp)) {
    // Valid → remove OTP (so it can’t be reused)
    $del = $conn->prepare("DELETE FROM otp_codes WHERE phone_number = ?");
    $del->bind_param('s', $phone);
    $del->execute();
    $del->close();

    // Flag session so change_password.php allows reset
    $_SESSION['pw_reset_ok']    = true;
    $_SESSION['pw_reset_phone'] = $phone; // normalized +639XXXXXXXXX

    jexit([
      'success'  => true,
      'message'  => 'OTP verified successfully.',
      'redirect' => 'change_password.php'
    ]);
  } else {
    // Bump attempts
    $upd = $conn->prepare("UPDATE otp_codes SET attempts = attempts + 1 WHERE phone_number = ?");
    $upd->bind_param('s', $phone);
    $upd->execute();
    $upd->close();

    jexit(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
  }
} catch (Throwable $e) {
  // If something PHP/SQL fails, return a friendly message + tiny debug hint
  jexit([
    'success' => false,
    'message' => 'Server error while verifying OTP.',
    // Uncomment next line only while debugging:
    // 'debug'   => $e->getMessage()
  ]);
}
