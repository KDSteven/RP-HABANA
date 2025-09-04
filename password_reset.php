<?php
// password_reset.php
session_start();
include 'config/db.php';

header('Content-Type: application/json');

$response = ["status" => "error", "message" => "Unknown error."];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');

    if (empty($username)) {
        $response = ["status" => "error", "message" => "❌ Username is required."];
        echo json_encode($response);
        exit;
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, role FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res  = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $response = ["status" => "error", "message" => "❌ Username not found."];
        echo json_encode($response);
        exit;
    }

    if ($user['role'] === 'admin') {
        $response = ["status" => "error", "message" => "❌ Admin accounts cannot request reset this way."];
        echo json_encode($response);
        exit;
    }

    // Check for existing pending request
    $stmt = $conn->prepare("SELECT id FROM password_resets WHERE user_id=? AND status='Pending'");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    if ($res->num_rows > 0) {
        $response = ["status" => "warning", "message" => "⚠️ You already have a pending request. Please wait for Admin approval."];
        echo json_encode($response);
        exit;
    }

    // Insert new request
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, requested_by, status, requested_at) VALUES (?, ?, 'Pending', NOW())");
    $stmt->bind_param("ii", $user['id'], $user['id']);
    if ($stmt->execute()) {
        $response = ["status" => "success", "message" => "✅ Request sent successfully! Please wait for Admin approval."];
    } else {
        $response = ["status" => "error", "message" => "❌ Something went wrong. Please try again."];
    }
    $stmt->close();
}

echo json_encode($response);
