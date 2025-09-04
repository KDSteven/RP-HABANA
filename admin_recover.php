<?php
session_start();
include 'config/db.php';
include 'config/secrets.php'; // where MASTER_RESET_HASH is stored

header('Content-Type: application/json');

$username = trim($_POST['username'] ?? '');
$recovery_code = trim($_POST['recovery_code'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if ($username === '' || $recovery_code === '' || $new_password === '' || $confirm_password === '') {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(["status" => "error", "message" => "Passwords do not match."]);
    exit;
}

// Step 1: Check admin user exists
$stmt = $conn->prepare("SELECT id, role FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit;
}
if ($user['role'] !== 'admin') {
    echo json_encode(["status" => "error", "message" => "Only admins can use recovery."]);
    exit;
}

// Step 2: Check recovery code
if (strlen($recovery_code) < 6) {
    echo json_encode(["status" => "error", "message" => "Recovery code seems too short. Did you type the plain code, not the hash?"]);
    exit;
}

if (!password_verify($recovery_code, MASTER_RESET_HASH)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid recovery code. (DEBUG: Plain entered = '{$recovery_code}')"
    ]);
    exit;
}

// Step 3: Update password
$hash = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password=?, must_change_password=0 WHERE id=?");
$stmt->bind_param("si", $hash, $user['id']);
$stmt->execute();
$stmt->close();

echo json_encode([
    "status" => "success",
    "message" => "Password reset successful. You can now login with your new password."
]);
